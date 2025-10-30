# AgfaRythmo 🎬

Application web professionnelle pour la génération de bandes rythmo destinées au doublage vidéo. Solution complète permettant la synchronisation précise de textes sur des timecodes vidéo, avec collaboration multi-utilisateurs et analyse IA automatique.

**Version** : 2.1.0-beta | **Mise à jour** : 29 octobre 2025

---

## 📖 Table des matières

- [Présentation](#-présentation)
- [Fonctionnalités](#-fonctionnalités)
- [Stack technique](#️-stack-technique)
- [Architecture](#️-architecture)
- [Installation](#-installation)
  - [Développement local](#développement-local)
  - [Déploiement production](#déploiement-production-vm-vierge)
- [Configuration](#️-configuration)
- [Utilisation](#-utilisation)
- [API](#-api-rest)
- [Troubleshooting](#-dépannage)
- [Documentation](#-documentation)
- [Contribution](#-contribution)

---

## 🎯 Présentation

**AgfaRythmo** est une application web moderne permettant de créer des bandes rythmo (ou "bandes rythmiques") utilisées dans le doublage vidéo professionnel. Elle synchronise des textes avec des timecodes vidéo sur plusieurs lignes, affiche les personnages avec des codes couleur, et détecte automatiquement les changements de plan grâce à l'intelligence artificielle.

### Cas d'usage

- 🎬 **Studios de doublage** : création de scripts synchronisés pour comédiens
- 📺 **Production audiovisuelle** : préparation de sous-titres avancés
- 🎓 **Formation** : apprentissage du doublage et de la synchronisation
- 🌍 **Traduction vidéo** : adaptation multilingue avec timing précis

### Points forts

- ✅ **Aucune dépendance cloud** : fonctionne entièrement on-premise
- ✅ **Collaborative** : plusieurs utilisateurs peuvent travailler sur un même projet
- ✅ **IA intégrée** : détection automatique des changements de plan (FFmpeg)
- ✅ **Format propriétaire** : export/import de projets complets en `.agfa` crypté
- ✅ **Production-ready** : optimisé pour serveurs mutualisés et VPS

---

## ✨ Fonctionnalités

### 🎥 Gestion vidéo

- **Upload et streaming** : téléversement de vidéos (MP4, WebM, AVI) avec lecture optimisée
- **Synchronisation précise** : player vidéo avec contrôle frame-par-frame
- **Preview finale** : vue d'ensemble avec bandes rythmo et scene changes
- **Support mobile** : lecture inline sur iOS/Android avec optimisations GPU

### 📝 Timecodes multi-lignes

- **1 à 10 lignes** configurables par projet
- **Édition en temps réel** : création, modification, suppression
- **Import SRT** : importation de fichiers sous-titres SRT
- **Séparateurs personnalisables** : découpage du texte en segments
- **Association personnages** : affichage optionnel du nom/couleur du personnage

### 🎭 Personnages

- **Couleurs personnalisées** : fond et texte (hex color picker)
- **Clonage entre projets** : réutilisation de personnages existants
- **Affichage conditionnel** : show/hide par timecode
- **Gestion centralisée** : CRUD complet avec interface dédiée

### 🎬 Scene Changes (Changements de plan)

- **Détection IA automatique** : analyse vidéo avec FFmpeg (histogrammes, seuils configurables)
- **Ajout manuel** : création de marqueurs manuels
- **Visualisation timeline** : indicateurs sur la bande rythmo
- **Navigation rapide** : saut au plan suivant/précédent
- **Export/Import** : inclus dans les fichiers `.agfa`

### 👥 Collaboration

- **3 niveaux de permissions** :
  - `view` : lecture seule
  - `edit` : modification du contenu
  - `admin` : gestion des collaborateurs + settings
- **Invitations par email** : envoi, acceptation, refus
- **Synchronisation temps réel** : refresh collaboratif automatique
- **Recherche utilisateurs** : ajout rapide de collaborateurs
- **Quitter un projet** : auto-suppression des collaborateurs

### 💾 Import/Export

- **Format `.agfa` propriétaire** : fichier JSON crypté contenant :
  - Métadonnées du projet
  - Tous les timecodes
  - Personnages avec couleurs
  - Scene changes
  - Settings complets
- **2 modes d'import** :
  - Avec vidéo (fichier .agfa + upload vidéo)
  - Sans vidéo (template/test)
- **Partage simplifié** : transfert de projets entre utilisateurs/instances

### 🎨 Presets de paramètres

- **Sauvegarde de configurations** : jusqu'à 5 presets par utilisateur
- **Réutilisation rapide** : application en 1 clic
- **Settings inclus** :
  - Nombre de lignes rythmo
  - Police, taille, couleurs
  - Espacement, hauteurs
  - Options d'affichage

### 🛡️ Administration

- **Gestion utilisateurs** : CRUD, changement de rôle (admin/user)
- **Gestion projets** : vue globale, suppression cascade
- **Statistiques** :
  - Nombre total d'utilisateurs/projets/timecodes
  - Espace disque utilisé par les vidéos (MB/GB)
- **Mode maintenance** : activation/désactivation instantanée

### 🎨 Interface & UX

- **Dark mode** : thème sombre élégant avec glassmorphism
- **Responsive design** : mobile-first, optimisé tablette/desktop
- **Menu hamburger** : navigation mobile fluide
- **Modales modernes** : `BaseModal` réutilisable, confirmations élégantes
- **Notifications toast** : feedback visuel pour toutes les actions
- **Raccourcis clavier** : navigation rapide (désactivés en édition)
- **Google Fonts dynamiques** : chargement asynchrone avec cache localStorage

---

## 🏗️ Stack technique

### Backend

| Technologie | Version | Rôle |
|------------|---------|------|
| **PHP** | 8.2+ | Langage serveur |
| **Laravel** | 12.0 | Framework web/API |
| **SQLite** | 3.x | Base de données (production-ready) |
| **Laravel Sanctum** | 4.0 | Authentification JWT/tokens |
| **Queue System** | Laravel Queue | Jobs asynchrones (analyse IA) |
| **FFmpeg** | 4.x+ | Analyse vidéo IA (optionnel) |

**Extensions PHP requises** : `pdo`, `sqlite3`, `gd`, `mbstring`, `fileinfo`, `json`, `openssl`

### Frontend

| Technologie | Version | Rôle |
|------------|---------|------|
| **Vue.js** | 3.5 | Framework JavaScript |
| **TypeScript** | 5.8 | Typage statique |
| **Vite** | 7.0 | Build tool + dev server |
| **Pinia** | 2.x | State management |
| **Vue Router** | 4.x | Routing SPA |
| **Axios** | 1.x | Client HTTP |
| **Tailwind CSS** | 4.0 | Framework CSS utility-first |
| **Heroicons** | 2.x | Icônes SVG |

### Infrastructure

- **Serveur web** : Apache 2.4+ ou Nginx 1.18+
- **Process manager** : Supervisor (recommandé) ou Systemd
- **Stockage** : Filesystem local (vidéos + SQLite)

---

## 🏛️ Architecture

### Structure globale

```
agfaRythmo/
├── agfa-rythmo-backend/          # API Laravel
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/      # 7 controllers API
│   │   │   │   ├── Api/
│   │   │   │   │   ├── AdminController.php
│   │   │   │   │   ├── AuthController.php
│   │   │   │   │   ├── CharacterController.php
│   │   │   │   │   ├── CollaborationController.php
│   │   │   │   │   ├── InvitationController.php
│   │   │   │   │   ├── ProjectController.php
│   │   │   │   │   ├── SceneAnalysisController.php
│   │   │   │   │   ├── SceneChangeController.php
│   │   │   │   │   ├── SettingsPresetController.php
│   │   │   │   │   └── TimecodeController.php
│   │   │   └── Middleware/       # CheckForMaintenanceMode
│   │   ├── Models/               # 8 modèles Eloquent
│   │   │   ├── User.php
│   │   │   ├── Project.php
│   │   │   ├── Timecode.php
│   │   │   ├── Character.php
│   │   │   ├── SceneChange.php
│   │   │   ├── ProjectCollaborator.php
│   │   │   ├── ProjectInvitation.php
│   │   │   └── SettingsPreset.php
│   │   ├── Jobs/
│   │   │   └── DetectSceneChanges.php  # Analyse IA FFmpeg
│   │   └── Services/
│   │       ├── SrtParser.php     # Parser fichiers SRT
│   │       └── ServerCapabilities.php  # Détection FFmpeg/workers
│   ├── config/
│   │   ├── ai.php                # Config fonctionnalités IA
│   │   ├── queue.php             # Config workers
│   │   └── ...
│   ├── database/
│   │   ├── migrations/           # 24 migrations
│   │   └── seeders/              # AdminUserSeeder
│   ├── routes/
│   │   └── api.php               # 57 routes REST
│   ├── storage/
│   │   ├── app/videos/           # Fichiers vidéo
│   │   ├── framework/
│   │   │   └── maintenance       # Fichier mode maintenance
│   │   └── logs/
│   └── public/
│       └── index.php             # Entry point
│
├── agfa-rythmo-frontend/         # SPA Vue 3
│   ├── src/
│   │   ├── views/                # 8 vues principales
│   │   │   ├── LoginView.vue
│   │   │   ├── RegisterView.vue
│   │   │   ├── ProjectsView.vue
│   │   │   ├── ProjectDetailView.vue
│   │   │   ├── FinalPreviewView.vue
│   │   │   ├── ProfileView.vue
│   │   │   ├── AdminView.vue
│   │   │   └── MaintenanceView.vue
│   │   ├── components/           # 27+ composants
│   │   │   ├── BaseModal.vue     # Modal réutilisable
│   │   │   ├── ConfirmModal.vue  # Confirmations modernes
│   │   │   ├── AiMenuModal.vue   # Menu IA unifié
│   │   │   ├── VideoPlayer.vue   # Player vidéo
│   │   │   ├── RythmoBandSingle.vue
│   │   │   ├── MultiRythmoBand.vue
│   │   │   └── projectDetail/    # 17 sous-composants
│   │   ├── api/                  # 11 services API
│   │   │   ├── axios.ts          # Config + intercepteurs
│   │   │   ├── auth.ts
│   │   │   ├── projects.ts
│   │   │   ├── timecodes.ts
│   │   │   ├── characters.ts
│   │   │   ├── sceneChanges.ts
│   │   │   ├── sceneAnalysis.ts
│   │   │   ├── collaboration.ts
│   │   │   ├── invitations.ts
│   │   │   ├── settingsPresets.ts
│   │   │   ├── serverCapabilities.ts
│   │   │   └── admin.ts
│   │   ├── stores/               # 2 stores Pinia
│   │   │   ├── auth.ts           # Authentification
│   │   │   └── projectSettings.ts
│   │   ├── composables/          # 3 composables
│   │   │   ├── useInvitations.ts
│   │   │   ├── useCollaborativeRefresh.ts
│   │   │   └── useServerCapabilities.ts
│   │   ├── services/
│   │   │   ├── googleFonts.ts    # Chargement fonts Google
│   │   │   └── notifications.ts  # Toast notifications
│   │   ├── utils/
│   │   │   ├── colorUtils.ts
│   │   │   └── separatorEncoding.ts
│   │   └── router/
│   │       └── index.ts          # Routes + guards
│   └── dist/                     # Build production
│
├── deploy.sh                     # Script déploiement
├── DEPLOYMENT_WORKERS.md         # Guide workers
├── MAINTENANCE_GUIDE.md          # Guide maintenance
├── SCENE_DETECTION_IA.md         # Guide analyse IA
└── README.md                     # Ce fichier
```

### Modèles de données

#### User
```php
- id, name, email, password, role (admin|user)
- Relations: projects(), collaborativeProjects(), sentInvitations(), receivedInvitations()
```

#### Project
```php
- id, name, description, video_path, rythmo_lines_count (1-10), project_settings (JSON), user_id
- Relations: owner(), collaborators(pivot: permission), timecodes(), characters(), sceneChanges()
- Méthodes: hasAccess($user), canModify($user), canAdmin($user)
```

#### Timecode
```php
- id, project_id, line_number, start, end, text, character_id, show_character, separator_positions
- Relations: project(), character()
```

#### Character
```php
- id, project_id, name, color (hex), text_color (hex)
```

#### SceneChange
```php
- id, project_id, timecode (float secondes), source (manual|auto)
```

### API REST (57 routes)

#### Authentification (5)
- `POST /api/auth/register`
- `POST /api/auth/login`
- `POST /api/auth/logout`
- `GET /api/auth/profile`
- `PUT /api/auth/change-password`

#### Projets (12)
- `GET /api/projects` - Liste projets accessibles
- `POST /api/projects` - Créer projet
- `GET /api/projects/{id}` - Détails projet
- `PUT /api/projects/{id}` - Modifier projet
- `DELETE /api/projects/{id}` - Supprimer projet
- `PUT /api/projects/{id}/settings` - Modifier settings
- `PUT /api/projects/{id}/rythmo-lines` - Changer nb lignes
- `GET /api/projects/{id}/export` - Export .agfa
- `POST /api/projects/import` - Import .agfa
- `POST /api/projects/upload-video` - Upload vidéo
- `GET /api/videos/{filename}` - Stream vidéo (public)

#### Timecodes (6)
- `GET /api/projects/{project}/timecodes`
- `POST /api/projects/{project}/timecodes`
- `GET /api/projects/{project}/timecodes/by-line/{line}`
- `PUT /api/timecodes/{id}`
- `DELETE /api/timecodes/{id}`
- `POST /api/projects/{project}/timecodes/import-srt`

#### Personnages (5)
- `GET /api/projects/{project}/characters`
- `POST /api/characters`
- `PUT /api/characters/{id}`
- `DELETE /api/characters/{id}`
- `GET /api/characters/for-cloning` - Liste pour clonage

#### Scene Changes (5)
- `GET /api/projects/{project}/scene-changes`
- `POST /api/projects/{project}/scene-changes`
- `PUT /api/scene-changes/{id}`
- `DELETE /api/scene-changes/{id}`
- `DELETE /api/projects/{project}/scene-changes` - Supprimer tous

#### Analyse IA (3)
- `POST /api/projects/{project}/analyze-scenes`
- `GET /api/projects/{project}/analysis-status`
- `POST /api/projects/{project}/cancel-analysis`

#### Collaboration (6)
- `GET /api/projects/{project}/collaborators`
- `POST /api/projects/{project}/collaborators`
- `PUT /api/projects/{project}/collaborators/{user}`
- `DELETE /api/projects/{project}/collaborators/{user}`
- `GET /api/projects/{project}/search-users`
- `POST /api/projects/{project}/leave`

#### Invitations (5)
- `GET /api/invitations/received`
- `GET /api/invitations/sent`
- `POST /api/invitations/{id}/accept`
- `POST /api/invitations/{id}/decline`
- `DELETE /api/invitations/{id}`

#### Presets (5)
- `GET /api/settings-presets`
- `POST /api/settings-presets`
- `PUT /api/settings-presets/{id}`
- `DELETE /api/settings-presets/{id}`

#### Admin (5)
- `GET /api/admin/users`
- `GET /api/admin/projects`
- `GET /api/admin/stats`
- `PUT /api/admin/users/{id}/role`
- `DELETE /api/admin/users/{id}`

#### Serveur (1)
- `GET /api/server/capabilities` - Détection FFmpeg/workers (public)

---

## 🚀 Installation

### Développement local

#### Prérequis
- **macOS** : PHP 8.2 (via Homebrew), Composer, Node.js 18+
- **Windows** : Laragon/XAMPP avec PHP 8.2+, Composer, Node.js 18+
- **Linux** : PHP 8.2, Composer, Node.js 18+

#### Étapes

```bash
# 1. Cloner le repository
git clone https://github.com/ParizetM/agfaRythmo.git
cd agfaRythmo

# 2. Backend - Installation
cd agfa-rythmo-backend
composer install

# 3. Configuration
cp .env.example .env
php artisan key:generate

# 4. Base de données
php artisan migrate
php artisan db:seed  # Crée admin@example.com / password

# 5. Permissions (Linux/macOS)
chmod -R 775 storage bootstrap/cache
chmod 644 database/database.sqlite

# 6. Frontend - Installation
cd ../agfa-rythmo-frontend
npm install

# 7. Build frontend
npm run build

# 8. Démarrer le serveur Laravel
cd ../agfa-rythmo-backend
php artisan serve
```

**Accès** : http://localhost:8000

**Compte admin** : `admin@example.com` / `password`

**Note** : Le worker de queue (`php artisan queue:work`) n'est nécessaire qu'en production ou pour tester l'analyse IA. En développement local, l'application fonctionne sans worker (sauf pour la détection automatique de scene changes).

---

### Déploiement production (VM vierge)

Guide complet pour installer AgfaRythmo sur une VM Ubuntu/Debian vierge.

#### 🖥️ Prérequis serveur

- **OS** : Ubuntu 22.04 LTS ou Debian 12 (recommandé)
- **RAM** : 2 GB minimum (4 GB recommandé)
- **Stockage** : 20 GB minimum (+ espace vidéos)
- **Accès** : SSH + sudo

#### 📦 Étape 1 : Installation des dépendances

```bash
# Mise à jour du système
sudo apt update && sudo apt upgrade -y

# Note: PHP 8.4 fonctionne aussi parfaitement
sudo apt install -y php php-cli php-fpm php-sqlite3 \
  php-mbstring php-xml php-curl php-zip php-gd \
  php-fileinfo php-json php-openssl

php -v

# Installation Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
composer --version

# Installation Node.js 20+
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
node -v && npm -v

# Installation Apache
sudo apt install -y apache2

# Activation modules Apache
sudo a2enmod rewrite
sudo a2enmod headers
sudo a2enmod ssl
sudo systemctl restart apache2

# Installation FFmpeg (pour IA - optionnel)
sudo apt install -y ffmpeg
ffmpeg -version

# Installation Supervisor (pour workers)
sudo apt install -y supervisor

# Installation Git
sudo apt install -y git
```

#### 🔧 Étape 2 : Clonage et configuration du projet

```bash
# Création répertoire web
sudo mkdir -p /var/www
cd /var/www

# Clonage du projet
sudo git clone https://github.com/ParizetM/agfaRythmo.git
sudo chown -R $USER:$USER agfaRythmo
cd agfaRythmo

# Backend - Installation dépendances
cd agfa-rythmo-backend
composer install --no-dev --optimize-autoloader

# Configuration environnement
cp .env.example .env
nano .env
```

**Configuration `.env` production** :
```ini
APP_NAME=AgfaRythmo
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.votre-domaine.com  # ⚠️ MODIFIER (URL API)

# Database (SQLite)
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/agfaRythmo/agfa-rythmo-backend/database/database.sqlite

# Queue (pour analyse IA)
QUEUE_CONNECTION=database

# Sanctum
SANCTUM_STATEFUL_DOMAINS=votre-domaine.com,api.votre-domaine.com  # ⚠️ MODIFIER
SESSION_DOMAIN=.votre-domaine.com                                  # ⚠️ MODIFIER

# Frontend URL (CORS)
FRONTEND_URL=https://votre-domaine.com      # ⚠️ MODIFIER (URL Frontend)

# IA Configuration (optionnel)
AI_SCENE_DETECTION_ENABLED=true
AI_AUTO_SUBTITLES_ENABLED=false
AI_VOICE_RECOGNITION_ENABLED=false
AI_AUDIO_ANALYSIS_ENABLED=false
```

```bash
# Génération clés
php artisan key:generate

# Création base de données SQLite
touch database/database.sqlite
chmod 664 database/database.sqlite

# Migrations + seed admin
php artisan migrate --force
php artisan db:seed --force

# Optimisations Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Permissions
sudo chown -R www-data:www-data /var/www/agfaRythmo
sudo chmod -R 775 /var/www/agfaRythmo/agfa-rythmo-backend/storage
sudo chmod -R 775 /var/www/agfaRythmo/agfa-rythmo-backend/bootstrap/cache
sudo chmod 664 /var/www/agfaRythmo/agfa-rythmo-backend/database/database.sqlite
```

#### 🎨 Étape 3 : Build du frontend

```bash
cd /var/www/agfaRythmo/agfa-rythmo-frontend

# Configuration environnement
nano .env.production
```

**Fichier `.env.production`** :
```ini
VITE_API_BASE_URL=https://api.votre-domaine.com/api  # ⚠️ MODIFIER (URL API Backend)
```

```bash
# Installation et build
npm install
npm run build

# Permissions sur le dossier de build
sudo chown -R www-data:www-data /var/www/agfaRythmo/agfa-rythmo-frontend/dist
```

#### 🌐 Étape 4 : Configuration Apache (2 VirtualHosts)

**Architecture** : Frontend et Backend séparés sur le même serveur

**A) VirtualHost Frontend (SPA Vue)**

```bash
sudo nano /etc/apache2/sites-available/agfarythmo-frontend.conf
```

**Fichier `agfarythmo-frontend.conf`** :
```apache
<VirtualHost *:80>
    ServerName agfaRythmo.agfagoofay.fr
    ServerAlias www.agfaRythmo.agfagoofay.fr
    
    DocumentRoot /agfaRythmo/agfa-rythmo-frontend/dist
    
    <Directory /agfaRythmo/agfa-rythmo-frontend/dist>
        AllowOverride None
        Require all granted
        Options -Indexes +FollowSymLinks
        
        # SPA routing - toutes les URLs vers index.html
        FallbackResource /index.html
    </Directory>
    
    # Cache des assets statiques
    <FilesMatch "\.(js|css|woff2?|ttf|eot|svg|png|jpg|jpeg|gif|webp|ico)$">
        Header set Cache-Control "public, max-age=31536000, immutable"
    </FilesMatch>
    
    # Compression
    <IfModule mod_deflate.c>
        AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/json
    </IfModule>
    
    # Logs
    ErrorLog ${APACHE_LOG_DIR}/agfarythmo_frontend_error.log
    CustomLog ${APACHE_LOG_DIR}/agfarythmo_frontend_access.log combined
</VirtualHost>
```

**B) VirtualHost Backend (API Laravel)**

```bash
sudo nano /etc/apache2/sites-available/agfarythmo-backend.conf
```

**Fichier `agfarythmo-backend.conf`** :
```apache
<VirtualHost *:80>
  ServerName agfarythmo-backend.agfagoofay.fr
  DocumentRoot /var/www/agfaRythmo/agfa-rythmo-backend/public

  <Directory /var/www/agfaRythmo/agfa-rythmo-backend/public>
    AllowOverride All
    Require all granted
    Options -Indexes +FollowSymLinks
  </Directory>

  # Redirection HTTP → HTTPS
  RewriteEngine On
  RewriteCond %{HTTPS} off
  RewriteRule ^ https://%{SERVER_NAME}%{REQUEST_URI} [L,R=301]

  # Logs
  ErrorLog ${APACHE_LOG_DIR}/agfarythmo_backend_error.log
  CustomLog ${APACHE_LOG_DIR}/agfarythmo_backend_access.log combined
</VirtualHost>

<VirtualHost *:443>
  ServerName agfarythmo-backend.agfagoofay.fr
  DocumentRoot /agfaRythmo/agfa-rythmo-backend/public
  SSLEngine on
  SSLCertificateFile /etc/letsencrypt/live/agfarythmo-backend.agfagoofay.fr/fullchain.pem
  SSLCertificateKeyFile /etc/letsencrypt/live/agfarythmo-backend.agfagoofay.fr/privkey.pem

  <Directory /agfaRythmo/agfa-rythmo-backend/public>
    AllowOverride All
    Require all granted
    Options -Indexes +FollowSymLinks
  </Directory>

  # Headers CORS
  <IfModule mod_headers.c>
    Header set Access-Control-Allow-Origin "https://agfarythmo.agfagoofay.fr"
    Header set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
    Header set Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With"
    Header set Access-Control-Allow-Credentials "true"
  </IfModule>

  # Logs
  ErrorLog ${APACHE_LOG_DIR}/agfarythmo_backend_error.log
  CustomLog ${APACHE_LOG_DIR}/agfarythmo_backend_access.log combined

  # PHP-FPM
  <FilesMatch \.php$>
    SetHandler "proxy:unix:/run/php/php8.4-fpm.sock|fcgi://localhost"
  </FilesMatch>
</VirtualHost>
```

**C) Activation des modules et sites**

```bash
# Modules nécessaires
sudo a2enmod proxy_fcgi
sudo a2enmod headers
sudo a2enmod deflate
sudo a2enmod expires

# Activation des sites
sudo a2ensite agfarythmo-frontend.conf
sudo a2ensite agfarythmo-backend.conf
sudo a2dissite 000-default.conf

# Redémarrage Apache
sudo systemctl reload apache2

# Tests
curl http://localhost  # Frontend
curl http://api.votre-domaine.com/api/server/capabilities  # Backend
```

#### 🔒 Étape 5 : HTTPS avec Let's Encrypt (recommandé)

```bash
# Installation Certbot
sudo apt install -y certbot python3-certbot-apache

# Obtention certificats SSL (Frontend + Backend)
sudo certbot --apache -d agfarythmo.agfagoofay.fr
sudo certbot --apache -d agfarythmo-backend.agfagoofay.fr

# Renouvellement automatique (déjà configuré par défaut)
sudo systemctl status certbot.timer
```

#### ⚙️ Étape 6 : Configuration workers de queue (OBLIGATOIRE pour IA)

**Option A : Supervisor (recommandé)**

```bash
# Configuration Supervisor
sudo nano /etc/supervisor/conf.d/agfaRythmo-worker.conf
```

**Fichier `agfaRythmo-worker.conf`** :
```ini
[program:agfaRythmo-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/agfaRythmo/agfa-rythmo-backend/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/agfaRythmo/agfa-rythmo-backend/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
# Activation Supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start agfaRythmo-worker:*

# Vérification
sudo supervisorctl status
```

**Option B : Systemd**

```bash
sudo nano /etc/systemd/system/agfaRythmo-worker.service
```

**Fichier `agfaRythmo-worker.service`** :
```ini
[Unit]
Description=AgfaRythmo Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/agfaRythmo/agfa-rythmo-backend
ExecStart=/usr/bin/php /var/www/agfaRythmo/agfa-rythmo-backend/artisan queue:work --sleep=3 --tries=3 --max-time=3600
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable agfaRythmo-worker
sudo systemctl start agfaRythmo-worker
sudo systemctl status agfaRythmo-worker
```

**Option C : Cron (serveur mutualisé sans Supervisor)**

```bash
crontab -e
```

Ajouter :
```cron
* * * * * cd /var/www/agfaRythmo/agfa-rythmo-backend && php artisan schedule:run >> /dev/null 2>&1
* * * * * cd /var/www/agfaRythmo/agfa-rythmo-backend && php artisan queue:work --stop-when-empty >> storage/logs/worker.log 2>&1
```

#### 🎉 Étape 7 : Vérification finale

```bash
# Test API
curl https://api.votre-domaine.com/api/server/capabilities

# Doit retourner :
# {"ffmpeg_available":true,"queue_worker_running":true,...}

# Test Frontend
curl https://votre-domaine.com
# Doit retourner le HTML de l'index.html

# Test worker
cd /var/www/agfaRythmo/agfa-rythmo-backend
php artisan queue:work --once --verbose

# Logs
tail -f storage/logs/laravel.log
tail -f storage/logs/worker.log
sudo tail -f /var/log/apache2/agfarythmo_backend_error.log
sudo tail -f /var/log/apache2/agfarythmo_frontend_error.log
```

**URLs de test** :
- **Frontend** : `https://votre-domaine.com`
- **API** : `https://api.votre-domaine.com/api/server/capabilities`
- **Login** : `admin@example.com` / `password`

#### 📊 Étape 8 : Monitoring (optionnel)

```bash
# Installation htop pour monitoring
sudo apt install -y htop

# Monitoring workers
watch -n 1 'sudo supervisorctl status'

# Monitoring jobs
cd /var/www/agfaRythmo/agfa-rythmo-backend
php artisan queue:monitor

# Monitoring espace disque
df -h
du -sh storage/app/videos/
```

---

## ⚙️ Configuration

### Variables d'environnement backend (`.env`)

| Variable | Valeur par défaut | Description |
|----------|-------------------|-------------|
| `APP_ENV` | `local` | Environnement (`local`, `production`) |
| `APP_DEBUG` | `true` | Mode debug (false en prod) |
| `APP_URL` | `http://localhost:8000` | URL de l'application (API) |
| `FRONTEND_URL` | `http://localhost:5173` | URL du frontend (CORS) |
| `DB_CONNECTION` | `sqlite` | Type de base de données |
| `DB_DATABASE` | `database/database.sqlite` | Chemin SQLite |
| `QUEUE_CONNECTION` | `database` | Driver de queue |
| `SANCTUM_STATEFUL_DOMAINS` | `localhost:8000` | Domaines Sanctum |
| `SESSION_DOMAIN` | `localhost` | Domaine de session |
| `AI_SCENE_DETECTION_ENABLED` | `true` | Activer détection IA |


### Configuration IA (`config/ai.php`)

```php
return [
    'scene_detection' => [
        'enabled' => env('AI_SCENE_DETECTION_ENABLED', true),
        'ffmpeg_path' => env('FFMPEG_PATH', 'ffmpeg'),
        'threshold' => env('SCENE_DETECTION_THRESHOLD', 0.4),
    ],
    // Futures fonctionnalités...
];
```

### Variables d'environnement frontend (`.env.production`)

| Variable | Valeur | Description |
|----------|--------|-------------|
| `VITE_API_BASE_URL` | `https://api.votre-domaine.com/api` | URL de l'API backend |

---

## 💡 Utilisation

### Création d'un projet

1. **Login** : Se connecter avec `admin@example.com` / `password`
2. **Nouveau projet** : Clic sur "Nouveau projet"
3. **Upload vidéo** : Sélectionner fichier MP4/WebM/AVI
4. **Configuration** :
   - Nombre de lignes rythmo (1-10)
   - Police, taille, couleurs
   - Espacements
5. **Sauvegarde** : Clic sur "Créer"

### Ajout de timecodes

**Option 1 : Création manuelle**
- Clic sur "+ Nouveau timecode"
- Entrer start/end (format `HH:MM:SS.mmm`)
- Saisir le texte
- Sélectionner personnage (optionnel)
- Sauvegarder

**Option 2 : Import SRT**
- Clic sur "Importer SRT"
- Sélectionner fichier `.srt`
- Validation automatique
- Timecodes créés avec lignes associées

### Détection IA des scene changes

1. **Clic sur bouton "IA"** (gradient violet/rose)
2. **Vérifier disponibilité** : statut FFmpeg dans modal
3. **Lancer analyse** : clic sur "Lancer la détection"
4. **Configuration** (optionnel) :
   - Seuil de détection (0.1-1.0)
   - Mode d'analyse (histogramme/diff)
5. **Attendre** : progression en temps réel
6. **Résultats** : scene changes créés automatiquement

### Collaboration

1. **Inviter un utilisateur** :
   - Panel "Collaborateurs"
   - Rechercher par email
   - Sélectionner permission (view/edit/admin)
   - Envoyer invitation
2. **Accepter une invitation** :
   - Notification dans header
   - Panel "Invitations reçues"
   - Clic sur "Accepter"
3. **Modifier permissions** :
   - Panel "Collaborateurs"
   - Dropdown de permissions
   - Mise à jour instantanée

### Export/Import de projets

**Export** :
- Clic sur "Exporter" dans détail du projet
- Téléchargement fichier `.agfa` (JSON crypté)
- Contient tout sauf la vidéo

**Import** :
- Clic sur "Importer un projet" (ProjectsView)
- **Avec vidéo** : sélectionner `.agfa` + vidéo
- **Sans vidéo** : sélectionner `.agfa` uniquement
- Création automatique du projet

### Mode maintenance

**Activation** :
```bash
cd agfa-rythmo-backend/storage/framework
mv RENAME_TO_maintenance_TO_ENABLE maintenance
```

**Désactivation** :
```bash
cd agfa-rythmo-backend/storage/framework
mv maintenance RENAME_TO_maintenance_TO_ENABLE
```

Effet immédiat, aucun redémarrage nécessaire. Redirection automatique vers `/maintenance`.

---

## 🐛 Dépannage

### Worker de queue ne démarre pas

**Symptômes** : Analyse IA reste à 0%, jobs ne se lancent pas

**Solutions** :
```bash
# Vérifier permissions
sudo chown -R www-data:www-data storage/
sudo chmod -R 775 storage/

# Tester manuellement
cd agfa-rythmo-backend
php artisan queue:work --once --verbose

# Vérifier Supervisor
sudo supervisorctl status
sudo supervisorctl restart agfaRythmo-worker:*

# Logs
tail -f storage/logs/worker.log
```

### FFmpeg introuvable

**Symptômes** : Menu IA affiche "FFmpeg non disponible"

**Solutions** :
```bash
# Installation
sudo apt install ffmpeg  # Ubuntu/Debian
brew install ffmpeg      # macOS

# Vérification
which ffmpeg
ffmpeg -version

# Configuration .env
FFMPEG_PATH=/usr/bin/ffmpeg  # Chemin complet si besoin
```

### Erreur 500 après déploiement

**Solutions** :
```bash
# Permissions
sudo chown -R www-data:www-data /var/www/agfaRythmo
sudo chmod -R 775 storage bootstrap/cache

# Cache Laravel
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Logs
tail -f storage/logs/laravel.log
sudo tail -f /var/log/apache2/agfarythmo_error.log
```

### CORS errors

**Symptômes** : Erreurs "Access-Control-Allow-Origin" dans console

**Solutions** :
```bash
# Backend .env
FRONTEND_URL=https://votre-domaine.com
SANCTUM_STATEFUL_DOMAINS=votre-domaine.com
SESSION_DOMAIN=.votre-domaine.com

# Vider cache
php artisan config:clear
```

### Upload vidéo échoue

**Solutions** :
```bash
# Augmenter limites PHP
sudo nano /etc/php/8.2/fpm/php.ini
```
Modifier :
```ini
upload_max_filesize = 500M
post_max_size = 500M
max_execution_time = 300
memory_limit = 256M
```

```bash
# Redémarrer PHP-FPM
sudo systemctl restart php8.2-fpm
```

### Base de données verrouillée (SQLite)

**Symptômes** : "Database is locked"

**Solutions** :
```bash
# Permissions
sudo chmod 664 database/database.sqlite
sudo chmod 775 database/

# Utiliser WAL mode (config/database.php)
'sqlite' => [
    'foreign_key_constraints' => true,
    'journal_mode' => 'WAL',  // Ajouter cette ligne
],

# Appliquer
php artisan config:clear
```

---

## 📚 Documentation

### Guides utilisateur
- **[SCENE_DETECTION_GUIDE.md](SCENE_DETECTION_GUIDE.md)** - Guide complet détection IA
- **[TESTING_SCENE_DETECTION.md](TESTING_SCENE_DETECTION.md)** - Tests et validation
- **[MAINTENANCE_GUIDE.md](MAINTENANCE_GUIDE.md)** - Activer/désactiver maintenance

### Guides techniques
- **[DEPLOYMENT_WORKERS.md](DEPLOYMENT_WORKERS.md)** - Configuration workers production
- **[SCENE_DETECTION_IA.md](SCENE_DETECTION_IA.md)** - Analyse IA avec FFmpeg
- **[SERVER_CAPABILITIES.md](SERVER_CAPABILITIES.md)** - Détection capacités serveur
- **[AI_CONFIG.md](AI_CONFIG.md)** - Configuration menu IA
- **[.github/instructions/instructions_projets.instructions.md](.github/instructions/instructions_projets.instructions.md)** - Instructions GitHub Copilot

### Ressources externes
- [Laravel 12 Documentation](https://laravel.com/docs/12.x)
- [Vue 3 Documentation](https://vuejs.org/)
- [FFmpeg Documentation](https://ffmpeg.org/documentation.html)
- [Supervisor Documentation](http://supervisord.org/)

---

## 👥 Contribution

Les contributions sont bienvenues ! Voici comment contribuer :

### Workflow

1. **Fork** le repository
2. **Créer une branche** :
   ```bash
   git checkout -b feature/NouvelleFonctionnalite
   ```
3. **Développer** en respectant les conventions
4. **Tester** :
   ```bash
   cd agfa-rythmo-backend && php artisan test
   cd agfa-rythmo-frontend && npm run lint
   ```
5. **Commit** avec messages clairs :
   ```bash
   git commit -m "feat: ajout export PDF des bandes rythmo"
   ```
6. **Push** :
   ```bash
   git push origin feature/NouvelleFonctionnalite
   ```
7. **Pull Request** avec description détaillée

### Conventions de code

**Backend (Laravel)** :
- PSR-12 coding standard
- Nommage : PascalCase classes, camelCase méthodes
- FormRequest pour validation
- Services pour logique métier complexe

**Frontend (Vue/TypeScript)** :
- `<script setup>` Composition API
- TypeScript strict (pas de `any`)
- Props/Emits typés
- Composables pour logique réutilisable

**Git commits** :
- `feat:` Nouvelle fonctionnalité
- `fix:` Correction de bug
- `refactor:` Refactorisation
- `docs:` Documentation
- `test:` Ajout de tests
- `chore:` Maintenance

---

## 📄 Licence

Ce projet est sous **licence propriétaire**. Tous droits réservés.

© 2025 Martin P. - AgfaRythmo

**Utilisation commerciale interdite sans autorisation.**

---

## 👨‍💻 Auteur & Support

**Développeur** : Martin P. ([@ParizetM](https://github.com/ParizetM))

### Support

- 📧 **Email** : support@agfarythmo.com
- 🐛 **Issues** : [GitHub Issues](https://github.com/ParizetM/agfaRythmo/issues)
- 💬 **Discussions** : [GitHub Discussions](https://github.com/ParizetM/agfaRythmo/discussions)
- 📖 **Wiki** : [GitHub Wiki](https://github.com/ParizetM/agfaRythmo/wiki)

### Réseaux sociaux

- 🐦 Twitter : [@AgfaRythmo](https://twitter.com/AgfaRythmo)
- 💼 LinkedIn : [Martin Parizet](https://linkedin.com/in/martin-parizet)

---

## 🙏 Remerciements

Ce projet utilise et remercie les technologies suivantes :

- **[Laravel](https://laravel.com)** - Framework PHP élégant
- **[Vue.js](https://vuejs.org)** - Framework JavaScript progressif
- **[Tailwind CSS](https://tailwindcss.com)** - Framework CSS utility-first
- **[FFmpeg](https://ffmpeg.org)** - Suite multimédia open-source
- **[Vite](https://vitejs.dev)** - Build tool ultra-rapide
- **[TypeScript](https://www.typescriptlang.org)** - JavaScript typé
- **[Pinia](https://pinia.vuejs.org)** - State management Vue

Et toute la communauté open-source ! ❤️

---

## 📝 Changelog

### v2.1.0-beta (29 octobre 2025)

**🎯 Nouvelles fonctionnalités**
- ✅ Menu IA unifié avec détection automatique capacités serveur
- ✅ Import/Export projets format `.agfa` crypté
- ✅ Mode maintenance global (backend + frontend)
- ✅ Détection IA automatique des changements de plan (FFmpeg)
- ✅ Statistiques espace vidéos dans admin
- ✅ Bouton "Supprimer tous les scene changes"

**🎨 UI/UX**
- ✅ Modales modernes avec `BaseModal` et `ConfirmModal`
- ✅ Menu navigation mobile responsive
- ✅ Google Fonts dynamiques avec cache localStorage
- ✅ Glassmorphism design
- ✅ Optimisations GPU et vidéo mobile (playsinline iOS)

**🔧 Techniques**
- ✅ Service `ServerCapabilities` pour détection FFmpeg/workers
- ✅ Composable `useServerCapabilities`
- ✅ Validation session authentification renforcée
- ✅ Désactivation raccourcis clavier contextuelle
- ✅ Navigation scene changes en preview finale

### v2.0.0-beta (octobre 2025)

**🎉 Release initiale**
- ✅ Architecture Laravel 12 + Vue 3 + TypeScript
- ✅ Authentification Sanctum (JWT)
- ✅ Timecodes multi-lignes (1-10)
- ✅ Personnages avec couleurs personnalisables
- ✅ Scene changes manuels
- ✅ Import fichiers SRT
- ✅ Collaboration multi-utilisateurs (3 permissions)
- ✅ Panel administration complet
- ✅ Interface responsive dark mode
- ✅ Presets paramètres utilisateur
- ✅ Streaming vidéo optimisé

---

## 🚀 Roadmap

### v2.2.0 (Q1 2026)
- [ ] Sous-titrage automatique (IA)
- [ ] Reconnaissance vocale
- [ ] Export PDF des bandes rythmo
- [ ] Templates de projets

### v2.3.0 (Q2 2026)
- [ ] Support multi-langues (i18n)
- [ ] Thèmes personnalisables
- [ ] Intégration stockage cloud (S3, GCS)
- [ ] API publique avec documentation

### v3.0.0 (Q3 2026)
- [ ] Synchronisation temps réel (WebSockets)
- [ ] Édition collaborative simultanée
- [ ] Versioning de projets
- [ ] Mobile apps (React Native)

---

<p align="center">
  <strong>Fait avec ❤️ et ☕ par Martin P.</strong><br>
  <sub>AgfaRythmo - Professional Rythmo Band Generator</sub>
</p>

<p align="center">
  <a href="#-table-des-matières">⬆ Retour en haut</a>
</p>
