# Use the official Laravel image as a base
FROM composer:2 AS build

# Set working directory
WORKDIR /app

# Copy composer.json and composer.lock
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-scripts --no-autoloader

# Copy the application code
COPY . .

# Install PHP dependencies (again, to ensure scripts and autoloading are correct)
RUN composer install --optimize-autoloader

# Build assets with Vite
FROM node:18 AS assets
WORKDIR /app
COPY . .
RUN npm install
RUN npm run build

# Final stage
FROM php:8.2-fpm

# Set working directory
WORKDIR /var/www/html

# Copy PHP dependencies from the build stage
COPY --from=build /app .

# Copy built assets from the assets stage
COPY --from=assets /app/public/build ./public/build

# Set permissions for storage and cache directories
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache

# Copy entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["docker-entrypoint.sh"]