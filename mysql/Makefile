#
# Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
#
# $Id$
# $Source$
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
# General Makefile for Mysql database
#

MYSQL=mysql 
BASE=dolibarr
OPTIONS=

TABLES=$(shell find tables/ -name "*sql")

all:
	cd tables \
	&& make show

show:
	cd tables \
	&& make show

table:
	cd tables \
	&& make create

dev:
	cd data \
	&& make dev


fulldev: droptable table load dev

droptable:
	cd tables \
	&& make drop

load:
	$(MYSQL) $(OPTIONS) $(BASE) < data/data.sql

drop: dropdb droppriv

droppriv:
	$(MYSQL) mysql -e "delete from db where Db='$(BASE)' ; "

dropdb:
	$(MYSQL) mysql -f -e "drop database if exists $(BASE) ; "

create: drop createdb createpriv


createdb:
	$(MYSQL) mysql -e "create database $(BASE) ; "

createpriv:
	$(MYSQL) mysql -e "insert into db (Host, Db) values ('localhost', '$(BASE)') ; "
	$(MYSQL) mysql -e "update db set select_priv     = 'Y'  where db='$(BASE)' ; "
	$(MYSQL) mysql -e "update db set insert_priv     = 'Y'  where db='$(BASE)' ; "
	$(MYSQL) mysql -e "update db set update_priv     = 'Y'  where db='$(BASE)' ; "
	$(MYSQL) mysql -e "update db set delete_priv     = 'Y'  where db='$(BASE)' ; "
	$(MYSQL) mysql -e "update db set create_priv     = 'Y'  where db='$(BASE)' ; "
	$(MYSQL) mysql -e "update db set drop_priv       = 'Y'  where db='$(BASE)' ; "
	$(MYSQL) mysql -e "update db set grant_priv      = 'Y'  where db='$(BASE)' ; "
	$(MYSQL) mysql -e "update db set references_priv = 'Y'  where db='$(BASE)' ; "
	$(MYSQL) mysql -e "update db set index_priv      = 'Y'  where db='$(BASE)' ; "
	$(MYSQL) mysql -e "update db set alter_priv      = 'Y'  where db='$(BASE)' ; "
	$(MYSQL) mysql -e "flush privileges ; "


mysql.sql: $(TABLES)
	find tables/ -name "*sql" | xargs cat | grep -v ^-- > mysql.sql