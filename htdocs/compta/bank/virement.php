<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * $Id$
 */

/**
		\file       htdocs/compta/bank/virement.php
		\ingroup    banque
		\brief      Page de saisie d'un virement
		\version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/bank.lib.php");

$langs->load("banks");

$user->getrights('banque');

if (! $user->rights->banque->transfer)
  accessforbidden();


/*
 * Action ajout d'un virement
 */
if ($_POST["action"] == 'add')
{
	$langs->load("errors");
	
	$mesg='';
	$dateo = dolibarr_mktime(12,0,0,$_POST["remonth"],$_POST["reday"],$_POST["reyear"]);
	$label = $_POST["label"];
	$amount= $_POST["amount"];

	if (! $label)
	{
		$error=1;	
		$mesg.="<div class=\"error\">".$langs->trans("ErrorFieldRequired",$langs->transnoentities("Label"))."</div>";
	}
	if (! $amount)
	{
		$error=1;	
		$mesg.="<div class=\"error\">".$langs->trans("ErrorFieldRequired",$langs->transnoentities("Amount"))."</div>";
	}
	if (! $_POST['account_from'])
	{
		$error=1;	
		$mesg.="<div class=\"error\">".$langs->trans("ErrorFieldRequired",$langs->transnoentities("TransferFrom"))."</div>";
	}
	if (! $_POST['account_to'])
	{
		$error=1;	
		$mesg.="<div class=\"error\">".$langs->trans("ErrorFieldRequired",$langs->transnoentities("TransferTo"))."</div>";
	}
	if (! $error)
	{
		require_once(DOL_DOCUMENT_ROOT.'/compta/bank/account.class.php');

		$accountfrom=new Account($db);
		$accountfrom->fetch($_POST["account_from"]);

		$accountto=new Account($db);
		$accountto->fetch($_POST["account_to"]);

		if ($accountto->id != $accountfrom->id)
		{
			$db->begin();
			
			$bank_line_id_from = $accountfrom->addline($dateo, 'VIR', $label, -1*price2num($amount), '', '', $user);
			$bank_line_id_to = $accountto->addline($dateo, 'VIR', $label, price2num($amount), '', '', $user);
	
	        $result1=$accountfrom->add_url_line($bank_line_id_from, $bank_line_id_to, DOL_URL_ROOT.'/compta/bank/ligne.php?rowid=', '(banktransfert)', 'banktransfert');
	        $result2=$accountto->add_url_line($bank_line_id_to, $bank_line_id_from, DOL_URL_ROOT.'/compta/bank/ligne.php?rowid=', '(banktransfert)', 'banktransfert');
	
			if ($result1 > 0 && $result2 > 0)
			{
				$mesg.="<div class=\"ok\">";
				$mesg.=$langs->trans("TransferFromToDone","<a href=\"account.php?account=".$accountfrom->id."\">".$accountfrom->label."</a>","<a href=\"account.php?account=".$accountto->id."\">".$accountto->label."</a>",$amount,$langs->transnoentities("Currency".$conf->monnaie));
				$mesg.="</div>";
				$db->commit();
			}
			else
			{
				$mesg.="<div class=\"error\">".$accountfrom->error.' '.$accountto->error."</div>";
				$db->rollback();
			}
		}
		else
		{
			$mesg.="<div class=\"error\">".$langs->trans("ErrorFromToAccountsMustDiffers")."</div>";
		}
	}
}



/*
 * Affichage
 */
 
llxHeader();

$html=new Form($db);


print_titre($langs->trans("BankTransfer"));
print '<br>';

if ($mesg) {
    print "$mesg<br>";
}

print $langs->trans("TransferDesc");
print "<br><br>";

print "<form name='add' method=\"post\" action=\"virement.php\">";

print '<input type="hidden" name="action" value="add">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("TransferFrom").'</td><td>'.$langs->trans("TransferTo").'</td><td>'.$langs->trans("Date").'</td><td>'.$langs->trans("Description").'</td><td>'.$langs->trans("Amount").'</td>';
print '</tr>';

$var=false;
print '<tr '.$bc[$var].'><td>';
print $html->select_comptes($_POST['account_from'],'account_from',0,'',1);
print "</td>";

print "<td>\n";
print $html->select_comptes($_POST['account_to'],'account_to',0,'',1);
print "</td>\n";

print "<td>";
$html->select_date($dateo,'','','','','add');
print "</td>\n";
print '<td><input name="label" class="flat" type="text" size="40" value="'.$_POST["label"].'"></td>';
print '<td><input name="amount" class="flat" type="text" size="8" value="'.$_POST["amount"].'"></td>';

print "</table>";

print '<br><center><input type="submit" class="button" value="'.$langs->trans("Add").'"></center>';

print "</form>";

$db->close();

llxFooter('$Date$ - $Revision$');
?>
