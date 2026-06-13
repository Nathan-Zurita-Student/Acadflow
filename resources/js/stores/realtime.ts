import { defineStore } from 'pinia'
import { ref } from 'vue'

/**
 * Sinais leves de tempo real compartilhados entre o AppLayout (que mantém a
 * assinatura única do canal do usuário) e as páginas que precisam reagir.
 */
export const useRealtimeStore = defineStore('realtime', () => {
  // Incrementado sempre que chega um evento `dashboard.stale`.
  const dashboardStaleTick = ref(0)

  function markDashboardStale() {
    dashboardStaleTick.value++
  }

  return { dashboardStaleTick, markDashboardStale }
})
