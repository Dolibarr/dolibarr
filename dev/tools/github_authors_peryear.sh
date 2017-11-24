#!/bin/sh

FROM=2016-01-01
TO=2016-12-31

echo "git log --since $FROM --before $TO | grep ^Author | sort -u -f -i -b | wc -l"
git log --since $FROM --before $TO | grep ^Author | sort -u -f -i -b | wc -l

