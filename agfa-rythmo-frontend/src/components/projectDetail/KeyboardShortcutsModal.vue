<template>
  <Transition name="modal">
    <div
      v-if="show"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm p-4"
      @click="closeModal"
    >
      <div
        class="bg-[#1a1f2e] border border-gray-700/50 rounded-2xl shadow-2xl w-full max-w-6xl max-h-[90vh] overflow-hidden flex flex-col"
        @click.stop
      >
        <!-- Header avec bouton fermer -->
        <div class="relative border-b border-gray-700/50 bg-gradient-to-r from-[#1a1f2e] to-[#202937] px-6 py-5">
          <div class="text-center">
            <h1 class="text-3xl font-bold text-white mb-2">Raccourcis clavier</h1>
            <p class="text-sm text-gray-400">
              <span class="inline-flex items-center gap-2">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
                {{ osName }}
              </span>
            </p>
          </div>
          <button
            @click="closeModal"
            class="absolute top-5 right-5 p-2 rounded-lg text-gray-400 hover:text-white hover:bg-white/10 transition-all duration-200"
            title="Fermer (Échap)"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Content scrollable -->
        <div class="flex-1 overflow-y-auto p-6 md:p-8">
          <!-- Grille responsive -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <!-- Navigation -->
            <div class="space-y-3">
              <h3 class="text-xs font-bold text-[#8455F6] uppercase tracking-wider mb-4 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
                Navigation
              </h3>
              <div class="space-y-2">
                <div class="shortcut-item">
                  <kbd class="key">Espace</kbd>
                  <span>Lecture / Pause</span>
                </div>
                <div class="shortcut-item">
                  <kbd class="key">Q</kbd>
                  <span>Frame précédente</span>
                </div>
                <div class="shortcut-item">
                  <kbd class="key">←</kbd>
                  <span>-1 seconde</span>
                </div>
                <div class="shortcut-item">
                  <kbd class="key">→</kbd>
                  <span>+1 seconde</span>
                </div>
                <div class="shortcut-item">
                  <kbd class="key">E</kbd>
                  <span>Frame suivante</span>
                </div>
                <div class="shortcut-item">
                  <kbd class="key">Shift + ←</kbd>
                  <span>Plan précédent</span>
                </div>
                <div class="shortcut-item">
                  <kbd class="key">Shift + →</kbd>
                  <span>Plan suivant</span>
                </div>
                <div class="shortcut-item">
                  <kbd class="key">↑</kbd>
                  <span>Timecode précédent</span>
                </div>
                <div class="shortcut-item">
                  <kbd class="key">↓</kbd>
                  <span>Timecode suivant</span>
                </div>
              </div>
            </div>

            <!-- Interface -->
            <div class="space-y-3">
              <h3 class="text-xs font-bold text-[#8455F6] uppercase tracking-wider mb-4 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                </svg>
                Interface
              </h3>
              <div class="space-y-2">
                <div class="shortcut-item">
                  <kbd class="key">T</kbd>
                  <span>Ajouter timecode</span>
                </div>
                <div class="shortcut-item">
                  <kbd class="key">S</kbd>
                  <span>Ajouter scène</span>
                </div>
                <div class="shortcut-item">
                  <kbd class="key">,</kbd>
                  <span>Aide (ouvrir/fermer)</span>
                </div>
                <div class="shortcut-item">
                  <kbd class="key">F</kbd>
                  <span>Aperçu final</span>
                </div>
                <div class="shortcut-item">
                  <kbd class="key">Échap</kbd>
                  <span>Fermer / Retour</span>
                </div>
              </div>
            </div>

            <!-- Lignes -->
            <div class="space-y-3">
              <h3 class="text-xs font-bold text-[#8455F6] uppercase tracking-wider mb-4 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                Lignes
              </h3>
              <div class="space-y-2">
                <div class="shortcut-item">
                  <kbd class="key">Shift + 1-6</kbd>
                  <span>Sélectionner ligne</span>
                </div>
              </div>
            </div>

            <!-- Personnages -->
            <div class="space-y-3">
              <h3 class="text-xs font-bold text-[#8455F6] uppercase tracking-wider mb-4 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                Personnages
              </h3>
              <div class="space-y-2">
                <div class="shortcut-item">
                  <kbd class="key">1 - 9, 0</kbd>
                  <span>Sélectionner personnage</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Section séparateur de texte -->
          <div class="mt-8 pt-6 border-t border-gray-700/50">
            <div class="bg-gradient-to-r from-[#8455F6]/10 to-transparent rounded-xl p-5 border border-[#8455F6]/20">
              <h3 class="text-sm font-semibold text-[#8455F6] mb-3 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                </svg>
                Séparateur de texte
              </h3>
              <div class="flex flex-wrap items-center gap-3 text-sm text-gray-300">
                <span>Utilisez</span>
                <kbd class="key-inline">|</kbd>
                <span>pour diviser le texte</span>
                <span class="text-gray-500">•</span>
                <span>Ex:</span>
                <code class="px-3 py-1 bg-[#384152] text-gray-200 rounded-lg font-mono text-xs border border-gray-600">Gauche|Droite</code>
              </div>
              <p class="text-xs text-gray-500 mt-3">
                Glissez la barre verticale dans l'éditeur pour redimensionner les deux parties
              </p>
            </div>
          </div>

          <!-- Note importante -->
          <div class="mt-6 bg-yellow-500/10 border border-yellow-500/30 rounded-xl p-4">
            <div class="flex gap-3">
              <svg class="w-5 h-5 text-yellow-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
              </svg>
              <p class="text-xs text-yellow-200/90 leading-relaxed">
                <strong class="font-semibold">Important :</strong> Pendant la saisie de texte, tous les raccourcis sont désactivés. La lecture vidéo est automatiquement mise en pause et reprend quand vous quittez le champ.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import { computed, onUnmounted, watch } from 'vue'

interface Props {
  show: boolean
}
const props = defineProps<Props>()

const emit = defineEmits<{
  close: []
}>()

// Détection de l'OS
const isMac = computed(() => {
  return navigator.platform.toUpperCase().indexOf('MAC') >= 0
})

const osName = computed(() => {
  if (navigator.platform.toUpperCase().indexOf('MAC') >= 0) return 'macOS'
  if (navigator.platform.toUpperCase().indexOf('WIN') >= 0) return 'Windows'
  if (navigator.platform.toUpperCase().indexOf('LINUX') >= 0) return 'Linux'
  return 'Système'
})

function closeModal() {
  emit('close')
}

// Gestion des raccourcis clavier pour fermer le modal
function handleKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape') {
    closeModal()
  }
}

// Écouter les événements clavier uniquement quand le modal est ouvert
let keydownListener: ((event: KeyboardEvent) => void) | null = null

watch(() => props.show, (isVisible) => {
  if (isVisible) {
    keydownListener = handleKeydown
    window.addEventListener('keydown', keydownListener)
  } else if (keydownListener) {
    window.removeEventListener('keydown', keydownListener)
    keydownListener = null
  }
})

onUnmounted(() => {
  if (keydownListener) {
    window.removeEventListener('keydown', keydownListener)
  }
})
</script>

<style scoped>
/* Transitions pour le modal */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-active > div,
.modal-leave-active > div {
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.modal-enter-from > div,
.modal-leave-to > div {
  transform: scale(0.95) translateY(-10px);
  opacity: 0;
}

/* Item de raccourci */
.shortcut-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem 0;
  color: #e5e7eb;
  font-size: 0.875rem;
  line-height: 1.5;
}

.shortcut-item span {
  color: #d1d5db;
}

/* Touches du clavier */
.key {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 2.5rem;
  padding: 0.375rem 0.75rem;
  background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
  border: 1px solid #4a5568;
  border-bottom-width: 3px;
  border-radius: 0.375rem;
  color: #ffffff;
  font-size: 0.813rem;
  font-weight: 600;
  font-family: ui-monospace, SFMono-Regular, "SF Mono", Consolas, "Liberation Mono", Menlo, monospace;
  text-align: center;
  white-space: nowrap;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
  flex-shrink: 0;
}

.key-inline {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.25rem 0.5rem;
  background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
  border: 1px solid #4a5568;
  border-bottom-width: 2px;
  border-radius: 0.25rem;
  color: #ffffff;
  font-size: 0.75rem;
  font-weight: 600;
  font-family: ui-monospace, SFMono-Regular, "SF Mono", Consolas, "Liberation Mono", Menlo, monospace;
  white-space: nowrap;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
}

/* Scrollbar personnalisée */
.overflow-y-auto {
  scrollbar-width: thin;
  scrollbar-color: #4a5568 #1a1f2e;
}

.overflow-y-auto::-webkit-scrollbar {
  width: 8px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: #1a1f2e;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #4a5568;
  border-radius: 4px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #5a6578;
}

/* Responsive */
@media (max-width: 768px) {
  .shortcut-item {
    font-size: 0.813rem;
  }

  .key {
    min-width: 2rem;
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
  }

  .key-inline {
    padding: 0.2rem 0.4rem;
    font-size: 0.7rem;
  }
}
</style>
