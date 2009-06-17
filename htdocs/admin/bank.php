<?php
/* Copyright (C) 2009 Laurent Destailleur          <eldy@users.sourceforge.net>
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
 */


/**
 *      \file       htdocs/admin/bank.php
 *		\ingroup    bank
 *		\brief      Page to setup the bank module
 *		\version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/facture.class.php');

$langs->load("admin");
$langs->load("companies");
$langs->load("bills");
$langs->load("other");

if (!$user->admin)
  accessforbidden();

$typeconst=array('yesno','texte','chaine');


/*
 * Actions
 */

if ($_POST["action"] == 'set_BANK_CHEQUERECEIPT_FREE_TEXT')
{
    dolibarr_set_const($db, "BANK_CHEQUERECEIPT_FREE_TEXT",trim($_POST["BANK_CHEQUERECEIPT_FREE_TEXT"]),'chaine',0,'',$conf->entity);
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
print '<td>'.$langs->trans("Parameter").'</td>';
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
print '<textarea name="BANK_CHEQUERECEIPT_FREE_TEXT" class="flat" cols="100">'.$conf->global->BANK_CHEQUERECEIPT_FREE_TEXT.'</textarea>';
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td></tr>\n";
print '</form>';

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

print '</table>';



$db->close();

llxFooter('$Date$ - $Revision$');
?>
