<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004 Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004 Benoit Mortier			 <benoit.mortier@opensides.be>
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

/*!	\file htdocs/admin/system/mysql-tables.php
		\brief      Page d'infos des tables de la base
		\version    $Revision$
*/

require("./pre.inc.php");
include_once $dolibarr_main_document_root."/lib/${dolibarr_main_db_type}.lib.php";

$langs->load("admin");

if (!$user->admin)
  accessforbidden();

	

if ($_GET["action"] == 'convert')
{
  $db->query("alter table ".$_GET["table"]." type=INNODB");
}

llxHeader();
	
print_titre($langs->trans("Tables")." ".ucfirst($dolibarr_main_db_type));

if($dolibarr_main_db_type=="mysql")
{
    print '<br>';
    print '<table class="noborder">';
    print '<tr class="liste_titre">';
    print '<td>'.$langs->trans("TableName").'</td>';
    print '<td colspan="2">'.$langs->trans("Type").'</td>';
    print '<td>'.$langs->trans("TableLineFormat").'</td>';
    print '<td>'.$langs->trans("NbOfRecord").'</td>';
    print '<td>Avg_row_length</td>';
    print '<td>Data_length</td>';
    print '<td>Max_Data_length</td>';
    print '<td>Index_length</td>';
    print '<td>Last check</td>';
    print "</tr>\n";
    
    $sql = "SHOW TABLE STATUS";
    
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
          print "<TR $bc[$var]>";
    
          print '<td>'.$row[0].'</td>';
          print '<td>'.$row[1].'</td>';
          if ($row[1] == "MyISAM")
    	{
    	  print '<td><a href="mysql-tables.php?action=convert&amp;table='.$row[0].'">Convertir</a></td>';
    	}
          else
    	{
    	  print '<td>-</td>';
    	}
          print '<td>'.$row[2].'</td>';
          print '<td align="right">'.$row[3].'</td>';
          print '<td align="right">'.$row[4].'</td>';
          print '<td align="right">'.$row[5].'</td>';
          print '<td align="right">'.$row[6].'</td>';
          print '<td align="right">'.$row[7].'</td>';
          print '<td align="right">'.$row[12].'</td>';
          print '</tr>';
          $i++;
        }
    }
}
else
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
llxFooter();
?>
