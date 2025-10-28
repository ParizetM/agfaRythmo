# Configuration des Fonctionnalités IA

**Version** : 2.1.1-beta | **Date** : 28 octobre 2025

## 🎯 Activation/Désactivation Simple

Les fonctionnalités IA d'AgfaRythmo peuvent être activées ou désactivées via un simple fichier de configuration.

---

## ⚙️ Configuration

### Fichier .env

Ajoutez ces lignes dans votre fichier `.env` (backend) :

```bash
# Fonctionnalités IA
AI_SCENE_DETECTION_ENABLED=true       # Détection automatique des changements de plan
AI_AUTO_SUBTITLES_ENABLED=false       # Sous-titrage automatique (à venir)
AI_VOICE_RECOGNITION_ENABLED=false    # Reconnaissance vocale (à venir)
AI_AUDIO_ANALYSIS_ENABLED=false       # Analyse audio (à venir)
```

### Options

- `true` = Fonctionnalité **activée** et visible dans le menu IA
- `false` = Fonctionnalité **désactivée** et masquée

---

## 🚀 Déploiement

### Installation Complète (avec IA)

```bash
# 1. Copier .env.example
cp .env.example .env

# 2. Activer la détection de scènes
AI_SCENE_DETECTION_ENABLED=true

# 3. S'assurer que FFmpeg est installé et que les workers tournent
ffmpeg -version
php artisan queue:work
```

### Installation Basique (sans IA)

```bash
# 1. Copier .env.example
cp .env.example .env

# 2. Désactiver toutes les fonctionnalités IA
AI_SCENE_DETECTION_ENABLED=false
AI_AUTO_SUBTITLES_ENABLED=false
AI_VOICE_RECOGNITION_ENABLED=false
AI_AUDIO_ANALYSIS_ENABLED=false
```

---

## 🎨 Interface Utilisateur

### Bouton IA

Le bouton "IA" est **toujours visible** dans la barre d'outils des projets.

**Clic** → Ouvre le menu IA qui affiche :
- ✅ Fonctionnalités activées (avec bouton "Lancer")
- ⚠️ Message si aucune fonctionnalité activée
- 📋 Placeholder pour futures fonctionnalités

### Exemple: Avec détection de scènes activée

```
┌─────────────────────────────────────────────┐
│ Fonctionnalités IA                          │
├─────────────────────────────────────────────┤
│                                              │
│ 🎬 Détection des changements de plan        │
│    Analyse automatique de la vidéo          │
│    ⏱️ Quelques minutes                      │
│                            [🚀 Lancer]       │
│                                              │
│ ┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄ │
│ + Nouvelles fonctionnalités IA à venir...   │
│                                              │
│                            [Fermer]          │
└─────────────────────────────────────────────┘
```

### Exemple: Toutes fonctionnalités désactivées

```
┌─────────────────────────────────────────────┐
│ Fonctionnalités IA                          │
├─────────────────────────────────────────────┤
│                                              │
│           ⚠️                                │
│  Aucune fonctionnalité IA activée           │
│  Contactez votre administrateur             │
│                                              │
│ ┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄ │
│ + Nouvelles fonctionnalités IA à venir...   │
│                                              │
│                            [Fermer]          │
└─────────────────────────────────────────────┘
```

---

## 📁 Fichiers de Configuration

### Backend

**`config/ai.php`** : Configuration des fonctionnalités IA

```php
return [
    'scene_detection' => env('AI_SCENE_DETECTION_ENABLED', true),
    'auto_subtitles' => env('AI_AUTO_SUBTITLES_ENABLED', false),
    'voice_recognition' => env('AI_VOICE_RECOGNITION_ENABLED', false),
    'audio_analysis' => env('AI_AUDIO_ANALYSIS_ENABLED', false),
];
```

**`app/Services/ServerCapabilities.php`** : Service qui expose les capacités

```php
public static function getCapabilities(): array
{
    return [
        'scene_detection' => config('ai.scene_detection', false),
        'auto_subtitles' => config('ai.auto_subtitles', false),
        'voice_recognition' => config('ai.voice_recognition', false),
        'audio_analysis' => config('ai.audio_analysis', false),
    ];
}
```

### API

**Route** : `GET /api/server/capabilities`

**Réponse** :
```json
{
  "scene_detection": true,
  "auto_subtitles": false,
  "voice_recognition": false,
  "audio_analysis": false
}
```

---

## 🔧 Modification à Chaud

Les changements dans `.env` sont pris en compte **immédiatement** après :

```bash
# Vider le cache de configuration
php artisan config:clear

# Redémarrer les workers si nécessaire
php artisan queue:restart
```

Pas besoin de redémarrer le serveur web !

---

## 📋 Cas d'Usage

### Serveur de Production (Complet)

```bash
# .env
AI_SCENE_DETECTION_ENABLED=true
AI_AUTO_SUBTITLES_ENABLED=false  # Pas encore implémenté
AI_VOICE_RECOGNITION_ENABLED=false  # Pas encore implémenté
AI_AUDIO_ANALYSIS_ENABLED=false  # Pas encore implémenté

# Prérequis
- FFmpeg installé
- Workers de queue actifs
```

### Serveur de Démo (Sans IA)

```bash
# .env
AI_SCENE_DETECTION_ENABLED=false
AI_AUTO_SUBTITLES_ENABLED=false
AI_VOICE_RECOGNITION_ENABLED=false
AI_AUDIO_ANALYSIS_ENABLED=false

# Aucun prérequis IA
- Pas besoin de FFmpeg
- Pas besoin de workers
```

### Environnement de Développement (Test)

```bash
# .env
AI_SCENE_DETECTION_ENABLED=true
AI_AUTO_SUBTITLES_ENABLED=true  # Pour tester l'UI future
AI_VOICE_RECOGNITION_ENABLED=false
AI_AUDIO_ANALYSIS_ENABLED=false

# Permet de tester les futures fonctionnalités dans l'UI
```

---

## 🆕 Ajout de Nouvelles Fonctionnalités

Pour ajouter une nouvelle fonctionnalité IA :

1. **Backend** : Ajouter dans `config/ai.php`
```php
'nouvelle_fonctionnalite' => env('AI_NOUVELLE_FONCTIONNALITE_ENABLED', false),
```

2. **Service** : Ajouter dans `ServerCapabilities::getCapabilities()`
```php
'nouvelle_fonctionnalite' => config('ai.nouvelle_fonctionnalite', false),
```

3. **Frontend** : Ajouter dans `src/api/serverCapabilities.ts`
```typescript
export interface ServerCapabilities {
  // ... existant
  nouvelle_fonctionnalite: boolean;
}
```

4. **UI** : Ajouter une carte dans `AiMenuModal.vue`
```vue
<div v-if="capabilities?.nouvelle_fonctionnalite">
  <!-- Interface de la nouvelle fonctionnalité -->
</div>
```

5. **`.env.example`** : Documenter
```bash
AI_NOUVELLE_FONCTIONNALITE_ENABLED=false
```

---

## ✅ Avantages de cette Approche

- ✅ **Simple** : Juste une variable d'environnement à changer
- ✅ **Clair** : Pas de détection automatique complexe
- ✅ **Flexible** : Activer/désactiver sans modifier le code
- ✅ **Évolutif** : Facile d'ajouter de nouvelles fonctionnalités
- ✅ **Pas d'info technique** : L'utilisateur ne voit pas comment ça marche en interne
- ✅ **Configuration serveur** : L'admin contrôle ce qui est disponible

---

**Dernière mise à jour** : 28 octobre 2025  
**Version** : 2.1.1-beta  
**Maintainer** : Martin P. (@ParizetM)
