<template>
  <div v-if="show" class="modal-overlay" @click.self="$emit('cancel')">
    <div class="modal-content">
      <div class="modal-header">
        <h3>Confirmer la suppression</h3>
        <button @click="$emit('cancel')" class="close-btn">&times;</button>
      </div>
      
      <div class="modal-body">
        <p>Êtes-vous sûr de vouloir supprimer ce timecode ?</p>
        <div v-if="timecode" class="timecode-info">
          <div class="timecode-details">
            <span class="time-range">{{ timecode.start.toFixed(2) }}s - {{ timecode.end.toFixed(2) }}s</span>
            <span class="line-info">Ligne {{ timecode.line_number }}</span>
          </div>
          <div class="timecode-text">{{ timecode.text }}</div>
          <div v-if="timecode.character" class="character-info" :style="{ backgroundColor: timecode.character.color }">
            {{ timecode.character.name }}
          </div>
        </div>
      </div>
      
      <div class="modal-footer">
        <button @click="$emit('cancel')" class="cancel-btn">
          Annuler
        </button>
        <button @click="$emit('confirm')" class="confirm-btn">
          Supprimer
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Character } from '../../api/characters'

interface Timecode {
  id?: number
  start: number
  end: number
  text: string
  line_number: number
  character_id?: number | null
  character?: Character
  show_character?: boolean
}

defineProps<{
  show: boolean
  timecode: Timecode | null
}>()

defineEmits<{
  (e: 'confirm'): void
  (e: 'cancel'): void
}>()
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: #2a3441;
  border-radius: 12px;
  padding: 0;
  min-width: 400px;
  max-width: 500px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
  border: 1px solid rgba(59, 130, 246, 0.2);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid rgba(59, 130, 246, 0.2);
}

.modal-header h3 {
  margin: 0;
  color: white;
  font-size: 1.25rem;
  font-weight: 600;
}

.close-btn {
  background: none;
  border: none;
  color: #9ca3af;
  font-size: 1.5rem;
  cursor: pointer;
  padding: 0;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  transition: all 0.2s;
}

.close-btn:hover {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
}

.modal-body {
  padding: 1.5rem;
}

.modal-body p {
  margin: 0 0 1rem 0;
  color: #e5e7eb;
  font-size: 1rem;
}

.timecode-info {
  background: #374151;
  border-radius: 8px;
  padding: 1rem;
  border-left: 3px solid #3b82f6;
}

.timecode-details {
  display: flex;
  gap: 1rem;
  align-items: center;
  margin-bottom: 0.5rem;
}

.time-range {
  color: #3b82f6;
  font-weight: 600;
  font-size: 0.875rem;
}

.line-info {
  color: #9ca3af;
  font-size: 0.75rem;
  background: #4b5563;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
}

.timecode-text {
  color: white;
  font-weight: 500;
  margin-bottom: 0.5rem;
}

.character-info {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 15px;
  font-size: 0.75rem;
  font-weight: 600;
  color: white;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
}

.modal-footer {
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
  padding: 1.5rem;
  border-top: 1px solid rgba(59, 130, 246, 0.2);
}

.cancel-btn {
  background: #4b5563;
  color: white;
  border: none;
  border-radius: 6px;
  padding: 0.75rem 1.5rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.cancel-btn:hover {
  background: #6b7280;
}

.confirm-btn {
  background: #ef4444;
  color: white;
  border: none;
  border-radius: 6px;
  padding: 0.75rem 1.5rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.confirm-btn:hover {
  background: #dc2626;
}
</style>
