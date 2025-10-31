import axios from './axios'

// Types
export type DialogueExtractionStatus = 'pending' | 'processing' | 'completed' | 'failed' | 'cancelled' | null

export interface DialogueExtractionOptions {
  language?: string // 'auto' | 'en' | 'fr' | 'zh' | 'ja' | etc.
  max_speakers?: number // 2-20
  whisper_model?: 'tiny' | 'base' | 'small' | 'medium' | 'large'
}

export interface DialogueExtractionStatusResponse {
  dialogue_extraction_status: DialogueExtractionStatus
  dialogue_extraction_progress: number // 0-100
  dialogue_extraction_message: string
  timecodes_count: number
  characters_count: number
  source_language?: string // Langue détectée par Whisper (ISO 639-1)
}

export interface DialogueExtractionStartResponse {
  message: string
  dialogue_extraction_status: DialogueExtractionStatus
  parameters: {
    language: string
    max_speakers: number
    whisper_model: string
  }
}

/**
 * Lancer l'extraction automatique de dialogues
 */
export const startDialogueExtraction = async (
  projectId: number,
  options: DialogueExtractionOptions = {}
): Promise<DialogueExtractionStartResponse> => {
  const response = await axios.post(`/projects/${projectId}/dialogue-extraction/start`, options)
  return response.data
}

/**
 * Récupérer le statut de l'extraction de dialogues
 */
export const getDialogueExtractionStatus = async (
  projectId: number
): Promise<DialogueExtractionStatusResponse> => {
  const response = await axios.get(`/projects/${projectId}/dialogue-extraction/status`)
  return response.data
}

/**
 * Annuler l'extraction de dialogues en cours
 */
export const cancelDialogueExtraction = async (projectId: number): Promise<{ message: string; dialogue_extraction_status: DialogueExtractionStatus }> => {
  const response = await axios.post(`/projects/${projectId}/dialogue-extraction/cancel`)
  return response.data
}

export default {
  startDialogueExtraction,
  getDialogueExtractionStatus,
  cancelDialogueExtraction,
}
