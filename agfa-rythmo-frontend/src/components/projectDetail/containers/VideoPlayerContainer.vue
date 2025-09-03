<template>
  <div class="video-player-container bg-agfa-bg rounded-lg overflow-hidden border border-gray-600">
    <!-- Header avec contrôles -->
    <div class="flex items-center justify-between p-4 bg-agfa-menu border-b border-gray-600">
      <h3 class="text-lg font-semibold text-white">Lecteur vidéo</h3>
      <div class="flex items-center space-x-2">
        <!-- Indicateur de synchronisation -->
        <div
          v-if="isSyncing"
          class="flex items-center text-yellow-400 text-sm"
        >
          <svg class="w-4 h-4 mr-1 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          Synchronisation...
        </div>
        <div
          v-else
          class="flex items-center text-green-400 text-sm"
        >
          <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          Synchronisé
        </div>
      </div>
    </div>

    <!-- Lecteur vidéo -->
    <div class="relative">
      <video
        ref="videoElement"
        class="w-full h-auto bg-black"
        :src="videoUrl"
        @loadedmetadata="handleVideoLoaded"
        @timeupdate="handleTimeUpdate"
        @play="handlePlay"
        @pause="handlePause"
        @seeked="handleSeeked"
        @loadstart="isSyncing = true"
        @canplay="isSyncing = false"
        @error="handleVideoError"
      />

      <!-- Overlay de texte -->
      <div
        v-if="showTextOverlay && currentTimecode"
        class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-75 p-4 text-center"
      >
        <div
          class="text-white text-lg font-medium"
          :style="{ color: currentTimecode.character?.color || '#ffffff' }"
        >
          {{ currentTimecode.text }}
        </div>
        <div class="text-gray-300 text-sm mt-1">
          {{ currentTimecode.character?.name || 'Narrateur' }} - L{{ currentTimecode.line_number }}
        </div>
      </div>

      <!-- Indicateur de changement de plan -->
      <div
        v-if="showSceneChangeIndicator"
        class="absolute top-4 left-4 bg-agfa-purple text-white px-3 py-1 rounded-full text-sm font-medium"
      >
        Plan {{ currentSceneNumber }}
      </div>
    </div>

    <!-- Contrôles de lecture -->
    <div class="p-4 bg-agfa-menu border-t border-gray-600">
      <div class="flex items-center justify-between mb-3">
        <!-- Contrôles principaux -->
        <div class="flex items-center space-x-2">
          <button
            @click="togglePlayPause"
            class="play-pause-btn"
          >
            <svg v-if="isPlaying" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h1m4 0h1m2-7H3a2 2 0 00-2 2v9a2 2 0 002 2h18a2 2 0 002-2V9a2 2 0 00-2-2h-4" />
            </svg>
          </button>

          <button
            @click="skipBackward"
            class="control-btn"
            title="Reculer de 10s"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0019 16V8a1 1 0 00-1.6-.8l-5.333 4zM4.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0011 16V8a1 1 0 00-1.6-.8l-5.334 4z" />
            </svg>
          </button>

          <button
            @click="skipForward"
            class="control-btn"
            title="Avancer de 10s"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.933 12.8a1 1 0 000-1.6L6.6 7.2A1 1 0 005 8v8a1 1 0 001.6.8l5.333-4zM19.933 12.8a1 1 0 000-1.6l-5.333-4A1 1 0 0013 8v8a1 1 0 001.6.8l5.333-4z" />
            </svg>
          </button>
        </div>

        <!-- Temps et vitesse -->
        <div class="flex items-center space-x-4">
          <div class="text-white text-sm font-mono">
            {{ formatTime(currentTime) }} / {{ formatTime(videoDuration) }}
          </div>

          <select
            v-model="playbackRate"
            @change="updatePlaybackRate"
            class="bg-agfa-button text-white text-sm border border-gray-600 rounded px-2 py-1"
          >
            <option value="0.25">0.25x</option>
            <option value="0.5">0.5x</option>
            <option value="0.75">0.75x</option>
            <option value="1">1x</option>
            <option value="1.25">1.25x</option>
            <option value="1.5">1.5x</option>
            <option value="2">2x</option>
          </select>
        </div>
      </div>

      <!-- Barre de progression -->
      <div class="relative">
        <input
          type="range"
          min="0"
          :max="videoDuration"
          :value="currentTime"
          @input="handleSeek"
          class="progress-bar"
        />

        <!-- Marqueurs de timecodes -->
        <div class="absolute top-0 left-0 right-0 h-1 pointer-events-none">
          <div
            v-for="timecode in timecodes"
            :key="timecode.id"
            class="absolute top-0 w-0.5 h-1 bg-opacity-60 transition-opacity"
            :style="{
              left: `${(timecode.start / videoDuration) * 100}%`,
              backgroundColor: timecode.character?.color || '#8455F6'
            }"
          />
        </div>

        <!-- Marqueurs de changements de plan -->
        <div class="absolute top-0 left-0 right-0 h-1 pointer-events-none">
          <div
            v-for="time in sceneChanges"
            :key="time"
            class="absolute top-0 w-1 h-1 bg-gray-400"
            :style="{ left: `${(time / videoDuration) * 100}%` }"
          />
        </div>
      </div>

      <!-- Options d'affichage -->
      <div class="flex items-center justify-between mt-3 text-sm">
        <div class="flex items-center space-x-4">
          <label class="flex items-center text-gray-300">
            <input
              v-model="showTextOverlay"
              type="checkbox"
              class="mr-2 rounded"
            />
            Afficher le texte
          </label>

          <label class="flex items-center text-gray-300">
            <input
              v-model="showSceneChangeIndicator"
              type="checkbox"
              class="mr-2 rounded"
            />
            Numéro de plan
          </label>
        </div>

        <button
          @click="toggleFullscreen"
          class="control-btn"
          title="Plein écran"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import type { Timecode } from '../composables/useRythmoCalculations'

interface Props {
  videoUrl: string
  timecodes?: Timecode[]
  sceneChanges?: number[]
  autoPlay?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  timecodes: () => [],
  sceneChanges: () => [],
  autoPlay: false
})

const emit = defineEmits<{
  'time-update': [time: number]
  'seek': [time: number]
  'play': []
  'pause': []
  'loaded': [duration: number]
}>()

// Références
const videoElement = ref<HTMLVideoElement>()

// État local du lecteur
const currentTime = ref(0)
const isPlaying = ref(false)
const videoDuration = ref(0)

// État local
const isSyncing = ref(false)
const playbackRate = ref('1')
const showTextOverlay = ref(true)
const showSceneChangeIndicator = ref(true)

// Timecode courant
const currentTimecode = computed(() => {
  return props.timecodes.find(tc =>
    currentTime.value >= tc.start && currentTime.value <= tc.end
  )
})

// Numéro de plan courant
const currentSceneNumber = computed(() => {
  if (props.sceneChanges.length === 0) return 1

  const currentSceneIndex = props.sceneChanges.findIndex(time => time > currentTime.value)
  return currentSceneIndex === -1 ? props.sceneChanges.length + 1 : currentSceneIndex + 1
})

// Formatage du temps
function formatTime(seconds: number): string {
  const minutes = Math.floor(seconds / 60)
  const secs = Math.floor(seconds % 60)
  return `${minutes}:${secs.toString().padStart(2, '0')}`
}

// Gestion de la vidéo
function handleVideoLoaded() {
  if (videoElement.value) {
    videoDuration.value = videoElement.value.duration
    emit('loaded', videoElement.value.duration)

    if (props.autoPlay) {
      videoElement.value.play()
    }
  }
}

function handleTimeUpdate() {
  if (videoElement.value) {
    currentTime.value = videoElement.value.currentTime
    emit('time-update', videoElement.value.currentTime)
  }
}

function handlePlay() {
  isPlaying.value = true
  emit('play')
}

function handlePause() {
  isPlaying.value = false
  emit('pause')
}

function handleSeeked() {
  isSyncing.value = false
}

function handleVideoError(event: Event) {
  console.error('Erreur de lecture vidéo:', event)
  isSyncing.value = false
}

// Contrôles
function togglePlayPause() {
  if (!videoElement.value) return

  if (isPlaying.value) {
    videoElement.value.pause()
  } else {
    videoElement.value.play()
  }
}

function skipBackward() {
  if (!videoElement.value) return
  videoElement.value.currentTime = Math.max(0, videoElement.value.currentTime - 10)
}

function skipForward() {
  if (!videoElement.value) return
  videoElement.value.currentTime = Math.min(videoDuration.value, videoElement.value.currentTime + 10)
}

function handleSeek(event: Event) {
  const target = event.target as HTMLInputElement
  const time = parseFloat(target.value)

  if (videoElement.value) {
    isSyncing.value = true
    videoElement.value.currentTime = time
    emit('seek', time)
  }
}

function updatePlaybackRate() {
  if (videoElement.value) {
    videoElement.value.playbackRate = parseFloat(playbackRate.value)
  }
}

function toggleFullscreen() {
  if (!videoElement.value) return

  if (document.fullscreenElement) {
    document.exitFullscreen()
  } else {
    videoElement.value.requestFullscreen()
  }
}

// Synchronisation externe
function seekTo(time: number) {
  if (videoElement.value) {
    isSyncing.value = true
    videoElement.value.currentTime = time
  }
}

// Gestion des raccourcis clavier
function handleKeyDown(event: KeyboardEvent) {
  if (event.target instanceof HTMLInputElement) return

  switch (event.code) {
    case 'Space':
      event.preventDefault()
      togglePlayPause()
      break
    case 'ArrowLeft':
      event.preventDefault()
      skipBackward()
      break
    case 'ArrowRight':
      event.preventDefault()
      skipForward()
      break
    case 'KeyF':
      event.preventDefault()
      toggleFullscreen()
      break
  }
}

// Exposition des méthodes
defineExpose({
  seekTo,
  play: () => videoElement.value?.play(),
  pause: () => videoElement.value?.pause(),
  togglePlayPause
})

// Lifecycle
onMounted(() => {
  document.addEventListener('keydown', handleKeyDown)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeyDown)
})

// Synchronisation avec les changements externes de temps
watch(() => currentTime.value, (newTime) => {
  if (videoElement.value && Math.abs(videoElement.value.currentTime - newTime) > 0.5) {
    videoElement.value.currentTime = newTime
  }
})
</script>

<style scoped>
.play-pause-btn {
  background: #8455F6;
  color: white;
  padding: 0.5rem;
  border-radius: 50%;
  transition: all 0.2s;
}

.play-pause-btn:hover {
  background: #7c3aed;
  transform: scale(1.05);
}

.control-btn {
  color: #9ca3af;
  padding: 0.5rem;
  border-radius: 0.375rem;
  transition: all 0.2s;
}

.control-btn:hover {
  color: white;
  background: rgba(132, 85, 246, 0.2);
}

.progress-bar {
  width: 100%;
  height: 0.25rem;
  background: #374151;
  border-radius: 0.125rem;
  appearance: none;
  cursor: pointer;
}

.progress-bar::-webkit-slider-thumb {
  appearance: none;
  width: 1rem;
  height: 1rem;
  background: #8455F6;
  border-radius: 50%;
  cursor: pointer;
}

.progress-bar::-moz-range-thumb {
  width: 1rem;
  height: 1rem;
  background: #8455F6;
  border-radius: 50%;
  cursor: pointer;
  border: none;
}

video::-webkit-media-controls {
  display: none !important;
}

video::-moz-media-controls {
  display: none !important;
}
</style>
