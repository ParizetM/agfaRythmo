<template>
  <div class="space-y-6">
    <!-- En-tête avec compteur -->
    <div class="flex items-center justify-between">
      <h3 class="text-lg font-semibold text-white">Mes Presets</h3>
      <span class="px-3 py-1 bg-gradient-to-r from-blue-500/20 to-purple-600/20 border border-blue-500/30 rounded-lg text-blue-300 text-sm font-medium">
        {{ userPresets.length }} / 5
      </span>
    </div>

    <!-- Grille des presets -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="index in 5"
        :key="index"
        class="relative rounded-xl overflow-hidden transition-all duration-300"
        :class="getPresetAtIndex(index - 1)
          ? 'bg-gradient-to-br from-agfa-bg-primary to-agfa-bg-tertiary border-2 border-blue-500/30 hover:border-blue-500/50 hover:shadow-lg hover:shadow-blue-500/20'
          : 'bg-agfa-bg-primary border-2 border-dashed border-gray-600 hover:border-gray-500'"
      >
        <template v-if="getPresetAtIndex(index - 1)">
          <!-- Preset rempli -->
          <div class="p-4 space-y-3">
            <!-- Header avec nom et numéro -->
            <div class="flex items-start justify-between pb-3 border-b border-gray-700">
              <h4 class="text-base font-semibold text-white truncate pr-2">
                {{ getPresetAtIndex(index - 1)!.name }}
              </h4>
              <span class="flex-shrink-0 w-6 h-6 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white text-xs font-bold shadow-lg">
                {{ index }}
              </span>
            </div>

            <!-- Informations du preset -->
            <div class="space-y-2">
              <div class="flex justify-between items-center text-sm">
                <span class="text-gray-400">Hauteur</span>
                <span class="text-white font-medium">{{ getPresetAtIndex(index - 1)!.settings.bandHeight }}px</span>
              </div>
              <div class="flex justify-between items-center text-sm">
                <span class="text-gray-400">Police</span>
                <span class="text-white font-medium">{{ getPresetAtIndex(index - 1)!.settings.fontSize }}rem</span>
              </div>
              <div class="flex justify-between items-center text-sm">
                <span class="text-gray-400">Font</span>
                <span class="text-white font-medium truncate max-w-[120px]" :title="getPresetAtIndex(index - 1)!.settings.fontFamily">
                  {{ getPresetAtIndex(index - 1)!.settings.fontFamily }}
                </span>
              </div>
            </div>

            <!-- Boutons d'action -->
            <div class="flex gap-2 pt-2">
              <button
                @click="applyPreset(getPresetAtIndex(index - 1)!.id)"
                class="flex-1 flex items-center justify-center gap-2 px-3 py-2 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white text-sm font-semibold rounded-lg transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98] shadow-md"
                title="Appliquer ce preset"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Appliquer
              </button>
              <button
                @click="confirmDelete(getPresetAtIndex(index - 1)!.id)"
                class="px-3 py-2 bg-red-500/20 hover:bg-red-500/30 border border-red-500/30 hover:border-red-500/50 text-red-400 hover:text-red-300 rounded-lg transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98]"
                title="Supprimer ce preset"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
              </button>
            </div>
          </div>
        </template>

        <template v-else>
          <!-- Slot vide -->
          <div class="p-6 flex flex-col items-center justify-center min-h-[200px]">
            <div class="w-12 h-12 rounded-full bg-gray-700/50 flex items-center justify-center mb-3">
              <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
              </svg>
            </div>
            <p class="text-gray-500 text-sm font-medium">Slot {{ index }} vide</p>
          </div>
        </template>
      </div>
    </div>

    <!-- Formulaire de sauvegarde -->
    <div class="bg-gradient-to-br from-agfa-bg-primary to-agfa-bg-tertiary rounded-xl border border-gray-700 p-6 space-y-4">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center shadow-lg flex-shrink-0">
          <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
          </svg>
        </div>
        <div class="flex-1">
          <h4 class="text-base font-semibold text-white">Sauvegarder les paramètres actuels</h4>
          <p class="text-xs text-gray-400 mt-0.5">Créez un nouveau preset avec vos réglages</p>
        </div>
      </div>

      <div class="flex flex-col sm:flex-row gap-3">
        <input
          v-model="newPresetName"
          type="text"
          placeholder="Nom du preset (ex: Cinéma, TV, Web...)"
          maxlength="255"
          class="flex-1 px-4 py-3 bg-agfa-bg-primary border border-gray-600 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent outline-none transition-all duration-300 text-white placeholder-gray-500 hover:border-gray-500"
          @keyup.enter="saveCurrentAsPreset"
        />
        <button
          @click="saveCurrentAsPreset"
          :disabled="!canSavePreset"
          class="px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 disabled:from-gray-600 disabled:to-gray-700 disabled:cursor-not-allowed text-white font-semibold rounded-xl transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98] shadow-lg shadow-green-500/25 disabled:shadow-none flex items-center justify-center gap-2 whitespace-nowrap"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
          </svg>
          Sauvegarder
        </button>
      </div>

      <!-- Message d'erreur -->
      <div v-if="saveError" class="p-3 bg-red-500/10 border border-red-500/30 rounded-lg">
        <p class="text-red-400 text-sm flex items-center gap-2">
          <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
          </svg>
          {{ saveError }}
        </p>
      </div>

      <!-- Info limite -->
      <div v-if="userPresets.length >= 5" class="p-3 bg-yellow-500/10 border border-yellow-500/30 rounded-lg">
        <p class="text-yellow-400 text-sm flex items-center gap-2">
          <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
          </svg>
          Limite atteinte : supprimez un preset existant pour en créer un nouveau
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useProjectSettingsStore } from '../../stores/projectSettings'
import type { SettingsPreset } from '../../api/settingsPresets'

const emit = defineEmits<{
  'preset-applied': []
}>()

const settingsStore = useProjectSettingsStore()
const userPresets = computed(() => settingsStore.userPresets)

const newPresetName = ref('')
const saveError = ref('')

// Récupère le preset à un index donné (0-4)
function getPresetAtIndex(index: number): SettingsPreset | null {
  return userPresets.value[index] || null
}

// Peut sauvegarder un preset si moins de 5 et nom non vide
const canSavePreset = computed(() => {
  return userPresets.value.length < 5 && newPresetName.value.trim().length > 0
})

// Sauvegarder les paramètres actuels comme preset
async function saveCurrentAsPreset() {
  if (!canSavePreset.value) return

  saveError.value = ''
  try {
    await settingsStore.saveAsPreset(newPresetName.value.trim())
    newPresetName.value = ''
  } catch (error: unknown) {
    if (error instanceof Error) {
      saveError.value = error.message
    } else {
      saveError.value = 'Erreur lors de la sauvegarde du preset'
    }
  }
}

// Appliquer un preset
async function applyPreset(presetId: number) {
  const success = await settingsStore.applyPreset(presetId)
  if (success) {
    emit('preset-applied')
  }
}

// Confirmer la suppression d'un preset
function confirmDelete(presetId: number) {
  const preset = userPresets.value.find((p) => p.id === presetId)
  if (!preset) return

  if (confirm(`Voulez-vous vraiment supprimer le preset "${preset.name}" ?`)) {
    settingsStore.deletePreset(presetId)
  }
}
</script>
