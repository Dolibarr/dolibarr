<?php
/* Copyright (C) 2011-2014 Regis Houssin  <regis.houssin@inodbox.com>
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
 *       \file       htdocs/core/ajax/loadinplace.php
 *       \brief      File to load field value
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', '1'); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU', '1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX', '1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC', '1');

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/genericobject.class.php';

$field			= GETPOST('field', 'alpha');
$element		= GETPOST('element', 'alpha');
$table_element	= GETPOST('table_element', 'alpha');
$fk_element		= GETPOST('fk_element', 'alpha');

/*
 * View
 */

top_httphead();

//print '<!-- Ajax page called with url '.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]).' -->'."\n";

// Load original field value
if (! empty($field) && ! empty($element) && ! empty($table_element) && ! empty($fk_element))
{
	$ext_element	= GETPOST('ext_element', 'alpha');
	$field			= substr($field, 8); // remove prefix val_
	$type			= GETPOST('type', 'alpha');
	$loadmethod		= (GETPOST('loadmethod', 'alpha') ? GETPOST('loadmethod', 'alpha') : 'getValueFrom');

	if ($element != 'order_supplier' && $element != 'invoice_supplier' && preg_match('/^([^_]+)_([^_]+)/i', $element, $regs))
	{
		$element = $regs[1];
		$subelement = $regs[2];
	}

	if ($element == 'propal') $element = 'propale';
	elseif ($element == 'fichinter') $element = 'ficheinter';
	elseif ($element == 'product') $element = 'produit';
	elseif ($element == 'member') $element = 'adherent';
	elseif ($element == 'order_supplier') {
		$element = 'fournisseur';
		$subelement = 'commande';
	}
	elseif ($element == 'invoice_supplier') {
		$element = 'fournisseur';
		$subelement = 'facture';
	}

	if ($user->rights->$element->lire || $user->rights->$element->read
	|| (isset($subelement) && ($user->rights->$element->$subelement->lire || $user->rights->$element->$subelement->read))
	|| ($element == 'payment' && $user->rights->facture->lire)
	|| ($element == 'payment_supplier' && $user->rights->fournisseur->facture->lire))
	{
		if ($type == 'select')
		{
			$methodname	= 'load_cache_'.$loadmethod;
			$cachename = 'cache_'.GETPOST('loadmethod', 'alpha');

			$form = new Form($db);
			if (method_exists($form, $methodname))
			{
				$ret = $form->$methodname();
				if ($ret > 0) echo json_encode($form->$cachename);
			}
			elseif (! empty($ext_element))
			{
				$module = $subelement = $ext_element;
				if (preg_match('/^([^_]+)_([^_]+)/i', $ext_element, $regs))
				{
					$module = $regs[1];
					$subelement = $regs[2];
				}

				dol_include_once('/'.$module.'/class/actions_'.$subelement.'.class.php');
				$classname = 'Actions'.ucfirst($subelement);
				$object = new $classname($db);
				$ret = $object->$methodname($fk_element);
				if ($ret > 0) echo json_encode($object->$cachename);
			}
		}
		else
		{
			$object = new GenericObject($db);
			$value=$object->$loadmethod($table_element, $fk_element, $field);
			echo $value;
		}
	}
	else
	{
		echo $langs->transnoentities('NotEnoughPermissions');
	}
}
