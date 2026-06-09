<template>
  <div class="space-y-4 animate-fade-in">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold text-white">Kanban</h1>
        <p class="text-dark-400 text-sm">{{ store.tasks.length }} tarefa{{ store.tasks.length !== 1 ? 's' : '' }}</p>
      </div>
      <button @click="showCreate = true" class="btn-primary">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Nova Tarefa
      </button>
    </div>

    <!-- Board -->
    <div class="flex gap-4 overflow-x-auto pb-4 min-h-[calc(100vh-200px)]">
      <KanbanColumn
        v-for="col in columns" :key="col.status"
        :column="col"
        :tasks="tasksByStatus[col.status] ?? []"
        :project-id="projectId"
        @task-moved="handleTaskMoved"
        @task-click="openTask"
        @quick-add="handleQuickAdd(col.status)"
        @status-change="handleStatusChange"
      />
    </div>

    <TaskModal
      v-if="showCreate || selectedTask"
      :project-id="projectId"
      :task="selectedTask ?? undefined"
      :default-status="createStatus"
      @close="closeModal"
      @saved="onTaskSaved"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useTasksStore } from '@/stores/tasks'
import { tasksApi } from '@/api/projects'
import type { Task, TaskStatus } from '@/types'
import KanbanColumn from '@/components/kanban/KanbanColumn.vue'
import TaskModal from '@/components/tasks/TaskModal.vue'

const route = useRoute()
const store = useTasksStore()
const projectId = Number(route.params.id)
const showCreate = ref(false)
const createStatus = ref<TaskStatus>('backlog')
const selectedTask = ref<Task | null>(null)

const columns = [
  { status: 'backlog' as TaskStatus, label: 'Backlog', color: 'text-slate-400' },
  { status: 'pending' as TaskStatus, label: 'Pendente', color: 'text-yellow-400' },
  { status: 'in_progress' as TaskStatus, label: 'Em andamento', color: 'text-blue-400' },
  { status: 'review' as TaskStatus, label: 'Revisão', color: 'text-purple-400' },
  { status: 'done' as TaskStatus, label: 'Concluída', color: 'text-emerald-400' },
]

const tasksByStatus = computed(() => {
  const map: Partial<Record<TaskStatus, Task[]>> = {}
  for (const col of columns) {
    map[col.status] = store.getByStatus(col.status)
  }
  return map
})

onMounted(() => store.fetchTasks(projectId))

async function handleStatusChange(task: Task, status: TaskStatus) {
  await handleTaskMoved([{ id: task.id, status, position: task.position }])
}

async function handleTaskMoved(updates: Array<{ id: number; status: TaskStatus; position: number }>) {
  for (const u of updates) {
    const task = store.tasks.find(t => t.id === u.id)
    if (task) { task.status = u.status; task.position = u.position }
  }
  await tasksApi.reorder(projectId, updates)
}

function openTask(task: Task) {
  selectedTask.value = task
}

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
