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

require("./pre.inc.php");

if ($action == 'add') {
  $newsletter = new Newsletter($db);

  $newsletter->email_subject    = $_POST["email_subject"];
  $newsletter->email_from_name  = $_POST["email_from_name"];
  $newsletter->email_from_email = $_POST["email_from_email"];
  $newsletter->email_replyto    = $_POST["email_replyto"];
  $newsletter->email_body       = $_POST["email_body"];

  $id = $newsletter->create($user);
}

if ($action == 'addga') {
  $newsletter = new Newsletter($db);

  $newsletter->linkga($id, $ga);
}

if ($action == 'update' && !$cancel)
{
  $newsletter = new Newsletter($db);

  $newsletter->email_subject    = $_POST["email_subject"];
  $newsletter->email_from_name  = $_POST["email_from_name"];
  $newsletter->email_from_email = $_POST["email_from_email"];
  $newsletter->email_replyto    = $_POST["email_replyto"];
  $newsletter->email_body       = $_POST["email_body"];

  $newsletter->update($id, $user);
}

if ($_POST["action"] == 'confirm_delete' && $_POST["confirm"] == yes)
{
  $newsletter = new Newsletter($db);
  $result = $newsletter->fetch($id);
  $newsletter->delete();
  Header("Location: index.php");
}

if ($_POST["action"] == 'confirm_valid' && $_POST["confirm"] == yes)
{
  $newsletter = new Newsletter($db);
  $result = $newsletter->fetch($id);
  $newsletter->validate($user);
}

if ($_POST["action"] == 'confirm_send' && $_POST["confirm"] == yes)
{
  $newsletter = new Newsletter($db);
  $result = $newsletter->fetch($id);
  $newsletter->send($user);
}


llxHeader();

/*
 *
 *
 */
if ($action == 'create')
{

  print "<form action=\"fiche.php?id=$id\" method=\"post\">\n";
  print '<input type="hidden" name="action" value="add">';

  print '<div class="titre">Nouvelle Newsletter</div><br>';
      
  print '<table border="1" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr><td width="20%">Emetteur nom</td><td><input name="email_from_name" size="30" value=""></td></tr>';
  print '<tr><td width="20%">Emetteur email</td><td><input name="email_from_email" size="40" value=""></td></tr>';
  print '<tr><td width="20%">Email de réponse</td><td><input name="email_replyto" size="40" value=""> (facultatif)</td></tr>';
  print '<tr><td width="20%">Sujet</td><td width="80%"><input name="email_subject" size="30" value=""></td></tr>';
  print '<tr><td width="20%">Cible</td><td><input name="nom" size="40" value=""></td></tr>';
  print '<tr><td width="20%" valign="top">Texte</td><td width="80%"><textarea name="email_body" rows="10" cols="60"></textarea></td></tr>';
  print '<tr><td colspan="2" align="center"><input type="submit" value="Créer"></td></tr>';
  print '</table>';
  print '</form>';

}
else
{
  if ($id)
    {

      $newsletter = new Newsletter($db);
      $result = $newsletter->fetch($id);

      if ( $result )
	{ 

	  /*
	   * Confirmation de la suppression de la newsletter
	   *
	   */
	  if ($action == 'delete')
	    {
	      $htmls = new Form($db);
          $htmls->form_confirm("fiche.php?id=$id","Supprimer une newsletter","Etes-vous sur de vouloir supprimer cet newsletter ?","confirm_delete");
	    }

	  /*
	   * Confirmation de la validation
	   *
	   */	  
	  if ($action == 'valid')
	    {
	      $htmls = new Form($db);
	      $htmls->form_confirm('fiche.php?id='.$id,"Valider une newsletter","Etes-vous sûr de vouloir valider cette newsletter ?");
	    }
	  /*
	   *
	   *
	   */
	  if ($action == 'send')
	    {
	      
	      print '<form method="post" action="fiche.php?id='.$id.'">';
	      print '<input type="hidden" name="action" value="confirm_send">';
	      print '<table class="border" width="100%">';
	      
	      print '<tr><td colspan="3">Envoi de newsletter</td></tr>';
	      
	      print '<tr><td class="delete">Etes-vous sur de vouloir envoyer cette newsletter ?</td><td class="delete">';
	      $htmls = new Form($db);
	      
	      $htmls->selectyesno("confirm","no");
	      
	      print "</td>\n";
	      print '<td class="delete" align="center"><input type="submit" value="Confirmer"</td></tr>';
	      print '</table>';
	      print "</form>\n";  
	    }
	  
	  /*
	   * Edition de la fiche
	   *
	   */


	  if ($action == 'edit')
	    {
	      print '<div class="titre">Edition de la fiche Newsletter : '.$newsletter->titre.'</div><br>';
	      
	      print "<form action=\"fiche.php?id=$id\" method=\"post\">\n";
	      print '<input type="hidden" name="action" value="update">';
	      
	      print '<table class="border">';

	      print '<tr><td>Emetteur nom</td><td><input name="email_from_name" size="30" value="'.$newsletter->email_from_name.'"></td></tr>';
	      print '<tr><td>Emetteur email</td><td><input name="email_from_email" size="40" value="'.$newsletter->email_from_email.'"></td></tr>';
	      print '<tr><td>Email de réponse</td><td><input name="email_replyto" size="40" value="'.$newsletter->email_replyto.'"></td></tr>';
	      
	      print "<tr>";
	      print '<td width="20%">Sujet</td>';
	      print '<td><input name="email_subject" size="40" value="'.$newsletter->email_subject.'"></td>';

	      print '<tr><td width="20%" valign="top">Texte</td><td width="80%"><textarea name="email_body" rows="10" cols="60">'.$newsletter->email_body.'</textarea></td></tr>';

	      print '<tr><td colspan="2" align="center"><input type="submit" value="'.$langs->trans("Save").'">&nbsp;<input type="submit" value="'.$langs->trans("Cancel").'" name="cancel"></td></tr>';
	      
	      print '</form>';

	      print '</table><hr>';
	      
	    }    

	  /*
	   * Affichage de la fiche
	   *
	   */

	  print '<div class="titre">Fiche Newsletter : '.$newsletter->titre.'</div><br>';

	  print '<table class="border" width="100%">';

	  print '<tr><td width="20%">Emetteur nom</td><td>'.$newsletter->email_from_name.'</td></tr>';
	  print '<tr><td width="20%">Emetteur email</td><td>'.$newsletter->email_from_email.'</td></tr>';
	  print '<tr><td width="20%">Email de réponse</td><td>'.$newsletter->email_replyto.'</td></tr>';	  
	  print '<tr><td width="20%">Nom</td><td width="80%">'.$newsletter->email_subject.'</td></tr>';
	  print '<tr><td width="20%" valign="top">Texte</td><td width="80%">'.nl2br($newsletter->email_body).'</td></tr>';

	  print "</table>";

	  if ($newsletter->status == 3)
	    {
	      print "<br />";
	      print '<table class="border" width="100%">';

	      print '<tr><td width="20%">Début de l\'envoi</td><td width="30%">'.strftime("%d %B %Y %H:%M:%S",$newsletter->date_send_begin).'</td>';
	      print '<td width="20%">Nombre de mails envoyés</td><td width="30%">'.$newsletter->nbsent.'</td></tr>';

	      print '<tr><td width="20%">Fin de l\'envoi</td><td width="30%">'.strftime("%d %B %Y %H:%M:%S",$newsletter->date_send_end).'</td>';
	      print '<td width="20%">Nombre de mails en erreur</td><td width="30%">'.$newsletter->nberror.'</td></tr>';
	      print "</table>";
	    }

	}
      else
	{
	  print "Fetch failed";
	}
    

    }
  else
    {
      print "Error";
    }
}

/* ************************************************************************** */
/*                                                                            */ 
/* Barre d'action                                                             */ 
/*                                                                            */ 
/* ************************************************************************** */

print '<div class="tabsAction">';
if ($newsletter->status == 0)
{
  print '<a class="tabAction" href="fiche.php?action=edit&id='.$id.'">'.$langs->trans("Edit").'</a>';
}

if ($newsletter->status == 0 && $id)
{
  print '<a class="tabAction" href="fiche.php?action=valid&id='.$id.'">'.$langs->trans("Valid").'</a>';
}

if ($newsletter->status == 1)
{
  print '<a class="tabAction" href="fiche.php?action=send&id='.$id.'">'.$langs->trans("Send").'</a>';
}

if($id && $newsletter->status == 0)
{
  print '<a class="tabAction" href="fiche.php?action=delete&id='.$id.'">'.$langs->trans("Delete").'</a>';
}
print '</div>';


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
