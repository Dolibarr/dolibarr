<?PHP
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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
require("./pre.inc.php");

$langs->load("company");

require("../../contact.class.php");
require("../../lib/webcal.class.php");
require("../../cactioncomm.class.php");
require("../../actioncomm.class.php");

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}

/*
 *
 *
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

  if ($_POST["actionid"]) {

    $actioncomm = new ActionComm($db);

    $actioncomm->type = $_POST["actionid"];
    $actioncomm->priority = isset($_POST["priority"])?$_POST["priority"]:0;
    $actioncomm->libelle  = $_POST["label"];
    
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
    
    $actioncomm->add($user);
     
      if ($conf->webcal && $todo_webcal == 'on')
	{
	  $webcal = new Webcal();

	  $webcal->heure = $heurehour . $heuremin . '00';
	  $webcal->duree = ($dureehour * 60) + $dureemin;
    
	  if ($_POST["actionid"] == 5)
	    {
	      $libelle = "Rendez-vous avec ".$contact->fullname;
	      $libelle .= "\n" . $actioncomm->libelle;
	    }
	  else
	    {
	      $libelle = $actioncomm->libelle;
	    }
	  
	  $webcal->add($user, $actioncomm->date, $societe->nom, $libelle);
	}

  //  Header("Location: ".DOL_URL_ROOT."/comm/fiche.php?socid=$socid");
  Header("Location: ".$_POST["from"]);

  } else {
    
    print "Le type d'action n'a pas été choisi";
    
  }

}

if ($_POST["action"] == 'confirm_delete' && $_POST["confirm"] == 'yes')
{
  $actioncomm = new ActionComm($db);
  $actioncomm->delete($_GET["id"]);
  Header("Location: index.php");
}

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


/*
 * Affichage fiche action vierge en mode creation
 *
 */

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
	  print_titre ("Saisie d'une action Rendez-vous");	  
      print "<br>";

      print '<input type="hidden" name="date" value="'.$db->idate(time()).'">'."\n";
      
      print '<table class="border" width="100%" cellspacing="0" cellpadding="3">';

      // Type d'action
      print '<tr><td colspan="2"><div class="titre">Rendez-vous</div></td></tr>';
      print '<input type="hidden" name="actionid" value="5">';

      // Societe, contact
	  print '<tr><td width="10%">'.$langs->trans("Action concernant la companie").'</td><td width="40%">';
      if ($_GET["socid"]) {
          $societe = new Societe($db);
          $nomsoc=$societe->get_nom($_GET["socid"]);
          print '<a href="../fiche.php?socid='.$_GET["socid"].'">'.$nomsoc.'</a>';
          print '<input type="hidden" name="socid" value="'.$_GET["socid"].'">';
      }
      else {  
          print $html->select_societes('','socid',1,1);
      }
	  print '</td></tr>';
    
      // Si la societe est imposée, on propose ces contacts
      if ($_GET["socid"]) {
    	  print '<tr><td width="10%">'.$langs->trans("Action concernant le contact").'</td><td width="40%">';
          print $html->select_contacts($_GET["socid"],'','contactid',1,1);
    	  print '</td></tr>';
      }

      print '<tr><td width="10%">'.$langs->trans("Date").'</td><td width="40%">';
      $html->select_date('','ac');
      print '</td></tr>';
      print '<tr><td width="10%">'.$langs->trans("Hour").'</td><td width="40%">';
      print_heure_select("heure",8,20);
      print '</td></tr>';
      print '<tr><td width="10%">'.$langs->trans("Duration").'</td><td width="40%">';
      print_duree_select("duree");
      print '</td></tr>';

      print '<tr><td valign="top">Commentaire</td><td>';
      print '<textarea cols="60" rows="6" name="todo_note"></textarea></td></tr>';
      print '<tr><td colspan="2" align="center"><input type="submit" value="'.$langs->trans("Add").'"></td></tr>';  
      print '</table>';
    }

  /* 
   * Si action de type autre que rendez-vous
   *
   */   
  else 
    {      
	  print_titre ("Saisie d'une action");	  
      print "<br>";
      
	  print '<table class="border" width="100%" cellspacing="0" cellpadding="3">';

      // Type d'action actifs
      print '<tr><td width="10%">'.$langs->trans("Action").'</td><td>';
      if ($_GET["actionid"]) {
        print '<input type="hidden" name="actionid" value="'.$_GET["actionid"].'">'."\n";      
        print $caction->get_nom($_GET["actionid"]);
      } else {
        $html->select_array("actionid",  $caction->liste_array(1), 0);
      }
      print '</td></tr>';

      print '<tr><td width="10%">'.$langs->trans("Label").'</td><td><input type="text" name="todo_label" size="30"></td></tr>';

      // Societe, contact
	  print '<tr><td width="10%">'.$langs->trans("Action concernant la companie").'</td><td width="40%">';
      if ($_GET["socid"]) {
          $societe = new Societe($db);
          $nomsoc=$societe->get_nom($_GET["socid"]);
          print '<a href="../fiche.php?socid='.$_GET["socid"].'">'.$nomsoc.'</a>';
          print '<input type="hidden" name="socid" value="'.$_GET["socid"].'">';
      }
      else {  
          print $html->select_societes('','socid',1,1);
      }
	  print '</td></tr>';
    
      // Si la societe est imposée, on propose ces contacts
      if ($_GET["socid"]) {
    	  print '<tr><td width="10%">'.$langs->trans("Action concernant le contact").'</td><td width="40%">';
          print $html->select_contacts($_GET["socid"],'','contactid',1,1);
    	  print '</td></tr>';
      }

      // Avancement
      if ($_GET["afaire"] == 1)
	{
	  print '<input type="hidden" name="percentage" value="0">';
	  print '<input type="hidden" name="todo" value="on">';
	  print '<tr><td width="10%">'.$langs->trans("Status").' / '.$langs->trans("Percentage").'</td><td>To do / 0%</td></tr>';
	}
      elseif ($_GET["afaire"] == 2)
	{
	  print '<input type="hidden" name="percentage" value="100">';
	  print '<tr><td width="10%">'.$langs->trans("Status").' / '.$langs->trans("Percentage").'</td><td>Done / 100%</td></tr>';
    } else 
	{
	  print '<tr><td width="10%">'.$langs->trans("Status").' / '.$langs->trans("Percentage").'</td><td><input type="text" name="percentage" value="0%"></td></tr>';
	}

      // Date
      print '<tr><td width="10%">'.$langs->trans("Date").'</td><td width="40%">';
      if ($_GET["afaire"] == 1)
    	{
            print $html->select_date('','ac');
      } else if ($_GET["afaire"] == 2) {
            print $html->select_date('','ac',1,1);
      } else {
            print $html->select_date('','ac',1,1);
      }
      print '</td></tr>';

      // Lien avec celendrier si module activé
      if ($conf->webcal->enabled)
	{
	  $langs->load("other");
	  print '<tr><td width="10%">'.$langs->trans("AddCalendarEntry").'</td><td><input type="checkbox" name="todo_webcal"></td></tr>';
	}

      // Description
      print '<tr><td valign="top">'.$langs->trans("Description").'</td><td>';
      print '<textarea cols="60" rows="6" name="todo_note"></textarea></td></tr>';

      print '</table>';
  
      print '<p align="center"><input type="submit" value="'.$langs->trans("Add").'"></p>';

    }

    print "</form>";
}

/*
 *
 *
 *
 */
if ($_GET["id"])
{
  // Confirmation suppression action
  if ($_GET["action"] == 'delete')
    {
      $html->form_confirm("fiche.php?id=".$_GET["id"],"Supprimer l'action","Etes-vous sûr de vouloir supprimer cette action ?","confirm_delete");
    }
  
  $act = new ActionComm($db);
  $act->fetch($_GET["id"]);
  
  $act->societe->fetch($act->societe->id);
  $act->author->fetch($act->author->id);
  $act->contact->fetch($act->contact->id);
  
  // Fiche action en mode edition
  if ($_GET["action"] == 'edit')
    {      
      print_titre ("Edition de la fiche action");
      print '<form action="fiche.php" method="post">';
      print '<input type="hidden" name="action" value="update">';
      print '<input type="hidden" name="id" value="'.$_GET["id"].'">';
      print '<input type="hidden" name="from" value="'.$_SERVER["HTTP_REFERER"].'">';

      print '<table class="border" width="100%" cellspacing="0" cellpadding="3">';
      print '<tr><td width="20%">Type</td><td colspan="3">'.$act->type.'</td></tr>';
      print '<tr><td width="20%">Société</td>';
      print '<td width="30%"><a href="../fiche.php?socid='.$act->societe->id.'">'.$act->societe->nom.'</a></td>';
      
      print '<td width="20%">Contact</td><td width="30%">';
      $html->select_array("contactid",  $act->societe->contact_array(), $act->contact->id, 1);
      print '</td></tr>';
      print '<tr><td>'.$langs->trans("Author").'</td><td>'.strftime('%d %B %Y %H:%M',$act->date).'</td>';
      print '<td>'.$langs->trans("Author").'</td><td>'.$act->author->fullname.'</td></tr>';
      print '<tr><td>Pourcentage réalisé</td><td colspan="3"><input name="percent" value="'.$act->percent.'">%</td></tr>';
      if ($act->objet_url)
	{
	  print '<tr><td>Objet lié</td>';
	  print '<td colspan="3">'.$act->objet_url.'</td></tr>';
	}
      
      if ($act->note)
	{
	  print '<tr><td valign="top">Commentaire</td><td colspan="3">';
	  print nl2br($act->note).'</td></tr>';
	}
      print '<tr><td align="center" colspan="4"><input type="submit" value="Enregister"</td></tr>';
      print '</table></form>';
    }
  else
    {      
      // Affichage fiche action en mode visu
      print_titre ("Action commerciale");
      
      print '<table class="border" width="100%" cellspacing="0" cellpadding="3">';
      print '<tr><td width="20%">Type</td><td colspan="3">'.$act->type.'</td></tr>';
      print '<tr><td width="20%">Société</td>';
      print '<td width="30%"><a href="../fiche.php?socid='.$act->societe->id.'">'.$act->societe->nom.'</a></td>';
      
      print '<td width="10%">Contact</td><td width="40%">'.$act->contact->fullname.'</td></tr>';
      print '<tr><td>'.$langs->trans("Author").'</td><td>'.strftime('%d %B %Y %H:%M',$act->date).'</td>';
      print '<td>'.$langs->trans("Author").'</td><td>'.$act->author->fullname.'</td></tr>';
      print '<tr><td>Pourcentage réalisé</td><td colspan="3">'.$act->percent.' %</td></tr>';
      if ($act->objet_url)
	{
	  print '<tr><td>Objet lié</td>';
	  print '<td colspan="3">'.$act->objet_url.'</td></tr>';
	}
      
      if ($act->note)
	{
	  print '<tr><td valign="top">Commentaire</td><td colspan="3">';
	  print nl2br($act->note).'</td></tr>';
	}
      print '</table>';
    }
  /*
   *
   */
  print '<br><div class="tabsAction">';

  if ($_GET["action"] == 'edit')
    {
      print '<a class="tabAction" href="fiche.php?id='.$act->id.'">'.$langs->trans("Cancel").'</a>';
    }
  else
    {
      print '<a class="tabAction" href="fiche.php?action=edit&id='.$act->id.'">'.$langs->trans("Edit").'</a>';
    }

  print '<a class="tabAction" href="fiche.php?action=delete&id='.$act->id.'">'.$langs->trans("Delete").'</a>';
  print '</div>';
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
