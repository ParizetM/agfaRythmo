<template>
  <main>
    <h1>Importer une vidéo</h1>
    <VideoUploader />
  </main>
</template>

<script setup lang="ts">
import VideoUploader from '../components/VideoUploader.vue'
</script>

<style scoped>
main {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-top: 2rem;
}
</style>
