<template>
  <div
    class="video-navigation-bar"
    :style="{ minHeight: minContainerHeight + 'px' }"
  >
    <!-- Barre de progression principale -->
    <div
      ref="progressBarContainer"
      class="progress-container"
      :style="{ height: containerHeight + 'rem' }"
      @mousedown="onMouseDown"
      @mousemove="onMouseMove"
      @mouseup="onMouseUp"
      @mouseleave="onMouseLeave"
      @touchstart="onTouchStart"
      @touchmove="onTouchMove"
      @touchend="onTouchEnd"

    >
      <!-- Timeline de fond -->
      <div class="progress-background">
        <!-- Graduations temporelles -->
        <div class="time-markers">
          <div
            v-for="marker in filteredTimeMarkers"
            :key="marker.time"
            class="time-marker"
            :style="{ left: marker.position + '%' }"
          >
            <div class="marker-line"></div>
            <div class="marker-text">{{ formatTimeShort(marker.time) }}</div>
          </div>
        </div>

        <!-- Indicateurs de changements de plan -->
        <div
          v-for="sceneChange in sceneChanges"
          :key="sceneChange"
          class="scene-change-indicator"
          :style="{ left: getSceneChangePosition(sceneChange) + '%' }"
          :title="`Changement de plan à ${formatTime(sceneChange)} (clic pour naviguer)`"
          @click="seekToSceneChange(sceneChange)"
        ></div>

        <!-- Blocs de timecodes simplifiés -->
        <div
          v-for="timecode in timecodes"
          :key="timecode.id || `${timecode.start}-${timecode.end}`"
          class="timecode-block"
          :class="{ 'active': isTimecodeActive(timecode) }"
          :style="getSimpleTimecodeStyle(timecode)"
          @click="seekToTimecode(timecode)"
          :title="`Ligne ${timecode.line_number}: ${timecode.text}`"
        ></div>
      </div>

      <!-- Barre de progression -->
      <div class="progress-bar">
        <div
          class="progress-fill"
          :style="{ width: progressPercentage + '%' }"
        ></div>
      </div>

      <!-- Curseur de position actuelle -->
      <div
        class="progress-cursor"
        :class="{ 'keyboard-active': pressedKey && ['seek-left', 'seek-right', 'frame-left', 'frame-right'].includes(pressedKey) }"
        :style="{ left: progressPercentage + '%' }"
      >
        <div class="cursor-handle"></div>
      </div>

      <!-- Indicateur de survol -->
      <div
        v-if="hoverTime !== null"
        class="hover-indicator"
        :style="{ left: hoverPercentage + '%' }"
      >
        <div class="hover-line"></div>
        <div class="hover-time">{{ formatTime(hoverTime) }}</div>
      </div>
    </div>

    <!-- Contrôles vidéo -->
    <div class="controls-container">
      <!-- Indicateur de durée vidéo -->
      <div class="duration-indicator">
        {{ formatTime(props.currentTime) }} / {{ formatTime(props.videoDuration) }}
      </div>

      <!-- Contrôles vidéo -->
      <div class="video-controls">
        <button
          @click="emit('seekDelta', -1)"
          title="← 1s (Flèche gauche)"
          class="control-button"
          :class="{ 'pressed': pressedKey === 'seek-left' }"
        >
          -1s
        </button>

        <button
          @click="emit('seekFrame', -1)"
          title="← 1 frame (A)"
          class="control-button"
          :class="{ 'pressed': pressedKey === 'frame-left' }"
        >
          -1f
        </button>

        <button
          @click="emit('seekDelta', 0)"
          title="Lecture/Pause (Espace)"
          class="control-button play-pause"
          :class="{ 'pressed': pressedKey === 'play-pause' }"
        >
          <svg
            v-if="props.isVideoPaused"
            width="16"
            height="16"
            viewBox="0 0 22 22"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
          >
            <polygon points="6,4 18,11 6,18" fill="currentColor"></polygon>
          </svg>
          <svg
            v-else
            width="16"
            height="16"
            viewBox="0 0 22 22"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
          >
            <rect x="6" y="4" width="3.5" height="14" fill="currentColor"></rect>
            <rect x="12.5" y="4" width="3.5" height="14" fill="currentColor"></rect>
          </svg>
        </button>

        <button
          @click="emit('seekFrame', 1)"
          title="→ 1 frame (E)"
          class="control-button"
          :class="{ 'pressed': pressedKey === 'frame-right' }"
        >
          +1f
        </button>

        <button
          @click="emit('seekDelta', 1)"
          title="→ 1s (Flèche droite)"
          class="control-button"
          :class="{ 'pressed': pressedKey === 'seek-right' }"
        >
          +1s
        </button>
      </div>


    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'

// Interfaces
interface Character {
  id: number
  name: string
  color?: string
}

interface Timecode {
  id?: number
  start: number
  end: number
  text: string
  line_number?: number
  character_id?: number | null
  character?: Character
  show_character?: boolean
}

// Props
const props = defineProps<{
  currentTime: number
  videoDuration: number
  timecodes: Timecode[]
  sceneChanges?: number[]
  isVideoPaused?: boolean
  rythmoLinesCount?: number
}>()

// Emits
const emit = defineEmits<{
  (e: 'seek', time: number): void
  (e: 'seekFrame', delta: number): void
  (e: 'seekDelta', delta: number): void
  (e: 'togglePlayPause'): void
}>()

// Refs
const progressBarContainer = ref<HTMLElement | null>(null)
const isDragging = ref(false)
const hoverTime = ref<number | null>(null)
const pressedKey = ref<string | null>(null)

// Computed
const linesCount = computed(() => {
  return Math.max(1, Math.min(6, props.rythmoLinesCount || 1))
})

const containerHeight = computed(() => {
  // Hauteur plus compacte avec un espacement dégressif
  const baseHeight = 2.5 // 2.5rem de base (40px)

  // Espacement dégressif : plus il y a de lignes, moins on ajoute par ligne
  let extraHeight = 0
  if (linesCount.value <= 2) {
    extraHeight = (linesCount.value - 1) * 1.2 // 1.2rem pour les 2 premières lignes
  } else if (linesCount.value <= 4) {
    extraHeight = 1.2 + (linesCount.value - 2) * 0.8 // Puis 0.8rem par ligne
  } else {
    extraHeight = 1.2 + 1.6 + (linesCount.value - 4) * 0.5 // Puis 0.5rem par ligne
  }

  return baseHeight + extraHeight
})

const minContainerHeight = computed(() => {
  // Hauteur minimale plus compacte
  const baseMinHeight = 65 // 65px de base

  // Espacement minimal dégressif
  let extraMinHeight = 0
  if (linesCount.value <= 2) {
    extraMinHeight = (linesCount.value - 1) * 20 // 20px pour les 2 premières lignes
  } else if (linesCount.value <= 4) {
    extraMinHeight = 20 + (linesCount.value - 2) * 15 // Puis 15px par ligne
  } else {
    extraMinHeight = 20 + 30 + (linesCount.value - 4) * 10 // Puis 10px par ligne
  }

  return baseMinHeight + extraMinHeight
})

const progressPercentage = computed(() => {
  if (!props.videoDuration) return 0
  return (props.currentTime / props.videoDuration) * 100
})

const hoverPercentage = computed(() => {
  if (hoverTime.value === null || !props.videoDuration) return 0
  return (hoverTime.value / props.videoDuration) * 100
})

const timeMarkers = computed(() => {
  if (!props.videoDuration) return []

  const markers = []

  // Calcul de l'intervalle selon la durée totale
  let interval = 10 // secondes par défaut
  if (props.videoDuration <= 30) interval = 2
  else if (props.videoDuration <= 120) interval = 10
  else if (props.videoDuration <= 600) interval = 30
  else if (props.videoDuration <= 1800) interval = 120
  else if (props.videoDuration <= 3600) interval = 300
  else interval = 600

  for (let time = 0; time <= props.videoDuration; time += interval) {
    const position = (time / props.videoDuration) * 100
    markers.push({ time, position })
  }

  return markers
})

// Filtre les marqueurs pour éviter la superposition
const filteredTimeMarkers = computed(() => {
  if (!timeMarkers.value.length) return []

  const filtered = []
  let lastPosition = -Infinity
  const minDistance = 8 // Pourcentage minimum entre les marqueurs

  for (const marker of timeMarkers.value) {
    if (marker.position - lastPosition >= minDistance) {
      filtered.push(marker)
      lastPosition = marker.position
    }
  }

  return filtered
})

// Methods
function formatTime(seconds: number): string {
  const mins = Math.floor(seconds / 60)
  const secs = Math.floor(seconds % 60)
  const ms = Math.floor((seconds % 1) * 100)
  return `${mins}:${secs.toString().padStart(2, '0')}.${ms.toString().padStart(2, '0')}`
}

function formatTimeShort(seconds: number): string {
  const mins = Math.floor(seconds / 60)
  const secs = Math.floor(seconds % 60)

  if (mins > 0) {
    return `${mins}:${secs.toString().padStart(2, '0')}`
  } else {
    return `${secs}s`
  }
}

function getTimecodePosition(time: number): number {
  if (!props.videoDuration) return 0
  return (time / props.videoDuration) * 100
}

function getSimpleTimecodeStyle(timecode: Timecode) {
  const startPos = getTimecodePosition(timecode.start)
  const endPos = getTimecodePosition(timecode.end)
  const width = endPos - startPos

  // Ne pas afficher si complètement en dehors du viewport
  if (startPos > 100 || endPos < 0 || width < 0.5) {
    return { display: 'none' }
  }

  const lineNumber = timecode.line_number || 1
  const totalLines = linesCount.value

  // N'afficher que les lignes qui correspondent au nombre choisi
  if (lineNumber > totalLines) {
    return { display: 'none' }
  }

  // Utiliser la couleur du personnage si disponible, sinon la couleur de ligne
  const color = timecode.character?.color || getLineColor(lineNumber)

  // Calcul de l'espacement vertical compact avec répartition intelligente
  const availableHeight = containerHeight.value * 16 - 30 // Hauteur en px moins marge réduite
  let lineSpacing = 16 // Espacement minimum

  if (totalLines <= 2) {
    lineSpacing = Math.min(25, availableHeight / Math.max(totalLines, 2))
  } else if (totalLines <= 4) {
    lineSpacing = Math.min(20, availableHeight / totalLines)
  } else {
    lineSpacing = Math.min(15, availableHeight / totalLines) // Très compact pour 5-6 lignes
  }

  // INVERSION: ligne 1 en haut (top), dernière ligne en bas
  // Pour cela, on calcule depuis le haut au lieu du bas
  const topOffset = (lineNumber - 1) * lineSpacing + 5 // 5px de marge du haut
  const baseZIndex = timecode.character ? 40 : 30

  return {
    left: Math.max(0, startPos) + '%',
    width: Math.max(0.5, Math.min(100, width)) + '%',
    top: `${topOffset}px`, // Positionnement depuis le haut
    '--character-color': color,
    '--line-color': color,
    zIndex: baseZIndex + lineNumber // Z-index différent par ligne
  }
}

function getLineColor(lineNumber: number): string {
  const colors = [
    '#8455F6', // Violet (ligne 1)
    '#F59E0B', // Orange (ligne 2)
    '#10B981', // Vert (ligne 3)
    '#EF4444', // Rouge (ligne 4)
    '#3B82F6', // Bleu (ligne 5)
    '#8B5CF6'  // Violet clair (ligne 6)
  ]
  return colors[(lineNumber - 1) % colors.length]
}

function getSceneChangePosition(sceneChange: number): number {
  return getTimecodePosition(sceneChange)
}

function isTimecodeActive(timecode: Timecode): boolean {
  return props.currentTime >= timecode.start && props.currentTime <= timecode.end
}

function seekToTimecode(timecode: Timecode) {
  emit('seek', timecode.start)
}

function seekToSceneChange(sceneChangeTime: number) {
  emit('seek', sceneChangeTime)
}

function getTimeFromPosition(clientX: number): number {
  if (!progressBarContainer.value || !props.videoDuration) return 0

  const rect = progressBarContainer.value.getBoundingClientRect()
  const percentage = Math.max(0, Math.min(100, ((clientX - rect.left) / rect.width) * 100))

  return Math.max(0, Math.min(props.videoDuration, (percentage * props.videoDuration) / 100))
}

function getClientXFromEvent(event: TouchEvent | MouseEvent): number {
  if ('touches' in event) {
    // Événement tactile
    return event.touches[0]?.clientX || event.changedTouches[0]?.clientX || 0
  }
  // Événement de souris
  return event.clientX
}

function onMouseDown(event: MouseEvent) {
  isDragging.value = true
  const time = getTimeFromPosition(event.clientX)
  emit('seek', time)
  event.preventDefault()
}

function onMouseMove(event: MouseEvent) {
  const time = getTimeFromPosition(event.clientX)
  hoverTime.value = time

  if (isDragging.value) {
    emit('seek', time)
  }
}

function onMouseUp() {
  isDragging.value = false
}

function onMouseLeave() {
  hoverTime.value = null
  isDragging.value = false
}

// Gestion des événements tactiles pour mobile
function onTouchStart(event: TouchEvent) {
  isDragging.value = true
  const clientX = getClientXFromEvent(event)
  const time = getTimeFromPosition(clientX)
  emit('seek', time)
  event.preventDefault()
}

function onTouchMove(event: TouchEvent) {
  const clientX = getClientXFromEvent(event)
  const time = getTimeFromPosition(clientX)
  hoverTime.value = time

  if (isDragging.value) {
    emit('seek', time)
    event.preventDefault() // Empêche le défilement de la page
  }
}

function onTouchEnd() {
  isDragging.value = false
  hoverTime.value = null
}

// Gestion des événements clavier avec animations
function onKeyDown(event: KeyboardEvent) {
  // Ignore si focus dans un champ de saisie (input, textarea, ou contenteditable)
  const target = event.target as HTMLElement | null
  if (!target) return
  const tag = target.tagName.toLowerCase()
  const isEditable = tag === 'input' || tag === 'textarea' || target.isContentEditable
  if (isEditable) return

  let keyPressed = ''

  switch (event.code) {
    case 'KeyQ': // Touche Q (position A sur AZERTY)
      keyPressed = 'frame-left'
      emit('seekFrame', -1)
      event.preventDefault()
      break
    case 'KeyE': // Touche E (même position sur AZERTY et QWERTY)
      keyPressed = 'frame-right'
      emit('seekFrame', 1)
      event.preventDefault()
      break
    case 'ArrowLeft':
      keyPressed = 'seek-left'
      emit('seekDelta', -1)
      event.preventDefault()
      break
    case 'ArrowRight':
      keyPressed = 'seek-right'
      emit('seekDelta', 1)
      event.preventDefault()
      break
    case 'Space':
      keyPressed = 'play-pause'

      const video = document.querySelector('video')
      if (video) {
        if (video.paused) {
          video.play()
        } else {
          video.pause()
        }
        emit('togglePlayPause')
      }
      event.preventDefault()
      break
    default:
      return
  }

  // Animation visuelle
  pressedKey.value = keyPressed
  setTimeout(() => {
    pressedKey.value = null
  }, 150) // Animation de 150ms
}// Écouteurs d'événements clavier
onMounted(() => {
  window.addEventListener('keydown', onKeyDown)
})

onUnmounted(() => {
  window.removeEventListener('keydown', onKeyDown)
})
</script>

<style scoped>
.video-navigation-bar {
  width: 100%;
  background: #202937;
  border: 1px solid #4b5563;
  border-radius: 0.5rem;
  padding: 0.75rem;
  margin-bottom: 1rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
  /* min-height sera définie dynamiquement via :style */
}

.progress-container {
  position: relative;
  /* height sera définie dynamiquement via :style */
  background: #1f2937;
  border-radius: 0.5rem;
  overflow: visible;
  cursor: pointer;
  user-select: none;
  touch-action: none; /* Empêche le défilement natif lors du glissement */
  -webkit-touch-callout: none; /* Désactive le menu contextuel sur iOS */
}

.progress-background {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
}

.time-markers {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
}

.time-marker {
  position: absolute;
  top: 0;
  bottom: 0;
  pointer-events: none;
}

.marker-line {
  position: absolute;
  top: 0;
  width: 1px;
  background: #6b7280;
  height: 100%;
}

.marker-text {
  position: absolute;
  top: 0.125rem;
  left: 0.25rem;
  font-size: 0.65rem;
  color: #9ca3af;
  font-family: ui-monospace, SFMono-Regular, monospace;
  transform: translateX(-50%);
  background: rgba(31, 41, 55, 0.8);
  padding: 0.125rem 0.25rem;
  border-radius: 0.125rem;
  white-space: nowrap;
}

.scene-change-indicator {
  position: absolute;
  top: 0;
  bottom: 0;
  width: 2px;
  background: #657390;
  opacity: 0.95;
  cursor: pointer;
  z-index: 2;
  transition: all 0.2s ease;
}

.scene-change-indicator:hover {
  background: #8B9DC3;
  width: 3px;
  opacity: 1;
}

.scene-change-indicator::after {
  content: '';
  position: absolute;
  top: -0.25rem;
  left: 50%;
  width: 0.5rem;
  height: 0.5rem;
  background: #657390;
  border-radius: 9999px;
  transform: translateX(-50%);
  transition: all 0.2s ease;
}

.scene-change-indicator:hover::after {
  background: #8B9DC3;
  width: 0.625rem;
  height: 0.625rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.timecode-block {
  position: absolute;
  /* top sera défini par le style inline pour chaque ligne */
  height: 0.25rem;
  cursor: pointer;
  transition: all 0.15s ease;
  background: var(--character-color, var(--line-color, #8455F6));
  opacity: 0.75;
  min-width: 3px;
  border-radius: 2px;
  backdrop-filter: blur(1px);
  mix-blend-mode: screen;
}

.timecode-block:hover {
  opacity: 0.85;
  height: 0.375rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
}

.timecode-block.active {
  opacity: 0.95;
  height: 0.5rem;
  box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.4), 0 2px 6px rgba(0, 0, 0, 0.3);
}



.progress-bar {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 0.1875rem;
  background: rgba(55, 65, 81, 0.8);
  border-radius: 1px;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #8455F6 0%, #a78bfa 100%);
  transition: all 0.1s;
  border-radius: 1px;
}

.progress-cursor {
  position: absolute;
  top: 0;
  bottom: 0;
  pointer-events: none;
  z-index: 4;
  transition: left 0.1s ease-out;
}

.cursor-handle {
  position: absolute;
  top: 0;
  left: 0;
  width: 0.125rem;
  height: 100%;
  background: #8455F6;
  transform: translateX(-50%);
}

.cursor-handle::before {
  content: '';
  position: absolute;
  top: 100%;
  left: 50%;
  width: 0.875rem;
  height: 0.875rem;
  background: #8455F6;
  border: 2px solid white;
  border-radius: 50%;
  transform: translate(-50%, -50%);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
  transition: all 0.15s ease-out;
}

.progress-cursor.keyboard-active .cursor-handle::before {
  animation: cursor-bounce 0.2s ease-out;
}

@keyframes cursor-bounce {
  0% {
    transform: translate(-50%, -50%) scale(1);
  }
  50% {
    transform: translate(-50%, -50%) scale(1.2);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3), 0 0 0 4px rgba(132, 85, 246, 0.3);
  }
  100% {
    transform: translate(-50%, -50%) scale(1);
  }
}

/* Zone tactile agrandie pour mobile */
@media (pointer: coarse) {
  .cursor-handle::before {
    width: 1.25rem;
    height: 1.25rem;
  }
}



.hover-indicator {
  position: absolute;
  top: 0;
  bottom: 0;
  pointer-events: none;
  z-index: 3;
}

.hover-line {
  position: absolute;
  top: 0;
  bottom: 0;
  width: 2px;
  background: #8455F6;
  opacity: 0.7;
  border-radius: 1px;
}

.hover-time {
  position: absolute;
  top: -1.5rem;
  left: 0;
  padding: 0.125rem 0.25rem;
  background: #111827;
  color: white;
  font-size: 0.75rem;
  border-radius: 0.25rem;
  font-family: ui-monospace, SFMono-Regular, monospace;
  transform: translateX(-50%);
}

.controls-container {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  margin-top: 0.75rem;
  position: relative;
}

.duration-indicator {
  position: absolute;
  left: 0;
  top: 50%;
  transform: translateY(-50%);
  font-family: ui-monospace, SFMono-Regular, monospace;
  font-size: 0.75rem;
  font-weight: 600;
  color: #9ca3af;
  background: rgba(31, 41, 55, 0.8);
  padding: 0.375rem 0.5rem;
  border-radius: 0.25rem;
  border: 1px solid rgba(75, 85, 99, 0.3);
  backdrop-filter: blur(4px);
}

.video-controls {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.control-button {
  background: #384152;
  color: white;
  border: 1px solid #4b5563;
  border-radius: 0.25rem;
  padding: 0.375rem 0.75rem;
  font-size: 0.75rem;
  font-weight: 500;
  transition: all 0.2s;
  cursor: pointer;
  min-height: 2rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.control-button:hover {
  background: #8455F6;
}

.control-button.pressed {
  background: #8455F6 !important;
  transform: scale(0.95);
  box-shadow: 0 0 0 3px rgba(132, 85, 246, 0.3);
  animation: keypress-pulse 0.15s ease-out;
}

@keyframes keypress-pulse {
  0% {
    box-shadow: 0 0 0 0 rgba(132, 85, 246, 0.7);
  }
  70% {
    box-shadow: 0 0 0 6px rgba(132, 85, 246, 0);
  }
  100% {
    box-shadow: 0 0 0 0 rgba(132, 85, 246, 0);
  }
}

.control-button.play-pause {
  min-width: 2.5rem;
  padding: 0.375rem 0.5rem;
}



/* Scrollbar personnalisée pour le container si nécessaire */
.progress-container::-webkit-scrollbar {
  display: none;
}
</style>
