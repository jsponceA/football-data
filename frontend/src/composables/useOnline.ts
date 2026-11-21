import { onBeforeUnmount, onMounted, ref } from 'vue'

// si el dispositivo tiene conexión; se actualiza solo cuando el navegador avisa que se cayó o volvió
export function useOnline() {
  const online = ref(typeof navigator === 'undefined' ? true : navigator.onLine)

  const update = () => {
    online.value = navigator.onLine
  }

  onMounted(() => {
    update()
    window.addEventListener('online', update)
    window.addEventListener('offline', update)
  })

  onBeforeUnmount(() => {
    window.removeEventListener('online', update)
    window.removeEventListener('offline', update)
  })

  return { online }
}
