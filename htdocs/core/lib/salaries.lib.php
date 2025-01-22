<?php
/**
 * Copyright (C) 2015	    Charlie BENKE           <charlie@patas-monkey.com>
 * Copyright (C) 2019	    Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2021		Gauthier VERDOL         <gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2023-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
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
 * or see https://www.gnu.org/
 */

/**
 * Returns an array with the tabs for the "salaries" section
 * It loads tabs from modules looking for the entity salaries
 *
 * @param Salary $object Current salaries object
 * @return	array<array{0:string,1:string,2:string}>	Tabs for the salaries section
 */
function salaries_prepare_head($object)
{
	global $db, $langs, $conf;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/salaries/card.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Salary");
	$head[$h][2] = 'card';
	$h++;

	if (isModEnabled('paymentbybanktransfer')) {
		$nbStandingOrders = 0;
		$sql = "SELECT COUNT(pfd.rowid) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_demande as pfd";
		$sql .= " WHERE pfd.fk_salary = ".((int) $object->id);
		$sql .= " AND type = 'ban'";
		$resql = $db->query($sql);
		if ($resql) {
			$obj = $db->fetch_object($resql);
			if ($obj) {
				$nbStandingOrders = $obj->nb;
			}
		} else {
			dol_print_error($db);
		}
		$langs->load("banks");
		$head[$h][0] = DOL_URL_ROOT.'/salaries/virement_request.php?id='.$object->id.'&type=bank-transfer';
		$head[$h][1] = $langs->trans('BankTransfer');
		if ($nbStandingOrders > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbStandingOrders.'</span>';
		}
		$head[$h][2] = 'request_virement';
		$h++;
	}

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'salaries', 'add', 'core');

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->salaries->dir_output."/".dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = DOL_URL_ROOT.'/salaries/document.php?id='.$object->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles + $nbLinks) > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
	}
	$head[$h][2] = 'documents';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/salaries/info.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'salaries', 'add', 'external');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'salaries', 'remove');

	return $head;
}

/**
 *  Return array head with list of tabs to view object information
 *
 * @return	array<array{0:string,1:string,2:string}>	Array of tabs to show
 */
function salaries_admin_prepare_head()
{
	global $conf, $db, $langs, $user;

	$extrafields = new ExtraFields($db);
	$extrafields->fetch_name_optionals_label('salary');

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/salaries/admin/salaries.php';
	$head[$h][1] = $langs->trans("Miscellaneous");
	$head[$h][2] = 'general';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'salaries_admin');

	$head[$h][0] = DOL_URL_ROOT.'/salaries/admin/salaries_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFieldsSalaries");
	$nbExtrafields = $extrafields->attributes['salary']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'attributes';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'salaries_admin', 'remove');

	return $head;
}
