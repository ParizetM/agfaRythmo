import api from './axios'

export interface InstrumentalStatus {
  status: 'not_generated' | 'processing' | 'completed' | 'failed'
  progress: number
  audio_path: string | null
}

/**
 * Générer la piste instrumentale (sans voix) pour un projet
 */
export async function generateInstrumental(projectId: number): Promise<InstrumentalStatus> {
  const response = await api.post(`/projects/${projectId}/instrumental`)
  return response.data
}

/**
 * Récupérer le statut de génération de la piste instrumentale
 */
export async function getInstrumentalStatus(projectId: number): Promise<InstrumentalStatus> {
  const response = await api.get(`/projects/${projectId}/instrumental/status`)
  return response.data
}

/**
 * Supprimer la piste instrumentale générée
 */
export async function deleteInstrumental(projectId: number): Promise<void> {
  await api.delete(`/projects/${projectId}/instrumental`)
}
