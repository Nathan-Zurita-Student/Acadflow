<template>
  <span :title="label" :class="['w-2 h-2 rounded-full flex-shrink-0', color]" />
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{ priority: string }>()
const map: Record<string, { color: string; label: string }> = {
  low: { color: 'bg-slate-500', label: 'Baixa' },
  medium: { color: 'bg-blue-500', label: 'Média' },
  high: { color: 'bg-amber-500', label: 'Alta' },
  urgent: { color: 'bg-red-500', label: 'Urgente' },
}
const color = computed(() => map[props.priority]?.color ?? 'bg-dark-500')
const label = computed(() => map[props.priority]?.label ?? props.priority)
</script>
