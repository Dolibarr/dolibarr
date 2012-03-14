<?php
/* Copyright (C) 2011-2012 Regis Houssin  <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *       \file       htdocs/core/ajax/saveinplace.php
 *       \brief      File to save field value
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1'); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');
//if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');

require('../../main.inc.php');
require_once(DOL_DOCUMENT_ROOT."/core/class/genericobject.class.php");

$field			= GETPOST('field','alpha');
$element		= GETPOST('element','alpha');
$table_element	= GETPOST('table_element','alpha');
$fk_element		= GETPOST('fk_element','alpha');

/*
 * View
 */

top_httphead();

//print '<!-- Ajax page called with url '.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].' -->'."\n";
//print_r($_POST);

// Load original field value
if (! empty($field) && ! empty($element) && ! empty($table_element) && ! empty($fk_element))
{
	$ext_element		= GETPOST('ext_element','alpha');
	$field				= substr($field, 8); // remove prefix val_
	$value				= GETPOST('value','alpha');
	$type				= GETPOST('type','alpha');
	$savemethod			= GETPOST('savemethod','alpha');
	$savemethodname		= (! empty($savemethod) ? $savemethod : 'setValueFrom');

	$view='';
	$format='text';
	$return=array();
	$error=0;

	if ($element != 'order_supplier' && $element != 'invoice_supplier' && preg_match('/^([^_]+)_([^_]+)/i',$element,$regs))
	{
		$element = $regs[1];
		$subelement = $regs[2];
	}

	if ($element == 'propal') $element = 'propale';
	else if ($element == 'fichinter') $element = 'ficheinter';
	else if ($element == 'product') $element = 'produit';
	else if ($element == 'order_supplier') {
		$element = 'fournisseur';
		$subelement = 'commande';
	}
	else if ($element == 'invoice_supplier') {
		$element = 'fournisseur';
		$subelement = 'facture';
	}

	if ($user->rights->$element->creer || $user->rights->$element->write
	|| (isset($subelement) && ($user->rights->$element->$subelement->creer || $user->rights->$element->$subelement->write))
	|| ($element == 'payment' && $user->rights->facture->paiement)
	|| ($element == 'payment_supplier' && $user->rights->fournisseur->facture->creer))
	{
		// Clean parameters
		$newvalue = trim($value);

		if ($type == 'numeric')
		{
			$newvalue = price2num($newvalue);

			// Check parameters
			if (! is_numeric($newvalue))
			{
				$error++;
				$return['error'] = $langs->trans('ErrorBadValue');
			}
		}
		else if ($type == 'datepicker')
		{
			$timestamp	= GETPOST('timestamp','int');
			$format		= 'date';
			$newvalue	= ($timestamp / 1000);
		}
		else if ($type == 'select')
		{
			$loadmethodname	= 'load_cache_'.GETPOST('loadmethod','alpha');
			$loadcachename	= 'cache_'.GETPOST('loadmethod','alpha');
			$loadviewname	= 'view_'.GETPOST('loadmethod','alpha');

			$form = new Form($db);
			if (method_exists($form, $loadmethodname))
			{
				$ret = $form->$loadmethodname();
				if ($ret > 0)
				{
					$loadcache = $form->$loadcachename;
					$value = $loadcache[$newvalue];
					
					if (! empty($form->$loadviewname))
					{
						$loadview = $form->$loadviewname;
						$view = $loadview[$newvalue];
					}
				}
				else
				{
					$error++;
					$return['error'] = $form->error;
				}
			}
			else
			{
				dol_include_once('/'.$ext_element.'/class/actions_'.$ext_element.'.class.php');
				$classname = 'Actions'.ucfirst($ext_element);
				$object = new $classname($db);
				$ret = $object->$loadmethodname();
				if ($ret > 0)
				{
					$loadcache = $object->$loadcachename;
					$value = $loadcache[$newvalue];
					
					if (! empty($object->$loadviewname))
					{
						$loadview = $object->$loadviewname;
						$view = $loadview[$newvalue];
					}
				}
				else
				{
					$error++;
					$return['error'] = $object->error;
				}
			}
		}

		if (! $error)
		{
			if (! is_object($object) || empty($savemethod)) $object = new GenericObject($db);

			// Specific for add_object_linked()
			// TODO add a function for variable treatment
			$object->ext_fk_element = $newvalue;
			$object->ext_element = $ext_element;
			$object->fk_element = $fk_element;
			$object->element = $element;
			
			$ret=$object->$savemethodname($field, $newvalue, $table_element, $fk_element, $format);
			if ($ret > 0)
			{
				if ($type == 'numeric') $value = price($newvalue);
				else if ($type == 'textarea') $value = dol_nl2br($newvalue);

				$return['value'] = $value;
				$return['view'] = (! empty($view) ? $view : $value);
			}
			else
			{
				$return['error'] = $object->error;
			}
		}

		echo json_encode($return);
	}
	else
	{
		echo $langs->trans('NotEnoughPermissions');
	}
}

?>
