<template>
  <Teleport to="body">
    <div
      class="fixed bottom-5 right-5 z-[300] flex flex-col gap-3 pointer-events-none"
      style="width: 380px; max-width: calc(100vw - 40px)"
    >
      <TransitionGroup name="notif">
        <div
          v-for="item in items"
          :key="item.id"
          class="pointer-events-auto rounded-2xl overflow-hidden cursor-pointer shadow-2xl"
          :class="['notif-card', { 'notif-card--hovered': item.paused }]"
          @click="navigate(item)"
          @mouseenter="pauseItem(item)"
          @mouseleave="resumeItem(item)"
        >
          <!-- Top accent line by type -->
          <div class="h-0.5" :class="typeStyle(item.type).accent" />

          <div class="flex items-start gap-3.5 px-4 pt-3.5 pb-3">
            <!-- Icon -->
            <div
              class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 text-xl mt-0.5"
              :class="typeStyle(item.type).bg"
            >
              {{ typeStyle(item.type).icon }}
            </div>

            <!-- Content -->
            <div class="flex-1 min-w-0">
              <div class="flex items-start justify-between gap-2">
                <p class="text-sm font-semibold text-white leading-snug">{{ item.title }}</p>
                <!-- Close -->
                <button
                  @click.stop="dismiss(item.id)"
                  class="flex-shrink-0 -mt-0.5 p-1 rounded-lg text-dark-500 hover:text-dark-200 hover:bg-white/10 transition-colors"
                  title="Fechar"
                >
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
              <p class="text-xs text-dark-400 mt-0.5 leading-relaxed line-clamp-2">{{ item.message }}</p>
              <div class="flex items-center gap-1.5 mt-2">
                <svg class="w-3 h-3 text-dark-600 flex-shrink-0" viewBox="0 0 32 32" fill="none">
                  <path d="M16 6L2 13l14 7 14-7-14-7z" fill="currentColor" fill-opacity="0.9"/>
                  <path d="M8 17v5c0 0 3 2.5 8 2.5s8-2.5 8-2.5v-5l-8 4-8-4z" fill="currentColor" fill-opacity="0.7"/>
                </svg>
                <span class="text-[11px] text-dark-600 font-medium">AcadFlow</span>
              </div>
            </div>
          </div>

          <!-- Progress bar -->
          <div class="h-[3px] bg-white/5 mx-3 mb-3 rounded-full overflow-hidden">
            <div
              class="h-full rounded-full progress-fill"
              :class="[typeStyle(item.type).bar, { paused: item.paused }]"
              :style="{ '--duration': `${DURATION}ms` }"
            />
          </div>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'

export interface PushPayload {
  type: string
  title: string
  message: string
  data?: Record<string, unknown> | null
}

interface PopupItem {
  id: number
  type: string
  title: string
  message: string
  data: Record<string, unknown> | null
  paused: boolean
  startTime: number
  remaining: number
  timer: ReturnType<typeof setTimeout> | null
}

const DURATION = 6000
const MAX      = 4

const router = useRouter()
const items  = ref<PopupItem[]>([])
let   nextId = 0

function typeStyle(type: string) {
  const map: Record<string, { icon: string; bg: string; accent: string; bar: string }> = {
    task_comment:          { icon: '💬', bg: 'bg-blue-500/15',    accent: 'bg-blue-500',    bar: 'bg-blue-500' },
    task_assigned:         { icon: '📌', bg: 'bg-accent-500/15',  accent: 'bg-accent-500',  bar: 'bg-accent-500' },
    task_approved:         { icon: '✅', bg: 'bg-emerald-500/15', accent: 'bg-emerald-500', bar: 'bg-emerald-500' },
    task_rejected:         { icon: '❌', bg: 'bg-red-500/15',     accent: 'bg-red-500',     bar: 'bg-red-500' },
    file_uploaded:         { icon: '📎', bg: 'bg-purple-500/15',  accent: 'bg-purple-500',  bar: 'bg-purple-500' },
    project_member_added:  { icon: '🎓', bg: 'bg-amber-500/15',   accent: 'bg-amber-500',   bar: 'bg-amber-500' },
    meeting_scheduled:     { icon: '📅', bg: 'bg-teal-500/15',    accent: 'bg-teal-500',    bar: 'bg-teal-500' },
  }
  return map[type] ?? { icon: '🔔', bg: 'bg-dark-700', accent: 'bg-accent-500', bar: 'bg-accent-500' }
}

function push(payload: PushPayload) {
  if (items.value.length >= MAX) {
    const oldest = items.value[0]
    clearTimers(oldest)
    items.value.shift()
  }

  const id = ++nextId
  const item: PopupItem = {
    id,
    type:      payload.type,
    title:     payload.title,
    message:   payload.message,
    data:      payload.data ?? null,
    paused:    false,
    startTime: Date.now(),
    remaining: DURATION,
    timer:     null,
  }

  item.timer = setTimeout(() => dismiss(id), DURATION)
  items.value.push(item)
}

function pauseItem(item: PopupItem) {
  if (item.paused || !item.timer) return
  clearTimeout(item.timer)
  item.timer = null
  item.remaining = Math.max(0, item.remaining - (Date.now() - item.startTime))
  item.paused = true
}

function resumeItem(item: PopupItem) {
  if (!item.paused) return
  item.paused    = false
  item.startTime = Date.now()
  item.timer     = setTimeout(() => dismiss(item.id), item.remaining)
}

function dismiss(id: number) {
  const idx = items.value.findIndex(p => p.id === id)
  if (idx === -1) return
  clearTimers(items.value[idx])
  items.value.splice(idx, 1)
}

function clearTimers(item: PopupItem) {
  if (item.timer) clearTimeout(item.timer)
}

function navigate(item: PopupItem) {
  dismiss(item.id)
  const d = item.data
  if (d?.task_id && d?.project_id) router.push(`/projects/${d.project_id}/kanban`)
  else if (d?.project_id) router.push(`/projects/${d.project_id}`)
}

defineExpose({ push })
</script>

<style scoped>
/* Card base */
.notif-card {
  background: rgba(15, 15, 20, 0.96);
  border: 1px solid rgba(255, 255, 255, 0.09);
  backdrop-filter: blur(12px);
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.notif-card--hovered {
  transform: scale(1.015);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
}

/* Progress bar */
.progress-fill {
  width: 100%;
  animation: drain var(--duration, 6000ms) linear forwards;
  transform-origin: left;
}
.progress-fill.paused {
  animation-play-state: paused;
}
@keyframes drain {
  from { width: 100%; }
  to   { width: 0%; }
}

/* TransitionGroup */
.notif-enter-active {
  animation: slideIn 0.35s cubic-bezier(0.34, 1.4, 0.64, 1);
}
.notif-leave-active {
  animation: slideOut 0.22s ease-in forwards;
}
.notif-move {
  transition: transform 0.3s ease;
}

@keyframes slideIn {
  from { opacity: 0; transform: translateX(calc(100% + 20px)); }
  to   { opacity: 1; transform: translateX(0); }
}
@keyframes slideOut {
  from { opacity: 1; transform: translateX(0) scale(1); }
  to   { opacity: 0; transform: translateX(calc(100% + 20px)) scale(0.95); }
}
</style>
