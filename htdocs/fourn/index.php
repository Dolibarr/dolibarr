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
 *
 */
require("./pre.inc.php3");
require("../contact.class.php3");

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}

llxHeader();


if ($action == 'note')
{
  $sql = "UPDATE llx_societe SET note='$note' WHERE idp=$socid";
  $result = $db->query($sql);
}

if ($action == 'stcomm') {
  if ($stcommid <> 'null' && $stcommid <> $oldstcomm) {
    $sql = "INSERT INTO socstatutlog (datel, fk_soc, fk_statut, author) ";
    $sql .= " VALUES ('$dateaction',$socid,$stcommid,'" . $GLOBALS["REMOTE_USER"] . "')";
    $result = @$db->query($sql);

    if ($result) {
      $sql = "UPDATE llx_societe SET fk_stcomm=$stcommid WHERE idp=$socid";
      $result = $db->query($sql);
    } else {
      $errmesg = "ERREUR DE DATE !";
    }
  }

  if ($actioncommid) {
    $sql = "INSERT INTO actioncomm (datea, fk_action, fk_soc, fk_user_author) VALUES ('$dateaction',$actioncommid,$socid,'" . $user->id . "')";
    $result = @$db->query($sql);

    if (!$result) {
      $errmesg = "ERREUR DE DATE !";
    }
  }
}
if ($page == -1) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;
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

if ($sortorder == "")
{
  $sortorder="ASC";
}
if ($sortfield == "")
{
  $sortfield="nom";
}


print_barre_liste("Liste des fournisseurs",$page, $PHP_SELF);

$sql = "SELECT s.idp, s.nom, s.ville,".$db->pdate("s.datec")." as datec, ".$db->pdate("s.datea")." as datea,  st.libelle as stcomm, s.prefix_comm FROM llx_societe as s, c_stcomm as st WHERE s.fk_stcomm = st.id AND s.fournisseur=1";

if (strlen($stcomm)) {
  $sql .= " AND s.fk_stcomm=$stcomm";
}

if ($socidp) {
  $sql .= " AND s.idp=$socidp";
}

if (strlen($begin)) {
  $sql .= " AND upper(s.nom) like '$begin%'";
}

if ($socname) {
  $sql .= " AND lower(s.nom) like '%".strtolower($socname)."%'";
  $sortfield = "lower(s.nom)";
  $sortorder = "ASC";
}

$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit( $limit, $offset);

$result = $db->query($sql);
if ($result)
{
  $num = $db->num_rows();
  $i = 0;
  
  if ($sortorder == "DESC") {
    $sortorder="ASC";
  } else {
    $sortorder="DESC";
  }
  print "<p><TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
  print '<TR class="liste_titre"><td>';
  print_liste_field_titre("Société",$PHP_SELF,"s.nom");
  print "</td><TD>Ville</TD>";
  print "<td colspan=\"2\">&nbsp;</td>";
  print "</TR>\n";
  $var=True;
  while ($i < $num)
    {
      $obj = $db->fetch_object( $i);
	
      $var=!$var;

      print "<TR $bc[$var]>";
      print "<TD><a href=\"fiche.php3?socid=$obj->idp\">$obj->nom</A></td>\n";
      print "<TD>".$obj->ville."</TD>\n";
	
      print "<TD align=\"center\">$obj->prefix_comm&nbsp;</TD>\n";
      
      print '<td><a href="facture/fiche.php3?action=create&socid='.$obj->idp.'"><img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/filenew.png" border="0" alt="Nouvelle facture"></a></td>';

      print "</tr>\n";
      $i++;
    }
  print "</table>";
  $db->free();
}
else 
{
  print $db->error();
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
