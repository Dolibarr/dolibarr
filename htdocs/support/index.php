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


pHeader("Dolibarr Help Service Center",$_SERVER["PHP_SELF"]);

print $langs->trans("HelpCenterDesc1")."<br>\n";
print $langs->trans("HelpCenterDesc2")."<br>\n";

print '<br><br><br>';

print '<table border="1">';


// Line of possible services
print '<tr>';
print '<td align="center">';
print '<b>Community support</b>';
print '</td>';
print '<td align="center">';
print '<b>EMailing support</b>';
print '</td>';
print '<td align="center">';
print '<b>Remote control support</b>';
print '</td>';
print '</tr>';


// Area of support cells
print '<tr>';
print '<td width="33%" align="center" valign="top">';

print '<table class="nocellnopadd"><tr><td align="center" valign="top">';
print img_picto('','/theme/common/who.png','',1);
print '</td></tr><tr><td align="center">';
print '<br>'.$langs->trans("ForDocumentationSeeWiki",'http://wiki.dolibarr.org','http://wiki.dolibarr.org').'<br>';
print '<br>'.$langs->trans("ForAnswersSeeForum",'http://www.dolibarr.org','http://www.dolibarr.org').'<br>';
print '</td></tr></table>';

print '</td>';
print '<td width="34%" align="center" valign="top">';

print '<table class="nocellnopadd"><tr><td align="center" valign="top">';
print img_picto('','/theme/common/pagemaster.png','',1);
print '</td></tr><tr><td align="center">';
print '<br>'.$langs->trans("FeatureNotYetAvailable").'.';
print '</td></tr></table>';

print '</td>';
print '<td width="33%" align="center" valign="top">';

print '<table class="nocellnopadd"><tr><td align="center" valign="top">';
print img_picto('','/theme/common/internet.png','',1);
print '</td></tr><tr><td align="center">';
print '<br>'.$langs->trans("FeatureNotYetAvailable").'.';
print '</td></tr></table>';

print '</td>';
print '</tr>';



print '</table>';

pFooter();
?>
