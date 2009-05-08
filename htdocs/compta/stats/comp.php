<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 */

/**
 *      \file       htdocs/compta/stats/comp.php
 *      \ingroup    commercial
 *  	\version	$Id$
 */
require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/accountancy.lib.php");

// Security check
if ($user->societe_id > 0)
{
	$socid = $user->societe_id;
}



function propals ($db, $year, $month) 
{
	global $bc,$langs,$conf;
	
	$sql = "SELECT s.nom, s.rowid as socid, p.rowid as propalid, p.price, p.ref,".$db->pdate("p.datep")." as dp, c.label as statut, c.id as statutid";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
	$sql.= ", ".MAIN_DB_PREFIX."propal as p";
	$sql.= ", ".MAIN_DB_PREFIX."c_propalst as c";
	$sql.= " WHERE p.fk_soc = s.rowid";
	$sql.= " AND p.entity = ".$conf->entity;
	$sql.= " AND p.fk_statut = c.id";
	$sql.= " AND c.id in (1,2,4)";
	$sql.= " AND date_format(p.datep, '%Y') = ".$year;
	$sql.= " AND round(date_format(p.datep, '%m')) = ".$month;


	$sql .= " ORDER BY p.fk_statut";

	$result = $db->query($sql);
	$num = $db->num_rows();
	$i = 0;
	print "<table class=\"noborder\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
	print "<tr class=\"liste_titre\"><td colspan=\"5\"><b>Propal</b></td></tr>";

	$oldstatut = -1;
	$subtotal = 0;
	while ($i < $num) {
		$objp = $db->fetch_object($result);

		if ($objp->statut <> $oldstatut ) {
			$oldstatut = $objp->statut;

			if ($i > 0) {
				print "<tr><td align=\"right\" colspan=\"4\">".$langs->trans("Total").": <b>".price($subtotal)."</b></td>\n";
				print "<td align=\"left\">".$langs->trans("Currency".$conf->monnaie)."</td></tr>\n";
			}
			$subtotal = 0;

			print "<tr class=\"liste_titre\">";
			print "<td>Societe</td>";
			print "<td>".$langs->trans("Ref")."</td>";
			print "<td align=\"right\">Date</td>";
			print "<td align=\"right\">".$langs->trans("Price")."</td>";
			print "<td align=\"center\">".$langs->trans("Status")."</td>";
			print "</tr>\n";
			$var=True;
		}

		$var=!$var;
		print "<tr $bc[$var]>";

		print "<td><a href=\"comp.php?socid=".$objp->socid."\">".$objp->nom."</a></td>\n";

		print "<td><a href=\"".DOL_URL_ROOT."/comm/propal.php?propalid=".$objp->propalid."\">".$objp->ref."</a></td>\n";

		print "<td align=\"right\">".dol_print_date($objp->dp)."</td>\n";

		print "<td align=\"right\">".price($objp->price)."</td>\n";
		print "<td align=\"center\">".$objp->statut."</td>\n";
		print "</tr>\n";

		$total = $total + $objp->price;
		$subtotal = $subtotal + $objp->price;

		$i++;
	}
	print "<tr><td align=\"right\" colspan=\"4\">".$langs->trans("Total").": <b>".price($subtotal)."</b></td>\n";
	print "<td align=\"left\">".$langs->trans("Currency".$conf->monnaie)."</td></tr>\n";
	print "<tr>";
	print "<td colspan=\"3\" align=\"right\"><b>".$langs->trans("Total").": ".price($total)."</b></td>";
	print "<td align=\"left\"><b>".$langs->trans("Currency".$conf->monnaie)."</b></td></tr>";
	print "</table>";
	$db->free();

}


function factures ($db, $year, $month, $paye)
{
	global $bc,$conf;

	$sql = "SELECT s.nom, s.rowid as socid, f.facnumber, f.total,".$db->pdate("f.datef")." as df, f.paye, f.rowid as facid ";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
	$sql.= ",".MAIN_DB_PREFIX."facture as f";
	$sql.= " WHERE f.fk_statut = 1";
	$sql.= " AND f.entity = ".$conf->entity;
	if ($conf->compta->mode != 'CREANCES-DETTES')	$sql.= " AND f.paye = ".$paye;
	$sql.= " AND f.fk_soc = s.rowid";
	$sql.= " AND date_format(f.datef, '%Y') = ".$year;
	$sql.= " AND round(date_format(f.datef, '%m')) = ".$month;
	$sql.= " ORDER BY f.datef DESC ";

	$result = $db->query($sql);
	if ($result)
	{
		$num = $db->num_rows();
		if ($num > 0)
		{
	  $i = 0;
	  print "<table class=\"noborder\" width=\"100%\" cellspacing=\"0\" cellpadding=\"3\">";
	  print "<tr class=\"liste_titre\"><td colspan=\"5\"><b>Factures</b></td></tr>";
	  print "<tr class=\"liste_titre\">";
	  print "<td>Societe</td>";
	  print "<td>Num</td>";
	  print "<td align=\"right\">Date</td>";
	  print "<td align=\"right\">Montant</td>";
	  print "<td align=\"right\">Payé</td>";
	  print "</tr>\n";
	  $var=True;
	  while ($i < $num)
	  {
	  	$objp = $db->fetch_object($result);
	  	$var=!$var;
	  	print "<tr $bc[$var]>";
	  	print "<td><a href=\"comp.php?socid=".$objp->socid."\">".$objp->nom."</a></td>\n";
	  	print "<td><a href=\"../facture.php?facid=".$objp->facid."\">".$objp->facnumber."</a></td>\n";
	  	if ($objp->df > 0 )
	  	{
	  		print "<td align=\"right\">".dol_print_date($objp->df)."</td>\n";
	  	}
	  	else
	  	{
	  		print "<td align=\"right\"><b>!!!</b></td>\n";
	  	}
	  	 
	  	print "<td align=\"right\">".price($objp->total)."</td>\n";
	  	 
	  	$payes[1] = "oui";
	  	$payes[0] = "<b>non</b>";

	  	print "<td align=\"right\">".$payes[$objp->paye]."</td>\n";
	  	print "</tr>\n";
	  	 
	  	$total = $total + $objp->total;
	  	 
	  	$i++;
	  }
	  print "<tr><td colspan=\"4\" align=\"right\">";
	  print "<b>Total : ".price($total)."</b></td><td></td></tr>";
	  print "</table>";
	  $db->free();
		}
	}
	else
	{
		print $db->error();
	}
}


function pt ($db, $sql, $year) 
{
	global $bc, $langs;

	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows();
		$i = 0; $total = 0 ;
		print '<table class="border" width="100%" cellspacing="0" cellpadding="3">';
		print "<tr class=\"liste_titre\">";
		print '<td>'.$langs->trans("Month").'</td>';
		print "<td align=\"right\">Montant</td></tr>\n";
		$var=True;
		$month = 1 ;

		while ($i < $num) {
			$obj = $db->fetch_object($result);
			$var=!$var;

			if ($obj->dm > $month ) {
				for ($b = $month ; $b < $obj->dm ; $b++) {
					print "<tr $bc[$var]>";
					print "<td>".dol_print_date(dol_mktime(12,0,0,$b, 1, $year),"%b")."</td>\n";
					print "<td align=\"right\">0</td>\n";
					print "</tr>\n";
					$var=!$var;
					$ca[$b] = 0;
				}
			}

			if ($obj->sum > 0) {
				print "<tr $bc[$var]>";
				print "<td><a href=\"comp.php?details=1&year=$year&month=$obj->dm\">";
				print dol_print_date(dol_mktime(12,0,0,$obj->dm, 1, $year),"%b")."</td>\n";
				print "<td align=\"right\">".price($obj->sum)."</td>\n";

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
			$beg = 0 ;
		}

		if ($beg <= 12 ) {
			for ($b = $beg + 1 ; $b < 13 ; $b++) {
				$var=!$var;
				print "<tr $bc[$var]>";
				print "<td>".dol_print_date(dol_mktime(12,0,0,$b, 1, $year),"%b")."</td>\n";
				print "<td align=\"right\">0</td>\n";
				print "</tr>\n";
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

function ppt ($db, $year, $socid)
{
	global $bc,$conf,$langs;
	print "<table width=\"100%\">";

	print '<tr class="liste_titre"><td align="center" valign="top" width="30%">';
	print "CA Prévisionnel basé sur les propal $year";

	print "</td><td align=\"center\" valign=\"top\">CA Réalisé $year</td>";
	print "<td align=\"center\" valign=\"top\">Delta $year</td></tr>";

	print '<tr><td valign="top" align="center" width="30%">';

	$sql = "SELECT sum(p.price) as sum, round(date_format(p.datep,'%m')) as dm";
	$sql.= " FROM ".MAIN_DB_PREFIX."propal as p";
	$sql.= " WHERE p.fk_statut in (1,2,4)";
	$sql.= " AND p.entity = ".$conf->entity;
	$sql.= " AND date_format(p.datep,'%Y') = ".$year;
	if ($socid)	$sql.= " AND p.fk_soc = ".$socid;
	$sql.= " GROUP BY dm";

	$prev = pt($db, $sql, $year);

	print "</td><td valign=\"top\" width=\"30%\">";

	$sql = "SELECT sum(f.total) as sum, round(date_format(f.datef, '%m')) as dm";
	$sql.= " FROM ".MAIN_DB_PREFIX."facture as f";
	$sql.= " WHERE f.fk_statut in (1,2)";
	$sql.= " AND f.entity = ".$conf->entity;
	if ($conf->compta->mode != 'CREANCES-DETTES')	$sql.= " AND f.paye = 1";
	$sql.= " AND date_format(f.datef,'%Y') = ".$year;
	if ($socid)	$sql.= " AND f.fk_soc = ".$socid;
	$sql.= " GROUP BY dm";

	$ca = pt($db, $sql, $year);

	print "</td><td valign=\"top\" width=\"30%\">";

	print '<table class="border" width="100%" cellspacing="0" cellpadding="3">';
	print "<tr class=\"liste_titre\">";
	print '<td>'.$langs->trans("Month").'</td>';
	print '<td align="right">'.$langs->trans("Amount").'</td>';
	print "</tr>\n";

	$var = 1 ;
	for ($b = 1 ; $b <= 12 ; $b++)
	{
		$var=!$var;

		$delta = $ca[$b] - $prev[$b];
		$deltat = $deltat + $delta ;
		print "<tr $bc[$var]>";
		print "<td>".dol_print_date(dol_mktime(12,0,0,$b, 1, $year),"%b")."</td>\n";
		print "<td align=\"right\">".price($delta)."</td>\n";
		print "</tr>\n";
	}

	$ayear = $year - 1;
	$acat = get_ca($db, $ayear, $socid) - get_ca_propal($db, $ayear, $socid);


	print "<tr class=\"total\"><td align=\"right\">Total :</td><td align=\"right\">".price($deltat)."</td></tr>";
	print "<tr class=\"total\"><td align=\"right\">Rappel $ayear :</td><td align=\"right\">".price($acat)."</td></tr>";
	print "<tr class=\"total\"><td align=\"right\">Soit :</td><td align=\"right\"><b>".price($acat+$deltat)."</b></td></tr>";

	print "</table>";
	print "</td></tr></table>";

}


/*
 * View
 */

llxHeader();


$cyear = isset($_GET["year"])?$_GET["year"]:0;
if (! $cyear) { $cyear = strftime ("%Y", time()); }

print_fiche_titre("Chiffre d'Affaire transformé (prévu-réalisé)",($cyear?"<a href='comp.php?year=".($cyear-1)."'>".img_previous()."</a> Année $cyear <a href='comp.php?year=".($cyear+1)."'>".img_next()."</a>":""));

ppt($db, $cyear, $socid);

if ($details == 1)
{
	print "<TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\"><tr><td valign=\"top\" width=\"50%\">";
	factures ($db, $year, $month, 1);
	print "</td><td valign=\"top\" width=\"50%\">";
	propals ($db, $year, $month);
	print "</td></tr></table>";
}
$db->close();


llxFooter('$Date$ - $Revision$');
?>
