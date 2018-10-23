<?php
/* TVI
 * Copyright (C) 2015	Florian HENRY 		<florian.henry@open-concept.pro>
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
 * \file tvi/ajax/list.php
 * \brief File to return datables output
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', '1'); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU', '1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX', '1');

require '../main.inc.php';
require DOL_DOCUMENT_ROOT.'/loan/class/loanschedule.class.php';

$mens=GETPOST('mens');
$capital=GETPOST('capital');
$rate=GETPOST('rate');
$echance=GETPOST('echeance');
$nbterm=GETPOST('nbterm');

top_httphead();

$output=array();

$object = new LoanSchedule($db);

$int = ($capital*($rate/12));
$int = round($int,2,PHP_ROUND_HALF_UP);
$cap_rest = round($capital - ($mens-$int),2,PHP_ROUND_HALF_UP);
$output[$echance]=array('cap_rest'=>$cap_rest,'cap_rest_str'=>price($cap_rest),'interet'=>$int,'interet_str'=>price($int,0,'',1),'mens'=>$mens);

$echance++;
$capital=$cap_rest;
while ($echance<=$nbterm) {

	$mens = round($object->calcMonthlyPayments($capital, $rate, $nbterm-$echance+1), 2, PHP_ROUND_HALF_UP);

	$int = ($capital*($rate/12));
	$int = round($int,2,PHP_ROUND_HALF_UP);
	$cap_rest = round($capital - ($mens-$int),2,PHP_ROUND_HALF_UP);

	$output[$echance]=array('cap_rest'=>$cap_rest,'cap_rest_str'=>price($cap_rest),'interet'=>$int,'interet_str'=>price($int,0,'',1),'mens'=>$mens);

	$capital=$cap_rest;
	$echance++;
}

echo json_encode($output);
