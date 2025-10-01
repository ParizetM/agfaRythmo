import { defineStore } from 'pinia'
import { ref, watch } from 'vue'

export interface ProjectSettings {
  bandHeight: number // Hauteur de la bande rythmo en pixels
  fontSize: number // Taille de la police en rem
  fontFamily: string // Famille de police
  bandBackgroundColor: string // Couleur de fond de la bande
  sceneChangeColor: string // Couleur des changements de scène
  overlayPosition: 'under' | 'over' // Position de la bande par rapport à la vidéo
}

const DEFAULT_SETTINGS: ProjectSettings = {
  bandHeight: 80,
  fontSize: 2.1,
  fontFamily: 'Inter',
  bandBackgroundColor: '#202937',
  sceneChangeColor: '#8455F6',
  overlayPosition: 'under',
}

const STORAGE_KEY = 'agfaRythmo_projectSettings'

export const useProjectSettingsStore = defineStore('projectSettings', () => {
  // État
  const settings = ref<ProjectSettings>({ ...DEFAULT_SETTINGS })

  // Charger les paramètres depuis localStorage
  function loadSettings() {
    try {
      const stored = localStorage.getItem(STORAGE_KEY)
      if (stored) {
        const parsed = JSON.parse(stored)
        settings.value = { ...DEFAULT_SETTINGS, ...parsed }
      }
    } catch (error) {
      console.error('Erreur lors du chargement des paramètres:', error)
    }
  }

  // Sauvegarder les paramètres dans localStorage
  function saveSettings() {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(settings.value))
    } catch (error) {
      console.error('Erreur lors de la sauvegarde des paramètres:', error)
    }
  }

  // Mettre à jour un paramètre
  function updateSetting<K extends keyof ProjectSettings>(key: K, value: ProjectSettings[K]) {
    settings.value[key] = value
    saveSettings()
  }

  // Réinitialiser aux valeurs par défaut
  function resetSettings() {
    settings.value = { ...DEFAULT_SETTINGS }
    saveSettings()
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

  // Initialiser au chargement
  loadSettings()

  return {
    settings,
    updateSetting,
    resetSettings,
    loadGoogleFont,
  }
})
