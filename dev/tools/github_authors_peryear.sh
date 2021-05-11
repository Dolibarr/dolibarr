#!/bin/sh

if [ "x$1" = "x" ]; then
	echo "Usage: $0 YEAR"
	exit
fi


FROM=$1-01-01
TO=$1-12-31

echo "git log --since $FROM --before $TO | grep ^Author | sort -u -f -i -b | wc -l"
git log --since $FROM --before $TO | grep ^Author | sort -u -f -i -b | wc -l

