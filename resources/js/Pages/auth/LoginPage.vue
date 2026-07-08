<template>
  <AuthLayout>
    <div class="glass border-gradient rounded-2xl p-6 sm:p-8 animate-scale-in">
      <!-- Cabeçalho -->
      <header class="mb-6">
        <h1 class="text-[1.6rem] font-semibold tracking-tight text-white">Bem-vindo de volta</h1>
        <p class="mt-1 text-sm text-dark-400">Entre para continuar no AcadFlow</p>
      </header>

      <!-- SSO -->
      <GoogleButton />

      <!-- Divisor -->
      <div class="my-5 flex items-center gap-3">
        <div class="h-px flex-1 bg-gradient-to-r from-transparent to-dark-700" />
        <span class="text-xs text-dark-500">ou entre com e-mail</span>
        <div class="h-px flex-1 bg-gradient-to-l from-transparent to-dark-700" />
      </div>

      <!-- Formulário -->
      <form novalidate @submit.prevent="handleLogin">
        <AuthField
          ref="emailField"
          v-model="form.email"
          label="E-mail"
          icon="mail"
          type="email"
          inputmode="email"
          autocomplete="email"
          placeholder="seu@email.com"
          :error="emailError"
          :success="emailSuccess"
          @blur="touched.email = true"
          @enter="focusPassword"
        />

        <div>
          <div class="mb-1.5 flex items-center justify-between">
            <label class="text-[13px] font-medium text-dark-300">Senha</label>
            <RouterLink to="/forgot-password" class="text-xs font-medium text-accent-400 transition-colors hover:text-accent-300">
              Esqueci minha senha
            </RouterLink>
          </div>
          <AuthField
            ref="passwordField"
            v-model="form.password"
            icon="lock"
            type="password"
            autocomplete="current-password"
            placeholder="••••••••"
            :error="passwordError"
            @blur="touched.password = true"
          />
        </div>

        <!-- Erro do servidor -->
        <transition name="alert">
          <div v-if="serverError" class="mb-4 flex items-start gap-2.5 rounded-xl border border-red-500/25 bg-red-500/10 px-3.5 py-2.5 text-sm text-red-300" :class="{ 'animate-shake': shakeKey }" :key="shakeKey">
            <svg class="mt-0.5 h-4 w-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
            <span>{{ serverError }}</span>
          </div>
        </transition>

        <AuthSubmit :loading="loading" :success="success">
          Entrar
          <template #loading>Entrando…</template>
          <template #success>Bem-vindo!</template>
        </AuthSubmit>
      </form>
    </div>

    <template #footer>
      <p class="text-center text-sm text-dark-400">
        Não tem conta?
        <RouterLink :to="registerLink" class="ml-1 font-medium text-accent-400 transition-colors hover:text-accent-300">
          Cadastre-se gratuitamente
        </RouterLink>
      </p>
    </template>
  </AuthLayout>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import AuthLayout from '@/components/auth/AuthLayout.vue'
import AuthField from '@/components/auth/AuthField.vue'
import AuthSubmit from '@/components/auth/AuthSubmit.vue'
import GoogleButton from '@/components/auth/GoogleButton.vue'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

const form = reactive({ email: '', password: '' })
const touched = reactive({ email: false, password: false })
const submitted = ref(false)
const serverError = ref('')
const loading = ref(false)
const success = ref(false)
const shakeKey = ref(0)

const emailField = ref<InstanceType<typeof AuthField> | null>(null)
const passwordField = ref<InstanceType<typeof AuthField> | null>(null)

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

const emailValid = computed(() => EMAIL_RE.test(form.email.trim()))
const emailError = computed(() => {
  if (!submitted.value) return ''
  if (!form.email.trim()) return 'Informe seu e-mail'
  if (!emailValid.value) return 'E-mail inválido'
  return ''
})
const emailSuccess = computed(() => emailValid.value)

const passwordError = computed(() => {
  if (!submitted.value) return ''
  if (!form.password) return 'Informe sua senha'
  return ''
})

const registerLink = computed(() =>
  route.query.redirect ? `/register?redirect=${encodeURIComponent(String(route.query.redirect))}` : '/register',
)

onMounted(() => {
  emailField.value?.focus()
  if (route.query.error === 'google') serverError.value = 'Não foi possível entrar com o Google. Tente novamente.'
})

function focusPassword() {
  passwordField.value?.focus()
}

async function handleLogin() {
  submitted.value = true
  serverError.value = ''
  if (emailError.value || passwordError.value || !form.email || !form.password) {
    if (emailError.value) emailField.value?.focus()
    else passwordField.value?.focus()
    return
  }

  loading.value = true
  try {
    await auth.login(form.email, form.password)
    success.value = true
    const redirect = route.query.redirect as string | undefined
    setTimeout(() => router.push(redirect || '/'), 550)
  } catch (e: any) {
    const errs = e.response?.data?.errors as Record<string, string[]> | undefined
    serverError.value =
      e.response?.data?.message ??
      (errs ? Object.values(errs)[0]?.[0] : undefined) ??
      'E-mail ou senha incorretos.'
    shakeKey.value++
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.alert-enter-active, .alert-leave-active { transition: opacity 0.25s, transform 0.25s; }
.alert-enter-from, .alert-leave-to { opacity: 0; transform: translateY(-4px); }
</style>
