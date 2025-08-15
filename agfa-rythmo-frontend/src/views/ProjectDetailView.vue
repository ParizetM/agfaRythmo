<template>
  <div class="project-edit-container">
    <header class="header-panel">
      <button class="back-btn" @click="goBack" title="Retour aux projets">
        <BackSvg/>

        <span>Projets</span>
      </button>
      <div class="project-infos">
        <h1>{{ project?.name }}</h1>
        <p class="desc">{{ project?.description }}</p>
      </div>
      <button
        v-if="project && project.video_path && project.timecodes && project.timecodes.length"
        class="final-preview-btn"
        @click="goToFinalPreview"
        title="Aperçu final plein écran"
      >
        Aperçu final
      </button>
    </header>
    <div class="main-grid">
      <div :class="['left-panel', { collapsed: isTimecodesCollapsed }]">
        <button
          class="collapse-btn"
          @click="toggleTimecodesPanel"
          :title="isTimecodesCollapsed ? 'Déplier' : 'Replier'"
        >
          <span v-if="isTimecodesCollapsed">
            <!-- Flèche droite SVG pour déplier -->
            <ArrowSvg />
          </span>
          <span v-else>
            <!-- Flèche gauche SVG pour replier -->
            <ArrowSvg style="transform: rotate(180deg);" />
          </span>
        </button>
        <div v-show="!isTimecodesCollapsed" class="timecodes-content">
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
      <div class="center-panel">
        <VideoPlayer
          v-if="project && project.video_path"
          :src="getVideoUrl(project.video_path)"
          :currentTime="currentTime"
          @timeupdate="onVideoTimeUpdate"
          @loadedmetadata="onLoadedMetadata"
        />
        <div v-else class="no-video">Aucune vidéo</div>
        <RythmoBand
          v-if="project && project.timecodes"
          :timecodes="project.timecodes"
          :currentTime="currentTime"
          :videoDuration="videoDuration"
        />
        <div class="rythmo-controls">
          <button @click="seek(-1)" title="← 1s (Flèche gauche)">-1s</button>
          <button @click="seekFrame(-1)" title="← 1 frame (A)">-1 frame</button>
          <button @click="seek(0)" title="Lecture/Pause (Espace)">
            <svg
              v-if="isVideoPaused"
              width="22"
              height="22"
              viewBox="0 0 22 22"
              fill="none"
              xmlns="http://www.w3.org/2000/svg"
            >
              <polygon points="6,4 18,11 6,18" fill="#fff" />
            </svg>
            <svg
              v-else
              width="22"
              height="22"
              viewBox="0 0 22 22"
              fill="none"
              xmlns="http://www.w3.org/2000/svg"
            >
              <rect x="6" y="4" width="3.5" height="14" fill="#fff" />
              <rect x="12.5" y="4" width="3.5" height="14" fill="#fff" />
            </svg>
          </button>
          <button @click="seekFrame(1)" title="→ 1 frame (E)">+1 frame</button>
          <button @click="seek(1)" title="→ 1s (Flèche droite)">+1s</button>
        </div>
      </div>
    </div>
    <!-- Modal d'édition/ajout de timecode (simple) -->
    <div v-if="showTimecodeModal" class="modal-bg">
      <div class="modal">
        <h4>{{ editTimecodeIdx !== null ? 'Éditer' : 'Ajouter' }} un timecode</h4>
        <form @submit.prevent="saveTimecode">
          <label
            >Début (s):
            <input v-model.number="modalTimecode.start" type="number" step="0.01" min="0" required
          /></label>
          <label
            >Fin (s):
            <input v-model.number="modalTimecode.end" type="number" step="0.01" min="0" required
          /></label>
          <label>Texte: <input v-model="modalTimecode.text" type="text" required /></label>
          <div class="modal-actions">
            <button type="submit">Valider</button>
            <button type="button" @click="closeTimecodeModal">Annuler</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import '../assets/styles/ProjectDetailView.css'
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
import { useRouter } from 'vue-router'
import { ref, onMounted, reactive } from 'vue'
import { useRoute } from 'vue-router'
import api from '../api/axios'
import TimecodesList from '../components/projectDetail/TimecodesList.vue'
import VideoPlayer from '../components/projectDetail/VideoPlayer.vue'
import RythmoBand from '../components/projectDetail/RythmoBand.vue'

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
