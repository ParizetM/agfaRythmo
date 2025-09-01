import api from './axios'

export interface Timecode {
  id?: number
  project_id: number
  line_number: number
  start: number
  end: number
  text: string
  created_at?: string
  updated_at?: string
}

export interface CreateTimecodeData {
  line_number: number
  start: number
  end: number
  text: string
}

export interface UpdateTimecodeData {
  line_number?: number
  start?: number
  end?: number
  text?: string
}

export const timecodeApi = {
  // Récupérer tous les timecodes d'un projet
  getAll(projectId: number) {
    return api.get<{ timecodes: Timecode[] }>(`/projects/${projectId}/timecodes`)
  },

  // Récupérer les timecodes d'une ligne spécifique
  getByLine(projectId: number, lineNumber: number) {
    return api.get<{ timecodes: Timecode[] }>(`/projects/${projectId}/timecodes/line/${lineNumber}`)
  },

  // Créer un nouveau timecode
  create(projectId: number, data: CreateTimecodeData) {
    return api.post<{ timecode: Timecode }>(`/projects/${projectId}/timecodes`, data)
  },

  // Mettre à jour un timecode
  update(projectId: number, timecodeId: number, data: UpdateTimecodeData) {
    return api.put<{ timecode: Timecode }>(`/projects/${projectId}/timecodes/${timecodeId}`, data)
  },

  // Supprimer un timecode
  delete(projectId: number, timecodeId: number) {
    return api.delete<{ message: string }>(`/projects/${projectId}/timecodes/${timecodeId}`)
  },

  // Récupérer un timecode spécifique
  getOne(projectId: number, timecodeId: number) {
    return api.get<{ timecode: Timecode }>(`/projects/${projectId}/timecodes/${timecodeId}`)
  }
}
