<?php
/* Copyright (C) 2003		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Sebastien Di Cintio		<sdicintio@ressource-toi.org>
 * Copyright (C) 2004		Benoit Mortier			<benoit.mortier@opensides.be>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *  \file           htdocs/admin/system/dbtable.php
 *  \brief          Page d'info des contraintes d'une table
 */

// Load Dolibarr environment
require '../../main.inc.php';

$langs->load("admin");

if (!$user->admin) {
	accessforbidden();
}

$table = GETPOST('table', 'aZ09');
$field = GETPOST('field', 'aZ09');
$action = GETPOST('action', 'aZ09');


/*
 * Actions
 */

if ($action == 'convertutf8') {
	$sql = "SHOW FULL COLUMNS IN ".$db->sanitize($table);

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		while ($i < $num) {
			$row = $db->fetch_row($resql);
			if ($row[0] == $field) {
				$sql = "ALTER TABLE ".$db->sanitize($table)." MODIFY ".$db->sanitize($row[0])." ".$row[1]." CHARACTER SET utf8";		// We must not sanitize the $row[1]
				$db->query($sql);

				$sql = "ALTER TABLE ".$db->sanitize($table)." MODIFY ".$db->sanitize($row[0])." ".$row[1]." COLLATE utf8_unicode_ci";	// We must not sanitize the $row[1]
				$db->query($sql);

				break;
			}
		}
	}
}
if ($action == 'convertutf8mb4') {
	$sql = "SHOW FULL COLUMNS IN ".$db->sanitize($table);

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		while ($i < $num) {
			$row = $db->fetch_row($resql);
			if ($row[0] == $field) {
				$sql = "ALTER TABLE ".$db->sanitize($table)." MODIFY ".$db->sanitize($row[0])." ".$row[1]." CHARACTER SET utf8mb4";		// We must not sanitize the $row[1]
				$db->query($sql);

				$sql = "ALTER TABLE ".$db->sanitize($table)." MODIFY ".$db->sanitize($row[0])." ".$row[1]." COLLATE utf8mb4_unicode_ci";	// We must not sanitize the $row[1]
				$db->query($sql);

				break;
			}
		}
	}
}


/*
 * View
 */

llxHeader('', '', '', '', 0, 0, '', '', '', 'mod-admin page-system_dbtable');


print load_fiche_titre($langs->trans("Table")." ".$table, '', 'title_setup');

// Define request to get table description
$base = 0;
$sql = null;
if (preg_match('/mysql/i', $conf->db->type)) {
	$sql = "SHOW TABLE STATUS LIKE '".$db->escape($db->escapeforlike($table))."'";
	$base = 1;
} elseif ($conf->db->type == 'pgsql') {
	$sql = "SELECT conname,contype FROM pg_constraint";
	$base = 2;
}

if (!$base || $sql === null) {
	print $langs->trans("FeatureNotAvailableWithThisDatabaseDriver");
} else {
	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		while ($i < $num) {
			$row = $db->fetch_row($resql);
			$i++;
		}
	}

	if ($base == 1) {	// mysql
		$link = array();
		$cons = explode(";", $row[14]);
		if (!empty($cons)) {
			foreach ($cons as $cc) {
				$cx = preg_replace("/\)\sREFER/", "", $cc);
				$cx = preg_replace("/\(`/", "", $cx);
				$cx = preg_replace("/`\)/", "", $cx);
				$cx = preg_replace("/`\s/", "", $cx);

				$val = explode("`", $cx);

				$link[trim($val[0])][0] = (isset($val[1]) ? $val[1] : '');
				$link[trim($val[0])][1] = (isset($val[2]) ? $val[2] : '');
			}
		}

		print '<table class="noborder">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("Fields").'</td>';
		print '<td>'.$langs->trans("Type").'</td>';
		print '<td>'.$langs->trans("Collation").'</td>';
		print '<td>'.$langs->trans("Null").'</td>';
		print '<td>'.$langs->trans("Index").'</td>';
		print '<td>'.$langs->trans("Default").'</td>';
		print '<td>'.$langs->trans("Extra").'</td>';
		print '<td>'.$langs->trans("Privileges").'</td>';
		print '<td>'.$langs->trans("FieldsLinked").'</td>';
		print '</tr>';

		// $sql = "DESCRIBE ".$table;
		$sql = "SHOW FULL COLUMNS IN ".$db->sanitize($table);

		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$row = $db->fetch_row($resql);

				print '<tr class="oddeven">';

				// field
				print "<td>".$row[0]."</td>";

				// type
				print "<td>";
				$proptype = $row[1];
				$pictoType = '';
				$matches = array();
				if (preg_match('/^varchar/', $proptype, $matches)) {
					$pictoType = 'varchar';
				} elseif (strpos($proptype, 'int') === 0 || strpos($proptype, 'tinyint') === 0 || strpos($proptype, 'bigint') === 0) {
					$pictoType = 'int';
				} elseif (strpos($proptype, 'timestamp') === 0) {
					$pictoType = 'datetime';
				} elseif (strpos($proptype, 'real') === 0) {
					$pictoType = 'double';
				}
				print(!empty($pictoType) ? getPictoForType($pictoType) : getPictoForType($proptype)).'<span title="'.dol_escape_htmltag($proptype).'">'.dol_escape_htmltag($proptype).'</span>';
				print "</td>";

				// collation
				print "<td>".(empty($row[2]) ? '&nbsp;' : $row[2]);

				// Link to convert collation
				if (isset($row[2])) {
					print '<br><span class="opacitymedium small">'.$langs->trans("ConvertInto");
					if (!in_array($row[2], array("utf8_unicode_ci"))) {
						print ' <a class="reposition" href="dbtable.php?action=convertutf8&table='.urlencode($table).'&field='.urlencode($row[0]).'&token='.newToken().'">utf8</a>';
					}
					if (!in_array($row[2], array("utf8mb4_unicode_ci"))) {
						print ' <a class="reposition" href="dbtable.php?action=convertutf8mb4&table='.urlencode($table).'&field='.urlencode($row[0]).'&token='.newToken().'">utf8mb4</a>';
					}
					print '</span>';
				} else {
					print '<br>&nbsp;';
				}

				print "</td>";

				// null
				print "<td>".$row[3]."</td>";
				// key
				print "<td>".(empty($row[4]) ? '' : $row[4])."</td>";
				// default
				print "<td>".(empty($row[5]) ? '' : $row[5])."</td>";
				// extra
				print "<td>".(empty($row[6]) ? '' : $row[6])."</td>";
				// privileges
				print "<td>".(empty($row[7]) ? '' : $row[7])."</td>";

				print "<td>".(isset($link[$row[0]][0]) ? $link[$row[0]][0] : '').".";
				print(isset($link[$row[0]][1]) ? $link[$row[0]][1] : '')."</td>";

				print '<!-- ALTER TABLE '.$table.' MODIFY '.$row[0].' '.$row[1].' COLLATE utf8_unicode_ci; -->';
				print '<!-- ALTER TABLE '.$table.' MODIFY '.$row[0].' '.$row[1].' CHARACTER SET utf8; -->';
				print '</tr>';
				$i++;
			}
		}
		print '</table>';
	}
}

// End of page
llxFooter();
$db->close();
