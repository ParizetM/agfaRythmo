<template>
  <div class="bg-agfa-dark rounded-xl p-6 min-w-56 max-w-96 text-white shadow-lg overflow-scroll">
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
            @click="$emit('add-to-line', lineNumber)"
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
            'flex flex-wrap items-center gap-2 p-2 rounded-lg cursor-pointer transition-all duration-300 hover:bg-gray-700',
            { 'bg-agfa-blue bg-opacity-40 ring-2 ring-agfa-blue ring-opacity-50': isSelected(timecode) }
          ]"
          @click="$emit('select', timecode)"
        >
          <span class="text-xs font-medium text-gray-300">
            {{ timecode.start.toFixed(2) }}s - {{ timecode.end.toFixed(2) }}s
          </span>
          <span class="flex-1 ml-2 text-sm text-white">
            {{ timecode.text }}
          </span>
          <div class="flex gap-1">
            <button
              @click.stop="$emit('edit', timecode)"
              class="bg-transparent border-none text-white cursor-pointer text-sm hover:text-agfa-blue transition-colors duration-300 p-1 rounded hover:bg-gray-600"
              title="Éditer"
            >
              ✏️
            </button>
            <button
              @click.stop="$emit('delete', timecode)"
              class="bg-transparent border-none text-white cursor-pointer text-sm hover:text-red-400 transition-colors duration-300 p-1 rounded hover:bg-gray-600"
              title="Supprimer"
            >
              🗑️
            </button>
          </div>
        </li>

        <li v-if="getTimecodesForLine(lineNumber).length === 0" class="text-gray-500 text-sm italic p-2">
          Aucun timecode sur cette ligne
        </li>
      </ul>
    </div>

    <!-- Bouton d'ajout global -->
    <button
      @click="$emit('add')"
      class="w-full bg-agfa-blue hover:bg-agfa-blue-hover text-white border-none rounded-lg py-2 px-4 cursor-pointer text-sm font-medium transition-colors duration-300"
    >
      Ajouter un timecode
    </button>
  </div>
</template>

<script setup lang="ts">
interface Timecode {
  id?: number
  project_id?: number
  start: number
  end: number
  text: string
  line_number: number
}

import { ref, watch } from 'vue'

const props = defineProps<{
  timecodes: Timecode[]
  rythmoLinesCount: number
  selected?: Timecode
}>()

defineEmits<{
  (e: 'select', timecode: Timecode): void
  (e: 'edit', timecode: Timecode): void
  (e: 'delete', timecode: Timecode): void
  (e: 'add'): void
  (e: 'add-to-line', lineNumber: number): void
}>()

const foldedLines = ref<Record<number, boolean>>({})

// Initialiser toutes les lignes dépliées
watch(() => props.rythmoLinesCount, (count) => {
  for (let i = 1; i <= count; i++) {
    if (!(i in foldedLines.value)) {
      foldedLines.value[i] = false
    }
  }
}, { immediate: true })

function toggleFold(lineNumber: number) {
  foldedLines.value[lineNumber] = !foldedLines.value[lineNumber]
}

function getTimecodesForLine(lineNumber: number): Timecode[] {
  return props.timecodes.filter(tc => tc.line_number === lineNumber)
}

function isSelected(timecode: Timecode): boolean {
  return props.selected?.id === timecode.id ||
         (props.selected?.start === timecode.start &&
          props.selected?.end === timecode.end &&
          props.selected?.text === timecode.text)
}
</script>

<style scoped>
.line-group {
  border-left: 3px solid #8455F6;
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
  color: #8455F6;
  font-size: 0.875rem;
}

.add-line-btn {
  width: 1.5rem;
  height: 1.5rem;
  background: #384152;
  color: white;
  border: 1px solid #8455F6;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.75rem;
  font-weight: bold;
  transition: all 0.2s;
}

.add-line-btn:hover {
  background: #8455F6;
  transform: scale(1.1);
}

.fold-btn {
  width: 1.5rem;
  height: 1.5rem;
  color: #8455F6;
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
  background: #8455F6;
  color: #fff;
}
</style>
