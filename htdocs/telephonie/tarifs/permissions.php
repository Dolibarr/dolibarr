<?PHP
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

if (!$user->rights->telephonie->tarif->permission)
  accessforbidden();

if ($_POST["action"] == 'perms')
{

  if ($_POST["perms"] == 0)
    {
      $sql = "DELETE FROM ".MAIN_DB_PREFIX."telephonie_tarif_grille_rights";
      $sql .= " WHERE fk_user = '".$_POST["user"]."'";
      $sql .= " AND fk_grille = '".$_POST["grille"]."';";
      $db->query($sql);
    }

  if ($_POST["perms"] == 1)
    {
      $sql = "UPDATE ".MAIN_DB_PREFIX."telephonie_tarif_grille_rights";
      $sql .= " SET pread= 1, pwrite = 0, fk_user_creat ='".$user->id."' WHERE fk_user = '".$_POST["user"]."'";
      $sql .= " AND fk_grille = '".$_POST["grille"]."';";
      if ( $db->query($sql) )
	{
	  if ($db->affected_rows($resql) == 0)
	    {
	      $sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_tarif_grille_rights";
	      $sql .= " (pread,pwrite,  fk_user, fk_grille, fk_user_creat) VALUES ";
	      $sql .= " (1,0,'".$_POST["user"]."','".$_POST["grille"]."','".$user->id."');";
	      if ( $db->query($sql) )
		{

		}
	    }
	}
    }

  if ($_POST["perms"] == 2)
    {
      $sql = "UPDATE ".MAIN_DB_PREFIX."telephonie_tarif_grille_rights";
      $sql .= " SET pread= 1, pwrite = 1, fk_user_creat ='".$user->id."' WHERE fk_user = '".$_POST["user"]."'";
      $sql .= " AND fk_grille = '".$_POST["grille"]."';";
      if ( $db->query($sql) )
	{

	  if ($db->affected_rows($resql) == 0)
	    {

	      $sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_tarif_grille_rights";
	      $sql .= " (pread,pwrite, fk_user, fk_grille, fk_user_creat) VALUES ";
	      $sql .= " (1,1,'".$_POST["user"]."','".$_POST["grille"]."','".$user->id."');";
	      if ( $db->query($sql) )
		{

		}
	      else
		{
		  print $sql;
		}
	    }

	}
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
 *
 *
 */
$grilles = array();
$sql = "SELECT d.rowid, d.libelle FROM ".MAIN_DB_PREFIX."telephonie_tarif_grille as d";
$resql = $db->query($sql);
if ($resql)
{
  while ($row = $db->fetch_row($resql))
    {
      $grilles[$row[0]] = $row[1];
    }
  $db->free($resql); 
}

$users = array();

$sql = "SELECT u.rowid, u.firstname, u.name FROM ".MAIN_DB_PREFIX."user as u";
$resql = $db->query($sql);
if ($resql)
{
  while ($row = $db->fetch_row($resql))
    {
      $users[$row[0]] = $row[1] . ' '.$row[2];
    }
  $db->free($resql);
}

$form = new Form($db);
print '<form action="permissions.php" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="perms">';
print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
print '<tr class="liste_titre"><td>Grille</td>';
print '<td>Utilisateur</td><td>Permissions</td><td>&nbsp;</td>';
print "</tr>\n";

print '<tr class="liste_titre">';
print '<td>';
print $form->select_array("grille",$grilles);
print '</td><td>';
print $form->select_array("user",$users);
print '</td><td><select name="perms">';
print '<option value="0">Aucun</option>';
print '<option value="1">Lecture</option>';
print '<option value="2">Lecture/Ecriture</option>';
print '<td><input type="submit"></td>';
print "</tr>\n";
print "</form>\n";
print "</table>\n";


/*
 *
 *
 *
 *
 */

$page = $_GET["page"];
$sortorder = $_GET["sortorder"];
$sortfield = $_GET["sortfield"];

if ($sortorder == "") $sortorder="ASC";
if ($sortfield == "") $sortfield="d.libelle ASC, d.rowid ";

$offset = $conf->liste_limit * $page ;

/*
 * Mode Liste
 *
 *
 *
 */

$sql = "SELECT d.rowid as grille, d.libelle as tarif_desc, d.type_tarif";
$sql .= " , u.login, u.name, u.firstname";
$sql .= " , r.pread, r.pwrite";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_tarif_grille as d";
$sql .= "," . MAIN_DB_PREFIX."telephonie_tarif_grille_rights as r";
$sql .= "," . MAIN_DB_PREFIX."user as u";

$sqlc .= " WHERE d.rowid = r.fk_grille";
$sqlc .= " AND r.fk_user = u.rowid";

if ($_GET["search_user"])
{
  $sqlc .=" AND t.libelle LIKE '%".$_GET["search_libelle"]."%'";
}

if ($_GET["search_grille"])
{
  $sqlc .=" AND d.libelle LIKE '%".$_GET["search_grille"]."%'";
}


$sql = $sql . $sqlc . " ORDER BY u.name ASC " . $db->plimit($conf->liste_limit+1, $offset);

$result = $db->query($sql);
if ($result)
{
  $num = $db->num_rows();
  $i = 0;
  
  print_barre_liste("Tarifs", $page, "index.php", "&type=".$_GET["type"], $sortfield, $sortorder, '', $num);

  print '<form action="permissions.php" method="GET">';

  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre">';

  print_liste_field_titre("Tarif","index.php","d.libelle");

  print '<td>Utilisateur</td><td align="center">Lecture</td><td align="center">Ecriture</td>';
  print "</tr>\n";

  print '<tr class="liste_titre">';
  print '<input type="hidden" name="type" value="'.$_GET["type"].'">';
  print '<td><input type="text" name="search_grille" size="10" value="'.$_GET["search_grille"].'"></td>';
  print '<td><input type="text" name="search_user" size="20" value="'.$_GET["search_user"].'"></td>';
  print '<td>&nbsp;</td>';
  print '<td><input type="submit"></td>';
  print '</form>';
  print '</tr>';

  $var=True;

  while ($i < min($num,$conf->liste_limit))
    {
      $obj = $db->fetch_object($i);	
      $var=!$var;

      print "<tr $bc[$var]>";

      print '<td><a href="grille.php?id='.$obj->grille.'">';
      print $obj->tarif_desc."</td>\n";
      print '<td>'.$obj->firstname." ".$obj->name."</td>\n";

      if ($obj->pread == 1)
	{
	  print '<td align="center">'.img_tick().'</td>';
	}
      else
	{
	  print '<td align="center">&nbsp;</td>';
	}

      if ($obj->pwrite == 1)
	{
	  print '<td align="center">'.img_tick().'</td>';
	}
      else
	{
	  print '<td align="center">&nbsp;</td>';
	}
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
