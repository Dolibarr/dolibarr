FROM php:7.0-apache

ENV HOST_USER_ID 33
ENV PHP_INI_DATE_TIMEZONE 'UTC'

RUN apt-get update && apt-get install -y libpng12-dev libjpeg-dev libldap2-dev \
	&& rm -rf /var/lib/apt/lists/* \
	&& docker-php-ext-configure gd --with-png-dir=/usr --with-jpeg-dir=/usr \
	&& docker-php-ext-install gd \
	&& docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu/ \
        && docker-php-ext-install ldap \
        && docker-php-ext-install mysqli \
        && apt-get purge -y libpng12-dev libjpeg-dev libldap2-dev

COPY docker-run.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-run.sh

EXPOSE 80

ENTRYPOINT ["docker-run.sh"]
