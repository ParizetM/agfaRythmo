# Fonctionnalité : Paramètres de Projet

## 📋 Vue d'ensemble

Nouvelle fonctionnalité permettant de personnaliser l'apparence des bandes rythmo dans l'application AgfaRythmo.

## ✨ Fonctionnalités implémentées

### 1. **Modal de paramètres** (`ProjectSettingsModal.vue`)
- Interface similaire à `KeyboardShortcutsModal.vue`
- Layout en 2 colonnes : formulaire à gauche, aperçu en direct à droite
- Accessible via un bouton icône ⚙️ dans le header de `ProjectDetailView`

### 2. **Paramètres disponibles**

#### 🎨 Apparence
- **Hauteur de la bande** : 80-200px (slider)
- **Taille de police** : 1.0-3.5rem (slider)
- **Police de caractères** : Sélection parmi 19 polices Google Fonts populaires
  - Sans-serif : Inter, Roboto, Open Sans, Lato, Montserrat, Poppins, etc.
  - Serif : Playfair Display, Merriweather, PT Serif, Lora, etc.
  - Monospace : Inconsolata, Fira Code, JetBrains Mono, etc.

#### 🎨 Couleurs
- **Couleur de fond de la bande** : Picker + input hexadécimal
- **Couleur des changements de scène** : Picker + input hexadécimal

#### 📺 Position dans l'aperçu final
- **Sous la vidéo** : Bande positionnée en dessous de la vidéo (ne chevauche pas)
  - La vidéo s'ajuste pour laisser de la place
  - Le haut de la bande est collé au bas de la vidéo
- **Par-dessus la vidéo** : Bande superposée en overlay transparent
  - Ancrée en bas de la vidéo
  - Légère transparence + flou d'arrière-plan
  - N'affecte pas la taille de la vidéo

### 3. **Aperçu en direct**
- Prévisualisation miniature dans la modal
- Mise à jour instantanée lors des changements
- Simulation de :
  - Ticks de temps
  - Changements de scène
  - Blocs de texte (normal et actif)
  - Application de tous les paramètres visuels

### 4. **Persistance des données**
- Store Pinia (`projectSettings.ts`)
- Sauvegarde automatique dans `localStorage`
- Chargement au démarrage de l'application
- Bouton "Réinitialiser" pour revenir aux valeurs par défaut

### 5. **Application des paramètres**

Les paramètres sont appliqués dynamiquement dans :
- ✅ `RythmoBandSingle.vue` : hauteur, police, taille, couleurs
- ✅ `FinalPreviewView.vue` : position overlay (over/under)
- ⚠️ `MultiRythmoBand.vue` : hérite des paramètres de `RythmoBandSingle.vue`

## 📁 Fichiers créés

```
agfa-rythmo-frontend/
├── src/
│   ├── stores/
│   │   └── projectSettings.ts                    # Store Pinia des paramètres
│   ├── services/
│   │   └── googleFonts.ts                        # Service Google Fonts
│   └── components/
│       └── projectDetail/
│           └── ProjectSettingsModal.vue          # Modal de paramètres
```

## 📝 Fichiers modifiés

```
agfa-rythmo-frontend/
├── src/
│   ├── views/
│   │   ├── ProjectDetailView.vue                 # Ajout du bouton paramètres
│   │   └── FinalPreviewView.vue                  # Gestion position overlay
│   └── components/
│       └── projectDetail/
│           └── RythmoBandSingle.vue              # Application des paramètres visuels
```

## 🎯 Valeurs par défaut

```typescript
{
  bandHeight: 120,           // pixels
  fontSize: 2.1,             // rem
  fontFamily: 'Inter',       // Google Font
  bandBackgroundColor: '#202937',
  sceneChangeColor: '#8455F6',
  overlayPosition: 'under'   // 'under' | 'over'
}
```

## 🚀 Utilisation

1. **Ouvrir les paramètres** : Cliquer sur l'icône ⚙️ dans le header du projet
2. **Modifier les paramètres** : Utiliser les sliders, pickers de couleur, etc.
3. **Voir l'aperçu** : L'aperçu se met à jour en temps réel
4. **Appliquer** : Cliquer sur "Appliquer" pour sauvegarder
5. **Réinitialiser** : Bouton "Réinitialiser" pour revenir aux valeurs par défaut

## 🔧 Intégration technique

### Store Pinia
```typescript
import { useProjectSettingsStore } from '@/stores/projectSettings'

const settingsStore = useProjectSettingsStore()
const settings = computed(() => settingsStore.settings)

// Utilisation
settings.value.bandHeight
settings.value.fontFamily
```

### CSS dynamique (v-bind)
```vue
<style scoped>
.rythmo-band {
  background: v-bind('projectSettings.bandBackgroundColor');
  min-height: v-bind('projectSettings.bandHeight + "px"');
}
</style>
```

### Chargement de polices Google Fonts
Les polices sont chargées dynamiquement via un `<link>` injecté dans le `<head>` du document.

## 📱 Responsive

- Modal adaptée : 95vw x 90vh
- Aperçu avec ratio 16:9
- Tous les contrôles accessibles sur petit écran

## 🎨 Design

- Suit le design system AgfaRythmo
- Couleurs cohérentes avec l'application
- Transitions fluides
- Icônes SVG natives

## ⌨️ Raccourcis clavier

- **Échap** : Fermer la modal

## 🐛 Notes de développement

- Les paramètres sont globaux (non liés à un projet spécifique)
- La police est chargée de manière asynchrone
- Le store utilise `localStorage` pour la persistance
- L'aperçu en direct est une simulation (pas de vraie bande rythmo)

## 🔮 Améliorations futures possibles

- [ ] Paramètres par projet (au lieu de globaux)
- [ ] Plus de polices Google Fonts (API complète)
- [ ] Préréglages de thèmes (clair/sombre/personnalisés)
- [ ] Export/Import de paramètres
- [ ] Aperçu avec vraies données du projet
- [ ] Animation de transition entre les modes
- [ ] Raccourci clavier pour ouvrir les paramètres
