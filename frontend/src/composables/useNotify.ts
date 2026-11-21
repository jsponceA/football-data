import { reactive } from 'vue'

// un solo aviso para toda la app; App.vue es quien lo dibuja
export const notification = reactive({ show: false, text: '', color: 'success' })

export function useNotify() {
  function notify(text: string, color: 'success' | 'error' = 'success') {
    notification.text = text
    notification.color = color
    notification.show = true
  }

  return { notify }
}
