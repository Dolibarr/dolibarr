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
  $sortorder="DESC";
}
if ($sortfield == "") {
  $sortfield="ls.tms";
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

$sql = "SELECT s.rowid as socid, s.nom, l.ligne, f.nom as fournisseur, l.statut, l.rowid, l.remise";
$sql .= " , ".$db->pdate("ls.tms")."  as dc";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql .= " ,".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
$sql .= " , ".MAIN_DB_PREFIX."telephonie_societe_ligne_statut as ls";
$sql .= " , ".MAIN_DB_PREFIX."telephonie_fournisseur as f";
$sql .= " WHERE l.fk_soc = s.rowid AND l.fk_fournisseur = f.rowid";
$sql .= " AND ls.fk_ligne = l.rowid";
$sql .= " AND ls.statut = 2";

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

$result = $db->query($sql);
if ($result)
{
  $num = $db->num_rows();
  $i = 0;
  
  $urladd= "&amp;statut=".$_GET["statut"];

  print_barre_liste("Lignes", $page, "historique.php", $urladd, $sortfield, $sortorder, '', $num);
  print"\n<!-- debut table -->\n";
  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre">';

  print '<td>&nbsp;</td>';

  print_liste_field_titre("Ligne","historique.php","l.ligne");
  print_liste_field_titre("Client (Agence/Filiale)","historique.php","s.nom");

  print '<td align="center">Statut</td>';

  print '<td>Fournisseur</td>';

  print "</tr>\n";

  print '<tr class="liste_titre">';
  print '<form action="historique.php" method="GET">';
  print '<td>&nbsp;</td>';
  print '<td><input type="text" name="search_ligne" value="'. $_GET["search_ligne"].'" size="10"></td>';

  print '<td><input type="submit" value="Chercher"></td>';

  print '<td>&nbsp;</td>';
  print '<td>&nbsp;</td>';

  print '</form>';
  print '</tr>';


  $var=True;

  $ligne = new LigneTel($db);

  while ($i < min($num,$conf->liste_limit))
    {
      $obj = $db->fetch_object();	
      $var=!$var;

      print "<tr $bc[$var]>";
      print '<td>'.strftime("%d/%m/%Y %H:%M", $obj->dc).'</td>';
      print '<td><img src="./graph'.$obj->statut.'.png">&nbsp;';
      
      print '<a href="'.DOL_URL_ROOT.'/telephonie/ligne/fiche.php?id='.$obj->rowid.'">';
      print img_file();      
      print '</a>&nbsp;';

      print '<a href="'.DOL_URL_ROOT.'/telephonie/ligne/fiche.php?id='.$obj->rowid.'">'.dolibarr_print_phone($obj->ligne,0,0,true)."</a></td>\n";

      print '<td><a href="'.DOL_URL_ROOT.'/telephonie/client/fiche.php?id='.$obj->socid.'">'.$obj->nom.'</a></td>';


      print '<td align="center">'.$ligne->statuts[$obj->statut]."</td>\n";


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
