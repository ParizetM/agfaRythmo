<template>
  <div class="flex justify-center gap-3 mt-5 mb-2 select-none">
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
      -1 <span class="hidden sm:inline-block">frame</span>
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
        <path
          d="M5 3L19 12L5 21V3Z"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
          fill="currentColor"
        />
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
        <path
          d="M6 4H8V18H6V4ZM14 4H16V18H14V4Z"
          fill="currentColor"
        />
      </svg>
    </button>

    <button
      @click="$emit('seekFrame', 1)"
      title="→ 1 frame (E)"
      class="bg-agfa-dark text-white border border-gray-600 rounded-lg px-4 py-2 text-lg cursor-pointer hover:bg-agfa-blue transition-colors duration-300 font-medium"
    >
      +1 <span class="hidden sm:inline-block">frame</span>
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
interface Props {
  isVideoPaused?: boolean
}

defineProps<Props>()

defineEmits<{
  'seek': [offset: number]
  'seekFrame': [offset: number]
}>()
</script>
