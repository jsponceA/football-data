import { fileURLToPath } from 'node:url'
import { mergeConfig, defineConfig, configDefaults } from 'vitest/config'
import viteConfig from './vite.config.ts'
import ViteFonts from 'unplugin-fonts/vite'

export default mergeConfig(
  viteConfig,
  defineConfig({
    test: {
      environment: 'jsdom',
      exclude: [...configDefaults.exclude, 'e2e/**'],
      root: fileURLToPath(new URL('./', import.meta.url)),
      // jsdom no trae lo que Vuetify espera del navegador (ResizeObserver, matchMedia...)
      setupFiles: ['./src/__tests__/setup.ts'],
      // Vuetify importa archivos .css; hay que pasarlos por Vite en vez de dejar que Node los lea
      server: { deps: { inline: ['vuetify'] } },
    },
    plugins: [
      ViteFonts({
        fontsource: {
          families: [
            {
              name: 'Roboto',
              weights: [100, 300, 400, 500, 700, 900],
              styles: ['normal', 'italic'],
            },
          ],
        },
      }),
    ],
  }),
)
