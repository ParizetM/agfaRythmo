<template>
  <div class="rythmo-cursor" :style="cursorStyle" />
</template>

<script setup lang="ts">
import { computed, type CSSProperties } from 'vue'

interface Props {
  centerOffset?: number
}

const props = withDefaults(defineProps<Props>(), {
  centerOffset: 0,
})

const cursorStyle = computed((): CSSProperties => ({
  position: 'absolute',
  top: '0',
  left: `${props.centerOffset}px`,
  width: '4px',
  height: '100%',
  background: 'linear-gradient(to bottom, #ffffff, #e5e7eb)',
  borderRadius: '2px',
  boxShadow: '0 0 8px rgba(255, 255, 255, 0.6), 0 0 16px rgba(0, 0, 0, 0.8)',
  zIndex: 2,
  transition: 'none',
}))
</script>

<style scoped>
.rythmo-cursor {
  pointer-events: none;
}
</style>
