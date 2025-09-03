<template>
  <div class="timeline-view bg-agfa-menu rounded-lg p-4 border border-gray-600">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-white">
        Timeline
      </h3>
      <div class="flex items-center space-x-2">
        <!-- Filtre par ligne -->
        <select
          v-model.number="selectedLine"
          class="px-2 py-1 text-xs bg-agfa-button text-white border border-gray-600 rounded"
        >
          <option :value="0">Toutes les lignes</option>
          <option v-for="n in maxLines" :key="n" :value="n">
            Ligne {{ n }}
          </option>
        </select>

        <!-- Bouton ajouter -->
        <button
          @click="$emit('add-timecode')"
          class="px-3 py-1 text-xs font-medium text-white bg-agfa-purple rounded hover:bg-purple-600 transition-colors"
        >
          + Ajouter
        </button>
      </div>
    </div>

    <!-- Liste des timecodes -->
    <div class="space-y-2 max-h-96 overflow-y-auto">
      <div
        v-for="(timecode, index) in filteredTimecodes"
        :key="timecode.id || index"
        class="timeline-item group"
        :class="{
          'active': timecode.id === activeTimecodeId,
          'editing': timecode.id === editingTimecodeId
        }"
        @click="$emit('seek', timecode.start)"
      >
        <!-- Numéro et ligne -->
        <div class="timeline-item-header">
          <span class="timeline-index">{{ index + 1 }}</span>
          <span class="timeline-line">L{{ timecode.line_number }}</span>
          <span class="timeline-time">{{ formatTime(timecode.start) }} - {{ formatTime(timecode.end) }}</span>
        </div>

        <!-- Contenu principal -->
        <div class="timeline-item-content">
          <!-- Personnage -->
          <div
            v-if="timecode.character"
            class="timeline-character"
            :style="{ backgroundColor: timecode.character.color }"
          >
            {{ timecode.character.name }}
          </div>

          <!-- Texte -->
          <div class="timeline-text">
            {{ timecode.text }}
          </div>
        </div>

        <!-- Actions -->
        <div class="timeline-actions">
          <button
            @click.stop="$emit('edit-timecode', timecode)"
            class="timeline-action-btn"
            title="Modifier"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
          </button>
          <button
            @click.stop="$emit('delete-timecode', timecode.id!)"
            class="timeline-action-btn text-red-400 hover:text-red-300"
            title="Supprimer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Message si aucun timecode -->
      <div v-if="filteredTimecodes.length === 0" class="text-center py-8">
        <p class="text-gray-400">
          {{ selectedLine === 0 ? 'Aucun timecode' : `Aucun timecode sur la ligne ${selectedLine}` }}
        </p>
        <button
          @click="$emit('add-timecode')"
          class="mt-2 px-4 py-2 text-sm font-medium text-white bg-agfa-purple rounded hover:bg-purple-600 transition-colors"
        >
          Créer le premier timecode
        </button>
      </div>
    </div>

    <!-- Statistiques -->
    <div class="mt-4 pt-4 border-t border-gray-600">
      <div class="grid grid-cols-3 gap-4 text-center">
        <div>
          <div class="text-lg font-semibold text-white">{{ filteredTimecodes.length }}</div>
          <div class="text-xs text-gray-400">Timecodes</div>
        </div>
        <div>
          <div class="text-lg font-semibold text-white">{{ totalDuration }}</div>
          <div class="text-xs text-gray-400">Durée totale</div>
        </div>
        <div>
          <div class="text-lg font-semibold text-white">{{ usedLines }}</div>
          <div class="text-xs text-gray-400">Lignes utilisées</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import type { Timecode } from '../composables/useRythmoCalculations'

interface Props {
  timecodes: Timecode[]
  activeTimecodeId?: number | null
  editingTimecodeId?: number | null
  currentTime: number
  maxLines?: number
}

const props = withDefaults(defineProps<Props>(), {
  maxLines: 6,
})

const emit = defineEmits<{
  'seek': [time: number]
  'edit-timecode': [timecode: Timecode]
  'delete-timecode': [timecodeId: number]
  'add-timecode': []
}>()

const selectedLine = ref(0) // 0 = toutes les lignes

// Timecodes filtrés par ligne
const filteredTimecodes = computed(() => {
  let filtered = [...props.timecodes]

  // Filtrer par ligne si sélectionnée
  if (selectedLine.value > 0) {
    filtered = filtered.filter(tc => tc.line_number === selectedLine.value)
  }

  // Trier par ligne puis par temps de début
  return filtered.sort((a, b) => {
    if (a.line_number !== b.line_number) {
      return a.line_number - b.line_number
    }
    return a.start - b.start
  })
})

// Statistiques
const totalDuration = computed(() => {
  const total = filteredTimecodes.value.reduce((sum, tc) => sum + (tc.end - tc.start), 0)
  return formatTime(total)
})

const usedLines = computed(() => {
  const lines = new Set(props.timecodes.map(tc => tc.line_number))
  return lines.size
})

// Formater le temps
function formatTime(seconds: number): string {
  const minutes = Math.floor(seconds / 60)
  const secs = Math.floor(seconds % 60)
  const ms = Math.floor((seconds % 1) * 10)
  return `${minutes}:${secs.toString().padStart(2, '0')}.${ms}`
}
</script>

<style scoped>
.timeline-item {
  background: #384152;
  border: 1px solid #4b5563;
  border-radius: 0.375rem;
  padding: 0.75rem;
  cursor: pointer;
  transition: all 0.2s;
}

.timeline-item:hover {
  border-color: #8455F6;
  background: rgba(56, 65, 82, 0.8);
}

.timeline-item.active {
  border-color: #10b981;
  background: rgba(16, 185, 129, 0.1);
}

.timeline-item.editing {
  border-color: #8455F6;
  background: rgba(132, 85, 246, 0.1);
}

.timeline-item-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
  font-size: 0.75rem;
}

.timeline-index {
  background: #8455F6;
  color: white;
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  font-family: ui-monospace, SFMono-Regular, "SF Mono", Consolas, "Liberation Mono", Menlo, monospace;
}

.timeline-line {
  background: #4b5563;
  color: white;
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  font-family: ui-monospace, SFMono-Regular, "SF Mono", Consolas, "Liberation Mono", Menlo, monospace;
}

.timeline-time {
  color: #d1d5db;
  font-family: ui-monospace, SFMono-Regular, "SF Mono", Consolas, "Liberation Mono", Menlo, monospace;
}

.timeline-item-content {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.timeline-character {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: white;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.8);
}

.timeline-text {
  color: white;
  font-size: 0.875rem;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.timeline-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.5rem;
  margin-top: 0.5rem;
  opacity: 0;
  transition: opacity 0.2s;
}

.timeline-item:hover .timeline-actions {
  opacity: 1;
}

.timeline-action-btn {
  padding: 0.25rem;
  color: #9ca3af;
  transition: color 0.2s;
}

.timeline-action-btn:hover {
  color: white;
}
</style>
