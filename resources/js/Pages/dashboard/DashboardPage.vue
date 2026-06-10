<template>
  <div class="space-y-6 animate-fade-in">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold text-white">Olá, {{ auth.user?.display_name || auth.user?.name?.split(' ')[0] }}!</h1>
        <p class="text-dark-400 text-sm mt-0.5">Aqui está um resumo dos seus projetos.</p>
      </div>
      <RouterLink to="/projects" class="btn-primary text-sm">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Novo Projeto
      </RouterLink>
    </div>

    <!-- Stats skeleton -->
    <div v-if="loading" class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div v-for="i in 4" :key="i" class="card animate-pulse h-24" />
    </div>

    <!-- Stats cards -->
    <div v-else class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <StatCard label="Projetos" :value="data?.stats.total_projects ?? 0" icon="folder" color="indigo" />
      <StatCard label="Tarefas Totais" :value="data?.stats.total_tasks ?? 0" icon="task" color="blue" />
      <StatCard label="Concluídas" :value="data?.stats.done_tasks ?? 0" icon="check" color="green"
        :sub="`${data?.stats.completion_rate ?? 0}% taxa`" />
      <StatCard label="Atrasadas" :value="data?.stats.overdue_tasks ?? 0" icon="warning" color="red" />
    </div>

    <!-- Row 1: chart + upcoming deadlines -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Weekly chart -->
      <div class="lg:col-span-2 card">
        <h3 class="text-sm font-semibold text-white mb-4">Tarefas Concluídas — Últimos 7 dias</h3>
        <apexchart v-if="!loading && weeklyOptions" type="area" height="200" :options="weeklyOptions" :series="weeklySeries" />
        <div v-else class="h-[200px] animate-pulse bg-dark-700/30 rounded-lg" />
      </div>

      <!-- Próximas entregas -->
      <div class="card">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-semibold text-white">Próximas Entregas</h3>
          <RouterLink to="/my-tasks" class="text-xs text-accent-400 hover:text-accent-300">Ver todas →</RouterLink>
        </div>
        <div v-if="loading" class="space-y-2">
          <div v-for="i in 4" :key="i" class="h-10 animate-pulse bg-dark-700/30 rounded-lg" />
        </div>
        <div v-else-if="data?.upcoming?.length" class="space-y-2">
          <div
            v-for="t in data.upcoming" :key="t.id"
            class="flex items-center gap-2.5 p-2 rounded-lg hover:bg-dark-700/50 cursor-pointer transition-colors"
            @click="router.push(`/projects/${t.project.id}/kanban`)"
          >
            <div class="w-2 h-2 rounded-full flex-shrink-0" :class="urgencyDot(t)" />
            <div class="flex-1 min-w-0">
              <p class="text-xs font-medium text-dark-200 truncate">{{ t.title }}</p>
              <p class="text-[10px] text-dark-500 truncate">{{ t.project.name }}</p>
            </div>
            <span class="text-[10px] font-medium flex-shrink-0" :class="dueDateColor(t)">
              {{ dueDateLabel(t) }}
            </span>
          </div>
        </div>
        <p v-else class="text-sm text-dark-500 text-center py-6">Nenhuma entrega nos próximos 7 dias</p>
      </div>
    </div>

    <!-- Row 2: projects + activity -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Projects table -->
      <div class="lg:col-span-2 card">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-semibold text-white">Projetos</h3>
          <RouterLink to="/projects" class="text-xs text-accent-400 hover:text-accent-300">Ver todos →</RouterLink>
        </div>
        <div v-if="loading" class="space-y-2">
          <div v-for="i in 4" :key="i" class="h-12 animate-pulse bg-dark-700/30 rounded-lg" />
        </div>
        <div v-else-if="data?.projects.length" class="space-y-1">
          <div v-for="p in data.projects.slice(0, 6)" :key="p.id"
            class="flex items-center gap-4 p-3 rounded-lg hover:bg-dark-700/50 transition-colors cursor-pointer"
            @click="router.push(`/projects/${p.id}`)">
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2">
                <p class="text-sm font-medium text-dark-100 truncate">{{ p.name }}</p>
                <span :class="['w-2 h-2 rounded-full flex-shrink-0', riskColor(p.risk_level)]" :title="`Risco: ${p.risk_level ?? 'baixo'}`" />
              </div>
              <p class="text-xs text-dark-500 mt-0.5">{{ p.tasks_count }} tarefa{{ p.tasks_count !== 1 ? 's' : '' }}</p>
            </div>
            <div class="w-24 flex-shrink-0">
              <div class="flex items-center justify-between mb-1">
                <span class="text-xs text-dark-500">{{ p.progress }}%</span>
              </div>
              <div class="h-1.5 bg-dark-700 rounded-full overflow-hidden">
                <div class="h-full rounded-full transition-all"
                  :class="p.progress >= 75 ? 'bg-emerald-500' : p.progress >= 40 ? 'bg-accent-500' : 'bg-amber-500'"
                  :style="{ width: p.progress + '%' }" />
              </div>
            </div>
            <StatusBadge :status="p.status" />
          </div>
        </div>
        <div v-else class="py-8 text-center">
          <p class="text-dark-500 text-sm">Nenhum projeto. <RouterLink to="/projects" class="text-accent-400">Criar projeto</RouterLink></p>
        </div>
      </div>

      <!-- Recent activity -->
      <div class="card">
        <h3 class="text-sm font-semibold text-white mb-4">Atividade Recente</h3>
        <div v-if="loading" class="space-y-3">
          <div v-for="i in 5" :key="i" class="h-10 animate-pulse bg-dark-700/30 rounded-lg" />
        </div>
        <div v-else class="space-y-3">
          <template v-if="data?.recent_activity.length">
            <div v-for="log in data.recent_activity.slice(0, 8)" :key="log.id"
              class="flex items-start gap-2.5">
              <div class="w-6 h-6 rounded-full bg-dark-700 flex items-center justify-center flex-shrink-0 text-xs font-semibold text-dark-300 mt-0.5">
                {{ log.user?.name?.[0] ?? '?' }}
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-xs text-dark-300 leading-snug">
                  <span class="font-medium text-dark-100">{{ log.user?.name }}</span>
                  {{ actionLabel(log.action) }}
                  <span v-if="log.project" class="text-dark-500"> em {{ log.project.name }}</span>
                </p>
                <p class="text-[10px] text-dark-600 mt-0.5">{{ timeAgo(log.created_at) }}</p>
              </div>
            </div>
          </template>
          <p v-else class="text-xs text-dark-500 text-center py-4">Sem atividade recente</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { dashboardApi } from '@/api/projects'
import { useAuthStore } from '@/stores/auth'
import StatCard from '@/components/ui/StatCard.vue'
import StatusBadge from '@/components/ui/StatusBadge.vue'

const auth = useAuthStore()
const router = useRouter()
const loading = ref(true)
const data = ref<any>(null)

onMounted(async () => {
  try {
    const res = await dashboardApi.global()
    data.value = res.data
  } finally {
    loading.value = false
  }
})

const weeklySeries = computed(() => [{
  name: 'Concluídas',
  data: data.value?.weekly_completions.map((d: any) => d.count) ?? [],
}])

const weeklyOptions = computed(() => {
  if (!data.value) return null
  return {
    chart: { toolbar: { show: false }, background: 'transparent' },
    theme: { mode: 'dark' },
    colors: ['#6366f1'],
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0, stops: [0, 100] } },
    stroke: { curve: 'smooth', width: 2 },
    xaxis: {
      categories: data.value.weekly_completions.map((d: any) => {
        return new Date(d.date).toLocaleDateString('pt-BR', { day: 'numeric', month: 'short' })
      }),
      labels: { style: { colors: '#64748b', fontSize: '11px' } },
      axisBorder: { show: false },
      axisTicks: { show: false },
    },
    yaxis: { labels: { style: { colors: '#64748b', fontSize: '11px' } }, min: 0 },
    grid: { borderColor: '#1e293b', strokeDashArray: 4 },
    tooltip: { theme: 'dark' },
    dataLabels: { enabled: false },
  }
})

function riskColor(level?: string) {
  return { high: 'bg-red-500', medium: 'bg-amber-500', low: 'bg-emerald-500' }[level ?? 'low'] ?? 'bg-dark-500'
}

function urgencyDot(task: any) {
  if (task.is_overdue) return 'bg-red-500'
  const diff = (new Date(task.due_date + 'T23:59:59').getTime() - Date.now()) / 86400000
  if (diff < 1) return 'bg-amber-500'
  if (diff <= 3) return 'bg-yellow-500'
  return 'bg-emerald-500'
}

function dueDateLabel(task: any) {
  if (task.is_overdue) return 'Atrasada'
  const diff = (new Date(task.due_date + 'T23:59:59').getTime() - Date.now()) / 86400000
  if (diff < 1) return 'Hoje'
  if (diff < 2) return 'Amanhã'
  return `${Math.ceil(diff)}d`
}

function dueDateColor(task: any) {
  if (task.is_overdue) return 'text-red-400'
  const diff = (new Date(task.due_date + 'T23:59:59').getTime() - Date.now()) / 86400000
  if (diff < 1) return 'text-amber-400'
  if (diff <= 3) return 'text-yellow-400'
  return 'text-dark-400'
}

function actionLabel(action: string) {
  const map: Record<string, string> = {
    created_task: 'criou uma tarefa',
    updated_task: 'atualizou uma tarefa',
    commented_task: 'comentou em uma tarefa',
    updated_project: 'atualizou o projeto',
    submitted_approval: 'enviou tarefa para aprovação',
    approved_task: 'aprovou uma tarefa',
    rejected_task: 'reprovou uma tarefa',
  }
  return map[action] ?? action
}

function timeAgo(date: string) {
  const diff = (Date.now() - new Date(date).getTime()) / 1000
  if (diff < 60) return 'agora'
  if (diff < 3600) return `${Math.floor(diff / 60)}m atrás`
  if (diff < 86400) return `${Math.floor(diff / 3600)}h atrás`
  return `${Math.floor(diff / 86400)}d atrás`
}
</script>
