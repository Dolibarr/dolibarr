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
 *
 * $Id$
 * $Source$
 */

/**
        \file       htdocs/admin/system/database-tables-contraintes.php
        \brief      Page d'info des contraintes de la base
        \version    $Revision$
*/

require("./pre.inc.php");
include_once $dolibarr_main_document_root."/lib/databases/".$conf->db->type.".lib.php";

$langs->load("admin");


if (!$user->admin)
accessforbidden();


llxHeader();

print_fiche_titre($langs->trans("Constraints"),'','setup');

$base=0;
if ($conf->db->type == 'mysql' || $conf->db->type == 'mysqli')
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
	//$sql = "";
  	//$base=3;
}

if (! $base)
{
	print $langs->trans("FeatureNotAvailableWithThisDatabaseDriver");
}
else
{
	print '<br>';
	print '<table class="noborder">';
	print '<tr class="liste_titre">';
	
	if ($base==1)
	{
	    print '<td>'.$langs->trans("Tables").'</td>';
	    print '<td>'.$langs->trans("Type").'</td>';
	    print '<td>'.$langs->trans("Constraints").'</td>';
	}
	if ($base==2)
	{
	    print '<td>'.$langs->trans("Constraints").'</td>';
	    print '<td>'.$langs->trans("ConstraintsType").'</td>';
	}
	
	print "</tr>\n";
	
	
	$result = $db->query($sql);
	if ($result)
	{
	    $num = $db->num_rows($result);
	    $var=True;
	    $i=0;
	    while ($i < $num)
	    {
	        $obj = $db->fetch_object($rsult);
	        $var=!$var;
	        print "<tr $bc[$var]>";
	
	        if ($base==1)
	        {
	            print '<td><a href="dbtable.php?table='.$obj->Name.'">'.$obj->Name.'</a></td>';
	            print '<td>'.$obj->Engine.'</td>';
	            print '<td>'.$obj->Comment.'</td>';
	        }
			if ($base==2)
	        {
	            print '<td>'.$obj->conname.'</td>';
	            print '<td>'.$obj->contype.'</td>';
	        }
	
	        print '</tr>';
	        $i++;
	    }
	}
	print '</table>';
}

llxFooter('$Date$ - $Revision$');
?>
