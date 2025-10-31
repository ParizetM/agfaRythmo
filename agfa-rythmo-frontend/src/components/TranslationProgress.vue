<template>
  <BaseModal
    :show="show"
    title="Traduction en cours"
    @update:show="() => {}"
    :close-on-backdrop="false"
    :hide-close="true"
    size="lg"
    icon-gradient="bg-gradient-to-br from-orange-500 to-yellow-500"
  >
    <template #icon>
      <div class="relative">
        <GlobeAltIcon class="w-8 h-8 text-white animate-pulse" />
        <div class="absolute -top-1 -right-1 w-3 h-3 bg-orange-500 rounded-full animate-ping"></div>
      </div>
    </template>

    <template #subtitle>
      {{ status.source_language || 'auto' }} → {{ status.target_language }}
    </template>

    <div class="space-y-6">
      <!-- Barre de progression -->
      <div>
        <div class="flex items-center justify-between mb-2">
          <span class="text-sm text-gray-400">Progression</span>
          <span class="text-sm font-medium text-white">{{ status.translation_progress }}%</span>
        </div>
        <div class="w-full h-2 bg-gray-700 rounded-full overflow-hidden">
          <div
            class="h-full bg-gradient-to-r from-orange-500 to-yellow-500 transition-all duration-300"
            :style="{ width: status.translation_progress + '%' }"
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
              : isCancelled
                ? 'bg-yellow-500/10 border-yellow-500/30 text-yellow-400'
                : 'bg-blue-500/10 border-blue-500/30 text-blue-400',
        ]"
      >
        <div class="flex items-start gap-2">
          <CheckCircleIcon v-if="isCompleted" class="h-5 w-5 flex-shrink-0 mt-0.5" />
          <XCircleIcon v-else-if="isFailed" class="h-5 w-5 flex-shrink-0 mt-0.5" />
          <ExclamationTriangleIcon v-else-if="isCancelled" class="h-5 w-5 flex-shrink-0 mt-0.5" />
          <ArrowPathIcon v-else class="h-5 w-5 flex-shrink-0 mt-0.5 animate-spin" />
          <span>{{ status.translation_message || 'Traitement...' }}</span>
        </div>
      </div>

      <!-- Statistiques -->
      <div class="grid grid-cols-2 gap-4">
        <div class="bg-gray-800/30 border border-gray-700/50 rounded-lg p-4">
          <div class="text-2xl font-bold text-white mb-1">{{ status.timecodes_count }}</div>
          <div class="text-sm text-gray-400">Timecodes totaux</div>
        </div>
        <div class="bg-gray-800/30 border border-gray-700/50 rounded-lg p-4">
          <div class="text-2xl font-bold text-orange-400 mb-1">
            {{ Math.floor((status.timecodes_count * status.translation_progress) / 100) }}
          </div>
          <div class="text-sm text-gray-400">Traduits</div>
        </div>
      </div>
    </div>

    <template #footer>
      <!-- Bouton annuler -->
      <button
        v-if="!isCompleted && !isFailed && !isCancelled"
        @click="handleCancel"
        :disabled="isCancelling"
        class="flex-1 px-4 py-2 bg-red-500/20 text-red-400 border border-red-500/30 rounded-lg hover:bg-red-500/30 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
      >
        {{ isCancelling ? 'Annulation...' : 'Annuler' }}
      </button>

      <!-- Bouton fermer (quand terminé) -->
      <button
        v-if="isCompleted || isFailed || isCancelled"
        @click="handleClose"
        class="flex-1 px-4 py-2 bg-gradient-to-r from-orange-500 to-yellow-500 text-white rounded-lg font-medium hover:from-orange-600 hover:to-yellow-600 transition-all"
      >
        Fermer
      </button>
    </template>
  </BaseModal>
</template>

<script setup lang="ts">
import { ref, computed, watch, onUnmounted } from 'vue'
import BaseModal from './BaseModal.vue'
import {
  GlobeAltIcon,
  CheckCircleIcon,
  XCircleIcon,
  ExclamationTriangleIcon,
  ArrowPathIcon,
} from '@heroicons/vue/24/outline'
import { getTranslationStatus, cancelTranslation } from '@/api/translation'
import type { TranslationStatus } from '@/api/translation'

interface Props {
  projectId: number
  show: boolean
}

interface Emits {
  (e: 'completed'): void
  (e: 'failed', message: string): void
  (e: 'cancelled'): void
  (e: 'update:show', value: boolean): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const status = ref<TranslationStatus>({
  translation_status: 'pending',
  translation_progress: 0,
  translation_message: 'Initialisation...',
  source_language: null,
  target_language: null,
  timecodes_count: 0,
})

const isCancelling = ref(false)
let pollingInterval: number | null = null

const isCompleted = computed(() => status.value.translation_status === 'completed')
const isFailed = computed(() => status.value.translation_status === 'failed')
const isCancelled = computed(() => status.value.translation_status === 'cancelled')

const fetchStatus = async () => {
  try {
    const data = await getTranslationStatus(props.projectId)
    status.value = data

    // Arrêter le polling si terminé
    if (
      data.translation_status === 'completed' ||
      data.translation_status === 'failed' ||
      data.translation_status === 'cancelled'
    ) {
      stopPolling()

      // Émettre événement selon le statut
      if (data.translation_status === 'completed') {
        emit('completed')
      } else if (data.translation_status === 'failed') {
        emit('failed', data.translation_message || 'Erreur inconnue')
      } else if (data.translation_status === 'cancelled') {
        emit('cancelled')
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

const handleCancel = async () => {
  if (isCancelling.value) return

  isCancelling.value = true

  try {
    await cancelTranslation(props.projectId)
  } catch (error) {
    console.error('Erreur lors de l\'annulation:', error)
  } finally {
    isCancelling.value = false
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
    console.log('📊 TranslationProgress: show changed to', newValue)
    if (newValue) {
      console.log('📊 TranslationProgress: Starting polling for project', props.projectId)
      startPolling()
    } else {
      console.log('📊 TranslationProgress: Stopping polling')
      stopPolling()
    }
  },
  { immediate: true }
)

onUnmounted(() => {
  stopPolling()
})
</script>
