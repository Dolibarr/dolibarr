# Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

DOC=manual
DEBIANDOC2PS= debiandoc2ps
DEBIANDOC2PDF= debiandoc2pdf
DEBIANDOC2HTML= debiandoc2html

OPTIONS=-v -lfr_FR.ISO8859-1 -pa4
HOPTIONS=-lfr_FR.ISO8859-1

RSYNC=/usr/bin/rsync
ROPT=-av
RSSH=-e ssh

FRPAGES = $(shell find * -regex '.*\.sgml' -print )

all: $(DOC).ps $(DOC).pdf $(DOC).html


%.ps:	%.sgml $(FRPAGES)
	$(DEBIANDOC2PS) $(OPTIONS) $<

%.pdf:	%.sgml
	$(DEBIANDOC2PDF) $(OPTIONS) $<

%.html: %.sgml $(FRPAGES)
	$(DEBIANDOC2HTML) $(HOPTIONS) $<

put:
	$(RSYNC) $(ROPT) $(RSSH) manual.html/* deb:/home/www/rodolphe.quiedeville.org/htdocs/projets/dolibarr/doc

clean:
	rm -f *pdf *ps