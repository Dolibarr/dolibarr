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

dev:
	$(SQL) $(OPTIONS) $(BASE) < data_dev.sql
	$(SQL) $(OPTIONS) $(BASE) < propal_dev.sql
	$(SQL) $(OPTIONS) $(BASE) < facture_dev.sql
