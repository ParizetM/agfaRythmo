<template>
  <BaseModal
    :show="show"
    title="Supprimer le projet"
    subtitle="Cette action est irréversible"
    size="md"
    icon-gradient="from-red-500 to-red-700"
    @close="$emit('close')"
  >
    <template v-slot:icon>
      <svg class="w-6 h-6 sm:w-8 sm:h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
        />
      </svg>
    </template>

    <template v-slot:default>
      <div class="space-y-4">
        <!-- Message de confirmation -->
        <div class="bg-red-900/20 border border-red-500/30 rounded-xl p-4">
          <p class="text-gray-200 text-sm leading-relaxed">
            Êtes-vous sûr de vouloir supprimer ce projet ?
          </p>
          <p class="text-red-300 text-sm font-semibold mt-2">
            ⚠️ Toutes les données associées (timecodes, personnages, changements de plan) seront définitivement perdues.
          </p>
        </div>

        <!-- Détails du projet -->
        <div v-if="project" class="bg-agfa-bg-primary rounded-xl p-4 border border-gray-700">
          <h4 class="text-white font-semibold mb-2">Projet à supprimer :</h4>
          <p class="text-gray-300 font-medium">{{ project.name }}</p>
          <p v-if="project.description" class="text-gray-400 text-sm mt-1">
            {{ project.description }}
          </p>
        </div>
      </div>
    </template>

    <template v-slot:footer>
      <button
        type="button"
        @click="$emit('close')"
        class="flex-1 px-4 sm:px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-xl transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98]"
      >
        Annuler
      </button>
      <button
        type="button"
        @click="$emit('confirm')"
        class="flex-1 px-4 sm:px-6 py-3 bg-gradient-to-r from-red-500 to-red-700 hover:from-red-600 hover:to-red-800 text-white font-semibold rounded-xl transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98] shadow-lg shadow-red-500/25"
      >
        Oui, supprimer
      </button>
    </template>
  </BaseModal>
</template>

<script setup lang="ts">
import BaseModal from './BaseModal.vue'

interface Project {
  id: number
  name: string
  description?: string
}

interface Props {
  show: boolean
  project: Project | null
}

defineProps<Props>()

defineEmits<{
  (e: 'close'): void
  (e: 'confirm'): void
}>()
</script>
