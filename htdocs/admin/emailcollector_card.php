<?php
/* Copyright (C) 2018 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *   	\file       htdocs/admin/emailcollector_card.php
 *		\ingroup    emailcollector
 *		\brief      Page to create/edit/view emailcollector
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/events.class.php';

include_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
include_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
include_once DOL_DOCUMENT_ROOT.'/emailcollector/class/emailcollector.class.php';
include_once DOL_DOCUMENT_ROOT.'/emailcollector/class/emailcollectorfilter.class.php';
include_once DOL_DOCUMENT_ROOT.'/emailcollector/class/emailcollectoraction.class.php';
include_once DOL_DOCUMENT_ROOT.'/emailcollector/lib/emailcollector.lib.php';

if (!$user->admin) accessforbidden();
if (empty($conf->emailcollector->enabled)) accessforbidden();

// Load traductions files required by page
$langs->loadLangs(array("admin", "mails", "other"));

// Get parameters
$id = GETPOST('id', 'int');
$ref        = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm    = GETPOST('confirm', 'alpha');
$cancel     = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'myobjectcard'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');

$operationid = GETPOST('operationid', 'int');

// Initialize technical objects
$object = new EmailCollector($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->emailcollector->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('emailcollectorcard')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criterias
$search_all = trim(GETPOST("search_all", 'alpha'));
$search = array();
foreach ($object->fields as $key => $val) {
	if (GETPOST('search_'.$key, 'alpha')) $search[$key] = GETPOST('search_'.$key, 'alpha');
}

if (GETPOST('saveoperation2')) $action = 'updateoperation';
if (empty($action) && empty($id) && empty($ref)) $action = 'view';

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once.

// Security check - Protection if external user
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (($object->statut == MyObject::STATUS_DRAFT) ? 1 : 0);
//$result = restrictedArea($user, 'mymodule', $object->id, '', '', 'fk_soc', 'rowid', $isdraft);

$permissionnote = $user->rights->emailcollector->write; // Used by the include of actions_setnotes.inc.php
$permissiondellink = $user->rights->emailcollector->write; // Used by the include of actions_dellink.inc.php
$permissiontoadd = $user->rights->emailcollector->write; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php

$debuginfo = '';


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	$error = 0;

	$permissiontoadd = 1;
	$permissiontodelete = 1;
	if (empty($backtopage)) $backtopage = DOL_URL_ROOT.'/admin/emailcollector_card.php?id='.($id > 0 ? $id : '__ID__');
	$backurlforlist = DOL_URL_ROOT.'/admin/emailcollector_list.php';

	// Actions cancel, add, update, delete or clone
	include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

	// Actions when linking object each other
	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';
}

if (GETPOST('addfilter', 'alpha'))
{
	$emailcollectorfilter = new EmailCollectorFilter($db);
	$emailcollectorfilter->type = GETPOST('filtertype', 'aZ09');
	$emailcollectorfilter->rulevalue = GETPOST('rulevalue', 'alpha');
	$emailcollectorfilter->fk_emailcollector = $object->id;
	$emailcollectorfilter->status = 1;
	$result = $emailcollectorfilter->create($user);

	if ($result > 0)
	{
		$object->fetchFilters();
	}
	else
	{
		setEventMessages($emailcollectorfilter->errors, $emailcollectorfilter->error, 'errors');
	}
}

if ($action == 'deletefilter')
{
	$emailcollectorfilter = new EmailCollectorFilter($db);
	$emailcollectorfilter->fetch(GETPOST('filterid', 'int'));
	$result = $emailcollectorfilter->delete($user);
	if ($result > 0)
	{
		$object->fetchFilters();
	}
	else
	{
		setEventMessages($emailcollectorfilter->errors, $emailcollectorfilter->error, 'errors');
	}
}

if (GETPOST('addoperation', 'alpha'))
{
	$emailcollectoroperation = new EmailCollectorAction($db);
	$emailcollectoroperation->type = GETPOST('operationtype', 'aZ09');
	$emailcollectoroperation->actionparam = GETPOST('operationparam', 'none');
	$emailcollectoroperation->fk_emailcollector = $object->id;
	$emailcollectoroperation->status = 1;
	$emailcollectoroperation->position = 50;

	$result = $emailcollectoroperation->create($user);

	if ($result > 0)
	{
		$object->fetchActions();
	}
	else
	{
		setEventMessages($emailcollectoroperation->errors, $emailcollectoroperation->error, 'errors');
	}
}

if ($action == 'updateoperation')
{
    $emailcollectoroperation = new EmailCollectorAction($db);
    $emailcollectoroperation->fetch(GETPOST('rowidoperation2', 'int'));

    $emailcollectoroperation->actionparam = GETPOST('operationparam2', 'none');

    $result = $emailcollectoroperation->update($user);

    if ($result > 0)
    {
        $object->fetchActions();
    }
    else
    {
        setEventMessages($emailcollectoroperation->errors, $emailcollectoroperation->error, 'errors');
    }
}
if ($action == 'deleteoperation')
{
	$emailcollectoroperation = new EmailCollectorAction($db);
	$emailcollectoroperation->fetch(GETPOST('operationid', 'int'));
	$result = $emailcollectoroperation->delete($user);
	if ($result > 0)
	{
		$object->fetchActions();
	}
	else
	{
		setEventMessages($emailcollectoroperation->errors, $emailcollectoroperation->error, 'errors');
	}
}

if ($action == 'confirm_collect')
{
	dol_include_once('/emailcollector/class/emailcollector.class.php');

	$res = $object->doCollectOneCollector();
	if ($res > 0)
	{
	    $debuginfo = $object->debuginfo;
	    setEventMessages($object->lastresult, null, 'mesgs');
	}
	else
	{
	    $debuginfo = $object->debuginfo;
	    setEventMessages($object->error, null, 'errors');
	}

	$action = '';
}




/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

$help_url = "EN:Module_EMail_Collector|FR:Module_Collecteur_de_courrier_électronique|ES:Module_EMail_Collector";

llxHeader('', 'EmailCollector', $help_url);

// Example : Adding jquery code
print '<script type="text/javascript" language="javascript">
jQuery(document).ready(function() {
	function init_myfunc()
	{
		jQuery("#myid").removeAttr(\'disabled\');
		jQuery("#myid").attr(\'disabled\',\'disabled\');
	}
	init_myfunc();
	jQuery("#mybutton").click(function() {
		init_myfunc();
	});
});
</script>';

// Part to create
if ($action == 'create') {
	print load_fiche_titre($langs->trans("NewEmailCollector", $langs->transnoentitiesnoconv("EmailCollector")));

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

	dol_fiche_head(array(), '');

	print '<table class="border centpercent tableforfield">'."\n";

	//unset($fields[]);

	// Common attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_add.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

	print '</table>'."\n";

	dol_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" name="add" value="'.dol_escape_htmltag($langs->trans("Create")).'">';
	print '&nbsp; ';
	print '<input type="'.($backtopage ? "submit" : "button").'" class="button" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'"'.($backtopage ? '' : ' onclick="javascript:history.go(-1)"').'>'; // Cancel for create does not post form if we don't know the backtopage
	print '</div>';

	print '</form>';
}

// Part to edit record
if (($id || $ref) && $action == 'edit')
{
	print load_fiche_titre($langs->trans("EmailCollector"));

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';

	dol_fiche_head();

	print '<table class="border centpercent tableforfield">'."\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_edit.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';

	print '</table>';

	dol_fiche_end();

	print '<div class="center"><input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
	print ' &nbsp; <input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

	print '</form>';
}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create')))
{
	$res = $object->fetch_optionals();

	$object->fetchFilters();
	$object->fetchActions();

	$head = emailcollectorPrepareHead($object);
	dol_fiche_head($head, 'card', $langs->trans("EmailCollector"), -1, 'emailcollector');

	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete')
	{
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteEmailCollector'), $langs->trans('ConfirmDeleteEmailCollector'), 'confirm_delete', '', 0, 1);
	}

	// Clone confirmation
	if ($action == 'clone') {
		// Create an array for form
		$formquestion = array();
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneEmailCollector', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
	}

	// Confirmation of action process
	if ($action == 'collect') {
		$formquestion = array(
			'text' => $langs->trans("EmailCollectorConfirmCollect"),
		);
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('EmailCollectorConfirmCollectTitle'), $text, 'confirm_collect', $formquestion, 0, 1, 220);
	}

	// Call Hook formConfirm
	$parameters = array('formConfirm' => $formconfirm, 'lineid' => $lineid);
	$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) $formconfirm .= $hookmanager->resPrint;
	elseif ($reshook > 0) $formconfirm = $hookmanager->resPrint;

	// Print form confirm
	print $formconfirm;

	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="'.dol_buildpath('/admin/emailcollector_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	/*
		// Ref bis
		$morehtmlref.=$form->editfieldkey("RefBis", 'ref_client', $object->ref_client, $object, $user->rights->emailcollector->creer, 'string', '', 0, 1);
		$morehtmlref.=$form->editfieldval("RefBis", 'ref_client', $object->ref_client, $object, $user->rights->emailcollector->creer, 'string', '', null, null, '', 1);
		// Thirdparty
		$morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $soc->getNomUrl(1);
		// Project
		if (! empty($conf->projet->enabled))
		{
		    $langs->load("projects");
		    $morehtmlref.='<br>'.$langs->trans('Project') . ' ';
		    if ($user->rights->emailcollector->creer)
		    {
		        if ($action != 'classify')
		        {
		            $morehtmlref.='<a class="editfielda" href="' . $_SERVER['PHP_SELF'] . '?action=classify&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
		            if ($action == 'classify') {
		                //$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
		                $morehtmlref.='<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
		                $morehtmlref.='<input type="hidden" name="action" value="classin">';
		                $morehtmlref.='<input type="hidden" name="token" value="'.newToken().'">';
		                $morehtmlref.=$formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
		                $morehtmlref.='<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
		                $morehtmlref.='</form>';
		            } else {
		                $morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
		            }
		        }
		    } else {
		        if (! empty($object->fk_project)) {
		            $proj = new Project($db);
		            $proj->fetch($object->fk_project);
		            $morehtmlref.='<a href="'.DOL_URL_ROOT.'/projet/card.php?id=' . $object->fk_project . '" title="' . $langs->trans('ShowProject') . '">';
		            $morehtmlref.=$proj->ref;
		            $morehtmlref.='</a>';
		        } else {
		            $morehtmlref.='';
		        }
		    }
		}
	*/
	$morehtmlref .= '</div>';

	$morehtml = $langs->trans("NbOfEmailsInInbox").' : ';

	$sourcedir = $object->source_directory;
	$targetdir = ($object->target_directory ? $object->target_directory : ''); // Can be '[Gmail]/Trash' or 'mytag'

	$connection = null;
	$connectstringserver = '';
	$connectstringsource = '';
	$connectstringtarget = '';

	if (function_exists('imap_open'))
	{
		$connectstringserver = $object->getConnectStringIMAP();

		try {
			if ($sourcedir) {
				//$connectstringsource = $connectstringserver.imap_utf7_encode($sourcedir);
				$connectstringsource = $connectstringserver.$object->getEncodedUtf7($sourcedir);
			}
			if ($targetdir) {
				//$connectstringtarget = $connectstringserver.imap_utf7_encode($targetdir);
				$connectstringtarget = $connectstringserver.$object->getEncodedUtf7($targetdir);
			}

			$connection = imap_open($connectstringsource, $object->login, $object->password);
		}
		catch (Exception $e)
		{
			print $e->getMessage();
		}

		$morehtml .= $form->textwithpicto('', 'connect string '.$connectstringserver);
	}
	else
	{
		$morehtml .= 'IMAP functions not available on your PHP';
	}

	if (!$connection)
	{
		$morehtml .= 'Failed to open IMAP connection '.$connectstringsource;
		$morehtml .= '<br>'.imap_last_error();
		//var_dump(imap_errors())
	}
	else
	{
		$morehtml .= imap_num_msg($connection);
	}

	if ($connection)
	{
		imap_close($connection);
	}

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref.'<div class="refidno">'.$morehtml.'</div>', '', 0, '', '', 0, '');

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">'."\n";

	// Common attributes
	//$keyforbreak='fieldkeytoswithonsecondcolumn';
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';


	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="updatefiltersactions">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';

	// Filters
	print '<table class="border centpercent tableforfield">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Filters").'</td><td></td><td></td>';
	print '</tr>';
	// Add filter
	print '<tr class="oddeven">';
	print '<td>';
	$arrayoftypes = array(
	    'from'=>array('label'=>'MailFrom', 'data-placeholder'=>$langs->trans('SearchString')),
	    'to'=>array('label'=>'MailTo', 'data-placeholder'=>$langs->trans('SearchString')),
	    'cc'=>array('label'=>'Cc', 'data-placeholder'=>$langs->trans('SearchString')),
	    'bcc'=>array('label'=>'Bcc', 'data-placeholder'=>$langs->trans('SearchString')),
	    'subject'=>array('label'=>'Subject', 'data-placeholder'=>$langs->trans('SearchString')),
	    'body'=>array('label'=>'Body', 'data-placeholder'=>$langs->trans('SearchString')),
	    // disabled because PHP imap_search is not compatible IMAPv4, only IMAPv2
	    //'header'=>array('label'=>'Header', 'data-placeholder'=>'HeaderKey SearchString'),                // HEADER key value
	    //'X1'=>'---',
	    //'notinsubject'=>array('label'=>'SubjectNotIn', 'data-placeholder'=>'SearchString'),
	    //'notinbody'=>array('label'=>'BodyNotIn', 'data-placeholder'=>'SearchString'),
	    'X2'=>'---',
	    'seen'=>array('label'=>'AlreadyRead', 'data-noparam'=>1),
	    'unseen'=>array('label'=>'NotRead', 'data-noparam'=>1),
	    'unanswered'=>array('label'=>'Unanswered', 'data-noparam'=>1),
	    'answered'=>array('label'=>'Answered', 'data-noparam'=>1),
	    'smaller'=>array('label'=>'SmallerThan', 'data-placeholder'=>$langs->trans('NumberOfBytes')),
	    'larger'=>array('label'=>'LargerThan', 'data-placeholder'=>$langs->trans('NumberOfBytes')),
	    'X3'=>'---',
	    'withtrackingid'=>array('label'=>'WithDolTrackingID', 'data-noparam'=>1),
	    'withouttrackingid'=>array('label'=>'WithoutDolTrackingID', 'data-noparam'=>1)
	);
	print $form->selectarray('filtertype', $arrayoftypes, '', 1, 0, 0, '', 1, 0, 0, '', '', 0, '', 2);

	print "\n";
	print '<script>';
	print 'jQuery("#filtertype").change(function() {
        console.log("We change a filter");
        if (jQuery("#filtertype option:selected").attr("data-noparam")) {
            jQuery("#rulevalue").attr("placeholder", "");
            jQuery("#rulevalue").text(""); jQuery("#rulevalue").prop("disabled", true);
        }
        else { jQuery("#rulevalue").prop("disabled", false); }
        jQuery("#rulevalue").attr("placeholder", (jQuery("#filtertype option:selected").attr("data-placeholder")));
    ';
	/*$noparam = array();
	foreach ($arrayoftypes as $key => $value)
	{
	    if ($value['noparam']) $noparam[] = $key;
	}*/
	print '})';
	print '</script>'."\n";

	print '</td><td>';
	print '<input type="text" name="rulevalue" id="rulevalue">';
	print '</td>';
	print '<td class="right"><input type="submit" name="addfilter" id="addfilter" class="flat button" value="'.$langs->trans("Add").'"></td>';
	print '</tr>';
	// List filters
	foreach ($object->filters as $rulefilter)
	{
		$rulefilterobj = new EmailCollectorFilter($db);
		$rulefilterobj->fetch($rulefilter['id']);

		print '<tr class="oddeven">';
		print '<td>';
		print $langs->trans($arrayoftypes[$rulefilter['type']]['label']);
		print '</td>';
		print '<td>'.$rulefilter['rulevalue'].'</td>';
		print '<td class="right">';
		print ' <a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=deletefilter&filterid='.$rulefilter['id'].'">'.img_delete().'</a>';
		print '</td>';
		print '</tr>';
	}

	print '</tr>';
	print '</table>';

	print '<div class="clearboth"></div><br>';

	// Operations
	print '<table id="tablelines" class="noborder noshadow tableforfield">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("EmailcollectorOperations").'</td><td></td><td></td><td></td>';
	print '</tr>';
	// Add operation
	print '<tr class="oddeven">';
	print '<td>';
	$arrayoftypes = array(
	    'loadthirdparty'=>$langs->trans('LoadThirdPartyFromName', $langs->transnoentities("ThirdPartyName")),
	    'loadandcreatethirdparty'=>$langs->trans('LoadThirdPartyFromNameOrCreate', $langs->transnoentities("ThirdPartyName")),
	    'recordevent'=>'RecordEvent');
	if ($conf->projet->enabled) $arrayoftypes['project'] = 'CreateLeadAndThirdParty';
	if ($conf->ticket->enabled) $arrayoftypes['ticket'] = 'CreateTicketAndThirdParty';

	// support hook for add action
	$parameters = array('arrayoftypes' => $arrayoftypes);
	$res = $hookmanager->executeHooks('addMoreActionsEmailCollector', $parameters, $object, $action);

	if ($res)
		$arrayoftypes = $hookmanager->resArray;
	else
		foreach ($hookmanager->resArray as $k=>$desc)
			$arrayoftypes[$k] = $desc;


	print $form->selectarray('operationtype', $arrayoftypes, '', 1, 0, 0, '', 1, 0, 0, '', 'maxwidth300');
	print '</td><td>';
	print '<input type="text" name="operationparam">';
	$htmltext = $langs->transnoentitiesnoconv("OperationParamDesc");
	//var_dump($htmltext);
	print $form->textwithpicto('', $htmltext, 1, 'help', '', 0, 2, 'operationparamtt');
	print '</td>';
	print '<td></td>';
	print '<td class="right"><input type="submit" name="addoperation" id="addoperation" class="flat button" value="'.$langs->trans("Add").'"></td>';
	print '</tr>';
	// List operations
	$nboflines = count($object->actions);
	$table_element_line = 'emailcollector_emailcollectoraction';
	$fk_element = 'position';
	$i = 0;
	foreach ($object->actions as $ruleaction)
	{
		$ruleactionobj = new EmailcollectorAction($db);
		$ruleactionobj->fetch($ruleaction['id']);

		print '<tr class="drag drop oddeven" id="row-'.$ruleaction['id'].'">';
		print '<td>';
		print '<!-- type of action: '.$ruleaction['type'].' -->';
		print $langs->trans($arrayoftypes[$ruleaction['type']]);
		if (in_array($ruleaction['type'], array('recordevent')))
		{
            print $form->textwithpicto('', $langs->transnoentitiesnoconv('IfTrackingIDFoundEventWillBeLinked'));
		}
		elseif (in_array($ruleaction['type'], array('loadthirdparty', 'loadandcreatethirdparty'))) {
			print $form->textwithpicto('', $langs->transnoentitiesnoconv('EmailCollectorLoadThirdPartyHelp'));
		}
		print '</td>';
		print '<td class="wordbreak">';
		if ($action == 'editoperation' && $ruleaction['id'] == $operationid)
		{
		    print '<input type="text" class="quatrevingtquinzepercent" name="operationparam2" value="'.$ruleaction['actionparam'].'"><br>';
		    print '<input type="hidden" name="rowidoperation2" value="'.$ruleaction['id'].'"><br>';
		    print '<input type="submit" class="button" name="saveoperation2" value="'.$langs->trans("Save").'"> <input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
		}
		else
		{
		    print $ruleaction['actionparam'];
		}
		print '</td>';
		// Move up/down
		print '<td class="center linecolmove tdlineupdown">';
		if ($i > 0)
		{
			print '<a class="lineupdown" href="'.$_SERVER['PHP_SELF'].'?action=up&amp;rowid='.$ruleaction['id'].'">'.img_up('default', 0, 'imgupforline').'</a>';
		}
		if ($i < count($object->actions) - 1) {
			print '<a class="lineupdown" href="'.$_SERVER['PHP_SELF'].'?action=down&amp;rowid='.$ruleaction['id'].'">'.img_down('default', 0, 'imgdownforline').'</a>';
		}
		print '</td>';
		// Delete
		print '<td class="right nowraponall">';
		print '<a class="editfielda marginrightonly" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=editoperation&operationid='.$ruleaction['id'].'">'.img_edit().'</a>';
		print ' <a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=deleteoperation&operationid='.$ruleaction['id'].'">'.img_delete().'</a>';
		print '</td>';
		print '</tr>';
		$i++;
	}

	print '</tr>';
	print '</table>';

	if (!empty($conf->use_javascript_ajax)) {
	    $urltorefreshaftermove = DOL_URL_ROOT.'/admin/emailcollector_card.php?id='.$id;
		include DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php';
	}

	print '</form>';

	print '</div>';
	print '</div>'; // End <div class="fichecenter">


	print '<div class="clearboth"></div><br>';

	dol_fiche_end();

	// Buttons for actions
	if ($action != 'presend' && $action != 'editline') {
		print '<div class="tabsAction">'."\n";
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

		if (empty($reshook))
		{
			print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=edit">'.$langs->trans("Edit").'</a></div>';

			// Clone
		    print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;socid='.$object->socid.'&amp;action=clone&amp;object=order">'.$langs->trans("ToClone").'</a></div>';

			print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=collect">'.$langs->trans("CollectNow").'</a></div>';

			print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=delete">'.$langs->trans('Delete').'</a></div>';
		}
		print '</div>'."\n";
	}

	if (!empty($debuginfo))
	{
	    print info_admin($debuginfo);
	}


	// Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	/*
	if ($action != 'presend') {
		print '<div class="fichecenter"><div class="fichehalfleft">';
		print '<a name="builddoc"></a>'; // ancre
	*/
		// Documents
		/*$objref = dol_sanitizeFileName($object->ref);
	    $relativepath = $comref . '/' . $comref . '.pdf';
	    $filedir = $conf->emailcollector->dir_output . '/' . $objref;
	    $urlsource = $_SERVER["PHP_SELF"] . "?id=" . $object->id;
	    $genallowed = $user->rights->emailcollector->read;	// If you can read, you can build the PDF to read content
	    $delallowed = $user->rights->emailcollector->create;	// If you can create/edit, you can remove a file on card
	    print $formfile->showdocuments('emailcollector', $objref, $filedir, $urlsource, $genallowed, $delallowed, $object->modelpdf, 1, 0, 0, 28, 0, '', '', '', $soc->default_lang);
		*/
	/*
		// Show links to link elements
		$linktoelem = $form->showLinkToObjectBlock($object, null, array('emailcollector'));
		$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);

		print '</div><div class="fichehalfright"><div class="ficheaddleft">';

		$MAXEVENT = 10;

		$morehtmlright = '<a href="' . dol_buildpath('/emailcollector/emailcollector_info.php', 1) . '?id=' . $object->id . '">';
		$morehtmlright .= $langs->trans("SeeAll");
		$morehtmlright .= '</a>';

		// List of actions on element
		include_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
		$formactions = new FormActions($db);
		$somethingshown = $formactions->showactions($object, 'emailcollector_emailcollector', $socid, 1, '', $MAXEVENT, '', $morehtmlright);

		print '</div></div></div>';
	}
	*/

	//Select mail models is same action as presend
	/*
	 if (GETPOST('modelselected')) $action = 'presend';

	 // Presend form
	 $modelmail='inventory';
	 $defaulttopic='InformationMessage';
	 $diroutput = $conf->product->dir_output.'/inventory';
	 $trackid = 'stockinv'.$object->id;

	 include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
	 */
}

// End of page
llxFooter();
$db->close();
