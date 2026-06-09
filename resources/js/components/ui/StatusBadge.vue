<template>
  <span :class="['badge', classes]">{{ label }}</span>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{ status: string; type?: 'project' | 'task' | 'priority' }>()

const projectStatuses: Record<string, { cls: string; label: string }> = {
  planning: { cls: 'bg-slate-600/30 text-slate-300', label: 'Planejamento' },
  active: { cls: 'bg-emerald-600/20 text-emerald-400', label: 'Ativo' },
  paused: { cls: 'bg-amber-600/20 text-amber-400', label: 'Pausado' },
  completed: { cls: 'bg-blue-600/20 text-blue-400', label: 'Concluído' },
  cancelled: { cls: 'bg-red-600/20 text-red-400', label: 'Cancelado' },
}

const taskStatuses: Record<string, { cls: string; label: string }> = {
  backlog: { cls: 'bg-slate-600/30 text-slate-400', label: 'Backlog' },
  pending: { cls: 'bg-yellow-600/20 text-yellow-400', label: 'Pendente' },
  in_progress: { cls: 'bg-blue-600/20 text-blue-400', label: 'Em andamento' },
  review: { cls: 'bg-purple-600/20 text-purple-400', label: 'Revisão' },
  done: { cls: 'bg-emerald-600/20 text-emerald-400', label: 'Concluída' },
}

const priorityMap: Record<string, { cls: string; label: string }> = {
  low: { cls: 'bg-slate-600/30 text-slate-400', label: 'Baixa' },
  medium: { cls: 'bg-blue-600/20 text-blue-400', label: 'Média' },
  high: { cls: 'bg-amber-600/20 text-amber-400', label: 'Alta' },
  urgent: { cls: 'bg-red-600/20 text-red-400', label: 'Urgente' },
}

const map = computed(() =>
  props.type === 'task' ? taskStatuses
    : props.type === 'priority' ? priorityMap
    : projectStatuses
)

const current = computed(() => map.value[props.status] ?? { cls: 'bg-dark-600/30 text-dark-400', label: props.status })
const classes = computed(() => current.value.cls)
const label = computed(() => current.value.label)
</script>
