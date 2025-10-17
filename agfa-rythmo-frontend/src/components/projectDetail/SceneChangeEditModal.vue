<template>
  <BaseModal
    :show="show"
    title="Éditer changement de plan"
    subtitle="Modifiez le timecode du changement de plan"
    size="md"
    @close="$emit('close')"
  >
    <template v-slot:icon>
      <svg class="w-6 h-6 sm:w-8 sm:h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"
        />
      </svg>
    </template>

    <template v-slot:default>
      <form id="scene-change-form" @submit.prevent="onSubmit" class="space-y-6">
        <div class="space-y-2">
          <label for="scene-timecode" class="block text-sm font-semibold text-gray-300">
            Timecode (secondes)
            <span class="text-red-400">*</span>
          </label>
          <input
            id="scene-timecode"
            v-model.number="localTimecode"
            type="number"
            step="0.01"
            min="0"
            required
            class="w-full px-4 py-3 rounded-xl border border-gray-600 bg-agfa-bg-primary text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all duration-300 hover:border-gray-500"
            placeholder="0.00"
          />
          <p class="text-xs text-gray-400 mt-1">
            ⏱️ Indiquez le moment exact du changement de plan en secondes
          </p>
        </div>
      </form>
    </template>

    <template v-slot:footer>
      <button
        type="button"
        @click="$emit('close')"
        class="flex-1 px-4 sm:px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-xl transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98]"
      >
        Annuler
      </button>
      <button
        type="submit"
        form="scene-change-form"
        class="flex-1 px-4 sm:px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-semibold rounded-xl transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98] shadow-lg shadow-blue-500/25"
      >
        Valider
      </button>
    </template>
  </BaseModal>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import BaseModal from '../BaseModal.vue'

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
