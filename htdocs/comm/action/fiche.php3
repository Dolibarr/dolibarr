<?PHP
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 *
 * $Id$
 * $Source$
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
 */
require("./pre.inc.php3");

require("../../contact.class.php3");
require("../..//lib/webcal.class.php3");
require("../cactioncomm.class.php3");
require("../actioncomm.class.php3");

$db = new Db();

/*
 *
 *
 *
 */
if ($action=='add_action') {
  $contact = new Contact($db);
  $contact->fetch($contactid);

  $actioncomm = new ActionComm($db);

  if ($actionid == 5) {
    $actioncomm->date = $db->idate(mktime($heurehour,$heuremin,0,$remonth,$reday,$reyear));
    $actioncomm->percent = 0;
  } else {
    $actioncomm->date = $date;
    $actioncomm->percent = 100;
  }
  $actioncomm->priority = 2;
  $actioncomm->type = $actionid;
  $actioncomm->contact = $contactid;



  $actioncomm->user = $user;

  $actioncomm->societe = $socid;
  $actioncomm->note = $note;

  $actioncomm->add($user);


  $societe = new Societe($db);
  $societe->fetch($socid);


  if ($todo) {

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

    if ($conf->webcal && $todo_webcal) {

      $webcal = new Webcal();

      $webcal->heure = $heurehour . $heuremin . '00';
      $webcal->duree = ($dureehour * 60) + $dureemin;
    
      if ($actionid == 5) {
	$libelle = "Rendez-vous avec ".$contact->fullname;
	$libelle .= "\n" . $todo->libelle;
      } else {
	$libelle = $todo->libelle;
      }
      
      $webcal->add($user, $todo->date, $societe->nom, $libelle);
    }
  }

  Header("Location: /comm/fiche.php3?socid=$socid");
}

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

      print '<tr><td colspan="2" bgcolor="#e0e0e0"><div class="titre">Rendez-vous</div></td></tr>';
      print '<tr><td width="10%">Société</td><td width="40%"bgcolor="#e0e0e0">';
      print '<b><a href="../fiche.php3?socid='.$socid.'">'.$societe->nom.'</a></td></tr>';
      print '<tr><td width="10%">Contact</td><td width="40%"bgcolor="#e0e0e0"><b>'.$contact->fullname.'</td></tr>';
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
   *Autre action
   *
   *
   */   
  else 
    {

      print '<form action="fiche.php3?socid='.$socid.'" method="post">';
      print '<input type="hidden" name="action" value="add_action">';

      print '<input type="hidden" name="date" value="'.$db->idate(time()).'">';
      print '<input type="hidden" name="actionid" value="'.$actionid.'">';
      print '<input type="hidden" name="contactid" value="'.$contactid.'">';
      print '<input type="hidden" name="socid" value="'.$socid.'">';
      
      print '<table width="100%" border="1" cellspacing="0" cellpadding="3">';
      
      print '<tr><td colspan="2" bgcolor="#e0e0e0"><div class="titre">Action effectuée</div></td></tr>';
      
      print '<tr><td width="10%">Action</td><td bgcolor="#e0e0e0"><b>'.$caction->libelle.'</td></tr>';
      print '<tr><td width="10%">Société</td><td width="40%"bgcolor="#e0e0e0">';
      print '<b><a href="../fiche.php3?socid='.$socid.'">'.$societe->nom.'</a></td></tr>';
      print '<tr><td width="10%">Contact</td><td width="40%"bgcolor="#e0e0e0"><b>'.$contact->fullname.'</td></tr>';
      print '<td>Date</td><td>'.strftime('%d %B %Y %H:%M',time()).'</td></tr>';
      print '<tr><td valign="top">Commentaire</td><td>';
      print '<textarea cols="60" rows="6" name="note"></textarea></td></tr>';
      
      print '<tr><td colspan="2" bgcolor="#e0e0e0"><div class="titre">Prochaine Action à faire</div></td></tr>';
      print '<tr><td width="10%">Ajouter</td><td><input type="checkbox" name="todo"></td></tr>';
      print '<tr><td width="10%">Date</td><td width="40%">';
      print_date_select();
      print '</td></tr>';
      print '<tr><td width="10%">Action</td><td><input type="text" name="todo_label" size="30"></td></tr>';
      print '<tr><td width="10%">Calendrier</td><td><input type="checkbox" name="todo_webcal"></td></tr>';
      print '<tr><td valign="top">Commentaire</td><td>';
      print '<textarea cols="60" rows="6" name="todo_note"></textarea></td></tr>';
      
      print '<tr><td colspan="2" align="center"><input type="submit" value="Enregistrer"></td></tr>';
  
      print '</form></table>';
  
    }
}
/*
 *
 *
 *
 */
if ($id) {
  $act = new ActionComm($db);
  $act->fetch($id);

  $act->societe->fetch($act->societe->id);

  print_titre ("Action commerciale");

  print '<table width="100%" border="1" cellspacing="0" cellpadding="3">';
  print '<tr><td width="10%">Type</td><td colspan="3" bgcolor="#e0e0e0"><b>'.$act->type.'</td></tr>';
  print '<tr><td width="10%">Société</td><td width="40%"bgcolor="#e0e0e0"><b>'.$act->societe->nom.'</td>';
  print '<td width="10%">Contact</td><td width="40%"bgcolor="#e0e0e0"><b>'.$fullname.'</td></tr>';
  print '<tr><td>Auteur</td><td>'.$fullname.'</td>';
  print '<td>Date</td><td>'.strftime('%d %B %Y %H:%M',time()).'</td></tr>';
  print '<tr><td valign="top">Commentaire</td><td colspan="3">';
  print nl2br($act->note).'</td></tr>';

  print '</table>';

  print '<p><p><a href="index.php3?action=delete_action&actionid='.$act->id.'">Supprimer</a>';
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
