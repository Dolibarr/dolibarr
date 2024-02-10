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
 * \file    dev/tools/secalert.php
 * \brief   Script to show alert when a new security bug has been fixed.
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
define('PRODUCT', "secalert");
define('VERSION', "1.0");

$phpstanlevel = 3;


print '***** '.constant('PRODUCT').' - '.constant('VERSION').' *****'."\n";
if (empty($argv[1])) {
	print 'You must run this tool being into the root of the project.'."\n";
	print 'Usage:   '.constant('PRODUCT').'.php  pathto/outputfile.html'."\n";
	print 'Example: '.constant('PRODUCT').'.php  documents/apstats/index.html';
	exit(0);
}

$outputpath = $argv[1];
$outputdir = dirname($outputpath);
$outputfile = basename($outputpath);

if (!is_dir($outputdir)) {
	print 'Error: dir '.$outputdir.' does not exists or is not writable'."\n";
	exit(1);
}

$i = 0;
while ($i < $argc) {
	$reg = array();
	if (preg_match('/--max=(.*)$/', $argv[$i], $reg)) {
		$dirscc = $reg[1];
	}
	/*
	if (preg_match('/--dir-phpstan=(.*)$/', $argv[$i], $reg)) {
		$dirphpstan = $reg[1];
	}
	*/
	$i++;
}


// Include Dolibarr environment
require_once $path.'../../htdocs/master.inc.php';
require_once $path.'../../htdocs/core/lib/files.lib.php';
require_once $path.'../../htdocs/core/lib/geturl.lib.php';

// After this $db is an opened handler to database. We close it at end of file.

// Load main language strings
$langs->load("main");


/*
 * Main
 */

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

// Retrieve the .git information
$urlgit = 'https://api.github.com/search/issues?q=is:pr+repo:Dolibarr/dolibarr+created:>'.dol_print_date(dol_now() - (3*3600*24*30), "%Y-%m");


$arrayofalerts = array();
$arrayofalerts1 = $arrayofalerts2 = $arrayofalerts3 = array();

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
	border-bottom: 1px solid #aaa;
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
$html .= '<h1>Sec Alert</h1>'."\n";
$currentDate = date("Y-m-d H:i:s"); // Format: Year-Month-Day Hour:Minute:Second
$html .= '<span class="opacitymedium">Generated on '.$currentDate.' in '.($timeend - $timestart).' seconds</span>'."\n";
$html .= '</header>'."\n";


// Lines of code

$html .= '<section class="chapter" id="linesofcode">'."\n";
$html .= '<h2><span class="fas fa-code pictofixedwidth"></span>Last security alerts</h2>'."\n";

foreach ($arrayofalerts as $alert) {
	$html .= '<h3>'.$alert['number'].' - ';
	$html .= $alert['title'].'</h3><br>';
	$html .= $alert['created_at'].'<br>';
	$html .= '<br>'."\n";
}

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
