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
      smoothScroll.value = targetScroll();
      rafId = null;
      return;
    }
    const diff = targetScroll() - smoothScroll.value;
    if (Math.abs(diff) > 0.1) {
      smoothScroll.value += diff * 0.05;
      rafId = requestAnimationFrame(animate);
    } else {
      smoothScroll.value = targetScroll();
      rafId = null;
    }
  }

  // Watch sur la cible ET sur le mode instantané
  watch([targetScroll, instant ?? ref(false)], () => {
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
