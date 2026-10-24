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

# A imagem PHP/Apache precisa do MPM prefork para o mod_php.
# Remove outros MPMs e deixa apenas prefork habilitado no build.
RUN rm -f /etc/apache2/mods-enabled/mpm_*.load /etc/apache2/mods-enabled/mpm_*.conf \
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

# Em desenvolvimento: porta 80 (docker-compose publica 8080:80).
# No Railway: porta interna 8080, conforme Public Networking.
EXPOSE 80 8080

# IMPORTANTE: Railway pode modificar os modulos carregados no startup.
# Limpar e reativar SOMENTE mpm_prefork evita AH00534 (mais de um MPM).
CMD set -eu; \
    rm -f /etc/apache2/mods-enabled/mpm_*.load /etc/apache2/mods-enabled/mpm_*.conf; \
    a2enmod mpm_prefork rewrite >/dev/null; \
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
    echo "Iniciando Apache na porta ${APACHE_PORT} com MPM prefork"; \
    apache2ctl configtest; \
    exec apache2-foreground
