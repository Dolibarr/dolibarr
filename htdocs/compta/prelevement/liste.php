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

llxHeader('','Prélèvements');
/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}

/*
 *
 *
 *
 */

$page = $_GET["page"];
$sortorder = (empty($_GET["sortorder"])) ? "DESC" : $_GET["sortorder"];
$sortfield = (empty($_GET["sortfield"])) ? "p.datec" : $_GET["sortfield"];
$offset = $conf->liste_limit * $page ;

$sql = "SELECT p.rowid, p.statut, p.ref, pl.amount,".$db->pdate("p.datec")." as datec";
$sql .= " , s.nom, s.code_client";
$sql .= " , pl.rowid as rowid_ligne, pl.statut as statut_ligne";
$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_bons as p";
$sql .= " , ".MAIN_DB_PREFIX."prelevement_lignes as pl";
$sql .= " , ".MAIN_DB_PREFIX."societe as s";
$sql .= " WHERE pl.fk_prelevement_bons = p.rowid";
$sql .= " AND s.idp = pl.fk_soc";

if ($_GET["search_bon"])
{
  $sql .= " AND p.ref LIKE '%".$_GET["search_bon"]."%'";
}

if ($_GET["search_code"])
{
  $sql .= " AND s.code_client LIKE '%".$_GET["search_code"]."%'";
}

if ($_GET["search_societe"])
{
  $sel =urldecode($_GET["search_societe"]);
  $sql .= " AND s.nom LIKE '%".$sel."%'";
}

$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit+1, $offset);

$result = $db->query($sql);
if ($result)
{
  $num = $db->num_rows();
  $i = 0;
  
  $urladd = "&amp;statut=".$_GET["statut"];
  $urladd .= "&amp;search_bon=".$_GET["search_bon"];

  print_barre_liste("Lignes de prélèvements", $page, "liste.php", $urladd, $sortfield, $sortorder, '', $num);
  print"\n<!-- debut table -->\n";
  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre"><td>Ligne</td>';

  print_liste_field_titre("Bon","liste.php","p.ref");

  print_liste_field_titre("Société","liste.php","s.nom");
  print_liste_field_titre("Date","liste.php","p.datec","","",'align="center"');

  print '<td align="right">Montant</td>';
  print_liste_field_titre("Code client","liste.php","s.code_client",'','','align="center"');

  print '</tr><tr class="liste_titre">';
  print '<form action="liste.php" method="GET"><td>&nbsp;</td>';
  print '<td><input type="text" name="search_bon" value="'. $_GET["search_bon"].'" size="8"></td>'; 
  print '<td><input type="text" name="search_societe" value="'. $_GET["search_societe"].'" size="12"></td>'; 
  print '<td><input type="submit" class="button" value="'.$langs->trans("Search").'"></td>';
  print '<td>&nbsp;</td>';
  print '<td align="center"><input type="text" name="search_code" value="'. $_GET["search_code"].'" size="8"></td>'; 

  print '</form>';
  print '</tr>';

  $var=True;

  while ($i < min($num,$conf->liste_limit))
    {
      $obj = $db->fetch_object($i);	
      $var=!$var;

      print "<tr $bc[$var]><td>";

      print '<img border="0" src="./statut'.$obj->statut_ligne.'.png"></a>&nbsp;';
      print '<a href="'.DOL_URL_ROOT.'/compta/prelevement/ligne.php?id='.$obj->rowid_ligne.'">';
      print substr('000000'.$obj->rowid_ligne, -6);
      print '</a></td>';

      print '<td><img border="0" src="./statut'.$obj->statut.'.png"></a>&nbsp;';

      print '<a href="fiche.php?id='.$obj->rowid.'">'.$obj->ref."</a></td>\n";
      print '<td><a href="fiche.php?id='.$obj->rowid.'">'.stripslashes($obj->nom)."</a></td>\n";
      print '<td align="center">'.strftime("%d/%m/%Y",$obj->datec)."</td>\n";

      print '<td align="right">'.price($obj->amount)." euros</td>\n";
      print '<td align="center"><a href="fiche.php?id='.$obj->rowid.'">'.$obj->code_client."</a></td>\n";
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
