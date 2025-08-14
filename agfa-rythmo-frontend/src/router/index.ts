import { createRouter, createWebHistory } from 'vue-router'



import HomeView from '../views/HomeView.vue'
import ProjectsView from '../views/ProjectsView.vue'
import ProjectDetailView from '../views/ProjectDetailView.vue'
import FinalPreviewView from '../views/FinalPreviewView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: HomeView,
    },
    {
      path: '/projects',
      name: 'projects',
      component: ProjectsView,
    },
    {
      path: '/projects/:id',
      name: 'project-detail',
      component: ProjectDetailView,
    },
    {
      path: '/final-preview',
      name: 'final-preview',
      component: FinalPreviewView,
    },
  ],
})

export default router
