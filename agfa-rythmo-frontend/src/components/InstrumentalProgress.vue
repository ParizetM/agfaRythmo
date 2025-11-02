<template>
  <BaseModal
    :show="show"
    title="Génération de la piste instrumentale"
    @update:show="() => {}"
    :close-on-backdrop="false"
    :hide-close="true"
    size="lg"
    icon-gradient="bg-gradient-to-br from-violet-500 to-pink-500"
  >
    <template #icon>
      <div class="relative">
        <svg class="w-8 h-8 text-white animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" class="opacity-50" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
        </svg>
        <div class="absolute -top-1 -right-1 w-3 h-3 bg-violet-500 rounded-full animate-ping"></div>
      </div>
    </template>

    <template #subtitle>
      Extraction de la piste sans voix (instrumental)
    </template>

    <div class="space-y-6">
      <!-- Barre de progression -->
      <div>
        <div class="flex items-center justify-between mb-2">
          <span class="text-sm text-gray-400">Progression</span>
          <span class="text-sm font-medium text-white">{{ progress }}%</span>
        </div>
        <div class="w-full h-2 bg-gray-700 rounded-full overflow-hidden">
          <div
            class="h-full bg-gradient-to-r from-violet-500 to-pink-500 transition-all duration-300"
            :style="{ width: progress + '%' }"
          ></div>
        </div>
      </div>

      <!-- Message de statut -->
      <div
        :class="[
          'px-4 py-3 rounded-lg border text-sm',
          isCompleted
            ? 'bg-green-500/10 border-green-500/30 text-green-400'
            : isFailed
              ? 'bg-red-500/10 border-red-500/30 text-red-400'
              : 'bg-violet-500/10 border-violet-500/30 text-violet-400',
        ]"
      >
        <div class="flex items-start gap-2">
          <CheckCircleIcon v-if="isCompleted" class="h-5 w-5 flex-shrink-0 mt-0.5" />
          <XCircleIcon v-else-if="isFailed" class="h-5 w-5 flex-shrink-0 mt-0.5" />
          <ArrowPathIcon v-else class="h-5 w-5 flex-shrink-0 mt-0.5 animate-spin" />
          <span>{{ statusMessage }}</span>
        </div>
      </div>

      <!-- Étapes de traitement -->
      <div class="space-y-3">
        <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Étapes</div>

        <!-- Étape 1: Extraction audio -->
        <div class="flex items-center gap-3">
          <div :class="[
            'w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0',
            progress >= 10 ? 'bg-violet-500/20 text-violet-400' : 'bg-gray-700 text-gray-500'
          ]">
            <CheckCircleIcon v-if="progress >= 10" class="w-5 h-5" />
            <span v-else class="text-xs font-bold">1</span>
          </div>
          <div class="flex-1">
            <p class="text-sm font-medium text-white">Extraction audio</p>
            <p class="text-xs text-gray-400">Conversion de la vidéo en audio</p>
          </div>
        </div>

        <!-- Étape 2: Séparation des sources -->
        <div class="flex items-center gap-3">
          <div :class="[
            'w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0',
            progress >= 30 ? 'bg-violet-500/20 text-violet-400' : 'bg-gray-700 text-gray-500'
          ]">
            <CheckCircleIcon v-if="progress >= 90" class="w-5 h-5" />
            <ArrowPathIcon v-else-if="progress >= 30" class="w-5 h-5 animate-spin" />
            <span v-else class="text-xs font-bold">2</span>
          </div>
          <div class="flex-1">
            <p class="text-sm font-medium text-white">Séparation Demucs</p>
            <p class="text-xs text-gray-400">Isolation des sources sonores (2-5 min)</p>
          </div>
        </div>

        <!-- Étape 3: Finalisation -->
        <div class="flex items-center gap-3">
          <div :class="[
            'w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0',
            progress >= 90 ? 'bg-violet-500/20 text-violet-400' : 'bg-gray-700 text-gray-500'
          ]">
            <CheckCircleIcon v-if="progress === 100" class="w-5 h-5" />
            <ArrowPathIcon v-else-if="progress >= 90" class="w-5 h-5 animate-spin" />
            <span v-else class="text-xs font-bold">3</span>
          </div>
          <div class="flex-1">
            <p class="text-sm font-medium text-white">Enregistrement</p>
            <p class="text-xs text-gray-400">Sauvegarde du fichier instrumental</p>
          </div>
        </div>
      </div>

      <!-- Info technique -->
      <div class="bg-gray-800/30 border border-gray-700/50 rounded-lg p-4">
        <div class="flex items-start gap-3">
          <svg class="w-5 h-5 text-violet-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <div class="text-xs text-gray-400">
            <p class="mb-1">
              <span class="font-semibold text-white">Piste instrumentale :</span> Musique + Effets sonores + Ambiance
            </p>
            <p>
              <span class="font-semibold text-white">Exclu :</span> Dialogues et voix
            </p>
          </div>
        </div>
      </div>
    </div>

    <template #footer>
      <!-- Bouton fermer (quand terminé ou échoué) -->
      <button
        v-if="isCompleted || isFailed"
        @click="handleClose"
        class="flex-1 px-4 py-2 bg-gradient-to-r from-violet-500 to-pink-500 text-white rounded-lg font-medium hover:from-violet-600 hover:to-pink-600 transition-all"
      >
        Fermer
      </button>

      <!-- Message pendant le traitement -->
      <div v-else class="flex-1 text-center text-sm text-gray-400 italic">
        Veuillez patienter, ce processus peut prendre plusieurs minutes...
      </div>
    </template>
  </BaseModal>
</template>

<script setup lang="ts">
import { ref, computed, watch, onUnmounted } from 'vue'
import BaseModal from './BaseModal.vue'
import {
  CheckCircleIcon,
  XCircleIcon,
  ArrowPathIcon,
} from '@heroicons/vue/24/outline'
import { getInstrumentalStatus } from '@/api/instrumental'
import type { InstrumentalStatus } from '@/api/instrumental'

interface Props {
  projectId: number
  show: boolean
}

interface Emits {
  (e: 'completed'): void
  (e: 'failed', message: string): void
  (e: 'update:show', value: boolean): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const status = ref<InstrumentalStatus>({
  status: 'processing',
  progress: 0,
  audio_path: null,
})

const progress = ref(0)
let pollingInterval: number | null = null

const isCompleted = computed(() => status.value.status === 'completed')
const isFailed = computed(() => status.value.status === 'failed')

const statusMessage = computed(() => {
  if (isCompleted.value) {
    return 'Piste instrumentale générée avec succès ! 🎉'
  }
  if (isFailed.value) {
    return 'Échec de la génération. Veuillez réessayer.'
  }

  // Messages selon la progression
  if (progress.value < 10) {
    return 'Initialisation...'
  } else if (progress.value < 30) {
    return 'Extraction de l\'audio de la vidéo...'
  } else if (progress.value < 90) {
    return 'Séparation des sources audio avec Demucs (cela peut prendre quelques minutes)...'
  } else if (progress.value < 100) {
    return 'Enregistrement du fichier instrumental...'
  }

  return 'Traitement en cours...'
})

const fetchStatus = async () => {
  try {
    const data = await getInstrumentalStatus(props.projectId)
    status.value = data
    progress.value = data.progress

    // Arrêter le polling si terminé
    if (data.status === 'completed' || data.status === 'failed') {
      stopPolling()

      // Émettre événement selon le statut
      if (data.status === 'completed') {
        emit('completed')
      } else if (data.status === 'failed') {
        emit('failed', 'Erreur lors de la génération de la piste instrumentale')
      }
    }
  } catch (error) {
    console.error('Erreur lors de la récupération du statut:', error)
    stopPolling()
    emit('failed', 'Erreur lors de la récupération du statut')
  }
}

const startPolling = () => {
  // Premier fetch immédiat
  fetchStatus()

  // Polling toutes les 2 secondes
  pollingInterval = window.setInterval(fetchStatus, 2000)
}

const stopPolling = () => {
  if (pollingInterval !== null) {
    clearInterval(pollingInterval)
    pollingInterval = null
  }
}

const handleClose = () => {
  stopPolling()
  emit('update:show', false)
}

// Watch pour démarrer le polling quand le modal s'ouvre
watch(
  () => props.show,
  (newValue) => {
    if (newValue) {
      startPolling()
    } else {
      stopPolling()
    }
  },
  { immediate: true }
)

onUnmounted(() => {
  stopPolling()
})
</script>
