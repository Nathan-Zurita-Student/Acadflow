<template>
  <AuthLayout>
    <div class="glass border-gradient rounded-2xl p-6 sm:p-8 animate-scale-in">
      <div class="mb-6 text-center">
        <div class="relative mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl border border-accent-500/20 bg-accent-500/10 text-accent-300">
          <span class="absolute -inset-2 rounded-full bg-accent-500/20 blur-xl animate-glow-pulse" aria-hidden="true" />
          <svg class="relative h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/><circle cx="12" cy="16" r="1"/></svg>
        </div>
        <h1 class="text-[1.5rem] font-semibold tracking-tight text-white">Nova senha</h1>
        <p class="mt-1.5 text-sm text-dark-400">Informe o código recebido e defina uma nova senha.</p>
      </div>

      <form novalidate @submit.prevent="submit">
        <AuthField
          v-if="!lockedEmail"
          v-model="email"
          label="E-mail"
          icon="mail"
          type="email"
          inputmode="email"
          autocomplete="email"
          placeholder="seu@email.com"
          :error="emailError"
          :success="emailValid"
          @blur="touched.email = true"
        />

        <div class="mb-4">
          <label class="mb-2.5 block text-center text-[13px] font-medium text-dark-300">Código de verificação</label>
          <CodeInput v-model="code" :disabled="loading" />
        </div>

        <div>
          <label class="mb-1.5 block text-[13px] font-medium text-dark-300">Nova senha</label>
          <AuthField
            v-model="password"
            icon="lock"
            type="password"
            autocomplete="new-password"
            placeholder="Crie uma senha forte"
            :error="passwordError"
            :success="passwordStrong"
            @blur="touched.password = true"
          />
          <transition name="collapse">
            <div v-if="password" class="-mt-1 mb-3">
              <div class="flex gap-1.5">
                <span v-for="i in 4" :key="i" class="h-1 flex-1 rounded-full transition-all duration-300" :class="i <= strength.bars ? strength.barClass : 'bg-dark-700'" />
              </div>
              <div class="mt-1.5 flex items-center justify-between">
                <span class="text-[11.5px] font-medium" :class="strength.textClass">{{ strength.label }}</span>
                <span class="text-[11px] text-dark-500">maiúsc. · minúsc. · número · símbolo</span>
              </div>
            </div>
          </transition>
        </div>

        <AuthField
          v-model="passwordConfirmation"
          label="Confirmar nova senha"
          icon="lock"
          type="password"
          autocomplete="new-password"
          placeholder="Repita a senha"
          :error="confirmError"
          :success="!!passwordConfirmation && password === passwordConfirmation"
          @blur="touched.confirm = true"
        />

        <transition name="alert">
          <div v-if="serverError" :key="serverError" class="mb-4 flex items-start gap-2.5 rounded-xl border border-red-500/25 bg-red-500/10 px-3.5 py-2.5 text-sm text-red-300 animate-shake">
            <svg class="mt-0.5 h-4 w-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
            <span>{{ serverError }}</span>
          </div>
        </transition>

        <AuthSubmit :loading="loading" :success="success">
          Redefinir senha
          <template #loading>Redefinindo…</template>
          <template #success>Senha alterada!</template>
        </AuthSubmit>
      </form>

      <p class="mt-5 text-center text-sm text-dark-400">
        <RouterLink to="/login" class="font-medium text-accent-400 transition-colors hover:text-accent-300">Voltar ao login</RouterLink>
      </p>
    </div>
  </AuthLayout>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from '@/composables/useToast'
import { authApi } from '@/api/auth'
import CodeInput from '@/components/ui/CodeInput.vue'
import AuthLayout from '@/components/auth/AuthLayout.vue'
import AuthField from '@/components/auth/AuthField.vue'
import AuthSubmit from '@/components/auth/AuthSubmit.vue'

const router = useRouter()
const route = useRoute()
const toast = useToast()

const queryEmail = (route.query.email as string) ?? ''
const email = ref(queryEmail)
const lockedEmail = ref(!!queryEmail)
const code = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const serverError = ref('')
const loading = ref(false)
const success = ref(false)

const touched = reactive({ email: false, password: false, confirm: false })
const submitted = ref(false)

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
const emailValid = computed(() => EMAIL_RE.test(email.value.trim()))
const emailError = computed(() => {
  if (lockedEmail.value || !submitted.value) return ''
  if (!email.value.trim()) return 'Informe seu e-mail'
  if (!emailValid.value) return 'E-mail inválido'
  return ''
})

const pwChecks = computed(() => ({
  length: password.value.length >= 8,
  lower: /[a-z]/.test(password.value),
  upper: /[A-Z]/.test(password.value),
  number: /\d/.test(password.value),
  symbol: /[^A-Za-z0-9]/.test(password.value),
}))
const passwordStrong = computed(() => Object.values(pwChecks.value).every(Boolean))
const passwordError = computed(() => {
  if (!submitted.value) return ''
  if (!password.value) return 'Crie uma senha'
  if (!pwChecks.value.length) return 'Use pelo menos 8 caracteres'
  if (!passwordStrong.value) return 'Inclua maiúsculas, minúsculas, número e símbolo'
  return ''
})
const confirmError = computed(() => {
  if (!submitted.value || !passwordConfirmation.value) return ''
  return password.value !== passwordConfirmation.value ? 'As senhas não coincidem' : ''
})

const strength = computed(() => {
  const score = Object.values(pwChecks.value).filter(Boolean).length
  if (score <= 2) return { bars: 1, label: 'Senha fraca', barClass: 'bg-red-500', textClass: 'text-red-400' }
  if (score === 3) return { bars: 2, label: 'Senha razoável', barClass: 'bg-amber-500', textClass: 'text-amber-400' }
  if (score === 4) return { bars: 3, label: 'Senha boa', barClass: 'bg-lime-500', textClass: 'text-lime-400' }
  return { bars: 4, label: 'Senha excelente', barClass: 'bg-emerald-500', textClass: 'text-emerald-400' }
})

async function submit() {
  submitted.value = true
  serverError.value = ''
  if (emailError.value || passwordError.value || confirmError.value) return
  if (code.value.length !== 6) { serverError.value = 'Informe o código de 6 dígitos.'; return }
  if (password.value !== passwordConfirmation.value) { serverError.value = 'As senhas não coincidem.'; return }

  loading.value = true
  try {
    await authApi.csrf()
    await authApi.resetPassword({
      email: email.value,
      code: code.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    })
    success.value = true
    toast.success('Senha redefinida! Faça login com a nova senha.')
    setTimeout(() => router.replace('/login'), 600)
  } catch (e: any) {
    serverError.value = e.response?.data?.errors?.code?.[0]
      ?? e.response?.data?.errors?.password?.[0]
      ?? e.response?.data?.message
      ?? 'Não foi possível redefinir a senha.'
    code.value = ''
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.alert-enter-active, .alert-leave-active { transition: opacity 0.25s, transform 0.25s; }
.alert-enter-from, .alert-leave-to { opacity: 0; transform: translateY(-4px); }
.collapse-enter-active, .collapse-leave-active { transition: opacity 0.25s, transform 0.25s; }
.collapse-enter-from, .collapse-leave-to { opacity: 0; transform: translateY(-4px); }
</style>
