FROM php:8.2-apache

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    default-mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo_mysql zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN a2dismod -f mpm_event mpm_worker || true \
    && rm -f /etc/apache2/mods-enabled/mpm_event.* \
    && rm -f /etc/apache2/mods-enabled/mpm_worker.* \
    && a2enmod mpm_prefork rewrite

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-scripts

COPY . .

COPY ./docker/apache/default.conf /etc/apache2/sites-available/000-default.conf

RUN composer dump-autoload --optimize \
    && mkdir -p storage/app/geodata storage bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# Ambiente local: Apache na porta 80 (docker-compose: 8080:80).
# Railway: Apache na porta 8080 (Public Networking: target port 8080).
EXPOSE 80 8080

# Recria a configuracao da porta a cada inicializacao, sem substituir
# a substring "Listen 80" dentro de "Listen 8080" repetidas vezes.
# Railway fornece RAILWAY_SERVICE_ID automaticamente.
CMD set -eu; \
    if [ -n "${RAILWAY_SERVICE_ID:-}" ]; then \
        APACHE_PORT=8080; \
    else \
        APACHE_PORT=80; \
    fi; \
    printf 'Listen %s\n' "$APACHE_PORT" > /etc/apache2/ports.conf; \
    sed -E -i "s#<VirtualHost \*:[0-9]+>#<VirtualHost *:${APACHE_PORT}>#" /etc/apache2/sites-available/000-default.conf; \
    if [ -n "${RAILWAY_VOLUME_MOUNT_PATH:-}" ]; then \
        if [ "$RAILWAY_VOLUME_MOUNT_PATH" != "/var/www/html/storage/app/geodata" ]; then \
            echo 'ERRO: O volume GeoJSON deve ser montado em /var/www/html/storage/app/geodata' >&2; \
            exit 1; \
        fi; \
        chown www-data:www-data /var/www/html/storage/app/geodata; \
        chmod 775 /var/www/html/storage/app/geodata; \
    fi; \
    echo "Iniciando Apache na porta ${APACHE_PORT}"; \
    exec apache2-foreground
