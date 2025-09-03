import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import type { Timecode } from './useRythmoCalculations'

export interface Character {
  id: number
  name: string
  color: string
}

export interface Project {
  id?: number
  name: string
  video_path?: string
  timecodes: Timecode[]
  characters: Character[]
  scene_changes: number[]
  rythmo_lines_count: number
}

/**
 * Store Pinia pour gérer l'état des timecodes et du projet
 */
export const useRythmoStore = defineStore('rythmo', () => {
  // État local
  const project = ref<Project | null>(null)
  const timecodes = ref<Timecode[]>([])
  const characters = ref<Character[]>([])
  const sceneChanges = ref<number[]>([])
  const rythmoLinesCount = ref<number>(1)

  // État de l'édition
  const editingTimecodeId = ref<number | null>(null)
  const editingText = ref<string>('')

  // État des interactions
  const resizingTimecode = ref<{ id: number; mode: 'left' | 'right' } | null>(null)
  const movingTimecode = ref<{ id: number; originalLine: number } | null>(null)

  // État de synchronisation avec le backend
  const isLoading = ref<boolean>(false)
  const lastSavedAt = ref<Date | null>(null)
  const hasUnsavedChanges = ref<boolean>(false)

  // Getters computed
  const timecodesForLine = computed(() => {
    return (lineNumber: number) =>
      timecodes.value.filter(tc => tc.line_number === lineNumber)
  })

  const characterById = computed(() => {
    return (id: number) =>
      characters.value.find(char => char.id === id)
  })

  const timecodeById = computed(() => {
    return (id: number) =>
      timecodes.value.find(tc => tc.id === id)
  })

  const sortedTimecodes = computed(() => {
    return [...timecodes.value].sort((a, b) => {
      if (a.line_number !== b.line_number) {
        return a.line_number - b.line_number
      }
      return a.start - b.start
    })
  })

  // Actions pour les timecodes
  function setTimecodes(newTimecodes: Timecode[]) {
    timecodes.value = [...newTimecodes]
    hasUnsavedChanges.value = true
  }

  function addTimecode(timecode: Omit<Timecode, 'id'>) {
    const newTimecode: Timecode = {
      ...timecode,
      id: Date.now(), // ID temporaire jusqu'à la sauvegarde
    }
    timecodes.value.push(newTimecode)
    hasUnsavedChanges.value = true
    return newTimecode
  }

  function updateTimecode(id: number, updates: Partial<Timecode>) {
    const index = timecodes.value.findIndex(tc => tc.id === id)
    if (index !== -1) {
      timecodes.value[index] = { ...timecodes.value[index], ...updates }
      hasUnsavedChanges.value = true
    }
  }

  function updateTimecodeText(id: number, text: string) {
    updateTimecode(id, { text })
  }

  function updateTimecodeBounds(id: number, start: number, end: number) {
    updateTimecode(id, { start, end })
  }

  function moveTimecode(id: number, newStart: number, newLineNumber: number) {
    const timecode = timecodeById.value(id)
    if (timecode) {
      const duration = timecode.end - timecode.start
      updateTimecode(id, {
        start: newStart,
        end: newStart + duration,
        line_number: newLineNumber
      })
    }
  }

  function updateTimecodeCharacterDisplay(id: number, showCharacter: boolean) {
    updateTimecode(id, { show_character: showCharacter })
  }

  function deleteTimecode(id: number) {
    const index = timecodes.value.findIndex(tc => tc.id === id)
    if (index !== -1) {
      timecodes.value.splice(index, 1)
      hasUnsavedChanges.value = true
    }
  }

  // Actions pour les personnages
  function setCharacters(newCharacters: Character[]) {
    characters.value = [...newCharacters]
  }

  function addCharacter(character: Omit<Character, 'id'>) {
    const newCharacter: Character = {
      ...character,
      id: Date.now(), // ID temporaire
    }
    characters.value.push(newCharacter)
    hasUnsavedChanges.value = true
    return newCharacter
  }

  function updateCharacter(id: number, updates: Partial<Character>) {
    const index = characters.value.findIndex(char => char.id === id)
    if (index !== -1) {
      characters.value[index] = { ...characters.value[index], ...updates }
      hasUnsavedChanges.value = true
    }
  }

  function deleteCharacter(id: number) {
    const index = characters.value.findIndex(char => char.id === id)
    if (index !== -1) {
      characters.value.splice(index, 1)
      // Supprimer la référence des timecodes
      timecodes.value.forEach(tc => {
        if (tc.character_id === id) {
          tc.character_id = null
          tc.character = undefined
        }
      })
      hasUnsavedChanges.value = true
    }
  }

  // Actions pour les changements de plan
  function setSceneChanges(newSceneChanges: number[]) {
    sceneChanges.value = [...newSceneChanges]
    hasUnsavedChanges.value = true
  }

  function addSceneChange(time: number) {
    if (!sceneChanges.value.includes(time)) {
      sceneChanges.value.push(time)
      sceneChanges.value.sort((a, b) => a - b)
      hasUnsavedChanges.value = true
    }
  }

  function removeSceneChange(time: number) {
    const index = sceneChanges.value.indexOf(time)
    if (index !== -1) {
      sceneChanges.value.splice(index, 1)
      hasUnsavedChanges.value = true
    }
  }

  // Actions pour le projet
  function setProject(newProject: Project) {
    project.value = newProject
    setTimecodes(newProject.timecodes)
    setCharacters(newProject.characters)
    setSceneChanges(newProject.scene_changes)
    setRythmoLinesCount(newProject.rythmo_lines_count)
    hasUnsavedChanges.value = false
    lastSavedAt.value = new Date()
  }

  function setRythmoLinesCount(count: number) {
    rythmoLinesCount.value = count
    hasUnsavedChanges.value = true
  }

  // Actions d'édition
  function startEditing(timecodeId: number, text: string) {
    editingTimecodeId.value = timecodeId
    editingText.value = text
  }

  function stopEditing() {
    editingTimecodeId.value = null
    editingText.value = ''
  }

  function finishEditing() {
    if (editingTimecodeId.value && editingText.value.trim()) {
      updateTimecodeText(editingTimecodeId.value, editingText.value.trim())
    }
    stopEditing()
  }

  // Actions d'interaction
  function startResizing(timecodeId: number, mode: 'left' | 'right') {
    resizingTimecode.value = { id: timecodeId, mode }
  }

  function stopResizing() {
    resizingTimecode.value = null
  }

  function startMoving(timecodeId: number) {
    const timecode = timecodeById.value(timecodeId)
    if (timecode) {
      movingTimecode.value = { id: timecodeId, originalLine: timecode.line_number }
    }
  }

  function stopMoving() {
    movingTimecode.value = null
  }

  // Utilitaires
  function resetState() {
    project.value = null
    timecodes.value = []
    characters.value = []
    sceneChanges.value = []
    rythmoLinesCount.value = 1
    stopEditing()
    stopResizing()
    stopMoving()
    hasUnsavedChanges.value = false
    lastSavedAt.value = null
  }

  function getProjectData(): Project | null {
    if (!project.value) return null

    return {
      ...project.value,
      timecodes: timecodes.value,
      characters: characters.value,
      scene_changes: sceneChanges.value,
      rythmo_lines_count: rythmoLinesCount.value,
    }
  }

  return {
    // État
    project,
    timecodes,
    characters,
    sceneChanges,
    rythmoLinesCount,
    editingTimecodeId,
    editingText,
    resizingTimecode,
    movingTimecode,
    isLoading,
    lastSavedAt,
    hasUnsavedChanges,

    // Getters
    timecodesForLine,
    characterById,
    timecodeById,
    sortedTimecodes,

    // Actions timecodes
    setTimecodes,
    addTimecode,
    updateTimecode,
    updateTimecodeText,
    updateTimecodeBounds,
    moveTimecode,
    updateTimecodeCharacterDisplay,
    deleteTimecode,

    // Actions personnages
    setCharacters,
    addCharacter,
    updateCharacter,
    deleteCharacter,

    // Actions changements de plan
    setSceneChanges,
    addSceneChange,
    removeSceneChange,

    // Actions projet
    setProject,
    setRythmoLinesCount,

    // Actions édition
    startEditing,
    stopEditing,
    finishEditing,

    // Actions interaction
    startResizing,
    stopResizing,
    startMoving,
    stopMoving,

    // Utilitaires
    resetState,
    getProjectData,
  }
})
