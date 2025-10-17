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
          v-for="(sceneChange, scIdx) in sceneChanges"
          :key="`scene-${scIdx}-${sceneChange}`"
          class="scene-change-indicator"
          :style="{ left: getSceneChangePosition(sceneChange) + '%' }"
          :title="`Changement de plan à ${formatTime(sceneChange)} (clic pour naviguer)`"
          @click="seekToSceneChange(sceneChange)"
        ></div>

        <!-- Blocs de timecodes simplifiés -->
        <div
          v-for="(timecode, tcIdx) in timecodes"
          :key="`tc-${tcIdx}-${timecode.id ?? 'new'}-${timecode.start}-${timecode.end}`"
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
    <div class="controls-container flex flex-col items-center gap-2 md:flex-row md:relative md:justify-center">
      <!-- Indicateur de durée vidéo -->
      <div class="duration-indicator md:absolute md:left-0 md:top-1/2 md:-translate-y-1/2">
        {{ formatTime(props.currentTime) }} / {{ formatTime(props.videoDuration) }}
      </div>

      <!-- Contrôles vidéo -->
      <div class="video-controls flex-wrap justify-center">
        <!-- Navigation entre changements de plan -->
        <div class="control-group scene-controls">
          <button
            @click="emit('navigateSceneChange', 'previous')"
            title="← Changement de plan précédent (Shift + ←)"
            class="control-button scene-button"
            :class="{ 'pressed': pressedKey === 'scene-previous' }"
            :disabled="!props.sceneChanges || props.sceneChanges.length === 0"
          >
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none">
              <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span class="control-label hidden sm:inline">Plan</span>
          </button>

          <button
            @click="emit('navigateSceneChange', 'next')"
            title="→ Changement de plan suivant (Shift + →)"
            class="control-button scene-button"
            :class="{ 'pressed': pressedKey === 'scene-next' }"
            :disabled="!props.sceneChanges || props.sceneChanges.length === 0"
          >
            <span class="control-label hidden sm:inline">Plan</span>
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none">
              <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
        </div>

        <!-- Séparateur -->
        <div class="control-separator hidden md:block"></div>

        <!-- Navigation frame par frame -->
        <button
          @click="emit('seekFrame', -1)"
          title="← 1 frame (Q)"
          class="control-button"
          :class="{ 'pressed': pressedKey === 'frame-left' }"
        >
          -1f
        </button>

        <!-- Navigation secondes -->
        <button
          @click="emit('seekDelta', -1)"
          title="← 1s (Flèche gauche)"
          class="control-button"
          :class="{ 'pressed': pressedKey === 'seek-left' }"
        >
          -1s
        </button>

        <!-- Bouton play/pause -->
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

        <!-- Navigation secondes -->
        <button
          @click="emit('seekDelta', 1)"
          title="→ 1s (Flèche droite)"
          class="control-button"
          :class="{ 'pressed': pressedKey === 'seek-right' }"
        >
          +1s
        </button>

        <!-- Navigation frame par frame -->
        <button
          @click="emit('seekFrame', 1)"
          title="→ 1 frame (E)"
          class="control-button"
          :class="{ 'pressed': pressedKey === 'frame-right' }"
        >
          +1f
        </button>

        <!-- Séparateur -->
        <div class="control-separator hidden md:block"></div>

        <!-- Navigation entre timecodes -->
        <div class="control-group timecode-controls">
          <button
            @click="emit('navigateTimecode', 'previous')"
            title="↑ Timecode précédent (↑)"
            class="control-button timecode-button"
            :class="{ 'pressed': pressedKey === 'timecode-previous' }"
            :disabled="!props.timecodes || props.timecodes.length === 0"
          >
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none">
              <path d="M18 15L12 9L6 15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span class="control-label hidden sm:inline">TC</span>
          </button>

          <button
            @click="emit('navigateTimecode', 'next')"
            title="↓ Timecode suivant (↓)"
            class="control-button timecode-button"
            :class="{ 'pressed': pressedKey === 'timecode-next' }"
            :disabled="!props.timecodes || props.timecodes.length === 0"
          >
            <span class="control-label hidden sm:inline">TC</span>
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none">
              <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
        </div>
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
  (e: 'navigateSceneChange', direction: 'next' | 'previous'): void
  (e: 'navigateTimecode', direction: 'next' | 'previous'): void
}>()

// Refs
const progressBarContainer = ref<HTMLElement | null>(null)
const isDragging = ref(false)
const hoverTime = ref<number | null>(null)
const pressedKey = ref<string | null>(null)
const pendingSeekTime = ref<number | null>(null)

// Constantes pour la compensation de décalage (identique à RythmoBandSingle)
const FRAME_OFFSET = 8 // Décalage de 8 frames
const FPS = 25 // 25 frames par seconde

// Computed
const linesCount = computed(() => {
  return Math.max(1, Math.min(6, props.rythmoLinesCount || 1))
})

const containerHeight = computed(() => {
  // Hauteur plus compacte avec un espacement dégressif
  const baseHeight = 2.3 // 2.5rem de base (40px)

  // Espacement dégressif : plus il y a de lignes, moins on ajoute par ligne
  let extraHeight = 0
  if (linesCount.value <= 2) {
    extraHeight = (linesCount.value - 1) * 0.5 // 1.2rem pour les 2 premières lignes
  } else if (linesCount.value <= 4) {
    extraHeight = 1.2 + (linesCount.value - 2) * 0.4 // Puis 0.8rem par ligne
  } else {
    extraHeight = 1.2 + 1.6 + (linesCount.value - 4) * 0.5 // Puis 0.5rem par ligne
  }

  return baseHeight + extraHeight
})

const minContainerHeight = computed(() => {
  // Hauteur minimale plus compacte
  const baseMinHeight = 40 // 65px de base

  // Espacement minimal dégressif
  let extraMinHeight = 0
  if (linesCount.value <= 2) {
    extraMinHeight = (linesCount.value - 1) * 20 // 20px pour les 2 premières lignes
  } else if (linesCount.value <= 4) {
    extraMinHeight = 20 + (linesCount.value - 2) * 8 // Puis 15px par ligne
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

  return {
    left: Math.max(0, startPos) + '%',
    width: Math.max(0.5, Math.min(100, width)) + '%',
    top: `${topOffset}px`, // Positionnement depuis le haut
    '--character-color': color,
    '--line-color': color,
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
  // Le timecode a été créé avec un décalage de -FRAME_OFFSET/FPS
  // On compense pour que la vidéo en pause s'affiche correctement
  const targetTime = timecode.start + (FRAME_OFFSET / FPS)
  emit('seek', Math.max(0, targetTime))
}

function seekToSceneChange(sceneChangeTime: number) {
  // Le scene change a été créé avec un décalage de -FRAME_OFFSET/FPS
  // On compense pour que la vidéo en pause s'affiche correctement
  const targetTime = sceneChangeTime + (FRAME_OFFSET / FPS)
  emit('seek', Math.max(0, targetTime))
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
  pendingSeekTime.value = time
  event.preventDefault()
}

function onMouseMove(event: MouseEvent) {
  const time = getTimeFromPosition(event.clientX)
  hoverTime.value = time

  if (isDragging.value) {
    pendingSeekTime.value = time
  }
}

function onMouseUp() {
  if (isDragging.value && pendingSeekTime.value !== null) {
    emit('seek', pendingSeekTime.value)
  }
  isDragging.value = false
  pendingSeekTime.value = null
}

function onMouseLeave() {
  hoverTime.value = null
  if (isDragging.value && pendingSeekTime.value !== null) {
    emit('seek', pendingSeekTime.value)
  }
  isDragging.value = false
  pendingSeekTime.value = null
}

// Gestion des événements tactiles pour mobile
function onTouchStart(event: TouchEvent) {
  isDragging.value = true
  const clientX = getClientXFromEvent(event)
  const time = getTimeFromPosition(clientX)
  pendingSeekTime.value = time
  event.preventDefault()
}

function onTouchMove(event: TouchEvent) {
  const clientX = getClientXFromEvent(event)
  const time = getTimeFromPosition(clientX)
  hoverTime.value = time

  if (isDragging.value) {
    pendingSeekTime.value = time
    event.preventDefault() // Empêche le défilement de la page
  }
}

function onTouchEnd() {
  if (isDragging.value && pendingSeekTime.value !== null) {
    emit('seek', pendingSeekTime.value)
  }
  isDragging.value = false
  hoverTime.value = null
  pendingSeekTime.value = null
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

  // Navigation entre changements de plan avec Shift + flèches
  if (event.shiftKey && event.key === 'ArrowLeft') {
    keyPressed = 'scene-previous'
    emit('navigateSceneChange', 'previous')
    event.preventDefault()
  } else if (event.shiftKey && event.key === 'ArrowRight') {
    keyPressed = 'scene-next'
    emit('navigateSceneChange', 'next')
    event.preventDefault()
  }
  // Navigation entre timecodes avec flèches haut/bas
  else if (event.key === 'ArrowUp' && !event.shiftKey) {
    keyPressed = 'timecode-previous'
    emit('navigateTimecode', 'previous')
    event.preventDefault()
  } else if (event.key === 'ArrowDown' && !event.shiftKey) {
    keyPressed = 'timecode-next'
    emit('navigateTimecode', 'next')
    event.preventDefault()
  }
  // Contrôles vidéo existants
  else {
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
        if (!event.shiftKey) { // Évite le conflit avec navigation des changements de plan
          keyPressed = 'seek-left'
          emit('seekDelta', -1)
          event.preventDefault()
        }
        break
      case 'ArrowRight':
        if (!event.shiftKey) { // Évite le conflit avec navigation des changements de plan
          keyPressed = 'seek-right'
          emit('seekDelta', 1)
          event.preventDefault()
        }
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
  }

  // Animation visuelle si une touche a été détectée
  if (keyPressed) {
    pressedKey.value = keyPressed
    setTimeout(() => {
      pressedKey.value = null
    }, 150) // Animation de 150ms
  }
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
  padding: 0.4rem;
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
  /* justify-content: center; */ /* Géré par Tailwind */
  gap: 1rem;
  margin-top: 0.75rem;
  /* position: relative; */ /* Géré par Tailwind */
}

.duration-indicator {
  /* position: absolute; */ /* Géré par Tailwind */
  /* left: 0; */ /* Géré par Tailwind */
  /* top: 50%; */ /* Géré par Tailwind */
  /* transform: translateY(-50%); */ /* Géré par Tailwind */
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

/* Groupes de contrôles */
.control-group {
  display: flex;
  align-items: center;
  gap: 0.125rem;
  background: rgba(31, 41, 55, 0.5);
  border-radius: 0.375rem;
  padding: 0.125rem;
  border: 1px solid rgba(75, 85, 99, 0.3);
}

/* Séparateur entre groupes */
.control-separator {
  width: 1px;
  height: 1.5rem;
  background: #4b5563;
  margin: 0 0.5rem;
}

/* Boutons de navigation spécialisés */
.scene-button, .timecode-button {
  min-width: 3rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.25rem;
  font-size: 0.6rem;
  padding: 0.25rem 0.5rem;
}

.scene-button {
  background: rgba(101, 115, 144, 0.2);
  border-color: rgba(101, 115, 144, 0.4);
}

.scene-button:hover {
  background: rgba(101, 115, 144, 0.4);
  border-color: rgba(101, 115, 144, 0.6);
}

.scene-button:disabled {
  opacity: 0.4;
  cursor: not-allowed;
  background: rgba(75, 85, 99, 0.2);
}

.timecode-button {
  background: rgba(132, 85, 246, 0.2);
  border-color: rgba(132, 85, 246, 0.4);
}

.timecode-button:hover {
  background: rgba(132, 85, 246, 0.4);
  border-color: rgba(132, 85, 246, 0.6);
}

.timecode-button:disabled {
  opacity: 0.4;
  cursor: not-allowed;
  background: rgba(75, 85, 99, 0.2);
}

.control-label {
  font-size: 0.5rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.025em;
}

/* Animations pour les boutons de navigation */
.scene-button.pressed,
.timecode-button.pressed {
  transform: scale(0.95);
  box-shadow: 0 0 0 3px rgba(132, 85, 246, 0.3);
}

.scene-button.pressed {
  box-shadow: 0 0 0 3px rgba(101, 115, 144, 0.3);
}

/* Scrollbar personnalisée pour le container si nécessaire */
.progress-container::-webkit-scrollbar {
  display: none;
}
</style>
