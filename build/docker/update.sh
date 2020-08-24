#!/bin/bash

set -e

DOCKER_BUILD=${DOCKER_BUILD:-0}
DOCKER_PUSH=${DOCKER_PUSH:-0}

BASE_DIR="$( cd "$(dirname "$0")" && pwd )"

source "${BASE_DIR}/versions.sh"

tags=""

rm -rf ${BASE_DIR}/images ${BASE_DIR}/docker-compose-links

for dolibarrVersion in ${DOLIBARR_VERSIONS[@]}; do
  echo "Generate Dockerfile for Dolibarr ${dolibarrVersion}"

  tags="${tags}\n\* "
  dolibarrMajor=`echo ${dolibarrVersion} | cut -d. -f1`

  # Mapping version according https://wiki.dolibarr.org/index.php/Versions
  # Regarding PHP Supported version : https://www.php.net/supported-versions.php
  if [ "${dolibarrMajor}" = "6" ]; then #Version discontinued
    php_base_images=( "5.6-apache-stretch" "7.1-apache-stretch" )
  elif [ "${dolibarrMajor}" = "7" ]; then
    php_base_images=( "5.6-apache-stretch" "7.2-apache-stretch" )
  elif [ "${dolibarrMajor}" = "8" ]; then
    php_base_images=( "5.6-apache-stretch" "7.2-apache-stretch" )
  elif [ "${dolibarrMajor}" = "9" ]; then
    php_base_images=( "5.6-apache-stretch" "7.3-apache-stretch" )
  elif [ "${dolibarrMajor}" = "10" ]; then
    php_base_images=( "5.6-apache-stretch" "7.3-apache-stretch" )
  elif [ "${dolibarrMajor}" = "11" ]; then
    php_base_images=( "5.6-apache-stretch" "7.4-apache" )
  elif [ "${dolibarrMajor}" = "12" ]; then
    php_base_images=( "5.6-apache-stretch" "7.4-apache" )
  else
    php_base_images=( "7.4-apache" )
  fi

  for php_base_image in ${php_base_images[@]}; do

    php_version=`echo ${php_base_image} | cut -d\- -f1`
    currentTag="${dolibarrVersion}-php${php_version}"
    dir=${BASE_DIR}/"images/${currentTag}"
    tags="${tags}${currentTag} "

    if [ "${php_version}" = "7.4" ]; then
      gd_config_args="\-\-with\-freetype\ \-\-with\-jpeg"
    else
      gd_config_args="\-\-with\-png\-dir=\/usr\ \-\-with-jpeg-dir=\/usr"
    fi

    mkdir -p ${dir}
    cat ${BASE_DIR}/Dockerfile.template | \
    sed 's/%PHP_BASE_IMAGE%/'"${php_base_image}"'/;'  | \
    sed 's/%DOLI_VERSION%/'"${dolibarrVersion}"'/;' | \
    sed 's/%GD_CONFIG_ARG%/'"${gd_config_args}"'/;' \
    > ${dir}/Dockerfile

    cp ${BASE_DIR}/docker-run.sh ${dir}/docker-run.sh

    if [ ${DOCKER_BUILD} -eq 1 ]; then
      docker build --compress --tag tuxgasy/dolibarr:${currentTag} ${dir}
    fi
    if [ ${DOCKER_PUSH} -eq 1 ]; then
      docker push tuxgasy/dolibarr:${currentTag}
    fi
  done

  if [ ${DOCKER_BUILD} -eq 1 ]; then
    docker tag tuxgasy/dolibarr:${currentTag} tuxgasy/dolibarr:${dolibarrVersion}
    docker tag tuxgasy/dolibarr:${currentTag} tuxgasy/dolibarr:${dolibarrMajor}
  fi
  if [ ${DOCKER_PUSH} -eq 1 ]; then
    docker push tuxgasy/dolibarr:${dolibarrVersion}
    docker push tuxgasy/dolibarr:${dolibarrMajor}
  fi

  tags="${tags}${dolibarrVersion} ${dolibarrMajor}"
done

if [ ${DOCKER_BUILD} -eq 1 ]; then
  docker tag tuxgasy/dolibarr:${dolibarrVersion} tuxgasy/dolibarr:latest
fi
if [ ${DOCKER_PUSH} -eq 1 ]; then
  docker push tuxgasy/dolibarr:latest
fi

tags="${tags} latest"

sed 's/%TAGS%/'"${tags}"'/' ${BASE_DIR}/README.template > ${BASE_DIR}/README.md
