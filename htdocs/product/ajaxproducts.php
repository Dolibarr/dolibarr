<?php
/* Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2007-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *       \file       htdocs/product/ajaxproducts.php
 *       \brief      File to return Ajax response on product list request
 *       \version    $Id: ajaxproducts.php,v 1.37 2011/07/31 23:19:26 eldy Exp $
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL',1); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
if (! defined('NOCSRFCHECK'))    define('NOCSRFCHECK','1');
if (empty($_GET['keysearch']) && ! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');

require('../main.inc.php');
require_once(DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php');

$langs->load("products");
$langs->load("main");


/*
 * View
 */

// Ajout directives pour resoudre bug IE
//header('Cache-Control: Public, must-revalidate');
//header('Pragma: public');

//top_htmlhead("", "", 1);  // Replaced with top_httphead. An ajax page does not need html header.
top_httphead();

//print '<!-- Ajax page called with url '.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].' -->'."\n";

//print '<body class="nocellnopadd">'."\n";

dol_syslog(join(',',$_GET));
//print_r($_GET);

if (! isset($_GET['htmlname'])) return;

$htmlname = $_GET['htmlname'];
$match = preg_grep('/('.$htmlname.'[0-9]+)/',array_keys($_GET));
sort($match);
$idprod = $match[0];

if (! isset($_GET[$htmlname]) && ! isset($_GET[$idprod])) return;

// When used from jQuery, the search term is added as GET param "term".
$searchkey=$_GET[$idprod];
if (empty($searchkey)) $searchkey=$_GET[$htmlname];
$outjson=isset($_GET['outjson'])?$_GET['outjson']:0;

// Get list of product.
$status=-1;
if (isset($_GET['status'])) $status=$_GET['status'];

$form = new Form($db);
if (empty($_GET['mode']) || $_GET['mode'] == 1)
{
	$arrayresult=$form->select_produits_do("",$htmlname,$_GET["type"],"",$_GET["price_level"],$searchkey,$status,2,$outjson);
}
if ($_GET['mode'] == 2)
{
	$arrayresult=$form->select_produits_fournisseurs_do($_GET["socid"],"",$htmlname,$_GET["type"],"",$searchkey,$status,$outjson);
}

$db->close();

if ($outjson) print json_encode($arrayresult);

//print "</body>";
//print "</html>";
?>