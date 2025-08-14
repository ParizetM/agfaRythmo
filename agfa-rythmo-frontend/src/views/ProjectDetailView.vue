<template>
  <div class="project-edit-container">
    <div class="header-panel">
      <div class="project-infos">
        <h2>{{ project?.name }}</h2>
        <p>{{ project?.description }}</p>
      </div>
    </div>
    <div class="main-grid">
      <div class="left-panel">
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
import { ref, onMounted, reactive } from 'vue'
import { useRoute } from 'vue-router'
import api from '../api/axios'
import TimecodesList from '../components/TimecodesList.vue'
import VideoPlayer from '../components/VideoPlayer.vue'
import RythmoBand from '../components/RythmoBand.vue'

const route = useRoute()
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
const videoDuration = ref(0)
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
function onSelectTimecode(idx: number) {
  selectedTimecodeIdx.value = idx
  // Seek vidéo si possible
  const tc = project.value?.timecodes?.[idx]
  if (tc) currentTime.value = tc.start
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
    project.value = data
  } catch {
    project.value = null
  } finally {
    loading.value = false
  }
})
</script>

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
  max-width: 1100px;
  display: flex;
  justify-content: flex-start;
  margin-bottom: 1rem;
  padding: 0.5rem 0.5rem 0 0.5rem;
}
.project-infos {
  color: #fff;
  text-align: left;
}
.main-grid {
  width: 100%;
  max-width: 1100px;
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
</style>
