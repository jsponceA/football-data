import { describe, expect, it, vi } from 'vitest'
import { ApiError } from '@/api/http'

// el captcha se prende solo con la clave del sitio; aquí se simula que está activo
vi.mock('@/utils/turnstile', () => ({ turnstileEnabled: true, turnstileSiteKey: 'clave', loadTurnstile: vi.fn() }))

const { useCaptcha } = await import('../useCaptcha')

describe('useCaptcha', () => {
  it('sin token no deja enviar y avisa que se espere a la verificación', () => {
    const { require } = useCaptcha()

    expect(() => require()).toThrow(ApiError)
    expect(() => require()).toThrow('Espera a que termine la verificación')
  })

  it('con token deja enviar', () => {
    const captcha = useCaptcha()
    captcha.token.value = 'token-valido'

    expect(() => captcha.require()).not.toThrow()
  })

  it('renovar pide un token nuevo al widget', () => {
    const captcha = useCaptcha()
    const reset = vi.fn()
    captcha.widget.value = { reset } as never

    captcha.renew()

    expect(reset).toHaveBeenCalledOnce()
  })

  it('renovar sin widget montado no falla', () => {
    expect(() => useCaptcha().renew()).not.toThrow()
  })
})
