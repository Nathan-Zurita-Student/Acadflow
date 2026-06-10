<template>
  <div class="max-w-3xl mx-auto space-y-8 animate-fade-in">
    <div>
      <h2 class="text-xl font-bold text-dark-100">Configurações do Projeto</h2>
      <p class="text-sm text-dark-500 mt-0.5">{{ project?.name }}</p>
    </div>

    <!-- Webhooks section -->
    <section class="card space-y-4">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="font-semibold text-dark-100">Webhooks</h3>
          <p class="text-xs text-dark-500 mt-0.5">Receba notificações em Discord, Slack ou qualquer serviço.</p>
        </div>
        <button @click="openCreate" class="btn-primary text-sm">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Adicionar
        </button>
      </div>

      <div v-if="loadingWebhooks" class="flex justify-center py-6">
        <div class="w-5 h-5 border-2 border-indigo-500/30 border-t-indigo-500 rounded-full animate-spin" />
      </div>

      <div v-else-if="webhooks.length" class="space-y-3">
        <div v-for="w in webhooks" :key="w.id"
          class="flex items-start gap-3 p-4 bg-dark-700/40 rounded-xl border border-dark-700 group">
          <!-- Active toggle -->
          <button @click="toggleActive(w)"
            class="mt-0.5 w-9 h-5 rounded-full relative transition-colors flex-shrink-0"
            :class="w.active ? 'bg-indigo-600' : 'bg-dark-600'">
            <span class="absolute top-0.5 w-4 h-4 rounded-full bg-white shadow transition-transform"
              :class="w.active ? 'translate-x-4' : 'translate-x-0.5'" />
          </button>

          <div class="flex-1 min-w-0">
            <p class="text-sm font-mono text-dark-300 truncate">{{ w.url }}</p>
            <div class="flex flex-wrap gap-1 mt-1.5">
              <span v-for="ev in w.events" :key="ev"
                class="text-[10px] bg-dark-600 text-dark-400 px-1.5 py-0.5 rounded-full">
                {{ ev }}
              </span>
            </div>
          </div>

          <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0">
            <button @click="testWebhook(w)"
              class="text-xs text-teal-400 hover:text-teal-300 px-2 py-1 rounded-lg hover:bg-teal-500/10 transition-colors">
              Testar
            </button>
            <button @click="deleteWebhook(w)"
              class="p-1.5 rounded-lg hover:bg-red-600/20 text-dark-500 hover:text-red-400 transition-colors">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <div v-else class="text-center py-8 text-dark-600">
        <svg class="w-10 h-10 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
        </svg>
        <p class="text-sm">Nenhum webhook configurado</p>
      </div>
    </section>
  </div>

  <!-- Webhook create modal -->
  <Teleport to="body">
    <div v-if="showModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm animate-fade-in"
      @click.self="showModal = false">
      <div class="w-full max-w-md bg-dark-800 border border-dark-700 rounded-2xl shadow-2xl animate-slide-up">
        <div class="flex items-center justify-between px-6 py-4 border-b border-dark-700">
          <h2 class="font-semibold text-white">Novo Webhook</h2>
          <button @click="showModal = false" class="p-1 rounded-lg hover:bg-dark-700 text-dark-400">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <form @submit.prevent="saveWebhook" class="p-6 space-y-4">
          <div>
            <label class="label">URL do Webhook *</label>
            <input v-model="wForm.url" class="input font-mono text-sm"
              placeholder="https://discord.com/api/webhooks/..." required />
            <p class="text-xs text-dark-600 mt-1">
              Compatível com Discord, Slack, Teams, Make, Zapier e qualquer HTTP POST.
            </p>
          </div>

          <div>
            <label class="label">Eventos a monitorar *</label>
            <div class="grid grid-cols-2 gap-2">
              <label v-for="ev in AVAILABLE_EVENTS" :key="ev.value"
                class="flex items-center gap-2 px-3 py-2 rounded-lg border cursor-pointer transition-colors"
                :class="wForm.events.includes(ev.value)
                  ? 'border-indigo-500/50 bg-indigo-500/10 text-indigo-300'
                  : 'border-dark-700 hover:border-dark-600 text-dark-400'">
                <input type="checkbox" :value="ev.value" v-model="wForm.events" class="hidden" />
                <div class="w-3.5 h-3.5 rounded border-2 flex items-center justify-center flex-shrink-0"
                  :class="wForm.events.includes(ev.value) ? 'bg-indigo-600 border-indigo-600' : 'border-dark-600'">
                  <svg v-if="wForm.events.includes(ev.value)" class="w-2.5 h-2.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                  </svg>
                </div>
                <span class="text-xs">{{ ev.label }}</span>
              </label>
            </div>
          </div>

          <div class="flex gap-3 pt-2">
            <button type="button" @click="showModal = false" class="btn-secondary flex-1 justify-center">Cancelar</button>
            <button type="submit" :disabled="saving || !wForm.events.length" class="btn-primary flex-1 justify-center">
              <span v-if="saving" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
              <span v-else>Criar</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useProjectsStore } from '@/stores/projects'
import { useToast } from '@/composables/useToast'
import { webhooksApi } from '@/api/projects'
import type { ProjectWebhook } from '@/types'

const route = useRoute()
const projectId = Number(route.params.id)
const projectsStore = useProjectsStore()
const { currentProject: project } = storeToRefs(projectsStore)
const toast = useToast()

const webhooks        = ref<ProjectWebhook[]>([])
const loadingWebhooks = ref(true)
const showModal       = ref(false)
const saving          = ref(false)

const AVAILABLE_EVENTS = [
  { value: 'task_created',      label: '📋 Tarefa criada' },
  { value: 'task_updated',      label: '✏️ Tarefa atualizada' },
  { value: 'task_approved',     label: '✅ Tarefa aprovada' },
  { value: 'task_rejected',     label: '❌ Tarefa rejeitada' },
  { value: 'member_added',      label: '🎓 Membro adicionado' },
  { value: 'meeting_scheduled', label: '📅 Reunião agendada' },
]

const wForm = ref({ url: '', events: [] as string[] })

function openCreate() {
  wForm.value = { url: '', events: [] }
  showModal.value = true
}

async function saveWebhook() {
  if (!wForm.value.url || !wForm.value.events.length) return
  saving.value = true
  try {
    const { data } = await webhooksApi.create(projectId, wForm.value)
    webhooks.value.unshift(data)
    showModal.value = false
    toast.success('Webhook criado!')
  } catch {
    toast.error('Erro ao criar webhook.')
  } finally {
    saving.value = false
  }
}

async function toggleActive(w: ProjectWebhook) {
  try {
    const { data } = await webhooksApi.update(projectId, w.id, { active: !w.active })
    Object.assign(w, data)
  } catch {
    toast.error('Erro ao atualizar webhook.')
  }
}

async function testWebhook(w: ProjectWebhook) {
  try {
    const { data } = await webhooksApi.test(projectId, w.id)
    if (data.success) toast.success('Webhook testado com sucesso!')
    else toast.error('Falha no teste do webhook.')
  } catch {
    toast.error('Erro ao testar webhook.')
  }
}

async function deleteWebhook(w: ProjectWebhook) {
  if (!confirm('Remover este webhook?')) return
  try {
    await webhooksApi.delete(projectId, w.id)
    webhooks.value = webhooks.value.filter(x => x.id !== w.id)
    toast.success('Webhook removido.')
  } catch {
    toast.error('Erro ao remover webhook.')
  }
}

onMounted(async () => {
  await projectsStore.fetchProject(projectId)
  try {
    const { data } = await webhooksApi.list(projectId)
    webhooks.value = data
  } finally {
    loadingWebhooks.value = false
  }
})
</script>
