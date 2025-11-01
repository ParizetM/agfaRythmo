<template>
  <BaseModal
    :show="show"
    title="✅ Extraction terminée"
    size="2xl"
    @close="$emit('update:show', false)"
  >
    <template #icon>
      <div class="w-12 h-12 rounded-full flex items-center justify-center bg-gradient-to-br from-green-500 to-emerald-600">
        <CheckCircleIcon class="h-6 w-6 text-white" />
      </div>
    </template>

    <template #subtitle>
      <div class="flex items-center gap-4 text-sm text-gray-400">
        <span>{{ result.timecodes_count }} dialogues extraits</span>
        <span>•</span>
        <span>{{ result.characters_count }} locuteurs détectés</span>
        <span v-if="result.source_language">•</span>
        <span v-if="result.source_language" class="flex items-center gap-1">
          <GlobeAltIcon class="h-4 w-4" />
          {{ getLanguageName(result.source_language) }}
        </span>
      </div>
    </template>

    <div class="space-y-6">
      <!-- Résumé -->
      <div class="bg-green-500/10 border border-green-500/20 rounded-lg p-4">
        <div class="flex items-start gap-3">
          <CheckCircleIcon class="h-5 w-5 text-green-500 flex-shrink-0 mt-0.5" />
          <div class="flex-1">
            <h4 class="font-medium text-white mb-1">
              Extraction réussie !
            </h4>
            <p class="text-sm text-gray-300">
              Les dialogues ont été automatiquement transcrits et les personnages créés.
              Vous pouvez maintenant éditer les noms des locuteurs ou traduire les dialogues.
            </p>
          </div>
        </div>
      </div>

      <!-- Liste des personnages créés -->
      <div v-if="characters.length > 0">
        <div class="flex items-center justify-between mb-3">
          <h4 class="text-sm font-medium text-gray-300 flex items-center gap-2">
            <UsersIcon class="h-4 w-4" />
            Personnages créés ({{ characters.length }})
          </h4>
          <button
            v-if="characters.length > 1"
            class="text-xs px-3 py-1.5 bg-purple-600/20 hover:bg-purple-600/30 text-purple-400 rounded-lg border border-purple-500/30 transition-colors flex items-center gap-1.5"
            @click="$emit('merge-characters')"
          >
            <UsersIcon class="h-3.5 w-3.5" />
            Fusionner personnages
          </button>
        </div>
        <div class="space-y-2 max-h-60 overflow-y-auto">
          <div
            v-for="character in characters"
            :key="character.id"
            class="flex items-center gap-3 bg-gray-800/50 rounded-lg p-3 border border-gray-700/50"
          >
            <!-- Couleur -->
            <div
              class="w-8 h-8 rounded-full flex-shrink-0"
              :style="{ backgroundColor: character.color }"
            />

            <!-- Nom -->
            <div class="flex-1 min-w-0">
              <input
                v-model="character.name"
                type="text"
                class="w-full bg-transparent border-none text-white placeholder-gray-500 focus:outline-none focus:ring-0"
                placeholder="Nom du personnage..."
                @blur="updateCharacterName(character)"
              />
            </div>

            <!-- Nombre de dialogues -->
            <div class="text-xs text-gray-400 flex-shrink-0">
              {{ getDialogueCount(character.id) }} dialogues
            </div>
          </div>
        </div>
      </div>

      <!-- Option de traduction -->
      <div v-if="canTranslate" class="border-t border-gray-700 pt-6">
        <h4 class="text-sm font-medium text-gray-300 mb-3 flex items-center gap-2">
          <GlobeAltIcon class="h-4 w-4" />
          Traduction automatique
        </h4>

        <div class="bg-blue-500/10 border border-blue-500/20 rounded-lg p-4">
          <div class="flex items-start gap-3">
            <GlobeAltIcon class="h-5 w-5 text-blue-400 flex-shrink-0 mt-0.5" />
            <div class="flex-1">
              <p class="text-sm text-gray-300 mb-3">
                <span v-if="result.source_language">
                  Langue détectée : <strong>{{ getLanguageName(result.source_language) }}</strong>.
                </span>
                <span v-else>
                  Langue source non détectée automatiquement.
                </span>
                Voulez-vous traduire les dialogues maintenant ?
              </p>

              <!-- Sélecteurs langues -->
              <div class="space-y-3">
                <!-- Langue source (toujours affichée) -->
                <div class="flex items-center gap-3">
                  <label class="text-sm text-gray-400 w-28 flex-shrink-0">
                    Langue source :
                  </label>
                  <select
                    v-model="sourceLanguage"
                    class="flex-1 bg-gray-800 border border-gray-600 rounded-lg px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="auto">Détection automatique</option>
                    <option value="fr">Français</option>
                    <option value="en">English</option>
                    <option value="es">Español</option>
                    <option value="de">Deutsch</option>
                    <option value="it">Italiano</option>
                    <option value="pt">Português</option>
                    <option value="zh">中文</option>
                    <option value="ja">日本語</option>
                    <option value="ko">한국어</option>
                    <option value="ar">العربية</option>
                  </select>
                </div>

                <!-- Langue cible -->
                <div class="flex items-center gap-3">
                  <label class="text-sm text-gray-400 w-28 flex-shrink-0">
                    Traduire vers :
                  </label>
                  <select
                    v-model="targetLanguage"
                    class="flex-1 bg-gray-800 border border-gray-600 rounded-lg px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="">Sélectionner...</option>
                    <option value="fr">Français</option>
                    <option value="en">English</option>
                    <option value="es">Español</option>
                    <option value="de">Deutsch</option>
                    <option value="it">Italiano</option>
                    <option value="pt">Português</option>
                    <option value="zh">中文</option>
                    <option value="ja">日本語</option>
                    <option value="ko">한국어</option>
                    <option value="ar">العربية</option>
                  </select>
                  <button
                    :disabled="!canStartTranslation"
                    class="btn-primary flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                    @click="handleTranslateNow"
                  >
                    <GlobeAltIcon class="h-4 w-4" />
                    Traduire
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <template #footer>
      <div class="flex items-center justify-end gap-3">
        <button class="btn-secondary" @click="$emit('update:show', false)">
          Fermer
        </button>
      </div>
    </template>
  </BaseModal>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import BaseModal from './BaseModal.vue'
import {
  CheckCircleIcon,
  GlobeAltIcon,
  UsersIcon,
} from '@heroicons/vue/24/outline'
import { characterApi, type Character, type UpdateCharacterData } from '@/api/characters'
import { timecodeApi, type Timecode } from '@/api/timecodes'

interface ExtractionResult {
  timecodes_count: number
  characters_count: number
  source_language?: string
}

interface Props {
  show: boolean
  result: ExtractionResult
  projectId: number
  translationEnabled?: boolean
}

interface Emits {
  (e: 'update:show', value: boolean): void
  (e: 'translate', options: { source_language: string; target_language: string }): void
  (e: 'merge-characters'): void
}

const props = withDefaults(defineProps<Props>(), {
  translationEnabled: false,
})

const emit = defineEmits<Emits>()

const characters = ref<Character[]>([])
const sourceLanguage = ref<string>('auto')
const targetLanguage = ref<string>('')
const dialogueCounts = ref<Record<number, number>>({})

const canTranslate = computed(() => {
  return props.translationEnabled
})

const canStartTranslation = computed(() => {
  // Juste besoin de la langue cible (source peut être "auto")
  return !!targetLanguage.value
})

const getLanguageName = (code: string): string => {
  const languages: Record<string, string> = {
    en: 'Anglais',
    fr: 'Français',
    es: 'Espagnol',
    de: 'Allemand',
    it: 'Italien',
    pt: 'Portugais',
    ru: 'Russe',
    zh: 'Chinois',
    ja: 'Japonais',
    ko: 'Coréen',
    ar: 'Arabe',
    hi: 'Hindi',
  }
  return languages[code] || code.toUpperCase()
}

const updateCharacterName = async (character: Character) => {
  try {
    const data: UpdateCharacterData = {
      name: character.name,
      color: character.color,
      text_color: character.text_color,
    }
    await characterApi.update(character.id, data)
  } catch (error) {
    console.error('Erreur lors de la mise à jour du personnage:', error)
  }
}

const getDialogueCount = (characterId: number): number => {
  return dialogueCounts.value[characterId] || 0
}

const loadCharacters = async () => {
  try {
    // Charger les personnages du projet
    const response = await characterApi.getAll(props.projectId)
    characters.value = response.data.characters || []

    // Compter les dialogues par personnage pour toutes les lignes
    const counts: Record<number, number> = {}

    // Obtenir TOUS les timecodes du projet (toutes lignes confondues)
    const timecodesResponse = await timecodeApi.getAll(props.projectId)
    const timecodes: Timecode[] = timecodesResponse.data.timecodes || []

    console.log('📊 DialogueExtractionResultModal: Loaded timecodes:', timecodes.length)

    // Compter les occurrences de chaque personnage
    timecodes.forEach((timecode: Timecode) => {
      if (timecode.character_id) {
        counts[timecode.character_id] = (counts[timecode.character_id] || 0) + 1
      }
    })

    console.log('📊 DialogueExtractionResultModal: Dialogue counts:', counts)
    dialogueCounts.value = counts
  } catch (error) {
    console.error('Erreur lors du chargement des personnages:', error)
  }
}

const handleTranslateNow = () => {
  if (!targetLanguage.value) return

  // Déterminer la langue source
  let sourceLang: string

  // Si l'utilisateur a sélectionné "auto" ou si sourceLanguage est vide
  if (sourceLanguage.value === 'auto' || sourceLanguage.value === '') {
    // Utiliser la langue détectée lors de l'extraction OU "auto" pour détection côté serveur
    sourceLang = props.result.source_language || 'auto'
  } else {
    // Utiliser la sélection manuelle de l'utilisateur
    sourceLang = sourceLanguage.value
  }

  emit('translate', {
    source_language: sourceLang,
    target_language: targetLanguage.value,
  })
  emit('update:show', false)
}

// Charger les données quand la modal s'ouvre
watch(() => props.show, (newValue, oldValue) => {
  console.log('📊 DialogueExtractionResultModal: show changed from', oldValue, 'to', newValue)
  console.trace('Stack trace:')
  if (newValue && props.projectId) {
    console.log('📊 Loading characters for project', props.projectId)
    loadCharacters()
  }
})

// Exposer la fonction loadCharacters pour permettre le rechargement depuis le parent
defineExpose({
  loadCharacters
})

// Log au montage du composant
console.log('🔧 DialogueExtractionResultModal: Component mounted with props:', {
  show: props.show,
  result: props.result,
  projectId: props.projectId
})
</script>

<style scoped>
.icon-gradient {
  width: 3rem;
  height: 3rem;
  border-radius: 9999px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-primary {
  padding: 0.5rem 1rem;
  background: linear-gradient(to right, rgb(37, 99, 235), rgb(147, 51, 234));
  color: white;
  border-radius: 0.5rem;
  font-weight: 500;
  transition: all 0.2s;
}

.btn-primary:hover {
  background: linear-gradient(to right, rgb(29, 78, 216), rgb(126, 34, 206));
}

.btn-secondary {
  padding: 0.5rem 1rem;
  background-color: rgb(55, 65, 81);
  color: white;
  border-radius: 0.5rem;
  font-weight: 500;
  transition: all 0.2s;
}

.btn-secondary:hover {
  background-color: rgb(75, 85, 99);
}
</style>
