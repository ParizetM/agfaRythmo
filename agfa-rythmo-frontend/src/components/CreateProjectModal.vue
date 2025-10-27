<template>
  <BaseModal
    :show="show"
    title="Nouveau projet"
    subtitle="Créez un nouveau projet de bande rythmo ou importez-en un"
    size="2xl"
    :close-on-backdrop="!uploading && !isImporting"
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
      <!-- Onglets -->
      <div class="tabs-container mb-6">
        <div class="flex gap-2 min-w-max">
          <button
            type="button"
            @click="currentTab = 'create'"
            :class="['tab', { 'tab-active': currentTab === 'create' }]"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 4v16m8-8H4"
              />
            </svg>
            <span>Créer nouveau</span>
          </button>
          <button
            type="button"
            @click="currentTab = 'import'"
            :class="['tab', { 'tab-active': currentTab === 'import' }]"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
              />
            </svg>
            <span>Importer projet</span>
          </button>
        </div>
      </div>

      <!-- Formulaire de création -->
      <form v-show="currentTab === 'create'" id="create-project-form" @submit.prevent="handleSubmit" class="space-y-6 py-2">
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

      <!-- Formulaire d'import -->
      <form v-show="currentTab === 'import'" id="import-project-form" @submit.prevent="handleImport" class="space-y-6 py-2">
        <div class="bg-blue-900/30 border border-blue-500/50 rounded-lg p-4 mb-4">
          <p class="text-sm text-blue-200 mb-2">
            Importez un projet complet avec toutes ses données et sa vidéo
          </p>
          <p class="text-xs text-blue-300">
            💡 Vous devez fournir le fichier .agfa ET la vidéo originale
          </p>
        </div>

        <!-- Nom du projet -->
        <div class="space-y-2">
          <label for="import-project-name" class="block text-sm font-semibold text-gray-300">
            Nom du projet
            <span class="text-red-400">*</span>
          </label>
          <input
            id="import-project-name"
            v-model="importFormData.name"
            type="text"
            required
            placeholder="Nom du nouveau projet"
            class="w-full px-4 py-3 rounded-xl border border-gray-600 bg-agfa-bg-primary text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all duration-300 placeholder-gray-500 hover:border-gray-500"
          />
        </div>

        <!-- Fichier .agfa -->
        <div class="space-y-2">
          <label class="block text-sm font-semibold text-gray-300">
            Fichier de projet (.agfa)
            <span class="text-red-400">*</span>
          </label>
          <input
            type="file"
            accept=".agfa"
            required
            @change="onAgfaFileSelected"
            class="w-full p-3 rounded-xl border border-gray-600 bg-agfa-bg-primary text-white file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:bg-gradient-to-r file:from-purple-500 file:to-blue-600 file:text-white file:cursor-pointer hover:file:from-purple-600 hover:file:to-blue-700 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all duration-300"
          />
          <p v-if="importFormData.agfaFile" class="mt-2 text-sm text-green-400">
            ✓ {{ importFormData.agfaFile.name }} ({{ formatFileSize(importFormData.agfaFile.size) }})
            <span class="text-yellow-400 ml-2">🔒 Fichier crypté</span>
          </p>
        </div>

        <!-- Fichier vidéo -->
        <div class="space-y-2">
          <label class="block text-sm font-semibold text-gray-300">
            Vidéo du projet
            <span class="text-red-400">*</span>
          </label>
          <input
            type="file"
            accept="video/*"
            required
            @change="onVideoFileSelected"
            class="w-full p-3 rounded-xl border border-gray-600 bg-agfa-bg-primary text-white file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:bg-gradient-to-r file:from-blue-500 file:to-purple-600 file:text-white file:cursor-pointer hover:file:from-blue-600 hover:file:to-purple-700 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all duration-300"
          />
          <p v-if="importFormData.videoFile" class="mt-2 text-sm text-green-400">
            ✓ {{ importFormData.videoFile.name }} ({{ formatFileSize(importFormData.videoFile.size) }})
            <span class="text-blue-400 ml-2">🎬 Vidéo</span>
          </p>
        </div>

        <!-- Messages d'erreur/succès -->
        <div v-if="importError" class="bg-red-900/30 border border-red-500/50 rounded-lg p-4">
          <p class="text-sm text-red-200">❌ {{ importError }}</p>
        </div>

        <div v-if="importSuccess" class="bg-green-900/30 border border-green-500/50 rounded-lg p-4">
          <p class="text-sm text-green-200">✅ {{ importSuccess }}</p>
        </div>

        <!-- Barre de progression -->
        <div v-if="isImporting" class="space-y-2">
          <div class="flex justify-between text-sm text-gray-300">
            <span>{{ importStep }}</span>
            <span>{{ importProgress }}%</span>
          </div>
          <div class="w-full bg-gray-700 rounded-full h-3 overflow-hidden">
            <div
              class="bg-gradient-to-r from-blue-500 to-purple-600 h-full transition-all duration-300"
              :style="{ width: importProgress + '%' }"
            ></div>
          </div>
        </div>
      </form>
    </template>

    <template v-slot:footer>
      <button
        type="button"
        @click="handleClose"
        :disabled="uploading || isImporting"
        class="flex-1 px-4 sm:px-6 py-3 bg-gray-600 hover:bg-gray-700 disabled:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-semibold rounded-xl transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98]"
      >
        Annuler
      </button>
      <button
        v-if="currentTab === 'create'"
        type="submit"
        form="create-project-form"
        :disabled="uploading || !formData.name || !formData.video"
        class="flex-1 px-4 sm:px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 disabled:from-gray-600 disabled:to-gray-700 disabled:cursor-not-allowed text-white font-semibold rounded-xl transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98] shadow-lg shadow-blue-500/25 disabled:shadow-none"
      >
        {{ uploading ? 'Création...' : 'Créer le projet' }}
      </button>
      <button
        v-if="currentTab === 'import'"
        type="submit"
        form="import-project-form"
        :disabled="isImporting || !importFormData.name || !importFormData.agfaFile || !importFormData.videoFile"
        class="flex-1 px-4 sm:px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 disabled:from-gray-600 disabled:to-gray-700 disabled:cursor-not-allowed text-white font-semibold rounded-xl transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98] shadow-lg shadow-blue-500/25 disabled:shadow-none"
      >
        {{ isImporting ? importStep : 'Importer le projet' }}
      </button>
    </template>
  </BaseModal>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import BaseModal from './BaseModal.vue'
import api from '@/api/axios'
import { notificationService } from '@/services/notifications'

interface Props {
  show: boolean
}

interface Emits {
  (e: 'close'): void
  (e: 'created', project: unknown): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Gestion des onglets
const currentTab = ref<'create' | 'import'>('create')

// Formulaire de création
const formData = ref({
  name: '',
  description: '',
  video: null as File | null,
})

const uploading = ref(false)
const uploadProgress = ref(0)
const isDragging = ref(false)
const fileInput = ref<HTMLInputElement>()

// Formulaire d'import
const importFormData = ref({
  name: '',
  agfaFile: null as File | null,
  videoFile: null as File | null
})

const isImporting = ref(false)
const importProgress = ref(0)
const importStep = ref('')
const importError = ref<string | null>(null)
const importSuccess = ref<string | null>(null)

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
      notificationService.warning('Format invalide', 'Seuls les fichiers MP4 sont acceptés.')
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
      notificationService.warning('Format invalide', 'Seuls les fichiers MP4 sont acceptés.')
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
  if (!uploading.value && !isImporting.value) {
    emit('close')
    resetForm()
  }
}

function resetForm() {
  // Reset création
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

  // Reset import
  importFormData.value = {
    name: '',
    agfaFile: null,
    videoFile: null
  }
  importProgress.value = 0
  importStep.value = ''
  importError.value = null
  importSuccess.value = null

  // Reset onglet
  currentTab.value = 'create'
}

// Gestion du fichier .agfa
function onAgfaFileSelected(event: Event) {
  const input = event.target as HTMLInputElement
  if (input.files && input.files.length > 0) {
    importFormData.value.agfaFile = input.files[0]
    importError.value = null
  }
}

// Gestion du fichier vidéo
function onVideoFileSelected(event: Event) {
  const input = event.target as HTMLInputElement
  if (input.files && input.files.length > 0) {
    importFormData.value.videoFile = input.files[0]
    importError.value = null
  }
}

// Gestion de l'import complet
async function handleImport() {
  if (!importFormData.value.name || !importFormData.value.agfaFile || !importFormData.value.videoFile) return

  isImporting.value = true
  importProgress.value = 0
  importError.value = null
  importSuccess.value = null

  try {
    // Étape 1: Upload de la vidéo (0-50%)
    importStep.value = 'Upload de la vidéo...'
    const videoFormData = new FormData()
    videoFormData.append('video', importFormData.value.videoFile!)

    const videoResponse = await api.post('/videos/upload', videoFormData, {
      headers: { 'Content-Type': 'multipart/form-data' },
      onUploadProgress: (progressEvent) => {
        if (progressEvent.total) {
          importProgress.value = Math.round((progressEvent.loaded / progressEvent.total) * 50)
        }
      }
    })

    // Extraire le nom du fichier depuis l'URL retournée
    const url = videoResponse.data.url
    let filename = url
    if (typeof url === 'string') {
      const parts = url.split('/')
      filename = parts[parts.length - 1]
    }

    // Étape 2: Import du projet (50-100%)
    importStep.value = 'Import du projet...'
    importProgress.value = 50

    const projectFormData = new FormData()
    projectFormData.append('file', importFormData.value.agfaFile!)
    projectFormData.append('name', importFormData.value.name)
    projectFormData.append('video_path', filename)

    const projectResponse = await api.post('/projects/import', projectFormData, {
      headers: { 'Content-Type': 'multipart/form-data' },
      onUploadProgress: (progressEvent) => {
        if (progressEvent.total) {
          importProgress.value = 50 + Math.round((progressEvent.loaded / progressEvent.total) * 50)
        }
      }
    })

    importProgress.value = 100
    importSuccess.value = 'Projet importé avec succès !'

    // Notifier le parent et fermer
    setTimeout(() => {
      emit('created', projectResponse.data)
      emit('close')
      resetForm()
    }, 1000)
  } catch (error: unknown) {
    console.error('Erreur lors de l\'import du projet:', error)
    const err = error as { response?: { data?: { message?: string } } }
    importError.value = err.response?.data?.message || 'Erreur lors de l\'import du projet'
  } finally {
    isImporting.value = false
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
    notificationService.error('Erreur', 'Erreur lors de la création du projet. Veuillez réessayer.')
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
/* Conteneur des onglets */
.tabs-container {
  border-bottom: 2px solid #374151;
  margin-bottom: 0;
  -webkit-overflow-scrolling: touch;
}

.tabs-container::-webkit-scrollbar {
  height: 4px;
}

.tabs-container::-webkit-scrollbar-track {
  background: #1f2937;
  border-radius: 2px;
}

.tabs-container::-webkit-scrollbar-thumb {
  background: #4b5563;
  border-radius: 2px;
}

.tab {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  background-color: transparent;
  color: #9ca3af;
  border: none;
  border-bottom: 3px solid transparent;
  cursor: pointer;
  font-size: 0.875rem;
  font-weight: 600;
  transition: all 0.2s ease;
  margin-bottom: -2px;
  white-space: nowrap;
}

.tab:hover {
  color: #d1d5db;
  background-color: rgba(132, 85, 246, 0.1);
}

.tab-active {
  color: #8455f6;
  border-bottom-color: #8455f6;
}

.tab-active:hover {
  color: #9d6fff;
}

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
