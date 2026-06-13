import { defineStore } from 'pinia'
import { ref } from 'vue'
import { tasksApi } from '@/api/projects'
import type { Task, TaskStatus } from '@/types'

export const useTasksStore = defineStore('tasks', () => {
  const tasks = ref<Task[]>([])
  const currentTask = ref<Task | null>(null)
  const loading = ref(false)

  async function fetchTasks(projectId: number) {
    loading.value = true
    try {
      const { data } = await tasksApi.list(projectId)
      tasks.value = data
    } finally {
      loading.value = false
    }
  }

  async function fetchTask(projectId: number, taskId: number) {
    const { data } = await tasksApi.get(projectId, taskId)
    currentTask.value = data
    return data
  }

  async function createTask(projectId: number, payload: Partial<Task> & { tag_ids?: number[]; assignee_ids?: number[] }) {
    const { data } = await tasksApi.create(projectId, payload)
    tasks.value.push(data)
    return data
  }

  async function updateTask(projectId: number, taskId: number, payload: Partial<Task> & { tag_ids?: number[]; assignee_ids?: number[] }) {
    const { data } = await tasksApi.update(projectId, taskId, payload)
    const idx = tasks.value.findIndex((t: Task) => t.id === taskId)
    if (idx !== -1) tasks.value[idx] = data
    if (currentTask.value?.id === taskId) currentTask.value = { ...currentTask.value, ...data }
    return data
  }

  async function deleteTask(projectId: number, taskId: number) {
    await tasksApi.delete(projectId, taskId)
    tasks.value = tasks.value.filter((t: Task) => t.id !== taskId)
  }

  function getByStatus(status: TaskStatus): Task[] {
    return tasks.value
      .filter((t: Task) => t.status === status)
      .sort((a: Task, b: Task) => a.position - b.position)
  }

  // ── Aplicação de eventos em tempo real (sem chamadas à API) ──
  function upsertTask(task: Task) {
    const idx = tasks.value.findIndex((t: Task) => t.id === task.id)
    if (idx !== -1) tasks.value[idx] = { ...tasks.value[idx], ...task }
    else tasks.value.push(task)
  }

  function removeTask(taskId: number) {
    tasks.value = tasks.value.filter((t: Task) => t.id !== taskId)
  }

  function applyReorder(updates: Array<{ id: number; status: TaskStatus; position: number }>) {
    for (const u of updates) {
      const t = tasks.value.find((x: Task) => x.id === u.id)
      if (t) { t.status = u.status; t.position = u.position }
    }
  }

  return {
    tasks, currentTask, loading,
    fetchTasks, fetchTask, createTask, updateTask, deleteTask, getByStatus,
    upsertTask, removeTask, applyReorder,
  }
})
