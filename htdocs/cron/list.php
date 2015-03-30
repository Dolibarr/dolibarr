<?php
/* Copyright (C) 2012      Nicolas Villa aka Boyquotes http://informetic.fr
 * Copyright (C) 2013      Florian Henry <florian.henry@open-concept.pro>
 * Copyright (C) 2013      Laurent Destailleur <eldy@users.srouceforge.net>
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
 *  \file       htdocs/cron/cron/list.php
 *  \ingroup    cron
 *  \brief      Lists Jobs
 */


require '../main.inc.php';
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once DOL_DOCUMENT_ROOT."/cron/class/cronjob.class.php";
require_once DOL_DOCUMENT_ROOT.'/core/lib/cron.lib.php';

$langs->load("admin");
$langs->load("cron");

if (!$user->rights->cron->read) accessforbidden();


/*
 * Actions
 */

$action=GETPOST('action','alpha');
$confirm=GETPOST('confirm','alpha');
$id=GETPOST('id','int');

$sortorder=GETPOST('sortorder','alpha');
$sortfield=GETPOST('sortfield','alpha');
$page=GETPOST('page','int');
$status=GETPOST('status','int');

//Search criteria
$search_label=GETPOST("search_label",'alpha');

if (empty($sortorder)) $sortorder="DESC";
if (empty($sortfield)) $sortfield="t.datenextrun";
if (empty($arch)) $arch = 0;
if ($page == -1) {
	$page = 0 ;
}

$limit = $conf->global->MAIN_SIZE_LISTE_LIMIT;
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

// Do we click on purge search criteria ?
if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter")) // Both test are required to be compatible with all browsers
{
	$search_label='';
	$status=-1;
}

$filter=array();
if (!empty($search_label))
{
	$filter['t.label']=$search_label;
}

// Delete jobs
if ($action == 'confirm_delete' && $confirm == "yes" && $user->rights->cron->delete){

	//Delete cron task
	$object = new Cronjob($db);
	$object->id=$id;
	$result = $object->delete($user);

	if ($result < 0) {
		setEventMessage($object->error,'errors');
	}
}

// Execute jobs
if ($action == 'confirm_execute' && $confirm == "yes" && $user->rights->cron->execute){

	//Execute jobs
	$object = new Cronjob($db);
	$job = $object->fetch($id);

	$result = $object->run_jobs($user->login);
	if ($result < 0) {
		setEventMessage($object->error,'errors');
	}
	else
	{
		$res = $object->reprogram_jobs($user->login);
		if ($res > 0)
		{
			if ($object->lastresult > 0) setEventMessage($langs->trans("JobFinished"),'warnings');
			else setEventMessage($langs->trans("JobFinished"),'mesgs');
			$action='';
		}
		else
		{
			setEventMessage($object->error,'errors');
			$action='';
		}
	}

	header("Location: ".DOL_URL_ROOT.'/cron/list.php?status=-1');		// Make a call to avoid to run twice job when using back
	exit;
}


/*
 * View
 */

$form = new Form($db);

$pagetitle=$langs->trans("CronList");

llxHeader('',$pagetitle);

print_fiche_titre($pagetitle,'','setup');

print $langs->trans('CronInfo');

if ($action == 'delete')
{
	print $form->formconfirm($_SERVER['PHP_SELF']."?id=".$id.'&status='.$status,$langs->trans("CronDelete"),$langs->trans("CronConfirmDelete"),"confirm_delete",'','',1);

}

if ($action == 'execute')
{
	print $form->formconfirm($_SERVER['PHP_SELF']."?id=".$id.'&status='.$status,$langs->trans("CronExecute"),$langs->trans("CronConfirmExecute"),"confirm_execute",'','',1);

}

// liste des jobs creer
$object = new Cronjob($db);
$result=$object->fetch_all($sortorder, $sortfield, $limit, $offset, $status, $filter);
if ($result < 0)
{
	setEventMessage($object->error,'errors');
}


print "<br><br>";


print '<form method="GET" action="'.$url_form.'" name="search_form">'."\n";
print '<input type="hidden" name="status" value="'.$status.'" >';

print '<table width="100%" class="noborder">';
print '<tr class="liste_titre">';
$arg_url='&page='.$page.'&status='.$status.'&search_label='.$search_label;
print_liste_field_titre($langs->trans("CronLabel"),$_SERVER["PHP_SELF"],"t.label","",$arg_url,'',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("CronTask"),'','',"",$arg_url,'',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("CronDtStart"),$_SERVER["PHP_SELF"],"t.datestart","",$arg_url,'',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("CronDtEnd"),$_SERVER["PHP_SELF"],"t.dateend","",$arg_url,'',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("CronDtLastLaunch"),$_SERVER["PHP_SELF"],"t.datelastrun","",$arg_url,'',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("CronDtNextLaunch"),$_SERVER["PHP_SELF"],"t.datenextrun","",$arg_url,'',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("CronFrequency"),'',"","",$arg_url,'',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("CronNbRun"),$_SERVER["PHP_SELF"],"t.nbrun","",$arg_url,'',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("CronLastResult"),$_SERVER["PHP_SELF"],"t.lastresult","",$arg_url,'',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("CronLastOutput"),$_SERVER["PHP_SELF"],"t.lastoutput","",$arg_url,'',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("Enabled"),$_SERVER["PHP_SELF"],"t.status","",$arg_url,'align="center"',$sortfield,$sortorder);
print '<td></td>';
print '</tr>';

print '<tr class="liste_titre">';

print '<td class="liste_titre">';
print '<input type="text" class="flat" name="search_label" value="'.$search_label.'" size="10">';
print '</td>';
print '<td>&nbsp;</td>';
print '<td>&nbsp;</td>';
print '<td>&nbsp;</td>';
print '<td>&nbsp;</td>';
print '<td>&nbsp;</td>';
print '<td>&nbsp;</td>';
print '<td>&nbsp;</td>';
print '<td>&nbsp;</td>';
print '<td>&nbsp;</td>';
print '<td class="liste_titre" align="center">';
print $form->selectarray('status', array('0'=>$langs->trans("No"),'1'=>$langs->trans("Yes")), $status, 1);
print '</td><td class="liste_titre" align="right">';
print '&nbsp;';
print '<input class="liste_titre" type="image" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
print '&nbsp; ';
print '<input type="image" class="liste_titre" name="button_removefilter" src="'.img_picto($langs->trans("Search"),'searchclear.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
print '</td>';

print '</tr>';


if (count($object->lines) > 0)
{
	// Loop on each active job
	$style='impair';
	foreach($object->lines as $line)
	{
		// title profil
		if ($style=='pair') {$style='impair';}
		else {$style='pair';}

		print '<tr class="'.$style.'">';

		print '<td>';
		if(!empty($line->label)) {
			print '<a href="'.DOL_URL_ROOT.'/cron/card.php?id='.$line->id.'">'.$line->label.'</a>';
		}
		else {
			print $langs->trans('CronNone');
		}
		print '</td>';

		print '<td>';
		if ($line->jobtype=='method') {
			print $langs->trans('CronModule').':'.$line->module_name.'<BR>';
			print $langs->trans('CronClass').':'. $line->classesname.'<BR>';
			print $langs->trans('CronObject').':'. $line->objectname.'<BR>';
			print $langs->trans('CronMethod').':'. $line->methodename;
			if(!empty($line->params)) {
				print '<br>'.$langs->trans('CronArgs').':'. $line->params;
			}

		}elseif ($line->jobtype=='command') {
			print $langs->trans('CronCommand').':'. dol_trunc($line->command);
			if(!empty($line->params)) {
				print '<br>'.$langs->trans('CronArgs').':'. $line->params;
			}
		}
		print '</td>';

		print '<td>';
		if(!empty($line->datestart)) {print dol_print_date($line->datestart,'dayhour');} else {print $langs->trans('CronNone');}
		print '</td>';

		print '<td>';
		if(!empty($line->dateend)) {print dol_print_date($line->dateend,'dayhour');} else {print $langs->trans('CronNone');}
		print '</td>';

		print '<td>';
		if(!empty($line->datelastrun)) {print dol_print_date($line->datelastrun,'dayhour');} else {print $langs->trans('CronNone');}
		print '</td>';

		print '<td>';
		if(!empty($line->datenextrun)) {print dol_print_date($line->datenextrun,'dayhour');} else {print $langs->trans('CronNone');}
		print '</td>';

		print '<td>';
		if($line->unitfrequency == "60") print $langs->trans('CronEach')." ".($line->frequency/$line->unitfrequency)." ".$langs->trans('Minutes');
		if($line->unitfrequency == "3600") print $langs->trans('CronEach')." ".($line->frequency/$line->unitfrequency)." ".$langs->trans('Hours');
		if($line->unitfrequency == "86400") print $langs->trans('CronEach')." ".($line->frequency/$line->unitfrequency)." ".$langs->trans('Days');
		if($line->unitfrequency == "604800") print $langs->trans('CronEach')." ".($line->frequency/$line->unitfrequency)." ".$langs->trans('Weeks');
		print '</td>';

		print '<td>';
		if(!empty($line->nbrun)) {print $line->nbrun;} else {print '0';}
		print '</td>';

		print '<td>';
		if(!empty($line->lastresult)) {print dol_trunc($line->lastresult);} else {print $langs->trans('CronNone');}
		print '</td>';

		print '<td>';
		if(!empty($line->lastoutput)) {print dol_trunc(nl2br($line->lastoutput),100);} else {print $langs->trans('CronNone');}
		print '</td>';

		print '<td align="center">';
		print yn($line->status);
		print '</td>';

		print '<td align="right">';
		if ($user->rights->cron->delete) {
			print "<a href=\"".$_SERVER["PHP_SELF"]."?id=".$line->id."&status=".$status."&action=delete\" title=\"".$langs->trans('CronDelete')."\">".img_delete()."</a> &nbsp;";
		} else {
			print "<a href=\"#\" title=\"".$langs->trans('NotEnoughPermissions')."\">".img_delete()."</a> &nbsp; ";
		}
		if ($user->rights->cron->execute) {
			print "<a href=\"".$_SERVER["PHP_SELF"]."?id=".$line->id."&status=".$status."&action=execute\" title=\"".$langs->trans('CronExecute')."\">".img_picto('',"play")."</a>";
		} else {
			print "<a href=\"#\" title=\"".$langs->trans('NotEnoughPermissions')."\">".img_picto('',"execute")."</a>";
		}
		print '</td>';

		print '</tr>';
	}
}
else
{
	print '<tr><td colspan="8">'.$langs->trans('CronNoJobs').'</td></tr>';
}

print '</table>';

print '</from>';



print "\n<div class=\"tabsAction\">\n";

if (! $user->rights->cron->create)
{
	print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->transnoentitiesnoconv("NotEnoughPermissions")).'">'.$langs->trans("CronCreateJob").'</a>';
}
else
{
	print '<a class="butAction" href="'.DOL_URL_ROOT.'/cron/card.php?action=create">'.$langs->trans("CronCreateJob").'</a>';
}

print '</div>';

print '<br>';


dol_print_cron_urls();


llxFooter();

$db->close();
