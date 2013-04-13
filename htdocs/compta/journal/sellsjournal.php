<?php
/* Copyright (C) 2007-2010	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2007-2010	Jean Heimburger		<jean@tiaris.info>
 * Copyright (C) 2011		Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2012		Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2012 Alexandre Spangaro	  <alexandre.spangaro@gmail.com>
 * Copyright (C) 2013       Marcos García <marcosgdf@gmail.com>
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
 *   	\file       htdocs/compta/journal/sellsjournal.php
 *		\ingroup    societe, facture
 *		\brief      Page with sells journal
 */
global $mysoc;

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/client.class.php';


$langs->load("companies");
$langs->load("other");
$langs->load("compta");

$date_startmonth=GETPOST('date_startmonth');
$date_startday=GETPOST('date_startday');
$date_startyear=GETPOST('date_startyear');
$date_endmonth=GETPOST('date_endmonth');
$date_endday=GETPOST('date_endday');
$date_endyear=GETPOST('date_endyear');

// Security check
if ($user->societe_id > 0) $socid = $user->societe_id;
if (! empty($conf->comptabilite->enabled)) $result=restrictedArea($user,'compta','','','resultat');
if (! empty($conf->accounting->enabled)) $result=restrictedArea($user,'accounting','','','comptarapport');

/*
 * Actions
 */

// None



/*
 * View
 */

$form=new Form($db);

llxHeader('',$langs->trans("SellsJournal"),'');


$year_current = strftime("%Y",dol_now());
$pastmonth = strftime("%m",dol_now()) - 1;
$pastmonthyear = $year_current;
if ($pastmonth == 0)
{
	$pastmonth = 12;
	$pastmonthyear--;
}

$date_start=dol_mktime(0, 0, 0, $date_startmonth, $date_startday, $date_startyear);
$date_end=dol_mktime(23, 59, 59, $date_endmonth, $date_endday, $date_endyear);

if (empty($date_start) || empty($date_end)) // We define date_start and date_end
{
	$date_start=dol_get_first_day($pastmonthyear,$pastmonth,false); $date_end=dol_get_last_day($pastmonthyear,$pastmonth,false);
}

$nom=$langs->trans("SellsJournal");
$nomlink='';
$periodlink='';
$exportlink='';
$builddate=time();
$description=$langs->trans("DescSellsJournal").'<br>';
if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) $description.= $langs->trans("DepositsAreNotIncluded");
else  $description.= $langs->trans("DepositsAreIncluded");
$period=$form->select_date($date_start,'date_start',0,0,0,'',1,0,1).' - '.$form->select_date($date_end,'date_end',0,0,0,'',1,0,1);
report_header($nom,$nomlink,$period,$periodlink,$description,$builddate,$exportlink);

$p = explode(":", $conf->global->MAIN_INFO_SOCIETE_PAYS);
$idpays = $p[0];

$sql = "SELECT f.rowid, f.facnumber, f.type, f.datef, f.ref_client,";
$sql.= " fd.product_type, fd.total_ht, fd.total_tva, fd.tva_tx, fd.total_ttc, fd.localtax1_tx, fd.localtax2_tx, fd.total_localtax1, fd.total_localtax2,";
$sql.= " s.rowid as socid, s.nom as name, s.code_compta, s.client,";
$sql.= " p.rowid as pid, p.ref as pref, p.accountancy_code_sell,";
$sql.= " ct.accountancy_code_sell as account_tva, ctl1.accountancy_code_sell as account_localtax1, ctl2.accountancy_code_sell as account_localtax2";
$sql.= " FROM ".MAIN_DB_PREFIX."facturedet fd";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = fd.fk_product";
$sql.= " JOIN ".MAIN_DB_PREFIX."facture f ON f.rowid = fd.fk_facture";
$sql.= " JOIN ".MAIN_DB_PREFIX."societe s ON s.rowid = f.fk_soc";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_tva ct ON fd.tva_tx = ct.taux AND ct.fk_pays = '".$idpays."'";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_tva ctl1 ON fd.localtax1_tx = ctl1.localtax1 AND ctl1.fk_pays = '".$idpays."'";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_tva ctl2 ON fd.localtax2_tx = ctl2.localtax2 AND ctl2.fk_pays = '".$idpays."'";
$sql.= " WHERE f.entity = ".$conf->entity;
$sql.= " AND f.fk_statut > 0";
if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) $sql.= " AND f.type IN (0,1,2)";
else $sql.= " AND f.type IN (0,1,2,3)";
$sql.= " AND fd.product_type IN (0,1)";
if ($date_start && $date_end) $sql .= " AND f.datef >= '".$db->idate($date_start)."' AND f.datef <= '".$db->idate($date_end)."'";
$sql.= " ORDER BY f.rowid";

$result = $db->query($sql);
if ($result)
{
	$tabfac = array();
	$tabht = array();
	$tabtva = array();
	$tablocaltax1 = array();
	$tablocaltax2 = array();
	$tabttc = array();
	$tabcompany = array();

	$num = $db->num_rows($result);
   	$i=0;
   	$resligne=array();
   	while ($i < $num)
   	{
   	    $obj = $db->fetch_object($result);
   	    // les variables
   	    $cptcli = (! empty($conf->global->COMPTA_ACCOUNT_CUSTOMER)?$conf->global->COMPTA_ACCOUNT_CUSTOMER:$langs->trans("CodeNotDef"));
   	    $compta_soc = (! empty($obj->code_compta)?$obj->code_compta:$cptcli);
		$compta_prod = $obj->accountancy_code_sell;
		if (empty($compta_prod))
		{
			if($obj->product_type == 0) $compta_prod = (! empty($conf->global->COMPTA_PRODUCT_SOLD_ACCOUNT)?$conf->global->COMPTA_PRODUCT_SOLD_ACCOUNT:$langs->trans("CodeNotDef"));
			else $compta_prod = (! empty($conf->global->COMPTA_SERVICE_SOLD_ACCOUNT)?$conf->global->COMPTA_SERVICE_SOLD_ACCOUNT:$langs->trans("CodeNotDef"));
		}
		$cpttva = (! empty($conf->global->COMPTA_VAT_ACCOUNT)?$conf->global->COMPTA_VAT_ACCOUNT:$langs->trans("CodeNotDef"));
		$compta_tva = (! empty($obj->account_tva)?$obj->account_tva:$cpttva);
		$compta_localtax1 = (! empty($obj->account_localtax1)?$obj->account_localtax1:$langs->trans("CodeNotDef"));
		$compta_localtax2 = (! empty($obj->account_localtax2)?$obj->account_localtax2:$langs->trans("CodeNotDef"));

    	//la ligne facture
   		$tabfac[$obj->rowid]["date"] = $obj->datef;
   		$tabfac[$obj->rowid]["ref"] = $obj->facnumber;
   		$tabfac[$obj->rowid]["type"] = $obj->type;
   		if (! isset($tabttc[$obj->rowid][$compta_soc])) $tabttc[$obj->rowid][$compta_soc]=0;
   		if (! isset($tabht[$obj->rowid][$compta_prod])) $tabht[$obj->rowid][$compta_prod]=0;
   		if (! isset($tabtva[$obj->rowid][$compta_tva])) $tabtva[$obj->rowid][$compta_tva]=0;
   		if (! isset($tablocaltax1[$obj->rowid][$compta_localtax1])) $tablocaltax1[$obj->rowid][$compta_localtax1]=0;
   		if (! isset($tablocaltax2[$obj->rowid][$compta_localtax2])) $tablocaltax2[$obj->rowid][$compta_localtax2]=0;
   		$tabttc[$obj->rowid][$compta_soc] += $obj->total_ttc;
   		$tabht[$obj->rowid][$compta_prod] += $obj->total_ht;
   		$tabtva[$obj->rowid][$compta_tva] += $obj->total_tva;
   		$tablocaltax1[$obj->rowid][$compta_localtax1] += $obj->total_localtax1;
   		$tablocaltax2[$obj->rowid][$compta_localtax2] += $obj->total_localtax2;
   		$tabcompany[$obj->rowid]=array('id'=>$obj->socid, 'name'=>$obj->name, 'client'=>$obj->client);
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
//print "<td>".$langs->trans("JournalNum")."</td>";
print "<td>".$langs->trans("Date")."</td><td>".$langs->trans("Piece").' ('.$langs->trans("InvoiceRef").")</td>";
print "<td>".$langs->trans("Account")."</td>";
print "<td>".$langs->trans("Type")."</td><td align='right'>".$langs->trans("Debit")."</td><td align='right'>".$langs->trans("Credit")."</td>";
print "</tr>\n";

$var=true;
$r='';

$invoicestatic=new Facture($db);
$companystatic=new Client($db);

foreach ($tabfac as $key => $val)
{
	$invoicestatic->id=$key;
	$invoicestatic->ref=$val["ref"];
	$invoicestatic->type=$val["type"];

	print "<tr ".$bc[$var].">";
	// third party
	//print "<td>".$conf->global->COMPTA_JOURNAL_SELL."</td>";
	print "<td>".$val["date"]."</td>";
	print "<td>".$invoicestatic->getNomUrl(1)."</td>";
	foreach ($tabttc[$key] as $k => $mt)
	{
    	$companystatic->id=$tabcompany[$key]['id'];
    	$companystatic->name=$tabcompany[$key]['name'];
    	$companystatic->client=$tabcompany[$key]['client'];
    	print "<td>".$k;
		print "</td><td>".$langs->trans("ThirdParty");
		print ' ('.$companystatic->getNomUrl(0,'customer',16).')';
		print "</td><td align='right'>".($mt>=0?price($mt):'')."</td><td align='right'>".($mt<0?price(-$mt):'')."</td>";
	}
	print "</tr>";
	// product
	foreach ($tabht[$key] as $k => $mt)
	{
		if ($mt)
		{
			print "<tr ".$bc[$var].">";
			//print "<td>".$conf->global->COMPTA_JOURNAL_SELL."</td>";
			print "<td>".$val["date"]."</td>";
			print "<td>".$invoicestatic->getNomUrl(1)."</td>";
			print "<td>".$k;
			print "</td><td>".$langs->trans("Products")."</td><td align='right'>".($mt<0?price(-$mt):'')."</td><td align='right'>".($mt>=0?price($mt):'')."</td></tr>";
		}
	}
	// vat
	foreach ($tabtva[$key] as $k => $mt)
	{
	    if ($mt)
	    {
    		print "<tr ".$bc[$var].">";
    		//print "<td>".$conf->global->COMPTA_JOURNAL_SELL."</td>";
    		print "<td>".$val["date"]."</td>";
    		print "<td>".$invoicestatic->getNomUrl(1)."</td>";
    		print "<td>".$k;
    		print "</td><td>".$langs->trans("VAT")."</td><td align='right'>".($mt<0?price(-$mt):'')."</td><td align='right'>".($mt>=0?price($mt):'')."</td></tr>";
	    }
	}
	// localtax1
	foreach ($tablocaltax1[$key] as $k => $mt)
	{
	    if ($mt)
	    {
    		print "<tr ".$bc[$var].">";
    		//print "<td>".$conf->global->COMPTA_JOURNAL_SELL."</td>";
    		print "<td>".$val["date"]."</td>";
    		print "<td>".$invoicestatic->getNomUrl(1)."</td>";
    		print "<td>".$k;
    		print "</td><td>".$langs->transcountrynoentities("LT1",$mysoc->country_code)."</td><td align='right'>".($mt<0?price(-$mt):'')."</td><td align='right'>".($mt>=0?price($mt):'')."</td></tr>";
	    }
	}
	// localtax2
	foreach ($tablocaltax2[$key] as $k => $mt)
	{
	    if ($mt)
	    {
    		print "<tr ".$bc[$var].">";
    		//print "<td>".$conf->global->COMPTA_JOURNAL_SELL."</td>";
    		print "<td>".$val["date"]."</td>";
    		print "<td>".$invoicestatic->getNomUrl(1)."</td>";
    		print "<td>".$k;
    		print "</td><td>".$langs->transcountrynoentities("LT2",$mysoc->country_code)."</td><td align='right'>".($mt<0?price(-$mt):'')."</td><td align='right'>".($mt>=0?price($mt):'')."</td></tr>";
	    }
	}

	$var = !$var;
}

print "</table>";


// End of page
llxFooter();
$db->close();
?>