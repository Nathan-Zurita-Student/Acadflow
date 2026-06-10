import api from './client'
import type { User } from '@/types'

export interface LoginPayload { email: string; password: string }
export interface RegisterPayload { name: string; email: string; password: string; password_confirmation: string; role?: string }
export interface AuthResponse { user: User; token: string }

export const authApi = {
  login: (data: LoginPayload) => api.post<AuthResponse>('/auth/login', data),
  register: (data: RegisterPayload | FormData) => api.post<AuthResponse>('/auth/register', data),
  logout: () => api.post('/auth/logout'),
  me: () => api.get<User>('/auth/me'),
  updateProfile: (data: FormData) => api.post<User>('/auth/profile', data),
}
