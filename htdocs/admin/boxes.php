<?PHP
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

print_titre("Boites");

print "<br>";
print "Les boites sont des cartouches d'informations réduites qui s'affichent sur certaines pages. Vous pouvez choisir ou non d'activer ces cartouches en ciquant sur 'Ajouter' ou la poubelle pour les désactiver. ";
print "Seules les boites en rapport avec un module actif sont présentées.<br>\n";


if ($HTTP_POST_VARS["action"] == 'add')
{

  $sql = "INSERT INTO ".MAIN_DB_PREFIX."boxes (box_id, position) values (".$HTTP_POST_VARS["rowid"].",".$HTTP_POST_VARS["constvalue"].");";

  $result = $db->query($sql);
}

if ($_GET["action"] == 'add')
{

  $sql = "INSERT INTO ".MAIN_DB_PREFIX."boxes (box_id, position) values (".$_GET["rowid"].",0);";

  $result = $db->query($sql);
}


if ($_GET["action"] == 'delete')
{
  $sql = "DELETE FROM ".MAIN_DB_PREFIX."boxes WHERE rowid=$rowid";

  $result = $db->query($sql);
}


// Définition des positions possibles pour les boites
$pos_array = array(0);      // Positions possibles pour une boite (0,1,2,...)
$pos_name = array();        // Nom des position 0=Homepage, 1=...
$pos_name[0]="Homepage";
$boxes = array();


/*
 * Recherche des boites actives par position possible
 * On stocke les boites actives par $boxes[position][id_boite]=1
 *
 */

$sql = "SELECT b.rowid, b.box_id, b.position, d.name FROM ".MAIN_DB_PREFIX."boxes as b, ".MAIN_DB_PREFIX."boxes_def as d where b.box_id = d.rowid";
$result = $db->query($sql);

if ($result) 
{
  $num = $db->num_rows();
  $i = 0;

  while ($i < $num)
    {
      $var = ! $var;
      $obj = $db->fetch_object( $i);
      //print "pos ".$obj->position;
      $boxes[$obj->position][$obj->box_id]=1;
      $i++;
    }
}
$db->free();


/*
 * Boites disponibles
 *
 */
print '<br>';
print_titre("Boites disponibles");

print '<table class="noborder" cellpadding="3" cellspacing="0" width="100%">';
print '<tr class="liste_titre">';
print '<td>Boites</td>';
print '<td>Fichier source</td>';
foreach ($pos_array as $position) {
    print '<td align="center">Activation '.$pos_name[$position].'</td>';
}
print "</tr>\n";

$sql = "SELECT rowid, name, file FROM ".MAIN_DB_PREFIX."boxes_def";
$result = $db->query($sql);
$var=True;

if ($result) 
{
    $num = $db->num_rows();
    $i = 0;
    
    while ($i < $num)
    {
        $var = ! $var;
        $obj = $db->fetch_object( $i);
        
        print '<tr '.$bc[$var].'><td width="200">'.$obj->name.'</td><td width="200">' . $obj->file . '</td>';

        // Pour chaque position possible, on affiche un lien 
        // d'activation si boite non deja active pour cette position
        foreach ($pos_array as $position) {
            print '<td width="50" align="center">';
            if (! $boxes[$position][$obj->rowid])
            {
                print '<a href="'.$PHP_SELF.'?rowid='.$obj->rowid.'&amp;action=add&pos='.$position.'">Ajouter</a>';
            }
            else
            {
                print "&nbsp;";
            }
            print '</td>';
        }

        print '</tr>';
    
        $i++;
    }
}
$db->free();

print '</table>';


print "<br>\n";
print_titre("Boites activées");

print '<table class="noborder" cellpadding="3" cellspacing="0" width="100%">';
print '<tr class="liste_titre">';
print '<td>Boites</td>';
print '<td>Active pour</td>';
print '<td align="center">Désactiver</td>';
print "</tr>\n";

$sql = "SELECT b.rowid, b.box_id, b.position, d.name FROM ".MAIN_DB_PREFIX."boxes as b, ".MAIN_DB_PREFIX."boxes_def as d where b.box_id = d.rowid";
$result = $db->query($sql);

if ($result) 
{
  $num = $db->num_rows();
  $i = 0;
  
  while ($i < $num)
    {
      $var = ! $var;
      $obj = $db->fetch_object( $i);

      print '<tr '.$bc[$var].'><td width="200">'.$obj->name.'</td><td width="200">' . $pos_name[$obj->position] . '</td><td width="50" align="center">';
      print '<a href="'.$PHP_SELF.'?rowid='.$obj->rowid.'&amp;action=delete">'.img_delete().'</a>';
      print '</td></tr>';
      $i++;
    }
}
$db->free();


print '</table>';


$db->close();

llxFooter();
?>
