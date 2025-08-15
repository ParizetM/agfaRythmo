<template>
  <div class="timecodes-editor">
    <h3>Édition des timecodes</h3>
    <table>
      <thead>
        <tr>
          <th>Début (s)</th>
          <th>Fin (s)</th>
          <th>Texte</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(line, idx) in localTimecodes" :key="idx">
          <td><input type="number" min="0" step="0.01" v-model.number="line.start" /></td>
          <td><input type="number" min="0" step="0.01" v-model.number="line.end" /></td>
          <td><input type="text" v-model="line.text" /></td>
          <td><button @click="removeLine(idx)">Supprimer</button></td>
        </tr>
      </tbody>
    </table>
    <button @click="addLine">Ajouter une ligne</button>
    <button @click="save" class="save-btn">Sauvegarder</button>
    <div v-if="saving">Sauvegarde en cours...</div>
    <div v-if="saveSuccess" class="success">Sauvegarde réussie !</div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, toRefs } from 'vue';
import api from '../../api/axios';

const props = defineProps<{ projectId: number; timecodes: { start: number; end: number; text: string }[] }>();
const emit = defineEmits(['updated']);

const localTimecodes = ref(props.timecodes ? JSON.parse(JSON.stringify(props.timecodes)) : []);
const saving = ref(false);
const saveSuccess = ref(false);

function addLine() {
  localTimecodes.value.push({ start: 0, end: 0, text: '' });
}
function removeLine(idx: number) {
  localTimecodes.value.splice(idx, 1);
}
async function save() {
  saving.value = true;
  saveSuccess.value = false;
  try {
    await api.put(`/projects/${props.projectId}`, { timecodes: localTimecodes.value });
    saveSuccess.value = true;
    emit('updated', localTimecodes.value);
  } finally {
    saving.value = false;
    setTimeout(() => (saveSuccess.value = false), 1500);
  }
}

watch(() => props.timecodes, (val) => {
  localTimecodes.value = val ? JSON.parse(JSON.stringify(val)) : [];
});
</script>

<style scoped>
.timecodes-editor {
  background: #f9fafb;
  border-radius: 10px;
  padding: 1.5rem;
  margin: 2rem 0;
  box-shadow: 0 2px 8px #0001;
  max-width: 700px;
}
table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 1rem;
}
th, td {
  border: 1px solid #e2e8f0;
  padding: 0.5rem;
  text-align: left;
}
input[type="number"] {
  width: 80px;
}
input[type="text"] {
  width: 100%;
}
button {
  margin-right: 0.5rem;
  padding: 0.3rem 0.8rem;
  border-radius: 4px;
  border: none;
  background: #3182ce;
  color: #fff;
  cursor: pointer;
}
button.save-btn {
  background: #38a169;
}
.success {
  color: #38a169;
  margin-top: 0.5rem;
}
</style>
