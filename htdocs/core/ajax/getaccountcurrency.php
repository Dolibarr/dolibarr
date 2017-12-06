<?php
/* Copyright (C) 2012 Regis Houssin  <regis.houssin@capnetworks.com>
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

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1'); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');
//if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
//if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');

require '../../main.inc.php';

$id			= GETPOST('id','int');

/*
 * View
 */

top_httphead();

//print '<!-- Ajax page called with url '.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]).' -->'."\n";

// Load original field value
if (! empty($id))
{
	require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
	$account=new Account($db);
	$result = $account->fetch($id);
	if ($result<0) {
		$return['value']	= '';
		$return['num']		= $result;
		$return['error']	= $account->errors[0];
	} else {
		$return['value']	= $account->currency_code;
		$return['num']		= $result;
		$return['error']	= '';
	}

	echo json_encode($return);
}

