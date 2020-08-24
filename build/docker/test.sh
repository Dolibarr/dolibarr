#!/bin/bash

set -e

BASE_DIR="$( cd "$(dirname "$0")" && pwd )"

DOLI_VER=${1}
PHP_VER=${2:-""}

rm -rf ${BASE_DIR}/docker-compose-links/ && mkdir ${BASE_DIR}/docker-compose-links

for directory in $(ls ${BASE_DIR}/images); do
  dolibarrVersion=`echo ${directory} | cut -d\- -f1`
  phpVersion=`echo ${directory} | cut -d\- -f2`

  dolibarrMajor=`echo ${dolibarrVersion} | cut -d. -f1`
  phpMajor=`echo ${phpVersion} | cut -d. -f1`

  ln -nfs ${BASE_DIR}/images/${directory} ${BASE_DIR}/docker-compose-links/${directory}
  if [ "${phpMajor}" = "php7" ]; then
    ln -nfs ${BASE_DIR}/images/${directory} ${BASE_DIR}/docker-compose-links/${dolibarrVersion}
  fi
done

echo "Testing for:"
echo " - Dolibarr ${DOLI_VER}"
if [ "${PHP_VER}" = "" ]; then
  echo " - PHP most recent"
  echo "Building image ..."
  DOLI_VERSION=${DOLI_VER} PHP_VERSION="" docker-compose -f ${BASE_DIR}/docker-compose.yml down 1> /dev/null 2>/dev/null
  DOLI_VERSION=${DOLI_VER} PHP_VERSION="" docker-compose -f ${BASE_DIR}/docker-compose.yml build web
  DOLI_VERSION=${DOLI_VER} PHP_VERSION="" docker-compose -f ${BASE_DIR}/docker-compose.yml up --force-recreate web
  DOLI_VERSION=${DOLI_VER} PHP_VERSION="" docker-compose -f ${BASE_DIR}/docker-compose.yml down
else
  echo " - PHP ${PHP_VER}"
  echo "Building image ..."
  DOLI_VERSION=${DOLI_VER} PHP_VERSION="-php${PHP_VER}" docker-compose -f ${BASE_DIR}/docker-compose.yml down 1> /dev/null
  DOLI_VERSION=${DOLI_VER} PHP_VERSION="-php${PHP_VER}" docker-compose -f ${BASE_DIR}/docker-compose.yml build web
  DOLI_VERSION=${DOLI_VER} PHP_VERSION="-php${PHP_VER}" docker-compose -f ${BASE_DIR}/docker-compose.yml up --force-recreate web
  DOLI_VERSION=${DOLI_VER} PHP_VERSION="-php${PHP_VER}" docker-compose -f ${BASE_DIR}/docker-compose.yml down
fi
