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
require("../lib/functions.inc.php3");
require("../lib/company.lib.php3");
llxHeader();
$db = new Db();
if ($sortorder == "") {
  $sortorder="ASC";
}
if ($sortfield == "") {
  $sortfield="nom";
}
$bc[0]="bgcolor=\"#c0f0c0\"";
$bc[1]="bgcolor=\"#b0e0b0\"";
$bc2[0]="bgcolor=\"#c9f000\"";
$bc2[1]="bgcolor=\"#b9e000\"";
$active["1"] = "Offres en ligne";
$active["-1"] = "Moderation";
$active["-2"] = "Refusées";
$active["0"] = "Rédaction";
$active["-3"] = "Désactivées";
$active["-4"] = "Supprimées";
$cr["t"] = "Cab. Recrut.";
$cr["f"] = "-";
$cr[""] = "????";
$cr["1"] = "Cab. Recrut.";
$cr["0"] = "-";

$yn["t"] = "oui";
$yn["f"] = "non";
$yn["1"] = "oui";
$yn["0"] = "non";

$deacmeth["b"] = "robots";
$deacmeth["m"] = "manuelle";

if ($action == 'attribute_prefix') {
  $prefix_attrib = soc_attribute_prefix($db, $socid);
}

if ($action == 'cabrecrut') {
  if ($selectvalue) {
    $sql = "UPDATE societe SET cabrecrut='$selectvalue' WHERE idp=$socid";
    $result = $db->query($sql);
  }
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
    $sql = "INSERT INTO actioncomm (datea, fk_action, fk_soc, author) VALUES ('$dateaction',$actioncommid,$socid,'" . $GLOBALS["REMOTE_USER"] . "')";
    $result = @$db->query($sql);

    if (!$result) {
      $errmesg = "ERREUR DE DATE !";
    }
  }
}
if ($page == -1) { $page = 0 ; }
$limit = 26;
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

print "<DIV align=\"center\">";

print "<A href=\"$PHP_SELF?page=$pageprev&begin=$begin&stcomm=$stcomm&sortfield=$sortfield&sortorder=$sortorder&aclasser=$aclasser&coord=$coord\">&lt;- Prev</A>\n| ";
print "<A href=\"$PHP_SELF?page=$pageprev&stcomm=$stcomm&sortfield=$sortfield&sortorder=$sortorder&aclasser=$aclasser&coord=$coord\">*</A>\n| ";
for ($i = 65 ; $i < 91; $i++) {
  print "<A href=\"$PHP_SELF?begin=" . chr($i) . "&stcomm=$stcomm\" class=\"T3\">";
  
  if ($begin == chr($i) ) {
    print  "<b>-&gt;" . chr($i) . "&lt;-</b>" ; 
  } else {
    print  chr($i)  ; 
  }
  print "</A> | ";
}
print " <A href=\"$PHP_SELF?page=$pagenext&begin=$begin&stcomm=$stcomm&sortfield=$sortfield&sortorder=$sortorder&aclasser=$aclasser&coord=$coord\">Next -></A>\n";
print "</DIV><P>";
/*
 * Recherche
 *
 *
 */
if ($mode == 'search') 
{
  if ($mode-search == 'soc')
    {
      $sql = "SELECT s.idp FROM societe as s, c_stcomm as st ";
      $sql .= " WHERE s.fk_stcomm = st.id AND s.datea IS NOT NULL";

      if ($socname) 
	{
	  $sql .= " AND lower(s.nom) like '%".strtolower($socname)."%'";
	  $sortfield = "lower(s.nom)";
	  $sortorder = "ASC";
	}
      
 
      $result = $db->query($sql);
      if ($result) 
	{
	  if ( $db->num_rows() == 1) 
	    {
	      $obj = $db->fetch_object(0);
	      $socid = $obj->idp;
	    }
	  $db->free();
	}
    }
  else 
    {

    }

}



/*
 * Mode fiche
 *
 *
 */  
if ($socid > 0) {

  $sql = "SELECT s.idp, s.nom, ".$db->pdate("s.datec")." as dc,".$db->pdate("s.datel")." as dl,".$db->pdate("s.datem")." as dm, ".$db->pdate("s.datea")." as da, s.intern, s.cjn, s.c_nom, s.c_prenom, s.c_tel, s.c_mail, s.tel, s.fax, s.fplus, s.cjn, s.viewed, st.libelle as stcomm, s.fk_stcomm, s.url,s.address,s.cp,s.ville, s.note,s.karma,s.off_acc, s.off_ref,s.view_res_coord, t.libelle as typent, s.cabrecrut, e.libelle as effectif, s.siren, s.prefix_comm, s.services,s.parent, s.description FROM societe as s, c_stcomm as st, c_typent as t, c_effectif as e ";
  $sql .= " WHERE s.fk_stcomm=st.id AND s.fk_typent = t.id AND s.fk_effectif = e.id";

  if ($to == 'next') {
    $sql .= " AND s.idp > $socid ORDER BY idp ASC LIMIT 1";
  } elseif ($to == 'prev') {
    $sql .= " AND s.idp < $socid ORDER BY idp DESC LIMIT 1";
  } else {
    $sql .= " AND s.idp = $socid";
  }

  $result = $db->query($sql);

  if ($result) {
    $objsoc = $db->fetch_object(0);

    $dac = strftime("%Y-%m-%d %H:%M", time());
    if ($errmesg) {
      print "<b>$errmesg</b><br>";
    }
    print "<form action=\"index.php3?socid=$objsoc->idp\" method=\"post\">";
    print "<input type=\"hidden\" name=\"action\" value=\"stcomm\">";
    print "<input type=\"hidden\" name=\"oldstcomm\" value=\"$objsoc->fk_stcomm\">";
    
    $sql = "SELECT st.id, st.libelle FROM c_stcomm as st ORDER BY id";
    $result = $db->query($sql);
    print "<select name=\"stcommid\">\n";
    print "<option value=\"null\" SELECTED>\n";
    if ($result) {
      $num = $db->num_rows();
      $i = 0 ;
      while ($i < $num) {
	$objse = $db->fetch_object( $i);
	
	print "<option value=\"$objse->id\"";
	if ($objse->id == $objsoc->fk_stcomm) { print " SELECTED"; }
	print ">$objse->libelle\n";
	$i++;
      }
    }
    print "</select>\n";
    $sql = "SELECT st.id, st.libelle FROM c_actioncomm as st ORDER BY id";
    $result = $db->query($sql);
    print "<select name=\"actioncommid\">\n";
    print "<option value=\"0\" SELECTED>\n";
    if ($result) {
      $num = $db->num_rows();
      $i = 0 ;
      while ($i < $num) {
	$objse = $db->fetch_object($i);
	
	print "<option value=\"$objse->id\">$objse->libelle\n";
	$i++;
      }
    }
    print "</select>\n";

    print "<input type=\"text\" name=\"dateaction\" size=\"16\" value=\"$dac\">";
    print "<input type=\"submit\" value=\"Update\">";
    print "</form>\n";
    /*
     *
     */
    print "<table width=\"100%\" border=\"0\" cellspacing=\"1\">\n";
    /*
     *
     */
    print "<tr><td><big>N° $objsoc->idp - $objsoc->nom - [$objsoc->stcomm]</big></td>";
    print "<td bgcolor=\"#e0E0E0\" align=\"center\"><a href=\"bookmark.php3?socidp=$objsoc->idp&action=add\">[Bookmark]</a></td>";
    print "<td bgcolor=\"#e0E0E0\" align=\"center\"><a href=\"projet/fiche.php3?socidp=$objsoc->idp&action=create\">[Projet]</a></td>";
    print "<td bgcolor=\"#e0E0E0\" align=\"center\"><a href=\"addpropal.php3?socidp=$objsoc->idp&action=create\">[Propal]</a></td>";
    print "<td><a href=\"socnote.php3?socid=$objsoc->idp\">Notes</a></td>";
    print "<td><a href=\"people.php3?socid=$objsoc->idp\">Contacts</a></td>";
    print "<td><a href=\"../tech/soc/soc.php3?socid=$objsoc->idp\">Fiche Technique</a></td>";
    print "<td bgcolor=\"#e0E0E0\" align=\"center\">[<a href=\"../soc.php3?socid=$objsoc->idp&action=edit\">Editer</a>]</td>";
    print "</tr></table>";
    if ($objsoc->parent > 0) {
      print "<hr>Société rattaché au cabinet <a href=\"$PHP_SELF?socid=$objsoc->parent\">$objsoc->parent</a>";
    }
    print "<hr>";
    print "<table width=\"100%\" border=0><tr>\n";
    print "<td valign=\"top\">";
    print "<table cellspacing=\"0\" border=\"1\" width=\"100%\">";

    print "<tr><td>Type</td><td> $objsoc->typent</td><td>Effectif</td><td>$objsoc->effectif</td></tr>";
    print "<tr><td>Tel</td><td> $objsoc->tel&nbsp;</td><td>fax</td><td>$objsoc->fax&nbsp;</td></tr>";
    print "<tr><td>Ville</td><td colspan=\"3\">".nl2br($objsoc->address)."<br>$objsoc->cp $objsoc->ville</td></tr>";

    print "<tr><td>siren</td><td><a href=\"http://www.societe.com/cgi-bin/recherche?rncs=$objsoc->siren\">$objsoc->siren</a>&nbsp;</td>";
    print "<td>prefix</td><td>";
    if ($objsoc->prefix_comm) {
      print $objsoc->prefix_comm;
    } else {
      print "[<a href=\"$PHP_SELF?socid=$objsoc->idp&action=attribute_prefix\">Attribuer</a>]";
    }

    print "</td></tr>";

    print "<tr><td>Site</td><td colspan=\"3\"><a href=\"http://$objsoc->url\">$objsoc->url</a>&nbsp;</td></tr>";

    print "<tr><td>Contact </td><td colspan=\"3\"><b>$objsoc->c_nom $objsoc->c_prenom</b>&nbsp;</td></tr>";
    print "<tr><td>tel</td><td><b>$objsoc->c_tel</b>&nbsp;</td><td colspan=\"2\">email : <b>$objsoc->c_mail</b>&nbsp;</td></tr>";
    print "</table>";

    /*
     *
     */
    print "</td>\n";
    print "<td valign=\"top\"><table border=0 width=\"100%\" cellspacing=0 bgcolor=#e0e0e0>";
    if ($objsoc->dl > 0) {
      $datel = strftime("%d %b %Y %H:%M", $objsoc->dl);
    } else {
      $datel = "Pas d'infos";
    }
    print "<tr><td>Dernière connexion</td><td align=center><b>$datel</b></td></tr>";
    print "<tr><td>Créée le</td><td align=center><b>" . strftime("%d %b %Y %H:%M", $objsoc->dc) . "</b></td></tr>";
    //print "<tr><td>Dernière modif le</td><td align=center><b>" . strftime("%d %b %Y %H:%M", $objsoc->dm) . "</b></td></tr>";
    print "<tr><td>Fiche Entreprise</td><td align=center><b>".$yn[$objsoc->fplus]."</b></td></tr>" ;
    print "<tr bgcolor=\"#d0d0d0\"><td>Coordonnees CV</td><td align=center><b>".$yn["$objsoc->view_res_coord"]."</b>&nbsp;</td></tr>" ;
    print "<tr bgcolor=\"#d0d0d0\"><td><b>Contacts CV</b></td><td align=center><b>".$yn["$objsoc->services"]."</b>&nbsp;</td></tr>" ;

    if ($objsoc->cabrecrut == 1) {
      print "<tr bgcolor=\"white\"><td><b>Cab. Recrut.</b> : Oui</td>";
    } elseif ($objsoc->cabrecrut == 0) {
      print "<tr><td>Cab. Recrut. : Non</td>";
    } else {
      print "<tr><td>Cab. Recrut. : ???</td>";
    }
    print "<td><a href=\"$PHP_SELF?socid=$objsoc->idp&action=changevalue&type=cabrecrut\">changer</a></td></tr>";

    print "<tr><td colspan=\"2\">";
    //print "<hr noshade size=1></td></tr>";
    //print "<tr><td>Cojonet</td><td align=center><b>".$yn["$objsoc->cjn"]."</b></td></tr>" ;
    //print "<tr><td>Consult Fiche</td><td align=center><b>$objsoc->viewed</b></td></tr>";

    print "<tr><td valign=\"top\">";
    print "<hr noshade size=1>";

    print "<table border=0 cellspacing=0>";
    print "<tr><td><b>Karma</b></td><td>:</td><td align=center><b>$objsoc->karma</b></td></tr>";
    print "<tr><td><b>Nb d'acceptation</b></td><td>:</td><td align=center><b>$objsoc->off_acc</b></td></tr>";
    print "<tr><td><b>Nb de refus</b></td><td>:</td><td align=center><b>$objsoc->off_ref</b></td></tr>";

    $sql = "SELECT count(idp) as cc, active FROM offre WHERE fk_soc = $objsoc->idp GROUP by active ORDER BY active DESC";
    if ( $db->query($sql) ) {

      $i = 0 ; $num = $db->num_rows();
      while ($i < $num) {
	$obj = $db->fetch_object( $i);
	print "<tr><td>".$active["$obj->active"] . "</td><td>:</td><td>$obj->cc</tr>";
	$i++;
      }
      $db->free();
    } else {
      print $db->error();
    }
    print "</table></td>";

    print "<td valign=\"top\"><hr noshade size=1>";
    print "<table cellspacing=0 border=0>";
    print "<tr><td><b>CV consultés</b></td>";
    $sql = "SELECT week1 + week2 + week3 + week4 as t, week1, week2, week3, week4 FROM soc_resviewed_byweek WHERE fk_soc = $objsoc->idp";
    if ( $db->query($sql) ) {
      $i = 0 ; $num = $db->num_rows();
      while ($i < $num) {
	$obj = $db->fetch_object( $i);
	print "<td>: $obj->t ( $obj->week1 - $obj->week2 - $obj->week3 - $obj->week4 )</td></tr>";
	$i++;
      }
      $db->free();
    } else {
      print $db->error();
    }
    print "</tr>";
    print "<tr><td><b>Contacts</b></td>";
    $sql = "SELECT week1 + week2 + week3 + week4 as t, week1, week2, week3, week4 FROM soc_rescontact_byweek WHERE fk_soc = $objsoc->idp";
    if ( $db->query($sql) ) {
      $i = 0 ; $num = $db->num_rows();
      while ($i < $num) {
	$obj = $db->fetch_object( $i);
	print "<td>: $obj->t ( $obj->week1 - $obj->week2 - $obj->week3 - $obj->week4 )</td></tr>";
	$i++;
      }
      $db->free();
    } else {
      print $db->error();
    }

    $sql = "SELECT count(idp) as cc FROM abo_soc WHERE fksoc = $objsoc->idp GROUP by active";
    $result = $db->query($sql);
    $i = 0 ; $num = $db->num_rows();
    while ($i < $num) {
      $obj = $db->fetch_object( $i);
      print "<tr><td>Abonnements</td><td>: $obj->cc</td></tr>";
      $i++;
    }
    $sql = "SELECT count(idp) as cc FROM socfollowresume WHERE fk_soc = $objsoc->idp";
    if ( $db->query($sql) ) {
      $i = 0 ; $num = $db->num_rows();
      while ($i < $num) {
	$obj = $db->fetch_object( $i);
	print "<tr><td>Cand. suivis</td><td>: $obj->cc</td></tr>";
	$i++;
      }
      $db->free();
    } else {
      print $db->error();
    }
    print "</table>\n";
    print "</td></tr>";

    print "</table></td></tr>\n";
    /*
     *
     */
    print "<tr><td valign=\"top\">";

    /*
     * Propales
     */
    $var=!$var;
    print "<TABLE border=\"1\" width=\"100%\" cellspacing=\"0\" cellpadding=\"1\">";
    $sql = "SELECT s.nom, s.idp, p.rowid as propalid, p.price, p.ref, p.remise, ".$db->pdate("p.datep")." as dp, c.label as statut, c.id as statutid";
    $sql .= " FROM societe as s, llx_propal as p, c_propalst as c WHERE p.fk_soc = s.idp AND p.fk_statut = c.id";
    $sql .= " AND s.idp = $objsoc->idp ORDER BY p.datep DESC";

    if ( $db->query($sql) ) {
      $num = $db->num_rows();
      if ($num >0 ) {
	print "<tr $bc[$var]><td colspan=\"4\"><a href=\"propal.php3?socidp=$objsoc->idp\">liste des propales ($num)</td></tr>";
      }
      $i = 0;	$now = time(); 	$lim = 3600 * 24 * 15 ;
      while ($i < $num && $i < 2) {
	$objp = $db->fetch_object( $i);
	$var=!$var;
	print "<TR $bc[$var]>";
	print "<TD><a href=\"propal.php3?propalid=$objp->propalid\">$objp->ref</a>\n";
	if ( ($now - $objp->dp) > $lim && $objp->statutid == 1 ) {
	  print " <b>&gt; 15 jours</b>";
	}
	print "</td><TD align=\"right\">".strftime("%d %B %Y",$objp->dp)."</TD>\n";
	print "<TD align=\"right\">".price($objp->price - $objp->remise)."</TD>\n";
	print "<TD align=\"center\">$objp->statut</TD></tr>\n";
	$i++;
      }
      $db->free();
    }
    /*
     *   Factures
     */
    $sql = "SELECT s.nom, s.idp, f.facnumber, f.amount, ".$db->pdate("f.datef")." as df, f.paye, f.rowid as facid ";
    $sql .= " FROM societe as s,llx_facture as f WHERE f.fk_soc = s.idp AND s.idp = $objsoc->idp ORDER BY f.datef DESC";
    if ( $db->query($sql) ) {
      $num = $db->num_rows(); $i = 0; 
      if ($num > 0) {
	print "<tr $bc2[$var]>";
	print "<td colspan=\"3\"><a href=\"../compta/index.php3?socidp=$objsoc->idp\">liste des factures ($num)</td></tr>";
      }

      while ($i < $num && $i < 2) {
	$objp = $db->fetch_object( $i);
	$var=!$var;
	print "<TR $bc2[$var]>";
	print "<TD><a href=\"../compta/facture.php3?facid=$objp->facid\">$objp->facnumber</a></TD>\n";
	if ($objp->df > 0 ) {
	  print "<TD align=\"right\">".strftime("%d %B %Y",$objp->df)."</TD>\n";
	} else {
	  print "<TD align=\"right\"><b>!!!</b></TD>\n";
	}
	print "<TD align=\"right\">".number_format($objp->amount, 2, ',', ' ')."</TD>\n";
	$paye[1] = "payée";
	$paye[0] = "<b>non payée</b>";
	print "<TD align=\"center\">".$paye[$objp->paye]."</TD>\n";
	print "</TR>\n";
	$i++;
      }
      $db->free();
    } else {
      print $db->error();
    }
    print "</table>";

    print "</td><td valign=\"top\">";
    /*
     *
     *  Ventes
     *
     */
    $sql  = "SELECT p.rowid,p.label,p.ref,".$db->pdate("v.dated")." as dd,".$db->pdate("v.datef")." as df";
    $sql .= " FROM llx_product as p, llx_ventes as v WHERE p.rowid = v.fk_product AND v.fk_soc = $objsoc->idp ORDER BY dated DESC";
    if ( $db->query($sql) ) {
      print "<table border=1 cellspacing=0 width=100% cellpadding=\"1\">";
      $i = 0 ; $num = $db->num_rows();
      if ($num > 0) {
	$tag = !$tag; print "<tr $bc[$tag]>";
	print "<td colspan=\"3\"><a href=\"../compta/index.php3?socidp=$objsoc->idp\">liste des ventes ($num)</td></tr>";
      }
      while ($i < $num && $i < 5) {
	$obj = $db->fetch_object( $i);
	$tag = !$tag;
	print "<tr $bc[$tag]>";
	$nw = time();
	if ($nw <= $obj->df && $nw >= $obj->dd) {
	  print "<td><b>$obj->label</b></td>";
	} else {
	  print "<td>$obj->label</td>";
	}
	print "<td align=\"right\">".strftime("%d %b %Y", $obj->dd) ."</td><td align=\"right\">".strftime("%d %b %Y", $obj->df) ."</tr>";
	$i++;
      }
      $db->free();
      print "</table>";
    } else {
      print $db->error();
    }
    /*
     *
     * Liste des projets associés
     *
     */
    $sql  = "SELECT p.rowid,p.title,p.ref,".$db->pdate("p.dateo")." as do";
    $sql .= " FROM llx_projet as p WHERE p.fk_soc = $objsoc->idp";
    if ( $db->query($sql) ) {
      print "<table border=1 cellspacing=0 width=100% cellpadding=\"1\">";
      $i = 0 ; 
      $num = $db->num_rows();
      if ($num > 0) {
	$tag = !$tag; print "<tr $bc[$tag]>";
	print "<td colspan=\"2\"><a href=\"projet/index.php3?socidp=$objsoc->idp\">liste des projets ($num)</td></tr>";
      }
      while ($i < $num && $i < 5) {
	$obj = $db->fetch_object( $i);
	$tag = !$tag;
	print "<tr $bc[$tag]>";
	print '<td><a href="projet/fiche.php3?id='.$obj->rowid.'">'.$obj->title.'</a></td>';

	print "<td align=\"right\">".strftime("%d %b %Y", $obj->do) ."</td></tr>";
	$i++;
      }
      $db->free();
      print "</table>";
    } else {
      print $db->error();
    }

    /*
     *
     *
     */
    print "</td></tr>";
    print "</table>\n";
    /*
     *
     *
     *
     */
    if ($action == 'changevalue') {

      print "<HR noshade>";
      print "<form action=\"index.php3?socid=$objsoc->idp\" method=\"post\">";
      print "<input type=\"hidden\" name=\"action\" value=\"cabrecrut\">";
      print "Cette société est un cabinet de recrutement : ";
      print "<select name=\"selectvalue\">";
      print "<option value=\"\">";
      print "<option value=\"t\">Oui";
      print "<option value=\"f\">Non";
      print "</select>";
      print "<input type=\"submit\" value=\"Mettre &agrave; jour\">";
      print "</form>\n";
    } else {
      /*
       *
       * Liste des contacts
       *
       */
      print "<table width=\"100%\" cellspacing=0 border=1 cellpadding=2>";

      print "<tr><td><b>Pr&eacute;nom Nom</b></td>";
      print '<td><b>Poste</b></td><td><b>T&eacute;l</b></td>';
      print "<td><b>Fax</b></td><td><b>Email</b></td>";
      print "<td><a href=\"people.php3?socid=$objsoc->idp&action=addcontact\">Ajouter</a></td></tr>";
    
      $sql = "SELECT p.idp, p.name, p.firstname, p.poste, p.phone, p.fax, p.email, p.note FROM socpeople as p WHERE p.fk_soc = $objsoc->idp  ORDER by p.datec";
      $result = $db->query($sql);
      $i = 0 ; $num = $db->num_rows(); $tag = True;
      while ($i < $num) {
	$obj = $db->fetch_object( $i);
	if ($tag) {
	  print "<tr bgcolor=\"e0e0e0\">";
	} else {
	  print "<tr>";
	}
	print "<td>$obj->firstname $obj->name";
	if ($obj->note) {
	  print "<br><b>".nl2br($obj->note);
	}
	print "</td>";
	print "<td>$obj->poste&nbsp;</td>";
	print '<td><a href="actioncomm.php3?action=create&actionid=1&contactid='.$obj->idp.'&socid='.$objsoc->idp.'">'.$obj->phone.'</a>&nbsp;</td>';
	print "<td>$obj->fax&nbsp;</td>";
	print '<td><a href="actioncomm.php3?action=create&actionid=4&contactid='.$obj->idp.'&socid='.$objsoc->idp.'">'.$obj->email.'</a>&nbsp;</td>';
	print "<td><a href=\"people.php3?socid=$objsoc->idp&action=editcontact&contactid=$obj->idp\">Modifier</a></td>";
	print "</tr>\n";
	$i++;
	$tag = !$tag;
      }
      print "</table>";
    
    
      print "\n<hr noshade size=1>\n";
      /*
       *
       */
      print "<table width=\"100%\" cellspacing=0 border=0 cellpadding=2>\n<tr><td valign=\"top\">\n";
      /*
       *
       * Liste des statuts commerciaux
       *
       */
      $limliste = 5 ;
      print "<table width=\"100%\" cellspacing=0 border=0 cellpadding=2>\n";

      $sql  = "SELECT a.id, ".$db->pdate("a.datel")." as da, c.libelle, a.author ";
      $sql .= " FROM socstatutlog as a, c_stcomm as c WHERE a.fk_soc = $objsoc->idp AND c.id=a.fk_statut ORDER by a.datel DESC";
      if ( $db->query($sql) ) {
	$i = 0 ; $num = $db->num_rows(); $tag = True;
	while ($i < $num && $i < $limliste) {
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
	$db->free();
	if ($num > $limliste) {
	  print "<tr><td>suite ...</td></tr>";
	}
      } else {
	print $db->error();
      }
      print "</table>";

      print "</td><td valign=\"top\">";
      /*
       *
       *      Listes des actions
       *
       */
      $sql = "SELECT a.id, ".$db->pdate("a.datea")." as da, c.libelle, a.author, a.propalrowid ";
      $sql .= " FROM actioncomm as a, c_actioncomm as c WHERE a.fk_soc = $objsoc->idp AND c.id=a.fk_action ORDER BY a.datea DESC, a.id DESC";
      if ( $db->query($sql) ) {
	print "<table width=\"100%\" cellspacing=0 border=0 cellpadding=2>\n";
	print '<tr><td><a href="actioncomm.php3?socid='.$objsoc->idp.'">Actions</a></td></tr>';

	$i = 0 ; $num = $db->num_rows(); $tag = True;
	while ($i < $num) {
	  $obj = $db->fetch_object( $i);
	  if ($tag) {
	    print "<tr bgcolor=\"e0e0e0\">";
	  } else {
	    print "<tr>";
	  }
	  print "<td>".  strftime("%d %b %Y %H:%M", $obj->da)  ."</td>";

	  if ($obj->propalrowid) {
	    print "<td><a href=\"propal.php3?propalid=$obj->propalrowid\">$obj->libelle</a></td>";
	  } else {
	    print "<td>$obj->libelle</td>";
	  }
	  print "<td>$obj->author</td>";
	  print "</tr>\n";
	  $i++;
	  $tag = !$tag;
	}
	print "</table>";

	$db->free();
      } else {
	print $db->error();
      }
      print "</td></tr></table>";    
      /*
       * Note sur la societe
       */

      print '<table border="1" width="100%" cellspacing="0" bgcolor="#e0e0e0">';
      print "<tr><td>".nl2br($objsoc->note)."</td></tr>";
      print "</table>";

      /*
       *
       *    Offres
       *
       */
      $sql = "SELECT o.idp, o.titre, ";
      $sql .= $db->pdate("o.datea")." as da, ".$db->pdate("o.dated")." as dd,";
      $sql .= $db->pdate("o.datec")." as dc,o.active,o.deacmeth, o.site FROM offre as o";
      $sql .= " WHERE o.fk_soc = $objsoc->idp AND o.created =1";
      $sql .= " ORDER BY o.datea DESC";
    
      $result = $db->query($sql);
      $num = $db->num_rows();
      $i = 0;
    
      if ($num > 0)
	{

	  $bc1="bgcolor=\"#c0f0c0\"";
	  $bc3="bgcolor=\"#90c090\"";
	  $bc2="bgcolor=\"#b0e0b0\"";
    
	  print "<p><TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
	  print "<TR bgcolor=\"orange\">";
	  print "<TD>$num</td>";
	  print "<TD><a href=\"index.php3?sortfield=s.idp&sortorder=$sortorder\">IDP</a></TD>";
	  print "<TD>Site</TD>";
	  print "<TD>Statut</TD>";
	  print "<TD><a href=\"index.php3?sortfield=s.nom&sortorder=$sortorder\">Titre</a></td>";
	  print "<TD align=\"center\">Cr&eacute;&eacute;e</TD>";
	  print "<TD align=\"center\">Activ&eacute;e</TD>";
	  print "<TD align=\"center\"><a href=\"index.php3?sortfield=s.datea&sortorder=$sortorder\">Dated</a></TD>";
	  print "<TD align=\"center\">Meth</TD>";
	  print "</TR>\n";
	  $var=True;
	  while ($i < $num) {
	    $objo = $db->fetch_object( $i);
	    $var=!$var;
	    
	    if (!$var) {
	      $bc=$bc1;
	    } else {
	      $bc=$bc2;
	    }
	    if ($objo->active < -2) {
	  $bc = $bc3;
	    }
      
	    print "<TR $bc>";
	    print "<TD>" . ($i + 1 + ($limit * $page)) . "</TD>";
	    print "<TD>$objo->idp</TD>";
	    print "<TD align=\"center\">$objo->site</TD>";
	    if ($objo->active == 1) {
	      print "<TD><b>" . $active["$objo->active"] ."</b></TD>";
	    } else {
	      print "<TD>" . $active["$objo->active"] ."</TD>";
	    }
	    print "<TD><a href=\"../prod/offre.php3?id=$objo->idp\">$objo->titre</A></TD>\n";
	    print "<TD align=\"center\">" . strftime("%d %b %Y", $objo->dc) . "</TD>";
	    print "<TD align=\"center\">" . strftime("%d %b %Y", $objo->da) . "</TD>";
	    if ($objo->active <> -2) {
	      print "<TD align=\"center\">" . strftime("%d %b %Y", $objo->dd) . "</TD>";
	    } else {
	      print "<TD align=\"center\">&nbsp;</TD>";
	    }
	    if ($objo->active == -3) {
	      print "<TD>".$deacmeth[$objo->deacmeth]."</TD>";
	    } else {
	      print "<TD align=\"center\">&nbsp;</TD>";
	    }

	    print "</TR>\n";
	    $i++;
	  }
	  print "</TABLE>";    
	}
      /*
       *
       *
       *
       */
      print "<HR noshade>";
      print "<form action=\"index.php3?socid=$objsoc->idp\" method=\"post\">";
      print "<input type=\"hidden\" name=\"action\" value=\"recontact\">";

      $strmonth[1] = "Janvier";
      $strmonth[2] = "F&eacute;vrier";
      $strmonth[3] = "Mars";
      $strmonth[4] = "Avril";
      $strmonth[5] = "Mai";
      $strmonth[6] = "Juin";
      $strmonth[7] = "Juillet";
      $strmonth[8] = "Ao&ucirc;t";
      $strmonth[9] = "Septembre";
      $strmonth[10] = "Octobre";
      $strmonth[11] = "Novembre";
      $strmonth[12] = "D&eacute;cembre";
    
      $smonth = 1;
      $syear = date("Y", time());
      print "A recontacter : ";
      print "<select name=\"reday\">";    
      for ($day = 1 ; $day < $sday + 32 ; $day++) {
	print "<option value=\"$day\">$day";
      }
      print "</select>";
      $cmonth = date("n", time());
      print "<select name=\"remonth\">";    
      for ($month = $smonth ; $month < $smonth + 12 ; $month++) {
	if ($month == $cmonth) {
	  print "<option value=\"$month\" SELECTED>" . $strmonth[$month];
	} else {
	  print "<option value=\"$month\">" . $strmonth[$month];
	}
      }
      print "</select>";
    
      print "<select name=\"reyear\">";
    
      for ($year = $syear ; $year < $syear + 5 ; $year++) {
	print "<option value=\"$year\">$year";
      }
      print "</select>";
      print "<input type=\"submit\" value=\"Ajouter\">";
      print "</form>\n";

      /*
       *
       *      Listes des actions
       *
       */
      $sql = "SELECT ".$db->pdate("sc.datec")." as da, sc.fk_cand ";
      $sql .= " FROM soccontact as sc WHERE sc.fk_soc = $objsoc->idp ORDER BY sc.datec DESC";
      $result = $db->query($sql);
      $num = $db->num_rows(); 
      if ($num > 0) 
	{
	  $tag = True;
	  $i = 0 ; 

	  print "<table width=100%><tr><td valign=top width=50%>";
	  print "Contacts<hr noshade><table cellspacing=0 border=0 cellpadding=2>";
	  print "<tr><td>date</td><td>Candidat</td></tr>";

	  while ($i < $num) {
	    $obj = $db->fetch_object( $i);
	    if ($tag) {
	      print "<tr bgcolor=\"e0e0e0\">";
	    } else {
	      print "<tr>";
	    }
	    print "<td>".  strftime("%d %b %Y %H:%M", $obj->da)  ."</td>";
	    print "<td align=\"center\">$obj->fk_cand</td>";
	    print "</tr>\n";
	    $i++;
	    $tag = !$tag;
	  }
	  print "</table>";
	  print "</td><td valign=top width=50%>";
	  print "Description de la société<hr noshade>$objsoc->description";
	  print "</td></tr></table>";    

	}
    }
  }
} else {
  /*
   * Mode Liste
   *
   *
   *
   */

  $bc[1]="bgcolor=\"#90c090\"";
  $bc[0]="bgcolor=\"#b0e0b0\"";

  $sql = "SELECT s.idp, s.nom, cabrecrut,".$db->pdate("s.datec")." as datec, ".$db->pdate("s.datea")." as datea, s.c_nom,s.c_prenom,s.c_tel,s.c_mail, s.cjn,st.libelle as stcomm, s.prefix_comm FROM societe as s, c_stcomm as st WHERE s.fk_stcomm = st.id AND s.datea IS NOT NULL";

  if (strlen($stcomm)) {
    $sql .= " AND s.fk_stcomm=$stcomm";
  }

  if (strlen($begin)) {
    $sql .= " AND upper(s.nom) like '$begin%'";
  }

  if ($aclasser==1) {
    $sql .= " AND cabrecrut is null";
  }

  if ($coord == 1) {
    $sql .= " AND view_res_coord=1";
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
    print "<p><TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
    print "<TR bgcolor=\"orange\">";
    print "<TD align=\"center\"><a href=\"index.php3?sortfield=idp&sortorder=$sortorder&begin=$begin\">Id</a></TD>";
    print "<TD><a href=\"index.php3?sortfield=lower(s.nom)&sortorder=$sortorder&begin=$begin\">Societe</a></td>";
    print "<TD>Contact</TD>";
    print "<TD>email</TD>";
    print "<TD align=\"center\">Statut</TD><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>";
    print "</TR>\n";
    $var=True;
    while ($i < $num) {
      $obj = $db->fetch_object( $i);
      
      $var=!$var;

      print "<TR $bc[$var]>";
      print "<TD align=\"center\"><b>$obj->idp</b></TD>";
      print "<TD><a href=\"index.php3?socid=$obj->idp\">$obj->nom</A></td>\n";
      print "<TD>$obj->c_nom $obj->c_prenom</TD>\n";
      print "<TD>$obj->c_mail</TD>\n";
      print "<TD align=\"center\">$obj->stcomm</TD>\n";
      print "<TD align=\"center\">$obj->prefix_comm&nbsp;</TD>\n";
      print "<TD><a href=\"addpropal.php3?socidp=$obj->idp&action=create\">[Propal]</A></td>\n";
      print "<TD><a href=\"ventes.php3?socid=$obj->idp&action=add\">[Ventes]</A></TD>\n";
      print "</TR>\n";
      $i++;
    }
    print "</TABLE>";
    $db->free();
  } else {
    print $db->error();
  }
}
$db->close();

llxFooter();
?>
