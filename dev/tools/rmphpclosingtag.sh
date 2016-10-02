#!/bin/bash
# vim:ft=sh:ts=3:sts=3:sw=3:et:
 
###
# Strips the closing php tag `?>` and any following blank lines from the 
# end of any PHP file in the current working directory and sub-directories. Files
# with non-whitespace characters following the closing tag will not be affected.
#
# Author: Bryan C. Geraghty <bryan@ravensight.org>
# Date: 2009-10-28
# Source: http://bryan.ravensight.org/2010/07/remove-php-closing-tag/
##
 
FILES=$(pcregrep -rnM --include='^.*\.php$' '^\?\>(?=([\s\n]+)?$(?!\n))' .);
 
for MATCH in $FILES;
do
   FILE=`echo $MATCH | awk -F ':' '{print $1}'`;
   TARGET=`echo $MATCH | awk -F ':' '{print $2}'`;
   LINE_COUNT=`wc -l $FILE | awk -F " " '{print $1}'`;
   echo "Removing lines ${TARGET} through ${LINE_COUNT} from file $FILE...";
   sed -i "${TARGET},${LINE_COUNT}d" $FILE;
done;
