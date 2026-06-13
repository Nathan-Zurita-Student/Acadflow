import { ref } from 'vue'
import type { AppNotification } from '@/api/notifications'

const isSupported = typeof window !== 'undefined' && 'Notification' in window
// Estado reativo compartilhado — a UI reage quando a permissão muda
const permission = ref<NotificationPermission>(isSupported ? Notification.permission : 'denied')

// Mantém o ref sincronizado MESMO quando o usuário muda nas configurações do site
// (a Permissions API avisa via onchange — sem isso, ativar pelo navegador não refletia aqui).
if (isSupported && typeof navigator !== 'undefined' && navigator.permissions?.query) {
  navigator.permissions.query({ name: 'notifications' as PermissionName })
    .then(status => {
      const sync = () => {
        permission.value = status.state === 'prompt' ? 'default' : (status.state as NotificationPermission)
      }
      sync()
      status.onchange = sync
    })
    .catch(() => { /* navegador sem Permissions API — segue com Notification.permission */ })
}

/**
 * Notificações nativas do navegador (Notification API).
 * Requer "contexto seguro" (HTTPS ou localhost). É o único mecanismo de
 * notificação do app (não há mais popup in-app), então dispara sempre.
 */
export function useBrowserNotifications() {
  async function requestPermission(): Promise<NotificationPermission> {
    if (!isSupported) return 'denied'
    if (Notification.permission !== 'default') {
      permission.value = Notification.permission
      return permission.value
    }
    try {
      const result = await Notification.requestPermission()
      permission.value = result
      return result
    } catch {
      return permission.value
    }
  }

  function notify(n: AppNotification, onClick?: () => void) {
    // Lê o estado AO VIVO (não o cache) — funciona mesmo se a permissão foi dada nas configs do site
    if (!isSupported || Notification.permission !== 'granted') return

    try {
      const notification = new Notification(n.title, {
        body: n.message,
        icon: '/imagem/acadflow.png',
        tag: `acadflow-${n.id}`,
      })
      notification.onclick = () => {
        window.focus()
        onClick?.()
        notification.close()
      }
    } catch { /* ignore */ }
  }

  return { supported: isSupported, permission, requestPermission, notify }
}
