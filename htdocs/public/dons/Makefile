# Authors: Loic Dachary <loic@gnu.org> and Jaime Villate <villate@gnu.org>
#
# XML/XSLT processor (validator)
# -------------------------
#
# sablotron (sabcmd)
# apt-get install sablotron
#
# libxslt + libxml2 (xsltproc)
# http://www.xmlsoft.org/
#
# XML validator
# -------------
# apt-get install rxp
# or
# ftp://ftp.cogsci.ed.ac.uk/pub/richard/rxp-1.2.3.tar.gz
#
XSLTPROC = sabcmd
WGET = wget
XSLTOPTS = \
	'$$fsfeurope=$(FSFEUROPE)' \
	'$$fsf=$(FSF)' \
	'$$gnu=$(GNU)'


all: 
	$(XSLTPROC) main-eucd.xsl don.xhtml > don.php
	$(XSLTPROC) main-eucd.xsl valid.xhtml > valid.php
	$(XSLTPROC) main-eucd.xsl erreur.xhtml > erreur.php
	$(XSLTPROC) main-eucd.xsl merci.xhtml > merci.php

get:
	rm -f navigation.fr.xsl fsfe-fr.xsl eucd.css
	$(WGET) http://eucd.info/eucd.css
	$(WGET) http://eucd.info/navigation.fr.xsl
	$(WGET) http://eucd.info/fsfe-fr.xsl

