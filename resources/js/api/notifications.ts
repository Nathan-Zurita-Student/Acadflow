import api from './client'

export interface AppNotification {
  id: number
  type: string
  title: string
  message: string
  data: Record<string, unknown> | null
  read_at: string | null
  created_at: string
}

export const notificationsApi = {
  list: () => api.get<{ items: AppNotification[]; unread: number }>('/notifications'),
  markRead: (id: number) => api.post<AppNotification>(`/notifications/${id}/read`),
  markAllRead: () => api.post('/notifications/read-all'),
  clearAll: () => api.delete('/notifications'),
}
