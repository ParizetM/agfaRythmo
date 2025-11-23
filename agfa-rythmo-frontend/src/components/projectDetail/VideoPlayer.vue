<template>
  <div class="flex justify-center items-center w-full" ref="containerRef">
    <video
      ref="videoRef"
      :src="src"
      crossorigin="anonymous"
      @timeupdate="onTimeUpdate"
      @loadedmetadata="onLoadedMetadata"
      @canplay="onCanPlay"
      @canplaythrough="onCanPlayThrough"
      @loadeddata="onLoadedData"
      @seeking="onSeeking"
      @seeked="onSeeked"
      @waiting="onWaiting"
      @playing="onPlaying"
      @stalled="onStalled"
      @error="onError"
      class="w-full max-w-4xl rounded-lg bg-black shadow-xl max-h-[60vh] object-contain"
      preload="metadata"
      playsinline
      webkit-playsinline
    />
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'

const props = defineProps<{ src: string; currentTime?: number }>()
const emit = defineEmits(['timeupdate', 'loadedmetadata', 'canplay', 'loadeddata', 'seeking', 'seeked', 'waiting', 'playing', 'stalled', 'canplaythrough', 'error'])
const videoRef = ref<HTMLVideoElement | null>(null)
const containerRef = ref<HTMLElement | null>(null)

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
function onCanPlay() {
  emit('canplay')
}
function onLoadedData() {
  emit('loadeddata')
}
function onSeeking() {
  emit('seeking')
}
function onSeeked() {
  emit('seeked')
}
function onWaiting() {
  emit('waiting')
}
function onPlaying() {
  emit('playing')
}
function onStalled() {
  emit('stalled')
}
function onCanPlayThrough() {
  emit('canplaythrough')
}
function onError(e: Event) {
  const videoEl = e.target as HTMLVideoElement
  const error = videoEl.error
  emit('error', error)
}
</script>

<style scoped>
/* Styles removed - no more sticky behavior */
</style>
