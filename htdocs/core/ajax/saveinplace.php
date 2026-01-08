<?php
/* Copyright (C) 2011-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2024-2025	MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 *       \file       htdocs/core/ajax/saveinplace.php
 *       \brief      File to load or update field value. Was used in past when option "Edit In Place" is set (MAIN_USE_JQUERY_JEDITABLE).
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1'); // Disables token renewal
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/genericobject.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

$field = GETPOST('field', 'alpha', 2);
$element = GETPOST('element', 'alpha', 2);
$table_element = GETPOST('table_element', 'alpha', 2);
$fk_element = GETPOST('fk_element', 'alpha', 2);
$id = $fk_element;

/* Example:
field:editval_ref_customer (8 first chars will removed to know name of property)
element:contrat
table_element:contrat
fk_element:4
type:string
value:aaa
loadmethod:
savemethod:
savemethodname:
*/

print 'field='.$field.' - element='.$element.' - table_element='.$table_element.' - id/fk_element='.$fk_element."\n";

// Load object according to $id and $element
$element_ref = '';
if (is_numeric($fk_element)) {
	$id = (int) $fk_element;
} else {
	$element_ref = $fk_element;
	$id = 0;
}
$object = fetchObjectByElement($id, $element, $element_ref);
if (! is_object($object)) {
	httponly_accessforbidden('Not allowed, bad combination of parameters for fetchObjectByElement');
}

$module = $object->module;
$element = $object->element;
$usesublevelpermission = ($module != $element ? $element : '');
if ($usesublevelpermission && !$user->hasRight($module, $element)) {	// There is no permission on object defined, we will check permission on module directly
	$usesublevelpermission = '';
}
print 'object->id='.$object->id.' - object->module='.$object->module.' - object->element='.$object->element.' - object->table_element='.$object->table_element.' - usesublevelpermission='.$usesublevelpermission."\n";

// Security check
$result = restrictedArea($user, $object->module, $object, $object->table_element, $usesublevelpermission, 'fk_soc', 'rowid', 0, 1);	// Call with mode return
if (!$result) {
	httponly_accessforbidden('Not allowed by restrictArea');
}


/*
 * View
 */

top_httphead();

//print '<!-- Ajax page called with url '.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]).' -->'."\n";
//print_r($_POST);

// Load original field value
if (!empty($field) && !empty($element) && !empty($table_element) && !empty($fk_element)) {
	$field = preg_replace('/^editval_/', '', $field); 	// remove prefix "editval_"

	$type = GETPOST('type', 'alpha', 2);	// type string by default

	$value = ($type == 'ckeditor' ? GETPOST('value', '', 2) : GETPOST('value', 'alpha', 2));

	//$ext_element = GETPOST('ext_element', 'alpha', 2);
	$ext_element = 'notused';

	//$savemethod = GETPOST('savemethod', 'alpha', 2);
	//$savemethodname = (!empty($savemethod) ? $savemethod : 'setValueFrom');
	$savemethodname = 'setValueFrom';

	$newelement = $element;
	$subelement = null;

	$format = 'text';
	$return = array();
	$error = 0;

	$regs = array();
	if ($element != 'order_supplier' && $element != 'invoice_supplier' && preg_match('/^([^_]+)_([^_]+)/i', $element, $regs)) {
		$element = $regs[1];
		$subelement = $regs[2];
	}

	if ($element == 'propal') {
		$newelement = 'propale';
	} elseif ($element == 'fichinter') {
		$newelement = 'ficheinter';
	} elseif ($element == 'product') {
		$newelement = 'produit';
	} elseif ($element == 'member') {
		$newelement = 'adherent';
	} elseif ($element == 'order_supplier') {
		$newelement = 'fournisseur';
		$subelement = 'commande';
	} elseif ($element == 'invoice_supplier') {
		$newelement = 'fournisseur';
		$subelement = 'facture';
	} else {
		$newelement = $element;
	}

	$_POST['action'] = 'update'; // Keep this. It is a hack so restrictarea will test permissions on write too

	$feature = $newelement;
	$feature2 = $subelement;
	$object_id = $fk_element;
	if ($feature == 'expedition' || $feature == 'shipping') {
		$feature = 'commande';
		$object_id = 0;
	}
	if ($feature == 'shipping') {
		$feature = 'commande';
	}
	if ($feature == 'payment') {
		$feature = 'facture';
	}
	if ($feature == 'payment_supplier') {
		$feature = 'fournisseur';
		$feature2 = 'facture';
	}
	//var_dump(GETPOST('action','aZ09'));
	//var_dump($newelement.'-'.$subelement."-".$feature."-".$object_id);
	$check_access = restrictedArea($user, $feature, $object_id, '', (string) $feature2);
	//var_dump($user->rights);

	if ($check_access) {
		// Clean parameters
		$newvalue = trim($value);

		if ($type == 'numeric') {
			$newvalue = price2num($newvalue);

			// Check parameters
			if (!is_numeric($newvalue)) {
				$error++;
				$return['error'] = $langs->trans('ErrorBadValue');
			}
		} elseif ($type == 'datepicker') {
			$timestamp = GETPOSTINT('timestamp', 2);
			$format = 'date';
			$newvalue = ($timestamp / 1000);
		}

		if (!$error) {
			// Specific for add_object_linked()
			// TODO add a function for variable treatment
			$object->ext_fk_element = $newvalue;
			$object->ext_element = $ext_element;
			$object->fk_element = $fk_element;
			$object->element = $element;

			$ret = $object->$savemethodname($field, $newvalue, $table_element, (int) $fk_element, $format);
			if ($ret > 0) {
				if ($type == 'numeric') {
					$value = price($newvalue);
				} elseif ($type == 'textarea') {
					$value = dol_nl2br($newvalue);
				}

				$return['value'] = $value;
			} else {
				$return['error'] = $object->error;
			}
		}

		echo json_encode($return);
	} else {
		echo $langs->trans('NotEnoughPermissions');
	}
}
