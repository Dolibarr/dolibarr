<?php
/* Copyright (C) 2006		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2007-2011	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2011-2016	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2013 		Philippe Grand			<philippe.grand@atoo-net.com>
 * Copyright (C) 2015-2016	Alexandre Spangaro		<aspangaro@open-dsi.fr>
 * Copyright (C) 2018-2020  Frédéric France         <frederic.france@netlogic.fr>
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
 *	\file		htdocs/compta/paiement/cheque/card.php
 *	\ingroup	bank, invoice
 *	\brief		Page for cheque deposits
 */

require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/cheque/class/remisecheque.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

// Load translation files required by the page
$langs->loadLangs(array('banks', 'categories', 'bills', 'companies', 'compta'));

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');

// Security check
$fieldname = (!empty($ref) ? 'ref' : 'rowid');
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'cheque', $id, 'bordereau_cheque', '', 'fk_user_author', $fieldname);

$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (!$sortorder) {
	$sortorder = "ASC";
}
if (!$sortfield) {
	$sortfield = "b.dateo,b.rowid";
}
if (empty($page) || $page == -1) {
	$page = 0;
}
$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$offset = $limit * $page;

$dir = $conf->bank->dir_output.'/checkdeposits/';
$filterdate = dol_mktime(0, 0, 0, GETPOST('fdmonth'), GETPOST('fdday'), GETPOST('fdyear'));
$filteraccountid = GETPOST('accountid', 'int');

$object = new RemiseCheque($db);


/*
 * Actions
 */

if ($action == 'setdate' && $user->rights->banque->cheque) {
	$result = $object->fetch(GETPOST('id', 'int'));
	if ($result > 0) {
		//print "x ".$_POST['liv_month'].", ".$_POST['liv_day'].", ".$_POST['liv_year'];
		$date = dol_mktime(0, 0, 0, $_POST['datecreate_month'], $_POST['datecreate_day'], $_POST['datecreate_year']);

		$result = $object->set_date($user, $date);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} else {
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

if ($action == 'setrefext' && $user->rights->banque->cheque) {
	$result = $object->fetch(GETPOST('id', 'int'));
	if ($result > 0) {
		$ref_ext = GETPOST('ref_ext');

		$result = $object->setValueFrom('ref_ext', $ref_ext, '', null, 'text', '', $user, 'CHECKDEPOSIT_MODIFY');
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} else {
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

if ($action == 'setref' && $user->rights->banque->cheque) {
	$result = $object->fetch(GETPOST('id', 'int'));
	if ($result > 0) {
		$ref = GETPOST('ref');

		$result = $object->set_number($user, $ref);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} else {
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

if ($action == 'create' && GETPOST("accountid", "int") > 0 && $user->rights->banque->cheque) {
	if (is_array($_POST['toRemise'])) {
		$result = $object->create($user, GETPOST("accountid", "int"), 0, GETPOST('toRemise'));
		if ($result > 0) {
			if ($object->statut == 1) {     // If statut is validated, we build doc
				$object->fetch($object->id); // To force to reload all properties in correct property name
				// Define output language
				$outputlangs = $langs;
				$newlang = '';
				if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
					$newlang = GETPOST('lang_id', 'aZ09');
				}
				//if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}
				$result = $object->generatePdf(GETPOST("model"), $outputlangs);
			}

			header("Location: ".$_SERVER["PHP_SELF"]."?id=".$object->id);
			exit;
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} else {
		setEventMessages($langs->trans("ErrorSelectAtLeastOne"), null, 'mesgs');
		$action = 'new';
	}
}

if ($action == 'remove' && $id > 0 && GETPOST("lineid", 'int') > 0 && $user->rights->banque->cheque) {
	$object->id = $id;
	$result = $object->removeCheck(GETPOST("lineid", "int"));
	if ($result === 0) {
		header("Location: ".$_SERVER["PHP_SELF"]."?id=".$object->id);
		exit;
	} else {
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

if ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->banque->cheque) {
	$object->id = $id;
	$result = $object->delete();
	if ($result == 0) {
		header("Location: index.php");
		exit;
	} else {
		setEventMessages($paiement->error, $paiement->errors, 'errors');
	}
}

if ($action == 'confirm_validate' && $confirm == 'yes' && $user->rights->banque->cheque) {
	$result = $object->fetch($id);
	$result = $object->validate($user);
	if ($result >= 0) {
		// Define output language
		$outputlangs = $langs;
		$newlang = '';
		if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
			$newlang = GETPOST('lang_id', 'aZ09');
		}
		//if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
		if (!empty($newlang)) {
			$outputlangs = new Translate("", $conf);
			$outputlangs->setDefaultLang($newlang);
		}
		$result = $object->generatePdf(GETPOST('model'), $outputlangs);

		header("Location: ".$_SERVER["PHP_SELF"]."?id=".$object->id);
		exit;
	} else {
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

if ($action == 'confirm_reject_check' && $confirm == 'yes' && $user->rights->banque->cheque) {
	$reject_date = dol_mktime(0, 0, 0, GETPOST('rejectdate_month'), GETPOST('rejectdate_day'), GETPOST('rejectdate_year'));
	$rejected_check = GETPOST('bankid', 'int');

	$object->fetch($id);
	$paiement_id = $object->rejectCheck($rejected_check, $reject_date);
	if ($paiement_id > 0) {
		setEventMessages($langs->trans("CheckRejectedAndInvoicesReopened"), null, 'mesgs');
		//header("Location: ".DOL_URL_ROOT.'/compta/paiement/card.php?id='.$paiement_id);
		//exit;
		$action = '';
	} else {
		setEventMessages($object->error, $object->errors, 'errors');
		$action = '';
	}
}

if ($action == 'builddoc' && $user->rights->banque->cheque) {
	$result = $object->fetch($id);

	// Save last template used to generate document
	//if (GETPOST('model')) $object->setDocModel($user, GETPOST('model','alpha'));

	$outputlangs = $langs;
	$newlang = '';
	if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
		$newlang = GETPOST('lang_id', 'aZ09');
	}
	//if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
	if (!empty($newlang)) {
		$outputlangs = new Translate("", $conf);
		$outputlangs->setDefaultLang($newlang);
	}
	$result = $object->generatePdf(GETPOST("model"), $outputlangs);
	if ($result <= 0) {
		dol_print_error($db, $object->error);
		exit;
	} else {
		header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.(empty($conf->global->MAIN_JUMP_TAG) ? '' : '#builddoc'));
		exit;
	}
} elseif ($action == 'remove_file' && $user->rights->banque->cheque) {
	// Remove file in doc form
	if ($object->fetch($id) > 0) {
		include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$langs->load("other");

		$file = $dir.get_exdir($object->ref, 0, 1, 0, $object, 'cheque').GETPOST('file');
		$ret = dol_delete_file($file, 0, 0, 0, $object);
		if ($ret) {
			setEventMessages($langs->trans("FileWasRemoved", GETPOST('file')), null, 'mesgs');
		} else {
			setEventMessages($langs->trans("ErrorFailToDeleteFile", GETPOST('file')), null, 'errors');
		}
	}
}


/*
 * View
 */

if (GETPOST('removefilter')) {
	$filterdate = '';
	$filteraccountid = 0;
}

$title = $langs->trans("Cheques")." - ".$langs->trans("Card");
$helpurl = "";
llxHeader("", $title, $helpurl);

$form = new Form($db);
$formfile = new FormFile($db);


if ($action == 'new') {
	$head = array();
	$h = 0;
	$head[$h][0] = $_SERVER["PHP_SELF"].'?action=new';
	$head[$h][1] = $langs->trans("MenuChequeDeposits");
	$hselected = $h;
	$h++;

	print load_fiche_titre($langs->trans("Cheques"), '', 'bank_account');
} else {
	$result = $object->fetch($id, $ref);
	if ($result < 0) {
		dol_print_error($db, $object->error);
		exit;
	}

	$h = 0;
	$head[$h][0] = $_SERVER["PHP_SELF"].'?id='.$object->id;
	$head[$h][1] = $langs->trans("CheckReceipt");
	$hselected = $h;
	$h++;
	//  $head[$h][0] = DOL_URL_ROOT.'/compta/paiement/cheque/info.php?id='.$object->id;
	//  $head[$h][1] = $langs->trans("Info");
	//  $h++;

	print dol_get_fiche_head($head, $hselected, $langs->trans("Cheques"), -1, 'payment');

	/*
	 * Confirmation of slip's delete
	 */
	if ($action == 'delete') {
		print $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans("DeleteCheckReceipt"), $langs->trans("ConfirmDeleteCheckReceipt"), 'confirm_delete', '', '', 1);
	}

	/*
	 * Confirmation of slip's validation
	 */
	if ($action == 'valide') {
		print $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans("ValidateCheckReceipt"), $langs->trans("ConfirmValidateCheckReceipt"), 'confirm_validate', '', '', 1);
	}

	/*
	 * Confirm check rejection
	 */
	if ($action == 'reject_check') {
		$formquestion = array(
			array('type' => 'hidden', 'name' => 'bankid', 'value' => GETPOST('lineid', 'int')),
			array('type' => 'date', 'name' => 'rejectdate_', 'label' => $langs->trans("RejectCheckDate"), 'value' => dol_now())
		);
		print $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans("RejectCheck"), $langs->trans("ConfirmRejectCheck"), 'confirm_reject_check', $formquestion, '', 1);
	}
}

$accounts = array();

if ($action == 'new') {
	$paymentstatic = new Paiement($db);
	$accountlinestatic = new AccountLine($db);

	$lines = array();

	$now = dol_now();

	print '<span class="opacitymedium">'.$langs->trans("SelectChequeTransactionAndGenerate").'</span><br><br>'."\n";

	print '<form class="nocellnopadd" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="new">';

	print dol_get_fiche_head();

	print '<table class="border centpercent">';
	//print '<tr><td width="30%">'.$langs->trans('Date').'</td><td width="70%">'.dol_print_date($now,'day').'</td></tr>';
	// Filter
	print '<tr><td class="titlefieldcreate">'.$langs->trans("DateChequeReceived").'</td><td>';
	print $form->selectDate($filterdate, 'fd', 0, 0, 1, '', 1, 1);
	print '</td></tr>';
	print '<tr><td>'.$langs->trans("BankAccount").'</td><td>';
	$form->select_comptes($filteraccountid, 'accountid', 0, 'courant <> 2', 1);
	print '</td></tr>';
	print '</table>';

	print dol_get_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" name="filter" value="'.dol_escape_htmltag($langs->trans("ToFilter")).'">';
	if ($filterdate || $filteraccountid > 0) {
		print ' &nbsp; ';
		print '<input type="submit" class="button" name="removefilter" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
	}
	print '</div>';
	print '</form>';
	print '<br>';

	$sql = "SELECT ba.rowid as bid, ba.label,";
	$sql .= " b.rowid as transactionid, b.label as transactionlabel, b.datec as datec, b.dateo as date, ";
	$sql .= " b.amount, b.emetteur, b.num_chq, b.banque,";
	$sql .= " p.rowid as paymentid, p.ref as paymentref";
	$sql .= " FROM ".MAIN_DB_PREFIX."bank as b";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."paiement as p ON p.fk_bank = b.rowid";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank_account as ba ON (b.fk_account = ba.rowid)";
	$sql .= " WHERE b.fk_type = 'CHQ'";
	$sql .= " AND ba.entity IN (".getEntity('bank_account').")";
	$sql .= " AND b.fk_bordereau = 0";
	$sql .= " AND b.amount > 0";
	if ($filterdate) {
		$sql .= " AND b.dateo = '".$db->idate($filterdate)."'";
	}
	if ($filteraccountid > 0) {
		$sql .= " AND ba.rowid = ".((int) $filteraccountid);
	}
	$sql .= $db->order("b.dateo,b.rowid", "ASC");

	$resql = $db->query($sql);
	if ($resql) {
		$i = 0;
		while ($obj = $db->fetch_object($resql)) {
			$accounts[$obj->bid] = $obj->label;
			$lines[$obj->bid][$i]["date"] = $db->jdate($obj->date);
			$lines[$obj->bid][$i]["amount"] = $obj->amount;
			$lines[$obj->bid][$i]["emetteur"] = $obj->emetteur;
			$lines[$obj->bid][$i]["numero"] = $obj->num_chq;
			$lines[$obj->bid][$i]["banque"] = $obj->banque;
			$lines[$obj->bid][$i]["id"] = $obj->transactionid;
			$lines[$obj->bid][$i]["ref"] = $obj->transactionid;
			$lines[$obj->bid][$i]["label"] = $obj->transactionlabel;
			$lines[$obj->bid][$i]["paymentid"] = $obj->paymentid;
			$lines[$obj->bid][$i]["paymentref"] = $obj->paymentref;
			$i++;
		}

		if ($i == 0) {
			print '<div class="opacitymedium">'.$langs->trans("NoWaitingChecks").'</div><br>';
		}
	}

	foreach ($accounts as $bid => $account_label) {
		print '
        <script language="javascript" type="text/javascript">
        jQuery(document).ready(function()
        {
            jQuery("#checkall_'.$bid.'").click(function()
            {
                jQuery(".checkforremise_'.$bid.'").prop(\'checked\', true);
            });
            jQuery("#checknone_'.$bid.'").click(function()
            {
                jQuery(".checkforremise_'.$bid.'").prop(\'checked\', false);
            });
        });
        </script>
        ';

		$num = $db->num_rows($resql);
		print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="create">';
		print '<input type="hidden" name="accountid" value="'.$bid.'">';

		$moreforfilter = '';
		print '<div class="div-table-responsive-no-min">';
		print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("DateChequeReceived").'</td>'."\n";
		print '<td>'.$langs->trans("ChequeNumber")."</td>\n";
		print '<td>'.$langs->trans("CheckTransmitter")."</td>\n";
		print '<td>'.$langs->trans("Bank")."</td>\n";
		print '<td>'.$langs->trans("Amount")."</td>\n";
		print '<td class="center">'.$langs->trans("Payment")."</td>\n";
		print '<td class="center">'.$langs->trans("LineRecord")."</td>\n";
		print '<td class="center">'.$langs->trans("Select")."<br>";
		if ($conf->use_javascript_ajax) {
			print '<a href="#" id="checkall_'.$bid.'">'.$langs->trans("All").'</a> / <a href="#" id="checknone_'.$bid.'">'.$langs->trans("None").'</a>';
		}
		print '</td>';
		print "</tr>\n";

		if (count($lines[$bid])) {
			foreach ($lines[$bid] as $lid => $value) {
				//$account_id = $bid; FIXME not used

				// FIXME $accounts[$bid] is a label !
				/*if (! isset($accounts[$bid]))
					$accounts[$bid]=0;
				$accounts[$bid] += 1;*/

				print '<tr class="oddeven">';
				print '<td>'.dol_print_date($value["date"], 'day').'</td>';
				print '<td>'.$value["numero"]."</td>\n";
				print '<td>'.$value["emetteur"]."</td>\n";
				print '<td>'.$value["banque"]."</td>\n";
				print '<td class="right"><span class="amount">'.price($value["amount"], 0, $langs, 1, -1, -1, $conf->currency).'</span></td>';

				// Link to payment
				print '<td class="center">';
				$paymentstatic->id = $value["paymentid"];
				$paymentstatic->ref = $value["paymentref"];
				if ($paymentstatic->id) {
					print $paymentstatic->getNomUrl(1);
				} else {
					print '&nbsp;';
				}
				print '</td>';
				// Link to bank transaction
				print '<td class="center">';
				$accountlinestatic->id = $value["id"];
				$accountlinestatic->ref = $value["ref"];
				if ($accountlinestatic->id > 0) {
					print $accountlinestatic->getNomUrl(1);
				} else {
					print '&nbsp;';
				}
				print '</td>';

				print '<td class="center">';
				print '<input id="'.$value["id"].'" class="flat checkforremise_'.$bid.'" checked type="checkbox" name="toRemise[]" value="'.$value["id"].'">';
				print '</td>';
				print '</tr>';

				$i++;
			}
		}
		print "</table>";
		print '</div>';

		print '<div class="tabsAction">';
		if ($user->rights->banque->cheque) {
			print '<input type="submit" class="button" value="'.$langs->trans('NewCheckDepositOn', $account_label).'">';
		} else {
			print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans('NewCheckDepositOn', $account_label).'</a>';
		}
		print '</div><br>';
		print '</form>';
	}
} else {
	$paymentstatic = new Paiement($db);
	$accountlinestatic = new AccountLine($db);
	$accountstatic = new Account($db);
	$accountstatic->fetch($object->account_id);

	$linkback = '<a href="'.DOL_URL_ROOT.'/compta/paiement/cheque/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '';
	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';


	print '<table class="border centpercent">';

	print '<tr><td class="titlefield">';

	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('Date');
	print '</td>';
	if ($action != 'editdate') {
		print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editdate&amp;id='.$object->id.'">'.img_edit($langs->trans('SetDate'), 1).'</a></td>';
	}
	print '</tr></table>';
	print '</td><td colspan="2">';
	if ($action == 'editdate') {
		print '<form name="setdate" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="setdate">';
		print $form->selectDate($object->date_bordereau, 'datecreate_', '', '', '', "setdate");
		print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
		print '</form>';
	} else {
		print $object->date_bordereau ? dol_print_date($object->date_bordereau, 'day') : '&nbsp;';
	}

	print '</td>';
	print '</tr>';

	// External ref
	/* Ext ref are not visible field on standard usage
	print '<tr><td>';

	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('RefExt');
	print '</td>';
	if ($action != 'editrefext') print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editrefext&amp;id='.$object->id.'">'.img_edit($langs->trans('SetRefExt'),1).'</a></td>';
	print '</tr></table>';
	print '</td><td colspan="2">';
	if ($action == 'editrefext')
	{
		print '<form name="setrefext" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="setrefext">';
		print '<input type="text" name="ref_ext" value="'.$object->ref_ext.'">';
		print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
		print '</form>';
	}
	else
	{
		print $object->ref_ext;
	}

	print '</td>';
	print '</tr>';
	*/

	print '<tr><td>'.$langs->trans('Account').'</td><td colspan="2">';
	print $accountstatic->getNomUrl(1);
	print '</td></tr>';

	// Number of bank checks
	print '<tr><td>'.$langs->trans('NbOfCheques').'</td><td colspan="2">';
	print $object->nbcheque;
	print '</td></tr>';

	print '<tr><td>'.$langs->trans('Total').'</td><td colspan="2">';
	print price($object->amount);
	print '</td></tr>';

	/*print '<tr><td>'.$langs->trans('Status').'</td><td colspan="2">';
	print $object->getLibStatut(4);
	print '</td></tr>';*/

	print '</table><br>';

	print '</div>';


	// List of bank checks
	$sql = "SELECT b.rowid, b.rowid as ref, b.label, b.amount, b.num_chq, b.emetteur,";
	$sql .= " b.dateo as date, b.datec as datec, b.banque,";
	$sql .= " p.rowid as pid, p.ref as pref, ba.rowid as bid, p.statut";
	$sql .= " FROM ".MAIN_DB_PREFIX."bank_account as ba";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank as b ON (b.fk_account = ba.rowid)";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."paiement as p ON p.fk_bank = b.rowid";
	$sql .= " WHERE ba.entity IN (".getEntity('bank_account').")";
	$sql .= " AND b.fk_type= 'CHQ'";
	$sql .= " AND b.fk_bordereau = ".((int) $object->id);
	$sql .= $db->order($sortfield, $sortorder);

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);

		print '<div class="div-table-responsive">';
		print '<table class="noborder centpercent">';

		$param = "&amp;id=".$object->id;

		print '<tr class="liste_titre">';
		print_liste_field_titre("Cheques", '', '', '', '', 'width="30"');
		print_liste_field_titre("DateChequeReceived", $_SERVER["PHP_SELF"], "b.dateo,b.rowid", "", $param, 'align="center"', $sortfield, $sortorder);
		print_liste_field_titre("Numero", $_SERVER["PHP_SELF"], "b.num_chq", "", $param, 'align="center"', $sortfield, $sortorder);
		print_liste_field_titre("CheckTransmitter", $_SERVER["PHP_SELF"], "b.emetteur", "", $param, "", $sortfield, $sortorder);
		print_liste_field_titre("Bank", $_SERVER["PHP_SELF"], "b.banque", "", $param, "", $sortfield, $sortorder);
		print_liste_field_titre("Amount", $_SERVER["PHP_SELF"], "b.amount", "", $param, 'class="right"', $sortfield, $sortorder);
		print_liste_field_titre("Payment", $_SERVER["PHP_SELF"], "p.rowid", "", $param, 'align="center"', $sortfield, $sortorder);
		print_liste_field_titre("LineRecord", $_SERVER["PHP_SELF"], "b.rowid", "", $param, 'align="center"', $sortfield, $sortorder);
		print_liste_field_titre('');
		print "</tr>\n";

		$i = 1;
		if ($num > 0) {
			while ($objp = $db->fetch_object($resql)) {
				print '<tr class="oddeven">';
				print '<td class="center">'.$i.'</td>';
				print '<td class="center">'.dol_print_date($db->jdate($objp->date), 'day').'</td>'; // Operation date
				print '<td class="center">'.($objp->num_chq ? $objp->num_chq : '&nbsp;').'</td>';
				print '<td>'.dol_trunc($objp->emetteur, 24).'</td>';
				print '<td>'.dol_trunc($objp->banque, 24).'</td>';
				print '<td class="right"><span class="amount">'.price($objp->amount).'</span></td>';
				// Link to payment
				print '<td class="center">';
				$paymentstatic->id = $objp->pid;
				$paymentstatic->ref = $objp->pref;
				if ($paymentstatic->id) {
					print $paymentstatic->getNomUrl(1);
				} else {
					print '&nbsp;';
				}
				print '</td>';
				// Link to bank transaction
				print '<td class="center">';
				$accountlinestatic->id = $objp->rowid;
				$accountlinestatic->ref = $objp->ref;
				if ($accountlinestatic->id > 0) {
					print $accountlinestatic->getNomUrl(1);
				} else {
					print '&nbsp;';
				}
				print '</td>';
				// Action button
				print '<td class="right">';
				if ($object->statut == 0) {
					print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=remove&amp;lineid='.$objp->rowid.'">'.img_delete().'</a>';
				}
				if ($object->statut == 1 && $objp->statut != 2) {
					print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=reject_check&amp;lineid='.$objp->rowid.'">'.img_picto($langs->trans("RejectCheck"), 'disable').'</a>';
				}
				if ($objp->statut == 2) {
					print ' &nbsp; '.img_picto($langs->trans('CheckRejected'), 'statut8').'</a>';
				}
				print '</td>';
				print '</tr>';

				$i++;
			}
		} else {
			print '<td colspan="8" class="opacitymedium">';
			print $langs->trans("None");
			print '</td>';
		}

		print "</table>";

		// Cheque denormalized data nbcheque is similar to real number of bank check
		if ($num > 0 && $i < ($object->nbcheque + 1)) {
			// Show warning that some records were removed.
			$langs->load("errors");
			print info_admin($langs->trans("WarningSomeBankTransactionByChequeWereRemovedAfter"), 0, 0, 'warning');
			// TODO Fix data ->nbcheque and ->amount
		}

		print "</div>";
	} else {
		dol_print_error($db);
	}

	print dol_get_fiche_end();
}




/*
 * Actions Buttons
 */

print '<div class="tabsAction">';

if ($user->socid == 0 && !empty($object->id) && $object->statut == 0 && $user->rights->banque->cheque) {
	print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=valide&amp;token='.newToken().'&amp;sortfield='.$sortfield.'&amp;sortorder='.$sortorder.'">'.$langs->trans('Validate').'</a>';
}

if ($user->socid == 0 && !empty($object->id) && $user->rights->banque->cheque) {
	print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=delete&amp;token='.newToken().'&amp;sortfield='.$sortfield.'&amp;sortorder='.$sortorder.'">'.$langs->trans('Delete').'</a>';
}
print '</div>';



if ($action != 'new') {
	if ($object->statut == 1) {
		$filename = dol_sanitizeFileName($object->ref);
		$filedir = $dir.get_exdir($object->ref, 0, 1, 0, $object, 'checkdeposits');
		$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;

		print $formfile->showdocuments('remisecheque', $filename, $filedir, $urlsource, 1, 1);

		print '<br>';
	}
}

// End of page
llxFooter();
$db->close();
