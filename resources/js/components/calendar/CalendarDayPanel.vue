<template>
  <div class="bg-dark-800 border border-dark-700 rounded-xl overflow-hidden flex flex-col">
    <div class="flex items-center justify-between px-4 py-3 border-b border-dark-700">
      <div>
        <h3 class="font-semibold text-white text-sm capitalize">{{ dayTitle }}</h3>
        <p class="text-xs text-dark-500">{{ tasks.length }} tarefa{{ tasks.length !== 1 ? 's' : '' }}</p>
      </div>
      <button v-if="day" @click="emit('close')" class="p-1 rounded-lg hover:bg-dark-700 text-dark-400" title="Limpar">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <!-- Sem dia selecionado -->
    <div v-if="!day" class="px-4 py-10 text-center">
      <p class="text-sm text-dark-400 font-medium">Selecione um dia</p>
      <p class="text-xs text-dark-600 mt-1">Passe o mouse ou clique em um dia para ver as tarefas.</p>
    </div>

    <!-- Dia sem tarefas -->
    <div v-else-if="!tasks.length" class="px-4 py-10 text-center">
      <p class="text-sm text-dark-400 font-medium">Nenhuma tarefa neste dia</p>
    </div>

    <!-- Lista de tarefas do dia -->
    <div v-else class="divide-y divide-dark-700/60 overflow-y-auto max-h-[60vh]">
      <button v-for="t in tasks" :key="t.id" type="button"
        @click="emit('openTask', t)"
        class="w-full text-left px-4 py-3 hover:bg-dark-700/40 transition-colors block">
        <div class="flex items-start gap-2.5">
          <span :class="['w-1.5 h-1.5 rounded-full mt-1.5 flex-shrink-0', DOT_CLASSES[color(t)]]" />
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-dark-100 leading-snug">{{ t.title }}</p>

            <div class="mt-1.5 space-y-1 text-xs">
              <div class="flex items-center gap-1.5 text-accent-400">
                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z" />
                </svg>
                <span class="truncate">{{ t.project.name }}</span>
              </div>

              <div class="flex items-center gap-1.5 text-dark-400">
                <UserAvatar :user="t.assignee" class="w-4 h-4 rounded-full text-[8px]" />
                <span class="truncate">{{ t.assignee?.name ?? 'Sem responsável' }}</span>
              </div>

              <div class="flex items-center gap-2 pt-0.5">
                <span class="text-dark-400">{{ statusLabel(t.status) }}</span>
                <span class="text-dark-600">·</span>
                <span :class="['px-1.5 py-0.5 rounded-full font-medium', priorityClass(t.priority)]">
                  {{ priorityLabel(t.priority) }}
                </span>
              </div>

              <div class="flex items-center gap-1.5" :class="t.is_overdue ? 'text-red-400' : 'text-dark-500'">
                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>Entrega: {{ t.due_date ? formatBr(t.due_date) : '—' }}</span>
              </div>
            </div>
          </div>
        </div>
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { CalendarTask } from '@/types'
import { DOT_CLASSES, taskColor, parseYmd } from '@/composables/useCalendar'
import UserAvatar from '@/components/ui/UserAvatar.vue'

const props = defineProps<{
  day: string | null
  tasks: CalendarTask[]
}>()

const emit = defineEmits<{
  openTask: [task: CalendarTask]
  close: []
}>()

const color = (t: CalendarTask) => taskColor(t)

const dayTitle = computed(() => {
  if (!props.day) return 'Detalhes do dia'
  return new Intl.DateTimeFormat('pt-BR', { weekday: 'long', day: 'numeric', month: 'long' }).format(parseYmd(props.day))
})

function formatBr(s: string) {
  const [y, m, d] = s.split('-')
  return `${d}/${m}/${y}`
}

const STATUS_LABEL: Record<string, string> = {
  backlog: 'Backlog', pending: 'Pendente', in_progress: 'Em andamento', review: 'Revisão', done: 'Concluída',
}
function statusLabel(s: string) { return STATUS_LABEL[s] ?? s }

const PRIORITY: Record<string, { l: string; c: string }> = {
  low:    { l: 'Baixa',   c: 'bg-slate-500/15 text-slate-300' },
  medium: { l: 'Média',   c: 'bg-yellow-500/15 text-yellow-300' },
  high:   { l: 'Alta',    c: 'bg-orange-500/15 text-orange-300' },
  urgent: { l: 'Urgente', c: 'bg-red-500/15 text-red-300' },
}
function priorityLabel(p: string) { return PRIORITY[p]?.l ?? p }
function priorityClass(p: string) { return PRIORITY[p]?.c ?? 'bg-dark-700 text-dark-300' }
</script>
