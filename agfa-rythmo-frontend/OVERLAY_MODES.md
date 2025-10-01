# Comportement des modes d'affichage des bandes rythmo

## 📺 Mode "Sous la vidéo" (Under)

```
┌─────────────────────────────────┐
│                                 │
│         VIDÉO                   │
│      (s'ajuste en hauteur)      │
│                                 │
└─────────────────────────────────┘  ← Bas de la vidéo
┌─────────────────────────────────┐  ← Haut de la bande (collé)
│     BANDE RYTHMO                │
│   (séparée, en dessous)         │
└─────────────────────────────────┘
```

**Caractéristiques :**
- ✅ La bande est **en dessous** de la vidéo
- ✅ Le **haut de la bande est collé au bas de la vidéo**
- ✅ La vidéo **s'ajuste en hauteur** pour laisser de la place
- ✅ Pas de chevauchement
- ✅ Opacité 100%
- ✅ Visible en mode plein écran

## 🎭 Mode "Par-dessus la vidéo" (Over)

```
┌─────────────────────────────────┐
│                                 │
│         VIDÉO                   │
│      (pleine hauteur)           │
│  ┌─────────────────────────┐   │
│  │   BANDE RYTHMO          │   │  ← Ancrée en bas
│  │   (overlay transparent) │   │
└──┴─────────────────────────┴───┘
```

**Caractéristiques :**
- ✅ La bande est **superposée sur la vidéo**
- ✅ Ancrée en **bas de la vidéo**
- ✅ La vidéo **garde sa taille complète**
- ✅ Chevauchement avec transparence
- ✅ Opacité 95% + effet de flou
- ✅ Effet cinématique

## 🎨 Aperçu dans la modal de paramètres

L'aperçu dans `ProjectSettingsModal.vue` reproduit **exactement** le même comportement que `FinalPreviewView.vue` :

### Structure HTML identique :
- Mode **Under** : `.preview-wrapper.with-band-below` + bande en dehors du container vidéo
- Mode **Over** : bande `position: absolute` à l'intérieur du container vidéo

### Styles CSS identiques :
- Mode **Under** : `position: relative`, `margin-top: 0`
- Mode **Over** : `position: absolute`, `bottom: 0`, `opacity: 0.95`, `backdrop-filter: blur(2px)`

## 🔧 Implémentation technique

### FinalPreviewView.vue
```vue
<div class="video-wrapper" :class="{ 'with-band-below': overlayPosition === 'under' }">
  <div class="video-container">
    <video />
    <!-- Bande overlay si mode 'over' -->
    <div v-if="overlayPosition === 'over'" class="preview-rythmo overlay-mode">
  </div>
  <!-- Bande séparée si mode 'under' -->
  <div v-if="overlayPosition === 'under'" class="preview-rythmo below-mode">
</div>
```

### ProjectSettingsModal.vue
```vue
<div class="preview-wrapper" :class="{ 'with-band-below': overlayPosition === 'under' }">
  <div class="preview-video-container">
    <div class="preview-video-area">
      <!-- Placeholder vidéo -->
      <!-- Bande overlay si mode 'over' -->
      <div v-if="overlayPosition === 'over'" class="preview-rythmo-band overlay-mode">
    </div>
  </div>
  <!-- Bande séparée si mode 'under' -->
  <div v-if="overlayPosition === 'under'" class="preview-rythmo-band below-mode">
</div>
```

## ✅ Résultat

Les deux vues (aperçu modal et aperçu final) utilisent maintenant **la même logique** :
- Même structure HTML
- Mêmes classes CSS
- Même comportement visuel
- Passage fluide d'un mode à l'autre avec transition CSS

L'utilisateur voit dans l'aperçu de la modal **exactement** ce qu'il verra dans l'aperçu final ! 🎯
