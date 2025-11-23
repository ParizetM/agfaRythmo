import { defineStore } from 'pinia'
import { ref, watch } from 'vue'
import { fetchProjectSettings, updateProjectSettings } from '../api/projectSettings'
import {
  fetchUserPresets,
  createPreset,
  deletePreset as deletePresetApi,
  type SettingsPreset,
} from '../api/settingsPresets'

export interface ProjectSettings {
  bandHeight: number // Hauteur de la bande rythmo en pixels
  fontSize: number // Taille de la police en rem
  fontFamily: string // Famille de police
  fontWeight: number // Poids de la police (100-900)
  bandBackgroundColor: string // Couleur de fond de la bande
  sceneChangeColor: string // Couleur des changements de scène
  overlayPosition: 'over' | 'under-full' | 'under-video-width' | 'contained-16-9' // Position de la bande par rapport à la vidéo
  bandScale: number // Échelle de la bande dans l'aperçu final (1.0 = 100%)
}

const DEFAULT_SETTINGS: ProjectSettings = {
  bandHeight: 50,
  fontSize: 1.5,
  fontFamily: 'Lexend',
  fontWeight: 400,
  bandBackgroundColor: '#202937',
  sceneChangeColor: '#8455F6',
  overlayPosition: 'under-full',
  bandScale: 2,
}

export const useProjectSettingsStore = defineStore('projectSettings', () => {
  // État
  const settings = ref<ProjectSettings>({ ...DEFAULT_SETTINGS })
  const currentProjectId = ref<number | null>(null)
  const isLoading = ref(false)

  // Presets utilisateur (max 5)
  const userPresets = ref<SettingsPreset[]>([])
  const presetsLoading = ref(false)

  // Charger les paramètres depuis l'API pour un projet spécifique
  async function loadSettings(projectId: number) {
    if (currentProjectId.value === projectId) {
      // Déjà chargé pour ce projet
      return
    }

    isLoading.value = true
    currentProjectId.value = projectId

    try {
      const projectSettings = await fetchProjectSettings(projectId)

      if (projectSettings) {
        // Utiliser les paramètres du projet
        settings.value = { ...DEFAULT_SETTINGS, ...projectSettings }
      } else {
        // Utiliser les paramètres par défaut si aucun n'est défini
        settings.value = { ...DEFAULT_SETTINGS }
      }
    } catch (error) {
      console.error('Erreur lors du chargement des paramètres:', error)
      settings.value = { ...DEFAULT_SETTINGS }
    } finally {
      isLoading.value = false
    }
  }

  // Sauvegarder les paramètres via l'API
  async function saveSettings() {
    if (!currentProjectId.value) {
      console.warn('Aucun projet actif pour sauvegarder les paramètres')
      return
    }

    try {
      const success = await updateProjectSettings(currentProjectId.value, settings.value)
      if (!success) {
        console.error('Échec de la sauvegarde des paramètres')
        throw new Error('Échec de la sauvegarde des paramètres')
      }
    } catch (error) {
      console.error('Erreur lors de la sauvegarde des paramètres:', error)
      throw error
    }
  }

  // Mettre à jour un paramètre et sauvegarder
  async function updateSetting<K extends keyof ProjectSettings>(key: K, value: ProjectSettings[K]) {
    settings.value[key] = value
    await saveSettings()
  }

  // Réinitialiser aux valeurs par défaut et sauvegarder
  async function resetSettings() {
    settings.value = { ...DEFAULT_SETTINGS }
    await saveSettings()
  }

  // Charger dynamiquement une police Google Fonts
  function loadGoogleFont(fontFamily: string) {
    const link = document.getElementById('google-font-dynamic') as HTMLLinkElement
    if (link) {
      link.href = `https://fonts.googleapis.com/css2?family=${fontFamily.replace(/ /g, '+')}:wght@400;600;700&display=swap`
    } else {
      const newLink = document.createElement('link')
      newLink.id = 'google-font-dynamic'
      newLink.rel = 'stylesheet'
      newLink.href = `https://fonts.googleapis.com/css2?family=${fontFamily.replace(/ /g, '+')}:wght@400;600;700&display=swap`
      document.head.appendChild(newLink)
    }
  }

  // Charger les presets de l'utilisateur
  async function loadUserPresets() {
    presetsLoading.value = true
    try {
      const presets = await fetchUserPresets()
      userPresets.value = presets
    } catch (error) {
      console.error('Erreur lors du chargement des presets:', error)
      userPresets.value = []
    } finally {
      presetsLoading.value = false
    }
  }

  // Sauvegarder les paramètres actuels comme preset
  async function saveAsPreset(name: string) {
    try {
      const newPreset = await createPreset(name, settings.value)
      if (newPreset) {
        userPresets.value.push(newPreset)
        return true
      }
      return false
    } catch (error) {
      console.error('Erreur lors de la sauvegarde du preset:', error)
      throw error
    }
  }

  // Appliquer un preset au projet actuel
  async function applyPreset(presetId: number) {
    const preset = userPresets.value.find((p) => p.id === presetId)
    if (!preset) {
      console.error('Preset introuvable')
      return false
    }

    try {
      settings.value = { ...preset.settings }
      await saveSettings()
      return true
    } catch (error) {
      console.error("Erreur lors de l'application du preset:", error)
      return false
    }
  }

  // Supprimer un preset
  async function deletePreset(presetId: number) {
    try {
      const success = await deletePresetApi(presetId)
      if (success) {
        userPresets.value = userPresets.value.filter((p) => p.id !== presetId)
        return true
      }
      return false
    } catch (error) {
      console.error('Erreur lors de la suppression du preset:', error)
      return false
    }
  }

  // Watcher pour charger la police quand elle change
  watch(
    () => settings.value.fontFamily,
    (newFont) => {
      if (newFont && newFont !== 'Inter') {
        loadGoogleFont(newFont)
      }
    },
    { immediate: true }
  )

  return {
    settings,
    currentProjectId,
    isLoading,
    userPresets,
    presetsLoading,
    loadSettings,
    updateSetting,
    resetSettings,
    loadGoogleFont,
    loadUserPresets,
    saveAsPreset,
    applyPreset,
    deletePreset,
  }
})
