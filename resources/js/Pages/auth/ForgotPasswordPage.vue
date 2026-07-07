<template>
  <AuthLayout>
    <div class="glass border-gradient rounded-2xl p-6 sm:p-8 animate-scale-in">
      <transition name="swap" mode="out-in">
        <!-- Estado inicial: pedir e-mail -->
        <div v-if="!sent" key="form">
          <div class="mb-6 text-center">
            <div class="relative mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl border border-accent-500/20 bg-accent-500/10 text-accent-300">
              <span class="absolute -inset-2 rounded-full bg-accent-500/20 blur-xl animate-glow-pulse" aria-hidden="true" />
              <svg class="relative h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 18v-2a4 4 0 0 1 4-4h4"/><circle cx="16" cy="16" r="6"/><path d="M16 14v2l1 1"/><circle cx="9" cy="7" r="4"/></svg>
            </div>
            <h1 class="text-[1.5rem] font-semibold tracking-tight text-white">Recuperar senha</h1>
            <p class="mt-1.5 text-sm text-dark-400">Informe seu e-mail para receber um código de recuperação.</p>
          </div>

          <form novalidate @submit.prevent="submit">
            <AuthField
              ref="emailField"
              v-model="email"
              label="E-mail"
              icon="mail"
              type="email"
              inputmode="email"
              autocomplete="email"
              placeholder="seu@email.com"
              :error="emailError"
              :success="emailValid"
              @blur="touched = true"
            />

            <transition name="alert">
              <div v-if="serverError" :key="serverError" class="mb-4 flex items-start gap-2.5 rounded-xl border border-red-500/25 bg-red-500/10 px-3.5 py-2.5 text-sm text-red-300 animate-shake">
                <svg class="mt-0.5 h-4 w-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                <span>{{ serverError }}</span>
              </div>
            </transition>

            <AuthSubmit :loading="loading">
              Enviar código
              <template #loading>Enviando…</template>
            </AuthSubmit>
          </form>
        </div>

        <!-- Estado enviado: sucesso -->
        <div v-else key="sent" class="text-center">
          <div class="relative mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full border border-emerald-500/25 bg-emerald-500/10 text-emerald-400">
            <span class="absolute -inset-2 rounded-full bg-emerald-500/20 blur-xl" aria-hidden="true" />
            <svg class="relative h-8 w-8 check-draw" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
          </div>
          <h1 class="text-[1.4rem] font-semibold tracking-tight text-white">Verifique seu e-mail</h1>
          <p class="mx-auto mt-2 max-w-xs text-sm leading-relaxed text-dark-400">
            Se <span class="font-medium text-dark-200">{{ email }}</span> estiver cadastrado, você receberá um código de recuperação em instantes.
          </p>
          <RouterLink :to="`/reset-password?email=${encodeURIComponent(email)}`" class="mt-6 block">
            <AuthSubmit type="button">Já tenho o código</AuthSubmit>
          </RouterLink>
        </div>
      </transition>

      <p class="mt-5 text-center text-sm text-dark-400">
        Lembrou a senha?
        <RouterLink to="/login" class="ml-1 font-medium text-accent-400 transition-colors hover:text-accent-300">Entrar</RouterLink>
      </p>
    </div>
  </AuthLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { authApi } from '@/api/auth'
import AuthLayout from '@/components/auth/AuthLayout.vue'
import AuthField from '@/components/auth/AuthField.vue'
import AuthSubmit from '@/components/auth/AuthSubmit.vue'

const email = ref('')
const touched = ref(false)
const submitted = ref(false)
const serverError = ref('')
const loading = ref(false)
const sent = ref(false)
const emailField = ref<InstanceType<typeof AuthField> | null>(null)

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
const emailValid = computed(() => EMAIL_RE.test(email.value.trim()))
const emailError = computed(() => {
  if (!(touched.value || submitted.value)) return ''
  if (!email.value.trim()) return 'Informe seu e-mail'
  if (!emailValid.value) return 'E-mail inválido'
  return ''
})

onMounted(() => emailField.value?.focus())

async function submit() {
  submitted.value = true
  serverError.value = ''
  if (emailError.value) { emailField.value?.focus(); return }

  loading.value = true
  try {
    await authApi.csrf() // cookie CSRF antes do POST público
    await authApi.forgotPassword(email.value)
    sent.value = true
  } catch (e: any) {
    serverError.value = e.response?.status === 429
      ? 'Muitas solicitações. Aguarde um pouco e tente novamente.'
      : (e.response?.data?.message ?? 'Não foi possível enviar o código.')
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.alert-enter-active, .alert-leave-active { transition: opacity 0.25s, transform 0.25s; }
.alert-enter-from, .alert-leave-to { opacity: 0; transform: translateY(-4px); }
.swap-enter-active, .swap-leave-active { transition: opacity 0.3s, transform 0.3s; }
.swap-enter-from { opacity: 0; transform: translateY(8px); }
.swap-leave-to { opacity: 0; transform: translateY(-8px); }

.check-draw path {
  stroke-dasharray: 26;
  stroke-dashoffset: 26;
  animation: draw 0.5s cubic-bezier(0.65, 0, 0.35, 1) 0.15s forwards;
}
@keyframes draw { to { stroke-dashoffset: 0; } }
</style>
