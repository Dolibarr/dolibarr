<?php
/* Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2025       Laurent Destailleur     <eldy@users.sourceforge.net>
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
 *	\file       htdocs/core/ajax/fetchCategories.php
 *	\brief      File to fetch categories
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1'); // Disables token renewal
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
// Do not check anti CSRF attack test
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}

if (!defined('NOIPCHECK')) {
	define('NOIPCHECK', '1'); // Do not check IP defined into conf $dolibarr_main_restrict_ip
}
if (!defined('NOBROWSERNOTIF')) {
	define('NOBROWSERNOTIF', '1');
}
include '../../main.inc.php';
include_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

$action = GETPOST('action', 'aZ09');
$lang = GETPOST('lang', 'aZ09');

$type = GETPOST('type');

if (!$user->hasRight('categorie', 'lire')) {
	restrictedArea($user, 'categorie');
}
/*
 * Actions
 */

// None


/*
 * View
 */

$form = new Form($db);

top_httphead('application/json');

if ($action == "getCategories") {
	$response = array();

	$cate_arbo = $form->select_all_categories($type, '', '', 64, 0, 2);

	/*
	$sql = "SELECT c.rowid, c.label, c.color";
	$sql .= " FROM ".MAIN_DB_PREFIX."categorie as c ";
	$sql .= " WHERE c.type = '".$db->escape($type)."'";

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		$response = array();
		while ($i < $num) {
			$obj = $db->fetch_object($resql);
			$response[] = array('id' => $obj->rowid, 'label' => $obj->label, 'html' => img_picto('', 'tag', 'class="pictofixedwidth"'.($obj->color ? ' style="color: #'.$obj->color.'"' : '')).$obj->label, 'color' => $obj->color);
			$i++;
		}
	} else {
		dol_print_error($db);
	}
	*/
	foreach ($cate_arbo as $categ) {
		$response[] = array('id' => $categ['id'], 'label' => $categ['label'], 'fulllabel' => $categ['fulllabel'], 'htmlforoption' => dolPrintHTML((string) $categ['fulllabel']), 'htmlforattribute' => dolPrintHTMLForAttribute((string) $categ['data-html']), 'color' => $categ['color']);
	}
	$response =json_encode($response);
	echo $response;
}
