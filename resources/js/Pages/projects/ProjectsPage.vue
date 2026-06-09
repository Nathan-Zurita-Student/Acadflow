<template>
  <div class="space-y-6 animate-fade-in">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold text-white">Projetos</h1>
        <p class="text-dark-400 text-sm">{{ projects.length }} projeto{{ projects.length !== 1 ? 's' : '' }}</p>
      </div>
      <button @click="showCreate = true" class="btn-primary">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Novo Projeto
      </button>
    </div>

    <!-- Skeleton -->
    <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
      <div v-for="i in 6" :key="i" class="card animate-pulse h-48" />
    </div>

    <!-- Grid -->
    <div v-else-if="projects.length" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
      <ProjectCard v-for="p in projects" :key="p.id" :project="p"
        @click="goToProject(p.id)"
        @delete="deleteProject(p.id)" />
    </div>

    <!-- Empty -->
    <div v-else class="text-center py-20">
      <div class="w-16 h-16 rounded-2xl bg-dark-800 flex items-center justify-center mx-auto mb-4">
        <svg class="w-8 h-8 text-dark-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z" />
        </svg>
      </div>
      <p class="text-dark-300 font-medium">Nenhum projeto</p>
      <p class="text-dark-500 text-sm mt-1">Crie seu primeiro projeto para começar</p>
      <button @click="showCreate = true" class="btn-primary mt-4">Criar projeto</button>
    </div>

    <!-- Create Modal -->
    <ProjectModal v-if="showCreate" @close="showCreate = false" @created="onCreated" />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'
import { useProjectsStore } from '@/stores/projects'
import ProjectCard from '@/components/ui/ProjectCard.vue'
import ProjectModal from '@/components/ui/ProjectModal.vue'

const store = useProjectsStore()
const { projects } = storeToRefs(store)
const router = useRouter()
const loading = ref(true)
const showCreate = ref(false)

onMounted(async () => {
  try { await store.fetchProjects() }
  finally { loading.value = false }
})

function goToProject(id: number) {
  router.push(`/projects/${id}`)
}

async function deleteProject(id: number) {
  if (!confirm('Tem certeza que deseja excluir este projeto?')) return
  await store.deleteProject(id)
}

function onCreated() {
  showCreate.value = false
}
</script>
