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

            <!-- Poids de police -->
            <div class="setting-group">
              <label class="setting-label">Poids de police</label>
              <div class="font-weights-grid">
                <button
                  v-for="weight in availableWeights"
                  :key="weight"
                  @click="localSettings.fontWeight = weight"
                  :class="[
                    'font-weight-card',
                    { 'selected': localSettings.fontWeight === weight }
                  ]"
                >
                  <div class="weight-sample" :style="{ fontFamily: localSettings.fontFamily, fontWeight: weight }">
                    Aa
                  </div>
                  <div class="weight-label">
                    {{ getWeightLabel(weight) }}
                  </div>
                </button>
              </div>
              <p v-if="availableWeights.length === 0" class="text-xs text-gray-500 mt-2 text-center">
                Chargement des poids disponibles...
              </p>
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

            <!-- Style des timecodes -->
            <div class="setting-group">
              <label class="setting-label">Style des timecodes</label>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-3">
                <!-- Style par défaut -->
                <button
                  @click="localSettings.timecodeStyle = 'default'"
                  :class="[
                    'px-3 py-3 rounded-lg border-2 transition-all duration-200 text-left',
                    localSettings.timecodeStyle === 'default'
                      ? 'border-agfa-accent bg-agfa-accent bg-opacity-20 text-white shadow-lg shadow-agfa-accent/20'
                      : 'border-gray-600 bg-agfa-button text-gray-400 hover:border-gray-500 hover:bg-gray-700',
                  ]"
                >
                  <div class="font-semibold mb-1 text-sm">Fond coloré</div>
                  <div class="text-xs opacity-80">Bloc avec fond personnage</div>
                </button>

                <!-- Style couleur personnage -->
                <button
                  @click="localSettings.timecodeStyle = 'character-color'"
                  :class="[
                    'px-3 py-3 rounded-lg border-2 transition-all duration-200 text-left',
                    localSettings.timecodeStyle === 'character-color'
                      ? 'border-agfa-accent bg-agfa-accent bg-opacity-20 text-white shadow-lg shadow-agfa-accent/20'
                      : 'border-gray-600 bg-agfa-button text-gray-400 hover:border-gray-500 hover:bg-gray-700',
                  ]"
                >
                  <div class="font-semibold mb-1 text-sm">Texte coloré</div>
                  <div class="text-xs opacity-80">Texte couleur personnage, sans fond</div>
                </button>
              </div>
            </div>

            <!-- Échelle de la bande dans l'aperçu final -->
            <div class="setting-group">
              <label class="setting-label">Échelle de la bande (aperçu final)</label>
              <input
                type="range"
                v-model.number="localSettings.bandScale"
                min="1.0"
                max="3.0"
                step="0.1"
                class="w-full slider"
              />
              <div class="flex justify-between text-xs sm:text-sm text-gray-400 mt-2">
                <span>100%</span>
                <span class="font-semibold text-white">{{ (localSettings.bandScale * 100).toFixed(0) }}%</span>
                <span>300%</span>
              </div>
              <p class="text-xs text-gray-500 mt-2">
                Augmente la taille de la bande rythmo dans l'aperçu final pour une meilleure lisibilité
              </p>
            </div>


          </div>

          <!-- Colonne Aperçu -->
          <div class="space-y-4 order-1 xl:order-2 xl:sticky xl:top-8 xl:self-start" v-if="showPreview">
            <div class="text-center">
              <h3 class="text-base sm:text-lg font-semibold text-gray-300 mb-4">
                Aperçu en direct
              </h3>
            </div>

            <!-- Container de l'aperçu avec ratio vidéo -->
            <div class="preview-container">
              <!-- Mode Par-dessus (overlay) -->
              <div
                v-if="localSettings.overlayPosition === 'over'"
                class="preview-wrapper"
              >
                <div class="preview-video-container">
                  <div class="preview-video-placeholder">
                    <svg
                      class="w-12 h-12 sm:w-16 sm:h-16 text-gray-600"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-gray-500 mt-2 text-xs">Vidéo 16:9</p>
                  </div>

                  <!-- Bande par-dessus -->
                  <div
                    class="preview-band-overlay"
                    :style="{
                      height: (localSettings.bandHeight * localSettings.bandScale) + 'px',
                      backgroundColor: localSettings.bandBackgroundColor,
                      fontFamily: localSettings.fontFamily,
                      fontSize: (localSettings.fontSize * localSettings.bandScale) + 'rem',
                      fontWeight: localSettings.fontWeight,
                    }"
                  >
                    <div class="preview-band-content">
                      <!-- Ticks -->
                      <div class="preview-tick" v-for="i in 5" :key="i" :style="{ left: i * 20 + '%' }"></div>

                      <!-- Scene change -->
                      <div class="preview-scene-change" :style="{
                        backgroundColor: localSettings.sceneChangeColor,
                        left: '60%'
                      }"></div>

                      <!-- Timecodes -->
                      <div class="preview-timecode" :class="{ 'character-color-style': localSettings.timecodeStyle === 'character-color' }" :style="{
                        left: '10%',
                        width: '35%',
                        backgroundColor: localSettings.timecodeStyle === 'default' ? '#4a5568' : 'transparent',
                        border: localSettings.timecodeStyle === 'character-color' ? '1px solid #4a5568' : 'none'
                      }">
                        <span :style="{ fontWeight: localSettings.fontWeight, color: localSettings.timecodeStyle === 'character-color' ? '#4a5568' : 'white' }">Exemple</span>
                      </div>
                      <div class="preview-timecode active" :class="{ 'character-color-style': localSettings.timecodeStyle === 'character-color' }" :style="{
                        left: '50%',
                        width: '40%',
                        backgroundColor: localSettings.timecodeStyle === 'default' ? '#48bb78' : 'transparent',
                        border: localSettings.timecodeStyle === 'character-color' ? '2px solid #48bb78' : 'none'
                      }">
                        <span :style="{ fontWeight: localSettings.fontWeight, color: localSettings.timecodeStyle === 'character-color' ? '#48bb78' : 'white' }">Actif</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Mode Sous - Pleine largeur -->
              <div
                v-else-if="localSettings.overlayPosition === 'under-full'"
                class="preview-wrapper"
              >
                <div class="preview-video-placeholder">
                  <svg class="w-12 h-12 sm:w-16 sm:h-16 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <p class="text-gray-500 mt-2 text-xs">Vidéo 16:9</p>
                </div>

                <!-- Bande pleine largeur sous la vidéo -->
                <div
                  class="preview-band-below full-width"
                  :style="{
                    height: (localSettings.bandHeight * localSettings.bandScale) + 'px',
                    backgroundColor: localSettings.bandBackgroundColor,
                    fontFamily: localSettings.fontFamily,
                    fontSize: (localSettings.fontSize * localSettings.bandScale) + 'rem',
                    fontWeight: localSettings.fontWeight,
                  }"
                >
                  <div class="preview-band-content">
                    <!-- Ticks -->
                    <div class="preview-tick" v-for="i in 5" :key="i" :style="{ left: i * 20 + '%' }"></div>

                    <!-- Scene change -->
                    <div class="preview-scene-change" :style="{
                      backgroundColor: localSettings.sceneChangeColor,
                      left: '60%'
                    }"></div>

                    <!-- Timecodes -->
                    <div class="preview-timecode" :class="{ 'character-color-style': localSettings.timecodeStyle === 'character-color' }" :style="{
                      left: '10%',
                      width: '35%',
                      backgroundColor: localSettings.timecodeStyle === 'default' ? '#4a5568' : 'transparent',
                      border: localSettings.timecodeStyle === 'character-color' ? '1px solid #4a5568' : 'none'
                    }">
                      <span :style="{ fontWeight: localSettings.fontWeight, color: localSettings.timecodeStyle === 'character-color' ? '#4a5568' : 'white' }">Exemple</span>
                    </div>
                    <div class="preview-timecode active" :class="{ 'character-color-style': localSettings.timecodeStyle === 'character-color' }" :style="{
                      left: '50%',
                      width: '40%',
                      backgroundColor: localSettings.timecodeStyle === 'default' ? '#48bb78' : 'transparent',
                      border: localSettings.timecodeStyle === 'character-color' ? '2px solid #48bb78' : 'none'
                    }">
                      <span :style="{ fontWeight: localSettings.fontWeight, color: localSettings.timecodeStyle === 'character-color' ? '#48bb78' : 'white' }">Actif</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Mode Sous - Largeur vidéo -->
              <div
                v-else-if="localSettings.overlayPosition === 'under-video-width'"
                class="preview-wrapper centered"
              >
                <div class="preview-video-centered">
                  <div class="preview-video-placeholder">
                    <svg class="w-12 h-12 sm:w-16 sm:h-16 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-gray-500 mt-2 text-xs">Vidéo 16:9</p>
                  </div>

                  <!-- Bande alignée sur largeur vidéo -->
                  <div
                    class="preview-band-below video-width"
                    :style="{
                      height: (localSettings.bandHeight * localSettings.bandScale) + 'px',
                      backgroundColor: localSettings.bandBackgroundColor,
                      fontFamily: localSettings.fontFamily,
                      fontSize: (localSettings.fontSize * localSettings.bandScale) + 'rem',
                      fontWeight: localSettings.fontWeight,
                    }"
                  >
                    <div class="preview-band-content">
                      <!-- Ticks -->
                      <div class="preview-tick" v-for="i in 5" :key="i" :style="{ left: i * 20 + '%' }"></div>

                      <!-- Scene change -->
                      <div class="preview-scene-change" :style="{
                        backgroundColor: localSettings.sceneChangeColor,
                        left: '60%'
                      }"></div>

                      <!-- Timecodes -->
                      <div class="preview-timecode" :class="{ 'character-color-style': localSettings.timecodeStyle === 'character-color' }" :style="{
                        left: '10%',
                        width: '35%',
                        backgroundColor: localSettings.timecodeStyle === 'default' ? '#4a5568' : 'transparent',
                        border: localSettings.timecodeStyle === 'character-color' ? '1px solid #4a5568' : 'none'
                      }">
                        <span :style="{ fontWeight: localSettings.fontWeight, color: localSettings.timecodeStyle === 'character-color' ? '#4a5568' : 'white' }">Exemple</span>
                      </div>
                      <div class="preview-timecode active" :class="{ 'character-color-style': localSettings.timecodeStyle === 'character-color' }" :style="{
                        left: '50%',
                        width: '40%',
                        backgroundColor: localSettings.timecodeStyle === 'default' ? '#48bb78' : 'transparent',
                        border: localSettings.timecodeStyle === 'character-color' ? '2px solid #48bb78' : 'none'
                      }">
                        <span :style="{ fontWeight: localSettings.fontWeight, color: localSettings.timecodeStyle === 'character-color' ? '#48bb78' : 'white' }">Actif</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Mode Contenu 16:9 -->
              <div
                v-else-if="localSettings.overlayPosition === 'contained-16-9'"
                class="preview-wrapper contained"
              >
                <div class="preview-contained-box">
                  <div class="preview-video-placeholder small">
                    <svg class="w-8 h-8 sm:w-12 sm:h-12 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-gray-500 mt-1 text-xs">Vidéo</p>
                  </div>

                  <!-- Bande dans le conteneur 16:9 -->
                  <div
                    class="preview-band-below contained-width"
                    :style="{
                      height: (localSettings.bandHeight * localSettings.bandScale) + 'px',
                      backgroundColor: localSettings.bandBackgroundColor,
                      fontFamily: localSettings.fontFamily,
                      fontSize: (localSettings.fontSize * localSettings.bandScale) + 'rem',
                      fontWeight: localSettings.fontWeight,
                    }"
                  >
                    <div class="preview-band-content">
                      <!-- Ticks -->
                      <div class="preview-tick" v-for="i in 5" :key="i" :style="{ left: i * 20 + '%' }"></div>

                      <!-- Scene change -->
                      <div class="preview-scene-change" :style="{
                        backgroundColor: localSettings.sceneChangeColor,
                        left: '60%'
                      }"></div>

                      <!-- Timecodes -->
                      <div class="preview-timecode" :class="{ 'character-color-style': localSettings.timecodeStyle === 'character-color' }" :style="{
                        left: '10%',
                        width: '35%',
                        backgroundColor: localSettings.timecodeStyle === 'default' ? '#4a5568' : 'transparent',
                        border: localSettings.timecodeStyle === 'character-color' ? '1px solid #4a5568' : 'none'
                      }">
                        <span :style="{ fontWeight: localSettings.fontWeight, color: localSettings.timecodeStyle === 'character-color' ? '#4a5568' : 'white' }">Ex.</span>
                      </div>
                      <div class="preview-timecode active" :class="{ 'character-color-style': localSettings.timecodeStyle === 'character-color' }" :style="{
                        left: '50%',
                        width: '40%',
                        backgroundColor: localSettings.timecodeStyle === 'default' ? '#48bb78' : 'transparent',
                        border: localSettings.timecodeStyle === 'character-color' ? '2px solid #48bb78' : 'none'
                      }">
                        <span :style="{ fontWeight: localSettings.fontWeight, color: localSettings.timecodeStyle === 'character-color' ? '#48bb78' : 'white' }">Actif</span>
                      </div>
                    </div>
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
import { type GoogleFont, loadGoogleFont, preloadPopularFonts, getAvailableWeights, getWeightLabel, fetchGoogleFonts } from '../../services/googleFonts'
import { notificationService } from '../../services/notifications'
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

// Contrôle de l'affichage de la preview (lazy loading)
const showPreview = ref(false)

// Polices disponibles
const availableFonts = ref<GoogleFont[]>([])

// Poids disponibles pour la police sélectionnée
const availableWeights = ref<number[]>([])

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
      // Activer la preview après un court délai pour ne pas surcharger
      setTimeout(() => {
        showPreview.value = true
      }, 100)
    } else {
      // Désactiver la preview quand on ferme pour économiser les ressources
      showPreview.value = false
    }
  },
)

async function loadFonts() {
  // Pré-charger toutes les polices populaires
  await preloadPopularFonts()
  availableFonts.value = await fetchGoogleFonts()

  // Charger les poids disponibles pour la police sélectionnée
  await loadAvailableWeights()
}

// Charger les poids disponibles pour la police sélectionnée
async function loadAvailableWeights() {
  try {
    const weights = await getAvailableWeights(localSettings.value.fontFamily)
    availableWeights.value = weights

    // Si le poids actuel n'est pas disponible, prendre le plus proche
    if (!weights.includes(localSettings.value.fontWeight)) {
      const closest = weights.reduce((prev, curr) =>
        Math.abs(curr - localSettings.value.fontWeight) < Math.abs(prev - localSettings.value.fontWeight) ? curr : prev
      )
      localSettings.value.fontWeight = closest
    }
  } catch (error) {
    console.error('Erreur lors du chargement des poids disponibles:', error)
    availableWeights.value = [400, 700] // Fallback
  }
}

// Charger la police sélectionnée quand elle change
watch(
  () => localSettings.value.fontFamily,
  async (newFont) => {
    if (newFont) {
      try {
        await loadGoogleFont(newFont)
        // Recharger les poids disponibles pour la nouvelle police
        await loadAvailableWeights()
      } catch (error) {
        console.error(`Impossible de charger la police ${newFont}:`, error)
      }
    }
  },
)

function closeModal() {
  emit('close')
}

async function applySettings() {
  try {
    // Appliquer tous les paramètres
    for (const [key, value] of Object.entries(localSettings.value)) {
      await settingsStore.updateSetting(key as keyof typeof localSettings.value, value)
    }
    notificationService.success('Paramètres sauvegardés', 'Les paramètres ont été appliqués avec succès', 3000)
    closeModal()
  } catch (error) {
    console.error('Erreur lors de la sauvegarde des paramètres:', error)
    notificationService.error(
      'Erreur de sauvegarde',
      'Impossible de sauvegarder les paramètres. Veuillez réessayer.',
      5000
    )
  }
}

function resetToDefaults() {
  settingsStore.resetSettings()
  localSettings.value = { ...settingsStore.settings }
  notificationService.info('Paramètres réinitialisés', 'Les paramètres par défaut ont été restaurés', 3000)
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

/* Grille de poids de police */
.font-weights-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
  gap: 0.75rem;
  margin-top: 0.5rem;
}

@media (min-width: 640px) {
  .font-weights-grid {
    grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
    gap: 1rem;
  }
}

.font-weight-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 0.75rem 0.5rem;
  background: rgba(55, 65, 81, 0.3);
  border: 2px solid rgba(75, 85, 99, 0.3);
  border-radius: 0.5rem;
  cursor: pointer;
  transition: all 0.2s ease;
  min-height: 90px;
}

.font-weight-card:hover {
  background: rgba(55, 65, 81, 0.5);
  border-color: rgba(132, 85, 246, 0.5);
  transform: translateY(-2px);
}

.font-weight-card.selected {
  background: rgba(132, 85, 246, 0.2);
  border-color: #8455f6;
  box-shadow: 0 0 0 3px rgba(132, 85, 246, 0.1);
}

.weight-sample {
  font-size: 2rem;
  line-height: 1;
  color: #ffffff;
  margin-bottom: 0.5rem;
  text-align: center;
}

.weight-label {
  font-size: 0.7rem;
  color: #9ca3af;
  text-align: center;
  font-weight: 500;
  line-height: 1.2;
}

@media (min-width: 640px) {
  .weight-label {
    font-size: 0.75rem;
  }
}

.font-weight-card.selected .weight-label {
  color: #8455f6;
  font-weight: 600;
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
  box-shadow:
    0 4px 6px -1px rgba(0, 0, 0, 0.3),
    0 2px 4px -1px rgba(0, 0, 0, 0.2);
  min-height: 300px;
  display: flex;
  align-items: center;
  justify-content: center;

}

.preview-wrapper {
  width: 100%;
  display: flex;
  flex-direction: column;
}

.preview-wrapper.centered {
  align-items: center;
}

.preview-wrapper.contained {
  aspect-ratio: 16 / 9;
}

.preview-video-container {
  position: relative;
  width: 100%;
  aspect-ratio: 16 / 9;
}

.preview-video-centered {
  width: 70%;
  max-width: 600px;
  display: flex;
  flex-direction: column;
}

.preview-contained-box {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.preview-video-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #374151 0%, #2d3748 100%);
  border: 2px dashed rgba(132, 85, 246, 0.5);
  border-radius: 0.5rem;
  padding: 2rem 1rem;
  aspect-ratio: 16 / 9;
  width: 100%;
}

.preview-video-placeholder.small {
  padding: 1rem;
  flex: 1;
  min-height: 0;
}

/* Bande par-dessus la vidéo (overlay) */
.preview-band-overlay {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  width: 100%;
  opacity: 0.95;
  backdrop-filter: blur(2px);
  box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.3);
}

/* Bande sous la vidéo (below) */
.preview-band-below {
  width: 100%;
  position: relative;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
}

.preview-band-below.full-width {
  width: 100%;
}

.preview-band-below.video-width {
  width: 100%;
}

.preview-band-below.contained-width {
  width: 100%;
  flex-shrink: 0;
}

/* Contenu de la bande */
.preview-band-content {
  position: relative;
  width: 100%;
  height: 100%;
  overflow: hidden;
}

.preview-tick {
  position: absolute;
  top: 0;
  bottom: 0;
  width: 1px;
  background-color: rgba(75, 85, 99, 0.5);
  z-index: 1;
}

.preview-scene-change {
  position: absolute;
  top: 0;
  bottom: 0;
  width: 3px;
  opacity: 0.9;
  z-index: 5;
  box-shadow: 0 0 8px currentColor;
}

.preview-timecode {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  height: 60%;
  border-radius: 0.375rem;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 0.75rem;
  z-index: 3;
  transition: all 0.2s ease;
}

.preview-timecode span {
  color: white;
  font-weight: 600;
  text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  font-size: 0.85em;
}

.preview-timecode.active {
  box-shadow:
    0 0 0 2px rgba(255, 255, 255, 0.6),
    0 4px 12px rgba(72, 187, 120, 0.4);
  z-index: 4;
}

/* Style character-color dans l'aperçu */
.preview-timecode.character-color-style {
  background: transparent !important;
}

.preview-timecode.character-color-style.active {
  box-shadow: none;
  border-width: 2px !important;
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
