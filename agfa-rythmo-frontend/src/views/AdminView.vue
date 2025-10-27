<template>
  <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
          Administration AgfaRythmo
        </h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">
          Gérez les utilisateurs et supervisez tous les projets
        </p>
      </div>

      <!-- Statistiques -->
      <div v-if="stats" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                    Utilisateurs total
                  </dt>
                  <dd class="text-lg font-medium text-gray-900 dark:text-white">
                    {{ stats.total_users }}
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                    Administrateurs
                  </dt>
                  <dd class="text-lg font-medium text-gray-900 dark:text-white">
                    {{ stats.admin_users }}
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                    Projets total
                  </dt>
                  <dd class="text-lg font-medium text-gray-900 dark:text-white">
                    {{ stats.total_projects }}
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                    Projets collaboratifs
                  </dt>
                  <dd class="text-lg font-medium text-gray-900 dark:text-white">
                    {{ stats.projects_with_collaborators }}
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>
        <!-- Statistiques vidéos -->

        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                    Espace total utilisé
                  </dt>
                  <dd class="text-lg font-medium text-gray-900 dark:text-white">
                    {{ formatFileSize(stats.total_video_size) }}
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

      </div>



      <!-- Onglets -->
      <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex space-x-8">
          <button
            @click="activeTab = 'users'"
            :class="[
              activeTab === 'users'
                ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
              'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm'
            ]"
          >
            Gestion des utilisateurs
          </button>
          <button
            @click="activeTab = 'projects'"
            :class="[
              activeTab === 'projects'
                ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
              'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm'
            ]"
          >
            Tous les projets
          </button>
        </nav>
      </div>

      <!-- Contenu des onglets -->
      <div class="mt-6">
        <!-- Onglet Utilisateurs -->
        <div v-if="activeTab === 'users'">
          <div class="sm:flex sm:items-center sm:justify-between mb-6">
            <div class="sm:flex-auto">
              <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Utilisateurs</h2>
            </div>
            <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
              <button
                @click="openCreateUserModal"
                class="inline-flex items-center justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
              >
                Créer un utilisateur
              </button>
            </div>
          </div>

          <!-- Recherche et filtres -->
          <div class="mb-4 flex gap-4">
            <div class="flex-1">
              <input
                v-model="userSearch"
                type="text"
                placeholder="Rechercher par nom ou email..."
                class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-800 dark:text-white sm:text-sm"
              />
            </div>
            <select
              v-model="userRoleFilter"
              class="rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-800 dark:text-white sm:text-sm"
            >
              <option value="">Tous les rôles</option>
              <option value="admin">Administrateurs</option>
              <option value="user">Utilisateurs</option>
            </select>
          </div>

          <!-- Liste des utilisateurs -->
          <div v-if="loading" class="text-center py-8">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-500"></div>
          </div>

          <div v-else-if="users.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
            Aucun utilisateur trouvé
          </div>

          <div v-else class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-md">
            <ul class="divide-y divide-gray-200 dark:divide-gray-700">
              <li v-for="user in users" :key="user.id" class="px-6 py-4">
                <div class="flex items-center justify-between">
                  <div class="flex items-center">
                    <div class="flex-shrink-0">
                      <div class="h-10 w-10 rounded-full bg-indigo-500 flex items-center justify-center">
                        <span class="text-white font-medium text-sm">
                          {{ user.name.charAt(0).toUpperCase() }}
                        </span>
                      </div>
                    </div>
                    <div class="ml-4">
                      <div class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ user.name }}
                        <span
                          :class="[
                            user.role === 'admin'
                              ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200'
                              : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
                            'ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium'
                          ]"
                        >
                          {{ user.role === 'admin' ? 'Admin' : 'Utilisateur' }}
                        </span>
                      </div>
                      <div class="text-sm text-gray-500 dark:text-gray-400">
                        {{ user.email }}
                      </div>
                    </div>
                  </div>
                  <div class="flex items-center space-x-2">
                    <button
                      @click="editUser(user)"
                      class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 text-sm"
                    >
                      Modifier
                    </button>
                    <button
                      @click="deleteUser(user)"
                      class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 text-sm"
                      :disabled="user.role === 'admin' && stats?.admin_users === 1"
                    >
                      Supprimer
                    </button>
                  </div>
                </div>
              </li>
            </ul>
          </div>
        </div>

        <!-- Onglet Projets -->
        <div v-if="activeTab === 'projects'">
          <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">Tous les projets</h2>

          <!-- Recherche projets -->
          <div class="mb-4">
            <input
              v-model="projectSearch"
              type="text"
              placeholder="Rechercher un projet..."
              class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-800 dark:text-white sm:text-sm"
            />
          </div>

          <!-- Liste des projets -->
          <div v-if="projectLoading" class="text-center py-8">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-500"></div>
          </div>

          <div v-else-if="projects.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
            Aucun projet trouvé
          </div>

          <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <div
              v-for="project in projects"
              :key="project.id"
              class="bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow duration-200"
            >
              <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                  {{ project.name }}
                </h3>
                <p v-if="project.owner" class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                  Propriétaire : {{ project.owner.name }}
                </p>
                <p v-if="project.collaborators?.length" class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                  {{ project.collaborators.length }} collaborateur(s)
                </p>
                <p v-if="project.video_path && project.video_size !== undefined && project.video_size > 0" class="text-sm text-indigo-500 dark:text-indigo-400 mb-4">
                  📹 {{ formatFileSize(project.video_size) }}
                </p>
                <p v-else-if="project.video_path" class="text-sm text-gray-400 dark:text-gray-500 mb-4">
                  📹 Vidéo présente (taille inconnue)
                </p>
                <div class="flex justify-between items-center">
                  <span class="text-xs text-gray-500 dark:text-gray-400">
                    Créé le {{ new Date(project.created_at).toLocaleDateString('fr-FR') }}
                  </span>
                  <div class="flex items-center space-x-2">
                    <router-link
                      :to="`/projects/${project.id}`"
                      class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 text-sm"
                    >
                      Voir
                    </router-link>
                    <button
                      @click="deleteProject(project)"
                      class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 text-sm"
                      title="Supprimer le projet"
                    >
                      Supprimer
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal de création/édition d'utilisateur -->
  <div v-if="showUserModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
      <div class="mt-3">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white">
            {{ editingUser ? 'Modifier l\'utilisateur' : 'Créer un utilisateur' }}
          </h3>
          <button
            @click="closeUserModal"
            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>

        <form @submit.prevent="saveUser">
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Nom complet
            </label>
            <input
              v-model="userForm.name"
              type="text"
              required
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white"
            />
          </div>

          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Email
            </label>
            <input
              v-model="userForm.email"
              type="email"
              required
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white"
            />
          </div>

          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Rôle
            </label>
            <select
              v-model="userForm.role"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white"
            >
              <option value="user">Utilisateur</option>
              <option value="admin">Administrateur</option>
            </select>
          </div>

          <div v-if="!editingUser" class="mb-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Mot de passe
            </label>
            <input
              v-model="userForm.password"
              type="password"
              required
              min="8"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white"
            />
          </div>

          <div v-if="editingUser" class="mb-4">
            <label class="flex items-center">
              <input
                v-model="showPasswordChange"
                type="checkbox"
                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
              />
              <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                Changer le mot de passe
              </span>
            </label>
          </div>

          <div v-if="editingUser && showPasswordChange" class="mb-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Nouveau mot de passe
            </label>
            <input
              v-model="userForm.password"
              type="password"
              min="8"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white"
            />
          </div>

          <div class="flex justify-end space-x-3 mt-6">
            <button
              type="button"
              @click="closeUserModal"
              class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              Annuler
            </button>
            <button
              type="submit"
              :disabled="userSaving"
              class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
            >
              {{ userSaving ? 'Enregistrement...' : (editingUser ? 'Modifier' : 'Créer') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import adminService, { type AdminStats, type UserWithStats, type Project } from '@/api/admin'
import { notificationService } from '@/services/notifications'

const router = useRouter()
const authStore = useAuthStore()

// Vérifier les droits d'accès
if (!authStore.isAdmin) {
  router.push('/')
}

const activeTab = ref('users')
const stats = ref<AdminStats | null>(null)

// Gestion des utilisateurs
const users = ref<UserWithStats[]>([])
const loading = ref(false)
const userSearch = ref('')
const userRoleFilter = ref('')

// Modal d'édition/création d'utilisateur
const showUserModal = ref(false)
const editingUser = ref<UserWithStats | null>(null)
const userSaving = ref(false)
const showPasswordChange = ref(false)
const userForm = ref({
  name: '',
  email: '',
  role: 'user' as 'admin' | 'user',
  password: ''
})

// Gestion des projets
const projects = ref<Project[]>([])
const projectLoading = ref(false)
const projectSearch = ref('')

// Fonction pour formater la taille des fichiers
const formatFileSize = (bytes: number): string => {
  if (bytes === 0) return '0 octets'

  const k = 1024
  const sizes = ['octets', 'Ko', 'Mo', 'Go', 'To']
  const i = Math.floor(Math.log(bytes) / Math.log(k))

  return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i]
}

onMounted(async () => {
  await loadStats()
  await loadUsers()
})

const loadStats = async () => {
  try {
    stats.value = await adminService.getStats()
  } catch (error) {
    console.error('Erreur lors du chargement des statistiques:', error)
  }
}

const loadUsers = async () => {
  loading.value = true
  try {
    const response = await adminService.getUsers({
      search: userSearch.value || undefined,
      role: userRoleFilter.value || undefined,
    })
    users.value = response.data
  } catch (error) {
    console.error('Erreur lors du chargement des utilisateurs:', error)
  } finally {
    loading.value = false
  }
}

const loadProjects = async () => {
  projectLoading.value = true
  try {
    const response = await adminService.getAllProjects({
      search: projectSearch.value || undefined,
    })
    projects.value = response.data
  } catch (error) {
    console.error('Erreur lors du chargement des projets:', error)
  } finally {
    projectLoading.value = false
  }
}

// Watchers pour la recherche
watch([userSearch, userRoleFilter], () => {
  loadUsers()
})

watch(projectSearch, () => {
  loadProjects()
})

watch(activeTab, (newTab) => {
  if (newTab === 'projects' && projects.value.length === 0) {
    loadProjects()
  }
})

// Fonctions pour la gestion des utilisateurs
const openCreateUserModal = () => {
  editingUser.value = null
  showPasswordChange.value = false
  userForm.value = {
    name: '',
    email: '',
    role: 'user',
    password: ''
  }
  showUserModal.value = true
}

const editUser = (user: UserWithStats) => {
  editingUser.value = user
  showPasswordChange.value = false
  userForm.value = {
    name: user.name,
    email: user.email,
    role: user.role,
    password: ''
  }
  showUserModal.value = true
}

const closeUserModal = () => {
  showUserModal.value = false
  editingUser.value = null
  showPasswordChange.value = false
  userForm.value = {
    name: '',
    email: '',
    role: 'user',
    password: ''
  }
}

const saveUser = async () => {
  userSaving.value = true

  try {
    if (editingUser.value) {
      // Modification d'utilisateur existant
      await adminService.updateUser(editingUser.value.id, {
        name: userForm.value.name,
        email: userForm.value.email,
        role: userForm.value.role
      })

      // Changer le mot de passe si demandé
      if (showPasswordChange.value && userForm.value.password) {
        await adminService.changeUserPassword(editingUser.value.id, userForm.value.password)
      }
    } else {
      // Création d'un nouvel utilisateur
      await adminService.createUser({
        name: userForm.value.name,
        email: userForm.value.email,
        role: userForm.value.role,
        password: userForm.value.password
      })
    }

    // Recharger les données
    await loadUsers()
    await loadStats()

    // Fermer la modal
    closeUserModal()
  } catch (error) {
    console.error('Erreur lors de la sauvegarde:', error)
    notificationService.error('Erreur', 'Erreur lors de la sauvegarde de l\'utilisateur')
  } finally {
    userSaving.value = false
  }
}

const deleteUser = async (user: UserWithStats) => {
  if (!confirm(`Êtes-vous sûr de vouloir supprimer l'utilisateur ${user.name} ?`)) {
    return
  }

  try {
    await adminService.deleteUser(user.id)
    await loadUsers()
    await loadStats()
    notificationService.success('Succès', 'Utilisateur supprimé avec succès')
  } catch (error) {
    console.error('Erreur lors de la suppression:', error)
    notificationService.error('Erreur', 'Erreur lors de la suppression de l\'utilisateur')
  }
}

// Fonction pour supprimer un projet
const deleteProject = async (project: Project) => {
  if (!confirm(`Êtes-vous sûr de vouloir supprimer le projet "${project.name}" ?`)) {
    return
  }

  try {
    await adminService.deleteProject(project.id)
    await loadProjects()
    await loadStats()
    notificationService.success('Succès', 'Projet supprimé avec succès')
  } catch (error) {
    console.error('Erreur lors de la suppression du projet:', error)
    notificationService.error('Erreur', 'Erreur lors de la suppression du projet')
  }
}
</script>
