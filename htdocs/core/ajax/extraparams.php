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
 *	\brief      File to make Ajax action on setting extra parameters of elements
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', '1'); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU', '1');
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML', '1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX', '1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC', '1');

include '../../main.inc.php';

$id = GETPOST('id', 'int');
$element = GETPOST('element', 'alpha');
$htmlelement = GETPOST('htmlelement', 'alpha');
$type = GETPOST('type', 'alpha');

/*
 * View
 */

top_httphead();

print '<!-- Ajax page called with url '.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]).' -->'."\n";

if(! empty($id) && ! empty($element) && ! empty($htmlelement) && ! empty($type))
{
	$value = GETPOST('value', 'alpha');
	$params=array();

	dol_syslog("AjaxSetExtraParameters id=".$id." element=".$element." htmlelement=".$htmlelement." type=".$type." value=".$value, LOG_DEBUG);

	$classpath = $subelement = $element;

	// For compatibility
	if ($element == 'order' || $element == 'commande')    { $classpath = $subelement = 'commande'; }
	elseif ($element == 'propal')				{ $classpath = 'comm/propal'; $subelement = 'propal'; }
	elseif ($element == 'facture')				{ $classpath = 'compta/facture'; $subelement = 'facture'; }
	elseif ($element == 'contract')			{ $classpath = $subelement = 'contrat'; }
	elseif ($element == 'shipping')			{ $classpath = $subelement = 'expedition'; }
	elseif ($element == 'deplacement')			{ $classpath = 'compta/deplacement'; $subelement = 'deplacement'; }
	elseif ($element == 'order_supplier')		{ $classpath = 'fourn'; $subelement = 'fournisseur.commande'; }
	elseif ($element == 'invoice_supplier')	{ $classpath = 'fourn'; $subelement = 'fournisseur.facture'; }

	dol_include_once('/'.$classpath.'/class/'.$subelement.'.class.php');

	if ($element == 'order_supplier')			{ $classname = 'CommandeFournisseur'; }
	elseif ($element == 'invoice_supplier')	{ $classname = 'FactureFournisseur'; }
	else $classname = ucfirst($subelement);

	$object	= new $classname($db);
	$object->fetch($id);

	$params[$htmlelement] = array($type => $value);
	$object->extraparams = array_merge($object->extraparams, $params);

	$result=$object->setExtraParameters();
}
