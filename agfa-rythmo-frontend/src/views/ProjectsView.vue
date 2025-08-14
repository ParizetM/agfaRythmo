<template>
  <div class="projects-container">
    <h2>Mes projets</h2>
    <button class="create-btn" @click="showCreateModal = true">Créer un nouveau projet</button>

    <div v-if="loading" class="loading">Chargement...</div>
    <div v-else>
      <div v-if="projects.length === 0">Aucun projet pour le moment.</div>
      <ul class="project-list">
        <li v-for="project in projects" :key="project.id">
          <router-link :to="`/projects/${project.id}`">
            <strong>{{ project.name }}</strong>
          </router-link>
        </li>
      </ul>
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
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';

const projects = ref<any[]>([]);
const loading = ref(true);
const showCreateModal = ref(false);
const uploading = ref(false);
const form = ref({ name: '', description: '', video: null as File | null });

async function fetchProjects() {
  loading.value = true;
  try {
    const res = await axios.get('/api/projects');
    projects.value = res.data;
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
    const uploadRes = await axios.post('/api/videos/upload', videoData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    // Création projet
    const projectRes = await axios.post('/api/projects', {
      name: form.value.name,
      description: form.value.description,
      video_path: uploadRes.data.path,
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
.project-list {
  list-style: none;
  padding: 0;
}
.project-list li {
  margin-bottom: 1rem;
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
