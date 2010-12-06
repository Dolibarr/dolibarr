<?php
/* Copyright (C) 2007-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
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

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/report.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/date.lib.php");


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

// Put here content of your page
// ...

$year_current = strftime("%Y",dol_now());
$pastmonth = strftime("%m",dol_now()) - 1;
if ($pastmonth == 0) $pastmonth = 12;

$date_start=dol_mktime(0,0,0,$_REQUEST["date_startmonth"],$_REQUEST["date_startday"],$_REQUEST["date_startyear"]);
$date_end=dol_mktime(23,59,59,$_REQUEST["date_endmonth"],$_REQUEST["date_endday"],$_REQUEST["date_endyear"]);

if (empty($date_start) || empty($date_end)) // We define date_start and date_end
{
	$date_start=dol_get_first_day($year_current,$pastmonth,false); $date_end=dol_get_last_day($year_current,$pastmonth,false);
}

$nom=$langs->trans("SellsJournal");
//$nomlink=;
$builddate=time();
$description=$langs->trans("DescSellsJournal");
$period=$html->select_date($date_start,'date_start',0,0,0,'',1,0,1).' - '.$html->select_date($date_end,'date_end',0,0,0,'',1,0,1);
report_header($nom,$nomlink,$period,$periodlink,$description,$builddate,$exportlink);

$p = explode(":", $conf->global->MAIN_INFO_SOCIETE_PAYS);
$idpays = $p[0];

$sql = "SELECT f.rowid, f.facnumber, f.datef, f.ref_client , fd.product_type, fd.total_ht, fd.total_tva, fd.tva_tx ";
$sql .= " , fd.total_ttc,p.accountancy_code_sell, s.code_compta , ct.accountancy_code";
$sql .= " FROM ".MAIN_DB_PREFIX."facturedet fd ";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = fd.fk_product ";
$sql .= " JOIN ".MAIN_DB_PREFIX."facture f ON f.rowid = fd.fk_facture ";
$sql .= " JOIN ".MAIN_DB_PREFIX."societe s ON s.rowid = f.fk_soc";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_tva ct ON fd.tva_tx = ct.taux AND ct.fk_pays = '".$idpays."'";
$sql .= " WHERE f.fk_statut > 0 AND f.entity IN (0,".$conf->entity.")";
if ($date_start && $date_end) $sql .= " AND f.datef >= '".$db->idate($date_start)."' AND f.datef <= '".$db->idate($date_end)."'";
$sql .= " order by f.rowid";

$result = $db->query($sql);
if ($result)
{
	$tabfac = array();
	$tabht = array();
	$tabtva = array();
	$tabttc = array();

	$num = $db->num_rows($result);
   	$i=0;
   	$resligne=array();
   	while ($i < $num)
   	{
   	    $obj = $db->fetch_object($result);
   	    // les variables
   	    $cptcli = (! empty($conf->global->COMPTA_ACCOUNT_CUSTOMER))?$conf->global->COMPTA_ACCOUNT_CUSTOMER:$langs->trans("CodeNotDef");
   	    $compta_soc = (! empty($obj->code_compta))?$obj->code_compta:$cptcli;
		$compta_prod = $obj->accountancy_code_sell;
		if (empty($compta_prod))
		{
			if($obj->product_type == 0) $compta_prod = (! empty($conf->global->COMPTA_PRODUCT_SOLD_ACCOUNT))?$conf->global->COMPTA_PRODUCT_SOLD_ACCOUNT:$langs->trans("CodeNotDef") ;
			else $compta_prod = (! empty($conf->global->COMPTA_SERVICE_SOLD_ACCOUNT))?$conf->global->COMPTA_SERVICE_SOLD_ACCOUNT:$langs->trans("CodeNotDef") ;
		}
		$cpttva = (! empty($conf->global->COMPTA_VAT_ACCOUNT))?$conf->global->COMPTA_VAT_ACCOUNT:$langs->trans("CodeNotDef");
		$compta_tva = (! empty($obj->accountancy_code))?$obj->accountancy_code:$cpttva;

    	//la ligne facture
   		$tabfac[$obj->rowid]["date"] = $obj->datef;
   		$tabfac[$obj->rowid]["ref"] = $obj->facnumber;
   		$tabfac[$obj->rowid]["piece"] = '';	// todo
   		$tabfac[$obj->rowid]["lib"] = $obj->ref_client;
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
//print "<td>".$langs->trans("JournalNum")."</td>";
print "<td>".$langs->trans("Date")."</td><td>".$langs->trans("InvoiceRef")."</td>";
print "<td>".$langs->trans("Piece")."</td><td>".$langs->trans("Account")."</td>";
print "<t><td>".$langs->trans("Label")."</td><td>".$langs->trans("Debit")."</td><td>".$langs->trans("Credit")."</td>";
print "</tr>\n";

$var=true;
$r='';

foreach ($tabfac as $key => $val)
{
	print "<tr $bc[$var] >";
	//facture
	//print "<td>".$conf->global->COMPTA_JOURNAL_SELL."</td>";
	print "<td>".$val["date"]."</td><td>".$val["ref"]."</td>";
	print "<td>".$val["piece"]."</td>";
	foreach ($tabttc[$key] as $k => $mt)
	{
		print "<td>".$k."</td><td>".$val["lib"]."</td><td>".$mt."</td><td></td>";
	}
	print "</tr>";
	// produit
	foreach ($tabht[$key] as $k => $mt)
	{
		print "<tr>";
		//print "<td>".$conf->global->COMPTA_JOURNAL_SELL."</td>";
		print "<td>".$val["date"]."</td><td>".$val["ref"]."</td>";
		print "<td>".$val["piece"]."</td>";
		print "<td>".$k."</td><td>".$val["lib"]."</td><td></td><td>".$mt."</td></tr>";
	}
	// tva
	foreach ($tabtva[$key] as $k => $mt)
	{
	    if ($mt)
	    {
    		print "<tr>";
    		//print "<td>".$conf->global->COMPTA_JOURNAL_SELL."</td>";
    		print "<td>".$val["date"]."</td><td>".$val["ref"]."</td>";
    		print "<td>".$val["piece"]."</td>";
    		print "<td>".$k."</td><td>".$val["lib"]."</td><td></td><td>".$mt."</td></tr>";
	    }
	}

	$val = !$val;
}

print "</table>";

/***************************************************
* LINKED OBJECT BLOCK
*
* Put here code to view linked object
****************************************************/
/*

$myobject->load_object_linked($myobject->id,$myobject->element);

foreach($myobject->linked_object as $linked_object => $linked_objectid)
{
	if ($conf->$linked_object->enabled)
	{
		$somethingshown=$myobject->showLinkedObjectBlock($linked_object,$linked_objectid,$somethingshown);
	}
}
*/

// End of page
$db->close();
llxFooter('$Date$ - $Revision$');
?>