import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

;(window as any).Pusher = Pusher

let echo!: Echo

try {
  const key = import.meta.env.VITE_REVERB_APP_KEY
  if (!key) throw new Error('VITE_REVERB_APP_KEY not set')

  echo = new Echo({
    broadcaster: 'reverb',
    key: key as string,
    wsHost: import.meta.env.VITE_REVERB_HOST as string,
    wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8080),
    wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8080),
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
    enabledTransports: ['ws', 'wss'],
    authorizer: (channel: { name: string }) => ({
      authorize: (socketId: string, callback: (error: boolean, data: unknown) => void) => {
        // Autenticação por SESSÃO/cookie: envia o cookie de sessão + o header CSRF.
        const xsrf = decodeURIComponent(
          document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/)?.[1] ?? ''
        )
        fetch('/api/broadcasting/auth', {
          method: 'POST',
          credentials: 'include',
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-XSRF-TOKEN': xsrf,
          },
          body: JSON.stringify({ socket_id: socketId, channel_name: channel.name }),
        })
          .then(r => r.json())
          .then(data => callback(false, data))
          .catch(err => callback(true, err))
      },
    }),
  })
} catch {
  // Reverb not configured — AppLayout gracefully degrades to polling
  echo = null as unknown as Echo
}

export default echo
