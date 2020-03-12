<?php
/* Copyright (C) 2003     	Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2017	Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004     	Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2009	Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015       Alexandre Spangaro   <aspangaro@open-dsi.fr>
 * Copyright (C) 2018       Ferran Marcet	     <fmarcet@2byte.es>
 * Copyright (C) 2018       Charlene Benke       <charlie@patas-monkey.com>
 * Copyright (C) 2019       Juanjo Menent		 <jmenent@2byte.es>
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
 *	    \file       htdocs/expensereport/list.php
 *      \ingroup    expensereport
 *		\brief      list of expense reports
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport_ik.class.php';

// Load translation files required by the page
$langs->loadLangs(array('companies', 'users', 'trips'));

$action=GETPOST('action', 'aZ09');
$massaction=GETPOST('massaction', 'alpha');
$show_files=GETPOST('show_files', 'int');
$confirm=GETPOST('confirm', 'alpha');
$toselect = GETPOST('toselect', 'array');
$contextpage=GETPOST('contextpage', 'aZ')?GETPOST('contextpage', 'aZ'):'expensereportlist';

$childids = $user->getAllChildIds(1);

// Security check
$socid = GETPOST('socid', 'int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'expensereport', '', '');
$id = GETPOST('id', 'int');
// If we are on the view of a specific user
if ($id > 0)
{
    $canread=0;
    if ($id == $user->id) $canread=1;
    if (! empty($user->rights->expensereport->readall)) $canread=1;
    if (! empty($user->rights->expensereport->lire) && in_array($id, $childids)) $canread=1;
    if (! $canread)
    {
        accessforbidden();
    }
}

$diroutputmassaction=$conf->expensereport->dir_output . '/temp/massgeneration/'.$user->id;


// Load variable for pagination
$limit = GETPOST('limit', 'int')?GETPOST('limit', 'int'):$conf->liste_limit;
$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page', 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder) $sortorder="DESC";
if (!$sortfield) $sortfield="d.date_debut";


$sall         = trim((GETPOST('search_all', 'alphanohtml')!='')?GETPOST('search_all', 'alphanohtml'):GETPOST('sall', 'alphanohtml'));
$search_ref   = GETPOST('search_ref', 'alpha');
$search_user  = GETPOST('search_user', 'int');
$search_amount_ht = GETPOST('search_amount_ht', 'alpha');
$search_amount_vat = GETPOST('search_amount_vat', 'alpha');
$search_amount_ttc = GETPOST('search_amount_ttc', 'alpha');
$search_status = (GETPOST('search_status', 'intcomma')!=''?GETPOST('search_status', 'intcomma'):GETPOST('statut', 'intcomma'));
$month_start  = GETPOST("month_start", "int");
$year_start   = GETPOST("year_start", "int");
$day_start    = GETPOST("day_start", "int");
$day_end      = GETPOST("day_end", "int");
$month_end    = GETPOST("month_end", "int");
$year_end     = GETPOST("year_end", "int");
$optioncss    = GETPOST('optioncss', 'alpha');

if ($search_status == '') $search_status=-1;
if ($search_user == '') $search_user=-1;

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$object = new ExpenseReport($db);
$hookmanager->initHooks(array('expensereportlist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('expensereport');
$search_array_options=$extrafields->getOptionalsFromPost($object->table_element, '', 'search_');


// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
    'd.ref'=>'Ref',
    'd.note_public'=>"NotePublic",
    'u.lastname'=>'Lastname',
    'u.firstname'=>"Firstname",
    'u.login'=>"Login",
);
if (empty($user->socid)) $fieldstosearchall["d.note_private"]="NotePrivate";

$arrayfields=array(
    'd.ref'=>array('label'=>$langs->trans("Ref"), 'checked'=>1),
    'user'=>array('label'=>$langs->trans("User"), 'checked'=>1),
    'd.date_debut'=>array('label'=>$langs->trans("DateStart"), 'checked'=>1),
    'd.date_fin'=>array('label'=>$langs->trans("DateEnd"), 'checked'=>1),
    'd.date_valid'=>array('label'=>$langs->trans("DateValidation"), 'checked'=>1),
    'd.date_approve'=>array('label'=>$langs->trans("DateApprove"), 'checked'=>1),
    'd.total_ht'=>array('label'=>$langs->trans("AmountHT"), 'checked'=>1),
    'd.total_vat'=>array('label'=>$langs->trans("AmountVAT"), 'checked'=>1),
    'd.total_ttc'=>array('label'=>$langs->trans("AmountTTC"), 'checked'=>1),
    'd.date_create'=>array('label'=>$langs->trans("DateCreation"), 'checked'=>0, 'position'=>500),
    'd.tms'=>array('label'=>$langs->trans("DateModificationShort"), 'checked'=>0, 'position'=>500),
    'd.fk_statut'=>array('label'=>$langs->trans("Status"), 'checked'=>1, 'position'=>1000),
);
// Extra fields
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
{
    foreach($extrafields->attribute_label as $key => $val)
    {
		if (! empty($extrafields->attribute_list[$key])) $arrayfields["ef.".$key]=array('label'=>$extrafields->attribute_label[$key], 'checked'=>(($extrafields->attribute_list[$key]<0)?0:1), 'position'=>$extrafields->attribute_pos[$key], 'enabled'=>(abs($extrafields->attribute_list[$key])!=3 && $extrafields->attribute_perms[$key]));
    }
}

$canedituser=(! empty($user->admin) || $user->rights->user->user->creer);

$object = new ExpenseReport($db);
$objectuser = new User($db);


/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) { $action='list'; $massaction=''; }
if (! GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction=''; }

$parameters=array('socid'=>$socid);
$reshook=$hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

if (empty($reshook))
{
	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha'))		// Both test must be present to be compatible with all browsers
	{
	    $search_ref="";
	    $search_user="";
	    $search_amount_ht="";
	    $search_amount_vat="";
	    $search_amount_ttc="";
	    $search_status="";
	    $month_start="";
	    $year_start="";
		$day ="";
	    $month_end="";
	    $year_end="";
	    $day_end = "";
	    $toselect='';
	    $search_array_options=array();
	}
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')
		|| GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha'))
	{
		$massaction='';     // Protection to avoid mass action if we force a new search during a mass action confirmation
	}

	// Mass actions
    $objectclass='ExpenseReport';
    $objectlabel='ExpenseReport';
    $permtoread = $user->rights->expensereport->lire;
    $permtodelete = $user->rights->expensereport->supprimer;
    $uploaddir = $conf->expensereport->dir_output;
    include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';

    if ($action == 'update' && ! $cancel)
    {
    	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

    	if ($canedituser)    // Case we can edit all field
    	{
    		$error = 0;

    		if (!$error)
    		{
    			$objectuser->fetch($id);

    			$objectuser->oldcopy = clone $objectuser;

    			$db->begin();

    			$objectuser->default_range = GETPOST('default_range');
    			$objectuser->default_c_exp_tax_cat = GETPOST('default_c_exp_tax_cat');

    			if (!$error) {
    				$ret = $objectuser->update($user);
    				if ($ret < 0) {
    					$error++;
    					if ($db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
    						$langs->load("errors");
    						setEventMessages($langs->trans("ErrorLoginAlreadyExists", $objectuser->login), null, 'errors');
    					}
    					else
    					{
    						setEventMessages($objectuser->error, $objectuser->errors, 'errors');
    					}
    				}
    			}

    			if (!$error && !count($objectuser->errors)) {
    				setEventMessages($langs->trans("UserModified"), null, 'mesgs');
    				$db->commit();
    			}
    			else {
    				$db->rollback();
    			}
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

$fuser = new User($db);

$title = $langs->trans("ListOfTrips");
llxHeader('', $title);

$max_year = 5;
$min_year = 5;

// Récupération de l'ID de l'utilisateur
$user_id = $user->id;

if ($id > 0)
{
	// Charge utilisateur edite
	$fuser->fetch($id, '', '', 1);
	$fuser->getrights();
	$user_id = $fuser->id;

	$search_user = $user_id;
}

$sql = "SELECT d.rowid, d.ref, d.fk_user_author, d.total_ht, d.total_tva, d.total_ttc, d.fk_statut as status,";
$sql.= " d.date_debut, d.date_fin, d.date_create, d.tms as date_modif, d.date_valid, d.date_approve, d.note_private, d.note_public,";
$sql.= " u.rowid as id_user, u.firstname, u.lastname, u.login, u.email, u.statut, u.photo";
// Add fields from extrafields
if (is_array($extrafields->attributes['expensereport']['type'])) {
	foreach ($extrafields->attributes['expensereport']['type'] as $key => $val) $sql.=($extrafields->attribute_type[$key] != 'separate' ? ",ef.".$key.' as options_'.$key : '');
}
// Add fields from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListSelect', $parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql.= " FROM ".MAIN_DB_PREFIX."expensereport as d";
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."expensereport_extrafields as ef on (d.rowid = ef.fk_object)";
$sql.= ", ".MAIN_DB_PREFIX."user as u";
$sql.= " WHERE d.fk_user_author = u.rowid AND d.entity IN (".getEntity('expensereport').")";
// Search all
if (!empty($sall)) $sql.= natural_search(array_keys($fieldstosearchall), $sall);
// Ref
if (!empty($search_ref)) $sql.= natural_search('d.ref', $search_ref);
// Date Start
$sql.= dolSqlDateFilter("d.date_debut",	$day_start, $month_start, $year_start);
// Date End
$sql.= dolSqlDateFilter("d.date_fin", $day_end, $month_end, $year_end);

if ($search_amount_ht != '') $sql.= natural_search('d.total_ht', $search_amount_ht, 1);
if ($search_amount_ttc != '') $sql.= natural_search('d.total_ttc', $search_amount_ttc, 1);
// User
if ($search_user != '' && $search_user >= 0) $sql.= " AND u.rowid = '".$db->escape($search_user)."'";
// Status
if ($search_status != '' && $search_status >= 0) $sql.=" AND d.fk_statut IN (".$db->escape($search_status).")";
// RESTRICT RIGHTS
if (empty($user->rights->expensereport->readall) && empty($user->rights->expensereport->lire_tous)
    && (empty($conf->global->MAIN_USE_ADVANCED_PERMS) || empty($user->rights->expensereport->writeall_advance)))
{
	$sql.= " AND d.fk_user_author IN (".join(',', $childids).")\n";
}
// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListWhere', $parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;

$sql.= $db->order($sortfield, $sortorder);

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
    $result = $db->query($sql);
    $nbtotalofrecords = $db->num_rows($result);
    if (($page * $limit) > $nbtotalofrecords)	// if total resultset is smaller then paging size (filtering), goto and load page 0
    {
    	$page = 0;
    	$offset = 0;
    }
}

$sql.= $db->plimit($limit+1, $offset);

//print $sql;
$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);

	$arrayofselected=is_array($toselect)?$toselect:array();

	$param='';
    if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.$contextpage;
	if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;
	if ($sall)					$param.="&sall=".$sall;
	if ($search_ref)			$param.="&search_ref=".$search_ref;
	if ($search_user)			$param.="&search_user=".$search_user;
	if ($search_amount_ht)		$param.="&search_amount_ht=".$search_amount_ht;
	if ($search_amount_ttc)		$param.="&search_amount_ttc=".$search_amount_ttc;
	if ($search_status >= 0)  	$param.="&search_status=".$search_status;
	if ($optioncss != '')       $param.='&optioncss='.$optioncss;
	// Add $param from extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

	// List of mass actions available
	$arrayofmassactions =  array(
		'generate_doc'=>$langs->trans("ReGeneratePDF"),
	    'builddoc'=>$langs->trans("PDFMerge"),
	    'presend'=>$langs->trans("SendByMail"),
	);
	if ($user->rights->expensereport->supprimer) $arrayofmassactions['predelete']='<span class="fa fa-trash paddingrightonly"></span>'.$langs->trans("Delete");
	if (in_array($massaction, array('presend','predelete'))) $arrayofmassactions=array();
	$massactionbutton=$form->selectMassAction('', $arrayofmassactions);

	// Lines of title fields
	print '<form id="searchFormList" action="'.$_SERVER["PHP_SELF"].'" method="POST">'."\n";
    if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="'.($action=='edit'?'update':'list').'">';
    print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
    print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	if ($id > 0) print '<input type="hidden" name="id" value="'.$id.'">';

	if ($id > 0)		// For user tab
	{
		$title = $langs->trans("User");
		$linkback = '<a href="'.DOL_URL_ROOT.'/user/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
		$head = user_prepare_head($fuser);

		dol_fiche_head($head, 'expensereport', $title, -1, 'user');

		dol_banner_tab($fuser, 'id', $linkback, $user->rights->user->user->lire || $user->admin);

		print '<div class="fichecenter">';
		print '<div class="underbanner clearboth"></div>';

		if (!empty($conf->global->MAIN_USE_EXPENSE_IK))
		{
			print '<table class="border centpercent">';

			if ($action == 'edit')
			{
				print '<tr><td class="titlefield">'.$langs->trans("DefaultCategoryCar").'</td>';
				print '<td>';
				print $form->selectExpenseCategories($fuser->default_c_exp_tax_cat, 'default_c_exp_tax_cat', 1);
				print '</td></tr>';

				print '<tr><td>'.$langs->trans("DefaultRangeNumber").'</td>';
				print '<td>';
				$maxRangeNum = ExpenseReportIk::getMaxRangeNumber($fuser->default_c_exp_tax_cat);
				print $form->selectarray('default_range', range(0, $maxRangeNum), $fuser->default_range);
				print '</td></tr>';
			}
			else
			{
				print '<tr><td class="titlefield">'.$langs->trans("DefaultCategoryCar").'</td>';
				print '<td class="fk_c_exp_tax_cat">';
				print dol_getIdFromCode($db, $fuser->default_c_exp_tax_cat, 'c_exp_tax_cat', 'rowid', 'label');
				print '</td></tr>';

				print '<tr><td>'.$langs->trans("DefaultRangeNumber").'</td>';
				print '<td>';
				print $fuser->default_range;
				print '</td></tr>';
			}

			print '</table>';
		}

		print '</div>';

		/*if (empty($conf->global->HOLIDAY_HIDE_BALANCE))
		{
			print '<div class="underbanner clearboth"></div>';

			print '<br>';

			showMyBalance($holiday, $user_id);
		}*/

		dol_fiche_end();

		if ($action != 'edit')
		{
			print '<div class="tabsAction">';

			if (!empty($conf->global->MAIN_USE_EXPENSE_IK))
			{
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=edit&id='.$user_id.'" class="butAction">'.$langs->trans("Modify").'</a>';
			}

			$childids = $user->getAllChildIds(1);

			$canedit=((in_array($user_id, $childids) && $user->rights->expensereport->creer)
				|| ($conf->global->MAIN_USE_ADVANCED_PERMS && $user->rights->expensereport->writeall_advance));

			// Buttons for actions
			if ($canedit)
			{
				print '<a href="'.DOL_URL_ROOT.'/expensereport/card.php?action=create&fk_user_author='.$fuser->id.'" class="butAction">'.$langs->trans("AddTrip").'</a>';
			}

			print '</div>';
		}
		else
		{
			print '<div class="center">';
			print '<input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
			print '</div><br>';
		}
	}
	else
	{
		$title = $langs->trans("ListTripsAndExpenses");

		$newcardbutton='';
		if ($user->rights->expensereport->creer)
		{
            $newcardbutton.= dolGetButtonTitle($langs->trans('NewTrip'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/expensereport/card.php?action=create');
		}

		print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'title_generic.png', 0, $newcardbutton, '', $limit);
	}

	$topicmail="SendExpenseReport";
	$modelmail="expensereport";
	$objecttmp=new ExpenseReport($db);
	$trackid='exp'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

	if ($sall)
    {
        foreach($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
        print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $sall) . join(', ', $fieldstosearchall).'</div>';
    }

	$moreforfilter='';

	$parameters=array();
	$reshook=$hookmanager->executeHooks('printFieldPreListTitle', $parameters);    // Note that $action and $object may have been modified by hook
	if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
	else $moreforfilter = $hookmanager->resPrint;

	if (! empty($moreforfilter))
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

	// Filters
	print '<tr class="liste_titre_filter">';
	if (! empty($arrayfields['d.ref']['checked']))
	{
    	print '<td class="liste_titre" align="left">';
    	print '<input class="flat" size="15" type="text" name="search_ref" value="'.$search_ref.'">';
        print '</td>';
	}
	// User
	if (! empty($arrayfields['user']['checked']))
	{
        if ($user->rights->expensereport->readall || $user->rights->expensereport->lire_tous)
    	{
    		print '<td class="liste_titre maxwidthonspartphone" align="left">';
    		print $form->select_dolusers($search_user, 'search_user', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth200');
    		print '</td>';
    	} else {
    		print '<td class="liste_titre">&nbsp;</td>';
    	}
	}
	// Date start
	if (! empty($arrayfields['d.date_debut']['checked']))
	{
    	print '<td class="liste_titre" align="center">';
	if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY))
		print '<input class="flat width25" type="text" maxlength="2" name="day_start" value="'.dol_escape_htmltag($day_start).'">';

    	print '<input class="flat valignmiddle" type="text" size="1" maxlength="2" name="month_start" value="'.$month_start.'">';
    	$formother->select_year($year_start, 'year_start', 1, $min_year, $max_year);
    	print '</td>';
	}
	// Date end
	if (! empty($arrayfields['d.date_fin']['checked']))
	{
    	print '<td class="liste_titre" align="center">';
	if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY))
		print '<input class="flat width25" type="text" maxlength="2" name="day_end" value="'.dol_escape_htmltag($day_end).'">';
	print '<input class="flat valignmiddle" type="text" size="1" maxlength="2" name="month_end" value="'.$month_end.'">';
    	$formother->select_year($year_end, 'year_end', 1, $min_year, $max_year);
    	print '</td>';
    }
	// Date valid
	if (! empty($arrayfields['d.date_valid']['checked']))
	{
    	print '<td class="liste_titre" align="center">';
    	//print '<input class="flat" type="text" size="1" maxlength="2" name="month_end" value="'.$month_end.'">';
    	//$formother->select_year($year_end,'year_end',1, $min_year, $max_year);
    	print '</td>';
    }
	// Date approve
	if (! empty($arrayfields['d.date_approve']['checked']))
	{
    	print '<td class="liste_titre" align="center">';
    	//print '<input class="flat" type="text" size="1" maxlength="2" name="month_end" value="'.$month_end.'">';
    	//$formother->select_year($year_end,'year_end',1, $min_year, $max_year);
    	print '</td>';
    }
    // Amount with no tax
	if (! empty($arrayfields['d.total_ht']['checked']))
	{
    	print '<td class="liste_titre right"><input class="flat" type="text" size="5" name="search_amount_ht" value="'.$search_amount_ht.'"></td>';
	}
	if (! empty($arrayfields['d.total_vat']['checked']))
	{
	   print '<td class="liste_titre right"><input class="flat" type="text" size="5" name="search_amount_vat" value="'.$search_amount_vat.'"></td>';
	}
	// Amount with all taxes
	if (! empty($arrayfields['d.total_ttc']['checked']))
	{
	   print '<td class="liste_titre right"><input class="flat" type="text" size="5" name="search_amount_ttc" value="'.$search_amount_ttc.'"></td>';
	}
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

	// Fields from hook
	$parameters=array('arrayfields'=>$arrayfields);
	$reshook=$hookmanager->executeHooks('printFieldListOption', $parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Date creation
	if (! empty($arrayfields['d.date_create']['checked']))
	{
	    print '<td class="liste_titre">';
	    print '</td>';
	}
	// Date modification
	if (! empty($arrayfields['d.tms']['checked']))
	{
	    print '<td class="liste_titre">';
	    print '</td>';
	}
	// Status
	if (! empty($arrayfields['d.fk_statut']['checked']))
	{
    	print '<td class="liste_titre right">';
    	select_expensereport_statut($search_status, 'search_status', 1, 1);
    	print '</td>';
	}
	// Action column
	print '<td class="liste_titre middle">';
	$searchpicto=$form->showFilterButtons();
	print $searchpicto;
	print '</td>';

	print "</tr>\n";

	print '<tr class="liste_titre">';
	if (! empty($arrayfields['d.ref']['checked']))                  print_liste_field_titre($arrayfields['d.ref']['label'], $_SERVER["PHP_SELF"], "d.ref", "", $param, '', $sortfield, $sortorder);
	if (! empty($arrayfields['user']['checked']))                   print_liste_field_titre($arrayfields['user']['label'], $_SERVER["PHP_SELF"], "u.lastname", "", $param, '', $sortfield, $sortorder);
	if (! empty($arrayfields['d.date_debut']['checked']))           print_liste_field_titre($arrayfields['d.date_debut']['label'], $_SERVER["PHP_SELF"], "d.date_debut", "", $param, 'align="center"', $sortfield, $sortorder);
	if (! empty($arrayfields['d.date_fin']['checked']))             print_liste_field_titre($arrayfields['d.date_fin']['label'], $_SERVER["PHP_SELF"], "d.date_fin", "", $param, 'align="center"', $sortfield, $sortorder);
	if (! empty($arrayfields['d.date_valid']['checked']))           print_liste_field_titre($arrayfields['d.date_valid']['label'], $_SERVER["PHP_SELF"], "d.date_valid", "", $param, 'align="center"', $sortfield, $sortorder);
	if (! empty($arrayfields['d.date_approve']['checked']))         print_liste_field_titre($arrayfields['d.date_approve']['label'], $_SERVER["PHP_SELF"], "d.date_approve", "", $param, 'align="center"', $sortfield, $sortorder);
	if (! empty($arrayfields['d.total_ht']['checked']))             print_liste_field_titre($arrayfields['d.total_ht']['label'], $_SERVER["PHP_SELF"], "d.total_ht", "", $param, 'align="right"', $sortfield, $sortorder);
	if (! empty($arrayfields['d.total_vat']['checked']))            print_liste_field_titre($arrayfields['d.total_vat']['label'], $_SERVER["PHP_SELF"], "d.total_tva", "", $param, 'align="right"', $sortfield, $sortorder);
	if (! empty($arrayfields['d.total_ttc']['checked']))            print_liste_field_titre($arrayfields['d.total_ttc']['label'], $_SERVER["PHP_SELF"], "d.total_ttc", "", $param, 'align="right"', $sortfield, $sortorder);
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
	// Hook fields
	$parameters=array('arrayfields'=>$arrayfields,'param'=>$param,'sortfield'=>$sortfield,'sortorder'=>$sortorder);
	$reshook=$hookmanager->executeHooks('printFieldListTitle', $parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	if (! empty($arrayfields['d.date_create']['checked'])) print_liste_field_titre($arrayfields['d.date_create']['label'], $_SERVER["PHP_SELF"], "d.date_create", "", $param, 'align="center" class="nowrap"', $sortfield, $sortorder);
	if (! empty($arrayfields['d.tms']['checked']))         print_liste_field_titre($arrayfields['d.tms']['label'], $_SERVER["PHP_SELF"], "d.tms", "", $param, 'align="center" class="nowrap"', $sortfield, $sortorder);
	if (! empty($arrayfields['d.fk_statut']['checked']))   print_liste_field_titre($arrayfields['d.fk_statut']['label'], $_SERVER["PHP_SELF"], "d.fk_statut", "", $param, 'align="right"', $sortfield, $sortorder);
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', $sortfield, $sortorder, 'maxwidthsearch ');
	print "</tr>\n";

	$total_total_ht = 0;
	$total_total_ttc = 0;
	$total_total_tva = 0;

	$expensereportstatic=new ExpenseReport($db);
	$usertmp = new User($db);

	if ($num > 0)
	{
        $i=0;
    	$totalarray=array();
 	    while ($i < min($num, $limit))
		{
			$obj = $db->fetch_object($resql);

			$expensereportstatic->id=$obj->rowid;
			$expensereportstatic->ref=$obj->ref;
			$expensereportstatic->status=$obj->status;
			$expensereportstatic->date_debut=$db->jdate($obj->date_debut);
			$expensereportstatic->date_fin=$db->jdate($obj->date_fin);
			$expensereportstatic->date_create=$db->jdate($obj->date_create);
			$expensereportstatic->date_modif=$db->jdate($obj->date_modif);
			$expensereportstatic->date_valid=$db->jdate($obj->date_valid);
			$expensereportstatic->date_approve=$db->jdate($obj->date_approve);
			$expensereportstatic->note_private=$obj->note_private;
			$expensereportstatic->note_public=$obj->note_public;


			print '<tr class="oddeven">';
			// Ref
			if (! empty($arrayfields['d.ref']['checked'])) {
    			print '<td>';
                print '<table class="nobordernopadding"><tr class="nocellnopadd">';
                print '<td class="nobordernopadding nowrap">';
    			print $expensereportstatic->getNomUrl(1);
    			print '</td>';
    			// Warning late icon and note
        		print '<td class="nobordernopadding nowrap">';
    			if ($expensereportstatic->status == 2 && $expensereportstatic->hasDelay('toappove')) print img_warning($langs->trans("Late"));
    			if ($expensereportstatic->status == 5 && $expensereportstatic->hasDelay('topay')) print img_warning($langs->trans("Late"));
    			if (!empty($obj->note_private) || !empty($obj->note_public))
    			{
    			    print ' <span class="note">';
    			    print '<a href="'.DOL_URL_ROOT.'/expensereport/note.php?id='.$obj->rowid.'">'.img_picto($langs->trans("ViewPrivateNote"), 'object_generic').'</a>';
    			    print '</span>';
    			}
    			print '</td>';
    			print '<td width="16" class="nobordernopadding hideonsmartphone right">';
    			$filename=dol_sanitizeFileName($obj->ref);
    			$filedir=$conf->expensereport->dir_output . '/' . dol_sanitizeFileName($obj->ref);
    			$urlsource=$_SERVER['PHP_SELF'].'?id='.$obj->rowid;
    			print $formfile->getDocumentsLink($expensereportstatic->element, $filename, $filedir);
    			print '</td>';
    			print '</tr></table>';
    			print '</td>';
    			if (! $i) $totalarray['nbfield']++;
			}
			// User
			if (! empty($arrayfields['user']['checked'])) {
			    print '<td class="left">';
    			$usertmp->id=$obj->id_user;
    			$usertmp->lastname=$obj->lastname;
    			$usertmp->firstname=$obj->firstname;
    			$usertmp->login=$obj->login;
    			$usertmp->statut=$obj->statut;
    			$usertmp->photo=$obj->photo;
    			$usertmp->email=$obj->email;
    			print $usertmp->getNomUrl(-1);
    			print '</td>';
    			if (! $i) $totalarray['nbfield']++;
			}
			// Start date
			if (! empty($arrayfields['d.date_debut']['checked'])) {
                print '<td class="center">'.($obj->date_debut > 0 ? dol_print_date($db->jdate($obj->date_debut), 'day') : '').'</td>';
                if (! $i) $totalarray['nbfield']++;
            }
            // End date
			if (! empty($arrayfields['d.date_fin']['checked'])) {
			    print '<td class="center">'.($obj->date_fin > 0 ? dol_print_date($db->jdate($obj->date_fin), 'day') : '').'</td>';
			    if (! $i) $totalarray['nbfield']++;
			}
			// Date validation
			if (! empty($arrayfields['d.date_valid']['checked'])) {
			    print '<td class="center">'.($obj->date_valid > 0 ? dol_print_date($db->jdate($obj->date_valid), 'day') : '').'</td>';
			    if (! $i) $totalarray['nbfield']++;
			}
			// Date approval
			if (! empty($arrayfields['d.date_approve']['checked'])) {
			    print '<td class="center">'.($obj->date_approve > 0 ? dol_print_date($db->jdate($obj->date_approve), 'day') : '').'</td>';
			    if (! $i) $totalarray['nbfield']++;
			}
			// Amount HT
            if (! empty($arrayfields['d.total_ht']['checked']))
            {
    		      print '<td class="right">'.price($obj->total_ht)."</td>\n";
    		      if (! $i) $totalarray['nbfield']++;
    		      if (! $i) $totalarray['totalhtfield']=$totalarray['nbfield'];
    		      $totalarray['totalht'] += $obj->total_ht;
            }
            // Amount VAT
            if (! empty($arrayfields['d.total_vat']['checked']))
            {
                print '<td class="right">'.price($obj->total_tva)."</td>\n";
                if (! $i) $totalarray['nbfield']++;
    		    if (! $i) $totalarray['totalvatfield']=$totalarray['nbfield'];
    		    $totalarray['totalvat'] += $obj->total_tva;
            }
            // Amount TTC
            if (! empty($arrayfields['d.total_ttc']['checked']))
            {
                print '<td class="right">'.price($obj->total_ttc)."</td>\n";
                if (! $i) $totalarray['nbfield']++;
    		    if (! $i) $totalarray['totalttcfield']=$totalarray['nbfield'];
    		    $totalarray['totalttc'] += $obj->total_ttc;
            }

            // Extra fields
            include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
            // Fields from hook
            $parameters=array('arrayfields'=>$arrayfields, 'obj'=>$obj);
            $reshook=$hookmanager->executeHooks('printFieldListValue', $parameters);    // Note that $action and $object may have been modified by hook
            print $hookmanager->resPrint;
            // Date creation
            if (! empty($arrayfields['d.date_create']['checked']))
            {
                print '<td class="nowrap center">';
                print dol_print_date($db->jdate($obj->date_create), 'dayhour');
                print '</td>';
                if (! $i) $totalarray['nbfield']++;
            }
            // Date modification
            if (! empty($arrayfields['d.tms']['checked']))
            {
                print '<td class="nowrap center">';
                print dol_print_date($db->jdate($obj->date_modif), 'dayhour');
                print '</td>';
                if (! $i) $totalarray['nbfield']++;
            }
            // Status
            if (! empty($arrayfields['d.fk_statut']['checked']))
            {
                print '<td class="nowrap right">'.$expensereportstatic->getLibStatut(5).'</td>';
                if (! $i) $totalarray['nbfield']++;
            }
            // Action column
            print '<td class="nowrap center">';
            if ($massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
            {
                $selected=0;
                if (in_array($obj->rowid, $arrayofselected)) $selected=1;
                print '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected?' checked="checked"':'').'>';
            }
            print '</td>';
            if (! $i) $totalarray['nbfield']++;

			print "</tr>\n";

			$total_total_ht = $total_total_ht + $obj->total_ht;
			$total_total_tva = $total_total_tva + $obj->total_tva;
			$total_total_ttc = $total_total_ttc + $obj->total_ttc;

			$i++;
		}
	}
	else
	{
	    $colspan=1;
        foreach($arrayfields as $key => $val) { if (! empty($val['checked'])) $colspan++; }

        print '<tr>'.'<td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("NoRecordFound").'</td></tr>';
	}

	// Show total line
	if (isset($totalarray['totalhtfield']))
	{
	    print '<tr class="liste_total">';
	    $i=0;
	    while ($i < $totalarray['nbfield'])
	    {
	        $i++;
	        if ($i == 1)
	        {
	            if ($num < $limit && empty($offset)) print '<td class="left">'.$langs->trans("Total").'</td>';
	            else print '<td class="left">'.$langs->trans("Totalforthispage").'</td>';
	        }
	        elseif ($totalarray['totalhtfield'] == $i) print '<td class="right">'.price($totalarray['totalht']).'</td>';
	        elseif ($totalarray['totalvatfield'] == $i) print '<td class="right">'.price($totalarray['totalvat']).'</td>';
	        elseif ($totalarray['totalttcfield'] == $i) print '<td class="right">'.price($totalarray['totalttc']).'</td>';
	        else print '<td></td>';
	    }
	    print '</tr>';
	}

	$db->free($resql);

	$parameters=array('arrayfields'=>$arrayfields, 'sql'=>$sql);
	$reshook=$hookmanager->executeHooks('printFieldListFooter', $parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	print '</table>'."\n";
    print '</div>';

	print '</form>'."\n";

	if (empty($id))
	{
		$hidegeneratedfilelistifempty=1;
		if ($massaction == 'builddoc' || $action == 'remove_file' || $show_files) $hidegeneratedfilelistifempty=0;

		// Show list of available documents
		$urlsource=$_SERVER['PHP_SELF'].'?sortfield='.$sortfield.'&sortorder='.$sortorder;
		$urlsource.=str_replace('&amp;', '&', $param);

		$filedir=$diroutputmassaction;
		$genallowed=$user->rights->expensereport->lire;
		$delallowed=$user->rights->expensereport->creer;

		print $formfile->showdocuments('massfilesarea_expensereport', '', $filedir, $urlsource, 0, $delallowed, '', 1, 1, 0, 48, 1, $param, $title, '', '', '', null, $hidegeneratedfilelistifempty);
	}
}
else
{
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
