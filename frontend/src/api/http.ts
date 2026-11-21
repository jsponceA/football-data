import axios, { AxiosError } from 'axios'

// el backend siempre responde { message } y en el 422 también { errors }
export class ApiError extends Error {
  constructor(
    public status: number,
    message: string,
    public errors: Record<string, string[]> = {},
    public retryAfter: number | null = null,
  ) {
    super(message)
  }
}

interface AuthHandlers {
  getToken: () => string | null
  onUnauthorized: () => void
}

let handlers: AuthHandlers = { getToken: () => null, onUnauthorized: () => {} }

// lo configura main.ts una vez que Pinia existe, así este archivo no depende del store
export function setAuthHandlers(value: AuthHandlers) {
  handlers = value
}

export const http = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  headers: { Accept: 'application/json' },
  timeout: 30000,
})

http.interceptors.request.use((config) => {
  const token = handlers.getToken()
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

http.interceptors.response.use(
  (response) => response,
  (error: AxiosError<{ message?: string; errors?: Record<string, string[]> }>) => {
    if (!error.response) {
      return Promise.reject(new ApiError(0, 'No se pudo conectar con el servidor.'))
    }

    const { status, data, headers, config } = error.response

    // un 401 con token enviado = sesión vencida; en el login no hay token y es solo "credenciales malas".
    // Al cerrar sesión con un token ya vencido no hay nada que avisar: la persona quería salir
    if (status === 401 && config.headers.Authorization && config.url !== '/logout') {
      handlers.onUnauthorized()
    }

    const retryAfter = Number(headers['retry-after']) || null

    return Promise.reject(
      new ApiError(status, data?.message ?? 'Ocurrió un error inesperado.', data?.errors, retryAfter),
    )
  },
)
