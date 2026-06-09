import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/invite/:token',
      name: 'accept-invite',
      component: () => import('@/Pages/AcceptInvitePage.vue'),
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('@/Pages/auth/LoginPage.vue'),
      meta: { guest: true },
    },
    {
      path: '/register',
      name: 'register',
      component: () => import('@/Pages/auth/RegisterPage.vue'),
      meta: { guest: true },
    },
    {
      path: '/',
      component: () => import('@/layouts/AppLayout.vue'),
      meta: { auth: true },
      children: [
        {
          path: '',
          name: 'dashboard',
          component: () => import('@/Pages/dashboard/DashboardPage.vue'),
        },
        {
          path: 'projects',
          name: 'projects',
          component: () => import('@/Pages/projects/ProjectsPage.vue'),
        },
        {
          path: 'projects/:id',
          name: 'project-detail',
          component: () => import('@/Pages/projects/ProjectDetailPage.vue'),
        },
        {
          path: 'projects/:id/kanban',
          name: 'project-kanban',
          component: () => import('@/Pages/projects/KanbanPage.vue'),
        },
        {
          path: 'projects/:id/members',
          name: 'project-members',
          component: () => import('@/Pages/projects/MembersPage.vue'),
        },
        {
          path: 'projects/:id/files',
          name: 'project-files',
          component: () => import('@/Pages/projects/FilesPage.vue'),
        },
      ],
    },
    { path: '/:pathMatch(.*)*', redirect: '/' },
  ],
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (to.meta.auth && !auth.isAuthenticated) return '/login'
  if (to.meta.guest && auth.isAuthenticated) return '/'
})

export default router
