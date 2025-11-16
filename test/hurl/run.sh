#!/bin/bash
# Script to run unit tests on API with hurl

# default is to do all tests
TEST_API=true
TEST_GUI=true
TEST_public=true

for arg in "$@"
do
	case "$arg" in
		--noapi)
		TEST_API=false
		;;
		--nogui)
		TEST_GUI=false
		;;
		--nopublic)
		TEST_public=false
		;;
		*)
		COOKIE=$( grep -c -- '--cookiefile=' <<< "$arg" )
		if [[ 1 -eq ${COOKIE} ]]; then
			COOKIEJAR=$( grep -- '--cookiefile=' <<< "$arg" | sed -e "s/^.*--cookiefile=//" | awk '{print $1}' )
		fi
		JUST=$( grep -c -- '--just=' <<< "$arg" )
		if [[ 1 -eq ${JUST} ]]; then
			JUST_ARG=$( grep -- '--just=' <<< "$arg" | sed -e "s/^.*--just=//" | awk '{print $1}' )
		fi
		;;
	esac
done

if [[ -z ${DOLIHOST+x} ]]; then
	DOLIHOST="localhost"
fi
hostnport="${DOLIHOST}"
if [[ -z ${DOLIPORT+x} ]]; then
	hostnport="${hostnport}:8080"
else
	hostnport="${hostnport}:${DOLIPORT}"
fi
if [[ -z ${DOLISUBURL+x} ]]; then
	DOLISUBURL=""
fi
if [[ "" != "${DOLISUBURL}" ]]; then
	hostnport="${hostnport}/${DOLISUBURL}"
fi

echo "----- Run hurl test on APIs ---"
if ! command -v hurl &> /dev/null; then
	echo The command hurl must be available.
	exit 1
fi

if [[ 1 -eq ${JUST} ]]; then
	find . -type f -iname '*.hurl' | grep "${JUST_ARG}" | xargs -I {} hurl --variable "hostnport=${hostnport}" --header "${DOLAPIKEY}" --test "{}"
	exit $?
fi

echo "First we run tests that do not require authentication"
if [[ "true" == "${TEST_public}" ]]; then
	find public/ -type f -iname '00*.hurl' -exec hurl --variable "hostnport=${hostnport}" --test "{}" + || exit 1
fi
find api/ gui/ -type f -iname '00*.hurl' -exec hurl --variable "hostnport=${hostnport}" --test "{}" + || exit 1

# Now we get ready to run tests that do require authentication
if [[ -z ${DOLAPIKEY+x} ]]; then
	echo "DOLAPIKEY bash variable is unset, no API tests that require authentication"
else
	if [[ "true" == "${TEST_API}" ]]; then
		echo "Now we are ready to run API tests that do require authentication"
		find api/ -type f -iname '10*.hurl' -not -iname '00*.hurl' -exec hurl --variable "hostnport=${hostnport}" --header "${DOLAPIKEY}" --test "{}" + || exit 2
	fi
fi

if [[ "true" == "${TEST_GUI}" ]]; then
	if [[ -s "${COOKIEJAR}" ]]; then
		true
	else
		./save_login_cookie.sh
	fi
	if [[ -z ${COOKIEJAR+x} ]]; then
		COOKIEJAR=/tmp/cookie.jar
	fi
	echo "Now we are ready to run GUI tests that do require authentication"
	find gui/ -type f -iname '10*.hurl' -not -iname 'save_login_cookie.hurl' -not -iname '00*.hurl' -exec hurl --variable "hostnport=${hostnport}" --cookie "${COOKIEJAR}" --test "{}" + || exit 3
	if [[ 0 -eq ${COOKIE} ]]; then
		rm -rf "${COOKIEJAR}"
	fi
fi
