<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
	    \file       htdocs/societe/notify/fiche.php
        \ingroup    societe
		\brief      Onglet notifications pour une societe
		\version    $Revision$
*/

require("pre.inc.php");

$langs->load("companies");

// Sécurité accés client
$socid = $_GET["socid"];
if ($user->societe_id > 0) 
{
    $socid = $user->societe_id;
}

$sortorder=$_GET["sortorder"];
$sortfield=$_GET["sortfield"];
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="c.name";


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
      $sql .= " VALUES (now(),$socid,".$_POST["contactid"].",".$_POST["actionid"].")";
      
      if ($db->query($sql))
	{
	  
	}
      else
	{
      dolibarr_print_error($db);
	}
    }
  else
    {
      dolibarr_print_error($db);
    }
}

/*
 * Action suppression notification
 */
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
 * Affichage notifications
 *
 */
$soc = new Societe($db);
$soc->id = $_GET["socid"];

if ( $soc->fetch($soc->id) )
{
    $html = new Form($db);
    $langs->load("other");
    
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
    		$langs->load("compta");
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

    $head[$h][0] = DOL_URL_ROOT.'/societe/info.php?socid='.$soc->id;
    $head[$h][1] = $langs->trans("Info");
    $h++;
    
    dolibarr_fiche_head($head, $hselected, $soc->nom);
    
    /*
    *
    *
    */
    
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
        dolibarr_print_error($db);
    }
    print $nb;
    print '</td></tr>';
    print '</table>';
    
    print '</div>';
    
    print "\n";
    
    // Ligne de titres
    print '<table width="100%" class="noborder">';
    print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans("Contact"),"fiche.php","c.name","","&socid=$socid",'',$sortfield);
    print_liste_field_titre($langs->trans("Action"),"fiche.php","a.titre","","&socid=$socid",'',$sortfield);
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
        dolibarr_print_error($db);
    }
    
    $var=false;
    print '<form action="fiche.php?socid='.$socid.'" method="post">';
    print '<input type="hidden" name="action" value="add">';
    print '<tr '.$bc[$var].'><td>';
    $html->select_array("contactid",$soc->contact_email_array());
    print '</td>';
    print '<td>';
    $html->select_array("actionid",$actions);
    print '</td>';
    print '<td align="center"><input type="submit" class="button" value="'.$langs->trans("Add").'"></td>';
    print '</tr>';
    print '</form>';
    
    
    // Liste
    $sql = "SELECT c.name, c.firstname, a.titre,n.rowid";
    $sql.= " FROM ".MAIN_DB_PREFIX."socpeople as c, ".MAIN_DB_PREFIX."action_def as a, ".MAIN_DB_PREFIX."notify_def as n";
    $sql.= " WHERE n.fk_contact = c.idp AND a.rowid = n.fk_action AND n.fk_soc = ".$soc->id;
    
    $resql=$db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);
        $i = 0;
        while ($i < $num)
        {
            $var = !$var;

            $obj = $db->fetch_object($resql);
    
            print '<tr '.$bc[$var].'><td>'.$obj->firstname . " ".$obj->name.'</td>';
            print '<td>'.$obj->titre.'</td>';
            print'<td align="center"><a href="fiche.php?socid='.$socid.'&action=delete&actid='.$obj->rowid.'">'.img_delete().'</a>';
            print '</tr>';
            $i++;
        }
        $db->free($resql);
    }
    else
    {
        dolibarr_print_error($db);
    }
    
    print '</table>';

}

$db->close();

llxFooter('$Date$ - $Revision$');

?>
