import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import TurnstileWidget from '@/components/TurnstileWidget.vue'

describe('TurnstileWidget', () => {
  it('sin clave del sitio no dibuja nada y no descarga el script de Cloudflare', () => {
    const wrapper = mount(TurnstileWidget, { props: { modelValue: '' } })

    expect(wrapper.find('.turnstile-box').exists()).toBe(false)
    expect(document.head.querySelector('script[src*="challenges.cloudflare.com"]')).toBeNull()
  })

  it('reset deja el token vacío', () => {
    const wrapper = mount(TurnstileWidget, { props: { modelValue: 'token-usado' } })

    ;(wrapper.vm as unknown as { reset: () => void }).reset()

    const emitted = wrapper.emitted('update:modelValue') ?? []
    expect(emitted[emitted.length - 1]).toEqual([''])
  })
})
