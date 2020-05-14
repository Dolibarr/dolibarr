<?php
/*
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
 *       \file       htdocs/core/ajax/objectonoff.php
 *       \brief      File to set status for an object
 *       			 This Ajax service is called when option MAIN_DIRECT_STATUS_UPDATE is set.
 */

if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', '1'); // Disables token renewal
if (!defined('NOREQUIREMENU'))  define('NOREQUIREMENU', '1');
if (!defined('NOREQUIREHTML'))  define('NOREQUIREHTML', '1');
if (!defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX', '1');
if (!defined('NOREQUIRESOC'))   define('NOREQUIRESOC', '1');
if (!defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN', '1');

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/genericobject.class.php';

$action = GETPOST('action', 'alpha');
$id = GETPOST('id', 'int');
$value = GETPOST('value', 'int');
$field = GETPOST('field', 'alpha');
$element = GETPOST('element', 'alpha');

$object = new GenericObject($db);

// Security check
if (!empty($user->socid)) {
	$socid = $user->socid;
}

if (empty($conf->global->MAIN_DIRECT_STATUS_UPDATE)) {
	accessforbidden('Calling this file is allowed only when MAIN_DIRECT_STATUS_UPDATE is set');
}


/*
 * View
 */

top_httphead();

print '<!-- Ajax page called with url '.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]).' -->'."\n";

if (in_array($field, array('status'))) {
	$result = restrictedArea($user, $element, $id);
}
elseif ($element == 'product' && in_array($field, array('tosell', 'tobuy', 'tobatch'))) {	// Special case for products
	$result = restrictedArea($user, 'produit|service', $id, 'product&product', '', '', 'rowid');
}
else {
	accessforbidden("Bad value for combination of parameters element/field.", 0, 0, 1);
	exit;
}

// Registering new values
if (($action == 'set') && !empty($id)) {
	$triggerkey = strtoupper($element).'_UPDATE';
	// Special case
	if ($triggerkey == 'SOCIETE_UPDATE') {
		$triggerkey = 'COMPANY_UPDATE';
	}

	$tablename = $element;
	if ($tablename == 'websitepage') $tablename = 'website_page';

	$format = 'int';

	$object->setValueFrom($field, $value, $tablename, $id, $format, '', $user, $triggerkey);
}
