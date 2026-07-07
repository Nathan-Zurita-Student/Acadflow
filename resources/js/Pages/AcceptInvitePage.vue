<template>
  <div class="relative flex min-h-screen w-full items-center justify-center overflow-hidden bg-dark-950 px-4 text-dark-100">
    <AuthBackground />

    <div class="relative z-10 w-full max-w-md">
      <!-- Entrando automaticamente (logado) -->
      <div v-if="autoJoining" class="text-center">
        <div class="mx-auto mb-4 h-12 w-12">
          <span class="block h-full w-full rounded-full border-4 border-accent-500/25 border-t-accent-500 animate-spin" />
        </div>
        <p class="text-sm text-dark-400">Entrando no projeto…</p>
      </div>

      <!-- Link inválido / expirado -->
      <div v-else-if="error" class="glass border-gradient rounded-2xl p-8 text-center animate-scale-in">
        <div class="relative mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full border border-red-500/25 bg-red-500/10 text-red-400">
          <span class="absolute -inset-2 rounded-full bg-red-500/15 blur-xl" aria-hidden="true" />
          <svg class="relative h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><path d="M12 9v4M12 17h.01"/></svg>
        </div>
        <h2 class="text-xl font-semibold tracking-tight text-white">Link inválido</h2>
        <p class="mt-1.5 text-sm text-dark-400">Este link de convite expirou ou é inválido.</p>
        <RouterLink to="/" class="btn-cta mt-6">Ir ao início</RouterLink>
      </div>

      <!-- Não logado: prévia do convite -->
      <div v-else-if="invite && !isLoggedIn" class="glass border-gradient rounded-2xl p-8 animate-scale-in">
        <div class="text-center">
          <div class="relative mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl text-2xl font-semibold text-white" :style="{ background: projectColor }">
            <span class="absolute -inset-2 rounded-full opacity-40 blur-xl" :style="{ background: projectColor }" aria-hidden="true" />
            <span class="relative">{{ invite.project.name?.[0]?.toUpperCase() || '#' }}</span>
          </div>
          <p class="text-xs font-medium uppercase tracking-wide text-dark-500">Você foi convidado para</p>
          <h2 class="mt-1 text-2xl font-semibold tracking-tight text-white">{{ invite.project.name }}</h2>
          <p v-if="invite.project.description" class="mt-2 text-sm leading-relaxed text-dark-400">
            {{ invite.project.description }}
          </p>
          <div class="mt-3 flex items-center justify-center gap-2">
            <span class="text-xs text-dark-500">
              {{ invite.project.members_count }} membro{{ invite.project.members_count !== 1 ? 's' : '' }}
            </span>
            <span class="h-1 w-1 rounded-full bg-dark-600" />
            <span
              class="rounded-full px-2 py-0.5 text-xs font-medium"
              :class="invite.role === 'leader' ? 'bg-amber-500/15 text-amber-400' : 'bg-accent-500/15 text-accent-400'"
            >
              {{ invite.role === 'leader' ? 'Líder' : 'Membro' }}
            </span>
          </div>
        </div>

        <div class="mt-6 space-y-3">
          <p class="text-center text-sm text-dark-400">Faça login ou crie uma conta para entrar no projeto automaticamente.</p>
          <RouterLink :to="`/login?redirect=${encodeURIComponent(fullPath)}`" class="btn-cta">Fazer login</RouterLink>
          <RouterLink :to="`/register?redirect=${encodeURIComponent(fullPath)}`" class="btn-ghost">Criar conta</RouterLink>
        </div>
      </div>

      <!-- Sucesso: entrou -->
      <div v-else-if="joined" class="glass border-gradient rounded-2xl p-8 text-center animate-scale-in">
        <div class="relative mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full border border-emerald-500/25 bg-emerald-500/10 text-emerald-400">
          <span class="absolute -inset-2 rounded-full bg-emerald-500/20 blur-xl" aria-hidden="true" />
          <svg class="relative h-8 w-8 check-draw" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7" /></svg>
        </div>
        <h2 class="text-xl font-semibold tracking-tight text-white">
          {{ alreadyMember ? 'Você já faz parte!' : 'Bem-vindo ao projeto!' }}
        </h2>
        <p class="mt-1.5 text-sm text-dark-400">
          {{ alreadyMember ? 'Você já é membro deste projeto.' : 'Você foi adicionado como ' + (invite?.role === 'leader' ? 'líder' : 'membro') + '.' }}
        </p>
        <RouterLink :to="`/projects/${joinedProjectId}`" class="btn-cta mt-6">Abrir projeto</RouterLink>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { inviteApi } from '@/api/projects'
import AuthBackground from '@/components/auth/AuthBackground.vue'

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

const PROJECT_COLORS = ['#6366f1', '#8b5cf6', '#ec4899', '#f43f5e', '#f97316', '#10b981', '#06b6d4', '#3b82f6']
const projectColor = computed(() => {
  const name = invite.value?.project.name ?? ''
  let hash = 0
  for (let i = 0; i < name.length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash)
  return PROJECT_COLORS[Math.abs(hash) % PROJECT_COLORS.length]
})

onMounted(async () => {
  // Busca as informações do convite primeiro (funciona sem auth)
  try {
    const { data } = await inviteApi.info(token.value)
    invite.value = data
  } catch {
    error.value = true
    return
  }

  // Se já estiver logado, aceita imediatamente
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

<style scoped>
.btn-cta {
  display: flex;
  width: 100%;
  align-items: center;
  justify-content: center;
  border-radius: 0.75rem;
  padding: 0.65rem 1rem;
  font-size: 0.875rem;
  font-weight: 600;
  color: #fff;
  background: linear-gradient(180deg, rgb(var(--accent-500)), rgb(var(--accent-600)));
  box-shadow: 0 8px 24px -8px rgb(var(--accent-600) / 0.6), inset 0 1px 0 0 rgb(255 255 255 / 0.2);
  transition: transform 0.18s cubic-bezier(0.34, 1.56, 0.64, 1), filter 0.2s;
}
.btn-cta:hover { filter: brightness(1.07); }
.btn-cta:active { transform: scale(0.98); }

.btn-ghost {
  display: flex;
  width: 100%;
  align-items: center;
  justify-content: center;
  border-radius: 0.75rem;
  padding: 0.65rem 1rem;
  font-size: 0.875rem;
  font-weight: 600;
  color: rgb(var(--d-200));
  background: rgb(var(--d-800) / 0.6);
  backdrop-filter: blur(8px);
  border: 1px solid rgb(255 255 255 / 0.08);
  transition: transform 0.18s cubic-bezier(0.34, 1.56, 0.64, 1), background 0.2s, border-color 0.2s;
}
.btn-ghost:hover { background: rgb(var(--d-700) / 0.7); border-color: rgb(255 255 255 / 0.14); }
.btn-ghost:active { transform: scale(0.98); }

.check-draw path {
  stroke-dasharray: 26;
  stroke-dashoffset: 26;
  animation: draw 0.5s cubic-bezier(0.65, 0, 0.35, 1) 0.15s forwards;
}
@keyframes draw { to { stroke-dashoffset: 0; } }
</style>
