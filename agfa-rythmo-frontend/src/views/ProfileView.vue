<template>
  <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
      <div class="px-4 py-5 sm:p-6">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
          Profil utilisateur
        </h3>

        <form @submit.prevent="updateProfile" class="mt-6 space-y-6">
          <div>
            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Nom complet
            </label>
            <input
              id="name"
              v-model="form.name"
              type="text"
              required
              class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-white bg-white dark:bg-gray-800 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            />
          </div>

          <div>
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Adresse email
            </label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              required
              class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-white bg-white dark:bg-gray-800 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            />
          </div>

          <div class="flex justify-end">
            <button
              type="submit"
              :disabled="loading"
              class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-md disabled:opacity-50"
            >
              {{ loading ? 'Mise à jour...' : 'Mettre à jour' }}
            </button>
          </div>
        </form>

        <div v-if="message" class="mt-4 text-green-600 dark:text-green-400">
          {{ message }}
        </div>

        <div v-if="error" class="mt-4 text-red-600 dark:text-red-400">
          {{ error }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const loading = ref(false)
const message = ref('')
const error = ref('')

const form = reactive({
  name: '',
  email: ''
})

onMounted(() => {
  if (authStore.user) {
    form.name = authStore.user.name
    form.email = authStore.user.email
  }
})

const updateProfile = async () => {
  try {
    loading.value = true
    error.value = ''
    message.value = ''

    await authStore.updateProfile({
      name: form.name,
      email: form.email
    })

    message.value = 'Profil mis à jour avec succès'

  } catch (err: unknown) {
    if (err && typeof err === 'object' && 'response' in err) {
      const axiosError = err as { response?: { data?: { message?: string } } }
      error.value = axiosError.response?.data?.message || 'Erreur lors de la mise à jour'
    } else {
      error.value = 'Erreur lors de la mise à jour'
    }
  } finally {
    loading.value = false
  }
}
</script>
