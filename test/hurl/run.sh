#!/bin/bash
# Copyright (C) 2025		Jon Bendtsen	<jon.bendtsen.github@jonb.dk>
# Copyright (C) 2025		MDW		<mdeweerd@users.noreply.github.com>

# Script to run unit tests on API with hurl
#
MYDIR=$(dirname "$(realpath "$0")")
cd "${MYDIR}" || exit 100

# Function to display help message
display_help() {
	cat <<EOHELP
Usage: $0 [OPTIONS] [TEST_FILTERS]

Run hurl tests on Dolibarr API, GUI, and public endpoints.

Options:
  --cookiefile=FILE    Specify the cookie file to use for GUI tests.
  --port=PORT          Specify the port number of the Dolibarr server.
  --host=HOST          Specify the host address of the Dolibarr server.
  --user=USERNAME      Specify the username for GUI tests.
  --pass=PASSWORD      Specify the password for GUI tests.
  --apikey=APIKEY      Specify the API key for API tests.
  --suburl=SUBURL      Specify the suburl of the Dolibarr server.
  --exclude=PATTERN    Exclude tests that match the specified pattern.
  --verbose | -v       Verbose hurl output
  --very-verbose       Very verbose hurl output
  --quiet | -q         Disable info output
  --help               Display this help message and exit.

Test Filters:
  You can provide partial test names as arguments to run specific tests.
  For example: $0 setup_modules status

Exclude Patterns:
  You can provide partial test names as arguments to exclude specific tests.
  For example: $0 --exclude=setup_modules --exclude=status

Examples:
  $0 --cookiefile=/path/to/cookie.jar --port=8080 --host=http://example.net --user=foobar --pass=topsecret --apikey=your_api_key --suburl=/dolibarr setup_modules
  $0 --exclude=setup_modules --exclude=status
  $0 setup_modules status
EOHELP
}

if [[ -n "${GITHUB_WORKSPACE}" ]]; then
	# Github compatible messages
	print_error() { printf "::error::%s\n" "$*" >&2; }
	print_warning() { if [[ "${QUIET}" = false ]]; then printf "::warning::%s\n" "$*" >&2 ; fi; }
	print_info() { if [[ "${QUIET}" = false ]]; then printf "::notice::%s\n" "$*" >&2 ; fi; }
else
	print_error() { printf "ERROR: %s\n" "$*" >&2; }
	print_warning() { if [[ "${QUIET}" = false ]]; then printf "WARNING: %s\n" "$*" >&2 ; fi; }
	print_info() { if [[ "${QUIET}" = false ]]; then printf "INFO: %s\n" "$*" >&2 ; fi; }
fi

# Parse command-line arguments as test filters
test_filters=()
exclude_patterns=()
QUIET=false

for arg in "$@"; do
	case "$arg" in
		--cookiefile=*)
			export COOKIEJAR="${arg#--cookiefile=}"
			;;
		--port=*)
			export DOLIPORT="${arg#--port=}"
			;;
		--host=*)
			export DOLIHOST="${arg#--host=}"
			;;
		--user=*)
			export DOLIUSERNAME="${arg#--user=}"
			;;
		--pass=*)
			export DOLIPASSWORD="${arg#--pass=}"
			;;
		--apikey=*)
			export DOLAPIKEY="${arg#--apikey=}"
			if [[ "${DOLAPIKEY}" != *": "* ]]; then
				DOLAPIKEY="DOLAPIKEY: ${DOLAPIKEY}"
			fi
			;;
		--suburl=*)
			export DOLISUBURL="${arg#--suburl=}"
			;;
		--exclude=*)
			exclude_patterns+=("${arg#--exclude=}")
			;;
		--very-verbose|--verbose|-v)
			VERBOSE=${arg}
			;;
		--quiet|-q)
			QUIET=true
			;;
		--help)
			display_help
			exit 0
			;;
		-*)
			print_error "Unknown option: $arg"
			display_help
			exit 1
			;;
		*)
			test_filters+=("$arg")
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

# Clean up test filters to remove anything up to 'hurl' and the directory separator following it included
for i in "${!test_filters[@]}"; do
	test_filters[i]="${test_filters[i]#*hurl/}"
done

echo
echo "----- Run hurl test on APIs ---"
if ! command -v hurl &> /dev/null; then
	HURL_INSTALL_URL="https://hurl.dev/docs/installation.html"
	print_error "hurl not found. See installation instructions: ${HURL_INSTALL_URL}"

	case "$(uname -s)" in
		Linux*)
			ID=unknown
			if [ -f /etc/os-release ]; then
				# shellcheck disable=1091
				. /etc/os-release
			fi
			case "${ID}" in
				ubuntu)
					echo "For Ubuntu >=18.04, Hurl can be installed from ppa:lepapareil/hurl"
					echo "    VERSION=7.0.0"
					echo "    sudo apt-add-repository -y ppa:lepapareil/hurl"
					echo "    sudo apt install hurl=\"${VERSION}*\""
					;;
				debian)
					echo "For Debian >=12, Hurl can be installed using a binary .deb file provided in each Hurl release."
					echo "    VERSION=7.0.0"
					echo "    curl --location --remote-name https://github.com/Orange-OpenSource/hurl/releases/download/${VERSION}/hurl_${VERSION}_amd64.deb"
					echo "    sudo apt update && sudo apt install ./hurl_${VERSION}_amd64.deb"
					;;
				alpine)
					echo "For Alpine, Hurl is available on testing channel."
					echo "    apk add --repository http://dl-cdn.alpinelinux.org/alpine/edge/testing hurl"
					;;
				arch)
					echo "For Arch Linux / Manjaro, Hurl is available on extra channel."
					echo "    pacman -Sy hurl"
					;;
				*)
					echo "On Linux, try:"
					echo "    INSTALL_DIR=/tmp"
					echo "    VERSION=7.0.0"
					echo "    curl --silent --location https://github.com/Orange-OpenSource/hurl/releases/download/${VERSION}/hurl-${VERSION}-x86_64-unknown-linux-gnu.tar.gz | tar xvz -C ${INSTALL_DIR}"
					echo "    export PATH=${INSTALL_DIR}/hurl-${VERSION}-x86_64-unknown-linux-gnu/bin:$PATH"
					;;
			esac
			;;
		Darwin*)
			echo "On macOS, try:"
			echo "    brew install hurl"
			;;
		CYGWIN*|MINGW*|MSYS*)
			echo "On Windows (with Chocolatey), try:"
			echo "    choco install hurl"
			;;
		*)
			echo "On other systems, try:"
			echo "    See installation instructions: ${HURL_INSTALL_URL}"
			;;
	esac
	print_error "The command hurl must be available."
	exit 1
fi

# Build the find command for the tests that do not require authentication
find_args=("api/" "gui/" "public/" "-type" "f" "-iwholename" "*/00*.hurl")
if [[ -n "${test_filters[*]}" ]]; then
	find_args+=("-and")
	or_prefix="("
	for test_filter in "${test_filters[@]}"; do
		[[ "${test_filter}" == *.hurl ]] || test_filter="${test_filter}*.hurl"
		find_args+=("${or_prefix}" "-iwholename" "*${test_filter}")
		or_prefix="-or"
	done
	find_args+=(")")
fi

if [[ -n "${exclude_patterns[*]}" ]]; then
	find_args+=("-and")
	not_prefix="("
	for exclude_pattern in "${exclude_patterns[@]}"; do
		[[ "${exclude_pattern}" == *.hurl ]] || exclude_pattern="${exclude_pattern}*.hurl"
		find_args+=("${not_prefix}" "-not" "-iwholename" "*${exclude_pattern}")
		not_prefix="-and"
	done
	find_args+=(")")
fi

print_info "1. Running tests (API,GUI,public) that do not require authentication"
# shellcheck disable=SC2086
if ! find "${find_args[@]}" -exec hurl ${VERBOSE} --variable "hostnport=${hostnport}" --test "{}" +; then
	print_warning "1. No tests found or failed to run tests that do not require authentication."
fi

# Now we get ready to run tests that do require authentication

# FUNCTION:
# Retrieves Dolibarr API key if not provided
# Usage: DOLAPIKEY=$(get_dolibarr_api_key "$API_URL" "$DOLAPIKEY" "$DOLIUSERNAME" "$DOLIPASSWORD")
get_dolibarr_api_key() {
	local API_URL=$1
	local current_key=$2
	local username=$3
	local password=$4

	# If key is already set, return it
	if [ -n "${current_key}" ]; then
		if [[ "${DOLAPIKEY}" != *": "* ]]; then
			print_error "Environment variable DOLAPIKEY has the wrong format: '${DOLAPIKEY}'"
			print_error "should perhaps be: 'DOLAPIKEY: ${DOLAPIKEY}'"
		else
			print_info "Using existing DOLAPIKEY."
			echo "${current_key}"
			return 0
		fi
	fi

	# Check if credentials are available
	if [ -z "${username}" ] || [ -z "${password}" ]; then
		print_info "DOLIUSERNAME or DOLIPASSWORD not set. Cannot retrieve API key."
		return 1
	fi

	# API call to get the token
	local response
	response=$(curl -s -f "${API_URL}/login?login=${username}&password=${password}")

	# Parse JSON response using here-strings
	local api_key error_code error_message
	api_key=$(jq -r '.success.token // ""' <<< "${response}" | sed -e 's/[[:space:]]*$//')
	error_code=$(jq -r '.success.code // 0' <<< "${response}" | sed -e 's/[[:space:]]*$//')
	error_message=$(jq -r '.error.message // ""' <<< "${response}" | sed -e 's/[[:space:]]*$//')

	# Check HTTP status code
	if [ "${error_code}" != 200 ]; then
		print_error "HTTP error: '${error_code}'"
		return 1
	fi

	# Check for API errors
	if [ "${error_code}" == 500 ]; then
		print_error "API error: '${error_message}"
		return 1
	fi

	# Check if API key is valid
	if [ -z "${api_key}" ] || [ "${api_key}" = "null" ]; then
		print_error "Failed to parse API key from response"
		return 1
	fi

	# Return the new API key
	echo "DOLAPIKEY: ${api_key}"
	return 0
}

API_URL=${hostnport}/api/index.php
#API_URL=${hostnport}/api

DOLAPIKEY=$(get_dolibarr_api_key "${API_URL}" "${DOLAPIKEY}" "${DOLIUSERNAME}" "${DOLIPASSWORD}")

if [[ -z "${DOLAPIKEY}" ]]; then
	print_info "DOLAPIKEY bash variable is unset/empty, no API tests that require authentication"
else
	# Build the find command for the API tests that do require authentication
	find_args=("api/" "-type" "f" "-iwholename" "*/10*.hurl" "-not" "-iwholename" "*/00*.hurl")
	if [[ -n "${test_filters[*]}" ]]; then
		find_args+=("-and")
		or_prefix="("
		for test_filter in "${test_filters[@]}"; do
			[[ "${test_filter}" == *.hurl ]] || test_filter="${test_filter}*.hurl"
			find_args+=("${or_prefix}" "-iwholename" "*${test_filter}")
			or_prefix="-or"
		done
		find_args+=(")")
	fi

	if [[ -n "${exclude_patterns[*]}" ]]; then
		find_args+=("-and")
		not_prefix="("
		for exclude_pattern in "${exclude_patterns[@]}"; do
			[[ "${exclude_pattern}" == *.hurl ]] || exclude_pattern="${exclude_pattern}*.hurl"
			find_args+=("${not_prefix}" "-not" "-iwholename" "*${exclude_pattern}")
			not_prefix="-and"
		done
		find_args+=(")")
	fi

	print_info "2.a. Running API tests that do require authentication"
	# shellcheck disable=SC2086
	if ! find "${find_args[@]}" -exec hurl ${VERBOSE} --variable "hostnport=${hostnport}" --header "${DOLAPIKEY}" --test "{}" +; then
		print_warning "2.a. No tests found or failed to run API tests that do require authentication."
	fi
fi

if [[ -z ${COOKIEJAR+x} ]]; then
	export COOKIEJAR
	COOKIEJAR=$(mktemp /tmp/hurltest_cookieXXXXXX.jar)
fi

# Convert to Windows path if running on Windows (Git Bash/Cygwin)
if command -v cygpath >/dev/null 2>&1; then
	COOKIEJAR=$(cygpath -w "$COOKIEJAR")
fi

# Cleanup on exit
cleanup() {
	rm -f "$COOKIEJAR"
}
trap cleanup EXIT


# Login and save the cookie to $COOKIEJAR
./save_login_cookie.sh


# Build the find command for the GUI tests that do require authentication
find_args=("gui/" "-type" "f" "-iwholename" "*/10*.hurl" "-not" "-iwholename" "save_login_cookie.hurl" "-not" "-iwholename" "*/00*.hurl")
if [[ -n "${test_filters[*]}" ]]; then
	find_args+=("-and")
	or_prefix="("
	for test_filter in "${test_filters[@]}"; do
		[[ "${test_filter}" == *.hurl ]] || test_filter="${test_filter}*.hurl"
		find_args+=("${or_prefix}" "-iwholename" "*${test_filter}")
		or_prefix="-or"
	done
	find_args+=(")")
fi

if [[ -n "${exclude_patterns[*]}" ]]; then
	find_args+=("-and")
	not_prefix="("
	for exclude_pattern in "${exclude_patterns[@]}"; do
		[[ "${exclude_pattern}" == *.hurl ]] || exclude_pattern="${exclude_pattern}*.hurl"
		find_args+=("${not_prefix}" "-not" "-iwholename" "*${exclude_pattern}")
		not_prefix="-and"
	done
	find_args+=(")")
fi

print_info "2.b. Running GUI tests that do require authentication"
# shellcheck disable=SC2086
if ! find "${find_args[@]}" -exec hurl ${VERBOSE} --variable "hostnport=${hostnport}" --cookie "${COOKIEJAR}" --test "{}" +; then
	print_warning "2.b. No tests found or failed to run GUI tests that do require authentication."
fi
