<?php
/* Copyright (C) 2012-2013 Philippe Berthet     <berthet@systune.be>
 * Copyright (C) 2004-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2013-2015 Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2015-2017 Ferran Marcet		<fmarcet@2byte.es>
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
 *	\file       htdocs/societe/consumption.php
 *  \ingroup    societe
 *	\brief      Add a tab on thirdparty view to list all products/services bought or sells by thirdparty
 */

require "../main.inc.php";
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';

// Security check
$socid = GETPOST('socid','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe', $socid, '&societe');
$object = new Societe($db);
if ($socid > 0) $object->fetch($socid);

// Sort & Order fields
$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder='DESC';
if (! $sortfield) $sortfield='dateprint';

// Search fields
$sref=GETPOST("sref");
$sprod_fulldescr=GETPOST("sprod_fulldescr");
$month	= GETPOST('month','int');
$year	= GETPOST('year','int');

// Clean up on purge search criteria ?
if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) // Both test are required to be compatible with all browsers
{
    $sref='';
    $sprod_fulldescr='';
    $year='';
    $month='';
}
// Customer or supplier selected in drop box
$thirdTypeSelect = GETPOST("third_select_id");
$type_element = GETPOST('type_element')?GETPOST('type_element'):'';

// Load translation files required by the page
$langs->loadLangs(array("companies", "bills", "orders", "suppliers", "propal", "interventions", "contracts", "products"));

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('consumptionthirdparty'));


/*
 * Actions
 */

$parameters=array('id'=>$socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');



/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);
$productstatic=new Product($db);

$title = $langs->trans("Referers",$object->name);
if (! empty($conf->global->MAIN_HTML_TITLE) && preg_match('/thirdpartynameonly/',$conf->global->MAIN_HTML_TITLE) && $object->name) $title=$object->name." - ".$title;
$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('',$title,$help_url);

if (empty($socid))
{
	dol_print_error($db);
	exit;
}

$head = societe_prepare_head($object);
dol_fiche_head($head, 'consumption', $langs->trans("ThirdParty"), -1, 'company');

$linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

dol_banner_tab($object, 'socid', $linkback, ($user->societe_id?0:1), 'rowid', 'nom');

print '<div class="fichecenter">';

print '<div class="underbanner clearboth"></div>';
print '<table class="border" width="100%">';

if (! empty($conf->global->SOCIETE_USEPREFIX))  // Old not used prefix field
{
	print '<tr><td class="titlefield">'.$langs->trans('Prefix').'</td><td colspan="3">'.$object->prefix_comm.'</td></tr>';
}

//if ($conf->agenda->enabled && $user->rights->agenda->myactions->read) $elementTypeArray['action']=$langs->transnoentitiesnoconv('Events');

if ($object->client)
{
	print '<tr><td class="titlefield">';
	print $langs->trans('CustomerCode').'</td><td colspan="3">';
	print $object->code_client;
	if ($object->check_codeclient() <> 0) print ' <font class="error">('.$langs->trans("WrongCustomerCode").')</font>';
	print '</td></tr>';
	$sql = "SELECT count(*) as nb from ".MAIN_DB_PREFIX."facture where fk_soc = ".$socid;
	$resql=$db->query($sql);
	if (!$resql) dol_print_error($db);

	$obj = $db->fetch_object($resql);
	$nbFactsClient = $obj->nb;
	$thirdTypeArray['customer']=$langs->trans("customer");
	if ($conf->propal->enabled && $user->rights->propal->lire) $elementTypeArray['propal']=$langs->transnoentitiesnoconv('Proposals');
	if ($conf->commande->enabled && $user->rights->commande->lire) $elementTypeArray['order']=$langs->transnoentitiesnoconv('Orders');
	if ($conf->facture->enabled && $user->rights->facture->lire) $elementTypeArray['invoice']=$langs->transnoentitiesnoconv('Invoices');
	if ($conf->contrat->enabled && $user->rights->contrat->lire) $elementTypeArray['contract']=$langs->transnoentitiesnoconv('Contracts');
}

if ($conf->ficheinter->enabled && $user->rights->ficheinter->lire) $elementTypeArray['fichinter']=$langs->transnoentitiesnoconv('Interventions');

if ($object->fournisseur)
{
	print '<tr><td class="titlefield">';
	print $langs->trans('SupplierCode').'</td><td colspan="3">';
	print $object->code_fournisseur;
	if ($object->check_codefournisseur() <> 0) print ' <font class="error">('.$langs->trans("WrongSupplierCode").')</font>';
	print '</td></tr>';
	$sql = "SELECT count(*) as nb from ".MAIN_DB_PREFIX."commande_fournisseur where fk_soc = ".$socid;
	$resql=$db->query($sql);
	if (!$resql) dol_print_error($db);

	$obj = $db->fetch_object($resql);
	$nbCmdsFourn = $obj->nb;
	$thirdTypeArray['supplier']=$langs->trans("supplier");
	if ($conf->fournisseur->enabled && $user->rights->fournisseur->facture->lire) $elementTypeArray['supplier_invoice']=$langs->transnoentitiesnoconv('SuppliersInvoices');
	if ($conf->fournisseur->enabled && $user->rights->fournisseur->commande->lire) $elementTypeArray['supplier_order']=$langs->transnoentitiesnoconv('SuppliersOrders');
	if ($conf->fournisseur->enabled && $user->rights->supplier_proposal->lire) $elementTypeArray['supplier_proposal']=$langs->transnoentitiesnoconv('SupplierProposals');
}
print '</table>';

print '</div>';

dol_fiche_end();
print '<br>';


print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?socid='.$socid.'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

$sql_select='';
/*if ($type_element == 'action')
{ 	// Customer : show products from invoices
	require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
	$documentstatic=new ActionComm($db);
	$sql_select = 'SELECT f.id as doc_id, f.id as doc_number, \'1\' as doc_type, f.datep as dateprint, ';
	$tables_from = MAIN_DB_PREFIX."actioncomm as f";
	$where = " WHERE rbl.parentid = f.id AND f.entity = ".$conf->entity;
	$dateprint = 'f.datep';
	$doc_number='f.id';
}*/
if ($type_element == 'fichinter')
{ 	// Customer : show products from invoices
	require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';
	$documentstatic=new Fichinter($db);
	$sql_select = 'SELECT f.rowid as doc_id, f.ref as doc_number, \'1\' as doc_type, f.datec as dateprint, f.fk_statut as status, ';
	$tables_from = MAIN_DB_PREFIX."fichinter as f LEFT JOIN ".MAIN_DB_PREFIX."fichinterdet as d ON d.fk_fichinter = f.rowid";	// Must use left join to work also with option that disable usage of lines.
	$where = " WHERE f.fk_soc = s.rowid AND s.rowid = ".$socid;
	$where.= " AND f.entity = ".$conf->entity;
	$dateprint = 'f.datec';
	$doc_number='f.ref';
}
if ($type_element == 'invoice')
{ 	// Customer : show products from invoices
	require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
	$documentstatic=new Facture($db);
	$sql_select = 'SELECT f.rowid as doc_id, f.facnumber as doc_number, f.type as doc_type, f.datef as dateprint, f.fk_statut as status, f.paye as paid, ';
	$tables_from = MAIN_DB_PREFIX."facture as f,".MAIN_DB_PREFIX."facturedet as d";
	$where = " WHERE f.fk_soc = s.rowid AND s.rowid = ".$socid;
	$where.= " AND d.fk_facture = f.rowid";
	$where.= " AND f.entity = ".$conf->entity;
	$dateprint = 'f.datef';
	$doc_number='f.facnumber';
	$thirdTypeSelect='customer';
}
if ($type_element == 'propal')
{
	require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
	$documentstatic=new Propal($db);
	$sql_select = 'SELECT c.rowid as doc_id, c.ref as doc_number, \'1\' as doc_type, c.datep as dateprint, c.fk_statut as status, ';
	$tables_from = MAIN_DB_PREFIX."propal as c,".MAIN_DB_PREFIX."propaldet as d";
	$where = " WHERE c.fk_soc = s.rowid AND s.rowid = ".$socid;
	$where.= " AND d.fk_propal = c.rowid";
	$where.= " AND c.entity = ".$conf->entity;
	$datePrint = 'c.datep';
	$doc_number='c.ref';
	$thirdTypeSelect='customer';
}
if ($type_element == 'order')
{
	require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
	$documentstatic=new Commande($db);
	$sql_select = 'SELECT c.rowid as doc_id, c.ref as doc_number, \'1\' as doc_type, c.date_commande as dateprint, c.fk_statut as status, ';
	$tables_from = MAIN_DB_PREFIX."commande as c,".MAIN_DB_PREFIX."commandedet as d";
	$where = " WHERE c.fk_soc = s.rowid AND s.rowid = ".$socid;
	$where.= " AND d.fk_commande = c.rowid";
	$where.= " AND c.entity = ".$conf->entity;
	$dateprint = 'c.date_commande';
	$doc_number='c.ref';
	$thirdTypeSelect='customer';
}
if ($type_element == 'supplier_invoice')
{ 	// Supplier : Show products from invoices.
	require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
	$documentstatic=new FactureFournisseur($db);
	$sql_select = 'SELECT f.rowid as doc_id, f.ref as doc_number, \'1\' as doc_type, f.datef as dateprint, f.fk_statut as status, f.paye as paid, ';
	$tables_from = MAIN_DB_PREFIX."facture_fourn as f,".MAIN_DB_PREFIX."facture_fourn_det as d";
	$where = " WHERE f.fk_soc = s.rowid AND s.rowid = ".$socid;
	$where.= " AND d.fk_facture_fourn = f.rowid";
	$where.= " AND f.entity = ".$conf->entity;
	$dateprint = 'f.datef';
	$doc_number='f.ref';
	$thirdTypeSelect='supplier';
}
if ($type_element == 'supplier_proposal')
{
    require_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';
    $documentstatic=new SupplierProposal($db);
    $sql_select = 'SELECT c.rowid as doc_id, c.ref as doc_number, \'1\' as doc_type, c.date_valid as dateprint, c.fk_statut as status, ';
    $tables_from = MAIN_DB_PREFIX."supplier_proposal as c,".MAIN_DB_PREFIX."supplier_proposaldet as d";
    $where = " WHERE c.fk_soc = s.rowid AND s.rowid = ".$socid;
    $where.= " AND d.fk_supplier_proposal = c.rowid";
    $where.= " AND c.entity = ".$conf->entity;
    $dateprint = 'c.date_valid';
    $doc_number='c.ref';
    $thirdTypeSelect='supplier';
}
if ($type_element == 'supplier_order')
{ 	// Supplier : Show products from orders.
	require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
	$documentstatic=new CommandeFournisseur($db);
	$sql_select = 'SELECT c.rowid as doc_id, c.ref as doc_number, \'1\' as doc_type, c.date_valid as dateprint, c.fk_statut as status, ';
	$tables_from = MAIN_DB_PREFIX."commande_fournisseur as c,".MAIN_DB_PREFIX."commande_fournisseurdet as d";
	$where = " WHERE c.fk_soc = s.rowid AND s.rowid = ".$socid;
	$where.= " AND d.fk_commande = c.rowid";
	$where.= " AND c.entity = ".$conf->entity;
	$dateprint = 'c.date_valid';
	$doc_number='c.ref';
	$thirdTypeSelect='supplier';
}
if ($type_element == 'contract')
{ 	// Order
	require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
	$documentstatic=new Contrat($db);
	$documentstaticline=new ContratLigne($db);
	$sql_select = 'SELECT c.rowid as doc_id, c.ref as doc_number, \'1\' as doc_type, c.date_contrat as dateprint, d.statut as status, ';
	$tables_from = MAIN_DB_PREFIX."contrat as c,".MAIN_DB_PREFIX."contratdet as d";
	$where = " WHERE c.fk_soc = s.rowid AND s.rowid = ".$socid;
	$where.= " AND d.fk_contrat = c.rowid";
	$where.= " AND c.entity = ".$conf->entity;
	$dateprint = 'c.date_valid';
	$doc_number='c.ref';
	$thirdTypeSelect='customer';
}

$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListSelect',$parameters);    // Note that $action and $object may have been modified by hook

if (!empty($sql_select))
{
	$sql = $sql_select;
	$sql.= ' d.description as description,';
	if ($type_element != 'fichinter' && $type_element != 'contract' && $type_element != 'supplier_proposal') $sql.= ' d.label, d.fk_product as product_id, d.fk_product as fk_product, d.info_bits, d.date_start, d.date_end, d.qty, d.qty as prod_qty, d.total_ht as total_ht, ';
	if ($type_element == 'supplier_proposal') $sql.= ' d.label, d.fk_product as product_id, d.fk_product as fk_product, d.info_bits, d.qty, d.qty as prod_qty, d.total_ht as total_ht, ';
	if ($type_element == 'contract') $sql.= ' d.label, d.fk_product as product_id, d.fk_product as fk_product, d.info_bits, d.date_ouverture as date_start, d.date_cloture as date_end, d.qty, d.qty as prod_qty, d.total_ht as total_ht, ';
	if ($type_element != 'fichinter') $sql.= ' p.ref as ref, p.rowid as prod_id, p.rowid as fk_product, p.fk_product_type as prod_type, p.fk_product_type as fk_product_type, p.entity as pentity,';
	$sql.= " s.rowid as socid ";
	if ($type_element != 'fichinter') $sql.= ", p.ref as prod_ref, p.label as product_label";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".$tables_from;
	if ($type_element != 'fichinter') $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON d.fk_product = p.rowid ';
	$sql.= $where;
	if ($month > 0) {
		if ($year > 0) {
			$start = dol_mktime(0, 0, 0, $month, 1, $year);
			$end = dol_time_plus_duree($start,1,'m') - 1;
			$sql.= " AND ".$dateprint." BETWEEN '".$db->idate($start)."' AND '".$db->idate($end)."'";
		} else {
			$sql.= " AND date_format(".$dateprint.", '%m') = '".sprintf('%02d',$month)."'";
		}
	} else if ($year > 0) {
		$start = dol_mktime(0, 0, 0, 1, 1, $year);
		$end = dol_time_plus_duree($start,1,'y') - 1;
		$sql.= " AND ".$dateprint." BETWEEN '".$db->idate($start)."' AND '".$db->idate($end)."'";
	}
	if ($sref) $sql.= " AND ".$doc_number." LIKE '%".$db->escape($sref)."%'";
	if ($sprod_fulldescr)
	{
	    $sql.= " AND (d.description LIKE '%".$db->escape($sprod_fulldescr)."%'";
	    if (GETPOST('type_element') != 'fichinter') $sql.= " OR p.ref LIKE '%".$db->escape($sprod_fulldescr)."%'";
	    if (GETPOST('type_element') != 'fichinter') $sql.= " OR p.label LIKE '%".$db->escape($sprod_fulldescr)."%'";
	    $sql.=")";
	}
	$sql.= $db->order($sortfield,$sortorder);

	$resql=$db->query($sql);
	$totalnboflines = $db->num_rows($resql);

	$sql.= $db->plimit($limit + 1, $offset);
	//print $sql;
}

$disabled=0;
$showempty=2;
if (empty($elementTypeArray) && ! $object->client && ! $object->fournisseur)
{
    $showempty=$langs->trans("ThirdpartyNotCustomerNotSupplierSoNoRef");
    $disabled=1;
}

// Define type of elements
$typeElementString = $form->selectarray("type_element", $elementTypeArray, GETPOST('type_element'), $showempty, 0, 0, '', 0, 0, $disabled, '', 'maxwidth150onsmartphone');
$button = '<input type="submit" class="button" name="button_third" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';

$param='';
$param.="&sref=".urlencode($sref);
$param.="&month=".urlencode($month);
$param.="&year=".urlencode($year);
$param.="&sprod_fulldescr=".urlencode($sprod_fulldescr);
$param.="&socid=".urlencode($socid);
$param.="&type_element=".urlencode($type_element);

$total_qty=0;

if ($sql_select)
{
	$resql=$db->query($sql);
	if (!$resql) dol_print_error($db);

	$num = $db->num_rows($resql);

	$param="&socid=".$socid."&type_element=".$type_element;
    if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.$contextpage;
	if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;
	if ($sprod_fulldescr) $param.= "&sprod_fulldescr=".urlencode($sprod_fulldescr);
	if ($sref) $param.= "&sref=".urlencode($sref);
	if ($month) $param.= "&month=".$month;
	if ($year) $param.= "&year=".$year;
	if ($optioncss != '') $param.='&optioncss='.$optioncss;

    print_barre_liste($langs->trans('ProductsIntoElements').' '.$typeElementString.' '.$button, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder,'',$num, $totalnboflines, '', 0, '', '', $limit);

    print '<div class="div-table-responsive-no-min">';
    print '<table class="liste" width="100%">'."\n";

    // Filters
    print '<tr class="liste_titre">';
    print '<td class="liste_titre" align="left">';
    print '<input class="flat" type="text" name="sref" size="8" value="'.$sref.'">';
    print '</td>';
    print '<td class="liste_titre nowrap center">'; // date
    print $formother->select_month($month?$month:-1, 'month', 1, 0, 'valignmiddle');
    $formother->select_year($year?$year:-1,'year',1, 20, 1);
    print '</td>';
    print '<td class="liste_titre" align="center">';
    print '</td>';
    print '<td class="liste_titre" align="left">';
    print '<input class="flat" type="text" name="sprod_fulldescr" size="15" value="'.dol_escape_htmltag($sprod_fulldescr).'">';
    print '</td>';
    print '<td class="liste_titre" align="center">';
    print '</td>';
    print '<td class="liste_titre" align="center">';
    print '</td>';
    print '<td class="liste_titre" align="right">';
    $searchpicto=$form->showFilterAndCheckAddButtons(0);
    print $searchpicto;
    print '</td>';
    print '</tr>';

    // Titles with sort buttons
    print '<tr class="liste_titre">';
    print_liste_field_titre('Ref',$_SERVER['PHP_SELF'],'doc_number','',$param,'align="left"',$sortfield,$sortorder);
    print_liste_field_titre('Date',$_SERVER['PHP_SELF'],'dateprint','',$param,'align="center" width="150"',$sortfield,$sortorder);
    print_liste_field_titre('Status',$_SERVER['PHP_SELF'],'fk_statut','',$param,'align="center"',$sortfield,$sortorder);
    print_liste_field_titre('Product',$_SERVER['PHP_SELF'],'','',$param,'align="left"',$sortfield,$sortorder);
    print_liste_field_titre('Quantity',$_SERVER['PHP_SELF'],'prod_qty','',$param,'align="right"',$sortfield,$sortorder);
    print_liste_field_titre('TotalHT',$_SERVER['PHP_SELF'],'total_ht','',$param,'align="right"',$sortfield,$sortorder);
    print_liste_field_titre('UnitPrice',$_SERVER['PHP_SELF'],'','',$param,'align="right"',$sortfield,$sortorder);
    print "</tr>\n";


	$i = 0;
	while (($objp = $db->fetch_object($resql)) && $i < min($num, $limit))
	{
		$documentstatic->id=$objp->doc_id;
		$documentstatic->ref=$objp->doc_number;
		$documentstatic->type=$objp->doc_type;
		$documentstatic->fk_statut=$objp->status;
		$documentstatic->fk_status=$objp->status;
		$documentstatic->statut=$objp->status;
		$documentstatic->status=$objp->status;
		$documentstatic->paye=$objp->paid;

		if (is_object($documentstaticline)) $documentstaticline->statut=$objp->status;

		print '<tr class="oddeven">';
		print '<td class="nobordernopadding nowrap" width="100">';
		print $documentstatic->getNomUrl(1);
		print '</td>';
		print '<td align="center" width="80">'.dol_print_date($db->jdate($objp->dateprint),'day').'</td>';

		// Status
		print '<td align="center">';
		if ($type_element == 'contract')
		{
			print $documentstaticline->getLibStatut(2);
		}
		else
		{
			print $documentstatic->getLibStatut(2);
		}
		print '</td>';

		print '<td>';

		// Define text, description and type
		$text=''; $description=''; $type=0;

		// Code to show product duplicated from commonobject->printObjectLine
		if ($objp->fk_product > 0)
		{
			$product_static = new Product($db);

			$product_static->type=$objp->fk_product_type;
			$product_static->id=$objp->fk_product;
			$product_static->ref=$objp->ref;
			$product_static->entity=$objp->pentity;
			$text=$product_static->getNomUrl(1);
		}

		// Product
		if ($objp->fk_product > 0)
		{
			// Define output language
			if (! empty($conf->global->MAIN_MULTILANGS) && ! empty($conf->global->PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE))
			{
				$prod = new Product($db);
				$prod->fetch($objp->fk_product);

				$outputlangs = $langs;
				$newlang='';
				if (empty($newlang) && GETPOST('lang_id','aZ09')) $newlang=GETPOST('lang_id','aZ09');
				if (empty($newlang)) $newlang=$object->default_lang;
				if (! empty($newlang))
				{
					$outputlangs = new Translate("",$conf);
					$outputlangs->setDefaultLang($newlang);
				}

				$label = (! empty($prod->multilangs[$outputlangs->defaultlang]["label"])) ? $prod->multilangs[$outputlangs->defaultlang]["label"] : $objp->product_label;
			}
			else
			{
				$label = $objp->product_label;
			}

			$text.= ' - '.(! empty($objp->label)?$objp->label:$label);
			$description=(! empty($conf->global->PRODUIT_DESC_IN_FORM)?'':dol_htmlentitiesbr($objp->description));
		}

		if (($objp->info_bits & 2) == 2) { ?>
			<a href="<?php echo DOL_URL_ROOT.'/comm/remx.php?id='.$object->id; ?>">
			<?php
			$txt='';
			print img_object($langs->trans("ShowReduc"),'reduc').' ';
			if ($objp->description == '(DEPOSIT)') $txt=$langs->trans("Deposit");
			elseif ($objp->description == '(EXCESS RECEIVED)') $txt=$langs->trans("ExcessReceived");
			elseif ($objp->description == '(EXCESS PAID)') $txt=$langs->trans("ExcessPaid");
			//else $txt=$langs->trans("Discount");
			print $txt;
			?>
			</a>
			<?php
			if ($objp->description)
			{
				if ($objp->description == '(CREDIT_NOTE)' && $objp->fk_remise_except > 0)
				{
					$discount=new DiscountAbsolute($db);
					$discount->fetch($objp->fk_remise_except);
					echo ($txt?' - ':'').$langs->transnoentities("DiscountFromCreditNote",$discount->getNomUrl(0));
				}
				if ($objp->description == '(EXCESS RECEIVED)' && $objp->fk_remise_except > 0)
				{
					$discount=new DiscountAbsolute($db);
					$discount->fetch($objp->fk_remise_except);
					echo ($txt?' - ':'').$langs->transnoentities("DiscountFromExcessReceived",$discount->getNomUrl(0));
				}
				elseif ($objp->description == '(EXCESS PAID)' && $objp->fk_remise_except > 0)
				{
					$discount=new DiscountAbsolute($db);
					$discount->fetch($objp->fk_remise_except);
					echo ($txt?' - ':'').$langs->transnoentities("DiscountFromExcessPaid",$discount->getNomUrl(0));
				}
				elseif ($objp->description == '(DEPOSIT)' && $objp->fk_remise_except > 0)
				{
					$discount=new DiscountAbsolute($db);
					$discount->fetch($objp->fk_remise_except);
					echo ($txt?' - ':'').$langs->transnoentities("DiscountFromDeposit",$discount->getNomUrl(0));
					// Add date of deposit
					if (! empty($conf->global->INVOICE_ADD_DEPOSIT_DATE)) echo ' ('.dol_print_date($discount->datec).')';
				}
				else
				{
					echo ($txt?' - ':'').dol_htmlentitiesbr($objp->description);
				}
			}
		}
		else
		{
			if ($objp->fk_product > 0) {

				echo $form->textwithtooltip($text,$description,3,'','',$i,0,'');

				// Show range
				echo get_date_range($objp->date_start, $objp->date_end);

				// Add description in form
				if (! empty($conf->global->PRODUIT_DESC_IN_FORM))
				{
					print (! empty($objp->description) && $objp->description!=$objp->product_label)?'<br>'.dol_htmlentitiesbr($objp->description):'';
				}
			} else {

				if (! empty($objp->label) || ! empty($objp->description))
				{
					if ($type==1) $text = img_object($langs->trans('Service'),'service');
					else $text = img_object($langs->trans('Product'),'product');

					if (! empty($objp->label)) {
						$text.= ' <strong>'.$objp->label.'</strong>';
						echo $form->textwithtooltip($text,dol_htmlentitiesbr($objp->description),3,'','',$i,0,'');
					} else {
						echo $text.' '.dol_htmlentitiesbr($objp->description);
					}
				}

				// Show range
				echo get_date_range($objp->date_start,$objp->date_end);
			}
		}

		/*
		$prodreftxt='';
		if ($objp->prod_id > 0)
		{
			$productstatic->id = $objp->prod_id;
			$productstatic->ref = $objp->prod_ref;
			$productstatic->status = $objp->prod_type;
			$prodreftxt = $productstatic->getNomUrl(0);
			if(!empty($objp->product_label)) $prodreftxt .= ' - '.$objp->product_label;
		}
		// Show range
		$prodreftxt .= get_date_range($objp->date_start, $objp->date_end);
		// Add description in form
		if (! empty($conf->global->PRODUIT_DESC_IN_FORM))
		{
			$prodreftxt .= (! empty($objp->description) && $objp->description!=$objp->product_label)?'<br>'.dol_htmlentitiesbr($objp->description):'';
		}
		*/
		print '</td>';

		//print '<td align="left">'.$prodreftxt.'</td>';

		print '<td align="right">'.$objp->prod_qty.'</td>';
		$total_qty+=$objp->prod_qty;

		print '<td align="right">'.price($objp->total_ht).'</td>';
		$total_ht+=$objp->total_ht;

		print '<td align="right">'.price($objp->total_ht/(empty($objp->prod_qty)?1:$objp->prod_qty)).'</td>';

		print "</tr>\n";
		$i++;
	}

	print '<tr class="liste_total">';
	print '<td>' . $langs->trans('Total') . '</td>';
	print '<td colspan="3"></td>';
	print '<td align="right">' . $total_qty . '</td>';
	print '<td align="right">' . price($total_ht) . '</td>';
	print '<td align="right">' . price($total_ht/(empty($total_qty)?1:$total_qty)) . '</td>';
	print "</table>";
	print '</div>';

	if ($num > $limit) {
		print_barre_liste('', $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder,'',$num);
	}
	$db->free($resql);
}
else if (empty($type_element) || $type_element == -1)
{
    print_barre_liste($langs->trans('ProductsIntoElements').' '.$typeElementString.' '.$button, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder,'',$num, '', '');

    print '<table class="liste" width="100%">'."\n";
    // Titles with sort buttons
    print '<tr class="liste_titre">';
    print_liste_field_titre('Ref',$_SERVER['PHP_SELF'],'doc_number','',$param,'align="left"',$sortfield,$sortorder);
    print_liste_field_titre('Date',$_SERVER['PHP_SELF'],'dateprint','',$param,'align="center" width="150"',$sortfield,$sortorder);
    print_liste_field_titre('Status',$_SERVER['PHP_SELF'],'fk_status','',$param,'align="center"',$sortfield,$sortorder);
    print_liste_field_titre('Product',$_SERVER['PHP_SELF'],'','',$param,'align="left"',$sortfield,$sortorder);
    print_liste_field_titre('Quantity',$_SERVER['PHP_SELF'],'prod_qty','',$param,'align="right"',$sortfield,$sortorder);
    print "</tr>\n";

	print '<tr class="oddeven"><td class="opacitymedium" colspan="5">'.$langs->trans("SelectElementAndClick", $langs->transnoentitiesnoconv("Search")).'</td></tr>';

	print "</table>";
}
else {
    print_barre_liste($langs->trans('ProductsIntoElements').' '.$typeElementString.' '.$button, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder,'',$num, '', '');

    print '<table class="liste" width="100%">'."\n";

	print '<tr class="oddeven"><td class="opacitymedium" colspan="5">'.$langs->trans("FeatureNotYetAvailable").'</td></tr>';

	print "</table>";
}

print "</form>";

// End of page
llxFooter();
$db->close();
