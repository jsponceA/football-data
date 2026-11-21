# Frontend

SPA de Football Data hecha con Vue 3, TypeScript, Vuetify, Pinia, Vue Router y CASL. La explicación general del
proyecto y cómo levantarlo completo está en el [README de la raíz](../README.md).

## Comandos

```bash
npm install
cp .env.example .env     # apunta a la API en http://localhost:8000/api
npm run dev              # http://localhost:5173
```

| Comando | Qué hace |
| --- | --- |
| `npm run dev` | Servidor de desarrollo |
| `npm run build-only` | Compila para producción en `dist/` |
| `npm run preview` | Sirve lo compilado (aquí se puede probar la PWA) |
| `npm run type-check` | Revisa los tipos con `vue-tsc` |
| `npm run test:unit` | Pruebas con Vitest |
| `npm run format` | Da formato con Prettier |

Necesita Node 22.18 o superior.

## Estructura

```
src/
  api/          Llamadas a la API (un archivo por recurso) y el cliente de axios
  components/   Componentes reutilizables
  composables/  Lógica compartida (listados paginados, formularios, avisos...)
  layouts/      Estructura de las pantallas con sesión
  plugins/      Permisos con CASL y paletas de color
  router/       Rutas y guardián de sesión y permisos
  stores/       Pinia: sesión y permisos de quien entró
  utils/        Formato, validaciones y ayudas
  views/        Una pantalla por archivo
  __tests__/    Pruebas, agrupadas por la carpeta que prueban
```
