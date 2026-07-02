<template>
  <div class="flex items-center gap-3 p-2.5 rounded-lg hover:bg-dark-700/50 transition-colors">
    <UserAvatar :user="stat.user" class="w-8 h-8 rounded-full bg-accent-600/30 text-sm font-semibold text-accent-300 flex-shrink-0" />
    <div class="flex-1 min-w-0">
      <p class="text-sm font-medium text-dark-100 truncate">{{ stat.user.name }}</p>
      <p class="text-xs text-dark-500">{{ stat.completed_tasks }}/{{ stat.total_tasks }} tarefas</p>
    </div>
    <div class="flex items-center gap-2">
      <div class="text-right">
        <div class="w-16 h-1.5 bg-dark-700 rounded-full overflow-hidden">
          <div class="h-full rounded-full transition-all"
            :class="scoreColor"
            :style="{ width: stat.score + '%' }" />
        </div>
        <p class="text-xs text-dark-500 mt-0.5">{{ stat.participation }}%</p>
      </div>
      <span v-if="isLeader"
        class="px-2 py-1 rounded-lg text-xs font-semibold bg-amber-500/15 text-amber-400 whitespace-nowrap">
        Líder
      </span>
      <span v-else :class="['w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold', gradeClass]">
        {{ stat.grade }}
      </span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { MemberStats } from '@/types'
import UserAvatar from './UserAvatar.vue'

const props = withDefaults(defineProps<{ stat: MemberStats; isLeader?: boolean }>(), {
  isLeader: false,
})

const scoreColor = computed(() => {
  if (props.stat.score >= 85) return 'bg-emerald-500'
  if (props.stat.score >= 70) return 'bg-blue-500'
  if (props.stat.score >= 50) return 'bg-amber-500'
  return 'bg-red-500'
})

const gradeClass = computed(() => {
  const m: Record<string, string> = { A: 'bg-emerald-600/20 text-emerald-400', B: 'bg-blue-600/20 text-blue-400', C: 'bg-amber-600/20 text-amber-400', D: 'bg-red-600/20 text-red-400' }
  return m[props.stat.grade] ?? 'bg-dark-700 text-dark-400'
})
</script>
