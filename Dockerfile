FROM php:8.2-cli

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libicu-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libzip-dev

# Instalar extensiones PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd intl pdo_mysql mbstring exif pcntl bcmath opcache zip

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Instalar Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs

# Configurar directorio de trabajo
WORKDIR /app

# Copiar archivos
COPY . .

# Instalar dependencias y build
RUN composer install --optimize-autoloader --no-dev \
    && npm install \
    && npm run build \
    && php artisan optimize \
    && chmod -R 777 storage bootstrap/cache

# Exponer puerto
EXPOSE 8080

# Crear script de inicio
RUN echo '#!/bin/bash\n\
set -e\n\
echo "Container started..."\n\
echo "Waiting 30 seconds for MySQL..."\n\
sleep 30\n\
echo "Running migrations..."\n\
php artisan migrate --force || echo "Migration failed"\n\
echo "Creating storage link..."\n\
php artisan storage:link || echo "Storage link failed"\n\
echo "Starting Laravel server on 0.0.0.0:8080..."\n\
exec php artisan serve --host=0.0.0.0 --port=8080\n\
' > /start.sh && chmod +x /start.sh

# Comando de inicio
CMD ["/start.sh"]
