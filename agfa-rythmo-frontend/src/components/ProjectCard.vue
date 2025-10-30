<template>
  <div
    class="group relative rounded-2xl overflow-hidden shadow-lg border border-gray-700/50 transition-all duration-300 hover:shadow-xl hover:border-gray-600 bg-agfa-dark-30"
  >
    <router-link :to="`/projects/${project.id}`" class="block">
      <!-- Vidéo container simplifié -->
      <div class="aspect-video bg-gray-900 relative overflow-hidden">
        <video
          v-if="project.video_path && !videoError"
          :src="getVideoUrl(project.video_path)"
          class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
          preload="metadata"
          muted
          playsinline
          @mouseover="onVideoHover"
          @mouseleave="onVideoLeave"
          @error="onVideoError"
        ></video>
        <div
          v-else
          class="flex flex-col items-center justify-center h-full text-gray-400"
        >
          <svg class="w-12 h-12 mb-2 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
          </svg>
          <span class="text-sm">Vidéo non disponible</span>
        </div>

        <!-- Badge collaboratif uniquement -->
        <div
          v-if="!isOwner"
          class="absolute top-3 left-3 z-20 bg-purple-600/90 backdrop-blur-sm text-white text-xs font-semibold px-3 py-1.5 rounded-lg shadow-lg flex items-center gap-1.5"
        >
          <UsersIcon class="w-3.5 h-3.5" />
          <span>Collaboratif</span>
        </div>
      </div>

      <!-- Contenu de la carte -->
      <div class="p-5 relative z-20">
        <!-- Titre simplifié -->
        <h3
          class="text-lg sm:text-xl font-bold text-white mb-2 group-hover:text-blue-400 transition-colors duration-200 line-clamp-2 break-words"
        >
          {{ project.name }}
        </h3>

        <!-- Propriétaire (pour projets collaboratifs) -->
        <div v-if="!isOwner && project.owner" class="flex items-center mb-3">
          <div
            class="h-7 w-7 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center mr-2 shadow-md"
          >
            <span class="text-white font-bold text-xs">
              {{ project.owner.name.charAt(0).toUpperCase() }}
            </span>
          </div>
          <div>
            <p class="text-xs text-gray-500 leading-none">Propriétaire</p>
            <p class="text-sm text-gray-300 font-medium">
              {{ project.owner.name }}
            </p>
          </div>
        </div>

        <!-- Description -->
        <p class="text-gray-400 text-sm leading-relaxed line-clamp-2 mb-3">
          {{ project.description || 'Aucune description' }}
        </p>

        <!-- Métadonnées -->
        <div
          v-if="isOwner && project.collaborators && project.collaborators.length > 0"
          class="flex items-center gap-1.5 text-xs text-gray-500"
        >
          <UsersIcon class="w-4 h-4" />
          <span>{{ project.collaborators.length }} collaborateur{{ project.collaborators.length > 1 ? 's' : '' }}</span>
        </div>
      </div>
    </router-link>

    <!-- Actions pour propriétaire simplifiées -->
    <div v-if="isOwner" class="px-5 pb-5 relative z-20">
      <!-- Bouton principal de statut -->
      <button
        v-if="project.status === 'in_progress'"
        @click.stop="$emit('toggle-status', project)"
        class="w-full mb-2.5 bg-green-600 hover:bg-green-700 text-white py-2.5 px-4 rounded-lg transition-all duration-200 text-sm font-semibold hover:scale-[1.01] active:scale-[0.99] flex items-center justify-center gap-2"
        title="Marquer comme terminé"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
        </svg>
        <span>Marquer comme terminé</span>
      </button>
      <button
        v-else
        @click.stop="$emit('toggle-status', project)"
        class="w-full mb-2.5 bg-blue-600 hover:bg-blue-700 text-white py-2.5 px-4 rounded-lg transition-all duration-200 text-sm font-semibold hover:scale-[1.01] active:scale-[0.99] flex items-center justify-center gap-2"
        title="Remettre en cours"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        <span>Remettre en cours</span>
      </button>

      <!-- Boutons secondaires -->
      <div class="grid grid-cols-2 gap-2.5">
        <button
          @click.stop="$emit('edit', project)"
          class="bg-gray-700/50 hover:bg-gray-700 border border-gray-600 hover:border-gray-500 text-white py-2.5 px-3 rounded-lg transition-all duration-200 text-sm font-medium hover:scale-[1.01] active:scale-[0.99] flex items-center justify-center gap-2"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
          </svg>
          <span>Modifier</span>
        </button>

        <button
          @click.stop="$emit('delete', project)"
          class="bg-red-600/20 hover:bg-red-600/30 border border-red-600/50 hover:border-red-500 text-red-400 hover:text-red-300 py-2.5 px-3 rounded-lg transition-all duration-200 text-sm font-medium hover:scale-[1.01] active:scale-[0.99] flex items-center justify-center gap-2"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
          </svg>
          <span>Supprimer</span>
        </button>
      </div>
    </div>

    <!-- Actions pour collaborateur -->
    <div v-else class="px-5 pb-5 relative z-20">
      <button
        @click.stop="$emit('open', project)"
        class="w-full bg-purple-600 hover:bg-purple-700 text-white py-2.5 px-4 rounded-lg transition-all duration-200 text-sm font-semibold hover:scale-[1.01] active:scale-[0.99] flex items-center justify-center gap-2"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
        </svg>
        <span>Ouvrir le projet</span>
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { UsersIcon } from '@heroicons/vue/24/outline'

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
  status?: 'in_progress' | 'completed'
  user_id?: number
  owner?: User
  collaborators?: User[]
}

interface Props {
  project: Project
  isOwner: boolean
}

defineProps<Props>()

defineEmits<{
  edit: [project: Project]
  delete: [project: Project]
  open: [project: Project]
  'toggle-status': [project: Project]
}>()

const videoError = ref(false)

function getVideoUrl(path?: string) {
  if (!path) return ''
  // Si path est déjà une URL absolue, on la retourne
  if (path.startsWith('http')) return path
  // Sinon, on construit l'URL de l'API vidéo
  const apiBase = import.meta.env.VITE_API_URL?.replace(/\/api\/?$/, '') || ''
  return `${apiBase}/api/videos/${encodeURIComponent(path)}`
}

function onVideoHover(event: MouseEvent) {
  const video = event.target as HTMLVideoElement | null
  if (video) video.play()
}

function onVideoLeave(event: MouseEvent) {
  const video = event.target as HTMLVideoElement | null
  if (video) {
    video.pause()
    video.currentTime = 0
  }
}

function onVideoError() {
  videoError.value = true
}
</script>
