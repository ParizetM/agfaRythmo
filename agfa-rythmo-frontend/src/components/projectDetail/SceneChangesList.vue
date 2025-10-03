<template>
  <div class="bg-agfa-dark rounded-lg p-3 w-64 text-white shadow-lg flex flex-col h-80">
    <h3 class="text-lg font-semibold mb-2 text-white">Changements de plan</h3>

    <div class="flex-1 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-600 scrollbar-track-transparent">
      <div class="space-y-1">
        <div
          v-for="(time, idx) in sceneChanges"
          :key="`scene-${Math.round(time * 10000)}-${idx}`"
          :class="[
            'flex items-center justify-between p-2 rounded transition-colors group',
            'hover:bg-gray-700',
            { 'bg-agfa-blue bg-opacity-30': idx === selected }
          ]"
        >
          <span
            class="text-sm font-medium cursor-pointer flex-1 hover:text-agfa-blue transition-colors"
            @click="$emit('seekTo', time); $emit('select', idx)"
            title="Aller au temps {{ time.toFixed(2) }}s"
          >
            {{ time.toFixed(2) }}s
          </span>
          <div class="flex gap-1 opacity-70 group-hover:opacity-100 transition-opacity">
            <button
              @click.stop="$emit('edit', idx)"
              class="p-1.5 hover:bg-gray-600 rounded text-sm transition-all hover:scale-110 bg-gray-800"
              title="Éditer"
            >
              ✏️
            </button>
            <button
              @click.stop="$emit('delete', idx)"
              class="p-1.5 hover:bg-red-600 rounded text-sm transition-all hover:scale-110 bg-gray-800"
              title="Supprimer"
            >
              🗑️
            </button>
          </div>
        </div>
      </div>
    </div>

    <button
      @click="$emit('add')"
      class="mt-2 bg-agfa-green hover:bg-agfa-green-hover text-white font-medium py-2 px-3 rounded text-sm transition-colors"
    >
      + Ajouter
    </button>
  </div>
</template>

<script setup lang="ts">
defineProps<{ sceneChanges: number[], selected?: number }>();
defineEmits(['select', 'edit', 'delete', 'add', 'seekTo']);
</script>
