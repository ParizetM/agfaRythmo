# AgfaRythmo 🎬

Application web professionnelle pour la génération de bandes rythmo (doublage vidéo).

**Version** : 2.1.0-beta | **Date** : 27 octobre 2025

---

## ✨ Fonctionnalités

### Core Features
- 📹 **Streaming vidéo optimisé** avec synchronisation précise
- 📝 **Timecodes multi-lignes** (1-10 lignes) avec texte éditable
- 🎭 **Personnages** avec couleurs personnalisables (fond + texte)
- 🎬 **Changements de plan** (scene changes) manuels ou détection **IA automatique**
- 📊 **Import SRT** pour timecodes
- 💾 **Import/Export projets** au format `.agfa` crypté

### Collaboration
- 👥 **Multi-utilisateurs** avec 3 niveaux de permissions (view/edit/admin)
- 📨 **Système d'invitations** pour partager des projets
- 🔄 **Synchronisation collaborative** en temps réel

### Administration
- 👤 **Gestion utilisateurs** (admin/user)
- 📁 **Gestion projets** avec statistiques
- 💽 **Monitoring espace disque** (taille vidéos)
- 🔧 **Mode maintenance** global

### UX/UI
- 🌙 **Dark mode** élégant avec glassmorphism
- 📱 **Responsive** mobile-first
- ⚡ **Optimisations GPU** pour mobile
- ⌨️ **Raccourcis clavier** intelligents
- 🎨 **Google Fonts** dynamiques avec cache

---

## 🏗️ Stack Technique

**Backend**
- Laravel 12.0 + PHP 8.2
- SQLite (production-ready)
- Laravel Sanctum 4.0 (auth JWT)
- Queue system (jobs background)
- FFmpeg (détection IA)

**Frontend**
- Vue 3.5 + TypeScript 5.8
- Pinia (state management)
- Vite 7 (build tool)
- Tailwind CSS 4
- Axios (HTTP client)

---

## 🚀 Installation

### Prérequis
- PHP 8.2+ avec extensions : pdo, sqlite, gd, mbstring
- Composer 2.x
- Node.js 18+
- FFmpeg (pour détection IA de plans)

### Installation locale

```bash
# 1. Cloner le repository
git clone https://github.com/ParizetM/agfaRythmo.git
cd agfaRythmo

# 2. Backend
cd agfa-rythmo-backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed

# 3. Frontend
cd ../agfa-rythmo-frontend
npm install
npm run build

# 4. Démarrer l'application
cd ../agfa-rythmo-backend
composer dev  # Lance serveur + queue + logs + vite
```

**Accès** : http://localhost:8000

**Compte admin par défaut** :
- Email : `admin@example.com`
- Password : `password`

---

## 🔧 Déploiement Production

### 📖 Guides complets

- **[Guide Workers de Queue](DEPLOYMENT_WORKERS.md)** - Configuration Supervisor/Systemd/Cron
- **[Guide Maintenance](MAINTENANCE_GUIDE.md)** - Activer/désactiver le mode maintenance
- **[Guide Détection IA](SCENE_DETECTION_IA.md)** - Configuration FFmpeg et analyse

### 🚀 Script de déploiement rapide

```bash
# Déploiement complet (backend + frontend)
./deploy.sh

# Déploiement backend seulement
./deploy.sh --skip-frontend
```

### ⚙️ Workers de queue (OBLIGATOIRE)

Pour que la détection IA fonctionne, **un worker de queue doit tourner en permanence**.

**Option recommandée : Supervisor**

```bash
# Installation
sudo apt-get install supervisor

# Configuration
sudo cp agfa-rythmo-backend/supervisor-agfaRythmo-worker.conf /etc/supervisor/conf.d/
sudo nano /etc/supervisor/conf.d/agfaRythmo-worker.conf  # Ajuster les chemins

# Activation
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start agfaRythmo-worker:*
```

Voir [DEPLOYMENT_WORKERS.md](DEPLOYMENT_WORKERS.md) pour les autres méthodes.

---

## 📚 Documentation

### Guides utilisateur
- [SCENE_DETECTION_GUIDE.md](SCENE_DETECTION_GUIDE.md) - Utiliser la détection de plans
- [TESTING_SCENE_DETECTION.md](TESTING_SCENE_DETECTION.md) - Tests et validation

### Guides techniques
- [DEPLOYMENT_WORKERS.md](DEPLOYMENT_WORKERS.md) - Workers de queue en production
- [MAINTENANCE_GUIDE.md](MAINTENANCE_GUIDE.md) - Mode maintenance
- [SCENE_DETECTION_IA.md](SCENE_DETECTION_IA.md) - Analyse IA avec FFmpeg
- [.github/instructions/instructions_projets.instructions.md](.github/instructions/instructions_projets.instructions.md) - Instructions GitHub Copilot

---

## 🗂️ Structure du Projet

```
agfaRythmo/
├── agfa-rythmo-backend/          # Laravel 12 API
│   ├── app/
│   │   ├── Http/Controllers/     # 7 controllers API
│   │   ├── Models/               # 8 modèles (User, Project, Timecode...)
│   │   ├── Jobs/                 # DetectSceneChanges (analyse IA)
│   │   └── Services/             # SrtParser
│   ├── routes/api.php            # 57 routes REST
│   └── database/migrations/      # 24 migrations
│
├── agfa-rythmo-frontend/         # Vue 3 + TypeScript
│   ├── src/
│   │   ├── views/                # 8 vues
│   │   ├── components/           # 27 composants
│   │   ├── api/                  # 11 services API
│   │   ├── stores/               # 2 stores Pinia
│   │   └── composables/          # 2 composables
│   └── dist/                     # Build production
│
├── deploy.sh                     # Script de déploiement
├── DEPLOYMENT_WORKERS.md         # Guide workers production
└── README.md                     # Ce fichier
```

---

## 🔑 API Routes (57 endpoints)

### Authentification
- `POST /api/auth/register` - Inscription
- `POST /api/auth/login` - Connexion
- `POST /api/auth/logout` - Déconnexion
- `GET /api/auth/profile` - Profil utilisateur
- `PUT /api/auth/change-password` - Changer mot de passe

### Projets
- `GET /api/projects` - Liste projets
- `POST /api/projects` - Créer projet
- `GET /api/projects/{id}` - Détails projet
- `PUT /api/projects/{id}` - Modifier projet
- `DELETE /api/projects/{id}` - Supprimer projet
- `GET /api/projects/{id}/export` - Exporter .agfa
- `POST /api/projects/import` - Importer .agfa

### Timecodes
- `GET /api/projects/{project}/timecodes` - Liste timecodes
- `POST /api/projects/{project}/timecodes` - Créer timecode
- `PUT /api/timecodes/{id}` - Modifier timecode
- `DELETE /api/timecodes/{id}` - Supprimer timecode
- `POST /api/projects/{project}/timecodes/import-srt` - Import SRT

### Scene Changes
- `GET /api/projects/{project}/scene-changes` - Liste changements
- `POST /api/projects/{project}/scene-changes` - Créer changement
- `PUT /api/scene-changes/{id}` - Modifier changement
- `DELETE /api/scene-changes/{id}` - Supprimer changement
- `DELETE /api/projects/{project}/scene-changes` - Supprimer tous

### Analyse IA
- `POST /api/projects/{project}/analyze-scenes` - Lancer analyse
- `GET /api/projects/{project}/analysis-status` - Statut analyse
- `POST /api/projects/{project}/cancel-analysis` - Annuler analyse

[Voir routes/api.php pour la liste complète]

---

## 🧪 Tests

```bash
# Backend
cd agfa-rythmo-backend
php artisan test

# Frontend
cd agfa-rythmo-frontend
npm run test
```

---

## 🐛 Dépannage

### Le worker de queue ne démarre pas

```bash
# Vérifier les permissions
sudo chown -R www-data:www-data storage/
sudo chmod -R 775 storage/

# Tester manuellement
php artisan queue:work --once --verbose
```

### FFmpeg introuvable

```bash
# Ubuntu/Debian
sudo apt-get install ffmpeg

# Vérifier l'installation
which ffmpeg
ffmpeg -version
```

### Analyse IA bloquée

```bash
# Vérifier les jobs
php artisan tinker
>>> DB::table('jobs')->count()

# Réinitialiser les jobs
php artisan queue:flush

# Redémarrer les workers
sudo supervisorctl restart agfaRythmo-worker:*
```

Voir [DEPLOYMENT_WORKERS.md](DEPLOYMENT_WORKERS.md#-dépannage) pour plus de solutions.

---

## 📝 Changelog

### v2.1.0-beta (27 octobre 2025)
- ✅ Détection IA automatique des changements de plan (FFmpeg)
- ✅ Import/Export projets format `.agfa` crypté
- ✅ Mode maintenance global
- ✅ Statistiques espace vidéos (admin)
- ✅ Modales de confirmation modernes
- ✅ Optimisations mobile (GPU, playsinline)
- ✅ Menu navigation responsive
- ✅ Google Fonts dynamiques avec cache
- ✅ Bouton "Supprimer tous les scene changes"

### v2.0.0-beta (octobre 2025)
- ✅ Architecture Laravel 12 + Vue 3 + TypeScript
- ✅ Authentification Sanctum (JWT)
- ✅ Timecodes multi-lignes (1-10)
- ✅ Personnages avec couleurs
- ✅ Scene changes manuels
- ✅ Import fichiers SRT
- ✅ Collaboration multi-utilisateurs
- ✅ Panel administration
- ✅ Interface responsive dark mode

---

## 👥 Contribution

Les contributions sont les bienvenues !

1. Fork le projet
2. Créer une branche (`git checkout -b feature/AmazingFeature`)
3. Commit les changements (`git commit -m 'Add AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

**Guidelines** :
- Respecter les conventions de code (Laravel PSR-12, Vue 3 Composition API)
- Ajouter des tests si applicable
- Mettre à jour la documentation

---

## 📄 Licence

Ce projet est sous licence propriétaire. Tous droits réservés.

---

## 👨‍💻 Auteur & Support

**Maintainer** : Martin P. ([@ParizetM](https://github.com/ParizetM))

**Support** :
- 📧 Email : support@agfarythmo.com
- 🐛 Issues : [GitHub Issues](https://github.com/ParizetM/agfaRythmo/issues)
- 📖 Documentation : [Wiki](https://github.com/ParizetM/agfaRythmo/wiki)

---

## 🙏 Remerciements

- Laravel Framework
- Vue.js Team
- FFmpeg Project
- Tailwind CSS
- Communauté open-source

---

<p align="center">
  Fait avec ❤️ par Martin P.
</p>
