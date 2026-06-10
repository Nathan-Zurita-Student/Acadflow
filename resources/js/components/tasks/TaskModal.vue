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
        <div v-if="isEdit" class="flex items-center gap-1 px-6 pt-3 flex-shrink-0">
          <button v-for="tab in tabs" :key="tab.id"
            @click="activeTab = tab.id"
            :class="['px-3 py-1.5 text-sm rounded-lg transition-colors flex items-center gap-1.5',
              activeTab === tab.id ? 'bg-dark-700 text-white font-medium' : 'text-dark-400 hover:text-dark-200']">
            {{ tab.label }}
            <span v-if="tab.id === 'files' && attachments.length"
              class="text-xs bg-indigo-600/30 text-indigo-400 px-1.5 py-0.5 rounded-full">
              {{ attachments.length }}
            </span>
            <span v-if="tab.id === 'comments' && (detail?.comments?.length ?? 0) > 0"
              class="text-xs bg-dark-600 text-dark-400 px-1.5 py-0.5 rounded-full">
              {{ detail?.comments?.length }}
            </span>
            <span v-if="tab.id === 'checklist' && completedChecklistRatio"
              class="text-xs bg-dark-600 text-dark-400 px-1.5 py-0.5 rounded-full">
              {{ completedChecklistRatio }}
            </span>
          </button>
          <div class="flex-1" />
          <button v-if="isLeader && props.task"
            @click="duplicateTask"
            :disabled="duplicating"
            class="ml-auto text-xs flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-dark-700 text-dark-400 hover:text-dark-200 hover:border-dark-600 transition-colors disabled:opacity-50"
            title="Duplicar tarefa">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
            Duplicar
          </button>
        </div>

        <!-- Content -->
        <div class="flex-1 overflow-y-auto">

          <!-- ── Form tab ─────────────────────────────────── -->
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

            <!-- ── Alocação de membros ─────────────────────── -->
            <div v-if="projectMembers.length">
              <label class="label">
                Membros alocados
                <span v-if="!isLeader" class="ml-1.5 text-xs text-dark-600 font-normal normal-case">(somente o líder pode alterar)</span>
              </label>

              <!-- Selected chips -->
              <div v-if="selectedIds.length" class="flex flex-wrap gap-2 mb-2">
                <div
                  v-for="m in selectedMembers" :key="m.id"
                  class="flex items-center gap-1.5 pl-1.5 pr-2 py-1 rounded-full text-xs font-medium bg-indigo-600/15 border border-indigo-500/25 text-indigo-300"
                >
                  <span class="w-5 h-5 rounded-full bg-indigo-600/40 flex items-center justify-center text-xs font-bold">
                    {{ m.name[0] }}
                  </span>
                  {{ m.name.split(' ')[0] }}
                  <button
                    v-if="isLeader"
                    @click="toggleMember(m.id)"
                    class="ml-0.5 hover:text-white transition-colors"
                  >
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
              </div>

              <!-- Picker (leader only) -->
              <div v-if="isLeader" class="relative" ref="pickerRef">
                <button
                  type="button"
                  @click="pickerOpen = !pickerOpen"
                  class="w-full flex items-center justify-between px-3 py-2 rounded-xl border border-dark-700 bg-dark-900 hover:border-dark-600 text-sm text-dark-400 hover:text-dark-200 transition-colors"
                >
                  <span class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                    {{ selectedIds.length ? 'Adicionar / remover membros' : 'Alocar membros para esta tarefa' }}
                  </span>
                  <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': pickerOpen }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </button>

                <!-- Dropdown -->
                <div
                  v-if="pickerOpen"
                  class="absolute z-10 top-full left-0 right-0 mt-1 bg-dark-800 border border-dark-700 rounded-xl shadow-2xl overflow-hidden"
                >
                  <!-- Search -->
                  <div class="p-2 border-b border-dark-700">
                    <input
                      v-model="memberSearch"
                      placeholder="Buscar membro..."
                      class="w-full text-sm bg-dark-900 border border-dark-700 rounded-lg px-3 py-2 text-dark-200 placeholder-dark-600 focus:outline-none focus:border-indigo-500"
                      @click.stop
                    />
                  </div>

                  <!-- Member list -->
                  <div class="max-h-52 overflow-y-auto">
                    <div
                      v-for="m in filteredMembers" :key="m.id"
                      @click="toggleMember(m.id)"
                      class="flex items-center gap-3 px-3 py-2.5 hover:bg-dark-700 cursor-pointer transition-colors"
                    >
                      <!-- Checkbox -->
                      <div
                        class="w-4 h-4 rounded border-2 flex items-center justify-center flex-shrink-0 transition-colors"
                        :class="isSelected(m.id)
                          ? 'bg-indigo-600 border-indigo-600'
                          : 'border-dark-600 bg-dark-800'"
                      >
                        <svg v-if="isSelected(m.id)" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                      </div>

                      <!-- Avatar -->
                      <div class="w-7 h-7 rounded-full bg-indigo-600/30 flex items-center justify-center text-xs font-bold text-indigo-300 flex-shrink-0">
                        {{ m.name[0] }}
                      </div>

                      <!-- Info -->
                      <div class="flex-1 min-w-0">
                        <p class="text-sm text-dark-200 font-medium truncate">{{ m.name }}</p>
                        <p class="text-xs text-dark-500 truncate">{{ m.email }}</p>
                      </div>

                      <!-- Role badge -->
                      <span
                        class="text-xs px-1.5 py-0.5 rounded-full flex-shrink-0"
                        :class="m.role === 'leader' ? 'bg-yellow-500/15 text-yellow-400' : 'bg-dark-700 text-dark-500'"
                      >{{ m.role === 'leader' ? 'Líder' : 'Membro' }}</span>
                    </div>

                    <p v-if="!filteredMembers.length" class="text-sm text-dark-600 text-center py-4">
                      Nenhum membro encontrado
                    </p>
                  </div>

                  <!-- Footer -->
                  <div class="p-2 border-t border-dark-700 flex items-center justify-between">
                    <span class="text-xs text-dark-500">{{ selectedIds.length }} selecionado{{ selectedIds.length !== 1 ? 's' : '' }}</span>
                    <button
                      @click="pickerOpen = false"
                      class="text-xs text-indigo-400 hover:text-indigo-300 font-medium px-2 py-1 rounded-lg hover:bg-indigo-500/10 transition-colors"
                    >Confirmar</button>
                  </div>
                </div>
              </div>

              <!-- Read-only for non-leaders -->
              <p v-else-if="!selectedIds.length" class="text-sm text-dark-600 italic">
                Nenhum membro alocado
              </p>
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

          <!-- ── Checklist tab ───────────────────────────── -->
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

          <!-- ── Comments tab ───────────────────────────── -->
          <div v-if="activeTab === 'comments'" class="p-6 space-y-4">
            <div ref="commentsContainer" class="space-y-3 max-h-64 overflow-y-auto">
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

          <!-- ── Files tab ───────────────────────────────── -->
          <div v-if="activeTab === 'files'" class="p-6 space-y-4">
            <div
              class="border-2 border-dashed rounded-xl p-6 text-center transition-colors cursor-pointer"
              :class="isDraggingFile ? 'border-indigo-500 bg-indigo-500/5' : 'border-dark-600 hover:border-dark-500 hover:bg-dark-700/30'"
              @dragover.prevent="isDraggingFile = true"
              @dragleave.self="isDraggingFile = false"
              @drop.prevent="onFileDrop"
              @click="fileInput?.click()"
            >
              <input ref="fileInput" type="file" class="hidden" multiple @change="onFileSelect" />
              <div v-if="!uploading">
                <svg class="w-8 h-8 text-dark-500 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
                <p class="text-sm text-dark-400">Arraste arquivos aqui ou <span class="text-indigo-400 font-medium">clique para selecionar</span></p>
                <p class="text-xs text-dark-600 mt-1">Imagens, PDFs, documentos — qualquer formato, até 50MB</p>
              </div>
              <div v-else class="flex flex-col items-center gap-2">
                <div class="w-6 h-6 border-2 border-indigo-500/30 border-t-indigo-500 rounded-full animate-spin" />
                <p class="text-sm text-dark-400">Enviando {{ uploadQueue.length > 1 ? uploadQueue.length + ' arquivos' : 'arquivo' }}...</p>
                <div class="w-48 h-1.5 bg-dark-700 rounded-full overflow-hidden">
                  <div class="h-full bg-indigo-500 rounded-full transition-all" :style="{ width: uploadProgress + '%' }" />
                </div>
              </div>
            </div>

            <div v-if="attachments.length" class="space-y-2">
              <div v-for="file in attachments" :key="file.id"
                class="flex items-center gap-3 p-3 rounded-xl bg-dark-700/40 border border-dark-700 hover:border-dark-600 group transition-colors">
                <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0" :class="fileIconBg(file.mime_type)">
                  <svg class="w-4 h-4" :class="fileIconColor(file.mime_type)" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="fileIconPath(file.mime_type)" />
                  </svg>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-dark-200 truncate">{{ file.name }}</p>
                  <p class="text-xs text-dark-500">{{ formatSize(file.size) }} · {{ timeAgo(file.created_at) }}</p>
                </div>
                <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                  <a :href="file.url" target="_blank" rel="noopener"
                    class="p-1.5 rounded-lg hover:bg-dark-600 text-dark-400 hover:text-white transition-colors" title="Abrir" @click.stop>
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                  </a>
                  <button @click="deleteAttachment(file.id)"
                    class="p-1.5 rounded-lg hover:bg-red-600/20 text-dark-400 hover:text-red-400 transition-colors" title="Remover">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>
            <p v-else-if="!uploading" class="text-sm text-dark-600 text-center py-2">Nenhum arquivo anexado ainda</p>
          </div>

        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { storeToRefs } from 'pinia'
import { useTasksStore } from '@/stores/tasks'
import { useProjectsStore } from '@/stores/projects'
import { useToast } from '@/composables/useToast'
import { tasksApi, attachmentsApi } from '@/api/projects'
import type { Task, TaskStatus, Attachment } from '@/types'

const props = defineProps<{
  projectId: number
  task?: Task
  defaultStatus?: TaskStatus
  isLeader?: boolean
}>()
const emit = defineEmits(['close', 'saved'])

const store         = useTasksStore()
const projectsStore = useProjectsStore()
const toast         = useToast()
const { currentProject } = storeToRefs(projectsStore)

const isEdit       = !!props.task
const saving       = ref(false)
const duplicating  = ref(false)
const error        = ref('')
const activeTab    = ref('form')
const detail       = ref<Task | null>(null)
const newComment   = ref('')
const newCheckItem = ref('')
const commentsContainer = ref<HTMLElement | null>(null)
let commentsPollInterval: ReturnType<typeof setInterval> | null = null

// ── member picker ─────────────────────────────────────
const pickerOpen   = ref(false)
const memberSearch = ref('')
const pickerRef    = ref<HTMLElement | null>(null)
const selectedIds  = ref<number[]>(
  props.task?.assignees?.map(a => a.id) ?? []
)

const projectMembers = computed(() => currentProject.value?.members ?? [])

const filteredMembers = computed(() => {
  const q = memberSearch.value.toLowerCase()
  if (!q) return projectMembers.value
  return projectMembers.value.filter(m =>
    m.name.toLowerCase().includes(q) || m.email.toLowerCase().includes(q)
  )
})

const selectedMembers = computed(() =>
  projectMembers.value.filter(m => selectedIds.value.includes(m.id))
)

function isSelected(id: number) { return selectedIds.value.includes(id) }

function toggleMember(id: number) {
  const idx = selectedIds.value.indexOf(id)
  if (idx === -1) selectedIds.value.push(id)
  else selectedIds.value.splice(idx, 1)
}

function onOutsideClick(e: MouseEvent) {
  if (pickerRef.value && !pickerRef.value.contains(e.target as Node)) {
    pickerOpen.value = false
  }
}
onMounted(() => document.addEventListener('mousedown', onOutsideClick))
onUnmounted(() => {
  document.removeEventListener('mousedown', onOutsideClick)
  stopCommentsPolling()
})

// ── files ─────────────────────────────────────────────
const fileInput    = ref<HTMLInputElement | null>(null)
const attachments  = ref<Attachment[]>([])
const isDraggingFile = ref(false)
const uploading    = ref(false)
const uploadProgress = ref(0)
const uploadQueue  = ref<File[]>([])

const tabs = [
  { id: 'form',      label: 'Detalhes' },
  { id: 'checklist', label: 'Checklist' },
  { id: 'comments',  label: 'Comentários' },
  { id: 'files',     label: 'Arquivos' },
]

const completedChecklistRatio = computed(() => {
  const items = detail.value?.checklists ?? []
  if (!items.length) return ''
  const done = items.filter((i: any) => i.completed).length
  return `${done}/${items.length}`
})

const form = ref({
  title:       props.task?.title       ?? '',
  description: props.task?.description ?? '',
  status:      props.task?.status      ?? props.defaultStatus ?? 'backlog',
  priority:    props.task?.priority    ?? 'medium',
  due_date:    props.task?.due_date    ?? '',
})

async function refreshComments() {
  if (!props.task) return
  const res = await tasksApi.get(props.projectId, props.task.id)
  if (detail.value) detail.value.comments = res.data.comments ?? []
}

function startCommentsPolling() {
  if (commentsPollInterval) return
  commentsPollInterval = setInterval(refreshComments, 5000)
}

function stopCommentsPolling() {
  if (commentsPollInterval) { clearInterval(commentsPollInterval); commentsPollInterval = null }
}

watch(activeTab, (tab) => {
  if (tab === 'comments') {
    startCommentsPolling()
    nextTick(() => scrollCommentsToBottom())
  } else {
    stopCommentsPolling()
  }
})

function scrollCommentsToBottom() {
  if (commentsContainer.value) {
    commentsContainer.value.scrollTop = commentsContainer.value.scrollHeight
  }
}

onMounted(async () => {
  if (isEdit && props.task) {
    const res = await tasksApi.get(props.projectId, props.task.id)
    detail.value = res.data
    attachments.value = res.data.attachments ?? []
  }
  if (!currentProject.value || currentProject.value.id !== props.projectId) {
    await projectsStore.fetchProject(props.projectId)
  }
})

async function submit() {
  if (!form.value.title.trim()) return
  error.value = ''
  saving.value = true
  try {
    const payload = {
      ...form.value,
      ...(props.isLeader ? { assignee_ids: selectedIds.value } : {}),
    }
    if (isEdit && props.task) {
      await store.updateTask(props.projectId, props.task.id, payload)
      toast.success('Tarefa atualizada.')
    } else {
      await store.createTask(props.projectId, payload)
      toast.success('Tarefa criada.')
    }
    emit('saved')
  } catch (e: any) {
    error.value = e.response?.data?.message ?? 'Erro ao salvar.'
  } finally {
    saving.value = false
  }
}

async function duplicateTask() {
  if (!props.task) return
  duplicating.value = true
  try {
    await store.createTask(props.projectId, {
      title: form.value.title + ' (cópia)',
      description: form.value.description,
      status: form.value.status,
      priority: form.value.priority,
      due_date: form.value.due_date,
      assignee_ids: selectedIds.value,
    })
    toast.success('Tarefa duplicada.')
    emit('saved')
  } catch {
    toast.error('Erro ao duplicar tarefa.')
  } finally {
    duplicating.value = false
  }
}

// ── checklist ─────────────────────────────────────────
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
  if (detail.value?.checklists)
    detail.value.checklists = detail.value.checklists.filter((c: { id: number }) => c.id !== id)
}

// ── comments ──────────────────────────────────────────
async function addComment() {
  if (!newComment.value.trim() || !props.task) return
  const res = await tasksApi.addComment(props.projectId, props.task.id, newComment.value)
  detail.value?.comments?.push(res.data)
  newComment.value = ''
  nextTick(() => scrollCommentsToBottom())
}

// ── file upload ───────────────────────────────────────
function onFileSelect(e: Event) {
  const files = Array.from((e.target as HTMLInputElement).files ?? [])
  if (files.length) uploadFiles(files)
}
function onFileDrop(e: DragEvent) {
  isDraggingFile.value = false
  const files = Array.from(e.dataTransfer?.files ?? [])
  if (files.length) uploadFiles(files)
}
async function uploadFiles(files: File[]) {
  if (!props.task) return
  uploadQueue.value = files
  uploading.value = true
  uploadProgress.value = 0
  for (let i = 0; i < files.length; i++) {
    try {
      const { data } = await attachmentsApi.upload(props.projectId, files[i], props.task.id)
      attachments.value.unshift(data)
    } catch {}
    uploadProgress.value = Math.round(((i + 1) / files.length) * 100)
  }
  uploading.value = false
  uploadQueue.value = []
  if (fileInput.value) fileInput.value.value = ''
}
async function deleteAttachment(id: number) {
  await attachmentsApi.delete(props.projectId, id)
  attachments.value = attachments.value.filter(a => a.id !== id)
}

// ── file helpers ──────────────────────────────────────
function fileIconPath(mime: string) {
  if (mime.startsWith('image/')) return 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'
  if (mime === 'application/pdf') return 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z'
  if (mime.includes('video')) return 'M15 10l4.553-2.069A1 1 0 0121 8.845v6.31a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z'
  return 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'
}
function fileIconBg(mime: string) {
  if (mime.startsWith('image/')) return 'bg-purple-500/15'
  if (mime === 'application/pdf') return 'bg-red-500/15'
  if (mime.includes('video')) return 'bg-blue-500/15'
  return 'bg-emerald-500/15'
}
function fileIconColor(mime: string) {
  if (mime.startsWith('image/')) return 'text-purple-400'
  if (mime === 'application/pdf') return 'text-red-400'
  if (mime.includes('video')) return 'text-blue-400'
  return 'text-emerald-400'
}
function formatSize(bytes: number) {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}
function timeAgo(date: string) {
  const diff = (Date.now() - new Date(date).getTime()) / 1000
  if (diff < 60) return 'agora'
  if (diff < 3600) return `${Math.floor(diff / 60)}m atrás`
  if (diff < 86400) return `${Math.floor(diff / 3600)}h atrás`
  return `${Math.floor(diff / 86400)}d atrás`
}
</script>
