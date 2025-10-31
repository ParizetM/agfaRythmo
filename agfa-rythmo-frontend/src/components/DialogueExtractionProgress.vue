<template>
  <BaseModal
    :show="show"
    title="Extraction de dialogues en cours"
    @update:show="() => {}"
    :hide-close="true"
    size="lg"
  >
    <template #icon>
      <div class="relative">
        <svg class="w-8 h-8 text-blue-400 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
        </svg>
        <div class="absolute -top-1 -right-1 w-3 h-3 bg-blue-500 rounded-full animate-ping"></div>
      </div>
    </template>

    <div class="space-y-6">
      <!-- Progress bar -->
      <div>
        <div class="flex justify-between items-center mb-2">
          <span class="text-sm font-medium text-gray-300">Progression</span>
          <span class="text-sm font-semibold text-blue-400">{{ progress }}%</span>
        </div>

        <div class="w-full bg-gray-700 rounded-full h-3 overflow-hidden">
          <div
            class="h-full bg-gradient-to-r from-blue-500 to-violet-500 transition-all duration-500 ease-out relative"
            :style="{ width: `${progress}%` }"
          >
            <div class="absolute inset-0 bg-white/20 animate-pulse"></div>
          </div>
        </div>
      </div>

      <!-- Message de statut -->
      <div class="bg-gray-800/50 border border-gray-700 rounded-lg p-4">
        <p class="text-gray-300 text-sm flex items-center gap-2">
          <svg class="w-4 h-4 text-blue-400 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
          </svg>
          {{ statusMessage }}
        </p>
      </div>

      <!-- Étapes de progression -->
      <div class="space-y-3">
        <div
          v-for="step in steps"
          :key="step.id"
          class="flex items-center gap-3 p-3 rounded-lg transition-all"
          :class="[
            step.status === 'completed' ? 'bg-green-500/10 border border-green-500/30' :
            step.status === 'in-progress' ? 'bg-blue-500/10 border border-blue-500/30 animate-pulse' :
            'bg-gray-800/30 border border-gray-700'
          ]"
        >
          <!-- Icône de statut -->
          <div class="flex-shrink-0">
            <svg
              v-if="step.status === 'completed'"
              class="w-5 h-5 text-green-400"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <svg
              v-else-if="step.status === 'in-progress'"
              class="w-5 h-5 text-blue-400 animate-spin"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            <svg
              v-else
              class="w-5 h-5 text-gray-600"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
          </div>

          <!-- Texte de l'étape -->
          <div class="flex-1">
            <p
              class="text-sm font-medium"
              :class="[
                step.status === 'completed' ? 'text-green-400' :
                step.status === 'in-progress' ? 'text-blue-400' :
                'text-gray-500'
              ]"
            >
              {{ step.label }}
            </p>
          </div>

          <!-- Pourcentage de l'étape -->
          <div
            v-if="step.status !== 'pending'"
            class="text-xs font-semibold"
            :class="[
              step.status === 'completed' ? 'text-green-400' :
              step.status === 'in-progress' ? 'text-blue-400' :
              'text-gray-500'
            ]"
          >
            {{ step.range }}
          </div>
        </div>
      </div>

      <!-- Statistiques (si disponibles) -->
      <div v-if="timecodesCount > 0 || charactersCount > 0" class="grid grid-cols-2 gap-4">
        <div class="bg-blue-500/10 border border-blue-500/30 rounded-lg p-4">
          <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
            </svg>
            <div>
              <p class="text-xs text-gray-400">Timecodes</p>
              <p class="text-lg font-bold text-blue-400">{{ timecodesCount }}</p>
            </div>
          </div>
        </div>

        <div class="bg-violet-500/10 border border-violet-500/30 rounded-lg p-4">
          <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            <div>
              <p class="text-xs text-gray-400">Personnages</p>
              <p class="text-lg font-bold text-violet-400">{{ charactersCount }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Avertissement -->
      <div class="bg-yellow-500/10 border border-yellow-500/30 rounded-lg p-3">
        <p class="text-yellow-400 text-xs flex items-center gap-2">
          <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          Ne fermez pas cette fenêtre. L'extraction peut prendre plusieurs minutes selon la durée de la vidéo.
        </p>
      </div>
    </div>

    <template #footer>
      <div class="flex justify-between items-center">
        <div class="text-xs text-gray-500">
          L'annulation supprimera les données déjà créées
        </div>
        <button
          @click="handleCancel"
          :disabled="isCancelling"
          class="px-4 py-2 bg-red-600 hover:bg-red-700 disabled:bg-gray-700 disabled:text-gray-500 text-white rounded-lg transition-colors flex items-center gap-2"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
          {{ isCancelling ? 'Annulation...' : 'Annuler l\'extraction' }}
        </button>
      </div>
    </template>
  </BaseModal>
</template>

<script setup lang="ts">
import { ref, computed, watch, onUnmounted } from 'vue'
import BaseModal from './BaseModal.vue'
import { getDialogueExtractionStatus, cancelDialogueExtraction } from '@/api/dialogueExtraction'

const props = defineProps<{
  show: boolean
  projectId: number
}>()

const emit = defineEmits<{
  'update:show': [value: boolean]
  'completed': [result: { timecodes_count: number; characters_count: number; source_language?: string }]
  'failed': [message: string]
  'cancelled': []
}>()

const progress = ref(0)
const statusMessage = ref('Initialisation...')
const timecodesCount = ref(0)
const charactersCount = ref(0)
const isCancelling = ref(false)

let pollingInterval: ReturnType<typeof setInterval> | null = null

// Étapes de progression
const steps = computed(() => [
  {
    id: 1,
    label: 'Extraction audio',
    range: '0-20%',
    status: progress.value >= 20 ? 'completed' : progress.value > 0 ? 'in-progress' : 'pending'
  },
  {
    id: 2,
    label: 'Transcription Whisper',
    range: '20-70%',
    status: progress.value >= 70 ? 'completed' : progress.value >= 20 ? 'in-progress' : 'pending'
  },
  {
    id: 3,
    label: 'Séparation des locuteurs',
    range: '70-90%',
    status: progress.value >= 90 ? 'completed' : progress.value >= 70 ? 'in-progress' : 'pending'
  },
  {
    id: 4,
    label: 'Création timecodes & personnages',
    range: '90-100%',
    status: progress.value === 100 ? 'completed' : progress.value >= 90 ? 'in-progress' : 'pending'
  }
])

// Polling du statut
const fetchStatus = async () => {
  try {
    const status = await getDialogueExtractionStatus(props.projectId)

    progress.value = status.dialogue_extraction_progress || 0
    statusMessage.value = status.dialogue_extraction_message || 'En cours...'
    timecodesCount.value = status.timecodes_count || 0
    charactersCount.value = status.characters_count || 0

    // Vérifier si terminé
    if (status.dialogue_extraction_status === 'completed') {
      stopPolling()
      const result = {
        timecodes_count: timecodesCount.value,
        characters_count: charactersCount.value,
        source_language: status.source_language
      }
      console.log('🚀 DialogueExtractionProgress: Emitting completed with result:', result)
      emit('completed', result)
    } else if (status.dialogue_extraction_status === 'failed') {
      stopPolling()
      emit('failed', statusMessage.value)
    } else if (status.dialogue_extraction_status === 'cancelled') {
      stopPolling()
      emit('cancelled')
    }
  } catch (error) {
    console.error('Erreur lors de la récupération du statut:', error)
  }
}

// Démarrer le polling
const startPolling = () => {
  fetchStatus() // Premier appel immédiat
  pollingInterval = setInterval(fetchStatus, 2000) // Puis toutes les 2 secondes
}

// Arrêter le polling
const stopPolling = () => {
  if (pollingInterval) {
    clearInterval(pollingInterval)
    pollingInterval = null
  }
}

// Annuler l'extraction
const handleCancel = async () => {
  if (isCancelling.value) return

  if (confirm('Êtes-vous sûr de vouloir annuler l\'extraction ? Les données déjà créées seront supprimées.')) {
    isCancelling.value = true
    try {
      await cancelDialogueExtraction(props.projectId)
      stopPolling()
      emit('cancelled')
    } catch (error) {
      console.error('Erreur lors de l\'annulation:', error)
      alert('Impossible d\'annuler l\'extraction')
    } finally {
      isCancelling.value = false
    }
  }
}

// Watch pour démarrer/arrêter le polling selon l'état de la modal
watch(() => props.show, (newValue) => {
  if (newValue) {
    // Reset des valeurs
    progress.value = 0
    statusMessage.value = 'Initialisation...'
    timecodesCount.value = 0
    charactersCount.value = 0
    isCancelling.value = false

    // Démarrer le polling
    startPolling()
  } else {
    // Arrêter le polling quand la modal se ferme
    stopPolling()
  }
})

onUnmounted(() => {
  stopPolling()
})
</script>
