<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo    <jlb@j1b.org>
 * Copyright (C) 2004-2006 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Franky Van Liedekerke <franky.van.liedekerke@telenet.be>
 * Copyright (C) 2005-2007 Regis Houssin         <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * $Id$
 */

/**
 *        \file       htdocs/about.php
 *       \brief      Fichier page a propos
 *       \version    $Revision$
 */

require("./pre.inc.php");

$langs->load("admin");

llxHeader();


print_fiche_titre("Dolibarr",'','setup');

print "<br>\n";

print $langs->trans("Version").':';
print '<ul>';
print '<li>'.DOL_VERSION.'</li>';
print '</ul>';

print "<br>\n";

print $langs->trans("DolibarrLicense").':';
print '<ul>';
print '<li>GNU/GPL</li>';
print '</ul>';

print "<br>\n";

print $langs->trans("DolibarrProjectLeader").':';
print '<ul>';
print '<li><a target="blank" href="http://rodolphe.quiedeville.org">Rodolphe Quiédeville</a>';
print '</ul>';

print "<br>\n";

print $langs->trans("OtherDeveloppers").':';
print '<ul>';
print '<li><a target="blank" href="http://www.ipsyn.net">Jean-Louis Bergamo</a></li>';
print '<li><a target="blank" href="http://www.destailleur.fr/">Laurent Destailleur</a></li>';
print '<li>Eric Seigne</li>';
print '<li>Benoit Mortier</li>';
print '<li>Régis Houssin</li>';
print '</ul>';

print "<br>\n";

print $langs->trans("Informations").':';

print '<ul>';
print '<li>';
print '<a target="blank" href="http://www.dolibarr.org/">'.$langs->trans("OfficialWebSite").'</a>';
print '</li>';
// Si langue francaise, on affiche site web francophone
if (eregi('^fr_',$langs->getDefaultLang()))
{
	print '<li>';
	print '<a target="blank" href="http://www.dolibarr.fr/">'.$langs->trans("OfficialWebSiteFr").'</a>';
	print '</li>';
	print '<li>';
	print '<a target="blank" href="http://www.dolibarr.com/wikidev/">'.$langs->trans("OfficialWikiFr").'</a>';
	print '</li>';
}
print '<li>';
print '<a target="blank" href="http://freshmeat.net/projects/dolibarr/">Freshmeat</a>';
print '</li>';

if (eregi('^fr_',$langs->getDefaultLang()))
{
	print '<li>';
	print 'Les tâches en cours de réalisation sur Dolibarr sont consultables dans le <a target="blank" href="http://savannah.nongnu.org/task/?group=dolibarr">gestionnaire de projet</a> sur Savannah.';
	print '</li>';
	
	print '<li>';
	print 'Si vous trouvez un bogue dans Dolibarr, vous pouvez en informer les développeurs sur le <a target="blank" href="http://savannah.nongnu.org/bugs/?group=dolibarr">système de gestion des bogues</a> de Savannah.';
	print '</li>';
	
	print '<li>';
	print 'Le code source de Dolibarr est consultable par l\'<a target="blank" href="http://savannah.nongnu.org/cgi-bin/viewcvs/dolibarr/dolibarr/">interface web du cvs</a>.';
	print '</li>';
}

print '</ul>';


if (eregi('^fr_',$langs->getDefaultLang()))
{
    print '<p>';
    print 'Vente / Support';
    print '<ul>';
    print '<li>';
    print 'Contactez Rodolphe Quiédeville sur <a target="blank" href="http://rodolphe.quiedeville.org">www.dolibarr.com</a>';
    print '</li>';
    print '</ul>';
}


llxFooter('$Date$ - $Revision$');

?>










