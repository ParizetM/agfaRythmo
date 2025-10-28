/**
 * Service API pour vérifier les capacités du serveur
 * Permet de détecter les fonctionnalités disponibles (FFmpeg, workers, etc.)
 */
import axios from './axios';

export interface ServerCapabilities {
  scene_detection: boolean;
  auto_subtitles: boolean;
  voice_recognition: boolean;
  audio_analysis: boolean;
}

/**
 * Récupère les capacités du serveur
 * Endpoint public (pas besoin d'authentification)
 */
export const getServerCapabilities = async (): Promise<ServerCapabilities> => {
  const response = await axios.get<ServerCapabilities>('/server/capabilities');
  return response.data;
};
