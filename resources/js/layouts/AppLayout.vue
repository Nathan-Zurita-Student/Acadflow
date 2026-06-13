<template>
  <div class="flex h-screen overflow-hidden bg-dark-950">
    <!-- Sidebar -->
    <aside
      :class="['fixed inset-y-0 left-0 z-50 flex flex-col bg-dark-900 border-r border-dark-700/60 transition-all duration-300 lg:static lg:translate-x-0',
        sidebarOpen ? 'translate-x-0' : '-translate-x-full',
        sidebarCollapsed ? 'w-16' : 'w-64']"
    >
      <!-- Logo + collapse toggle -->
      <div class="flex items-center gap-3 px-4 py-4 border-b border-dark-700/60 flex-shrink-0">
        <img src="/imagem/acadflow.png" alt="AcadFlow" class="h-8 w-8 object-contain flex-shrink-0" />
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

        <!-- Current project sub-nav -->
        <template v-if="currentProject && !sidebarCollapsed">
          <div class="mt-4 mb-2 px-2">
            <p class="text-[10px] font-semibold text-dark-500 uppercase tracking-wider truncate">{{ currentProject.name }}</p>
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

      <!-- User section -->
      <div class="px-2 py-3 border-t border-dark-700/60 flex-shrink-0">
        <div class="flex items-center gap-2.5 px-2 py-2 rounded-lg hover:bg-dark-800 cursor-pointer group"
          :class="sidebarCollapsed ? 'justify-center' : ''"
          @click="showProfile = true">
          <UserAvatar :user="auth.user" class="w-8 h-8 rounded-full bg-accent-600/30 text-accent-400 text-sm font-semibold flex-shrink-0" />
          <template v-if="!sidebarCollapsed">
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-dark-100 truncate">{{ auth.user?.display_name || auth.user?.name }}</p>
              <p class="text-xs text-dark-500 truncate">{{ auth.user?.email }}</p>
            </div>
            <button @click.stop="handleLogout" class="opacity-0 group-hover:opacity-100 p-1 rounded hover:bg-dark-700 transition-opacity flex-shrink-0">
              <svg class="w-4 h-4 text-dark-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
              </svg>
            </button>
          </template>
        </div>
      </div>
    </aside>

    <ProfileModal v-if="showProfile" @close="showProfile = false" />
    <Toast />
    <NotificationPopup ref="notifPopupRef" />

    <!-- Overlay mobile -->
    <div v-if="sidebarOpen" @click="sidebarOpen = false"
      class="fixed inset-0 z-40 bg-black/50 lg:hidden" />

    <!-- Main content -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
      <!-- Topbar -->
      <header class="flex items-center gap-4 px-6 py-3.5 bg-dark-900/80 backdrop-blur border-b border-dark-700/60 flex-shrink-0">
        <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-2 rounded-lg hover:bg-dark-700">
          <svg class="w-5 h-5 text-dark-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>
        <!-- Breadcrumb / page title -->
        <div class="flex-1 min-w-0">
          <slot name="title" />
        </div>
        <div class="flex items-center gap-2">
          <span class="text-xs text-dark-500 bg-dark-800 border border-dark-700 px-2 py-1 rounded-md hidden sm:block">
            {{ currentDate }}
          </span>
          <NotificationBell />
        </div>
      </header>

      <!-- Page content -->
      <main class="flex-1 overflow-y-auto p-6">
        <RouterView />
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useProjectsStore } from '@/stores/projects'
import { useNotificationsStore } from '@/stores/notifications'
import { useRealtimeStore } from '@/stores/realtime'
import { useBrowserNotifications } from '@/composables/useBrowserNotifications'
import { dashboardApi } from '@/api/projects'
import echo from '@/echo'
import NavItem from '@/components/ui/NavItem.vue'
import UserAvatar from '@/components/ui/UserAvatar.vue'
import ProfileModal from '@/components/ui/ProfileModal.vue'
import NotificationBell from '@/components/ui/NotificationBell.vue'
import NotificationPopup from '@/components/ui/NotificationPopup.vue'
import Toast from '@/components/ui/Toast.vue'

const auth = useAuthStore()
const projectsStore = useProjectsStore()
const notifStore = useNotificationsStore()
const realtimeStore = useRealtimeStore()
const { requestPermission, notify: browserNotify } = useBrowserNotifications()
const router = useRouter()
const sidebarOpen = ref(false)
const sidebarCollapsed = ref(false)
const showProfile = ref(false)
const myTasksCount = ref(0)
const notifPopupRef = ref<InstanceType<typeof NotificationPopup> | null>(null)

const currentProject = computed(() => projectsStore.currentProject)

const isLeaderOfCurrentProject = computed(() => {
  if (!currentProject.value) return false
  if (currentProject.value.is_owner) return true
  const uid = auth.user?.id
  return currentProject.value.members.some(m => m.id === uid && m.role === 'leader')
})

const currentDate = computed(() =>
  new Intl.DateTimeFormat('pt-BR', { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date())
)

// Trigger popups for notifications that arrive via polling
watch(() => notifStore.popupQueue.length, () => {
  const queued = notifStore.drainPopupQueue()
  queued.forEach(n => notifPopupRef.value?.push(n))
})

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
  refreshMyTasksBadge()

  // Pede as permissões necessárias assim que o app carrega (notificações)
  requestPermission()

  // Subscribe to personal real-time notification channel
  if (auth.user?.id) {
    try {
      echoChannel = echo.private(`App.Models.User.${auth.user.id}`)
        .listen('.notification.sent', (e: any) => {
          notifStore.addIncoming(e)
          notifPopupRef.value?.push(e)
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
