<template>
  <div class="relative inline-block">
    <!-- État: Initial (not_generated) - Bouton icône -->
    <button
      v-if="buttonState === 'initial'"
      @click="handleInitialClick"
      class="flex items-center justify-center w-10 h-10 rounded-lg bg-gray-700 hover:bg-gray-600 transition-colors"
      title="Générer audio instrumental (sans voix)"
    >
      <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" class="opacity-50" />
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
      </svg>
    </button>

    <!-- État: Processing - Bouton cliquable qui ouvre la modal -->
    <button
      v-else-if="buttonState === 'processing'"
      @click="showProgressModal = true"
      class="flex items-center justify-center w-10 h-10 rounded-lg bg-violet-600 relative overflow-hidden cursor-pointer hover:bg-violet-700 transition-colors"
      title="Génération en cours - Cliquer pour voir la progression"
    >
      <!-- Barre de progression -->
      <div
        class="absolute inset-0 bg-gradient-to-r from-violet-500 to-pink-500 transition-all duration-300"
        :style="{ width: `${progress}%` }"
      ></div>

      <!-- Spinner -->
      <svg class="w-5 h-5 text-white animate-spin relative z-10" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
    </button>

    <!-- État: Toggle (completed) - Bouton ON/OFF avec X au hover -->
    <div
      v-else-if="buttonState === 'toggle'"
      class="relative group"
      @mouseenter="showDeleteButton = true"
      @mouseleave="showDeleteButton = false"
    >
      <button
        @click="handleToggle"
        :class="[
          'flex items-center justify-center w-10 h-10 rounded-lg transition-all',
          muteVocals
            ? 'bg-gradient-to-r from-violet-600 to-pink-600 text-white'
            : 'bg-gray-700 hover:bg-gray-600 text-gray-300'
        ]"
        :title="muteVocals ? 'Audio instrumental activé' : 'Audio original activé'"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            v-if="muteVocals"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"
            class="opacity-50"
          />
          <path
            v-if="muteVocals"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"
          />
          <path
            v-else
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"
          />
        </svg>
      </button>

      <!-- Bouton Delete X (apparaît au hover) -->
      <button
        v-if="showDeleteButton"
        @click="handleDelete"
        class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 hover:bg-red-600 rounded-full flex items-center justify-center shadow-lg transition-all z-10"
        title="Supprimer la piste instrumentale"
      >
        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <!-- Modal de confirmation -->
    <ConfirmModal
      v-model:show="showConfirmModal"
      title="Générer audio instrumental ?"
      message="Cette action va créer une piste audio sans les voix (instrumental uniquement: musique, effets sonores, ambiance)."
      details="Durée estimée: 2-5 minutes selon la taille de la vidéo. La vidéo originale ne sera pas modifiée."
      type="info"
      confirmText="Générer"
      @confirm="handleGenerate"
      @cancel="showConfirmModal = false"
    />

    <!-- Modal de progression -->
    <InstrumentalProgress
      v-if="project"
      :show="showProgressModal"
      :project-id="project.id"
      @update:show="showProgressModal = $event"
      @completed="handleProgressCompleted"
      @failed="handleProgressFailed"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onUnmounted } from 'vue'
import { generateInstrumental, getInstrumentalStatus, deleteInstrumental } from '@/api/instrumental'
import { notificationService } from '@/services/notifications'
import ConfirmModal from './ConfirmModal.vue'
import InstrumentalProgress from './InstrumentalProgress.vue'
import type { Project } from '@/api/projects'

interface Props {
  project: Project
  muteVocals: boolean
}

interface Emits {
  (e: 'update:muteVocals', value: boolean): void
  (e: 'update:project', value: Project): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const showConfirmModal = ref(false)
const showProgressModal = ref(false)
const showDeleteButton = ref(false)
const progress = ref(0)
let pollingInterval: number | null = null

// État du bouton
const buttonState = computed(() => {
  if (!props.project.instrumental_audio_path) return 'initial'
  if (props.project.instrumental_status === 'processing') return 'processing'
  return 'toggle'
})

// Handler: Clic initial (ouvre modal de confirmation)
const handleInitialClick = () => {
  showConfirmModal.value = true
}

// Handler: Génération (après confirmation)
const handleGenerate = async () => {
  try {
    showConfirmModal.value = false

    // Lancer la génération
    await generateInstrumental(props.project.id)

    // Mettre à jour le statut du projet localement
    emit('update:project', {
      ...props.project,
      instrumental_status: 'processing',
      instrumental_progress: 0
    })

    // Ouvrir la modal de progression
    showProgressModal.value = true

    // Démarrer le polling
    startPolling()

    // Notification
    showToast('Génération de la piste instrumentale lancée...', 'info')
  } catch (error) {
    console.error('Erreur lors de la génération:', error)
    showToast('Erreur lors du lancement de la génération', 'error')
  }
}

// Handler: Progression complétée
const handleProgressCompleted = () => {
  showProgressModal.value = false
  // Le polling va déjà mettre à jour le projet
  showToast('Piste instrumentale prête !', 'success')
}

// Handler: Progression échouée
const handleProgressFailed = (message: string) => {
  showProgressModal.value = false
  showToast(message || 'Erreur lors de la génération', 'error')
}

// Handler: Toggle ON/OFF
const handleToggle = () => {
  emit('update:muteVocals', !props.muteVocals)
}

// Handler: Suppression
const handleDelete = async () => {
  try {
    showDeleteButton.value = false

    await deleteInstrumental(props.project.id)

    // Mettre à jour le projet
    emit('update:project', {
      ...props.project,
      instrumental_audio_path: null,
      instrumental_status: 'not_generated',
      instrumental_progress: 0
    })

    // Désactiver le mute si actif
    if (props.muteVocals) {
      emit('update:muteVocals', false)
    }

    // Nettoyer le localStorage
    localStorage.removeItem(`muteVocals_${props.project.id}`)

    showToast('Piste instrumentale supprimée', 'success')
  } catch (error) {
    console.error('Erreur lors de la suppression:', error)
    showToast('Erreur lors de la suppression', 'error')
  }
}

// Polling du statut pendant la génération
const startPolling = () => {
  if (pollingInterval) return

  pollingInterval = window.setInterval(async () => {
    try {
      const status = await getInstrumentalStatus(props.project.id)

      // Mettre à jour la progression
      progress.value = status.progress

      // Mettre à jour le projet
      emit('update:project', {
        ...props.project,
        instrumental_status: status.status,
        instrumental_progress: status.progress,
        instrumental_audio_path: status.audio_path
      })

      // Arrêter le polling si terminé
      if (status.status === 'completed') {
        stopPolling()
        showToast('Piste instrumentale prête !', 'success')
      } else if (status.status === 'failed') {
        stopPolling()
        showToast('Erreur lors de la génération de la piste instrumentale', 'error')
      }
    } catch (error) {
      console.error('Erreur lors du polling:', error)
    }
  }, 2000) // Poll toutes les 2 secondes
}

const stopPolling = () => {
  if (pollingInterval) {
    clearInterval(pollingInterval)
    pollingInterval = null
  }
}

// Watch: Démarrer le polling si déjà en cours de processing au montage
watch(
  () => props.project.instrumental_status,
  (newStatus) => {
    if (newStatus === 'processing') {
      startPolling()
    }
  },
  { immediate: true }
)

// Cleanup au démontage
onUnmounted(() => {
  stopPolling()
})

// Helper: Toast notifications
const showToast = (message: string, type: 'success' | 'error' | 'info') => {
  if (type === 'success') {
    notificationService.success('Succès', message)
  } else if (type === 'error') {
    notificationService.error('Erreur', message)
  } else {
    notificationService.info('Info', message)
  }
}

// Exposer handleInitialClick pour accès programmatique depuis le parent
defineExpose({
  handleInitialClick
})

</script>
