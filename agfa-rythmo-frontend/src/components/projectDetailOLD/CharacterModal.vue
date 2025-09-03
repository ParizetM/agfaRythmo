<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-agfa-dark rounded-lg p-6 w-full max-w-md mx-4 shadow-xl">
      <h2 class="text-xl font-semibold text-white mb-4">
        {{ editingCharacter ? 'Modifier le personnage' : 'Nouveau personnage' }}
      </h2>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <!-- Nom du personnage -->
        <div>
          <label class="block text-sm font-medium text-gray-300 mb-2">
            Nom du personnage
          </label>
          <input
            v-model="formData.name"
            type="text"
            required
            class="w-full bg-agfa-button text-white border border-gray-600 rounded px-3 py-2 focus:border-blue-500 focus:outline-none"
            placeholder="Nom du personnage"
          />
        </div>

        <!-- Couleur -->
        <div>
          <label class="block text-sm font-medium text-gray-300 mb-2">
            Couleur
          </label>
          <div class="flex items-center space-x-3">
            <input
              v-model="formData.color"
              type="color"
              class="w-12 h-10 rounded border border-gray-600 bg-agfa-button cursor-pointer"
            />
            <input
              v-model="formData.color"
              type="text"
              pattern="^#[0-9A-Fa-f]{6}$"
              class="flex-1 bg-agfa-button text-white border border-gray-600 rounded px-3 py-2 focus:border-blue-500 focus:outline-none"
              placeholder="#8455F6"
            />
          </div>
        </div>

        <!-- Section clonage (seulement en mode création) -->
        <div v-if="!editingCharacter" class="border-t border-gray-600 pt-4">
          <div class="flex items-center justify-between mb-3">
            <label class="text-sm font-medium text-gray-300">
              Cloner depuis un autre projet
            </label>
            <button
              type="button"
              @click="showCloneSection = !showCloneSection"
              class="text-blue-400 hover:text-blue-300 text-sm"
            >
              {{ showCloneSection ? 'Annuler' : 'Parcourir' }}
            </button>
          </div>

          <div v-if="showCloneSection" class="space-y-3">
            <div v-if="loadingCloneCharacters" class="text-gray-400 text-sm">
              Chargement des personnages...
            </div>
            <div v-else-if="!cloneCharacters.length" class="text-gray-400 text-sm">
              Aucun personnage disponible pour le clonage
            </div>
            <div v-else class="max-h-48 overflow-y-auto space-y-2">
              <div
                v-for="char in cloneCharacters"
                :key="char.id"
                @click="selectCharacterToClone(char)"
                class="flex items-center space-x-3 p-3 rounded border border-gray-600 hover:border-blue-500 cursor-pointer bg-agfa-button"
                :class="{ 'border-blue-500 bg-blue-900/20': selectedCloneCharacter?.id === char.id }"
              >
                <div
                  class="w-6 h-6 rounded-full border-2 border-white"
                  :style="{ backgroundColor: char.color }"
                ></div>
                <div class="flex-1">
                  <div class="text-white font-medium">{{ char.name }}</div>
                  <div class="text-gray-400 text-sm">{{ char.project?.name }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Boutons -->
        <div class="flex space-x-3 pt-4">
          <button
            type="button"
            @click="$emit('close')"
            class="flex-1 px-4 py-2 border border-gray-600 text-gray-300 rounded hover:bg-gray-700 transition-colors"
          >
            Annuler
          </button>
          <button
            type="submit"
            :disabled="isSubmitting"
            class="flex-1 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            {{ isSubmitting ? 'Enregistrement...' : (editingCharacter ? 'Modifier' : 'Créer') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, watch } from 'vue'
import { characterApi, type Character } from '../../api/characters'

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
  color: '#8455F6'
})

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
  } else {
    formData.name = ''
    formData.color = '#8455F6'
  }
}, { immediate: true })

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
}

async function handleSubmit() {
  isSubmitting.value = true

  try {
    let character: Character

    if (props.editingCharacter) {
      // Mode édition
      const response = await characterApi.update(props.editingCharacter.id, {
        name: formData.name.trim(),
        color: formData.color
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
      if (character.name !== formData.name.trim() || character.color !== formData.color) {
        const updateResponse = await characterApi.update(character.id, {
          name: formData.name.trim(),
          color: formData.color
        })
        character = updateResponse.data
      }
    } else {
      // Mode création normale
      const response = await characterApi.create({
        project_id: props.projectId,
        name: formData.name.trim(),
        color: formData.color
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
