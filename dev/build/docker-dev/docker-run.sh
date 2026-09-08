#!/bin/bash
# Script used by the Dockerfile.
# See README.md to know how to create a Dolibarr env with docker
set -ex

if [ "${PHP_INI_DIR}" == "" ]; then
	echo
	echo This script must not be run directly. It is used by the Dockerfile
	echo See README.md
	echo
	exit
fi

if [ "${HOST_USER_ID}" == "" ]; then
	echo "Define HOST_USER_ID to your user ID before starting (example: www-data)"
	exit 1
fi
if [ "${HOST_GROUP_ID}" == "" ]; then
	echo "Define HOST_GROUP_ID to your group ID before starting (example: www-data)"
	exit 1
fi

usermod -u "${HOST_USER_ID}" www-data
groupmod -g "${HOST_GROUP_ID}" www-data

echo "[docker-run] => update '${PHP_INI_DIR}/conf.d/dolibarr-php.ini'"
cat <<EOF > "${PHP_INI_DIR}/conf.d/dolibarr-php.ini"
date.timezone = ${PHP_INI_DATE_TIMEZONE:-UTC}
memory_limit = ${PHP_INI_MEMORY_LIMIT:-256M}
EOF

exec apache2-foreground
