import api, { csrf } from './client'
import type { User } from '@/types'

export interface LoginPayload { email: string; password: string }
export interface AuthUserResponse { user: User }
export interface ResetPasswordPayload {
  email: string
  code: string
  password: string
  password_confirmation: string
}
export interface ChangePasswordPayload {
  current_password: string
  password: string
  password_confirmation: string
}
export interface SessionInfo {
  id: string
  ip_address: string | null
  browser: string
  platform: string
  is_current: boolean
  last_active: string | null
  created_at: string | null
}

export const authApi = {
  csrf,
  login: (data: LoginPayload) => api.post<AuthUserResponse>('/auth/login', data),
  register: (data: FormData) => api.post<AuthUserResponse>('/auth/register', data),
  logout: () => api.post('/auth/logout'),
  me: () => api.get<AuthUserResponse>('/auth/me'),
  updateProfile: (data: FormData) => api.post<AuthUserResponse>('/auth/profile', data),

  // Verificação de e-mail por código
  verifyEmail: (code: string) => api.post<AuthUserResponse>('/auth/email/verify', { code }),
  resendEmailCode: () => api.post('/auth/email/resend'),

  // Recuperação de senha por código
  forgotPassword: (email: string) => api.post('/auth/forgot-password', { email }),
  resetPassword: (data: ResetPasswordPayload) => api.post('/auth/reset-password', data),

  // Perfil seguro
  changePassword: (data: ChangePasswordPayload) => api.put('/auth/password', data),
  requestEmailChange: (data: { current_password: string; email: string }) =>
    api.post('/auth/email/change', data),
  confirmEmailChange: (code: string) =>
    api.post<AuthUserResponse>('/auth/email/change/confirm', { code }),

  // Sessões
  sessions: () => api.get<{ data: SessionInfo[] }>('/auth/sessions'),
  revokeSession: (id: string) => api.delete(`/auth/sessions/${id}`),
  revokeOtherSessions: () => api.delete('/auth/sessions/others'),

  // Conta
  deleteAccount: (current_password: string) =>
    api.delete('/auth/account', { data: { current_password } }),

  // LGPD — exportação de dados pessoais (download JSON)
  exportData: () => api.get('/auth/account/export', { responseType: 'blob' }),
}
