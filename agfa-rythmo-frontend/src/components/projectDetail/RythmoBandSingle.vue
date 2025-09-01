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
                :class="{ active: el.tcIdx === activeIdx }"
                :style="getAbsoluteBlockStyle(el)"
                @click="onBlockClick(el.tcIdx)"
                @dblclick="onBlockDblClick(el.tcIdx, el.text)"
                style="cursor: pointer; position: absolute"
              >
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
                  <span class="distort-text" :style="getDistortStyle(el.tcIdx)">{{ el.text }}</span>
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
            <!-- Blocs -->
            <template v-for="(el, idx) in bandElements" :key="'block' + idx">
              <div
                v-if="el.type === 'block'"
                class="rythmo-block"
                :class="{ active: el.tcIdx === activeIdx }"
                :style="getAbsoluteBlockStyle(el)"
                @click="onBlockClick(el.tcIdx)"
                @dblclick="onBlockDblClick(el.tcIdx, el.text)"
                style="cursor: pointer; position: absolute"
              >
                <template v-if="editingIdx === el.tcIdx">
                  <input
                    :ref="setEditInputRef"
                    v-model="editingText"
                    @blur="finishEdit"
                    @keyup.enter="finishEdit"
                    @keyup.esc="cancelEdit"
                    class="rythmo-edit-input"
                    :style="{ width: getBlockWidth(el.tcIdx) + 'px' }"
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
                    "
                  />
                </template>
                <template v-else>
                  <span class="distort-text" :style="getDistortStyle(el.tcIdx)">{{ el.text }}</span>
                </template>
              </div>
            </template>
            <!-- Gaps -->
            <template v-for="(el, idx) in bandElements" :key="'gap' + idx">
              <div
                v-if="el.type === 'gap'"
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
          <div v-if="isLastLine" class="rythmo-ticks pointer-events-none">
            <template v-for="tick in ticks" :key="'tick' + tick.x">
              <div
                class="rythmo-tick"
                :class="{ 'tick-second': tick.isSecond }"
                :style="getTickStyle(tick)"
              ></div>
            </template>
            <!-- Barres verticales de changement de plan -->
            <template v-for="(x, idx) in sceneChangePositions" :key="'scenechange' + idx">
              <div
                class="scene-change-bar"
                :style="{ left: x + 'px' }"
              ></div>
            </template>
          </div>
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
interface Timecode {
  id?: number
  start: number
  end: number
  text: string
  line_number: number
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

const totalDuration = computed(() => {
  if (props.videoDuration && props.videoDuration > 0) return props.videoDuration
  if (!props.timecodes.length) return 1
  return props.timecodes[props.timecodes.length - 1].end
})

const bandWidth = computed(() => totalDuration.value * PX_PER_SEC)

function getBlockWidth(idx: number) {
  const tc = props.timecodes[idx]
  return Math.max(MIN_BLOCK_WIDTH, (tc.end - tc.start) * PX_PER_SEC)
}

// Nouvelle fonction : calcule la position x (en px) d'un timecode
function getBlockX(idx: number) {
  const tc = props.timecodes[idx]
  return tc.start * PX_PER_SEC + computedVisibleWidth.value / 2
}

function getAbsoluteBlockStyle(el: BandBlock) {
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

const activeIdx = computed(() => {
  const OFFSET = -0.2 // décalage en secondes
  const t = (props.currentTime ?? 0) + OFFSET
  return props.timecodes.findIndex((tc) => t >= tc.start && t < tc.end)
})

function getDistortStyle(idx: number) {
  const width = getBlockWidth(idx)
  // Calcule le scaleX pour que le texte occupe exactement la largeur du bloc
  // On mesure la longueur du texte (en px) pour ajuster le scale
  const text = props.timecodes[idx].text || ''
  // Crée un span temporaire pour mesurer la largeur réelle du texte
  const span = document.createElement('span')
  span.style.visibility = 'hidden'
  span.style.position = 'absolute'
  span.style.whiteSpace = 'pre'
  span.style.fontSize = '2.1rem'
  span.style.fontFamily = 'inherit'
  span.innerText = text
  document.body.appendChild(span)
  const textWidth = span.offsetWidth || 1 // éviter division par zéro
  document.body.removeChild(span)
  // Le scaleX est le ratio entre la largeur du bloc et celle du texte
  const scaleX = width / textWidth
  return {
    transform: `scaleX(${scaleX})`,
    width: '100%',
  }
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
  const tcs = props.timecodes
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
  (e: 'add-timecode'): void
}>()
const onBlockClick = (idx: number) => {
  if (props.timecodes[idx]) {
    emit('seek', props.timecodes[idx].start)
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
    const timecode = props.timecodes[editingIdx.value]
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
  bottom: 0;
  width: 5px;
  height: 200%;
  background: #8455f6;
  opacity: 0.95;
  border-radius: 2px;
  z-index: 3;
  box-shadow: 0 0 8px #8455f6cc;
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
  overflow: hidden;
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
  overflow: hidden;
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
  overflow: hidden;
  height: 100%;
  flex-shrink: 0;
  border-radius: 4px;
  margin: 0;
}
.rythmo-block {
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  border: 1px solid rgba(59, 130, 246, 0.3);
}
.rythmo-block.active {
  background: linear-gradient(135deg, #10b981, #059669);
  border: 1px solid #10b981;
  box-shadow: 0 0 12px rgba(16, 185, 129, 0.4);
}
.rythmo-block-gap {
  background: var(--agfa-gray) !important;
  opacity: 0.3;
  border: 1px solid rgba(75, 85, 99, 0.3);
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
</style>
