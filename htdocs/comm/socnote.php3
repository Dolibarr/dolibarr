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

//require("../../www/lib/company.class.php3");
/*
 *
 */
llxHeader();
$db = new Db();
if ($sortorder == "") {
  $sortfield="lower(s.nom)";
  $sortorder="ASC";
}

$active["1"] = "Offres en ligne";
$active["-1"] = "Moderation";
$active["-2"] = "Refusées";
$active["0"] = "Rédaction";
$active["-3"] = "Désactivées";
$active["-4"] = "Supprimées";

$yn["t"] = "oui";
$yn["f"] = "non";

if ($action == 'stcomm') {
  $sql = "UPDATE societe SET fk_stcomm=$stcommid WHERE idp=$socid";
  $result = $db->query($sql);

    $sql = "INSERT INTO socstatutlog (datel, fk_soc, fk_statut, author) VALUES (now(),$socid,$stcommid,'" . $GLOBALS["REMOTE_USER"] . "')";
    $result = $db->query($sql);


  if ($actioncommid) {
    $sql = "INSERT INTO actioncomm (datea, fk_action, fk_soc) VALUES (now(),$actioncommid,$socid)";
    $result = $db->query($sql);
  }
}

if ($socid > 0) {

  $soc = new Company($db, $socid);
  $soc->fetch();
  /*
   *
   */
  $sql = "SELECT s.idp, s.nom,".$db->pdate("s.datec")." as dc,".$db->pdate("s.datem")." as dm,".$db->pdate("s.datea")." as da, s.intern, s.cjn, s.c_nom, s.c_prenom, s.c_tel, s.c_mail, s.tel, s.fax, s.fplus, s.cjn, s.viewed, st.libelle as stcomm, s.fk_stcomm, s.url,s.cp,s.ville, s.note FROM societe as s, c_stcomm as st ";
  $sql .= " WHERE s.fk_stcomm=st.id";

  $sql .= " AND s.idp = $socid";
    

  $result = $db->query($sql);

  if ($result) {
    $objsoc = $db->fetch_object($result , 0);



    print "<table width=\"100%\" border=0><tr>\n";

    print "<td bgcolor=\"white\" colspan=\"2\"><big>N° $objsoc->idp - $soc->nom - [$soc->stcomm]</big></td></tr>" ;

    print "<tr>";

    print "<td valign=\"top\">";
    print "tel : $soc->tel<br>";
    print "fax : $soc->fax<br>";
    print "$soc->cp $soc->ville<br>";
    if ($objsoc->url) {
      print "<a href=\"http://$soc->url\">$soc->url</a><br>";
    }
    print "<br>Contact : <br><b>$soc->c_nom $soc->c_prenom</b>";
    print "<br>tel : <b>$soc->c_tel</b>";
    print "<br>email : <b>$soc->c_mail</b>";


    print "</td>\n";
    print "<td valign=\"top\"><table border=0 width=\"100%\" cellspacing=0 bgcolor=#e0e0e0>";
    print "<tr><td>Créée le</td><td align=center><b>" . strftime("%d %b %Y %H:%M", $objsoc->dc) . "</b></td></tr>";
    print "<tr><td>Dernière modif le</td><td align=center><b>" . strftime("%d %b %Y %H:%M", $objsoc->dm) . "</b></td></tr>";
    print "<tr><td>Fiche société</td><td align=center><b>".$yn[$objsoc->fplus]."</b></td></tr>" ;
    print "<tr><td>Cojonet</td><td align=center><b>".$yn["$objsoc->cjn"]."</b></td></tr>" ;
    print "<tr><td>Consult Fiche</td><td align=center><b>$objsoc->viewed</b></td></tr>";
    print "<tr><td valign=\"top\"><b>Offres</b>";

    print "<hr noshade size=1><table border=0 cellspacing=0>";
    $sql = "SELECT count(idp) as cc, active FROM offre WHERE fk_soc = $objsoc->idp GROUP by active ORDER BY active DESC";
    $result = $db->query($sql);
    $i = 0 ; $num = $db->num_rows();
    while ($i < $num) {
      $obj = $db->fetch_object( $i);
      print "<tr><td>".$active["$obj->active"] . "</td><td>:</td><td>$obj->cc</tr>";
      $i++;
    }
    print "</table></td>";

    print "<td valign=\"top\"><b>Divers</b><hr noshade size=1>";
    print "<table cellspacing=0 border=0>";
    $sql = "SELECT count(idp) as cc FROM abo_soc WHERE fksoc = $objsoc->idp GROUP by active";
    $result = $db->query($sql);
    $i = 0 ; $num = $db->num_rows();
    while ($i < $num) {
      $obj = $db->fetch_object( $i);
      print "<tr><td>Abonnements :</td><td>$obj->cc</td></tr>";
      $i++;
    }
    $sql = "SELECT count(idp) as cc FROM socfollowresume WHERE fk_soc = $objsoc->idp";
    $result = $db->query($sql);
    $i = 0 ; $num = $db->num_rows();
    while ($i < $num) {
      $obj = $db->fetch_object( $i);
      print "<tr><td>Cand. suivis :</td><td>$obj->cc</td></tr>";
      $i++;
    }
    $sql = "SELECT count(idp) as cc FROM soccontact WHERE fk_soc = $objsoc->idp";
    $result = $db->query($sql);
    $i = 0 ; $num = $db->num_rows();
    while ($i < $num) {
      $obj = $db->fetch_object( $i);
      print "<tr><td>Contacts :</td><td>$obj->cc</td></tr>";
      $i++;
    }
 
    print "</table></td>\n";
    print "</tr>";

    print "</table></td></tr>\n";
    print "</table>";

    print "<hr noshade size=1>";
    /*
     *
     *
     */
    print "<table width=\"100%\" cellspacing=0 border=0 cellpadding=2><tr><td valign=\"top\">";
    print "<table width=\"100%\" cellspacing=0 border=0 cellpadding=2>";

    $sql = "SELECT a.id,".$db->pdate("a.datel")." as da, c.libelle, a.author FROM socstatutlog as a, c_stcomm as c WHERE a.fk_soc = $objsoc->idp AND c.id=a.fk_statut ORDER by a.datel DESC";
    $result = $db->query($sql);
    $i = 0 ; $num = $db->num_rows(); $tag = True;
    while ($i < $num) {
      $obj = $db->fetch_object( $i);
      if ($tag) {
	print "<tr bgcolor=\"e0e0e0\">";
      } else {
	print "<tr>";
      }
      print "<td>".  strftime("%d %b %Y %H:%M", $obj->da)  ."</td>";
      print "<td>$obj->libelle</td>";
      print "<td>$obj->author</td>";
      print "</tr>\n";
      $i++;
      $tag = !$tag;
    }
    print "</table>";

    print "</td>";
    /*
     *
     *
     */
    print "<td valign=\"top\">"; 
    
    print "<table width=\"100%\" cellspacing=0 border=0 cellpadding=2>";

    $sql = "SELECT a.id,".$db->pdate("a.datea")." as da, c.libelle, a.author FROM actioncomm as a, c_actioncomm as c WHERE a.fk_soc = $objsoc->idp AND c.id=a.fk_action ORDER by a.datea DESC";

    $result = $db->query($sql);
    $i = 0 ; $num = $db->num_rows(); $tag = True;
    while ($i < $num) {
      $obj = $db->fetch_object( $i);
      if ($tag) {
	print "<tr bgcolor=\"e0e0e0\">";
      } else {
	print "<tr>";
      }
      print "<td>".  strftime("%d %b %Y %H:%M", $obj->da)  ."</td>";
      print "<td>$obj->libelle</td>";
      print "<td>$obj->author</td>";
      print "</tr>\n";
      $i++;
      $tag = !$tag;
    }
    print "</table>";


    print "</td></tr></table>";

    
    print "<table border=0 width=\"100%\" cellspacing=2 bgcolor=#e0e0e0>";
    print "<tr><td>";
    print "<form method=\"post\" action=\"index.php3?socid=$socid\">";
    print "<input type=\"hidden\" name=\"action\" value=\"note\">";
    print "<textarea name=\"note\" cols=\"60\" rows=\"10\">$objsoc->note</textarea><br>";
    print "<input type=\"submit\">";
    print "</form></td></tr>";
    print "<tr><td>".nl2br($objsoc->note)."</td></tr>";
    print "</table>";
    
  } else {
    print $db->error();
  }
}
$db->free();
$db->close();

llxFooter();
?>
