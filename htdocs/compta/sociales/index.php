<?PHP
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

llxHeader();

function valeur($sql)
{
  global $db;
  if ( $db->query($sql) )
    {
      if ( $db->num_rows() )
	{
	  $valeur = $db->result(0,0);
	}
      $db->free();
    }
  return $valeur;
}


/*
 *
 */

if ($action == 'add')
{
  $sql = "INSERT INTO ".MAIN_DB_PREFIX."chargesociales (fk_type, libelle, date_ech, amount) ";
  $sql .= " VALUES ($type,'$libelle','$date',$amount);";

  if (! $db->query($sql) )
    {
      print $db->error();
    }
}

if ($_GET["action"] == 'del')
{
  $sql = "DELETE FROM ".MAIN_DB_PREFIX."chargesociales where rowid='".$_GET["id"]."'";

  if (! $db->query($sql) )
    {
      print $db->error();
    }
}

$year=$_GET["year"];
$filtre=$_GET["filtre"];

/*
 *  Affichage liste et formulaire des charges.
 */

print_titre("Charges sociales $year");

print "<br>\n";

print "<table class=\"noborder\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
print "<tr class=\"liste_titre\">";
print '<td>';
print_liste_field_titre("Echéance/Date",$PHP_SELF,"de");
print '</td><td>';
print_liste_field_titre("Période",$PHP_SELF,"periode");
print '</td><td align="left">';
print_liste_field_titre("Type",$PHP_SELF,"type");
print '</td><td align="left">';
print_liste_field_titre("Libellé",$PHP_SELF,"s.libelle");
print '</td><td align="right">';
print_liste_field_titre("Montant",$PHP_SELF,"s.amount");
print '</td><td align="center">';
print_liste_field_titre("Payé",$PHP_SELF,"s.paye");
print '</td><td>&nbsp;</td>';
print "</tr>\n";


$sql = "SELECT s.rowid as id, c.libelle as type, s.amount,".$db->pdate("s.date_ech")." as de, s.date_pai, s.libelle, s.paye,".$db->pdate("s.periode")." as periode,".$db->pdate("s.date_pai")." as dp";
$sql .= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c, ".MAIN_DB_PREFIX."chargesociales as s";
$sql .= " WHERE s.fk_type = c.id";
if ($year > 0)
{
    $sql .= " AND date_format(s.periode, '%Y') = $year";
}
if ($filtre) {
    $filtre=ereg_replace(":","=",$filtre);
    $sql .= " AND $filtre";
}
$sql .= " ORDER BY lower(s.date_ech) DESC";

if ( $db->query($sql) )
{
  $num = $db->num_rows();
  $i = 0;

  while ($i < $num)
    {
      $obj = $db->fetch_object( $i);

      $var = !$var;
      print "<tr $bc[$var]>";
      print '<td>'.strftime("%d %b %y",$obj->de).'</td>';
      print '<td>';
      if ($obj->periode) {
      	print '<a href="index.php?year='.strftime("%Y",$obj->periode).'">'.strftime("%Y",$obj->periode).'</a>';
      } else {
      	print '&nbsp;';
      }
      print '</td>';
      print '<td>'.$obj->type.'</td><td>'.$obj->libelle.'</td>';
      print '<td align="right">'.price($obj->amount).'</td>';
      
      if ($obj->paye)
	{
	  print '<td align="center" class="normal">'.strftime("%d/%m/%y",$obj->dp).'</td>';
	  print '<td>&nbsp;</td>';
	} else {
	  print '<td align="center"><a class="impayee" href="'.$PHP_SELF.'?filtre=paye:0">Non</a></td>';
	  print '<td align="center"><a href="charges.php?id='.$obj->id.'">'.img_edit().'</a>';
	  print ' &nbsp; <a href="'.$PHP_SELF.'?action=del&id='.$obj->id.'">'.img_delete().'</a></td>';
	}
      print '</tr>';
      $i++;
    }
}
else
{
  print $db->error();
}

/*
 * Forumalaire d'ajout d'une charge
 *
 */
print '<tr class="form"><form method="post" action="index.php">';
print '<input type="hidden" name="action" value="add">';
print '<td><input type="text" size="8" name="date"> YYYYMMDD</td>';
print '<td>&nbsp;</td>';

print '<td align="left"><select name="type">';

$sql = "SELECT c.id, c.libelle as type FROM ".MAIN_DB_PREFIX."c_chargesociales as c";
$sql .= " ORDER BY lower(c.libelle) ASC";

if ( $db->query($sql) )
{
  $num = $db->num_rows();
  $i = 0;

  while ($i < $num)
    {
      $obj = $db->fetch_object( $i);
      print '<option value="'.$obj->id.'">'.$obj->type;
      $i++;
    }
}
print '</select>';
print '</td>';
print '<td align="left"><input type="text" size="20" name="libelle"></td>';
print '<td align="right"><input type="text" size="6" name="amount"></td>';
print '<td>&nbsp;</td>';

print '<td><input type="submit" value="Ajouter"></form></td>';
print '</tr>';

print '</table>';



$db->close();
 
llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
