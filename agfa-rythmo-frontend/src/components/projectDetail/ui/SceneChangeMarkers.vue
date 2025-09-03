<template>
  <div class="scene-changes">
    <div
      v-for="(position, index) in sceneChangePositions"
      :key="`scene-${index}`"
      class="scene-change-bar"
      :style="{ left: position + 'px' }"
    />
  </div>
</template>

<script setup lang="ts">
interface Props {
  sceneChangePositions: number[]
}

defineProps<Props>()
</script>

<style scoped>
.scene-changes {
  position: absolute;
  left: 0;
  bottom: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
  z-index: 3;
}

.scene-change-bar {
  position: absolute;
  bottom: 0;
  width: 5px;
  height: 200%;
  background: #657390;
  opacity: 0.95;
  border-radius: 2px;
  z-index: 3;
  box-shadow: 0 0 8px #657390;
  pointer-events: none;
  transition: none;
}
</style>
