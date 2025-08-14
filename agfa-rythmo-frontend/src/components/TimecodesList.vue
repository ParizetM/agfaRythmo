<template>
  <div class="timecodes-list">
    <h3>Timecodes</h3>
    <ul>
      <li v-for="(line, idx) in timecodes" :key="idx" :class="{ selected: idx === selected }" @click="$emit('select', idx)">
        <span>{{ line.start.toFixed(2) }}s - {{ line.end.toFixed(2) }}s</span>
        <span class="tc-text">{{ line.text }}</span>
        <div>
        <button @click.stop="$emit('edit', idx)">✏️</button>
        <button @click.stop="$emit('delete', idx)">🗑️</button>
        </div>
      </li>
    </ul>
    <button @click="$emit('add')">Ajouter un timecode</button>
  </div>
</template>

<script setup lang="ts">
defineProps<{ timecodes: { start: number; end: number; text: string }[], selected?: number }>();
defineEmits(['select', 'edit', 'delete', 'add']);
</script>

<style scoped>
.timecodes-list {
  background: #232733;
  border-radius: 8px;
  padding: 1rem;
  min-width: 220px;
  max-width: 400px;
  color: #fff;
  box-shadow: 0 1px 6px #0002;
}
ul {
  list-style: none;
  padding: 0;
  margin: 0 0 1rem 0;
}
li {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.5rem;
  padding: 0.3rem 0.2rem;
  border-radius: 4px;
  cursor: pointer;
}
li.selected {
  background: #3182ce44;
}
.tc-text {
  flex: 1;
  margin-left: 0.5rem;
  font-size: 0.95em;
}
button {
  background: none;
  border: none;
  color: #fff;
  cursor: pointer;
  font-size: 1em;
  margin-left: 0.2rem;
}
button:hover {
  color: #3182ce;
}
</style>
