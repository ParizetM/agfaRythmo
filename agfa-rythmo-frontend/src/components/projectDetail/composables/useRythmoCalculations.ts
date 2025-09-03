import { computed, type Ref } from 'vue'

export interface Timecode {
  id?: number
  start: number
  end: number
  text: string
  line_number: number
  character_id?: number | null
  character?: {
    id: number
    name: string
    color: string
  }
  show_character?: boolean
}

// Configuration des calculs rythmo
export const RYTHMO_CONFIG = {
  PX_PER_SEC: 80,
  MIN_BLOCK_WIDTH: 40,
  TICK_INTERVAL: 0.2, // secondes entre petits traits
  LINE_HEIGHT: 48, // hauteur d'une ligne en pixels
} as const

/**
 * Composable pour tous les calculs liés à la bande rythmo
 */
export function useRythmoCalculations(
  timecodes: Ref<Timecode[]>,
  videoDuration: Ref<number | undefined>,
  visibleWidth: Ref<number>
) {
  // Calcul de la durée totale
  const totalDuration = computed(() => {
    if (videoDuration.value && videoDuration.value > 0) return videoDuration.value
    if (!timecodes.value.length) return 1
    return timecodes.value[timecodes.value.length - 1].end
  })

  // Largeur totale de la bande
  const bandWidth = computed(() => totalDuration.value * RYTHMO_CONFIG.PX_PER_SEC)

  // Offset pour centrer le curseur
  const centerOffset = computed(() => visibleWidth.value / 2)

  /**
   * Convertit un temps en position X (pixels)
   */
  function timeToPixels(time: number): number {
    return time * RYTHMO_CONFIG.PX_PER_SEC + centerOffset.value
  }

  /**
   * Convertit une position X (pixels) en temps
   */
  function pixelsToTime(pixels: number): number {
    return (pixels - centerOffset.value) / RYTHMO_CONFIG.PX_PER_SEC
  }

  /**
   * Calcule la largeur d'un bloc de timecode
   */
  function getBlockWidth(timecode: Timecode): number {
    return Math.max(RYTHMO_CONFIG.MIN_BLOCK_WIDTH, (timecode.end - timecode.start) * RYTHMO_CONFIG.PX_PER_SEC)
  }

  /**
   * Calcule la position X d'un bloc de timecode
   */
  function getBlockX(timecode: Timecode): number {
    return timeToPixels(timecode.start)
  }

  /**
   * Calcule la largeur d'un gap entre deux timecodes
   */
  function getGapWidth(start: number, end: number): number {
    return Math.max(10, (end - start) * RYTHMO_CONFIG.PX_PER_SEC)
  }

  /**
   * Calcule la position X d'un gap
   */
  function getGapX(start: number): number {
    return timeToPixels(start)
  }

  /**
   * Génère les ticks (graduations) de la timeline
   */
  const ticks = computed(() => {
    const arr: Array<{ x: number; isSecond: boolean }> = []
    const duration = totalDuration.value

    for (let t = 0; t <= duration; t += RYTHMO_CONFIG.TICK_INTERVAL) {
      arr.push({
        x: timeToPixels(t),
        isSecond: Math.abs(t % 1) < 0.01 || Math.abs((t % 1) - 1) < 0.01,
      })
    }
    return arr
  })

  /**
   * Calcule les positions des changements de plan
   */
  function getSceneChangePositions(sceneChanges: number[]): number[] {
    if (!sceneChanges || !sceneChanges.length) return []
    return sceneChanges.map(timeToPixels)
  }

  /**
   * Calcule le scroll target basé sur le temps actuel
   */
  function getScrollTarget(currentTime: number): number {
    const maxScroll = Math.max(0, bandWidth.value + visibleWidth.value)

    // Si la bande est plus courte que la fenêtre, scroll=0
    if (bandWidth.value <= visibleWidth.value) return 0

    // Sinon, scroll jusqu'à la position actuelle
    return Math.min(currentTime * RYTHMO_CONFIG.PX_PER_SEC, maxScroll)
  }

  /**
   * Trouve l'index du timecode actif au temps donné
   */
  function getActiveTimecodeIndex(currentTime: number, offset: number = -0.2): number {
    const adjustedTime = currentTime + offset
    return timecodes.value.findIndex(tc => adjustedTime >= tc.start && adjustedTime < tc.end)
  }

  /**
   * Calcule le style de distorsion du texte pour s'adapter au bloc
   */
  function getTextDistortStyle(timecode: Timecode): { transform: string; width: string } {
    const blockWidth = getBlockWidth(timecode)
    const text = timecode.text || ''

    // Créer un span temporaire pour mesurer la largeur réelle du texte
    const span = document.createElement('span')
    span.style.visibility = 'hidden'
    span.style.position = 'absolute'
    span.style.whiteSpace = 'pre'
    span.style.fontSize = '1.8rem'
    span.style.fontFamily = 'inherit'
    span.innerText = text
    document.body.appendChild(span)

    const textWidth = span.offsetWidth || 1 // éviter division par zéro
    document.body.removeChild(span)

    // Le scaleX est le ratio entre la largeur du bloc et celle du texte
    const scaleX = blockWidth / textWidth

    return {
      transform: `scaleX(${scaleX})`,
      width: '100%',
    }
  }

  /**
   * Calcule la ligne cible basée sur un déplacement Y
   */
  function getTargetLine(currentLine: number, deltaY: number, maxLines: number = 6): number {
    const lineOffset = Math.round(deltaY / RYTHMO_CONFIG.LINE_HEIGHT)
    return Math.max(1, Math.min(maxLines, currentLine + lineOffset))
  }

  return {
    // Computed values
    totalDuration,
    bandWidth,
    centerOffset,
    ticks,

    // Functions
    timeToPixels,
    pixelsToTime,
    getBlockWidth,
    getBlockX,
    getGapWidth,
    getGapX,
    getSceneChangePositions,
    getScrollTarget,
    getActiveTimecodeIndex,
    getTextDistortStyle,
    getTargetLine,

    // Constants
    RYTHMO_CONFIG,
  }
}
