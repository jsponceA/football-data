import { watchEffect } from 'vue'

const APP_NAME = 'Football Data'

export function pageTitle(title?: string) {
  return title ? `${title} | ${APP_NAME}` : APP_NAME
}

// para pantallas donde el título depende de lo que se cargó, como el nombre de una liga
export function usePageTitle(title: () => string | undefined) {
  watchEffect(() => {
    const value = title()
    // mientras carga se queda el título de la ruta
    if (value) document.title = pageTitle(value)
  })
}
