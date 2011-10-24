<?php
/* Copyright (C) 2007-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007-2010 Jean Heimburger  <jean@tiaris.info>
 * Copyright (C) 2011	   Juanjo Menent    <jmenent@2byte.es>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *   	\file       htdocs/compta/journal/purchasesjournal.php
 *		\ingroup    societe, fournisseur, facture
 *		\brief      Page with purchases journal
 */
require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/report.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");
require_once(DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.facture.class.php");

$langs->load("companies");
$langs->load("other");
$langs->load("compta");

// Protection if external user
if ($user->societe_id > 0)
{
	accessforbidden();
}


/*******************************************************************
 * ACTIONS
 *
 * Put here all code to do according to value of "action" parameter
 ********************************************************************/




/***************************************************
 * PAGE
 *
 * Put here all code to build page
 ****************************************************/

llxHeader('','','');

$html=new Form($db);

$year_current = strftime("%Y",dol_now());
$pastmonth = strftime("%m",dol_now()) - 1;
$pastmonthyear = $year_current;
if ($pastmonth == 0)
{
	$pastmonth = 12;
	$pastmonthyear--;
}

$date_start=dol_mktime(0,0,0,$_REQUEST["date_startmonth"],$_REQUEST["date_startday"],$_REQUEST["date_startyear"]);
$date_end=dol_mktime(23,59,59,$_REQUEST["date_endmonth"],$_REQUEST["date_endday"],$_REQUEST["date_endyear"]);

if (empty($date_start) || empty($date_end)) // We define date_start and date_end
{
	$date_start=dol_get_first_day($pastmonthyear,$pastmonth,false); $date_end=dol_get_last_day($pastmonthyear,$pastmonth,false);
}

$nom=$langs->trans("PurchasesJournal");
//$nomlink=;
$builddate=time();
$description=$langs->trans("DescPurchasesJournal");
$period=$html->select_date($date_start,'date_start',0,0,0,'',1,0,1).' - '.$html->select_date($date_end,'date_end',0,0,0,'',1,0,1);
report_header($nom,$nomlink,$period,$periodlink,$description,$builddate,$exportlink);

$p = explode(":", $conf->global->MAIN_INFO_SOCIETE_PAYS);
$idpays = $p[0];

$sql = "SELECT f.rowid, f.facnumber, f.type, f.datef, f.libelle,";
$sql .= " fd.total_ttc, fd.tva_tx, fd.total_ht, fd.tva as total_tva, fd.product_type,";
$sql .= " s.code_compta_fournisseur, p.accountancy_code_buy , ct.accountancy_code";
$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn_det fd ";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_tva ct ON fd.tva_tx = ct.taux AND ct.fk_pays = '".$idpays."'";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = fd.fk_product ";
$sql .= " JOIN ".MAIN_DB_PREFIX."facture_fourn f ON f.rowid = fd.fk_facture_fourn ";
$sql .= " JOIN ".MAIN_DB_PREFIX."societe s ON s.rowid = f.fk_soc" ;
$sql .= " WHERE f.fk_statut > 0 AND f.entity IN (0,".$conf->entity.")";
if ($date_start && $date_end) $sql .= " AND f.datef >= '".$db->idate($date_start)."' AND f.datef <= '".$db->idate($date_end)."'";

$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);
	// les variables
	$cptfour = (! empty($conf->global->COMPTA_ACCOUNT_SUPPLIER))?$conf->global->COMPTA_ACCOUNT_SUPPLIER:$langs->trans("CodeNotDef");
	$cpttva = (! empty($conf->global->COMPTA_VAT_ACCOUNT))?$conf->global->COMPTA_VAT_ACCOUNT:$langs->trans("CodeNotDef");

	$tabfac = array();
	$tabht = array();
	$tabtva = array();
	$tabttc = array();

	$i=0;
	while ($i < $num)
	{
		$obj = $db->fetch_object($result);
		// contrôles
		$compta_soc = (! empty($obj->code_compta_fournisseur))?$obj->code_compta_fournisseur:$cptfour;
		$compta_prod = $obj->accountancy_code_buy;
		if (empty($compta_prod))
		{
			if($obj->product_type == 0) $compta_prod = (! empty($conf->global->COMPTA_PRODUCT_BUY_ACCOUNT))?$conf->global->COMPTA_PRODUCT_BUY_ACCOUNT:$langs->trans("CodeNotDef");
			else $compta_prod = (! empty($conf->global->COMPTA_SERVICE_BUY_ACCOUNT))?$conf->global->COMPTA_SERVICE_BUY_ACCOUNT:$langs->trans("CodeNotDef");
		}
		$compta_tva = (! empty($obj->accountancy_code))?$obj->accountancy_code:$cpttva;

		$tabfac[$obj->rowid]["date"] = $obj->datef;
		$tabfac[$obj->rowid]["ref"] = $obj->facnumber;
		$tabfac[$obj->rowid]["type"] = $obj->type;
		$tabfac[$obj->rowid]["lib"] = $obj->libelle;
		$tabttc[$obj->rowid][$compta_soc] += $obj->total_ttc;
		$tabht[$obj->rowid][$compta_prod] += $obj->total_ht;
		$tabtva[$obj->rowid][$compta_tva] += $obj->total_tva;

		$i++;
	}
}
else {
	dol_print_error($db);
}

/*
 * Show result array
 */
$i = 0;
print "<table class=\"noborder\" width=\"100%\">";
print "<tr class=\"liste_titre\">";
///print "<td>".$langs->trans("JournalNum")."</td>";
print "<td>".$langs->trans("Date")."</td>";
print "<td>".$langs->trans("Piece").' ('.$langs->trans("InvoiceRef").")</td>";
print "<td>".$langs->trans("Account")."</td>";
print "<t><td>".$langs->trans("Type")."</td><td align='right'>".$langs->trans("Debit")."</td><td align='right'>".$langs->trans("Credit")."</td>";
print "</tr>\n";

$var=true;
$r='';

$invoicestatic=new FactureFournisseur($db);

foreach ($tabfac as $key => $val)
{
	$invoicestatic->id=$key;
	$invoicestatic->ref=$val["ref"];
	$invoicestatic->type=$val["type"];

	print "<tr ".$bc[$var]." >";
	// third party
	//print "<td>".$conf->global->COMPTA_JOURNAL_BUY."</td>";
	print "<td>".$val["date"]."</td>";
	print "<td>".$invoicestatic->getNomUrl(1)."</td>";

	foreach ($tabttc[$key] as $k => $mt)
	{
		print "<td>".$k."</td><td>".$langs->trans("ThirdParty")."</td><td align='right'>".($mt>=0?price($mt):'')."</td><td align='right'>".($mt<0?-price(-$mt):'')."</td>";
	}
	print "</tr>";
	// product
	foreach ($tabht[$key] as $k => $mt)
	{
		if ($mt)
		{
			print "<tr ".$bc[$var]." >";
			//print "<td>".$conf->global->COMPTA_JOURNAL_BUY."</td>";
			print "<td>".$val["date"]."</td>";
			print "<td>".$invoicestatic->getNomUrl(1)."</td>";
			print "<td>".$k."</td><td>".$langs->trans("Products")."</td><td align='right'>".($mt<0?price(-$mt):'')."</td><td align='right'>".($mt>=0?price($mt):'')."</td></tr>";
		}
	}
	// vat
	//var_dump($tabtva);
	foreach ($tabtva[$key] as $k => $mt)
	{
		if ($mt)
		{
			print "<tr ".$bc[$var]." >";
			//print "<td>".$conf->global->COMPTA_JOURNAL_BUY."</td>";
			print "<td>".$val["date"]."</td>";
			print "<td>".$invoicestatic->getNomUrl(1)."</td>";
			print "<td>".$k."</td><td>".$langs->trans("VAT")." ".$key."</td><td align='right'>".($mt<0?price(-$mt):'')."</td><td align='right'>".($mt>=0?price($mt):'')."</td></tr>";
		}
	}

	$var = !$var;
}

print "</table>";


// End of page
$db->close();
llxFooter();
?>