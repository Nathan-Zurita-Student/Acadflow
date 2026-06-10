import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { notificationsApi, type AppNotification } from '@/api/notifications'

export const useNotificationsStore = defineStore('notifications', () => {
  const items       = ref<AppNotification[]>([])
  const unread      = ref(0)
  const initialized = ref(false)
  const popupQueue  = ref<AppNotification[]>([])

  const hasUnread = computed(() => unread.value > 0)

  async function fetch() {
    const knownIds = new Set(items.value.map(i => i.id))
    const { data } = await notificationsApi.list()

    if (initialized.value) {
      // Queue genuinely new (unread) notifications for popup display
      data.items
        .filter(n => !knownIds.has(n.id) && !n.read_at)
        .forEach(n => popupQueue.value.push(n))
    }

    items.value   = data.items
    unread.value  = data.unread
    initialized.value = true
  }

  function addIncoming(n: Omit<AppNotification, 'read_at'> & { read_at?: string | null }) {
    const notification: AppNotification = { ...n, read_at: null }
    items.value.unshift(notification)
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

  function drainPopupQueue(): AppNotification[] {
    const queued = [...popupQueue.value]
    popupQueue.value = []
    return queued
  }

  return { items, unread, hasUnread, popupQueue, fetch, addIncoming, markRead, markAllRead, drainPopupQueue }
})
