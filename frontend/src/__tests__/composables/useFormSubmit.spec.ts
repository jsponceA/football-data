import { describe, expect, it, vi } from 'vitest'
import type { SubmitEventPromise } from 'vuetify'
import { ApiError } from '@/api/http'
import { useFormSubmit } from '@/composables/useFormSubmit'

// el evento de Vuetify es una promesa que trae si el formulario pasó sus reglas
const event = (valid: boolean) => Promise.resolve({ valid }) as unknown as SubmitEventPromise

describe('useFormSubmit', () => {
  it('con el formulario válido ejecuta la acción y devuelve true', async () => {
    const { handleSubmit, loading } = useFormSubmit()
    const action = vi.fn().mockResolvedValue(undefined)

    const ok = await handleSubmit(event(true), action)

    expect(ok).toBe(true)
    expect(action).toHaveBeenCalledOnce()
    expect(loading.value).toBe(false)
  })

  it('con el formulario inválido no llama a la acción', async () => {
    const { handleSubmit } = useFormSubmit()
    const action = vi.fn()

    const ok = await handleSubmit(event(false), action)

    expect(ok).toBe(false)
    expect(action).not.toHaveBeenCalled()
  })

  it('mientras envía, loading está en true', async () => {
    const { handleSubmit, loading } = useFormSubmit()
    let release: () => void = () => {}
    const pending = handleSubmit(event(true), () => new Promise<void>((resolve) => (release = resolve)))

    await vi.waitFor(() => expect(loading.value).toBe(true))
    release()
    await pending

    expect(loading.value).toBe(false)
  })

  it('un 422 deja el mensaje y los errores por campo', async () => {
    const { handleSubmit, error, fieldErrors } = useFormSubmit()

    const ok = await handleSubmit(event(true), async () => {
      throw new ApiError(422, 'Datos inválidos.', { email: ['Ya existe.'] })
    })

    expect(ok).toBe(false)
    expect(error.value).toBe('Datos inválidos.')
    expect(fieldErrors.value).toEqual({ email: ['Ya existe.'] })
  })

  it('un error que no es de la API da un mensaje genérico', async () => {
    const { handleSubmit, error } = useFormSubmit()

    await handleSubmit(event(true), async () => {
      throw new Error('boom')
    })

    expect(error.value).toBe('Ocurrió un error inesperado.')
  })

  it('al reintentar se borran los errores anteriores', async () => {
    const { handleSubmit, error, fieldErrors } = useFormSubmit()
    await handleSubmit(event(true), async () => {
      throw new ApiError(422, 'Datos inválidos.', { name: ['Falta.'] })
    })

    const ok = await handleSubmit(event(true), async () => {})

    expect(ok).toBe(true)
    expect(error.value).toBe('')
    expect(fieldErrors.value).toEqual({})
  })
})
