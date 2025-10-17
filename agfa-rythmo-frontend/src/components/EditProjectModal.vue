<template>
  <BaseModal
    :show="show"
    title="Modifier le projet"
    subtitle="Mettez à jour les informations de votre projet"
    size="lg"
    :close-on-backdrop="!uploading"
    @close="handleClose"
  >
    <template v-slot:icon>
      <svg class="w-6 h-6 sm:w-8 sm:h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
        />
      </svg>
    </template>

    <template v-slot:default>
      <form id="edit-project-form" @submit.prevent="handleSubmit" class="space-y-6">
        <!-- Nom du projet -->
        <div class="space-y-2">
          <label for="edit-project-name" class="block text-sm font-semibold text-gray-300">
            Nom du projet
            <span class="text-red-400">*</span>
          </label>
          <input
            id="edit-project-name"
            v-model="formData.name"
            type="text"
            required
            placeholder="Ex: Doublage Film Capella"
            class="w-full px-4 py-3 bg-agfa-bg-primary border border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all duration-300 text-white placeholder-gray-500 hover:border-gray-500"
          />
        </div>

        <!-- Description -->
        <div class="space-y-2">
          <label for="edit-project-description" class="block text-sm font-semibold text-gray-300">
            Description
          </label>
          <textarea
            id="edit-project-description"
            v-model="formData.description"
            rows="3"
            placeholder="Décrivez votre projet..."
            class="w-full px-4 py-3 bg-agfa-bg-primary border border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all duration-300 resize-none text-white placeholder-gray-500 hover:border-gray-500"
          ></textarea>
        </div>

        <!-- Changement de vidéo (optionnel) -->
        <div class="space-y-2">
          <label for="edit-project-video" class="block text-sm font-semibold text-gray-300">
            Remplacer la vidéo (optionnel)
          </label>
          <div class="relative">
            <input
              id="edit-project-video"
              type="file"
              accept="video/mp4"
              @change="handleFileChange"
              class="w-full px-4 py-3 rounded-xl border border-gray-600 bg-agfa-bg-primary text-white file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:bg-gradient-to-r file:from-blue-500 file:to-purple-600 file:text-white file:cursor-pointer hover:file:from-blue-600 hover:file:to-purple-700 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all duration-300"
            />
            <p class="mt-2 text-xs text-gray-400">
              Format supporté : MP4 uniquement
            </p>
          </div>
          <div v-if="formData.newVideo" class="mt-3 p-3 bg-green-900/20 border border-green-500/30 rounded-lg">
            <p class="text-sm text-green-300 flex items-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
              {{ formData.newVideo.name }} ({{ formatFileSize(formData.newVideo.size) }})
            </p>
          </div>
        </div>

        <!-- Barre de progression si upload -->
        <div v-if="uploading" class="space-y-3">
          <div class="flex items-center justify-between text-sm">
            <span class="text-gray-300 font-medium">Upload de la nouvelle vidéo...</span>
            <span class="text-blue-400 font-bold">{{ uploadProgress }}%</span>
          </div>
          <div class="h-3 bg-gray-700 rounded-full overflow-hidden">
            <div
              class="h-full bg-gradient-to-r from-blue-500 to-purple-600 rounded-full transition-all duration-300 ease-out relative overflow-hidden"
              :style="{ width: `${uploadProgress}%` }"
            >
              <!-- Effet de shimmer -->
              <div
                class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent shimmer"
              ></div>
            </div>
          </div>
          <p class="text-center text-gray-400 text-xs">
            Veuillez patienter pendant l'upload...
          </p>
        </div>
      </form>
    </template>

    <template v-slot:footer>
      <button
        type="button"
        @click="handleClose"
        :disabled="uploading"
        class="flex-1 px-4 sm:px-6 py-3 bg-gray-600 hover:bg-gray-700 disabled:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-semibold rounded-xl transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98]"
      >
        Annuler
      </button>
      <button
        type="submit"
        form="edit-project-form"
        :disabled="uploading || !formData.name"
        class="flex-1 px-4 sm:px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 disabled:from-gray-600 disabled:to-gray-700 disabled:cursor-not-allowed text-white font-semibold rounded-xl transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98] shadow-lg shadow-blue-500/25 disabled:shadow-none"
      >
        {{ uploading ? 'Mise à jour...' : 'Enregistrer' }}
      </button>
    </template>
  </BaseModal>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import BaseModal from './BaseModal.vue'
import api from '@/api/axios'

interface Project {
  id: number
  name: string
  description?: string
  video_path?: string
}

interface Props {
  show: boolean
  project: Project | null
}

interface Emits {
  (e: 'close'): void
  (e: 'updated', project: Project): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const formData = ref({
  name: '',
  description: '',
  newVideo: null as File | null,
})

const uploading = ref(false)
const uploadProgress = ref(0)

// Initialiser le formulaire quand le projet change
watch(
  () => props.project,
  (project) => {
    if (project) {
      formData.value = {
        name: project.name,
        description: project.description || '',
        newVideo: null,
      }
    }
  },
  { immediate: true }
)

function handleFileChange(event: Event) {
  const target = event.target as HTMLInputElement
  if (target.files && target.files.length > 0) {
    const file = target.files[0]
    if (file.type === 'video/mp4') {
      formData.value.newVideo = file
    } else {
      alert('Seuls les fichiers MP4 sont acceptés.')
      target.value = ''
    }
  }
}

function formatFileSize(bytes: number): string {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
}

function handleClose() {
  if (!uploading.value) {
    emit('close')
    resetForm()
  }
}

function resetForm() {
  formData.value = {
    name: '',
    description: '',
    newVideo: null,
  }
  uploadProgress.value = 0
}

async function handleSubmit() {
  if (!props.project || !formData.value.name) return

  uploading.value = true
  uploadProgress.value = 0

  try {
    let videoFilename = props.project.video_path

    // Si une nouvelle vidéo est fournie, l'uploader d'abord
    if (formData.value.newVideo) {
      const videoData = new FormData()
      videoData.append('video', formData.value.newVideo)

      const uploadRes = await api.post('/videos/upload', videoData, {
        headers: { 'Content-Type': 'multipart/form-data' },
        onUploadProgress: (progressEvent) => {
          if (progressEvent.total) {
            const percentCompleted = Math.round((progressEvent.loaded * 90) / progressEvent.total)
            uploadProgress.value = percentCompleted
          }
        },
      })

      // Extraire le nom du fichier
      const url = uploadRes.data.url
      if (typeof url === 'string') {
        const parts = url.split('/')
        videoFilename = parts[parts.length - 1]
      }

      uploadProgress.value = 95
    }

    // Mettre à jour le projet
    const updateRes = await api.put(`/projects/${props.project.id}`, {
      name: formData.value.name,
      description: formData.value.description,
      video_path: videoFilename,
    })

    uploadProgress.value = 100

    // Attendre un peu pour montrer 100%
    await new Promise((resolve) => setTimeout(resolve, 300))

    emit('updated', updateRes.data)
    emit('close')
    resetForm()
  } catch (error) {
    console.error('Erreur lors de la mise à jour du projet:', error)
    alert('Erreur lors de la mise à jour du projet. Veuillez réessayer.')
  } finally {
    uploading.value = false
    uploadProgress.value = 0
  }
}

// Réinitialiser le formulaire quand on ferme le modal
watch(
  () => props.show,
  (newValue) => {
    if (!newValue && !uploading.value) {
      resetForm()
    }
  }
)
</script>

<style scoped>
/* Animation shimmer pour la barre de progression */
@keyframes shimmer {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(100%);
  }
}

.shimmer {
  animation: shimmer 2s infinite;
}
</style>
