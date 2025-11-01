# AgfaRythmo Backend

Backend API Laravel pour l'application de génération de bandes rythmo.

## 📋 Prérequis

- PHP 8.2+
- Composer
- SQLite
- Python 3.8+ (pour fonctionnalités IA)
- FFmpeg (pour traitement vidéo)

## 🚀 Installation

### 1. Dépendances PHP

```bash
composer install
```

### 2. Configuration

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Base de données

```bash
php artisan migrate
php artisan db:seed  # Optionnel: données de test
```

### 4. Dépendances Python (Fonctionnalités IA)

**⚠️ Serveur 4GB+ RAM recommandé pour IA complète**

```bash
# Installation des dépendances Python
pip install -r scripts/requirements.txt

# Vérification
python scripts/extract_dialogues.py --help
```

**Détail des dépendances** :
- **Whisper** (1-2GB) : Transcription audio
- **Demucs** (1-2GB) : Séparation vocale (améliore qualité)
- **Resemblyzer** (500MB) : Diarization locuteurs
- **NLLB-200** (~2GB) : Traduction 200 langues

## ⚙️ Configuration IA (.env)

```bash
# === Fonctionnalités IA ===
AI_SCENE_DETECTION_ENABLED=true
AI_DIALOGUE_EXTRACTION_ENABLED=true
AI_TRANSLATION_ENABLED=true

# === Extraction dialogues ===
AI_WHISPER_MODEL=tiny                    # tiny/base/small
AI_DIARIZATION_ENABLED=true
AI_DIARIZATION_METHOD=resemblyzer        # resemblyzer (⭐⭐⭐⭐⭐) ou mfcc (⭐⭐)
AI_VOCAL_SEPARATION_ENABLED=true        # Demucs (⚠️ 4GB+ RAM)
AI_MAX_SPEAKERS=10

# === Traduction ===
AI_TRANSLATION_PROVIDER=nllb
AI_NLLB_MODEL_SIZE=600M
HF_TOKEN=your_huggingface_token         # Gratuit: https://huggingface.co/settings/tokens
```

### Configuration serveurs low-RAM (2GB)

```bash
AI_WHISPER_MODEL=tiny
AI_DIARIZATION_METHOD=mfcc              # Plus léger que Resemblyzer
AI_VOCAL_SEPARATION_ENABLED=false       # Désactiver Demucs
```

## 🏃 Lancement

### Développement

```bash
# Terminal 1: API + Frontend
composer dev

# Terminal 2: Worker (pour extraction dialogues/traduction)
php artisan queue:work --memory=4096 --timeout=1800
```

**Note Worker** :
- `--memory=4096` : Limite RAM (4GB, augmenter si serveur puissant)
- `--timeout=1800` : Timeout 30min (extraction longue)

### Production

```bash
# API
php artisan serve --host=0.0.0.0 --port=8000

# Worker (avec supervisor ou systemd)
php artisan queue:work --memory=4096 --timeout=1800 --tries=3
```

## 📁 Structure

```
agfa-rythmo-backend/
├── app/
│   ├── Http/Controllers/Api/     # Controllers API REST
│   ├── Jobs/                     # Jobs asynchrones (extraction, traduction)
│   ├── Models/                   # Modèles Eloquent
│   └── Services/                 # Services (ServerCapabilities)
├── config/
│   └── ai.php                    # Configuration IA
├── database/
│   └── migrations/               # Migrations DB
├── routes/
│   └── api.php                   # Routes API (56 endpoints)
├── scripts/                      # Scripts Python IA
│   ├── extract_dialogues.py      # Extraction + Whisper
│   ├── separate_vocals.py        # Demucs séparation vocale
│   ├── resemblyzer_diarization.py # Diarization Resemblyzer
│   ├── simple_diarization.py     # Diarization MFCC (fallback)
│   ├── translate_nllb.py         # Traduction NLLB-200
│   └── requirements.txt          # Dépendances Python
└── storage/
    ├── app/videos/               # Vidéos uploadées
    └── logs/                     # Logs Laravel
```

## 🔧 Jobs Asynchrones

### ExtractDialogues (6 étapes)

1. Extraction audio (FFmpeg)
2. Séparation vocale (Demucs) - optionnel
3. Transcription (Whisper)
4. Diarization (Resemblyzer/MFCC)
5. Attribution personnages
6. Création timecodes

**RAM requise** : 2-4GB selon config

### TranslateTimecodes

Traduction batch avec NLLB-200 (200 langues).

**RAM requise** : ~2GB

## 🐛 Troubleshooting

### Worker killed (exit 137)

❌ **Problème** : OOM (Out Of Memory)

✅ **Solutions** :
1. Augmenter limite worker : `--memory=8192`
2. Désactiver Demucs : `AI_VOCAL_SEPARATION_ENABLED=false`
3. Modèle Whisper plus léger : `AI_WHISPER_MODEL=tiny`
4. Méthode MFCC : `AI_DIARIZATION_METHOD=mfcc`

### Extraction timeout

❌ **Problème** : Worker timeout

✅ **Solution** :
```bash
php artisan queue:work --timeout=3600  # 1h
```

### Python ModuleNotFoundError

❌ **Problème** : Dépendances manquantes

✅ **Solution** :
```bash
pip install -r scripts/requirements.txt
```

### Numpy incompatible

❌ **Problème** : `numpy 2.x` incompatible avec Resemblyzer

✅ **Solution** :
```bash
pip install numpy==1.26.4
```

## 📊 Endpoints API

**56 routes REST** :
- Auth : `/api/auth/*`
- Projects : `/api/projects/*`
- Timecodes : `/api/projects/{project}/timecodes/*`
- Characters : `/api/characters/*`
- Scene Changes : `/api/scene-changes/*`
- Collaboration : `/api/projects/{project}/collaborators/*`
- Invitations : `/api/invitations/*`
- Translation : `/api/translation/*`
- Admin : `/api/admin/*`

Voir `routes/api.php` pour détails.

## 📝 License

MIT License - Voir LICENSE

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
