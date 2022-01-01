<?php
/* Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2013      Peter Fontaine       <contact@peterfontaine.fr>
 * Copyright (C) 2015-2016 Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2015	   Alexandre Spangaro	<aspangaro@open-dsi.fr>
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
if (!empty($conf->holiday->enabled)) require_once DOL_DOCUMENT_ROOT.'/holiday/class/holiday.class.php';
if (!empty($conf->expensereport->enabled)) require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
if (!empty($conf->salaries->enabled)) require_once DOL_DOCUMENT_ROOT.'/salaries/class/paymentsalary.class.php';

// Load translation files required by page
$langs->loadLangs(array('companies', 'commercial', 'banks', 'bills', 'trips', 'holiday', 'salaries'));

$id = GETPOST('id', 'int');
$bankid = GETPOST('bankid', 'int');
$action = GETPOST("action", 'alpha');
$cancel = GETPOST('cancel', 'alpha');

// Security check
$socid = 0;
if ($user->socid > 0) $socid = $user->socid;
$feature2 = (($socid && $user->rights->user->self->creer) ? '' : 'user');
// Ok if user->rights->salaries->read or user->rights->hrm->read
//$result = restrictedArea($user, 'salaries|hrm', $id, 'user&user', $feature2);
$ok = false;
if ($user->id == $id) $ok = true; // A user can always read its own card
if (!empty($user->rights->salaries->read)) $ok = true;
if (!empty($user->rights->hrm->read)) $ok = true;
if (!empty($user->rights->expensereport->lire) && ($user->id == $object->id || $user->rights->expensereport->readall)) $ok = true;
if (!$ok)
{
	accessforbidden();
}

$object = new User($db);
if ($id > 0 || !empty($ref))
{
	$result = $object->fetch($id, $ref, '', 1);
	$object->getrights();
}

$account = new UserBankAccount($db);
if (!$bankid)
{
	$account->fetch(0, '', $id);
}
else
{
	$account->fetch($bankid);
}
if (empty($account->userid)) $account->userid = $object->id;


/*
 *	Actions
 */

if ($action == 'add' && !$cancel)
{
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

	if (!$result)
	{
		setEventMessages($account->error, $account->errors, 'errors');
		$action = 'edit'; // Force chargement page edition
	}
	else
	{
        setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
        $action = '';
	}
}

if ($action == 'update' && !$cancel)
{
    $account->userid = $object->id;

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

    if (!$result)
	{
		setEventMessages($account->error, $account->errors, 'errors');
		$action = 'edit'; // Force chargement page edition
	}
	else
	{
        setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
        $action = '';
    }
}

// update personal email
if ($action == 'setpersonal_email')
{
	$object->personal_email = GETPOST('personal_email');
	$result = $object->update($user);
	if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
}

// update personal mobile
if ($action == 'setpersonal_mobile')
{
	$object->personal_mobile = GETPOST('personal_mobile');
	$result = $object->update($user);
	if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
}


/*
 *	View
 */

$childids = $user->getAllChildIds(1);

llxHeader(null, $langs->trans("BankAccounts"));

$head = user_prepare_head($object);

if ($id && $bankid && $action == 'edit' && $user->rights->user->user->creer)
{
    print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'" method="post">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="update">';
    print '<input type="hidden" name="id" value="'.GETPOST("id", 'int').'">';
    print '<input type="hidden" name="bankid" value="'.$bankid.'">';
}
if ($id && $action == 'create' && $user->rights->user->user->creer)
{
    print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'" method="post">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="add">';
    print '<input type="hidden" name="bankid" value="'.$bankid.'">';
}


// View
if ($action != 'edit' && $action != 'create')		// If not bank account yet, $account may be empty
{
	$title = $langs->trans("User");
	dol_fiche_head($head, 'bank', $title, -1, 'user');

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

    print '</table>';

	print '</div><div class="fichehalfright"><div class="ficheaddleft">';

	// Nbre max d'elements des petites listes
	$MAXLIST = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;

	/*
	 * Latest salary payments
	 */
	if (!empty($conf->salaries->enabled) &&
		$user->rights->salaries->read && (in_array($object->id, $childids) || $object->id == $user->id)
		)
	{
		$salary = new PaymentSalary($db);

		$sql = "SELECT ps.rowid, ps.datesp, ps.dateep, ps.amount";
		$sql .= " FROM ".MAIN_DB_PREFIX."payment_salary as ps";
		$sql .= " WHERE ps.fk_user = ".$object->id;
		$sql .= " AND ps.entity = ".$conf->entity;
		$sql .= " ORDER BY ps.datesp DESC";

		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);

	        print '<table class="noborder centpercent">';

            print '<tr class="liste_titre">';
   			print '<td colspan="4"><table width="100%" class="nobordernopadding"><tr><td>'.$langs->trans("LastSalaries", ($num <= $MAXLIST ? "" : $MAXLIST)).'</td><td class="right"><a class="notasortlink" href="'.DOL_URL_ROOT.'/salaries/list.php?search_user='.$object->login.'">'.$langs->trans("AllSalaries").'<span class="badge marginleftonlyshort">'.$num.'</span></a></td>';
   			print '</tr></table></td>';
   			print '</tr>';

			$i = 0;
			while ($i < $num && $i < $MAXLIST)
			{
				$objp = $db->fetch_object($resql);

				print '<tr class="oddeven">';
                print '<td class="nowrap">';
                $salary->id = $objp->rowid;
				$salary->ref = $objp->rowid;

                print $salary->getNomUrl(1);
				print '</td><td class="right" width="80px">'.dol_print_date($db->jdate($objp->datesp), 'day')."</td>\n";
				print '<td class="right" width="80px">'.dol_print_date($db->jdate($objp->dateep), 'day')."</td>\n";
				print '<td class="right" style="min-width: 60px">'.price($objp->amount).'</td></tr>';
				$i++;
			}
			$db->free($resql);

			if ($num <= 0) print '<td colspan="4" class="opacitymedium">'.$langs->trans("None").'</a>';
			print "</table>";
		}
		else
		{
			dol_print_error($db);
		}
	}

	/*
	 * Last holidays
	 */
	if (!empty($conf->holiday->enabled) &&
		($user->rights->holiday->read_all || ($user->rights->holiday->read && $object->id == $user->id))
		)
	{
		$holiday = new Holiday($db);

		$sql = "SELECT h.rowid, h.statut, h.fk_type, h.date_debut, h.date_fin, h.halfday";
		$sql .= " FROM ".MAIN_DB_PREFIX."holiday as h";
		$sql .= " WHERE h.fk_user = ".$object->id;
		$sql .= " AND h.entity = ".$conf->entity;
		$sql .= " ORDER BY h.date_debut DESC";

		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);

	        print '<table class="noborder centpercent">';

            print '<tr class="liste_titre">';
  			print '<td colspan="4"><table class="nobordernopadding centpercent"><tr><td>'.$langs->trans("LastHolidays", ($num <= $MAXLIST ? "" : $MAXLIST)).'</td><td class="right"><a class="notasortlink" href="'.DOL_URL_ROOT.'/holiday/list.php?id='.$object->id.'">'.$langs->trans("AllHolidays").'<span class="badge marginleftonlyshort">'.$num.'</span></a></td>';
   			print '</tr></table></td>';
   			print '</tr>';

			$i = 0;
			while ($i < $num && $i < $MAXLIST)
			{
				$objp = $db->fetch_object($resql);

				print '<tr class="oddeven">';
                print '<td class="nowrap">';
                $holiday->id = $objp->rowid;
				$holiday->ref = $objp->rowid;
                $holiday->fk_type = $objp->fk_type;
				$nbopenedday = num_open_day($db->jdate($objp->date_debut), $db->jdate($objp->date_fin), 0, 1, $objp->halfday);

                print $holiday->getNomUrl(1);
				print '</td><td class="right" width="80px">'.dol_print_date($db->jdate($objp->date_debut), 'day')."</td>\n";
				print '<td class="right" style="min-width: 60px">'.$nbopenedday.' '.$langs->trans('DurationDays').'</td>';
				print '<td class="right" style="min-width: 60px" class="nowrap">'.$holiday->LibStatut($objp->statut, 5).'</td></tr>';
				$i++;
			}
			$db->free($resql);

			if ($num <= 0) print '<td colspan="4" class="opacitymedium">'.$langs->trans("None").'</a>';
			print "</table>";
		}
		else
		{
			dol_print_error($db);
		}
	}

	/*
	 * Last expense report
	 */
	if (!empty($conf->expensereport->enabled) &&
		($user->rights->expensereport->readall || ($user->rights->expensereport->lire && $object->id == $user->id))
		)
	{
		$exp = new ExpenseReport($db);

		$sql = "SELECT e.rowid, e.ref, e.fk_statut, e.date_debut, e.total_ttc";
		$sql .= " FROM ".MAIN_DB_PREFIX."expensereport as e";
		$sql .= " WHERE e.fk_user_author = ".$object->id;
		$sql .= " AND e.entity = ".$conf->entity;
		$sql .= " ORDER BY e.date_debut DESC";

		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);

	        print '<table class="noborder centpercent">';

            print '<tr class="liste_titre">';
   			print '<td colspan="4"><table class="nobordernopadding centpercent"><tr><td>'.$langs->trans("LastExpenseReports", ($num <= $MAXLIST ? "" : $MAXLIST)).'</td><td class="right"><a class="notasortlink" href="'.DOL_URL_ROOT.'/expensereport/list.php?id='.$object->id.'">'.$langs->trans("AllExpenseReports").'<span class="badge marginleftonlyshort">'.$num.'</span></a></td>';
   			print '</tr></table></td>';
   			print '</tr>';

			$i = 0;
			while ($i < $num && $i < $MAXLIST)
			{
				$objp = $db->fetch_object($resql);

				print '<tr class="oddeven">';
                print '<td class="nowrap">';
                $exp->id = $objp->rowid;
				$exp->ref = $objp->ref;
                $exp->fk_type = $objp->fk_type;

                print $exp->getNomUrl(1);
				print '</td><td class="right" width="80px">'.dol_print_date($db->jdate($objp->date_debut), 'day')."</td>\n";
				print '<td class="right" style="min-width: 60px">'.price($objp->total_ttc).'</td>';
				print '<td class="right nowrap" style="min-width: 60px">'.$exp->LibStatut($objp->fk_statut, 5).'</td></tr>';
				$i++;
			}
			$db->free($resql);

			if ($num <= 0) print '<td colspan="4" class="opacitymedium">'.$langs->trans("None").'</a>';
			print "</table>";
		}
		else
		{
			dol_print_error($db);
		}
	}

    print '</div></div></div>';
	print '<div style="clear:both"></div>';

    dol_fiche_end();

	// List of bank accounts (Currently only one bank account possible for each employee)

	$morehtmlright = '';
	if ($account->id == 0) {
		$morehtmlright = dolGetButtonTitle($langs->trans('Add'), '', 'fa fa-plus-circle', $_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=create');
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
	print_liste_field_titre('', $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'maxwidthsearch ');
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
		if ($user->rights->hrm->employee->write || $user->rights->user->creer) {
			print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&bankid='.$account->id.'&action=edit">';
			print img_picto($langs->trans("Modify"), 'edit');
			print '</a>';
		}
		print '</td>';

		print '</tr>';
	}


	if ($account->id == 0)
	{
		$colspan = 6;
		print '<tr><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("NoBANRecord").'</td></tr>';
	}

	print '</table>';
	print '</div>';
}

// Edit
if ($id && ($action == 'edit' || $action == 'create') && $user->rights->user->user->creer)
{
	$title = $langs->trans("User");
	dol_fiche_head($head, 'bank', $title, 0, 'user');

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

    dol_fiche_end();

	print '<div class="center">';
	print '<input class="button" value="'.$langs->trans("Modify").'" type="submit">';
    print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input class="button" name="cancel" value="'.$langs->trans("Cancel").'" type="submit">';
    print '</div>';
}

if ($id && $action == 'edit' && $user->rights->user->user->creer) print '</form>';

if ($id && $action == 'create' && $user->rights->user->user->creer) print '</form>';

// End of page
llxFooter();
$db->close();
