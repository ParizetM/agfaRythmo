<template>
  <div class="presets-manager">
    <h3 class="text-lg font-semibold text-gray-300 mb-4">Mes Presets ({{ userPresets.length }}/5)</h3>

    <!-- Liste des slots de presets -->
    <div class="presets-grid">
      <div
        v-for="index in 5"
        :key="index"
        class="preset-slot"
        :class="{ 'preset-filled': getPresetAtIndex(index - 1) }"
      >
        <template v-if="getPresetAtIndex(index - 1)">
          <!-- Preset rempli -->
          <div class="preset-content">
            <div class="preset-header">
              <h4 class="preset-name">{{ getPresetAtIndex(index - 1)!.name }}</h4>
              <span class="preset-slot-number">{{ index }}</span>
            </div>

            <div class="preset-info">
              <div class="preset-param">
                <span class="param-label">Hauteur:</span>
                <span class="param-value">{{ getPresetAtIndex(index - 1)!.settings.bandHeight }}px</span>
              </div>
              <div class="preset-param">
                <span class="param-label">Police:</span>
                <span class="param-value">{{ getPresetAtIndex(index - 1)!.settings.fontSize }}rem</span>
              </div>
              <div class="preset-param">
                <span class="param-label">Font:</span>
                <span class="param-value truncate">{{ getPresetAtIndex(index - 1)!.settings.fontFamily }}</span>
              </div>
            </div>

            <div class="preset-actions">
              <button
                @click="applyPreset(getPresetAtIndex(index - 1)!.id)"
                class="btn-apply"
                title="Appliquer ce preset"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Appliquer
              </button>
              <button
                @click="confirmDelete(getPresetAtIndex(index - 1)!.id)"
                class="btn-delete"
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
          <div class="preset-empty">
            <div class="empty-icon">
              <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
              </svg>
            </div>
            <p class="text-gray-500 text-sm">Slot {{ index }} vide</p>
          </div>
        </template>
      </div>
    </div>

    <!-- Formulaire de création de preset -->
    <div class="save-preset-form">
      <h4 class="text-md font-semibold text-gray-300 mb-3">Sauvegarder les paramètres actuels</h4>
      <div class="form-group">
        <input
          v-model="newPresetName"
          type="text"
          placeholder="Nom du preset (ex: Cinéma, TV, Web...)"
          maxlength="255"
          class="preset-name-input"
          @keyup.enter="saveCurrentAsPreset"
        />
        <button
          @click="saveCurrentAsPreset"
          :disabled="!canSavePreset"
          class="btn-save"
          :class="{ 'btn-disabled': !canSavePreset }"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
          </svg>
          Sauvegarder
        </button>
      </div>
      <p v-if="saveError" class="error-message">{{ saveError }}</p>
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

<style scoped>
.presets-manager {
  width: 100%;
}

.presets-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 2rem;
}

.preset-slot {
  background-color: #1f2937;
  border: 2px solid #374151;
  border-radius: 0.5rem;
  padding: 1rem;
  min-height: 180px;
  transition: all 0.2s ease;
}

.preset-slot.preset-filled {
  border-color: #8455F6;
}

.preset-slot.preset-filled:hover {
  border-color: #9d6fff;
  box-shadow: 0 0 0 2px rgba(132, 85, 246, 0.2);
}

.preset-content {
  display: flex;
  flex-direction: column;
  height: 100%;
  gap: 0.75rem;
}

.preset-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid #374151;
}

.preset-name {
  font-size: 1rem;
  font-weight: 600;
  color: white;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.preset-slot-number {
  background-color: #8455F6;
  color: white;
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.125rem 0.5rem;
  border-radius: 0.25rem;
  flex-shrink: 0;
}

.preset-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.preset-param {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.875rem;
}

.param-label {
  color: #9ca3af;
}

.param-value {
  color: white;
  font-weight: 500;
}

.preset-actions {
  display: flex;
  gap: 0.5rem;
}

.btn-apply {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  background-color: #8455F6;
  color: white;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  font-weight: 600;
  transition: background-color 0.2s;
  border: none;
  cursor: pointer;
}

.btn-apply:hover {
  background-color: #7c3aed;
}

.btn-delete {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0.5rem;
  background-color: #dc2626;
  color: white;
  border-radius: 0.375rem;
  transition: background-color 0.2s;
  border: none;
  cursor: pointer;
}

.btn-delete:hover {
  background-color: #b91c1c;
}

.preset-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  padding: 1rem;
}

.empty-icon {
  margin-bottom: 0.5rem;
  opacity: 0.5;
}

.save-preset-form {
  background-color: #1f2937;
  border: 2px solid #374151;
  border-radius: 0.5rem;
  padding: 1.5rem;
}

.form-group {
  display: flex;
  gap: 0.75rem;
  align-items: stretch;
}

.preset-name-input {
  flex: 1;
  padding: 0.75rem 1rem;
  background-color: #374151;
  border: 1px solid #4b5563;
  border-radius: 0.375rem;
  color: white;
  font-size: 0.875rem;
}

.preset-name-input:focus {
  outline: none;
  border-color: #8455F6;
  box-shadow: 0 0 0 2px rgba(132, 85, 246, 0.2);
}

.preset-name-input::placeholder {
  color: #6b7280;
}

.btn-save {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  background-color: #10b981;
  color: white;
  border-radius: 0.375rem;
  font-weight: 600;
  transition: background-color 0.2s;
  border: none;
  cursor: pointer;
  white-space: nowrap;
}

.btn-save:hover:not(.btn-disabled) {
  background-color: #059669;
}

.btn-disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.error-message {
  margin-top: 0.75rem;
  padding: 0.75rem;
  background-color: rgba(220, 38, 38, 0.1);
  border: 1px solid #dc2626;
  border-radius: 0.375rem;
  color: #fca5a5;
  font-size: 0.875rem;
}

.truncate {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>
