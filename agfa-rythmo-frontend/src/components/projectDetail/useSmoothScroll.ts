// Patch d'animation smooth pour la bande rythmo
// Utilise requestAnimationFrame pour interpoler la position de scroll
// À intégrer dans RythmoBand.vue


import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
import type { Ref } from 'vue';

/**
 * Hook de scroll smooth, avec possibilité de forcer un scroll instantané (sans animation)
 * @param targetScroll fonction qui retourne la position cible
 * @param instant ref booléen : true = scroll instantané, false = smooth
 */
export function useSmoothScroll(targetScroll: () => number, instant?: Ref<boolean>) {
  const smoothScroll = ref(0);
  let rafId: number | null = null;

  function animate() {
    if (instant && instant.value) {
      // Mode instantané : snap immédiatement sans animation
      smoothScroll.value = targetScroll();
      rafId = null;
      return;
    }
    const target = targetScroll();
    const diff = target - smoothScroll.value;

    // Seuil beaucoup plus strict pour éviter les micro-saccades
    if (Math.abs(diff) > 0.01) {
      // Interpolation plus douce avec easing
      smoothScroll.value += diff * 0.08;
      rafId = requestAnimationFrame(animate);
    } else {
      // Snap précis à la valeur cible pour éviter les oscillations
      smoothScroll.value = target;
      rafId = null;
    }
  }

  // Watch séparé sur instant pour réagir immédiatement au passage en mode pause
  watch(instant ?? ref(false), (isInstant, wasInstant) => {
    if (isInstant && !wasInstant) {
      // Passage de smooth à instant (pause) : arrêt immédiat
      if (rafId) {
        cancelAnimationFrame(rafId);
        rafId = null;
      }
      smoothScroll.value = targetScroll();
    } else if (!isInstant && wasInstant) {
      // Passage de instant à smooth (play) : relancer l'animation
      if (rafId) {
        cancelAnimationFrame(rafId);
        rafId = null;
      }
      animate();
    }
  }, { immediate: false });

  // Watch sur la cible pour relancer l'animation
  watch(targetScroll, () => {
    if (rafId) {
      cancelAnimationFrame(rafId);
      rafId = null;
    }
    animate();
  });

  onMounted(() => animate());
  onBeforeUnmount(() => { if (rafId) cancelAnimationFrame(rafId); });
  return smoothScroll;
}
