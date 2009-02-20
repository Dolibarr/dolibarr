<?PHP
/* Copyright (C) 2004-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
    \file       htdocs/telephonie/config/xdsl.php
    \ingroup    telephonie
    \brief      Page configuration telephonie
    \version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/fourn/fournisseur.class.php');
require_once(DOL_DOCUMENT_ROOT.'/telephonie/adsl/fournisseurxdsl.class.php');

$langs->load("admin");
$langs->load("suppliers");
$langs->load("products");

if (!$user->admin) accessforbidden();

if ($_GET["action"] == "all")
{

  $sql_d = "DELETE FROM ".MAIN_DB_PREFIX."societe_perms;";
  
  if ($resql_d = $db->query( $sql_d) )
    {
      $socs = array();
      
      $sql_s = "SELECT rowid FROM ".MAIN_DB_PREFIX."societe;";
      
      if ( $resql_s = $db->query( $sql_s) )
	{
	  while ($row_s = $db->fetch_row($resql_s))
	    {
	      array_push($socs, $row_s[0]);
	    }
	  $db->free($resql);
	}
      
      $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."user;";
      
      if ( $resql = $db->query( $sql) )
	{
	  while ($row = $db->fetch_row($resql))
	    {	      

	      foreach ($socs as $soc)
		{
		  $sql_i = "INSERT INTO ".MAIN_DB_PREFIX."societe_perms ";
		  $sql_i.= " (fk_soc,fk_user,pread,pwrite,pperms) ";
		  $sql_i.= " VALUES ($soc,$row[0],1,1,1) ";
		  
		  $resql_i = $db->query( $sql_i );
		}
	      
	    }
	  $db->free($resql);
	}
    }
   
  Header("Location: perms.php");
}

/*
 *
 *
 *
 */
llxHeader('','Téléphonie - Configuration - Permissions');

$h=0;
$head[$h][0] = DOL_URL_ROOT."/telephonie/config/perms.php";
$head[$h][1] = $langs->trans("Specials");
$hselected = $h;
$h++;

dol_fiche_head($head, $hselected, "Definitions des permissions");

print_titre("Actions speciales");

print '<table class="noborder" cellpadding="3" cellspacing="0" width="100%">';

print '<tr class="liste_titre">';
print '<td>Actions</td>';
print '<td align="center">-</td>';
print "</tr>\n";


$var=!$var;
print "<tr $bc[$var]><td>Toutes les permissions a tout le monde</td>";
print '<td align="center"><a href="perms.php?action=all">Appliquer</a>';
print '</tr>';

print '</table>';
print '</div>';

$db->close();

llxFooter();
?>
