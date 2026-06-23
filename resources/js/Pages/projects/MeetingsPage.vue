<template>
  <div class="max-w-4xl mx-auto space-y-6 animate-fade-in">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-bold text-dark-100">Reuniões</h2>
        <p class="text-sm text-dark-500 mt-0.5">{{ project?.name }}</p>
      </div>
      <button @click="openCreate" class="btn-primary">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Nova Reunião
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-16">
      <div class="w-8 h-8 border-2 border-accent-500/30 border-t-accent-500 rounded-full animate-spin" />
    </div>

    <template v-else>
      <!-- Upcoming meetings -->
      <section v-if="upcoming.length">
        <h3 class="text-xs font-semibold text-dark-500 uppercase tracking-wider mb-3">
          Próximas ({{ upcoming.length }})
        </h3>
        <div class="space-y-3">
          <MeetingCard
            v-for="m in upcoming" :key="m.id"
            :meeting="m"
            :can-edit="canEdit(m)"
            @edit="openEdit(m)"
            @delete="deleteMeeting(m)"
          />
        </div>
      </section>

      <!-- Past meetings -->
      <section v-if="past.length">
        <h3 class="text-xs font-semibold text-dark-500 uppercase tracking-wider mb-3">
          Anteriores ({{ past.length }})
        </h3>
        <div class="space-y-3 opacity-70">
          <MeetingCard
            v-for="m in past" :key="m.id"
            :meeting="m"
            :can-edit="canEdit(m)"
            @edit="openEdit(m)"
            @delete="deleteMeeting(m)"
          />
        </div>
      </section>

      <!-- Empty state -->
      <div v-if="!upcoming.length && !past.length"
        class="flex flex-col items-center gap-3 py-20 text-dark-600">
        <svg class="w-16 h-16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <p class="text-sm font-medium">Nenhuma reunião agendada</p>
        <p class="text-xs">Crie a primeira reunião do grupo!</p>
      </div>
    </template>

    <!-- Create / Edit Modal -->
    <Teleport to="body">
      <div v-if="showModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm animate-fade-in"
        @click.self="showModal = false">
        <div class="w-full max-w-lg bg-dark-800 border border-dark-700 rounded-2xl shadow-2xl animate-slide-up">
          <div class="flex items-center justify-between px-6 py-4 border-b border-dark-700">
            <h2 class="font-semibold text-white">{{ editing ? 'Editar Reunião' : 'Nova Reunião' }}</h2>
            <button @click="showModal = false" class="p-1 rounded-lg hover:bg-dark-700 text-dark-400">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <form @submit.prevent="saveForm" class="p-6 space-y-4">
            <div>
              <label class="label">Título *</label>
              <input v-model="form.title" class="input" placeholder="Ex: Reunião de alinhamento" required />
            </div>
            <div>
              <label class="label">Descrição</label>
              <textarea v-model="form.description" class="input resize-none" rows="2" placeholder="Pauta da reunião..." />
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="label">Data e hora *</label>
                <input v-model="form.scheduled_at" type="datetime-local" class="input" required />
              </div>
              <div>
                <label class="label">Local / Link</label>
                <input v-model="form.location" class="input" placeholder="Sala 3, Google Meet..." />
              </div>
            </div>
            <div>
              <label class="label">Ata / Notas</label>
              <textarea v-model="form.notes" class="input resize-none" rows="3"
                placeholder="Registre decisões, tarefas ou anotações da reunião..." />
            </div>

            <div class="flex gap-3 pt-2">
              <button type="button" @click="showModal = false" class="btn-secondary flex-1 justify-center">Cancelar</button>
              <button type="submit" :disabled="saving" class="btn-primary flex-1 justify-center">
                <span v-if="saving" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                <span v-else>{{ editing ? 'Salvar' : 'Criar' }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useProjectsStore } from '@/stores/projects'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'
import { meetingsApi } from '@/api/projects'
import MeetingCard from '@/components/ui/MeetingCard.vue'
import type { Meeting } from '@/types'

const route = useRoute()
const projectId = Number(route.params.id)

const projectsStore = useProjectsStore()
const { currentProject: project } = storeToRefs(projectsStore)
const auth = useAuthStore()
const toast = useToast()

const meetings  = ref<Meeting[]>([])
const loading   = ref(true)
const saving    = ref(false)
const showModal = ref(false)
const editing   = ref<Meeting | null>(null)

const form = ref({
  title: '',
  description: '',
  scheduled_at: '',
  location: '',
  notes: '',
})

const upcoming = computed(() => meetings.value.filter(m => !m.is_past))
const past     = computed(() => meetings.value.filter(m => m.is_past))

function canEdit(m: Meeting) {
  return m.creator?.id === auth.user?.id || !!project.value?.is_owner
}

function openCreate() {
  editing.value = null
  form.value = { title: '', description: '', scheduled_at: '', location: '', notes: '' }
  showModal.value = true
}

function openEdit(m: Meeting) {
  editing.value = m
  form.value = {
    title: m.title,
    description: m.description ?? '',
    scheduled_at: m.scheduled_at.slice(0, 16),
    location: m.location ?? '',
    notes: m.notes ?? '',
  }
  showModal.value = true
}

async function saveForm() {
  if (!form.value.title || !form.value.scheduled_at) return
  saving.value = true
  try {
    if (editing.value) {
      const { data } = await meetingsApi.update(projectId, editing.value.id, form.value)
      const idx = meetings.value.findIndex(m => m.id === editing.value!.id)
      if (idx !== -1) meetings.value[idx] = data
      toast.success('Reunião atualizada.')
    } else {
      const { data } = await meetingsApi.create(projectId, form.value)
      meetings.value.unshift(data)
      toast.success('Reunião agendada!')
    }
    showModal.value = false
  } catch {
    toast.error('Erro ao salvar reunião.')
  } finally {
    saving.value = false
  }
}

async function deleteMeeting(m: Meeting) {
  const { confirm } = useConfirm()
  if (!await confirm({ message: `Remover "${m.title}"?`, confirmText: 'Remover', variant: 'danger' })) return
  try {
    await meetingsApi.delete(projectId, m.id)
    meetings.value = meetings.value.filter(x => x.id !== m.id)
    toast.success('Reunião removida.')
  } catch {
    toast.error('Erro ao remover reunião.')
  }
}

onMounted(async () => {
  await projectsStore.fetchProject(projectId)
  try {
    const { data } = await meetingsApi.list(projectId)
    meetings.value = data
  } catch {
    toast.error('Erro ao carregar reuniões.')
  } finally {
    loading.value = false
  }
})
</script>
