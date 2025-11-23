/**
 * Composable pour gérer les interactions tactiles et souris de manière unifiée
 * Permet de supporter à la fois desktop (mouse) et mobile (touch) avec le même code
 */

export interface PointerPosition {
  x: number
  y: number
}

export interface PointerEventHandlers {
  onStart: (position: PointerPosition, event: MouseEvent | TouchEvent) => void
  onMove: (position: PointerPosition, event: MouseEvent | TouchEvent) => void
  onEnd: (position: PointerPosition, event: MouseEvent | TouchEvent) => void
}

/**
 * Extrait la position X/Y d'un événement mouse ou touch
 */
function getPointerPosition(event: MouseEvent | TouchEvent): PointerPosition {
  if ('touches' in event) {
    // Touch event
    const touch = event.touches[0] || event.changedTouches[0]
    return {
      x: touch.clientX,
      y: touch.clientY
    }
  } else {
    // Mouse event
    return {
      x: event.clientX,
      y: event.clientY
    }
  }
}

/**
 * Crée des handlers unifiés pour mouse et touch events
 * Gère automatiquement l'ajout/suppression des listeners globaux
 */
export function createPointerHandlers(
  handlers: PointerEventHandlers,
  options: {
    stopPropagation?: boolean
    preventDefault?: boolean
    cursor?: string
  } = {}
) {
  const { stopPropagation = true, preventDefault = true, cursor = 'move' } = options

  let isActive = false
  let startPosition: PointerPosition | null = null

  const handleMove = (event: MouseEvent | TouchEvent) => {
    if (!isActive) return

    const position = getPointerPosition(event)
    handlers.onMove(position, event)
  }

  const handleEnd = (event: MouseEvent | TouchEvent) => {
    if (!isActive) return

    isActive = false
    const position = getPointerPosition(event)
    handlers.onEnd(position, event)

    // Cleanup global listeners
    document.removeEventListener('mousemove', handleMove)
    document.removeEventListener('mouseup', handleEnd)
    document.removeEventListener('touchmove', handleMove)
    document.removeEventListener('touchend', handleEnd)
    document.removeEventListener('touchcancel', handleEnd)
    document.body.style.cursor = ''
  }

  const handleStart = (event: MouseEvent | TouchEvent) => {
    if (stopPropagation) event.stopPropagation()
    if (preventDefault) event.preventDefault()

    isActive = true
    startPosition = getPointerPosition(event)

    handlers.onStart(startPosition, event)

    // Add global listeners for move and end
    document.addEventListener('mousemove', handleMove, { passive: false })
    document.addEventListener('mouseup', handleEnd)
    document.addEventListener('touchmove', handleMove, { passive: false })
    document.addEventListener('touchend', handleEnd)
    document.addEventListener('touchcancel', handleEnd)
    document.body.style.cursor = cursor
  }

  // Cleanup function to remove any remaining listeners
  const cleanup = () => {
    if (isActive) {
      document.removeEventListener('mousemove', handleMove)
      document.removeEventListener('mouseup', handleEnd)
      document.removeEventListener('touchmove', handleMove)
      document.removeEventListener('touchend', handleEnd)
      document.removeEventListener('touchcancel', handleEnd)
      document.body.style.cursor = ''
      isActive = false
    }
  }

  return {
    handleStart,
    cleanup,
    isActive: () => isActive
  }
}

/**
 * Crée un handler pour le scroll tactile horizontal
 */
export function createScrollHandler(
  onScroll: (deltaX: number, deltaY: number) => void,
  options: {
    preventDefault?: boolean
    threshold?: number
  } = {}
) {
  const { preventDefault = true, threshold = 5 } = options

  let touchStartX = 0
  let touchStartY = 0
  let lastTouchX = 0
  let lastTouchY = 0
  let isScrolling = false

  const handleTouchStart = (event: TouchEvent) => {
    const touch = event.touches[0]
    touchStartX = touch.clientX
    touchStartY = touch.clientY
    lastTouchX = touch.clientX
    lastTouchY = touch.clientY
    isScrolling = false
  }

  const handleTouchMove = (event: TouchEvent) => {
    const touch = event.touches[0]
    const deltaX = lastTouchX - touch.clientX
    const deltaY = lastTouchY - touch.clientY

    // Détecter la direction du scroll au premier mouvement significatif
    if (!isScrolling) {
      const totalDeltaX = Math.abs(touch.clientX - touchStartX)
      const totalDeltaY = Math.abs(touch.clientY - touchStartY)

      if (totalDeltaX > threshold || totalDeltaY > threshold) {
        isScrolling = true
        // Si le mouvement horizontal est dominant, empêcher le scroll vertical
        if (totalDeltaX > totalDeltaY && preventDefault) {
          event.preventDefault()
        }
      }
    } else {
      // Continuer à empêcher le scroll par défaut si on scrolle horizontalement
      const totalDeltaX = Math.abs(touch.clientX - touchStartX)
      const totalDeltaY = Math.abs(touch.clientY - touchStartY)

      if (totalDeltaX > totalDeltaY && preventDefault) {
        event.preventDefault()
      }
    }

    if (isScrolling) {
      onScroll(deltaX, deltaY)
    }

    lastTouchX = touch.clientX
    lastTouchY = touch.clientY
  }

  const handleTouchEnd = () => {
    isScrolling = false
  }

  return {
    handleTouchStart,
    handleTouchMove,
    handleTouchEnd
  }
}
