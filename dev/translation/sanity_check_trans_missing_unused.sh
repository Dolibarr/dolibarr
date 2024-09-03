#!/bin/bash
#
# Find unused translations pretty fast...
#
# Principle:
#
# 1.Generate two files:
#   - one for available translations keys,
#   - one for expected keys.
# 2. Make the difference between the files.
#
# Find expected translation keys:
#   1. Find all occurrences that look like `->trans("` or `->trans('`
#      with fast grep.
#   2. Split result to have only one '->trans(' on each line
#   3. Filter the text between the single or double quotes.
#
# Find available translation keys:
#   1. Get all strings before '=' token in the language files
#
# Notes:
#   - Some side effects from translations on variables.
#   - Some other minors side effects to be examined (#, %).
#
# Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>

LANG_DIR=htdocs/langs/en_US/
MYDIR=$(dirname "$(realpath "$0")")
TMP=${TMP:=/tmp}  # Most of the time defined on the system.
EXPECTED_FILE=${TMP}/expected_translations
AVAILABLE_FILE_NODEDUP=${TMP}/available_translations_no_dedup
AVAILABLE_FILE=${TMP}/available_translations
DUPLICATE_KEYS_FILE=${TMP}/duplicate_keys
DYNAMIC_KEYS_FILE=${TMP}/dynamic_keys
MISSING_AND_UNUSED_FILE=${TMP}/missing_and_unused
MISSING_FILE=${TMP}/missing
UNUSED_FILE=${TMP}/unused
EXPECTED_REGEX='(Country..|ExportDataset_.*|Language_.._..|MonthVeryShort\d\d|PaperFormat.*||Permission.*|ProfId\d(..)?|unit.*)'
DYNAMIC_KEYS_SRC_FILE=${MYDIR}/dynamic_translation_keys.lst
EXCLUDE_KEYS_SRC_FILE=${MYDIR}/ignore_translation_keys.lst
DUPLICATE_KEYS_SRC_FILE=${MYDIR}/duplicate_translation_keys.lst

# Grep options that are reused (normal grep)
GREP_OPTS=""
GREP_OPTS="${GREP_OPTS} --exclude=htdocs/theme/common/fontawe*/"
GREP_OPTS="${GREP_OPTS} --exclude-dir=.cache --exclude-dir=.git"
GREP_OPTS="${GREP_OPTS} --exclude=*.phar --exclude=*.webp --exclude=*.z"
GREP_OPTS="${GREP_OPTS} --exclude=*.sw? --exclude=*.json"

# Note: using 'git grep' to restrict to version controlled files
#       and more flexible globbing.

if [ "$1" == "--help" ]; then
	echo "----- sanity_check_trans_missing_unused.sh -----"
	echo "Usage: sanity_check_trans_missing_unused.sh (--help) (--showunused)"
	exit;
fi

exit_code=0

# Find all translations keys available in the language files (for the language)
grep --no-filename -r -oP -- '^([^#=]+?)(?=\s*=.*)' "${LANG_DIR}" \
	| grep -x -v -F -f "${EXCLUDE_KEYS_SRC_FILE}" \
	| sort > "${AVAILABLE_FILE_NODEDUP}"
sort -u \
	< "${AVAILABLE_FILE_NODEDUP}" \
	> "${AVAILABLE_FILE}"


# Combine strings found in sources with pre-determined dynamic string values.

## Build some regex strings to match translations
#
EXTRACT_STR=""
JOIN_STR=""
for t in '->trans' '->transnoentities' '->transnoentitiesnoconv' '->newItem' '->buttonsSaveCancel'; do
	MATCH_STR="$MATCH_STR$JOIN_STR$t"
	EXTRACT_STR="$EXTRACT_STR$JOIN_STR(?<=${t}\\([\"'])([^\"']+)(?=[\"']\$)"
	JOIN_STR="|"
done

#echo "MATCH_STR=$MATCH_STR"
#echo "EXTRACT_STR=$EXTRACT_STR"
#echo "Generate the file EXPECTED_FILE=${EXPECTED_FILE} (contains autodetected dynamic trans and declared dynamic trans)"

{
	# Find static strings that are translated in the sources (comments stripped)
	# shellcheck disable=2086
	# With std grep: `grep --no-filename -r ${GREP_OPTS} -- '->trans(' . `
	# Using git grep avoiding to look into unversioned files
	# transnoentitiesnoconv
	git grep -h -r -P -- "${MATCH_STR}\\(" ':*.php' ':*.html' \
		| sed 's@\(^#\|[^:]//\|/\*\|^\s*\*\).*@@' \
	| sed 's@)\|\(['"'"'"]\)\(,\)@\1\n@g' \
		| grep -aPo "$EXTRACT_STR(?=.$)"

	# "Append" the list of strings that are used in dynamic expressions.
	# (Fixed list: needs to be updated if the dynamic strings evolve.)
	cat "${DYNAMIC_KEYS_SRC_FILE}"
} \
	| grep -x -v -F -f "${EXCLUDE_KEYS_SRC_FILE}" \
	| sort -u \
	| grep -v -P '^(#|$)' \
	> "${EXPECTED_FILE}"


# shellcheck disable=2050
if [ 0 = 1 ] ; then
	# Find dynamic keys for call to trans.
	# shellcheck disable=2086
	grep --no-filename ${GREP_OPTS} -r -- '->trans(' . \
		| tr ')' '\n' \
		| grep -- '->trans(' \
		| grep -v -P '(?<=->trans\(["'"'"'])([^"'"'"']*)(?=["'"'"'])' \
		| grep -Po '(?<=->trans\()(.*)' \
		| sort -u \
		> "${DYNAMIC_KEYS_FILE}"
fi


# Produce reports on STDOUT. It generates the files with missing and unused entries with format
# < xxx
# > yyy
# Some output is already compatible with message extraction for github annotation (logToCs.py)
#

diff "${AVAILABLE_FILE}" "${EXPECTED_FILE}" \
	| grep -E "^[<>]" \
	| grep -v -P "^< ${EXPECTED_REGEX}$" \
	| sort \
	> "${MISSING_AND_UNUSED_FILE}"

rm -f "${UNUSED_FILE}.grep" >/dev/null 2>&1
sed -n 's@< \(.*\)@^\1\\s*=@p' \
	< "${MISSING_AND_UNUSED_FILE}" \
	> "${UNUSED_FILE}.grep"


# Prepare file with exact matches for use with `git grep`, supposing " quotes
#
REPL_STR=""
for t in trans transnoentities transnoentitiesnoconv newItem buttonsSaveCancel; do
   REPL_STR="${REPL_STR}\n->${t}(\"\\1\","
   REPL_STR="${REPL_STR}\n->${t}('\\1',"
   REPL_STR="${REPL_STR}\n->${t}(\"\\1\")"
   REPL_STR="${REPL_STR}\n->${t}('\\1')"
done

rm -f "${MISSING_FILE}.grep" >/dev/null 2>&1
sed -n 's@> \(.*\)'"@${REPL_STR}@p" \
	< "${MISSING_AND_UNUSED_FILE}" \
	| grep -v -E '^$' \
	> "${MISSING_FILE}.grep"


exit_code=0


if [ -s "${MISSING_FILE}.grep" ] ; then
	# Report missing translation in recognizable format

	echo "##[group]List missing translations (used by code but not found into lang files) - Generate CTI errors"

	git grep -n --column -r -F -f "${MISSING_FILE}.grep" -- ':*.php' ':*.html' \
		| sort -t: -k 4 \
		| sed 's@^\([^:]*:[^:]*:[^:]*:\)\s*@\1 Missing translation; @' > "${MISSING_FILE}.result"

	if [ -s "${MISSING_FILE}.result" ] ; then
		exit_code=1
		cat "${MISSING_FILE}.result"
	fi

	echo "##[endgroup]"
fi


if [ -s "${UNUSED_FILE}.grep" ] ; then
	#exit_code=1	# We do not consider adding new entries for future use as an error (even if ignore_translation_keys is not filled).

	# Report unused translation in recognizable format

	echo
	echo "##[group]List Apparently Unused Translations (found into a lang file but not into code) - Does NOT generate CTI errors, only warnings"
	echo "## :warning: Unused Translations may match ->trans(\$key.'SomeString')."
	echo "##   You can add such dynamic keys to $(basename "$DYNAMIC_KEYS_SRC_FILE")"
	echo "##   so that they are ignored for this report."
	echo "## :warning: Unused Translations may also be commented in the code"
	echo "##   You can add such 'disabled' keys to $(basename "$EXCLUDE_KEYS_SRC_FILE")"
	echo "##   so that they are ignored for this report."

	git grep -n --column -r -f "${UNUSED_FILE}.grep" -- "${LANG_DIR}"'/*.lang' \
		| sort -t: -k 4 \
		| sed 's@^\([^:]*:[^:]*:[^:]*:\)\s*@Warning Not used, translated; @'

	echo "##[endgroup]"
	echo
fi


diff "${AVAILABLE_FILE_NODEDUP}" "${AVAILABLE_FILE}" \
	| grep -Po '(?<=^\< )(.*)$' \
	| grep -x -v -F -f "${DUPLICATE_KEYS_SRC_FILE}" \
	| sed 's/.*/^\0=/' \
	> "${DUPLICATE_KEYS_FILE}"

if [ -s "${DUPLICATE_KEYS_FILE}" ] ; then
	exit_code=1
	echo
	echo "##[group]List Duplicate Keys - Generate CTI errors"
	echo "## :warning:"
	echo "##   Duplicate keys may be expected across language files."
	echo "##   You may want to avoid them or they could be a copy/paste mistake."
	echo "##   You can add add valid duplicates to $(basename "$DUPLICATE_KEYS_SRC_FILE")"
	echo "##   so that they are ignored for this report."
	cat "${DUPLICATE_KEYS_FILE}"
	echo "##[endgroup]"
	echo

	git grep -n -r -f "${DUPLICATE_KEYS_FILE}" -- "${LANG_DIR}"'/*.lang' \
		| sort -t: -k 3 \
		| sed 's@^\([^:]*:[^:]*:\)\s*@\1 Is/Has duplicate @'
fi


exit $exit_code
