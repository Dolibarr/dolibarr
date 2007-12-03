<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004 Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004 Benoit Mortier       <benoit.mortier@opensides.be>
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
   \file       htdocs/admin/system/database.php
   \brief      Page des infos système de la base de donnée
   \version    $Revision$
*/

require("./pre.inc.php");
include_once $dolibarr_main_document_root."/lib/databases/".$conf->db->type.".lib.php";

$langs->load("admin");

if (!$user->admin)
  accessforbidden();


  
/*
* Afficahge page
*/
  
llxHeader();

print_fiche_titre($langs->trans("DatabaseConfiguration"),'','setup');

print '<br>';
print $langs->trans("DatabaseName").' : <b>'.$dolibarr_main_db_name.'</b><br>';


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
      print '<table class="noborder">';
      print '<tr class="liste_titre">';
      print '<td>'.$langs->trans("Parameter").'</td>';
      print '<td>'.$langs->trans("Value").'</td>';
      print "</tr>\n";
      
      $resql = $db->query($sql);
      if ($resql) 
	{
	  $var=True;
	  while ($row = $db->fetch_row($resql))
	    {
	      $var=!$var;
	      print "<tr $bc[$var]>";
	      print '<td>'.$row[0].'</td><td>'.$row[1]."</td></tr>\n";
	    }
	  $db->free($resql);
	}
      print "</table>\n";
    }
}

llxFooter('$Date$ - $Revision$');
?>
