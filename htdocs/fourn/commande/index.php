<?PHP
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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
require("./pre.inc.php");
require_once DOL_DOCUMENT_ROOT."/contact.class.php";

llxHeader('',"Commandes Fournisseurs");

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}

$commande = new CommandeFournisseur($db);

print_barre_liste("Commandes fournisseurs", $page, "index.php", "", $sortfield, $sortorder, '', $num);

print '<table class="noborder" width="100%">';
print '<tr><td width="30%">';

$sql = "SELECT count(cf.rowid), fk_statut";
$sql .= " ,cf.rowid,cf.ref";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s ";
$sql .= " , ".MAIN_DB_PREFIX."commande_fournisseur as cf";
$sql .= " WHERE cf.fk_soc = s.idp ";

if ($socidp) {
  $sql .= " AND s.idp=".$socidp;
}
$sql .= " GROUP BY cf.fk_statut";

$result = $db->query($sql);
if ($result)
{
  $num = $db->num_rows();
  $i = 0;
  
  print '<table class="liste" width="100%">';
  print '<tr class="liste_titre"><td>Statut</td><td align="center">Nb</td><td>&nbsp;</td>';
  print "</tr>\n";
  $var=True;

  while ($i < $num)
    {
      $row = $db->fetch_row();
      $var=!$var;

      print "<tr $bc[$var]>";
      print '<td>'.$commande->statuts[$row[1]].'</td>';
      print '<td align="center">'.$row[0].'</td>';
      print '<td align="center"><a href="liste.php?statut='.$row[1].'"><img src="statut'.$row[1].'.png" border="0" alt="Statut"></a></td>';

      print "</tr>\n";
      $i++;
    }
  print "</table>";
  $db->free();
}
else 
{
  print $db->error() . ' ' . $sql;
}

print '</td><td>&nbsp;</td>';

print '</td></tr></table>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
