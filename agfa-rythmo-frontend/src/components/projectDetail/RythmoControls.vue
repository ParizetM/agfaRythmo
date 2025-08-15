<template>
  <div class="rythmo-controls">
    <button @click="$emit('seek', -1)" title="← 1s (Flèche gauche)">-1s</button>
    <button @click="$emit('seekFrame', -1)" title="← 1 frame (A)">-1 frame</button>
    <button @click="$emit('seek', 0)" title="Lecture/Pause (Espace)">
      <svg
        v-if="isVideoPaused"
        width="22"
        height="22"
        viewBox="0 0 22 22"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
      >
        <polygon points="6,4 18,11 6,18" fill="#fff" />
      </svg>
      <svg
        v-else
        width="22"
        height="22"
        viewBox="0 0 22 22"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
      >
        <rect x="6" y="4" width="3.5" height="14" fill="#fff" />
        <rect x="12.5" y="4" width="3.5" height="14" fill="#fff" />
      </svg>
    </button>
    <button @click="$emit('seekFrame', 1)" title="→ 1 frame (E)">+1 frame</button>
    <button @click="$emit('seek', 1)" title="→ 1s (Flèche droite)">+1s</button>
  </div>
</template>

<script setup lang="ts">
import { defineProps, defineEmits } from 'vue'
const props = defineProps<{ isVideoPaused: boolean }>()
defineEmits(['seek', 'seekFrame'])
</script>

<style scoped>
.rythmo-controls {
  display: flex;
  gap: 0.5rem;
  justify-content: center;
  align-items: center;
  margin-top: 1rem;
}
</style>
