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

DOC=dolibarr-dev
DEBIANDOC2PS= debiandoc2ps
DEBIANDOC2PDF= debiandoc2pdf
DEBIANDOC2HTML= debiandoc2html
DEBIANDOC2LATEX= debiandoc2latex
DEBIANDOC2TEXT= debiandoc2text

OPTIONS=-v -lfr_FR.ISO8859-1 -pa4
HOPTIONS=-lfr_FR.ISO8859-1

RSYNC=/usr/bin/rsync
ROPT=-av
RSSH=-e ssh

FRPAGES = $(shell find * -regex '.*\.sgml' -print )

all: $(DOC).pdf $(DOC).html


%.tex:	%.sgml $(FRPAGES)
	$(DEBIANDOC2LATEX) $(HOPTIONS) $<

%.ps:	%.sgml $(FRPAGES)
	$(DEBIANDOC2PS) $(OPTIONS) $<

$(DOC).pdf: $(FRPAGES)
	$(DEBIANDOC2PDF) $(OPTIONS) $(DOC).sgml

$(DOC).html: $(FRPAGES)
	$(DEBIANDOC2HTML) $(HOPTIONS) $<

put:
	$(RSYNC) $(ROPT) $(RSSH) dolibarr-dev.html rodolphe.quiedeville.org:/home/www/rodolphe.quiedeville.org/htdocs/projets/dolibarr/doc

clean:
	rm -f *pdf *ps