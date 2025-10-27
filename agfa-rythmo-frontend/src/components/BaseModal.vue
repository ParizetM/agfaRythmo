<template>
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        @click.self="handleBackdropClick"
      >
        <!-- Backdrop avec blur -->
        <div
          class="absolute inset-0 bg-black/70 backdrop-blur-sm"
          @click="handleBackdropClick"
        ></div>

        <!-- Modal -->
        <div
          :class="[
            'relative rounded-3xl shadow-2xl w-full overflow-hidden border border-gray-700/50 transform transition-all flex flex-col',
            'bg-agfa-dark-30',
            sizeClasses,
            heightClasses
          ]"
        >
          <!-- Header -->
          <div
            v-if="!hideHeader"
            class="relative px-4 sm:px-6 md:px-8 pt-6 sm:pt-8 pb-4 sm:pb-6 border-b border-gray-700/50 flex-shrink-0"
          >
            <div class="flex items-center gap-3 sm:gap-4">
              <!-- Icône badge (slot ou props) -->
              <div
                v-if="showIcon"
                :class="[
                  'w-12 h-12 sm:w-14 sm:h-14 rounded-2xl flex items-center justify-center shadow-lg flex-shrink-0',
                  iconGradient
                ]"
              >
                <slot name="icon">
                  <svg
                    class="w-6 h-6 sm:w-8 sm:h-8 text-white"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                  </svg>
                </slot>
              </div>

              <!-- Titre et sous-titre -->
              <div class="flex-1 min-w-0">
                <h3 class="text-xl sm:text-2xl md:text-3xl font-bold text-white leading-tight break-words">
                  <slot name="title">{{ title }}</slot>
                </h3>
                <p
                  v-if="subtitle || $slots.subtitle"
                  class="text-gray-400 text-xs sm:text-sm mt-1 line-clamp-2"
                >
                  <slot name="subtitle">{{ subtitle }}</slot>
                </p>
              </div>

              <!-- Bouton fermeture -->
              <button
                v-if="!hideClose"
                @click="handleClose"
                class="w-8 h-8 sm:w-10 sm:h-10 rounded-full hover:bg-gray-700/50 flex items-center justify-center transition-all duration-200 text-gray-400 hover:text-white flex-shrink-0"
                aria-label="Fermer"
              >
                <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M6 18L18 6M6 6l12 12"
                  />
                </svg>
              </button>
            </div>
          </div>

          <!-- Body avec scroll -->
          <div :class="['overflow-y-auto', scrollableClass]">
            <slot />
          </div>

          <!-- Footer (optionnel) -->
          <div
            v-if="$slots.footer"
            class="px-4 sm:px-6 md:px-8 pb-6 sm:pb-8 flex gap-3 sm:gap-4 flex-shrink-0 border-t border-gray-700/50 pt-4 sm:pt-6"
          >
            <slot name="footer" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { computed, watch, onUnmounted } from 'vue'

interface Props {
  show: boolean
  title?: string
  subtitle?: string
  size?: 'sm' | 'md' | 'lg' | 'xl' | '2xl' | 'full'
  maxHeight?: 'none' | 'screen' | '95vh' | '90vh' | '80vh' | '70vh'
  closeOnBackdrop?: boolean
  hideClose?: boolean
  hideHeader?: boolean
  showIcon?: boolean
  iconGradient?: string
}

const props = withDefaults(defineProps<Props>(), {
  title: '',
  subtitle: '',
  size: 'lg',
  maxHeight: '90vh',
  closeOnBackdrop: true,
  hideClose: false,
  hideHeader: false,
  showIcon: true,
  iconGradient: 'bg-gradient-to-br from-blue-500 to-purple-600'
})

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'update:show', value: boolean): void
}>()

const sizeClasses = computed(() => {
  const sizes = {
    sm: 'max-w-sm',
    md: 'max-w-md',
    lg: 'max-w-lg',
    xl: 'max-w-xl',
    '2xl': 'max-w-2xl',
    full: 'max-w-full mx-4'
  }
  return sizes[props.size]
})

const heightClasses = computed(() => {
  if (props.maxHeight === 'none') return ''
  if (props.maxHeight === 'screen') return 'max-h-screen'
  return `max-h-[${props.maxHeight}]`
})

const scrollableClass = computed(() => {
  // Padding approprié pour le contenu scrollable
  return 'px-4 sm:px-6 md:px-8 py-6'
})

function handleClose() {
  emit('close')
  emit('update:show', false)
}

function handleBackdropClick() {
  if (props.closeOnBackdrop) {
    handleClose()
  }
}

// Gestion de la touche Échap pour fermer le modal
function handleKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape' && props.closeOnBackdrop) {
    handleClose()
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
</style>
