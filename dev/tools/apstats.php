#!/usr/bin/env php
<?php
/*
 * Copyright (C) 2023 	   	Laurent Destailleur 	<eldy@users.sourceforge.net>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 * \brief   Script to report Advanced Statistics on a coding PHP project
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

$phpstanlevel = 3;

// Include Dolibarr environment
if (!is_readable("{$path}../../htdocs/config/config.php")) {
	define("DOL_DOCUMENT_ROOT", __DIR__."/../../htdocs");
} else {
	require_once $path.'../../htdocs/master.inc.php';
}
require_once $path.'../../htdocs/core/lib/files.lib.php';
require_once $path.'../../htdocs/core/lib/functions.lib.php';
require_once $path.'../../htdocs/core/lib/geturl.lib.php';

print '***** '.constant('PRODUCT').' - '.constant('VERSION').' *****'."\n";
if (empty($argv[1])) {
	print 'You must run this tool at the root of the project.'."\n";
	print 'Usage:   '.constant('PRODUCT').'.php  pathto/outputfile.html  [--dir-scc=pathtoscc|disabled] [--dir-phpstan=pathtophpstan|disabled] [--dir-phan=path/to/phan|disabled]'."\n";
	print 'Example: '.constant('PRODUCT').'.php  documents/apstats/index.html --dir-scc=/snap/bin --dir-phpstan=~/git/phpstan/htdocs/includes/bin --dir-phan=~/vendor/bin/phan';
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
$dir_phan = '';
$datatable_script = '';

$i = 0;
while ($i < $argc) {
	$reg = array();
	if (preg_match('/^--dir-scc=(.*)$/', $argv[$i], $reg)) {
		$dirscc = $reg[1];
	} elseif (preg_match('/^--dir-phpstan=(.*)$/', $argv[$i], $reg)) {
		$dirphpstan = $reg[1];
	} elseif (preg_match('/^--dir-phan=(.*)$/', $argv[$i], $reg)) {
		$dir_phan = $reg[1];
	}
	$i++;
}

if (!is_readable("{$path}phan/config.php")) {
	print "Skipping phan - configuration not found\n";
	// Disable phan while not integrated yet
	$dir_phan = 'disabled';
}


// Start getting data

$timestart = time();

// Retrieve the .git information
$urlgit = 'https://github.com/Dolibarr/dolibarr/blob/develop/';

// Count lines of code of application
$output_arrproj = array();
$output_arrdep = array();
if ($dirscc != 'disabled') {
	$commandcheck = ($dirscc ? $dirscc.'/' : '').'scc . --exclude-dir=htdocs/includes,htdocs/custom,htdocs/theme/common/fontawesome-5,htdocs/theme/common/octicons';
	print 'Execute SCC to count lines of code in project: '.$commandcheck."\n";
	$resexecproj = 0;
	exec($commandcheck, $output_arrproj, $resexecproj);


	// Count lines of code of dependencies
	$commandcheck = ($dirscc ? $dirscc.'/' : '').'scc htdocs/includes htdocs/theme/common/fontawesome-5 htdocs/theme/common/octicons';
	print 'Execute SCC to count lines of code in dependencies: '.$commandcheck."\n";
	$resexecdep = 0;
	exec($commandcheck, $output_arrdep, $resexecdep);
}

// Get technical debt
$output_arrtd = array();
if ($dirphpstan != 'disabled') {
	$commandcheck = ($dirphpstan ? $dirphpstan.'/' : '').'phpstan --level='.$phpstanlevel.' -v analyze -a build/phpstan/bootstrap.php --memory-limit 5G --error-format=github';
	print 'Execute PHPStan to get the technical debt: '.$commandcheck."\n";
	$resexectd = 0;
	exec($commandcheck, $output_arrtd, $resexectd);
}

$output_phan_json = array();
$res_exec_phan = 0;
if ($dir_phan != 'disabled') {
	// Get technical debt (phan)
	$PHAN_CONFIG = "dev/tools/phan/config_extended.php";
	$PHAN_BASELINE = "dev/tools/phan/baseline.txt";
	$PHAN_MIN_PHP = "7.0";
	$PHAN_MEMORY_OPT = "--memory-limit 5G";

	$commandcheck
		= ($dir_phan ? $dir_phan.DIRECTORY_SEPARATOR : '')
		  ."phan --output-mode json $PHAN_MEMORY_OPT -k $PHAN_CONFIG -B $PHAN_BASELINE --analyze-twice --minimum-target-php-version $PHAN_MIN_PHP";
	print 'Execute Phan to get the technical debt: '.$commandcheck."\n";
	exec($commandcheck, $output_phan_json, $res_exec_phan);
}


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

// Search the max
$arrayofmax = array('Lines' => 0);
foreach (array('proj', 'dep') as $source) {
	if (!empty($arrayoflineofcode[$source])) {
		foreach ($arrayoflineofcode[$source] as $val) {
			$arrayofmax['Lines'] = max($arrayofmax['Lines'], $val['Lines']);
		}
	}
}


$nbofmonth = 2;
$delay = (3600 * 24 * 30 * $nbofmonth);

// Get stats on nb of commits
$commandcheck = "git log --shortstat --no-renames --no-merges --use-mailmap --pretty=".escapeshellarg('format:%cI;%H;%aN;%aE;%ce;%s')." --since=".dol_print_date(dol_now() - $delay, '%Y-%m-%d'); // --since=  --until=...
print 'Execute git log to get list of commits: '.$commandcheck."\n";
$output_arrglpu = array();
$resexecglpu = 0;
//exec($commandcheck, $output_arrglpu, $resexecglpu);


// Retrieve the git information for security alerts
$nbofmonth = 2;
$delay = (3600 * 24 * 30 * $nbofmonth);
$arrayofalerts = array();

$commandcheck = "git log --shortstat --no-renames --no-merges --use-mailmap --pretty=".escapeshellarg('format:%cI;%H;%aN;%aE;%ce;%s')." --since=".escapeshellarg(dol_print_date(dol_now() - $delay, '%Y-%m-%d'))." | grep -E ".escapeshellarg("(yogosha|CVE|Sec:)");
print 'Execute git log to get commits related to security: '.$commandcheck."\n";
$output_arrglpu = array();
$resexecglpu = 0;
exec($commandcheck, $output_arrglpu, $resexecglpu);
foreach ($output_arrglpu as $val) {
	$tmpval = cleanVal2($val);
	if (preg_match('/(yogosha|CVE|Sec:)/i', $tmpval['title'])) {
		$alreadyfound = '';
		$alreadyfoundcommitid = '';
		foreach ($arrayofalerts as $val) {
			if ($val['issueidyogosha'] && $val['issueidyogosha'] == $tmpval['issueidyogosha']) {	// Already in list
				$alreadyfound = 'yogosha';
				$alreadyfoundcommitid = $val['commitid'];
				break;
			}
			if ($val['issueid'] && $val['issueid'] == $tmpval['issueid']) {	// Already in list
				$alreadyfound = 'git';
				$alreadyfoundcommitid = $val['commitid'];
				break;
			}
			if ($val['issueidcve'] && $val['issueidcve'] == $tmpval['issueidcve']) {	// Already in list
				$alreadyfound = 'cve';
				$alreadyfoundcommitid = $val['commitid'];
				break;
			}
		}
		//$alreadyfound=0;
		if (!$alreadyfound) {
			$arrayofalerts[$tmpval['commitid']] = $tmpval;
		} else {
			if (empty($arrayofalerts[$alreadyfoundcommitid]['commitidbis'])) {
				$arrayofalerts[$alreadyfoundcommitid]['commitidbis'] = array();
			}
			$arrayofalerts[$alreadyfoundcommitid]['commitidbis'][] = $tmpval['commitid'];
		}
	}
}


/*
//$urlgit = 'https://api.github.com/search/issues?q=is:pr+repo:Dolibarr/dolibarr+created:>'.dol_print_date(dol_now() - $delay, "%Y-%m");
$urlgit = 'https://api.github.com/search/commits?q=repo:Dolibarr/dolibarr+yogosha+created:>'.dol_print_date(dol_now() - $delay, "%Y-%m");

// Count lines of code of application
$newurl = $urlgit.'+CVE';
$result = getURLContent($newurl);
print 'Execute GET on github for '.$newurl."\n";
if ($result && $result['http_code'] == 200) {
	$arrayofalerts1 = json_decode($result['content']);

	foreach ($arrayofalerts1->items as $val) {
		$tmpval = cleanVal($val);
		if (preg_match('/CVE/i', $tmpval['title'])) {
			$arrayofalerts[$tmpval['number']] = $tmpval;
		}
	}
} else {
	print 'Error: failed to get github response';
	exit(-1);
}

$newurl = $urlgit.'+yogosha';
$result = getURLContent($newurl);
print 'Execute GET on github for '.$newurl."\n";
if ($result && $result['http_code'] == 200) {
	$arrayofalerts2 = json_decode($result['content']);

	foreach ($arrayofalerts2->items as $val) {
		$tmpval = cleanVal($val);
		if (preg_match('/yogosha:/i', $tmpval['title'])) {
			$arrayofalerts[$tmpval['number']] = $tmpval;
		}
	}
} else {
	print 'Error: failed to get github response';
	exit(-1);
}

$newurl = $urlgit.'+Sec:';
$result = getURLContent($newurl);
print 'Execute GET on github for '.$newurl."\n";
if ($result && $result['http_code'] == 200) {
	$arrayofalerts3 = json_decode($result['content']);
	foreach ($arrayofalerts3->items as $val) {
		$tmpval = cleanVal($val);
		if (preg_match('/Sec:/i', $tmpval['title'])) {
			$arrayofalerts[$tmpval['number']] = $tmpval;
		}
	}
} else {
	print 'Error: failed to get github response';
	exit(-1);
}
*/

$timeend = time();


/*
 * View
 */

$html = '<html>'."\n";
$html .= '<meta charset="utf-8">'."\n";
$html .= '<meta http-equiv="refresh" content="300">'."\n";
$html .= '<meta name="viewport" content="width=device-width, initial-scale=1.0">'."\n";
$html .= '<meta name="keywords" content="erp, crm, dolibarr, statistics, project, security alerts">'."\n";
$html .= '<meta name="title" content="Dolibarr project statistics">'."\n";
$html .= '<meta name="description" content="Statistics about the Dolibarr ERP CRM Open Source project (lines of code, contributions, security alerts, technical debt...">'."\n";
$html .= '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.9.0/css/all.min.css" integrity="sha512-q3eWabyZPc1XTCmF+8/LuE1ozpg5xxn7iO89yfSOd5/oKvyqLngoNGsx8jq92Y8eXJ/IRxQbEC+FGSYxtk2oiw==" crossorigin="anonymous" referrerpolicy="no-referrer" />'."\n";
$html .= '<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js" integrity="sha512-3gJwYpMe3QewGELv8k/BX9vcqhryRdzRMxVfq6ngyWXwo03GFEzjsUm8Q7RZcHPHksttq7/GFoxjCVUjkjvPdw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>'."\n";
$html .= '<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">';
$html .= '<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>';
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
.hiddenimp {
	display: none !important;
}
.trgroup {
	border-bottom: 1px solid #aaa;
}
.tdoverflowmax100 {
	max-width: 100px;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}
.tdoverflowmax300 {
	max-width: 300px;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}
.seedetail {
	color: #000088;
}
.box {
	padding: 20px;
	font-size: 1.2em;
	margin-top: 10px;
	margin-bottom: 10px;
	width: 200px;
}
.inline-block {
	display: inline-block;
}
.inline {
	display: inline;
}
.box.inline-box {
    display: inline-block;
	text-align: center;
	margin-left: 10px;
}
.boxallwidth {
    border-radius: 9px;
    border-color: #000;
    border-width: 2px;
    padding: 5px;
    border-style: solid;
	background-color: #f8f8f8;
}
.back1 {
	background-color: #884466;
	color: #FFF;
}
.back2 {
	background-color: #664488;
	color: #FFF;
}
.badge {
	padding: 2px;
	background-color: #eee;
}
.seeothercommit, .seedetail {
	cursor: pointer;
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
.list_technical_debt {
	/* font-size: smaller */
}
.pictofixedwidth {
	font-size: smaller;
    width: 28px;
    vertical-align: middle;
}
.bargraph {
	background-color: #358;
}
.small {
	font-size: smaller;
}
.fr {
	float: right;
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


// Header

$html .= '<header>'."\n";
$html .= '<h1>Advanced Project Statistics</h1>'."\n";
$currentDate = date("Y-m-d H:i:s"); // Format: Year-Month-Day Hour:Minute:Second
$html .= '<span class="opacitymedium">Generated on '.$currentDate.' in '.($timeend - $timestart).' seconds</span>'."\n";
$html .= '</header>'."\n";


// Lines of code

$html .= '<section class="chapter" id="linesofcode">'."\n";
$html .= '<h2><span class="fas fa-code pictofixedwidth"></span>Lines of code</h2>'."\n";

$html .= '<div class="boxallwidth">'."\n";
$html .= '<div class="div-table-responsive">'."\n";
$html .= '<table class="centpercent">';
$html .= '<tr class="loc">';
$html .= '<th class="left" style="min-width: 150px">Language</th>';
$html .= '<th class="right">Bytes</th>';
$html .= '<th class="right">Files</th>';
$html .= '<th class="right">Lines</th>';
$html .= '<th></th>';
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
	$html .= ' &nbsp; &nbsp; <div class="seedetail fr" data-source="'.$source.'"><span class="fas fa-chart-bar pictofixedwidth"></span>See detail per file type...</span>';
	$html .= '<td class="right">'.formatNumber($arrayofmetrics[$source]['Bytes']).'</td>';
	$html .= '<td class="right">'.formatNumber($arrayofmetrics[$source]['Files']).'</td>';
	$html .= '<td class="right">'.formatNumber($arrayofmetrics[$source]['Lines']).'</td>';
	$html .= '<td></td>';
	$html .= '<td class="right">'.formatNumber($arrayofmetrics[$source]['Blanks']).'</td>';
	$html .= '<td class="right">'.formatNumber($arrayofmetrics[$source]['Comments']).'</td>';
	$html .= '<td class="right">'.formatNumber($arrayofmetrics[$source]['Code']).'</td>';
	//$html .= '<td></td>';
	$html .= '</tr>';
	if (!empty($arrayoflineofcode[$source])) {
		foreach ($arrayoflineofcode[$source] as $key => $val) {
			$html .= '<tr class="loc hidden source'.$source.' language'.str_replace(' ', '', $key).'">';
			$html .= '<td>'.$key.'</td>';
			$html .= '<td class="right"></td>';
			$html .= '<td class="right nowrap">'.(empty($val['Files']) ? '' : formatNumber($val['Files'])).'</td>';
			$html .= '<td class="right nowrap">'.(empty($val['Lines']) ? '' : formatNumber($val['Lines'])).'</td>';
			$html .= '<td class="nowrap">';
			$percent = $val['Lines'] / $arrayofmax['Lines'];
			$widthbar = round(200 * $percent);
			$html .= '<div class="bargraph" style="width: '.max(1, $widthbar).'px">&nbsp;</div>';
			$html .= '</td>';
			$html .= '<td class="right nowrap">'.(empty($val['Blanks']) ? '' : formatNumber($val['Blanks'])).'</td>';
			$html .= '<td class="right nowrap">'.(empty($val['Comments']) ? '' : formatNumber($val['Comments'])).'</td>';
			$html .= '<td class="right nowrap">'.(empty($val['Code']) ? '' : formatNumber($val['Code'])).'</td>';
			//$html .= '<td class="right">'.(empty($val['Complexity']) ? '' : $val['Complexity']).'</td>';
			/*$html .= '<td class="nowrap">';
			$html .= '';
			$html .= '</td>';
			*/
			$html .= '</tr>';
		}
	}
}

$html .= '<tr class="trgrouptotal">';
$html .= '<td class="left">Total</td>';
$html .= '<td class="right nowrap">'.formatNumber($arrayofmetrics['proj']['Bytes'] + $arrayofmetrics['dep']['Bytes']).'</td>';
$html .= '<td class="right nowrap">'.formatNumber($arrayofmetrics['proj']['Files'] + $arrayofmetrics['dep']['Files']).'</td>';
$html .= '<td class="right nowrap">'.formatNumber($arrayofmetrics['proj']['Lines'] + $arrayofmetrics['dep']['Lines']).'</td>';
$html .= '<td></td>';
$html .= '<td class="right nowrap">'.formatNumber($arrayofmetrics['proj']['Blanks'] + $arrayofmetrics['dep']['Blanks']).'</td>';
$html .= '<td class="right nowrap">'.formatNumber($arrayofmetrics['proj']['Comments'] + $arrayofmetrics['dep']['Comments']).'</td>';
$html .= '<td class="right nowrap">'.formatNumber($arrayofmetrics['proj']['Code'] + $arrayofmetrics['dep']['Code']).'</td>';
//$html .= '<td>'.$arrayofmetrics['Complexity'].'</td>';
//$html .= '<td></td>';
$html .= '</tr>';
$html .= '</table>';
$html .= '</div>';
$html .= '</div>';

$html .= '</section>'."\n";


// Contributions

$html .= '<section class="chapter" id="projectvalue">'."\n";
$html .= '<h2><span class="fas fa-tasks pictofixedwidth"></span>Contributions</h2>'."\n";

$html .= '<div class="boxallwidth">'."\n";

$html .= 'TODO...';

$html .= '<!-- ';
foreach ($output_arrglpu as $line) {
	$html .= $line."\n";
}
$html .= ' -->';

$html .= '</div>';

$html .= '</section>'."\n";


// Contributors

$html .= '<section class="chapter" id="projectvalue">'."\n";
$html .= '<h2><span class="fas fa-user pictofixedwidth"></span>Contributors</h2>'."\n";

$html .= '<div class="boxallwidth">'."\n";

$html .= 'TODO...';

$html .= '</div>';

$html .= '</section>'."\n";


// Project value

$html .= '<section class="chapter" id="projectvalue">'."\n";
$html .= '<h2><span class="fas fa-dollar-sign pictofixedwidth"></span>Project value</h2>'."\n";

$html .= '<div class="boxallwidth">'."\n";
$html .= '<div class="box inline-box back1">';
$html .= 'COCOMO value<br><span class="small opacitymedium">(Basic organic model)</span><br>';
$html .= '<b>$'.formatNumber((empty($arraycocomo['proj']['currency']) ? 0 : $arraycocomo['proj']['currency']) + (empty($arraycocomo['dep']['currency']) ? 0 : $arraycocomo['dep']['currency']), 2).'</b>';
$html .= '</div>';
if (array_key_exists('proj', $arraycocomo)) {
	$html .= '<div class="box inline-box back2">';
	$html .= 'COCOMO effort<br><span class="small opacitymedium">(Basic organic model)</span><br>';
	$html .= '<b>'.formatNumber($arraycocomo['proj']['people'] * $arraycocomo['proj']['effort'] + $arraycocomo['dep']['people'] * $arraycocomo['dep']['effort']);
	$html .= ' months people</b>';
	$html .= '</div>';
}
$html .= '</div>';

$html .= '</section>'."\n";


$tmpstan = '';
$nblines = 0;
if (!empty($output_arrtd)) {
	foreach ($output_arrtd as $line) {
		$reg = array();
		//print $line."\n";
		preg_match('/^::error file=(.*),line=(\d+),col=(\d+)::(.*)$/', $line, $reg);
		if (!empty($reg[1])) {
			if ($nblines < 20) {
				$tmpstan .= '<tr class="nohidden">';
			} else {
				$tmpstan .= '<tr class="hidden sourcephpstan">';
			}
			$tmpstan .= '<td>'.dolPrintLabel($reg[1]).'</td>';
			$tmpstan .= '<td class="">';
			$tmpstan .= '<a href="'.($urlgit.$reg[1].'#L'.$reg[2]).'" target="_blank">'.dolPrintLabel($reg[2]).'</a>';
			$tmpstan .= '</td>';
			$tmpstan .= '<td class="tdoverflowmax300" title="'.dolPrintHTMLForAttribute($reg[4]).'">'.dolPrintLabel($reg[4]).'</td>';
			$tmpstan .= '</tr>'."\n";

			$nblines++;
		}
	}
}

$tmpphan = '';
$phan_nblines = 0;
if (count($output_phan_json) != 0) {
	$phan_notices = json_decode($output_phan_json[count($output_phan_json) - 1], true);
	// Info: result code is $res_exec_phan
	'@phan-var-force array<array{type:string,type_id:int,check_name:string,description:string,severity:int,location:array{path:string,lines:array{begin:int,end:int}}}> $phan_notices';
	$phan_items = [];
	foreach ($phan_notices as $notice) {
		if (!empty($notice['location'])) {
			$path = $notice['location']['path'];
			if ($path == 'internal') {
				continue;
			}
			$line_start = $notice['location']['lines']['begin'];
			$line_end = $notice['location']['lines']['end'];
			if ($line_start == $line_end) {
				$line_range = "#L{$line_start}";
				$line_range_txt = $line_start;
			} else {
				$line_range = "#L{$line_start}-L{$line_end}";
				$line_range_txt = "{$line_start}-{$line_end}";
			}
			$code_url_attr = dol_escape_htmltag($urlgit.$path.$line_range);
			if ($phan_nblines < 20) {
				$tmpphan .= '<tr class="nohidden">';
			} else {
				$tmpphan .= '<tr class="hidden sourcephan">';
			}
			$tmpphan .= '<td>'.dolPrintLabel($path).'</td>';
			$tmpphan .= '<td class="">';
			$tmpphan .= '<a href="'.$code_url_attr.'" target="_blank">'.$line_range_txt.'</a>';
			$tmpphan .= '</td>';
			$tmpphan .= '<td class="tdoverflowmax300" title="'.dolPrintHTMLForAttribute($notice['description']).'">'.dolPrintLabel($notice['description']).'</td>';
			$tmpphan .= '</tr>';
			$tmpphan .= "\n";

			$phan_nblines++;
		}
	}
}


// Last security errors

$html .= '<section class="chapter" id="linesofcode">'."\n";
$html .= '<h2><span class="fas fa-code pictofixedwidth"></span>Last security issues <span class="opacitymedium">(last '.($nbofmonth != 1 ? $nbofmonth.' months' : 'month').')</span></h2>'."\n";

$html .= '<div class="boxallwidth">'."\n";
$html .= '<div class="div-table-responsive">'."\n";
$html .= '<table class="list_technical_debt centpercent">'."\n";
$html .= '<tr class="trgroup"><td>Commit ID</td><td>Date</td><td style="white-space: nowrap">Reported on<br>Yogosha</td><td style="white-space: nowrap">Reported on<br>GIT</td><td style="white-space: nowrap">Reported on<br>CVE</td><td>Title</td></tr>'."\n";
foreach ($arrayofalerts as $alert) {
	$html .= '<tr style="vertical-align: top;">';
	$html .= '<td class="nowrap">';
	$html .= '<a target="_blank" href="https://github.com/Dolibarr/dolibarr/commit/'.$alert['commitid'].'">'.dol_trunc($alert['commitid'], 8).'</a>';
	if (!empty($alert['commitidbis'])) {
		$html .= ' <div class="more inline"><span class="seeothercommit badge">+</span><div class="morediv hidden">';
		foreach ($alert['commitidbis'] as $tmpcommitidbis) {
			$html .= '<a target="_blank" href="https://github.com/Dolibarr/dolibarr/commit/'.$tmpcommitidbis.'">'.dol_trunc($tmpcommitidbis, 8).'</a><br>';
		}
		$html .= '</div></div>';
	}
	$html .= '</td>';
	$html .= '<td style="white-space: nowrap">';
	$html .= preg_replace('/T.*$/', '', $alert['created_at']);
	$html .= '</td>';
	$html .= '<td style="white-space: nowrap">';
	if (!empty($alert['issueidyogosha'])) {
		//$html .= '<a target="_blank" href="https://yogosha.com?'.$alert['issueidyogosha'].'">';
		$html .= '#yogosha'.$alert['issueidyogosha'];
		//$html .= '</a>';
	} else {
		//$html .= '<span class="opacitymedium">public issue</span>';
	}
	$html .= '</td>';
	$html .= '<td style="white-space: nowrap">';
	if (!empty($alert['issueid'])) {
		$html .= '<a target="_blank" href="https://github.com/Dolibarr/dolibarr/issues/'.$alert['issueid'].'">#'.$alert['issueid'].'</a>';
	} else {
		//$html .= '<span class="opacitymedium">private</span>';
	}
	$html .= '</td>';
	$html .= '<td style="white-space: nowrap">';
	if (!empty($alert['issueidcve'])) {
		$cve = preg_replace('/\s+/', '-', trim($alert['issueidcve']));
		$html .= '<a target="_blank" href="https://nvd.nist.gov/vuln/detail/CVE-'.$cve.'">CVE-'.$cve.'</a>';
	}
	$html .= '</td>';
	$html .= '<td class="tdoverflowmax300" title="'.dol_escape_htmltag($alert['title']).'">'.dol_escape_htmltag($alert['title']).'</td>';
	$html .= '</tr>';
}
$html .= '</table>';
$html .= '</div>';
$html .= '</div>';
$html .= '</section>';


// Technical debt PHPstan

if ($dirphpstan != 'disabled') {
	$datatable_script .= '
 if (typeof(DataTable)==="function") {jQuery(".sourcephpstan").toggle(true);}
 let phpstantable = new DataTable("#technicaldebt table", {
    lengthMenu: [
        [10, 25, 50, 100, -1],
        [10, 25, 50, 100, \'All\']
    ]});
';
	$html .= '<section class="chapter" id="technicaldebt">'."\n";
	$html .= '<h2><span class="fas fa-book-dead pictofixedwidth"></span>Technical debt <span class="opacitymedium">(PHPStan level '.$phpstanlevel.' -> '.$nblines.' warnings)</span></h2>'."\n";

	$html .= '<div class="boxallwidth">'."\n";
	$html .= '<div class="div-table-responsive">'."\n";
	$html .= '<table class="list_technical_debt centpercent">'."\n";
	$html .= '<thead><tr class="trgroup"><td>File</td><td>Line</td><td>Type</td></tr></thead><tbody>'."\n";
	$html .= $tmpstan;
	$html .= '<tbody></table>';
	// Disabled, no more required as list is managed with datatable
	//$html .= '<div><span class="seedetail" data-source="phpstan" id="sourcephpstan">Show all...</span></div>';
	$html .= '</div></div>';

	$html .= '</section>'."\n";
}


// Technical debt Phan

if ($dir_phan != 'disabled') {
	$datatable_script .= '
 if (typeof(DataTable)==="function") {jQuery(".sourcephan").toggle(true);}
 let phantable = new DataTable("#technicaldebtphan table", {
    lengthMenu: [
        [10, 25, 50, 100, -1],
        [10, 25, 50, 100, \'All\']
    ]});
';
	$html .= '<section class="chapter" id="technicaldebtphan">'."\n";
	$html .= '<h2><span class="fas fa-book-dead pictofixedwidth"></span>Technical debt <span class="opacitymedium">(PHAN '.$phan_nblines.' warnings)</span></h2>'."\n";

	$html .= '<div class="boxallwidth">'."\n";
	$html .= '<div class="div-table-responsive">'."\n";
	$html .= '<table class="list_technical_debt centpercent">'."\n";
	$html .= '<thead><tr class="trgroup"><td>File</td><td>Line</td><td>Detail</td></tr></thead><tbody>'."\n";
	$html .= $tmpphan;
	$html .= '</tbody></table>';
	// Disabled, no more required as list is managed with datatable
	//$html .= '<div><span class="seedetail" data-source="phan" id="sourcephan">Show all...</span></div>';
	$html .= '</div></div>';

	$html .= '</section>'."\n";
}


// JS code to allow to expand/collapse

$html .= '
<script>
$(document).ready(function() {
	$(".seeothercommit").on("click", function() {
	  	console.log("Click on seeothercommit");
 		$(this).closest(\'.more\').find(\'.morediv\').toggle();
	});
	$(".seedetail").on("click", function() {
		var source = $(this).attr("data-source");
	  	console.log("Click on "+source+" so we show class .source"+source);
		jQuery(".source"+source).toggle();
	} );
	'.$datatable_script.'
});
</script>
';
$html .= '</body>';
$html .= '</html>';

// Output report into a HTML file
$fh = fopen($outputpath, 'w');
if ($fh) {
	fwrite($fh, $html);
	fclose($fh);

	print 'Generation of output file '.$outputfile.' done.'."\n";
} else {
	print 'Failed to open '.$outputfile.' for output.'."\n";
}


/**
 * function to format a number
 *
 * @param	string|int		$number			Number to format
 * @param	int				$nbdec			Number of decimal digits
 * @return	string							Formatted string
 */
function formatNumber($number, $nbdec = 0)
{
	return number_format($number, 0, '.', ' ');
}

/**
 * cleanVal
 *
 * @param array 	$val		Array of a PR
 * @return 						Array of a PR
 */
function cleanVal($val)
{
	$tmpval = array();

	$tmpval['url'] = $val->url;
	$tmpval['number'] = $val->number;
	$tmpval['title'] = $val->title;
	$tmpval['created_at'] = $val->created_at;
	$tmpval['updated_at'] = $val->updated_at;

	return $tmpval;
}

/**
 * cleanVal2
 *
 * @param array 	$val		Array of a PR
 * @return 						Array of a PR
 */
function cleanVal2($val)
{
	$tmp = explode(';', $val);

	$tmpval = array();
	$tmpval['commitid'] = $tmp[1];
	$tmpval['url'] = '';
	$tmpval['issueid'] = '';
	$tmpval['issueidyogosha'] = '';
	$tmpval['issueidcve'] = '';
	$tmpval['title'] = array_key_exists(5, $tmp) ? $tmp[5] : '';
	$tmpval['created_at'] = array_key_exists(0, $tmp) ? $tmp[0] : '';
	$tmpval['updated_at'] = '';

	$reg = array();
	if (preg_match('/#(\d+)/', $tmpval['title'], $reg)) {
		$tmpval['issueid'] = $reg[1];
	}
	if (preg_match('/CVE([0-9\-\s]+)/', $tmpval['title'], $reg)) {
		$tmpval['issueidcve'] = preg_replace('/^\-/', '', trim($reg[1]));
	}
	if (preg_match('/#yogosha(\d+)/i', $tmpval['title'], $reg)) {
		$tmpval['issueidyogosha'] = $reg[1];
	}

	return $tmpval;
}
