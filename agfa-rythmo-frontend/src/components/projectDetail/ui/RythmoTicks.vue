<template>
  <div class="rythmo-ticks">
    <div
      v-for="tick in ticks"
      :key="`tick-${tick.x}`"
      class="rythmo-tick"
      :class="{ 'tick-second': tick.isSecond }"
      :style="getTickStyle(tick)"
    />
  </div>
</template>

<script setup lang="ts">
import { type CSSProperties } from 'vue'

interface Tick {
  x: number
  isSecond: boolean
}

interface Props {
  ticks: Tick[]
}

defineProps<Props>()

function getTickStyle(tick: Tick): CSSProperties {
  return {
    left: tick.x + 'px',
    height: tick.isSecond ? '80%' : '45%',
    width: tick.isSecond ? '3px' : '2px',
    background: tick.isSecond ? '#8455F6' : '#aaa',
    opacity: tick.isSecond ? 0.5 : 0.3,
    position: 'absolute',
    bottom: tick.isSecond ? '0%' : '0%',
    borderRadius: '2px',
    zIndex: 1,
    pointerEvents: 'none',
    transition: 'none',
  }
}
</script>

<style scoped>
.rythmo-ticks {
  position: absolute;
  left: 0;
  bottom: 0;
  width: 100%;
  height: 2rem;
  pointer-events: none;
  z-index: 1;
}

.rythmo-tick {
  position: absolute;
  bottom: 0%;
  width: 2px;
  height: 45%;
  background: #aaa;
  opacity: 0.45;
  border-radius: 2px;
  z-index: 1;
  pointer-events: none;
  transition: none;
}

.rythmo-tick.tick-second {
  width: 3px;
  height: 80%;
  background: #8455f6;
  opacity: 0.85;
  bottom: 0;
}
</style>
