<?php
/* Copyright (C) 2004-2020  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2013  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2016-2018  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2017-2022  Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2021       Gauthier VERDOL     	<gauthier.verdol@atm-consulting.fr>
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
 *      \file       htdocs/compta/sociales/card.php
 *		\ingroup    tax
 *		\brief      Social contribution card page
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formsocialcontrib.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/paymentsocialcontribution.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/tax.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
if (isModEnabled('project')) {
	include_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}
if (isModEnabled('accounting')) {
	include_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array('compta', 'bills', 'banks', 'hrm'));

$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'myobjectcard'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');

$lineid = GETPOSTINT('lineid');

$fk_project = (GETPOST('fk_project') ? GETPOSTINT('fk_project') : 0);

$dateech = dol_mktime(GETPOST('echhour'), GETPOST('echmin'), GETPOST('echsec'), GETPOST('echmonth'), GETPOST('echday'), GETPOST('echyear'));
$dateperiod = dol_mktime(GETPOST('periodhour'), GETPOST('periodmin'), GETPOST('periodsec'), GETPOST('periodmonth'), GETPOST('periodday'), GETPOST('periodyear'));
$label = GETPOST('label', 'alpha');
$actioncode = GETPOST('actioncode');
$fk_user = GETPOSTINT('userid') > 0 ? GETPOSTINT('userid') : 0;

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('taxcard', 'globalcard'));

// Initialize a technical objects
$object = new ChargeSociales($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->tax->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('taxsocialcontributioncard', 'globalcard'));

if (empty($action) && empty($id) && empty($ref)) {
	$action = 'view';
}

// Load object
if ($id > 0 || $ref) {
	$object->fetch($id, $ref);
}

$permissiontoread = $user->hasRight('tax', 'charges', 'lire');
$permissiontoadd = $user->hasRight('tax', 'charges', 'creer'); // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->rights->tax->charges->supprimer || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_UNPAID);
$permissionnote = $user->hasRight('tax', 'charges', 'creer'); // Used by the include of actions_setnotes.inc.php
$permissiondellink = $user->hasRight('tax', 'charges', 'creer'); // Used by the include of actions_dellink.inc.php
$upload_dir = $conf->tax->multidir_output[isset($object->entity) ? $object->entity : 1];

// Security check
$socid = GETPOSTINT('socid');
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'tax', $object->id, 'chargesociales', 'charges');


/*
 * Actions
 */

$parameters = array('socid' => $socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	// Classify paid
	if ($action == 'confirm_paid' && $permissiontoadd && $confirm == 'yes') {
		$result = $object->setPaid($user);
	}

	if ($action == 'reopen' && $user->hasRight('tax', 'charges', 'creer')) {
		if ($object->paye) {
			$result = $object->setUnpaid($user);
			if ($result > 0) {
				header('Location: '.$_SERVER["PHP_SELF"].'?id='.$id);
				exit();
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	}

	// Link to a project
	if ($action == 'classin' && $permissiontoadd) {
		$object->setProject(GETPOST('fk_project'));
	}

	if ($action == 'setfk_user' && $permissiontoadd) {
		$object->fk_user = $fk_user;
		$object->update($user);
	}

	if ($action == 'setlib' && $permissiontoadd) {
		$result = $object->setValueFrom('libelle', GETPOST('lib'), '', '', 'text', '', $user, 'TAX_MODIFY');
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// payment mode
	if ($action == 'setmode' && $permissiontoadd) {
		$result = $object->setPaymentMethods(GETPOSTINT('mode_reglement_id'));
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// Bank account
	if ($action == 'setbankaccount' && $permissiontoadd) {
		$result = $object->setBankAccount(GETPOSTINT('fk_account'));
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// Delete social contribution
	if ($action == 'confirm_delete' && $permissiontodelete && $confirm == 'yes') {
		$totalpaid = $object->getSommePaiement();
		if (empty($totalpaid)) {
			$result = $object->delete($user);
			if ($result > 0) {
				header("Location: list.php");
				exit;
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
			}
		} else {
			setEventMessages($langs->trans('DisabledBecausePayments'), null, 'errors');
		}
	}


	// Add social contribution
	if ($action == 'add' && $permissiontoadd) {
		$amount = price2num(GETPOST('amount', 'alpha'), 'MT');

		if (!$dateech) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Date")), null, 'errors');
			$action = 'create';
		} elseif (!$dateperiod) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Period")), null, 'errors');
			$action = 'create';
		} elseif (!($actioncode > 0)) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Type")), null, 'errors');
			$action = 'create';
		} elseif (empty($amount)) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Amount")), null, 'errors');
			$action = 'create';
		} elseif (!is_numeric($amount)) {
			setEventMessages($langs->trans("ErrorFieldMustBeANumeric", $langs->transnoentities("Amount")), null, 'errors');
			$action = 'create';
		} else {
			$object->type = $actioncode;
			$object->label = GETPOST('label', 'alpha');
			$object->date_ech = $dateech;
			$object->periode = $dateperiod;
			$object->period = $dateperiod;
			$object->amount = $amount;
			$object->fk_user			= $fk_user;
			$object->mode_reglement_id = GETPOSTINT('mode_reglement_id');
			$object->fk_account = GETPOSTINT('fk_account');
			$object->fk_project = GETPOSTINT('fk_project');

			$id = $object->create($user);
			if ($id <= 0) {
				setEventMessages($object->error, $object->errors, 'errors');
				$action = 'create';
			}
		}
	}

	if ($action == 'update' && !$cancel && $permissiontoadd) {
		$amount = price2num(GETPOST('amount', 'alpha'), 'MT');

		if (!$dateech) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Date")), null, 'errors');
			$action = 'edit';
		} elseif (!$dateperiod) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Period")), null, 'errors');
			$action = 'edit';
		} elseif (empty($amount)) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Amount")), null, 'errors');
			$action = 'edit';
		} elseif (!is_numeric($amount)) {
			setEventMessages($langs->trans("ErrorFieldMustBeANumeric", $langs->transnoentities("Amount")), null, 'errors');
			$action = 'create';
		} else {
			$result = $object->fetch($id);

			$object->oldcopy = dol_clone($object, 2);

			$object->type = $actioncode;
			$object->date_ech = $dateech;
			$object->period = $dateperiod;
			$object->periode = $dateperiod;
			$object->amount = $amount;
			$object->fk_user = $fk_user;

			$result = $object->update($user);
			if ($result <= 0) {
				setEventMessages($object->error, $object->errors, 'errors');
			} else {
				// Reload object to get new value of some properties
				if ($object->oldcopy->type != $object->type) {
					$object->fetch($id);
				}
			}
		}
	}

	// Action clone object
	if ($action == 'confirm_clone' && $confirm != 'yes') {	// Test on permission not required here
		$action = '';
	}

	if ($action == 'confirm_clone' && $confirm == 'yes' && $permissiontoadd) {
		$db->begin();

		$originalId = $object->id;

		if ($object->id > 0) {
			$object->id = 0;
			$object->ref = '';
			$object->paye = 0;
			if (GETPOST('amount', 'alphanohtml')) {
				$object->amount = price2num(GETPOST('amount', 'alphanohtml'), 'MT', 2);
			}

			if (GETPOST('clone_label', 'alphanohtml')) {
				$object->label = GETPOST('clone_label', 'alphanohtml');
			} else {
				$object->label = $langs->trans("CopyOf").' '.$object->label;
			}

			if (GETPOSTINT('clone_for_next_month')) {	// This can be true only if TAX_ADD_CLONE_FOR_NEXT_MONTH_CHECKBOX has been set
				$object->period = dol_time_plus_duree($object->period, 1, 'm');
				$object->date_ech = dol_time_plus_duree($object->date_ech, 1, 'm');
			} else {
				// Note date_ech is often a little bit higher than dateperiod
				$newdateperiod = dol_mktime(0, 0, 0, GETPOSTINT('clone_periodmonth'), GETPOSTINT('clone_periodday'), GETPOSTINT('clone_periodyear'));
				$newdateech = dol_mktime(0, 0, 0, GETPOSTINT('clone_date_echmonth'), GETPOSTINT('clone_date_echday'), GETPOSTINT('clone_date_echyear'));
				if ($newdateperiod) {
					$object->period = $newdateperiod;
					if (empty($newdateech)) {
						$object->date_ech = $object->periode;
					}
				}
				if ($newdateech) {
					$object->date_ech = $newdateech;
					if (empty($newdateperiod)) {
						// TODO We can here get dol_get_last_day of previous month:
						// $object->period = dol_get_last_day(year of $object->date_ech - 1m, month or $object->date_ech -1m)
						$object->period = $object->date_ech;
					}
				}
			}

			$resultcheck = $object->check();
			if ($resultcheck) {
				$id = $object->create($user);
				if ($id > 0) {
					$db->commit();
					$db->close();

					header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
					exit;
				} else {
					$id = $originalId;
					$db->rollback();

					setEventMessages($object->error, $object->errors, 'errors');
				}
			}
		} else {
			$db->rollback();
			dol_print_error($db, $object->error);
		}
	}

	// Actions to build doc
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';
}


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formsocialcontrib = new FormSocialContrib($db);
$bankaccountstatic = new Account($db);
if (isModEnabled('project')) {
	$formproject = new FormProjets($db);
}

$now = dol_now();

$title = $langs->trans("SocialContribution").' - '.$langs->trans("Card");
$help_url = 'EN:Module_Taxes_and_social_contributions|FR:Module_Taxes_et_charges_spéciales|ES:M&oacute;dulo Impuestos y cargas sociales (IVA, impuestos)';
llxHeader("", $title, $help_url);


// Form to create a social contribution
if ($action == 'create') {
	print load_fiche_titre($langs->trans("NewSocialContribution"));

	print '<form name="charge" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';

	print dol_get_fiche_head();

	print '<table class="border centpercent tableforfieldcreate">';

	// Label
	print "<tr>";
	print '<td class="titlefieldcreate fieldrequired">';
	print $langs->trans("Label");
	print '</td>';
	print '<td><input type="text" name="label" class="flat minwidth300" value="'.dol_escape_htmltag(GETPOST('label', 'alpha')).'" autofocus></td>';
	print '</tr>';
	print '<tr>';

	// Type
	print '<td class="fieldrequired">';
	print $langs->trans("Type");
	print '</td>';
	print '<td>';
	$formsocialcontrib->select_type_socialcontrib(GETPOST("actioncode", 'alpha') ? GETPOST("actioncode", 'alpha') : '', 'actioncode', 1);
	print '</td>';
	print '</tr>';

	// Date
	print '<tr>';
	print '<td class="fieldrequired">';
	print $langs->trans("Date");
	print '</td>';
	print '<td>';
	print $form->selectDate(!empty($dateech) ? $dateech : '-1', 'ech', 0, 0, 0, 'charge', 1, 1);
	print '</td>';
	print "</tr>\n";

	// Date end period
	print '<tr>';
	print '<td class="fieldrequired">';
	print $form->textwithpicto($langs->trans("PeriodEndDate"), $langs->trans("LastDayTaxIsRelatedTo"));
	print '</td>';
	print '<td>';
	print $form->selectDate(!empty($dateperiod) ? $dateperiod : '-1', 'period', 0, 0, 0, 'charge', 1);
	print '</td>';
	print '</tr>';

	// Amount
	print '<tr>';
	print '<td class="fieldrequired">';
	print $langs->trans("Amount");
	print '</td>';
	print '<td><input type="text" size="6" name="amount" class="flat" value="'.dol_escape_htmltag(GETPOST('amount', 'alpha')).'"></td>';
	print '</tr>';

	// Employee
	print '<tr><td>';
	print $langs->trans('Employee');
	print '</td>';
	print '<td>'.img_picto('', 'user', 'class="pictofixedwidth"').$form->select_dolusers('', 'userid', 1).'</td></tr>';

	// Project
	if (isModEnabled('project')) {
		$formproject = new FormProjets($db);

		// Associated project
		$langs->load("projects");

		print '<tr><td>'.$langs->trans("Project").'</td><td>';

		print img_picto('', 'project', 'class="pictofixedwidth"').$formproject->select_projects(-1, $fk_project, 'fk_project', 0, 0, 1, 1, 0, 0, 0, '', 1);

		print '</td></tr>';
	}

	// Payment Mode
	print '<tr><td>'.$langs->trans('DefaultPaymentMode').'</td><td colspan="2">';
	$form->select_types_paiements(GETPOSTINT('mode_reglement_id'), 'mode_reglement_id');
	print '</td></tr>';

	// Bank Account
	if (isModEnabled("bank")) {
		print '<tr><td>'.$langs->trans('DefaultBankAccount').'</td><td colspan="2">';
		print img_picto('', 'bank_account', 'class="pictofixedwidth"').$form->select_comptes(GETPOSTINT('fk_account'), 'fk_account', 0, '', 2, '', 0, '', 1);
		print '</td></tr>';
	}

	print '</table>';

	print dol_get_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button button-add" value="'.$langs->trans("Add").'">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input type="button" class="button button-cancel" value="'.$langs->trans("Cancel").'" onClick="history.go(-1)">';
	print '</div>';

	print '</form>';
}

// View mode
if ($id > 0) {
	$formconfirm = '';

	if ($result > 0) {
		$head = tax_prepare_head($object);

		$totalpaid = $object->getSommePaiement();

		// Clone confirmation
		if ($action === 'clone') {
			$formquestion = array(
				array('type' => 'text', 'name' => 'clone_label', 'label' => $langs->trans("Label"), 'value' => $langs->trans("CopyOf").' '.$object->label, 'tdclass'=>'fieldrequired'),
			);
			if (getDolGlobalString('TAX_ADD_CLONE_FOR_NEXT_MONTH_CHECKBOX')) {
				$formquestion[] = array('type' => 'checkbox', 'name' => 'clone_for_next_month', 'label' => $langs->trans("CloneTaxForNextMonth"), 'value' => 1);
			} else {
				$formquestion[] = array('type' => 'date', 'datenow'=>1, 'name' => 'clone_date_ech', 'label' => $langs->trans("Date"), 'value' => -1);
				$formquestion[] = array('type' => 'date', 'name' => 'clone_period', 'label' => $langs->trans("PeriodEndDate"), 'value' => -1);
				$formquestion[] = array('type' => 'text', 'name' => 'amount', 'label' => $langs->trans("Amount"), 'value' => price($object->amount), 'morecss' => 'width100');
			}

			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneTax', $object->ref), 'confirm_clone', $formquestion, 'yes', 1, 280);
		}


		if ($action == 'paid') {
			$text = $langs->trans('ConfirmPaySocialContribution');
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id, $langs->trans('PaySocialContribution'), $text, "confirm_paid", '', '', 2);
		}

		// Confirmation of the removal of the Social Contribution
		if ($action == 'delete') {
			$text = $langs->trans('ConfirmDeleteSocialContribution');
			$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id, $langs->trans('DeleteSocialContribution'), $text, 'confirm_delete', '', '', 2);
		}

		if ($action == 'edit') {
			print '<form name="charge" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="POST">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="update">';
		}
		// Call Hook formConfirm
		$parameters = array('formConfirm' => $formconfirm, 'lineid' => $lineid);
		$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if (empty($reshook)) {
			$formconfirm .= $hookmanager->resPrint;
		} elseif ($reshook > 0) {
			$formconfirm = $hookmanager->resPrint;
		}


		print dol_get_fiche_head($head, 'card', $langs->trans("SocialContribution"), -1, 'bill', 0, '', '', 0, '', 1);

		// Print form confirm
		print $formconfirm;


		// Social contribution card
		$linkback = '<a href="'.DOL_URL_ROOT.'/compta/sociales/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

		$morehtmlref = '<div class="refidno">';
		// Ref customer
		$morehtmlref .= $form->editfieldkey("Label", 'lib', $object->label, $object, $user->hasRight('tax', 'charges', 'creer'), 'string', '', 0, 1);
		$morehtmlref .= $form->editfieldval("Label", 'lib', $object->label, $object, $user->hasRight('tax', 'charges', 'creer'), 'string', '', null, null, '', 1);

		// Employee
		if ($action != 'editfk_user') {
			if ($object->getSommePaiement() > 0 && $object->fk_user > 0) {
				$userstatic = new User($db);
				$result = $userstatic->fetch($object->fk_user);
				if ($result > 0) {
					$morehtmlref .= '<br>' .$langs->trans('Employee').' : '.$userstatic->getNomUrl(1);
				}
			} else {
				$morehtmlref .= '<br>' . $form->editfieldkey("Employee", 'fk_user', $object->label, $object, $user->hasRight('salaries', 'write'), 'string', '', 0, 1);
				if ($object->fk_user > 0) {
					$userstatic = new User($db);
					$result = $userstatic->fetch($object->fk_user);
					if ($result > 0) {
						$morehtmlref .= $userstatic->getNomUrl(1);
					} else {
						dol_print_error($db);
						exit();
					}
				}
			}
		} else {
			$morehtmlref .= '<br>'.$langs->trans('Employee').' :&nbsp;';
			$morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
			$morehtmlref .= '<input type="hidden" name="action" value="setfk_user">';
			$morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
			$morehtmlref .= $form->select_dolusers($object->fk_user, 'userid', 1);
			$morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
			$morehtmlref .= '</form>';
		}

		// Project
		if (isModEnabled('project')) {
			$langs->load("projects");
			$morehtmlref .= '<br>';
			if ($permissiontoadd) {
				$morehtmlref .= img_picto($langs->trans("Project"), 'project', 'class="pictofixedwidth"');
				if ($action != 'classify') {
					$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&token='.newToken().'&id='.((int) $object->id).'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> ';
				}
				$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, (!getDolGlobalString('PROJECT_CAN_ALWAYS_LINK_TO_ALL_SUPPLIERS') ? $object->socid : -1), $object->fk_project, ($action == 'classify' ? 'fk_project' : 'none'), 0, 0, 0, 1, '', 'maxwidth300');
			} else {
				if (!empty($object->fk_project)) {
					$proj = new Project($db);
					$proj->fetch($object->fk_project);
					$morehtmlref .= $proj->getNomUrl(1);
					if ($proj->title) {
						$morehtmlref .= '<span class="opacitymedium"> - '.dol_escape_htmltag($proj->title).'</span>';
					}
				}
			}
		}
		$morehtmlref .= '</div>';

		$morehtmlright = '';

		$object->totalpaid = $totalpaid; // To give a chance to dol_banner_tab to use already paid amount to show correct status

		dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref, '', 0, '', '', 0, $morehtmlright);

		print '<div class="fichecenter">';
		print '<div class="fichehalfleft">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border centpercent tableforfield">';

		// Type
		print '<tr><td class="titlefieldmiddle">';
		print $langs->trans("Type")."</td><td>";
		if ($action == 'edit' && $object->getSommePaiement() == 0) {
			$formsocialcontrib->select_type_socialcontrib(GETPOST("actioncode", 'alpha') ? GETPOST("actioncode", 'alpha') : $object->type, 'actioncode', 1);
		} else {
			print $object->type_label;
		}
		print "</td>";

		print "</tr>";

		// Date
		if ($action == 'edit') {
			print '<tr><td>'.$langs->trans("Date")."</td><td>";
			print $form->selectDate($object->date_ech, 'ech', 0, 0, 0, 'charge', 1, 1);
			print "</td></tr>";
		} else {
			print "<tr><td>".$langs->trans("Date")."</td><td>".dol_print_date($object->date_ech, 'day')."</td></tr>";
		}

		// Period end date
		print "<tr><td>".$form->textwithpicto($langs->trans("PeriodEndDate"), $langs->trans("LastDayTaxIsRelatedTo"))."</td>";
		print "<td>";
		if ($action == 'edit') {
			print $form->selectDate($object->period, 'period', 0, 0, 0, 'charge', 1);
		} else {
			print dol_print_date($object->period, "day");
		}
		print "</td></tr>";

		// Amount
		if ($action == 'edit') {
			print '<tr><td>'.$langs->trans("AmountTTC")."</td><td>";
			print '<input type="text" name="amount" size="12" class="flat" value="'.price($object->amount).'">';
			print "</td></tr>";
		} else {
			print '<tr><td>'.$langs->trans("AmountTTC").'</td><td><span class="amount">'.price($object->amount, 0, $langs, 1, -1, -1, $conf->currency).'</span></td></tr>';
		}

		// Mode of payment
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('DefaultPaymentMode');
		print '</td>';
		if ($action != 'editmode') {
			print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editmode&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->trans('SetMode'), 1).'</a></td>';
		}
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editmode') {
			$form->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$object->id, $object->mode_reglement_id, 'mode_reglement_id', '', 1, 1);
		} else {
			$form->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$object->id, $object->mode_reglement_id, 'none');
		}
		print '</td></tr>';

		// Bank account
		if (isModEnabled("bank")) {
			print '<tr><td class="nowrap">';
			print '<table class="centpercent nobordernopadding"><tr><td class="nowrap">';
			print $langs->trans('DefaultBankAccount');
			print '<td>';
			if ($action != 'editbankaccount' && $user->hasRight('tax', 'charges', 'creer')) {
				print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editbankaccount&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->trans('SetBankAccount'), 1).'</a></td>';
			}
			print '</tr></table>';
			print '</td><td>';
			if ($action == 'editbankaccount') {
				$form->formSelectAccount($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_account, 'fk_account', 1);
			} else {
				$form->formSelectAccount($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_account, 'none');
			}
			print '</td>';
			print '</tr>';
		}

		// Other attributes
		$parameters = array();
		$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;

		print '</table>';

		print '</div>';
		print '<div class="fichehalfright">';

		print '<div class="underbanner clearboth"></div>';

		$nbcols = 3;
		if (isModEnabled("bank")) {
			$nbcols++;
		}

		/*
		 * Payments
		 */
		$sql = "SELECT p.rowid, p.num_paiement as num_payment, p.datep as dp, p.amount,";
		$sql .= " c.code as type_code,c.libelle as paiement_type,";
		$sql .= ' ba.rowid as baid, ba.ref as baref, ba.label, ba.number as banumber, ba.account_number, ba.currency_code as bacurrency_code, ba.fk_accountancy_journal';
		$sql .= " FROM ".MAIN_DB_PREFIX."paiementcharge as p";
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank as b ON p.fk_bank = b.rowid';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank_account as ba ON b.fk_account = ba.rowid';
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as c ON p.fk_typepaiement = c.id";
		$sql .= ", ".MAIN_DB_PREFIX."chargesociales as cs";
		$sql .= " WHERE p.fk_charge = ".((int) $id);
		$sql .= " AND p.fk_charge = cs.rowid";
		$sql .= " AND cs.entity IN (".getEntity('sc').")";
		$sql .= " ORDER BY dp DESC";

		//print $sql;
		$resql = $db->query($sql);
		if ($resql) {
			$totalpaid = 0;

			$num = $db->num_rows($resql);
			$i = 0;
			$total = 0;

			print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
			print '<table class="noborder paymenttable">';
			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans("RefPayment").'</td>';
			print '<td>'.$langs->trans("Date").'</td>';
			print '<td>'.$langs->trans("Type").'</td>';
			if (isModEnabled("bank")) {
				print '<td class="liste_titre right">'.$langs->trans('BankAccount').'</td>';
			}
			print '<td class="right">'.$langs->trans("Amount").'</td>';
			print '</tr>';

			$paymentsocialcontributiontmp = new PaymentSocialContribution($db);

			if ($num > 0) {
				while ($i < $num) {
					$objp = $db->fetch_object($resql);

					$paymentsocialcontributiontmp->id = $objp->rowid;
					$paymentsocialcontributiontmp->ref = $objp->rowid;
					$paymentsocialcontributiontmp->datep = $db->jdate($objp->dp);

					print '<tr class="oddeven"><td>';
					print $paymentsocialcontributiontmp->getNomUrl(1);
					print '</td>';

					print '<td>'.dol_print_date($db->jdate($objp->dp), 'day')."</td>\n";
					$labeltype = $langs->trans("PaymentType".$objp->type_code) != "PaymentType".$objp->type_code ? $langs->trans("PaymentType".$objp->type_code) : $objp->paiement_type;
					print "<td>".$labeltype.' '.$objp->num_payment."</td>\n";
					if (isModEnabled("bank")) {
						$bankaccountstatic->id = $objp->baid;
						$bankaccountstatic->ref = $objp->baref;
						$bankaccountstatic->label = $objp->baref;
						$bankaccountstatic->number = $objp->banumber;
						$bankaccountstatic->currency_code = $objp->bacurrency_code;

						if (isModEnabled('accounting')) {
							$bankaccountstatic->account_number = $objp->account_number;

							$accountingjournal = new AccountingJournal($db);
							$accountingjournal->fetch($objp->fk_accountancy_journal);
							$bankaccountstatic->accountancy_journal = $accountingjournal->getNomUrl(0, 1, 1, '', 1);
						}

						print '<td class="right">';
						if ($bankaccountstatic->id) {
							print $bankaccountstatic->getNomUrl(1, 'transactions');
						}
						print '</td>';
					}
					print '<td class="right"><span class="amount">'.price($objp->amount)."</span></td>\n";
					print "</tr>";
					$totalpaid += $objp->amount;
					$i++;
				}
			} else {
				print '<tr class="oddeven"><td><span class="opacitymedium">'.$langs->trans("None").'</span></td>';
				print '<td></td><td></td><td></td><td></td>';
				print '</tr>';
			}

			print '<tr><td colspan="'.$nbcols.'" class="right">'.$langs->trans("AlreadyPaid").' :</td><td class="right nowraponall">'.price($totalpaid)."</td></tr>\n";
			print '<tr><td colspan="'.$nbcols.'" class="right">'.$langs->trans("AmountExpected").' :</td><td class="right nowraponall">'.price($object->amount)."</td></tr>\n";

			$resteapayer = $object->amount - $totalpaid;
			$cssforamountpaymentcomplete = 'amountpaymentcomplete';

			print '<tr><td colspan="'.$nbcols.'" class="right">'.$langs->trans("RemainderToPay")." :</td>";
			print '<td class="right nowraponall'.($resteapayer ? ' amountremaintopay' : (' '.$cssforamountpaymentcomplete)).'">'.price($resteapayer)."</td></tr>\n";

			print "</table>";
			print '</div>';

			$db->free($resql);
		} else {
			dol_print_error($db);
		}

		print '</div>';
		print '</div>';

		print '<div class="clearboth"></div>';

		print dol_get_fiche_end();

		if ($action == 'edit') {
			print $form->buttonsSaveCancel();

			print "</form>\n";
		}



		// Buttons for actions

		if ($action != 'edit') {
			print '<div class="tabsAction">'."\n";

			// Reopen
			if ($object->paye && $user->hasRight('tax', 'charges', 'creer')) {
				print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/compta/sociales/card.php?id='.$object->id.'&action=reopen&token='.newToken().'">'.$langs->trans("ReOpen").'</a></div>';
			}

			// Edit
			if ($object->paye == 0 && $user->hasRight('tax', 'charges', 'creer')) {
				print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/compta/sociales/card.php?id='.$object->id.'&action=edit&token='.newToken().'">'.$langs->trans("Modify").'</a></div>';
			}

			// Emit payment
			if ($object->paye == 0 && ((price2num($object->amount) < 0 && price2num($resteapayer, 'MT') < 0) || (price2num($object->amount) > 0 && price2num($resteapayer, 'MT') > 0)) && $user->hasRight('tax', 'charges', 'creer')) {
				print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/compta/paiement_charge.php?id='.$object->id.'&action=create&token='.newToken().'">'.$langs->trans("DoPayment")."</a></div>";
			}

			// Classify 'paid'
			if ($object->paye == 0 && round($resteapayer) <= 0 && $user->hasRight('tax', 'charges', 'creer')) {
				print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/compta/sociales/card.php?id='.$object->id.'&action=paid&token='.newToken().'">'.$langs->trans("ClassifyPaid").'</a></div>';
			}

			// Clone
			if ($user->hasRight('tax', 'charges', 'creer')) {
				print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/compta/sociales/card.php?id='.$object->id.'&action=clone&token='.newToken().'">'.$langs->trans("ToClone")."</a></div>";
			}

			// Delete
			if ($user->hasRight('tax', 'charges', 'supprimer') && empty($totalpaid)) {
				print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.DOL_URL_ROOT.'/compta/sociales/card.php?id='.$object->id.'&action=delete&token='.newToken().'">'.$langs->trans("Delete").'</a></div>';
			} else {
				print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#" title="'.(dol_escape_htmltag($langs->trans("DisabledBecausePayments"))).'">'.$langs->trans("Delete").'</a></div>';
			}

			print "</div>";
		}


		// Select mail models is same action as presend
		if (GETPOST('modelselected')) {
			$action = 'presend';
		}

		if ($action != 'presend') {
			print '<div class="fichecenter"><div class="fichehalfleft">';
			print '<a name="builddoc"></a>'; // ancre

			$includedocgeneration = 1;

			// Documents
			if ($includedocgeneration) {
				$objref = dol_sanitizeFileName($object->ref);
				$relativepath = $objref.'/'.$objref.'.pdf';
				$filedir = $conf->tax->dir_output.'/'.$objref;
				$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
				$genallowed = 0;
				$delallowed = $user->hasRight('tax', 'charges', 'creer'); // If you can create/edit, you can remove a file on card
				print $formfile->showdocuments('tax', $objref, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', $langs->defaultlang);
			}

			// Show links to link elements
			//$linktoelem = $form->showLinkToObjectBlock($object, null, array('myobject'));
			//$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);


			print '</div><div class="fichehalfright">';

			/*
			 $MAXEVENT = 10;

			 $morehtmlcenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', dol_buildpath('/mymodule/myobject_agenda.php', 1).'?id='.$object->id);

			 // List of actions on element
			 include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
			 $formactions = new FormActions($db);
			 $somethingshown = $formactions->showactions($object, $object->element.'@'.$object->module, (is_object($object->thirdparty) ? $object->thirdparty->id : 0), 1, '', $MAXEVENT, '', $morehtmlcenter);
			 */

			print '</div></div>';
		}

		//Select mail models is same action as presend
		if (GETPOST('modelselected')) {
			$action = 'presend';
		}

		// Presend form
		$modelmail = 'sc';
		$defaulttopic = 'InformationMessage';
		$diroutput = $conf->tax->dir_output;
		$trackid = 'sc'.$object->id;

		include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
	} else {
		/* Social contribution not found */
		dol_print_error(null, $object->error);
	}
}

// End of page
llxFooter();
$db->close();
