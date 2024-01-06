#!/bin/bash

#
# Usage:
#   Optionally set COMPOSER_CMD to the command to use to run composer.
#   Optionally set COMPOSER_VENDOR_DIR to your vendor path for composer.
#
#   Run php-cs-fixer by calling this script:
#     ./run-php-cs-fixer.sh check    # Only checks (not available with PHP 7.0)
#     ./run-php-cs-fixer.sh fix      # Fixes
#
#   You can fix only a few files using
#     ./run-php-cs-fixer.sh fix htdocs/path/to/myfile.php
#
#   You can run this from the root directory of dolibarr
#     dev/tools/run-php-cs-fixer.sh fix htdocs/path/to/myfile.php
#
#   You can provide the environment variables on the CLI like this:
#     COMPOSER_CMD="php ~/composer.phar" COMPOSER_VENDOR_DIR="~/vendor" ./run-php-cs-fixer.sh
#
#   or export them:
#     export COMPOSER_CMD="~/composer.phar"
#     export COMPOSER_VENDOR_DIR="~/vendor"
#     ./run-php-cs-fixer.sh
#
#  Or some other method
#


MYDIR=$(dirname "$(realpath "$0")")
export COMPOSER_VENDOR_DIR=${COMPOSER_VENDOR_DIR:=$MYDIR/vendor}
COMPOSER_CMD=${COMPOSER_CMD:composer}
MINPHPVERSION="7.0"


echo "***** run-php-cs-fixer.sh *****"

if [ "x$1" = "x" ]; then
	echo "Syntax: run-php-cs-fixer.sh check|fix  [path_from_root_project]"
	exit 1;
fi


#
# Check composer is available
#
if [ ! -r "${COMPOSER_CMD}" ] ; then
	echo composer is not available or not in path. You can give the path of composer by setting COMPOSER_CMD=/pathto/composer
	echo Example: export COMPOSER_CMD="~/composer.phar"
	echo Example: export COMPOSER_CMD="/usr/local/bin/composer"
	exit 1;
fi


#
# Install/update php-cs-fixer
#
echo Install php-cs-fixer
PHP_CS_FIXER="${COMPOSER_VENDOR_DIR}/bin/php-cs-fixer"
if [ ! -r "${PHP_CS_FIXER}" ] ; then
  [[ ! -e "${COMPOSER_VENDOR_DIR}" ]] && ${COMPOSER_CMD} install
  [[ -e "${COMPOSER_VENDOR_DIR}" ]] && ${COMPOSER_CMD} update
  php${MINPHPVERSION} ${COMPOSER_CMD} require --dev friendsofphp/php-cs-fixer
  echo
fi


# With PHP 7.0, php-cs-fixer is V2 (command check not supported)
# With PHP 8.2, php-cs-fixer is V3
 
(
  echo cd "${MYDIR}/../../.."
  cd "${MYDIR}/../../.." || exit
  CMD=
  # If no argument, run check by default
  [[ "$1" == "" ]] && CMD=check
  # shellcheck disable=SC2086
  echo php${MINPHPVERSION} "${PHP_CS_FIXER}" $CMD "$@"
  php${MINPHPVERSION} "${PHP_CS_FIXER}" $CMD "$@"
)
