#!/usr/bin/env php
<?php
/*
 * Copyright (C) 2023 	   Laurent Destailleur 	<eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    dev/tools/apstats.php
 * \brief   Script to report Advanced Statistics on a coding project
 */


$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path = dirname(__FILE__) . '/';

// Test si mode batch
$sapi_type = php_sapi_name();
if (substr($sapi_type, 0, 3) == 'cgi') {
	echo "Error: You are using PHP for CGI. To execute " . $script_file . " from command line, you must use PHP for CLI mode.\n";
	exit();
}

error_reporting(E_ALL & ~E_DEPRECATED);
define('PRODUCT', "apstats");
define('VERSION', "1.0");

$phpstanlevel = 2;


print '***** '.constant('PRODUCT').' - '.constant('VERSION').' *****'."\n";
if (empty($argv[1])) {
	print 'You must run this tool being into the root of the project.'."\n";
	print 'Usage:   '.constant('PRODUCT').'.php  pathto/outputfile.html  [--dir-scc=pathtoscc] [--dir-phpstan=pathtophpstan]'."\n";
	print 'Example: '.constant('PRODUCT').'.php  documents/apstats/index.html --dir-scc=/snap/bin --dir-phpstan=~/git/phpstan/htdocs/includes/bin';
	exit(0);
}

$outputpath = $argv[1];
$outputdir = dirname($outputpath);
$outputfile = basename($outputpath);

if (!is_dir($outputdir)) {
	print 'Error: dir '.$outputdir.' does not exists or is not writable'."\n";
	exit(1);
}

$dirscc = '';
$dirphpstan = '';

$i = 0;
while ($i < $argc) {
	$reg = array();
	if (preg_match('/--dir-scc=(.*)$/', $argv[$i], $reg)) {
		$dirscc = $reg[1];
	}
	if (preg_match('/--dir-phpstan=(.*)$/', $argv[$i], $reg)) {
		$dirphpstan = $reg[1];
	}
	$i++;
}

$timestart = time();

// Count lines of code of Dolibarr itself
/*
$commandcheck = 'cloc . --exclude-dir=includes --exclude-dir=custom --ignore-whitespace --vcs=git';
$resexec = shell_exec($commandcheck);
$resexec = (int) (empty($resexec) ? 0 : trim($resexec));


// Count lines of code of external dependencies
$commandcheck = 'cloc htdocs/includes --ignore-whitespace --vcs=git';
$resexec = shell_exec($commandcheck);
$resexec = (int) (empty($resexec) ? 0 : trim($resexec));
*/

// Count lines of code of application
$commandcheck = ($dirscc ? $dirscc.'/' : '').'scc . --exclude-dir=htdocs/includes,htdocs/custom,htdocs/theme/common/fontawesome-5,htdocs/theme/common/octicons';
print 'Execute SCC to count lines of code in project: '.$commandcheck."\n";
$output_arrproj = array();
$resexecproj = 0;
exec($commandcheck, $output_arrproj, $resexecproj);


// Count lines of code of dependencies
$commandcheck = ($dirscc ? $dirscc.'/' : '').'scc htdocs/includes htdocs/theme/common/fontawesome-5 htdocs/theme/common/octicons';
print 'Execute SCC to count lines of code in dependencies: '.$commandcheck."\n";
$output_arrdep = array();
$resexecdep = 0;
exec($commandcheck, $output_arrdep, $resexecdep);


// Get technical debt
$commandcheck = ($dirphpstan ? $dirphpstan.'/' : '').'phpstan --level='.$phpstanlevel.' -v analyze -a build/phpstan/bootstrap.php --memory-limit 5G --error-format=github';
print 'Execute PHPStan to get the technical debt: '.$commandcheck."\n";
$output_arrtd = array();
$resexectd = 0;
exec($commandcheck, $output_arrtd, $resexectd);

$arrayoflineofcode = array();
$arraycocomo = array();
$arrayofmetrics = array(
	'proj' => array('Bytes' => 0, 'Files' => 0, 'Lines' => 0, 'Blanks' => 0, 'Comments' => 0, 'Code' => 0, 'Complexity' => 0),
	'dep' => array('Bytes' => 0, 'Files' => 0, 'Lines' => 0, 'Blanks' => 0, 'Comments' => 0, 'Code' => 0, 'Complexity' => 0)
);

// Analyse $output_arrproj
foreach (array('proj', 'dep') as $source) {
	print 'Analyze SCC result for lines of code for '.$source."\n";
	if ($source == 'proj') {
		$output_arr = &$output_arrproj;
	} elseif ($source == 'dep') {
		$output_arr = &$output_arrdep;
	} else {
		print 'Bad value for $source';
		die();
	}

	foreach ($output_arr as $line) {
		if (preg_match('/^(───|Language|Total)/', $line)) {
			continue;
		}

		//print $line."<br>\n";

		if (preg_match('/^Estimated Cost.*\$(.*)/i', $line, $reg)) {
			$arraycocomo[$source]['currency'] = preg_replace('/[^\d\.]/', '', str_replace(array(',', ' '), array('', ''), $reg[1]));
		}
		if (preg_match('/^Estimated Schedule Effort.*\s([\d\s,]+)/i', $line, $reg)) {
			$arraycocomo[$source]['effort'] = str_replace(array(',', ' '), array('.', ''), $reg[1]);
		}
		if (preg_match('/^Estimated People.*\s([\d\s,]+)/i', $line, $reg)) {
			$arraycocomo[$source]['people'] = str_replace(array(',', ' '), array('.', ''), $reg[1]);
		}
		if (preg_match('/^Processed\s(\d+)\s/i', $line, $reg)) {
			$arrayofmetrics[$source]['Bytes'] = $reg[1];
		}

		if (preg_match('/^(.*)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)$/', $line, $reg)) {
			$arrayoflineofcode[$source][$reg[1]]['Files'] = $reg[2];
			$arrayoflineofcode[$source][$reg[1]]['Lines'] = $reg[3];
			$arrayoflineofcode[$source][$reg[1]]['Blanks'] = $reg[4];
			$arrayoflineofcode[$source][$reg[1]]['Comments'] = $reg[5];
			$arrayoflineofcode[$source][$reg[1]]['Code'] = $reg[6];
			$arrayoflineofcode[$source][$reg[1]]['Complexity'] = $reg[7];
		}
	}

	if (!empty($arrayoflineofcode[$source])) {
		foreach ($arrayoflineofcode[$source] as $key => $val) {
			$arrayofmetrics[$source]['Files'] += $val['Files'];
			$arrayofmetrics[$source]['Lines'] += $val['Lines'];
			$arrayofmetrics[$source]['Blanks'] += $val['Blanks'];
			$arrayofmetrics[$source]['Comments'] += $val['Comments'];
			$arrayofmetrics[$source]['Code'] += $val['Code'];
			$arrayofmetrics[$source]['Complexity'] += $val['Complexity'];
		}
	}
}

$timeend = time();


/*
 * View
 */

$html = '<html>'."\n";
$html .= '<meta charset="utf-8">'."\n";
$html .= '<meta http-equiv="refresh" content="300">'."\n";
$html .= '<meta name="viewport" content="width=device-width, initial-scale=1.0">'."\n";
$html .= '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.9.0/css/all.min.css" integrity="sha512-q3eWabyZPc1XTCmF+8/LuE1ozpg5xxn7iO89yfSOd5/oKvyqLngoNGsx8jq92Y8eXJ/IRxQbEC+FGSYxtk2oiw==" crossorigin="anonymous" referrerpolicy="no-referrer" />'."\n";
$html .= '<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js" integrity="sha512-3gJwYpMe3QewGELv8k/BX9vcqhryRdzRMxVfq6ngyWXwo03GFEzjsUm8Q7RZcHPHksttq7/GFoxjCVUjkjvPdw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>'."\n";
$html .= '
<style>
body {
	margin: 10px;
	margin-left: 50px;
	margin-right: 50px;
}

h1 {
	font-size: 1.5em;
	font-weight: bold;
	padding-top: 5px;
	padding-bottom: 5px;
	margin-top: 5px;
	margin-bottom: 5px;
}

header {
	text-align: center;
}
header, section.chapter {
	margin-top: 10px;
	margin-bottom: 10px;
	padding: 10px;
}

table {
	border-collapse: collapse;
}
th,td {
	padding-top: 5px;
	padding-bottom: 5px;
	padding-left: 10px;
	padding-right: 10px;
}
.left {
	text-align: left;
}
.right {
	text-align: right;
}
.nowrap {
	white-space: nowrap;
}
.opacitymedium {
	opacity: 0.5;
}
.centpercent {
	width: 100%;
}
.hidden {
	display: none;
}
.trgroup {
	background-color: #EEE;
}
.seedetail {
	color: #000088;
	cursor: pointer;
}
.box {
	padding: 20px;
	font-size: 1.2em;
	margin-top: 10px;
	margin-bottom: 10px;
	width: 200px;
}
.box.inline-box {
    display: inline-block;
	text-align: center;
	margin-left: 10px;
}
.back1 {
	background-color: #884466;
	color: #FFF;
}
.back2 {
	background-color: #664488;
	color: #FFF;
}

div.fiche>form>div.div-table-responsive {
    min-height: 392px;
}
div.fiche>form>div.div-table-responsive, div.fiche>form>div.div-table-responsive-no-min {
    overflow-x: auto;
}
.div-table-responsive {
    line-height: 120%;
}
.div-table-responsive, .div-table-responsive-no-min {
    overflow-x: auto;
    min-height: 0.01%;
}


/* Force values for small screen 767 */
@media only screen and (max-width: 767px)
{
	body {
		margin: 5px;
		margin-left: 5px;
		margin-right: 5px;
	}
}


</style>'."\n";

$html .= '<body>'."\n";

$html .= '<header>'."\n";
$html .= '<h1>Advanced Project Statistics</h1>'."\n";
$currentDate = date("Y-m-d H:i:s"); // Format: Year-Month-Day Hour:Minute:Second
$html .= '<span class="opacitymedium">Generated on '.$currentDate.' in '.($timeend - $timestart).' seconds</span>'."\n";
$html .= '</header>'."\n";

$html .= '<section class="chapter" id="linesofcode">'."\n";
$html .= '<h2>Lines of code</h2>'."\n";

$html .= '<div class="div-table-responsive">'."\n";
$html .= '<table class="centpercent">';
$html .= '<tr class="loc">';
$html .= '<th class="left">Language</th>';
$html .= '<th class="right">Bytes</th>';
$html .= '<th class="right">Files</th>';
$html .= '<th class="right">Lines</th>';
$html .= '<th class="right">Blanks</th>';
$html .= '<th class="right">Comments</th>';
$html .= '<th class="right">Code</th>';
//$html .= '<td class="right">'.$val['Complexity'].'</td>';
$html .= '</tr>';
foreach (array('proj', 'dep') as $source) {
	$html .= '<tr class="trgroup" id="source'.$source.'">';
	if ($source == 'proj') {
		$html .= '<td>All files without dependencies';
	} elseif ($source == 'dep') {
		$html .= '<td>All files of dependencies only';
	}
	$html .= ' &nbsp; &nbsp; <span class="seedetail" data-source="'.$source.'">(See detail per file type...)</span>';
	$html .= '<td class="right">'.formatNumber($arrayofmetrics[$source]['Bytes']).'</td>';
	$html .= '<td class="right">'.formatNumber($arrayofmetrics[$source]['Files']).'</td>';
	$html .= '<td class="right">'.formatNumber($arrayofmetrics[$source]['Lines']).'</td>';
	$html .= '<td class="right">'.formatNumber($arrayofmetrics[$source]['Blanks']).'</td>';
	$html .= '<td class="right">'.formatNumber($arrayofmetrics[$source]['Comments']).'</td>';
	$html .= '<td class="right">'.formatNumber($arrayofmetrics[$source]['Code']).'</td>';
	$html .= '<td></td>';
	$html .= '</tr>';
	if (!empty($arrayoflineofcode[$source])) {
		foreach ($arrayoflineofcode[$source] as $key => $val) {
			$html .= '<tr class="loc hidden source'.$source.' language'.str_replace(' ', '', $key).'">';
			$html .= '<td>'.$key.'</td>';
			$html .= '<td class="right"></td>';
			$html .= '<td class="right nowrap">'.(empty($val['Files']) ? '' : formatNumber($val['Files'])).'</td>';
			$html .= '<td class="right nowrap">'.(empty($val['Lines']) ? '' : formatNumber($val['Lines'])).'</td>';
			$html .= '<td class="right nowrap">'.(empty($val['Blanks']) ? '' : formatNumber($val['Blanks'])).'</td>';
			$html .= '<td class="right nowrap">'.(empty($val['Comments']) ? '' : formatNumber($val['Comments'])).'</td>';
			$html .= '<td class="right nowrap">'.(empty($val['Code']) ? '' : formatNumber($val['Code'])).'</td>';
			//$html .= '<td class="right">'.(empty($val['Complexity']) ? '' : $val['Complexity']).'</td>';
			$html .= '<td class="nowrap">TODO graph here...</td>';
			$html .= '</tr>';
		}
	}
}

$html .= '<tr class="trgroup">';
$html .= '<td class="left">Total</td>';
$html .= '<td class="right nowrap">'.formatNumber($arrayofmetrics['proj']['Bytes'] + $arrayofmetrics['dep']['Bytes']).'</td>';
$html .= '<td class="right nowrap">'.formatNumber($arrayofmetrics['proj']['Files'] + $arrayofmetrics['dep']['Files']).'</td>';
$html .= '<td class="right nowrap">'.formatNumber($arrayofmetrics['proj']['Lines'] + $arrayofmetrics['dep']['Lines']).'</td>';
$html .= '<td class="right nowrap">'.formatNumber($arrayofmetrics['proj']['Blanks'] + $arrayofmetrics['dep']['Blanks']).'</td>';
$html .= '<td class="right nowrap">'.formatNumber($arrayofmetrics['proj']['Comments'] + $arrayofmetrics['dep']['Comments']).'</td>';
$html .= '<td class="right nowrap">'.formatNumber($arrayofmetrics['proj']['Code'] + $arrayofmetrics['dep']['Code']).'</td>';
//$html .= '<td>'.$arrayofmetrics['Complexity'].'</td>';
$html .= '<td></td>';
$html .= '</tr>';
$html .= '</table>';
$html .= '</div>';

$html .= '</section>'."\n";

$html .= '<section class="chapter" id="projectvalue">'."\n";
$html .= '<h2>Project value</h2><br>'."\n";
$html .= '<div class="box inline-box back1">';
$html .= 'COCOMO (Basic organic model) value:<br>';
$html .= '<b>$'.formatNumber((empty($arraycocomo['proj']['currency']) ? 0 : $arraycocomo['proj']['currency']) + (empty($arraycocomo['dep']['currency']) ? 0 : $arraycocomo['dep']['currency']), 2).'</b>';
$html .= '</div>';
$html .= '<div class="box inline-box back2">';
$html .= 'COCOMO (Basic organic model) effort<br>';
$html .= '<b>'.formatNumber($arraycocomo['proj']['people'] * $arraycocomo['proj']['effort'] + $arraycocomo['dep']['people'] * $arraycocomo['dep']['effort']);
$html .= ' monthes people</b><br>';
$html .= '</section>'."\n";

$tmp = '';
$nblines = 0;
foreach ($output_arrtd as $line) {
	$reg = array();
	//print $line."\n";
	preg_match('/^::error file=(.*),line=(\d+),col=(\d+)::(.*)$/', $line, $reg);
	if (!empty($reg[1])) {
		$tmp .= '<tr><td>'.$reg[1].'</td><td>'.$reg[2].'</td><td>'.$reg[4].'</td></tr>'."\n";
		$nblines++;
	}
}

$html .= '<section class="chapter" id="technicaldebt">'."\n";
$html .= '<h2>Technical debt <span class="opacitymedium">(PHPStan level '.$phpstanlevel.' -> '.$nblines.' warnings)</span></h2><br>'."\n";
$html .= '<div class="div-table-responsive">'."\n";
$html .= '<table class="list_technical_debt">'."\n";
$html .= '<tr><td>File</td><td>Line</td><td>Type</td></tr>'."\n";
$html .= $tmp;
$html .= '</table>';
$html .= '</div>';
$html .= '</section>'."\n";

$html .= '
<script>
$(document).ready(function() {
$( ".seedetail" ).on( "click", function() {
	var source = $(this).attr("data-source");
  	console.log("Click on "+source);
	jQuery(".source"+source).toggle();
} );
});
</script>
';
$html .= '</body>';
$html .= '</html>';

$fh = fopen($outputpath, 'w');
if ($fh) {
	fwrite($fh, $html);
	fclose($fh);

	print 'Generation of output file '.$outputfile.' done.'."\n";
} else {
	print 'Failed to open '.$outputfile.' for ouput.'."\n";
}


/**
 * function to format a number
 *
 * @param	string|int		$number			Number to format
 * @param	int				$nbdec			Number of decimal digits
 * @return	string							Formated string
 */
function formatNumber($number, $nbdec = 0)
{
	return number_format($number, 0, '.', ' ');
}
