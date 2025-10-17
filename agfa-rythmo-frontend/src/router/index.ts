import { createRouter, createWebHistory } from 'vue-router'
import type { RouteLocationNormalized, NavigationGuardNext } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

import ProjectsView from '../views/ProjectsView.vue'
import ProjectDetailView from '../views/ProjectDetailView.vue'
import FinalPreviewView from '../views/FinalPreviewView.vue'
import LoginView from '../views/LoginView.vue'
import RegisterView from '../views/RegisterView.vue'
import ProfileView from '../views/ProfileView.vue'
import AdminView from '../views/AdminView.vue'

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
      component: ProjectsView,
      meta: { requiresAuth: true }
    },
    {
      path: '/projects',
      redirect: '/'
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
    {
      path: '/admin',
      name: 'admin',
      component: AdminView,
      meta: { requiresAuth: true, requiresAdmin: true }
    },
  ],
})

// Guard de navigation global
router.beforeEach(async (to: RouteLocationNormalized, from: RouteLocationNormalized, next: NavigationGuardNext) => {
  const authStore = useAuthStore()

  // Vérifier systématiquement l'authentification pour les routes protégées
  if (to.meta.requiresAuth) {
    // Si pas de token stocké, rediriger vers login
    if (!authStore.token) {
      next({
        name: 'login',
        query: { redirect: to.fullPath }
      })
      return
    }

    // Vérifier la validité du token avec le backend
    const isValid = await authStore.checkAuth()
    if (!isValid) {
      // Token invalide ou expiré, rediriger vers login
      next({
        name: 'login',
        query: { redirect: to.fullPath }
      })
      return
    }
  }

  // Vérifier si la route est réservée aux invités (non connectés)
  if (to.meta.requiresGuest && authStore.isAuthenticated) {
    // Rediriger vers la page d'accueil
    next({ name: 'home' })
    return
  }

  // Vérifier si la route nécessite des droits admin
  if (to.meta.requiresAdmin && (!authStore.isAuthenticated || !authStore.isAdmin)) {
    // Rediriger vers la page d'accueil
    next({ name: 'home' })
    return
  }

  next()
})

export default router
