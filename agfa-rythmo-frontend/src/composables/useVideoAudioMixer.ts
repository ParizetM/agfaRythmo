import { ref, watch, type Ref, type ComputedRef } from 'vue'

// Extension du type HTMLVideoElement pour stocker les listeners
interface VideoElementWithListeners extends HTMLVideoElement {
  _audioMixerListeners?: {
    handlePlay: () => void
    handlePause: () => void
    handleSeeking: () => void
    handleSeeked: () => void
    handleRateChange: () => void
  }
}

/**
 * Composable pour switcher entre l'audio original de la vidéo et une piste audio externe
 * Mute la vidéo et joue l'audio instrumental synchronisé
 */
export function useVideoAudioMixer(
  videoElement: HTMLVideoElement | null,
  instrumentalAudioUrl: Ref<string | null> | ComputedRef<string | null>,
  muteVocals: Ref<boolean>
) {
  const instrumentalElement = ref<HTMLAudioElement | null>(null)

  /**
   * Initialise (charge l'audio instrumental)
   */
  const init = () => {
    // Rien à faire, init lazy au premier switch
  }

  /**
   * Charge l'audio instrumental
   */
  const loadInstrumental = async () => {
    if (!instrumentalAudioUrl.value || instrumentalElement.value) return

    instrumentalElement.value = new Audio(instrumentalAudioUrl.value)
    instrumentalElement.value.preload = 'auto'
    instrumentalElement.value.crossOrigin = 'anonymous'

    // Attendre que l'audio soit chargé
    await new Promise((resolve) => {
      if (instrumentalElement.value) {
        instrumentalElement.value.addEventListener('loadedmetadata', resolve, { once: true })
      }
    })
  }

  /**
   * Switch entre audio original et instrumental
   */
  const switchAudio = async () => {
    if (!videoElement) return

    if (muteVocals.value && instrumentalAudioUrl.value) {
      // Mode instrumental : mute vidéo et joue instrumental
      if (!instrumentalElement.value) {
        await loadInstrumental()
      }

      if (!instrumentalElement.value) return

      // Mute la vidéo
      videoElement.muted = true

      // Synchroniser l'instrumental avec la vidéo
      instrumentalElement.value.currentTime = videoElement.currentTime
      instrumentalElement.value.playbackRate = videoElement.playbackRate

      if (!videoElement.paused) {
        instrumentalElement.value.play().catch((e) => console.error('Play error:', e))
      }

      // Ajouter les listeners de synchronisation
      syncVideoWithInstrumental()
    } else {
      // Mode original : unmute vidéo et pause instrumental
      videoElement.muted = false

      if (instrumentalElement.value) {
        instrumentalElement.value.pause()
      }

      // Retirer les listeners de sync
      removeVideoListeners()
    }
  }

  /**
   * Synchronise l'audio instrumental avec la vidéo
   */
  const syncVideoWithInstrumental = () => {
    if (!videoElement || !instrumentalElement.value) return

    const handlePlay = () => {
      if (instrumentalElement.value && muteVocals.value) {
        instrumentalElement.value.play().catch((e) => console.error('Play error:', e))
      }
    }

    const handlePause = () => {
      if (instrumentalElement.value) {
        instrumentalElement.value.pause()
      }
    }

    const handleSeeking = () => {
      if (instrumentalElement.value && videoElement) {
        instrumentalElement.value.currentTime = videoElement.currentTime
      }
    }

    const handleSeeked = () => {
      // Resynchroniser après seek
      if (instrumentalElement.value && videoElement && muteVocals.value) {
        instrumentalElement.value.currentTime = videoElement.currentTime
        if (!videoElement.paused) {
          instrumentalElement.value.play().catch((e) => console.error('Play error:', e))
        }
      }
    }

    const handleRateChange = () => {
      if (instrumentalElement.value && videoElement) {
        instrumentalElement.value.playbackRate = videoElement.playbackRate
      }
    }

    videoElement.addEventListener('play', handlePlay)
    videoElement.addEventListener('pause', handlePause)
    videoElement.addEventListener('seeking', handleSeeking)
    videoElement.addEventListener('seeked', handleSeeked)
    videoElement.addEventListener('ratechange', handleRateChange)

    // Stocker les références pour cleanup
    const videoWithListeners = videoElement as VideoElementWithListeners
    videoWithListeners._audioMixerListeners = {
      handlePlay,
      handlePause,
      handleSeeking,
      handleSeeked,
      handleRateChange
    }
  }

  /**
   * Retire les event listeners
   */
  const removeVideoListeners = () => {
    if (!videoElement) return

    const videoWithListeners = videoElement as VideoElementWithListeners
    const listeners = videoWithListeners._audioMixerListeners
    if (!listeners) return

    videoElement.removeEventListener('play', listeners.handlePlay)
    videoElement.removeEventListener('pause', listeners.handlePause)
    videoElement.removeEventListener('seeking', listeners.handleSeeking)
    videoElement.removeEventListener('seeked', listeners.handleSeeked)
    videoElement.removeEventListener('ratechange', listeners.handleRateChange)

    delete videoWithListeners._audioMixerListeners
  }

  /**
   * Cleanup
   */
  const cleanup = () => {
    removeVideoListeners()

    if (instrumentalElement.value) {
      instrumentalElement.value.pause()
      instrumentalElement.value.src = ''
      instrumentalElement.value = null
    }

    // Restore video audio
    if (videoElement) {
      videoElement.muted = false
    }
  }

  // Watch changements
  watch(muteVocals, () => {
    switchAudio()
  })

  watch(instrumentalAudioUrl, async (newUrl) => {
    if (newUrl && muteVocals.value) {
      await loadInstrumental()
      switchAudio()
    }
  })

  return {
    init,
    switchAudio,
    cleanup
  }
}
