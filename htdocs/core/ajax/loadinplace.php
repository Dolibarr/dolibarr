<?php
/* Copyright (C) 2011-2014 Regis Houssin  <regis.houssin@inodbox.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *       \file       htdocs/core/ajax/loadinplace.php
 *       \brief      File to load field value. used only when option "Edit In Place" is set (MAIN_USE_JQUERY_JEDITABLE).
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

$field = GETPOST('field', 'alpha');
$element = GETPOST('element', 'alpha');
$table_element = GETPOST('table_element', 'alpha');
$fk_element = GETPOST('fk_element', 'alpha');
$id = $fk_element;

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

if (!getDolGlobalString('MAIN_USE_JQUERY_JEDITABLE')) {
	httponly_accessforbidden('Can be used only when option MAIN_USE_JQUERY_JEDITABLE is set');
}


/*
 * View
 */

top_httphead();

//print '<!-- Ajax page called with url '.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]).' -->'."\n";

// Load original field value
if (!empty($field) && !empty($element) && !empty($table_element) && !empty($fk_element)) {
	$ext_element	= GETPOST('ext_element', 'alpha');
	$field = substr($field, 8); // remove prefix val_
	$type = GETPOST('type', 'alpha');
	$loadmethod		= (GETPOST('loadmethod', 'alpha') ? GETPOST('loadmethod', 'alpha') : 'getValueFrom');

	if ($element != 'order_supplier' && $element != 'invoice_supplier' && preg_match('/^([^_]+)_([^_]+)/i', $element, $regs)) {
		$element = $regs[1];
		$subelement = $regs[2];
	}

	if ($element == 'propal') {
		$element = 'propale';
	} elseif ($element == 'fichinter') {
		$element = 'ficheinter';
	} elseif ($element == 'product') {
		$element = 'produit';
	} elseif ($element == 'member') {
		$element = 'adherent';
	} elseif ($element == 'order_supplier') {
		$element = 'fournisseur';
		$subelement = 'commande';
	} elseif ($element == 'invoice_supplier') {
		$element = 'fournisseur';
		$subelement = 'facture';
	}

	if ($user->hasRight($element, 'lire') || $user->hasRight($element, 'read')
	|| (isset($subelement) && ($user->hasRight($element, $subelement, 'lire') || $user->hasRight($element, $subelement, 'read')))
	|| ($element == 'payment' && $user->hasRight('facture', 'lire'))
	|| ($element == 'payment_supplier' && $user->hasRight('fournisseur', 'facture', 'lire'))) {
		if ($type == 'select') {
			$methodname = 'load_cache_'.$loadmethod;
			$cachename = 'cache_'.GETPOST('loadmethod', 'alpha');

			$form = new Form($db);
			if (method_exists($form, $methodname)) {
				$ret = $form->$methodname();
				if ($ret > 0) {
					echo json_encode($form->$cachename);
				}
			} elseif (!empty($ext_element)) {
				$module = $subelement = $ext_element;
				$regs = array();
				if (preg_match('/^([^_]+)_([^_]+)/i', $ext_element, $regs)) {
					$module = $regs[1];
					$subelement = $regs[2];
				}

				dol_include_once('/'.$module.'/class/actions_'.$subelement.'.class.php');
				$classname = 'Actions'.ucfirst($subelement);
				$object = new $classname($db);
				'@phan-var-force ActionsMulticompany|ActionsAdherentCardCommon|ActionsContactCardCommon|CommonHookActions|ActionsCardProduct|ActionsCardService|ActionsCardCommon $object';
				$ret = $object->$methodname($fk_element);
				if ($ret > 0) {
					echo json_encode($object->$cachename);
				}
			}
		} else {
			$object = new GenericObject($db);
			$value = $object->$loadmethod($table_element, $fk_element, $field);
			echo $value;
		}
	} else {
		echo $langs->transnoentities('NotEnoughPermissions');
	}
}
