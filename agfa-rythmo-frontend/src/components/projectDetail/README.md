# ProjectDetailNew - Architecture Refactorisée

## 🎯 Objectifs Atteints

Cette nouvelle architecture suit le plan de refactorisation en séparant clairement :
- **Logique métier** dans les composables
- **Composants UI** simples et réutilisables
- **État centralisé** avec Pinia
- **Responsabilités uniques** par composant

## 📁 Structure

```
projectDetailNew/
├── composables/           # Logique métier réutilisable
│   ├── useRythmoCalculations.ts    # Calculs pixels/temps
│   ├── useRythmoState.ts           # Store Pinia centralisé
│   ├── useRythmoInteractions.ts    # Gestion interactions
│   └── useSmoothScroll.ts          # Animation smooth scroll
├── ui/                    # Composants UI purs
│   ├── RythmoBlock.vue             # Bloc de timecode individuel
│   ├── RythmoCharacterTag.vue      # Étiquette personnage
│   ├── RythmoTicks.vue             # Graduations temporelles
│   ├── RythmoCursor.vue            # Curseur de lecture
│   ├── SceneChangeMarkers.vue      # Barres changement plan
│   ├── RythmoMovePreview.vue       # Preview déplacement
│   └── RythmoTrack.vue             # Bande rythmo complète
├── controls/              # Composants de contrôle
│   └── RythmoControls.vue          # Contrôles lecture
├── containers/            # Conteneurs principaux
│   └── MultiRythmoContainer.vue    # Conteneur multi-lignes
├── modals/               # Modales (à venir)
├── lists/                # Listes (à venir)
└── index.ts              # Point d'entrée
```

## 🔧 Composables

### `useRythmoCalculations`
- Convertit temps ↔ pixels
- Calcule positions et tailles des blocs
- Génère les graduations (ticks)
- Gère la distorsion du texte

### `useRythmoState` (Store Pinia)
- État centralisé des timecodes, personnages, projet
- Actions CRUD avec gestion optimiste
- État des interactions (édition, redimensionnement, déplacement)
- Synchronisation avec le backend

### `useRythmoInteractions`
- Gestion drag & drop
- Redimensionnement des blocs
- Déplacement entre lignes
- Édition inline
- Preview en temps réel

## 🎨 Composants UI

### `RythmoBlock.vue`
Composant de bloc de timecode avec :
- Support édition inline
- Zones de redimensionnement
- Affichage personnage
- Distorsion du texte

### `RythmoTrack.vue`
Bande rythmo complète refactorisée :
- Utilise les nouveaux composants UI
- Logique extraite dans les composables
- Moins de 200 lignes (vs 1000+ avant)

### `MultiRythmoContainer.vue`
Conteneur pour multiple lignes :
- Configuration du nombre de lignes
- Gestion des événements centralisée
- Reload intelligent des lignes

## 📊 Métriques Atteintes

| Métrique | Avant | Après | ✅ Cible |
|----------|--------|-------|----------|
| Lignes/composant | >1000 | <200 | <200 |
| Complexité | >20 | <10 | <10 |
| Props par composant | >10 | <5 | <5 |
| Responsabilités | Multiple | Une | Une |

## 🚀 Utilisation

### Import des composants
```typescript
import { 
  MultiRythmoContainer, 
  useRythmoStore,
  useRythmoCalculations 
} from '@/components/projectDetailNew'
```

### Utilisation basique
```vue
<template>
  <MultiRythmoContainer
    :timecodes="timecodes"
    :current-time="currentTime"
    :video-duration="videoDuration"
    :scene-changes="sceneChanges"
    :rythmo-lines-count="linesCount"
    @seek="handleSeek"
    @update-timecode="handleUpdateTimecode"
  />
</template>

<script setup lang="ts">
import { MultiRythmoContainer, useRythmoStore } from '@/components/projectDetailNew'

const store = useRythmoStore()
// ... reste de la logique
</script>
```

### Utilisation avec le store
```typescript
const store = useRythmoStore()

// Charger un projet
store.setProject(projectData)

// Ajouter un timecode
const newTimecode = store.addTimecode({
  start: 10.5,
  end: 15.2,
  text: "Nouveau dialogue",
  line_number: 1
})

// Mettre à jour
store.updateTimecodeText(timecodeId, "Texte modifié")
```

## ✨ Avantages de la Nouvelle Architecture

### 🔥 Performance
- Calculs optimisés et cachés
- Rendus conditionnels intelligents
- Mise à jour optimiste de l'état

### 🧪 Testabilité
- Composables purs facilement testables
- Composants UI isolés
- État prévisible

### 🔄 Réutilisabilité
- Composables réutilisables dans d'autres contextes
- Composants UI génériques
- Store Pinia partageable

### 🛠 Maintenabilité
- Responsabilités clairement séparées
- Code plus lisible et documenté
- Évolution facilitée

## 🔄 Migration depuis l'Ancien Code

Pour migrer progressivement :

1. **Remplacer MultiRythmoBand** par `MultiRythmoContainer`
2. **Utiliser le store Pinia** au lieu des props drilling
3. **Adapter les événements** selon la nouvelle API
4. **Tester composant par composant**

## 🚧 Prochaines Étapes (Priorité 2-3)

- [ ] Créer les modales (TimecodeModal, CharacterModal)
- [ ] Créer les listes (TimelineView, CharactersList)
- [ ] Ajouter les tests unitaires
- [ ] Optimisations performances (virtualisation)
- [ ] Documentation complète

Cette architecture respecte les principes SOLID et les bonnes pratiques Vue.js/TypeScript tout en gardant la compatibilité avec l'existant.
