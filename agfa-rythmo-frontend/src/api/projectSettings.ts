import api from './axios'

export interface ProjectSettings {
  bandHeight: number
  fontSize: number
  fontFamily: string
  bandBackgroundColor: string
  sceneChangeColor: string
  overlayPosition: 'under' | 'over'
}

/**
 * Récupérer les paramètres d'un projet depuis l'API
 */
export async function fetchProjectSettings(projectId: number): Promise<ProjectSettings | null> {
  try {
    const response = await api.get(`/projects/${projectId}`)
    return response.data.project_settings || null
  } catch (error) {
    console.error('Erreur lors de la récupération des paramètres du projet:', error)
    return null
  }
}

/**
 * Mettre à jour les paramètres d'un projet via l'API
 */
export async function updateProjectSettings(
  projectId: number,
  settings: ProjectSettings
): Promise<boolean> {
  try {
    await api.patch(`/projects/${projectId}/settings`, {
      project_settings: settings,
    })
    return true
  } catch (error) {
    console.error('Erreur lors de la mise à jour des paramètres du projet:', error)
    return false
  }
}
