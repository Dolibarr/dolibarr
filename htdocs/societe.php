<?PHP
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 */
require("./pre.inc.php3");
require("./contact.class.php3");
require("./lib/webcal.class.php3");
require("./cactioncomm.class.php3");
require("./actioncomm.class.php3");

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}


llxHeader();

$db = new Db();

if ($sortorder == "") {
  $sortorder="ASC";
}
if ($sortfield == "") {
  $sortfield="nom";
}

if ($page == -1) { $page = 0 ; }

$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;


/*
 * Recherche
 *
 *
 */
if ($mode == 'search') {
  if ($mode-search == 'soc') {
    $sql = "SELECT s.idp FROM llx_societe as s ";
    $sql .= " WHERE lower(s.nom) like '%".strtolower($socname)."%'";
  }
      
  if ( $db->query($sql) ) {
    if ( $db->num_rows() == 1) {
      $obj = $db->fetch_object(0);
      $socid = $obj->idp;
    }
    $db->free();
  }
}


/*
 * Mode Liste
 *
 *
 *
 */
print_barre_liste("Liste des societes", $page, $PHP_SELF);

$sql = "SELECT s.idp, s.nom, s.ville, ".$db->pdate("s.datec")." as datec, ".$db->pdate("s.datea")." as datea,  st.libelle as stcomm, s.prefix_comm, s.client, s.fournisseur";
$sql .= " FROM llx_societe as s, c_stcomm as st WHERE s.fk_stcomm = st.id";

if ($user->societe_id > 0) {
  $sql .= " AND s.idp = " . $user->societe_id;
}


if (strlen($stcomm)) {
  $sql .= " AND s.fk_stcomm=$stcomm";
}

if (strlen($begin)) {
  $sql .= " AND upper(s.nom) like '$begin%'";
}

if ($socname) {
  $sql .= " AND lower(s.nom) like '%".strtolower($socname)."%'";
  $sortfield = "lower(s.nom)";
  $sortorder = "ASC";
}

$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit, $offset);

$result = $db->query($sql);
if ($result) {
  $num = $db->num_rows();
  $i = 0;
    
  if ($sortorder == "DESC") 
    {
      $sortorder="ASC";
    } 
  else
    {
      $sortorder="DESC";
    }
  print "<TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
  print '<TR class="liste_titre">';
  print "<TD valign=\"center\">";
  print_liste_field_titre("Société",$PHP_SELF,"s.nom");
  print "</td><TD>";
  print_liste_field_titre("Ville",$PHP_SELF,"s.ville");
  print "</td>";
  print '</td><td colspan="2">&nbsp;</td>';
  print "</TR>\n";
  $var=True;
  while ($i < $num) {
    $obj = $db->fetch_object( $i);
    
    $var=!$var;
    
    print "<TR $bc[$var]>";
    print "<TD><a href=\"soc.php3?socid=$obj->idp\">$obj->nom</A></td>\n";
    print "<TD>".$obj->ville."&nbsp;</TD>\n";
    print '<TD align="center">';
    if ($obj->client)
      {
	print "<a href=\"comm/fiche.php3?socid=$obj->idp\">client</A></td>\n";
      }
    else
      {
	print "&nbsp;";
      }
    print "</td><TD align=\"center\">";
    if ($obj->fournisseur)
      {
	print "<a href=\"/fourn/fiche.php3?socid=$obj->idp\">fournisseur</A></td>\n";
      }
    else
      {
	print "&nbsp;";
      }
    
    print "</TR>\n";
    $i++;
  }
  print "</TABLE>";
  $db->free();
}
else
{
  print $db->error() . ' ' . $sql;
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
