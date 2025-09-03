<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
    @click.self="closeModal"
  >
    <div class="bg-agfa-menu rounded-lg p-6 w-full max-w-md mx-4 border border-gray-600">
      <!-- Header -->
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-white">
          Changement de plan
        </h3>
        <button
          @click="closeModal"
          class="text-gray-400 hover:text-white transition-colors"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Info -->
      <div class="mb-4 p-3 bg-agfa-button rounded-md border border-gray-600">
        <p class="text-sm text-gray-300">
          Ajouter une barre de changement de plan à <span class="font-semibold text-white">{{ formatTime(currentTime) }}</span>
        </p>
      </div>

      <!-- Temps personnalisé -->
      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-white mb-2">
            Temps exact (secondes)
          </label>
          <input
            v-model.number="customTime"
            ref="timeInput"
            type="number"
            step="0.1"
            min="0"
            :max="videoDuration"
            class="w-full px-3 py-2 bg-agfa-button text-white border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-agfa-purple focus:border-transparent"
          />
          <p class="text-xs text-gray-400 mt-1">
            Durée vidéo: {{ formatTime(videoDuration || 0) }}
          </p>
        </div>

        <!-- Changements existants proches -->
        <div v-if="nearbySceneChanges.length > 0" class="bg-agfa-button rounded-md p-3 border border-gray-600">
          <label class="block text-sm font-medium text-white mb-2">
            Changements de plan proches
          </label>
          <div class="space-y-1">
            <div
              v-for="time in nearbySceneChanges"
              :key="time"
              class="flex items-center justify-between text-sm"
            >
              <span class="text-gray-300">{{ formatTime(time) }}</span>
              <button
                type="button"
                @click="removeSceneChange(time)"
                class="text-red-400 hover:text-red-300 transition-colors"
                title="Supprimer"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end space-x-3 pt-4">
          <button
            type="button"
            @click="closeModal"
            class="px-4 py-2 text-sm font-medium text-gray-300 bg-agfa-button border border-gray-600 rounded-md hover:bg-gray-600 transition-colors"
          >
            Annuler
          </button>
          <button
            type="submit"
            :disabled="!isTimeValid"
            class="px-4 py-2 text-sm font-medium text-white bg-agfa-purple rounded-md hover:bg-purple-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            Ajouter
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick } from 'vue'

interface Props {
  isOpen: boolean
  currentTime: number
  videoDuration?: number
  sceneChanges: number[]
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'close': []
  'add': [time: number]
  'remove': [time: number]
}>()

const timeInput = ref<HTMLInputElement>()
const customTime = ref(0)

// Validation du temps
const isTimeValid = computed(() => {
  return customTime.value >= 0 &&
         customTime.value <= (props.videoDuration || Infinity) &&
         !props.sceneChanges.includes(customTime.value)
})

// Changements de plan proches (dans un rayon de 5 secondes)
const nearbySceneChanges = computed(() => {
  const radius = 5
  return props.sceneChanges.filter(time =>
    Math.abs(time - props.currentTime) <= radius
  ).sort((a, b) => a - b)
})

// Formater le temps en mm:ss
function formatTime(seconds: number): string {
  const minutes = Math.floor(seconds / 60)
  const secs = Math.floor(seconds % 60)
  const ms = Math.floor((seconds % 1) * 10)
  return `${minutes}:${secs.toString().padStart(2, '0')}.${ms}`
}

// Initialiser le temps quand la modal s'ouvre
watch(() => props.isOpen, (isOpen) => {
  if (isOpen) {
    customTime.value = Math.round(props.currentTime * 10) / 10

    // Focus sur le champ temps
    nextTick(() => {
      timeInput.value?.focus()
      timeInput.value?.select()
    })
  }
})

function closeModal() {
  emit('close')
}

function handleSubmit() {
  if (!isTimeValid.value) return

  emit('add', customTime.value)
  closeModal()
}

function removeSceneChange(time: number) {
  emit('remove', time)
}

// Gestion des raccourcis clavier
function handleKeyDown(event: KeyboardEvent) {
  if (event.key === 'Escape') {
    closeModal()
  } else if (event.key === 'Enter' && (event.ctrlKey || event.metaKey)) {
    handleSubmit()
  }
}

// Ajouter/retirer les écouteurs d'événements
watch(() => props.isOpen, (isOpen) => {
  if (isOpen) {
    document.addEventListener('keydown', handleKeyDown)
  } else {
    document.removeEventListener('keydown', handleKeyDown)
  }
})
</script>

<style scoped>
/* Styles spécifiques si nécessaire */
</style>
