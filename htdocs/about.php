<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo    <jlb@j1b.org>
 * Copyright (C) 2004-2009 Laurent Destailleur   <eldy@users.sourceforge.net>
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
 */

/**
 *        \file       htdocs/about.php
 *       \brief      Fichier page a propos
 *       \version    $Id$
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

print $langs->trans("Developpers").':';
print '<ul>';
print '<li>'.$langs->trans("SeeWikiForAllTeam").': <a href="http://wiki.dolibarr.org/index.php/Dolibarr_Project" target="_blank">http://wiki.dolibarr.org/index.php/Dolibarr_Project</a></li>';
print '</ul>';

print "<br>\n";

print $langs->trans("OtherInformations").':';

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
}
print '<li>';
print '<a target="blank" href="http://wiki.dolibarr.org/">'.$langs->trans("OfficialWiki").'</a>';
print '</li>';
print '<li>';
print '<a target="blank" href="http://demo.dolibarr.org/public/demo">'.$langs->trans("OfficialDemo").'</a>';
print '</li>';

if (eregi('^fr_',$langs->getDefaultLang()))
{
	print '<li>';
	print 'Les t&acirc;ches en cours de r&eacute;alisation sur Dolibarr sont consultables dans le <a target="blank" href="http://savannah.nongnu.org/task/?group=dolibarr">gestionnaire de projets</a> sur Savannah.';
	print '</li>';

	print '<li>';
	print 'Si vous trouvez un bogue dans Dolibarr, vous pouvez en informer les d&eacute;veloppeurs sur le <a target="blank" href="http://savannah.nongnu.org/bugs/?group=dolibarr">syst&egrave;me de gestion des bogues</a> de Savannah.';
	print '</li>';

	print '<li>';
	print 'Le code source de Dolibarr est consultable par l\'<a target="_blank" href="http://savannah.nongnu.org/cgi-bin/viewcvs/dolibarr/dolibarr/">interface web du cvs</a>.';
	print '</li>';
}

print '</ul>';


print $langs->trans("HelpCenter");
print '<ul>';
print '<li>';
//print $langs->trans("SeeWikiPage",'http://wiki.dolibarr.org/index.php/List_of_OpenSource_Software_companies_and_freelancers');
print '<a target="_blank" href="'.DOL_URL_ROOT.'/support/index.php">'.$langs->trans("HelpCenter").'</a>';
print '</li>';
print '</ul>';


print '<br>'.$langs->trans("MakeADonation").':<br>';

print '<ul>';
print '<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="hosted_button_id" value="6573525">';
print '<input class="none" type="image" src="'.DOL_URL_ROOT.'/theme/common/paypal.png" border="0" name="submit" alt="Help Dolibarr making a donation">';
print '</form>';
print '</ul>';


llxFooter('$Date$ - $Revision$');
?>










