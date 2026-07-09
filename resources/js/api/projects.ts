import api from './client'
import type { Project, ProjectDashboard, MemberStats, Task, Attachment, Meeting, ProjectNote, ProjectWebhook, ProjectColumn, CalendarTask } from '@/types'

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
  leave: (id: number) => api.delete(`/projects/${id}/leave`),
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
  markCommentsDelivered: (projectId: number, taskId: number) =>
    api.post(`/projects/${projectId}/tasks/${taskId}/comments/delivered`),
  markCommentsRead: (projectId: number, taskId: number) =>
    api.post(`/projects/${projectId}/tasks/${taskId}/comments/read`),
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

export const columnsApi = {
  list:    (projectId: number) => api.get<ProjectColumn[]>(`/projects/${projectId}/columns`),
  create:  (projectId: number, data: { label: string; color?: string }) =>
    api.post<ProjectColumn>(`/projects/${projectId}/columns`, data),
  update:  (projectId: number, columnId: number, data: { label?: string; color?: string }) =>
    api.put<ProjectColumn>(`/projects/${projectId}/columns/${columnId}`, data),
  delete:  (projectId: number, columnId: number) =>
    api.delete(`/projects/${projectId}/columns/${columnId}`),
  reorder: (projectId: number, ids: number[]) =>
    api.post<ProjectColumn[]>(`/projects/${projectId}/columns/reorder`, { ids }),
}

export const attachmentsApi = {
  list: (projectId: number) => api.get<Attachment[]>(`/projects/${projectId}/attachments`),
  upload: (projectId: number, file: File, taskId?: number, name?: string) => {
    const form = new FormData()
    form.append('file', file)
    if (taskId) form.append('task_id', String(taskId))
    if (name)   form.append('name', name)
    return api.post<Attachment>(`/projects/${projectId}/attachments`, form)
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

export interface GlobalSearchResults {
  projects: Array<{ id: number; name: string; status: string }>
  tasks: Array<{ id: number; title: string; status: string; priority: string; project: { id: number; name: string } }>
  members: Array<{ id: number; name: string; email: string; avatar: string | null; project_id: number | null }>
}

export const searchApi = {
  global: (q: string, signal?: AbortSignal) =>
    api.get<GlobalSearchResults>('/search', { params: { q }, signal }),
}

export const calendarApi = {
  /** Tarefas dos projetos do usuário cujo intervalo cruza a janela [from, to] (YYYY-MM-DD). */
  range: (from: string, to: string) => api.get<CalendarTask[]>('/calendar', { params: { from, to } }),
}

export interface AiPlanTask {
  title: string
  description: string
  status: string
  priority: string
  due_date: string | null
  suggested_assignee_id: number | null
  subtasks: string[]
}

export const aiApi = {
  generatePlan: (projectId: number, payload: { content?: string; file?: File | null; due_date?: string }) => {
    const form = new FormData()
    if (payload.content) form.append('content', payload.content)
    if (payload.file)    form.append('file', payload.file)
    if (payload.due_date) form.append('due_date', payload.due_date)
    return api.post<{ tasks: AiPlanTask[] }>(`/projects/${projectId}/ai/generate-plan`, form)
  },
  applyPlan: (projectId: number, tasks: Array<Record<string, unknown>>) =>
    api.post<{ tasks: Task[] }>(`/projects/${projectId}/ai/apply-plan`, { tasks }),
}

export const projectInvitationsApi = {
  send:    (projectId: number, userId: number, role?: string) =>
    api.post(`/projects/${projectId}/invitations`, { user_id: userId, role }),
  respond: (invitationId: number, action: 'accept' | 'decline') =>
    api.post(`/invitations/${invitationId}/respond`, { action }),
  pending: () =>
    api.get<Array<{
      id: number
      project: { id: number; name: string }
      role: string
      invited_by: { id: number; name: string; avatar: string | null }
      expires_at: string
      created_at: string
    }>>('/invitations/pending'),
}

export const inviteApi = {
  generate: (projectId: number, role: 'member' | 'leader') =>
    api.post<{ token: string; role: string; expires_at: string }>(`/projects/${projectId}/invite`, { role }),
  info: (token: string) =>
    api.get<{
      project: { id: number; name: string; description: string | null; members_count: number }
      role: string
      expires_at: string
      invited_by: { name: string; avatar: string | null } | null
    }>(`/invite/${token}`),
  accept: (token: string) =>
    api.post<{ message: string; project_id: number; role: string; already_member?: boolean }>(`/invite/${token}/accept`),
}

export const timeApi = {
  log: (projectId: number, taskId: number, seconds: number) =>
    api.post<{ time_seconds: number }>(`/projects/${projectId}/tasks/${taskId}/time`, { seconds }),
}

export const meetingsApi = {
  list:   (projectId: number) => api.get<Meeting[]>(`/projects/${projectId}/meetings`),
  create: (projectId: number, data: Partial<Meeting>) => api.post<Meeting>(`/projects/${projectId}/meetings`, data),
  update: (projectId: number, id: number, data: Partial<Meeting>) => api.put<Meeting>(`/projects/${projectId}/meetings/${id}`, data),
  delete: (projectId: number, id: number) => api.delete(`/projects/${projectId}/meetings/${id}`),
}

export const notesApi = {
  list:   (projectId: number) => api.get<ProjectNote[]>(`/projects/${projectId}/notes`),
  create: (projectId: number, data: { title: string; content?: string }) => api.post<ProjectNote>(`/projects/${projectId}/notes`, data),
  update: (projectId: number, id: number, data: { title?: string; content?: string }) => api.put<ProjectNote>(`/projects/${projectId}/notes/${id}`, data),
  delete: (projectId: number, id: number) => api.delete(`/projects/${projectId}/notes/${id}`),
}

export const webhooksApi = {
  list:   (projectId: number) => api.get<ProjectWebhook[]>(`/projects/${projectId}/webhooks`),
  create: (projectId: number, data: { url: string; events: string[] }) => api.post<ProjectWebhook>(`/projects/${projectId}/webhooks`, data),
  update: (projectId: number, id: number, data: Partial<ProjectWebhook>) => api.put<ProjectWebhook>(`/projects/${projectId}/webhooks/${id}`, data),
  delete: (projectId: number, id: number) => api.delete(`/projects/${projectId}/webhooks/${id}`),
  test:   (projectId: number, id: number) => api.post<{ success: boolean; message: string }>(`/projects/${projectId}/webhooks/${id}/test`),
}
