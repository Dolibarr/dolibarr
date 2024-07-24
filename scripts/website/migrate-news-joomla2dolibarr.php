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
 * \file scripts/website/migrate_news_joomla2dolibarr.php
 * \ingroup scripts
 * \brief Migrate news from a Joomla databse into a Dolibarr website
 */

if (!defined('NOSESSION')) {
	define('NOSESSION', '1');
}

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
$joomlaserverinfo = empty($argv[3]) ? '' : $argv[3];
$image = 'image/__WEBSITE_KEY__/images/stories/dolibarr.png';

$max = (!isset($argv[4]) || (empty($argv[4]) && $argv[4] !== '0')) ? '10' : $argv[4];
$excludeid = (empty($argv[5]) ? '' : $argv[5]);
$forcelang = (empty($argv[6]) ? '' : $argv[6]);

if (empty($argv[3]) || !in_array($argv[1], array('test', 'confirm')) || empty($websiteref)) {
	print '***** '.$script_file.' *****'."\n";
	print "Usage: $script_file (test|confirm) website login:pass@serverjoomla/tableprefix/databasejoomla [nbmaxrecord]\n";
	print "\n";
	print "Load joomla news and create them into Dolibarr database (if they don't alreay exist).\n";
	exit(-1);
}

require $path."../../htdocs/master.inc.php";
include_once DOL_DOCUMENT_ROOT.'/website/class/website.class.php';
include_once DOL_DOCUMENT_ROOT.'/website/class/websitepage.class.php';
include_once DOL_DOCUMENT_ROOT.'/core/lib/website2.lib.php';


/*
 * Main
 */

$langs->load('main');

if (!empty($dolibarr_main_db_readonly)) {
	print "Error: instance in read-onyl mode\n";
	exit(-1);
}

$joomlaserverinfoarray = preg_split('/(:|@|\/)/', $joomlaserverinfo);
$joomlalogin = $joomlaserverinfoarray[0];
$joomlapass = $joomlaserverinfoarray[1];
$joomlahost = $joomlaserverinfoarray[2];
$joomlaprefix = $joomlaserverinfoarray[3];
$joomladatabase = $joomlaserverinfoarray[4];
$joomlaport = 3306;


$website = new Website($db);
$result = $website->fetch(0, $websiteref);
if ($result <= 0) {
	print 'Error, web site '.$websiteref.' not found'."\n";
	exit(-1);
}
$websiteid = $website->id;
$importid = dol_print_date(dol_now(), 'dayhourlog');

$dbjoomla = getDoliDBInstance('mysqli', $joomlahost, $joomlalogin, $joomlapass, $joomladatabase, $joomlaport);
if ($dbjoomla->error) {
	dol_print_error($dbjoomla, "host=".$joomlahost.", port=".$joomlaport.", user=".$joomlalogin.", databasename=".$joomladatabase.", ".$dbjoomla->error);
	exit(-1);
}

$sql = 'SELECT c.id, c.title, c.alias, c.created, c.introtext, `fulltext`, c.metadesc, c.metakey, c.language, c.created, c.publish_up, u.username FROM '.$joomlaprefix.'_content as c';
$sql .= ' LEFT JOIN '.$joomlaprefix.'_users as u ON u.id = c.created_by';
$sql .= ' WHERE featured = 1';
$sql .= ' AND c.id NOT IN ('.$excludeid.')';
$sql .= ' ORDER BY publish_up ASC';
$resql = $dbjoomla->query($sql);

if (!$resql) {
	dol_print_error($dbjoomla);
	exit;
}

$blogpostheader = file_get_contents($path.'blogpost-header.txt');
if ($blogpostheader === false) {
	print "Error: Failed to load file content of 'blogpost-header.txt'\n";
	exit(-1);
}
$blogpostfooter = file_get_contents($path.'blogpost-footer.txt');
if ($blogpostfooter === false) {
	print "Error: Failed to load file content of 'blogpost-footer.txt'\n";
	exit(-1);
}



$db->begin();

$i = 0;
$nbimported = 0;
$nbalreadyexists = 0;
while ($obj = $dbjoomla->fetch_object($resql)) {
	if ($obj) {
		$i++;
		$id = $obj->id;
		$alias = $obj->alias;
		$title = $obj->title;
		//$description = dol_string_nohtmltag($obj->introtext);
		$description = trim(dol_trunc(dol_string_nohtmltag($obj->metadesc), 250));
		if (empty($description)) {
			$description = trim(dol_trunc(dol_string_nohtmltag($obj->introtext), 250));
		}

		$htmltext = "";
		if ($blogpostheader) {
			$htmltext .= $blogpostheader."\n";
		}
		$htmltext .= '<section id="mysectionnewsintro" contenteditable="true">'."\n";
		$htmltext .= $obj->introtext;
		$htmltext .= '</section>'."\n";
		if ($obj->fulltext) {
			$htmltext .= '<section id="mysectionnewscontent" contenteditable="true">'."\n";
			$htmltext .= '<br>'."\n".'<hr>'."\n".'<br>'."\n";
			$htmltext .= $obj->fulltext;
			$htmltext .= "</section>";
		}
		if ($blogpostfooter) {
			$htmltext .= "\n".$blogpostfooter;
		}

		$language = ($forcelang ? $forcelang : ($obj->language && $obj->language != '*' ? $obj->language : 'en'));
		$keywords = $obj->metakey;
		$author_alias = $obj->username;

		$date_creation = $dbjoomla->jdate($obj->publish_up);

		print '#'.$i.' id='.$id.' '.$title.' lang='.$language.' keywords='.$keywords.' importid='.$importid."\n";

		$sqlinsert = 'INSERT INTO '.MAIN_DB_PREFIX.'website_page(fk_website, pageurl, aliasalt, title, description, keywords, content, status, type_container, lang, import_key, image, date_creation, author_alias)';
		$sqlinsert .= " VALUES(".$websiteid.", '".$db->escape($alias)."', '', '".$db->escape($title)."', '".$db->escape($description)."', '".$db->escape($keywords)."', ";
		$sqlinsert .= " '".$db->escape($htmltext)."', '1', 'blogpost', '".$db->escape($language)."', '".$db->escape($importid)."', '".$db->escape($image)."', '".$db->idate($date_creation)."', '".$db->escape($author_alias)."')";
		print $sqlinsert."\n";

		$result = $db->query($sqlinsert);
		if ($result <= 0) {
			print 'ERROR: '.$db->lasterrno.": ".$sqlinsert."\n";
			if ($db->lasterrno != 'DB_ERROR_RECORD_ALREADY_EXISTS') {
				$error++;
			} else {
				$nbalreadyexists++;
			}
		} else {
			$pageid = $db->last_insert_id(MAIN_DB_PREFIX.'website_page');

			if ($pageid > 0) {	// We must also regenerate page on disk
				global $dolibarr_main_data_root;
				$pathofwebsite = $dolibarr_main_data_root.'/website/'.$websiteref;
				$filetpl = $pathofwebsite.'/page'.$pageid.'.tpl.php';
				$websitepage = new WebsitePage($db);
				$websitepage->fetch($pageid);
				dolSavePageContent($filetpl, $website, $websitepage);
			}

			print "Insert done - pageid = ".$pageid."\n";
			$nbimported++;
		}

		if ($max && $i >= $max) {
			print 'Nb max of record ('.$max.') reached. We stop now.'."\n";
			break;
		}
	}
}

if ($mode == 'confirm' && !$error) {
	print "Commit\n";
	print $nbalreadyexists." page(s) already exists.\n";
	print $nbimported." page(s) imported with importid=".$importid."\n";
	$db->commit();
} else {
	print "Rollback (mode=test)\n";
	$db->rollback();
}

exit($error);
