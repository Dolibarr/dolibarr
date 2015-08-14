<?php
/* Copyright (C) 2006-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2007      Patrick Raguin <patrick.raguin@gmail.com>
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
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/core/lib/format_cards.lib.php
 *	\brief      Set of functions used for cards generation
 *	\ingroup    core
 */


global $_Avery_Labels;

// Unit of metric are defined into field 'metric' in mm.
// To get into inch, just /25.4
// Size of pages available on: http://www.worldlabel.com/Pages/pageaverylabels.htm
// _PosX = marginLeft+(_COUNTX*(width+SpaceX));
$_Avery_Labels = array (
			      '5160'=>array('name'=>'Avery-5160, WL-875WX',
					    'paper-size'=>'letter',
					    'metric'=>'mm',
					    'marginLeft'=>5.58165,	// 0.21975 inch
					    'marginTop'=>12.7,		// 0.5 inch
					    'NX'=>3,
					    'NY'=>10,
					    'SpaceX'=>3.556,	// 0.14 inch
					    'SpaceY'=>0,
					    'width'=>65.8749,	// 2.59350 inch
					    'height'=>25.4,		// 1 inch
					    'font-size'=>7),
			      '5161'=>array('name'=>'Avery-5161, WL-75WX',
					    'paper-size'=>'letter',
					    'metric'=>'mm',
					    'marginLeft'=>4.445,	// 0.175 inch
					    'marginTop'=>12.7,
					    'NX'=>2,
					    'NY'=>10,
					    'SpaceX'=>3.968,	// 0.15625 inch
					    'SpaceY'=>0,
					    'width'=>101.6,		// 4 inch
					    'height'=>25.4,		// 1 inch
					    'font-size'=>7),
			      '5162'=>array('name'=>'Avery-5162, WL-100WX',
					    'paper-size'=>'letter',
					    'metric'=>'mm',
					    'marginLeft'=>3.8735,	// 0.1525 inch
					    'marginTop'=>22.352,	// 0.88 inch
					    'NX'=>2,
					    'NY'=>7,
					    'SpaceX'=>4.954,	// 0.195 inch
					    'SpaceY'=>0,
					    'width'=>101.6,		// 4 inch
					    'height'=>33.781,	// 1.33 inch
					    'font-size'=>8),
			      '5163'=>array('name'=>'Avery-5163, WL-125WX',
					    'paper-size'=>'letter',
					    'metric'=>'mm',
					    'marginLeft'=>4.572,	// 0.18 inch
					    'marginTop'=>12.7,	// 0.5 inch
					    'NX'=>2,
					    'NY'=>5,
					    'SpaceX'=>3.556,	// 0.14 inch
					    'SpaceY'=>0,
					    'width'=>101.6,		// 4 inch
					    'height'=>50.8,		// 2 inch
					    'font-size'=>10),
			     /* Bugged '5164'=>array('name'=>'5164 (Letter)',
					    'paper-size'=>'letter',
					    'metric'=>'in',
					    'marginLeft'=>0.148,
					    'marginTop'=>0.5,
					    'NX'=>2,
					    'NY'=>3,
					    'SpaceX'=>0.2031,
					    'SpaceY'=>0,
					    'width'=>4.0,
					    'height'=>3.33,
					    'font-size'=>12), */
			      '8600'=>array('name'=>'Avery-8600',
					    'paper-size'=>'letter',
					    'metric'=>'mm',
					    'marginLeft'=>7.1,
					    'marginTop'=>19,
					    'NX'=>3,
					    'NY'=>10,
					    'SpaceX'=>9.5,
					    'SpaceY'=>3.1,
					    'width'=>66.6,
					    'height'=>25.4,
					    'font-size'=>7),
			      'L7163'=>array('name'=>'Avery-L7163',
					     'paper-size'=>'A4',
					     'metric'=>'mm',
					     'marginLeft'=>5,
					     'marginTop'=>15,
					     'NX'=>2,
					     'NY'=>7,
					     'SpaceX'=>2.5,
					     'SpaceY'=>0,
					     'width'=>99.1,
					     'height'=>38.1,
					     'font-size'=>8),
					// 85.0 x 54.0 mm
			      'AVERYC32010'=>array('name'=>'Avery-C32010',
					     'paper-size'=>'A4',
					     'metric'=>'mm',
					     'marginLeft'=>15,
					     'marginTop'=>13,
					     'NX'=>2,
					     'NY'=>5,
					     'SpaceX'=>10,
					     'SpaceY'=>0,
					     'width'=>85,
					     'height'=>54,
					     'font-size'=>10),
					'CARD'=>array('name'=>'Dolibarr Business cards',
					    'paper-size'=>'A4',
					    'metric'=>'mm',
					    'marginLeft'=>15,
					    'marginTop'=>15,
					    'NX'=>2,
					    'NY'=>5,
					    'SpaceX'=>0,
					    'SpaceY'=>0,
					    'width'=>85,
					    'height'=>54,
					    'font-size'=>10)
		);

foreach($_Avery_Labels as $key => $val)
{
	$_Avery_Labels[$key]['name'].=' ('.$_Avery_Labels[$key]['paper-size'].' - '.$_Avery_Labels[$key]['NX'].'x'.$_Avery_Labels[$key]['NY'].')';
}

