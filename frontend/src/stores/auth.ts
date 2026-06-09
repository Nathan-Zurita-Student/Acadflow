import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi } from '@/api/auth'
import type { User } from '@/types'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(localStorage.getItem('token'))
  const loading = ref(false)

  const isAuthenticated = computed(() => !!token.value)

  function setToken(t: string) {
    token.value = t
    localStorage.setItem('token', t)
  }

  function clearAuth() {
    user.value = null
    token.value = null
    localStorage.removeItem('token')
  }

  async function login(email: string, password: string) {
    loading.value = true
    try {
      const { data } = await authApi.login({ email, password })
      setToken(data.token)
      user.value = data.user
      return data
    } finally {
      loading.value = false
    }
  }

  async function register(name: string, email: string, password: string, role?: string) {
    loading.value = true
    try {
      const { data } = await authApi.register({
        name, email, password,
        password_confirmation: password,
        role,
      })
      setToken(data.token)
      user.value = data.user
      return data
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    try { await authApi.logout() } catch {}
    clearAuth()
  }

  async function fetchMe() {
    if (!token.value) return
    try {
      const { data } = await authApi.me()
      user.value = data
    } catch {
      clearAuth()
    }
  }

  return { user, token, loading, isAuthenticated, login, register, logout, fetchMe, clearAuth }
})
