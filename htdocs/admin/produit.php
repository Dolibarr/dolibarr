<?php
/* Copyright (C) 2004-2005 Laurent Destailleur       <eldy@users.sourceforge.net>
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
	    \file       htdocs/admin/produit.php
		\ingroup    produit
		\brief      Page d'administration/configuration du module Produit
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("admin");
$langs->load("propal");

if (!$user->admin)
  accessforbidden();


if ($_POST["action"] == 'nbprod')
{
    dolibarr_set_const($db, "PRODUIT_LIMIT_SIZE", $_POST["value"]);
    Header("Location: produit.php");
    exit;
}



/*
 * Affiche page
 */

llxHeader('',$langs->trans("ProductSetup"));


print_titre($langs->trans("ProductSetup"));

/*
 * Formulaire parametres divers
 */

print '<br>';
print "<form method=\"post\" action=\"produit.php\">";
print "<input type=\"hidden\" name=\"action\" value=\"nbprod\">";
print "<table class=\"noborder\" width=\"100%\">";
print "<tr class=\"liste_titre\">";
print "  <td>".$langs->trans("Name")."</td>\n";
print "  <td align=\"left\">".$langs->trans("Value")."</td>\n";
print "  <td>&nbsp;</td>\n";
print "</tr><tr ".$bc[false].">";
print '<td>'.$langs->trans("NumberOfProductShowInSelect").'</td>';
print "<td align=\"left\"><input size=\"3\" type=\"text\" class=\"flat\" name=\"value\" value=\"".$conf->global->PRODUIT_LIMIT_SIZE."\"></td>";
print '<td><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
print '</tr>';
print '</table>';
print '</form>';

$db->close();

llxFooter();
?>
