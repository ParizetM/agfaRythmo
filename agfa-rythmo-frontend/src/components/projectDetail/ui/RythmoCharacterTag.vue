<template>
  <div
    class="character-tag"
    :style="{
      backgroundColor: character?.color,
      color: getContrastColor(character?.color || '#8455F6')
    }"
  >
    {{ character?.name }}
    <button
      class="character-toggle"
      @click.stop="$emit('toggle')"
      :title="show ? 'Masquer le personnage' : 'Afficher le personnage'"
    >
      {{ show ? '×' : '👤' }}
    </button>
  </div>
</template>

<script setup lang="ts">
interface Character {
  id: number
  name: string
  color: string
}

interface Props {
  character?: Character | null
  show?: boolean
}

defineProps<Props>()

defineEmits<{
  toggle: []
}>()

function getContrastColor(backgroundColor: string): string {
  // Convertir la couleur hex en RGB
  const hex = backgroundColor.replace('#', '')
  const r = parseInt(hex.substr(0, 2), 16)
  const g = parseInt(hex.substr(2, 2), 16)
  const b = parseInt(hex.substr(4, 2), 16)

  // Calculer la luminance
  const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255

  // Retourner blanc ou noir selon la luminance
  return luminance > 0.5 ? '#000000' : '#FFFFFF'
}
</script>

<style scoped>
.character-tag {
  position: absolute;
  top: 2px;
  right: 101%;
  font-size: 0.7rem;
  font-weight: bold;
  padding: 2px 6px;
  border-radius: 4px;
  z-index: 10;
  line-height: 1;
  white-space: nowrap;
  text-shadow: none;
  opacity: 0.9;
  max-width: calc(100% - 8px);
  overflow: hidden;
  text-overflow: ellipsis;
  display: flex;
  align-items: center;
  gap: 4px;
}

.character-toggle {
  background: rgba(255, 255, 255, 0.3);
  border: none;
  border-radius: 50%;
  width: 14px;
  height: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  font-weight: bold;
  cursor: pointer;
  transition: all 0.2s ease;
  color: inherit;
}

.character-toggle:hover {
  background: rgba(255, 255, 255, 0.6);
  transform: scale(1.1);
}
</style>
