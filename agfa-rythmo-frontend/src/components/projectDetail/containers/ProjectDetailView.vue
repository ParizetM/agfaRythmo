<template>
  <div class="project-detail-view h-screen flex flex-col bg-agfa-bg">
    <!-- Header du projet -->
    <header class="bg-agfa-menu border-b border-gray-600 p-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <button
            @click="$emit('back')"
            class="p-2 text-gray-400 hover:text-white transition-colors"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
          </button>

          <div>
            <h1 class="text-xl font-semibold text-white">{{ project?.name || 'Projet sans nom' }}</h1>
            <p class="text-sm text-gray-400">
              {{ timecodes.length }} timecodes • {{ characters.length }} personnages • {{ sceneChanges.length }} plans
            </p>
          </div>
        </div>

        <div class="flex items-center space-x-2">
          <!-- Indicateur de sauvegarde -->
          <div
            v-if="hasUnsavedChanges"
            class="flex items-center text-yellow-400 text-sm"
          >
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L4.35 15.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
            Non sauvegardé
          </div>

          <div
            v-else-if="lastSavedAt"
            class="flex items-center text-green-400 text-sm"
          >
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            Sauvegardé {{ formatSaveTime(lastSavedAt) }}
          </div>

          <!-- Actions rapides -->
          <button
            @click="saveProject"
            :disabled="isLoading"
            class="px-3 py-1 text-sm font-medium text-white bg-agfa-purple rounded hover:bg-purple-600 disabled:opacity-50 transition-colors"
          >
            {{ isLoading ? 'Sauvegarde...' : 'Sauvegarder' }}
          </button>

          <button
            @click="showExportDialog = true"
            class="px-3 py-1 text-sm font-medium text-white bg-agfa-button border border-gray-600 rounded hover:bg-gray-600 transition-colors"
          >
            Exporter
          </button>
        </div>
      </div>
    </header>

    <!-- Contenu principal -->
    <div class="flex-1 flex overflow-hidden">
      <!-- Panneau latéral -->
      <aside class="w-80 bg-agfa-menu border-r border-gray-600 flex flex-col">
        <!-- Onglets -->
        <div class="flex border-b border-gray-600">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            class="flex-1 px-4 py-3 text-sm font-medium transition-colors"
            :class="[
              activeTab === tab.id
                ? 'text-white bg-agfa-purple'
                : 'text-gray-400 hover:text-white hover:bg-agfa-button'
            ]"
          >
            <component :is="tab.icon" class="w-4 h-4 mx-auto mb-1" />
            {{ tab.label }}
          </button>
        </div>

        <!-- Contenu des onglets -->
        <div class="flex-1 overflow-y-auto p-4">
          <!-- Onglet Timeline -->
          <TimelineView
            v-if="activeTab === 'timeline'"
            :timecodes="timecodes"
            :current-time="currentTime"
            @edit="openTimecodeModal"
            @delete="deleteTimecode"
            @seek="seekToTime"
          />

          <!-- Onglet Personnages -->
          <CharactersList
            v-if="activeTab === 'characters'"
            :characters="characters"
            :timecodes="timecodes"
            @edit="openCharacterModal"
            @delete="deleteCharacter"
            @add="openCharacterModal()"
          />

          <!-- Onglet Plans -->
          <SceneChangesList
            v-if="activeTab === 'scenes'"
            :scene-changes="sceneChanges"
            :timecodes="timecodes"
            :video-duration="videoDuration"
            @add-scene-change="openSceneChangeModal"
            @remove-scene-change="removeSceneChange"
            @seek="seekToTime"
          />
        </div>
      </aside>

      <!-- Zone principale -->
      <main class="flex-1 flex flex-col">
        <!-- Lecteur vidéo -->
        <div class="h-1/2 border-b border-gray-600">
          <VideoPlayerContainer
            ref="videoPlayer"
            :video-url="project?.video_path || ''"
            :timecodes="timecodes"
            :scene-changes="sceneChanges"
            @time-update="handleTimeUpdate"
            @seek="handleVideoSeek"
            @loaded="handleVideoLoaded"
          />
        </div>

        <!-- Bande rythmo -->
        <div class="flex-1 overflow-auto">
          <MultiRythmoContainer
            ref="rhythmoContainer"
            :timecodes="timecodes"
            :characters="characters"
            :scene-changes="sceneChanges"
            :current-time="currentTime"
            :video-duration="videoDuration"
            :rythmo-lines-count="rythmoLinesCount"
            @timecode-updated="handleTimecodeUpdate"
            @timecode-created="handleTimecodeCreate"
            @seek="seekToTime"
            @update:lines-count="updateLinesCount"
          />
        </div>
      </main>
    </div>

    <!-- Modals -->
    <TimecodeModal
      :is-open="modals.timecode.isOpen"
      :timecode="modals.timecode.data"
      :characters="characters"
      @close="closeTimecodeModal"
      @save="handleTimcodeSave"
      @delete="handleTimecodeDelete"
    />

    <CharacterModal
      :is-open="modals.character.isOpen"
      :character="modals.character.data"
      @close="closeCharacterModal"
      @save="handleCharacterSave"
      @delete="handleCharacterDelete"
    />

    <SceneChangeModal
      :is-open="modals.sceneChange.isOpen"
      :current-time="currentTime"
      :video-duration="videoDuration"
      :scene-changes="sceneChanges"
      @close="closeSceneChangeModal"
      @add="addSceneChange"
      @remove="removeSceneChange"
    />

    <!-- Dialog d'export -->
    <div
      v-if="showExportDialog"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
      @click.self="showExportDialog = false"
    >
      <div class="bg-agfa-menu rounded-lg p-6 w-full max-w-md mx-4 border border-gray-600">
        <h3 class="text-lg font-semibold text-white mb-4">Exporter le projet</h3>

        <div class="space-y-3">
          <button
            @click="exportAsJson"
            class="w-full p-3 text-left bg-agfa-button border border-gray-600 rounded hover:bg-gray-600 transition-colors"
          >
            <div class="text-white font-medium">JSON</div>
            <div class="text-gray-400 text-sm">Export complet des données</div>
          </button>

          <button
            @click="exportAsText"
            class="w-full p-3 text-left bg-agfa-button border border-gray-600 rounded hover:bg-gray-600 transition-colors"
          >
            <div class="text-white font-medium">Texte</div>
            <div class="text-gray-400 text-sm">Texte seul avec timecodes</div>
          </button>

          <button
            @click="exportAsSrt"
            class="w-full p-3 text-left bg-agfa-button border border-gray-600 rounded hover:bg-gray-600 transition-colors"
          >
            <div class="text-white font-medium">SRT</div>
            <div class="text-gray-400 text-sm">Sous-titres</div>
          </button>
        </div>

        <div class="flex justify-end space-x-3 mt-6">
          <button
            @click="showExportDialog = false"
            class="px-4 py-2 text-sm font-medium text-gray-300 bg-agfa-button border border-gray-600 rounded hover:bg-gray-600 transition-colors"
          >
            Annuler
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { useRythmoStore, type Character, type Project } from '../composables/useRythmoState'
import type { Timecode } from '../composables/useRythmoCalculations'

// Composants
import VideoPlayerContainer from './VideoPlayerContainer.vue'
import MultiRythmoContainer from './MultiRythmoContainer.vue'
import TimelineView from '../lists/TimelineView.vue'
import CharactersList from '../lists/CharactersList.vue'
import SceneChangesList from '../lists/SceneChangesList.vue'
import TimecodeModal from '../modals/TimecodeModal.vue'
import CharacterModal from '../modals/CharacterModal.vue'
import SceneChangeModal from '../modals/SceneChangeModal.vue'

interface Props {
  projectId: number
}

// Props et emits (marqués comme utilisés)
// eslint-disable-next-line @typescript-eslint/no-unused-vars
const props = defineProps<Props>()

// eslint-disable-next-line @typescript-eslint/no-unused-vars
const emit = defineEmits<{
  'back': []
  'project-updated': [project: Project]
}>()

// Store
const store = useRythmoStore()
const {
  project,
  timecodes,
  characters,
  sceneChanges,
  rythmoLinesCount,
  hasUnsavedChanges,
  lastSavedAt,
  isLoading
} = store

// Références
const videoPlayer = ref<InstanceType<typeof VideoPlayerContainer>>()
const rhythmoContainer = ref<InstanceType<typeof MultiRythmoContainer>>()

// État local
const activeTab = ref('timeline')
const currentTime = ref(0)
const videoDuration = ref(0)
const showExportDialog = ref(false)

// Onglets
const tabs = [
  {
    id: 'timeline',
    label: 'Timeline',
    icon: 'svg' // Remplacer par un vrai composant d'icône
  },
  {
    id: 'characters',
    label: 'Personnages',
    icon: 'svg'
  },
  {
    id: 'scenes',
    label: 'Plans',
    icon: 'svg'
  }
]

// Modals
const modals = ref({
  timecode: {
    isOpen: false,
    data: null as Timecode | null
  },
  character: {
    isOpen: false,
    data: null as Character | null
  },
  sceneChange: {
    isOpen: false
  }
})

// Gestion du temps de sauvegarde
function formatSaveTime(date: Date): string {
  const now = new Date()
  const diff = Math.floor((now.getTime() - date.getTime()) / 1000)

  if (diff < 60) return 'à l\'instant'
  if (diff < 3600) return `il y a ${Math.floor(diff / 60)}min`
  return `il y a ${Math.floor(diff / 3600)}h`
}

// Gestion de la lecture vidéo
function handleTimeUpdate(time: number) {
  currentTime.value = time
}

function handleVideoSeek(time: number) {
  currentTime.value = time
}

function handleVideoLoaded(duration: number) {
  videoDuration.value = duration
}

function seekToTime(time: number) {
  videoPlayer.value?.seekTo(time)
  currentTime.value = time
}

// Gestion des timecodes
function handleTimecodeUpdate(timecode: Timecode) {
  store.updateTimecode(timecode.id!, timecode)
}

function handleTimecodeCreate(timecode: Omit<Timecode, 'id'>) {
  store.addTimecode(timecode)
}

function openTimecodeModal(timecode?: Timecode) {
  modals.value.timecode.data = timecode || null
  modals.value.timecode.isOpen = true
}

function closeTimecodeModal() {
  modals.value.timecode.isOpen = false
  modals.value.timecode.data = null
}

function handleTimcodeSave(timecode: Timecode) {
  if (timecode.id) {
    store.updateTimecode(timecode.id, timecode)
  } else {
    store.addTimecode(timecode)
  }
  closeTimecodeModal()
}

function handleTimecodeDelete(id: number) {
  store.deleteTimecode(id)
  closeTimecodeModal()
}

function deleteTimecode(id: number) {
  if (confirm('Supprimer ce timecode ?')) {
    store.deleteTimecode(id)
  }
}

// Gestion des personnages
function openCharacterModal(character?: Character) {
  modals.value.character.data = character || null
  modals.value.character.isOpen = true
}

function closeCharacterModal() {
  modals.value.character.isOpen = false
  modals.value.character.data = null
}

function handleCharacterSave(character: Character | Omit<Character, 'id'>) {
  if ('id' in character && character.id) {
    store.updateCharacter(character.id, character)
  } else {
    store.addCharacter(character)
  }
  closeCharacterModal()
}

function handleCharacterDelete(id: number) {
  store.deleteCharacter(id)
  closeCharacterModal()
}

function deleteCharacter(id: number) {
  if (confirm('Supprimer ce personnage ?')) {
    store.deleteCharacter(id)
  }
}

// Gestion des changements de plan
function openSceneChangeModal() {
  modals.value.sceneChange.isOpen = true
}

function closeSceneChangeModal() {
  modals.value.sceneChange.isOpen = false
}

function addSceneChange(time: number) {
  store.addSceneChange(time)
  closeSceneChangeModal()
}

function removeSceneChange(time: number) {
  if (confirm('Supprimer ce changement de plan ?')) {
    store.removeSceneChange(time)
  }
}

// Gestion du projet
function updateLinesCount(count: number) {
  store.setRythmoLinesCount(count)
}

async function saveProject() {
  // TODO: Implémenter la sauvegarde vers l'API
  console.log('Sauvegarde du projet...', store.getProjectData())
}

// Export
function exportAsJson() {
  const data = store.getProjectData()
  const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' })
  downloadFile(blob, `${project?.name || 'projet'}.json`)
  showExportDialog.value = false
}

function exportAsText() {
  const text = timecodes
    .map((tc: Timecode) => `${formatTime(tc.start)} - ${formatTime(tc.end)}: ${tc.text}`)
    .join('\n')

  const blob = new Blob([text], { type: 'text/plain' })
  downloadFile(blob, `${project?.name || 'projet'}.txt`)
  showExportDialog.value = false
}

function exportAsSrt() {
  const srt = timecodes
    .map((tc: Timecode, index: number) => {
      const startTime = formatSrtTime(tc.start)
      const endTime = formatSrtTime(tc.end)
      return `${index + 1}\n${startTime} --> ${endTime}\n${tc.text}\n`
    })
    .join('\n')

  const blob = new Blob([srt], { type: 'text/plain' })
  downloadFile(blob, `${project?.name || 'projet'}.srt`)
  showExportDialog.value = false
}

function downloadFile(blob: Blob, filename: string) {
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = filename
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  URL.revokeObjectURL(url)
}

function formatTime(seconds: number): string {
  const minutes = Math.floor(seconds / 60)
  const secs = Math.floor(seconds % 60)
  return `${minutes}:${secs.toString().padStart(2, '0')}`
}

function formatSrtTime(seconds: number): string {
  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  const secs = Math.floor(seconds % 60)
  const ms = Math.floor((seconds % 1) * 1000)

  return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')},${ms.toString().padStart(3, '0')}`
}

// Gestion des raccourcis clavier globaux
function handleGlobalKeyDown(event: KeyboardEvent) {
  if (event.ctrlKey || event.metaKey) {
    switch (event.key) {
      case 's':
        event.preventDefault()
        saveProject()
        break
      case 'e':
        event.preventDefault()
        showExportDialog.value = true
        break
    }
  }
}

// Lifecycle
onMounted(() => {
  document.addEventListener('keydown', handleGlobalKeyDown)
  // TODO: Charger le projet depuis l'API
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleGlobalKeyDown)
})
</script>

<style scoped>
/* Styles personnalisés si nécessaire */
</style>
