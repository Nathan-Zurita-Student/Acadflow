<template>
  <div class="space-y-5 animate-fade-in">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold text-white">Projetos</h1>
        <p class="text-dark-400 text-sm">{{ filtered.length }} de {{ projects.length }} projeto{{ projects.length !== 1 ? 's' : '' }}</p>
      </div>
      <button @click="showCreate = true" class="btn-primary">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Novo Projeto
      </button>
    </div>

    <!-- Filtros e busca -->
    <div class="flex flex-wrap items-center gap-2">
      <!-- Search -->
      <div class="relative flex-1 min-w-[180px] max-w-xs">
        <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-dark-500 pointer-events-none"
          fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input v-model="searchQuery" placeholder="Buscar projetos..."
          class="w-full text-sm bg-dark-800 border border-dark-700 rounded-xl pl-8 pr-3 py-2 text-dark-200 placeholder-dark-600 focus:outline-none focus:border-accent-500" />
      </div>

      <!-- Status filter -->
      <select v-model="filterStatus"
        class="text-sm bg-dark-800 border border-dark-700 rounded-xl px-3 py-2 text-dark-300 focus:outline-none focus:border-accent-500">
        <option value="">Todos os status</option>
        <option value="active">Ativo</option>
        <option value="planning">Planejamento</option>
        <option value="paused">Pausado</option>
        <option value="completed">Concluído</option>
        <option value="cancelled">Cancelado</option>
      </select>

      <!-- Sort -->
      <select v-model="sortBy"
        class="text-sm bg-dark-800 border border-dark-700 rounded-xl px-3 py-2 text-dark-300 focus:outline-none focus:border-accent-500">
        <option value="name">Nome (A-Z)</option>
        <option value="progress_desc">Progresso ↓</option>
        <option value="progress_asc">Progresso ↑</option>
        <option value="deadline">Prazo mais próximo</option>
        <option value="recent">Mais recentes</option>
      </select>

      <button v-if="searchQuery || filterStatus || sortBy !== 'name'"
        @click="searchQuery = ''; filterStatus = ''; sortBy = 'name'"
        class="text-xs text-dark-500 hover:text-dark-300 px-2 py-1.5 rounded-lg hover:bg-dark-700 transition-colors">
        Limpar
      </button>
    </div>

    <!-- Skeleton -->
    <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
      <div v-for="i in 6" :key="i" class="card animate-pulse h-48" />
    </div>

    <!-- Grid -->
    <div v-else-if="filtered.length" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
      <ProjectCard v-for="p in filtered" :key="p.id" :project="p"
        @click="goToProject(p.id)"
        @delete="deleteProject(p.id)" />
    </div>

    <!-- Empty with search -->
    <div v-else-if="searchQuery || filterStatus" class="text-center py-16">
      <p class="text-dark-300 font-medium">Nenhum projeto encontrado</p>
      <p class="text-dark-500 text-sm mt-1">Tente ajustar os filtros ou a busca</p>
      <button @click="searchQuery = ''; filterStatus = ''"
        class="btn-secondary mt-4">Limpar filtros</button>
    </div>

    <!-- Empty no projects -->
    <div v-else class="text-center py-20">
      <div class="w-16 h-16 rounded-2xl bg-dark-800 flex items-center justify-center mx-auto mb-4">
        <svg class="w-8 h-8 text-dark-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z" />
        </svg>
      </div>
      <p class="text-dark-300 font-medium">Nenhum projeto</p>
      <p class="text-dark-500 text-sm mt-1">Crie seu primeiro projeto ou entre via convite</p>
      <button @click="showCreate = true" class="btn-primary mt-4">Criar projeto</button>
    </div>

    <ProjectModal v-if="showCreate" @close="showCreate = false" @created="onCreated" />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'
import { useProjectsStore } from '@/stores/projects'
import { useToast } from '@/composables/useToast'
import ProjectCard from '@/components/ui/ProjectCard.vue'
import ProjectModal from '@/components/ui/ProjectModal.vue'
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

onMounted(async () => {
  try { await store.fetchProjects() }
  finally { loading.value = false }
})

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
  if (!confirm('Tem certeza que deseja excluir este projeto?')) return
  await store.deleteProject(id)
  toast.success('Projeto excluído.')
}

function onCreated() {
  showCreate.value = false
  toast.success('Projeto criado com sucesso!')
}
</script>
