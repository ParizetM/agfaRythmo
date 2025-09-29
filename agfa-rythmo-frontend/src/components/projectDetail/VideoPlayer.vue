<template>
  <div class="flex justify-center items-center w-full">
    <video
      ref="videoRef"
      :src="src"
      @timeupdate="onTimeUpdate"
      @loadedmetadata="onLoadedMetadata"
      class="w-full max-w-4xl rounded-lg bg-black shadow-xl max-h-[60vh] object-contain"
    />
  </div>
</template>

<script setup lang="ts">

import { ref, watch } from 'vue'

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
