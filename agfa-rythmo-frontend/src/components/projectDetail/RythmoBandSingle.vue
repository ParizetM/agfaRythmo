<template>
  <div class="rythmo-band" @mouseenter="isHovered = true" @mouseleave="isHovered = false">
    <div class="rythmo-track-container" ref="trackContainer" :style="{ width: `${bandWidth}px` }">
      <div
        class="rythmo-content"
        :class="{ 'no-transition': noTransition }"
        :style="rythmoTextStyle"
      >

        <div class="rythmo-text">
          <template v-if="bandElements.length">
            <template v-for="(el, idx) in bandElements" :key="'el' + idx">
              <div
                v-if="el.type === 'block'"
                class="rythmo-block"
                :class="{
                  active: el.tcIdx === activeIdx,
                  'is-dragged-block': isBlockBeingDragged(el.tcIdx),
                  'is-dragged-away': isBlockMovingAway(el.tcIdx),
                }"
                :style="getAbsoluteBlockStyle(el)"
                @click="onBlockClick(el.tcIdx)"
                @dblclick="onBlockDblClick(el.tcIdx, el.text)"
                style="cursor: pointer; position: absolute"
              >
                <!-- Zone de redimensionnement gauche -->
                <div
                  class="resize-handle resize-left"
                  @mousedown="onResizeStart(el.tcIdx, 'left', $event)"
                  title="Redimensionner à gauche"
                ></div>

                <!-- Zone de redimensionnement droite -->
                <div
                  class="resize-handle resize-right"
                  @mousedown="onResizeStart(el.tcIdx, 'right', $event)"
                  title="Redimensionner à droite"
                ></div>

                <!-- Zone de déplacement (bord inférieur) -->
                <div
                  class="move-handle"
                  @mousedown="onMoveStart(el.tcIdx, $event)"
                  title="Déplacer le timecode"
                ></div>



                <template v-if="editingIdx === el.tcIdx">
                  <input
                    :ref="setEditInputRef"
                    v-model="editingText"
                    @blur="finishEdit"
                    @keyup.enter="finishEdit"
                    @keyup.esc="cancelEdit"
                    class="rythmo-edit-input"
                    :style="{
                      minWidth: getBlockWidth(el.tcIdx) + 'px',
                      overflow: 'visible',
                      whiteSpace: 'pre',
                    }"
                    style="
                      font-size: 1.2rem;
                      font-weight: 600;
                      background: #23272f;
                      color: #fff;
                      border-radius: 4px;
                      border: 1px solid #8455f6;
                      padding: 0 6px;
                      outline: none;
                      height: 2.2rem;
                      overflow: visible;
                      white-space: pre;
                    "
                  />
                </template>
                <template v-else>
                  <!-- Étiquette du personnage en haut à droite -->
                  <div
                    v-if="shouldShowCharacter(el.tcIdx) && getBlockWidth(el.tcIdx) >= 100"
                    class="character-tag"
                    :class="{ 'character-tag-hovered': characterDropdownIdx === el.tcIdx }"
                    :style="{
                      backgroundColor: getTimecodeCharacter(el.tcIdx)?.color,
                      color: getContrastColor(getTimecodeCharacter(el.tcIdx)?.color || '#8455F6')
                    }"
                    @mouseenter="hoveredCharacterIdx = el.tcIdx"
                    @mouseleave="hoveredCharacterIdx = null"
                  >
                    {{ getTimecodeCharacter(el.tcIdx)?.name }}

                    <!-- Boutons d'action (visibles seulement en hover) -->
                    <div v-if="hoveredCharacterIdx === el.tcIdx" class="character-actions">
                      <!-- Triangle pour ouvrir le dropdown -->
                      <button
                        class="character-dropdown-btn"
                        @click.stop="toggleCharacterDropdown(el.tcIdx)"
                        title="Changer de personnage"
                      >
                        ▼
                      </button>

                      <!-- Croix pour masquer le personnage -->
                      <button
                        class="character-toggle"
                        @click.stop="toggleCharacterDisplay(el.tcIdx)"
                        title="Masquer le personnage"
                      >
                        ×
                      </button>
                    </div>
                  </div>

                  <!-- Dropdown de sélection de personnage (en dehors du character-tag) -->
                  <div
                    v-if="characterDropdownIdx === el.tcIdx"
                    class="character-dropdown"
                    @click.stop
                    ref="dropdownRef"
                  >
                    <div class="character-dropdown-content">
                      <div
                        v-for="character in props.characters"
                        :key="character.id"
                        class="character-option"
                        :style="{
                          backgroundColor: character.color,
                          color: getContrastColor(character.color)
                        }"
                        @click="changeTimecodeCharacter(el.tcIdx, character.id)"
                      >
                        {{ character.name }}
                      </div>
                    </div>
                  </div>

                  <!-- Bouton pour afficher le personnage quand il est masqué -->
                  <button
                    v-else-if="!shouldShowCharacter(el.tcIdx) && getTimecodeCharacter(el.tcIdx) && getBlockWidth(el.tcIdx) >= 50 && isHovered"
                    class="character-show-btn"
                    @click.stop="toggleCharacterDisplay(el.tcIdx)"
                    :style="{ borderColor: getTimecodeCharacter(el.tcIdx)?.color }"
                    title="Afficher le personnage"
                  >
                    👤
                  </button>

                  <!-- Bouton de suppression au hover -->
                  <button
                    class="delete-timecode-btn"
                    @click.stop="onDeleteClick(el.tcIdx)"
                    title="Supprimer ce timecode"
                  >
                    🗑️
                  </button>

                  <!-- Texte du timecode coloré selon le personnage -->
                  <span
                    class="distort-text"
                    :style="{
                      ...getDistortStyle(el.tcIdx),
                      color: getTimecodeCharacter(el.tcIdx)?.color || 'inherit'
                    }"
                  >
                    {{ el.text }}
                  </span>
                </template>
              </div>

              <div
                v-else-if="el.type === 'gap'"
                class="rythmo-block rythmo-block-gap"
                :style="getAbsoluteGapStyle(el)"
                style="position: absolute"
              >
                <span class="gap-label">{{ el.label }}</span>
              </div>
            </template>
          </template>
          <div
            v-else
            class="rythmo-block rythmo-block-gap"
            :style="
              getAbsoluteGapStyle({
                x: 0,
                width: bandWidth,
                label: `0s - ${totalDuration.toFixed(2)}s`,
                type: 'gap',
              })
            "
            style="position: absolute"
          >
            <span class="gap-label">0s - {{ totalDuration.toFixed(2) }}s</span>
          </div>
        </div>
        <div
          v-if="shouldRenderDragOverlay"
          class="rythmo-block drag-overlay"
          :style="dragOverlayStyle"
        >
          <div
            v-if="dragOverlayCharacterVisible && dragOverlayCharacter"
            class="character-tag"
            :style="{
              backgroundColor: dragOverlayCharacter.color,
              color: getContrastColor(dragOverlayCharacter.color || '#8455F6')
            }"
          >
            {{ dragOverlayCharacter.name }}
          </div>
          <span
            class="distort-text"
            :style="{
              ...dragOverlayTextStyle,
              color: dragOverlayCharacter?.color || 'inherit'
            }"
          >
            {{ dragOverlayText }}
          </span>
        </div>
          <div v-if="isLastLine" class="rythmo-ticks pointer-events-none">
            <template v-for="tick in ticks" :key="'tick' + tick.x">
              <div
                class="rythmo-tick"
                :class="{ 'tick-second': tick.isSecond }"
                :style="getTickStyle(tick)"
              ></div>
            </template>
            <!-- Barres verticales de changement de plan -->

          </div>
          <template v-for="(x, idx) in sceneChangePositions" :key="'scenechange' + idx">
              <div
                class="scene-change-bar"
                :style="{ left: x + 'px' }"
              ></div>
            </template>
      </div>
      <div class="rythmo-cursor"></div>

      <!-- Overlay avec informations de ligne -->
      <div v-if="isHovered" class="line-overlay">
        <div class="line-info">
          <span class="line-number-badge">Ligne {{ lineNumber }}</span>
        </div>
      </div>

    </div>
  </div>



  <!-- Debug infos -->
  <!-- <div class="rythmo-debug">
    <div><b>videoDuration</b> : {{ videoDuration }}</div>
    <div><b>totalDuration</b> : {{ totalDuration }}</div>
    <div><b>visibleWidth</b> : {{ visibleWidth }}</div>
    <div>
      <b>timecodes</b> :
      <pre>{{ timecodes }}</pre>
    </div>
  </div> -->
</template>

<script setup lang="ts">
// --- TICKS (traits réguliers sur la bande) ---
type Tick = { x: number; isSecond: boolean }
const TICK_INTERVAL = 0.2 // secondes entre petits traits
const ticks = computed<Tick[]>(() => {
  const arr: Tick[] = []
  const duration = totalDuration.value
  const pxOffset = computedVisibleWidth.value / 2
  for (let t = 0; t <= duration; t += TICK_INTERVAL) {
    arr.push({
      x: t * PX_PER_SEC + pxOffset,
      isSecond: Math.abs(t % 1) < 0.01 || Math.abs((t % 1) - 1) < 0.01, // tolérance flottante
    })
  }
  return arr
})

function getTickStyle(tick: Tick): CSSProperties {
  return {
    left: tick.x + 'px',
    height: tick.isSecond ? '80%' : '45%',
    width: tick.isSecond ? '3px' : '2px',
    background: tick.isSecond ? '#8455F6' : '#aaa',
    opacity: tick.isSecond ? 0.5 : 0.3,
    position: 'absolute',
    bottom: tick.isSecond ? '0%' : '0%',
    borderRadius: '2px',
    zIndex: 1,
    pointerEvents: 'none',
    transition: 'none',
  }
}
import {
  ref,
  computed,
  onMounted,
  onBeforeUnmount,
  watch,
  nextTick,
  isRef,
  type ComponentPublicInstance,
  type CSSProperties,
} from 'vue'
import { useSmoothScroll } from './useSmoothScroll'
import { type Character } from '../../api/characters'

interface Timecode {
  id?: number
  start: number
  end: number
  text: string
  line_number: number
  character_id?: number | null
  character?: Character
  show_character?: boolean
}

interface DragState {
  active: boolean
  timecode: Timecode
  sourceLineNumber: number
  targetLineNumber: number
  newStart: number
  duration: number
  index: number
}

interface DragStartPayload {
  timecode: Timecode
  sourceLineNumber: number
  index: number
}

interface DragUpdatePayload {
  timecodeId?: number
  index: number
  newStart: number
  targetLineNumber: number
  pointerX: number
  pointerY: number
}

const props = defineProps<{
  timecodes: Timecode[]
  currentTime: number
  videoDuration?: number
  visibleWidth?: number
  instant?: boolean | import('vue').Ref<boolean>
  sceneChanges?: number[]
  lineNumber: number
  isLastLine: boolean
  dragState?: DragState | null
  characters?: Character[]
}>()

const isHovered = ref(false)

// Calcule les positions X (en px) des changements de plan
const sceneChangePositions = computed(() => {
  if (!props.sceneChanges || !props.sceneChanges.length) return []
  return props.sceneChanges.map(t => t * PX_PER_SEC + computedVisibleWidth.value / 2)
})
// Types pour la bande rythmo
type BandBlock = { type: 'block'; x: number; width: number; text: string; tcIdx: number }
type BandGap = { type: 'gap'; x: number; width: number; label: string }
type BandElement = BandBlock | BandGap
const trackContainer = ref<HTMLDivElement | null>(null)
const PX_PER_SEC = 80
const MIN_BLOCK_WIDTH = 40

// Responsive: largeur visible = largeur du conteneur ou prop
const localVisibleWidth = ref(0)
const computedVisibleWidth = computed(() => {
  if (typeof props.visibleWidth === 'number' && props.visibleWidth > 0) return props.visibleWidth
  return localVisibleWidth.value
})
onMounted(() => {
  if (typeof props.visibleWidth !== 'number') {
    const updateWidth = () => {
      if (trackContainer.value) localVisibleWidth.value = trackContainer.value.offsetWidth
    }

    // ResizeObserver reference (may be assigned in nextTick)
    let ro: ResizeObserver | null = null

    // Ensure DOM painted before measuring and attach a ResizeObserver when possible
    nextTick(() => {
      updateWidth()
      if (typeof ResizeObserver !== 'undefined' && trackContainer.value) {
        ro = new ResizeObserver(updateWidth)
        ro.observe(trackContainer.value)
      }
    })

    // Fallback to window resize for environments without ResizeObserver
    window.addEventListener('resize', updateWidth)

    // Clean up listeners and observer
    onBeforeUnmount(() => {
      window.removeEventListener('resize', updateWidth)
      if (ro) ro.disconnect()
    })
  }
})

const rythmoTextStyle = computed(() => ({
  width: `${bandWidth.value}px`,
  transform: `translateX(-${smoothScroll.value}px)`,
  // plus de paddingLeft, tout est positionné en absolu
}))

// --- Redimensionnement des blocs ---
const resizingIdx = ref<number | null>(null)
const resizeMode = ref<'left' | 'right' | null>(null)
const resizeStartX = ref(0)
const resizeStartTime = ref(0)
const resizeStartEnd = ref(0)

// --- Déplacement des blocs ---
const movingIdx = ref<number | null>(null)
const moveStartX = ref(0)
const moveStartY = ref(0)
const moveStartTime = ref(0)
const moveCurrentTargetLine = ref<number | null>(null)

const shouldRenderDragOverlay = computed(() => {
  const dragState = props.dragState
  if (!dragState || !dragState.active) return false
  if (dragState.targetLineNumber !== props.lineNumber) return false
  // Si on reste sur la ligne source, c'est le bloc réel qui se déplace
  if (dragState.sourceLineNumber === props.lineNumber) return false
  return true
})

const dragOverlayWidth = computed(() => {
  if (!props.dragState || !props.dragState.active) return 0
  return Math.max(MIN_BLOCK_WIDTH, props.dragState.duration * PX_PER_SEC)
})

const dragOverlayStyle = computed<CSSProperties>(() => {
  if (!shouldRenderDragOverlay.value || !props.dragState) return {}

  const x = props.dragState.newStart * PX_PER_SEC + computedVisibleWidth.value / 2
  return {
    left: `${x}px`,
    width: `${dragOverlayWidth.value}px`,
    height: '100%',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    overflow: 'visible',
    flexShrink: 0,
    borderRadius: '4px',
    margin: '0',
    position: 'absolute',
    pointerEvents: 'none',
    zIndex: 5,
  }
})

const dragOverlayText = computed(() => props.dragState?.timecode.text || '')
const dragOverlayCharacter = computed(() => props.dragState?.timecode.character || null)
const dragOverlayCharacterVisible = computed(() => {
  if (!props.dragState || !dragOverlayCharacter.value) return false
  if (props.dragState.timecode.show_character === false) return false
  return dragOverlayWidth.value >= 100
})

const dragOverlayTextStyle = computed(() => {
  if (!props.dragState) return {}
  return computeDistortStyle(dragOverlayText.value, dragOverlayWidth.value)
})

// Variables pour la preview de déplacement

// Données locales pour le redimensionnement en temps réel
const localTimecodes = ref<Timecode[]>([])

// Synchronise les timecodes locaux avec les props
watch(() => props.timecodes, (newTimecodes) => {
  if (resizingIdx.value === null && movingIdx.value === null) {
    localTimecodes.value = [...newTimecodes]
  }
}, { immediate: true, deep: true })

// Utilise les timecodes locaux pendant le redimensionnement, sinon les props
const effectiveTimecodes = computed(() => {
  if (resizingIdx.value !== null || movingIdx.value !== null) {
    return localTimecodes.value
  }
  return props.timecodes
})

const totalDuration = computed(() => {
  if (props.videoDuration && props.videoDuration > 0) return props.videoDuration
  if (!effectiveTimecodes.value.length) return 1
  return effectiveTimecodes.value[effectiveTimecodes.value.length - 1].end
})

const bandWidth = computed(() => totalDuration.value * PX_PER_SEC)

function getBlockWidth(idx: number) {
  const tc = effectiveTimecodes.value[idx]
  return Math.max(MIN_BLOCK_WIDTH, (tc.end - tc.start) * PX_PER_SEC)
}

// Nouvelle fonction : calcule la position x (en px) d'un timecode
function getBlockX(idx: number) {
  const tc = effectiveTimecodes.value[idx]
  return tc.start * PX_PER_SEC + computedVisibleWidth.value / 2
}

function getTimecodeCharacter(idx: number): Character | null {
  const tc = effectiveTimecodes.value[idx]
  return tc?.character || null
}

function shouldShowCharacter(idx: number): boolean {
  const tc = effectiveTimecodes.value[idx]
  return !!(tc?.character && tc?.show_character !== false)
}

// Fonction pour ouvrir/fermer le dropdown de personnages
function toggleCharacterDropdown(idx: number) {
  if (characterDropdownIdx.value === idx) {
    characterDropdownIdx.value = null
  } else {
    characterDropdownIdx.value = idx
  }
}// Fonction pour changer le personnage d'un timecode
function changeTimecodeCharacter(idx: number, characterId: number | null) {
  const tc = effectiveTimecodes.value[idx]
  if (!tc || !tc.id) return

  // Émettre l'événement pour mettre à jour le personnage du timecode
  emit('update-timecode-character', {
    timecode: tc,
    characterId: characterId
  })

  // Fermer le dropdown
  characterDropdownIdx.value = null
}

function toggleCharacterDisplay(idx: number) {
  const tc = effectiveTimecodes.value[idx]
  if (!tc || !tc.id) return

  const newShowCharacter = !shouldShowCharacter(idx)

  // Émettre l'événement pour mettre à jour le timecode
  emit('update-timecode-show-character', {
    timecode: tc,
    showCharacter: newShowCharacter
  })
}

function cloneTimecode(tc: Timecode): Timecode {
  return {
    ...tc,
    character: tc.character ? { ...tc.character } : tc.character,
  }
}

function getContrastColor(backgroundColor: string): string {
  // Convertir la couleur hex en RGB
  const hex = backgroundColor.replace('#', '')
  const r = parseInt(hex.substr(0, 2), 16)
  const g = parseInt(hex.substr(2, 2), 16)
  const b = parseInt(hex.substr(4, 2), 16)

  // Calculer la luminance
  const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255

  // Retourner blanc ou noir selon la luminance
  return luminance > 0.5 ? '#000000' : '#FFFFFF'
}

function getAbsoluteBlockStyle(el: BandBlock) {
  return {
    left: el.x + 'px',
    width: el.width + 'px',
    height: '100%',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    overflow: 'visible',
    flexShrink: 0,
    borderRadius: '4px',
    margin: '0',
    position: 'absolute',
  }
}

const activeIdx = computed(() => {
  const OFFSET = -0.2 // décalage en secondes
  const t = (props.currentTime ?? 0) + OFFSET
  return effectiveTimecodes.value.findIndex((tc) => t >= tc.start && t < tc.end)
})

function computeDistortStyle(text: string, width: number, fontSize = '2.1rem'): CSSProperties {
  const span = document.createElement('span')
  span.style.visibility = 'hidden'
  span.style.position = 'absolute'
  span.style.whiteSpace = 'pre'
  span.style.fontSize = fontSize
  span.style.fontFamily = 'inherit'
  span.innerText = text
  document.body.appendChild(span)
  const textWidth = span.offsetWidth || 1
  document.body.removeChild(span)
  const scaleX = width / textWidth

  return {
    transform: `scaleX(${scaleX})`,
    width: '100%',
  }
}

function getDistortStyle(idx: number) {
  const width = getBlockWidth(idx)
  const text = effectiveTimecodes.value[idx].text || ''
  return computeDistortStyle(text, width)
}

function isBlockBeingDragged(idx: number) {
  return movingIdx.value === idx
}

function isBlockMovingAway(idx: number) {
  if (!props.dragState || !props.dragState.active) return false
  if (movingIdx.value !== idx) return false
  return props.dragState.targetLineNumber !== props.dragState.sourceLineNumber
}

function getGapWidth(start: number, end: number) {
  return Math.max(10, (end - start) * PX_PER_SEC)
}

function getGapX(start: number) {
  return start * PX_PER_SEC + computedVisibleWidth.value / 2
}

function getAbsoluteGapStyle(el: BandGap) {
  return {
    left: el.x + 'px',
    width: el.width + 'px',
    height: '100%',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    overflow: 'hidden',
    flexShrink: 0,
    borderRadius: '4px',
    margin: '0',
    position: 'absolute',
  }
}
// Génère la liste des éléments (blocs et gaps) avec coordonnées précises
const bandElements = computed<BandElement[]>(() => {
  const arr: BandElement[] = []
  const tcs = effectiveTimecodes.value
  if (!tcs.length) return arr
  // Gap avant le premier timecode
  if (tcs[0].start > 0.2) {
    const x = getGapX(0)
    const width = getGapWidth(0, tcs[0].start)
    arr.push({
      type: 'gap',
      x,
      width,
      label: tcs[0].start >= 1 ? tcs[0].start.toFixed(2) + 's' : '',
    })
  }
  // Blocs + gaps intermédiaires
  for (let i = 0; i < tcs.length; i++) {
    // Bloc
    const x = getBlockX(i)
    const width = getBlockWidth(i)
    arr.push({ type: 'block', x, width, text: tcs[i].text, tcIdx: i })
    // Gap après ce bloc (si trou)
    if (i < tcs.length - 1 && tcs[i].end < tcs[i + 1].start) {
      const gapX = getGapX(tcs[i].end)
      const gapWidth = getGapWidth(tcs[i].end, tcs[i + 1].start)
      arr.push({
        type: 'gap',
        x: gapX,
        width: gapWidth,
        label:
          tcs[i + 1].start - tcs[i].end >= 1
            ? (tcs[i + 1].start - tcs[i].end).toFixed(2) + 's'
            : '',
      })
    }
  }
  // Gap après le dernier timecode
  const last = tcs[tcs.length - 1]
  if (Math.round((totalDuration.value - last.end) * 100) / 100 >= 0.5) {
    const x = getGapX(last.end)
    const width = getGapWidth(last.end, totalDuration.value)
    arr.push({ type: 'gap', x, width, label: (totalDuration.value - last.end).toFixed(2) + 's' })
  }
  return arr
})

const noTransition = ref(false)
const targetScroll = computed(() => {
  const maxScroll = Math.max(0, bandWidth.value + computedVisibleWidth.value)
  // Si la bande est plus courte que la fenêtre, scroll=0
  if (bandWidth.value <= computedVisibleWidth.value) return 0
  // Sinon, scroll jusqu'à la toute fin
  return Math.min(props.currentTime * PX_PER_SEC, maxScroll)
})
const instantRef = computed(() => {
  if (isRef(props.instant)) return props.instant.value
  return !!props.instant
})
const smoothScroll = useSmoothScroll(() => targetScroll.value, instantRef)

// Désactive la transition si le scroll saute brutalement (seek, pause/play)
const emit = defineEmits<{
  (e: 'seek', time: number): void
  (e: 'update-timecode', payload: { timecode: Timecode; text: string }): void
  (e: 'update-timecode-bounds', payload: { timecode: Timecode; start: number; end: number }): void
  (e: 'move-timecode', payload: { timecode: Timecode; newStart: number; newLineNumber: number }): void
  (e: 'update-timecode-show-character', payload: { timecode: Timecode; showCharacter: boolean }): void
  (e: 'update-timecode-character', payload: { timecode: Timecode; characterId: number | null }): void
  (e: 'delete-timecode', payload: { timecode: Timecode }): void
  (e: 'add-timecode'): void
  (e: 'reload-lines', payload: { sourceLineNumber: number; targetLineNumber: number }): void
  (e: 'dragging-start', payload: DragStartPayload): void
  (e: 'dragging-update', payload: DragUpdatePayload): void
  (e: 'dragging-end'): void
}>()
const onBlockClick = (idx: number) => {
  if (effectiveTimecodes.value[idx]) {
    emit('seek', effectiveTimecodes.value[idx].start)
  }
}

const onDeleteClick = (idx: number) => {
  const timecode = effectiveTimecodes.value[idx]
  if (timecode) {
    emit('delete-timecode', { timecode })
  }
}

watch(smoothScroll, (val, oldVal) => {
  if (Math.abs(val - oldVal) > 40) {
    noTransition.value = true
    setTimeout(() => {
      noTransition.value = false
    }, 40)
  }
})

// --- Edition du texte d'un bloc ---
const editingIdx = ref<number | null>(null)
const editingText = ref('')

// Variables pour la gestion du dropdown de personnages
const hoveredCharacterIdx = ref<number | null>(null)
const characterDropdownIdx = ref<number | null>(null)



const editInput = ref<HTMLInputElement | null>(null)
function setEditInputRef(el: Element | ComponentPublicInstance | null) {
  // On ne garde la ref que si c'est l'input actuellement édité
  if (el === null) {
    editInput.value = null
    return
  }

  // Détecte un input DOM directement ou récupère le $el d'un composant
  let inputEl: HTMLInputElement | null = null
  if (el instanceof HTMLInputElement) {
    inputEl = el
  } else if (el && '$el' in el) {
    // Utilise le type ComponentPublicInstance déjà importé pour accéder à $el
    const maybeEl = (el as ComponentPublicInstance).$el
    if (maybeEl instanceof HTMLInputElement) {
      inputEl = maybeEl
    }
  }

  if (inputEl && editingIdx.value !== null) {
    editInput.value = inputEl
    nextTick(() => {
      editInput.value?.focus()
    })
  } else {
    // Si ce n'est pas l'input attendu, on nettoie la ref
    editInput.value = null
  }
}

function onBlockDblClick(idx: number, text: string) {
  editingIdx.value = idx
  editingText.value = text
  nextTick(() => {
    if (editInput.value) editInput.value.focus()
  })
}

function finishEdit() {
  if (editingIdx.value !== null && editingText.value.trim() !== '') {
    const timecode = effectiveTimecodes.value[editingIdx.value]
    if (timecode) {
      emit('update-timecode', { timecode, text: editingText.value })
    }
  }
  editingIdx.value = null
  editingText.value = ''
}

function cancelEdit() {
  editingIdx.value = null
  editingText.value = ''
}



// --- Fonctions de redimensionnement ---
function onResizeStart(idx: number, mode: 'left' | 'right', event: MouseEvent) {
  event.stopPropagation()
  event.preventDefault()

  resizingIdx.value = idx
  resizeMode.value = mode
  resizeStartX.value = event.clientX

  const timecode = effectiveTimecodes.value[idx]
  resizeStartTime.value = timecode.start
  resizeStartEnd.value = timecode.end

  document.addEventListener('mousemove', onResizeMove)
  document.addEventListener('mouseup', onResizeEnd)
  document.body.style.cursor = 'ew-resize'
}

function onResizeMove(event: MouseEvent) {
  if (resizingIdx.value === null || !resizeMode.value) return

  const deltaX = event.clientX - resizeStartX.value
  const deltaTime = deltaX / PX_PER_SEC

  let newStart = resizeStartTime.value
  let newEnd = resizeStartEnd.value

  if (resizeMode.value === 'left') {
    newStart = Math.max(0, resizeStartTime.value + deltaTime)
    // S'assurer que le début ne dépasse pas la fin
    if (newStart >= newEnd) {
      newStart = newEnd - 0.1
    }
  } else if (resizeMode.value === 'right') {
    newEnd = Math.max(resizeStartTime.value + 0.1, resizeStartEnd.value + deltaTime)
  }

  // Mise à jour locale immédiate pour le feedback visuel seulement
  const newTimecodes = [...localTimecodes.value]
  newTimecodes[resizingIdx.value] = {
    ...newTimecodes[resizingIdx.value],
    start: newStart,
    end: newEnd
  }
  localTimecodes.value = newTimecodes
}

function onResizeEnd() {
  if (resizingIdx.value === null || !resizeMode.value) return

  // Calcul final des nouvelles valeurs
  const finalTimecode = localTimecodes.value[resizingIdx.value]
  const originalTimecode = props.timecodes[resizingIdx.value]

  // Émission de l'événement de mise à jour seulement à la fin
  emit('update-timecode-bounds', {
    timecode: originalTimecode,
    start: finalTimecode.start,
    end: finalTimecode.end
  })

  resizingIdx.value = null
  resizeMode.value = null
  document.removeEventListener('mousemove', onResizeMove)
  document.removeEventListener('mouseup', onResizeEnd)
  document.body.style.cursor = ''

  // Resynchronise avec les props après le redimensionnement
  localTimecodes.value = [...props.timecodes]
}

// Gestionnaire pour fermer le dropdown quand on clique ailleurs
function handleGlobalClick() {
  if (characterDropdownIdx.value !== null) {
    characterDropdownIdx.value = null
  }
}

onMounted(() => {
  document.addEventListener('click', handleGlobalClick)
})

// Nettoyer les événements en cas de démontage du composant
onBeforeUnmount(() => {
  document.removeEventListener('mousemove', onResizeMove)
  document.removeEventListener('mouseup', onResizeEnd)
  document.removeEventListener('mousemove', onMoveMove)
  document.removeEventListener('mouseup', onMoveEnd)
  document.removeEventListener('click', handleGlobalClick)
  document.body.style.cursor = ''
})

// --- Fonctions de déplacement ---
function onMoveStart(idx: number, event: MouseEvent) {
  event.stopPropagation()
  event.preventDefault()

  movingIdx.value = idx
  moveStartX.value = event.clientX
  moveStartY.value = event.clientY

  const timecode = effectiveTimecodes.value[idx]
  moveStartTime.value = timecode.start
  moveCurrentTargetLine.value = props.lineNumber

  // Crée une copie locale pour les déplacements
  localTimecodes.value = [...props.timecodes]

  emit('dragging-start', {
    timecode: cloneTimecode(timecode),
    sourceLineNumber: props.lineNumber,
    index: idx
  } satisfies DragStartPayload)

  document.addEventListener('mousemove', onMoveMove)
  document.addEventListener('mouseup', onMoveEnd)
  document.body.style.cursor = 'move'
}

function onMoveMove(event: MouseEvent) {
  if (movingIdx.value === null) return

  const deltaX = event.clientX - moveStartX.value
  const deltaTime = deltaX / PX_PER_SEC

  // Calcul de la nouvelle position temporelle
  const newStart = Math.max(0, moveStartTime.value + deltaTime)

  // Calcul de la ligne cible basée sur la position Y de la souris
  const deltaY = event.clientY - moveStartY.value
  const lineHeight = 48 // Hauteur approximative d'une ligne rythmo (3rem = 48px)
  const lineOffset = Math.round(deltaY / lineHeight)
  const targetLine = Math.max(1, Math.min(6, props.lineNumber + lineOffset))

  moveCurrentTargetLine.value = targetLine

  // Mise à jour locale pour feedback visuel
  const newTimecodes = [...localTimecodes.value]
  if (newTimecodes[movingIdx.value]) {
    const current = newTimecodes[movingIdx.value]
    const duration = current.end - current.start
    newTimecodes[movingIdx.value] = {
      ...current,
      start: newStart,
      end: newStart + duration,
    }
    localTimecodes.value = newTimecodes
  }

  const movingTimecode = props.timecodes[movingIdx.value]
  emit('dragging-update', {
    timecodeId: movingTimecode?.id,
    index: movingIdx.value,
    newStart,
    targetLineNumber: targetLine,
    pointerX: event.clientX,
    pointerY: event.clientY
  } satisfies DragUpdatePayload)
}

function onMoveEnd() {
  if (movingIdx.value === null) return

  const finalTimecode = localTimecodes.value[movingIdx.value]
  const originalTimecode = props.timecodes[movingIdx.value]
  const sourceLineNumber = props.lineNumber
  const targetLineNumber = moveCurrentTargetLine.value || props.lineNumber

  if (finalTimecode && originalTimecode) {
    // Émission de l'événement de déplacement
    emit('move-timecode', {
      timecode: originalTimecode,
      newStart: finalTimecode.start,
      newLineNumber: targetLineNumber
    })

    // Demander le rechargement des lignes concernées pour éviter les bugs visuels
    emit('reload-lines', {
      sourceLineNumber: sourceLineNumber,
      targetLineNumber: targetLineNumber
    })
  }

  movingIdx.value = null
  moveCurrentTargetLine.value = null

  document.removeEventListener('mousemove', onMoveMove)
  document.removeEventListener('mouseup', onMoveEnd)
  document.body.style.cursor = ''

  // Resynchronise avec les props après le déplacement
  localTimecodes.value = [...props.timecodes]

  emit('dragging-end')
}
</script>

<style scoped>
.rythmo-ticks {
  position: absolute;
  left: 0;
  bottom: 0;
  width: 100%;
  height: 2rem;
  pointer-events: none;
  z-index: 1;

}
.rythmo-tick {
  position: absolute;
  bottom: 0%;
  width: 2px;
  height: 45%;
  background: #aaa;
  opacity: 0.45;
  border-radius: 2px;
  z-index: 1;
  pointer-events: none;
  transition: none;
}
.rythmo-tick.tick-second {
  width: 3px;
  height: 80%;
  background: #8455f6;
  opacity: 0.85;
  bottom: 0;
}
.scene-change-bar {
  position: absolute;
  bottom: -5%;
  width: 5px;
  height: 110%;
  background: #657390;
  opacity: 0.95;
  border-radius: 2px;
  z-index: 3;
  box-shadow: 0 0 8px #657390;
  pointer-events: none;
  transition: none;
}
.rythmo-debug {
  background: #222;
  color: #fff;
  font-size: 0.9rem;
  margin-top: 0.5rem;
  padding: 0.5rem 1rem;
  width: fit-content;
  border-radius: 6px;
  opacity: 0.8;
  word-break: break-all;
}
.rythmo-band {
  width: 100%;
  overflow: visible;
  background: #1f2937;
  border-radius: 8px;
  /* margin-top: 0.75rem; */
  min-height: 3rem;
  display: flex;
  align-items: center;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  border: 1px solid rgba(59, 130, 246, 0.2);
}
.rythmo-track-container {
  position: relative;
  height: 3rem;
  overflow: visible;
}
.rythmo-text {
  /* plus de flex, tout est positionné en absolu */
  position: absolute;
  left: 0;
  top: 0;
  width: 100%;
  height: 3rem;
  font-size: 1.1rem;
  color: #fff;
  transition: transform 0.18s cubic-bezier(0.4, 2, 0.6, 1);
}
.rythmo-content {
  position: absolute;
  left: 0;
  top: 0;
  width: 100%;
  height: 3rem;
  will-change: transform;
  transition: transform 0.18s cubic-bezier(0.4, 2, 0.6, 1);
}
.rythmo-content.no-transition {
  transition: none;
}
.rythmo-text > div {
  position: absolute;
  left: 0;
  top: 0;
  width: 100%;
  height: 3rem;
  z-index: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: visible;
  height: 100%;
  flex-shrink: 0;
  border-radius: 4px;
  margin: 0;
}
.rythmo-block {
  position: relative;
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  border: 1px solid rgba(59, 130, 246, 0.3);
  z-index: 4; /* Blocs de texte toujours devant les gaps */
}
.rythmo-block.active {
  background: linear-gradient(135deg, #10b981, #059669);
  border: 1px solid #10b981;
  box-shadow: 0 0 12px rgba(16, 185, 129, 0.4);
  z-index: 5; /* Bloc actif encore plus devant */
}
.rythmo-block-gap {
  background: var(--agfa-gray) !important;
  opacity: 0.3;
  border: 1px solid rgba(75, 85, 99, 0.3);
  z-index: 1; /* Gaps toujours derrière les blocs de texte */
}
.rythmo-text span,
.distort-text {
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  margin: 0;
  opacity: 0.9;
  background: none;
  border-radius: 3px;
  font-size: 1.8rem;
  font-weight: 600;
  overflow: visible;
  flex-grow: 1;
  width: 100%;
  white-space: pre;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.8);
}
.rythmo-block.active .distort-text {
  opacity: 1;
  color: #ffffff;
  /* font-weight: bold; */
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.8);
}

.character-tag {
  position: absolute;
  top: 2px;
  right: 101%;
  font-size: 0.7rem;
  font-weight: bold;
  padding: 2px 6px;
  border-radius: 4px;
  z-index: 10;
  line-height: 1;
  white-space: nowrap;
  text-shadow: none;
  opacity: 0.9;
  max-width: calc(100% - 8px);
  overflow: hidden;
  text-overflow: ellipsis;
  display: flex;
  align-items: center;
  gap: 4px;
}

.character-toggle {
  background: rgba(255, 255, 255, 0.3);
  border: none;
  border-radius: 50%;
  width: 14px;
  height: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  font-weight: bold;
  cursor: pointer;
  transition: all 0.2s ease;
  color: inherit;
}

.character-toggle:hover {
  background: rgba(255, 255, 255, 0.6);
  transform: scale(1.1);
}

.character-show-btn {
  position: absolute;
  top: 2px;
  right: 2px;
  background: rgba(0, 0, 0, 0.3);
  border: 1px solid;
  border-radius: 50%;
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  cursor: pointer;
  transition: all 0.2s ease;
  z-index: 10;
}

.character-show-btn:hover {
  background: rgba(0, 0, 0, 0.6);
  transform: scale(1.1);
}

.delete-timecode-btn {
  position: absolute;
  top: 2px;
  left: 2px;
  background: transparent;
  color: white;
  border: none;
  border-radius: 4px;
  width: 20px;
  height: 20px;
  display: none; /* Caché par défaut */
  align-items: center;
  justify-content: center;
  font-size: 12px;
  cursor: pointer;
  transition: all 0.3s ease;
  z-index: 15;
}

.rythmo-block:hover .delete-timecode-btn {
  display: flex; /* Affiché au hover du bloc */
}

.delete-timecode-btn:hover {
  color: #fca5a5;
  background: rgba(107, 114, 128, 0.6);
  transform: scale(1.1);
}
.rythmo-cursor {
  position: absolute;
  top: 0;
  left: 50%;
  width: 4px;
  height: 100%;
  background: linear-gradient(to bottom, #ffffff, #e5e7eb);
  border-radius: 2px;
  box-shadow:
    0 0 8px rgba(255, 255, 255, 0.6),
    0 0 16px rgba(0, 0, 0, 0.8);
  z-index: 2;
  transition: none;
}
.gap-label {
  font-size: 0.875rem;
  color: #9ca3af;
  font-style: italic;
  user-select: none;
  opacity: 0.6 !important;
  font-weight: 500;
}
/* Permet à l'input d'édition de dépasser sur la droite si le texte est long */
.rythmo-edit-input {
  min-width: 0;
  width: fit-content;
  white-space: pre;
  overflow: visible;
  z-index: 10;
}

/* Zones de redimensionnement */
.resize-handle {
  position: absolute;
  top: 0;
  width: 8px;
  height: 100%;
  cursor: ew-resize;
  z-index: 15;
  background: transparent;
  transition: background 0.2s;
}

.resize-handle:hover {
  background: rgba(255, 255, 255, 0.6);
}

.resize-left {
  left: 0;
  border-radius: 4px 0 0 4px;
}

.resize-right {
  right: 0;
  border-radius: 0 4px 4px 0;
}

/* Zone de déplacement */
.move-handle {
  position: absolute;
  bottom: 0;
  left: 8px;
  right: 8px;
  height: 8px;
  cursor: move;
  z-index: 15;
  background: transparent;
  transition: background 0.2s;
  border-radius: 0 0 4px 4px;
}

.move-handle:hover {
  background: rgba(255, 255, 255, 0.6);
}



/* Overlay avec informations de ligne */
.line-overlay {
  position: absolute;
  top: 0;
  right: 0;
  height: 100%;
  display: flex;
  align-items: center;
  padding-right: 8px;
  pointer-events: none;
  z-index: 5;
}

.line-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  pointer-events: auto;
}

.line-number-badge {
  display: flex;
  align-items: center;
  justify-content: center;
  width: fit-content;
  height: fit-content;
  padding: 0.25rem 0.5rem;
  background: rgba(132, 85, 246, 0.8);
  color: white;
  border-radius: 4px;
  font-weight: 600;
  font-size: 0.75rem;
  backdrop-filter: blur(4px);
}

.add-timecode-btn {
  width: 1.5rem;
  height: 1.5rem;
  background: rgba(56, 65, 82, 0.9);
  color: white;
  border: 1px solid rgba(132, 85, 246, 0.8);
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.75rem;
  font-weight: bold;
  transition: all 0.2s;
  backdrop-filter: blur(4px);
}

.add-timecode-btn:hover {
  background: rgba(132, 85, 246, 0.9);
  transform: scale(1.1);
}

.drag-overlay {
  pointer-events: none;
  background: linear-gradient(135deg, #6366f1, #4f46e5);
  border: 1px solid rgba(99, 102, 241, 0.4);
  box-shadow: 0 6px 18px rgba(0, 0, 0, 0.25);
  opacity: 0.95;
}

.rythmo-block.is-dragged-block {
  cursor: grabbing;
  box-shadow: 0 8px 18px rgba(59, 130, 246, 0.35);
  transition: none;
}

.rythmo-block.is-dragged-away {
  opacity: 0.2;
}

/* Nouveaux styles pour le dropdown de personnages */
.character-actions {
  display: flex;
  align-items: center;
  gap: 2px;
  margin-left: 4px;
  z-index: 20;
  position: relative;
}

.character-dropdown-btn {
  background: rgba(255, 255, 255, 0.4);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 3px;
  width: 16px;
  height: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  cursor: pointer;
  transition: all 0.2s ease;
  color: inherit;
  font-weight: bold;
}

.character-dropdown-btn:hover {
  background: rgba(255, 255, 255, 0.5);
  transform: scale(1.1);
}

.character-dropdown {
  position: absolute;
  bottom: 100%;
  right: 100%;
  z-index: 1000;
  margin-bottom: 6px;
  margin-right: -8px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
  border-radius: 8px;
  overflow: hidden;
  backdrop-filter: blur(12px);
  border: 1px solid rgba(132, 85, 246, 0.2);
  animation: fadeInUp 0.2s ease-out;
}

.character-dropdown-content {
  background: linear-gradient(135deg, rgba(30, 35, 45, 0.95), rgba(42, 48, 60, 0.95));
  min-width: 120px;
  max-width: 180px;
}

.character-option {
  padding: 10px 14px;
  cursor: pointer;
  font-size: 0.8rem;
  font-weight: 600;
  transition: all 0.2s ease;
  border-radius: 4px;
  margin: 2px 4px;
  position: relative;
  overflow: hidden;
}

.character-option:hover {
  transform: scale(1.02);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

/* Animation pour les actions de personnage */
.character-tag {
  transition: all 0.2s ease;
}

.character-tag:hover {
  transform: scale(1.02);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.character-tag-hovered {
  transform: scale(1.02);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

/* Animation pour le dropdown */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(8px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}
</style>
