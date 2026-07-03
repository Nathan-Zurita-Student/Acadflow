<template>
  <div class="min-h-screen flex items-center justify-center bg-dark-950 px-4">
    <div class="w-full max-w-md animate-slide-up">
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-accent-600 mb-4 shadow-lg shadow-accent-600/30">
          <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
          </svg>
        </div>
        <h1 class="text-2xl font-bold text-white">Confirme seu e-mail</h1>
        <p class="text-dark-400 mt-1 text-sm">
          Enviamos um código de 6 dígitos para<br />
          <span class="text-dark-200 font-medium">{{ auth.user?.email }}</span>
        </p>
      </div>

      <div class="card">
        <form @submit.prevent="submit" class="space-y-5" novalidate>
          <CodeInput v-model="code" :disabled="loading" @complete="submit" />

          <p v-if="error" class="text-sm text-red-400 bg-red-400/10 border border-red-400/20 rounded-lg px-3 py-2 text-center">
            {{ error }}
          </p>

          <button type="submit" :disabled="loading || code.length !== 6" class="btn-primary w-full justify-center">
            <span v-if="loading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
            <span v-else>Confirmar</span>
          </button>
        </form>

        <div class="mt-5 text-center text-sm text-dark-400">
          Não recebeu?
          <button
            :disabled="cooldown > 0 || resending"
            class="text-accent-400 hover:text-accent-300 font-medium ml-1 disabled:text-dark-600 disabled:cursor-not-allowed"
            @click="resend"
          >
            {{ cooldown > 0 ? `Reenviar em ${cooldown}s` : 'Reenviar código' }}
          </button>
        </div>
      </div>

      <p class="mt-4 text-center text-sm text-dark-500">
        <button class="hover:text-dark-300" @click="logout">Sair da conta</button>
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'
import { authApi } from '@/api/auth'
import CodeInput from '@/components/ui/CodeInput.vue'

const auth = useAuthStore()
const router = useRouter()
const toast = useToast()

const code = ref('')
const error = ref('')
const loading = ref(false)
const resending = ref(false)
const cooldown = ref(0)
let timer: ReturnType<typeof setInterval> | undefined

function startCooldown(seconds = 60) {
  cooldown.value = seconds
  timer = setInterval(() => {
    if (--cooldown.value <= 0 && timer) clearInterval(timer)
  }, 1000)
}

async function submit() {
  if (code.value.length !== 6 || loading.value) return
  error.value = ''
  loading.value = true
  try {
    const { data } = await authApi.verifyEmail(code.value)
    auth.setUser(data.user)
    toast.success('E-mail confirmado! Bem-vindo(a) ao AcadFlow.')
    router.replace('/')
  } catch (e: any) {
    error.value = e.response?.data?.errors?.code?.[0] ?? e.response?.data?.message ?? 'Código inválido.'
    code.value = ''
  } finally {
    loading.value = false
  }
}

async function resend() {
  if (cooldown.value > 0 || resending.value) return
  resending.value = true
  try {
    await authApi.resendEmailCode()
    toast.success('Enviamos um novo código para o seu e-mail.')
    startCooldown()
  } catch (e: any) {
    const status = e.response?.status
    toast.error(status === 429 ? 'Aguarde um pouco antes de pedir outro código.' : 'Não foi possível reenviar agora.')
    if (status === 429) startCooldown()
  } finally {
    resending.value = false
  }
}

async function logout() {
  await auth.logout()
  router.replace('/login')
}

onUnmounted(() => timer && clearInterval(timer))
</script>
