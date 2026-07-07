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
      path: '/forgot-password',
      name: 'forgot-password',
      component: () => import('@/Pages/auth/ForgotPasswordPage.vue'),
      meta: { guest: true },
    },
    {
      path: '/reset-password',
      name: 'reset-password',
      component: () => import('@/Pages/auth/ResetPasswordPage.vue'),
      meta: { guest: true },
    },
    {
      // Tela de confirmação de e-mail: requer login, mas NÃO e-mail verificado.
      path: '/verify-email',
      name: 'verify-email',
      component: () => import('@/Pages/auth/VerifyEmailPage.vue'),
      meta: { auth: true },
    },
    {
      path: '/auth/callback',
      name: 'auth-callback',
      component: () => import('@/Pages/auth/AuthCallbackPage.vue'),
    },
    // Páginas legais — públicas (acessíveis logado ou não).
    {
      path: '/termos',
      name: 'terms',
      component: () => import('@/Pages/legal/TermsPage.vue'),
    },
    {
      path: '/privacidade',
      name: 'privacy',
      component: () => import('@/Pages/legal/PrivacyPage.vue'),
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
          path: 'calendar',
          name: 'calendar',
          component: () => import('@/Pages/CalendarPage.vue'),
        },
        {
          path: 'projects',
          name: 'projects',
          component: () => import('@/Pages/projects/ProjectsPage.vue'),
        },
        {
          path: 'settings',
          redirect: '/settings/profile',
        },
        {
          path: 'settings/:tab',
          name: 'settings',
          component: () => import('@/Pages/settings/SettingsPage.vue'),
        },
        // Compatibilidade: /plans antigo agora vive dentro de Configurações.
        {
          path: 'plans',
          redirect: '/settings/plans',
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

  // Hidrata a sessão (via cookie) na primeira navegação.
  if (!auth.ready) {
    await auth.fetchMe()
  }

  // Rotas protegidas exigem sessão.
  if (to.meta.auth && !auth.isAuthenticated) {
    const query = to.fullPath !== '/' ? { redirect: to.fullPath } : {}
    return { path: '/login', query }
  }

  // Rotas de convidado: se já logado, manda para o destino adequado.
  if (to.meta.guest && auth.isAuthenticated) {
    return auth.isVerified ? '/' : '/verify-email'
  }

  // Gate de verificação: logado mas sem e-mail verificado só acessa /verify-email.
  if (auth.isAuthenticated && !auth.isVerified && to.meta.auth && to.name !== 'verify-email') {
    return '/verify-email'
  }

  // Já verificado não precisa da tela de verificação.
  if (to.name === 'verify-email' && auth.isVerified) {
    return '/'
  }
})

export default router
