#!/bin/sh

# Script de arranque del contenedor: prepara Laravel y levanta procesos.

# Generar APP_KEY si no existe
php artisan key:generate --force

# Limpiar y cachear configuración
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Arrancar servicios con supervisor
# Supervisor mantendra vivos php-fpm y nginx.
exec /usr/bin/supervisord -c /etc/supervisord.conf
