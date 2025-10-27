<template>
  <BaseModal
    :show="show"
    :title="timecode ? 'Éditer un timecode' : 'Ajouter un timecode'"
    :subtitle="importMode ? 'Importez un fichier SRT pour ajouter plusieurs timecodes' : 'Créez ou modifiez un timecode pour la bande rythmo'"
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
          d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
        />
      </svg>
    </template>

    <template v-slot:default>
      <!-- Bouton pour basculer entre mode manuel et import SRT -->
      <div class="flex gap-2 mb-6">
        <button
          type="button"
          @click="importMode = false"
          :class="[
            'flex-1 py-2 px-4 rounded-lg font-medium transition-colors',
            timecode != null ? 'opacity-50 cursor-not-allowed' : '',
            !importMode
              ? 'bg-agfa-blue text-white'
              : 'bg-gray-700 text-gray-300 hover:bg-gray-600'
          ]"
        >
          Saisie manuelle
        </button>
        <button
          type="button"
            :disabled="timecode != null"
          @click="importMode = true"
          :class="[
            'flex-1 py-2 px-4 rounded-lg font-medium transition-colors',
            timecode != null ? 'opacity-50 cursor-not-allowed' : '',
            importMode
              ? 'bg-agfa-blue text-white'
              : 'bg-gray-700 text-gray-300 hover:bg-gray-600'
          ]"
        >
          📄 Importer SRT
        </button>
      </div>

      <!-- Mode saisie manuelle -->
      <form v-if="!importMode" id="timecode-form" @submit.prevent="handleSubmit" class="space-y-6">
        <div class="space-y-2">
          <label for="line-number" class="block text-sm font-semibold text-gray-300">
            Ligne rythmo
            <span class="text-red-400">*</span>
          </label>
          <select
            id="line-number"
            v-model="formData.line_number"
            :disabled="maxLines === 1"
            required
            :class="[
              'w-full px-4 py-3 border border-gray-600 text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all duration-300 rounded-xl',
              maxLines === 1 ? 'bg-gray-700 cursor-not-allowed opacity-75' : 'bg-agfa-bg-primary hover:border-gray-500'
            ]"
          >
            <option v-for="n in maxLines" :key="n" :value="n">
              {{ maxLines === 1 ? 'Ligne unique' : `Ligne ${n}` }}
            </option>
          </select>
        </div>

        <div class="space-y-2">
          <label for="start-time" class="block text-sm font-semibold text-gray-300">
            Début (secondes)
            <span class="text-red-400">*</span>
          </label>
          <input
            id="start-time"
            :value="formatNumber(formData.start)"
            @input="onInputNumber($event, 'start')"
            type="number"
            step="0.001"
            min="0"
            required
            class="w-full px-4 py-3 rounded-xl border border-gray-600 bg-agfa-bg-primary text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all duration-300 hover:border-gray-500"
          />
        </div>

        <div class="space-y-2">
          <label for="end-time" class="block text-sm font-semibold text-gray-300">
            Fin (secondes)
            <span class="text-red-400">*</span>
          </label>
          <input
            id="end-time"
            :value="formatNumber(formData.end)"
            @input="onInputNumber($event, 'end')"
            type="number"
            step="0.001"
            min="0"
            required
            class="w-full px-4 py-3 rounded-xl border border-gray-600 bg-agfa-bg-primary text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all duration-300 hover:border-gray-500"
          />
        </div>

        <div class="space-y-2">
          <label for="timecode-text" class="block text-sm font-semibold text-gray-300">
            Texte
            <span class="text-red-400">*</span>
          </label>
          <input
            id="timecode-text"
            v-model="formData.text"
            type="text"
            required
            placeholder="Entrez le texte du timecode"
            class="w-full px-4 py-3 rounded-xl border border-gray-600 bg-agfa-bg-primary text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all duration-300 placeholder-gray-500 hover:border-gray-500"
          />
          <div class="mt-3 p-3 bg-blue-900/20 border border-blue-500/30 rounded-lg text-sm text-gray-300">
            <p class="mb-2 flex items-start gap-2">
              <span class="text-blue-400">💡</span>
              <span><strong class="text-white">Astuce :</strong> Utilisez le caractère <code class="bg-gray-700 px-1.5 py-0.5 rounded text-blue-300">|</code> pour contrôler l'espacement et les largeurs.</span>
            </p>
            <div class="ml-6 space-y-1 text-xs">
              <p>• <code class="bg-gray-700 px-1.5 py-0.5 rounded text-gray-300">mot1|mot2</code> → espaces fixes entre les mots</p>
              <p>• <code class="bg-gray-700 px-1.5 py-0.5 rounded text-gray-300">mot1|2|mot2</code> → "mot2" sera 2× plus large que "mot1"</p>
            </div>
          </div>
        </div>
      </form>

      <!-- Mode import SRT -->
      <div v-else class="space-y-4">
        <div class="bg-blue-900/30 border border-blue-500/50 rounded-lg p-4 mb-4">
          <p class="text-sm text-blue-200">
            ℹ️ Importez un fichier de sous-titres au format SRT. Tous les sous-titres seront ajoutés à la ligne sélectionnée.
          </p>
        </div>

        <label class="block">
          <span class="text-white mb-2 block">Ligne de destination:</span>
          <select
            v-model="srtImportData.line_number"
            required
            class="w-full p-3 rounded-xl border border-gray-600 bg-agfa-bg-primary text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all duration-300 hover:border-gray-500"
          >
            <option v-for="n in maxLines" :key="n" :value="n">
              {{ maxLines === 1 ? 'Ligne unique' : `Ligne ${n}` }}
            </option>
          </select>
        </label>

        <label class="block">
          <span class="text-white mb-2 block">Personnage (optionnel):</span>
          <select
            v-model="srtImportData.character_id"
            class="w-full p-3 rounded-xl border border-gray-600 bg-agfa-bg-primary text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all duration-300 hover:border-gray-500"
          >
            <option :value="null">Aucun personnage</option>
            <option v-for="char in characters" :key="char.id" :value="char.id">
              {{ char.name }}
            </option>
          </select>
        </label>

        <label class="block">
          <span class="text-white mb-2 block">Fichier SRT:</span>
          <input
            type="file"
            accept=".srt"
            @change="onSrtFileSelected"
            class="w-full p-3 rounded-xl border border-gray-600 bg-agfa-bg-primary text-white file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:bg-gradient-to-r file:from-blue-500 file:to-purple-600 file:text-white file:cursor-pointer hover:file:from-blue-600 hover:file:to-purple-700 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all duration-300"
          />
          <p v-if="srtImportData.file" class="mt-2 text-sm text-green-400">
            ✓ {{ srtImportData.file.name }} ({{ formatFileSize(srtImportData.file.size) }})
          </p>
        </label>

        <div v-if="importError" class="bg-red-900/30 border border-red-500/50 rounded-lg p-4">
          <p class="text-sm text-red-200">❌ {{ importError }}</p>
        </div>

        <div v-if="importSuccess" class="bg-green-900/30 border border-green-500/50 rounded-lg p-4">
          <p class="text-sm text-green-200">✅ {{ importSuccess }}</p>
        </div>
      </div>
    </template>

    <template v-slot:footer>
      <!-- Footer pour mode saisie manuelle -->
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
        form="timecode-form"
        class="flex-1 px-4 sm:px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-semibold rounded-xl transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98] shadow-lg shadow-blue-500/25"
      >
        {{ timecode ? 'Modifier' : 'Créer' }}
      </button>

      <!-- Footer pour mode import SRT -->
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
        @click="handleSrtImport"
        :disabled="!srtImportData.file || isImporting"
        class="flex-1 px-4 sm:px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 disabled:from-gray-600 disabled:to-gray-700 disabled:cursor-not-allowed text-white font-semibold rounded-xl transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98] shadow-lg shadow-blue-500/25 disabled:shadow-none"
      >
        {{ isImporting ? 'Import en cours...' : 'Importer' }}
      </button>
    </template>
  </BaseModal>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import BaseModal from '../BaseModal.vue'
import { timecodeApi } from '@/api/timecodes'
import type { Character } from '@/api/characters'

// Formate un nombre à 3 décimales max
function formatNumber(val: number) {
  return val.toFixed(3);
}

// Gère l'input pour start/end et limite à 3 décimales
function onInputNumber(e: Event, field: 'start' | 'end') {
  const value = parseFloat((e.target as HTMLInputElement).value);
  if (!isNaN(value)) {
    formData.value[field] = Math.round(value * 1000) / 1000;
  } else {
    formData.value[field] = 0;
  }
}

interface TimecodeFormData {
  line_number: number
  start: number
  end: number
  text: string
}

interface TimecodeItem {
  id?: number
  project_id?: number
  start: number
  end: number
  text: string
  line_number: number
}

interface SrtImportData {
  line_number: number
  character_id: number | null
  file: File | null
}

const props = defineProps<{
  show: boolean
  timecode?: TimecodeItem | null
  maxLines: number
  defaultLineNumber?: number
  currentTime?: number
  projectId?: number
  characters?: Character[]
}>()

const emit = defineEmits<{
  (e: 'submit', data: TimecodeFormData): void
  (e: 'close'): void
  (e: 'srt-imported', count: number): void
}>()

// Mode d'affichage : false = saisie manuelle, true = import SRT
const importMode = ref(false)

// Données du formulaire de saisie manuelle
const formData = ref<TimecodeFormData>({
  line_number: 1,
  start: 0,
  end: 0,
  text: ''
})

// Données pour l'import SRT
const srtImportData = ref<SrtImportData>({
  line_number: 1,
  character_id: null,
  file: null
})

// États de l'import
const isImporting = ref(false)
const importError = ref<string | null>(null)
const importSuccess = ref<string | null>(null)

// Réinitialise le formulaire dès que le modal doit s'afficher
watch(
  [() => props.show, () => props.timecode, () => props.defaultLineNumber, () => props.currentTime],
  ([show]) => {
    if (!show) {
      // Réinitialiser le mode et les messages quand le modal se ferme
      importMode.value = false
      importError.value = null
      importSuccess.value = null
      srtImportData.value.file = null
      return
    }

    if (props.timecode) {
      // Mode édition (toujours en saisie manuelle)
      importMode.value = false
      formData.value = {
        line_number: props.timecode.line_number,
        start: Math.round(props.timecode.start * 1000) / 1000,
        end: Math.round(props.timecode.end * 1000) / 1000,
        text: props.timecode.text
      }
      return
    }

    // Mode création
    const currentTime = Math.round((props.currentTime || 0) * 1000) / 1000
    formData.value = {
      line_number: props.defaultLineNumber || 1,
      start: currentTime,
      end: Math.round((currentTime + 3) * 1000) / 1000, // 3 secondes par défaut
      text: ''
    }

    // Initialiser les données SRT avec la même ligne
    srtImportData.value.line_number = props.defaultLineNumber || 1
  },
  { immediate: true }
)

function handleSubmit() {
  emit('submit', { ...formData.value })
}

// Gestion du fichier SRT sélectionné
function onSrtFileSelected(event: Event) {
  const input = event.target as HTMLInputElement
  if (input.files && input.files.length > 0) {
    srtImportData.value.file = input.files[0]
    importError.value = null
    importSuccess.value = null
  }
}

// Formate la taille du fichier
function formatFileSize(bytes: number): string {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}

// Gestion de l'import SRT
async function handleSrtImport() {
  if (!srtImportData.value.file || !props.projectId) {
    importError.value = 'Veuillez sélectionner un fichier'
    return
  }

  isImporting.value = true
  importError.value = null
  importSuccess.value = null

  try {
    const response = await timecodeApi.importSrt(
      props.projectId,
      srtImportData.value.file,
      srtImportData.value.line_number,
      srtImportData.value.character_id
    )

    importSuccess.value = `${response.data.count} timecode(s) importé(s) avec succès !`

    // Notifier le parent du succès
    emit('srt-imported', response.data.count)

    // Fermer le modal après 2 secondes
    setTimeout(() => {
      emit('close')
    }, 2000)
  } catch (error: unknown) {
    const err = error as { response?: { data?: { message?: string } } }
    importError.value = err.response?.data?.message || 'Erreur lors de l\'import du fichier SRT'
  } finally {
    isImporting.value = false
  }
}
</script>

<style scoped>
/* Réutilise les styles existants du modal parent */
</style>
