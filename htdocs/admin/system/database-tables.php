<?php
/* Copyright (C) 2003		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Sebastien Di Cintio		<sdicintio@ressource-toi.org>
 * Copyright (C) 2004		Benoit Mortier			<benoit.mortier@opensides.be>
<<<<<<< HEAD
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
=======
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@inodbox.com>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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
 *	\file       htdocs/admin/system/database-tables.php
<<<<<<< HEAD
 *	\brief      Page d'infos des tables de la base
=======
 *	\brief      Page with information on database tables
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 */

require '../../main.inc.php';

$langs->load("admin");

if (! $user->admin)
	accessforbidden();

<<<<<<< HEAD
$action=GETPOST('action','alpha');
=======
$action=GETPOST('action', 'alpha');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9


if ($action == 'convert')
{
<<<<<<< HEAD
	$db->query("alter table ".$_GET["table"]." ENGINE=INNODB");
=======
    $sql="ALTER TABLE ".$db->escape(GETPOST("table", "aZ09"))." ENGINE=INNODB";
	$db->query($sql);
}
if ($action == 'convertutf8')
{
    $sql="ALTER TABLE ".$db->escape(GETPOST("table", "aZ09"))." CHARACTER SET utf8 COLLATE utf8_unicode_ci";
    $db->query($sql);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
}


/*
 * View
 */

llxHeader();

<<<<<<< HEAD
print load_fiche_titre($langs->trans("Tables")." ".ucfirst($conf->db->type),'','title_setup');
=======
print load_fiche_titre($langs->trans("Tables")." ".ucfirst($conf->db->type), '', 'title_setup');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9


// Define request to get table description
$base=0;
<<<<<<< HEAD
if (preg_match('/mysql/i',$conf->db->type))
=======
if (preg_match('/mysql/i', $conf->db->type))
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
{
	$sql = "SHOW TABLE STATUS";
	$base=1;
}
<<<<<<< HEAD
else if ($conf->db->type == 'pgsql')
=======
elseif ($conf->db->type == 'pgsql')
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
{
	$sql = "SELECT conname, contype FROM pg_constraint;";
	$base=2;
}
<<<<<<< HEAD
else if ($conf->db->type == 'mssql')
=======
elseif ($conf->db->type == 'mssql')
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
{
	//$sqls[0] = "";
	//$base=3;
}
<<<<<<< HEAD
else if ($conf->db->type == 'sqlite' || $conf->db->type == 'sqlite3')
=======
elseif ($conf->db->type == 'sqlite' || $conf->db->type == 'sqlite3')
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
{
	//$sql = "SELECT name, type FROM sqlite_master";
	$base = 4;
}


if (! $base)
{
	print $langs->trans("FeatureNotAvailableWithThisDatabaseDriver");
}
else
{
	if ($base == 1)
	{
        print '<div class="div-table-responsive-no-min">';
	    print '<table class="noborder">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("TableName").'</td>';
		print '<td colspan="2">'.$langs->trans("Type").'</td>';
		print '<td>'.$langs->trans("Format").'</td>';
<<<<<<< HEAD
		print '<td align="right">'.$langs->trans("NbOfRecord").'</td>';
		print '<td align="right">Avg_row_length</td>';
		print '<td align="right">Data_length</td>';
		print '<td align="right">Max_Data_length</td>';
		print '<td align="right">Index_length</td>';
		print '<td align="right">Increment</td>';
		print '<td align="right">Last check</td>';
		print '<td align="right">Collation</td>';
=======
		print '<td class="right">'.$langs->trans("NbOfRecord").'</td>';
		print '<td class="right">Avg_row_length</td>';
		print '<td class="right">Data_length</td>';
		print '<td class="right">Max_Data_length</td>';
		print '<td class="right">Index_length</td>';
		print '<td class="right">Increment</td>';
		print '<td class="right">Last check</td>';
		print '<td class="right">Collation</td>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		print "</tr>\n";

		$sql = "SHOW TABLE STATUS";

		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i=0;
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);
				print '<tr class="oddeven">';

				print '<td><a href="dbtable.php?table='.$obj->Name.'">'.$obj->Name.'</a></td>';
				print '<td>'.$obj->Engine.'</td>';
<<<<<<< HEAD
				if (isset($row[1]) && $row[1] == "MyISAM")
				{
					print '<td><a href="database-tables.php?action=convert&amp;table='.$row[0].'">'.$langs->trans("Convert").'</a></td>';
=======
				if (isset($obj->Engine) && $obj->Engine == "MyISAM")
				{
				    print '<td><a class="reposition" href="database-tables.php?action=convert&amp;table='.$obj->Name.'">'.$langs->trans("Convert").' InnoDB</a></td>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
				}
				else
				{
					print '<td>&nbsp;</td>';
				}
				print '<td>'.$obj->Row_format.'</td>';
				print '<td align="right">'.$obj->Rows.'</td>';
				print '<td align="right">'.$obj->Avg_row_length.'</td>';
				print '<td align="right">'.$obj->Data_length.'</td>';
				print '<td align="right">'.$obj->Max_data_length.'</td>';
				print '<td align="right">'.$obj->Index_length.'</td>';
				print '<td align="right">'.$obj->Auto_increment.'</td>';
				print '<td align="right">'.$obj->Check_time.'</td>';
<<<<<<< HEAD
				print '<td align="right">'.$obj->Collation.'</td>';
=======
				print '<td align="right">'.$obj->Collation;
				if (isset($obj->Collation) && (in_array($obj->Collation, array("utf8mb4_general_ci", "utf8mb4_unicode_ci", "latin1_swedish_ci"))))
				{
				    print '<br><a class="reposition" href="database-tables.php?action=convertutf8&amp;table='.$obj->Name.'">'.$langs->trans("Convert").' UTF8</a>';
				}
				print '</td>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
				print '</tr>';
				$i++;
			}
		}
		print '</table>';
		print '</div>';
	}

	if ($base == 2)
	{
        print '<div class="div-table-responsive-no-min">';
	    print '<table class="noborder">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("TableName").'</td>';
		print '<td>Nb of tuples</td>';
		print '<td>Nb index fetcher.</td>';
		print '<td>Nb tuples insert</td>';
		print '<td>Nb tuples modify</td>';
		print '<td>Nb tuples delete</td>';
		print "</tr>\n";

		$sql = "SELECT relname, seq_tup_read, idx_tup_fetch, n_tup_ins, n_tup_upd, n_tup_del";
		$sql.= " FROM pg_stat_user_tables";

		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i=0;
			while ($i < $num)
			{
				$row = $db->fetch_row($resql);
				print '<tr class="oddeven">';
				print '<td>'.$row[0].'</td>';
<<<<<<< HEAD
				print '<td align="right">'.$row[1].'</td>';
				print '<td align="right">'.$row[2].'</td>';
				print '<td align="right">'.$row[3].'</td>';
				print '<td align="right">'.$row[4].'</td>';
				print '<td align="right">'.$row[5].'</td>';
=======
				print '<td class="right">'.$row[1].'</td>';
				print '<td class="right">'.$row[2].'</td>';
				print '<td class="right">'.$row[3].'</td>';
				print '<td class="right">'.$row[4].'</td>';
				print '<td class="right">'.$row[5].'</td>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
				print '</tr>';
				$i++;
			}
		}
		print '</table>';
		print '</div>';
	}

	if ($base == 4)
	{
		// Sqlite by PDO or by Sqlite3
    print '<div class="div-table-responsive-no-min">';
	  print '<table class="noborder">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("TableName").'</td>';
		print '<td>'.$langs->trans("NbOfRecord").'</td>';
		print "</tr>\n";

		$sql = "SELECT name, type FROM sqlite_master where type='table' and name not like 'sqlite%' ORDER BY name";
		$resql = $db->query($sql);

		if ($resql)
		{
			while ($row = $db->fetch_row($resql)) {

				$rescount = $db->query("SELECT COUNT(*) FROM " . $row[0]);
				if ($rescount) {
					$row_count = $db->fetch_row($rescount);
					$count = $row_count[0];
				} else {
					$count = '?';
				}

				print '<tr class="oddeven">';
				print '<td>'.$row[0].'</td>';
				print '<td>'.$count.'</td>';
				print '</tr>';
			}
		}

		print '</table>';
		print '</div>';
	}
}

<<<<<<< HEAD
=======
// End of page
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
llxFooter();
$db->close();
