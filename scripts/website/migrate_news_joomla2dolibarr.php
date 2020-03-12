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
 * \file scripts/website/migrate_newsèjoomla2dolibarr.php
 * \ingroup scripts
 * \brief Migrate news from a Joomla databse into a Dolibarr website
 */

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

$mode = $argv[1];
$websiteref = $argv[2];
$joomlaserverinfo = $argv[3];
$image = 'image/__WEBSITE_KEY__/images/stories/dolibarr.png';

$max = 1;

if (empty($argv[3]) || !in_array($argv[1], array('test', 'confirm')) || empty($websiteref)) {
	print "Usage: $script_file (test|confirm) website login:pass@serverjoomla/tableprefix/databasejoomla\n";
	print "\n";
	print "Load joomla news and create them into Dolibarr database (if they don't alreay exist).\n";
	exit(-1);
}

require $path."../../htdocs/master.inc.php";
include_once DOL_DOCUMENT_ROOT.'/website/class/website.class.php';

$langs->load('main');

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

$dbjoomla=getDoliDBInstance('mysqli', $joomlahost, $joomlalogin, $joomlapass, $joomladatabase, $joomlaport);
if ($dbjoomla->error)
{
	dol_print_error($dbjoomla, "host=".$joomlahost.", port=".$joomlaport.", user=".$joomlalogin.", databasename=".$joomladatabase.", ".$dbjoomla->error);
	exit(-1);
}

$sql = 'SELECT c.id, c.title, c.alias, c.created, c.introtext, `fulltext`, c.metadesc, c.metakey, c.language, c.created, c.publish_up, u.username FROM '.$joomlaprefix.'_content as c';
$sql.= ' LEFT JOIN '.$joomlaprefix.'_users as u ON u.id = c.created_by';
$sql.= ' WHERE featured = 1';
$sql.= ' ORDER BY publish_up ASC';
$resql = $dbjoomla->query($sql);

if (! $resql) {
	dol_print_error($dbjoomla);
	exit;
}

$db->begin();

while ($obj = $dbjoomla->fetch_object($resql)) {
	$i = 0;
	if ($obj) {
		$i++;
		$id = $obj->id;
		$alias = $obj->alias;
		$title = $obj->title;
		//$description = dol_string_nohtmltag($obj->introtext);
		$description = trim(dol_trunc(dol_string_nohtmltag($obj->metadesc), 250));
		if (empty($description)) $description = trim(dol_trunc(dol_string_nohtmltag($obj->introtext), 250));
		$hmtltext = $obj->introtext.'<br>'."\n".'<hr>'."\n".'<br>'."\n".$obj->fulltext;
		$language = ($obj->language && $obj->language != '*' ? $obj->language : 'en');
		$keywords = $obj->metakey;
		$author_alias = $obj->username;

		$date_creation = $dbjoomla->jdate($obj->publish_up);

		print $i.' '.$id.' '.$title.' '.$language.' '.$keywords.' '.$importid."\n";

		$sqlinsert = 'INSERT INTO '.MAIN_DB_PREFIX.'website_page(fk_website, pageurl, aliasalt, title, description, keywords, content, status, type_container, lang, import_key, image, date_creation, author_alias)';
		$sqlinsert .= " VALUES(".$websiteid.", '".$db->escape($alias)."', '', '".$db->escape($title)."', '".$db->escape($description)."', '".$db->escape($keywords)."', ";
		$sqlinsert .= " '".$db->escape($hmtltext)."', '1', 'blogpost', '".$db->escape($language)."', '".$db->escape($importid)."', '".$db->escape($image)."', '".$db->idate($date_creation)."', '".$db->escape($author_alias)."')";
		print $sqlinsert."\n";

		$result = $db->query($sqlinsert);
		if ($result <= 0) {
			$error++;
			print 'Error, '.$db->lasterror.": ".$sqlinsert."\n";
			break;
		}

		if ($max && $i <= $max) {
			print 'Nb max of record reached. We stop now.'."\n";
			break;
		}
	}
}

if ($mode == 'confirm' && ! $error) {
	$db->commit();
} else {
	$db->rollback();
}

exit($error);
