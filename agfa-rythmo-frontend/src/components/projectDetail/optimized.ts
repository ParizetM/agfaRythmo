import { defineAsyncComponent } from 'vue'

/**
 * Lazy loading des composants modaux pour améliorer les performances
 * Phase 3: Optimisations et intégration
 */

// Modals avec lazy loading
export const TimecodeModal = defineAsyncComponent(
  () => import('./modals/TimecodeModal.vue')
)

export const CharacterModal = defineAsyncComponent(
  () => import('./modals/CharacterModal.vue')
)

export const SceneChangeModal = defineAsyncComponent(
  () => import('./modals/SceneChangeModal.vue')
)

// Listes avec lazy loading
export const TimelineView = defineAsyncComponent(
  () => import('./lists/TimelineView.vue')
)

export const CharactersList = defineAsyncComponent(
  () => import('./lists/CharactersList.vue')
)

export const SceneChangesList = defineAsyncComponent(
  () => import('./lists/SceneChangesList.vue')
)

// Conteneurs principaux (toujours chargés)
export { default as VideoPlayerContainer } from './containers/VideoPlayerContainer.vue'
export { default as MultiRythmoContainer } from './containers/MultiRythmoContainer.vue'
export { default as ProjectDetailView } from './containers/ProjectDetailView.vue'

// Composables optimisés
export { useRythmoStore } from './composables/useRythmoState'
export { useRythmoCalculations } from './composables/useRythmoCalculations'
export { useRythmoInteractions } from './composables/useRythmoInteractions'
export { useSmoothScroll } from './composables/useSmoothScroll'
export { useBackendSync } from './composables/useBackendSync'
export {
  useVirtualization,
  useDebounce,
  useThrottle,
  useLRUCache,
  useBatchUpdates,
  useIntersectionObserver,
  usePerformanceMonitor
} from './composables/usePerformanceOptimization'

// Types
export type { Timecode } from './composables/useRythmoCalculations'
export type { Character, Project } from './composables/useRythmoState'
