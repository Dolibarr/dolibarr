#!/bin/bash
# Script compatible with Cygwin
# When argument is '1', save baseline
#
# Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>

MYPATH=$(realpath "$(dirname "$(readlink -f "$0")")/../../..")
if [[ $(uname) = CYGWIN* ]] ; then
	MYPATH="$(cygpath -w "$MYPATH")"
fi

# BASELINE_PATH=.phan/baseline.txt
CONFIG_PATH=dev/tools/phan/config.php
BASELINE_PATH=dev/tools/phan/baseline.txt

# When full is provided as an argument,
# still use the baseline, but verify all
# rules.
if [ "$1" = "full" ] || [ "$2" = "full" ] ; then
	CONFIG_PATH=dev/tools/phan/config_extended.php
fi

if [ "$1" = "1" ] ; then
	docker run -v "$MYPATH:/mnt/src" phanphp/phan:latest  -k /mnt/src/${CONFIG_PATH} --analyze-twice --save-baseline /mnt/src/${BASELINE_PATH}
else
	docker run -v "$MYPATH:/mnt/src" phanphp/phan:latest  -k /mnt/src/${CONFIG_PATH} -B /mnt/src/${BASELINE_PATH} --analyze-twice
fi
