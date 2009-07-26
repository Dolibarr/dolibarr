<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 \file       htdocs/admin/system/database-tables.php
 \brief      Page d'infos des tables de la base
 \version    $Id$
 */

require("./pre.inc.php");
include_once $dolibarr_main_document_root."/lib/databases/".$conf->db->type.".lib.php";

$langs->load("admin");

if (!$user->admin)
accessforbidden();


if ($_GET["action"] == 'convert')
{
	$db->query("alter table ".$_GET["table"]." type=INNODB");
}


/*
 * View
 */

llxHeader();

print_fiche_titre($langs->trans("Tables")." ".ucfirst($conf->db->type),'','setup');


// Define request to get table description
$base=0;
if (eregi('mysql',$conf->db->type))
{
	$sql = "SHOW TABLE STATUS";
	$base=1;
}
else if ($conf->db->type == 'pgsql')
{
	$sql = "SELECT conname, contype FROM pg_constraint;";
	$base=2;
}
else if ($conf->db->type == 'mssql')
{
	//TODO: r�cup�rer les infos du serveur
	//$sqls[0] = "";
	//$base=3;
}


if (! $base)
{
	print $langs->trans("FeatureNotAvailableWithThisDatabaseDriver");
}
else
{
	if ($base == 1)
	{
		print '<table class="noborder">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("TableName").'</td>';
		print '<td colspan="2">'.$langs->trans("Type").'</td>';
		print '<td>'.$langs->trans("Format").'</td>';
		print '<td>'.$langs->trans("NbOfRecord").'</td>';
		print '<td>Avg_row_length</td>';
		print '<td>Data_length</td>';
		print '<td>Max_Data_length</td>';
		print '<td>Index_length</td>';
		print '<td>Increment</td>';
		print '<td>Last check</td>';
		print '<td>Collation</td>';
		print "</tr>\n";

		$sql = "SHOW TABLE STATUS";

		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$var=True;
			$i=0;
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);
				$var=!$var;
				print "<tr $bc[$var]>";

				print '<td><a href="dbtable.php?table='.$obj->Name.'">'.$obj->Name.'</a></td>';
				print '<td>'.$obj->Engine.'</td>';
				if ($row[1] == "MyISAM")
				{
					print '<td><a href="database-tables.php?action=convert&amp;table='.$row[0].'">'.$langs->trans("Convert").'</a></td>';
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
				print '<td align="right">'.$obj->Collation.'</td>';
				print '</tr>';
				$i++;
			}
		}
		print '</table>';
	}

	if ($base == 2)
	{
		print '<br>';
		print '<table class="noborder">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("TableName").'</td>';
		print '<td>Nombre de tuples lu</td>';
		print '<td>Nb index fetcher.</td>';
		print '<td>Nbre de tuples inserer</td>';
		print '<td>Nbre de tuple modifier</td>';
		print '<td>Nbre de tuple supprimer</td>';
		print "</tr>\n";
		$sql = "select relname,seq_tup_read,idx_tup_fetch,n_tup_ins,n_tup_upd,n_tup_del from pg_stat_user_tables;";

		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$var=True;
			$i=0;
			while ($i < $num)
			{
				$row = $db->fetch_row($resql);
				$var=!$var;
				print "<tr $bc[$var]>";
				print '<td align="right">'.$row[0].'</td>';
				print '<td align="right">'.$row[1].'</td>';
				print '<td align="right">'.$row[2].'</td>';
				print '<td align="right">'.$row[3].'</td>';
				print '<td align="right">'.$row[4].'</td>';
				print '<td align="right">'.$row[5].'</td>';
				print '</tr>';
				$i++;
			}
		}
		print '</table>';
	}
}

llxFooter('$Date$ - $Revision$');
?>
