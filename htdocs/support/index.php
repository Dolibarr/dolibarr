<?php
/* Copyright (C) 2008-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *       \file      htdocs/install/phpinfo.php
 *       \ingroup   install
 *       \brief     Provide an Online Help support
 *       \version   $Id$
 */

include_once("./inc.php");

$langs->load("other");
$langs->load("help");


pHeader("Dolibarr Help Service Center",$_SERVER["PHP_SELF"]);

print $langs->trans("HelpCenterDesc1")."<br>\n";
print $langs->trans("HelpCenterDesc2")."<br>\n";

print '<br><br><br>';

print '<table border="1">';


// Line of possible services
print '<tr>';
print '<td align="center">';
print '<b>'.$langs->trans("CommunitySupport").'</b>';
print '</td>';
print '<td align="center">';
print '<b>'.$langs->trans("EMailSupport").'</b>';
print '</td>';
print '<td align="center">';
print '<b>'.$langs->trans("RemoteControlSupport").'</b>';
print '</td>';
print '</tr>';


// Area of support cells
print '<tr>';
print '<td width="33%" align="center" valign="top" style="background:#FFFFFF">';

print '<table class="nocellnopadd"><tr><td align="center" valign="top">';
print img_picto('','/theme/common/who.png','',1);
print '</td></tr><tr><td align="center">';
$urlwiki='http://wiki.dolibarr.org';
if ($langs->defaultlang == 'fr_FR') $urlwiki='http://wiki.dolibarr.org/index.php/Accueil';
if ($langs->defaultlang == 'es_ES') $urlwiki='http://wiki.dolibarr.org/index.php/Portada';
print '<br>'.$langs->trans("ForDocumentationSeeWiki",$urlwiki,$urlwiki).'<br>';
print '<br>'.$langs->trans("ForAnswersSeeForum",'http://www.dolibarr.org/forum/','http://www.dolibarr.org/forum/').'<br>';
print '</td></tr></table>';

print '</td>';
print '<td width="34%" align="center" valign="top" style="background:#FFFFFF">';

print '<table class="nocellnopadd"><tr><td align="center" valign="top">';
print img_picto('','/theme/common/mail.png','',1);
print '</td></tr><tr><td align="center">';
print '<br>'.$langs->trans("FeatureNotYetAvailable").'.';
print '</td></tr></table>';

print '</td>';
print '<td width="33%" align="center" valign="top" style="background:#FFFFFF">';

print '<table class="nocellnopadd"><tr><td align="center" valign="top">';
print img_picto('','/theme/common/internet.png','',1);
print '</td></tr><tr><td align="center">';
print '<br>'.$langs->trans("ToSeeListOfAvailableRessources").' ';
print '<a href="online.php">'.$langs->trans("ClickHere").'</a>';
print '</td></tr></table>';

print '</td>';
print '</tr>';



print '</table>';

pFooter();
?>
