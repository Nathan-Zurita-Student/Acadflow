<template>
  <div class="space-y-6 animate-fade-in">
    <!-- Header -->
    <div>
      <h1 class="text-xl font-bold text-white">Olá, {{ auth.user?.name?.split(' ')[0] }}!</h1>
      <p class="text-dark-400 text-sm mt-0.5">Veja um resumo de todos os seus projetos.</p>
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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Weekly chart -->
      <div class="lg:col-span-2 card">
        <h3 class="text-sm font-semibold text-white mb-4">Tarefas Concluídas — Últimos 7 dias</h3>
        <apexchart v-if="weeklyOptions" type="area" height="200" :options="weeklyOptions" :series="weeklySeries" />
      </div>

      <!-- Risk overview -->
      <div class="card">
        <h3 class="text-sm font-semibold text-white mb-4">Risco dos Projetos</h3>
        <div class="space-y-2">
          <template v-if="data?.projects.length">
            <div v-for="p in data.projects.slice(0, 5)" :key="p.id"
              class="flex items-center gap-3 p-2.5 rounded-lg hover:bg-dark-700/50 transition-colors cursor-pointer"
              @click="router.push(`/projects/${p.id}`)">
              <span :class="['w-2 h-2 rounded-full flex-shrink-0', riskColor(p.risk_level)]" />
              <span class="text-sm text-dark-200 flex-1 truncate">{{ p.name }}</span>
              <span class="text-xs text-dark-500">{{ p.progress }}%</span>
            </div>
          </template>
          <p v-else class="text-sm text-dark-500 text-center py-4">Nenhum projeto ainda</p>
        </div>
      </div>
    </div>

    <!-- Projects table + activity -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="lg:col-span-2 card">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-semibold text-white">Projetos</h3>
          <RouterLink to="/projects" class="text-xs text-indigo-400 hover:text-indigo-300">Ver todos →</RouterLink>
        </div>
        <div class="space-y-2">
          <template v-if="data?.projects.length">
            <div v-for="p in data.projects" :key="p.id"
              class="flex items-center gap-4 p-3 rounded-lg hover:bg-dark-700/50 transition-colors cursor-pointer"
              @click="router.push(`/projects/${p.id}`)">
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-dark-100 truncate">{{ p.name }}</p>
                <p class="text-xs text-dark-500 mt-0.5">{{ p.tasks_count }} tarefas</p>
              </div>
              <div class="w-24">
                <div class="flex items-center justify-between mb-1">
                  <span class="text-xs text-dark-500">{{ p.progress }}%</span>
                </div>
                <div class="h-1.5 bg-dark-700 rounded-full overflow-hidden">
                  <div class="h-full bg-indigo-500 rounded-full transition-all" :style="{ width: p.progress + '%' }" />
                </div>
              </div>
              <StatusBadge :status="p.status" />
            </div>
          </template>
          <div v-else class="py-8 text-center">
            <p class="text-dark-500 text-sm">Nenhum projeto. <RouterLink to="/projects" class="text-indigo-400">Criar projeto</RouterLink></p>
          </div>
        </div>
      </div>

      <!-- Recent activity -->
      <div class="card">
        <h3 class="text-sm font-semibold text-white mb-4">Atividade Recente</h3>
        <div class="space-y-3">
          <template v-if="data?.recent_activity.length">
            <div v-for="log in data.recent_activity.slice(0, 8)" :key="log.id"
              class="flex items-start gap-2.5">
              <div class="w-6 h-6 rounded-full bg-dark-700 flex items-center justify-center flex-shrink-0 text-xs font-semibold text-dark-300 mt-0.5">
                {{ log.user?.name?.[0] ?? '?' }}
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-xs text-dark-300">
                  <span class="font-medium text-dark-100">{{ log.user?.name }}</span>
                  {{ actionLabel(log.action) }}
                </p>
                <p class="text-xs text-dark-600 mt-0.5">{{ timeAgo(log.created_at) }}</p>
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
import { useRouter } from 'vue-router'
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

const weeklyOptions = computed(() => ({
  chart: { toolbar: { show: false }, background: 'transparent', sparkline: { enabled: false } },
  theme: { mode: 'dark' },
  colors: ['#6366f1'],
  fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0, stops: [0, 100] } },
  stroke: { curve: 'smooth', width: 2 },
  xaxis: {
    categories: data.value?.weekly_completions.map((d: any) => {
      const date = new Date(d.date)
      return date.toLocaleDateString('pt-BR', { day: 'numeric', month: 'short' })
    }) ?? [],
    labels: { style: { colors: '#64748b', fontSize: '11px' } },
    axisBorder: { show: false },
    axisTicks: { show: false },
  },
  yaxis: { labels: { style: { colors: '#64748b', fontSize: '11px' } } },
  grid: { borderColor: '#1e293b', strokeDashArray: 4 },
  tooltip: { theme: 'dark' },
}))

function riskColor(level?: string) {
  return { high: 'bg-red-500', medium: 'bg-amber-500', low: 'bg-emerald-500' }[level ?? 'low'] ?? 'bg-dark-500'
}

function actionLabel(action: string) {
  const map: Record<string, string> = {
    created_task: 'criou uma tarefa',
    updated_task: 'atualizou uma tarefa',
    commented_task: 'comentou em uma tarefa',
    updated_project: 'atualizou o projeto',
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
