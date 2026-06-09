<template>
  <div class="card hover:border-dark-600 hover:bg-dark-750 cursor-pointer transition-all duration-200 group"
    @click="$emit('click')">
    <div class="flex items-start justify-between mb-3">
      <div class="flex-1 min-w-0">
        <h3 class="font-semibold text-dark-100 group-hover:text-white transition-colors truncate">
          {{ project.name }}
        </h3>
        <p v-if="project.category" class="text-xs text-dark-500 mt-0.5">{{ project.category }}</p>
      </div>
      <div class="flex items-center gap-2 ml-2">
        <StatusBadge :status="project.status" />
        <button
          @click.stop="$emit('delete')"
          class="opacity-0 group-hover:opacity-100 p-1 rounded hover:bg-red-600/20 text-dark-500 hover:text-red-400 transition-all">
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
          </svg>
        </button>
      </div>
    </div>

    <p v-if="project.description" class="text-sm text-dark-400 line-clamp-2 mb-3">{{ project.description }}</p>

    <!-- Progress -->
    <div class="mb-3">
      <div class="flex items-center justify-between mb-1.5">
        <span class="text-xs text-dark-500">Progresso</span>
        <span class="text-xs font-medium text-dark-300">{{ project.progress }}%</span>
      </div>
      <div class="h-1.5 bg-dark-700 rounded-full overflow-hidden">
        <div class="h-full rounded-full transition-all duration-500"
          :class="progressColor"
          :style="{ width: project.progress + '%' }" />
      </div>
    </div>

    <!-- Footer -->
    <div class="flex items-center justify-between pt-2 border-t border-dark-700/60">
      <div class="flex items-center gap-1.5">
        <svg class="w-3.5 h-3.5 text-dark-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
        </svg>
        <span class="text-xs text-dark-500">{{ project.tasks_count }} tarefas</span>
      </div>

      <div class="flex items-center gap-1.5">
        <svg class="w-3.5 h-3.5 text-dark-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <span class="text-xs text-dark-500">{{ deadline }}</span>
      </div>

      <!-- Members avatars -->
      <div class="flex -space-x-1.5">
        <div v-for="m in project.members.slice(0, 3)" :key="m.id"
          :title="m.name"
          class="w-6 h-6 rounded-full bg-indigo-600/40 border-2 border-dark-800 flex items-center justify-center text-xs font-medium text-indigo-300">
          {{ m.name[0] }}
        </div>
        <div v-if="project.members.length > 3"
          class="w-6 h-6 rounded-full bg-dark-600 border-2 border-dark-800 flex items-center justify-center text-xs text-dark-400">
          +{{ project.members.length - 3 }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { Project } from '@/types'
import StatusBadge from './StatusBadge.vue'

const props = defineProps<{ project: Project }>()
defineEmits(['click', 'delete'])

const progressColor = computed(() => {
  if (props.project.progress >= 75) return 'bg-emerald-500'
  if (props.project.progress >= 40) return 'bg-indigo-500'
  return 'bg-amber-500'
})

const deadline = computed(() => {
  if (!props.project.deadline) return 'Sem prazo'
  return new Date(props.project.deadline + 'T00:00:00').toLocaleDateString('pt-BR', { day: 'numeric', month: 'short' })
})
</script>
