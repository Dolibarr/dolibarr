<?php
/* Copyright (C) 2004       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2018  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2004       Benoit Mortier          <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2010-2016  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2011-2018  Philippe Grand          <philippe.grand@atoo-net.com>
 * Copyright (C) 2011       Remy Younes             <ryounes@gmail.com>
 * Copyright (C) 2012-2015  Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2012       Christophe Battarel     <christophe.battarel@ltairis.fr>
 * Copyright (C) 2011-2016  Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2015       Ferran Marcet           <fmarcet@2byte.es>
 * Copyright (C) 2016       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2018-2021  Frédéric France         <frederic.france@netlogic.fr>
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
 *	    \file       htdocs/admin/mails_templates.php
 *		\ingroup    core
 *		\brief      Page to administer emails templates
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';

// Load translation files required by the page
$langsArray=array("errors", "admin", "mails", "languages");

if (!empty($conf->adherent->enabled)) {
	$langsArray[]='members';
}
if (!empty($conf->eventorganization->enabled)) {
	$langsArray[]='eventorganization';
}

$langs->loadLangs($langsArray);

$toselect = GETPOST('toselect', 'array');
$action = GETPOST('action', 'aZ09') ?GETPOST('action', 'aZ09') : 'view';
$massaction = GETPOST('massaction', 'alpha');
$confirm = GETPOST('confirm', 'alpha'); // Result of a confirmation
$mode = GETPOST('mode', 'aZ09');
$optioncss = GETPOST('optioncss', 'alpha');

$id = GETPOST('id', 'int');
$rowid = GETPOST('rowid', 'alpha');
$search_label = GETPOST('search_label', 'alphanohtml'); // Must allow value like 'Abc Def' or '(MyTemplateName)'
$search_type_template = GETPOST('search_type_template', 'alpha');
$search_lang = GETPOST('search_lang', 'alpha');
$search_fk_user = GETPOST('search_fk_user', 'intcomma');
$search_topic = GETPOST('search_topic', 'alpha');

$acts = array();
$actl = array();
$acts[0] = "activate";
$acts[1] = "disable";
$actl[0] = img_picto($langs->trans("Disabled"), 'switch_off', 'class="size15x"');
$actl[1] = img_picto($langs->trans("Activated"), 'switch_on', 'class="size15x"');

$listoffset = GETPOST('listoffset', 'alpha');
$listlimit = GETPOST('listlimit', 'alpha') > 0 ?GETPOST('listlimit', 'alpha') : 1000;

$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $listlimit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (empty($sortfield)) {
	$sortfield = 'type_template, lang, position, label';
}
if (empty($sortorder)) {
	$sortorder = 'ASC';
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('emailtemplates'));


// Name of SQL tables of dictionaries
$tabname = array();
$tabname[25] = MAIN_DB_PREFIX."c_email_templates";

// Nom des champs en resultat de select pour affichage du dictionnaire
$tabfield = array();
$tabfield[25] = "label,lang,type_template,fk_user,private,position,topic,joinfiles,content";
if (!empty($conf->global->MAIN_EMAIL_TEMPLATES_FOR_OBJECT_LINES)) {
	$tabfield[25] .= ',content_lines';
}

// Nom des champs d'edition pour modification d'un enregistrement
$tabfieldvalue = array();
$tabfieldvalue[25] = "label,lang,type_template,fk_user,private,position,topic,joinfiles,content";
if (!empty($conf->global->MAIN_EMAIL_TEMPLATES_FOR_OBJECT_LINES)) {
	$tabfieldvalue[25] .= ',content_lines';
}

// Nom des champs dans la table pour insertion d'un enregistrement
$tabfieldinsert = array();
$tabfieldinsert[25] = "label,lang,type_template,fk_user,private,position,topic,joinfiles,content";
if (!empty($conf->global->MAIN_EMAIL_TEMPLATES_FOR_OBJECT_LINES)) {
	$tabfieldinsert[25] .= ',content_lines';
}
$tabfieldinsert[25] .= ',entity'; // Must be at end because not into other arrays

// Condition to show dictionary in setup page
$tabcond = array();
$tabcond[25] = true;

// List of help for fields
// Set MAIN_EMAIL_TEMPLATES_FOR_OBJECT_LINES to allow edit of template for lines
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
$formmail = new FormMail($db);
if (empty($conf->global->MAIN_EMAIL_TEMPLATES_FOR_OBJECT_LINES)) {
	$tmp = FormMail::getAvailableSubstitKey('formemail');
	$tmp['__(AnyTranslationKey)__'] = 'Translation';
	$helpsubstit = $langs->trans("AvailableVariables").':<br>';
	$helpsubstitforlines = $langs->trans("AvailableVariables").':<br>';
	foreach ($tmp as $key => $val) {
		$helpsubstit .= $key.' -> '.$val.'<br>';
		$helpsubstitforlines .= $key.' -> '.$val.'<br>';
	}
} else {
	$tmp = FormMail::getAvailableSubstitKey('formemailwithlines');
	$tmp['__(AnyTranslationKey)__'] = 'Translation';
	$helpsubstit = $langs->trans("AvailableVariables").':<br>';
	$helpsubstitforlines = $langs->trans("AvailableVariables").':<br>';
	foreach ($tmp as $key => $val) {
		$helpsubstit .= $key.' -> '.$val.'<br>';
	}
	$tmp = FormMail::getAvailableSubstitKey('formemailforlines');
	foreach ($tmp as $key => $val) {
		$helpsubstitforlines .= $key.' -> '.$val.'<br>';
	}
}


$tabhelp = array();
$tabhelp[25] = array(
	'label'=>$langs->trans('EnterAnyCode'),
	'topic'=>'<span class="small">'.$helpsubstit.'</span>',
	'joinfiles'=>$langs->trans('AttachMainDocByDefault'),
	'content'=>'<span class="small">'.$helpsubstit.'</span>',
	'content_lines'=>'<span class="small">'.$helpsubstitforlines.'</span>',
	'type_template'=>$langs->trans("TemplateForElement"),
	'private'=>$langs->trans("TemplateIsVisibleByOwnerOnly"),
	'position'=>$langs->trans("PositionIntoComboList")
);


// We save list of template email Dolibarr can manage. This list can found by a grep into code on "->param['models']"
$elementList = array();
// Add all and none after the sort

$elementList['all'] = '-- '.dol_escape_htmltag($langs->trans("All")).' --';
$elementList['none'] = '-- '.dol_escape_htmltag($langs->trans("None")).' --';
$elementList['user'] = img_picto('', 'user', 'class="pictofixedwidth"').dol_escape_htmltag($langs->trans('MailToUser'));
if (!empty($conf->adherent->enabled) && !empty($user->rights->adherent->lire)) {
	$elementList['member'] = img_picto('', 'object_member', 'class="pictofixedwidth"').dol_escape_htmltag($langs->trans('MailToMember'));
}
if (!empty($conf->recruitment->enabled) && !empty($user->rights->recruitment->recruitmentjobposition->read)) {
	$elementList['recruitmentcandidature_send'] = img_picto('', 'recruitmentcandidature', 'class="pictofixedwidth"').dol_escape_htmltag($langs->trans('RecruitmentCandidatures'));
}
if (!empty($conf->societe->enabled) && !empty($user->rights->societe->lire)) {
	$elementList['thirdparty'] = img_picto('', 'company', 'class="pictofixedwidth"').dol_escape_htmltag($langs->trans('MailToThirdparty'));
}
if (!empty($conf->project->enabled)) {
	$elementList['project'] = img_picto('', 'project', 'class="pictofixedwidth"').dol_escape_htmltag($langs->trans('MailToProject'));
}
if (!empty($conf->propal->enabled) && !empty($user->rights->propal->lire)) {
	$elementList['propal_send'] = img_picto('', 'propal', 'class="pictofixedwidth"').dol_escape_htmltag($langs->trans('MailToSendProposal'));
}
if (!empty($conf->commande->enabled) && !empty($user->rights->commande->lire)) {
	$elementList['order_send'] = img_picto('', 'order', 'class="pictofixedwidth"').dol_escape_htmltag($langs->trans('MailToSendOrder'));
}
if (isModEnabled('facture') && !empty($user->rights->facture->lire)) {
	$elementList['facture_send'] = img_picto('', 'bill', 'class="pictofixedwidth"').dol_escape_htmltag($langs->trans('MailToSendInvoice'));
}
if (!empty($conf->expedition->enabled)) {
	$elementList['shipping_send'] = img_picto('', 'dolly', 'class="pictofixedwidth"').dol_escape_htmltag($langs->trans('MailToSendShipment'));
}
if (!empty($conf->reception->enabled)) {
	$elementList['reception_send'] = img_picto('', 'dollyrevert', 'class="pictofixedwidth"').dol_escape_htmltag($langs->trans('MailToSendReception'));
}
if (!empty($conf->ficheinter->enabled)) {
	$elementList['fichinter_send'] = img_picto('', 'intervention', 'class="pictofixedwidth"').dol_escape_htmltag($langs->trans('MailToSendIntervention'));
}
if (!empty($conf->supplier_proposal->enabled)) {
	$elementList['supplier_proposal_send'] = img_picto('', 'propal', 'class="pictofixedwidth"').dol_escape_htmltag($langs->trans('MailToSendSupplierRequestForQuotation'));
}
if ((!empty($conf->fournisseur->enabled) && !empty($user->rights->fournisseur->commande->lire) && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD)) || (!empty($conf->supplier_order->enabled) && !empty($user->rights->supplier_order->lire))) {
	$elementList['order_supplier_send'] = img_picto('', 'order', 'class="pictofixedwidth"').dol_escape_htmltag($langs->trans('MailToSendSupplierOrder'));
}
if ((!empty($conf->fournisseur->enabled) && !empty($user->rights->fournisseur->facture->lire) && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD)) || (!empty($conf->supplier_invoice->enabled) && !empty($user->rights->supplier_invoice->lire))) {
	$elementList['invoice_supplier_send'] = img_picto('', 'bill', 'class="pictofixedwidth"').dol_escape_htmltag($langs->trans('MailToSendSupplierInvoice'));
}
if (!empty($conf->contrat->enabled) && !empty($user->rights->contrat->lire)) {
	$elementList['contract'] = img_picto('', 'contract', 'class="pictofixedwidth"').dol_escape_htmltag($langs->trans('MailToSendContract'));
}
if (!empty($conf->ticket->enabled) && !empty($user->rights->ticket->read)) {
	$elementList['ticket_send'] = img_picto('', 'ticket', 'class="pictofixedwidth"').dol_escape_htmltag($langs->trans('MailToTicket'));
}
if (!empty($conf->expensereport->enabled) && !empty($user->rights->expensereport->lire)) {
	$elementList['expensereport_send'] = img_picto('', 'trip', 'class="pictofixedwidth"').dol_escape_htmltag($langs->trans('MailToExpenseReport'));
}
if (isModEnabled('agenda')) {
	$elementList['actioncomm_send'] = img_picto('', 'action', 'class="pictofixedwidth"').dol_escape_htmltag($langs->trans('MailToSendEventPush'));
}
if (!empty($conf->eventorganization->enabled) && !empty($user->rights->eventorganization->read)) {
	$elementList['conferenceorbooth'] = img_picto('', 'action', 'class="pictofixedwidth"').dol_escape_htmltag($langs->trans('MailToSendEventOrganization'));
}
if (!empty($conf->partnership->enabled) && !empty($user->rights->partnership->read)) {
	$elementList['partnership_send'] = img_picto('', 'partnership', 'class="pictofixedwidth"').dol_escape_htmltag($langs->trans('MailToPartnership'));
}

$parameters = array('elementList'=>$elementList);
$reshook = $hookmanager->executeHooks('emailElementlist', $parameters); // Note that $action and $object may have been modified by some hooks
if ($reshook == 0) {
	foreach ($hookmanager->resArray as $item => $value) {
		$elementList[$item] = $value;
	}
}



if (!empty($user->socid)) {
	accessforbidden();
}

$permissiontoadd = 1;

//asort($elementList);

$id = 25;



/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) {
	$action = 'list';
	$massaction = '';
}
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') {
	$massaction = '';
}

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
		// All tests are required to be compatible with all browsers
		$search_label = '';
		$search_type_template = '';
		$search_lang = '';
		$search_fk_user = '';
		$search_topic = '';
		$toselect = array();
		$search_array_options = array();
	}

	// Actions add or modify an entry into a dictionary
	if (GETPOST('actionadd', 'alpha') || GETPOST('actionmodify', 'alpha')) {
		$listfield = explode(',', str_replace(' ', '', $tabfield[$id]));
		$listfieldinsert = explode(',', $tabfieldinsert[$id]);
		$listfieldmodify = explode(',', $tabfieldinsert[$id]);
		$listfieldvalue = explode(',', $tabfieldvalue[$id]);

		// Check that all fields are filled
		$ok = 1;
		foreach ($listfield as $f => $value) {
			// Not mandatory fields
			if ($value == 'joinfiles') {
				continue;
			}
			if ($value == 'content') {
				continue;
			}
			if ($value == 'content_lines') {
				continue;
			}

			// Rename some POST variables into a generic name
			if (GETPOST('actionmodify', 'alpha') && $value == 'topic') {
				$_POST['topic'] = GETPOST('topic-'.$rowid);
			}

			if ((!GETPOSTISSET($value) || GETPOST($value) == '' || GETPOST($value) == '-1') && $value != 'lang' && $value != 'fk_user' && $value != 'position') {
				$ok = 0;
				$fieldnamekey = $listfield[$f];
				// We take translate key of field
				if ($fieldnamekey == 'libelle' || ($fieldnamekey == 'label')) {
					$fieldnamekey = 'Code';
				}
				if ($fieldnamekey == 'code') {
					$fieldnamekey = 'Code';
				}
				if ($fieldnamekey == 'note') {
					$fieldnamekey = 'Note';
				}
				if ($fieldnamekey == 'type_template') {
					$fieldnamekey = 'TypeOfTemplate';
				}
				if ($fieldnamekey == 'fk_user') {
					$fieldnamekey = 'Owner';
				}
				if ($fieldnamekey == 'private') {
					$fieldnamekey = 'Private';
				}
				if ($fieldnamekey == 'position') {
					$fieldnamekey = 'Position';
				}
				if ($fieldnamekey == 'topic') {
					$fieldnamekey = 'Topic';
				}

				setEventMessages($langs->transnoentities("ErrorFieldRequired", $langs->transnoentities($fieldnamekey)), null, 'errors');
				$action = 'add';
			}
		}

		// If previous test is ok action is add, we add the line
		if ($ok && GETPOST('actionadd')) {
			// Add new entry
			$sql = "INSERT INTO ".$tabname[$id]." (";
			// List of fields
			$sql .= $tabfieldinsert[$id];
			$sql .= ", active, enabled)";
			$sql .= " VALUES(";

			// List of values
			$i = 0;
			foreach ($listfieldinsert as $f => $value) {
				$keycode = $listfieldvalue[$i];
				if ($value == 'lang') {
					$keycode = 'langcode';
				}
				if (empty($keycode)) {
					$keycode = $value;
				}

				// Clean input variables
				if ($value == 'entity') {
					$_POST[$keycode] = $conf->entity;
				}
				if ($value == 'fk_user' && !($_POST[$keycode] > 0)) {
					$_POST[$keycode] = '';
				}
				if ($value == 'private' && !is_numeric($_POST[$keycode])) {
					$_POST[$keycode] = '0';
				}
				if ($value == 'position' && !is_numeric($_POST[$keycode])) {
					$_POST[$keycode] = '1';
				}
				//var_dump($keycode.' '.$value);

				if ($i) {
					$sql .= ", ";
				}
				if (GETPOST($keycode) == '' && $keycode != 'langcode') {
					$sql .= "null"; // langcode must be '' if not defined so the unique key that include lang will work
				} elseif (GETPOST($keycode) == '0' && $keycode == 'langcode') {
					$sql .= "''"; // langcode must be '' if not defined so the unique key that include lang will work
				} elseif ($keycode == 'fk_user') {
					if (!$user->admin) {	// A non admin user can only edit its own template
						$sql .= " ".((int) $user->id);
					} else {
						$sql .= " ".((int) GETPOST($keycode, 'int'));
					}
				} elseif ($keycode == 'content') {
					$sql .= "'".$db->escape(GETPOST($keycode, 'restricthtml'))."'";
				} elseif (in_array($keycode, array('joinfiles', 'private', 'position', 'entity'))) {
					$sql .= (int) GETPOST($keycode, 'int');
				} else {
					$sql .= "'".$db->escape(GETPOST($keycode, 'alphanohtml'))."'";
				}
				$i++;
			}
			$sql .= ", 1, 1)";

			dol_syslog("actionadd", LOG_DEBUG);
			$result = $db->query($sql);
			if ($result) {	// Add is ok
				setEventMessages($langs->transnoentities("RecordSaved"), null, 'mesgs');
				$_POST = array('id'=>$id); // Clean $_POST array, we keep only id
			} else {
				if ($db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
					setEventMessages($langs->transnoentities("ErrorRecordAlreadyExists"), null, 'errors');
				} else {
					dol_print_error($db);
				}
				$action = 'add';
			}
		}

		// We modify the line
		if ($ok && GETPOST('actionmodify')) {
			$rowidcol = "rowid";

			// Modify entry
			$sql = "UPDATE ".$tabname[$id]." SET ";
			// Modifie valeur des champs
			$i = 0;
			foreach ($listfieldmodify as $field) {
				if ($field == 'entity') {
					// entity not present on listfieldmodify array
					$keycode = $field;
					$_POST[$keycode] = $conf->entity;
				} else {
					$keycode = $listfieldvalue[$i];
				}

				if ($field == 'lang') {
					$keycode = 'langcode';
				}
				if (empty($keycode)) {
					$keycode = $field;
				}

				// Rename some POST variables into a generic name
				if ($field == 'fk_user' && !($_POST['fk_user'] > 0)) {
					$_POST['fk_user'] = '';
				}
				if ($field == 'topic') {
					$_POST['topic'] = $_POST['topic-'.$rowid];
				}
				if ($field == 'joinfiles') {
					$_POST['joinfiles'] = $_POST['joinfiles-'.$rowid];
				}
				if ($field == 'content') {
					$_POST['content'] = $_POST['content-'.$rowid];
				}
				if ($field == 'content_lines') {
					$_POST['content_lines'] = $_POST['content_lines-'.$rowid];
				}

				if ($i) {
					$sql .= ", ";
				}
				$sql .= $field."=";

				if (GETPOST($keycode) == '' || ($keycode != 'langcode' && $keycode != 'position' && $keycode != 'private' && !GETPOST($keycode))) {
					$sql .= "null"; // langcode,... must be '' if not defined so the unique key that include lang will work
				} elseif (GETPOST($keycode) == '0' && $keycode == 'langcode') {
					$sql .= "''"; // langcode must be '' if not defined so the unique key that include lang will work
				} elseif ($keycode == 'fk_user') {
					if (!$user->admin) {	// A non admin user can only edit its own template
						$sql .= " ".((int) $user->id);
					} else {
						$sql .= " ".((int) GETPOST($keycode, 'int'));
					}
				} elseif ($keycode == 'content') {
					$sql .= "'".$db->escape(GETPOST($keycode, 'restricthtml'))."'";
				} elseif (in_array($keycode, array('joinfiles', 'private', 'position'))) {
					$sql .= (int) GETPOST($keycode, 'int');
				} else {
					$sql .= "'".$db->escape(GETPOST($keycode, 'alphanohtml'))."'";
				}

				$i++;
			}

			$sql .= " WHERE ".$rowidcol." = ".((int) $rowid);
			if (!$user->admin) {	// A non admin user can only edit its own template
				$sql .= " AND fk_user  = ".((int) $user->id);
			}
			//print $sql;exit;
			dol_syslog("actionmodify", LOG_DEBUG);
			//print $sql;
			$resql = $db->query($sql);
			if ($resql) {
				setEventMessages($langs->transnoentities("RecordSaved"), null, 'mesgs');
			} else {
				setEventMessages($db->error(), null, 'errors');
				$action = 'edit';
			}
		}
	}

	if ($action == 'confirm_delete' && $confirm == 'yes') {       // delete
		$rowidcol = "rowid";

		$sql = "DELETE from ".$tabname[$id]." WHERE ".$rowidcol." = ".((int) $rowid);
		if (!$user->admin) {	// A non admin user can only edit its own template
			$sql .= " AND fk_user = ".((int) $user->id);
		}
		dol_syslog("delete", LOG_DEBUG);
		$result = $db->query($sql);
		if (!$result) {
			if ($db->errno() == 'DB_ERROR_CHILD_EXISTS') {
				setEventMessages($langs->transnoentities("ErrorRecordIsUsedByChild"), null, 'errors');
			} else {
				dol_print_error($db);
			}
		}
	}

	// activate
	if ($action == $acts[0]) {
		$rowidcol = "rowid";

		$sql = "UPDATE ".$tabname[$id]." SET active = 1 WHERE rowid = ".((int) $rowid);

		$result = $db->query($sql);
		if (!$result) {
			dol_print_error($db);
		}
	}

	// disable
	if ($action == $acts[1]) {
		$rowidcol = "rowid";

		$sql = "UPDATE ".$tabname[$id]." SET active = 0 WHERE rowid = ".((int) $rowid);

		$result = $db->query($sql);
		if (!$result) {
			dol_print_error($db);
		}
	}
}


/*
 * View
 */

$form = new Form($db);

$now = dol_now();

$formadmin = new FormAdmin($db);

//$help_url = "EN:Module_MyObject|FR:Module_MyObject_FR|ES:Módulo_MyObject";
$help_url = '';
if (!empty($user->admin) && (empty($_SESSION['leftmenu']) || $_SESSION['leftmenu'] != 'email_templates')) {
	$title = $langs->trans("EMailsSetup");
} else {
	$title = $langs->trans("EMailTemplates");
}
$morejs = array();
$morecss = array();

$sql = "SELECT rowid as rowid, module, label, type_template, lang, fk_user, private, position, topic, joinfiles, content_lines, content, enabled, active";
$sql .= " FROM ".MAIN_DB_PREFIX."c_email_templates";
$sql .= " WHERE entity IN (".getEntity('email_template').")";
if (!$user->admin) {
	$sql .= " AND (private = 0 OR (private = 1 AND fk_user = ".((int) $user->id)."))"; // Show only public and private to me
	$sql .= " AND (active = 1 OR fk_user = ".((int) $user->id).")"; // Show only active or owned by me
}
if (empty($conf->global->MAIN_MULTILANGS)) {
	$sql .= " AND (lang = '".$db->escape($langs->defaultlang)."' OR lang IS NULL OR lang = '')";
}
if ($search_label) {
	$sql .= natural_search('label', $search_label);
}
if ($search_type_template != '' && $search_type_template != '-1') {
	$sql .= natural_search('type_template', $search_type_template);
}
if ($search_lang) {
	$sql .= natural_search('lang', $search_lang);
}
if ($search_fk_user != '' && $search_fk_user != '-1') {
	$sql .= natural_search('fk_user', $search_fk_user, 2);
}
if ($search_topic) {
	$sql .= natural_search('topic', $search_topic);
}
// If sort order is "country", we use country_code instead
if ($sortfield == 'country') {
	$sortfield = 'country_code';
}
$sql .= $db->order($sortfield, $sortorder);
$sql .= $db->plimit($listlimit + 1, $offset);
//print $sql;

// Output page
// --------------------------------------------------------------------

llxHeader('', $title, $help_url, '', 0, 0, $morejs, $morecss, '', '');

$arrayofselected = is_array($toselect) ? $toselect : array();

$param = '';
if (!empty($mode)) {
	$param .= '&mode='.urlencode($mode);
}
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
	$param .= '&contextpage='.urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.urlencode($limit);
}
if (!empty($search) && is_array($search)) {
	foreach ($search as $key => $val) {
		if (is_array($search[$key]) && count($search[$key])) {
			foreach ($search[$key] as $skey) {
				if ($skey != '') {
					$param .= '&search_'.$key.'[]='.urlencode($skey);
				}
			}
		} elseif ($search[$key] != '') {
			$param .= '&search_'.$key.'='.urlencode($search[$key]);
		}
	}
}
if ($optioncss != '') {
	$param .= '&optioncss='.urlencode($optioncss);
}
// Add $param from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';
// Add $param from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSearchParam', $parameters, $object); // Note that $action and $object may have been modified by hook
$param .= $hookmanager->resPrint;


$linkback = '';
$titlepicto = 'title_setup';


$url = DOL_URL_ROOT.'/admin/mails_templates.php?action=add&token='.newToken();
$newcardbutton = dolGetButtonTitle($langs->trans('NewEMailTemplate'), '', 'fa fa-plus-circle', $url, '', $permissiontoadd);


if (!empty($user->admin) && (empty($_SESSION['leftmenu']) || $_SESSION['leftmenu'] != 'email_templates')) {
	print load_fiche_titre($title, '', $titlepicto);
} else {
	print load_fiche_titre($title, $newcardbutton, $titlepicto);
}

if (!empty($user->admin) && (empty($_SESSION['leftmenu']) || $_SESSION['leftmenu'] != 'email_templates')) {
	$head = email_admin_prepare_head();

	print dol_get_fiche_head($head, 'templates', '', -1);

	if (!empty($user->admin) && (empty($_SESSION['leftmenu']) || $_SESSION['leftmenu'] != 'email_templates')) {
		print load_fiche_titre('', $newcardbutton, '');
	}
}


// Confirmation de la suppression de la ligne
if ($action == 'delete') {
	print $form->formconfirm($_SERVER["PHP_SELF"].'?'.($page ? 'page='.$page.'&' : '').'sortfield='.$sortfield.'&sortorder='.$sortorder.'&rowid='.$rowid.'&code='.$code.'&id='.$id, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_delete', '', 0, 1);
}




$fieldlist = explode(',', $tabfield[$id]);

if ($action == 'add') {
	// Form to add a new line
	print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$id.'" method="POST">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="from" value="'.dol_escape_htmltag(GETPOST('from', 'alpha')).'">';

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';

	// Line to enter new values (title)
	print '<tr class="liste_titre">';
	foreach ($fieldlist as $field => $value) {
		// Determine le nom du champ par rapport aux noms possibles
		// dans les dictionnaires de donnees
		$valuetoshow = ucfirst($fieldlist[$field]); // Par defaut
		$valuetoshow = $langs->trans($valuetoshow); // try to translate
		$align = "left";
		if ($fieldlist[$field] == 'fk_user') {
			$valuetoshow = $langs->trans("Owner");
		}
		if ($fieldlist[$field] == 'lang') {
			$valuetoshow = (empty($conf->global->MAIN_MULTILANGS) ? '&nbsp;' : $langs->trans("Language"));
		}
		if ($fieldlist[$field] == 'type') {
			$valuetoshow = $langs->trans("Type");
		}
		if ($fieldlist[$field] == 'code') {
			$valuetoshow = $langs->trans("Code");
		}
		if ($fieldlist[$field] == 'libelle' || $fieldlist[$field] == 'label') {
			$valuetoshow = $langs->trans("Code");
		}
		if ($fieldlist[$field] == 'type_template') {
			$valuetoshow = $langs->trans("TypeOfTemplate"); $align = "center";
		}
		if ($fieldlist[$field] == 'private') {
			$align = 'center';
		}
		if ($fieldlist[$field] == 'position') {
			$align = 'center';
		}

		if ($fieldlist[$field] == 'topic') {
			$valuetoshow = '';
		}
		if ($fieldlist[$field] == 'joinfiles') {
			$valuetoshow = '';
		}
		if ($fieldlist[$field] == 'content') {
			$valuetoshow = '';
		}
		if ($fieldlist[$field] == 'content_lines') {
			$valuetoshow = '';
		}

		if ($valuetoshow != '') {
			print '<th class="'.$align.'">';
			if (!empty($tabhelp[$id][$value]) && preg_match('/^http(s*):/i', $tabhelp[$id][$value])) {
				print '<a href="'.$tabhelp[$id][$value].'" target="_blank" rel="noopener noreferrer">'.$valuetoshow.' '.img_help(1, $valuetoshow).'</a>';
			} elseif (!empty($tabhelp[$id][$value])) {
				if (in_array($value, array('topic'))) {
					print $form->textwithpicto($valuetoshow, $tabhelp[$id][$value], 1, 'help', '', 0, 2, $value); // Tooltip on click
				} else {
					print $form->textwithpicto($valuetoshow, $tabhelp[$id][$value], 1, 'help', '', 0, 2); // Tooltip on hover
				}
			} else {
				print $valuetoshow;
			}
			print '</th>';
		}
	}
	print '<th>';
	print '<input type="hidden" name="id" value="'.$id.'">';
	print '</th>';
	print '</tr>';

	$obj = new stdClass();
	// If data was already input, we define them in obj to populate input fields.
	if (GETPOST('actionadd')) {
		foreach ($fieldlist as $key => $val) {
			if (GETPOST($val) != '') {
				$obj->$val = GETPOST($val);
			}
		}
	}

	$tmpaction = 'create';
	$parameters = array(
		'fieldlist' => $fieldlist,
		'tabname' => $tabname[$id]
	);
	$reshook = $hookmanager->executeHooks('createEmailTemplateFieldlist', $parameters, $obj, $tmpaction); // Note that $action and $object may have been modified by some hooks
	$error = $hookmanager->error;
	$errors = $hookmanager->errors;


	// Line to enter new values (input fields)
	print '<tr class="oddeven">';

	if (empty($reshook)) {
		if ($action == 'edit') {
			fieldList($fieldlist, $obj, $tabname[$id], 'hide');
		} else {
			fieldList($fieldlist, $obj, $tabname[$id], 'add');
		}
	}

	print '<td class="right">';
	print '</td>';
	print "</tr>";

	// Show fields for topic, join files and body
	$fieldsforcontent = array('topic', 'joinfiles', 'content');
	if (!empty($conf->global->MAIN_EMAIL_TEMPLATES_FOR_OBJECT_LINES)) {
		$fieldsforcontent = array('topic', 'joinfiles', 'content', 'content_lines');
	}
	foreach ($fieldsforcontent as $tmpfieldlist) {
		print '<tr class="impair nodrag nodrop nohover"><td colspan="7" class="nobottom">';

		// Label
		if ($tmpfieldlist == 'topic') {
			print '<strong>'.$form->textwithpicto($langs->trans("Topic"), $tabhelp[$id][$tmpfieldlist], 1, 'help', '', 0, 2, $tmpfieldlist).'</strong> ';
		}
		if ($tmpfieldlist == 'joinfiles') {
			print '<strong>'.$form->textwithpicto($langs->trans("FilesAttachedToEmail"), $tabhelp[$id][$tmpfieldlist], 1, 'help', '', 0, 2, $tmpfieldlist).'</strong> ';
		}
		if ($tmpfieldlist == 'content') {
			print $form->textwithpicto($langs->trans("Content"), $tabhelp[$id][$tmpfieldlist], 1, 'help', '', 0, 2, $tmpfieldlist).'<br>';
		}
		if ($tmpfieldlist == 'content_lines') {
			print $form->textwithpicto($langs->trans("ContentForLines"), $tabhelp[$id][$tmpfieldlist], 1, 'help', '', 0, 2, $tmpfieldlist).'<br>';
		}

		// Input field
		if ($tmpfieldlist == 'topic') {
			print '<input type="text" class="flat minwidth500" name="'.$tmpfieldlist.'" value="'.(!empty($obj->{$tmpfieldlist}) ? $obj->{$tmpfieldlist} : '').'">';
		} elseif ($tmpfieldlist == 'joinfiles') {
			print '<input type="text" class="flat maxwidth50" name="'.$tmpfieldlist.'" value="'.(isset($obj->{$tmpfieldlist}) ? $obj->{$tmpfieldlist} : '1').'">';
		} else {
			// print '<textarea cols="3" rows="'.ROWS_2.'" class="flat" name="'.$fieldlist[$field].'">'.(! empty($obj->{$fieldlist[$field]})?$obj->{$fieldlist[$field]}:'').'</textarea>';
			$okforextended = true;
			if (empty($conf->global->FCKEDITOR_ENABLE_MAIL)) {
				$okforextended = false;
			}
			$doleditor = new DolEditor($tmpfieldlist, (!empty($obj->{$tmpfieldlist}) ? $obj->{$tmpfieldlist} : ''), '', 180, 'dolibarr_mailings', 'In', 0, true, $okforextended, ROWS_4, '90%');
			print $doleditor->Create(1);
		}
		print '</td>';
		print '</tr>';
	}

	print '</table>';

	if ($action != 'edit') {
		print '<center>';
		print '<input type="submit" class="button button-add" name="actionadd" value="'.$langs->trans("Add").'"> ';
		print '<input type="submit" class="button button-cancel" name="actioncancel" value="'.$langs->trans("Cancel").'">';
		print '</center>';
	}

	print '</div>';
	print '</form>';
	print '<br><br>';
} // END IF not edit

// List of available record in database
dol_syslog("htdocs/admin/dict", LOG_DEBUG);
$resql = $db->query($sql);
if (!$resql) {
	dol_print_error($db);
	exit;
}

$num = $db->num_rows($resql);

print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$id.'" method="POST">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="from" value="'.dol_escape_htmltag(GETPOST('from', 'alpha')).'">';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';

$i = 0;

$param = '&id='.$id;
if ($search_label) {
	$param .= '&search_label='.urlencode($search_label);
}
if ($search_lang > 0) {
	$param .= '&search_lang='.urlencode($search_lang);
}
if ($search_type_template != '-1') {
	$param .= '&search_type_template='.urlencode($search_type_template);
}
if ($search_fk_user > 0) {
	$param .= '&search_fk_user='.urlencode($search_fk_user);
}
if ($search_topic) {
	$param .= '&search_topic='.urlencode($search_topic);
}

$paramwithsearch = $param;
if ($sortorder) {
	$paramwithsearch .= '&sortorder='.urlencode($sortorder);
}
if ($sortfield) {
	$paramwithsearch .= '&sortfield='.urlencode($sortfield);
}
if (GETPOST('from', 'alpha')) {
	$paramwithsearch .= '&from='.urlencode(GETPOST('from', 'alpha'));
}

// There is several pages
if ($num > $listlimit) {
	print '<tr class="none"><td class="right" colspan="'.(3 + count($fieldlist)).'">';
	print_fleche_navigation($page, $_SERVER["PHP_SELF"], $paramwithsearch, ($num > $listlimit), '<li class="pagination"><span>'.$langs->trans("Page").' '.($page + 1).'</span></li>');
	print '</td></tr>';
}


// Title line with search boxes
print '<tr class="liste_titre">';

foreach ($fieldlist as $field => $value) {
	if ($value == 'label') {
		print '<td class="liste_titre"><input type="text" name="search_label" class="maxwidth200" value="'.dol_escape_htmltag($search_label).'"></td>';
	} elseif ($value == 'lang') {
		print '<td class="liste_titre">';
		print $formadmin->select_language($search_lang, 'search_lang', 0, null, 1, 0, 0, 'maxwidth150');
		print '</td>';
	} elseif ($value == 'fk_user') {
		print '<td class="liste_titre">';
		print $form->select_dolusers($search_fk_user, 'search_fk_user', 1, null, 0, ($user->admin ? '' : 'hierarchyme'), null, 0, 0, 0, '', 0, '', 'maxwidth150');
		print '</td>';
	} elseif ($value == 'topic') {
		print '<td class="liste_titre"><input type="text" name="search_topic" value="'.dol_escape_htmltag($search_topic).'"></td>';
	} elseif ($value == 'type_template') {
		print '<td class="liste_titre center">';
		print $form->selectarray('search_type_template', $elementList, $search_type_template, 1, 0, 0, '', 0, 0, 0, '', 'minwidth150', 1, '', 0, 1);
		print '</td>';
	} elseif (!in_array($value, array('content', 'content_lines'))) {
		print '<td class="liste_titre"></td>';
	}
}

if (empty($conf->global->MAIN_EMAIL_TEMPLATES_FOR_OBJECT_LINES)) {
	print '<td class="liste_titre"></td>';
}

// Action column
print '<td class="liste_titre right" width="64">';
$searchpicto = $form->showFilterButtons();
print $searchpicto;
print '</td>';
print '</tr>';

// Title of lines
print '<tr class="liste_titre">';
foreach ($fieldlist as $field => $value) {
	$showfield = 1; // By defaut
	$align = "left";
	$sortable = 1;
	$valuetoshow = '';
	$forcenowrap = 1;
	/*
	$tmparray=getLabelOfField($fieldlist[$field]);
	$showfield=$tmp['showfield'];
	$valuetoshow=$tmp['valuetoshow'];
	$align=$tmp['align'];
	$sortable=$tmp['sortable'];
	*/
	$valuetoshow = ucfirst($fieldlist[$field]); // By defaut
	$valuetoshow = $langs->trans($valuetoshow); // try to translate
	if ($fieldlist[$field] == 'fk_user') {
		$valuetoshow = $langs->trans("Owner");
	}
	if ($fieldlist[$field] == 'lang') {
		$valuetoshow = $langs->trans("Language");
	}
	if ($fieldlist[$field] == 'type') {
		$valuetoshow = $langs->trans("Type");
	}
	if ($fieldlist[$field] == 'libelle' || $fieldlist[$field] == 'label') {
		$valuetoshow = $langs->trans("Code");
	}
	if ($fieldlist[$field] == 'type_template') {
		$align = 'center';
		$valuetoshow = $langs->trans("TypeOfTemplate");
	}
	if ($fieldlist[$field] == 'private') {
		$align = 'center';
	}
	if ($fieldlist[$field] == 'position') {
		$align = 'center';
	}

	if ($fieldlist[$field] == 'joinfiles') {
		$valuetoshow = $langs->trans("FilesAttachedToEmail"); $align = 'center'; $forcenowrap = 0;
	}
	if ($fieldlist[$field] == 'content') {
		$valuetoshow = $langs->trans("Content"); $showfield = 0;
	}
	if ($fieldlist[$field] == 'content_lines') {
		$valuetoshow = $langs->trans("ContentForLines"); $showfield = 0;
	}

	// Show fields
	if ($showfield) {
		if (!empty($tabhelp[$id][$value])) {
			if (in_array($value, array('topic'))) {
				$valuetoshow = $form->textwithpicto($valuetoshow, $tabhelp[$id][$value], 1, 'help', '', 0, 2, 'tooltip'.$value, $forcenowrap); // Tooltip on click
			} else {
				$valuetoshow = $form->textwithpicto($valuetoshow, $tabhelp[$id][$value], 1, 'help', '', 0, 2, '', $forcenowrap); // Tooltip on hover
			}
		}
		print getTitleFieldOfList($valuetoshow, 0, $_SERVER["PHP_SELF"], ($sortable ? $fieldlist[$field] : ''), ($page ? 'page='.$page.'&' : ''), $param, "align=".$align, $sortfield, $sortorder);
	}
}

print getTitleFieldOfList($langs->trans("Status"), 0, $_SERVER["PHP_SELF"], "active", ($page ? 'page='.$page.'&' : ''), $param, 'align="center"', $sortfield, $sortorder);
print getTitleFieldOfList('');
print '</tr>';

if ($num) {
	$nbqualified = 0;

	// Lines with values
	while ($i < $num) {
		$obj = $db->fetch_object($resql);

		if ($obj) {
			if ($action == 'edit' && ($rowid == (!empty($obj->rowid) ? $obj->rowid : $obj->code))) {
				print '<tr class="oddeven" id="rowid-'.$obj->rowid.'">';

				$tmpaction = 'edit';
				$parameters = array('fieldlist'=>$fieldlist, 'tabname'=>$tabname[$id]);
				$reshook = $hookmanager->executeHooks('editEmailTemplateFieldlist', $parameters, $obj, $tmpaction); // Note that $action and $object may have been modified by some hooks
				$error = $hookmanager->error; $errors = $hookmanager->errors;

				// Show fields
				if (empty($reshook)) {
					fieldList($fieldlist, $obj, $tabname[$id], 'edit');
				}

				print '<td></td><td></td><td></td>';
				print '<td class="center">';
				print '<input type="hidden" name="page" value="'.$page.'">';
				print '<input type="hidden" name="rowid" value="'.$rowid.'">';
				print '<input type="submit" class="button buttongen button-save" name="actionmodify" value="'.$langs->trans("Modify").'">';
				print '<div name="'.(!empty($obj->rowid) ? $obj->rowid : $obj->code).'"></div>';
				print '<input type="submit" class="button buttongen button-cancel" name="actioncancel" value="'.$langs->trans("Cancel").'">';
				print '</td>';

				$fieldsforcontent = array('topic', 'joinfiles', 'content');
				if (!empty($conf->global->MAIN_EMAIL_TEMPLATES_FOR_OBJECT_LINES)) {
					$fieldsforcontent = array('topic', 'joinfiles', 'content', 'content_lines');
				}
				foreach ($fieldsforcontent as $tmpfieldlist) {
					$showfield = 1;
					$align = "left";
					$valuetoshow = $obj->{$tmpfieldlist};

					$class = 'tddict';
					// Show value for field
					if ($showfield) {
						// Show line for topic, joinfiles and content
						print '</tr><tr class="oddeven" nohover tr-'.$tmpfieldlist.'-'.$rowid.' ">';
						print '<td colspan="8">';
						if ($tmpfieldlist == 'topic') {
							print '<strong>'.$form->textwithpicto($langs->trans("Topic"), $tabhelp[$id][$tmpfieldlist], 1, 'help', '', 0, 2, $tmpfieldlist).'</strong> ';
							print '<input type="text" class="flat minwidth500" name="'.$tmpfieldlist.'-'.$rowid.'" value="'.(!empty($obj->{$tmpfieldlist}) ? $obj->{$tmpfieldlist} : '').'">';
						}
						if ($tmpfieldlist == 'joinfiles') {
							print '<strong>'.$form->textwithpicto($langs->trans("FilesAttachedToEmail"), $tabhelp[$id][$tmpfieldlist], 1, 'help', '', 0, 2, $tmpfieldlist).'</strong> ';
							print '<input type="text" class="flat maxwidth50" name="'.$tmpfieldlist.'-'.$rowid.'" value="'.(!empty($obj->{$tmpfieldlist}) ? $obj->{$tmpfieldlist} : '').'">';
						}

						// If $acceptlocallinktomedia is true, we can add link  media files int email templates (we already can do this into HTML editor of an email).
						// Note that local link to a file into medias are replaced with a real link by email in CMailFile.class.php with value $urlwithroot defined like this:
						// $urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
						// $urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
						$acceptlocallinktomedia = getDolGlobalInt('MAIN_DISALLOW_MEDIAS_IN_EMAIL_TEMPLATES') ? 0 : 1;
						if ($acceptlocallinktomedia) {
							global $dolibarr_main_url_root;
							$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));

							// Parse $newUrl
							$newUrlArray = parse_url($urlwithouturlroot);
							$hosttocheck = $newUrlArray['host'];
							$hosttocheck = str_replace(array('[', ']'), '', $hosttocheck); // Remove brackets of IPv6

							if (function_exists('gethostbyname')) {
								$iptocheck = gethostbyname($hosttocheck);
							} else {
								$iptocheck = $hosttocheck;
							}

							//var_dump($iptocheck.' '.$acceptlocallinktomedia);
							if (!filter_var($iptocheck, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
								// If ip of public url is an private network IP, we do not allow this.
								$acceptlocallinktomedia = 0;
								// TODO Show a warning
							}

							if (preg_match('/http:/i', $urlwithouturlroot)) {
								// If public url is not a https, we do not allow to add medias link. It will generate security alerts when email will be sent.
								$acceptlocallinktomedia = 0;
								// TODO Show a warning
							}
						}

						if ($tmpfieldlist == 'content') {
							print $form->textwithpicto($langs->trans("Content"), $tabhelp[$id][$tmpfieldlist], 1, 'help', '', 0, 2, $tmpfieldlist).'<br>';
							$okforextended = true;
							if (empty($conf->global->FCKEDITOR_ENABLE_MAIL)) {
								$okforextended = false;
							}
							$doleditor = new DolEditor($tmpfieldlist.'-'.$rowid, (!empty($obj->{$tmpfieldlist}) ? $obj->{$tmpfieldlist} : ''), '', 500, 'dolibarr_mailings', 'In', 0, $acceptlocallinktomedia, $okforextended, ROWS_6, '90%');
							print $doleditor->Create(1);
						}
						if ($tmpfieldlist == 'content_lines') {
							print $form->textwithpicto($langs->trans("ContentForLines"), $tabhelp[$id][$tmpfieldlist], 1, 'help', '', 0, 2, $tmpfieldlist).'<br>';
							$okforextended = true;
							if (empty($conf->global->FCKEDITOR_ENABLE_MAIL)) {
								$okforextended = false;
							}
							$doleditor = new DolEditor($tmpfieldlist.'-'.$rowid, (! empty($obj->{$tmpfieldlist}) ? $obj->{$tmpfieldlist} : ''), '', 140, 'dolibarr_mailings', 'In', 0, $acceptlocallinktomedia, $okforextended, ROWS_6, '90%');
							print $doleditor->Create(1);
						}
						print '</td>';
						print '<td></td>';
						print '<td></td>';
					}
				}

				print "</tr>\n";

				$nbqualified++;
			} else {
				// If template is for a module, check module is enabled.
				if ($obj->module) {
					$tempmodulekey = $obj->module;
					if (empty($conf->$tempmodulekey) || empty($conf->$tempmodulekey->enabled)) {
						$i++;
						continue;
					}
				}

				$keyforobj = 'type_template';
				if (!in_array($obj->$keyforobj, array_keys($elementList))) {
					$i++;
					continue; // It means this is a type of template not into elementList (may be because enabled condition of this type is false because module is not enabled)
				}
				// Test on 'enabled'
				if (!dol_eval($obj->enabled, 1, 1, '1')) {
					$i++;
					continue; // Email template not qualified
				}

				$nbqualified++;

				print '<tr class="oddeven" id="rowid-'.$obj->rowid.'">';

				$tmpaction = 'view';
				$parameters = array('fieldlist'=>$fieldlist, 'tabname'=>$tabname[$id]);
				$reshook = $hookmanager->executeHooks('viewEmailTemplateFieldlist', $parameters, $obj, $tmpaction); // Note that $action and $object may have been modified by some hooks

				$error = $hookmanager->error; $errors = $hookmanager->errors;

				if (empty($reshook)) {
					foreach ($fieldlist as $field => $value) {
						if (in_array($fieldlist[$field], array('content', 'content_lines'))) {
							continue;
						}
						$showfield = 1;
						$align = "";
						$class = "tddict";
						$title = '';
						$valuetoshow = $obj->{$fieldlist[$field]};
						if ($value == 'label' || $value == 'topic') {
							if ($langs->trans($valuetoshow) != $valuetoshow) {
								$valuetoshow = $langs->trans($valuetoshow);
							}
							$valuetoshow = dol_escape_htmltag($valuetoshow);
						}
						if ($value == 'label') {
							$class .= ' tdoverflowmax100';
						}
						if ($value == 'topic') {
							$class .= ' tdoverflowmax200 small';
						}
						if ($value == 'type_template') {
							$valuetoshow = isset($elementList[$valuetoshow]) ? $elementList[$valuetoshow] : $valuetoshow;
							$align = "center";
						}
						if ($value == 'lang' && $valuetoshow) {
							$valuetoshow = $valuetoshow.' - '.$langs->trans("Language_".$valuetoshow);
						}
						if ($value == 'fk_user') {
							if ($valuetoshow > 0) {
								$fuser = new User($db);
								$fuser->fetch($valuetoshow);
								$valuetoshow = $fuser->getNomUrl(1);
							}
						}
						if ($value == 'private') {
							$align = "center";
							if ($valuetoshow) {
								$valuetoshow = yn($valuetoshow);
							} else {
								$valuetoshow = '';
							}
						}
						if ($value == 'position') {
							$align = "center";
						}
						if ($value == 'joinfiles') {
							$align = "center";
							if ($valuetoshow) {
								$valuetoshow = 1;
							} else {
								$valuetoshow = '';
							}
						}
						if ($align) {
							$class .= ' '.$align;
						}

						// Show value for field
						if ($showfield) {
							print '<!-- '.$fieldlist[$field].' -->';
							print '<td class="'.$class.'"';
							if (in_array($value, array('code', 'label', 'topic'))) {
								print ' title="'.dol_escape_htmltag($valuetoshow).'"';
							}
							print '>';
							print $valuetoshow;
							print '</td>';
						}
					}
				}

				// Can an entry be erased or disabled ?
				$iserasable = 1; $canbedisabled = 1; $canbemodified = 1; // true by default
				if (!$user->admin && $obj->fk_user != $user->id) {
					$iserasable = 0;
					$canbedisabled = 0;
					$canbemodified = 0;
				}

				$url = $_SERVER["PHP_SELF"].'?'.($page ? 'page='.$page.'&' : '').'sortfield='.$sortfield.'&sortorder='.$sortorder.'&rowid='.(!empty($obj->rowid) ? $obj->rowid : (!empty($obj->code) ? $obj->code : '')).'&code='.(!empty($obj->code) ?urlencode($obj->code) : '');
				if ($param) {
					$url .= '&'.$param;
				}

				// Status / Active
				print '<td class="center nowrap">';
				if ($canbedisabled) {
					print '<a class="reposition" href="'.$url.'&action='.$acts[$obj->active].'&token='.newToken().'">'.$actl[$obj->active].'</a>';
				} else {
					print '<span class="opacitymedium">'.$actl[$obj->active].'</span>';
				}
				print "</td>";

				// Modify link / Delete link
				print '<td class="center nowraponall" width="64">';
				if ($canbemodified) {
					print '<a class="reposition editfielda" href="'.$url.'&action=edit&token='.newToken().'">'.img_edit().'</a>';
				}
				if ($iserasable) {
					print '<a class="marginleftonly" href="'.$url.'&action=delete&token='.newToken().'">'.img_delete().'</a>';
					//else print '<a href="#">'.img_delete().'</a>';    // Some dictionary can be edited by other profile than admin
				}
				print '</td>';

				print "</tr>\n";
			}
		}

		$i++;
	}
}

// If no record found
if ($nbqualified == 0) {
	$colspan = 10;
	print '<tr><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span></td></tr>';
}

print '</table>';
print '</div>';

print '</form>';


if (!empty($user->admin) && (empty($_SESSION['leftmenu']) || $_SESSION['leftmenu'] != 'email_templates')) {
	print dol_get_fiche_end();
}


// End of page
llxFooter();
$db->close();


/**
 *	Show fields in insert/edit mode
 *
 * 	@param		array	$fieldlist		Array of fields
 * 	@param		Object	$obj			If we show a particular record, obj is filled with record fields
 *  @param		string	$tabname		Name of SQL table
 *  @param		string	$context		'add'=Output field for the "add form", 'edit'=Output field for the "edit form", 'hide'=Output field for the "add form" but we dont want it to be rendered
 *	@return		void
 */
function fieldList($fieldlist, $obj = '', $tabname = '', $context = '')
{
	global $conf, $langs, $user, $db;
	global $form;
	global $elementList;

	$formadmin = new FormAdmin($db);

	foreach ($fieldlist as $field => $value) {
		if ($value == 'fk_user') {
			print '<td>';
			if ($user->admin) {
				print $form->select_dolusers(empty($obj->{$value}) ? '' : $obj->{$value}, 'fk_user', 1, null, 0, ($user->admin ? '' : 'hierarchyme'), null, 0, 0, 0, '', 0, '', 'minwidth150 maxwidth300');
			} else {
				if ($context == 'add') {	// I am not admin and we show the add form
					print $user->getNomUrl(1); // Me
					$forcedvalue = $user->id;
				} else {
					if ($obj && !empty($obj->{$value}) && $obj->{$value} > 0) {
						$fuser = new User($db);
						$fuser->fetch($obj->{$value});
						print $fuser->getNomUrl(1);
						$forcedvalue = $fuser->id;
					} else {
						$forcedvalue = $obj->{$value};
					}
				}
				$keyname = $value;
				print '<input type="hidden" value="'.$forcedvalue.'" name="'.$keyname.'">';
			}
			print '</td>';
		} elseif ($value == 'lang') {
			print '<td>';
			if (!empty($conf->global->MAIN_MULTILANGS)) {
				$selectedlang = GETPOSTISSET('langcode') ?GETPOST('langcode', 'aZ09') : $langs->defaultlang;
				if ($context == 'edit') {
					$selectedlang = $obj->{$value};
				}
				print $formadmin->select_language($selectedlang, 'langcode', 0, null, 1, 0, 0, 'maxwidth150');
			} else {
				if (!empty($obj->{$value})) {
					print $obj->{$value}.' - '.$langs->trans('Language_'.$obj->{$value});
				}
				$keyname = $value;
				if ($keyname == 'lang') {
					$keyname = 'langcode'; // Avoid conflict with lang param
				}
				print '<input type="hidden" value="'.(empty($obj->{$value}) ? '' : $obj->{$value}).'" name="'.$keyname.'">';
			}
			print '</td>';
		} elseif ($value == 'type_template') {
			// Le type de template
			print '<td class="center">';
			if ($context == 'edit' && !empty($obj->{$value}) && !in_array($obj->{$value}, array_keys($elementList))) {
				// Current template type is an unknown type, so we must keep it as it is.
				print '<input type="hidden" name="type_template" value="'.$obj->{$value}.'">';
				print $obj->{$value};
			} else {
				print $form->selectarray('type_template', $elementList, (!empty($obj->{$value}) ? $obj->{$value}:''), 1, 0, 0, '', 0, 0, 0, '', 'minwidth150', 1, '', 0, 1);
			}
			print '</td>';
		} elseif ($context == 'add' && in_array($value, array('topic', 'joinfiles', 'content', 'content_lines'))) {
			continue;
		} elseif ($context == 'edit' && in_array($value, array('topic', 'joinfiles', 'content', 'content_lines'))) {
			continue;
		} elseif ($context == 'hide' && in_array($value, array('topic', 'joinfiles', 'content', 'content_lines'))) {
			continue;
		} else {
			$size = ''; $class = ''; $classtd = '';
			if ($value == 'code') {
				$class = 'maxwidth100';
			}
			if ($value == 'label') {
				$class = 'maxwidth200';
			}
			if ($value == 'private') {
				$class = 'maxwidth50'; $classtd = 'center';
			}
			if ($value == 'position') {
				$class = 'maxwidth50'; $classtd = 'center';
			}
			if ($value == 'libelle') {
				$class = 'quatrevingtpercent';
			}
			if ($value == 'topic') {
				$class = 'quatrevingtpercent';
			}
			if ($value == 'sortorder' || $value == 'sens' || $value == 'category_type') {
				$size = 'size="2" ';
			}

			print '<td'.($classtd ? ' class="'.$classtd.'"' : '').'>';
			if ($value == 'private') {
				if (empty($user->admin)) {
					print $form->selectyesno($value, '1', 1);
				} else {
					//print '<input type="text" '.$size.'class="flat'.($class?' '.$class:'').'" value="1" name="'.$fieldlist[$field].'">';
					print $form->selectyesno($value, (isset($obj->{$value}) ? $obj->{$value}:''), 1);
				}
			} else {
				print '<input type="text" '.$size.'class="flat'.($class ? ' '.$class : '').'" value="'.(isset($obj->{$value}) ? $obj->{$value}:'').'" name="'. $value .'">';
			}
			print '</td>';
		}
	}
}
