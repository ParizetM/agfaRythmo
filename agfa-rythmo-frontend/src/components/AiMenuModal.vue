<template>
  <BaseModal
    :show="show"
    title="Fonctionnalités IA"
    @update:show="$emit('update:show', $event)"
    max-width="2xl"
  >
    <template #icon>
      <svg class="w-8 h-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
      </svg>
    </template>

    <div class="space-y-4">
      <!-- Détection des changements de plan -->
      <div
        v-if="capabilities?.scene_detection"
        class="group bg-gray-800/50 rounded-lg p-4 border transition-all duration-300 border-purple-500/50 hover:border-purple-500 hover:bg-gray-800/70"
      >
        <div class="flex items-start justify-between">
          <div class="flex-1">
            <h4 class="text-white font-semibold mb-1 flex items-center gap-2">
              <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
              </svg>
              Détection des changements de plan
            </h4>
            <p class="text-gray-400 text-sm mb-2">
              Analyse automatique de la vidéo pour détecter les cuts et changements de plan
            </p>
            <div class="flex items-center gap-4 text-xs text-gray-500">
              <span class="flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Quelques minutes
              </span>
            </div>
          </div>

          <button
            :disabled="hasSceneChanges || isAnalyzing"
            class="px-4 py-2 rounded-lg font-semibold transition-all duration-300 flex items-center gap-2"
            :class="[
              hasSceneChanges || isAnalyzing
                ? 'bg-gray-700 text-gray-500 cursor-not-allowed'
                : 'bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white shadow-lg hover:shadow-xl hover:scale-105'
            ]"
            @click.stop="handleStartAnalysis"
            :title="hasSceneChanges ? 'Supprimez d\'abord les changements de plan existants' : ''"
          >
            <svg class="w-4 h-4" :class="{ 'animate-pulse': isAnalyzing }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span v-if="isAnalyzing">En cours...</span>
            <span v-else-if="hasSceneChanges">Indisponible</span>
            <span v-else>Lancer</span>
          </button>
        </div>
      </div>

      <!-- Message si aucune fonctionnalité activée -->
      <div v-if="!capabilities?.scene_detection" class="bg-gray-800/30 rounded-lg p-6 border border-dashed border-gray-700">
        <div class="flex flex-col items-center justify-center gap-3 text-gray-500">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          <p class="text-sm font-medium">Aucune fonctionnalité IA activée</p>
          <p class="text-xs text-gray-600 text-center max-w-md">
            Les fonctionnalités IA sont désactivées. Contactez votre administrateur pour les activer.
          </p>
        </div>
      </div>

      <!-- Placeholder pour futures fonctionnalités IA -->
      <div class="bg-gray-800/30 rounded-lg p-4 border border-dashed border-gray-700">
        <div class="flex items-center justify-center gap-2 text-gray-500 py-4">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
          </svg>
          <span class="text-sm">Nouvelles fonctionnalités IA à venir...</span>
        </div>
        <p class="text-center text-xs text-gray-600 mt-2">
                 </p>
      </div>
    </div>

    <template #footer>
      <div class="flex justify-end">
        <button
          @click="$emit('update:show', false)"
          class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition-colors"
        >
          Fermer
        </button>
      </div>
    </template>
  </BaseModal>
</template>

<script setup lang="ts">
import BaseModal from './BaseModal.vue'
import type { ServerCapabilities } from '@/api/serverCapabilities'

defineProps<{
  show: boolean
  capabilities: ServerCapabilities | null
  hasSceneChanges: boolean
  isAnalyzing: boolean
}>()

const emit = defineEmits<{
  'update:show': [value: boolean]
  'start-analysis': []
}>()

const handleStartAnalysis = () => {
  emit('start-analysis')
}
</script>
