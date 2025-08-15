<template>
  <div class="rythmo-band">
    <div class="rythmo-track-container" ref="trackContainer" :style="{ width: `${bandWidth}px` }">
      <div
        class="rythmo-text"
        :class="{ 'no-transition': noTransition }"
        :style="rythmoTextStyle"
      >
        <template v-if="timecodes.length">
          <!-- Gap avant le premier timecode -->
          <div
            v-if="timecodes[0].start > 0.5"
            class="rythmo-block rythmo-block-gap"
            :style="getGapBlockStyle(0, timecodes[0].start)"
          >
            <span class="gap-label">{{ (timecodes[0].start - 0).toFixed(2) }}s</span>
          </div>
          <!-- Timecodes + gaps intermédiaires -->
          <template v-for="(line, idx) in timecodes" :key="'tc' + idx">
            <div
              class="rythmo-block"
              :class="{ active: idx === activeIdx }"
              :style="getBlockStyle(idx)"
            >
              <span class="distort-text" :style="getDistortStyle(idx)">{{ line.text }}</span>
            </div>

            <!-- Gap entre timecodes (si trou) -->
            <div
              v-if="idx < timecodes.length - 1 && timecodes[idx].end < timecodes[idx + 1].start"
              class="rythmo-block rythmo-block-gap"
              :style="getGapBlockStyle(timecodes[idx].end, timecodes[idx + 1].start)"
            >
              <span class="gap-label">
                {{ (timecodes[idx + 1].start - timecodes[idx].end).toFixed(2) }}s
              </span>
            </div>
          </template>
          <!-- Gap après le dernier timecode -->
          <div
            v-if="
              Math.round((totalDuration - timecodes[timecodes.length - 1].end) * 100) / 100 >= 0.5
            "
            class="rythmo-block rythmo-block-gap"
            :style="getGapBlockStyle(timecodes[timecodes.length - 1].end, totalDuration)"
          >
            <span class="gap-label">
              {{ (totalDuration - timecodes[timecodes.length - 1].end).toFixed(2) }}s
            </span>
          </div>
          <!-- Padding transparent pour permettre le scroll complet jusqu'à la fin -->
        </template>
        <!-- Si aucun timecode, toute la barre = gap -->
        <div
          v-else
          class="rythmo-block rythmo-block-gap"
          :style="getGapBlockStyle(0, totalDuration)"
        >
          <span class="gap-label">0s - {{ totalDuration.toFixed(2) }}s</span>
        </div>
      </div>
      <div class="rythmo-cursor"></div>
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
import { ref, computed, defineProps, onMounted, onBeforeUnmount, watch, nextTick } from 'vue'
import { useSmoothScroll } from './useSmoothScroll'
const props = defineProps<{
  timecodes: { start: number; end: number; text: string }[]
  currentTime: number
  videoDuration?: number
  visibleWidth?: number
}>()

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
  paddingLeft: computedVisibleWidth.value / 2 + 'px',
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

function getBlockStyle(idx: number) {
  return {
    width: getBlockWidth(idx) + 'px',
    flexShrink: 0,
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    overflow: 'hidden',
    height: '100%',
  }
}

const activeIdx = computed(() =>
  props.timecodes.findIndex((tc) => props.currentTime >= tc.start && props.currentTime < tc.end),
)

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

// Bloc "gap" avant/après : width proportionnelle à la durée
function getGapBlockStyle(start: number, end: number) {
  const width = Math.max(10, (end - start) * PX_PER_SEC)
  return {
    width: width + 'px',
    flexShrink: 0,
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    overflow: 'hidden',
    height: '100%',
    background: '#2d3748',
    opacity: 0.5,
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
const smoothScroll = useSmoothScroll(() => targetScroll.value)

// Désactive la transition si le scroll saute brutalement (seek, pause/play)
watch(smoothScroll, (val, oldVal) => {
  if (Math.abs(val - oldVal) > 40) {
    noTransition.value = true
    setTimeout(() => {
      noTransition.value = false
    }, 40)
  }
})
</script>

<style scoped>
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
  background: #181c24;
  border-radius: 6px;
  margin-top: 0.5rem;
  min-height: 2.5rem;
  display: flex;
  align-items: center;
  box-shadow: 0 1px 4px #0002;
}
.rythmo-track-container {
  position: relative;
  height: 2.5rem;
  overflow: hidden;
}
.rythmo-text {
  display: flex;
  align-items: center;
  transition: transform 0.18s cubic-bezier(0.4, 2, 0.6, 1);
  font-size: 1.1rem;
  color: #fff;
  height: 2.5rem;
  position: absolute;
  left: 0;
  top: 0;
}
.rythmo-block,
.rythmo-block-gap {
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  height: 100%;
  flex-shrink: 0;
}
.rythmo-block-gap {
  background: #2d3748 !important;
  opacity: 0.5;
}
.rythmo-text span,
.distort-text {
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: left;
  margin: 0 0.2rem;
  opacity: 0.5;
  background: none;
  border-radius: 3px;
  font-size: 2.1rem;
  overflow: visible;
  flex-grow: 1;
  width: 100%;
  white-space: pre;
}
.rythmo-text span.active {
  opacity: 1;
  color: #38a169;
  font-weight: bold;
  background: #38a16922;
}
.rythmo-cursor {
  position: absolute;
  top: 0;
  left: 50%;
  width: 3px;
  height: 100%;
  background: #fff;
  border-radius: 2px;
  box-shadow: 0 0 6px #0008;
  z-index: 2;
  transition: none;
}
.gap-label {
  font-size: 1rem;
  color: #cbd5e1;
  font-style: italic;
  user-select: none;
  opacity: 0.2 !important;
}
</style>
