import api from './axios'
import type { ProjectSettings } from '../stores/projectSettings'

export interface SettingsPreset {
  id: number
  user_id: number
  name: string
  settings: ProjectSettings
  created_at: string
  updated_at: string
}

/**
 * Récupérer tous les presets de l'utilisateur connecté
 */
export async function fetchUserPresets(): Promise<SettingsPreset[]> {
  try {
    const response = await api.get('/settings-presets')
    return response.data
  } catch (error) {
    console.error('Erreur lors de la récupération des presets:', error)
    return []
  }
}

/**
 * Créer un nouveau preset
 */
export async function createPreset(
  name: string,
  settings: ProjectSettings
): Promise<SettingsPreset | null> {
  try {
    const response = await api.post('/settings-presets', {
      name,
      settings,
    })
    return response.data
  } catch (error) {
    console.error('Erreur lors de la création du preset:', error)
    if (error && typeof error === 'object' && 'response' in error) {
      const axiosError = error as { response?: { data?: { message?: string } } }
      if (axiosError.response?.data?.message) {
        throw new Error(axiosError.response.data.message)
      }
    }
    throw error
  }
}

/**
 * Mettre à jour un preset existant
 */
export async function updatePreset(
  id: number,
  name: string,
  settings: ProjectSettings
): Promise<SettingsPreset | null> {
  try {
    const response = await api.put(`/settings-presets/${id}`, {
      name,
      settings,
    })
    return response.data
  } catch (error) {
    console.error('Erreur lors de la mise à jour du preset:', error)
    return null
  }
}

/**
 * Supprimer un preset
 */
export async function deletePreset(id: number): Promise<boolean> {
  try {
    await api.delete(`/settings-presets/${id}`)
    return true
  } catch (error) {
    console.error('Erreur lors de la suppression du preset:', error)
    return false
  }
}
