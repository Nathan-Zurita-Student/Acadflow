<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm animate-fade-in"
      @click.self="$emit('close')">
      <div class="w-full max-w-2xl bg-dark-800 border border-dark-700 rounded-2xl shadow-2xl animate-slide-up max-h-[90vh] flex flex-col">

        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-dark-700 flex-shrink-0">
          <h2 class="font-semibold text-white">{{ isEdit ? 'Editar Tarefa' : 'Nova Tarefa' }}</h2>
          <button @click="$emit('close')" class="p-1 rounded-lg hover:bg-dark-700 text-dark-400">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Tabs -->
        <div v-if="isEdit" class="flex gap-1 px-6 pt-3 flex-shrink-0">
          <button v-for="tab in tabs" :key="tab.id"
            @click="activeTab = tab.id"
            :class="['px-3 py-1.5 text-sm rounded-lg transition-colors',
              activeTab === tab.id ? 'bg-dark-700 text-white font-medium' : 'text-dark-400 hover:text-dark-200']">
            {{ tab.label }}
          </button>
        </div>

        <!-- Content -->
        <div class="flex-1 overflow-y-auto">
          <!-- Form tab -->
          <div v-if="activeTab === 'form'" class="p-6 space-y-4">
            <div>
              <label class="label">Título *</label>
              <input v-model="form.title" class="input" placeholder="Título da tarefa" required />
            </div>
            <div>
              <label class="label">Descrição</label>
              <textarea v-model="form.description" class="input resize-none" rows="3" placeholder="Descreva a tarefa..." />
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="label">Status</label>
                <select v-model="form.status" class="input">
                  <option value="backlog">Backlog</option>
                  <option value="pending">Pendente</option>
                  <option value="in_progress">Em andamento</option>
                  <option value="review">Revisão</option>
                  <option value="done">Concluída</option>
                </select>
              </div>
              <div>
                <label class="label">Prioridade</label>
                <select v-model="form.priority" class="input">
                  <option value="low">Baixa</option>
                  <option value="medium">Média</option>
                  <option value="high">Alta</option>
                  <option value="urgent">Urgente</option>
                </select>
              </div>
            </div>
            <div>
              <label class="label">Prazo</label>
              <input v-model="form.due_date" type="date" class="input" />
            </div>

            <p v-if="error" class="text-sm text-red-400 bg-red-400/10 border border-red-400/20 rounded-lg px-3 py-2">{{ error }}</p>

            <div class="flex gap-3 pt-2">
              <button type="button" @click="$emit('close')" class="btn-secondary flex-1 justify-center">Cancelar</button>
              <button @click="submit" :disabled="saving" class="btn-primary flex-1 justify-center">
                <span v-if="saving" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                <span v-else>{{ isEdit ? 'Salvar' : 'Criar' }}</span>
              </button>
            </div>
          </div>

          <!-- Checklist tab -->
          <div v-if="activeTab === 'checklist'" class="p-6">
            <div class="flex gap-2 mb-4">
              <input v-model="newCheckItem" class="input flex-1" placeholder="Novo item..." @keyup.enter="addCheckItem" />
              <button @click="addCheckItem" class="btn-primary">Adicionar</button>
            </div>
            <div class="space-y-2">
              <div v-for="item in detail?.checklists ?? []" :key="item.id"
                class="flex items-center gap-3 p-2.5 rounded-lg hover:bg-dark-700/50 group">
                <input type="checkbox" :checked="item.completed"
                  @change="toggleChecklist(item.id, !item.completed)"
                  class="w-4 h-4 rounded border-dark-600 bg-dark-700 text-indigo-500 focus:ring-indigo-500" />
                <span :class="['flex-1 text-sm', item.completed ? 'line-through text-dark-500' : 'text-dark-200']">
                  {{ item.title }}
                </span>
                <button @click="deleteChecklist(item.id)"
                  class="opacity-0 group-hover:opacity-100 p-1 rounded hover:bg-red-600/20 text-dark-500 hover:text-red-400">
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
              <p v-if="!detail?.checklists?.length" class="text-sm text-dark-500 text-center py-4">
                Nenhum item na checklist
              </p>
            </div>
          </div>

          <!-- Comments tab -->
          <div v-if="activeTab === 'comments'" class="p-6 space-y-4">
            <div class="space-y-3 max-h-64 overflow-y-auto">
              <div v-for="c in detail?.comments ?? []" :key="c.id" class="flex gap-2.5">
                <div class="w-7 h-7 rounded-full bg-indigo-600/30 flex items-center justify-center text-xs font-semibold text-indigo-300 flex-shrink-0">
                  {{ c.user.name[0] }}
                </div>
                <div class="flex-1 bg-dark-700/50 rounded-lg p-3">
                  <div class="flex items-center gap-2 mb-1">
                    <span class="text-xs font-semibold text-dark-200">{{ c.user.name }}</span>
                    <span class="text-xs text-dark-600">{{ timeAgo(c.created_at) }}</span>
                  </div>
                  <p class="text-sm text-dark-300">{{ c.content }}</p>
                </div>
              </div>
              <p v-if="!detail?.comments?.length" class="text-sm text-dark-500 text-center py-4">Sem comentários</p>
            </div>
            <div class="flex gap-2 border-t border-dark-700 pt-4">
              <input v-model="newComment" class="input flex-1" placeholder="Escreva um comentário..." @keyup.enter="addComment" />
              <button @click="addComment" :disabled="!newComment.trim()" class="btn-primary">Enviar</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useTasksStore } from '@/stores/tasks'
import { tasksApi } from '@/api/projects'
import type { Task, TaskStatus } from '@/types'

const props = defineProps<{
  projectId: number
  task?: Task
  defaultStatus?: TaskStatus
}>()
const emit = defineEmits(['close', 'saved'])

const store = useTasksStore()
const isEdit = !!props.task
const saving = ref(false)
const error = ref('')
const activeTab = ref('form')
const detail = ref<Task | null>(null)
const newComment = ref('')
const newCheckItem = ref('')

const tabs = [
  { id: 'form', label: 'Detalhes' },
  { id: 'checklist', label: 'Checklist' },
  { id: 'comments', label: 'Comentários' },
]

const form = ref({
  title: props.task?.title ?? '',
  description: props.task?.description ?? '',
  status: props.task?.status ?? props.defaultStatus ?? 'backlog',
  priority: props.task?.priority ?? 'medium',
  due_date: props.task?.due_date ?? '',
})

onMounted(async () => {
  if (isEdit && props.task) {
    detail.value = (await tasksApi.get(props.projectId, props.task.id)).data
  }
})

async function submit() {
  if (!form.value.title.trim()) return
  error.value = ''
  saving.value = true
  try {
    if (isEdit && props.task) {
      await store.updateTask(props.projectId, props.task.id, form.value)
    } else {
      await store.createTask(props.projectId, form.value)
    }
    emit('saved')
  } catch (e: any) {
    error.value = e.response?.data?.message ?? 'Erro ao salvar.'
  } finally {
    saving.value = false
  }
}

async function addComment() {
  if (!newComment.value.trim() || !props.task) return
  const res = await tasksApi.addComment(props.projectId, props.task.id, newComment.value)
  detail.value?.comments?.unshift(res.data)
  newComment.value = ''
}

async function addCheckItem() {
  if (!newCheckItem.value.trim() || !props.task) return
  const res = await tasksApi.addChecklist(props.projectId, props.task.id, newCheckItem.value)
  detail.value?.checklists?.push(res.data)
  newCheckItem.value = ''
}

async function toggleChecklist(id: number, completed: boolean) {
  if (!props.task) return
  await tasksApi.updateChecklist(props.projectId, props.task.id, id, completed)
  const item = detail.value?.checklists?.find((c: { id: number }) => c.id === id)
  if (item) item.completed = completed
}

async function deleteChecklist(id: number) {
  if (!props.task) return
  await tasksApi.deleteChecklist(props.projectId, props.task.id, id)
  if (detail.value?.checklists) {
    detail.value.checklists = detail.value.checklists.filter((c: { id: number }) => c.id !== id)
  }
}

function timeAgo(date: string) {
  const diff = (Date.now() - new Date(date).getTime()) / 1000
  if (diff < 60) return 'agora'
  if (diff < 3600) return `${Math.floor(diff / 60)}m atrás`
  if (diff < 86400) return `${Math.floor(diff / 3600)}h atrás`
  return `${Math.floor(diff / 86400)}d atrás`
}
</script>
