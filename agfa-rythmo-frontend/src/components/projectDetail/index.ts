// Export des composables
export { useRythmoCalculations } from './composables/useRythmoCalculations'
export { useRythmoStore } from './composables/useRythmoState'
export { useRythmoInteractions } from './composables/useRythmoInteractions'
export { useSmoothScroll } from './composables/useSmoothScroll'

// Phase 3 - Composables avancés
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

// Export des composants UI
export { default as RythmoBlock } from './ui/RythmoBlock.vue'
export { default as RythmoCharacterTag } from './ui/RythmoCharacterTag.vue'
export { default as RythmoTicks } from './ui/RythmoTicks.vue'
export { default as RythmoCursor } from './ui/RythmoCursor.vue'
export { default as SceneChangeMarkers } from './ui/SceneChangeMarkers.vue'
export { default as RythmoMovePreview } from './ui/RythmoMovePreview.vue'
export { default as RythmoTrack } from './ui/RythmoTrack.vue'

// Export des contrôles
export { default as RythmoControls } from './controls/RythmoControls.vue'

// Export des modals
export { default as TimecodeModal } from './modals/TimecodeModal.vue'
export { default as CharacterModal } from './modals/CharacterModal.vue'
export { default as SceneChangeModal } from './modals/SceneChangeModal.vue'

// Export des listes
export { default as TimelineView } from './lists/TimelineView.vue'
export { default as CharactersList } from './lists/CharactersList.vue'
export { default as SceneChangesList } from './lists/SceneChangesList.vue'

// Export des conteneurs
export { default as MultiRythmoContainer } from './containers/MultiRythmoContainer.vue'
export { default as VideoPlayerContainer } from './containers/VideoPlayerContainer.vue'
export { default as ProjectDetailView } from './containers/ProjectDetailView.vue'

// Export des types principaux
export type { Timecode } from './composables/useRythmoCalculations'
export type { Character, Project } from './composables/useRythmoState'
