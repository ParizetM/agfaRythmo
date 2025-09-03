<template>
  <div
    class="rythmo-track"
    @mouseenter="isHovered = true"
    @mouseleave="isHovered = false"
  >
    <div
      class="rythmo-track-container"
      ref="trackContainer"
      :style="{ width: `${bandWidth}px` }"
    >
      <div
        class="rythmo-content"
        :class="{ 'no-transition': noTransition }"
        :style="contentStyle"
      >
        <!-- Container pour les éléments positionnés absolument -->
        <div class="rythmo-elements">
          <!-- Blocs de timecode et gaps -->
          <template v-for="(element, idx) in bandElements" :key="'el' + idx">
            <RythmoBlock
              v-if="element.type === 'block'"
              :timecode="element.timecode"
              :block-width="element.width"
              :is-active="element.tcIdx === activeIdx"
              :is-editing="editingTimecodeId === element.timecode.id"
              :text-distort-style="calculations.getTextDistortStyle(element.timecode)"
              :style="{
                left: element.x + 'px',
                position: 'absolute',
                top: '0',
                height: '100%'
              }"
              @block-click="interactions.handleBlockClick"
              @block-dblclick="interactions.handleBlockDoubleClick"
              @resize-start="interactions.startResize"
              @move-start="(id, event) => interactions.startMove(id, lineNumber, event)"
              @edit-finish="interactions.finishEdit"
              @edit-cancel="interactions.cancelEdit"
              @character-toggle="(id) => $emit('character-toggle', id)"
            />

            <RythmoBlock
              v-else-if="element.type === 'gap'"
              :timecode="element"
              :block-width="element.width"
              :is-gap="true"
              :gap-label="element.label"
              :style="{
                left: element.x + 'px',
                position: 'absolute',
                top: '0',
                height: '100%'
              }"
            />
          </template>
        </div>

        <!-- Graduations temporelles (seulement sur la dernière ligne) -->
        <RythmoTicks
          v-if="isLastLine"
          :ticks="calculations.ticks.value"
        />

        <!-- Barres de changement de plan -->
        <SceneChangeMarkers
          :scene-change-positions="sceneChangePositions"
        />
      </div>

      <!-- Curseur de lecture -->
      <RythmoCursor
        :center-offset="calculations.centerOffset.value"
      />

      <!-- Preview de déplacement -->
      <RythmoMovePreview
        :visible="interactions.interactionState.value.previewPosition.visible"
        :x="interactions.interactionState.value.previewPosition.x"
        :y="interactions.interactionState.value.previewPosition.y"
        :timecode="movingTimecode"
        :target-line="interactions.interactionState.value.previewPosition.targetLine"
        :text-distort-style="movingTimecode ? calculations.getTextDistortStyle(movingTimecode) : undefined"
      />

      <!-- Overlay avec informations de ligne -->
      <div v-if="isHovered" class="line-overlay">
        <div class="line-info">
          <span class="line-number-badge">Ligne {{ lineNumber }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from 'vue'
import { useRythmoStore } from '../composables/useRythmoState'
import { useRythmoCalculations, type Timecode } from '../composables/useRythmoCalculations'
import { useRythmoInteractions } from '../composables/useRythmoInteractions'
import { useSmoothScroll } from '../composables/useSmoothScroll'

import RythmoBlock from '../ui/RythmoBlock.vue'
import RythmoTicks from '../ui/RythmoTicks.vue'
import RythmoCursor from '../ui/RythmoCursor.vue'
import SceneChangeMarkers from '../ui/SceneChangeMarkers.vue'
import RythmoMovePreview from '../ui/RythmoMovePreview.vue'

interface Props {
  timecodes: Timecode[]
  currentTime: number
  videoDuration?: number
  visibleWidth?: number
  instant?: boolean | import('vue').Ref<boolean>
  sceneChanges?: number[]
  lineNumber: number
  isLastLine: boolean
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'seek': [time: number]
  'update-timecode': [payload: { timecode: Timecode; text: string }]
  'update-timecode-bounds': [payload: { timecode: Timecode; start: number; end: number }]
  'move-timecode': [payload: { timecode: Timecode; newStart: number; newLineNumber: number }]
  'character-toggle': [timecodeId: number]
  'add-timecode': []
  'reload-lines': [payload: { sourceLineNumber: number; targetLineNumber: number }]
}>()

// État local
const store = useRythmoStore()
const trackContainer = ref<HTMLDivElement>()
const isHovered = ref(false)
const noTransition = ref(false)

// Largeur visible responsive
const localVisibleWidth = ref(0)
const computedVisibleWidth = computed(() => {
  if (typeof props.visibleWidth === 'number' && props.visibleWidth > 0)
    return props.visibleWidth
  return localVisibleWidth.value
})

// Reactive refs pour les calculs
const timecodesRef = computed(() => props.timecodes)
const videoDurationRef = computed(() => props.videoDuration)
const visibleWidthRef = computedVisibleWidth

// Initialisation des composables
const calculations = useRythmoCalculations(timecodesRef, videoDurationRef, visibleWidthRef)
const interactions = useRythmoInteractions(timecodesRef, calculations, emit)

// État de l'édition depuis le store
const editingTimecodeId = computed(() => store.editingTimecodeId)

// Calculs dérivés
const bandWidth = calculations.bandWidth
const activeIdx = computed(() =>
  calculations.getActiveTimecodeIndex(props.currentTime)
)

const sceneChangePositions = computed(() =>
  calculations.getSceneChangePositions(props.sceneChanges || [])
)

const movingTimecode = computed(() => {
  const movingId = interactions.interactionState.value.movingTimecodeId
  return movingId ? props.timecodes.find(tc => tc.id === movingId) : null
})

// Types pour les éléments de la bande
type BandBlock = {
  type: 'block'
  x: number
  width: number
  timecode: Timecode
  tcIdx: number
}
type BandGap = {
  type: 'gap'
  x: number
  width: number
  label: string
  id?: undefined
  start: number
  end: number
  text: string
  line_number: number
}
type BandElement = BandBlock | BandGap

// Génération des éléments de la bande
const bandElements = computed<BandElement[]>(() => {
  const arr: BandElement[] = []
  const tcs = interactions.effectiveTimecodes.value

  if (!tcs.length) {
    // Si aucun timecode, créer un gap pour toute la durée
    return [{
      type: 'gap',
      x: 0,
      width: bandWidth.value,
      label: `0s - ${calculations.totalDuration.value.toFixed(2)}s`,
      start: 0,
      end: calculations.totalDuration.value,
      text: '',
      line_number: props.lineNumber,
    }]
  }

  // Gap avant le premier timecode
  if (tcs[0].start > 0.2) {
    const x = calculations.getGapX(0)
    const width = calculations.getGapWidth(0, tcs[0].start)
    arr.push({
      type: 'gap',
      x,
      width,
      label: tcs[0].start >= 1 ? tcs[0].start.toFixed(2) + 's' : '',
      start: 0,
      end: tcs[0].start,
      text: '',
      line_number: props.lineNumber,
    })
  }

  // Blocs + gaps intermédiaires
  for (let i = 0; i < tcs.length; i++) {
    // Bloc de timecode
    const x = calculations.getBlockX(tcs[i])
    const width = calculations.getBlockWidth(tcs[i])
    arr.push({
      type: 'block',
      x,
      width,
      timecode: tcs[i],
      tcIdx: i
    })

    // Gap après ce bloc (si trou)
    if (i < tcs.length - 1 && tcs[i].end < tcs[i + 1].start) {
      const gapX = calculations.getGapX(tcs[i].end)
      const gapWidth = calculations.getGapWidth(tcs[i].end, tcs[i + 1].start)
      arr.push({
        type: 'gap',
        x: gapX,
        width: gapWidth,
        label: tcs[i + 1].start - tcs[i].end >= 1
          ? (tcs[i + 1].start - tcs[i].end).toFixed(2) + 's'
          : '',
        start: tcs[i].end,
        end: tcs[i + 1].start,
        text: '',
        line_number: props.lineNumber,
      })
    }
  }

  // Gap après le dernier timecode
  const last = tcs[tcs.length - 1]
  if (Math.round((calculations.totalDuration.value - last.end) * 100) / 100 >= 0.5) {
    const x = calculations.getGapX(last.end)
    const width = calculations.getGapWidth(last.end, calculations.totalDuration.value)
    arr.push({
      type: 'gap',
      x,
      width,
      label: (calculations.totalDuration.value - last.end).toFixed(2) + 's',
      start: last.end,
      end: calculations.totalDuration.value,
      text: '',
      line_number: props.lineNumber,
    })
  }

  return arr
})

// Smooth scroll
const targetScroll = computed(() => calculations.getScrollTarget(props.currentTime))
const instantRef = computed(() => {
  if (typeof props.instant === 'object' && 'value' in props.instant) {
    return props.instant.value
  }
  return !!props.instant
})
const smoothScroll = useSmoothScroll(() => targetScroll.value, instantRef)

// Style du contenu avec transformation
const contentStyle = computed(() => ({
  width: `${bandWidth.value}px`,
  transform: `translateX(-${smoothScroll.value}px)`,
  transition: noTransition.value ? 'none' : 'transform 0.18s cubic-bezier(0.4, 2, 0.6, 1)',
}))

// Gestion de la largeur responsive
onMounted(() => {
  if (typeof props.visibleWidth !== 'number') {
    const updateWidth = () => {
      if (trackContainer.value) {
        localVisibleWidth.value = trackContainer.value.offsetWidth
      }
    }

    let ro: ResizeObserver | null = null

    nextTick(() => {
      updateWidth()
      if (typeof ResizeObserver !== 'undefined' && trackContainer.value) {
        ro = new ResizeObserver(updateWidth)
        ro.observe(trackContainer.value)
      }
    })

    window.addEventListener('resize', updateWidth)

    onBeforeUnmount(() => {
      window.removeEventListener('resize', updateWidth)
      if (ro) ro.disconnect()
    })
  }
})

// Désactive la transition si le scroll saute brutalement
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
.rythmo-track {
  width: 100%;
  overflow: visible;
  background: #1f2937;
  border-radius: 8px;
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

.rythmo-content {
  position: absolute;
  left: 0;
  top: 0;
  width: 100%;
  height: 3rem;
  will-change: transform;
}

.rythmo-content.no-transition {
  transition: none;
}

.rythmo-elements {
  position: absolute;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
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
</style>
