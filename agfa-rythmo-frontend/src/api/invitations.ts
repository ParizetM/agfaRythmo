import axios from './axios';

export interface ProjectInvitation {
  id: number;
  project: {
    id: number;
    name: string;
    description: string;
  };
  invited_by: {
    id: number;
    name: string;
    email: string;
  };
  permission: 'edit' | 'admin';
  message?: string;
  created_at: string;
  expires_at?: string;
}

export interface SendInvitationRequest {
  project_id: number;
  user_email: string;
  permission: 'edit' | 'admin';
  message?: string;
}

export const invitationService = {
  // Obtenir les invitations en attente pour l'utilisateur connecté
  async getPendingInvitations(): Promise<ProjectInvitation[]> {
    const response = await axios.get('/invitations');
    return response.data;
  },

  // Envoyer une invitation
  async sendInvitation(data: SendInvitationRequest) {
    const response = await axios.post('/invitations', data);
    return response.data;
  },

  // Accepter une invitation
  async acceptInvitation(invitationId: number) {
    const response = await axios.post(`/invitations/${invitationId}/accept`);
    return response.data;
  },

  // Refuser une invitation
  async declineInvitation(invitationId: number) {
    const response = await axios.post(`/invitations/${invitationId}/decline`);
    return response.data;
  },

  // Annuler une invitation (pour le propriétaire du projet)
  async cancelInvitation(invitationId: number) {
    const response = await axios.delete(`/invitations/${invitationId}`);
    return response.data;
  },
};
