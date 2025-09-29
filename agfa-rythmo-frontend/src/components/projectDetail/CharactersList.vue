<template>
  <div class="characters-panel">
    <div class="flex flex-wrap gap-2">
      <div
        v-for="character in characters"
        :key="character.id"
        @click="selectCharacter(character)"
        @mouseenter="hoveredCharacter = character"
        @mouseleave="hoveredCharacter = null"
        class="relative flex items-center space-x-2 px-3 py-2 rounded-lg border-2 cursor-pointer transition-all duration-200 min-w-0"
        :class="{
          'border-blue-500 bg-blue-900/30': activeCharacter?.id === character.id,
          'border-gray-600 bg-agfa-button hover:border-gray-400':
            activeCharacter?.id !== character.id,
        }"
      >
        <!-- Couleur du personnage -->
        <div
          class="w-4 h-4 rounded-full border border-white/50 flex-shrink-0"
          :style="{ backgroundColor: character.color }"
        ></div>

        <!-- Nom du personnage avec raccourci clavier -->
        <div class="flex items-center gap-1 min-w-0 flex-1">
          <span class="text-white font-medium text-sm truncate" :style="{ color: character.color }">
            {{ character.name }}
          </span>
          <span
            v-if="getCharacterKeyIndex(character) !== -1"
            class="text-xs px-1 rounded bg-gray-700/50 text-gray-300 font-mono flex-shrink-0"
          >
            {{ getCharacterKeyIndex(character) === 9 ? '0' : (getCharacterKeyIndex(character) + 1).toString() }}
          </span>
        </div>

        <!-- Menu contextuel au hover -->
        <div
          v-if="hoveredCharacter?.id === character.id"
          class="absolute top-full left-0 mt-1 bg-gray-800 border border-gray-600 rounded-lg shadow-lg z-10 min-w-max"
          @click.stop
        >
          <button
            @click="$emit('edit-character', character)"
            class="flex items-center space-x-2 w-full px-3 py-2 text-sm text-gray-300 hover:bg-gray-700 rounded-t-lg"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
              ></path>
            </svg>
            <span>Modifier</span>
          </button>
          <button
            @click="handleDeleteCharacter(character)"
            class="flex items-center space-x-2 w-full px-3 py-2 text-sm text-red-400 hover:bg-gray-700 rounded-b-lg"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
              ></path>
            </svg>
            <span>Supprimer</span>
          </button>
        </div>
      </div>
      <button
        @click="$emit('add-character')"
        class="w-8 h-8 bg-blue-600 hover:bg-blue-700 text-white rounded-full flex items-center justify-center transition-colors"
        title="Ajouter un personnage"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
      </button>
    </div>

    <!-- Modal de confirmation de suppression -->
    <div
      v-if="showDeleteModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    >
      <div class="bg-agfa-dark rounded-lg p-6 w-full max-w-md mx-4 shadow-xl">
        <h3 class="text-xl font-semibold text-white mb-4">Supprimer le personnage</h3>

        <p class="text-gray-300 mb-4">
          Êtes-vous sûr de vouloir supprimer le personnage
          <span class="font-semibold" :style="{ color: characterToDelete?.color }">
            "{{ characterToDelete?.name }}"
          </span>
          ?
        </p>

        <div class="mb-4">
          <label class="flex items-center space-x-2 text-gray-300">
            <input
              v-model="deleteTimecodes"
              type="checkbox"
              class="rounded border-gray-600 bg-agfa-button focus:ring-blue-500"
            />
            <span class="text-sm">Supprimer également tous les timecodes de ce personnage</span>
          </label>
          <p class="text-xs text-gray-400 mt-1 ml-6">
            Si décoché, les timecodes deviendront "sans personnage"
          </p>
        </div>

        <div class="flex space-x-3">
          <button
            @click="cancelDelete"
            class="flex-1 px-4 py-2 border border-gray-600 text-gray-300 rounded hover:bg-gray-700 transition-colors"
          >
            Annuler
          </button>
          <button
            @click="confirmDelete"
            :disabled="isDeleting"
            class="flex-1 px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            {{ isDeleting ? 'Suppression...' : 'Supprimer' }}
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { characterApi, type Character } from '../../api/characters'

const props = defineProps<{
  characters: Character[]
  activeCharacter?: Character | null
}>()

const emit = defineEmits<{
  (e: 'character-selected', character: Character): void
  (e: 'add-character'): void
  (e: 'edit-character', character: Character): void
  (e: 'character-deleted', characterId: number): void
}>()

const hoveredCharacter = ref<Character | null>(null)
const showDeleteModal = ref(false)
const characterToDelete = ref<Character | null>(null)
const deleteTimecodes = ref(false)
const isDeleting = ref(false)

// Fonction pour obtenir l'index du personnage dans la liste (pour les raccourcis clavier)
function getCharacterKeyIndex(character: Character): number {
  const index = props.characters.findIndex(c => c.id === character.id)
  return index < 10 ? index : -1 // Seulement les 10 premiers (1-0)
}

// Gestion des événements clavier
function onKeyDown(event: KeyboardEvent) {
  // Ignore si focus dans un champ de saisie
  const target = event.target as HTMLElement | null
  if (!target) return
  const tag = target.tagName.toLowerCase()
  const isEditable = tag === 'input' || tag === 'textarea' || target.isContentEditable
  if (isEditable) return

  // Ignore si des modificateurs sont pressés (pour éviter les conflits avec Shift+1-6 pour les lignes)
  if (event.shiftKey || event.ctrlKey || event.altKey || event.metaKey) return

  // Gestion des touches 1-9 et 0
  const keyMap = {
    'Digit1': 0, 'Numpad1': 0,
    'Digit2': 1, 'Numpad2': 1,
    'Digit3': 2, 'Numpad3': 2,
    'Digit4': 3, 'Numpad4': 3,
    'Digit5': 4, 'Numpad5': 4,
    'Digit6': 5, 'Numpad6': 5,
    'Digit7': 6, 'Numpad7': 6,
    'Digit8': 7, 'Numpad8': 7,
    'Digit9': 8, 'Numpad9': 8,
    'Digit0': 9, 'Numpad0': 9
  } as const

  const characterIndex = keyMap[event.code as keyof typeof keyMap]

  if (characterIndex !== undefined && props.characters[characterIndex]) {
    event.preventDefault()
    selectCharacter(props.characters[characterIndex])
  }
}

function selectCharacter(character: Character) {
  emit('character-selected', character)
}

function handleDeleteCharacter(character: Character) {
  characterToDelete.value = character
  deleteTimecodes.value = false
  showDeleteModal.value = true
}

function cancelDelete() {
  showDeleteModal.value = false
  characterToDelete.value = null
  deleteTimecodes.value = false
}

async function confirmDelete() {
  if (!characterToDelete.value) return

  isDeleting.value = true
  try {
    await characterApi.delete(characterToDelete.value.id, deleteTimecodes.value)
    emit('character-deleted', characterToDelete.value.id)
    showDeleteModal.value = false
    characterToDelete.value = null
  } catch (error) {
    console.error('Erreur lors de la suppression:', error)
    // TODO: Afficher message d'erreur à l'utilisateur
  } finally {
    isDeleting.value = false
  }
}

// Écouteurs d'événements clavier
onMounted(() => {
  window.addEventListener('keydown', onKeyDown)
})

onUnmounted(() => {
  window.removeEventListener('keydown', onKeyDown)
})
</script>

<style scoped>
.characters-panel {
  position: relative;
}

/* Assurer que le menu contextuel reste visible */
.relative:hover .absolute {
  display: block;
}
</style>
