<?PHP
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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
require("./pre.inc.php");
require("./lib.inc.php");


function propals ($db, $year, $month) {
  global $bc;
  $sql = "SELECT s.nom, s.idp, p.rowid as propalid, p.price, p.ref,".$db->pdate("p.datep")." as dp, c.label as statut, c.id as statutid";
  $sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."propal as p, ".MAIN_DB_PREFIX."c_propalst as c WHERE p.fk_soc = s.idp AND p.fk_statut = c.id";
  $sql .= " AND c.id in (1,2,4)";
  $sql .= " AND date_format(p.datep, '%Y') = $year ";
  $sql .= " AND round(date_format(p.datep, '%m')) = $month ";


  $sql .= " ORDER BY p.fk_statut";

  $result = $db->query($sql);
  $num = $db->num_rows();
  $i = 0;
  print "<TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
  print "<TR bgcolor=\"#e0e0e0\"><td colspan=\"3\"><b>Propal</b></td></tr>";

  $oldstatut = -1;
  $subtotal = 0;
  while ($i < $num) {
    $objp = $db->fetch_object( $i);

    if ($objp->statut <> $oldstatut ) {
      $oldstatut = $objp->statut;
      
      if ($i > 0) {
	print "<tr><td align=\"right\" colspan=\"4\">Total : <b>".price($subtotal)."</b></td>\n";
	print "<td align=\"left\">Euros HT</td></tr>\n";
      }
      $subtotal = 0;

      print "<TR bgcolor=\"#e0e0e0\">";
      print "<TD>Societe</td>";
      print "<TD>Réf</TD>";
      print "<TD align=\"right\">Date</TD>";
      print "<TD align=\"right\">Prix</TD>";
      print "<TD align=\"center\">Statut</TD>";
      print "</TR>\n";
      $var=True;
    }
  
    $var=!$var;
    print "<TR $bc[$var]>";
    
    print "<TD><a href=\"comp.php?socidp=$objp->idp\">$objp->nom</a></TD>\n";
    
    print "<TD><a href=\"../../comm/propal.php?propalid=$objp->propalid\">$objp->ref</a></TD>\n";
    
    print "<TD align=\"right\">".strftime("%d %B %Y",$objp->dp)."</TD>\n";
    
    print "<TD align=\"right\">".price($objp->price)."</TD>\n";
    print "<TD align=\"center\">$objp->statut</TD>\n";
    print "</TR>\n";
    
    $total = $total + $objp->price;
    $subtotal = $subtotal + $objp->price;
    
    $i++;
  }
  print "<tr><td align=\"right\" colspan=\"4\">Total : <b>".price($subtotal)."</b></td>\n";
  print "<td align=\"left\">Euros HT</td></tr>\n";
  print "<tr>";
  print "<td colspan=\"3\" align=\"right\"><b>Total : ".price($total)."</b></td>";
  print "<td align=\"left\"><b>Euros HT</b></td></tr>";
  print "</TABLE>";
  $db->free();

}


function factures ($db, $year, $month, $paye) {
  global $bc;

  $sql = "SELECT s.nom, s.idp, f.facnumber, f.total,".$db->pdate("f.datef")." as df, f.paye, f.rowid as facid ";
  $sql .= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture as f";
  $sql .= " WHERE f.fk_statut = 1";
  if ($conf->compta->mode != 'CREANCES-DETTES') { 
	$sql .= " AND f.paye = $paye";
  }
  $sql .= " AND f.fk_soc = s.idp";
  $sql .= " AND date_format(f.datef, '%Y') = $year ";
  $sql .= " AND round(date_format(f.datef, '%m')) = $month ";
  $sql .= " ORDER BY f.datef DESC ";

  $result = $db->query($sql);
  if ($result)
    {
      $num = $db->num_rows();
      if ($num > 0)
	{
	  $i = 0;
	  print "<TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"3\">";
	  print "<TR bgcolor=\"orange\"><td colspan=\"3\"><b>Factures</b></td></tr>";
	  print "<TR bgcolor=\"orange\">";
	  print "<TD>Societe</td>";
	  print "<TD>Num</TD>";
	  print "<TD align=\"right\">Date</TD>";
	  print "<TD align=\"right\">Montant</TD>";
	  print "<TD align=\"right\">Payé</TD>";
	  print "</TR>\n";
	  $var=True;
	  while ($i < $num)
	    {
	      $objp = $db->fetch_object( $i);
	      $var=!$var;
	      print "<TR $bc[$var]>";
	      print "<TD><a href=\"comp.php?socidp=$objp->idp\">$objp->nom</a></TD>\n";
	      print "<TD><a href=\"../facture.php?facid=$objp->facid\">$objp->facnumber</a></TD>\n";
	      if ($objp->df > 0 )
		{
		  print "<TD align=\"right\">".strftime("%d %B %Y",$objp->df)."</TD>\n";
		}
	      else
		{
		  print "<TD align=\"right\"><b>!!!</b></TD>\n";
		}
	      
	      print "<TD align=\"right\">".price($objp->total)."</TD>\n";
	      
	      $payes[1] = "oui";
	      $payes[0] = "<b>non</b>";
	      	      
	      print "<TD align=\"right\">".$payes[$objp->paye]."</TD>\n";
	      print "</TR>\n";
	      
	      $total = $total + $objp->total;
	      
	      $i++;
	    }
	  print "<tr><td colspan=\"4\" align=\"right\">";
	  print "<b>Total : ".price($total)."</b></td><td></td></tr>";
	  print "</TABLE>";
	  $db->free();
	}
    }
  else
    {
      print $db->error();
    }
}


function pt ($db, $sql, $year) {
  global $bc;

  $result = $db->query($sql);
  if ($result) {
    $num = $db->num_rows();
    $i = 0; $total = 0 ;
    print '<table class="border" width="100%" cellspacing="0" cellpadding="3">';
    print "<TR class=\"liste_titre\">";
    print "<TD>Mois</TD>";
    print "<td align=\"right\">Montant</td></tr>\n";
    $var=True;
    $month = 1 ;

    while ($i < $num) {
      $obj = $db->fetch_object( $i);
      $var=!$var;

      if ($obj->dm > $month ) {
	for ($b = $month ; $b < $obj->dm ; $b++) {
	  print "<TR $bc[$var]>";
	  print "<TD>".strftime("%B",mktime(12,0,0,$b, 1, $year))."</TD>\n";
	  print "<TD align=\"right\">0</TD>\n";	  
	  print "</TR>\n";
	  $var=!$var;
	  $ca[$b] = 0;
	}
      }

      if ($obj->sum > 0) {
	print "<TR $bc[$var]>";
	print "<td><a href=\"comp.php?details=1&year=$year&month=$obj->dm\">";
	print strftime("%B",mktime(12,0,0,$obj->dm, 1, $year))."</TD>\n";
	print "<TD align=\"right\">".price($obj->sum)."</TD>\n";
	
	print "</TR>\n";
	$month = $obj->dm + 1;
	$ca[$obj->dm] = $obj->sum;
	$total = $total + $obj->sum;
      }
      $i++;
    }

    if ($num) {
      $beg = $obj->dm;
    } else {
      $beg = 1 ;
    }

    if ($beg <= 12 ) {
      for ($b = $beg + 1 ; $b < 13 ; $b++) {
	$var=!$var;
	print "<TR $bc[$var]>";
	print "<TD>".strftime("%B",mktime(12,0,0,$b, 1, $year))."</TD>\n";
	print "<TD align=\"right\">0</TD>\n";	  
	print "</TR>\n";
	$ca[$b] = 0;
      }
    }

    print "<tr class=\"total\"><td align=\"right\">Total :</td><td align=\"right\"><b>".price($total)."</b></td></tr>";    
    print "</table>";

    $db->free();
    return $ca;
  } else {
    print $db->error();
  }
}

function ppt ($db, $year, $socidp)
{
  global $bc;
  print "<table width=\"100%\">";

  print '<tr><td align="center" valign="top" width="30%">';
  print "CA Prévisionnel basé sur les propal $year";
  
  print "</td><td align=\"center\" valign=\"top\">CA Réalisé $year</td>";
  print "<td align=\"center\" valign=\"top\">Delta $year</td></tr>";
  
  print '<tr><td valign="top" align="center" width="30%">';
  
  $sql = "SELECT sum(f.price) as sum, round(date_format(f.datep,'%m')) as dm";
  $sql .= " FROM ".MAIN_DB_PREFIX."propal as f WHERE fk_statut in (1,2,4) AND date_format(f.datep,'%Y') = $year ";

  if ($socidp)
    {
      $sql .= " AND f.fk_soc = $socidp";
    }

  $sql .= " GROUP BY dm";
  
  $prev = pt($db, $sql, $year);
  
  print "</td><td valign=\"top\" width=\"30%\">";
  
  $sql = "SELECT sum(f.total) as sum, round(date_format(f.datef, '%m')) as dm";
  $sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
  $sql .= " WHERE f.fk_statut = 1";
  if ($conf->compta->mode != 'CREANCES-DETTES') { 
	$sql .= " AND f.paye = 1";
  }
  $sql .= " AND date_format(f.datef,'%Y') = $year ";
  if ($socidp)
    {
      $sql .= " AND f.fk_soc = $socidp";
    }
  $sql .= " GROUP BY dm";
  
  $ca = pt($db, $sql, $year);
  
  print "</td><td valign=\"top\" width=\"30%\">";
  
  print '<table class="border" width="100%" cellspacing="0" cellpadding="3">';
  print "<TR class=\"liste_titre\">";
  print "<TD>Mois</TD>";
  print "<TD align=\"right\">Montant</TD>";
  print "</TR>\n";

  $var = 1 ;
  for ($b = 1 ; $b <= 12 ; $b++)
    {
      $var=!$var;

      $delta = $ca[$b] - $prev[$b];
      $deltat = $deltat + $delta ;
      print "<TR $bc[$var]>";
      print "<TD>".strftime("%B",mktime(12,0,0,$b, 1, $year))."</TD>\n";
      print "<TD align=\"right\">".price($delta)."</TD>\n";	  
      print "</TR>\n";
    }
  
  $ayear = $year - 1;
  $acat = get_ca($db, $ayear, $socidp) - get_ca_propal($db, $ayear, $socidp);


  print "<tr class=\"total\"><td align=\"right\">Total :</td><td align=\"right\">".price($deltat)."</td></tr>";
  print "<tr class=\"total\"><td align=\"right\">Rappel $ayear :</td><td align=\"right\">".price($acat)."</td></tr>";
  print "<tr class=\"total\"><td align=\"right\">Soit :</td><td align=\"right\"><b>".price($acat+$deltat)."</b></td></tr>";

  print "</table>";
  print "</td></tr></table>";

}


/*
 *
 */

llxHeader();

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $socidp = $user->societe_id;
}


$cyear = strftime ("%Y", time());

ppt($db, $cyear, $socidp);

if ($details == 1)
{
  print "<TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\"><tr><td valign=\"top\" width=\"50%\">";
  factures ($db, $year, $month, 1);
  print "</td><td valign=\"top\" width=\"50%\">";
  propals ($db, $year, $month);
  print "</td></tr></table>";
}
$db->close();


llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
