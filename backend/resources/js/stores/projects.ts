import { defineStore } from 'pinia'
import { ref } from 'vue'
import { projectsApi } from '@/api/projects'
import type { Project, ProjectDashboard } from '@/types'

export const useProjectsStore = defineStore('projects', () => {
  const projects = ref<Project[]>([])
  const currentProject = ref<Project | null>(null)
  const currentDashboard = ref<ProjectDashboard | null>(null)
  const loading = ref(false)

  async function fetchProjects() {
    loading.value = true
    try {
      const { data } = await projectsApi.list()
      projects.value = data
    } finally {
      loading.value = false
    }
  }

  async function fetchProject(id: number) {
    const { data } = await projectsApi.get(id)
    currentProject.value = data
    return data
  }

  async function fetchDashboard(id: number) {
    const { data } = await projectsApi.dashboard(id)
    currentDashboard.value = data
    currentProject.value = data.project
    return data
  }

  async function createProject(payload: Partial<Project>) {
    const { data } = await projectsApi.create(payload)
    projects.value.unshift(data)
    return data
  }

  async function updateProject(id: number, payload: Partial<Project>) {
    const { data } = await projectsApi.update(id, payload)
    const idx = projects.value.findIndex((p: Project) => p.id === id)
    if (idx !== -1) projects.value[idx] = data
    if (currentProject.value?.id === id) currentProject.value = data
    return data
  }

  async function deleteProject(id: number) {
    await projectsApi.delete(id)
    projects.value = projects.value.filter((p: Project) => p.id !== id)
  }

  return {
    projects,
    currentProject,
    currentDashboard,
    loading,
    fetchProjects,
    fetchProject,
    fetchDashboard,
    createProject,
    updateProject,
    deleteProject,
  }
})
