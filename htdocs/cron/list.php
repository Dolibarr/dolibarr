<?php
/* Copyright (C) 2012      Nicolas Villa aka Boyquotes http://informetic.fr
 * Copyright (C) 2013      Florian Henry       <florian.henry@open-concept.pro>
 * Copyright (C) 2013-2016 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2019      Frédéric France     <frederic.france@netlogic.fr>
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
 *  \file       htdocs/cron/list.php
 *  \ingroup    cron
 *  \brief      Lists Jobs
 */


require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/cron/class/cronjob.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/cron.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("admin","cron","bills","members"));

if (!$user->rights->cron->read) accessforbidden();

$action=GETPOST('action', 'alpha');
$massaction = GETPOST('massaction', 'alpha');											// The bulk action (combo box choice into lists)
$confirm=GETPOST('confirm', 'alpha');
$toselect   = GETPOST('toselect', 'array');												// Array of ids of elements selected into a list
$contextpage= GETPOST('contextpage', 'aZ')?GETPOST('contextpage', 'aZ'):'cronjoblist';   // To manage different context of search

$id=GETPOST('id', 'int');

$limit = GETPOST('limit', 'int')?GETPOST('limit', 'int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOST("page", 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield='t.status,t.priority';
if (! $sortorder) $sortorder='DESC,ASC';

$search_status=(GETPOSTISSET('search_status')?GETPOST('search_status', 'int'):GETPOST('status', 'int'));

//Search criteria
$search_label=GETPOST("search_label", 'alpha');
$securitykey = GETPOST('securitykey', 'alpha');

$diroutputmassaction=$conf->cronjob->dir_output . '/temp/massgeneration/'.$user->id;

$object = new Cronjob($db);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('cronjoblist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('cronjob');
$search_array_options=$extrafields->getOptionalsFromPost($object->table_element, '', 'search_');



/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) { $action='list'; $massaction=''; }
if (! GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction=''; }

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
	{
		$search_label='';
		$search_status=-1;
		$toselect='';
		$search_array_options=array();
	}
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')
		|| GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha'))
	{
		$massaction='';     // Protection to avoid mass action if we force a new search during a mass action confirmation
	}

	$filter=array();
	if (!empty($search_label))
	{
		$filter['t.label']=$search_label;
	}

	// Delete jobs
	if ($action == 'confirm_delete' && $confirm == "yes" && $user->rights->cron->delete)
	{
		//Delete cron task
		$object = new Cronjob($db);
		$object->id=$id;
		$result = $object->delete($user);

		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// Execute jobs
	if ($action == 'confirm_execute' && $confirm == "yes" && $user->rights->cron->execute)
	{
	    if (! empty($conf->global->CRON_KEY) && $conf->global->CRON_KEY != $securitykey)
	    {
	        setEventMessages('Security key '.$securitykey.' is wrong', null, 'errors');
	        $action='';
	    }
	    else
	    {
	        $object = new Cronjob($db);
	    	$job = $object->fetch($id);

	        $now = dol_now();   // Date we start

	        $resrunjob = $object->run_jobs($user->login);   // Return -1 if KO, 1 if OK
	    	if ($resrunjob < 0) {
	    		setEventMessages($object->error, $object->errors, 'errors');
	    	}

	    	// Programm next run
	    	$res = $object->reprogram_jobs($user->login, $now);
	    	if ($res > 0)
	    	{
	    		if ($resrunjob >= 0)	// We show the result of reprogram only if no error message already reported
	    		{
	    		    if ($object->lastresult >= 0) setEventMessages($langs->trans("JobFinished"), null, 'mesgs');
	    		    else setEventMessages($langs->trans("JobFinished"), null, 'errors');
	    		}
	    		$action='';
	    	}
	    	else
	    	{
	    		setEventMessages($object->error, $object->errors, 'errors');
	    		$action='';
	    	}

	    	$param='&search_status='.urlencode($search_status);
	    	if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.urlencode($contextpage);
	    	if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.urlencode($limit);
	    	if ($search_label)	  $param.='&search_label='.urlencode($search_label);
	    	if ($optioncss != '') $param.='&optioncss='.urlencode($optioncss);
	    	// Add $param from extra fields
	    	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

	    	header("Location: ".DOL_URL_ROOT.'/cron/list.php?'.$param.($sortfield?'&sortfield='.$sortfield:'').($sortorder?'&sortorder='.$sortorder:''));		// Make a redirect to avoid to run twice the job when using back
	    	exit;
	    }
	}

	// Mass actions
	$objectclass='CronJob';
	$objectlabel='CronJob';
	$permtoread = $user->rights->cron->read;
	$permtocreate = $user->rights->cron->create?$user->rights->cron->create:$user->rights->cron->write;
	$permtodelete = $user->rights->cron->delete;
	$uploaddir = $conf->cron->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
	if ($permtocreate)
	{
		$tmpcron = new Cronjob($db);
		foreach($toselect as $id)
		{
			$result = $tmpcron->fetch($id);
			if ($result)
			{
				$result = 0;
				if ($massaction == 'disable') $result = $tmpcron->setStatut(Cronjob::STATUS_DISABLED);
				elseif ($massaction == 'enable') $result = $tmpcron->setStatut(Cronjob::STATUS_ENABLED);
				//else dol_print_error($db, 'Bad value for massaction');
				if ($result < 0) setEventMessages($tmpcron->error, $tmpcron->errors, 'errors');
			}
			else
			{
				$error++;
			}
		}
	}
}


/*
 * View
 */

$form = new Form($db);
$cronjob = new Cronjob($db);

$pagetitle=$langs->trans("CronList");

llxHeader('', $pagetitle);


$sql = "SELECT";
$sql.= " t.rowid,";
$sql.= " t.tms,";
$sql.= " t.datec,";
$sql.= " t.jobtype,";
$sql.= " t.label,";
$sql.= " t.command,";
$sql.= " t.classesname,";
$sql.= " t.objectname,";
$sql.= " t.methodename,";
$sql.= " t.params,";
$sql.= " t.md5params,";
$sql.= " t.module_name,";
$sql.= " t.priority,";
$sql.= " t.processing,";
$sql.= " t.datelastrun,";
$sql.= " t.datenextrun,";
$sql.= " t.dateend,";
$sql.= " t.datestart,";
$sql.= " t.lastresult,";
$sql.= " t.datelastresult,";
$sql.= " t.lastoutput,";
$sql.= " t.unitfrequency,";
$sql.= " t.frequency,";
$sql.= " t.status,";
$sql.= " t.fk_user_author,";
$sql.= " t.fk_user_mod,";
$sql.= " t.note,";
$sql.= " t.maxrun,";
$sql.= " t.nbrun,";
$sql.= " t.libname,";
$sql.= " t.test";
$sql.= " FROM ".MAIN_DB_PREFIX."cronjob as t";
$sql.= " WHERE entity IN (0,".$conf->entity.")";
if ($search_status >= 0 && $search_status < 2 && $search_status != '') $sql.= " AND t.status = ".(empty($search_status)?'0':'1');
//Manage filter
if (is_array($filter) && count($filter)>0) {
	foreach($filter as $key => $value) {
		$sql.= ' AND '.$key.' LIKE \'%'.$db->escape($value).'%\'';
	}
}
$sqlwhere = array();
if (!empty($module_name)) {
	$sqlwhere[]='(t.module_name='.$db->escape($module_name).')';
}
if (count($sqlwhere)>0) {
	$sql.= " WHERE ".implode(' AND ', $sqlwhere);
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

$result=$db->query($sql);
if (! $result) dol_print_error($db);

$num = $db->num_rows($result);

$arrayofselected=is_array($toselect)?$toselect:array();

$param = '';
if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.urlencode($contextpage);
if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.urlencode($limit);
if ($search_status)   $param.='&search_status='.urlencode($search_status);
if ($search_label)	  $param.='&search_label='.urlencode($search_label);
if ($optioncss != '') $param.='&optioncss='.urlencode($optioncss);
// Add $param from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

$stringcurrentdate = $langs->trans("CurrentHour").': '.dol_print_date(dol_now(), 'dayhour');

if ($action == 'delete')
{
	print $form->formconfirm($_SERVER['PHP_SELF']."?id=".$id.$param, $langs->trans("CronDelete"), $langs->trans("CronConfirmDelete"), "confirm_delete", '', '', 1);
}
if ($action == 'execute')
{
	print $form->formconfirm($_SERVER['PHP_SELF']."?id=".$id.'&securitykey='.$securitykey.$param, $langs->trans("CronExecute"), $langs->trans("CronConfirmExecute"), "confirm_execute", '', '', 1);
}

// List of mass actions available
$arrayofmassactions =  array(
//'presend'=>$langs->trans("SendByMail"),
//'builddoc'=>$langs->trans("PDFMerge"),
	'enable'=>$langs->trans("CronStatusActiveBtn"),
	'disable'=>$langs->trans("CronStatusInactiveBtn"),
);
if ($user->rights->mymodule->delete) $arrayofmassactions['predelete']='<span class="fa fa-trash paddingrightonly"></span>'.$langs->trans("Delete");
if (in_array($massaction, array('presend','predelete'))) $arrayofmassactions=array();
$massactionbutton=$form->selectMassAction('', $arrayofmassactions);


print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'" name="search_form">'."\n";
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

// Line with explanation and button new
$newcardbutton = dolGetButtonTitle($langs->trans('New'), $langs->trans('CronCreateJob'), 'fa fa-plus-circle', DOL_URL_ROOT.'/cron/card.php?action=create&backtopage='.urlencode($_SERVER['PHP_SELF']), '', $user->rights->cron->create);


print_barre_liste($pagetitle, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'title_setup', 0, $newcardbutton, '', $limit);


print '<span class="opacitymedium">'.$langs->trans('CronInfo').'</span><br>';

$text =$langs->trans("HoursOnThisPageAreOnServerTZ").' '.$stringcurrentdate.'<br>';
if (! empty($conf->global->CRON_WARNING_DELAY_HOURS)) $text.=$langs->trans("WarningCronDelayed", $conf->global->CRON_WARNING_DELAY_HOURS);
print info_admin($text);
print '<br>';

$varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;
$selectedfields='';
//$selectedfields=$form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);	// This also change content of $arrayfields
$selectedfields.=(count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

print '<div class="div-table-responsive">';
print '<table class="noborder">';

print '<tr class="liste_titre_filter">';
print '<td class="liste_titre">&nbsp;</td>';
print '<td class="liste_titre">';
print '<input type="text" class="flat" name="search_label" value="'.$search_label.'" size="10">';
print '</td>';
print '<td class="liste_titre">&nbsp;</td>';
print '<td class="liste_titre">&nbsp;</td>';
print '<td class="liste_titre">&nbsp;</td>';
print '<td class="liste_titre">&nbsp;</td>';
print '<td class="liste_titre">&nbsp;</td>';
print '<td class="liste_titre">&nbsp;</td>';
print '<td class="liste_titre">&nbsp;</td>';
print '<td class="liste_titre">&nbsp;</td>';
print '<td class="liste_titre">&nbsp;</td>';
print '<td class="liste_titre">&nbsp;</td>';
print '<td class="liste_titre">&nbsp;</td>';
print '<td class="liste_titre">&nbsp;</td>';
print '<td class="liste_titre" align="center">';
print $form->selectarray('search_status', array('0'=>$langs->trans("Disabled"), '1'=>$langs->trans("Enabled")), $search_status, 1);
print '</td><td class="liste_titre right">';
$searchpicto=$form->showFilterButtons();
print $searchpicto;
print '</td>';
print '</tr>';

print '<tr class="liste_titre">';
print_liste_field_titre("ID", $_SERVER["PHP_SELF"], "t.rowid", "", $param, '', $sortfield, $sortorder);
print_liste_field_titre("CronLabel", $_SERVER["PHP_SELF"], "t.label", "", $param, '', $sortfield, $sortorder);
print_liste_field_titre("Prority", $_SERVER["PHP_SELF"], "t.priority", "", $param, '', $sortfield, $sortorder);
print_liste_field_titre("CronTask", '', '', "", $param, '', $sortfield, $sortorder);
print_liste_field_titre("CronFrequency", '', "", "", $param, '', $sortfield, $sortorder);
print_liste_field_titre("CronDtStart", $_SERVER["PHP_SELF"], "t.datestart", "", $param, 'align="center"', $sortfield, $sortorder);
print_liste_field_titre("CronDtEnd", $_SERVER["PHP_SELF"], "t.dateend", "", $param, 'align="center"', $sortfield, $sortorder);
print_liste_field_titre("CronMaxRun", $_SERVER["PHP_SELF"], "t.maxrun", "", $param, 'align="right"', $sortfield, $sortorder);
print_liste_field_titre("CronNbRun", $_SERVER["PHP_SELF"], "t.nbrun", "", $param, 'align="right"', $sortfield, $sortorder);
print_liste_field_titre("CronDtLastLaunch", $_SERVER["PHP_SELF"], "t.datelastrun", "", $param, 'align="center"', $sortfield, $sortorder);
print_liste_field_titre("Duration", $_SERVER["PHP_SELF"], "", "", $param, 'align="center"', $sortfield, $sortorder);
print_liste_field_titre("CronLastResult", $_SERVER["PHP_SELF"], "t.lastresult", "", $param, 'align="center"', $sortfield, $sortorder);
print_liste_field_titre("CronLastOutput", $_SERVER["PHP_SELF"], "t.lastoutput", "", $param, '', $sortfield, $sortorder);
print_liste_field_titre("CronDtNextLaunch", $_SERVER["PHP_SELF"], "t.datenextrun", "", $param, 'align="center"', $sortfield, $sortorder);
print_liste_field_titre("Status", $_SERVER["PHP_SELF"], "t.status,t.priority", "", $param, 'align="center"', $sortfield, $sortorder);
print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", "", $param, 'align="center"', $sortfield, $sortorder, 'maxwidthsearch ');
print "</tr>\n";


if ($num > 0)
{
	// Loop on each job
	$now = dol_now();
	$i=0;

	while ($i < min($num, $limit))
	{
		$obj = $db->fetch_object($result);

		if (empty($obj)) break;
		if (! verifCond($obj->test)) continue;        // Discard line with test = false

		$object->id = $obj->rowid;
		$object->ref = $obj->rowid;
		$object->label = $obj->label;
		$object->status = $obj->status;
		$object->priority = $obj->priority;
		$object->processing = $obj->processing;

		$datelastrun = $db->jdate($obj->datelastrun);
		$datelastresult = $db->jdate($obj->datelastresult);

		print '<tr class="oddeven">';

		// Ref
		print '<td class="nowraponall">';
		print $object->getNomUrl(1);
		print '</td>';

		// Label
		print '<td>';
		if (! empty($obj->label))
		{
			$object->ref = $langs->trans($obj->label);
			print $object->getNomUrl(0, '', 1);
			$object->ref = $obj->rowid;
		}
		else
		{
			//print $langs->trans('CronNone');
		}
		print '</td>';

		// Priority
		print '<td class="right">';
		print $object->priority;
		print '</td>';

		print '<td>';
		if ($obj->jobtype=='method')
		{
		    $text=$langs->trans("CronClass");
			$texttoshow=$langs->trans('CronModule').': '.$obj->module_name.'<br>';
			$texttoshow.=$langs->trans('CronClass').': '. $obj->classesname.'<br>';
			$texttoshow.=$langs->trans('CronObject').': '. $obj->objectname.'<br>';
			$texttoshow.=$langs->trans('CronMethod').': '. $obj->methodename;
			$texttoshow.='<br>'.$langs->trans('CronArgs').': '. $obj->params;
			$texttoshow.='<br>'.$langs->trans('Comment').': '. $langs->trans($obj->note);
		}
		elseif ($obj->jobtype=='command')
		{
			$text=$langs->trans('CronCommand');
			$texttoshow=$langs->trans('CronCommand').': '.dol_trunc($obj->command);
			$texttoshow.='<br>'.$langs->trans('CronArgs').': '. $obj->params;
			$texttoshow.='<br>'.$langs->trans('Comment').': '. $langs->trans($obj->note);
		}
		print $form->textwithpicto($text, $texttoshow, 1);
		print '</td>';

		print '<td>';
		if($obj->unitfrequency == "60") print $langs->trans('CronEach')." ".($obj->frequency)." ".$langs->trans('Minutes');
		if($obj->unitfrequency == "3600") print $langs->trans('CronEach')." ".($obj->frequency)." ".$langs->trans('Hours');
		if($obj->unitfrequency == "86400") print $langs->trans('CronEach')." ".($obj->frequency)." ".$langs->trans('Days');
		if($obj->unitfrequency == "604800") print $langs->trans('CronEach')." ".($obj->frequency)." ".$langs->trans('Weeks');
		print '</td>';

		print '<td class="center">';
		if(!empty($obj->datestart)) {print dol_print_date($db->jdate($obj->datestart), 'dayhour');}
		print '</td>';

		print '<td class="center">';
		if(!empty($obj->dateend)) {print dol_print_date($db->jdate($obj->dateend), 'dayhour');}
		print '</td>';

		print '<td class="right">';
		if (!empty($obj->maxrun)) {print $obj->maxrun;}
		print '</td>';

		print '<td class="right">';
		if (!empty($obj->nbrun)) {print $obj->nbrun;} else {print '0';}
		print '</td>';

		// Date start last run
		print '<td class="center">';
		if (!empty($datelastrun)) {print dol_print_date($datelastrun, 'dayhoursec');}
		print '</td>';

		// Duration
		print '<td class="center">';
		if (!empty($datelastresult) && ($datelastresult >= $datelastrun)) {
		    print convertSecondToTime(max($datelastresult - $datelastrun, 1), 'allhourminsec');
		    //print '<br>'.($datelastresult - $datelastrun).' '.$langs->trans("seconds");
		}
		print '</td>';

		// Return code of last run
		print '<td class="center">';
		if ($obj->lastresult != '') {
			if (empty($obj->lastresult)) print $obj->lastresult;
			else print '<span class="error">'.dol_trunc($obj->lastresult).'</div>';
		}
		print '</td>';

		// Output of last run
		print '<td>';
		if(!empty($obj->lastoutput)) {print dol_trunc(nl2br($obj->lastoutput), 50);}
		print '</td>';

		print '<td class="center">';
		if (!empty($obj->datenextrun)) {
			$datenextrun = $db->jdate($obj->datenextrun);
			if (empty($obj->status)) print '<span class="opacitymedium">';
			print dol_print_date($datenextrun, 'dayhoursec');
			if ($obj->status == Cronjob::STATUS_ENABLED)
			{
				if ($obj->maxrun && $obj->nbrun >= $obj->maxrun) print img_warning($langs->trans("MaxRunReached"));
				elseif ($datenextrun && $datenextrun < $now) print img_warning($langs->trans("Late"));
			}
			if (empty($obj->status)) print '</span>';
		}
		print '</td>';

		// Status
		print '<td class="center">';
		print $object->getLibStatut(3);
		print '</td>';

		print '<td class="nowraponall right">';

		$backtourl = urlencode($_SERVER["PHP_SELF"].'?'.$param.($sortfield?'&sortfield='.$sortfield:'').($sortorder?'&sortorder='.$sortorder:''));
		if ($user->rights->cron->create)
		{
			print "<a href=\"".DOL_URL_ROOT."/cron/card.php?id=".$obj->rowid."&action=edit".($sortfield?'&sortfield='.$sortfield:'').($sortorder?'&sortorder='.$sortorder:'').$param;
			print "&backtourl=".$backtourl."\" title=\"".dol_escape_htmltag($langs->trans('Edit'))."\">".img_picto($langs->trans('Edit'), 'edit')."</a> &nbsp;";
		}
		if ($user->rights->cron->delete)
		{
			print "<a href=\"".$_SERVER["PHP_SELF"]."?id=".$obj->rowid."&action=delete".($sortfield?'&sortfield='.$sortfield:'').($sortorder?'&sortorder='.$sortorder:'').$param;
			print "\" title=\"".dol_escape_htmltag($langs->trans('CronDelete'))."\">".img_picto($langs->trans('CronDelete'), 'delete')."</a> &nbsp;";
		} else {
			print "<a href=\"#\" title=\"".dol_escape_htmltag($langs->trans('NotEnoughPermissions'))."\">".img_picto($langs->trans('NotEnoughPermissions'), 'delete')."</a> &nbsp; ";
		}
		if ($user->rights->cron->execute)
		{
		    if (!empty($obj->status)) print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$obj->rowid.'&action=execute'.(empty($conf->global->CRON_KEY)?'':'&securitykey='.$conf->global->CRON_KEY).($sortfield?'&sortfield='.$sortfield:'').($sortorder?'&sortorder='.$sortorder:'').$param."\" title=\"".dol_escape_htmltag($langs->trans('CronExecute'))."\">".img_picto($langs->trans('CronExecute'), "play").'</a>';
		    else print '<a href="#" class="cursordefault" title="'.dol_escape_htmltag($langs->trans('JobDisabled')).'">'.img_picto($langs->trans('JobDisabled'), "playdisabled").'</a>';
		} else {
			print '<a href="#" class="cursornotallowed" title="'.dol_escape_htmltag($langs->trans('NotEnoughPermissions')).'">'.img_picto($langs->trans('NotEnoughPermissions'), "playdisabled").'</a>';
		}
		if ($massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
		{
			$selected=0;
			if (in_array($obj->rowid, $arrayofselected)) $selected=1;
			print ' &nbsp; <input id="cb'.$obj->rowid.'" class="flat checkforselect valignmiddle" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected?' checked="checked"':'').'>';
		}
		print '</td>';

		print '</tr>';

		$i++;
	}
}
else
{
	print '<tr><td colspan="9" class="opacitymedium">'.$langs->trans('CronNoJobs').'</td></tr>';
}

print '</table>';
print '</div>';

print '</from>';


print '<br><br>';


dol_print_cron_urls();

llxFooter();

$db->close();
