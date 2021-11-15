<?php
/* Copyright (C) 2002-2004  Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003       Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2015  Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009  Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2013       Peter Fontaine       <contact@peterfontaine.fr>
 * Copyright (C) 2015-2016  Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2015       Alexandre Spangaro   <aspangaro@open-dsi.fr>
 * Copyright (C) 2021       Gauthier VERDOL      <gauthier.verdol@atm-consulting.fr>
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
 *	    \file       htdocs/user/bank.php
 *      \ingroup    HRM
 *		\brief      Tab for HRM
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/userbankaccount.class.php';
if (!empty($conf->holiday->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/holiday/class/holiday.class.php';
}
if (!empty($conf->expensereport->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
}
if (!empty($conf->salaries->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/salaries/class/salary.class.php';
	require_once DOL_DOCUMENT_ROOT.'/salaries/class/paymentsalary.class.php';
}

// Load translation files required by page
$langs->loadLangs(array('companies', 'commercial', 'banks', 'bills', 'trips', 'holiday', 'salaries'));

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alphanohtml');
$bankid = GETPOST('bankid', 'int');
$action = GETPOST("action", 'alpha');
$cancel = GETPOST('cancel', 'alpha');

// Security check
$socid = 0;
if ($user->socid > 0) {
	$socid = $user->socid;
}
$feature2 = (($socid && $user->rights->user->self->creer) ? '' : 'user');

$object = new User($db);
if ($id > 0 || !empty($ref)) {
	$result = $object->fetch($id, $ref, '', 1);
	$object->getrights();
}

$account = new UserBankAccount($db);
if (!$bankid) {
	$account->fetch(0, '', $id);
} else {
	$account->fetch($bankid);
}
if (empty($account->userid)) {
	$account->userid = $object->id;
}


// Define value to know what current user can do on users
$canadduser = (!empty($user->admin) || $user->rights->user->user->creer);
$canreaduser = (!empty($user->admin) || $user->rights->user->user->lire);
$permissiontoaddbankaccount = (!empty($user->rights->salaries->write) || !empty($user->rights->hrm->employee->write) || !empty($user->rights->user->creer));

// Ok if user->rights->salaries->read or user->rights->hrm->read
//$result = restrictedArea($user, 'salaries|hrm', $id, 'user&user', $feature2);
$ok = false;
if ($user->id == $id) {
	$ok = true; // A user can always read its own card
}
if (!empty($user->rights->salaries->read)) {
	$ok = true;
}
if (!empty($user->rights->hrm->read)) {
	$ok = true;
}
if (!empty($user->rights->expensereport->lire) && ($user->id == $object->id || $user->rights->expensereport->readall)) {
	$ok = true;
}
if (!$ok) {
	accessforbidden();
}


/*
 *	Actions
 */

if ($action == 'add' && !$cancel && $permissiontoaddbankaccount) {
	$account->userid          = $object->id;

	$account->bank            = GETPOST('bank', 'alpha');
	$account->label           = GETPOST('label', 'alpha');
	$account->courant         = GETPOST('courant', 'alpha');
	$account->code_banque     = GETPOST('code_banque', 'alpha');
	$account->code_guichet    = GETPOST('code_guichet', 'alpha');
	$account->number          = GETPOST('number', 'alpha');
	$account->cle_rib         = GETPOST('cle_rib', 'alpha');
	$account->bic             = GETPOST('bic', 'alpha');
	$account->iban            = GETPOST('iban', 'alpha');
	$account->domiciliation   = GETPOST('domiciliation', 'alpha');
	$account->proprio         = GETPOST('proprio', 'alpha');
	$account->owner_address   = GETPOST('owner_address', 'alpha');

	$result = $account->create($user);

	if (!$result) {
		setEventMessages($account->error, $account->errors, 'errors');
		$action = 'edit'; // Force chargement page edition
	} else {
		setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
		$action = '';
	}
}

if ($action == 'update' && !$cancel && $permissiontoaddbankaccount) {
	$account->userid = $object->id;

	/*
	if ($action == 'update' && !$cancel)
	{
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		if ($canedituser)    // Case we can edit all field
		{
			$error = 0;

			if (!$error)
			{
				$objectuser->fetch($id);

				$objectuser->oldcopy = clone $objectuser;

				$db->begin();

				$objectuser->default_range = GETPOST('default_range');
				$objectuser->default_c_exp_tax_cat = GETPOST('default_c_exp_tax_cat');

				if (!$error) {
					$ret = $objectuser->update($user);
					if ($ret < 0) {
						$error++;
						if ($db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
							$langs->load("errors");
							setEventMessages($langs->trans("ErrorLoginAlreadyExists", $objectuser->login), null, 'errors');
						} else {
							setEventMessages($objectuser->error, $objectuser->errors, 'errors');
						}
					}
				}

				if (!$error && !count($objectuser->errors)) {
					setEventMessages($langs->trans("UserModified"), null, 'mesgs');
					$db->commit();
				} else {
					$db->rollback();
				}
			}
		}
	}*/

	$account->bank            = GETPOST('bank', 'alpha');
	$account->label           = GETPOST('label', 'alpha');
	$account->courant         = GETPOST('courant', 'alpha');
	$account->code_banque     = GETPOST('code_banque', 'alpha');
	$account->code_guichet    = GETPOST('code_guichet', 'alpha');
	$account->number          = GETPOST('number', 'alpha');
	$account->cle_rib         = GETPOST('cle_rib', 'alpha');
	$account->bic             = GETPOST('bic', 'alpha');
	$account->iban            = GETPOST('iban', 'alpha');
	$account->domiciliation   = GETPOST('domiciliation', 'alpha');
	$account->proprio         = GETPOST('proprio', 'alpha');
	$account->owner_address   = GETPOST('owner_address', 'alpha');

	$result = $account->update($user);

	if (!$result) {
		setEventMessages($account->error, $account->errors, 'errors');
		$action = 'edit'; // Force chargement page edition
	} else {
		setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
		$action = '';
	}
}

// update personal email
if ($action == 'setpersonal_email' && $canadduser) {
	$object->personal_email = (string) GETPOST('personal_email', 'alphanohtml');
	$result = $object->update($user);
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

// update personal mobile
if ($action == 'setpersonal_mobile' && $canadduser) {
	$object->personal_mobile = (string) GETPOST('personal_mobile', 'alphanohtml');
	$result = $object->update($user);
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

if (!empty($conf->global->MAIN_USE_EXPENSE_IK)) {
	// update default_c_exp_tax_cat
	if ($action == 'setdefault_c_exp_tax_cat' && $canadduser) {
		$object->default_c_exp_tax_cat = GETPOST('default_c_exp_tax_cat', 'int');
		$result = $object->update($user);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// update default range
	if ($action == 'setdefault_range' && $canadduser) {
		$object->default_range = GETPOST('default_range', 'int');
		$result = $object->update($user);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
}


/*
 *	View
 */

$form = new Form($db);

$childids = $user->getAllChildIds(1);

llxHeader(null, $langs->trans("BankAccounts"));

$head = user_prepare_head($object);

if ($id && $bankid && $action == 'edit' && $user->rights->user->user->creer) {
	print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'" method="post">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="'.GETPOST("id", 'int').'">';
	print '<input type="hidden" name="bankid" value="'.$bankid.'">';
}
if ($id && $action == 'create' && $user->rights->user->user->creer) {
	print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'" method="post">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="bankid" value="'.$bankid.'">';
}


// View
if ($action != 'edit' && $action != 'create') {		// If not bank account yet, $account may be empty
	$title = $langs->trans("User");
	print dol_get_fiche_head($head, 'bank', $title, -1, 'user');

	$linkback = '';

	if ($user->rights->user->user->lire || $user->admin) {
		$linkback = '<a href="'.DOL_URL_ROOT.'/user/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
	}

	dol_banner_tab($object, 'id', $linkback, $user->rights->user->user->lire || $user->admin);

	print '<div class="fichecenter"><div class="fichehalfleft">';

	print '<div class="underbanner clearboth"></div>';

	print '<table class="border centpercent tableforfield">';

	print '<tr><td class="titlefield">'.$langs->trans("Login").'</td>';
	print '<td>'.$object->login.'</td>';
	print '</tr>';

	print '<tr class="nowrap">';
	print '<td>';
	print $form->editfieldkey("UserPersonalEmail", 'personal_email', $object->personal_email, $object, $user->rights->user->user->creer);
	print '</td><td>';
	print $form->editfieldval("UserPersonalEmail", 'personal_email', $object->personal_email, $object, $user->rights->user->user->creer, 'email', ($object->personal_email != '' ? dol_print_email($object->personal_email) : ''));
	print '</td>';
	print '</tr>';

	print '<tr class="nowrap">';
	print '<td>';
	print $form->editfieldkey("UserPersonalMobile", 'personal_mobile', $object->personal_mobile, $object, $user->rights->user->user->creer);
	print '</td><td>';
	print $form->editfieldval("UserPersonalMobile", 'personal_mobile', $object->personal_mobile, $object, $user->rights->user->user->creer, 'string', ($object->personal_mobile != '' ? dol_print_phone($object->personal_mobile) : ''));
	print '</td>';
	print '</tr>';

	if (!empty($conf->global->MAIN_USE_EXPENSE_IK)) {
		print '<tr class="nowrap">';
		print '<td>';
		print $form->editfieldkey("DefaultCategoryCar", 'default_c_exp_tax_cat', $object->default_c_exp_tax_cat, $object, $user->rights->user->user->creer);
		print '</td><td>';
		if ($action == 'editdefault_c_exp_tax_cat') {
			$ret = '<form method="post" action="'.$_SERVER["PHP_SELF"].($moreparam ? '?'.$moreparam : '').'">';
			$ret .= '<input type="hidden" name="action" value="setdefault_c_exp_tax_cat">';
			$ret .= '<input type="hidden" name="token" value="'.newToken().'">';
			$ret .= '<input type="hidden" name="id" value="'.$object->id.'">';
			$ret .= $form->selectExpenseCategories($object->default_c_exp_tax_cat, 'default_c_exp_tax_cat', 1);
			$ret .= '<input type="submit" class="button" name="modify" value="'.$langs->trans("Modify").'"> ';
			$ret .= '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
			$ret .= '</form>';
			print $ret;
		} else {
			$label_exp_tax_cat = dol_getIdFromCode($db, $object->default_c_exp_tax_cat, 'c_exp_tax_cat', 'rowid', 'label');
			print $langs->trans($label_exp_tax_cat);
			//print $form->editfieldval("DefaultCategoryCar", 'default_c_exp_tax_cat', $object->default_c_exp_tax_cat, $object, $user->rights->user->user->creer, 'string', ($object->default_c_exp_tax_cat != '' ? $object->default_c_exp_tax_cat : ''));
		}
		print '</td>';
		print '</tr>';

		print '<tr class="nowrap">';
		print '<td>';
		print $form->editfieldkey("DefaultRangeNumber", 'default_range', $object->default_range, $object, $user->rights->user->user->creer);
		print '</td><td>';
		if ($action == 'editdefault_range') {
			$ret = '<form method="post" action="'.$_SERVER["PHP_SELF"].($moreparam ? '?'.$moreparam : '').'">';
			$ret .= '<input type="hidden" name="action" value="setdefault_range">';
			$ret .= '<input type="hidden" name="token" value="'.newToken().'">';
			$ret .= '<input type="hidden" name="id" value="'.$object->id.'">';
			$maxRangeNum = ExpenseReportIk::getMaxRangeNumber($object->default_c_exp_tax_cat);
			$ret .= $form->selectarray('default_range', range(0, $maxRangeNum), $object->default_range);
			$ret .= '<input type="submit" class="button" name="modify" value="'.$langs->trans("Modify").'"> ';
			$ret .= '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
			$ret .= '</form>';
			print $ret;
		} else {
			print $object->default_range;
		}
		print '</td>';
		print '</tr>';
	}

	print '</table>';

	print '</div><div class="fichehalfright"><div class="ficheaddleft">';

	// Max number of elements in small lists
	$MAXLIST = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;

	// Latest payments of salaries
	if (!empty($conf->salaries->enabled) &&
		(($user->rights->salaries->read && (in_array($object->id, $childids) || $object->id == $user->id)) || (!empty($user->rights->salaries->readall)))
		) {
		$payment_salary = new PaymentSalary($db);
		$salary = new Salary($db);

		$sql = "SELECT s.rowid as sid, s.ref as sref, s.label, s.datesp, s.dateep, s.paye, s.amount, SUM(ps.amount) as alreadypaid";
		$sql .= " FROM ".MAIN_DB_PREFIX."salary as s";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."payment_salary as ps ON (s.rowid = ps.fk_salary)";
		$sql .= " WHERE s.fk_user = ".$object->id;
		$sql .= " AND s.entity IN (".getEntity('salary').")";
		$sql .= " GROUP BY s.rowid, s.ref, s.label, s.datesp, s.dateep, s.paye, s.amount";
		$sql .= " ORDER BY s.dateep DESC";

		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);

			print '<table class="noborder centpercent">';

			print '<tr class="liste_titre">';
			print '<td colspan="5"><table class="nobordernopadding centpercent"><tr><td>'.$langs->trans("LastSalaries", ($num <= $MAXLIST ? "" : $MAXLIST)).'</td><td class="right"><a class="notasortlink" href="'.DOL_URL_ROOT.'/salaries/list.php?search_user='.$object->login.'">'.$langs->trans("AllSalaries").'<span class="badge marginleftonlyshort">'.$num.'</span></a></td>';
			print '</tr></table></td>';
			print '</tr>';

			$i = 0;
			while ($i < $num && $i < $MAXLIST) {
				$objp = $db->fetch_object($resql);

				$salary->id = $objp->sid;
				$salary->ref = $objp->sref ? $objp->sref : $objp->sid;
				$salary->label = $objp->label;
				$salary->datesp = $db->jdate($objp->datesp);
				$salary->dateep = $db->jdate($objp->dateep);
				$salary->paye = $objp->paye;
				$salary->amount = $objp->amount;

				$payment_salary->id = $objp->rowid;
				$payment_salary->ref = $objp->ref;
				$payment_salary->datep = $db->jdate($objp->datep);

				print '<tr class="oddeven">';
				print '<td class="nowraponall">';
				print $salary->getNomUrl(1);
				print '</td>';

				print '<td class="right" width="80px">'.dol_print_date($db->jdate($objp->datesp), 'day')."</td>\n";
				print '<td class="right" width="80px">'.dol_print_date($db->jdate($objp->dateep), 'day')."</td>\n";
				print '<td class="right" class="nowraponall"><span class="amount">'.price($objp->amount).'</span></td>';
				print '<td class="right" class="nowraponall">'.$salary->getLibStatut(5, $objp->alreadypaid).'</td>';

				print '</tr>';
				$i++;
			}
			$db->free($resql);

			if ($num <= 0) {
				print '<td colspan="5" class="opacitymedium">'.$langs->trans("None").'</a>';
			}
			print "</table>";
		} else {
			dol_print_error($db);
		}
	}

	// Latest leave requests
	if (!empty($conf->holiday->enabled) &&
		($user->rights->holiday->readall || ($user->rights->holiday->read && $object->id == $user->id))
		) {
		$holiday = new Holiday($db);

		$sql = "SELECT h.rowid, h.statut as status, h.fk_type, h.date_debut, h.date_fin, h.halfday";
		$sql .= " FROM ".MAIN_DB_PREFIX."holiday as h";
		$sql .= " WHERE h.fk_user = ".$object->id;
		$sql .= " AND h.entity IN (".getEntity('holiday').")";
		$sql .= " ORDER BY h.date_debut DESC";

		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);

			print '<table class="noborder centpercent">';

			print '<tr class="liste_titre">';
			print '<td colspan="4"><table class="nobordernopadding centpercent"><tr><td>'.$langs->trans("LastHolidays", ($num <= $MAXLIST ? "" : $MAXLIST)).'</td><td class="right"><a class="notasortlink" href="'.DOL_URL_ROOT.'/holiday/list.php?id='.$object->id.'">'.$langs->trans("AllHolidays").'<span class="badge marginleftonlyshort">'.$num.'</span></a></td>';
			print '</tr></table></td>';
			print '</tr>';

			$i = 0;
			while ($i < $num && $i < $MAXLIST) {
				$objp = $db->fetch_object($resql);

				$holiday->id = $objp->rowid;
				$holiday->ref = $objp->rowid;
				$holiday->fk_type = $objp->fk_type;
				$holiday->statut = $objp->status;
				$nbopenedday = num_open_day($db->jdate($objp->date_debut), $db->jdate($objp->date_fin), 0, 1, $objp->halfday);

				print '<tr class="oddeven">';
				print '<td class="nowrap">';
				print $holiday->getNomUrl(1);
				print '</td><td class="right" width="80px">'.dol_print_date($db->jdate($objp->date_debut), 'day')."</td>\n";
				print '<td class="right" style="min-width: 60px">'.$nbopenedday.' '.$langs->trans('DurationDays').'</td>';
				print '<td class="right" style="min-width: 60px" class="nowrap">'.$holiday->LibStatut($objp->status, 5).'</td></tr>';
				$i++;
			}
			$db->free($resql);

			if ($num <= 0) {
				print '<td colspan="4" class="opacitymedium">'.$langs->trans("None").'</a>';
			}
			print "</table>";
		} else {
			dol_print_error($db);
		}
	}

	// Latest expense report
	if (!empty($conf->expensereport->enabled) &&
		($user->rights->expensereport->readall || ($user->rights->expensereport->lire && $object->id == $user->id))
		) {
		$exp = new ExpenseReport($db);

		$sql = "SELECT e.rowid, e.ref, e.fk_statut as status, e.date_debut, e.total_ttc";
		$sql .= " FROM ".MAIN_DB_PREFIX."expensereport as e";
		$sql .= " WHERE e.fk_user_author = ".$object->id;
		$sql .= " AND e.entity = ".$conf->entity;
		$sql .= " ORDER BY e.date_debut DESC";

		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);

			print '<table class="noborder centpercent">';

			print '<tr class="liste_titre">';
			print '<td colspan="4"><table class="nobordernopadding centpercent"><tr><td>'.$langs->trans("LastExpenseReports", ($num <= $MAXLIST ? "" : $MAXLIST)).'</td><td class="right"><a class="notasortlink" href="'.DOL_URL_ROOT.'/expensereport/list.php?id='.$object->id.'">'.$langs->trans("AllExpenseReports").'<span class="badge marginleftonlyshort">'.$num.'</span></a></td>';
			print '</tr></table></td>';
			print '</tr>';

			$i = 0;
			while ($i < $num && $i < $MAXLIST) {
				$objp = $db->fetch_object($resql);

				$exp->id = $objp->rowid;
				$exp->ref = $objp->ref;
				$exp->status = $objp->status;

				print '<tr class="oddeven">';
				print '<td class="nowrap">';
				print $exp->getNomUrl(1);
				print '</td><td class="right" width="80px">'.dol_print_date($db->jdate($objp->date_debut), 'day')."</td>\n";
				print '<td class="right" style="min-width: 60px"><span class="amount">'.price($objp->total_ttc).'</span></td>';
				print '<td class="right nowrap" style="min-width: 60px">'.$exp->LibStatut($objp->status, 5).'</td></tr>';
				$i++;
			}
			$db->free($resql);

			if ($num <= 0) {
				print '<td colspan="4" class="opacitymedium">'.$langs->trans("None").'</a>';
			}
			print "</table>";
		} else {
			dol_print_error($db);
		}
	}

	print '</div></div></div>';
	print '<div style="clear:both"></div>';

	print dol_get_fiche_end();

	// List of bank accounts (Currently only one bank account possible for each employee)

	$morehtmlright = '';
	if ($account->id == 0) {
		if ($permissiontoaddbankaccount) {
			$morehtmlright = dolGetButtonTitle($langs->trans('Add'), '', 'fa fa-plus-circle', $_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=create');
		} else {
			$morehtmlright = dolGetButtonTitle($langs->trans('Add'), 'NotEnoughPermission', 'fa fa-plus-circle', '', '', -2);
		}
	} else {
		$morehtmlright = dolGetButtonTitle($langs->trans('Add'), 'AlreadyOneBankAccount', 'fa fa-plus-circle', '', '', -2);
	}

	print load_fiche_titre($langs->trans("BankAccounts"), $morehtmlright, 'bank_account');

	print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
	print '<table class="liste centpercent">';

	print '<tr class="liste_titre">';
	print_liste_field_titre("LabelRIB");
	print_liste_field_titre("Bank");
	print_liste_field_titre("RIB");
	print_liste_field_titre("IBAN");
	print_liste_field_titre("BIC");
	print_liste_field_titre('', $_SERVER["PHP_SELF"], "", '', '', '', '', '', 'maxwidthsearch ');
	print "</tr>\n";

	if ($account->id > 0) {
		print '<tr class="oddeven">';
		// Label
		print '<td>'.$account->label.'</td>';
		// Bank name
		print '<td>'.$account->bank.'</td>';
		// Account number
		print '<td>';
		$string = '';
		foreach ($account->getFieldsToShow() as $val) {
			if ($val == 'BankCode') {
				$string .= $account->code_banque.' ';
			} elseif ($val == 'BankAccountNumber') {
				$string .= $account->number.' ';
			} elseif ($val == 'DeskCode') {
				$string .= $account->code_guichet.' ';
			} elseif ($val == 'BankAccountNumberKey') {
				$string .= $account->cle_rib.' ';
			}
		}
		if (!empty($account->label) && $account->number) {
			if (!checkBanForAccount($account)) {
				$string .= ' '.img_picto($langs->trans("ValueIsNotValid"), 'warning');
			} else {
				$string .= ' '.img_picto($langs->trans("ValueIsValid"), 'info');
			}
		}

		print $string;
		print '</td>';
		// IBAN
		print '<td>'.$account->iban;
		if (!empty($account->iban)) {
			if (!checkIbanForAccount($account)) {
				print ' '.img_picto($langs->trans("IbanNotValid"), 'warning');
			}
		}
		print '</td>';
		// BIC
		print '<td>'.$account->bic;
		if (!empty($account->bic)) {
			if (!checkSwiftForAccount($account)) {
				print ' '.img_picto($langs->trans("SwiftNotValid"), 'warning');
			}
		}
		print '</td>';

		// Edit/Delete
		print '<td class="right nowraponall">';
		if ($permissiontoaddbankaccount) {
			print '<a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&bankid='.$account->id.'&action=edit">';
			print img_picto($langs->trans("Modify"), 'edit');
			print '</a>';
		}
		print '</td>';

		print '</tr>';
	}


	if ($account->id == 0) {
		$colspan = 6;
		print '<tr><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("NoBANRecord").'</td></tr>';
	}

	print '</table>';
	print '</div>';
}

// Edit
if ($id && ($action == 'edit' || $action == 'create') && $user->rights->user->user->creer) {
	$title = $langs->trans("User");
	print dol_get_fiche_head($head, 'bank', $title, 0, 'user');

	$linkback = '<a href="'.DOL_URL_ROOT.'/user/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	dol_banner_tab($object, 'id', $linkback, $user->rights->user->user->lire || $user->admin);

	//print '<div class="fichecenter">';

	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent">';

	print '<tr><td class="titlefield fieldrequired">'.$langs->trans("LabelRIB").'</td>';
	print '<td colspan="4"><input size="30" type="text" name="label" value="'.$account->label.'"></td></tr>';

	print '<tr><td class="fieldrequired">'.$langs->trans("BankName").'</td>';
	print '<td><input size="30" type="text" name="bank" value="'.$account->bank.'"></td></tr>';

	// Show fields of bank account
	foreach ($account->getFieldsToShow() as $val) {
		if ($val == 'BankCode') {
			$name = 'code_banque';
			$size = 8;
			$content = $account->code_banque;
		} elseif ($val == 'DeskCode') {
			$name = 'code_guichet';
			$size = 8;
			$content = $account->code_guichet;
		} elseif ($val == 'BankAccountNumber') {
			$name = 'number';
			$size = 18;
			$content = $account->number;
		} elseif ($val == 'BankAccountNumberKey') {
			$name = 'cle_rib';
			$size = 3;
			$content = $account->cle_rib;
		}

		print '<td>'.$langs->trans($val).'</td>';
		print '<td><input size="'.$size.'" type="text" class="flat" name="'.$name.'" value="'.$content.'"></td>';
		print '</tr>';
	}

	// IBAN
	print '<tr><td class="fieldrequired">'.$langs->trans("IBAN").'</td>';
	print '<td colspan="4"><input size="30" type="text" name="iban" value="'.$account->iban.'"></td></tr>';

	print '<tr><td class="fieldrequired">'.$langs->trans("BIC").'</td>';
	print '<td colspan="4"><input size="12" type="text" name="bic" value="'.$account->bic.'"></td></tr>';

	print '<tr><td class="tdtop">'.$langs->trans("BankAccountDomiciliation").'</td><td colspan="4">';
	print '<textarea name="domiciliation" rows="4" class="quatrevingtpercent">';
	print $account->domiciliation;
	print "</textarea></td></tr>";

	print '<tr><td>'.$langs->trans("BankAccountOwner").'</td>';
	print '<td colspan="4"><input size="30" type="text" name="proprio" value="'.$account->proprio.'"></td></tr>';
	print "</td></tr>\n";

	print '<tr><td class="tdtop">'.$langs->trans("BankAccountOwnerAddress").'</td><td colspan="4">';
	print '<textarea name="owner_address" rows="4" class="quatrevingtpercent">';
	print $account->owner_address;
	print "</textarea></td></tr>";

	print '</table>';

	//print '</div>';

	print dol_get_fiche_end();

	print '<div class="center">';
	print '<input class="button" value="'.$langs->trans("Modify").'" type="submit">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'" type="submit">';
	print '</div>';
}

if ($id && $action == 'edit' && $user->rights->user->user->creer) {
	print '</form>';
}

if ($id && $action == 'create' && $user->rights->user->user->creer) {
	print '</form>';
}

// End of page
llxFooter();
$db->close();
