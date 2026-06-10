<template>
  <div class="relative" ref="containerRef">
    <!-- Botão sino -->
    <button
      @click="toggle"
      class="relative p-2 rounded-lg hover:bg-dark-700 text-dark-400 hover:text-dark-200 transition-colors"
      title="Notificações"
    >
      <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
      </svg>
      <span
        v-if="store.hasUnread"
        class="absolute top-1 right-1 w-4 h-4 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center leading-none"
      >{{ store.unread > 9 ? '9+' : store.unread }}</span>
    </button>

    <!-- Dropdown -->
    <Transition
      enter-active-class="transition-all duration-150 ease-out"
      enter-from-class="opacity-0 scale-95 translate-y-1"
      enter-to-class="opacity-100 scale-100 translate-y-0"
      leave-active-class="transition-all duration-100 ease-in"
      leave-from-class="opacity-100 scale-100 translate-y-0"
      leave-to-class="opacity-0 scale-95 translate-y-1"
    >
      <div
        v-if="open"
        class="absolute right-0 top-full mt-2 w-80 bg-dark-800 border border-dark-700 rounded-2xl shadow-2xl z-50 overflow-hidden origin-top-right"
      >
        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 border-b border-dark-700">
          <h3 class="text-sm font-semibold text-white">Notificações</h3>
          <button
            v-if="store.hasUnread"
            @click="store.markAllRead()"
            class="text-xs text-accent-400 hover:text-accent-300 transition-colors"
          >Marcar todas como lidas</button>
        </div>

        <!-- Lista -->
        <div class="max-h-80 overflow-y-auto">
          <div v-if="!store.items.length" class="px-4 py-8 text-center">
            <svg class="w-8 h-8 text-dark-600 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            <p class="text-sm text-dark-500">Nenhuma notificação</p>
          </div>

          <div
            v-for="n in store.items" :key="n.id"
            @click="handleClick(n)"
            class="flex items-start gap-3 px-4 py-3 hover:bg-dark-700/60 cursor-pointer transition-colors border-b border-dark-700/40 last:border-0"
            :class="{ 'bg-accent-500/5': !n.read_at }"
          >
            <!-- Ícone por tipo -->
            <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 text-base mt-0.5"
              :class="iconBg(n.type)">
              {{ typeEmoji(n.type) }}
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-xs font-semibold text-dark-100 leading-tight">{{ n.title }}</p>
              <p class="text-xs text-dark-400 mt-0.5 leading-relaxed line-clamp-2">{{ n.message }}</p>
              <p class="text-[10px] text-dark-600 mt-1">{{ timeAgo(n.created_at) }}</p>
            </div>
            <div v-if="!n.read_at" class="w-2 h-2 rounded-full bg-accent-500 flex-shrink-0 mt-1.5" />
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { onClickOutside } from '@vueuse/core'
import { useNotificationsStore } from '@/stores/notifications'
import { usePolling } from '@/composables/usePolling'
import type { AppNotification } from '@/api/notifications'

const store  = useNotificationsStore()
const router = useRouter()
const open   = ref(false)
const containerRef = ref<HTMLElement | null>(null)

// Fallback polling every 60s — real-time handled by Reverb WebSocket
usePolling(() => store.fetch(), 60_000)

onClickOutside(containerRef, () => { open.value = false })

function toggle() { open.value = !open.value }

function handleClick(n: AppNotification) {
  if (!n.read_at) store.markRead(n.id)
  if (n.data?.project_id) {
    const base = `/projects/${n.data.project_id}`
    router.push(n.data.task_id ? `${base}/kanban` : base)
  }
  open.value = false
}

function typeEmoji(type: string) {
  const map: Record<string, string> = {
    task_comment: '💬', task_assigned: '📌', task_approved: '✅',
    task_rejected: '❌', file_uploaded: '📎', project_member_added: '🎓',
    meeting_scheduled: '📅',
  }
  return map[type] ?? '🔔'
}

function iconBg(type: string) {
  if (type === 'task_approved') return 'bg-emerald-500/15'
  if (type === 'task_rejected') return 'bg-red-500/15'
  if (type === 'task_comment') return 'bg-blue-500/15'
  if (type === 'file_uploaded') return 'bg-purple-500/15'
  if (type === 'meeting_scheduled') return 'bg-teal-500/15'
  return 'bg-accent-500/15'
}

function timeAgo(date: string) {
  const diff = (Date.now() - new Date(date).getTime()) / 1000
  if (diff < 60) return 'agora'
  if (diff < 3600) return `${Math.floor(diff / 60)}m atrás`
  if (diff < 86400) return `${Math.floor(diff / 3600)}h atrás`
  return `${Math.floor(diff / 86400)}d atrás`
}
</script>
