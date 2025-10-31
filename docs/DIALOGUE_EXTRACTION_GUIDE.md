# Guide d'extraction automatique des dialogues

**Version** : 2.2.0-beta | **Date** : 31 octobre 2025

## 📋 Vue d'ensemble

L'extraction automatique de dialogues utilise l'intelligence artificielle pour transcrire automatiquement les paroles d'une vidéo et créer les timecodes avec détection des locuteurs.

### Fonctionnalités

- ✅ **Transcription automatique** : Extraction de tous les dialogues de la vidéo
- ✅ **Détection des locuteurs** : Identification automatique des différents speakers (diarization)
- ✅ **Multi-langue** : Support de 12 langues (français, anglais, chinois, japonais, etc.)
- ✅ **Création automatique** : Timecodes et personnages générés automatiquement
- ✅ **Optimisé 2GB RAM** : Utilise des modèles légers (Whisper tiny/base/small)
- ✅ **Traitement local** : Aucune donnée envoyée à des services externes
- ✅ **Progression temps réel** : Suivi détaillé de l'extraction en cours
- ✅ **Cancellation** : Possibilité d'annuler à tout moment

---

## 🔧 Installation

### Prérequis

- **Python 3.8+** installé sur le serveur
- **FFmpeg** pour extraction audio
- **2GB RAM minimum** recommandé
- **Espace disque** : ~500MB pour les modèles Whisper

### Installation des dépendances Python

```bash
cd agfa-rythmo-backend
pip install -r scripts/requirements.txt
```

**Contenu de `scripts/requirements.txt`** :
```
openai-whisper==20231117
pyannote-audio==3.1.1
```

### Configuration `.env`

Ajouter dans `agfa-rythmo-backend/.env` :

```bash
# ======== Extraction de dialogues IA ========
AI_DIALOGUE_EXTRACTION_ENABLED=true

# Modèle Whisper (tiny/base/small - tiny recommandé pour 2GB RAM)
WHISPER_MODEL=tiny

# Diarization (détection locuteurs)
DIARIZATION_ENABLED=true
MAX_SPEAKERS=10

# Langues supportées (fr,en,zh,ja,es,de,it,pt,ru,ko,ar,hi)
SUPPORTED_LANGUAGES=fr,en,zh,ja,es,de,it,pt,ru,ko,ar,hi
```

### Vérification de l'installation

```bash
# Tester FFmpeg
ffmpeg -version

# Tester Python et les dépendances
python3 agfa-rythmo-backend/scripts/extract_dialogues.py --help
```

---

## 🎯 Utilisation

### 1. Depuis l'interface (recommandé)

1. **Ouvrir un projet** sans timecodes existants
2. **Cliquer sur le bouton "IA"** (icône étoile, gradient violet/rose)
3. **Sélectionner "Extraction de dialogues"** dans le menu IA
4. **Configurer les paramètres** :
   - **Langue** : Choisir la langue parlée dans la vidéo
   - **Max speakers** : Nombre maximum de locuteurs à détecter (2-20)
   - **Modèle Whisper** : 
     - `tiny` : Rapide, léger (2GB RAM OK)
     - `base` : Plus précis, 4GB RAM
     - `small` : Meilleure qualité, 8GB RAM
5. **Lancer l'extraction**
6. **Suivre la progression** (4 étapes) :
   - Extraction audio (0-20%)
   - Transcription Whisper (20-70%)
   - Détection locuteurs (70-90%)
   - Création timecodes (90-100%)
7. **Résultat** : Timecodes et personnages créés automatiquement !

### 2. Via API (avancé)

**Démarrer l'extraction** :
```bash
POST /api/projects/{project_id}/dialogue-extraction/start
Content-Type: application/json

{
  "language": "fr",
  "max_speakers": 5,
  "whisper_model": "tiny"
}
```

**Vérifier le statut** :
```bash
GET /api/projects/{project_id}/dialogue-extraction/status
```

**Annuler l'extraction** :
```bash
POST /api/projects/{project_id}/dialogue-extraction/cancel
```

---

## ⚙️ Configuration avancée

### Modèles Whisper

| Modèle | RAM nécessaire | Vitesse | Précision | Recommandé pour |
|--------|---------------|---------|-----------|-----------------|
| `tiny` | 1GB | Très rapide | Bonne | Serveurs limités, tests |
| `base` | 2GB | Rapide | Très bonne | Usage général |
| `small` | 4GB | Moyen | Excellente | Haute qualité |

### Langues supportées

- **Français** : `fr`
- **Anglais** : `en`
- **Chinois** : `zh`
- **Japonais** : `ja`
- **Espagnol** : `es`
- **Allemand** : `de`
- **Italien** : `it`
- **Portugais** : `pt`
- **Russe** : `ru`
- **Coréen** : `ko`
- **Arabe** : `ar`
- **Hindi** : `hi`

### Diarization (détection des locuteurs)

**Activer/Désactiver** :
```bash
DIARIZATION_ENABLED=true  # Détection automatique des speakers
DIARIZATION_ENABLED=false # Un seul speaker pour tous les dialogues
```

**Nombre maximum de speakers** :
```bash
MAX_SPEAKERS=10  # Max 10 locuteurs différents
```

---

## 🎨 Création automatique des personnages

### Palette de couleurs

Les personnages sont créés automatiquement avec des couleurs distinctives :

| Speaker | Couleur fond | Couleur texte |
|---------|-------------|---------------|
| Speaker 1 | `#3b82f6` (Bleu) | `#ffffff` |
| Speaker 2 | `#ef4444` (Rouge) | `#ffffff` |
| Speaker 3 | `#10b981` (Vert) | `#ffffff` |
| Speaker 4 | `#f59e0b` (Orange) | `#ffffff` |
| Speaker 5 | `#8b5cf6` (Violet) | `#ffffff` |
| Speaker 6 | `#ec4899` (Rose) | `#ffffff` |
| Speaker 7 | `#14b8a6` (Teal) | `#ffffff` |
| Speaker 8 | `#f97316` (Orange vif) | `#ffffff` |
| Speaker 9 | `#6366f1` (Indigo) | `#ffffff` |
| Speaker 10+ | `#64748b` (Gris) | `#ffffff` |

### Distribution sur les lignes rythmo

Si le projet a **plusieurs lignes rythmo** (ex: 3 lignes) et **plusieurs speakers** (ex: 5 speakers) :
- Les speakers sont **distribués** sur les différentes lignes
- Exemple avec 3 lignes et 5 speakers :
  - Ligne 1 : Speaker 1, Speaker 4
  - Ligne 2 : Speaker 2, Speaker 5
  - Ligne 3 : Speaker 3

---

## 🚨 Limitations et contraintes

### Fonctionnement

- ⚠️ **Pas de timecodes existants** : L'extraction est désactivée si le projet a déjà des timecodes
- ⚠️ **Traitement asynchrone** : L'extraction peut prendre **plusieurs minutes** selon la durée de la vidéo
- ⚠️ **Mémoire** : Respecter les recommandations RAM pour chaque modèle Whisper
- ⚠️ **Qualité audio** : Les résultats dépendent de la qualité audio de la vidéo

### Performances

**Temps d'extraction estimé** (vidéo de 10 minutes) :

| Modèle | Temps | RAM |
|--------|-------|-----|
| `tiny` | 2-3 min | 1GB |
| `base` | 4-5 min | 2GB |
| `small` | 8-10 min | 4GB |

### Gestion des erreurs

En cas d'échec :
- ❌ **Rollback automatique** : Tous les timecodes/personnages créés sont supprimés
- ❌ **Fallback speaker unique** : Si la diarization échoue, un seul speaker est utilisé
- ❌ **Messages d'erreur clairs** : Affichés dans l'interface

---

## 🔍 Troubleshooting

### Problème : "FFmpeg not found"

**Solution** :
```bash
# macOS
brew install ffmpeg

# Ubuntu/Debian
sudo apt install ffmpeg

# CentOS/RHEL
sudo yum install ffmpeg
```

### Problème : "Out of memory"

**Solutions** :
1. Utiliser un modèle plus léger : `WHISPER_MODEL=tiny`
2. Réduire le nombre de speakers : `MAX_SPEAKERS=5`
3. Augmenter la RAM du serveur (minimum 2GB recommandé)

### Problème : "Extraction échoue immédiatement"

**Vérifications** :
1. Vérifier que Python 3.8+ est installé : `python3 --version`
2. Vérifier les dépendances : `pip list | grep whisper`
3. Vérifier les logs Laravel : `php artisan pail`
4. Vérifier le fichier vidéo existe : `storage/app/videos/`

### Problème : "Diarization ne fonctionne pas"

**Solutions** :
1. Vérifier `DIARIZATION_ENABLED=true` dans `.env`
2. Installer pyannote-audio : `pip install pyannote-audio==3.1.1`
3. Si problème persiste, désactiver : `DIARIZATION_ENABLED=false`

### Problème : "Mauvaise détection de langue"

**Solutions** :
1. Forcer la langue dans l'interface (ne pas utiliser "auto")
2. Vérifier la qualité audio de la vidéo
3. Utiliser un modèle plus gros : `WHISPER_MODEL=base` ou `small`

---

## 📊 Architecture technique

### Pipeline de traitement

```
1. Extraction audio (FFmpeg)
   ├─ Conversion vidéo → WAV
   ├─ 16kHz mono
   └─ Stockage temporaire

2. Transcription (Whisper)
   ├─ Chargement modèle (tiny/base/small)
   ├─ Détection automatique langue (ou forcée)
   ├─ Segmentation par phrases
   └─ Timecodes précis (start/end)

3. Diarization (pyannote-audio) [optionnel]
   ├─ Analyse des voix
   ├─ Identification des locuteurs
   └─ Attribution speaker par segment

4. Création timecodes + personnages
   ├─ Insertion en base de données
   ├─ Association timecode ↔ character
   ├─ Distribution sur lignes rythmo
   └─ Palette couleurs automatique
```

### Job Laravel

**Fichier** : `app/Jobs/ExtractDialogues.php`

**Fonctionnalités** :
- Appelle le script Python avec `proc_open()`
- Mise à jour de la progression en temps réel
- Vérification cancellation toutes les 2 secondes
- Rollback automatique en cas d'erreur
- Timeout de 30 minutes

### Script Python

**Fichier** : `scripts/extract_dialogues.py`

**Arguments** :
```bash
python3 extract_dialogues.py \
  --video "/path/to/video.mp4" \
  --language "fr" \
  --model "tiny" \
  --max-speakers 5 \
  --output "/path/to/output.json" \
  --project-id 123
```

**Output JSON** :
```json
{
  "timecodes": [
    {
      "start": 1.23,
      "end": 3.45,
      "text": "Bonjour, comment allez-vous ?",
      "speaker": "SPEAKER_00",
      "language": "fr"
    }
  ],
  "speakers": ["SPEAKER_00", "SPEAKER_01"],
  "metadata": {
    "duration": 120.5,
    "model": "tiny",
    "language": "fr"
  }
}
```

---

## 🎬 Workflow complet

### Exemple : Projet de doublage français

1. **Créer un projet** avec vidéo et 3 lignes rythmo
2. **Lancer l'extraction** :
   - Langue : Français
   - Max speakers : 5
   - Modèle : tiny
3. **Attendre** (progression en temps réel)
4. **Résultat** :
   - 50 timecodes créés automatiquement
   - 3 personnages détectés (Speaker 1, 2, 3)
   - Distribution :
     - Ligne 1 : Speaker 1
     - Ligne 2 : Speaker 2
     - Ligne 3 : Speaker 3
5. **Révision manuelle** :
   - Renommer "Speaker 1" → "Alice"
   - Renommer "Speaker 2" → "Bob"
   - Corriger textes si nécessaire
6. **Export** : Projet prêt pour le doublage !

---

## 🔐 Sécurité et confidentialité

### Traitement local

- ✅ **Aucune API externe** : Tout le traitement est fait localement
- ✅ **Données privées** : Les vidéos ne quittent jamais le serveur
- ✅ **Modèles open-source** : Whisper (OpenAI) et pyannote-audio

### Permissions

- 🔒 Nécessite **permission "edit"** ou "admin" sur le projet
- 🔒 Vérifie que le projet n'a **pas de timecodes existants**
- 🔒 Validation des paramètres côté backend (language, max_speakers, model)

---

## 🚀 Améliorations futures (Phase 2)

### Traduction automatique

- Transcription dans langue source (ex: anglais)
- Traduction vers langue cible (ex: français)
- Contexte basé sur scène/personnages
- UI pour activer/désactiver + sélection langue cible

### Validation post-extraction

- Interface de révision des timecodes
- Fusion de speakers mal identifiés
- Correction texte en masse
- Réassignation personnages

### Optimisations

- Support GPU (CUDA) pour accélérer Whisper
- Modèles Whisper quantifiés (plus légers)
- Cache des modèles entre extractions
- Parallélisation de la diarization

---

## 📝 Changelog

### v2.2.0-beta (31 octobre 2025)

- ✅ Extraction automatique dialogues
- ✅ Détection locuteurs (diarization)
- ✅ Support 12 langues
- ✅ Création auto timecodes + personnages
- ✅ UI complète avec progression temps réel
- ✅ Optimisé 2GB RAM

---

## 💡 Conseils et bonnes pratiques

### Pour une meilleure qualité

1. **Qualité audio** : Utiliser des vidéos avec audio clair
2. **Langue forcée** : Ne pas utiliser "auto" si possible
3. **Modèle adapté** : `tiny` pour tests, `base` pour production
4. **Speakers réaliste** : Ne pas surestimer le nombre de locuteurs
5. **Révision manuelle** : Toujours vérifier les résultats après extraction

### Optimisation des performances

1. **Queue workers** : Configurer plusieurs workers Laravel pour traiter en parallèle
2. **RAM** : Allouer au moins 2GB pour `base` model
3. **CPU** : Préférer multi-core pour diarization
4. **Stockage** : Prévoir espace pour fichiers audio temporaires

---

## 📞 Support

**Problème** ? Consultez :
1. Les logs Laravel : `php artisan pail`
2. Les logs Python : affichés dans l'interface de progression
3. La documentation Whisper : https://github.com/openai/whisper
4. La documentation pyannote : https://github.com/pyannote/pyannote-audio

**Besoin d'aide** ? Ouvrir une issue GitHub avec :
- Version PHP, Python, FFmpeg
- Configuration `.env` (sans secrets)
- Logs complets de l'erreur
- Caractéristiques de la vidéo (durée, format, codec)

---

**Dernière mise à jour** : 31 octobre 2025  
**Maintainer** : Martin P. (@ParizetM)
