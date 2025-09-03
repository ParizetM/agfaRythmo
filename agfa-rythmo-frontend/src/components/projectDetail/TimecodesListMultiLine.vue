<template>
  <div class="bg-agfa-dark rounded-xl p-6 min-w-56 max-w-96 text-white shadow-lg overflow-y-auto">
    <h3 class="text-xl font-bold mb-4 text-white">Timecodes</h3>

    <!-- Groupés par ligne -->
    <div v-for="lineNumber in rythmoLinesCount" :key="lineNumber" class="line-group mb-4">
      <div class="line-header mb-2">
        <span class="line-title">Ligne {{ lineNumber }}</span>
        <div class="flex gap-2 items-center">
          <button
            @click="toggleFold(lineNumber)"
            class="fold-btn"
            :title="foldedLines[lineNumber] ? 'Déplier la ligne' : 'Replier la ligne'"
          >
            <span v-if="foldedLines[lineNumber]">&#9654;</span>
            <span v-else>&#9660;</span>
          </button>
          <button
            @click="emit('add-to-line', lineNumber)"
            class="add-line-btn"
            title="Ajouter un timecode sur cette ligne"
          >
            +
          </button>
        </div>
      </div>

      <ul v-show="!foldedLines[lineNumber]" class="list-none p-0 m-0 space-y-1">
        <li
          v-for="(timecode, idx) in getTimecodesForLine(lineNumber)"
          :key="timecode.id || idx"
          :class="[
            'p-2 rounded-lg cursor-pointer transition-all duration-300 hover:bg-gray-700',
            {
              'bg-agfa-blue bg-opacity-40 ring-2 ring-agfa-blue ring-opacity-50':
                isSelected(timecode),
            },
          ]"
          @click="emit('select', timecode)"
        >
          <div class="flex items-center gap-1">
            <span class="text-xs font-medium text-gray-300">
              {{ timecode.start.toFixed(2) }}s - {{ timecode.end.toFixed(2) }}s
            </span>

            <!-- Sélecteur de personnage -->
            <select
              v-model="timecode.character_id"
              class="text-xs border border-gray-600 rounded px-1 py-0.5 min-w-20"
              :style="getCharacterSelectStyle(timecode.character_id)"
              @change="updateTimecodeCharacter(timecode)"
              @click.stop
            >
              <option :value="null" style="background: #4a5568; color: #ffffff">Aucun</option>
              <option
                v-for="character in characters"
                :key="character.id"
                :value="character.id"
                :style="{ backgroundColor: character.color, color: getTextColor(character.color) }"
              >
                {{ character.name }}
              </option>
            </select>
          </div>
          <span class="flex-1 ml-2 text-sm text-white">
            {{ timecode.text }}
          </span>
          <div class="flex gap-1">
            <button
              @click.stop="emit('edit', timecode)"
              class="bg-transparent border-none text-white cursor-pointer text-sm hover:text-agfa-blue transition-colors duration-300 p-1 rounded hover:bg-gray-600"
              title="Éditer"
            >
              ✏️
            </button>
            <button
              @click.stop="emit('delete', timecode)"
              class="bg-transparent border-none text-white cursor-pointer text-sm hover:text-red-400 transition-colors duration-300 p-1 rounded hover:bg-gray-600"
              title="Supprimer"
            >
              🗑️
            </button>
          </div>
        </li>

        <li
          v-if="getTimecodesForLine(lineNumber).length === 0"
          class="text-gray-500 text-sm italic p-2"
        >
          Aucun timecode sur cette ligne
        </li>
      </ul>
    </div>

    <!-- Bouton d'ajout global -->
    <button
      @click="emit('add')"
      class="w-full bg-agfa-blue hover:bg-agfa-blue-hover text-white border-none rounded-lg py-2 px-4 cursor-pointer text-sm font-medium transition-colors duration-300"
    >
      Ajouter un timecode
    </button>
  </div>
</template>

<script setup lang="ts">
interface Character {
  id: number
  name: string
  color: string
}

interface Timecode {
  id?: number
  project_id?: number
  start: number
  end: number
  text: string
  line_number: number
  character_id?: number | null
  character?: Character
  show_character?: boolean
}

import { ref, watch, onMounted } from 'vue'
import { characterApi } from '../../api/characters'
import { timecodeApi, type UpdateTimecodeData } from '../../api/timecodes'

const props = defineProps<{
  timecodes: Timecode[]
  rythmoLinesCount: number
  selected?: Timecode
  projectId: number
}>()

const emit = defineEmits<{
  (e: 'select', timecode: Timecode): void
  (e: 'edit', timecode: Timecode): void
  (e: 'delete', timecode: Timecode): void
  (e: 'add'): void
  (e: 'add-to-line', lineNumber: number): void
  (e: 'updated'): void
}>()

const foldedLines = ref<Record<number, boolean>>({})
const characters = ref<Character[]>([])

// Charger les personnages
async function loadCharacters() {
  try {
    const response = await characterApi.getAll(props.projectId)
    characters.value = response.data.characters
  } catch (error) {
    console.error('Erreur lors du chargement des personnages:', error)
  }
}

// Fonction pour obtenir le style du select basé sur la couleur du personnage
function getCharacterSelectStyle(characterId: number | null | undefined) {
  if (!characterId) {
    return {
      backgroundColor: '#4a5568',
      color: '#ffffff',
    }
  }

  const character = characters.value.find((c) => c.id === characterId)
  if (!character) {
    return {
      backgroundColor: '#4a5568',
      color: '#ffffff',
    }
  }

  return {
    backgroundColor: character.color,
    color: getTextColor(character.color),
  }
}

// Fonction pour déterminer la couleur du texte (noir ou blanc) selon la couleur de fond
function getTextColor(backgroundColor: string): string {
  // Supprimer le # si présent
  const color = backgroundColor.replace('#', '')

  // Convertir en RGB
  const r = parseInt(color.substr(0, 2), 16)
  const g = parseInt(color.substr(2, 2), 16)
  const b = parseInt(color.substr(4, 2), 16)

  // Calculer la luminance
  const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255

  // Retourner noir pour les couleurs claires, blanc pour les couleurs sombres
  return luminance > 0.5 ? '#000000' : '#ffffff'
}

// Fonction pour mettre à jour un timecode quand on change de personnage
async function updateTimecodeCharacter(timecode: Timecode) {
  if (timecode.id) {
    try {
      const updateData: UpdateTimecodeData = {
        character_id: timecode.character_id,
      }
      await timecodeApi.update(props.projectId, timecode.id, updateData)
      emit('updated')
    } catch (error) {
      console.error('Erreur lors de la mise à jour du personnage:', error)
    }
  }
}

// Initialiser toutes les lignes dépliées
watch(
  () => props.rythmoLinesCount,
  (count) => {
    for (let i = 1; i <= count; i++) {
      if (!(i in foldedLines.value)) {
        foldedLines.value[i] = false
      }
    }
  },
  { immediate: true },
)

// Charger les personnages au montage
onMounted(() => {
  loadCharacters()
})

function toggleFold(lineNumber: number) {
  foldedLines.value[lineNumber] = !foldedLines.value[lineNumber]
}

function getTimecodesForLine(lineNumber: number): Timecode[] {
  return props.timecodes.filter((tc) => tc.line_number === lineNumber)
}

function isSelected(timecode: Timecode): boolean {
  return (
    props.selected?.id === timecode.id ||
    (props.selected?.start === timecode.start &&
      props.selected?.end === timecode.end &&
      props.selected?.text === timecode.text)
  )
}
</script>

<style scoped>
.line-group {
  border-left: 3px solid #8455f6;
  padding-left: 0.75rem;
}

.line-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.5rem;
}

.line-title {
  font-weight: 600;
  color: #8455f6;
  font-size: 0.875rem;
}

.add-line-btn {
  width: 1.5rem;
  height: 1.5rem;
  background: #384152;
  color: white;
  border: 1px solid #8455f6;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.75rem;
  font-weight: bold;
  transition: all 0.2s;
}

.add-line-btn:hover {
  background: #8455f6;
  transform: scale(1.1);
}

.fold-btn {
  width: 1.5rem;
  height: 1.5rem;
  color: #8455f6;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s;
}
.fold-btn:hover {
  background: #8455f6;
  color: #fff;
}
</style>
