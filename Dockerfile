FROM php:8.2-cli

# Dependencias del sistema
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

# Extensiones PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd intl pdo_mysql mbstring exif pcntl bcmath opcache zip

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs

WORKDIR /app

COPY . .

RUN chmod +x start.sh

# ⚠️ IMPORTANTE: NO optimize / NO jwt / NO cache acá
RUN composer install --optimize-autoloader --no-dev \
    && npm install \
    && npm run build \
    && chmod -R 777 storage bootstrap/cache

EXPOSE 8080

CMD ["/app/start.sh"]
