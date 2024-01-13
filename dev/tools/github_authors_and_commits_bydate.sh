#!/bin/sh
#
# Count number of different contributors and number of commits for a given year or month
# Can be used for statistics (for example to generate the infography of the year)
#
# shellcheck disable=2027,2086,2268

echo "***** github_authors_and_commits_bydate.sh *****"
if [ "x$1" = "x" ]; then
	echo "Usage: $0  (byyear|bymonth)  YEARSTART  [YEAREND]"
	exit
fi
if [ "x$1" != "xbyyear" -a "x$1" != "xbymonth" ]; then
	echo "Usage: $0  (byyear|bymonth)  YEARSTART  [YEAREND]"
	exit
fi

DATEFORMAT="%Y"
if [ "x$1" = "xbymonth" ]; then
	DATEFORMAT="%Y%m"
fi

FROM=$2-01-01
TO=$2-12-31
if [ "x$3" != "x" ]; then
	TO=$2-12-31
fi

echo "--- Number of different contributors for the year"
echo "git log --since $FROM --before $TO | grep ^Author | awk -F'<' '{ print $1 }' | iconv -f UTF-8 -t ASCII//TRANSLIT | sort -u -f -i -b | wc -l"
git log --since $FROM --before $TO | iconv -f UTF-8 -t ASCII//TRANSLIT | grep '^Author' | awk -F"<" '{ print $1 }' | sort -u -f -i -b | wc -l


echo "--- Number of commit $1"
echo "git log --pretty='format:%cd' --date=format:'$DATEFORMAT' | sort | uniq -c | awk '{ if (\$2 >= '"$1"') { print \"Year: \"\$2\", commits: \"\$1 } }'"
git log --pretty='format:%cd' --date=format:"$DATEFORMAT" | iconv -f UTF-8 -t ASCII//TRANSLIT | sort | uniq -c | awk '{ if ($2 >= '$1') { print "Year: "$2", commits: "$1 } }'
