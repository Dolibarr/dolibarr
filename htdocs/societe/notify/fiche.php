<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
	    \file       htdocs/societe/notify/fiche.php
        \ingroup    societe, notification
		\brief      Onglet notifications pour une societe
		\version    $Id$
*/

require("pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/contact.class.php");

$langs->load("companies");
$langs->load("mails");

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe','','');

$sortorder=$_GET["sortorder"];
$sortfield=$_GET["sortfield"];
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="c.name";


/*
*	View
*/

llxHeader();

/*
 * Action ajout notification
 */
if ($_POST["action"] == 'add')
{
  $sql = "DELETE FROM ".MAIN_DB_PREFIX."notify_def";
  $sql .= " WHERE fk_soc=".$socid." AND fk_contact=".$_POST["contactid"]." AND fk_action=".$_POST["actionid"];
  if ($db->query($sql))
   {
      $sql = "INSERT INTO ".MAIN_DB_PREFIX."notify_def (datec,fk_soc, fk_contact, fk_action)";
      $sql .= " VALUES (".$db->idate(mktime()).",".$socid.",".$_POST["contactid"].",".$_POST["actionid"].")";
      
      if ($db->query($sql))
	{
	  
	}
      else
	{
      dol_print_error($db);
	}
    }
  else
    {
      dol_print_error($db);
    }
}

/*
 * Action suppression notification
 */
if ($_GET["action"] == 'delete')
{
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."notify_def where rowid=".$_GET["actid"].";";
	$db->query($sql);
}


/*
 * Affichage notifications
 *
 */
$soc = new Societe($db);
$soc->id = $socid;

if ( $soc->fetch($soc->id) )
{
    $html = new Form($db);
    $langs->load("other");
    
	/*
	 * Affichage onglets
	 */
	$head = societe_prepare_head($soc);

	dol_fiche_head($head, 'notify', $soc->nom);

    
    print '<table class="border"width="100%">';
    print '<tr><td width="20%">'.$langs->trans("Name").'</td><td colspan="3">'.$soc->nom.'</td></tr>';
    print '<tr><td width="30%">'.$langs->trans("NbOfActiveNotifications").'</td>';
    print '<td colspan="3">';
    $sql = "SELECT COUNT(n.rowid) as nb";
    $sql.= " FROM ".MAIN_DB_PREFIX."notify_def as n";
    $sql.= " WHERE fk_soc = ".$soc->id;
    $resql=$db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);
        $i = 0;
        while ($i < $num)
        {
            $obj = $db->fetch_object($resql);
            $nb=$obj->nb;
            $i++;
        }
    }
    else {
        dol_print_error($db);
    }
    print $nb;
    print '</td></tr>';
    print '</table>';
    
    print '</div>';
    
    print "\n";
    
    if(count($soc->contact_email_array()) > 0)
    {
		print_fiche_titre($langs->trans("AddNewNotification"));
		
	    print '<form action="fiche.php?socid='.$socid.'" method="post">';
	
	    // Ligne de titres
	    print '<table width="100%" class="noborder">';
	    print '<tr class="liste_titre">';
	    print_liste_field_titre($langs->trans("Contact"),"fiche.php","c.name",'',"&socid=$socid",'"width="45%"',$sortfield,$sortorder);
	    print_liste_field_titre($langs->trans("Action"),"fiche.php","a.titre",'',"&socid=$socid",'"width="45%"',$sortfield,$sortorder);
	    print '<td>&nbsp;</td>';
	    print '</tr>';
	    
	    // Charge tableau $actions
	    $sql = "SELECT a.rowid, a.code, a.titre";
	    $sql.= " FROM ".MAIN_DB_PREFIX."action_def as a";
	    
	    $resql=$db->query($sql);
	    if ($resql)
	    {
	        $num = $db->num_rows($resql);
	        $i = 0;
	        while ($i < $num)
	        {
	            $obj = $db->fetch_object($resql);
	            $libelle=($langs->trans("Notify_".$obj->code)!="Notify_".$obj->code?$langs->trans("Notify_".$obj->code):$obj->titre);
	            $actions[$obj->rowid] = $libelle;
	    
	            $i++;
	        }
	        $db->free($resql);
	    }
	    else
	    {
	        dol_print_error($db);
	    }
	    
	    $var=false;

	    print '<input type="hidden" name="action" value="add">';
	    print '<tr '.$bc[$var].'><td>';
	    $html->select_array("contactid",$soc->contact_email_array());
	    print '</td>';
	    print '<td>';
	    $html->select_array("actionid",$actions);
	    print '</td>';
	    print '<td align="center"><input type="submit" class="button" value="'.$langs->trans("Add").'"></td>';
	    print '</tr>';
	    print '</table>';
	
	    print '</form>';
		print '<br>';  
    }  


	print_fiche_titre($langs->trans("ListOfActiveNotifications"));
	$var=true;
	
    // Ligne de titres
    print '<table width="100%" class="noborder">';
    print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans("Contact"),"fiche.php","c.name",'',"&socid=$socid",'"width="45%"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Action"),"fiche.php","a.titre",'',"&socid=$socid",'"width="45%"',$sortfield,$sortorder);
    print '<td>&nbsp;</td>';
    print '</tr>';
    
    // Liste
    $sql = "SELECT c.rowid as id, c.name, c.firstname, a.titre, n.rowid";
    $sql.= " FROM ".MAIN_DB_PREFIX."socpeople as c, ".MAIN_DB_PREFIX."action_def as a, ".MAIN_DB_PREFIX."notify_def as n";
    $sql.= " WHERE n.fk_contact = c.rowid AND a.rowid = n.fk_action AND n.fk_soc = ".$soc->id;
    
    $resql=$db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);
        $i = 0;

		$contactstatic=new Contact($db);
		
        while ($i < $num)
        {
            $var = !$var;

            $obj = $db->fetch_object($resql);
    
            $contactstatic->id=$obj->id;
            $contactstatic->name=$obj->name;
            $contactstatic->firstname=$obj->firstname;
            print '<tr '.$bc[$var].'><td>'.$contactstatic->getNomUrl(1).'</td>';
            print '<td>'.$obj->titre.'</td>';
            print'<td align="center"><a href="fiche.php?socid='.$socid.'&action=delete&actid='.$obj->rowid.'">'.img_delete().'</a>';
            print '</tr>';
            $i++;
        }
        $db->free($resql);
    }
    else
    {
        dol_print_error($db);
    }
    
    print '</table>';

}

$db->close();

llxFooter('$Date$ - $Revision$');

?>
