---
applyTo: '**'
---

# AgfaRythmo - Instructions GitHub Copilot

**Version** : 2.2.1-beta | **Mise à jour** : 23 novembre 2025

## 🚨 Règles Strictes

### ⛔ INTERDICTIONS
- **JAMAIS** `npm run dev` dans frontend (utiliser `composer dev` dans backend)
- **JAMAIS** modifier migrations déjà exécutées
- **JAMAIS** créer routes API sans vérifier `routes/api.php`

### ✅ OBLIGATOIRES
- Vue : `<script setup>` + TypeScript strict
- Laravel : FormRequest pour validation, PascalCase classes, camelCase méthodes
- Tester avant commit
- **UI/UX** : JAMAIS utiliser `alert()` ou `confirm()` natifs → utiliser `ConfirmModal.vue` ou `BaseModal.vue` pour dialogues modernes

## 📋 Contexte Projet

Application web pour génération de bandes rythmo (doublage vidéo) :
- Streaming vidéo + timecodes multi-lignes synchronisés
- Personnages avec couleurs + scene changes
- Collaboration multi-utilisateurs (view/edit/admin)
- Import/Export projets format .agfa crypté
- Administration complète + mode maintenance

## 🏗️ Stack Technique

**Backend** : Laravel 12.0 + PHP 8.2 + SQLite + Sanctum 4.0
**Frontend** : Vue 3.5 + TypeScript 5.8 + Pinia + Vite 7 + Tailwind 4
**Dev** : `composer dev` (serveur + queue + logs + vite)

## 📂 Structure Clé

```
agfa-rythmo-backend/
├── app/Models/                    # 8 modèles
│   ├── User, Project, Timecode, Character
│   ├── SceneChange, SettingsPreset
│   └── ProjectCollaborator, ProjectInvitation
├── app/Http/Controllers/Api/      # 6 controllers API
├── routes/api.php                 # 56 routes REST
└── database/migrations/           # 22 migrations

agfa-rythmo-frontend/
├── src/views/                     # 8 vues (Login, Projects, ProjectDetail, Admin, Profile, FinalPreview, Register, Maintenance)
├── src/components/                # 25 composants (BaseModal, CreateProjectModal, VideoPlayer, RythmoBand...)
├── src/api/                       # 11 services (projects, auth, timecodes, characters...)
├── src/stores/                    # 2 stores Pinia (auth, projectSettings)
└── src/composables/               # 2 composables (useInvitations, useCollaborativeRefresh)
```

## 🗄️ Modèles Principaux

### User
- Rôles : `admin` | `user`
- Relations : projects(), collaborativeProjects(), sentInvitations(), receivedInvitations()

### Project
- `$fillable` : name, description, video_path, rythmo_lines_count (1-10), project_settings (JSON), user_id
- Relations : owner(), collaborators(pivot: permission), timecodes(), characters(), sceneChanges(), invitations()
- Méthodes permissions : hasAccess(), canModify(), canAdmin()

### Timecode (multi-lignes)
- `$fillable` : project_id, line_number, start, end, text, character_id, show_character, separator_positions
- Relations : project(), character()

### Character
- `$fillable` : project_id, name, color (hex), text_color (hex)

### SceneChange
- `$fillable` : project_id, timecode (float secondes)

### SettingsPreset
- Max 5 par utilisateur
- `$fillable` : user_id, name, settings (JSON)

### ProjectInvitation
- Status : `pending` | `accepted` | `declined` | `cancelled`
- Permission : `view` | `edit` | `admin`

## 🌐 API REST (56 routes)

**Vérifier toujours `routes/api.php` avant de créer une nouvelle route !**

### Routes clés
- Auth : `/api/auth/*` (register, login, logout, profile, change-password)
- Projects : `/api/projects/*` (CRUD + import/export + settings + rythmo-lines)
- Timecodes : `/api/projects/{project}/timecodes/*` (CRUD + import-srt + getByLine)
- Characters : `/api/characters/*` (CRUD + clone + for-cloning)
- SceneChanges : `/api/projects/{project}/scene-changes/*` + `/api/scene-changes/{id}`
- Collaboration : `/api/projects/{project}/collaborators/*` + search-users + leave
- Invitations : `/api/invitations/*` (CRUD + accept/decline)
- Presets : `/api/settings-presets/*` (max 5/user)
- Admin : `/api/admin/*` (users, projects, stats avec taille vidéos)
- Videos : `/api/videos/*` (upload, stream public)
- Maintenance : détecté via `storage/framework/maintenance` → 503

## 🎨 Frontend Architecture

**8 vues** : Login, Register, Projects, ProjectDetail, FinalPreview, Profile, Admin, Maintenance
**25 composants** : BaseModal, modales projets/import, VideoPlayer, RythmoBand, listes (timecodes, characters, scene changes, collaborators), etc.
**11 services API** : axios (config + maintenance), auth, admin, projects (import/export), timecodes, characters, collaboration, invitations, presets, sceneChanges, projectSettings
**2 stores Pinia** : auth (validation token), projectSettings
**2 composables** : useInvitations, useCollaborativeRefresh
**2 services** : googleFonts (cache localStorage), notifications
**2 utils** : colorUtils, separatorEncoding
**Router** : guards auth (requiresAuth, requiresGuest, requiresAdmin)

## ✅ Fonctionnalités Implémentées

**Auth** : Inscription/Connexion, JWT Sanctum, profil, changement mdp, rôles admin/user, guards routes
**Projets** : CRUD, upload vidéos, streaming optimisé, settings JSON, lignes rythmo 1-10, permissions, **import/export .agfa**
**Timecodes** : CRUD multi-lignes, sync vidéo, import SRT, séparateurs, association personnages
**Personnages** : CRUD, couleurs fond+texte, clonage entre projets
**Scene Changes** : CRUD, indicateurs timeline, sync vidéo
**Collaboration** : Permissions (view/edit/admin), invitations, recherche users, quitter projet
**Presets** : Max 5/user, sauvegarde/application settings
**Admin** : CRUD users/projects, stats globales, **taille vidéos**, suppression cascade
**UI/UX** : Responsive dark, **menu mobile**, **BaseModal**, toast, **glassmorphism**, raccourcis clavier, **Google Fonts cache**, **GPU mobile**, **playsinline iOS**
**Mode Maintenance** : Fichier backend → 503 → redirection frontend `/maintenance`

## Nouvelles fonctionnalités (Octobre 2025)

### 🎯 Import/Export de projets (.agfa)
**Format propriétaire crypté pour sauvegarder et partager des projets complets**

#### Fonctionnalités d'export :
- Export complet du projet au format `.agfa` (JSON crypté)
- Inclut : métadonnées, timecodes, personnages, scene changes, settings
- **Exclut la vidéo** (trop volumineuse)
- Endpoint : `GET /api/projects/{id}/export`
- Frontend : bouton "Exporter" dans ProjectDetailView

#### Fonctionnalités d'import :
- **2 modes d'import** :
  1. **Import avec vidéo** : fichier .agfa + vidéo à uploader
  2. **Import sans vidéo** : fichier .agfa seul (pour tests/templates)
- Validation du format de fichier
- Recréation complète des données (timecodes, personnages, etc.)
- Endpoint : `POST /api/projects/import`
- Frontend : modales `ProjectImportModal` et `ProjectImportWithVideoModal`
- Accessible depuis bouton "Importer un projet" dans ProjectsView

#### Structure du fichier .agfa :
```json
{
  "export_version": "1.0",
  "export_date": "2025-10-27T...",
  "project": {
    "name": "...",
    "description": "...",
    "rythmo_lines_count": 2,
    "project_settings": {...}
  },
  "timecodes": [...],
  "characters": [...],
  "scene_changes": [...]
}
```

### 🔧 Mode Maintenance Global
**Système de maintenance simplifié pour arrêter temporairement l'application**

#### Backend :
- Détection fichier `storage/framework/maintenance`
- Si existe → toutes les requêtes API retournent 503 Service Unavailable
- Middleware `CheckForMaintenanceMode` dans `bootstrap/app.php`
- **Activation** : renommer `RENAME_TO_maintenance_TO_ENABLE` → `maintenance`
- **Désactivation** : renommer `maintenance` → `RENAME_TO_maintenance_TO_ENABLE`
- Aucun redémarrage nécessaire

#### Frontend :
- Intercepteur Axios détecte code 503
- Redirection automatique vers `/maintenance`
- Vue `MaintenanceView` avec message élégant
- Route publique (accessible sans authentification)

#### Guide :
- Documentation complète : `MAINTENANCE_GUIDE.md`
- Méthode FTP recommandée (simple et instantanée)
- Pas de cache, changement immédiat

### 📊 Statistiques Vidéos (Admin)
**Monitoring de l'espace disque utilisé par les vidéos**

#### Backend :
- Calcul automatique taille fichiers vidéo
- Ajout dans endpoint `GET /api/admin/stats` :
  - `total_videos_size` : espace total en octets
  - `total_videos_size_mb` : espace en MB (arrondi 2 décimales)
- Calcul taille par projet dans `GET /api/admin/projects`
- Utilise `filesize()` PHP sur `video_path`

#### Frontend :
- Affichage dans AdminView :
  - Carte "Espace vidéos" avec taille totale en MB/GB
  - Colonne "Taille vidéo" dans tableau projets
- Formatage automatique (B/KB/MB/GB selon taille)
- Types TypeScript mis à jour dans `api/admin.ts`

### 🎨 Google Fonts Dynamiques
**Chargement intelligent des polices Google pour preview**

#### Service `googleFonts.ts` :
- Fonction `loadGoogleFont(fontFamily: string)`
- Chargement asynchrone via Google Fonts API
- **Cache localStorage** pour éviter rechargements
- Clé cache : `google-font-${fontFamily}`
- Fallback vers fonts système si échec
- Préchargement via `<link rel="preconnect">`
- Utilisé dans ProjectSettingsModal et RythmoBand

### 📱 Optimisations Mobile
**Améliorations UX pour appareils mobiles et tactiles**

#### Menu navigation responsive :
- Menu hamburger pour mobile (< 768px)
- Transitions fluides avec Tailwind
- Icônes Heroicons (Bars3Icon, XMarkIcon)
- État actif sur routes
- Composant dans App.vue

#### Optimisations vidéo mobile :
- **Attribut `playsinline`** : lecture inline sur iOS (pas de fullscreen forcé)
- **GPU optimizations** : 
  - Hardware acceleration avec `transform: translateZ(0)`
  - Buffering amélioré
  - Smooth scrolling optimisé
- Composants : VideoPlayer.vue, RythmoBandSingle.vue

#### Navigation vidéo améliorée :
- Seek par scroll horizontal sur bande rythmo
- Frame offset compensation pour précision
- Navigation scene/timecode avec boutons tactiles
- Boutons larges et espacés pour mobile
- VideoNavigationBar responsive

### 🎨 UI/UX Améliorations
**Design moderne et cohérent**

#### ConfirmModal.vue :
**Composant moderne pour confirmations utilisateur (remplace `confirm()` natif)**
- **Props** :
  - `show` (boolean) : affichage de la modal
  - `title` (string) : titre de la confirmation
  - `message` (string) : message principal
  - `details?` (string) : détails optionnels
  - `confirmText?` (string) : texte bouton confirmer (défaut: "Confirmer")
  - `cancelText?` (string) : texte bouton annuler (défaut: "Annuler")
  - `type?` ('danger' | 'warning' | 'info') : type de confirmation (défaut: 'warning')
- **Événements** :
  - `@confirm` : émis lors de la confirmation
  - `@cancel` : émis lors de l'annulation
  - `@update:show` : pour v-model:show
- **Utilisation** :
```vue
<ConfirmModal
  v-model:show="showConfirm"
  title="Supprimer ce projet ?"
  message="Cette action est irréversible."
  type="danger"
  @confirm="handleDelete"
/>
```

#### Glassmorphism :
- Panneaux latéraux avec effet verre (backdrop-filter: blur)
- Bordures subtiles et ombres douces
- Transparence contrôlée (bg-opacity)
- Composants : CollaboratorsPanel, SceneChangesList, CharactersList

#### Modales modernes :
- Composant `BaseModal.vue` réutilisable
- Props : modelValue, title, maxWidth, showCloseButton
- Slots : default (body), footer (actions)
- Fermeture : Escape, clic extérieur, bouton X
- Transitions fluides avec Tailwind
- Utilisé dans : CreateProjectModal, EditProjectModal, DeleteProjectModal
- **Titres longs** : utilisent `break-words` au lieu de `truncate` pour affichage complet sur plusieurs lignes

#### UI compacte :
- Espacements optimisés (reduced padding/margin)
- Composants plus denses pour mobile
- Sélecteurs de ligne dans timecode list
- Amélioration typographie et contrastes
- Refactorisation ProjectDetailView pour lisibilité

### 🔐 Sécurité Authentification
**Renforcement de la validation des sessions**

#### Améliorations :
- Validation token avant chaque route protégée
- Vérification expiration token côté client
- Auto-refresh token si proche expiration
- Déconnexion automatique si token invalide
- Meilleure gestion erreurs 401/403
- Store Pinia `auth.ts` amélioré

### ⌨️ Améliorations Raccourcis Clavier
**Désactivation contextuelle pour éviter conflits**

#### Système intelligent :
- Détection focus sur inputs/textareas
- Désactivation raccourcis pendant édition texte
- Évite conflits avec saisie utilisateur
- Raccourcis restent actifs hors édition
- Implémenté dans ProjectDetailView

### 🎬 Prévisualisation Finale
**Passage des scene changes à la vue finale**

### 🔍 Menu IA avec Configuration Simple
**Interface unifiée pour toutes les fonctionnalités IA avec configuration via .env**

#### Objectif :
- Menu centralisé pour toutes les fonctionnalités IA
- **Configuration simple** via fichier `.env` (pas de détection automatique)
- Interface adaptée selon les fonctionnalités activées
- Extensible pour futures fonctionnalités IA

#### Configuration Backend :
- Fichier `config/ai.php` : définit les fonctionnalités disponibles
- Variables `.env` :
  - `AI_SCENE_DETECTION_ENABLED=true/false` : détection changements de plan
  - `AI_AUTO_SUBTITLES_ENABLED=true/false` : sous-titrage auto (futur)
  - `AI_VOICE_RECOGNITION_ENABLED=true/false` : reconnaissance vocale (futur)
  - `AI_AUDIO_ANALYSIS_ENABLED=true/false` : analyse audio (futur)
- Service `ServerCapabilities.php` : lit la config et l'expose via API
- Endpoint public `GET /api/server/capabilities` : retourne config JSON

#### Frontend :
- **Composant `AiMenuModal.vue`** : interface principale
  - **Pas d'affichage d'état technique** (FFmpeg, workers, etc.)
  - Cartes pour chaque fonctionnalité activée
  - Bouton "Lancer" avec gradient violet/rose
  - Badge "Déjà analysé" si déjà fait
  - Message "Aucune fonctionnalité IA activée" si tout désactivé
  - Placeholder "Futures fonctionnalités à venir..."
- Service API `serverCapabilities.ts` : interface TypeScript
- Composable `useServerCapabilities.ts` : gestion état
- Chargement au démarrage dans `App.vue` via `onMounted`
- Utilisation dans `ProjectDetailView.vue` :
  - Bouton "IA" toujours visible (gradient violet/rose)
  - Clic ouvre `AiMenuModal` avec fonctionnalités activées
  - Modal ferme automatiquement en lançant analyse

#### UX :
- **Bouton IA** : Toujours visible, design moderne
- **Modal IA** :
  - Simple et épurée
  - Pas d'info technique sur FFmpeg/workers
  - Juste les fonctionnalités disponibles
  - Footer avec bouton "Fermer"
- **Avec fonctionnalités** : Cartes interactives, boutons actifs
- **Sans fonctionnalités** : Message informatif

#### Documentation :
- Guide complet : `AI_CONFIG.md`
- Configuration `.env.example` documentée
- Instructions ajout nouvelles fonctionnalités

### 🎤 Extraction Automatique de Dialogues (IA)
**Transcription automatique des dialogues avec détection des locuteurs**

#### Objectif :
- Extraire automatiquement les dialogues parlés d'une vidéo
- Créer les timecodes et personnages automatiquement
- Détection intelligente des différents speakers (diarization)
- Support multi-langue (12 langues : fr, en, zh, ja, es, de, it, pt, ru, ko, ar, hi)

#### Architecture Backend :
- **Migration** : `2025_10_31_120000_add_dialogue_extraction_to_projects_table.php`
  - Ajoute `dialogue_extraction_status`, `dialogue_extraction_progress`, `dialogue_extraction_message` à table projects
- **Script Python** : `scripts/extract_dialogues.py`
  - **Whisper** (OpenAI) : Transcription multi-langue avec 3 modèles (tiny/base/small)
  - **pyannote-audio** : Diarization pour détection des locuteurs
  - **FFmpeg** : Extraction audio de la vidéo (WAV 16kHz mono)
  - Output JSON avec timecodes + speakers + metadata
  - Optimisé 2GB RAM (model tiny, gc.collect(), model unloading)
- **Job** : `app/Jobs/ExtractDialogues.php`
  - Appelle script Python avec proc_open()
  - Progression temps réel (4 étapes) :
    - 0-20% : Extraction audio
    - 20-70% : Transcription Whisper
    - 70-90% : Diarization
    - 90-100% : Création timecodes + personnages
  - Vérification cancellation toutes les 2s
  - **Rollback automatique** si échec (supprime timecodes/characters créés)
  - **Auto-création personnages** : palette 10 couleurs distinctives
  - **Distribution speakers** : sur lignes rythmo si plusieurs lignes
  - Timeout 30 minutes
- **Controller** : `app/Http/Controllers/Api/DialogueExtractionController.php`
  - `startExtraction()` : Valide pas de timecodes existants, dispatch Job
  - `getStatus()` : Retourne statut/progression/message
  - `cancelExtraction()` : Annule Job en cours
- **Routes** : 3 routes dans `routes/api.php`
  - `POST /projects/{project}/dialogue-extraction/start`
  - `GET /projects/{project}/dialogue-extraction/status`
  - `POST /projects/{project}/dialogue-extraction/cancel`

#### Configuration `.env` :
```bash
AI_DIALOGUE_EXTRACTION_ENABLED=true
WHISPER_MODEL=tiny              # tiny/base/small
DIARIZATION_ENABLED=true
MAX_SPEAKERS=10
SUPPORTED_LANGUAGES=fr,en,zh,ja,es,de,it,pt,ru,ko,ar,hi
```

#### Architecture Frontend :
- **Service API** : `src/api/dialogueExtraction.ts`
  - Types : `DialogueExtractionOptions`, `DialogueExtractionStatus`
  - Fonctions : `startDialogueExtraction()`, `getDialogueExtractionStatus()`, `cancelDialogueExtraction()`
- **ServerCapabilities** : ajout `dialogue_extraction: boolean`
- **Composant `DialogueExtractionModal.vue`** :
  - Sélecteur langue (12 langues)
  - Slider max speakers (2-20)
  - Dropdown modèle Whisper (tiny/base/small)
  - Warning auto-création timecodes/personnages
  - Émit événement `@start` avec options
- **Composant `DialogueExtractionProgress.vue`** :
  - Visualisation 4 étapes avec progression
  - Polling status toutes les 2s
  - Statistiques (timecodes count, characters count)
  - Bouton annulation
  - Événements : `@completed`, `@failed`, `@cancelled`
- **Intégration `ProjectDetailView.vue`** :
  - `hasTimecodes` computed : vérifie si timecodes existent
  - 5 handlers :
    - `handleStartDialogueExtraction()` : ouvre modal settings
    - `handleDialogueExtractionStart()` : lance extraction via API
    - `handleDialogueExtractionCompleted()` : recharge timecodes/characters
    - `handleDialogueExtractionFailed()` : affiche erreur
    - `handleDialogueExtractionCancelled()` : notification annulation
  - Refresh automatique après extraction réussie
- **Mise à jour `AiMenuModal.vue`** :
  - Carte "Extraction de dialogues" (gradient blue/violet, icône microphone)
  - Désactivée si `hasTimecodes === true` (inverse de scene detection)
  - Message warning si déjà des timecodes
  - Émit événement `@start-dialogue-extraction`

#### UX :
- **Condition** : Désactivé si projet a déjà des timecodes (inverse de détection scène)
- **Workflow** :
  1. Clic bouton "IA" → ouvre AiMenuModal
  2. Clic "Extraction de dialogues" → ouvre DialogueExtractionModal
  3. Configuration (langue/speakers/model) → clic "Lancer"
  4. DialogueExtractionProgress s'affiche avec progression temps réel
  5. À la fin : timecodes + personnages créés automatiquement
- **Fallbacks** :
  - Si diarization échoue → single speaker utilisé
  - Si échec total → rollback automatique
- **Performances** : 2-10 minutes selon modèle et durée vidéo

#### Palette couleurs personnages auto-créés :
1. Speaker 1 : `#3b82f6` (Bleu)
2. Speaker 2 : `#ef4444` (Rouge)
3. Speaker 3 : `#10b981` (Vert)
4. Speaker 4 : `#f59e0b` (Orange)
5. Speaker 5 : `#8b5cf6` (Violet)
6. Speaker 6 : `#ec4899` (Rose)
7. Speaker 7 : `#14b8a6` (Teal)
8. Speaker 8 : `#f97316` (Orange vif)
9. Speaker 9 : `#6366f1` (Indigo)
10. Speaker 10+ : `#64748b` (Gris)

#### Documentation :
- Guide complet : `DIALOGUE_EXTRACTION_GUIDE.md`
- Installation dépendances : `pip install -r scripts/requirements.txt`
- Configuration `.env` détaillée
- Troubleshooting (FFmpeg, RAM, modèles)

#### Futures améliorations (Phase 2) :
- Traduction automatique avec contexte
- UI validation/édition post-extraction
- Fusion speakers mal identifiés
- Support GPU (CUDA) pour accélération


## Scripts de développement

### Backend (composer)
```bash
composer install           # Installation dépendances
php artisan migrate       # Migrations DB
php artisan db:seed       # Seeders
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

# ⚠️ NE JAMAIS UTILISER : npm run dev
```

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

## Notes critiques pour GitHub Copilot

### ⚠️ À VÉRIFIER SYSTÉMATIQUEMENT

1. **Endpoints API existants** : TOUJOURS vérifier dans `routes/api.php` avant création
2. **Modèles Laravel** : respecter $fillable, $casts, relations existantes
3. **TypeScript strict** : jamais de `any`, toujours typer props/emits/stores
4. **Permissions** : vérifier hasAccess(), canModify(), canAdmin() pour chaque action
5. **Migrations** : JAMAIS modifier migration déjà migrée, créer nouvelle migration
6. **Tests** : exécuter après chaque modification importante

### 🚀 Workflow de développement recommandé

4. **Vérifier types** : `npm run type-check` dans frontend
7. **Lint** : `npm run lint` + `./vendor/bin/pint`
8. **Commit** : messages clairs (feat/fix/refactor)

---

**Dernière mise à jour** : 23 novembre 2025
**Version du projet** : 2.2.1-beta
**Maintainer** : Martin P. (@ParizetM)

---

## 📝 Changelog récent

### v2.2.1-beta (23 novembre 2025)
- ✅ **Support tactile complet** : Touch events pour toutes les interactions sur mobile
- ✅ **Scroll horizontal tactile** : Swipe pour naviguer dans la timeline
- ✅ **Zones de contrôle agrandies** : Handles resize/move/separator 33-100% plus larges sur mobile
- ✅ **Scene changes tactiles** : Déplacement au doigt avec grab handle toujours visible
- ✅ **Composable useTouchAndMouse** : Gestion unifiée mouse/touch réutilisable
- ✅ **Media queries responsive** : Adaptation automatique desktop/mobile (< 768px)
- ✅ **Feedback visuel mobile** : Handles toujours visibles (pas besoin de hover)
- ✅ **Cleanup automatique** : Gestion correcte des listeners touch/mouse
- ✅ **Documentation complète** : Guide `MOBILE_TOUCH_IMPROVEMENTS.md`

### v2.2.0-beta (31 octobre 2025)
- ✅ **Extraction automatique de dialogues (IA)** : Transcription multi-langue avec Whisper
- ✅ **Détection locuteurs** : Diarization automatique avec pyannote-audio
- ✅ **Support 12 langues** : fr, en, zh, ja, es, de, it, pt, ru, ko, ar, hi
- ✅ **Auto-création personnages** : Palette 10 couleurs + distribution sur lignes rythmo
- ✅ **Progression temps réel** : 4 étapes (audio/whisper/diarization/timecodes)
- ✅ **Optimisé 2GB RAM** : Modèle Whisper tiny, gestion mémoire intelligente
- ✅ **Rollback automatique** : Suppression timecodes/personnages si échec
- ✅ **Traduction automatique (IA)** : 4 providers (DeepL ⭐⭐⭐⭐⭐, Google ⭐⭐⭐⭐, MyMemory, LibreTranslate)
- ✅ **Support multi-langues** : 30+ langues selon provider
- ✅ **Contexte personnages** : Amélioration qualité traduction
- ✅ **Auto-détection langue** : Source auto ou manuelle
- ✅ **Progression temps réel** : Polling, stats, annulation
- ✅ **UI complète** : Modales configuration + progression avec polling
- ✅ **Documentation** : Guides complets (`DIALOGUE_EXTRACTION_GUIDE.md`, `TRANSLATION_GUIDE.md`, `TRANSLATION_API_KEYS.md`)

### v2.1.1-beta (28 octobre 2025)
- ✅ **Menu IA unifié** : Interface centralisée pour toutes les fonctionnalités IA
- ✅ **Détection capacités serveur** : Auto-détection FFmpeg et workers avec statut visuel
- ✅ **Support serveurs basiques** : Fonctionne sur hébergement PHP-only sans FFmpeg
- ✅ **Extensibilité IA** : Architecture prête pour futures fonctionnalités (sous-titrage, etc.)
- ✅ **UX améliorée** : Badges colorés, tooltips explicatifs, messages d'avertissement clairs

### v2.1.0-beta (27 octobre 2025)
- ✅ Import/Export projets format .agfa crypté
- ✅ Mode maintenance global (backend + frontend)
- ✅ Statistiques taille vidéos dans admin
- ✅ Chargement dynamique Google Fonts avec cache
- ✅ Menu navigation mobile responsive
- ✅ Optimisations GPU et vidéo mobile (playsinline)
- ✅ Modales modernes avec BaseModal
- ✅ UI glassmorphism et design amélioré
- ✅ Validation session authentification renforcée
- ✅ Désactivation raccourcis clavier contextuelle
- ✅ Navigation scene changes en preview finale

### v2.0.0-beta (octobre 2025)
- ✅ Architecture complète Laravel 12 + Vue 3 + TypeScript
- ✅ Système authentification Sanctum
- ✅ Gestion projets collaborative
- ✅ Timecodes multi-lignes (1-10 lignes)
- ✅ Personnages avec couleurs personnalisables
- ✅ Scene changes sur timeline
- ✅ Import fichiers SRT
- ✅ Presets paramètres utilisateur
- ✅ Panel administration complet
- ✅ Interface responsive dark mode