<template>
  <div id="app" class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Navigation Bar -->
    <nav
      v-if="authStore.isAuthenticated"
      class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700"
    >
      <div class="max-w-7xl px-2 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <!-- Logo & Desktop Nav -->
          <div class="flex items-center">
            <router-link
              to="/"
              class="text-xl font-bold text-indigo-600 dark:text-indigo-400"
            >
              <Logo_titre_largeSvg class="h-20 w-auto fill-indigo-600" />
            </router-link>
            <!-- Desktop Nav -->
            <div class="ml-10 hidden md:flex items-baseline space-x-4">
              <router-link
                to="/"
                class="text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 px-3 py-2 rounded-md text-sm font-medium"
                :class="{ 'text-indigo-600 dark:text-indigo-400': $route.name === 'home' }"
              >
                Mes projets
              </router-link>
              <router-link
                v-if="authStore.isAdmin"
                to="/admin"
                class="text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 px-3 py-2 rounded-md text-sm font-medium"
                :class="{ 'text-indigo-600 dark:text-indigo-400': $route.name === 'admin' }"
              >
                Administration
              </router-link>
            </div>
          </div>

          <!-- Desktop Profile -->
          <div class="hidden md:flex items-center space-x-4">
            <!-- Badge invitations -->
            <router-link
              v-if="invitationCount > 0"
              to="/"
              class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-blue-600 hover:text-blue-700 transition-colors"
              title="Vous avez des invitations en attente"
            >
              <EnvelopeIcon class="h-5 w-5" />
              <span class="absolute -top-1 -right-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-red-600 rounded-full">
                {{ invitationCount }}
              </span>
            </router-link>
            <span class="text-gray-700 dark:text-gray-300 text-sm">
              Bonjour, {{ authStore.user?.name }}
            </span>
            <router-link
              to="/profile"
              class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 px-3 py-2 rounded-md text-sm font-medium"
            >
              Profil
            </router-link>
            <button
              @click="handleLogout"
              class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 px-3 py-2 rounded-md text-sm font-medium"
            >
              Déconnexion
            </button>
          </div>

          <!-- Mobile menu button -->
          <div class="flex items-center md:hidden">
            <button
              @click="mobileMenuOpen = !mobileMenuOpen"
              type="button"
              class="inline-flex items-center justify-center rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500"
              aria-controls="mobile-menu"
              :aria-expanded="mobileMenuOpen"
            >
              <span class="sr-only">Ouvrir le menu principal</span>
              <svg v-if="!mobileMenuOpen" class="block h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M4 6h16M4 12h16M4 18h16" />
              </svg>
              <svg v-else class="block h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
      </div>
      <!-- Mobile Menu -->
      <div
        class="md:hidden"
        id="mobile-menu"
        v-show="mobileMenuOpen"
        @click.away="mobileMenuOpen = false"
      >
        <div class="px-4 pt-2 pb-3 space-y-1 sm:px-3">
          <router-link
            to="/"
            class="block text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 px-3 py-2 rounded-md text-base font-medium"
            :class="{ 'text-indigo-600 dark:text-indigo-400': $route.name === 'home' }"
            @click="mobileMenuOpen = false"
          >
            Mes projets
          </router-link>
          <router-link
            v-if="authStore.isAdmin"
            to="/admin"
            class="block text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 px-3 py-2 rounded-md text-base font-medium"
            :class="{ 'text-indigo-600 dark:text-indigo-400': $route.name === 'admin' }"
            @click="mobileMenuOpen = false"
          >
            Administration
          </router-link>
          <router-link
            v-if="invitationCount > 0"
            to="/"
            class="relative block px-3 py-2 text-base font-medium text-blue-600 hover:text-blue-700 transition-colors"
            title="Vous avez des invitations en attente"
            @click="mobileMenuOpen = false"
          >
            <span class="flex items-center">
              <EnvelopeIcon class="h-5 w-5 mr-2" />
              Invitations
              <span class="ml-2 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-red-600 rounded-full">
                {{ invitationCount }}
              </span>
            </span>
          </router-link>
          <div class="border-t border-gray-200 dark:border-gray-700 my-2"></div>
          <span class="block text-gray-700 dark:text-gray-300 text-base px-3 py-2">
            Bonjour, {{ authStore.user?.name }}
          </span>
          <router-link
            to="/profile"
            class="block text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 px-3 py-2 rounded-md text-base font-medium"
            @click="mobileMenuOpen = false"
          >
            Profil
          </router-link>
          <button
            @click="handleLogout"
            class="block w-full text-left text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 px-3 py-2 rounded-md text-base font-medium"
          >
            Déconnexion
          </button>
        </div>
      </div>
    </nav>

    <!-- Content -->
    <main>
      <router-view />
    </main>

    <!-- Notifications -->
    <NotificationToast />
  </div>
</template>

<script setup lang="ts">
import { useAuthStore } from './stores/auth'
import { useRouter } from 'vue-router'
import { EnvelopeIcon } from '@heroicons/vue/24/outline'
import NotificationToast from './components/NotificationToast.vue'
import { useInvitations } from './composables/useInvitations'
import { ref, onMounted, onUnmounted } from 'vue'
import Logo_titre_largeSvg from './assets/icons/logo_titre_large.svg'


const authStore = useAuthStore()
const router = useRouter()
const { invitationCount } = useInvitations()

const mobileMenuOpen = ref(false)

const handleLogout = async () => {
  await authStore.logout()
  router.push('/login')
}

// Vérification périodique de l'authentification (toutes les 5 minutes)
let authCheckInterval: number | null = null

const startAuthCheck = () => {
  // Vérifier immédiatement au montage
  if (authStore.isAuthenticated) {
    authStore.checkAuth().then((isValid) => {
      if (!isValid && router.currentRoute.value.meta.requiresAuth) {
        router.push('/login')
      }
    })
  }

  // Puis vérifier toutes les 5 minutes
  authCheckInterval = window.setInterval(async () => {
    if (authStore.isAuthenticated) {
      const isValid = await authStore.checkAuth()
      if (!isValid && router.currentRoute.value.meta.requiresAuth) {
        router.push('/login')
      }
    }
  }, 5 * 60 * 1000) // 5 minutes
}

const stopAuthCheck = () => {
  if (authCheckInterval) {
    clearInterval(authCheckInterval)
    authCheckInterval = null
  }
}

onMounted(() => {
  startAuthCheck()
})

onUnmounted(() => {
  stopAuthCheck()
})
</script>
