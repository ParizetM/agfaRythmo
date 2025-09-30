import { ref, onMounted } from 'vue';
import { invitationService } from '../api/invitations';

export function useInvitations() {
  const invitationCount = ref(0);
  const loading = ref(false);

  const loadInvitationCount = async () => {
    try {
      loading.value = true;
      const invitations = await invitationService.getPendingInvitations();
      invitationCount.value = invitations.length;
    } catch (error) {
      console.error('Erreur lors du chargement des invitations:', error);
      invitationCount.value = 0;
    } finally {
      loading.value = false;
    }
  };

  const refreshCount = () => {
    loadInvitationCount();
  };

  onMounted(() => {
    loadInvitationCount();
  });

  return {
    invitationCount,
    loading,
    refreshCount
  };
}
