import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

const key = import.meta.env.VITE_REVERB_APP_KEY
const host = import.meta.env.VITE_REVERB_HOST
const port = import.meta.env.VITE_REVERB_PORT
const scheme = import.meta.env.VITE_REVERB_SCHEME

;(window as any).Pusher = Pusher

export const echo = new Echo({
    broadcaster: 'pusher',
    key,
    wsHost: host,
    wsPort: Number(port),
    wssPort: Number(port),
    forceTLS: scheme === 'https',
    enabledTransports: ['ws', 'wss'],
})
