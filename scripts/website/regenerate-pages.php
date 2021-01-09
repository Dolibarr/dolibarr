#!/usr/bin/env php
<?php
/* Copyright (C) 2020 Laurent Destailleur <eldy@users.sourceforge.net>
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
 * \file scripts/website/regenerate_pages.php
 * \ingroup scripts
 * \brief Regenerate all pages of a web site
 */

if (!defined('NOSESSION')) define('NOSESSION', '1');

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path = __DIR__.'/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
	echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
	exit(-1);
}

@set_time_limit(0); // No timeout for this script
define('EVEN_IF_ONLY_LOGIN_ALLOWED', 1); // Set this define to 0 if you want to lock your script when dolibarr setup is "locked to admin user only".

$error = 0;

$mode = empty($argv[1]) ? '' : $argv[1];
$websiteref = empty($argv[2]) ? '' : $argv[2];
$max = (!isset($argv[3]) || (empty($argv[3]) && $argv[3] !== '0')) ? '10' : $argv[3];

if (empty($argv[2]) || !in_array($argv[1], array('test', 'confirm')) || empty($websiteref)) {
	print '***** '.$script_file.' *****'."\n";
	print "Usage: $script_file (test|confirm) website [nbmaxrecord]\n";
	print "\n";
	print "Regenerate all pages of a web site.\n";
	exit(-1);
}

require $path."../../htdocs/master.inc.php";
include_once DOL_DOCUMENT_ROOT.'/website/class/website.class.php';
include_once DOL_DOCUMENT_ROOT.'/website/class/websitepage.class.php';
include_once DOL_DOCUMENT_ROOT.'/core/lib/website2.lib.php';

$langs->load('main');

$website = new Website($db);
$result = $website->fetch(0, $websiteref);
if ($result <= 0) {
	print 'Error, web site '.$websiteref.' not found'."\n";
	exit(-1);
}

$websitepagestatic = new WebsitePage($db);

$db->begin();

$listofpages = $websitepagestatic->fetchAll($website->id, '', '', $max);

global $dolibarr_main_data_root;
$pathofwebsite = $dolibarr_main_data_root.'/website/'.$websiteref;

$nbgenerated = 0;
foreach ($listofpages as $websitepage) {
	$filealias = $pathofwebsite.'/'.$websitepage->pageurl.'.php';
	$filetpl = $pathofwebsite.'/page'.$websitepage->id.'.tpl.php';
	if ($mode == 'confirm') {
		dolSavePageAlias($filealias, $website, $websitepage);
		dolSavePageContent($filetpl, $website, $websitepage);
	}
	print "Generation of page done - pageid = ".$websitepage->id." - ".$websitepage->pageurl."\n";
	$nbgenerated++;

	if ($max && $nbgenerated >= $max) {
		print 'Nb max of record ('.$max.') reached. We stop now.'."\n";
		break;
	}
}

if ($mode == 'confirm') {
	print $nbgenerated." page(s) generated into ".$pathofwebsite."\n";
} else {
	print $nbgenerated." page(s) found but not generated (test mode)\n";
}

exit($error);
