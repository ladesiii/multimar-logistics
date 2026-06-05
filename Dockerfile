# ============================================
# STAGE 1: Build del frontend (Vue + Vite)
# ============================================
FROM node:20-alpine AS frontend-build

WORKDIR /app

# Instala dependencias aprovechando la caché de Docker
COPY package*.json ./
RUN npm install

# Copia el código y genera los assets para producción
COPY . .
RUN npm run build

# ============================================
# STAGE 2: Imagen final con PHP + NGINX (Debian)
# ============================================
FROM php:8.3-fpm-bullseye

# Evita alertas interactivas durante la instalación
ENV DEBIAN_FRONTEND=noninteractive

# Instala Nginx, Supervisor y herramientas base
RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
    curl \
    unzip \
    git \
    libonig-dev \
    autoconf \
    g++ \
    make \
    gnupg2 \
    apt-transport-https \
    && rm -rf /var/lib/apt/lists/*

# Driver SQL Server oficial (Forma moderna compatible con Debian 11)
RUN curl -fsSL https://packages.microsoft.com/keys/microsoft.asc | gpg --dearmor -o /usr/share/keyrings/microsoft-prod.gpg \
    && echo "deb [signed-by=/usr/share/keyrings/microsoft-prod.gpg] https://packages.microsoft.com/debian/11/prod bullseye main" > /etc/apt/sources.list.d/mssql-release.list \
    && apt-get update && ACCEPT_EULA=Y apt-get install -y msodbcsql18 unixodbc-dev \
    && pecl install sqlsrv pdo_sqlsrv \
    && docker-php-ext-enable sqlsrv pdo_sqlsrv \
    && rm -rf /var/lib/apt/lists/*

# Extensiones PHP requeridas por Laravel
RUN docker-php-ext-install pdo mbstring bcmath pcntl

# Instalar Composer desde la imagen oficial
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copia el código del backend
COPY . .

# Copia los assets compilados por Vite desde la STAGE 1
COPY --from=frontend-build /app/public/build ./public/build

# Instala dependencias de Composer optimizadas para producción
RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs

# Crear las carpetas de almacenamiento si no existen antes del chown
RUN mkdir -p storage/framework/cache/data \
    && mkdir -p storage/framework/app/cache \
    && mkdir -p storage/framework/sessions \
    && mkdir -p storage/framework/views \
    && mkdir -p storage/logs

# Ajusta permisos definitivos (Usuario www-data de Nginx/PHP)
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Copia configuraciones usando tu estructura
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 80

CMD ["/start.sh"]
