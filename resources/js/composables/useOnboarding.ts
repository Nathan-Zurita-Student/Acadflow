import { ref } from 'vue'

/**
 * Tour de boas-vindas (frente #6). Estado global (singleton) + persistência
 * por usuário no localStorage, para **nunca repetir automaticamente**.
 */
const tourOpen = ref(false)

function storageKey(userId?: number | null) {
  return `acadflow:onboarded:${userId ?? 'anon'}`
}

export function useOnboarding() {
  function hasOnboarded(userId?: number | null): boolean {
    try {
      return localStorage.getItem(storageKey(userId)) === '1'
    } catch {
      return false
    }
  }

  function markOnboarded(userId?: number | null) {
    try {
      localStorage.setItem(storageKey(userId), '1')
    } catch { /* ignora (modo privado etc.) */ }
  }

  /** Abre o tour manualmente (ex.: "Rever tour" na Busca Global). */
  function openTour() {
    tourOpen.value = true
  }

  function closeTour() {
    tourOpen.value = false
  }

  /** Conclui/pula: marca como visto e fecha (não reabre sozinho). */
  function finishTour(userId?: number | null) {
    markOnboarded(userId)
    tourOpen.value = false
  }

  /** Abre no primeiro acesso, se ainda não tiver sido visto. */
  function maybeAutoStart(userId?: number | null) {
    if (!hasOnboarded(userId)) tourOpen.value = true
  }

  return { tourOpen, hasOnboarded, markOnboarded, openTour, closeTour, finishTour, maybeAutoStart }
}
