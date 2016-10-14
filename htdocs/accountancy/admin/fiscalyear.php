<?php
/* Copyright (C) 2013-2016 Alexandre Spangaro   <aspangaro.dolibarr@gmail.com>
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
 * \file htdocs/accountancy/admin/fiscalyear.php
 * \ingroup fiscal year
 * \brief Setup page to configure fiscal year
 */
require '../../main.inc.php';

require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/fiscalyear.class.php';

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
if (! $sortfield) $sortfield="f.rowid"; // Set here default search field
if (! $sortorder) $sortorder="ASC";

$langs->load("admin");
$langs->load("compta");

// Security check
if ($user->societe_id > 0)
	accessforbidden();
if (! $user->rights->accounting->fiscalyear)
	accessforbidden();

$error = 0;

// List of status
static $tmpstatut2label = array (
		'0' => 'OpenFiscalYear',
		'1' => 'CloseFiscalYear' 
);
$statut2label = array (
		'' 
);
foreach ( $tmpstatut2label as $key => $val )
	$statut2label[$key] = $langs->trans($val);

$errors = array ();

$object = new Fiscalyear($db);

/*
 * Actions
 */



/*
 * View
 */

$max = 100;

$form = new Form($db);

$title = $langs->trans('FiscalYears');
$helpurl = "";
llxHeader('', $title, $helpurl);

$sql = "SELECT f.rowid, f.label, f.date_start, f.date_end, f.statut, f.entity";
$sql .= " FROM " . MAIN_DB_PREFIX . "accounting_fiscalyear as f";
$sql .= " WHERE f.entity = " . $conf->entity;
$sql.=$db->order($sortfield,$sortorder);

// Count total nb of records
$nbtotalofrecords = 0;
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
}	

$sql.= $db->plimit($limit+1, $offset);

$result = $db->query($sql);
if ($result) {
	$var = false;
	$num = $db->num_rows($result);
	
	$i = 0;

	$title = $langs->trans('FiscalYears');
	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $params, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'title_generic', 0, '', '', $limit, 1);
	
	// Load attribute_label
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>' . $langs->trans("Ref") . '</td>';
	print '<td>' . $langs->trans("Label") . '</td>';
	print '<td>' . $langs->trans("DateStart") . '</td>';
	print '<td>' . $langs->trans("DateEnd") . '</td>';
	print '<td align="right">' . $langs->trans("Statut") . '</td>';
	print '</tr>';
	
	if ($num) {
		$fiscalyearstatic = new Fiscalyear($db);
		
		while ( $i < $num && $i < $max ) {
			$obj = $db->fetch_object($result);
			$fiscalyearstatic->id = $obj->rowid;
			print '<tr ' . $bc[$var] . '>';
			print '<td><a href="fiscalyear_card.php?id=' . $obj->rowid . '">' . img_object($langs->trans("ShowFiscalYear"), "technic") . ' ' . $obj->rowid . '</a></td>';
			print '<td align="left">' . $obj->label . '</td>';
			print '<td align="left">' . dol_print_date($db->jdate($obj->date_start), 'day') . '</td>';
			print '<td align="left">' . dol_print_date($db->jdate($obj->date_end), 'day') . '</td>';
			print '<td align="right">' . $fiscalyearstatic->LibStatut($obj->statut, 5) . '</td>';
			print '</tr>';
			$var = ! $var;
			$i ++;
		}
	} else {
		print '<tr ' . $bc[$var] . '><td colspan="5" class="opacitymedium">' . $langs->trans("None") . '</td></tr>';
	}
	
	print '</table>';
} else {
	dol_print_error($db);
}

dol_fiche_end();

// Buttons
print '<div class="tabsAction">';
print '<a class="butAction" href="fiscalyear_card.php?action=create">' . $langs->trans("NewFiscalYear") . '</a>';
print '</div>';

llxFooter();
$db->close();