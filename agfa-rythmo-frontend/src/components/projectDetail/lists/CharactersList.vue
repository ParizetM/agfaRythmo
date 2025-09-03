<template>
  <div class="characters-list bg-agfa-menu rounded-lg p-4 border border-gray-600">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-white">
        Personnages
      </h3>
      <button
        @click="$emit('add-character')"
        class="px-3 py-1 text-xs font-medium text-white bg-agfa-purple rounded hover:bg-purple-600 transition-colors"
      >
        + Ajouter
      </button>
    </div>

    <!-- Liste des personnages -->
    <div class="space-y-2 max-h-64 overflow-y-auto">
      <div
        v-for="character in characters"
        :key="character.id"
        class="character-item group"
        @click="$emit('edit-character', character)"
      >
        <!-- Indicateur de couleur -->
        <div
          class="character-color"
          :style="{ backgroundColor: character.color }"
        />

        <!-- Nom -->
        <div class="character-name">
          {{ character.name }}
        </div>

        <!-- Statistiques -->
        <div class="character-stats">
          <span class="character-count">{{ getCharacterTimecodeCount(character.id) }}</span>
          <span class="character-count-label">timecodes</span>
        </div>

        <!-- Actions -->
        <div class="character-actions">
          <button
            @click.stop="$emit('edit-character', character)"
            class="character-action-btn"
            title="Modifier"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
          </button>
          <button
            @click.stop="$emit('delete-character', character.id)"
            class="character-action-btn text-red-400 hover:text-red-300"
            title="Supprimer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Message si aucun personnage -->
      <div v-if="characters.length === 0" class="text-center py-8">
        <p class="text-gray-400 mb-2">Aucun personnage</p>
        <button
          @click="$emit('add-character')"
          class="px-4 py-2 text-sm font-medium text-white bg-agfa-purple rounded hover:bg-purple-600 transition-colors"
        >
          Créer le premier personnage
        </button>
      </div>
    </div>

    <!-- Statistiques globales -->
    <div v-if="characters.length > 0" class="mt-4 pt-4 border-t border-gray-600">
      <div class="grid grid-cols-2 gap-4 text-center">
        <div>
          <div class="text-lg font-semibold text-white">{{ characters.length }}</div>
          <div class="text-xs text-gray-400">Personnages</div>
        </div>
        <div>
          <div class="text-lg font-semibold text-white">{{ assignedTimecodesCount }}</div>
          <div class="text-xs text-gray-400">Timecodes assignés</div>
        </div>
      </div>
    </div>

    <!-- Personnages les plus utilisés -->
    <div v-if="topCharacters.length > 0" class="mt-4">
      <h4 class="text-sm font-medium text-white mb-2">Plus utilisés</h4>
      <div class="space-y-1">
        <div
          v-for="item in topCharacters"
          :key="item.character.id"
          class="flex items-center justify-between text-xs"
        >
          <div class="flex items-center space-x-2">
            <div
              class="w-3 h-3 rounded-full"
              :style="{ backgroundColor: item.character.color }"
            />
            <span class="text-white">{{ item.character.name }}</span>
          </div>
          <span class="text-gray-400">{{ item.count }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { Character } from '../composables/useRythmoState'
import type { Timecode } from '../composables/useRythmoCalculations'

interface Props {
  characters: Character[]
  timecodes: Timecode[]
}

const props = defineProps<Props>()

defineEmits<{
  'add-character': []
  'edit-character': [character: Character]
  'delete-character': [characterId: number]
}>()

// Compter les timecodes par personnage
function getCharacterTimecodeCount(characterId: number): number {
  return props.timecodes.filter(tc => tc.character_id === characterId).length
}

// Nombre total de timecodes assignés
const assignedTimecodesCount = computed(() => {
  return props.timecodes.filter(tc => tc.character_id).length
})

// Top 3 des personnages les plus utilisés
const topCharacters = computed(() => {
  const counts = props.characters.map(character => ({
    character,
    count: getCharacterTimecodeCount(character.id)
  }))

  return counts
    .filter(item => item.count > 0)
    .sort((a, b) => b.count - a.count)
    .slice(0, 3)
})
</script>

<style scoped>
.character-item {
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

.character-item:hover {
  border-color: #8455F6;
  background: rgba(56, 65, 82, 0.8);
}

.character-color {
  width: 1rem;
  height: 1rem;
  border-radius: 50%;
  flex-shrink: 0;
  border: 2px solid rgba(255, 255, 255, 0.2);
}

.character-name {
  flex: 1;
  color: white;
  font-weight: 500;
}

.character-stats {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.125rem;
}

.character-count {
  color: white;
  font-weight: 600;
  font-size: 0.875rem;
}

.character-count-label {
  color: #9ca3af;
  font-size: 0.625rem;
}

.character-actions {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  opacity: 0;
  transition: opacity 0.2s;
}

.character-item:hover .character-actions {
  opacity: 1;
}

.character-action-btn {
  padding: 0.25rem;
  color: #9ca3af;
  transition: color 0.2s;
}

.character-action-btn:hover {
  color: white;
}
</style>
