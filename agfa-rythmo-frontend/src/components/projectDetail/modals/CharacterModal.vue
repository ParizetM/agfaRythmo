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
          {{ character?.id ? 'Modifier le personnage' : 'Nouveau personnage' }}
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
        <!-- Nom -->
        <div>
          <label class="block text-sm font-medium text-white mb-2">
            Nom du personnage
          </label>
          <input
            v-model="formData.name"
            ref="nameInput"
            type="text"
            class="w-full px-3 py-2 bg-agfa-button text-white border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-agfa-purple focus:border-transparent"
            placeholder="Ex: Alice, Bob..."
            required
          />
        </div>

        <!-- Couleur -->
        <div>
          <label class="block text-sm font-medium text-white mb-2">
            Couleur
          </label>
          <div class="flex items-center space-x-3">
            <input
              v-model="formData.color"
              type="color"
              class="w-12 h-10 border border-gray-600 rounded cursor-pointer bg-agfa-button"
            />
            <input
              v-model="formData.color"
              type="text"
              class="flex-1 px-3 py-2 bg-agfa-button text-white border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-agfa-purple focus:border-transparent"
              placeholder="#8455F6"
              pattern="^#[0-9A-Fa-f]{6}$"
            />
          </div>
          <p class="text-xs text-gray-400 mt-1">
            Format hexadécimal (ex: #8455F6)
          </p>
        </div>

        <!-- Couleurs prédéfinies -->
        <div>
          <label class="block text-sm font-medium text-white mb-2">
            Couleurs prédéfinies
          </label>
          <div class="grid grid-cols-6 gap-2">
            <button
              v-for="color in predefinedColors"
              :key="color"
              type="button"
              @click="formData.color = color"
              :class="[
                'w-8 h-8 rounded border-2 transition-all',
                formData.color === color
                  ? 'border-white shadow-lg scale-110'
                  : 'border-gray-600 hover:border-gray-400'
              ]"
              :style="{ backgroundColor: color }"
              :title="color"
            />
          </div>
        </div>

        <!-- Preview -->
        <div class="bg-agfa-button rounded-md p-3 border border-gray-600">
          <label class="block text-sm font-medium text-white mb-2">
            Aperçu
          </label>
          <div
            class="inline-flex items-center px-3 py-1 rounded text-sm font-semibold"
            :style="{
              backgroundColor: formData.color,
              color: getContrastColor(formData.color)
            }"
          >
            {{ formData.name || 'Nom du personnage' }}
          </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end space-x-3 pt-4">
          <button
            v-if="character?.id"
            type="button"
            @click="handleDelete"
            class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 transition-colors"
          >
            Supprimer
          </button>
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
            {{ character?.id ? 'Modifier' : 'Créer' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick } from 'vue'
import type { Character } from '../composables/useRythmoState'

interface Props {
  isOpen: boolean
  character?: Character | null
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'close': []
  'save': [character: Omit<Character, 'id'> | Character]
  'delete': [characterId: number]
}>()

const nameInput = ref<HTMLInputElement>()

// Couleurs prédéfinies
const predefinedColors = [
  '#8455F6', '#3B82F6', '#10B981', '#F59E0B',
  '#EF4444', '#EC4899', '#8B5CF6', '#06B6D4',
  '#84CC16', '#F97316', '#6366F1', '#14B8A6'
]

// Données du formulaire
const formData = ref({
  name: '',
  color: '#8455F6',
})

// Validation du formulaire
const isFormValid = computed(() => {
  return formData.value.name.trim() !== '' &&
         /^#[0-9A-Fa-f]{6}$/.test(formData.value.color)
})

// Calculer la couleur de contraste
function getContrastColor(backgroundColor: string): string {
  const hex = backgroundColor.replace('#', '')
  const r = parseInt(hex.substr(0, 2), 16)
  const g = parseInt(hex.substr(2, 2), 16)
  const b = parseInt(hex.substr(4, 2), 16)
  const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255
  return luminance > 0.5 ? '#000000' : '#FFFFFF'
}

// Initialiser le formulaire quand la modal s'ouvre
watch(() => props.isOpen, (isOpen) => {
  if (isOpen) {
    if (props.character) {
      // Mode édition
      formData.value = {
        name: props.character.name,
        color: props.character.color,
      }
    } else {
      // Mode création - générer une couleur aléatoire
      const randomColor = predefinedColors[Math.floor(Math.random() * predefinedColors.length)]
      formData.value = {
        name: '',
        color: randomColor,
      }
    }

    // Focus sur le champ nom
    nextTick(() => {
      nameInput.value?.focus()
    })
  }
})

function closeModal() {
  emit('close')
}

function handleSubmit() {
  if (!isFormValid.value) return

  const characterData = {
    name: formData.value.name.trim(),
    color: formData.value.color.toLowerCase(),
  }

  if (props.character?.id) {
    // Mode édition
    emit('save', { ...characterData, id: props.character.id })
  } else {
    // Mode création
    emit('save', characterData)
  }

  closeModal()
}

function handleDelete() {
  if (props.character?.id) {
    if (confirm(`Êtes-vous sûr de vouloir supprimer le personnage "${props.character.name}" ?`)) {
      emit('delete', props.character.id)
      closeModal()
    }
  }
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
