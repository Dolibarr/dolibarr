<?PHP
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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
require_once DOL_DOCUMENT_ROOT."/contact.class.php";


$page = $_GET["page"];
$sortorder = $_GET["sortorder"];
$sortfield = $_GET["sortfield"];


llxHeader();

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}

if ($sortorder == "") {
  $sortorder="DESC";
}
if ($sortfield == "") {
  $sortfield="cf.date_creation";
}


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

$sql = "SELECT s.idp, s.nom, s.ville, ".$db->pdate("s.datea")." as datea";
$sql .= " ,cf.rowid,cf.ref";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s ";
$sql .= " , ".MAIN_DB_PREFIX."commande_fournisseur as cf";
$sql .= " WHERE cf.fk_soc = s.idp ";

if ($socidp) {
  $sql .= " AND s.idp=$socidp";
}

$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit+1, $offset);

$result = $db->query($sql);
if ($result)
{
  $num = $db->num_rows();
  $i = 0;
  
  print_barre_liste("Commandes fournisseurs", $page, "index.php", "", $sortfield, $sortorder, '', $num);
  if ($sortorder == "DESC")
    {
      $sortorder="ASC";
    }
  else
    {
      $sortorder="DESC";
    }
  print '<table class="liste">';
  print '<tr class="liste_titre"><td>Réf</td>';
  print_liste_field_titre("Société","index.php","s.nom");

  print "<td>Ville</td>";
  print "</tr>\n";
  $var=True;

  while ($i < min($num,$conf->liste_limit))
    {
      $obj = $db->fetch_object($i);	
      $var=!$var;

      print "<tr $bc[$var]>";
      print '<td><a href="'.DOL_URL_ROOT.'/fourn/commande/fiche.php?id='.$obj->rowid.'">'.img_file().'</a>';
      print '&nbsp;<a href="'.DOL_URL_ROOT.'/fourn/commande/fiche.php?id='.$obj->rowid.'">'.$obj->ref.'</a></td>'."\n";
      print '<td><a href="'.DOL_URL_ROOT.'/fourn/fiche.php?socid='.$obj->idp.'">'.img_file().'</a>';
      print '&nbsp;<a href="'.DOL_URL_ROOT.'/fourn/fiche.php?socid='.$obj->idp.'">'.$obj->nom.'</a></td>'."\n";
      print "<td>".$obj->ville."</td>\n";       
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
