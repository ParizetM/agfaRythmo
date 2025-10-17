---
applyTo: '**/*.vue'
---

# Instructions de Design - Modal Moderne AgfaRythmo

**Date de création** : 17 octobre 2025  
**Composant de référence** : `CreateProjectModal.vue`

## 🎨 Philosophie de Design

Le design des modals d'AgfaRythmo suit une approche **moderne, élégante et professionnelle** avec :
- **Glassmorphism subtil** : backdrop blur pour profondeur
- **Gradients dynamiques** : bleu-violet pour les accents
- **Animations fluides** : transitions à 300ms
- **Feedback visuel riche** : états hover, focus, disabled clairs
- **Accessibilité** : contrastes élevés, navigation clavier

## 📐 Structure Architecturale

### 1. Container Modal (Teleport + Overlay)

```vue
<Teleport to="body">
  <Transition name="modal">
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <!-- Backdrop avec blur -->
      <div class="absolute inset-0 bg-black/70 backdrop-blur-sm"></div>
      
      <!-- Contenu modal -->
      <div class="relative bg-gradient-to-br from-agfa-bg-secondary to-agfa-bg-tertiary 
                  rounded-3xl shadow-2xl max-w-2xl w-full overflow-hidden 
                  border border-gray-700/50">
        <!-- Contenu ici -->
      </div>
    </div>
  </Transition>
</Teleport>
```

**Caractéristiques clés** :
- `Teleport to="body"` : évite les problèmes de z-index
- `backdrop-blur-sm` : effet glassmorphism moderne
- `bg-black/70` : overlay sombre à 70% d'opacité
- `rounded-3xl` : coins très arrondis (24px)
- `shadow-2xl` : ombre portée importante pour profondeur
- `border-gray-700/50` : bordure subtile semi-transparente

### 2. Header Élégant

```vue
<div class="relative px-8 pt-8 pb-6 border-b border-gray-700/50">
  <div class="flex items-center gap-4">
    <!-- Icône badge avec gradient -->
    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500 to-purple-600 
                flex items-center justify-center shadow-lg">
      <svg class="w-8 h-8 text-white"><!-- Icon SVG --></svg>
    </div>
    
    <!-- Titres -->
    <div class="flex-1">
      <h3 class="text-3xl font-bold text-white">Titre Principal</h3>
      <p class="text-gray-400 text-sm mt-1">Sous-titre explicatif</p>
    </div>
    
    <!-- Bouton fermeture -->
    <button class="w-10 h-10 rounded-full hover:bg-gray-700/50 
                   flex items-center justify-center transition-all duration-200 
                   text-gray-400 hover:text-white">
      <svg class="w-6 h-6"><!-- X icon --></svg>
    </button>
  </div>
</div>
```

**Points clés** :
- **Badge icône** : gradient bleu-violet, arrondi à 16px (rounded-2xl)
- **Titre** : text-3xl (30px), font-bold, blanc pur
- **Sous-titre** : text-sm (14px), gray-400, mt-1 (4px)
- **Bouton X** : rounded-full, hover subtil avec bg-gray-700/50

### 3. Formulaire avec Spacing Cohérent

```vue
<form @submit.prevent="handleSubmit" class="px-8 py-6 space-y-6">
  <!-- Chaque champ suit ce pattern -->
  <div class="space-y-2">
    <label class="block text-sm font-semibold text-gray-300">
      Label <span class="text-red-400">*</span>
    </label>
    <input class="w-full px-4 py-3 bg-agfa-bg-primary border border-gray-600 
                  rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent 
                  outline-none transition-all duration-300 text-white 
                  placeholder-gray-500 hover:border-gray-500" />
  </div>
</form>
```

**Spacing System** :
- `px-8` : padding horizontal du form (32px)
- `py-6` : padding vertical du form (24px)
- `space-y-6` : espacement entre champs (24px)
- `space-y-2` : espacement label-input (8px)

**Style des Inputs** :
- `rounded-xl` : coins arrondis à 12px
- `px-4 py-3` : padding confortable (16px horizontal, 12px vertical)
- `bg-agfa-bg-primary` : fond sombre (#121827)
- `border-gray-600` : bordure neutre par défaut
- `hover:border-gray-500` : bordure plus claire au hover
- `focus:ring-2 focus:ring-blue-500` : ring bleu au focus
- `focus:border-transparent` : supprime bordure au focus (remplacée par ring)

### 4. Zone de Drop Drag & Drop

```vue
<div @click="triggerFileInput"
     @dragover.prevent="isDragging = true"
     @dragleave.prevent="isDragging = false"
     @drop.prevent="handleDrop"
     :class="[
       'relative border-2 border-dashed rounded-xl p-8 transition-all duration-300 cursor-pointer',
       isDragging 
         ? 'border-blue-500 bg-blue-500/10' 
         : 'border-gray-600 hover:border-gray-500 bg-agfa-bg-primary/50'
     ]">
  
  <!-- État vide -->
  <div v-if="!formData.video" class="text-center">
    <div class="w-16 h-16 mx-auto mb-4 rounded-2xl 
                bg-gradient-to-br from-blue-500/20 to-purple-600/20 
                flex items-center justify-center">
      <svg class="w-8 h-8 text-blue-400"><!-- Upload icon --></svg>
    </div>
    <p class="text-white font-medium mb-1">Cliquez pour sélectionner ou glissez-déposez</p>
    <p class="text-gray-400 text-sm">Format supporté : MP4 uniquement</p>
  </div>
  
  <!-- État avec fichier -->
  <div v-else class="text-center">
    <div class="w-16 h-16 mx-auto mb-4 rounded-2xl 
                bg-gradient-to-br from-green-500/20 to-emerald-600/20 
                flex items-center justify-center">
      <svg class="w-8 h-8 text-green-400"><!-- Video icon --></svg>
    </div>
    <p class="text-white font-medium mb-1">{{ formData.video.name }}</p>
    <p class="text-gray-400 text-sm">{{ formatFileSize(formData.video.size) }}</p>
    <button class="mt-3 text-red-400 hover:text-red-300 text-sm font-medium">
      Supprimer
    </button>
  </div>
</div>
```

**Caractéristiques** :
- `border-2 border-dashed` : bordure pointillée épaisse
- États visuels :
  - **Normal** : border-gray-600, bg-agfa-bg-primary/50
  - **Hover** : border-gray-500
  - **Dragging** : border-blue-500, bg-blue-500/10
- **Icône badge** : gradient transparent (20% opacity)
- **Transitions** : 300ms sur toutes propriétés

### 5. Barre de Progression Animée

```vue
<div v-if="uploading" class="space-y-3">
  <!-- Label et pourcentage -->
  <div class="flex items-center justify-between text-sm">
    <span class="text-gray-300 font-medium">Upload en cours...</span>
    <span class="text-blue-400 font-bold">{{ uploadProgress }}%</span>
  </div>
  
  <!-- Barre -->
  <div class="h-3 bg-gray-700 rounded-full overflow-hidden">
    <div class="h-full bg-gradient-to-r from-blue-500 to-purple-600 
                rounded-full transition-all duration-300 ease-out 
                relative overflow-hidden"
         :style="{ width: `${uploadProgress}%` }">
      <!-- Effet shimmer -->
      <div class="absolute inset-0 bg-gradient-to-r 
                  from-transparent via-white/30 to-transparent 
                  shimmer"></div>
    </div>
  </div>
  
  <!-- Message d'aide -->
  <p class="text-center text-gray-400 text-xs">
    Veuillez patienter pendant l'upload de votre vidéo...
  </p>
</div>
```

**Animation Shimmer** :
```css
@keyframes shimmer {
  0% { transform: translateX(-100%); }
  100% { transform: translateX(100%); }
}

.shimmer {
  animation: shimmer 2s infinite;
}
```

**Design de la barre** :
- `h-3` : hauteur de 12px (confortable)
- `bg-gray-700` : fond sombre pour contraste
- `rounded-full` : coins complètement arrondis
- **Gradient** : bleu vers violet (from-blue-500 to-purple-600)
- **Shimmer** : brillance qui se déplace toutes les 2s
- **Transition** : ease-out sur la largeur (300ms)

### 6. Boutons d'Action

```vue
<div class="flex gap-4 pt-4">
  <!-- Bouton annuler -->
  <button type="button"
          :disabled="uploading"
          class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 
                 disabled:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed 
                 text-white font-semibold rounded-xl transition-all duration-300 
                 transform hover:scale-[1.02] active:scale-[0.98]">
    Annuler
  </button>
  
  <!-- Bouton principal (submit) -->
  <button type="submit"
          :disabled="uploading || !formData.name || !formData.video"
          class="flex-1 px-6 py-3 
                 bg-gradient-to-r from-blue-500 to-purple-600 
                 hover:from-blue-600 hover:to-purple-700 
                 disabled:from-gray-600 disabled:to-gray-700 
                 disabled:cursor-not-allowed 
                 text-white font-semibold rounded-xl 
                 transition-all duration-300 
                 transform hover:scale-[1.02] active:scale-[0.98] 
                 shadow-lg shadow-blue-500/25 disabled:shadow-none">
    {{ uploading ? 'Création...' : 'Créer le projet' }}
  </button>
</div>
```

**Effets interactifs** :
- `hover:scale-[1.02]` : grossit légèrement au hover (+2%)
- `active:scale-[0.98]` : réduit au clic (-2%) pour effet "pressé"
- `shadow-lg shadow-blue-500/25` : ombre bleue sur bouton principal
- **États disabled** :
  - Opacité réduite (50%)
  - Curseur not-allowed
  - Pas de shadow
  - Gradient gris

## 🎭 Animations et Transitions

### Transition du Modal

```vue
<Transition name="modal">
  <!-- Contenu -->
</Transition>
```

```css
/* Animations de transition du modal */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-active > div:last-child,
.modal-leave-active > div:last-child {
  transition: transform 0.3s ease, opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-from > div:last-child,
.modal-leave-to > div:last-child {
  transform: scale(0.9);
  opacity: 0;
}
```

**Comportement** :
- **Entrée** : fade-in + scale (0.9 → 1.0) sur 300ms
- **Sortie** : fade-out + scale (1.0 → 0.9) sur 300ms
- **Easing** : ease (courbe naturelle)

### Transitions sur les Éléments

Toutes les transitions utilisent :
```css
transition-all duration-300
```

Soit **300ms** pour tous les changements CSS (couleur, taille, bordure, etc.)

## 🎨 Palette de Couleurs Spécifique

### Backgrounds
- `agfa-bg-primary` : #121827 (fond principal, très sombre)
- `agfa-bg-secondary` : #202937 (fond modal, moins sombre)
- `agfa-bg-tertiary` : #2a3441 (cartes surélevées, plus clair)

### Accents
- **Bleu** : blue-500 (#3b82f6), blue-600 (#2563eb), blue-400 (#60a5fa)
- **Violet** : purple-600 (#9333ea), purple-700 (#7e22ce)
- **Vert** : green-400 (#4ade80), green-500 (#22c55e)
- **Rouge** : red-400 (#f87171), red-300 (#fca5a5)

### Neutres
- **Texte principal** : white (#ffffff)
- **Texte secondaire** : gray-300 (#d1d5db)
- **Texte désactivé** : gray-400 (#9ca3af)
- **Bordures** : gray-600 (#4b5563), gray-700 (#374151)

### Gradients Signature
```css
/* Gradient principal (icônes, boutons) */
bg-gradient-to-br from-blue-500 to-purple-600

/* Gradient hover */
hover:from-blue-600 hover:to-purple-700

/* Gradient transparent (backgrounds) */
from-blue-500/20 to-purple-600/20
```

## 📏 Système de Spacing

### Padding/Margin Standards
- **XS** : 2 (8px) - espaces très serrés
- **SM** : 3 (12px) - espaces inputs
- **MD** : 4 (16px) - espaces standards
- **LG** : 6 (24px) - espaces sections
- **XL** : 8 (32px) - padding conteneurs

### Border Radius
- **SM** : rounded-lg (8px) - petits éléments
- **MD** : rounded-xl (12px) - inputs, boutons
- **LG** : rounded-2xl (16px) - badges, icônes
- **XL** : rounded-3xl (24px) - modals, cartes

### Tailles d'Icônes
- **SM** : w-4 h-4 (16px) - icônes inline
- **MD** : w-6 h-6 (24px) - icônes boutons
- **LG** : w-8 h-8 (32px) - icônes badges
- **XL** : w-14 h-14 (56px) - badges principaux

## ✨ Règles de Cohérence

### Pour Créer un Nouveau Modal

1. **Toujours utiliser** `Teleport to="body"`
2. **Backdrop** : `bg-black/70 backdrop-blur-sm`
3. **Container** : `rounded-3xl shadow-2xl border border-gray-700/50`
4. **Header** : badge gradient + titre 3xl + sous-titre gray-400
5. **Form** : `px-8 py-6 space-y-6`
6. **Inputs** : `rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500`
7. **Boutons** : gradient principal, scale au hover, disabled avec opacity
8. **Animations** : transition 300ms ease

### États Visuels Obligatoires

Chaque élément interactif doit avoir :
- ✅ **Hover** : changement de couleur ou bordure
- ✅ **Focus** : ring bleu visible (accessibilité)
- ✅ **Disabled** : opacity 50% + cursor-not-allowed
- ✅ **Active** : scale-[0.98] pour feedback tactile

### Accessibilité

- ✅ Labels avec `for` et id correspondant
- ✅ Champs requis avec `<span class="text-red-400">*</span>`
- ✅ Bouton fermeture avec aria-label
- ✅ Focus visible avec ring-2
- ✅ Texte avec contraste suffisant (WCAG AA minimum)

## 🚀 Exemples d'Utilisation

### Modal Simple

```vue
<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm"></div>
        <div class="relative bg-gradient-to-br from-agfa-bg-secondary to-agfa-bg-tertiary 
                    rounded-3xl shadow-2xl max-w-md w-full">
          <div class="px-8 pt-8 pb-6 border-b border-gray-700/50">
            <h3 class="text-2xl font-bold text-white">Titre</h3>
          </div>
          <div class="px-8 py-6">
            <!-- Contenu -->
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
```

### Modal avec Formulaire

```vue
<form @submit.prevent="handleSubmit" class="px-8 py-6 space-y-6">
  <div class="space-y-2">
    <label for="name" class="block text-sm font-semibold text-gray-300">
      Nom <span class="text-red-400">*</span>
    </label>
    <input
      id="name"
      v-model="formData.name"
      type="text"
      required
      class="w-full px-4 py-3 bg-agfa-bg-primary border border-gray-600 
             rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent 
             outline-none transition-all duration-300 text-white 
             placeholder-gray-500 hover:border-gray-500"
    />
  </div>
  
  <div class="flex gap-4 pt-4">
    <button type="button" @click="$emit('close')"
            class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 
                   text-white font-semibold rounded-xl transition-all duration-300">
      Annuler
    </button>
    <button type="submit"
            class="flex-1 px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 
                   hover:from-blue-600 hover:to-purple-700 text-white font-semibold 
                   rounded-xl transition-all duration-300 shadow-lg shadow-blue-500/25">
      Valider
    </button>
  </div>
</form>
```

## 📋 Checklist pour un Modal Parfait

- [ ] Teleport vers body
- [ ] Backdrop avec blur
- [ ] Container avec gradient from-to
- [ ] Border radius 3xl (24px)
- [ ] Shadow 2xl
- [ ] Header avec badge gradient
- [ ] Spacing cohérent (px-8, py-6, space-y-6)
- [ ] Inputs avec rounded-xl et focus:ring-2
- [ ] Boutons avec gradient et hover:scale
- [ ] États disabled gérés
- [ ] Transitions 300ms
- [ ] Animation modal (scale + fade)
- [ ] Accessibilité (labels, required, focus)

---

**Maintenu par** : Martin P. (@ParizetM)  
**Référence** : `CreateProjectModal.vue` - Modal de création de projet  
**Version** : 1.0.0
