import axios from './axios'

export interface TranslationOptions {
  target_language: string
  source_language?: string | null
  use_character_context?: boolean
}

export interface TranslationStatus {
  translation_status: string | null
  translation_progress: number
  translation_message: string | null
  source_language: string | null
  target_language: string | null
  timecodes_count: number
}

export interface SupportedLanguage {
  code: string
  name: string
}

export interface SupportedLanguagesResponse {
  languages: SupportedLanguage[]
  provider: string
  error?: string
}

/**
 * Démarrer la traduction des dialogues
 */
export const startTranslation = async (
  projectId: number,
  options: TranslationOptions
): Promise<{ message: string; translation_status: string; timecodes_count: number }> => {
  const response = await axios.post(`/projects/${projectId}/translation/start`, options)
  return response.data
}

/**
 * Obtenir le statut de la traduction
 */
export const getTranslationStatus = async (projectId: number): Promise<TranslationStatus> => {
  const response = await axios.get(`/projects/${projectId}/translation/status`)
  return response.data
}

/**
 * Annuler la traduction en cours
 */
export const cancelTranslation = async (projectId: number): Promise<{ message: string }> => {
  const response = await axios.post(`/projects/${projectId}/translation/cancel`)
  return response.data
}

/**
 * Obtenir les langues supportées par le provider
 */
export const getSupportedLanguages = async (): Promise<SupportedLanguagesResponse> => {
  const response = await axios.get('/translation/supported-languages')
  return response.data
}
