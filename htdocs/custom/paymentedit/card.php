<?php

/* Copyright (C) 2024-2025  Florian Hödl  <florian@hoedl.co>
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
 * \file       htdocs/custom/paymentedit/card.php
 * \ingroup    paymentedit
 * \brief      Edit page for various payments
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/paymentvarious.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

if (isModEnabled('accounting')) {
    require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
    require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
    require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';
}
if (isModEnabled('project')) {
    require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
    require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files
$langs->loadLangs(array("compta", "banks", "bills", "users", "accountancy", "paymentedit@paymentedit"));

// Get parameters
$id = GETPOSTINT('id');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'aZ09');

// Security check
$socid = GETPOSTINT("socid");
if ($user->socid) {
    $socid = $user->socid;
}

$result = restrictedArea($user, 'banque', '', '', '');

// Permission check
$permissiontoadd = $user->hasRight('banque', 'modifier');

// Initialize object
$object = new PaymentVarious($db);

// Load object
if ($id > 0) {
    $result = $object->fetch($id);
    if ($result <= 0) {
        setEventMessages($langs->trans('RecordNotFound'), null, 'errors');
        header('Location: '.DOL_URL_ROOT.'/compta/bank/various_payment/list.php');
        exit;
    }
}

// Check if reconciled (cannot edit reconciled payments)
$isReconciled = !empty($object->rappro) && $object->rappro == 1;
if ($isReconciled && $action != '') {
    setEventMessages($langs->trans('LinkedToAConciliatedTransaction'), null, 'errors');
    header('Location: '.DOL_URL_ROOT.'/compta/bank/various_payment/card.php?id='.$object->id);
    exit;
}

// Check if already accounted
$alreadyAccounted = false;
if (method_exists($object, 'getVentilExportCompta')) {
    $alreadyAccounted = $object->getVentilExportCompta();
}


/*
 * Actions
 */

if ($cancel) {
    header('Location: '.DOL_URL_ROOT.'/compta/bank/various_payment/card.php?id='.$object->id);
    exit;
}

// Update action - with CSRF protection
if ($action == 'update' && $permissiontoadd && GETPOST('token', 'alpha') == $_SESSION['newtoken']) {
    $error = 0;

    // Parse dates
    $datep = dol_mktime(12, 0, 0, GETPOSTINT("datepmonth"), GETPOSTINT("datepday"), GETPOSTINT("datepyear"));
    $datev = dol_mktime(12, 0, 0, GETPOSTINT("datevmonth"), GETPOSTINT("datevday"), GETPOSTINT("datevyear"));
    if (empty($datev)) {
        $datev = $datep;
    }

    // Validate required fields
    if (empty($datep)) {
        setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("DatePayment")), null, 'errors');
        $error++;
    }
    $label = GETPOST('label', 'restricthtml');
    if (empty($label)) {
        setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Label")), null, 'errors');
        $error++;
    }
    $amount = GETPOSTFLOAT('amount');
    if (empty($amount)) {
        setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Amount")), null, 'errors');
        $error++;
    }

    if (!$error) {
        $db->begin();

        // Update object properties
        $object->datep = $datep;
        $object->datev = $datev;
        $object->label = $label;
        $object->amount = $amount;
        $object->num_payment = GETPOST('num_payment', 'alpha');
        $object->note = GETPOST('note', 'restricthtml');
        $object->note_private = $object->note; // Set both for compatibility
        $object->fk_project = GETPOSTINT('fk_project');
        $object->fk_user_modif = $user->id;

        // Accounting fields - only if not already accounted
        if (!$alreadyAccounted) {
            $accountancy_code = GETPOST('accountancy_code', 'alpha');
            if ($accountancy_code != '-1') {
                $object->accountancy_code = $accountancy_code;
            }
            $object->subledger_account = GETPOST('subledger_account', 'alpha');
        }

        // Call update method
        $result = $object->update($user);

        if ($result > 0) {
            // Also update bank line if exists
            if ($object->fk_bank > 0) {
                $bankline = new AccountLine($db);
                $resultBank = $bankline->fetch($object->fk_bank);
                if ($resultBank > 0) {
                    // Update bank line label and dates
                    $bankline->label = $object->label;
                    $bankline->datev = $object->datev;
                    $bankline->dateo = $object->datep;

                    // Update amount based on sens
                    if ($object->sens == 1) {
                        // Credit (positive)
                        $bankline->amount = abs($object->amount);
                    } else {
                        // Debit (negative)
                        $bankline->amount = -1 * abs($object->amount);
                    }

                    $resultBankUpdate = $bankline->update($user);
                    if ($resultBankUpdate < 0) {
                        $error++;
                        setEventMessages($bankline->error, $bankline->errors, 'errors');
                    }
                }
            }
        } else {
            $error++;
            setEventMessages($object->error, $object->errors, 'errors');
        }

        if (!$error) {
            $db->commit();
            setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
            header('Location: '.DOL_URL_ROOT.'/compta/bank/various_payment/card.php?id='.$object->id);
            exit;
        } else {
            $db->rollback();
            $action = 'edit';
        }
    } else {
        $action = 'edit';
    }
}


/*
 * View
 */

$form = new Form($db);
if (isModEnabled('accounting')) {
    $formaccounting = new FormAccounting($db);
}
if (isModEnabled('project')) {
    $formproject = new FormProjets($db);
}

$title = $langs->trans('PaymentEditTitle');
$help_url = '';

llxHeader('', $title, $help_url);

if ($id > 0) {
    // Check permission to edit
    if (!$permissiontoadd) {
        accessforbidden('NotEnoughPermissions');
    }

    // Re-fetch to get latest data after update
    $object->fetch($id);

    // Tab header - use the same tabs as core
    $head = various_payment_prepare_head($object);

    print dol_get_fiche_head($head, 'card', $langs->trans("VariousPayment"), -1, $object->picto);

    // Object banner
    $morehtmlref = '<div class="refidno">';
    if (isModEnabled('project') && !empty($formproject)) {
        $langs->load("projects");
        $morehtmlref .= img_picto($langs->trans("Project"), 'project', 'class="pictofixedwidth"');
        if (!empty($object->fk_project)) {
            $proj = new Project($db);
            $proj->fetch($object->fk_project);
            $morehtmlref .= $proj->getNomUrl(1);
            if ($proj->title) {
                $morehtmlref .= '<span class="opacitymedium"> - '.dol_escape_htmltag($proj->title).'</span>';
            }
        }
    }
    $morehtmlref .= '</div>';

    $linkback = '<a href="'.DOL_URL_ROOT.'/compta/bank/various_payment/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

    dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref, '', 0, '', '');

    print '<div class="fichecenter">';
    print '<div class="underbanner clearboth"></div>';

    // Edit form
    print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="update">';
    print '<input type="hidden" name="id" value="'.$object->id.'">';

    print '<table class="border centpercent tableforfield">';

    // Label
    print '<tr><td class="titlefield fieldrequired">'.$langs->trans("Label").'</td>';
    print '<td><input type="text" name="label" class="minwidth300" value="'.dol_escape_htmltag($object->label).'"></td></tr>';

    // Payment date
    print '<tr><td class="fieldrequired">'.$langs->trans("DatePayment").'</td><td>';
    print $form->selectDate($object->datep, "datep", 0, 0, 0, 'edit', 1, 1);
    print '</td></tr>';

    // Value date
    print '<tr><td>'.$langs->trans("DateValue").'</td><td>';
    print $form->selectDate($object->datev, "datev", 0, 0, 0, 'edit', 1, 1);
    print '</td></tr>';

    // Sens (direction) - display only, cannot change
    if ($object->sens == '1') {
        $sens = $langs->trans("Credit");
    } else {
        $sens = $langs->trans("Debit");
    }
    print '<tr><td>'.$langs->trans("Sens").'</td><td>'.$sens;
    print ' <span class="opacitymedium">('.$langs->trans("PaymentEditSensReadonly").')</span>';
    print '</td></tr>';

    // Amount
    print '<tr><td class="fieldrequired">'.$langs->trans("Amount").'</td>';
    print '<td><input type="text" name="amount" class="minwidth100" value="'.price($object->amount).'"></td></tr>';

    // Payment number
    print '<tr><td>'.$langs->trans('Numero').' <em>('.$langs->trans("ChequeOrTransferNumber").')</em></td>';
    print '<td><input type="text" name="num_payment" class="maxwidth150" value="'.dol_escape_htmltag($object->num_payment).'"></td></tr>';

    // Project
    if (isModEnabled('project') && !empty($formproject)) {
        print '<tr><td>'.$langs->trans("Project").'</td><td>';
        print img_picto('', 'project', 'class="pictofixedwidth"');
        print $formproject->select_projects(-1, (string) $object->fk_project, 'fk_project', 0, 0, 1, 1, 0, 0, 0, '', 1);
        print '</td></tr>';
    }

    // Accountancy code
    if (isModEnabled('accounting') && !empty($formaccounting)) {
        print '<tr><td class="nowrap">'.$langs->trans("AccountAccounting").'</td><td>';
        if ($alreadyAccounted) {
            // Show as readonly with explanation
            $accountingaccount = new AccountingAccount($db);
            $accountingaccount->fetch(0, $object->accountancy_code, 1);
            print $accountingaccount->getNomUrl(0, 1, 1, '', 1);
            print ' <span class="opacitymedium">('.$langs->trans("PaymentEditAccountingLocked").')</span>';
        } else {
            print $formaccounting->select_account($object->accountancy_code, 'accountancy_code', 1, array(), 1, 1);
        }
        print '</td></tr>';

        // Subledger account
        print '<tr><td class="nowrap">'.$langs->trans("SubledgerAccount").'</td><td>';
        if ($alreadyAccounted) {
            print length_accounta($object->subledger_account);
            print ' <span class="opacitymedium">('.$langs->trans("PaymentEditAccountingLocked").')</span>';
        } else {
            if (getDolGlobalString('ACCOUNTANCY_COMBO_FOR_AUX')) {
                print $formaccounting->select_auxaccount($object->subledger_account, 'subledger_account', 1, '');
            } else {
                print '<input type="text" class="maxwidth200" name="subledger_account" value="'.dol_escape_htmltag($object->subledger_account).'">';
            }
        }
        print '</td></tr>';
    } else {
        // Without accounting module
        print '<tr><td class="nowrap">'.$langs->trans("AccountAccounting").'</td>';
        print '<td><input type="text" class="minwidth100" name="accountancy_code" value="'.dol_escape_htmltag($object->accountancy_code).'"></td></tr>';

        print '<tr><td class="nowrap">'.$langs->trans("SubledgerAccount").'</td>';
        print '<td><input type="text" class="minwidth100" name="subledger_account" value="'.dol_escape_htmltag($object->subledger_account).'"></td></tr>';
    }

    // Bank transaction link (display only)
    if (isModEnabled('bank')) {
        print '<tr><td>'.$langs->trans('BankTransactionLine').'</td><td>';
        if ($object->fk_bank > 0) {
            $bankline = new AccountLine($db);
            $result = $bankline->fetch($object->fk_bank);
            if ($result > 0) {
                print $bankline->getNomUrl(1, 0, 'showall');
            } else {
                print '<span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span>';
            }
        } else {
            print '<span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span>';
        }
        print '</td></tr>';
    }

    // Note
    print '<tr><td class="tdtop">'.$langs->trans("Note").'</td>';
    print '<td>';
    print '<textarea name="note" class="flat quatrevingtpercent" rows="3">'.dol_escape_htmltag($object->note_private).'</textarea>';
    print '</td></tr>';

    print '</table>';

    print '</div>'; // fichecenter

    print dol_get_fiche_end();

    // Action buttons
    print $form->buttonsSaveCancel();

    print '</form>';
}

// End of page
llxFooter();
$db->close();
