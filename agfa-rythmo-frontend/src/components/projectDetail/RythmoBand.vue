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
            v-if="timecodes[0].start > 0.2"
            class="rythmo-block rythmo-block-gap"
            :style="getGapBlockStyle(0, timecodes[0].start)"
          >
            <span class="gap-label">{{ timecodes[0].start >= 1 ? timecodes[0].start.toFixed(2) + 's' : '' }}</span>
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
                {{ (timecodes[idx + 1].start - timecodes[idx].end) >= 1 ? (timecodes[idx + 1].start - timecodes[idx].end).toFixed(2) + 's' : '' }}
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

const activeIdx = computed(() => {
  const OFFSET = -0.1 // décalage en secondes
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
  background: #1f2937;
  border-radius: 8px;
  margin-top: 0.75rem;
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
  display: flex;
  align-items: center;
  transition: transform 0.18s cubic-bezier(0.4, 2, 0.6, 1);
  font-size: 1.1rem;
  color: #fff;
  height: 3rem;
  position: absolute;
  left: 0;
  top: 0;
}
.rythmo-text.no-transition {
  transition: none;
}
.rythmo-block,
.rythmo-block-gap {
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  height: 100%;
  flex-shrink: 0;
  border-radius: 4px;
  margin: 0 1px;
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
  margin: 0 0.2rem;
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
  font-weight: bold;
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
  box-shadow: 0 0 8px rgba(255, 255, 255, 0.6), 0 0 16px rgba(0, 0, 0, 0.8);
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
</style>
