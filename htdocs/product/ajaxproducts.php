<?php
/* Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2005-2007 Regis Houssin        <regis.houssin@cap-networks.com>
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
 *
 * $Id$
 * $Source$
 */

/**
        \file       htdocs/product/ajaxproducts.php
        \brief      Fichier de reponse sur evenement Ajax
        \version    $Revision$
*/

require('../master.inc.php');

$langs->load("products");
$langs->load("main");

//header("Content-type: text/html; charset=UTF-8");
header("Content-type: text/html; charset=iso-8859-1");
print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
print "\n<html>";
print "\n<body>";


// Generation liste de produits
if(isset($_GET['keysearch']) && !empty($_GET['keysearch']))
{
	$form = new Form($db);
	if ($_GET['type'] == 1)
	{
		$form->select_produits_do("",$_GET["htmlname"],"","",$_GET["price_level"],$_GET["keysearch"]);
	}
	else if ($_GET['type'] == 2)
	{
		$form->select_produits_fournisseurs_do("","",$_GET["htmlname"],"","",$_GET["keysearch"]);
	}
}

print "</body>"; 
print "</html>"; 
?>
