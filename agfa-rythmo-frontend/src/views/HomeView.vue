<template>
  <div class="home-container">
    <h1>Bienvenue sur Agfa Rythmo</h1>
    <router-link to="/projects">
      <button class="main-btn">Gérer mes projets</button>
    </router-link>
  </div>
</template>

<script setup lang="ts">
</script>

<style scoped>
.home-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 80vh;
}
.main-btn {
  margin-top: 2rem;
  padding: 1rem 2rem;
  font-size: 1.2rem;
  background: #2d3748;
  color: #fff;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.2s;
}
.main-btn:hover {
  background: #4a5568;
}
</style>
