<?php
/* TVI
 * Copyright (C) 2015	Florian HENRY 		<florian.henry@open-concept.pro>
 * Copyright (C) 2020   Maxime DEMAREST     <maxime@indelog.fr>
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
 *  \file htdocs/loan/calcmens.php
 *  \ingroup    loan
 *  \brief File to calculate loan monthly payments
 */

if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', '1'); // Disables token renewal
if (!defined('NOREQUIREMENU'))  define('NOREQUIREMENU', '1');
if (!defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX', '1');

require '../main.inc.php';
require DOL_DOCUMENT_ROOT.'/core/lib/loan.lib.php';

$mens = GETPOST('mens');
$capital = GETPOST('capital');
$rate = GETPOST('rate');
$echance = GETPOST('echeance');
$nbterm = GETPOST('nbterm');

top_httphead();

$output = array();

$output = loanCalcMonthlyPayment($mens, $capital, $rate, $echance, $nbterm);

echo json_encode($output);
