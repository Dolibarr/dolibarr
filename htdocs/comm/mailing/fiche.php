<?PHP
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005 Laurent Destailleur  <eldy@uers.sourceforge.net>
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

/**     \file       htdocs/comm/mailing/fiche.php
        \brief      Fiche mailing, onglet général
        \version    $Revision$
*/

require("./pre.inc.php");

$langs->load("mails");

$mesg = '';


if ($_POST["action"] == 'add')
{
  $mil = new Mailing($db);

  $mil->titre        = $_POST["titre"];
  $mil->sujet        = $_POST["sujet"];
  $mil->body         = $_POST["body"];


  if ( $mil->create($user) == 0)
    {
      Header("Location: fiche.php?id=".$mil->id);
    }
}


if ($_POST["action"] == 'update')
{
  $mil = new Mailing($db);

  $mil->id           = $_GET["id"];
  $mil->titre        = $_POST["titre"];
  $mil->sujet        = $_POST["sujet"];
  $mil->body         = $_POST["body"];


  if ( $mil->update() == 0)
    {
      Header("Location: fiche.php?id=".$mil->id);
    }
}


if ($_POST["action"] == 'confirm_valide')
{
  
  if ($_POST["confirm"] == 'yes')
    {
      $mil = new Mailing($db);

      if ($mil->fetch($_GET["id"]) == 0)
	{
	  $mil->valid($user);
	  
	  Header("Location: fiche.php?id=".$mil->id);
	}
      else
	{
	  dolibarr_print_error($db);
	}
    }
  else
    {
      Header("Location: fiche.php?id=".$_GET["id"]);
    }
}

if ($_POST["action"] == 'confirm_approve')
{
  
  if ($_POST["confirm"] == 'yes')
    {
      $mil = new Mailing($db);

      if ($mil->fetch($_GET["id"]) == 0)
	{
	  $mil->approve($user);
	  
	  Header("Location: fiche.php?id=".$mil->id);
	}
      else
	{
	  dolibarr_print_error($db);
	}
    }
  else
    {
      Header("Location: fiche.php?id=".$_GET["id"]);
    }
}





llxHeader("","","Fiche Mailing");

if ($_POST["cancel"] == $langs->trans("Cancel"))
{
  $action = '';
}

/*
 * Création
 *
 */

$mil = new Mailing($db);

if ($_GET["action"] == 'create')
{
  print '<form action="fiche.php" method="post">'."\n";
  print '<input type="hidden" name="action" value="add">';

  print_titre($langs->trans("NewMailing"));
      
  print '<table class="border" width="100%">';

  print '<tr><td width="20%">'.$langs->trans("MailTitle").'</td><td><input name="titre" size="30" value=""></td></tr>';

  print '<tr><td width="20%">'.$langs->trans("MailTopic").'</td><td><input name="sujet" size="40" value=""></td></tr>';

  print '<tr><td width="20%" valign="top">'.$langs->trans("MailMessage").'</td><td><textarea cols="30" rows="8" name="body"></textarea></td></tr>';

  print '<tr><td colspan="2" align="center"><input type="submit" value="'.$langs->trans("CreateMailing").'"></td></tr>';
  print '</table>';
  print '</form>';
}
else
{
  $html = new Form($db);
  if ($mil->fetch($_GET["id"]) == 0)
    {

      $h=0;
      $head[$h][0] = DOL_URL_ROOT."/comm/mailing/fiche.php?id=".$mil->id;
      $head[$h][1] = $langs->trans("MailCard");
      $hselected = $h;
      $h++;
      
      $head[$h][0] = DOL_URL_ROOT."/comm/mailing/cibles.php?id=".$mil->id;
      $head[$h][1] = $langs->trans('MailTargets');
      $h++;
      
      dolibarr_fiche_head($head, $hselected, substr($mil->titre,0,20));
      
      
      /*
       * Confirmation de la validation du mailing
       *
       */
      if ($_GET["action"] == 'valide')
	{
	  $html->form_confirm("fiche.php?id=".$mil->id,
			      "Valider le mailing",
			      "Confirmez-vous la validation du mailing ?",
			      "confirm_valide");
	}
	
      /*
       * Confirmation de l'approbation du mailing
       *
       */
      if ($_GET["action"] == 'approve')
	{
	  $html->form_confirm("fiche.php?id=".$mil->id,
			      "Approuver le mailing",
			      "Confirmez-vous l'approbation du mailing ?",
			      "confirm_approve");
	}
      
      print_titre("Mailing");
      
      print '<table class="border" width="100%">';
      
      print '<tr><td width="20%">'.$langs->trans("MailTitle").'</td><td colspan="3">'.$mil->titre.'</td></tr>';
      
      print '<tr><td width="20%">'.$langs->trans("MailSender").'</td><td>'.htmlentities($mil->email_from).'</td>';
      print '<td>'.$langs->trans("EMail").'</td><td>'.htmlentities($mil->email_from).'</td></tr>';
      
      //print '<tr><td width="20%">Réponse</td><td>'.htmlentities($mil->email_replyto).'</td></tr>';
      //print '<tr><td width="20%">Retour Erreur</td><td>'.htmlentities($mil->email_errorsto).'</td></tr>';

      if ($mil->statut > 0)
	{
	  print '<tr><td width="20%">Nb destinataires</td><td colspan="3">'.$mil->nbemail.'</td></tr>';
	}

      print '<tr><td width="20%">'.$langs->trans("Status").'</td><td colspan="3">'.$mil->statuts[$mil->statut].'</td></tr>';

      $uc = new User($db, $mil->user_creat);
      $uc->fetch();
      print '<tr><td width="20%">'.$langs->trans("CreatedBy").'</td><td>'.$uc->fullname.'</td>';
      print '<td>'.$langs->trans("DateCreation").'</td>';
      print '<td>'.strftime("%d %b %Y %H:%M", $mil->date_creat).'</td></tr>';

      if ($mil->statut > 0)
	{
	  $uv = new User($db, $mil->user_valid);
	  $uv->fetch();
	  print '<tr><td width="20%">'.$langs->trans("ValidatedBy").'</td><td>'.$uv->fullname.'</td>';
	  print '<td>'.$langs->trans("Date").'</td>';
	  print '<td>'.strftime("%d %b %Y %H:%M", $mil->date_valid).'</td></tr>';
	}

      if ($mil->statut > 1)
	{
	  $ua = new User($db, $mil->user_appro);
	  $ua->fetch();
	  print '<tr><td width="20%">'.$langs->trans("ApprovedBy").'</td><td>'.$ua->fullname.'</td>';
	  print '<td>'.$langs->trans("Date").'</td>';
	  print '<td>'.strftime("%d %b %Y %H:%M", $mil->date_appro).'</td></tr>';
	}
      
      // Contenu du mail
      print '<tr><td width="20%">'.$langs->trans("MailTopic").'</td><td colspan="3">'.$mil->sujet.'</td></tr>';

      print '<tr><td width="20%" valign="top">'.$langs->trans("MailMessage").'</td><td colspan="3">';
      print nl2br($mil->body).'</td></tr>';


      print '</table><br>';
      
      print "</div>";
      
      
      /*
       * Boutons d'action
       */
      
      print "\n\n<div class=\"tabsAction\">\n";
      
      if ($_GET["action"] == '')
	{
	  
	  print '<a class="tabAction" href="fiche.php?action=test&amp;id='.$mil->id.'">'.$langs->trans("TestMailing").'</a>';
	  
	  if ($mil->statut == 0)
	    {
	      print '<a class="tabAction" href="fiche.php?action=valide&amp;id='.$mil->id.'">'.$langs->trans("ValidMailing").'</a>';
	    }
	  
	  if ($mil->statut == 1 && $mil->nbemail > 0)
	    {
	      print '<a class="tabAction" href="fiche.php?action=approve&amp;id='.$mil->id.'">'.$langs->trans("ApproveMailing").'</a>';
	    }
	  

	}
      print '<br /><br /></div>';
    }
  
}



$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
