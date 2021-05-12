<?php
<<<<<<< HEAD
/* Copyright (C) 2011-2014 Regis Houssin  <regis.houssin@capnetworks.com>
=======
/* Copyright (C) 2011-2014 Regis Houssin  <regis.houssin@inodbox.com>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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

<<<<<<< HEAD
if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1'); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
=======
if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', '1'); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU', '1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX', '1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC', '1');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/genericobject.class.php';

<<<<<<< HEAD
$field			= GETPOST('field','alpha');
$element		= GETPOST('element','alpha');
$table_element	= GETPOST('table_element','alpha');
$fk_element		= GETPOST('fk_element','alpha');
=======
$field			= GETPOST('field', 'alpha');
$element		= GETPOST('element', 'alpha');
$table_element	= GETPOST('table_element', 'alpha');
$fk_element		= GETPOST('fk_element', 'alpha');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

/*
 * View
 */

top_httphead();

//print '<!-- Ajax page called with url '.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]).' -->'."\n";

// Load original field value
if (! empty($field) && ! empty($element) && ! empty($table_element) && ! empty($fk_element))
{
<<<<<<< HEAD
	$ext_element	= GETPOST('ext_element','alpha');
	$field			= substr($field, 8); // remove prefix val_
	$type			= GETPOST('type','alpha');
	$loadmethod		= (GETPOST('loadmethod','alpha') ? GETPOST('loadmethod','alpha') : 'getValueFrom');

	if ($element != 'order_supplier' && $element != 'invoice_supplier' && preg_match('/^([^_]+)_([^_]+)/i',$element,$regs))
=======
	$ext_element	= GETPOST('ext_element', 'alpha');
	$field			= substr($field, 8); // remove prefix val_
	$type			= GETPOST('type', 'alpha');
	$loadmethod		= (GETPOST('loadmethod', 'alpha') ? GETPOST('loadmethod', 'alpha') : 'getValueFrom');

	if ($element != 'order_supplier' && $element != 'invoice_supplier' && preg_match('/^([^_]+)_([^_]+)/i', $element, $regs))
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		$element = $regs[1];
		$subelement = $regs[2];
	}

	if ($element == 'propal') $element = 'propale';
<<<<<<< HEAD
	else if ($element == 'fichinter') $element = 'ficheinter';
	else if ($element == 'product') $element = 'produit';
	else if ($element == 'member') $element = 'adherent';
	else if ($element == 'order_supplier') {
		$element = 'fournisseur';
		$subelement = 'commande';
	}
	else if ($element == 'invoice_supplier') {
=======
	elseif ($element == 'fichinter') $element = 'ficheinter';
	elseif ($element == 'product') $element = 'produit';
	elseif ($element == 'member') $element = 'adherent';
	elseif ($element == 'order_supplier') {
		$element = 'fournisseur';
		$subelement = 'commande';
	}
	elseif ($element == 'invoice_supplier') {
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
<<<<<<< HEAD
			$cachename = 'cache_'.GETPOST('loadmethod','alpha');
=======
			$cachename = 'cache_'.GETPOST('loadmethod', 'alpha');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

			$form = new Form($db);
			if (method_exists($form, $methodname))
			{
				$ret = $form->$methodname();
				if ($ret > 0) echo json_encode($form->$cachename);
			}
<<<<<<< HEAD
			else if (! empty($ext_element))
			{
				$module = $subelement = $ext_element;
				if (preg_match('/^([^_]+)_([^_]+)/i',$ext_element,$regs))
=======
			elseif (! empty($ext_element))
			{
				$module = $subelement = $ext_element;
				if (preg_match('/^([^_]+)_([^_]+)/i', $ext_element, $regs))
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
<<<<<<< HEAD

=======
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
