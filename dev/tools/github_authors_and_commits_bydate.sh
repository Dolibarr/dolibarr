#!/bin/sh
#
# Count number of different contributors and number of commits for a given year or month
# Can be used for statistics (for example to generate the infography of the year)
#

PERIOD=$1
STARTYEAR=$2
ENDYEAR=$3

DEBUG=${DEBUG:=0}  # Example: run script with DEBUG=1 script arguments

echo "***** github_authors_and_commits_bydate.sh *****"

if [ "$PERIOD" != "byyear" ] && [ "$PERIOD" != "bymonth" ] && [ "$PERIOD" != "byday" ]; then
	echo "Usage: $0  (byyear|bymonth|byday)  YEARSTART  [YEAREND]"
	exit 1
fi

# Default is byyear
DATEFORMAT="%Y"
if [ "$PERIOD" = "bymonth" ]; then
	DATEFORMAT="%Y%m"
elif [ "$PERIOD" = "byday" ]; then
	DATEFORMAT="%Y%m%d"
fi

FROM=${STARTYEAR}-01-01
TO=${STARTYEAR}-12-31
if [ "${ENDYEAR}" != "" ]; then
	TO=${ENDYEAR}-12-31
else
	ENDYEAR=9999
fi

echo "--- Number of different contributors for the period $FROM $TO"
[ "$DEBUG" -ne 0 ] && echo "git log --use-mailmap --since '$FROM' --before '$TO' | iconv -f UTF-8 -t ASCII//TRANSLIT | grep ^Author | awk -F'<' '{ print $1 }' | sort -u -f -i -b | wc -l" >&2
git log --since "$FROM" --before "$TO" | iconv -c -f UTF-8 -t ASCII//TRANSLIT | grep '^Author' | awk -F"<" '{ print $1 }' | sort -u -f -i -b | wc -l


echo "--- Number of commits $1"
[ "$DEBUG" -ne 0 ] && echo "git log --use-mailmap --pretty='format:%cd' --date=format:'$DATEFORMAT' | iconv -f UTF-8 -t ASCII//TRANSLIT | sort | uniq -c | awk '{ if (\$2 >= '$1') { print \"Year: \"\$2\", commits: \"\$1 } }'" >&2
git log --pretty='format:%cd' --date=format:"$DATEFORMAT" | iconv -f UTF-8 -t ASCII//TRANSLIT | sort | uniq -c  | awk '{ if ($2 >= '"${STARTYEAR}"' && $2 <='"${ENDYEAR}"') { print "Year: "$2", commits: "$1 } }'
