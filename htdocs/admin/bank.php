<?php
/* Copyright (C) 2009       Laurent Destailleur        <eldy@users.sourceforge.net>
 * Copyright (C) 2010-2016  Juanjo Menent	       <jmenent@2byte.es>
 * Copyright (C) 2013-2014  Philippe Grand             <philippe.grand@atoo-net.com>
 * Copyright (C) 2015       Jean-François Ferry         <jfefe@aternatik.fr>
 * Copyright (C) 2016       Neil Orley			<neil.orley@oeris.fr>
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
 *      \file       htdocs/admin/bank.php
 *		\ingroup    bank
 *		\brief      Page to setup the bank module
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

$langs->load("admin");
$langs->load("companies");
$langs->load("bills");
$langs->load("other");
$langs->load("banks");

if (!$user->admin)
  accessforbidden();

$action = GETPOST('action','alpha');

// Other parameters BANK_*
$list = array (
		'BANK_EXPORT_SEPARATOR'
);

/*
 * Actions
 */
if ($action == 'update') {
	$error = 0;

	foreach ($list as $constname) {
		$constvalue = GETPOST($constname, 'alpha');

		if (! dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, '', $conf->entity)) {
			$error ++;
		}
	}

	if (! $error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

/*
 * Actions
 */

//Order display of bank account
if ($action == 'setbankorder')
{
	if (dolibarr_set_const($db, "BANK_SHOW_ORDER_OPTION",GETPOST('value','alpha'),'chaine',0,'',$conf->entity) > 0)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
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

llxHeader("",$langs->trans("BankSetupModule"));

$form=new Form($db);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("BankSetupModule"),$linkback,'title_setup');

$head = bank_admin_prepare_head(null);
dol_fiche_head($head, 'general', $langs->trans("BankSetupModule"), 0, 'account');

$var=true;

$var=! $var;

//Show bank account order
print load_fiche_titre($langs->trans("BankOrderShow"));

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
$bankorder[0][2]='BankCode DeskCode BankAccountNumber BankAccountNumberKey';
$bankorder[1][0]=$langs->trans("BankOrderES");
$bankorder[1][1]=$langs->trans("BankOrderESDesc");
$bankorder[1][2]='BankCode DeskCode BankAccountNumberKey BankAccountNumber';

$var = true;
$i=0;

$nbofbank=count($bankorder);
while ($i < $nbofbank)
{
	$var = !$var;

	print '<tr '.$bc[$var].'>';
	print '<td>'.$bankorder[$i][0]."</td><td>\n";
	print $bankorder[$i][1];
	print '</td>';
	print '<td class="nowrap">';
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
	print '</tr>'."\n";
	$i++;
}

print '</table>'."\n";

dol_fiche_end();



//Show Export separator
print load_fiche_titre($langs->trans("ExportOptions"));

print '<form action="' . $_SERVER["PHP_SELF"] . '" method="post">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="update">';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Name') . '</td>';
print '<td >' . $langs->trans('Value') . '</td>';
print "</tr>\n";

foreach ( $list as $key ) {
	$var = ! $var;

	print '<tr ' . $bc[$var] . ' class="value">';

	// Key
	print '<td width="50%"><label for="' . $key . '">' . $langs->trans('ExtrafieldSeparator') . ' ('.$key.')</label></td>';
	// Value
	print '<td><input type="text" size="20" id="' . $key . '" name="' . $key . '" value="' . ( empty($conf->global->$key) ? ';' : $conf->global->$key ) . '"></td>';

	print '</tr>';
}

//Save
print '<tr>';
print '<td colspan="2">';
print '<div class="right"><input type="submit" class="button" value="' . $langs->trans('Save') . '" name="button"></div>';
print '</td>';
print '</tr>';

print "</table>\n";


dol_fiche_end();

print "</form>";




llxFooter();

$db->close();
