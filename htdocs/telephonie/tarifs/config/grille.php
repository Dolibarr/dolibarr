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

$page = $_GET["page"];
$sortorder = $_GET["sortorder"];
$sortfield = $_GET["sortfield"];

/*
 *
 *
 */
if ($_POST["action"] == 'add')
{

  require_once DOL_DOCUMENT_ROOT."/telephonie/telephonie.tarif.grille.class.php";

  $obgrille = new TelephonieTarifGrille($db);
      
  $obgrille->CreateGrille($user, $_POST["nom"], $_POST["type"]);

  Header("Location: grille.php");

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


print '<form action="grille.php" method="post">';
print '<input type="hidden" name="action" value="add">';

print '<table class="border" width="100%">';

// Nom
print "<tr>".'<td valign="top">'.$langs->trans("Lastname").'*</td>';
print '<td>';

print '<input size="30" type="text" name="nom" value="">';

print '</td></tr>';
print "<tr><td>Type de grille</td>".'<td><select name="type"><option value="vente">vente<option value="achat">achat</select></td></tr>';
print "<tr>".'<td align="center" colspan="2"><input class="button" value="'.$langs->trans("Create").'" type="submit"></td></tr>';

print '</table></form>';


print '<br> <table width="100%" class="noborder">';
print '<tr><td valign="top" width="50%">';

$sql = "SELECT d.libelle as tarif_desc, d.rowid";

$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_tarif_grille as d";
$sql .= ","    . MAIN_DB_PREFIX."telephonie_tarif_grille_rights as r";
$sql .= " WHERE d.rowid = r.fk_grille";
$sql .= " AND r.fk_user =".$user->id;
$sql .= " AND r.pread = 1";


$sql .= " ORDER BY d.rowid";

$result = $db->query($sql);
if ($result)
{
  $num = $db->num_rows();
  $i = 0;
  
  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre">';
  print "<td>Grille</td>";
  print "</tr>\n";

  $var=True;

  while ($i < $num)
    {
      $obj = $db->fetch_object($i);	
      $var=!$var;

      print "<tr $bc[$var]>";
      print '<td><a href="grille.php?id='.$obj->rowid.'">'.$obj->tarif_desc."</a></td>\n";

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
