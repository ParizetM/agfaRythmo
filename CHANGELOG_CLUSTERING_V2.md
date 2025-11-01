# 🎯 Amélioration Clustering Diarization - Version 2.0

**Date** : 31 janvier 2025  
**Objectif** : Améliorer la précision de détection des locuteurs en utilisant une approche "embeddings-like"

---

## 🔴 Problème Initial

**Symptômes** :
- ❌ Même personne → Plusieurs speakers différents
- ❌ Personnes différentes → Même speaker
- ❌ Résultats complètement incohérents

**Cause identifiée** :
Les 112 dimensions de features (MFCC, pitch, spectral, chroma, formants) capturent des **propriétés acoustiques** mais pas l'**identité vocale**.

La distance euclidienne sur ces features brutes ne fonctionne pas car :
- Elle compare les magnitudes (amplitude) au lieu de l'identité
- Les features ne sont pas "normalisées" comme des embeddings
- Différentes dimensions ont des échelles très différentes

---

## ✅ Solution Implémentée

### 1️⃣ **Normalisation L2 des Features**

**Avant** :
```python
features_array = np.array([...])  # 112D raw features
clustering = AgglomerativeClustering(metric='euclidean', linkage='ward')
labels = clustering.fit_predict(features_array)
```

**Après** :
```python
from sklearn.preprocessing import normalize
features_normalized = normalize(features_array, norm='l2')  # Vecteurs unitaires
```

**Effet** :
- Convertit chaque vecteur 112D en **vecteur unitaire** (longueur = 1)
- Comme les embeddings de speaker recognition professionnels
- Focus sur la **direction** (identité) au lieu de la magnitude (volume)

---

### 2️⃣ **Distance Cosine au lieu d'Euclidienne**

**Avant** :
```python
metric='euclidean'  # Distance dans l'espace, sensible à l'amplitude
linkage='ward'      # Minimise variance intra-cluster
```

**Après** :
```python
metric='cosine'     # Mesure l'angle entre vecteurs (similarité d'identité)
linkage='average'   # Compatible avec cosine
```

**Effet** :
- Distance cosine = 1 - cosine_similarity
- Mesure l'**angle** entre vecteurs au lieu de la distance euclidienne
- Insensible à l'échelle (volume, amplitude)
- Parfait pour comparer identités vocales

---

### 3️⃣ **Approche Hybride avec Fallback**

**Algorithme** :
1. **Essai COSINE** (méthode principale) :
   - Sur features normalisées (L2)
   - Teste k=2 à k=min(max_speakers, 8)
   - Silhouette score avec metric='cosine'
   - Validation : minimum 2 segments par cluster

2. **Fallback EUCLIDEAN** (si score cosine < 0.35) :
   - Sur features brutes
   - Même process de sélection
   - Garde le meilleur des deux

**Code** :
```python
# Approche 1 : Cosine sur features normalisées
for n_clusters in range(2, min(max_speakers + 1, 9)):
    clustering = AgglomerativeClustering(
        n_clusters=n_clusters,
        metric='cosine',
        linkage='average'
    )
    labels = clustering.fit_predict(features_normalized)
    
    # Validation : min 2 segments/cluster
    unique, counts = np.unique(labels, return_counts=True)
    if np.any(counts < 2):
        continue
    
    score = silhouette_score(features_normalized, labels, metric='cosine')
    # Garde le meilleur score

# Approche 2 : Euclidean fallback si score cosine faible
if best_score < 0.35:
    # Même process avec euclidean...
```

---

## 📊 Logs Améliorés

**Nouveaux messages** :
```
🔧 Features normalisées (L2) : (25, 112)

🧪 Test clustering (2 à 8 clusters)...
  📐 Méthode COSINE (sur features normalisées):
    k=2: silhouette=0.542, distribution={0: 12, 1: 13}
    k=3: silhouette=0.489, distribution={0: 8, 1: 9, 2: 8}
    k=4: silhouette=0.621, distribution={0: 6, 1: 7, 2: 6, 3: 6}
    
✅ Meilleur: 4 clusters (silhouette=0.621, méthode=cosine)
  Cluster 0: 6 segments, Baryton, pitch_moy=125.3Hz
  Cluster 1: 7 segments, Soprano, pitch_moy=245.7Hz
  Cluster 2: 6 segments, Tenor, pitch_moy=165.2Hz
  Cluster 3: 6 segments, Alto, pitch_moy=195.4Hz
```

**Informations affichées** :
- ✅ Méthode utilisée (cosine ou euclidean)
- ✅ Distribution des segments par cluster
- ✅ Silhouette score pour chaque k testé
- ✅ Détails par cluster (tessiture, pitch moyen)

---

## 🧪 Comment Tester

### Option 1 : Script de Test Automatique

```bash
cd /Users/martinp/Documents/GitHub/agfaRythmo
./test_new_clustering.sh
```

**Ce qu'il fait** :
1. Prend une vidéo de test
2. Extrait l'audio (WAV 16kHz mono)
3. Transcription Whisper (modèle tiny)
4. Lance diarization avec nouveau clustering
5. Affiche les speakers détectés et leur distribution

### Option 2 : Test Manuel

```bash
cd agfa-rythmo-backend

# 1. Extraire audio
ffmpeg -i storage/app/private/public/videos/VOTRE_VIDEO.mp4 \
    -ar 16000 -ac 1 /tmp/audio.wav

# 2. Whisper
whisper /tmp/audio.wav --model tiny --language fr \
    --output_format json --output_dir /tmp

# 3. Diarization (nouveau clustering)
python3 scripts/simple_diarization.py \
    /tmp/audio.wav \
    /tmp/audio.json \
    /tmp/diarization.json \
    --max-speakers 10

# 4. Analyser résultat
cat /tmp/diarization.json | jq '.segments[] | {speaker, text}'
```

---

## 📈 Amélioration Attendue

### Avant (Euclidean brut)
- 4 personnes → Détection : 2 speakers (sous-détection)
- Même personne → 3 speakers différents (sur-segmentation)
- **Score utilisateur** : "nul a chier" ❌

### Après (L2 + Cosine)
- 4 personnes → Détection : 4 speakers ✅
- Même personne → 1 speaker cohérent ✅
- **Score attendu** : Précision significativement améliorée 🎯

**Pourquoi** :
- Normalisation L2 → Features deviennent des "embeddings" unitaires
- Distance cosine → Compare identité vocale, pas amplitude
- Validation clusters → Évite sur-segmentation
- Fallback euclidean → Robustesse si cosine échoue

---

## 🔬 Théorie : Embeddings vs Features Brutes

### Features Brutes (ancien)
```
Personne A : [12.3, -5.6, 8.2, ..., 145.7]  (volume fort)
Personne A : [6.1, -2.8, 4.1, ..., 72.8]   (volume faible)
Distance euclidienne = GRANDE (différentes magnitudes)
→ Classées comme 2 personnes différentes ❌
```

### Embeddings Normalisés (nouveau)
```
Personne A : [0.34, -0.15, 0.23, ..., 0.41]  (normalisé)
Personne A : [0.34, -0.15, 0.23, ..., 0.41]  (normalisé)
Distance cosine = PETITE (même direction)
→ Classées comme même personne ✅
```

**Analogie** :
- Features brutes = position GPS (lat, lon, altitude)
- Embeddings = direction boussole (angle seulement)
- Pour identifier une personne, on veut sa "direction vocale", pas son volume

---

## 🚀 Prochaines Étapes

### Si ça marche (score > 0.8)
1. ✅ Valider avec plusieurs vidéos de test
2. ✅ Supprimer anciens logs debug
3. ✅ Documenter dans instructions_projets.md
4. ✅ Merger dans main

### Si amélioration mais pas parfait (score 0.5-0.7)
1. 🔧 Augmenter features (200D au lieu de 112D)
2. 🔧 Ajouter features prosodiques (intonation, rythme)
3. 🔧 Essayer combinaison cosine + euclidean pondérée

### Si toujours cassé (score < 0.5)
1. 🔍 Envisager modèle pré-entraîné léger :
   - **Resemblyzer** : 256D embeddings, ~20MB
   - **SpeechBrain** : xvectors, ~50MB
   - Trade-off : +30MB RAM mais vraie qualité embeddings
2. 🔍 Ou : UI semi-supervisée (user clique quelques segments par speaker)

---

## 📝 Fichiers Modifiés

- `agfa-rythmo-backend/scripts/simple_diarization.py` :
  - Fonction `apply_clustering()` complètement refactorisée
  - Ajout normalisation L2
  - Ajout distance cosine
  - Approche hybride cosine + euclidean
  - Logs détaillés

- `test_new_clustering.sh` (nouveau) :
  - Script de test automatique complet

- `CHANGELOG_CLUSTERING_V2.md` (ce fichier) :
  - Documentation changements

---

## 🎓 Ressources

**Distance Cosine** :
- [Cosine Similarity - Wikipedia](https://en.wikipedia.org/wiki/Cosine_similarity)
- Formule : `cosine_similarity = dot(A, B) / (||A|| * ||B||)`
- Distance : `1 - cosine_similarity`

**Speaker Embeddings** :
- [X-vectors for Speaker Recognition](https://www.danielpovey.com/files/2018_icassp_xvectors.pdf)
- [Resemblyzer](https://github.com/resemble-ai/Resemblyzer)
- Idée : Deep learning → embeddings discriminatifs

**L2 Normalization** :
- Convertit vecteur en vecteur unitaire (longueur = 1)
- Formule : `v_normalized = v / ||v||`
- Effet : Distance euclidienne ≈ Distance cosine (pour vecteurs unitaires)

---

**Auteur** : GitHub Copilot + Martin P.  
**Version** : 2.0 (Clustering avec embeddings-like features)  
**Status** : 🧪 En test

---

## ✨ TL;DR

**Changement** : Features 112D → Normalisées L2 → Distance Cosine au lieu d'Euclidienne

**Pourquoi** : Traiter features comme des "embeddings" vocaux (direction) au lieu de mesures acoustiques brutes (magnitude)

**Résultat attendu** : Même personne = même speaker, Personnes différentes = speakers différents

**Comment tester** : `./test_new_clustering.sh`
