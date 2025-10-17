<template>
  <BaseModal
    :show="show"
    title="Nouveau projet"
    subtitle="Créez un nouveau projet de bande rythmo"
    size="2xl"
    :close-on-backdrop="!uploading"
    @close="handleClose"
  >
    <template v-slot:icon>
      <svg class="w-6 h-6 sm:w-8 sm:h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M12 4v16m8-8H4"
        />
      </svg>
    </template>

    <template v-slot:default>
      <form id="create-project-form" @submit.prevent="handleSubmit" class="space-y-6">
        <!-- Nom du projet -->
        <div class="space-y-2">
          <label for="project-name" class="block text-sm font-semibold text-gray-300">
            Nom du projet
            <span class="text-red-400">*</span>
          </label>
          <input
            id="project-name"
            v-model="formData.name"
            type="text"
            required
            placeholder="Ex: Doublage Film Capella"
            class="w-full px-4 py-3 bg-agfa-bg-primary border border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all duration-300 text-white placeholder-gray-500 hover:border-gray-500"
          />
        </div>

        <!-- Description -->
        <div class="space-y-2">
          <label for="project-description" class="block text-sm font-semibold text-gray-300">
            Description
          </label>
          <textarea
            id="project-description"
            v-model="formData.description"
            rows="3"
            placeholder="Décrivez votre projet..."
            class="w-full px-4 py-3 bg-agfa-bg-primary border border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all duration-300 resize-none text-white placeholder-gray-500 hover:border-gray-500"
          ></textarea>
        </div>

        <!-- Upload vidéo -->
        <div class="space-y-2">
          <label class="block text-sm font-semibold text-gray-300">
            Vidéo
            <span class="text-red-400">*</span>
          </label>

          <!-- Zone de drop -->
          <div
            @click="triggerFileInput"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="handleDrop"
            :class="[
              'relative border-2 border-dashed rounded-xl p-8 transition-all duration-300 cursor-pointer',
              isDragging
                ? 'border-blue-500 bg-blue-500/10'
                : 'border-gray-600 hover:border-gray-500 bg-agfa-bg-primary/50',
            ]"
          >
            <input
              ref="fileInput"
              type="file"
              accept="video/mp4"
              @change="handleFileChange"
              class="hidden"
            />

            <div v-if="!formData.video" class="text-center">
              <div
                class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-blue-500/20 to-purple-600/20 flex items-center justify-center"
              >
                <svg
                  class="w-8 h-8 text-blue-400"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                  />
                </svg>
              </div>
              <p class="text-white font-medium mb-1">
                Cliquez pour sélectionner ou glissez-déposez
              </p>
              <p class="text-gray-400 text-sm">Format supporté : MP4 uniquement</p>
            </div>

            <div v-else class="text-center">
              <div
                class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-green-500/20 to-emerald-600/20 flex items-center justify-center"
              >
                <svg
                  class="w-8 h-8 text-green-400"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"
                  />
                </svg>
              </div>
              <p class="text-white font-medium mb-1">{{ formData.video.name }}</p>
              <p class="text-gray-400 text-sm">{{ formatFileSize(formData.video.size) }}</p>
              <button
                type="button"
                @click.stop="removeFile"
                class="mt-3 text-red-400 hover:text-red-300 text-sm font-medium transition-colors"
              >
                Supprimer
              </button>
            </div>
          </div>
        </div>

        <!-- Barre de progression -->
        <div v-if="uploading" class="space-y-3">
          <div class="flex items-center justify-between text-sm">
            <span class="text-gray-300 font-medium">Upload en cours...</span>
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
            Veuillez patienter pendant l'upload de votre vidéo...
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
        form="create-project-form"
        :disabled="uploading || !formData.name || !formData.video"
        class="flex-1 px-4 sm:px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 disabled:from-gray-600 disabled:to-gray-700 disabled:cursor-not-allowed text-white font-semibold rounded-xl transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98] shadow-lg shadow-blue-500/25 disabled:shadow-none"
      >
        {{ uploading ? 'Création...' : 'Créer le projet' }}
      </button>
    </template>
  </BaseModal>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import BaseModal from './BaseModal.vue'
import api from '@/api/axios'

interface Props {
  show: boolean
}

interface Emits {
  (e: 'close'): void
  (e: 'created', project: unknown): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const formData = ref({
  name: '',
  description: '',
  video: null as File | null,
})

const uploading = ref(false)
const uploadProgress = ref(0)
const isDragging = ref(false)
const fileInput = ref<HTMLInputElement>()

function triggerFileInput() {
  fileInput.value?.click()
}

function handleFileChange(event: Event) {
  const target = event.target as HTMLInputElement
  if (target.files && target.files.length > 0) {
    const file = target.files[0]
    if (file.type === 'video/mp4') {
      formData.value.video = file
    } else {
      alert('Seuls les fichiers MP4 sont acceptés.')
      if (fileInput.value) {
        fileInput.value.value = ''
      }
    }
  }
}

function handleDrop(event: DragEvent) {
  isDragging.value = false
  if (event.dataTransfer?.files && event.dataTransfer.files.length > 0) {
    const file = event.dataTransfer.files[0]
    if (file.type === 'video/mp4') {
      formData.value.video = file
    } else {
      alert('Seuls les fichiers MP4 sont acceptés.')
    }
  }
}

function removeFile() {
  formData.value.video = null
  if (fileInput.value) {
    fileInput.value.value = ''
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
    video: null,
  }
  uploadProgress.value = 0
  isDragging.value = false
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

async function handleSubmit() {
  if (!formData.value.video || !formData.value.name) return

  uploading.value = true
  uploadProgress.value = 0

  try {
    // Simulation de progression pour l'upload vidéo
    const progressInterval = setInterval(() => {
      if (uploadProgress.value < 90) {
        uploadProgress.value += Math.random() * 15
        if (uploadProgress.value > 90) uploadProgress.value = 90
      }
    }, 300)

    // Upload vidéo
    const videoData = new FormData()
    videoData.append('video', formData.value.video)

    const uploadRes = await api.post('/videos/upload', videoData, {
      headers: { 'Content-Type': 'multipart/form-data' },
      onUploadProgress: (progressEvent) => {
        if (progressEvent.total) {
          const percentCompleted = Math.round((progressEvent.loaded * 90) / progressEvent.total)
          uploadProgress.value = percentCompleted
        }
      },
    })

    clearInterval(progressInterval)
    uploadProgress.value = 95

    // Récupérer le nom du fichier
    const url = uploadRes.data.url
    let filename = url
    if (typeof url === 'string') {
      const parts = url.split('/')
      filename = parts[parts.length - 1]
    }

    // Création du projet
    const projectRes = await api.post('/projects', {
      name: formData.value.name,
      description: formData.value.description,
      video_path: filename,
    })

    uploadProgress.value = 100

    // Attendre un peu pour montrer 100%
    await new Promise((resolve) => setTimeout(resolve, 500))

    emit('created', projectRes.data)
    emit('close')
    resetForm()
  } catch (error) {
    console.error('Erreur lors de la création du projet:', error)
    alert('Erreur lors de la création du projet. Veuillez réessayer.')
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
  },
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
