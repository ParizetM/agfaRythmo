<template>
  <div class="flex flex-col items-center bg-gray-900 min-h-screen p-0 m-0">
    <!-- Header Panel -->
    <header class="w-full flex items-center justify-between bg-agfa-dark shadow-lg py-2 px-2 md:px-6 lg:px-12">
      <button
        class="flex items-center gap-2 bg-transparent border-none text-white text-lg font-medium cursor-pointer px-3 py-1 rounded-lg hover:bg-gray-800 transition-colors duration-300"
        @click="goBack"
        title="Retour aux projets"
      >
        <BackSvg class="w-5 h-5" />
        <span class="hidden md:inline"
        >Projets</span>
      </button>

      <div class="flex-1 text-white text-left mx-6 min-w-0 hidden sm:block">
        <h1 class="text-3xl font-bold mb-1 whitespace-nowrap overflow-hidden text-ellipsis">
          {{ project?.name }}
        </h1>
        <p class="text-lg text-gray-300 m-0 whitespace-nowrap overflow-hidden text-ellipsis">
          {{ project?.description }}
        </p>
      </div>

      <div class="flex items-center gap-3">
        <!-- Bouton instrumental (mute vocals) -->
        <InstrumentalButton
          ref="instrumentalButtonRef"
          v-if="project && project.video_path && canManageProject"
          :project="project"
          :muteVocals="muteVocals"
          @update:muteVocals="muteVocals = $event"
          @update:project="project = $event"
        />

        <!-- Bouton paramètres du projet -->
        <button
          class="bg-transparent text-gray-300 hover:text-white border border-gray-600 hover:border-gray-400 rounded-lg p-2 cursor-pointer transition-colors duration-300"
          @click="showProjectSettings = true"
          title="Paramètres du projet"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
            ></path>
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
            ></path>
          </svg>
        </button>

        <!-- Bouton raccourcis clavier -->
        <button
          class="bg-transparent text-gray-300 hover:text-white border border-gray-600 hover:border-gray-400 rounded-lg p-2 cursor-pointer transition-colors duration-300"
          @click="showKeyboardShortcuts = true"
          title="Raccourcis clavier (Cmd + ?)"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
            ></path>
          </svg>
        </button>

        <!-- Bouton collaborateurs -->
        <button
          v-if="project && canManageProject"
          class="bg-transparent text-gray-300 hover:text-white border border-gray-600 hover:border-gray-400 rounded-lg p-2 cursor-pointer transition-colors duration-300"
          @click="showCollaboratorsModal = true"
          title="Gérer les collaborateurs"
        >
          <UsersIcon class="w-5 h-5" />
        </button>

        <!-- Bouton IA - Ouvre le menu des fonctionnalités IA -->
        <button
          v-if="project && project.video_path && canManageProject"
          class="flex items-center gap-2 border rounded-lg px-3 py-2 font-semibold cursor-pointer transition-all duration-300 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white border-purple-500 shadow-lg hover:shadow-xl hover:scale-105 active:scale-95"
          @click="showAiMenu = true"
          title="Fonctionnalités IA"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
          </svg>
          <span class="hidden md:inline">IA</span>
        </button>

        <!-- Bouton export JSON -->
        <ExportDropdown
          v-if="project"
          :projectName="project.name"
          :videoPath="project.video_path"
          :instrumentalPath="project.instrumental_audio_path"
          @export="handleExport"
          @generate-instrumental="handleGenerateInstrumental"
        />

        <!-- Bouton aperçu final -->
        <button
          v-if="project && project.video_path && compatibleTimecodes.length > 0"
          class="bg-agfa-blue hover:bg-agfa-blue-hover text-white border-none rounded-lg px-2 py-2 text-base font-bold cursor-pointer shadow-lg transition-colors duration-300"
          @click="goToFinalPreview"
          title="Aperçu final plein écran"
        >

          <span class="hidden md:inline"
        >Aperçu</span>
           final
        </button>
      </div>
    </header>

    <!-- Message d'erreur d'accès -->
    <div
      v-if="project && !hasProjectAccess"
      class="w-full max-w-4xl mx-auto p-6 bg-red-600 text-white rounded-lg mb-6"
    >
      <div class="flex items-center justify-center">
        <div class="text-center">
          <h3 class="text-lg font-semibold mb-2">Accès refusé</h3>
          <p class="mb-4">Vous n'avez pas les droits pour accéder à ce projet.</p>
          <button
            @click="goBack"
            class="bg-white text-red-600 px-4 py-2 rounded-md font-medium hover:bg-gray-100"
          >
            Retour aux projets
          </button>
        </div>
      </div>
    </div>

    <!-- Message d'erreur de chargement général -->
    <div
      v-if="loadingError && !loading"
      class="w-full max-w-4xl mx-auto p-6 bg-red-600 text-white rounded-lg mb-6"
    >
      <div class="flex items-center justify-center">
        <div class="text-center">
          <svg class="w-16 h-16 mx-auto mb-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
          <h3 class="text-lg font-semibold mb-2">Erreur de chargement</h3>
          <p class="mb-4">{{ loadingError }}</p>
          <div class="flex gap-3 justify-center">
            <button
              @click="retryLoading"
              class="bg-white text-red-600 px-4 py-2 rounded-md font-medium hover:bg-gray-100 transition-colors"
            >
              Réessayer
            </button>
            <button
              @click="goBack"
              class="bg-transparent border-2 border-white text-white px-4 py-2 rounded-md font-medium hover:bg-white/10 transition-colors"
            >
              Retour aux projets
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Grid -->
    <div
      v-else
      class="w-full flex flex-row items-start justify-center lg:flex-col overflow-x-hidden overflow-y-visible h-fit"
    >
      <!-- Overlay Left Panel - Timecodes -->
      <!-- Overlay Right Panel - Scene Changes -->
      <div>
        <button
          class="fixed top-[40px] right-0 z-50 text-white border border-gray-600 rounded-l-lg w-7 h-12 flex items-center justify-center cursor-pointer shadow-lg text-lg p-0 hover:bg-agfa-blue transition-colors duration-300 backdrop-blur-sm bg-black/30"
          @click="toggleSceneChangesPanel"
          :title="isSceneChangesCollapsed ? 'Déplier' : 'Replier'"
          style="transition: right 0.2s"
        >
          <ArrowSvg :class="isSceneChangesCollapsed ? 'w-4 h-4 rotate-180' : 'w-4 h-4'" />
        </button>

        <transition name="fade">
          <div
            v-if="!isSceneChangesCollapsed"
            class="fixed top-[60px] right-0 z-40 h-fit w-64 max-w-full flex flex-col pr-2"
          >
            <SceneChangesList
              :sceneChanges="uniqueSceneChangeTimecodes"
              :selected="selectedSceneChangeIdx ?? undefined"
              @select="onSelectSceneChange"
              @edit="onEditSceneChange"
              @delete="onDeleteSceneChange"
              @deleteAll="onDeleteAllSceneChanges"
              @add="onAddSceneChange"
              @seekTo="onNavigationSeek"
            />
          </div>
        </transition>
      </div>
      <div>
        <button
          class="fixed top-[40px] left-0 z-50 backdrop-blur-sm bg-black/30 text-white border border-gray-600 rounded-r-lg w-7 h-12 flex items-center justify-center cursor-pointer shadow-lg text-lg p-0 hover:bg-agfa-blue transition-colors duration-300"
          @click="toggleTimecodesPanel"
          :title="isTimecodesCollapsed ? 'Déplier' : 'Replier'"
          style="transition: left 0.2s"
        >
          <ArrowSvg :class="isTimecodesCollapsed ? 'w-4 h-4' : 'w-4 h-4 rotate-180'" />
        </button>

        <transition name="fade">
          <div
            v-if="!isTimecodesCollapsed"
            class="fixed top-[58px] left-0 z-40 h-fit w-80 max-w-full flex flex-col overflow-y-auto"
          >
            <TimecodesListMultiLine
              :timecodes="compatibleTimecodes"
              :rythmo-lines-count="project?.rythmo_lines_count || 1"
              :selected="selectedTimecode || undefined"
              :project-id="project?.id || 0"
              @select="selectTimecode"
              @edit="editTimecode"
              @delete="deleteTimecode"
              @add="onAddTimecode"
              @add-to-line="addTimecodeToLine"
              @deleteAll="onDeleteAllTimecodes"
              @updated="loadTimecodes"
            />
          </div>
        </transition>
      </div>

      <!-- Center Panel - Video and Controls -->
      <div
        class="flex-1 flex flex-col items-center bg-agfa-dark rounded-lg shadow-lg min-w-0 lg:mr-0 lg:max-w-full lg:px-1 p-2 relative min-h-[500px]"
      >
        <!-- Écran de chargement dans la zone vidéo -->
        <div
          v-if="project && project.video_path && isVideoLoading"
          class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm z-30 flex flex-col items-center justify-center rounded-lg h-full w-lvw"
        >
          <div class="flex flex-col items-center justify-center space-y-6">
            <!-- Spinner personnalisé -->
            <div class="relative">
              <div class="w-24 h-24 border-8 border-gray-700 rounded-full"></div>
              <div
                class="w-24 h-24 border-8 border-agfa-blue border-t-transparent rounded-full animate-spin absolute top-0 left-0"
              ></div>
            </div>

            <!-- Message de chargement -->
            <div class="text-center mt-8">
              <p class="text-xl text-white font-medium mb-2">Chargement de la vidéo...</p>
              <p class="text-sm text-gray-400">Veuillez patienter</p>
            </div>

            <!-- Barre de progression stylisée -->
            <div class="w-64 h-1 bg-gray-700 rounded-full mt-4 overflow-hidden">
              <div class="h-full bg-agfa-blue animate-pulse rounded-full" style="width: 100%"></div>
            </div>
          </div>
        </div>

        <!-- Indicateur de buffering pendant le seek (toujours au-dessus de la vidéo) -->
        <div
          v-if="isVideoBuffering && !isVideoLoading"
          class="absolute inset-0 flex items-center justify-center z-20 pointer-events-none"
        >
          <div class="buffering-spinner">
            <div class="spinner-ring"></div>
            <div class="spinner-ring-inner"></div>
            <div class="spinner-dot"></div>
          </div>
        </div>

        <!-- Lecteur vidéo (toujours présent dans le DOM pour charger, masqué si loading) -->
        <VideoPlayer
          v-if="project && project.video_path"
          :class="{ 'opacity-0 pointer-events-none': isVideoLoading }"
          :src="getVideoUrl(project.video_path)"
          :currentTime="currentTime"
          @timeupdate="onVideoTimeUpdate"
          @loadedmetadata="onLoadedMetadata"
          @canplay="onVideoCanPlay"
          @canplaythrough="onVideoCanPlayThrough"
          @loadeddata="onVideoLoadedData"
          @seeking="onVideoSeeking"
          @seeked="onVideoSeeked"
          @waiting="onVideoWaiting"
          @playing="onVideoPlaying"
          @stalled="onVideoStalled"
          @error="onVideoError"
        />

        <!-- Message si pas de vidéo -->
        <div
          v-if="!project || !project.video_path"
          class="w-full max-w-3xl h-96 bg-gray-800 text-white flex items-center justify-center rounded-lg"
        >
          Aucune vidéo
        </div>

        <!-- Boutons ajout changement de plan et ajout timecode -->
        <div class="flex flex-row gap-3 mt-4 mb-2 w-full max-w-3xl">
          <!-- Bouton Ajouter un changement de plan -->
          <button
            class="scene-button flex-1 flex items-center justify-center gap-2 text-white border rounded-lg px-4 py-3 font-semibold cursor-pointer shadow-lg transition-all duration-300 hover:shadow-xl hover:scale-105 active:scale-95
              bg-[rgba(101,115,144,0.2)] border-[rgba(101,115,144,0.4)]
              hover:bg-[rgba(101,115,144,0.4)] hover:border-[rgba(101,115,144,0.6)]
              disabled:opacity-40 disabled:cursor-not-allowed disabled:bg-[rgba(75,85,99,0.2)]"
            @click="addSceneChange"
            title="Ajouter un changement de plan (S)"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <line x1="12" y1="4" x2="12" y2="20" stroke-width="3" stroke-linecap="round"/>
              <circle cx="12" cy="4" r="2" fill="currentColor"/>
            </svg>
            <span>Changement de plan</span>
          </button>

          <!-- Bouton Ajouter un timecode -->
          <button
            class="timecode-button flex-1 flex items-center justify-center gap-2 text-white border rounded-lg px-4 py-3 font-semibold cursor-pointer shadow-lg transition-all duration-300 hover:shadow-xl hover:scale-105 active:scale-95
              bg-[rgba(132,85,246,0.2)] border-[rgba(132,85,246,0.4)]
              hover:bg-[rgba(132,85,246,0.4)] hover:border-[rgba(132,85,246,0.6)]"
            @click="onAddTimecode"
            title="Ajouter un timecode (T)"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <rect x="4" y="8" width="16" height="8" rx="2" stroke-width="2"/>
              <path d="M8 11h8M8 13h6" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <span>Timecode</span>
          </button>
        </div>

        <!-- CharactersList moved inside MultiRythmoBand (forwarded props/events) -->

        <!-- Barre de navigation vidéo -->
        <VideoNavigationBar
          v-if="project && videoDuration > 0"
          :currentTime="currentTime"
          :videoDuration="videoDuration"
          :timecodes="compatibleTimecodes"
          :sceneChanges="uniqueSceneChangeTimecodes"
          :isVideoPaused="isVideoPaused"
          :rythmoLinesCount="project.rythmo_lines_count"
          @seek="onNavigationSeek"
          @seekDelta="seek"
          @seekFrame="seekFrame"
          @togglePlayPause="updatePlayPauseState"
          @navigateSceneChange="onNavigateSceneChange"
          @navigateTimecode="onNavigateTimecode"
        />

        <!-- Configuration multi-lignes et bandes rythmo -->
        <MultiRythmoBand
          v-if="project"
          :key="rythmoReloadKey"
          :timecodes="compatibleTimecodes"
          :sceneChanges="uniqueSceneChanges"
          :currentTime="currentTime"
          :videoDuration="videoDuration"
          :instant="instantRythmoScroll"
          :rythmoLinesCount="Number(project.rythmo_lines_count || 1)"
          :characters="allCharacters"
          :activeCharacter="activeCharacter"
          @character-selected="onCharacterSelected"
          @add-character="onAddCharacter"
          @edit-character="onEditCharacter"
          @character-deleted="onCharacterDeleted"
          @seek="onRythmoSeek"
          @update-timecode="onUpdateTimecode"
          @update-timecode-bounds="onUpdateTimecodeBounds"
          @move-timecode="onMoveTimecode"
          @update-timecode-show-character="onUpdateTimecodeShowCharacter"
          @update-timecode-character="onUpdateTimecodeCharacter"
          @update-timecode-separator-positions="onUpdateTimecodeSeparatorPositions"
          @delete-timecode="onDeleteTimecode"
          @add-timecode-to-line="onAddTimecodeToLine"
          @update-lines-count="onUpdateLinesCount"
          @selected-line-changed="onSelectedLineChanged"
          @update-scene-change="onUpdateSceneChangeFromBand"
          @delete-scene-change="onDeleteSceneChangeFromBand"
        />

        <!-- <RythmoControls
          :isVideoPaused="isVideoPaused"
          @seek="seek"
          @seekFrame="seekFrame"
        /> -->
      </div>
    </div>

    <!-- Modal d'édition/ajout de timecode (utilise le composant dédié) -->
    <TimecodeModal
      v-if="showTimecodeModal"
      :show="showTimecodeModal"
      :timecode="editTimecodeIdx !== null ? compatibleTimecodes[editTimecodeIdx] : null"
      :maxLines="project?.rythmo_lines_count || 1"
      :defaultLineNumber="modalTimecode.line_number"
      :currentTime="currentTime"
      :projectId="project?.id"
      :characters="allCharacters"
      @submit="onTimecodeModalSubmit"
      @close="closeTimecodeModal"
      @srt-imported="onSrtImported"
    />

    <!-- Modal de gestion des personnages -->
    <CharacterModal
      v-if="showCharacterModal && project"
      :projectId="project.id"
      :editingCharacter="editingCharacter"
      @close="closeCharacterModal"
      @saved="onCharacterSaved"
    />

    <!-- Modal d'édition de changement de plan -->
    <SceneChangeEditModal
      :show="showSceneChangeModal"
      :timecode="editSceneChangeIdx !== null ? sceneChanges[editSceneChangeIdx]?.timecode : null"
      @submit="onSceneChangeModalSubmit"
      @close="closeSceneChangeModal"
    />

    <!-- Modal des raccourcis clavier -->
    <KeyboardShortcutsModal :show="showKeyboardShortcuts" @close="showKeyboardShortcuts = false" />

    <!-- Modal des paramètres du projet -->
    <ProjectSettingsModal :show="showProjectSettings" @close="showProjectSettings = false" />

    <!-- Menu IA avec état des capacités et fonctionnalités -->
    <AiMenuModal
      v-model:show="showAiMenu"
      :capabilities="serverCapabilities"
      :has-scene-changes="hasSceneChanges"
      :has-timecodes="hasTimecodes"
      :is-analyzing="isAnalyzing"
      @start-analysis="handleStartAnalysis"
      @start-dialogue-extraction="handleStartDialogueExtraction"
      @start-translation="handleStartTranslation"
    />

    <!-- Modal des paramètres d'analyse IA -->
    <SceneAnalysisSettingsModal
      v-model="showAnalysisSettings"
      @confirm="handleAnalysisConfirm"
    />

    <!-- Modal d'analyse IA des changements de plan -->
    <SceneAnalysisModal
      :show="showAnalysisModal"
      :status="analysisStatus === 'none' ? 'pending' : analysisStatus"
      :progress="analysisProgress"
      :message="analysisMessage"
      :showDetails="true"
      @cancel="handleCancelAnalysis"
    />

    <!-- Modal d'extraction de dialogues - Configuration -->
    <DialogueExtractionModal
      v-model:show="showDialogueExtractionSettings"
      @start="handleDialogueExtractionStart"
    />

    <!-- Modal d'extraction de dialogues - Progression -->
    <DialogueExtractionProgress
      v-if="project"
      :show="showDialogueExtractionProgress"
      :project-id="project.id"
      @update:show="showDialogueExtractionProgress = $event"
      @completed="handleDialogueExtractionCompleted"
      @failed="handleDialogueExtractionFailed"
      @cancelled="handleDialogueExtractionCancelled"
    />

    <!-- Modal d'extraction de dialogues - Résultat / Post-traitement -->
    <DialogueExtractionResultModal
      ref="dialogueExtractionResultModalRef"
      :show="showDialogueExtractionResult"
      @update:show="(value) => { showDialogueExtractionResult = value }"
      :result="dialogueExtractionResult || { timecodes_count: 0, characters_count: 0 }"
      :project-id="project?.id || 0"
      :translation-enabled="serverCapabilities?.translation || false"
      @translate="handleTranslateFromExtraction"
      @merge-characters="showCharacterMergeModal = true"
    />

    <!-- Modal de fusion de personnages -->
    <CharacterMergeModal
      v-if="project"
      v-model:show="showCharacterMergeModal"
      :characters="allCharacters"
      :dialogue-counts="dialogueCounts"
      @merge="handleCharactersMerged"
    />

    <!-- Modal de traduction - Configuration -->
    <TranslateDialoguesModal
      v-model:show="showTranslationSettings"
      :availableLanguages="(serverCapabilities?.supported_languages || []) as any"
      :provider="serverCapabilities?.translation_provider || 'unknown'"
      @start="handleTranslationStart"
    />

    <!-- Composant de traduction - Progression -->
    <TranslationProgress
      v-if="project"
      :show="showTranslationProgress"
      :project-id="project.id"
      @update:show="showTranslationProgress = $event"
      @completed="handleTranslationCompleted"
      @failed="handleTranslationFailed"
      @cancelled="handleTranslationCancelled"
    />

    <!-- Dialog de confirmation d'annulation -->
    <ConfirmDialog
      v-model:show="showCancelConfirm"
      title="Annuler l'analyse ?"
      message="Êtes-vous sûr de vouloir annuler l'analyse en cours ? Cette action supprimera tous les changements de plan déjà détectés."
      confirm-text="Oui, annuler"
      cancel-text="Non, continuer"
      danger
      @confirm="() => { pendingCancelAction?.(); showCancelConfirm = false; pendingCancelAction = null }"
      @cancel="() => { showCancelConfirm = false; pendingCancelAction = null }"
    />

    <!-- Modal de confirmation pour suppression de tous les scene changes -->
    <ConfirmModal
      v-model:show="showDeleteAllConfirm"
      title="Supprimer tous les changements de plan ?"
      :message="`Êtes-vous sûr de vouloir supprimer tous les ${sceneChanges.length} changements de plan ?`"
      details="Cette action est irréversible."
      confirm-text="Oui, tout supprimer"
      cancel-text="Annuler"
      type="danger"
      @confirm="confirmDeleteAllSceneChanges"
    />

    <!-- Modal de confirmation pour suppression de tous les timecodes -->
    <ConfirmModal
      v-model:show="showDeleteAllTimecodesConfirm"
      title="Supprimer tous les timecodes ?"
      :message="`Êtes-vous sûr de vouloir supprimer tous les ${allTimecodes.length} timecodes ?`"
      details="Cette action est irréversible et supprimera tous les dialogues du projet."
      confirm-text="Oui, tout supprimer"
      cancel-text="Annuler"
      type="danger"
      @confirm="confirmDeleteAllTimecodes"
    />

    <!-- Modal de gestion des collaborateurs -->
    <BaseModal
      :show="showCollaboratorsModal"
      title="Gérer les collaborateurs"
      subtitle="Invitez et gérez les personnes qui travaillent sur ce projet"
      size="2xl"
      max-height="90vh"
      @close="showCollaboratorsModal = false"
    >
      <template v-slot:icon>
        <UsersIcon class="w-6 h-6 sm:w-8 sm:h-8 text-white" />
      </template>

      <template v-slot:default>
        <CollaboratorsPanel v-if="project" :projectId="project.id" :canManage="canManageProject" />
      </template>
    </BaseModal>
  </div>
</template>

<script setup lang="ts">
import TimecodeModal from '../components/projectDetail/TimecodeModal.vue'
import KeyboardShortcutsModal from '../components/projectDetail/KeyboardShortcutsModal.vue'
import ProjectSettingsModal from '../components/projectDetail/ProjectSettingsModal.vue'
import BaseModal from '../components/BaseModal.vue'
import { UsersIcon } from '@heroicons/vue/24/outline'
// Contrôle du scroll instantané pour la bande rythmo

const instantRythmoScroll = ref(true) // true = instantané, false = smooth
import BackSvg from '../assets/icons/back.svg'
import ArrowSvg from '../assets/icons/arrow.svg'
// Gestion du repli horizontal de la partie timecodes (fermé par défaut)
const isTimecodesCollapsed = ref(true)
function toggleTimecodesPanel() {
  isTimecodesCollapsed.value = !isTimecodesCollapsed.value
}
import { useRouter, useRoute } from 'vue-router'
import { ref, onMounted, reactive, computed, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useProjectSettingsStore } from '@/stores/projectSettings'
import { useCollaborativeRefresh } from '@/composables/useCollaborativeRefresh'
import { useServerCapabilities } from '@/composables/useServerCapabilities'
import { useVideoAudioMixer } from '@/composables/useVideoAudioMixer'
import api from '../api/axios'
import { AxiosError } from 'axios'
import { timecodeApi, type Timecode as ApiTimecode } from '../api/timecodes'
import { characterApi, type Character } from '../api/characters'
import * as sceneChangesApi from '../api/sceneChanges'
import { exportProject } from '../api/projects'
import { startSceneAnalysis, getAnalysisStatus, cancelSceneAnalysis } from '../api/sceneAnalysis'
import { startDialogueExtraction } from '../api/dialogueExtraction'
import type { DialogueExtractionOptions } from '../api/dialogueExtraction'
import { startTranslation } from '../api/translation'
import { notificationService } from '../services/notifications'
import TimecodesListMultiLine from '../components/projectDetail/TimecodesListMultiLine.vue'
import SceneChangesList from '../components/projectDetail/SceneChangesList.vue'
import CharacterModal from '../components/projectDetail/CharacterModal.vue'
import SceneChangeEditModal from '../components/projectDetail/SceneChangeEditModal.vue'
import CollaboratorsPanel from '../components/projectDetail/CollaboratorsPanel.vue'
import InstrumentalButton from '../components/InstrumentalButton.vue'
import ExportDropdown from '../components/ExportDropdown.vue'
// Gestion du repli horizontal de la partie scene changes (fermé par défaut)
const isSceneChangesCollapsed = ref(true)
function toggleSceneChangesPanel() {
  isSceneChangesCollapsed.value = !isSceneChangesCollapsed.value
}

const selectedSceneChangeIdx = ref<number | null>(null)
function onSelectSceneChange(idx: number) {
  selectedSceneChangeIdx.value = idx
  // Seek vidéo si possible
  const t = sceneChanges.value[idx]
  if (typeof t === 'number') {
    lastSeekFromTimecode = true
    currentTime.value = t
    instantRythmoScroll.value = true
  }
}
function onEditSceneChange(idx: number) {
  selectedSceneChangeIdx.value = idx
  editSceneChangeIdx.value = idx
  showSceneChangeModal.value = true
}
function onAddSceneChange() {
  addSceneChange()
}
async function onDeleteSceneChange(idx: number) {
  const sc = sceneChanges.value[idx]
  if (!sc) return
  try {
    await api.delete(`/scene-changes/${sc.id}`)
    sceneChanges.value.splice(idx, 1)
  } catch {
    // TODO: gestion d'erreur
  }
}

async function onDeleteAllSceneChanges() {
  if (!project.value) return

  // Afficher la modal de confirmation
  showDeleteAllConfirm.value = true
}

async function confirmDeleteAllSceneChanges() {
  if (!project.value) return

  try {
    // Supprimer tous les scene changes du projet via l'API
    await api.delete(`/projects/${project.value.id}/scene-changes`)

    // Vider le tableau local
    sceneChanges.value = []

    // Rafraîchir les bandes rythmo
    refreshRythmoBands()

    notificationService.success('Succès', 'Tous les changements de plan ont été supprimés')
  } catch (error) {
    console.error('Erreur lors de la suppression des changements de plan:', error)
    notificationService.error('Erreur', 'Impossible de supprimer les changements de plan')
  }
}

// ===== SUPPRESSION DE TOUS LES TIMECODES =====

async function onDeleteAllTimecodes() {
  if (!project.value) return

  // Afficher la modal de confirmation
  showDeleteAllTimecodesConfirm.value = true
}

async function confirmDeleteAllTimecodes() {
  if (!project.value) return

  try {
    // Supprimer tous les timecodes du projet via l'API
    await api.delete(`/projects/${project.value.id}/timecodes`)

    // Vider le tableau local
    allTimecodes.value = []

    // Rafraîchir les bandes rythmo
    refreshRythmoBands()

    notificationService.success('Succès', 'Tous les timecodes ont été supprimés')
  } catch (error) {
    console.error('Erreur lors de la suppression des timecodes:', error)
    notificationService.error('Erreur', 'Impossible de supprimer les timecodes')
  }
}

// Nouveaux gestionnaires pour les événements des bandes rythmo
async function onUpdateSceneChangeFromBand(payload: {
  id: number
  newTimecode: number
  isPreview: boolean
}) {
  if (payload.isPreview) {
    // Pour le preview, on peut juste retourner sans rien faire
    // Le feedback visuel est géré par le composant qui drag
    return
  }

  try {
    const updatedSceneChange = await sceneChangesApi.updateSceneChange(payload.id, {
      timecode: payload.newTimecode,
    })

    // Mettre à jour dans la liste locale
    const index = sceneChanges.value.findIndex((sc) => sc.id === payload.id)
    if (index !== -1) {
      sceneChanges.value[index] = updatedSceneChange
      // Retrier par timecode
      sceneChanges.value.sort((a, b) => a.timecode - b.timecode)
    }
  } catch (error) {
    console.error('Erreur lors de la mise à jour du changement de plan:', error)
    // TODO: Afficher un message d'erreur à l'utilisateur
  }
}

async function onDeleteSceneChangeFromBand(payload: { id: number }) {
  try {
    await sceneChangesApi.deleteSceneChange(payload.id)

    // Retirer de la liste locale
    const index = sceneChanges.value.findIndex((sc) => sc.id === payload.id)
    if (index !== -1) {
      sceneChanges.value.splice(index, 1)
    }
  } catch (error) {
    console.error('Erreur lors de la suppression du changement de plan:', error)
    // TODO: Afficher un message d'erreur à l'utilisateur
  }
}
import VideoPlayer from '../components/projectDetail/VideoPlayer.vue'
import VideoNavigationBar from '../components/projectDetail/VideoNavigationBar.vue'
import MultiRythmoBand from '../components/projectDetail/MultiRythmoBand.vue'
import SceneAnalysisModal from '../components/SceneAnalysisModal.vue'
import SceneAnalysisSettingsModal from '../components/SceneAnalysisSettingsModal.vue'
import AiMenuModal from '../components/AiMenuModal.vue'
import DialogueExtractionModal from '../components/DialogueExtractionModal.vue'
import DialogueExtractionProgress from '../components/DialogueExtractionProgress.vue'
import DialogueExtractionResultModal from '../components/DialogueExtractionResultModal.vue'
import CharacterMergeModal from '../components/CharacterMergeModal.vue'
import TranslateDialoguesModal from '../components/TranslateDialoguesModal.vue'
import TranslationProgress from '../components/TranslationProgress.vue'
import ConfirmDialog from '../components/ConfirmDialog.vue'
import ConfirmModal from '../components/ConfirmModal.vue'

// import RythmoControls from '../components/projectDetail/RythmoControls.vue'

const route = useRoute()
const router = useRouter()
const settingsStore = useProjectSettingsStore()

// Fonction de retour aux projets
function goBack() {
  router.push({ name: 'home' })
}

// Liste des changements de plan (objets {id, timecode})
// Utilise le type depuis l'API
type SceneChange = sceneChangesApi.SceneChange
const sceneChanges = ref<SceneChange[]>([])

// Computed pour s'assurer que sceneChanges n'a pas de doublons
const uniqueSceneChanges = computed(() => {
  return sceneChanges.value.filter(
    (sc, index, array) => array.findIndex((s) => s.id === sc.id) === index,
  )
})

function goToFinalPreview() {
  if (!project.value || !project.value.video_path || compatibleTimecodes.value.length === 0) return
  router.push({
    name: 'final-preview',
    query: {
      video: getVideoUrl(project.value.video_path),
      rythmo: JSON.stringify(compatibleTimecodes.value),
      rythmoLinesCount: String(project.value.rythmo_lines_count || 1),
      sceneChanges: JSON.stringify(uniqueSceneChanges.value),
      muteVocals: muteVocals.value ? 'true' : 'false', // Passer l'état mute
      instrumentalAudio: instrumentalAudioUrl.value || '', // Passer l'URL de l'instrumental
    },
  })
}

// Fonction pour gérer les différents types d'export
async function handleExport(type: 'project' | 'video' | 'audio' | 'instrumental') {
  if (!project.value) return

  try {
    switch (type) {
      case 'project':
        // Export du projet complet en .agfa
        await handleExportProject()
        break

      case 'video':
        // Télécharger la vidéo source
        if (project.value.video_path) {
          const videoUrl = getVideoUrl(project.value.video_path)
          const link = document.createElement('a')
          link.href = videoUrl
          link.download = `${project.value.name.replace(/[^a-z0-9]/gi, '_').toLowerCase()}_video.mp4`
          document.body.appendChild(link)
          link.click()
          document.body.removeChild(link)
          notificationService.success('Succès', 'Téléchargement de la vidéo lancé')
        }
        break

      case 'audio':
        // Extraire et télécharger l'audio de la vidéo
        if (project.value.video_path) {
          notificationService.info('Extraction audio', 'Extraction de l\'audio en cours...')

          // Utiliser la nouvelle route /audio-extract avec le path complet encodé
          const apiBase = import.meta.env.VITE_API_URL?.replace(/\/api\/?$/, '') || ''
          const audioUrl = `${apiBase}/api/audio-extract/${encodeURIComponent(project.value.video_path)}`

          try {
            // Créer un lien de téléchargement
            const link = document.createElement('a')
            link.href = audioUrl
            link.download = `${project.value.name.replace(/[^a-z0-9]/gi, '_').toLowerCase()}_audio.wav`
            document.body.appendChild(link)
            link.click()
            document.body.removeChild(link)
            notificationService.success('Succès', 'Téléchargement de l\'audio lancé')
          } catch (error) {
            console.error('Erreur lors de l\'extraction audio:', error)
            notificationService.error('Erreur', 'Impossible d\'extraire l\'audio')
          }
        }
        break

      case 'instrumental':
        // Télécharger la piste instrumentale
        if (project.value.instrumental_audio_path) {
          const apiBase = import.meta.env.VITE_API_URL?.replace(/\/api\/?$/, '') || ''
          const instrumentalUrl = `${apiBase}/storage/${project.value.instrumental_audio_path}`
          const link = document.createElement('a')
          link.href = instrumentalUrl
          link.download = `${project.value.name.replace(/[^a-z0-9]/gi, '_').toLowerCase()}_instrumental.wav`
          document.body.appendChild(link)
          link.click()
          document.body.removeChild(link)
          notificationService.success('Succès', 'Téléchargement de la piste instrumentale lancé')
        }
        break
    }
  } catch (error) {
    console.error('Erreur lors de l\'export:', error)
    notificationService.error('Erreur', 'Erreur lors de l\'export')
  }
}

// Fonction pour déclencher la génération de l'instrumental depuis ExportDropdown
function handleGenerateInstrumental() {
  // Déclencher la modal de confirmation du composant InstrumentalButton
  instrumentalButtonRef.value?.handleInitialClick()
}

// Fonction pour exporter le projet en format .agfa crypté
async function handleExportProject() {
  if (!project.value) return

  try {
    const exportData = await exportProject(project.value.id)

    // Créer un blob et télécharger le fichier
    const blob = new Blob([JSON.stringify(exportData, null, 2)], {
      type: 'application/octet-stream'
    })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `${project.value.name.replace(/[^a-z0-9]/gi, '_').toLowerCase()}_export.agfa`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Erreur lors de l\'export du projet:', error)
    notificationService.error('Erreur', 'Erreur lors de l\'export du projet')
  }
}

interface Timecode {
  id?: number
  project_id?: number
  start: number
  end: number
  text: string
  line_number?: number
  character_id?: number | null
  show_character?: boolean
}
interface Project {
  id: number
  name: string
  description?: string
  video_path?: string
  timecodes?: Timecode[]
  rythmo_lines_count: number
  user_id: number
  owner?: { id: number; name: string; email: string }
  collaborators?: Array<{
    id: number
    name: string
    email: string
    permission?: string
    pivot?: { permission: string; created_at: string }
  }>
  // Instrumental audio feature
  instrumental_audio_path?: string | null
  instrumental_status?: 'not_generated' | 'processing' | 'completed' | 'failed'
  instrumental_progress?: number
  // Required by API Project type
  status: 'in_progress' | 'completed'
  created_at: string
  updated_at: string
}
const project = ref<Project | null>(null)
const loading = ref(true)
const loadingError = ref<string | null>(null)
const currentTime = ref(0)
// Pour éviter de rebinder le currentTime à chaque update (empêche le seek natif)
let lastSeekFromTimecode = false
const videoDuration = ref(0)
const videoFps = ref(25) // valeur par défaut, sera mise à jour
const isVideoPaused = ref(true)
const selectedTimecodeIdx = ref<number | null>(null)
const isVideoLoading = ref(true) // Indique si la vidéo est en cours de chargement
const isSeeking = ref(false) // Indique si on est en train de scrubber la vidéo
const isVideoBuffering = ref(false) // Indique si la vidéo est en train de buffer

// État pour la fonctionnalité Instrumental (mute vocals)
const muteVocals = ref(false)

// Ref vers le composant InstrumentalButton pour accès programmatique
const instrumentalButtonRef = ref<InstanceType<typeof InstrumentalButton> | null>(null)

// URL de l'audio instrumental (si disponible)
const instrumentalAudioUrl = computed(() => {
  if (!project.value?.instrumental_audio_path) return null

  // Extraire projectId et filename du path "instrumental/{projectId}/instrumental.wav"
  const pathParts = project.value.instrumental_audio_path.split('/')
  const projectId = pathParts[1] // "instrumental/32/instrumental.wav" -> "32"
  const filename = pathParts[2] // "instrumental/32/instrumental.wav" -> "instrumental.wav"

  const apiBase = import.meta.env.VITE_API_URL || ''
  return `${apiBase}/instrumental/${projectId}/${filename}`
})

// Initialiser le mixer audio (Web Audio API)
let audioMixer: ReturnType<typeof useVideoAudioMixer> | null = null

// Throttle pour les mises à jour vidéo (optimisation mobile)
let lastUpdateTime = 0
const UPDATE_THROTTLE = 100 // ms entre chaque update (réduit la charge)

// Gestion optimisée du buffering pour éviter les icônes bloquées
let bufferingTimeout: number | null = null
let seekingTimeout: number | null = null
const BUFFERING_TIMEOUT = 3000 // Timeout max pour forcer la fin du buffering (3s)
const SEEKING_DEBOUNCE = 150 // Debounce pour les événements seeking/seeked

// Constantes pour le décalage de synchronisation
const FRAME_OFFSET = 8 // Décalage de 8 frames
const FPS = 25 // Frames par seconde

/**
 * Fonction helper pour vérifier si la vidéo est vraiment prête
 */
function isVideoReallyReady(): boolean {
  const videoEl = document.querySelector('video') as HTMLVideoElement | null
  if (!videoEl) return false

  // readyState >= 3 signifie HAVE_FUTURE_DATA (assez de données pour jouer)
  // readyState === 4 signifie HAVE_ENOUGH_DATA (peut jouer jusqu'à la fin)
  return videoEl.readyState >= 3
}

/**
 * Force la fin du buffering (sécurité anti-bug)
 */
function forceEndBuffering() {
  if (bufferingTimeout !== null) {
    clearTimeout(bufferingTimeout)
    bufferingTimeout = null
  }

  // Vérifier l'état réel de la vidéo avant de désactiver
  if (isVideoReallyReady() || !isVideoLoading.value) {
    isVideoBuffering.value = false
    isSeeking.value = false
  }
}

/**
 * Démarre le buffering avec timeout de sécurité
 */
function startBuffering() {
  isVideoBuffering.value = true

  // Clear ancien timeout
  if (bufferingTimeout !== null) {
    clearTimeout(bufferingTimeout)
  }

  // Timeout de sécurité pour forcer la fin du buffering
  bufferingTimeout = window.setTimeout(() => {
    forceEndBuffering()
  }, BUFFERING_TIMEOUT)
}

/**
 * Termine le buffering (avec vérification)
 */
function endBuffering() {
  if (bufferingTimeout !== null) {
    clearTimeout(bufferingTimeout)
    bufferingTimeout = null
  }

  // Attendre un tick pour s'assurer que tous les événements sont traités
  setTimeout(() => {
    if (isVideoReallyReady()) {
      isVideoBuffering.value = false
    }
  }, 50)
}
// Flag global indiquant qu'on est dans un champ texte (désactive les raccourcis)
const isEditingText = ref(false)
// Pour savoir si on doit reprendre la lecture après édition
let resumePlaybackAfterEdit = false
// Stockage des handlers focus pour cleanup (références module-level)
let focusInHandler: ((e: Event) => void) | null = null
let focusOutHandler: ((e: Event) => void) | null = null

// Clé pour forcer la reconstruction complète du composant MultiRythmoBand
const generateRythmoKey = () => `rythmo-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`
const rythmoReloadKey = ref(generateRythmoKey())

// Fonction centralisée pour rafraîchir les bandes rythmo
function refreshRythmoBands() {
  rythmoReloadKey.value = generateRythmoKey()
}

// Timecodes multi-lignes (nouvelle API)
const allTimecodes = ref<ApiTimecode[]>([])

// Gestion des personnages
const allCharacters = ref<Character[]>([])
const activeCharacter = ref<Character | null>(null)
const showCharacterModal = ref(false)
const editingCharacter = ref<Character | null>(null)

// Gestion du modal des raccourcis clavier
const showKeyboardShortcuts = ref(false)
const showCollaboratorsModal = ref(false)
const showProjectSettings = ref(false)

// Gestion de l'analyse IA des changements de plan
const isAnalyzing = ref(false)
const showAiMenu = ref(false)
const showAnalysisModal = ref(false)
const showAnalysisSettings = ref(false)
const analysisStatus = ref<'none' | 'pending' | 'processing' | 'completed' | 'failed' | 'cancelled'>('none')
const analysisProgress = ref(0)  // 0-100
const analysisMessage = ref('')
let analysisPollingInterval: number | null = null

// Gestion de l'extraction de dialogues
const showDialogueExtractionSettings = ref(false)
const showDialogueExtractionProgress = ref(false)
const showDialogueExtractionResult = ref(false)
const showCharacterMergeModal = ref(false)
const dialogueExtractionResultModalRef = ref<InstanceType<typeof DialogueExtractionResultModal> | null>(null)
const dialogueExtractionResult = ref<{
  timecodes_count: number
  characters_count: number
  source_language?: string
} | null>(null)

// Gestion de la traduction
const showTranslationSettings = ref(false)
const showTranslationProgress = ref(false)

// Dialog de confirmation pour annulation
const showCancelConfirm = ref(false)
const pendingCancelAction = ref<(() => void) | null>(null)

// Dialog de confirmation pour suppression de tous les scene changes
const showDeleteAllConfirm = ref(false)

// Dialog de confirmation pour suppression de tous les timecodes
const showDeleteAllTimecodesConfirm = ref(false)

// Ligne actuellement sélectionnée (pour création de nouveaux timecodes)
const selectedLineNumber = ref<number>(1)

// Store d'authentification
const authStore = useAuthStore()
const { capabilities: serverCapabilities } = useServerCapabilities()

// Computed pour vérifier s'il y a des collaborateurs actifs
const hasActiveCollaborators = computed(() => {
  if (!project.value?.collaborators) return false
  return project.value.collaborators.length > 0
})

// Computed pour l'ID du projet (pour le composable)
const projectId = computed(() => project.value?.id ?? null)

// Fonction de synchronisation pour le polling collaboratif
async function syncCollaborativeData() {
  if (!project.value?.id) return

  try {
    // Recharger les timecodes
    await loadTimecodes()

    // Recharger les personnages
    await loadCharacters()

    // Recharger les scene changes (sans /api car déjà dans baseURL)
    const scResponse = await api.get(`/projects/${project.value.id}/scene-changes`)
    sceneChanges.value = scResponse.data

    // Rafraîchir les bandes rythmo
    refreshRythmoBands()
  } catch (error) {
    console.error('Erreur lors de la synchronisation collaborative:', error)
  }
}

// Initialiser le polling collaboratif
useCollaborativeRefresh({
  projectId,
  hasCollaborators: hasActiveCollaborators,
  isEditingContent: computed(() => isEditingText.value || !isVideoPaused.value), // Ne pas synchro si édition OU vidéo en lecture
  onRefresh: syncCollaborativeData,
  intervalMs: 4000, // Sync toutes les 4 secondes
})

// Computed pour les timecodes de scene changes uniques
const uniqueSceneChangeTimecodes = computed(() => {
  // Utilise un Set pour éliminer les doublons, puis convertit en array
  const uniqueTimecodes = [...new Set(uniqueSceneChanges.value.map((sc) => sc.timecode))]
  return uniqueTimecodes.sort((a, b) => a - b)
})

// Computed pour compter les dialogues par personnage
const dialogueCounts = computed<Record<number, number>>(() => {
  const counts: Record<number, number> = {}
  compatibleTimecodes.value.forEach((tc) => {
    // Vérifier si le timecode a un character_id (car peut être différents types)
    if ('character_id' in tc && tc.character_id) {
      counts[tc.character_id] = (counts[tc.character_id] || 0) + 1
    }
  })
  return counts
})

// Computed pour vérifier s'il y a des changements de plan existants
const hasSceneChanges = computed(() => {
  return sceneChanges.value.length > 0
})

// Computed pour vérifier s'il y a des timecodes existants
const hasTimecodes = computed(() => {
  return allTimecodes.value.length > 0
})

// Fonction pour trouver une position libre pour un nouveau timecode
function findFreeTimecodePosition(
  preferredStart: number,
  duration: number,
  lineNumber: number,
): { start: number; end: number } {
  const MARGIN = 0.1 // Marge de sécurité de 0.1 seconde

  // Récupère tous les timecodes de la même ligne, triés par ordre de début
  const sameLineTimecodes = allTimecodes.value
    .filter((tc) => tc.line_number === lineNumber)
    .sort((a, b) => a.start - b.start)

  // Si pas de timecodes sur cette ligne, utiliser la position préférée
  if (sameLineTimecodes.length === 0) {
    return { start: preferredStart, end: preferredStart + duration }
  }

  // Vérifie si l'espace au curseur (position préférée) est libre
  const preferredEnd = preferredStart + duration
  const hasConflictAtPreferred = sameLineTimecodes.some((tc) => {
    // Il y a conflit si les plages se chevauchent (avec marge)
    return !(preferredEnd + MARGIN <= tc.start || preferredStart >= tc.end + MARGIN)
  })

  // Si l'espace au curseur est libre, l'utiliser
  if (!hasConflictAtPreferred) {
    return { start: preferredStart, end: preferredEnd }
  }

  // Sinon, cherche le dernier timecode et place le nouveau après
  const lastTimecode = sameLineTimecodes[sameLineTimecodes.length - 1]
  const newStart = lastTimecode.end + MARGIN

  return { start: newStart, end: newStart + duration }
}

// Fonction pour ajuster les bornes d'un timecode modifié
function adjustTimecodeForModal(
  newStart: number,
  newEnd: number,
  lineNumber: number,
  excludeTimecodeId?: number,
): { start: number; end: number } {
  const MARGIN = 0.1 // Marge de sécurité de 0.1 seconde

  // Récupère tous les timecodes de la même ligne, sauf celui qu'on exclut
  const sameLineTimecodes = allTimecodes.value
    .filter((tc) => tc.line_number === lineNumber && tc.id !== excludeTimecodeId)
    .sort((a, b) => a.start - b.start)

  let adjustedStart = newStart
  let adjustedEnd = newEnd

  // Trouve les timecodes qui pourraient être en conflit
  const conflictingBefore = sameLineTimecodes.filter(
    (tc) => tc.end > adjustedStart - MARGIN && tc.start < adjustedStart,
  )
  const conflictingAfter = sameLineTimecodes.filter(
    (tc) => tc.start < adjustedEnd + MARGIN && tc.end > adjustedEnd,
  )

  // Ajuster le début si conflit avec un timecode précédent
  if (conflictingBefore.length > 0) {
    const lastConflict = conflictingBefore[conflictingBefore.length - 1]
    adjustedStart = lastConflict.end + MARGIN
  }

  // Ajuster la fin si conflit avec un timecode suivant
  if (conflictingAfter.length > 0) {
    const firstConflict = conflictingAfter[0]
    adjustedEnd = firstConflict.start - MARGIN
  }

  // Recalculer la fin selon le nouveau début si nécessaire
  const originalDuration = newEnd - newStart
  if (adjustedStart !== newStart) {
    adjustedEnd = adjustedStart + originalDuration

    // Vérifier à nouveau les conflits après
    const stillConflictingAfter = sameLineTimecodes.find(
      (tc) => tc.start < adjustedEnd + MARGIN && tc.end > adjustedEnd,
    )
    if (stillConflictingAfter) {
      adjustedEnd = stillConflictingAfter.start - MARGIN
    }
  }

  // S'assurer que start < end avec une durée minimale
  if (adjustedStart >= adjustedEnd) {
    adjustedEnd = adjustedStart + Math.max(0.5, originalDuration)
  }

  return { start: adjustedStart, end: adjustedEnd }
}

// Vérifier si l'utilisateur a accès au projet (lecture)
const hasProjectAccess = computed(() => {
  if (!project.value || !authStore.user) return false

  // Admin global a toujours accès
  if (authStore.isAdmin) return true

  // Propriétaire du projet a accès
  if (project.value.user_id === authStore.user.id) return true

  // Collaborateur a accès
  if (project.value.collaborators && authStore.user) {
    return project.value.collaborators.some((collab) => collab.id === authStore.user!.id)
  }

  return false
})

// Vérifier si l'utilisateur peut gérer le projet
const canManageProject = computed(() => {
  if (!project.value || !authStore.user) return false

  // Admin global ou propriétaire du projet
  if (authStore.isAdmin || project.value.user_id === authStore.user.id) {
    return true
  }

  // Collaborateur avec permission 'admin'
  if (project.value.collaborators && authStore.user) {
    const userCollaborator = project.value.collaborators.find(
      (collab) => collab.id === authStore.user!.id,
    )
    if (userCollaborator) {
      // Support des deux structures possibles: permission directe ou dans pivot
      const permission = userCollaborator.permission || userCollaborator.pivot?.permission
      return permission === 'admin'
    }
  }

  return false
})

// Conversion temporaire des anciens timecodes en format compatible
const compatibleTimecodes = computed(() => {
  // Protection contre les états de chargement
  if (loading.value) return []

  // Utilise d'abord les nouveaux timecodes de l'API
  if (allTimecodes.value.length > 0) {
    // Filtrer les doublons par ID au cas où il y en aurait
    const uniqueTimecodes = allTimecodes.value.filter(
      (tc, index, array) => tc.id != null && array.findIndex((t) => t.id === tc.id) === index,
    )
    return uniqueTimecodes
  }

  // Fallback sur les anciens timecodes (JSON) s'il n'y en a pas dans la nouvelle table
  if (!project.value?.timecodes) {
    return []
  }

  let oldTimecodes = project.value.timecodes
  if (typeof oldTimecodes === 'string') {
    try {
      oldTimecodes = JSON.parse(oldTimecodes)
    } catch (error) {
      console.error('Failed to parse timecodes JSON:', error)
      oldTimecodes = []
    }
  }

  if (!Array.isArray(oldTimecodes)) {
    console.error('oldTimecodes is not an array:', oldTimecodes)
    return []
  }

  // Convertit en format ApiTimecode avec line_number = 1 par défaut
  return oldTimecodes.map((tc, index) => {
    // Crée un ID temporaire vraiment unique basé sur les propriétés du timecode
    const hash = Math.abs(
      project.value!.id * 1000000 +
        index * 10000 +
        Math.round(tc.start * 100) +
        Math.round(tc.end * 100),
    )
    return {
      id: hash + 100000, // Décale pour éviter les conflits avec les vrais IDs
      project_id: project.value!.id,
      line_number: 1, // Tous sur la ligne 1 par défaut
      start: tc.start,
      end: tc.end,
      text: tc.text,
    }
  })
})

// Fonctions pour les timecodes multi-lignes
async function loadTimecodes() {
  if (!project.value) return
  try {
    const res = await timecodeApi.getAll(project.value.id)
    allTimecodes.value = res.data.timecodes
  } catch (error) {
    console.error('Error loading timecodes:', error)
    allTimecodes.value = []
  }
}

// Chargement des personnages
async function loadCharacters() {
  if (!project.value) return
  try {
    const res = await characterApi.getAll(project.value.id)
    allCharacters.value = res.data.characters
    // Sélectionner automatiquement le premier personnage comme actif
    if (allCharacters.value.length > 0 && !activeCharacter.value) {
      activeCharacter.value = allCharacters.value[0]
    }
  } catch (error) {
    console.error('Error loading characters:', error)
    allCharacters.value = []
  }
}

// Computed property pour le timecode sélectionné
const selectedTimecode = computed(() => {
  if (selectedTimecodeIdx.value === null) return null
  return compatibleTimecodes.value[selectedTimecodeIdx.value] || null
})

// Fonctions pour la nouvelle interface TimecodesListMultiLine
function selectTimecode(timecode: Timecode) {
  const idx = compatibleTimecodes.value.findIndex(
    (tc) =>
      tc.id === timecode.id ||
      (tc.start === timecode.start && tc.end === timecode.end && tc.text === timecode.text),
  )
  if (idx >= 0) {
    onSelectTimecode(idx)
  }
}

function editTimecode(timecode: Timecode) {
  const idx = compatibleTimecodes.value.findIndex(
    (tc) =>
      tc.id === timecode.id ||
      (tc.start === timecode.start && tc.end === timecode.end && tc.text === timecode.text),
  )
  if (idx >= 0) {
    onEditTimecode(idx)
  }
}

function deleteTimecode(timecode: Timecode) {
  onDeleteTimecode({ timecode })
}

function addTimecodeToLine(lineNumber: number) {
  // Ouvre le modal avec la ligne pré-sélectionnée
  // Applique le décalage de compensation (FRAME_OFFSET frames en arrière)
  const adjustedTime = Math.max(0, currentTime.value - FRAME_OFFSET / FPS)

  editTimecodeIdx.value = null
  Object.assign(modalTimecode, {
    start: adjustedTime,
    end: adjustedTime + 2,
    text: '',
    line_number: lineNumber,
    character_id: activeCharacter.value?.id || null,
  })
  showTimecodeModal.value = true
}

function onAddTimecodeToLine() {
  // TODO: Ouvrir modal pour ajouter timecode sur cette ligne
}

async function onUpdateLinesCount(count: number) {
  if (!project.value) return
  try {
    await api.patch(`/projects/${project.value.id}/rythmo-lines`, { rythmo_lines_count: count })
    project.value.rythmo_lines_count = count
  } catch (error) {
    console.error('Erreur lors de la mise à jour du nombre de lignes:', error)
  }
}

// ===== GESTION DES PERSONNAGES =====

function onCharacterSelected(character: Character) {
  activeCharacter.value = character
}

function onAddCharacter() {
  editingCharacter.value = null
  showCharacterModal.value = true
}

function onEditCharacter(character: Character) {
  editingCharacter.value = character
  showCharacterModal.value = true
}

function onCharacterSaved(character: Character) {
  showCharacterModal.value = false
  editingCharacter.value = null

  // Ajouter ou mettre à jour le personnage dans la liste
  const existingIndex = allCharacters.value.findIndex((c) => c.id === character.id)
  if (existingIndex >= 0) {
    allCharacters.value[existingIndex] = character
  } else {
    allCharacters.value.push(character)
  }

  // Si c'est le premier personnage ou pas de personnage actif, le sélectionner
  if (!activeCharacter.value || allCharacters.value.length === 1) {
    activeCharacter.value = character
  }

  // Recharger les timecodes pour mettre à jour les références des personnages
  loadTimecodes().then(() => {
    // Rafraîchir les bandes rythmo pour afficher les nouvelles couleurs
    refreshRythmoBands()
  })
}

function onCharacterDeleted(characterId: number) {
  allCharacters.value = allCharacters.value.filter((c) => c.id !== characterId)

  // Si le personnage supprimé était actif, sélectionner le premier disponible
  if (activeCharacter.value?.id === characterId) {
    activeCharacter.value = allCharacters.value[0] || null
  }

  // Recharger les timecodes pour refléter les changements
  loadTimecodes()
}

function closeCharacterModal() {
  showCharacterModal.value = false
  editingCharacter.value = null
}

// Gestionnaire pour les changements de ligne sélectionnée
function onSelectedLineChanged(lineNumber: number) {
  selectedLineNumber.value = lineNumber
}

// Nouvelle fonction onUpdateTimecode pour le nouveau format
async function onUpdateTimecode({
  timecode,
  text,
}: {
  timecode: ApiTimecode | Timecode
  text: string
}) {
  const tc = timecode as ApiTimecode
  if (!tc.id || !project.value) return
  try {
    await timecodeApi.update(project.value.id, tc.id, { text })
    // Met à jour localement
    const index = allTimecodes.value.findIndex((t) => t.id === tc.id)
    if (index >= 0) {
      allTimecodes.value[index].text = text
    }
  } catch {
    // TODO: gestion d'erreur
  }
}

// Nouvelle fonction pour le redimensionnement des timecodes
async function onUpdateTimecodeBounds({
  timecode,
  start,
  end,
}: {
  timecode: ApiTimecode | Timecode
  start: number
  end: number
}) {
  const tc = timecode as ApiTimecode
  if (!tc.id || !project.value) return
  try {
    await timecodeApi.update(project.value.id, tc.id, { start, end })
    // Met à jour localement
    const index = allTimecodes.value.findIndex((t) => t.id === tc.id)
    if (index >= 0) {
      allTimecodes.value[index].start = start
      allTimecodes.value[index].end = end
    }
  } catch (error) {
    console.error('Erreur lors de la mise à jour des bornes du timecode:', error)
  }
}

// Nouvelle fonction pour le déplacement des timecodes
async function onMoveTimecode({
  timecode,
  newStart,
  newLineNumber,
}: {
  timecode: ApiTimecode | Timecode
  newStart: number
  newLineNumber: number
}) {
  const tc = timecode as ApiTimecode
  if (!tc.id || !project.value) return

  try {
    // Calcule la nouvelle fin en gardant la même durée
    const duration = tc.end - tc.start
    const newEnd = newStart + duration

    await timecodeApi.update(project.value.id, tc.id, {
      start: newStart,
      end: newEnd,
      line_number: newLineNumber,
    })

    // Met à jour localement
    const index = allTimecodes.value.findIndex((t) => t.id === tc.id)
    if (index >= 0) {
      allTimecodes.value[index].start = newStart
      allTimecodes.value[index].end = newEnd
      allTimecodes.value[index].line_number = newLineNumber
    }
  } catch (error) {
    console.error('Erreur lors du déplacement du timecode:', error)
  }
}

// Nouvelle fonction pour basculer l'affichage du personnage
async function onUpdateTimecodeShowCharacter({
  timecode,
  showCharacter,
}: {
  timecode: ApiTimecode | Timecode
  showCharacter: boolean
}) {
  const tc = timecode as ApiTimecode
  if (!tc.id || !project.value) return

  try {
    await timecodeApi.update(project.value.id, tc.id, { show_character: showCharacter })

    // Met à jour localement
    const index = allTimecodes.value.findIndex((t) => t.id === tc.id)
    if (index >= 0) {
      allTimecodes.value[index].show_character = showCharacter
    }
  } catch (error) {
    console.error("Erreur lors de la mise à jour de l'affichage du personnage:", error)
  }
}

// Nouvelle fonction pour changer le personnage d'un timecode
async function onUpdateTimecodeCharacter({
  timecode,
  characterId,
}: {
  timecode: ApiTimecode | Timecode
  characterId: number | null
}) {
  const tc = timecode as ApiTimecode
  if (!tc.id || !project.value) return

  try {
    await timecodeApi.update(project.value.id, tc.id, { character_id: characterId })

    // Met à jour localement
    const index = allTimecodes.value.findIndex((t) => t.id === tc.id)
    if (index >= 0) {
      allTimecodes.value[index].character_id = characterId
      // Trouve et assigne l'objet character complet
      if (characterId) {
        allTimecodes.value[index].character = allCharacters.value.find((c) => c.id === characterId)
      } else {
        allTimecodes.value[index].character = undefined
      }
    }
  } catch (error) {
    console.error('Erreur lors de la mise à jour du personnage du timecode:', error)
  }
}

// Nouvelle fonction pour sauvegarder les positions des séparateurs
async function onUpdateTimecodeSeparatorPositions({
  timecode,
  separatorPositions,
}: {
  timecode: ApiTimecode | Timecode
  separatorPositions: Record<number, number>
}) {
  const tc = timecode as ApiTimecode
  if (!tc.id || !project.value) return

  try {
    await timecodeApi.update(project.value.id, tc.id, { separator_positions: separatorPositions })

    // Met à jour localement
    const index = allTimecodes.value.findIndex((t) => t.id === tc.id)
    if (index >= 0) {
      allTimecodes.value[index].separator_positions = separatorPositions
    }
  } catch (error) {
    console.error('Erreur lors de la mise à jour des positions des séparateurs:', error)
  }
}

// Ajout d'un changement de plan au timecode courant
async function addSceneChange() {
  if (!project.value) return
  // Applique le décalage de compensation (FRAME_OFFSET frames en arrière)
  const adjustedTime = Math.max(0, currentTime.value - FRAME_OFFSET / FPS)
  const t = Math.round(adjustedTime * 100) / 100
  // Vérifie si déjà présent (tolérance 0.01s)
  if (sceneChanges.value.some((sc) => Math.abs(sc.timecode - t) < 0.01)) return
  try {
    const res = await api.post(`/projects/${project.value.id}/scene-changes`, { timecode: t })
    sceneChanges.value.push(res.data)
    sceneChanges.value.sort((a, b) => a.timecode - b.timecode)
  } catch {
    // TODO: gestion d'erreur
  }
}

// ===== GESTION DE L'ANALYSE IA DES CHANGEMENTS DE PLAN =====

/**
 * Démarrer l'analyse automatique de détection de plans
 */
async function handleStartAnalysis() {
  if (!project.value || hasSceneChanges.value) return

  // Fermer le menu IA et afficher la modale de paramètres
  showAiMenu.value = false
  showAnalysisSettings.value = true
}

/**
 * Confirmer et lancer l'analyse avec les paramètres choisis
 */
async function handleAnalysisConfirm(params: { fps: number; threshold: number }) {
  if (!project.value || hasSceneChanges.value) return

  try {
    // Lancer l'analyse avec les paramètres
    await startSceneAnalysis(project.value.id, params)

    // Afficher le modal de chargement
    isAnalyzing.value = true
    showAnalysisModal.value = true
    analysisStatus.value = 'pending'
    analysisProgress.value = 0
    analysisMessage.value = ''

    // Démarrer le polling du statut
    startAnalysisPolling()
  } catch (error) {
    console.error('Erreur lors du lancement de l\'analyse:', error)
    isAnalyzing.value = false
    showAnalysisModal.value = false
    notificationService.error('Erreur', 'Erreur lors du lancement de l\'analyse. Veuillez réessayer.')
  }
}

/**
 * Annuler l'analyse en cours
 */
async function handleCancelAnalysis() {
  if (!project.value) return

  // Afficher le dialog de confirmation
  showCancelConfirm.value = true
  pendingCancelAction.value = async () => {
    try {
      // Appeler l'API pour annuler
      await cancelSceneAnalysis(project.value!.id)

      // Arrêter le polling
      stopAnalysisPolling()

      // Fermer le modal
      isAnalyzing.value = false
      showAnalysisModal.value = false
      analysisStatus.value = 'cancelled'
      analysisProgress.value = 0
      analysisMessage.value = 'Analyse annulée'

      // Notification
      notificationService.success('Succès', 'Analyse annulée avec succès')
    } catch (error) {
      console.error('Erreur lors de l\'annulation de l\'analyse:', error)
      notificationService.error('Erreur', 'Erreur lors de l\'annulation de l\'analyse. Elle pourrait déjà être terminée.')
    }
  }
}

/**
 * Démarrer le polling pour vérifier le statut de l'analyse
 */
function startAnalysisPolling() {
  if (!project.value) return

  // Nettoyer l'intervalle existant si présent
  if (analysisPollingInterval !== null) {
    clearInterval(analysisPollingInterval)
  }

  // Polling toutes les 2 secondes
  analysisPollingInterval = window.setInterval(async () => {
    if (!project.value) {
      stopAnalysisPolling()
      return
    }

    try {
      const status = await getAnalysisStatus(project.value.id)
      analysisStatus.value = status.analysis_status
      analysisProgress.value = status.analysis_progress || 0
      analysisMessage.value = status.analysis_message || ''

      // Si l'analyse est terminée ou a échoué
      if (status.analysis_status === 'completed' || status.analysis_status === 'failed') {
        stopAnalysisPolling()
        isAnalyzing.value = false
        showAnalysisModal.value = false

        if (status.analysis_status === 'completed') {
          // Recharger les scene changes
          const scResponse = await api.get(`/projects/${project.value.id}/scene-changes`)
          sceneChanges.value = scResponse.data

          // Rafraîchir les bandes rythmo
          refreshRythmoBands()

          // Notification de succès
          notificationService.success('Succès', `Analyse terminée ! ${status.scene_changes_count} changement(s) de plan détecté(s).`)
        } else {
          // Notification d'échec
          notificationService.error('Échec', status.analysis_message || 'L\'analyse a échoué. Veuillez vérifier les logs ou réessayer.')
        }
      }
    } catch (error) {
      console.error('Erreur lors de la vérification du statut:', error)
      stopAnalysisPolling()
      isAnalyzing.value = false
      showAnalysisModal.value = false
    }
  }, 2000)
}

/**
 * Arrêter le polling
 */
function stopAnalysisPolling() {
  if (analysisPollingInterval !== null) {
    clearInterval(analysisPollingInterval)
    analysisPollingInterval = null
  }
}

// ======== Extraction de dialogues IA ========

/**
 * Ouvrir la modale de paramètres pour l'extraction de dialogues
 */
async function handleStartDialogueExtraction() {
  if (!project.value || hasTimecodes.value) return

  // Fermer le menu IA et afficher la modale de paramètres
  showAiMenu.value = false
  showDialogueExtractionSettings.value = true
}

/**
 * Lancer l'extraction de dialogues avec les paramètres choisis
 */
async function handleDialogueExtractionStart(options: DialogueExtractionOptions) {
  if (!project.value || hasTimecodes.value) return

  try {
    // Lancer l'extraction avec les paramètres
    await startDialogueExtraction(project.value.id, options)

    // Fermer la modale de paramètres et afficher celle de progression
    showDialogueExtractionSettings.value = false
    showDialogueExtractionProgress.value = true

    // Notification de lancement
    notificationService.success('Extraction lancée', 'L\'extraction des dialogues a démarré. Cela peut prendre plusieurs minutes.')
  } catch (error) {
    console.error('Erreur lors du lancement de l\'extraction de dialogues:', error)
    notificationService.error('Erreur', 'Erreur lors du lancement de l\'extraction. Veuillez réessayer.')
  }
}

/**
 * Extraction de dialogues terminée avec succès
 */
async function handleDialogueExtractionCompleted(result?: {
  timecodes_count?: number
  characters_count?: number
  source_language?: string
}) {
  console.log('🎯 handleDialogueExtractionCompleted called with result:', result)

  showDialogueExtractionProgress.value = false

  // Stocker le résultat pour le modal
  if (result && result.timecodes_count !== undefined && result.characters_count !== undefined) {
    console.log('✅ Result valid, recharging data...')

    // Recharger les timecodes et personnages AVANT d'ouvrir le modal
    await loadTimecodes()
    await loadCharacters()
    refreshRythmoBands()

    dialogueExtractionResult.value = {
      timecodes_count: result.timecodes_count || 0,
      characters_count: result.characters_count || 0,
      source_language: result.source_language,
    }

    // Ouvrir le modal de post-traitement
    showDialogueExtractionResult.value = true
    console.log('✅ Modal opened with data loaded')
  } else {
    // Si pas de résultat valide, juste recharger les données
    console.log('⚠️ No valid result, reloading data anyway')
    await loadTimecodes()
    await loadCharacters()
    refreshRythmoBands()
  }
}

/**
 * Lancer la traduction directement depuis le modal de résultat
 */
async function handleTranslateFromExtraction(options: {
  source_language: string
  target_language: string
}) {
  // Fermer le modal de résultat
  showDialogueExtractionResult.value = false

  // Lancer la traduction directement
  await startTranslation(project.value!.id, {
    target_language: options.target_language,
    source_language: options.source_language,
    use_character_context: true,
  })

  // Ouvrir la progress modal
  showTranslationProgress.value = true

  notificationService.success(
    'Traduction lancée',
    `Traduction vers ${options.target_language.toUpperCase()} en cours...`,
  )
}

/**
 * Gestion de la fusion de personnages
 */
async function handleCharactersMerged(data: { characterIds: number[]; mergedName: string }) {
  if (!project.value) return

  try {
    // Convertir camelCase → snake_case pour l'API
    const response = await characterApi.merge({
      character_ids: data.characterIds,
      merged_name: data.mergedName
    })

    // Afficher notification de succès
    notificationService.success(
      'Personnages fusionnés',
      `${response.data.characters_deleted} personnages fusionnés en "${response.data.merged_character.name}". ${response.data.timecodes_reassigned} dialogues réassignés.`
    )

    // Recharger les personnages et timecodes
    await loadCharacters()
    await loadTimecodes()

    // Recharger les personnages dans le modal de résultat (si ouvert)
    if (dialogueExtractionResultModalRef.value) {
      await dialogueExtractionResultModalRef.value.loadCharacters()
    }

    // Fermer le modal
    showCharacterMergeModal.value = false

    // Rafraîchir les bandes rythmo
    refreshRythmoBands()
  } catch (error) {
    console.error('Erreur lors de la fusion des personnages:', error)
    notificationService.error('Erreur', 'Erreur lors de la fusion des personnages')
  }
}

/**
 * Extraction de dialogues échouée
 */
function handleDialogueExtractionFailed(message: string) {
  showDialogueExtractionProgress.value = false
  notificationService.error('Échec', message || 'L\'extraction des dialogues a échoué. Veuillez réessayer.')
}

/**
 * Extraction de dialogues annulée
 */
function handleDialogueExtractionCancelled() {
  showDialogueExtractionProgress.value = false
  notificationService.success('Annulée', 'L\'extraction des dialogues a été annulée.')
}

/**
 * Ouvrir la modale de paramètres pour la traduction
 */
async function handleStartTranslation() {
  if (!project.value || !hasTimecodes.value) return

  // Fermer le menu IA et afficher la modale de paramètres
  showAiMenu.value = false
  showTranslationSettings.value = true
}

/**
 * Lancer la traduction avec les paramètres choisis
 */
async function handleTranslationStart(options: import('@/api/translation').TranslationOptions) {
  if (!project.value || !hasTimecodes.value) return

  try {
    // Lancer la traduction avec les paramètres
    await import('@/api/translation').then(api => api.startTranslation(project.value!.id, options))

    // Fermer la modale de paramètres et afficher celle de progression
    showTranslationSettings.value = false
    showTranslationProgress.value = true

    // Notification de lancement
    notificationService.success('Traduction lancée', 'La traduction des dialogues a démarré. Cela peut prendre plusieurs minutes.')
  } catch (error) {
    console.error('Erreur lors du lancement de la traduction:', error)
    notificationService.error('Erreur', 'Erreur lors du lancement de la traduction. Veuillez réessayer.')
  }
}

/**
 * Traduction terminée avec succès
 */
async function handleTranslationCompleted() {
  showTranslationProgress.value = false

  // Recharger les timecodes
  await loadTimecodes()

  // Rafraîchir les bandes rythmo
  refreshRythmoBands()

  // Notification de succès
  notificationService.success('Succès', 'La traduction des dialogues est terminée !')
}

/**
 * Traduction échouée
 */
function handleTranslationFailed(message: string) {
  showTranslationProgress.value = false
  notificationService.error('Échec', message || 'La traduction des dialogues a échoué. Veuillez réessayer.')
}

/**
 * Traduction annulée
 */
function handleTranslationCancelled() {
  showTranslationProgress.value = false
  notificationService.success('Annulée', 'La traduction des dialogues a été annulée.')
}

// Modal d'édition/ajout de timecode
const showTimecodeModal = ref(false)
const editTimecodeIdx = ref<number | null>(null)
const modalTimecode = reactive<Timecode>({
  start: 0,
  end: 0,
  text: '',
  line_number: 1,
  character_id: null,
})

// Modal d'édition de changement de plan
const showSceneChangeModal = ref(false)
const editSceneChangeIdx = ref<number | null>(null)

function getVideoUrl(path?: string) {
  if (!path) return ''
  if (path.startsWith('http')) return path
  const apiBase = import.meta.env.VITE_API_URL?.replace(/\/api\/?$/, '') || ''
  return `${apiBase}/api/videos/${encodeURIComponent(path)}`
}

function onVideoTimeUpdate(time: number) {
  // Throttle des mises à jour pour améliorer les performances mobile
  const now = Date.now()
  if (now - lastUpdateTime < UPDATE_THROTTLE) {
    return
  }
  lastUpdateTime = now

  // Si le seek vient d'un clic sur timecode, on ignore le premier event
  if (lastSeekFromTimecode) {
    lastSeekFromTimecode = false
    return
  }
  currentTime.value = time

  // Sélectionne le timecode courant (optimisé)
  if (compatibleTimecodes.value.length > 0) {
    const idx = compatibleTimecodes.value.findIndex((tc) => time >= tc.start && time < tc.end)
    selectedTimecodeIdx.value = idx >= 0 ? idx : null
  }

  // Si la vidéo joue, smooth, sinon instantané
  const videoEl = document.querySelector('video') as HTMLVideoElement | null
  instantRythmoScroll.value = !videoEl || videoEl.paused
}
function onLoadedMetadata(duration: number) {
  videoDuration.value = duration
  // Ne mettre à false que si c'est actuellement true (évite les problèmes de timing)
  if (isVideoLoading.value) {
    isVideoLoading.value = false
  }

  // Initialiser le mixer audio une fois la vidéo chargée
  const videoEl = document.querySelector('video') as HTMLVideoElement | null
  if (videoEl && !audioMixer) {
    audioMixer = useVideoAudioMixer(videoEl, instrumentalAudioUrl, muteVocals)
    audioMixer.init()
  }
}

function onVideoCanPlay() {
  // Sur mobile, canplay est souvent plus fiable que loadedmetadata
  if (isVideoLoading.value) {
    isVideoLoading.value = false
  }
}

function onVideoCanPlayThrough() {
  // La vidéo a assez de données pour jouer sans interruption
  if (isVideoLoading.value) {
    isVideoLoading.value = false
  }
  // Arrêter le buffering de manière sécurisée
  endBuffering()
}

function onVideoLoadedData() {
  // loadeddata signifie que les données sont prêtes
  if (isVideoLoading.value) {
    isVideoLoading.value = false
  }
  // Arrêter le buffering si la vidéo est prête
  endBuffering()
}

function onVideoSeeking() {
  // La vidéo cherche une nouvelle position
  startBuffering()
  isSeeking.value = true
}

function onVideoSeeked() {
  // La vidéo a trouvé la position - utiliser debounce pour éviter les fluctuations
  if (seekingTimeout !== null) {
    clearTimeout(seekingTimeout)
  }

  seekingTimeout = window.setTimeout(() => {
    isSeeking.value = false
    endBuffering()
    seekingTimeout = null
  }, SEEKING_DEBOUNCE)
}

function onVideoWaiting() {
  // La vidéo attend des données
  startBuffering()
}

function onVideoPlaying() {
  // La vidéo joue - arrêter le buffering de manière sécurisée
  endBuffering()
}

function onVideoStalled() {
  // Le téléchargement est bloqué
  startBuffering()
}

function onVideoError(error: MediaError | null) {
  // Erreur de chargement vidéo - afficher le buffering
  startBuffering()

  // Retry automatique pour les erreurs réseau (code 2)
  if (error?.code === 2) {
    setTimeout(() => {
      const videoEl = document.querySelector('video') as HTMLVideoElement | null
      if (videoEl) {
        videoEl.load()
      }
    }, 2000)
  }
}

function seek(delta: number) {
  const videoEl = document.querySelector('video') as HTMLVideoElement | null

  if (delta === 0) {
    // Toggle play/pause
    if (videoEl) {
      if (videoEl.paused) {
        startBuffering()
        videoEl.play().catch(() => {
          // Si échec, on réessaie après avoir forcé le chargement
          videoEl.load()
          forceEndBuffering()
        })
        instantRythmoScroll.value = false
      } else {
        videoEl.pause()
        instantRythmoScroll.value = true
        forceEndBuffering()
      }
      isVideoPaused.value = videoEl.paused
    }
    return
  }

  // Avance/recul d'une seconde
  isSeeking.value = true
  startBuffering()

  let t = currentTime.value + delta
  t = Math.max(0, Math.min(videoDuration.value, t))
  currentTime.value = t

  if (videoEl) {
    videoEl.currentTime = t
  }
  instantRythmoScroll.value = true
}

function seekFrame(delta: number) {
  isSeeking.value = true
  startBuffering()

  const frameDuration = 1 / videoFps.value
  let t = currentTime.value + delta * frameDuration
  t = Math.max(0, Math.min(videoDuration.value, t))
  currentTime.value = t

  const videoEl = document.querySelector('video') as HTMLVideoElement | null
  if (videoEl) {
    videoEl.currentTime = t
  }
  instantRythmoScroll.value = true
}

function updatePlayPauseState() {
  // Met à jour l'état isVideoPaused pour synchroniser l'interface
  const videoEl = document.querySelector('video') as HTMLVideoElement | null
  if (videoEl) {
    isVideoPaused.value = videoEl.paused
    instantRythmoScroll.value = videoEl.paused
  }
}
function onSelectTimecode(idx: number) {
  selectedTimecodeIdx.value = idx
  // Seek vidéo si possible
  const tc = compatibleTimecodes.value[idx]
  if (tc) {
    lastSeekFromTimecode = true
    currentTime.value = tc.start
    // Scroll instantané lors d'un seek manuel
    instantRythmoScroll.value = true
  }
}
function onEditTimecode(idx: number) {
  editTimecodeIdx.value = idx
  const tc = compatibleTimecodes.value[idx]
  if (tc) {
    Object.assign(modalTimecode, {
      start: tc.start,
      end: tc.end,
      text: tc.text,
      line_number: tc.line_number || 1,
    })
  }
  showTimecodeModal.value = true
}
async function onAddTimecode() {
  if (!project.value) return

  try {
    // Applique le décalage de compensation (FRAME_OFFSET frames en arrière)
    const adjustedTime = Math.max(0, currentTime.value - FRAME_OFFSET / FPS)

    // Trouver une position libre pour le nouveau timecode
    const freePosition = findFreeTimecodePosition(
      adjustedTime,
      6, // durée de 6 secondes
      selectedLineNumber.value,
    )

    // Créer un nouveau timecode de 6 secondes directement dans la base
    const newTimecodeData = {
      start: freePosition.start,
      end: freePosition.end,
      text: 'Insérer du texte ici',
      line_number: selectedLineNumber.value, // Utilise la ligne actuellement sélectionnée
      character_id: activeCharacter.value?.id || null,
      show_character: !!activeCharacter.value,
    }

    await timecodeApi.create(project.value.id, newTimecodeData)

    // Recharger les timecodes pour récupérer le nouveau
    await loadTimecodes()

    // Le timecode est créé avec le texte "Insérer du texte ici"
    // L'utilisateur peut double-cliquer dessus pour l'éditer
  } catch (error) {
    console.error('Erreur lors de la création du timecode:', error)
  }
}
async function onDeleteTimecode(payload: { timecode: Timecode }) {
  if (!project.value) return

  const timecodeToDelete = payload.timecode
  if (!timecodeToDelete?.id) return

  try {
    await timecodeApi.delete(project.value.id, timecodeToDelete.id)
    // Recharger les timecodes
    await loadTimecodes()
  } catch (error) {
    console.error('Erreur lors de la suppression du timecode:', error)
  }
}
// Callback pour la soumission du modal de timecode
function onTimecodeModalSubmit(data: {
  line_number: number
  start: number
  end: number
  text: string
}) {
  if (!project.value) return

  // Si editTimecodeIdx !== null, on met à jour le timecode existant
  if (editTimecodeIdx.value !== null) {
    const tc = compatibleTimecodes.value[editTimecodeIdx.value]
    if (tc?.id) {
      // Ajuster les bornes pour éviter les superpositions
      const adjustedBounds = adjustTimecodeForModal(data.start, data.end, data.line_number, tc.id)

      const adjustedData = {
        ...data,
        start: adjustedBounds.start,
        end: adjustedBounds.end,
      }

      timecodeApi.update(project.value.id, tc.id, adjustedData).then(() => {
        loadTimecodes()
        closeTimecodeModal()
      })
    }
    return
  }

  // Pour la création, trouver une position libre
  const freePosition = findFreeTimecodePosition(data.start, data.end - data.start, data.line_number)

  const adjustedData = {
    ...data,
    start: freePosition.start,
    end: freePosition.end,
  }

  // Sinon on crée un nouveau timecode
  timecodeApi.create(project.value.id, adjustedData).then(() => {
    loadTimecodes()
    closeTimecodeModal()
  })
}
function closeTimecodeModal() {
  showTimecodeModal.value = false
}

// Gestionnaire pour l'import SRT réussi
async function onSrtImported(count: number) {
  console.log(`${count} timecode(s) importé(s) avec succès`)

  // Recharger les timecodes depuis le backend
  await loadTimecodes()

  // Rafraîchir les bandes rythmo pour afficher les nouveaux timecodes
  refreshRythmoBands()
}

// Gestion du modal de changement de plan
async function onSceneChangeModalSubmit(newTimecode: number) {
  if (editSceneChangeIdx.value === null || !project.value) return

  const sceneChange = sceneChanges.value[editSceneChangeIdx.value]
  if (!sceneChange) return

  try {
    const updatedSceneChange = await sceneChangesApi.updateSceneChange(sceneChange.id, {
      timecode: newTimecode,
    })

    // Mettre à jour dans la liste locale
    sceneChanges.value[editSceneChangeIdx.value] = updatedSceneChange
    sceneChanges.value.sort((a, b) => a.timecode - b.timecode)

    closeSceneChangeModal()
  } catch (error) {
    console.error('Erreur lors de la mise à jour du changement de plan:', error)
  }
}

function closeSceneChangeModal() {
  showSceneChangeModal.value = false
  editSceneChangeIdx.value = null
}

// Navigation entre changements de plan
function goToNextSceneChange() {
  if (!uniqueSceneChangeTimecodes.value.length) return

  const sortedSceneChanges = [...uniqueSceneChangeTimecodes.value].sort((a, b) => a - b)
  const currentIdx = sortedSceneChanges.findIndex((sc) => sc > currentTime.value)

  if (currentIdx !== -1) {
    // Aller au prochain changement de plan
    seekToTime(sortedSceneChanges[currentIdx])
  } else if (sortedSceneChanges.length > 0) {
    // Si on est après le dernier, aller au premier
    seekToTime(sortedSceneChanges[0])
  }
}

function goToPreviousSceneChange() {
  if (!uniqueSceneChangeTimecodes.value.length) return

  const sortedSceneChanges = [...uniqueSceneChangeTimecodes.value].sort((a, b) => a - b)
  // Trouver le dernier changement de plan avant le temps actuel (alternative à findLastIndex)
  let currentIdx = -1
  for (let i = sortedSceneChanges.length - 1; i >= 0; i--) {
    if (sortedSceneChanges[i] < currentTime.value) {
      currentIdx = i
      break
    }
  }

  if (currentIdx !== -1) {
    // Aller au changement de plan précédent
    seekToTime(sortedSceneChanges[currentIdx])
  } else if (sortedSceneChanges.length > 0) {
    // Si on est avant le premier, aller au dernier
    seekToTime(sortedSceneChanges[sortedSceneChanges.length - 1])
  }
}

// Navigation entre timecodes
function goToNextTimecode() {
  if (!allTimecodes.value.length) return

  const sortedTimecodes = [...allTimecodes.value].sort((a, b) => a.start - b.start)
  const currentIdx = sortedTimecodes.findIndex((tc) => tc.start > currentTime.value)

  if (currentIdx !== -1) {
    // Aller au prochain timecode
    seekToTime(sortedTimecodes[currentIdx].start)
  }
  // Ne plus boucler au début si on est au dernier
}

function goToPreviousTimecode() {
  if (!allTimecodes.value.length) return

  const sortedTimecodes = [...allTimecodes.value].sort((a, b) => a.start - b.start)
  // Trouver le dernier timecode avant le temps actuel avec une tolérance plus grande
  // pour gérer correctement la compensation de frames
  const tolerance = 0.5 // Augmenté à 0.5s pour une meilleure détection
  let currentIdx = -1
  for (let i = sortedTimecodes.length - 1; i >= 0; i--) {
    if (sortedTimecodes[i].start < currentTime.value - tolerance) {
      currentIdx = i
      break
    }
  }

  if (currentIdx !== -1) {
    // Aller au timecode précédent
    seekToTime(sortedTimecodes[currentIdx].start)
  }
  // Ne plus boucler à la fin si on est au premier
}

// Fonction helper pour seek avec compensation
function seekToTime(time: number) {
  // Appliquer la compensation FRAME_OFFSET pour la synchronisation
  const compensatedTime = time + FRAME_OFFSET / FPS
  currentTime.value = Math.max(0, compensatedTime)
  lastSeekFromTimecode = true
}

// Gestionnaires pour les événements de navigation depuis VideoNavigationBar
function onNavigateSceneChange(direction: 'next' | 'previous') {
  if (direction === 'next') {
    goToNextSceneChange()
  } else {
    goToPreviousSceneChange()
  }
}

function onNavigateTimecode(direction: 'next' | 'previous') {
  if (direction === 'next') {
    goToNextTimecode()
  } else {
    goToPreviousTimecode()
  }
}

// Gestion des raccourcis clavier globaux
function handleGlobalKeydown(event: KeyboardEvent) {
  // Si on édite du texte, on ignore tous les raccourcis globaux
  if (isEditingText.value) return
  const isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0
  const cmdKey = isMac ? event.metaKey : event.ctrlKey

  // Si le modal des raccourcis est ouvert, Échap le ferme
  if (event.key === 'Escape' && showKeyboardShortcuts.value) {
    event.preventDefault()
    showKeyboardShortcuts.value = false
    return
  }

  // , pour toggle les raccourcis (sans Cmd/Ctrl) - AZERTY friendly
  if (event.key === ',' && !cmdKey && !event.shiftKey && !event.altKey) {
    event.preventDefault()
    showKeyboardShortcuts.value = !showKeyboardShortcuts.value
    return
  }

  // Cmd/Ctrl + ? pour ouvrir les raccourcis (garde l'ancien raccourci aussi)
  if (cmdKey && event.key === '/') {
    event.preventDefault()
    showKeyboardShortcuts.value = true
    return
  }

  // T pour ajouter un timecode
  if (event.key === 't' && !cmdKey && !event.shiftKey && !event.altKey) {
    event.preventDefault()
    onAddTimecode()
    return
  }

  // S pour ajouter un changement de scène
  if (event.key === 's' && !cmdKey && !event.shiftKey && !event.altKey) {
    event.preventDefault()
    addSceneChange()
    return
  }

  // F pour aperçu final
  if (event.key === 'f' || event.key === 'F') {
    if (project.value && project.value.video_path && compatibleTimecodes.value.length > 0) {
      event.preventDefault()
      goToFinalPreview()
    }
    return
  }

  // Navigation entre changements de plan avec Shift + flèches gauche/droite
  if (event.shiftKey && event.key === 'ArrowLeft' && !cmdKey && !event.altKey) {
    event.preventDefault()
    goToPreviousSceneChange()
    return
  }

  if (event.shiftKey && event.key === 'ArrowRight' && !cmdKey && !event.altKey) {
    event.preventDefault()
    goToNextSceneChange()
    return
  }

  // Navigation entre timecodes avec flèches haut/bas
  if (event.key === 'ArrowUp' && !cmdKey && !event.shiftKey && !event.altKey) {
    event.preventDefault()
    goToPreviousTimecode()
    return
  }

  if (event.key === 'ArrowDown' && !cmdKey && !event.shiftKey && !event.altKey) {
    event.preventDefault()
    goToNextTimecode()
    return
  }
}

// Fonction pour charger les données du projet
async function loadProjectData() {
  loading.value = true
  loadingError.value = null

  // Timeout de sécurité : forcer la fin du loading après 30s
  const loadingTimeout = setTimeout(() => {
    if (loading.value) {
      console.error('Timeout de chargement du projet atteint (30s)')
      loading.value = false
      loadingError.value = 'Le chargement du projet prend trop de temps. Veuillez vérifier votre connexion.'
    }
  }, 30000)

  try {
    // Étape 1 : Charger le projet principal (critique)
    const res = await api.get(`/projects/${route.params.id}`)
    const data = res.data

    // Corrige le cas où timecodes est une string JSON
    if (typeof data.timecodes === 'string') {
      try {
        data.timecodes = JSON.parse(data.timecodes)
      } catch {
        data.timecodes = []
      }
    }

    // Si le backend fournit les fps, on les récupère
    if (typeof data.fps === 'number' && data.fps > 0) {
      videoFps.value = data.fps
    }

    project.value = data

    // Étape 2 : Charger les données dépendantes EN PARALLÈLE (non-bloquant)
    // Utilise Promise.allSettled pour ne pas bloquer si une requête échoue
    const [settingsResult, sceneChangesResult, timecodesResult, charactersResult] = await Promise.allSettled([
      settingsStore.loadSettings(data.id),
      api.get(`/projects/${data.id}/scene-changes`),
      loadTimecodes(),
      loadCharacters()
    ])

    // Traiter les résultats individuellement
    if (sceneChangesResult.status === 'fulfilled') {
      sceneChanges.value = Array.isArray(sceneChangesResult.value.data) ? sceneChangesResult.value.data : []
    } else {
      console.warn('Échec du chargement des scene changes:', sceneChangesResult.reason)
      sceneChanges.value = []
    }

    if (timecodesResult.status === 'rejected') {
      console.warn('Échec du chargement des timecodes:', timecodesResult.reason)
      allTimecodes.value = []
    }

    if (charactersResult.status === 'rejected') {
      console.warn('Échec du chargement des personnages:', charactersResult.reason)
      allCharacters.value = []
    }

    // Vérifier si une analyse est en cours
    if (data.analysis_status && ['pending', 'processing'].includes(data.analysis_status)) {
      isAnalyzing.value = true
      showAnalysisModal.value = true
      analysisStatus.value = data.analysis_status
      analysisProgress.value = data.analysis_progress || 0
      analysisMessage.value = data.analysis_message || ''
      startAnalysisPolling()
    }

    // Restaurer l'état muteVocals depuis localStorage
    const savedMuteVocals = localStorage.getItem(`muteVocals_${data.id}`)
    if (savedMuteVocals !== null) {
      muteVocals.value = savedMuteVocals === 'true'
    }

    // Initialiser l'état de chargement vidéo
    if (!project.value?.video_path) {
      isVideoLoading.value = false
    } else if (videoDuration.value === 0) {
      isVideoLoading.value = true

      // Timeout de sécurité pour la vidéo (15s)
      setTimeout(() => {
        if (isVideoLoading.value && videoDuration.value === 0) {
          console.warn('Timeout de chargement vidéo - forçage fin du loading')
          isVideoLoading.value = false
          notificationService.error(
            'Erreur vidéo',
            'La vidéo met trop de temps à charger. Vérifiez votre connexion.'
          )
        }
      }, 15000)
    }
  } catch (error) {
    console.error('Erreur lors du chargement du projet:', error)

    // Vérifier si c'est une erreur d'accès refusé (403)
    if (error instanceof AxiosError && error.response?.status === 403) {
      router.push({
        name: 'home',
        query: {
          error: "Vous n'avez pas les droits pour accéder à ce projet",
        },
      })
      return
    }

    // Pour les autres erreurs, afficher le message d'erreur avec retry
    let errorMessage = 'Erreur de chargement du projet'

    if (error instanceof AxiosError) {
      if (error.code === 'ECONNABORTED' || error.code === 'ERR_NETWORK') {
        errorMessage = 'Impossible de contacter le serveur. Vérifiez votre connexion Internet.'
      } else if (error.response?.status === 404) {
        errorMessage = 'Projet introuvable.'
      } else if (error.response && error.response.status >= 500) {
        errorMessage = 'Erreur serveur. Veuillez réessayer dans quelques instants.'
      } else if (error.message) {
        errorMessage = error.message
      }
    }

    loadingError.value = errorMessage
  } finally {
    clearTimeout(loadingTimeout)
    loading.value = false
  }
}

// Fonction pour réessayer le chargement
function retryLoading() {
  loadProjectData()
}

onMounted(async () => {
  await loadProjectData()

  // Ajouter les gestionnaires de raccourcis clavier
  window.addEventListener('keydown', handleGlobalKeydown)

  // Sauvegarder automatiquement l'état muteVocals dans localStorage
  watch(muteVocals, (newValue) => {
    if (project.value?.id) {
      localStorage.setItem(`muteVocals_${project.value.id}`, String(newValue))
    }
  })

  // Gestion focus/blur pour désactivation des raccourcis
  const onFocusIn = (ev: Event) => {
    const target = ev.target as HTMLElement | null
    if (!target) return
    if (
      target.matches(
        'input, textarea, [contenteditable="true"], [contenteditable=""], [contenteditable]',
      )
    ) {
      if (!isEditingText.value) {
        isEditingText.value = true
        // Pause vidéo si en lecture
        const videoEl = document.querySelector('video') as HTMLVideoElement | null
        if (videoEl && !videoEl.paused) {
          videoEl.pause()
          resumePlaybackAfterEdit = true
        } else {
          resumePlaybackAfterEdit = false
        }
      }
    }
  }
  const onFocusOut = () => {
    // Attendre fin de boucle pour voir si nouveau focus est toujours dans un champ
    requestAnimationFrame(() => {
      const active = document.activeElement as HTMLElement | null
      if (
        active &&
        active.matches(
          'input, textarea, [contenteditable="true"], [contenteditable=""], [contenteditable]',
        )
      ) {
        return // toujours dans un champ
      }
      if (isEditingText.value) {
        isEditingText.value = false
        // Reprendre la lecture si nécessaire
        if (resumePlaybackAfterEdit) {
          const videoEl = document.querySelector('video') as HTMLVideoElement | null
          if (videoEl) {
            videoEl.play().catch(() => {})
          }
          resumePlaybackAfterEdit = false
        }
      }
    })
  }
  window.addEventListener('focusin', onFocusIn)
  window.addEventListener('focusout', onFocusOut)
  focusInHandler = onFocusIn
  focusOutHandler = onFocusOut
})

// Nettoyage des événements
import { onUnmounted } from 'vue'

onUnmounted(() => {
  window.removeEventListener('keydown', handleGlobalKeydown)
  if (focusInHandler) window.removeEventListener('focusin', focusInHandler)
  if (focusOutHandler) window.removeEventListener('focusout', focusOutHandler)

  // Arrêter le polling de l'analyse si actif
  stopAnalysisPolling()

  // Nettoyer le mixer audio
  if (audioMixer) {
    audioMixer.cleanup()
  }

  // Nettoyer les timeouts de buffering/seeking pour éviter les fuites mémoire
  if (bufferingTimeout !== null) {
    clearTimeout(bufferingTimeout)
    bufferingTimeout = null
  }
  if (seekingTimeout !== null) {
    clearTimeout(seekingTimeout)
    seekingTimeout = null
  }
})

// Seek déclenché par clic sur un bloc de la bande rythmo
const onRythmoSeek = (time: number) => {
  lastSeekFromTimecode = true
  currentTime.value = time
  // Met à jour la vidéo si possible
  const videoEl = document.querySelector('video') as HTMLVideoElement | null
  if (videoEl) videoEl.currentTime = time
  // Sélectionne le timecode courant
  if (compatibleTimecodes.value.length > 0) {
    const idx = compatibleTimecodes.value.findIndex((tc) => time >= tc.start && time < tc.end)
    selectedTimecodeIdx.value = idx >= 0 ? idx : null
  }
  // Scroll instantané lors d'un seek manuel
  instantRythmoScroll.value = true
}

// Seek déclenché par la barre de navigation vidéo
const onNavigationSeek = (time: number) => {
  lastSeekFromTimecode = true
  currentTime.value = time
  // Met à jour la vidéo si possible
  const videoEl = document.querySelector('video') as HTMLVideoElement | null
  if (videoEl) videoEl.currentTime = time
  // Sélectionne le timecode courant
  if (compatibleTimecodes.value.length > 0) {
    const idx = compatibleTimecodes.value.findIndex((tc) => time >= tc.start && time < tc.end)
    selectedTimecodeIdx.value = idx >= 0 ? idx : null
  }
  // Scroll instantané lors d'un seek manuel
  instantRythmoScroll.value = true
}
</script>
<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Spinner de buffering élégant */
.buffering-spinner {
  position: relative;
  width: 80px;
  height: 80px;
  background: rgba(0, 0, 0, 0.75);
  backdrop-filter: blur(8px);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
}

/* Anneau extérieur qui tourne */
.spinner-ring {
  position: absolute;
  width: 60px;
  height: 60px;
  border: 3px solid transparent;
  border-top-color: #8455f6;
  border-right-color: #8455f6;
  border-radius: 50%;
  animation: spin 1s cubic-bezier(0.68, -0.55, 0.265, 1.55) infinite;
}

/* Anneau intérieur qui tourne en sens inverse */
.spinner-ring-inner {
  position: absolute;
  width: 45px;
  height: 45px;
  border: 2px solid transparent;
  border-bottom-color: #60a5fa;
  border-left-color: #60a5fa;
  border-radius: 50%;
  animation: spin-reverse 1.2s cubic-bezier(0.68, -0.55, 0.265, 1.55) infinite;
}

/* Point central qui pulse */
.spinner-dot {
  width: 12px;
  height: 12px;
  background: linear-gradient(135deg, #8455f6, #60a5fa);
  border-radius: 50%;
  animation: pulse 1s ease-in-out infinite;
  box-shadow: 0 0 20px rgba(132, 85, 246, 0.6);
}

@keyframes spin {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}

@keyframes spin-reverse {
  0% {
    transform: rotate(360deg);
  }
  100% {
    transform: rotate(0deg);
  }
}

@keyframes pulse {
  0%,
  100% {
    transform: scale(1);
    opacity: 1;
  }
  50% {
    transform: scale(1.3);
    opacity: 0.7;
  }
}
</style>
