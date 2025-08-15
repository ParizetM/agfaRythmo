// Patch d'animation smooth pour la bande rythmo
// Utilise requestAnimationFrame pour interpoler la position de scroll
// À intégrer dans RythmoBand.vue

import { ref, onMounted, onBeforeUnmount, watch } from 'vue';

export function useSmoothScroll(targetScroll: () => number) {
  const smoothScroll = ref(0);
  let rafId: number | null = null;
  function animate() {
    const diff = targetScroll() - smoothScroll.value;
    if (Math.abs(diff) > 0.1) {
      smoothScroll.value += diff * 0.05;
      rafId = requestAnimationFrame(animate);
    } else {
      smoothScroll.value = targetScroll();
      rafId = null;
    }
  }
  watch(targetScroll, () => {
    if (!rafId) animate();
  });
  onMounted(() => animate());
  onBeforeUnmount(() => { if (rafId) cancelAnimationFrame(rafId); });
  return smoothScroll;
}
