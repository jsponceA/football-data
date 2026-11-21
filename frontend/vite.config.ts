import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'

// https://vite.dev/config/
export default defineConfig({
  plugins: [
    vue(),
    vueDevTools(),
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  build: {
    // por defecto Vite mete en el css (en base64) las imágenes de menos de 4 kB y las banderas pesan poco:
    // el css se iba a 1,2 MB. Así cada bandera se descarga solo cuando se muestra.
    assetsInlineLimit: (file) => (file.includes('flag-icons') ? false : undefined),
  },
})
