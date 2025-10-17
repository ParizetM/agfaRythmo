<template>
  <div class="space-y-6">
    <!-- Header avec statistiques et bouton -->
    <div class="flex items-center justify-between">
      <div>
        <h3 class="text-lg font-semibold text-white">
          Participants au projet
        </h3>
        <p class="text-sm text-gray-400 mt-1">
          {{ getTotalParticipants() }} personne{{ getTotalParticipants() > 1 ? 's' : '' }} participe{{ getTotalParticipants() > 1 ? 'nt' : '' }} à ce projet
        </p>
      </div>
      <button
        v-if="canManageCollaborators"
        @click="showInviteModal = true"
        class="inline-flex items-center px-4 py-2.5 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-semibold rounded-xl transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98] shadow-lg shadow-blue-500/25"
      >
        <PlusIcon class="w-4 h-4 mr-2" />
        Inviter
      </button>
    </div>

    <div v-if="loading" class="text-center py-8">
      <div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-blue-500"></div>
      <p class="text-gray-400 text-sm mt-3">Chargement des collaborateurs...</p>
    </div>

    <div v-else>
      <!-- Section Propriétaire -->
      <div v-if="collaboratorData?.owner" class="mb-6">
        <h4 class="text-sm font-semibold text-gray-300 mb-3 flex items-center">
          <UserCircleIcon class="w-4 h-4 mr-2" />
          Propriétaire du projet
        </h4>
        <div class="flex items-center justify-between p-4 bg-gradient-to-r from-purple-500/10 to-purple-600/10 rounded-xl border border-purple-500/30">
          <div class="flex items-center">
            <div class="h-10 w-10 rounded-full bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center shadow-lg">
              <span class="text-white font-medium text-sm">
                {{ collaboratorData.owner.name.charAt(0).toUpperCase() }}
              </span>
            </div>
            <div class="ml-3">
              <div class="text-sm font-medium text-white">
                {{ collaboratorData.owner.name }}
                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-500/20 text-purple-300 border border-purple-500/30">
                  <CheckCircleIcon class="w-3 h-3 mr-1" />
                  Propriétaire
                </span>
              </div>
              <div class="text-sm text-gray-400">
                {{ collaboratorData.owner.email }}
              </div>
              <div class="text-xs text-gray-500 mt-1">
                Accès complet • Peut inviter des collaborateurs
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Section Collaborateurs -->
      <div v-if="collaboratorData?.collaborators && collaboratorData.collaborators.length > 0">
        <h4 class="text-sm font-semibold text-gray-300 mb-3 flex items-center">
          <UsersIcon class="w-4 h-4 mr-2" />
          Collaborateurs ({{ collaboratorData.collaborators.length }})
        </h4>
        <div class="space-y-3">
          <div
            v-for="collaborator in collaboratorData.collaborators"
            :key="collaborator.id"
            class="flex items-center justify-between p-4 bg-agfa-bg-primary rounded-xl border border-gray-700 hover:border-gray-600 transition-all"
          >
            <div class="flex items-center flex-1">
              <div class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center shadow-lg">
                <span class="text-white font-medium text-sm">
                  {{ collaborator.name.charAt(0).toUpperCase() }}
                </span>
              </div>
              <div class="ml-3 flex-1">
                <div class="flex items-center">
                  <div class="text-sm font-medium text-white">
                    {{ collaborator.name }}
                  </div>
                  <span
                    :class="getPermissionBadgeClass(collaborator.permission)"
                    class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  >
                    {{ getPermissionLabel(collaborator.permission) }}
                  </span>
                </div>
                <div class="text-sm text-gray-400">
                  {{ collaborator.email }}
                </div>
                <div class="text-xs text-gray-500 mt-1">
                  {{ getPermissionDescription(collaborator.permission) }}
                </div>
              </div>
            </div>

            <div v-if="canManageCollaborators" class="flex items-center space-x-2">
              <button
                @click="editCollaborator(collaborator)"
                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-400 hover:text-blue-300 bg-blue-500/10 hover:bg-blue-500/20 border border-blue-500/30 rounded-lg transition-all"
              >
                <PencilIcon class="w-3 h-3 mr-1" />
                Modifier
              </button>
              <button
                @click="removeCollaborator(collaborator)"
                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-400 hover:text-red-300 bg-red-500/10 hover:bg-red-500/20 border border-red-500/30 rounded-lg transition-all"
              >
                <TrashIcon class="w-3 h-3 mr-1" />
                Retirer
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Section Invitations en attente -->
      <div v-if="canManageCollaborators && pendingInvitations.length > 0" class="mb-6">
        <h4 class="text-sm font-semibold text-gray-300 mb-3 flex items-center">
          <EnvelopeIcon class="w-4 h-4 mr-2" />
          Invitations en attente ({{ pendingInvitations.length }})
        </h4>
        <div class="space-y-3">
          <div
            v-for="invitation in pendingInvitations"
            :key="invitation.id"
            class="flex items-center justify-between p-4 bg-gradient-to-r from-yellow-500/10 to-yellow-600/10 rounded-xl border border-yellow-500/30"
          >
            <div class="flex items-center flex-1">
              <div class="h-10 w-10 rounded-full bg-gradient-to-br from-yellow-500 to-yellow-600 flex items-center justify-center shadow-lg">
                <ClockIcon class="w-5 h-5 text-white" />
              </div>
              <div class="ml-3 flex-1">
                <div class="flex items-center">
                  <div class="text-sm font-medium text-white">
                    {{ invitation.invited_user.name }}
                  </div>
                  <span
                    :class="getPermissionBadgeClass(invitation.permission)"
                    class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  >
                    {{ getPermissionLabel(invitation.permission) }}
                  </span>
                </div>
                <div class="text-sm text-gray-400">
                  {{ invitation.invited_user.email }}
                </div>
                <div class="text-xs text-yellow-400 mt-1 flex items-center">
                  <ClockIcon class="w-3 h-3 mr-1" />
                  En attente de réponse • Invité le {{ formatDate(invitation.created_at) }}
                </div>
              </div>
            </div>

            <div class="flex items-center space-x-2">
              <button
                @click="cancelInvitation(invitation)"
                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-400 hover:text-red-300 bg-red-500/10 hover:bg-red-500/20 border border-red-500/30 rounded-lg transition-all"
              >
                <XMarkIcon class="w-3 h-3 mr-1" />
                Annuler
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Aucun collaborateur -->
      <div v-else-if="!loading && (!collaboratorData?.collaborators || collaboratorData.collaborators.length === 0)">
        <h4 class="text-sm font-semibold text-gray-300 mb-3 flex items-center">
          <UsersIcon class="w-4 h-4 mr-2" />
          Collaborateurs
        </h4>
        <div class="text-center py-8 bg-agfa-bg-primary rounded-xl border-2 border-dashed border-gray-600">
          <UsersIcon class="w-12 h-12 mx-auto mb-4 text-gray-500" />
          <p class="text-sm font-medium text-gray-300">Aucun collaborateur pour le moment</p>
          <p class="text-xs text-gray-500 mt-1">Invitez des personnes pour travailler ensemble sur ce projet</p>
          <button
            v-if="canManageCollaborators"
            @click="showInviteModal = true"
            class="mt-4 inline-flex items-center px-4 py-2 text-sm font-medium text-blue-400 hover:text-blue-300 bg-blue-500/10 hover:bg-blue-500/20 border border-blue-500/30 rounded-lg transition-all"
          >
            <PlusIcon class="w-4 h-4 mr-2" />
            Inviter votre première personne
          </button>
        </div>
      </div>
    </div>

    <!-- Modal d'invitation -->
    <BaseModal
      :show="showInviteModal"
      title="Inviter un collaborateur"
      subtitle="Recherchez et invitez des personnes à collaborer sur ce projet"
      size="lg"
      @close="closeInviteModal"
    >
      <template v-slot:icon>
        <PlusIcon class="w-6 h-6 sm:w-8 sm:h-8 text-white" />
      </template>

      <template v-slot:default>
        <div class="space-y-6">
          <!-- Recherche d'utilisateurs -->
          <div>
            <label class="block text-sm font-semibold text-gray-300 mb-2">
              Rechercher un utilisateur
            </label>
            <input
              v-model="searchQuery"
              @input="searchUsers"
              type="text"
              placeholder="Nom ou email..."
              class="w-full px-4 py-3 bg-agfa-bg-primary border border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all duration-300 text-white placeholder-gray-500 hover:border-gray-500"
            />

            <!-- Résultats de recherche -->
            <div v-if="searchResults.length > 0" class="mt-3 max-h-48 overflow-y-auto border border-gray-600 rounded-xl bg-agfa-bg-primary">
              <div
                v-for="user in searchResults"
                :key="user.id"
                @click="selectUser(user)"
                class="p-3 hover:bg-gray-700 cursor-pointer border-b border-gray-700 last:border-b-0 transition-colors"
              >
                <div class="flex items-center">
                  <div class="h-8 w-8 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center shadow-lg">
                    <span class="text-white font-medium text-sm">
                      {{ user.name.charAt(0).toUpperCase() }}
                    </span>
                  </div>
                  <div class="ml-3">
                    <div class="text-sm font-medium text-white">{{ user.name }}</div>
                    <div class="text-xs text-gray-400">{{ user.email }}</div>
                  </div>
                </div>
              </div>
            </div>

            <div v-else-if="searchQuery && searchLoading" class="mt-3 text-center py-4 text-gray-400">
              <div class="inline-block animate-spin rounded-full h-5 w-5 border-b-2 border-blue-500"></div>
            </div>

            <div v-else-if="searchQuery && !searchLoading" class="mt-3 text-center py-4 text-gray-400">
              Aucun utilisateur trouvé
            </div>
          </div>

          <!-- Utilisateur sélectionné -->
          <div v-if="selectedUser" class="p-4 bg-gradient-to-r from-blue-500/10 to-purple-600/10 rounded-xl border border-blue-500/30">
            <div class="flex items-center justify-between">
              <div class="flex items-center">
                <div class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center shadow-lg">
                  <span class="text-white font-medium">
                    {{ selectedUser.name.charAt(0).toUpperCase() }}
                  </span>
                </div>
                <div class="ml-3">
                  <div class="text-sm font-medium text-white">{{ selectedUser.name }}</div>
                  <div class="text-sm text-gray-400">{{ selectedUser.email }}</div>
                </div>
              </div>
              <button
                @click="selectedUser = null"
                class="w-8 h-8 rounded-full hover:bg-gray-700 flex items-center justify-center text-gray-400 hover:text-white transition-all"
            >
              ✕
            </button>
          </div>
        </div>

        <!-- Sélection des permissions -->
        <div v-if="selectedUser">
          <label class="block text-sm font-medium text-gray-300 mb-3">
            Permissions
          </label>
          <div class="space-y-2">
            <label class="flex items-center p-3 bg-agfa-bg-primary rounded-xl border border-gray-600 hover:border-gray-500 cursor-pointer transition-all">
              <input
                v-model="selectedPermission"
                type="radio"
                value="edit"
                class="h-4 w-4 text-blue-500 focus:ring-blue-500 border-gray-500"
              />
              <span class="ml-3 text-sm text-gray-300">
                <strong class="text-white">Collaborateur</strong> - Peut voir et modifier tout le contenu du projet
              </span>
            </label>
            <label class="flex items-center p-3 bg-agfa-bg-primary rounded-xl border border-gray-600 hover:border-gray-500 cursor-pointer transition-all">
              <input
                v-model="selectedPermission"
                type="radio"
                value="admin"
                class="h-4 w-4 text-blue-500 focus:ring-blue-500 border-gray-500"
              />
              <span class="ml-3 text-sm text-gray-300">
                <strong class="text-white">Administrateur</strong> - Peut tout faire + inviter/gérer d'autres collaborateurs
              </span>
            </label>
          </div>
        </div>
        </div>
      </template>

      <template v-slot:footer>
        <button
          @click="closeInviteModal"
          class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-xl transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98]"
        >
          Annuler
        </button>
        <button
          @click="inviteCollaborator"
          :disabled="!selectedUser || !selectedPermission || inviting"
          class="flex-1 px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 disabled:from-gray-600 disabled:to-gray-700 disabled:cursor-not-allowed text-white font-semibold rounded-xl transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98] shadow-lg shadow-blue-500/25 disabled:shadow-none"
        >
          {{ inviting ? 'Invitation...' : 'Inviter' }}
        </button>
      </template>
    </BaseModal>

    <!-- Modal de modification des permissions -->
    <BaseModal
      :show="showEditModal"
      title="Modifier les permissions"
      subtitle="Ajustez les droits d'accès du collaborateur"
      size="md"
      @close="closeEditModal"
    >
      <template v-slot:icon>
        <PencilIcon class="w-6 h-6 sm:w-8 sm:h-8 text-white" />
      </template>

      <template v-slot:default>
        <div class="space-y-6">
          <!-- Collaborateur concerné -->
          <div v-if="editingCollaborator" class="p-4 bg-agfa-bg-primary rounded-xl border border-gray-700">
            <div class="flex items-center">
              <div class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center shadow-lg">
                <span class="text-white font-medium">
                  {{ editingCollaborator.name.charAt(0).toUpperCase() }}
                </span>
              </div>
              <div class="ml-3">
                <div class="text-sm font-medium text-white">{{ editingCollaborator.name }}</div>
                <div class="text-sm text-gray-400">{{ editingCollaborator.email }}</div>
              </div>
            </div>
          </div>

          <!-- Sélection des nouvelles permissions -->
          <div>
            <label class="block text-sm font-medium text-gray-300 mb-3">
              Nouvelles permissions
            </label>
            <div class="space-y-2">
              <label class="flex items-center p-3 bg-agfa-bg-primary rounded-xl border border-gray-600 hover:border-gray-500 cursor-pointer transition-all">
                <input
                  v-model="editingPermission"
                  type="radio"
                  value="edit"
                  class="h-4 w-4 text-blue-500 focus:ring-blue-500 border-gray-500"
                />
                <span class="ml-3 text-sm text-gray-300">
                  <strong class="text-white">Collaborateur</strong> - Peut voir et modifier tout le contenu du projet
                </span>
              </label>
              <label class="flex items-center p-3 bg-agfa-bg-primary rounded-xl border border-gray-600 hover:border-gray-500 cursor-pointer transition-all">
                <input
                  v-model="editingPermission"
                  type="radio"
                  value="admin"
                  class="h-4 w-4 text-blue-500 focus:ring-blue-500 border-gray-500"
                />
                <span class="ml-3 text-sm text-gray-300">
                  <strong class="text-white">Administrateur</strong> - Peut tout faire + inviter/gérer d'autres collaborateurs
                </span>
              </label>
            </div>
          </div>
        </div>
      </template>

      <template v-slot:footer>
        <button
          @click="closeEditModal"
          class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-xl transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98]"
        >
          Annuler
        </button>
        <button
          @click="updatePermissions"
          :disabled="!editingPermission || updating"
          class="flex-1 px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 disabled:from-gray-600 disabled:to-gray-700 disabled:cursor-not-allowed text-white font-semibold rounded-xl transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98] shadow-lg shadow-blue-500/25 disabled:shadow-none"
        >
          {{ updating ? 'Mise à jour...' : 'Mettre à jour' }}
        </button>
      </template>
    </BaseModal>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import type { User } from '../../api/auth'
import collaborationService, { type CollaboratorResponse, type Collaborator, type ProjectPendingInvitation } from '../../api/collaboration'
import { useAuthStore } from '../../stores/auth'
import { notificationService } from '../../services/notifications'
import BaseModal from '../BaseModal.vue'
import {
  PlusIcon,
  UsersIcon,
  UserCircleIcon,
  EnvelopeIcon,
  ClockIcon,
  PencilIcon,
  TrashIcon,
  XMarkIcon,
  CheckCircleIcon
} from '@heroicons/vue/24/outline'

interface Props {
  projectId: number
  canManage?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  canManage: false
})

const authStore = useAuthStore()
const collaboratorData = ref<CollaboratorResponse | null>(null)
const loading = ref(false)

// Variables pour les invitations en attente
const pendingInvitations = ref<ProjectPendingInvitation[]>([])
const loadingInvitations = ref(false)

// Variables pour l'invitation
const showInviteModal = ref(false)
const searchQuery = ref('')
const searchResults = ref<User[]>([])
const searchLoading = ref(false)
const selectedUser = ref<User | null>(null)
const selectedPermission = ref<'edit' | 'admin'>('edit')
const inviting = ref(false)

// Variables pour l'édition des permissions
const showEditModal = ref(false)
const editingCollaborator = ref<Collaborator | null>(null)
const editingPermission = ref<'edit' | 'admin'>('edit')
const updating = ref(false)

const canManageCollaborators = computed(() => {
  return props.canManage || authStore.isAdmin
})

onMounted(() => {
  loadCollaborators()
  loadPendingInvitations()
})

const loadCollaborators = async () => {
  loading.value = true
  try {
    collaboratorData.value = await collaborationService.getCollaborators(props.projectId)
  } catch (error) {
    console.error('Erreur lors du chargement des collaborateurs:', error)
  } finally {
    loading.value = false
  }
}

const loadPendingInvitations = async () => {
  if (!canManageCollaborators.value) return

  loadingInvitations.value = true
  try {
    pendingInvitations.value = await collaborationService.getPendingInvitations(props.projectId)
  } catch (error) {
    console.error('Erreur lors du chargement des invitations en attente:', error)
  } finally {
    loadingInvitations.value = false
  }
}

const getPermissionLabel = (permission: string) => {
  switch (permission) {
    case 'admin': return 'Administrateur'
    case 'edit': return 'Collaborateur'
    // Rétrocompatibilité avec l'ancien système
    case 'view': return 'Lecture (obsolète)'
    case 'write': return 'Collaborateur'
    case 'read': return 'Lecture (obsolète)'
    default: return permission
  }
}

const getPermissionBadgeClass = (permission: string) => {
  switch (permission) {
    case 'admin': return 'bg-red-500/20 text-red-300 border border-red-500/30'
    case 'edit': return 'bg-green-500/20 text-green-300 border border-green-500/30'
    case 'view': return 'bg-blue-500/20 text-blue-300 border border-blue-500/30'
    // Rétrocompatibilité avec l'ancien système
    case 'write': return 'bg-green-500/20 text-green-300 border border-green-500/30'
    case 'read': return 'bg-blue-500/20 text-blue-300 border border-blue-500/30'
    default: return 'bg-gray-500/20 text-gray-300 border border-gray-500/30'
  }
}

const getTotalParticipants = () => {
  if (!collaboratorData.value) return 0
  // Propriétaire + collaborateurs
  const ownerCount = collaboratorData.value.owner ? 1 : 0
  const collaboratorsCount = collaboratorData.value.collaborators ? collaboratorData.value.collaborators.length : 0
  return ownerCount + collaboratorsCount
}

const formatDate = (dateString: string) => {
  try {
    return new Date(dateString).toLocaleDateString('fr-FR', {
      day: 'numeric',
      month: 'short',
      year: 'numeric'
    })
  } catch {
    return dateString
  }
}

const getPermissionDescription = (permission: string) => {
  switch (permission) {
    case 'admin': return 'Accès complet • Peut gérer les collaborateurs'
    case 'edit': return 'Peut modifier le contenu du projet'
    case 'view': return 'Peut uniquement consulter le projet'
    // Rétrocompatibilité avec l'ancien système
    case 'write': return 'Peut modifier le contenu du projet'
    case 'read': return 'Peut uniquement consulter le projet'
    default: return 'Permissions non définies'
  }
}

// Fonctions pour la recherche d'utilisateurs
let searchTimeout: number | null = null

const searchUsers = async () => {
  if (!searchQuery.value.trim()) {
    searchResults.value = []
    return
  }

  // Debounce la recherche
  if (searchTimeout) {
    clearTimeout(searchTimeout)
  }

  searchTimeout = setTimeout(async () => {
    searchLoading.value = true
    try {
      searchResults.value = await collaborationService.searchUsers(props.projectId, searchQuery.value)
    } catch (error) {
      console.error('Erreur lors de la recherche d\'utilisateurs:', error)
      searchResults.value = []
    } finally {
      searchLoading.value = false
    }
  }, 300) as unknown as number
}

const selectUser = (user: User) => {
  selectedUser.value = user
  searchQuery.value = ''
  searchResults.value = []
}

// Fonctions pour l'invitation
const inviteCollaborator = async () => {
  if (!selectedUser.value || !selectedPermission.value) return

  inviting.value = true
  try {
    await collaborationService.inviteCollaborator(props.projectId, {
      user_email: selectedUser.value.email,
      permission: selectedPermission.value
    })

    // Recharger la liste des collaborateurs et des invitations
    await loadCollaborators()
    await loadPendingInvitations()

    // Afficher un message de succès
    notificationService.success(
      'Invitation envoyée',
      `Une invitation a été envoyée à ${selectedUser.value.name} ! L'utilisateur doit accepter l'invitation pour rejoindre le projet.`
    )

    // Fermer la modal et réinitialiser
    closeInviteModal()
  } catch (error) {
    console.error('Erreur lors de l\'invitation:', error)
    // Afficher l'erreur à l'utilisateur
    const errorMessage = error instanceof Error ? error.message : 'Erreur lors de l\'invitation'
    notificationService.error('Erreur d\'invitation', errorMessage)
  } finally {
    inviting.value = false
  }
}

const closeInviteModal = () => {
  showInviteModal.value = false
  selectedUser.value = null
  selectedPermission.value = 'edit'
  searchQuery.value = ''
  searchResults.value = []
}

// Fonctions pour l'édition des permissions
const editCollaborator = (collaborator: Collaborator) => {
  editingCollaborator.value = collaborator
  editingPermission.value = collaborator.permission
  showEditModal.value = true
}

const updatePermissions = async () => {
  if (!editingCollaborator.value || !editingPermission.value) return

  updating.value = true
  try {
    await collaborationService.updatePermissions(
      props.projectId,
      editingCollaborator.value.id,
      { permission: editingPermission.value }
    )

    // Recharger la liste des collaborateurs
    await loadCollaborators()

    // Afficher un message de succès
    notificationService.success('Permissions mises à jour', `Les permissions ont été mises à jour pour ${editingCollaborator.value.name}`)

    // Fermer la modal
    closeEditModal()
  } catch (error) {
    console.error('Erreur lors de la mise à jour des permissions:', error)
    const errorMessage = error instanceof Error ? error.message : 'Erreur lors de la mise à jour'
    notificationService.error('Erreur de permissions', errorMessage)
  } finally {
    updating.value = false
  }
}

const closeEditModal = () => {
  showEditModal.value = false
  editingCollaborator.value = null
  editingPermission.value = 'edit'
}

const removeCollaborator = async (collaborator: Collaborator) => {
  if (!confirm(`Êtes-vous sûr de vouloir retirer ${collaborator.name} du projet ?`)) {
    return
  }

  try {
    await collaborationService.removeCollaborator(props.projectId, collaborator.id)
    await loadCollaborators()
    notificationService.success('Collaborateur retiré', `${collaborator.name} a été retiré du projet`)
  } catch (error) {
    console.error('Erreur lors de la suppression du collaborateur:', error)
    const errorMessage = error instanceof Error ? error.message : 'Erreur lors de la suppression'
    notificationService.error('Erreur de suppression', errorMessage)
  }
}

const cancelInvitation = async (invitation: ProjectPendingInvitation) => {
  if (!confirm(`Êtes-vous sûr de vouloir annuler l'invitation de ${invitation.invited_user.name} ?`)) {
    return
  }

  try {
    await collaborationService.cancelInvitation(invitation.id)
    await loadPendingInvitations()
    notificationService.success('Invitation annulée', `L'invitation de ${invitation.invited_user.name} a été annulée`)
  } catch (error) {
    console.error('Erreur lors de l\'annulation de l\'invitation:', error)
    const errorMessage = error instanceof Error ? error.message : 'Erreur lors de l\'annulation'
    notificationService.error('Erreur d\'annulation', errorMessage)
  }
}
</script>
