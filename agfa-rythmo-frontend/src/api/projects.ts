import api from './axios'
import type { ProjectSettings } from './projectSettings'

export interface Project {
  id: number
  name: string
  description?: string
  video_path?: string
  rythmo_lines_count: number
  project_settings?: ProjectSettings
  user_id: number
  status: 'in_progress' | 'completed'
  created_at: string
  updated_at: string
  // Instrumental audio feature
  instrumental_audio_path?: string | null
  instrumental_status?: 'not_generated' | 'processing' | 'completed' | 'failed'
  instrumental_progress?: number
}

export interface ProjectExportData {
  export_version: string
  export_date: string
  project: {
    name: string
    description?: string
    rythmo_lines_count: number
    project_settings?: ProjectSettings
    video_path?: string
  }
  timecodes: Array<{
    line_number: number
    start: number
    end: number
    text: string
    character_name?: string
    show_character: boolean
    separator_positions: number[]
  }>
  characters: Array<{
    name: string
    color: string
    text_color: string
  }>
  scene_changes: Array<{
    timecode: number
  }>
}

/**
 * Récupérer tous les projets accessibles
 */
export async function fetchProjects() {
  const response = await api.get('/projects')
  return response.data
}

/**
 * Récupérer un projet spécifique
 */
export async function fetchProject(projectId: number) {
  const response = await api.get(`/projects/${projectId}`)
  return response.data
}

/**
 * Créer un nouveau projet
 */
export async function createProject(data: {
  name: string
  description?: string
  video_path?: string
  rythmo_lines_count?: number
  status?: 'in_progress' | 'completed'
}) {
  const response = await api.post('/projects', data)
  return response.data
}

/**
 * Mettre à jour un projet
 */
export async function updateProject(projectId: number, data: Partial<Project>) {
  const response = await api.put(`/projects/${projectId}`, data)
  return response.data
}

/**
 * Supprimer un projet
 */
export async function deleteProject(projectId: number) {
  const response = await api.delete(`/projects/${projectId}`)
  return response.data
}

/**
 * Exporter un projet en format .agfa crypté
 */
export async function exportProject(projectId: number): Promise<ProjectExportData> {
  const response = await api.get(`/projects/${projectId}/export`, {
    responseType: 'json'
  })
  return response.data
}

/**
 * Importer un projet depuis un fichier JSON
 */
export async function importProject(file: File, name: string) {
  const formData = new FormData()
  formData.append('file', file)
  formData.append('name', name)

  const response = await api.post('/projects/import', formData, {
    headers: {
      'Content-Type': 'multipart/form-data',
    },
  })
  return response.data
}

/**
 * Mettre à jour le nombre de lignes rythmo
 */
export async function updateRythmoLinesCount(projectId: number, count: number) {
  const response = await api.patch(`/projects/${projectId}/rythmo-lines`, {
    rythmo_lines_count: count,
  })
  return response.data
}
