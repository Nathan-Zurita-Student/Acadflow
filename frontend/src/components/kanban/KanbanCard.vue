<template>
  <div
    class="bg-dark-800 border border-dark-700 rounded-lg p-3 cursor-pointer hover:border-dark-600
           transition-all duration-150 group select-none"
    :class="{ 'opacity-50': dragging }"
    draggable="true"
    @dragstart="onDragStart"
    @dragend="dragging = false; $emit('dragend')"
    @click.self="$emit('click')"
  >
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
        class="w-full text-xs rounded-lg px-2 py-1.5 border border-dark-700 bg-dark-900 focus:outline-none focus:border-indigo-500 cursor-pointer transition-colors"
        :class="statusClass"
      >
        <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
      </select>
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

      <!-- Right: timer + due date + assignees -->
      <div class="flex items-center gap-1.5">
        <!-- Timer -->
        <button
          class="flex items-center gap-1 text-xs px-1.5 py-0.5 rounded-md transition-colors"
          :class="timerRunning
            ? 'text-indigo-400 bg-indigo-500/10 hover:bg-indigo-500/20'
            : 'text-dark-500 hover:text-dark-300 hover:bg-dark-700'"
          @click.stop="toggleTimer"
          :title="timerRunning ? 'Parar timer' : 'Iniciar timer'"
        >
          <svg v-if="!timerRunning" class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
            <path d="M8 5v14l11-7z" />
          </svg>
          <svg v-else class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
            <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z" />
          </svg>
          <span>{{ timerDisplay }}</span>
        </button>

        <!-- Due date -->
        <span
          v-if="task.due_date"
          class="text-xs"
          :class="task.is_overdue ? 'text-red-400' : 'text-dark-500'"
          @click="$emit('click')"
        >{{ formatDate(task.due_date) }}</span>

        <!-- Assignees (up to 3) -->
        <div class="flex -space-x-1" @click="$emit('click')">
          <div
            v-for="a in displayAssignees" :key="a.id"
            :title="a.name"
            class="w-5 h-5 rounded-full bg-indigo-600/40 border border-dark-800 flex items-center justify-center text-xs font-semibold text-indigo-300"
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
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { timeApi } from '@/api/projects'
import type { Task, TaskStatus } from '@/types'
import PriorityDot from '@/components/ui/PriorityDot.vue'

const props = defineProps<{ task: Task; index: number; projectId?: number }>()
const emit = defineEmits<{
  click: []
  dragstart: [e: DragEvent]
  dragend: []
  'status-change': [status: TaskStatus]
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
  const newStatus = (e.target as HTMLSelectElement).value as TaskStatus
  emit('status-change', newStatus)
}

// ── assignees ────────────────────────────────────────
const allAssignees = computed(() => {
  if (props.task.assignees?.length) return props.task.assignees
  if (props.task.assignee) return [props.task.assignee]
  return []
})
const displayAssignees = computed(() => allAssignees.value.slice(0, 3))
const extraAssignees   = computed(() => Math.max(0, allAssignees.value.length - 3))

// ── timer ────────────────────────────────────────────
const STORAGE_KEY = `acadflow_timer_${props.task.id}`
const elapsed   = ref(0)
const timerRunning = ref(false)
let interval: ReturnType<typeof setInterval> | null = null

function loadTimer() {
  const raw = localStorage.getItem(STORAGE_KEY)
  if (!raw) return
  const startedAt = Number(raw)
  elapsed.value = Math.floor((Date.now() - startedAt) / 1000)
  timerRunning.value = true
  startInterval()
}

function startInterval() {
  if (interval) return
  interval = setInterval(() => {
    const raw = localStorage.getItem(STORAGE_KEY)
    if (raw) elapsed.value = Math.floor((Date.now() - Number(raw)) / 1000)
  }, 1000)
}

async function toggleTimer() {
  if (timerRunning.value) {
    // Stop
    clearInterval(interval!)
    interval = null
    timerRunning.value = false
    const seconds = elapsed.value
    localStorage.removeItem(STORAGE_KEY)
    elapsed.value = 0
    if (seconds > 0 && props.projectId) {
      try {
        await timeApi.log(props.projectId, props.task.id, seconds)
      } catch {}
    }
  } else {
    // Start
    localStorage.setItem(STORAGE_KEY, String(Date.now()))
    timerRunning.value = true
    elapsed.value = 0
    startInterval()
  }
}

const timerDisplay = computed(() => {
  const base = props.task.time_seconds + (timerRunning.value ? elapsed.value : 0)
  if (base === 0 && !timerRunning.value) return ''
  const h = Math.floor(base / 3600)
  const m = Math.floor((base % 3600) / 60)
  const s = base % 60
  if (h > 0) return `${h}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
  return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
})

onMounted(loadTimer)
onUnmounted(() => { if (interval) clearInterval(interval) })

// ── helpers ──────────────────────────────────────────
function formatDate(d: string) {
  return new Date(d + 'T00:00:00').toLocaleDateString('pt-BR', { day: 'numeric', month: 'short' })
}
</script>
