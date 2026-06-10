<template>
  <div class="space-y-6 animate-fade-in" v-if="dashboard">
    <!-- Header -->
    <div class="flex items-start justify-between">
      <div>
        <div class="flex items-center gap-2 text-sm text-dark-500 mb-1">
          <RouterLink to="/projects" class="hover:text-dark-300">Projetos</RouterLink>
          <span>/</span>
          <span class="text-dark-300">{{ dashboard.project.name }}</span>
        </div>
        <h1 class="text-xl font-bold text-white">{{ dashboard.project.name }}</h1>
        <p v-if="dashboard.project.description" class="text-dark-400 text-sm mt-1">{{ dashboard.project.description }}</p>
      </div>
      <div class="flex items-center gap-2">
        <RiskBadge :level="dashboard.risk_level" />
        <button @click="showEdit = true" class="btn-secondary">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
          </svg>
          Editar
        </button>
      </div>
    </div>

    <!-- Quick nav -->
    <div class="flex gap-2 flex-wrap">
      <RouterLink :to="`/projects/${projectId}/kanban`" class="btn-secondary text-xs">
        Kanban
      </RouterLink>
      <RouterLink :to="`/projects/${projectId}/members`" class="btn-secondary text-xs">
        Membros
      </RouterLink>
      <RouterLink :to="`/projects/${projectId}/files`" class="btn-secondary text-xs">
        Arquivos
      </RouterLink>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <StatCard label="Total de Tarefas" :value="dashboard.tasks_total" icon="task" color="blue" />
      <StatCard label="Concluídas" :value="dashboard.tasks_done" icon="check" color="green"
        :sub="`${Math.round(dashboard.tasks_done / Math.max(dashboard.tasks_total, 1) * 100)}%`" />
      <StatCard label="Atrasadas" :value="dashboard.tasks_overdue" icon="warning" color="red" />
      <div class="card">
        <p class="text-xs text-dark-400 mb-1">Progresso</p>
        <p class="text-2xl font-bold text-white">{{ dashboard.project.progress }}%</p>
        <div class="h-1.5 bg-dark-700 rounded-full overflow-hidden mt-2">
          <div class="h-full bg-accent-500 rounded-full transition-all duration-700"
            :style="{ width: dashboard.project.progress + '%' }" />
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Tasks by status donut -->
      <div class="card">
        <h3 class="text-sm font-semibold text-white mb-4">Tarefas por Status</h3>
        <apexchart type="donut" height="220" :options="donutOptions" :series="donutSeries" />
      </div>

      <!-- Weekly burndown -->
      <div class="card">
        <h3 class="text-sm font-semibold text-white mb-4">Conclusões da Semana</h3>
        <apexchart type="bar" height="220" :options="barOptions" :series="barSeries" />
      </div>
    </div>

    <!-- Timeline -->
    <div class="card">
      <h3 class="text-sm font-semibold text-white mb-4">Timeline do Projeto</h3>
      <div class="space-y-3">
        <TimelineItem
          v-for="item in timelineItems" :key="item.label"
          :label="item.label"
          :progress="item.progress"
          :count="item.count"
          :total="item.total"
        />
      </div>
    </div>

    <!-- Members + Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="card">
        <h3 class="text-sm font-semibold text-white mb-4">Membros</h3>
        <div class="space-y-3">
          <MemberRow v-for="s in dashboard.member_stats" :key="s.user.id" :stat="s" />
        </div>
      </div>

      <div class="card">
        <h3 class="text-sm font-semibold text-white mb-4">Atividade Recente</h3>
        <div class="space-y-3">
          <div v-for="log in dashboard.recent_activity" :key="log.id" class="flex items-start gap-2.5">
            <UserAvatar :user="log.user" class="w-6 h-6 rounded-full bg-dark-700 text-xs font-semibold text-dark-300 flex-shrink-0 mt-0.5" />
            <div>
              <p class="text-xs text-dark-300">
                <span class="font-medium text-dark-100">{{ log.user?.name }}</span>
                {{ actionLabel(log.action) }}
                <span v-if="log.data?.title" class="text-dark-400"> "{{ log.data.title }}"</span>
              </p>
              <p class="text-xs text-dark-600 mt-0.5">{{ timeAgo(log.created_at) }}</p>
            </div>
          </div>
          <p v-if="!dashboard.recent_activity.length" class="text-xs text-dark-500 text-center py-4">Sem atividade</p>
        </div>
      </div>
    </div>

    <ProjectModal v-if="showEdit" :project="dashboard.project" @close="showEdit = false" @updated="refresh" />
  </div>

  <div v-else-if="loading" class="space-y-6">
    <div class="animate-pulse space-y-4">
      <div class="h-8 w-64 bg-dark-800 rounded" />
      <div class="grid grid-cols-4 gap-4">
        <div v-for="i in 4" :key="i" class="h-24 bg-dark-800 rounded-xl" />
      </div>
    </div>
  </div>

  <div v-else class="flex flex-col items-center justify-center py-24 text-center">
    <div class="w-14 h-14 rounded-2xl bg-red-500/10 flex items-center justify-center mb-4">
      <svg class="w-7 h-7 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
      </svg>
    </div>
    <p class="text-white font-medium mb-1">Não foi possível carregar o projeto</p>
    <p class="text-dark-500 text-sm mb-6">Verifique se o servidor está rodando e tente novamente.</p>
    <button @click="refresh" class="btn-primary">Tentar novamente</button>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useProjectsStore } from '@/stores/projects'
import { useAuthStore } from '@/stores/auth'
import StatCard from '@/components/ui/StatCard.vue'
import RiskBadge from '@/components/ui/RiskBadge.vue'
import MemberRow from '@/components/ui/MemberRow.vue'
import TimelineItem from '@/components/ui/TimelineItem.vue'
import ProjectModal from '@/components/ui/ProjectModal.vue'
import UserAvatar from '@/components/ui/UserAvatar.vue'
import { useTimeAgo } from '@/composables/useTimeAgo'

const route = useRoute()
const router = useRouter()
const store = useProjectsStore()
const authStore = useAuthStore()
const projectId = Number(route.params.id)
const loading = ref(true)
const showEdit = ref(false)

function chartAccent(): string {
  const raw = getComputedStyle(document.documentElement).getPropertyValue('--accent-600').trim()
  return raw ? `rgb(${raw.split(/\s+/).join(',')})` : '#6366f1'
}

const dashboard = computed(() => store.currentDashboard)

const isLeader = computed(() => {
  const project = store.currentProject
  if (!project) return false
  if (project.is_owner) return true
  const uid = authStore.user?.id
  return project.members.some(m => m.id === uid && m.role === 'leader')
})

onMounted(async () => {
  await refresh()
  if (!isLeader.value) {
    router.replace(`/projects/${projectId}/kanban`)
  }
})

async function refresh() {
  loading.value = true
  showEdit.value = false
  try { await store.fetchDashboard(projectId) }
  finally { loading.value = false }
}



const statusLabels: Record<string, string> = {
  backlog: 'Backlog', pending: 'Pendente', in_progress: 'Em andamento', review: 'Revisão', done: 'Concluída',
}
const statusColors = ['#64748b', '#eab308', '#6366f1', '#8b5cf6', '#10b981']

const donutSeries = computed(() => {
  if (!dashboard.value) return []
  const s = dashboard.value.tasks_by_status
  return Object.values(s).map(Number)
})
const donutOptions = computed(() => ({
  chart: { background: 'transparent', toolbar: { show: false } },
  theme: { mode: 'dark' },
  labels: Object.keys(dashboard.value?.tasks_by_status ?? {}).map(k => statusLabels[k] ?? k),
  colors: statusColors,
  legend: { position: 'bottom', labels: { colors: '#94a3b8' } },
  plotOptions: { pie: { donut: { size: '65%' } } },
  tooltip: { theme: 'dark' },
  dataLabels: { enabled: false },
}))

type WeeklyEntry = { date: string; count: number }
const barSeries = computed(() => [{
  name: 'Concluídas',
  data: dashboard.value?.weekly_completions.map((d: WeeklyEntry) => d.count) ?? [],
}])
const barOptions = computed(() => ({
  chart: { background: 'transparent', toolbar: { show: false } },
  theme: { mode: 'dark' },
  colors: [chartAccent()],
  xaxis: {
    categories: dashboard.value?.weekly_completions.map((d: WeeklyEntry) =>
      new Date(d.date).toLocaleDateString('pt-BR', { day: 'numeric', month: 'short' })
    ) ?? [],
    labels: { style: { colors: '#64748b', fontSize: '11px' } },
    axisBorder: { show: false }, axisTicks: { show: false },
  },
  yaxis: { labels: { style: { colors: '#64748b', fontSize: '11px' } } },
  grid: { borderColor: '#1e293b' },
  plotOptions: { bar: { borderRadius: 4 } },
  dataLabels: { enabled: false },
  tooltip: { theme: 'dark' },
}))

const timelineItems = computed(() => {
  if (!dashboard.value) return []
  const s = dashboard.value.tasks_by_status
  const total = dashboard.value.tasks_total
  return [
    { label: 'Backlog', progress: total ? Math.round(((s.backlog ?? 0) / total) * 100) : 0, count: s.backlog ?? 0, total },
    { label: 'Em andamento', progress: total ? Math.round(((s.in_progress ?? 0) / total) * 100) : 0, count: s.in_progress ?? 0, total },
    { label: 'Revisão', progress: total ? Math.round(((s.review ?? 0) / total) * 100) : 0, count: s.review ?? 0, total },
    { label: 'Concluída', progress: total ? Math.round(((s.done ?? 0) / total) * 100) : 0, count: s.done ?? 0, total },
  ]
})

function actionLabel(action: string) {
  const m: Record<string, string> = {
    created_task: 'criou uma tarefa', updated_task: 'atualizou uma tarefa',
    commented_task: 'comentou em', updated_project: 'atualizou o projeto',
  }
  return m[action] ?? action
}

const { timeAgo } = useTimeAgo()
</script>
