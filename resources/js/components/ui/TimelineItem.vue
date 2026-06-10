<template>
  <div class="flex items-center gap-4">
    <div class="w-32 text-sm text-dark-400 flex-shrink-0">{{ label }}</div>
    <div class="flex-1 h-2 bg-dark-700 rounded-full overflow-hidden">
      <div class="h-full rounded-full transition-all duration-700"
        :class="barColor"
        :style="{ width: progress + '%' }" />
    </div>
    <div class="w-16 text-right">
      <span class="text-sm font-semibold text-white">{{ progress }}%</span>
      <span class="text-xs text-dark-500 ml-1">({{ count }})</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{ label: string; progress: number; count: number; total: number }>()

const barColor = computed(() => {
  const m: Record<string, string> = {
    'Backlog': 'bg-slate-500',
    'Em andamento': 'bg-accent-500',
    'Revisão': 'bg-purple-500',
    'Concluída': 'bg-emerald-500',
  }
  return m[props.label] ?? 'bg-accent-500'
})
</script>
