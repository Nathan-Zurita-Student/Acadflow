import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

;(window as any).Pusher = Pusher

const echo = new Echo({
  broadcaster: 'reverb',
  key: import.meta.env.VITE_REVERB_APP_KEY as string,
  wsHost: import.meta.env.VITE_REVERB_HOST as string,
  wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8080),
  wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8080),
  forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
  enabledTransports: ['ws', 'wss'],
  authorizer: (channel: { name: string }) => ({
    authorize: (socketId: string, callback: (error: boolean, data: unknown) => void) => {
      fetch('/api/broadcasting/auth', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          Authorization: `Bearer ${localStorage.getItem('token') ?? ''}`,
        },
        body: JSON.stringify({ socket_id: socketId, channel_name: channel.name }),
      })
        .then(r => r.json())
        .then(data => callback(false, data))
        .catch(err => callback(true, err))
    },
  }),
})

export default echo
