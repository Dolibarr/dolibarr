<?php
/* Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013      Peter Fontaine       <contact@peterfontaine.fr>
 * Copyright (C) 2015-2016 Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2015	   Alexandre Spangaro	<aspangaro.dolibarr@gmail.com>
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
 *	    \file       htdocs/user/bank.php
 *      \ingroup    HRM
 *		\brief      BAN tab for users
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/userbankaccount.class.php';

$langs->load("companies");
$langs->load("commercial");
$langs->load("banks");
$langs->load("bills");

$id = GETPOST('id','int');
$action = GETPOST("action");

// Security check
$socid=0;
if ($user->societe_id > 0) $socid = $user->societe_id;
$feature2 = (($socid && $user->rights->user->self->creer)?'':'user');
if ($user->id == $id) $feature2=''; // A user can always read its own card
$result = restrictedArea($user, 'user', $id, 'user&user', $feature2);

$object = new User($db);
if ($id > 0 || ! empty($ref))
{
	$result = $object->fetch($id, $ref);
}

/*
 *	Actions
 */

if ($action == 'update' && ! $_POST["cancel"])
{
	// Modification
	$account = new UserBankAccount($db);

    $account->fetch($id);

    $account->userid          = $object->id;

	$account->bank            = $_POST["bank"];
	$account->label           = $_POST["label"];
	$account->courant         = $_POST["courant"];
	$account->clos            = $_POST["clos"];
	$account->code_banque     = $_POST["code_banque"];
	$account->code_guichet    = $_POST["code_guichet"];
	$account->number          = $_POST["number"];
	$account->cle_rib         = $_POST["cle_rib"];
	$account->bic             = $_POST["bic"];
	$account->iban            = $_POST["iban"];
	$account->domiciliation   = $_POST["domiciliation"];
	$account->proprio         = $_POST["proprio"];
	$account->owner_address   = $_POST["owner_address"];

	$result = $account->update($user);
	if (! $result)
	{
		setEventMessages($account->error, $account->errors, 'errors');
		$_GET["action"]='edit';     // Force chargement page edition
	}
	else
	{
		$url=DOL_URL_ROOT.'/user/bank.php?id='.$object->id;
        header('Location: '.$url);
        exit;
	}
}

/*
 *	View
 */

$form = new Form($db);

llxHeader();

$head = user_prepare_head($object);

$account = new UserBankAccount($db);
if (! $id)
    $account->fetch(0,$object->id);
else
    $account->fetch($id);
if (empty($account->userid)) $account->userid=$object->id;


if ($id && $action == 'edit' && $user->rights->user->user->creer)
{
    print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'" method="post">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="update">';
    print '<input type="hidden" name="id" value="'.GETPOST("id",'int').'">';
}
if ($id && $action == 'create' && $user->rights->user->user->creer)
{
    print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'" method="post">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="add">';
}


// View
if ($id && $action != 'edit')
{
	$title = $langs->trans("User");
	dol_fiche_head($head, 'bank', $title, 0, 'user');

	$linkback = '';

	if ($user->rights->user->user->lire || $user->admin) {
		$linkback = '<a href="'.DOL_URL_ROOT.'/user/index.php">'.$langs->trans("BackToList").'</a>';
	}
	
    dol_banner_tab($object,'id',$linkback,$user->rights->user->user->lire || $user->admin);
        
    print '<div class="fichecenter">';

    print '<div class="underbanner clearboth"></div>';
    print '<table class="border centpercent">';

    print '<tr><td class="titlefield">'.$langs->trans("LabelRIB").'</td>';
    print '<td colspan="4">'.$account->label.'</td></tr>';

	print '<tr><td>'.$langs->trans("BankName").'</td>';
	print '<td colspan="4">'.$account->bank.'</td></tr>';

	// Show fields of bank account
	foreach ($account->getFieldsToShow() as $val) {
		if ($val == 'BankCode') {
			$content = $account->code_banque;
		} elseif ($val == 'DeskCode') {
			$content = $account->code_guichet;
		} elseif ($val == 'BankAccountNumber') {
			$content = $account->number;
		} elseif ($val == 'BankAccountNumberKey') {
			$content = $account->cle_rib;
		}

		print '<tr><td>'.$langs->trans($val).'</td>';
		print '<td colspan="3">'.$content.'</td>';
		print '</tr>';
	}

	print '<tr><td class="tdtop">'.$langs->trans("IBAN").'</td>';
	print '<td colspan="4">'.$account->iban . '&nbsp;';
    if (! empty($account->iban)) {
        if (! checkIbanForAccount($account)) {
            print img_picto($langs->trans("IbanNotValid"),'warning');
        } else {
            print img_picto($langs->trans("IbanValid"),'info');
        }
    }
    print '</td></tr>';

	print '<tr><td class="tdtop">'.$langs->trans("BIC").'</td>';
	print '<td colspan="4">'.$account->bic.'&nbsp;';
    if (! empty($account->bic)) {
        if (! checkSwiftForAccount($account)) {
            print img_picto($langs->trans("SwiftNotValid"),'warning');
        } else {
            print img_picto($langs->trans("SwiftValid"),'info');
        }
    }
    print '</td></tr>';

	print '<tr><td class="tdtop">'.$langs->trans("BankAccountDomiciliation").'</td><td colspan="4">';
	print $account->domiciliation;
	print "</td></tr>\n";

	print '<tr><td class="tdtop">'.$langs->trans("BankAccountOwner").'</td><td colspan="4">';
	print $account->proprio;
	print "</td></tr>\n";

	print '<tr><td class="tdtop">'.$langs->trans("BankAccountOwnerAddress").'</td><td colspan="4">';
	print $account->owner_address;
	print "</td></tr>\n";

	print '</table>';

	// Check BBAN
	if ($account->label && ! checkBanForAccount($account))
	{
		print '<div class="warning">'.$langs->trans("RIBControlError").'</div>';
	}

    print "</div>";
    
    dol_fiche_end();

	/*
	 * Barre d'actions
	 */
	print '<div class="tabsAction">';

	if ($user->rights->user->user->creer)
	{
		print '<a class="butAction" href="bank.php?id='.$object->id.'&amp;action=edit">'.$langs->trans("Edit").'</a>';
	}

	print '</div>';
}

// Edit
if ($id && $action == 'edit' && $user->rights->user->user->creer)
{
	$title = $langs->trans("User");
	dol_fiche_head($head, 'bank', $title, 0, 'user');

	$linkback = '<a href="'.DOL_URL_ROOT.'/user/index.php">'.$langs->trans("BackToList").'</a>';
	
    dol_banner_tab($object,'id',$linkback,$user->rights->user->user->lire || $user->admin);
        
    print '<div class="fichecenter">';
    
    print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent">';

    print '<tr><td valign="top" width="35%" class="fieldrequired">'.$langs->trans("LabelRIB").'</td>';
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
    print '<tr><td class="tdtop fieldrequired">'.$langs->trans("IBAN").'</td>';
    print '<td colspan="4"><input size="30" type="text" name="iban" value="'.$account->iban.'"></td></tr>';

    print '<tr><td class="tdtop fieldrequired">'.$langs->trans("BIC").'</td>';
    print '<td colspan="4"><input size="12" type="text" name="bic" value="'.$account->bic.'"></td></tr>';

    print '<tr><td class="tdtop">'.$langs->trans("BankAccountDomiciliation").'</td><td colspan="4">';
    print '<textarea name="domiciliation" rows="4" cols="40">';
    print $account->domiciliation;
    print "</textarea></td></tr>";

    print '<tr><td class="tdtop">'.$langs->trans("BankAccountOwner").'</td>';
    print '<td colspan="4"><input size="30" type="text" name="proprio" value="'.$account->proprio.'"></td></tr>';
    print "</td></tr>\n";

    print '<tr><td class="tdtop">'.$langs->trans("BankAccountOwnerAddress").'</td><td colspan="4">';
    print "<textarea name=\"owner_address\" rows=\"4\" cols=\"40\">";
    print $account->owner_address;
    print "</textarea></td></tr>";

    print '</table>';

    print '</div>';
    
    dol_fiche_end();

	print '<div align="center">';
	print '<input class="button" value="'.$langs->trans("Modify").'" type="submit">';
    print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input class="button" name="cancel" value="'.$langs->trans("Cancel").'" type="submit">';
    print '</div>';
}

llxFooter();

$db->close();
