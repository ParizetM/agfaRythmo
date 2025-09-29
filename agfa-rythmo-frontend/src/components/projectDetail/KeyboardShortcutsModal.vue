<template>
  <Transition name="modal">
    <div
      v-if="show"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
      @click="closeModal"
    >
      <div
        class="bg-agfa-dark border border-gray-600 rounded-lg shadow-xl w-[95vw] h-[90vh] mx-4 overflow-y-auto"
        @click.stop
      >
        <!-- Content -->
        <div class="p-8 relative">
          <!-- Bouton fermer -->
          <button
            @click="closeModal"
            class="absolute top-4 right-4 text-gray-400 hover:text-white transition-colors duration-200"
            title="Fermer (Échap)"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>

          <!-- Titre principal -->
          <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-white mb-2">Raccourcis AgfaRythmo</h1>
            <p class="text-gray-400">{{ osName }} • {{ cmdKey }} = {{ isMac ? 'Cmd' : 'Ctrl' }}</p>
          </div>

          <!-- Grille simple 4 colonnes -->
          <div class="grid grid-cols-4 gap-12">

            <!-- Navigation -->
            <div>
              <h3 class="text-lg font-semibold mb-6 text-center section-title">NAVIGATION</h3>
              <div class="space-y-4">
                <div class="shortcut-row">
                  <kbd class="key">Espace</kbd>
                  <span class="desc">Lecture/Pause</span>
                </div>
                <div class="shortcut-row">
                  <kbd class="key">←</kbd>
                  <span class="desc">-1 sec</span>
                </div>
                <div class="shortcut-row">
                  <kbd class="key">→</kbd>
                  <span class="desc">+1 sec</span>
                </div>
                <div class="shortcut-row">
                  <kbd class="key">Q</kbd>
                  <span class="desc">-1 frame</span>
                </div>
                <div class="shortcut-row">
                  <kbd class="key">E</kbd>
                  <span class="desc">+1 frame</span>
                </div>
              </div>
            </div>

            <!-- Interface -->
            <div>
              <h3 class="text-lg font-semibold mb-6 text-center section-title">INTERFACE</h3>
              <div class="space-y-4">
                <div class="shortcut-row">
                  <kbd class="key">T</kbd>
                  <span class="desc">Ajouter timecode</span>
                </div>
                <div class="shortcut-row">
                  <kbd class="key">S</kbd>
                  <span class="desc">Ajouter scène</span>
                </div>
                <div class="shortcut-row">
                  <kbd class="key">,</kbd>
                  <span class="desc">Ouvrir/Fermer aide</span>
                </div>
                <div class="shortcut-row">
                  <kbd class="key">F</kbd>
                  <span class="desc">Aperçu final</span>
                </div>
                <div class="shortcut-row">
                  <kbd class="key">Échap</kbd>
                  <span class="desc">Fermer / Retour</span>
                </div>
              </div>
            </div>

            <!-- Lignes -->
            <div>
              <h3 class="text-lg font-semibold mb-6 text-center section-title">LIGNES</h3>
              <div class="space-y-4">
                <div class="shortcut-row">
                  <kbd class="key">Shift+1-6</kbd>
                  <span class="desc">Sélectionner ligne</span>
                </div>
              </div>
            </div>

            <!-- Personnages -->
            <div>
              <h3 class="text-lg font-semibold mb-6 text-center section-title">PERSONNAGES</h3>
              <div class="space-y-4">
                <div class="shortcut-row">
                  <kbd class="key">1-9, 0</kbd>
                  <span class="desc">Sélectionner personnage</span>
                </div>
              </div>
            </div>

          </div>

          <!-- Note simple -->
          <div class="text-center mt-12">
            <p class="text-xs text-gray-500">
              * Personnages et lignes : hors champs de saisie uniquement
            </p>
          </div>
        </div>


      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import { computed } from 'vue'

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

const isWindows = computed(() => {
  return navigator.platform.toUpperCase().indexOf('WIN') >= 0
})

const isLinux = computed(() => {
  return navigator.platform.toUpperCase().indexOf('LINUX') >= 0
})

const osName = computed(() => {
  if (isMac.value) return 'macOS'
  if (isWindows.value) return 'Windows'
  if (isLinux.value) return 'Linux'
  return 'Inconnu'
})

const cmdKey = computed(() => {
  return isMac.value ? 'Cmd' : 'Ctrl'
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
import { onUnmounted, watch } from 'vue'

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
.shortcut-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 0.75rem;
}

.key {
  background-color: #384152;
  color: #ffffff;
  padding: 0.375rem 0.75rem;
  border-radius: 0.25rem;
  font-size: 0.875rem;
  font-weight: 600;
  font-family: ui-monospace, SFMono-Regular, "SF Mono", Consolas, "Liberation Mono", Menlo, monospace;
  border: 1px solid #6b7280;
  min-width: 3rem;
  text-align: center;
  white-space: nowrap;
}

.desc {
  color: #e5e7eb;
  font-size: 0.875rem;
  font-weight: 400;
}

.section-title {
  color: #8455F6;
}

/* Transitions pour le modal */
.modal-enter-active, .modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from, .modal-leave-to {
  opacity: 0;
}

.modal-enter-active .bg-agfa-dark,
.modal-leave-active .bg-agfa-dark {
  transition: transform 0.3s ease;
}

.modal-enter-from .bg-agfa-dark,
.modal-leave-to .bg-agfa-dark {
  transform: scale(0.9);
}
</style>
