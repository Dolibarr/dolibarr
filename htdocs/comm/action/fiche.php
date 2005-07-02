<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
        \file       htdocs/comm/action/fiche.php
        \ingroup    commercial
        \brief      Page de la fiche action commercial
        \version    $Revision$
*/
 
require("./pre.inc.php");

require("../../contact.class.php");
require("../../cactioncomm.class.php");
require("../../actioncomm.class.php");
if ($conf->webcal->enabled) {
    require("../../lib/webcal.class.php");
}

$langs->load("companies");
$langs->load("commercial");
$langs->load("other");
$langs->load("bills");


/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}

/*
 * Action création de l'action
 *
 */
if ($_POST["action"] == 'add_action') 
{
  if ($_POST["contactid"])
    {
      $contact = new Contact($db);
      $contact->fetch($_POST["contactid"]);
    }
  if ($_POST["socid"])
    {
      $societe = new Societe($db);
      $societe->fetch($_POST["socid"]);
    }

  if ($_POST["actionid"])
    {
      
      $actioncomm = new ActionComm($db);
      
      $actioncomm->type = $_POST["actionid"];
      $actioncomm->priority = isset($_POST["priority"])?$_POST["priority"]:0;
      if ($_POST["actionid"] == 5)
      {
          if ($contact->fullname) { $actioncomm->libelle = $langs->trans("TaskRDVWith",$contact->fullname); }
          else { $actioncomm->libelle = $langs->trans("TaskRDV"); }
      } else {
          $actioncomm->libelle = $_POST["label"];
      }
      $actioncomm->date = $db->idate(mktime($_POST["heurehour"],
					    $_POST["heuremin"],
					    0,
					    $_POST["acmonth"],
					    $_POST["acday"],
					    $_POST["acyear"])
				     );
      
      $actioncomm->percent = isset($_POST["percentage"])?$_POST["percentage"]:0;
      
      $actioncomm->user = $user;
      
      $actioncomm->societe = isset($_POST["socid"])?$_POST["socid"]:0;
      $actioncomm->contact = isset($_POST["contactid"])?$_POST["contactid"]:0;
      $actioncomm->note = $_POST["note"];
      
      // On definit la ressource webcal si le module webcal est actif
      $webcal=0;
      if ($conf->webcal->enabled && $_POST["todo_webcal"] == 'on')
	{
	  $webcal = new Webcal();
	  
	  if (! $webcal->localdb->ok)
	    {
	      // Si la creation de l'objet n'as pu se connecter
	      $error="Dolibarr n'a pu se connecter à la base Webcalendar avec les identifiants définis (host=".$conf->webcal->db->host." dbname=".$conf->webcal->db->name." user=".$conf->webcal->db->user."). L'option de mise a jour Webcalendar a été ignorée.";
	      $webcal=-1;
	    }
	  else
	    {
	      $webcal->heure = $_POST["heurehour"] . $_POST["heuremin"] . '00';
	      $webcal->duree = ($_POST["dureehour"] * 60) + $_POST["dureemin"];
	      
	      if ($_POST["actionid"] == 5)
		{
		  $libellecal = $langs->trans("TaskRDVWith",$contact->fullname);
		  $libellecal .= "\n" . $actioncomm->libelle;
		}
	      else
		{
		  $libellecal = $actioncomm->libelle;
		}
	      
	      $webcal->date=mktime($_POST["heurehour"],
				   $_POST["heuremin"],
				   0,
				   $_POST["acmonth"],
				   $_POST["acday"],
				   $_POST["acyear"]);
	      $webcal->texte=$societe->nom;
	      $webcal->desc=$libellecal;
	    }
	}
      
      // On crée l'action (avec ajout eventuel dans webcal si défini)
      $idaction=$actioncomm->add($user, $webcal);
      
      if ($idaction > 0)
	{
	  if (! $actioncomm->error)
	    {
	      // Si pas d'erreur
	      Header("Location: ".$_POST["from"]);
	    }
	  else
	    {
	      // Si erreur
	      $_GET["id"]=$idaction;
	      $error=$actioncomm->error;
	    }
	}
      else
	{
	  dolibarr_print_error($db);        
	}
    }
  else
    {
      print "Le type d'action n'a pas été choisi";
    }
  
}

/*
 * Action suppression de l'action
 *
 */
if ($_POST["action"] == 'confirm_delete' && $_POST["confirm"] == 'yes')
{
  $actioncomm = new ActionComm($db);
  $actioncomm->delete($_GET["id"]);

  Header("Location: index.php");
}

/*
 * Action mise à jour de l'action
 *
 */
if ($_POST["action"] == 'update')
{
  $action = new Actioncomm($db);
  $action->fetch($_POST["id"]);
  $action->percent     = $_POST["percent"];
  $action->contact->id = $_POST["contactid"];
  $action->update();

  Header("Location: ".$_POST["from"]);
}

llxHeader();

$html = new Form($db);

/* ************************************************************************** */
/*                                                                            */
/* Affichage fiche en mode création                                           */
/*                                                                            */
/* ************************************************************************** */

if ($_GET["action"] == 'create')
{
  $caction = new CActioncomm($db);
  
  if ($_GET["contactid"])
    {      
      $contact = new Contact($db);
      $contact->fetch($_GET["contactid"]);
    }

  print '<form action="fiche.php" method="post">';
  print '<input type="hidden" name="from" value="'.$_SERVER["HTTP_REFERER"].'">';
  print '<input type="hidden" name="action" value="add_action">';

  /*
   * Si action de type Rendez-vous
   *
   */
  if ($_GET["actionid"] == 5) 
    {
      print_titre ($langs->trans("AddActionRendezVous"));	  
      print "<br>";

      print '<input type="hidden" name="date" value="'.$db->idate(time()).'">'."\n";
      
      print '<table class="border" width="100%">';

      // Type d'action
      print '<input type="hidden" name="actionid" value="5">';

      // Societe, contact
      print '<tr><td nowrap>'.$langs->trans("ActionOnCompany").'</td><td>';
      if ($_GET["socid"])
	{
          $societe = new Societe($db);
          $societe->fetch($_GET["socid"]);
	  print $societe->nom_url;
          print '<input type="hidden" name="socid" value="'.$_GET["socid"].'">';
	}
      else
	{  
	  print $html->select_societes('','socid',1,1);
	}
      print '</td></tr>';
      
      // Si la societe est imposée, on propose ces contacts
      if ($_GET["socid"])
	{
	  print '<tr><td>'.$langs->trans("ActionOnContact").'</td><td width="40%">';
          print $html->select_contacts($_GET["socid"],'','contactid',1,1);
    	  print '</td></tr>';
	}

      print '<tr><td>'.$langs->trans("Date").'</td><td>';
      $html->select_date('','ac');
      print '</td></tr>';
      print '<tr><td>'.$langs->trans("Hour").'</td><td>';
      print_heure_select("heure",8,20);
      print '</td></tr>';
      print '<tr><td>'.$langs->trans("Duration").'</td><td>';
      print_duree_select("duree");
      print '</td></tr>';

      add_row_for_webcal_link();
        
      print '<tr><td valign="top">'.$langs->trans("Note").'</td><td>';
      print '<textarea cols="60" rows="6" name="note"></textarea></td></tr>';
      print '<tr><td colspan="2" align="center"><input type="submit" value="'.$langs->trans("Add").'"></td></tr>';  
      print '</table>';
    }

  /* 
   * Si action de type autre que rendez-vous
   *
   */   
  else 
    { 
      /*
       * Click to dial
       *
       */
      if ($conf->clicktodial->enabled)
	{
	  $user->fetch_clicktodial();

	  if ($_GET["call"] && $user->clicktodial_enabled == 1)
	    {

	      print '<Script language=javascript>'."\n";

	      $url = CLICKTODIAL_URL ."?login=".$user->clicktodial_login."&password=".$user->clicktodial_password."&caller=".$user->clicktodial_poste ."&called=".$_GET["call"];
	      
	      print 'window.open("'.$url.'","clicktodial", "toolbar=no,location=0,directories=0,status=0,menubar=no,scrollbars=1,resizable=1,copyhistory=0,width=400,height=300,top=10,left=10");';
	      print "\n</script>\n";
	    }
	}

      /*
       *
       *
       */

      print_titre ($langs->trans("AddAnAction"));
      print "<br>";
      
      print '<table class="border" width="100%">';

      // Type d'action actifs
      print '<tr><td>'.$langs->trans("Action").'</td><td>';
      if ($_GET["actionid"])
	{
	  print '<input type="hidden" name="actionid" value="'.$_GET["actionid"].'">'."\n";      
	  print $caction->get_nom($_GET["actionid"]);
	}
      else
	{
	  $html->select_array("actionid",  $caction->liste_array(1), 0);
	}
      print '</td></tr>';
      
      print '<tr><td>'.$langs->trans("Label").'</td><td><input type="text" name="label" size="30"></td></tr>';
      
      // Societe, contact
      print '<tr><td nowrap>'.$langs->trans("ActionOnCompany").'</td><td>';
      if ($_GET["socid"])
	{
          $societe = new Societe($db);
          $societe->fetch($_GET["socid"]);
	  print $societe->nom_url;
          print '<input type="hidden" name="socid" value="'.$_GET["socid"].'">';
	}
      else 
	{  
	  print $html->select_societes('','socid',1,1);
	}
      print '</td></tr>';
      
      // Si la societe est imposée, on propose ces contacts
      if ($_GET["socid"])
	{
	  print '<tr><td nowrap>'.$langs->trans("ActionOnContact").'</td><td>';
          print $html->select_contacts($_GET["socid"],'','contactid',1,1);
    	  print '</td></tr>';
	}
      
      // Avancement
      if ($_GET["afaire"] == 1)
	{
	  print '<input type="hidden" name="percentage" value="0">';
	  print '<input type="hidden" name="todo" value="on">';
	  print '<tr><td width="10%">'.$langs->trans("Status").' / '.$langs->trans("Percentage").'</td><td>'.$langs->trans("StatusActionToDo").' / 0%</td></tr>';
	}
      elseif ($_GET["afaire"] == 2)
	{
	  print '<input type="hidden" name="percentage" value="100">';
	  print '<tr><td>'.$langs->trans("Status").' / '.$langs->trans("Percentage").'</td><td>'.$langs->trans("StatusActionDone").' / 100%</td></tr>';
	} else 
	  {
	    print '<tr><td>'.$langs->trans("Status").' / '.$langs->trans("Percentage").'</td><td><input type="text" name="percentage" value="0%"></td></tr>';
	  }
      
      // Date
      print '<tr><td>'.$langs->trans("Date").'</td><td>';
      if ($_GET["afaire"] == 1)
	{
	  $html->select_date('','ac');
	  print '<tr><td>'.$langs->trans("Hour").'</td><td>';
	  print_heure_select("heure",8,20);
	  print '</td></tr>';
	} 
      else if ($_GET["afaire"] == 2) 
	{
	  $html->select_date('','ac',1,1);
	  print '<tr><td>'.$langs->trans("Hour").'</td><td>';
	  print_heure_select("heure",8,20);
	  print '</td></tr>';
	} 
      else 
	{
	  $html->select_date('','ac',1,1);
	  print '<tr><td>'.$langs->trans("Hour").'</td><td>';
	  print_heure_select("heure",8,20);
	  print '</td></tr>';
	}
      print '</td></tr>';
      
      add_row_for_webcal_link();
      
      // Note
      print '<tr><td valign="top">'.$langs->trans("Note").'</td><td>';
      print '<textarea cols="60" rows="6" name="note"></textarea></td></tr>';

      print '</table>';  
      print '<p align="center"><input type="submit" value="'.$langs->trans("Add").'"></p>';

    }
    print "</form>";
}

/*
 * Affichage action en mode edition ou visu
 *
 */
if ($_GET["id"])
{
    if ($error) { 
        print '<font class="error">'.$error.'</font><br><br>';
    }

    $act = new ActionComm($db);
    $act->fetch($_GET["id"]);
    
    $act->societe->fetch($act->societe->id);
    $act->author->fetch($act->author->id);
    $act->contact->fetch($act->contact->id);

    /*
     * Affichage onglets
     */

    $h = 0;

    $head[$h][0] = DOL_URL_ROOT.'/comm/action/fiche.php?id='.$_GET["id"];
    $head[$h][1] = $langs->trans("CardAction");
    $hselected=$h;
    $h++;

    dolibarr_fiche_head($head, $hselected, $langs->trans("Ref")." ".$act->id);


  // Confirmation suppression action
  if ($_GET["action"] == 'delete')
    {
      $html->form_confirm("fiche.php?id=".$_GET["id"],$langs->trans("DeleteAction"),$langs->trans("ConfirmDeleteAction"),"confirm_delete");
    }
  
  if ($_GET["action"] == 'edit')
    {
      // Fiche action en mode edition
      print '<form action="fiche.php" method="post">';
      print '<input type="hidden" name="action" value="update">';
      print '<input type="hidden" name="id" value="'.$_GET["id"].'">';
      print '<input type="hidden" name="from" value="'.$_SERVER["HTTP_REFERER"].'">';

      print '<table class="border" width="100%">';
      print '<tr><td>'.$langs->trans("Type").'</td><td colspan="3">'.$act->type.'</td></tr>';
      print '<tr><td>'.$langs->trans("Label").'</td><td colspan="3">'.$act->libelle.'</td></tr>';
      print '<tr><td>'.$langs->trans("Company").'</td>';
      print '<td><a href="../fiche.php?socid='.$act->societe->id.'">'.$act->societe->nom.'</a></td>';
      
      print '<td>'.$langs->trans("Contact").'</td><td width="30%">';
      $html->select_array("contactid",  $act->societe->contact_array(), $act->contact->id, 1);
      print '</td></tr>';
      print '<tr><td>'.$langs->trans("DateCreation").'</td><td>'.strftime('%d %B %Y %H:%M',$act->date).'</td>';
      print '<td>'.$langs->trans("Author").'</td><td>'.$act->author->fullname.'</td></tr>';
      print '<tr><td nowrap>'.$langs->trans("PercentDone").'</td><td colspan="3"><input name="percent" value="'.$act->percent.'">%</td></tr>';
      if ($act->objet_url)
	{
	  print '<tr><td>'.$langs->trans("LinkedObject").'</td>';
	  print '<td colspan="3">'.$act->objet_url.'</td></tr>';
	}
      
      if ($act->note)
	{
	  print '<tr><td valign="top">'.$langs->trans("Note").'</td><td colspan="3">';
	  print nl2br($act->note).'</td></tr>';
	}
      print '<tr><td align="center" colspan="4"><input type="submit" value="'.$langs->trans("Save").'"</td></tr>';
      print '</table></form>';
    }
  else
    {      
      // Affichage fiche action en mode visu
      print '<table class="border" width="100%"';
      print '<tr><td>'.$langs->trans("Type").'</td><td colspan="3">'.$act->type.'</td></tr>';
      print '<tr><td>'.$langs->trans("Label").'</td><td colspan="3">'.$act->libelle.'</td></tr>';
      print '<tr><td>'.$langs->trans("Company").'</td>';
      print '<td>'.$act->societe->nom_url.'</td>';
      
      print '<td>'.$langs->trans("Contact").'</td>';
      print '<td><a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$act->contact->id.'">'.$act->contact->fullname.'</a></td></tr>';
      print '<tr><td>'.$langs->trans("DateCreation").'</td><td>'.strftime('%d %B %Y %H:%M',$act->date).'</td>';
      print '<td>'.$langs->trans("Author").'</td><td>'.$act->author->fullname.'</td></tr>';
      print '<tr><td nowrap>'.$langs->trans("PercentDone").'</td><td colspan="3">'.$act->percent.' %</td></tr>';
      if ($act->objet_url)
	{
	  print '<tr><td>'.$langs->trans("LinkedObject").'</td>';
	  print '<td colspan="3">'.$act->objet_url.'</td></tr>';
	}
      
      if ($act->note)
	{
	  print '<tr><td valign="top">'.$langs->trans("Note").'</td><td colspan="3">';
	  print nl2br($act->note).'</td></tr>';
	}
      print '</table>';
    }
    print '<br>';
    
    print "</div>\n";


    /*
     * Barre d'actions
     *
     */

    print '<div class="tabsAction">';
    
    if ($_GET["action"] == 'edit')
    {
      print '<a class="butAction" href="fiche.php?id='.$act->id.'">'.$langs->trans("Cancel").'</a>';
    }
    else
    {
      print '<a class="butAction" href="fiche.php?action=edit&id='.$act->id.'">'.$langs->trans("Edit").'</a>';
    }
    
    print '<a class="butActionDelete" href="fiche.php?action=delete&id='.$act->id.'">'.$langs->trans("Delete").'</a>';
    print '</div>';
}

$db->close();

llxFooter('$Date$ - $Revision$');


/**
        \brief      Ajoute une ligne de tableau a 2 colonnes pour avoir l'option webcalendar
        \return     int     Retourne le nombre de lignes ajoutées
*/
function add_row_for_webcal_link()
{
    global $conf,$langs,$user;
    $nbtr=0;
    
    // Lien avec calendrier si module activé
    if ($conf->webcal->enabled) {
        if ($conf->webcal->syncro != 'never')
        {
            $langs->load("other");
            if (! $user->webcal_login)
            {
                print '<tr><td width="10%">'.$langs->trans("AddCalendarEntry").'</td>';
                print '<td><input type="checkbox" disabled name="todo_webcal">';
                print ' '.$langs->transnoentities("ErrorWebcalLoginNotDefined","<a href=\"".DOL_URL_ROOT."/user/fiche.php?id=".$user->id."\">".$user->login."</a>");
                print '</td>';
                print '</tr>';
                $nbtr++;
            }
            else
            {
                if ($conf->webcal->syncro == 'always')
                {
                    print '<input type="hidden" name="todo_webcal" value="on">';
                }
                else
                {
                    print '<tr><td width="10%">'.$langs->trans("AddCalendarEntry").'</td>';
                    print '<td><input type="checkbox" name="todo_webcal"'.(($conf->webcal->syncro=='always' || $conf->webcal->syncro=='yesbydefault')?' checked':'').'></td>';
                    print '</tr>';
                    $nbtr++;
                }
            }
        }
    }

    return $nbtr;
}


?>


