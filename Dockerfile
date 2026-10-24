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

EXPOSE 80

# No Railway, o Volume e montado somente na inicializacao do container.
# Como ele pertence inicialmente a root, concedemos acesso ao Apache (www-data).
# Fora do Railway, mantemos a inicializacao local como antes.
CMD rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.* \
    && a2enmod mpm_prefork rewrite >/dev/null \
    && if [ -n "${RAILWAY_VOLUME_MOUNT_PATH:-}" ]; then \
         if [ "$RAILWAY_VOLUME_MOUNT_PATH" != "/var/www/html/storage/app/geodata" ]; then \
           echo "ERRO: Configure o Railway Volume em /var/www/html/storage/app/geodata" >&2; \
           exit 1; \
         fi; \
         chown www-data:www-data /var/www/html/storage/app/geodata \
         && chmod 775 /var/www/html/storage/app/geodata; \
       fi \
    && sed -i "s/Listen 80/Listen ${PORT:-80}/" /etc/apache2/ports.conf \
    && sed -i "s#<VirtualHost \*:80>#<VirtualHost *:${PORT:-80}>#" /etc/apache2/sites-available/000-default.conf \
    && apache2-foreground
