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
        <form @submit.prevent="handleRegister" class="space-y-4">

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
            <input v-model="form.name" type="text" class="input" placeholder="Seu nome" required />
          </div>
          <div>
            <label class="label">Email</label>
            <input v-model="form.email" type="email" class="input" placeholder="seu@email.com" required />
          </div>
          <div>
            <label class="label">Senha</label>
            <input v-model="form.password" type="password" class="input" placeholder="Mínimo 8 caracteres" required />
          </div>

          <p v-if="error" class="text-sm text-red-400 bg-red-400/10 border border-red-400/20 rounded-lg px-3 py-2">
            {{ error }}
          </p>

          <button type="submit" :disabled="loading" class="btn-primary w-full justify-center">
            <span v-if="loading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
            <span v-else>Criar conta</span>
          </button>
        </form>

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
