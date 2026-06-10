<template>
  <div class="space-y-5 animate-fade-in">

    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold text-white">Minhas Tarefas</h1>
        <p class="text-dark-400 text-sm mt-0.5">Todas as tarefas que você tem em todos os projetos</p>
      </div>
    </div>

    <!-- Stats rápidas -->
    <div v-if="!loading" class="grid grid-cols-2 md:grid-cols-4 gap-3">
      <div class="card py-3 px-4">
        <p class="text-xs text-dark-500 mb-1">Total pendente</p>
        <p class="text-2xl font-bold text-white">{{ nonDoneTasks.length }}</p>
      </div>
      <div class="card py-3 px-4">
        <p class="text-xs text-dark-500 mb-1">Atrasadas</p>
        <p class="text-2xl font-bold text-red-400">{{ overdueTasks.length }}</p>
      </div>
      <div class="card py-3 px-4">
        <p class="text-xs text-dark-500 mb-1">Vencem hoje</p>
        <p class="text-2xl font-bold text-amber-400">{{ todayTasks.length }}</p>
      </div>
      <div class="card py-3 px-4">
        <p class="text-xs text-dark-500 mb-1">Concluídas</p>
        <p class="text-2xl font-bold text-emerald-400">{{ doneTasks.length }}</p>
      </div>
    </div>

    <!-- Filtros -->
    <div v-if="!loading" class="flex flex-wrap items-center gap-2">
      <!-- Search -->
      <div class="relative min-w-[200px] max-w-xs flex-1">
        <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-dark-500 pointer-events-none"
          fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input v-model="searchQuery" placeholder="Buscar tarefas..."
          class="w-full text-xs bg-dark-800 border border-dark-700 rounded-lg pl-7 pr-3 py-1.5 text-dark-200 placeholder-dark-600 focus:outline-none focus:border-accent-500" />
      </div>

      <!-- Status -->
      <div class="flex items-center gap-1 bg-dark-800 border border-dark-700 rounded-xl p-1">
        <button v-for="f in statusFilters" :key="f.value"
          @click="filterStatus = f.value"
          :class="['px-3 py-1.5 rounded-lg text-xs font-medium transition-colors',
            filterStatus === f.value
              ? 'bg-accent-600 text-white'
              : 'text-dark-400 hover:text-dark-200']">
          {{ f.label }}
        </button>
      </div>

      <!-- Projeto -->
      <select v-model="filterProject"
        class="text-xs bg-dark-800 border border-dark-700 rounded-xl px-3 py-2 text-dark-300 focus:outline-none focus:border-accent-500">
        <option value="">Todos os projetos</option>
        <option v-for="p in uniqueProjects" :key="p.id" :value="p.id">{{ p.name }}</option>
      </select>

      <!-- Urgência -->
      <select v-model="filterUrgency"
        class="text-xs bg-dark-800 border border-dark-700 rounded-xl px-3 py-2 text-dark-300 focus:outline-none focus:border-accent-500">
        <option value="">Todas as urgências</option>
        <option value="overdue">Atrasadas</option>
        <option value="today">Vencem hoje</option>
        <option value="week">Vencem essa semana</option>
        <option value="no_date">Sem prazo</option>
      </select>

      <button
        v-if="filterStatus !== 'active' || filterProject || filterUrgency || searchQuery"
        @click="filterStatus = 'active'; filterProject = ''; filterUrgency = ''; searchQuery = ''"
        class="text-xs text-dark-500 hover:text-dark-300 px-2 py-1.5 rounded-lg hover:bg-dark-700 transition-colors">
        Limpar
      </button>

      <div class="ml-auto text-xs text-dark-500">{{ filteredTasks.length }} tarefa{{ filteredTasks.length !== 1 ? 's' : '' }}</div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="space-y-2">
      <div v-for="i in 6" :key="i" class="card animate-pulse h-16" />
    </div>

    <!-- Lista de tarefas -->
    <div v-else-if="filteredTasks.length" class="space-y-2">
      <div
        v-for="task in filteredTasks" :key="task.id"
        class="card flex items-center gap-4 hover:border-dark-600 transition-colors cursor-pointer group py-3 px-4"
        @click="openTask(task)"
      >
        <!-- Urgência dot -->
        <div class="flex-shrink-0">
          <div class="w-2.5 h-2.5 rounded-full" :class="urgencyDot(task)" />
        </div>

        <!-- Conteúdo -->
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2 flex-wrap">
            <span class="text-sm font-medium text-dark-100 truncate">{{ task.title }}</span>
            <span v-if="task.tags?.length"
              v-for="tag in task.tags.slice(0, 2)" :key="tag.id"
              class="text-[10px] px-1.5 py-0.5 rounded-full font-medium"
              :style="{ backgroundColor: tag.color + '25', color: tag.color }">
              {{ tag.name }}
            </span>
          </div>
          <div class="flex items-center gap-2 mt-0.5">
            <span class="text-xs text-accent-400">{{ task.project.name }}</span>
            <span class="text-dark-600">·</span>
            <span class="text-xs" :class="statusColor(task.status)">{{ statusLabel(task.status) }}</span>
          </div>
        </div>

        <!-- Prazo -->
        <div class="flex-shrink-0 text-right hidden sm:block">
          <span v-if="task.due_date" class="text-xs font-medium" :class="dueDateColor(task)">
            {{ dueDateLabel(task) }}
          </span>
          <span v-else class="text-xs text-dark-600">Sem prazo</span>
        </div>

        <!-- Prioridade -->
        <div class="flex-shrink-0">
          <span class="text-xs px-2 py-0.5 rounded-full font-medium" :class="priorityClass(task.priority)">
            {{ priorityLabel(task.priority) }}
          </span>
        </div>

        <!-- Ação rápida: ir ao projeto -->
        <button
          @click.stop="goToKanban(task)"
          class="flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity p-1.5 rounded-lg hover:bg-dark-700 text-dark-400 hover:text-accent-400"
          title="Abrir no Kanban">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Empty -->
    <div v-else class="text-center py-20">
      <div class="w-16 h-16 rounded-2xl bg-dark-800 flex items-center justify-center mx-auto mb-4">
        <svg class="w-8 h-8 text-dark-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
        </svg>
      </div>
      <p class="text-dark-300 font-medium">Nenhuma tarefa encontrada</p>
      <p class="text-dark-500 text-sm mt-1">
        {{ allTasks.length ? 'Tente ajustar os filtros' : 'Você não tem tarefas alocadas no momento' }}
      </p>
    </div>

    <!-- Task Modal -->
    <TaskModal
      v-if="selectedTask"
      :project-id="selectedTask.project.id"
      :task="selectedTask as any"
      @close="selectedTask = null"
      @saved="onTaskSaved"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { dashboardApi } from '@/api/projects'
import TaskModal from '@/components/tasks/TaskModal.vue'

const router = useRouter()
const loading = ref(true)
const allTasks = ref<any[]>([])
const selectedTask = ref<any>(null)

const filterStatus  = ref<'active' | 'done' | 'all'>('active')
const filterProject = ref('')
const filterUrgency = ref('')
const searchQuery   = ref('')

const statusFilters = [
  { value: 'active', label: 'Pendentes' },
  { value: 'done',   label: 'Concluídas' },
  { value: 'all',    label: 'Todas' },
]

onMounted(async () => {
  try {
    const res = await dashboardApi.myTasks()
    allTasks.value = res.data
  } finally {
    loading.value = false
  }
})

const nonDoneTasks = computed(() => allTasks.value.filter(t => t.status !== 'done'))
const doneTasks    = computed(() => allTasks.value.filter(t => t.status === 'done'))
const overdueTasks = computed(() => allTasks.value.filter(t => t.is_overdue && t.status !== 'done'))
const todayTasks   = computed(() => allTasks.value.filter(t => {
  if (!t.due_date || t.status === 'done') return false
  return new Date(t.due_date + 'T23:59:59').toDateString() === new Date().toDateString()
}))

const uniqueProjects = computed(() => {
  const map = new Map<number, { id: number; name: string }>()
  for (const t of allTasks.value) {
    if (!map.has(t.project.id)) map.set(t.project.id, t.project)
  }
  return [...map.values()].sort((a, b) => a.name.localeCompare(b.name))
})

const filteredTasks = computed(() => {
  let list = allTasks.value

  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase()
    list = list.filter(t => t.title.toLowerCase().includes(q) || t.project.name.toLowerCase().includes(q))
  }

  if (filterStatus.value === 'active')
    list = list.filter(t => t.status !== 'done')
  else if (filterStatus.value === 'done')
    list = list.filter(t => t.status === 'done')

  if (filterProject.value)
    list = list.filter(t => t.project.id === Number(filterProject.value))

  if (filterUrgency.value === 'overdue')
    list = list.filter(t => t.is_overdue)
  else if (filterUrgency.value === 'today')
    list = list.filter(t => {
      if (!t.due_date) return false
      return new Date(t.due_date + 'T23:59:59').toDateString() === new Date().toDateString()
    })
  else if (filterUrgency.value === 'week')
    list = list.filter(t => {
      if (!t.due_date) return false
      const d = new Date(t.due_date + 'T23:59:59')
      const now = new Date()
      const diff = (d.getTime() - now.getTime()) / 86400000
      return diff >= 0 && diff <= 7
    })
  else if (filterUrgency.value === 'no_date')
    list = list.filter(t => !t.due_date)

  return list
})

function urgencyDot(task: any) {
  if (task.is_overdue) return 'bg-red-500'
  if (!task.due_date) return 'bg-dark-600'
  const diff = (new Date(task.due_date + 'T23:59:59').getTime() - Date.now()) / 86400000
  if (diff < 1) return 'bg-amber-500'
  if (diff <= 3) return 'bg-yellow-500'
  return 'bg-emerald-500'
}

function dueDateLabel(task: any) {
  if (task.is_overdue) {
    const days = Math.ceil((Date.now() - new Date(task.due_date + 'T23:59:59').getTime()) / 86400000)
    return days === 1 ? 'Atrasou ontem' : `${days}d atrasada`
  }
  const diff = (new Date(task.due_date + 'T23:59:59').getTime() - Date.now()) / 86400000
  if (diff < 1) return 'Vence hoje'
  if (diff < 2) return 'Vence amanhã'
  if (diff <= 7) return `${Math.ceil(diff)}d restantes`
  return new Date(task.due_date + 'T00:00:00').toLocaleDateString('pt-BR', { day: 'numeric', month: 'short' })
}

function dueDateColor(task: any) {
  if (task.is_overdue) return 'text-red-400'
  const diff = (new Date(task.due_date + 'T23:59:59').getTime() - Date.now()) / 86400000
  if (diff < 1) return 'text-amber-400'
  if (diff <= 3) return 'text-yellow-400'
  return 'text-dark-400'
}

function statusLabel(s: string) {
  return { backlog: 'Backlog', pending: 'Pendente', in_progress: 'Em andamento', review: 'Revisão', done: 'Concluída' }[s] ?? s
}

function statusColor(s: string) {
  return { backlog: 'text-slate-400', pending: 'text-yellow-400', in_progress: 'text-blue-400', review: 'text-purple-400', done: 'text-emerald-400' }[s] ?? 'text-dark-400'
}

function priorityLabel(p: string) {
  return { low: 'Baixa', medium: 'Média', high: 'Alta', urgent: 'Urgente' }[p] ?? p
}

function priorityClass(p: string) {
  return {
    urgent: 'bg-red-500/15 text-red-400',
    high:   'bg-orange-500/15 text-orange-400',
    medium: 'bg-yellow-500/15 text-yellow-400',
    low:    'bg-slate-500/15 text-slate-400',
  }[p] ?? 'bg-dark-700 text-dark-400'
}

function openTask(task: any) {
  selectedTask.value = task
}

function goToKanban(task: any) {
  router.push(`/projects/${task.project.id}/kanban`)
}

function onTaskSaved() {
  selectedTask.value = null
  dashboardApi.myTasks().then(res => { allTasks.value = res.data })
}
</script>
