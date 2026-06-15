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
        class="absolute right-0 top-full mt-2 w-80 max-w-[calc(100vw-1.5rem)] bg-dark-800 border border-dark-700 rounded-2xl shadow-2xl z-50 overflow-hidden origin-top-right"
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

        <!-- Pedido de permissão para notificações do navegador -->
        <div v-if="supported && permission !== 'granted'" class="px-4 py-3 border-b border-dark-700 bg-accent-500/5">
          <div class="flex items-start gap-2.5">
            <Icon name="notifications" :size="18" class="text-accent-400 mt-0.5" />
            <div class="flex-1 min-w-0">
              <template v-if="permission === 'default'">
                <p class="text-xs text-dark-100 font-medium">Ativar notificações do navegador</p>
                <p class="text-[11px] text-dark-500 mt-0.5 leading-relaxed">Seja avisado de novas tarefas, comentários e menções mesmo com a aba minimizada.</p>
                <button
                  @click="enableBrowserNotifications"
                  :disabled="requesting"
                  class="mt-2 inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-lg bg-accent-600 hover:bg-accent-500 disabled:opacity-50 text-white transition-colors"
                >
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                      d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                  </svg>
                  Ativar notificações
                </button>
              </template>
              <template v-else>
                <p class="text-xs text-dark-100 font-medium">Notificações bloqueadas</p>
                <p class="text-[11px] text-dark-500 mt-0.5 leading-relaxed">Para reativar, clique no ícone de cadeado na barra de endereço do navegador e permita notificações para este site.</p>
              </template>
            </div>
          </div>
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
            class="flex items-start gap-3 px-4 py-3 border-b border-dark-700/40 last:border-0 transition-colors"
            :class="[
              n.type === 'project_invitation' ? 'cursor-default' : 'hover:bg-dark-700/60 cursor-pointer',
              { 'bg-accent-500/5': !n.read_at }
            ]"
            @click="n.type !== 'project_invitation' && handleClick(n)"
          >
            <!-- Ícone por tipo -->
            <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5"
              :class="iconBg(n.type)">
              <Icon :name="typeIcon(n.type)" :size="18" :class="iconColor(n.type)" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-xs font-semibold text-dark-100 leading-tight">{{ n.title }}</p>
              <p class="text-xs text-dark-400 mt-0.5 leading-relaxed line-clamp-2">{{ n.message }}</p>

              <!-- Accept / Decline for project invitations -->
              <div v-if="n.type === 'project_invitation' && !n.read_at && n.data?.invitation_id" class="flex gap-2 mt-2">
                <button
                  @click.stop="respondToInvite(n, 'accept')"
                  :disabled="respondingId === n.id"
                  class="flex items-center gap-1 text-xs px-2.5 py-1 rounded-lg bg-emerald-500/15 text-emerald-400 hover:bg-emerald-500/25 transition-colors disabled:opacity-50"
                >
                  <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                  </svg>
                  Aceitar
                </button>
                <button
                  @click.stop="respondToInvite(n, 'decline')"
                  :disabled="respondingId === n.id"
                  class="flex items-center gap-1 text-xs px-2.5 py-1 rounded-lg bg-red-500/15 text-red-400 hover:bg-red-500/25 transition-colors disabled:opacity-50"
                >
                  <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                  Recusar
                </button>
              </div>
              <p v-else-if="n.type === 'project_invitation' && n.read_at" class="text-[10px] text-dark-600 mt-1 italic">
                Convite respondido
              </p>

              <p class="text-[10px] text-dark-600 mt-1">{{ timeAgo(n.created_at) }}</p>
            </div>
            <div v-if="!n.read_at && n.type !== 'project_invitation'" class="w-2 h-2 rounded-full bg-accent-500 flex-shrink-0 mt-1.5" />
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
import { useBrowserNotifications } from '@/composables/useBrowserNotifications'
import { useToast } from '@/composables/useToast'
import type { AppNotification } from '@/api/notifications'
import { useTimeAgo } from '@/composables/useTimeAgo'
import { projectInvitationsApi } from '@/api/projects'
import Icon from '@/components/ui/Icon.vue'

const store  = useNotificationsStore()
const router = useRouter()
const toast  = useToast()
const { supported, permission, requestPermission } = useBrowserNotifications()
const open   = ref(false)
const containerRef = ref<HTMLElement | null>(null)
const respondingId = ref<number | null>(null)
const requesting = ref(false)

async function enableBrowserNotifications() {
  requesting.value = true
  try {
    const result = await requestPermission()
    if (result === 'granted') toast.success('Notificações ativadas!')
    else if (result === 'denied') toast.error('Permissão negada. Habilite nas configurações do navegador.')
  } finally {
    requesting.value = false
  }
}

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

async function respondToInvite(n: AppNotification, action: 'accept' | 'decline') {
  if (!n.data?.invitation_id) return
  respondingId.value = n.id
  try {
    await projectInvitationsApi.respond(n.data.invitation_id as number, action)
    store.markRead(n.id)
    if (action === 'accept' && n.data?.project_id) {
      router.push(`/projects/${n.data.project_id}`)
      open.value = false
    }
  } catch {
    // silently ignore — the invite may have expired
  } finally {
    respondingId.value = null
  }
}

function typeIcon(type: string) {
  const map: Record<string, string> = {
    task_comment: 'chat', task_assigned: 'push_pin', task_approved: 'check_circle',
    task_rejected: 'cancel', file_uploaded: 'attach_file', project_member_added: 'school',
    meeting_scheduled: 'event', project_invitation: 'mail',
    project_invitation_accepted: 'celebration', project_invitation_declined: 'sentiment_dissatisfied',
    task_mention: 'campaign', task_status: 'sync', task_priority: 'bolt', project_removed: 'logout',
    task_approval_requested: 'hourglass_top', project_status: 'traffic', project_member_joined: 'waving_hand',
  }
  return map[type] ?? 'notifications'
}

function iconColor(type: string) {
  if (type === 'task_approved' || type === 'project_invitation_accepted') return 'text-emerald-400'
  if (type === 'task_rejected' || type === 'project_invitation_declined' || type === 'project_removed') return 'text-red-400'
  if (type === 'task_comment' || type === 'task_status') return 'text-blue-400'
  if (type === 'task_priority') return 'text-orange-400'
  if (type === 'task_approval_requested') return 'text-yellow-400'
  if (type === 'project_status' || type === 'meeting_scheduled') return 'text-teal-400'
  if (type === 'file_uploaded') return 'text-purple-400'
  if (type === 'project_invitation' || type === 'project_member_joined' || type === 'project_member_added') return 'text-amber-400'
  return 'text-accent-400'
}

function iconBg(type: string) {
  if (type === 'task_approved' || type === 'project_invitation_accepted') return 'bg-emerald-500/15'
  if (type === 'task_rejected' || type === 'project_invitation_declined') return 'bg-red-500/15'
  if (type === 'task_comment' || type === 'task_status') return 'bg-blue-500/15'
  if (type === 'task_mention') return 'bg-accent-500/15'
  if (type === 'task_priority') return 'bg-orange-500/15'
  if (type === 'project_removed') return 'bg-red-500/15'
  if (type === 'task_approval_requested') return 'bg-yellow-500/15'
  if (type === 'project_status') return 'bg-teal-500/15'
  if (type === 'file_uploaded') return 'bg-purple-500/15'
  if (type === 'meeting_scheduled') return 'bg-teal-500/15'
  if (type === 'project_invitation' || type === 'project_member_joined' || type === 'project_member_added') return 'bg-amber-500/15'
  return 'bg-accent-500/15'
}

const { timeAgo } = useTimeAgo()
</script>
