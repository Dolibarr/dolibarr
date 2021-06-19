<?php
/* Copyright (C) 2006-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2007      Patrick Raguin       <patrick.raguin@gmail.com>
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
 * or see https://www.gnu.org/
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

$sql = "SELECT rowid, code, name, paper_size, orientation, metric, leftmargin, topmargin, nx, ny, spacex, spacey, width, height, font_size, custom_x, custom_y, active FROM ".MAIN_DB_PREFIX."c_format_cards WHERE active=1 ORDER BY code ASC";
$resql = $db->query($sql);
if ($resql) {
	while ($row = $db->fetch_array($resql)) {
		$_Avery_Labels[$row['code']]['name'] = $row['name'];
		$_Avery_Labels[$row['code']]['paper-size'] = $row['paper_size'];
		$_Avery_Labels[$row['code']]['orientation'] = $row['orientation'];
		$_Avery_Labels[$row['code']]['metric'] = $row['metric'];
		$_Avery_Labels[$row['code']]['marginLeft'] = $row['leftmargin'];
		$_Avery_Labels[$row['code']]['marginTop'] = $row['topmargin'];
		$_Avery_Labels[$row['code']]['marginTop'] = $row['topmargin'];
		$_Avery_Labels[$row['code']]['NX'] = $row['nx'];
		$_Avery_Labels[$row['code']]['NY'] = $row['ny'];
		$_Avery_Labels[$row['code']]['SpaceX'] = $row['spacex'];
		$_Avery_Labels[$row['code']]['SpaceY'] = $row['spacey'];
		$_Avery_Labels[$row['code']]['width'] = $row['width'];
		$_Avery_Labels[$row['code']]['height'] = $row['height'];
		$_Avery_Labels[$row['code']]['font-size'] = $row['font_size'];
		$_Avery_Labels[$row['code']]['custom_x'] = $row['custom_x'];
		$_Avery_Labels[$row['code']]['custom_y'] = $row['custom_y'];
	}
} else {
	dol_print_error($db);
}

// We add characteristics to the name
foreach ($_Avery_Labels as $key => $val) {
	$_Avery_Labels[$key]['name'] .= ' ('.$_Avery_Labels[$key]['paper-size'].' - '.$_Avery_Labels[$key]['NX'].'x'.$_Avery_Labels[$key]['NY'].')';
}
