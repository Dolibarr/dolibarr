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


llxHeader("","","Fiche Mailing");



if ($_POST["action"] == 'add')
{
  $mil = new Mailing($db);

  $mil->from         = $_POST["from"];
  $mil->titre        = $_POST["titre"];
  $mil->sujet        = $_POST["sujet"];
  $mil->body         = $_POST["body"];

  if ($mil->create($user))
    {
      Header("Location: fiche.php?id=".$mil->id);
    }
  else
   {
    $message='<div class="error">'.$mil->error.'</div>';
    $_GET["action"]="create";
   }
}

if ($_POST["action"] == 'update')
{
  $mil = new Mailing($db);

  $mil->id           = $_POST["id"];
  $mil->from         = $_POST["from"];
  $mil->titre        = $_POST["titre"];
  $mil->sujet        = $_POST["sujet"];
  $mil->body         = $_POST["body"];

  if ($mil->update())
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

if ($_POST["action"] == 'confirm_delete')
{
  if ($_POST["confirm"] == 'yes')
    {
      $mil = new Mailing($db);
      $mil->id = $_GET["id"];
    
      if ($mil->delete($mil->id))
        {
          Header("Location: index.php");
        }
    }
}

if ($_POST["cancel"] == $langs->trans("Cancel"))
{
  $action = '';
}



/*
 * Mailing en mode création
 *
 */

$mil = new Mailing($db);

if ($_GET["action"] == 'create')
{
    print '<form action="fiche.php" method="post">'."\n";
    print '<input type="hidden" name="action" value="add">';

    print_titre($langs->trans("NewMailing"));

    if ($message) print "$message<br>";

    print '<table class="border" width="100%">';

    print '<tr><td width="20%">'.$langs->trans("MailFrom").'</td><td><input name="from" size="30" value="'.MAIN_MAIL_FROM.'"></td></tr>';

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
        $head[$h][1] = $langs->trans('MailRecipients');
        $h++;

/*
        $head[$h][0] = DOL_URL_ROOT."/comm/mailing/history.php?id=".$mil->id;
        $head[$h][1] = $langs->trans("MailHistory");
        $h++;
*/
        dolibarr_fiche_head($head, $hselected, $langs->trans("Mailing").": ".substr($mil->titre,0,20));

        // Confirmation de la validation du mailing
        if ($_GET["action"] == 'valide')
        {
            $html->form_confirm("fiche.php?id=".$mil->id,$langs->trans("ValidMailing"),$langs->trans("ConfirmValidMailing"),"confirm_valide");
        }

        // Confirmation de l'approbation du mailing
        if ($_GET["action"] == 'approve')
        {
            $html->form_confirm("fiche.php?id=".$mil->id,"Approuver le mailing","Confirmez-vous l'approbation du mailing ?","confirm_approve");
        }

        // Confirmation de la suppression
        if ($_GET["action"] == 'delete')
        {
            $html->form_confirm("fiche.php?id=".$mil->id,$langs->trans("DeleteAMailing"),$langs->trans("ConfirmDeleteMailing"),"confirm_delete");
        }


        if ($_GET["action"] != 'edit')
        {
            /*
             * Mailing en mode visu
             *
             */

            print '<table class="border" width="100%">';

            print '<tr><td width="25%">'.$langs->trans("MailTitle").'</td><td colspan="3">'.$mil->titre.'</td></tr>';
            print '<tr><td width="25%">'.$langs->trans("MailFrom").'</td><td colspan="3">'.htmlentities($mil->email_from).'</td></tr>';
            print '<tr><td width="25%">'.$langs->trans("TotalNbOfDistinctRecipients").'</td><td colspan="3">'.($mil->nbemail?$mil->nbemail:'<font class="error">'.$langs->trans("NoTargetYet").'</font>').'</td></tr>';
            print '<tr><td width="25%">'.$langs->trans("Status").'</td><td colspan="3">'.$mil->statuts[$mil->statut].'</td></tr>';

            $uc = new User($db, $mil->user_creat);
            $uc->fetch();
            print '<tr><td>'.$langs->trans("CreatedBy").'</td><td>'.$uc->fullname.'</td>';
            print '<td>'.$langs->trans("DateCreation").'</td>';
            print '<td>'.strftime("%d %b %Y %H:%M", $mil->date_creat).'</td></tr>';

            if ($mil->statut > 0)
            {
                $uv = new User($db, $mil->user_valid);
                $uv->fetch();
                print '<tr><td>'.$langs->trans("ValidatedBy").'</td><td>'.$uv->fullname.'</td>';
                print '<td>'.$langs->trans("Date").'</td>';
                print '<td>'.strftime("%d %b %Y %H:%M", $mil->date_valid).'</td></tr>';
            }

            if ($mil->statut > 1)
            {
                $ua = new User($db, $mil->user_appro);
                $ua->fetch();
                print '<tr><td>'.$langs->trans("ApprovedBy").'</td><td>'.$ua->fullname.'</td>';
                print '<td>'.$langs->trans("Date").'</td>';
                print '<td>'.strftime("%d %b %Y %H:%M", $mil->date_appro).'</td></tr>';
            }

            // Contenu du mail
            print '<tr><td>'.$langs->trans("MailTopic").'</td><td colspan="3">'.$mil->sujet.'</td></tr>';
            print '<tr><td valign="top">'.$langs->trans("MailMessage").'</td><td colspan="3">';
            print nl2br($mil->body).'</td></tr>';

            print '</table><br>';

            print "</div>";


            /*
             * Boutons d'action
             */
            if ($_GET["action"] == '')
            {
                print "\n\n<div class=\"tabsAction\">\n";

                if ($mil->statut == 0)
                {
                    print '<a class="tabAction" href="fiche.php?action=edit&amp;id='.$mil->id.'">'.$langs->trans("EditMailing").'</a>';
                }

                //print '<a class="tabAction" href="fiche.php?action=test&amp;id='.$mil->id.'">'.$langs->trans("PreviewMailing").'</a>';

                print '<a class="tabAction" href="fiche.php?action=test&amp;id='.$mil->id.'">'.$langs->trans("TestMailing").'</a>';

                if ($mil->statut == 0 && $mil->nbemail > 0)
                {
                    print '<a class="tabAction" href="fiche.php?action=valide&amp;id='.$mil->id.'">'.$langs->trans("ValidMailing").'</a>';
                }

/*                if ($mil->statut == 1 && $mil->nbemail > 0)
                {
                    print '<a class="tabAction" href="fiche.php?action=approve&amp;id='.$mil->id.'">'.$langs->trans("ApproveMailing").'</a>';
                }
*/
                if ($mil->statut <= 1)
                {
                    print '<a class="butDelete" href="fiche.php?action=delete&amp;id='.$mil->id.'">'.$langs->trans("DeleteMailing").'</a>';
                }

                print '<br /><br /></div>';
            }


            if ($_GET["action"] == 'test')
            {
            	      print_titre($langs->trans("TestMailing"));
            	      
            	      // Créé l'objet formulaire mail
            	      include_once("../../html.formmail.class.php");
            	      $formmail = new FormMail($db);	    
            	      $formmail->fromname = $user->fullname;
            	      $formmail->frommail = $user->email;
                      $formmail->withfrom=1;
                      $formmail->withto=$user->email;
                      $formmail->withcc=0;
                      $formmail->withtopic=0;
                      $formmail->withfile=0;
            	      $formmail->withbody=1;
                      // Tableau des substitutions
                      $formmail->substit["__FACREF__"]=$fac->ref;
                      // Tableau des paramètres complémentaires du post
                      $formmail->param["action"]="send";
                      $formmail->param["models"]=$mil->body;
                      $formmail->param["mailid"]=$mil->id;
                      $formmail->param["returnurl"]=DOL_URL_ROOT."/comm/mailing.php?id=$mil->id";
            
                      $formmail->show_form();
            }

        }
        else
        {
            /*
             * Mailing en mode edition
             */
            print '<form action="fiche.php" method="post">'."\n";
            print '<input type="hidden" name="action" value="update">';
            print '<input type="hidden" name="id" value="'.$mil->id.'">';
            print '<table class="border" width="100%">';

            print '<tr><td width="20%">'.$langs->trans("MailTitle").'</td><td colspan="3"><input type="text" size=50 name="titre" value="'.$mil->titre.'"></td></tr>';
            print '<tr><td width="20%">'.$langs->trans("MailFrom").'</td><td colspan="3"><input type="text" size=50 name="from" value="'.htmlentities($mil->email_from).'"></td></tr>';
            print '<tr><td width="20%">'.$langs->trans("MailTopic").'</td><td colspan="3"><input type="text" size=50 name="sujet" value="'.$mil->sujet.'"></td></tr>';
            print '<tr><td width="20%" valign="top">'.$langs->trans("MailMessage").'</td><td colspan="3"><textarea name="body" cols=50 rows=10>';
            print nl2br($mil->body).'</textarea></td></tr>';

            print '<tr><td colspan="4" align="center"><input type="submit" value="'.$langs->trans("Save").'"></td></tr>';
            print '</table><br>';
            print '</form>';

            print "</div>";
        }
    }

}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
