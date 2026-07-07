import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi } from '@/api/auth'
import type { User } from '@/types'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const loading = ref(false)
  // `ready` indica que já tentamos hidratar a sessão via /auth/me ao menos uma vez.
  const ready = ref(false)

  const isAuthenticated = computed(() => !!user.value)
  const isVerified = computed(() => !!user.value?.email_verified)

  async function login(email: string, password: string) {
    loading.value = true
    try {
      await authApi.csrf()
      const { data } = await authApi.login({ email, password })
      user.value = data.user
      return data.user
    } finally {
      loading.value = false
    }
  }

  async function register(input: {
    name: string
    email: string
    password: string
    avatar?: File | null
    terms?: boolean
  }) {
    loading.value = true
    try {
      await authApi.csrf()
      const payload = new FormData()
      payload.append('name', input.name)
      payload.append('email', input.email)
      payload.append('password', input.password)
      payload.append('password_confirmation', input.password)
      if (input.avatar) payload.append('avatar', input.avatar)
      if (input.terms) payload.append('terms', '1')

      const { data } = await authApi.register(payload)
      user.value = data.user
      return data.user
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    try { await authApi.logout() } catch { /* ignora */ }
    user.value = null
  }

  /** Hidrata o usuário a partir do cookie de sessão. Nunca lança. */
  async function fetchMe() {
    try {
      const { data } = await authApi.me()
      user.value = data.user
    } catch {
      user.value = null
    } finally {
      ready.value = true
    }
  }

  function setUser(u: User | null) {
    user.value = u
  }

  async function updateProfile(formData: FormData) {
    const { data } = await authApi.updateProfile(formData)
    user.value = data.user
    return data.user
  }

  return {
    user, loading, ready,
    isAuthenticated, isVerified,
    login, register, logout, fetchMe, setUser, updateProfile,
  }
})
