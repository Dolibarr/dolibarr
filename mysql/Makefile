# Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
# General Makefile for Mysql database
#

MYSQL=mysql 
BASE=dolibarr
OPTIONS=

TABLES=$(shell find tables/ -name "*sql")

all:
	cat README

show:
	cd tables \
	&& make show BASE=$(BASE) OPTIONS=$(OPTIONS)

table:
	cd tables \
	&& make create BASE=$(BASE) OPTIONS=$(OPTIONS)

dev:
	cd data \
	&& make dev BASE=$(BASE) OPTIONS=$(OPTIONS)


fulldev: drop table load dev

drop:
	$(MYSQL) $(OPTIONS) $(BASE) < drop.sql

load:
	$(MYSQL) $(OPTIONS) $(BASE) < data/data.sql

dropall: dropdb droppriv

droppriv:
	$(MYSQL) $(OPTIONS) mysql -e "delete from db where Db='$(BASE)' ; "

dropdb:
	$(MYSQL) $(OPTIONS) mysql -f -e "drop database if exists $(BASE) ; "

create: drop createdb createpriv


createdb:
	$(MYSQL) $(OPTIONS) mysql -e "create database $(BASE) ; "

createpriv:
	$(MYSQL) $(OPTIONS) mysql -e "insert into db (Host, Db) values ('localhost', '$(BASE)') ; "
	$(MYSQL) $(OPTIONS) mysql -e "update db set select_priv     = 'Y'  where db='$(BASE)' ; "
	$(MYSQL) $(OPTIONS) mysql -e "update db set insert_priv     = 'Y'  where db='$(BASE)' ; "
	$(MYSQL) $(OPTIONS) mysql -e "update db set update_priv     = 'Y'  where db='$(BASE)' ; "
	$(MYSQL) $(OPTIONS) mysql -e "update db set delete_priv     = 'Y'  where db='$(BASE)' ; "
	$(MYSQL) $(OPTIONS) mysql -e "update db set create_priv     = 'Y'  where db='$(BASE)' ; "
	$(MYSQL) $(OPTIONS) mysql -e "update db set drop_priv       = 'Y'  where db='$(BASE)' ; "
	$(MYSQL) $(OPTIONS) mysql -e "update db set grant_priv      = 'Y'  where db='$(BASE)' ; "
	$(MYSQL) $(OPTIONS) mysql -e "update db set references_priv = 'Y'  where db='$(BASE)' ; "
	$(MYSQL) $(OPTIONS) mysql -e "update db set index_priv      = 'Y'  where db='$(BASE)' ; "
	$(MYSQL) $(OPTIONS) mysql -e "update db set alter_priv      = 'Y'  where db='$(BASE)' ; "
	$(MYSQL) $(OPTIONS) mysql -e "flush privileges ; "


mysql.sql: $(TABLES) data/data.sql
	find tables/ -name "*sql" | xargs cat | grep -v ^-- | cut -d "-" -f 1 > mysql.sql
	cat data/data.sql | grep -v ^-- >> mysql.sql

mysql-BE.sql: $(TABLES) data/data-BE.sql
	find tables/ -name "*sql" | xargs cat | grep -v ^-- | cut -d "-" -f 1 > mysql.sql
	cat data/data-BE.sql | grep -v ^-- >> mysql.sql