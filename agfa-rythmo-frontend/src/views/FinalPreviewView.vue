<template>
  <div class="final-preview" @click="exitPreview">
    <div v-if="!started" class="start-overlay">
      <button class="start-btn" @click.stop="startPreview">Appuyer pour démarrer</button>
    </div>
    <div v-else-if="countdown > 0" class="countdown">
      <span>{{ countdown }}</span>
    </div>
    <div v-else class="preview-content">
      <div class="video-wrapper" :class="{ 'with-band-below': projectSettings.overlayPosition === 'under' }">
        <div class="video-container">
          <video
            ref="video"
            :src="videoSrc"
            class="preview-video"
            @loadedmetadata="onLoadedMetadata"
            @click="playError && video && video.play()"
            tabindex="0"
          />
          <!-- Bande rythmo par-dessus la vidéo (ancrée en bas) -->
          <div
            v-if="videoWidth && videoHeight && projectSettings.overlayPosition === 'over'"
            class="preview-rythmo overlay-mode"
            :style="{
              width: rythmoBarWidth + 'px',
              left: '50%',
              transform: 'translateX(-50%)',
            }"
          >
            <MultiRythmoBand
              :timecodes="rythmoData"
              :currentTime="currentTime"
              :videoDuration="videoDuration"
              :rythmoLinesCount="getRythmoLinesCount"
              :hideConfig="true"
              :disableSelection="true"
            />
          </div>
        </div>

        <!-- Bande rythmo sous la vidéo (ne chevauche pas) -->
        <div
          v-if="videoWidth && videoHeight && projectSettings.overlayPosition === 'under'"
          class="preview-rythmo below-mode"
          :style="{
            height: rythmoBarHeight + 'px'
          }"
        >
          <MultiRythmoBand
            :timecodes="rythmoData"
            :currentTime="currentTime"
            :videoDuration="videoDuration"
            :rythmoLinesCount="getRythmoLinesCount"
            :hideConfig="true"
            :disableSelection="true"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onUnmounted, watch, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import MultiRythmoBand from '../components/projectDetail/MultiRythmoBand.vue'
import { useProjectSettingsStore } from '../stores/projectSettings'

const router = useRouter()
const route = useRoute()
const settingsStore = useProjectSettingsStore()
const projectSettings = computed(() => settingsStore.settings)

const rythmoData = route.query.rythmo ? JSON.parse(route.query.rythmo as string) : []
// Récupère le nombre de lignes depuis les paramètres de la route (configuré dans le projet)
const getRythmoLinesCount = route.query.rythmoLinesCount
  ? parseInt(route.query.rythmoLinesCount as string, 10)
  : 1

const video = ref<HTMLVideoElement | null>(null)
const videoSrc = (route.query.video as string) || ''
const started = ref(false)
const countdown = ref(3)
const currentTime = ref(0)
const videoWidth = ref<number>(0)
const videoHeight = ref<number>(0)
const videoDuration = ref<number>(0)
const playError = ref('')
const videoReady = ref(false)
const rythmoBarWidth = ref<number>(0)
const rythmoBarHeight = ref<number>(0)
let interval: number
let rafId: number

function onLoadedMetadata() {
  if (video.value) {
    videoWidth.value = video.value.videoWidth
    videoHeight.value = video.value.videoHeight
    videoDuration.value = video.value.duration
    videoReady.value = true
    console.log(
      '[onLoadedMetadata] video readyState:',
      video.value.readyState,
      'duration:',
      video.value.duration,
    )
  }
}

watch([started, videoReady, countdown], async ([isStarted, isReady, count]) => {
  if (isStarted && isReady && count === 0) {
    if (video.value) {
      video.value.currentTime = 0
      video.value.muted = false
      try {
        video.value.focus()
        console.log('[watch] Tentative de play()')
        await video.value.play()
        playError.value = ''
        console.log('[watch] play() OK')
      } catch {
        playError.value =
          'Impossible de démarrer la vidéo automatiquement. Cliquez sur la vidéo pour lancer la lecture.'
        console.log('[watch] play() ECHEC')
      }
      updateTime()
    }
  }
})

function exitPreview() {
  console.log('[exitPreview] retour arrière')
  router.back()
}

function updateTime() {
  if (video.value) {
    currentTime.value = video.value.currentTime
    rafId = requestAnimationFrame(updateTime)
    // Log toutes les 10 frames
    if (Math.floor(currentTime.value * 10) % 10 === 0) {
      console.log('[updateTime] currentTime:', currentTime.value)
    }
  }
}

function updateRythmoBarWidth() {
  if (video.value) {
    const rect = video.value.getBoundingClientRect()
    rythmoBarWidth.value = rect.width
    console.log('[updateRythmoBarWidth] largeur vidéo affichée:', rect.width)
  }
}

function updateRythmoBarHeight() {
  // Calculer la hauteur de la bande rythmo depuis les settings du projet
  const heightPerLine = projectSettings.value.bandHeight || 80
  rythmoBarHeight.value = heightPerLine * getRythmoLinesCount
  console.log('[updateRythmoBarHeight] hauteur bande rythmo:', rythmoBarHeight.value, 'px (', heightPerLine, 'px × ', getRythmoLinesCount, 'lignes)')
}

watch([videoWidth, videoHeight, videoReady, started, countdown], () => {
  setTimeout(() => {
    updateRythmoBarWidth()
    updateRythmoBarHeight()
  }, 50)
})

onMounted(() => {
  window.addEventListener('resize', updateRythmoBarWidth)
})

onUnmounted(() => {
  clearInterval(interval)
  cancelAnimationFrame(rafId)
  window.removeEventListener('resize', updateRythmoBarWidth)
  console.log('[onUnmounted] Clean up')
})



async function startPreview() {
  started.value = true
  let c = 3
  countdown.value = c
  console.log('[startPreview] Lancement du compte à rebours')
  interval = window.setInterval(() => {
    c--
    countdown.value = c
    console.log('[startPreview] Countdown:', c)
    if (c === 0) {
      clearInterval(interval)
      // Le play sera déclenché par le watcher
    }
  }, 1000)
}

onUnmounted(() => {
  clearInterval(interval)
  cancelAnimationFrame(rafId)
  console.log('[onUnmounted] Clean up')
})
</script>

<style scoped>
.final-preview {
  position: fixed;
  inset: 0;
  background: #000;
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}
.start-overlay {
  position: absolute;
  inset: 0;
  background: #000e;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10;
}
.start-btn {
  font-size: 2.5rem;
  padding: 2rem 4rem;
  border-radius: 2rem;
  background: #3182ce;
  color: #fff;
  border: none;
  font-weight: bold;
  cursor: pointer;
  box-shadow: 0 4px 24px #0006;
  transition: background 0.2s;
}
.start-btn:hover {
  background: #225ea8;
}
.countdown {
  font-size: 8vw;
  color: #fff;
  font-weight: bold;
  user-select: none;
}
.preview-content {
  width: 100vw;
  height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  position: relative;
  overflow: hidden;
}

.video-wrapper {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
}

/* Mode avec bande sous la vidéo : ajuster le conteneur */
.video-wrapper.with-band-below {
  justify-content: flex-start;
}

.video-container {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  flex-shrink: 0;
}

/* Mode overlay : la vidéo prend tout l'espace */
.video-wrapper:not(.with-band-below) .video-container {
  height: 100vh;
}

/* Mode below : la vidéo laisse de la place pour la bande */
.video-wrapper.with-band-below .video-container {
  max-height: calc(100vh - v-bind(rythmoBarHeight + 'px'));
}

.preview-video {
  max-width: 100vw;
  max-height: 100%;
  width: auto;
  height: auto;
  object-fit: contain;
  background: #000;
  display: block;
}

/* Bande rythmo en mode overlay (par-dessus, ancrée en bas de la vidéo) */
.preview-rythmo.overlay-mode {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  pointer-events: none;
  z-index: 10;
  opacity: 0.95;
  backdrop-filter: blur(2px);
}

/* Bande rythmo en mode below (sous la vidéo, collée en haut de la bande) */
.preview-rythmo.below-mode {
  position: relative;
  width: 100%;
  margin-top: 0;
  pointer-events: none;
  z-index: 2;
}
.play-error {
  position: absolute;
  top: 2rem;
  left: 50%;
  transform: translateX(-50%);
  color: #fff;
  background: #c00d;
  padding: 1rem 2rem;
  border-radius: 1rem;
  font-size: 1.2rem;
  z-index: 20;
  box-shadow: 0 2px 12px #0008;
}
</style>
