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
require("../lib/Product.class.php3");

require("./propal.class.php3");

$db = new Db();

$sql = "SELECT s.nom, s.idp, s.prefix_comm FROM societe as s WHERE s.idp = $socidp;";

$result = $db->query($sql);
if ($result) {
  if ( $db->num_rows() ) {
    $objsoc = $db->fetch_object(0);
  }
  $db->free();
}
$bc[0]="bgcolor=\"#90c090\"";
$bc[1]="bgcolor=\"#b0e0b0\"";

$yn["t"] = "oui";
$yn["f"] = "non";

llxHeader();

print "<table width=\"100%\">";
print "<tr><td>Propositions commerciales pour <b><a href=\"index.php3?socid=$socidp\">$objsoc->nom</a></b></td>";
print "</tr>";
print "</table>";

if ($action == 'add') {
  $propal = new Propal($socidp);

  $propal->remise = $remise;
  $propal->datep = $db->idate(mktime(12, 1 , 1, $pmonth, $pday, $pyear));

  $propal->contactid = $contactidp;
  $propal->projetidp = $projetidp;

  $propal->author = $user->id;
  $propal->note = $note;

  $propal->ref = $ref;

  $propal->add_product($idprod1);
  $propal->add_product($idprod2);
  $propal->add_product($idprod3);
  $propal->add_product($idprod4);
  
  $sqlok = $propal->create($db);
  
  /*
   *
   *   Generation
   *
   */
  if ($sqlok) {
    print "<hr><b>Génération du PDF</b><p>";

    $command = "export DBI_DSN=\"".$GLOBALS["DBI"]."\" ";
    $command .= " ; ../../scripts/propal-tex.pl --propal=$propalid --pdf --gljroot=" . $GLOBALS["GLJ_ROOT"] ;

    //$command .= " ; ../../scripts/fax-tex.pl --propal=$propalid --gljroot=" . $GLOBALS["GLJ_ROOT"] ;

    $output = system($command);
    print "<p>command : $command<br>";

  } else {
    print $db->error();
  }
}
/*
 *
 * Creation d'une nouvelle propale
 *
 */
if ($action == 'create') {
  if ( $objsoc->prefix_comm ) {

    $numpr = "PR-" . $objsoc->prefix_comm . "-" . strftime("%y%m%d", time());

    $sql = "SELECT count(*) FROM llx_propal WHERE ref like '$numpr%'";

    if ( $db->query($sql) ) {
      $num = $db->result(0, 0);
      $db->free();
      if ($num > 0) {
	$numpr .= "." . ($num + 1);
      }
    }
    
    print "<form action=\"$PHP_SELF?socidp=$socidp\" method=\"post\">";
    print '<table border="0" cellspacing="3"><tr><td valign="top">';
    
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
    print '<table border="0">';
    print "<tr><td>Date</td><td>";
    $cday = date("d", time());
    print "<select name=\"pday\">";    
    for ($day = 1 ; $day < $sday + 32 ; $day++) {
      if ($day == $cday) {
	print "<option value=\"$day\" SELECTED>$day";
      } else {
	print "<option value=\"$day\">$day";
      }
    }
    print "</select>";
    $cmonth = date("n", time());
    print "<select name=\"pmonth\">";    
    for ($month = $smonth ; $month < $smonth + 12 ; $month++) {
      if ($month == $cmonth) {
	print "<option value=\"$month\" SELECTED>" . $strmonth[$month];
      } else {
	print "<option value=\"$month\">" . $strmonth[$month];
      }
    }
    print "</select>";
    
    print "<select name=\"pyear\">";
    
    for ($year = $syear ; $year < $syear + 5 ; $year++) {
      print "<option value=\"$year\">$year";
    }
    print "</select></td></tr>";
    
    print "<input type=\"hidden\" name=\"action\" value=\"add\">";
    $author = $GLOBALS["REMOTE_USER"];
    print "<tr><td>Auteur</td><td><input type=\"hidden\" name=\"author\" value=\"$author\">$author</td></tr>";
    print "<tr><td>Num</td><td><input name=\"ref\" value=\"$numpr\"></td></tr>\n";
    /*
     *
     * Destinataire de la propale
     *
     */
    print "<tr><td>Contact</td><td><select name=\"contactidp\">\n";
    $sql = "SELECT p.idp, p.name, p.firstname, p.poste, p.phone, p.fax, p.email FROM socpeople as p WHERE p.fk_soc = $socidp";
    
    if ( $db->query($sql) ) {
      $i = 0 ;
      $numdest = $db->num_rows(); 
      while ($i < $numdest) {
	$contact = $db->fetch_object( $i);
	print '<option value="'.$contact->idp.'"';
	if ($contact->idp == $setcontact) {
	  print ' SELECTED';
	}
	print '>'.$contact->firstname.' '.$contact->name.' ['.$contact->email.']</option>';
	$i++;
      }
      $db->free();
    } else {
      print $db->error();
    }
    print '</select>';
    if ($numdest==0) {
      print '<br><b>Cette societe n\'a pas de contact, veuillez en creer un avant de faire de propale</b><br>';
      print '<a href=people.php3?socid='.$socidp.'&action=addcontact>Ajouter un contact</a>';
    }
    print '</td></tr>';
    /*
     *
     * Projet associé
     *
     */
    print '<tr><td valign="top">Projet</td><td><select name="projetidp">';
    print '<option value="0"></option>';

    $sql = "SELECT p.rowid, p.title FROM llx_projet as p WHERE p.fk_soc = $socidp";
    
    if ( $db->query($sql) ) {
      $i = 0 ;
      $numprojet = $db->num_rows();
      while ($i < $numprojet) {
	$projet = $db->fetch_object($i);
	print "<option value=\"$projet->rowid\">$projet->title</option>";
	$i++;
      }
      $db->free();
    } else {
      print $db->error();
    }
    print '</select>';
    if ($numprojet==0) {
      print '<br>Cette societe n\'a pas de projet.<br>';
      print '<a href=projet/fiche.php3?socidp='.$socidp.'&action=create>Créer un projet</a>';
    }
    print '</td></tr>';

    print "</table>";  
    /*
     *
     * Liste des elements
     *
     */
    $sql = "SELECT p.rowid,p.label,p.ref,p.price FROM llx_product as p ORDER BY p.ref";
    if ( $db->query($sql) ) {
      $opt = "<option value=\"0\" SELECTED></option>";
      if ($result) {
	$num = $db->num_rows();	$i = 0;	
	while ($i < $num) {
	  $objp = $db->fetch_object( $i);
	  $opt .= "<option value=\"$objp->rowid\">[$objp->ref] $objp->label : $objp->price</option>\n";
	  $i++;
	}
      }
      $db->free();
    } else {
      print $db->error();
    }
    
    print "<table border=1 cellspacing=0>";
    
    print "<tr><td>Service/Produits</td></tr>\n";
    print "<tr><td><select name=\"idprod1\">$opt</select></td></tr>\n";
    print "<tr><td><select name=\"idprod2\">$opt</select></td></tr>\n";
    print "<tr><td><select name=\"idprod3\">$opt</select></td></tr>\n";
    print "<tr><td><select name=\"idprod4\">$opt</select></td></tr>\n";
    print "<tr><td align=\"right\">Remise : <input size=\"6\" name=\"remise\" value=\"0\"></td></tr>\n";    
    print "</table>";
    /*
     * Si il n'y a pas de contact pour la societe on ne permet pas la creation de propale
     */
    if ($numdest > 0) {
      print "<input type=\"submit\" value=\"Enregistrer\">";
    }
    print "</td><td valign=\"top\">";
    print "Commentaires :<br>";
    print "<textarea name=\"note\" wrap=\"soft\" cols=\"30\" rows=\"15\"></textarea>";
    
    print "</td></tr></table>";
    
    print "</form>";
    
    print "<hr noshade>";
  } else {
    print "Vous devez d'abord associer un prefixe commercial a cette societe" ;
  }
}
/*
 *
 * Liste des propales
 *
 */
$sql = "SELECT s.nom,s.idp, p.price, p.ref,".$db->pdate("p.datep")." as dp, p.rowid as propalid, c.id as statut, c.label as lst";
$sql .= " FROM societe as s, llx_propal as p, c_propalst as c ";
$sql .= " WHERE p.fk_soc = s.idp AND p.fk_statut = c.id";
if ($socidp) {
  $sql .= " AND s.idp = $socidp";
}
$sql .= " ORDER BY p.datec DESC ;";

if ( $db->query($sql) ) {
  $num = $db->num_rows();
  $i = 0;
  print "<p><TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
  print "<TR bgcolor=\"orange\">";
  print "<TD><a href=\"$PHP_SELF?sortfield=lower(p.label)&sortorder=ASC\">Societe</a></td>";
  print "<TD>Num</TD>";
  print "<TD>Statut</TD>";
  print "<TD align=\"right\">Date</TD>";
  print "<TD align=\"right\">Prix</TD><td>&nbsp;</td>";
  print "</TR>\n";
  $var=True;
  while ($i < $num) {
    $objp = $db->fetch_object( $i);
    $var=!$var;
    print "<TR $bc[$var]>";
    print "<TD><a href=\"index.php3?socid=$objp->idp\">$objp->nom</a></TD>\n";
    print "<TD><a href=\"propal.php3?propalid=$objp->propalid\">$objp->ref</a></TD>\n";
    print "<TD>$objp->lst</TD>\n";
    
    print "<TD align=\"right\">".strftime("%d %B %Y",$objp->dp)."</TD>\n";
    print "<TD align=\"right\">".price($objp->price)."</TD><td>&nbsp;</td>\n";
    print "</TR>\n";
    
    $total = $total + $objp->price;
    
    $i++;
  }
  print "<tr><td colspan=\"2\" align=\"right\"><b>Total : ".francs($total)." FF</b></td><td colspan=\"3\" align=\"right\"><b>Total : ".price($total)."</b></td><td>euros</td></tr>";
  print "</TABLE>";
  $db->free();
} else {
  print $db->error();
  print "<p>$sql";
}
$db->close();
llxFooter();
?>
