<template>
  <BaseModal
    :show="show"
    title="Importer un projet complet"
    subtitle="Importez un fichier .agfa et sa vidéo associée"
    size="2xl"
    max-height="90vh"
    @close="$emit('close')"
  >
    <template v-slot:icon>
      <svg class="w-6 h-6 sm:w-8 sm:h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
        />
      </svg>
    </template>

    <template v-slot:default>
      <form id="import-form" @submit.prevent="handleImport" class="space-y-6">
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
          <label for="project-name" class="block text-sm font-semibold text-gray-300">
            Nom du projet
            <span class="text-red-400">*</span>
          </label>
          <input
            id="project-name"
            v-model="formData.name"
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
          <p v-if="formData.agfaFile" class="mt-2 text-sm text-green-400">
            ✓ {{ formData.agfaFile.name }} ({{ formatFileSize(formData.agfaFile.size) }})
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
          <p v-if="formData.videoFile" class="mt-2 text-sm text-green-400">
            ✓ {{ formData.videoFile.name }} ({{ formatFileSize(formData.videoFile.size) }})
            <span class="text-blue-400 ml-2">🎬 Vidéo</span>
          </p>
        </div>

        <!-- Aperçu des données du projet -->
        <div v-if="projectPreview" class="bg-gray-800/50 border border-gray-600 rounded-lg p-4 space-y-2">
          <h4 class="text-white font-semibold mb-2">📋 Aperçu du projet</h4>
          <div class="text-sm text-gray-300 space-y-1">
            <p>• <strong>Nom original:</strong> {{ projectPreview.project.name }}</p>
            <p v-if="projectPreview.project.description">• <strong>Description:</strong> {{ projectPreview.project.description }}</p>
            <p>• <strong>Lignes rythmo:</strong> {{ projectPreview.project.rythmo_lines_count }}</p>
            <p>• <strong>Timecodes:</strong> {{ projectPreview.timecodes.length }}</p>
            <p>• <strong>Personnages:</strong> {{ projectPreview.characters.length }}</p>
            <p>• <strong>Changements de plan:</strong> {{ projectPreview.scene_changes.length }}</p>
          </div>
        </div>

        <!-- Messages d'erreur/succès -->
        <div v-if="importError" class="bg-red-900/30 border border-red-500/50 rounded-lg p-4">
          <p class="text-sm text-red-200">❌ {{ importError }}</p>
        </div>

        <div v-if="importSuccess" class="bg-green-900/30 border border-green-500/50 rounded-lg p-4">
          <p class="text-sm text-green-200">✅ {{ importSuccess }}</p>
        </div>

        <!-- Barre de progression -->
        <div v-if="isUploading" class="space-y-2">
          <div class="flex justify-between text-sm text-gray-300">
            <span>{{ uploadStep }}</span>
            <span>{{ uploadProgress }}%</span>
          </div>
          <div class="w-full bg-gray-700 rounded-full h-3 overflow-hidden">
            <div
              class="bg-gradient-to-r from-blue-500 to-purple-600 h-full transition-all duration-300"
              :style="{ width: uploadProgress + '%' }"
            ></div>
          </div>
        </div>
      </form>
    </template>

    <template v-slot:footer>
      <button
        type="button"
        @click="$emit('close')"
        :disabled="isUploading"
        class="flex-1 px-4 sm:px-6 py-3 bg-gray-600 hover:bg-gray-700 disabled:bg-gray-800 disabled:cursor-not-allowed text-white font-semibold rounded-xl transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98]"
      >
        Annuler
      </button>
      <button
        type="submit"
        form="import-form"
        :disabled="!canSubmit || isUploading"
        class="flex-1 px-4 sm:px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 disabled:from-gray-600 disabled:to-gray-700 disabled:cursor-not-allowed text-white font-semibold rounded-xl transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98] shadow-lg shadow-blue-500/25 disabled:shadow-none"
      >
        {{ isUploading ? uploadStep : 'Importer le projet' }}
      </button>
    </template>
  </BaseModal>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import BaseModal from './BaseModal.vue'
import type { ProjectExportData } from '@/api/projects'
import api from '@/api/axios'

interface FormData {
  name: string
  agfaFile: File | null
  videoFile: File | null
}

defineProps<{
  show: boolean
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'project-imported', projectId: number): void
}>()

const formData = ref<FormData>({
  name: '',
  agfaFile: null,
  videoFile: null
})

const projectPreview = ref<ProjectExportData | null>(null)
const importError = ref<string | null>(null)
const importSuccess = ref<string | null>(null)
const isUploading = ref(false)
const uploadProgress = ref(0)
const uploadStep = ref('')

const canSubmit = computed(() => {
  return formData.value.name && formData.value.agfaFile && formData.value.videoFile
})

// Gestion du fichier .agfa
async function onAgfaFileSelected(event: Event) {
  const input = event.target as HTMLInputElement
  if (input.files && input.files.length > 0) {
    formData.value.agfaFile = input.files[0]
    importError.value = null
    projectPreview.value = null

    // Note: On ne peut pas prévisualiser un fichier crypté côté client
    // La prévisualisation se fera après décryptage côté serveur
  }
}

// Gestion du fichier vidéo
function onVideoFileSelected(event: Event) {
  const input = event.target as HTMLInputElement
  if (input.files && input.files.length > 0) {
    formData.value.videoFile = input.files[0]
    importError.value = null
  }
}

// Formater la taille du fichier
function formatFileSize(bytes: number): string {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}

// Gestion de l'import complet
async function handleImport() {
  if (!canSubmit.value || isUploading.value) return

  isUploading.value = true
  uploadProgress.value = 0
  importError.value = null
  importSuccess.value = null

  try {
    // Étape 1: Upload de la vidéo (0-50%)
    uploadStep.value = 'Upload de la vidéo...'
    const videoFormData = new FormData()
    videoFormData.append('video', formData.value.videoFile!)

    const videoResponse = await api.post('/videos/upload', videoFormData, {
      headers: { 'Content-Type': 'multipart/form-data' },
      onUploadProgress: (progressEvent) => {
        if (progressEvent.total) {
          uploadProgress.value = Math.round((progressEvent.loaded / progressEvent.total) * 50)
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
    uploadStep.value = 'Import du projet...'
    uploadProgress.value = 50

    const projectFormData = new FormData()
    projectFormData.append('file', formData.value.agfaFile!)
    projectFormData.append('name', formData.value.name)
    projectFormData.append('video_path', filename)

    const projectResponse = await api.post('/projects/import', projectFormData, {
      headers: { 'Content-Type': 'multipart/form-data' },
      onUploadProgress: (progressEvent) => {
        if (progressEvent.total) {
          uploadProgress.value = 50 + Math.round((progressEvent.loaded / progressEvent.total) * 50)
        }
      }
    })

    uploadProgress.value = 100
    importSuccess.value = 'Projet importé avec succès !'

    // Notifier le parent
    setTimeout(() => {
      emit('project-imported', projectResponse.data.project.id)
      emit('close')
    }, 1000)

  } catch (error: unknown) {
    const err = error as { response?: { data?: { message?: string } } }
    importError.value = err.response?.data?.message || 'Erreur lors de l\'import du projet'
    uploadProgress.value = 0
  } finally {
    isUploading.value = false
    uploadStep.value = ''
  }
}
</script>

<style scoped>
/* Styles personnalisés si nécessaire */
</style>
