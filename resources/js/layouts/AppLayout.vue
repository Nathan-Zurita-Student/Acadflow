<template>
  <div class="relative flex h-screen overflow-hidden bg-dark-950">
    <!-- Background ambiente vivo (sutil) -->
    <AppAmbience />

    <!-- Sidebar -->
    <aside
      :class="['fixed inset-y-0 left-0 z-50 flex flex-col bg-dark-900/85 backdrop-blur-lg border-r border-dark-700/60 transition-all duration-300 lg:static lg:translate-x-0',
        sidebarOpen ? 'translate-x-0' : '-translate-x-full',
        sidebarCollapsed ? 'w-16' : 'w-64']"
    >
      <!-- Logo + collapse toggle -->
      <div class="flex items-center gap-3 px-4 py-4 border-b border-dark-700/60 flex-shrink-0">
        <span class="relative flex-shrink-0">
          <span class="absolute -inset-1 rounded-full bg-accent-500/25 blur-md animate-glow-pulse" aria-hidden="true" />
          <img src="/imagem/acadflow.png" alt="AcadFlow" class="relative h-8 w-8 object-contain" />
        </span>
        <template v-if="!sidebarCollapsed">
          <span class="font-bold text-white text-sm tracking-wide">AcadFlow</span>
          <!-- Desktop: collapse -->
          <button @click="sidebarCollapsed = true"
            class="ml-auto p-1 rounded-lg text-dark-500 hover:text-dark-300 hover:bg-dark-800 transition-colors hidden lg:flex">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7M18 19l-7-7 7-7" />
            </svg>
          </button>
          <!-- Mobile: close sidebar -->
          <button @click="sidebarOpen = false"
            class="ml-auto p-1 rounded-lg text-dark-500 hover:text-dark-300 hover:bg-dark-800 transition-colors lg:hidden">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </template>
        <button v-else @click="sidebarCollapsed = false"
          class="mx-auto p-1 rounded-lg text-dark-500 hover:text-dark-300 hover:bg-dark-800 transition-colors hidden lg:flex">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M6 5l7 7-7 7" />
          </svg>
        </button>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 px-2 py-4 space-y-0.5 overflow-y-auto overflow-x-hidden">
        <NavItem to="/" icon="home" :label="sidebarCollapsed ? '' : 'Dashboard'" :exact="true" :collapsed="sidebarCollapsed" />
        <NavItem to="/my-tasks" icon="task" :label="sidebarCollapsed ? '' : 'Minhas Tarefas'" :collapsed="sidebarCollapsed">
          <template #badge>
            <span v-if="myTasksCount > 0 && !sidebarCollapsed"
              class="ml-auto text-[10px] font-bold bg-accent-600 text-white rounded-full px-1.5 py-0.5 leading-none min-w-[18px] text-center">
              {{ myTasksCount > 99 ? '99+' : myTasksCount }}
            </span>
          </template>
        </NavItem>
        <NavItem to="/projects" icon="folder" :label="sidebarCollapsed ? '' : 'Projetos'" :collapsed="sidebarCollapsed" />
        <NavItem to="/calendar" icon="calendar" :label="sidebarCollapsed ? '' : 'Calendário'" :collapsed="sidebarCollapsed" />

        <!-- Current project sub-nav -->
        <template v-if="currentProject && !sidebarCollapsed">
          <div class="mt-4 mb-2 mx-1 px-3 py-2 rounded-lg bg-accent-600/10 border border-accent-500/25">
            <p class="text-[10px] font-semibold text-accent-400/80 uppercase tracking-wider">Projeto:</p>
            <p class="text-sm font-bold text-white truncate leading-tight">{{ currentProject.name }}</p>
          </div>
          <div class="ml-2 pl-3 border-l border-dark-700">
            <NavItem v-if="isLeaderOfCurrentProject" :to="`/projects/${currentProject.id}`" icon="chart" label="Visão Geral" :exact="true" />
            <NavItem :to="`/projects/${currentProject.id}/kanban`" icon="kanban" label="Kanban" />
            <NavItem :to="`/projects/${currentProject.id}/members`" icon="users" label="Membros" />
            <NavItem :to="`/projects/${currentProject.id}/files`" icon="paperclip" label="Arquivos" />
            <NavItem :to="`/projects/${currentProject.id}/meetings`" icon="calendar" label="Reuniões" />
            <NavItem :to="`/projects/${currentProject.id}/notes`" icon="notes" label="Notas" />
            <NavItem v-if="isLeaderOfCurrentProject" :to="`/projects/${currentProject.id}/settings`" icon="settings" label="Configurações" />
          </div>
        </template>
      </nav>

      <!-- Configurações -->
      <div class="px-2 py-3 border-t border-dark-700/60 flex-shrink-0">
        <div class="flex items-center gap-2.5 px-2 py-2 rounded-lg hover:bg-dark-800 cursor-pointer transition-colors"
          :class="[sidebarCollapsed ? 'justify-center' : '', isSettingsActive ? 'bg-accent-600/20 text-accent-400' : 'text-dark-300']"
          title="Configurações"
          @click="router.push('/settings')">
          <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          <span v-if="!sidebarCollapsed" class="text-sm font-medium">Configurações</span>
        </div>
      </div>
    </aside>
    <Toast />
    <ConfirmDialog />
    <CommandPalette />
    <ShortcutsHelp />
    <OnboardingTour />

    <!-- Overlay mobile -->
    <div v-if="sidebarOpen" @click="sidebarOpen = false"
      class="fixed inset-0 z-40 bg-black/50 lg:hidden" />

    <!-- Main content -->
    <div class="relative z-10 flex-1 flex flex-col min-w-0 overflow-hidden">
      <!-- Topbar -->
      <header class="flex items-center gap-3 sm:gap-4 px-4 sm:px-6 py-3.5 bg-dark-900/70 backdrop-blur-md border-b border-dark-700/60 flex-shrink-0">
        <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-2 rounded-lg hover:bg-dark-700">
          <svg class="w-5 h-5 text-dark-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>
        <!-- Breadcrumb / page title -->
        <div class="flex-1 min-w-0">
          <slot name="title" />
        </div>
        <div class="flex items-center gap-2 sm:gap-3">
          <!-- Gatilho da Busca Global (Ctrl+K) -->
          <button
            type="button"
            title="Buscar (Ctrl+K)"
            class="flex items-center gap-2 rounded-lg border border-dark-700 bg-dark-800/60 px-2.5 py-1.5 text-dark-400 transition-colors hover:border-dark-600 hover:text-dark-200"
            @click="openPalette"
          >
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <span class="hidden text-sm lg:inline">Buscar…</span>
            <kbd class="hidden rounded border border-dark-600 bg-dark-900 px-1.5 py-0.5 text-[10px] font-medium lg:inline">{{ searchHint }}</kbd>
          </button>
          <span class="text-xs text-dark-500 bg-dark-800 border border-dark-700 px-2 py-1 rounded-md hidden lg:block">
            {{ currentDate }}
          </span>
          <NotificationBell />

          <!-- Perfil do usuário -->
          <div class="flex items-center gap-2 pl-2 sm:pl-3 border-l border-dark-700">
            <RouterLink to="/settings" class="flex items-center gap-2 group" title="Configurações">
              <UserAvatar :user="auth.user" class="w-8 h-8 rounded-full bg-accent-600/30 text-accent-400 text-sm font-semibold flex-shrink-0" />
              <span class="text-sm font-medium text-dark-100 group-hover:text-white truncate max-w-[120px] hidden sm:block">
                {{ auth.user?.display_name || auth.user?.name }}
              </span>
            </RouterLink>
            <button @click="handleLogout" title="Sair"
              class="p-1.5 rounded-lg hover:bg-dark-700 text-dark-400 hover:text-red-400 transition-colors">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
              </svg>
            </button>
          </div>
        </div>
      </header>

      <!-- Page content -->
      <main class="flex-1 overflow-y-auto p-4 sm:p-6">
        <RouterView v-slot="{ Component, route: r }">
          <!-- Só opacity + transform (compositor/GPU) — fluido em hardware modesto. -->
          <transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 translate-y-3"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-1"
            mode="out-in"
          >
            <component :is="Component" :key="r.path" />
          </transition>
        </RouterView>
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useProjectsStore } from '@/stores/projects'
import { useNotificationsStore } from '@/stores/notifications'
import { useRealtimeStore } from '@/stores/realtime'
import { useBrowserNotifications } from '@/composables/useBrowserNotifications'
import { dashboardApi } from '@/api/projects'
import echo from '@/echo'
import NavItem from '@/components/ui/NavItem.vue'
import UserAvatar from '@/components/ui/UserAvatar.vue'
import NotificationBell from '@/components/ui/NotificationBell.vue'
import Toast from '@/components/ui/Toast.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import CommandPalette from '@/components/ui/CommandPalette.vue'
import ShortcutsHelp from '@/components/ui/ShortcutsHelp.vue'
import AppAmbience from '@/components/ui/AppAmbience.vue'
import OnboardingTour from '@/components/ui/OnboardingTour.vue'
import { useShortcuts } from '@/composables/useShortcuts'
import { useOnboarding } from '@/composables/useOnboarding'

const auth = useAuthStore()
const projectsStore = useProjectsStore()
const notifStore = useNotificationsStore()
const realtimeStore = useRealtimeStore()
const { requestPermission, notify: browserNotify } = useBrowserNotifications()
const router = useRouter()
const route = useRoute()
const sidebarOpen = ref(false)
const sidebarCollapsed = ref(false)
const myTasksCount = ref(0)

// Atalhos globais + Busca Global (Ctrl+K)
const { install: installShortcuts, uninstall: uninstallShortcuts, openPalette } = useShortcuts()
const { maybeAutoStart } = useOnboarding()
const searchHint = /Mac|iPhone|iPad/.test(navigator.platform || navigator.userAgent) ? '⌘K' : 'Ctrl K'

const currentProject = computed(() => projectsStore.currentProject)
const isSettingsActive = computed(() => route.path.startsWith('/settings'))

const isLeaderOfCurrentProject = computed(() => {
  if (!currentProject.value) return false
  if (currentProject.value.is_owner) return true
  const uid = auth.user?.id
  return currentProject.value.members.some(m => m.id === uid && m.role === 'leader')
})

const currentDate = computed(() =>
  new Intl.DateTimeFormat('pt-BR', { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date())
)

let echoChannel: ReturnType<typeof echo.private> | null = null

function navigateToNotification(data: Record<string, any> | null | undefined) {
  if (data?.project_id) {
    const base = `/projects/${data.project_id}`
    router.push(data.task_id ? `${base}/kanban` : base)
  }
}

async function refreshMyTasksBadge() {
  try {
    const res = await dashboardApi.myTasks()
    myTasksCount.value = (res.data as any[]).filter((t: any) => t.status !== 'done').length
  } catch {}
}

onMounted(async () => {
  installShortcuts()
  // Tour de boas-vindas no primeiro acesso (nunca repete sozinho).
  maybeAutoStart(auth.user?.id)
  refreshMyTasksBadge()

  // Pede as permissões necessárias assim que o app carrega (notificações)
  requestPermission()

  // Subscribe to personal real-time notification channel
  if (auth.user?.id) {
    try {
      echoChannel = echo.private(`App.Models.User.${auth.user.id}`)
        .listen('.notification.sent', (e: any) => {
          notifStore.addIncoming(e)
          browserNotify(e, () => navigateToNotification(e.data))
        })
        .listen('.dashboard.stale', () => {
          realtimeStore.markDashboardStale()
          refreshMyTasksBadge()
        })
    } catch (err) {
      // Reverb may not be running in dev — gracefully degrade to polling
      console.warn('[AcadFlow] Reverb not available, falling back to polling', err)
    }
  }
})

onUnmounted(() => {
  uninstallShortcuts()
  if (echoChannel) {
    echo.leave(`App.Models.User.${auth.user?.id}`)
    echoChannel = null
  }
})

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}
</script>
