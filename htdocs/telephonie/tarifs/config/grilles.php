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
  
if ($_POST["action"] == 'add' && $user->rights->telephonie->tarif->permission)
{
  require_once DOL_DOCUMENT_ROOT."/telephonie/telephonie.tarif.grille.class.php";

  $obgrille = new TelephonieTarifGrille($db);
      
  $obgrille->CreateGrille($user, $_POST["nom"], $_POST["type"], $_POST["copy"]);

  Header("Location: grilles.php");
}

if ($_POST["action"] == 'remove' && $user->rights->telephonie->tarif->permission)
{
  require_once DOL_DOCUMENT_ROOT."/telephonie/telephonie.tarif.grille.class.php";

  $obgrille = new TelephonieTarifGrille($db);
      
  $obgrille->RemoveGrille($user, $_POST["id"], $_POST["replace"]);

  Header("Location: grilles.php");
}

llxHeader("","Configuration des grilles tarifs");

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
print '<tr><td valign="top" width="50%">';

$sql = "SELECT d.libelle as tarif_desc, d.rowid, d.type_tarif";

$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_tarif_grille as d";
$sql .= ","    . MAIN_DB_PREFIX."telephonie_tarif_grille_rights as r";
$sql .= " WHERE d.rowid = r.fk_grille";
$sql .= " AND r.fk_user =".$user->id;
$sql .= " AND r.pread = 1";
$sql .= " ORDER BY d.libelle";

$result = $db->query($sql);
if ($result)
{
  $num = $db->num_rows();
  $i = 0;
  
  while ($i < $num)
    {
      $obj = $db->fetch_object($i);	
      $grilles[$obj->rowid][0] = $obj->rowid;
      $grilles[$obj->rowid][1] = stripslashes($obj->tarif_desc);
      $grilles[$obj->rowid][2] = $obj->type_tarif;
      $i++;
    }

  $db->free();
}
else 
{
  print $db->error() . ' ' . $sql;
}


print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
print '<tr class="liste_titre">';
print "<td>Grille</td><td>Type</td><td>&nbsp;</td>";
print "</tr>\n";

$var=True;

foreach($grilles as $grille)
{  
  $var=!$var;
  
  print "<tr $bc[$var]>";
  print '<td><a href="grille.php?id='.$grille[0].'">'.$grille[1]."</a></td>\n";
  print '<td>'.$grille[2]."</td>\n";
  print '<td align="right"><a href="grille.php?id='.$grille[0].'&amp;action=delete">'.$langs->trans("Delete")."</a></td>\n";
  print "</tr>\n";
}
print "</table>";


if ($_GET["action"] == 'delete')
{
  print '<br><br><form action="grilles.php" method="post">';
  print '<input type="hidden" name="token_level_1" value="'.$_SESSION['newtoken'].'">';
  print '<input type="hidden" name="action" value="remove">';
  print '<input type="hidden" name="id" value="'.$_GET['id'].'">';
  print '<table class="border" width="100%">';
  print '<tr><td colspan="2">Supprimer la grille : '.$grilles[$_GET['id']][1].' ?</td></tr>';
  // Nom
  print "<tr><td>Utiliser la grille</td>".'<td><select name="replace">';
  foreach($grilles as $grille)
    {
      if ($grille[0] <> $_GET['id'])
	print '<option value="'.$grille[0].'">'.$grille[1]."</option>\n";
    }
  print '</select> en remplacement de la grille supprimee.</td></tr>';
  
  print "<tr>".'<td align="center" colspan="2"><input class="button" value="'.$langs->trans("Delete").'" type="submit"></td></tr>';
  
  print '</table></form>';
}
else
{
  print '<br><br><form action="grilles.php" method="post">';
  print '<input type="hidden" name="token_level_1" value="'.$_SESSION['newtoken'].'">';
  print '<input type="hidden" name="action" value="add">';
  
  print '<table class="border" width="100%">';
  
  // Nom
  print "<tr>".'<td valign="top">'.$langs->trans("Lastname").'*</td>';
  print '<td>';
  
  print '<input size="30" type="text" name="nom" value="">';
  
  print '</td></tr>';
  print "<tr><td>Type de grille</td>".'<td><select name="type"><option value="vente">vente<option value="achat">achat</select></td></tr>';
  
  print "<tr><td>Copier la grille</td>".'<td><select name="copy">';
  print '<option value="0">Grille vide</option>';
  foreach($grilles as $grille)
    {
      print '<option value="'.$grille[0].'">'.$grille[1]."</option>\n";
    }
  print '</select></td></tr>';
  
  print "<tr>".'<td align="center" colspan="2"><input class="button" value="'.$langs->trans("Create").'" type="submit"></td></tr>';
  
  print '</table></form>';
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
