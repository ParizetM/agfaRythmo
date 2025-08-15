<template>
  <div class="bg-agfa-dark rounded-xl p-6 min-w-56 max-w-96 text-white shadow-lg">
    <h3 class="text-xl font-bold mb-4 text-white">Timecodes</h3>

    <ul class="list-none p-0 m-0 mb-6 space-y-2">
      <li
        v-for="(line, idx) in timecodes"
        :key="idx"
        :class="[
          'flex flex-wrap items-center gap-2 p-3 rounded-lg cursor-pointer transition-all duration-300 hover:bg-gray-700',
          { 'bg-agfa-blue bg-opacity-40 ring-2 ring-agfa-blue ring-opacity-50': idx === selected }
        ]"
        @click="$emit('select', idx)"
      >
        <span class="text-sm font-medium text-gray-300">
          {{ line.start.toFixed(2) }}s - {{ line.end.toFixed(2) }}s
        </span>
        <span class="flex-1 ml-2 text-sm text-white">
          {{ line.text }}
        </span>
        <div class="flex gap-2">
          <button
            @click.stop="$emit('edit', idx)"
            class="bg-transparent border-none text-white cursor-pointer text-base ml-1 hover:text-agfa-blue transition-colors duration-300 p-1 rounded hover:bg-gray-600"
            title="Éditer"
          >
            ✏️
          </button>
          <button
            @click.stop="$emit('delete', idx)"
            class="bg-transparent border-none text-white cursor-pointer text-base ml-1 hover:text-agfa-red transition-colors duration-300 p-1 rounded hover:bg-gray-600"
            title="Supprimer"
          >
            🗑️
          </button>
        </div>
      </li>
    </ul>

    <button
      @click="$emit('add')"
      class="w-full bg-agfa-green hover:bg-agfa-green-hover text-white font-medium py-3 px-4 rounded-lg transition-all duration-300 transform hover:scale-105"
    >
      + Ajouter un timecode
    </button>
  </div>
</template>

<script setup lang="ts">
defineProps<{ timecodes: { start: number; end: number; text: string }[], selected?: number }>();
defineEmits(['select', 'edit', 'delete', 'add']);
</script>
