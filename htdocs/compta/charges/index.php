<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2014 Alexandre Spangaro   <aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2011-2014 Juanjo Menent	    <jmenent@2byte.es>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
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
 *		\brief      Page to list payments of special expenses
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/paymentsocialcontribution.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/salaries/class/paymentsalary.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

$langs->load("compta");
$langs->load("bills");

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'tax|salaries', '', '', 'charges|');

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
$sal_static = new PaymentSalary($db);

llxHeader('',$langs->trans("SpecialExpensesArea"));

$title=$langs->trans("SpecialExpensesArea");
if ($_GET["mode"] == 'sconly') $title=$langs->trans("SocialContributionsPayments");

$param='';
if (GETPOST("mode") == 'sconly') $param='&mode=sconly';
if ($sortfield) $param.='&sortfield='.$sortfield;
if ($sortorder) $param.='&sortorder='.$sortorder;

print load_fiche_titre($title, ($year?"<a href='index.php?year=".($year-1).$param."'>".img_previous()."</a> ".$langs->trans("Year")." $year <a href='index.php?year=".($year+1).$param."'>".img_next()."</a>":""), 'title_accountancy.png');

if ($year) $param.='&year='.$year;

if (GETPOST("mode") != 'sconly')
{
	print $langs->trans("DescTaxAndDividendsArea").'<br>';
	print "<br>";
}

// Payment Salary
if ($conf->salaries->enabled)
{
	if (empty($_GET["mode"]) || $_GET["mode"] != 'sconly')
	{
		$sal = new PaymentSalary($db);

		print load_fiche_titre($langs->trans("SalariesPayments").($year?' ('.$langs->trans("Year").' '.$year.')':''), '', '');

		$sql = "SELECT s.rowid, s.amount, s.label, s.datep as datep, s.datev as datev, s.datesp, s.dateep, s.salary, u.salary as current_salary";
		$sql.= " FROM ".MAIN_DB_PREFIX."payment_salary as s, ".MAIN_DB_PREFIX."user as u";
		$sql.= " WHERE s.entity IN (".getEntity('user',1).")";
		$sql.= " AND u.rowid = s.fk_user";
		if ($year > 0)
		{
			$sql.= " AND (s.datesp between '".$db->idate(dol_get_first_day($year,1,false))."' AND '".$db->idate(dol_get_last_day($year,12,false))."'";
			$sql.= " OR s.dateep between '".$db->idate(dol_get_first_day($year,1,false))."' AND '".$db->idate(dol_get_last_day($year,12,false))."')";
		}
		if (preg_match('/^s\./',$sortfield)) $sql.= $db->order($sortfield,$sortorder);

		$result = $db->query($sql);
		if ($result)
		{
		    $num = $db->num_rows($result);
		    $i = 0;
		    $total = 0 ;
		    print '<table class="noborder" width="100%">';
		    print '<tr class="liste_titre">';
			print_liste_field_titre($langs->trans("PeriodEndDate"),$_SERVER["PHP_SELF"],"s.dateep","",$param,'width="140px"',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans("Label"),$_SERVER["PHP_SELF"],"s.label","",$param,'',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans("ExpectedToPay"),$_SERVER["PHP_SELF"],"s.amount","",$param,'align="right"',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans("RefPayment"),$_SERVER["PHP_SELF"],"s.rowid","",$param,'',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans("DatePayment"),$_SERVER["PHP_SELF"],"s.datep","",$param,'align="center"',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans("PayedByThisPayment"),$_SERVER["PHP_SELF"],"s.amount","",$param,'align="right"',$sortfield,$sortorder);
		    print "</tr>\n";
		    $var=1;
		    while ($i < $num)
		    {
		        $obj = $db->fetch_object($result);

		        $total = $total + $obj->amount;

		        $var=!$var;
		        print "<tr ".$bc[$var].">";
		        
		        print '<td align="left">'.dol_print_date($db->jdate($obj->dateep),'day').'</td>'."\n";

		        print "<td>".$obj->label."</td>\n";

		        print '<td align="right">'.($obj->salary?price($obj->salary):'')."</td>";

		        // Ref payment
				$sal_static->id=$obj->rowid;
			    $sal_static->ref=$obj->rowid;
		        print '<td align="left">'.$sal_static->getNomUrl(1)."</td>\n";

		        print '<td align="center">'.dol_print_date($db->jdate($obj->datep),'day')."</td>\n";
		        print '<td align="right">'.price($obj->amount)."</td>";
		        print "</tr>\n";

		        $i++;
		    }
		    print '<tr class="liste_total"><td colspan="2">'.$langs->trans("Total").'</td>';
		    print '<td align="right">'."</td>";
		    print '<td align="center">&nbsp;</td>';
		    print '<td align="center">&nbsp;</td>';
		    print '<td align="right">'.price($total)."</td>";
		    print "</tr>";

		    print "</table>";
		    $db->free($result);

	     	print "<br>";
	  }
		else
		{
		    dol_print_error($db);
		}
	}
}


if ($conf->tax->enabled)
{
	// Social contributions only
	if (GETPOST("mode") != 'sconly')
	{
		print load_fiche_titre($langs->trans("SocialContributionsPayments").($year?' ('.$langs->trans("Year").' '.$year.')':''), '', '');
	}

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("PeriodEndDate"),$_SERVER["PHP_SELF"],"cs.date_ech","",$param,'width="140px"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Label"),$_SERVER["PHP_SELF"],"c.libelle","",$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Type"),$_SERVER["PHP_SELF"],"cs.fk_type","",$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("ExpectedToPay"),$_SERVER["PHP_SELF"],"cs.amount","",$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("RefPayment"),$_SERVER["PHP_SELF"],"pc.rowid","",$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("DatePayment"),$_SERVER["PHP_SELF"],"pc.datep","",$param,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Type"),$_SERVER["PHP_SELF"],"pct.code","",$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("PayedByThisPayment"),$_SERVER["PHP_SELF"],"pc.amount","",$param,'align="right"',$sortfield,$sortorder);
	print "</tr>\n";

	$sql = "SELECT c.id, c.libelle as lib,";
	$sql.= " cs.rowid, cs.libelle, cs.fk_type as type, cs.periode, cs.date_ech, cs.amount as total,";
	$sql.= " pc.rowid as pid, pc.datep, pc.amount as totalpaye, pc.num_paiement as num_payment,";
	$sql.= " pct.code as payment_code";
	$sql.= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c,";
	$sql.= " ".MAIN_DB_PREFIX."chargesociales as cs";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiementcharge as pc ON pc.fk_charge = cs.rowid";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as pct ON pc.fk_typepaiement = pct.id";
	$sql.= " WHERE cs.fk_type = c.id";
	$sql.= " AND cs.entity = ".$conf->entity;
	if ($year > 0)
	{
		$sql .= " AND (";
		// Si period renseignee on l'utilise comme critere de date, sinon on prend date echeance,
		// ceci afin d'etre compatible avec les cas ou la periode n'etait pas obligatoire
		$sql .= "   (cs.periode IS NOT NULL AND cs.periode between '".$db->idate(dol_get_first_day($year))."' AND '".$db->idate(dol_get_last_day($year))."')";
		$sql .= " OR (cs.periode IS NULL AND cs.date_ech between '".$db->idate(dol_get_first_day($year))."' AND '".$db->idate(dol_get_last_day($year))."')";
		$sql .= ")";
	}
	if (preg_match('/^cs\./',$sortfield) || preg_match('/^c\./',$sortfield) || preg_match('/^pc\./',$sortfield) || preg_match('/^pct\./',$sortfield)) $sql.= $db->order($sortfield,$sortorder);
	//$sql.= $db->plimit($limit+1,$offset);
	//print $sql;

	dol_syslog("compta/charges/index.php: select payment", LOG_DEBUG);
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
			print "<tr ".$bc[$var].">";
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
	        // Type payment
    	    print '<td>';
    	    if ($obj->payment_code) print $langs->trans("PaymentTypeShort".$obj->payment_code).' ';
    	    print $obj->num_payment.'</td>';
			// Paid
			print '<td align="right">';
			if ($obj->totalpaye) print price($obj->totalpaye);
			print '</td>';
			print '</tr>';

			$total = $total + $obj->total;
			$totalnb = $totalnb + $obj->nb;
			$totalpaye = $totalpaye + $obj->totalpaye;
			$i++;
		}
	    print '<tr class="liste_total"><td colspan="3" class="liste_total">'.$langs->trans("Total").'</td>';
	    print '<td align="right" class="liste_total">'.price($total)."</td>";
	    print '<td align="center" class="liste_total">&nbsp;</td>';
	    print '<td align="center" class="liste_total">&nbsp;</td>';
	    print '<td align="center" class="liste_total">&nbsp;</td>';
	    print '<td align="right" class="liste_total">'.price($totalpaye)."</td>";
		print "</tr>";
	}
	else
	{
		dol_print_error($db);
	}
	print '</table>';
}

// VAT
if ($conf->tax->enabled)
{
	if (empty($_GET["mode"]) || $_GET["mode"] != 'sconly')
	{
		print "<br>";

		$tva = new Tva($db);

		print load_fiche_titre($langs->trans("VATPayments").($year?' ('.$langs->trans("Year").' '.$year.')':''), '', '');

		$sql = "SELECT pv.rowid, pv.amount, pv.label, pv.datev as dm";
		$sql.= " FROM ".MAIN_DB_PREFIX."tva as pv";
		$sql.= " WHERE pv.entity = ".$conf->entity;
		if ($year > 0)
		{
			// Si period renseignee on l'utilise comme critere de date, sinon on prend date echeance,
			// ceci afin d'etre compatible avec les cas ou la periode n'etait pas obligatoire
			$sql.= " AND pv.datev between '".$db->idate(dol_get_first_day($year,1,false))."' AND '".$db->idate(dol_get_last_day($year,12,false))."'";
		}
		if (preg_match('/^pv\./',$sortfield)) $sql.= $db->order($sortfield,$sortorder);

		$result = $db->query($sql);
		if ($result)
		{
		    $num = $db->num_rows($result);
		    $i = 0;
		    $total = 0 ;
		    print '<table class="noborder" width="100%">';
		    print '<tr class="liste_titre">';
			print_liste_field_titre($langs->trans("PeriodEndDate"),$_SERVER["PHP_SELF"],"pv.datev","",$param,'width="140px"',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans("Label"),$_SERVER["PHP_SELF"],"pv.label","",$param,'',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans("ExpectedToPay"),$_SERVER["PHP_SELF"],"pv.amount","",$param,'align="right"',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans("RefPayment"),$_SERVER["PHP_SELF"],"pv.rowid","",$param,'',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans("DatePayment"),$_SERVER["PHP_SELF"],"pv.datev","",$param,'align="center"',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans("PayedByThisPayment"),$_SERVER["PHP_SELF"],"pv.amount","",$param,'align="right"',$sortfield,$sortorder);
		    print "</tr>\n";
		    $var=1;
		    while ($i < $num)
		    {
		        $obj = $db->fetch_object($result);

		        $total = $total + $obj->amount;

		        $var=!$var;
		        print "<tr ".$bc[$var].">";
		        print '<td align="left">'.dol_print_date($db->jdate($obj->dm),'day').'</td>'."\n";

		        print "<td>".$obj->label."</td>\n";

		        print '<td align="right">'.price($obj->amount)."</td>";

		        // Ref payment
				$tva_static->id=$obj->rowid;
				$tva_static->ref=$obj->rowid;
		        print '<td align="left">'.$tva_static->getNomUrl(1)."</td>\n";

		        print '<td align="center">'.dol_print_date($db->jdate($obj->dm),'day')."</td>\n";
		        print '<td align="right">'.price($obj->amount)."</td>";
		        print "</tr>\n";

		        $i++;
		    }
		    print '<tr class="liste_total"><td colspan="2">'.$langs->trans("Total").'</td>';
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
}
//localtax
if($mysoc->localtax1_assuj=="1" && $mysoc->localtax2_assuj=="1")
{
	$j=1;
	$numlt=3;
}
elseif($mysoc->localtax1_assuj=="1")
{
	$j=1;
	$numlt=2;
}
elseif($mysoc->localtax2_assuj=="1")
{
	$j=2;
	$numlt=3;
}
else
{
	$j=0;
	$numlt=0;
}

while($j<$numlt)
{
	if (empty($_GET["mode"]) || $_GET["mode"] != 'sconly')
	{
		print "<br>";

		$tva = new Tva($db);

		print load_fiche_titre($langs->transcountry(($j==1?"LT1Payments":"LT2Payments"),$mysoc->country_code).($year?' ('.$langs->trans("Year").' '.$year.')':''), '', '');


		$sql = "SELECT pv.rowid, pv.amount, pv.label, pv.datev as dm, pv.datep as dp";
		$sql.= " FROM ".MAIN_DB_PREFIX."localtax as pv";
		$sql.= " WHERE pv.entity = ".$conf->entity." AND localtaxtype = ".$j ;
		if ($year > 0)
		{
			// Si period renseignee on l'utilise comme critere de date, sinon on prend date echeance,
			// ceci afin d'etre compatible avec les cas ou la periode n'etait pas obligatoire
			$sql.= " AND pv.datev between '".$db->idate(dol_get_first_day($year,1,false))."' AND '".$db->idate(dol_get_last_day($year,12,false))."'";
		}
		if (preg_match('/^pv/',$sortfield)) $sql.= $db->order($sortfield,$sortorder);

		$result = $db->query($sql);
		if ($result)
		{
			$num = $db->num_rows($result);
			$i = 0;
			$total = 0 ;
			print '<table class="noborder" width="100%">';
			print '<tr class="liste_titre">';
			print_liste_field_titre($langs->trans("PeriodEndDate"),$_SERVER["PHP_SELF"],"pv.datev","",$param,'width="120"',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans("Label"),$_SERVER["PHP_SELF"],"pv.label","",$param,'',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans("ExpectedToPay"),$_SERVER["PHP_SELF"],"pv.amount","",$param,'align="right"',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans("RefPayment"),$_SERVER["PHP_SELF"],"pv.rowid","",$param,'',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans("DatePayment"),$_SERVER["PHP_SELF"],"pv.datep","",$param,'align="center"',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans("PayedByThisPayment"),$_SERVER["PHP_SELF"],"pv.amount","",$param,'align="right"',$sortfield,$sortorder);
			print "</tr>\n";
			$var=1;
			while ($i < $num)
			{
				$obj = $db->fetch_object($result);

				$total = $total + $obj->amount;

				$var=!$var;
				print "<tr ".$bc[$var].">";
				print '<td align="left">'.dol_print_date($db->jdate($obj->dm),'day').'</td>'."\n";

				print "<td>".$obj->label."</td>\n";

				print '<td align="right">'.price($obj->amount)."</td>";

				// Ref payment
				$tva_static->id=$obj->rowid;
				$tva_static->ref=$obj->rowid;
				print '<td align="left">'.$tva_static->getNomUrl(1)."</td>\n";

				print '<td align="center">'.dol_print_date($db->jdate($obj->dp),'day')."</td>\n";
				print '<td align="right">'.price($obj->amount)."</td>";
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
	$j++;
}


llxFooter();

$db->close();
