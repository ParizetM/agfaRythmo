/**
 * Service API pour vérifier les capacités du serveur
 * Permet de détecter les fonctionnalités disponibles (FFmpeg, workers, etc.)
 */
import axios from './axios';

export interface SupportedLanguage {
  code: string;
  name: string;
}

export interface ServerCapabilities {
  scene_detection: boolean;
  dialogue_extraction: boolean;
  translation: boolean;
  auto_subtitles: boolean;
  voice_recognition: boolean;
  audio_analysis: boolean;
  supported_languages?: SupportedLanguage[];
  translation_provider?: string;
  diarization_method?: 'mfcc' | 'resemblyzer';
  diarization_fallback_reason?: string;
  available_ram_mb?: number;
}

/**
 * Récupère les capacités du serveur
 * Endpoint public (pas besoin d'authentification)
 */
export const getServerCapabilities = async (): Promise<ServerCapabilities> => {
  const response = await axios.get<ServerCapabilities>('/server/capabilities');
  return response.data;
};
