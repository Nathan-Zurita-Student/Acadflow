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
        <form @submit.prevent="handleLogin" class="space-y-4">
          <div>
            <label class="label">Email</label>
            <input v-model="form.email" type="email" class="input" placeholder="seu@email.com" required />
          </div>
          <div>
            <label class="label">Senha</label>
            <input v-model="form.password" type="password" class="input" placeholder="••••••••" required />
          </div>

          <p v-if="error" class="text-sm text-red-400 bg-red-400/10 border border-red-400/20 rounded-lg px-3 py-2">
            {{ error }}
          </p>

          <button type="submit" :disabled="loading" class="btn-primary w-full justify-center">
            <span v-if="loading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
            <span v-else>Entrar</span>
          </button>
        </form>

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

const auth = useAuthStore()
const router = useRouter()
const route  = useRoute()

const form = ref({ email: '', password: '' })
const error = ref('')
const loading = ref(false)

async function handleLogin() {
  error.value = ''
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
