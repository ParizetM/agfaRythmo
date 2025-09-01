<template>
  <div
    v-if="show"
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
  >
    <div class="bg-agfa-dark text-white rounded-xl p-8 min-w-96 shadow-2xl">
      <h4 class="text-xl font-bold mb-6">
        {{ timecode ? 'Éditer' : 'Ajouter' }} un timecode
      </h4>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <label class="block">
          <span class="text-white mb-2 block">Ligne rythmo:</span>
          <select
            v-model="formData.line_number"
            :disabled="maxLines === 1"
            required
            :class="[
              'w-full p-3 rounded-lg border border-gray-600 text-white focus:ring-2 focus:ring-agfa-blue focus:border-transparent outline-none transition-all duration-300',
              maxLines === 1 ? 'bg-gray-700 cursor-not-allowed opacity-75' : 'bg-gray-800'
            ]"
          >
            <option v-for="n in maxLines" :key="n" :value="n">
              {{ maxLines === 1 ? 'Ligne unique' : `Ligne ${n}` }}
            </option>
          </select>
        </label>

        <label class="block">
          <span class="text-white mb-2 block">Début (s):</span>
          <input
            v-model.number="formData.start"
            type="number"
            step="0.01"
            min="0"
            required
            class="w-full p-3 rounded-lg border border-gray-600 bg-gray-800 text-white focus:ring-2 focus:ring-agfa-blue focus:border-transparent outline-none transition-all duration-300"
          />
        </label>

        <label class="block">
          <span class="text-white mb-2 block">Fin (s):</span>
          <input
            v-model.number="formData.end"
            type="number"
            step="0.01"
            min="0"
            required
            class="w-full p-3 rounded-lg border border-gray-600 bg-gray-800 text-white focus:ring-2 focus:ring-agfa-blue focus:border-transparent outline-none transition-all duration-300"
          />
        </label>

        <label class="block">
          <span class="text-white mb-2 block">Texte:</span>
          <input
            v-model="formData.text"
            type="text"
            required
            class="w-full p-3 rounded-lg border border-gray-600 bg-gray-800 text-white focus:ring-2 focus:ring-agfa-blue focus:border-transparent outline-none transition-all duration-300"
          />
        </label>

        <div class="flex gap-4 pt-4">
          <button
            type="submit"
            class="flex-1 bg-agfa-blue hover:bg-agfa-blue-hover text-white border-none rounded-lg py-3 px-5 cursor-pointer text-base font-medium transition-colors duration-300"
          >
            {{ timecode ? 'Modifier' : 'Créer' }}
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
interface TimecodeFormData {
  line_number: number
  start: number
  end: number
  text: string
}

interface TimecodeItem {
  id?: number
  project_id?: number
  start: number
  end: number
  text: string
  line_number: number
}

const props = defineProps<{
  show: boolean
  timecode?: TimecodeItem | null
  maxLines: number
  defaultLineNumber?: number
  currentTime?: number
}>()

const emit = defineEmits<{
  (e: 'submit', data: TimecodeFormData): void
  (e: 'close'): void
}>()

const formData = ref<TimecodeFormData>({
  line_number: 1,
  start: 0,
  end: 0,
  text: ''
})

// Réinitialise le formulaire quand le modal s'ouvre
watch(() => props.show, (show) => {
  if (show) {
    if (props.timecode) {
      // Mode édition
      formData.value = {
        line_number: props.timecode.line_number,
        start: props.timecode.start,
        end: props.timecode.end,
        text: props.timecode.text
      }
    } else {
      // Mode création
      const currentTime = props.currentTime || 0
      formData.value = {
        line_number: props.defaultLineNumber || 1,
        start: currentTime,
        end: currentTime + 3, // 3 secondes par défaut
        text: ''
      }
    }
  }
})

function handleSubmit() {
  emit('submit', { ...formData.value })
}
</script>

<style scoped>
/* Réutilise les styles existants du modal parent */
</style>
