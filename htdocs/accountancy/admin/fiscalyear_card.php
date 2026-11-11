<?php
/* Copyright (C) 2014-2026	Alexandre Spangaro	<alexandre@inovea-conseil.com>
 * Copyright (C) 2018-2026  Frédéric France     <frederic.france@free.fr>
 * Copyright (C) 2025		MDW					<mdeweerd@users.noreply.github.com>
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
 * \file        htdocs/accountancy/admin/fiscalyear_card.php
 * \ingroup     Accountancy (Double entries)
 * \brief       Page to show a fiscal year
 */

// Load Dolibarr environment
require '../../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var ExtraFields $extrafields
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/core/lib/fiscalyear.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/fiscalyear.class.php';

// Load translation files required by the page
$langs->loadLangs(array("admin", "compta"));

// Get parameters
$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha') ? GETPOST('ref', 'alpha') : GETPOST('label', 'alpha');

$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'alpha');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : str_replace('_', '', basename(dirname(__FILE__)).basename(__FILE__, '.php')); // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');					// if not set, a default page will be used
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');	// if not set, $backtopage will be used
$dol_openinpopup = GETPOST('dol_openinpopup', 'aZ09');

$error = 0;

// Initialize a technical objects
$object = new Fiscalyear($db);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'.

$date_start = dol_mktime(0, 0, 0, GETPOSTINT('fiscalyearmonth'), GETPOSTINT('fiscalyearday'), GETPOSTINT('fiscalyearyear'));
$date_end = dol_mktime(0, 0, 0, GETPOSTINT('fiscalyearendmonth'), GETPOSTINT('fiscalyearendday'), GETPOSTINT('fiscalyearendyear'));

$permissiontoadd = $user->hasRight('accounting', 'fiscalyear', 'write');
$permissiontodelete = $user->hasRight('accounting', 'fiscalyear', 'write');

// Security check
if ($user->socid > 0) {
	accessforbidden();
}
if (!$permissiontoadd) { // after this test $permissiontoadd is always true
	accessforbidden();
}


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if ($reshook == 0) {
	if ($action == 'confirm_delete' && $confirm == "yes") { // @phpstan-ignore-line $permissiontoadd always true
		$result = $object->fetch($id);
		$object->oldcopy = dol_clone($object, 2); // @phan-suppress-current-line PhanTypeMismatchProperty
		$result = $object->delete($user);
		if ($result >= 0) {
			header("Location: fiscalyear.php");
			exit();
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} elseif ($action == 'add') { // @phpstan-ignore-line $permissiontoadd always true
		if (!GETPOST('cancel', 'alpha')) {
			$error = 0;

			$object->date_start = $date_start;
			$object->date_end = $date_end;
			$object->label = GETPOST('label', 'alpha');
			$object->status = 0; // A new fiscal year is always open
			$object->datec = dol_now();

			if (empty($object->date_start) || empty($object->date_end)) {
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Date")), null, 'errors');
				$error++;
			}
			if (empty($object->label)) {
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Label")), null, 'errors');
				$error++;
			}

			if (!$error) {
				$db->begin();

				$id = $object->create($user);

				if ($id > 0) {
					$db->commit();

					header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
					exit;
				} else {
					$db->rollback();

					// Handle overlap error
					if ($id == -5 && !empty($object->errors[0])) {
						setEventMessages($langs->trans($object->error, $object->errors[0]), null, 'errors');
					} else {
						setEventMessages($object->error, $object->errors, 'errors');
					}
					$action = 'create';
				}
			} else {
				$action = 'create';
			}
		} else {
			header("Location: ".DOL_URL_ROOT.'/accountancy/admin/fiscalyear.php');
			exit();
		}
	} elseif ($action == 'update') { // @phpstan-ignore-line $permissiontoadd always true
		// Update record
		if (!GETPOST('cancel', 'alpha')) {
			$result = $object->fetch($id);

			$object->date_start = GETPOST('fiscalyear') ? $date_start : '';
			$object->date_end = GETPOST('fiscalyearend') ? $date_end : '';
			$object->label = GETPOST('label', 'alpha');
			$object->status = GETPOSTINT('status');

			$result = $object->update($user);

			if ($result > 0) {
				header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
				exit;
			} else {
				// Handle overlap error
				if ($result == -5 && !empty($object->errors[0])) {
					setEventMessages($langs->trans($object->error, $object->errors[0]), null, 'errors');
				} else {
					setEventMessages($object->error, $object->errors, 'errors');
				}
			}
		} else {
			header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
			exit;
		}
	} elseif ($action == 'reopen') { // @phpstan-ignore-line $permissiontoadd always true
		if (getDolGlobalString('ACCOUNTING_CAN_REOPEN_CLOSED_PERIOD')) {
			$result = $object->fetch($id);

			$object->status = GETPOSTINT('status');

			$result = $object->update($user);

			if ($result > 0) {
				header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
				exit;
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	}
}


/*
 * View
 */

$form = new Form($db);

$title = $langs->trans("Fiscalyear")." - ".$langs->trans("Card");
if ($action == 'create') {
	$title = $langs->trans("NewFiscalYear");
}

$help_url = 'EN:Module_Double_Entry_Accounting#Setup|FR:Module_Comptabilit&eacute;_en_Partie_Double#Configuration';

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-accountancy page-fiscalyear_card');

// Default values for a new fiscal year
$defaultstart = '';
$defaultend = '';
$defaultlabel = '';
if ($action == 'create' && empty($date_start)) {
	$lastend = 0;
	$sql = "SELECT MAX(date_end) as date_end FROM ".MAIN_DB_PREFIX."accounting_fiscalyear";
	$sql .= " WHERE entity = ".((int) $conf->entity);
	$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		if ($obj && !empty($obj->date_end)) {
			$lastend = $db->jdate($obj->date_end);
		}
	}

	if ($lastend) {
		// Day after the end of the previous fiscal year
		$p = dol_getdate($lastend);
		$defaultstart = dol_time_plus_duree(dol_mktime(12, 0, 0, $p['mon'], $p['mday'], $p['year']), 1, 'd');
	} else {
		// No fiscal year yet: use SOCIETE_FISCAL_MONTH_START (1 = January)
		$startmonth = max(1, min(12, getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1)));
		$now = dol_getdate(dol_now());
		$startyear = ($startmonth > $now['mon']) ? $now['year'] - 1 : $now['year'];
		$defaultstart = dol_mktime(12, 0, 0, $startmonth, 1, $startyear);
	}

	// End = start + 12 months - 1 day
	$defaultend = dol_time_plus_duree(dol_time_plus_duree($defaultstart, 12, 'm'), -1, 'd');

	// Label: "2026" if same calendar year, otherwise "2026-2027"
	$ys = (int) dol_print_date($defaultstart, '%Y');
	$ye = (int) dol_print_date($defaultend, '%Y');
	$defaultlabel = ($ys == $ye) ? (string) $ys : $ys.'-'.$ye;
}

if ($action == 'create') {
	print load_fiche_titre($title, '', 'object_'.$object->picto);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';

	print dol_get_fiche_head(array(), '');

	print '<table class="border centpercent tableforfieldcreate">';

	// Label
	print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("Label").'</td><td>';
	print '<input name="label" id="label" size="32" value="'.dol_escape_htmltag(GETPOST('label', 'alpha') ? GETPOST('label', 'alpha') : $defaultlabel).'">';
	print '</td></tr>';

	// Date start
	print '<tr><td class="fieldrequired">'.$langs->trans("DateStart").'</td><td>';
	print $form->selectDate($date_start ? $date_start : ($defaultstart ? $defaultstart : -1), 'fiscalyear');
	print '</td></tr>';

	// Date end
	print '<tr><td class="fieldrequired">'.$langs->trans("DateEnd").'</td><td>';
	print $form->selectDate($date_end ? $date_end : ($defaultend ? $defaultend : -1), 'fiscalyearend');
	print '</td></tr>';

	/*
	// Status
	print '<tr>';
	print '<td class="fieldrequired">' . $langs->trans("Status") . '</td>';
	print '<td class="valeur">';
	print $form->selectarray('status', $status2label, GETPOST('status', 'int'));
	print '</td></tr>';
	*/

	print '</table>'."\n";

	print dol_get_fiche_end();

	$labelposted = GETPOST('label', 'alpha') ? 1 : 0;
	print '<script>
	jQuery(document).ready(function() {
		// The label is "touched" only if the user typed it (or it was posted back after an error)
		var labelTouched = '.((int) $labelposted).';
		jQuery("#label").on("input", function() { labelTouched = true; });

		function readDate(prefix) {
			var d = parseInt(jQuery("#" + prefix + "day").val());
			var m = parseInt(jQuery("#" + prefix + "month").val());
			var y = parseInt(jQuery("#" + prefix + "year").val());
			if (!d || !m || !y) return null;
			return new Date(y, m - 1, d);
		}

		function updateLabel() {
			if (labelTouched) return;
			var start = readDate("fiscalyear");
			var end = readDate("fiscalyearend");
			if (!start || !end) return;
			var ys = start.getFullYear(), ye = end.getFullYear();
			jQuery("#label").val(ys === ye ? ys : ys + "-" + ye);
		}

		function updateEndFromStart() {
			var start = readDate("fiscalyear");
			if (!start) return;
			var end = new Date(start.getFullYear() + 1, start.getMonth(), start.getDate() - 1);
			jQuery("#fiscalyearendday").val(end.getDate());
			jQuery("#fiscalyearendmonth").val(end.getMonth() + 1);
			jQuery("#fiscalyearendyear").val(end.getFullYear());
			jQuery("#fiscalyearend").datepicker("setDate", end);
			updateLabel();
		}

		jQuery("#fiscalyear").on("change", function() { setTimeout(updateEndFromStart, 100); });
		jQuery("#fiscalyearend").on("change", function() { setTimeout(updateLabel, 100); });
	});
	</script>';

	print $form->buttonsSaveCancel("Create");

	print '</form>';

	dol_set_focus('input[name="label"]');
}

// Part to edit record
if (($id || $ref) && $action == 'edit') {
	print load_fiche_titre($langs->trans("Fiscalyear"), '', 'object_'.$object->picto);

	print '<form method="POST" name="update" action="'.$_SERVER["PHP_SELF"].'">'."\n";
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="status" value="' . $object->status . '">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';
	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	}
	if ($backtopageforcancel) {
		print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
	}

	print dol_get_fiche_head();

	print '<table class="border centpercent tableforfieldedit">'."\n";

	// Ref
	print "<tr>";
	print '<td class="titlefieldcreate titlefield">'.$langs->trans("Ref").'</td><td>';
	print $object->ref;
	print '</td></tr>';

	// Label
	print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td><td>';
	print '<input name="label" class="flat" size="32" value="'.dolPrintHTMLForAttribute($object->label).'">';
	print '</td></tr>';

	// Date start
	print '<tr><td class="fieldrequired">'.$langs->trans("DateStart").'</td><td>';
	print $form->selectDate($object->date_start ? $object->date_start : -1, 'fiscalyear');
	print '</td></tr>';

	// Date end
	print '<tr><td class="fieldrequired">'.$langs->trans("DateEnd").'</td><td>';
	print $form->selectDate($object->date_end ? $object->date_end : -1, 'fiscalyearend');
	print '</td></tr>';

	// Status
	print '<tr><td>'.$langs->trans("Status").'</td><td>';
	print $object->getLibStatut(4);
	print '</td></tr>';

	print '</table>';

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel();

	print '</form>';
}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
	$head = fiscalyear_prepare_head($object);

	print dol_get_fiche_head($head, 'card', $langs->trans("Fiscalyear"), -1, $object->picto);

	$morehtmlref = '<div class="refidno">';
	$morehtmlref .= '</div>';

	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id, $langs->trans("DeleteFiscalYear"), $langs->trans("ConfirmDeleteFiscalYear"), "confirm_delete", '', 0, 1);
	}

	// Print form confirm
	print $formconfirm;

	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="'.DOL_URL_ROOT.'/accountancy/admin/fiscalyear.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	dol_banner_tab($object, 'label', $linkback, 1, 'label', 'label', $morehtmlref);

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">'."\n";

	// Id
	print "<tr>";
	print '<td class="titlefield">'.$langs->trans("Id").'</td><td>';
	print $object->id;
	print '</td></tr>';

	// Label
	print '<tr><td class="tdtop">';
	print $form->editfieldkey("Label", 'label', $object->label, $object, 0, 'alpha:32');
	print '</td><td>';
	print $form->editfieldval("Label", 'label', $object->label, $object, 0, 'alpha:32');
	print '</td></tr>';

	// Date start
	print '<tr><td>';
	print $form->editfieldkey("DateStart", 'date_start', (string) $object->date_start, $object, 0, 'datepicker');
	print '</td><td>';
	print $form->editfieldval("DateStart", 'date_start', $object->date_start, $object, 0, 'datepicker');
	print '</td></tr>';

	// Date end
	print '<tr><td>';
	print $form->editfieldkey("DateEnd", 'date_end', (string) $object->date_end, $object, 0, 'datepicker');
	print '</td><td>';
	print $form->editfieldval("DateEnd", 'date_end', $object->date_end, $object, 0, 'datepicker');
	print '</td></tr>';

	print '</table>';

	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();


	/*
	 * Action bar
	 */
	if ($user->hasRight('accounting', 'fiscalyear', 'write')) {
		print '<div class="tabsAction">';

		if (getDolGlobalString('ACCOUNTING_CAN_REOPEN_CLOSED_PERIOD') && $object->status == $object::STATUS_CLOSED) {
			print dolGetButtonAction($langs->trans("ReOpen"), '', 'reopen', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=reopen&token='.newToken(), 'reopen', (int) $permissiontoadd);
		}

		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&token='.newToken().'&id='.$id.'">'.$langs->trans('Modify').'</a>';

		print dolGetButtonAction($langs->trans('Delete'), $langs->trans('Delete'), 'delete', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&token='.newToken(), 'delete', (int) $permissiontodelete);

		print '</div>';
	}
}

// End of page
llxFooter();
$db->close();
