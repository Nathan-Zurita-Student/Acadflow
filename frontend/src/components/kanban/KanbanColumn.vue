<template>
  <div class="flex-shrink-0 w-72 flex flex-col">
    <!-- Column header -->
    <div class="flex items-center justify-between mb-3 px-1">
      <div class="flex items-center gap-2">
        <span class="w-2 h-2 rounded-full" :class="dotColor" />
        <h3 class="text-sm font-semibold text-dark-200">{{ column.label }}</h3>
        <span class="text-xs text-dark-500 bg-dark-700 px-1.5 py-0.5 rounded-full">{{ tasks.length }}</span>
      </div>
      <button @click="$emit('quick-add')" class="p-1 rounded hover:bg-dark-700 text-dark-500 hover:text-dark-300 transition-colors">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
      </button>
    </div>

    <!-- Drop zone -->
    <div
      class="flex-1 bg-dark-900/50 border border-dark-700/60 rounded-xl p-2 space-y-2 transition-colors"
      :class="{ 'border-indigo-500/50 bg-indigo-500/5': isDraggingOver }"
      @dragover.prevent="isDraggingOver = true"
      @dragleave.self="isDraggingOver = false"
      @drop.prevent="onDrop"
    >
      <KanbanCard
        v-for="(task, index) in tasks"
        :key="task.id"
        :task="task"
        :index="index"
        :project-id="projectId"
        @click="$emit('task-click', task)"
        @dragstart="onDragStart($event, task)"
        @dragend="isDraggingOver = false"
        @status-change="(status) => $emit('status-change', task, status)"
      />
      <div v-if="!tasks.length" class="h-16 flex items-center justify-center">
        <p class="text-xs text-dark-600">Arraste tarefas aqui</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useTasksStore } from '@/stores/tasks'
import type { Task, TaskStatus } from '@/types'
import KanbanCard from './KanbanCard.vue'

const props = defineProps<{
  column: { status: TaskStatus; label: string; color: string }
  tasks: Task[]
  projectId: number
}>()

const emit = defineEmits<{
  (e: 'task-moved', updates: Array<{ id: number; status: TaskStatus; position: number }>): void
  (e: 'task-click', task: Task): void
  (e: 'quick-add'): void
  (e: 'status-change', task: Task, status: TaskStatus): void
}>()

const tasksStore = useTasksStore()
const isDraggingOver = ref(false)

const dotColors: Record<string, string> = {
  backlog: 'bg-slate-500',
  pending: 'bg-yellow-500',
  in_progress: 'bg-blue-500',
  review: 'bg-purple-500',
  done: 'bg-emerald-500',
}
const dotColor = computed(() => dotColors[props.column.status])

function onDragStart(e: DragEvent, task: Task) {
  if (e.dataTransfer) {
    e.dataTransfer.setData('text/plain', String(task.id))
    e.dataTransfer.effectAllowed = 'move'
  }
}

function onDrop(e: DragEvent) {
  isDraggingOver.value = false
  const taskId = Number(e.dataTransfer?.getData('text/plain'))
  if (!taskId) return

  const task = tasksStore.tasks.find(t => t.id === taskId)
  if (!task) return

  const newStatus = props.column.status
  if (task.status === newStatus) return

  emit('task-moved', [{ id: taskId, status: newStatus, position: props.tasks.length }])
}
</script>
