<template>
  <div class="multi-rythmo-container">
    <!-- Configuration du nombre de lignes -->
    <div v-if="!hideConfig" class="rythmo-config mb-3">
      <label class="text-white text-sm font-medium">
        Nombre de lignes rythmo:
        <select
          v-model.number="localRythmoLinesCount"
          @change="onLinesCountChange"
          class="ml-2 bg-agfa-button text-white border border-gray-600 rounded px-2 py-1 text-sm"
        >
          <option :value="1">1 ligne</option>
          <option :value="2">2 lignes</option>
          <option :value="3">3 lignes</option>
          <option :value="4">4 lignes</option>
          <option :value="5">5 lignes</option>
          <option :value="6">6 lignes</option>
        </select>
      </label>
    </div>

    <!-- Bandes rythmo multiples - collées -->
    <div class="rythmo-bands-container">
      <div v-for="lineNumber in localRythmoLinesCount" :key="lineNumber" class="rythmo-line-wrapper">
        <RythmoBandSingle
          :timecodes="getTimecodesForLine(Number(lineNumber))"
          :currentTime="currentTime"
          :videoDuration="videoDuration"
          :instant="instant"
          :sceneChanges="sceneChanges"
          :lineNumber="Number(lineNumber)"
          :isLastLine="lineNumber === localRythmoLinesCount"
          @seek="$emit('seek', $event)"
          @update-timecode="onUpdateTimecode"
          @add-timecode="() => $emit('add-timecode-to-line', Number(lineNumber))"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import RythmoBandSingle from './RythmoBandSingle.vue'

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
  instant?: boolean | import('vue').Ref<boolean>
  sceneChanges?: number[]
  rythmoLinesCount: number
  hideConfig?: boolean
}>()

const emit = defineEmits<{
  (e: 'seek', time: number): void
  (e: 'update-timecode', payload: { timecode: Timecode; text: string }): void
  (e: 'add-timecode-to-line', lineNumber: number): void
  (e: 'update-lines-count', count: number): void
}>()

const localRythmoLinesCount = ref(props.rythmoLinesCount || 1)

// Synchronise avec les props
watch(() => props.rythmoLinesCount, (newCount) => {
  if (newCount && newCount !== localRythmoLinesCount.value) {
    localRythmoLinesCount.value = newCount
  }
})

function getTimecodesForLine(lineNumber: number): Timecode[] {
  return props.timecodes.filter(tc => tc.line_number === lineNumber)
}

function onUpdateTimecode(payload: { timecode: Timecode; text: string }) {
  emit('update-timecode', payload)
}

function onLinesCountChange() {
  emit('update-lines-count', localRythmoLinesCount.value)
}
</script>

<style scoped>
.multi-rythmo-container {
  width: 100%;
}

.rythmo-config {
  display: flex;
  align-items: center;
  justify-content: flex-start;
  padding: 0.5rem 1rem;
  background: #202937;
  border-radius: 6px;
  border: 1px solid rgba(59, 130, 246, 0.2);
}

.rythmo-bands-container {
  display: flex;
  flex-direction: column;
  gap: 0; /* Aucun espacement entre les lignes */
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.rythmo-line-wrapper {
  position: relative;
  width: 100%;
  border-bottom: 1px solid rgba(59, 130, 246, 0.2);
}

.rythmo-line-wrapper:last-child {
  border-bottom: none;
}
</style>
