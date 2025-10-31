/**
 * Composable pour gérer les capacités du serveur
 * Détecte automatiquement les fonctionnalités disponibles (FFmpeg, workers, etc.)
 */
import { ref, readonly } from 'vue'
import { getServerCapabilities, type ServerCapabilities } from '@/api/serverCapabilities'

const capabilities = ref<ServerCapabilities | null>(null)
const isLoading = ref(false)
const error = ref<string | null>(null)

export function useServerCapabilities() {
  /**
   * Charge les capacités du serveur
   */
  const loadCapabilities = async () => {
    // Si déjà chargé, ne pas recharger
    if (capabilities.value !== null) {
      return capabilities.value
    }

    isLoading.value = true
    error.value = null

    try {
      capabilities.value = await getServerCapabilities()
      return capabilities.value
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Erreur lors du chargement des capacités'
      console.error('Erreur chargement capacités serveur:', err)
      // Valeurs par défaut en cas d'erreur (mode dégradé)
      capabilities.value = {
        scene_detection: false,
        dialogue_extraction: false,
        translation: false,
        auto_subtitles: false,
        voice_recognition: false,
        audio_analysis: false
      }
      return capabilities.value
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Réinitialise et recharge les capacités
   */
  const refreshCapabilities = async () => {
    capabilities.value = null
    return await loadCapabilities()
  }

  return {
    capabilities: capabilities as Readonly<typeof capabilities>,
    isLoading: readonly(isLoading),
    error: readonly(error),
    loadCapabilities,
    refreshCapabilities
  }
}
