# ============================================
# STAGE 1: Build del frontend (Vue + Vite)
# ============================================
FROM node:20-alpine AS frontend-build

# Directorio de trabajo para el build del frontend.
WORKDIR /app

# Instala dependencias de Node (mejor cache de capas copiando primero package*.json).
COPY package*.json ./
RUN npm install

# Copia el codigo y genera los assets para produccion en public/build.
COPY . .
RUN npm run build

# ============================================
# STAGE 2: Imagen final con PHP + NGINX (Debian)
# ============================================
FROM php:8.3-fpm-bullseye

# Instala Nginx, Supervisor y dependencias del runtime/build de extensiones PHP.
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
    apt-transport-https

# Driver SQL Server oficial para Debian
RUN curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add - \
    && curl https://packages.microsoft.com/config/debian/11/prod.list > /etc/apt/sources.list.d/mssql-release.list \
    && apt-get update \
    && ACCEPT_EULA=Y apt-get install -y msodbcsql18 unixodbc-dev \
    && pecl install sqlsrv pdo_sqlsrv \
    && docker-php-ext-enable sqlsrv pdo_sqlsrv

# Extensiones PHP requeridas por Laravel y tareas en segundo plano.
RUN docker-php-ext-install pdo mbstring bcmath pcntl

# Composer para instalar dependencias PHP dentro de la imagen final.
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Raiz de la aplicacion Laravel.
WORKDIR /var/www/html

# Copia codigo backend y assets compilados desde la etapa frontend-build.
COPY . .
COPY --from=frontend-build /app/public/build ./public/build

# Instala dependencias de produccion de Composer.
RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs

# Ajusta permisos para que Laravel pueda escribir cache/logs correctamente.
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Copia configuraciones de Nginx, Supervisor y script de arranque.
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

# Puerto HTTP expuesto por Nginx dentro del contenedor.
EXPOSE 80

# Inicia script que prepara Laravel y levanta Supervisor (php-fpm + nginx).
CMD ["/start.sh"]
