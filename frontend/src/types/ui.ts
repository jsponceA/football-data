import type { RouteLocationRaw } from 'vue-router'

export interface Crumb {
  title: string
  // sin "to" es la página actual
  to?: RouteLocationRaw
}
