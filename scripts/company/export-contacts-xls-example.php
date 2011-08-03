#!/usr/bin/php
<?php
/**
 * Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**
 *      \file       scripts/company/export-contacts-xls-example.php
 *      \ingroup    company
 *      \brief      Export third parties' contacts with emails
 *		\version	$Id: export-contacts-xls-example.php,v 1.8 2011/07/31 22:22:12 eldy Exp $
 */

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path=dirname(__FILE__).'/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
	echo "Error: You ar usingr PH for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
	exit;
}

if (! isset($argv[1]) || ! $argv[1]) {
	print "Usage: $script_file now\n";
	exit;
}
$now=$argv[1];

// Recupere env dolibarr
$version='$Revision: 1.8 $';

require_once("../../htdocs/master.inc.php");
require_once(PHP_WRITEEXCEL_PATH."/class.writeexcel_workbook.inc.php");
require_once(PHP_WRITEEXCEL_PATH."/class.writeexcel_worksheet.inc.php");

$error = 0;


$fname = DOL_DATA_ROOT.'/export-contacts.xls';

$workbook = new writeexcel_workbook($fname);

$page = &$workbook->addworksheet('Export Dolibarr');

$page->set_column(0,4,18); // A

$sql = "SELECT distinct c.email, c.name, c.firstname, s.nom ";
$sql .= " FROM ".MAIN_DB_PREFIX."socpeople as c";
$sql .= ", ".MAIN_DB_PREFIX."societe as s";
$sql .= " WHERE s.rowid = c.fk_soc";
$sql .= " AND c.email IS NOT NULL";
$sql .= " ORDER BY c.email ASC";

$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);

	print "Lines ".$num."\n";

	$i = 0;
	$j = 1;

	$page->write_string(0, 0,  $langs->trans("ThirdParty"));
	$page->write_string(0, 1,  $langs->trans("Firstname"));
	$page->write_string(0, 2,  $langs->trans("Lastname"));
	$page->write_string(0, 3,  $langs->trans("Email"));

	$oldemail = "";

	while ($i < $num)
	{
		$obj = $db->fetch_object($resql);

		if ($obj->email <> $oldemail)
		{
			$page->write_string($j, 0,  $obj->nom);
			$page->write_string($j, 1,  $obj->firstname);
			$page->write_string($j, 2,  $obj->name);
			$page->write_string($j, 3,  $obj->email);
			$j++;

			$oldemail = $obj->email;
		}

		$i++;

	}

	print 'File '.$fname.' was generated.'."\n";
}

$workbook->close();
?>
