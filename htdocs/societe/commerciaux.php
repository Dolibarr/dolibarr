<?php
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 */

/**
        \file       htdocs/societe/commerciaux.php
        \ingroup    societe
        \brief      Page d'affectations des commerciaux aux societes
        \version    $Id$
*/
 
require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");

$langs->load("companies");
$langs->load("commercial");
$langs->load("customers");
$langs->load("suppliers");
$langs->load("banks");

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe','','');


/*
*	Actions
*/

if($_GET["socid"] && $_GET["commid"])
{
  if ($user->rights->societe->creer)
    {
      $soc = new Societe($db);
      $soc->id = $_GET["socid"];
      $soc->fetch($_GET["socid"]);
      $soc->add_commercial($user, $_GET["commid"]);

      Header("Location: commerciaux.php?socid=".$soc->id);
    }
  else
    {
      Header("Location: commerciaux.php?socid=".$_GET["socid"]);
    }
}

if($_GET["socid"] && $_GET["delcommid"])
{
  if ($user->rights->societe->creer)
    {
      $soc = new Societe($db);
      $soc->id = $_GET["socid"];
      $soc->fetch($_GET["socid"]);
      $soc->del_commercial($user, $_GET["delcommid"]);

      Header("Location: commerciaux.php?socid=".$soc->id);
	  exit;
    }
  else
    {
      Header("Location: commerciaux.php?socid=".$_GET["socid"]);
	  exit;
    }
}


/*
*	View
*/

llxHeader();

if ($_GET["socid"])
{
    $soc = new Societe($db);
    $soc->id = $_GET["socid"];
    $result=$soc->fetch($_GET["socid"]);
    
    $head=societe_prepare_head2($soc);
	
    dolibarr_fiche_head($head, 'salesrepresentative', $langs->trans("ThirdParty"));
    
    /*
    * Fiche soci�t� en mode visu
    */
    
    print '<table class="border" width="100%">';
    print '<tr><td width="20%">'.$langs->trans('Name').'</td><td colspan="3">'.$soc->nom.'</td></tr>';
    
  print '<tr><td>';
  print $langs->trans('CustomerCode').'</td><td width="20%">';
  print $soc->code_client;
  if ($soc->check_codeclient() <> 0) print ' '.$langs->trans("WrongCustomerCode");
  print '</td><td>'.$langs->trans('Prefix').'</td><td>'.$soc->prefix_comm.'</td></tr>';

    print "<tr><td valign=\"top\">".$langs->trans('Address')."</td><td colspan=\"3\">".nl2br($soc->adresse)."</td></tr>";
    
    print '<tr><td>'.$langs->trans('Zip').'</td><td>'.$soc->cp."</td>";
    print '<td>'.$langs->trans('Town').'</td><td>'.$soc->ville."</td></tr>";
    
    print '<tr><td>'.$langs->trans('Country').'</td><td colspan="3">'.$soc->pays.'</td>';
    
    print '<tr><td>'.$langs->trans('Phone').'</td><td>'.dolibarr_print_phone($soc->tel).'</td>';
    print '<td>'.$langs->trans('Fax').'</td><td>'.dolibarr_print_phone($soc->fax).'</td></tr>';
    
    print '<tr><td>'.$langs->trans('Web').'</td><td colspan="3">';
    if ($soc->url) { print '<a href="http://'.$soc->url.'">http://'.$soc->url.'</a>'; }
    print '</td></tr>';
    
    // Liste les commerciaux
    print '<tr><td valign="top">'.$langs->trans("SalesRepresentatives").'</td>';
    print '<td colspan="3">';

    $sql = "SELECT u.rowid, u.name, u.firstname";
    $sql .= " FROM ".MAIN_DB_PREFIX."user as u";
    $sql .= " , ".MAIN_DB_PREFIX."societe_commerciaux as sc";
    $sql .= " WHERE sc.fk_soc =".$soc->id;
    $sql .= " AND sc.fk_user = u.rowid";
    $sql .= " ORDER BY u.name ASC ";
    
    $resql = $db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);
        $i = 0;
    
        while ($i < $num)
        {
        	$obj = $db->fetch_object($resql);
        	
          if (!$user->rights->societe->client->voir)
          {
          	print '<a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$obj->rowid.'">';
            print img_object($langs->trans("ShowUser"),"user").' ';
            print stripslashes($obj->firstname)." " .stripslashes($obj->name)."\n";
            print '</a><br>';
            $i++;
          }
          else
          {
            print '<a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$obj->rowid.'">';
            print img_object($langs->trans("ShowUser"),"user").' ';
            print $obj->firstname." " .$obj->name."\n";
            print '</a>&nbsp;';
            print '<a href="commerciaux.php?socid='.$_GET["socid"].'&amp;delcommid='.$obj->rowid.'">';
            print img_delete();
            print '</a><br>';
            $i++;
          }
        }
    
        $db->free($resql);
    }
    else
    {
        dolibarr_print_error($db);
    }
    if($i == 0) { print $langs->trans("NoSalesRepresentativeAffected"); }

    print "</td></tr>";    
    
    print '</table>';
    print "</div>\n";
    
    
    if ($user->rights->societe->creer && $user->rights->societe->client->voir)
    {
        /*
        * Liste
        *
        */
    
        $langs->load("users");
        $title=$langs->trans("ListOfUsers");
    
        $sql = "SELECT u.rowid, u.name, u.firstname, u.login";
        $sql .= " FROM ".MAIN_DB_PREFIX."user as u";
        $sql .= " ORDER BY u.name ASC ";
    
        $resql = $db->query($sql);
        if ($resql)
        {
            $num = $db->num_rows($resql);
            $i = 0;
    
            print_titre($title);
    
            // Lignes des titres
            print '<table class="noborder" width="100%">';
            print '<tr class="liste_titre">';
            print '<td>'.$langs->trans("Name").'</td>';
            print '<td>'.$langs->trans("Login").'</td>';
            print '<td>&nbsp;</td>';
            print "</tr>\n";
    
            $var=True;
    
            while ($i < $num)
            {
                $obj = $db->fetch_object($resql);
                $var=!$var;
                print "<tr $bc[$var]><td>";
                print '<a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$obj->rowid.'">';
                print img_object($langs->trans("ShowUser"),"user").' ';
                print stripslashes($obj->firstname)." " .stripslashes($obj->name)."\n";
                print '</a>';
                print '</td><td>'.$obj->login.'</td>';
                print '<td><a href="commerciaux.php?socid='.$_GET["socid"].'&amp;commid='.$obj->rowid.'">'.$langs->trans("Add").'</a></td>';
    
                print '</tr>'."\n";
                $i++;
            }
    
            print "</table>";
            $db->free($resql);
        }
        else
        {
            dolibarr_print_error($db);
        }
    }

}


$db->close();

llxFooter('$Date$ - $Revision$');
?>
