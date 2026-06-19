FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libzip-dev \
    zip \
    unzip \
    sqlite3 \
    libsqlite3-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install zip pdo pdo_sqlite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application files
COPY . .

# Setup env and SQLite database
RUN cp .env.example .env && \
    mkdir -p database && \
    touch database/database.sqlite

# Install PHP dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Generate key and run migrations
RUN php artisan key:generate && \
    php artisan migrate --force

# Adjust permissions for storage, cache, and database
RUN chmod -R 777 storage bootstrap/cache database

# Expose port 8000
EXPOSE 8000

# Start Laravel development server on port 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
