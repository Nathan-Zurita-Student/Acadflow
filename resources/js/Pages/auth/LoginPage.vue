<template>
  <div class="min-h-screen flex items-center justify-center bg-dark-950 px-4">
    <div class="w-full max-w-md animate-slide-up">
      <!-- Logo -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-accent-600 mb-4 shadow-lg shadow-accent-600/30">
          <svg class="w-8 h-8" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
            <!-- Graduation cap -->
            <path d="M16 6L2 13l14 7 14-7-14-7z" fill="white" fill-opacity="0.95"/>
            <path d="M8 17v6c0 0 3.5 3 8 3s8-3 8-3v-6l-8 4-8-4z" fill="white" fill-opacity="0.75"/>
            <!-- Flow line / tassel -->
            <path d="M26 13v7" stroke="white" stroke-width="1.8" stroke-linecap="round"/>
            <circle cx="26" cy="21.5" r="1.5" fill="white" fill-opacity="0.8"/>
          </svg>
        </div>
        <h1 class="text-2xl font-bold text-white">AcadFlow</h1>
        <p class="text-dark-400 mt-1 text-sm">Entre na sua conta</p>
      </div>

      <div class="card">
        <form @submit.prevent="handleLogin" class="space-y-4" novalidate>
          <div>
            <label class="label">Email</label>
            <input v-model="form.email" type="email" class="input" placeholder="seu@email.com" />
          </div>
          <div>
            <div class="flex items-center justify-between">
              <label class="label">Senha</label>
              <RouterLink to="/forgot-password" class="text-xs text-accent-400 hover:text-accent-300">Esqueci minha senha</RouterLink>
            </div>
            <input v-model="form.password" type="password" class="input" placeholder="••••••••" autocomplete="current-password" />
          </div>

          <p v-if="error" class="text-sm text-red-400 bg-red-400/10 border border-red-400/20 rounded-lg px-3 py-2">
            {{ error }}
          </p>

          <button type="submit" :disabled="loading" class="btn-primary w-full justify-center">
            <span v-if="loading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
            <span v-else>Entrar</span>
          </button>
        </form>

        <!-- Divisor -->
        <div class="flex items-center gap-3 my-4">
          <div class="flex-1 h-px bg-dark-700" />
          <span class="text-xs text-dark-500">ou</span>
          <div class="flex-1 h-px bg-dark-700" />
        </div>

        <!-- Entrar com Google -->
        <a href="/api/auth/google/redirect"
          class="w-full flex items-center justify-center gap-2.5 px-4 py-2.5 rounded-lg bg-white text-gray-700 text-sm font-medium hover:bg-gray-100 transition-colors">
          <svg class="w-4 h-4" viewBox="0 0 48 48" aria-hidden="true">
            <path fill="#FFC107" d="M43.611 20.083H42V20H24v8h11.303c-1.649 4.657-6.08 8-11.303 8-6.627 0-12-5.373-12-12s5.373-12 12-12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 12.955 4 4 12.955 4 24s8.955 20 20 20 20-8.955 20-20c0-1.341-.138-2.65-.389-3.917z"/>
            <path fill="#FF3D00" d="M6.306 14.691l6.571 4.819C14.655 15.108 18.961 12 24 12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 16.318 4 9.656 8.337 6.306 14.691z"/>
            <path fill="#4CAF50" d="M24 44c5.166 0 9.86-1.977 13.409-5.192l-6.19-5.238C29.211 35.091 26.715 36 24 36c-5.202 0-9.619-3.317-11.283-7.946l-6.522 5.025C9.505 39.556 16.227 44 24 44z"/>
            <path fill="#1976D2" d="M43.611 20.083H42V20H24v8h11.303c-.792 2.237-2.231 4.166-4.087 5.571l6.19 5.238C36.971 39.205 44 34 44 24c0-1.341-.138-2.65-.389-3.917z"/>
          </svg>
          Continuar com Google
        </a>

        <p class="mt-4 text-center text-sm text-dark-400">
          Não tem conta?
          <RouterLink :to="route.query.redirect ? `/register?redirect=${encodeURIComponent(String(route.query.redirect))}` : '/register'" class="text-accent-400 hover:text-accent-300 font-medium ml-1">Cadastre-se</RouterLink>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { validateFields } from '@/composables/useFormValidation'

const auth = useAuthStore()
const router = useRouter()
const route  = useRoute()

const form = ref({ email: '', password: '' })
const error = ref('')
const loading = ref(false)

if (route.query.error === 'google') {
  error.value = 'Não foi possível entrar com o Google. Tente novamente.'
}

async function handleLogin() {
  error.value = ''

  const msg = validateFields([
    { value: form.value.email,    label: 'Email', rules: ['required', 'email'] },
    { value: form.value.password, label: 'Senha', rules: ['required'] },
  ])
  if (msg) { error.value = msg; return }

  loading.value = true
  try {
    await auth.login(form.value.email, form.value.password)
    const redirect = route.query.redirect as string | undefined
    router.push(redirect || '/')
  } catch (e: any) {
    const errs = e.response?.data?.errors as Record<string, string[]> | undefined
    error.value = e.response?.data?.message
      ?? (errs ? Object.values(errs)[0]?.[0] : undefined)
      ?? 'Erro ao fazer login.'
  } finally {
    loading.value = false
  }
}
</script>
