<template>
  <BaseModal
    :show="show"
    @update:show="$emit('update:show', $event)"
    title="Traduire les dialogues"
    size="xl"
    icon-gradient="bg-gradient-to-br from-orange-500 to-yellow-500"
  >
    <template #icon>
      <GlobeAltIcon class="w-8 h-8 text-white" />
    </template>

    <div class="space-y-6">
      <!-- Description -->
      <div class="bg-blue-500/10 border border-blue-500/30 rounded-lg p-4">
        <div class="flex items-start gap-3">
          <GlobeAltIcon class="h-5 w-5 text-blue-400 flex-shrink-0 mt-0.5" />
          <div class="text-sm text-gray-300">
            <p class="font-medium text-blue-400 mb-1">Traduction automatique des dialogues</p>
            <p>
              Les textes de tous les timecodes seront traduits dans la langue cible sélectionnée.
              Le contexte des personnages peut améliorer la qualité de la traduction.
            </p>
          </div>
        </div>
      </div>

      <!-- Avertissement -->
      <div class="bg-yellow-500/10 border border-yellow-500/30 rounded-lg p-4">
        <div class="flex items-start gap-3">
          <ExclamationTriangleIcon class="h-5 w-5 text-yellow-400 flex-shrink-0 mt-0.5" />
          <div class="text-sm text-gray-300">
            <p class="font-medium text-yellow-400 mb-1">Attention</p>
            <p>
              Cette action remplacera le texte existant de tous les timecodes par la traduction.
              Il est recommandé d'exporter votre projet avant de lancer la traduction.
            </p>
          </div>
        </div>
      </div>

      <!-- Configuration -->
      <div class="space-y-4">
        <!-- Langue source -->
        <div>
          <label class="block text-sm font-medium text-gray-300 mb-2">
            Langue source
          </label>
          <select
            v-model="sourceLanguage"
            class="w-full px-4 py-2 bg-gray-800/50 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-orange-500/50 focus:border-orange-500"
          >
            <option value="auto">Détection automatique</option>
            <option
              v-for="lang in availableLanguages"
              :key="lang.code"
              :value="lang.code"
            >
              {{ lang.name }}
            </option>
          </select>
          <p class="mt-1 text-xs text-gray-500">
            La détection automatique analyse le texte pour identifier la langue
          </p>
        </div>

        <!-- Langue cible -->
        <div>
          <label class="block text-sm font-medium text-gray-300 mb-2">
            Langue cible <span class="text-red-400">*</span>
          </label>
          <select
            v-model="targetLanguage"
            class="w-full px-4 py-2 bg-gray-800/50 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-orange-500/50 focus:border-orange-500"
          >
            <option value="" disabled>Sélectionner une langue</option>
            <option
              v-for="lang in availableLanguages"
              :key="lang.code"
              :value="lang.code"
            >
              {{ lang.name }}
            </option>
          </select>
        </div>

        <!-- Utiliser contexte personnages -->
        <div>
          <label class="flex items-start gap-3 cursor-pointer">
            <input
              type="checkbox"
              v-model="useCharacterContext"
              class="mt-1 h-4 w-4 rounded border-gray-700 bg-gray-800/50 text-orange-500 focus:ring-2 focus:ring-orange-500/50"
            />
            <div>
              <div class="text-sm font-medium text-gray-300">
                Utiliser le contexte des personnages
              </div>
              <p class="text-xs text-gray-500 mt-1">
                Inclut les noms des personnages pour améliorer la traduction.
                Recommandé si vous avez défini des personnages.
              </p>
            </div>
          </label>
        </div>

        <!-- Info provider -->
        <div class="bg-gradient-to-br from-gray-800/50 to-gray-900/50 border border-gray-700 rounded-lg p-4">
          <div class="flex items-start gap-3">
            <div class="p-2 bg-gradient-to-br from-orange-500/20 to-yellow-500/20 rounded-lg">
              <InformationCircleIcon class="h-5 w-5 text-orange-400" />
            </div>
            <div class="flex-1">
              <p class="text-sm font-medium text-white mb-1">
                Service utilisé : {{ providerName }}
              </p>
              <p class="text-xs text-gray-400 leading-relaxed">
                {{ providerDescription }}
              </p>
              <p class="text-xs text-gray-500 mt-2">
                💡 Les phrases sont traduites par groupes de 10 pour préserver le contexte et améliorer la qualité.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <template #footer>
      <div class="flex items-center justify-between">
        <button
          @click="$emit('update:show', false)"
          class="px-4 py-2 text-gray-400 hover:text-white transition-colors"
        >
          Annuler
        </button>
        <button
          @click="handleStart"
          :disabled="!targetLanguage || isStarting"
          class="px-6 py-2 bg-gradient-to-r from-orange-500 to-yellow-500 text-white rounded-lg font-medium hover:from-orange-600 hover:to-yellow-600 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
        >
          <ArrowPathIcon v-if="isStarting" class="h-5 w-5 animate-spin" />
          <span>{{ isStarting ? 'Lancement...' : 'Lancer la traduction' }}</span>
        </button>
      </div>
    </template>
  </BaseModal>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import BaseModal from './BaseModal.vue'
import {
  GlobeAltIcon,
  ExclamationTriangleIcon,
  InformationCircleIcon,
  ArrowPathIcon,
} from '@heroicons/vue/24/outline'
import type { SupportedLanguage } from '@/api/serverCapabilities'

interface Props {
  show: boolean
  availableLanguages: SupportedLanguage[]
  provider?: string
}

interface Emits {
  (e: 'update:show', value: boolean): void
  (e: 'start', options: {
    target_language: string
    source_language: string | null
    use_character_context: boolean
  }): void
}

const props = withDefaults(defineProps<Props>(), {
  provider: 'libretranslate',
})

const emit = defineEmits<Emits>()

const sourceLanguage = ref<string>('auto')
const targetLanguage = ref<string>('')
const useCharacterContext = ref<boolean>(true)
const isStarting = ref(false)

const providerName = computed(() => {
  const providers: Record<string, string> = {
    nllb: 'NLLB-200',
    mymemory: 'MyMemory',
  }
  return providers[props.provider] || props.provider
})

const providerDescription = computed(() => {
  const descriptions: Record<string, string> = {
    nllb: '⭐⭐⭐⭐ Meta AI - 200 langues, gratuit, local (~2GB). Excellente qualité, supporte toutes les langues dont chinois, japonais, coréen. Batch natif pour traduction rapide.',
    mymemory: '⭐⭐⭐ Gratuit 10k/jour, aucune configuration. Qualité correcte, limite 500 caractères par phrase. Fallback automatique.',
  }
  return descriptions[props.provider] || 'Service de traduction automatique configuré sur le serveur.'
})

const handleStart = () => {
  if (!targetLanguage.value) return

  isStarting.value = true

  emit('start', {
    target_language: targetLanguage.value,
    source_language: sourceLanguage.value === 'auto' ? null : sourceLanguage.value,
    use_character_context: useCharacterContext.value,
  })

  // Reset après un court délai
  setTimeout(() => {
    isStarting.value = false
  }, 500)
}
</script>
