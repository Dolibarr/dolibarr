FROM php:7.0-apache

ENV HOST_USER_ID 33
ENV PHP_INI_DATE_TIMEZONE 'UTC'

RUN apt-get update && apt-get install -y libpng-dev libjpeg-dev libldap2-dev \
	&& rm -rf /var/lib/apt/lists/* \
	&& docker-php-ext-configure gd --with-png-dir=/usr --with-jpeg-dir=/usr \
	&& docker-php-ext-install gd \
	&& docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu/ \
        && docker-php-ext-install ldap \
        && docker-php-ext-install mysqli \
        && apt-get purge -y libjpeg-dev libldap2-dev

COPY docker-run.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-run.sh

RUN pecl install xdebug-2.5.5 && docker-php-ext-enable xdebug
RUN echo 'zend_extension="/usr/local/lib/php/extensions/no-debug-non-zts-20151012/xdebug.so"' >> /usr/local/etc/php/php.ini
RUN echo 'xdebug.remote_autostart=0' >> /usr/local/etc/php/php.ini
RUN echo 'xdebug.remote_enable=1' >> /usr/local/etc/php/php.ini
RUN echo 'xdebug.default_enable=0' >> /usr/local/etc/php/php.ini
RUN echo 'xdebug.remote_host=docker.for.mac.host.internal' >> /usr/local/etc/php/php.ini
RUN echo 'xdebug.remote_port=9000' >> /usr/local/etc/php/php.ini
RUN echo 'xdebug.remote_connect_back=0' >> /usr/local/etc/php/php.ini
RUN echo 'xdebug.profiler_enable=0' >> /usr/local/etc/php/php.ini
RUN echo 'xdebug.remote_log="/tmp/xdebug.log"' >> /usr/local/etc/php/php.ini


EXPOSE 80

ENTRYPOINT ["docker-run.sh"]
