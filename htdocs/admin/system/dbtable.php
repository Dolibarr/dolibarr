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
 \file           htdocs/admin/system/dbtable.php
 \brief          Page d'info des contraintes d'une table
 \version        $Id$
 */

require("./pre.inc.php");
include_once $dolibarr_main_document_root."/lib/databases/".$conf->db->type.".lib.php";

$langs->load("admin");


if (!$user->admin)
accessforbidden();


/*
 * View
 */

llxHeader();


print_fiche_titre($langs->trans("Table") . " ".$_GET["table"],'','setup');

// Define request to get table description
$base=0;
if (eregi('mysql',$conf->db->type))
{
	$sql = "SHOW TABLE STATUS LIKE '".$_GET["table"]."'";
	$base=1;
}

if ($conf->db->type == 'pgsql')
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
	print '<br>';

	$result = $db->query($sql);
	if ($result)
	{
		$num = $db->num_rows($resql);
		$var=True;
		$i=0;
		while ($i < $num)
		{
			$row = $db->fetch_row($resql);
			$i++;
		}
	}


	if ($base==1)
	{

		$cons = explode(";",$row[14]);

		foreach  ($cons as $cc)
		{
			$cx = ereg_replace("\) REFER", "", $cc);
			$cx = ereg_replace("\(`", "", $cx);
			$cx = ereg_replace("`\)", "", $cx);
			$cx = ereg_replace("` ", "", $cx);

			$val = explode("`",$cx);

			$link[trim($val[0])][0] = $val[1];
			$link[trim($val[0])][1] = $val[2];

		}

		//  var_dump($link);

		print '<table>';
		print '<tr class="liste_titre"><td>'.$langs->trans("Fields").'</td><td>'.$langs->trans("Type").'</td><td>'.$langs->trans("Index").'</td>';
		print '<td>'.$langs->trans("FieldsLinked").'</td></tr>';

		$sql = "DESCRIBE ".$_GET["table"];
		$result = $db->query($sql);
		if ($result)
		{
			$num = $db->num_rows();
			$var=True;
			$i=0;
			while ($i < $num)
			{
				$row = $db->fetch_row($i);
				$var=!$var;
				print "<tr $bc[$var]>";


				print "<td>$row[0]</td>";
				print "<td>$row[1]</td>";
				print "<td>$row[3]</td>";
				print "<td>".$link[$row[0]][0].".";
				print $link[$row[0]][1]."</td>";


				print '</tr>';
				$i++;
			}
		}
		print '</table>';
	}
}

llxFooter('$Date$ - $Revision$');
?>
