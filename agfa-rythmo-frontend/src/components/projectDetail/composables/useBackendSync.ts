import { ref, computed, watch } from 'vue'
import { useRythmoStore, type Character, type Project } from './useRythmoState'
import type { Timecode } from './useRythmoCalculations'
import { useDebounce, useBatchUpdates } from './usePerformanceOptimization'

interface ApiResponse<T> {
  success: boolean
  data?: T
  error?: string
}

interface SyncOperation {
  type: 'create' | 'update' | 'delete'
  entity: 'timecode' | 'character' | 'project' | 'scene_change'
  id?: number
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  data?: any
  timestamp: number
}

/**
 * Composable pour la synchronisation optimisée avec le backend
 */
export function useBackendSync() {
  const store = useRythmoStore()
  const { batchUpdate } = useBatchUpdates()

  // État de synchronisation
  const isSyncing = ref(false)
  const syncQueue = ref<SyncOperation[]>([])
  const syncErrors = ref<string[]>([])
  const lastSyncAt = ref<Date | null>(null)
  const retryCount = ref(0)
  const maxRetries = 3

  // Configuration
  const syncInterval = 30000 // 30 secondes
  const maxQueueSize = 100

  // Statistiques de sync
  const syncStats = computed(() => ({
    queueSize: syncQueue.value.length,
    errorCount: syncErrors.value.length,
    lastSync: lastSyncAt.value,
    isHealthy: syncErrors.value.length === 0 && syncQueue.value.length < 10
  }))

  // Debounced sync pour éviter trop d'appels
  const debouncedSync = useDebounce(processSyncQueue, 1000)

  // API calls
  async function apiCall<T>(
    method: 'GET' | 'POST' | 'PUT' | 'DELETE',
    endpoint: string,
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    data?: any
  ): Promise<ApiResponse<T>> {
    try {
      const response = await fetch(`/api${endpoint}`, {
        method,
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: data ? JSON.stringify(data) : undefined
      })

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`)
      }

      const result = await response.json()
      return { success: true, data: result }
    } catch (error) {
      console.error(`API Error [${method} ${endpoint}]:`, error)
      return {
        success: false,
        error: error instanceof Error ? error.message : 'Unknown error'
      }
    }
  }

  // Ajouter une opération à la queue
  function queueOperation(operation: Omit<SyncOperation, 'timestamp'>) {
    if (syncQueue.value.length >= maxQueueSize) {
      console.warn('Sync queue is full, dropping oldest operation')
      syncQueue.value.shift()
    }

    syncQueue.value.push({
      ...operation,
      timestamp: Date.now()
    })

    debouncedSync()
  }

  // Traiter la queue de synchronisation
  async function processSyncQueue() {
    if (isSyncing.value || syncQueue.value.length === 0) return

    isSyncing.value = true
    const operations = [...syncQueue.value]
    syncQueue.value = []

    try {
      await batchSyncOperations(operations)
      retryCount.value = 0
      lastSyncAt.value = new Date()
    } catch (error) {
      console.error('Sync failed:', error)

      // Remettre les opérations en queue si pas trop de tentatives
      if (retryCount.value < maxRetries) {
        syncQueue.value.unshift(...operations)
        retryCount.value++

        // Retry avec exponential backoff
        setTimeout(() => {
          processSyncQueue()
        }, Math.pow(2, retryCount.value) * 1000)
      } else {
        syncErrors.value.push(`Failed to sync after ${maxRetries} attempts`)
        retryCount.value = 0
      }
    } finally {
      isSyncing.value = false
    }
  }

  // Traitement en batch des opérations
  async function batchSyncOperations(operations: SyncOperation[]) {
    // Grouper par type d'entité
    const grouped = operations.reduce((acc, op) => {
      if (!acc[op.entity]) acc[op.entity] = []
      acc[op.entity].push(op)
      return acc
    }, {} as Record<string, SyncOperation[]>)

    // Traiter chaque groupe
    for (const [entity, ops] of Object.entries(grouped)) {
      await syncEntityOperations(entity, ops)
    }
  }

  // Synchroniser les opérations pour une entité
  async function syncEntityOperations(entity: string, operations: SyncOperation[]) {
    for (const operation of operations) {
      try {
        await syncSingleOperation(operation)
      } catch (error) {
        console.error(`Failed to sync ${operation.type} ${entity}:`, error)
        throw error
      }
    }
  }

  // Synchroniser une opération unique
  async function syncSingleOperation(operation: SyncOperation) {
    const { type, entity, id, data } = operation

    switch (entity) {
      case 'timecode':
        await syncTimecodeOperation(type, id, data)
        break
      case 'character':
        await syncCharacterOperation(type, id, data)
        break
      case 'scene_change':
        await syncSceneChangeOperation(type, data)
        break
      case 'project':
        await syncProjectOperation(type, id, data)
        break
    }
  }

  // Sync spécifiques par entité
  async function syncTimecodeOperation(
    type: SyncOperation['type'],
    id?: number,
    data?: Partial<Timecode>
  ) {
    switch (type) {
      case 'create':
        const createResponse = await apiCall<Timecode>('POST', '/timecodes', data)
        if (createResponse.success && createResponse.data) {
          // Mettre à jour l'ID temporaire avec l'ID réel
          batchUpdate(() => {
            if (data?.id) {
              store.updateTimecode(data.id, { id: createResponse.data!.id })
            }
          })
        }
        break

      case 'update':
        if (id) {
          await apiCall('PUT', `/timecodes/${id}`, data)
        }
        break

      case 'delete':
        if (id) {
          await apiCall('DELETE', `/timecodes/${id}`)
        }
        break
    }
  }

  async function syncCharacterOperation(
    type: SyncOperation['type'],
    id?: number,
    data?: Partial<Character>
  ) {
    switch (type) {
      case 'create':
        const createResponse = await apiCall<Character>('POST', '/characters', data)
        if (createResponse.success && createResponse.data) {
          batchUpdate(() => {
            if (data?.id) {
              store.updateCharacter(data.id, { id: createResponse.data!.id })
            }
          })
        }
        break

      case 'update':
        if (id) {
          await apiCall('PUT', `/characters/${id}`, data)
        }
        break

      case 'delete':
        if (id) {
          await apiCall('DELETE', `/characters/${id}`)
        }
        break
    }
  }

  // eslint-disable-next-line @typescript-eslint/no-unused-vars, @typescript-eslint/no-explicit-any
  async function syncSceneChangeOperation(type: SyncOperation['type'], data?: any) {
    const projectId = store.project?.id
    if (!projectId) return

    switch (type) {
      case 'create':
      case 'update':
        await apiCall('PUT', `/projects/${projectId}/scene-changes`, {
          scene_changes: store.sceneChanges
        })
        break
    }
  }

  async function syncProjectOperation(
    type: SyncOperation['type'],
    id?: number,
    data?: Partial<Project>
  ) {
    switch (type) {
      case 'update':
        if (id) {
          await apiCall('PUT', `/projects/${id}`, data)
        }
        break
    }
  }

  // Synchronisation complète du projet
  async function fullSync(projectId: number) {
    isSyncing.value = true

    try {
      const response = await apiCall<Project>('GET', `/projects/${projectId}`)

      if (response.success && response.data) {
        batchUpdate(() => {
          store.setProject(response.data!)
        })
        lastSyncAt.value = new Date()
      } else {
        throw new Error(response.error || 'Failed to load project')
      }
    } catch (error) {
      syncErrors.value.push(`Full sync failed: ${error}`)
      throw error
    } finally {
      isSyncing.value = false
    }
  }

  // Sauvegarde complète
  async function saveProject() {
    const projectData = store.getProjectData()
    if (!projectData) return

    isSyncing.value = true

    try {
      const response = await apiCall<Project>('PUT', `/projects/${projectData.id}`, projectData)

      if (response.success) {
        store.hasUnsavedChanges = false
        store.lastSavedAt = new Date()
        lastSyncAt.value = new Date()
      } else {
        throw new Error(response.error || 'Save failed')
      }
    } catch (error) {
      syncErrors.value.push(`Save failed: ${error}`)
      throw error
    } finally {
      isSyncing.value = false
    }
  }

  // Configuration automatique de la sync
  function setupAutoSync() {
    // Écouter les changements du store
    watch(() => store.hasUnsavedChanges, (hasChanges) => {
      if (hasChanges) {
        // Démarrer la sync automatique après modifications
        setTimeout(() => {
          if (store.hasUnsavedChanges && syncQueue.value.length === 0) {
            queueOperation({
              type: 'update',
              entity: 'project',
              id: store.project?.id,
              data: store.getProjectData()
            })
          }
        }, 5000) // 5 secondes après la dernière modification
      }
    })

    // Sync périodique
    setInterval(() => {
      if (!isSyncing.value && store.hasUnsavedChanges) {
        debouncedSync()
      }
    }, syncInterval)
  }

  // Cleanup
  function clearErrors() {
    syncErrors.value = []
  }

  function resetSync() {
    syncQueue.value = []
    syncErrors.value = []
    retryCount.value = 0
    isSyncing.value = false
  }

  return {
    // État
    isSyncing,
    syncStats,
    syncErrors,
    lastSyncAt,

    // Actions
    queueOperation,
    processSyncQueue,
    fullSync,
    saveProject,
    setupAutoSync,

    // Utilitaires
    clearErrors,
    resetSync
  }
}
