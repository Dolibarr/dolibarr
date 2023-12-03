#!/bin/bash

#
# Usage:
#   Optionally set COMPOSER_CMD to the command to use to run composer.
#   Optionally set COMPOSER_VENDOR_DIR to your vendor path for composer.
#
#   Run php-cs-fixer by calling this script:
#     ./run-php-cs-fixer.sh check    # Only checks
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
#     export COMPOSER_CMD="php ~/composer.phar"
#     export COMPOSER_VENDOR_DIR="~/vendor"
#     ./run-php-cs-fixer.sh
#
#  Or some other method
#


MYDIR=$(dirname "$(realpath "$0")")
export COMPOSER_VENDOR_DIR=${COMPOSER_VENDOR_DIR:=$MYDIR/vendor}
COMPOSER_CMD=${COMPOSER_CMD:=composer}

#
# Install/update
#
PHP_CS_FIXER="${COMPOSER_VENDOR_DIR}/bin/php-cs-fixer"
if [ ! -r "${PHP_CS_FIXER}" ] ; then
  [[ ! -e "${COMPOSER_VENDOR_DIR}" ]] && ${COMPOSER_CMD} install
  [[ -e "${COMPOSER_VENDOR_DIR}" ]] && ${COMPOSER_CMD} update
  ${COMPOSER_CMD} require --dev friendsofphp/php-cs-fixer
fi


if [ "x$1" = "x" ]; then
	echo "***** run-php-cs-fixer.sh *****"
	echo "Syntax: run-php-cs-fixer.sh check|fix  [path]"
	exit 1;
fi

(
  cd "${MYDIR}/../../.." || exit
  CMD=
  # If no argument, run check by default
  [[ "$1" == "" ]] && CMD=check
  # shellcheck disable=SC2086
  "${PHP_CS_FIXER}" $CMD "$@"
)
