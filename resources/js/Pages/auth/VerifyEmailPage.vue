<template>
  <AuthLayout>
    <div class="glass border-gradient rounded-2xl p-6 sm:p-8 animate-scale-in">
      <!-- Ícone + cabeçalho -->
      <div class="mb-6 text-center">
        <div class="relative mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl border border-accent-500/20 bg-accent-500/10 text-accent-300">
          <span class="absolute -inset-2 rounded-full bg-accent-500/20 blur-xl animate-glow-pulse" aria-hidden="true" />
          <svg class="relative h-7 w-7 animate-float-med" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
        </div>
        <h1 class="text-[1.5rem] font-semibold tracking-tight text-white">Confirme seu e-mail</h1>
        <p class="mt-1.5 text-sm text-dark-400">
          Enviamos um código de 6 dígitos para<br />
          <span class="font-medium text-dark-100">{{ auth.user?.email }}</span>
        </p>
      </div>

      <form class="space-y-5" novalidate @submit.prevent="submit">
        <CodeInput v-model="code" :disabled="loading" @complete="submit" />

        <transition name="alert">
          <div v-if="error" :key="error" class="flex items-center justify-center gap-2 rounded-xl border border-red-500/25 bg-red-500/10 px-3.5 py-2.5 text-sm text-red-300 animate-shake">
            <svg class="h-4 w-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
            <span>{{ error }}</span>
          </div>
        </transition>

        <AuthSubmit :loading="loading" :disabled="code.length !== 6">
          Confirmar
          <template #loading>Confirmando…</template>
        </AuthSubmit>
      </form>

      <div class="mt-5 text-center text-sm text-dark-400">
        Não recebeu?
        <button
          :disabled="cooldown > 0 || resending"
          class="ml-1 font-medium text-accent-400 transition-colors hover:text-accent-300 disabled:cursor-not-allowed disabled:text-dark-600"
          @click="resend"
        >
          {{ cooldown > 0 ? `Reenviar em ${cooldown}s` : 'Reenviar código' }}
        </button>
      </div>
    </div>

    <template #footer>
      <p class="text-center text-sm text-dark-500">
        <button class="transition-colors hover:text-dark-300" @click="logout">Sair da conta</button>
      </p>
    </template>
  </AuthLayout>
</template>

<script setup lang="ts">
import { ref, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'
import { authApi } from '@/api/auth'
import CodeInput from '@/components/ui/CodeInput.vue'
import AuthLayout from '@/components/auth/AuthLayout.vue'
import AuthSubmit from '@/components/auth/AuthSubmit.vue'

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

<style scoped>
.alert-enter-active, .alert-leave-active { transition: opacity 0.25s, transform 0.25s; }
.alert-enter-from, .alert-leave-to { opacity: 0; transform: translateY(-4px); }
</style>
