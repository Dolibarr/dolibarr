<?PHP
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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


$page = $_GET["page"];
$sortorder = $_GET["sortorder"];

llxHeader();

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}

if ($sortorder == "") {
  $sortorder="DESC";
}
if ($sortfield == "") {
  $sortfield="l.date";
}

if ($page == -1) { $page = 0 ; }

$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

/*
 * Mode Liste
 *
 *
 *
 */

$sql = "SELECT ligne, numero, date, fourn_cout, fourn_montant, duree, tarif_achat_temp, tarif_achat_fixe, tarif_vente_temp, tarif_vente_fixe, cout_achat, cout_vente, remise";

$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_communications_details as l";

if ($_GET["ligne"])
{
  $sql .= " WHERE ligne like '%".$_GET["ligne"]."%'";
  
  if ($_GET["numero"])
    {
      $sql .= " AND numero like '%".$_GET["numero"]."%'";
      
    }  
}

//$sql .= " WHERE fourn_montant > cout_vente AND cout_vente > 0";


$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit+1, $offset);

$result = $db->query($sql);
if ($result)
{
  $num = $db->num_rows();
  $i = 0;
  
  print_barre_liste("Communications", $page, "communications.php", '&amp;ligne='.$_GET["ligne"], $sortfield, $sortorder, '', $num);

  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre">';
  print_liste_field_titre("Ligne","communications.php","l.ligne");
  print '<td align="center">Date</td><td align="center">Numéro</td><td align="center">';
  print "Durée</td>";
  print '<td align="right">Prix Vente</td><td align="right">Cout Achat</td>';

  print '<td align="right">Vente /sec</td><td align="right">Vente Fixe</td>';
  print '<td align="right">Achat /sec</td><td align="right">Achat Fixe</td>';
  print '<td align="right">'.$langs->trans("Discount").'</td>';
  print "</tr>\n";

  print '<tr class="liste_titre">';
  print '<form action="communications.php" method="GET">';
  print '<td><input type="text" name="ligne" value="'. $_GET["ligne"].'" size="20"></td>';
  print '<td>&nbsp;</td>';
  print '<td align="center"><input type="text" name="numero" value="'. $_GET["numero"].'" size="12"></td>';

  print '<td><input type="submit" class="button" value="'.$langs->trans("Search").'"></td>';



  print '<td>&nbsp;</td>';
  print '<td align="center" colspan="6">Tarifs</td>';

  print '</form>';
  print '</tr>';


  $var=True;

  while ($i < min($num,$conf->liste_limit))
    {
      $obj = $db->fetch_object($i);	
      $var=!$var;

      print "<tr $bc[$var]>";
      print "<td>".$obj->ligne."</td>\n";
      print '<td align="center">'.$obj->date."</td>\n";
      print '<td align="center">'.$obj->numero."</td>\n";
      print '<td align="center">'.$obj->duree."</td>\n";

      print '<td align="right">'.sprintf("%01.4f",$obj->cout_vente)."</td>\n";
      print '<td align="right">'.sprintf("%01.4f",$obj->cout_achat)."</td>\n";

      print '<td align="right">'.sprintf("%01.4f",$obj->tarif_vente_temp)."</td>\n";
      print '<td align="right">'.sprintf("%01.4f",$obj->tarif_vente_fixe)."</td>\n";

      print '<td align="right">'.sprintf("%01.4f",$obj->tarif_achat_temp)."</td>\n";
      print '<td align="right">'.sprintf("%01.4f",$obj->tarif_achat_fixe)."</td>\n";

      print '<td align="center">'.$obj->remise." %</td>\n";

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

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
