<template>
  <BaseModal
    :show="show"
    title="Créer un projet"
    :subtitle="importMode ? 'Importez un fichier JSON pour recréer un projet complet' : 'Créez un nouveau projet vierge'"
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
          d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
        />
      </svg>
    </template>

    <template v-slot:default>
      <!-- Bouton pour basculer entre mode manuel et import JSON -->
      <div class="flex gap-2 mb-6">
        <button
          type="button"
          @click="importMode = false"
          :class="[
            'flex-1 py-2 px-4 rounded-lg font-medium transition-colors',
            !importMode
              ? 'bg-agfa-blue text-white'
              : 'bg-gray-700 text-gray-300 hover:bg-gray-600'
          ]"
        >
          Création manuelle
        </button>
        <button
          type="button"
          @click="importMode = true"
          :class="[
            'flex-1 py-2 px-4 rounded-lg font-medium transition-colors',
            importMode
              ? 'bg-agfa-blue text-white'
              : 'bg-gray-700 text-gray-300 hover:bg-gray-600'
          ]"
        >
          Importer JSON
        </button>
      </div>

      <!-- Mode création manuelle -->
      <form v-if="!importMode" id="project-form" @submit.prevent="handleCreate" class="space-y-6">
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
            placeholder="Mon projet rythmo"
            class="w-full px-4 py-3 rounded-xl border border-gray-600 bg-agfa-bg-primary text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all duration-300 placeholder-gray-500 hover:border-gray-500"
          />
        </div>

        <div class="space-y-2">
          <label for="project-description" class="block text-sm font-semibold text-gray-300">
            Description (optionnelle)
          </label>
          <textarea
            id="project-description"
            v-model="formData.description"
            rows="3"
            placeholder="Décrivez votre projet..."
            class="w-full px-4 py-3 rounded-xl border border-gray-600 bg-agfa-bg-primary text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all duration-300 placeholder-gray-500 hover:border-gray-500 resize-none"
          ></textarea>
        </div>

        <div class="space-y-2">
          <label for="rythmo-lines" class="block text-sm font-semibold text-gray-300">
            Nombre de lignes rythmo
            <span class="text-red-400">*</span>
          </label>
          <select
            id="rythmo-lines"
            v-model="formData.rythmo_lines_count"
            required
            class="w-full px-4 py-3 border border-gray-600 text-white bg-agfa-bg-primary hover:border-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all duration-300 rounded-xl"
          >
            <option v-for="n in 6" :key="n" :value="n">
              {{ n === 1 ? 'Ligne unique' : `${n} lignes` }}
            </option>
          </select>
        </div>
      </form>

      <!-- Mode import JSON -->
      <div v-else class="space-y-4">
        <div class="bg-blue-900/30 border border-blue-500/50 rounded-lg p-4 mb-4">
          <p class="text-sm text-blue-200 mb-2">
            ℹ️ Importez un fichier JSON exporté depuis AgfaRythmo pour recréer un projet complet avec tous ses timecodes, personnages et changements de plan.
          </p>
          <p class="text-xs text-blue-300">
            💡 Le nom du projet peut être personnalisé ci-dessous.
          </p>
        </div>

        <label class="block">
          <span class="text-white mb-2 block font-semibold">
            Nom du nouveau projet
            <span class="text-red-400">*</span>
          </span>
          <input
            v-model="importData.name"
            type="text"
            required
            placeholder="Mon projet importé"
            class="w-full px-4 py-3 rounded-xl border border-gray-600 bg-agfa-bg-primary text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all duration-300 placeholder-gray-500 hover:border-gray-500"
          />
        </label>

        <label class="block">
          <span class="text-white mb-2 block font-semibold">
            Fichier JSON ou fichier crypté (.agfa)
            <span class="text-red-400">*</span>
          </span>
          <input
            type="file"
            accept=".json,.agfa,application/json"
            @change="onJsonFileSelected"
            class="w-full p-3 rounded-xl border border-gray-600 bg-agfa-bg-primary text-white file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:bg-gradient-to-r file:from-blue-500 file:to-purple-600 file:text-white file:cursor-pointer hover:file:from-blue-600 hover:file:to-purple-700 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all duration-300"
          />
          <p v-if="importData.file" class="mt-2 text-sm text-green-400">
            ✓ {{ importData.file.name }} ({{ formatFileSize(importData.file.size) }})
            <span v-if="importData.file.name.endsWith('.agfa')" class="text-yellow-400 ml-2">🔒 Fichier crypté</span>
          </p>
        </label>

        <!-- Prévisualisation des données du fichier -->
        <div v-if="importPreview" class="bg-gray-800/50 border border-gray-600 rounded-lg p-4 space-y-2">
          <h4 class="text-white font-semibold mb-2">📋 Aperçu du fichier</h4>
          <div class="text-sm text-gray-300 space-y-1">
            <p>• <strong>Nom original:</strong> {{ importPreview.project.name }}</p>
            <p v-if="importPreview.project.description">• <strong>Description:</strong> {{ importPreview.project.description }}</p>
            <p>• <strong>Lignes rythmo:</strong> {{ importPreview.project.rythmo_lines_count }}</p>
            <p>• <strong>Timecodes:</strong> {{ importPreview.timecodes.length }}</p>
            <p>• <strong>Personnages:</strong> {{ importPreview.characters.length }}</p>
            <p>• <strong>Changements de plan:</strong> {{ importPreview.scene_changes.length }}</p>
            <p class="text-xs text-gray-400 mt-2">Version d'export: {{ importPreview.export_version }}</p>
          </div>
        </div>

        <div v-if="importError" class="bg-red-900/30 border border-red-500/50 rounded-lg p-4">
          <p class="text-sm text-red-200">❌ {{ importError }}</p>
        </div>

        <div v-if="importSuccess" class="bg-green-900/30 border border-green-500/50 rounded-lg p-4">
          <p class="text-sm text-green-200">✅ {{ importSuccess }}</p>
        </div>
      </div>
    </template>

    <template v-slot:footer>
      <!-- Footer pour mode création manuelle -->
      <button
        v-if="!importMode"
        type="button"
        @click="$emit('close')"
        class="flex-1 px-4 sm:px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-xl transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98]"
      >
        Annuler
      </button>
      <button
        v-if="!importMode"
        type="submit"
        form="project-form"
        :disabled="isCreating"
        class="flex-1 px-4 sm:px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 disabled:from-gray-600 disabled:to-gray-700 disabled:cursor-not-allowed text-white font-semibold rounded-xl transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98] shadow-lg shadow-blue-500/25 disabled:shadow-none"
      >
        {{ isCreating ? 'Création...' : 'Créer le projet' }}
      </button>

      <!-- Footer pour mode import JSON -->
      <button
        v-if="importMode"
        type="button"
        @click="$emit('close')"
        class="flex-1 px-4 sm:px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-xl transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98]"
      >
        Annuler
      </button>
      <button
        v-if="importMode"
        type="button"
        @click="handleImport"
        :disabled="!importData.file || !importData.name || isImporting"
        class="flex-1 px-4 sm:px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 disabled:from-gray-600 disabled:to-gray-700 disabled:cursor-not-allowed text-white font-semibold rounded-xl transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98] shadow-lg shadow-blue-500/25 disabled:shadow-none"
      >
        {{ isImporting ? 'Import en cours...' : 'Importer le projet' }}
      </button>
    </template>
  </BaseModal>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import BaseModal from './BaseModal.vue'
import { createProject, importProject, type ProjectExportData } from '@/api/projects'
import { notificationService } from '@/services/notifications'

interface ProjectFormData {
  name: string
  description: string
  rythmo_lines_count: number
}

interface ImportData {
  name: string
  file: File | null
}

const props = defineProps<{
  show: boolean
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'project-created', projectId: number): void
}>()

// Mode d'affichage : false = création manuelle, true = import JSON
const importMode = ref(false)

// Données du formulaire de création manuelle
const formData = ref<ProjectFormData>({
  name: '',
  description: '',
  rythmo_lines_count: 1
})

// Données pour l'import JSON
const importData = ref<ImportData>({
  name: '',
  file: null
})

// Prévisualisation du fichier JSON
const importPreview = ref<ProjectExportData | null>(null)

// États
const isCreating = ref(false)
const isImporting = ref(false)
const importError = ref<string | null>(null)
const importSuccess = ref<string | null>(null)

// Réinitialise le formulaire dès que le modal doit s'afficher
watch(
  () => props.show,
  (show) => {
    if (!show) {
      // Réinitialiser le mode et les messages quand le modal se ferme
      importMode.value = false
      importError.value = null
      importSuccess.value = null
      importData.value.file = null
      importData.value.name = ''
      importPreview.value = null
      formData.value = {
        name: '',
        description: '',
        rythmo_lines_count: 1
      }
    }
  },
  { immediate: true }
)

// Gestion de la création manuelle
async function handleCreate() {
  if (isCreating.value) return

  isCreating.value = true
  try {
    const project = await createProject({
      name: formData.value.name,
      description: formData.value.description || undefined,
      rythmo_lines_count: formData.value.rythmo_lines_count
    })
    emit('project-created', project.id)
    emit('close')
  } catch (error: unknown) {
    const err = error as { response?: { data?: { message?: string } } }
    notificationService.error('Erreur', err.response?.data?.message || 'Erreur lors de la création du projet')
  } finally {
    isCreating.value = false
  }
}

// Gestion du fichier JSON sélectionné
async function onJsonFileSelected(event: Event) {
  const input = event.target as HTMLInputElement
  if (input.files && input.files.length > 0) {
    const file = input.files[0]
    importData.value.file = file
    importError.value = null
    importSuccess.value = null
    importPreview.value = null

    // Lire et parser le fichier pour prévisualisation
    try {
      const content = await file.text()
      const data = JSON.parse(content) as ProjectExportData

      // Vérifier la structure
      if (!data.export_version || !data.project) {
        importError.value = 'Format de fichier non reconnu'
        return
      }

      importPreview.value = data

      // Pré-remplir le nom si vide
      if (!importData.value.name) {
        importData.value.name = data.project.name + ' (importé)'
      }
    } catch (err) {
      importError.value = 'Impossible de lire le fichier JSON'
      console.error(err)
    }
  }
}

// Formate la taille du fichier
function formatFileSize(bytes: number): string {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}

// Gestion de l'import JSON
async function handleImport() {
  if (!importData.value.file || !importData.value.name || isImporting.value) {
    return
  }

  isImporting.value = true
  importError.value = null
  importSuccess.value = null

  try {
    const response = await importProject(importData.value.file, importData.value.name)

    importSuccess.value = 'Projet importé avec succès !'

    // Notifier le parent du succès
    emit('project-created', response.project.id)

    // Fermer le modal après 1 seconde
    setTimeout(() => {
      emit('close')
    }, 1000)
  } catch (error: unknown) {
    const err = error as { response?: { data?: { message?: string } } }
    importError.value = err.response?.data?.message || 'Erreur lors de l\'import du fichier JSON'
  } finally {
    isImporting.value = false
  }
}
</script>

<style scoped>
/* Réutilise les styles existants du modal parent */
</style>
