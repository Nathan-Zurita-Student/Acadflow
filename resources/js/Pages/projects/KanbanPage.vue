<template>
  <div class="space-y-4 animate-fade-in">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold text-white">Kanban</h1>
        <p class="text-dark-400 text-sm">{{ store.tasks.length }} tarefa{{ store.tasks.length !== 1 ? 's' : '' }}</p>
      </div>
      <div class="flex items-center gap-3">
        <div
          v-if="isLeader && pendingApprovals > 0"
          class="flex items-center gap-2 text-xs text-yellow-400 bg-yellow-500/10 border border-yellow-500/20 rounded-xl px-3 py-2"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
          </svg>
          {{ pendingApprovals }} aguardando aprovação
        </div>

        <!-- Indicador de sincronização -->
        <div class="flex items-center gap-1.5 text-xs text-dark-600">
          <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse" />
          ao vivo
        </div>

        <button @click="showCreate = true" class="btn-primary">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Nova Tarefa
        </button>
      </div>
    </div>

    <!-- Filtros -->
    <div class="flex flex-wrap items-center gap-2">
      <div class="relative flex-1 min-w-[160px] max-w-xs">
        <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-dark-500 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input
          v-model="filterSearchInput"
          placeholder="Buscar tarefas..."
          class="w-full text-xs bg-dark-800 border border-dark-700 rounded-lg pl-7 pr-3 py-1.5 text-dark-200 placeholder-dark-600 focus:outline-none focus:border-indigo-500"
        />
      </div>
      <select v-model="filterPriority" class="text-xs bg-dark-800 border border-dark-700 rounded-lg px-2.5 py-1.5 text-dark-300 focus:outline-none focus:border-indigo-500">
        <option value="">Prioridade</option>
        <option value="urgent">Urgente</option>
        <option value="high">Alta</option>
        <option value="medium">Média</option>
        <option value="low">Baixa</option>
      </select>
      <select v-model="filterAssignee" class="text-xs bg-dark-800 border border-dark-700 rounded-lg px-2.5 py-1.5 text-dark-300 focus:outline-none focus:border-indigo-500">
        <option value="">Membro</option>
        <option v-for="m in currentProject?.members ?? []" :key="m.id" :value="m.id">{{ m.name.split(' ')[0] }}</option>
      </select>
      <!-- Filtros rápidos de urgência -->
      <div class="flex items-center gap-1">
        <button
          v-for="qf in quickFilters" :key="qf.value"
          @click="filterQuick = filterQuick === qf.value ? '' : qf.value"
          :class="['text-xs px-2.5 py-1.5 rounded-lg border transition-colors',
            filterQuick === qf.value
              ? qf.activeClass
              : 'border-dark-700 text-dark-400 hover:text-dark-200 hover:border-dark-600']">
          {{ qf.label }}
        </button>
      </div>
      <button
        v-if="filterSearchInput || filterPriority || filterAssignee || filterQuick"
        @click="filterSearchInput = ''; filterSearch = ''; filterPriority = ''; filterAssignee = ''; filterQuick = ''"
        class="text-xs text-dark-500 hover:text-dark-300 px-2 py-1.5 rounded-lg hover:bg-dark-700 transition-colors"
      >Limpar</button>
    </div>
    <p v-if="!selectedTask && !showCreate" class="text-[10px] text-dark-600">Pressione <kbd class="px-1 py-0.5 rounded bg-dark-700 text-dark-400 font-mono">N</kbd> para nova tarefa</p>

    <!-- Board -->
    <div class="flex gap-4 overflow-x-auto pb-4 min-h-[calc(100vh-260px)]">
      <KanbanColumn
        v-for="col in columns" :key="col.status"
        :column="col"
        :tasks="filteredByStatus[col.status] ?? []"
        :project-id="projectId"
        :is-leader="isLeader"
        @task-moved="handleTaskMoved"
        @task-click="openTask"
        @quick-add="handleQuickAdd(col.status)"
        @status-change="handleStatusChange"
        @approval-change="handleApprovalChange"
      />
    </div>

    <TaskModal
      v-if="showCreate || selectedTask"
      :project-id="projectId"
      :task="selectedTask ?? undefined"
      :default-status="createStatus"
      :is-leader="isLeader"
      @close="closeModal"
      @saved="onTaskSaved"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useRoute } from 'vue-router'
import { useTasksStore } from '@/stores/tasks'
import { useProjectsStore } from '@/stores/projects'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'
import { tasksApi } from '@/api/projects'
import type { Task, TaskStatus } from '@/types'
import KanbanColumn from '@/components/kanban/KanbanColumn.vue'
import TaskModal from '@/components/tasks/TaskModal.vue'

const route         = useRoute()
const store         = useTasksStore()
const projectsStore = useProjectsStore()
const authStore     = useAuthStore()
const toast         = useToast()
const projectId     = Number(route.params.id)

const { currentProject } = storeToRefs(projectsStore)

const showCreate   = ref(false)
const createStatus = ref<TaskStatus>('backlog')
const selectedTask = ref<Task | null>(null)

// filtros
const filterSearchInput = ref('')
const filterSearch      = ref('')
const filterPriority    = ref('')
const filterAssignee    = ref<number | ''>('')
const filterQuick       = ref('')

const quickFilters = [
  { value: 'overdue', label: 'Atrasadas', activeClass: 'bg-red-500/15 border-red-500/30 text-red-400' },
  { value: 'today',   label: 'Hoje',      activeClass: 'bg-amber-500/15 border-amber-500/30 text-amber-400' },
  { value: 'week',    label: 'Essa semana', activeClass: 'bg-yellow-500/15 border-yellow-500/30 text-yellow-400' },
]

let searchDebounce: ReturnType<typeof setTimeout> | null = null
watch(filterSearchInput, (val) => {
  if (searchDebounce) clearTimeout(searchDebounce)
  searchDebounce = setTimeout(() => { filterSearch.value = val }, 250)
})

const isLeader = computed(() => {
  if (!currentProject.value) return false
  if (currentProject.value.is_owner) return true
  const uid = authStore.user?.id
  return currentProject.value.members.some(m => m.id === uid && m.role === 'leader')
})

const pendingApprovals = computed(() =>
  store.tasks.filter(t => t.approval_status === 'pending').length
)

const columns = [
  { status: 'backlog'     as TaskStatus, label: 'Backlog',       color: 'text-slate-400' },
  { status: 'pending'     as TaskStatus, label: 'Pendente',      color: 'text-yellow-400' },
  { status: 'in_progress' as TaskStatus, label: 'Em andamento',  color: 'text-blue-400' },
  { status: 'review'      as TaskStatus, label: 'Revisão',       color: 'text-purple-400' },
  { status: 'done'        as TaskStatus, label: 'Concluída',     color: 'text-emerald-400' },
]

const filteredTasks = computed(() => {
  let tasks = store.tasks
  if (filterSearch.value.trim()) {
    const q = filterSearch.value.toLowerCase()
    tasks = tasks.filter(t => t.title.toLowerCase().includes(q) || t.description?.toLowerCase().includes(q))
  }
  if (filterPriority.value) {
    tasks = tasks.filter(t => t.priority === filterPriority.value)
  }
  if (filterAssignee.value) {
    tasks = tasks.filter(t =>
      t.assignees?.some(a => a.id === filterAssignee.value) || t.assignee?.id === filterAssignee.value
    )
  }
  if (filterQuick.value === 'overdue') {
    tasks = tasks.filter(t => t.due_date && new Date(t.due_date + 'T23:59:59') < new Date())
  } else if (filterQuick.value === 'today') {
    tasks = tasks.filter(t => {
      if (!t.due_date) return false
      return new Date(t.due_date + 'T23:59:59').toDateString() === new Date().toDateString()
    })
  } else if (filterQuick.value === 'week') {
    tasks = tasks.filter(t => {
      if (!t.due_date) return false
      const diff = (new Date(t.due_date + 'T23:59:59').getTime() - Date.now()) / 86400000
      return diff >= 0 && diff <= 7
    })
  }
  return tasks
})

const filteredByStatus = computed(() => {
  const map: Partial<Record<TaskStatus, Task[]>> = {}
  for (const col of columns) {
    map[col.status] = filteredTasks.value
      .filter(t => t.status === col.status)
      .sort((a, b) => a.position - b.position)
  }
  return map
})

// ── polling AJAX a cada 15s ────────────────────────────
let pollInterval: ReturnType<typeof setInterval> | null = null

function startPolling() {
  if (pollInterval) return
  pollInterval = setInterval(async () => {
    if (!document.hidden && !selectedTask.value && !showCreate.value) {
      await store.fetchTasks(projectId)
    }
  }, 15000)
}

function stopPolling() {
  if (pollInterval) { clearInterval(pollInterval); pollInterval = null }
}

function onVisibilityChange() {
  if (document.hidden) stopPolling()
  else { store.fetchTasks(projectId); startPolling() }
}

function onKeyDown(e: KeyboardEvent) {
  if (
    e.key === 'n' && !e.ctrlKey && !e.metaKey && !e.shiftKey &&
    !(e.target instanceof HTMLInputElement) &&
    !(e.target instanceof HTMLTextAreaElement) &&
    !(e.target instanceof HTMLSelectElement)
  ) {
    showCreate.value = true
  }
}

onMounted(async () => {
  store.fetchTasks(projectId)
  if (!currentProject.value || currentProject.value.id !== projectId) {
    await projectsStore.fetchProject(projectId)
  }
  startPolling()
  document.addEventListener('visibilitychange', onVisibilityChange)
  document.addEventListener('keydown', onKeyDown)
})

onUnmounted(() => {
  stopPolling()
  document.removeEventListener('visibilitychange', onVisibilityChange)
  document.removeEventListener('keydown', onKeyDown)
})

async function handleTaskMoved(updates: Array<{ id: number; status: TaskStatus; position: number }>) {
  for (const u of updates) {
    const task = store.tasks.find(t => t.id === u.id)
    if (task) { task.status = u.status; task.position = u.position }
  }
  await tasksApi.reorder(projectId, updates)
}

async function handleStatusChange(task: Task, status: TaskStatus) {
  await handleTaskMoved([{ id: task.id, status, position: task.position }])
}

function handleApprovalChange(taskId: number, status: string, note?: string) {
  const task = store.tasks.find(t => t.id === taskId)
  if (!task) return
  task.approval_status = status as Task['approval_status']
  task.rejection_note  = note ?? null
}

function openTask(task: Task) { selectedTask.value = task }

function handleQuickAdd(status: TaskStatus) {
  createStatus.value = status
  showCreate.value = true
}

function closeModal() {
  showCreate.value = false
  selectedTask.value = null
}

async function onTaskSaved() {
  closeModal()
  await store.fetchTasks(projectId)
}
</script>
