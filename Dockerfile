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

# Publish Swagger views
RUN php artisan vendor:publish --tag=l5-swagger-views --force

# Copy pre-made Swagger view with CDN
COPY swagger-view-fix.sh /tmp/
RUN chmod +x /tmp/swagger-view-fix.sh && /tmp/swagger-view-fix.sh

# Generate Swagger docs
RUN php artisan vendor:publish --provider="L5Swagger\L5SwaggerServiceProvider" --force
RUN php artisan l5-swagger:generate

# Clear cache without database dependencies
RUN php artisan config:clear
RUN php artisan view:clear

# Expose port 8000 and start Laravel server
EXPOSE 8000

# Run migrations and cache clear in the CMD (after tables are created)
CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=8000