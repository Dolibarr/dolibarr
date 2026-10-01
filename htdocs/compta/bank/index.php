<?php
/* Copyright (C) 2026		Frédéric France				<frederic.france@free.fr>
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
 *       \file       htdocs/compta/bank/index.php
 *       \ingroup    bank
 *       \brief      Home page for the Bank | Cash module (dashboard with widgets)
 */

// Load Dolibarr environment
require '../../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

// Initialize a technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(['bankindex']);

// Load translation files required by the page
$langs->loadLangs(['banks', 'bills', 'compta']);

// Security check
$result = restrictedArea($user, 'banque');

$max = getDolUserInt('MAIN_SIZE_SHORTLIST_LIMIT', getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT', 5));


/*
 * Actions
 */

if (GETPOST('addbox')) {
	// Add box (when submit is done from a form when ajax disabled)
	require_once DOL_DOCUMENT_ROOT.'/core/class/infobox.class.php';
	$zone = GETPOSTINT('areacode');
	$userid = GETPOSTINT('userid');
	$boxorder = GETPOST('boxorder', 'aZ09');
	$boxorder .= GETPOST('boxcombo', 'aZ09');
	$result = InfoBox::saveboxorder($db, $zone, $boxorder, $userid);
	if ($result > 0) {
		setEventMessages($langs->trans("BoxAdded"), null);
	}
}


/*
 * View
 */

$accountstatic = new Account($db);
$accountlinestatic = new AccountLine($db);

$title = $langs->trans('MenuBankCash');
$help_url = 'EN:Module_Banks_and_Cash|FR:Module_Banques_et_Caisses|ES:Módulo_Bancos_y_Cajas|DE:Modul_Banken_und_Barvermögen';

// Load $resultboxes
$resultboxes = FormOther::getBoxesArea($user, "29");

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-bank page-index');

print load_fiche_titre($langs->trans("BankArea"), $resultboxes['selectboxlist'], 'bank_account');


print '<div class="fichecenter">';

print '<div class="twocolumns">';

print '<div class="firstcolumn fichehalfleft boxhalfleft" id="boxhalfleft">';


/*
 * Open accounts with their balance
 */

$sql = "SELECT b.rowid, b.ref, b.label, b.number, b.courant, b.currency_code, b.account_number";
$sql .= ", SUM(bl.amount) as solde";
$sql .= " FROM ".MAIN_DB_PREFIX."bank_account as b";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank as bl ON bl.fk_account = b.rowid";
$sql .= " WHERE b.entity IN (".getEntity('bank_account').")";
$sql .= " AND b.clos = 0";
$sql .= " GROUP BY b.rowid, b.ref, b.label, b.number, b.courant, b.currency_code, b.account_number";
$sql .= $db->order("b.label", "ASC");

$resql = $db->query($sql);
if ($resql) {
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<th colspan="2">'.$langs->trans("BankAccounts");
	$lastmodified = '<a href="'.DOL_URL_ROOT.'/compta/bank/list.php?search_status=opened" title="'.$langs->trans("FullList").'">';
	$lastmodified .= '<span class="badge marginleftonlyshort">...</span>';
	$lastmodified .= '</a>';
	print $lastmodified;
	print '</th>';
	print '<th class="right">'.$langs->trans("Balance").'</th>';
	print '</tr>';

	$num = $db->num_rows($resql);
	if ($num) {
		$solde_total = [];
		$i = 0;
		while ($i < $num) {
			$obj = $db->fetch_object($resql);

			$accountstatic->id = $obj->rowid;
			$accountstatic->ref = $obj->ref;
			$accountstatic->label = $obj->label;
			$accountstatic->number = $obj->number;
			$accountstatic->account_number = $obj->account_number;
			$accountstatic->currency_code = $obj->currency_code;
			$accountstatic->type = $obj->courant;

			$currency_code = ($obj->currency_code ? $obj->currency_code : $conf->currency);
			$solde = (float) price2num($obj->solde, 'MU');
			if (!array_key_exists($currency_code, $solde_total)) {
				$solde_total[$currency_code] = 0;
			}
			$solde_total[$currency_code] += $solde;

			print '<tr class="oddeven">';
			print '<td>'.$accountstatic->getNomUrl(1).'</td>';
			print '<td>'.dol_escape_htmltag($obj->number).'</td>';
			print '<td class="nowraponall right amount">';
			print '<a href="'.DOL_URL_ROOT.'/compta/bank/bankentries_list.php?id='.((int) $obj->rowid).'">';
			print '<span class="amount">';
			print price($solde, 0, $langs, 1, -1, -1, $currency_code);
			print '</span>';
			print '</a>';
			print '</td>';
			print '</tr>';
			$i++;
		}

		// Total per currency
		foreach ($solde_total as $currency_code => $total) {
			print '<tr class="liste_total">';
			print '<td class="liste_total" colspan="2">'.$langs->trans("Total").' ('.dol_escape_htmltag((string) $currency_code).')</td>';
			print '<td class="liste_total nowraponall right amount">'.price($total, 0, $langs, 1, -1, -1, $currency_code).'</td>';
			print '</tr>';
		}
	} else {
		print '<tr class="oddeven">';
		print '<td colspan="3"><span class="opacitymedium">'.$langs->trans("None").'</span></td>';
		print '</tr>';
	}
	print '</table>';
	print '</div>';
	print '<br>';
	$db->free($resql);
} else {
	dol_print_error($db);
}


print $resultboxes['boxlista'];

print '</div><div class="secondcolumn fichehalfright boxhalfright" id="boxhalfright">';


/*
 * Last modified bank transactions
 */

$sql = "SELECT bl.rowid, bl.label, bl.amount, bl.dateo, bl.datev, bl.tms as datem, bl.fk_account";
$sql .= ", b.rowid as bankid, b.ref as bankref, b.label as banklabel, b.currency_code";
$sql .= " FROM ".MAIN_DB_PREFIX."bank as bl";
$sql .= " INNER JOIN ".MAIN_DB_PREFIX."bank_account as b ON b.rowid = bl.fk_account";
$sql .= " WHERE b.entity IN (".getEntity('bank_account').")";
$sql .= $db->order("bl.tms", "DESC");
$sql .= $db->plimit($max, 0);

$resql = $db->query($sql);
if ($resql) {
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<th colspan="3">'.$langs->trans("LatestBankTransactionsModified", $max);
	$lastmodified = '<a href="'.DOL_URL_ROOT.'/compta/bank/bankentries_list.php?sortfield=b.tms&sortorder=DESC" title="'.$langs->trans("FullList").'">';
	$lastmodified .= '<span class="badge marginleftonlyshort">...</span>';
	$lastmodified .= '</a>';
	print $lastmodified;
	print '</th>';
	print '<th class="right">'.$langs->trans("Amount").'</th>';
	print '</tr>';

	$num = $db->num_rows($resql);
	if ($num) {
		$i = 0;
		while ($i < $num) {
			$obj = $db->fetch_object($resql);

			$accountlinestatic->id = $obj->rowid;
			$accountlinestatic->ref = (string) $obj->rowid;
			$accountlinestatic->label = $obj->label;
			$accountlinestatic->amount = $obj->amount;

			$accountstatic->id = $obj->bankid;
			$accountstatic->ref = $obj->bankref;
			$accountstatic->label = $obj->banklabel;
			$accountstatic->currency_code = $obj->currency_code;

			print '<tr class="oddeven">';
			print '<td class="nowraponall">'.$accountlinestatic->getNomUrl(1).'</td>';
			print '<td class="tdoverflowmax200" title="'.dol_escape_htmltag($obj->label).'">'.dol_escape_htmltag($obj->label).'</td>';
			print '<td class="nowraponall">'.$accountstatic->getNomUrl(1).'</td>';
			print '<td class="nowraponall right amount">'.price($obj->amount, 0, $langs, 1, -1, -1, $obj->currency_code).'</td>';
			print '</tr>';
			$i++;
		}
	} else {
		print '<tr class="oddeven">';
		print '<td colspan="4"><span class="opacitymedium">'.$langs->trans("None").'</span></td>';
		print '</tr>';
	}
	print '</table>';
	print '</div>';
	print '<br>';
	$db->free($resql);
} else {
	dol_print_error($db);
}


print $resultboxes['boxlistb'];

print '</div></div></div>';

$object = new stdClass();
$parameters = [
	'user' => $user,
];
$reshook = $hookmanager->executeHooks('dashboardBank', $parameters, $object);

// End of page
llxFooter();
$db->close();
