#!/bin/sh

#
# Prepare l'installation web de Dolibarr
#
mkdir documents
chown www-data documents
mkdir htdocs/document
chown www-data htdocs/document
touch htdocs/conf/conf.php
chown www-data htdocs/conf/conf.php
