<?php
/* Copyright (C) 2003		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005	Laurent Destailleur		<eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  \file           htdocs/admin/system/dbtable.php
 *  \brief          Page d'info des contraintes d'une table
 */

require '../../main.inc.php';

$langs->load("admin");

if (! $user->admin)
	accessforbidden();

$table=GETPOST('table','alpha');


/*
 * View
 */

llxHeader();


print load_fiche_titre($langs->trans("Table") . " ".$table,'','title_setup');

// Define request to get table description
$base=0;
if (preg_match('/mysql/i',$conf->db->type))
{
	$sql = "SHOW TABLE STATUS LIKE '".$db->escape($table)."'";
	$base=1;
}
else if ($conf->db->type == 'pgsql')
{
	$sql = "SELECT conname,contype FROM pg_constraint";
	$base=2;
}

if (! $base)
{
	print $langs->trans("FeatureNotAvailableWithThisDatabaseDriver");
}
else
{
	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i=0;
		while ($i < $num)
		{
			$row = $db->fetch_row($resql);
			$i++;
		}
	}

	if ($base == 1)	// mysql
	{
		$link=array();
		$cons = explode(";", $row[14]);
		if (! empty($cons))
		{
			foreach($cons as $cc)
			{
				$cx = preg_replace("/\)\sREFER/", "", $cc);
				$cx = preg_replace("/\(`/", "", $cx);
				$cx = preg_replace("/`\)/", "", $cx);
				$cx = preg_replace("/`\s/", "", $cx);

				$val = explode("`",$cx);

				$link[trim($val[0])][0] = (isset($val[1])?$val[1]:'');
				$link[trim($val[0])][1] = (isset($val[2])?$val[2]:'');
			}
		}

		//  var_dump($link);

		print '<table class="noborder">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("Fields").'</td><td>'.$langs->trans("Type").'</td><td>'.$langs->trans("Index").'</td>';
		print '<td></td>';
		print '<td></td>';
		print '<td></td>';
		print '<td></td>';
		print '<td>'.$langs->trans("FieldsLinked").'</td>';
		print '</tr>';

		//$sql = "DESCRIBE ".$table;
		$sql = "SHOW FULL COLUMNS IN ".$db->escape($table);

		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i=0;
			while ($i < $num)
			{
				$row = $db->fetch_row($resql);
				print '<tr class="oddeven">';
				print "<td>".$row[0]."</td>";
				print "<td>".$row[1]."</td>";
				print "<td>".$row[3]."</td>";
				print "<td>".(empty($row[4])?'':$row[4])."</td>";
				print "<td>".(empty($row[5])?'':$row[5])."</td>";
				print "<td>".(empty($row[6])?'':$row[6])."</td>";
				print "<td>".(empty($row[7])?'':$row[7])."</td>";

				print "<td>".(isset($link[$row[0]][0])?$link[$row[0]][0]:'').".";
				print (isset($link[$row[0]][1])?$link[$row[0]][1]:'')."</td>";

				print '<!-- ALTER ALTER TABLE '.$table.' MODIFY '.$row[0].' '.$row[1].' COLLATE utf8_unicode_ci; -->';
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
