import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'
import { VitePWA } from 'vite-plugin-pwa'

// https://vite.dev/config/
export default defineConfig({
  plugins: [
    vue(),
    vueDevTools(),
    VitePWA({
      // "prompt": cuando hay una versión nueva la app avisa y la persona decide cuándo actualizar,
      // en vez de recargar sola y perder lo que esté escribiendo en un formulario
      registerType: 'prompt',
      // el registro lo hace el componente PwaUpdate (así puede mostrar el aviso)
      injectRegister: false,
      includeAssets: ['favicon.ico', 'favicon-16x16.png', 'favicon-32x32.png', 'apple-touch-icon.png'],
      manifest: {
        name: 'Football Data',
        short_name: 'Football Data',
        description: 'Consulta las ligas, los equipos y los jugadores de las principales competiciones de fútbol.',
        lang: 'es',
        start_url: '/',
        scope: '/',
        // se abre como una app, sin la barra del navegador
        display: 'standalone',
        categories: ['sports'],
        // color de la barra del sistema y de la pantalla de carga
        theme_color: '#1b8f4c',
        background_color: '#121212',
        icons: [
          { src: 'pwa-192x192.png', sizes: '192x192', type: 'image/png' },
          { src: 'pwa-512x512.png', sizes: '512x512', type: 'image/png' },
          // con margen de seguridad: Android la recorta en círculo, cuadrado redondeado, etc.
          { src: 'maskable-512x512.png', sizes: '512x512', type: 'image/png', purpose: 'maskable' },
        ],
      },
      workbox: {
        // la cáscara de la app (html, js, css, fuentes e imágenes propias) queda guardada y abre al instante
        globPatterns: ['**/*.{js,css,html,ico,png,webp,woff2}'],
        cleanupOutdatedCaches: true,
        navigateFallback: 'index.html',
        // la API y su documentación nunca deben responderse con el index.html de la SPA
        navigateFallbackDenylist: [/^\/api\//, /^\/docs\//],
        runtimeCaching: [
          {
            // escudos, emblemas y fotos de perfil vienen de otro dominio: se guardan para verlos sin esperar,
            // y se actualizan en segundo plano
            urlPattern: ({ request, sameOrigin }) => request.destination === 'image' && !sameOrigin,
            handler: 'StaleWhileRevalidate',
            options: {
              cacheName: 'imagenes',
              expiration: { maxEntries: 300, maxAgeSeconds: 60 * 60 * 24 * 30 },
              // 0 = respuesta opaca de otro dominio sin CORS (los escudos de football-data)
              cacheableResponse: { statuses: [0, 200] },
            },
          },
          {
            // las banderas son ~270 svg pequeños (Vite los deja en /assets): no se precargan todos,
            // solo se guardan los que se llegan a mostrar
            urlPattern: ({ sameOrigin, url }) => sameOrigin && url.pathname.endsWith('.svg'),
            handler: 'CacheFirst',
            options: {
              cacheName: 'banderas',
              expiration: { maxEntries: 300, maxAgeSeconds: 60 * 60 * 24 * 365 },
            },
          },
        ],
      },
    }),
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
