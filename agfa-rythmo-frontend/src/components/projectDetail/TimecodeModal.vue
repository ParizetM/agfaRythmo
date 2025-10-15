<template>
  <div
    v-if="show"
    class="fixed inset-0 bg-opacity-50 flex items-center justify-center z-50"
  >
    <div class="bg-agfa-dark text-white rounded-xl p-8 min-w-96 max-w-2xl shadow-2xl max-h-[90vh] overflow-y-auto">
      <h4 class="text-xl font-bold mb-6">
        {{ timecode ? 'Éditer' : 'Ajouter' }} un timecode
      </h4>

      <!-- Bouton pour basculer entre mode manuel et import SRT -->
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
          Saisie manuelle
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
          📄 Importer SRT
        </button>
      </div>

      <!-- Mode saisie manuelle -->
      <form v-if="!importMode" @submit.prevent="handleSubmit" class="space-y-4">
        <label class="block">
          <span class="text-white mb-2 block">Ligne rythmo:</span>
          <select
            v-model="formData.line_number"
            :disabled="maxLines === 1"
            required
            :class="[
              'w-full p-3 rounded-lg border border-gray-600 text-white focus:ring-2 focus:ring-agfa-blue focus:border-transparent outline-none transition-all duration-300',
              maxLines === 1 ? 'bg-gray-700 cursor-not-allowed opacity-75' : 'bg-gray-800'
            ]"
          >
            <option v-for="n in maxLines" :key="n" :value="n">
              {{ maxLines === 1 ? 'Ligne unique' : `Ligne ${n}` }}
            </option>
          </select>
        </label>

        <label class="block">
          <span class="text-white mb-2 block">Début (s):</span>
          <input
            :value="formatNumber(formData.start)"
            @input="onInputNumber($event, 'start')"
            type="number"
            step="0.001"
            min="0"
            required
            class="w-full p-3 rounded-lg border border-gray-600 bg-gray-800 text-white focus:ring-2 focus:ring-agfa-blue focus:border-transparent outline-none transition-all duration-300"
          />
        </label>

        <label class="block">
          <span class="text-white mb-2 block">Fin (s):</span>
          <input
            :value="formatNumber(formData.end)"
            @input="onInputNumber($event, 'end')"
            type="number"
            step="0.001"
            min="0"
            required
            class="w-full p-3 rounded-lg border border-gray-600 bg-gray-800 text-white focus:ring-2 focus:ring-agfa-blue focus:border-transparent outline-none transition-all duration-300"
          />
        </label>

        <label class="block">
          <span class="text-white mb-2 block">Texte:</span>
          <input
            v-model="formData.text"
            type="text"
            required
            placeholder="Entrez le texte du timecode"
            class="w-full p-3 rounded-lg border border-gray-600 bg-gray-800 text-white focus:ring-2 focus:ring-agfa-blue focus:border-transparent outline-none transition-all duration-300"
          />
          <div class="mt-2 text-sm text-gray-400">
            <p class="mb-1">💡 <strong>Astuce :</strong> Utilisez le caractère <code class="bg-gray-700 px-1 rounded">|</code> pour contrôler l'espacement et les largeurs.</p>
            <p class="text-xs mb-1">• <code class="bg-gray-700 px-1 rounded">mot1|mot2</code> → espaces fixes entre les mots</p>
            <p class="text-xs">• <code class="bg-gray-700 px-1 rounded">mot1|2|mot2</code> → "mot2" sera 2× plus large que "mot1"</p>
          </div>
        </label>

                <div class="flex gap-4 pt-4">
          <button
            type="submit"
            class="flex-1 bg-agfa-blue hover:bg-agfa-blue-hover text-white border-none rounded-lg py-3 px-5 cursor-pointer text-base font-medium transition-colors duration-300"
          >
            {{ timecode ? 'Modifier' : 'Créer' }}
          </button>
          <button
            type="button"
            @click="$emit('close')"
            class="flex-1 bg-gray-600 hover:bg-gray-700 text-white border-none rounded-lg py-3 px-5 cursor-pointer text-base font-medium transition-colors duration-300"
          >
            Annuler
          </button>
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
            class="w-full p-3 rounded-lg border border-gray-600 bg-gray-800 text-white focus:ring-2 focus:ring-agfa-blue focus:border-transparent outline-none transition-all duration-300"
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
            class="w-full p-3 rounded-lg border border-gray-600 bg-gray-800 text-white focus:ring-2 focus:ring-agfa-blue focus:border-transparent outline-none transition-all duration-300"
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
            class="w-full p-3 rounded-lg border border-gray-600 bg-gray-800 text-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-agfa-blue file:text-white file:cursor-pointer hover:file:bg-agfa-blue-hover focus:ring-2 focus:ring-agfa-blue focus:border-transparent outline-none transition-all duration-300"
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

        <div class="flex gap-4 pt-4">
          <button
            type="button"
            @click="handleSrtImport"
            :disabled="!srtImportData.file || isImporting"
            class="flex-1 bg-agfa-blue hover:bg-agfa-blue-hover text-white border-none rounded-lg py-3 px-5 cursor-pointer text-base font-medium transition-colors duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ isImporting ? 'Import en cours...' : 'Importer' }}
          </button>
          <button
            type="button"
            @click="$emit('close')"
            class="flex-1 bg-gray-600 hover:bg-gray-700 text-white border-none rounded-lg py-3 px-5 cursor-pointer text-base font-medium transition-colors duration-300"
          >
            Annuler
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
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
