<?php
/* Copyright (C) 2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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

print "This area can be used to get a Help service support.<br>\n";
print "This service is available in <b>english only</b>.<br><br>";

print '<br><br>';

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


// Logo of possible services
print '<tr>';
print '<td>';
print $langs->trans("FeatureNotYetAvailable").'.';
print '</td>';
print '<td>';
print $langs->trans("FeatureNotYetAvailable").'.';
print '</td>';
print '<td>';
print $langs->trans("FeatureNotYetAvailable").'.';
print '</td>';
print '</tr>';




print '</table>';

pFooter();
?>
