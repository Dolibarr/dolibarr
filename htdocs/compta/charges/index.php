<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       htdocs/compta/charges/index.php
 *      \ingroup    compta
 *		\brief      Page liste des charges
 *		\version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/compta/tva/tva.class.php");
require_once(DOL_DOCUMENT_ROOT."/chargesociales.class.php");

$langs->load("compta");

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'tax', '', '', 'charges');

$year=$_GET["year"];
$filtre=$_GET["filtre"];
if (! $year) { $year=date("Y", time()); }



/*
 * View
 */

llxHeader('',$langs->trans("TaxAndDividendsArea"));

print_fiche_titre($langs->trans("TaxAndDividendsArea"),($year?"<a href='index.php?year=".($year-1)."'>".img_previous()."</a> ".$langs->trans("Year")." $year <a href='index.php?year=".($year+1)."'>".img_next()."</a>":""));

print $langs->trans("DescTaxAndDividendsArea").'<br>';
print "<br>";


// Social contributions

print_titre($langs->trans("SocialContributions"));
print '<table class="noborder" width="100%">';
print "<tr class=\"liste_titre\">";
print '<td width="120">'.$langs->trans("PeriodEndDate").'</td>';
print '<td>'.$langs->trans("Label").'</td>';
print "<td>".$langs->trans("Type")."</td>";
print "<td align=\"right\">".$langs->trans("Amount")."</td>";
print "<td align=\"right\">".$langs->trans("NbOfPayments")."</td>";
print "<td align=\"right\">".$langs->trans("AlreadyPayed")."</td>";
print "</tr>\n";

$sql = "SELECT c.id, c.libelle as lib, s.rowid, s.libelle, s.fk_type as type, s.periode, s.date_ech,";
$sql.= " count(s.rowid) as nb, sum(s.amount) as total, sum(pc.amount) as totalpaye";
$sql.= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c,";
$sql.= " ".MAIN_DB_PREFIX."chargesociales as s";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiementcharge as pc ON pc.fk_charge = s.rowid";
$sql.= " WHERE s.fk_type = c.id";
$sql.= " AND s.entity = ".$conf->entity;
if ($year > 0)
{
	$sql .= " AND (";
	// Si period renseignee on l'utilise comme critere de date, sinon on prend date echeance,
	// ceci afin d'etre compatible avec les cas ou la periode n'etait pas obligatoire
	$sql .= "   (s.periode is not null and date_format(s.periode, '%Y') = $year) ";
	$sql .= "or (s.periode is null     and date_format(s.date_ech, '%Y') = $year)";
	$sql .= ")";
}
$sql.= " GROUP BY c.id, c.libelle, s.rowid, s.fk_type, s.periode, s.date_ech";
$sql.= " ORDER BY c.libelle ASC";

dol_syslog("compta/charges/index.php: select payment sql=".$sql);
$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;
	$total = 0;
	$totalnb = 0;
	$totalpaye = 0;
	$var=true;

	while ($i < $num)
	{
		$obj = $db->fetch_object($resql);
		$var = !$var;
		print "<tr $bc[$var]>";
		$date=$obj->periode;
		if (empty($date)) $date=$obj->date_ech;
		print '<td>'.dol_print_date($date,'day').'</td>';
		print '<td align="left">';
		$socialcontrib=new ChargeSociales($db);
		$socialcontrib->id=$obj->rowid;
		$socialcontrib->lib=$obj->libelle;
		print $socialcontrib->getNomUrl(1,'16');
		print '</td>';
		print '<td><a href="../sociales/index.php?filtre=s.fk_type:'.$obj->type.'">'.$obj->lib.'</a></td>';
		print '<td align="right">'.price($obj->total).'</td>';
		print '<td align="right">'.$obj->nb.'</td>';
		print '<td align="right">'.price($obj->totalpaye).'</td>';
		print '</tr>';
		$total = $total + $obj->total;
		$totalnb = $totalnb + $obj->nb;
		$totalpaye = $totalpaye + $obj->totalpaye;
		$i++;
	}
    print '<tr class="liste_total"><td align="right" colspan="3">'.$langs->trans("Total").'</td>';
    print '<td align="right"><b>'.price($total)."</b></td>";
    print '<td align="right"><b>'.$totalnb.'</b></td>';
    print '<td align="right"><b>'.price($totalpaye)."</b></td>";
	print "</tr>";
}
else
{
	dol_print_error($db);
}
print '</table>';


// VAT

if (empty($_GET["mode"]) || $_GET["mode"] != 'sconly')
{
	print "<br>";

	$tva = new Tva($db);

	print_titre($langs->trans("VATPayments"));

	$sql = "SELECT f.rowid, f.amount, f.label, ".$db->pdate("f.datev")." as dm";
	$sql.= " FROM ".MAIN_DB_PREFIX."tva as f ";
	$sql.= " WHERE f.entity = ".$conf->entity;
	if ($year > 0)
	{
		// Si period renseignee on l'utilise comme critere de date, sinon on prend date echeance,
		// ceci afin d'etre compatible avec les cas ou la periode n'etait pas obligatoire
		$sql.= " AND date_format(f.datev, '%Y') = ".$year;
	}
	$sql.= " ORDER BY dm DESC";

	$result = $db->query($sql);
	if ($result)
	{
	    $num = $db->num_rows($result);
	    $i = 0;
	    $total = 0 ;
	    print '<table class="noborder" width="100%">';
	    print '<tr class="liste_titre">';
	    print '<td width="120" nowrap="nowrap">'.$langs->trans("PeriodEndDate").'</td>';
	    print "<td>".$langs->trans("Label")."</td>";
	    print '<td align="right">'.$langs->trans("Amount")."</td>";
	    print '<td align="center">'.$langs->trans("DatePayment")."</td>";
	    print "<td align=\"right\">".$langs->trans("AlreadyPayed")."</td>";
	    print "</tr>\n";
	    $var=1;
	    while ($i < $num)
	    {
	        $obj = $db->fetch_object($result);
	        $var=!$var;
	        print "<tr $bc[$var]>";
	        print '<td align="left">'.dol_print_date($obj->dm,'day').' ? </td>'."\n";
	        print "<td>".$obj->label."</td>\n";
	        $total = $total + $obj->amount;

	        print "<td align=\"right\">".price($obj->amount)."</td>";
	        print '<td align="center">'.dol_print_date($obj->dm,'day')."</td>\n";
	        print "<td align=\"right\">".price($obj->amount)."</td>";
	        print "</tr>\n";

	        $i++;
	    }
	    print '<tr class="liste_total"><td align="right" colspan="2">'.$langs->trans("Total").'</td>';
	    print '<td align="right"><b>'.price($total)."</b></td>";
	    print '<td>&nbsp;</td>';
	    print '<td align="right"><b>'.price($total)."</b></td>";
	    print "</tr>";

	    print "</table>";
	    $db->free($result);
	}
	else
	{
	    dol_print_error($db);
	}
}


$db->close();

llxFooter('$Date$ - $Revision$');
?>
