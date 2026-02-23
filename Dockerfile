# Usa a imagem oficial do PHP com Apache
FROM php:8.1-apache

# Define o diretório de trabalho
WORKDIR /var/www/html

# Instala pacotes adicionais necessários e extensões PHP
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    nano \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo_mysql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# INJETA O COMPOSER NO CONTÊINER (Linha adicionada para corrigir o erro)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Habilita o mod_rewrite para Laravel funcionar corretamente
RUN a2enmod rewrite

# Copia os arquivos do projeto
COPY . /var/www/html

# Copia a configuração local do Apache
COPY ./docker/apache/default.conf /etc/apache2/sites-available/000-default.conf

# Garante que os diretórios 'storage' e 'bootstrap/cache' existam
RUN mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache

# Define permissões corretas nos diretórios
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Expõe a porta 80
EXPOSE 80

# Comando padrão para rodar o Apache
CMD ["apache2-foreground"]