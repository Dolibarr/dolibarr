FROM php:5.6-apache

RUN apt-get update && apt-get install -y php5-gd php5-mysql

COPY htdocs/ /var/www/html/

RUN chown -hR www-data:www-data /var/www/html

EXPOSE 80