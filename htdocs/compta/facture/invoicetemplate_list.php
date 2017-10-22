<?php
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013      Florian Henry	    <florian.henry@open-concept.pro>
 * Copyright (C) 2013      Juanjo Menent	    <jmenent@2byte.es>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2012      Cedric Salvador      <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015      Alexandre Spangaro   <aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2016      Meziane Sof		<virtualsof@yahoo.fr>
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
 *	\file       htdocs/compta/facture/invoicetemplate_list.php
 *	\ingroup    facture
 *	\brief      Page to show list of template/recurring invoices
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture-rec.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
if (! empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
	//require_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';
}
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/invoice.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';

$langs->load('bills');
$langs->load('compta');
$langs->load('admin');
$langs->load('other');

$action     = GETPOST('action','alpha');
$massaction = GETPOST('massaction','alpha');
$show_files = GETPOST('show_files','int');
$confirm    = GETPOST('confirm','alpha');
$cancel     = GETPOST('cancel', 'alpha');
$toselect   = GETPOST('toselect', 'array');
$contextpage= GETPOST('contextpage','aZ')?GETPOST('contextpage','aZ'):'invoicetemplatelist';   // To manage different context of search

// Security check
$id=(GETPOST('facid','int')?GETPOST('facid','int'):GETPOST('id','int'));
$lineid=GETPOST('lineid','int');
$ref=GETPOST('ref','alpha');
if ($user->societe_id) $socid=$user->societe_id;
$objecttype = 'facture_rec';
if ($action == "create" || $action == "add") $objecttype = '';
$result = restrictedArea($user, 'facture', $id, $objecttype);
$projectid = GETPOST('projectid','int');

$search_ref=GETPOST('search_ref');
$search_societe=GETPOST('search_societe');
$search_montant_ht=GETPOST('search_montant_ht');
$search_montant_vat=GETPOST('search_montant_vat');
$search_montant_ttc=GETPOST('search_montant_ttc');
$search_payment_mode=GETPOST('search_payment_mode');
$search_payment_term=GETPOST('search_payment_term');
$day=GETPOST('day');
$year=GETPOST('year');
$month=GETPOST('month');
$day_date_when=GETPOST('day_date_when');
$year_date_when=GETPOST('year_date_when');
$month_date_when=GETPOST('month_date_when');
$search_recurring=GETPOST('search_recurring','int');
$search_frequency=GETPOST('search_frequency','alpha');
$search_unit_frequency=GETPOST('search_unit_frequency','alpha');

$limit = GETPOST('limit')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
if (! $sortorder) $sortorder='DESC';
if (! $sortfield) $sortfield='f.titre';
$pageprev = $page - 1;
$pagenext = $page + 1;

$object = new FactureRec($db);
if (($id > 0 || $ref) && $action != 'create' && $action != 'add')
{
	$ret = $object->fetch($id, $ref);
	if (!$ret)
	{
		setEventMessages($langs->trans("ErrorRecordNotFound"), null, 'errors');
	}
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('invoicereccard','globalcard'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('facture_rec');
$search_array_options=$extrafields->getOptionalsFromPost($extralabels,'','search_');

$permissionnote = $user->rights->facture->creer; // Used by the include of actions_setnotes.inc.php
$permissiondellink=$user->rights->facture->creer;	// Used by the include of actions_dellink.inc.php
$permissiontoedit = $user->rights->facture->creer; // Used by the include of actions_lineupdonw.inc.php

$arrayfields=array(
	'f.titre'=>array('label'=>$langs->trans("Ref"), 'checked'=>1),
	's.nom'=>array('label'=>$langs->trans("ThirdParty"), 'checked'=>1),
	'f.total'=>array('label'=>$langs->trans("AmountHT"), 'checked'=>1),
	'f.tva'=>array('label'=>$langs->trans("AmountVAT"), 'checked'=>1),
	'f.total_ttc'=>array('label'=>$langs->trans("AmountTTC"), 'checked'=>1),
	'f.fk_mode_reglement'=>array('label'=>$langs->trans("PaymentMode"), 'checked'=>0),
	'f.fk_cond_reglement'=>array('label'=>$langs->trans("PaymentTerm"), 'checked'=>0),
	'recurring'=>array('label'=>$langs->trans("RecurringInvoiceTemplate"), 'checked'=>1),
	'f.frequency'=>array('label'=>$langs->trans("Frequency"), 'checked'=>1),
	'f.unit_frequency'=>array('label'=>$langs->trans("FrequencyUnit"), 'checked'=>1),
	'f.nb_gen_done'=>array('label'=>$langs->trans("NbOfGenerationDoneShort"), 'checked'=>1),
	'f.date_last_gen'=>array('label'=>$langs->trans("DateLastGeneration"), 'checked'=>1),
	'f.date_when'=>array('label'=>$langs->trans("NextDateToExecution"), 'checked'=>1),
	'status'=>array('label'=>$langs->trans("Status"), 'checked'=>1, 'position'=>100),
	'f.datec'=>array('label'=>$langs->trans("DateCreation"), 'checked'=>0, 'position'=>500),
	'f.tms'=>array('label'=>$langs->trans("DateModificationShort"), 'checked'=>0, 'position'=>500),
);
// Extra fields
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
{
	foreach($extrafields->attribute_label as $key => $val)
	{
		if (! empty($extrafields->attribute_list[$key])) $arrayfields["ef.".$key]=array('label'=>$extrafields->attribute_label[$key], 'checked'=>(($extrafields->attribute_list[$key]<0)?0:1), 'position'=>$extrafields->attribute_pos[$key], 'enabled'=>$extrafields->attribute_perms[$key]);
	}
}


/*
 * Actions
 */

if (GETPOST('cancel','alpha')) { $action='list'; $massaction=''; }
if (! GETPOST('confirmmassaction','alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction=''; }

$parameters = array('socid' => $socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	if (GETPOST('cancel','alpha')) $action='';

	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Do we click on purge search criteria ?
	if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) // All test are required to be compatible with all browsers
	{
		$search_ref='';
		$search_societe='';
		$search_montant_ht='';
		$search_montant_vat='';
		$search_montant_ttc='';
		$search_payment_mode='';
		$search_payment_term='';
		$day='';
		$year='';
		$month='';
		$day_date_when='';
		$year_date_when='';
		$month_date_when='';
		$search_recurring='';
		$search_frequency='';
		$search_unit_frequency='';
		$search_array_options=array();
	}

	// Mass actions
	/*$objectclass='MyObject';
    $objectlabel='MyObject';
    $permtoread = $user->rights->mymodule->read;
    $permtodelete = $user->rights->mymodule->delete;
    $uploaddir = $conf->mymodule->dir_output;
    include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';*/

}


/*
 *	View
 */

llxHeader('',$langs->trans("RepeatableInvoices"),'ch-facture.html#s-fac-facture-rec');

$form = new Form($db);
$formother = new FormOther($db);
if (! empty($conf->projet->enabled)) { $formproject = new FormProjets($db); }
$companystatic = new Societe($db);
$invoicerectmp = new FactureRec($db);

$now = dol_now();
$tmparray=dol_getdate($now);
$today = dol_mktime(23,59,59,$tmparray['mon'],$tmparray['mday'],$tmparray['year']);   // Today is last second of current day


/*
 *  List mode
 */
$sql = "SELECT s.nom as name, s.rowid as socid, f.rowid as facid, f.titre, f.total, f.tva as total_vat, f.total_ttc, f.frequency, f.unit_frequency,";
$sql.= " f.nb_gen_done, f.nb_gen_max, f.date_last_gen, f.date_when,";
$sql.= " f.datec, f.tms,";
$sql.= " f.fk_cond_reglement, f.fk_mode_reglement";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture_rec as f";
if (! $user->rights->societe->client->voir && ! $socid) {
	$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
}
$sql.= " WHERE f.fk_soc = s.rowid";
$sql.= ' AND f.entity IN ('.getEntity('facture').')';
if (! $user->rights->societe->client->voir && ! $socid) {
	$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
}
if ($search_ref)                  $sql .= natural_search('f.titre', $search_ref);
if ($search_societe)              $sql .= natural_search('s.nom', $search_societe);
if ($search_montant_ht != '')     $sql .= natural_search('f.total', $search_montant_ht, 1);
if ($search_montant_vat != '')    $sql .= natural_search('f.tva', $search_montant_vat, 1);
if ($search_montant_ttc != '')    $sql .= natural_search('f.total_ttc', $search_montant_ttc, 1);
if (! empty($search_payment_mode) && $search_payment_mode != '-1')   $sql .= natural_search('f.fk_mode_reglement', $search_payment_mode, 1);
if (! empty($search_payment_term) && $search_payment_term != '-1')   $sql .= natural_search('f.fk_cond_reglement', $search_payment_term, 1);
if ($search_recurring == '1')     $sql .= ' AND f.frequency > 0';
if ($search_recurring == '0')     $sql .= ' AND (f.frequency IS NULL or f.frequency = 0)';
if ($search_frequency != '')      $sql .= natural_search('f.frequency', $search_frequency, 1);
if ($search_unit_frequency != '') $sql .= natural_search('f.unit_frequency', $search_unit_frequency);

if ($month > 0)
{
	if ($year > 0 && empty($day))
		$sql.= " AND f.date_last_gen BETWEEN '".$db->idate(dol_get_first_day($year,$month,false))."' AND '".$db->idate(dol_get_last_day($year,$month,false))."'";
	else if ($year > 0 && ! empty($day))
		$sql.= " AND f.date_last_gen BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month, $day, $year))."' AND '".$db->idate(dol_mktime(23, 59, 59, $month, $day, $year))."'";
	else
		$sql.= " AND date_format(f.date_last_gen, '%m') = '".$month."'";
}
else if ($year > 0)
{
	$sql.= " AND f.date_last_gen BETWEEN '".$db->idate(dol_get_first_day($year,1,false))."' AND '".$db->idate(dol_get_last_day($year,12,false))."'";
}
if ($month_date_when > 0)
{
	if ($year_date_when > 0 && empty($day_date_when))
		$sql.= " AND f.date_when BETWEEN '".$db->idate(dol_get_first_day($year_date_when,$month_date_when,false))."' AND '".$db->idate(dol_get_last_day($year_date_when,$month_date_when,false))."'";
	else if ($year_date_when > 0 && ! empty($day_date_when))
		$sql.= " AND f.date_date_when_reglement BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month_date_when, $day_date_when, $year_date_when))."' AND '".$db->idate(dol_mktime(23, 59, 59, $month_date_when, $day_date_when, $year_date_when))."'";
	else
		$sql.= " AND date_format(f.date_when, '%m') = '".$month_date_when."'";
}
else if ($year_date_when > 0)
{
	$sql.= " AND f.date_when BETWEEN '".$db->idate(dol_get_first_day($year_date_when,1,false))."' AND '".$db->idate(dol_get_last_day($year_date_when,12,false))."'";
}

$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
}

$sql.= $db->order($sortfield, $sortorder);
$sql.= $db->plimit($limit+1,$offset);

$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);

	$param='';
	if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.urlencode($contextpage);
	if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.urlencode($limit);
	if ($socid)              $param.='&socid='.urlencode($socid);
	if ($day)                $param.='&day='.urlencode($day);
	if ($month)              $param.='&month='.urlencode($month);
	if ($year)               $param.='&year=' .urlencode($year);
	if ($day_date_when)      $param.='&day_date_when='.urlencode($day_date_when);
	if ($month_date_when)    $param.='&month_date_when='.urlencode($month_date_when);
	if ($year_date_when)     $param.='&year_date_when=' .urlencode($year_date_when);
	if ($search_ref)         $param.='&search_ref=' .urlencode($search_ref);
	if ($search_societe)     $param.='&search_societe=' .urlencode($search_societe);
	if ($search_montant_ht != '')   $param.='&search_montant_ht=' .urlencode($search_montant_ht);
	if ($search_montant_vat != '')  $param.='&search_montant_vat='.urlencode($search_montant_vat);
	if ($search_montant_ttc != '')  $param.='&search_montant_ttc='.urlencode($search_montant_ttc);
	if ($search_payment_mode != '') $param.='&search_payment_mode='.urlencode($search_payment_mode);
	if ($search_payment_type != '') $param.='&search_payment_type='.urlencode($search_payment_type);
	if ($search_recurring != '' && $search_recurrning != '-1')    $param.='&search_recurring='  .urlencode($search_recurring);
	if ($search_frequency > 0)      $param.='&search_frequency='  .urlencode($search_frequency);
	if ($search_unit_frequency > 0) $param.='&search_unit_frequency='.urlencode($search_unit_frequency);
	if ($option)             $param.="&option=".urlencode($option);
	if ($optioncss != '')    $param.='&optioncss='.urlencode($optioncss);
	// Add $param from extra fields
	foreach ($search_array_options as $key => $val)
	{
		$crit=$val;
		$tmpkey=preg_replace('/search_options_/','',$key);
		if ($val != '') $param.='&search_options_'.$tmpkey.'='.urlencode($val);
	}

	$massactionbutton=$form->selectMassAction('', $massaction == 'presend' ? array() : array('presend'=>$langs->trans("SendByMail"), 'builddoc'=>$langs->trans("PDFMerge")));

	$varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;
	$selectedfields=$form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);	// This also change content of $arrayfields
	//$selectedfields.=$form->showCheckAddButtons('checkforselect', 1);

	print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
	print '<input type="hidden" name="viewstatut" value="'.$viewstatut.'">';

	print_barre_liste($langs->trans("RepeatableInvoices"),$page,$_SERVER['PHP_SELF'],$param,$sortfield,$sortorder,'',$num,$nbtotalofrecords,'title_accountancy.png',0,'','', $limit);

	print $langs->trans("ToCreateAPredefinedInvoice", $langs->transnoentitiesnoconv("ChangeIntoRepeatableInvoice")).'<br><br>';

	$i = 0;

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

	// Filters lines
	print '<tr class="liste_titre_filter">';
	// Ref
	if (! empty($arrayfields['f.titre']['checked']))
	{
		print '<td class="liste_titre" align="left">';
		print '<input class="flat" size="6" type="text" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
		print '</td>';
	}
	// Thirpdarty
	if (! empty($arrayfields['s.nom']['checked']))
	{
		print '<td class="liste_titre" align="left"><input class="flat" type="text" size="8" name="search_societe" value="'.dol_escape_htmltag($search_societe).'"></td>';
	}
	if (! empty($arrayfields['f.total']['checked']))
	{
		// Amount net
		print '<td class="liste_titre" align="right">';
		print '<input class="flat" type="text" size="5" name="search_montant_ht" value="'.dol_escape_htmltag($search_montant_ht).'">';
		print '</td>';
	}
	if (! empty($arrayfields['f.tva']['checked']))
	{
		// Amount Vat
		print '<td class="liste_titre" align="right">';
		print '<input class="flat" type="text" size="5" name="search_montant_vat" value="'.dol_escape_htmltag($search_montant_vat).'">';
		print '</td>';
	}
	if (! empty($arrayfields['f.total_ttc']['checked']))
	{
		// Amount
		print '<td class="liste_titre" align="right">';
		print '<input class="flat" type="text" size="5" name="search_montant_ttc" value="'.dol_escape_htmltag($search_montant_ttc).'">';
		print '</td>';
	}
	if (! empty($arrayfields['f.fk_cond_reglement']['checked']))
	{
		// Payment term
		print '<td class="liste_titre" align="right">';
		print $form->select_conditions_paiements($search_payment_term, 'search_payment_term', -1, 1, 1, 'maxwidth100');
		print "</td>";
	}
	if (! empty($arrayfields['f.fk_mode_reglement']['checked']))
	{
		// Payment mode
		print '<td class="liste_titre" align="right">';
		print $form->select_types_paiements($search_payment_mode, 'search_payment_mode', '', 0, 1, 1, 0, 1, 'maxwidth100');
		print '</td>';
	}
	if (! empty($arrayfields['recurring']['checked']))
	{
		// Recurring or not
		print '<td class="liste_titre" align="center">';
		print $form->selectyesno('search_recurring', $search_recurring, 1, false, 1);
		print '</td>';
	}
	if (! empty($arrayfields['f.frequency']['checked']))
	{
		// Recurring or not
		print '<td class="liste_titre" align="center">';
		print '<input class="flat" type="text" size="1" name="search_frequency" value="'.dol_escape_htmltag($search_frequency).'">';
		print '</td>';
	}
	if (! empty($arrayfields['f.unit_frequency']['checked']))
	{
		// Frequency unit
		print '<td class="liste_titre" align="center">';
		print '<input class="flat" type="text" size="1" name="search_unit_frequency" value="'.dol_escape_htmltag($search_unit_frequency).'">';
		print '</td>';
	}
	if (! empty($arrayfields['f.nb_gen_done']['checked']))
	{
		// Nb generation
		print '<td class="liste_titre" align="center">';
		print '</td>';
	}
	// Date invoice
	if (! empty($arrayfields['f.date_last_gen']['checked']))
	{
		print '<td class="liste_titre" align="center">';
		if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat" type="text" size="1" maxlength="2" name="day" value="'.$day.'">';
		print '<input class="flat" type="text" size="1" maxlength="2" name="month" value="'.$month.'">';
		$formother->select_year($year?$year:-1,'year',1, 20, 5);
		print '</td>';
	}
	// Date due
	if (! empty($arrayfields['f.date_when']['checked']))
	{
		print '<td class="liste_titre" align="center">';
		if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat" type="text" size="1" maxlength="2" name="day_date_when" value="'.$day_date_when.'">';
		print '<input class="flat" type="text" size="1" maxlength="2" name="month_date_when" value="'.$month_date_when.'">';
		$formother->select_year($year_date_when?$year_date_when:-1,'year_date_when',1, 20, 5);
		print '</td>';
	}
	// Extra fields
	if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
	{
		foreach($extrafields->attribute_label as $key => $val)
		{
			if (! empty($arrayfields["ef.".$key]['checked']))
			{
				$align=$extrafields->getAlignFlag($key);
				$typeofextrafield=$extrafields->attribute_type[$key];
				print '<td class="liste_titre'.($align?' '.$align:'').'">';
				if (in_array($typeofextrafield, array('varchar', 'int', 'double', 'select')))
				{
					$crit=$val;
					$tmpkey=preg_replace('/search_options_/','',$key);
					$searchclass='';
					if (in_array($typeofextrafield, array('varchar', 'select'))) $searchclass='searchstring';
					if (in_array($typeofextrafield, array('int', 'double'))) $searchclass='searchnum';
					print '<input class="flat'.($searchclass?' '.$searchclass:'').'" size="4" type="text" name="search_options_'.$tmpkey.'" value="'.dol_escape_htmltag($search_array_options['search_options_'.$tmpkey]).'">';
				}
				print '</td>';
			}
		}
	}
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
	if (! empty($arrayfields['status']['checked']))
	{
		print '<td class="liste_titre" align="center">';
		print '</td>';
	}
	// Action column
	print '<td class="liste_titre" align="middle">';
	$searchpicto=$form->showFilterAndCheckAddButtons(0, 'checkforselect', 1);
	print $searchpicto;
	print '</td>';
	print "</tr>\n";


	print '<tr class="liste_titre">';
	if (! empty($arrayfields['f.titre']['checked']))         print_liste_field_titre($arrayfields['f.titre']['label'],$_SERVER['PHP_SELF'],"f.titre","",$param,"",$sortfield,$sortorder);
	if (! empty($arrayfields['s.nom']['checked']))           print_liste_field_titre($arrayfields['s.nom']['label'],$_SERVER['PHP_SELF'],"s.nom","",$param,"",$sortfield,$sortorder);
	if (! empty($arrayfields['f.total']['checked']))         print_liste_field_titre($arrayfields['f.total']['label'],$_SERVER['PHP_SELF'],"f.total","",$param,'align="right"',$sortfield,$sortorder);
	if (! empty($arrayfields['f.tva']['checked']))           print_liste_field_titre($arrayfields['f.tva']['label'],$_SERVER['PHP_SELF'],"f.tva","",$param,'align="right"',$sortfield,$sortorder);
	if (! empty($arrayfields['f.total_ttc']['checked']))     print_liste_field_titre($arrayfields['f.total_ttc']['label'],$_SERVER['PHP_SELF'],"f.total_ttc","",$param,'align="right"',$sortfield,$sortorder);
	if (! empty($arrayfields['f.fk_cond_reglement']['checked']))     print_liste_field_titre($arrayfields['f.fk_cond_reglement']['label'],$_SERVER['PHP_SELF'],"f.fk_cond_reglement","",$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['f.fk_mode_reglement']['checked']))     print_liste_field_titre($arrayfields['f.fk_mode_reglement']['label'],$_SERVER['PHP_SELF'],"f.fk_mode_reglement","",$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['recurring']['checked']))       print_liste_field_titre($arrayfields['recurring']['label'],$_SERVER['PHP_SELF'],"recurring","",$param,'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['f.frequency']['checked']))     print_liste_field_titre($arrayfields['f.frequency']['label'],$_SERVER['PHP_SELF'],"f.frequency","",$param,'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['f.unit_frequency']['checked'])) print_liste_field_titre($arrayfields['f.unit_frequency']['label'],$_SERVER['PHP_SELF'],"f.unit_frequency","",$param,'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['f.nb_gen_done']['checked']))   print_liste_field_titre($arrayfields['f.nb_gen_done']['label'],$_SERVER['PHP_SELF'],"f.nb_gen_done","",$param,'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['f.date_last_gen']['checked'])) print_liste_field_titre($arrayfields['f.date_last_gen']['label'],$_SERVER['PHP_SELF'],"f.date_last_gen","",$param,'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['f.date_when']['checked']))     print_liste_field_titre($arrayfields['f.date_when']['label'],$_SERVER['PHP_SELF'],"f.date_when","",$param,'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['f.datec']['checked']))         print_liste_field_titre($arrayfields['f.datec']['label'],$_SERVER['PHP_SELF'],"f.datec","",$param,'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['f.tms']['checked']))           print_liste_field_titre($arrayfields['f.tms']['label'],$_SERVER['PHP_SELF'],"f.tms","",$param,'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['status']['checked']))          print_liste_field_titre($arrayfields['status']['label'],$_SERVER['PHP_SELF'],"","",$param,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"],"",'','','align="center"',$sortfield,$sortorder,'nomaxwidthsearch ')."\n";
	print "</tr>\n";

	if ($num > 0)
	{
		$i=0;
		$totalarray=array();
		while ($i < min($num,$limit))
		{
			$objp = $db->fetch_object($resql);
			if (empty($objp)) break;

			$companystatic->id=$objp->socid;
			$companystatic->name=$objp->name;

			$invoicerectmp->id=$objp->id;
			$invoicerectmp->frequency=$objp->frequency;
			$invoicerectmp->suspend=$objp->suspend;
			$invoicerectmp->unit_frequency=$objp->unit_frequency;
			$invoicerectmp->nb_gen_max=$objp->nb_gen_max;
			$invoicerectmp->nb_gen_done=$objp->nb_gen_done;

			print '<tr class="oddeven">';

			if (! empty($arrayfields['f.titre']['checked']))
			{
			   print '<td><a href="'.DOL_URL_ROOT.'/compta/facture/fiche-rec.php?id='.$objp->facid.'">'.img_object($langs->trans("ShowBill"),"bill").' '.$objp->titre;
			   print "</a></td>\n";
			   if (! $i) $totalarray['nbfield']++;
			}
			if (! empty($arrayfields['s.nom']['checked']))
			{
			   print '<td class="tdoverflowmax200">'.$companystatic->getNomUrl(1,'customer').'</td>';
			   if (! $i) $totalarray['nbfield']++;
			}
			if (! empty($arrayfields['f.total']['checked']))
			{
			   print '<td align="right">'.price($objp->total).'</td>'."\n";
			   if (! $i) $totalarray['nbfield']++;
			   if (! $i) $totalarray['pos'][$totalarray['nbfield']]='f.total';
			   $totalarray['val']['f.total'] += $objp->total;
			}
			if (! empty($arrayfields['f.tva']['checked']))
			{
			   print '<td align="right">'.price($objp->total_vat).'</td>'."\n";
			   if (! $i) $totalarray['nbfield']++;
			   if (! $i) $totalarray['pos'][$totalarray['nbfield']]='f.tva';
			   $totalarray['val']['f.tva'] += $objp->total_vat;
			}
			if (! empty($arrayfields['f.total_ttc']['checked']))
			{
			   print '<td align="right">'.price($objp->total_ttc).'</td>'."\n";
			   if (! $i) $totalarray['nbfield']++;
			   if (! $i) $totalarray['pos'][$totalarray['nbfield']]='f.total_ttc';
			   $totalarray['val']['f.total_ttc'] += $objp->total_ttc;
			}
			// Payment term
			if (! empty($arrayfields['f.fk_cond_reglement']['checked']))
			{
			   print '<td align="right">';
			   print $form->form_conditions_reglement('', $objp->fk_cond_reglement, 'none');
			   print '</td>'."\n";
			   if (! $i) $totalarray['nbfield']++;
			}
			// Payment mode
			if (! empty($arrayfields['f.fk_mode_reglement']['checked']))
			{
			   print '<td align="right">';
			   print $form->form_modes_reglement('', $objp->fk_mode_reglement, 'none');
			   print '</td>'."\n";
			   if (! $i) $totalarray['nbfield']++;
			}
			if (! empty($arrayfields['recurring']['checked']))
			{
			   print '<td align="center">'.yn($objp->frequency?1:0).'</td>';
			   if (! $i) $totalarray['nbfield']++;
			}
			if (! empty($arrayfields['f.frequency']['checked']))
			{
			   print '<td align="center">'.($objp->frequency > 0 ? $objp->frequency : '').'</td>';
			   if (! $i) $totalarray['nbfield']++;
			}
			if (! empty($arrayfields['f.unit_frequency']['checked']))
			{
			   print '<td align="center">'.($objp->frequency > 0 ? $objp->unit_frequency : '').'</td>';
			   if (! $i) $totalarray['nbfield']++;
			}
			if (! empty($arrayfields['f.nb_gen_done']['checked']))
			{
				print '<td align="center">';
				print ($objp->frequency ? $objp->nb_gen_done.($objp->nb_gen_max>0?' / '. $objp->nb_gen_max:'') : '<span class="opacitymedium">'.$langs->trans('NA').'</span>');
				print '</td>';
				if (! $i) $totalarray['nbfield']++;
			}
			if (! empty($arrayfields['f.date_last_gen']['checked']))
			{
			   print '<td align="center">';
			   print ($objp->frequency ? dol_print_date($db->jdate($objp->date_last_gen),'day') : '<span class="opacitymedium">'.$langs->trans('NA').'</span>');
			   print '</td>';
			   if (! $i) $totalarray['nbfield']++;
			}
			if (! empty($arrayfields['f.date_when']['checked']))
			{
			   print '<td align="center">';
			   print ($objp->frequency ? ($invoicerectmp->isMaxNbGenReached()?'<strike>':'').dol_print_date($db->jdate($objp->date_when),'day').($invoicerectmp->isMaxNbGenReached()?'</strike>':'') : '<span class="opacitymedium">'.$langs->trans('NA').'</span>');
			   print '</td>';
			   if (! $i) $totalarray['nbfield']++;
			}
			if (! empty($arrayfields['f.datec']['checked']))
			{
			   print '<td align="center">';
			   print dol_print_date($db->jdate($objp->datec),'dayhour');
			   print '</td>';
			   if (! $i) $totalarray['nbfield']++;
			}
			if (! empty($arrayfields['f.tms']['checked']))
			{
			   print '<td align="center">';
			   print dol_print_date($db->jdate($objp->tms),'dayhour');
			   print '</td>';
			   if (! $i) $totalarray['nbfield']++;
			}
			if (! empty($arrayfields['status']['checked']))
			{
			   print '<td align="center">';
			   print $invoicerectmp->getLibStatut(3,0);
			   print '</td>';
			   if (! $i) $totalarray['nbfield']++;
			}
			// Action column
			print '<td align="center">';
			if ($user->rights->facture->creer)
			{
				if (empty($objp->frequency) || $db->jdate($objp->date_when) <= $today)
				{
					print '<a href="'.DOL_URL_ROOT.'/compta/facture/card.php?action=create&amp;socid='.$objp->socid.'&amp;fac_rec='.$objp->facid.'">';
					print $langs->trans("CreateBill").'</a>';
				}
				else
				{
					print $langs->trans("DateIsNotEnough");
				}
			}
			else
			{
				print "&nbsp;";
			}
			if (! $i) $totalarray['nbfield']++;
			print "</td>";

			print "</tr>\n";

			$i++;
		}
	}
	else
	{
		$colspan=1;
		foreach($arrayfields as $key => $val) { if (! empty($val['checked'])) $colspan++; }
		print '<tr><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("NoRecordFound").'</td></tr>';
	}

	//var_dump($totalarray);
	// Show total line
	if (isset($totalarray['pos']))
	{
		print '<tr class="liste_total">';
		$i=0;
		while ($i < $totalarray['nbfield'])
		{
			$i++;
			if (! empty($totalarray['pos'][$i]))  print '<td align="right">'.price($totalarray['val'][$totalarray['pos'][$i]]).'</td>';
			else
			{
				if ($i == 1)
				{
					if ($num < $limit) print '<td align="left">'.$langs->trans("Total").'</td>';
					else print '<td align="left">'.$langs->trans("Totalforthispage").'</td>';
				}
				else print '<td></td>';
			}
		}
		print '</tr>';
	}

	print "</table>";
	print "</div>";
	print "</form>";

	$db->free($resql);
}
else
{
	dol_print_error($db);
}

llxFooter();

$db->close();
