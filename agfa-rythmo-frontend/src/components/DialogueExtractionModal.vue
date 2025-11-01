<template>
  <BaseModal
    :show="show"
    title="Extraction automatique de dialogues"
    @update:show="$emit('update:show', $event)"
    size="lg"
  >
    <template #icon>
      <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
      </svg>
    </template>

    <div class="space-y-6">
      <!-- Description -->
      <div class="bg-blue-500/10 border border-blue-500/30 rounded-lg p-4">
        <div class="flex items-start gap-3">
          <svg class="w-5 h-5 text-blue-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          <div class="text-sm text-gray-300">
            <p class="font-medium text-blue-400 mb-1">Comment ça fonctionne ?</p>
            <ul class="space-y-1 text-gray-400 list-disc list-inside">
              <li>Extraction audio de la vidéo</li>
              <li>Séparation des voix</li>
              <li>Transcription automatique des dialogues</li>
              <li>Identification et séparation des locuteurs</li>
              <li>Création automatique des timecodes et personnages</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Langue source -->
      <div>
        <label for="language" class="block text-sm font-medium text-gray-300 mb-2">
          Langue de la vidéo
          <span class="text-gray-500 font-normal ml-1">(optionnel)</span>
        </label>
        <select
          id="language"
          v-model="formData.language"
          class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="auto">🌐 Détection automatique</option>
          <option value="fr">🇫🇷 Français</option>
          <option value="en">🇬🇧 English</option>
          <option value="es">🇪🇸 Español</option>
          <option value="de">🇩🇪 Deutsch</option>
          <option value="it">🇮🇹 Italiano</option>
          <option value="pt">🇵🇹 Português</option>
          <option value="zh">🇨🇳 中文</option>
          <option value="ja">🇯🇵 日本語</option>
          <option value="ko">🇰🇷 한국어</option>
          <option value="ru">🇷🇺 Русский</option>
          <option value="ar">🇸🇦 العربية</option>
        </select>
        <p class="text-xs text-gray-500 mt-1">
          La détection automatique fonctionne dans la plupart des cas
        </p>
      </div>

      <!-- Nombre de locuteurs -->
      <div>
        <label for="maxSpeakers" class="block text-sm font-medium text-gray-300 mb-2">
          Nombre maximum de locuteurs
        </label>
        <div class="flex items-center gap-4">
          <input
            id="maxSpeakers"
            v-model.number="formData.max_speakers"
            type="range"
            min="2"
            max="20"
            step="1"
            class="flex-1 h-2 bg-gray-700 rounded-lg appearance-none cursor-pointer accent-blue-500"
          />
          <div class="flex items-center justify-center w-12 h-10 bg-gray-700 border border-gray-600 rounded-lg">
            <span class="text-white font-semibold">{{ formData.max_speakers }}</span>
          </div>
        </div>
        <p class="text-xs text-gray-500 mt-1">
          Plus le nombre est élevé, plus l'analyse sera longue
        </p>
      </div>

      <!-- Modèle de transcription -->
      <div>
        <label for="whisperModel" class="block text-sm font-medium text-gray-300 mb-2">
          Qualité de transcription
        </label>
        <select
          id="whisperModel"
          v-model="formData.whisper_model"
          class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="tiny">⚡ Rapide (recommandé pour 2GB RAM)</option>
          <option value="base">🚀 Équilibré</option>
          <option value="small">🎯 Précis (nécessite 2GB+ RAM)</option>
        </select>
        <p class="text-xs text-gray-500 mt-1">
          Compromis entre vitesse et précision de transcription
        </p>
      </div>

      <!-- Méthode d'identification des locuteurs -->
      <div class="bg-gray-800/50 border border-gray-700 rounded-lg p-4">
        <div class="flex items-center justify-between mb-2">
          <span class="text-sm font-medium text-gray-300">Méthode d'identification</span>
          <div :class="`px-3 py-1 rounded-full text-xs font-medium border ${diarizationMethodBadge.classes}`">
            {{ diarizationMethodBadge.text }}
          </div>
        </div>
        <p class="text-xs text-gray-400">
          {{ diarizationMethodLabel }}
        </p>
        <p class="text-xs text-gray-500 mt-1">
          {{ diarizationMethodBadge.subtitle }}
        </p>
      </div>

      <!-- Warning RAM insuffisante -->
      <div v-if="showRamWarning" class="bg-orange-500/10 border border-orange-500/30 rounded-lg p-4">
        <div class="flex items-start gap-3">
          <svg class="w-5 h-5 text-orange-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
          </svg>
          <div class="text-sm">
            <p class="text-orange-500 font-medium mb-1">⚠️ Précision réduite</p>
            <p class="text-orange-400/80">
              {{ capabilities?.diarization_fallback_reason }}.
              Méthode standard utilisée (précision 30-50%).
              Pour une meilleure précision, passez à un serveur 4GB RAM minimum.
            </p>
          </div>
        </div>
      </div>

      <!-- Estimation durée -->
      <div class="bg-gray-800/50 border border-gray-700 rounded-lg p-4">
        <div class="flex items-center gap-2 text-sm">
          <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          <span class="text-gray-400">Durée estimée :</span>
          <span class="text-white font-medium">{{ estimatedDuration }}</span>
        </div>
      </div>

      <!-- Avertissement -->
      <div class="bg-yellow-500/10 border border-yellow-500/30 rounded-lg p-4">
        <div class="flex items-start gap-3">
          <svg class="w-5 h-5 text-yellow-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
          </svg>
          <div class="text-sm">
            <p class="text-yellow-500 font-medium mb-1">⚠️ Important</p>
            <p class="text-yellow-400/80">
              L'extraction créera automatiquement des timecodes et personnages.
              Vous ne pourrez pas annuler cette action une fois terminée (sauf en supprimant manuellement).
            </p>
          </div>
        </div>
      </div>
    </div>

    <template #footer>
      <div class="flex justify-end gap-3">
        <button
          @click="$emit('update:show', false)"
          class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition-colors"
        >
          Annuler
        </button>
        <button
          @click="handleStart"
          class="px-6 py-2 bg-gradient-to-r from-blue-600 to-violet-600 hover:from-blue-700 hover:to-violet-700 text-white rounded-lg font-semibold transition-all shadow-lg hover:shadow-xl flex items-center gap-2"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          Lancer l'extraction
        </button>
      </div>
    </template>
  </BaseModal>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import BaseModal from './BaseModal.vue'
import type { DialogueExtractionOptions } from '@/api/dialogueExtraction'
import { useServerCapabilities } from '@/composables/useServerCapabilities'

defineProps<{
  show: boolean
}>()

const emit = defineEmits<{
  'update:show': [value: boolean]
  'start': [options: DialogueExtractionOptions]
}>()

const { capabilities } = useServerCapabilities()

const formData = ref<DialogueExtractionOptions>({
  language: 'auto',
  max_speakers: 10,
  whisper_model: 'tiny'
})

const diarizationMethodLabel = computed(() => {
  const method = capabilities.value?.diarization_method
  if (method === 'resemblyzer') {
    return '🎤 Méthode avancée (précision 85-95%)'
  }
  return '🎵 Méthode standard (précision 30-50%)'
})

const diarizationMethodBadge = computed(() => {
  const method = capabilities.value?.diarization_method
  if (method === 'resemblyzer') {
    return {
      text: 'Avancée',
      subtitle: 'Serveur 4GB+ RAM',
      classes: 'bg-blue-500/20 border-blue-500/40 text-blue-400'
    }
  }
  return {
    text: 'Standard',
    subtitle: 'Serveur 2GB RAM',
    classes: 'bg-yellow-500/20 border-yellow-500/40 text-yellow-400'
  }
})

const showRamWarning = computed(() => {
  return capabilities.value?.diarization_fallback_reason !== undefined
})

const estimatedDuration = computed(() => {
  const model = formData.value.whisper_model
  const method = capabilities.value?.diarization_method

  let base = 'Variable'
  if (model === 'tiny') base = 'Variable (rapide)'
  if (model === 'base') base = 'Variable (moyen)'
  if (model === 'small') base = 'Variable (lent)'

  // Resemblyzer ajoute ~1-2min de plus
  if (method === 'resemblyzer') {
    base += ' + embeddings (~2min)'
  }

  return base
})

const handleStart = () => {
  emit('start', formData.value)
  emit('update:show', false)
}
</script>
