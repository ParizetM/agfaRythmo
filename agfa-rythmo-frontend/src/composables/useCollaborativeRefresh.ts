import { ref, onMounted, onUnmounted, computed } from 'vue'
import type { Ref } from 'vue'

interface CollaborativeRefreshOptions {
  projectId: Ref<number | null>
  hasCollaborators: Ref<boolean>
  isEditingContent: Ref<boolean>
  onRefresh: () => Promise<void>
  intervalMs?: number
}

export function useCollaborativeRefresh(options: CollaborativeRefreshOptions) {
  const {
    projectId,
    hasCollaborators,
    isEditingContent,
    onRefresh,
    intervalMs = 4000 // 4 secondes par défaut
  } = options

  const isPollingActive = ref(false)
  const lastSyncTime = ref<Date | null>(null)
  const isSyncing = ref(false)
  let pollingInterval: ReturnType<typeof setInterval> | null = null

  // Computed pour déterminer si le polling doit être actif
  const shouldPoll = computed(() => {
    return (
      projectId.value !== null &&
      hasCollaborators.value &&
      !isEditingContent.value
    )
  })

  // Fonction de synchronisation
  async function sync() {
    if (isSyncing.value) return // Éviter les syncs simultanés

    try {
      isSyncing.value = true
      await onRefresh()
      lastSyncTime.value = new Date()
    } catch (error) {
      console.error('Erreur lors de la synchronisation collaborative:', error)
    } finally {
      isSyncing.value = false
    }
  }

  // Démarrer le polling
  function startPolling() {
    if (pollingInterval) return // Déjà actif

    isPollingActive.value = true

    pollingInterval = setInterval(() => {
      if (shouldPoll.value) {
        sync()
      }
    }, intervalMs)
  }

  // Arrêter le polling
  function stopPolling() {
    if (pollingInterval) {
      clearInterval(pollingInterval)
      pollingInterval = null
      isPollingActive.value = false
    }
  }

  // Synchronisation manuelle
  function forceSyncNow() {
    return sync()
  }

  // Watcher pour démarrer/arrêter automatiquement le polling
  function checkPollingState() {
    if (shouldPoll.value && !isPollingActive.value) {
      startPolling()
    } else if (!shouldPoll.value && isPollingActive.value) {
      stopPolling()
    }
  }

  // Lifecycle hooks
  onMounted(() => {
    checkPollingState()
    // Vérifier périodiquement si on doit démarrer/arrêter le polling
    const stateCheckInterval = setInterval(checkPollingState, 1000)

    onUnmounted(() => {
      clearInterval(stateCheckInterval)
      stopPolling()
    })
  })

  return {
    isPollingActive,
    isSyncing,
    lastSyncTime,
    forceSyncNow,
    startPolling,
    stopPolling
  }
}
