<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-white">Projetos</h1>
        <p class="text-dark-400 text-sm">{{ filtered.length }} de {{ projects.length }} projeto{{ projects.length !== 1 ? 's' : '' }}</p>
      </div>
      <button v-if="projects.length" @click="showCreate = true" class="btn-primary">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Novo Projeto
      </button>
    </div>

    <!-- Filtros e busca -->
    <div class="space-y-3">
      <!-- Search (100% da largura) -->
      <div class="relative w-full">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-dark-500 pointer-events-none"
          fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input v-model="searchQuery" placeholder="Buscar projetos..."
          class="w-full text-sm bg-dark-800 border border-dark-700 rounded-xl pl-9 pr-3 py-2 text-dark-200 placeholder-dark-600 focus:outline-none focus:border-accent-500" />
      </div>

      <!-- Status + Ordenação (50% cada) -->
      <div class="grid grid-cols-2 gap-2">
        <select v-model="filterStatus"
          class="w-full text-sm bg-dark-800 border border-dark-700 rounded-xl px-3 py-2 text-dark-300 focus:outline-none focus:border-accent-500">
          <option value="">Todos os status</option>
          <option value="active">Ativo</option>
          <option value="planning">Planejamento</option>
          <option value="paused">Pausado</option>
          <option value="completed">Concluído</option>
          <option value="cancelled">Cancelado</option>
        </select>

        <select v-model="sortBy"
          class="w-full text-sm bg-dark-800 border border-dark-700 rounded-xl px-3 py-2 text-dark-300 focus:outline-none focus:border-accent-500">
          <option value="name">Nome (A-Z)</option>
          <option value="progress_desc">Progresso ↓</option>
          <option value="progress_asc">Progresso ↑</option>
          <option value="deadline">Prazo mais próximo</option>
          <option value="recent">Mais recentes</option>
        </select>
      </div>

      <div v-if="searchQuery || filterStatus || sortBy !== 'name'" class="flex justify-end">
        <button @click="searchQuery = ''; filterStatus = ''; sortBy = 'name'"
          class="text-xs text-dark-500 hover:text-dark-300 px-2 py-1.5 rounded-lg hover:bg-dark-700 transition-colors">
          Limpar
        </button>
      </div>
    </div>

    <!-- Skeleton -->
    <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 stagger-in">
      <Skeleton v-for="i in 6" :key="i" h="12rem" rounded="rounded-xl" />
    </div>

    <!-- Grid -->
    <div v-else-if="filtered.length" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 stagger-in">
      <ProjectCard v-for="p in filtered" :key="p.id" :project="p"
        @click="goToProject(p.id)"
        @delete="deleteProject(p.id)" />
    </div>

    <!-- Empty with search -->
    <EmptyState
      v-else-if="searchQuery || filterStatus"
      title="Nenhum projeto encontrado"
      description="Tente ajustar os filtros ou a busca."
    >
      <template #icon>
        <svg class="relative h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
      </template>
      <template #action>
        <button class="btn-secondary" @click="searchQuery = ''; filterStatus = ''">Limpar filtros</button>
      </template>
    </EmptyState>

    <!-- Empty no projects -->
    <EmptyState
      v-else
      title="Nenhum projeto ainda"
      description="Crie seu primeiro projeto ou entre em um via convite."
    >
      <template #action>
        <button class="btn-primary" @click="showCreate = true">
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
          Criar projeto
        </button>
      </template>
    </EmptyState>

    <ProjectModal v-if="showCreate" @close="showCreate = false" @created="onCreated" />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'
import { useProjectsStore } from '@/stores/projects'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'
import ProjectCard from '@/components/ui/ProjectCard.vue'
import ProjectModal from '@/components/ui/ProjectModal.vue'
import Skeleton from '@/components/ui/Skeleton.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import type { Project } from '@/types'

const store   = useProjectsStore()
const router  = useRouter()
const toast   = useToast()
const { projects } = storeToRefs(store)
const loading      = ref(true)
const showCreate   = ref(false)
const searchQuery  = ref('')
const filterStatus = ref('')
const sortBy       = ref('name')

function openCreate() { showCreate.value = true }

onMounted(async () => {
  // A Busca Global (Ctrl+K) e o atalho "N" disparam este evento.
  window.addEventListener('acadflow:new-project', openCreate)
  try { await store.fetchProjects() }
  finally { loading.value = false }
})

onUnmounted(() => window.removeEventListener('acadflow:new-project', openCreate))

const filtered = computed(() => {
  let list = [...projects.value] as Project[]

  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase()
    list = list.filter(p =>
      p.name.toLowerCase().includes(q) ||
      p.description?.toLowerCase().includes(q) ||
      p.category?.toLowerCase().includes(q)
    )
  }

  if (filterStatus.value)
    list = list.filter(p => p.status === filterStatus.value)

  list.sort((a, b) => {
    switch (sortBy.value) {
      case 'progress_desc': return (b.progress ?? 0) - (a.progress ?? 0)
      case 'progress_asc':  return (a.progress ?? 0) - (b.progress ?? 0)
      case 'deadline': {
        if (!a.deadline && !b.deadline) return 0
        if (!a.deadline) return 1
        if (!b.deadline) return -1
        return new Date(a.deadline).getTime() - new Date(b.deadline).getTime()
      }
      case 'recent':  return new Date(b.created_at).getTime() - new Date(a.created_at).getTime()
      default:        return a.name.localeCompare(b.name)
    }
  })
  return list
})

function goToProject(id: number) { router.push(`/projects/${id}`) }

async function deleteProject(id: number) {
  const { confirm } = useConfirm()
  if (!await confirm({ title: 'Excluir projeto', message: 'Tem certeza que deseja excluir este projeto?', variant: 'danger' })) return
  await store.deleteProject(id)
  toast.success('Projeto excluído.')
}

function onCreated() {
  showCreate.value = false
  toast.success('Projeto criado com sucesso!')
}
</script>
