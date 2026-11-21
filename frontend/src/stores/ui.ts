import { ref } from 'vue'
import { defineStore } from 'pinia'

export type ThemeMode = 'system' | 'light' | 'dark'

export const useUiStore = defineStore(
  'ui',
  () => {
    // mientras nadie elija, se sigue el modo del sistema
    const theme = ref<ThemeMode>('system')

    return { theme }
  },
  { persist: true },
)
