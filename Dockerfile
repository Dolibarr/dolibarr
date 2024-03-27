ARG ARCH=

FROM ${ARCH}php:8.2-apache-buster

ENV DOLI_VERSION 19.0.0
ENV DOLI_INSTALL_AUTO 1
ENV DOLI_PROD 1

ENV DOLI_DB_TYPE mysqli
ENV DOLI_DB_HOST mysql
ENV DOLI_DB_HOST_PORT 3306
ENV DOLI_DB_NAME dolidb

ENV DOLI_URL_ROOT 'http://localhost'
ENV DOLI_NOCSRFCHECK 0

ENV DOLI_AUTH dolibarr
ENV DOLI_LDAP_HOST 127.0.0.1
ENV DOLI_LDAP_PORT 389
ENV DOLI_LDAP_VERSION 3
ENV DOLI_LDAP_SERVER_TYPE openldap
ENV DOLI_LDAP_LOGIN_ATTRIBUTE uid
ENV DOLI_LDAP_DN 'ou=users,dc=my-domain,dc=com'
ENV DOLI_LDAP_FILTER ''
ENV DOLI_LDAP_BIND_DN ''
ENV DOLI_LDAP_BIND_PASS ''
ENV DOLI_LDAP_DEBUG false

ENV DOLI_CRON 0

ENV WWW_USER_ID 33
ENV WWW_GROUP_ID 33

ENV PHP_INI_DATE_TIMEZONE 'UTC'
ENV PHP_INI_MEMORY_LIMIT 256M
ENV PHP_INI_UPLOAD_MAX_FILESIZE 2M
ENV PHP_INI_POST_MAX_SIZE 8M
ENV PHP_INI_ALLOW_URL_FOPEN 0

RUN sed -i \
  -e 's/^\(ServerSignature On\)$/#\1/g' \
  -e 's/^#\(ServerSignature Off\)$/\1/g' \
  -e 's/^\(ServerTokens\) OS$/\1 Prod/g' \
  /etc/apache2/conf-available/security.conf

RUN apt-get update -y \
    && apt-get dist-upgrade -y \
    && apt-get install -y --no-install-recommends \
        libc-client-dev \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libkrb5-dev \
        libldap2-dev \
        libpng-dev \
        libpq-dev \
        libxml2-dev \
        libzip-dev \
        default-mysql-client \
        postgresql-client \
        cron \
    && apt-get autoremove -y \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) calendar intl mysqli pdo_mysql gd soap zip \
    && docker-php-ext-configure pgsql -with-pgsql \
    && docker-php-ext-install pdo_pgsql pgsql \
    && docker-php-ext-configure ldap --with-libdir=lib/$(gcc -dumpmachine)/ \
    && docker-php-ext-install -j$(nproc) ldap \
    && docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
    && docker-php-ext-install imap \
    && mv ${PHP_INI_DIR}/php.ini-production ${PHP_INI_DIR}/php.ini \
    && rm -rf /var/lib/apt/lists/*

# Get Dolibarr
COPY htdocs/* /var/www/html/
COPY scripts /var/www/

RUN ln -s /var/www/html /var/www/htdocs && \
    rm -rf /tmp/* && \
    mkdir -p /var/www/documents && \
    mkdir -p /var/www/html/custom && \
    chown -R www-data:www-data /var/www

EXPOSE 80
VOLUME /var/www/documents
VOLUME /var/www/html/custom

COPY docker-init.php /var/www/scripts/
COPY docker-run.sh /usr/local/bin/
ENTRYPOINT ["docker-run.sh"]

CMD ["apache2-foreground"]
