<?php
/* Copyright (C) 2003     	Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016	Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004     	Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2009	Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2015       Alexandre Spangaro   <aspangaro.dolibarr@gmail.com>
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

require "../main.inc.php";
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

$langs->load("companies");
$langs->load("users");
$langs->load("trips");

$action=GETPOST('action','alpha');
$massaction=GETPOST('massaction','alpha');
$show_files=GETPOST('show_files','int');
$confirm=GETPOST('confirm','alpha');
$toselect = GETPOST('toselect', 'array');

// Security check
$socid = $_GET["socid"]?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'expensereport','','');

$diroutputmassaction=$conf->expensereport->dir_output . '/temp/massgeneration/'.$user->id;


// Load variable for pagination
$limit = GETPOST("limit")?GETPOST("limit","int"):$conf->liste_limit;
$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page = GETPOST('page','int');
if ($page == -1) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder) $sortorder="DESC";
if (!$sortfield) $sortfield="d.date_debut";


$sall         = GETPOST('sall');
$search_ref   = GETPOST('search_ref');
$search_user  = GETPOST('search_user','int');
$search_amount_ht = GETPOST('search_amount_ht','alpha');
$search_amount_vat = GETPOST('search_amount_vat','alpha');
$search_amount_ttc = GETPOST('search_amount_ttc','alpha');
$search_status = (GETPOST('search_status','alpha')!=''?GETPOST('search_status','alpha'):GETPOST('statut','alpha'));
$month_start  = GETPOST("month_start","int");
$year_start   = GETPOST("year_start","int");
$month_end    = GETPOST("month_end","int");
$year_end     = GETPOST("year_end","int");
$optioncss = GETPOST('optioncss','alpha');

if ($search_status == '') $search_status=-1;
if ($search_user == '') $search_user=-1;

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$contextpage='expensereportlist';

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('expensereportlist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('expensereport');
$search_array_options=$extrafields->getOptionalsFromPost($extralabels,'','search_');


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
    'd.datec'=>array('label'=>$langs->trans("DateCreation"), 'checked'=>0, 'position'=>500),
    'd.tms'=>array('label'=>$langs->trans("DateModificationShort"), 'checked'=>0, 'position'=>500),
    'd.fk_statut'=>array('label'=>$langs->trans("Status"), 'checked'=>1, 'position'=>1000),
);
// Extra fields
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
{
    foreach($extrafields->attribute_label as $key => $val)
    {
        $arrayfields["ef.".$key]=array('label'=>$extrafields->attribute_label[$key], 'checked'=>$extrafields->attribute_list[$key], 'position'=>$extrafields->attribute_pos[$key], 'enabled'=>$extrafields->attribute_perms[$key]);
    }
}



/*
 * Actions 
 */

if (GETPOST('cancel')) { $action='list'; $massaction=''; }
if (! GETPOST('confirmmassaction') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction=''; }

$parameters=array('socid'=>$socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

// Purge search criteria
if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter.x") || GETPOST("button_removefilter"))		// Both test must be present to be compatible with all browsers
{
    $search_ref="";
    $search_user="";
    $search_amount_ht="";
    $search_amount_vat="";
    $search_amount_ttc="";
    $search_status="";
    $month_start="";
    $year_start="";
    $month_end="";
    $year_end="";
    $toselect='';
    $search_array_options=array();
}

if (empty($reshook))
{
    $objectclass='ExpenseReport';
    $objectlabel='ExpenseReport';
    $permtoread = $user->rights->expensereport->lire;
    $permtodelete = $user->rights->expensereport->supprimer;
    $uploaddir = $conf->expensereport->dir_output;
    include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}



/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);

llxHeader('', $langs->trans("ListOfTrips"));

$max_year = 5;
$min_year = 5;


$sql = "SELECT d.rowid, d.ref, d.fk_user_author, d.total_ht, d.total_tva, d.total_ttc, d.fk_statut as status,";
$sql.= " d.date_debut, d.date_fin, d.date_create, d.tms as date_modif, d.date_valid, d.date_approve, d.note_private, d.note_public,";
$sql.= " u.rowid as id_user, u.firstname, u.lastname, u.login, u.statut, u.photo";
// Add fields from extrafields
foreach ($extrafields->attribute_label as $key => $val) $sql.=($extrafields->attribute_type[$key] != 'separate' ? ",ef.".$key.' as options_'.$key : '');
// Add fields from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListSelect',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql.= " FROM ".MAIN_DB_PREFIX."expensereport as d";
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."expensereport_extrafields as ef on (d.rowid = ef.fk_object)";
$sql.= ", ".MAIN_DB_PREFIX."user as u";
$sql.= " WHERE d.fk_user_author = u.rowid AND d.entity IN (".getEntity('expensereport', 1).")";
// Search all
if (!empty($sall)) $sql.= natural_search(array_keys($fieldstosearchall), $sall);
// Ref
if (!empty($search_ref)) $sql.= natural_search('d.ref', $search_ref);
// Date Start
if ($month_start > 0)
{
    if ($year_start > 0 && empty($day))
    $sql.= " AND d.date_debut BETWEEN '".$db->idate(dol_get_first_day($year_start,$month_start,false))."' AND '".$db->idate(dol_get_last_day($year_start,$month_start,false))."'";
    else if ($year_start > 0 && ! empty($day))
    $sql.= " AND d.date_debut BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month_start, $day, $year_start))."' AND '".$db->idate(dol_mktime(23, 59, 59, $month_start, $day, $year_start))."'";
    else
    $sql.= " AND date_format(d.date_debut, '%m') = '".$month_start."'";
}
else if ($year_start > 0)
{
	$sql.= " AND d.date_debut BETWEEN '".$db->idate(dol_get_first_day($year_start,1,false))."' AND '".$db->idate(dol_get_last_day($year_start,12,false))."'";
}
// Date Start
if ($month_end > 0)
{
    if ($year_end > 0 && empty($day))
    $sql.= " AND d.date_fin BETWEEN '".$db->idate(dol_get_first_day($year_end,$month_end,false))."' AND '".$db->idate(dol_get_last_day($year_end,$month_end,false))."'";
    else if ($year_end > 0 && ! empty($day))
    $sql.= " AND d.date_fin BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month_end, $day, $year_end))."' AND '".$db->idate(dol_mktime(23, 59, 59, $month_end, $day, $year_end))."'";
    else
    $sql.= " AND date_format(d.date_fin, '%m') = '".$month_end."'";
}
else if ($year_end > 0)
{
	$sql.= " AND d.date_fin BETWEEN '".$db->idate(dol_get_first_day($year_end,1,false))."' AND '".$db->idate(dol_get_last_day($year_end,12,false))."'";
}
// Amount
if ($search_amount_ht != '') $sql.= natural_search('d.total_ht', $search_amount_ht, 1);
if ($search_amount_ttc != '') $sql.= natural_search('d.total_ttc', $search_amount_ttc, 1);
// User
if ($search_user != '' && $search_user >= 0) $sql.= " AND u.rowid = '".$db->escape($search_user)."'";
// Status
if ($search_status != '' && $search_status >= 0)
{
	if (strstr($search_status, ',')) $sql.=" AND d.fk_statut IN (".$db->escape($search_status).")";
	else $sql.=" AND d.fk_statut = ".$search_status;
}
// RESTRICT RIGHTS
if (empty($user->rights->expensereport->readall) && empty($user->rights->expensereport->lire_tous)
    && (empty($conf->global->MAIN_USE_ADVANCED_PERMS) || empty($user->rights->expensereport->writeall_advance)))
{
	$childids = $user->getAllChildIds();
	$childids[]=$user->id;
	$sql.= " AND d.fk_user_author IN (".join(',',$childids).")\n";
}
// Add where from extra fields
foreach ($search_array_options as $key => $val)
{
    $crit=$val;
    $tmpkey=preg_replace('/search_options_/','',$key);
    $typ=$extrafields->attribute_type[$tmpkey];
    $mode=0;
    if (in_array($typ, array('int','double'))) $mode=1;    // Search on a numeric
    if ($val && ( ($crit != '' && ! in_array($typ, array('select'))) || ! empty($crit)))
    {
        $sql .= natural_search('ef.'.$tmpkey, $crit, $mode);
    }
}
// Add where from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListWhere',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;

$sql.= $db->order($sortfield,$sortorder);

$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
    $result = $db->query($sql);
    $nbtotalofrecords = $db->num_rows($result);
}
$sql.= $db->plimit($limit+1, $offset);

//print $sql;
$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);

	$arrayofselected=is_array($toselect)?$toselect:array();

	$param="";
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
	foreach ($search_array_options as $key => $val)
	{
	    $crit=$val;
	    $tmpkey=preg_replace('/search_options_/','',$key);
	    if ($val != '') $param.='&search_options_'.$tmpkey.'='.urlencode($val);
	}

	// List of mass actions available
	$arrayofmassactions =  array(
	    //'presend'=>$langs->trans("SendByMail"),
	    //'builddoc'=>$langs->trans("PDFMerge"),
	);
	if ($user->rights->expensereport->supprimer) $arrayofmassactions['delete']=$langs->trans("Delete");
	if ($massaction == 'presend') $arrayofmassactions=array();
	$massactionbutton=$form->selectMassAction('', $arrayofmassactions);

	print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">'."\n";
    if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';

	$title = $langs->trans("ListTripsAndExpenses");
	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'title_generic.png', 0, '', '', $limit);
	
	if ($sall)
    {
        foreach($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
        print $langs->trans("FilterOnInto", $sall) . join(', ',$fieldstosearchall);
    }

	$moreforfilter='';

	$parameters=array();
	$reshook=$hookmanager->executeHooks('printFieldPreListTitle',$parameters);    // Note that $action and $object may have been modified by hook
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

    print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";
	print "<tr class=\"liste_titre\">";
	if (! empty($arrayfields['d.ref']['checked']))                  print_liste_field_titre($arrayfields['d.ref']['label'],$_SERVER["PHP_SELF"],"d.ref","",$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['user']['checked']))                   print_liste_field_titre($arrayfields['user']['label'],$_SERVER["PHP_SELF"],"u.lastname","",$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['d.date_debut']['checked']))           print_liste_field_titre($arrayfields['d.date_debut']['label'],$_SERVER["PHP_SELF"],"d.date_debut","",$param,'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['d.date_fin']['checked']))             print_liste_field_titre($arrayfields['d.date_fin']['label'],$_SERVER["PHP_SELF"],"d.date_fin","",$param,'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['d.date_valid']['checked']))           print_liste_field_titre($arrayfields['d.date_valid']['label'],$_SERVER["PHP_SELF"],"d.date_valid","",$param,'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['d.date_approve']['checked']))         print_liste_field_titre($arrayfields['d.date_approve']['label'],$_SERVER["PHP_SELF"],"d.date_approve","",$param,'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['d.total_ht']['checked']))             print_liste_field_titre($arrayfields['d.total_ht']['label'],$_SERVER["PHP_SELF"],"d.total_ht","",$param,'align="right"',$sortfield,$sortorder);
	if (! empty($arrayfields['d.total_vat']['checked']))            print_liste_field_titre($arrayfields['d.total_vat']['label'],$_SERVER["PHP_SELF"],"d.total_tva","",$param,'align="right"',$sortfield,$sortorder);
	if (! empty($arrayfields['d.total_ttc']['checked']))            print_liste_field_titre($arrayfields['d.total_ttc']['label'],$_SERVER["PHP_SELF"],"d.total_ttc","",$param,'align="right"',$sortfield,$sortorder);
	// Extra fields
	if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
	{
	   foreach($extrafields->attribute_label as $key => $val) 
	   {
           if (! empty($arrayfields["ef.".$key]['checked'])) 
           {
				$align=$extrafields->getAlignFlag($key);
				print_liste_field_titre($extralabels[$key],$_SERVER["PHP_SELF"],"ef.".$key,"",$param,($align?'align="'.$align.'"':''),$sortfield,$sortorder);
           }
	   }
	}
	// Hook fields
	$parameters=array('arrayfields'=>$arrayfields);
    $reshook=$hookmanager->executeHooks('printFieldListTitle',$parameters);    // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;
	if (! empty($arrayfields['d.datec']['checked']))     print_liste_field_titre($arrayfields['d.datec']['label'],$_SERVER["PHP_SELF"],"d.date_creation","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
	if (! empty($arrayfields['d.tms']['checked']))       print_liste_field_titre($arrayfields['d.tms']['label'],$_SERVER["PHP_SELF"],"d.tms","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
	if (! empty($arrayfields['d.fk_statut']['checked'])) print_liste_field_titre($arrayfields['d.fk_statut']['label'],$_SERVER["PHP_SELF"],"d.fk_statut","",$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"],"",'','','align="right"',$sortfield,$sortorder,'maxwidthsearch ');
	print "</tr>\n";

	// Filters
	print '<tr class="liste_titre">';
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
    		print $form->select_dolusers($search_user, 'search_user', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth300');
    		print '</td>';
    	} else {
    		print '<td class="liste_titre">&nbsp;</td>';
    	}
	}
	// Date start
	if (! empty($arrayfields['d.date_debut']['checked']))
	{
    	print '<td class="liste_titre" align="center">';
    	print '<input class="flat" type="text" size="1" maxlength="2" name="month_start" value="'.$month_start.'">';
    	$formother->select_year($year_start,'year_start',1, $min_year, $max_year);
    	print '</td>';
	}
	// Date end
	if (! empty($arrayfields['d.date_fin']['checked']))
	{
    	print '<td class="liste_titre" align="center">';
    	print '<input class="flat" type="text" size="1" maxlength="2" name="month_end" value="'.$month_end.'">';
    	$formother->select_year($year_end,'year_end',1, $min_year, $max_year);
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
    	print '<td class="liste_titre" align="right"><input class="flat" type="text" size="5" name="search_amount_ht" value="'.$search_amount_ht.'"></td>';
	}
	if (! empty($arrayfields['d.total_vat']['checked']))
	{
	   print '<td class="liste_titre" align="right"><input class="flat" type="text" size="5" name="search_amount_vat" value="'.$search_amount_vat.'"></td>';
	}
	// Amount with all taxes
	if (! empty($arrayfields['d.total_ttc']['checked']))
	{
	   print '<td class="liste_titre" align="right"><input class="flat" type="text" size="5" name="search_amount_ttc" value="'.$search_amount_ttc.'"></td>';
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
	if (! empty($arrayfields['d.datec']['checked']))
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
    	print '<td class="liste_titre" align="right">';
    	select_expensereport_statut($search_status,'search_status',1,1);
    	print '</td>';
	}
	// Action column
	print '<td class="liste_titre" align="middle">';
	$searchpitco=$form->showFilterAndCheckAddButtons($massactionbutton?1:0, 'checkforselect', 1);
	print $searchpitco;
	print '</td>';

	print "</tr>\n";

	$var=true;

	$total_total_ht = 0;
	$total_total_ttc = 0;
	$total_total_tva = 0;

	$expensereportstatic=new ExpenseReport($db);
	$usertmp = new User($db);

	if ($num > 0)
	{
        $i=0;
    	$var=true;
    	$totalarray=array();
 	    while ($i < min($num,$limit))
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
				
			$var=!$var;
			print "<tr ".$bc[$var].">";
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
    			    print '<a href="'.DOL_URL_ROOT.'/expensereport/note.php?id='.$obj->rowid.'">'.img_picto($langs->trans("ViewPrivateNote"),'object_generic').'</a>';
    			    print '</span>';
    			}
    			print '</td>';
    			print '<td width="16" align="right" class="nobordernopadding hideonsmartphone">';
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
			    print '<td align="left">';
    			$usertmp->id=$obj->id_user;
    			$usertmp->lastname=$obj->lastname;
    			$usertmp->firstname=$obj->firstname;
    			$usertmp->login=$obj->login;
    			$usertmp->statut=$obj->statut;
    			$usertmp->photo=$obj->photo;
    			print $usertmp->getNomUrl(-1);
    			print '</td>';
    			if (! $i) $totalarray['nbfield']++;
			}
			// Start date
			if (! empty($arrayfields['d.date_debut']['checked'])) {
                print '<td align="center">'.($obj->date_debut > 0 ? dol_print_date($obj->date_debut, 'day') : '').'</td>';
                if (! $i) $totalarray['nbfield']++;
            }
            // End date
			if (! empty($arrayfields['d.date_fin']['checked'])) {
			    print '<td align="center">'.($obj->date_fin > 0 ? dol_print_date($obj->date_fin, 'day') : '').'</td>';
			    if (! $i) $totalarray['nbfield']++;
			}
			// Date validation
			if (! empty($arrayfields['d.date_valid']['checked'])) {
			    print '<td align="center">'.($obj->date_valid > 0 ? dol_print_date($obj->date_valid, 'day') : '').'</td>';
			    if (! $i) $totalarray['nbfield']++;
			}
			// Date approval
			if (! empty($arrayfields['d.date_approve']['checked'])) {
			    print '<td align="center">'.($obj->date_approve > 0 ? dol_print_date($obj->date_approve, 'day') : '').'</td>';
			    if (! $i) $totalarray['nbfield']++;
			}
			// Amount HT
            if (! empty($arrayfields['d.total_ht']['checked']))
            {
    		      print '<td align="right">'.price($obj->total_ht)."</td>\n";
    		      if (! $i) $totalarray['nbfield']++;
    		      if (! $i) $totalarray['totalhtfield']=$totalarray['nbfield'];
    		      $totalarray['totalht'] += $obj->total_ht;
            }
            // Amount VAT
            if (! empty($arrayfields['d.total_vat']['checked']))
            {
                print '<td align="right">'.price($obj->total_tva)."</td>\n";
                if (! $i) $totalarray['nbfield']++;
    		    if (! $i) $totalarray['totalvatfield']=$totalarray['nbfield'];
    		    $totalarray['totalvat'] += $obj->total_tva;
            }
            // Amount TTC
            if (! empty($arrayfields['d.total_ttc']['checked']))
            {
                print '<td align="right">'.price($obj->total_ttc)."</td>\n";
                if (! $i) $totalarray['nbfield']++;
    		    if (! $i) $totalarray['totalttcfield']=$totalarray['nbfield'];
    		    $totalarray['totalttc'] += $obj->total_ttc;
            }
    		
            // Extra fields
            if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
            {
                foreach($extrafields->attribute_label as $key => $val)
                {
                    if (! empty($arrayfields["ef.".$key]['checked']))
                    {
                        print '<td';
                        $align=$extrafields->getAlignFlag($key);
                        if ($align) print ' align="'.$align.'"';
                        print '>';
                        $tmpkey='options_'.$key;
                        print $extrafields->showOutputField($key, $obj->$tmpkey, '', 1);
                        print '</td>';
                        if (! $i) $totalarray['nbfield']++;
                    }
                }
            }
            // Fields from hook
            $parameters=array('arrayfields'=>$arrayfields, 'obj'=>$obj);
            $reshook=$hookmanager->executeHooks('printFieldListValue',$parameters);    // Note that $action and $object may have been modified by hook
            print $hookmanager->resPrint;
            // Date creation
            if (! empty($arrayfields['d.datec']['checked']))
            {
                print '<td align="center" class="nowrap">';
                print dol_print_date($db->jdate($obj->date_create), 'dayhour');
                print '</td>';
                if (! $i) $totalarray['nbfield']++;
            }
            // Date modification
            if (! empty($arrayfields['d.tms']['checked']))
            {
                print '<td align="center" class="nowrap">';
                print dol_print_date($db->jdate($obj->date_modif), 'dayhour');
                print '</td>';
                if (! $i) $totalarray['nbfield']++;
            }
            // Status
            if (! empty($arrayfields['d.fk_statut']['checked']))
            {
                print '<td align="right" class="nowrap">'.$expensereportstatic->getLibStatut(5).'</td>';
                if (! $i) $totalarray['nbfield']++;
            }
            // Action column
            print '<td class="nowrap" align="center">';
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
		print '<tr '.$bc[false].'>'.'<td colspan="9" class="opacitymedium">'.$langs->trans("NoRecordFound").'</td></tr>';
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
	            if ($num < $limit) print '<td align="left">'.$langs->trans("Total").'</td>';
	            else print '<td align="left">'.$langs->trans("Totalforthispage").'</td>';
	        }
	        elseif ($totalarray['totalhtfield'] == $i) print '<td align="right">'.price($totalarray['totalht']).'</td>';
	        elseif ($totalarray['totalvatfield'] == $i) print '<td align="right">'.price($totalarray['totalvat']).'</td>';
	        elseif ($totalarray['totalttcfield'] == $i) print '<td align="right">'.price($totalarray['totalttc']).'</td>';
	        else print '<td></td>';
	    }
	    print '</tr>';
	}

	$db->free($resql);

	$parameters=array('arrayfields'=>$arrayfields, 'sql'=>$sql);
	$reshook=$hookmanager->executeHooks('printFieldListFooter',$parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	print '</table>'."\n";
    print '</div>';

	print '</form>'."\n";

	/*
	if ($massaction == 'builddoc' || $action == 'remove_file' || $show_files)
	{
	    // Show list of available documents
	    $urlsource=$_SERVER['PHP_SELF'].'?sortfield='.$sortfield.'&sortorder='.$sortorder;
	    $urlsource.=str_replace('&amp;','&',$param);
	
	    $filedir=$diroutputmassaction;
	    $genallowed=$user->rights->expensereport->lire;
	    $delallowed=$user->rights->expensereport->lire;
	
	    print $formfile->showdocuments('massfilesarea_orders','',$filedir,$urlsource,0,$delallowed,'',1,1,0,48,1,$param,$title,'');
	}
	else
	{
	    print '<br><a name="show_files"></a><a href="'.$_SERVER["PHP_SELF"].'?show_files=1'.$param.'#show_files">'.$langs->trans("ShowTempMassFilesArea").'</a>';
	}
	*/
}
else
{
	dol_print_error($db);
}


llxFooter();

$db->close();
