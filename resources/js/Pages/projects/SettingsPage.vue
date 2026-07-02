<template>
  <div class="max-w-2xl mx-auto space-y-8 animate-fade-in">
    <div>
      <h2 class="text-xl font-bold text-dark-100">Configurações do Projeto</h2>
      <p class="text-sm text-dark-500 mt-0.5">{{ project?.name }}</p>
    </div>

    <!-- Project info -->
    <section class="card space-y-4">
      <h3 class="font-semibold text-dark-100">Informações gerais</h3>

      <div>
        <label class="label">Nome do projeto</label>
        <input v-model="form.name" class="input" placeholder="Nome do projeto" />
      </div>

      <div>
        <label class="label">Descrição</label>
        <textarea v-model="form.description" class="input resize-none" rows="3"
          placeholder="Descreva o objetivo do projeto..." />
      </div>

      <p v-if="saveError" class="text-sm text-red-400 bg-red-400/10 border border-red-400/20 rounded-lg px-3 py-2">
        {{ saveError }}
      </p>
      <p v-if="saveOk" class="text-sm text-emerald-400 bg-emerald-400/10 border border-emerald-400/20 rounded-lg px-3 py-2">
        Projeto atualizado com sucesso.
      </p>

      <div class="flex justify-end">
        <button @click="saveProject" :disabled="saving" class="btn-primary">
          <span v-if="saving" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
          <span v-else>Salvar alterações</span>
        </button>
      </div>
    </section>

    <!-- Danger zone -->
    <section class="card border-red-600/20 space-y-3">
      <h3 class="font-semibold text-red-400">Zona de perigo</h3>
      <p class="text-sm text-dark-500">
        Excluir o projeto remove permanentemente todas as tarefas, arquivos, reuniões e notas associadas. Esta ação não pode ser desfeita.
      </p>
      <div class="flex justify-center pt-1">
        <button @click="confirmDelete" class="btn-danger w-3/4 justify-center">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
          </svg>
          Excluir projeto
        </button>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useProjectsStore } from '@/stores/projects'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'

const route = useRoute()
const router = useRouter()
const projectId = Number(route.params.id)
const projectsStore = useProjectsStore()
const { currentProject: project } = storeToRefs(projectsStore)
const toast = useToast()

const saving    = ref(false)
const saveError = ref('')
const saveOk    = ref(false)

const form = ref({ name: '', description: '' })

async function saveProject() {
  if (!form.value.name.trim()) return
  saveError.value = ''
  saveOk.value = false
  saving.value = true
  try {
    await projectsStore.updateProject(projectId, form.value)
    saveOk.value = true
    setTimeout(() => { saveOk.value = false }, 3000)
  } catch (e: any) {
    saveError.value = e.response?.data?.message ?? 'Erro ao salvar.'
  } finally {
    saving.value = false
  }
}

async function confirmDelete() {
  const { confirm } = useConfirm()
  if (!await confirm({ title: 'Excluir projeto', message: `Excluir "${project.value?.name}"? Esta ação é irreversível.`, variant: 'danger' })) return
  try {
    await projectsStore.deleteProject(projectId)
    toast.success('Projeto excluído.')
    router.push('/projects')
  } catch {
    toast.error('Erro ao excluir o projeto.')
  }
}

onMounted(async () => {
  await projectsStore.fetchProject(projectId)
  form.value.name        = project.value?.name        ?? ''
  form.value.description = project.value?.description ?? ''
})
</script>
