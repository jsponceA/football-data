# syntax=docker/dockerfile:1

# PHP 8.5 con FPM: Nginx (contenedor "web") le habla por FastCGI en el puerto 9000
FROM php:8.5-fpm-alpine

# PostgreSQL; el resto de extensiones que pide Laravel ya vienen en la imagen.
# Desde PHP 8.5 opcache va integrado en PHP: ya no se instala como extensión aparte
RUN apk add --no-cache libpq postgresql-dev \
    && docker-php-ext-install pdo_pgsql \
    && apk del postgresql-dev

# ajustes de PHP: la foto de perfil admite hasta 2 MB (esto deja margen) y, como la imagen no
# cambia en caliente, opcache no necesita revisar si los archivos se modificaron
RUN printf '%s\n' \
    'upload_max_filesize = 5M' \
    'post_max_size = 6M' \
    'memory_limit = 256M' \
    'expose_php = 0' \
    'display_errors = Off' \
    'log_errors = On' \
    'opcache.enable = 1' \
    'opcache.memory_consumption = 128' \
    'opcache.validate_timestamps = 0' \
    > /usr/local/etc/php/conf.d/zz-app.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www/html

# primero solo los archivos de dependencias: mientras no cambien, Docker reutiliza esta capa
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY . .
RUN composer dump-autoload --optimize --no-dev --no-scripts --no-interaction \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views \
        storage/logs storage/app/public bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

# Lo que corre al arrancar el contenedor, antes de PHP-FPM. Es seguro repetirlo en cada arranque.
COPY --chmod=755 <<'EOF' /usr/local/bin/entrypoint
#!/bin/sh
set -e
cd /var/www/html

# la carpeta storage es un volumen: la primera vez llega vacía
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views \
    storage/logs storage/app/public bootstrap/cache

# si no se da una APP_KEY se crea una sola vez y se guarda en el volumen,
# así una sesión o un dato cifrado no se invalidan al reiniciar
if [ -z "$APP_KEY" ]; then
    if [ ! -s storage/.app_key ]; then
        php -r 'echo "base64:" . base64_encode(random_bytes(32));' > storage/.app_key
    fi
    APP_KEY="$(cat storage/.app_key)"
    export APP_KEY
fi

# claves con las que Passport firma los tokens; también se crean una sola vez
if [ ! -f storage/oauth-private.key ]; then
    php artisan passport:keys --no-interaction
fi

php artisan migrate --force --no-interaction

# los seeders solo corren con la base vacía: reejecutarlos reiniciaría las contraseñas
# de los usuarios de prueba y los permisos de los roles
roles="$(php -r '
$pdo = new PDO(
    "pgsql:host=" . getenv("DB_HOST") . ";port=" . getenv("DB_PORT") . ";dbname=" . getenv("DB_DATABASE"),
    getenv("DB_USERNAME"),
    getenv("DB_PASSWORD")
);
echo (int) $pdo->query("SELECT COUNT(*) FROM roles")->fetchColumn();
')"
if [ "$roles" = "0" ]; then
    php artisan db:seed --force --no-interaction
fi

# con la configuración en caché las variables de entorno se leen una sola vez, aquí
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
php artisan view:cache --no-interaction

# lo anterior corrió como root: PHP-FPM trabaja como www-data y necesita poder escribir
chown -R www-data:www-data storage bootstrap/cache

exec "$@"
EOF

# por si Git en Windows dejó finales de línea CRLF, que en Linux rompen el script
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint

ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]
