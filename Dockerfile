FROM php:8.4-cli

# System dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    zip \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    && rm -rf /var/lib/apt/lists/*

# Configure PHP extensions
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg

# Install PHP extensions required by Laravel
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Create application user
ARG UID=1000
ARG GID=1000

RUN groupadd -g ${GID} app \
    && useradd -u ${UID} -g app -m app

# Application directory
WORKDIR /var/www/html

# Copy composer files first for better layer caching
COPY --chown=app:app composer.json composer.lock ./

# Install PHP dependencies at build time
RUN composer install --no-interaction --no-dev --optimize-autoloader --no-scripts

# Copy application code
COPY --chown=app:app . .

# Copy entrypoint script
COPY --chown=app:app docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Ensure storage and bootstrap/cache are writable
RUN chown -R app:app storage bootstrap/cache

USER app

EXPOSE 8000

ENTRYPOINT ["docker-entrypoint.sh"]