<template>
  <div class="flex justify-center gap-3 mt-5 mb-2">
    <button
      @click="$emit('seek', -1)"
      title="← 1s (Flèche gauche)"
      class="bg-agfa-dark text-white border border-gray-600 rounded-lg px-4 py-2 text-lg cursor-pointer hover:bg-agfa-blue transition-colors duration-300 font-medium"
    >
      -1s
    </button>

    <button
      @click="$emit('seekFrame', -1)"
      title="← 1 frame (A)"
      class="bg-agfa-dark text-white border border-gray-600 rounded-lg px-4 py-2 text-lg cursor-pointer hover:bg-agfa-blue transition-colors duration-300 font-medium"
    >
      -1 frame
    </button>

    <button
      @click="$emit('seek', 0)"
      title="Lecture/Pause (Espace)"
      class="bg-agfa-dark text-white border border-gray-600 rounded-lg px-4 py-2 text-lg cursor-pointer hover:bg-agfa-blue transition-colors duration-300 flex items-center justify-center min-w-[3rem]"
    >
      <svg
        v-if="isVideoPaused"
        width="22"
        height="22"
        viewBox="0 0 22 22"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
        class="align-middle"
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
        class="align-middle"
      >
        <rect x="6" y="4" width="3.5" height="14" fill="#fff" />
        <rect x="12.5" y="4" width="3.5" height="14" fill="#fff" />
      </svg>
    </button>

    <button
      @click="$emit('seekFrame', 1)"
      title="→ 1 frame (E)"
      class="bg-agfa-dark text-white border border-gray-600 rounded-lg px-4 py-2 text-lg cursor-pointer hover:bg-agfa-blue transition-colors duration-300 font-medium"
    >
      +1 frame
    </button>

    <button
      @click="$emit('seek', 1)"
      title="→ 1s (Flèche droite)"
      class="bg-agfa-dark text-white border border-gray-600 rounded-lg px-4 py-2 text-lg cursor-pointer hover:bg-agfa-blue transition-colors duration-300 font-medium"
    >
      +1s
    </button>
  </div>
</template>

<script setup lang="ts">
defineProps<{ isVideoPaused: boolean }>()
defineEmits(['seek', 'seekFrame'])
</script>
