<template>
  <div class="card flex items-start gap-4">
    <div :class="['w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0', bgColor]">
      <svg class="w-5 h-5" :class="iconColor" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="iconPath" />
      </svg>
    </div>
    <div>
      <p class="text-2xl font-bold text-white">{{ value }}</p>
      <p class="text-xs text-dark-400 mt-0.5">{{ label }}</p>
      <p v-if="sub" class="text-xs text-dark-500 mt-0.5">{{ sub }}</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  label: string
  value: number | string
  icon: string
  color: 'indigo' | 'blue' | 'green' | 'red' | 'amber'
  sub?: string
}>()

const colors = {
  indigo: { bg: 'bg-accent-600/20', icon: 'text-accent-400' },
  blue: { bg: 'bg-blue-600/20', icon: 'text-blue-400' },
  green: { bg: 'bg-emerald-600/20', icon: 'text-emerald-400' },
  red: { bg: 'bg-red-600/20', icon: 'text-red-400' },
  amber: { bg: 'bg-amber-600/20', icon: 'text-amber-400' },
}

const bgColor = computed(() => colors[props.color].bg)
const iconColor = computed(() => colors[props.color].icon)

const icons: Record<string, string> = {
  folder: 'M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z',
  task: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
  check: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
  warning: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
  users: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
}

const iconPath = computed(() => icons[props.icon] ?? icons.task)
</script>
