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

$user->getrights('facture');
$user->getrights('compta');

if (!$user->admin && !$user->rights->compta->charges->lire)
  accessforbidden();


llxHeader();


/*
 * Ajout d'une charge sociale
 */

if ($action == 'add')
{
  if (! $_POST["date"] || ! $_POST["periode"] || ! $_POST["amount"]) {
    $mesg="<div class=\"error\">Erreur: Tous les champs date et montant doivent etre renseignés avec une valeur non vide.</div>";
  }
  else {

      $sql = "INSERT INTO ".MAIN_DB_PREFIX."chargesociales (fk_type, libelle, date_ech, periode, amount) ";
      $sql .= " VALUES (".$_POST["type"].",'".addslashes($_POST["libelle"])."','".$_POST["date"]."','".$_POST["periode"]."','".$_POST["amount"]."');";
    
      if (! $db->query($sql) )
        {
          print $db->error();
        }
      else {
        $mesg="<div class=\"ok\">La charge a été ajoutée.</div>";
      }
  }
}

/*
 * Suppression d'une charge sociale
 */

if ($_GET["action"] == 'del')
{
  $sql = "DELETE FROM ".MAIN_DB_PREFIX."chargesociales where rowid='".$_GET["id"]."'";

  if (! $db->query($sql) )
    {
      print $db->error();
    }
}



/*
 *  Affichage liste et formulaire des charges.
 */

$year=$_GET["year"];
$filtre=$_GET["filtre"];
//if (! $year) { $year=date("Y", time()); }

print_fiche_titre("Charges sociales",($year?"<a href='index.php?year=".($year-1)."'>".img_previous()."</a> Année $year <a href='index.php?year=".($year+1)."'>".img_next()."</a>":""));
print "<br>\n";

if ($mesg) {
    print "$mesg<br>";
}

print "<table class=\"noborder\" width=\"100%\" cellspacing=\"0\" cellpadding=\"2\">";
print "<tr class=\"liste_titre\">";
print '<td>';
print_liste_field_titre("Num","index.php","id");
print '</td>';
print '<td>';
print_liste_field_titre("Echéance","index.php","de");
print '</td><td>';
print_liste_field_titre("Période","index.php","periode");
print '</td><td align="left">';
print_liste_field_titre("Type","index.php","type");
print '</td><td align="left">';
print_liste_field_titre("Libellé","index.php","s.libelle");
print '</td><td align="right">';
print_liste_field_titre("Montant","index.php","s.amount");
print '</td><td align="center">';
print_liste_field_titre("Statut","index.php","s.paye");
print '</td><td>&nbsp;</td>';
print "</tr>\n";


$sql = "SELECT s.rowid as id, s.fk_type as type, c.libelle as type_lib, s.amount,".$db->pdate("s.date_ech")." as de, s.date_pai, s.libelle, s.paye,".$db->pdate("s.periode")." as periode,".$db->pdate("s.date_pai")." as dp";
$sql .= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c, ".MAIN_DB_PREFIX."chargesociales as s";
$sql .= " WHERE s.fk_type = c.id";
if ($year > 0)
{
    $sql .= " AND (";
    // Si period renseigné on l'utilise comme critere de date, sinon on prend date échéance,
    // ceci afin d'etre compatible avec les cas ou la période n'etait pas obligatoire
    $sql .= "   (s.periode is not null and date_format(s.periode, '%Y') = $year) ";
    $sql .= "or (s.periode is null     and date_format(s.date_ech, '%Y') = $year)";
    $sql .= ")";
}
if ($filtre) {
    $filtre=ereg_replace(":","=",$filtre);
    $sql .= " AND $filtre";
}
if ($_GET["sortfield"]) {
    $sql .= " ORDER BY ".$_GET["sortfield"];    
}
else {
    $sql .= " ORDER BY lower(s.date_ech)";
}
if ($_GET["sortorder"]) {
    $sql .= " ".$_GET["sortorder"];
}
else {
    $sql .= " DESC";
}



if ( $db->query($sql) )
{
  $num = $db->num_rows();
  $i = 0;

  while ($i < $num)
    {
      $obj = $db->fetch_object( $i);

      $var = !$var;
      print "<tr $bc[$var]>";
      print '<td width="80"><a href="charges.php?id='.$obj->id.'">'.$obj->id.'</a></td>';
      print '<td width="110">'.dolibarr_print_date($obj->de).'</td>';
      print '<td>';
      if ($obj->periode) {
      	print '<a href="index.php?year='.strftime("%Y",$obj->periode).'">'.strftime("%Y",$obj->periode).'</a>';
      } else {
      	print '&nbsp;';
      }
      print '</td>';
      print '<td>'.$obj->type_lib.'</td><td>'.$obj->libelle.'</td>';
      print '<td align="right" width="100">'.price($obj->amount).'</td>';
      
      if ($obj->paye)
	{
	  print '<td align="center" class="normal" width="100"><a class="payee" href="index.php?filtre=paye:1">Payé</a></td>';
	  print '<td>&nbsp;</td>';
	} else {
	  print '<td align="center"><a class="impayee" href="index.php?filtre=paye:0">Impayé</a></td>';
	  print '<td align="center" nowrap>';
	  if ($user->rights->compta->charges->creer) {
	    print '<a href="charges.php?id='.$obj->id.'">'.img_edit().'</a>';
	  }
	  if ($user->rights->compta->charges->supprimer) {
	    print ' &nbsp; <a href="index.php?action=del&id='.$obj->id.'">'.img_delete().'</a>';
	  }
	  print '</td>';
	}
      print '</tr>';
      $i++;
    }
}
else
{
  print "Error :".$db->error()." - $sql";
}

/*
 * Forumalaire d'ajout d'une charge
 *
 */
if ($user->rights->compta->charges->creer) {
    print '<tr class="form" valign="top"><form method="post" action="index.php">';
    print '<input type="hidden" name="action" value="add">';
    print '<td>&nbsp;</td>';
    print '<td><input type="text" size="8" name="date"><br>YYYYMMDD</td>';
    print '<td><input type="text" size="8" name="periode"><br>YYYYMMDD</td>';
    
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
    print '<td align="left"><input type="text" size="24" name="libelle"></td>';
    print '<td align="right"><input type="text" size="6" name="amount"></td>';
    print '<td>&nbsp;</td>';
    
    print '<td><input type="submit" value="Ajouter"></form></td>';
    print '</tr>';
}

print '</table>';



$db->close();
 
llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
