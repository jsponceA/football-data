# Football Data

[![CI](https://github.com/jsponceA/football-data/actions/workflows/ci.yml/badge.svg)](https://github.com/jsponceA/football-data/actions/workflows/ci.yml)

Plataforma interna para consultar ligas, equipos y jugadores de fútbol. Los datos vienen de
[football-data.org](https://www.football-data.org), pero la aplicación guarda su propia copia y controla qué puede
hacer cada persona según su rol. Es la solución a la prueba técnica de Senior Full Stack Developer de Inca Rail.

- **API:** Laravel 13 y PHP 8.5, autenticación con Passport, base de datos PostgreSQL.
- **SPA:** Vue 3 con TypeScript, Vuetify, Pinia y CASL. Se puede instalar como aplicación (PWA).
- **Todo junto:** un `compose.yml` levanta la base de datos, la API, la SPA y los procesos de segundo plano con un solo comando.

Versiones principales:

| Pieza | Versión |
| --- | --- |
| PHP | 8.5 |
| Laravel y Passport | 13 |
| PostgreSQL | 18 |
| Vue y Vue Router | 3.5 y 5 |
| Vuetify, Pinia y CASL | 4, 4 y 7 |
| TypeScript y Node | 6 y 22 |

## Contenido

- [Estructura del repositorio](#estructura-del-repositorio)

1. [Cómo ejecutarlo](#1-cómo-ejecutarlo)
2. [Usuarios de prueba](#2-usuarios-de-prueba)
3. [Variables de entorno](#3-variables-de-entorno)
4. [Pruebas](#4-pruebas)
5. [La API](#5-la-api)
6. [Cómo resolví el problema](#6-cómo-resolví-el-problema)
7. [Decisiones de arquitectura](#7-decisiones-de-arquitectura)
8. [Frontend](#8-frontend)
9. [Despliegue en un servidor](#9-despliegue-en-un-servidor)
10. [Supuestos](#10-supuestos)
11. [Lo que quedó pendiente](#11-lo-que-quedó-pendiente)

## Estructura del repositorio

```
backend/      API Laravel
frontend/     SPA Vue
docs/         Diagramas (imagen y archivo editable de Mermaid)
docker/       Configuración de Nginx para los contenedores
compose.yml   Levanta PostgreSQL, la API, la SPA, el worker de la cola y el programador de tareas
.env.example  Variables para Docker Compose
```

## 1. Cómo ejecutarlo

Hace falta un token de football-data.org. Se pide gratis en
<https://www.football-data.org/client/register> y llega por correo.

### Opción A: con Docker (la más corta)

Requisitos: Git y Docker con Compose (en Windows, Docker Desktop). No hace falta tener PHP, Node ni PostgreSQL.

**1. Clona el repositorio**

```bash
git clone https://github.com/jsponceA/football-data.git
cd football-data
```

**2. Crea el archivo de variables y pega el token**

```bash
cp .env.example .env
```

En PowerShell, `cp` también funciona. Abre `.env` con cualquier editor y pega el token en la línea
`FOOTBALL_DATA_TOKEN=`. Lo demás ya viene listo para usar en local.

**3. Levanta todo**

```bash
docker compose up --build
```

La primera vez tarda unos minutos, porque descarga las imágenes e instala las dependencias. Está listo cuando en los
registros aparece `ready to handle connections`. Para dejar la terminal libre, usa `docker compose up -d --build`.
Levanta cinco contenedores: `db`, `app` (la API), `web` (Nginx con la SPA), `worker` (la cola) y `scheduler` (las tareas
programadas).

Al arrancar, el contenedor de la API crea las llaves de Passport, ejecuta las migraciones y carga los seeders sin que
tengas que hacer nada más. Los seeders solo corren cuando la base de datos está vacía, así que reiniciar los
contenedores no restablece las contraseñas de los usuarios.

**4. Pruébalo**

| Qué | Dirección |
| --- | --- |
| Aplicación (SPA) | <http://localhost:8080> |
| API | <http://localhost:8000/api> |
| Documentación de la API | <http://localhost:8000/docs/api> |

Entra a la aplicación con `admin@example.com` y `password` (ver [usuarios de prueba](#2-usuarios-de-prueba)). Para
comprobar que todo funciona: la pantalla de Ligas debe mostrar las ligas y, al abrir una (por ejemplo la Premier
League), se importan sus equipos y jugadores.

Si algo no arranca:
- **Puerto ocupado.** Si ya usas el 8080 o el 8000 (por ejemplo, con `php artisan serve`), detén eso o cambia
  `FRONT_PORT` y `API_PORT` en el `.env`. Si cambias el puerto de la API, ajusta también `APP_URL`.
- **Errores de la API.** Se ven con `docker compose logs app`.

**5. Apagar y limpiar**

```bash
docker compose down        # detiene y elimina los contenedores; conserva la base de datos y las fotos
docker compose down -v     # además borra la base de datos y las fotos
```

### Opción B: sin Docker

Requisitos: PHP 8.5 (con las extensiones `pdo_pgsql`, `mbstring`, `intl`, `gd`, `bcmath` y `zip`), Composer 2,
Node 22 y PostgreSQL.

**API**

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

Crea la base de datos y ajusta en `backend/.env` los valores `DB_USERNAME`, `DB_PASSWORD` y `FOOTBALL_DATA_TOKEN`:

```bash
psql -U postgres -c "CREATE DATABASE db_football ENCODING 'UTF8'"
```

Después:

```bash
php artisan migrate --seed
php artisan passport:keys
php artisan storage:link
php artisan serve
```

La API queda en <http://localhost:8000>. `storage:link` hace falta para que se vean las fotos de perfil.

**SPA**, en otra terminal:

```bash
cd frontend
npm install
cp .env.example .env
npm run dev
```

Abre <http://localhost:5173>. El archivo `frontend/.env` ya apunta a la API en `http://localhost:8000/api`.

**Cola y tareas programadas** (opcional), en dos terminales más desde `backend`:

```bash
php artisan queue:work
php artisan schedule:work
```

Sin ellas la aplicación funciona igual; lo único que no ocurre es el refresco automático de las ligas (ver
[Segundo plano](#segundo-plano-cola-y-tareas-programadas)).

## 2. Usuarios de prueba

Los crean los seeders al preparar la base de datos.

| Correo | Contraseña | Rol | Qué puede hacer |
| --- | --- | --- | --- |
| `admin@example.com` | `password` | admin | Todo: ver y importar ligas, equipos y jugadores, y administrar usuarios y roles |
| `consulta@example.com` | `password` | solo-consulta | Solo ver ligas, equipos y jugadores |

Esa contraseña es la que se usa si no cambias nada. Se puede cambiar con `SEED_USER_PASSWORD` antes de crear la base
de datos; en un servidor real conviene hacerlo.

Cualquier persona también puede crear su cuenta desde la pantalla de registro. Entra con el rol de solo consulta y un
administrador puede cambiárselo después.

Si no configuras correo, los mensajes (bienvenida y restablecer contraseña) no se envían: quedan en el registro de la
API. En Docker se leen con `docker compose logs app`; sin Docker, en `backend/storage/logs/laravel.log`.

## 3. Variables de entorno

Hay tres archivos de ejemplo y ninguno lleva secretos. Los `.env` reales no se suben al repositorio.

| Archivo | Para qué |
| --- | --- |
| `.env.example` | Docker Compose |
| `backend/.env.example` | La API, cuando se ejecuta sin Docker |
| `frontend/.env.example` | La SPA, cuando se ejecuta sin Docker |

Las que importan:

| Variable | Qué hace |
| --- | --- |
| `FOOTBALL_DATA_TOKEN` | Token de football-data.org. Es la única obligatoria |
| `FOOTBALL_DATA_RATE_LIMIT` | Peticiones por minuto que la API se permite hacer afuera. Por defecto 9 |
| `FOOTBALL_DATA_FRESH_HOURS` | Cuántas horas un dato guardado se considera reciente. Por defecto 24 |
| `APP_URL` | Dirección pública de la API. De ahí salen las URL de las fotos |
| `FRONTEND_URL` | Dirección de la SPA. Es el único origen que acepta CORS y a donde apuntan los enlaces de los correos |
| `SEED_USER_PASSWORD` | Contraseña de los usuarios de prueba |
| `MAIL_MAILER` | `log` no envía nada; `resend` envía de verdad (necesita `RESEND_API_KEY` y un remitente verificado) |
| `DOCS_USER` y `DOCS_PASSWORD` | Usuario (por defecto `docs`) y contraseña para abrir `/docs/api` en producción. Sin contraseña, la documentación queda cerrada |
| `TURNSTILE_SECRET` y `VITE_TURNSTILE_SITE_KEY` | Captcha de Cloudflare en registro y recuperar contraseña. Vacíos, queda apagado |

## 4. Pruebas

```bash
# API: usa SQLite en memoria y simula football-data, así que no gasta peticiones reales ni necesita token
cd backend
php artisan test

# SPA
cd frontend
npm run test:unit
npm run type-check
```

La API tiene 159 pruebas y la SPA 122. Las de la API cubren el login y los permisos de cada ruta por rol, la
importación (que no duplique registros y qué responde cuando falta cupo o falla el servicio externo), el limitador, el
cliente de football-data, el trabajo de la cola, el comando programado y el evento de importación, la paginación, el registro y el restablecimiento de contraseña. Las de la SPA cubren las
validaciones, el guardián de rutas, el cierre de sesión por token vencido y los componentes que más lógica tienen.

En cada `push` y en cada pull request, GitHub Actions ejecuta las pruebas de la API, y las de la SPA junto con la
revisión de tipos y la compilación (`.github/workflows/ci.yml`).

## 5. La API

La documentación interactiva está en `/docs/api` (y la especificación OpenAPI en `/docs/api.json`). Se genera sola a
partir de las rutas, las validaciones y los resources.

- Con `APP_ENV=local`, el valor por defecto de los ejemplos, se abre sin pedir nada.
- Con `APP_ENV=production` queda cerrada. Para abrirla, pon una contraseña en `DOCS_PASSWORD`: el navegador pedirá el
  usuario (`docs` por defecto, se cambia con `DOCS_USER`) y esa contraseña. Si la contraseña está vacía, nadie entra.

Todas las rutas van bajo `/api` y, salvo las públicas, piden el token en `Authorization: Bearer <token>`.

Los listados vienen paginados (`page` y `per_page`, hasta 100 por página). Los de equipos y jugadores aceptan además
`search`; los equipos se pueden filtrar con `competition` y los jugadores con `team`.

| Método y ruta | Permiso | Qué hace |
| --- | --- | --- |
| `POST /login` | pública | Devuelve el token y los datos de la persona |
| `POST /register`, `/forgot-password`, `/reset-password` | pública | Crear cuenta y recuperar contraseña |
| `POST /logout` | sesión | Invalida el token |
| `GET /me` | sesión | Datos de quien inició sesión y sus permisos |
| `GET /competitions` | `competitions.view` | Todas las ligas |
| `GET /competitions/{id}` | `competitions.view` | Detalle de una liga; guarda sus equipos y jugadores |
| `GET /team` y `GET /team/{id}` | `teams.view` | Equipos guardados y detalle de uno |
| `GET /players` | `players.view` | Jugadores guardados |
| `/users` (listar, crear, ver, editar, activar o desactivar, foto) | `users.manage` | Gestión de usuarios |
| `/roles` y `GET /permissions` | `roles.manage` | Gestión de roles y permisos |

Las cinco rutas de la prueba están tal como se piden, incluido `/team` en singular. En todas, `{id}` es el id que usa
football-data.org (por ejemplo `/competitions/2021` para la Premier League y `/team/57` para el Arsenal), así que el id
interno de la base de datos nunca se expone.

Los errores siempre salen en JSON con el mismo formato, `{ "message": "..." }`, y el 422 añade el detalle por campo:

| Código | Cuándo |
| --- | --- |
| 401 | Falta el token o venció |
| 403 | La persona no tiene el permiso, o la liga no está en el plan gratuito |
| 404 | La liga, el equipo o el jugador no existe |
| 422 | Datos inválidos |
| 429 | Se acabó el cupo de football-data y no hay copia local. Trae `Retry-After` con los segundos de espera |
| 502, 503 | football-data no responde o no está configurado, y no hay copia local |

## 6. Cómo resolví el problema

### Arquitectura

![Arquitectura](docs/arquitectura.png)

La SPA habla solo con la API de Laravel. La API valida el token con Passport, revisa el permiso de la ruta y deja el
trabajo en servicios. Los servicios leen y guardan en PostgreSQL con Eloquent, y solo cuando un dato no es reciente
pasan por el limitador antes de llamar a football-data. Esa llamada sale por una única clase, `FootballDataClient`.
Aparte, un programador y un worker (con el mismo código de la API) refrescan las ligas por la noche pasando por el mismo
servicio y el mismo límite.
Archivo editable: [`docs/arquitectura.mmd`](docs/arquitectura.mmd).

### El límite de 10 peticiones por minuto

El plan gratuito de football-data solo deja hacer 10 peticiones por minuto. Lo resolví con tres reglas.

**1. Primero lo que ya tengo.** Las rutas `/team`, `/team/{id}` y `/players` solo leen de la base de datos y nunca llaman
afuera. Las ligas también se responden desde la base de datos mientras el dato sea reciente (24 horas, configurable).
Así, aunque football-data esté caído o sin cupo, todo lo guardado se puede consultar.

**2. Importar una liga cuesta dos llamadas como máximo.** Comprobé con el token real que `GET /competitions/{id}/teams`
ya trae cada equipo con su plantilla. Por eso importar una liga es: la lista de ligas (solo si está vieja o la liga
no se conocía) y los equipos. Eso permite hacerlo en la misma petición, sin esperar a una cola.

**3. Un limitador propio, antes de llamar.** La API cuenta sus llamadas salientes y se permite 9 por minuto, una menos
que el tope, para tener margen. El contador vive en la caché, así que lo comparten todos los procesos. El cliente se
niega a llamar si no queda cupo y cuenta cada llamada antes de hacerla, porque aunque falle igual gasta una del
plan. Además, antes de importar una liga el servicio pregunta si alcanza para las dos llamadas, para no dejar una
importación a medias. Si no hay cupo, no intenta nada y responde así.

| Situación | Con copia local | Sin copia local |
| --- | --- | --- |
| Sin cupo | 200 con los datos guardados y `meta.stale: true` | 429 con `Retry-After` |
| football-data caído o con error | 200 con los datos guardados y `meta.stale: true` | 502 o 503 |
| Liga de pago (plan distinto a `TIER_ONE`) | 403, sin gastar ninguna llamada | 403 |
| Liga inexistente | 404 | 404 |

La SPA usa `meta.stale` para mostrar un aviso de que los datos pueden estar desactualizados, y el `Retry-After` para
decirle a la persona cuántos segundos esperar.

La importación que pide una persona (`GET /competitions/{id}`) no pasa por una cola: son dos llamadas como máximo, la
persona está esperando el resultado y una cola le agregaría una pantalla de "importando...". La cola se usa para lo que
nadie está esperando, que es mantener los datos al día.

### Segundo plano: cola y tareas programadas

- **Refresco nocturno.** Cada noche a las 3:00 (hora de Lima, cuando casi nadie consulta y sobra cupo) el programador
  ejecuta `php artisan football:refresh`. Ese comando no importa nada: encola un trabajo `ImportCompetition` por cada
  liga gratuita ya importada cuyos datos tengan más de 24 horas, separados 15 segundos entre sí.
- **El worker ejecuta los trabajos** (`php artisan queue:work`). Cada uno usa el mismo servicio que la API, así que pasa
  por el mismo limitador y nunca se salta el tope de 9 peticiones por minuto.
- **Sin cupo no falla, espera.** Si no hay cupo o football-data no responde, el trabajo se pospone (lo que indique el
  limitador, o un minuto) y sigue intentando durante media hora. Las ligas de pago (403) y las inexistentes (404) se
  descartan, porque reintentar no cambia nada. Una misma liga no se encola dos veces a la vez.
- **Primera carga.** `php artisan football:refresh --all` encola también las ligas gratuitas que nunca se importaron,
  trayendo antes la lista de ligas si hace falta. En una instalación nueva, las 12 se reparten solas en unos minutos.
- **Evento.** Cuando una liga termina de guardarse, venga de una persona o de la cola, se dispara
  `CompetitionImported` y un listener deja el registro (liga, equipos y jugadores). Es un solo evento con un solo
  listener; está para poder añadir otras reacciones, como un aviso, sin tocar el servicio.

La cola usa la base de datos (`QUEUE_CONNECTION=database`), así que no hace falta Redis. En Docker, el worker y el
programador son servicios del `compose.yml`; sin Docker son las dos terminales de la sección de ejecución. Para probarlo
sin esperar a las 3:00, ejecuta `php artisan football:refresh` (en Docker,
`docker compose exec app php artisan football:refresh`) y mira los registros con `docker compose logs worker`.

### Flujo de importación

![Flujo de importación](docs/flujo-importacion.png)

Es la secuencia completa de `GET /competitions/{id}`: qué se revisa primero, cuándo se pide cupo, qué se responde en
cada fallo y cómo se guardan los datos. La escritura va en una transacción y con `upsert`, por eso consultar la misma
liga dos veces no duplica nada, y los jugadores que ya no están en una plantilla se retiran de ese equipo.
Archivo editable: [`docs/flujo-importacion.mmd`](docs/flujo-importacion.mmd).

### Autenticación y permisos

Passport entrega un token con `POST /login` (dura 8 horas) y `POST /logout` lo revoca. Las contraseñas se guardan con
hash. Una persona desactivada no puede usar la API, y al desactivarla se cierran todas sus sesiones.

Los permisos se manejan con `spatie/laravel-permission`, con el formato `recurso.acción`: `competitions.view`,
`teams.view`, `players.view`, `users.manage` y `roles.manage`. Cada ruta de la API exige su permiso, así que la
seguridad no depende de lo que muestre la interfaz. Un administrador puede crear roles nuevos y elegir sus permisos
desde la SPA.

`GET /me` devuelve los permisos de la persona ya en el formato de CASL, por ejemplo
`{ "action": "view", "subject": "teams" }`, y con eso la SPA decide qué rutas y botones mostrar.

### Modelo de datos

![Modelo de datos](docs/modelo-datos.png)

Ligas, equipos y jugadores guardan el `external_id` de football-data, con índice único, que es lo que permite hacer
`upsert` sin duplicar. Una liga y un equipo se relacionan en una tabla intermedia, porque un equipo puede jugar varias
competiciones. Los usuarios, roles y permisos son las tablas de `spatie/laravel-permission`.
Archivo editable: [`docs/modelo-datos.mmd`](docs/modelo-datos.mmd).

## 7. Decisiones de arquitectura

Usé las capas simples de Laravel y no apliqué DDD, arquitectura hexagonal, Clean Architecture ni CQRS.

```
Ruta -> Middleware (token y permiso) -> Controlador -> Servicio -> Modelo (Eloquent)
                                          |    |
                           FormRequest <--+    +--> Resource            Servicio -> FootballDataClient -> football-data.org
                         (valida entrada)        (da forma a la salida)
```

- Los controladores solo reciben la petición y devuelven un resource. La lógica está en los servicios
  (`CompetitionService`, `UserService`, `RoleService`...).
- La validación está en FormRequests y la salida en Resources.
- Todo lo que habla con football-data está en `FootballDataClient`, y el limitador en `ApiRateLimiter`.

Comparé estas opciones:

| Opción | Lo bueno | Por qué no |
| --- | --- | --- |
| Hexagonal o Clean Architecture | El dominio queda aislado de Laravel y de la fuente de datos | El dominio es casi solo "guardar lo que llega y servirlo". Habría muchas interfaces y clases que solo reenvían la llamada |
| DDD | Útil cuando hay reglas de negocio ricas | Aquí no las hay: las reglas importantes son de acceso y de límite de peticiones |
| CQRS | Separa lecturas y escrituras | Las lecturas salen de las mismas tablas que escriben, así que separarlas no aportaría nada |
| Eventos | Permiten reaccionar a lo que pasa sin acoplar el código | Los usé donde costaban poco: un evento, `CompetitionImported`, con un listener. Una arquitectura orientada a eventos completa sería demasiado para este alcance |
| Capas simples de Laravel | Se entiende a la primera, es lo que espera quien conoce Laravel | Si el dominio crece, habrá que separarlo |

Lo que sí hice para dejar abierta la puerta: la dependencia externa está detrás de una sola clase, así que cambiar de
proveedor o pasarlo a un puerto es tocar un archivo. Si apareciera una segunda fuente de datos o reglas de negocio más
complejas, ese sería el momento de pasar a hexagonal.

## 8. Frontend

SPA con Vue 3, Vue Router, TypeScript y Composition API. Usa Vuetify para el diseño, Pinia (con persistencia) para la
sesión y CASL para los permisos.

- Pantallas: inicio, ligas con el detalle de cada una, equipos, jugadores, usuarios, roles y permisos, perfil, y el
  flujo de entrada (login, registro, olvidé mi contraseña).
- Se ve bien en celular y en escritorio, con modo claro y oscuro.
- Cada ruta declara el permiso que necesita. Si falta, la persona ve una pantalla de "sin acceso" y no una página rota.
  Si la sesión vence, vuelve al login con un aviso y regresa a donde estaba al volver a entrar.
- Cargas con esqueletos, listas vacías, errores con mensaje claro y avisos cuando falla football-data o se alcanza el
  límite. Listados con búsqueda, filtros y paginación.
- Componentes reutilizables: tablas, formularios, diálogos de confirmación, estados de carga, etc.
- Es una PWA: se puede instalar en el celular o en el escritorio. Guarda la aplicación para abrirla rápido y avisa
  cuando hay una versión nueva, pero nunca guarda en caché las respuestas de la API, para no dejar datos de personas
  en el dispositivo.

## 9. Despliegue en un servidor

El mismo `compose.yml` sirve para un servidor. La SPA y la API van en dominios distintos (por ejemplo
`app.midominio.com` y `api.midominio.com`) y el HTTPS lo pone el Nginx del propio servidor, con certbot.

1. En el `.env`: pon las dos URLs reales en `APP_URL` y `FRONTEND_URL`, `APP_ENV=production`, contraseñas propias
   en `POSTGRES_PASSWORD` y `SEED_USER_PASSWORD`, y descomenta `BIND_ADDRESS=127.0.0.1` y `RESTART_POLICY`.
   Con `BIND_ADDRESS` los contenedores solo escuchan en el propio servidor. Si el servidor ya usa los puertos 8080 u
   8000, cámbialos con `FRONT_PORT` y `API_PORT`. Si quieres ver la documentación de la API en el servidor, pon
   también `DOCS_PASSWORD` (ver [La API](#5-la-api)).
2. `docker compose up -d --build`. Eso levanta también el worker y el programador, así que no hace falta configurar
   ningún cron en el servidor.
3. En el Nginx del servidor, un sitio por dominio que reenvíe al puerto que corresponda:

```nginx
server {
    server_name app.midominio.com;
    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
# igual para api.midominio.com hacia el puerto 8000; después: certbot --nginx
```

Si pones Cloudflare delante, usa el modo SSL "Full (strict)", y en el Nginx del servidor restaura la IP real de quien
visita (`real_ip_header CF-Connecting-IP`). Si no, el limitador de intentos de login vería siempre la IP de Cloudflare.

Para que la PWA reciba las versiones nuevas, el servicio de caché no debe guardar `/sw.js` ni `/manifest.webmanifest`.
Nginx ya los entrega con `no-cache`; si usas Cloudflare, comprueba que no los marque como `HIT`.

Para actualizar: `git pull && docker compose up -d --build`. Las variables `VITE_` se escriben al compilar la SPA, por
eso hay que reconstruir si cambian.

## 10. Supuestos

- **Rutas públicas.** La prueba dice que todas las rutas salvo el login van protegidas. Añadí registro y recuperar
  contraseña, que por naturaleza son públicas, con límite de intentos por minuto y captcha opcional. Todo lo demás exige
  token.
- **Número de camiseta.** El plan gratuito de football-data no entrega `shirtNumber` en ninguna plantilla. La columna
  existe y se llena si algún plan lo trae, pero con el plan gratuito queda vacía.
- **Solo las ligas del plan gratuito.** Las ligas de pago aparecen en el listado, atenuadas y con la etiqueta "Plan de
  pago", y no se pueden abrir. Si alguien llama a la API directamente, recibe un 403 con el mensaje "Esa liga no está
  disponible con el plan gratuito".
- **Una liga importada es reciente durante 24 horas.** Dentro de ese plazo no se vuelve a pedir nada afuera.
- **Quien se registra entra como solo consulta.** Un administrador puede darle más permisos.
- **Un administrador no puede quedarse fuera.** No puede desactivarse ni cambiarse sus propios roles.
- **La nacionalidad de los jugadores se guarda como texto**, tal como la entrega football-data (que a veces usa dos
  nombres para el mismo país).
- **Hay un solo contador de cupo para toda la API,** no uno por persona: el límite es de la cuenta de football-data.

## 11. Lo que quedó pendiente

- **Seguimiento de la cola.** Los trabajos que agotan sus reintentos quedan en la tabla `failed_jobs` (se ven con
  `php artisan queue:failed`), pero no hay panel ni aviso. Con más tiempo, Laravel Horizon (necesita Redis) o una
  notificación cuando un trabajo falle.
- **Refresco selectivo.** El refresco nocturno actualiza todas las ligas ya importadas, no solo las más consultadas. Con
  las 12 del plan gratuito son pocas llamadas; con un plan mayor convendría priorizar.
- **Pruebas de aceptación de punta a punta.** Hay pruebas de la API con base de datos y de la SPA, pero no un navegador
  automatizado que recorra el flujo completo.
- **Pruebas contra PostgreSQL.** Las pruebas de la API usan SQLite en memoria. La base real es PostgreSQL, y ahí podrían
  aparecer diferencias (por ejemplo, en las búsquedas sin distinguir mayúsculas).
- **Análisis de estilo en el CI.** Laravel Pint está instalado, pero el código aún no sigue sus reglas, así que no lo
  puse en el pipeline.
- **Despliegue automático.** Hoy se hace a mano con `git pull`. Automatizarlo exigiría guardar una llave SSH como secreto
  del repositorio.
