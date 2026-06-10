import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { notificationsApi, type AppNotification } from '@/api/notifications'

export const useNotificationsStore = defineStore('notifications', () => {
  const items  = ref<AppNotification[]>([])
  const unread = ref(0)

  const hasUnread = computed(() => unread.value > 0)

  async function fetch() {
    const { data } = await notificationsApi.list()
    items.value  = data.items
    unread.value = data.unread
  }

  // Called by real-time WebSocket when a new notification arrives
  function addIncoming(n: Omit<AppNotification, 'read_at'> & { read_at?: string | null }) {
    const notification: AppNotification = { ...n, read_at: null }
    items.value.unshift(notification)
    // keep list to 30 most recent
    if (items.value.length > 30) items.value.splice(30)
    unread.value++
  }

  async function markRead(id: number) {
    await notificationsApi.markRead(id)
    const n = items.value.find(i => i.id === id)
    if (n && !n.read_at) { n.read_at = new Date().toISOString(); unread.value = Math.max(0, unread.value - 1) }
  }

  async function markAllRead() {
    await notificationsApi.markAllRead()
    items.value.forEach(n => { if (!n.read_at) n.read_at = new Date().toISOString() })
    unread.value = 0
  }

  return { items, unread, hasUnread, fetch, addIncoming, markRead, markAllRead }
})
