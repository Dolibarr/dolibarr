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

require("../lib/CMailFile.class.php3");
/*
 *  Modules optionnels
 */
require("projet/project.class.php3");
/*
 *
 */

$author = $GLOBALS["REMOTE_USER"];

llxHeader();
print "<table width=\"100%\">";
print "<tr><td>Propositions commerciales</td>";
if ($socidp) {
print "<td align=\"right\"><a href=\"addpropal.php3?socidp=$socidp&action=create\">Nouvelle Propal</a></td>";
}
print "<td align=\"right\"><a href=\"propal.php3\">Liste</a></td>";
print "<td align=\"right\"><a href=\"/compta/prev.php3\">CA Prévisionnel</a></td>";
print "<td align=\"right\"><a href=\"$PHP_SELF?viewstatut=2\">Propal Signées</a></td></tr>";
print "</table>";

$bc[0]="bgcolor=\"#90c090\"";
$bc[1]="bgcolor=\"#b0e0b0\"";

$db = new Db();

if ($sortfield == "") {
  $sortfield="lower(p.label)";
}
if ($sortorder == "") {
  $sortorder="ASC";
}

$yn["t"] = "oui";
$yn["f"] = "non";

if ($page == -1) { $page = 0 ; }
$limit = 26;
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

if ($action == 'setstatut') {
  $sql = "UPDATE llx_propal SET fk_statut = $statut, note = '$note' WHERE rowid = $propalid";
  $result = $db->query($sql);
} elseif ( $action == 'delete' ) {
  $sql = "DELETE FROM llx_propal WHERE rowid = $propalid;";
  if ( $db->query($sql) ) {

    $sql = "DELETE FROM llx_propaldet WHERE fk_propal = $propalid ;";
    if ( $db->query($sql) ) {
      print "<b><font color=\"red\">Propal supprimée</font></b>";
    } else {
      print $db->error();
      print "<p>$sql";
    } 
  } else {
    print $db->error();
    print "<p>$sql";
  }
  $propalid = 0;
  $brouillon = 1;
}

if ($propalid) {
  if ($valid == 1) {
    $sql = "SELECT p.fk_soc, p.fk_projet,p.price, p.ref,".$db->pdate("p.datep")." as dp, p.author";
    $sql .= " FROM llx_propal as p WHERE p.rowid = $propalid";
    
    if ( $db->query($sql) ) {
      $obj = $db->fetch_object( 0 );

      $sql = "UPDATE llx_propal SET fk_statut = 1 WHERE rowid = $propalid;";
      if (! $db->query($sql) ) {
	print $db->error();
      }

    } else {
      print $db->error();
    }
  }
  /*
   *
   */
  $sql = "SELECT s.nom, s.idp, p.price, p.fk_projet,p.remise, p.tva, p.total, p.ref,".$db->pdate("p.datep")." as dp, c.id as statut, c.label as lst, p.author, p.note, x.firstname, x.name, x.fax, x.phone, x.email";
  $sql .= " FROM societe as s, llx_propal as p, c_propalst as c, socpeople as x";
  $sql .= " WHERE p.fk_soc = s.idp AND p.fk_statut = c.id AND x.idp = p.fk_soc_contact AND p.rowid = $propalid";


  /*  $sql = "SELECT s.nom, s.idp, p.price, p.remise, p.tva, p.total, p.ref,".$db->pdate("p.datep")." as dp, c.id as statut, c.label as lst, p.author, p.note, x.firstname, x.name, x.fax, x.phone, x.email";
   *  $sql .= " FROM societe as s, llx_propal as p, c_propalst as c";
   *  $sql .= " WHERE p.fk_soc = s.idp AND p.fk_statut = c.id AND p.rowid = $propalid";
   */

  $result = $db->query($sql);

  if ( $result ) {
    $obj = $db->fetch_object( 0 );
    
    if ($db->num_rows()) {
            
      $color1 = "#e0e0e0";

      print "<table border=\"1\" cellspacing=\"0\" cellpadding=\"2\">";

      print "<tr><td>Société</td><td colspan=\"4\"><a href=\"index.php3?socid=$obj->idp\">$obj->nom</a></td><td align=\"right\"><a href=\"propal.php3?socidp=$obj->idp\">Autres propales</a></td>";
      print "<td valign=\"top\" rowspan=\"8\">Note :<br>". nl2br($obj->note)."</td></tr>";
      //
      if ($obj->fk_projet) {
	$projet = new Project();
	$projet->fetch($db,$obj->fk_projet); 
	print '<tr><td>Projet</td><td colspan="5">';
	print '<a href="projet/fiche.php3?id='.$projet->id.'">';
	print $projet->title.'</a></td></tr>';
      }
      print "<tr><td>Destinataire</td><td colspan=\"5\">$obj->firstname $obj->name &lt;$obj->email&gt;</td></tr>";
      /*
       *
       */
      print "<tr><td>Numéro</td><td colspan=\"2\">$obj->ref</td>";
      print "<td bgcolor=\"$color1\">Montant HT</td><td bgcolor=\"$color1\" align=\"right\">".price($obj->price)."</td>";
      print "<td bgcolor=\"$color1\">euros</td></tr>";
      /*
       *
       */
      print "<tr><td>date</td><td colspan=\"2\" align=\"right\">".strftime("%A %d %B %Y",$obj->dp)."</td>\n";
      print "<td bgcolor=\"$color1\">Remise</td><td bgcolor=\"$color1\" align=\"right\">".price($obj->remise)."</td>";
      print "<td bgcolor=\"$color1\">euros</td></tr>";

      /*
       *
       */
      print "<tr><td>Auteur</td><td colspan=\"2\">$obj->author</td>";

      $totalht = $obj->price - $obj->remise ;

      print "<td bgcolor=\"$color1\">Total HT</td><td bgcolor=\"$color1\" align=\"right\"><b>".price($totalht)."</b></td><td bgcolor=\"$color1\">euros ";
      print "<small>soit ".francs($totalht)." francs</small></td></tr>";
      /*
       *
       */
      print "<tr>";

      print "<td colspan=3>&nbsp;</td>";
      print "<td bgcolor=\"$color1\">TVA</td><td bgcolor=\"$color1\" align=\"right\">".price($obj->tva)."</td><td bgcolor=\"$color1\">euros</td></tr>";

      print "</tr>";
      print "<tr><td colspan=3>&nbsp;</td>";
      print "<td bgcolor=\"$color1\">Total TTC</td><td bgcolor=\"$color1\" align=\"right\">".price($obj->total)."</td><td bgcolor=\"$color1\">euros ";
      print "<small>soit ".francs($obj->total)." francs</small></td></tr>";
      print "</tr>";
      /*
       *
       */
      print "<tr bgcolor=\"#f0f0f0\"><td>Statut :</td><td colspan=2 align=center><b>$obj->lst</b></td>";
      if ($obj->statut == 0) {
	print "<td colspan=3 align=center>[<a href=\"$PHP_SELF?propalid=$propalid&valid=1\">Valider</a>]</td>";
      } elseif ($obj->statut == 1) {
	print "<td colspan=3 align=center>[<a href=\"$PHP_SELF?propalid=$propalid&action=statut\">Changer</a>]</td>";
      } else {
	print "<td colspan=3>&nbsp;</td>";
      }

      print "</table>";

      if ($action == 'statut') {
	print "<form action=\"$PHP_SELF?propalid=$propalid\" method=\"post\">";
	print "<input type=\"hidden\" name=\"action\" value=\"setstatut\">";
	print "<select name=\"statut\">";
	print "<option value=\"2\">Signée";
	print "<option value=\"3\">Non Signée";
	print '</select>';
	print '<br><textarea cols="60" rows="6" wrap="soft" name="note">';
	print $obj->note . "\n--------------------------\n";
	print '</textarea><br><input type="submit" value="Valider">';
	print "</form>";
      }


      print "<table width=\"100%\" cellspacing=2><tr><td valign=\"top\">";
      /*
       * Produits
       */
      $sql = "SELECT p.label as product, p.ref, pt.price";
      $sql .= " FROM llx_propaldet as pt, llx_product as p WHERE pt.fk_product = p.rowid AND pt.fk_propal = $propalid";

      $result = $db->query($sql);
      if ($result) {
	$num = $db->num_rows();
	$i = 0; $total = 0;
	print "<p><b>Produits</b><TABLE border=\"1\" width=\"100%\" cellspacing=\"0\" cellpadding=\"3\">";
	print "<TR bgcolor=\"orange\">";
	print "<td>Réf</td><td>Produit</td>";
	print "<td align=\"right\">Prix</TD><td>&nbsp;</td>";
	print "</TR>\n";

	$var=True;
	while ($i < $num) {
	  $objp = $db->fetch_object( $i);
	  $var=!$var;
	  print "<TR $bc[$var]>";
	  print "<TD>[$objp->ref]</TD>\n";
	  print "<TD>$objp->product</TD>\n";
	  print "<TD align=\"right\">".price($objp->price)."</TD><td>euros</td>\n";
	  print "</tr>";
	  $total = $total + $objp->price;
	  $i++;
	}
	//print "<tr><td align=\"right\" colspan=\"3\">Total : <b>".price($total)."</b></td><td>Euros HT</td></tr>\n";
	print "</table>";
      }
      /*
       *
       */
      print "</td><td valign=\"top\" width=\"50%\">";
      /*
       * Factures associees
       */
      $sql = "SELECT f.facnumber, f.amount,".$db->pdate("f.datef")." as df, f.rowid as facid, f.author, f.paye";
      $sql .= " FROM llx_facture as f, llx_fa_pr as fp WHERE fp.fk_facture = f.rowid AND fp.fk_propal = $propalid";

      $result = $db->query($sql);
      if ($result) {
	$num = $db->num_rows();
	$i = 0; $total = 0;
	print "<p><b>Facture(s) associée(s)</b><TABLE border=\"1\" width=\"100%\" cellspacing=\"0\" cellpadding=\"3\">";
	print "<tr>";
	print "<td>Num</td>";
	print "<td>Date</td>";
	print "<td>Auteur</td>";
	print "<td align=\"right\">Prix</TD>";
	print "</TR>\n";

	$var=True;
	while ($i < $num) {
	  $objp = $db->fetch_object( $i);
	  $var=!$var;
	  print "<TR bgcolor=\"#e0e0e0\">";
	  print "<TD><a href=\"../compta/facture.php3?facid=$objp->facid\">$objp->facnumber</a>";
	  if ($objp->paye) { print " (<b>pay&eacute;e</b>)"; } 
	  print "</TD>\n";
	  print "<TD>".strftime("%d %B %Y",$objp->df)."</TD>\n";
	  print "<TD>$objp->author</TD>\n";
	  print "<TD align=\"right\">$objp->amount</TD>\n";
	  print "</tr>";
	  $total = $total + $objp->amount;
	  $i++;
	}
	print "<tr><td align=\"right\" colspan=\"4\">Total : <b>$total</b> Euros HT</td></tr>\n";
	print "</table>";
	$db->free();
      }
      print "</table>";
      /*
       * Actions
       */
      print "<p><TABLE border=\"1\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\"><tr>";
  
      if ($obj->statut == 0) {
	print "<td bgcolor=\"#e0e0e0\" align=\"center\" width=\"25%\">[<a href=\"$PHP_SELF?propalid=$propalid&action=delete\">Supprimer</a>]</td>";
      } else {
	print "<td align=\"center\" width=\"25%\">-</td>";
      } 
      if ($obj->statut == 2) {
	print "<td bgcolor=\"#e0e0e0\" align=\"center\" width=\"25%\"><a href=\"facture.php3?propalid=$propalid&action=create\">Emettre une facture</td>";
      } else {
	print "<td align=\"center\" width=\"25%\">-</td>";
      }
      if ($obj->statut == 1) {
	$file = $GLOBALS["GLJ_ROOT"] . "/www-sys/doc/propal/$obj->ref/$obj->ref.pdf";
	if (file_exists($file)) {
	  print "<td bgcolor=\"#e0e0e0\" align=\"center\" width=\"25%\">";
	  print "[<a href=\"$PHP_SELF?propalid=$propalid&action=presend\">Envoyer la propale par mail</a>]</td>";
	} else {
	  print "<td bgcolor=\"#e0e0e0\" align=\"center\" width=\"25%\">! Propale non generee !</td>";
	}
      } else {
	print "<td align=\"center\" width=\"25%\">-</td>";
      }
      if ($obj->statut == 0) {
	print "<td bgcolor=\"#e0e0e0\" align=\"center\" width=\"25%\">[<a href=\"$PHP_SELF?propalid=$propalid&valid=1\">Valider</a>]</td>";
      } else {
	print "<td align=\"center\" width=\"25%\">-</td>";
      }
      print "</tr></table>";
      /*
       *
       */
      if ($action == 'fax') {
	print "<hr><b>Génération du fax</b><br>";
	$command = "export DBI_DSN=\"dbi:mysql:dbname=lolixfr:host=espy:user=rodo\" ";
	$command .= " ; ../../scripts/propal-tex.pl --propal=$propalid --pdf --gljroot=" . $GLOBALS["GLJ_ROOT"] ;
	//$command .= " ; ../../scripts/fax-tex.pl --propal=$propalid --gljroot=" . $GLOBALS["GLJ_ROOT"] ;

	print "<p>Resultat :<p>";

	$output = system($command);
	print "<p>command : $command<br>";
      } 
      /*
       * Send
       *
       */
      if ($action == 'send') {
	$file = $GLOBALS["GLJ_ROOT"] . "/www-sys/doc/propal/$obj->ref/$obj->ref.pdf";
	if (file_exists($file)) {

	  $subject = "Notre proposition commerciale $obj->ref";
	  $message = "Veuillez trouver ci-joint notre proposition commerciale $obj->ref\n\nCordialement\n\n";
	  $filepath = $file ;
	  $filename = "$obj->ref.pdf";
	  $mimetype = "application/pdf";

	  $replyto = "$replytoname <$replytomail>";

	  $mailfile = new CMailFile($subject,$sendto,$replyto,$message,$filepath,$mimetype, $filename);

	  if ( $mailfile->sendfile() ) {

	    print "<p>envoy&eacute; &agrave; $sendto";
	    print "<p>envoy&eacute; par ".htmlentities($replyto);
	  } else {
	    print "<b>!! erreur d'envoi";
	  }
	}

	if ( $db->query($sql) ) {
	  $sql = "INSERT INTO actioncomm (datea,fk_action,fk_soc,author,propalrowid,note) VALUES (now(), 3, $obj->idp,'$author', $propalid, 'Envoyée à $sendto');";
	  if (! $db->query($sql) ) {
	    print $db->error();
	    print "<p>$sql</p>";
	  }
	} else {
	  print $db->error();
	}
      }
      /*
       *
       */
      print "<hr>";
      print "<table width=\"100%\" cellspacing=2><tr><td width=\"50%\" valign=\"top\">";
      print "<b>Documents générés</b><br>";
      print "<table width=\"100%\" cellspacing=0 border=1 cellpadding=3>";

      $file = $GLOBALS["GLJ_ROOT"] . "/www-sys/doc/propal/$obj->ref/$obj->ref.pdf";
      if (file_exists($file)) {
	print "<tr><td>Propale PDF</a></td><td><a href=\"../../doc/propal/$obj->ref/$obj->ref.pdf\">$obj->ref.pdf</a></td></tr>";
      }  
      $file = $GLOBALS["GLJ_ROOT"] . "/www-sys/doc/propal/$obj->ref/$obj->ref.ps";
      if (file_exists($file)) {
	print "<tr><td>Propale Postscript</a></td><td><a href=\"../../doc/propal/$obj->ref/$obj->ref.ps\">$obj->ref.ps</a></td>";
	print "</tr>";
      }
      print "<tr><td colspan=\"2\">(<a href=\"../../doc/propal/$obj->ref/\">liste...</a>)</td></tr>";

      $file = $GLOBALS["GLJ_ROOT"] . "/www-sys/doc/propale/$obj->ref/FAX-$obj->ref.ps";  
      if (file_exists($file)) {
	print "<tr><td><a href=\"../../doc/fax/\">FAX d'entete</a></td></tr>";
      }
      print "</table>\n";
      /*
       *
       */
      print "</td><td valign=\"top\" width=\"50%\">";
      print "<b>Propale envoyée</b><br>";
      /*
       *
       */
      $sql = "SELECT ".$db->pdate("a.datea"). " as da, author, note" ;
      $sql .= " FROM actioncomm as a WHERE a.fk_soc = $obj->idp AND a.propalrowid = $propalid ";

      if ( $db->query($sql) ) {
	$num = $db->num_rows();
	$i = 0; $total = 0;
	print "<TABLE border=\"1\" width=\"100%\" cellspacing=\"0\" cellpadding=\"3\">";
	print "<tr><td>Date</td><td>Auteur</td></TR>\n";

	while ($i < $num) {
	  $objp = $db->fetch_object( $i);
	  print "<TR><TD>".strftime("%d %B %Y %H:%M:%S",$objp->da)."</TD>\n";
	  print "<TD>$objp->author</TD></tr>\n";
	  print "<tr><td colspan=\"2\">$objp->note</td></tr>";
	  $i++;
	}
	print "</table>";
	$db->free();
      } else {
	print $db->error();
      }
      /*
       *
       */
      print "</td></tr></table>";
      /*
       *
       *
       */
      if ($action == 'presend') {
	$sendto = "rq@lolix.org";
	$replytoname = "Service commercial Lolix"; $ from_name = $replytoname;
	$replytomail = "commercial@lolix.org"; $from_mail = $replytomail;

	print "<form method=\"post\" action=\"$PHP_SELF?propalid=$propalid&action=send\">\n";
	print "<input type=\"hidden\" name=\"sendto\" value=\"$sendto\">\n";
	print "<input type=\"hidden\" name=\"replytoname\" value=\"$replytoname\">\n";
	print "<input type=\"hidden\" name=\"replytomail\" value=\"$replytomail\">\n";

	print "<p><b>Envoyer la propale par mail</b>";
	print "<table cellspacing=0 border=1 cellpadding=3>";
	print "<tr><td>Destinataire</td><td colspan=\"5\">$obj->firstname $obj->name</td>";
	print "<td><input size=\"30\" name=\"sendto\" value=\"$obj->email\"></td></tr>";
	print "<tr><td>Expediteur</td><td colspan=\"5\">$from_name</td><td>$from_mail</td></tr>";
	print "<tr><td>Reply-to</td><td colspan=\"5\">$replytoname</td>";
	print "<td>$replytomail</td></tr>";

	print "</table>";
	print "<input type=\"submit\" value=\"Envoyer\">";
	print "</form>";
      }

    } else {
      print "Num rows = " . $db->num_rows();
      print "<p><b>$sql";
    }

  } else {
    print $db->error();
    print "<p><b>$sql";
  }


  /*
   *
   *
   *
   */
} else {
  /*
   *
   *
   * Liste des propals
   *
   * 
   */

  print "<P>";
  $sql = "SELECT s.nom, s.idp, p.rowid as propalid, p.price - p.remise as price, p.ref,".$db->pdate("p.datep")." as dp, c.label as statut, c.id as statutid";
  $sql .= " FROM societe as s, llx_propal as p, c_propalst as c WHERE p.fk_soc = s.idp AND p.fk_statut = c.id";

  if ($socidp) { $sql .= " AND s.idp = $socidp"; }

  if ($viewstatut) { $sql .= " AND c.id = $viewstatut"; }

  if ($month > 0) {
    //    $sql .= " AND date_part('month', date(p.datep)) = $month";
    $sql .= " AND date_format(p.datep, '%Y-%m') = '$year-$month'";
  }
  if ($year > 0) {
    //    $sql .= " AND date_part('year', date(p.datep)) = $year";
    $sql .= " AND date_format(p.datep, '%Y') = $year";
  }
  
  $sql .= " ORDER BY p.fk_statut, datep DESC";

  if ( $db->query($sql) ) {
    $num = $db->num_rows();
    $i = 0;
    print "<p><TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";

    $oldstatut = -1;
    $subtotal = 0;
    while ($i < $num) {
      $objp = $db->fetch_object( $i);

      if ($objp->statut <> $oldstatut ) {
	$oldstatut = $objp->statut;

	if ($i > 0) {
	  print "<tr><td align=\"right\" colspan=\"6\">Total : <b>".price($subtotal)."</b></td>\n";
	  print "<td align=\"left\">Euros HT</td></tr>\n";
	}
	$subtotal = 0;
	
	print "<TR bgcolor=\"#e0e0e0\">";
	print "<TD>[<a href=\"$PHP_SELF\">Tous</a>]</td>";
	print "<TD>Réf</TD>";
	print "<TD><a href=\"$PHP_SELF?sortfield=lower(p.label)&sortorder=ASC\">Societe</a></td>";
	print "<TD align=\"right\" colspan=\"2\">Date</TD>";
	print "<TD align=\"right\">Prix</TD>";
	print "<TD align=\"center\">Statut [<a href=\"$PHP_SELF?viewstatut=$objp->statutid\">Filtre</a>]</TD>";
	print "</TR>\n";
	$var=True;
      }
      
      $var=!$var;
      print "<TR $bc[$var]>";
      print "<TD>[<a href=\"$PHP_SELF?socidp=$objp->idp\">Filtre</a>]</TD>\n";
      print "<TD><a href=\"$PHP_SELF?propalid=$objp->propalid\">$objp->ref</a></TD>\n";
      print "<TD><a href=\"index.php3?socid=$objp->idp\">$objp->nom</a></TD>\n";      
      
      $now = time();
      $lim = 3600 * 24 * 15 ;
      
      if ( ($now - $objp->dp) > $lim && $objp->statutid == 1 ) {
	print "<td><b> &gt; 15 jours</b></td>";
      } else {
	print "<td>&nbsp;</td>";
      }
      
      print "<TD align=\"right\">";
      $y = strftime("%Y",$objp->dp);
      $m = strftime("%m",$objp->dp);
      
      print strftime("%d",$objp->dp)."\n";
      print " <a href=\"propal.php3?year=$y&month=$m\">";
      print strftime("%B",$objp->dp)."</a>\n";
      print " <a href=\"propal.php3?year=$y\">";
      print strftime("%Y",$objp->dp)."</a></TD>\n";
      
      //print "<TD align=\"right\">".strftime("%d %B %Y",$objp->dp)."</TD>\n";
      
      print "<TD align=\"right\">".price($objp->price)."</TD>\n";
      print "<TD align=\"center\">$objp->statut</TD>\n";
      print "</TR>\n";
  
      $total = $total + $objp->price;
      $subtotal = $subtotal + $objp->price;
      
      $i++;
    }
    print "<tr><td align=\"right\" colspan=\"6\">Total : <b>".price($subtotal)."</b></td>\n";
    print "<td align=\"left\">Euros HT</td></tr>\n";
    
    
    print "<tr><td></td><td>$i propales</td><td align=\"right\"><small>Soit : ".francs($total)." FF HT</small></td>";
    print "<td colspan=\"3\" align=\"right\"><b>Total : ".price($total)."</b></td>";
    print "<td align=\"left\"><b>Euros HT</b></td></tr>";
    print "</TABLE>";
    $db->free();
  } else {
    print $db->error();
  }
}
$db->close();
llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
