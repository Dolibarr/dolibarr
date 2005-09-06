<?php
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
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
        \file       htdocs/adherents/type.php
        \ingroup    adherent
		\brief      Page de configuration des types d'adhérents
		\version    $Revision$
*/

require("./pre.inc.php");
require(DOL_DOCUMENT_ROOT."/adherents/adherent.class.php");
require(DOL_DOCUMENT_ROOT."/adherents/adherent_type.class.php");

$langs->load("members");

$rowid=isset($_GET["rowid"])?$_GET["rowid"]:$_POST["rowid"];



if ($_POST["action"] == 'add' && $user->admin) 
{
    if ($_POST["button"] != $langs->trans("Cancel")) {
        $adht = new AdherentType($db);
          
        $adht->libelle     = trim($_POST["libelle"]);
        $adht->cotisation  = $yesno[$_POST["cotisation"]];
        $adht->commentaire = trim($_POST["comment"]);
        $adht->mail_valid  = trim($_POST["mail_valid"]);
        $adht->vote        = $yesno[$_POST["vote"]];

        if ($adht->libelle)
        {
            $id=$adht->create($user->id);
            if ($id > 0)
            {
                Header("Location: type.php");
            }
        }
    }
}

if ($_POST["action"] == 'update' && $user->admin) 
{
    if ($_POST["button"] != $langs->trans("Cancel")) {
        $adht = new AdherentType($db);
        $adht->id          = $_POST["rowid"];
        $adht->libelle     = $_POST["libelle"];
        $adht->cotisation  = $yesno[$_POST["cotisation"]];
        $adht->commentaire = $_POST["comment"];
        $adht->mail_valid  = $_POST["mail_valid"];
        $adht->vote        = $yesno[$_POST["vote"]];
        
        $adht->update($user->id);

        Header("Location: type.php?rowid=".$_POST["rowid"]);
        exit;
    }	  
}

if ($_GET["action"] == 'delete')
{
  $adh = new Adherent($db);
  $adh->delete($rowid);
  Header("Location: liste.php");
}
if ($_GET["action"] == 'commentaire')
{
  $don = new Don($db);
  $don->set_commentaire($rowid,$_POST["commentaire"]);
  $action = "edit";
}



llxHeader();



/* ************************************************************************** */
/*                                                                            */
/* Liste des types d'adhérents                                                */
/*                                                                            */
/* ************************************************************************** */

if (! $rowid && $_GET["action"] != 'create' && $_GET["action"] != 'edit') {

    print_titre($langs->trans("MembersTypeSetup"));
    print '<br>';


    $sql = "SELECT d.rowid, d.libelle, d.cotisation, d.vote";
    $sql .= " FROM ".MAIN_DB_PREFIX."adherent_type as d";
    
    $result = $db->query($sql);
    if ($result) 
    {
      $num = $db->num_rows($result);
      $i = 0;
      
      print '<table class="noborder" width="100%">';
      
      print '<tr class="liste_titre">';
      print '<td>'.$langs->trans("Ref").'</td>';
      print '<td>'.$langs->trans("Label").'</td><td align="center">'.$langs->trans("SubscriptionRequired").'</td>';
      print '<td align="center">'.$langs->trans("VoteAllowed").'</td><td>&nbsp;</td>';
      print "</tr>\n";
      
      $var=True;
      while ($i < $num)
        {
          $objp = $db->fetch_object($result);
          $var=!$var;
          print "<tr $bc[$var]>";
          print '<td><a href="type.php?rowid='.$objp->rowid.'">'.img_object($langs->trans("ShwoType"),'group').' '.$objp->rowid.'</a></td>';
          print '<td>'.$objp->libelle.'</td>';
          print '<td align="center">'.$langs->trans($objp->cotisation).'</td>';
          print '<td align="center">'.$langs->trans($objp->vote).'</td>';
          print '<td><a href="type.php?action=edit&rowid='.$objp->rowid.'">'.img_edit().'</td>';
          print "</tr>";
          $i++;
        }
      print "</table>";
    }
    else
    {
      dolibarr_print_error($db);
    }


    /*
     * Barre d'actions
     *
     */
    print '<div class="tabsAction">';
    print "<a class=\"tabAction\" href=\"type.php?action=create\">".$langs->trans("NewType")."</a>";
    print "</div>";

}


/* ************************************************************************** */
/*                                                                            */
/* Création d'un type adherent                                                */
/*                                                                            */
/* ************************************************************************** */


if ($_GET["action"] == 'create') {
  $htmls = new Form($db);

  print_titre($langs->trans("NewMemberType"));
  print '<br>';
  
  print "<form action=\"type.php\" method=\"post\">";
  print '<table class="border" width="100%">';
  
  print '<input type="hidden" name="action" value="add">';

  print '<tr><td>'.$langs->trans("Label").'</td><td><input type="text" name="libelle" size="40"></td></tr>';  

  print '<tr><td>'.$langs->trans("SubscriptionRequired").'</td><td>';
  $htmls->selectyesnonum("cotisation","");
  print '</tr>';
  
  print '<tr><td>'.$langs->trans("VoteAllowed").'</td><td>';
  $htmls->selectyesnonum("vote","");
  print '</tr>';

  print '<tr><td valign="top">'.$langs->trans("Comments").'</td><td>';
  print "<textarea name=\"comment\" wrap=\"soft\" cols=\"60\" rows=\"3\"></textarea></td></tr>";

  print '<tr><td valign="top">'.$langs->trans("WelcomeEMail").'</td><td>';
  print "<textarea name=\"mail_valid\" wrap=\"soft\" cols=\"60\" rows=\"15\"></textarea></td></tr>";

  print '<tr><td colspan="2" align="center"><input type="submit" name="button" class="button" value="'.$langs->trans("Add").'"> &nbsp;';
  print '<input type="submit" name="button" class="button" value="'.$langs->trans("Cancel").'"></td></tr>';

  print "</form>\n";
  print "</table>\n";
  
      
} 
/* ************************************************************************** */
/*                                                                            */
/* Edition de la fiche                                                        */
/*                                                                            */
/* ************************************************************************** */
if ($rowid > 0)
{
    if ($_GET["action"] != 'edit')
    {
        $adht = new AdherentType($db);
        $adht->id = $rowid;
        $adht->fetch($rowid);


        $h=0;
        
        $head[$h][0] = $_SERVER["PHP_SELF"].'?rowid='.$adht->id;
        $head[$h][1] = $langs->trans("MemberType").': '.$adht->libelle;
        $h++;

        dolibarr_fiche_head($head, 0, '');


        print '<table class="border" width="100%">';
        
        print '<tr><td width="15%">'.$langs->trans("Ref").'</td><td>'.$adht->id.'</td></tr>';  
        print '<tr><td width="15%">'.$langs->trans("Label").'</td><td>'.$adht->libelle.'</td></tr>';  
        
        print '<tr><td>'.$langs->trans("SubscriptionRequired").'</td><td>';
        print $adht->cotisation;
        print '</tr>';
        
        print '<tr><td>'.$langs->trans("VoteAllowed").'</td><td>';
        print $adht->vote;
        print '</tr>';
        
        print '<tr><td valign="top">'.$langs->trans("Comments").'</td><td>';
        print nl2br($adht->commentaire)."</td></tr>";
        
        print '<tr><td valign="top">'.$langs->trans("WelcomeEMail").'</td><td>';
        print nl2br($adht->mail_valid)."</td></tr>";
        
        print '</table>';

        print '</div>';

        /*
         * Barre d'actions
         *
         */
        print '<div class="tabsAction">';
        print "<a class=\"tabAction\" href=\"type.php?action=edit&amp;rowid=".$adht->id."\">".$langs->trans("Edit")."</a>";
        print "</div>";
    }
    
    if ($_GET["action"] == 'edit')
    {
        $htmls = new Form($db);
        
        $adht = new AdherentType($db);
        $adht->id = $rowid;
        $adht->fetch($rowid);


        $h=0;
        
        $head[$h][0] = $_SERVER["PHP_SELF"].'?rowid='.$adht->id;
        $head[$h][1] = $langs->trans("MemberType").': '.$adht->libelle;
        $h++;

        dolibarr_fiche_head($head, 0, '');


        print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?rowid='.$rowid.'">';
        print '<input type="hidden" name="rowid" value="'.$rowid.'">';
        print '<input type="hidden" name="action" value="update">';
        print '<table class="border" width="100%">';
        
        print '<tr><td width="15%">'.$langs->trans("Ref").'</td><td>'.$adht->id.'</td></tr>';  

        print '<tr><td>'.$langs->trans("Label").'</td><td><input type="text" name="libelle" size="40" value="'.$adht->libelle.'"></td></tr>';  
        
        print '<tr><td>'.$langs->trans("SubscriptionRequired").'</td><td>';
        $htmls->selectyesnonum("cotisation",$adht->cotisation);
        print '</tr>';
        
        print '<tr><td>'.$langs->trans("VoteAllowed").'</td><td>';
        $htmls->selectyesnonum("vote",$adht->vote);
        print '</tr>';
        
        print '<tr><td valign="top">'.$langs->trans("Comments").'</td><td>';
        print "<textarea name=\"comment\" wrap=\"soft\" cols=\"60\" rows=\"3\">".$adht->commentaire."</textarea></td></tr>";
        
        print '<tr><td valign="top">'.$langs->trans("WelcomeEMail").'</td><td>';
        print "<textarea name=\"mail_valid\" wrap=\"soft\" cols=\"60\" rows=\"15\">".$adht->mail_valid."</textarea></td></tr>";
        
        print '<tr><td colspan="2" align="center"><input type="submit" class="button" value="'.$langs->trans("Save").'"> &nbsp;';
        print '<input type="submit" name="button" class="button" value="'.$langs->trans("Cancel").'"></td></tr>';
        
        print '</table>';
        print "</form>";
    }
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
