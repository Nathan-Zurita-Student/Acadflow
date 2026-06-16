<template>
  <div class="min-h-screen flex items-center justify-center bg-dark-950 px-4">
    <div class="w-full max-w-md animate-slide-up">
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-accent-600 mb-4 shadow-lg shadow-accent-600/30">
          <svg class="w-8 h-8" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M16 6L2 13l14 7 14-7-14-7z" fill="white" fill-opacity="0.95"/>
            <path d="M8 17v6c0 0 3.5 3 8 3s8-3 8-3v-6l-8 4-8-4z" fill="white" fill-opacity="0.75"/>
            <path d="M26 13v7" stroke="white" stroke-width="1.8" stroke-linecap="round"/>
            <circle cx="26" cy="21.5" r="1.5" fill="white" fill-opacity="0.8"/>
          </svg>
        </div>
        <h1 class="text-2xl font-bold text-white">AcadFlow</h1>
        <p class="text-dark-400 mt-1 text-sm">Crie sua conta</p>
      </div>

      <div class="card">
        <form @submit.prevent="handleRegister" class="space-y-4" novalidate>

          <!-- Avatar picker -->
          <div class="flex flex-col items-center gap-3 pb-2">
            <div
              class="relative w-20 h-20 rounded-full overflow-hidden cursor-pointer group border-2 border-dark-600 hover:border-accent-500 transition-colors"
              :style="!avatarPreview ? { background: previewColor } : undefined"
              @click="fileInput?.click()"
            >
              <img v-if="avatarPreview" :src="avatarPreview" class="w-full h-full object-cover" alt="Foto de perfil" />
              <span v-else class="w-full h-full flex items-center justify-center text-2xl font-bold text-white select-none">
                {{ form.name?.[0]?.toUpperCase() || '?' }}
              </span>
              <div class="absolute inset-0 bg-black/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
              </div>
            </div>
            <input ref="fileInput" type="file" accept="image/*" class="hidden" @change="onAvatarChange" />
            <p class="text-xs text-dark-400">Foto de perfil <span class="text-dark-500">(opcional)</span></p>
          </div>

          <div>
            <label class="label">Nome completo</label>
            <input v-model="form.name" type="text" class="input" placeholder="Seu nome" />
          </div>
          <div>
            <label class="label">Email</label>
            <input v-model="form.email" type="email" class="input" placeholder="seu@email.com" />
          </div>
          <div>
            <label class="label">Senha</label>
            <input v-model="form.password" type="password" class="input" placeholder="Mínimo 8 caracteres" />
          </div>

          <p v-if="error" class="text-sm text-red-400 bg-red-400/10 border border-red-400/20 rounded-lg px-3 py-2">
            {{ error }}
          </p>

          <button type="submit" :disabled="loading" class="btn-primary w-full justify-center">
            <span v-if="loading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
            <span v-else>Criar conta</span>
          </button>
        </form>

        <!-- Divisor -->
        <div class="flex items-center gap-3 my-4">
          <div class="flex-1 h-px bg-dark-700" />
          <span class="text-xs text-dark-500">ou</span>
          <div class="flex-1 h-px bg-dark-700" />
        </div>

        <!-- Cadastrar com Google -->
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
          Já tem conta?
          <RouterLink :to="route.query.redirect ? `/login?redirect=${encodeURIComponent(String(route.query.redirect))}` : '/login'" class="text-accent-400 hover:text-accent-300 font-medium ml-1">Entrar</RouterLink>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { validateFields } from '@/composables/useFormValidation'

const auth = useAuthStore()
const router = useRouter()
const route  = useRoute()

const form = ref({ name: '', email: '', password: '' })
const error = ref('')
const loading = ref(false)
const avatarFile = ref<File | null>(null)
const avatarPreview = ref<string | null>(null)
const fileInput = ref<HTMLInputElement | null>(null)

const AVATAR_COLORS = [
  '#6366f1', '#8b5cf6', '#ec4899', '#f43f5e',
  '#f97316', '#eab308', '#10b981', '#06b6d4',
  '#3b82f6', '#14b8a6',
]

const previewColor = computed(() => {
  const name = form.value.name
  if (!name) return AVATAR_COLORS[0]
  let hash = 0
  for (let i = 0; i < name.length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash)
  return AVATAR_COLORS[Math.abs(hash) % AVATAR_COLORS.length]
})

function onAvatarChange(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0]
  if (!file) return
  avatarFile.value = file
  avatarPreview.value = URL.createObjectURL(file)
}

async function handleRegister() {
  error.value = ''

  const msg = validateFields([
    { value: form.value.name,     label: 'Nome completo', rules: ['required'] },
    { value: form.value.email,    label: 'Email',         rules: ['required', 'email'] },
    { value: form.value.password, label: 'Senha',         rules: ['required', 'min:8'] },
  ])
  if (msg) { error.value = msg; return }

  loading.value = true
  try {
    await auth.register(form.value.name, form.value.email, form.value.password, avatarFile.value)
    const redirect = route.query.redirect as string | undefined
    router.push(redirect || '/')
  } catch (e: any) {
    const errs = e.response?.data?.errors as Record<string, string[]> | undefined
    error.value = errs ? (Object.values(errs)[0]?.[0] ?? 'Erro ao cadastrar.') : (e.response?.data?.message ?? 'Erro ao cadastrar.')
  } finally {
    loading.value = false
  }
}
</script>
