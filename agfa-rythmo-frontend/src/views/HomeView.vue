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
      <!-- Banner erreur d'accès -->
      <div v-if="accessError" class="mb-6 p-4 bg-red-600 text-white rounded-lg flex items-center justify-between">
        <div class="text-sm">{{ accessError }}</div>
        <button @click="accessError = null" class="bg-transparent border border-white text-white px-3 py-1 rounded-md text-sm">Fermer</button>
      </div>

      <!-- Banner erreur API -->
      <div v-if="apiError" class="mb-6 p-4 bg-red-600 text-white rounded-lg flex items-center justify-between">
        <div class="text-sm">Erreur serveur : {{ apiError }}</div>
        <div class="flex items-center gap-3">
          <button @click="reloadProjects" class="bg-white text-red-600 px-3 py-1 rounded-md text-sm font-medium">Réessayer</button>
          <button @click="apiError = null" class="bg-transparent border border-white text-white px-3 py-1 rounded-md text-sm">Fermer</button>
        </div>
      </div>

      <h2 class="text-3xl font-semibold text-white mb-8 text-center">Aperçu des projets</h2>

      <div v-if="loading" class="text-white text-center text-lg">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-white mr-3"></div>
        Chargement...
      </div>

      <div v-else class="animate-fade-in space-y-12">
        <div v-if="recentOwnedProjects.length === 0 && recentCollaborativeProjects.length === 0" class="text-white text-center text-lg">
          Aucun projet pour le moment.
        </div>        <!-- Mes projets récents -->
        <div v-if="recentOwnedProjects.length > 0">
          <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl font-semibold text-white">Mes projets récents</h3>
            <router-link to="/projects" class="text-agfa-blue hover:text-agfa-blue-hover text-sm font-medium">
              Voir tous →
            </router-link>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
            <div
              v-for="project in recentOwnedProjects"
              :key="'owned-' + project.id"
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
                  <div v-if="project.collaborators && project.collaborators.length > 0" class="text-center mt-2">
                    <span class="text-xs text-gray-500">{{ project.collaborators.length }} collaborateur(s)</span>
                  </div>
                </div>
              </router-link>
            </div>
          </div>
        </div>

        <!-- Projets collaboratifs récents -->
        <div v-if="recentCollaborativeProjects.length > 0">
          <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl font-semibold text-white">Projets collaboratifs récents</h3>
            <router-link to="/projects" class="text-agfa-blue hover:text-agfa-blue-hover text-sm font-medium">
              Voir tous →
            </router-link>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
            <div
              v-for="project in recentCollaborativeProjects"
              :key="'collab-' + project.id"
              class="group cursor-pointer transform transition-all duration-300 hover:scale-105"
            >
              <router-link :to="`/projects/${project.id}`" class="block">
                <div class="bg-white rounded-2xl p-6 card-shadow hover:card-shadow-hover transition-all duration-300 relative">
                  <div class="absolute top-4 right-4 z-10">
                    <span class="bg-indigo-600 text-white text-xs px-2 py-1 rounded-full">
                      Collaborateur
                    </span>
                  </div>
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
                  <div v-if="project.owner" class="text-center mt-1">
                    <span class="text-xs text-gray-500">Par {{ project.owner.name }}</span>
                  </div>
                </div>
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import api from '../api/axios';
import { useAuthStore } from '@/stores/auth';

interface User {
  id: number;
  name: string;
  email: string;
  role: string;
}

interface Project {
  id: number;
  name: string;
  video_path?: string;
  user_id?: number;
  owner?: User;
  collaborators?: User[];
}

const route = useRoute();
const authStore = useAuthStore();
const projects = ref<Project[]>([]);
const loading = ref(true);
const apiError = ref<string | null>(null);
const accessError = ref<string | null>(null);

// Séparer les projets récents en fonction du propriétaire (limité à 4 projets max)
const recentOwnedProjects = computed(() =>
  projects.value
    .filter(project => project.user_id === authStore.user?.id)
    .slice(0, 4)
);

const recentCollaborativeProjects = computed(() =>
  projects.value
    .filter(project =>
      project.user_id !== authStore.user?.id &&
      project.collaborators?.some(collab => collab.id === authStore.user?.id)
    )
    .slice(0, 4)
);

function getVideoUrl(path?: string) {
  if (!path) return '';
  // Si path est déjà une URL absolue, on la retourne
  if (path.startsWith('http')) return path;
  // Sinon, on construit l'URL de l'API vidéo
  const apiBase = import.meta.env.VITE_API_URL?.replace(/\/api\/?$/, '') || '';
  return `${apiBase}/api/videos/${encodeURIComponent(path)}`;
}

onMounted(async () => {
  // Récupérer le message d'erreur depuis les paramètres de query
  if (route.query.error) {
    accessError.value = String(route.query.error);
  }

  // S'assurer que l'utilisateur est chargé avant de charger les projets
  if (!authStore.user && authStore.token) {
    await authStore.fetchProfile();
  }
  reloadProjects();
});

async function reloadProjects() {
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
