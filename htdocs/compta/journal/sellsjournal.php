<?php
/* Copyright (C) 2007-2010  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2007-2010  Jean Heimburger         <jean@tiaris.info>
 * Copyright (C) 2011-2014  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2012       Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2011-2012  Alexandre Spangaro      <aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2012       Cédric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2013       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2014       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
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

// Load translation files required by the page
$langs->loadLangs(array('companies', 'other', 'compta'));

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

$morequery='&date_startyear='.$date_startyear.'&date_startmonth='.$date_startmonth.'&date_startday='.$date_startday.'&date_endyear='.$date_endyear.'&date_endmonth='.$date_endmonth.'&date_endday='.$date_endday;

llxHeader('',$langs->trans("SellsJournal"),'','',0,0,'','',$morequery);


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
$periodlink='';
$exportlink='';
$builddate=dol_now();
$description=$langs->trans("DescSellsJournal").'<br>';
if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) $description.= $langs->trans("DepositsAreNotIncluded");
else  $description.= $langs->trans("DepositsAreIncluded");
$period=$form->selectDate($date_start, 'date_start', 0, 0, 0, '', 1, 0).' - '.$form->selectDate($date_end, 'date_end', 0, 0, 0, '', 1, 0);
report_header($name,'',$period,$periodlink,$description,$builddate,$exportlink);

$p = explode(":", $conf->global->MAIN_INFO_SOCIETE_COUNTRY);
$idpays = $p[0];

$sql = "SELECT f.rowid, f.ref, f.type, f.datef, f.ref_client,";
$sql.= " fd.product_type, fd.total_ht, fd.total_tva, fd.tva_tx, fd.total_ttc, fd.localtax1_tx, fd.localtax2_tx, fd.total_localtax1, fd.total_localtax2, fd.rowid as id, fd.situation_percent,";
$sql.= " s.rowid as socid, s.nom as name, s.code_compta, s.client,";
$sql.= " p.rowid as pid, p.ref as pref, p.accountancy_code_sell,";
$sql.= " ct.accountancy_code_sell as account_tva, ct.recuperableonly";
$sql.= " FROM ".MAIN_DB_PREFIX."facturedet as fd";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON p.rowid = fd.fk_product";
$sql.= " JOIN ".MAIN_DB_PREFIX."facture as f ON f.rowid = fd.fk_facture";
$sql.= " JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = f.fk_soc";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_tva ct ON fd.tva_tx = ct.taux AND fd.info_bits = ct.recuperableonly AND ct.fk_pays = '".$idpays."'";
$sql.= " WHERE f.entity = ".$conf->entity;
$sql.= " AND f.fk_statut > 0";
if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) {
	$sql.= " AND f.type IN (".Facture::TYPE_STANDARD.",".Facture::TYPE_REPLACEMENT.",".Facture::TYPE_CREDIT_NOTE.",".Facture::TYPE_SITUATION.")";
}
else {
	$sql.= " AND f.type IN (".Facture::TYPE_STANDARD.",".Facture::TYPE_STANDARD.",".Facture::TYPE_CREDIT_NOTE.",".Facture::TYPE_DEPOSIT.",".Facture::TYPE_SITUATION.")";
}

$sql.= " AND fd.product_type IN (0,1)";
if ($date_start && $date_end) $sql .= " AND f.datef >= '".$db->idate($date_start)."' AND f.datef <= '".$db->idate($date_end)."'";
$sql.= " ORDER BY f.rowid";

// TODO Find a better trick to avoid problem with some mysql installations
if (in_array($db->type, array('mysql', 'mysqli'))) $db->query('SET SQL_BIG_SELECTS=1');

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
	$account_localtax1=0;
	$account_localtax2=0;

	$num = $db->num_rows($result);
   	$i=0;
   	$resligne=array();
   	while ($i < $num)
   	{
   	    $obj = $db->fetch_object($result);
   	    // les variables
   	    $cptcli = (! empty($conf->global->ACCOUNTING_ACCOUNT_CUSTOMER)?$conf->global->ACCOUNTING_ACCOUNT_CUSTOMER:$langs->trans("CodeNotDef"));
   	    $compta_soc = (! empty($obj->code_compta)?$obj->code_compta:$cptcli);
		$compta_prod = $obj->accountancy_code_sell;
		if (empty($compta_prod))
		{
			if($obj->product_type == 0) $compta_prod = (! empty($conf->global->ACCOUNTING_PRODUCT_SOLD_ACCOUNT)?$conf->global->ACCOUNTING_PRODUCT_SOLD_ACCOUNT:$langs->trans("CodeNotDef"));
			else $compta_prod = (! empty($conf->global->ACCOUNTING_SERVICE_SOLD_ACCOUNT)?$conf->global->ACCOUNTING_SERVICE_SOLD_ACCOUNT:$langs->trans("CodeNotDef"));
		}
		$cpttva = (! empty($conf->global->ACCOUNTING_VAT_SOLD_ACCOUNT)?$conf->global->ACCOUNTING_VAT_SOLD_ACCOUNT:$langs->trans("CodeNotDef"));
		$compta_tva = (! empty($obj->account_tva)?$obj->account_tva:$cpttva);

		$account_localtax1=getLocalTaxesFromRate($obj->tva_tx, 1, $obj->thirdparty, $mysoc);
		$compta_localtax1= (! empty($account_localtax1[3])?$account_localtax1[3]:$langs->trans("CodeNotDef"));
		$account_localtax2=getLocalTaxesFromRate($obj->tva_tx, 2, $obj->thirdparty, $mysoc);
		$compta_localtax2= (! empty($account_localtax2[3])?$account_localtax2[3]:$langs->trans("CodeNotDef"));

		// Situation invoices handling
		$line = new FactureLigne($db);
		$line->fetch($obj->id);   // id of line
		$prev_progress = 0;
		if ($obj->type==Facture::TYPE_SITUATION) {
			// Avoid divide by 0
			if ($obj->situation_percent == 0) {
				$situation_ratio = 0;
			} else {
		        $prev_progress = $line->get_prev_progress($obj->rowid);   // id on invoice
			    $situation_ratio = ($obj->situation_percent - $prev_progress) / $obj->situation_percent;
			}
		} else {
			$situation_ratio = 1;
		}

    	//la ligne facture
   		$tabfac[$obj->rowid]["date"] = $obj->datef;
   		$tabfac[$obj->rowid]["ref"] = $obj->ref;
   		$tabfac[$obj->rowid]["type"] = $obj->type;
   		if (! isset($tabttc[$obj->rowid][$compta_soc])) $tabttc[$obj->rowid][$compta_soc]=0;
   		if (! isset($tabht[$obj->rowid][$compta_prod])) $tabht[$obj->rowid][$compta_prod]=0;
   		if (! isset($tabtva[$obj->rowid][$compta_tva])) $tabtva[$obj->rowid][$compta_tva]=0;
   		if (! isset($tablocaltax1[$obj->rowid][$compta_localtax1])) $tablocaltax1[$obj->rowid][$compta_localtax1]=0;
   		if (! isset($tablocaltax2[$obj->rowid][$compta_localtax2])) $tablocaltax2[$obj->rowid][$compta_localtax2]=0;
		$tabttc[$obj->rowid][$compta_soc] += $obj->total_ttc * $situation_ratio;
		$tabht[$obj->rowid][$compta_prod] += $obj->total_ht * $situation_ratio;
		if($obj->recuperableonly != 1) $tabtva[$obj->rowid][$compta_tva] += $obj->total_tva * $situation_ratio;
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


print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
//print "<td>".$langs->trans("JournalNum")."</td>";
print '<td>'.$langs->trans('Date').'</td><td>'.$langs->trans('Piece').' ('.$langs->trans('InvoiceRef').')</td>';
print '<td>'.$langs->trans('Account').'</td>';
print '<td>'.$langs->trans('Type').'</td><td align="right">'.$langs->trans('Debit').'</td><td align="right">'.$langs->trans('Credit').'</td>';
print "</tr>\n";


$invoicestatic=new Facture($db);
$companystatic=new Client($db);

foreach ($tabfac as $key => $val)
{
	$invoicestatic->id=$key;
	$invoicestatic->ref=$val["ref"];
	$invoicestatic->type=$val["type"];

	$companystatic->id=$tabcompany[$key]['id'];
	$companystatic->name=$tabcompany[$key]['name'];
	$companystatic->client=$tabcompany[$key]['client'];

	$lines = array(
		array(
			'var' => $tabttc[$key],
			'label' => $langs->trans('ThirdParty').' ('.$companystatic->getNomUrl(0, 'customer', 16).')',
			'nomtcheck' => true,
			'inv' => true
		),
		array(
			'var' => $tabht[$key],
			'label' => $langs->trans('Products'),
		),
		array(
			'var' => $tabtva[$key],
			'label' => $langs->trans('VAT')
		),
		array(
			'var' => $tablocaltax1[$key],
			'label' => $langs->transcountry('LT1', $mysoc->country_code)
		),
		array(
			'var' => $tablocaltax2[$key],
			'label' => $langs->transcountry('LT2', $mysoc->country_code)
		)
	);

	foreach ($lines as $line)
	{
		foreach ($line['var'] as $k => $mt)
		{
			if (isset($line['nomtcheck']) || $mt)
			{
				print '<tr class="oddeven">';
				print "<td>".dol_print_date($db->jdate($val["date"]))."</td>";
				print "<td>".$invoicestatic->getNomUrl(1)."</td>";
				print "<td>".$k."</td><td>".$line['label']."</td>";

				if (isset($line['inv']))
				{
					print '<td align="right">'.($mt>=0?price($mt):'')."</td>";
					print '<td align="right">'.($mt<0?price(-$mt):'')."</td>";
				}
				else
				{
					print '<td align="right">'.($mt<0?price(-$mt):'')."</td>";
	    			print '<td align="right">'.($mt>=0?price($mt):'')."</td>";
				}

				print "</tr>";
			}
		}
	}
}

print "</table>";

// End of page
llxFooter();
$db->close();
