<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *   \file       htdocs/admin/system/database.php
 *   \brief      Page des infos système de la base de donnée
 *   \version    $Id$
 */

require("./pre.inc.php");
include_once $dolibarr_main_document_root."/lib/databases/".$conf->db->type.".lib.php";

$langs->load("admin");

if (!$user->admin)
accessforbidden();



/*
 * View
 */

$html=new Form($db);

llxHeader();

print_fiche_titre($langs->trans("DatabaseConfiguration"),'','setup');

// Database
print '<table class="noborder" width="100%">';
print "<tr class=\"liste_titre\"><td colspan=\"2\">".$langs->trans("Database")."</td></tr>\n";
print "<tr $bc[0]><td width=\"280\">".$langs->trans("Version")."</td><td>" . $db->getVersion() . "</td></tr>\n";
print "<tr $bc[1]><td>".$langs->trans("DatabaseServer")."</td><td>" . $conf->db->host . "</td></tr>\n";
print "<tr $bc[0]><td>".$langs->trans("DatabasePort")."</td><td>" . $conf->db->port . "</td></tr>\n";
print "<tr $bc[1]><td>".$langs->trans("DatabaseName")."</td><td>" . $conf->db->name . "</td></tr>\n";
print "<tr $bc[0]><td>".$langs->trans("DriverType")."</td><td>" . $conf->db->type . "</td></tr>\n";
print "<tr $bc[1]><td>".$langs->trans("User")."</td><td>" . $conf->db->user . "</td></tr>\n";
print "<tr $bc[0]><td>".$langs->trans("Password")."</td><td>" . eregi_replace('.','*',$dolibarr_main_db_pass) . "</td></tr>\n";
print "<tr $bc[1]><td>".$langs->trans("DBStoringCharset")."</td><td>" . $db->getDefaultCharacterSetDatabase() . "</td></tr>\n";
print "<tr $bc[0]><td>".$langs->trans("DBSortingCharset")."</td><td>" . $db->getDefaultCollationDatabase() . "</td></tr>\n";
print '</table>';


$base=0;
$sqls = array();
if ($conf->db->type == 'mysql' || $conf->db->type == 'mysqli')
{
	$sqls[0] = "SHOW VARIABLES";
	$sqls[1] = "SHOW STATUS";
	$base=1;
}
else if ($conf->db->type == 'pgsql')
{
	$sqls[0] = "select name,setting from pg_settings;";
	$base=2;
}
else if ($conf->db->type == 'mssql')
{
	//$sqls[0] = "";
	//$base=3;
}

if (! $base)
{
	print $langs->trans("FeatureNotAvailableWithThisDatabaseDriver");
}
else
{
	foreach($sqls as $sql)
	{
		print '<br>';
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("Parameter").'</td>';
		print '<td>'.$langs->trans("Value").'</td>';
		print "</tr>\n";

		// arraytest is an array of test to do
		$arraytest=array();
		if (eregi('mysql',$db->type))
		{
			$arraytest=array(
//				"character_set_connection"=>'UTF-8',
				'character_set_database'=>'dolibarr_main_db_character_set',
//				'collation_connection'=>"UTF-8",
				'collation_database'=>'dolibarr_main_db_collation'
			);
		}

		$resql = $db->query($sql);
		if ($resql)
		{
			$var=True;
			while ($row = $db->fetch_row($resql))
			{
				$var=!$var;
				print "<tr $bc[$var]>";
				print '<td>';
				print $row[0];
				print '</td>';
				print '<td>';
				$show=0;$text='';
				foreach($arraytest as $key => $val)
				{
					if ($key != $row[0]) continue;
					$text='Should be in line with value of param <b>'.$val.'</b> thas is <b>'.${$val}.'</b>';
					$show=1;
				}
				if ($show==0) print $row[1];
				if ($show==1) print $html->textwithpicto($row[1],$text);
				if ($show==2) print $html->textwithpicto($row[1],$text,1,'warning');
				print '</td>';
				print "</tr>\n";
			}
			$db->free($resql);
		}
		print "</table>\n";
	}
}

llxFooter('$Date$ - $Revision$');
?>
