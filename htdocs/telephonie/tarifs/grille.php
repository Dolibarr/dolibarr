<?PHP
/* Copyright (C) 2005-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
$sortfield = $_GET["sortfield"];

$sql = "SELECT pwrite, pread ";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_tarif_grille_rights ";
$sql .= " WHERE fk_grille = '".$_GET["id"]."'";
$sql .= " AND fk_user = ".$user->id;

$auth_write = 0;

$resql = $db->query($sql);
if ($resql)
{
  $num = $db->num_rows();
  $i = 0;

  if ($num > 0)
    {
      $row = $db->fetch_row($resql);
      $auth_write = $row[0];
      $auth_read = $row[1];
    }
  $db->free($resql);
}

if ($auth_read == 0)
  accessforbidden();

/*
 *
 *
 */
if ($_POST["action"] == 'modif' && $auth_write)
{
  $sortorder = "DESC";
  $sortfield = "m.tms";

  $temporel = ereg_replace(",",".",$_POST["temporel"]);
  $fixe = ereg_replace(",",".",$_POST["fixe"]);

  if ($temporel > 0 or $_POST["gratuit"] == 'on')
    {
      require_once DOL_DOCUMENT_ROOT."/telephonie/telephonie.tarif.grille.class.php";

      $obgrille = new TelephonieTarifGrille($db);

      $obgrille->UpdateTarif($_GET["id"], $_POST["tarif"], $temporel, $fixe, $user);

      Header("Location: grille.php?id=".$_GET["id"]);
    }
}

llxHeader();

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}




/*
 * Mode Liste
 *
 *
 *
 */
print '<table width="100%" class="noborder">';
print '<tr><td valign="top" width="30%">';

$sql = "SELECT d.libelle as tarif_desc, d.type_tarif";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_tarif_grille as d";
$sql .= " WHERE d.rowid = '".$_GET["id"]."'";

$resql = $db->query($sql);
if ($resql)
{
  $num = $db->num_rows();
  $i = 0;

  if ($num > 0)
    {
      $grille = $db->fetch_row($resql);
    }
}

print "Grille : ".$grille[0]."<br>";

//print '<a href="grille-export.php?id='.$_GET["id"].'">Export tableur</a><br><br>';

if ($auth_write)
{

  print '<form method="POST" action="grille.php?id='.$_GET["id"].'">';
  print '<input type="hidden" name="token_level_1" value="'.$_SESSION['newtoken'].'">';
  print '<input type="hidden" name="action" value="modif">';
  print '<table width="100%" class="border">';
  print '<tr><td colspan="2">Modification</td></tr>';
  print '<tr><td>Tarif</td>';
  print '<td><select name="tarif">';
  
  $sql = "SELECT rowid, libelle";
  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_tarif";
  //$sql .= " WHERE tlink = 0";
  $sql .= " ORDER BY libelle ASC";
  
  $resql = $db->query($sql);
  if ($resql)
    {
      $num = $db->num_rows();
      $i = 0;
      
      while ($i < $num)
	{
	  $row = $db->fetch_row($resql);
	  print '<option value="'.$row[0].'">'.$row[1]."\n";
	  $i++;
	}
    }
  print '</select></td></tr>';
  

  print '<tr><td>Cout minute</td>';
  print '<td><input type="text" name="temporel" value="0"></td></tr>';
  
  print '<tr><td>Cout connexion</td>';
  print '<td><input type="text" name="fixe" value="0"></td></tr>';
  
  print '<tr><td>Numero gratuit</td>';
  print '<td><input type="checkbox" name="gratuit"></td></tr>';

  print '<tr><td colspan="2"><input type="submit" value="'.$langs->trans("Save").'"></td></tr>';
  print '</table></form>';
  
}


print '<br><table width="100%" class="border">';
print '<tr><td>Personnes pouvant modifier cette grille :</td></tr>';

$sql = "SELECT u.name, u.firstname";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_tarif_grille_rights as r";
$sql .= " , ".MAIN_DB_PREFIX."user  as u";
$sql .= " WHERE r.fk_grille = '".$_GET["id"]."'";
$sql .= " AND r.fk_user = u.rowid ";
$sql .= " AND r.pwrite = 1";

$resql = $db->query($sql);
if ($resql)
{
  $num = $db->num_rows();
  $i = 0;
  while ($i < $num )
    {
      $row = $db->fetch_row($resql);
      print '<tr><td>- '.$row[1] . ' '.$row[0].'</td></tr>';
      $i++;
    }
}
print '</table>';


print '<br><table width="100%" class="border">';
print '<tr><td>Export :</td></tr>';
print '<tr><td>- <a href="grille-export.php?id='.$_GET["id"].'">fichier format tableur</a></td></tr>';
print '</table>';

print '</td><td valign="top" width="70%">';



if ($sortorder == "") $sortorder="ASC";
if ($sortfield == "") $sortfield="t.libelle ASC, d.rowid ";

$offset = $conf->liste_limit * $page ;


$sql = "SELECT d.libelle as tarif_desc, d.type_tarif";
$sql .= " , t.libelle as tarif";
$sql .= " , m.temporel, m.fixe, t.rowid";
$sql .= " , u.login";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_tarif_grille as d";
$sql .= "," . MAIN_DB_PREFIX."telephonie_tarif_montant as m";
$sql .= "," . MAIN_DB_PREFIX."telephonie_tarif as t";
$sql .= "," . MAIN_DB_PREFIX."user as u";

$sqlc .= " WHERE d.rowid = m.fk_tarif_desc";
$sqlc .= " AND m.fk_tarif = t.rowid";
$sqlc .= " AND m.fk_user = u.rowid";

$sqlc .= " AND d.rowid = '".$_GET["id"]."'";


if ($_GET["search_libelle"])
{
  $sqlc .=" AND t.libelle LIKE '%".$_GET["search_libelle"]."%'";
}

if ($_GET["search_prefix"])
{
  $sqlc .=" AND tf.prefix LIKE '%".$_GET["search_prefix"]."%'";
}

if ($_GET["type"])
{
  $sqlc .= " AND d.type_tarif = '".$_GET["type"]."'";
}


$sql = $sql . $sqlc . " ORDER BY $sortfield $sortorder";

$result = $db->query($sql);
if ($result)
{
  $num = $db->num_rows();
  $i = 0;
  
  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre">';

  print_liste_field_titre("Grille","grille.php","d.libelle");
  print_liste_field_titre("Tarif","grille.php","t.libelle", "&type=".$_GET["type"]);
  print_liste_field_titre("Cout / min","grille.php","temporel", "&type=".$_GET["type"]);
  print "</td>";
  print "<td>Cout fixe</td>";
  print "<td>Type</td><td>User</td>";
  print "</tr>\n";

  $var=True;

  while ($i < $num)
    {
      $obj = $db->fetch_object($i);	
      $var=!$var;

      print "<tr $bc[$var]>";

      print "<td>".$obj->tarif_desc."</td>\n";
      print '<td><a href="tarif.php?id='.$obj->rowid.'">'.$obj->tarif."</a></td>\n";
      print "<td>".sprintf("%01.4f",$obj->temporel)."</td>\n";
      print "<td>".sprintf("%01.4f",$obj->fixe)."</td>\n";
      print "<td>".$obj->type_tarif."</td>\n";
      print "<td>".$obj->login."</td>\n";
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

print '</td></tr></table>';





$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
