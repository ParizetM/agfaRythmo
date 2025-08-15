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
        v-if="project && project.video_path && project.timecodes && project.timecodes.length"
        class="bg-agfa-blue hover:bg-agfa-blue-hover text-white border-none rounded-lg px-5 py-2 text-base font-bold cursor-pointer shadow-lg transition-colors duration-300"
        @click="goToFinalPreview"
        title="Aperçu final plein écran"
      >
        Aperçu final
      </button>
    </header>

    <!-- Main Grid -->
    <div class="w-full flex flex-row gap-6 items-start justify-center lg:flex-col lg:gap-2">
      <!-- Left Panel - Timecodes -->
      <div :class="['relative transition-all duration-300', isTimecodesCollapsed ? 'min-w-0 max-w-9 w-9 flex-none overflow-visible flex items-center justify-end p-0' : 'min-w-56 max-w-96 flex-none w-80']">
        <button
          class="absolute top-3 -right-4 z-30 bg-agfa-dark text-white border border-gray-600 rounded-r-lg w-7 h-9 flex items-center justify-center cursor-pointer shadow-lg text-lg p-0 hover:bg-agfa-blue transition-colors duration-300 lg:static lg:mx-auto lg:right-auto lg:left-auto lg:top-auto lg:transform-none"
          :class="{ 'static mx-auto right-auto left-auto top-auto transform-none': isTimecodesCollapsed }"
          @click="toggleTimecodesPanel"
          :title="isTimecodesCollapsed ? 'Déplier' : 'Replier'"
        >
          <ArrowSvg
            :class="isTimecodesCollapsed ? 'w-4 h-4' : 'w-4 h-4 rotate-180'"
          />
        </button>

        <div v-show="!isTimecodesCollapsed" class="w-full h-full">
          <TimecodesList
            v-if="project"
            :timecodes="project.timecodes || []"
            :selected="selectedTimecodeIdx ?? undefined"
            @select="onSelectTimecode"
            @edit="onEditTimecode"
            @delete="onDeleteTimecode"
            @add="onAddTimecode"
          />
        </div>
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

        <RythmoBand
          v-if="project && project.timecodes"
          :timecodes="project.timecodes"
          :currentTime="currentTime"
          :videoDuration="videoDuration"
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
  </div>
</template>

<script setup lang="ts">
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
import { ref, onMounted, reactive } from 'vue'
import api from '../api/axios'
import TimecodesList from '../components/projectDetail/TimecodesList.vue'
import VideoPlayer from '../components/projectDetail/VideoPlayer.vue'
import RythmoBand from '../components/projectDetail/RythmoBand.vue'

import RythmoControls from '../components/projectDetail/RythmoControls.vue'

const route = useRoute()
const router = useRouter()

function goToFinalPreview() {
  if (!project.value || !project.value.video_path || !project.value.timecodes) return
  router.push({
    name: 'final-preview',
    query: {
      video: getVideoUrl(project.value.video_path),
      rythmo: JSON.stringify(project.value.timecodes),
    },
  })
}
interface Timecode {
  start: number
  end: number
  text: string
}
interface Project {
  id: number
  name: string
  description?: string
  video_path?: string
  timecodes?: Timecode[]
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

// Modal d'édition/ajout de timecode
const showTimecodeModal = ref(false)
const editTimecodeIdx = ref<number | null>(null)
const modalTimecode = reactive<Timecode>({ start: 0, end: 0, text: '' })

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
  if (project.value && project.value.timecodes) {
    const idx = project.value.timecodes.findIndex((tc) => time >= tc.start && time < tc.end)
    selectedTimecodeIdx.value = idx >= 0 ? idx : null
  }
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
      } else {
        videoEl.pause()
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
}
function onSelectTimecode(idx: number) {
  selectedTimecodeIdx.value = idx
  // Seek vidéo si possible
  const tc = project.value?.timecodes?.[idx]
  if (tc) {
    lastSeekFromTimecode = true
    currentTime.value = tc.start
  }
}
function onEditTimecode(idx: number) {
  editTimecodeIdx.value = idx
  const tc = project.value?.timecodes?.[idx]
  if (tc) Object.assign(modalTimecode, tc)
  showTimecodeModal.value = true
}
function onAddTimecode() {
  editTimecodeIdx.value = null
  Object.assign(modalTimecode, { start: currentTime.value, end: currentTime.value + 2, text: '' })
  showTimecodeModal.value = true
}
function onDeleteTimecode(idx: number) {
  if (!project.value?.timecodes) return
  project.value.timecodes.splice(idx, 1)
  saveTimecodes()
}
function closeTimecodeModal() {
  showTimecodeModal.value = false
}
function saveTimecode() {
  if (!project.value) return
  if (!project.value.timecodes) project.value.timecodes = []
  if (editTimecodeIdx.value !== null) {
    // édition
    project.value.timecodes[editTimecodeIdx.value] = { ...modalTimecode }
  } else {
    // ajout
    project.value.timecodes.push({ ...modalTimecode })
  }
  saveTimecodes()
  showTimecodeModal.value = false
}
async function saveTimecodes() {
  if (!project.value) return
  try {
    await api.put(`/projects/${project.value.id}`, { timecodes: project.value.timecodes })
  } catch {
    // TODO: gestion d'erreur
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
  } catch {
    project.value = null
  } finally {
    loading.value = false
  }

  // Gestion des raccourcis clavier
  window.addEventListener('keydown', handleKeydown)
})

function handleKeydown(e: KeyboardEvent) {
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
</script>
