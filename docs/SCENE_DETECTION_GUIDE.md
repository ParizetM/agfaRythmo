# Guide de Détection Automatique des Plans (IA)

**Version** : 1.0.0 | **Date** : 27 octobre 2025

## 📋 Vue d'ensemble

Cette fonctionnalité utilise FFmpeg pour détecter automatiquement les changements de plan dans une vidéo et les ajouter au projet AgfaRythmo. L'analyse est effectuée en arrière-plan via le système de queues Laravel.

## ✨ Fonctionnalités

- ✅ **Détection automatique** des changements de plan via FFmpeg (seuil 0.4)
- ✅ **Traitement asynchrone** avec Laravel Queue
- ✅ **Suivi de progression** en temps réel (0-100%)
- ✅ **Interface bloquante** avec modal pendant l'analyse
- ✅ **Persistance de l'état** lors du rechargement de page
- ✅ **Annulation en cours d'analyse** avec nettoyage automatique
- ✅ **Permissions** : modification requise (edit ou admin)

## 🎯 Cas d'utilisation

### Activation du bouton IA
Le bouton "Détection IA" n'est activé que si :
- Le projet n'a **AUCUN** scene change existant
- L'utilisateur a les permissions de modification
- Une vidéo est associée au projet
- Aucune analyse n'est en cours

### Workflow complet

1. **Démarrage** : L'utilisateur clique sur "Détection IA"
2. **Validation** : Le backend vérifie les prérequis
3. **Lancement** : Job dispatché dans la queue Laravel
4. **Progression** : Modal affiche le pourcentage (0-100%)
5. **Annulation** : Possibilité d'interrompre à tout moment
6. **Finalisation** : Scene changes ajoutés au projet ou nettoyage si annulé

## 🔧 Architecture Technique

### Backend

#### Migration
```php
// 2025_10_27_210934_add_analysis_status_to_projects_table.php
$table->string('analysis_status')->default('none');

// 2025_10_27_212502_add_analysis_progress_to_projects_table.php
$table->integer('analysis_progress')->default(0);
$table->text('analysis_message')->nullable();
```

#### Statuts possibles
- `none` : Aucune analyse (état initial)
- `pending` : Analyse en file d'attente
- `processing` : Analyse en cours
- `completed` : Analyse terminée avec succès
- `failed` : Erreur lors de l'analyse
- `cancelled` : Analyse annulée par l'utilisateur

#### Job : `DetectSceneChanges`
```php
// Points de contrôle d'annulation (5 emplacements)
1. Avant vérification vidéo
2. Avant exécution FFmpeg
3. Avant parsing du log FFmpeg
4. Avant enregistrement des scene changes
5. Après enregistrement (nettoyage si annulé)

// Progression
0%   → Démarrage
10%  → Vérification vidéo
20%  → Préparation FFmpeg
60%  → Exécution FFmpeg terminée
70-95% → Enregistrement progressif (par batch de 10 scene changes)
100% → Finalisation
```

#### Endpoints API

##### POST `/api/projects/{id}/analyze-scenes`
Démarre l'analyse automatique
- **Permissions** : `canModify()`
- **Validations** :
  - Aucun scene change existant
  - Vidéo présente
  - Pas d'analyse en cours
- **Retour** : `{ message, analysis_status: 'pending' }`

##### GET `/api/projects/{id}/analysis-status`
Récupère le statut de l'analyse
- **Permissions** : `hasAccess()`
- **Retour** :
```json
{
  "analysis_status": "processing",
  "analysis_progress": 75,
  "analysis_message": "Enregistrement des changements de plan...",
  "scene_changes_count": 42
}
```

##### POST `/api/projects/{id}/cancel-analysis`
Annule l'analyse en cours
- **Permissions** : `canModify()`
- **Validations** : Analyse en cours (`pending` ou `processing`)
- **Retour** : `{ message, analysis_status: 'cancelled' }`
- **Effet** : Le Job détectera le statut `cancelled` et arrêtera le traitement

### Frontend

#### Service API : `sceneAnalysis.ts`
```typescript
interface AnalysisStatus {
  analysis_status: 'none' | 'pending' | 'processing' | 'completed' | 'failed' | 'cancelled'
  analysis_progress: number  // 0-100
  analysis_message: string
  scene_changes_count: number
}

startSceneAnalysis(projectId)
getAnalysisStatus(projectId)
cancelSceneAnalysis(projectId)
```

#### Composant : `SceneAnalysisModal.vue`
- Modal bloquant (non-fermable pendant l'analyse)
- Affichage progression (barre + pourcentage)
- Message de statut dynamique
- Bouton "Annuler l'analyse"
- Spinner animé

#### Vue : `ProjectDetailView.vue`
```typescript
// Polling automatique toutes les 2 secondes
const pollInterval = 2000

// Détection auto au mount si analyse en cours
onMounted(() => {
  if (analysisStatus.value === 'pending' || analysisStatus.value === 'processing') {
    startPolling()
  }
})

// Annulation
const handleCancelAnalysis = async () => {
  if (confirm('Voulez-vous vraiment annuler l\'analyse en cours ?')) {
    await cancelSceneAnalysis(projectId)
    stopPolling()
    // Mise à jour après annulation
  }
}
```

## 🎥 FFmpeg Command

```bash
ffmpeg -i video.mp4 \
  -vf "select='gt(scene,0.4)',showinfo" \
  -f null - 2>&1
```

### Paramètres
- `select='gt(scene,0.4)'` : Détecte les changements > 40% de différence
- `showinfo` : Affiche les métadonnées de chaque frame détectée
- `-f null -` : Pas de sortie vidéo (analyse seule)
- `2>&1` : Capture stderr pour parsing

### Format de log FFmpeg
```
[Parsed_showinfo_1 @ 0x...] n:  42 pts:  1234567 pts_time:12.345 ...
```

### Parsing
```php
preg_match('/pts_time:(\d+\.?\d*)/', $line, $matches)
$timecode = (float) $matches[1]
```

## 🔒 Gestion des Permissions

| Rôle | Permissions |
|------|------------|
| **Propriétaire** | Tous les droits (start, cancel, view) |
| **Collaborateur Admin** | Tous les droits |
| **Collaborateur Edit** | Tous les droits |
| **Collaborateur View** | Status uniquement (pas start/cancel) |
| **Non-collaborateur** | Aucun accès |

## 🚨 Gestion des Erreurs

### Backend
```php
try {
    // Exécution FFmpeg
} catch (\Exception $e) {
    $project->update([
        'analysis_status' => 'failed',
        'analysis_message' => 'Erreur : ' . $e->getMessage()
    ]);
}
```

### Frontend
```typescript
try {
  await startSceneAnalysis(projectId)
} catch (error) {
  if (error.response?.status === 422) {
    // Validation error (déjà des scene changes, etc.)
  } else if (error.response?.status === 403) {
    // Permission denied
  }
  notifications.error('Erreur lors du lancement de l\'analyse')
}
```

## ⚠️ Annulation d'Analyse

### Comportement
1. **Utilisateur** clique sur "Annuler l'analyse"
2. **Confirmation** via dialog natif
3. **API** met `analysis_status = 'cancelled'`
4. **Job** détecte le statut à chaque checkpoint
5. **Cleanup** : Suppression des scene changes déjà créés
6. **UI** : Arrêt du polling, fermeture modal

### Checkpoints d'annulation
```php
// Le Job vérifie à 5 endroits stratégiques
$this->project->refresh();
if ($this->project->analysis_status === 'cancelled') {
    $this->cleanupOnCancellation();
    return;
}
```

### Nettoyage automatique
```php
private function cleanupOnCancellation(): void
{
    // Supprimer tous les scene changes créés pendant cette analyse
    $this->project->sceneChanges()->delete();
    
    $this->project->update([
        'analysis_status' => 'cancelled',
        'analysis_progress' => 0,
        'analysis_message' => 'Analyse annulée par l\'utilisateur'
    ]);
}
```

## 📊 Suivi de Progression

### Étapes du Job
| Étape | Progression | Message |
|-------|------------|---------|
| Démarrage | 0% | "Démarrage de l'analyse..." |
| Vérification vidéo | 10% | "Vérification de la vidéo..." |
| Préparation FFmpeg | 20% | "Préparation de l'analyse FFmpeg..." |
| Exécution FFmpeg | 20-60% | "Analyse de la vidéo en cours..." |
| Parsing log | 60% | "Traitement des résultats..." |
| Enregistrement | 70-95% | "Enregistrement des changements de plan..." |
| Finalisation | 100% | "Analyse terminée" |

### Calcul progression enregistrement
```php
$batchSize = 10;
foreach ($sceneChanges as $index => $timecode) {
    if ($index % $batchSize === 0) {
        $progress = 70 + (($index / count($sceneChanges)) * 25);
        $this->updateProgress((int)$progress, "...");
    }
}
```

## 🔄 Persistance de l'État

### Problème
L'utilisateur recharge la page pendant une analyse → perte du contexte

### Solution
```typescript
onMounted(async () => {
  if (project.value?.id) {
    // Vérifier le statut d'analyse au mount
    const status = await getAnalysisStatus(project.value.id)
    analysisStatus.value = status.analysis_status
    
    // Si analyse en cours, reprendre le polling
    if (status.analysis_status === 'pending' || 
        status.analysis_status === 'processing') {
      startPolling()
    }
  }
})
```

## 🧪 Tests Recommandés

### Backend
```bash
# Tester l'endpoint de démarrage
POST /api/projects/1/analyze-scenes

# Tester le statut
GET /api/projects/1/analysis-status

# Tester l'annulation
POST /api/projects/1/cancel-analysis

# Lancer le worker de queue
php artisan queue:work --tries=1
```

### Frontend
1. Cliquer sur "Détection IA" → Modal apparaît
2. Progression augmente progressivement
3. Recharger la page → Modal persiste
4. Cliquer "Annuler" → Analyse stoppée, modal fermé
5. Scene changes créés → Bouton IA désactivé

## 📝 Notes Importantes

### ⚠️ Prérequis
- **FFmpeg installé** sur le serveur
- **Queue worker actif** : `php artisan queue:work`
- **Espace disque** suffisant pour logs temporaires
- **Timeout PHP** augmenté si vidéos longues (non bloquant grâce aux queues)

### 🚀 Optimisations
- Chunking du parsing FFmpeg pour économiser la mémoire
- Batch insert des scene changes par paquets de 10
- Polling côté client toutes les 2s (pas trop fréquent)
- Cache des résultats FFmpeg (pas implémenté actuellement)

### 🔧 Maintenance
- **Nettoyage** : Les analyses `failed` ou `cancelled` gardent leur statut
- **Reset** : Pour relancer une analyse, supprimer manuellement les scene changes
- **Logs** : Vérifier `storage/logs/laravel.log` en cas d'erreur FFmpeg

---

**Dernière mise à jour** : 27 octobre 2025
**Version** : 1.0.0
**Mainteneur** : Martin P. (@ParizetM)
