#!/bin/sh
#
# Count number of different contributors and number of commits for a given year.
# Can be used for statistics (for example to generate the inforgraphy of the year)
#
 
if [ "x$1" = "x" ]; then
	echo "Usage: $0 YEARSTART [YEAREND]"
	exit
fi


FROM=$1-01-01
TO=$1-12-31

if [ "x$2" != "x" ]; then
	TO=$2-12-31
fi

echo "--- Number of contributors for the year"
echo "git log --since $FROM --before $TO | grep ^Author | awk -F'<' '{ print $1 }' | iconv -f UTF-8 -t ASCII//TRANSLIT | sort -u -f -i -b | wc -l"
git log --since $FROM --before $TO | grep '^Author' | awk -F"<" '{ print $1 }' | iconv -f UTF-8 -t ASCII//TRANSLIT | sort -u -f -i -b | wc -l


echo "--- Number of commit for the year"
echo "git log --pretty='format:%cd' --date=format:'%Y' | sort | uniq -c | awk '{ if (\$2 >= '"$1"') { print \"Year: \"\$2\", commits: \"\$1 } }'"
git log --pretty='format:%cd' --date=format:'%Y' | sort | uniq -c | awk '{ if ($2 >= '$1') { print "Year: "$2", commits: "$1 } }'
