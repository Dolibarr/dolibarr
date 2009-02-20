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

/**	        \file       htdocs/telephonie/ligne/numdata.php
	        \ingroup    telephonie
	        \brief      Num data
	        \version    $Revision$
*/

require("./pre.inc.php");


$page = $_GET["page"];
$sortorder = $_GET["sortorder"];
$sortfield = $_GET["sortfield"];

if ($_POST["action"] == 'addnum')
{

  if (strlen(trim($_POST["numero"])) > 0)
    {
      $sql = "REPLACE INTO ".MAIN_DB_PREFIX."telephonie_numdata";
      $sql .= " (fk_groupe, numero,fk_user) VALUES ";
      $sql .= " (".$_POST["groupeid"].",'".$_POST["numero"]."',".$user->id.")";
      
      if ( $db->query($sql) )
	{
	  //Header("Location: numdata.php?id=".$ligne->id);
	  Header("Location: numdata.php?id=".$ligne->id);
	}
      else
	{
	  print $db->error();
	}
    }
}

if ($_GET["action"] == 'delete')
{

  if (strlen(trim($_GET["id"])) > 0)
    {
      $sql = "DELETE FROM ".MAIN_DB_PREFIX."telephonie_numdata";
      $sql .= " WHERE rowid = ".$_GET["id"];
      
      if ( $db->query($sql) )
	{
	  Header("Location: numdata.php");
	}
      else
	{
	  print $db->error();
	}
    }
}

llxHeader('','Telephonie - Ligne - Liste');
/*
 * S�curit� acc�s client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}


/*
 * Recherche
 *
 *
 */

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


print_barre_liste("Num�ros data", $page, "numdata.php", $urladd, $sortfield, $sortorder, '', $num);

print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
print '<tr class="liste_titre">';
print_liste_field_titre("Num�ro","numdata.php","l.ligne");

print_liste_field_titre("Client","numdata.php","s.nom");
print '<td>&nbsp;</td>';

print "</tr>\n";

print '<tr class="liste_titre">';
print '<form action="numdata.php" method="GET">';
print '<td><input type="text" name="search_ligne" value="'. $_GET["search_ligne"].'" size="12"></td>';  print '<td><input type="text" name="search_client" value="'. $_GET["search_client"].'" size="20"></td>';

print '<td><input type="submit" class="button" value="'.$langs->trans("Search").'"></td>';

print '</form>';
print '</tr>';


print '<tr class="liste_titre">';
print '<form action="numdata.php" method="POST">';
print '<input type="hidden" name="action" value="addnum"></td>';  
print '<td><input type="text" name="numero" size="12" maxlength="12"></td>';  

print '<td><select name="groupeid">';
$sql = "SELECT distinct g.rowid , g.nom";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_groupeligne as g";

if ( $db->query($sql) )
{  
  $num = $db->num_rows();
  $i = 0;      
  while ($i < $num)
    {
      $row = $db->fetch_row($i);
      print '<option value="'.$row[0].'">'.$row[1];
      $i++;
    }
}
print '</select></td>';
print '<td><input type="submit" value="Ajouter"></td>';

print '</form>';
print '</tr>';


$sql = "SELECT g.nom, n.numero, n.rowid";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_groupeligne as g";
$sql .= ",".MAIN_DB_PREFIX."telephonie_numdata as n";

$sql .= " WHERE n.fk_groupe = g.rowid";

if ($sortorder == "") {
  $sortorder="ASC";
}
if ($sortfield == "") {
  $sortfield="g.nom";
}


$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit+1, $offset);

$result = $db->query($sql);
if ($result)
{
  $num = $db->num_rows();
  $i = 0;
  
  $var=True;

  $ligne = new LigneTel($db);

  while ($i < min($num,$conf->liste_limit))
    {
      $obj = $db->fetch_object($i);	
      $var=!$var;

      print "<tr $bc[$var]><td>";

      print dol_print_phone($obj->numero,0,0,true)."</td>\n";
      print '<td><a href="'.DOL_URL_ROOT.'/soc.php?socid='.$obj->socid.'">'.$obj->nom.'</a></td>';

      print '<td align="center"><a href="'.DOL_URL_ROOT.'/telephonie/ligne/numdata.php?action=delete&amp;id='.$obj->rowid.'">';
      print img_delete();
      print '</a></td>';

      print "</tr>\n";
      $i++;
    }

  $db->free();
}
else 
{
  print $db->error() . ' ' . $sql;
}

  print "</table>";

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
