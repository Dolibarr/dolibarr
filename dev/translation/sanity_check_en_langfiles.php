<?php
/* Copyright (c) 2015 Lorenzo Novaro <novalore@19.coop>
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
* along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

// directory containing the english lang files
$workdir = "../../htdocs/langs/en_US/";

$files = scandir($workdir);
$exludefiles = array('.','..','README');
$files = array_diff($files,$exludefiles);
$langstrings_3d = array();
$langstrings_full = array();
foreach ($files AS $file) {
	$path_file = pathinfo($file);
	// we're only interested in .lang files
	if ($path_file['extension']=='lang') {
		$content = file($workdir.$file);
		foreach ($content AS $line => $row) {
			// don't want comment lines
			if (substr($row,0,1) !== '#') {
				// don't want lines without the separator (why should those even be here, anyway...)
				if (strpos($row,'=')!==false) {
					$row_array = explode('=',$row);
					$langstrings_3d[$path_file['basename']][$line+1]=$row_array[0];
					$langstrings_full[]=$row_array[0];
				}				
			}
		}
	}
}

foreach ($langstrings_3d AS $filename => $file) {
	foreach ($file AS $linenum => $value) {
		$keys = array_keys($langstrings_full, $value);
		if (count($keys)>1) {
				foreach ($keys AS $key) {
					$dups[$value][$filename] = $linenum;
				}
		}
	}
}

echo "<h2>Duplicate strings in lang files in $workdir</h2>";
echo "<pre>";
print_r($dups);

?>