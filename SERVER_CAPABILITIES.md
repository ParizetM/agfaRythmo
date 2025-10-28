# Menu IA avec Détection Automatique des Capacités

**Version** : 2.1.1-beta | **Date** : 28 octobre 2025

## 🎯 Objectif

AgfaRythmo dispose d'un **menu IA centralisé** qui détecte automatiquement les capacités du serveur et adapte l'interface en conséquence. Ce système permet de :

- Fonctionner sur **serveurs complets** (avec FFmpeg et workers) → Toutes les fonctionnalités IA
- Fonctionner sur **hébergements basiques** (PHP standard) → Interface adaptée sans erreurs
- **Afficher clairement** le statut de chaque fonctionnalité IA
- **Être extensible** pour futures fonctionnalités (sous-titrage auto, reconnaissance vocale, etc.)

---

## 🔍 Capacités Détectées

### 1. FFmpeg
- **Détection** : Tentative d'exécution de `ffmpeg -version`
- **Utilisé pour** : Analyse IA automatique des changements de plan vidéo
- **Si absent** : Bouton "IA" masqué, badge "IA non disponible" affiché

### 2. Queue Worker
- **Détection** : Vérification de la configuration Laravel (`config/queue.php`)
- **Utilisé pour** : Traitement asynchrone des jobs (analyse vidéo)
- **Si absent** : Analyse IA désactivée

### 3. AI Analysis
- **Détection** : Combinaison de FFmpeg + Queue Worker
- **Utilisé pour** : Fonctionnalité complète d'analyse automatique
- **Si absent** : Fonctionnalité IA complètement désactivée

---

## 🏗️ Architecture Technique

### Backend

#### Service de Détection
**Fichier** : `app/Services/ServerCapabilities.php`

```php
namespace App\Services;

class ServerCapabilities
{
    /**
     * Vérifie si FFmpeg est disponible sur le serveur
     */
    public static function hasFfmpeg(): bool
    {
        try {
            exec('ffmpeg -version 2>&1', $output, $returnCode);
            return $returnCode === 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Vérifie si les workers de queue sont configurés
     */
    public static function hasQueueWorker(): bool
    {
        return config('queue.default') !== 'sync';
    }

    /**
     * Vérifie si l'analyse IA est disponible
     */
    public static function hasAiAnalysis(): bool
    {
        return self::hasFfmpeg() && self::hasQueueWorker();
    }

    /**
     * Retourne toutes les capacités
     */
    public static function getCapabilities(): array
    {
        return [
            'ffmpeg' => self::hasFfmpeg(),
            'queue_worker' => self::hasQueueWorker(),
            'ai_analysis' => self::hasAiAnalysis(),
        ];
    }
}
```

#### API Endpoint
**Route** : `GET /api/server/capabilities`

**Réponse** :
```json
{
  "ffmpeg": true,
  "queue_worker": true,
  "ai_analysis": true
}
```

**Accès** : Public (pas d'authentification requise)

---

### Frontend

#### Service API
**Fichier** : `src/api/serverCapabilities.ts`

```typescript
export interface ServerCapabilities {
  ffmpeg: boolean;
  queue_worker: boolean;
  ai_analysis: boolean;
}

export const getServerCapabilities = async (): Promise<ServerCapabilities> => {
  const response = await axios.get<ServerCapabilities>('/server/capabilities');
  return response.data;
};
```

#### Composable
**Fichier** : `src/composables/useServerCapabilities.ts`

```typescript
export function useServerCapabilities() {
  const capabilities = ref<ServerCapabilities | null>(null);
  
  const loadCapabilities = async () => {
    if (capabilities.value !== null) {
      return capabilities.value;
    }
    
    try {
      capabilities.value = await getServerCapabilities();
    } catch (err) {
      // Mode dégradé en cas d'erreur
      capabilities.value = {
        ffmpeg: false,
        queue_worker: false,
        ai_analysis: false
      };
    }
    
    return capabilities.value;
  };
  
  return {
    capabilities: readonly(capabilities),
    loadCapabilities
  };
}
```

#### Chargement au Démarrage
**Fichier** : `src/App.vue`

```typescript
import { useServerCapabilities } from './composables/useServerCapabilities';

const { loadCapabilities } = useServerCapabilities();

onMounted(() => {
  // Charger les capacités au démarrage de l'app
  loadCapabilities();
});
```

#### Utilisation dans les Composants
**Fichier** : `src/views/ProjectDetailView.vue`

```vue
<template>
  <!-- Bouton IA - Toujours visible, ouvre le menu -->
  <button
    v-if="project && project.video_path && canManageProject"
    @click="showAiMenu = true"
    class="bg-gradient-to-r from-purple-600 to-pink-600 ..."
  >
    IA
  </button>

  <!-- Menu IA avec détection automatique -->
  <AiMenuModal
    v-model:show="showAiMenu"
    :capabilities="serverCapabilities"
    :has-scene-changes="hasSceneChanges"
    :is-analyzing="isAnalyzing"
    @start-analysis="handleStartAnalysis"
  />
</template>

<script setup lang="ts">
import { useServerCapabilities } from '@/composables/useServerCapabilities';
import AiMenuModal from '@/components/AiMenuModal.vue';

const { capabilities: serverCapabilities } = useServerCapabilities();
const showAiMenu = ref(false);

function handleStartAnalysis() {
  // Fermer le menu et ouvrir les paramètres d'analyse
  showAiMenu.value = false;
  showAnalysisSettings.value = true;
}
</script>
```

#### Composant AiMenuModal
**Fichier** : `src/components/AiMenuModal.vue`

**Props** :
- `show` (boolean) : affichage de la modal
- `capabilities` (ServerCapabilities | null) : capacités détectées
- `hasSceneChanges` (boolean) : projet déjà analysé
- `isAnalyzing` (boolean) : analyse en cours

**Events** :
- `@update:show` : fermeture de la modal
- `@start-analysis` : lancement de l'analyse des changements de plan

**Sections** :
1. **État du système** : FFmpeg + Workers avec badges colorés
2. **Fonctionnalités** : Carte détection + placeholder futures fonctionnalités
3. **Footer** : Documentation + bouton Fermer

---

## 📋 Scénarios d'Utilisation

### Scénario 1 : Serveur Complet (Production)
**Configuration** :
- FFmpeg installé : ✅
- Workers configurés : ✅
- Queue driver : `database`

**Résultat** :
```json
{
  "ffmpeg": true,
  "queue_worker": true,
  "ai_analysis": true
}
```

**Interface** : Bouton "IA" visible et fonctionnel

---

### Scénario 2 : Hébergement Mutualisé (Basique)
**Configuration** :
- FFmpeg installé : ❌
- Workers configurés : ❌
- Queue driver : `sync`

**Résultat** :
```json
{
  "ffmpeg": false,
  "queue_worker": false,
  "ai_analysis": false
}
```

**Interface** : Badge "IA non disponible" affiché avec tooltip explicatif

---

### Scénario 3 : Serveur de Développement
**Configuration** :
- FFmpeg installé : ✅
- Workers configurés : ❌
- Queue driver : `sync`

**Résultat** :
```json
{
  "ffmpeg": true,
  "queue_worker": false,
  "ai_analysis": false
}
```

**Interface** : Badge "IA non disponible" (worker manquant)

---

## 🎨 Interface Utilisateur

### Menu IA Principal (AiMenuModal)

**Ouverture** : Clic sur le bouton "IA" dans la barre d'outils du projet (toujours visible)

#### Section "État du système"
```
╔═══════════════════════════════════════════╗
║ État du système                            ║
║ ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ ║
║ FFmpeg               ✓ Disponible         ║
║ Workers de queue     ✓ Actifs             ║
╚═══════════════════════════════════════════╝
```

**Avec capacités** : Badges verts avec checkmark (✓)  
**Sans capacités** : Badges rouges avec croix (✗) + avertissement jaune

#### Section "Fonctionnalités disponibles"

**Détection des changements de plan** (si IA disponible) :
```
┌─────────────────────────────────────────────────┐
│ 🎬 Détection des changements de plan            │
│                                                  │
│ Analyse automatique de la vidéo pour détecter   │
│ les cuts et changements de plan                 │
│                                                  │
│ ⏱️ Quelques minutes    ⚡ FFmpeg requis         │
│                                                  │
│                           [🚀 Lancer] ←────────│
└─────────────────────────────────────────────────┘
```

**Si déjà analysé** : Badge bleu "Déjà analysé" + bouton "Déjà fait" (désactivé)

**Futures fonctionnalités** :
```
┌─────────────────────────────────────────────────┐
│        + Nouvelles fonctionnalités IA           │
│          à venir...                              │
│                                                  │
│ Sous-titrage automatique, reconnaissance        │
│ vocale, analyse audio, etc.                      │
└─────────────────────────────────────────────────┘
```

### État "IA Disponible"
- Carte interactive avec hover effect
- Bouton "Lancer" avec gradient violet/rose
- Badges colorés verts pour FFmpeg et Workers
- Aucun message d'avertissement

### État "IA Non Disponible"
- Carte grisée, non cliquable
- Texte "Non disponible" à la place du bouton
- Badges rouges pour capacités manquantes
- Avertissement jaune avec lien documentation

**Footer** : Bouton "Fermer" + lien "Documentation" vers GitHub

---

## 🚀 Déploiement

### Installation sur Serveur Complet

1. **Installer FFmpeg**
```bash
# Ubuntu/Debian
sudo apt-get install ffmpeg

# CentOS/RHEL
sudo yum install ffmpeg

# Vérifier
ffmpeg -version
```

2. **Configurer les workers**
```bash
# Copier la configuration Supervisor
sudo cp supervisor-agfaRythmo-worker.conf /etc/supervisor/conf.d/

# Démarrer les workers
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start agfaRythmo-worker:*
```

3. **Vérifier les capacités**
```bash
# Via API
curl https://votre-domaine.com/api/server/capabilities

# Résultat attendu :
# {"ffmpeg":true,"queue_worker":true,"ai_analysis":true}
```

---

### Installation sur Serveur Basique

1. **Déployer l'application**
```bash
# Build et upload
npm run build
# Uploader dist/ sur le serveur
```

2. **Configuration Laravel**
```env
# .env
QUEUE_CONNECTION=sync  # Pas de workers
```

3. **Vérifier les capacités**
```bash
# Via API
curl https://votre-domaine.com/api/server/capabilities

# Résultat attendu :
# {"ffmpeg":false,"queue_worker":false,"ai_analysis":false}
```

4. **Vérifier l'interface**
- Se connecter à l'application
- Ouvrir un projet avec vidéo
- Vérifier que le badge "IA non disponible" s'affiche au lieu du bouton

---

## 🐛 Dépannage

### Problème : Badge "IA non disponible" alors que FFmpeg est installé

**Diagnostic** :
```bash
# Vérifier FFmpeg
which ffmpeg
ffmpeg -version

# Vérifier les permissions
ls -la $(which ffmpeg)

# Tester depuis PHP
php -r "exec('ffmpeg -version 2>&1', \$output, \$code); echo \$code;"
# Doit afficher : 0
```

**Solutions** :
1. Ajouter FFmpeg au PATH du serveur web
2. Vérifier les permissions d'exécution
3. Redémarrer PHP-FPM/Apache

---

### Problème : Capacités non détectées

**Diagnostic** :
```bash
# Tester l'endpoint directement
curl -v https://votre-domaine.com/api/server/capabilities

# Vérifier les logs Laravel
tail -f storage/logs/laravel.log
```

**Solutions** :
1. Vider le cache de configuration : `php artisan config:clear`
2. Vérifier les logs PHP : `tail -f /var/log/php-fpm/error.log`
3. Tester la détection manuellement :
```php
php artisan tinker
> \App\Services\ServerCapabilities::getCapabilities();
```

---

## ✅ Tests

### Test Backend
```bash
# Dans Laravel Tinker
php artisan tinker

> \App\Services\ServerCapabilities::hasFfmpeg();
# true ou false

> \App\Services\ServerCapabilities::hasQueueWorker();
# true ou false

> \App\Services\ServerCapabilities::getCapabilities();
# Affiche le tableau complet
```

### Test API
```bash
# Via cURL
curl https://localhost:8000/api/server/capabilities

# Avec jq pour formater
curl -s https://localhost:8000/api/server/capabilities | jq
```

### Test Frontend
1. Ouvrir les DevTools (F12)
2. Aller dans l'onglet Network
3. Filtrer par "capabilities"
4. Rafraîchir la page
5. Vérifier la requête GET `/api/server/capabilities`
6. Vérifier la réponse JSON

---

## 📚 Références

- **Backend** : `app/Services/ServerCapabilities.php`
- **API** : `routes/api.php` (ligne ~7)
- **Frontend API** : `src/api/serverCapabilities.ts`
- **Composable** : `src/composables/useServerCapabilities.ts`
- **Vue principale** : `src/views/ProjectDetailView.vue`
- **Chargement** : `src/App.vue`

---

## 🔄 Évolutions Futures

### Capacités Supplémentaires à Détecter
- [ ] Support GPU (pour accélération FFmpeg)
- [ ] Espace disque disponible
- [ ] Limite upload de fichiers
- [ ] Extensions PHP disponibles
- [ ] Version de FFmpeg

### Améliorations Interface
- [ ] Page de diagnostic complet dans l'admin
- [ ] Affichage des versions des dépendances
- [ ] Alertes si capacités manquantes
- [ ] Guide d'installation contextuel

---

**Dernière mise à jour** : 28 octobre 2025  
**Version** : 2.1.1-beta  
**Maintainer** : Martin P. (@ParizetM)
