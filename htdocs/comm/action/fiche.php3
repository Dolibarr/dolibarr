<?PHP
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require("./pre.inc.php3");

require("../../contact.class.php3");
require("../../lib/webcal.class.php3");
require("../../cactioncomm.class.php3");
require("../../actioncomm.class.php3");

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}


$db = new Db();

/*
 *
 *
 *
 */
if ($action=='add_action') 
{
  $contact = new Contact($db);
  $contact->fetch($contactid);
  $societe = new Societe($db);
  $societe->fetch($socid);

  $actioncomm = new ActionComm($db);
  
  $actioncomm->date = $db->idate(mktime($HTTP_POST_VARS["achour"],
					$HTTP_POST_VARS["acmin"],
					0,
					$HTTP_POST_VARS["acmonth"],
					$HTTP_POST_VARS["acday"],
					$HTTP_POST_VARS["acyear"])
				 );
  if ($actionid == 5) 
    {
      $actioncomm->percent = 0;
    }
  else
    {
      $actioncomm->percent = 100;
    }
  $actioncomm->priority = 2;
  $actioncomm->type = $actionid;
  $actioncomm->contact = $contactid;

  $actioncomm->user = $user;

  $actioncomm->societe = $socid;
  $actioncomm->note = $note;

  $actioncomm->add($user);

  if ($todo == 'on' )
    {

      $todo = new ActionComm($db);
      $todo->type = 0;
      $todo->date = $db->idate(mktime(12,0,0,$remonth, $reday, $reyear));
      
      $todo->libelle = $todo_label;
      $todo->priority = 2;
      $todo->societe = $societe->id;
      $todo->contact = $contactid;
      
      $todo->user = $user;
      
      $todo->note = $todo_note;
      
      $todo->percent = 0;
      
      $todo->add($user);
      
      if ($conf->webcal && $todo_webcal == 'on')
	{
	  $webcal = new Webcal();

	  $webcal->heure = $heurehour . $heuremin . '00';
	  $webcal->duree = ($dureehour * 60) + $dureemin;
    
	  if ($actionid == 5)
	    {
	      $libelle = "Rendez-vous avec ".$contact->fullname;
	      $libelle .= "\n" . $todo->libelle;
	    }
	  else
	    {
	      $libelle = $todo->libelle;
	    }
	  
	  $webcal->add($user, $todo->date, $societe->nom, $libelle);
	}
  }
  Header("Location: ".DOL_URL_ROOT."/comm/fiche.php3?socid=$socid");
}

if ($HTTP_POST_VARS["action"] == 'confirm_delete' && $HTTP_POST_VARS["confirm"] == yes)
{
  $actioncomm = new ActionComm($db);
  $actioncomm->delete($id);
  Header("Location: index.php");
}


/******************************************************************************/
/*                                                                            */
/*                  Fin des   Actions                                         */
/*                                                                            */
/******************************************************************************/

llxHeader();
/*
 *
 *
 *
 */
if ($action=='create' && $actionid && $contactid) {
  $caction = new CActioncomm();
  $caction->fetch($db, $actionid);

  $contact = new Contact($db);
  $contact->fetch($contactid);

  $societe = new Societe($db);
  $societe->get_nom($socid);

  /*
   * Rendez-vous
   *
   */
  if ($actionid == 5) 
    {
      print '<form action="'.$PHP_SELF.'?socid='.$socid.'" method="post">';
      print '<input type="hidden" name="action" value="add_action">';

      print '<input type="hidden" name="date" value="'.$db->idate(time()).'">';
      print '<input type="hidden" name="actionid" value="'.$actionid.'">';
      print '<input type="hidden" name="contactid" value="'.$contactid.'">';
      print '<input type="hidden" name="socid" value="'.$socid.'">';
      
      print '<table width="100%" border="1" cellspacing="0" cellpadding="3">';

      print '<tr><td colspan="2"><div class="titre">Rendez-vous</div></td></tr>';
      print '<tr><td width="10%">Société</td><td width="40%">';
      print '<a href="../fiche.php3?socid='.$socid.'">'.$societe->nom.'</a></td></tr>';
      print '<tr><td width="10%">Contact</td><td width="40%">'.$contact->fullname.'</td></tr>';
      print '<tr><td width="10%">Date</td><td width="40%">';
      print_date_select();
      print '</td></tr>';
      print '<tr><td width="10%">Heure</td><td width="40%">';
      print_heure_select("heure",8,20);
      print '</td></tr>';
      print '<tr><td width="10%">Durée</td><td width="40%">';
      print_duree_select("duree");
      print '</td></tr>';

      print '<tr><td valign="top">Commentaire</td><td>';
      print '<textarea cols="60" rows="6" name="todo_note"></textarea></td></tr>';
      
      print '<tr><td colspan="2" align="center"><input type="submit" value="Enregistrer"></td></tr>';
  
      print '</form></table>';
    }
  /* 
   *
   * Action autre que rendez-vous
   *
   *
   */   
  else 
    {
      $html = new Form($db);
      print_titre ("Action effectuée");

      print '<form action="'.$PHP_SELF.'?socid='.$socid.'" method="post">';
      print '<input type="hidden" name="action" value="add_action">';

      print '<input type="hidden" name="actionid" value="'.$actionid.'">';
      print '<input type="hidden" name="contactid" value="'.$contactid.'">';
      print '<input type="hidden" name="socid" value="'.$socid.'">';
      
      print '<table width="100%" border="1" cellspacing="0" cellpadding="3">';
            
      print '<tr><td width="10%">Action</td><td>'.$caction->libelle.'</td></tr>';
      print '<tr><td width="10%">Société</td><td width="40%">';
      print '<a href="../fiche.php3?socid='.$socid.'">'.$societe->nom.'</a></td></tr>';
      print '<tr><td width="10%">Contact</td><td width="40%">'.$contact->fullname.'</td></tr>';
      print '<td>Date</td><td>';
      print $html->select_date('','ac',1,1);
      print '</td></tr>';
      print '<tr><td valign="top">Commentaire</td><td>';
      print '<textarea cols="60" rows="6" name="note"></textarea></td></tr>';
      print "</table><p />";

      print_titre ("Prochaine Action à faire");

      print '<table width="100%" border="1" cellspacing="0" cellpadding="3">';

      print '<tr><td width="10%">Ajouter</td><td><input type="checkbox" name="todo"></td></tr>';
      print '<tr><td width="10%">Date</td><td width="40%">';
      print_date_select();
      print '</td></tr>';
      print '<tr><td width="10%">Action</td><td><input type="text" name="todo_label" size="30"></td></tr>';
      print '<tr><td width="10%">Calendrier</td><td><input type="checkbox" name="todo_webcal"></td></tr>';
      print '<tr><td valign="top">Commentaire</td><td>';
      print '<textarea cols="60" rows="6" name="todo_note"></textarea></td></tr>';
      print '</table>';
  
      print '<p align="center"><input type="submit" value="Enregistrer"></p>';

      print "</form>";
    }
}
/*
 *
 *
 *
 */
if ($id)
{

  if ($action == 'delete')
    {

      print '<form method="post" action="'.$PHP_SELF.'?id='.$id.'">';
      print '<input type="hidden" name="action" value="confirm_delete">';
      print '<table cellspacing="0" border="1" width="100%" cellpadding="3">';
      
      print '<tr><td colspan="3">Supprimer l\'action</td></tr>';
      
      print '<tr><td class="delete">Etes-vous sur de vouloir supprimer cette action ?</td><td class="delete">';
      $htmls = new Form($db);
      
      $htmls->selectyesno("confirm","no");
  
      print "</td>\n";
      print '<td class="delete" align="center"><input type="submit" value="Confirmer"</td></tr>';
      print '</table>';
      print "</form>\n";  
    }
  
  $act = new ActionComm($db);
  $act->fetch($id);

  $act->societe->fetch($act->societe->id);
  $act->author->fetch($act->author->id);
  $act->contact->fetch($act->contact->id);

  print_titre ("Action commerciale");

  print '<table width="100%" border="1" cellspacing="0" cellpadding="3">';
  print '<tr><td width="10%">Type</td><td colspan="3">'.$act->type.'</td></tr>';
  print '<tr><td width="10%">Société</td>';
  print '<td width="40%"><a href="../fiche.php3?socid='.$act->societe->id.'">'.$act->societe->nom.'</a></td>';

  print '<td width="10%">Contact</td><td width="40%">'.$act->contact->fullname.'</td></tr>';
  print '<tr><td>Auteur</td><td>'.$act->author->fullname.'</td>';
  print '<td>Date</td><td>'.strftime('%d %B %Y %H:%M',$act->date).'</td></tr>';
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

  /*
   *
   */
  print '<br><table border="1" cellspadding="3" cellspacing="0" width="100%"><tr>';

  print '<td align="center" width="20%">-</td>';
  print '<td align="center" width="20%">-</td>';
  print '<td align="center" width="20%">-</td>';
  print '<td align="center" width="20%">-</td>';
  print '<td align="center" width="20%">';
  print '<a href="fiche.php3?action=delete&id='.$act->id.'">Supprimer</a></td>';
  print '</table>';
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
