# Détection Automatique de Changements de Plan (IA)

**Version** : 2.1.0-beta | **Date** : 27 octobre 2025

## 📋 Vue d'ensemble

Nouvelle fonctionnalité permettant la détection automatique des changements de plan (cuts) dans une vidéo en utilisant FFmpeg. Cette analyse est effectuée en arrière-plan via un système de queue Laravel pour éviter les timeouts.

## 🎯 Fonctionnalités

### Interface Utilisateur

- **Bouton IA** dans le header de `ProjectDetailView`
- Activé uniquement si :
  - Le projet possède une vidéo
  - L'utilisateur a les droits de modification
  - **Aucun changement de plan n'existe déjà**
- Design gradient violet-rose avec effet hover
- Icône ampoule avec animation pulse pendant l'analyse

### Flux de traitement

1. **Déclenchement** : Clic sur le bouton "IA"
2. **Blocage UI** : Modal bloquant s'affiche immédiatement
3. **Traitement backend** : Job Laravel asynchrone lance FFmpeg
4. **Polling** : L'interface vérifie le statut toutes les 2 secondes
5. **Résultat** : Déblocage + affichage des nouveaux scene changes

## 🏗️ Architecture Backend

### Migration

```bash
php artisan migrate
```

Fichier : `2025_10_27_210934_add_analysis_status_to_projects_table.php`

Ajoute le champ `analysis_status` au modèle `Project` :
- Valeurs : `none`, `pending`, `processing`, `completed`, `failed`
- Default : `none`

### Job Laravel

**Fichier** : `app/Jobs/DetectSceneChanges.php`

**Fonctionnement** :
1. Met le statut à `processing`
2. Récupère le chemin de la vidéo depuis Storage
3. Exécute FFmpeg avec le filtre scene detection (threshold 0.4)
4. Parse les timecodes détectés
5. Crée les `SceneChange` dans la base de données
6. Met le statut à `completed` ou `failed`

**Commande FFmpeg** :
```bash
ffmpeg -i video.mp4 \
  -vf "select='gt(scene,0.4)',showinfo" \
  -f null - 2>&1 | \
  grep "Parsed_showinfo" | \
  grep "pts_time" | \
  sed 's/.*pts_time:\([0-9.]*\).*/\1/'
```

### Controller API

**Fichier** : `app/Http/Controllers/Api/SceneAnalysisController.php`

**Endpoints** :

#### POST `/api/projects/{project}/analyze-scenes`
Lance l'analyse automatique

**Vérifications** :
- Permission de modification
- Absence de scene changes existants
- Présence d'une vidéo
- Pas d'analyse déjà en cours

**Réponse** :
```json
{
  "message": "Analyse lancée avec succès",
  "analysis_status": "pending"
}
```

#### GET `/api/projects/{project}/analysis-status`
Récupère le statut de l'analyse

**Réponse** :
```json
{
  "analysis_status": "processing",
  "scene_changes_count": 0
}
```

### Routes API

Ajoutées dans `routes/api.php` :
```php
Route::post('/projects/{project}/analyze-scenes', [SceneAnalysisController::class, 'startAnalysis']);
Route::get('/projects/{project}/analysis-status', [SceneAnalysisController::class, 'getStatus']);
```

## 🎨 Architecture Frontend

### Service API

**Fichier** : `src/api/sceneAnalysis.ts`

```typescript
export interface AnalysisStatus {
  analysis_status: 'none' | 'pending' | 'processing' | 'completed' | 'failed'
  scene_changes_count: number
}

export async function startSceneAnalysis(projectId: number)
export async function getAnalysisStatus(projectId: number)
```

### Composant Modal

**Fichier** : `src/components/SceneAnalysisModal.vue`

**Caractéristiques** :
- Basé sur `BaseModal`
- Non fermable (pas de croix, pas de clic extérieur, pas d'Escape)
- Spinner animé avec anneaux rotatifs
- Barre de progression indéterminée
- Message dynamique selon le statut
- Option pour afficher les détails techniques

**Props** :
```typescript
{
  show: boolean
  status: 'pending' | 'processing' | 'completed' | 'failed'
  showDetails?: boolean
}
```

### Intégration ProjectDetailView

**États ajoutés** :
```typescript
const isAnalyzing = ref(false)
const showAnalysisModal = ref(false)
const analysisStatus = ref<'none' | 'pending' | 'processing' | 'completed' | 'failed'>('none')
let analysisPollingInterval: number | null = null
```

**Computed** :
```typescript
const hasSceneChanges = computed(() => sceneChanges.value.length > 0)
```

**Fonctions** :
- `handleStartAnalysis()` : Lance l'analyse
- `startAnalysisPolling()` : Démarre le polling (2s)
- `stopAnalysisPolling()` : Arrête le polling
- Nettoyage dans `onUnmounted()`

## 🚀 Utilisation

### Prérequis

1. **FFmpeg installé** sur le serveur
2. **Queue worker** Laravel actif :
   ```bash
   php artisan queue:work
   ```
   Ou via `composer dev` qui inclut déjà le queue worker

### Workflow utilisateur

1. Ouvrir un projet avec une vidéo
2. S'assurer qu'aucun changement de plan n'existe
3. Cliquer sur le bouton "IA" (icône ampoule)
4. Attendre la fin de l'analyse (modal bloquant)
5. Les scene changes apparaissent automatiquement

### Gestion d'erreurs

**Si le bouton est désactivé** :
- Vérifier qu'il n'y a pas déjà des scene changes
- Vérifier les permissions (edit/admin)
- Vérifier qu'une vidéo est bien associée

**Si l'analyse échoue** :
- Vérifier les logs Laravel : `storage/logs/laravel.log`
- Vérifier que FFmpeg est installé : `which ffmpeg`
- Vérifier les permissions du dossier vidéo
- Le statut passera à `failed` et un message d'erreur s'affichera

## 🔧 Configuration

### Ajuster le seuil de détection

Dans `app/Jobs/DetectSceneChanges.php`, ligne avec `gt(scene,0.4)` :

```php
// Threshold de 0.4 (ajustable selon les besoins)
$command = sprintf(
    'ffmpeg -i %s -vf "select=\'gt(scene,0.4)\',showinfo" -f null - 2>&1 ...',
    escapeshellarg($videoPath),
    escapeshellarg($outputFile)
);
```

**Valeurs recommandées** :
- `0.3` : Détection sensible (plus de cuts)
- `0.4` : Balance recommandée
- `0.5` : Détection stricte (moins de cuts)

### Ajuster le polling

Dans `ProjectDetailView.vue`, fonction `startAnalysisPolling()` :

```typescript
// Polling toutes les 2 secondes
analysisPollingInterval = window.setInterval(async () => {
  // ...
}, 2000)
```

## 📊 Performance

### Temps de traitement

Dépend de :
- Durée de la vidéo
- Résolution
- Complexité des scènes
- Puissance du serveur

**Estimation** : ~1-5 minutes pour une vidéo de 30 minutes en 1080p

### Optimisation

- Le job tourne en arrière-plan (pas de timeout HTTP)
- Fichier temporaire nettoyé automatiquement
- Évite les doublons lors de la création des SceneChanges

## 🐛 Debug

### Logs Laravel

```bash
tail -f storage/logs/laravel.log
```

Rechercher :
- `Début de l'analyse de détection de plans`
- `Exécution de la commande FFmpeg`
- `Analyse terminée`
- Erreurs éventuelles

### Tester FFmpeg manuellement

```bash
ffmpeg -i /path/to/video.mp4 \
  -vf "select='gt(scene,0.4)',showinfo" \
  -f null - 2>&1 | \
  grep "pts_time"
```

### Vérifier le queue worker

```bash
php artisan queue:listen --verbose
```

## 🔒 Sécurité

- Vérification des permissions avant lancement
- Validation de l'existence du fichier vidéo
- Escapage des arguments shell (`escapeshellarg`)
- Limitation : 1 seule analyse à la fois par projet
- Nettoyage automatique des fichiers temporaires

## 📝 TODO / Améliorations futures

- [ ] Barre de progression avec pourcentage réel
- [ ] Possibilité d'annuler l'analyse en cours
- [ ] Prévisualisation des cuts avant validation
- [ ] Support de différents algorithmes de détection
- [ ] Historique des analyses
- [ ] Notification en temps réel (WebSocket/Pusher)

---

**Maintainer** : Martin P. (@ParizetM)
**Documentation mise à jour** : 27 octobre 2025
