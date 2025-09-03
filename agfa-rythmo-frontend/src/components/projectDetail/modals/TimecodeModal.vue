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
          {{ timecode?.id ? 'Modifier le timecode' : 'Nouveau timecode' }}
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

      <!-- Form -->
      <form @submit.prevent="handleSubmit" class="space-y-4">
        <!-- Texte -->
        <div>
          <label class="block text-sm font-medium text-white mb-2">
            Texte
          </label>
          <textarea
            v-model="formData.text"
            ref="textInput"
            class="w-full px-3 py-2 bg-agfa-button text-white border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-agfa-purple focus:border-transparent resize-none"
            rows="3"
            placeholder="Entrez le texte du timecode..."
            required
          />
        </div>

        <!-- Temps -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-white mb-2">
              Début (s)
            </label>
            <input
              v-model.number="formData.start"
              type="number"
              step="0.1"
              min="0"
              class="w-full px-3 py-2 bg-agfa-button text-white border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-agfa-purple focus:border-transparent"
              required
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-white mb-2">
              Fin (s)
            </label>
            <input
              v-model.number="formData.end"
              type="number"
              step="0.1"
              :min="formData.start + 0.1"
              class="w-full px-3 py-2 bg-agfa-button text-white border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-agfa-purple focus:border-transparent"
              required
            />
          </div>
        </div>

        <!-- Ligne -->
        <div>
          <label class="block text-sm font-medium text-white mb-2">
            Ligne
          </label>
          <select
            v-model.number="formData.line_number"
            class="w-full px-3 py-2 bg-agfa-button text-white border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-agfa-purple focus:border-transparent"
          >
            <option v-for="n in maxLines" :key="n" :value="n">
              Ligne {{ n }}
            </option>
          </select>
        </div>

        <!-- Personnage -->
        <div>
          <label class="block text-sm font-medium text-white mb-2">
            Personnage (optionnel)
          </label>
          <select
            v-model.number="formData.character_id"
            class="w-full px-3 py-2 bg-agfa-button text-white border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-agfa-purple focus:border-transparent"
          >
            <option :value="null">Aucun personnage</option>
            <option
              v-for="character in characters"
              :key="character.id"
              :value="character.id"
            >
              {{ character.name }}
            </option>
          </select>
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
            :disabled="!isFormValid"
            class="px-4 py-2 text-sm font-medium text-white bg-agfa-purple rounded-md hover:bg-purple-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            {{ timecode?.id ? 'Modifier' : 'Créer' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick } from 'vue'
import type { Timecode } from '../composables/useRythmoCalculations'
import type { Character } from '../composables/useRythmoState'

interface Props {
  isOpen: boolean
  timecode?: Timecode | null
  characters: Character[]
  maxLines?: number
  currentTime?: number
}

const props = withDefaults(defineProps<Props>(), {
  maxLines: 6,
  currentTime: 0,
})

const emit = defineEmits<{
  'close': []
  'save': [timecode: Omit<Timecode, 'id'> | Timecode]
}>()

const textInput = ref<HTMLTextAreaElement>()

// Données du formulaire
const formData = ref({
  text: '',
  start: 0,
  end: 1,
  line_number: 1,
  character_id: null as number | null,
  show_character: true,
})

// Validation du formulaire
const isFormValid = computed(() => {
  return formData.value.text.trim() !== '' &&
         formData.value.start >= 0 &&
         formData.value.end > formData.value.start &&
         formData.value.line_number >= 1 &&
         formData.value.line_number <= props.maxLines
})

// Initialiser le formulaire quand la modal s'ouvre
watch(() => props.isOpen, (isOpen) => {
  if (isOpen) {
    if (props.timecode) {
      // Mode édition
      formData.value = {
        text: props.timecode.text,
        start: props.timecode.start,
        end: props.timecode.end,
        line_number: props.timecode.line_number,
        character_id: props.timecode.character_id || null,
        show_character: props.timecode.show_character !== false,
      }
    } else {
      // Mode création
      formData.value = {
        text: '',
        start: props.currentTime,
        end: props.currentTime + 2,
        line_number: 1,
        character_id: null,
        show_character: true,
      }
    }

    // Focus sur le champ texte
    nextTick(() => {
      textInput.value?.focus()
    })
  }
})

function closeModal() {
  emit('close')
}

function handleSubmit() {
  if (!isFormValid.value) return

  const timecodeData = {
    text: formData.value.text.trim(),
    start: formData.value.start,
    end: formData.value.end,
    line_number: formData.value.line_number,
    character_id: formData.value.character_id,
    show_character: formData.value.show_character,
  }

  if (props.timecode?.id) {
    // Mode édition
    emit('save', { ...timecodeData, id: props.timecode.id })
  } else {
    // Mode création
    emit('save', timecodeData)
  }

  closeModal()
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
