<template>
  <BaseModal
    :show="show"
    title="Analyse IA en cours"
    subtitle="Détection automatique des changements de plan"
    :hide-close="true"
    :close-on-backdrop="false"
    size="lg"
  >
    <template v-slot:icon>
      <svg class="w-6 h-6 sm:w-8 sm:h-8 text-white animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
      </svg>
    </template>

    <template v-slot:default>
      <div class="flex flex-col items-center justify-center space-y-6 py-8">
        <!-- Spinner personnalisé -->
        <div class="relative">
          <div class="w-24 h-24 border-8 border-gray-700 rounded-full"></div>
          <div class="w-24 h-24 border-8 border-agfa-blue border-t-transparent rounded-full animate-spin absolute top-0 left-0"></div>
        </div>

        <!-- Message de statut -->
        <div class="text-center">
          <p class="text-xl text-white font-medium mb-2">{{ statusMessage }}</p>
          <p v-if="progressPercentage > 0" class="text-2xl text-agfa-blue font-bold mb-1">
            {{ progressPercentage }}%
          </p>
          <p class="text-sm text-gray-400">Cette opération peut prendre plusieurs minutes...</p>
        </div>

        <!-- Barre de progression -->
        <div class="w-full max-w-md h-2 bg-gray-700 rounded-full overflow-hidden">
          <div
            class="h-full bg-gradient-to-r from-agfa-blue to-purple-500 rounded-full transition-all duration-500"
            :style="{ width: `${progressPercentage}%` }"
          ></div>
        </div>

        <!-- Bouton Annuler -->
        <button
          @click="$emit('cancel')"
          class="mt-4 px-6 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition-colors duration-300 shadow-lg hover:shadow-xl"
        >
          Annuler l'analyse
        </button>

        <!-- Détails techniques (optionnel) -->
        <div v-if="showDetails" class="text-xs text-gray-500 text-center">
          <p>Analyse vidéo avec FFmpeg...</p>
          <p class="mt-1">Détection des changements de scène en cours</p>
        </div>
      </div>
    </template>
  </BaseModal>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import BaseModal from './BaseModal.vue'

const props = defineProps<{
  show: boolean
  status: 'pending' | 'processing' | 'completed' | 'failed' | 'cancelled'
  progress?: number  // 0-100
  message?: string
  showDetails?: boolean
}>()

defineEmits<{
  cancel: []
}>()

const statusMessage = computed(() => {
  if (props.message) return props.message

  switch (props.status) {
    case 'pending':
      return 'Démarrage de l\'analyse...'
    case 'processing':
      return 'Analyse de la vidéo en cours...'
    case 'completed':
      return 'Analyse terminée !'
    case 'failed':
      return 'Erreur lors de l\'analyse'
    default:
      return 'Analyse en cours...'
  }
})

const progressPercentage = computed(() => {
  return props.progress ?? 0
})

</script>

<style scoped>
/* Animations personnalisées */
@keyframes pulse-slow {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.animate-pulse {
  animation: pulse-slow 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
