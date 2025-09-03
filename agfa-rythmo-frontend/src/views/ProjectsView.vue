<template>
  <div class="min-h-screen p-8 animate-fade-in">
    <div class="max-w-7xl mx-auto">
      <div class="flex justify-between items-center mb-8">
        <h2 class="text-4xl font-bold text-white">Mes projets</h2>
        <button
          @click="showCreateModal = true"
          class="bg-agfa-blue hover:bg-agfa-blue-hover text-white font-semibold px-8 py-3 rounded-xl transition-all duration-300 transform hover:scale-105 hover:shadow-xl"
        >
          Créer un nouveau projet
        </button>
      </div>

      <!-- Banner erreur API -->
      <div v-if="apiError" class="mb-6 p-4 bg-red-600 text-white rounded-lg flex items-center justify-between">
        <div class="text-sm">Erreur serveur : {{ apiError }}</div>
        <div class="flex items-center gap-3">
          <button @click="fetchProjects" class="bg-white text-red-600 px-3 py-1 rounded-md text-sm font-medium">Réessayer</button>
          <button @click="apiError = null" class="bg-transparent border border-white text-white px-3 py-1 rounded-md text-sm">Fermer</button>
        </div>
      </div>

      <div v-if="loading" class="text-center py-16">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-white mr-4"></div>
        <span class="text-white text-xl">Chargement...</span>
      </div>

      <div v-else>
        <div v-if="projects.filter(isValidProject).length === 0" class="text-center py-16">
          <div class="text-white text-xl mb-4">Aucun projet pour le moment.</div>
          <button
            @click="showCreateModal = true"
            class="bg-agfa-green hover:bg-agfa-green-hover text-white font-semibold px-6 py-3 rounded-xl transition-all duration-300"
          >
            Créer votre premier projet
          </button>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
          <div
            v-for="project in projects.filter(isValidProject)"
            :key="project.id"
            class="group bg-white rounded-2xl overflow-hidden card-shadow hover:card-shadow-hover transition-all duration-300 transform hover:-translate-y-2"
          >
            <router-link :to="`/projects/${project.id}`" class="block">
              <div class="aspect-video bg-gray-800 relative overflow-hidden">
                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all duration-300 z-0"></div>
                <video
                  v-if="project.video_path && !videoError[project.id]"
                  :src="getVideoUrl(project.video_path)"
                  class="w-full h-full object-cover relative z-10"
                  preload="metadata"
                  muted
                  playsinline
                  @mouseover="onVideoHover($event)"
                  @mouseleave="onVideoLeave($event)"
                  @error="onVideoError($event, project.id)"
                ></video>
                <div v-else class="flex items-center justify-center h-full text-white text-lg relative z-10">
                  Vidéo non trouvée
                </div>
              </div>

              <div class="p-6">
                <h3 class="text-xl font-bold text-agfa-dark mb-2 group-hover:text-agfa-blue transition-colors duration-300">
                  {{ project.name }}
                </h3>
                <p class="text-gray-600 text-sm line-clamp-2">
                  {{ project.description || 'Pas de description' }}
                </p>
              </div>
            </router-link>

            <div class="px-6 pb-6 flex gap-3">
              <button
                @click.stop="openEditModal(project)"
                class="flex-1 bg-agfa-blue hover:bg-agfa-blue-hover text-white py-2 px-4 rounded-lg transition-all duration-300 text-sm font-medium"
              >
                Modifier
              </button>
              <button
                @click.stop="openDeleteModal(project)"
                class="flex-1 bg-agfa-red hover:bg-agfa-red-hover text-white py-2 px-4 rounded-lg transition-all duration-300 text-sm font-medium"
              >
                Supprimer
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal création projet -->
    <div v-if="showCreateModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl p-8 max-w-md w-full transform transition-all duration-300 scale-100">
        <h3 class="text-2xl font-bold text-agfa-dark mb-6">Nouveau projet</h3>
        <form @submit.prevent="createProject" class="space-y-4">
          <input
            v-model="form.name"
            placeholder="Nom du projet"
            required
            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-agfa-blue focus:border-transparent outline-none transition-all duration-300"
          />
          <textarea
            v-model="form.description"
            placeholder="Description"
            rows="3"
            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-agfa-blue focus:border-transparent outline-none transition-all duration-300 resize-none"
          ></textarea>
          <input
            type="file"
            accept="video/*"
            @change="onFileChange"
            required
            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-agfa-blue focus:border-transparent outline-none transition-all duration-300"
          />

          <div class="flex gap-4 pt-4">
            <button
              type="submit"
              :disabled="uploading"
              class="flex-1 bg-agfa-blue hover:bg-agfa-blue-hover disabled:bg-gray-400 text-white py-3 rounded-lg transition-all duration-300 font-medium"
            >
              {{ uploading ? 'Création...' : 'Créer' }}
            </button>
            <button
              type="button"
              @click="showCreateModal = false"
              class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-3 rounded-lg transition-all duration-300 font-medium"
            >
              Annuler
            </button>
          </div>
        </form>

        <div v-if="uploading" class="mt-4 text-center">
          <div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-agfa-blue mr-2"></div>
          <span class="text-agfa-blue">Upload en cours...</span>
        </div>
      </div>
    </div>

    <!-- Modal édition projet -->
    <div v-if="showEditModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl p-8 max-w-md w-full transform transition-all duration-300 scale-100">
        <h3 class="text-2xl font-bold text-agfa-dark mb-6">Modifier le projet</h3>
        <form @submit.prevent="updateProject" class="space-y-4">
          <input
            v-model="editForm.name"
            placeholder="Nom du projet"
            required
            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-agfa-blue focus:border-transparent outline-none transition-all duration-300"
          />
          <textarea
            v-model="editForm.description"
            placeholder="Description"
            rows="3"
            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-agfa-blue focus:border-transparent outline-none transition-all duration-300 resize-none"
          ></textarea>
          <input
            type="file"
            accept="video/*"
            @change="onEditFileChange"
            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-agfa-blue focus:border-transparent outline-none transition-all duration-300"
          />

          <div class="flex gap-4 pt-4">
            <button
              type="submit"
              :disabled="editUploading"
              class="flex-1 bg-agfa-blue hover:bg-agfa-blue-hover disabled:bg-gray-400 text-white py-3 rounded-lg transition-all duration-300 font-medium"
            >
              {{ editUploading ? 'Mise à jour...' : 'Enregistrer' }}
            </button>
            <button
              type="button"
              @click="showEditModal = false"
              class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-3 rounded-lg transition-all duration-300 font-medium"
            >
              Annuler
            </button>
          </div>
        </form>

        <div v-if="editUploading" class="mt-4 text-center">
          <div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-agfa-blue mr-2"></div>
          <span class="text-agfa-blue">Mise à jour en cours...</span>
        </div>
      </div>
    </div>

    <!-- Modal suppression projet -->
    <div v-if="showDeleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl p-8 max-w-md w-full transform transition-all duration-300 scale-100">
        <h3 class="text-2xl font-bold text-agfa-dark mb-4">Supprimer le projet</h3>
        <p class="text-gray-600 mb-6">Êtes-vous sûr de vouloir supprimer ce projet ? Cette action est irréversible.</p>

        <div class="flex gap-4">
          <button
            @click="confirmDeleteProject"
            class="flex-1 bg-agfa-red hover:bg-agfa-red-hover text-white py-3 rounded-lg transition-all duration-300 font-medium"
          >
            Oui, supprimer
          </button>
          <button
            @click="showDeleteModal = false"
            class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-3 rounded-lg transition-all duration-300 font-medium"
          >
            Annuler
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '../api/axios';

interface Timecode {
  start: number;
  end: number;
  text: string;
}

interface Project {
  id: number;
  name: string;
  description?: string;
  video_path?: string;
  timecodes?: Timecode[];
  text_content?: string;
  json_path?: string;
  created_at?: string;
  updated_at?: string;
}

const projects = ref<Project[]>([]);
const loading = ref(true);
const apiError = ref<string | null>(null);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const editUploading = ref(false);
const projectToDelete = ref<Project | null>(null);
const form = ref({ name: '', description: '', video: null as File | null });
const editForm = ref<{ id: number; name: string; description: string; video: File | null; video_path?: string }>({ id: 0, name: '', description: '', video: null, video_path: '' });
const uploading = ref(false);
const videoError = ref<Record<number, boolean>>({});

function getVideoUrl(path?: string) {
  if (!path) return '';
  // Si path est déjà une URL absolue, on la retourne
  if (path.startsWith('http')) return path;
  // Sinon, on construit l'URL de l'API vidéo
  const apiBase = import.meta.env.VITE_API_URL?.replace(/\/api\/?$/, '') || '';
  return `${apiBase}/api/videos/${encodeURIComponent(path)}`;
}
function onVideoHover(event: MouseEvent) {
  const video = event.target as HTMLVideoElement | null;
  if (video) video.play();
}
function onVideoLeave(event: MouseEvent) {
  const video = event.target as HTMLVideoElement | null;
  if (video) {
    video.pause();
    video.currentTime = 0;
  }
}
function onVideoError(event: Event, id: number) {
  videoError.value[id] = true;
}

async function fetchProjects() {
  loading.value = true;
  try {
    const res = await api.get('/projects');
    projects.value = Array.isArray(res.data) ? res.data : [];
    apiError.value = null;
  } catch (err: unknown) {
    apiError.value = err instanceof Error ? err.message : String(err) || 'Impossible de joindre le serveur';
  } finally {
    loading.value = false;
  }
}

function onFileChange(e: Event) {
  const files = (e.target as HTMLInputElement).files;
  if (files && files.length > 0) {
    form.value.video = files[0];
  }
}

async function createProject() {
  if (!form.value.video) return;
  uploading.value = true;
  try {
    // Upload vidéo
    const videoData = new FormData();
    videoData.append('video', form.value.video);
    const uploadRes = await api.post('/videos/upload', videoData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    // Récupérer juste le nom du fichier pour video_path
    const url = uploadRes.data.url;
    let filename = url;
    // Si url commence par '/', on prend le dernier segment
    if (typeof url === 'string') {
      const parts = url.split('/');
      filename = parts[parts.length - 1];
    }
    // Création projet
    const projectRes = await api.post('/projects', {
      name: form.value.name,
      description: form.value.description,
      video_path: filename,
    });
    projects.value.push(projectRes.data);
    showCreateModal.value = false;
    form.value.name = '';
    form.value.description = '';
    form.value.video = null;
    apiError.value = null;
  } catch (err: unknown) {
    apiError.value = err instanceof Error ? err.message : String(err) || 'Erreur lors de la création du projet';
  } finally {
    uploading.value = false;
  }
}

function openEditModal(project: Project) {
  editForm.value = {
    id: project.id,
    name: project.name,
    description: project.description || '',
    video: null,
    video_path: project.video_path,
  };
  showEditModal.value = true;
}

function onEditFileChange(e: Event) {
  const files = (e.target as HTMLInputElement).files;
  if (files && files.length > 0) {
    editForm.value.video = files[0];
  }
}

async function updateProject() {
  editUploading.value = true;
  try {
    let videoPath = editForm.value.video_path;
    if (editForm.value.video) {
      // Upload new video
      const videoData = new FormData();
      videoData.append('video', editForm.value.video);
      const uploadRes = await api.post('/videos/upload', videoData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      // Récupérer juste le nom du fichier pour video_path
      const url = uploadRes.data.url;
      let filename = url;
      if (typeof url === 'string') {
        const parts = url.split('/');
        filename = parts[parts.length - 1];
      }
      videoPath = filename;
    }
    // Update project
    await api.put(`/projects/${editForm.value.id}`, {
      name: editForm.value.name,
      description: editForm.value.description,
      video_path: videoPath,
    });
    const index = projects.value.findIndex(p => p.id === editForm.value.id);
    if (index !== -1) {
      projects.value[index] = { ...projects.value[index], ...editForm.value, video_path: videoPath };
    }
    showEditModal.value = false;
    editForm.value = { id: 0, name: '', description: '', video: null };
    apiError.value = null;
  } catch (err: unknown) {
    apiError.value = err instanceof Error ? err.message : String(err) || 'Erreur lors de la mise à jour';
  } finally {
    editUploading.value = false;
  }
}

function openDeleteModal(project: Project) {
  projectToDelete.value = project;
  showDeleteModal.value = true;
}

async function confirmDeleteProject() {
  if (!projectToDelete.value) return;
  try {
    await api.delete(`/projects/${projectToDelete.value.id}`);
    projects.value = projects.value.filter(p => p.id !== projectToDelete.value!.id);
    apiError.value = null;
  } catch (err: unknown) {
    apiError.value = err instanceof Error ? err.message : String(err) || 'Erreur lors de la suppression';
  } finally {
    showDeleteModal.value = false;
    projectToDelete.value = null;
  }
}

function isValidProject(project: unknown): project is Project {
  return typeof project === 'object' && project !== null && 'id' in project && 'name' in project;
}

onMounted(fetchProjects);
</script>
