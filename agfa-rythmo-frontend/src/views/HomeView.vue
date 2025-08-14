<template>
  <div class="home-container">
    <h1>Bienvenue sur Agfa Rythmo</h1>
    <router-link to="/projects">
      <button class="main-btn">Gérer mes projets</button>
    </router-link>
    <div class="projects-thumbnails">
      <h2>Mes projets</h2>
      <div v-if="loading">Chargement...</div>
      <div v-else>
        <div v-if="projects.length === 0">Aucun projet pour le moment.</div>
        <div class="card-list">
          <div v-for="project in projects" :key="project.id" class="project-card">
            <router-link :to="`/projects/${project.id}`">
              <div class="card-thumb">
                <video
                  v-if="project.video_path"
                  :src="getVideoUrl(project.video_path)"
                  width="220"
                  height="124"
                  preload="metadata"
                  muted
                  playsinline
                  @mouseover="onVideoHover($event)"
                  @mouseleave="onVideoLeave($event)"
                  style="object-fit:cover; border-radius:10px; background:#222;"
                ></video>
                <div v-else class="no-thumb">Pas de vidéo</div>
              </div>
              <div class="card-title">{{ project.name }}</div>
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '../api/axios';

interface Project {
  id: number;
  name: string;
  video_path?: string;
}

const projects = ref<Project[]>([]);
const loading = ref(true);

function getVideoUrl(path?: string) {
  if (!path) return '';
  // Si path est déjà une URL absolue, on la retourne
  if (path.startsWith('http')) return path;
  // Sinon, on construit l'URL de l'API vidéo
  const apiBase = import.meta.env.VITE_API_URL?.replace(/\/api\/?$/, '') || '';
  return `${apiBase}/api/videos/${encodeURIComponent(path)}`;
}

onMounted(async () => {
  loading.value = true;
  try {
    const res = await api.get('/projects');
    projects.value = Array.isArray(res.data) ? res.data : [];
  } finally {
    loading.value = false;
  }
});

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
</script>

<style scoped>
.home-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-start;
  min-height: 80vh;
}
.main-btn {
  margin-top: 2rem;
  padding: 1rem 2rem;
  font-size: 1.2rem;
  background: #2d3748;
  color: #fff;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.2s;
}
.main-btn:hover {
  background: #4a5568;
}
.projects-thumbnails {
  margin-top: 3rem;
  width: 100%;
  max-width: 1100px;
}
.card-list {
  display: flex;
  flex-wrap: wrap;
  gap: 2rem;
  justify-content: flex-start;
}
.project-card {
  background: #fff;
  border-radius: 14px;
  box-shadow: 0 2px 12px #0002;
  width: 240px;
  padding: 1rem 1rem 0.7rem 1rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  transition: box-shadow 0.2s, transform 0.2s;
}
.project-card:hover {
  box-shadow: 0 6px 24px #0003;
  transform: translateY(-4px) scale(1.03);
}
.card-thumb {
  width: 220px;
  height: 124px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #222;
  border-radius: 10px;
  overflow: hidden;
}
.no-thumb {
  color: #fff;
  font-size: 1.1rem;
  text-align: center;
}
.card-title {
  margin-top: 0.7rem;
  font-weight: bold;
  text-align: center;
  font-size: 1.1rem;
  color: #2d3748;
}
</style>
