import { createRouter, createWebHistory } from 'vue-router'
import type { RouteLocationNormalized, NavigationGuardNext } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

import HomeView from '../views/HomeView.vue'
import ProjectsView from '../views/ProjectsView.vue'
import ProjectDetailView from '../views/ProjectDetailView.vue'
import FinalPreviewView from '../views/FinalPreviewView.vue'
import LoginView from '../views/LoginView.vue'
import RegisterView from '../views/RegisterView.vue'
import ProfileView from '../views/ProfileView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    // Routes publiques
    {
      path: '/login',
      name: 'login',
      component: LoginView,
      meta: { requiresGuest: true }
    },
    {
      path: '/register',
      name: 'register',
      component: RegisterView,
      meta: { requiresGuest: true }
    },

    // Routes protégées
    {
      path: '/',
      name: 'home',
      component: HomeView,
      meta: { requiresAuth: true }
    },
    {
      path: '/projects',
      name: 'projects',
      component: ProjectsView,
      meta: { requiresAuth: true }
    },
    {
      path: '/projects/:id',
      name: 'project-detail',
      component: ProjectDetailView,
      meta: { requiresAuth: true }
    },
    {
      path: '/final-preview',
      name: 'final-preview',
      component: FinalPreviewView,
      meta: { requiresAuth: true }
    },
    {
      path: '/profile',
      name: 'profile',
      component: ProfileView,
      meta: { requiresAuth: true }
    },
  ],
})

// Guard de navigation global
router.beforeEach(async (to: RouteLocationNormalized, from: RouteLocationNormalized, next: NavigationGuardNext) => {
  const authStore = useAuthStore()

  // Initialiser l'authentification si pas encore fait
  if (authStore.token && !authStore.user) {
    try {
      await authStore.initAuth()
    } catch {
      // Si l'initialisation échoue, continuer avec l'état non authentifié
    }
  }

  // Vérifier si la route nécessite une authentification
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    // Rediriger vers la page de connexion avec l'URL de retour
    next({
      name: 'login',
      query: { redirect: to.fullPath }
    })
    return
  }

  // Vérifier si la route est réservée aux invités (non connectés)
  if (to.meta.requiresGuest && authStore.isAuthenticated) {
    // Rediriger vers la page d'accueil
    next({ name: 'home' })
    return
  }

  next()
})

export default router
