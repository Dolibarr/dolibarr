<?php
/* Copyright (C) 2013-2014 Olivier Geffroy      <jeff@jeffinfo.com>
 * Copyright (C) 2013-2017 Alexandre Spangaro   <aspangaro@zendsi.com>
 * Copyright (C) 2014      Florian Henry        <florian.henry@open-concept.pro>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
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
 * \file        htdocs/accountancy/admin/importaccounts.php
 * \ingroup		Advanced accountancy
 * \brief 		Page import accounting account
 */
require '../../main.inc.php';

require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountingaccount.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formaccounting.class.php';

// Load translation files required by the page
$langs->loadLangs(array("compta","bills","accountancy"));

// Security check
if (! $user->admin)
	accessforbidden();

$limit = GETPOST('limit','int')?GETPOST('limit','int'):(empty($conf->global->ACCOUNTING_LIMIT_LIST_VENTILATION)?$conf->liste_limit:$conf->global->ACCOUNTING_LIMIT_LIST_VENTILATION);
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;




/*
 * View
 */

llxHeader('', $langs->trans("ImportAccount"));

$to_import = GETPOST("mesCasesCochees");

if ($_POST["action"] == 'import') {
	print '<div><font color="red">' . $langs->trans("Processing") . '...</font></div>';
	if (is_array($to_import) && count($to_import) > 0) {
		print '<div><font color="red">' . count($to_import) . ' ' . $langs->trans("SelectedLines") . '</font></div>';
		$sql = 'SELECT pcg_version FROM ' . MAIN_DB_PREFIX . 'accounting_system WHERE rowid=' . $conf->global->CHARTOFACCOUNTS;

		$result = $db->query($sql);
		if ($result && ($db->num_rows($result) > 0)) {

			$obj = $db->fetch_object($result);

			$cpt = 0;
			foreach ( $to_import as $maLigneCochee ) {

				$accounting = new AccountingAccount($db);

				$monLabel = (string) GETPOST('label' . $maLigneCochee);
				$monParentAccount = (string) GETPOST('AccountParent' . $maLigneCochee);
				$monType = (string) GETPOST('pcgType' . $maLigneCochee);
				$monSubType = (string) GETPOST('pcgSubType' . $maLigneCochee);

				$accounting->fk_pcg_version = $obj->pcg_version;
				$accounting->account_number = $maLigneCochee;
				$accounting->label = $monLabel;
				$accounting->account_parent = $monParentAccount;
				$accounting->pcg_type = $monType;
				$accounting->pcg_subtype = $monSubType;
				$accounting->active = 1;

				$result = $accounting->create($user);
				if ($result > 0) {
					setEventMessages($langs->trans("AccountingAccountAdd"), null, 'mesgs');
				} else {
					setEventMessages($accounting->error, $accounting->errors, 'errors');
				}
				$cpt ++;
			}
		} else {
			setEventMessages($langs->trans('AccountPlanNotFoundCheckSetting'), null, 'errors');
		}
	} else {
		print '<div><font color="red">' . $langs->trans("AnyLineImport") . '</font></div>';
	}
	print '<div><font color="red">' . $langs->trans("EndProcessing") . '</font></div>';
}

// list accounting account from product

$sql = "(SELECT p.rowid as product_id, p.accountancy_code_sell as accounting ";
$sql .= " FROM  " . MAIN_DB_PREFIX . "product as p ";
$sql .= " WHERE p.accountancy_code_sell >=0";
$sql .= " GROUP BY accounting ";
$sql .= ")";
$sql .= "UNION ALL(SELECT p.rowid as product_id, p.accountancy_code_buy as accounting ";
$sql .= " FROM  " . MAIN_DB_PREFIX . "product as p ";
$sql .= " WHERE p.accountancy_code_buy >=0";
$sql .= " GROUP BY accounting ";
$sql .= ") ";
$sql .= " ORDER BY accounting DESC " . $db->plimit($limit + 1, $offset);

dol_syslog('accountancy/admin/importaccounts.php:: $sql=' . $sql);
$result = $db->query($sql);
if ($result) {
	$num_lines = $db->num_rows($result);
	$i = 0;
	print_barre_liste($langs->trans("ImportAccount"), $page, $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, '', $num_lines);

	print '<form action="' . $_SERVER["PHP_SELF"] . '" method="POST">' . "\n";
	print '<input type="hidden" name="action" value="import">';

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td>' . $langs->trans("AccountAccouting") . '</td>';
	print '<td>' . $langs->trans("label") . '</td>';
	print '<td>' . $langs->trans("Accountparent") . '</td>';
	print '<td>' . $langs->trans("Pcgtype") . '</td>';
	print '<td>' . $langs->trans("Pcgsubtype") . '</td>';
	print '<td align="center">' . $langs->trans("Import") . '</td>';
	print '</tr>';

	$form = new Form($db);
	$formaccounting = new FormAccounting($db);

	while ( $i < min($num_lines, $limit) ) {
		$objp = $db->fetch_object($result);
		print '<tr class="oddeven">';

		print '<td align="left">';
		print $objp->accounting;
		print '</td>';

		print '<td align="left">';
		print '<input name="label" size="30" value="">';
		print '</td>';

		// Colonne choix du compte
		print '<td>';
		print $formaccounting->select_account($accounting->account_parent, 'AccountParent');
		print '</td>';

		print '<td>';
		print '<input type="text" name="pcgType" value="'.dol_escape_htmltag(isset($_POST['pcg_subtype'])?GETPOST('pcg_subtype','alpha'):$accounting->pcg_type).'">';
		print '</td>';

		print '<td>';
		print '<input type="text" name="pcgSubType" value="'.dol_escape_htmltag(isset($_POST['pcg_subtype'])?GETPOST('pcg_subtype','alpha'):$accounting->pcg_subtype).'">';
		print '</td>';

		// Colonne choix ligne a ventiler
		$checked = ('label' == 'O') ? ' checked' : '';

		print '<td align="center">';
		print '<input type="checkbox" name="mesCasesCochees[]" ' . $checked . ' value="' . $objp->accounting . '"/>';
		print '</td>';

		print '</tr>';
		$i ++;
	}

	print '<tr><td colspan="8">&nbsp;</td></tr><tr><td colspan="8" align="center"><input type="submit" class="butAction" value="' . $langs->trans("Import") . '"></td></tr>';

	print '</table>';
	print '</form>';
} else {
	print $db->error();
}

// End of page
llxFooter();
$db->close();
