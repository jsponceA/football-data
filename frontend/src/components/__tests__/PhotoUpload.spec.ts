import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import { createTestVuetify, flush } from '@/__tests__/helpers'
import PhotoUpload from '../PhotoUpload.vue'

function mountPhoto(props: { url?: string | null } = {}) {
  const wrapper = mount(PhotoUpload, {
    props: {
      file: null,
      removed: false,
      // v-model:file y v-model:removed: el componente emite y el padre devuelve el valor
      'onUpdate:file': (value: File | null) => wrapper.setProps({ file: value }),
      'onUpdate:removed': (value: boolean) => wrapper.setProps({ removed: value }),
      ...props,
    },
    global: { plugins: [createTestVuetify()] },
  })
  return wrapper
}

async function choose(wrapper: ReturnType<typeof mountPhoto>, file: File) {
  const input = wrapper.find('input[type=file]')
  Object.defineProperty(input.element, 'files', { value: [file], configurable: true })
  await input.trigger('change')
  await flush()
}

const image = (name: string, type: string, bytes = 1000) => new File([new Uint8Array(bytes)], name, { type })

describe('PhotoUpload', () => {
  it('sin foto muestra la invitación a cargar una', () => {
    const wrapper = mountPhoto()

    expect(wrapper.text()).toContain('Cargar imagen o arrastrar aquí')
    expect(wrapper.text()).toContain('Máx. 2 MB')
    expect(wrapper.find('.photo-upload__img').exists()).toBe(false)
    expect(wrapper.text()).not.toContain('Quitar')
  })

  it('con una foto guardada la muestra y ofrece quitarla', () => {
    const wrapper = mountPhoto({ url: 'https://example.com/foto.png' })

    expect(wrapper.find('.photo-upload__img').exists()).toBe(true)
    expect(wrapper.text()).toContain('Quitar')
  })

  it('una imagen válida se acepta y se muestra la vista previa', async () => {
    const wrapper = mountPhoto()
    const png = image('yo.png', 'image/png')

    await choose(wrapper, png)

    expect(wrapper.props('file')).toBe(png)
    expect(wrapper.find('.photo-upload__img').exists()).toBe(true)
    expect(wrapper.text()).not.toContain('Usa una imagen')
  })

  it('rechaza un formato que el servidor no acepta', async () => {
    const wrapper = mountPhoto()

    await choose(wrapper, image('animada.gif', 'image/gif'))

    expect(wrapper.props('file')).toBeNull()
    expect(wrapper.text()).toContain('Usa una imagen JPG, PNG o WEBP.')
  })

  it('rechaza una imagen de más de 2 MB', async () => {
    const wrapper = mountPhoto()

    await choose(wrapper, image('grande.jpg', 'image/jpeg', 2 * 1024 * 1024 + 1))

    expect(wrapper.props('file')).toBeNull()
    expect(wrapper.text()).toContain('La imagen pesa más de 2 MB.')
  })

  it('exactamente 2 MB sí entra', async () => {
    const wrapper = mountPhoto()

    await choose(wrapper, image('justa.jpg', 'image/jpeg', 2 * 1024 * 1024))

    expect(wrapper.props('file')).not.toBeNull()
  })

  it('quitar una foto ya guardada marca que hay que borrarla en el servidor', async () => {
    const wrapper = mountPhoto({ url: 'https://example.com/foto.png' })

    await wrapper.findAll('button').find((b) => b.text().includes('Quitar'))!.trigger('click')

    expect(wrapper.props('removed')).toBe(true)
    expect(wrapper.find('.photo-upload__img').exists()).toBe(false)
  })

  it('quitar una imagen recién elegida no marca nada para el servidor', async () => {
    const wrapper = mountPhoto()
    await choose(wrapper, image('yo.png', 'image/png'))

    await wrapper.findAll('button').find((b) => b.text().includes('Quitar'))!.trigger('click')

    expect(wrapper.props('file')).toBeNull()
    expect(wrapper.props('removed')).toBe(false)
  })

  it('elegir otra imagen después de quitar la guardada cancela el borrado', async () => {
    const wrapper = mountPhoto({ url: 'https://example.com/foto.png' })
    await wrapper.findAll('button').find((b) => b.text().includes('Quitar'))!.trigger('click')
    expect(wrapper.props('removed')).toBe(true)

    await choose(wrapper, image('nueva.png', 'image/png'))

    expect(wrapper.props('removed')).toBe(false)
    expect(wrapper.props('file')).not.toBeNull()
  })
})
