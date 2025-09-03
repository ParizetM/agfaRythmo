<template>
  <div class="timecodes-editor">
    <h3>Édition des timecodes</h3>

    <!-- Sélection du personnage actuel -->
    <div class="character-selector mb-4" v-if="characters.length > 0">
      <label class="block text-sm font-medium text-gray-300 mb-2">
        Personnage actuel pour les nouveaux timecodes :
      </label>
      <select
        v-model="selectedCharacterId"
        class="w-full bg-agfa-button text-white border border-gray-600 rounded px-3 py-2 focus:border-blue-500 focus:outline-none"
      >
        <option :value="null">Aucun personnage</option>
        <option
          v-for="character in characters"
          :key="character.id"
          :value="character.id"
        >
          {{ character.name }}
        </option>
      </select>
    </div>

    <table>
      <thead>
        <tr>
          <th>Ligne</th>
          <th>Début (s)</th>
          <th>Fin (s)</th>
          <th>Personnage</th>
          <th>Texte</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(line, idx) in localTimecodes" :key="idx">
          <td>
            <select v-model.number="line.line_number" class="w-16 bg-agfa-button text-white border border-gray-600 rounded px-2 py-1">
              <option v-for="i in 6" :key="i" :value="i">{{ i }}</option>
            </select>
          </td>
          <td><input type="number" min="0" step="0.01" v-model.number="line.start" class="w-20 bg-agfa-button text-white border border-gray-600 rounded px-2 py-1" /></td>
          <td><input type="number" min="0" step="0.01" v-model.number="line.end" class="w-20 bg-agfa-button text-white border border-gray-600 rounded px-2 py-1" /></td>
          <td>
            <select
              v-model="line.character_id"
              class="w-32 text-white border border-gray-600 rounded px-2 py-1"
              :style="getCharacterSelectStyle(line.character_id)"
              @change="updateTimecodeCharacter(line)"
            >
              <option :value="null" style="background: #4a5568; color: #ffffff;">Aucun</option>
              <option
                v-for="character in characters"
                :key="character.id"
                :value="character.id"
                :style="{ backgroundColor: character.color, color: getTextColor(character.color) }"
              >
                {{ character.name }}
              </option>
            </select>
          </td>
          <td><input type="text" v-model="line.text" class="w-full bg-agfa-button text-white border border-gray-600 rounded px-2 py-1" /></td>
          <td><button @click="removeLine(idx)" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded">Supprimer</button></td>
        </tr>
      </tbody>
    </table>
    <button @click="addLine" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded mr-2">Ajouter une ligne</button>
    <button @click="save" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">Sauvegarder</button>
    <div v-if="saving" class="text-yellow-400 mt-2">Sauvegarde en cours...</div>
    <div v-if="saveSuccess" class="text-green-400 mt-2">Sauvegarde réussie !</div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import { timecodeApi, type Timecode, type CreateTimecodeData, type UpdateTimecodeData } from '../../api/timecodes';
import { characterApi, type Character } from '../../api/characters';

interface ExtendedTimecode extends Timecode {
  isNew?: boolean;
}

const props = defineProps<{
  projectId: number;
  timecodes: Timecode[];
  activeCharacter?: Character | null;
}>();

const emit = defineEmits<{
  (e: 'updated'): void;
}>();

const localTimecodes = ref<ExtendedTimecode[]>([]);
const characters = ref<Character[]>([]);
const selectedCharacterId = ref<number | null>(props.activeCharacter?.id || null);
const saving = ref(false);
const saveSuccess = ref(false);

// Charger les personnages
async function loadCharacters() {
  try {
    const response = await characterApi.getAll(props.projectId);
    characters.value = response.data.characters;
  } catch (error) {
    console.error('Erreur lors du chargement des personnages:', error);
  }
}

function addLine() {
  const newTimecode: ExtendedTimecode = {
    id: undefined, // Sera défini lors de la sauvegarde
    project_id: props.projectId,
    line_number: 1,
    start: 0,
    end: 2,
    text: '',
    character_id: selectedCharacterId.value,
    show_character: true,
    created_at: '',
    updated_at: '',
    isNew: true
  };
  localTimecodes.value.push(newTimecode);
}

function removeLine(idx: number) {
  localTimecodes.value.splice(idx, 1);
}

// Fonction pour obtenir le style du select basé sur la couleur du personnage
function getCharacterSelectStyle(characterId: number | null | undefined) {
  if (!characterId) {
    return {
      backgroundColor: '#4a5568',
      color: '#ffffff'
    };
  }

  const character = characters.value.find(c => c.id === characterId);
  if (!character) {
    return {
      backgroundColor: '#4a5568',
      color: '#ffffff'
    };
  }

  return {
    backgroundColor: character.color,
    color: getTextColor(character.color)
  };
}

// Fonction pour déterminer la couleur du texte (noir ou blanc) selon la couleur de fond
function getTextColor(backgroundColor: string): string {
  // Supprimer le # si présent
  const color = backgroundColor.replace('#', '');

  // Convertir en RGB
  const r = parseInt(color.substr(0, 2), 16);
  const g = parseInt(color.substr(2, 2), 16);
  const b = parseInt(color.substr(4, 2), 16);

  // Calculer la luminance
  const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;

  // Retourner noir pour les couleurs claires, blanc pour les couleurs sombres
  return luminance > 0.5 ? '#000000' : '#ffffff';
}

// Fonction pour mettre à jour un timecode quand on change de personnage
async function updateTimecodeCharacter(timecode: ExtendedTimecode) {
  if (!timecode.isNew && timecode.id) {
    // Sauvegarder immédiatement si c'est un timecode existant
    try {
      const updateData: UpdateTimecodeData = {
        character_id: timecode.character_id
      };
      await timecodeApi.update(props.projectId, timecode.id, updateData);
      emit('updated');
    } catch (error) {
      console.error('Erreur lors de la mise à jour du personnage:', error);
    }
  }
}

async function save() {
  saving.value = true;
  saveSuccess.value = false;

  try {
    for (let i = 0; i < localTimecodes.value.length; i++) {
      const timecode = localTimecodes.value[i];

      if (timecode.isNew) {
        // Créer un nouveau timecode
        const createData: CreateTimecodeData = {
          line_number: timecode.line_number,
          start: timecode.start,
          end: timecode.end,
          text: timecode.text,
          character_id: timecode.character_id,
          show_character: timecode.show_character
        };
        const response = await timecodeApi.create(props.projectId, createData);

        // Remplacer l'objet temporaire par l'objet créé
        localTimecodes.value[i] = { ...response.data.timecode, isNew: false };

      } else if (timecode.id) {
        // Mettre à jour un timecode existant
        const updateData: UpdateTimecodeData = {
          line_number: timecode.line_number,
          start: timecode.start,
          end: timecode.end,
          text: timecode.text,
          character_id: timecode.character_id,
          show_character: timecode.show_character
        };
        await timecodeApi.update(props.projectId, timecode.id, updateData);
      }
    }

    saveSuccess.value = true;
    emit('updated');

  } catch (error) {
    console.error('Erreur lors de la sauvegarde:', error);
  } finally {
    saving.value = false;
    setTimeout(() => (saveSuccess.value = false), 1500);
  }
}

// Synchroniser avec les props
watch(() => props.timecodes, (newTimecodes) => {
  localTimecodes.value = newTimecodes.map(tc => ({ ...tc }));
}, { immediate: true });

// Synchroniser avec le personnage actif
watch(() => props.activeCharacter, (newActiveCharacter) => {
  if (newActiveCharacter) {
    selectedCharacterId.value = newActiveCharacter.id;
  }
}, { immediate: true });

onMounted(() => {
  loadCharacters();
});
</script>

<style scoped>
.timecodes-editor {
  background: #202937;
  border-radius: 10px;
  padding: 1.5rem;
  margin: 2rem 0;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
  max-width: 900px;
  color: #ffffff;
}

.character-selector {
  background: rgba(59, 130, 246, 0.1);
  border: 1px solid rgba(59, 130, 246, 0.2);
  border-radius: 6px;
  padding: 1rem;
}

h3 {
  color: #ffffff;
  margin-bottom: 1rem;
}

table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 1rem;
  background: #384152;
  border-radius: 6px;
  overflow: hidden;
}

th, td {
  border: 1px solid #4a5568;
  padding: 0.75rem 0.5rem;
  text-align: left;
  color: #ffffff;
}

th {
  background: #2d3748;
  font-weight: 600;
  font-size: 0.875rem;
}

td {
  background: #384152;
}

input[type="number"],
input[type="text"],
select {
  background: #4a5568;
  color: #ffffff;
  border: 1px solid #718096;
  border-radius: 4px;
  padding: 0.25rem 0.5rem;
  font-size: 0.875rem;
}

input[type="number"]:focus,
input[type="text"]:focus,
select:focus {
  outline: none;
  border-color: #3182ce;
  box-shadow: 0 0 0 1px #3182ce;
}

button {
  padding: 0.5rem 1rem;
  border-radius: 6px;
  border: none;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 0.875rem;
}

button:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.mb-2 {
  margin-bottom: 0.5rem;
}

.mb-4 {
  margin-bottom: 1rem;
}

.mt-2 {
  margin-top: 0.5rem;
}

.mr-2 {
  margin-right: 0.5rem;
}

.w-16 {
  width: 4rem;
}

.w-20 {
  width: 5rem;
}

.w-32 {
  width: 8rem;
}

.w-full {
  width: 100%;
}

.block {
  display: block;
}

.text-sm {
  font-size: 0.875rem;
}

.font-medium {
  font-weight: 500;
}

.text-gray-300 {
  color: #d1d5db;
}

.text-white {
  color: #ffffff;
}

.text-yellow-400 {
  color: #fbbf24;
}

.text-green-400 {
  color: #34d399;
}

.bg-agfa-button {
  background-color: #384152;
}

.border-gray-600 {
  border-color: #4b5563;
}

.rounded {
  border-radius: 0.375rem;
}

.px-2 {
  padding-left: 0.5rem;
  padding-right: 0.5rem;
}

.px-3 {
  padding-left: 0.75rem;
  padding-right: 0.75rem;
}

.px-4 {
  padding-left: 1rem;
  padding-right: 1rem;
}

.py-1 {
  padding-top: 0.25rem;
  padding-bottom: 0.25rem;
}

.py-2 {
  padding-top: 0.5rem;
  padding-bottom: 0.5rem;
}

.focus\:border-blue-500:focus {
  border-color: #3b82f6;
}

.focus\:outline-none:focus {
  outline: none;
}

.bg-blue-600 {
  background-color: #2563eb;
}

.bg-green-600 {
  background-color: #16a34a;
}

.bg-red-600 {
  background-color: #dc2626;
}

.hover\:bg-blue-700:hover {
  background-color: #1d4ed8;
}

.hover\:bg-green-700:hover {
  background-color: #15803d;
}

.hover\:bg-red-700:hover {
  background-color: #b91c1c;
}
</style>
