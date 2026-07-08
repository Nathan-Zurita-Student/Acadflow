<template>
  <AuthLayout :hide-mobile-logo="true" :compact="true">
    <div class="animate-scale-in">
      <!-- Cabeçalho -->
      <header class="mb-4">
        <h1 class="text-[1.6rem] font-semibold tracking-tight text-white">Crie sua conta</h1>
        <p class="mt-1 text-sm text-dark-400">Comece a organizar sua vida acadêmica</p>
      </header>

      <!-- SSO -->
      <GoogleButton />

      <div class="my-4 flex items-center gap-3">
        <div class="h-px flex-1 bg-gradient-to-r from-transparent to-dark-700" />
        <span class="text-xs text-dark-500">ou cadastre-se com e-mail</span>
        <div class="h-px flex-1 bg-gradient-to-l from-transparent to-dark-700" />
      </div>

      <form novalidate @submit.prevent="handleRegister">
        <!-- Avatar (opcional) -->
        <div class="mb-3 flex flex-col items-center">
          <button type="button" class="avatar-picker group" @click="fileInput?.click()">
            <span class="avatar-glow" aria-hidden="true" />
            <template v-if="avatarPreview">
              <img :src="avatarPreview" class="avatar-media" alt="Foto de perfil" />
              <span class="avatar-overlay"><span class="avatar-cta">Trocar foto</span></span>
            </template>
            <span v-else class="avatar-empty">
              <span class="avatar-cta">Selecionar foto de perfil</span>
            </span>
          </button>
          <input ref="fileInput" type="file" accept="image/*" class="hidden" @change="onAvatarChange" />
        </div>

        <AuthField
          ref="nameField"
          v-model="form.name"
          label="Nome completo"
          icon="user"
          autocomplete="name"
          placeholder="Seu nome"
          :error="nameError"
          :success="nameValid"
          @blur="touched.name = true"
        />

        <AuthField
          v-model="form.email"
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

        <div>
          <label class="mb-1.5 block text-[13px] font-medium text-dark-300">Senha</label>
          <AuthField
            v-model="form.password"
            icon="lock"
            type="password"
            autocomplete="new-password"
            placeholder="Crie uma senha forte"
            :error="passwordError"
            :success="passwordStrong"
            @blur="touched.password = true"
          />

          <!-- Medidor de força -->
          <transition name="collapse">
            <div v-if="form.password" class="-mt-1 mb-2">
              <div class="flex gap-1.5">
                <span
                  v-for="i in 4"
                  :key="i"
                  class="h-1 flex-1 rounded-full transition-all duration-300"
                  :class="i <= strength.bars ? strength.barClass : 'bg-dark-700'"
                />
              </div>
              <div class="mt-1.5 flex items-center justify-between">
                <span class="text-[11.5px] font-medium" :class="strength.textClass">{{ strength.label }}</span>
                <span class="text-[11px] text-dark-500">maiúsc. · minúsc. · número · símbolo</span>
              </div>
            </div>
          </transition>
        </div>

        <!-- Aceite obrigatório de termos -->
        <label class="mb-2 flex cursor-pointer items-start gap-2.5 select-none">
          <input v-model="form.terms" type="checkbox" class="sr-only" @change="touched.terms = true" />
          <span class="tc-box" :class="{ 'tc-checked': form.terms, 'tc-error': termsError }">
            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
          </span>
          <span class="text-[13px] leading-snug text-dark-400">
            Li e concordo com os
            <RouterLink to="/termos" target="_blank" class="font-medium text-accent-400 hover:text-accent-300">Termos de Uso</RouterLink>
            e a
            <RouterLink to="/privacidade" target="_blank" class="font-medium text-accent-400 hover:text-accent-300">Política de Privacidade</RouterLink>.
          </span>
        </label>
        <div class="min-h-[1rem] px-0.5">
          <transition name="msg">
            <p v-if="termsError" class="flex items-center gap-1.5 text-[12.5px] font-medium text-red-400">
              <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
              Você precisa aceitar os termos para continuar
            </p>
          </transition>
        </div>

        <!-- Erro do servidor -->
        <transition name="alert">
          <div v-if="serverError" :key="shakeKey" class="mb-4 mt-1 flex items-start gap-2.5 rounded-xl border border-red-500/25 bg-red-500/10 px-3.5 py-2.5 text-sm text-red-300" :class="{ 'animate-shake': shakeKey }">
            <svg class="mt-0.5 h-4 w-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
            <span>{{ serverError }}</span>
          </div>
        </transition>

        <AuthSubmit :loading="loading" :success="success" class="mt-1">
          Criar conta
          <template #loading>Criando conta…</template>
          <template #success>Conta criada!</template>
        </AuthSubmit>
      </form>
    </div>

    <template #footer>
      <p class="text-center text-sm text-dark-400">
        Já tem conta?
        <RouterLink :to="loginLink" class="ml-1 font-medium text-accent-400 transition-colors hover:text-accent-300">
          Entrar
        </RouterLink>
      </p>
    </template>
  </AuthLayout>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useOnboarding } from '@/composables/useOnboarding'
import AuthLayout from '@/components/auth/AuthLayout.vue'
import AuthField from '@/components/auth/AuthField.vue'
import AuthSubmit from '@/components/auth/AuthSubmit.vue'
import GoogleButton from '@/components/auth/GoogleButton.vue'

const auth = useAuthStore()
const { markPendingTour } = useOnboarding()
const router = useRouter()
const route = useRoute()

const form = reactive({ name: '', email: '', password: '', terms: false })
const touched = reactive({ name: false, email: false, password: false, terms: false })
const submitted = ref(false)
const serverError = ref('')
const loading = ref(false)
const success = ref(false)
const shakeKey = ref(0)

const avatarFile = ref<File | null>(null)
const avatarPreview = ref<string | null>(null)
const fileInput = ref<HTMLInputElement | null>(null)
const nameField = ref<InstanceType<typeof AuthField> | null>(null)

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

// --- Validações reativas ---
const nameValid = computed(() => form.name.trim().length >= 2)
const nameError = computed(() => {
  if (!submitted.value) return ''
  if (!form.name.trim()) return 'Informe seu nome'
  if (!nameValid.value) return 'Nome muito curto'
  return ''
})

const emailValid = computed(() => EMAIL_RE.test(form.email.trim()))
const emailError = computed(() => {
  if (!submitted.value) return ''
  if (!form.email.trim()) return 'Informe seu e-mail'
  if (!emailValid.value) return 'E-mail inválido'
  return ''
})

const pwChecks = computed(() => ({
  length: form.password.length >= 8,
  lower: /[a-z]/.test(form.password),
  upper: /[A-Z]/.test(form.password),
  number: /\d/.test(form.password),
  symbol: /[^A-Za-z0-9]/.test(form.password),
}))
const passwordStrong = computed(() => Object.values(pwChecks.value).every(Boolean))
const passwordError = computed(() => {
  if (!submitted.value) return ''
  if (!form.password) return 'Crie uma senha'
  if (!pwChecks.value.length) return 'Use pelo menos 8 caracteres'
  if (!passwordStrong.value) return 'Inclua maiúsculas, minúsculas, número e símbolo'
  return ''
})

const strength = computed(() => {
  const score = Object.values(pwChecks.value).filter(Boolean).length // 0..5
  if (score <= 2) return { bars: 1, label: 'Senha fraca', barClass: 'bg-red-500', textClass: 'text-red-400' }
  if (score === 3) return { bars: 2, label: 'Senha razoável', barClass: 'bg-amber-500', textClass: 'text-amber-400' }
  if (score === 4) return { bars: 3, label: 'Senha boa', barClass: 'bg-lime-500', textClass: 'text-lime-400' }
  return { bars: 4, label: 'Senha excelente', barClass: 'bg-emerald-500', textClass: 'text-emerald-400' }
})

const termsError = computed(() => submitted.value && !form.terms)

const loginLink = computed(() =>
  route.query.redirect ? `/login?redirect=${encodeURIComponent(String(route.query.redirect))}` : '/login',
)

const AVATAR_COLORS = ['#6366f1', '#8b5cf6', '#ec4899', '#f43f5e', '#f97316', '#eab308', '#10b981', '#06b6d4', '#3b82f6', '#14b8a6']
const previewColor = computed(() => {
  const name = form.name
  if (!name) return AVATAR_COLORS[0]
  let hash = 0
  for (let i = 0; i < name.length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash)
  return AVATAR_COLORS[Math.abs(hash) % AVATAR_COLORS.length]
})

onMounted(() => nameField.value?.focus())

function onAvatarChange(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0]
  if (!file) return
  avatarFile.value = file
  avatarPreview.value = URL.createObjectURL(file)
}

async function handleRegister() {
  submitted.value = true
  serverError.value = ''

  if (nameError.value || emailError.value || passwordError.value || termsError.value) {
    shakeKey.value++
    return
  }

  loading.value = true
  try {
    await auth.register({
      name: form.name,
      email: form.email,
      password: form.password,
      avatar: avatarFile.value,
      terms: form.terms,
    })
    success.value = true
    markPendingTour() // agenda o tour de boas-vindas para a 1ª entrada no app
    // Nova conta entra não verificada → confirmação por código.
    setTimeout(() => router.push('/verify-email'), 550)
  } catch (e: any) {
    const errs = e.response?.data?.errors as Record<string, string[]> | undefined
    serverError.value = errs
      ? (Object.values(errs)[0]?.[0] ?? 'Não foi possível criar a conta.')
      : (e.response?.data?.message ?? 'Não foi possível criar a conta.')
    shakeKey.value++
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
/* Avatar picker */
.avatar-picker {
  position: relative;
  width: 5rem;
  height: 5rem;
  border-radius: 9999px;
  cursor: pointer;
}
.avatar-glow {
  position: absolute;
  inset: -3px;
  border-radius: 9999px;
  background: conic-gradient(from 0deg, rgb(var(--accent-500)), rgb(139 92 246), rgb(34 211 238), rgb(var(--accent-500)));
  opacity: 0;
  filter: blur(6px);
  transition: opacity 0.3s;
}
.avatar-picker:hover .avatar-glow { opacity: 0.55; }

/* Foto escolhida */
.avatar-media {
  position: relative;
  width: 100%;
  height: 100%;
  border-radius: 9999px;
  object-fit: cover;
  border: 2px solid rgb(var(--d-700));
  transition: border-color 0.25s;
}
.avatar-picker:hover .avatar-media { border-color: rgb(var(--accent-500)); }

/* Estado vazio: campo "selecionar" com borda tracejada */
.avatar-empty {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
  border-radius: 9999px;
  border: 1.5px dashed rgb(var(--d-600));
  background: rgb(var(--d-800) / 0.5);
  transition: border-color 0.25s, background 0.25s;
}
.avatar-picker:hover .avatar-empty {
  border-color: rgb(var(--accent-500));
  background: rgb(var(--accent-500) / 0.08);
}

/* Texto dentro do círculo */
.avatar-cta {
  padding: 0 0.35rem;
  text-align: center;
  font-size: 9px;
  font-weight: 600;
  line-height: 1.15;
  color: rgb(var(--d-300));
}
.avatar-picker:hover .avatar-empty .avatar-cta { color: rgb(var(--accent-300)); }

/* Overlay para trocar quando já há foto */
.avatar-overlay {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 9999px;
  background: rgb(0 0 0 / 0.6);
  opacity: 0;
  transition: opacity 0.2s;
}
.avatar-overlay .avatar-cta { color: #fff; }
.avatar-picker:hover .avatar-overlay { opacity: 1; }

/* Checkbox de termos */
.tc-box {
  display: inline-flex;
  height: 1.15rem;
  width: 1.15rem;
  flex-shrink: 0;
  margin-top: 1px;
  align-items: center;
  justify-content: center;
  border-radius: 0.375rem;
  border: 1.5px solid rgb(var(--d-600));
  color: transparent;
  transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.tc-box svg { transform: scale(0.4); transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1); }
.tc-checked {
  background: rgb(var(--accent-500));
  border-color: rgb(var(--accent-500));
  color: #fff;
}
.tc-checked svg { transform: scale(1); }
.tc-error { border-color: rgb(239 68 68 / 0.8); }
.sr-only:focus-visible + .tc-box { box-shadow: 0 0 0 3px rgb(var(--accent-500) / 0.3); }

/* Transições */
.alert-enter-active, .alert-leave-active { transition: opacity 0.25s, transform 0.25s; }
.alert-enter-from, .alert-leave-to { opacity: 0; transform: translateY(-4px); }
.msg-enter-active, .msg-leave-active { transition: opacity 0.2s, transform 0.2s; }
.msg-enter-from, .msg-leave-to { opacity: 0; transform: translateY(-3px); }
.collapse-enter-active, .collapse-leave-active { transition: opacity 0.25s, transform 0.25s; }
.collapse-enter-from, .collapse-leave-to { opacity: 0; transform: translateY(-4px); }
</style>
