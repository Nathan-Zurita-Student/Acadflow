<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-fade-in"
      @click.self="$emit('close')">
      <div class="w-full max-w-lg bg-dark-800 border border-dark-700 rounded-2xl shadow-2xl animate-slide-up">
        <div class="flex items-center justify-between px-6 py-4 border-b border-dark-700">
          <h2 class="font-semibold text-white">{{ isEdit ? 'Editar Projeto' : 'Novo Projeto' }}</h2>
          <button @click="$emit('close')" class="p-1 rounded-lg hover:bg-dark-700 text-dark-400">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <form @submit.prevent="submit" class="p-6 space-y-4" novalidate>
          <div>
            <label class="label">Nome do projeto *</label>
            <input v-model="form.name" class="input" placeholder="Ex: Monografia de TCC" />
          </div>
          <div>
            <label class="label">Descrição</label>
            <textarea v-model="form.description" class="input resize-none" rows="3"
              placeholder="Descreva o projeto..." />
          </div>
          <div>
            <label class="label">Categoria</label>
            <input v-model="form.category" class="input" placeholder="Ex: TCC, IC, Extensão" />
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="label">Status</label>
              <select v-model="form.status" class="input">
                <option value="planning">Planejamento</option>
                <option value="active">Ativo</option>
                <option value="paused">Pausado</option>
                <option value="completed">Concluído</option>
              </select>
            </div>
            <div>
              <label class="label">Prazo final</label>
              <input v-model="form.deadline" type="date" class="input" />
            </div>
          </div>

          <p v-if="error" class="text-sm text-red-400 bg-red-400/10 border border-red-400/20 rounded-lg px-3 py-2">{{ error }}</p>

          <div class="flex gap-3 pt-2">
            <button type="button" @click="$emit('close')" class="btn-secondary flex-1 justify-center">Cancelar</button>
            <button type="submit" :disabled="saving" class="btn-primary flex-1 justify-center">
              <span v-if="saving" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
              <span v-else>{{ isEdit ? 'Salvar' : 'Criar' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useProjectsStore } from '@/stores/projects'
import { validateFields } from '@/composables/useFormValidation'
import type { Project } from '@/types'

const props = defineProps<{ project?: Project }>()
const emit = defineEmits(['close', 'created', 'updated'])

const store = useProjectsStore()
const isEdit = !!props.project
const saving = ref(false)
const error = ref('')

const form = ref({
  name: props.project?.name ?? '',
  description: props.project?.description ?? '',
  category: props.project?.category ?? '',
  status: props.project?.status ?? 'planning',
  deadline: props.project?.deadline ?? '',
})

async function submit() {
  error.value = ''

  const msg = validateFields([
    { value: form.value.name, label: 'Nome do projeto', rules: ['required'] },
  ])
  if (msg) { error.value = msg; return }

  saving.value = true
  try {
    if (isEdit && props.project) {
      await store.updateProject(props.project.id, form.value)
      emit('updated')
    } else {
      await store.createProject(form.value)
      emit('created')
    }
  } catch (e: any) {
    error.value = e.response?.data?.message ?? 'Erro ao salvar.'
  } finally {
    saving.value = false
  }
}
</script>
