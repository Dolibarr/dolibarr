<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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
require("./pre.inc.php");

//include "/home/vegeta/www/dolibarr/htdocs/conf/conf.php";
// ici comme ça j'ai la style sheet!

/*$conf = "../../conf/conf.php";
if (file_exists($conf))
{
 include($conf);
}*/

	include_once $dolibarr_main_document_root."/lib/${dolibarr_main_db_type}.lib.php";
	
 

if (!$user->admin)
  accessforbidden();
	
llxHeader();

if($dolibarr_main_db_type=="mysql")
{
 	print_titre("Configuration MySql");
  $sql = "SHOW VARIABLES";
	$base=1;
}
else
{
	print_titre("Configuration Pgsql");
	$sql = "select name,setting from pg_settings;";
	$base=2;
}

print '<br>';
print '<table class="noborder" cellpadding="3" cellspacing="1">';
print '<tr class="liste_titre">';
print '<td>Propriétés&nbsp;du&nbsp;serveur</td>';
print '<td>Valeur</td>';
print "</tr>\n";


$result = $db->query($sql);
if ($result) 
{
  $i=0;
  $num = $db->num_rows();
  $var=True;
  while ($i < $num)
    {
      $objp = $db->fetch_object( $i);
      $var=!$var;
      print "<tr $bc[$var]>";
			if($base==1)
      	print '<td>'.$objp->Variable_name.'</td><td>'.$objp->Value.'</td>';
			else
			  print '<td>'.$objp->name.'</td><td>'.$objp->setting.'</td>';
      print '</tr>';

      $i++;
    }
}
print '</table>';

llxFooter();
?>
