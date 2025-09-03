<template>
  <div class="scene-changes-list bg-agfa-menu rounded-lg p-4 border border-gray-600">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-white">
        Changements de plan
      </h3>
      <button
        @click="$emit('add-scene-change')"
        class="px-3 py-1 text-xs font-medium text-white bg-agfa-purple rounded hover:bg-purple-600 transition-colors"
      >
        + Ajouter
      </button>
    </div>

    <!-- Liste des changements -->
    <div class="space-y-2 max-h-64 overflow-y-auto">
      <div
        v-for="(time, index) in sortedSceneChanges"
        :key="index"
        class="scene-change-item group"
        @click="$emit('seek', time)"
      >
        <!-- Numéro -->
        <div class="scene-change-number">
          {{ index + 1 }}
        </div>

        <!-- Temps -->
        <div class="scene-change-time">
          {{ formatTime(time) }}
        </div>

        <!-- Indicateur visuel -->
        <div class="scene-change-bar-preview" />

        <!-- Context (timecodes proches) -->
        <div class="scene-change-context">
          <div
            v-for="tc in getNearbyTimecodes(time)"
            :key="tc.id"
            class="context-timecode"
            :style="{ color: tc.character?.color || '#ffffff' }"
          >
            L{{ tc.line_number }} • {{ tc.text.substring(0, 30) }}{{ tc.text.length > 30 ? '...' : '' }}
          </div>
        </div>

        <!-- Actions -->
        <div class="scene-change-actions">
          <button
            @click.stop="$emit('seek', time)"
            class="scene-change-action-btn"
            title="Aller au temps"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h1m4 0h1m2-7H3a2 2 0 00-2 2v9a2 2 0 002 2h18a2 2 0 002-2V9a2 2 0 00-2-2h-4" />
            </svg>
          </button>
          <button
            @click.stop="$emit('remove-scene-change', time)"
            class="scene-change-action-btn text-red-400 hover:text-red-300"
            title="Supprimer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Message si aucun changement -->
      <div v-if="sceneChanges.length === 0" class="text-center py-8">
        <p class="text-gray-400 mb-2">Aucun changement de plan</p>
        <button
          @click="$emit('add-scene-change')"
          class="px-4 py-2 text-sm font-medium text-white bg-agfa-purple rounded hover:bg-purple-600 transition-colors"
        >
          Ajouter le premier changement
        </button>
      </div>
    </div>

    <!-- Statistiques -->
    <div v-if="sceneChanges.length > 0" class="mt-4 pt-4 border-t border-gray-600">
      <div class="grid grid-cols-3 gap-4 text-center">
        <div>
          <div class="text-lg font-semibold text-white">{{ sceneChanges.length }}</div>
          <div class="text-xs text-gray-400">Changements</div>
        </div>
        <div>
          <div class="text-lg font-semibold text-white">{{ averageInterval }}</div>
          <div class="text-xs text-gray-400">Intervalle moyen</div>
        </div>
        <div>
          <div class="text-lg font-semibold text-white">{{ totalDuration }}</div>
          <div class="text-xs text-gray-400">Durée totale</div>
        </div>
      </div>
    </div>

    <!-- Outils d'analyse -->
    <div v-if="sceneChanges.length > 1" class="mt-4">
      <h4 class="text-sm font-medium text-white mb-2">Analyse</h4>
      <div class="space-y-1 text-xs">
        <div class="flex justify-between">
          <span class="text-gray-400">Plus court :</span>
          <span class="text-white">{{ shortestInterval }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-400">Plus long :</span>
          <span class="text-white">{{ longestInterval }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-400">Densité :</span>
          <span class="text-white">{{ density }} changements/min</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { Timecode } from '../composables/useRythmoCalculations'

interface Props {
  sceneChanges: number[]
  timecodes: Timecode[]
  videoDuration?: number
}

const props = defineProps<Props>()

defineEmits<{
  'add-scene-change': []
  'remove-scene-change': [time: number]
  'seek': [time: number]
}>()

// Changements triés par temps
const sortedSceneChanges = computed(() => {
  return [...props.sceneChanges].sort((a, b) => a - b)
})

// Formater le temps
function formatTime(seconds: number): string {
  const minutes = Math.floor(seconds / 60)
  const secs = Math.floor(seconds % 60)
  const ms = Math.floor((seconds % 1) * 10)
  return `${minutes}:${secs.toString().padStart(2, '0')}.${ms}`
}

// Trouver les timecodes proches d'un changement de plan
function getNearbyTimecodes(time: number): Timecode[] {
  const radius = 3 // 3 secondes avant/après
  return props.timecodes
    .filter(tc => Math.abs(tc.start - time) <= radius || Math.abs(tc.end - time) <= radius)
    .sort((a, b) => Math.abs(a.start - time) - Math.abs(b.start - time))
    .slice(0, 2) // Maximum 2 timecodes
}

// Calculs statistiques
const intervals = computed(() => {
  const sorted = sortedSceneChanges.value
  const intervals: number[] = []

  for (let i = 1; i < sorted.length; i++) {
    intervals.push(sorted[i] - sorted[i - 1])
  }

  return intervals
})

const averageInterval = computed(() => {
  if (intervals.value.length === 0) return '0s'
  const avg = intervals.value.reduce((sum, interval) => sum + interval, 0) / intervals.value.length
  return formatTime(avg)
})

const shortestInterval = computed(() => {
  if (intervals.value.length === 0) return '0s'
  return formatTime(Math.min(...intervals.value))
})

const longestInterval = computed(() => {
  if (intervals.value.length === 0) return '0s'
  return formatTime(Math.max(...intervals.value))
})

const totalDuration = computed(() => {
  const duration = props.videoDuration ||
    (sortedSceneChanges.value.length > 0 ? sortedSceneChanges.value[sortedSceneChanges.value.length - 1] : 0)
  return formatTime(duration)
})

const density = computed(() => {
  const duration = props.videoDuration ||
    (sortedSceneChanges.value.length > 0 ? sortedSceneChanges.value[sortedSceneChanges.value.length - 1] : 1)
  const changesPerMinute = (props.sceneChanges.length / duration) * 60
  return changesPerMinute.toFixed(1)
})
</script>

<style scoped>
.scene-change-item {
  display: flex;
  align-items: center;
  background: #384152;
  border: 1px solid #4b5563;
  border-radius: 0.375rem;
  padding: 0.75rem;
  cursor: pointer;
  transition: all 0.2s;
  gap: 0.75rem;
}

.scene-change-item:hover {
  border-color: #8455F6;
  background: rgba(56, 65, 82, 0.8);
}

.scene-change-number {
  background: #657390;
  color: white;
  width: 1.5rem;
  height: 1.5rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 600;
  flex-shrink: 0;
}

.scene-change-time {
  color: white;
  font-weight: 500;
  font-family: ui-monospace, SFMono-Regular, "SF Mono", Consolas, "Liberation Mono", Menlo, monospace;
  min-width: 4rem;
}

.scene-change-bar-preview {
  width: 0.25rem;
  height: 1.5rem;
  background: #657390;
  border-radius: 0.125rem;
  flex-shrink: 0;
  box-shadow: 0 0 4px #657390;
}

.scene-change-context {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
  min-width: 0;
}

.context-timecode {
  font-size: 0.625rem;
  opacity: 0.8;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.scene-change-actions {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  opacity: 0;
  transition: opacity 0.2s;
}

.scene-change-item:hover .scene-change-actions {
  opacity: 1;
}

.scene-change-action-btn {
  padding: 0.25rem;
  color: #9ca3af;
  transition: color 0.2s;
}

.scene-change-action-btn:hover {
  color: white;
}
</style>
