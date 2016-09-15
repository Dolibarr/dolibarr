<?php
/* Copyright (C) 2012      Nicolas Villa aka Boyquotes http://informetic.fr
 * Copyright (C) 2013      Florian Henry       <florian.henry@open-concept.pro>
 * Copyright (C) 2013-2016 Laurent Destailleur <eldy@users.sourceforge.net>
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
$langs->load("bills");

if (!$user->rights->cron->read) accessforbidden();

$action=GETPOST('action','alpha');
$confirm=GETPOST('confirm','alpha');
$id=GETPOST('id','int');

$limit = GETPOST('limit')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) {
    $page = 0;
}
$offset = $limit * $page;
if (! $sortorder) $sortorder='ASC';
if (! $sortfield) $sortfield='t.label';
$pageprev = $page - 1;
$pagenext = $page + 1;

$status=GETPOST('status','int');
if ($status == '') $status=-2;

//Search criteria
$search_label=GETPOST("search_label",'alpha');
$securitykey = GETPOST('securitykey','alpha');

if (empty($sortorder)) $sortorder="DESC";
if (empty($sortfield)) $sortfield="t.status";
if (empty($arch)) $arch = 0;
if ($page == -1) {
    $page = 0 ;
}


/*
 * Actions
 */

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
    		if ($resrunjob >= 0)	// We add result of reprogram ony if no error message already reported 
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
    
    	header("Location: ".DOL_URL_ROOT.'/cron/list.php?status=-2');		// Make a call to avoid to run twice job when using back
    	exit;
    }
}


/*
 * View
 */

$form = new Form($db);

$pagetitle=$langs->trans("CronList");

llxHeader('',$pagetitle);

// list of jobs created
// TODO Replace this with an embedded select.
$object = new Cronjob($db);
$result=$object->fetch_all($sortorder, $sortfield, $limit, $offset, $status, $filter);
if ($result < 0)
{
	setEventMessages($object->error, $object->errors, 'errors');
}
$nbtotalofrecord=0;
$num=count($object->lines);

$param='&page='.$page.'&status='.$status.'&search_label='.$search_label;

$stringcurrentdate = $langs->trans("CurrentHour").': '.dol_print_date(dol_now(), 'dayhour');



if ($action == 'delete')
{
    print $form->formconfirm($_SERVER['PHP_SELF']."?id=".$id.'&status='.$status,$langs->trans("CronDelete"),$langs->trans("CronConfirmDelete"),"confirm_delete",'','',1);

}

if ($action == 'execute')
{
    print $form->formconfirm($_SERVER['PHP_SELF']."?id=".$id.'&status='.$status.'&securitykey='.$securitykey,$langs->trans("CronExecute"),$langs->trans("CronConfirmExecute"),"confirm_execute",'','',1);

}


print '<form method="GET" action="'.$url_form.'" name="search_form">'."\n";
print '<input type="hidden" name="status" value="'.$status.'" >';
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="viewstatut" value="'.$viewstatut.'">';

print_barre_liste($pagetitle, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $stringcurrentdate, $num, 0, 'title_setup', 0, '', '', $limit);


// Line with explanation and button new job
if (! $user->rights->cron->create)
{
    $buttontoshow.='<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->transnoentitiesnoconv("NotEnoughPermissions")).'">'.$langs->trans("CronCreateJob").'</a>';
}
else
{
    $buttontoshow.='<a class="butAction" style="margin-right: 0px;margin-left: 0px;" href="'.DOL_URL_ROOT.'/cron/card.php?action=create">'.$langs->trans("CronCreateJob").'</a>';
}
print '<table class="centpercent"><tr><td>'.$langs->trans('CronInfo').'</td><td class="right">'.$buttontoshow.'</td></tr></table>';
print '<br>';


print '<table width="100%" class="noborder">';
print '<tr class="liste_titre">';
print_liste_field_titre($langs->trans("ID"),$_SERVER["PHP_SELF"],"t.rowid","",$param,'',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("CronLabel"),$_SERVER["PHP_SELF"],"t.label","",$param,'',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("CronTask"),'','',"",$param,'',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("CronFrequency"),'',"","",$param,'',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("CronDtStart"),$_SERVER["PHP_SELF"],"t.datestart","",$param,'align="center"',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("CronDtEnd"),$_SERVER["PHP_SELF"],"t.dateend","",$param,'align="center"',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("CronMaxRun"),$_SERVER["PHP_SELF"],"t.maxrun","",$param,'align="right"',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("CronNbRun"),$_SERVER["PHP_SELF"],"t.nbrun","",$param,'align="right"',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("CronDtNextLaunch"),$_SERVER["PHP_SELF"],"t.datenextrun","",$param,'align="center"',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("CronDtLastLaunch"),$_SERVER["PHP_SELF"],"t.datelastrun","",$param,'align="center"',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("CronLastResult"),$_SERVER["PHP_SELF"],"t.lastresult","",$param,'align="center"',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("CronLastOutput"),$_SERVER["PHP_SELF"],"t.lastoutput","",$param,'',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"t.status","",$param,'align="center"',$sortfield,$sortorder);
print_liste_field_titre('');
print "</tr>\n";

print '<tr class="liste_titre">';
print '<td>&nbsp;</td>';
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
print '<td>&nbsp;</td>';
print '<td class="liste_titre" align="center">';
print $form->selectarray('status', array('0'=>$langs->trans("Disabled"), '1'=>$langs->trans("Enabled"), '-2'=>$langs->trans("EnabledAndDisabled"), '2'=>$langs->trans("Archived")), $status, 1);
print '</td><td class="liste_titre" align="right">';
print '<input class="liste_titre" type="image" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
print '<input type="image" class="liste_titre" name="button_removefilter" src="'.img_picto($langs->trans("Search"),'searchclear.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
print '</td>';
print '</tr>';


if ($num > 0)
{
	// Loop on each job
	$style='pair';
	foreach($object->lines as $line)
	{
	    if (! verifCond($line->test)) continue;        // Discard line with test = false
	    
		// title profil
		if ($style=='pair') {$style='impair';}
		else {$style='pair';}

		print '<tr class="'.$style.'">';

		print '<td class="nowrap">';
		print '<a href="'.DOL_URL_ROOT.'/cron/card.php?id='.$line->id.'">';
		print img_picto('', 'object_cron').' ';
		print $line->id;
		print '</a>';
		print '</td>';

		print '<td>';
		if (! empty($line->label))
		{
			print '<a href="'.DOL_URL_ROOT.'/cron/card.php?id='.$line->id.'">'.$langs->trans($line->label).'</a>';
		}
		else
		{
			//print $langs->trans('CronNone');
		}
		print '</td>';

		print '<td>';
		if ($line->jobtype=='method')
		{
		    $text=$langs->trans("CronClass");
			$texttoshow=$langs->trans('CronModule').': '.$line->module_name.'<br>';
			$texttoshow.=$langs->trans('CronClass').': '. $line->classesname.'<br>';
			$texttoshow.=$langs->trans('CronObject').': '. $line->objectname.'<br>';
			$texttoshow.=$langs->trans('CronMethod').': '. $line->methodename;
			$texttoshow.='<br>'.$langs->trans('CronArgs').': '. $line->params;
			$texttoshow.='<br>'.$langs->trans('Comment').': '. $langs->trans($line->note);
		}
		elseif ($line->jobtype=='command') 
		{
			$text=$langs->trans('CronCommand');
			$texttoshow=$langs->trans('CronCommand').': '.dol_trunc($line->command);
			$texttoshow.='<br>'.$langs->trans('CronArgs').': '. $line->params;
			$texttoshow.='<br>'.$langs->trans('Comment').': '. $langs->trans($line->note);
		}
		print $form->textwithpicto($text, $texttoshow, 1);
		print '</td>';

		print '<td>';
		if($line->unitfrequency == "60") print $langs->trans('CronEach')." ".($line->frequency)." ".$langs->trans('Minutes');
		if($line->unitfrequency == "3600") print $langs->trans('CronEach')." ".($line->frequency)." ".$langs->trans('Hours');
		if($line->unitfrequency == "86400") print $langs->trans('CronEach')." ".($line->frequency)." ".$langs->trans('Days');
		if($line->unitfrequency == "604800") print $langs->trans('CronEach')." ".($line->frequency)." ".$langs->trans('Weeks');
		print '</td>';

		print '<td class="center">';
		if(!empty($line->datestart)) {print dol_print_date($line->datestart,'dayhour');}
		print '</td>';

		print '<td class="center">';
		if(!empty($line->dateend)) {print dol_print_date($line->dateend,'dayhour');}
		print '</td>';

		print '<td align="right">';
		if (!empty($line->maxrun)) {print $line->maxrun;}
		print '</td>';
		
		print '<td align="right">';
		if (!empty($line->nbrun)) {print $line->nbrun;} else {print '0';}
		print '</td>';

		print '<td class="center">';
		if(!empty($line->datenextrun)) {print dol_print_date($line->datenextrun,'dayhour');}
		print '</td>';

		print '<td class="center">';
		if(!empty($line->datelastrun)) {print dol_print_date($line->datelastrun,'dayhour');}
		print '</td>';

		print '<td class="center">';
		if ($line->lastresult != '') {print dol_trunc($line->lastresult);}
		print '</td>';

		print '<td>';
		if(!empty($line->lastoutput)) {print dol_trunc(nl2br($line->lastoutput),50);}
		print '</td>';

		// Status
		print '<td align="center">';
		if ($line->status == 1) print $langs->trans("Enabled");
		elseif ($line->status == 2) print $langs->trans("Archived");
		else print $langs->trans("Disabled");
		print '</td>';

		print '<td align="right" class="nowrap">';
		if ($user->rights->cron->create)
		{
			print "<a href=\"".DOL_URL_ROOT."/cron/card.php?id=".$line->id."&action=edit".($sortfield?'&sortfield='.$sortfield:'').($sortorder?'&sortorder='.$sortorder:'').$param."&backtourl=".urlencode($_SERVER["PHP_SELF"])."\" title=\"".dol_escape_htmltag($langs->trans('Edit'))."\">".img_picto($langs->trans('Edit'),'edit')."</a> &nbsp;";
		}
		if ($user->rights->cron->delete)
		{
			print "<a href=\"".$_SERVER["PHP_SELF"]."?id=".$line->id."&action=delete".($sortfield?'&sortfield='.$sortfield:'').($sortorder?'&sortorder='.$sortorder:'').$param."\" title=\"".dol_escape_htmltag($langs->trans('CronDelete'))."\">".img_picto($langs->trans('CronDelete'),'delete')."</a> &nbsp;";
		} else {
			print "<a href=\"#\" title=\"".dol_escape_htmltag($langs->trans('NotEnoughPermissions'))."\">".img_picto($langs->trans('NotEnoughPermissions'), 'delete')."</a> &nbsp; ";
		}
		if ($user->rights->cron->execute)
		{
		    if (!empty($line->status)) print "<a href=\"".$_SERVER["PHP_SELF"]."?id=".$line->id."&action=execute".(empty($conf->global->CRON_KEY)?'':'&securitykey='.$conf->global->CRON_KEY).($sortfield?'&sortfield='.$sortfield:'').($sortorder?'&sortorder='.$sortorder:'').$param."\" title=\"".dol_escape_htmltag($langs->trans('CronExecute'))."\">".img_picto($langs->trans('CronExecute'),"play")."</a>";
		    else print "<a href=\"#\" title=\"".dol_escape_htmltag($langs->trans('JobDisabled'))."\">".img_picto($langs->trans('JobDisabled'),"play")."</a>";
		} else {
			print "<a href=\"#\" title=\"".dol_escape_htmltag($langs->trans('NotEnoughPermissions'))."\">".img_picto($langs->trans('NotEnoughPermissions'),"play")."</a>";
		}
		print '</td>';

		print '</tr>';
	}
}
else
{
	print '<tr><td colspan="9">'.$langs->trans('CronNoJobs').'</td></tr>';
}

print '</table>';

print '</from>';


print '<br><br>';


dol_print_cron_urls();


llxFooter();

$db->close();
