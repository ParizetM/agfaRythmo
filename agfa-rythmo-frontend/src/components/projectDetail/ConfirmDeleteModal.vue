<template>
  <BaseModal
    :show="show"
    title="Confirmer la suppression"
    subtitle="Cette action est irréversible"
    size="lg"
    icon-gradient="bg-gradient-to-br from-red-500 to-red-700"
    @close="$emit('cancel')"
  >
    <!-- Icône personnalisée -->
    <template #icon>
      <svg class="w-6 h-6 sm:w-8 sm:h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
        />
      </svg>
    </template>

    <!-- Contenu -->
    <div class="px-4 sm:px-6 md:px-8 py-6">
      <p class="text-gray-300 text-base mb-4">
        Êtes-vous sûr de vouloir supprimer ce timecode ?
      </p>

      <div v-if="timecode" class="bg-agfa-bg-primary rounded-xl p-4 border-l-4 border-red-500">
        <div class="flex items-center gap-3 mb-3 flex-wrap">
          <span class="px-3 py-1 bg-red-500/20 text-red-400 rounded-lg text-sm font-semibold">
            {{ timecode.start.toFixed(2) }}s - {{ timecode.end.toFixed(2) }}s
          </span>
          <span class="px-2 py-1 bg-gray-700 text-gray-300 rounded text-xs font-medium">
            Ligne {{ timecode.line_number }}
          </span>
        </div>
        <div class="text-white font-medium mb-2 break-words">{{ timecode.text }}</div>
        <div
          v-if="timecode.character"
          class="inline-block px-3 py-1 rounded-full text-xs font-semibold text-white"
          :style="{ backgroundColor: timecode.character.color }"
        >
          {{ timecode.character.name }}
        </div>
      </div>
    </div>

    <!-- Footer avec boutons -->
    <template #footer>
      <button
        @click="$emit('cancel')"
        class="flex-1 px-4 sm:px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-xl transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98]"
      >
        Annuler
      </button>
      <button
        @click="$emit('confirm')"
        class="flex-1 px-4 sm:px-6 py-3 bg-gradient-to-r from-red-500 to-red-700 hover:from-red-600 hover:to-red-800 text-white font-semibold rounded-xl transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98] shadow-lg shadow-red-500/25"
      >
        Supprimer
      </button>
    </template>
  </BaseModal>
</template>

<script setup lang="ts">
import BaseModal from '../BaseModal.vue'
import type { Character } from '../../api/characters'

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

defineProps<{
  show: boolean
  timecode: Timecode | null
}>()

defineEmits<{
  (e: 'confirm'): void
  (e: 'cancel'): void
}>()
</script>
