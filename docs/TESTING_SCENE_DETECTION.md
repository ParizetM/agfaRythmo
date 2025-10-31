# Guide de Test - Détection IA des Changements de Plan

## 🧪 Prérequis

1. **Backend** : Laravel avec queue worker actif
   ```bash
   cd agfa-rythmo-backend
   composer dev
   ```
   Cela lance automatiquement le queue worker.

2. **FFmpeg installé** :
   ```bash
   ffmpeg -version
   ```
   Si non installé :
   - **macOS** : `brew install ffmpeg`
   - **Linux** : `sudo apt install ffmpeg`
   - **Windows** : Télécharger depuis ffmpeg.org

3. **Frontend** : 
   Le frontend est servi par le backend via Vite dans `composer dev`

4. **Base de données** : Migration exécutée
   ```bash
   php artisan migrate
   ```

## ✅ Scénario de Test Complet

### Test 1 : Lancement réussi de l'analyse

**Étapes** :
1. Se connecter à l'application
2. Créer un nouveau projet avec une vidéo
3. Ouvrir le projet dans `ProjectDetailView`
4. Vérifier que le bouton "IA" (icône ampoule) est visible et actif
5. Cliquer sur le bouton "IA"

**Résultats attendus** :
- ✅ Modal bloquant s'affiche immédiatement
- ✅ Message "Démarrage de l'analyse..."
- ✅ Spinner animé visible
- ✅ UI complètement bloquée (impossible de cliquer ailleurs)
- ✅ Après quelques secondes : "Analyse de la vidéo en cours..."
- ✅ Statut passe à `processing` (vérifiable dans logs)

**Logs à surveiller** :
```bash
tail -f agfa-rythmo-backend/storage/logs/laravel.log
```
Rechercher :
```
Début de l'analyse de détection de plans pour le projet X
Exécution de la commande FFmpeg: ...
```

### Test 2 : Fin de l'analyse et affichage des résultats

**Étapes** (suite du Test 1) :
1. Attendre la fin de l'analyse (1-5 min selon la vidéo)

**Résultats attendus** :
- ✅ Modal se ferme automatiquement
- ✅ Alert JavaScript : "Analyse terminée ! X changement(s) de plan détecté(s)."
- ✅ Les scene changes apparaissent dans le panneau de droite
- ✅ Les indicateurs de scene changes sont visibles sur la timeline
- ✅ Le bouton "IA" devient grisé/désactivé (car scene changes existent maintenant)

**Vérification backend** :
```sql
SELECT * FROM scene_changes WHERE project_id = X;
```

**Logs** :
```
Analyse terminée pour le projet X. Y changements de plan créés.
```

### Test 3 : Bouton désactivé si scene changes existent

**Étapes** :
1. Projet avec des scene changes existants (du Test 2)
2. Recharger la page `ProjectDetailView`

**Résultats attendus** :
- ✅ Bouton "IA" visible mais grisé
- ✅ Curseur `not-allowed` au survol
- ✅ Tooltip : "Des changements de plan existent déjà"
- ✅ Clic sans effet

### Test 4 : Permissions insuffisantes

**Étapes** :
1. Se connecter avec un compte collaborateur en mode "view"
2. Ouvrir un projet partagé

**Résultats attendus** :
- ✅ Bouton "IA" non visible (car `canManageProject = false`)

### Test 5 : Projet sans vidéo

**Étapes** :
1. Créer un projet sans uploader de vidéo
2. Ouvrir le projet

**Résultats attendus** :
- ✅ Bouton "IA" non visible (car `!project.video_path`)

### Test 6 : Analyse échouée

**Étapes** :
1. **Simuler une erreur** : Renommer temporairement le fichier vidéo
   ```bash
   mv storage/app/public/videos/xxx.mp4 storage/app/public/videos/xxx.mp4.bak
   ```
2. Lancer l'analyse

**Résultats attendus** :
- ✅ Modal s'affiche
- ✅ Après quelques secondes, le statut passe à `failed`
- ✅ Modal se ferme
- ✅ Alert : "L'analyse a échoué. Veuillez vérifier les logs ou réessayer."
- ✅ Aucun scene change créé

**Logs** :
```
Erreur lors de l'analyse de détection de plans pour le projet X: Fichier vidéo introuvable
```

**Nettoyage** :
```bash
mv storage/app/public/videos/xxx.mp4.bak storage/app/public/videos/xxx.mp4
```

### Test 7 : Polling et rafraîchissement collaboratif

**Étapes** :
1. Lancer une analyse
2. Pendant l'analyse, observer le réseau dans DevTools (F12)

**Résultats attendus** :
- ✅ Requête `GET /api/projects/{id}/analysis-status` toutes les 2 secondes
- ✅ Réponse avec `analysis_status` et `scene_changes_count`
- ✅ Polling s'arrête automatiquement quand statut = `completed` ou `failed`

### Test 8 : Nettoyage onUnmounted

**Étapes** :
1. Lancer une analyse
2. Pendant l'analyse, naviguer vers une autre page (cliquer "Projets")

**Résultats attendus** :
- ✅ Modal se ferme
- ✅ Polling s'arrête (pas de requêtes dans DevTools)
- ✅ Aucune erreur console

## 🔍 Vérifications Techniques

### 1. Base de données

**Vérifier la migration** :
```sql
DESCRIBE projects;
```
Doit contenir la colonne `analysis_status` (VARCHAR, default 'none')

**Vérifier les scene changes créés** :
```sql
SELECT project_id, COUNT(*) as count, MIN(timecode), MAX(timecode)
FROM scene_changes
GROUP BY project_id;
```

### 2. Queue Jobs

**Vérifier que le job est dispatché** :
```sql
SELECT * FROM jobs;
```

Si vide après quelques secondes, le job a été traité.

**En cas d'erreur** :
```sql
SELECT * FROM failed_jobs ORDER BY failed_at DESC LIMIT 5;
```

### 3. FFmpeg

**Test manuel** :
```bash
cd agfa-rythmo-backend/storage/app/public/videos
ffmpeg -i votre_video.mp4 \
  -vf "select='gt(scene,0.4)',showinfo" \
  -f null - 2>&1 | \
  grep "pts_time" | \
  head -20
```

Doit afficher des timecodes :
```
10.234567
25.891234
42.567890
...
```

### 4. API

**Tester l'endpoint startAnalysis** :
```bash
curl -X POST http://localhost:8000/api/projects/1/analyze-scenes \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json"
```

**Tester l'endpoint getStatus** :
```bash
curl http://localhost:8000/api/projects/1/analysis-status \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## 🐛 Problèmes Courants

### Le bouton IA n'apparaît pas

**Causes possibles** :
- Pas de vidéo dans le projet → Solution : Uploader une vidéo
- Permissions insuffisantes → Solution : Devenir propriétaire ou admin
- Scene changes déjà présents → Solution : Les supprimer

### L'analyse reste bloquée sur "pending"

**Causes possibles** :
- Queue worker non démarré → Solution : `php artisan queue:work`
- Erreur dans le job → Vérifier `failed_jobs` et logs

### Aucun scene change détecté

**Causes possibles** :
- Vidéo avec peu de changements de plan → Normal
- Threshold trop élevé → Réduire à 0.3 dans `DetectSceneChanges.php`
- FFmpeg non installé → Installer FFmpeg

### Erreur "Fichier vidéo introuvable"

**Causes possibles** :
- Lien symbolique storage non créé → `php artisan storage:link`
- Fichier supprimé manuellement → Ré-uploader

## 📊 Métriques de Performance

**Temps d'analyse moyens observés** :

| Durée vidéo | Résolution | Temps analyse |
|-------------|------------|---------------|
| 5 min       | 720p       | ~30 sec       |
| 15 min      | 1080p      | ~1-2 min      |
| 30 min      | 1080p      | ~3-5 min      |
| 1h          | 4K         | ~10-15 min    |

*Testé sur MacBook Pro M1, résultats variables selon machine*

## ✨ Cas d'Usage Réels

### Vidéo avec beaucoup de cuts (clip musical)
- Threshold : 0.3-0.4
- Résultat : 50-200 scene changes

### Vidéo avec peu de cuts (plan séquence)
- Threshold : 0.4-0.5
- Résultat : 0-10 scene changes

### Vidéo interview (alternance caméras)
- Threshold : 0.4
- Résultat : 20-50 scene changes

---

**Dernière mise à jour** : 27 octobre 2025
