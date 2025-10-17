---
applyTo: '**'
---

# Instructions détaillées pour GitHub Copilot - AgfaRythmo

**Date de mise à jour** : 17 octobre 2025

## Règles de développement strictes

### ⛔ INTERDICTIONS ABSOLUES
- **Ne JAMAIS faire `npm run dev` dans le frontend**
- **Ne pas utiliser de navigateur simple dans VSCode** pour les prévisualisations
- **Ne jamais créer de routes API sans vérifier les existantes**
- **Ne jamais modifier les migrations déjà exécutées**

### ✅ BONNES PRATIQUES OBLIGATOIRES
- **Toujours** ajouter les fonctions dans `<script setup>` pour les fichiers Vue
- **Toujours** utiliser TypeScript strict pour le frontend
- **Toujours** valider les données côté backend avec les FormRequest Laravel
- **Toujours** respecter l'architecture API REST existante
- **Toujours** tester les modifications avant de commit
- **Suivre** les conventions Laravel (PascalCase pour classes, camelCase pour méthodes)
- **Suivre** les conventions Vue.js (PascalCase pour composants, kebab-case pour events)

## Contexte du projet

**AgfaRythmo** est une application web professionnelle qui recrée l'application Capella pour la génération de bandes rythmo (rythmo bands). Elle permet :
- L'import et le streaming de vidéos
- L'édition de texte synchronisé avec timecodes multi-lignes
- La gestion des personnages avec couleurs personnalisables
- La gestion des changements de plan (scene changes)
- L'import de fichiers SRT
- La collaboration multi-utilisateurs avec permissions
- Un système d'authentification complet (admin/user)
- La gestion de presets de paramètres (max 5 par utilisateur)
- La prévisualisation complète des bandes rythmo

### Architecture actuelle

```
agfaRythmo/
├── agfa-rythmo-backend/    # API Laravel 12.0 (PHP 8.2+)
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   ├── Api/                    # Controllers API
│   │   │   │   │   ├── AdminUserController.php
│   │   │   │   │   ├── AuthController.php
│   │   │   │   │   ├── ProjectCollaboratorController.php
│   │   │   │   │   ├── ProjectController.php
│   │   │   │   │   ├── ProjectInvitationController.php
│   │   │   │   │   └── SettingsPresetController.php
│   │   │   │   ├── CharacterController.php
│   │   │   │   ├── SceneChangeController.php
│   │   │   │   ├── TimecodeController.php
│   │   │   │   └── VideoController.php
│   │   │   └── Middleware/
│   │   │       └── IsAdmin.php             # Middleware admin
│   │   ├── Models/
│   │   │   ├── Character.php               # Personnages
│   │   │   ├── Project.php                 # Projets
│   │   │   ├── ProjectCollaborator.php     # Collaborateurs
│   │   │   ├── ProjectInvitation.php       # Invitations
│   │   │   ├── SceneChange.php             # Changements de plan
│   │   │   ├── SettingsPreset.php          # Presets paramètres
│   │   │   ├── Timecode.php                # Timecodes
│   │   │   └── User.php                    # Utilisateurs
│   │   └── Services/
│   │       └── SrtParser.php               # Parseur SRT
│   ├── database/
│   │   ├── database.sqlite                 # Base de données SQLite
│   │   └── migrations/                     # 44+ migrations
│   └── routes/
│       └── api.php                         # Routes API REST
│
├── agfa-rythmo-frontend/   # Application Vue.js 3.5 + TypeScript
│   ├── src/
│   │   ├── api/                            # Services API Axios (10 fichiers)
│   │   │   ├── admin.ts
│   │   │   ├── auth.ts
│   │   │   ├── axios.ts
│   │   │   ├── characters.ts
│   │   │   ├── collaboration.ts
│   │   │   ├── invitations.ts
│   │   │   ├── projectSettings.ts
│   │   │   ├── sceneChanges.ts
│   │   │   ├── settingsPresets.ts
│   │   │   └── timecodes.ts
│   │   ├── components/
│   │   │   ├── InvitationsPanel.vue        # Panneau invitations
│   │   │   ├── NotificationToast.vue       # Notifications toast
│   │   │   ├── VideoUploader.vue           # Upload vidéos
│   │   │   └── projectDetail/              # 15 composants d'édition
│   │   │       ├── CharacterModal.vue
│   │   │       ├── CharactersList.vue
│   │   │       ├── CollaboratorsPanel.vue
│   │   │       ├── ConfirmDeleteModal.vue
│   │   │       ├── KeyboardShortcutsModal.vue
│   │   │       ├── MultiRythmoBand.vue
│   │   │       ├── PresetsManager.vue
│   │   │       ├── ProjectSettingsModal.vue
│   │   │       ├── RythmoBandSingle.vue
│   │   │       ├── SceneChangeEditModal.vue
│   │   │       ├── SceneChangesList.vue
│   │   │       ├── TimecodeModal.vue
│   │   │       ├── TimecodesListMultiLine.vue
│   │   │       ├── VideoNavigationBar.vue
│   │   │       ├── VideoPlayer.vue
│   │   │       └── useSmoothScroll.ts
│   │   ├── composables/
│   │   │   ├── useCollaborativeRefresh.ts  # Rafraîchissement collab
│   │   │   └── useInvitations.ts           # Gestion invitations
│   │   ├── router/
│   │   │   └── index.ts                    # Router avec guards auth
│   │   ├── services/
│   │   │   ├── googleFonts.ts              # Chargement fonts
│   │   │   └── notifications.ts            # Service notifications
│   │   ├── stores/
│   │   │   ├── auth.ts                     # Store authentification
│   │   │   ├── counter.ts                  # Store exemple (à supprimer)
│   │   │   └── projectSettings.ts          # Store settings projet
│   │   ├── utils/
│   │   │   ├── colorUtils.ts               # Utils couleurs
│   │   │   └── separatorEncoding.ts        # Encodage séparateurs
│   │   └── views/
│   │       ├── AdminView.vue               # Admin panel
│   │       ├── FinalPreviewView.vue        # Prévisualisation finale
│   │       ├── LoginView.vue               # Connexion
│   │       ├── ProfileView.vue             # Profil utilisateur
│   │       ├── ProjectDetailView.vue       # Édition projet
│   │       ├── ProjectsView.vue            # Liste projets
│   │       └── RegisterView.vue            # Inscription
│   └── tailwind.config.js                  # Config Tailwind
│
└── video.mp4                               # Fichier de test
```

## Stack technique complète

### Backend (agfa-rythmo-backend/)
- **Framework** : Laravel 12.0 (PHP 8.2+)
- **Base de données** : SQLite (`database/database.sqlite`)
- **Authentification** : Laravel Sanctum 4.0 avec tokens API
- **Build** : Vite 7.0.4 + Tailwind CSS 4.0 + Laravel Vite Plugin 2.0
- **Tests** : PHPUnit 11.5.3
- **Dev Tools** : 
  - Laravel Pail 1.2.2 (logs en temps réel)
  - Laravel Tinker 2.10.1
  - Laravel Pint 1.13 (formatage code)
  - Concurrently 9.0.1 (orchestration dev)

**Script de développement** :
```bash
composer dev  # Lance simultanément : server, queue, logs, vite
```

### Frontend (agfa-rythmo-frontend/)
- **Framework** : Vue.js 3.5.18 + TypeScript 5.8
- **State Management** : Pinia 3.0.3
- **Routing** : Vue Router 4.5.1 avec guards d'authentification
- **Build** : Vite 7.0.6
- **Styling** : Tailwind CSS 4.1.12 + @tailwindcss/postcss
- **HTTP Client** : Axios 1.11.0 avec intercepteurs
- **Icons** : Heroicons Vue 2.2.0
- **Node** : ^20.19.0 || >=22.12.0
- **Dev Tools** :
  - Vue DevTools 8.0.0
  - ESLint 9.31.0 + Prettier 3.6.2
  - vue-tsc 3.0.4 (type checking)
  - vite-svg-loader 5.1.0

## Modèles de données détaillés (Backend)

### 1. User (Utilisateurs)
```php
protected $fillable = ['name', 'email', 'password', 'role'];

// Rôles possibles: 'admin' | 'user'
// Méthodes: isAdmin(), isUser(), accessibleProjects(), pendingInvitations()
// Relations: 
//   - projects() : projets créés
//   - collaborativeProjects() : projets collaboratifs
//   - sentInvitations() : invitations envoyées
//   - receivedInvitations() : invitations reçues
```

### 2. Project (Projets)
```php
protected $fillable = [
    'name',
    'description',
    'video_path',           // Chemin vidéo
    'timecodes',            // JSON legacy
    'text_content',         // Texte legacy
    'json_path',            // Chemin export JSON
    'rythmo_lines_count',   // Nombre de lignes rythmo
    'user_id',              // Propriétaire
    'project_settings'      // JSON settings (cast array)
];

// Méthodes: hasAccess(User), canModify(User), canAdmin(User)
// Relations:
//   - owner() : User propriétaire
//   - collaborators() : Users collaborateurs (pivot: permission, created_at)
//   - sceneChanges() : SceneChange
//   - timecodes() : Timecode (orderBy line_number, start)
//   - characters() : Character
//   - invitations() : ProjectInvitation
//   - timecodesForLine($lineNumber) : Timecode filtrés
```

### 3. Timecode (Timecodes multi-lignes)
```php
protected $fillable = [
    'project_id',
    'line_number',          // Numéro de ligne (1, 2, 3...)
    'start',                // Float (secondes)
    'end',                  // Float (secondes)
    'text',                 // Texte du timecode
    'character_id',         // ID personnage (nullable)
    'show_character',       // Boolean affichage nom
    'separator_positions'   // Array positions séparateurs
];

protected $casts = [
    'start' => 'float',
    'end' => 'float',
    'line_number' => 'integer',
    'show_character' => 'boolean',
    'separator_positions' => 'array'
];

// Relations: project(), character()
```

### 4. Character (Personnages)
```php
protected $fillable = [
    'project_id',
    'name',                 // Nom du personnage
    'color',                // Couleur fond (hex)
    'text_color'            // Couleur texte (hex)
];

// Relations: project(), timecodes()
```

### 5. SceneChange (Changements de plan)
```php
protected $fillable = [
    'project_id',
    'timecode'              // Float (secondes)
];

// Relations: project()
```

### 6. SettingsPreset (Presets paramètres utilisateur)
```php
protected $fillable = [
    'user_id',
    'name',                 // Nom du preset
    'settings'              // JSON settings (cast array)
];

// Limite: 5 presets max par utilisateur
// Méthode statique: canCreate(User) : bool
// Relations: user()
```

### 7. ProjectCollaborator (Table pivot)
```php
// Champs pivot :
// - user_id : ID utilisateur
// - project_id : ID projet
// - permission : 'view' | 'edit' | 'admin'
// - created_at, updated_at
```

### 8. ProjectInvitation (Invitations de projet)
```php
protected $fillable = [
    'project_id',
    'invited_user_id',      // ID utilisateur invité
    'invited_by',           // ID utilisateur inviteur
    'permission',           // 'view' | 'edit' | 'admin'
    'status',               // 'pending' | 'accepted' | 'declined' | 'cancelled'
    'expires_at'            // DateTime expiration (nullable)
];

// Relations: project(), invitedUser(), invitedBy()
```

## API REST Endpoints complets

### 🔓 Routes publiques (sans authentification)

```php
POST /api/auth/register        // Inscription
POST /api/auth/login           // Connexion
GET  /api/videos/{filename}    // Streaming vidéo (public pour <video>)
```

### 🔐 Routes authentifiées (middleware: auth:sanctum)

#### Authentification
```php
POST   /api/auth/logout           // Déconnexion
GET    /api/auth/profile          // Récupérer profil
PUT    /api/auth/profile          // Modifier profil
POST   /api/auth/change-password  // Changer mot de passe
```

#### Projets
```php
GET    /api/projects                         // Liste projets accessibles
POST   /api/projects                         // Créer projet
GET    /api/projects/{id}                    // Détails projet
PUT    /api/projects/{id}                    // Modifier projet
DELETE /api/projects/{id}                    // Supprimer projet
PATCH  /api/projects/{project}/rythmo-lines  // Modifier nb lignes rythmo
PATCH  /api/projects/{project}/settings      // Modifier settings projet
```

#### Vidéos
```php
POST /api/videos/upload  // Upload vidéo (multipart/form-data)
```

#### Timecodes (multi-lignes)
```php
GET    /api/projects/{project}/timecodes                  // Liste tous timecodes
POST   /api/projects/{project}/timecodes                  // Créer timecode
POST   /api/projects/{project}/timecodes/import-srt       // Import fichier SRT
GET    /api/projects/{project}/timecodes/{timecode}       // Détails timecode
PUT    /api/projects/{project}/timecodes/{timecode}       // Modifier timecode
DELETE /api/projects/{project}/timecodes/{timecode}       // Supprimer timecode
GET    /api/projects/{project}/timecodes/line/{lineNumber} // Timecodes d'une ligne
```

#### Changements de plan
```php
GET    /api/projects/{project}/scene-changes  // Liste scene changes
POST   /api/projects/{project}/scene-changes  // Créer scene change
PUT    /api/scene-changes/{id}                // Modifier scene change
DELETE /api/scene-changes/{id}                // Supprimer scene change
```

#### Personnages
```php
GET    /api/projects/{project}/characters  // Liste personnages du projet
POST   /api/characters                     // Créer personnage
PUT    /api/characters/{character}         // Modifier personnage
DELETE /api/characters/{character}         // Supprimer personnage
POST   /api/characters/clone               // Cloner personnage
GET    /api/characters/for-cloning         // Liste personnages clonables
```

#### Presets de paramètres (max 5/user)
```php
GET    /api/settings-presets        // Liste presets utilisateur
POST   /api/settings-presets        // Créer preset
PUT    /api/settings-presets/{id}   // Modifier preset
DELETE /api/settings-presets/{id}   // Supprimer preset
```

#### Collaboration sur projets
```php
GET    /api/projects/{project}/collaborators         // Liste collaborateurs
POST   /api/projects/{project}/collaborators         // Ajouter collaborateur
PUT    /api/projects/{project}/collaborators/{user}  // Modifier permission
DELETE /api/projects/{project}/collaborators/{user}  // Retirer collaborateur
POST   /api/projects/{project}/leave                 // Quitter projet
GET    /api/projects/{project}/search-users          // Rechercher utilisateurs
```

#### Invitations de projets
```php
GET    /api/invitations                          // Mes invitations reçues
POST   /api/invitations                          // Créer invitation
GET    /api/projects/{project}/invitations       // Invitations du projet
POST   /api/invitations/{invitation}/accept      // Accepter invitation
POST   /api/invitations/{invitation}/decline     // Refuser invitation
DELETE /api/invitations/{invitation}             // Annuler invitation
```

### 🔐👑 Routes admin (middleware: auth:sanctum + admin)

```php
GET    /api/admin/users                    // Liste tous utilisateurs
POST   /api/admin/users                    // Créer utilisateur
GET    /api/admin/users/{user}             // Détails utilisateur
PUT    /api/admin/users/{user}             // Modifier utilisateur
POST   /api/admin/users/{user}/change-password  // Changer mdp utilisateur
DELETE /api/admin/users/{user}             // Supprimer utilisateur
GET    /api/admin/projects                 // Liste tous projets
DELETE /api/admin/projects/{project}       // Supprimer projet
GET    /api/admin/stats                    // Statistiques globales
```

## Architecture Frontend détaillée

### Vues (7 vues principales)
```
src/views/
├── LoginView.vue           # Page de connexion
├── RegisterView.vue        # Page d'inscription
├── ProjectsView.vue        # Liste des projets (page d'accueil)
├── ProjectDetailView.vue   # Édition complète de projet
├── FinalPreviewView.vue    # Prévisualisation finale bande rythmo
├── ProfileView.vue         # Profil utilisateur
└── AdminView.vue           # Panel d'administration (admin only)
```

### Composants (19 composants)
```
src/components/
├── InvitationsPanel.vue       # Panneau notifications invitations
├── NotificationToast.vue      # Système de notifications toast
├── VideoUploader.vue          # Upload et gestion vidéos
└── projectDetail/             # 16 composants édition projet
    ├── CharacterModal.vue           # Modal création/édition personnage
    ├── CharactersList.vue           # Liste personnages avec couleurs
    ├── CollaboratorsPanel.vue       # Gestion collaborateurs projet
    ├── ConfirmDeleteModal.vue       # Modal confirmation suppression
    ├── KeyboardShortcutsModal.vue   # Aide raccourcis clavier
    ├── MultiRythmoBand.vue          # Bande rythmo multi-lignes
    ├── PresetsManager.vue           # Gestion presets paramètres
    ├── ProjectSettingsModal.vue     # Modal paramètres projet
    ├── RythmoBandSingle.vue         # Bande rythmo ligne simple
    ├── SceneChangeEditModal.vue     # Modal édition scene change
    ├── SceneChangesList.vue         # Liste changements de plan
    ├── TimecodeModal.vue            # Modal création/édition timecode
    ├── TimecodesListMultiLine.vue   # Liste timecodes multi-lignes
    ├── VideoNavigationBar.vue       # Barre navigation vidéo
    ├── VideoPlayer.vue              # Player vidéo custom
    └── useSmoothScroll.ts           # Composable smooth scroll
```

### Services API (10 fichiers Axios)
```
src/api/
├── axios.ts               # Configuration Axios avec intercepteurs
├── auth.ts                # API authentification
├── admin.ts               # API administration
├── characters.ts          # API personnages
├── collaboration.ts       # API collaborateurs
├── invitations.ts         # API invitations
├── projectSettings.ts     # API paramètres projets
├── sceneChanges.ts        # API changements de plan
├── settingsPresets.ts     # API presets paramètres
└── timecodes.ts           # API timecodes + import SRT
```

### Stores Pinia (3 stores)
```
src/stores/
├── auth.ts                # Store authentification + guards
├── projectSettings.ts     # Store paramètres projet actif
└── counter.ts             # Store exemple (à supprimer)
```

### Composables (2 composables)
```
src/composables/
├── useCollaborativeRefresh.ts  # Rafraîchissement automatique collab
└── useInvitations.ts           # Gestion invitations temps réel
```

### Services (2 services)
```
src/services/
├── googleFonts.ts         # Chargement dynamique Google Fonts
└── notifications.ts       # Service notifications centralisé
```

### Utilitaires (2 utils)
```
src/utils/
├── colorUtils.ts          # Fonctions manipulation couleurs
└── separatorEncoding.ts   # Encodage/décodage séparateurs timecodes
```

### Router (Vue Router 4.5.1)
Configuration avec guards d'authentification :
- `requiresAuth` : routes protégées (redirection vers /login)
- `requiresGuest` : routes invités (redirection vers / si connecté)
- `requiresAdmin` : routes admin (redirection vers / si non-admin)
- Validation token automatique avant chaque route protégée
- Gestion query param `redirect` pour retour après login

## Fonctionnalités complètes implémentées

### ✅ Authentification & Autorisation
- [x] Inscription/Connexion avec email + password
- [x] JWT tokens via Laravel Sanctum
- [x] Gestion profil utilisateur
- [x] Changement de mot de passe
- [x] Rôles admin/user
- [x] Guards de routes frontend
- [x] Middleware backend (IsAdmin)
- [x] Auto-refresh token

### ✅ Gestion de projets
- [x] CRUD complet projets
- [x] Upload vidéos multi-formats
- [x] Streaming vidéo optimisé
- [x] Métadonnées (nom, description)
- [x] Settings projet personnalisables (JSON)
- [x] Nombre de lignes rythmo configurable
- [x] Permissions d'accès (propriétaire/collaborateurs)

### ✅ Timecodes multi-lignes
- [x] CRUD timecodes avec ligne_number
- [x] Synchronisation vidéo temps réel
- [x] Import fichiers SRT avec parsing
- [x] Séparateurs personnalisables dans texte
- [x] Association personnages avec couleurs
- [x] Affichage/masquage nom personnage
- [x] Tri automatique par ligne puis start
- [x] Édition inline

### ✅ Personnages
- [x] CRUD complet personnages
- [x] Couleurs personnalisables (fond + texte)
- [x] Association aux timecodes
- [x] Clonage entre projets
- [x] Prévisualisation couleurs
- [x] Validation contraste texte/fond

### ✅ Changements de plan
- [x] CRUD scene changes
- [x] Indicateurs visuels sur timeline
- [x] Synchronisation avec vidéo
- [x] Édition rapide

### ✅ Collaboration multi-utilisateurs
- [x] Ajout collaborateurs par email
- [x] 3 niveaux permissions (view/edit/admin)
- [x] Invitations avec statut (pending/accepted/declined)
- [x] Expiration invitations (nullable)
- [x] Notifications invitations
- [x] Recherche utilisateurs
- [x] Quitter projet
- [x] Gestion accès temps réel

### ✅ Presets de paramètres
- [x] Création presets (max 5/user)
- [x] Sauvegarde settings projet
- [x] Application rapide preset
- [x] Renommage/suppression presets
- [x] Stockage JSON flexible

### ✅ Administration
- [x] Panel admin complet
- [x] Gestion utilisateurs (CRUD)
- [x] Gestion tous projets
- [x] Changement mdp utilisateurs
- [x] Statistiques globales
- [x] Suppression en cascade

### ✅ Interface utilisateur
- [x] Design responsive (mobile-first)
- [x] Thème sombre moderne
- [x] Notifications toast
- [x] Modales accessibles
- [x] Raccourcis clavier
- [x] Smooth scrolling
- [x] Loading states
- [x] Error handling
- [x] Heroicons integration

### 🚧 En développement actif
- [ ] Export projets complets (JSON)
- [ ] Export vidéo avec bande rythmo incrustée
- [ ] Mode hors ligne (PWA)
- [ ] Websockets pour collaboration temps réel
- [ ] Historique modifications (undo/redo)

## Scripts de développement

### Backend (composer)
```bash
composer install           # Installation dépendances
composer dev              # Lance serveur + queue + logs + vite
composer test             # Tests PHPUnit
php artisan migrate       # Migrations DB
php artisan db:seed       # Seeders
php artisan serve         # Serveur dev seul (port 8000)
php artisan tinker        # REPL Laravel
php artisan pail          # Logs temps réel
```

### Frontend (npm)
```bash
npm install               # Installation dépendances
npm run build             # Build production
npm run preview           # Preview build production
npm run type-check        # Vérification TypeScript
npm run lint              # ESLint + fix auto
npm run format            # Prettier format

# ⚠️ NE JAMAIS UTILISER : npm run dev (utiliser composer dev à la place)
```

## Thème et Design System

### Palette de couleurs Tailwind
```javascript
// tailwind.config.js
colors: {
  // Couleurs classiques AgfaRythmo
  'agfa-dark': '#2d3748',
  'agfa-gray': '#4a5568',
  'agfa-light': '#f7fafc',
  'agfa-blue': '#3182ce',
  'agfa-blue-hover': '#2563eb',
  'agfa-green': '#38a169',
  'agfa-green-hover': '#2f855a',
  'agfa-red': '#e53e3e',
  'agfa-red-hover': '#c53030',
  
  // Thème sombre moderne (nouveau)
  'agfa-bg-primary': '#121827',      // Fond principal (très sombre)
  'agfa-bg-secondary': '#202937',    // Menu/cartes (moins sombre)
  'agfa-bg-tertiary': '#2a3441',     // Cartes surélevées (plus clair)
}

// Animations custom
animation: {
  'fade-in': 'fadeIn 0.3s ease-in-out',
  'slide-up': 'slideUp 0.3s ease-out',
}
```

### Guidelines UI/UX
- **Interface sombre** : dark mode par défaut avec bons contrastes
- **Design épuré** : pas de fioritures, focus sur la fonctionnalité
- **Accessibilité** : ARIA labels, keyboard navigation, focus visible
- **Responsive** : mobile-first, breakpoints Tailwind standards
- **Feedback utilisateur** : toasts, spinners, états disabled
- **Typographie** : Google Fonts chargées dynamiquement
- **Icons** : Heroicons (outline & solid)
- **Espacement** : cohérence avec scale Tailwind (4px base)

## Bonnes pratiques de développement

### Architecture & Patterns

#### Backend Laravel
- **Controllers fins** : déléguer logique métier aux Services/Models
- **Form Requests** : validation centralisée avec messages FR
- **Resources** : transformations API uniformes
- **Policies** : autorisation granulaire (hasAccess, canModify, canAdmin)
- **Eloquent Relations** : lazy/eager loading selon contexte
- **Transactions DB** : pour opérations multi-modèles
- **Queue Jobs** : pour tâches longues (uploads, exports)

#### Frontend Vue/TypeScript
- **Composition API** : `<script setup>` obligatoire
- **TypeScript strict** : typage complet (interfaces, types)
- **Props validation** : withDefaults() + defineProps()
- **Emits déclarés** : defineEmits<{...}>()
- **Composables** : logique réutilisable (use*)
- **Stores Pinia** : état global avec getters/actions
- **Error boundaries** : gestion erreurs composants
- **Lazy loading** : routes + composants lourds

### Code Quality

#### Conventions de nommage
```typescript
// Backend PHP (Laravel)
class ProjectController {}      // PascalCase classes
public function updateSettings() {}  // camelCase méthodes
protected $fillable = [];       // camelCase propriétés
public const MAX_PRESETS = 5;   // SCREAMING_SNAKE_CASE constantes

// Frontend TypeScript
interface ProjectSettings {}    // PascalCase interfaces/types
const projectStore = useProjectStore()  // camelCase variables
export function formatTimecode() {}     // camelCase fonctions
export const API_BASE_URL = ''  // SCREAMING_SNAKE_CASE constantes

// Vue composants
<MyComponent />                 // PascalCase composants
@update:modelValue             // kebab-case events
v-model:selected-items         // kebab-case props multi-mots
```

#### Tests
- **Tests unitaires** : fonctions critiques (parsers, utils, validators)
- **Tests API** : tous endpoints avec Feature tests Laravel
- **Tests composants** : interactions utilisateur complexes
- **Coverage** : minimum 70% sur code métier

### Performance & Optimisation

#### Backend
- **DB Indexes** : sur foreign keys + colonnes de recherche
- **Eager Loading** : `with()` pour éviter N+1 queries
- **Pagination** : toutes les listes (15-25 items/page)
- **Cache** : routes API fréquentes (config, stats)
- **Streaming** : vidéos avec Content-Range headers
- **Validation** : côté serveur TOUJOURS (pas de confiance client)

#### Frontend
- **Code splitting** : routes lazy-loaded
- **Debouncing** : recherches, autosave (300-500ms)
- **Throttling** : scroll events, resize (100ms)
- **Virtual scrolling** : listes > 100 items
- **Image optimization** : formats modernes (webp, avif)
- **Bundle size** : < 500KB initial, < 2MB total
- **Tree shaking** : imports nommés, pas de barrel exports

### Sécurité

#### Backend
- **SQL Injection** : Eloquent ORM (parameterized queries)
- **XSS** : échappement automatique Blade (manuel si JSON)
- **CSRF** : tokens Sanctum + SameSite cookies
- **CORS** : config stricte (origins whitelistées)
- **Rate limiting** : 60 req/min par défaut, 10/min auth
- **Password hashing** : bcrypt (Laravel default)
- **Sensitive data** : jamais dans logs/responses

#### Frontend
- **Token storage** : localStorage (rotation régulière)
- **XSS prevention** : v-html JAMAIS avec user input
- **HTTPS only** : redirect automatique en prod
- **Content Security Policy** : headers restrictifs
- **Secrets** : variables d'environnement (VITE_*)

## Services & Intégrations

### SrtParser Service
Parser robuste de fichiers SRT :
- **Format** : `HH:MM:SS,mmm --> HH:MM:SS,mmm`
- **Normalisation** : fins de ligne multi-OS
- **Validation** : regex strict + exception si invalide
- **Conversion** : timecode SRT → float secondes (3 décimales)
- **Nettoyage** : strip HTML tags, trim whitespace
- **Multi-lignes** : support texte sur plusieurs lignes

### Google Fonts Service
Chargement dynamique de polices :
- **API** : Google Fonts API
- **Cache** : localStorage pour éviter rechargements
- **Fallbacks** : fonts système si échec
- **Performance** : chargement asynchrone

### Notifications Service
Système centralisé de notifications :
- **Types** : success, error, warning, info
- **Position** : top-right par défaut
- **Durée** : 3-5s selon type
- **Queue** : max 3 notifications simultanées
- **Animations** : fade-in + slide-up

## Déploiement & Environnement

### Environnement de développement
- **OS** : macOS, Linux, Windows (WSL2 recommandé)
- **PHP** : 8.2+ avec extensions (sqlite, mbstring, openssl)
- **Node.js** : 20.19+ ou 22.12+
- **SQLite** : 3.35+ (inclus macOS/Linux)
- **Git** : version control + GitHub

### Cibles de déploiement
- **Développement** : VSCode + GitHub Copilot + Laravel Valet/Herd
- **Staging** : VPS Linux + Nginx + SQLite
- **Production** : 
  - Backend : Laravel Forge + DigitalOcean/Linode
  - Frontend : Vercel/Netlify (SPA statique)
  - Base : SQLite (< 100 users) ou PostgreSQL (scale)
- **Desktop** : Futur portage Electron prévu
- **Mobile** : Responsive web, possible PWA

## Notes critiques pour GitHub Copilot

### ⚠️ À VÉRIFIER SYSTÉMATIQUEMENT

1. **Endpoints API existants** : TOUJOURS vérifier dans `routes/api.php` avant création
2. **Modèles Laravel** : respecter $fillable, $casts, relations existantes
3. **TypeScript strict** : jamais de `any`, toujours typer props/emits/stores
4. **Permissions** : vérifier hasAccess(), canModify(), canAdmin() pour chaque action
5. **Migrations** : JAMAIS modifier migration déjà migrée, créer nouvelle migration
6. **Tests** : exécuter après chaque modification importante
7. **Git** : commits atomiques avec messages conventionnels

### 🚀 Workflow de développement recommandé

1. **Lancer environnement** : `composer dev` dans backend
2. **Créer branche** : `git checkout -b feature/ma-fonctionnalite`
3. **Développer** : backend → API → frontend → tests
4. **Vérifier types** : `npm run type-check` dans frontend
5. **Tester API** : Postman/Insomnia ou Feature tests
6. **Tester UI** : navigateur + Vue DevTools
7. **Lint** : `npm run lint` + `./vendor/bin/pint`
8. **Commit** : messages clairs (feat/fix/refactor)
9. **Push** : `git push origin feature/ma-fonctionnalite`
10. **PR** : description complète + screenshots si UI

### 🎯 Priorités de développement

1. **Stabilité** > nouvelles features
2. **Sécurité** > performance
3. **UX** > esthétique
4. **Code lisible** > code clever
5. **Documentation** > code auto-documenté (mais les deux c'est mieux)

### 📚 Ressources importantes

- **Laravel Docs** : https://laravel.com/docs/12.x
- **Vue 3 Docs** : https://vuejs.org/guide/
- **Pinia Docs** : https://pinia.vuejs.org/
- **Tailwind Docs** : https://tailwindcss.com/docs
- **TypeScript Handbook** : https://www.typescriptlang.org/docs/

---

**Dernière mise à jour** : 17 octobre 2025
**Version du projet** : 2.0.0-beta
**Maintainer** : Martin P. (@ParizetM)