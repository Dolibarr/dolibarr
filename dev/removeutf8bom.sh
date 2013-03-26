#!/bin/sh
#
# Removes UTF-8 BOM from a file list on STDIN
# Use by piping the output of a findutf8bom script
#
# Raphaël Doursenaud - rdoursenaud@gpcsolutions.fr
#
# Example:
# cd dirwithfiles
# ls | /path/dev/removeutf8bom.sh

while read f; do
	echo "Fixing $f"
	sed -i '1s/^\xEF\xBB\xBF//' $f
done
