<template>
  <BaseModal
    :show="modelValue"
    @update:show="$emit('update:modelValue', $event)"
    title="Paramètres de détection IA"
    size="lg"
  >
    <div class="space-y-6">
      <!-- Description -->
      <p class="text-sm text-gray-400">
        Configurez les paramètres d'analyse pour optimiser la vitesse et la précision de la détection automatique des changements de plan.
      </p>

      <!-- FPS (Vitesse d'analyse) -->
      <div>
        <label class="block text-sm font-medium text-gray-300 mb-2">
          Vitesse d'analyse (FPS)
        </label>
        <div class="flex items-center gap-4">
          <input
            v-model.number="localFps"
            type="range"
            min="1"
            max="30"
            step="1"
            class="flex-1 h-2 bg-gray-700 rounded-lg appearance-none cursor-pointer slider"
          />
          <span class="text-white font-semibold w-12 text-center">{{ localFps }}</span>
        </div>
        <div class="mt-2 flex justify-between text-xs text-gray-400">
          <span>Très lent (1 fps)</span>
          <span>Rapide (30 fps)</span>
        </div>
        <p class="mt-2 text-xs text-gray-500">
          <strong>{{ speedDescription }}</strong> - {{ speedExplanation }}
        </p>
      </div>

      <!-- Threshold (Sensibilité) -->
      <div>
        <label class="block text-sm font-medium text-gray-300 mb-2">
          Sensibilité de détection
        </label>
        <div class="flex items-center gap-4">
          <input
            v-model.number="localThreshold"
            type="range"
            min="0.1"
            max="1.0"
            step="0.05"
            class="flex-1 h-2 bg-gray-700 rounded-lg appearance-none cursor-pointer slider"
          />
          <span class="text-white font-semibold w-12 text-center">{{ localThreshold.toFixed(2) }}</span>
        </div>
        <div class="mt-2 flex justify-between text-xs text-gray-400">
          <span>Très sensible (0.1)</span>
          <span>Peu sensible (1.0)</span>
        </div>
        <p class="mt-2 text-xs text-gray-500">
          <strong>{{ sensitivityDescription }}</strong> - {{ sensitivityExplanation }}
        </p>
      </div>

      <!-- Estimation -->
      <div class="bg-blue-500/10 border border-blue-500/20 rounded-lg p-4">
        <div class="flex items-start gap-3">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-400 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
          </svg>
          <div class="flex-1">
            <h4 class="text-sm font-medium text-blue-300 mb-1">Recommandations</h4>
            <ul class="text-xs text-gray-400 space-y-1">
              <li>• <strong>FPS 2-5</strong> : Bon équilibre vitesse/précision pour la plupart des vidéos</li>
              <li>• <strong>FPS 10+</strong> : Pour vidéos avec changements rapides (action, sport)</li>
              <li>• <strong>Sensibilité 0.3-0.5</strong> : Valeurs recommandées pour films/séries</li>
              <li>• <strong>Sensibilité basse (0.1-0.2)</strong> : Détecte même les petits changements</li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer avec boutons -->
    <template #footer>
      <div class="flex justify-end gap-3">
        <button
          @click="$emit('update:modelValue', false)"
          class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition-colors"
        >
          Annuler
        </button>
        <button
          @click="handleConfirm"
          class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors"
        >
          Lancer l'analyse
        </button>
      </div>
    </template>
  </BaseModal>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import BaseModal from './BaseModal.vue'

interface Props {
  modelValue: boolean
}

interface Emits {
  (e: 'update:modelValue', value: boolean): void
  (e: 'confirm', params: { fps: number; threshold: number }): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Valeurs locales avec valeurs par défaut recommandées
const localFps = ref(25)
const localThreshold = ref(0.25)

// Descriptions dynamiques basées sur les valeurs
const speedDescription = computed(() => {
  if (localFps.value <= 2) return 'Très rapide'
  if (localFps.value <= 5) return 'Rapide'
  if (localFps.value <= 10) return 'Modéré'
  if (localFps.value <= 20) return 'Lent'
  return 'Très lent'
})

const speedExplanation = computed(() => {
  if (localFps.value <= 2) return 'Analyse 1 image toutes les ~0.5s (12x plus rapide)'
  if (localFps.value <= 5) return 'Analyse 1 image toutes les ~0.2s (5x plus rapide)'
  if (localFps.value <= 10) return 'Analyse 1 image toutes les ~0.1s (2-3x plus rapide)'
  if (localFps.value <= 20) return 'Analyse la plupart des images (précis mais lent)'
  return 'Analyse toutes les images (maximum de précision)'
})

const sensitivityDescription = computed(() => {
  if (localThreshold.value <= 0.2) return 'Très sensible'
  if (localThreshold.value <= 0.35) return 'Sensible'
  if (localThreshold.value <= 0.6) return 'Modéré'
  if (localThreshold.value <= 0.8) return 'Peu sensible'
  return 'Très peu sensible'
})

const sensitivityExplanation = computed(() => {
  if (localThreshold.value <= 0.2) return 'Détecte même les petits changements (risque de faux positifs)'
  if (localThreshold.value <= 0.35) return 'Bon équilibre, détecte la plupart des changements'
  if (localThreshold.value <= 0.6) return 'Ne détecte que les changements notables'
  if (localThreshold.value <= 0.8) return 'Ne détecte que les changements importants'
  return 'Ne détecte que les changements très marqués'
})

// Réinitialiser les valeurs quand la modal s'ouvre
watch(() => props.modelValue, (isOpen) => {
  if (isOpen) {
    localFps.value = 25
    localThreshold.value = 0.25
  }
})

const handleConfirm = () => {
  emit('confirm', {
    fps: localFps.value,
    threshold: localThreshold.value
  })
  emit('update:modelValue', false)
}
</script>

<style scoped>
/* Style personnalisé pour les sliders */
.slider::-webkit-slider-thumb {
  appearance: none;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: #9333ea; /* purple-600 */
  cursor: pointer;
  box-shadow: 0 0 0 4px rgba(147, 51, 234, 0.2);
  transition: all 0.15s ease;
}

.slider::-webkit-slider-thumb:hover {
  background: #7e22ce; /* purple-700 */
  box-shadow: 0 0 0 6px rgba(147, 51, 234, 0.3);
}

.slider::-moz-range-thumb {
  width: 20px;
  height: 20px;
  border: none;
  border-radius: 50%;
  background: #9333ea;
  cursor: pointer;
  box-shadow: 0 0 0 4px rgba(147, 51, 234, 0.2);
  transition: all 0.15s ease;
}

.slider::-moz-range-thumb:hover {
  background: #7e22ce;
  box-shadow: 0 0 0 6px rgba(147, 51, 234, 0.3);
}
</style>
