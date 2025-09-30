<template>
  <div id="app" class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Navigation Bar -->
    <nav v-if="authStore.isAuthenticated" class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex items-center">
            <router-link
              to="/"
              class="text-xl font-bold text-indigo-600 dark:text-indigo-400"
            >
              AgfaRythmo
            </router-link>

            <div class="ml-10 flex items-baseline space-x-4">
              <router-link
                to="/"
                class="text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 px-3 py-2 rounded-md text-sm font-medium"
                :class="{ 'text-indigo-600 dark:text-indigo-400': $route.name === 'home' }"
              >
                Accueil
              </router-link>
              <router-link
                to="/projects"
                class="text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 px-3 py-2 rounded-md text-sm font-medium"
                :class="{ 'text-indigo-600 dark:text-indigo-400': $route.name === 'projects' }"
              >
                Projets
              </router-link>
            </div>
          </div>

          <div class="flex items-center space-x-4">
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
        </div>
      </div>
    </nav>

    <!-- Content -->
    <main>
      <router-view />
    </main>
  </div>
</template>

<script setup lang="ts">
import { useAuthStore } from './stores/auth'
import { useRouter } from 'vue-router'

const authStore = useAuthStore()
const router = useRouter()

const handleLogout = async () => {
  await authStore.logout()
  router.push('/login')
}
</script>
