---
applyTo: '**'
---

# Instructions détaillées pour GitHub Copilot - AgfaRythmo

## Règles de développement strictes

- **INTERDICTIONS** :
  - Ne pas utiliser de navigateur simple dans VSCode
  - Ne jamais faire `npm run dev` (utiliser les scripts composer du backend)
- **BONNES PRATIQUES** :
  - Toujours ajouter les fonctions JavaScript au bon endroit dans les fichiers Vue (dans `<script setup>`)
  - Respecter l'architecture API REST existante
  - Utiliser TypeScript pour le frontend
  - Suivre les conventions Laravel et Vue.js standards

## Contexte du projet

**AgfaRythmo** est une application web professionnelle qui recrée l'application Capella pour la génération de bandes rythmo. Elle permet l'import de vidéos, l'édition de texte synchronisé avec timecodes, la gestion des changements de plan et la prévisualisation complète.

### Architecture actuelle

```
agfaRythmo/
├── agfa-rythmo-backend/    # API Laravel 12.0
├── agfa-rythmo-frontend/   # Application Vue.js 3.5 + TypeScript
└── video.mp4              # Fichier de test
```

## Stack technique détaillée

### Backend (agfa-rythmo-backend/)
- **Framework** : Laravel 12.0 (PHP 8.2+)
- **Base de données** : SQLite (`database/database.sqlite`)
- **Authentification** : Laravel Sanctum 4.0
- **Build** : Vite 7.0 + Tailwind CSS 4.0
- **Tests** : PHPUnit 11.5

### Frontend (agfa-rythmo-frontend/)
- **Framework** : Vue.js 3.5.18 + TypeScript 5.8
- **State Management** : Pinia 3.0.3
- **Routing** : Vue Router 4.5.1
- **Build** : Vite 7.0.6
- **Styling** : Tailwind CSS 4.1.12
- **HTTP Client** : Axios 1.11.0
- **Node** : ^20.19.0 || >=22.12.0

## Modèles de données (Backend)

### Models principaux
```php
// Project.php - Projet principal
protected $fillable = ['name', 'description', 'video_path', 'timecodes', 'text_content', 'json_path', 'rythmo_lines_count'];

// Timecode.php - Timecodes multi-lignes
// SceneChange.php - Changements de plan
// Character.php - Personnages
// User.php - Utilisateurs
```

### Relations
- `Project` hasMany `SceneChange`, `Timecode`, `Character`
- Timecodes ordonnés par `line_number` puis `start`

## API REST Endpoints

### Projets
```php
// CRUD complet
GET|POST /api/projects
GET|PUT|DELETE /api/projects/{id}
PATCH /api/projects/{project}/rythmo-lines
```

### Vidéos
```php
POST /api/videos/upload
GET /api/videos/{filename}  // Streaming
```

### Timecodes (multi-lignes)
```php
GET|POST /api/projects/{project}/timecodes
GET|PUT|DELETE /api/projects/{project}/timecodes/{timecode}
GET /api/projects/{project}/timecodes/line/{lineNumber}
```

### Changements de plan
```php
GET|POST /api/projects/{project}/scene-changes
DELETE /api/scene-changes/{id}
```

### Personnages
```php
GET /api/projects/{project}/characters
POST|PUT|DELETE /api/characters/{character}
POST /api/characters/clone
GET /api/characters/for-cloning
```

## Structure Frontend

### Vues principales
```
src/views/
├── HomeView.vue           # Page d'accueil
├── ProjectsView.vue       # Liste des projets
├── ProjectDetailView.vue  # Édition de projet
└── FinalPreviewView.vue   # Prévisualisation finale
```

### Composants
```
src/components/
├── VideoUploader.vue      # Upload de vidéos
└── projectDetail/         # Composants d'édition
```

### Architecture frontend
```
src/
├── api/           # Services API (Axios)
├── assets/        # Assets statiques
├── components/    # Composants Vue réutilisables
├── router/        # Configuration Vue Router
├── stores/        # Stores Pinia
└── views/         # Pages principales
```

## Fonctionnalités implémentées

### ✅ Fonctionnalités actuelles
- **Gestion de projets** : CRUD complet avec métadonnées
- **Upload de vidéos** : Multi-formats avec streaming
- **Timecodes multi-lignes** : Édition avancée par ligne
- **Changements de plan** : Indicateurs visuels
- **Gestion des personnages** : CRUD + clonage
- **Interface responsive** : Tailwind CSS avec thème sombre
- **Navigation** : Vue Router configuré

### 🚧 En développement
- Bande rythmo animée synchronisée
- Prévisualisation vidéo + texte intégré
- Export de projets (JSON)
- Interface d'édition temps réel

## Scripts de développement
### Frontend
```bash
npm run dev        # Serveur de développement
npm run build      # Build production
npm run type-check # Vérification TypeScript
npm run lint       # ESLint + Prettier
```

## Thème et Design System

### Palette de couleurs
```css
--bg-primary: #121827      /* Fond principal */
--bg-secondary: #202937    /* Couleur menu */
--accent: #8455F6          /* Couleur forte (violet) */
--button: #384152          /* Couleur bouton */
--text: #FFFFFF            /* Texte principal */
```

### Guidelines UI
- Interface sombre (dark mode par défaut)
- Design moderne et épuré
- Composants accessibles
- Responsive design (mobile-first)

## Bonnes pratiques de développement

### Code Quality
- **TypeScript strict** pour le frontend
- **Validation Laravel** pour les APIs
- **Tests unitaires** obligatoires pour les fonctions critiques
- **Documentation** des endpoints et composants complexes

### Architecture
- Séparation stricte frontend/backend via API REST
- State management centralisé avec Pinia
- Composants Vue réutilisables et modulaires
- Modèles Laravel avec relations bien définies

### Performance
- Lazy loading des composants Vue
- Streaming vidéo optimisé
- Pagination pour les listes importantes
- Cache approprié côté backend

## Cibles de déploiement

- **Développement** : VSCode + GitHub Copilot
- **Web** : Application SPA moderne
- **Desktop** : Futur portage Electron prévu
- **Cross-platform** : Linux, macOS, Windows

## Notes importantes pour Copilot

1. **Toujours vérifier** les endpoints API existants avant d'en créer de nouveaux
2. **Respecter** la structure des modèles Laravel existants
3. **Utiliser** TypeScript pour tous les nouveaux fichiers frontend
4. **Suivre** les conventions de nommage Laravel/Vue.js
5. **Tester** les modifications avec les scripts composer appropriés
6. **NE JAMAIS** exécuter `npm run dev` dans le frontend