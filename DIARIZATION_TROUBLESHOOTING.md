# 🔍 Diarization - Guide de Dépannage

## Problème : Un seul locuteur détecté au lieu de plusieurs

### ✅ Vérifications à effectuer

#### 1. **Vérifier que la diarization est activée**
```bash
cd agfa-rythmo-backend
grep "AI_DIARIZATION_ENABLED" .env
```
✅ Doit afficher : `AI_DIARIZATION_ENABLED=true`

#### 2. **Vérifier le token HuggingFace**
```bash
grep "HF_TOKEN" .env
```
✅ Doit afficher : `HF_TOKEN=hf_xxxxxxxxxxxxx` (votre token)

❌ Si vide ou absent : C'est le problème !

#### 3. **Installer pyannote.audio**
```bash
cd agfa-rythmo-backend
pip install pyannote.audio>=3.1.0
```

#### 4. **Vérifier les logs du worker**
Les logs du worker Laravel afficheront maintenant les messages détaillés :

```bash
# Terminal où tourne le worker
php artisan queue:work
```

Cherchez dans les logs :
- ✅ `👥 DIARIZATION ACTIVÉE - Détection des locuteurs`
- ✅ `✅ SUCCÈS: X locuteur(s) détecté(s)`
- ❌ `⚠️ DIARIZATION DÉSACTIVÉE`
- ❌ `❌ ERREUR: Token HuggingFace manquant ou invalide`

---

## 🔧 Configuration du Token HuggingFace (2 minutes)

### Étape 1 : Créer un compte (gratuit)
👉 https://huggingface.co

### Étape 2 : Générer un token
1. Aller sur : https://huggingface.co/settings/tokens
2. Cliquer sur "New token"
3. Type : "Read" (suffisant)
4. Copier le token : `hf_xxxxxxxxxxxxx`

### Étape 3 : Accepter les conditions du modèle
**IMPORTANT** : Sans cette étape, le token ne fonctionnera pas !

1. Aller sur : https://huggingface.co/pyannote/speaker-diarization-3.1
2. Cliquer sur "Agree and access repository"
3. Accepter les conditions d'utilisation

### Étape 4 : Ajouter le token dans .env
```bash
cd agfa-rythmo-backend
nano .env  # ou vim, ou votre éditeur préféré
```

Ajouter/modifier la ligne :
```bash
HF_TOKEN=hf_xxxxxxxxxxxxx
```

### Étape 5 : Redémarrer le worker
```bash
# Ctrl+C pour arrêter le worker actuel
php artisan queue:work --queue=default --sleep=3 --tries=3 --timeout=60
```

---

## 📊 Messages de diagnostic dans les logs

### ✅ **Succès complet**
```
╔════════════════════════════════════════════════════════════════╗
║  👥 DIARIZATION ACTIVÉE - Détection des locuteurs            ║
╚════════════════════════════════════════════════════════════════╝

📥 Chargement du modèle de diarization...
✅ Modèle de diarization chargé
🔍 Analyse des locuteurs (max 10)...

╔════════════════════════════════════════════════════════════════╗
║  ✅ SUCCÈS: 4 locuteur(s) détecté(s)                           ║
╠════════════════════════════════════════════════════════════════╣
║  4 personnage(s) seront créés automatiquement              ║
║  Vous pourrez les renommer ou fusionner après l'extraction     ║
╚════════════════════════════════════════════════════════════════╝
```

### ❌ **Erreur : Token manquant/invalide**
```
╔════════════════════════════════════════════════════════════════╗
║  ❌ ERREUR: Token HuggingFace manquant ou invalide           ║
╠════════════════════════════════════════════════════════════════╣
║  La diarization (détection locuteurs) nécessite un token HF   ║
║                                                                ║
║  📝 SOLUTION (2 minutes) :                                    ║
║  1. Créer compte gratuit: https://huggingface.co              ║
║  2. Générer token: https://huggingface.co/settings/tokens     ║
║  3. Accepter conditions du modèle:                            ║
║     https://huggingface.co/pyannote/speaker-diarization-3.1   ║
║  4. Ajouter dans .env : HF_TOKEN=hf_xxxxx                     ║
║  5. Relancer l'extraction                                     ║
║                                                                ║
║  ⚠️  Sans token : UN SEUL locuteur sera créé                 ║
╚════════════════════════════════════════════════════════════════╝

⚠️  Fallback: Attribution de tous les dialogues à SPEAKER_00
```

### ⚠️ **Diarization désactivée**
```
╔════════════════════════════════════════════════════════════════╗
║  ⚠️  DIARIZATION DÉSACTIVÉE                                   ║
╠════════════════════════════════════════════════════════════════╣
║  AI_DIARIZATION_ENABLED=false dans le fichier .env            ║
║                                                                ║
║  Résultat : UN SEUL locuteur (SPEAKER_00)                      ║
║                                                                ║
║  Pour activer la détection multi-locuteurs :                  ║
║  1. Mettre AI_DIARIZATION_ENABLED=true dans .env              ║
║  2. Configurer HF_TOKEN (voir .env.example)                   ║
║  3. Installer: pip install pyannote.audio                     ║
╚════════════════════════════════════════════════════════════════╝
```

---

## 🎯 Test rapide

1. **Lancer une extraction** avec une vidéo contenant plusieurs personnes
2. **Surveiller les logs du worker** en temps réel
3. **Vérifier le résultat** :
   - Si **1 seul personnage** créé → Problème de diarization
   - Si **plusieurs personnages** créés → Succès ! 🎉

---

## 💡 Astuces

- **Qualité audio** : Une bonne qualité audio améliore la détection
- **Voix distinctes** : Fonctionne mieux avec des voix différentes (homme/femme, accents)
- **Nombre de speakers** : Configurable via `AI_MAX_SPEAKERS` (défaut : 10)
- **Fusion manuelle** : Si trop de speakers détectés, utilisez le modal de fusion

---

## 📝 Checklist complète

- [ ] `AI_DIARIZATION_ENABLED=true` dans .env
- [ ] `HF_TOKEN=hf_xxxxx` configuré dans .env
- [ ] Compte HuggingFace créé
- [ ] Conditions du modèle acceptées sur HuggingFace
- [ ] `pip install pyannote.audio` exécuté
- [ ] Worker Laravel redémarré
- [ ] Logs du worker surveillés pendant l'extraction
- [ ] Test avec vidéo multi-locuteurs

---

**Date de dernière mise à jour** : 31 octobre 2025
