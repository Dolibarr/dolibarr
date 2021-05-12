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
 *       \file       htdocs/core/ajax/price.php
 *       \brief      File to get ht and ttc
 */

<<<<<<< HEAD
if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1'); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');

require('../../main.inc.php');

$output		= GETPOST('output','alpha');
$amount		= price2num(GETPOST('amount','alpha'));
$tva_tx		= str_replace('*','',GETPOST('tva_tx','alpha'));
=======
if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', '1'); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU', '1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX', '1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC', '1');

require '../../main.inc.php';

$output		= GETPOST('output', 'alpha');
$amount		= price2num(GETPOST('amount', 'alpha'));
$tva_tx		= str_replace('*', '', GETPOST('tva_tx', 'alpha'));
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

/*
 * View
 */

top_httphead();

//print '<!-- Ajax page called with url '.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]).' -->'."\n";

// Load original field value
if (! empty($output) && isset($amount) && isset($tva_tx))
{
	$return=array();
	$price='';

	if (is_numeric($amount) && $amount != '')
	{
		if ($output == 'price_ttc') {

			$price = price2num($amount * (1 + ($tva_tx/100)), 'MU');
			$return['price_ht'] = $amount;
			$return['price_ttc'] = (isset($price) && $price != '' ? price($price) : '');
<<<<<<< HEAD

		}
		else if ($output == 'price_ht') {
=======
		}
		elseif ($output == 'price_ht') {
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

			$price = price2num($amount / (1 + ($tva_tx/100)), 'MU');
			$return['price_ht'] = (isset($price) && $price != '' ? price($price) : '');
			$return['price_ttc'] = ($tva_tx == 0 ? $price : $amount);
		}
	}

	echo json_encode($return);
}
<<<<<<< HEAD

=======
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
