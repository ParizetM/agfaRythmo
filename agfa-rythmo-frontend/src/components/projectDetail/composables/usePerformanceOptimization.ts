import { ref, computed, nextTick } from 'vue'

/**
 * Composable pour la virtualisation des listes longues
 * Optimise les performances en ne rendant que les éléments visibles
 */
export function useVirtualization<T>(
  items: T[],
  itemHeight: number,
  containerHeight: number,
  buffer: number = 5
) {
  const scrollTop = ref(0)
  const containerRef = ref<HTMLElement>()

  // Index des éléments visibles
  const startIndex = computed(() => {
    return Math.max(0, Math.floor(scrollTop.value / itemHeight) - buffer)
  })

  const endIndex = computed(() => {
    const visibleCount = Math.ceil(containerHeight / itemHeight)
    return Math.min(items.length - 1, startIndex.value + visibleCount + buffer * 2)
  })

  // Éléments visibles uniquement
  const visibleItems = computed(() => {
    return items.slice(startIndex.value, endIndex.value + 1).map((item, index) => ({
      item,
      index: startIndex.value + index,
      top: (startIndex.value + index) * itemHeight
    }))
  })

  // Hauteur totale pour le scrollbar
  const totalHeight = computed(() => items.length * itemHeight)

  // Offset pour positionner les éléments visibles
  const offsetY = computed(() => startIndex.value * itemHeight)

  function handleScroll(event: Event) {
    const target = event.target as HTMLElement
    scrollTop.value = target.scrollTop
  }

  function scrollToIndex(index: number) {
    if (containerRef.value) {
      const targetScroll = index * itemHeight
      containerRef.value.scrollTop = targetScroll
      scrollTop.value = targetScroll
    }
  }

  return {
    containerRef,
    visibleItems,
    totalHeight,
    offsetY,
    handleScroll,
    scrollToIndex
  }
}

/**
 * Debounce des calculs coûteux
 */
// eslint-disable-next-line @typescript-eslint/no-explicit-any
export function useDebounce<T extends (...args: any[]) => any>(
  func: T,
  delay: number
): (...args: Parameters<T>) => void {
  let timeoutId: number

  return (...args: Parameters<T>) => {
    clearTimeout(timeoutId)
    timeoutId = window.setTimeout(() => func(...args), delay)
  }
}

/**
 * Throttle pour les événements fréquents
 */
// eslint-disable-next-line @typescript-eslint/no-explicit-any
export function useThrottle<T extends (...args: any[]) => any>(
  func: T,
  delay: number
): (...args: Parameters<T>) => void {
  let lastCall = 0

  return (...args: Parameters<T>) => {
    const now = Date.now()
    if (now - lastCall >= delay) {
      lastCall = now
      func(...args)
    }
  }
}

/**
 * Cache LRU simple pour éviter les recalculs
 */
export function useLRUCache<K, V>(maxSize: number = 100) {
  const cache = new Map<K, V>()

  function get(key: K): V | undefined {
    if (cache.has(key)) {
      // Déplacer en fin pour marquer comme récemment utilisé
      const value = cache.get(key)!
      cache.delete(key)
      cache.set(key, value)
      return value
    }
    return undefined
  }

  function set(key: K, value: V): void {
    if (cache.has(key)) {
      cache.delete(key)
    } else if (cache.size >= maxSize) {
      // Supprimer le plus ancien
      const firstKey = cache.keys().next().value as K
      if (firstKey !== undefined) {
        cache.delete(firstKey)
      }
    }
    cache.set(key, value)
  }

  function clear(): void {
    cache.clear()
  }

  return { get, set, clear }
}

/**
 * Gestion optimisée des updates batch
 */
export function useBatchUpdates() {
  const pendingUpdates = ref<(() => void)[]>([])
  let isUpdatePending = false

  function batchUpdate(updateFn: () => void) {
    pendingUpdates.value.push(updateFn)

    if (!isUpdatePending) {
      isUpdatePending = true
      nextTick(() => {
        const updates = [...pendingUpdates.value]
        pendingUpdates.value = []
        isUpdatePending = false

        updates.forEach(update => update())
      })
    }
  }

  return { batchUpdate }
}

/**
 * Observer de intersection pour le lazy loading
 */
export function useIntersectionObserver(
  callback: (entries: IntersectionObserverEntry[]) => void,
  options?: IntersectionObserverInit
) {
  const observer = ref<IntersectionObserver>()
  const isSupported = typeof IntersectionObserver !== 'undefined'

  function observe(element: Element) {
    if (!isSupported) return

    if (!observer.value) {
      observer.value = new IntersectionObserver(callback, {
        root: null,
        rootMargin: '50px',
        threshold: 0,
        ...options
      })
    }

    observer.value.observe(element)
  }

  function unobserve(element: Element) {
    observer.value?.unobserve(element)
  }

  function disconnect() {
    observer.value?.disconnect()
    observer.value = undefined
  }

  return {
    isSupported,
    observe,
    unobserve,
    disconnect
  }
}

/**
 * Mesure des performances
 */
export function usePerformanceMonitor() {
  const metrics = ref<{
    renderTime: number
    updateCount: number
    lastUpdate: number
  }>({
    renderTime: 0,
    updateCount: 0,
    lastUpdate: 0
  })

  function measureRender<T>(renderFn: () => T): T {
    const start = performance.now()
    const result = renderFn()
    const end = performance.now()

    metrics.value.renderTime = end - start
    metrics.value.updateCount++
    metrics.value.lastUpdate = Date.now()

    return result
  }

  function logPerformance(label: string) {
    console.log(`[Performance] ${label}:`, {
      renderTime: `${metrics.value.renderTime.toFixed(2)}ms`,
      updateCount: metrics.value.updateCount,
      lastUpdate: new Date(metrics.value.lastUpdate).toLocaleTimeString()
    })
  }

  return {
    metrics,
    measureRender,
    logPerformance
  }
}
