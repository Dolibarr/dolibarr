<?php
/* Copyright (C) 2003		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2021	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Sebastien Di Cintio		<sdicintio@ressource-toi.org>
 * Copyright (C) 2004		Benoit Mortier			<benoit.mortier@opensides.be>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@inodbox.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/admin/system/database-tables.php
 *	\brief      Page with information on database tables. Add also some maintenance action to convert tables.
 */

if (! defined('CSRFCHECK_WITH_TOKEN')) {
	define('CSRFCHECK_WITH_TOKEN', '1');		// Force use of CSRF protection with tokens even for GET
}

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

$langs->load("admin");

if (!$user->admin) {
	accessforbidden();
}

$table = GETPOST('table', 'aZ09');
$action = GETPOST('action', 'aZ09');


/*
 * Actions
 */

if ($action == 'convert') {	// Convert engine into innodb
	$sql = "ALTER TABLE ".$db->sanitize($table)." ENGINE=INNODB";
	$db->query($sql);
}
if ($action == 'convertutf8') {
	$sql = "ALTER TABLE ".$db->sanitize($table)." CHARACTER SET utf8 COLLATE utf8_unicode_ci";
	$db->query($sql);
}
if ($action == 'convertutf8mb4') {
	$sql = "ALTER TABLE ".$db->sanitize($table)." CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
	$db->query($sql);
}
if ($action == 'convertdynamic') {
	$sql = "ALTER TABLE ".$db->sanitize($table)." ROW_FORMAT=DYNAMIC;";
	$db->query($sql);
}


/*
 * View
 */

llxHeader('', '', '', '', 0, 0, '', '', '', 'mod-admin page-database_tables');

print load_fiche_titre($langs->trans("Tables")." ".ucfirst($conf->db->type), '', 'title_setup');


// Define request to get table description
$base = 0;
if (preg_match('/mysql/i', $conf->db->type)) {
	$sql = "SHOW TABLE STATUS";
	$base = 1;
} elseif ($conf->db->type == 'pgsql') {
	$sql = "SELECT conname, contype FROM pg_constraint;";
	$base = 2;
} elseif ($conf->db->type == 'mssql') {
	//$sqls[0] = "";
	//$base=3;
} elseif ($conf->db->type == 'sqlite' || $conf->db->type == 'sqlite3') {
	//$sql = "SELECT name, type FROM sqlite_master";
	$base = 4;
}


if (!$base) {
	print $langs->trans("FeatureNotAvailableWithThisDatabaseDriver");
} else {
	if ($base == 1) {
		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder">';
		print '<tr class="liste_titre">';
		print '<td>#</td>';
		print '<td>'.$langs->trans("TableName").'</td>';
		print '<td colspan="2">'.$langs->trans("Type").'</td>';
		print '<td>'.$langs->trans("Format").'</td>';
		print '<td class="right">'.$langs->trans("NbOfRecord").'</td>';
		print '<td class="right">Avg_row_length</td>';
		print '<td class="right">Data_length</td>';
		print '<td class="right">Max_Data_length</td>';
		print '<td class="right">Index_length</td>';
		print '<td class="right">Increment</td>';
		print '<td class="right">Last check</td>';
		print '<td class="right">Collation</td>';
		print "</tr>\n";

		$arrayoffilesrich = dol_dir_list(DOL_DOCUMENT_ROOT.'/install/mysql/tables/', 'files', 0, '\.sql$');
		$arrayoffiles = array();
		$arrayoftablesautocreated = array();
		foreach ($arrayoffilesrich as $value) {
			//print $shortsqlfilename.' ';
			$shortsqlfilename = preg_replace('/\-[a-z]+\./', '.', $value['name']);
			$arrayoffiles[$value['name']] = $shortsqlfilename;
			if ($value['name'] == $shortsqlfilename && ! preg_match('/\.key\.sql$/', $value['name'])) {
				// This is a sql file automatically created
				$arrayoftablesautocreated[$value['name']] = $shortsqlfilename;
			}
		}

		// Now loop on tables really found into database
		$sql = "SHOW TABLE STATUS";

		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				print '<tr class="oddeven">';

				print '<td>'.($i+1).'</td>';
				print '<td class="tdoverflowmax300" title="'.dol_escape_htmltag($obj->Name).'"><a href="dbtable.php?table='.urlencode($obj->Name).'">'.$obj->Name.'</a>';
				$tablename = preg_replace('/^'.MAIN_DB_PREFIX.'/', 'llx_', $obj->Name);

				if (in_array($tablename.'.sql', $arrayoffiles)) {
					if (in_array($tablename.'.sql', $arrayoftablesautocreated)) {
						$img = "info";
					} else {
						$img = "info_black";
						print img_picto($langs->trans("NotAvailableByDefaultEnabledOnModuleActivation"), $img, 'class="small opacitymedium"');
					}
				} else {
					$img = "info_black";
					print img_picto($langs->trans("ExternalModule"), $img, 'class="small"');
				}
				print '</td>';
				print '<td>'.$obj->Engine.'</td>';
				if (isset($obj->Engine) && $obj->Engine == "MyISAM") {
					print '<td><a class="reposition" href="database-tables.php?action=convert&table='.urlencode($obj->Name).'&token='.newToken().'">'.$langs->trans("Convert").' InnoDb</a></td>';
				} else {
					print '<td>&nbsp;</td>';
				}
				print '<td>';
				print $obj->Row_format;
				if (isset($obj->Row_format) && (in_array($obj->Row_format, array("Compact")))) {
					print '<br><a class="reposition" href="database-tables.php?action=convertdynamic&table='.urlencode($obj->Name).'&token='.newToken().'">'.$langs->trans("Convert").' Dynamic</a>';
				}
				print '</td>';
				print '<td class="right">'.$obj->Rows.'</td>';
				print '<td class="right">'.$obj->Avg_row_length.'</td>';
				print '<td class="right">'.$obj->Data_length.'</td>';
				print '<td class="right">'.$obj->Max_data_length.'</td>';
				print '<td class="right">'.$obj->Index_length.'</td>';
				print '<td class="right">'.$obj->Auto_increment.'</td>';
				print '<td class="right">'.$obj->Check_time.'</td>';
				print '<td class="right nowraponall">'.$obj->Collation;
				// Link to convert collation
				if (isset($obj->Collation)) {
					print '<br><span class="opacitymedium small">'.$langs->trans("ConvertInto");
					if (!in_array($obj->Collation, array("utf8_unicode_ci"))) {
						print ' <a class="reposition" href="database-tables.php?action=convertutf8&table='.urlencode($obj->Name).'&token='.newToken().'">utf8</a>';
					}
					if (!in_array($obj->Collation, array("utf8mb4_unicode_ci"))) {
						print ' <a class="reposition" href="database-tables.php?action=convertutf8mb4&table='.urlencode($obj->Name).'&token='.newToken().'">utf8mb4</a>';
					}
					print '</span>';
				}
				print '</td>';
				print '</tr>';
				$i++;
			}
		}
		print '</table>';
		print '</div>';
	}

	if ($base == 2) {
		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder">';
		print '<tr class="liste_titre">';

		print '<td>#</td>';
		print '<td>'.$langs->trans("TableName").'</td>';
		print '<td>Nb of tuples</td>';
		print '<td>Nb index fetcher.</td>';
		print '<td>Nb tuples insert</td>';
		print '<td>Nb tuples modify</td>';
		print '<td>Nb tuples delete</td>';
		print "</tr>\n";

		$sql = "SELECT relname, seq_tup_read, idx_tup_fetch, n_tup_ins, n_tup_upd, n_tup_del";
		$sql .= " FROM pg_stat_user_tables ORDER BY relname";

		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$row = $db->fetch_row($resql);
				print '<tr class="oddeven">';
				print '<td>'.($i+1).'</td>';
				print '<td>'.$row[0].'</td>';
				print '<td class="right">'.$row[1].'</td>';
				print '<td class="right">'.$row[2].'</td>';
				print '<td class="right">'.$row[3].'</td>';
				print '<td class="right">'.$row[4].'</td>';
				print '<td class="right">'.$row[5].'</td>';
				print '</tr>';
				$i++;
			}
		}
		print '</table>';
		print '</div>';
	}

	if ($base == 4) {
		// Sqlite by PDO or by Sqlite3
		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder">';
		print '<tr class="liste_titre">';
		print '<td>#</td>';
		print '<td>'.$langs->trans("TableName").'</td>';
		print '<td>'.$langs->trans("NbOfRecord").'</td>';
		print "</tr>\n";

		$sql = "SELECT name, type FROM sqlite_master where type='table' and name not like 'sqlite%' ORDER BY name";
		$resql = $db->query($sql);

		if ($resql) {
			while ($row = $db->fetch_row($resql)) {
				$rescount = $db->query("SELECT COUNT(*) FROM ".$row[0]);
				if ($rescount) {
					$row_count = $db->fetch_row($rescount);
					$count = $row_count[0];
				} else {
					$count = '?';
				}

				print '<tr class="oddeven">';
				print '<td>'.($i+1).'</td>';
				print '<td>'.$row[0].'</td>';
				print '<td>'.$count.'</td>';
				print '</tr>';
			}
		}

		print '</table>';
		print '</div>';
	}
}

// End of page
llxFooter();
$db->close();
