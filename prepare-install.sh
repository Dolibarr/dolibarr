#!/bin/sh

#
# Prepare l'installation web de Dolibarr
#
mkdir documents
chown www-data documents
mkdir htdocs/documents
chown www-data htdocs/documents
touch htdocs/conf/conf.php
chown www-data htdocs/conf/conf.php
