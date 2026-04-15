#!/bin/sh

# Generar APP_KEY si no existe
php artisan key:generate --force

# Limpiar y cachear configuración
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Arrancar servicios con supervisor
exec /usr/bin/supervisord -c /etc/supervisord.conf