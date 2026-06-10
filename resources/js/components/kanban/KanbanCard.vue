<template>
  <div
    class="bg-dark-800 border rounded-lg p-3 cursor-pointer transition-all duration-150 group select-none"
    :class="[cardBorderClass, { 'opacity-50': dragging }]"
    draggable="true"
    @dragstart="onDragStart"
    @dragend="dragging = false; $emit('dragend')"
    @click.self="$emit('click')"
  >
    <!-- Approval banner (pending) -->
    <div
      v-if="task.approval_status === 'pending'"
      class="flex items-center gap-1.5 text-xs text-yellow-400 bg-yellow-500/10 border border-yellow-500/20 rounded-lg px-2.5 py-1.5 mb-2"
      @click="$emit('click')"
    >
      <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
      </svg>
      Aguardando aprovação do líder
    </div>

    <!-- Rejection banner -->
    <div
      v-else-if="task.approval_status === 'rejected'"
      class="mb-2 rounded-lg border border-red-500/20 bg-red-500/10 px-2.5 py-1.5"
      @click="$emit('click')"
    >
      <p class="text-xs text-red-400 font-medium flex items-center gap-1.5">
        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
          <path d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z"/>
        </svg>
        Reprovada pelo líder
      </p>
      <p v-if="task.rejection_note" class="text-xs text-red-300/80 mt-0.5 leading-relaxed">
        "{{ task.rejection_note }}"
      </p>
    </div>

    <!-- Approved banner -->
    <div
      v-else-if="task.approval_status === 'approved'"
      class="flex items-center gap-1.5 text-xs text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 rounded-lg px-2.5 py-1.5 mb-2"
      @click="$emit('click')"
    >
      <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14l-5-5 1.41-1.41L10 13.17l7.59-7.59L19 7l-9 9z"/>
      </svg>
      Aprovada pelo líder
    </div>

    <!-- Title row -->
    <div class="flex items-start justify-between gap-2 mb-2" @click="$emit('click')">
      <h4 class="text-sm font-medium text-dark-100 group-hover:text-white transition-colors leading-tight flex-1">
        {{ task.title }}
      </h4>
      <PriorityDot :priority="task.priority" class="flex-shrink-0 mt-0.5" />
    </div>

    <!-- Tags -->
    <div v-if="task.tags?.length" class="flex flex-wrap gap-1 mb-2" @click="$emit('click')">
      <span
        v-for="tag in task.tags" :key="tag.id"
        class="text-xs px-1.5 py-0.5 rounded-full font-medium"
        :style="{ backgroundColor: tag.color + '25', color: tag.color }"
      >{{ tag.name }}</span>
    </div>

    <!-- Status select -->
    <div class="mb-2" @click.stop>
      <select
        :value="task.status"
        @change="onStatusChange"
        class="w-full text-xs rounded-lg px-2 py-1.5 border border-dark-700 bg-dark-900 focus:outline-none focus:border-accent-500 cursor-pointer transition-colors"
        :class="statusClass"
      >
        <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
      </select>
    </div>

    <!-- Leader approval actions -->
    <div v-if="isLeader && task.approval_status === 'pending'" class="mb-2 space-y-1.5" @click.stop>
      <p class="text-xs text-dark-500 font-medium uppercase tracking-wide">Revisão do líder</p>
      <div class="flex gap-2">
        <button
          @click="handleApprove"
          :disabled="approving"
          class="flex-1 flex items-center justify-center gap-1.5 py-1.5 rounded-lg text-xs font-semibold bg-emerald-600/15 hover:bg-emerald-600/25 text-emerald-400 border border-emerald-600/20 transition-colors disabled:opacity-50"
        >
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
          </svg>
          Aprovar
        </button>
        <button
          @click="showRejectForm = !showRejectForm"
          class="flex-1 flex items-center justify-center gap-1.5 py-1.5 rounded-lg text-xs font-semibold bg-red-600/15 hover:bg-red-600/25 text-red-400 border border-red-600/20 transition-colors"
        >
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
          Reprovar
        </button>
      </div>

      <!-- Rejection note form -->
      <div v-if="showRejectForm" class="space-y-1.5 pt-1">
        <textarea
          v-model="rejectionNote"
          placeholder="Motivo da reprovação (opcional)..."
          rows="2"
          class="w-full text-xs bg-dark-900 border border-dark-700 rounded-lg px-2.5 py-2 text-dark-200 placeholder-dark-600 resize-none focus:outline-none focus:border-red-500/50"
        />
        <div class="flex gap-2">
          <button
            @click="showRejectForm = false; rejectionNote = ''"
            class="flex-1 py-1.5 text-xs text-dark-400 hover:text-dark-200 rounded-lg border border-dark-700 hover:border-dark-600 transition-colors"
          >Cancelar</button>
          <button
            @click="handleReject"
            :disabled="approving"
            class="flex-1 py-1.5 text-xs font-semibold text-red-400 bg-red-600/15 hover:bg-red-600/25 rounded-lg border border-red-600/20 transition-colors disabled:opacity-50"
          >Confirmar reprovação</button>
        </div>
      </div>
    </div>

    <!-- Submit for approval (non-leader, not yet submitted) -->
    <div
      v-else-if="!isLeader && !task.approval_status"
      class="mb-2"
      @click.stop
    >
      <button
        @click="handleSubmit"
        :disabled="approving"
        class="w-full flex items-center justify-center gap-1.5 py-1.5 rounded-lg text-xs font-medium text-dark-400 hover:text-yellow-400 border border-dark-700 hover:border-yellow-500/30 hover:bg-yellow-500/5 transition-all disabled:opacity-50"
      >
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        {{ approving ? 'Enviando...' : 'Enviar para aprovação' }}
      </button>
    </div>

    <!-- Re-submit after rejection -->
    <div
      v-else-if="!isLeader && task.approval_status === 'rejected'"
      class="mb-2"
      @click.stop
    >
      <button
        @click="handleSubmit"
        :disabled="approving"
        class="w-full flex items-center justify-center gap-1.5 py-1.5 rounded-lg text-xs font-medium text-red-400 hover:text-yellow-400 border border-red-600/20 hover:border-yellow-500/30 hover:bg-yellow-500/5 transition-all disabled:opacity-50"
      >
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        {{ approving ? 'Enviando...' : 'Reenviar para aprovação' }}
      </button>
    </div>

    <!-- Footer -->
    <div class="flex items-center justify-between pt-2 border-t border-dark-700/50">
      <!-- Left: checklist + overdue -->
      <div class="flex items-center gap-2" @click="$emit('click')">
        <span
          v-if="task.checklists_total > 0"
          class="flex items-center gap-1 text-xs"
          :class="task.checklists_done === task.checklists_total ? 'text-emerald-400' : 'text-dark-500'"
        >
          <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          {{ task.checklists_done }}/{{ task.checklists_total }}
        </span>
        <span v-if="task.is_overdue" class="text-xs text-red-400">⚠</span>
      </div>

      <!-- Right: due date + assignees -->
      <div class="flex items-center gap-1.5">
        <!-- Countdown de prazo -->
        <span
          v-if="task.due_date"
          class="text-xs px-1.5 py-0.5 rounded-md font-medium"
          :class="dueDateClass"
          @click="$emit('click')"
          :title="formatDate(task.due_date)"
        >{{ dueDateLabel }}</span>

        <!-- Assignees (up to 3) -->
        <div class="flex -space-x-1" @click="$emit('click')">
          <div
            v-for="a in displayAssignees" :key="a.id"
            :title="a.name"
            class="w-5 h-5 rounded-full bg-accent-600/40 border border-dark-800 flex items-center justify-center text-xs font-semibold text-accent-300"
          >{{ a.name[0] }}</div>
          <div
            v-if="extraAssignees > 0"
            class="w-5 h-5 rounded-full bg-dark-600 border border-dark-800 flex items-center justify-center text-xs text-dark-400"
          >+{{ extraAssignees }}</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { tasksApi } from '@/api/projects'
import type { Task, TaskStatus } from '@/types'
import PriorityDot from '@/components/ui/PriorityDot.vue'

const props = defineProps<{
  task: Task
  index: number
  projectId?: number
  isLeader?: boolean
}>()

const emit = defineEmits<{
  click: []
  dragstart: [e: DragEvent]
  dragend: []
  'status-change': [status: TaskStatus]
  'approval-change': [taskId: number, status: string, note?: string]
}>()

// ── drag ────────────────────────────────────────────
const dragging = ref(false)
function onDragStart(e: DragEvent) {
  dragging.value = true
  if (e.dataTransfer) {
    e.dataTransfer.setData('text/plain', String(props.task.id))
    e.dataTransfer.effectAllowed = 'move'
  }
  emit('dragstart', e)
}

// ── status ───────────────────────────────────────────
const statuses: { value: TaskStatus; label: string }[] = [
  { value: 'backlog',     label: 'Backlog' },
  { value: 'pending',     label: 'Pendente' },
  { value: 'in_progress', label: 'Em andamento' },
  { value: 'review',      label: 'Revisão' },
  { value: 'done',        label: 'Concluída' },
]
const statusColorMap: Record<string, string> = {
  backlog:     'text-slate-400',
  pending:     'text-yellow-400',
  in_progress: 'text-blue-400',
  review:      'text-purple-400',
  done:        'text-emerald-400',
}
const statusClass = computed(() => statusColorMap[props.task.status] ?? 'text-dark-400')

function onStatusChange(e: Event) {
  emit('status-change', (e.target as HTMLSelectElement).value as TaskStatus)
}

// ── card border based on approval ────────────────────
const cardBorderClass = computed(() => {
  if (props.task.approval_status === 'pending')  return 'border-yellow-500/40 hover:border-yellow-500/60'
  if (props.task.approval_status === 'approved') return 'border-emerald-500/40 hover:border-emerald-500/60'
  if (props.task.approval_status === 'rejected') return 'border-red-500/40 hover:border-red-500/60'
  return 'border-dark-700 hover:border-dark-600'
})

// ── approval ─────────────────────────────────────────
const approving = ref(false)
const showRejectForm = ref(false)
const rejectionNote = ref('')

async function handleSubmit() {
  if (!props.projectId) return
  approving.value = true
  try {
    await tasksApi.submitApproval(props.projectId, props.task.id)
    emit('approval-change', props.task.id, 'pending')
  } finally {
    approving.value = false
  }
}

async function handleApprove() {
  if (!props.projectId) return
  approving.value = true
  try {
    await tasksApi.approveTask(props.projectId, props.task.id)
    emit('approval-change', props.task.id, 'approved')
  } finally {
    approving.value = false
  }
}

async function handleReject() {
  if (!props.projectId) return
  approving.value = true
  try {
    await tasksApi.rejectTask(props.projectId, props.task.id, rejectionNote.value || undefined)
    emit('approval-change', props.task.id, 'rejected', rejectionNote.value)
    showRejectForm.value = false
    rejectionNote.value = ''
  } finally {
    approving.value = false
  }
}

// ── assignees ────────────────────────────────────────
const allAssignees = computed(() => {
  if (props.task.assignees?.length) return props.task.assignees
  if (props.task.assignee) return [props.task.assignee]
  return []
})
const displayAssignees = computed(() => allAssignees.value.slice(0, 3))
const extraAssignees   = computed(() => Math.max(0, allAssignees.value.length - 3))

function formatDate(d: string) {
  return new Date(d + 'T00:00:00').toLocaleDateString('pt-BR', { day: 'numeric', month: 'short' })
}

const dueDateDiff = computed(() => {
  if (!props.task.due_date) return null
  const today = new Date(); today.setHours(0, 0, 0, 0)
  const due   = new Date(props.task.due_date + 'T00:00:00')
  return Math.round((due.getTime() - today.getTime()) / 86400000)
})

const dueDateLabel = computed(() => {
  const d = dueDateDiff.value
  if (d === null) return ''
  if (props.task.status === 'done') return formatDate(props.task.due_date!)
  if (d < 0)  return `${Math.abs(d)}d atrasada`
  if (d === 0) return 'Vence hoje'
  if (d === 1) return 'Vence amanhã'
  if (d <= 7)  return `${d}d restantes`
  return formatDate(props.task.due_date!)
})

const dueDateClass = computed(() => {
  const d = dueDateDiff.value
  if (d === null || props.task.status === 'done') return 'text-dark-500'
  if (d < 0)  return 'bg-red-500/15 text-red-400'
  if (d === 0) return 'bg-orange-500/15 text-orange-400'
  if (d <= 3)  return 'bg-yellow-500/10 text-yellow-400'
  return 'text-dark-500'
})
</script>
