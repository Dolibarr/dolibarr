<?php
/* Copyright (C) 2011 Regis Houssin  <regis@dolibarr.fr>
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
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');

require('../../main.inc.php');
require_once(DOL_DOCUMENT_ROOT."/core/class/genericobject.class.php");

/*
 * View
 */

top_httphead();

//print '<!-- Ajax page called with url '.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].' -->'."\n";
print_r($_POST);

// Load original field value
if((isset($_POST['field']) && ! empty($_POST['field']))
	&& (isset($_POST['element']) && ! empty($_POST['element']))
	&& (isset($_POST['table_element']) && ! empty($_POST['table_element']))
	&& (isset($_POST['fk_element']) && ! empty($_POST['fk_element'])))
{
	$element		= GETPOST('element');
	$table_element	= GETPOST('table_element');
	$field			= GETPOST('field');
	$fk_element		= GETPOST('fk_element');
	$value			= GETPOST('value');
	$type			= GETPOST('type');
	
	$return=array();
	$error=0;
	
	if ($element == 'fichinter') $element = 'ficheinter';
	
	if ($user->rights->$element->creer || $user->rights->$element->write)
	{
		$object = new GenericObject($db);
		
		// Clean parameters
		$value = trim($value);
		if ($type == 'numeric')
		{
			$value = price2num($value);
		
			// Check parameters
			if (! is_numeric($value))
			{
				$error++;
				$return['error'] = $langs->trans('ErrorBadValue');
			}
		}
		
		if (! $error)
		{
			$ret=$object->setValueFrom($table_element, $fk_element, $field, $value);
			if ($ret > 0)
			{
				if ($type == 'numeric') $value = price($value);
				else if ($type == 'textarea') $value = dol_nl2br($value);
				
				$return['value'] = $value;
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
