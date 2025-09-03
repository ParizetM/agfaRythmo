<template>
  <div class="multi-rythmo-container">
    <!-- Configuration du nombre de lignes -->
    <div v-if="!hideConfig" class="rythmo-config mb-3">
      <label class="text-white text-sm font-medium">
        Nombre de lignes rythmo:
        <select
          v-model.number="localRythmoLinesCount"
          @change="onLinesCountChange"
          class="ml-2 bg-agfa-button text-white border border-gray-600 rounded px-2 py-1 text-sm"
        >
          <option :value="1">1 ligne</option>
          <option :value="2">2 lignes</option>
          <option :value="3">3 lignes</option>
          <option :value="4">4 lignes</option>
          <option :value="5">5 lignes</option>
          <option :value="6">6 lignes</option>
        </select>
      </label>
    </div>

    <!-- Bandes rythmo multiples -->
    <div class="rythmo-bands-container">
      <div
        v-for="lineNumber in localRythmoLinesCount"
        :key="lineNumber"
        class="rythmo-line-wrapper"
      >
        <RythmoTrack
          :key="lineReloadKeys[lineNumber] || 0"
          :timecodes="getTimecodesForLine(Number(lineNumber))"
          :current-time="currentTime"
          :video-duration="videoDuration"
          :instant="instant"
          :scene-changes="sceneChanges"
          :line-number="Number(lineNumber)"
          :is-last-line="lineNumber === localRythmoLinesCount"
          @seek="$emit('seek', $event)"
          @update-timecode="onUpdateTimecode"
          @update-timecode-bounds="onUpdateTimecodeBounds"
          @move-timecode="onMoveTimecode"
          @character-toggle="onCharacterToggle"
          @reload-lines="onReloadLines"
          @add-timecode="() => $emit('add-timecode-to-line', Number(lineNumber))"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { useRythmoStore } from '../composables/useRythmoState'
import type { Timecode } from '../composables/useRythmoCalculations'
import RythmoTrack from '../ui/RythmoTrack.vue'

interface Props {
  timecodes: Timecode[]
  currentTime: number
  videoDuration?: number
  instant?: boolean | import('vue').Ref<boolean>
  sceneChanges?: number[]
  rythmoLinesCount: number
  hideConfig?: boolean
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'seek': [time: number]
  'update-timecode': [payload: { timecode: Timecode; text: string }]
  'update-timecode-bounds': [payload: { timecode: Timecode; start: number; end: number }]
  'move-timecode': [payload: { timecode: Timecode; newStart: number; newLineNumber: number }]
  'character-toggle': [timecodeId: number]
  'add-timecode-to-line': [lineNumber: number]
  'update-lines-count': [count: number]
}>()

const store = useRythmoStore()
const localRythmoLinesCount = ref(props.rythmoLinesCount || 1)

// Clés pour forcer le reload individuel de chaque ligne
const lineReloadKeys = ref<Record<number, number>>({})

// Synchronise avec les props
watch(() => props.rythmoLinesCount, (newCount) => {
  if (newCount && newCount !== localRythmoLinesCount.value) {
    localRythmoLinesCount.value = newCount
  }
})

// Filtrer les timecodes par ligne
function getTimecodesForLine(lineNumber: number): Timecode[] {
  return props.timecodes.filter(tc => tc.line_number === lineNumber)
}

// Gestion des événements
function onUpdateTimecode(payload: { timecode: Timecode; text: string }) {
  // Mettre à jour le store local
  if (payload.timecode.id) {
    store.updateTimecodeText(payload.timecode.id, payload.text)
  }
  // Propager l'événement
  emit('update-timecode', payload)
}

function onUpdateTimecodeBounds(payload: { timecode: Timecode; start: number; end: number }) {
  // Mettre à jour le store local
  if (payload.timecode.id) {
    store.updateTimecodeBounds(payload.timecode.id, payload.start, payload.end)
  }
  // Propager l'événement
  emit('update-timecode-bounds', payload)
}

function onMoveTimecode(payload: { timecode: Timecode; newStart: number; newLineNumber: number }) {
  // Mettre à jour le store local
  if (payload.timecode.id) {
    store.moveTimecode(payload.timecode.id, payload.newStart, payload.newLineNumber)
  }
  // Propager l'événement
  emit('move-timecode', payload)
}

function onCharacterToggle(timecodeId: number) {
  const timecode = store.timecodeById(timecodeId)
  if (timecode) {
    const newShowCharacter = !timecode.show_character
    store.updateTimecodeCharacterDisplay(timecodeId, newShowCharacter)

    // Propager l'événement
    emit('character-toggle', timecodeId)
  }
}

function onReloadLines(payload: { sourceLineNumber: number; targetLineNumber: number }) {
  // Recharge seulement les lignes concernées après un court délai
  setTimeout(() => {
    console.log(`Reload lignes ${payload.sourceLineNumber} et ${payload.targetLineNumber}`)

    // Force le reload de la ligne source
    lineReloadKeys.value[payload.sourceLineNumber] =
      (lineReloadKeys.value[payload.sourceLineNumber] || 0) + 1

    // Force le reload de la ligne cible si différente
    if (payload.targetLineNumber !== payload.sourceLineNumber) {
      lineReloadKeys.value[payload.targetLineNumber] =
        (lineReloadKeys.value[payload.targetLineNumber] || 0) + 1
    }
  }, 100)
}

function onLinesCountChange() {
  store.setRythmoLinesCount(localRythmoLinesCount.value)
  emit('update-lines-count', localRythmoLinesCount.value)
}
</script>

<style scoped>
.multi-rythmo-container {
  width: 100%;
}

.rythmo-config {
  display: flex;
  align-items: center;
  justify-content: flex-start;
  padding: 0.5rem 1rem;
  background: #202937;
  border-radius: 6px;
  border: 1px solid rgba(59, 130, 246, 0.2);
}

.rythmo-bands-container {
  display: flex;
  flex-direction: column;
  gap: 0; /* Aucun espacement entre les lignes */
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.rythmo-line-wrapper {
  position: relative;
  width: 100%;
  border-bottom: 1px solid rgba(59, 130, 246, 0.2);
}

.rythmo-line-wrapper:last-child {
  border-bottom: none;
}
</style>
