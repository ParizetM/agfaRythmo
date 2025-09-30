<template>
  <div
    v-if="show"
    class="fixed inset-0 flex items-center justify-center z-50 backdrop-blur-sm"
    @click.self="$emit('close')"
  >
    <div class="bg-agfa-dark text-white rounded-lg p-6 w-80 shadow-xl border border-gray-700/50 backdrop-blur-md">
      <h4 class="text-lg font-semibold mb-4 text-agfa-blue">Éditer changement de plan</h4>
      <form @submit.prevent="onSubmit" class="space-y-4">
        <label class="block">
          <span class="text-sm text-gray-300 mb-1 block">Timecode (secondes)</span>
          <input
            v-model.number="localTimecode"
            type="number"
            step="0.01"
            min="0"
            required
            class="w-full p-2.5 rounded-md border border-gray-600/50 bg-gray-800/70 text-white placeholder-gray-400 focus:ring-2 focus:ring-agfa-blue/50 focus:border-agfa-blue outline-none transition-all duration-200 text-sm"
            placeholder="0.00"
          />
        </label>
        <div class="flex gap-2 pt-2">
          <button
            type="submit"
            class="flex-1 bg-agfa-blue hover:bg-agfa-blue-hover text-white border-none rounded-md py-2.5 px-4 text-sm font-medium transition-all duration-200 hover:scale-105"
          >
            Valider
          </button>
          <button
            type="button"
            @click="$emit('close')"
            class="flex-1 bg-gray-700/70 hover:bg-gray-600 text-white border-none rounded-md py-2.5 px-4 text-sm font-medium transition-all duration-200 hover:scale-105"
          >
            Annuler
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'

const props = defineProps<{ show: boolean, timecode: number | null }>()
const emit = defineEmits(['submit', 'close'])

const localTimecode = ref(props.timecode ?? 0)

watch(() => props.timecode, (val) => {
  if (val !== null && val !== undefined) localTimecode.value = val
})

function onSubmit() {
  emit('submit', localTimecode.value)
}
</script>
