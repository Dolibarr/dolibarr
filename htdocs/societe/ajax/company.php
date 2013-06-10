<?php
/* Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2007-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *       \file       htdocs/product/ajax/products.php
 *       \brief      File to return Ajax response on product list request
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL',1); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
if (! defined('NOCSRFCHECK'))    define('NOCSRFCHECK','1');
if (empty($_GET['keysearch']) && ! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');

require '../../main.inc.php';

$htmlname=GETPOST('htmlname','alpha');
$socid=GETPOST('socid','int');
$type=GETPOST('type','int');
$mode=GETPOST('mode','int');
$status=((GETPOST('status','int') >= 0) ? GETPOST('status','int') : -1);
$outjson=(GETPOST('outjson','int') ? GETPOST('outjson','int') : 0);
$price_level=GETPOST('price_level','int');
$action=GETPOST('action', 'alpha');
$id=GETPOST('id', 'int');
$price_by_qty_rowid=GETPOST('pbq', 'int');

/*
 * View
 */

//print '<!-- Ajax page called with url '.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].' -->'."\n";

dol_syslog(join(',',$_GET));
//print_r($_GET);

if (! empty($action) && $action == 'fetch' && ! empty($id))
{
	require DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

	$outjson=array();

	$object = new Societe($db);
	$ret=$object->fetch($id);
	if ($ret > 0)
	{
		$outname=$object->name;

		$outjson = array('name'=>$outname);
	}

	echo json_encode($outjson);
}
else
{
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

	$langs->load("products");
	$langs->load("main");

	top_httphead();

	if (empty($htmlname)) return;

	$match = preg_grep('/('.$htmlname.'[0-9]+)/',array_keys($_GET));
	sort($match);
	$id = (! empty($match[0]) ? $match[0] : '');

	if (! GETPOST($htmlname) && ! GETPOST($id)) return;

	// When used from jQuery, the search term is added as GET param "term".
	$searchkey=(GETPOST($id)?GETPOST($id):(GETPOST($htmlname)?GETPOST($htmlname):''));

	$form = new Form($db);
	if (empty($mode) || $mode == 'customer')
	{
		$arrayresult=$form->select_company_html($socid,$htmlname,"client IN (1,3)",0,0,0,null,$searchkey,$outjson);
	}
	elseif ($mode == 'supplier')
	{
		$arrayresult=$form->select_company_html($socid,$htmlname,"fournisseur=1",0,0,0,null,$searchkey,$outjson);
	}

	$db->close();

	if ($outjson) print json_encode($arrayresult);
}

?>