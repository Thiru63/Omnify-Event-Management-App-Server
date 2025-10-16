# Use official PHP image with required extensions
FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git curl libpq-dev zip unzip libonidocker-php-ext-install pdo pdo_pgsql mbstring zip

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

# Publish Swagger config
RUN php artisan vendor:publish --provider="L5Swagger\L5SwaggerServiceProvider" --force

# Generate Swagger JSON
RUN php artisan l5-swagger:generate

# FIX THE JSON - Replace ALL localhost occurrences with production URL
RUN sed -i 's|http://localhost:8000/api|https://omnify-event-management-app-server.onrender.com/api|g' storage/api-docs/api-docs.json
RUN sed -i 's|Local Development Server|Production Server|g' storage/api-docs/api-docs.json
RUN sed -i 's|http://localhost:8000|https://omnify-event-management-app-server.onrender.com|g' storage/api-docs/api-docs.json

# Clear caches
RUN php artisan config:clear && php artisan view:clear

# Expose port 8000 and start Laravel server
EXPOSE 8000

CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=8000