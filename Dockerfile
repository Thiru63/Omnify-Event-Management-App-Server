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
    chmod -R 775 storage/ database/ bootstrap/cache/

# Set production URL
RUN echo "APP_URL=https://omnify-event-management-app-server.onrender.com" >> .env

# Create api-docs directory explicitly
RUN mkdir -p storage/api-docs

# Generate Swagger JSON FIRST
RUN php artisan l5-swagger:generate

# Then publish assets
RUN php artisan vendor:publish --provider="L5Swagger\L5SwaggerServiceProvider" --force

# Publish and fix Swagger views with CDN
RUN php artisan vendor:publish --tag=l5-swagger-views --force

# Replace ALL asset paths with CDN URLs
RUN sed -i 's|{{ \$assetPath }}/swagger-ui.css|https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui.css|g' resources/views/vendor/l5-swagger/index.blade.php
RUN sed -i 's|{{ \$assetPath }}/swagger-ui-bundle.js|https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui-bundle.js|g' resources/views/vendor/l5-swagger/index.blade.php
RUN sed -i 's|{{ \$assetPath }}/swagger-ui-standalone-preset.js|https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui-standalone-preset.js|g' resources/views/vendor/l5-swagger/index.blade.php
RUN sed -i 's|{{ \$assetPath }}/favicon-32x32.png|https://unpkg.com/swagger-ui-dist@5.9.0/favicon-32x32.png|g' resources/views/vendor/l5-swagger/index.blade.php
RUN sed -i 's|{{ \$assetPath }}/favicon-16x16.png|https://unpkg.com/swagger-ui-dist@5.9.0/favicon-16x16.png|g' resources/views/vendor/l5-swagger/index.blade.php

# Clear only safe caches
RUN php artisan config:clear && php artisan view:clear

# Expose port 8000 and start Laravel server
EXPOSE 8000

CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=8000