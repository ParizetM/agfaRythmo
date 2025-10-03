<template>
  <div class="flex flex-col items-center bg-gray-900 min-h-screen p-0 m-0">
    <!-- Header Panel -->
    <header class="w-full relative flex items-center justify-between bg-agfa-dark shadow-lg mb-6 py-5 px-6">
      <button
        class="flex items-center gap-2 bg-transparent border-none text-white text-lg font-medium cursor-pointer px-3 py-1 rounded-lg hover:bg-gray-800 transition-colors duration-300"
        @click="goBack"
        title="Retour aux projets"
      >
        <BackSvg class="w-5 h-5"/>
        <span>Projets</span>
      </button>

      <div class="flex-1 text-white text-left mx-6 min-w-0">
        <h1 class="text-3xl font-bold mb-1 whitespace-nowrap overflow-hidden text-ellipsis">
          {{ project?.name }}
        </h1>
        <p class="text-lg text-gray-300 m-0 whitespace-nowrap overflow-hidden text-ellipsis">
          {{ project?.description }}
        </p>
      </div>

      <div class="flex items-center gap-3">
        <!-- Bouton paramètres du projet -->
        <button
          class="bg-transparent text-gray-300 hover:text-white border border-gray-600 hover:border-gray-400 rounded-lg p-2 cursor-pointer transition-colors duration-300"
          @click="showProjectSettings = true"
          title="Paramètres du projet"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
          </svg>
        </button>

        <!-- Bouton raccourcis clavier -->
        <button
          class="bg-transparent text-gray-300 hover:text-white border border-gray-600 hover:border-gray-400 rounded-lg p-2 cursor-pointer transition-colors duration-300"
          @click="showKeyboardShortcuts = true"
          title="Raccourcis clavier (Cmd + ?)"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
        </button>

        <!-- Bouton collaborateurs -->
        <button
          v-if="project && canManageProject"
          class="bg-transparent text-gray-300 hover:text-white border border-gray-600 hover:border-gray-400 rounded-lg p-2 cursor-pointer transition-colors duration-300"
          @click="showCollaboratorsModal = true"
          title="Gérer les collaborateurs"
        >
            <UsersIcon class="w-5 h-5" />
        </button>

        <!-- Bouton aperçu final -->
        <button
          v-if="project && project.video_path && compatibleTimecodes.length > 0"
          class="bg-agfa-blue hover:bg-agfa-blue-hover text-white border-none rounded-lg px-5 py-2 text-base font-bold cursor-pointer shadow-lg transition-colors duration-300"
          @click="goToFinalPreview"
          title="Aperçu final plein écran"
        >
          Aperçu final
        </button>
      </div>
    </header>

    <!-- Message d'erreur d'accès -->
    <div v-if="project && !hasProjectAccess" class="w-full max-w-4xl mx-auto p-6 bg-red-600 text-white rounded-lg mb-6">
      <div class="flex items-center justify-center">
        <div class="text-center">
          <h3 class="text-lg font-semibold mb-2">Accès refusé</h3>
          <p class="mb-4">Vous n'avez pas les droits pour accéder à ce projet.</p>
          <button
            @click="goBack"
            class="bg-white text-red-600 px-4 py-2 rounded-md font-medium hover:bg-gray-100"
          >
            Retour aux projets
          </button>
        </div>
      </div>
    </div>

    <!-- Main Grid -->
  <div v-else class="w-full relative flex flex-row items-start justify-center lg:flex-col lg:gap-2 overflow-x-hidden">
  <!-- Overlay Left Panel - Timecodes -->
      <!-- Overlay Right Panel - Scene Changes -->
      <div>
        <button
          class="fixed top-[88px] right-0 z-50 bg-agfa-dark text-white border border-gray-600 rounded-l-lg w-7 h-12 flex items-center justify-center cursor-pointer shadow-lg text-lg p-0 hover:bg-agfa-blue transition-colors duration-300"
          @click="toggleSceneChangesPanel"
          :title="isSceneChangesCollapsed ? 'Déplier' : 'Replier'"
          style="transition: right 0.2s;"
        >
          <ArrowSvg :class="isSceneChangesCollapsed ? 'w-4 h-4 rotate-180' : 'w-4 h-4'" />
        </button>

        <transition name="fade">
          <div
            v-if="!isSceneChangesCollapsed"
            class="fixed top-[88px] right-0 z-40 h-[calc(100vh-88px)] w-64 max-w-full flex flex-col pr-2 pointer-events-none"
          >
            <SceneChangesList
              :sceneChanges="uniqueSceneChangeTimecodes"
              :selected="selectedSceneChangeIdx ?? undefined"
              @select="onSelectSceneChange"
              @edit="onEditSceneChange"
              @delete="onDeleteSceneChange"
              @add="onAddSceneChange"
              @seekTo="onNavigationSeek"
            />
          </div>
        </transition>
      </div>
      <div>
        <button
          class="fixed top-[88px] left-0 z-50 bg-agfa-dark text-white border border-gray-600 rounded-r-lg w-7 h-12 flex items-center justify-center cursor-pointer shadow-lg text-lg p-0 hover:bg-agfa-blue transition-colors duration-300"
          @click="toggleTimecodesPanel"
          :title="isTimecodesCollapsed ? 'Déplier' : 'Replier'"
          style="transition: left 0.2s;"
        >
          <ArrowSvg :class="isTimecodesCollapsed ? 'w-4 h-4' : 'w-4 h-4 rotate-180'" />
        </button>

        <transition name="fade">
          <div
            v-if="!isTimecodesCollapsed"
            class="fixed top-[88px] left-0 z-40 h-[calc(100vh-88px)] w-80 max-w-full flex flex-col "
          >
                      <TimecodesListMultiLine
            :timecodes="compatibleTimecodes"
            :rythmo-lines-count="project?.rythmo_lines_count || 1"
            :selected="selectedTimecode || undefined"
            :project-id="project?.id || 0"
            @select="selectTimecode"
            @edit="editTimecode"
            @delete="deleteTimecode"
            @add="onAddTimecode"
            @add-to-line="addTimecodeToLine"
            @updated="loadTimecodes"
          />
          </div>
        </transition>
      </div>

      <!-- Center Panel - Video and Controls -->
      <div class="flex-1 flex flex-col items-center bg-agfa-dark rounded-lg p-4 shadow-lg min-w-0 mr-4 lg:mr-0 lg:max-w-full lg:px-1">
        <VideoPlayer
          v-if="project && project.video_path"
          :src="getVideoUrl(project.video_path)"
          :currentTime="currentTime"
          @timeupdate="onVideoTimeUpdate"
          @loadedmetadata="onLoadedMetadata"
        />
        <div v-else class="w-full max-w-3xl h-96 bg-gray-800 text-white flex items-center justify-center rounded-lg">
          Aucune vidéo
        </div>


        <!-- Boutons ajout changement de plan et ajout timecode -->
        <div class="flex flex-row gap-2 mt-4 mb-2">
          <button
            class="bg-agfa-blue hover:bg-agfa-blue-hover text-white border-none rounded-lg px-5 py-2 text-base font-bold cursor-pointer shadow-lg transition-colors duration-300"
            @click="addSceneChange"
          >
            Ajouter un changement de plan
          </button>
          <button
            class="bg-agfa-strong hover:bg-agfa-blue-hover text-white border-none rounded-lg px-5 py-2 text-base font-bold cursor-pointer shadow-lg transition-colors duration-300"
            @click="onAddTimecode"
            title="Ajouter un timecode"
          >
            + Timecode
          </button>
        </div>

  <!-- CharactersList moved inside MultiRythmoBand (forwarded props/events) -->

        <!-- Barre de navigation vidéo -->
        <VideoNavigationBar
          v-if="project && videoDuration > 0"
          :currentTime="currentTime"
          :videoDuration="videoDuration"
          :timecodes="compatibleTimecodes"
          :sceneChanges="uniqueSceneChangeTimecodes"
          :isVideoPaused="isVideoPaused"
          :rythmoLinesCount="project.rythmo_lines_count"
          @seek="onNavigationSeek"
          @seekDelta="seek"
          @seekFrame="seekFrame"
          @togglePlayPause="updatePlayPauseState"
        />

        <!-- Configuration multi-lignes et bandes rythmo -->
        <MultiRythmoBand
          v-if="project"
          :key="rythmoReloadKey"
          :timecodes="compatibleTimecodes"
          :sceneChanges="uniqueSceneChanges"
          :currentTime="currentTime"
          :videoDuration="videoDuration"
          :instant="instantRythmoScroll"
          :rythmoLinesCount="Number(project.rythmo_lines_count || 1)"
          :characters="allCharacters"
          :activeCharacter="activeCharacter"
          @character-selected="onCharacterSelected"
          @add-character="onAddCharacter"
          @edit-character="onEditCharacter"
          @character-deleted="onCharacterDeleted"
          @seek="onRythmoSeek"
          @update-timecode="onUpdateTimecode"
          @update-timecode-bounds="onUpdateTimecodeBounds"
          @move-timecode="onMoveTimecode"
          @update-timecode-show-character="onUpdateTimecodeShowCharacter"
          @update-timecode-character="onUpdateTimecodeCharacter"
          @delete-timecode="onDeleteTimecode"
          @add-timecode-to-line="onAddTimecodeToLine"
          @update-lines-count="onUpdateLinesCount"
          @selected-line-changed="onSelectedLineChanged"
          @update-scene-change="onUpdateSceneChangeFromBand"
          @delete-scene-change="onDeleteSceneChangeFromBand"
        />




        <!-- <RythmoControls
          :isVideoPaused="isVideoPaused"
          @seek="seek"
          @seekFrame="seekFrame"
        /> -->
      </div>
    </div>

    <!-- Modal d'édition/ajout de timecode (utilise le composant dédié) -->
    <TimecodeModal
      v-if="showTimecodeModal"
      :show="showTimecodeModal"
      :timecode="editTimecodeIdx !== null ? compatibleTimecodes[editTimecodeIdx] : null"
      :maxLines="project?.rythmo_lines_count || 1"
      :defaultLineNumber="modalTimecode.line_number"
      :currentTime="currentTime"
      @submit="onTimecodeModalSubmit"
      @close="closeTimecodeModal"
    />

    <!-- Modal de gestion des personnages -->
    <CharacterModal
      v-if="showCharacterModal && project"
      :projectId="project.id"
      :editingCharacter="editingCharacter"
      @close="closeCharacterModal"
      @saved="onCharacterSaved"
    />

    <!-- Modal d'édition de changement de plan -->
    <SceneChangeEditModal
      :show="showSceneChangeModal"
      :timecode="editSceneChangeIdx !== null ? sceneChanges[editSceneChangeIdx]?.timecode : null"
      @submit="onSceneChangeModalSubmit"
      @close="closeSceneChangeModal"
    />

    <!-- Modal des raccourcis clavier -->
    <KeyboardShortcutsModal
      :show="showKeyboardShortcuts"
      @close="showKeyboardShortcuts = false"
    />

    <!-- Modal des paramètres du projet -->
    <ProjectSettingsModal
      :show="showProjectSettings"
      @close="showProjectSettings = false"
    />

    <!-- Modal de gestion des collaborateurs -->
    <div v-if="showCollaboratorsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-2xl w-full max-h-[80vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white">
            Gérer les collaborateurs
          </h3>
          <button
            @click="showCollaboratorsModal = false"
            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>

        <CollaboratorsPanel
          v-if="project"
          :projectId="project.id"
          :canManage="canManageProject"
        />
      </div>
    </div>
  </div>

</template>

<script setup lang="ts">
import TimecodeModal from '../components/projectDetail/TimecodeModal.vue'
import KeyboardShortcutsModal from '../components/projectDetail/KeyboardShortcutsModal.vue'
import ProjectSettingsModal from '../components/projectDetail/ProjectSettingsModal.vue'
import { UsersIcon } from '@heroicons/vue/24/outline'
// Contrôle du scroll instantané pour la bande rythmo

const instantRythmoScroll = ref(true) // true = instantané, false = smooth
import BackSvg from '../assets/icons/back.svg'
import ArrowSvg from '../assets/icons/arrow.svg'
// Gestion du repli horizontal de la partie timecodes (fermé par défaut)
const isTimecodesCollapsed = ref(true)
function toggleTimecodesPanel() {
  isTimecodesCollapsed.value = !isTimecodesCollapsed.value
}
import { useRouter, useRoute } from 'vue-router'
import { ref, onMounted, reactive, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useProjectSettingsStore } from '@/stores/projectSettings'
import api from '../api/axios'
import { AxiosError } from 'axios'
import { timecodeApi, type Timecode as ApiTimecode } from '../api/timecodes'
import { characterApi, type Character } from '../api/characters'
import * as sceneChangesApi from '../api/sceneChanges'
import TimecodesListMultiLine from '../components/projectDetail/TimecodesListMultiLine.vue'
import SceneChangesList from '../components/projectDetail/SceneChangesList.vue'
import CharacterModal from '../components/projectDetail/CharacterModal.vue'
import SceneChangeEditModal from '../components/projectDetail/SceneChangeEditModal.vue'
import CollaboratorsPanel from '../components/projectDetail/CollaboratorsPanel.vue'
// Gestion du repli horizontal de la partie scene changes (fermé par défaut)
const isSceneChangesCollapsed = ref(true)
function toggleSceneChangesPanel() {
  isSceneChangesCollapsed.value = !isSceneChangesCollapsed.value
}

const selectedSceneChangeIdx = ref<number | null>(null)
function onSelectSceneChange(idx: number) {
  selectedSceneChangeIdx.value = idx
  // Seek vidéo si possible
  const t = sceneChanges.value[idx]
  if (typeof t === 'number') {
    lastSeekFromTimecode = true
    currentTime.value = t
    instantRythmoScroll.value = true
  }
}
function onEditSceneChange(idx: number) {
  selectedSceneChangeIdx.value = idx
  editSceneChangeIdx.value = idx
  showSceneChangeModal.value = true
}
function onAddSceneChange() {
  addSceneChange()
}
async function onDeleteSceneChange(idx: number) {
  const sc = sceneChanges.value[idx]
  if (!sc) return
  try {
    await api.delete(`/scene-changes/${sc.id}`)
    sceneChanges.value.splice(idx, 1)
  } catch {
    // TODO: gestion d'erreur
  }
}

// Nouveaux gestionnaires pour les événements des bandes rythmo
async function onUpdateSceneChangeFromBand(payload: { id: number; newTimecode: number; isPreview: boolean }) {
  if (payload.isPreview) {
    // Pour le preview, on peut juste retourner sans rien faire
    // Le feedback visuel est géré par le composant qui drag
    return
  }

  try {
    const updatedSceneChange = await sceneChangesApi.updateSceneChange(payload.id, {
      timecode: payload.newTimecode
    })

    // Mettre à jour dans la liste locale
    const index = sceneChanges.value.findIndex(sc => sc.id === payload.id)
    if (index !== -1) {
      sceneChanges.value[index] = updatedSceneChange
      // Retrier par timecode
      sceneChanges.value.sort((a, b) => a.timecode - b.timecode)
    }
  } catch (error) {
    console.error('Erreur lors de la mise à jour du changement de plan:', error)
    // TODO: Afficher un message d'erreur à l'utilisateur
  }
}

async function onDeleteSceneChangeFromBand(payload: { id: number }) {
  try {
    await sceneChangesApi.deleteSceneChange(payload.id)

    // Retirer de la liste locale
    const index = sceneChanges.value.findIndex(sc => sc.id === payload.id)
    if (index !== -1) {
      sceneChanges.value.splice(index, 1)
    }
  } catch (error) {
    console.error('Erreur lors de la suppression du changement de plan:', error)
    // TODO: Afficher un message d'erreur à l'utilisateur
  }
}
import VideoPlayer from '../components/projectDetail/VideoPlayer.vue'
import VideoNavigationBar from '../components/projectDetail/VideoNavigationBar.vue'
import MultiRythmoBand from '../components/projectDetail/MultiRythmoBand.vue'

// import RythmoControls from '../components/projectDetail/RythmoControls.vue'

const route = useRoute()
const router = useRouter()
const settingsStore = useProjectSettingsStore()

// Fonction de retour aux projets
function goBack() {
  router.push({ name: 'home' })
}

// Liste des changements de plan (objets {id, timecode})
// Utilise le type depuis l'API
type SceneChange = sceneChangesApi.SceneChange
const sceneChanges = ref<SceneChange[]>([])

// Computed pour s'assurer que sceneChanges n'a pas de doublons
const uniqueSceneChanges = computed(() => {
  return sceneChanges.value.filter((sc, index, array) =>
    array.findIndex(s => s.id === sc.id) === index
  )
})

function goToFinalPreview() {
  if (!project.value || !project.value.video_path || compatibleTimecodes.value.length === 0) return
  router.push({
    name: 'final-preview',
    query: {
      video: getVideoUrl(project.value.video_path),
      rythmo: JSON.stringify(compatibleTimecodes.value),
      rythmoLinesCount: String(project.value.rythmo_lines_count || 1),
    },
  })
}
interface Timecode {
  id?: number
  project_id?: number
  start: number
  end: number
  text: string
  line_number?: number
  character_id?: number | null
  show_character?: boolean
}
interface Project {
  id: number
  name: string
  description?: string
  video_path?: string
  timecodes?: Timecode[]
  rythmo_lines_count?: number
  user_id?: number
  owner?: { id: number; name: string; email: string }
  collaborators?: Array<{
    id: number;
    name: string;
    email: string;
    permission?: string;
    pivot?: { permission: string; created_at: string }
  }>
}
const project = ref<Project | null>(null)
const loading = ref(true)
const currentTime = ref(0)
// Pour éviter de rebinder le currentTime à chaque update (empêche le seek natif)
let lastSeekFromTimecode = false
const videoDuration = ref(0)
const videoFps = ref(25) // valeur par défaut, sera mise à jour
const isVideoPaused = ref(true)
const selectedTimecodeIdx = ref<number | null>(null)
// Flag global indiquant qu'on est dans un champ texte (désactive les raccourcis)
const isEditingText = ref(false)
// Pour savoir si on doit reprendre la lecture après édition
let resumePlaybackAfterEdit = false
// Stockage des handlers focus pour cleanup (références module-level)
let focusInHandler: ((e: Event) => void) | null = null
let focusOutHandler: ((e: Event) => void) | null = null

// Clé pour forcer la reconstruction complète du composant MultiRythmoBand
const generateRythmoKey = () => `rythmo-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`
const rythmoReloadKey = ref(generateRythmoKey())

// Timecodes multi-lignes (nouvelle API)
const allTimecodes = ref<ApiTimecode[]>([])

// Gestion des personnages
const allCharacters = ref<Character[]>([])
const activeCharacter = ref<Character | null>(null)
const showCharacterModal = ref(false)
const editingCharacter = ref<Character | null>(null)

// Gestion du modal des raccourcis clavier
const showKeyboardShortcuts = ref(false)
const showCollaboratorsModal = ref(false)
const showProjectSettings = ref(false)

// Ligne actuellement sélectionnée (pour création de nouveaux timecodes)
const selectedLineNumber = ref<number>(1)

// Store d'authentification
const authStore = useAuthStore()

// Computed pour les timecodes de scene changes uniques
const uniqueSceneChangeTimecodes = computed(() => {
  // Utilise un Set pour éliminer les doublons, puis convertit en array
  const uniqueTimecodes = [...new Set(uniqueSceneChanges.value.map(sc => sc.timecode))]
  return uniqueTimecodes.sort((a, b) => a - b)
})

// Fonction pour trouver une position libre pour un nouveau timecode
function findFreeTimecodePosition(
  preferredStart: number,
  duration: number,
  lineNumber: number
): { start: number; end: number } {
  const MARGIN = 0.1 // Marge de sécurité de 0.1 seconde

  // Récupère tous les timecodes de la même ligne, triés par ordre de début
  const sameLineTimecodes = allTimecodes.value
    .filter(tc => tc.line_number === lineNumber)
    .sort((a, b) => a.start - b.start)

  // Si pas de timecodes sur cette ligne, utiliser la position préférée
  if (sameLineTimecodes.length === 0) {
    return { start: preferredStart, end: preferredStart + duration }
  }

  // Cherche le dernier timecode et place le nouveau après
  const lastTimecode = sameLineTimecodes[sameLineTimecodes.length - 1]
  const newStart = lastTimecode.end + MARGIN

  return { start: newStart, end: newStart + duration }
}

// Fonction pour ajuster les bornes d'un timecode modifié
function adjustTimecodeForModal(
  newStart: number,
  newEnd: number,
  lineNumber: number,
  excludeTimecodeId?: number
): { start: number; end: number } {
  const MARGIN = 0.1 // Marge de sécurité de 0.1 seconde

  // Récupère tous les timecodes de la même ligne, sauf celui qu'on exclut
  const sameLineTimecodes = allTimecodes.value
    .filter(tc => tc.line_number === lineNumber && tc.id !== excludeTimecodeId)
    .sort((a, b) => a.start - b.start)

  let adjustedStart = newStart
  let adjustedEnd = newEnd

  // Trouve les timecodes qui pourraient être en conflit
  const conflictingBefore = sameLineTimecodes.filter(tc => tc.end > adjustedStart - MARGIN && tc.start < adjustedStart)
  const conflictingAfter = sameLineTimecodes.filter(tc => tc.start < adjustedEnd + MARGIN && tc.end > adjustedEnd)

  // Ajuster le début si conflit avec un timecode précédent
  if (conflictingBefore.length > 0) {
    const lastConflict = conflictingBefore[conflictingBefore.length - 1]
    adjustedStart = lastConflict.end + MARGIN
  }

  // Ajuster la fin si conflit avec un timecode suivant
  if (conflictingAfter.length > 0) {
    const firstConflict = conflictingAfter[0]
    adjustedEnd = firstConflict.start - MARGIN
  }

  // Recalculer la fin selon le nouveau début si nécessaire
  const originalDuration = newEnd - newStart
  if (adjustedStart !== newStart) {
    adjustedEnd = adjustedStart + originalDuration

    // Vérifier à nouveau les conflits après
    const stillConflictingAfter = sameLineTimecodes.find(tc =>
      tc.start < adjustedEnd + MARGIN && tc.end > adjustedEnd
    )
    if (stillConflictingAfter) {
      adjustedEnd = stillConflictingAfter.start - MARGIN
    }
  }

  // S'assurer que start < end avec une durée minimale
  if (adjustedStart >= adjustedEnd) {
    adjustedEnd = adjustedStart + Math.max(0.5, originalDuration)
  }

  return { start: adjustedStart, end: adjustedEnd }
}

// Vérifier si l'utilisateur a accès au projet (lecture)
const hasProjectAccess = computed(() => {
  if (!project.value || !authStore.user) return false

  // Admin global a toujours accès
  if (authStore.isAdmin) return true

  // Propriétaire du projet a accès
  if (project.value.user_id === authStore.user.id) return true

  // Collaborateur a accès
  if (project.value.collaborators && authStore.user) {
    return project.value.collaborators.some(
      collab => collab.id === authStore.user!.id
    )
  }

  return false
})

// Vérifier si l'utilisateur peut gérer le projet
const canManageProject = computed(() => {
  if (!project.value || !authStore.user) return false

  // Admin global ou propriétaire du projet
  if (authStore.isAdmin || project.value.user_id === authStore.user.id) {
    return true
  }

  // Collaborateur avec permission 'admin'
  if (project.value.collaborators && authStore.user) {
    const userCollaborator = project.value.collaborators.find(
      collab => collab.id === authStore.user!.id
    )
    if (userCollaborator) {
      // Support des deux structures possibles: permission directe ou dans pivot
      const permission = userCollaborator.permission || userCollaborator.pivot?.permission
      return permission === 'admin'
    }
  }

  return false
})

// Conversion temporaire des anciens timecodes en format compatible
const compatibleTimecodes = computed(() => {
  // Protection contre les états de chargement
  if (loading.value) return []

  // Utilise d'abord les nouveaux timecodes de l'API
  if (allTimecodes.value.length > 0) {
    // Filtrer les doublons par ID au cas où il y en aurait
    const uniqueTimecodes = allTimecodes.value.filter((tc, index, array) =>
      tc.id != null && array.findIndex(t => t.id === tc.id) === index
    )
    return uniqueTimecodes
  }

  // Fallback sur les anciens timecodes (JSON) s'il n'y en a pas dans la nouvelle table
  if (!project.value?.timecodes) {
    return []
  }

  let oldTimecodes = project.value.timecodes
  if (typeof oldTimecodes === 'string') {
    try {
      oldTimecodes = JSON.parse(oldTimecodes)
    } catch (error) {
      console.error('Failed to parse timecodes JSON:', error)
      oldTimecodes = []
    }
  }

  if (!Array.isArray(oldTimecodes)) {
    console.error('oldTimecodes is not an array:', oldTimecodes)
    return []
  }

  // Convertit en format ApiTimecode avec line_number = 1 par défaut
  return oldTimecodes.map((tc, index) => {
    // Crée un ID temporaire vraiment unique basé sur les propriétés du timecode
    const hash = Math.abs(
      (project.value!.id * 1000000) +
      (index * 10000) +
      (Math.round(tc.start * 100)) +
      (Math.round(tc.end * 100))
    )
    return {
      id: hash + 100000, // Décale pour éviter les conflits avec les vrais IDs
      project_id: project.value!.id,
      line_number: 1, // Tous sur la ligne 1 par défaut
      start: tc.start,
      end: tc.end,
      text: tc.text
    }
  })
})

// Fonctions pour les timecodes multi-lignes
async function loadTimecodes() {
  if (!project.value) return
  try {
    const res = await timecodeApi.getAll(project.value.id)
    allTimecodes.value = res.data.timecodes
  } catch (error) {
    console.error('Error loading timecodes:', error)
    allTimecodes.value = []
  }
}

// Chargement des personnages
async function loadCharacters() {
  if (!project.value) return
  try {
    const res = await characterApi.getAll(project.value.id)
    allCharacters.value = res.data.characters
    // Sélectionner automatiquement le premier personnage comme actif
    if (allCharacters.value.length > 0 && !activeCharacter.value) {
      activeCharacter.value = allCharacters.value[0]
    }
  } catch (error) {
    console.error('Error loading characters:', error)
    allCharacters.value = []
  }
}

// Computed property pour le timecode sélectionné
const selectedTimecode = computed(() => {
  if (selectedTimecodeIdx.value === null) return null
  return compatibleTimecodes.value[selectedTimecodeIdx.value] || null
})

// Fonctions pour la nouvelle interface TimecodesListMultiLine
function selectTimecode(timecode: Timecode) {
  const idx = compatibleTimecodes.value.findIndex(tc =>
    tc.id === timecode.id ||
    (tc.start === timecode.start && tc.end === timecode.end && tc.text === timecode.text)
  )
  if (idx >= 0) {
    onSelectTimecode(idx)
  }
}

function editTimecode(timecode: Timecode) {
  const idx = compatibleTimecodes.value.findIndex(tc =>
    tc.id === timecode.id ||
    (tc.start === timecode.start && tc.end === timecode.end && tc.text === timecode.text)
  )
  if (idx >= 0) {
    onEditTimecode(idx)
  }
}

function deleteTimecode(timecode: Timecode) {
  onDeleteTimecode({ timecode })
}

function addTimecodeToLine(lineNumber: number) {
  // Ouvre le modal avec la ligne pré-sélectionnée
  editTimecodeIdx.value = null
  Object.assign(modalTimecode, {
    start: currentTime.value,
    end: currentTime.value + 2,
    text: '',
    line_number: lineNumber,
    character_id: activeCharacter.value?.id || null
  })
  showTimecodeModal.value = true
}

function onAddTimecodeToLine() {
  // TODO: Ouvrir modal pour ajouter timecode sur cette ligne
}

async function onUpdateLinesCount(count: number) {
  if (!project.value) return
  try {
    await api.patch(`/projects/${project.value.id}/rythmo-lines`, { rythmo_lines_count: count })
    project.value.rythmo_lines_count = count
  } catch (error) {
    console.error('Erreur lors de la mise à jour du nombre de lignes:', error)
  }
}

// ===== GESTION DES PERSONNAGES =====

function onCharacterSelected(character: Character) {
  activeCharacter.value = character
}

function onAddCharacter() {
  editingCharacter.value = null
  showCharacterModal.value = true
}

function onEditCharacter(character: Character) {
  editingCharacter.value = character
  showCharacterModal.value = true
}

function onCharacterSaved(character: Character) {
  showCharacterModal.value = false
  editingCharacter.value = null

  // Ajouter ou mettre à jour le personnage dans la liste
  const existingIndex = allCharacters.value.findIndex(c => c.id === character.id)
  if (existingIndex >= 0) {
    allCharacters.value[existingIndex] = character
  } else {
    allCharacters.value.push(character)
  }

  // Si c'est le premier personnage ou pas de personnage actif, le sélectionner
  if (!activeCharacter.value || allCharacters.value.length === 1) {
    activeCharacter.value = character
  }
}

function onCharacterDeleted(characterId: number) {
  allCharacters.value = allCharacters.value.filter(c => c.id !== characterId)

  // Si le personnage supprimé était actif, sélectionner le premier disponible
  if (activeCharacter.value?.id === characterId) {
    activeCharacter.value = allCharacters.value[0] || null
  }

  // Recharger les timecodes pour refléter les changements
  loadTimecodes()
}

function closeCharacterModal() {
  showCharacterModal.value = false
  editingCharacter.value = null
}

// Gestionnaire pour les changements de ligne sélectionnée
function onSelectedLineChanged(lineNumber: number) {
  selectedLineNumber.value = lineNumber
}

// Nouvelle fonction onUpdateTimecode pour le nouveau format
async function onUpdateTimecode({ timecode, text }: { timecode: ApiTimecode | Timecode; text: string }) {
  const tc = timecode as ApiTimecode
  if (!tc.id || !project.value) return
  try {
    await timecodeApi.update(project.value.id, tc.id, { text })
    // Met à jour localement
    const index = allTimecodes.value.findIndex(t => t.id === tc.id)
    if (index >= 0) {
      allTimecodes.value[index].text = text
    }
  } catch {
    // TODO: gestion d'erreur
  }
}

// Nouvelle fonction pour le redimensionnement des timecodes
async function onUpdateTimecodeBounds({ timecode, start, end }: { timecode: ApiTimecode | Timecode; start: number; end: number }) {
  const tc = timecode as ApiTimecode
  if (!tc.id || !project.value) return
  try {
    await timecodeApi.update(project.value.id, tc.id, { start, end })
    // Met à jour localement
    const index = allTimecodes.value.findIndex(t => t.id === tc.id)
    if (index >= 0) {
      allTimecodes.value[index].start = start
      allTimecodes.value[index].end = end
    }
  } catch (error) {
    console.error('Erreur lors de la mise à jour des bornes du timecode:', error)
  }
}

// Nouvelle fonction pour le déplacement des timecodes
async function onMoveTimecode({ timecode, newStart, newLineNumber }: { timecode: ApiTimecode | Timecode; newStart: number; newLineNumber: number }) {
  const tc = timecode as ApiTimecode
  if (!tc.id || !project.value) return

  try {
    // Calcule la nouvelle fin en gardant la même durée
    const duration = tc.end - tc.start
    const newEnd = newStart + duration

    await timecodeApi.update(project.value.id, tc.id, {
      start: newStart,
      end: newEnd,
      line_number: newLineNumber
    })

    // Met à jour localement
    const index = allTimecodes.value.findIndex(t => t.id === tc.id)
    if (index >= 0) {
      allTimecodes.value[index].start = newStart
      allTimecodes.value[index].end = newEnd
      allTimecodes.value[index].line_number = newLineNumber
    }

  } catch (error) {
    console.error('Erreur lors du déplacement du timecode:', error)
  }
}

// Nouvelle fonction pour basculer l'affichage du personnage
async function onUpdateTimecodeShowCharacter({ timecode, showCharacter }: { timecode: ApiTimecode | Timecode; showCharacter: boolean }) {
  const tc = timecode as ApiTimecode
  if (!tc.id || !project.value) return

  try {
    await timecodeApi.update(project.value.id, tc.id, { show_character: showCharacter })

    // Met à jour localement
    const index = allTimecodes.value.findIndex(t => t.id === tc.id)
    if (index >= 0) {
      allTimecodes.value[index].show_character = showCharacter
    }
  } catch (error) {
    console.error('Erreur lors de la mise à jour de l\'affichage du personnage:', error)
  }
}

// Nouvelle fonction pour changer le personnage d'un timecode
async function onUpdateTimecodeCharacter({ timecode, characterId }: { timecode: ApiTimecode | Timecode; characterId: number | null }) {
  const tc = timecode as ApiTimecode
  if (!tc.id || !project.value) return

  try {
    await timecodeApi.update(project.value.id, tc.id, { character_id: characterId })

    // Met à jour localement
    const index = allTimecodes.value.findIndex(t => t.id === tc.id)
    if (index >= 0) {
      allTimecodes.value[index].character_id = characterId
      // Trouve et assigne l'objet character complet
      if (characterId) {
        allTimecodes.value[index].character = allCharacters.value.find(c => c.id === characterId)
      } else {
        allTimecodes.value[index].character = undefined
      }
    }
  } catch (error) {
    console.error('Erreur lors de la mise à jour du personnage du timecode:', error)
  }
}

// Ajout d'un changement de plan au timecode courant
async function addSceneChange() {
  if (!project.value) return
  const t = Math.round(currentTime.value * 100) / 100
  // Vérifie si déjà présent (tolérance 0.01s)
  if (sceneChanges.value.some(sc => Math.abs(sc.timecode - t) < 0.01)) return
  try {
    const res = await api.post(`/projects/${project.value.id}/scene-changes`, { timecode: t })
    sceneChanges.value.push(res.data)
    sceneChanges.value.sort((a, b) => a.timecode - b.timecode)
  } catch {
    // TODO: gestion d'erreur
  }
}

// Modal d'édition/ajout de timecode
const showTimecodeModal = ref(false)
const editTimecodeIdx = ref<number | null>(null)
const modalTimecode = reactive<Timecode>({ start: 0, end: 0, text: '', line_number: 1, character_id: null })

// Modal d'édition de changement de plan
const showSceneChangeModal = ref(false)
const editSceneChangeIdx = ref<number | null>(null)

function getVideoUrl(path?: string) {
  if (!path) return ''
  if (path.startsWith('http')) return path
  const apiBase = import.meta.env.VITE_API_URL?.replace(/\/api\/?$/, '') || ''
  return `${apiBase}/api/videos/${encodeURIComponent(path)}`
}

function onVideoTimeUpdate(time: number) {
  // Si le seek vient d'un clic sur timecode, on ignore le premier event
  if (lastSeekFromTimecode) {
    lastSeekFromTimecode = false
    return
  }
  currentTime.value = time
  // Sélectionne le timecode courant
  if (compatibleTimecodes.value.length > 0) {
    const idx = compatibleTimecodes.value.findIndex((tc) => time >= tc.start && time < tc.end)
    selectedTimecodeIdx.value = idx >= 0 ? idx : null
  }
  // Si la vidéo joue, smooth, sinon instantané
  const videoEl = document.querySelector('video') as HTMLVideoElement | null
  instantRythmoScroll.value = !videoEl || videoEl.paused
}
function onLoadedMetadata(duration: number) {
  videoDuration.value = duration
}

function seek(delta: number) {
  // Si delta vaut 0, toggle play/pause sur la vidéo
  const videoEl = document.querySelector('video') as HTMLVideoElement | null
  if (delta === 0) {
    if (videoEl) {
      if (videoEl.paused) {
        videoEl.play()
        instantRythmoScroll.value = false // smooth
      } else {
        videoEl.pause()
        instantRythmoScroll.value = true // instantané
      }
      isVideoPaused.value = videoEl.paused
    }
    return
  }
  // Avance/recul d'une seconde
  let t = currentTime.value + delta
  t = Math.max(0, Math.min(videoDuration.value, t))
  currentTime.value = t
  // Si on modifie le temps, on met à jour la vidéo si possible
  if (videoEl) videoEl.currentTime = t
  instantRythmoScroll.value = true // instantané
}
function seekFrame(delta: number) {
  // Avance/recul d'une frame selon les fps
  const frameDuration = 1 / videoFps.value
  let t = currentTime.value + delta * frameDuration
  t = Math.max(0, Math.min(videoDuration.value, t))
  currentTime.value = t
  // Met à jour la vidéo si possible
  const videoEl = document.querySelector('video') as HTMLVideoElement | null
  if (videoEl) videoEl.currentTime = t
  instantRythmoScroll.value = true // instantané
}

function updatePlayPauseState() {
  // Met à jour l'état isVideoPaused pour synchroniser l'interface
  const videoEl = document.querySelector('video') as HTMLVideoElement | null
  if (videoEl) {
    isVideoPaused.value = videoEl.paused
    instantRythmoScroll.value = videoEl.paused
  }
}
function onSelectTimecode(idx: number) {
  selectedTimecodeIdx.value = idx
  // Seek vidéo si possible
  const tc = compatibleTimecodes.value[idx]
  if (tc) {
    lastSeekFromTimecode = true
    currentTime.value = tc.start
    // Scroll instantané lors d'un seek manuel
    instantRythmoScroll.value = true
  }
}
function onEditTimecode(idx: number) {
  editTimecodeIdx.value = idx
  const tc = compatibleTimecodes.value[idx]
  if (tc) {
    Object.assign(modalTimecode, {
      start: tc.start,
      end: tc.end,
      text: tc.text,
      line_number: tc.line_number || 1
    })
  }
  showTimecodeModal.value = true
}
async function onAddTimecode() {
  if (!project.value) return

  try {
    // Trouver une position libre pour le nouveau timecode
    const freePosition = findFreeTimecodePosition(
      currentTime.value,
      6, // durée de 6 secondes
      selectedLineNumber.value
    )

    // Créer un nouveau timecode de 6 secondes directement dans la base
    const newTimecodeData = {
      start: freePosition.start,
      end: freePosition.end,
      text: 'Insérer du texte ici',
      line_number: selectedLineNumber.value, // Utilise la ligne actuellement sélectionnée
      character_id: activeCharacter.value?.id || null,
      show_character: !!activeCharacter.value
    }

    await timecodeApi.create(project.value.id, newTimecodeData)

    // Recharger les timecodes pour récupérer le nouveau
    await loadTimecodes()

    // Le timecode est créé avec le texte "Insérer du texte ici"
    // L'utilisateur peut double-cliquer dessus pour l'éditer
  } catch (error) {
    console.error('Erreur lors de la création du timecode:', error)
  }
}
async function onDeleteTimecode(payload: { timecode: Timecode }) {
  if (!project.value) return

  const timecodeToDelete = payload.timecode
  if (!timecodeToDelete?.id) return

  try {
    await timecodeApi.delete(project.value.id, timecodeToDelete.id)
    // Recharger les timecodes
    await loadTimecodes()
  } catch (error) {
    console.error('Erreur lors de la suppression du timecode:', error)
  }
}
// Callback pour la soumission du modal de timecode
function onTimecodeModalSubmit(data: { line_number: number; start: number; end: number; text: string }) {
  if (!project.value) return

  // Si editTimecodeIdx !== null, on met à jour le timecode existant
  if (editTimecodeIdx.value !== null) {
    const tc = compatibleTimecodes.value[editTimecodeIdx.value]
    if (tc?.id) {
      // Ajuster les bornes pour éviter les superpositions
      const adjustedBounds = adjustTimecodeForModal(
        data.start,
        data.end,
        data.line_number,
        tc.id
      )

      const adjustedData = {
        ...data,
        start: adjustedBounds.start,
        end: adjustedBounds.end
      }

      timecodeApi.update(project.value.id, tc.id, adjustedData).then(() => {
        loadTimecodes()
        closeTimecodeModal()
      })
    }
    return
  }

  // Pour la création, trouver une position libre
  const freePosition = findFreeTimecodePosition(
    data.start,
    data.end - data.start,
    data.line_number
  )

  const adjustedData = {
    ...data,
    start: freePosition.start,
    end: freePosition.end
  }

  // Sinon on crée un nouveau timecode
  timecodeApi.create(project.value.id, adjustedData).then(() => {
    loadTimecodes()
    closeTimecodeModal()
  })
}
function closeTimecodeModal() {
  showTimecodeModal.value = false
}

// Gestion du modal de changement de plan
async function onSceneChangeModalSubmit(newTimecode: number) {
  if (editSceneChangeIdx.value === null || !project.value) return

  const sceneChange = sceneChanges.value[editSceneChangeIdx.value]
  if (!sceneChange) return

  try {
    const updatedSceneChange = await sceneChangesApi.updateSceneChange(sceneChange.id, {
      timecode: newTimecode
    })

    // Mettre à jour dans la liste locale
    sceneChanges.value[editSceneChangeIdx.value] = updatedSceneChange
    sceneChanges.value.sort((a, b) => a.timecode - b.timecode)

    closeSceneChangeModal()
  } catch (error) {
    console.error('Erreur lors de la mise à jour du changement de plan:', error)
  }
}

function closeSceneChangeModal() {
  showSceneChangeModal.value = false
  editSceneChangeIdx.value = null
}

// Gestion des raccourcis clavier globaux
function handleGlobalKeydown(event: KeyboardEvent) {
  // Si on édite du texte, on ignore tous les raccourcis globaux
  if (isEditingText.value) return
  const isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0
  const cmdKey = isMac ? event.metaKey : event.ctrlKey

  // Si le modal des raccourcis est ouvert, Échap le ferme
  if (event.key === 'Escape' && showKeyboardShortcuts.value) {
    event.preventDefault()
    showKeyboardShortcuts.value = false
    return
  }

  // , pour toggle les raccourcis (sans Cmd/Ctrl) - AZERTY friendly
  if (event.key === ',' && !cmdKey && !event.shiftKey && !event.altKey) {
    event.preventDefault()
    showKeyboardShortcuts.value = !showKeyboardShortcuts.value
    return
  }

  // Cmd/Ctrl + ? pour ouvrir les raccourcis (garde l'ancien raccourci aussi)
  if (cmdKey && event.key === '/') {
    event.preventDefault()
    showKeyboardShortcuts.value = true
    return
  }

  // T pour ajouter un timecode
  if (event.key === 't' && !cmdKey && !event.shiftKey && !event.altKey) {
    event.preventDefault()
    onAddTimecode()
    return
  }

  // S pour ajouter un changement de scène
  if (event.key === 's' && !cmdKey && !event.shiftKey && !event.altKey) {
    event.preventDefault()
    addSceneChange()
    return
  }  // Échap pour retour aux projets (uniquement si le modal n'est pas ouvert)
  if (event.key === 'Escape' && !showKeyboardShortcuts.value) {
    event.preventDefault()
    goBack()
    return
  }

  // F pour aperçu final
  if (event.key === 'f' || event.key === 'F') {
    if (project.value && project.value.video_path && compatibleTimecodes.value.length > 0) {
      event.preventDefault()
      goToFinalPreview()
    }
    return
  }
}

onMounted(async () => {
  loading.value = true
  try {
    const res = await api.get(`/projects/${route.params.id}`)
    const data = res.data
    // Corrige le cas où timecodes est une string JSON
    if (typeof data.timecodes === 'string') {
      try {
        data.timecodes = JSON.parse(data.timecodes)
      } catch {
        data.timecodes = []
      }
    }
    // Si le backend fournit les fps, on les récupère
    if (typeof data.fps === 'number' && data.fps > 0) {
      videoFps.value = data.fps
    }
    project.value = data

    // Charger les paramètres du projet depuis l'API
    await settingsStore.loadSettings(data.id)

    // Récupère les changements de plan
    const scRes = await api.get(`/projects/${data.id}/scene-changes`)
    sceneChanges.value = Array.isArray(scRes.data) ? scRes.data : []

    // Charge les timecodes multi-lignes
    await loadTimecodes()

    // Charge les personnages
    await loadCharacters()
  } catch (error) {
    // Vérifier si c'est une erreur d'accès refusé (403)
    if (error instanceof AxiosError && error.response?.status === 403) {
      // Rediriger vers la page d'accueil avec un message d'erreur
      router.push({
        name: 'home',
        query: {
          error: 'Vous n\'avez pas les droits pour accéder à ce projet'
        }
      })
      return
    }

    // Pour les autres erreurs (404, 500, etc.), rediriger également
    project.value = null
    sceneChanges.value = []
    router.push({
      name: 'home',
      query: {
        error: 'Projet introuvable ou erreur de chargement'
      }
    })
  } finally {
    loading.value = false
  }

  // Ajouter les gestionnaires de raccourcis clavier
  window.addEventListener('keydown', handleGlobalKeydown)

  // Gestion focus/blur pour désactivation des raccourcis
  const onFocusIn = (ev: Event) => {
    const target = ev.target as HTMLElement | null
    if (!target) return
    if (target.matches('input, textarea, [contenteditable="true"], [contenteditable=""], [contenteditable]')) {
      if (!isEditingText.value) {
        isEditingText.value = true
        // Pause vidéo si en lecture
        const videoEl = document.querySelector('video') as HTMLVideoElement | null
        if (videoEl && !videoEl.paused) {
          videoEl.pause()
          resumePlaybackAfterEdit = true
        } else {
          resumePlaybackAfterEdit = false
        }
      }
    }
  }
  const onFocusOut = () => {
    // Attendre fin de boucle pour voir si nouveau focus est toujours dans un champ
    requestAnimationFrame(() => {
      const active = document.activeElement as HTMLElement | null
      if (active && (active.matches('input, textarea, [contenteditable="true"], [contenteditable=""], [contenteditable]'))) {
        return // toujours dans un champ
      }
      if (isEditingText.value) {
        isEditingText.value = false
        // Reprendre la lecture si nécessaire
        if (resumePlaybackAfterEdit) {
          const videoEl = document.querySelector('video') as HTMLVideoElement | null
          if (videoEl) {
            videoEl.play().catch(() => {})
          }
          resumePlaybackAfterEdit = false
        }
      }
    })
  }
  window.addEventListener('focusin', onFocusIn)
  window.addEventListener('focusout', onFocusOut)
  focusInHandler = onFocusIn
  focusOutHandler = onFocusOut
})

// Nettoyage des événements
import { onUnmounted } from 'vue'

onUnmounted(() => {
  window.removeEventListener('keydown', handleGlobalKeydown)
  if (focusInHandler) window.removeEventListener('focusin', focusInHandler)
  if (focusOutHandler) window.removeEventListener('focusout', focusOutHandler)
})


// Seek déclenché par clic sur un bloc de la bande rythmo
const onRythmoSeek = (time: number) => {
  lastSeekFromTimecode = true
  currentTime.value = time
  // Met à jour la vidéo si possible
  const videoEl = document.querySelector('video') as HTMLVideoElement | null
  if (videoEl) videoEl.currentTime = time
  // Sélectionne le timecode courant
  if (compatibleTimecodes.value.length > 0) {
    const idx = compatibleTimecodes.value.findIndex((tc) => time >= tc.start && time < tc.end)
    selectedTimecodeIdx.value = idx >= 0 ? idx : null
  }
  // Scroll instantané lors d'un seek manuel
  instantRythmoScroll.value = true
}

// Seek déclenché par la barre de navigation vidéo
const onNavigationSeek = (time: number) => {
  lastSeekFromTimecode = true
  currentTime.value = time
  // Met à jour la vidéo si possible
  const videoEl = document.querySelector('video') as HTMLVideoElement | null
  if (videoEl) videoEl.currentTime = time
  // Sélectionne le timecode courant
  if (compatibleTimecodes.value.length > 0) {
    const idx = compatibleTimecodes.value.findIndex((tc) => time >= tc.start && time < tc.end)
    selectedTimecodeIdx.value = idx >= 0 ? idx : null
  }
  // Scroll instantané lors d'un seek manuel
  instantRythmoScroll.value = true
}
</script>
<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.2s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>
