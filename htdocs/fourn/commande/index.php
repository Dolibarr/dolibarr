<?PHP
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 */

/**
   \file       htdocs/fourn/commande/index.php
   \ingroup    commande
   \brief      Page accueil commandes fournisseurs
   \version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/contact.class.php");

llxHeader('',"Commandes Fournisseurs");

// Sécurité accés client
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}

$commande = new CommandeFournisseur($db);

print_barre_liste($langs->trans("SuppliersOrders"), $page, "index.php", "", $sortfield, $sortorder, '', $num);

print '<table class="noborder" width="100%">';
print '<tr valign="top"><td width="30%">';

$sql = "SELECT count(cf.rowid), fk_statut,";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s,";
$sql.= " ".MAIN_DB_PREFIX."commande_fournisseur as cf";
$sql.= " WHERE cf.fk_soc = s.rowid ";
$sql.= " GROUP BY cf.fk_statut";

$resql = $db->query($sql);
if ($resql)
{
  $num = $db->num_rows($resql);
  $i = 0;
  
  print '<table class="liste" width="100%">';
  print '<tr class="liste_titre"><td>'.$langs->trans("Status").'</td><td align="center">'.$langs->trans("Nb").'</td><td>&nbsp;</td>';
  print "</tr>\n";
  $var=True;

  while ($i < $num)
    {
      $row = $db->fetch_row($resql);
      $var=!$var;

      print "<tr $bc[$var]>";
      print '<td>'.$commande->statuts[$row[1]].'</td>';
      print '<td align="center">'.$row[0].'</td>';
      print '<td align="center"><a href="liste.php?statut='.$row[1].'">'.$commande->LibStatut($row[1],3).'</a></td>';

      print "</tr>\n";
      $i++;
    }
  print "</table>";
  $db->free($resql);
}
else 
{
  dolibarr_print_error($db);
}
/*
 *
 */
print '</td><td width="70%" valign="top">';
/*
 *
 */
$sql = "SELECT u.name, u.firstname";
$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
$sql .= " , ".MAIN_DB_PREFIX."user_rights as ur";
$sql .= " WHERE u.rowid = ur.fk_user";
$sql .= " AND ur.fk_id = 184";

$resql = $db->query($sql);
if ($resql)
{
  $num = $db->num_rows($resql);
  $i = 0;
  
  print '<table class="liste" width="100%">';
  print '<tr class="liste_titre"><td>Personnes habilitées à approuver les commandes</td>';
  print "</tr>\n";
  $var=True;

  while ($i < $num)
    {
      $row = $db->fetch_row($resql);
      $var=!$var;

      print "<tr $bc[$var]>";
      print '<td>'.$row[1].' '.$row[0].'</td>';
      print "</tr>\n";
      $i++;
    }
  print "</table>";
  $db->free($resql);
}
else 
{
  dolibarr_print_error($db);
}

print '</td></tr></table>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
