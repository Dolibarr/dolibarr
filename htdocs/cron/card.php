<?php
/* Copyright (C) 2012      Nicolas Villa aka Boyquotes http://informetic.fr
 * Copyright (C) 2013      Florian Henry <florian.henry@open-concpt.pro>
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
 *  \file       htdocs/cron/card.php
 *  \ingroup    cron
 *  \brief      Cron Jobs Card
 */

require '../main.inc.php';

require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

// librairie jobs
require_once DOL_DOCUMENT_ROOT."/cron/class/cronjob.class.php";
require_once DOL_DOCUMENT_ROOT."/core/class/html.formcron.class.php";
require_once DOL_DOCUMENT_ROOT.'/core/lib/cron.lib.php';


$langs->load("admin");
$langs->load("cron");

if (!$user->rights->cron->create) accessforbidden();

$id=GETPOST('id','int');
$action=GETPOST('action','alpha');
$confirm=GETPOST('confirm','alpha');
$cancel=GETPOST('cancel','alpha');
$backtourl=GETPOST('backtourl','alpha');
$securitykey = GETPOST('securitykey','alpha');


/*
 * Actions
 */

$object = new Cronjob($db);
if (!empty($id))
{
	$result=$object->fetch($id);
	if ($result < 0)
	{
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

if (!empty($cancel))
{
    if (!empty($id) && empty($backtourl))
	{
		$action='';
	}
	else
	{
	    if ($backtourl)
	    {
	        header("Location: ".$backtourl);
	        exit;
	    }
	    else
	    {
	        header("Location: ".DOL_URL_ROOT.'/cron/list.php?status=-2');
		    exit;
	    }
	}
}

// Delete jobs
if ($action == 'confirm_delete' && $confirm == "yes" && $user->rights->cron->delete)
{
	$result = $object->delete($user);

	if ($result < 0)
	{
		setEventMessages($object->error, $object->errors, 'errors');
		$action='edit';
	}
	else
	{
		Header("Location: ".DOL_URL_ROOT.'/cron/list.php?status=-2');
		exit;
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
        $now = dol_now();   // Date we start

    	$result=$object->run_jobs($user->login);

    	if ($result < 0)
    	{
    		setEventMessages($object->error, $object->errors, 'errors');
    		$action='';
    	}
    	else
    	{
    		$res = $object->reprogram_jobs($user->login, $now);
    		if ($res > 0)
    		{
    			if ($object->lastresult > 0) setEventMessages($langs->trans("JobFinished"), null, 'warnings');
    			else setEventMessages($langs->trans("JobFinished"), null, 'mesgs');
    			$action='';
    		}
    		else
    		{
    			setEventMessages($object->error, $object->errors, 'errors');
    			$action='';
    		}
    	}
    }
}


if ($action=='add')
{
	$object->jobtype=GETPOST('jobtype','alpha');
	$object->label=GETPOST('label','alpha');
	$object->command=GETPOST('command','alpha');
	$object->priority=GETPOST('priority','int');
	$object->classesname=GETPOST('classesname','alpha');
	$object->objectname=GETPOST('objectname','alpha');
	$object->methodename=GETPOST('methodename','alpha');
	$object->params=GETPOST('params');
	$object->md5params=GETPOST('md5params');
	$object->module_name=GETPOST('module_name','alpha');
	$object->note=GETPOST('note','none');
	$object->datestart=dol_mktime(GETPOST('datestarthour','int'), GETPOST('datestartmin','int'), 0, GETPOST('datestartmonth','int'), GETPOST('datestartday','int'), GETPOST('datestartyear','int'));
	$object->dateend=dol_mktime(GETPOST('dateendhour','int'), GETPOST('dateendmin','int'), 0, GETPOST('dateendmonth','int'), GETPOST('dateendday','int'), GETPOST('dateendyear','int'));
	$object->datenextrun=dol_mktime(GETPOST('datenextrunhour','int'), GETPOST('datenextrunmin','int'), 0, GETPOST('datenextrunmonth','int'), GETPOST('datenextrunday','int'), GETPOST('datenextrunyear','int'));
	$object->unitfrequency=GETPOST('unitfrequency','int');
	$object->frequency=GETPOST('nbfrequency','int');
	$object->maxrun=GETPOST('maxrun','int');

	// Add cron task
	$result = $object->create($user);

	// test du Resultat de la requete
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
		$action='create';
	}
	else {
		setEventMessages($langs->trans('CronSaveSucess'), null, 'mesgs');
		$action='';
	}
}

// Save parameters
if ($action=='update')
{
	$object->id=$id;
	$object->jobtype=GETPOST('jobtype');
	$object->label=GETPOST('label');
	$object->command=GETPOST('command');
	$object->classesname=GETPOST('classesname','alpha');
	$object->priority=GETPOST('priority','int');
	$object->objectname=GETPOST('objectname','alpha');
	$object->methodename=GETPOST('methodename','alpha');
	$object->params=GETPOST('params');
	$object->md5params=GETPOST('md5params');
	$object->module_name=GETPOST('module_name','alpha');
	$object->note=GETPOST('note','none');
	$object->datestart=dol_mktime(GETPOST('datestarthour','int'), GETPOST('datestartmin','int'), 0, GETPOST('datestartmonth','int'), GETPOST('datestartday','int'), GETPOST('datestartyear','int'));
	$object->dateend=dol_mktime(GETPOST('dateendhour','int'), GETPOST('dateendmin','int'), 0, GETPOST('dateendmonth','int'), GETPOST('dateendday','int'), GETPOST('dateendyear','int'));
	$object->datenextrun=dol_mktime(GETPOST('datenextrunhour','int'), GETPOST('datenextrunmin','int'), 0, GETPOST('datenextrunmonth','int'), GETPOST('datenextrunday','int'), GETPOST('datenextrunyear','int'));
	$object->unitfrequency=GETPOST('unitfrequency','int');
	$object->frequency=GETPOST('nbfrequency','int');
	$object->maxrun=GETPOST('maxrun','int');

	// Add cron task
	$result = $object->update($user);

	// test du Resultat de la requete
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
		$action='edit';
	}
	else {
		setEventMessages($langs->trans('CronSaveSucess'), null, 'mesgs');
		$action='';
	}
}

if ($action=='activate')
{
	$object->status=1;

	// Add cron task
	$result = $object->update($user);

	// test du Resultat de la requete
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
		$action='edit';
	}
	else {
		setEventMessages($langs->trans('CronSaveSucess'), null, 'mesgs');
		$action='';
	}
}

if ($action=='inactive')
{
	$object->status=0;

	// Add cron task
	$result = $object->update($user);

	// test du Resultat de la requete
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
		$action='edit';
	}
	else {
		setEventMessages($langs->trans('CronSaveSucess'), null, 'mesgs');
		$action='';
	}
}



/*
 * View
 */

$form = new Form($db);
$formCron = new FormCron($db);

llxHeader('',$langs->trans("CronAdd"));

if ($action=='edit' || empty($action) || $action=='delete' || $action=='execute')
{
	$head=cron_prepare_head($object);
}
elseif ($action=='create')
{
	print load_fiche_titre($langs->trans("CronTask"),'','title_setup');
}

if ($conf->use_javascript_ajax)
{
	print "\n".'<script type="text/javascript" language="javascript">';
	print 'jQuery(document).ready(function () {
                    function initfields()
                    {
                        if ($("#jobtype option:selected").val()==\'method\') {
							$(".blockmethod").show();
							$(".blockcommand").hide();
						}
						if ($("#jobtype option:selected").val()==\'command\') {
							$(".blockmethod").hide();
							$(".blockcommand").show();
						}
                    }
                    initfields();
                    jQuery("#jobtype").change(function() {
                        initfields();
                    });
               })';
	print '</script>'."\n";
}

if ($action == 'delete')
{
	print $form->formconfirm($_SERVER['PHP_SELF']."?id=".$object->id,$langs->trans("CronDelete"),$langs->trans("CronConfirmDelete"),"confirm_delete",'','',1);

	$action='';
}

if ($action == 'execute'){
	print $form->formconfirm($_SERVER['PHP_SELF']."?id=".$object->id.'&securitykey='.$securitykey,$langs->trans("CronExecute"),$langs->trans("CronConfirmExecute"),"confirm_execute",'','',1);

	$action='';
}



/*
 * Create Template
 */

if (empty($object->status) && $action != 'create')
{
	setEventMessages($langs->trans("CronTaskInactive"), null, 'warnings');
}

if (($action=="create") || ($action=="edit"))
{
	print '<form name="cronform" action="'.$_SERVER["PHP_SELF"].'" method="post">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";
	print '<input type="hidden" name="backtourl" value="'.GETPOST('backtourl').'">'."\n";
	if (!empty($object->id)) {
		print '<input type="hidden" name="action" value="update">'."\n";
		print '<input type="hidden" name="id" value="'.$object->id.'">'."\n";
	} else {
		print '<input type="hidden" name="action" value="add">'."\n";
	}

	if ($action=="edit") dol_fiche_head($head, 'card', $langs->trans("CronTask"), 0, 'cron');
	else dol_fiche_head('');

	print '<table class="border" width="100%">';

	print '<tr><td class="fieldrequired titlefieldcreate">';
	print $langs->trans('CronLabel')."</td>";
	print "<td><input type=\"text\" size=\"30\" name=\"label\" value=\"".$object->label."\" /> ";
	print "</td>";
	print "<td>";
	print "</td>";
	print "</tr>\n";

	print '<tr><td class="fieldrequired">';
	print $langs->trans('CronType')."</td><td>";
	print $formCron->select_typejob('jobtype',$object->jobtype);
	print "</td>";
	print "<td>";
	print "</td>";
	print "</tr>\n";

	print '<tr class="blockmethod"><td>';
	print $langs->trans('CronModule')."</td><td>";
	print "<input type=\"text\" size=\"20\" name=\"module_name\" value=\"".$object->module_name."\" /> ";
	print "</td>";
	print "<td>";
	print $form->textwithpicto('',$langs->trans("CronModuleHelp"),1,'help');
	print "</td>";
	print "</tr>\n";

	print '<tr class="blockmethod"><td>';
	print $langs->trans('CronClassFile')."</td><td>";
	print '<input type="text" class="minwidth300" name="classesname" value="'.$object->classesname.'" /> ';
	print "</td>";
	print "<td>";
	print $form->textwithpicto('',$langs->trans("CronClassFileHelp"),1,'help');
	print "</td>";
	print "</tr>\n";

	print '<tr class="blockmethod"><td>';
	print $langs->trans('CronObject')."</td><td>";
	print "<input type=\"text\" size=\"20\" name=\"objectname\" value=\"".$object->objectname."\" /> ";
	print "</td>";
	print "<td>";
	print $form->textwithpicto('',$langs->trans("CronObjectHelp"),1,'help');
	print "</td>";
	print "</tr>\n";

	print '<tr class="blockmethod"><td>';
	print $langs->trans('CronMethod')."</td><td>";
	print '<input type="text" class="minwidth300" name="methodename" value="'.$object->methodename.'" /> ';
	print "</td>";
	print "<td>";
	print $form->textwithpicto('',$langs->trans("CronMethodHelp"),1,'help');
	print "</td>";
	print "</tr>\n";

	print '<tr class="blockmethod"><td>';
	print $langs->trans('CronArgs')."</td><td>";
	print "<input type=\"text\" class=\"quatrevingtpercent\" name=\"params\" value=\"".$object->params."\" /> ";
	print "</td>";
	print "<td>";
	print $form->textwithpicto('',$langs->trans("CronArgsHelp"),1,'help');
	print "</td>";
	print "</tr>\n";

	print '<tr class="blockcommand"><td>';
	print $langs->trans('CronCommand')."</td><td>";
	print "<input type=\"text\" size=\"50\" name=\"command\" value=\"".$object->command."\" /> ";
	print "</td>";
	print "<td>";
	print $form->textwithpicto('',$langs->trans("CronCommandHelp"),1,'help');
	print "</td>";
	print "</tr>\n";

	print '<tr><td>';
	print $langs->trans('CronNote')."</td><td>";
	$doleditor = new DolEditor('note', $object->note, '', 160, 'dolibarr_notes', 'In', true, false, 0, ROWS_4, '90%');
	$doleditor->Create();
	print "</td>";
	print "<td>";
	print "</td>";
	print "</tr>\n";

	print '<tr><td class="fieldrequired">';
	print $langs->trans('CronEvery')."</td>";
	print "<td>";
	print '<select name="nbfrequency">';
	for ($i=1; $i<=60; $i++)
	{
	    if ($object->frequency == $i)
	    {
	        print "<option value='".$i."' selected>".$i."</option>";
	    }
	    else
	    {
	        print "<option value='".$i."'>".$i."</option>";
	    }
	}
	print "</select>";
	$input = " <input type=\"radio\" name=\"unitfrequency\" value=\"60\" id=\"frequency_minute\" ";
	if($object->unitfrequency=="60")
	{
	    $input .= ' checked />';
	}
	else{
	    $input .= ' />';
	}
	$input .= "<label for=\"frequency_minute\">".$langs->trans('Minutes')."</label>";
	print $input;

	$input = " <input type=\"radio\" name=\"unitfrequency\" value=\"3600\" id=\"frequency_heures\" ";
	if($object->unitfrequency=="3600"){
	    $input .= ' checked />';
	}
	else{
	    $input .= ' />';
	}
	$input .= "<label for=\"frequency_heures\">".$langs->trans('Hours')."</label>";
	print $input;

	$input = " <input type=\"radio\" name=\"unitfrequency\" value=\"86400\" id=\"frequency_jours\" ";
	if($object->unitfrequency=="86400"){
	    $input .= ' checked />';
	}
	else{
	    $input .= ' />';
	}
	$input .= "<label for=\"frequency_jours\">".$langs->trans('Days')."</label>";
	print $input;

	$input = " <input type=\"radio\" name=\"unitfrequency\" value=\"604800\" id=\"frequency_semaine\" ";
	if($object->unitfrequency=="604800"){
	    $input .= ' checked />';
	}
	else{
	    $input .= ' />';
	}
	$input .= "<label for=\"frequency_semaine\">".$langs->trans('Weeks')."</label>";
	print $input;
	print "</td>";
	print "<td>";
	print "</td>";
	print "</tr>\n";

	print "<tr><td>";
	print $langs->trans('CronDtStart')."</td><td>";
	if(!empty($object->datestart))
	{
	    $form->select_date($object->datestart,'datestart',1,1,'',"cronform");
	}
	else
	{
	    $form->select_date('','datestart',1,1,'',"cronform");
	}
	print "</td>";
	print "<td>";
	print "</td>";
	print "</tr>\n";

	print "<tr><td>";
	print $langs->trans('CronDtEnd')."</td><td>";
	if(!empty($object->dateend)){
	    $form->select_date($object->dateend,'dateend',1,1,'',"cronform");
	}
	else{
	    $form->select_date(-1,'dateend',1,1,1,"cronform");
	}
	print "</td>";
	print "<td>";
	print "</td>";
	print "</tr>\n";

	print "<tr><td>";
	print $langs->trans('CronPriority')."</td>";
	$priority=0;
	if (!empty($object->priority)) {
	    $priority=$object->priority;
	}
	print "<td><input type=\"text\" size=\"2\" name=\"priority\" value=\"".$priority."\" /> ";
	print "</td>";
	print "<td>";
	print "</td>";
	print "</tr>\n";

	print '<tr><td>';
	$maxrun='';
	if (!empty($object->maxrun)) {
	    $maxrun=$object->maxrun;
	}
	print $langs->trans('CronMaxRun')."</td>";
	print "<td><input type=\"text\" size=\"2\" name=\"maxrun\" value=\"".$maxrun."\" /> ";
	print "</td>";
	print "<td>";
	print "</td>";
	print "</tr>\n";

	print '<tr><td>';
	print $langs->trans('CronDtNextLaunch');
	print ' ('.$langs->trans('CronFrom').')';
	print "</td><td>";
	if(!empty($object->datenextrun))
	{
	    $form->select_date($object->datenextrun,'datenextrun',1,1,'',"cronform");
	}
	else
	{
	    $form->select_date(-1,'datenextrun',1,1,'',"cronform");
	}
	print "</td>";
    print "<td>";
	print "</td>";
	print "</tr>";

	print '</table>';

	dol_fiche_end();

	print '<div align="center">';
	print '<input type="submit" name="save" class="button" value="'.$langs->trans("Save").'">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input type="submit" name="cancel" class="button" value="'.$langs->trans("Cancel").'">';
	print "</div>";

	print "</form>\n";

}
else
{
	/*
	 * view card
	 */
    $now = dol_now();

	dol_fiche_head($head, 'card', $langs->trans("CronTask"), -1, 'cron');

	$linkback = '<a href="' . DOL_URL_ROOT . '/cron/list.php?status=-2&restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';

	$morehtmlref='<div class="refidno">';
	$morehtmlref.='</div>';

	dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref);

	// box add_jobs_box
	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border" width="100%">';

	print '<tr><td class="titlefield">';
	print $langs->trans('CronLabel')."</td>";
	print "<td>".$langs->trans($object->label);
	print "</td></tr>";

	print "<tr><td>";
	print $langs->trans('CronType')."</td><td>";
	print $formCron->select_typejob('jobtype',$object->jobtype,1);
	print "</td></tr>";

	print '<tr class="blockmethod"><td>';
	print $langs->trans('CronModule')."</td><td>";
	print $object->module_name;
	print "</td></tr>";

	print '<tr class="blockmethod"><td>';
	print $langs->trans('CronClassFile')."</td><td>";
	print $object->classesname;
	print "</td></tr>";

	print '<tr class="blockmethod"><td>';
	print $langs->trans('CronObject')."</td><td>";
	print $object->objectname;
	print "</td></tr>";

	print '<tr class="blockmethod"><td>';
	print $langs->trans('CronMethod')."</td><td>";
	print $object->methodename;
	print "</td></tr>";

	print '<tr class="blockmethod"><td>';
	print $langs->trans('CronArgs')."</td><td>";
	print $object->params;
	print "</td></tr>";

	print '<tr class="blockcommand"><td>';
	print $langs->trans('CronCommand')."</td><td>";
	print $object->command;
	print "</td></tr>";

	print '<tr><td>';
	print $langs->trans('CronNote')."</td><td>";
	print $langs->trans($object->note);
	print "</td></tr>";

	print '</table>';
    print '</div>';

	print '<br>';


	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border" width="100%">';

	print '<tr><td class="titlefield">';
	print $langs->trans('CronEvery')."</td>";
	print "<td>";
	if($object->unitfrequency == "60") print $langs->trans('CronEach')." ".($object->frequency)." ".$langs->trans('Minutes');
	if($object->unitfrequency == "3600") print $langs->trans('CronEach')." ".($object->frequency)." ".$langs->trans('Hours');
	if($object->unitfrequency == "86400") print $langs->trans('CronEach')." ".($object->frequency)." ".$langs->trans('Days');
	if($object->unitfrequency == "604800") print $langs->trans('CronEach')." ".($object->frequency)." ".$langs->trans('Weeks');
	print "</td></tr>";

	print '<tr><td>';
	print $langs->trans('CronDtStart')."</td><td>";
	if(!empty($object->datestart)) {print dol_print_date($object->datestart,'dayhoursec');}
	print "</td></tr>";

	print "<tr><td>";
	print $langs->trans('CronDtEnd')."</td><td>";
	if(!empty($object->dateend)) {print dol_print_date($object->dateend,'dayhoursec');}
	print "</td></tr>";

	print "<tr><td>";
	print $langs->trans('CronPriority')."</td>";
	print "<td>".$object->priority;
	print "</td></tr>";

	print "<tr><td>";
	print $langs->trans('CronMaxRun')."</td>";
	print "<td>";
	print $object->maxrun>0?$object->maxrun:'';
	print "</td></tr>";

	print "<tr><td>";
	print $langs->trans('CronNbRun')."</td>";
	print "<td>".$object->nbrun;
	print "</td></tr>";

	// Date next run (from)
	print '<tr><td>';
	print $langs->trans('CronDtNextLaunch');
	print ' ('.$langs->trans('CronFrom').')';
	print "</td><td>";
	//print '<strong>';
	if (! $object->status) print $langs->trans("Disabled");
	elseif (!empty($object->datenextrun)) { print img_picto('','object_calendarday').' '.dol_print_date($object->datenextrun,'dayhoursec');}
	else {print $langs->trans('CronNone');}
	//print '</strong>';
	if ($object->maxnbrun && $object->nbrun >= $object->maxrun) print img_warning($langs->trans("Finished"));
	if ($object->datenextrun && $object->datenextrun < $now) print img_warning($langs->trans("Late"));
	print "</td></tr>";

	print '</table>';
	print '</div>';

	print '<br>';

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border" width="100%">';

	print '<tr><td class="titlefield">';
	print $langs->trans('CronDtLastLaunch')."</td><td>";
	if(!empty($object->datelastrun)) {print dol_print_date($object->datelastrun,'dayhoursec');} else {print $langs->trans('CronNone');}
	print "</td></tr>";

	print '<tr><td>';
	print $langs->trans('CronDtLastResult')."</td><td>";
	if(!empty($object->datelastresult)) {print dol_print_date($object->datelastresult,'dayhoursec');} else {print $langs->trans('CronNone');}
	print "</td></tr>";

	print '<tr><td>';
	print $langs->trans('CronLastResult')."</td><td>";
	print $object->lastresult;
	print "</td></tr>";

	print '<tr><td>';
	print $langs->trans('CronLastOutput')."</td><td>";
	print nl2br($object->lastoutput);
	print "</td></tr>";

	print '</table>';
    print '</div>';

	dol_fiche_end();


	print "\n\n<div class=\"tabsAction\">\n";
	if (! $user->rights->cron->create) {
		print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->transnoentitiesnoconv("NotEnoughPermissions")).'">'.$langs->trans("Edit").'</a>';
	} else {
		print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=edit&id='.$object->id.'">'.$langs->trans("Edit").'</a>';
	}

	if ((empty($user->rights->cron->execute)))
	{
		print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->transnoentitiesnoconv("NotEnoughPermissions")).'">'.$langs->trans("CronExecute").'</a>';
	}
	else if (empty($object->status))
	{
		print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->transnoentitiesnoconv("JobDisabled")).'">'.$langs->trans("CronExecute").'</a>';
	}
	else {
		print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=execute&id='.$object->id.(empty($conf->global->CRON_KEY)?'':'&securitykey='.$conf->global->CRON_KEY).'">'.$langs->trans("CronExecute").'</a>';
	}

	if (! $user->rights->cron->create) {
	    print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->transnoentitiesnoconv("NotEnoughPermissions")).'">'.$langs->trans("CronStatusActiveBtn").'/'.$langs->trans("CronStatusInactiveBtn").'</a>';
	} else {
	    if (empty($object->status)) {
	        print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=activate&id='.$object->id.'">'.$langs->trans("CronStatusActiveBtn").'</a>';
	    } else {
	        print '<a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?action=inactive&id='.$object->id.'">'.$langs->trans("CronStatusInactiveBtn").'</a>';
	    }
	}

	if (! $user->rights->cron->delete) {
		print '<a class="butActionDeleteRefused" href="#" title="'.dol_escape_htmltag($langs->transnoentitiesnoconv("NotEnoughPermissions")).'">'.$langs->trans("Delete").'</a>';
	} else {
		print '<a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?action=delete&id='.$object->id.'">'.$langs->trans("Delete").'</a>';
	}
	print '</div>';

	print '<br>';
}


llxFooter();

$db->close();
