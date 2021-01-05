<?php
/*
 * Copyright (C) 2019-2020  Frédéric France     <frederic.france@netlogic.fr>
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

// Defines
$defines = (isset($defines) && is_array($defines)) ? $defines : [];
foreach ($defines as $define) {
	if (! defined($define)) {
		define($define, 1);
	}
}
// Load Dolibarr environment
$res = 0;
$filename = isset($_SERVER['SCRIPT_FILENAME']) ? explode('/', parse_url($_SERVER['SCRIPT_FILENAME'])['path']) : [];
$newfile = [];
$arraymain = [];
foreach ($filename as $val) {
	$newfile[] = $val;
	$arraymain[] = implode('/', $newfile);
}
$arraymain = array_merge(
	array(
		$_SERVER["CONTEXT_DOCUMENT_ROOT"] ?? '',
		'..',
		'../..',
		'../../..',
		'../../../..',
	),
	array_reverse($arraymain)
);
foreach ($arraymain as $path) {
	if (file_exists($path . '/main.inc.php')) {
		$res = include $path . '/main.inc.php';
		if ($res) {
			break;
		}
	}
}
if (! $res) {
	die("Include of main fails");
}
