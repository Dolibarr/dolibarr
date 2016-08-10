FROM php:5.6-apache

ENV DOLIBARR_RELEASE 3.9.3

RUN apt-get update && apt-get install -y libpng12-dev libjpeg-dev \
	&& rm -rf /var/lib/apt/lists/* \
	&& docker-php-ext-configure gd --with-png-dir=/usr --with-jpeg-dir=/usr \
	&& docker-php-ext-install gd

RUN docker-php-ext-install mysqli

RUN curl -L https://github.com/Dolibarr/dolibarr/archive/${DOLIBARR_RELEASE}.tar.gz \
        > /tmp/${DOLIBARR_RELEASE}.tar.gz \
        && tar xzf /tmp/${DOLIBARR_RELEASE}.tar.gz -C /tmp \
        && cp -ar /tmp/dolibarr-${DOLIBARR_RELEASE}/htdocs/* /var/www/html/ \
        && rm -rf /tmp/dolibarr-${DOLIBARR_RELEASE} /tmp/${DOLIBARR_RELEASE}.tar.gz

EXPOSE 80
