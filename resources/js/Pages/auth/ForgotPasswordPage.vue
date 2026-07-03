<template>
  <div class="min-h-screen flex items-center justify-center bg-dark-950 px-4">
    <div class="w-full max-w-md animate-slide-up">
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-accent-600 mb-4 shadow-lg shadow-accent-600/30">
          <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
          </svg>
        </div>
        <h1 class="text-2xl font-bold text-white">Recuperar senha</h1>
        <p class="text-dark-400 mt-1 text-sm">Informe seu e-mail para receber um código.</p>
      </div>

      <div class="card">
        <form v-if="!sent" @submit.prevent="submit" class="space-y-4" novalidate>
          <div>
            <label class="label">E-mail</label>
            <input v-model="email" type="email" class="input" placeholder="seu@email.com" autocomplete="email" />
          </div>

          <p v-if="error" class="text-sm text-red-400 bg-red-400/10 border border-red-400/20 rounded-lg px-3 py-2">
            {{ error }}
          </p>

          <button type="submit" :disabled="loading" class="btn-primary w-full justify-center">
            <span v-if="loading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
            <span v-else>Enviar código</span>
          </button>
        </form>

        <div v-else class="text-center space-y-4">
          <p class="text-sm text-dark-300">
            Se o e-mail estiver cadastrado, você receberá um código de recuperação em instantes.
          </p>
          <RouterLink :to="`/reset-password?email=${encodeURIComponent(email)}`" class="btn-primary w-full justify-center">
            Já tenho o código
          </RouterLink>
        </div>

        <p class="mt-4 text-center text-sm text-dark-400">
          Lembrou a senha?
          <RouterLink to="/login" class="text-accent-400 hover:text-accent-300 font-medium ml-1">Entrar</RouterLink>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { validateFields } from '@/composables/useFormValidation'
import { authApi } from '@/api/auth'

const email = ref('')
const error = ref('')
const loading = ref(false)
const sent = ref(false)

async function submit() {
  error.value = ''
  const msg = validateFields([{ value: email.value, label: 'E-mail', rules: ['required', 'email'] }])
  if (msg) { error.value = msg; return }

  loading.value = true
  try {
    await authApi.csrf() // cookie CSRF antes do POST público
    await authApi.forgotPassword(email.value)
    sent.value = true
  } catch (e: any) {
    error.value = e.response?.status === 429
      ? 'Muitas solicitações. Aguarde um pouco e tente novamente.'
      : (e.response?.data?.message ?? 'Não foi possível enviar o código.')
  } finally {
    loading.value = false
  }
}
</script>
