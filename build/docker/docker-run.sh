#!/bin/bash
# Script used by the Dockerfile.
# See README.md to know how to create a Dolibarr env with docker 

usermod -u ${HOST_USER_ID} www-data
groupmod -g ${HOST_USER_ID} www-data

chgrp -hR www-data /var/www/html
chmod g+rwx /var/www/html/conf

if [ ! -d /var/documents ]; then
	echo "[docker-run] => create volume directory /var/documents ..."
	mkdir -p /var/documents
fi
echo "[docker-run] => Set Permission to www-data for /var/documents"
chown -R www-data:www-data /var/documents

echo "[docker-run] => update ${PHP_INI_DIR}/conf.d/dolibarr-php.ini"
cat <<EOF > ${PHP_INI_DIR}/conf.d/dolibarr-php.ini
date.timezone = ${PHP_INI_DATE_TIMEZONE:-UTC}
memory_limit = ${PHP_INI_MEMORY_LIMIT:-256M}
EOF

exec apache2-foreground
