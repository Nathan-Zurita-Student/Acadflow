<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm animate-fade-in"
      @click.self="$emit('close')">
      <div class="w-full max-w-2xl bg-dark-800 border border-dark-700 rounded-2xl shadow-2xl animate-slide-up max-h-[90vh] flex flex-col">

        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-dark-700 flex-shrink-0">
          <h2 class="font-semibold text-white flex items-center gap-2">
            Gerar plano com IA
          </h2>
          <button @click="$emit('close')" class="p-1 rounded-lg hover:bg-dark-700 text-dark-400">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- ── Etapa 1: entrada ───────────────────────────── -->
        <div v-if="step === 'input'" class="p-6 space-y-4 overflow-y-auto">
          <p class="text-sm text-dark-400">
            Cole o enunciado do trabalho (ou anexe o PDF) e a IA monta as tarefas do Kanban. Você revisa tudo antes de criar.
          </p>

          <div>
            <label class="label">Enunciado do trabalho</label>
            <textarea v-model="content" rows="7" class="input resize-none"
              placeholder="Cole aqui o enunciado, requisitos, critérios de avaliação..." />
          </div>

          <div>
            <label class="label">Ou anexe um PDF <span class="text-dark-600 font-normal">(opcional)</span></label>
            <input ref="fileInput" type="file" accept="application/pdf" class="hidden" @change="onFile" />
            <div class="flex items-center gap-2">
              <button type="button" @click="fileInput?.click()" class="btn-secondary text-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                </svg>
                Escolher PDF
              </button>
              <span v-if="file" class="text-xs text-dark-300 truncate flex-1">{{ file.name }}</span>
              <button v-if="file" @click="file = null" class="text-dark-500 hover:text-red-400 text-xs">remover</button>
            </div>
          </div>

          <div>
            <label class="label">Data de entrega <span class="text-dark-600 font-normal">(opcional)</span></label>
            <input v-model="dueDate" type="date" class="input" />
          </div>

          <p v-if="error" class="text-sm text-red-400 bg-red-400/10 border border-red-400/20 rounded-lg px-3 py-2">{{ error }}</p>

          <div class="flex gap-3 pt-1">
            <button @click="$emit('close')" class="btn-secondary flex-1 justify-center">Cancelar</button>
            <button @click="generate" :disabled="generating || (!content.trim() && !file)" class="btn-primary flex-1 justify-center disabled:opacity-50">
              <span v-if="generating" class="flex items-center gap-2">
                <span class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                Gerando...
              </span>
              <span v-else>Gerar plano</span>
            </button>
          </div>
        </div>

        <!-- ── Etapa 2: preview editável ──────────────────── -->
        <template v-else>
          <div class="px-6 py-3 border-b border-dark-700 flex-shrink-0">
            <p class="text-sm text-dark-300">
              <span class="font-semibold text-white">{{ tasks.length }}</span> tarefa{{ tasks.length !== 1 ? 's' : '' }} gerada{{ tasks.length !== 1 ? 's' : '' }} — revise e edite antes de criar.
            </p>
          </div>

          <div class="flex-1 overflow-y-auto px-6 py-4 space-y-3">
            <div v-for="(t, idx) in tasks" :key="idx" class="rounded-xl border border-dark-700 bg-dark-900/40 p-3 space-y-2.5">
              <div class="flex items-start gap-2">
                <input v-model="t.title" class="input flex-1 font-medium" placeholder="Título da tarefa" />
                <button @click="tasks.splice(idx, 1)" class="p-2 rounded-lg hover:bg-red-600/20 text-dark-500 hover:text-red-400 flex-shrink-0" title="Remover">
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>

              <textarea v-model="t.description" rows="2" class="input resize-none text-xs" placeholder="Descrição (opcional)" />

              <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                <select v-model="t.status" class="input text-xs">
                  <option value="backlog">Backlog</option>
                  <option value="pending">Pendente</option>
                  <option value="in_progress">Em andamento</option>
                  <option value="review">Revisão</option>
                  <option value="done">Concluída</option>
                </select>
                <select v-model="t.priority" class="input text-xs">
                  <option value="low">Baixa</option>
                  <option value="medium">Média</option>
                  <option value="high">Alta</option>
                  <option value="urgent">Urgente</option>
                </select>
                <input v-model="t.due_date" type="date" class="input text-xs" />
                <select v-model="t.assignee_id" class="input text-xs">
                  <option :value="null">Sem responsável</option>
                  <option v-for="m in members" :key="m.id" :value="m.id">{{ m.name.split(' ')[0] }}</option>
                </select>
              </div>

              <div>
                <label class="text-[11px] text-dark-500">Subtarefas (uma por linha)</label>
                <textarea v-model="t.subtasksText" rows="2" class="input resize-none text-xs mt-1" placeholder="Ex: Pesquisar referências&#10;Escrever introdução" />
              </div>
            </div>

            <p v-if="!tasks.length" class="text-sm text-dark-500 text-center py-6">Todas as tarefas foram removidas.</p>
          </div>

          <div class="px-6 py-4 border-t border-dark-700 flex-shrink-0">
            <p v-if="error" class="text-sm text-red-400 mb-2">{{ error }}</p>
            <div class="flex gap-3">
              <button @click="step = 'input'" class="btn-secondary flex-1 justify-center">Voltar</button>
              <button @click="apply" :disabled="applying || !tasks.length" class="btn-primary flex-1 justify-center disabled:opacity-50">
                <span v-if="applying" class="flex items-center gap-2">
                  <span class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                  Criando...
                </span>
                <span v-else>Criar {{ tasks.length }} tarefa{{ tasks.length !== 1 ? 's' : '' }}</span>
              </button>
            </div>
          </div>
        </template>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { storeToRefs } from 'pinia'
import { useProjectsStore } from '@/stores/projects'
import { useToast } from '@/composables/useToast'
import { aiApi, type AiPlanTask } from '@/api/projects'

const props = defineProps<{ projectId: number }>()
const emit = defineEmits(['close', 'created'])

const projectsStore = useProjectsStore()
const { currentProject } = storeToRefs(projectsStore)
const toast = useToast()

const members = computed(() => currentProject.value?.members ?? [])

interface EditableTask {
  title: string
  description: string
  status: string
  priority: string
  due_date: string | null
  assignee_id: number | null
  subtasksText: string
}

const step       = ref<'input' | 'preview'>('input')
const content    = ref('')
const file       = ref<File | null>(null)
const dueDate    = ref<string>(currentProject.value?.deadline ?? '')
const generating = ref(false)
const applying   = ref(false)
const error      = ref('')
const tasks      = ref<EditableTask[]>([])
const fileInput  = ref<HTMLInputElement | null>(null)

function onFile(e: Event) {
  const f = (e.target as HTMLInputElement).files?.[0]
  if (f) file.value = f
}

async function generate() {
  error.value = ''
  generating.value = true
  try {
    const { data } = await aiApi.generatePlan(props.projectId, {
      content: content.value.trim() || undefined,
      file: file.value,
      due_date: dueDate.value || undefined,
    })
    tasks.value = (data.tasks as AiPlanTask[]).map(t => ({
      title: t.title,
      description: t.description ?? '',
      status: t.status,
      priority: t.priority,
      due_date: t.due_date,
      assignee_id: t.suggested_assignee_id,
      subtasksText: (t.subtasks ?? []).join('\n'),
    }))
    step.value = 'preview'
  } catch (e: any) {
    error.value = e.response?.data?.message ?? 'Erro ao gerar o plano.'
  } finally {
    generating.value = false
  }
}

async function apply() {
  error.value = ''
  applying.value = true
  try {
    const payload = tasks.value.map(t => ({
      title: t.title.trim(),
      description: t.description.trim(),
      status: t.status,
      priority: t.priority,
      due_date: t.due_date || null,
      assignee_id: t.assignee_id || null,
      subtasks: t.subtasksText.split('\n').map(s => s.trim()).filter(Boolean),
    })).filter(t => t.title)

    if (!payload.length) { error.value = 'Adicione ao menos uma tarefa com título.'; return }

    await aiApi.applyPlan(props.projectId, payload)
    toast.success(`${payload.length} tarefa${payload.length !== 1 ? 's' : ''} criada${payload.length !== 1 ? 's' : ''} com sucesso!`)
    emit('created')
    emit('close')
  } catch (e: any) {
    error.value = e.response?.data?.message ?? 'Erro ao criar as tarefas.'
  } finally {
    applying.value = false
  }
}
</script>
