<template>
  <div class="project-edit-container">
    <header class="header-panel">
      <button class="back-btn" @click="goBack" title="Retour aux projets">
        <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M14 18L8 11L14 4" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
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
        <button class="collapse-btn" @click="toggleTimecodesPanel" :title="isTimecodesCollapsed ? 'Déplier' : 'Replier'">
          <span v-if="isTimecodesCollapsed">
            <!-- Flèche droite SVG pour déplier -->
            <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M7 5L11 9L7 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </span>
          <span v-else>
            <!-- Flèche gauche SVG pour replier -->
            <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M11 5L7 9L11 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
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
            <svg v-if="isVideoPaused" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
              <polygon points="6,4 18,11 6,18" fill="#fff"/>
            </svg>
            <svg v-else width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect x="6" y="4" width="3.5" height="14" fill="#fff"/>
              <rect x="12.5" y="4" width="3.5" height="14" fill="#fff"/>
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
import TimecodesList from '../components/TimecodesList.vue'
import VideoPlayer from '../components/VideoPlayer.vue'
import RythmoBand from '../components/RythmoBand.vue'

const route = useRoute()
const router = useRouter()

function goToFinalPreview() {
  if (!project.value || !project.value.video_path || !project.value.timecodes) return;
  router.push({
    name: 'final-preview',
    query: {
      video: getVideoUrl(project.value.video_path),
      rythmo: JSON.stringify(project.value.timecodes),
    },
  });
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
  // Essaye de récupérer les fps de la vidéo si possible
  const videoEl = document.querySelector('video')
  if (videoEl) {
    // Certains navigateurs exposent videoEl.getVideoPlaybackQuality() ou videoEl.webkitDecodedFrameCount
    // Mais il n'y a pas d'API standard pour les fps, donc on laisse à 25 par défaut
    // Si le backend peut fournir les fps, il faudrait les passer dans project
  }
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

<style scoped>
.rythmo-controls svg {
  vertical-align: middle;
  width: 1.5em;
  height: 1.5em;
}
</style>

<style scoped>
.rythmo-controls {
  display: flex;
  justify-content: center;
  gap: 1.2em;
  margin: 1.2em 0 0.5em 0;
}
.rythmo-controls button {
  background: #232733;
  color: #fff;
  border: 1px solid #444;
  border-radius: 6px;
  padding: 0.4em 1.1em;
  font-size: 1.1em;
  cursor: pointer;
  transition: background 0.18s;
}
.rythmo-controls button:hover {
  background: #3182ce;
}
</style>

<style scoped>
/* Nouvelle disposition modulaire */
.project-edit-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  background: #181c24;
  min-height: 100vh;
  padding: 0;
  margin: 0;
}
.header-panel {
  width: 100%;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: #232733;
  box-shadow: 0 2px 12px #0003;
  margin-bottom: 1.5rem;
  padding: 1.2rem 0;
  border-radius: 0;
  min-width: 0;
}
.back-btn {
  display: flex;
  align-items: center;
  gap: 0.5em;
  background: none;
  border: none;
  color: #fff;
  font-size: 1.1em;
  font-weight: 500;
  cursor: pointer;
  padding: 0.3em 0.8em 0.3em 0.2em;
  border-radius: 6px;
  transition: background 0.18s;
  margin-right: 1.2em;
}
.back-btn:hover {
  background: #181c24;
}
.header-panel .project-infos {
  flex: 1 1 auto;
  color: #fff;
  text-align: left;
  margin: 0 1.5em;
  min-width: 0;
}
.header-panel h1 {
  font-size: 2em;
  font-weight: 700;
  margin: 0 0 0.2em 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.header-panel .desc {
  font-size: 1.08em;
  color: #bfc7d5;
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.final-preview-btn {
  position: static;
  margin-left: auto;
  background: #3182ce;
  color: #fff;
  border: none;
  border-radius: 4px;
  padding: 0.5rem 1.2rem;
  font-size: 1em;
  font-weight: bold;
  cursor: pointer;
  z-index: 10;
  box-shadow: 0 2px 8px #0002;
  transition: background 0.2s;
}
.final-preview-btn:hover {
  background: #225ea8;
}
.project-infos {
  color: #fff;
  text-align: left;
}
.main-grid {
  width: 100%;
  display: flex;
  flex-direction: row;
  gap: 1.5rem;
  align-items: flex-start;
  justify-content: center;
}
.left-panel {
  min-width: 220px;
  max-width: 400px;
  flex: 0 0 350px;
  position: relative;
  transition: min-width 0.2s, max-width 0.2s, width 0.2s;
}
.left-panel.collapsed {
  min-width: 0;
  max-width: 36px;
  width: 36px;
  flex: 0 0 36px;
  overflow: visible;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  padding: 0;
}
.left-panel .collapse-btn {
  position: absolute;
  top: 10px;
  right: -16px;
  z-index: 30;
  background: #232733;
  color: #fff;
  border: 1px solid #444;
  border-radius: 0 6px 6px 0;
  width: 28px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  box-shadow: 0 2px 8px #0002;
  font-size: 1.2em;
  padding: 0;
  transition: background 0.2s;
}
.left-panel.collapsed .collapse-btn {
  position: static;
  margin: 0 auto;
  right: auto;
  left: auto;
  top: auto;
  transform: none;
  display: flex;
  align-items: center;
  justify-content: center;
}
.left-panel .collapse-btn:hover {
  background: #3182ce;
}
.left-panel .timecodes-content {
  width: 100%;
  height: 100%;
}
.collapse-btn {
  position: absolute;
  top: 10px;
  right: -16px;
  z-index: 20;
  background: #232733;
  color: #fff;
  border: 1px solid #444;
  border-radius: 0 6px 6px 0;
  width: 28px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  box-shadow: 0 2px 8px #0002;
  font-size: 1.2em;
  padding: 0;
  transition: background 0.2s;
}
.collapse-btn:hover {
  background: #3182ce;
}
.center-panel {
  flex: 1 1 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  background: #232733;
  border-radius: 8px;
  padding: 1rem 0.5rem 0.5rem 0.5rem;
  box-shadow: 0 1px 6px #0002;
  min-width: 0;
  margin-right: 1rem;
}
.no-video {
  width: 100%;
  max-width: 720px;
  height: 405px;
  background: #222;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
}
/* Modal styles */
.modal-bg {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: #0008;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}
.modal {
  background: #232733;
  color: #fff;
  border-radius: 8px;
  padding: 2rem 2rem 1rem 2rem;
  min-width: 320px;
  box-shadow: 0 2px 12px #0005;
}
.modal label {
  display: block;
  margin-bottom: 0.7rem;
}
.modal input {
  width: 100%;
  padding: 0.3rem;
  border-radius: 4px;
  border: 1px solid #444;
  background: #181c24;
  color: #fff;
}
.modal-actions {
  display: flex;
  gap: 1rem;
  margin-top: 1rem;
}
.modal button {
  background: #3182ce;
  color: #fff;
  border: none;
  border-radius: 4px;
  padding: 0.4rem 1.2rem;
  cursor: pointer;
  font-size: 1em;
}
.modal button[type='button'] {
  background: #444;
}
@media (max-width: 1000px) {
  .main-grid {
    flex-direction: column;
    gap: 0.5rem;
    max-width: 100vw;
  }
  .header-panel {
    max-width: 100vw;
    padding-left: 0.2rem;
    padding-right: 0.2rem;
  }
  .center-panel {
    max-width: 100vw;
    padding-left: 0.2rem;
    padding-right: 0.2rem;
  }
}
@media (max-width: 600px) {
  .project-edit-container {
    padding: 0;
  }
  .header-panel,
  .center-panel {
    padding: 0.2rem 0.1rem;
  }
}

/* Bouton Aperçu final */
.final-preview-btn {
  position: absolute;
  top: 1.2rem;
  right: 2rem;
  background: #3182ce;
  color: #fff;
  border: none;
  border-radius: 4px;
  padding: 0.5rem 1.2rem;
  font-size: 1em;
  font-weight: bold;
  cursor: pointer;
  z-index: 10;
  box-shadow: 0 2px 8px #0002;
  transition: background 0.2s;
}
.final-preview-btn:hover {
  background: #225ea8;
}
</style>

