FROM php:8.5-fpm

# Argumentos para sincronizar permisos con el usuario del host
ARG user=sistemasti
ARG uid=1000

# Instalar dependencias del sistema requeridas
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    ca-certificates \
    gnupg \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar el instalador optimizado de extensiones PHP
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# Instalar extensiones PHP necesarias para Laravel 13, DomPDF, OpenSpout, Redis y MySQL
RUN install-php-extensions \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    redis \
    opcache

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Instalar Node.js (v22 LTS) y npm para compilar activos Vite/Tailwind
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Crear usuario del sistema con el mismo UID/GID para evitar problemas de permisos
RUN if id -u $uid >/dev/null 2>&1; then \
        existing_user=$(id -nu $uid); \
        usermod -l $user $existing_user || true; \
    else \
        useradd -G www-data,root -u $uid -d /home/$user $user; \
    fi \
    && mkdir -p /home/$user/.composer \
    && chown -R $uid:$uid /home/$user

# Configurar directorio de trabajo
WORKDIR /var/www

USER $user

EXPOSE 9000

CMD ["php-fpm"]
