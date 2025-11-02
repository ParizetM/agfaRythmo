<template>
  <div class="relative" ref="dropdownRef">
    <!-- Bouton export avec dropdown -->
    <button
      @click="toggleDropdown"
      class="bg-transparent text-gray-300 hover:text-white border border-gray-600 hover:border-gray-400 rounded-lg p-2 cursor-pointer transition-colors duration-300"
      :title="isOpen ? 'Fermer le menu' : 'Options d\'export'"
    >
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
        ></path>
      </svg>
    </button>

    <!-- Menu dropdown -->
    <transition
      enter-active-class="transition ease-out duration-100"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <div
        v-if="isOpen"
        class="absolute right-0 mt-2 w-64 rounded-lg shadow-xl backdrop-blur-sm bg-black/30 rounded-r-lg border border-gray-700/5 z-50 overflow-hidden"
      >
        <div class="py-1">
          <!-- Header -->
          <div class="px-4 py-2 border-b border-gray-700">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Options d'export</p>
          </div>

          <!-- Option 1: Projet complet (.agfa) -->
          <button
            @click="handleExport('project')"
            class="w-full text-left px-4 py-3 hover:bg-gray-700 transition-colors duration-150 flex items-start gap-3"
          >
            <svg class="w-5 h-5 text-blue-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <div class="flex-1">
              <p class="text-sm font-medium text-white">Projet complet (.agfa)</p>
              <p class="text-xs text-gray-400 mt-0.5">Timecodes, personnages, settings</p>
            </div>
          </button>

          <!-- Séparateur -->
          <div class="border-t border-gray-700 my-1"></div>

          <!-- Header médias -->
          <div class="px-4 py-2">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Médias</p>
          </div>

          <!-- Option 2: Vidéo source -->
          <button
            @click="handleExport('video')"
            class="w-full text-left px-4 py-3 hover:bg-gray-700 transition-colors duration-150 flex items-start gap-3"
          >
            <svg class="w-5 h-5 text-pink-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
            <div class="flex-1">
              <p class="text-sm font-medium text-white">Vidéo source</p>
              <p class="text-xs text-gray-400 mt-0.5">Télécharger la vidéo originale</p>
            </div>
          </button>

          <!-- Option 3: Audio complet -->
          <button
            @click="handleExport('audio')"
            class="w-full text-left px-4 py-3 hover:bg-gray-700 transition-colors duration-150 flex items-start gap-3"
          >
            <svg class="w-5 h-5 text-green-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
            </svg>
            <div class="flex-1">
              <p class="text-sm font-medium text-white">Audio complet</p>
              <p class="text-xs text-gray-400 mt-0.5">Bande son complète de la vidéo</p>
            </div>
          </button>

          <!-- Option 4: Instrumental (sans voix) -->
          <button
            v-if="hasInstrumental"
            @click="handleExport('instrumental')"
            class="w-full text-left px-4 py-3 hover:bg-gray-700 transition-colors duration-150 flex items-start gap-3"
          >
            <svg class="w-5 h-5 text-violet-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" class="opacity-50" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
            </svg>
            <div class="flex-1">
              <p class="text-sm font-medium text-white">Instrumental (sans voix)</p>
              <p class="text-xs text-gray-400 mt-0.5">Musique et effets sonores uniquement</p>
            </div>
          </button>

          <!-- Option pour générer l'instrumental si pas encore fait -->
          <button
            v-else
            @click="handleGenerateInstrumental"
            class="w-full text-left px-4 py-3 hover:bg-gray-700 transition-colors duration-150 flex items-start gap-3"
          >
            <svg class="w-5 h-5 text-violet-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <div class="flex-1">
              <p class="text-sm font-medium text-white">Générer l'instrumental</p>
              <p class="text-xs text-gray-400 mt-0.5">Créer la version sans voix pour l'exporter</p>
            </div>
          </button>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'

interface Props {
  projectName: string
  videoPath?: string
  instrumentalPath?: string | null
}

interface Emits {
  (e: 'export', type: 'project' | 'video' | 'audio' | 'instrumental'): void
  (e: 'generate-instrumental'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const isOpen = ref(false)
const dropdownRef = ref<HTMLElement | null>(null)

// Computed : vérifie si instrumental est disponible (reactif aux changements de props)
const hasInstrumental = computed(() => !!props.instrumentalPath)

const toggleDropdown = () => {
  isOpen.value = !isOpen.value
}

const handleExport = (type: 'project' | 'video' | 'audio' | 'instrumental') => {
  emit('export', type)
  isOpen.value = false
}

const handleGenerateInstrumental = () => {
  emit('generate-instrumental')
  isOpen.value = false
}

// Fermer le dropdown si clic à l'extérieur
const handleClickOutside = (event: MouseEvent) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
    isOpen.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>
