<?php
/* TVI
 * Copyright (C) 2015	    Florian HENRY 		    <florian.henry@open-concept.pro>
 * Copyright (C) 2020       Maxime DEMAREST         <maxime@indelog.fr>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 *  \file htdocs/loan/calcmens.php
 *  \ingroup    loan
 *  \brief File to calculate loan monthly payments
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1'); // Disables token renewal
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}

// Load Dolibarr environment
require '../main.inc.php';
require DOL_DOCUMENT_ROOT.'/core/lib/loan.lib.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

$mens = (float) price2num(GETPOST('mens'));
$capital = (float) price2num(GETPOST('capital'));
$rate = (float) price2num(GETPOST('rate'));
$echance = GETPOSTINT('echeance');
$nbterm = GETPOSTINT('nbterm');

top_httphead();

$output = array();

$output = loanCalcMonthlyPayment($mens, $capital, $rate, $echance, $nbterm);

echo json_encode($output);
