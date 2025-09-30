<template>
  <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
          Participants au projet
        </h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
          {{ getTotalParticipants() }} personne{{ getTotalParticipants() > 1 ? 's' : '' }} participe{{ getTotalParticipants() > 1 ? 'nt' : '' }} à ce projet
        </p>
      </div>
      <button
        v-if="canManageCollaborators"
        @click="showInviteModal = true"
        class="inline-flex items-center px-4 py-2 border border-transparent text-sm leading-4 font-medium rounded-lg shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200"
      >
        <PlusIcon class="w-4 h-4 mr-2" />
        Inviter
      </button>
    </div>

    <div v-if="loading" class="text-center py-4">
      <div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-indigo-500"></div>
    </div>

    <div v-else>
      <!-- Section Propriétaire -->
      <div v-if="collaboratorData?.owner" class="mb-6">
        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3 flex items-center">
          <UserCircleIcon class="w-4 h-4 mr-2" />
          Propriétaire du projet
        </h4>
        <div class="flex items-center justify-between p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl border border-purple-100 dark:border-purple-800">
          <div class="flex items-center">
            <div class="h-10 w-10 rounded-full bg-purple-500 flex items-center justify-center shadow-sm">
              <span class="text-white font-medium text-sm">
                {{ collaboratorData.owner.name.charAt(0).toUpperCase() }}
              </span>
            </div>
            <div class="ml-3">
              <div class="text-sm font-medium text-gray-900 dark:text-white">
                {{ collaboratorData.owner.name }}
                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                  <CheckCircleIcon class="w-3 h-3 mr-1" />
                  Propriétaire
                </span>
              </div>
              <div class="text-sm text-gray-500 dark:text-gray-400">
                {{ collaboratorData.owner.email }}
              </div>
              <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                Accès complet • Peut inviter des collaborateurs
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Section Collaborateurs -->
      <div v-if="collaboratorData?.collaborators && collaboratorData.collaborators.length > 0">
        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3 flex items-center">
          <UsersIcon class="w-4 h-4 mr-2" />
          Collaborateurs ({{ collaboratorData.collaborators.length }})
        </h4>
        <div class="space-y-3">
          <div
            v-for="collaborator in collaboratorData.collaborators"
            :key="collaborator.id"
            class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-xl border border-gray-100 dark:border-gray-600 hover:border-gray-200 dark:hover:border-gray-500 transition-colors"
          >
            <div class="flex items-center flex-1">
              <div class="h-10 w-10 rounded-full bg-indigo-500 flex items-center justify-center shadow-sm">
                <span class="text-white font-medium text-sm">
                  {{ collaborator.name.charAt(0).toUpperCase() }}
                </span>
              </div>
              <div class="ml-3 flex-1">
                <div class="flex items-center">
                  <div class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ collaborator.name }}
                  </div>
                  <span
                    :class="getPermissionBadgeClass(collaborator.permission)"
                    class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  >
                    {{ getPermissionLabel(collaborator.permission) }}
                  </span>
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                  {{ collaborator.email }}
                </div>
                <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                  {{ getPermissionDescription(collaborator.permission) }}
                </div>
              </div>
            </div>

            <div v-if="canManageCollaborators" class="flex items-center space-x-2">
              <button
                @click="editCollaborator(collaborator)"
                class="inline-flex items-center px-3 py-1 text-xs font-medium text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 bg-indigo-50 dark:bg-indigo-900/20 hover:bg-indigo-100 dark:hover:bg-indigo-900/40 rounded-lg transition-colors"
              >
                <PencilIcon class="w-3 h-3 mr-1" />
                Modifier
              </button>
              <button
                @click="removeCollaborator(collaborator)"
                class="inline-flex items-center px-3 py-1 text-xs font-medium text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/40 rounded-lg transition-colors"
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
        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3 flex items-center">
          <EnvelopeIcon class="w-4 h-4 mr-2" />
          Invitations en attente ({{ pendingInvitations.length }})
        </h4>
        <div class="space-y-3">
          <div
            v-for="invitation in pendingInvitations"
            :key="invitation.id"
            class="flex items-center justify-between p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-xl border border-yellow-200 dark:border-yellow-800"
          >
            <div class="flex items-center flex-1">
              <div class="h-10 w-10 rounded-full bg-yellow-500 flex items-center justify-center shadow-sm">
                <ClockIcon class="w-5 h-5 text-white" />
              </div>
              <div class="ml-3 flex-1">
                <div class="flex items-center">
                  <div class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ invitation.invited_user.name }}
                  </div>
                  <span
                    :class="getPermissionBadgeClass(invitation.permission)"
                    class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  >
                    {{ getPermissionLabel(invitation.permission) }}
                  </span>
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                  {{ invitation.invited_user.email }}
                </div>
                <div class="text-xs text-yellow-600 dark:text-yellow-400 mt-1 flex items-center">
                  <ClockIcon class="w-3 h-3 mr-1" />
                  En attente de réponse • Invité le {{ formatDate(invitation.created_at) }}
                </div>
              </div>
            </div>

            <div class="flex items-center space-x-2">
              <button
                @click="cancelInvitation(invitation)"
                class="inline-flex items-center px-3 py-1 text-xs font-medium text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/40 rounded-lg transition-colors"
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
        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3 flex items-center">
          <UsersIcon class="w-4 h-4 mr-2" />
          Collaborateurs
        </h4>
        <div class="text-center py-8 text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-700/50 rounded-xl border-2 border-dashed border-gray-200 dark:border-gray-600">
          <UsersIcon class="w-12 h-12 mx-auto mb-4 text-gray-400" />
          <p class="text-sm font-medium">Aucun collaborateur pour le moment</p>
          <p class="text-xs mt-1">Invitez des personnes pour travailler ensemble sur ce projet</p>
          <button
            v-if="canManageCollaborators"
            @click="showInviteModal = true"
            class="mt-4 inline-flex items-center px-4 py-2 text-sm font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300"
          >
            <PlusIcon class="w-4 h-4 mr-2" />
            Inviter votre première personne
          </button>
        </div>
      </div>
    </div>

    <!-- Modal d'invitation -->
    <div v-if="showInviteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white dark:bg-gray-800 p-6 rounded-xl max-w-lg w-full mx-4 shadow-2xl">
        <h4 class="text-xl font-medium text-gray-900 dark:text-white mb-6">
          Inviter un collaborateur
        </h4>

        <!-- Recherche d'utilisateurs -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Rechercher un utilisateur
          </label>
          <input
            v-model="searchQuery"
            @input="searchUsers"
            type="text"
            placeholder="Nom ou email..."
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
          />

          <!-- Résultats de recherche -->
          <div v-if="searchResults.length > 0" class="mt-3 max-h-48 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-lg">
            <div
              v-for="user in searchResults"
              :key="user.id"
              @click="selectUser(user)"
              class="p-3 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer border-b border-gray-100 dark:border-gray-600 last:border-b-0"
            >
              <div class="flex items-center">
                <div class="h-8 w-8 rounded-full bg-indigo-500 flex items-center justify-center">
                  <span class="text-white font-medium text-sm">
                    {{ user.name.charAt(0).toUpperCase() }}
                  </span>
                </div>
                <div class="ml-3">
                  <div class="text-sm font-medium text-gray-900 dark:text-white">{{ user.name }}</div>
                  <div class="text-xs text-gray-500 dark:text-gray-400">{{ user.email }}</div>
                </div>
              </div>
            </div>
          </div>

          <div v-else-if="searchQuery && searchLoading" class="mt-3 text-center py-4 text-gray-500">
            <div class="inline-block animate-spin rounded-full h-5 w-5 border-b-2 border-indigo-500"></div>
          </div>

          <div v-else-if="searchQuery && !searchLoading" class="mt-3 text-center py-4 text-gray-500 dark:text-gray-400">
            Aucun utilisateur trouvé
          </div>
        </div>

        <!-- Utilisateur sélectionné -->
        <div v-if="selectedUser" class="mb-6 p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <div class="h-10 w-10 rounded-full bg-indigo-500 flex items-center justify-center">
                <span class="text-white font-medium">
                  {{ selectedUser.name.charAt(0).toUpperCase() }}
                </span>
              </div>
              <div class="ml-3">
                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ selectedUser.name }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ selectedUser.email }}</div>
              </div>
            </div>
            <button
              @click="selectedUser = null"
              class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
            >
              ✕
            </button>
          </div>
        </div>

        <!-- Sélection des permissions -->
        <div v-if="selectedUser" class="mb-6">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
            Permissions
          </label>
          <div class="space-y-2">
            <label class="flex items-center">
              <input
                v-model="selectedPermission"
                type="radio"
                value="edit"
                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
              />
              <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">
                <strong>Collaborateur</strong> - Peut voir et modifier tout le contenu du projet
              </span>
            </label>
            <label class="flex items-center">
              <input
                v-model="selectedPermission"
                type="radio"
                value="admin"
                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
              />
              <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">
                <strong>Administrateur</strong> - Peut tout faire + inviter/gérer d'autres collaborateurs
              </span>
            </label>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end space-x-3">
          <button
            @click="closeInviteModal"
            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-600 hover:bg-gray-200 dark:hover:bg-gray-500 rounded-lg transition-colors"
          >
            Annuler
          </button>
          <button
            @click="inviteCollaborator"
            :disabled="!selectedUser || !selectedPermission || inviting"
            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-400 rounded-lg transition-colors disabled:cursor-not-allowed"
          >
            {{ inviting ? 'Invitation...' : 'Inviter' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modal de modification des permissions -->
    <div v-if="showEditModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white dark:bg-gray-800 p-6 rounded-xl max-w-md w-full mx-4 shadow-2xl">
        <h4 class="text-xl font-medium text-gray-900 dark:text-white mb-6">
          Modifier les permissions
        </h4>

        <div v-if="editingCollaborator" class="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
          <div class="flex items-center">
            <div class="h-10 w-10 rounded-full bg-indigo-500 flex items-center justify-center">
              <span class="text-white font-medium">
                {{ editingCollaborator.name.charAt(0).toUpperCase() }}
              </span>
            </div>
            <div class="ml-3">
              <div class="text-sm font-medium text-gray-900 dark:text-white">{{ editingCollaborator.name }}</div>
              <div class="text-sm text-gray-500 dark:text-gray-400">{{ editingCollaborator.email }}</div>
            </div>
          </div>
        </div>

        <!-- Sélection des nouvelles permissions -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
            Nouvelles permissions
          </label>
          <div class="space-y-2">
            <label class="flex items-center">
              <input
                v-model="editingPermission"
                type="radio"
                value="edit"
                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
              />
              <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">
                <strong>Collaborateur</strong> - Peut voir et modifier tout le contenu du projet
              </span>
            </label>
            <label class="flex items-center">
              <input
                v-model="editingPermission"
                type="radio"
                value="admin"
                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
              />
              <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">
                <strong>Administrateur</strong> - Peut tout faire + inviter/gérer d'autres collaborateurs
              </span>
            </label>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end space-x-3">
          <button
            @click="closeEditModal"
            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-600 hover:bg-gray-200 dark:hover:bg-gray-500 rounded-lg transition-colors"
          >
            Annuler
          </button>
          <button
            @click="updatePermissions"
            :disabled="!editingPermission || updating"
            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-400 rounded-lg transition-colors disabled:cursor-not-allowed"
          >
            {{ updating ? 'Mise à jour...' : 'Mettre à jour' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import type { User } from '../../api/auth'
import collaborationService, { type CollaboratorResponse, type Collaborator, type ProjectPendingInvitation } from '../../api/collaboration'
import { useAuthStore } from '../../stores/auth'
import { notificationService } from '../../services/notifications'
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
    case 'admin': return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
    case 'edit': return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
    case 'view': return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'
    // Rétrocompatibilité avec l'ancien système
    case 'write': return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
    case 'read': return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'
    default: return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'
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
