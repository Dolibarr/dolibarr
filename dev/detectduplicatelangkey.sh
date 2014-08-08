#!/bin/sh
# Helps find duplicate translation keys in language files
#
# Copyright (C) 2014 Raphaël Doursenaud - rdoursenaud@gpcsolutions.fr

for file in `find . -type f`
do
    dupes=$(
    sed "s/^\s*//" "$file" | # Remove any leading whitespace
    sed "s/\s*\=/=/" | # Remove any whitespace before =
    grep -Po "(^.*?)=" | # Non greedeely match everything before =
    sed "s/\=//" | # Remove trailing = so we get the key
    sort | uniq -d # Find duplicates
    )

    if [ -n "$dupes" ]
    then
        echo "Duplicates found in $file"
        echo "$dupes"
    fi
done
