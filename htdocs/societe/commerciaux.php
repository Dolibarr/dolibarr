<?php
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

/*!
  \file       htdocs/societe/commerciaux.php
  \ingroup    societe
  \brief      Page d'affectations des commerciaux aux societes
  \version    $Revision$
*/
 
require("./pre.inc.php");

$user->getrights();

$langs->load("companies");
$langs->load("customers");
$langs->load("suppliers");

/*
 * Sécurité accés client
 */
 
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}

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
    }
  else
    {
      Header("Location: commerciaux.php?socid=".$_GET["socid"]);
    }
}

llxHeader();

if($_GET["socid"])
{

  $soc = new Societe($db);
  $soc->id = $_GET["socid"];
  $soc->fetch($_GET["socid"]);
  

  $head[0][0] = DOL_URL_ROOT.'/soc.php?socid='.$soc->id;
  $head[0][1] = $langs->trans("Company");
  $h = 1;

  $head[$h][0] = 'lien.php?socid='.$soc->id;
  $head[$h][1] = 'Lien';
  $h++;

  $head[$h][0] = 'commerciaux.php?socid='.$soc->id;
  $head[$h][1] = 'Commerciaux';
  $h++;

  $head[$h][0] = DOL_URL_ROOT.'/socnote.php?socid='.$soc->id;
  $head[$h][1] = $langs->trans("Note");
  $h++;
  
  dolibarr_fiche_head($head, 2, $soc->nom);

  /*
   * Fiche société en mode visu
   */

  print '<table class="border" width="100%">';
  print '<tr><td width="20%">'.$langs->trans('Name').'</td><td>'.$soc->nom.'</td></tr>';

  print '<tr><td valign="top">'.$langs->trans('Address')."</td><td>".nl2br($soc->adresse)."<br>".$soc->cp." ".$soc->ville." ".$soc->pays."</td></tr>";

  print '<tr><td>'.$langs->trans('Phone').'</td><td>'.dolibarr_print_phone($soc->tel).'</td></tr>';


  print '<tr><td>';
  print $langs->trans('Code client').'</td><td>';
  print $soc->code_client;
  if ($soc->check_codeclient() <> 0)
    {
      print "Code incorrect";
    }
  print '</td></tr>';

  print '<tr><td>'.$langs->trans('Web').'</td><td>';
  if ($soc->url) { print '<a href="http://'.$soc->url.'">http://'.$soc->url.'</a>'; }
  print '</td></tr>';
  
  if ($soc->parent > 0)
    {
      $socm = new Societe($db);
      $socm->fetch($soc->parent);
      
      print '<tr><td>Maison mère</td><td>'.$socm->nom_url.' ('.$socm->code_client.')<br />'.$socm->ville.'</td></tr>';
    }

  /* ********** */

  $sql = "SELECT u.rowid, u.name, u.firstname";
  $sql .= " FROM ".MAIN_DB_PREFIX."user as u";
  $sql .= " , ".MAIN_DB_PREFIX."societe_commerciaux as sc";
  $sql .= " WHERE sc.fk_soc =".$soc->id;
  $sql .= " AND sc.fk_user = u.rowid";
  $sql .= " ORDER BY u.name ASC ";
  
  $result = $db->query($sql);
  if ($result)
    {
      $num = $db->num_rows();
      $i = 0;
      
      while ($i < $num)
	{
	  $obj = $db->fetch_object();    

	  print '<tr><td>Commercial</td><td>';
	  print stripslashes($obj->firstname)." " .stripslashes($obj->name)."\n";
	  print '&nbsp;';
	  print '<a href="commerciaux.php?socid='.$_GET["socid"].'&amp;delcommid='.$obj->rowid.'">';
	  print img_delete();
	  print '</a></td></tr>'."\n";
	  $i++;
	}
      
      print "</table>";
      $db->free();
    }
  else
    {
      dolibarr_print_error($db);
    }
  

  print '</table>';
  print "<br></div>\n";



  if ($user->rights->societe->creer)
    {
      /*
       * Liste
       *
       */
      
      $title=$langs->trans("Liste des utilisateurs");
      
      $sql = "SELECT u.rowid, u.name, u.firstname";
      $sql .= " FROM ".MAIN_DB_PREFIX."user as u";
      $sql .= " ORDER BY u.name ASC ";
      
      $result = $db->query($sql);
      if ($result)
	{
	  $num = $db->num_rows();
	  $i = 0;
	  
	  print_titre($title);
	  
	  // Lignes des titres
	  print '<table class="noborder" width="100%">';
	  print '<tr class="liste_titre">';
	  print '<td>'.$langs->trans("Nom").'</td>';
	  print '<td>&nbsp;</td>';
	  print "</tr>\n";
	  
	  $var=True;
	  
	  while ($i < $num)
	    {
	      $obj = $db->fetch_object();    
	      $var=!$var;    
	      print "<tr $bc[$var]><td>";
	      print stripslashes($obj->firstname)." " .stripslashes($obj->name)."</td>\n";
	      print '<td><a href="commerciaux.php?socid='.$_GET["socid"].'&amp;commid='.$obj->rowid.'">Sélectionner</a></td>';
	      
	      print '</tr>'."\n";
	      $i++;
	    }
	  
	  print "</table>";
	  $db->free();
	}
      else
	{
	  dolibarr_print_error($db);
	}
    }            
  
}


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
