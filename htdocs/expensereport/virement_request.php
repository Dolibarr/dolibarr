<?php
/* Copyright (C) 2026		Maximilien Valenzano	<slordef.vikamet@gmail.com>
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
 *	\file       htdocs/expensereport/virement_request.php
 *	\ingroup    expensereport
 *	\brief      Page to request the payment of an expense report by credit transfer
 */

// Load Dolibarr environment
require '../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/core/lib/expensereport.lib.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/bonprelevement.class.php';

// Load translation files required by the page
$langs->loadLangs(array("trips", "bills", "banks", "salaries", "withdrawals"));

$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');

$childids = $user->getAllChildIds(1);

// Security check
$result = restrictedArea($user, 'expensereport', $id, 'expensereport');

$object = new ExpenseReport($db);
if (!$object->fetch($id, $ref) > 0) {
	dol_print_error($db);
}

if ($object->id > 0) {
	// Check current user can read this expense report
	$canread = 0;
	if ($user->hasRight('expensereport', 'readall')) {
		$canread = 1;
	}
	if (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('expensereport', 'writeall_advance')) {
		$canread = 1;	// Can create an expense report for everybody, so can also read it
	}
	if ($user->hasRight('expensereport', 'lire') && in_array($object->fk_user_author, $childids)) {
		$canread = 1;
	}
	if ($user->hasRight('expensereport', 'approve') && $object->fk_user_validator == $user->id) {
		$canread = 1;	// The user designated to approve this expense report
	}
	if (!$canread) {
		accessforbidden();
	}
}

$permissiontoadd = $user->hasRight('paymentbybanktransfer', 'create') && $user->hasRight('expensereport', 'to_paid');


/*
 * Actions
 */

if ($action == "add" && $permissiontoadd) {
	$result = $object->demande_prelevement($user, GETPOSTFLOAT('request_transfer'));
	if ($result > 0) {
		setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
	} else {
		setEventMessages($object->error, $object->errors, 'errors');
	}
	$action = '';
}

if ($action == "delete" && $permissiontoadd) {
	$result = $object->demande_prelevement_delete($user, GETPOSTINT('did'));
	if ($result == 0) {
		header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
		exit;
	}
}


/*
 * View
 */

$title = $langs->trans("ExpenseReport")." - ".$langs->trans("BankTransfer");
llxHeader("", $title);

$head = expensereport_prepare_head($object);

print dol_get_fiche_head($head, 'request_virement', $langs->trans("ExpenseReport"), -1, 'trip');

$linkback = '<a href="'.DOL_URL_ROOT.'/expensereport/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

$userstatic = new User($db);
$userstatic->fetch($object->fk_user_author);
$morehtmlref = '<div class="refidno">'.$langs->trans('Employee').' : '.$userstatic->getNomUrl(-1).'</div>';

dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

$resteapayer = (float) price2num($object->total_ttc - $object->getSumPayments(), 'MT');

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';
print '<table class="border centpercent tableforfield">';
print '<tr><td class="titlefieldmiddle">'.$langs->trans("AmountTTC").'</td><td><span class="amount">'.price($object->total_ttc, 0, $langs, 1, -1, -1, $conf->currency).'</span></td></tr>';
print '<tr><td>'.$langs->trans("RemainderToPay").'</td><td><span class="amount">'.price($resteapayer, 0, $langs, 1, -1, -1, $conf->currency).'</span></td></tr>';
print '</table>';
print '</div>';

print dol_get_fiche_end();

$sql = "SELECT pfd.rowid, pfd.traite, pfd.date_demande, pfd.date_traite, pfd.amount, pfd.fk_prelevement_bons,";
$sql .= " pb.ref, pb.statut as status, pb.amount as pb_amount,";
$sql .= " u.rowid as user_id, u.email, u.lastname, u.firstname, u.login, u.statut as user_status";
$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_demande as pfd";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u on pfd.fk_user_demande = u.rowid";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."prelevement_bons as pb ON pb.rowid = pfd.fk_prelevement_bons";
$sql .= " WHERE pfd.fk_expensereport = ".((int) $object->id);
$sql .= " AND pfd.type = 'ban'";
$sql .= " ORDER BY pfd.date_demande DESC";
$resql = $db->query($sql);
if (!$resql) {
	dol_print_error($db);
}

$requests = array();
while ($resql && ($obj = $db->fetch_object($resql))) {
	$requests[] = $obj;
}
$pending = $object->getCreditTransferPendingAmount();

$buttonlabel = $langs->trans("MakeTransferRequest");

print '<div class="tabsAction">'."\n";
if ($object->status != ExpenseReport::STATUS_APPROVED || $object->paid) {
	print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("StatusOfRefMustBe", $object->ref, $langs->transnoentitiesnoconv("Approved"))).'">'.$buttonlabel.'</a>';
} elseif ($pending != 0) {
	print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("RequestAlreadyDone")).'">'.$buttonlabel.'</a>';
} elseif ($resteapayer <= 0) {
	print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("AmountMustBePositive")).'">'.$buttonlabel.'</a>';
} elseif (!$permissiontoadd) {
	print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$buttonlabel.'</a>';
} else {
	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
	print '<input type="hidden" name="token" value="'.newToken().'" />';
	print '<input type="hidden" name="action" value="add" />';
	print '<label for="withdraw_request_amount">'.$langs->trans('BankTransferAmount').' </label>';
	print '<input type="text" id="withdraw_request_amount" name="request_transfer" value="'.price($resteapayer, 0, $langs, 1, -1, -1).'" size="9" />';
	print '<input type="submit" class="butAction" value="'.$buttonlabel.'" />';
	print '</form>';
}
print '</div>';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("DateRequest").'</td>';
print '<td>'.$langs->trans("User").'</td>';
print '<td class="center">'.$langs->trans("Amount").'</td>';
print '<td class="center">'.$langs->trans("DateProcess").'</td>';
print '<td class="center">'.$langs->trans("BankTransferReceipt").'</td>';
print '<td>&nbsp;</td>';
print '</tr>';

$tmpuser = new User($db);
foreach ($requests as $obj) {
	$tmpuser->id = $obj->user_id;
	$tmpuser->login = $obj->login;
	$tmpuser->ref = $obj->login;
	$tmpuser->email = $obj->email;
	$tmpuser->lastname = $obj->lastname;
	$tmpuser->firstname = $obj->firstname;
	$tmpuser->status = $obj->user_status;

	print '<tr class="oddeven">';
	print '<td class="nowraponall">'.dol_print_date($db->jdate($obj->date_demande), 'dayhour')."</td>\n";
	print '<td class="tdoverflowmax125">'.$tmpuser->getNomUrl(-1, '', 0, 0, 0, 0, 'login').'</td>';
	print '<td class="center"><span class="amount">'.price($obj->amount).'</span></td>';
	if ($obj->traite) {
		print '<td class="center nowraponall">'.dol_print_date($db->jdate($obj->date_traite), 'dayhour', 'tzuserrel')."</td>\n";
	} else {
		print '<td class="center"><span class="opacitymedium">'.$langs->trans("OrderWaiting").'</span></td>';
	}
	print '<td class="center minwidth75">';
	if ($obj->fk_prelevement_bons > 0) {
		$withdrawreceipt = new BonPrelevement($db);
		$withdrawreceipt->id = $obj->fk_prelevement_bons;
		$withdrawreceipt->ref = $obj->ref;
		$withdrawreceipt->status = $obj->status;
		$withdrawreceipt->amount = $obj->pb_amount;
		print $withdrawreceipt->getNomUrl(1).' '.$withdrawreceipt->getLibStatut(2);
	}
	print '</td>';
	print '<td class="right">';
	if (!$obj->traite && $permissiontoadd) {
		print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=delete&token='.newToken().'&did='.$obj->rowid.'">'.img_delete().'</a>';
	}
	print '</td>';
	print "</tr>\n";
}
if (!count($requests)) {
	print '<tr class="oddeven"><td colspan="6"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
}

print "</table>";
print '</div>';

// End of page
llxFooter();
$db->close();
