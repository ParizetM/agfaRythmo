<template>
  <div class="projects-container">
    <h2>Mes projets</h2>
    <button class="create-btn" @click="showCreateModal = true">Créer un nouveau projet</button>

    <div v-if="loading" class="loading">Chargement...</div>
    <div v-else>
      <div class="card-list">
        <div v-for="project in projects.filter(isValidProject)" :key="project.id" class="project-card">
          <router-link :to="`/projects/${project.id}`" class="card-link">
            <div class="card-thumb">
              <video
                v-if="project.video_path && !videoError[project.id]"
                :src="getVideoUrl(project.video_path)"
                width="220"
                height="124"
                preload="metadata"
                muted
                playsinline
                :poster="getPosterFrame(project.video_path)"
                @mouseover="onVideoHover($event)"
                @mouseleave="onVideoLeave($event)"
                @error="onVideoError($event, project.id)"
                style="object-fit:cover; border-radius:10px; background:#222;"
              ></video>
              <div v-else class="no-thumb">Vidéo non trouvée</div>
            </div>
            <div class="card-content">
              <div class="card-title">{{ project.name }}</div>
              <div class="card-desc">{{ project.description || 'Pas de description' }}</div>
            </div>
          </router-link>
          <div class="card-actions">
            <button class="edit-btn" @click.stop="openEditModal(project)">Modifier</button>
            <button class="delete-btn" @click.stop="openDeleteModal(project)">Supprimer</button>
          </div>
        </div>
      </div>
      <div v-if="projects.filter(isValidProject).length === 0">Aucun projet pour le moment.</div>
    </div>

    <!-- Modal création projet -->
    <div v-if="showCreateModal" class="modal-backdrop">
      <div class="modal">
        <h3>Nouveau projet</h3>
        <form @submit.prevent="createProject">
          <input v-model="form.name" placeholder="Nom du projet" required />
          <textarea v-model="form.description" placeholder="Description"></textarea>
          <input type="file" accept="video/*" @change="onFileChange" required />
          <button type="submit">Créer</button>
          <button type="button" @click="showCreateModal = false">Annuler</button>
        </form>
        <div v-if="uploading">Upload en cours...</div>
      </div>
    </div>

    <!-- Modal édition projet -->
    <div v-if="showEditModal" class="modal-backdrop">
      <div class="modal">
        <h3>Modifier le projet</h3>
        <form @submit.prevent="updateProject">
          <input v-model="editForm.name" placeholder="Nom du projet" required />
          <textarea v-model="editForm.description" placeholder="Description"></textarea>
          <input type="file" accept="video/*" @change="onEditFileChange" />
          <button type="submit">Enregistrer</button>
          <button type="button" @click="showEditModal = false">Annuler</button>
        </form>
        <div v-if="editUploading">Mise à jour en cours...</div>
      </div>
    </div>

    <!-- Modal suppression projet -->
    <div v-if="showDeleteModal" class="modal-backdrop">
      <div class="modal">
        <h3>Supprimer le projet</h3>
        <p>Êtes-vous sûr de vouloir supprimer ce projet ? Cette action est irréversible.</p>
        <div class="modal-actions">
          <button @click="confirmDeleteProject" class="delete-btn">Oui, supprimer</button>
          <button @click="showDeleteModal = false">Annuler</button>
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
  } finally {
    showDeleteModal.value = false;
    projectToDelete.value = null;
  }
}

function isValidProject(project: unknown): project is Project {
  return typeof project === 'object' && project !== null && 'id' in project && 'name' in project;
}

function getPosterFrame(path?: string) {
  // Si tu as un service de génération de thumbnail, adapte ici
  // Sinon, on retourne la même image que la vidéo (le navigateur prendra la première frame)
  return path ? getVideoUrl(path) : '';
}

onMounted(fetchProjects);
</script>

<style scoped>
.projects-container {
  max-width: 700px;
  margin: 2rem auto;
  padding: 2rem;
  background: #f7fafc;
  border-radius: 12px;
  box-shadow: 0 2px 8px #0001;
}
.create-btn {
  margin-bottom: 1.5rem;
  padding: 0.7rem 1.5rem;
  background: #3182ce;
  color: #fff;
  border: none;
  border-radius: 6px;
  cursor: pointer;
}
.create-btn:hover {
  background: #2563eb;
}
.card-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 1.5rem;
}
.project-card {
  background: #fff;
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 2px 12px #0002;
  transition: transform 0.2s;
  cursor: pointer;
}
.project-card:hover {
  transform: translateY(-2px);
}
.card-thumb {
  position: relative;
  width: 100%;
  padding-top: 56.25%; /* 16:9 ratio */
}
.card-thumb video {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  border-radius: 10px 10px 0 0;
}
.card-content {
  padding: 1rem;
}
.card-title {
  font-weight: 500;
  font-size: 1.1rem;
  margin-bottom: 0.5rem;
}
.card-desc {
  font-size: 0.9rem;
  color: #555;
  margin-bottom: 1rem;
}
.card-actions {
  display: flex;
  gap: 0.5rem;
}
.view-btn, .edit-btn, .delete-btn {
  flex: 1;
  padding: 0.5rem;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  text-align: center;
}
.view-btn {
  background: #38a169;
  color: #fff;
}
.view-btn:hover {
  background: #2f855a;
}
.edit-btn {
  background: #3182ce;
  color: #fff;
}
.edit-btn:hover {
  background: #2563eb;
}
.delete-btn {
  background: #e53e3e;
  color: #fff;
}
.delete-btn:hover {
  background: #c53030;
}
.loading {
  color: #888;
}
.modal-backdrop {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: #0005;
  display: flex;
  align-items: center;
  justify-content: center;
}
.modal {
  background: #fff;
  padding: 2rem;
  border-radius: 10px;
  min-width: 320px;
  box-shadow: 0 2px 12px #0002;
}
.modal input, .modal textarea {
  width: 100%;
  margin-bottom: 1rem;
  padding: 0.5rem;
  border-radius: 4px;
  border: 1px solid #ddd;
}
.modal button {
  margin-right: 1rem;
  padding: 0.5rem 1.2rem;
  border: none;
  border-radius: 4px;
  background: #3182ce;
  color: #fff;
  cursor: pointer;
}
.modal button[type="button"] {
  background: #aaa;
}
</style>
