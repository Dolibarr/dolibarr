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
require("cactioncomm.class.php3");
require("actioncomm.class.php3");
require("../todocomm.class.php3");

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
 *
 * Mode fiche
 *
 *
 */  
if ($socid > 0) {
  $societe = new Societe($db, $socid);
  

  $sql = "SELECT s.idp, s.nom, ".$db->pdate("s.datec")." as dc, s.tel, s.fax, st.libelle as stcomm, s.fk_stcomm, s.url,s.address,s.cp,s.ville, s.note, t.libelle as typent, e.libelle as effectif, s.siren, s.prefix_comm, s.services,s.parent, s.description FROM societe as s, c_stcomm as st, c_typent as t, c_effectif as e ";
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

    /*
     *
     */
    print "<table width=\"100%\" border=\"0\" cellspacing=\"1\">\n";

    print "<tr><td><div class=\"titre\">Fiche client : $objsoc->nom</div></td>";
    print "<td align=\"center\"><a href=\"bookmark.php3?socidp=$objsoc->idp&action=add\">[Bookmark]</a></td>";
    print "<td align=\"center\"><a href=\"projet/fiche.php3?socidp=$objsoc->idp&action=create\">[Projet]</a></td>";
    print "<td align=\"center\"><a href=\"addpropal.php3?socidp=$objsoc->idp&action=create\">[Propal]</a></td>";
    print "<td><a href=\"socnote.php3?socid=$objsoc->idp\">Notes</a></td>";
    print "<td align=\"center\">[<a href=\"../soc.php3?socid=$objsoc->idp&action=edit\">Editer</a>]</td>";
    print "</tr></table>";
    /*
     *
     *
     */


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

    print "</table>";

    /*
     *
     */
    print "</td>\n";
    print '<td valign="top" width="50%">';

    /*
     *
     * Propales
     *
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
	$var=!$var;
    $sql = "SELECT s.nom, s.idp, f.facnumber, f.amount, ".$db->pdate("f.datef")." as df, f.paye, f.rowid as facid ";
    $sql .= " FROM societe as s,llx_facture as f WHERE f.fk_soc = s.idp AND s.idp = $objsoc->idp ORDER BY f.datef DESC";
    if ( $db->query($sql) ) {
      $num = $db->num_rows(); $i = 0; 
      if ($num > 0) {
	print "<tr $bc[$var]>";
	print "<td colspan=\"4\"><a href=\"../compta/index.php3?socidp=$objsoc->idp\">liste des factures ($num)</td></tr>";
      }

      while ($i < $num && $i < 2) {
	$objp = $db->fetch_object( $i);
	$var=!$var;
	print "<TR $bc[$var]>";
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



    print '</td></tr>';
    /*
     *
     */
    print "<tr><td valign=\"top\">";


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
	print '<td><a href="actioncomm.php3?action=create&actionid=5&contactid='.$obj->idp.'&socid='.$objsoc->idp.'">'.$obj->firstname.' '. $obj->name.'</a>&nbsp;</td>';

	if ($obj->note) {
	  print "<br><b>".nl2br($obj->note);
	}
	print "</td>";
	print "<td>$obj->poste&nbsp;</td>";
	print '<td><a href="actioncomm.php3?action=create&actionid=1&contactid='.$obj->idp.'&socid='.$objsoc->idp.'">'.$obj->phone.'</a>&nbsp;</td>';
	print '<td><a href="actioncomm.php3?action=create&actionid=2&contactid='.$obj->idp.'&socid='.$objsoc->idp.'">'.$obj->fax.'</a>&nbsp;</td>';
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
      print '<table width="100%" cellspacing=0 border=0 cellpadding=2>';
      print '<tr>';
      print '<td valign="top">';
      /*
       *
       *      Listes des actions
       *
       */
      $sql = "SELECT a.id, ".$db->pdate("a.datea")." as da, c.libelle, u.code, a.propalrowid, a.fk_user_author, fk_contact, u.rowid ";
      $sql .= " FROM actioncomm as a, c_actioncomm as c, llx_user as u ";
      $sql .= " WHERE a.fk_soc = $objsoc->idp ";
      $sql .= " AND u.rowid = a.fk_user_author";
      $sql .= " AND c.id=a.fk_action ";
      $sql .= " ORDER BY a.datea DESC, a.id DESC";

      if ( $db->query($sql) ) {
	print "<table width=\"100%\" cellspacing=0 border=0 cellpadding=2>\n";

	$i = 0 ; $num = $db->num_rows();
	while ($i < $num) {
	  $var = !$var;

	  $obj = $db->fetch_object( $i);
	  print "<tr $bc[$var]>";

	  if ($oldyear == strftime("%Y",$obj->da) ) {
	    print '<td align="center">|</td>';
	  } else {
	    print "<TD align=\"center\">" .strftime("%Y",$obj->da)."</TD>\n"; 
	    $oldyear = strftime("%Y",$obj->da);
	  }

	  if ($oldmonth == strftime("%Y%b",$obj->da) ) {
	    print '<td align="center">|</td>';
	  } else {
	    print "<TD align=\"center\">" .strftime("%b",$obj->da)."</TD>\n"; 
	    $oldmonth = strftime("%Y%b",$obj->da);
	  }
	  
	  print "<TD>" .strftime("%d",$obj->da)."</TD>\n"; 
	  print "<TD>" .strftime("%H:%M",$obj->da)."</TD>\n";

	  print '<td width="10%">&nbsp;</td>';

	  if ($obj->propalrowid) {
	    print '<td width="40%"><a href="propal.php3?propalid='.$obj->propalrowid.'">'.$obj->libelle.'</a></td>';
	  } else {
	    print '<td width="40%">'.$obj->libelle.'</td>';
	  }
	  /*
	   * Contact pour cette action
	   *
	   */
	  if ($obj->fk_contact) {
	    $contact = new Contact($db);
	    $contact->fetch($obj->fk_contact);
	    print '<td width="40%"><a href="people.php3?socid='.$objsoc->idp.'&contactid='.$contact->id.'">'.$contact->fullname.'</a></td>';
	  } else {
	    print '<td width="40%">&nbsp;</td>';
	  }
	  /*
	   */
	  print '<td width="20%"><a href="../user.php3">'.$obj->code.'</a></td>';
	  print "</tr>\n";
	  $i++;
	}
	print "</table>";

	$db->free();
      } else {
	print $db->error();
      }
      print "</td></tr></table>";    
      /*
       *
       * Notes sur la societe
       *
       */
      print '<table border="1" width="100%" cellspacing="0" bgcolor="#e0e0e0">';
      print "<tr><td>".nl2br($objsoc->note)."</td></tr>";
      print "</table>";
      /*
       *
       *
       *
       */




    }
  } else {
    print $db->error() . "<br>" . $sql;
  }
} else {
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
      print "<TD><a href=\"index.php3?socid=$obj->idp\">$obj->nom</A></td>\n";
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
}
$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
