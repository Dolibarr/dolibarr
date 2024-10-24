<?php
/* Copyright (C) 2013-2024  Alexandre Spangaro  <aspangaro@easya.solutions>
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
 *  \file       htdocs/accountancy/admin/fiscalyear.php
 *  \ingroup    Accountancy (Double entries)
 *  \brief      Setup page to configure fiscal year
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/fiscalyear.class.php';

$action = GETPOST('action', 'aZ09');

// Load variable for pagination
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) {
	$sortfield = "f.rowid"; // Set here default search field
}
if (!$sortorder) {
	$sortorder = "ASC";
}

// Load translation files required by the page
$langs->loadLangs(array("admin", "compta"));

$error = 0;
$errors = array();

// List of status
static $tmpstatut2label = array(
		'0' => 'OpenFiscalYear',
		'1' => 'CloseFiscalYear'
);

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$object = new Fiscalyear($db);
$hookmanager->initHooks(array('fiscalyearlist'));

// Security check
if ($user->socid > 0) {
	accessforbidden();
}
if (!$user->hasRight('accounting', 'fiscalyear', 'write')) {              // If we can read accounting records, we should be able to see fiscal year.
	accessforbidden();
}

/*
 * Actions
 */



/*
 * View
 */

$max = 100;

$form = new Form($db);
$fiscalyearstatic = new Fiscalyear($db);

$title = $langs->trans('AccountingPeriods');

$help_url = 'EN:Module_Double_Entry_Accounting#Setup|FR:Module_Comptabilit&eacute;_en_Partie_Double#Configuration';

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-accountancy page-admin_fiscalyear');

$sql = "SELECT f.rowid, f.label, f.date_start, f.date_end, f.statut as status, f.entity";
$sql .= " FROM ".MAIN_DB_PREFIX."accounting_fiscalyear as f";
$sql .= " WHERE f.entity = ".$conf->entity;
$sql .= $db->order($sortfield, $sortorder);

// Count total nb of records
$nbtotalofrecords = '';
if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
	if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller then paging size (filtering), goto and load page 0
		$page = 0;
		$offset = 0;
	}
}

$sql .= $db->plimit($limit + 1, $offset);

$result = $db->query($sql);
if ($result) {
	$num = $db->num_rows($result);
	$param = '';

	$parameters = array('param' => $param);
	$reshook = $hookmanager->executeHooks('addMoreActionsButtonsList', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if ($reshook < 0) {
		setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
	}

	$newcardbutton = empty($hookmanager->resPrint) ? '' : $hookmanager->resPrint;

	if (empty($reshook)) {
		$newcardbutton .= dolGetButtonTitle($langs->trans('NewFiscalYear'), '', 'fa fa-plus-circle', 'fiscalyear_card.php?action=create', '', $user->hasRight('accounting', 'fiscalyear', 'write'));
	}

	$title = $langs->trans('AccountingPeriods');
	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'calendar', 0, $newcardbutton, '', $limit, 1);

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste centpercent">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Ref").'</td>';
	print '<td>'.$langs->trans("Label").'</td>';
	print '<td>'.$langs->trans("DateStart").'</td>';
	print '<td>'.$langs->trans("DateEnd").'</td>';
	print '<td class="center">'.$langs->trans("NumberOfAccountancyEntries").'</td>';
	print '<td class="center">'.$langs->trans("NumberOfAccountancyMovements").'</td>';
	print '<td class="right">'.$langs->trans("Status").'</td>';
	print '</tr>';

	// Loop on record
	// --------------------------------------------------------------------
	$i = 0;
	if ($num) {
		while ($i < $num && $i < $max) {
			$obj = $db->fetch_object($result);

			$fiscalyearstatic->ref = $obj->rowid;
			$fiscalyearstatic->id = $obj->rowid;
			$fiscalyearstatic->date_start = $obj->date_start;
			$fiscalyearstatic->date_end = $obj->date_end;
			$fiscalyearstatic->statut = $obj->status;
			$fiscalyearstatic->status = $obj->status;

			print '<tr class="oddeven">';
			print '<td>';
			print $fiscalyearstatic->getNomUrl(1);
			print '</td>';
			print '<td class="left">'.$obj->label.'</td>';
			print '<td class="left">'.dol_print_date($db->jdate($obj->date_start), 'day').'</td>';
			print '<td class="left">'.dol_print_date($db->jdate($obj->date_end), 'day').'</td>';
			print '<td class="center">'.$object->getAccountancyEntriesByFiscalYear($obj->date_start, $obj->date_end).'</td>';
			print '<td class="center">'.$object->getAccountancyMovementsByFiscalYear($obj->date_start, $obj->date_end).'</td>';
			print '<td class="right">'.$fiscalyearstatic->LibStatut($obj->status, 5).'</td>';
			print '</tr>';
			$i++;
		}
	} else {
		print '<tr class="oddeven"><td colspan="7"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span></td></tr>';
	}
	print '</table>';
	print '</div>';
} else {
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
