import axios from './axios'

export interface SceneChange {
  id: number
  project_id: number
  timecode: number
  created_at?: string
  updated_at?: string
}

export interface CreateSceneChangeRequest {
  timecode: number
}

export interface UpdateSceneChangeRequest {
  timecode: number
}

// Récupérer tous les changements de plan d'un projet
export async function getSceneChanges(projectId: number): Promise<SceneChange[]> {
  const response = await axios.get(`/projects/${projectId}/scene-changes`)
  return response.data
}

// Créer un nouveau changement de plan
export async function createSceneChange(
  projectId: number,
  data: CreateSceneChangeRequest
): Promise<SceneChange> {
  const response = await axios.post(`/projects/${projectId}/scene-changes`, data)
  return response.data
}

// Modifier un changement de plan
export async function updateSceneChange(
  id: number,
  data: UpdateSceneChangeRequest
): Promise<SceneChange> {
  const response = await axios.put(`/scene-changes/${id}`, data)
  return response.data
}

// Supprimer un changement de plan
export async function deleteSceneChange(id: number): Promise<{ success: boolean }> {
  const response = await axios.delete(`/scene-changes/${id}`)
  return response.data
}