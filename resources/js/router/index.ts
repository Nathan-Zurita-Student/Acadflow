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
      path: '/auth/callback',
      name: 'auth-callback',
      component: () => import('@/Pages/auth/AuthCallbackPage.vue'),
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
          path: 'my-tasks',
          name: 'my-tasks',
          component: () => import('@/Pages/MyTasksPage.vue'),
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
        {
          path: 'projects/:id/meetings',
          name: 'project-meetings',
          component: () => import('@/Pages/projects/MeetingsPage.vue'),
        },
        {
          path: 'projects/:id/notes',
          name: 'project-notes',
          component: () => import('@/Pages/projects/NotesPage.vue'),
        },
        {
          path: 'projects/:id/settings',
          name: 'project-settings',
          component: () => import('@/Pages/projects/SettingsPage.vue'),
        },
      ],
    },
    { path: '/:pathMatch(.*)*', redirect: '/' },
  ],
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (auth.isAuthenticated && !auth.user) {
    await auth.fetchMe()
  }

  if (to.meta.auth && !auth.isAuthenticated) return '/login'
  if (to.meta.guest && auth.isAuthenticated) return '/'
})

export default router
