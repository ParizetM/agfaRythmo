<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="true" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm"></div>

        <!-- Modal -->
        <div
          class="relative bg-gradient-to-br from-agfa-bg-secondary to-agfa-bg-tertiary rounded-3xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden border border-gray-700/50 transform transition-all flex flex-col"
        >
          <!-- Header -->
          <div class="relative px-8 pt-8 pb-6 border-b border-gray-700/50 flex-shrink-0">
            <div class="flex items-center gap-4">
              <!-- Icône badge -->
              <div
                class="w-14 h-14 rounded-2xl bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center shadow-lg"
              >
                <svg
                  class="w-8 h-8 text-white"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                  />
                </svg>
              </div>

              <!-- Titre -->
              <div class="flex-1">
                <h3 class="text-3xl font-bold text-white">
                  {{ editingCharacter ? 'Modifier le personnage' : 'Nouveau personnage' }}
                </h3>
                <p class="text-gray-400 text-sm mt-1">
                  Personnalisez les couleurs et le nom
                </p>
              </div>

              <!-- Bouton fermeture -->
              <button
                type="button"
                @click="$emit('close')"
                class="w-10 h-10 rounded-full hover:bg-gray-700/50 flex items-center justify-center transition-all duration-200 text-gray-400 hover:text-white"
              >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M6 18L18 6M6 6l12 12"
                  />
                </svg>
              </button>
            </div>
          </div>

          <!-- Body avec scroll -->
          <div class="overflow-y-auto flex-1">
            <form id="character-form" @submit.prevent="handleSubmit" class="px-8 py-6 space-y-6">
        <!-- Nom du personnage -->
        <div class="space-y-2">
          <label for="character-name" class="block text-sm font-semibold text-gray-300">
            Nom du personnage
            <span class="text-red-400">*</span>
          </label>
          <input
            id="character-name"
            v-model="formData.name"
            type="text"
            required
            class="w-full px-4 py-3 bg-agfa-bg-primary border border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition-all duration-300 text-white placeholder-gray-500 hover:border-gray-500"
            placeholder="Ex: Jean Dupont"
          />
        </div>

        <!-- Couleur de fond -->
        <div class="space-y-2">
          <label class="block text-sm font-semibold text-gray-300">
            Couleur de fond
          </label>
          <div class="space-y-3">
            <!-- Sélecteur de couleur de base -->
            <div class="flex items-center gap-3">
              <input
                v-model="baseColor"
                type="color"
                @input="onBaseColorChange"
                class="w-12 h-12 rounded-xl border-2 border-gray-600 bg-agfa-bg-primary cursor-pointer hover:border-purple-500 transition-colors"
              />
              <input
                v-model="formData.color"
                type="text"
                @input="onColorTextChange"
                class="flex-1 px-4 py-3 bg-agfa-bg-primary text-white border border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition-all duration-300 placeholder-gray-500 hover:border-gray-500"
                placeholder="rgba(132, 85, 246, 0.8)"
              />
            </div>

            <!-- Couleurs prédéfinies -->
            <div class="flex flex-wrap gap-2">
              <button
                v-for="(color, idx) in predefinedColors"
                :key="idx"
                type="button"
                @click="selectPredefinedColor(color)"
                class="w-10 h-10 rounded-lg border-2 border-gray-600 hover:border-purple-400 hover:scale-110 transition-all duration-200"
                :style="{ backgroundColor: color }"
                :title="color"
              ></button>
            </div>

            <!-- Slider d'opacité -->
            <div>
              <div class="flex items-center justify-between mb-2">
                <label class="text-sm text-gray-400">Opacité</label>
                <span class="text-sm font-semibold text-purple-400">{{ Math.round(opacity * 100) }}%</span>
              </div>
              <input
                v-model.number="opacity"
                type="range"
                min="0.1"
                max="1"
                step="0.05"
                @input="updateOpacity"
                class="w-full h-2 bg-gray-700 rounded-full appearance-none cursor-pointer slider"
              />
            </div>
          </div>
        </div>

        <!-- Couleur de texte -->
        <div class="space-y-2">
          <label class="block text-sm font-semibold text-gray-300">
            Couleur de texte
          </label>
          <div class="flex items-center gap-3">
            <input
              :value="formData.text_color || suggestTextColor(formData.color)"
              @input="formData.text_color = ($event.target as HTMLInputElement).value"
              type="color"
              class="w-12 h-12 rounded-xl border-2 border-gray-600 bg-agfa-bg-primary cursor-pointer hover:border-purple-500 transition-colors"
            />
            <input
              v-model="formData.text_color"
              type="text"
              pattern="^#[0-9A-Fa-f]{6}$"
              class="flex-1 px-4 py-3 bg-agfa-bg-primary text-white border border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition-all duration-300 placeholder-gray-500 hover:border-gray-500"
              :placeholder="suggestTextColor(formData.color)"
            />
            <button
              type="button"
              @click="autoSelectTextColor"
              class="px-4 py-3 bg-gradient-to-r from-purple-500 to-pink-600 text-white rounded-xl text-sm font-medium hover:from-purple-600 hover:to-pink-700 transition-all duration-300 transform hover:scale-105"
              title="Couleur automatique"
            >
              Auto
            </button>
          </div>
          <p class="text-xs text-gray-400 mt-2">
            Laissez vide pour une sélection automatique basée sur la couleur de fond
          </p>
        </div>

        <!-- Aperçu -->
        <div class="space-y-2">
          <label class="block text-sm font-semibold text-gray-300">
            Aperçu
          </label>
          <div
            class="px-6 py-4 rounded-xl text-center font-semibold text-lg shadow-lg"
            :style="{
              backgroundColor: formData.color,
              color: formData.text_color || suggestTextColor(formData.color)
            }"
          >
            {{ formData.name || 'Nom du personnage' }}
          </div>
        </div>

        <!-- Section clonage (seulement en mode création) -->
        <div v-if="!editingCharacter" class="border-t border-gray-700/50 pt-6 space-y-3">
          <div class="flex items-center justify-between mb-3">
            <label class="text-sm font-semibold text-gray-300">
              Cloner depuis un autre projet
            </label>
            <button
              type="button"
              @click="showCloneSection = !showCloneSection"
              class="text-purple-400 hover:text-purple-300 text-sm font-medium transition-colors"
            >
              {{ showCloneSection ? 'Annuler' : 'Parcourir' }}
            </button>
          </div>

          <div v-if="showCloneSection" class="space-y-3">
            <div v-if="loadingCloneCharacters" class="text-gray-400 text-sm text-center py-4">
              <div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-purple-500 mr-2"></div>
              Chargement des personnages...
            </div>
            <div v-else-if="!cloneCharacters.length" class="text-gray-400 text-sm text-center py-4 bg-agfa-bg-primary rounded-xl">
              Aucun personnage disponible pour le clonage
            </div>
            <div v-else class="max-h-64 overflow-y-auto space-y-2 pr-2">
              <div
                v-for="char in cloneCharacters"
                :key="char.id"
                @click="selectCharacterToClone(char)"
                class="flex items-center gap-3 p-4 rounded-xl border-2 border-gray-600 hover:border-purple-500 cursor-pointer bg-agfa-bg-primary transition-all duration-300 transform hover:scale-[1.02]"
                :class="{ 'border-purple-500 bg-purple-900/20 ring-2 ring-purple-500/50': selectedCloneCharacter?.id === char.id }"
              >
                <div
                  class="w-8 h-8 rounded-full border-2 border-white shadow-lg"
                  :style="{ backgroundColor: char.color }"
                ></div>
                <div class="flex-1">
                  <div class="text-white font-semibold">{{ char.name }}</div>
                  <div class="text-gray-400 text-sm">{{ char.project?.name }}</div>
                </div>
                <svg
                  v-if="selectedCloneCharacter?.id === char.id"
                  class="w-6 h-6 text-purple-400"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                >
                  <path
                    fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                    clip-rule="evenodd"
                  />
                </svg>
              </div>
            </div>
          </div>
        </div>

            </form>
          </div>

          <!-- Footer avec boutons -->
          <div class="px-8 pb-8 flex gap-4 flex-shrink-0 border-t border-gray-700/50 pt-6">
            <button
              type="button"
              @click="$emit('close')"
              :disabled="isSubmitting"
              class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 disabled:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-semibold rounded-xl transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98]"
            >
              Annuler
            </button>
            <button
              type="submit"
              form="character-form"
              :disabled="isSubmitting"
              class="flex-1 px-6 py-3 bg-gradient-to-r from-purple-500 to-pink-600 hover:from-purple-600 hover:to-pink-700 disabled:from-gray-600 disabled:to-gray-700 disabled:cursor-not-allowed text-white font-semibold rounded-xl transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98] shadow-lg shadow-purple-500/25 disabled:shadow-none"
            >
              {{ isSubmitting ? 'Enregistrement...' : (editingCharacter ? 'Modifier' : 'Créer') }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, watch } from 'vue'
import { characterApi, type Character } from '../../api/characters'
import { suggestTextColor, predefinedColors, toRGBA } from '../../utils/colorUtils'

const props = defineProps<{
  projectId: number
  editingCharacter?: Character | null
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'saved', character: Character): void
}>()

const formData = reactive({
  name: '',
  color: 'rgba(132, 85, 246, 0.8)',
  text_color: null as string | null
})

// Variables pour la gestion des couleurs RGBA
const baseColor = ref('#8455F6')
const opacity = ref(0.8)

const isSubmitting = ref(false)
const showCloneSection = ref(false)
const loadingCloneCharacters = ref(false)
const cloneCharacters = ref<Character[]>([])
const selectedCloneCharacter = ref<Character | null>(null)

// Initialiser le formulaire
watch(() => props.editingCharacter, (character) => {
  if (character) {
    formData.name = character.name
    formData.color = character.color
    formData.text_color = character.text_color || null
    // Mettre à jour baseColor et opacity depuis la couleur du personnage
    onColorTextChange()
  } else {
    formData.name = ''
    formData.color = 'rgba(132, 85, 246, 0.8)'
    formData.text_color = null
    baseColor.value = '#8455F6'
    opacity.value = 0.8
  }
}, { immediate: true })

// Fonction appelée quand la couleur de base change
function onBaseColorChange() {
  updateColorFromBase()
}

// Fonction appelée quand le texte de couleur change
function onColorTextChange() {
  // Tenter d'extraire la couleur de base et l'opacité depuis le texte
  try {
    const colorMatch = formData.color.match(/rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*(?:\s*,\s*([\d.]+))?\s*\)/)
    if (colorMatch) {
      const r = parseInt(colorMatch[1])
      const g = parseInt(colorMatch[2])
      const b = parseInt(colorMatch[3])
      const a = colorMatch[4] ? parseFloat(colorMatch[4]) : 1

      const hexColor = `#${r.toString(16).padStart(2, '0')}${g.toString(16).padStart(2, '0')}${b.toString(16).padStart(2, '0')}`
      // S'assurer que la couleur hexadécimale est valide
      if (hexColor.match(/^#[0-9A-Fa-f]{6}$/)) {
        baseColor.value = hexColor
        opacity.value = a
      }
    }
  } catch {
    // Ignorer les erreurs de parsing et garder les valeurs par défaut
  }
}

// Fonction pour sélectionner une couleur prédéfinie
function selectPredefinedColor(color: string) {
  formData.color = color
  onColorTextChange() // Mettre à jour baseColor et opacity
}

// Fonction pour mettre à jour l'opacité
function updateOpacity() {
  updateColorFromBase()
}

// Fonction pour mettre à jour la couleur RGBA basée sur baseColor et opacity
function updateColorFromBase() {
  const rgba = toRGBA(baseColor.value, opacity.value)
  if (rgba) {
    formData.color = rgba
  }
}

// Fonction pour sélectionner automatiquement la couleur de texte
function autoSelectTextColor() {
  formData.text_color = suggestTextColor(formData.color)
}

// Charger les personnages disponibles pour le clonage
async function loadCloneCharacters() {
  if (props.editingCharacter) return // Pas de clonage en mode édition

  loadingCloneCharacters.value = true
  try {
    const response = await characterApi.getForCloning(props.projectId)
    cloneCharacters.value = response.data.characters
  } catch (error) {
    console.error('Erreur lors du chargement des personnages:', error)
    cloneCharacters.value = []
  } finally {
    loadingCloneCharacters.value = false
  }
}

function selectCharacterToClone(character: Character) {
  selectedCloneCharacter.value = character
  formData.name = character.name
  formData.color = character.color
  formData.text_color = character.text_color || null
  // Mettre à jour baseColor et opacity depuis la couleur clonée
  onColorTextChange()
}

async function handleSubmit() {
  isSubmitting.value = true

  try {
    let character: Character

    if (props.editingCharacter) {
      // Mode édition
      const response = await characterApi.update(props.editingCharacter.id, {
        name: formData.name.trim(),
        color: formData.color,
        text_color: formData.text_color
      })
      character = response.data
    } else if (selectedCloneCharacter.value) {
      // Mode clonage
      const response = await characterApi.clone({
        source_character_id: selectedCloneCharacter.value.id,
        target_project_id: props.projectId
      })
      character = response.data
      // Mettre à jour avec les données du formulaire si elles ont été modifiées
      if (character.name !== formData.name.trim() || character.color !== formData.color || character.text_color !== formData.text_color) {
        const updateResponse = await characterApi.update(character.id, {
          name: formData.name.trim(),
          color: formData.color,
          text_color: formData.text_color
        })
        character = updateResponse.data
      }
    } else {
      // Mode création normale
      const response = await characterApi.create({
        project_id: props.projectId,
        name: formData.name.trim(),
        color: formData.color,
        text_color: formData.text_color
      })
      character = response.data
    }

    emit('saved', character)
  } catch (error) {
    console.error('Erreur lors de la sauvegarde:', error)
    // TODO: Afficher message d'erreur à l'utilisateur
  } finally {
    isSubmitting.value = false
  }
}

onMounted(() => {
  loadCloneCharacters()
})
</script>

<style scoped>
/* Animations de transition du modal */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-active > div:last-child,
.modal-leave-active > div:last-child {
  transition: transform 0.3s ease, opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-from > div:last-child,
.modal-leave-to > div:last-child {
  transform: scale(0.9);
  opacity: 0;
}

/* Styles pour le slider d'opacité */
.slider {
  -webkit-appearance: none;
  appearance: none;
  background: #374151;
}

.slider::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  height: 20px;
  width: 20px;
  border-radius: 50%;
  background: linear-gradient(135deg, #a855f7, #ec4899);
  cursor: pointer;
  border: 2px solid #ffffff;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
  transition: transform 0.2s;
}

.slider::-webkit-slider-thumb:hover {
  transform: scale(1.2);
}

.slider::-moz-range-thumb {
  height: 16px;
  width: 16px;
  border-radius: 50%;
  background: linear-gradient(135deg, #a855f7, #ec4899);
  cursor: pointer;
  border: 2px solid #ffffff;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
  transition: transform 0.2s;
}

.slider::-moz-range-thumb:hover {
  transform: scale(1.2);
}
</style>
