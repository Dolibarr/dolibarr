#/bin/bash
#
# Count number of lines modified per user for a given branch
#

if [ "x$2" = "x" ]; then
	echo "Usage: $0  origin/branchstart|tagnamestart|START  origin/branchend|tagnameend|HEAD"
	exit
fi

START=$1
if [ "x$START" = "xSTART" ]; then
	START=""
fi

echo "git log $START..$2 --shortstat | grep ... | perl ... > /tmp/github_lines_perusers.tmp"
git log $START..$2 --shortstat | grep -e 'Author:' -e 'Date:' -e ' changed' -e ' insertion' -e ' deletion' | perl -n -e '/^(.*)$/; $line = $1; if ($line =~ /(changed|insertion|deletion)/) { $line =~ s/[^0-9\s]//g; my @arr=split /\s+/, $line; $tot=0; for (1..@arr) { $tot += $arr[$_]; }; print $tot."\n"; } else { print $line."\n"; };' > /tmp/github_lines_perusers.tmp

cat /tmp/github_lines_perusers.tmp | awk 'BEGIN { FS="\n"; print "user and nb of lines"; lastuser=""; } { if ($1 ~ /Author:/) { lastuser=$1 }; if ($1 ~ /^[0-9]+$/) { aaa[lastuser]+=$1; } } END { for (var in aaa) print var," ",aaa[var]; } '

