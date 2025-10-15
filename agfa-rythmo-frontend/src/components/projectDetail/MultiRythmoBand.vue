<template>
  <div class="multi-rythmo-container">
    <!-- Configuration du nombre de lignes -->
    <div v-if="!hideConfig" class="rythmo-config mb-3 overflow-x-auto">
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
            <option :value="1" style="background: #384152; color: #ffffff">1 ligne</option>
            <option :value="2" style="background: #384152; color: #ffffff">2 lignes</option>
            <option :value="3" style="background: #384152; color: #ffffff">3 lignes</option>
            <option :value="4" style="background: #384152; color: #ffffff">4 lignes</option>
            <option :value="5" style="background: #384152; color: #ffffff">5 lignes</option>
            <option :value="6" style="background: #384152; color: #ffffff">6 lignes</option>
          </select>
        </label>
      </div>
    </div>

    <!-- Bandes rythmo multiples - collées -->
    <div class="rythmo-bands-container">
      <div v-for="lineNumber in localRythmoLinesCount" :key="lineNumber" class="rythmo-line-wrapper">
        <RythmoBandSingle
          :key="`line-${lineNumber}-reload-${lineReloadKeys[lineNumber] || lineNumber}`"
          :timecodes="getTimecodesForLine(Number(lineNumber))"
          :currentTime="currentTime"
          :videoDuration="videoDuration"
          :instant="instant"
          :sceneChanges="sceneChanges"
          :lineNumber="Number(lineNumber)"
          :isLastLine="lineNumber === localRythmoLinesCount"
          :dragState="dragState"
          :sceneChangeDragState="sceneChangeDragState"
          :sceneChangeHoverState="sceneChangeHoverState"
          :characters="characters"
          :selectedLine="selectedLine"
          :disableSelection="disableSelection"
          @seek="$emit('seek', $event)"
          @update-timecode="onUpdateTimecode"
          @update-timecode-bounds="onUpdateTimecodeBounds"
          @move-timecode="onMoveTimecode"
          @update-timecode-show-character="onUpdateTimecodeShowCharacter"
          @update-timecode-character="onUpdateTimecodeCharacter"
          @update-timecode-separator-positions="onUpdateTimecodeSeparatorPositions"
          @delete-timecode="onDeleteTimecode"
          @reload-lines="onReloadLines"
          @dragging-start="onDragStart"
          @dragging-update="onDragUpdate"
          @dragging-end="onDragEnd"
          @add-timecode="() => $emit('add-timecode-to-line', Number(lineNumber))"
          @line-selected="onLineSelected"
          @update-scene-change="onUpdateSceneChange"
          @delete-scene-change="onDeleteSceneChange"
          @scene-change-drag-start="onSceneChangeDragStart"
          @scene-change-drag-update="onSceneChangeDragUpdate"
          @scene-change-drag-end="onSceneChangeDragEnd"
          @scene-change-hover-start="onSceneChangeHoverStart"
          @scene-change-hover-end="onSceneChangeHoverEnd"
        />
      </div>
    </div>

    <!-- Modal de confirmation de suppression -->
    <ConfirmDeleteModal
      :show="showDeleteModal"
      :timecode="timecodeToDelete"
      @confirm="onConfirmDelete"
      @cancel="onCancelDelete"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted, onBeforeUnmount } from 'vue'
import RythmoBandSingle from './RythmoBandSingle.vue'
import CharactersList from './CharactersList.vue'
import ConfirmDeleteModal from './ConfirmDeleteModal.vue'
import { type Character } from '../../api/characters'
import { type SeparatorPositions } from '../../utils/separatorEncoding'

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

interface SceneChange {
  id: number
  timecode: number
  project_id: number
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

interface SceneChangeDragState {
  active: boolean
  sceneChangeId: number
  index: number
  startTime: number
  currentTime: number
  startX: number
  currentX: number
  currentY: number
}

interface SceneChangeHoverState {
  active: boolean
  sceneChangeId: number
  index: number
}

const props = defineProps<{
  timecodes: Timecode[]
  currentTime: number
  videoDuration?: number
  instant?: boolean | import('vue').Ref<boolean>
  sceneChanges?: SceneChange[]
  rythmoLinesCount: number
  hideConfig?: boolean
  characters?: Character[]
  activeCharacter?: Character | null
  disableSelection?: boolean // Désactive les effets visuels de sélection
}>()

const emit = defineEmits<{
  (e: 'seek', time: number): void
  (e: 'update-timecode', payload: { timecode: Timecode; text: string }): void
  (e: 'update-timecode-bounds', payload: { timecode: Timecode; start: number; end: number }): void
  (e: 'move-timecode', payload: { timecode: Timecode; newStart: number; newLineNumber: number }): void
  (e: 'update-timecode-show-character', payload: { timecode: Timecode; showCharacter: boolean }): void
  (e: 'update-timecode-character', payload: { timecode: Timecode; characterId: number | null }): void
  (e: 'update-timecode-separator-positions', payload: { timecode: Timecode; separatorPositions: SeparatorPositions }): void
  (e: 'delete-timecode', payload: { timecode: Timecode }): void
  (e: 'add-timecode-to-line', lineNumber: number): void
  (e: 'update-lines-count', count: number): void
  (e: 'selected-line-changed', lineNumber: number): void
  // Character related events forwarded from CharactersList
  (e: 'character-selected', character: Character): void
  (e: 'add-character'): void
  (e: 'edit-character', character: Character): void
  (e: 'character-deleted', characterId: number): void
  // Scene change related events
  (e: 'update-scene-change', payload: { id: number; newTimecode: number; isPreview: boolean }): void
  (e: 'delete-scene-change', payload: { id: number }): void
}>()

const localRythmoLinesCount = ref(props.rythmoLinesCount || 1)

// Variables pour la suppression de timecodes
const showDeleteModal = ref(false)
const timecodeToDelete = ref<Timecode | null>(null)

// Clés pour forcer le reload individuel de chaque ligne
const lineReloadKeys = ref<Record<number, number>>({})
const dragState = ref<DragState | null>(null)

// État de drag pour les scene changes
const sceneChangeDragState = ref<SceneChangeDragState | null>(null)

// État de hover partagé pour les scene changes
const sceneChangeHoverState = ref<SceneChangeHoverState | null>(null)

// État de la ligne sélectionnée
const selectedLine = ref<number | null>(1)

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

function onUpdateTimecodeCharacter(payload: { timecode: Timecode; characterId: number | null }) {
  emit('update-timecode-character', payload)
}

function onUpdateTimecodeSeparatorPositions(payload: { timecode: Timecode; separatorPositions: SeparatorPositions }) {
  emit('update-timecode-separator-positions', payload)
}

function onReloadLines(payload: { sourceLineNumber: number; targetLineNumber: number }) {
  // Recharge seulement les lignes concernées après un court délai
  setTimeout(() => {
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

// Gestion de la suppression de timecodes
function onDeleteTimecode(payload: { timecode: Timecode }) {
  timecodeToDelete.value = payload.timecode
  showDeleteModal.value = true
}

function onConfirmDelete() {
  if (timecodeToDelete.value) {
    emit('delete-timecode', { timecode: timecodeToDelete.value })
  }
  showDeleteModal.value = false
  timecodeToDelete.value = null
}

function onCancelDelete() {
  showDeleteModal.value = false
  timecodeToDelete.value = null
}

// Gestion de la sélection de ligne
function onLineSelected(lineNumber: number) {
  // Vérifier que le numéro de ligne est valide
  if (lineNumber >= 1 && lineNumber <= localRythmoLinesCount.value) {
    selectedLine.value = lineNumber
    // Émettre le changement vers le parent
    emit('selected-line-changed', lineNumber)
  }
}

// Gestion des événements des scene changes
function onUpdateSceneChange(payload: { id: number; newTimecode: number; isPreview: boolean }) {
  emit('update-scene-change', payload)
}

function onDeleteSceneChange(payload: { id: number }) {
  emit('delete-scene-change', payload)
}

function onSceneChangeDragStart(payload: { sceneChangeId: number; index: number; startTime: number; startX: number; startY: number }) {
  sceneChangeDragState.value = {
    active: true,
    sceneChangeId: payload.sceneChangeId,
    index: payload.index,
    startTime: payload.startTime,
    currentTime: payload.startTime,
    startX: payload.startX,
    currentX: payload.startX,
    currentY: payload.startY,
  }
}

function onSceneChangeDragUpdate(payload: { currentTime: number; currentX: number; currentY: number; isPreview: boolean }) {
  if (!sceneChangeDragState.value || !sceneChangeDragState.value.active) return

  sceneChangeDragState.value.currentTime = payload.currentTime
  sceneChangeDragState.value.currentX = payload.currentX
  sceneChangeDragState.value.currentY = payload.currentY

  if (!payload.isPreview) {
    emit('update-scene-change', {
      id: sceneChangeDragState.value.sceneChangeId,
      newTimecode: payload.currentTime,
      isPreview: false
    })
  }
}

function onSceneChangeDragEnd() {
  if (sceneChangeDragState.value) {
    sceneChangeDragState.value.active = false
  }
}

function onSceneChangeHoverStart(payload: { sceneChangeId: number; index: number }) {
  sceneChangeHoverState.value = {
    active: true,
    sceneChangeId: payload.sceneChangeId,
    index: payload.index,
  }
}

function onSceneChangeHoverEnd() {
  if (sceneChangeHoverState.value) {
    sceneChangeHoverState.value.active = false
  }
}

// Gestion des raccourcis clavier pour la sélection de lignes (Shift + 1-6)
function onKeyDownLineSelection(event: KeyboardEvent) {
  // Ignore si focus dans un champ de saisie
  const target = event.target as HTMLElement | null
  if (!target) return
  const tag = target.tagName.toLowerCase()
  const isEditable = tag === 'input' || tag === 'textarea' || target.isContentEditable
  if (isEditable) return

  // Seulement si Shift est pressé
  if (!event.shiftKey) return

  // Mapping des touches avec Shift pour sélection de ligne
  const lineKeyMap = {
    'Digit1': 1, 'Numpad1': 1,
    'Digit2': 2, 'Numpad2': 2,
    'Digit3': 3, 'Numpad3': 3,
    'Digit4': 4, 'Numpad4': 4,
    'Digit5': 5, 'Numpad5': 5,
    'Digit6': 6, 'Numpad6': 6,
  } as const

  const lineNumber = lineKeyMap[event.code as keyof typeof lineKeyMap]

  if (lineNumber !== undefined) {
    // Vérifier que la ligne existe
    if (lineNumber <= localRythmoLinesCount.value) {
      event.preventDefault()
      selectedLine.value = lineNumber
      // Émettre le changement vers le parent
      emit('selected-line-changed', lineNumber)
    }
  }
}

// Gestion des événements de cycle de vie
onMounted(() => {
  window.addEventListener('keydown', onKeyDownLineSelection)
})

onBeforeUnmount(() => {
  window.removeEventListener('keydown', onKeyDownLineSelection)
})
</script>

<style scoped>
.multi-rythmo-container {
  padding-bottom: 20px;
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
  overflow-y: visible;
  overflow-x: visible;
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
