FROM php:8.1-fpm-alpine

# تثبيت المكتبات المطلوبة
RUN apk add --no-cache \
    libc-dev \
    oniguruma-dev \
    libxml2-dev \
    curl \
    wget \
    git \
    zip \
    unzip

# تثبيت PHP Extensions
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mbstring \
    json \
    xml \
    curl \
    gd \
    intl \
    opcache \
    bcmath

# تثبيت Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# تعيين دليل العمل
WORKDIR /var/www/html

# نسخ الملفات
COPY . .

# تثبيت المكتبات
RUN composer install --optimize-autoloader --no-dev

# إنشاء دليل التخزين
RUN mkdir -p storage/logs storage/backups storage/uploads && \
    chmod -R 775 storage/

# تعريض المنافذ
EXPOSE 9000

# تشغيل PHP-FPM
CMD ["php-fpm"]
