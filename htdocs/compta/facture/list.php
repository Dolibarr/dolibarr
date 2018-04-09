<?php
/* Copyright (C) 2002-2006 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Eric Seigne           <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2016 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2015 Regis Houssin         <regis.houssin@capnetworks.com>
 * Copyright (C) 2006      Andre Cianfarani      <acianfa@free.fr>
 * Copyright (C) 2010-2012 Juanjo Menent         <jmenent@2byte.es>
 * Copyright (C) 2012      Christophe Battarel   <christophe.battarel@altairis.fr>
 * Copyright (C) 2013      Florian Henry         <florian.henry@open-concept.pro>
 * Copyright (C) 2013      Cédric Salvador       <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015      Jean-François Ferry   <jfefe@aternatik.fr>
 * Copyright (C) 2015-2016 Ferran Marcet         <fmarcet@2byte.es>
 * Copyright (C) 2017      Josep Lluís Amador    <joseplluis@lliuretic.cat>
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
 *	\file       htdocs/compta/facture/list.php
 *	\ingroup    facture
 *	\brief      List of customer invoices
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
if (! empty($conf->commande->enabled)) require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';


$langs->load('bills');
$langs->load('companies');
$langs->load('products');

$sall=trim((GETPOST('search_all', 'alphanohtml')!='')?GETPOST('search_all', 'alphanohtml'):GETPOST('sall', 'alphanohtml'));
$projectid=(GETPOST('projectid')?GETPOST('projectid','int'):0);

$id=(GETPOST('id','int')?GETPOST('id','int'):GETPOST('facid','int'));  // For backward compatibility
$ref=GETPOST('ref','alpha');
$socid=GETPOST('socid','int');

$action=GETPOST('action','alpha');
$massaction=GETPOST('massaction','alpha');
$show_files=GETPOST('show_files','int');
$confirm=GETPOST('confirm','alpha');
$toselect = GETPOST('toselect', 'array');
$contextpage=GETPOST('contextpage','aZ')?GETPOST('contextpage','aZ'):'invoicelist';

$lineid=GETPOST('lineid','int');
$userid=GETPOST('userid','int');
$search_product_category=GETPOST('search_product_category','int');
$search_ref=GETPOST('sf_ref')?GETPOST('sf_ref','alpha'):GETPOST('search_ref','alpha');
$search_refcustomer=GETPOST('search_refcustomer','alpha');
$search_type=GETPOST('search_type','int');
$search_project=GETPOST('search_project','alpha');
$search_societe=GETPOST('search_societe','alpha');
$search_montant_ht=GETPOST('search_montant_ht','alpha');
$search_montant_vat=GETPOST('search_montant_vat','alpha');
$search_montant_localtax1=GETPOST('search_montant_localtax1','alpha');
$search_montant_localtax2=GETPOST('search_montant_localtax2','alpha');
$search_montant_ttc=GETPOST('search_montant_ttc','alpha');
$search_status=GETPOST('search_status','int');
$search_paymentmode=GETPOST('search_paymentmode','int');
$search_town=GETPOST('search_town','alpha');
$search_zip=GETPOST('search_zip','alpha');
$search_state=trim(GETPOST("search_state"));
$search_country=GETPOST("search_country",'int');
$search_type_thirdparty=GETPOST("search_type_thirdparty",'int');
$search_user = GETPOST('search_user','int');
$search_sale = GETPOST('search_sale','int');
$search_day	= GETPOST('search_day','int');
$search_month	= GETPOST('search_month','int');
$search_year	= GETPOST('search_year','int');
$search_day_lim	= GETPOST('search_day_lim','int');
$search_month_lim	= GETPOST('search_month_lim','int');
$search_year_lim	= GETPOST('search_year_lim','int');

$option = GETPOST('search_option');
if ($option == 'late') {
	$search_status = '1';
}
$filtre	= GETPOST('filtre','alpha');

$limit = GETPOST('limit')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
if (! $sortorder && ! empty($conf->global->INVOICE_DEFAULT_UNPAYED_SORT_ORDER) && $search_status == 1) $sortorder=$conf->global->INVOICE_DEFAULT_UNPAYED_SORT_ORDER;
if (! $sortorder) $sortorder='DESC';
if (! $sortfield) $sortfield='f.datef';
$pageprev = $page - 1;
$pagenext = $page + 1;

// Security check
$fieldid = (! empty($ref)?'facnumber':'rowid');
if (! empty($user->societe_id)) $socid=$user->societe_id;
$result = restrictedArea($user, 'facture', $id,'','','fk_soc',$fieldid);

$diroutputmassaction=$conf->facture->dir_output . '/temp/massgeneration/'.$user->id;

$object=new Facture($db);

$now=dol_now();

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$object = new Facture($db);
$hookmanager->initHooks(array('invoicelist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('facture');
$search_array_options=$extrafields->getOptionalsFromPost($extralabels,'','search_');

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	'f.facnumber'=>'Ref',
	'f.ref_client'=>'RefCustomer',
	'pd.description'=>'Description',
	's.nom'=>"ThirdParty",
	'f.note_public'=>'NotePublic',
);
if (empty($user->socid)) $fieldstosearchall["f.note_private"]="NotePrivate";

$checkedtypetiers=0;
$arrayfields=array(
	'f.facnumber'=>array('label'=>$langs->trans("Ref"), 'checked'=>1),
	'f.ref_client'=>array('label'=>$langs->trans("RefCustomer"), 'checked'=>1),
	'f.type'=>array('label'=>$langs->trans("Type"), 'checked'=>0),
	'f.date'=>array('label'=>$langs->trans("DateInvoice"), 'checked'=>1),
	'f.date_lim_reglement'=>array('label'=>$langs->trans("DateDue"), 'checked'=>1),
	'p.ref'=>array('label'=>$langs->trans("ProjectRef"), 'checked'=>0, 'enabled'=>(empty($conf->projet->enabled)?0:1)),
	's.nom'=>array('label'=>$langs->trans("ThirdParty"), 'checked'=>1),
	's.town'=>array('label'=>$langs->trans("Town"), 'checked'=>1),
	's.zip'=>array('label'=>$langs->trans("Zip"), 'checked'=>1),
	'state.nom'=>array('label'=>$langs->trans("StateShort"), 'checked'=>0),
	'country.code_iso'=>array('label'=>$langs->trans("Country"), 'checked'=>0),
	'typent.code'=>array('label'=>$langs->trans("ThirdPartyType"), 'checked'=>$checkedtypetiers),
	'f.fk_mode_reglement'=>array('label'=>$langs->trans("PaymentMode"), 'checked'=>1),
	'f.total_ht'=>array('label'=>$langs->trans("AmountHT"), 'checked'=>1),
	'f.total_vat'=>array('label'=>$langs->trans("AmountVAT"), 'checked'=>0),
	'f.total_localtax1'=>array('label'=>$langs->transcountry("AmountLT1", $mysoc->country_code), 'checked'=>0, 'enabled'=>($mysoc->localtax1_assuj=="1")),
	'f.total_localtax2'=>array('label'=>$langs->transcountry("AmountLT2", $mysoc->country_code), 'checked'=>0, 'enabled'=>($mysoc->localtax2_assuj=="1")),
	'f.total_ttc'=>array('label'=>$langs->trans("AmountTTC"), 'checked'=>0),
	'dynamount_payed'=>array('label'=>$langs->trans("Received"), 'checked'=>0),
	'rtp'=>array('label'=>$langs->trans("Rest"), 'checked'=>0),
	'f.datec'=>array('label'=>$langs->trans("DateCreation"), 'checked'=>0, 'position'=>500),
	'f.tms'=>array('label'=>$langs->trans("DateModificationShort"), 'checked'=>0, 'position'=>500),
	'f.fk_statut'=>array('label'=>$langs->trans("Status"), 'checked'=>1, 'position'=>1000),
);
// Extra fields
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
{
	foreach($extrafields->attribute_label as $key => $val)
	{
		if (! empty($extrafields->attribute_list[$key])) $arrayfields["ef.".$key]=array('label'=>$extrafields->attribute_label[$key], 'checked'=>(($extrafields->attribute_list[$key]<0)?0:1), 'position'=>$extrafields->attribute_pos[$key], 'enabled'=>(abs($extrafields->attribute_list[$key])!=3 && $extrafields->attribute_perms[$key]));
	}
}


/*
 * Actions
 */

if (GETPOST('cancel','alpha')) { $action='list'; $massaction=''; }
if (! GETPOST('confirmmassaction','alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction=''; }

$parameters=array('socid'=>$socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

// Do we click on purge search criteria ?
if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter','alpha') || GETPOST('button_removefilter.x','alpha')) // All tests are required to be compatible with all browsers
{
	$search_user='';
	$search_sale='';
	$search_product_category='';
	$search_ref='';
	$search_refcustomer='';
	$search_type='';
	$search_project='';
	$search_societe='';
	$search_montant_ht='';
	$search_montant_vat='';
	$search_montant_localtax1='';
	$search_montant_localtax2='';
	$search_montant_ttc='';
	$search_status='';
	$search_paymentmode='';
	$search_town='';
	$search_zip="";
	$search_state="";
	$search_type='';
	$search_country='';
	$search_type_thirdparty='';
	$search_day='';
	$search_year='';
	$search_month='';
	$option='';
	$filter='';
	$search_day_lim='';
	$search_year_lim='';
	$search_month_lim='';
	$toselect='';
	$search_array_options=array();
}

if (empty($reshook))
{
	$objectclass='Facture';
	$objectlabel='Invoices';
	$permtoread = $user->rights->facture->lire;
	$permtocreate = $user->rights->facture->creer;
	$permtodelete = $user->rights->facture->supprimer;
	$uploaddir = $conf->facture->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}

if ($massaction == 'withdrawrequest')
{
	$langs->load("withdrawals");

	if (!$user->rights->prelevement->bons->creer)
	{
		$error++;
		setEventMessages($langs->trans("NotEnoughPermissions"), null, 'errors');
	}
	else
	{
		//Checking error
		$error = 0;

		$arrayofselected=is_array($toselect)?$toselect:array();
		$listofbills=array();
		foreach($arrayofselected as $toselectid)
		{
			$objecttmp=new Facture($db);
			$result=$objecttmp->fetch($toselectid);
			if ($result > 0)
			{
				$totalpaye  = $objecttmp->getSommePaiement();
				$totalcreditnotes = $objecttmp->getSumCreditNotesUsed();
				$totaldeposits = $objecttmp->getSumDepositsUsed();
				$objecttmp->resteapayer = price2num($objecttmp->total_ttc - $totalpaye - $totalcreditnotes - $totaldeposits,'MT');
				$listofbills[] = $objecttmp;
				if($objecttmp->paye || $objecttmp->resteapayer==0){
					$error++;
					setEventMessages($objecttmp->ref.' '.$langs->trans("AlreadyPaid"), $objecttmp->errors, 'errors');
				} else if($objecttmp->resteapayer<0){
					$error++;
					setEventMessages($objecttmp->ref.' '.$langs->trans("AmountMustBePositive"), $objecttmp->errors, 'errors');
				}
				if (!($objecttmp->statut > Facture::STATUS_DRAFT)){
					$error++;
					setEventMessages($objecttmp->ref.' '.$langs->trans("Draft"), $objecttmp->errors, 'errors');
				}

				$rsql = "SELECT pfd.rowid, pfd.traite, pfd.date_demande as date_demande";
				$rsql .= " , pfd.date_traite as date_traite";
				$rsql .= " , pfd.amount";
				$rsql .= " , u.rowid as user_id, u.lastname, u.firstname, u.login";
				$rsql .= " FROM ".MAIN_DB_PREFIX."prelevement_facture_demande as pfd";
				$rsql .= " , ".MAIN_DB_PREFIX."user as u";
				$rsql .= " WHERE fk_facture = ".$objecttmp->id;
				$rsql .= " AND pfd.fk_user_demande = u.rowid";
				$rsql .= " AND pfd.traite = 0";
				$rsql .= " ORDER BY pfd.date_demande DESC";

				$result_sql = $db->query($rsql);
				if ($result_sql)
				{
					$numprlv = $db->num_rows($result_sql);
				}

				if ($numprlv>0){
					$error++;
					setEventMessages($objecttmp->ref.' '.$langs->trans("RequestAlreadyDone"), $objecttmp->errors, 'errors');
				}
				if (!empty($objecttmp->mode_reglement_code ) && $objecttmp->mode_reglement_code != 'PRE'){
					$error++;
					setEventMessages($objecttmp->ref.' '.$langs->trans("BadPaymentMethod"), $objecttmp->errors, 'errors');
				}

			}
		}

		//Massive withdraw request
		if(!empty($listofbills) && empty($error))
		{
			$nbwithdrawrequestok=0;
			foreach($listofbills as $aBill)
			{
				$db->begin();
				$result = $aBill->demande_prelevement($user, $aBill->resteapayer);
				if ($result > 0)
				{
					$db->commit();
					$nbwithdrawrequestok++;
				}
				else
				{

					$db->rollback();
					setEventMessages($aBill->error, $aBill->errors, 'errors');
				}
			}
			if ($nbwithdrawrequestok > 0)
			{
				setEventMessages($langs->trans("WithdrawRequestsDone", $nbwithdrawrequestok), null, 'mesgs');
			}
		}
	}

}



/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);
$bankaccountstatic=new Account($db);
$facturestatic=new Facture($db);
$formcompany=new FormCompany($db);
$thirdpartystatic=new Societe($db);

llxHeader('',$langs->trans('CustomersInvoices'),'EN:Customers_Invoices|FR:Factures_Clients|ES:Facturas_a_clientes');

$sql = 'SELECT';
if ($sall || $search_product_category > 0) $sql = 'SELECT DISTINCT';
$sql.= ' f.rowid as id, f.facnumber as ref, f.ref_client, f.type, f.note_private, f.note_public, f.increment, f.fk_mode_reglement, f.total as total_ht, f.tva as total_vat, f.total_ttc,';
$sql.= ' f.localtax1 as total_localtax1, f.localtax2 as total_localtax2,';
$sql.= ' f.datef as df, f.date_lim_reglement as datelimite,';
$sql.= ' f.paye as paye, f.fk_statut,';
$sql.= ' f.datec as date_creation, f.tms as date_update,';
$sql.= ' s.rowid as socid, s.nom as name, s.email, s.town, s.zip, s.fk_pays, s.client, s.fournisseur, s.code_client, s.code_fournisseur, s.code_compta as code_compta_client, s.code_compta_fournisseur,';
$sql.= " typent.code as typent_code,";
$sql.= " state.code_departement as state_code, state.nom as state_name,";
$sql.= " country.code as country_code,";
$sql.= " p.rowid as project_id, p.ref as project_ref, p.title as project_label";
// We need dynamount_payed to be able to sort on status (value is surely wrong because we can count several lines several times due to other left join or link with contacts. But what we need is just 0 or > 0)
// TODO Better solution to be able to sort on already payed or remain to pay is to store amount_payed in a denormalized field.
if (! $sall) $sql.= ', SUM(pf.amount) as dynamount_payed';
// Add fields from extrafields
foreach ($extrafields->attribute_label as $key => $val) $sql.=($extrafields->attribute_type[$key] != 'separate' ? ",ef.".$key.' as options_'.$key : '');
// Add fields from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListSelect',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql.= ' FROM '.MAIN_DB_PREFIX.'societe as s';
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as country on (country.rowid = s.fk_pays)";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_typent as typent on (typent.id = s.fk_typent)";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as state on (state.rowid = s.fk_departement)";
$sql.= ', '.MAIN_DB_PREFIX.'facture as f';
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture_extrafields as ef on (f.rowid = ef.fk_object)";
if (! $sall) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiement_facture as pf ON pf.fk_facture = f.rowid';
if ($sall || $search_product_category > 0) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'facturedet as pd ON f.rowid=pd.fk_facture';
if ($search_product_category > 0) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie_product as cp ON cp.fk_product=pd.fk_product';
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = f.fk_projet";
// We'll need this table joined to the select in order to filter by sale
if ($search_sale > 0 || (! $user->rights->societe->client->voir && ! $socid)) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
if ($search_user > 0)
{
	$sql.=", ".MAIN_DB_PREFIX."element_contact as ec";
	$sql.=", ".MAIN_DB_PREFIX."c_type_contact as tc";
}
$sql.= ' WHERE f.fk_soc = s.rowid';
$sql.= ' AND f.entity IN ('.getEntity('facture').')';
if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($search_product_category > 0) $sql.=" AND cp.fk_categorie = ".$db->escape($search_product_category);
if ($socid > 0) $sql.= ' AND s.rowid = '.$socid;
if ($userid)
{
	if ($userid == -1) $sql.=' AND f.fk_user_author IS NULL';
	else $sql.=' AND f.fk_user_author = '.$userid;
}
if ($filtre)
{
	$aFilter = explode(',', $filtre);
	foreach ($aFilter as $filter)
	{
		$filt = explode(':', $filter);
		$sql .= ' AND ' . $db->escape(trim($filt[0])) . ' = ' . $db->escape(trim($filt[1]));
	}
}
if ($search_ref) $sql .= natural_search('f.facnumber', $search_ref);
if ($search_refcustomer) $sql .= natural_search('f.ref_client', $search_refcustomer);
if ($search_type != '' && $search_type >= 0)
{
	if ($search_type == '0') $sql.=" AND f.type = 0";  // standard
	if ($search_type == '1') $sql.=" AND f.type = 1";  // replacement
	if ($search_type == '2') $sql.=" AND f.type = 2";  // credit note
	if ($search_type == '3') $sql.=" AND f.type = 3";  // deposit
	if ($search_type == '4') $sql.=" AND f.type = 4";  // proforma
	if ($search_type == '5') $sql.=" AND f.type = 5";  // situation
}
if ($search_project) $sql .= natural_search('p.ref', $search_project);
if ($search_societe) $sql .= natural_search('s.nom', $search_societe);
if ($search_town)  $sql.= natural_search('s.town', $search_town);
if ($search_zip)   $sql.= natural_search("s.zip",$search_zip);
if ($search_state) $sql.= natural_search("state.nom",$search_state);
if ($search_country) $sql .= " AND s.fk_pays IN (".$search_country.')';
if ($search_type_thirdparty) $sql .= " AND s.fk_typent IN (".$search_type_thirdparty.')';
if ($search_company) $sql .= natural_search('s.nom', $search_company);
if ($search_montant_ht != '') $sql.= natural_search('f.total', $search_montant_ht, 1);
if ($search_montant_vat != '') $sql.= natural_search('f.tva', $search_montant_vat, 1);
if ($search_montant_localtax1 != '') $sql.= natural_search('f.localtax1', $search_montant_localtax1, 1);
if ($search_montant_localtax2 != '') $sql.= natural_search('f.localtax2', $search_montant_localtax2, 1);
if ($search_montant_ttc != '') $sql.= natural_search('f.total_ttc', $search_montant_ttc, 1);
if ($search_status != '' && $search_status >= 0)
{
	if ($search_status == '0') $sql.=" AND f.fk_statut = 0";  // draft
	if ($search_status == '1') $sql.=" AND f.fk_statut = 1";  // unpayed
	if ($search_status == '2') $sql.=" AND f.fk_statut = 2";  // payed     Not that some corrupted data may contains f.fk_statut = 1 AND f.paye = 1 (it means payed too but should not happend. If yes, reopen and reclassify billed)
	if ($search_status == '3') $sql.=" AND f.fk_statut = 3";  // abandonned
}
if ($search_paymentmode > 0) $sql .= " AND f.fk_mode_reglement = ".$db->escape($search_paymentmode);
if ($search_month > 0)
{
	if ($search_year > 0 && empty($search_day))
	$sql.= " AND f.datef BETWEEN '".$db->idate(dol_get_first_day($search_year,$search_month,false))."' AND '".$db->idate(dol_get_last_day($search_year,$search_month,false))."'";
	else if ($search_year > 0 && ! empty($search_day))
	$sql.= " AND f.datef BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $search_month, $search_day, $search_year))."' AND '".$db->idate(dol_mktime(23, 59, 59, $search_month, $search_day, $serch_year))."'";
	else
	$sql.= " AND date_format(f.datef, '%m') = '".$month."'";
}
else if ($search_year > 0)
{
	$sql.= " AND f.datef BETWEEN '".$db->idate(dol_get_first_day($search_year,1,false))."' AND '".$db->idate(dol_get_last_day($search_year,12,false))."'";
}
if ($month_lim > 0)
{
	if ($search_year_lim > 0 && empty($search_day_lim))
		$sql.= " AND f.date_lim_reglement BETWEEN '".$db->idate(dol_get_first_day($search_year_lim,$search_month_lim,false))."' AND '".$db->idate(dol_get_last_day($search_year_lim,$search_month_lim,false))."'";
	else if ($search_year_lim > 0 && ! empty($search_day_lim))
		$sql.= " AND f.date_lim_reglement BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $search_month_lim, $search_day_lim, $search_year_lim))."' AND '".$db->idate(dol_mktime(23, 59, 59, $search_month_lim, $search_day_lim, $search_year_lim))."'";
	else
		$sql.= " AND date_format(f.date_lim_reglement, '%m') = '".$db->escape($search_month_lim)."'";
}
else if ($search_year_lim > 0)
{
	$sql.= " AND f.date_lim_reglement BETWEEN '".$db->idate(dol_get_first_day($search_year_lim,1,false))."' AND '".$db->idate(dol_get_last_day($search_year_lim,12,false))."'";
}
if ($option == 'late') $sql.=" AND f.date_lim_reglement < '".$db->idate(dol_now() - $conf->facture->client->warning_delay)."'";
if ($search_sale > 0) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$search_sale;
if ($search_user > 0)
{
	$sql.= " AND ec.fk_c_type_contact = tc.rowid AND tc.element='facture' AND tc.source='internal' AND ec.element_id = f.rowid AND ec.fk_socpeople = ".$search_user;
}
// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListWhere',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;

if (! $sall)
{
	$sql.= ' GROUP BY f.rowid, f.facnumber, ref_client, f.type, f.note_private, f.note_public, f.increment, f.fk_mode_reglement, f.total, f.tva, f.total_ttc,';
	$sql.= ' f.localtax1, f.localtax2,';
	$sql.= ' f.datef, f.date_lim_reglement,';
	$sql.= ' f.paye, f.fk_statut,';
	$sql.= ' f.datec, f.tms,';
	$sql.= ' s.rowid, s.nom, s.email, s.town, s.zip, s.fk_pays, s.client, s.fournisseur, s.code_client, s.code_fournisseur, s.code_compta, s.code_compta_fournisseur,';
	$sql.= ' typent.code,';
	$sql.= ' state.code_departement, state.nom,';
	$sql.= ' country.code,';
	$sql.= " p.rowid, p.ref";

	foreach ($extrafields->attribute_label as $key => $val) //prevent error with sql_mode=only_full_group_by
	{
		$sql.=($extrafields->attribute_type[$key] != 'separate' ? ",ef.".$key : '');
	}
}
else
{
	$sql .= natural_search(array_keys($fieldstosearchall), $sall);
}

$sql.= ' ORDER BY ';
$listfield=explode(',',$sortfield);
foreach ($listfield as $key => $value) $sql.= $listfield[$key].' '.$sortorder.',';
$sql.= ' f.rowid DESC ';

$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
}

$sql.= $db->plimit($limit+1,$offset);
//print $sql;

$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);

	$arrayofselected=is_array($toselect)?$toselect:array();

	if ($socid)
	{
		$soc = new Societe($db);
		$soc->fetch($socid);
		if (empty($search_societe)) $search_societe = $soc->name;
	}

	$param='&socid='.$socid;
	if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.$contextpage;
	if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;
	if ($sall)				 $param.='&sall='.urlencode($sall);
	if ($search_day)         $param.='&search_day='.urlencode($search_day);
	if ($search_month)       $param.='&search_month='.urlencode($search_month);
	if ($search_year)        $param.='&search_year=' .urlencode($search_year);
	if ($search_day_lim)     $param.='&search_day_lim='.urlencode($search_day_lim);
	if ($search_month_lim)   $param.='&search_month_lim='.urlencode($search_month_lim);
	if ($search_year_lim)    $param.='&search_year_lim=' .urlencode($search_year_lim);
	if ($search_ref)         $param.='&search_ref=' .urlencode($search_ref);
	if ($search_refcustomer) $param.='&search_refcustomer=' .urlencode($search_refcustomer);
	if ($search_type != '')  $param.='&search_type='.urlencode($search_type);
	if ($search_societe)     $param.='&search_societe=' .urlencode($search_societe);
	if ($search_sale > 0)    $param.='&search_sale=' .urlencode($search_sale);
	if ($search_user > 0)    $param.='&search_user=' .urlencode($search_user);
	if ($search_product_category > 0)   $param.='$search_product_category=' .urlencode($search_product_category);
	if ($search_montant_ht != '')  $param.='&search_montant_ht='.urlencode($search_montant_ht);
	if ($search_montant_vat != '')  $param.='&search_montant_vat='.urlencode($search_montant_vat);
	if ($search_montant_localtax1 != '')  $param.='&search_montant_localtax1='.urlencode($search_montant_localtax1);
	if ($search_montant_localtax2 != '')  $param.='&search_montant_localtax2='.urlencode($search_montant_localtax2);
	if ($search_montant_ttc != '') $param.='&search_montant_ttc='.urlencode($search_montant_ttc);
	if ($search_status != '') $param.='&search_status='.urlencode($search_status);
	if ($search_paymentmode > 0) $param.='search_paymentmode='.urlencode($search_paymentmode);
	if ($show_files)         $param.='&show_files='.urlencode($show_files);
	if ($option)             $param.="&search_option=".urlencode($option);
	if ($optioncss != '')    $param.='&optioncss='.urlencode($optioncss);
	// Add $param from extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

	$arrayofmassactions=array(
		'validate'=>$langs->trans("Validate"),
		'presend'=>$langs->trans("SendByMail"),
		'builddoc'=>$langs->trans("PDFMerge"),
	);
	if ($conf->prelevement->enabled)
	{
	   $langs->load("withdrawals");
	   $arrayofmassactions['withdrawrequest']=$langs->trans("MakeWithdrawRequest");
	}
	if ($user->rights->facture->supprimer)
	{
		//if (! empty($conf->global->STOCK_CALCULATE_ON_BILL) || empty($conf->global->INVOICE_CAN_ALWAYS_BE_REMOVED))
		if (empty($conf->global->INVOICE_CAN_ALWAYS_BE_REMOVED))
		{
			// mass deletion never possible on invoices on such situation
		}
		else
		{
		   $arrayofmassactions['predelete']=$langs->trans("Delete");
		}
	}
	if (in_array($massaction, array('presend','predelete'))) $arrayofmassactions=array();
	$massactionbutton=$form->selectMassAction('', $arrayofmassactions);

	$newcardbutton='';
	if($user->rights->facture->creer)
	{
		$newcardbutton='<a class="butAction" href="'.DOL_URL_ROOT.'/compta/facture/card.php?action=create">'.$langs->trans('NewBill').'</a>';
	}

	$i = 0;
	print '<form method="POST" name="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";

	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="viewstatut" value="'.$viewstatut.'">';
	print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

	print_barre_liste($langs->trans('BillsCustomers').' '.($socid?' '.$soc->name:''), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'title_accountancy.png', 0, $newcardbutton, '', $limit);

	$topicmail="SendBillRef";
	$modelmail="facture_send";
	$objecttmp=new Facture($db);
	$trackid='inv'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

	if ($sall)
	{
		foreach($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
		print $langs->trans("FilterOnInto", $sall) . join(', ',$fieldstosearchall);
	}

 	// If the user can view prospects other than his'
	$moreforfilter='';
 	if ($user->rights->societe->client->voir || $socid)
 	{
 		$langs->load("commercial");
 		$moreforfilter.='<div class="divsearchfield">';
 		$moreforfilter.=$langs->trans('ThirdPartiesOfSaleRepresentative'). ': ';
		$moreforfilter.=$formother->select_salesrepresentatives($search_sale, 'search_sale', $user, 0, 1, 'maxwidth200');
	 	$moreforfilter.='</div>';
 	}
	// If the user can view prospects other than his'
	if ($user->rights->societe->client->voir || $socid)
	{
		$moreforfilter.='<div class="divsearchfield">';
		$moreforfilter.=$langs->trans('LinkedToSpecificUsers'). ': ';
		$moreforfilter.=$form->select_dolusers($search_user, 'search_user', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth200');
	 	$moreforfilter.='</div>';
	}
	// If the user can view prospects other than his'
	if ($conf->categorie->enabled && ($user->rights->produit->lire || $user->rights->service->lire))
	{
		include_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		$moreforfilter.='<div class="divsearchfield">';
		$moreforfilter.=$langs->trans('IncludingProductWithTag'). ': ';
		$cate_arbo = $form->select_all_categories(Categorie::TYPE_PRODUCT, null, 'parent', null, null, 1);
		$moreforfilter.=$form->selectarray('search_product_category', $cate_arbo, $search_product_category, 1, 0, 0, '', 0, 0, 0, 0, 'maxwidth300', 1);
		$moreforfilter.='</div>';
	}
	$parameters=array();
	$reshook=$hookmanager->executeHooks('printFieldPreListTitle',$parameters);    // Note that $action and $object may have been modified by hook
	if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
	else $moreforfilter = $hookmanager->resPrint;

	if ($moreforfilter)
	{
   		print '<div class="liste_titre liste_titre_bydiv centpercent">';
		print $moreforfilter;
		print '</div>';
	}

	$varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;
	$selectedfields=$form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);	// This also change content of $arrayfields
	if ($massactionbutton) $selectedfields.=$form->showCheckAddButtons('checkforselect', 1);

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

	// Filters lines
	print '<tr class="liste_titre_filter">';
	// Ref
	if (! empty($arrayfields['f.facnumber']['checked']))
	{
		print '<td class="liste_titre" align="left">';
		print '<input class="flat" size="6" type="text" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
		print '</td>';
	}
	// Ref customer
	if (! empty($arrayfields['f.ref_client']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input class="flat" size="6" type="text" name="search_refcustomer" value="'.dol_escape_htmltag($search_refcustomer).'">';
		print '</td>';
	}
	// Type
	if (! empty($arrayfields['f.type']['checked']))
	{
		print '<td class="liste_titre maxwidthonsmartphone">';
		$listtype=array(
			Facture::TYPE_STANDARD=>$langs->trans("InvoiceStandard"),
			Facture::TYPE_REPLACEMENT=>$langs->trans("InvoiceReplacement"),
			Facture::TYPE_CREDIT_NOTE=>$langs->trans("InvoiceAvoir"),
			Facture::TYPE_DEPOSIT=>$langs->trans("InvoiceDeposit"),
		);
		if (! empty($conf->global->INVOICE_USE_SITUATION))
		{
			$listtype[Facture::TYPE_SITUATION] = $langs->trans("InvoiceSituation");
		}
		//$listtype[Facture::TYPE_PROFORMA]=$langs->trans("InvoiceProForma");     // A proformat invoice is not an invoice but must be an order.
		print $form->selectarray('search_type', $listtype, $search_type, 1, 0, 0, '', 0, 0, 0, 'ASC', 'maxwidth100');
		print '</td>';
	}
	// Date invoice
	if (! empty($arrayfields['f.date']['checked']))
	{
		print '<td class="liste_titre nowraponall" align="center">';
		if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat valignmiddle" type="text" size="1" maxlength="2" name="search_day" value="'.dol_escape_htmltag($search_day).'">';
		print '<input class="flat valignmiddle" type="text" size="1" maxlength="2" name="search_month" value="'.dol_escape_htmltag($search_month).'">';
		$formother->select_year($search_year?$search_year:-1,'search_year',1, 20, 5, 0, 0, '', 'widthauto valignmiddle');
		print '</td>';
	}
	// Date due
	if (! empty($arrayfields['f.date_lim_reglement']['checked']))
	{
		print '<td class="liste_titre nowraponall" align="center">';
		if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat valignmiddle" type="text" size="1" maxlength="2" name="search_day_lim" value="'.dol_escape_htmltag($search_day_lim).'">';
		print '<input class="flat valignmiddle" type="text" size="1" maxlength="2" name="search_month_lim" value="'.dol_escape_htmltag($search_month_lim).'">';
		$formother->select_year($search_year_lim?$search_year_lim:-1,'search_year_lim',1, 20, 5, 0, 0, '', 'widthauto valignmiddle');
		print '<br><input type="checkbox" name="search_option" value="late"'.($option == 'late'?' checked':'').'> '.$langs->trans("Late");
		print '</td>';
	}
	// Project
	if (! empty($arrayfields['p.ref']['checked']))
	{
		print '<td class="liste_titre"><input class="flat" type="text" size="6" name="search_project" value="'.$search_project.'"></td>';
	}
	// Thirpdarty
	if (! empty($arrayfields['s.nom']['checked']))
	{
		print '<td class="liste_titre"><input class="flat" type="text" size="6" name="search_societe" value="'.$search_societe.'"></td>';
	}
	// Town
	if (! empty($arrayfields['s.town']['checked'])) print '<td class="liste_titre"><input class="flat" type="text" size="6" name="search_town" value="'.dol_escape_htmltag($search_town).'"></td>';
	// Zip
	if (! empty($arrayfields['s.zip']['checked'])) print '<td class="liste_titre"><input class="flat" type="text" size="4" name="search_zip" value="'.dol_escape_htmltag($search_zip).'"></td>';
	// State
	if (! empty($arrayfields['state.nom']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input class="flat" size="4" type="text" name="search_state" value="'.dol_escape_htmltag($search_state).'">';
		print '</td>';
	}
	// Country
	if (! empty($arrayfields['country.code_iso']['checked']))
	{
		print '<td class="liste_titre" align="center">';
		print $form->select_country($search_country,'search_country','',0,'maxwidth100');
		print '</td>';
	}
	// Company type
	if (! empty($arrayfields['typent.code']['checked']))
	{
		print '<td class="liste_titre maxwidthonsmartphone" align="center">';
		print $form->selectarray("search_type_thirdparty", $formcompany->typent_array(0), $search_type_thirdparty, 0, 0, 0, '', 0, 0, 0, (empty($conf->global->SOCIETE_SORT_ON_TYPEENT)?'ASC':$conf->global->SOCIETE_SORT_ON_TYPEENT), 'maxwidth100');
		print '</td>';
	}
	// Payment mode
	if (! empty($arrayfields['f.fk_mode_reglement']['checked']))
	{
		print '<td class="liste_titre" align="left">';
		$form->select_types_paiements($search_paymentmode, 'search_paymentmode', '', 0, 1, 1, 10);
		print '</td>';
	}
	if (! empty($arrayfields['f.total_ht']['checked']))
	{
		// Amount
		print '<td class="liste_titre" align="right">';
		print '<input class="flat" type="text" size="5" name="search_montant_ht" value="'.dol_escape_htmltag($search_montant_ht).'">';
		print '</td>';
	}
	if (! empty($arrayfields['f.total_vat']['checked']))
	{
		// Amount
		print '<td class="liste_titre" align="right">';
		print '<input class="flat" type="text" size="5" name="search_montant_vat" value="'.dol_escape_htmltag($search_montant_vat).'">';
		print '</td>';
	}
	if (! empty($arrayfields['f.total_localtax1']['checked']))
	{
		// Localtax1
		print '<td class="liste_titre" align="right">';
		print '<input class="flat" type="text" size="5" name="search_montant_localtax1" value="'.$search_montant_localtax1.'">';
		print '</td>';
	}
	if (! empty($arrayfields['f.total_localtax2']['checked']))
	{
		// Localtax2
		print '<td class="liste_titre" align="right">';
		print '<input class="flat" type="text" size="5" name="search_montant_localtax2" value="'.$search_montant_localtax2.'">';
		print '</td>';
	}
	if (! empty($arrayfields['f.total_ttc']['checked']))
	{
		// Amount
		print '<td class="liste_titre" align="right">';
		print '<input class="flat" type="text" size="5" name="search_montant_ttc" value="'.dol_escape_htmltag($search_montant_ttc).'">';
		print '</td>';
	}
	if (! empty($arrayfields['dynamount_payed']['checked']))
	{
		print '<td class="liste_titre" align="right">';
		print '</td>';
	}
	if (! empty($arrayfields['rtp']['checked']))
	{
		print '<td class="liste_titre" align="right">';
		print '</td>';
	}
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

	// Fields from hook
	$parameters=array('arrayfields'=>$arrayfields);
	$reshook=$hookmanager->executeHooks('printFieldListOption',$parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Date creation
	if (! empty($arrayfields['f.datec']['checked']))
	{
		print '<td class="liste_titre">';
		print '</td>';
	}
	// Date modification
	if (! empty($arrayfields['f.tms']['checked']))
	{
		print '<td class="liste_titre">';
		print '</td>';
	}
	// Status
	if (! empty($arrayfields['f.fk_statut']['checked']))
	{
		print '<td class="liste_titre maxwidthonsmartphone" align="right">';
		$liststatus=array('0'=>$langs->trans("BillShortStatusDraft"), '1'=>$langs->trans("BillShortStatusNotPaid"), '2'=>$langs->trans("BillShortStatusPaid"), '3'=>$langs->trans("BillShortStatusCanceled"));
		print $form->selectarray('search_status', $liststatus, $search_status, 1);
		print '</td>';
	}
	// Action column
	print '<td class="liste_titre" align="middle">';
	$searchpicto=$form->showFilterButtons();
	print $searchpicto;
	print '</td>';
	print "</tr>\n";

	print '<tr class="liste_titre">';
	if (! empty($arrayfields['f.facnumber']['checked']))          print_liste_field_titre($arrayfields['f.facnumber']['label'],$_SERVER['PHP_SELF'],'f.facnumber','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['f.ref_client']['checked']))         print_liste_field_titre($arrayfields['f.ref_client']['label'],$_SERVER["PHP_SELF"],'f.ref_client','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['f.type']['checked']))               print_liste_field_titre($arrayfields['f.type']['label'],$_SERVER["PHP_SELF"],'f.type','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['f.date']['checked']))               print_liste_field_titre($arrayfields['f.date']['label'],$_SERVER['PHP_SELF'],'f.datef','',$param,'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['f.date_lim_reglement']['checked'])) print_liste_field_titre($arrayfields['f.date_lim_reglement']['label'],$_SERVER['PHP_SELF'],"f.date_lim_reglement",'',$param,'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['p.ref']['checked']))                print_liste_field_titre($arrayfields['p.ref']['label'],$_SERVER['PHP_SELF'],"p.ref",'',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['s.nom']['checked']))                print_liste_field_titre($arrayfields['s.nom']['label'],$_SERVER['PHP_SELF'],'s.nom','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['s.town']['checked']))               print_liste_field_titre($arrayfields['s.town']['label'],$_SERVER["PHP_SELF"],'s.town','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['s.zip']['checked']))                print_liste_field_titre($arrayfields['s.zip']['label'],$_SERVER["PHP_SELF"],'s.zip','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['state.nom']['checked']))            print_liste_field_titre($arrayfields['state.nom']['label'],$_SERVER["PHP_SELF"],"state.nom","",$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['country.code_iso']['checked']))     print_liste_field_titre($arrayfields['country.code_iso']['label'],$_SERVER["PHP_SELF"],"country.code_iso","",$param,'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['typent.code']['checked']))          print_liste_field_titre($arrayfields['typent.code']['label'],$_SERVER["PHP_SELF"],"typent.code","",$param,'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['f.fk_mode_reglement']['checked']))  print_liste_field_titre($arrayfields['f.fk_mode_reglement']['label'],$_SERVER["PHP_SELF"],"f.fk_mode_reglement","",$param,"",$sortfield,$sortorder);
	if (! empty($arrayfields['f.total_ht']['checked']))           print_liste_field_titre($arrayfields['f.total_ht']['label'],$_SERVER['PHP_SELF'],'f.total','',$param,'align="right"',$sortfield,$sortorder);
	if (! empty($arrayfields['f.total_vat']['checked']))          print_liste_field_titre($arrayfields['f.total_vat']['label'],$_SERVER['PHP_SELF'],'f.tva','',$param,'align="right"',$sortfield,$sortorder);
	if (! empty($arrayfields['f.total_localtax1']['checked']))    print_liste_field_titre($arrayfields['f.total_localtax1']['label'],$_SERVER['PHP_SELF'],'f.localtax1','',$param,'align="right"',$sortfield,$sortorder);
	if (! empty($arrayfields['f.total_localtax2']['checked']))    print_liste_field_titre($arrayfields['f.total_localtax2']['label'],$_SERVER['PHP_SELF'],'f.localtax2','',$param,'align="right"',$sortfield,$sortorder);
	if (! empty($arrayfields['f.total_ttc']['checked']))          print_liste_field_titre($arrayfields['f.total_ttc']['label'],$_SERVER['PHP_SELF'],'f.total_ttc','',$param,'align="right"',$sortfield,$sortorder);
	if (! empty($arrayfields['dynamount_payed']['checked']))      print_liste_field_titre($arrayfields['dynamount_payed']['label'],$_SERVER['PHP_SELF'],'','',$param,'align="right"',$sortfield,$sortorder);
	if (! empty($arrayfields['rtp']['checked']))                  print_liste_field_titre($arrayfields['rtp']['label'],$_SERVER['PHP_SELF'],'','',$param,'align="right"',$sortfield,$sortorder);
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
	// Hook fields
	$parameters=array('arrayfields'=>$arrayfields,'param'=>$param,'sortfield'=>$sortfield,'sortorder'=>$sortorder);
	$reshook=$hookmanager->executeHooks('printFieldListTitle',$parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	if (! empty($arrayfields['f.datec']['checked']))     print_liste_field_titre($arrayfields['f.datec']['label'],$_SERVER["PHP_SELF"],"f.datec","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
	if (! empty($arrayfields['f.tms']['checked']))       print_liste_field_titre($arrayfields['f.tms']['label'],$_SERVER["PHP_SELF"],"f.tms","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
	if (! empty($arrayfields['f.fk_statut']['checked'])) print_liste_field_titre($arrayfields['f.fk_statut']['label'],$_SERVER["PHP_SELF"],"fk_statut,paye,type,dynamount_payed","",$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"],"",'','','align="center"',$sortfield,$sortorder,'maxwidthsearch ');
	print "</tr>\n";

	$projectstatic=new Project($db);

	if ($num > 0)
	{
		$i=0;
		$totalarray=array();
		while ($i < min($num,$limit))
		{
			$obj = $db->fetch_object($resql);

			$datelimit=$db->jdate($obj->datelimite);
			$facturestatic->id=$obj->id;
			$facturestatic->ref=$obj->ref;
			$facturestatic->type=$obj->type;
			$facturestatic->statut=$obj->fk_statut;
			$facturestatic->date_lim_reglement=$db->jdate($obj->datelimite);
			$facturestatic->note_public=$obj->note_public;
			$facturestatic->note_private=$obj->note_private;

			$thirdpartystatic->id=$obj->socid;
			$thirdpartystatic->name=$obj->name;
			$thirdpartystatic->client=$obj->client;
			$thirdpartystatic->fournisseur=$obj->fournisseur;
			$thirdpartystatic->code_client=$obj->code_client;
			$thirdpartystatic->code_compta_client=$obj->code_compta_client;
			$thirdpartystatic->code_fournisseur=$obj->code_fournisseur;
			$thirdpartystatic->code_compta_fournisseur=$obj->code_compta_fournisseur;
			$thirdpartystatic->email=$obj->email;
			$thirdpartystatic->country_code=$obj->country_code;

			$paiement = $facturestatic->getSommePaiement();
			$totalcreditnotes = $facturestatic->getSumCreditNotesUsed();
			$totaldeposits = $facturestatic->getSumDepositsUsed();
			$totalpay = $paiement + $totalcreditnotes + $totaldeposits;
			$remaintopay = $obj->total_ttc - $totalpay;

			print '<tr class="oddeven">';
			if (! empty($arrayfields['f.facnumber']['checked']))
			{
				print '<td class="nowrap">';

				print '<table class="nobordernopadding"><tr class="nocellnopadd">';

				print '<td class="nobordernopadding nowrap">';
				print $facturestatic->getNomUrl(1,'',200,0,'',0,1);
				print empty($obj->increment)?'':' ('.$obj->increment.')';
				print '</td>';

				print '<td style="min-width: 20px" class="nobordernopadding nowrap">';
				$filename=dol_sanitizeFileName($obj->ref);
				$filedir=$conf->facture->dir_output . '/' . dol_sanitizeFileName($obj->ref);
				$urlsource=$_SERVER['PHP_SELF'].'?id='.$obj->id;
				print $formfile->getDocumentsLink($facturestatic->element, $filename, $filedir);
				print '</td>';
				print '</tr>';
				print '</table>';

				print "</td>\n";
				if (! $i) $totalarray['nbfield']++;
			}

			// Customer ref
			if (! empty($arrayfields['f.ref_client']['checked']))
			{
				print '<td class="nowrap">';
				print $obj->ref_client;
				print '</td>';
				if (! $i) $totalarray['nbfield']++;
			}

			// Type
			if (! empty($arrayfields['f.type']['checked']))
			{
				print '<td class="nowrap">';
				print $facturestatic->getLibType();
				print "</td>";
				if (! $i) $totalarray['nbfield']++;
			}

			// Date
			if (! empty($arrayfields['f.date']['checked']))
			{
				print '<td align="center" class="nowrap">';
				print dol_print_date($db->jdate($obj->df),'day');
				print '</td>';
				if (! $i) $totalarray['nbfield']++;
			}

			// Date limit
			if (! empty($arrayfields['f.date_lim_reglement']['checked']))
			{
				print '<td align="center" class="nowrap">'.dol_print_date($datelimit,'day');
				if ($facturestatic->hasDelay())
				{
					print img_warning($langs->trans('Late'));
				}
				print '</td>';
				if (! $i) $totalarray['nbfield']++;
			}

			// Project
			if (! empty($arrayfields['p.ref']['checked']))
			{
				print '<td class="nowrap">';
				if ($obj->project_id > 0)
				{
					$projectstatic->id=$obj->project_id;
					$projectstatic->ref=$obj->project_ref;
					$projectstatic->title=$obj->project_label;
					print $projectstatic->getNomUrl(1);
				}
				print '</td>';
				if (! $i) $totalarray['nbfield']++;
			}

			// Third party
			if (! empty($arrayfields['s.nom']['checked']))
			{
				print '<td class="tdoverflowmax200">';
				print $thirdpartystatic->getNomUrl(1,'customer');
				print '</td>';
				if (! $i) $totalarray['nbfield']++;
			}
			// Town
			if (! empty($arrayfields['s.town']['checked']))
			{
				print '<td class="nocellnopadd">';
				print $obj->town;
				print '</td>';
				if (! $i) $totalarray['nbfield']++;
			}
			// Zip
			if (! empty($arrayfields['s.zip']['checked']))
			{
				print '<td class="nocellnopadd">';
				print $obj->zip;
				print '</td>';
				if (! $i) $totalarray['nbfield']++;
			}
			// State
			if (! empty($arrayfields['state.nom']['checked']))
			{
				print "<td>".$obj->state_name."</td>\n";
				if (! $i) $totalarray['nbfield']++;
			}
			// Country
			if (! empty($arrayfields['country.code_iso']['checked']))
			{
				print '<td align="center">';
				$tmparray=getCountry($obj->fk_pays,'all');
				print $tmparray['label'];
				print '</td>';
				if (! $i) $totalarray['nbfield']++;
			}
			// Type ent
			if (! empty($arrayfields['typent.code']['checked']))
			{
				print '<td align="center">';
				if (count($typenArray)==0) $typenArray = $formcompany->typent_array(1);
				print $typenArray[$obj->typent_code];
				print '</td>';
				if (! $i) $totalarray['nbfield']++;
			}

			// Payment mode
			if (! empty($arrayfields['f.fk_mode_reglement']['checked']))
			{
				print '<td>';
				$form->form_modes_reglement($_SERVER['PHP_SELF'], $obj->fk_mode_reglement, 'none', '', -1);
				print '</td>';
				if (! $i) $totalarray['nbfield']++;
			}

			// Amount HT
			if (! empty($arrayfields['f.total_ht']['checked']))
			{
				  print '<td align="right">'.price($obj->total_ht)."</td>\n";
				  if (! $i) $totalarray['nbfield']++;
				  if (! $i) $totalarray['totalhtfield']=$totalarray['nbfield'];
				  $totalarray['totalht'] += $obj->total_ht;
			}
			// Amount VAT
			if (! empty($arrayfields['f.total_vat']['checked']))
			{
				print '<td align="right">'.price($obj->total_vat)."</td>\n";
				if (! $i) $totalarray['nbfield']++;
				if (! $i) $totalarray['totalvatfield']=$totalarray['nbfield'];
				$totalarray['totalvat'] += $obj->total_vat;
			}
			// Amount LocalTax1
			if (! empty($arrayfields['f.total_localtax1']['checked']))
			{
				print '<td align="right">'.price($obj->total_localtax1)."</td>\n";
				if (! $i) $totalarray['nbfield']++;
				if (! $i) $totalarray['totallocaltax1field']=$totalarray['nbfield'];
				$totalarray['totallocaltax1'] += $obj->total_localtax1;
			}
			// Amount LocalTax2
			if (! empty($arrayfields['f.total_localtax2']['checked']))
			{
				print '<td align="right">'.price($obj->total_localtax2)."</td>\n";
				if (! $i) $totalarray['nbfield']++;
				if (! $i) $totalarray['totallocaltax2field']=$totalarray['nbfield'];
				$totalarray['totallocaltax2'] += $obj->total_localtax2;
			}
			// Amount TTC
			if (! empty($arrayfields['f.total_ttc']['checked']))
			{
				print '<td align="right">'.price($obj->total_ttc)."</td>\n";
				if (! $i) $totalarray['nbfield']++;
				if (! $i) $totalarray['totalttcfield']=$totalarray['nbfield'];
				$totalarray['totalttc'] += $obj->total_ttc;
			}

			if (! empty($arrayfields['dynamount_payed']['checked']))
			{
				print '<td align="right">'.(! empty($totalpay)?price($totalpay,0,$langs):'&nbsp;').'</td>'; // TODO Use a denormalized field
				if (! $i) $totalarray['nbfield']++;
				if (! $i) $totalarray['totalamfield']=$totalarray['nbfield'];
				$totalarray['totalam'] += $totalpay;
			}

			if (! empty($arrayfields['rtp']['checked']))
			{
				print '<td align="right">'.(! empty($remaintopay)?price($remaintopay,0,$langs):'&nbsp;').'</td>'; // TODO Use a denormalized field
				if (! $i) $totalarray['nbfield']++;
				if (! $i) $totalarray['totalrtpfield']=$totalarray['nbfield'];
				$totalarray['totalrtp'] += $remaintopay;
			}

			// Extra fields
			include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
			// Fields from hook
			$parameters=array('arrayfields'=>$arrayfields, 'obj'=>$obj);
			$reshook=$hookmanager->executeHooks('printFieldListValue',$parameters);    // Note that $action and $object may have been modified by hook
			print $hookmanager->resPrint;
			// Date creation
			if (! empty($arrayfields['f.datec']['checked']))
			{
				print '<td align="center" class="nowrap">';
				print dol_print_date($db->jdate($obj->date_creation), 'dayhour', 'tzuser');
				print '</td>';
				if (! $i) $totalarray['nbfield']++;
			}
			// Date modification
			if (! empty($arrayfields['f.tms']['checked']))
			{
				print '<td align="center" class="nowrap">';
				print dol_print_date($db->jdate($obj->date_update), 'dayhour', 'tzuser');
				print '</td>';
				if (! $i) $totalarray['nbfield']++;
			}
			// Status
			if (! empty($arrayfields['f.fk_statut']['checked']))
			{
				print '<td align="right" class="nowrap">';
				print $facturestatic->LibStatut($obj->paye,$obj->fk_statut,5,$paiement,$obj->type);
				print "</td>";
				if (! $i) $totalarray['nbfield']++;
			}

			// Action column
			print '<td class="nowrap" align="center">';
			if ($massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
			{
				$selected=0;
				if (in_array($obj->id, $arrayofselected)) $selected=1;
				print '<input id="cb'.$obj->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->id.'"'.($selected?' checked="checked"':'').'>';
			}
			print '</td>' ;
			if (! $i) $totalarray['nbfield']++;

			print "</tr>\n";

			$i++;
		}

		// Show total line
		if (isset($totalarray['totalhtfield'])
	   || isset($totalarray['totalvatfield'])
	   || isset($totalarray['totallocaltax1field'])
	   || isset($totalarray['totallocaltax2field'])
	   || isset($totalarray['totalttcfield'])
	   || isset($totalarray['totalamfield'])
	   || isset($totalarray['totalrtpfield'])
	   )
		{
			print '<tr class="liste_total">';
			$i=0;
			while ($i < $totalarray['nbfield'])
			{
			   $i++;
			   if ($i == 1)
			   {
					if ($num < $limit && empty($offset)) print '<td align="left">'.$langs->trans("Total").'</td>';
					else print '<td align="left">'.$langs->trans("Totalforthispage").'</td>';
			   }
			   elseif ($totalarray['totalhtfield'] == $i)  print '<td align="right">'.price($totalarray['totalht']).'</td>';
			   elseif ($totalarray['totalvatfield'] == $i) print '<td align="right">'.price($totalarray['totalvat']).'</td>';
			   elseif ($totalarray['totallocaltax1field'] == $i) print '<td align="right">'.price($totalarray['totallocaltax1']).'</td>';
			   elseif ($totalarray['totallocaltax2field'] == $i) print '<td align="right">'.price($totalarray['totallocaltax2']).'</td>';
			   elseif ($totalarray['totalttcfield'] == $i) print '<td align="right">'.price($totalarray['totalttc']).'</td>';
			   elseif ($totalarray['totalamfield'] == $i)  print '<td align="right">'.price($totalarray['totalam']).'</td>';
			   elseif ($totalarray['totalrtpfield'] == $i)  print '<td align="right">'.price($totalarray['totalrtp']).'</td>';
			   else print '<td></td>';
			}
			print '</tr>';

		}
	}

	$db->free($resql);

	$parameters=array('arrayfields'=>$arrayfields, 'sql'=>$sql);
	$reshook=$hookmanager->executeHooks('printFieldListFooter',$parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	print "</table>\n";
	print '</div>';

	print "</form>\n";

	$hidegeneratedfilelistifempty=1;
	if ($massaction == 'builddoc' || $action == 'remove_file' || $show_files) $hidegeneratedfilelistifempty=0;

	// Show list of available documents
	$urlsource=$_SERVER['PHP_SELF'].'?sortfield='.$sortfield.'&sortorder='.$sortorder;
	$urlsource.=str_replace('&amp;','&',$param);

	$filedir=$diroutputmassaction;
	$genallowed=$user->rights->facture->lire;
	$delallowed=$user->rights->facture->creer;

	print $formfile->showdocuments('massfilesarea_invoices','',$filedir,$urlsource,0,$delallowed,'',1,1,0,48,1,$param,$title,'','','',null,$hidegeneratedfilelistifempty);
}
else
{
	dol_print_error($db);
}

llxFooter();
$db->close();
