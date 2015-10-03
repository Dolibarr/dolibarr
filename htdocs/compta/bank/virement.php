<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2015 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2012	   Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
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
 *		\file       htdocs/compta/bank/virement.php
 *		\ingroup    banque
 *		\brief      Page de saisie d'un virement
 */

require('../../main.inc.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

$langs->load("banks");
$langs->load("categories");

if (! $user->rights->banque->transfer)
  accessforbidden();

$action = GETPOST('action','alpha');


/*
 * Actions
 */

if ($action == 'add')
{
	$langs->load("errors");

	$dateo = dol_mktime(12,0,0,GETPOST('remonth','int'),GETPOST('reday','int'),GETPOST('reyear','int'));
	$label = GETPOST('label','alpha');
	$amount= GETPOST('amount');

	if (! $label)
	{
		$error=1;
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("Description")), 'errors');
	}
	if (! $amount)
	{
		$error=1;
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("Amount")), 'errors');
	}
	if (! GETPOST('account_from','int'))
	{
		$error=1;
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("TransferFrom")), 'errors');
	}
	if (! GETPOST('account_to','int'))
	{
		$error=1;
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("TransferTo")), 'errors');
	}
	if (! $error)
	{
		require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

		$accountfrom=new Account($db);
		$accountfrom->fetch(GETPOST('account_from','int'));

		$accountto=new Account($db);
		$accountto->fetch(GETPOST('account_to','int'));

		if (($accountto->id != $accountfrom->id) && ($accountto->currency_code == $accountfrom->currency_code))
		{
			$db->begin();

			$error=0;
			$bank_line_id_from=0;
			$bank_line_id_to=0;
			$result=0;

			// By default, electronic transfert from bank to bank
			$typefrom='PRE';
			$typeto='VIR';
			if ($accountto->courant == 2 || $accountfrom->courant == 2)
			{
				// This is transfert of change
				$typefrom='LIQ';
				$typeto='LIQ';
			}

			if (! $error) $bank_line_id_from = $accountfrom->addline($dateo, $typefrom, $label, -1*price2num($amount), '', '', $user);
			if (! ($bank_line_id_from > 0)) $error++;
			if (! $error) $bank_line_id_to = $accountto->addline($dateo, $typeto, $label, price2num($amount), '', '', $user);
			if (! ($bank_line_id_to > 0)) $error++;

		    if (! $error) $result=$accountfrom->add_url_line($bank_line_id_from, $bank_line_id_to, DOL_URL_ROOT.'/compta/bank/ligne.php?rowid=', '(banktransfert)', 'banktransfert');
			if (! ($result > 0)) $error++;
		    if (! $error) $result=$accountto->add_url_line($bank_line_id_to, $bank_line_id_from, DOL_URL_ROOT.'/compta/bank/ligne.php?rowid=', '(banktransfert)', 'banktransfert');
			if (! ($result > 0)) $error++;

			if (! $error)
			{
				$mesgs = $langs->trans("TransferFromToDone","<a href=\"account.php?account=".$accountfrom->id."\">".$accountfrom->label."</a>","<a href=\"account.php?account=".$accountto->id."\">".$accountto->label."</a>",$amount,$langs->transnoentities("Currency".$conf->currency));
				setEventMessage($mesgs);
				$db->commit();
			}
			else
			{
				setEventMessage($accountfrom->error.' '.$accountto->error, 'errors');
				$db->rollback();
			}
		}
		else
		{
			setEventMessage($langs->trans("ErrorFromToAccountsMustDiffers"), 'errors');
		}
	}
}



/*
 * Affichage
 */

llxHeader();

$form=new Form($db);

$account_from='';
$account_to='';
$label='';
$amount='';

if($error)
{
	$account_from =	GETPOST('account_from','int');
	$account_to	= GETPOST('account_to','int');
	$label = GETPOST('label','alpha');
	$amount = GETPOST('amount','int');
}

print load_fiche_titre($langs->trans("BankTransfer"), '', 'title_bank.png');

print $langs->trans("TransferDesc");
print "<br><br>";

print "<form name='add' method=\"post\" action=\"virement.php\">";
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

print '<input type="hidden" name="action" value="add">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("TransferFrom").'</td><td>'.$langs->trans("TransferTo").'</td><td>'.$langs->trans("Date").'</td><td>'.$langs->trans("Description").'</td><td>'.$langs->trans("Amount").'</td>';
print '</tr>';

$var=false;
print '<tr '.$bc[$var].'><td>';
$form->select_comptes($account_from,'account_from',0,'',1);
print "</td>";

print "<td>\n";
$form->select_comptes($account_to,'account_to',0,'',1);
print "</td>\n";

print "<td>";
$form->select_date((! empty($dateo)?$dateo:''),'','','','','add');
print "</td>\n";
print '<td><input name="label" class="flat" type="text" size="40" value="'.$label.'"></td>';
print '<td><input name="amount" class="flat" type="text" size="8" value="'.$amount.'"></td>';

print "</table>";

print '<br><div class="center"><input type="submit" class="button" value="'.$langs->trans("Add").'"></div>';

print "</form>";

$db->close();

llxFooter();
