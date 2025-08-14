<template>
  <div class="project-detail-container">
    <h2>Détail du projet</h2>
    <div v-if="loading">Chargement...</div>
    <div v-else-if="project">
      <h3>{{ project.name }}</h3>
      <p>{{ project.description }}</p>
      <video v-if="project.video_path" :src="getVideoUrl(project.video_path)" controls width="320" />
    </div>
    <div v-else>
      Projet introuvable.
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import api from '../api/axios';

const route = useRoute();
const project = ref<any>(null);
const loading = ref(true);

function getVideoUrl(path: string) {
  // Adapter selon le stockage réel
  return path.startsWith('http') ? path : `http://localhost:8000/storage/${path}`;
}

onMounted(async () => {
  loading.value = true;
  try {
    const res = await api.get(`/projects/${route.params.id}`);
    project.value = res.data;
  } catch {
    project.value = null;
  } finally {
    loading.value = false;
  }
});
</script>

<style scoped>
.project-detail-container {
  max-width: 600px;
  margin: 2rem auto;
  background: #fff;
  border-radius: 10px;
  padding: 2rem;
  box-shadow: 0 2px 8px #0001;
}
</style>
