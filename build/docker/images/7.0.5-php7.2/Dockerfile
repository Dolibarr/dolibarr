FROM php:7.2-apache-stretch

MAINTAINER Garcia MICHEL <garcia@soamichel.fr>

ENV DOLI_INSTALL_AUTO 1

ENV DOLI_DB_HOST mysql
ENV DOLI_DB_USER doli
ENV DOLI_DB_PASSWORD doli_pass
ENV DOLI_DB_NAME dolidb

ENV DOLI_ADMIN_LOGIN admin
ENV DOLI_ADMIN_PASSWORD admin
ENV DOLI_URL_ROOT 'http://localhost'
ENV DOLI_NOCSRFCHECK 0

ENV WWW_USER_ID 33
ENV WWW_GROUP_ID 33

ENV PHP_INI_DATE_TIMEZONE 'UTC'
ENV PHP_INI_MEMORY_LIMIT 256M

RUN apt-get update -y \
    && apt-get install -y --no-install-recommends \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libldap2-dev \
        libxml2-dev \
        libzip-dev \
        default-mysql-client \
        unzip \
    && apt-get autoremove -y \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-configure gd --with-png-dir=/usr --with-jpeg-dir=/usr \
    && docker-php-ext-install -j$(nproc) calendar intl mysqli pdo_mysql gd soap zip \
    && docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu/ \
    && docker-php-ext-install -j$(nproc) ldap && \
    mv ${PHP_INI_DIR}/php.ini-production ${PHP_INI_DIR}/php.ini

ENV DOLI_VERSION 7.0.5

# Get Dolibarr
ADD https://github.com/Dolibarr/dolibarr/archive/${DOLI_VERSION}.zip /tmp/dolibarr.zip
RUN unzip -q /tmp/dolibarr.zip -d /tmp/dolibarr && \
    cp -r /tmp/dolibarr/dolibarr-${DOLI_VERSION}/htdocs/* /var/www/html/ && \
    ln -s /var/www/html /var/www/htdocs && \
    cp -r /tmp/dolibarr/dolibarr-${DOLI_VERSION}/scripts /var/www/ && \
    rm -rf /tmp/* && \
    mkdir -p /var/www/documents && \
    chown -R www-data:www-data /var/www

EXPOSE 80
VOLUME /var/www/documents

COPY docker-run.sh /usr/local/bin/
ENTRYPOINT ["docker-run.sh"]
