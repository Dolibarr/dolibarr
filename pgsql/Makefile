#
# Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
# Copyright (C) 2002-2003 Éric Seigne <erics@rycks.com>
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

SQL=psql 
BASE=dolibarr
OPTIONS=-U postgres
OWNER=dolibarradm
OWNERPASS=azaz

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
	$(SQL) $(OPTIONS) $(BASE) < data/data.sql

drop: dropdb droppriv

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


