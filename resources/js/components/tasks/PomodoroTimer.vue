<template>
  <div class="p-6 space-y-6">
    <!-- Cycle indicator -->
    <div class="flex justify-center gap-1.5">
      <div
        v-for="i in 4" :key="i"
        class="w-2.5 h-2.5 rounded-full transition-all"
        :class="i <= completedCycles % 4
          ? 'bg-accent-500 scale-110'
          : 'bg-dark-700'"
      />
    </div>

    <!-- Phase label -->
    <div class="text-center">
      <span class="text-xs font-semibold uppercase tracking-widest px-3 py-1 rounded-full"
        :class="phaseStyle.badge">
        {{ phaseLabel }}
      </span>
    </div>

    <!-- Timer circle -->
    <div class="flex justify-center">
      <div class="relative w-44 h-44">
        <svg class="w-full h-full -rotate-90" viewBox="0 0 100 100">
          <!-- Background ring -->
          <circle cx="50" cy="50" r="45" fill="none" stroke="currentColor"
            class="text-dark-700" stroke-width="6" />
          <!-- Progress ring -->
          <circle cx="50" cy="50" r="45" fill="none"
            :stroke="phaseStyle.stroke"
            stroke-width="6"
            stroke-linecap="round"
            :stroke-dasharray="`${2 * Math.PI * 45}`"
            :stroke-dashoffset="`${2 * Math.PI * 45 * (1 - progress)}`"
            class="transition-all duration-1000"
          />
        </svg>
        <!-- Time display -->
        <div class="absolute inset-0 flex flex-col items-center justify-center">
          <span class="text-4xl font-bold tabular-nums" :class="phaseStyle.text">{{ display }}</span>
          <span class="text-xs text-dark-500 mt-1">{{ remaining }} seg restantes</span>
        </div>
      </div>
    </div>

    <!-- Controls -->
    <div class="flex items-center justify-center gap-3">
      <button @click="reset"
        class="w-10 h-10 rounded-full border border-dark-700 hover:border-dark-600 text-dark-500 hover:text-dark-300 flex items-center justify-center transition-colors"
        title="Resetar">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
      </button>

      <button @click="toggle"
        class="w-16 h-16 rounded-full flex items-center justify-center text-white font-semibold transition-all shadow-lg active:scale-95"
        :class="running ? 'bg-dark-700 hover:bg-dark-600' : phaseStyle.button">
        <svg v-if="!running" class="w-6 h-6 ml-0.5" fill="currentColor" viewBox="0 0 24 24">
          <path d="M8 5v14l11-7z" />
        </svg>
        <svg v-else class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
          <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z" />
        </svg>
      </button>

      <button @click="skip"
        class="w-10 h-10 rounded-full border border-dark-700 hover:border-dark-600 text-dark-500 hover:text-dark-300 flex items-center justify-center transition-colors"
        title="Pular fase">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M6 5l7 7-7 7" />
        </svg>
      </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 gap-3">
      <div class="bg-dark-700/40 rounded-xl px-4 py-3 text-center">
        <p class="text-2xl font-bold text-dark-100">{{ completedCycles }}</p>
        <p class="text-xs text-dark-500 mt-0.5">Sessões concluídas</p>
      </div>
      <div class="bg-dark-700/40 rounded-xl px-4 py-3 text-center">
        <p class="text-2xl font-bold text-dark-100">{{ formatMin(completedCycles * WORK_DURATION) }}</p>
        <p class="text-xs text-dark-500 mt-0.5">Minutos focados</p>
      </div>
    </div>

    <!-- Sound toggle -->
    <div class="flex items-center justify-center gap-2">
      <button @click="soundEnabled = !soundEnabled"
        class="flex items-center gap-2 text-xs text-dark-500 hover:text-dark-300 transition-colors">
        <svg v-if="soundEnabled" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M15.536 8.464a5 5 0 010 7.072M12 6v12m0 0l-3-3m3 3l3-3M9.172 9.172a4 4 0 000 5.656" />
        </svg>
        <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
        </svg>
        {{ soundEnabled ? 'Som ativado' : 'Som desativado' }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onUnmounted, watch } from 'vue'
import { timeApi } from '@/api/projects'

const props = defineProps<{
  projectId: number
  taskId: number
}>()

// Durations in seconds
const WORK_DURATION       = 25 * 60
const SHORT_BREAK         = 5  * 60
const LONG_BREAK          = 15 * 60
const SESSIONS_BEFORE_LONG = 4

type Phase = 'work' | 'short-break' | 'long-break'

const phase           = ref<Phase>('work')
const remaining       = ref(WORK_DURATION)
const running         = ref(false)
const completedCycles = ref(0)
const soundEnabled    = ref(true)
let ticker: ReturnType<typeof setInterval> | null = null

const progress = computed(() => {
  const total = phase.value === 'work'
    ? WORK_DURATION
    : phase.value === 'short-break' ? SHORT_BREAK : LONG_BREAK
  return 1 - remaining.value / total
})

const display = computed(() => {
  const m = Math.floor(remaining.value / 60).toString().padStart(2, '0')
  const s = (remaining.value % 60).toString().padStart(2, '0')
  return `${m}:${s}`
})

const phaseLabel = computed(() => ({
  'work':        'Em foco',
  'short-break': 'Pausa curta',
  'long-break':  'Pausa longa',
}[phase.value]))

const phaseStyle = computed(() => ({
  'work':        { badge: 'bg-red-500/15 text-red-400',    text: 'text-red-400',    stroke: '#f87171', button: 'bg-red-500 hover:bg-red-400' },
  'short-break': { badge: 'bg-teal-500/15 text-teal-400',  text: 'text-teal-400',  stroke: '#2dd4bf', button: 'bg-teal-500 hover:bg-teal-400' },
  'long-break':  { badge: 'bg-green-500/15 text-green-400', text: 'text-green-400', stroke: '#4ade80', button: 'bg-green-500 hover:bg-green-400' },
}[phase.value]))

function toggle() {
  running.value ? pause() : start()
}

function start() {
  running.value = true
  ticker = setInterval(tick, 1000)
}

function pause() {
  running.value = false
  if (ticker) { clearInterval(ticker); ticker = null }
}

async function tick() {
  remaining.value--
  if (remaining.value <= 0) {
    await onPhaseEnd()
  }
}

async function onPhaseEnd() {
  pause()
  playSound()

  if (phase.value === 'work') {
    completedCycles.value++
    // Log worked time to the task
    try {
      await timeApi.log(props.projectId, props.taskId, WORK_DURATION)
    } catch {}

    if (completedCycles.value % SESSIONS_BEFORE_LONG === 0) {
      nextPhase('long-break')
    } else {
      nextPhase('short-break')
    }
  } else {
    nextPhase('work')
  }
}

function nextPhase(p: Phase) {
  phase.value = p
  remaining.value = p === 'work' ? WORK_DURATION : p === 'short-break' ? SHORT_BREAK : LONG_BREAK
}

function skip() {
  pause()
  if (phase.value === 'work') {
    // Skip without logging time
    if (completedCycles.value % SESSIONS_BEFORE_LONG === SESSIONS_BEFORE_LONG - 1) {
      nextPhase('long-break')
    } else {
      nextPhase('short-break')
    }
  } else {
    nextPhase('work')
  }
}

function reset() {
  pause()
  phase.value = 'work'
  remaining.value = WORK_DURATION
}

function playSound() {
  if (!soundEnabled.value) return
  try {
    const ctx = new AudioContext()
    const osc = ctx.createOscillator()
    const gain = ctx.createGain()
    osc.connect(gain)
    gain.connect(ctx.destination)
    osc.frequency.value = phase.value === 'work' ? 440 : 528
    gain.gain.setValueAtTime(0.3, ctx.currentTime)
    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.8)
    osc.start()
    osc.stop(ctx.currentTime + 0.8)
  } catch {}
}

function formatMin(seconds: number) {
  return Math.floor(seconds / 60)
}

onUnmounted(() => { if (ticker) clearInterval(ticker) })
</script>
