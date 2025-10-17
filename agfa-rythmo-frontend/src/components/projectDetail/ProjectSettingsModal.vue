<template>
  <BaseModal
    :show="show"
    title="Paramètres du Projet"
    subtitle="Personnalisez l'apparence des bandes rythmo"
    size="full"
    max-height="90vh"
    @close="closeModal"
  >
    <template v-slot:icon>
      <svg
        class="w-6 h-6 sm:w-8 sm:h-8 text-white"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"
        />
      </svg>
    </template>

    <template v-slot:default>
      <div class="space-y-6 sm:space-y-8">
        <!-- Onglets -->
        <div class="tabs-container mb-6 sm:mb-8">
          <div class="flex gap-2 min-w-max">
            <button
              @click="activeTab = 'settings'"
              :class="['tab', { 'tab-active': activeTab === 'settings' }]"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"
                ></path>
              </svg>
              <span class="hidden sm:inline">Paramètres</span>
              <span class="sm:hidden">Réglages</span>
            </button>
            <button
              @click="activeTab = 'presets'"
              :class="['tab', { 'tab-active': activeTab === 'presets' }]"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"
                ></path>
              </svg>
              <span class="hidden sm:inline">Mes Presets ({{ userPresets.length }}/5)</span>
              <span class="sm:hidden">Presets ({{ userPresets.length }})</span>
            </button>
          </div>
        </div>

        <!-- Contenu de l'onglet Paramètres -->
        <div
          v-show="activeTab === 'settings'"
          class="grid grid-cols-1 xl:grid-cols-2 gap-6 lg:gap-8"
        >
          <!-- Colonne Formulaire -->
          <div class="space-y-5 sm:space-y-6 order-2 xl:order-1 p-2">
            <!-- Position de l'overlay -->
            <div class="setting-group">
              <label class="setting-label">Position dans l'aperçu final</label>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-3">
                <!-- Mode Over -->
                <button
                  @click="localSettings.overlayPosition = 'over'"
                  :class="[
                    'px-3 py-3 rounded-lg border-2 transition-all duration-200 text-left',
                    localSettings.overlayPosition === 'over'
                      ? 'border-agfa-accent bg-agfa-accent bg-opacity-20 text-white shadow-lg shadow-agfa-accent/20'
                      : 'border-gray-600 bg-agfa-button text-gray-400 hover:border-gray-500 hover:bg-gray-700',
                  ]"
                >
                  <div class="font-semibold mb-1 text-sm">Par-dessus la vidéo</div>
                  <div class="text-xs opacity-80">Overlay transparent en bas</div>
                </button>

                <!-- Mode Under Full -->
                <button
                  @click="localSettings.overlayPosition = 'under-full'"
                  :class="[
                    'px-3 py-3 rounded-lg border-2 transition-all duration-200 text-left',
                    localSettings.overlayPosition === 'under-full'
                      ? 'border-agfa-accent bg-agfa-accent bg-opacity-20 text-white shadow-lg shadow-agfa-accent/20'
                      : 'border-gray-600 bg-agfa-button text-gray-400 hover:border-gray-500 hover:bg-gray-700',
                  ]"
                >
                  <div class="font-semibold mb-1 text-sm">Sous - Pleine largeur</div>
                  <div class="text-xs opacity-80">Bande 100% largeur écran</div>
                </button>

                <!-- Mode Under Video Width -->
                <button
                  @click="localSettings.overlayPosition = 'under-video-width'"
                  :class="[
                    'px-3 py-3 rounded-lg border-2 transition-all duration-200 text-left',
                    localSettings.overlayPosition === 'under-video-width'
                      ? 'border-agfa-accent bg-agfa-accent bg-opacity-20 text-white shadow-lg shadow-agfa-accent/20'
                      : 'border-gray-600 bg-agfa-button text-gray-400 hover:border-gray-500 hover:bg-gray-700',
                  ]"
                >
                  <div class="font-semibold mb-1 text-sm">Sous - Largeur vidéo</div>
                  <div class="text-xs opacity-80">Bande alignée sur vidéo</div>
                </button>

                <!-- Mode Contained 16:9 -->
                <button
                  @click="localSettings.overlayPosition = 'contained-16-9'"
                  :class="[
                    'px-3 py-3 rounded-lg border-2 transition-all duration-200 text-left',
                    localSettings.overlayPosition === 'contained-16-9'
                      ? 'border-agfa-accent bg-agfa-accent bg-opacity-20 text-white shadow-lg shadow-agfa-accent/20'
                      : 'border-gray-600 bg-agfa-button text-gray-400 hover:border-gray-500 hover:bg-gray-700',
                  ]"
                >
                  <div class="font-semibold mb-1 text-sm">Contenu 16:9</div>
                  <div class="text-xs opacity-80">Vidéo + bande en 16:9 fixe</div>
                </button>
              </div>
            </div>
            <!-- Hauteur de la bande -->
            <div class="setting-group">
              <label class="setting-label">Hauteur de la bande (px)</label>
              <input
                type="range"
                v-model.number="localSettings.bandHeight"
                min="40"
                max="200"
                step="10"
                class="w-full slider"
              />
              <div class="flex justify-between text-xs sm:text-sm text-gray-400 mt-2">
                <span>40px</span>
                <span class="font-semibold text-white">{{ localSettings.bandHeight }}px</span>
                <span>200px</span>
              </div>
            </div>

            <!-- Taille de police -->
            <div class="setting-group">
              <label class="setting-label">Taille de police (rem)</label>
              <input
                type="range"
                v-model.number="localSettings.fontSize"
                min="1.0"
                max="3.5"
                step="0.1"
                class="w-full slider"
              />
              <div class="flex justify-between text-xs sm:text-sm text-gray-400 mt-2">
                <span>1.0rem</span>
                <span class="font-semibold text-white"
                  >{{ localSettings.fontSize.toFixed(1) }}rem</span
                >
                <span>3.5rem</span>
              </div>
            </div>

            <!-- Choix de police -->
            <div class="setting-group">
              <label class="setting-label">Police de caractères</label>
              <select
                v-model="localSettings.fontFamily"
                class="w-full px-3 sm:px-4 py-2 sm:py-2.5 bg-agfa-button border border-gray-600 rounded-lg text-white text-sm sm:text-base focus:outline-none focus:ring-2 focus:ring-agfa-accent focus:border-transparent transition-all"
              >
                <option
                  v-for="font in availableFonts"
                  :key="font.family"
                  :value="font.family"
                  :style="{ fontFamily: font.family, backgroundColor: '#384152', color: '#ffffff' }"
                >
                  {{ font.family }} ({{ font.category }})
                </option>
              </select>
            </div>

            <!-- Couleur du fond de la bande -->
            <div class="setting-group">
              <label class="setting-label">Couleur de fond de la bande</label>
              <div class="flex items-center gap-3 sm:gap-4">
                <input
                  type="color"
                  v-model="localSettings.bandBackgroundColor"
                  class="color-picker"
                />
                <input
                  type="text"
                  v-model="localSettings.bandBackgroundColor"
                  class="flex-1 px-3 sm:px-4 py-2 bg-agfa-button border border-gray-600 rounded-lg text-white text-sm sm:text-base focus:outline-none focus:ring-2 focus:ring-agfa-accent focus:border-transparent transition-all"
                  placeholder="#202937"
                />
              </div>
            </div>

            <!-- Couleur des changements de scène -->
            <div class="setting-group">
              <label class="setting-label">Couleur des changements de scène</label>
              <div class="flex items-center gap-3 sm:gap-4">
                <input type="color" v-model="localSettings.sceneChangeColor" class="color-picker" />
                <input
                  type="text"
                  v-model="localSettings.sceneChangeColor"
                  class="flex-1 px-3 sm:px-4 py-2 bg-agfa-button border border-gray-600 rounded-lg text-white text-sm sm:text-base focus:outline-none focus:ring-2 focus:ring-agfa-accent focus:border-transparent transition-all"
                  placeholder="#8455F6"
                />
              </div>
            </div>


          </div>

          <!-- Colonne Aperçu -->
          <div class="space-y-4 order-1 xl:order-2">
            <div class="text-center">
              <h3 class="text-base sm:text-lg font-semibold text-gray-300 mb-4">
                Aperçu en direct
              </h3>
            </div>

            <!-- Container de l'aperçu avec ratio vidéo -->
            <div
              class="preview-container"
              :class="{
                'with-band-below':
                  localSettings.overlayPosition === 'under-full' ||
                  localSettings.overlayPosition === 'under-video-width',
                'contained-mode': localSettings.overlayPosition === 'contained-16-9',
              }"
              :style="{ '--band-height': localSettings.bandHeight + 'px' }"
            >
              <div class="preview-video-area">
                <div class="preview-video-placeholder">
                  <svg
                    class="w-12 h-12 sm:w-16 sm:h-16 lg:w-20 lg:h-20 text-gray-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"
                    ></path>
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                    ></path>
                  </svg>
                  <p class="text-gray-500 mt-2 text-xs sm:text-sm">Vidéo de démo</p>
                </div>

                <!-- Bande rythmo preview -->
                <div
                  class="preview-rythmo-band"
                  :class="{
                    'mode-over': localSettings.overlayPosition === 'over',
                    'mode-under-full': localSettings.overlayPosition === 'under-full',
                    'mode-under-video': localSettings.overlayPosition === 'under-video-width',
                    'mode-contained': localSettings.overlayPosition === 'contained-16-9',
                  }"
                  :style="{
                    height: localSettings.bandHeight + 'px',
                    backgroundColor: localSettings.bandBackgroundColor,
                    fontFamily: localSettings.fontFamily,
                    fontSize: localSettings.fontSize + 'rem',
                    opacity: localSettings.overlayPosition === 'over' ? '0.9' : '1',
                  }"
                >
                  <!-- Ticks de temps -->
                  <div class="preview-ticks">
                    <div
                      v-for="i in 5"
                      :key="i"
                      class="preview-tick"
                      :style="{ left: i * 20 + '%' }"
                    ></div>
                  </div>

                  <!-- Scene change preview -->
                  <div
                    class="preview-scene-change"
                    :style="{
                      backgroundColor: localSettings.sceneChangeColor,
                      left: '60%',
                    }"
                  ></div>

                  <!-- Blocs de texte preview -->
                  <div class="preview-text-blocks">
                    <div
                      class="preview-block"
                      :style="{
                        left: '10%',
                        width: '35%',
                        backgroundColor: '#4a5568',
                      }"
                    >
                      <span class="preview-text">Exemple de texte</span>
                    </div>
                    <div
                      class="preview-block active"
                      :style="{
                        left: '50%',
                        width: '40%',
                        backgroundColor: '#48bb78',
                      }"
                    >
                      <span class="preview-text">Texte actif</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Description de l'aperçu -->
              <div class="mt-4 p-3 sm:p-4 bg-agfa-button rounded-lg border border-gray-700">
                <p class="text-xs sm:text-sm text-gray-400">
                  <strong class="text-white">
                    {{
                      localSettings.overlayPosition === 'over'
                        ? 'Par-dessus la vidéo'
                        : localSettings.overlayPosition === 'under-full'
                          ? 'Sous - Pleine largeur'
                          : localSettings.overlayPosition === 'under-video-width'
                            ? 'Sous - Largeur vidéo'
                            : 'Contenu 16:9'
                    }}:
                  </strong>
                  {{
                    localSettings.overlayPosition === 'over'
                      ? 'La bande rythmo sera superposée transparente en bas de la vidéo.'
                      : localSettings.overlayPosition === 'under-full'
                        ? "La bande rythmo sera affichée sous la vidéo avec une largeur de 100% de l'écran."
                        : localSettings.overlayPosition === 'under-video-width'
                          ? 'La bande rythmo sera affichée sous la vidéo, alignée sur sa largeur.'
                          : 'La vidéo et la bande rythmo seront contenues dans un ratio 16:9 fixe.'
                  }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Contenu de l'onglet Presets -->
        <div v-show="activeTab === 'presets'">
          <PresetsManager @preset-applied="onPresetApplied" />
        </div>
      </div>
    </template>
    <template v-slot:footer>
      <!-- Boutons d'action -->
            <div class="flex flex-col sm:flex-row gap-3 sm:gap-4 pt-4 w-full">
              <button
                @click="resetToDefaults"
                class="flex-1 px-4 sm:px-6 py-2.5 sm:py-3 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition-all duration-200 font-medium text-sm sm:text-base"
              >
                Réinitialiser
              </button>
              <button
                @click="applySettings"
                class="flex-1 px-4 sm:px-6 py-2.5 sm:py-3 bg-agfa-accent hover:bg-purple-600 text-white rounded-lg transition-all duration-200 font-semibold text-sm sm:text-base shadow-lg shadow-agfa-accent/30"
              >
                Appliquer
              </button>
            </div>
    </template>
  </BaseModal>
</template>

<script setup lang="ts">
import { ref, watch, onUnmounted, computed } from 'vue'
import { useProjectSettingsStore } from '../../stores/projectSettings'
import { POPULAR_FONTS, type GoogleFont } from '../../services/googleFonts'
import PresetsManager from './PresetsManager.vue'
import BaseModal from '../BaseModal.vue'

interface Props {
  show: boolean
}

const props = defineProps<Props>()

const emit = defineEmits<{
  close: []
}>()

const settingsStore = useProjectSettingsStore()

// Onglet actif ('settings' ou 'presets')
const activeTab = ref<'settings' | 'presets'>('settings')

// Paramètres locaux (copie pour modification avant application)
const localSettings = ref({ ...settingsStore.settings })

// Polices disponibles
const availableFonts = ref<GoogleFont[]>(POPULAR_FONTS)

// Presets utilisateur
const userPresets = computed(() => settingsStore.userPresets)

// Synchroniser les paramètres locaux quand la modal s'ouvre
watch(
  () => props.show,
  (isVisible) => {
    if (isVisible) {
      activeTab.value = 'settings'
      localSettings.value = { ...settingsStore.settings }
      // Charger les polices et les presets
      loadFonts()
      settingsStore.loadUserPresets()
    }
  },
)

async function loadFonts() {
  // Pour l'instant, on utilise les polices populaires
  // On pourrait charger plus de polices via l'API Google Fonts
  availableFonts.value = POPULAR_FONTS
}

function closeModal() {
  emit('close')
}

function applySettings() {
  // Appliquer tous les paramètres
  Object.entries(localSettings.value).forEach(([key, value]) => {
    settingsStore.updateSetting(key as keyof typeof localSettings.value, value)
  })
  closeModal()
}

function resetToDefaults() {
  settingsStore.resetSettings()
  localSettings.value = { ...settingsStore.settings }
}

// Quand un preset est appliqué, recharger les paramètres locaux
function onPresetApplied() {
  localSettings.value = { ...settingsStore.settings }
  activeTab.value = 'settings'
}

// Gestion des raccourcis clavier pour fermer le modal
function handleKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape') {
    closeModal()
  }
}

// Écouter les événements clavier uniquement quand le modal est ouvert
let keydownListener: ((event: KeyboardEvent) => void) | null = null

watch(
  () => props.show,
  (isVisible) => {
    if (isVisible) {
      keydownListener = handleKeydown
      window.addEventListener('keydown', keydownListener)
    } else if (keydownListener) {
      window.removeEventListener('keydown', keydownListener)
      keydownListener = null
    }
  },
)

onUnmounted(() => {
  if (keydownListener) {
    window.removeEventListener('keydown', keydownListener)
  }
})
</script>

<style scoped>
/* Conteneur des onglets */
.tabs-container {
  border-bottom: 2px solid #374151;
  margin-bottom: 0;
  -webkit-overflow-scrolling: touch;
}

.tabs-container::-webkit-scrollbar {
  height: 4px;
}

.tabs-container::-webkit-scrollbar-track {
  background: #1f2937;
  border-radius: 2px;
}

.tabs-container::-webkit-scrollbar-thumb {
  background: #4b5563;
  border-radius: 2px;
}

.tab {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  background-color: transparent;
  color: #9ca3af;
  border: none;
  border-bottom: 3px solid transparent;
  cursor: pointer;
  font-size: 0.875rem;
  font-weight: 600;
  transition: all 0.2s ease;
  margin-bottom: -2px;
  white-space: nowrap;
}

@media (min-width: 640px) {
  .tab {
    padding: 0.75rem 1.5rem;
  }
}

.tab:hover {
  color: #d1d5db;
  background-color: rgba(132, 85, 246, 0.1);
}

.tab-active {
  color: #8455f6;
  border-bottom-color: #8455f6;
}

.tab-active:hover {
  color: #9d6fff;
}

/* Groupes de paramètres */
.setting-group {
  background-color: rgba(55, 65, 81, 0.3);
  padding: 1rem;
  border-radius: 0.75rem;
  border: 1px solid rgba(75, 85, 99, 0.3);
  transition: all 0.2s ease;
}

.setting-group:hover {
  background-color: rgba(55, 65, 81, 0.5);
  border-color: rgba(75, 85, 99, 0.5);
}

@media (min-width: 640px) {
  .setting-group {
    padding: 1.25rem;
  }
}

.setting-label {
  display: block;
  font-size: 0.875rem;
  font-weight: 600;
  color: #e5e7eb;
  margin-bottom: 0.75rem;
  letter-spacing: 0.025em;
}

/* Sliders */
.slider {
  height: 0.5rem;
  background: linear-gradient(to right, #374151 0%, #4b5563 100%);
  border-radius: 0.5rem;
  appearance: none;
  cursor: pointer;
  transition: all 0.2s ease;
}

.slider:hover {
  background: linear-gradient(to right, #4b5563 0%, #6b7280 100%);
}

.slider::-webkit-slider-thumb {
  appearance: none;
  width: 1.25rem;
  height: 1.25rem;
  background: linear-gradient(135deg, #8455f6 0%, #9d6fff 100%);
  border-radius: 9999px;
  cursor: pointer;
  box-shadow: 0 2px 8px rgba(132, 85, 246, 0.4);
  transition: all 0.2s ease;
}

.slider::-webkit-slider-thumb:hover {
  transform: scale(1.1);
  box-shadow: 0 4px 12px rgba(132, 85, 246, 0.6);
}

.slider::-moz-range-thumb {
  width: 1.25rem;
  height: 1.25rem;
  background: linear-gradient(135deg, #8455f6 0%, #9d6fff 100%);
  border-radius: 9999px;
  cursor: pointer;
  border: 0;
  box-shadow: 0 2px 8px rgba(132, 85, 246, 0.4);
  transition: all 0.2s ease;
}

.slider::-moz-range-thumb:hover {
  transform: scale(1.1);
  box-shadow: 0 4px 12px rgba(132, 85, 246, 0.6);
}

/* Color picker */
.color-picker {
  width: 3.5rem;
  height: 2.5rem;
  border-radius: 0.5rem;
  cursor: pointer;
  border: 2px solid #4b5563;
  background-color: transparent;
  transition: all 0.2s ease;
  flex-shrink: 0;
}

@media (min-width: 640px) {
  .color-picker {
    width: 4rem;
  }
}

.color-picker:hover {
  border-color: #8455f6;
  box-shadow: 0 0 0 3px rgba(132, 85, 246, 0.1);
}

.color-picker::-webkit-color-swatch-wrapper {
  padding: 0;
}

.color-picker::-webkit-color-swatch {
  border: 0;
  border-radius: 0.375rem;
}

/* Conteneur de prévisualisation */
.preview-container {
  background-color: #000;
  border-radius: 0.75rem;
  overflow: hidden;
  border: 1px solid #374151;
  display: flex;
  flex-direction: column;
  box-shadow:
    0 4px 6px -1px rgba(0, 0, 0, 0.3),
    0 2px 4px -1px rgba(0, 0, 0, 0.2);
}

.preview-video-area {
  position: relative;
  background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%);
  flex-shrink: 0;
  transition: all 0.3s ease;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-start;
}

/* Mode "par-dessus" : la zone vidéo garde son aspect ratio 16:9 */
.preview-container:not(.with-band-below) .preview-video-area {
  aspect-ratio: 16 / 9;
}

/* Mode "sous la vidéo" : la zone vidéo s'adapte au contenu */
.preview-container.with-band-below .preview-video-area {
  min-height: 0;
}

.preview-video-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  border: 3px dashed #8455f6;
  transition: all 0.3s ease;
  aspect-ratio: 16 / 9;
  max-width: 100%;
  background: linear-gradient(135deg, #374151 0%, #2d3748 100%);
  height: auto;
  border-radius: 0.5rem;
}

/* Mode "par-dessus" : le placeholder prend tout l'espace disponible */
.preview-container:not(.with-band-below) .preview-video-placeholder {
  width: 100%;
  height: 100%;
  border-radius: 0;
}

/* Mode "sous la vidéo" : le placeholder garde son ratio mais réduit à 70% de largeur */
.preview-container.with-band-below .preview-video-placeholder {
  width: 70%;
  margin: 1rem;
}

/* Mode "contained 16:9" : tout le container a un ratio 16:9 */
.preview-container.contained-mode {
  aspect-ratio: 16 / 9;
  max-width: 100%;
  max-height: 100%;
}

/* En mode contained, la zone vidéo doit laisser de la place pour la bande */
.preview-container.contained-mode .preview-video-area {
  flex: 1;
  min-height: 0;
  overflow: hidden;
  width: 100%;
  height: 100%;
}

.preview-container.contained-mode .preview-video-placeholder {
  width: 100%;
  height: calc(100% - var(--band-height, 80px));
  max-height: calc(100% - var(--band-height, 80px));
  aspect-ratio: initial;
  flex-shrink: 0;
  margin: 0;
  border-radius: 0;
}

/* Bande rythmo */
.preview-rythmo-band {
  position: relative;
  left: 0;
  right: 0;
  width: 100%;
  transition: all 0.3s ease;
  overflow: hidden;
  flex-shrink: 0;
  box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.3);
}

/* En mode "par-dessus", la bande est en position absolue au bas */
.preview-rythmo-band.mode-over {
  position: absolute;
  bottom: 0;
}

/* En mode "sous", la bande suit le placeholder (flex) */
.preview-rythmo-band.mode-under-full,
.preview-rythmo-band.mode-under-video,
.preview-rythmo-band.mode-contained {
  position: relative;
}

/* Mode under-video-width : bande alignée sur 70% comme la vidéo */
.preview-container.with-band-below .preview-rythmo-band.mode-under-video {
  width: 70%;
  margin: 0 auto;
  border-radius: 0.5rem;
  margin-bottom: 1rem;
}

/* Mode contained : la bande prend toute la largeur du container */
.preview-container.contained-mode .preview-rythmo-band.mode-contained {
  width: 100%;
}

.preview-ticks {
  position: absolute;
  inset: 0;
  pointer-events: none;
}

.preview-tick {
  position: absolute;
  top: 0;
  bottom: 0;
  width: 1px;
  background-color: #4b5563;
  opacity: 0.5;
}

.preview-scene-change {
  position: absolute;
  top: 0;
  bottom: 0;
  width: 0.25rem;
  opacity: 0.8;
  z-index: 10;
  box-shadow: 0 0 8px currentColor;
}

.preview-text-blocks {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  padding: 0 0.75rem;
}

@media (min-width: 640px) {
  .preview-text-blocks {
    padding: 0 1rem;
  }
}

.preview-block {
  position: absolute;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 0.5rem;
  padding: 0 0.75rem;
  height: 60%;
  transition: all 0.3s ease;
}

@media (min-width: 640px) {
  .preview-block {
    padding: 0 1rem;
  }
}

.preview-block.active {
  box-shadow:
    0 0 0 2px rgba(255, 255, 255, 0.5),
    0 4px 12px rgba(72, 187, 120, 0.3);
  transform: translateY(-2px);
}

.preview-text {
  color: white;
  font-weight: 600;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.7);
  font-size: 0.75rem;
}

@media (min-width: 640px) {
  .preview-text {
    font-size: 0.875rem;
  }
}

/* Transitions pour le modal */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-active > div > div,
.modal-leave-active > div > div {
  transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.modal-enter-from > div > div,
.modal-leave-to > div > div {
  transform: scale(0.95) translateY(10px);
}
</style>
