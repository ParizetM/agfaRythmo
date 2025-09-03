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

      <button
        v-if="project && project.video_path && compatibleTimecodes.length > 0"
        class="bg-agfa-blue hover:bg-agfa-blue-hover text-white border-none rounded-lg px-5 py-2 text-base font-bold cursor-pointer shadow-lg transition-colors duration-300"
        @click="goToFinalPreview"
        title="Aperçu final plein écran"
      >
        Aperçu final
      </button>
    </header>

    <!-- Main Grid -->
  <div class="w-full relative flex flex-row items-start justify-center lg:flex-col lg:gap-2">
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
            class="fixed top-[88px] right-0 z-40 h-[calc(100vh-88px)] w-80 max-w-full flex flex-col pr-2"
          >
            <SceneChangesList
              :sceneChanges="sceneChanges.map(sc => sc.timecode)"
              :selected="selectedSceneChangeIdx ?? undefined"
              @select="onSelectSceneChange"
              @edit="onEditSceneChange"
              @delete="onDeleteSceneChange"
              @add="onAddSceneChange"
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
            class="fixed top-[88px] left-0 z-40 h-[calc(100vh-88px)] w-80 max-w-full flex flex-col pl-2 "
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

        <!-- Bouton ajout changement de plan -->
        <button
          class="mt-4 mb-2 bg-agfa-blue hover:bg-agfa-blue-hover text-white border-none rounded-lg px-5 py-2 text-base font-bold cursor-pointer shadow-lg transition-colors duration-300"
          @click="addSceneChange"
        >
          Ajouter un changement de plan
        </button>

        <!-- Gestion des personnages -->
        <CharactersList
          v-if="project"
          :characters="allCharacters"
          :activeCharacter="activeCharacter"
          @character-selected="onCharacterSelected"
          @add-character="onAddCharacter"
          @edit-character="onEditCharacter"
          @character-deleted="onCharacterDeleted"
        />

        <!-- Configuration multi-lignes et bandes rythmo -->
        <MultiRythmoBand
          v-if="project"
          :key="rythmoReloadKey"
          :timecodes="compatibleTimecodes"
          :sceneChanges="sceneChanges.map(sc => sc.timecode)"
          :currentTime="currentTime"
          :videoDuration="videoDuration"
          :instant="instantRythmoScroll"
          :rythmoLinesCount="Number(project.rythmo_lines_count || 1)"
          @seek="onRythmoSeek"
          @update-timecode="onUpdateTimecode"
          @update-timecode-bounds="onUpdateTimecodeBounds"
          @move-timecode="onMoveTimecode"
          @update-timecode-show-character="onUpdateTimecodeShowCharacter"
          @add-timecode-to-line="onAddTimecodeToLine"
          @update-lines-count="onUpdateLinesCount"
        />




        <RythmoControls
          :isVideoPaused="isVideoPaused"
          @seek="seek"
          @seekFrame="seekFrame"
        />
      </div>
    </div>

    <!-- Modal d'édition/ajout de timecode -->
    <div v-if="showTimecodeModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-agfa-dark text-white rounded-xl p-8 min-w-80 shadow-2xl">
        <h4 class="text-xl font-bold mb-6">
          {{ editTimecodeIdx !== null ? 'Éditer' : 'Ajouter' }} un timecode
        </h4>

        <form @submit.prevent="saveTimecode" class="space-y-4">
          <label class="block">
            <span class="text-white mb-2 block">Ligne rythmo:</span>
            <select
              v-model.number="modalTimecode.line_number"
              :disabled="(project?.rythmo_lines_count || 1) === 1"
              required
              :class="[
                'w-full p-3 rounded-lg border border-gray-600 text-white focus:ring-2 focus:ring-agfa-blue focus:border-transparent outline-none transition-all duration-300',
                (project?.rythmo_lines_count || 1) === 1 ? 'bg-gray-700 cursor-not-allowed opacity-75' : 'bg-gray-800'
              ]"
            >
              <option v-for="n in (project?.rythmo_lines_count || 1)" :key="n" :value="n">
                {{ (project?.rythmo_lines_count || 1) === 1 ? 'Ligne unique' : `Ligne ${n}` }}
              </option>
            </select>
          </label>

          <label class="block">
            <span class="text-white mb-2 block">Début (s):</span>
            <input
              v-model.number="modalTimecode.start"
              type="number"
              step="0.01"
              min="0"
              required
              class="w-full p-3 rounded-lg border border-gray-600 bg-gray-800 text-white focus:ring-2 focus:ring-agfa-blue focus:border-transparent outline-none transition-all duration-300"
            />
          </label>

          <label class="block">
            <span class="text-white mb-2 block">Fin (s):</span>
            <input
              v-model.number="modalTimecode.end"
              type="number"
              step="0.01"
              min="0"
              required
              class="w-full p-3 rounded-lg border border-gray-600 bg-gray-800 text-white focus:ring-2 focus:ring-agfa-blue focus:border-transparent outline-none transition-all duration-300"
            />
          </label>

          <label class="block">
            <span class="text-white mb-2 block">Texte:</span>
            <input
              v-model="modalTimecode.text"
              type="text"
              required
              class="w-full p-3 rounded-lg border border-gray-600 bg-gray-800 text-white focus:ring-2 focus:ring-agfa-blue focus:border-transparent outline-none transition-all duration-300"
            />
          </label>

          <div class="flex gap-4 pt-4">
            <button
              type="submit"
              class="flex-1 bg-agfa-blue hover:bg-agfa-blue-hover text-white border-none rounded-lg py-3 px-5 cursor-pointer text-base font-medium transition-colors duration-300"
            >
              Valider
            </button>
            <button
              type="button"
              @click="closeTimecodeModal"
              class="flex-1 bg-gray-600 hover:bg-gray-700 text-white border-none rounded-lg py-3 px-5 cursor-pointer text-base font-medium transition-colors duration-300"
            >
              Annuler
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal de gestion des personnages -->
    <CharacterModal
      v-if="showCharacterModal && project"
      :projectId="project.id"
      :editingCharacter="editingCharacter"
      @close="closeCharacterModal"
      @saved="onCharacterSaved"
    />
  </div>

</template>

<script setup lang="ts">
// Contrôle du scroll instantané pour la bande rythmo

const instantRythmoScroll = ref(true) // true = instantané, false = smooth
import BackSvg from '../assets/icons/back.svg'
import ArrowSvg from '../assets/icons/arrow.svg'
function goBack() {
  router.push({ name: 'projects' })
}
// Gestion du repli horizontal de la partie timecodes (fermé par défaut)
const isTimecodesCollapsed = ref(true)
function toggleTimecodesPanel() {
  isTimecodesCollapsed.value = !isTimecodesCollapsed.value
}
import { useRouter, useRoute } from 'vue-router'
import { ref, onMounted, reactive, computed } from 'vue'
import api from '../api/axios'
import { timecodeApi, type Timecode as ApiTimecode } from '../api/timecodes'
import { characterApi, type Character } from '../api/characters'
import TimecodesListMultiLine from '../components/projectDetail/TimecodesListMultiLine.vue'
import SceneChangesList from '../components/projectDetail/SceneChangesList.vue'
import CharactersList from '../components/projectDetail/CharactersList.vue'
import CharacterModal from '../components/projectDetail/CharacterModal.vue'
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
  // TODO: Implémenter l'édition de scene change si nécessaire
  console.log('Éditer scene change', idx)
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
import VideoPlayer from '../components/projectDetail/VideoPlayer.vue'
import MultiRythmoBand from '../components/projectDetail/MultiRythmoBand.vue'

import RythmoControls from '../components/projectDetail/RythmoControls.vue'

const route = useRoute()
const router = useRouter()

// Liste des changements de plan (objets {id, timecode})
interface SceneChange { id: number; timecode: number }
const sceneChanges = ref<SceneChange[]>([])

function goToFinalPreview() {
  if (!project.value || !project.value.video_path || compatibleTimecodes.value.length === 0) return
  router.push({
    name: 'final-preview',
    query: {
      video: getVideoUrl(project.value.video_path),
      rythmo: JSON.stringify(compatibleTimecodes.value),
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

// Clé pour forcer la reconstruction complète du composant MultiRythmoBand
const rythmoReloadKey = ref(0)

// Timecodes multi-lignes (nouvelle API)
const allTimecodes = ref<ApiTimecode[]>([])

// Gestion des personnages
const allCharacters = ref<Character[]>([])
const activeCharacter = ref<Character | null>(null)
const showCharacterModal = ref(false)
const editingCharacter = ref<Character | null>(null)

// Conversion temporaire des anciens timecodes en format compatible
const compatibleTimecodes = computed(() => {
  // Utilise d'abord les nouveaux timecodes de l'API
  if (allTimecodes.value.length > 0) {
    return allTimecodes.value
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
  return oldTimecodes.map((tc, index) => ({
    id: index + 1000, // ID temporaire
    project_id: project.value!.id,
    line_number: 1, // Tous sur la ligne 1 par défaut
    start: tc.start,
    end: tc.end,
    text: tc.text
  }))
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
  const idx = compatibleTimecodes.value.findIndex(tc =>
    tc.id === timecode.id ||
    (tc.start === timecode.start && tc.end === timecode.end && tc.text === timecode.text)
  )
  if (idx >= 0) {
    onDeleteTimecode(idx)
  }
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

function onAddTimecodeToLine(lineNumber: number) {
  // TODO: Ouvrir modal pour ajouter timecode sur cette ligne
  console.log('Ajouter timecode sur ligne', lineNumber)
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
function onAddTimecode() {
  editTimecodeIdx.value = null
  Object.assign(modalTimecode, {
    start: currentTime.value,
    end: currentTime.value + 2,
    text: '',
    line_number: 1,
    character_id: activeCharacter.value?.id || null
  })
  showTimecodeModal.value = true
}
async function onDeleteTimecode(idx: number) {
  if (!project.value) return

  const timecodeToDelete = compatibleTimecodes.value[idx]
  if (!timecodeToDelete?.id) return

  try {
    await timecodeApi.delete(project.value.id, timecodeToDelete.id)
    // Recharger les timecodes
    await loadTimecodes()
  } catch (error) {
    console.error('Erreur lors de la suppression du timecode:', error)
  }
}
function closeTimecodeModal() {
  showTimecodeModal.value = false
}
async function saveTimecode() {
  if (!project.value) return

  try {
    if (editTimecodeIdx.value !== null) {
      // Mode édition - trouver le timecode à partir de l'index dans compatibleTimecodes
      const timecodeToEdit = compatibleTimecodes.value[editTimecodeIdx.value]
      if (timecodeToEdit?.id) {
        // Mettre à jour via l'API
        await timecodeApi.update(project.value.id, timecodeToEdit.id, {
          start: modalTimecode.start,
          end: modalTimecode.end,
          text: modalTimecode.text,
          line_number: modalTimecode.line_number || 1
        })
      }
    } else {
      // Mode création - créer via l'API
      await timecodeApi.create(project.value.id, {
        start: modalTimecode.start,
        end: modalTimecode.end,
        text: modalTimecode.text,
        line_number: modalTimecode.line_number || 1
      })
    }

    // Recharger les timecodes
    await loadTimecodes()
    showTimecodeModal.value = false
  } catch (error) {
    console.error('Erreur lors de la sauvegarde du timecode:', error)
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
    // Récupère les changements de plan
    const scRes = await api.get(`/projects/${data.id}/scene-changes`)
    sceneChanges.value = Array.isArray(scRes.data) ? scRes.data : []

    // Charge les timecodes multi-lignes
    await loadTimecodes()

    // Charge les personnages
    await loadCharacters()
  } catch {
    project.value = null
    sceneChanges.value = []
  } finally {
    loading.value = false
  }

  // Gestion des raccourcis clavier
  window.addEventListener('keydown', handleKeydown)
})

function handleKeydown(e: KeyboardEvent) {
  // Ignore si focus dans un champ de saisie (input, textarea, ou contenteditable)
  const target = e.target as HTMLElement | null
  if (!target) return
  const tag = target.tagName.toLowerCase()
  const isEditable = tag === 'input' || tag === 'textarea' || target.isContentEditable
  if (isEditable) return

  // Espace = play/pause
  if (e.code === 'Space') {
    e.preventDefault()
    seek(0)
  }
  // Flèche gauche = -1s
  else if (e.code === 'ArrowLeft') {
    e.preventDefault()
    seek(-1)
  }
  // Flèche droite = +1s
  else if (e.code === 'ArrowRight') {
    e.preventDefault()
    seek(1)
  }
  // A = -1 frame
  else if (e.key === 'a' || e.key === 'A') {
    e.preventDefault()
    seekFrame(-1)
  }
  // E = +1 frame
  else if (e.key === 'e' || e.key === 'E') {
    e.preventDefault()
    seekFrame(1)
  }
}


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
</script>
<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.2s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>
