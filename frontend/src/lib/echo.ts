import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

Pusher.logToConsole = true
window.Pusher = Pusher

console.log('[echo.ts] env', {
    key: import.meta.env?.VITE_REVERB_APP_KEY,
    host: import.meta.env?.VITE_REVERB_HOST,
    port: import.meta.env?.VITE_REVERB_PORT,
    scheme: import.meta.env?.VITE_REVERB_SCHEME,
})

const echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env?.VITE_REVERB_APP_KEY ?? 'local',
    cluster: 'mt1',

    // IMPORTANT: for Reverb, path should be /app (don’t leave this blank)
    // wsPath: import.meta.env?.VITE_REVERB_PATH ?? '/app',

    wsHost: import.meta.env?.VITE_REVERB_HOST ?? window.location.hostname,
    wsPort: Number(import.meta.env?.VITE_REVERB_PORT ?? 8081),
    wssPort: Number(import.meta.env?.VITE_REVERB_PORT ?? 8081),

    // IMPORTANT: Reverb uses /app and then Echo/Pusher adds /<key>
    // wsPath: import.meta.env?.VITE_REVERB_PATH ?? "",

    forceTLS: String(import.meta.env?.VITE_REVERB_SCHEME ?? 'http').toLowerCase() === 'https',
    encrypted: String(import.meta.env?.VITE_REVERB_SCHEME ?? 'http').toLowerCase() === 'https',

    enabledTransports: ['ws', 'wss'],
    disableStats: true,
})

echo.connector.pusher.connection.bind('state_change', (states) => {
    console.log('[pusher] state_change', states)
})

echo.connector.pusher.connection.bind('connected', () => {
    console.log('[pusher] connected')
})

echo.connector.pusher.connection.bind('error', (err) => {
    console.log('[pusher] error', err)
})

export default echo
