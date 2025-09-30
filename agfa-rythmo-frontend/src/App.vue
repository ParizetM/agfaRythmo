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

          <div class="flex items-center space-x-4">
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

const authStore = useAuthStore()
const router = useRouter()
const { invitationCount } = useInvitations()

const handleLogout = async () => {
  await authStore.logout()
  router.push('/login')
}
</script>
