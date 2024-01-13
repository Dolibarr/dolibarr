#!/bin/bash
#
# Count number of lines modified per user for a given branch
#

# shellcheck disable=2002,2086,2268
DEBUG=${DEBUG:=0}

if [ "$2" = "" ]; then
	echo "***** github_lines_perusers.sh *****"
	echo "Return the number of lines of code produced by each contributor between 2 versions"
	echo "Usage:   $0  origin/branchstart|tagnamestart|START  origin/branchend|tagnameend|HEAD"
	echo "Example: $0  origin/18.0 origin/19.0"
	echo "Example: $0  origin/18.0 HEAD"
	exit
fi

START="$1.."
END=$2
if [ "$START" = "START.." ]; then
	START=""
fi

[ "$DEBUG" ] && echo "git log '${START}${END}' --shortstat | grep ... | perl ... > /tmp/github_lines_perusers.tmp"
git log "${START}${END}" --shortstat --use-mailmap | iconv -f UTF-8 -t ASCII//TRANSLIT | grep -e 'Author:' -e 'Date:' -e ' changed' -e ' insertion' -e ' deletion' | perl -n -e '/^(.*)$/; $line = $1; if ($line =~ /(changed|insertion|deletion)/) { $line =~ s/[^0-9\s]//g; my @arr=split /\s+/, $line; $tot=0; for (1..@arr) { $tot += $arr[$_]; }; print $tot."\n"; } else { print $line."\n"; };' > /tmp/github_lines_perusers.tmp

echo "Users and nb of lines";
cat /tmp/github_lines_perusers.tmp | awk 'BEGIN { FS="\n"; lastuser=""; } { if ($1 ~ /^Author:/) { sub(/<.*/, ""); lastuser=tolower($1) }; if ($1 ~ /^[0-9]+$/) { aaa[lastuser]+=$1; } } END { for (var in aaa) print var," ",aaa[var]; } ' | sort

