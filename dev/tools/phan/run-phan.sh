#!/bin/bash -xv
# Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>


#
# Usage:
#   Optionally set COMPOSER_CMD to allow running composer.
#   Optionally point COMPOSER_VENDOR_DIR to your vendor path for composer.
#
#   Run phan by calling this script:
#     ./run-phan.sh      # Only checks
#     ./run-phan.sh 1    # Regenerate the baseline.txt file
#
#   You can run this from the root directory of dolibarr
#     dev/tools/run-phan.sh
#
#   You can provide the environment variables on the CLI like this:
#     COMPOSER_CMD="php ~/composer.phar" COMPOSER_VENDOR_DIR="~/vendor" ./run-phan.sh
#
#   or export them:
#     export COMPOSER_CMD="~/composer.phar"
#     export COMPOSER_VENDOR_DIR="~/vendor"
#     ./run-phan.sh
#
#  Or some other method
#
#

MYDIR=$(dirname "$(realpath "$0")")
PROJECT_DIR=$(realpath "${MYDIR}/../../..")

if [[ $(uname) = CYGWIN* ]] ; then
	MYDIR="$(cygpath -w "$MYDIR")"
	PROJECT_DIR="$(cygpath -w "$PROJECT_DIR")"
fi
BASELINE=${MYDIR}/baseline.txt
PHAN_CONFIG=${PHAN_CONFIG:=${MYDIR}/config.php}
export COMPOSER_VENDOR_DIR=${COMPOSER_VENDOR_DIR:=$MYDIR/vendor}
COMPOSER_CMD=${COMPOSER_CMD:=${MYDIR}/../composer.phar}
PHAN=${COMPOSER_VENDOR_DIR}/bin/phan
PHP=${PHP:=php${MINPHPVERSION}}

# Check if we should use the extended configuration
if [ "$1" = "full" ] ; then
	shift
	PHAN_CONFIG=${MYDIR}/config_extended.php
	BASELINE=${MYDIR}/baseline_extended.txt
fi

if [ "$1" = "1" ] ; then
	shift
	BASELINE_OPT=(--save-baseline "${BASELINE}")
elif [ -r "${BASELINE}" ] ; then
	BASELINE_OPT=(--load-baseline "${BASELINE}")
fi
export BASELINE_OPT



echo "***** $(basename "$0") *****"


if [ ! -x "${PHAN}" ] && [ "$(which phan 2>/dev/null)" != "" ] ; then
	PHAN=phan
fi

if [ ! -x "${PHAN}" ] ; then
	#
	# Check composer is available
	#
	if [ ! -r "${COMPOSER_CMD}" ] ; then
		echo composer is not available. Provide the path by setting COMPOSER_CMD=/path/to/composer
		echo Example: export COMPOSER_CMD="~/composer.phar"
		echo Example: export COMPOSER_CMD="/usr/local/bin/composer"
		exit 1
	fi


	#
	# Install/update phan
	#
	echo Install phan
	if [ ! -r "${PHAN}" ] ; then
		# shellcheck disable=2086
		[[ ! -e "${COMPOSER_VENDOR_DIR}" ]] && "${PHP}" ${COMPOSER_CMD} install
		# shellcheck disable=2086
		[[ -e "${COMPOSER_VENDOR_DIR}" ]] && "${PHP}" ${COMPOSER_CMD} update
		"${PHP}" "${COMPOSER_CMD}" require --dev phan/phan
		echo
	fi
fi

(
	echo "cd '${PROJECT_DIR}'"
	cd "${PROJECT_DIR}" || exit
	echo "\"${PHAN}\" --analyze-twice --config-file \"${PHAN_CONFIG}\" --memory-limit 4096M ${BASELINE_OPT}""$*"
	# shellcheck disable=2086,2090
	"${PHAN}" --analyze-twice --config-file "${PHAN_CONFIG}" --memory-limit 4096M "${BASELINE_OPT[@]}" "$@"
)
