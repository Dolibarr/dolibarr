<?PHP
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 *
 * $Id$
 * $Source$
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
 */
require("./pre.inc.php3");
require("../contact.class.php3");
require("../lib/webcal.class.php3");
require("../cactioncomm.class.php3");
require("../actioncomm.class.php3");

llxHeader();

$db = new Db();

if ($sortorder == "") {
  $sortorder="ASC";
}
if ($sortfield == "") {
  $sortfield="nom";
}

if ($action=='add_action') {
  /*
   * Vient de actioncomm.php3
   *
   */
  $actioncomm = new ActionComm($db);
  $actioncomm->date = $date;
  $actioncomm->type = $actionid;
  $actioncomm->contact = $contactid;

  $actioncomm->societe = $socid;
  $actioncomm->note = $note;

  $actioncomm->add($user);


  $societe = new Societe($db);
  $societe->fetch($socid);


  $todo = new TodoComm($db);
  $todo->date = mktime(12,0,0,$remonth, $reday, $reyear);

  $todo->libelle = $todo_label;

  $todo->societe = $societe->id;
  $todo->contact = $contactid;

  $todo->note = $todo_note;

  $todo->add($user);

  $webcal = new Webcal();
  $webcal->add($user, $todo->date, $societe->nom, $todo->libelle);
}


if ($action == 'attribute_prefix') {
  $societe = new Societe($db, $socid);
  $societe->attribute_prefix($db, $socid);
}

if ($action == 'recontact') {
  $dr = mktime(0, 0, 0, $remonth, $reday, $reyear);
  $sql = "INSERT INTO llx_soc_recontact (fk_soc, datere, author) VALUES ($socid, $dr,'". $GLOBALS["REMOTE_USER"]."')";
  $result = $db->query($sql);
}

if ($action == 'note') {
  $sql = "UPDATE societe SET note='$note' WHERE idp=$socid";
  $result = $db->query($sql);
}

if ($action == 'stcomm') {
  if ($stcommid <> 'null' && $stcommid <> $oldstcomm) {
    $sql = "INSERT INTO socstatutlog (datel, fk_soc, fk_statut, author) ";
    $sql .= " VALUES ('$dateaction',$socid,$stcommid,'" . $GLOBALS["REMOTE_USER"] . "')";
    $result = @$db->query($sql);

    if ($result) {
      $sql = "UPDATE societe SET fk_stcomm=$stcommid WHERE idp=$socid";
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
    $sql = "SELECT s.idp FROM societe as s ";
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
  print_barre_liste("Liste des clients", $page, $PHP_SELF);

  $sql = "SELECT s.idp, s.nom, s.ville, ".$db->pdate("s.datec")." as datec, ".$db->pdate("s.datea")." as datea,  st.libelle as stcomm, s.prefix_comm FROM societe as s, c_stcomm as st WHERE s.fk_stcomm = st.id AND s.client=1";

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

  $sql .= " ORDER BY $sortfield $sortorder " . $db->plimit( $limit, $offset);

  $result = $db->query($sql);
  if ($result) {
    $num = $db->num_rows();
    $i = 0;

    if ($sortorder == "DESC") {
      $sortorder="ASC";
    } else {
      $sortorder="DESC";
    }
    print "<TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
    print '<TR class="liste_titre">';
    print "<TD valign=\"center\">";
    print_liste_field_titre("Société",$PHP_SELF,"s.nom");
    print "</td><TD>Ville</TD>";
    print "<TD>email</TD>";
    print "<TD align=\"center\">Statut</TD><td>&nbsp;</td><td colspan=\"2\">&nbsp;</td>";
    print "</TR>\n";
    $var=True;
    while ($i < $num) {
      $obj = $db->fetch_object( $i);
      
      $var=!$var;

      print "<TR $bc[$var]>";
      print "<TD><a href=\"fiche.php3?socid=$obj->idp\">$obj->nom</A></td>\n";
      print "<TD>".$obj->ville."&nbsp;</TD>\n";
      print "<TD>&nbsp;</TD>\n";
      print "<TD align=\"center\">$obj->stcomm</TD>\n";
      print "<TD align=\"center\">$obj->prefix_comm&nbsp;</TD>\n";
      print "<TD align=\"center\"><a href=\"addpropal.php3?socidp=$obj->idp&action=create\">[Propal]</A></td>\n";
      if ($conf->fichinter->enabled) {
	print "<TD align=\"center\"><a href=\"../fichinter/fiche.php3?socidp=$obj->idp&action=create\">[Fiche Inter]</A></td>\n";
      } else {
	print "<TD>&nbsp;</TD>\n";
      }
      print "</TR>\n";
      $i++;
    }
    print "</TABLE>";
    $db->free();
  } else {
    print $db->error() . ' ' . $sql;
  }

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
