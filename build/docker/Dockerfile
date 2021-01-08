FROM php:7.3-apache

ENV PHP_INI_DATE_TIMEZONE 'UTC'
ENV PHP_INI_MEMORY_LIMIT 256M

RUN apt-get update -y \
    && apt-get install -y --no-install-recommends \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libjpeg62-turbo \
        libpng-dev \
        libpng16-16 \
        libldap2-dev \
        libxml2-dev \
        libzip-dev \
        zlib1g-dev \
        libicu-dev \
        g++ \
        default-mysql-client \
        unzip \
        curl \
        apt-utils \
        msmtp \
        msmtp-mta \
        mailutils \
    && apt-get autoremove -y \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-configure gd --with-png-dir=/usr --with-jpeg-dir=/usr \
    && docker-php-ext-install -j$(nproc) calendar intl mysqli pdo_mysql gd soap zip \
    && docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu/ \
    && docker-php-ext-install -j$(nproc) ldap && \
    mv ${PHP_INI_DIR}/php.ini-development ${PHP_INI_DIR}/php.ini

RUN mkdir /var/documents
RUN chown www-data:www-data /var/documents

COPY docker-run.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-run.sh

RUN pecl install xdebug && docker-php-ext-enable xdebug
RUN echo 'zend_extension="/usr/local/lib/php/extensions/no-debug-non-zts-20180731/xdebug.so"' >> ${PHP_INI_DIR}/php.ini
RUN echo 'xdebug.remote_autostart=1' >> ${PHP_INI_DIR}/php.ini
RUN echo 'xdebug.remote_enable=1' >> ${PHP_INI_DIR}/php.ini
RUN echo 'xdebug.default_enable=1' >> ${PHP_INI_DIR}/php.ini
#RUN echo 'xdebug.remote_host=docker.host' >> ${PHP_INI_DIR}/php.ini
RUN echo 'xdebug.remote_port=9000' >> ${PHP_INI_DIR}/php.ini
RUN echo 'xdebug.remote_connect_back=1' >> ${PHP_INI_DIR}/php.ini
RUN echo 'xdebug.profiler_enable=0' >> ${PHP_INI_DIR}/php.ini
RUN echo 'xdebug.remote_log="/tmp/xdebug.log"' >> ${PHP_INI_DIR}/php.ini
#RUN echo 'localhost docker.host' >> /etc/hosts

# set up sendmail config, to use maildev
RUN echo "account default" > /etc/msmtprc
RUN echo "auth off" >> /etc/msmtprc
RUN echo "port 25" >> /etc/msmtprc
RUN echo "host mail" >> /etc/msmtprc
RUN echo "from local@localdomain.com" >> /etc/msmtprc
RUN echo "domain localhost.localdomain" >> /etc/msmtprc
RUN echo "sendmail_path=/usr/bin/msmtp -t" >> /usr/local/etc/php/conf.d/php-sendmail.ini
RUN echo "localhost localhost.localdomain" >> /etc/hosts

EXPOSE 80

ENTRYPOINT ["docker-run.sh"]
