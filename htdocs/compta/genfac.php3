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
llxHeader();
$db = new Db();
if ($sortorder == "") {
  $sortfield="lower(s.nom)";
  $sortorder="ASC";
}
$bc[0]="bgcolor=\"#90c090\"";
$bc[1]="bgcolor=\"#b0e0b0\"";

$yn["1"] = "oui";
$yn["0"] = "non";

if ($action == 'valid') {
  $sql = "UPDATE llx_facture set fk_statut = 1 WHERE rowid = $facid ;";
  $result = $db->query( $sql);
}

if ($action == 'payed') {
  $sql = "UPDATE llx_facture set paye = 1 WHERE rowid = $facid ;";
  $result = $db->query( $sql);
}

if ($action == 'delete') {
  $sql = "DELETE FROM llx_facture WHERE rowid = $facid ; DELETE FROM llx_fa_pr WHERE fk_facture = $facid";
  $result = $db->query( $sql);
  $facid = 0 ;
}


if ($facid > 0) {

  $sql = "SELECT s.nom as socnom, s.idp as socidp, f.facnumber, f.amount, int(f.datef) as df, f.paye, f.fk_statut as statut, f.author ";
  $sql .= " FROM societe as s,llx_facture as f WHERE f.fk_soc = s.idp AND f.rowid = $facid";

  $result = $db->query( $sql);
  
  if ($result) {
    $num = $db->num_rows();
    if ($num) {
      $obj = $db->fetch_object( $i);    
    }
    $db->free();
  }
  

  $factex = "\documentclass[a4paper,11pt]{article}

\usepackage[francais]{babel}

\usepackage[dvips]{graphics}

\usepackage{fancyhdr}
%
%

\\newcommand{\aquatre}{
	\setlength{\oddsidemargin}{0.5cm}
  	\setlength{\evensidemargin}{0.5cm}
	\setlength{\\textwidth}{16cm}
  	%\setlength{\\topmargin}{0.5cm}
	%\setlength{\\textheight}{24cm}
  	%\setlength{\headheight}{0cm}
	%\setlength{\headsep}{0cm}
  	\setlength{\parindent}{0.5cm}
	\setlength{\parskip}{0.2cm}
}

%
% Debut du document
%
\aquatre


\\title{Conseil d'administration du 30 juin 2001}

\fancyhead{}
\fancyhead[RO,LE]{Conseil d'administration du 30 juin 2001}
\fancyfoot[C]{Page \\thepage}
\pagestyle{fancy}

\begin{document}

toto

\end{document}";


  $filename = "/tmp/fac.tex";

  $fp = fopen($filename, "w");

  fwrite($fp, $factex);

  fclose($fp);
  print "latex $filename<p>";

  $outp = `cd /tmp ; pdflatex $filename`;

  print "<p>$outp<p>";


} else {
  /*
   * Liste
   *
   */

  function liste($db, $paye) {
    global $bc, $year, $month;
    $sql = "SELECT s.nom, s.idp, f.facnumber, f.amount, int(f.datef) as df, f.paye, f.rowid as facid ";
    $sql .= " FROM societe as s,llx_facture as f WHERE f.fk_soc = s.idp AND f.paye = $paye";
  
    if ($socidp) {
      $sql .= " AND s.idp = $socidp";
    }

    if ($month > 0) {
      $sql .= " AND date_part('month', date(f.datef)) = $month";
    }
    if ($year > 0) {
      $sql .= " AND date_part('year', date(f.datef)) = $year";
    }
    
    $sql .= " ORDER BY f.datef DESC ";
        
    $result = $db->query($sql);
    if ($result) {
      $num = $db->num_rows();
      if ($num > 0) {
	$i = 0;
	print "<p><TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
	print "<TR bgcolor=\"orange\">";
	print "<TD>[<a href=\"$PHP_SELF\">Tous</a>]</td>";
	print "<TD><a href=\"$PHP_SELF?sortfield=lower(p.label)&sortorder=ASC\">Societe</a></td>";
	print "<TD>Num</TD>";
	print "<TD align=\"right\">Date</TD>";
	print "<TD align=\"right\">Montant</TD>";
	print "<TD align=\"right\">Payé</TD>";
	print "<TD align=\"right\">Moyenne</TD>";
	print "</TR>\n";
	$var=True;
	while ($i < $num) {
	  $objp = $db->fetch_object( $i);
	  $var=!$var;
	  print "<TR $bc[$var]>";
	  print "<TD>[<a href=\"$PHP_SELF?socidp=$objp->idp\">Filtre</a>]</TD>\n";
	  print "<TD><a href=\"../comm/index.php?socid=$objp->idp\">$objp->nom</a></TD>\n";
	  
	  
	  print "<td><a href=\"facture.php3?facid=$objp->facid\">$objp->facnumber</a></TD>\n";
	  
	  if ($objp->df > 0 ) {
	    print "<TD align=\"right\">";
	    $y = strftime("%Y",$objp->df);
	    $m = strftime("%m",$objp->df);

	    print strftime("%d",$objp->df)."\n";
	    print " <a href=\"facture.php3?year=$y&month=$m\">";
	    print strftime("%B",$objp->df)."</a>\n";
	    print " <a href=\"facture.php3?year=$y\">";
	    print strftime("%Y",$objp->df)."</a></TD>\n";
	  } else {
	    print "<TD align=\"right\"><b>!!!</b></TD>\n";
	  }
	  
	  print "<TD align=\"right\">$objp->amount</TD>\n";
	  
	  $yn[1] = "oui";
	  $yn[0] = "<b>non</b>";
	  
	  $total = $total + $objp->amount;	  
	  print "<TD align=\"right\">".$yn[$objp->paye]."</TD>\n";
	  print "<TD align=\"right\">".round($total / ($i + 1))."</TD>\n";
	  print "</TR>\n";
	  

	  
	  $i++;
	}
	print "<tr><td></td><td>$i factures</td><td colspan=\"2\" align=\"right\"><b>Total : ".round($total * 6.55957)." FF</b></td>";
	print "<td align=\"right\"><b>Total : $total</b></td><td>euros HT</td>";
	print "<td align=\"right\"><b>Moyenne : ".round($total/ $i)."</b></td></tr>";
	print "</TABLE>";
	$db->free();
      }
    }
  }
  print "<P>";
  liste($db, 0);
  print "<P>";
  liste($db, 1);

}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
