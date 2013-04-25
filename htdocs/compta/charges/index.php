<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@capnetworks.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/compta/charges/index.php
 *      \ingroup    compta
 *		\brief      Page to list payments of social contributions and vat
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/paymentsocialcontribution.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

$langs->load("compta");
$langs->load("bills");

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'tax', '', '', 'charges');

$year=$_GET["year"];
$filtre=$_GET["filtre"];
if (! $year && $_GET["mode"] != 'sconly') { $year=date("Y", time()); }

$sortfield = isset($_GET["sortfield"])?$_GET["sortfield"]:$_POST["sortfield"];
$sortorder = isset($_GET["sortorder"])?$_GET["sortorder"]:$_POST["sortorder"];
$page = $_GET["page"];
if ($page < 0) $page = 0;

//$limit = $conf->liste_limit;
//$offset = $limit * $page ;

if (! $sortfield) $sortfield="cs.date_ech";
if (! $sortorder) $sortorder="DESC";


/*
 * View
 */

$tva_static = new Tva($db);
$socialcontrib=new ChargeSociales($db);
$payment_sc_static=new PaymentSocialContribution($db);

llxHeader('',$langs->trans("TaxAndDividendsArea"));

$title=$langs->trans("TaxAndDividendsArea");
if ($_GET["mode"] == 'sconly') $title=$langs->trans("SocialContributionsPayments");

print_fiche_titre($title,($year?"<a href='index.php?year=".($year-1)."'>".img_previous()."</a> ".$langs->trans("Year")." $year <a href='index.php?year=".($year+1)."'>".img_next()."</a>":""));

if ($_GET["mode"] != 'sconly')
{
	print $langs->trans("DescTaxAndDividendsArea").'<br>';
	print "<br>";
}


// Social contributions
if ($_GET["mode"] != 'sconly')
{
	print_titre($langs->trans("SocialContributionsPayments"));
}

if ($_GET["mode"] == 'sconly')
{
	$param='&mode=sconly';
}

print '<table class="noborder" width="100%">';
print "<tr class=\"liste_titre\">";
print_liste_field_titre($langs->trans("PeriodEndDate"),$_SERVER["PHP_SELF"],"cs.date_ech","",$param,'width="120"',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("Label"),$_SERVER["PHP_SELF"],"c.libelle","",$param,'',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("Type"),$_SERVER["PHP_SELF"],"cs.fk_type","",$param,'',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("ExpectedToPay"),$_SERVER["PHP_SELF"],"cs.amount","",$param,'align="right"',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("RefPayment"),$_SERVER["PHP_SELF"],"pc.rowid","",$param,'',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("DatePayment"),$_SERVER["PHP_SELF"],"pc.datep","",$param,'align="center"',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("PayedByThisPayment"),$_SERVER["PHP_SELF"],"pc.amount","",$param,'align="right"',$sortfield,$sortorder);
print "</tr>\n";

$sql = "SELECT c.id, c.libelle as lib,";
$sql.= " cs.rowid, cs.libelle, cs.fk_type as type, cs.periode, cs.date_ech, cs.amount as total,";
$sql.= " pc.rowid as pid, pc.datep, pc.amount as totalpaye";
$sql.= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c,";
$sql.= " ".MAIN_DB_PREFIX."chargesociales as cs";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiementcharge as pc ON pc.fk_charge = cs.rowid";
$sql.= " WHERE cs.fk_type = c.id";
$sql.= " AND cs.entity = ".$conf->entity;
if ($year > 0)
{
	$sql .= " AND (";
	// Si period renseignee on l'utilise comme critere de date, sinon on prend date echeance,
	// ceci afin d'etre compatible avec les cas ou la periode n'etait pas obligatoire
	$sql .= "   (cs.periode IS NOT NULL AND cs.periode between '".$db->idate(dol_get_first_day($year))."' AND '".$db->idate(dol_get_last_day($year))."')";
	$sql .= "OR (cs.periode IS NULL AND cs.date_ech between '".$db->idate(dol_get_first_day($year))."' AND '".$db->idate(dol_get_last_day($year))."')";
	$sql .= ")";
}
$sql.= $db->order($sortfield,$sortorder);
//$sql.= $db->plimit($limit+1,$offset);
//print $sql;

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
		// Date
		$date=$obj->periode;
		if (empty($date)) $date=$obj->date_ech;
		print '<td>'.dol_print_date($date,'day').'</td>';
		// Label
		print '<td>';
		$socialcontrib->id=$obj->rowid;
		$socialcontrib->ref=$obj->libelle;
		$socialcontrib->lib=$obj->libelle;
		print $socialcontrib->getNomUrl(1,'20');
		print '</td>';
		// Type
		print '<td><a href="../sociales/index.php?filtre=cs.fk_type:'.$obj->type.'">'.$obj->lib.'</a></td>';
		// Expected to pay
		print '<td align="right">'.price($obj->total).'</td>';
		// Ref payment
		$payment_sc_static->id=$obj->pid;
		$payment_sc_static->ref=$obj->pid;
		print '<td>'.$payment_sc_static->getNomUrl(1)."</td>\n";
		// Date payment
		print '<td align="center">'.dol_print_date($db->jdate($obj->datep),'day').'</td>';
		// Paid
		print '<td align="right">'.price($obj->totalpaye).'</td>';
		print '</tr>';
		$total = $total + $obj->total;
		$totalnb = $totalnb + $obj->nb;
		$totalpaye = $totalpaye + $obj->totalpaye;
		$i++;
	}
    print '<tr class="liste_total"><td align="right" colspan="3">'.$langs->trans("Total").'</td>';
    print '<td align="right">'.price($total)."</td>";
    print '<td align="center">&nbsp;</td>';
    print '<td align="center">&nbsp;</td>';
    print '<td align="right">'.price($totalpaye)."</td>";
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

	$sql = "SELECT f.rowid, f.amount, f.label, f.datev as dm";
	$sql.= " FROM ".MAIN_DB_PREFIX."tva as f ";
	$sql.= " WHERE f.entity = ".$conf->entity;
	if ($year > 0)
	{
		// Si period renseignee on l'utilise comme critere de date, sinon on prend date echeance,
		// ceci afin d'etre compatible avec les cas ou la periode n'etait pas obligatoire
		$sql.= " AND f.datev between '".$db->idate(dol_get_first_day($year,1,false))."' AND '".$db->idate(dol_get_last_day($year,12,false))."'";
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
	    print '<td width="120" class="nowrap">'.$langs->trans("PeriodEndDate").'</td>';
	    print "<td>".$langs->trans("Label")."</td>";
	    print '<td align="right" width="10%">'.$langs->trans("ExpectedToPay")."</td>";
	    print '<td align="right" width="10%">'.$langs->trans("RefPayment")."</td>";
	    print '<td align="center" width="15%">'.$langs->trans("DatePayment")."</td>";
	    print '<td align="right" width="10%">'.$langs->trans("PayedByThisPayment")."</td>";
	    print "</tr>\n";
	    $var=1;
	    while ($i < $num)
	    {
	        $obj = $db->fetch_object($result);

	        $total = $total + $obj->amount;

	        $var=!$var;
	        print "<tr $bc[$var]>";
	        print '<td align="left">'.dol_print_date($db->jdate($obj->dm),'day').' ? </td>'."\n";

	        print "<td>".$obj->label."</td>\n";

	        print '<td align="right">'.price($obj->amount)."</td>";

			$tva_static->id=$obj->rowid;
			$tva_static->ref=$obj->rowid;
	        print '<td align="right">'.$tva_static->getNomUrl(1)."</td>\n";

	        print '<td align="center">'.dol_print_date($db->jdate($obj->dm),'day')."</td>\n";
	        print "<td align=\"right\">".price($obj->amount)."</td>";
	        print "</tr>\n";

	        $i++;
	    }
	    print '<tr class="liste_total"><td align="right" colspan="2">'.$langs->trans("Total").'</td>';
	    print '<td align="right">'.price($total)."</td>";
	    print '<td align="center">&nbsp;</td>';
	    print '<td align="center">&nbsp;</td>';
	    print '<td align="right">'.price($total)."</td>";
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

llxFooter();
?>
