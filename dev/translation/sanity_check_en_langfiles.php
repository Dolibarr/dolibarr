<?php
/* Copyright (c) 2015 Tommaso Basilici <t.basilici@19.coop>
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

echo "<html>";
echo "<head>";

echo "<STYLE type=\"text/css\"> 
body {
	color: #444;
	font: 100%/30px 'Helvetica Neue', helvetica, arial, sans-serif;
	text-shadow: 0 1px 0 #fff;
}

strong {
	font-weight: bold; 
}

em {
	font-style: italic; 
}

table {
	background: #f5f5f5;
	border-collapse: separate;
	box-shadow: inset 0 1px 0 #fff;
	font-size: 12px;
	line-height: 24px;
	margin: 30px auto;
	text-align: left;
	width: 800px;
}	

th {
	background-color: #777;
	border-left: 1px solid #555;
	border-right: 1px solid #777;
	border-top: 1px solid #555;
	border-bottom: 1px solid #333;
	box-shadow: inset 0 1px 0 #999;
	color: #fff;
  font-weight: bold;
	padding: 10px 15px;
	position: relative;
	text-shadow: 0 1px 0 #000;	
}

th:after {
	background: linear-gradient(rgba(255,255,255,0), rgba(255,255,255,.08));
	content: '';
	display: block;
	height: 25%;
	left: 0;
	margin: 1px 0 0 0;
	position: absolute;
	top: 25%;
	width: 100%;
}

th:first-child {
	border-left: 1px solid #777;	
	box-shadow: inset 1px 1px 0 #999;
}

th:last-child {
	box-shadow: inset -1px 1px 0 #999;
}

td {
	border-right: 1px solid #fff;
	border-left: 1px solid #e8e8e8;
	border-top: 1px solid #fff;
	border-bottom: 1px solid #e8e8e8;
	padding: 10px 15px;
	position: relative;
	transition: all 300ms;
}

td:first-child {
	box-shadow: inset 1px 0 0 #fff;
}	

td:last-child {
	border-right: 1px solid #e8e8e8;
	box-shadow: inset -1px 0 0 #fff;
}	

tr {
	background-color: #f1f1f1;

}

tr:nth-child(odd) td {
	background-color: #f1f1f1;	
}

tr:last-of-type td {
	box-shadow: inset 0 -1px 0 #fff; 
}

tr:last-of-type td:first-child {
	box-shadow: inset 1px -1px 0 #fff;
}	

tr:last-of-type td:last-child {
	box-shadow: inset -1px -1px 0 #fff;
}	

tbody:hover td {
	color: transparent;
	text-shadow: 0 0 3px #aaa;
}

tbody:hover tr:hover td {
	color: #444;
	text-shadow: 0 1px 0 #fff;
} </STYLE>";

echo "<body>";
echo "<h3>If you call this file with the argument \"?unused=true\" it searches for the translation strings that exist in en_US but are never used</h3>";
echo "<h2>IMPORTANT: that can take quite a lot of time (up to 10 minutes), you need to tune the max_execution_time on your php.ini accordingly</h2>";
echo "<h3>Happy translating :)</h3>";

// directory containing the php and lang files 
$htdocs 	= "../../htdocs/"; 
// directory containing the english lang files
$workdir 	= $htdocs."langs/en_US/";

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
					$langstrings_dist[$row_array[0]]=$row_array[0];
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
					$dups[$value][$filename][$linenum] = '';
				}
		}
	}
}

echo "<h2>Duplicate strings in lang files in $workdir - ".count($dups)." found</h2>";
echo "<pre>";

echo "<table border_bottom=1> ";
echo "<thead><tr><th align=\"center\">#</th><th>String</th><th>File and lines</th></thead>";
echo "<tbody>";
$count = 0;
foreach ($dups as $string => $pages) {
	$count++;
	echo "<tr>";
	echo "<td align=\"center\">$count</td>";
	echo "<td>$string</td>";
	echo "<td>";
	foreach ($pages AS $page => $lines ) {
		echo "$page ";
		foreach ($lines as $line => $nothing) {
			echo "($line) ";
		}
		echo "<br>";
	}
	echo "</td></tr>";
}
echo "</tbody>";
echo "</table>";


if ($_REQUEST['unused'] == 'true') {

	foreach ($langstrings_dist AS $value){
		$search = '\'trans("'.$value.'")\'';
		$string =  'grep -R -m 1 -F --include=*.php '.$search.' '.$htdocs.'*';
		exec($string,$output);
		if (empty($output)) {
			$unused[$value] = true;
			echo $value.'<br>';
		}
	}

	echo "<h2>Strings in en_US that are never used</h2>";
	echo "<pre>";
	print_r($unused);
}
echo "</body>";
echo "</html>";
?>