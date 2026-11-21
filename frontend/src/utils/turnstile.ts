// Cloudflare Turnstile: la clave pública del sitio va en frontend/.env; sin ella el captcha queda apagado
export const turnstileSiteKey: string = import.meta.env.VITE_TURNSTILE_SITE_KEY ?? ''
export const turnstileEnabled = turnstileSiteKey !== ''

interface TurnstileOptions {
  sitekey: string
  theme?: 'auto' | 'light' | 'dark'
  callback: (token: string) => void
  'expired-callback'?: () => void
  'error-callback'?: () => void
}

interface TurnstileApi {
  render: (container: HTMLElement, options: TurnstileOptions) => string
  reset: (widgetId: string) => void
  remove: (widgetId: string) => void
}

declare global {
  interface Window {
    turnstile?: TurnstileApi
  }
}

const SCRIPT_URL = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit'

let loading: Promise<TurnstileApi> | null = null

// el script se descarga una sola vez aunque haya varios formularios con captcha
export function loadTurnstile(): Promise<TurnstileApi> {
  if (window.turnstile) return Promise.resolve(window.turnstile)

  loading ??= new Promise<TurnstileApi>((resolve, reject) => {
    const script = document.createElement('script')
    script.src = SCRIPT_URL
    script.async = true
    script.onload = () => (window.turnstile ? resolve(window.turnstile) : reject(new Error('Turnstile no cargó')))
    script.onerror = () => {
      // permite reintentar si la red falló
      loading = null
      reject(new Error('No se pudo cargar Turnstile'))
    }
    document.head.appendChild(script)
  })

  return loading
}
