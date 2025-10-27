import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'
import './assets/styles/tailwind.css'
import { useAuthStore } from './stores/auth'
import { preloadPopularFonts } from './services/googleFonts'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)

// Initialiser l'authentification après l'installation de Pinia
const authStore = useAuthStore()
authStore.initAuth()

// Pré-charger les polices Google Fonts populaires en arrière-plan
preloadPopularFonts().catch((error) => {
  console.warn('Impossible de pré-charger toutes les polices:', error)
})

app.mount('#app')
