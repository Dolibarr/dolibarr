<?php
/* Copyright (C) 2017		Alexandre Spangaro   <aspangaro@zendsi.com>
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
 *
 */

/**
 * \file		htdocs/accountancy/admin/journals.php
 * \ingroup		Advanced accountancy
 * \brief		Setup page to configure journals
 */
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountingjournal.class.php';

$action = GETPOST('action');

// Load variable for pagination
$limit = GETPOST("limit")?GETPOST("limit","int"):$conf->liste_limit;
$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page = GETPOST('page','int');
if ($page == -1) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield="j.rowid"; // Set here default search field
if (! $sortorder) $sortorder="ASC";

$langs->load("admin");
$langs->load("compta");
$langs->load("accountancy");

// Security check
if ($user->societe_id > 0)
	accessforbidden();
if (! $user->rights->accounting->fiscalyear)              // If we can read accounting records, we shoul be able to see fiscal year.
    accessforbidden();
	
$error = 0;

// List of status
/*
static $tmptype2label = array (
		'0' => 'AccountingJournalTypeVariousOperation',
		'1' => 'AccountingJournalTypeSale',
		'2' => 'AccountingJournalTypePurchase',
		'3' => 'AccountingJournalTypeBank',
		'9' => 'AccountingJournalTypeHasNew'
);
$type2label = array (
		'' 
);
foreach ( $tmptype2label as $key => $val )
	$type2label[$key] = $langs->trans($val);
*/

$errors = array ();

$object = new AccountingJournal($db);


/*
 * Actions
 */



/*
 * View
 */
$title = $langs->trans('AccountingJournals');
$helpurl = "";
llxHeader('', $title, $helpurl);

$max = 100;
$form = new Form($db);

$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans('ConfigAccountingExpert'), $linkback, 'title_setup');

$head = admin_accounting_prepare_head(null);

dol_fiche_head($head, 'journal', $langs->trans("Configuration"), -1, 'cron');

$sql = "SELECT j.rowid, j.code, j.label, j.nature, j.active";
$sql .= " FROM " . MAIN_DB_PREFIX . "accounting_journal as j";
// $sql .= " WHERE j.entity = " . $conf->entity;
$sql.=$db->order($sortfield,$sortorder);

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
}

$sql.= $db->plimit($limit+1, $offset);

$result = $db->query($sql);
if ($result) {
	$num = $db->num_rows($result);

	$i = 0;

	// $title = $langs->trans('AccountingJournals');
	// print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $params, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'title_accountancy', 0, '', '', $limit, 1);

	// Load attribute_label
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	// print '<td>' . $langs->trans("Ref") . '</td>';
	print '<td>' . $langs->trans("Code") . '</td>';
	print '<td>' . $langs->trans("Label") . '</td>';
	print '<td>' . $langs->trans("Nature") . '</td>';
	print '</tr>';

	if ($num) {
		$accountingjournalstatic = new AccountingJournal($db);

		while ( $i < $num && $i < $max ) {
			$obj = $db->fetch_object($result);
			$accountingjournalstatic->id = $obj->rowid;
			print '<tr class="oddeven">';
			print '<td><a href="journals_card.php?id=' . $obj->rowid . '">' . img_object($langs->trans("ShowJournal"), "technic") . ' ' . $obj->code . '</a></td>';
			print '<td align="left">' . $obj->label . '</td>';
			print '<td>' . $accountingjournalstatic->LibType($obj->nature, 0) . '</td>';
			print '</tr>';
			$i ++;
		}
	} else {
		print '<tr class="oddeven"><td colspan="3" class="opacitymedium">' . $langs->trans("None") . '</td></tr>';
	}
	print '</table>';
} else {
	dol_print_error($db);
}

dol_fiche_end();

// Buttons
print '<div class="tabsAction">';
if (! empty($user->rights->accounting->fiscalyear))
{
    print '<a class="butAction" href="journals_card.php?action=create">' . $langs->trans("NewAccountingJournal") . '</a>';
}
else
{
    print '<a class="butActionRefused" href="#">' . $langs->trans("NewAccountingJournal") . '</a>';
}
print '</div>';

llxFooter();
$db->close();