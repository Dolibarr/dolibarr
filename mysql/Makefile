# Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
# Copyright (C) 2005      Laurent Destailleur  <eldy@users.sourceforge.net>
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
# make mysql.sql
# mysql dolibarr < mysql.sql
#
# Le script mysql.sql cotient tous le ordres sql pour créer la base
# Tables + Index et Clés + Données
#
# **********************************************************************

SQL=mysql 
BASE=dolibarr
OPTIONS=

FIND=find
#FIND="c:/Program files/cygwin/bin/find"


TABLES=$(shell $(FIND) tables/ -name "*sql")


all:
	cat README

load:
	$(SQL) $(OPTIONS) $(BASE) < data/data.sql

dev:
	cd data \
	&& make dev BASE=$(BASE) OPTIONS=$(OPTIONS)



dropall: dropdb droppriv

droppriv:
	$(SQL) $(OPTIONS) $(SQL) -e "delete from db where Db='$(BASE)' ; "

dropdb:
	$(SQL) $(OPTIONS) $(SQL) -f -e "drop database if exists $(BASE) ; "



create: createdb createpriv

createdb:
	$(SQL) $(OPTIONS) mysql -e "create database $(BASE) ; "

createpriv:
	$(SQL) $(OPTIONS) mysql -e "insert into db (Host, Db) values ('localhost', '$(BASE)') ; "
	$(SQL) $(OPTIONS) mysql -e "update db set select_priv     = 'Y'  where db='$(BASE)' ; "
	$(SQL) $(OPTIONS) mysql -e "update db set insert_priv     = 'Y'  where db='$(BASE)' ; "
	$(SQL) $(OPTIONS) mysql -e "update db set update_priv     = 'Y'  where db='$(BASE)' ; "
	$(SQL) $(OPTIONS) mysql -e "update db set delete_priv     = 'Y'  where db='$(BASE)' ; "
	$(SQL) $(OPTIONS) mysql -e "update db set create_priv     = 'Y'  where db='$(BASE)' ; "
	$(SQL) $(OPTIONS) mysql -e "update db set drop_priv       = 'Y'  where db='$(BASE)' ; "
	$(SQL) $(OPTIONS) mysql -e "update db set grant_priv      = 'Y'  where db='$(BASE)' ; "
	$(SQL) $(OPTIONS) mysql -e "update db set references_priv = 'Y'  where db='$(BASE)' ; "
	$(SQL) $(OPTIONS) mysql -e "update db set index_priv      = 'Y'  where db='$(BASE)' ; "
	$(SQL) $(OPTIONS) mysql -e "update db set alter_priv      = 'Y'  where db='$(BASE)' ; "
	$(SQL) $(OPTIONS) mysql -e "flush privileges ; "



mysql.sql: $(TABLES) data/data.sql Makefile
	echo "-- Fichier généré par make mysql.sql" > mysql.sql
	echo "-- Contient les ordres de creation des tables + les clés + les données" >> mysql.sql
	echo "-- ;" >> mysql.sql
	$(FIND) tables/ -name "*sql" | grep -v "key.sql" | xargs cat | grep -v "^--" | cut -d "-" -f 1 >> mysql.sql
	$(FIND) tables/ -name "*sql" | grep "key.sql" | xargs cat | grep -v "^--" | cut -d "-" -f 1 >> mysql.sql
	cat data/data.sql | grep -v ^-- >> mysql.sql
