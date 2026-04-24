FROM dunglas/frankenphp:php8.3

WORKDIR /app

# Install system dependencies required by Composer
RUN apt-get update && apt-get install -y --no-install-recommends \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

# Install required PHP extensions
RUN install-php-extensions pdo_mysql mbstring opcache zip

# Populate $_ENV from process environment (needed for Railway injected vars)
RUN echo "variables_order = EGPCS" > $PHP_INI_DIR/conf.d/env-vars.ini

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copy application files
COPY . .

RUN mkdir -p uploads/avatars

EXPOSE 80

CMD ["frankenphp", "run", "--config", "/app/Caddyfile", "--adapter", "caddyfile"]
