<?php
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
 *
 */

require("pre.inc.php");

$langs->load("companies");

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  //$socid = $user->societe_id;
	$socid = $_GET["socid"];
}
	
$socid = $_GET["socid"];

llxHeader();

if ($_POST["action"] == 'add')
{
  $sql = "DELETE FROM ".MAIN_DB_PREFIX."notify_def";
  $sql .= " WHERE fk_soc=".$socid." AND fk_contact=".$_POST["contactid"]." AND fk_action=".$_POST["actionid"];
  if ($db->query($sql))
   {
      $sql = "INSERT INTO ".MAIN_DB_PREFIX."notify_def (datec,fk_soc, fk_contact, fk_action)";
      $sql .= " VALUES (now(),$socid,".$_POST["contactid"].",".$_POST["actionid"].")";
      
      if ($db->query($sql))
	{
	  
	}
      else
	{
	  print $sql;
	}
    }
  else
    {
      print $db->error() ."$sql";;
    }
}

if ($_GET["action"] == 'delete')
{
 $sql = "DELETE FROM ".MAIN_DB_PREFIX."notify_def where rowid=".$_GET["actid"].";";
 $db->query($sql);
    
 // if ($db->query($sql))
   // {
      // TODO ajouter une sécu pour la suppression 
    //}
}

/*
 *
 *
 */
$soc = new Societe($db);
$soc->id = $_GET["socid"];

if ( $soc->fetch($soc->id) ) 
{
  $h=0;
  
  $head[$h][0] = DOL_URL_ROOT.'/soc.php?socid='.$soc->id;
  $head[$h][1] = $langs->trans("Company");
  $h++;
  
  if ($soc->client==1)
    {
      $head[$h][0] = DOL_URL_ROOT.'/comm/fiche.php?socid='.$soc->id;
      $head[$h][1] = $langs->trans("Customer");
      $h++;
    }

  if ($soc->client==2)
    {
      $head[$h][0] = DOL_URL_ROOT.'/comm/prospect/fiche.php?id='.$soc->id;
      $head[$h][1] = $langs->trans("Prospect");
      $h++;
    }
  if ($soc->fournisseur)
    {
      $head[$h][0] = DOL_URL_ROOT.'/fourn/fiche.php?socid='.$soc->id;
      $head[$h][1] = $langs->trans("Supplier");
      $h++;
    }

  if ($conf->compta->enabled) {
      $head[$h][0] = DOL_URL_ROOT.'/compta/fiche.php?socid='.$soc->id;
      $head[$h][1] = $langs->trans("Accountancy");
      $h++;
  }

  $head[$h][0] = DOL_URL_ROOT.'/socnote.php?socid='.$soc->id;
  $head[$h][1] = $langs->trans("Note");
  $h++;
  
  if ($user->societe_id == 0)
    {
      $head[$h][0] = DOL_URL_ROOT.'/docsoc.php?socid='.$soc->id;
      $head[$h][1] = $langs->trans("Documents");
      $h++;
    }
      
  $head[$h][0] = DOL_URL_ROOT.'/societe/notify/fiche.php?socid='.$soc->id;
  $head[$h][1] = $langs->trans("Notifications");
  $hselected=$h;
  $h++;
  
  dolibarr_fiche_head($head, $hselected, $soc->nom);

  /*
   *
   *
   */

  print '<table class="border"width="100%">';
  print '<tr><td width="20%">'.$langs->trans("Name").'</td><td class="valeur">'.$soc->nom.'</td></tr>';
  print '<tr><td valign="top">'.$langs->trans("Address").'</td><td class="valeur">'.nl2br($soc->adresse).'&nbsp;</td></tr>';
  print '<tr><td>'.$langs->trans("Zip").' / '.$langs->trans("Town").'</td><td class="valeur">'.$soc->cp.' / '.$soc->ville.'</td></tr>';

  print '</table><br></div>';

  print "\n";

  /*
   *
   */
  if ($sortorder == "")
    {
      $sortorder="ASC";
    }
  if ($sortfield == "")
    {
      $sortfield="c.name";
    }
  print '<table width="100%" class="noborder">';
  print '<tr class="liste_titre">';
  print_liste_field_titre($langs->trans("Contact"),"fiche.php","c.name","","&socid=$socid",'',$sortfield);
  print_liste_field_titre($langs->trans("Action"),"fiche.php","a.titre","","&socid=$socid",'',$sortfield);
  print '<td>&nbsp;</td>';
  print '</tr>';

  $sql = "SELECT c.name, c.firstname, a.titre,n.rowid FROM ".MAIN_DB_PREFIX."socpeople as c, ".MAIN_DB_PREFIX."action_def as a, ".MAIN_DB_PREFIX."notify_def as n";
  $sql .= " WHERE n.fk_contact = c.idp AND a.rowid = n.fk_action AND n.fk_soc = ".$soc->id;

  if ($db->query($sql))
    {
      $num = $db->num_rows();
      $i = 0;      
      $var=True;
      while ($i < $num)
	{
	  $obj = $db->fetch_object( $i);
	  
	  print '<tr '.$bc[$var].'><td>'.$obj->firstname . " ".$obj->name.'</td>';
	  print '<td>'.$obj->titre.'</td>';
		print'<td align="center">
		<a href="fiche.php?socid='.$socid.'&action=delete&actid=		'.$obj->rowid.'">'.img_delete().'</a>';
		
	  $i++;
	  $var = !$var;
	}
      $db->free();
    }
  else
    {
      print $db->error();
    }
  /*
   *
   */

  $sql = "SELECT a.rowid, a.titre FROM ".MAIN_DB_PREFIX."action_def as a";

  if ($db->query($sql))
    {
      $num = $db->num_rows();
      $i = 0;      
      while ($i < $num)
	{
	  $obj = $db->fetch_object( $i);	  
	  $actions[$obj->rowid] = $obj->titre;
	  $i++;
	}
      $db->free();
    }
  else
    {
      print $db->error;
    }

  $html = new Form($db);
  print '<form action="fiche.php?socid='.$socid.'" method="post">';
  print '<input type="hidden" name="action" value="add">';
  print '<tr '.$bc[$var].'><td>';
  $html->select_array("contactid",$soc->contact_email_array());
  print '</td>';
  print '<td>';
  $html->select_array("actionid",$actions);
  print '</td>';
  print '<td align="center"><input type="submit" value="'.$langs->trans("Add").'"></td>';
  print '</tr></form></table>';

}
/*
 *
 */
$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
