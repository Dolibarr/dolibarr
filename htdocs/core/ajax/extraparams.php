<?php
/* Copyright (C) 2012 Regis Houssin  <regis@dolibarr.fr>
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
 *	\file       /htdocs/core/ajax/showhide.php
 *	\brief      File to return Ajax response on set show/hide element
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1'); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');

include("../../main.inc.php");

$id = GETPOST('id','int');
$element = GETPOST('element','alpha');
$htmlelement = GETPOST('htmlelement','alpha');
$type = GETPOST('type', 'alpha');

/*
 * View
 */

top_httphead();

print '<!-- Ajax page called with url '.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].' -->'."\n";

// Registering the location of boxes
if(! empty($id) && ! empty($element) && ! empty($htmlelement) && ! empty($type))
{
	$value = GETPOST('value','alpha');
	$params=array();
	
	dol_syslog("AjaxSetExtraParameters id=".$id." element=".$element." htmlelement=".$htmlelement." type=".$type." value=".$value, LOG_DEBUG);
	
	// For compatibility
	if ($element == 'order' || $element == 'commande')    { $element = $subelement = 'commande'; }
	if ($element == 'propal')   { $element = 'comm/propal'; $subelement = 'propal'; }
	if ($element == 'facture')	{ $element = 'compta/facture'; $subelement = 'facture'; }
	if ($element == 'contract') { $element = $subelement = 'contrat'; }
	if ($element == 'shipping') { $element = $subelement = 'expedition'; }
	
	dol_include_once('/'.$element.'/class/'.$subelement.'.class.php');
	
	$classname	= ucfirst($subelement);
	$object		= new $classname($db);
	$object->id	= $id;
	
	$params[$htmlelement] = array($type => $value);
	$result=$object->setExtraParameters($params);
}

?>
