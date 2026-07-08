import { ref } from 'vue'

/**
 * Tour de boas-vindas (frente #6).
 *
 * Regra: só aparece automaticamente para quem **acabou de se cadastrar**
 * (o cadastro chama `markPendingTour()`), e nunca repete. Usuários já
 * existentes não veem o tour ao entrar — só se abrirem manualmente pela
 * Busca Global ("Rever tour de boas-vindas").
 */
const tourOpen = ref(false)

const PENDING_KEY = 'acadflow:pending-tour'

function onboardedKey(userId?: number | null) {
  return `acadflow:onboarded:${userId ?? 'anon'}`
}

export function useOnboarding() {
  function hasOnboarded(userId?: number | null): boolean {
    try {
      return localStorage.getItem(onboardedKey(userId)) === '1'
    } catch {
      return false
    }
  }

  function markOnboarded(userId?: number | null) {
    try {
      localStorage.setItem(onboardedKey(userId), '1')
    } catch { /* ignora (modo privado etc.) */ }
  }

  /** Marca que o tour deve aparecer na 1ª entrada no app (chamado no cadastro). */
  function markPendingTour() {
    try {
      localStorage.setItem(PENDING_KEY, '1')
    } catch { /* ignora */ }
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

  /**
   * Abre no primeiro acesso APENAS se houver um cadastro recente pendente.
   * Consome a flag pendente para não reabrir depois.
   */
  function maybeAutoStart(userId?: number | null) {
    let pending = false
    try {
      pending = localStorage.getItem(PENDING_KEY) === '1'
    } catch { /* ignora */ }

    if (pending && !hasOnboarded(userId)) {
      tourOpen.value = true
      try { localStorage.removeItem(PENDING_KEY) } catch { /* ignora */ }
    }
  }

  return {
    tourOpen,
    hasOnboarded,
    markOnboarded,
    markPendingTour,
    openTour,
    closeTour,
    finishTour,
    maybeAutoStart,
  }
}
