import { ref, onBeforeUnmount, computed, type Ref } from 'vue'
import { useRythmoStore } from './useRythmoState'
import { useRythmoCalculations, type Timecode } from './useRythmoCalculations'

export interface InteractionState {
  // Redimensionnement
  isResizing: boolean
  resizingTimecodeId: number | null
  resizeMode: 'left' | 'right' | null
  resizeStartX: number
  resizeStartTime: number
  resizeStartEnd: number

  // Déplacement
  isMoving: boolean
  movingTimecodeId: number | null
  moveStartX: number
  moveStartY: number
  moveStartTime: number
  moveCurrentTargetLine: number | null

  // Preview
  previewPosition: {
    x: number
    y: number
    visible: boolean
    targetLine: number
  }
}

/**
 * Composable pour gérer toutes les interactions de la bande rythmo
 */
export function useRythmoInteractions(
  timecodes: Ref<Timecode[]>,
  calculations: ReturnType<typeof useRythmoCalculations>,
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  emit: (...args: any[]) => void
) {
  const store = useRythmoStore()

  // État des interactions
  const interactionState = ref<InteractionState>({
    isResizing: false,
    resizingTimecodeId: null,
    resizeMode: null,
    resizeStartX: 0,
    resizeStartTime: 0,
    resizeStartEnd: 0,

    isMoving: false,
    movingTimecodeId: null,
    moveStartX: 0,
    moveStartY: 0,
    moveStartTime: 0,
    moveCurrentTargetLine: null,

    previewPosition: {
      x: 0,
      y: 0,
      visible: false,
      targetLine: 1,
    },
  })

  // Données locales pour le feedback visuel en temps réel
  const localTimecodes = ref<Timecode[]>([])

  // Synchronise les timecodes locaux avec le store
  const syncLocalTimecodes = () => {
    if (!interactionState.value.isResizing && !interactionState.value.isMoving) {
      localTimecodes.value = [...timecodes.value]
    }
  }

  // Utilise les timecodes locaux pendant les interactions, sinon ceux du store
  const effectiveTimecodes = computed(() => {
    return (interactionState.value.isResizing || interactionState.value.isMoving)
      ? localTimecodes.value
      : timecodes.value
  })

  // === GESTION DU REDIMENSIONNEMENT ===

  function startResize(timecodeId: number, mode: 'left' | 'right', event: MouseEvent) {
    event.stopPropagation()
    event.preventDefault()

    const timecode = timecodes.value.find((tc: Timecode) => tc.id === timecodeId)
    if (!timecode) return

    interactionState.value.isResizing = true
    interactionState.value.resizingTimecodeId = timecodeId
    interactionState.value.resizeMode = mode
    interactionState.value.resizeStartX = event.clientX
    interactionState.value.resizeStartTime = timecode.start
    interactionState.value.resizeStartEnd = timecode.end

    // Initialiser les timecodes locaux
    localTimecodes.value = [...timecodes.value]

    store.startResizing(timecodeId, mode)

    document.addEventListener('mousemove', handleResizeMove)
    document.addEventListener('mouseup', handleResizeEnd)
    document.body.style.cursor = 'ew-resize'
  }

  function handleResizeMove(event: MouseEvent) {
    if (!interactionState.value.isResizing || !interactionState.value.resizingTimecodeId) return

    const deltaX = event.clientX - interactionState.value.resizeStartX
    const deltaTime = calculations.pixelsToTime(deltaX) - calculations.pixelsToTime(0)

    let newStart = interactionState.value.resizeStartTime
    let newEnd = interactionState.value.resizeStartEnd

    if (interactionState.value.resizeMode === 'left') {
      newStart = Math.max(0, interactionState.value.resizeStartTime + deltaTime)
      if (newStart >= newEnd) {
        newStart = newEnd - 0.1
      }
    } else if (interactionState.value.resizeMode === 'right') {
      newEnd = Math.max(interactionState.value.resizeStartTime + 0.1, interactionState.value.resizeStartEnd + deltaTime)
    }

    // Mise à jour locale immédiate pour le feedback visuel
    const timecodeIndex = localTimecodes.value.findIndex(tc => tc.id === interactionState.value.resizingTimecodeId)
    if (timecodeIndex !== -1) {
      localTimecodes.value[timecodeIndex] = {
        ...localTimecodes.value[timecodeIndex],
        start: newStart,
        end: newEnd
      }
    }
  }

  function handleResizeEnd() {
    if (!interactionState.value.isResizing || !interactionState.value.resizingTimecodeId) return

    const timecodeId = interactionState.value.resizingTimecodeId
    const localTimecode = localTimecodes.value.find(tc => tc.id === timecodeId)

    if (localTimecode) {
      // Émettre l'événement de mise à jour
      emit('update-timecode-bounds', {
        timecodeId,
        start: localTimecode.start,
        end: localTimecode.end
      })

      // Mettre à jour le store
      store.updateTimecodeBounds(timecodeId, localTimecode.start, localTimecode.end)
    }

    // Nettoyage
    interactionState.value.isResizing = false
    interactionState.value.resizingTimecodeId = null
    interactionState.value.resizeMode = null

    store.stopResizing()

    document.removeEventListener('mousemove', handleResizeMove)
    document.removeEventListener('mouseup', handleResizeEnd)
    document.body.style.cursor = ''

    // Resynchroniser
    syncLocalTimecodes()
  }

  // === GESTION DU DÉPLACEMENT ===

  function startMove(timecodeId: number, currentLineNumber: number, event: MouseEvent) {
    event.stopPropagation()
    event.preventDefault()

    const timecode = timecodes.value.find((tc: Timecode) => tc.id === timecodeId)
    if (!timecode) return

    interactionState.value.isMoving = true
    interactionState.value.movingTimecodeId = timecodeId
    interactionState.value.moveStartX = event.clientX
    interactionState.value.moveStartY = event.clientY
    interactionState.value.moveStartTime = timecode.start
    interactionState.value.moveCurrentTargetLine = currentLineNumber

    // Initialiser la preview
    interactionState.value.previewPosition = {
      x: event.clientX,
      y: event.clientY,
      visible: true,
      targetLine: currentLineNumber
    }

    // Initialiser les timecodes locaux
    localTimecodes.value = [...timecodes.value]

    store.startMoving(timecodeId)

    document.addEventListener('mousemove', handleMoveMove)
    document.addEventListener('mouseup', handleMoveEnd)
    document.body.style.cursor = 'move'
  }

  function handleMoveMove(event: MouseEvent) {
    if (!interactionState.value.isMoving || !interactionState.value.movingTimecodeId) return

    const deltaX = event.clientX - interactionState.value.moveStartX
    const deltaY = event.clientY - interactionState.value.moveStartY

    const deltaTime = calculations.pixelsToTime(deltaX) - calculations.pixelsToTime(0)
    const newStart = Math.max(0, interactionState.value.moveStartTime + deltaTime)

    // Calcul de la ligne cible
    const targetLine = calculations.getTargetLine(
      interactionState.value.moveCurrentTargetLine || 1,
      deltaY
    )

    interactionState.value.moveCurrentTargetLine = targetLine

    // Mise à jour de la preview
    interactionState.value.previewPosition = {
      x: event.clientX,
      y: event.clientY,
      visible: true,
      targetLine
    }

    // Mise à jour locale pour feedback visuel
    const timecodeIndex = localTimecodes.value.findIndex(tc => tc.id === interactionState.value.movingTimecodeId)
    if (timecodeIndex !== -1) {
      const originalDuration = localTimecodes.value[timecodeIndex].end - localTimecodes.value[timecodeIndex].start
      localTimecodes.value[timecodeIndex] = {
        ...localTimecodes.value[timecodeIndex],
        start: newStart,
        end: newStart + originalDuration,
        line_number: targetLine
      }
    }
  }

  function handleMoveEnd() {
    if (!interactionState.value.isMoving || !interactionState.value.movingTimecodeId) return

    const timecodeId = interactionState.value.movingTimecodeId
    const localTimecode = localTimecodes.value.find(tc => tc.id === timecodeId)
    const targetLine = interactionState.value.moveCurrentTargetLine || 1

    if (localTimecode) {
      // Émettre l'événement de déplacement
      emit('move-timecode', {
        timecodeId,
        newStart: localTimecode.start,
        newLineNumber: targetLine
      })

      // Mettre à jour le store
      store.moveTimecode(timecodeId, localTimecode.start, targetLine)
    }

    // Nettoyage
    interactionState.value.isMoving = false
    interactionState.value.movingTimecodeId = null
    interactionState.value.moveCurrentTargetLine = null
    interactionState.value.previewPosition = { x: 0, y: 0, visible: false, targetLine: 1 }

    store.stopMoving()

    document.removeEventListener('mousemove', handleMoveMove)
    document.removeEventListener('mouseup', handleMoveEnd)
    document.body.style.cursor = ''

    // Resynchroniser
    syncLocalTimecodes()
  }

  // === GESTION DE L'ÉDITION ===

  function startEdit(timecodeId: number, text: string) {
    store.startEditing(timecodeId, text)
  }

  function finishEdit(timecodeId: number, newText: string) {
    if (newText.trim()) {
      emit('update-timecode-text', { timecodeId, text: newText.trim() })
      store.updateTimecodeText(timecodeId, newText.trim())
    }
    store.stopEditing()
  }

  function cancelEdit() {
    store.stopEditing()
  }

  // === GESTION DES CLICS ===

  function handleBlockClick(timecode: Timecode) {
    emit('seek', timecode.start)
  }

  function handleBlockDoubleClick(timecode: Timecode) {
    startEdit(timecode.id!, timecode.text)
  }

  // === NETTOYAGE ===

  onBeforeUnmount(() => {
    document.removeEventListener('mousemove', handleResizeMove)
    document.removeEventListener('mouseup', handleResizeEnd)
    document.removeEventListener('mousemove', handleMoveMove)
    document.removeEventListener('mouseup', handleMoveEnd)
    document.body.style.cursor = ''
  })

  // Synchroniser au montage
  syncLocalTimecodes()

  return {
    // État
    interactionState,
    effectiveTimecodes,

    // Actions de redimensionnement
    startResize,

    // Actions de déplacement
    startMove,

    // Actions d'édition
    startEdit,
    finishEdit,
    cancelEdit,

    // Actions de clic
    handleBlockClick,
    handleBlockDoubleClick,

    // Utilitaires
    syncLocalTimecodes,
  }
}
