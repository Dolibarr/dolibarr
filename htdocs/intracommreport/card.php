<?php
/* Copyright (C) 2015       ATM Consulting          <support@atm-consulting.fr>
 * Copyright (C) 2019-2020  Open-DSI                <support@open-dsi.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *	\file       htdocs/intracommreport/card.php
 *	\ingroup    Intracomm report
 *	\brief      Page to manage intracomm report export
 */


/**
 *  Terms
 *
 *	DEB = Declaration d'Exchanges de Biens (FR)   =  Declaration of Exchange of Goods (EN)
 *  DES = Déclaration Européenne de Services (FR) =  European Declaration of Services (EN)
 *
 *  INTRACOMM: Douanes françaises (FR) = french customs (EN)  -  https://www.douane.gouv.fr/professionnels/commerce-international/import-export
 *
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/intracommreport/class/intracommreport.class.php';

// Load translation files required by the page
$langs->loadLangs(array("intracommreport"));

// Get Parameters
$id = GETPOSTINT('id');
$action = GETPOST('action');
$year = GETPOSTINT('year');
$month = GETPOSTINT('month');
$label = (string) GETPOST('label', 'alphanohtml');

$exporttype = GETPOSTISSET('exporttype') ? GETPOST('exporttype', 'alphanohtml') : 'deb'; // DEB or DES
$type_declaration = (string) GETPOST('type_declaration', 'alphanohtml');	// 'introduction' or 'expedition'

$backtopage = GETPOST('backtopage', 'alpha');

$declaration = array(
	"deb" => $langs->trans("DEB"),
	"des" => $langs->trans("DES"),
);

$typeOfDeclaration = array(
	"introduction" => $langs->trans("Introduction"),
	"expedition" => $langs->trans("Expedition"),
);

// Initialize a technical objects
$object = new IntracommReport($db);
if ($id > 0) {
	$object->fetch($id);
}
$form = new Form($db);
$formother = new FormOther($db);

// Initialize a technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('intracommcard', 'globalcard'));

$error = 0;

// Permissions
$permissiontoread = $user->hasRight('intracommreport', 'read');
$permissiontoadd = $user->hasRight('intracommreport', 'write');
$permissiontodelete = $user->hasRight('intracommreport', 'delete');

// Security check (enable the most restrictive one)
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (isset($object->status) && ($object->status == $object::STATUS_DRAFT) ? 1 : 0);
//restrictedArea($user, $object->element, $object->id, $object->table_element, '', 'fk_soc', 'rowid', $isdraft);
if (empty($conf->intracommreport->enabled)) {
	accessforbidden();
}
if (!$permissiontoread) {
	accessforbidden();
}



/*
 * 	Actions
 */

$parameters = array('id' => $id);
// Note that $action and $object may have been modified by some hooks
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if ($permissiontodelete && $action == 'confirm_delete' && $confirm == 'yes') {
	$result = $object->delete($user);
	if ($result > 0) {
		if (!empty($backtopage)) {
			header("Location: ".$backtopage);
			exit;
		} else {
			header("Location: list.php");
			exit;
		}
	} else {
		$errmesg = $object->error;
	}
}

if ($action == 'add' && $permissiontoadd) {
	$object->label = trim($label);
	$object->exporttype = trim($exporttype);		// 'des' or 'deb'
	$object->type_declaration =  $type_declaration;	// 'introduction' or 'expedition'
	//$object->subscription = (int) $subscription;

	// Fill array 'array_options' with data from add form
	// $ret = $extrafields->setOptionalsFromPost($extralabels, $object);
	// if ($ret < 0) {
	// 	$error++;
	// }

	if (empty($object->label)) {
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Label")), null, 'errors');
	} else {
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."intracommreport WHERE ref='".$db->escape($object->label)."'";
		$result = $db->query($sql);
		if ($result) {
			$num = $db->num_rows($result);
		}
		if ($num) {
			$error++;
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorLabelAlreadyExists", $login), null, 'errors');
		}
	}

	if (!$error) {
		$id = $object->create($user);
		if ($id > 0) {
			header("Location: ".$_SERVER["PHP_SELF"].'?id='.$id);
			exit;
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
			$action = 'create';
		}
	} else {
		$action = 'create';
	}
}


/*
 * View
 */

$title = $langs->trans("IntracommReportTitle");
llxHeader("", $title, '', '', 0, 0, '', '', '', 'mod-intracommreport page-card');

// Creation mode
if ($action == 'create') {
	print load_fiche_titre($langs->trans("IntracommReportTitle"));

	print '<form name="charge" method="post" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add" />';

	print dol_get_fiche_head();

	print '<table class="border" width="100%">';

	// Label
	print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("Label").'</td><td><input type="text" class="minwidth200" name="label" autofocus="autofocus"></td></tr>';

	// Declaration
	print '<tr><td class="fieldrequired">'.$langs->trans("Declaration")."</td><td>\n";
	print $form->selectarray("declaration", $declaration, GETPOST('declaration', 'alpha') ? GETPOST('declaration', 'alpha') : $object->declaration, 0);
	print "</td>\n";

	// Analysis period
	print '<tr>';
	print '<td class="titlefieldcreate fieldrequired">';
	print $langs->trans("AnalysisPeriod");
	print '</td>';
	print '<td>';
	print $formother->select_month($month ? date('M') : $month, 'month', 0, 1, 'widthauto valignmiddle ', true);
	print $formother->selectyear($year ? date('Y') : $year, 'year', 0, 3, 3, 0, 0, '', '', true);
	print '</td>';
	print '</tr>';

	// Type of declaration
	print '<tr><td class="fieldrequired">'.$langs->trans("TypeOfDeclaration")."</td><td>\n";
	print $form->selectarray("type_declaration", $typeOfDeclaration, GETPOST('type_declaration', 'alpha') ? GETPOST('type_declaration', 'alpha') : $object->type_declaration, 0);
	print "</td>\n";

	print '</table>';

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel();

	print '</form>';
}

if ($id > 0 && $action != 'edit') {
	/* ************************************************************************** */
	/*                                                                            */
	/* View mode                                                                  */
	/*                                                                            */
	/* ************************************************************************** */
	$res = $object->fetch($id);
	if ($res < 0) {
		dol_print_error($db, $object->error);
		exit;
	}

	/*
	 * Show tabs
	 */
	//$head = intracommreport_prepare_head($object);

	print dol_get_fiche_head(array(), 'general', $langs->trans("IntracommReport"), -1, 'user');

	// Confirm remove report
	if ($action == 'delete') {
		$formquestion = array();
		if ($backtopage) {
			$formquestion[] = array(
				'type' => 'hidden',
				'name' => 'backtopage',
				'value' => ($backtopage != '1' ? $backtopage : $_SERVER["HTTP_REFERER"])
			);
		}
		print $form->formconfirm(
			"card.php?rowid=".urlencode((string) ($id)),
			$langs->trans("DeleteReport"),
			$langs->trans("ConfirmDeleteReport"),
			"confirm_delete",
			$formquestion,
			'no',
			1
		);
	}

	$linkback = '<a href="'.DOL_URL_ROOT.'/intracommreport/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	dol_banner_tab($object, 'rowid', $linkback);

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';

	print '<div class="underbanner clearboth"></div>';
	print '<table class="border tableforfield centpercent">';

	// Type
	print '<tr><td class="titlefield">'.$langs->trans("Type").'</td><td class="valeur">'.$object->declaration."</td></tr>\n";

	// Analysis period
	print '<tr><td>'.$langs->trans("AnalysisPeriod").'</td><td class="valeur">'.$object->period.'</td>';
	print '</tr>';

	// Type of Declaration
	print '<tr><td>'.$langs->trans("TypeOfDeclaration").'</td><td class="valeur">'.$object->exporttype.'</td>';
	print '</tr>';

	print "</table>\n";

	print "</div></div></div>\n";
	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();
}

// End of page
llxFooter();
$db->close();
