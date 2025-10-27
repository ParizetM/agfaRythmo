<template>
  <!-- Affichage seulement s'il y a des invitations ou en cours de chargement -->
  <div v-if="loading || invitations.length > 0" class="bg-agfa-bg-secondary rounded-lg shadow-sm border border-gray-600 p-6">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-lg font-semibold text-white flex items-center">
        <EnvelopeIcon class="h-5 w-5 mr-2 text-agfa-blue" />
        Invitations en attente
        <span v-if="invitations.length > 0" class="ml-2 bg-red-900 bg-opacity-30 text-red-300 text-xs font-medium px-2 py-1 rounded-full">
          {{ invitations.length }}
        </span>
      </h2>

      <button
        @click="refreshInvitations"
        :disabled="loading"
        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-agfa-blue hover:bg-agfa-blue-hover focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-agfa-blue disabled:opacity-50"
      >
        <ArrowPathIcon :class="['h-4 w-4 mr-1', loading ? 'animate-spin' : '']" />
        Actualiser
      </button>
    </div>

    <div v-if="loading" class="text-center py-8">
      <div class="inline-flex items-center">
        <ArrowPathIcon class="animate-spin h-5 w-5 mr-2 text-gray-400" />
        <span class="text-gray-300">Chargement des invitations...</span>
      </div>
    </div>

    <div v-else class="space-y-4">
      <div
        v-for="invitation in invitations"
        :key="invitation.id"
        class="border border-gray-600 rounded-lg p-4 hover:border-gray-500 transition-colors bg-agfa-bg-primary"
      >
        <div class="flex items-start justify-between">
          <div class="flex-1">
            <div class="flex items-center space-x-2 mb-2">
              <h3 class="text-lg font-medium text-white">{{ invitation.project.name }}</h3>
              <span :class="[
                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                getPermissionStyle(invitation.permission)
              ]">
                {{ getPermissionLabel(invitation.permission) }}
              </span>
            </div>

            <p v-if="invitation.project.description" class="text-sm text-gray-300 mb-2">
              {{ invitation.project.description }}
            </p>

            <div class="flex items-center text-sm text-gray-400 mb-2">
              <UserIcon class="h-4 w-4 mr-1" />
              Invité par <strong class="text-white"> {{ invitation.invited_by.name }} </strong>
              ({{ invitation.invited_by.email }})
            </div>

            <p v-if="invitation.message" class="text-sm text-gray-300 bg-agfa-bg-secondary rounded-md p-2 mb-2">
              "{{ invitation.message }}"
            </p>

            <div class="flex items-center text-xs text-gray-500">
              <ClockIcon class="h-4 w-4 mr-1" />
              Reçue le {{ formatDate(invitation.created_at) }}
              <span v-if="invitation.expires_at" class="ml-2">
                • Expire le {{ formatDate(invitation.expires_at) }}
              </span>
            </div>
          </div>

          <div class="flex flex-col space-y-2 ml-4">
            <button
              @click="acceptInvitation(invitation)"
              :disabled="processing"
              class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50"
            >
              <CheckIcon class="h-4 w-4 mr-1" />
              Accepter
            </button>

            <button
              @click="declineInvitation(invitation)"
              :disabled="processing"
              class="inline-flex items-center px-4 py-2 border border-gray-600 text-sm font-medium rounded-md text-gray-300 bg-agfa-bg-secondary hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-agfa-blue disabled:opacity-50"
            >
              <XMarkIcon class="h-4 w-4 mr-1" />
              Refuser
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import {
  EnvelopeIcon,
  UserIcon,
  ClockIcon,
  CheckIcon,
  XMarkIcon,
  ArrowPathIcon
} from '@heroicons/vue/24/outline';
import { invitationService, type ProjectInvitation } from '../api/invitations';
import { useRouter } from 'vue-router';
import { AxiosError } from 'axios';
import { notificationService } from '@/services/notifications';

const router = useRouter();
const invitations = ref<ProjectInvitation[]>([]);
const loading = ref(false);
const processing = ref(false);

const emit = defineEmits<{
  invitationAccepted: [invitation: ProjectInvitation];
  invitationDeclined: [invitation: ProjectInvitation];
}>();

onMounted(() => {
  loadInvitations();
});

async function loadInvitations() {
  try {
    loading.value = true;
    invitations.value = await invitationService.getPendingInvitations();
  } catch (error) {
    console.error('Erreur lors du chargement des invitations:', error);
  } finally {
    loading.value = false;
  }
}

async function refreshInvitations() {
  await loadInvitations();
}

async function acceptInvitation(invitation: ProjectInvitation) {
  try {
    processing.value = true;
    await invitationService.acceptInvitation(invitation.id);

    // Retirer l'invitation de la liste
    const index = invitations.value.findIndex(inv => inv.id === invitation.id);
    if (index > -1) {
      invitations.value.splice(index, 1);
    }

    emit('invitationAccepted', invitation);

    // Rediriger vers le projet accepté
    router.push(`/projects/${invitation.project.id}`);
  } catch (error) {
    console.error('Erreur lors de l\'acceptation de l\'invitation:', error);

    // Gestion spécifique pour l'erreur de contrainte d'unicité
    let errorMessage = '';
    if (error instanceof AxiosError) {
      errorMessage = error.response?.data?.message || error.message || '';
    } else if (error instanceof Error) {
      errorMessage = error.message;
    }

    if (errorMessage.includes('UNIQUE constraint failed')) {
      notificationService.warning('Déjà collaborateur', 'Il semble que vous soyez déjà collaborateur sur ce projet. Veuillez actualiser la page.');
      // Actualiser les invitations pour synchroniser l'état
      await refreshInvitations();
    } else {
      // Autres erreurs
      notificationService.error('Erreur', 'Erreur lors de l\'acceptation de l\'invitation. Veuillez réessayer.');
    }
  } finally {
    processing.value = false;
  }
}

async function declineInvitation(invitation: ProjectInvitation) {
  try {
    processing.value = true;
    await invitationService.declineInvitation(invitation.id);

    // Retirer l'invitation de la liste
    const index = invitations.value.findIndex(inv => inv.id === invitation.id);
    if (index > -1) {
      invitations.value.splice(index, 1);
    }

    emit('invitationDeclined', invitation);
  } catch (error) {
    console.error('Erreur lors du refus de l\'invitation:', error);
  } finally {
    processing.value = false;
  }
}

function getPermissionLabel(permission: string): string {
  const labels = {
    'edit': 'Collaborateur',
    'admin': 'Administrateur',
    // Rétrocompatibilité
    'view': 'Lecture (obsolète)',
    'write': 'Collaborateur'
  };
  return labels[permission as keyof typeof labels] || permission;
}

function getPermissionStyle(permission: string): string {
  const styles = {
    'edit': 'bg-blue-900 bg-opacity-30 text-blue-300',
    'admin': 'bg-purple-900 bg-opacity-30 text-purple-300',
    // Rétrocompatibilité
    'view': 'bg-gray-900 bg-opacity-30 text-gray-400',
    'write': 'bg-blue-900 bg-opacity-30 text-blue-300'
  };
  return styles[permission as keyof typeof styles] || 'bg-gray-900 bg-opacity-30 text-gray-400';
}

function formatDate(dateStr: string): string {
  return new Date(dateStr).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}
</script>
