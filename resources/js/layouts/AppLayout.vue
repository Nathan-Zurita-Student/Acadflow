<template>
  <div class="flex h-screen overflow-hidden bg-dark-950">
    <!-- Sidebar -->
    <aside
      :class="['fixed inset-y-0 left-0 z-50 flex flex-col w-64 bg-dark-900 border-r border-dark-700/60 transition-transform duration-300 lg:static lg:translate-x-0',
        sidebarOpen ? 'translate-x-0' : '-translate-x-full']"
    >
      <!-- Logo -->
      <div class="flex items-center gap-3 px-5 py-4 border-b border-dark-700/60">
        <img src="/imagem/acadflow.png" alt="AcadFlow" class="h-10 w-auto object-contain" />
      </div>

      <!-- Navigation -->
      <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
        <NavItem to="/" icon="home" label="Dashboard" :exact="true" />
        <NavItem to="/projects" icon="folder" label="Projetos" />

        <!-- Current project sub-nav -->
        <template v-if="currentProject">
          <div class="mt-4 mb-2 px-2">
            <p class="text-xs font-semibold text-dark-500 uppercase tracking-wider">Projeto Atual</p>
          </div>
          <div class="ml-2 pl-3 border-l border-dark-700">
            <NavItem v-if="isLeaderOfCurrentProject" :to="`/projects/${currentProject.id}`" icon="chart" label="Visão Geral" :exact="true" />
            <NavItem :to="`/projects/${currentProject.id}/kanban`" icon="kanban" label="Kanban" />
            <NavItem :to="`/projects/${currentProject.id}/members`" icon="users" label="Membros" />
            <NavItem :to="`/projects/${currentProject.id}/files`" icon="paperclip" label="Arquivos" />
          </div>
        </template>
      </nav>

      <!-- User section -->
      <div class="px-3 py-3 border-t border-dark-700/60">
        <div class="flex items-center gap-3 px-2 py-2 rounded-lg hover:bg-dark-800 cursor-pointer group"
          @click="showProfile = true">
          <div class="w-8 h-8 rounded-full overflow-hidden flex-shrink-0">
            <img v-if="auth.user?.avatar" :src="auth.user.avatar" class="w-full h-full object-cover" alt="Avatar" />
            <div v-else class="w-full h-full bg-indigo-600/30 flex items-center justify-center text-indigo-400 text-sm font-semibold">
              {{ userInitial }}
            </div>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-dark-100 truncate">{{ auth.user?.display_name || auth.user?.name }}</p>
            <p class="text-xs text-dark-500 truncate">{{ auth.user?.email }}</p>
          </div>
          <button @click.stop="handleLogout" class="opacity-0 group-hover:opacity-100 p-1 rounded hover:bg-dark-700 transition-opacity">
            <svg class="w-4 h-4 text-dark-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
          </button>
        </div>
      </div>
    </aside>

    <ProfileModal v-if="showProfile" @close="showProfile = false" />

    <!-- Overlay -->
    <div v-if="sidebarOpen" @click="sidebarOpen = false"
      class="fixed inset-0 z-40 bg-black/50 lg:hidden" />

    <!-- Main content -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
      <!-- Topbar -->
      <header class="flex items-center gap-4 px-6 py-4 bg-dark-900/80 backdrop-blur border-b border-dark-700/60">
        <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-2 rounded-lg hover:bg-dark-700">
          <svg class="w-5 h-5 text-dark-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>
        <div class="flex-1" />
        <div class="flex items-center gap-2">
          <span class="text-xs text-dark-500 bg-dark-800 border border-dark-700 px-2 py-1 rounded-md">
            {{ currentDate }}
          </span>
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
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useProjectsStore } from '@/stores/projects'
import NavItem from '@/components/ui/NavItem.vue'
import ProfileModal from '@/components/ui/ProfileModal.vue'

const auth = useAuthStore()
const projectsStore = useProjectsStore()
const router = useRouter()
const sidebarOpen = ref(false)
const showProfile = ref(false)

const currentProject = computed(() => projectsStore.currentProject)
const userInitial = computed(() => auth.user?.name?.[0]?.toUpperCase() ?? '?')

const isLeaderOfCurrentProject = computed(() => {
  if (!currentProject.value) return false
  if (currentProject.value.is_owner) return true
  const uid = auth.user?.id
  return currentProject.value.members.some(m => m.id === uid && m.role === 'leader')
})
const currentDate = computed(() =>
  new Intl.DateTimeFormat('pt-BR', { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date())
)

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}
</script>
