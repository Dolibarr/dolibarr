# Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
# General Makefile
#

FILE=dolibarr-0.1.6

tar:
	rm -fr dolibarr-*.tar.gz* $(FILE)
	mkdir $(FILE)
	rsync -ar doc htdocs mysql misc COPY* http* INSTALL scripts templates pgsql $(FILE)/
	tar --exclude-from tar.exclude -cvvf $(FILE).tar $(FILE)/
	gzip $(FILE).tar
	md5sum $(FILE).tar.gz > $(FILE).tar.gz.md5sum
	scp $(FILE).tar.gz* rodolphe.quiedeville.org:/home/www/rodolphe.quiedeville.org/htdocs/projets/dolibarr/dl/
	scp $(FILE).tar.gz rodolphe@subversions.gnu.org:/upload/dolibarr