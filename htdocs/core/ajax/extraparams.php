<?php
/* Copyright (C) 2012 Regis Houssin  <regis.houssin@inodbox.com>
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
 *	\file       /htdocs/core/ajax/extraparams.php
 *	\brief      File to make Ajax action on setting extra parameters of elements.
 *				Called bu bloc_showhide.tpl.php, itself called when MAIN_DISABLE_CONTACTS_TAB or MAIN_DISABLE_NOTES_TAB are set
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1'); // Disables token renewal
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
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

include '../../main.inc.php';

$id = GETPOSTINT('id');
$element = GETPOST('element', 'aZ09arobase');
$htmlelement = GETPOST('htmlelement', 'alpha');
$type = GETPOST('type', 'alpha');

// Load object according to $id and $element
$object = fetchObjectByElement($id, $element);

$module = $object->module;
$element = $object->element;
$usesublevelpermission = ($module != $element ? $element : '');
if ($usesublevelpermission && !$user->hasRight($module, $element)) {	// There is no permission on object defined, we will check permission on module directly
	$usesublevelpermission = '';
}

//print $object->id.' - '.$object->module.' - '.$object->element.' - '.$object->table_element.' - '.$usesublevelpermission."\n";

// Security check
$result = restrictedArea($user, $object->module, $object, $object->table_element, $usesublevelpermission, 'fk_soc', 'rowid', 0, 1);	// Call with mode return
if (!$result) {
	httponly_accessforbidden('Not allowed by restrictArea');
}


/*
 * View
 */

top_httphead();

print '<!-- Ajax page called with url '.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]).' -->'."\n";

if (!empty($id) && !empty($element) && !empty($htmlelement) && !empty($type)) {
	$value = GETPOST('value', 'alpha');
	$params = array();

	dol_syslog("AjaxSetExtraParameters id=".$id." element=".$element." htmlelement=".$htmlelement." type=".$type." value=".$value, LOG_DEBUG);

	if (is_object($object)) {
		$params[$htmlelement] = array($type => $value);
		$object->extraparams = array_merge($object->extraparams, $params);

		$result = $object->setExtraParameters();
	}
}
