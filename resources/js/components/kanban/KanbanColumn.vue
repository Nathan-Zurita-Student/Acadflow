<template>
  <div class="flex-shrink-0 w-72 flex flex-col">
    <!-- Column header -->
    <div class="flex items-center justify-between mb-3 px-1">
      <div class="flex items-center gap-2">
        <span class="w-2 h-2 rounded-full" :class="dotColor" />
        <h3 class="text-sm font-semibold text-dark-200">{{ column.label }}</h3>
        <span class="text-xs text-dark-500 bg-dark-700 px-1.5 py-0.5 rounded-full">{{ tasks.length }}</span>
        <!-- pending approval badge -->
        <span
          v-if="pendingCount > 0"
          class="inline-flex items-center gap-1 text-xs bg-yellow-500/15 text-yellow-400 px-1.5 py-0.5 rounded-full"
          title="Tarefas aguardando aprovação"
        >{{ pendingCount }} <Icon name="hourglass_top" :size="12" /></span>
      </div>
      <button @click="$emit('quick-add')" class="p-1 rounded hover:bg-dark-700 text-dark-500 hover:text-dark-300 transition-colors">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
      </button>
    </div>

    <!-- Drop zone (vuedraggable) — reordena dentro da coluna e move entre colunas -->
    <draggable
      :list="tasks"
      group="kanban"
      item-key="id"
      :animation="160"
      ghost-class="kanban-ghost"
      filter=".kanban-no-drag, select, button, textarea, input, a, option"
      :prevent-on-filter="false"
      :scroll="true"
      :scrollSensitivity="120"
      :scrollSpeed="18"
      :bubbleScroll="true"
      :forceFallback="true"
      :fallbackTolerance="4"
      :delay="120"
      :delayOnTouchOnly="true"
      :touchStartThreshold="6"
      class="flex-1 bg-dark-900/50 border border-dark-700/60 rounded-xl p-2 space-y-2 transition-colors min-h-[80px]"
      @change="$emit('reorder')"
    >
      <template #item="{ element: task, index }">
        <KanbanCard
          :task="task"
          :index="index"
          :project-id="projectId"
          :is-leader="isLeader"
          :columns="columns"
          @click="$emit('task-click', task)"
          @delete="$emit('task-delete', task)"
          @status-change="(status) => $emit('status-change', task, status)"
          @approval-change="(taskId, status, note) => $emit('approval-change', taskId, status, note)"
        />
      </template>
      <template #footer>
        <div v-if="!tasks.length" class="h-16 flex items-center justify-center">
          <p class="text-xs text-dark-600">Arraste tarefas aqui</p>
        </div>
      </template>
    </draggable>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import draggable from 'vuedraggable'
import type { Task, TaskStatus, KanbanColumnDef } from '@/types'
import KanbanCard from './KanbanCard.vue'
import Icon from '@/components/ui/Icon.vue'

const props = defineProps<{
  column: KanbanColumnDef
  columns: KanbanColumnDef[]
  tasks: Task[]
  projectId: number
  isLeader: boolean
}>()

defineEmits<{
  (e: 'task-click', task: Task): void
  (e: 'quick-add'): void
  (e: 'status-change', task: Task, status: TaskStatus): void
  (e: 'approval-change', taskId: number, status: string, note?: string): void
  (e: 'task-delete', task: Task): void
  (e: 'reorder'): void
}>()

const pendingCount = computed(() => props.tasks.filter(t => t.approval_status === 'pending').length)

const dotColors: Record<string, string> = {
  backlog:     'bg-slate-500',
  pending:     'bg-yellow-500',
  in_progress: 'bg-blue-500',
  review:      'bg-purple-500',
  done:        'bg-emerald-500',
}
// Colunas padrão usam o mapa acima; colunas personalizadas derivam o ponto
// a partir da sua cor (text-cor-400 → bg-cor-500).
const dotColor = computed(() =>
  dotColors[props.column.status]
    ?? props.column.color?.replace('text-', 'bg-').replace(/-\d+$/, '-500')
    ?? 'bg-slate-500'
)
</script>

<style scoped>
.kanban-ghost {
  opacity: 0.45;
}
</style>
