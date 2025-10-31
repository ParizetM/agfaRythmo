# 📚 Documentation AgfaRythmo

Documentation technique complète pour l'application AgfaRythmo.

## 🚀 Guides de démarrage rapide

### Pour les utilisateurs

- **[Guide Principal](../README.md)** - Vue d'ensemble et installation de base
- **[Guide Maintenance](MAINTENANCE_GUIDE.md)** - Activer/désactiver le mode maintenance

### Pour les développeurs

- **[Configuration IA](AI_CONFIG.md)** - Configuration générale des fonctionnalités IA
- **[Déploiement Workers](DEPLOYMENT_WORKERS.md)** - Configuration des workers Laravel Queue
- **[Capacités Serveur](SERVER_CAPABILITIES.md)** - Auto-détection des fonctionnalités disponibles

## 🤖 Fonctionnalités IA

### Détection de changements de plan

- **[Guide Détection Scène](SCENE_DETECTION_GUIDE.md)** - Guide complet d'utilisation
- **[Architecture IA Détection](SCENE_DETECTION_IA.md)** - Détails techniques de l'algorithme
- **[Tests Détection](TESTING_SCENE_DETECTION.md)** - Procédures de test

### Extraction automatique de dialogues

- **[Guide Extraction Dialogues](DIALOGUE_EXTRACTION_GUIDE.md)** - Whisper + Diarization
  - Installation Python/Whisper
  - Configuration modèles (tiny/base/small)
  - Support 12 langues
  - Auto-création personnages
  - Diarization (séparation locuteurs)

### Traduction automatique

- **[Guide Traduction](TRANSLATION_GUIDE.md)** - Guide complet de traduction
  - 4 providers (DeepL ⭐⭐⭐⭐⭐, Google, MyMemory, LibreTranslate)
  - Configuration .env
  - Support 30+ langues
  - Contexte personnages
  
- **[Obtenir Clés API](TRANSLATION_API_KEYS.md)** - Guide rapide pour obtenir les clés
  - DeepL (gratuit 500k/mois)
  - Google Translate ($300 crédits)
  - MyMemory (aucune config)
  - LibreTranslate (API publique)

## 📖 Structure de la documentation

```
docs/
├── README.md (ce fichier)
│
├── Configuration & Déploiement
│   ├── AI_CONFIG.md
│   ├── DEPLOYMENT_WORKERS.md
│   ├── MAINTENANCE_GUIDE.md
│   └── SERVER_CAPABILITIES.md
│
└── Fonctionnalités IA
    ├── Détection Scène
    │   ├── SCENE_DETECTION_GUIDE.md
    │   ├── SCENE_DETECTION_IA.md
    │   └── TESTING_SCENE_DETECTION.md
    │
    ├── Extraction Dialogues
    │   └── DIALOGUE_EXTRACTION_GUIDE.md
    │
    └── Traduction
        ├── TRANSLATION_GUIDE.md
        └── TRANSLATION_API_KEYS.md
```

## 🔍 Index par sujet

### Installation & Configuration

| Guide | Description |
|-------|-------------|
| [AI_CONFIG.md](AI_CONFIG.md) | Configuration fonctionnalités IA via .env |
| [DEPLOYMENT_WORKERS.md](DEPLOYMENT_WORKERS.md) | Configuration workers Laravel Queue |
| [SERVER_CAPABILITIES.md](SERVER_CAPABILITIES.md) | Auto-détection FFmpeg/Python/etc. |

### Maintenance & Administration

| Guide | Description |
|-------|-------------|
| [MAINTENANCE_GUIDE.md](MAINTENANCE_GUIDE.md) | Activer mode maintenance (simple rename fichier) |

### Fonctionnalités IA - Détection Scène

| Guide | Description |
|-------|-------------|
| [SCENE_DETECTION_GUIDE.md](SCENE_DETECTION_GUIDE.md) | Guide utilisateur complet |
| [SCENE_DETECTION_IA.md](SCENE_DETECTION_IA.md) | Architecture technique (FFmpeg + algorithme) |
| [TESTING_SCENE_DETECTION.md](TESTING_SCENE_DETECTION.md) | Tests et validation |

### Fonctionnalités IA - Extraction Dialogues

| Guide | Description |
|-------|-------------|
| [DIALOGUE_EXTRACTION_GUIDE.md](DIALOGUE_EXTRACTION_GUIDE.md) | Whisper + Diarization (installation, config, usage) |

### Fonctionnalités IA - Traduction

| Guide | Description |
|-------|-------------|
| [TRANSLATION_GUIDE.md](TRANSLATION_GUIDE.md) | Guide complet (4 providers, config, troubleshooting) |
| [TRANSLATION_API_KEYS.md](TRANSLATION_API_KEYS.md) | Obtenir clés DeepL/Google/MyMemory/LibreTranslate |

## 🆘 Aide rapide

### Problème : Feature IA non disponible

1. Vérifier `.env` : `AI_*_ENABLED=true`
2. Recharger config : `php artisan config:cache`
3. Consulter [AI_CONFIG.md](AI_CONFIG.md)

### Problème : Workers non actifs

1. Vérifier `QUEUE_CONNECTION=database` dans `.env`
2. Lancer workers : `php artisan queue:work`
3. Consulter [DEPLOYMENT_WORKERS.md](DEPLOYMENT_WORKERS.md)

### Problème : Traduction échoue

1. Vérifier provider dans `.env`
2. Pour DeepL/Google : vérifier clé API
3. Consulter [TRANSLATION_GUIDE.md](TRANSLATION_GUIDE.md#troubleshooting)

### Problème : Extraction dialogues échoue

1. Vérifier Python + Whisper installés
2. Vérifier FFmpeg installé
3. Consulter [DIALOGUE_EXTRACTION_GUIDE.md](DIALOGUE_EXTRACTION_GUIDE.md#troubleshooting)

## 📝 Contribuer à la documentation

- Les guides sont en Markdown
- Mettre à jour ce README.md lors d'ajout de nouveaux guides
- Suivre la structure existante
- Inclure toujours : installation, configuration, usage, troubleshooting

---

**Dernière mise à jour** : 31 octobre 2025
**Version** : 2.2.0-beta
