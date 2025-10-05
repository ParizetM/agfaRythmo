<template>
  <div class="rythmo-band" @mouseenter="isHovered = true" @mouseleave="isHovered = false" @click="onBandClick">
    <!-- Triangle de sélection de ligne -->
    <div
      v-if="props.selectedLine === props.lineNumber"
      class="line-selector-triangle"
      :title="`Ligne ${props.lineNumber} sélectionnée`"
    >
      ▶
    </div>

    <div class="rythmo-track-container" ref="trackContainer" :style="{ width: `${bandWidth}px` }">
      <div
        class="rythmo-content"
        :class="{ 'no-transition': noTransition }"
        :style="rythmoTextStyle"
      >
        <div class="rythmo-text">
          <template v-if="bandElements.length">
            <template v-for="(el, idx) in bandElements" :key="getElementKey(el, idx)">
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
                      color: getContrastColor(getTimecodeCharacter(el.tcIdx)?.color || '#8455F6'),
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
                          color: getContrastColor(character.color),
                        }"
                        @click="changeTimecodeCharacter(el.tcIdx, character.id)"
                      >
                        {{ character.name }}
                      </div>
                    </div>
                  </div>

                  <!-- Bouton pour afficher le personnage quand il est masqué -->
                  <button
                    v-else-if="
                      !shouldShowCharacter(el.tcIdx) &&
                      getTimecodeCharacter(el.tcIdx) &&
                      getBlockWidth(el.tcIdx) >= 50 &&
                      isHovered
                    "
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
                  <template v-if="hasSeparator(el.text)">
                    <!-- Texte avec séparateur(s) -->
                    <div class="split-text-container">
                      <template v-for="(segment, segIdx) in getTextSegments(el.tcIdx)" :key="`segment-${el.tcIdx}-${segIdx}`">
                        <div
                          class="text-segment-wrapper"
                          :style="{ flex: getSegmentFlex(el.tcIdx, segIdx) }"
                        >
                          <span
                            class="distort-text"
                            :style="{
                              ...getSegmentDistortStyle(el.tcIdx, segIdx),
                              color: getTimecodeTextColor(el.tcIdx),
                            }"
                          >
                            {{ segment }}
                          </span>
                        </div>
                        <div
                          v-if="segIdx < getTextSegments(el.tcIdx).length - 1"
                          class="text-separator"
                          :class="{ 'separator-dragging': resizingSeparatorIdx === el.tcIdx && resizingSeparatorSubIdx === segIdx }"
                          @mousedown="onSeparatorResizeStart(el.tcIdx, segIdx, $event)"
                        >
                          <div class="separator-line"></div>
                          <div class="separator-handle"></div>
                        </div>
                      </template>
                    </div>
                  </template>
                  <template v-else>
                    <!-- Texte normal sans séparateur -->
                     <div class="overflow-hidden w-full h-full flex items-center justify-center">
                    <span
                      class="distort-text"
                      :style="{
                        ...getDistortStyle(el.tcIdx),
                        color: getTimecodeTextColor(el.tcIdx),
                      }"
                    >
                      {{ el.text }}
                    </span>
                    </div>
                  </template>
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
              color: getContrastColor(dragOverlayCharacter.color || '#8455F6'),
            }"
          >
            {{ dragOverlayCharacter.name }}
          </div>
          <span
            class="distort-text"
            :style="{
              ...dragOverlayTextStyle,
              color: dragOverlayCharacter?.text_color || (dragOverlayCharacter ? getContrastColor(dragOverlayCharacter.color) : 'inherit'),
            }"
          >
            {{ dragOverlayText }}
          </span>
        </div>

        <!-- Overlay de déplacement des scene changes -->
        <div
          v-if="shouldRenderSceneChangeDragOverlay"
          class="scene-change-drag-overlay"
          :style="sceneChangeDragOverlayStyle"
        ></div>

        <div v-if="isLastLine" class="rythmo-ticks pointer-events-none">
          <template v-for="(tick, tickIdx) in ticks" :key="'tick' + tickIdx">
            <div
              class="rythmo-tick"
              :class="{ 'tick-second': tick.isSecond }"
              :style="getTickStyle(tick)"
            ></div>
          </template>
          <!-- Barres verticales de changement de plan -->
        </div>
        <template v-for="(sceneChange, idx) in sceneChangePositions" :key="'scenechange' + sceneChange.id">
          <div
            class="scene-change-bar"
            :class="{
              'hovered': isSceneChangeHovered(sceneChange.id),
              'dragging': draggingSceneChangeIdx === idx,
              'interactive': props.lineNumber === 1 || props.isLastLine
            }"
            :style="{ left: sceneChange.x + 'px' }"
            @mouseenter="onSceneChangeHover(idx)"
            @mouseleave="onSceneChangeLeave()"
          >
            <!-- Zone de grab visible uniquement sur la ligne 1 -->
            <div
              v-if="props.lineNumber === 1"
              class="scene-change-grab-handle"
              @mousedown="onSceneChangeDragStart(idx, sceneChange, $event)"
              title="Glisser pour déplacer le changement de plan"
            ></div>

            <!-- Bouton de suppression visible au hover uniquement sur la dernière ligne -->
            <button
              v-if="props.isLastLine && isSceneChangeHovered(sceneChange.id)"
              class="scene-change-delete-btn"
              @click="onSceneChangeDelete(sceneChange.id)"
              title="Supprimer le changement de plan"
            >
              🗑️
            </button>
          </div>
        </template>
      </div>
      <div class="rythmo-cursor"></div>

      <!-- Tooltip de redimensionnement - compacte -->
      <div v-if="resizingIdx !== null" class="resize-tooltip" :style="getResizeTooltipStyle()">
        <div class="resize-tooltip-content">
          <span v-if="resizeMode === 'left'" class="resize-time">
            {{ formatTime(localTimecodes[resizingIdx]?.start || 0) }}
          </span>
          <span v-else class="resize-time">
            {{ formatTime(localTimecodes[resizingIdx]?.end || 0) }}
          </span>
        </div>
      </div>

      <!-- Tooltip de déplacement -->
      <div v-if="movingIdx !== null" class="move-tooltip" :style="getMoveTooltipStyle()">
        <div class="move-tooltip-content">
          <div class="move-time">
            {{ formatTime(localTimecodes[movingIdx]?.start || 0) }}
          </div>
          <div class="move-line">→ Ligne {{ moveCurrentTargetLine }}</div>
        </div>
      </div>

      <!-- Tooltip de déplacement des scene changes -->
      <div v-if="props.sceneChangeDragState?.active" class="scene-change-drag-tooltip" :style="getSceneChangeDragTooltipStyle()">
        <div class="scene-change-drag-tooltip-content">
          <div class="scene-change-time">
            {{ formatTime(getSceneChangeDragTime()) }}
          </div>
        </div>
      </div>

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
import { getContrastColor } from '../../utils/colorUtils'
import { useProjectSettingsStore } from '../../stores/projectSettings'

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

interface SceneChange {
  id: number
  timecode: number
  project_id: number
}

interface SceneChangeDragState {
  active: boolean
  sceneChangeId: number
  index: number
  startTime: number
  currentTime: number
  startX: number
  currentX: number
  currentY: number
}

interface SceneChangeHoverState {
  active: boolean
  sceneChangeId: number
  index: number
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
  sceneChanges?: SceneChange[]
  lineNumber: number
  isLastLine: boolean
  dragState?: DragState | null
  sceneChangeDragState?: SceneChangeDragState | null
  sceneChangeHoverState?: SceneChangeHoverState | null
  characters?: Character[]
  selectedLine?: number | null // Ligne actuellement sélectionnée (1-6)
}>()

// Store des paramètres de projet
const settingsStore = useProjectSettingsStore()
const projectSettings = computed(() => settingsStore.settings)

const isHovered = ref(false)

// --- Gestion de la séparation de texte ---
// Map<timecodeIdx, array de flex values pour chaque segment>
const segmentFlexValues = ref<Map<number, number[]>>(new Map())
const resizingSeparatorIdx = ref<number | null>(null)
const resizingSeparatorSubIdx = ref<number | null>(null)
const separatorResizeStartX = ref(0)
const separatorResizeStartFlexes = ref<number[]>([])

// Vérifie si un texte contient le séparateur |
function hasSeparator(text: string): boolean {
  return text.includes('|')
}

// Divise le texte en segments basés sur le séparateur |
function getTextSegments(idx: number): string[] {
  const tc = effectiveTimecodes.value[idx]
  if (!tc) return []
  return tc.text.split('|')
}

// Récupère la valeur flex pour un segment
function getSegmentFlex(idx: number, segmentIdx: number): number {
  const segments = getTextSegments(idx)
  if (!segments.length) return 1

  // Récupère les valeurs flex personnalisées ou utilise des valeurs par défaut
  let flexes = segmentFlexValues.value.get(idx)

  if (!flexes || flexes.length !== segments.length) {
    // Initialise avec des valeurs égales pour tous les segments
    flexes = new Array(segments.length).fill(1)
    segmentFlexValues.value.set(idx, flexes)
  }

  return flexes[segmentIdx] || 1
}

// Calcule le style de distorsion pour un segment
function getSegmentDistortStyle(idx: number, segmentIdx: number): CSSProperties {
  const segments = getTextSegments(idx)
  if (!segments[segmentIdx]) return {}

  const text = segments[segmentIdx]
  const blockWidth = getBlockWidth(idx)
  const totalFlexes = segments.reduce((sum, _, i) => sum + getSegmentFlex(idx, i), 0)
  const segmentFlex = getSegmentFlex(idx, segmentIdx)
  const separatorWidth = 12 // Largeur d'un séparateur
  const totalSeparatorsWidth = (segments.length - 1) * separatorWidth

  // Calcule la largeur disponible pour ce segment
  const availableWidth = ((blockWidth - totalSeparatorsWidth) * segmentFlex) / totalFlexes

  return computeDistortStyle(text, Math.max(20, availableWidth))
}

// Commence le redimensionnement du séparateur
function onSeparatorResizeStart(idx: number, separatorIdx: number, event: MouseEvent) {
  event.stopPropagation()
  event.preventDefault()

  resizingSeparatorIdx.value = idx
  resizingSeparatorSubIdx.value = separatorIdx
  separatorResizeStartX.value = event.clientX

  // Sauvegarde les flex values actuelles
  const segments = getTextSegments(idx)
  separatorResizeStartFlexes.value = []
  for (let i = 0; i < segments.length; i++) {
    separatorResizeStartFlexes.value.push(getSegmentFlex(idx, i))
  }

  document.addEventListener('mousemove', onSeparatorResizeMove)
  document.addEventListener('mouseup', onSeparatorResizeEnd)
  document.body.style.cursor = 'ew-resize'
}

// Met à jour la position du séparateur pendant le drag
function onSeparatorResizeMove(event: MouseEvent) {
  if (resizingSeparatorIdx.value === null || resizingSeparatorSubIdx.value === null) return

  const idx = resizingSeparatorIdx.value
  const sepIdx = resizingSeparatorSubIdx.value
  const blockWidth = getBlockWidth(idx)
  const deltaX = event.clientX - separatorResizeStartX.value

  const segments = getTextSegments(idx)
  const totalSeparatorsWidth = (segments.length - 1) * 12
  const availableWidth = blockWidth - totalSeparatorsWidth

  // Calculer la somme des flex des deux segments adjacents
  const totalFlexStart = separatorResizeStartFlexes.value.reduce((sum, flex) => sum + flex, 0)
  const leftFlexStart = separatorResizeStartFlexes.value[sepIdx]
  const rightFlexStart = separatorResizeStartFlexes.value[sepIdx + 1]
  const adjacentFlexSum = leftFlexStart + rightFlexStart

  // Largeur réelle occupée par les deux segments adjacents au début
  const adjacentWidthStart = (availableWidth * adjacentFlexSum) / totalFlexStart

  // Calculer la nouvelle distribution en pixels
  const leftWidthStart = (adjacentWidthStart * leftFlexStart) / adjacentFlexSum
  const leftWidthNew = Math.max(20, Math.min(adjacentWidthStart - 20, leftWidthStart + deltaX))
  const rightWidthNew = adjacentWidthStart - leftWidthNew

  // Convertir en flex values (proportions)
  const leftFlexNew = (leftWidthNew / adjacentWidthStart) * adjacentFlexSum
  const rightFlexNew = (rightWidthNew / adjacentWidthStart) * adjacentFlexSum

  const newFlexes = [...separatorResizeStartFlexes.value]

  // Ajuste uniquement les deux segments adjacents, les autres restent inchangés
  newFlexes[sepIdx] = Math.max(0.3, leftFlexNew)
  newFlexes[sepIdx + 1] = Math.max(0.3, rightFlexNew)

  segmentFlexValues.value.set(idx, newFlexes)
}

// Termine le redimensionnement du séparateur
function onSeparatorResizeEnd() {
  resizingSeparatorIdx.value = null
  resizingSeparatorSubIdx.value = null

  document.removeEventListener('mousemove', onSeparatorResizeMove)
  document.removeEventListener('mouseup', onSeparatorResizeEnd)
  document.body.style.cursor = ''
}// Calcule les positions X (en px) des changements de plan
const sceneChangePositions = computed(() => {
  if (!props.sceneChanges || !props.sceneChanges.length) return []
  return props.sceneChanges.map((sc) => ({
    id: sc.id,
    timecode: sc.timecode,
    x: sc.timecode * PX_PER_SEC + computedVisibleWidth.value / 2
  }))
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
  fontFamily: projectSettings.value.fontFamily,
  fontSize: `${projectSettings.value.fontSize}rem`,
  // plus de paddingLeft, tout est positionné en absolu
}))

// --- Redimensionnement des blocs ---
const resizingIdx = ref<number | null>(null)
const resizeMode = ref<'left' | 'right' | null>(null)
const resizeStartX = ref(0)
const resizeStartTime = ref(0)
const resizeStartEnd = ref(0)
const resizeMouseX = ref(0)
const resizeMouseY = ref(0)
const resizeFixedY = ref(0) // Position Y fixée à l'apparition de la tooltip

// --- Déplacement des blocs ---
const movingIdx = ref<number | null>(null)
const moveStartX = ref(0)
const moveStartY = ref(0)
const moveStartTime = ref(0)
const moveCurrentTargetLine = ref<number | null>(null)
const moveMouseX = ref(0)
const moveMouseY = ref(0)

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
  const character = dragOverlayCharacter.value

  const baseStyles: CSSProperties = {
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

  // Si un personnage est assigné, appliquer sa couleur
  if (character) {
    return {
      ...baseStyles,
      background: character.color,
      border: `1px solid ${character.color}`,
    }
  }

  return baseStyles
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

// Scene Change Drag Overlay
const shouldRenderSceneChangeDragOverlay = computed(() => {
  return props.sceneChangeDragState?.active === true
})

// Fonction pour vérifier si un scene change est hovered via l'état partagé
const isSceneChangeHovered = (sceneChangeId: number): boolean => {
  return props.sceneChangeHoverState?.active === true &&
         props.sceneChangeHoverState.sceneChangeId === sceneChangeId
}

const sceneChangeDragOverlayStyle = computed<CSSProperties>(() => {
  if (!shouldRenderSceneChangeDragOverlay.value || !props.sceneChangeDragState) {
    return { display: 'none' }
  }

  // Utiliser l'état partagé pour la position
  const currentTime = props.sceneChangeDragState.currentTime
  const x = currentTime * PX_PER_SEC + computedVisibleWidth.value / 2

  return {
    position: 'absolute',
    left: `${x}px`,
    bottom: '-5%',
    width: '5px',
    height: '110%',
    background: '#10b981', // Couleur verte pendant le drag
    opacity: 0.8,
    borderRadius: '2px',
    zIndex: 10,
    boxShadow: '0 0 16px #10b981',
    pointerEvents: 'none',
    transition: 'none',
  }
})

// Variables pour la preview de déplacement

// Données locales pour le redimensionnement en temps réel
const localTimecodes = ref<Timecode[]>([])

// Synchronise les timecodes locaux avec les props
watch(
  () => props.timecodes,
  (newTimecodes) => {
    if (resizingIdx.value === null && movingIdx.value === null) {
      localTimecodes.value = [...newTimecodes]
    }
  },
  { immediate: true, deep: true },
)

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

// Récupère la couleur de texte appropriée pour un timecode
function getTimecodeTextColor(idx: number): string {
  const character = getTimecodeCharacter(idx)
  if (!character) return 'inherit'

  // Si le personnage a une couleur de texte définie, l'utiliser
  if (character.text_color) {
    return character.text_color
  }

  // Sinon, calculer automatiquement le contraste
  return getContrastColor(character.color)
}

// Fonction pour ouvrir/fermer le dropdown de personnages
function toggleCharacterDropdown(idx: number) {
  if (characterDropdownIdx.value === idx) {
    characterDropdownIdx.value = null
  } else {
    characterDropdownIdx.value = idx
  }
} // Fonction pour changer le personnage d'un timecode
function changeTimecodeCharacter(idx: number, characterId: number | null) {
  const tc = effectiveTimecodes.value[idx]
  if (!tc || !tc.id) return

  // Émettre l'événement pour mettre à jour le personnage du timecode
  emit('update-timecode-character', {
    timecode: tc,
    characterId: characterId,
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
    showCharacter: newShowCharacter,
  })
}

function cloneTimecode(tc: Timecode): Timecode {
  return {
    ...tc,
    character: tc.character ? { ...tc.character } : tc.character,
  }
}

// Fonctions de formatage du temps
function formatTime(seconds: number): string {
  const mins = Math.floor(seconds / 60)
  const secs = (seconds % 60).toFixed(2)
  return `${mins}:${secs.padStart(5, '0')}s`
}

function getResizeTooltipStyle(): CSSProperties {
  if (resizingIdx.value === null || !resizeMode.value) return { display: 'none' }

  // Position relative à la souris avec Y fixe
  const containerRect = trackContainer.value?.getBoundingClientRect()
  if (!containerRect) return { display: 'none' }

  const relativeX = resizeMouseX.value - containerRect.left

  return {
    position: 'absolute',
    left: `${relativeX - 30}px`, // Suit la souris en X (largeur tooltip ~60px)
    top: `${resizeFixedY.value}px`, // Y fixe défini à l'apparition
    zIndex: 9999,
    pointerEvents: 'none',
  }
}

function getMoveTooltipStyle(): CSSProperties {
  if (movingIdx.value === null) return { display: 'none' }

  // Position relative à la souris (suit X et Y)
  const containerRect = trackContainer.value?.getBoundingClientRect()
  if (!containerRect) return { display: 'none' }

  const relativeX = moveMouseX.value - containerRect.left
  const relativeY = moveMouseY.value - containerRect.top

  return {
    position: 'absolute',
    left: `${relativeX - 30}px`, // Suit la souris en X (largeur tooltip ~60px)
    top: `${relativeY - 40}px`, // 40px au-dessus de la souris
    zIndex: 9999,
    pointerEvents: 'none',
  }
}

// Tooltip pour les scene changes
function getSceneChangeDragTooltipStyle(): CSSProperties {
  if (!props.sceneChangeDragState?.active) return { display: 'none' }

  // Position relative à la souris (suit X et Y)
  const containerRect = trackContainer.value?.getBoundingClientRect()
  if (!containerRect) return { display: 'none' }

  const relativeX = props.sceneChangeDragState.currentX - containerRect.left
  const relativeY = props.sceneChangeDragState.currentY - containerRect.top

  return {
    position: 'absolute',
    left: `${relativeX - 30}px`, // Suit la souris en X
    top: `${relativeY - 40}px`, // 40px au-dessus de la souris
    zIndex: 9999,
    pointerEvents: 'none',
  }
}

function getSceneChangeDragTime(): number {
  if (!props.sceneChangeDragState?.active) return 0

  // Utiliser le temps actuel de l'état partagé
  return props.sceneChangeDragState.currentTime
}
function getAbsoluteBlockStyle(el: BandBlock): CSSProperties {
  const character = getTimecodeCharacter(el.tcIdx)

  // Styles de base
  const baseStyles: CSSProperties = {
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

  // Si un personnage est assigné, appliquer sa couleur de fond
  if (character) {
    return {
      ...baseStyles,
      background: character.color,
      border: `1px solid ${character.color}`,
    }
  }

  return baseStyles
}

const activeIdx = computed(() => {
  const OFFSET = -0.2 // décalage en secondes
  const t = (props.currentTime ?? 0) + OFFSET
  return effectiveTimecodes.value.findIndex((tc) => t >= tc.start && t < tc.end)
})

function computeDistortStyle(text: string, width: number, fontSize?: string): CSSProperties {
  const actualFontSize = fontSize || `${projectSettings.value.fontSize}rem`
  const span = document.createElement('span')
  span.style.visibility = 'hidden'
  span.style.position = 'absolute'
  span.style.whiteSpace = 'pre'
  span.style.fontSize = actualFontSize
  span.style.fontFamily = projectSettings.value.fontFamily
  span.innerText = text
  document.body.appendChild(span)
  const textWidth = span.offsetWidth || 1
  document.body.removeChild(span)
  const scaleX = (width / textWidth ) * 0.95 // facteur d'ajustement pour éviter le débordement

  return {
    transform: `scaleX(${scaleX})`,
    transformOrigin: 'center center',
    width: '100%',
    WebkitFontSmoothing: 'antialiased',
    MozOsxFontSmoothing: 'grayscale',
    backfaceVisibility: 'hidden',
    willChange: 'transform',
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

// Compteur global pour générer des IDs uniques
let keyCounter = 0

// Génère une clé unique et stable pour chaque élément de la bande
function getElementKey(el: BandElement, idx: number): string {
  if (el.type === 'block') {
    // Pour les blocs, utiliser l'ID du timecode s'il existe, sinon une combinaison unique
    const timecode = effectiveTimecodes.value[el.tcIdx]
    if (timecode?.id) {
      return `block-L${props.lineNumber}-ID${timecode.id}`
    } else {
      // Fallback avec un hash unique basé sur les propriétés du timecode
      const hash = timecode ?
        `${Math.round(timecode.start * 10000)}-${Math.round(timecode.end * 10000)}-${timecode.text?.length || 0}` :
        `fallback-${++keyCounter}`
      return `block-L${props.lineNumber}-TC${el.tcIdx}-H${hash}`
    }
  } else {
    // Pour les gaps, créer une clé basée sur la position exacte
    const hash = `${Math.round(el.x * 10000)}-${Math.round(el.width * 10000)}`
    return `gap-L${props.lineNumber}-P${hash}-I${idx}`
  }
}

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
  (
    e: 'move-timecode',
    payload: { timecode: Timecode; newStart: number; newLineNumber: number },
  ): void
  (
    e: 'update-timecode-show-character',
    payload: { timecode: Timecode; showCharacter: boolean },
  ): void
  (
    e: 'update-timecode-character',
    payload: { timecode: Timecode; characterId: number | null },
  ): void
  (e: 'delete-timecode', payload: { timecode: Timecode }): void
  (e: 'add-timecode'): void
  (e: 'reload-lines', payload: { sourceLineNumber: number; targetLineNumber: number }): void
  (e: 'dragging-start', payload: DragStartPayload): void
  (e: 'dragging-update', payload: DragUpdatePayload): void
  (e: 'dragging-end'): void
  (e: 'line-selected', lineNumber: number): void
  (e: 'update-scene-change', payload: { id: number; newTimecode: number; isPreview: boolean }): void
  (e: 'delete-scene-change', payload: { id: number }): void
  (e: 'scene-change-drag-start', payload: { sceneChangeId: number; index: number; startTime: number; startX: number; startY: number }): void
  (e: 'scene-change-drag-update', payload: { currentTime: number; currentX: number; currentY: number; isPreview: boolean }): void
  (e: 'scene-change-drag-end'): void
  (e: 'scene-change-hover-start', payload: { sceneChangeId: number; index: number }): void
  (e: 'scene-change-hover-end'): void
}>()

// Fonction pour ajuster la position d'un timecode lors du déplacement
function adjustTimecodePosition(
  newStart: number,
  duration: number,
  lineNumber: number,
  excludeTimecodeId?: number
): { start: number; end: number } {
  const MARGIN = 0.1 // Marge de sécurité de 0.1 seconde

  // Récupère tous les timecodes de la même ligne, sauf celui qu'on exclut
  const sameLineTimecodes = props.timecodes
    .filter(tc => tc.line_number === lineNumber && tc.id !== excludeTimecodeId)
    .sort((a, b) => a.start - b.start)

  let adjustedStart = newStart
  const adjustedEnd = adjustedStart + duration

  // Trouve le timecode qui pourrait être en conflit
  const conflictingTimecode = sameLineTimecodes.find(tc =>
    (adjustedStart < tc.end + MARGIN && adjustedEnd > tc.start - MARGIN)
  )

  if (conflictingTimecode) {
    // Se positionner à la suite du timecode en conflit
    adjustedStart = conflictingTimecode.end + MARGIN
  }

  return {
    start: adjustedStart,
    end: adjustedStart + duration
  }
}

// Fonction pour ajuster les bornes lors du redimensionnement
function adjustTimecodeResize(
  currentStart: number,
  newStart: number,
  newEnd: number,
  lineNumber: number,
  excludeTimecodeId?: number
): { start: number; end: number } {
  const MARGIN = 0.1 // Marge de sécurité de 0.1 seconde

  // Récupère tous les timecodes de la même ligne, sauf celui qu'on exclut
  const sameLineTimecodes = props.timecodes
    .filter(tc => tc.line_number === lineNumber && tc.id !== excludeTimecodeId)
    .sort((a, b) => a.start - b.start)

  let adjustedStart = newStart
  let adjustedEnd = newEnd

  // Vérifie les conflits avec les timecodes précédents (quand on étend vers la gauche)
  if (adjustedStart < currentStart) {
    const prevTimecode = sameLineTimecodes
      .filter(tc => tc.end <= currentStart)
      .pop() // Le dernier (le plus proche)

    if (prevTimecode && adjustedStart < prevTimecode.end + MARGIN) {
      adjustedStart = prevTimecode.end + MARGIN
    }
  }

  // Vérifie les conflits avec les timecodes suivants (quand on étend vers la droite)
  if (adjustedEnd > currentStart) {
    const nextTimecode = sameLineTimecodes
      .find(tc => tc.start >= currentStart)

    if (nextTimecode && adjustedEnd > nextTimecode.start - MARGIN) {
      adjustedEnd = nextTimecode.start - MARGIN
    }
  }

  // S'assurer que start < end
  if (adjustedStart >= adjustedEnd) {
    adjustedEnd = adjustedStart + 0.5 // Durée minimale de 0.5s
  }

  return {
    start: adjustedStart,
    end: adjustedEnd
  }
}

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

// Gestionnaire de clic sur la bande pour sélectionner la ligne
const onBandClick = () => {
  // Toujours sélectionner la ligne, peu importe où on clique
  emit('line-selected', props.lineNumber)
}

// --- Fonctions pour l'interaction avec les scene changes ---
function onSceneChangeHover(idx: number) {
  hoveredSceneChangeIdx.value = idx

  // Émettre vers le parent seulement si on est sur la première ou dernière ligne
  if (props.lineNumber === 1 || props.isLastLine) {
    const sceneChange = props.sceneChanges?.[idx]
    if (sceneChange) {
      emit('scene-change-hover-start', {
        sceneChangeId: sceneChange.id,
        index: idx
      })
    }
  }
}

function onSceneChangeLeave() {
  hoveredSceneChangeIdx.value = null

  // Émettre vers le parent seulement si on est sur la première ou dernière ligne
  if (props.lineNumber === 1 || props.isLastLine) {
    emit('scene-change-hover-end')
  }
}

function onSceneChangeDelete(sceneChangeId: number) {
  emit('delete-scene-change', { id: sceneChangeId })
}

function onSceneChangeDragStart(idx: number, sceneChange: { id: number; timecode: number; x: number }, event: MouseEvent) {
  event.stopPropagation()
  event.preventDefault()

  draggingSceneChangeIdx.value = idx
  sceneChangeDragStartX.value = event.clientX
  sceneChangeDragStartTime.value = sceneChange.timecode
  sceneChangeDragMouseX.value = event.clientX
  sceneChangeDragMouseY.value = event.clientY

  // Émettre vers le parent pour initialiser l'état partagé
  emit('scene-change-drag-start', {
    sceneChangeId: sceneChange.id,
    index: idx,
    startTime: sceneChange.timecode,
    startX: event.clientX,
    startY: event.clientY
  })

  document.addEventListener('mousemove', onSceneChangeDragMove)
  document.addEventListener('mouseup', onSceneChangeDragEnd)
  document.body.style.cursor = 'ew-resize'
}

function onSceneChangeDragMove(event: MouseEvent) {
  if (draggingSceneChangeIdx.value === null) return

  sceneChangeDragMouseX.value = event.clientX
  sceneChangeDragMouseY.value = event.clientY

  const deltaX = event.clientX - sceneChangeDragStartX.value
  const deltaTime = deltaX / PX_PER_SEC
  const newTimecode = Math.max(0, sceneChangeDragStartTime.value + deltaTime)

  // Émettre vers le parent pour mettre à jour l'état partagé
  emit('scene-change-drag-update', {
    currentTime: newTimecode,
    currentX: event.clientX,
    currentY: event.clientY,
    isPreview: true
  })
}

function onSceneChangeDragEnd() {
  if (draggingSceneChangeIdx.value === null) return

  const deltaX = sceneChangeDragMouseX.value - sceneChangeDragStartX.value
  const deltaTime = deltaX / PX_PER_SEC
  const newTimecode = Math.max(0, sceneChangeDragStartTime.value + deltaTime)

  // Émettre la mise à jour finale vers l'API
  emit('scene-change-drag-update', {
    currentTime: newTimecode,
    currentX: sceneChangeDragMouseX.value,
    currentY: sceneChangeDragMouseY.value,
    isPreview: false
  })

  // Signaler la fin du drag
  emit('scene-change-drag-end')

  draggingSceneChangeIdx.value = null
  document.removeEventListener('mousemove', onSceneChangeDragMove)
  document.removeEventListener('mouseup', onSceneChangeDragEnd)
  document.body.style.cursor = ''
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

// Variables pour l'interaction avec les scene changes
const hoveredSceneChangeIdx = ref<number | null>(null)
const draggingSceneChangeIdx = ref<number | null>(null)
const sceneChangeDragStartX = ref(0)
const sceneChangeDragStartTime = ref(0)
const sceneChangeDragMouseX = ref(0)
const sceneChangeDragMouseY = ref(0)



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
  resizeMouseX.value = event.clientX
  resizeMouseY.value = event.clientY

  // Calcul de la position Y fixe relative au conteneur
  const containerRect = trackContainer.value?.getBoundingClientRect()
  if (containerRect) {
    resizeFixedY.value = event.clientY - containerRect.top - 40 // 40px au-dessus de la souris
  }

  const timecode = effectiveTimecodes.value[idx]
  resizeStartTime.value = timecode.start
  resizeStartEnd.value = timecode.end

  document.addEventListener('mousemove', onResizeMove)
  document.addEventListener('mouseup', onResizeEnd)
  document.body.style.cursor = 'ew-resize'
}

function onResizeMove(event: MouseEvent) {
  if (resizingIdx.value === null || !resizeMode.value) return

  // Mise à jour de la position X de la souris pour la tooltip (Y reste fixe)
  resizeMouseX.value = event.clientX

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
    end: newEnd,
  }
  localTimecodes.value = newTimecodes
}

function onResizeEnd() {
  if (resizingIdx.value === null || !resizeMode.value) return

  // Calcul final des nouvelles valeurs
  const finalTimecode = localTimecodes.value[resizingIdx.value]
  const originalTimecode = props.timecodes[resizingIdx.value]

  // Ajuster les bornes pour éviter les superpositions
  const adjustedBounds = adjustTimecodeResize(
    originalTimecode.start,
    finalTimecode.start,
    finalTimecode.end,
    props.lineNumber,
    originalTimecode.id
  )

  // Émission de l'événement de mise à jour avec les bornes ajustées
  emit('update-timecode-bounds', {
    timecode: originalTimecode,
    start: adjustedBounds.start,
    end: adjustedBounds.end,
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
  document.removeEventListener('mousemove', onSceneChangeDragMove)
  document.removeEventListener('mouseup', onSceneChangeDragEnd)
  document.removeEventListener('mousemove', onSeparatorResizeMove)
  document.removeEventListener('mouseup', onSeparatorResizeEnd)
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
  moveMouseX.value = event.clientX
  moveMouseY.value = event.clientY

  const timecode = effectiveTimecodes.value[idx]
  moveStartTime.value = timecode.start
  moveCurrentTargetLine.value = props.lineNumber

  // Crée une copie locale pour les déplacements
  localTimecodes.value = [...props.timecodes]

  emit('dragging-start', {
    timecode: cloneTimecode(timecode),
    sourceLineNumber: props.lineNumber,
    index: idx,
  } satisfies DragStartPayload)

  document.addEventListener('mousemove', onMoveMove)
  document.addEventListener('mouseup', onMoveEnd)
  document.body.style.cursor = 'move'
}

function onMoveMove(event: MouseEvent) {
  if (movingIdx.value === null) return

  // Mise à jour de la position de la souris pour la tooltip
  moveMouseX.value = event.clientX
  moveMouseY.value = event.clientY

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
    pointerY: event.clientY,
  } satisfies DragUpdatePayload)
}

function onMoveEnd() {
  if (movingIdx.value === null) return

  const finalTimecode = localTimecodes.value[movingIdx.value]
  const originalTimecode = props.timecodes[movingIdx.value]
  const sourceLineNumber = props.lineNumber
  const targetLineNumber = moveCurrentTargetLine.value || props.lineNumber

  if (finalTimecode && originalTimecode) {
    // Ajuster la position pour éviter les superpositions
    const duration = originalTimecode.end - originalTimecode.start
    const adjustedPosition = adjustTimecodePosition(
      finalTimecode.start,
      duration,
      targetLineNumber,
      originalTimecode.id
    )

    // Émission de l'événement de déplacement avec la position ajustée
    emit('move-timecode', {
      timecode: originalTimecode,
      newStart: adjustedPosition.start,
      newLineNumber: targetLineNumber,
    })

    // Demander le rechargement des lignes concernées pour éviter les bugs visuels
    emit('reload-lines', {
      sourceLineNumber: sourceLineNumber,
      targetLineNumber: targetLineNumber,
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
  background: v-bind('projectSettings.sceneChangeColor');
  opacity: 0.7;
  border-radius: 2px;
  z-index: 3;
  box-shadow: 0 0 8px v-bind('projectSettings.sceneChangeColor');
  pointer-events: none;
  transition: all 0.2s ease;
}

.scene-change-bar.interactive {
  pointer-events: auto;
  cursor: pointer;
}

.scene-change-bar.hovered {
  background: v-bind('projectSettings.sceneChangeColor');
  box-shadow: 0 0 12px v-bind('projectSettings.sceneChangeColor');
  opacity: 1;
  transform: scaleX(1.2);
}

.scene-change-bar.dragging {
  background: #657390;
  box-shadow: none;
  opacity: 0.3;
  z-index: 1;
}

.scene-change-grab-handle {
  position: absolute;
  top: -8px;
  left: -3px;
  width: 11px;
  height: 16px;
  background: rgba(132, 85, 246, 0.8);
  border: 1px solid #8455f6;
  border-radius: 3px;
  cursor: grab;
  opacity: 0;
  transition: all 0.2s ease;
  z-index: 5;
}

.scene-change-bar.hovered .scene-change-grab-handle,
.scene-change-bar.dragging .scene-change-grab-handle {
  opacity: 1;
}

.scene-change-grab-handle:hover {
  background: rgba(132, 85, 246, 1);
  transform: scale(1.1);
}

.scene-change-grab-handle:active {
  cursor: grabbing;
}

.scene-change-delete-btn {
  position: absolute;
  bottom: -10px;
  left: 50%;
  transform: translateX(-50%);
  height: 22px;
  width: 20px;
  background: linear-gradient(180deg, rgba(132,85,246,0.98), rgba(120,60,230,0.95));
  color: #fff;
  border-radius: 100%;
  cursor: pointer;
  font-size: 12px;
  line-height: 1;
  display: flex;
  align-items: center;
  justify-content: center; /* centré */
  z-index: 12;
  box-shadow: 0 6px 14px rgba(132,85,246,0.18);
  transition: transform 0.12s ease, background 0.12s ease, box-shadow 0.12s ease;
}

.scene-change-delete-btn:hover {
  background: rgba(132, 85, 246, 1);
}

.scene-change-drag-overlay {
  position: absolute;
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
  background: v-bind('projectSettings.bandBackgroundColor');
  border-radius: 8px;
  /* margin-top: 0.75rem; */
  min-height: v-bind('projectSettings.bandHeight + "px"');
  display: flex;
  align-items: center;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  border: 1px solid rgba(59, 130, 246, 0.2);
}
.rythmo-track-container {
  position: relative;
  height: v-bind('projectSettings.bandHeight + "px"');
  overflow: visible;
}
.rythmo-text {
  /* plus de flex, tout est positionné en absolu */
  position: absolute;
  left: 0;
  top: 0;
  width: 100%;
  height: v-bind('projectSettings.bandHeight + "px"');
  font-size: 1.1rem;
  color: #fff;
  transition: transform 0.18s cubic-bezier(0.4, 2, 0.6, 1);
}
.rythmo-content {
  position: absolute;
  left: 0;
  top: 0;
  width: 100%;
  height: v-bind('projectSettings.bandHeight + "px"');
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
  height: v-bind('projectSettings.bandHeight + "px"');
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
  /* Le background est appliqué via inline style si personnage, sinon par défaut */
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  border: 1px solid rgba(59, 130, 246, 0.3);
  z-index: 4; /* Blocs de texte toujours devant les gaps */
}

.rythmo-block.active {
  box-shadow: 0 0 12px rgba(16, 185, 129, 0.4);
  /* Le background actif est appliqué conditionnellement */
}

/* Si pas de personnage, appliquer le style actif vert */
.rythmo-block.active:not([style*="background"]) {
  background: linear-gradient(135deg, #10b981, #059669);
  border: 1px solid #10b981;
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
  font-size: v-bind('projectSettings.fontSize + "rem"');
  font-weight: 600;
  overflow: visible;
  flex-grow: 1;
  width: 100%;
  white-space: pre;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  backface-visibility: hidden;
  transform-origin: center center;
}
.rythmo-block.active .distort-text {
  opacity: 1;
  color: #ffffff;
  /* font-weight: bold; */
}

.character-tag {
  position: absolute;
  top: 2px;
  right: 101%;
  font-size: 0.7rem;
  font-weight: bold;
  padding: 2px 6px;
  border-radius: 4px;
  z-index: 20;
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
  top: -5%;
  left: 50%;
  width: 4px;
  height: 110%;
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
  width: fit-content;
  display: flex;
  flex-direction: column;
  padding: 6px 0;
  gap: 4px;
}

.character-option {
  cursor: pointer;
  line-height: 1;
  white-space: nowrap;
  padding: 2px 6px;

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

/* Styles pour la tooltip de redimensionnement */
.resize-tooltip {
  position: absolute;
  z-index: 9999;
  pointer-events: none;
  animation: fadeInUp 0.15s ease-out;
}

.resize-tooltip-content {
  background: rgba(30, 35, 45, 0.95);
  border: 1px solid rgba(132, 85, 246, 0.4);
  border-radius: 4px;
  padding: 4px 8px;
  /* backdrop-filter: blur(8px); */
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
  font-size: 0.7rem;
  white-space: nowrap;
  text-align: center;
}

.resize-time {
  color: #8455f6;
  font-family: 'Monaco', 'Menlo', monospace;
  font-size: 0.7rem;
  font-weight: 600;
}

/* Styles pour la tooltip de déplacement */
.move-tooltip {
  position: absolute;
  z-index: 9999;
  pointer-events: none;
  animation: fadeInUp 0.15s ease-out;
}

.move-tooltip-content {
  background: rgba(30, 35, 45, 0.95);
  border: 1px solid rgba(16, 185, 129, 0.4);
  border-radius: 4px;
  padding: 4px 8px;
  backdrop-filter: blur(8px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
  font-size: 0.7rem;
  white-space: nowrap;
  text-align: center;
}

.move-time {
  color: #10b981;
  font-family: 'Monaco', 'Menlo', monospace;
  font-size: 0.7rem;
  font-weight: 600;
  margin-bottom: 2px;
}

.move-line {
  color: #e5e7eb;
  font-size: 0.6rem;
  font-weight: 500;
  opacity: 0.9;
}

/* Styles pour la tooltip de déplacement des scene changes */
.scene-change-drag-tooltip {
  position: absolute;
  z-index: 9999;
  pointer-events: none;
  animation: fadeInUp 0.15s ease-out;
}

.scene-change-drag-tooltip-content {
  background: rgba(30, 35, 45, 0.95);
  border: 1px solid rgba(132, 85, 246, 0.4);
  border-radius: 4px;
  padding: 4px 8px;
  backdrop-filter: blur(8px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
  font-size: 0.7rem;
  white-space: nowrap;
  text-align: center;
}

.scene-change-time {
  color: #8455f6;
  font-family: 'Monaco', 'Menlo', monospace;
  font-size: 0.7rem;
  font-weight: 600;
}

/* Styles pour le triangle de sélection de ligne */
.line-selector-triangle {
  position: absolute;
  left: 0;
  top: 50%;
  transform: translateY(-50%);
  font-size: 1.2rem;
  color: #8455f6;
  z-index: 1;
  transition: all 0.3s ease;
  animation: fadeInLeft 0.3s ease-out;
  pointer-events: none;
  text-shadow:
    0 0 6px rgba(132, 85, 246, 0.8),
    0 0 12px rgba(132, 85, 246, 0.6),
    0 1px 3px rgba(0, 0, 0, 0.8);
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.5));
  font-weight: bold;
}

@keyframes fadeInLeft {
  from {
    opacity: 0;
    transform: translateY(-50%) translateX(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(-50%) translateX(0);
  }
}

/* Styles pour la séparation de texte */
.split-text-container {
  display: flex;
  align-items: stretch;
  justify-content: stretch;
  height: 100%;
  width: 100%;
  overflow: hidden;
  padding: 0;
  margin: 0;
  gap: 0; /* Pas d'espace entre les éléments */
}

.text-segment-wrapper {
  display: flex;
  align-items: stretch;
  justify-content: stretch;
  height: 100%;
  overflow: hidden;
  position: relative;
  min-width: 0; /* Important pour permettre le shrink */
  padding: 0;
  margin: 0;
}

.text-segment-wrapper .distort-text {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
  overflow: visible;
  text-align: center;
  transform-origin: center center; /* Important pour éviter le flou */
  padding: 0;
  margin: 0;
}
.text-separator {
  position: relative;
  height: 100%;
  width: 12px;
  flex-shrink: 0;
  z-index: 25;
  cursor: ew-resize;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
  padding: 0;
  margin: 0;
}

.text-separator:hover .separator-line,
.separator-dragging .separator-line {
  background: rgba(132, 85, 246, 0.9);
  width: 3px;
  box-shadow: 0 0 10px rgba(132, 85, 246, 0.7);
}

.text-separator:hover .separator-handle,
.separator-dragging .separator-handle {
  opacity: 1;
  transform: scale(1.1);
}

.separator-line {
  position: absolute;
  width: 2px;
  height: 90%;
  background: rgba(255, 255, 255, 0.4);
  border-radius: 1px;
  transition: all 0.2s ease;
  pointer-events: none;
  box-shadow: 0 0 4px rgba(255, 255, 255, 0.3);
}

.separator-handle {
  position: absolute;
  width: 10px;
  height: 20px;
  background: linear-gradient(135deg, rgba(132, 85, 246, 0.95), rgba(99, 102, 241, 0.95));
  border: 1px solid rgba(132, 85, 246, 0.8);
  border-radius: 3px;
  opacity: 0;
  transition: all 0.2s ease;
  pointer-events: none;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.4);
  backdrop-filter: blur(4px);
}

.separator-dragging {
  z-index: 30;
}
</style>
