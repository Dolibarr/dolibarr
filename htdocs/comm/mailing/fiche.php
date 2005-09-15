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
 */

/**
        \file       htdocs/comm/mailing/fiche.php
        \ingroup    mailing
        \brief      Fiche mailing, onglet général
        \version    $Revision$
*/

require("./pre.inc.php");

$langs->load("mails");

$user->getrights("mailing");

if (! $user->rights->mailing->lire || $user->societe_id > 0)
  accessforbidden();


$message = '';


// Action envoi mailing pour tous
if ($_GET["action"] == 'sendall')
{
    // Pour des raisons de sécurité, on ne permet pas cette fonction via l'IHM,
    // on affiche donc juste un message
    $message='<div class="warning">'.$langs->trans("MailingNeedCommand").'</div>';
    $message.="php ./scripts/mailing-send.php ".$_GET["id"];
    $_GET["action"]='';
}

// Action envoi test mailing
if ($_POST["action"] == 'send')
{
    $mil = new Mailing($db);

    $mil->id           = $_POST["mailid"];
    $mil->fromname     = $_POST["fromname"];
    $mil->frommail     = $_POST["frommail"];
    $mil->sendto       = $_POST["sendto"];
    $mil->titre        = $_POST["titre"];
    $mil->sujet        = $_POST["subject"];
    $mil->body         = $_POST["message"];

    if ($mil->sendto && $mil->sujet && $mil->body)
    {
        require_once(DOL_DOCUMENT_ROOT."/lib/CMailFile.class.php");

        $sendto = $mil->sendto;
        $from = $mil->fromname." <".$mil->frommail.">";
        $arr_file = array();
        $arr_mime = array();
        $arr_name = array();

        $mailfile = new CMailFile($mil->sujet,$sendto,$from,$mil->body,$arr_file,$arr_mime,$arr_name);

        $result=$mailfile->sendfile();

        if($result)
        {
            $message='<div class="ok">'.$langs->trans("MailSuccessfulySent",$from,$sendto).'</div>';
        }
        else
        {
            $message='<div class="error">'.$langs->trans("ResultKo").'</div>';
        }

        $_GET["action"]='';
        $_GET["id"]=$mil->id;
    }
    else
    {
        $message='<div class="error">'.$langs->trans("ErrorUnknown").'</div>';
    }

}

// Action ajout mailing
if ($_POST["action"] == 'add')
{
    $message='';
    
    $mil = new Mailing($db);

    $mil->email_from   = trim($_POST["from"]);
    $mil->titre        = trim($_POST["titre"]);
    $mil->sujet        = trim($_POST["sujet"]);
    $mil->body         = trim($_POST["body"]);

    if (! $mil->titre) $message.=($message?'<br>':'').$langs->trans("ErrorFieldRequired",$langs->trans("MailTitle"));
    if (! $mil->sujet) $message.=($message?'<br>':'').$langs->trans("ErrorFieldRequired",$langs->trans("MailTopic"));

    if (! $message)
    {
        if ($mil->create($user) >= 0)
        {
            Header("Location: fiche.php?id=".$mil->id);
            exit;
        }
        $message=$mil->error;
    }

    $message='<div class="error">'.$message.'</div>';
    $_GET["action"]="create";
}

// Action mise a jour mailing
if ($_POST["action"] == 'update')
{
  $mil = new Mailing($db);

  $mil->id           = $_POST["id"];
  $mil->email_from   = $_POST["from"];
  $mil->titre        = $_POST["titre"];
  $mil->sujet        = $_POST["sujet"];
  $mil->body         = $_POST["body"];

  if ($mil->update())
    {
      Header("Location: fiche.php?id=".$mil->id);
    }
}

// Action confirmation validation
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

// Action confirmation suppression
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



llxHeader("","","Fiche Mailing");


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

    print '<tr><td width="25%">'.$langs->trans("MailFrom").'</td><td><input class="flat" name="from" size="40" value="'.$conf->mailing->email_from.'"></td></tr>';
    print '<tr><td width="25%">'.$langs->trans("MailTitle").'</td><td><input class="flat" name="titre" size="40" value=""></td></tr>';
    print '<tr><td width="25%">'.$langs->trans("MailTopic").'</td><td><input class="flat" name="sujet" size="60" value=""></td></tr>';
    print '<tr><td width="25%" valign="top">'.$langs->trans("MailMessage").'<br>';
    print '<br><i>'.$langs->trans("CommonSubstitutions").':<br>';
    print '__EMAIL__ = '.$langs->trans("EMail").'<br>';
    print '__LASTNAME__ = '.$langs->trans("Lastname").'<br>';
    print '__FIRSTNAME__ = '.$langs->trans("Firstname").'<br>';
    print '</td>';
    print '<td><textarea cols="70" rows="10" name="body"></textarea></td></tr>';
    print '<tr><td colspan="2" align="center"><input type="submit" class="button" value="'.$langs->trans("CreateMailing").'"></td></tr>';
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
            print '<br>';
        }

        // Confirmation de l'approbation du mailing
        if ($_GET["action"] == 'approve')
        {
            $html->form_confirm("fiche.php?id=".$mil->id,"Approuver le mailing","Confirmez-vous l'approbation du mailing ?","confirm_approve");
            print '<br>';
        }

        // Confirmation de la suppression
        if ($_GET["action"] == 'delete')
        {
            $html->form_confirm("fiche.php?id=".$mil->id,$langs->trans("DeleteAMailing"),$langs->trans("ConfirmDeleteMailing"),"confirm_delete");
            print '<br>';
        }


        if ($_GET["action"] != 'edit')
        {
            /*
             * Mailing en mode visu
             *
             */

            print '<table class="border" width="100%">';

            print '<tr><td width="25%">'.$langs->trans("Ref").'</td><td colspan="3">'.$mil->id.'</td></tr>';
            print '<tr><td width="25%">'.$langs->trans("MailTitle").'</td><td colspan="3">'.$mil->titre.'</td></tr>';
            print '<tr><td width="25%">'.$langs->trans("MailFrom").'</td><td colspan="3">'.htmlentities($mil->email_from).'</td></tr>';
            print '<tr><td width="25%">'.$langs->trans("TotalNbOfDistinctRecipients").'</td><td colspan="3">'.($mil->nbemail?$mil->nbemail:'<font class="error">'.$langs->trans("NoTargetYet").'</font>').'</td></tr>';
            print '<tr><td width="25%">'.$langs->trans("Status").'</td><td colspan="3">'.$mil->statuts[$mil->statut].'</td></tr>';

            $uc = new User($db, $mil->user_creat);
            $uc->fetch();
            print '<tr><td>'.$langs->trans("CreatedBy").'</td><td>'.$uc->fullname.'</td>';
            print '<td>'.$langs->trans("Date").'</td>';
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
                print '<tr><td>'.$langs->trans("SentBy").'</td><td>'.$langs->trans("Unknown").'</td>';
                print '<td>'.$langs->trans("Date").'</td>';
                print '<td>'.strftime("%d %b %Y %H:%M", $mil->date_envoi).'</td></tr>';
            }

            // Contenu du mail
            print '<tr><td>'.$langs->trans("MailTopic").'</td><td colspan="3">'.$mil->sujet.'</td></tr>';
            print '<tr><td valign="top">'.$langs->trans("MailMessage").'</td><td colspan="3">';
            print nl2br($mil->body).'</td></tr>';

            print '</table>';

            print "</div>";

            if ($message) print "$message<br>";

            /*
             * Boutons d'action
             */
            if ($_GET["action"] == '')
            {
                print "\n\n<div class=\"tabsAction\">\n";

                if ($mil->statut == 0 && $user->rights->mailing->creer)
                {
                    print '<a class="butAction" href="fiche.php?action=edit&amp;id='.$mil->id.'">'.$langs->trans("EditMailing").'</a>';
                }

                //print '<a class="butAction" href="fiche.php?action=test&amp;id='.$mil->id.'">'.$langs->trans("PreviewMailing").'</a>';

                print '<a class="butAction" href="fiche.php?action=test&amp;id='.$mil->id.'">'.$langs->trans("TestMailing").'</a>';

                if ($mil->statut == 0 && $mil->nbemail > 0 && $user->rights->mailing->valider)
                {
                    print '<a class="butAction" href="fiche.php?action=valide&amp;id='.$mil->id.'">'.$langs->trans("ValidMailing").'</a>';
                }

                if ($mil->statut == 1 && $mil->nbemail > 0 && $user->rights->mailing->valider)
                {
                    print '<a class="butAction" href="fiche.php?action=sendall&amp;id='.$mil->id.'">'.$langs->trans("SendMailing").'</a>';
                }

                if ($mil->statut <= 1 && $user->rights->mailing->supprimer)
                {
                    print '<a class="butActionDelete" href="fiche.php?action=delete&amp;id='.$mil->id.'">'.$langs->trans("DeleteMailing").'</a>';
                }

                print '<br /><br /></div>';
            }


            if ($_GET["action"] == 'test')
            {
            	      print_titre($langs->trans("TestMailing"));
            	      
            	      // Créé l'objet formulaire mail
            	      include_once("../../html.formmail.class.php");
            	      $formmail = new FormMail($db);	    
            	      $formmail->fromname = $mil->email_from;
            	      $formmail->frommail = $mil->email_from;
                      $formmail->withfrom=1;
                      $formmail->withto=$user->email?$user->email:1;
                      $formmail->withcc=0;
                      $formmail->withtopic=$mil->sujet;
                      $formmail->withtopicreadonly=1;
                      $formmail->withfile=0;
            	      $formmail->withbody=$mil->body;
            	      $formmail->withbodyreadonly=1;
                      // Tableau des substitutions
                      $formmail->substit["__FACREF__"]=$fac->ref;
                      // Tableau des paramètres complémentaires du post
                      $formmail->param["action"]="send";
                      $formmail->param["models"]="body";
                      $formmail->param["mailid"]=$mil->id;
                      $formmail->param["returnurl"]=DOL_URL_ROOT."/comm/mailing/fiche.php?id=".$mil->id;
            
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

            print '<tr><td width="25%">'.$langs->trans("Ref").'</td><td colspan="3">'.$mil->id.'</td></tr>';
            print '<tr><td width="25%">'.$langs->trans("MailTitle").'</td><td colspan="3"><input class="flat" type="text" size=40 name="titre" value="'.htmlentities($mil->titre).'"></td></tr>';
            print '<tr><td width="25%">'.$langs->trans("MailFrom").'</td><td colspan="3"><input class="flat" type="text" size=40 name="from" value="'.htmlentities($mil->email_from).'"></td></tr>';
            print '<tr><td width="25%">'.$langs->trans("MailTopic").'</td><td colspan="3"><input class="flat" type="text" size=60 name="sujet" value="'.htmlentities($mil->sujet).'"></td></tr>';
            print '<tr><td width="25%" valign="top">'.$langs->trans("MailMessage").'</td><td colspan="3"><textarea name="body" cols=70 rows=10>';
            print $mil->body.'</textarea></td></tr>';

            print '<tr><td colspan="4" align="center"><input type="submit" class="button" value="'.$langs->trans("Save").'"></td></tr>';
            print '</table>';
            print '</form>';

            print "</div>";
        }
    }

}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
