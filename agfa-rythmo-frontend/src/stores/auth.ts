import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import authService, { type User, type LoginCredentials, type RegisterData } from '@/api/auth'
import axios from '@/api/axios'

export const useAuthStore = defineStore('auth', () => {
  // État
  const user = ref<User | null>(null)
  const token = ref<string | null>(localStorage.getItem('auth_token'))
  const loading = ref(false)

  // Getters
  const isAuthenticated = computed(() => !!token.value && !!user.value)
  const isGuest = computed(() => !isAuthenticated.value)
  const isAdmin = computed(() => isAuthenticated.value && user.value?.role === 'admin')
  const isUser = computed(() => isAuthenticated.value && user.value?.role === 'user')

  // Actions
  const setAuth = (userData: User, authToken: string) => {
    user.value = userData
    token.value = authToken
    localStorage.setItem('auth_token', authToken)

    // Configurer le header d'authentification pour Axios
    axios.defaults.headers.common['Authorization'] = `Bearer ${authToken}`
  }

  const clearAuth = () => {
    user.value = null
    token.value = null
    localStorage.removeItem('auth_token')

    // Supprimer le header d'authentification
    delete axios.defaults.headers.common['Authorization']
  }

  const login = async (credentials: LoginCredentials) => {
    loading.value = true
    try {
      const response = await authService.login(credentials)
      setAuth(response.user, response.token)
    } finally {
      loading.value = false
    }
  }

  const register = async (userData: RegisterData) => {
    loading.value = true
    try {
      const response = await authService.register(userData)
      setAuth(response.user, response.token)
    } finally {
      loading.value = false
    }
  }

  const logout = async () => {
    if (token.value) {
      try {
        await authService.logout()
      } catch (error) {
        // Même si la déconnexion côté serveur échoue, on nettoie côté client
        console.error('Erreur lors de la déconnexion:', error)
      }
    }
    clearAuth()
  }

  const fetchProfile = async () => {
    if (!token.value) return

    try {
      const response = await authService.getProfile()
      user.value = response.user
    } catch (error) {
      // Si le token n'est plus valide, on nettoie l'authentification
      clearAuth()
      throw error
    }
  }

  const updateProfile = async (data: { name: string; email: string }) => {
    const response = await authService.updateProfile(data)
    user.value = response.user
    return response
  }

  const changePassword = async (data: { current_password: string; password: string; password_confirmation: string }) => {
    const response = await authService.changePassword(data)
    // Après un changement de mot de passe, le serveur supprime tous les tokens
    clearAuth()
    return response
  }

  // Initialiser l'authentification au chargement
  const initAuth = async () => {
    if (token.value) {
      // Configurer le header d'authentification pour Axios
      axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`

      try {
        await fetchProfile()
      } catch {
        // Token invalide, nettoyer
        clearAuth()
      }
    }
  }

  return {
    // État
    user,
    token,
    loading,

    // Getters
    isAuthenticated,
    isGuest,
    isAdmin,
    isUser,

    // Actions
    login,
    register,
    logout,
    fetchProfile,
    updateProfile,
    changePassword,
    initAuth,
    setAuth,
    clearAuth
  }
})
