<template>
  <div class="final-preview" @click="exitPreview">
    <div v-if="!started" class="start-overlay">
      <button class="start-btn" @click.stop="startPreview">Appuyer pour démarrer</button>
    </div>
    <div v-else-if="countdown > 0" class="countdown">
      <span>{{ countdown }}</span>
    </div>
    <div v-else class="preview-content">
      <div class="video-wrapper">
        <video
          ref="video"
          :src="videoSrc"
          class="preview-video"
          @loadedmetadata="onLoadedMetadata"
          @click="playError && video.value && video.value.play()"
          tabindex="0"
        />
        <div
          v-if="videoWidth && videoHeight"
          class="preview-rythmo"
          :style="{
            width: rythmoBarWidth + 'px',
            left: '50%',
            transform: 'translateX(-50%)',
            bottom: '0',
          }"
        >
          <MultiRythmoBand
            :timecodes="rythmoData"
            :currentTime="currentTime"
            :videoDuration="videoDuration"
            :rythmoLinesCount="getRythmoLinesCount"
            :hideConfig="true"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onUnmounted, watch, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import MultiRythmoBand from '../components/projectDetail/MultiRythmoBand.vue'

const router = useRouter()
const route = useRoute()

const rythmoData = route.query.rythmo ? JSON.parse(route.query.rythmo as string) : []
// Détermine le nombre de lignes rythmo à partir des données
const getRythmoLinesCount = Array.isArray(rythmoData)
  ? Math.max(1, ...rythmoData.map(tc => tc.line_number || 1))
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

watch([videoWidth, videoHeight, videoReady, started, countdown], () => {
  setTimeout(updateRythmoBarWidth, 50)
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

async function waitForVideoReady() {
  return new Promise<void>((resolve) => {
    if (!video.value) {
      console.log('[waitForVideoReady] Pas de ref vidéo')
      return resolve()
    }
    if (video.value.readyState >= 2) {
      console.log('[waitForVideoReady] Vidéo déjà prête')
      return resolve()
    }
    const onCanPlay = () => {
      video.value?.removeEventListener('canplay', onCanPlay)
      console.log('[waitForVideoReady] canplay event')
      resolve()
    }
    video.value.addEventListener('canplay', onCanPlay)
    console.log('[waitForVideoReady] En attente de canplay...')
  })
}

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
}
.video-wrapper {
  position: relative;
  width: 100vw;
  height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
}
.preview-video {
  width: 100vw;
  height: 100vh;
  object-fit: contain;
  background: #000;
  display: block;
  margin: 0 auto;
}
.preview-rythmo {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
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
