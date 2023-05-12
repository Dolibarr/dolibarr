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

if [ ! -f /usr/local/etc/php/php.ini ]; then
  cat <<EOF > /usr/local/etc/php/php.ini
date.timezone = $PHP_INI_DATE_TIMEZONE
EOF
fi

exec apache2-foreground
