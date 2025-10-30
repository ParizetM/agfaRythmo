<template>
  <div class="min-h-screen p-8 animate-fade-in">
    <div class="max-w-7xl mx-auto">
      <div class="flex justify-between items-center mb-8">
        <h2 class="text-4xl font-bold text-white">Mes projets</h2>
        <div class="flex items-center gap-4">
          <!-- Bouton invitations (optionnel) -->
          <button
            v-if="!showInvitations && invitationCount > 0"
            @click="showInvitations = true"
            class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded-lg transition-all duration-300 text-sm"
          >
            📧 Voir mes invitations ({{ invitationCount }})
          </button>
          <button
            v-else-if="showInvitations"
            @click="showInvitations = false"
            class="bg-gray-600 hover:bg-gray-700 text-white font-medium px-4 py-2 rounded-lg transition-all duration-300 text-sm"
          >
            ✕ Masquer les invitations
          </button>

          <button
            @click="showCreateModal = true"
            class="bg-agfa-blue hover:bg-agfa-blue-hover text-white font-semibold px-8 py-3 rounded-xl transition-all duration-300 transform hover:scale-105 hover:shadow-xl"
          >
            Nouveau projet
          </button>
        </div>
      </div>

      <!-- Onglets de filtrage -->
      <div class="mb-8 border-b border-gray-700">
        <nav class="flex space-x-8" aria-label="Tabs">
          <button
            @click="activeTab = 'in_progress'"
            :class="[
              activeTab === 'in_progress'
                ? 'border-agfa-blue text-agfa-blue'
                : 'border-transparent text-gray-400 hover:text-gray-300 hover:border-gray-300',
              'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-300',
            ]"
          >
            En cours ({{ inProgressProjects.length }})
          </button>
          <button
            @click="activeTab = 'completed'"
            :class="[
              activeTab === 'completed'
                ? 'border-agfa-blue text-agfa-blue'
                : 'border-transparent text-gray-400 hover:text-gray-300 hover:border-gray-300',
              'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-300',
            ]"
          >
            Terminés ({{ completedProjects.length }})
          </button>
          <button
            @click="activeTab = 'collaborative'"
            :class="[
              activeTab === 'collaborative'
                ? 'border-agfa-blue text-agfa-blue'
                : 'border-transparent text-gray-400 hover:text-gray-300 hover:border-gray-300',
              'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-300',
            ]"
          >
            Collaboratifs ({{ collaborativeProjects.length }})
          </button>
        </nav>
      </div>

      <!-- Banner erreur API -->
      <div
        v-if="apiError"
        class="mb-6 p-4 bg-red-600 text-white rounded-lg flex items-center justify-between"
      >
        <div class="text-sm">Erreur serveur : {{ apiError }}</div>
        <div class="flex items-center gap-3">
          <button
            @click="fetchProjects"
            class="bg-red-800 hover:bg-red-900 text-white px-3 py-1 rounded-md text-sm font-medium"
          >
            Réessayer
          </button>
          <button
            @click="apiError = null"
            class="bg-transparent border border-white text-white px-3 py-1 rounded-md text-sm"
          >
            Fermer
          </button>
        </div>
      </div>

      <div v-if="loading" class="text-center py-16">
        <div
          class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-white mr-4"
        ></div>
        <span class="text-white text-xl">Chargement...</span>
      </div>

      <div v-else>
        <!-- Panneau des invitations - affiché seulement si explicitement demandé et qu'il y a des invitations -->
        <div v-if="showInvitations && invitationCount > 0" class="mb-8">
          <InvitationsPanel
            @invitation-accepted="onInvitationAccepted"
            @invitation-declined="onInvitationDeclined"
          />
        </div>

        <div
          v-if="ownedProjects.length === 0 && collaborativeProjects.length === 0"
          class="text-center py-16"
        >
          <div class="text-white text-xl mb-4">Aucun projet pour le moment.</div>
          <button
            @click="showCreateModal = true"
            class="bg-agfa-green hover:bg-agfa-green-hover text-white font-semibold px-6 py-3 rounded-xl transition-all duration-300"
          >
            Créer votre premier projet
          </button>
        </div>

        <!-- Affichage selon l'onglet actif -->
        <div v-else>
          <!-- Projets en cours -->
          <div v-if="activeTab === 'in_progress'">
            <div v-if="inProgressProjects.length === 0" class="text-center py-16">
              <div class="text-white text-xl mb-4">Aucun projet en cours.</div>
              <button
                @click="showCreateModal = true"
                class="bg-agfa-green hover:bg-agfa-green-hover text-white font-semibold px-6 py-3 rounded-xl transition-all duration-300"
              >
                Créer un nouveau projet
              </button>
            </div>
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
              <ProjectCard
                v-for="project in inProgressProjects.filter(isValidProject)"
                :key="project.id"
                :project="project"
                :is-owner="true"
                @edit="openEditModal"
                @delete="openDeleteModal"
                @toggle-status="toggleProjectStatus"
              />
            </div>
          </div>

          <!-- Projets terminés -->
          <div v-if="activeTab === 'completed'">
            <div v-if="completedProjects.length === 0" class="text-center py-16">
              <div class="text-white text-xl mb-4">Aucun projet terminé.</div>
              <p class="text-gray-400">
                Marquez un projet comme terminé pour le retrouver ici.
              </p>
            </div>
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
              <ProjectCard
                v-for="project in completedProjects.filter(isValidProject)"
                :key="project.id"
                :project="project"
                :is-owner="true"
                @edit="openEditModal"
                @delete="openDeleteModal"
                @toggle-status="toggleProjectStatus"
              />
            </div>
          </div>

          <!-- Projets collaboratifs -->
          <div v-if="activeTab === 'collaborative'">
            <div v-if="collaborativeProjects.length === 0" class="text-center py-16">
              <div class="text-white text-xl mb-4">Aucun projet collaboratif.</div>
              <p class="text-gray-400">
                Les projets partagés avec vous apparaîtront ici.
              </p>
            </div>
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
              <ProjectCard
                v-for="project in collaborativeProjects.filter(isValidProject)"
                :key="project.id"
                :project="project"
                :is-owner="false"
                @open="openProject"
              />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal création/import projet -->
    <CreateProjectModal
      :show="showCreateModal"
      @close="showCreateModal = false"
      @created="onProjectCreated"
    />

    <!-- Modal édition projet -->
    <EditProjectModal
      :show="showEditModal"
      :project="projectToEdit"
      @close="showEditModal = false"
      @updated="handleProjectUpdated"
    />

    <!-- Modal suppression projet -->
    <DeleteProjectModal
      :show="showDeleteModal"
      :project="projectToDelete"
      @close="showDeleteModal = false"
      @confirm="confirmDeleteProject"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import api from '../api/axios'
import { useAuthStore } from '@/stores/auth'
import InvitationsPanel from '../components/InvitationsPanel.vue'
import CreateProjectModal from '../components/CreateProjectModal.vue'
import EditProjectModal from '../components/EditProjectModal.vue'
import DeleteProjectModal from '../components/DeleteProjectModal.vue'
import ProjectCard from '../components/ProjectCard.vue'
import { useInvitations } from '@/composables/useInvitations'

interface Timecode {
  start: number
  end: number
  text: string
}

interface User {
  id: number
  name: string
  email: string
  role: string
}

interface Project {
  id: number
  name: string
  description?: string
  video_path?: string
  timecodes?: Timecode[]
  text_content?: string
  json_path?: string
  created_at?: string
  updated_at?: string
  user_id?: number
  status?: 'in_progress' | 'completed'
  owner?: User
  collaborators?: User[]
}

const authStore = useAuthStore()
const { invitationCount } = useInvitations()
const projects = ref<Project[]>([])
const loading = ref(true)
const apiError = ref<string | null>(null)
const activeTab = ref<'in_progress' | 'completed' | 'collaborative'>('in_progress')

// Séparer les projets en fonction du propriétaire
const ownedProjects = computed(() =>
  projects.value.filter((project) => project.user_id === authStore.user?.id),
)

const collaborativeProjects = computed(() =>
  projects.value.filter(
    (project) =>
      project.user_id !== authStore.user?.id &&
      project.collaborators?.some((collab) => collab.id === authStore.user?.id),
  ),
)

// Filtrer les projets en cours (uniquement les projets dont on est propriétaire)
const inProgressProjects = computed(() =>
  ownedProjects.value.filter((project) => !project.status || project.status === 'in_progress'),
)

// Filtrer les projets terminés (uniquement les projets dont on est propriétaire)
const completedProjects = computed(() =>
  ownedProjects.value.filter((project) => project.status === 'completed'),
)

const showCreateModal = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const showInvitations = ref(false)
const projectToEdit = ref<Project | null>(null)
const projectToDelete = ref<Project | null>(null)

async function toggleProjectStatus(project: Project) {
  try {
    const newStatus = project.status === 'completed' ? 'in_progress' : 'completed'
    await api.put(`/projects/${project.id}`, { status: newStatus })

    // Mettre à jour le projet dans la liste
    const index = projects.value.findIndex((p) => p.id === project.id)
    if (index !== -1) {
      projects.value[index].status = newStatus
    }
    apiError.value = null
  } catch (err: unknown) {
    apiError.value =
      err instanceof Error ? err.message : String(err) || 'Erreur lors du changement de statut'
  }
}

async function fetchProjects() {
  loading.value = true
  try {
    const res = await api.get('/projects')
    projects.value = Array.isArray(res.data) ? res.data : []
    apiError.value = null
  } catch (err: unknown) {
    apiError.value =
      err instanceof Error ? err.message : String(err) || 'Impossible de joindre le serveur'
  } finally {
    loading.value = false
  }
}

function onProjectCreated(project: unknown) {
  // Recharger la liste des projets pour inclure le nouveau
  fetchProjects()
  // Rediriger vers le nouveau projet
  const projectData = project as { id: number }
  window.location.href = `/projects/${projectData.id}`
}

function openEditModal(project: Project) {
  projectToEdit.value = project
  showEditModal.value = true
}

function handleProjectUpdated(updatedProject: Project) {
  const index = projects.value.findIndex((p) => p.id === updatedProject.id)
  if (index !== -1) {
    projects.value[index] = { ...projects.value[index], ...updatedProject }
  }
  apiError.value = null
}

function openDeleteModal(project: Project) {
  projectToDelete.value = project
  showDeleteModal.value = true
}

async function confirmDeleteProject() {
  if (!projectToDelete.value) return
  try {
    await api.delete(`/projects/${projectToDelete.value.id}`)
    projects.value = projects.value.filter((p) => p.id !== projectToDelete.value!.id)
    apiError.value = null
  } catch (err: unknown) {
    apiError.value =
      err instanceof Error ? err.message : String(err) || 'Erreur lors de la suppression'
  } finally {
    showDeleteModal.value = false
    projectToDelete.value = null
  }
}

function openProject(project: Project) {
  // Naviguer vers le projet sans les options de modification/suppression
  window.location.href = `/projects/${project.id}`
}

function isValidProject(project: unknown): project is Project {
  return typeof project === 'object' && project !== null && 'id' in project && 'name' in project
}

function onInvitationAccepted() {
  // Recharger les projets pour afficher le nouveau projet collaboratif
  fetchProjects()
}

function onInvitationDeclined() {
  // Pas besoin de recharger les projets
}

// Watcher pour automatiquement cacher le panneau quand il n'y a plus d'invitations
watch(invitationCount, (newCount) => {
  if (newCount === 0 && showInvitations.value) {
    showInvitations.value = false
  }
})

onMounted(async () => {
  // S'assurer que l'utilisateur est chargé avant de charger les projets
  if (!authStore.user && authStore.token) {
    await authStore.fetchProfile()
  }
  fetchProjects()
})
</script>
