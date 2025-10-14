<template>
  <div class="characters-panel">
    <div class="characters-scroll flex gap-2 pb-2 flex-row">
      <div
        v-for="character in characters"
        :key="character.id"
        class="character-item group flex flex-col items-center min-w-[72px] max-w-[110px] flex-shrink-0 relative"
      >
        <!-- Bouton principal du personnage -->
        <button
          class="w-full flex flex-col items-center px-2 py-1 border-2 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 text-xs md:rounded-md md:group-hover:rounded-t-md md:group-hover:rounded-b-none rounded-t-md rounded-b-none relative"
          :class="{
            'border-blue-500 bg-blue-900/30': activeCharacter?.id === character.id,
            'border-gray-600 bg-agfa-button hover:border-gray-400':
              activeCharacter?.id !== character.id,
          }"
          @click="selectCharacter(character)"
        >
          <!-- Badge du raccourci clavier (PC seulement) -->
          <span
            v-if="getCharacterKeyIndex(character) !== -1"
            class="absolute top-0.5 right-0.5 text-[9px] w-4 h-4 items-center justify-center bg-gray-700/80 text-gray-300 font-mono rounded-full hidden md:flex"
          >
            {{
              getCharacterKeyIndex(character) === 9
                ? '0'
                : (getCharacterKeyIndex(character) + 1).toString()
            }}
          </span>

          <span
            class="truncate font-medium w-full text-center"
            :style="{
              backgroundColor: character.color,
              color: character.text_color || getContrastColor(character.color),
              padding: '2px 6px',
              borderRadius: '4px',
              fontSize: '0.95em',
              marginBottom: '2px',
              width: '100%',
            }"
          >
            {{ character.name }}
          </span>
        </button>

        <!-- Boutons d'action intégrés -->
        <div
          class="w-full flex border-2 border-t-0 rounded-b-md overflow-hidden transition-opacity duration-200 md:opacity-0 md:group-hover:opacity-100 opacity-100"
          :class="{
            'border-blue-500': activeCharacter?.id === character.id,
            'border-gray-600': activeCharacter?.id !== character.id,
          }"
        >
          <button
            @click.stop="$emit('edit-character', character)"
            class="flex-1 px-1 py-1 text-xs bg-agfa-button hover:bg-gray-600 text-gray-300 hover:text-white transition-colors focus:outline-none focus:bg-gray-600"
            title="Modifier"
          >
            <svg class="w-3 h-3 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
              />
            </svg>
          </button>
          <div class="w-px bg-gray-600">

          </div>
          <button
            @click.stop="handleDeleteCharacter(character)"
            class="flex-1 px-1 py-1 text-xs bg-agfa-button hover:bg-red-600 text-gray-300 hover:text-white transition-colors focus:outline-none focus:bg-red-600"
            title="Supprimer"
          >
            <svg class="w-3 h-3 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
              />
            </svg>
          </button>

        </div>
      </div>
      <!-- Bouton ajouter -->
      <div
        class="character-item flex flex-col items-center min-w-[72px] max-w-[110px] flex-shrink-0"
      >
        <button
          @click="$emit('add-character')"
          class="w-9 h-9 bg-blue-600 hover:bg-blue-700 text-white rounded-full flex items-center justify-center transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500"
          title="Ajouter un personnage"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 4v16m8-8H4"
            />
          </svg>
        </button>
      </div>
    </div>

    <!-- Modal de confirmation de suppression -->
    <div
      v-if="showDeleteModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 overflow-auto"
    >
      <div class="bg-agfa-dark rounded-lg p-6 w-full max-w-md mx-4 shadow-xl">
        <h3 class="text-xl font-semibold text-white mb-4">Supprimer le personnage</h3>
        <p class="text-gray-300 mb-4">
          Êtes-vous sûr de vouloir supprimer le personnage
          <span
            class="font-semibold px-2 py-1 rounded"
            :style="{
              backgroundColor: characterToDelete?.color,
              color:
                characterToDelete?.text_color ||
                getContrastColor(characterToDelete?.color || '#FFFFFF'),
            }"
          >
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
import { getContrastColor } from '../../utils/colorUtils'

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

const showDeleteModal = ref(false)
const characterToDelete = ref<Character | null>(null)
const deleteTimecodes = ref(false)
const isDeleting = ref(false)

// Fonction pour obtenir l'index du personnage dans la liste (pour les raccourcis clavier)
function getCharacterKeyIndex(character: Character): number {
  const index = props.characters.findIndex((c) => c.id === character.id)
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
    Digit1: 0,
    Numpad1: 0,
    Digit2: 1,
    Numpad2: 1,
    Digit3: 2,
    Numpad3: 2,
    Digit4: 3,
    Numpad4: 3,
    Digit5: 4,
    Numpad5: 4,
    Digit6: 5,
    Numpad6: 5,
    Digit7: 6,
    Numpad7: 6,
    Digit8: 7,
    Numpad8: 7,

    Digit9: 8,
    Numpad9: 8,
    Digit0: 9,
    Numpad0: 9,
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

.characters-scroll {
  scrollbar-width: thin;
  scrollbar-color: #3b82f6 #222;
}
.characters-scroll::-webkit-scrollbar {
  height: 6px;
}
.characters-scroll::-webkit-scrollbar-thumb {
  background: #3b82f6;
  border-radius: 4px;
}
.characters-scroll::-webkit-scrollbar-track {
  background: #222;
}

.character-item {
  min-width: 72px;
  max-width: 110px;
  margin-right: 0.25rem;
}

@media (max-width: 640px) {
  .characters-scroll {
    gap: 0.5rem;
    padding-bottom: 0.5rem;
  }
  .character-item {
    min-width: 60px;
    max-width: 90px;
  }
}
</style>
