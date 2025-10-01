<template>
  <Transition name="modal">
    <div
      v-if="show"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
      @click="closeModal"
    >
      <div
        class="bg-agfa-dark border border-gray-600 rounded-lg shadow-xl w-[95vw] h-[90vh] mx-4 overflow-y-auto"
        @click.stop
      >
        <!-- Content -->
        <div class="p-8 relative">
          <!-- Bouton fermer -->
          <button
            @click="closeModal"
            class="absolute top-4 right-4 text-gray-400 hover:text-white transition-colors duration-200"
            title="Fermer (Échap)"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>

          <!-- Titre principal -->
          <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-white mb-2">Paramètres du Projet</h1>
            <p class="text-gray-400">Personnalisez l'apparence des bandes rythmo</p>
          </div>

          <!-- Layout en 2 colonnes : Formulaire à gauche, Aperçu à droite -->
          <div class="grid grid-cols-2 gap-8">
            <!-- Colonne Formulaire -->
            <div class="space-y-6">
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
                <div class="flex justify-between text-sm text-gray-400 mt-1">
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
                <div class="flex justify-between text-sm text-gray-400 mt-1">
                  <span>1.0rem</span>
                  <span class="font-semibold text-white">{{ localSettings.fontSize.toFixed(1) }}rem</span>
                  <span>3.5rem</span>
                </div>
              </div>

              <!-- Choix de police -->
              <div class="setting-group">
                <label class="setting-label">Police de caractères</label>
                <select
                  v-model="localSettings.fontFamily"
                  class="w-full px-4 py-2 bg-agfa-button border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-agfa-accent"
                >
                  <option
                    v-for="font in availableFonts"
                    :key="font.family"
                    :value="font.family"
                    :style="{ fontFamily: font.family }"
                  >
                    {{ font.family }} ({{ font.category }})
                  </option>
                </select>
              </div>

              <!-- Couleur du fond de la bande -->
              <div class="setting-group">
                <label class="setting-label">Couleur de fond de la bande</label>
                <div class="flex items-center gap-4">
                  <input
                    type="color"
                    v-model="localSettings.bandBackgroundColor"
                    class="color-picker"
                  />
                  <input
                    type="text"
                    v-model="localSettings.bandBackgroundColor"
                    class="flex-1 px-4 py-2 bg-agfa-button border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-agfa-accent"
                    placeholder="#202937"
                  />
                </div>
              </div>

              <!-- Couleur des changements de scène -->
              <div class="setting-group">
                <label class="setting-label">Couleur des changements de scène</label>
                <div class="flex items-center gap-4">
                  <input
                    type="color"
                    v-model="localSettings.sceneChangeColor"
                    class="color-picker"
                  />
                  <input
                    type="text"
                    v-model="localSettings.sceneChangeColor"
                    class="flex-1 px-4 py-2 bg-agfa-button border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-agfa-accent"
                    placeholder="#8455F6"
                  />
                </div>
              </div>

              <!-- Position de l'overlay -->
              <div class="setting-group">
                <label class="setting-label">Position dans l'aperçu final</label>
                <div class="flex gap-4">
                  <button
                    @click="localSettings.overlayPosition = 'under'"
                    :class="[
                      'flex-1 px-4 py-3 rounded-lg border-2 transition-all duration-200',
                      localSettings.overlayPosition === 'under'
                        ? 'border-agfa-accent bg-agfa-accent bg-opacity-20 text-white'
                        : 'border-gray-600 bg-agfa-button text-gray-400 hover:border-gray-500'
                    ]"
                  >
                    <div class="font-semibold mb-1">Sous la vidéo</div>
                    <div class="text-xs">Bande en bas</div>
                  </button>
                  <button
                    @click="localSettings.overlayPosition = 'over'"
                    :class="[
                      'flex-1 px-4 py-3 rounded-lg border-2 transition-all duration-200',
                      localSettings.overlayPosition === 'over'
                        ? 'border-agfa-accent bg-agfa-accent bg-opacity-20 text-white'
                        : 'border-gray-600 bg-agfa-button text-gray-400 hover:border-gray-500'
                    ]"
                  >
                    <div class="font-semibold mb-1">Par-dessus la vidéo</div>
                    <div class="text-xs">Overlay transparent</div>
                  </button>
                </div>
              </div>

              <!-- Boutons d'action -->
              <div class="flex gap-4 pt-4 border-t border-gray-700">
                <button
                  @click="resetToDefaults"
                  class="flex-1 px-6 py-3 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition-colors duration-200"
                >
                  Réinitialiser
                </button>
                <button
                  @click="applySettings"
                  class="flex-1 px-6 py-3 bg-agfa-accent hover:bg-purple-600 text-white rounded-lg transition-colors duration-200 font-semibold"
                >
                  Appliquer
                </button>
              </div>
            </div>

            <!-- Colonne Aperçu -->
            <div class="space-y-4">
              <div class="text-center">
                <h3 class="text-lg font-semibold text-gray-300 mb-4">Aperçu en direct</h3>
              </div>

              <!-- Container de l'aperçu avec ratio vidéo -->
              <div class="preview-container" :class="{ 'with-band-below': localSettings.overlayPosition === 'under' }">
                <div class="preview-video-area">
                  <div class="preview-video-placeholder">
                    <svg class="w-20 h-20 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-gray-500 mt-2">Vidéo de démo</p>
                  </div>

                  <!-- Bande rythmo preview -->
                  <div
                    class="preview-rythmo-band"
                    :style="{
                      height: localSettings.bandHeight + 'px',
                      backgroundColor: localSettings.bandBackgroundColor,
                      fontFamily: localSettings.fontFamily,
                      fontSize: localSettings.fontSize + 'rem',
                      opacity: localSettings.overlayPosition === 'over' ? '0.9' : '1'
                    }"
                  >
                    <!-- Ticks de temps -->
                    <div class="preview-ticks">
                      <div
                        v-for="i in 5"
                        :key="i"
                        class="preview-tick"
                        :style="{ left: (i * 20) + '%' }"
                      ></div>
                    </div>

                    <!-- Scene change preview -->
                    <div
                      class="preview-scene-change"
                      :style="{
                        backgroundColor: localSettings.sceneChangeColor,
                        left: '60%'
                      }"
                    ></div>

                    <!-- Blocs de texte preview -->
                    <div class="preview-text-blocks">
                      <div
                        class="preview-block"
                        :style="{
                          left: '10%',
                          width: '35%',
                          backgroundColor: '#4a5568'
                        }"
                      >
                        <span class="preview-text">Exemple de texte</span>
                      </div>
                      <div
                        class="preview-block active"
                        :style="{
                          left: '50%',
                          width: '40%',
                          backgroundColor: '#48bb78'
                        }"
                      >
                        <span class="preview-text">Texte actif</span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Description de l'aperçu -->
                <div class="mt-4 p-4 bg-agfa-button rounded-lg">
                  <p class="text-sm text-gray-400">
                    <strong class="text-white">Mode {{ localSettings.overlayPosition === 'under' ? 'Sous la vidéo' : 'Par-dessus' }}:</strong>
                    {{ localSettings.overlayPosition === 'under'
                      ? 'La bande rythmo sera affichée en dessous de la vidéo dans l\'aperçu final.'
                      : 'La bande rythmo sera superposée transparente sur la vidéo dans l\'aperçu final.'
                    }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import { ref, watch, onUnmounted } from 'vue'
import { useProjectSettingsStore } from '../../stores/projectSettings'
import { POPULAR_FONTS, type GoogleFont } from '../../services/googleFonts'

interface Props {
  show: boolean
}

const props = defineProps<Props>()

const emit = defineEmits<{
  close: []
}>()

const settingsStore = useProjectSettingsStore()

// Paramètres locaux (copie pour modification avant application)
const localSettings = ref({ ...settingsStore.settings })

// Polices disponibles
const availableFonts = ref<GoogleFont[]>(POPULAR_FONTS)

// Synchroniser les paramètres locaux quand la modal s'ouvre
watch(() => props.show, (isVisible) => {
  if (isVisible) {
    localSettings.value = { ...settingsStore.settings }
    // Charger les polices si nécessaire
    loadFonts()
  }
})

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

// Gestion des raccourcis clavier pour fermer le modal
function handleKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape') {
    closeModal()
  }
}

// Écouter les événements clavier uniquement quand le modal est ouvert
let keydownListener: ((event: KeyboardEvent) => void) | null = null

watch(() => props.show, (isVisible) => {
  if (isVisible) {
    keydownListener = handleKeydown
    window.addEventListener('keydown', keydownListener)
  } else if (keydownListener) {
    window.removeEventListener('keydown', keydownListener)
    keydownListener = null
  }
})

onUnmounted(() => {
  if (keydownListener) {
    window.removeEventListener('keydown', keydownListener)
  }
})
</script>

<style scoped>
.setting-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.setting-label {
  display: block;
  font-size: 0.875rem;
  font-weight: 600;
  color: #d1d5db;
  margin-bottom: 0.5rem;
}

.slider {
  height: 0.5rem;
  background-color: #374151;
  border-radius: 0.5rem;
  appearance: none;
  cursor: pointer;
}

.slider::-webkit-slider-thumb {
  appearance: none;
  width: 1.25rem;
  height: 1.25rem;
  background-color: #8455F6;
  border-radius: 9999px;
  cursor: pointer;
}

.slider::-webkit-slider-thumb:hover {
  background-color: #7c3aed;
}

.slider::-moz-range-thumb {
  width: 1.25rem;
  height: 1.25rem;
  background-color: #8455F6;
  border-radius: 9999px;
  cursor: pointer;
  border: 0;
}

.slider::-moz-range-thumb:hover {
  background-color: #7c3aed;
}

.color-picker {
  width: 4rem;
  height: 2.5rem;
  border-radius: 0.5rem;
  cursor: pointer;
  border: 2px solid #4b5563;
  background-color: transparent;
}

.color-picker::-webkit-color-swatch-wrapper {
  padding: 0;
}

.color-picker::-webkit-color-swatch {
  border: 0;
  border-radius: 0.5rem;
}

.preview-container {
  background-color: #000;
  border-radius: 0.5rem;
  overflow: hidden;
  border: 1px solid #374151;
  display: flex;
  flex-direction: column;
}

.preview-video-area {
  position: relative;
  background-color: black;
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
  border: 3px dashed #8455F6;
  transition: all 0.3s ease;
  aspect-ratio: 16 / 9;
  max-width: 100%;
  background-color: #374151;
  height: auto;
}

/* Mode "par-dessus" : le placeholder prend tout l'espace disponible */
.preview-container:not(.with-band-below) .preview-video-placeholder {
  width: 100%;
  height: 100%;
}

/* Mode "sous la vidéo" : le placeholder garde son ratio mais réduit à 70% de largeur */
.preview-container.with-band-below .preview-video-placeholder {
  width: 70%;
}

.preview-rythmo-band {
  position: relative;
  left: 0;
  right: 0;
  width: 100%;
  transition: all 0.3s ease;
  overflow: hidden;
  flex-shrink: 0;
}

/* En mode "par-dessus", la bande est en position absolue au bas */
.preview-container:not(.with-band-below) .preview-rythmo-band {
  position: absolute;
  bottom: 0;
}

/* En mode "sous", la bande suit le placeholder (flex) */
.preview-container.with-band-below .preview-rythmo-band {
  position: relative;
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
}

.preview-scene-change {
  position: absolute;
  top: 0;
  bottom: 0;
  width: 0.25rem;
  opacity: 0.8;
  z-index: 10;
}

.preview-text-blocks {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  padding: 0 1rem;
}

.preview-block {
  position: absolute;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 0.5rem;
  padding: 0 1rem;
  height: 60%;
  transition: all 0.3s ease;
}

.preview-block.active {
  box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.5);
}

.preview-text {
  color: white;
  font-weight: 600;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
}

/* Transitions pour le modal */
.modal-enter-active, .modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from, .modal-leave-to {
  opacity: 0;
}

.modal-enter-active .bg-agfa-dark,
.modal-leave-active .bg-agfa-dark {
  transition: transform 0.3s ease;
}

.modal-enter-from .bg-agfa-dark,
.modal-leave-to .bg-agfa-dark {
  transform: scale(0.9);
}
</style>
