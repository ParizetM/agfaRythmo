<template>
  <BaseModal
    :show="show"
    title="Fusionner les personnages"
    size="xl"
    @close="$emit('update:show', false)"
  >
    <template #icon>
      <div class="w-12 h-12 rounded-full flex items-center justify-center bg-gradient-to-br from-purple-500 to-pink-600">
        <UsersIcon class="h-6 w-6 text-white" />
      </div>
    </template>

    <template #subtitle>
      <p class="text-sm text-gray-400">
        Fusionnez plusieurs locuteurs détectés en un seul personnage
      </p>
    </template>

    <div class="space-y-6">
      <!-- Instruction -->
      <div class="bg-blue-500/10 border border-blue-500/20 rounded-lg p-4">
        <div class="flex items-start gap-3">
          <svg class="w-5 h-5 text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          <div>
            <p class="text-sm text-gray-300">
              <strong>Comment fusionner :</strong>
            </p>
            <ol class="text-sm text-gray-400 mt-2 space-y-1 list-decimal list-inside">
              <li>Sélectionnez les personnages à fusionner (minimum 2)</li>
              <li>Donnez un nom au personnage final</li>
              <li>Cliquez sur "Fusionner" - tous les dialogues seront réassignés</li>
            </ol>
          </div>
        </div>
      </div>

      <!-- Liste des personnages avec sélection -->
      <div>
        <h4 class="text-sm font-medium text-gray-300 mb-3">
          Personnages disponibles ({{ characters.length }})
        </h4>

        <div class="space-y-2 max-h-80 overflow-y-auto">
          <div
            v-for="character in characters"
            :key="character.id"
            class="flex items-center gap-3 p-3 rounded-lg border transition-all cursor-pointer"
            :class="[
              selectedCharacters.includes(character.id)
                ? 'bg-purple-500/20 border-purple-500/50'
                : 'bg-gray-800/50 border-gray-700 hover:border-gray-600'
            ]"
            @click="toggleCharacter(character.id)"
          >
            <!-- Checkbox -->
            <div
              class="w-5 h-5 rounded border-2 flex items-center justify-center flex-shrink-0"
              :class="[
                selectedCharacters.includes(character.id)
                  ? 'bg-purple-500 border-purple-500'
                  : 'border-gray-600'
              ]"
            >
              <svg
                v-if="selectedCharacters.includes(character.id)"
                class="w-3 h-3 text-white"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
              </svg>
            </div>

            <!-- Couleur -->
            <div
              class="w-8 h-8 rounded-full flex-shrink-0"
              :style="{ backgroundColor: character.color }"
            ></div>

            <!-- Infos -->
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-white truncate">
                {{ character.name }}
              </p>
              <p class="text-xs text-gray-400">
                {{ getDialogueCount(character.id) }} dialogue(s)
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Formulaire de fusion -->
      <div v-if="selectedCharacters.length >= 2" class="border-t border-gray-700 pt-6">
        <h4 class="text-sm font-medium text-gray-300 mb-3">
          Personnage final
        </h4>

        <div class="space-y-3">
          <!-- Nom du nouveau personnage -->
          <div>
            <label class="block text-sm text-gray-400 mb-2">
              Nom du personnage fusionné
            </label>
            <select
              v-model="mergedName"
              class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500 cursor-pointer"
            >
              <option value="" disabled>Sélectionnez un nom...</option>
              <option
                v-for="charId in selectedCharacters"
                :key="charId"
                :value="getCharacterName(charId)"
              >
                {{ getCharacterName(charId) }}
              </option>
            </select>
            <p class="text-xs text-gray-500 mt-1">
              Choisissez le nom parmi les personnages sélectionnés
            </p>
          </div>

          <!-- Résumé -->
          <div class="bg-gray-800/50 border border-gray-700 rounded-lg p-3">
            <p class="text-sm text-gray-300">
              <strong>{{ selectedCharacters.length }} personnages</strong> seront fusionnés en
              <strong>"{{ mergedName || '(sans nom)' }}"</strong>
            </p>
            <p class="text-xs text-gray-400 mt-1">
              Total : {{ totalDialogues }} dialogue(s) seront réassignés
            </p>
          </div>
        </div>
      </div>

      <!-- Message si moins de 2 sélectionnés -->
      <div v-else-if="selectedCharacters.length === 1" class="text-center py-8">
        <UsersIcon class="h-12 w-12 text-gray-600 mx-auto mb-3" />
        <p class="text-sm text-gray-400">
          Sélectionnez au moins un autre personnage pour fusionner
        </p>
      </div>

      <div v-else class="text-center py-8">
        <UsersIcon class="h-12 w-12 text-gray-600 mx-auto mb-3" />
        <p class="text-sm text-gray-400">
          Sélectionnez au moins 2 personnages pour commencer
        </p>
      </div>
    </div>

    <template #footer>
      <div class="flex items-center justify-between gap-3">
        <button class="btn-secondary" @click="$emit('update:show', false)">
          Annuler
        </button>
        <button
          :disabled="!canMerge"
          class="btn-primary disabled:opacity-50 disabled:cursor-not-allowed"
          @click="handleMerge"
        >
          Fusionner {{ selectedCharacters.length }} personnages
        </button>
      </div>
    </template>
  </BaseModal>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import BaseModal from './BaseModal.vue'
import { UsersIcon } from '@heroicons/vue/24/outline'
import type { Character } from '@/api/characters'

interface Props {
  show: boolean
  characters: Character[]
  dialogueCounts: Record<number, number>
}

interface Emits {
  (e: 'update:show', value: boolean): void
  (e: 'merge', data: { characterIds: number[]; mergedName: string }): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const selectedCharacters = ref<number[]>([])
const mergedName = ref<string>('')

const canMerge = computed(() => {
  return selectedCharacters.value.length >= 2 && mergedName.value.trim().length > 0
})

const totalDialogues = computed(() => {
  return selectedCharacters.value.reduce((sum, charId) => {
    return sum + (props.dialogueCounts[charId] || 0)
  }, 0)
})

const toggleCharacter = (charId: number) => {
  const index = selectedCharacters.value.indexOf(charId)
  if (index > -1) {
    selectedCharacters.value.splice(index, 1)
  } else {
    selectedCharacters.value.push(charId)
  }
}

const getDialogueCount = (charId: number): number => {
  return props.dialogueCounts[charId] || 0
}

const getCharacterName = (charId: number): string => {
  const character = props.characters.find(c => c.id === charId)
  return character?.name || 'Inconnu'
}

const handleMerge = () => {
  if (!canMerge.value) return

  emit('merge', {
    characterIds: [...selectedCharacters.value],
    mergedName: mergedName.value.trim()
  })

  // Reset
  selectedCharacters.value = []
  mergedName.value = ''
  emit('update:show', false)
}
</script>

<style scoped>
.btn-primary {
  padding: 0.5rem 1rem;
  background: linear-gradient(to right, rgb(147, 51, 234), rgb(219, 39, 119));
  color: white;
  border-radius: 0.5rem;
  font-weight: 500;
  transition: all 0.2s;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(147, 51, 234, 0.4);
}

.btn-secondary {
  padding: 0.5rem 1rem;
  background: rgb(55, 65, 81);
  color: white;
  border-radius: 0.5rem;
  font-weight: 500;
  transition: all 0.2s;
}

.btn-secondary:hover {
  background: rgb(75, 85, 99);
}
</style>
