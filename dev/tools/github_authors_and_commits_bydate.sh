#!/bin/sh
#
# Count number of different contributors and number of commits for a given year or month
# Can be used for statistics (for example to generate the infography of the year)
# With the option peruser, the number of commits is also detailed for each user
#

PERIOD=$1
STARTYEAR=$2
ENDYEAR=$3
PERUSER=$4

# Also accept peruser as 1st argument (byyear is then used as period), or as 3rd argument (when YEAREND is not provided)
if [ "$PERIOD" = "peruser" ]; then
	PERIOD="byyear"
	PERUSER="peruser"
elif [ "$ENDYEAR" = "peruser" ]; then
	PERUSER="peruser"
	ENDYEAR=""
fi

DEBUG=${DEBUG:=0}  # Example: run script with DEBUG=1 script arguments

echo "***** github_authors_and_commits_bydate.sh *****"

if [ "$PERIOD" != "byyear" ] && [ "$PERIOD" != "bymonth" ] && [ "$PERIOD" != "byday" ]; then
	echo "Usage: $0  (byyear|bymonth|byday)  YEARSTART  [YEAREND]  [peruser]"
	exit 1
fi
if [ "$PERUSER" != "" ] && [ "$PERUSER" != "peruser" ]; then
	echo "Usage: $0  (byyear|bymonth|byday)  YEARSTART  [YEAREND]  [peruser]"
	exit 1
fi

# Default is byyear
DATEFORMAT="%Y"
LABEL="Year"
if [ "$PERIOD" = "bymonth" ]; then
	DATEFORMAT="%Y%m"
	LABEL="Month"
elif [ "$PERIOD" = "byday" ]; then
	DATEFORMAT="%Y%m%d"
	LABEL="Day"
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


echo "--- Number of commits $PERIOD${PERUSER:+ (with detail per user)}"
[ "$DEBUG" -ne 0 ] && echo "git log --pretty='format:%cd %an' --date=format:'$DATEFORMAT' | iconv -f UTF-8 -t ASCII//TRANSLIT | awk -v startyear='$STARTYEAR' -v endyear='$ENDYEAR' '{ if (substr(\$1, 1, 4) >= startyear && substr(\$1, 1, 4) <= endyear) { name = \$2; for (i = 3; i <= NF; i++) { name = name \" \" \$i } total[\$1]++; peruser[\$1, name]++ } } END { for (k in total) { print k\" \"total[k] } for (k in peruser) { split(k, a, SUBSEP); if (a[2] != \"\") { print a[1]\" \"peruser[k]\" \"a[2] } } }' | sort -k1,1 -k2,2nr | awk -v label='$LABEL' -v withdetail='$PERUSER' '{ if (NF < 3) { print label\": \"\$1\", commits: \"\$2 } else if (withdetail == \"peruser\") { name = \$3; for (i = 4; i <= NF; i++) { name = name \" \"\$i } print \"\t\"name\": \"\$2 } }'" >&2
git log --pretty='format:%cd %an' --date=format:"$DATEFORMAT" | iconv -f UTF-8 -t ASCII//TRANSLIT | awk -v startyear="$STARTYEAR" -v endyear="$ENDYEAR" '{ if (substr($1, 1, 4) >= startyear && substr($1, 1, 4) <= endyear) { name = $2; for (i = 3; i <= NF; i++) { name = name " " $i } total[$1]++; peruser[$1, name]++ } } END { for (k in total) { print k" "total[k] } for (k in peruser) { split(k, a, SUBSEP); if (a[2] != "") { print a[1]" "peruser[k]" "a[2] } } }' | sort -k1,1 -k2,2nr | awk -v label="$LABEL" -v withdetail="$PERUSER" '{ if (NF < 3) { print label": "$1", commits: "$2 } else if (withdetail == "peruser") { name = $3; for (i = 4; i <= NF; i++) { name = name " " $i } print "\t"name": "$2 } }'
