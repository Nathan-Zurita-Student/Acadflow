import { onMounted, onUnmounted, ref } from 'vue'

interface PollingOptions {
  /** Pause when the document tab is not visible (default: true) */
  visibilityPause?: boolean
  /** Run immediately on mount before the first interval fires (default: true) */
  immediate?: boolean
}

/**
 * Runs `fn` on a repeating `interval` (ms), automatically pausing when the
 * browser tab is hidden and cleaning up on component unmount.
 */
export function usePolling(
  fn: () => void | Promise<void>,
  interval: number,
  options: PollingOptions = {},
) {
  const { visibilityPause = true, immediate = true } = options
  const running = ref(false)
  let timer: ReturnType<typeof setInterval> | null = null

  function start() {
    if (timer) return
    running.value = true
    timer = setInterval(() => {
      if (visibilityPause && document.hidden) return
      fn()
    }, interval)
  }

  function stop() {
    if (timer) { clearInterval(timer); timer = null }
    running.value = false
  }

  function handleVisibilityChange() {
    if (!visibilityPause) return
    if (document.hidden) {
      stop()
    } else {
      fn()  // catch up immediately after tab regains focus
      start()
    }
  }

  onMounted(() => {
    if (immediate) fn()
    start()
    if (visibilityPause) document.addEventListener('visibilitychange', handleVisibilityChange)
  })

  onUnmounted(() => {
    stop()
    if (visibilityPause) document.removeEventListener('visibilitychange', handleVisibilityChange)
  })

  return { running, start, stop }
}
