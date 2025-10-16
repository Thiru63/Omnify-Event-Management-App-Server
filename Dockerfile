# Use official PHP image with required extensions
FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git curl libpq-dev zip unzip libonig-dev libzip-dev && \
    docker-php-ext-install pdo pdo_pgsql mbstring zip

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . .

# Install dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Copy the environment file if not present
RUN cp .env.example .env

# Generate app key
RUN php artisan key:generate

# Create SQLite database and set permissions
RUN mkdir -p database && \
    touch database/database.sqlite && \
    chmod 775 database/database.sqlite && \
    chmod -R 775 storage/ && \
    chmod -R 775 bootstrap/cache/

# Publish and modify Swagger views to use CDN (FIX FOR SWAGGER)
RUN php artisan vendor:publish --tag=l5-swagger-views --force && \
    sed -i 's|{{ \$assetPath }}/swagger-ui.css|https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui.css|g' resources/views/vendor/l5-swagger/index.blade.php && \
    sed -i 's|{{ \$assetPath }}/swagger-ui-bundle.js|https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui-bundle.js|g' resources/views/vendor/l5-swagger/index.blade.php && \
    sed -i 's|{{ \$assetPath }}/swagger-ui-standalone-preset.js|https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui-standalone-preset.js|g' resources/views/vendor/l5-swagger/index.blade.php

# Generate Swagger docs
RUN php artisan vendor:publish --provider="L5Swagger\L5SwaggerServiceProvider" --force
RUN php artisan l5-swagger:generate

# Clear cache without database dependencies
RUN php artisan config:clear
RUN php artisan view:clear

# Expose port 8000 and start Laravel server
EXPOSE 8000

# Run migrations and cache clear in the CMD (after tables are created)
CMD php artisan migrate --force && php artisan cache:clear && php artisan serve --host=0.0.0.0 --port=8000