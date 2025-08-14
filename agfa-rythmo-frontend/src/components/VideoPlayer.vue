<template>
  <div class="video-player">
    <video
      ref="videoRef"
      :src="src"
      controls
      @timeupdate="onTimeUpdate"
      @loadedmetadata="onLoadedMetadata"
      class="main-video"
    />
  </div>
</template>

<script setup lang="ts">

import { ref, watch, defineProps, defineEmits } from 'vue'

const props = defineProps<{ src: string; currentTime?: number }>()
const emit = defineEmits(['timeupdate', 'loadedmetadata'])
const videoRef = ref<HTMLVideoElement | null>(null)

// Permet le seek depuis le parent (ex: clic sur timecode)
watch(
  () => props.currentTime,
  (val) => {
    if (
      videoRef.value &&
      typeof val === 'number' &&
      Math.abs(val - videoRef.value.currentTime) > 0.1
    ) {
      videoRef.value.currentTime = val
    }
  },
)

function onTimeUpdate(e: Event) {
  emit('timeupdate', (e.target as HTMLVideoElement).currentTime)
}
function onLoadedMetadata(e: Event) {
  emit('loadedmetadata', (e.target as HTMLVideoElement).duration)
}
</script>

<style scoped>
.video-player {
  display: flex;
  justify-content: center;
  align-items: center;
  width: 100%;
}
.main-video {
  width: 100%;
  max-width: 720px;
  border-radius: 6px;
  background: #000;
  box-shadow: 0 1px 4px #0003;
  max-height: 70vh;
}
</style>
