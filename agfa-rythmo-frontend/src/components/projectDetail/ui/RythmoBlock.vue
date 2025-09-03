<template>
  <div
    class="rythmo-block"
    :class="{
      active: isActive,
      'rythmo-block-gap': isGap
    }"
    :style="blockStyle"
    @click="$emit('block-click', timecode)"
    @dblclick="$emit('block-dblclick', timecode)"
  >
    <!-- Zones de redimensionnement -->
    <template v-if="!isGap && !isEditing">
      <div
        class="resize-handle resize-left"
        @mousedown="timecode.id && $emit('resize-start', timecode.id, 'left', $event)"
        title="Redimensionner à gauche"
      />
      <div
        class="resize-handle resize-right"
        @mousedown="timecode.id && $emit('resize-start', timecode.id, 'right', $event)"
        title="Redimensionner à droite"
      />
      <div
        class="move-handle"
        @mousedown="timecode.id && $emit('move-start', timecode.id, $event)"
        title="Déplacer le timecode"
      />
    </template>

    <!-- Contenu du bloc -->
    <template v-if="isEditing">
      <input
        ref="editInput"
        v-model="editText"
        @blur="timecode.id && $emit('edit-finish', timecode.id, editText)"
        @keyup.enter="timecode.id && $emit('edit-finish', timecode.id, editText)"
        @keyup.esc="$emit('edit-cancel')"
        class="rythmo-edit-input"
        :style="editInputStyle"
      />
    </template>

    <template v-else-if="isGap">
      <span class="gap-label">{{ gapLabel }}</span>
    </template>

    <template v-else>
      <!-- Étiquette du personnage -->
      <RythmoCharacterTag
        v-if="shouldShowCharacter && blockWidth >= 100"
        :character="timecode.character"
        :show="timecode.show_character !== false"
        @toggle="timecode.id && $emit('character-toggle', timecode.id)"
      />

      <!-- Bouton pour afficher le personnage quand masqué -->
      <button
        v-else-if="!shouldShowCharacter && timecode.character && blockWidth >= 50"
        class="character-show-btn"
        @click.stop="timecode.id && $emit('character-toggle', timecode.id)"
        :style="{ borderColor: timecode.character.color }"
        title="Afficher le personnage"
      >
        👤
      </button>

      <!-- Texte du timecode -->
      <span
        class="distort-text"
        :style="textStyle"
      >
        {{ timecode.text }}
      </span>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, nextTick, watch } from 'vue'
import type { Timecode } from '../composables/useRythmoCalculations'
import RythmoCharacterTag from './RythmoCharacterTag.vue'

interface Props {
  timecode: Timecode
  blockWidth: number
  isActive?: boolean
  isGap?: boolean
  gapLabel?: string
  isEditing?: boolean
  textDistortStyle?: { transform: string; width: string }
}

const props = withDefaults(defineProps<Props>(), {
  isActive: false,
  isGap: false,
  gapLabel: '',
  isEditing: false,
})

const emit = defineEmits<{
  'block-click': [timecode: Timecode]
  'block-dblclick': [timecode: Timecode]
  'resize-start': [timecodeId: number, mode: 'left' | 'right', event: MouseEvent]
  'move-start': [timecodeId: number, event: MouseEvent]
  'edit-finish': [timecodeId: number, text: string]
  'edit-cancel': []
  'character-toggle': [timecodeId: number]
}>()

const editInput = ref<HTMLInputElement>()
const editText = ref('')

// Styles calculés
const blockStyle = computed(() => ({
  width: `${props.blockWidth}px`,
  height: '100%',
  display: 'flex',
  alignItems: 'center',
  justifyContent: 'center',
  overflow: 'visible',
  flexShrink: 0,
  borderRadius: '4px',
  margin: '0',
  position: 'absolute' as const,
  cursor: props.isGap ? 'default' : 'pointer',
}))

const shouldShowCharacter = computed(() => {
  return !!(props.timecode.character && props.timecode.show_character !== false)
})

const textStyle = computed(() => {
  const baseStyle = {
    color: props.timecode.character?.color || 'inherit',
    fontSize: '1.8rem',
    fontWeight: '600',
    textShadow: '0 1px 2px rgba(0, 0, 0, 0.8)',
  }

  // Appliquer la distorsion si fournie
  if (props.textDistortStyle) {
    return { ...baseStyle, ...props.textDistortStyle }
  }

  return baseStyle
})

const editInputStyle = computed(() => ({
  minWidth: `${props.blockWidth}px`,
  fontSize: '1.2rem',
  fontWeight: '600',
  background: '#23272f',
  color: '#fff',
  borderRadius: '4px',
  border: '1px solid #8455f6',
  padding: '0 6px',
  outline: 'none',
  height: '2.2rem',
  overflow: 'visible',
  whiteSpace: 'pre' as const,
}))

// Gestion de l'édition
watch(() => props.isEditing, (isEditing) => {
  if (isEditing) {
    editText.value = props.timecode.text
    nextTick(() => {
      editInput.value?.focus()
    })
  }
})
</script>

<style scoped>
.rythmo-block {
  position: relative;
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  border: 1px solid rgba(59, 130, 246, 0.3);
}

.rythmo-block.active {
  background: linear-gradient(135deg, #10b981, #059669);
  border: 1px solid #10b981;
  box-shadow: 0 0 12px rgba(16, 185, 129, 0.4);
}

.rythmo-block-gap {
  background: var(--agfa-gray) !important;
  opacity: 0.3;
  border: 1px solid rgba(75, 85, 99, 0.3);
}

.distort-text {
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  margin: 0;
  opacity: 0.9;
  background: none;
  border-radius: 3px;
  overflow: visible;
  flex-grow: 1;
  width: 100%;
  white-space: pre;
}

.rythmo-block.active .distort-text {
  opacity: 1;
  color: #ffffff;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.8);
}

.gap-label {
  font-size: 0.875rem;
  color: #9ca3af;
  font-style: italic;
  user-select: none;
  opacity: 0.6 !important;
  font-weight: 500;
}

/* Zones de redimensionnement */
.resize-handle {
  position: absolute;
  top: 0;
  width: 8px;
  height: 100%;
  cursor: ew-resize;
  z-index: 15;
  background: transparent;
  transition: background 0.2s;
}

.resize-handle:hover {
  background: rgba(255, 255, 255, 0.6);
}

.resize-left {
  left: 0;
  border-radius: 4px 0 0 4px;
}

.resize-right {
  right: 0;
  border-radius: 0 4px 4px 0;
}

/* Zone de déplacement */
.move-handle {
  position: absolute;
  bottom: 0;
  left: 8px;
  right: 8px;
  height: 8px;
  cursor: move;
  z-index: 15;
  background: transparent;
  transition: background 0.2s;
  border-radius: 0 0 4px 4px;
}

.move-handle:hover {
  background: rgba(255, 255, 255, 0.6);
}

.character-show-btn {
  position: absolute;
  top: 2px;
  right: 2px;
  background: rgba(0, 0, 0, 0.3);
  border: 1px solid;
  border-radius: 50%;
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  cursor: pointer;
  transition: all 0.2s ease;
  z-index: 10;
}

.character-show-btn:hover {
  background: rgba(0, 0, 0, 0.6);
  transform: scale(1.1);
}

.rythmo-edit-input {
  min-width: 0;
  width: fit-content;
  white-space: pre;
  overflow: visible;
  z-index: 10;
}
</style>
