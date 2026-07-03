<template>
  <div class="min-h-screen flex items-center justify-center bg-dark-950 px-4">
    <div class="w-full max-w-md animate-slide-up">
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-accent-600 mb-4 shadow-lg shadow-accent-600/30">
          <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
          </svg>
        </div>
        <h1 class="text-2xl font-bold text-white">Nova senha</h1>
        <p class="text-dark-400 mt-1 text-sm">Informe o código recebido e defina uma nova senha.</p>
      </div>

      <div class="card">
        <form @submit.prevent="submit" class="space-y-4" novalidate>
          <div v-if="!lockedEmail">
            <label class="label">E-mail</label>
            <input v-model="email" type="email" class="input" placeholder="seu@email.com" autocomplete="email" />
          </div>

          <div>
            <label class="label text-center block mb-3">Código de verificação</label>
            <CodeInput v-model="code" :disabled="loading" />
          </div>

          <div>
            <label class="label">Nova senha</label>
            <input v-model="password" type="password" class="input" placeholder="Mínimo 8 caracteres" autocomplete="new-password" />
            <p class="text-xs text-dark-500 mt-1">Use maiúsculas, minúsculas, números e símbolos.</p>
          </div>
          <div>
            <label class="label">Confirmar nova senha</label>
            <input v-model="passwordConfirmation" type="password" class="input" placeholder="Repita a senha" autocomplete="new-password" />
          </div>

          <p v-if="error" class="text-sm text-red-400 bg-red-400/10 border border-red-400/20 rounded-lg px-3 py-2">
            {{ error }}
          </p>

          <button type="submit" :disabled="loading" class="btn-primary w-full justify-center">
            <span v-if="loading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
            <span v-else>Redefinir senha</span>
          </button>
        </form>

        <p class="mt-4 text-center text-sm text-dark-400">
          <RouterLink to="/login" class="text-accent-400 hover:text-accent-300 font-medium">Voltar ao login</RouterLink>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from '@/composables/useToast'
import { validateFields } from '@/composables/useFormValidation'
import { authApi } from '@/api/auth'
import CodeInput from '@/components/ui/CodeInput.vue'

const router = useRouter()
const route = useRoute()
const toast = useToast()

const queryEmail = (route.query.email as string) ?? ''
const email = ref(queryEmail)
const lockedEmail = ref(!!queryEmail)
const code = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const error = ref('')
const loading = ref(false)

async function submit() {
  error.value = ''
  const msg = validateFields([
    { value: email.value, label: 'E-mail', rules: ['required', 'email'] },
    { value: password.value, label: 'Nova senha', rules: ['required', 'min:8'] },
  ])
  if (msg) { error.value = msg; return }
  if (code.value.length !== 6) { error.value = 'Informe o código de 6 dígitos.'; return }
  if (password.value !== passwordConfirmation.value) { error.value = 'As senhas não coincidem.'; return }

  loading.value = true
  try {
    await authApi.csrf()
    await authApi.resetPassword({
      email: email.value,
      code: code.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    })
    toast.success('Senha redefinida! Faça login com a nova senha.')
    router.replace('/login')
  } catch (e: any) {
    error.value = e.response?.data?.errors?.code?.[0]
      ?? e.response?.data?.errors?.password?.[0]
      ?? e.response?.data?.message
      ?? 'Não foi possível redefinir a senha.'
    code.value = ''
  } finally {
    loading.value = false
  }
}
</script>
