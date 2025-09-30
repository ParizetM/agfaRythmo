import api from './axios'

export interface Character {
  id: number
  project_id: number
  name: string
  color: string
  text_color?: string | null
  created_at?: string
  updated_at?: string
  project?: {
    id: number
    name: string
  }
}

export interface CreateCharacterData {
  project_id: number
  name: string
  color: string
  text_color?: string | null
}

export interface UpdateCharacterData {
  name?: string
  color?: string
  text_color?: string | null
}

export interface CloneCharacterData {
  source_character_id: number
  target_project_id: number
}

export const characterApi = {
  // Récupérer tous les personnages d'un projet
  getAll: (projectId: number) => {
    return api.get<{ characters: Character[] }>(`/projects/${projectId}/characters`)
  },

  // Créer un nouveau personnage
  create: (data: CreateCharacterData) => {
    return api.post<Character>('/characters', data)
  },

  // Modifier un personnage
  update: (id: number, data: UpdateCharacterData) => {
    return api.put<Character>(`/characters/${id}`, data)
  },

  // Supprimer un personnage
  delete: (id: number, deleteTimecodes: boolean = false) => {
    return api.delete(`/characters/${id}`, {
      params: { deleteTimecodes }
    })
  },

  // Cloner un personnage d'un autre projet
  clone: (data: CloneCharacterData) => {
    return api.post<Character>('/characters/clone', data)
  },

  // Récupérer les personnages disponibles pour le clonage
  getForCloning: (excludeProjectId?: number) => {
    return api.get<{ characters: Character[] }>('/characters/for-cloning', {
      params: excludeProjectId ? { exclude_project_id: excludeProjectId } : {}
    })
  }
}
