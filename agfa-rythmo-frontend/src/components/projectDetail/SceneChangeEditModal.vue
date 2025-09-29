<template>
  <div v-if="show" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-agfa-dark text-white rounded-xl p-8 min-w-80 shadow-2xl">
      <h4 class="text-xl font-bold mb-6">Éditer un changement de plan</h4>
      <form @submit.prevent="onSubmit" class="space-y-4">
        <label class="block">
          <span class="text-white mb-2 block">Timecode (s):</span>
          <input
            v-model.number="localTimecode"
            type="number"
            step="0.01"
            min="0"
            required
            class="w-full p-3 rounded-lg border border-gray-600 bg-gray-800 text-white focus:ring-2 focus:ring-agfa-blue focus:border-transparent outline-none transition-all duration-300"
          />
        </label>
        <div class="flex gap-4 pt-4">
          <button
            type="submit"
            class="flex-1 bg-agfa-blue hover:bg-agfa-blue-hover text-white border-none rounded-lg py-3 px-5 cursor-pointer text-base font-medium transition-colors duration-300"
          >
            Valider
          </button>
          <button
            type="button"
            @click="$emit('close')"
            class="flex-1 bg-gray-600 hover:bg-gray-700 text-white border-none rounded-lg py-3 px-5 cursor-pointer text-base font-medium transition-colors duration-300"
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
