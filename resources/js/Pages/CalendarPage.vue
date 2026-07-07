<template>
  <div class="space-y-5 stagger-in">
    <!-- Cabeçalho -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-white">Calendário</h1>
        <p class="text-dark-400 text-sm">Prazos, carga de trabalho e entregas dos seus projetos</p>
      </div>
    </div>

    <!-- Resumo -->
    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-5 gap-3">
      <div class="card py-3 px-4">
        <p class="text-xs text-dark-500 mb-1">Tarefas ativas</p>
        <p class="text-2xl font-bold text-white">{{ activeCount }}</p>
      </div>
      <div class="card py-3 px-4">
        <p class="text-xs text-dark-500 mb-1">Concluídas</p>
        <p class="text-2xl font-bold text-blue-400">{{ doneCount }}</p>
      </div>
      <div class="card py-3 px-4">
        <p class="text-xs text-dark-500 mb-1">Atrasadas</p>
        <p class="text-2xl font-bold text-red-400">{{ overdueCount }}</p>
      </div>
      <div class="card py-3 px-4">
        <p class="text-xs text-dark-500 mb-1">Entregas na semana</p>
        <p class="text-2xl font-bold text-amber-400">{{ weekDeliveries }}</p>
      </div>
      <div class="card py-3 px-4 col-span-2 sm:col-span-3 xl:col-span-1">
        <p class="text-xs text-dark-500 mb-1">Próxima entrega</p>
        <template v-if="nextDelivery">
          <p class="text-sm font-bold text-white truncate" :title="nextDelivery.title">{{ nextDelivery.title }}</p>
          <p class="text-xs text-dark-400">{{ formatBr(nextDelivery.due_date!) }}</p>
        </template>
        <p v-else class="text-sm font-medium text-dark-500">Nenhuma</p>
      </div>
    </div>

    <!-- Filtros -->
    <div class="space-y-3">
      <!-- Search (100% da largura) -->
      <div class="relative w-full">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-dark-500 pointer-events-none"
          fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input v-model="searchQuery" placeholder="Buscar tarefa..."
          class="w-full text-sm bg-dark-800 border border-dark-700 rounded-xl pl-9 pr-3 py-2 text-dark-200 placeholder-dark-600 focus:outline-none focus:border-accent-500" />
      </div>

      <!-- Selects em grade 2x2 -->
      <div class="grid grid-cols-2 gap-2">
        <select v-model="filterProject" class="w-full text-sm bg-dark-800 border border-dark-700 rounded-xl px-3 py-2 text-dark-300 focus:outline-none focus:border-accent-500">
          <option value="">Todos os projetos</option>
          <option v-for="p in uniqueProjects" :key="p.id" :value="p.id">{{ p.name }}</option>
        </select>

        <select v-model="filterMember" class="w-full text-sm bg-dark-800 border border-dark-700 rounded-xl px-3 py-2 text-dark-300 focus:outline-none focus:border-accent-500">
          <option value="">Todos os membros</option>
          <option v-for="m in uniqueMembers" :key="m.id" :value="m.id">{{ m.name }}</option>
        </select>

        <select v-model="filterStatus" class="w-full text-sm bg-dark-800 border border-dark-700 rounded-xl px-3 py-2 text-dark-300 focus:outline-none focus:border-accent-500">
          <option value="">Todos os status</option>
          <option v-for="s in uniqueStatuses" :key="s" :value="s">{{ statusLabel(s) }}</option>
        </select>

        <select v-model="filterPriority" class="w-full text-sm bg-dark-800 border border-dark-700 rounded-xl px-3 py-2 text-dark-300 focus:outline-none focus:border-accent-500">
          <option value="">Todas as prioridades</option>
          <option value="urgent">Urgente</option>
          <option value="high">Alta</option>
          <option value="medium">Média</option>
          <option value="low">Baixa</option>
        </select>
      </div>

      <!-- Limpar + legenda + contagem -->
      <div class="flex flex-wrap items-center gap-3">
        <button v-if="hasFilters" @click="clearFilters"
          class="text-xs text-dark-500 hover:text-dark-300 px-2 py-1.5 rounded-lg hover:bg-dark-700 transition-colors">
          Limpar
        </button>
        <div class="ml-auto flex items-center gap-3 text-[11px] text-dark-400">
          <span v-for="c in legend" :key="c.key" class="hidden sm:flex items-center gap-1.5">
            <span :class="['w-2.5 h-2.5 rounded-sm', DOT_CLASSES[c.key]]" />
            {{ c.label }}
          </span>
          <span class="text-dark-500">{{ filteredTasks.length }} tarefa{{ filteredTasks.length !== 1 ? 's' : '' }}</span>
        </div>
      </div>
    </div>

    <!-- Calendário -->
    <div class="acad-fc relative bg-dark-800 border border-dark-700 rounded-xl p-3 sm:p-4">
      <div v-if="loading" class="absolute top-3 right-4 z-10 flex items-center gap-1.5 text-xs text-dark-500">
        <span class="w-3 h-3 border-2 border-dark-600 border-t-accent-500 rounded-full animate-spin" />
        Carregando…
      </div>
      <FullCalendar :options="calendarOptions" />
    </div>

    <!-- Painel do dia selecionado -->
    <CalendarDayPanel v-if="selectedDay"
      :day="selectedDay" :tasks="panelTasks"
      @open-task="openTask" @close="selectedDay = null" />

    <!-- Modal de tarefa (reuso) -->
    <TaskModal v-if="selectedTask"
      :project-id="selectedTask.project.id"
      :task="selectedTask as any"
      @close="selectedTask = null"
      @saved="onTaskSaved" />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onUnmounted } from 'vue'
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'
import ptBrLocale from '@fullcalendar/core/locales/pt-br'
import type { CalendarOptions, EventClickArg, EventInput } from '@fullcalendar/core'
import { calendarApi } from '@/api/projects'
import { useRealtimeStore } from '@/stores/realtime'
import { ymd, addDays, parseYmd, taskRange, taskColor, taskCoversDay, DOT_CLASSES, COLOR_LABELS, type CalColor } from '@/composables/useCalendar'
import CalendarDayPanel from '@/components/calendar/CalendarDayPanel.vue'
import TaskModal from '@/components/tasks/TaskModal.vue'
import type { CalendarTask } from '@/types'

const realtimeStore = useRealtimeStore()

const tasks = ref<CalendarTask[]>([])
const loading = ref(true)
const currentRange = ref<{ from: string; to: string } | null>(null)

const searchQuery = ref('')
const filterProject = ref('')
const filterMember = ref('')
const filterStatus = ref('')
const filterPriority = ref('')

const selectedDay = ref<string | null>(null)
const selectedTask = ref<CalendarTask | null>(null)

const legend = (Object.keys(COLOR_LABELS) as CalColor[]).map(key => ({ key, label: COLOR_LABELS[key] }))

const COLOR_HEX: Record<CalColor, string> = {
  green: '#10b981', yellow: '#f59e0b', red: '#ef4444', blue: '#3b82f6',
}

// ── Carregamento dos dados (dirigido pelo datesSet do FullCalendar) ──
async function load(silent = false) {
  if (!currentRange.value) return
  if (!silent) loading.value = true
  try {
    const { data } = await calendarApi.range(currentRange.value.from, currentRange.value.to)
    tasks.value = data
  } finally {
    loading.value = false
  }
}

let staleTimer: ReturnType<typeof setTimeout> | null = null
watch(() => realtimeStore.dashboardStaleTick, () => {
  if (staleTimer) clearTimeout(staleTimer)
  staleTimer = setTimeout(() => load(true), 400)
})
onUnmounted(() => { if (staleTimer) clearTimeout(staleTimer) })

// ── Opções de filtro derivadas das tarefas ──
const uniqueProjects = computed(() => {
  const map = new Map<number, { id: number; name: string }>()
  for (const t of tasks.value) if (!map.has(t.project.id)) map.set(t.project.id, t.project)
  return [...map.values()].sort((a, b) => a.name.localeCompare(b.name))
})
const uniqueMembers = computed(() => {
  const map = new Map<number, { id: number; name: string }>()
  for (const t of tasks.value) {
    for (const m of [t.assignee, ...t.assignees]) {
      if (m && !map.has(m.id)) map.set(m.id, { id: m.id, name: m.name })
    }
  }
  return [...map.values()].sort((a, b) => a.name.localeCompare(b.name))
})
const uniqueStatuses = computed(() => [...new Set(tasks.value.map(t => t.status))])

// ── Tarefas filtradas ──
const filteredTasks = computed(() => {
  let list = tasks.value
  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase()
    list = list.filter(t => t.title.toLowerCase().includes(q))
  }
  if (filterProject.value) list = list.filter(t => t.project.id === Number(filterProject.value))
  if (filterMember.value) {
    const id = Number(filterMember.value)
    list = list.filter(t => t.assignee?.id === id || t.assignees.some(a => a.id === id))
  }
  if (filterStatus.value) list = list.filter(t => t.status === filterStatus.value)
  if (filterPriority.value) list = list.filter(t => t.priority === filterPriority.value)
  return list
})

const hasFilters = computed(() =>
  !!(searchQuery.value || filterProject.value || filterMember.value || filterStatus.value || filterPriority.value),
)
function clearFilters() {
  searchQuery.value = ''; filterProject.value = ''; filterMember.value = ''; filterStatus.value = ''; filterPriority.value = ''
}

// ── Eventos do FullCalendar ──
const events = computed<EventInput[]>(() =>
  filteredTasks.value.flatMap(t => {
    const r = taskRange(t)
    if (!r) return []
    const color = taskColor(t)
    return [{
      id: String(t.id),
      title: t.title,
      start: ymd(r.start),
      end: ymd(addDays(r.end, 1)), // FullCalendar trata o fim como exclusivo
      allDay: true,
      backgroundColor: COLOR_HEX[color],
      borderColor: COLOR_HEX[color],
      textColor: color === 'yellow' ? '#1f2937' : '#ffffff',
      extendedProps: { task: t },
    }]
  }),
)

const calendarOptions = computed<CalendarOptions>(() => ({
  plugins: [dayGridPlugin, interactionPlugin],
  initialView: 'dayGridMonth',
  locale: ptBrLocale,
  firstDay: 0,
  headerToolbar: { left: 'prev,next today', center: 'title', right: '' },
  buttonText: { today: 'Hoje' },
  height: 'auto',
  fixedWeekCount: false,
  dayMaxEvents: 3,
  displayEventTime: false,
  events: events.value,
  eventClick: onEventClick,
  dateClick: onDateClick,
  datesSet: onDatesSet,
  eventDidMount: onEventMount,
}))

function onDatesSet(arg: { startStr: string; endStr: string }) {
  currentRange.value = { from: arg.startStr.slice(0, 10), to: arg.endStr.slice(0, 10) }
  load()
}
function onEventClick(arg: EventClickArg) {
  openTask(arg.event.extendedProps.task as CalendarTask)
}
function onDateClick(arg: { dateStr: string }) {
  selectedDay.value = selectedDay.value === arg.dateStr ? null : arg.dateStr
}
function onEventMount(arg: { el: HTMLElement; event: { extendedProps: Record<string, any> } }) {
  const t = arg.event.extendedProps.task as CalendarTask
  const parts = [t.title, t.project.name]
  if (t.assignee) parts.push('Resp.: ' + t.assignee.name)
  if (t.due_date) parts.push('Entrega: ' + formatBr(t.due_date))
  arg.el.title = parts.join(' · ')
}

// ── Métricas do resumo ──
const activeCount = computed(() => filteredTasks.value.filter(t => t.status !== 'done').length)
const doneCount = computed(() => filteredTasks.value.filter(t => t.status === 'done').length)
const overdueCount = computed(() => filteredTasks.value.filter(t => t.is_overdue && t.status !== 'done').length)
const nextDelivery = computed(() => {
  const today = ymd(new Date())
  return filteredTasks.value
    .filter(t => t.status !== 'done' && t.due_date && t.due_date >= today)
    .sort((a, b) => (a.due_date! < b.due_date! ? -1 : 1))[0] ?? null
})
const weekDeliveries = computed(() => {
  const start = new Date(); start.setHours(0, 0, 0, 0)
  const end = addDays(start, 7)
  return filteredTasks.value.filter(t => {
    if (t.status === 'done' || !t.due_date) return false
    const d = parseYmd(t.due_date)
    return d.getTime() >= start.getTime() && d.getTime() <= end.getTime()
  }).length
})

// ── Painel do dia ──
const panelTasks = computed(() =>
  selectedDay.value ? filteredTasks.value.filter(t => taskCoversDay(t, selectedDay.value!)) : [],
)

function openTask(task: CalendarTask) { selectedTask.value = task }
function onTaskSaved() { selectedTask.value = null; load(true) }

function formatBr(s: string) {
  const [y, m, d] = s.split('-')
  return `${d}/${m}/${y}`
}

const STATUS_LABEL: Record<string, string> = {
  backlog: 'Backlog', pending: 'Pendente', in_progress: 'Em andamento', review: 'Revisão', done: 'Concluída',
}
function statusLabel(s: string) { return STATUS_LABEL[s] ?? s }
</script>

<style>
/* Tema escuro do FullCalendar (escopado pela classe .acad-fc do contêiner). */
.acad-fc .fc {
  --fc-border-color: rgb(var(--d-700) / 0.6);
  --fc-page-bg-color: transparent;
  --fc-neutral-bg-color: rgb(var(--d-900));
  --fc-today-bg-color: rgb(var(--accent-600) / 0.08);
  --fc-event-border-color: transparent;
  color: rgb(var(--d-200));
}
.acad-fc .fc .fc-toolbar.fc-header-toolbar { margin-bottom: 1rem; }
.acad-fc .fc-toolbar-title { color: rgb(var(--d-50)); font-size: 1.05rem; font-weight: 700; text-transform: capitalize; }
.acad-fc .fc .fc-button-primary {
  background: rgb(var(--d-700));
  border-color: rgb(var(--d-600));
  color: rgb(var(--d-100));
  text-transform: capitalize;
  font-size: 0.8rem;
  padding: 0.35rem 0.7rem;
  box-shadow: none;
}
.acad-fc .fc .fc-button-primary:hover { background: rgb(var(--d-600)); border-color: rgb(var(--d-500)); }
.acad-fc .fc .fc-button-primary:disabled { opacity: 0.45; }
.acad-fc .fc .fc-button-primary:not(:disabled):active,
.acad-fc .fc .fc-button-primary:not(:disabled).fc-button-active {
  background: rgb(var(--accent-600));
  border-color: rgb(var(--accent-600));
}
.acad-fc .fc .fc-button:focus { box-shadow: 0 0 0 2px rgb(var(--accent-500) / 0.4); }
.acad-fc .fc-col-header-cell-cushion {
  color: rgb(var(--d-500));
  text-transform: uppercase;
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.04em;
  padding: 8px 4px;
}
.acad-fc .fc-daygrid-day-number { color: rgb(var(--d-300)); font-size: 12px; padding: 6px 8px; }
.acad-fc .fc-day-other .fc-daygrid-day-frame { background: rgb(var(--d-900) / 0.35); }
.acad-fc .fc-day-other .fc-daygrid-day-number { color: rgb(var(--d-600)); }
.acad-fc .fc-day-today .fc-daygrid-day-number {
  background: rgb(var(--accent-600));
  color: #fff;
  border-radius: 9999px;
  width: 22px; height: 22px;
  display: inline-flex; align-items: center; justify-content: center;
  margin: 4px;
  font-weight: 700;
}
.acad-fc .fc-daygrid-day-frame { min-height: 92px; cursor: pointer; transition: background 0.12s; }
.acad-fc .fc-daygrid-day-frame:hover { background: rgb(var(--d-700) / 0.25); }
.acad-fc .fc-daygrid-event {
  font-size: 11px;
  font-weight: 600;
  padding: 1px 6px;
  border-radius: 5px;
  cursor: pointer;
  margin-top: 2px;
}
.acad-fc .fc-daygrid-event:hover { filter: brightness(1.12); }
.acad-fc .fc-event-title { overflow: hidden; text-overflow: ellipsis; }
.acad-fc .fc-more-link { color: rgb(var(--accent-400)); font-size: 11px; font-weight: 600; }
.acad-fc .fc-scrollgrid, .acad-fc .fc-scrollgrid td, .acad-fc .fc-scrollgrid th { border-color: rgb(var(--d-700) / 0.55); }
.acad-fc .fc-scrollgrid { border-radius: 8px; overflow: hidden; }

/* Popover de "+N mais" (renderizado fora do contêiner) */
.fc-popover.fc-more-popover {
  background: rgb(var(--d-800));
  border: 1px solid rgb(var(--d-700));
  border-radius: 10px;
  box-shadow: 0 10px 30px rgb(0 0 0 / 0.5);
}
.fc-popover .fc-popover-header {
  background: rgb(var(--d-900));
  color: rgb(var(--d-100));
  padding: 6px 10px;
  font-size: 12px;
  font-weight: 600;
  text-transform: capitalize;
}
.fc-popover .fc-popover-close { color: rgb(var(--d-400)); opacity: 0.8; }
.fc-popover .fc-popover-body { padding: 6px; }
.fc-popover .fc-daygrid-event { font-size: 11px; font-weight: 600; }
</style>
