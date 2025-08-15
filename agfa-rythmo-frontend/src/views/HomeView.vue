<template>
  <div class="flex flex-col items-center justify-start min-h-screen p-8 animate-fade-in">
    <h1 class="text-5xl font-bold text-white mb-12 text-center animate-float">
      Bienvenue sur Agfa Rythmo
    </h1>

    <router-link to="/projects" class="mb-16">
      <button class="bg-agfa-blue hover:bg-agfa-blue-hover text-white font-semibold px-10 py-4 rounded-xl text-xl transition-all duration-300 transform hover:scale-105 hover:shadow-2xl">
        Gérer mes projets
      </button>
    </router-link>

    <div class="w-full max-w-7xl">
      <h2 class="text-3xl font-semibold text-white mb-8 text-center">Mes projets</h2>

      <div v-if="loading" class="text-white text-center text-lg">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-white mr-3"></div>
        Chargement...
      </div>

      <div v-else class="animate-fade-in">
        <div v-if="projects.length === 0" class="text-white text-center text-lg">
          Aucun projet pour le moment.
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
          <div
            v-for="project in projects"
            :key="project.id"
            class="group cursor-pointer transform transition-all duration-300 hover:scale-105"
          >
            <router-link :to="`/projects/${project.id}`" class="block">
              <div class="bg-white rounded-2xl p-6 card-shadow hover:card-shadow-hover transition-all duration-300">
                <div class="w-full h-32 bg-gray-800 rounded-xl overflow-hidden mb-4 relative">
                  <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all duration-300 rounded-xl z-0"></div>
                  <video
                    v-if="project.video_path"
                    :src="getVideoUrl(project.video_path)"
                    class="w-full h-full object-cover relative z-10"
                    preload="metadata"
                    muted
                    playsinline
                    @mouseover="onVideoHover($event)"
                    @mouseleave="onVideoLeave($event)"
                  ></video>
                  <div v-else class="flex items-center justify-center h-full text-white text-lg relative z-10">
                    Pas de vidéo
                  </div>
                </div>
                <h3 class="text-xl font-bold text-agfa-dark text-center group-hover:text-agfa-blue transition-colors duration-300">
                  {{ project.name }}
                </h3>
              </div>
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
