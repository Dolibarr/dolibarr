<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 *   	\file       htdocs/admin/emailcollectore/emailcollector_card.php
 *		\ingroup    emailcollector
 *		\brief      Page to create/edit/view emailcollector
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/events.class.php';

include_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
include_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
dol_include_once('/emailcollector/class/emailcollector.class.php');
dol_include_once('/emailcollector/lib/emailcollector.lib.php');

if (!$user->admin)
	accessforbidden();

// Load traductions files requiredby by page
$langs->loadLangs(array("admin", "other"));

// Get parameters
$id			= GETPOST('id', 'int');
$ref        = GETPOST('ref', 'alpha');
$action		= GETPOST('action', 'alpha');
$confirm    = GETPOST('confirm', 'alpha');
$cancel     = GETPOST('cancel', 'aZ09');
$contextpage= GETPOST('contextpage','aZ')?GETPOST('contextpage','aZ'):'myobjectcard';   // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');

// Initialize technical objects
$object = new EmailCollector($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->emailcollector->dir_output . '/temp/massgeneration/' . $user->id;
$hookmanager->initHooks(array('emailcollectorcard')); // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('emailcollector');
$search_array_options = $extrafields->getOptionalsFromPost($extralabels, '', 'search_');

// Initialize array of search criterias
$search_all = trim(GETPOST("search_all", 'alpha'));
$search = array();
foreach ($object->fields as $key => $val) {
	if (GETPOST('search_'.$key,'alpha')) $search[$key]=GETPOST('search_'.$key,'alpha');
}

if (empty($action) && empty($id) && empty($ref)) $action='view';

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php';  // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals

// Security check - Protection if external user
//if ($user->societe_id > 0) access_forbidden();
//if ($user->societe_id > 0) $socid = $user->societe_id;
//$isdraft = (($object->statut == MyObject::STATUS_DRAFT) ? 1 : 0);
//$result = restrictedArea($user, 'mymodule', $object->id, '', '', 'fk_soc', 'rowid', $isdraft);


/*
 * Actions
 *
 * Put here all code to do according to value of "action" parameter
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	$error = 0;

	$permissiontoadd=1;
	$permissiontodelete=1;
	if (empty($backtopage)) $backtopage = dol_buildpath('/emailcollector/emailcollector_card.php',1).'?id='.($id > 0 ? $id : '__ID__');
	$backurlforlist = dol_buildpath('/emailcollector/emailcollector_list.php', 1);

	// Actions cancel, add, update, delete or clone
	include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';
	
	// Actions when linking object each other
	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';		// Must be include, not include_once
	
	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';
}



/*
 * View
 *
 * Put here all code to build page
 */

$form = new Form($db);
$formfile = new FormFile($db);

llxHeader('', 'EmailCollector', '');

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

	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';

	dol_fiche_head(array(), '');

	print '<table class="border centpercent">'."\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_add.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_add.tpl.php';

	print '</table>'."\n";

	dol_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" name="add" value="' . dol_escape_htmltag($langs->trans("Create")) . '">';
	print '&nbsp; ';
	print '<input type="' . ($backtopage ? "submit" : "button") . '" class="button" name="cancel" value="' . dol_escape_htmltag($langs->trans("Cancel")) . '"' . ($backtopage ? '' : ' onclick="javascript:history.go(-1)"') . '>'; // Cancel for create does not post form if we don't know the backtopage
	print '</div>';

	print '</form>';
}

// Part to edit record
if (($id || $ref) && $action == 'edit')
{
	print load_fiche_titre($langs->trans("EmailCollector"));

	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
	print '<input type="hidden" name="id" value="' . $object->id . '">';

	dol_fiche_head();

	print '<table class="border centpercent">' . "\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_edit.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_edit.tpl.php';

	print '</table>';

	dol_fiche_end();

	print '<div class="center"><input type="submit" class="button" name="save" value="' . $langs->trans("Save") . '">';
	print ' &nbsp; <input type="submit" class="button" name="cancel" value="' . $langs->trans("Cancel") . '">';
	print '</div>';

	print '</form>';
}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create')))
{
	$res = $object->fetch_optionals();

	$head = emailcollectorPrepareHead($object);
	dol_fiche_head($head, 'card', $langs->trans("EmailCollector"), -1, 'emailcollector');

	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete')
	{
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('DeleteEmailCollector'), $langs->trans('ConfirmDeleteEmailCollector'), 'confirm_delete', '', 0, 1);
	}

	// Clone confirmation
	if ($action == 'clone') {
		// Create an array for form
		$formquestion = array();
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('CloneMyObject'), $langs->trans('ConfirmCloneMyObject', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
	}
	
	// Confirmation of action process
	if ($action == 'collect') {
		$formquestion = array(
			'text' => $langs->trans("EmailCollectorConfirmCollect"),
		);
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('EmailCollectorConfirmCollectTitle'), $text, 'confirm_collect', $formquestion, 0, 1, 220);
	}

	// Call Hook formConfirm
	$parameters = array('lineid' => $lineid);
	$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) $formconfirm.=$hookmanager->resPrint;
	elseif ($reshook > 0) $formconfirm=$hookmanager->resPrint;
	
	// Print form confirm
	print $formconfirm;
	
	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="' . dol_buildpath('/admin/emailcollector_list.php', 1) . '?restore_lastsearch_values=1' . (!empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

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
		            $morehtmlref.='<a href="' . $_SERVER['PHP_SELF'] . '?action=classify&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
		            if ($action == 'classify') {
		                //$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
		                $morehtmlref.='<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
		                $morehtmlref.='<input type="hidden" name="action" value="classin">';
		                $morehtmlref.='<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
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

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent">'."\n";

	// Common attributes
	//$keyforbreak='fieldkeytoswithonsecondcolumn';
	include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_view.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

	print '</table>';


	if ($action == 'confirm_collect')
	{
		print_fiche_titre($langs->trans('MessagesFetchingResults'), '', '');

		dol_include_once('/emailcollector/class/emailcollector.class.php');
		$emailcollector = new EmailCollector($object);

		$res = $emailcollector->collectEmails();
		if (is_array($res)) {
			if (count($res['actions_done']) > 0) {
				setEventMessages($langs->trans('XActionsDone', count($res['actions_done'])), null, 'info');
			} else {
				setEventMessages($langs->trans('NoActionsdone'), null, 'info');
			}
		} else {
			setEventMessages($langs->trans('NoEmailsToProcess'), null, 'info');
		}
		$action = '';
	}
	print '</div>';
	print '</div>';
	
	
	print '<div class="clearboth"></div><br>';

	dol_fiche_end();

	// Buttons for actions
	if ($action != 'presend' && $action != 'editline') {
		print '<div class="tabsAction">' . "\n";
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		
		if (empty($reshook))
		{
			print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=collect">' . $langs->trans("CollectNow") . '</a>' . "\n";

			print '<a class="butActionDelete" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=delete">' . $langs->trans('Delete') . '</a>' . "\n";
		}
		print '</div>' . "\n";
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
