<template>
  <div class="min-h-screen bg-agfa-bg-primary flex items-center justify-center px-4 sm:px-6 relative overflow-hidden">
    <!-- Gradients d'arrière-plan animés -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
      <div class="absolute top-0 left-1/4 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl animate-pulse"></div>
      <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-purple-600/10 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s"></div>
    </div>

    <!-- Card principale style BaseModal -->
    <div class="relative max-w-2xl w-full animate-fade-in">
      <div class="bg-agfa-bg-secondary rounded-3xl shadow-2xl border border-gray-700/50 overflow-hidden">
        <!-- Header avec icône badge style BaseModal -->
        <div class="px-6 sm:px-8 pt-8 sm:pt-10 pb-6 border-b border-gray-700/50">
          <div class="flex flex-col items-center gap-6">
            <!-- Icône badge avec gradient bleu-violet -->
            <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-2xl bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center shadow-lg animate-pulse">
              <svg class="w-10 h-10 sm:w-12 sm:h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
            </div>

            <!-- Titre et sous-titre -->
            <div class="text-center">
              <h1 class="text-3xl sm:text-4xl md:text-5xl font-bold text-white mb-3">
                Maintenance en cours
              </h1>
              <p class="text-gray-400 text-sm sm:text-base">
                AgfaRythmo
              </p>
            </div>
          </div>
        </div>

        <!-- Body -->
        <div class="px-6 sm:px-8 py-8 sm:py-10">
          <!-- Message principal -->
          <div class="text-center mb-8">
            <p class="text-lg sm:text-xl text-gray-300 mb-4 leading-relaxed">
              Agfarythmo est actuellement en maintenance pour améliorer votre expérience.
            </p>
            <p class="text-gray-400 text-sm sm:text-base">
              Nous effectuons quelques améliorations et serons de retour très bientôt !
            </p>
          </div>

          <!-- Animation de chargement élégante -->
          <div class="flex justify-center items-center space-x-3 mb-8">
            <div class="w-3 h-3 rounded-full bg-gradient-to-r from-blue-500 to-blue-600 animate-bounce shadow-lg shadow-blue-500/50" style="animation-delay: 0ms"></div>
            <div class="w-3 h-3 rounded-full bg-gradient-to-r from-purple-500 to-purple-600 animate-bounce shadow-lg shadow-purple-500/50" style="animation-delay: 150ms"></div>
            <div class="w-3 h-3 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 animate-bounce shadow-lg shadow-blue-500/50" style="animation-delay: 300ms"></div>
          </div>

          <!-- Info card -->
          <div class="bg-agfa-bg-tertiary rounded-2xl p-6 border border-gray-700/30">
            <div class="flex items-start gap-4">
              <div class="flex-shrink-0">
                <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div class="flex-1">
                <h3 class="text-white font-semibold mb-2">Que se passe-t-il ?</h3>
                <p class="text-gray-400 text-sm leading-relaxed">
                  Martin à surement tout cassé en poussant la dernière mise à jour. Ne vous inquiétez pas, il travaille dur pour tout réparer !
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="px-6 sm:px-8 pb-8 sm:pb-10 pt-4 border-t border-gray-700/50">
          <p class="text-center text-gray-500 text-sm">
            © 2025 AgfaRythmo - Fait avec ❤️
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()
let checkInterval: number | null = null
const isRedirecting = ref(false)

// Créer une instance axios dédiée sans les intercepteurs pour éviter les boucles
const maintenanceApi = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
  withCredentials: false,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  }
})

// Fonction pour vérifier si la maintenance est terminée
async function checkMaintenanceStatus() {
  // Ne pas vérifier si déjà en cours de redirection
  if (isRedirecting.value) return

  try {
    // Essayer de faire un appel API simple (sans intercepteurs)
    const token = localStorage.getItem('auth_token')
    const headers = token ? { 'Authorization': `Bearer ${token}` } : {}

    await maintenanceApi.get('/auth/profile', { headers })

    // Si l'appel réussit (pas de 503), la maintenance est terminée
    console.log('✅ Maintenance terminée, redirection...')
    isRedirecting.value = true

    // Nettoyer l'intervalle avant de rediriger
    if (checkInterval) {
      clearInterval(checkInterval)
      checkInterval = null
    }

    // Petit délai pour une transition douce
    setTimeout(() => {
      router.push('/')
    }, 300)
  } catch (error: unknown) {
    // Si erreur 503, la maintenance est toujours active - on reste ici
    const err = error as { response?: { status?: number } }

    if (err.response?.status === 503) {
      // Maintenance toujours active, on reste
      return
    }

    // Si c'est une autre erreur (401, 404, etc.), la maintenance est terminée
    console.log('✅ Maintenance terminée (erreur non-503), redirection...')
    isRedirecting.value = true

    if (checkInterval) {
      clearInterval(checkInterval)
      checkInterval = null
    }

    setTimeout(() => {
      router.push('/')
    }, 300)
  }
}

onMounted(() => {
  // Vérifier toutes les 10 secondes (réduit la charge)
  checkInterval = window.setInterval(checkMaintenanceStatus, 10000)

  // Première vérification après 3 secondes
  setTimeout(checkMaintenanceStatus, 3000)
})

onUnmounted(() => {
  // Nettoyer l'intervalle quand le composant est détruit
  if (checkInterval) {
    clearInterval(checkInterval)
  }
})
</script>

<style scoped>
@keyframes pulse {
  0%, 100% {
    opacity: 0.5;
  }
  50% {
    opacity: 0.8;
  }
}
</style>
