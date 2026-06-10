import api from './client'
import type { Project, ProjectDashboard, MemberStats, Task, Attachment } from '@/types'

export const projectsApi = {
  list: () => api.get<Project[]>('/projects'),
  get: (id: number) => api.get<Project>(`/projects/${id}`),
  create: (data: Partial<Project>) => api.post<Project>('/projects', data),
  update: (id: number, data: Partial<Project>) => api.put<Project>(`/projects/${id}`, data),
  delete: (id: number) => api.delete(`/projects/${id}`),
  dashboard: (id: number) => api.get<ProjectDashboard>(`/projects/${id}/dashboard`),
  members: (id: number) => api.get<MemberStats[]>(`/projects/${id}/members`),
  addMember: (id: number, userId: number, role?: string) => api.post(`/projects/${id}/members`, { user_id: userId, role }),
  removeMember: (id: number, userId: number) => api.delete(`/projects/${id}/members/${userId}`),
}

export const tasksApi = {
  list: (projectId: number, params?: Record<string, string>) => api.get<Task[]>(`/projects/${projectId}/tasks`, { params }),
  get: (projectId: number, taskId: number) => api.get<Task>(`/projects/${projectId}/tasks/${taskId}`),
  create: (projectId: number, data: Partial<Task> & { tag_ids?: number[] }) => api.post<Task>(`/projects/${projectId}/tasks`, data),
  update: (projectId: number, taskId: number, data: Partial<Task> & { tag_ids?: number[] }) => api.put<Task>(`/projects/${projectId}/tasks/${taskId}`, data),
  delete: (projectId: number, taskId: number) => api.delete(`/projects/${projectId}/tasks/${taskId}`),
  reorder: (projectId: number, tasks: Array<{ id: number; status: string; position: number }>) =>
    api.post(`/projects/${projectId}/tasks/reorder`, { tasks }),
  addComment: (projectId: number, taskId: number, content: string) =>
    api.post(`/projects/${projectId}/tasks/${taskId}/comments`, { content }),
  submitApproval: (projectId: number, taskId: number) =>
    api.post<{ approval_status: string }>(`/projects/${projectId}/tasks/${taskId}/submit-approval`),
  approveTask: (projectId: number, taskId: number) =>
    api.post<{ approval_status: string }>(`/projects/${projectId}/tasks/${taskId}/approve`),
  rejectTask: (projectId: number, taskId: number, note?: string) =>
    api.post<{ approval_status: string; rejection_note: string | null }>(`/projects/${projectId}/tasks/${taskId}/reject`, { note }),
  addChecklist: (projectId: number, taskId: number, title: string) =>
    api.post(`/projects/${projectId}/tasks/${taskId}/checklists`, { title }),
  updateChecklist: (projectId: number, taskId: number, checklistId: number, completed: boolean) =>
    api.put(`/projects/${projectId}/tasks/${taskId}/checklists/${checklistId}`, { completed }),
  deleteChecklist: (projectId: number, taskId: number, checklistId: number) =>
    api.delete(`/projects/${projectId}/tasks/${taskId}/checklists/${checklistId}`),
}

export const attachmentsApi = {
  list: (projectId: number) => api.get<Attachment[]>(`/projects/${projectId}/attachments`),
  upload: (projectId: number, file: File, taskId?: number, name?: string) => {
    const form = new FormData()
    form.append('file', file)
    if (taskId) form.append('task_id', String(taskId))
    if (name)   form.append('name', name)
    return api.post<Attachment>(`/projects/${projectId}/attachments`, form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },
  viewUrl:     (projectId: number, attachmentId: number) =>
    `/api/projects/${projectId}/attachments/${attachmentId}/view`,
  downloadUrl: (projectId: number, attachmentId: number) =>
    `/api/projects/${projectId}/attachments/${attachmentId}/download`,
  delete: (projectId: number, attachmentId: number) => api.delete(`/projects/${projectId}/attachments/${attachmentId}`),
}

export const dashboardApi = {
  global:   () => api.get('/dashboard'),
  myTasks:  () => api.get('/my-tasks'),
  users:    (q: string) => api.get('/users/search', { params: { q } }),
}

export const inviteApi = {
  generate: (projectId: number, role: 'member' | 'leader') =>
    api.post<{ token: string; role: string; expires_at: string }>(`/projects/${projectId}/invite`, { role }),
  info: (token: string) =>
    api.get<{
      project: { id: number; name: string; description: string | null; members_count: number }
      role: string
      expires_at: string
    }>(`/invite/${token}`),
  accept: (token: string) =>
    api.post<{ message: string; project_id: number; role: string; already_member?: boolean }>(`/invite/${token}/accept`),
}

export const timeApi = {
  log: (projectId: number, taskId: number, seconds: number) =>
    api.post<{ time_seconds: number }>(`/projects/${projectId}/tasks/${taskId}/time`, { seconds }),
}
