<?PHP
/* Copyright (C) 2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

require("./fichinter.class.php3");

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

llxHeader();
/*
 * Traitements des actions
 *
 *
 */

if ($action == 'valid') {
  $fichinter = new Fichinter($db);
  $fichinter->id = $id;
  $fichinter->valid($user->id);
}

if ($action == 'add') {
  $fichinter = new Fichinter($db);

  $fichinter->date = $db->idate(mktime(12, 1 , 1, $pmonth, $pday, $pyear));

  $fichinter->projetidp = $projetidp;

  $fichinter->author = $user->id;
  $fichinter->note = $note;

  $fichinter->ref = $ref;

  $id = $fichinter->create();
}
/*
 *
 *   Generation
 *
 */
if ($action == 'generate') {
  if ($id) {
    print "<hr><b>Génération du PDF</b><p>";

    //$DBI = "dbi:mysql:dbname=lolixdev:host=espy:user=rodo";

    $gljroot = "/home/www/dolibarr/dolibarr/htdocs";

    $command = 'export DBI_DSN="dbi:'.$conf->db->type.':dbname='.$conf->db->name.':host='.$conf->db->host.'"';

    $command .= " ; ./tex-fichinter.pl --fichinter=".$id ;
    $command .= " --pdf --ps";
    $command .= " --output="    .$conf->fichinter->outputdir;
    $command .= " --templates=" .$conf->fichinter->templatesdir;

    $output = system($command);
    print "<p>command : <b>$command<br>";
    print $output;
  } else {
    print $db->error();
  }
}
/*
 * Mode creation
 * Creation d'une nouvelle propale
 *
 */
if ($action == 'create') {
  if ( $objsoc->prefix_comm ) {

    $numpr = "FI-" . $objsoc->prefix_comm . "-" . strftime("%y%m%d", time());

    $sql = "SELECT count(*) FROM llx_propal WHERE ref like '$numpr%'";

    if ( $db->query($sql) ) {
      $num = $db->result(0, 0);
      $db->free();
      if ($num > 0) {
	$numpr .= "." . ($num + 1);
      }
    }
    
    print "<form action=\"$PHP_SELF?socidp=$socidp\" method=\"post\">";

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
    print '<table border="1" cellspadding="3" cellspacing="0" width="100%">';
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

    print "<tr><td>Numéro</td><td><input name=\"ref\" value=\"$numpr\"></td></tr>\n";

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
      print 'Cette société n\'a pas de projet.&nbsp;';
      print '<a href=projet/fiche.php3?socidp='.$socidp.'&action=create>Créer un projet</a>';
    }
    print '</td></tr>';


    print '<tr><td valign="top">Commentaires</td>';
    print "<td><textarea name=\"note\" wrap=\"soft\" cols=\"60\" rows=\"15\"></textarea>";
    print '</td></tr>';

    print '<tr><td colspan="2" align="center">';
    print "<input type=\"submit\" value=\"Enregistrer\">";
    print '</td></tr>';
    print "</table>";  
    
    print "</form>";
    
    print "<hr noshade>";
  } else {
    print "Vous devez d'abord associer un prefixe commercial a cette societe" ;
  }
}
/*
 * Mode Fiche 
 * Affichage de la fiche d'intervention
 *
 *
 */

if ($id) {

  $fichinter = new Fichinter($db);
  $fichinter->fetch($id);

  print '<table border="1" cellspadding="3" cellspacing="0" width="100%">';
  print '<tr><td width="20%">Date</td><td>'.strftime("%A %d %B %Y",$fichinter->date).'</td></tr>';
    
  print '<tr><td>Numéro</td><td>'.$fichinter->ref.'</td></tr>';

  print '<tr><td valign="top">Projet</td><td>&nbsp;</td></tr>';


  print '<tr><td valign="top">Commentaires</td>';
  print "<td>";
  print nl2br($fichinter->note);
  print '</td></tr>';

  if ($fichinter->statut == 0) {

    print '<tr><td valign="top">Action</td><td><a href="fiche.php3?id='.$id.'&action=valid">Valider</a></td></tr>';

  }

  print '<tr><td valign="top">Action</td><td><a href="fiche.php3?id='.$id.'&action=generate">Génération du pdf</a></td></tr>';


  print '<tr><td>Documents</td><td><a href="'.$conf->fichinter->outputurl.'/'.$fichinter->ref.'">liste...</a>';

  $file = $conf->fichinter->outputdir . "/$fichinter->ref/$fichinter->ref.pdf";
  if (file_exists($file)) {
    
    print '<br>PDF : <a href="'.$conf->fichinter->outputurl.'/'.$fichinter->ref.'/'.$fichinter->ref.'.pdf">'.$fichinter->ref.'.pdf</a>';
  }
  $file = $conf->fichinter->outputdir . "/$fichinter->ref/$fichinter->ref.ps";
  if (file_exists($file)) {
    
    print '<br>PS : <a href="'.$conf->fichinter->outputurl.'/'.$fichinter->ref.'/'.$fichinter->ref.'.ps">'.$fichinter->ref.'.ps</a><br>';
  }

  print '</td></tr>';

  print "</table>";  
    
}

/*
 *
 * Liste des fiches
 *
 */
$sql = "SELECT s.nom,s.idp, f.ref,".$db->pdate("f.datei")." as dp, f.rowid as fichid, f.fk_statut";
$sql .= " FROM societe as s, llx_fichinter as f ";
$sql .= " WHERE f.fk_soc = s.idp ";
$sql .= " ORDER BY f.datei DESC ;";

if ( $db->query($sql) ) {
  $num = $db->num_rows();
  $i = 0;
  print "<p><TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
  print "<TR bgcolor=\"orange\">";
  print "<TD>Num</TD>";
  print "<TD><a href=\"$PHP_SELF?sortfield=lower(p.label)&sortorder=ASC\">Societe</a></td>";
  print "<TD>Date</TD>";
  print "<TD>Statut</TD>";
  print "</TR>\n";
  $var=True;
  while ($i < $num) {
    $objp = $db->fetch_object( $i);
    $var=!$var;
    print "<TR $bc[$var]>";
    print "<TD><a href=\"fiche.php3?id=$objp->fichid\">$objp->ref</a></TD>\n";
    print "<TD><a href=\"../comm/index.php3?socid=$objp->idp\">$objp->nom</a></TD>\n";
    print "<TD>".strftime("%d %B %Y",$objp->dp)."</TD>\n";
    print "<TD>$objp->fk_statut</TD>\n";
    
    print "</TR>\n";
    
    $i++;
  }

  print "</TABLE>";
  $db->free();
} else {
  print $db->error();
  print "<p>$sql";
}
$db->close();
llxFooter();
?>
