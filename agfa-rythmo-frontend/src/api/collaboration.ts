import axios from './axios'
import type { User } from './auth'

export interface Collaborator {
  id: number
  name: string
  email: string
  permission: 'edit' | 'admin'
  joined_at: string
}

export interface CollaboratorResponse {
  owner: User
  collaborators: Collaborator[]
}

export interface InviteCollaboratorData {
  user_email: string
  permission: 'edit' | 'admin'
  message?: string
}

export interface UpdatePermissionData {
  permission: 'edit' | 'admin'
}

export interface ProjectPendingInvitation {
  id: number
  invited_user: {
    id: number
    name: string
    email: string
  }
  permission: 'edit' | 'admin'
  created_at: string
  expires_at?: string
}

class CollaborationService {
  // Lister les collaborateurs d'un projet
  async getCollaborators(projectId: number): Promise<CollaboratorResponse> {
    const response = await axios.get<CollaboratorResponse>(`/projects/${projectId}/collaborators`)
    return response.data
  }

  // Inviter un collaborateur
  async inviteCollaborator(projectId: number, data: InviteCollaboratorData): Promise<{ message: string; invitation: object }> {
    const response = await axios.post<{ message: string; invitation: object }>(`/invitations`, {
      project_id: projectId,
      ...data
    })
    return response.data
  }

  // Mettre à jour les permissions d'un collaborateur
  async updatePermissions(projectId: number, userId: number, data: UpdatePermissionData): Promise<{ message: string; collaborator: Collaborator }> {
    const response = await axios.put<{ message: string; collaborator: Collaborator }>(`/projects/${projectId}/collaborators/${userId}`, data)
    return response.data
  }

  // Retirer un collaborateur
  async removeCollaborator(projectId: number, userId: number): Promise<{ message: string }> {
    const response = await axios.delete<{ message: string }>(`/projects/${projectId}/collaborators/${userId}`)
    return response.data
  }

  // Quitter un projet (pour le collaborateur)
  async leaveProject(projectId: number): Promise<{ message: string }> {
    const response = await axios.post<{ message: string }>(`/projects/${projectId}/leave`)
    return response.data
  }

  // Rechercher des utilisateurs à inviter
  async searchUsers(projectId: number, search: string): Promise<User[]> {
    const response = await axios.get<User[]>(`/projects/${projectId}/search-users`, {
      params: { search }
    })
    return response.data
  }

  // Récupérer les invitations en attente pour un projet
  async getPendingInvitations(projectId: number): Promise<ProjectPendingInvitation[]> {
    const response = await axios.get<ProjectPendingInvitation[]>(`/projects/${projectId}/invitations`)
    return response.data
  }

  // Annuler une invitation (pour le propriétaire du projet)
  async cancelInvitation(invitationId: number): Promise<{ message: string }> {
    const response = await axios.delete<{ message: string }>(`/invitations/${invitationId}`)
    return response.data
  }
}

export default new CollaborationService()
