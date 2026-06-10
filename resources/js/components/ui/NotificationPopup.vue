<template>
  <Teleport to="body">
    <div class="fixed top-16 right-4 z-[200] flex flex-col gap-2 pointer-events-none" style="max-width: 340px; width: calc(100vw - 32px);">
      <TransitionGroup name="popup">
        <div
          v-for="popup in popups"
          :key="popup.id"
          class="pointer-events-auto bg-dark-800 border border-dark-700 rounded-2xl shadow-2xl overflow-hidden cursor-pointer group"
          @click="navigate(popup)"
        >
          <!-- Progress bar -->
          <div class="h-0.5 bg-dark-700">
            <div
              class="h-full bg-indigo-500 transition-none"
              :style="{ width: popup.progress + '%', transition: `width ${DURATION}ms linear` }"
            />
          </div>

          <div class="flex items-start gap-3 p-4">
            <!-- Icon -->
            <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0 text-base"
              :class="typeStyle(popup.type).bg">
              {{ typeStyle(popup.type).icon }}
            </div>

            <!-- Content -->
            <div class="flex-1 min-w-0 pr-1">
              <p class="text-sm font-semibold text-dark-100 leading-tight">{{ popup.title }}</p>
              <p class="text-xs text-dark-400 mt-0.5 leading-relaxed line-clamp-2">{{ popup.message }}</p>
            </div>

            <!-- Close -->
            <button
              @click.stop="dismiss(popup.id)"
              class="flex-shrink-0 p-1 rounded-lg text-dark-500 hover:text-dark-300 hover:bg-dark-700 transition-colors opacity-0 group-hover:opacity-100"
            >
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'

export interface PopupItem {
  id: number
  type: string
  title: string
  message: string
  data: Record<string, unknown> | null
  progress: number
  timer: ReturnType<typeof setTimeout> | null
  interval: ReturnType<typeof setInterval> | null
}

const DURATION = 6000  // ms before auto-dismiss
const MAX      = 3     // max stacked popups

const router = useRouter()
const popups = ref<PopupItem[]>([])
let nextId = 0

function typeStyle(type: string) {
  const map: Record<string, { icon: string; bg: string }> = {
    task_comment:       { icon: '💬', bg: 'bg-blue-500/15' },
    task_assigned:      { icon: '📌', bg: 'bg-indigo-500/15' },
    task_approved:      { icon: '✅', bg: 'bg-emerald-500/15' },
    task_rejected:      { icon: '❌', bg: 'bg-red-500/15' },
    file_uploaded:      { icon: '📎', bg: 'bg-purple-500/15' },
    project_member_added: { icon: '🎓', bg: 'bg-amber-500/15' },
    meeting_scheduled:  { icon: '📅', bg: 'bg-teal-500/15' },
  }
  return map[type] ?? { icon: '🔔', bg: 'bg-dark-700' }
}

function push(payload: { type: string; title: string; message: string; data: Record<string, unknown> | null }) {
  if (popups.value.length >= MAX) {
    const oldest = popups.value[0]
    clearPopupTimers(oldest)
    popups.value.shift()
  }

  const id = ++nextId
  const item: PopupItem = {
    id,
    type: payload.type,
    title: payload.title,
    message: payload.message,
    data: payload.data,
    progress: 100,
    timer: null,
    interval: null,
  }

  popups.value.push(item)

  // Animate progress bar down
  const startTime = Date.now()
  item.interval = setInterval(() => {
    const elapsed = Date.now() - startTime
    item.progress = Math.max(0, 100 - (elapsed / DURATION) * 100)
  }, 50)

  item.timer = setTimeout(() => dismiss(id), DURATION)
}

function dismiss(id: number) {
  const idx = popups.value.findIndex(p => p.id === id)
  if (idx === -1) return
  clearPopupTimers(popups.value[idx])
  popups.value.splice(idx, 1)
}

function clearPopupTimers(item: PopupItem) {
  if (item.timer)    clearTimeout(item.timer)
  if (item.interval) clearInterval(item.interval)
}

function navigate(popup: PopupItem) {
  dismiss(popup.id)
  const d = popup.data as any
  if (d?.task_id && d?.project_id) {
    router.push(`/projects/${d.project_id}/kanban`)
  } else if (d?.project_id) {
    router.push(`/projects/${d.project_id}`)
  }
}

defineExpose({ push })
</script>

<style scoped>
.popup-enter-active {
  animation: popupIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.popup-leave-active {
  animation: popupOut 0.2s ease-in forwards;
}
.popup-move {
  transition: transform 0.25s ease;
}
@keyframes popupIn {
  from { opacity: 0; transform: translateX(100%) scale(0.9); }
  to   { opacity: 1; transform: translateX(0) scale(1); }
}
@keyframes popupOut {
  from { opacity: 1; transform: translateX(0) scale(1); }
  to   { opacity: 0; transform: translateX(100%) scale(0.9); }
}
</style>
