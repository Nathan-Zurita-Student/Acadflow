<template>
  <Teleport to="body">
    <div
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-fade-in"
      @click.self="$emit('close')"
    >
      <div class="w-full max-w-md bg-dark-800 border border-dark-700 rounded-2xl shadow-2xl animate-slide-up">

        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-dark-700">
          <div>
            <h2 class="font-semibold text-white">Convidar Membro</h2>
            <p class="text-xs text-dark-500 mt-0.5">Busque pelo nome ou email do usuário</p>
          </div>
          <button @click="$emit('close')" class="p-1.5 rounded-lg hover:bg-dark-700 text-dark-400 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Body -->
        <div class="p-6 space-y-4">

          <!-- Role selector -->
          <div>
            <label class="label">Função no projeto</label>
            <div class="grid grid-cols-2 gap-2">
              <button
                v-for="opt in roleOptions"
                :key="opt.value"
                @click="selectedRole = opt.value"
                :class="[
                  'flex items-center gap-2 p-3 rounded-xl border text-sm font-medium transition-all',
                  selectedRole === opt.value
                    ? 'border-accent-500 bg-accent-500/10 text-accent-400'
                    : 'border-dark-700 text-dark-400 hover:border-dark-600 hover:text-dark-300'
                ]"
              >
                <span class="text-base">{{ opt.icon }}</span>
                <div class="text-left">
                  <p>{{ opt.label }}</p>
                  <p class="text-xs font-normal opacity-70">{{ opt.desc }}</p>
                </div>
              </button>
            </div>
          </div>

          <!-- Search input -->
          <div class="relative">
            <svg
              class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-dark-500 pointer-events-none"
              fill="none" viewBox="0 0 24 24" stroke="currentColor"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input
              v-model="query"
              class="input pl-9"
              placeholder="Digite nome ou email..."
              autocomplete="off"
            />
          </div>

          <!-- Results list -->
          <div class="space-y-2 min-h-[80px] max-h-64 overflow-y-auto">

            <div v-if="searching" class="flex items-center justify-center gap-2 py-8 text-dark-500 text-sm">
              <span class="w-4 h-4 border-2 border-dark-600 border-t-accent-500 rounded-full animate-spin" />
              Buscando...
            </div>

            <div v-else-if="query.length < 2" class="flex items-center justify-center py-8 text-dark-600 text-sm">
              Digite ao menos 2 caracteres para buscar
            </div>

            <div v-else-if="results.length === 0" class="flex flex-col items-center justify-center py-8 text-dark-500 text-sm gap-1">
              <svg class="w-8 h-8 text-dark-700 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              Nenhum usuário encontrado
            </div>

            <div
              v-else
              v-for="user in results"
              :key="user.id"
              class="flex items-center gap-3 p-3 rounded-xl border border-dark-700 hover:border-dark-600 hover:bg-dark-700/30 transition-colors"
            >
              <div class="w-9 h-9 rounded-full bg-accent-600/20 flex items-center justify-center text-accent-400 font-semibold text-sm flex-shrink-0">
                {{ user.name[0].toUpperCase() }}
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-white truncate">{{ user.name }}</p>
                <p class="text-xs text-dark-500 truncate">{{ user.email }}</p>
              </div>
              <button
                @click="invite(user)"
                :disabled="adding === user.id"
                class="btn-primary text-xs px-3 py-1.5 flex-shrink-0 min-w-[80px] flex items-center justify-center gap-1.5"
              >
                <span v-if="adding === user.id" class="w-3 h-3 border border-white/30 border-t-white rounded-full animate-spin" />
                <span v-else>Convidar</span>
              </button>
            </div>
          </div>

          <p v-if="error" class="text-sm text-red-400 bg-red-400/10 border border-red-400/20 rounded-lg px-3 py-2">{{ error }}</p>

          <p v-if="successCount > 0" class="text-sm text-emerald-400 bg-emerald-400/10 border border-emerald-400/20 rounded-lg px-3 py-2">
            {{ successCount }} membro{{ successCount > 1 ? 's' : '' }} convidado{{ successCount > 1 ? 's' : '' }} com sucesso.
          </p>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { dashboardApi, projectsApi } from '@/api/projects'
import type { MemberStats } from '@/types'

const props = defineProps<{
  projectId: number
  existingMembers: MemberStats[]
}>()

const emit = defineEmits<{
  close: []
  invited: []
}>()

type SearchUser = { id: number; name: string; email: string; avatar: string | null }

const roleOptions = [
  { value: 'member' as const, label: 'Membro', icon: '👤', desc: 'Acesso padrão' },
  { value: 'leader' as const, label: 'Líder', icon: '⭐', desc: 'Pode gerenciar' },
]

const query = ref('')
const results = ref<SearchUser[]>([])
const searching = ref(false)
const adding = ref<number | null>(null)
const error = ref('')
const successCount = ref(0)
const selectedRole = ref<'member' | 'leader'>('member')

let debounceTimer: ReturnType<typeof setTimeout>

watch(query, (val) => {
  clearTimeout(debounceTimer)
  results.value = []
  error.value = ''

  if (val.trim().length < 2) return

  searching.value = true
  debounceTimer = setTimeout(async () => {
    try {
      const { data } = await dashboardApi.users(val.trim())
      const existingIds = new Set(props.existingMembers.map(m => m.user.id))
      results.value = (data as SearchUser[]).filter(u => !existingIds.has(u.id))
    } catch {
      error.value = 'Erro ao buscar usuários.'
    } finally {
      searching.value = false
    }
  }, 300)
})

async function invite(user: SearchUser) {
  adding.value = user.id
  error.value = ''
  try {
    await projectsApi.addMember(props.projectId, user.id, selectedRole.value)
    results.value = results.value.filter(u => u.id !== user.id)
    successCount.value++
    emit('invited')
  } catch (e: any) {
    error.value = e.response?.data?.message ?? 'Erro ao convidar membro.'
  } finally {
    adding.value = null
  }
}
</script>
