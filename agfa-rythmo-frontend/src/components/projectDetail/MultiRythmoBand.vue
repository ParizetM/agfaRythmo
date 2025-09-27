<template>
  <div class="multi-rythmo-container">
    <!-- Configuration du nombre de lignes -->
    <div v-if="!hideConfig" class="rythmo-config mb-3">
      <div class="flex items-center space-x-4 w-full">
        <!-- Characters list placé à gauche du select -->
        <div class="flex-shrink-0">
          <CharactersList
            :characters="props.characters || []"
            :activeCharacter="props.activeCharacter || null"
            @character-selected="(c: any) => emit('character-selected', c)"
            @add-character="() => emit('add-character')"
            @edit-character="(c: any) => emit('edit-character', c)"
            @character-deleted="(id: any) => emit('character-deleted', id)"
          />
        </div>

        <label class="text-white text-sm font-medium ml-auto">
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
    </div>

    <!-- Bandes rythmo multiples - collées -->
    <div class="rythmo-bands-container">
      <div v-for="lineNumber in localRythmoLinesCount" :key="lineNumber" class="rythmo-line-wrapper">
        <RythmoBandSingle
          :key="lineReloadKeys[lineNumber] || 0"
          :timecodes="getTimecodesForLine(Number(lineNumber))"
          :currentTime="currentTime"
          :videoDuration="videoDuration"
          :instant="instant"
          :sceneChanges="sceneChanges"
          :lineNumber="Number(lineNumber)"
          :isLastLine="lineNumber === localRythmoLinesCount"
          :dragState="dragState"
          @seek="$emit('seek', $event)"
          @update-timecode="onUpdateTimecode"
          @update-timecode-bounds="onUpdateTimecodeBounds"
          @move-timecode="onMoveTimecode"
          @update-timecode-show-character="onUpdateTimecodeShowCharacter"
          @reload-lines="onReloadLines"
          @dragging-start="onDragStart"
          @dragging-update="onDragUpdate"
          @dragging-end="onDragEnd"
          @add-timecode="() => $emit('add-timecode-to-line', Number(lineNumber))"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import RythmoBandSingle from './RythmoBandSingle.vue'
import CharactersList from './CharactersList.vue'
import { type Character } from '../../api/characters'

interface Timecode {
  id?: number
  start: number
  end: number
  text: string
  line_number: number
  character_id?: number | null
  character?: Character
  show_character?: boolean
}

interface DragState {
  active: boolean
  timecode: Timecode
  sourceLineNumber: number
  targetLineNumber: number
  newStart: number
  duration: number
  index: number
}

interface DragStartPayload {
  timecode: Timecode
  sourceLineNumber: number
  index: number
}

interface DragUpdatePayload {
  timecodeId?: number
  index: number
  newStart: number
  targetLineNumber: number
  pointerX: number
  pointerY: number
}

const props = defineProps<{
  timecodes: Timecode[]
  currentTime: number
  videoDuration?: number
  instant?: boolean | import('vue').Ref<boolean>
  sceneChanges?: number[]
  rythmoLinesCount: number
  hideConfig?: boolean
  characters?: Character[]
  activeCharacter?: Character | null
}>()

const emit = defineEmits<{
  (e: 'seek', time: number): void
  (e: 'update-timecode', payload: { timecode: Timecode; text: string }): void
  (e: 'update-timecode-bounds', payload: { timecode: Timecode; start: number; end: number }): void
  (e: 'move-timecode', payload: { timecode: Timecode; newStart: number; newLineNumber: number }): void
  (e: 'update-timecode-show-character', payload: { timecode: Timecode; showCharacter: boolean }): void
  (e: 'add-timecode-to-line', lineNumber: number): void
  (e: 'update-lines-count', count: number): void
  // Character related events forwarded from CharactersList
  (e: 'character-selected', character: Character): void
  (e: 'add-character'): void
  (e: 'edit-character', character: Character): void
  (e: 'character-deleted', characterId: number): void
}>()

const localRythmoLinesCount = ref(props.rythmoLinesCount || 1)

// Clés pour forcer le reload individuel de chaque ligne
const lineReloadKeys = ref<Record<number, number>>({})
const dragState = ref<DragState | null>(null)

// Synchronise avec les props
watch(() => props.rythmoLinesCount, (newCount) => {
  if (newCount && newCount !== localRythmoLinesCount.value) {
    localRythmoLinesCount.value = newCount
  }
})

function getTimecodesForLine(lineNumber: number): Timecode[] {
  return props.timecodes.filter(tc => tc.line_number === lineNumber)
}

function onUpdateTimecode(payload: { timecode: Timecode; text: string }) {
  emit('update-timecode', payload)
}

function onUpdateTimecodeBounds(payload: { timecode: Timecode; start: number; end: number }) {
  emit('update-timecode-bounds', payload)
}

function onMoveTimecode(payload: { timecode: Timecode; newStart: number; newLineNumber: number }) {
  emit('move-timecode', payload)
}

function onUpdateTimecodeShowCharacter(payload: { timecode: Timecode; showCharacter: boolean }) {
  emit('update-timecode-show-character', payload)
}

function onReloadLines(payload: { sourceLineNumber: number; targetLineNumber: number }) {
  // Recharge seulement les lignes concernées après un court délai
  setTimeout(() => {
    console.log(`Reload lignes ${payload.sourceLineNumber} et ${payload.targetLineNumber}`)

    // Force le reload de la ligne source
    lineReloadKeys.value[payload.sourceLineNumber] = (lineReloadKeys.value[payload.sourceLineNumber] || 0) + 1

    // Force le reload de la ligne cible si différente
    if (payload.targetLineNumber !== payload.sourceLineNumber) {
      lineReloadKeys.value[payload.targetLineNumber] = (lineReloadKeys.value[payload.targetLineNumber] || 0) + 1
    }
  }, 100)
}

function onLinesCountChange() {
  emit('update-lines-count', localRythmoLinesCount.value)
}

function onDragStart(payload: DragStartPayload) {
  const duration = payload.timecode.end - payload.timecode.start
  dragState.value = {
    active: true,
    timecode: payload.timecode,
    sourceLineNumber: payload.sourceLineNumber,
    targetLineNumber: payload.sourceLineNumber,
    newStart: payload.timecode.start,
    duration,
    index: payload.index,
  }
}

function onDragUpdate(payload: DragUpdatePayload) {
  if (!dragState.value || !dragState.value.active) return
  if (dragState.value.index !== payload.index) return
  if (typeof payload.timecodeId !== 'undefined' && dragState.value.timecode.id !== payload.timecodeId) return

  dragState.value = {
    ...dragState.value,
    newStart: payload.newStart,
    targetLineNumber: payload.targetLineNumber,
  }
}

function onDragEnd() {
  dragState.value = null
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
