import { AxiosError, type AxiosRequestConfig, type InternalAxiosRequestConfig } from 'axios'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { ApiError, http, setAuthHandlers } from '@/api/http'

type Sent = InternalAxiosRequestConfig | null
let sent: Sent = null
const originalAdapter = http.defaults.adapter

// reemplaza la red: el adaptador recibe la petición ya procesada por los interceptores
function respond(status: number, data: unknown = {}, headers: Record<string, string> = {}) {
  http.defaults.adapter = async (config: InternalAxiosRequestConfig) => {
    sent = config
    const response = { data, status, statusText: '', headers, config }
    if (status >= 200 && status < 300) return response
    throw new AxiosError('falló', String(status), config, null, response)
  }
}

function failWithoutResponse() {
  http.defaults.adapter = async (config: AxiosRequestConfig) => {
    throw new AxiosError('Network Error', 'ERR_NETWORK', config as InternalAxiosRequestConfig)
  }
}

const onUnauthorized = vi.fn()

beforeEach(() => {
  sent = null
  onUnauthorized.mockReset()
  setAuthHandlers({ getToken: () => 'token-123', onUnauthorized })
})

afterEach(() => {
  http.defaults.adapter = originalAdapter
  setAuthHandlers({ getToken: () => null, onUnauthorized: () => {} })
})

describe('petición', () => {
  it('manda el token en la cabecera Authorization', async () => {
    respond(200, { ok: true })

    await http.get('/me')

    expect(sent?.headers.Authorization).toBe('Bearer token-123')
    expect(sent?.headers.Accept).toBe('application/json')
  })

  it('sin token no manda cabecera Authorization', async () => {
    setAuthHandlers({ getToken: () => null, onUnauthorized })
    respond(200)

    await http.post('/login', {})

    expect(sent?.headers.Authorization).toBeUndefined()
  })
})

describe('errores', () => {
  it('un 422 trae el mensaje y los errores por campo', async () => {
    respond(422, { message: 'Datos inválidos.', errors: { email: ['El correo ya existe.'] } })

    const error = await http.post('/users', {}).catch((e) => e)

    expect(error).toBeInstanceOf(ApiError)
    expect(error.status).toBe(422)
    expect(error.message).toBe('Datos inválidos.')
    expect(error.errors).toEqual({ email: ['El correo ya existe.'] })
  })

  it('un 429 guarda cuántos segundos esperar', async () => {
    respond(429, { message: 'Demasiadas peticiones.' }, { 'retry-after': '42' })

    const error = await http.get('/competitions').catch((e) => e)

    expect(error.status).toBe(429)
    expect(error.retryAfter).toBe(42)
  })

  it('si la respuesta no trae mensaje usa uno genérico', async () => {
    respond(500, {})

    const error = await http.get('/players').catch((e) => e)

    expect(error.message).toBe('Ocurrió un error inesperado.')
    expect(error.errors).toEqual({})
    expect(error.retryAfter).toBeNull()
  })

  it('sin respuesta del servidor avisa que no hay conexión', async () => {
    failWithoutResponse()

    const error = await http.get('/me').catch((e) => e)

    expect(error).toBeInstanceOf(ApiError)
    expect(error.status).toBe(0)
    expect(error.message).toBe('No se pudo conectar con el servidor.')
  })
})

describe('sesión vencida', () => {
  it('un 401 con token enviado cierra la sesión', async () => {
    respond(401, { message: 'No autenticado.' })

    await http.get('/me').catch(() => {})

    expect(onUnauthorized).toHaveBeenCalledOnce()
  })

  it('un 401 sin token (login con datos malos) no cierra nada', async () => {
    setAuthHandlers({ getToken: () => null, onUnauthorized })
    respond(401, { message: 'Correo o contraseña incorrectos.' })

    const error = await http.post('/login', {}).catch((e) => e)

    expect(onUnauthorized).not.toHaveBeenCalled()
    expect(error.message).toBe('Correo o contraseña incorrectos.')
  })

  it('un 401 al cerrar sesión no avisa de sesión vencida: la persona quería salir', async () => {
    respond(401, { message: 'No autenticado.' })

    await http.post('/logout').catch(() => {})

    expect(onUnauthorized).not.toHaveBeenCalled()
  })

  it('un 403 no cierra la sesión: es falta de permiso, no de sesión', async () => {
    respond(403, { message: 'Sin permiso.' })

    await http.get('/users').catch(() => {})

    expect(onUnauthorized).not.toHaveBeenCalled()
  })
})
