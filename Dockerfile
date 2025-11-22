# Railway PHP Dockerfile
FROM php:8.1-cli

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy composer files first (for better caching)
COPY composer.json composer.lock ./

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copy startup script first
COPY start.sh /start.sh
RUN chmod +x /start.sh

# Copy application files
COPY . .

# Set permissions
RUN chmod -R 755 /app

# Expose port (Railway will use $PORT environment variable)
EXPOSE 8080

# Start command (Railway provides $PORT via environment variable)
# Use shell form to ensure PORT variable is expanded
CMD ["/bin/sh", "/start.sh"]
