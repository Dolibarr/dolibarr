<?PHP
/* Copyright (C) 2004-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

/**	        \file       htdocs/telephonie/ligne/listecommande.php
	        \ingroup    telephonie
	        \brief      Liste commandes
	        \version    $Revision$
*/

require("./pre.inc.php");


$page = $_GET["page"];
$sortorder = $_GET["sortorder"];
$sortfield = $_GET["sortfield"];

llxHeader('','Telephonie - Ligne - Liste');
/*
 * S�curit� acc�s client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}

if ($sortorder == "") {
  $sortorder="ASC";
}
if ($sortfield == "") {
  $sortfield="l.date_commande_last";
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

$sql = "SELECT s.rowid as socid, l.date_commande_last, s.nom, l.ligne, f.nom as fournisseur, l.statut, l.rowid, l.remise";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql .= " ,".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
$sql .= " , ".MAIN_DB_PREFIX."telephonie_fournisseur as f";
$sql .= " WHERE l.statut = 2 AND l.fk_soc = s.rowid AND l.fk_fournisseur = f.rowid";

if ($_GET["search_ligne"])
{
  $sel =urldecode($_GET["search_ligne"]);
  $sel = ereg_replace("\.","",$sel);
  $sel = ereg_replace(" ","",$sel);
  $sql .= " AND l.ligne LIKE '%".$sel."%'";
}

if ($_GET["search_client"])
{
  $sel =urldecode($_GET["search_client"]);
  $sql .= " AND s.nom LIKE '%".$sel."%'";
}

$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit+1, $offset);

$resql = $db->query($sql);
if ($resql)
{
  $num = $db->num_rows($resql);
  $i = 0;
  
  print_barre_liste("Lignes", $page, "listecommande.php", "", $sortfield, $sortorder, '', $num);

  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre">';
  print_liste_field_titre("Ligne","listecommande.php","l.ligne");
  print_liste_field_titre("Client (Agence/Filiale)","listecommande.php","s.nom");

  print '<td align="center">Date</td>';
  print '<td>Fournisseur</td>';
  print "</tr>\n";

  print '<tr class="liste_titre">';
  print '<form action="listecommande.php" method="GET">';
  print '<td><input type="text" name="search_ligne" value="'. $_GET["search_ligne"].'" size="12"></td>';  print '<td><input type="text" name="search_client" value="'. $_GET["search_client"].'" size="20"></td>';

  print '<td><input type="submit" class="button" value="'.$langs->trans("Search").'"></td>';

  print '<td>&nbsp;</td>';
  print '</form>';
  print '</tr>';

  $var=True;

  while ($i < min($num,$conf->liste_limit))
    {
      $obj = $db->fetch_object($resql);	
      $var=!$var;

      print "<tr $bc[$var]><td>";

      print '<img src="./graph'.$obj->statut.'.png">&nbsp;';
      
      print '<a href="'.DOL_URL_ROOT.'/telephonie/ligne/fiche.php?id='.$obj->rowid.'">';
      print img_file();
      
      print '</a>&nbsp;';

      print '<a href="fiche.php?id='.$obj->rowid.'">'.dolibarr_print_phone($obj->ligne,0,0,true)."</a></td>\n";
      print '<td><a href="'.DOL_URL_ROOT.'/soc.php?socid='.$obj->socid.'">'.$obj->nom.'</a></td>';
      print '<td align="center">'.$obj->date_commande_last."</td>\n";

      print "<td>".$obj->fournisseur."</td>\n";
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
