#
# Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
# Copyright (C) 2002-2003 Éric Seigne <erics@rycks.com>
# Copyright (C) 2004 Benoit Mortier <benoit.mortier@opensides.be>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
#
# $Id$
# $Source$
#
# **********************************************************************
# Ce makefile permet d'initialiser la base de donnée de dolibarr en manuel.
# Il n'est utile que si vous n'utilisez pas l'installateur automatique
# accessible à http://localhost/dolibarr/install/
#
# Pour une installation manuelle, créer la base dolibarr puis faire:
# make table
# make load
#
# Ou bien, toujours après avoir créer la base dolibarr:
# make mysql.sql
# mysql dolibarr < mysql.sql
#
# Le script mysql.sql cotient tous le ordres sql pour créer la base
# Tables + Index et Clés + Données
#
# **********************************************************************

SQL=psql 
BASE=dolibarr
OPTIONS=-U postgres
OWNER=dolibarradm
OWNERPASS=azaz
FIND=find
#FIND="c:/Program files/cygwin/bin/find"


TABLES=$(shell $(FIND) tables/ -name "*sql")


all:
	cat README

show:
	cd tables \
	&& make show

drop:
	cd tables \
	&& make drop

table:
	cd tables \
	&& make create
load:
	$(SQL) $(OPTIONS) $(BASE) < data/data.sql

dev:
	cd data \
	&& make dev



fulldev: drop table load dev


drop: droppriv dropdb dropuser

droppriv:
	$(SQL) $(OPTIONS) template1 -c "revoke all privileges on database $(BASE) from $(OWNER) ; "

dropuser:
	$(SQL) $(OPTIONS) template1 -c "drop user $(OWNER) ; "

dropdb:
	$(SQL) $(OPTIONS) template1 -c "drop database $(BASE) ; "



create: drop createuser createdb createpriv

createuser:
	$(SQL) $(OPTIONS) template1 -c "create user $(OWNER) with UNENCRYPTED PASSWORD '$(OWNERPASS)' ; "

createdb:
	$(SQL) $(OPTIONS) template1 -c "create database $(BASE) with owner = $(OWNER) ; "

createpriv:
	$(SQL) $(OPTIONS) template1 -c "grant all privileges on database $(BASE) to $(OWNER) ; "


pgsql.sql: $(TABLES) data/data.sql Makefile
	echo "-- Fichier généré par make pgsql.sql" > pgsql.sql
	echo "-- Contient les ordres de creation des tables + les clés + les données" >> pgsql.sql
	echo "-- ;" >> pgsql.sql
	$(FIND) tables/ -name "*sql" | xargs cat | grep -v "^--" | cut -d "-" -f 1 >> pgsql.sql
	cat data/data.sql | grep -v ^-- >> pgsql.sql
