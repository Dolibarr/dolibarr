<?php
<<<<<<< HEAD
/* Copyright (C) 2012 Regis Houssin  <regis.houssin@capnetworks.com>
=======
/* Copyright (C) 2012 Regis Houssin  <regis.houssin@inodbox.com>
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
 *       \file       htdocs/core/ajax/vatrates.php
 *       \brief      File to load vat rates combobox
 */

<<<<<<< HEAD
if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1'); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');

require '../../main.inc.php';

$id			= GETPOST('id','int');
$action		= GETPOST('action','alpha');
$htmlname	= GETPOST('htmlname','alpha');
$selected	= (GETPOST('selected')?GETPOST('selected'):'-1');
$productid	= (GETPOST('productid','int')?GETPOST('productid','int'):0);
=======
if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', '1'); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU', '1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX', '1');

require '../../main.inc.php';

$id			= GETPOST('id', 'int');
$action		= GETPOST('action', 'alpha');
$htmlname	= GETPOST('htmlname', 'alpha');
$selected	= (GETPOST('selected')?GETPOST('selected'):'-1');
$productid	= (GETPOST('productid', 'int')?GETPOST('productid', 'int'):0);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

/*
 * View
 */

top_httphead();

//print '<!-- Ajax page called with url '.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]).' -->'."\n";

// Load original field value
if (! empty($id) && ! empty($action) && ! empty($htmlname))
{
	$form = new Form($db);
	$soc = new Societe($db);

	$soc->fetch($id);

	if ($action == 'getSellerVATRates')
	{
		$seller = $mysoc;
		$buyer = $soc;
	}
	else
	{
		$buyer = $mysoc;
		$seller = $soc;
	}

	$return=array();

<<<<<<< HEAD
	$return['value']	= $form->load_tva('tva_tx',$selected,$seller,$buyer,$productid,0,'',true);
=======
	$return['value']	= $form->load_tva('tva_tx', $selected, $seller, $buyer, $productid, 0, '', true);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	$return['num']		= $form->num;
	$return['error']	= $form->error;

	echo json_encode($return);
}
<<<<<<< HEAD

=======
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
