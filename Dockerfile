FROM php:8.2-apache

# 1. Actualizar el sistema e instalar TODAS las dependencias (Postgres + MongoDB)
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libssl-dev \
    pkg-config \
    libcurl4-openssl-dev \
    # 2. Instalar y habilitar extensiones de PostgreSQL
    && docker-php-ext-install pdo pdo_pgsql pgsql \
    # 3. Compilar, instalar y habilitar la extensión de MongoDB
    && pecl install mongodb \
    && docker-php-ext-enable mongodb

# Habilitar mod_rewrite de Apache
RUN a2enmod rewrite

# Copiar todos los archivos de tu proyecto al contenedor
COPY . /var/www/html/

# Dar los permisos correctos al servidor web
RUN chown -R www-data:www-data /var/www/html

# Exponer el puerto 80 para Render
EXPOSE 80