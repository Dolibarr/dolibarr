<?php
/* Copyright (C) 2009 Laurent Destailleur          <eldy@users.sourceforge.net>
 * Copyright (C) 2010 Juanjo Menent			       <jmenent@2byte.es>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */


/**
 *      \file       htdocs/compta/bank/admin/bank.php
 *		\ingroup    bank
 *		\brief      Page to setup the bank module
 *		\version    $Id: bank.php,v 1.8 2011/07/31 22:23:31 eldy Exp $
 */

require("../../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php');

$langs->load("admin");
$langs->load("companies");
$langs->load("bills");
$langs->load("other");
$langs->load("banks");

if (!$user->admin)
  accessforbidden();

$typeconst=array('yesno','texte','chaine');


/*
 * Actions
 */

if ($_POST["action"] == 'set_BANK_CHEQUERECEIPT_FREE_TEXT')
{
    dolibarr_set_const($db, "BANK_CHEQUERECEIPT_FREE_TEXT",$_POST["BANK_CHEQUERECEIPT_FREE_TEXT"],'chaine',0,'',$conf->entity);
}

//Order display of bank account
if ($_GET["action"] == 'setbankorder')
{
	if (dolibarr_set_const($db, "BANK_SHOW_ORDER_OPTION",$_GET["value"],'chaine',0,'',$conf->entity) > 0)
	{
		Header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

/*
 * view
 */

llxHeader("","");

$html=new Form($db);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("BankSetupModule"),$linkback,'setup');
print '<br>';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td>';
print '<td align="center" width="60">&nbsp;</td>';
print '<td width="80">&nbsp;</td>';
print "</tr>\n";
$var=true;

$var=! $var;
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_BANK_CHEQUERECEIPT_FREE_TEXT">';
print '<tr '.$bc[$var].'><td colspan="2">';
print $langs->trans("FreeLegalTextOnChequeReceipts").' ('.$langs->trans("AddCRIfTooLong").')<br>';
print '<textarea name="BANK_CHEQUERECEIPT_FREE_TEXT" class="flat" cols="120">'.$conf->global->BANK_CHEQUERECEIPT_FREE_TEXT.'</textarea>';
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td></tr>\n";
print '</table>';
print "<br>";

/*
$var=!$var;
print "<form method=\"post\" action=\"".$_SERVER["PHP_SELF"]."\">";
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print "<input type=\"hidden\" name=\"action\" value=\"set_BANK_CHEQUERECEIPT_DRAFT_WATERMARK\">";
print '<tr '.$bc[$var].'><td colspan="2">';
print $langs->trans("WatermarkOnDraftChequeReceipt").'<br>';
print '<input size="50" class="flat" type="text" name="BANK_CHEQUERECEIPT_DRAFT_WATERMARK" value="'.$conf->global->BANK_CHEQUERECEIPT_DRAFT_WATERMARK.'">';
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td></tr>\n";
print '</form>';
*/


//Show bank account order
print_titre($langs->trans("BankOrderShow"));

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="140">'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td>'.$langs->trans("Example").'</td>';
print '<td align="center">'.$langs->trans("Status").'</td>';
print '<td align="center" width="60">&nbsp;</td>';
print "</tr>\n";

$bankorder[0][0]=$langs->trans("BankOrderGlobal");
$bankorder[0][1]=$langs->trans("BankOrderGlobalDesc");
$bankorder[0][2]='BankCode DeskCode AccountNumber BankAccountNumberKey';
$bankorder[1][0]=$langs->trans("BankOrderES");
$bankorder[1][1]=$langs->trans("BankOrderESDesc");
$bankorder[1][2]='BankCode DeskCode BankAccountNumberKey AccountNumber';

$var = true;
$i=0;

while ($i < sizeof($bankorder))
{
	$var = !$var;

	print '<tr '.$bc[$var].'>';
	print '<td>'.$bankorder[$i][0]."</td><td>\n";
	print $bankorder[$i][1];
	print '</td>';
	print '<td nowrap="nowrap">';
	$tmparray=explode(' ',$bankorder[$i][2]);
	foreach($tmparray as $key => $val)
	{
	    if ($key > 0) print ', ';
	    print $langs->trans($val);
	}
	print "</td>\n";

	if ($conf->global->BANK_SHOW_ORDER_OPTION == $i)
	{
		print '<td align="center">';
		print img_picto($langs->trans("Activated"),'on');
		print '</td>';
	}
	else
	{
		print '<td align="center"><a href="'.$_SERVER['PHP_SELF'].'?action=setbankorder&amp;value='.$i.'">';
		print img_picto($langs->trans("Disabled"),'off');
		print '</a></td>';
	}
	print '<td>&nbsp;</td>';
	print "</tr>\n";
	$i++;
}

print "</table>\n";

$db->close();

llxFooter('$Date: 2011/07/31 22:23:31 $ - $Revision: 1.8 $');
?>
