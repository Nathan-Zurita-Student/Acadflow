<template>
  <div class="card card-glow relative flex items-start gap-4 overflow-hidden">
    <!-- Glow de canto na cor do card -->
    <span class="pointer-events-none absolute -right-8 -top-8 h-28 w-28 rounded-full blur-2xl" :style="glowStyle" aria-hidden="true" />

    <div class="relative flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl ring-1" :class="tile.wrap">
      <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="iconPath" />
      </svg>
    </div>

    <div class="relative min-w-0">
      <p class="text-2xl font-bold tabular-nums text-white">{{ display }}</p>
      <p class="mt-0.5 text-xs text-dark-400">{{ label }}</p>
      <p v-if="sub" class="mt-0.5 text-xs text-dark-500">{{ sub }}</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'

const props = defineProps<{
  label: string
  value: number | string
  icon: string
  color: 'indigo' | 'blue' | 'green' | 'red' | 'amber'
  sub?: string
}>()

const colors = {
  indigo: { wrap: 'bg-accent-500/15 text-accent-300 ring-accent-500/25', glow: 'rgb(var(--accent-500) / 0.22)' },
  blue:   { wrap: 'bg-blue-500/15 text-blue-300 ring-blue-500/25',       glow: 'rgb(59 130 246 / 0.22)' },
  green:  { wrap: 'bg-emerald-500/15 text-emerald-300 ring-emerald-500/25', glow: 'rgb(16 185 129 / 0.22)' },
  red:    { wrap: 'bg-red-500/15 text-red-300 ring-red-500/25',           glow: 'rgb(239 68 68 / 0.22)' },
  amber:  { wrap: 'bg-amber-500/15 text-amber-300 ring-amber-500/25',     glow: 'rgb(245 158 11 / 0.22)' },
}

const tile = computed(() => colors[props.color])
const glowStyle = computed(() => ({ background: `radial-gradient(circle, ${colors[props.color].glow}, transparent 70%)` }))

// Count-up animado dos valores numéricos.
const display = ref<number | string>(props.value)

function animateTo(target: number) {
  const reduce = typeof window !== 'undefined' && window.matchMedia?.('(prefers-reduced-motion: reduce)').matches
  if (reduce || target <= 0) { display.value = target; return }
  const dur = 900
  const start = performance.now()
  const from = typeof display.value === 'number' ? display.value : 0
  const tick = (now: number) => {
    const p = Math.min(1, (now - start) / dur)
    const eased = 1 - Math.pow(1 - p, 3)
    display.value = Math.round(from + (target - from) * eased)
    if (p < 1) requestAnimationFrame(tick)
  }
  requestAnimationFrame(tick)
}

onMounted(() => {
  if (typeof props.value === 'number') { display.value = 0; animateTo(props.value) }
})

watch(() => props.value, (v) => {
  if (typeof v === 'number') animateTo(v)
  else display.value = v
})

const icons: Record<string, string> = {
  folder: 'M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z',
  task: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
  check: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
  warning: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
  users: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
}
const iconPath = computed(() => icons[props.icon] ?? icons.task)
</script>
