import api from './axios'

export interface AnalysisStatus {
  analysis_status: 'none' | 'pending' | 'processing' | 'completed' | 'failed' | 'cancelled'
  analysis_progress: number  // 0-100
  analysis_message: string
  scene_changes_count: number
}

export interface AnalysisParams {
  threshold?: number  // 0.1 - 1.0
  fps?: number        // 1 - 30
}

/**
 * Lancer l'analyse automatique de détection de plans
 */
export async function startSceneAnalysis(
  projectId: number,
  params?: AnalysisParams
): Promise<{ message: string; analysis_status: string }> {
  const response = await api.post(`/projects/${projectId}/analyze-scenes`, params)
  return response.data
}

/**
 * Récupérer le statut de l'analyse
 */
export async function getAnalysisStatus(projectId: number): Promise<AnalysisStatus> {
  const response = await api.get(`/projects/${projectId}/analysis-status`)
  return response.data
}

/**
 * Annuler l'analyse en cours
 */
export async function cancelSceneAnalysis(projectId: number): Promise<{ message: string; analysis_status: string }> {
  const response = await api.post(`/projects/${projectId}/cancel-analysis`)
  return response.data
}
