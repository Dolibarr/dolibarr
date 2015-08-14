FROM php:5.6-apache

RUN apt-get update && apt-get install -y libpng12-dev libjpeg-dev \
	&& rm -rf /var/lib/apt/lists/* \
	&& docker-php-ext-configure gd --with-png-dir=/usr --with-jpeg-dir=/usr \
	&& docker-php-ext-install gd

RUN docker-php-ext-install mysqli

COPY htdocs/ /var/www/html/

RUN chown -hR www-data:www-data /var/www/html

EXPOSE 80