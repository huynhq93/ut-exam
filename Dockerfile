FROM php:8.2-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libzip-dev \
    zip \
    unzip

# Install PHP extensions
RUN docker-php-ext-install zip pdo pdo_mysql

# Install Xdebug
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Copy Xdebug configuration
COPY docker/php/conf.d/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files first
COPY composer.json ./

# Install dependencies
RUN composer update --no-scripts

# Copy source code
COPY . .

# Generate autoload files
RUN composer dump-autoload

# Default command to keep container running
CMD ["php", "-a"] 