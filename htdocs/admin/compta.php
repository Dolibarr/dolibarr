<?PHP
/* Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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
require("./pre.inc.php");

if (!$user->admin)
  accessforbidden();


llxHeader();


$compta_mode = defined("COMPTA_MODE")?COMPTA_MODE:"RECETTES-DEPENSES";

if ($action == 'setcomptamode')
{
  $compta_mode = $HTTP_POST_VARS["compta_mode"];
  dolibarr_set_const($db, "COMPTA_MODE",$compta_mode);
}


print_titre("Module de comptabilité");

print '<table class="noborder" cellpadding="3" cellspacing="0" width=\"100%\">';

print '<form action="'.$PHP_SELF.'" method="post">';
print '<input type="hidden" name="action" value="setcomptamode">';
print '<TR class="liste_titre">';
print '<td>Option de tenue de comptabilité</td><td>Description</td>';
print '<td><input type="submit" value="Modifier"></td>';
print "</TR>\n";
print "<tr ".$bc[True]."><td width=\"200\"><input type=\"radio\" name=\"compta_mode\" value=\"RECETTES-DEPENSES\"".($compta_mode != "CREANCES-DETTES"?" checked":"")."> Option Recettes-Dépenses</td>";
print "<td colspan=\"2\">Dans ce mode, le CA est calculé sur la base des factures à l'état payé.\nLa validité des chiffres n'est donc assurée que si la tenue de la comptabilité passe rigoureusement par des entrées/sorties sur les comptes via des factures.\nDe plus, dans cette version, Dolibarr utilise la date de passage de la facture à l'état 'Validé' et non la date de passage à l'état 'Payé'.</td></tr>\n";
print "<tr ".$bc[False]."><td width=\"200\"><input type=\"radio\" name=\"compta_mode\" value=\"CREANCES-DETTES\"".($compta_mode == "CREANCES-DETTES"?" checked":"")."> Option Créances-Dettes</td>";
print "<td colspan=\"2\">Dans ce mode, le CA est calculé sur la base des factures validées. Qu'elles soient ou non payés, dès lors qu'elles sont dues, elles apparaissent dans le résultat.</td></tr>\n";
print "</form>";
print "</table>";

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
