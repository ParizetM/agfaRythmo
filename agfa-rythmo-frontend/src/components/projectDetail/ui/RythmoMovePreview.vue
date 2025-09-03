<template>
  <div
    v-if="visible"
    class="move-preview"
    :style="previewStyle"
  >
    <!-- Étiquette du personnage -->
    <RythmoCharacterTag
      v-if="timecode?.character"
      :character="timecode.character"
      :show="true"
    />

    <!-- Texte du timecode -->
    <span
      class="distort-text"
      :style="textStyle"
    >
      {{ timecode?.text || '' }}
    </span>

    <div class="preview-line-indicator">
      Ligne {{ targetLine }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, type CSSProperties } from 'vue'
import type { Timecode } from '../composables/useRythmoCalculations'
import RythmoCharacterTag from './RythmoCharacterTag.vue'

interface Props {
  visible: boolean
  x: number
  y: number
  timecode?: Timecode | null
  targetLine: number
  textDistortStyle?: { transform: string; width: string }
}

const props = defineProps<Props>()

const previewStyle = computed((): CSSProperties => {
  if (!props.visible || !props.timecode) return { display: 'none' }

  const duration = props.timecode.end - props.timecode.start
  const width = duration * 80 + 30 // 80 = PX_PER_SEC

  return {
    position: 'fixed',
    left: props.x + 'px',
    top: props.y + 'px',
    width: width + 'px',
    height: '3rem',
    zIndex: 1000,
    pointerEvents: 'none',
    transform: 'translate(-50%, -50%)',
    background: 'linear-gradient(135deg, #8455F6, #7c3aed)',
    color: 'white',
    borderRadius: '4px',
    padding: '8px',
    boxSizing: 'border-box',
    fontSize: '1.8rem',
    fontWeight: '600',
    opacity: 0.8,
    border: '2px solid #ffffff',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    textShadow: '0 1px 2px rgba(0, 0, 0, 0.8)',
    boxShadow: '0 4px 12px rgba(0, 0, 0, 0.3)',
  }
})

const textStyle = computed(() => {
  const baseStyle = {
    color: props.timecode?.character?.color || 'inherit',
    fontSize: '1.8rem',
    fontWeight: '600',
  }

  if (props.textDistortStyle) {
    return { ...baseStyle, ...props.textDistortStyle }
  }

  return baseStyle
})
</script>

<style scoped>
.distort-text {
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  width: 100%;
  white-space: pre;
}

.preview-line-indicator {
  position: absolute;
  bottom: -20px;
  left: 50%;
  transform: translateX(-50%);
  padding: 2px 8px;
  background-color: #ffffff;
  color: #121827;
  font-size: 10px;
  font-weight: bold;
  border-radius: 4px;
  white-space: nowrap;
}
</style>
