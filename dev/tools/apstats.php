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

error_reporting(E_ALL & ~ E_DEPRECATED);
define('PRODUCT', "apstats");
define('VERSION', "1.0");


print '***** '.constant('PRODUCT').' - '.constant('VERSION').' *****'."\n";
if (empty($argv[1])) {
	print 'You must run this tool being into the root of the project.'."\n";
	print 'Usage:   '.constant('PRODUCT').'.php pathto/index.html  [--dir-scc=pathtoscc] [--dir-phpstan=pathtophpstan]'."\n";
	print 'Example: '.constant('PRODUCT').'.php dev/tools/apstats.php documents/apstats/index.html --dir-phpstan=~/git/phpstan/htdocs/includes/bin';
	exit(0);
}

$outputpath = $argv[1];
$outputdir = dirname($outputpath);
$outputfile = basename($outputpath);

if (! is_dir($outputdir)) {
	print 'Error: dir '.$outputdir.' does not exists or is not writable'."\n";
	exit(1);
}

$dirscc = '';
$dirphpstan = '';

$i = 0;
while ($i < $argc) {
	$reg = array();
	if (preg_match('/--dir-scc=(.*)$/', $argv[$i], $reg)) {
		$dirphpstan = $reg[1];
	}
	if (preg_match('/--dir-phpstan=(.*)$/', $argv[$i], $reg)) {
		$dirphpstan = $reg[1];
	}
	$i++;
}


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
$commandcheck = ($dirscc ? $dirscc.'/' : '').'scc . --exclude-dir=includes,custom';
print 'Execute SCC to count lines of code in project: '.$commandcheck."\n";
$output_arrproj = array();
$resexecproj = 0;
exec($commandcheck, $output_arrproj, $resexecproj);


// Count lines of code of dependencies
$commandcheck = ($dirscc ? $dirscc.'/' : '').'scc htdocs/includes';
print 'Execute SCC to count lines of code in dependencies: '.$commandcheck."\n";
$output_arrdep = array();
$resexecdep = 0;
exec($commandcheck, $output_arrdep, $resexecdep);


// Get technical debt
$commandcheck = ($dirphpstan ? $dirphpstan.'/' : '').'phpstan -v analyze -a build/phpstan/bootstrap.php --memory-limit 5G --error-format=github';
print 'Execute PHPStan to get the technical debt: '.$commandcheck."\n";
$output_arrtd = array();
$resexectd = 0;
exec($commandcheck, $output_arrtd, $resexectd);

$arrayoflineofcode = array();
$arraycocomo = array();
$arrayofmetrics = array('Bytes'=>0, 'Files'=>0, 'Lines'=>0, 'Blanks'=>0, 'Comments'=>0, 'Code'=>0, 'Complexity'=>0);

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
			$arraycocomo[$source]['currency'] = preg_replace('/[^\d\.]/', '', str_replace(array(',', ' '), array('.', ''), $reg[1]));
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
}


/*
 * View
 */

$html = '<html>';
$html .= '
<style>
body {
	margin: 10px;
}

h1 {
	font-size: 1.5em;
	font-weight: bold;
	padding-top: 5px;
	padding-bottom: 5px;
	margin-top: 5px;
	margin-bottom: 5px;
}

header, section.chapter {
	margin-top: 10px;
	margin-bottom: 10px;
	padding: 10px;
}

.left {
	text-align: left;
}
.right {
	text-align: right;
}
.opacity {
	opacity: 0.5;
}
</style>';

$html .= '<body>';

$html .= '<header>';
$html .= '<h1>Advanced Project Statistics</h1>';
$currentDate = date("Y-m-d H:i:s"); // Format: Year-Month-Day Hour:Minute:Second
$html .= '<span class="opacity">Generated on '.$currentDate.'</span>';
$html .= '</header>';

$html .= '<section class="chapter">';
$html .= '<h2>Lines of code</h2>';
$html .= '<table>';
$html .= '<tr class="loc">';
$html .= '<th class="left">Language</td>';
$html .= '<th class="right">Files</th>';
$html .= '<th class="right">Lines</th>';
$html .= '<th class="right">Blanks</th>';
$html .= '<th class="right">Comments</th>';
$html .= '<th class="right">Code</th>';
//$html .= '<td class="right">'.$val['Complexity'].'</td>';
$html .= '</th>';
foreach (array('proj', 'dep') as $source) {
	if ($source == 'proj') {
		$html .= '<tr><td colspan="7">Written by project team only:</td></tr>';
	} elseif ($source == 'dep') {
		$html .= '<tr><td colspan="7">Dependencies:</td></tr>';
	}
	foreach ($arrayoflineofcode[$source] as $key => $val) {
		$html .= '<tr class="loc source'.$source.' language'.str_replace(' ', '', $key).'">';
		$html .= '<td>'.$key.'</td>';
		$html .= '<td class="right">'.$val['Files'].'</td>';
		$html .= '<td class="right">'.$val['Lines'].'</td>';
		$html .= '<td class="right">'.$val['Blanks'].'</td>';
		$html .= '<td class="right">'.$val['Comments'].'</td>';
		$html .= '<td class="right">'.$val['Code'].'</td>';
		//$html .= '<td class="right">'.$val['Complexity'].'</td>';
		$html .= '</tr>';
		$arrayofmetrics['Files'] += $val['Files'];
		$arrayofmetrics['Lines'] += $val['Lines'];
		$arrayofmetrics['Blanks'] += $val['Blanks'];
		$arrayofmetrics['Comments'] += $val['Comments'];
		$arrayofmetrics['Code'] += $val['Code'];
		$arrayofmetrics['Complexity'] += $val['Complexity'];
	}
}

$html .= '<tr>';
$html .= '<tr><td colspan="7">Total:</td></tr>';
$html .= '<tr>';
$html .= '<td class="right">'.$arrayofmetrics['Bytes'].' octets</td>';
$html .= '<td class="right">'.$arrayofmetrics['Files'].'</td>';
$html .= '<td class="right">'.$arrayofmetrics['Lines'].'</td>';
$html .= '<td class="right">'.$arrayofmetrics['Blanks'].'</td>';
$html .= '<td class="right">'.$arrayofmetrics['Comments'].'</td>';
$html .= '<td class="right">'.$arrayofmetrics['Code'].'</td>';
//$html .= '<td>'.$arrayofmetrics['Complexity'].'</td>';
$html .= '</tr>';
$html .= '<table>';

$html .= '</section>';

$html .= '<section class="chapter">';
$html .= '<h2>Project value:</h2><br>';
$html .= 'CODOMO (Basic model) value: $'.($arraycocomo['proj']['currency'] + $arraycocomo['dep']['currency']).'<br>';
$html .= 'CODOMO (Basic model) effort: '.($arraycocomo['proj']['people'] * $arraycocomo['proj']['effort'] + $arraycocomo['dep']['people'] * $arraycocomo['dep']['effort']).' Month people<br>';
$html .= '</section>';

$html .= '<section class="chapter">';
$html .= '<h2>Technical debt: ('.count($output_arrtd).')</h2><br>';
$html .= join('<br>'."\n", $output_arrtd);
$html .= '</section>';

$html .= '</boby>';
$html .= '</html>';

$fh = fopen($outputpath, 'w');
if ($fh) {
	fwrite($fh, $html);
	fclose($fh);

	print 'Generation of output file '.$outputfile.' done.'."\n";
} else {
	print 'Failed to open '.$outputfile.' for ouput.'."\n";
}
