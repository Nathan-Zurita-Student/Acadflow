<template>
  <div class="space-y-6 animate-fade-in">

    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold text-white">Membros</h1>
        <p class="text-dark-400 text-sm">{{ stats.length }} membro{{ stats.length !== 1 ? 's' : '' }}</p>
      </div>
      <div v-if="canManage" class="flex items-center gap-2">
        <button v-if="currentProject?.is_owner" @click="showLinkModal = true"
          class="inline-flex items-center gap-2 px-4 py-2 bg-accent-600/15 hover:bg-accent-600/25 text-accent-400 text-sm font-medium rounded-lg border border-accent-600/25 transition-all duration-150">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
          </svg>
          Gerar Link
        </button>
        <button @click="showInvite = true" class="btn-primary">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
          </svg>
          Convidar
        </button>
      </div>
    </div>

    <!-- Skeleton -->
    <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div v-for="i in 4" :key="i" class="card animate-pulse h-36" />
    </div>

    <!-- Members grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div
        v-for="s in stats"
        :key="s.user.id"
        class="card hover:border-dark-600 transition-colors relative group"
      >
        <!-- Remove button -->
        <button
          v-if="canManage && !isProjectOwner(s.user.id)"
          @click="confirmRemove(s)"
          class="absolute top-3 right-3 opacity-0 group-hover:opacity-100 p-1.5 rounded-lg hover:bg-red-500/10 text-dark-600 hover:text-red-400 transition-all"
          title="Remover membro"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6zM21 12h-6" />
          </svg>
        </button>

        <div class="flex items-start gap-4">
          <div class="w-12 h-12 rounded-xl bg-accent-600/20 flex items-center justify-center text-lg font-bold text-accent-400 flex-shrink-0">
            {{ s.user.name[0] }}
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-start justify-between gap-2">
              <div class="min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                  <h3 class="font-semibold text-white">{{ s.user.name }}</h3>
                  <span
                    v-if="isProjectOwner(s.user.id)"
                    class="text-xs text-amber-400 bg-amber-400/10 border border-amber-400/20 px-1.5 py-0.5 rounded-md"
                  >
                    Dono
                  </span>
                </div>
                <p class="text-xs text-dark-500 truncate">{{ s.user.email }}</p>
              </div>
              <span :class="['w-9 h-9 rounded-xl flex items-center justify-center text-sm font-bold flex-shrink-0', gradeClass(s.grade)]">
                {{ s.grade }}
              </span>
            </div>

            <div class="mt-3 grid grid-cols-3 gap-3 text-center">
              <div>
                <p class="text-lg font-bold text-white">{{ s.total_tasks }}</p>
                <p class="text-xs text-dark-500">Total</p>
              </div>
              <div>
                <p class="text-lg font-bold text-emerald-400">{{ s.completed_tasks }}</p>
                <p class="text-xs text-dark-500">Feitas</p>
              </div>
              <div>
                <p class="text-lg font-bold" :class="s.overdue_tasks > 0 ? 'text-red-400' : 'text-dark-500'">
                  {{ s.overdue_tasks }}
                </p>
                <p class="text-xs text-dark-500">Atrasadas</p>
              </div>
            </div>

            <div class="mt-3">
              <div class="flex items-center justify-between mb-1">
                <span class="text-xs text-dark-500">Produtividade</span>
                <span class="text-xs font-medium text-dark-300">{{ s.score }}%</span>
              </div>
              <div class="h-1.5 bg-dark-700 rounded-full overflow-hidden">
                <div
                  class="h-full rounded-full transition-all duration-700"
                  :class="scoreBarColor(s.score)"
                  :style="{ width: s.score + '%' }"
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Summary chart -->
    <div v-if="stats.length" class="card">
      <h3 class="text-sm font-semibold text-white mb-4">Comparativo de Desempenho</h3>
      <apexchart type="bar" height="220" :options="chartOptions" :series="chartSeries" />
    </div>
  </div>

  <!-- Invite member modal -->
  <InviteMemberModal
    v-if="showInvite"
    :projectId="projectId"
    :existingMembers="stats"
    @close="showInvite = false"
    @invited="onMemberInvited"
  />

  <!-- Invite link modal -->
  <InviteLinkModal
    v-if="showLinkModal"
    :projectId="projectId"
    :canSetLeader="!!currentProject?.is_owner || isLeader"
    @close="showLinkModal = false"
  />

  <!-- Remove confirmation -->
  <Teleport to="body">
    <div
      v-if="removing"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-fade-in"
      @click.self="removing = null"
    >
      <div class="w-full max-w-sm bg-dark-800 border border-dark-700 rounded-2xl shadow-2xl p-6 animate-slide-up">
        <div class="w-10 h-10 rounded-xl bg-red-500/10 flex items-center justify-center mb-4">
          <svg class="w-5 h-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6zM21 12h-6" />
          </svg>
        </div>
        <h3 class="font-semibold text-white mb-1">Remover membro</h3>
        <p class="text-sm text-dark-400 mb-6">
          Tem certeza que deseja remover
          <span class="text-white font-medium">{{ removing.user.name }}</span>
          do projeto? Esta ação não pode ser desfeita.
        </p>
        <div class="flex gap-3">
          <button @click="removing = null" class="btn-secondary flex-1 justify-center">
            Cancelar
          </button>
          <button
            @click="removeMember"
            :disabled="removeLoading"
            class="flex-1 flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-red-600 hover:bg-red-500 disabled:opacity-50 text-white text-sm font-medium transition-colors"
          >
            <span v-if="removeLoading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
            <span v-else>Remover</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useRoute } from 'vue-router'
import { useProjectsStore } from '@/stores/projects'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'
import { projectsApi } from '@/api/projects'
import type { MemberStats } from '@/types'
import InviteMemberModal from '@/components/ui/InviteMemberModal.vue'
import InviteLinkModal from '@/components/ui/InviteLinkModal.vue'

const route = useRoute()
const projectId = Number(route.params.id)

const projectsStore = useProjectsStore()
const authStore = useAuthStore()
const toast = useToast()
const { currentProject } = storeToRefs(projectsStore)

function chartAccent(): string {
  const raw = getComputedStyle(document.documentElement).getPropertyValue('--accent-600').trim()
  return raw ? `rgb(${raw.split(/\s+/).join(',')})` : '#6366f1'
}

const loading = ref(true)
const stats = ref<MemberStats[]>([])
const showInvite = ref(false)
const showLinkModal = ref(false)
const removing = ref<MemberStats | null>(null)
const removeLoading = ref(false)

onMounted(async () => {
  try {
    if (!currentProject.value || currentProject.value.id !== projectId) {
      await projectsStore.fetchProject(projectId)
    }
    const { data } = await projectsApi.members(projectId)
    stats.value = data
  } finally {
    loading.value = false
  }
})

const isLeader = computed(() => {
  const userId = authStore.user?.id
  return !!currentProject.value?.members.some(m => m.id === userId && m.role === 'leader')
})

const canManage = computed(() => {
  if (!currentProject.value) return false
  if (currentProject.value.is_owner) return true
  return isLeader.value
})

function isProjectOwner(userId: number) {
  return currentProject.value?.owner?.id === userId
}

async function reloadStats() {
  const { data } = await projectsApi.members(projectId)
  stats.value = data
  await projectsStore.fetchProject(projectId)
}

async function onMemberInvited() {
  await reloadStats()
  toast.success('Membro convidado com sucesso.')
}

function confirmRemove(member: MemberStats) {
  removing.value = member
}

async function removeMember() {
  if (!removing.value) return
  removeLoading.value = true
  try {
    const name = removing.value.user.name
    await projectsApi.removeMember(projectId, removing.value.user.id)
    removing.value = null
    await reloadStats()
    toast.success(`${name} removido do projeto.`)
  } catch {
    toast.error('Erro ao remover membro.')
  } finally {
    removeLoading.value = false
  }
}

function gradeClass(grade: string) {
  return {
    A: 'bg-emerald-600/20 text-emerald-400',
    B: 'bg-blue-600/20 text-blue-400',
    C: 'bg-amber-600/20 text-amber-400',
    D: 'bg-red-600/20 text-red-400',
  }[grade] ?? ''
}

function scoreBarColor(score: number) {
  if (score >= 85) return 'bg-emerald-500'
  if (score >= 70) return 'bg-blue-500'
  if (score >= 50) return 'bg-amber-500'
  return 'bg-red-500'
}

const chartSeries = computed(() => [
  { name: 'Concluídas', data: stats.value.map(s => s.completed_tasks) },
  { name: 'Atrasadas', data: stats.value.map(s => s.overdue_tasks) },
])

const chartOptions = computed(() => ({
  chart: { background: 'transparent', toolbar: { show: false } },
  theme: { mode: 'dark' },
  colors: ['#10b981', '#ef4444'],
  xaxis: {
    categories: stats.value.map(s => s.user.name.split(' ')[0]),
    labels: { style: { colors: '#64748b', fontSize: '11px' } },
    axisBorder: { show: false },
    axisTicks: { show: false },
  },
  yaxis: { labels: { style: { colors: '#64748b', fontSize: '11px' } } },
  grid: { borderColor: '#1e293b' },
  plotOptions: { bar: { borderRadius: 4, columnWidth: '60%' } },
  dataLabels: { enabled: false },
  legend: { labels: { colors: '#94a3b8' } },
  tooltip: { theme: 'dark' },
}))
</script>
