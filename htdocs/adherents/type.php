<?php
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
require_once(DOL_DOCUMENT_ROOT."/adherents/adherent.class.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/adherent_type.class.php");

$langs->load("members");

$rowid=isset($_GET["rowid"])?$_GET["rowid"]:$_POST["rowid"];


/*
*	Actions
*/
if ($user->rights->adherent->configurer && $_POST["action"] == 'add') 
{
    if ($_POST["button"] != $langs->trans("Cancel"))
    {
        $adht = new AdherentType($db);
          
        $adht->libelle     = trim($_POST["libelle"]);
        $adht->cotisation  = trim($_POST["cotisation"]);
        $adht->commentaire = trim($_POST["comment"]);
        $adht->mail_valid  = trim($_POST["mail_valid"]);
        $adht->vote        = trim($_POST["vote"]);

        if ($adht->libelle)
        {
            $id=$adht->create($user->id);
            if ($id > 0)
            {
                Header("Location: type.php");
                exit;
            }
			else
			{
				$mesg=$adht->error;
				$_GET["action"] = 'create';
			}
        }
        else
        {
        	$mesg=$langs->trans("ErrorFieldRequired",$langs->transnoentities("Label"));
			$_GET["action"] = 'create';
        }
    }
}

if ($user->rights->adherent->configurer && $_POST["action"] == 'update') 
{
    if ($_POST["button"] != $langs->trans("Cancel"))
    {
        $adht = new AdherentType($db);
        $adht->id          = $_POST["rowid"];
        $adht->libelle     = trim($_POST["libelle"]);
        $adht->cotisation  = trim($_POST["cotisation"]);
        $adht->commentaire = trim($_POST["comment"]);
        $adht->mail_valid  = trim($_POST["mail_valid"]);
        $adht->vote        = trim($_POST["vote"]);
        
        $adht->update($user->id);

        Header("Location: type.php?rowid=".$_POST["rowid"]);
        exit;
    }	  
}

if ($user->rights->adherent->configurer && $_GET["action"] == 'delete')
{
	$adht = new AdherentType($db);
	$adht->delete($rowid);
	Header("Location: ".$_SERVER["PHP_SELF"]);
	exit;
}

if ($user->rights->adherent->configurer && $_GET["action"] == 'commentaire')
{
	$don = new Don($db);
	$don->set_commentaire($rowid,$_POST["commentaire"]);
}



llxHeader();

/* ************************************************************************** */
/*                                                                            */
/* Liste des types d'adhérents                                                */
/*                                                                            */
/* ************************************************************************** */

if (! $rowid && $_GET["action"] != 'create' && $_GET["action"] != 'edit')
{

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
      print '<td>'.$langs->trans("Label").'</td>';
	  print '<td align="center">'.$langs->trans("SubscriptionRequired").'</td>';
      print '<td align="center">'.$langs->trans("VoteAllowed").'</td>';
	  print '<td>&nbsp;</td>';
      print "</tr>\n";
      
      $var=True;
      while ($i < $num)
        {
          $objp = $db->fetch_object($result);
          $var=!$var;
          print "<tr $bc[$var]>";
          print '<td><a href="type.php?rowid='.$objp->rowid.'">'.img_object($langs->trans("ShwoType"),'group').' '.$objp->rowid.'</a></td>';
          print '<td>'.$objp->libelle.'</td>';
          print '<td align="center">'.yn($objp->cotisation).'</td>';
          print '<td align="center">'.yn($objp->vote).'</td>';
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

	// New type
	if ($user->rights->adherent->configurer)
	{
		print "<a class=\"butAction\" href=\"type.php?action=create\">".$langs->trans("NewType")."</a>";
	}

    print "</div>";

}


/* ************************************************************************** */
/*                                                                            */
/* Création d'un type adherent                                                */
/*                                                                            */
/* ************************************************************************** */
if ($_GET["action"] == 'create')
{
	$htmls = new Form($db);

	print_titre($langs->trans("NewMemberType"));
	print '<br>';

	if ($mesg) print '<div class="error">'.$mesg.'</div>';

	print "<form action=\"type.php\" method=\"post\">";
	print '<table class="border" width="100%">';

	print '<input type="hidden" name="action" value="add">';

	print '<tr><td>'.$langs->trans("Label").'</td><td><input type="text" name="libelle" size="40"></td></tr>';  

	print '<tr><td>'.$langs->trans("SubscriptionRequired").'</td><td>';
	print $htmls->selectyesno("cotisation",1,1);
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("VoteAllowed").'</td><td>';
	print $htmls->selectyesno("vote",0,1);
	print '</td></tr>';

	print '<tr><td valign="top">'.$langs->trans("Description").'</td><td>';
	print "<textarea name=\"comment\" wrap=\"soft\" cols=\"60\" rows=\"3\"></textarea></td></tr>";

	print '<tr><td valign="top">'.$langs->trans("WelcomeEMail").'</td><td>';
	if ($conf->fckeditor->enabled)
	{
		require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
		$doleditor=new DolEditor('mail_valid',$adht->mail_valid,280,'dolibarr_notes','',false);
		$doleditor->Create();
	}
	else
	{
		print '<textarea class="flat" name="mail_valid" rows="15" cols="90">';
		print $adht->mail_valid;
		print '</textarea>';
	}
	print '</td></tr>';

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
        $head[$h][1] = $langs->trans("Card");
        $head[$h][2] = 'card';
        $h++;

        dolibarr_fiche_head($head, 'card', $langs->trans("MemberType"));


        print '<table class="border" width="100%">';
        
        print '<tr><td width="15%">'.$langs->trans("Ref").'</td><td>'.$adht->id.'</td></tr>';  
        print '<tr><td width="15%">'.$langs->trans("Label").'</td><td>'.$adht->libelle.'</td></tr>';  
        
        print '<tr><td>'.$langs->trans("SubscriptionRequired").'</td><td>';
        print yn($adht->cotisation);
        print '</tr>';
        
        print '<tr><td>'.$langs->trans("VoteAllowed").'</td><td>';
        print yn($adht->vote);
        print '</tr>';
        
        print '<tr><td valign="top">'.$langs->trans("Description").'</td><td>';
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

		// Edit
		if ($user->rights->adherent->configurer)
		{
			print "<a class=\"butAction\" href=\"type.php?action=edit&amp;rowid=".$adht->id."\">".$langs->trans("Modify")."</a>";
		}
	
		// Add
	    print "<a class=\"butAction\" href=\"fiche.php?action=create&typeid=".$adht->id."\">".$langs->trans("AddMember")."</a>";

		// Delete
		if ($user->rights->adherent->configurer)
		{
			print "<a class=\"butActionDelete\" href=\"type.php?action=delete&rowid=".$adht->id."\">".$langs->trans("DeleteType")."</a>";
		}
		
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
        $head[$h][1] = $langs->trans("Card");
        $head[$h][2] = 'card';
        $h++;

        dolibarr_fiche_head($head, 'card', $langs->trans("MemberType"));


        print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?rowid='.$rowid.'">';
        print '<input type="hidden" name="rowid" value="'.$rowid.'">';
        print '<input type="hidden" name="action" value="update">';
        print '<table class="border" width="100%">';
        
        print '<tr><td width="15%">'.$langs->trans("Ref").'</td><td>'.$adht->id.'</td></tr>';  

        print '<tr><td>'.$langs->trans("Label").'</td><td><input type="text" name="libelle" size="40" value="'.$adht->libelle.'"></td></tr>';  
        
        print '<tr><td>'.$langs->trans("SubscriptionRequired").'</td><td>';
        print $htmls->selectyesno("cotisation",$adht->cotisation,1);
        print '</td></tr>';
        
        print '<tr><td>'.$langs->trans("VoteAllowed").'</td><td>';
        print $htmls->selectyesno("vote",$adht->vote,1);
        print '</td></tr>';
        
        print '<tr><td valign="top">'.$langs->trans("Description").'</td><td>';
        print "<textarea name=\"comment\" wrap=\"soft\" cols=\"90\" rows=\"3\">".$adht->commentaire."</textarea></td></tr>";
        
        print '<tr><td valign="top">'.$langs->trans("WelcomeEMail").'</td><td>';
		if ($conf->fckeditor->enabled)
		{
			require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
			$doleditor=new DolEditor('mail_valid',$adht->mail_valid,280,'dolibarr_notes','',false);
			$doleditor->Create();
		}
		else
		{
			print '<textarea class="flat" name="mail_valid" rows="15" cols="90">';
			print $adht->mail_valid;
			print '</textarea>';
		}
        print "</td></tr>";
        
        print '<tr><td colspan="2" align="center"><input type="submit" class="button" value="'.$langs->trans("Save").'"> &nbsp; &nbsp;';
        print '<input type="submit" name="button" class="button" value="'.$langs->trans("Cancel").'"></td></tr>';
        
        print '</table>';
        print "</form>";
    }
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
