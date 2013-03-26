#!/bin/sh
#
# Checks if files contains UTF-8 BOM
# in dolibarr source tree excluding
# git repository, custom modules and incuded libraries
#
# Raphaël Doursenaud - rdoursenaud@gpcsolutions.fr
grep -rlI \
--exclude-dir='.git' --exclude-dir='includes' --exclude-dir='custom' \
$'\xEF\xBB\xBF' .
