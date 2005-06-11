<?php
/* Copyright (C) 2005      Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * $Source$
 */

/**	
        \file       htdocs/admin/dons.php
		\ingroup    dons
		\brief      Page d'administration/configuration du module Dons
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("admin");
$langs->load("donations");

if (!$user->admin)
  accessforbidden();


$dons_addon_var      = FACTURE_ADDON;


$typeconst=array('yesno','texte','chaine');


if ($_GET["action"] == 'set')
{
  if (dolibarr_set_const($db, "FACTURE_ADDON",$_GET["value"]))
    $facture_addon_var = $_GET["value"];
}



$dir = "../compta/dons/formulaire";


llxHeader('',$langs->trans("DonationsSetup"),'DonConfiguration');

print_titre($langs->trans("DonationsSetup"));


/*
 *  PDF
 */
print '<br>';
print_titre("Modèles de bon de dons");

print '<table class="noborder" width=\"100%\">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td align="center" width="60">'.$langs->trans("Activated").'</td>';
print '<td width="80">&nbsp;</td>';
print "</tr>\n";

clearstatcache();

$handle=opendir($dir);

$var=True;
while (($file = readdir($handle))!==false)
{
  if (substr($file, strlen($file) -4) == '.php')
    {
	  $var = !$var;

      print '<tr '.$bc[$var].'><td width=\"100\">';
      echo "$file";
      print '</td><td>&nbsp;</td><td align="center">';

      if ($facture_addon_var_pdf == "$name")
	{
	  print img_tick();
      print '</td><td align="center">';
      print '&nbsp;';
	}
      else
	{
	  print '&nbsp;';
      print '</td><td align="center">';
      print '<a href="dons.php?action=setform&value='.$name.'">'.$langs->trans("Activate").'</a>';
	}
	print "</td></tr>\n";

    }
}
closedir($handle);

print '</table>';


print "<br>";


$db->close();

llxFooter('$Date$ - $Revision$');
?>
