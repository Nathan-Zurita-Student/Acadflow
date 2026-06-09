<template>
  <div class="space-y-4 animate-fade-in">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold text-white">Kanban</h1>
        <p class="text-dark-400 text-sm">{{ store.tasks.length }} tarefa{{ store.tasks.length !== 1 ? 's' : '' }}</p>
      </div>
      <div class="flex items-center gap-3">
        <!-- Pending approvals alert (leader only) -->
        <div
          v-if="isLeader && pendingApprovals > 0"
          class="flex items-center gap-2 text-xs text-yellow-400 bg-yellow-500/10 border border-yellow-500/20 rounded-xl px-3 py-2"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
          </svg>
          {{ pendingApprovals }} tarefa{{ pendingApprovals !== 1 ? 's' : '' }} aguardando aprovação
        </div>
        <button @click="showCreate = true" class="btn-primary">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Nova Tarefa
        </button>
      </div>
    </div>

    <!-- Board -->
    <div class="flex gap-4 overflow-x-auto pb-4 min-h-[calc(100vh-200px)]">
      <KanbanColumn
        v-for="col in columns" :key="col.status"
        :column="col"
        :tasks="tasksByStatus[col.status] ?? []"
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
import { ref, computed, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useRoute } from 'vue-router'
import { useTasksStore } from '@/stores/tasks'
import { useProjectsStore } from '@/stores/projects'
import { useAuthStore } from '@/stores/auth'
import { tasksApi } from '@/api/projects'
import type { Task, TaskStatus } from '@/types'
import KanbanColumn from '@/components/kanban/KanbanColumn.vue'
import TaskModal from '@/components/tasks/TaskModal.vue'

const route          = useRoute()
const store          = useTasksStore()
const projectsStore  = useProjectsStore()
const authStore      = useAuthStore()
const projectId      = Number(route.params.id)

const { currentProject } = storeToRefs(projectsStore)

const showCreate   = ref(false)
const createStatus = ref<TaskStatus>('backlog')
const selectedTask = ref<Task | null>(null)

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

const tasksByStatus = computed(() => {
  const map: Partial<Record<TaskStatus, Task[]>> = {}
  for (const col of columns) map[col.status] = store.getByStatus(col.status)
  return map
})

onMounted(async () => {
  store.fetchTasks(projectId)
  if (!currentProject.value || currentProject.value.id !== projectId) {
    await projectsStore.fetchProject(projectId)
  }
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
