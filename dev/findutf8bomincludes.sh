#!/bin/sh
#
# Checks if files contains UTF-8 BOM
# in dolibarr includes tree excluding
# git repository
#
# Raphaël Doursenaud - rdoursenaud@gpcsolutions.fr
grep -rlI \
--exclude-dir='.git' \
$'\xEF\xBB\xBF' htdocs/includes
