import { onUnmounted } from 'vue'
import echo from '@/echo'

/**
 * Helper de ciclo de vida sobre o Laravel Echo.
 * - Degrada graciosamente quando o Reverb não está configurado (`echo === null`).
 * - Sai automaticamente de todos os canais ao desmontar o componente.
 */
export function useRealtime() {
  const enabled = !!echo
  const joined = new Set<string>()

  function privateChannel(name: string) {
    if (!echo) return null
    joined.add(name)
    return echo.private(name)
  }

  function presence(name: string) {
    if (!echo) return null
    joined.add(name)
    return echo.join(name)
  }

  function whisper(name: string, event: string, payload: unknown) {
    if (!echo) return
    try {
      echo.private(name).whisper(event, payload)
    } catch { /* canal ainda não pronto */ }
  }

  function leave(name: string) {
    if (!echo) return
    echo.leave(name)
    joined.delete(name)
  }

  function leaveAll() {
    if (!echo) return
    joined.forEach(name => echo.leave(name))
    joined.clear()
  }

  onUnmounted(leaveAll)

  return { enabled, privateChannel, presence, whisper, leave, leaveAll }
}
