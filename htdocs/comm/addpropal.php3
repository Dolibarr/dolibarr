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
require("./propal.class.php3");

$db = new Db();

$sql = "SELECT s.nom, s.idp, s.prefix_comm FROM societe as s WHERE s.idp = $socidp;";

$result = $db->query($sql);
if ($result) 
{
  if ( $db->num_rows() ) 
    {
      $objsoc = $db->fetch_object(0);
    }
  $db->free();
}



llxHeader();

print_titre("Nouvelle proposition commerciale");


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
    
    print "<form action=\"propal.php3?socidp=$socidp\" method=\"post\">";
    print "<input type=\"hidden\" name=\"action\" value=\"add\">";

    print '<table border="1" cellspacing="0" cellpadding="3" width="100%"><tr><td width="50%" valign="top">';
    
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
    print '<tr><td>Société</td><td><a href="fiche.php3?socid='.$socidp.'">'.$objsoc->nom.'</a></td></tr>';
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
    
    print '<tr><td>Auteur</td><td>'.$user->fullname.'</td></tr>';
    print "<tr><td>Num</td><td><input name=\"ref\" value=\"$numpr\"></td></tr>\n";
    /*
     *
     * Destinataire de la propale
     *
     */
    print "<tr><td>Contact</td><td><select name=\"contactidp\">\n";
    $sql = "SELECT p.idp, p.name, p.firstname, p.poste, p.phone, p.fax, p.email FROM llx_socpeople as p WHERE p.fk_soc = $socidp";
    
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
      print 'Cette societe n\'a pas de contact, veuillez en creer un avant de faire de propale</b><br>';
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
      print 'Cette societe n\'a pas de projet.<br>';
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
    
    print "<tr><td colspan=\"2\">Service/Produits</td></tr>\n";
    print "<tr><td><select name=\"idprod1\">$opt</select></td>";
    print "<td><input type=\"text\" size=\"2\" name=\"qty1\" value=\"1\"></td></tr>\n";

    print "<tr><td><select name=\"idprod2\">$opt</select></td>";
    print "<td><input type=\"text\" size=\"2\" name=\"qty2\" value=\"1\"></td></tr>\n";

    print "<tr><td><select name=\"idprod3\">$opt</select></td>";
    print "<td><input type=\"text\" size=\"2\" name=\"qty3\" value=\"1\"></td></tr>\n";

    print "<tr><td><select name=\"idprod4\">$opt</select></td>";
    print "<td><input type=\"text\" size=\"2\" name=\"qty4\" value=\"1\"></td></tr>\n";

    print "<tr><td align=\"right\" colspan=\"2\">Remise : <input size=\"6\" name=\"remise\" value=\"0\"></td></tr>\n";    
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
 */
$db->close();
llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
