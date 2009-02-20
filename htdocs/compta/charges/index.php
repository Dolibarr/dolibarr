<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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

// Security check
if (!$user->admin && !$user->rights->tax->charges->lire)
  accessforbidden();

$year=$_GET["year"];
$filtre=$_GET["filtre"];
if (! $year) { $year=date("Y", time()); }



/*
 * View
 */

llxHeader('',$langs->trans("TaxAndDividendsArea"));

print_fiche_titre($langs->trans("TaxAndDividendsArea"),($year?"<a href='index.php?year=".($year-1)."'>".img_previous()."</a> ".$langs->trans("Year")." $year <a href='index.php?year=".($year+1)."'>".img_next()."</a>":""));

print "<br>";


// Social contributions

print_titre($langs->trans("SocialContributions"));
print '<table class="noborder" width="100%">';
print "<tr class=\"liste_titre\">";
print "<td>".$langs->trans("Type")."</td>";
print "<td align=\"right\">".$langs->trans("Nb")."</td>";
print "<td align=\"right\">".$langs->trans("Amount")."</td>";
print "<td align=\"right\">".$langs->trans("AlreadyPayed")."</td>";
print "</tr>\n";

$sql = "SELECT c.libelle as lib, s.fk_type as type,";
$sql.=" count(s.rowid) as nb, sum(s.amount) as total, sum(IF(paye=1,s.amount,0)) as totalpaye";
$sql.= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c, ".MAIN_DB_PREFIX."chargesociales as s";
$sql.= " WHERE s.fk_type = c.id";
if ($year > 0)
{
	$sql .= " AND (";
	// Si period renseigné on l'utilise comme critere de date, sinon on prend date échéance,
	// ceci afin d'etre compatible avec les cas ou la période n'etait pas obligatoire
	$sql .= "   (s.periode is not null and date_format(s.periode, '%Y') = $year) ";
	$sql .= "or (s.periode is null     and date_format(s.date_ech, '%Y') = $year)";
	$sql .= ")";
}
$sql .= " GROUP BY c.libelle ASC";

$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;
	$total = 0;
	$totalpaye = 0;
	$var=true;

	while ($i < $num)
	{
		$obj = $db->fetch_object($resql);
		$var = !$var;
		print "<tr $bc[$var]>";
		print '<td><a href="../sociales/index.php?filtre=s.fk_type:'.$obj->type.'">'.$obj->lib.'</a></td>';
		print '<td align="right">'.$obj->nb.'</td>';
		print '<td align="right">'.price($obj->total).'</td>';
		print '<td align="right">'.price($obj->totalpaye).'</td>';
		print '</tr>';
		$total = $total + $obj->total;
		$totalpaye = $totalpaye + $obj->totalpaye;
		$i++;
	}
    print '<tr class="liste_total"><td align="right" colspan="2">'.$langs->trans("Total").'</td>';
    print '<td align="right"><b>'.price($total)."</b></td>";
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
	
	$sql = "SELECT rowid, amount, label, ".$db->pdate("f.datev")." as dm";
	$sql .= " FROM ".MAIN_DB_PREFIX."tva as f ";
	if ($year > 0)
	{
		// Si period renseigné on l'utilise comme critere de date, sinon on prend date échéance,
		// ceci afin d'etre compatible avec les cas ou la période n'etait pas obligatoire
		$sql .= " WHERE date_format(f.datev, '%Y') = ".$year;
	}
	$sql .= " ORDER BY dm DESC";
	
	$result = $db->query($sql);
	if ($result)
	{
	    $num = $db->num_rows($result);
	    $i = 0; 
	    $total = 0 ;
	    print '<table class="noborder" width="100%">';
	    print '<tr class="liste_titre">';
	    print '<td nowrap>'.$langs->trans("Date").'</td>';
	    print "<td>".$langs->trans("Label")."</td>";
	    print '<td align="right">'.$langs->trans("Amount")."</td>";
	    print "</tr>\n";
	    $var=1;
	    while ($i < $num)
	    {
	        $obj = $db->fetch_object($result);
	        $var=!$var;
	        print "<tr $bc[$var]>";
	        print '<td align="left">'.dol_print_date($obj->dm,'day')."</td>\n";
	        print "<td>".$obj->label."</td>\n";
	        $total = $total + $obj->amount;
	        
	        print "<td align=\"right\">".price($obj->amount)."</td>";
	        print "</tr>\n";
	        
	        $i++;
	    }
	    print '<tr class="liste_total"><td align="right" colspan="2">'.$langs->trans("Total").'</td>';
	    print '<td align="right"><b>'.price($total)."</b></td></tr>";
	    
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
