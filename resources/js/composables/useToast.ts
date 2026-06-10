import { ref } from 'vue'

export interface ToastItem {
  id: number
  message: string
  type: 'success' | 'error' | 'info' | 'warning'
}

const toasts = ref<ToastItem[]>([])
let seq = 0

export function useToast() {
  function show(message: string, type: ToastItem['type'] = 'success', ms = 3500) {
    const id = ++seq
    toasts.value.push({ id, message, type })
    setTimeout(() => dismiss(id), ms)
  }
  function dismiss(id: number) {
    toasts.value = toasts.value.filter(t => t.id !== id)
  }
  return {
    toasts,
    dismiss,
    success: (msg: string) => show(msg, 'success'),
    error:   (msg: string) => show(msg, 'error', 5000),
    info:    (msg: string) => show(msg, 'info'),
    warning: (msg: string) => show(msg, 'warning', 4000),
  }
}
