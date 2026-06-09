<template>
  <div class="min-h-screen bg-dark-950 flex items-center justify-center p-4">
    <!-- Loading -->
    <div v-if="loading" class="text-center space-y-3">
      <div class="w-10 h-10 border-4 border-indigo-500/30 border-t-indigo-500 rounded-full animate-spin mx-auto" />
      <p class="text-dark-400 text-sm">Verificando convite...</p>
    </div>

    <!-- Invalid / expired -->
    <div v-else-if="error" class="bg-dark-800 border border-dark-700 rounded-2xl p-8 max-w-md w-full text-center space-y-4">
      <div class="w-14 h-14 rounded-full bg-red-500/10 flex items-center justify-center mx-auto">
        <svg class="w-7 h-7 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
      </div>
      <h2 class="text-xl font-bold text-white">Link inválido</h2>
      <p class="text-dark-400 text-sm">Este link de convite expirou ou é inválido.</p>
      <router-link
        to="/"
        class="inline-block px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition-colors"
      >Ir ao início</router-link>
    </div>

    <!-- Valid invite card -->
    <div v-else-if="invite" class="bg-dark-800 border border-dark-700 rounded-2xl p-8 max-w-md w-full space-y-6">
      <!-- Project info -->
      <div class="text-center space-y-2">
        <div class="w-16 h-16 rounded-2xl bg-indigo-600/20 flex items-center justify-center mx-auto mb-4">
          <svg class="w-8 h-8 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
          </svg>
        </div>
        <h2 class="text-2xl font-bold text-white">{{ invite.project.name }}</h2>
        <p v-if="invite.project.description" class="text-dark-400 text-sm leading-relaxed">
          {{ invite.project.description }}
        </p>
        <div class="flex items-center justify-center gap-4 pt-1">
          <span class="text-xs text-dark-500">
            {{ invite.project.members_count }} membro{{ invite.project.members_count !== 1 ? 's' : '' }}
          </span>
          <span class="text-xs px-2 py-0.5 rounded-full font-medium"
            :class="invite.role === 'leader' ? 'bg-yellow-500/15 text-yellow-400' : 'bg-indigo-500/15 text-indigo-400'">
            {{ invite.role === 'leader' ? 'Líder' : 'Membro' }}
          </span>
        </div>
      </div>

      <!-- Success state -->
      <div v-if="joined" class="text-center space-y-3">
        <div class="w-12 h-12 rounded-full bg-emerald-500/10 flex items-center justify-center mx-auto">
          <svg class="w-6 h-6 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <p class="text-emerald-400 font-medium text-sm">
          {{ alreadyMember ? 'Você já é membro deste projeto.' : 'Você entrou no projeto!' }}
        </p>
        <router-link
          :to="`/projects/${invite.project.id}`"
          class="inline-block px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition-colors"
        >Abrir projeto</router-link>
      </div>

      <!-- Not logged in -->
      <div v-else-if="!isLoggedIn" class="space-y-3">
        <p class="text-sm text-dark-400 text-center">Faça login para entrar no projeto.</p>
        <router-link
          :to="`/login?redirect=/invite/${token}`"
          class="block w-full py-2.5 text-center bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition-colors"
        >Fazer login</router-link>
      </div>

      <!-- Join button -->
      <div v-else class="space-y-3">
        <button
          @click="accept"
          :disabled="accepting"
          class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white text-sm font-semibold rounded-xl transition-colors"
        >{{ accepting ? 'Entrando...' : 'Entrar no projeto' }}</button>
        <p class="text-xs text-dark-600 text-center">
          Link expira em {{ expiresIn }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { inviteApi } from '@/api/projects'

const route  = useRoute()
const router = useRouter()
const auth   = useAuthStore()

const token    = computed(() => route.params.token as string)
const isLoggedIn = computed(() => !!auth.user)

const loading  = ref(true)
const error    = ref(false)
const accepting = ref(false)
const joined   = ref(false)
const alreadyMember = ref(false)
const invite   = ref<{
  project: { id: number; name: string; description: string | null; members_count: number }
  role: string
  expires_at: string
} | null>(null)

const expiresIn = computed(() => {
  if (!invite.value) return ''
  const diff = new Date(invite.value.expires_at).getTime() - Date.now()
  const days = Math.floor(diff / 86400000)
  const hours = Math.floor((diff % 86400000) / 3600000)
  if (days > 0) return `${days}d ${hours}h`
  return `${hours}h`
})

onMounted(async () => {
  try {
    const { data } = await inviteApi.info(token.value)
    invite.value = data
  } catch {
    error.value = true
  } finally {
    loading.value = false
  }
})

async function accept() {
  accepting.value = true
  try {
    const { data } = await inviteApi.accept(token.value)
    alreadyMember.value = !!data.already_member
    joined.value = true
  } catch {
    error.value = true
  } finally {
    accepting.value = false
  }
}
</script>
