<?PHP
/* Copyright (C) 2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

$dir = $conf->telephonie->dir_output."/cdr/atraiter/" ;

$handle=opendir($dir);

$files = array();

$var=true;
while (($file = readdir($handle))!==false)
{
  if (is_file($dir.'/'.$file))
    array_push($files, $file);
}
closedir($handle);



if (!$user->rights->telephonie->facture->ecrire) accessforbidden();

$page = $_GET["page"];
$sortorder = $_GET["sortorder"];
$sortfield = $_GET["sortfield"];
if ($page == -1) { $page = 0 ; }

$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;


llxHeader();

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}

if ($sortorder == "") {
  $sortorder="";
}
if ($sortfield == "") {
  $sortfield="date ASC";
}

/*
 * Mode Liste
 *
 */

$sql = "SELECT ligne,date,heure,num, montant, duree,fichier";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_import_cdr";
$sql .= " WHERE 1=1";
if ($_GET["search_ligne"])
{
  $sel =urldecode($_GET["search_ligne"]);
  $sel = ereg_replace("\.","",$sel);
  $sel = ereg_replace(" ","",$sel);
  $sql .= " AND ligne LIKE '%".$sel."%'";
}
$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit+1, $offset);
$resql = $db->query($sql);

$num = $db->num_rows($resql);


print_barre_liste("CDR a traiter", $page, "cdr.php", "", $sortfield, $sortorder, '', $num);

print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
print '<tr class="liste_titre">';
print '<td>Ligne</td><td>Numero</td><td>Date</td><td align="right">Duree</td>';
print '<td align="right">Montant</td><td>Fichier</td>';
print "</tr>\n";

print '<tr class="liste_titre">';
print '<form action="cdr.php" method="GET">';
print '<td><input type="text" name="search_ligne" value="'. $_GET["search_ligne"].'" size="10"></td>'; 
print '<td><input type="text" name="search_num" value="'. $_GET["search_num"].'" size="10"></td>';
print '<td>&nbsp;</td>';
print '<td>&nbsp;</td>';
print '<td>&nbsp;</td>';
print '<td><input type="submit" class="button" value="'.$langs->trans("Search").'"></td>';

$var=True;


while ($obj = $db->fetch_object($resql))
{
  $var=!$var;
  
  print "<tr $bc[$var]>";
  print '<td>'.$obj->ligne."</td>\n";
  print '<td>'.$obj->num."</td>\n";
  print '<td>'.$obj->date." ".$obj->heure."</td>\n";
  print '<td align="right">'.$obj->duree."</td>\n";
  print '<td align="right">'.$obj->montant."</td>\n";
  print '<td align="right">'.$obj->fichier."</td>\n";

}
print "</table>";


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
