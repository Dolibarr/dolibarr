FROM php:8.1-apache

# Installation des extensions PHP requises
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev \
    libzip-dev zip unzip libxml2-dev \
    libicu-dev \
    && docker-php-ext-configure gd \
        --with-freetype --with-jpeg \
    && docker-php-ext-install \
        gd pdo pdo_mysql mysqli \
       zip xml calendar intl \
    && a2enmod rewrite

# Dossier de travail
WORKDIR /var/www/html
COPY htdocs/ .

# Dossier documents Dolibarr
RUN mkdir -p /var/documents \
    && chown -R www-data:www-data /var/www/html \
    && chown -R www-data:www-data /var/documents

EXPOSE 80
CMD ["apache2-foreground"]
