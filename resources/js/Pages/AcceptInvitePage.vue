<template>
  <div class="min-h-screen bg-dark-950 flex items-center justify-center p-4">

    <!-- Joining automatically (logged in) -->
    <div v-if="autoJoining" class="text-center space-y-3">
      <div class="w-10 h-10 border-4 border-indigo-500/30 border-t-indigo-500 rounded-full animate-spin mx-auto" />
      <p class="text-dark-400 text-sm">Entrando no projeto...</p>
    </div>

    <!-- Invalid / expired -->
    <div v-else-if="error" class="bg-dark-800 border border-dark-700 rounded-2xl p-8 max-w-md w-full text-center space-y-4">
      <div class="w-14 h-14 rounded-full bg-red-500/10 flex items-center justify-center mx-auto">
        <svg class="w-7 h-7 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
      </div>
      <h2 class="text-xl font-bold text-white">Link inválido</h2>
      <p class="text-dark-400 text-sm">Este link de convite expirou ou é inválido.</p>
      <router-link to="/" class="inline-block px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition-colors">
        Ir ao início
      </router-link>
    </div>

    <!-- Not logged in: show invite preview + login/register buttons -->
    <div v-else-if="invite && !isLoggedIn" class="bg-dark-800 border border-dark-700 rounded-2xl p-8 max-w-md w-full space-y-6">
      <div class="text-center space-y-2">
        <div class="w-16 h-16 rounded-2xl bg-indigo-600/20 flex items-center justify-center mx-auto mb-4">
          <svg class="w-8 h-8 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
        </div>
        <p class="text-sm text-dark-500 uppercase tracking-wide font-medium">Você foi convidado para</p>
        <h2 class="text-2xl font-bold text-white">{{ invite.project.name }}</h2>
        <p v-if="invite.project.description" class="text-dark-400 text-sm leading-relaxed">
          {{ invite.project.description }}
        </p>
        <div class="flex items-center justify-center gap-3 pt-1">
          <span class="text-xs text-dark-500">
            {{ invite.project.members_count }} membro{{ invite.project.members_count !== 1 ? 's' : '' }}
          </span>
          <span
            class="text-xs px-2 py-0.5 rounded-full font-medium"
            :class="invite.role === 'leader' ? 'bg-yellow-500/15 text-yellow-400' : 'bg-indigo-500/15 text-indigo-400'"
          >
            {{ invite.role === 'leader' ? 'Líder' : 'Membro' }}
          </span>
        </div>
      </div>

      <div class="space-y-3">
        <p class="text-sm text-dark-400 text-center">Faça login ou crie uma conta para entrar no projeto automaticamente.</p>
        <router-link
          :to="`/login?redirect=${encodeURIComponent(fullPath)}`"
          class="block w-full py-2.5 text-center bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition-colors"
        >
          Fazer login
        </router-link>
        <router-link
          :to="`/register?redirect=${encodeURIComponent(fullPath)}`"
          class="block w-full py-2.5 text-center bg-dark-700 hover:bg-dark-600 text-dark-200 text-sm font-semibold rounded-xl transition-colors"
        >
          Criar conta
        </router-link>
      </div>
    </div>

    <!-- Success: joined -->
    <div v-else-if="joined" class="bg-dark-800 border border-dark-700 rounded-2xl p-8 max-w-md w-full text-center space-y-4">
      <div class="w-14 h-14 rounded-full bg-emerald-500/10 flex items-center justify-center mx-auto">
        <svg class="w-7 h-7 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
      </div>
      <h2 class="text-xl font-bold text-white">
        {{ alreadyMember ? 'Você já faz parte!' : 'Bem-vindo ao projeto!' }}
      </h2>
      <p class="text-dark-400 text-sm">
        {{ alreadyMember ? 'Você já é membro deste projeto.' : 'Você foi adicionado como ' + (invite?.role === 'leader' ? 'líder' : 'membro') + '.' }}
      </p>
      <router-link
        :to="`/projects/${joinedProjectId}`"
        class="inline-block px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition-colors"
      >
        Abrir projeto
      </router-link>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { inviteApi } from '@/api/projects'

const route = useRoute()
const auth  = useAuthStore()

const token    = computed(() => route.params.token as string)
const fullPath = computed(() => route.fullPath)
const isLoggedIn = computed(() => !!auth.user)

const autoJoining = ref(false)
const error       = ref(false)
const joined      = ref(false)
const alreadyMember  = ref(false)
const joinedProjectId = ref<number | null>(null)
const invite = ref<{
  project: { id: number; name: string; description: string | null; members_count: number }
  role: string
  expires_at: string
} | null>(null)

onMounted(async () => {
  // Fetch invite info first (works without auth)
  try {
    const { data } = await inviteApi.info(token.value)
    invite.value = data
  } catch {
    error.value = true
    return
  }

  // If already logged in, accept immediately
  if (isLoggedIn.value) {
    await acceptInvite()
  }
})

async function acceptInvite() {
  autoJoining.value = true
  try {
    const { data } = await inviteApi.accept(token.value)
    alreadyMember.value    = !!data.already_member
    joinedProjectId.value  = data.project_id
    joined.value = true
  } catch {
    error.value = true
  } finally {
    autoJoining.value = false
  }
}
</script>
