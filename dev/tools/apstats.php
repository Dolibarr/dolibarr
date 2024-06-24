#!/usr/bin/env php
<?php
/*
 * Copyright (C) 2023-2024 	Laurent Destailleur 	<eldy@users.sourceforge.net>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 * \brief   Script to report Advanced Statistics and Status on a PHP project
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
	print 'Example: '.constant('PRODUCT').'.php  documents/apstats/index.html --dir-scc=/snap/bin --dir-phpstan=~/git/phpstan/htdocs/includes/bin --dir-phan=~/vendor/bin/phan --url-site=https://www.dolibarr.org';
	exit(0);
}

$outputpath = $argv[1];
$outputdir = dirname($outputpath);
$outputfile = basename($outputpath);
$outputfilerss = preg_replace('/\.\w+$/i', '', $outputfile).'-security.rss';

if (!is_dir($outputdir)) {
	print 'Error: dir '.$outputdir.' does not exists or is not writable'."\n";
	exit(1);
}

$dirscc = '';
$dirphpstan = '';
$dir_phan = '';
$datatable_script = '';
$url_root = '';
$url_site = '';
$url_flux = '';
$project = '';

$i = 0;
while ($i < $argc) {
	$reg = array();
	if (preg_match('/^--dir-scc=(.*)$/', $argv[$i], $reg)) {
		$dirscc = $reg[1];
	} elseif (preg_match('/^--dir-phpstan=(.*)$/', $argv[$i], $reg)) {
		$dirphpstan = $reg[1];
	} elseif (preg_match('/^--dir-phan=(.*)$/', $argv[$i], $reg)) {
		$dir_phan = $reg[1];
	} elseif (preg_match('/^--url-root=(.*)$/', $argv[$i], $reg)) {
		$url_root = $reg[1];
	} elseif (preg_match('/^--url-site=(.*)$/', $argv[$i], $reg)) {
		$url_site = $reg[1];
	} elseif (preg_match('/^--project-name=(.*)$/', $argv[$i], $reg)) {
		$project = $reg[1];
	}

	$i++;
}

// PHPSTAN setup
$PHPSTANLEVEL = 4;

// PHAN setup. Configuration is required, otherwise phan is disabled.
$PHAN_CONFIG = "{$path}phan/config_extended.php";
$PHAN_BASELINE = "{$path}phan/baseline_extended.txt";		// BASELINE is ignored if it does not exist
$PHAN_MIN_PHP = "7.0";
$PHAN_MEMORY_OPT = "--memory-limit 5G";

if (!is_readable($PHAN_CONFIG)) {
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

// Get technical debt with PHPStan
$output_arrtd = array();
if ($dirphpstan != 'disabled') {
	$commandcheck = ($dirphpstan ? $dirphpstan.'/' : '').'phpstan --version';
	print 'Execute PHPStan to get the version: '.$commandcheck."\n";
	$resexectd = 0;
	exec($commandcheck, $output_arrtd, $resexectd);
}
$phpstanversion = $output_arrtd[0];

$output_arrtd = array();
if ($dirphpstan != 'disabled') {
	$commandcheck = ($dirphpstan ? $dirphpstan.'/' : '').'phpstan --level='.$PHPSTANLEVEL.' -v analyze -a build/phpstan/bootstrap.php --memory-limit 8G --error-format=github';
	print 'Execute PHPStan to get the technical debt: '.$commandcheck."\n";
	$resexectd = 0;
	exec($commandcheck, $output_arrtd, $resexectd);
}

// Get technical debt with Phan
$output_phan_json = array();
$res_exec_phan = 0;
if ($dir_phan != 'disabled') {
	if (is_readable($PHAN_BASELINE)) {
		$PHAN_BASELINE_OPT = "-B '${PHAN_BASELINE}'";
	} else {
		$PHAN_BASELINE_OPT = '';
	}
	// Get technical debt (phan)
	$commandcheck
		= ($dir_phan ? $dir_phan.DIRECTORY_SEPARATOR : '')
		  ."phan --output-mode json $PHAN_MEMORY_OPT -k '$PHAN_CONFIG' $PHAN_BASELINE_OPT --analyze-twice --minimum-target-php-version $PHAN_MIN_PHP";
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
$commandcheck = "git log --all --shortstat --no-renames --no-merges --use-mailmap --pretty=".escapeshellarg('format:%cI;%H;%aN;%aE;%ce;%s')." --since=".dol_print_date(dol_now() - $delay, '%Y-%m-%d'); // --since=  --until=...
print 'Execute git log to get list of commits: '.$commandcheck."\n";
$output_arrglpu = array();
$resexecglpu = 0;
//exec($commandcheck, $output_arrglpu, $resexecglpu);


// Get git information for security alerts
$nbofmonth = 3;
$delay = (3600 * 24 * 30 * $nbofmonth);
$arrayofalerts = array();

$commandcheck = "git log --all --shortstat --no-renames --use-mailmap --pretty=".escapeshellarg('format:%cI;%H;%aN;%aE;%ce;%s')." --since=".escapeshellarg(dol_print_date(dol_now() - $delay, '%Y-%m-%d'))." | grep -i -E ".escapeshellarg("(#yogosha|CVE|Sec:|Sec )");
print 'Execute git log to get commits related to security: '.$commandcheck."\n";
$output_arrglpu = array();
$resexecglpu = 0;
exec($commandcheck, $output_arrglpu, $resexecglpu);
foreach ($output_arrglpu as $val) {
	// Parse the line to split interesting data
	$tmpval = cleanVal2($val);

	if (preg_match('/(#yogosha|CVE|Sec:|Sec\s)/i', $tmpval['title'])) {
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
			if ($val['title'] && $val['title'] == $tmpval['title']) {	// Already in list
				$alreadyfound = 'title';
				$alreadyfoundcommitid = $val['commitid'];
				break;
			}
		}
		//$alreadyfound=0;
		if (!$alreadyfound) {
			// Get branch names
			$commandgetbranch = "git branch -r --contains '".$tmpval['commitid']."'";
			print 'Execute git branch to get the name of branches for the commit: '.$commandgetbranch."\n";
			$output_arrgetbranch = array();
			$resexecgetbranch = 0;
			exec($commandgetbranch, $output_arrgetbranch, $resexecgetbranch);

			foreach ($output_arrgetbranch as $valbranch) {
				if (empty($tmpval['branch'])) {
					$tmpval['branch'] = array();
				}
				if (preg_match('/^\s*origin\/(develop|\d)/', $valbranch)) {
					$tmpval['branch'][] = preg_replace('/^\s*origin\//', '', $valbranch);
				}
			}

			$arrayofalerts[$tmpval['commitid']] = $tmpval;
		} else {
			if (empty($arrayofalerts[$alreadyfoundcommitid]['commitidbis'])) {
				$arrayofalerts[$alreadyfoundcommitid]['commitidbis'] = array();
			}

			// Get branch names
			$commandgetbranch = "git branch -r --contains '".$tmpval['commitid']."'";
			print 'Execute git branch to get the name of branches for the commit: '.$commandgetbranch."\n";
			$output_arrgetbranch = array();
			$resexecgetbranch = 0;
			exec($commandgetbranch, $output_arrgetbranch, $resexecgetbranch);

			foreach ($output_arrgetbranch as $valbranch) {
				if (empty($tmpval['branch'])) {
					$tmpval['branch'] = array();
				}
				if (preg_match('/^\s*origin\/(develop|\d)/', $valbranch)) {
					$tmpval['branch'][] = preg_replace('/^\s*origin\//', '', $valbranch);
				}
			}
			/*var_dump($tmpval['commitid'].' '.$alreadyfoundcommitid);
			var_dump($arrayofalerts[$alreadyfoundcommitid]['branch']);
			var_dump($tmpval);*/
			$arrayofalerts[$alreadyfoundcommitid]['branch'] = array_merge($arrayofalerts[$alreadyfoundcommitid]['branch'], $tmpval['branch']);

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
	.boxallwidth picture img {
	    width: 100%;
	}
}

</style>'."\n";

$html .= '<body>'."\n";


// Header

$html .= '<header>'."\n";
$html .= '<h1>Advanced Project Status</h1>'."\n";
$currentDate = date("Y-m-d H:i:s"); // Format: Year-Month-Day Hour:Minute:Second
$html .= '<span class="opacitymedium">Generated on '.$currentDate.' in '.($timeend - $timestart).' seconds by <a target="_blank" href="https://github.com/Dolibarr/dolibarr/blob/develop/dev/tools/apstats.php">apstats</a></span>'."\n";
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

// OSSINSIGHT graph
/*
$html .= <<<END
<br>
<!-- Copy-paste in your Readme.md file -->

<a href="https://next.ossinsight.io/widgets/official/analyze-repo-loc-per-month?repo_id=1957456" target="_blank" style="display: block" align="center">
<picture>
<source media="(prefers-color-scheme: dark)" srcset="https://next.ossinsight.io/widgets/official/analyze-repo-loc-per-month/thumbnail.png?repo_id=1957456&image_size=auto&color_scheme=dark" width="721" height="auto">
<img alt="Lines of Code Changes of Dolibarr/dolibarr" src="https://next.ossinsight.io/widgets/official/analyze-repo-loc-per-month/thumbnail.png?repo_id=1957456&image_size=auto&color_scheme=light" width="721" height="auto">
</picture>
</a>

<!-- Made with [OSS Insight](https://ossinsight.io/) -->
END;
*/

$html .= '</section>'."\n";



// Contributions

$html .= '<section class="chapter" id="projectvalue">'."\n";
$html .= '<h2><span class="fas fa-tasks pictofixedwidth"></span>Contributions</h2>'."\n";

$html .= '<div class="boxallwidth">'."\n";

$html .= <<<END
<!-- Copy-paste in your Readme.md file -->

<a href="https://next.ossinsight.io/widgets/official/analyze-repo-pushes-and-commits-per-month?repo_id=1957456" target="_blank" style="display: block" align="center">
<picture>
<source media="(prefers-color-scheme: dark)" srcset="https://next.ossinsight.io/widgets/official/analyze-repo-pushes-and-commits-per-month/thumbnail.png?repo_id=1957456&image_size=auto&color_scheme=dark" width="721" height="auto">
<img alt="Pushes and Commits of Dolibarr/dolibarr" src="https://next.ossinsight.io/widgets/official/analyze-repo-pushes-and-commits-per-month/thumbnail.png?repo_id=1957456&image_size=auto&color_scheme=light" width="721" height="auto">
</picture>
</a>

<!-- Made with [OSS Insight](https://ossinsight.io/) -->


<!-- Copy-paste in your Readme.md file -->

<a href="https://next.ossinsight.io/widgets/official/analyze-repo-pull-requests-size-per-month?repo_id=1957456" target="_blank" style="display: block" align="center">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset="https://next.ossinsight.io/widgets/official/analyze-repo-pull-requests-size-per-month/thumbnail.png?repo_id=1957456&image_size=auto&color_scheme=dark" width="721" height="auto">
    <img alt="Pull Request Size of Dolibarr/dolibarr" src="https://next.ossinsight.io/widgets/official/analyze-repo-pull-requests-size-per-month/thumbnail.png?repo_id=1957456&image_size=auto&color_scheme=light" width="721" height="auto">
  </picture>
</a>

<!-- Made with [OSS Insight](https://ossinsight.io/) -->

END;


$html .= '<!-- ';
foreach ($output_arrglpu as $line) {
	$html .= $line."\n";
}
$html .= ' -->';

$html .= '</div>';

$html .= '</section>'."\n";


// Community - Contributors

$html .= '<section class="chapter" id="projectvalue">'."\n";
$html .= '<h2><span class="fas fa-user pictofixedwidth"></span>Contributors</h2>'."\n";

$html .= '<div class="boxallwidth">'."\n";

$html .= <<<END
<center><br>Thumbs of most active contributors<br>
<br>
<a href="https://github.com/Dolibarr/dolibarr/graphs/contributors"><img src="https://camo.githubusercontent.com/a641a400eef38e00a93b572dcfc30d13ceaaeefbca951d09ed9189142d20cf62/68747470733a2f2f6f70656e636f6c6c6563746976652e636f6d2f646f6c69626172722f636f6e7472696275746f72732e7376673f77696474683d38393026627574746f6e3d66616c7365" alt="Dolibarr" data-canonical-src="https://opencollective.com/dolibarr/contributors.svg?width=890&amp;button=false" style="max-width: 100%;"></a>
</center>
<br>
END;

/*
$html .= <<<END
<!-- Copy-paste in your Readme.md file -->

<a href="https://next.ossinsight.io/widgets/official/compose-contributors?repo_id=1957456&limit=200" target="_blank" style="display: block" align="center">
<picture>
<source media="(prefers-color-scheme: dark)" srcset="https://next.ossinsight.io/widgets/official/compose-contributors/thumbnail.png?repo_id=1957456&limit=200&image_size=auto&color_scheme=dark" width="655" height="auto">
<img alt="Contributors of Dolibarr/dolibarr" src="https://next.ossinsight.io/widgets/official/compose-contributors/thumbnail.png?repo_id=1957456&limit=200&image_size=auto&color_scheme=light" width="655" height="auto">
</picture>
</a>

<!-- Made with [OSS Insight](https://ossinsight.io/) -->
END;
*/

$html .= <<<END
<br>
<!-- Copy-paste in your Readme.md file -->

<a href="https://next.ossinsight.io/widgets/official/analyze-repo-stars-history?repo_id=1957456" target="_blank" style="display: block" align="center">
<picture>
<source media="(prefers-color-scheme: dark)" srcset="https://next.ossinsight.io/widgets/official/analyze-repo-stars-history/thumbnail.png?repo_id=1957456&image_size=auto&color_scheme=dark" width="721" height="auto">
<img alt="Star History of Dolibarr/dolibarr" src="https://next.ossinsight.io/widgets/official/analyze-repo-stars-history/thumbnail.png?repo_id=1957456&image_size=auto&color_scheme=light" width="721" height="auto">
</picture>
</a>

<!-- Made with [OSS Insight](https://ossinsight.io/) -->
END;

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
$title_security_short = "Last security issues";
$title_security = ($project ? "[".$project."] " : "").$title_security_short;

$html .= '<section class="chapter" id="linesofcode">'."\n";
$html .= '<h2><span class="fas fa-code pictofixedwidth"></span>'.$title_security_short.' <span class="opacitymedium">(last '.($nbofmonth != 1 ? $nbofmonth.' months' : 'month').')</span></h2>'."\n";

$html .= '<div class="boxallwidth">'."\n";
$html .= '<div class="div-table-responsive">'."\n";
$html .= '<table class="list_technical_debt centpercent">'."\n";
$html .= '<tr class="trgroup"><td>Commit ID</td><td>Date</td><td style="white-space: nowrap">Reported on<br>Yogosha</td><td style="white-space: nowrap">Reported on<br>GIT</td><td style="white-space: nowrap">Reported on<br>CVE</td><td>Title</td><td>Branch of fix</td></tr>'."\n";
foreach ($arrayofalerts as $key => $alert) {
	$cve = '';
	$yogosha = empty($alert['issueidyogosha']) ? '' : $alert['issueidyogosha'];
	$arrayofalerts[$key]['url_commit'] = 'https://github.com/Dolibarr/dolibarr/commit/'.$alert['commitid'];
	if (!empty($alert['issueid'])) {
		$arrayofalerts[$key]['url_issue'] = 'https://github.com/Dolibarr/dolibarr/issues/'.$alert['issueid'];
	}
	if (!empty($alert['issueidcve'])) {
		$cve = preg_replace('/\s+/', '-', trim($alert['issueidcve']));
		$arrayofalerts[$key]['url_cve'] = 'https://nvd.nist.gov/vuln/detail/CVE-'.$cve;
	}
	$arrayofalerts[$key]['title'] = ($project ? "[".$project."] " : "").'Security alert - '.($yogosha ? ' Yogosha #'.$yogosha.' - ' : '').($cve ? 'CVE-'.$cve.' - ' : '');
	$arrayofalerts[$key]['title'] .= 'Fix committed as: '.dol_trunc($alert['commitid'], 8);

	$arrayofalerts[$key]['description'] = '<![CDATA[Security alert<br>';

	$html .= '<tr style="vertical-align: top;">';

	// Commits ID - Add link to Github
	$html .= '<td class="nowrap">';
	$html .= '<a target="_blank" href="'.$arrayofalerts[$key]['url_commit'].'">'.dol_trunc($alert['commitid'], 8).'</a>';
	$arrayofalerts[$key]['description'] .= "\n<br>".'Commit ID: <a href="'.$arrayofalerts[$key]['url_commit'].'">'.dol_trunc($alert['commitid'], 8).'</a>';

	if (!empty($alert['commitidbis'])) {
		$html .= ' <div class="more inline"><span class="seeothercommit badge">+</span><div class="morediv hidden">';
		foreach ($alert['commitidbis'] as $tmpcommitidbis) {
			$html .= '<a target="_blank" href="https://github.com/Dolibarr/dolibarr/commit/'.$tmpcommitidbis.'">'.dol_trunc($tmpcommitidbis, 8).'</a><br>';
			$arrayofalerts[$key]['description'] .= "\n<br>".'Commit ID: <a href="https://github.com/Dolibarr/dolibarr/commit/'.$tmpcommitidbis.'">'.dol_trunc($tmpcommitidbis, 8).'</a>';
		}
		$html .= '</div></div>';
	}
	$html .= '</td>';

	// Date creation
	$html .= '<td style="white-space: nowrap">';
	$html .= preg_replace('/T.*$/', '', $alert['created_at']);
	$html .= '</td>';

	// Yogosha ID
	$html .= '<td style="white-space: nowrap">';
	if (!empty($alert['issueidyogosha'])) {
		//$html .= '<a target="_blank" href="https://yogosha.com?'.$alert['issueidyogosha'].'">';
		$html .= '#yogosha'.$alert['issueidyogosha'];
		$arrayofalerts[$key]['description'] .= "\n<br>".'Yogosha ID #'.$alert['issueidyogosha'];
		//$html .= '</a>';
	} else {
		//$html .= '<span class="opacitymedium">public issue</span>';
	}
	$html .= '</td>';

	// GIT Issue/PR ID
	$html .= '<td style="white-space: nowrap">';
	if (!empty($alert['issueid'])) {
		$html .= '<a target="_blank" href="'.$arrayofalerts[$key]['url_issue'].'">#'.$alert['issueid'].'</a>';
		$arrayofalerts[$key]['description'] .= "\n<br>".'GitHub ID <a href="'.$arrayofalerts[$key]['url_issue'].'" target="_blank">#'.$alert['issueid'].'</a>';
	} else {
		//$html .= '<span class="opacitymedium">private</span>';
	}
	$html .= '</td>';

	// CVE ID
	$html .= '<td style="white-space: nowrap">';
	if (!empty($alert['issueidcve'])) {
		$cve = preg_replace('/\s+/', '-', trim($alert['issueidcve']));
		$html .= '<a target="_blank" href="'.$arrayofalerts[$key]['url_cve'].'">CVE-'.$cve.'</a>';
		$arrayofalerts[$key]['description'] .= "\n<br>".'CVE: <a href="'.$arrayofalerts[$key]['url_cve'].'">CVE-'.$cve.'</a>';
	}
	$html .= '</td>';

	// Description
	$html .= '<td class="tdoverflowmax300" title="'.dol_escape_htmltag($alert['title']).'">'.dol_escape_htmltag($alert['title']).'</td>';

	// Branches
	$html .= '<td style="white-space: nowrap">';
	if (!empty($alert['branch'])) {
		$listofbranchnames = implode(', ', array_unique($alert['branch']));
		$html .= $listofbranchnames;
		$arrayofalerts[$key]['description'] .= "\n<br><br>".'Branches of fix: '.$listofbranchnames;
	}
	$html .= '</td>';

	$arrayofalerts[$key]['description'] .= ']]>';

	$html .= '</tr>';
}
$html .= '</table>';
$html .= '</div>';
$html .= '</div>';

$html .= '<br>';
$html .= 'You can use this URL for RSS notifications: <a href="/'.$outputfilerss.'">'.$outputfilerss.'</a><br><br>';

$html .= '</section>';


// Generate the RSS file
$fh = fopen($outputdir.'/'.$outputfilerss, 'w');
if ($fh) {
	if ($url_root && empty($url_site)) {
		$url_site = $url_root;
	}
	if ($url_root && empty($url_flux)) {
		$url_flux = $url_root.'/'.$outputfilerss;
	}

	// Generation of the feed
	fwrite($fh, '<?xml version="1.0" encoding="UTF-8" ?>'."\n");
	fwrite($fh, '<rss version="2.0">'."\n");
	fwrite($fh, '<channel>'."\n");
	fwrite($fh, '<title>' . htmlspecialchars($title_security) . '</title>'."\n");
	fwrite($fh, '<description>' . htmlspecialchars("Feed of the latest security reports on the project") . '</description>'."\n");
	//fwrite($fh, '<atom:link href="https://cti.dolibarr.org/index-security.rss" rel="self" type="application/rss+xml" />'."\n");
	fwrite($fh, '<language>en-US</language>'."\n");
	fwrite($fh, '<lastBuildDate>'.date('r').'</lastBuildDate>'."\n");
	/*
	<lastBuildDate>Mon, 29 Apr 2024 11:33:54 +0000</lastBuildDate>
	<atom:link href="https://cti.dolibarr.org/security-index.rss" rel="self" type="application/rss+xml" />
	*/
	if ($url_site) {
		fwrite($fh, '<link>' . htmlspecialchars($url_site) . '</link>'."\n");
	}
	// Image
	fwrite($fh, '<image>'."\n");
	fwrite($fh, '<url>https://www.dolibarr.org/medias/image/www.dolibarr.org/badge-openssf.png</url>'."\n");
	fwrite($fh, '<title>' . htmlspecialchars($title_security) . '</title>'."\n");
	if ($url_site) {
		fwrite($fh, '<link>' . htmlspecialchars($url_site) . '</link>'."\n");
	}
	fwrite($fh, '</image>'."\n");

	foreach ($arrayofalerts as $alert) {
		$alert['url_commit'] = 'https://github.com/Dolibarr/dolibarr/commit/'.$alert['commitid'];

		fwrite($fh, '<item>'."\n");
		fwrite($fh, '<title>' . htmlspecialchars($alert['title']) . '</title>'."\n");
		// Description of alert, list of links to sources and branches of fixes
		fwrite($fh, '<description>' . $alert['description'] . '</description>'."\n");	// no htmlspeciachars here
		fwrite($fh, '<link>' . htmlspecialchars($alert['url_commit']) . '</link>'."\n");
		$tmpdate = strtotime($alert['created_at']);
		fwrite($fh, '<pubDate>' . htmlspecialchars(date('r', $tmpdate)) . '</pubDate>'."\n");
		fwrite($fh, '<guid isPermaLink="false"><![CDATA['.htmlspecialchars($alert['commitid']).']]></guid>'."\n");	// A hidden unique ID

		fwrite($fh, '</item>'."\n");
	}

	fwrite($fh, '</channel>'."\n");
	fwrite($fh, '</rss>'."\n");

	fclose($fh);

	print 'Generation of RSS output file '.$outputdir.'/'.$outputfilerss.' done.'."\n";
} else {
	print 'Failed to generate the RSS file '.$outputdir.'/'.$outputfilerss."\n";
}



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
	$html .= '<h2><span class="fas fa-book-dead pictofixedwidth"></span>Technical debt <span class="opacitymedium">('.$phpstanversion.' - level '.$PHPSTANLEVEL.' -> '.$nblines.' warnings)</span></h2>'."\n";

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

	print 'Generation of output file '.$outputpath.' done.'."\n";
} else {
	print 'Failed to open '.$outputpath.' for output.'."\n";
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
