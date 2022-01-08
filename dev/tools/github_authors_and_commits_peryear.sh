#!/bin/sh

if [ "x$1" = "x" ]; then
	echo "Usage: $0 YEAR"
	exit
fi


FROM=$1-01-01
TO=$1-12-31

echo "Number of contributors for the year"
echo "git log --since $FROM --before $TO | grep ^Author | sort -u -f -i -b | wc -l"
git log --since $FROM --before $TO | grep ^Author | sort -u -f -i -b | wc -l


echo "Number of commit for the year"
git log --pretty='format:%cd' --date=format:'%Y' | uniq -c | awk '{print "Year: "$2", commits: "$1}' | grep "Year: $1"
