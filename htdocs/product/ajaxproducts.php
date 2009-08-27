<?php
/* Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2007-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *       \file       htdocs/product/ajaxproducts.php
 *       \brief      File to return Ajax response on product list request
 *       \version    $Id$
 */

define('NOTOKENRENEWAL',1); // Disables token renewal

require('../main.inc.php');

$langs->load("products");
$langs->load("main");

top_htmlhead("", "", 1);

print '<body class="nocellnopadd">'."\n";

// Generation liste de produits
if (! empty($_GET['keysearch']))
{
	//var_dump($_GET); exit;
	//print $_GET["price_level"]; exit;
	$status=-1;
	if (isset($_GET['status'])) $status=$_GET['status'];
	$form = new Form($db);
	if (empty($_GET['mode']) || $_GET['mode'] == 1)
	{
		$form->select_produits_do("",$_GET["htmlname"],$_GET["type"],"",$_GET["price_level"],$_GET["keysearch"],$status);
	}
	if ($_GET['mode'] == 2)
	{
		$form->select_produits_fournisseurs_do($_GET["socid"],"",$_GET["htmlname"],$_GET["type"],"",$_GET["keysearch"]);
	}
}
else if (! empty($_GET['markup']))
{
	print $_GET['markup'];
	//print $_GET['count'];
	//$field = "<input size='10' type='text' class='flat' id='sellingdata_ht".$_GET['count']."' name='sellingdata_ht".$_GET['count']."' value='".$_GET['markup']."'>";
	//print '<input size="10" type="text" class="flat" id="sellingdata_ht'.$_GET['count'].'" name="sellingdata_ht'.$_GET['count'].'" value="'.$field.'">';
	//print $field;
}
else if (! empty($_GET['selling']))
{
	//print $_GET['markup'];
	//print $_GET['count'];
	print '<input size="10" type="text" class="flat" name="cashflow'.$_GET['count'].'" value="'.$_GET['selling'].'">';
}

print "</body>";
print "</html>";
?>
