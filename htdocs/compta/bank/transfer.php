<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
<<<<<<< HEAD
 * Copyright (C) 2005-2015 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2012	   Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
=======
 * Copyright (C) 2005-2015 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2012	   Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
 *		\file       htdocs/compta/bank/transfer.php
 *		\ingroup    banque
 *		\brief      Page de saisie d'un virement
 */

<<<<<<< HEAD
require('../../main.inc.php');
=======
require '../../main.inc.php';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

// Load translation files required by the page
$langs->loadLangs(array("banks", "categories", "multicurrency"));

if (! $user->rights->banque->transfer)
  accessforbidden();

<<<<<<< HEAD
$action = GETPOST('action','alpha');
=======
$action = GETPOST('action', 'alpha');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
$error = 0;


/*
 * Actions
 */

if ($action == 'add')
{
	$langs->load("errors");

<<<<<<< HEAD
	$dateo = dol_mktime(12,0,0,GETPOST('remonth','int'),GETPOST('reday','int'),GETPOST('reyear','int'));
	$label = GETPOST('label','alpha');
	$amount= GETPOST('amount','alpha');
	$amountto= GETPOST('amountto','alpha');
=======
	$dateo = dol_mktime(12, 0, 0, GETPOST('remonth', 'int'), GETPOST('reday', 'int'), GETPOST('reyear', 'int'));
	$label = GETPOST('label', 'alpha');
	$amount= GETPOST('amount', 'alpha');
	$amountto= GETPOST('amountto', 'alpha');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	if (! $label)
	{
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Description")), null, 'errors');
	}
	if (! $amount)
	{
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Amount")), null, 'errors');
	}
<<<<<<< HEAD
	if (! GETPOST('account_from','int'))
=======
	if (! GETPOST('account_from', 'int'))
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("TransferFrom")), null, 'errors');
	}
<<<<<<< HEAD
	if (! GETPOST('account_to','int'))
=======
	if (! GETPOST('account_to', 'int'))
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("TransferTo")), null, 'errors');
	}
	if (! $error)
	{
		require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

		$accountfrom=new Account($db);
<<<<<<< HEAD
		$accountfrom->fetch(GETPOST('account_from','int'));

		$accountto=new Account($db);
		$accountto->fetch(GETPOST('account_to','int'));
=======
		$accountfrom->fetch(GETPOST('account_from', 'int'));

		$accountto=new Account($db);
		$accountto->fetch(GETPOST('account_to', 'int'));
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

		if ($accountto->currency_code == $accountfrom->currency_code)
		{
			$amountto=$amount;
		}
		else
		{
			if (! $amountto)
			{
				$error++;
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("AmountTo")), null, 'errors');
			}
		}

		if (($accountto->id != $accountfrom->id) && empty($error))
		{
			$db->begin();

			$bank_line_id_from=0;
			$bank_line_id_to=0;
			$result=0;

			// By default, electronic transfert from bank to bank
			$typefrom='PRE';
			$typeto='VIR';
			if ($accountto->courant == Account::TYPE_CASH || $accountfrom->courant == Account::TYPE_CASH)
			{
				// This is transfer of change
				$typefrom='LIQ';
				$typeto='LIQ';
			}

			if (! $error) $bank_line_id_from = $accountfrom->addline($dateo, $typefrom, $label, -1*price2num($amount), '', '', $user);
			if (! ($bank_line_id_from > 0)) $error++;
			if (! $error) $bank_line_id_to = $accountto->addline($dateo, $typeto, $label, price2num($amountto), '', '', $user);
			if (! ($bank_line_id_to > 0)) $error++;

		    if (! $error) $result=$accountfrom->add_url_line($bank_line_id_from, $bank_line_id_to, DOL_URL_ROOT.'/compta/bank/ligne.php?rowid=', '(banktransfert)', 'banktransfert');
			if (! ($result > 0)) $error++;
		    if (! $error) $result=$accountto->add_url_line($bank_line_id_to, $bank_line_id_from, DOL_URL_ROOT.'/compta/bank/ligne.php?rowid=', '(banktransfert)', 'banktransfert');
			if (! ($result > 0)) $error++;

			if (! $error)
			{
				$mesgs = $langs->trans("TransferFromToDone", '<a href="bankentries_list.php?id='.$accountfrom->id.'&sortfield=b.datev,b.dateo,b.rowid&sortorder=desc">'.$accountfrom->label."</a>", '<a href="bankentries_list.php?id='.$accountto->id.'">'.$accountto->label."</a>", $amount, $langs->transnoentities("Currency".$conf->currency));
				setEventMessages($mesgs, null, 'mesgs');
				$db->commit();
			}
			else
			{
				setEventMessages($accountfrom->error.' '.$accountto->error, null, 'errors');
				$db->rollback();
			}
		}
		else
		{
		    $error++;
			setEventMessages($langs->trans("ErrorFromToAccountsMustDiffers"), null, 'errors');
		}
	}
}



/*
 * View
 */

llxHeader();
print '		<script type="text/javascript">
        	$(document).ready(function () {
    	  		$(".selectbankaccount").change(function() {
						console.log("We change bank account");
						init_page();
				});

				function init_page() {
					console.log("Set fields according to currency");
        			var account1 = $("#selectaccount_from").val();
        			var account2 = $("#selectaccount_to").val();
        			var currencycode1="";
        			var currencycode2="";

					$.get("'.DOL_URL_ROOT.'/core/ajax/getaccountcurrency.php", {id: account1})
						.done(function( data ) {
							if (data != null)
							{
								var item= $.parseJSON(data);
								if (item.num==-1) {
									console.error("Error: "+item.error);
								} else if (item.num!==0) {
									currencycode1 = item.value;
								}

								$.get("'.DOL_URL_ROOT.'/core/ajax/getaccountcurrency.php", {id: account2})
									.done(function( data ) {
										if (data != null)
										{
											var item=$.parseJSON(data);
											if (item.num==-1) {
												console.error("Error: "+item.error);
											} else if (item.num!==0) {
												currencycode2 = item.value;
											}

											if (currencycode2!==currencycode1 && currencycode2!=="" && currencycode1!=="") {
						        				$(".multicurrency").show();
						        			} else {
												$(".multicurrency").hide();
											}
										}
									else {
										console.error("Error: Ajax url has returned an empty page. Should be an empty json array.");
									}
			        			}).fail(function( data ) {
									console.error("Error: has returned an empty page. Should be an empty json array.");
								});
							}
							else {
								console.error("Error: has returned an empty page. Should be an empty json array.");
							}
    	        	}).fail(function( data ) {
						console.error("Error: has returned an empty page. Should be an empty json array.");
					});
        		}

				init_page();
        	});
    		</script>';

$form=new Form($db);

$account_from='';
$account_to='';
$label='';
$amount='';

if ($error)
{
<<<<<<< HEAD
	$account_from =	GETPOST('account_from','int');
	$account_to	= GETPOST('account_to','int');
	$label = GETPOST('label','alpha');
	$amount = GETPOST('amount','alpha');
=======
	$account_from =	GETPOST('account_from', 'int');
	$account_to	= GETPOST('account_to', 'int');
	$label = GETPOST('label', 'alpha');
	$amount = GETPOST('amount', 'alpha');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
}

print load_fiche_titre($langs->trans("MenuBankInternalTransfer"), '', 'title_bank.png');

print $langs->trans("TransferDesc");
print "<br><br>";

print '<form name="add" method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

print '<input type="hidden" name="action" value="add">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("TransferFrom").'</td><td>'.$langs->trans("TransferTo").'</td><td>'.$langs->trans("Date").'</td><td>'.$langs->trans("Description").'</td><td>'.$langs->trans("Amount").'</td>';
print '<td style="display:none" class="multicurrency">'.$langs->trans("AmountToOthercurrency").'</td>';
print '</tr>';

$var=false;
print '<tr class="oddeven"><td>';
$form->select_comptes($account_from, 'account_from', 0, '', 1, '', empty($conf->multicurrency->enabled)?0:1);
print "</td>";

print "<td>\n";
$form->select_comptes($account_to, 'account_to', 0, '', 1, '', empty($conf->multicurrency->enabled)?0:1);
print "</td>\n";

print "<td>";
<<<<<<< HEAD
$form->select_date((! empty($dateo)?$dateo:''),'','','','','add');
=======
print $form->selectDate((! empty($dateo)?$dateo:''), '', '', '', '', 'add');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
print "</td>\n";
print '<td><input name="label" class="flat quatrevingtpercent" type="text" value="'.dol_escape_htmltag($label).'"></td>';
print '<td><input name="amount" class="flat" type="text" size="6" value="'.dol_escape_htmltag($amount).'"></td>';
print '<td style="display:none" class="multicurrency"><input name="amountto" class="flat" type="text" size="6" value="'.dol_escape_htmltag($amountto).'"></td>';

print "</table>";

print '<br><div class="center"><input type="submit" class="button" value="'.$langs->trans("Add").'"></div>';

print "</form>";

<<<<<<< HEAD
=======
// End of page
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
llxFooter();
$db->close();
