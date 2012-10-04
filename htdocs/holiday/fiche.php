<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2011      Dimitri Mouillard    <dmouillard@teclib.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

/**
 *   	\file       fiche.php
 *		\ingroup    holiday
 *		\brief      Form and file creation of paid holiday.
 */

require('../main.inc.php');
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/holiday/common.inc.php';

// Get parameters
$myparam = GETPOST("myparam");
$action=GETPOST('action');
$id=GETPOST('id');

// Protection if external user
if ($user->societe_id > 0) accessforbidden();

$user_id = $user->id;



/*******************************************************************
 * Actions
********************************************************************/

// Si création de la demande
if ($action == 'create')
{

    // Si pas le droit de créer une demande
    if(!$user->rights->holiday->write)
    {
        header('Location: fiche.php?action=request&error=CantCreate');
        exit;
    }

    $date_debut = dol_mktime(0, 0, 0, $_POST['date_debut_month'], $_POST['date_debut_day'], $_POST['date_debut_year']);
    $date_fin = dol_mktime(0, 0, 0, $_POST['date_fin_month'], $_POST['date_fin_day'], $_POST['date_fin_year']);

    $valideur = GETPOST('valideur');
    $description = trim(GETPOST('description'));
    $userID = GETPOST('userID');

    // Si pas de date de début
    if (empty($date_debut))
    {
        header('Location: fiche.php?action=request&error=nodatedebut');
        exit;
    }

    // Si pas de date de fin
    if (empty($date_fin))
    {
        header('Location: fiche.php?action=request&error=nodatefin');
        exit;
    }

    $testDateDebut = $date_debut;
    $testDateFin = $date_fin;

    // Si date de début après la date de fin
    if($testDateDebut > $testDateFin)
    {
        header('Location: fiche.php?action=request&error=datefin');
        exit;
    }

    $cp = new Holiday($db);

    $verifCP = $cp->verifDateHolidayCP($userID,$date_debut,$date_fin);

    // On vérifie si il n'y a pas déjà des congés payés sur cette période
    if (! $verifCP)
    {
        header('Location: fiche.php?action=request&error=alreadyCP');
        exit;
    }

    // Si aucun jours ouvrés dans la demande
    $nbopenedday=num_open_day($testDateDebut,$testDateFin,0,1);
    if($nbopenedday < 1)
    {
        header('Location: fiche.php?action=request&error=DureeHoliday');
        exit;
    }

    // Si pas de validateur choisi
    if ($valideur < 1)
    {
        header('Location: fiche.php?action=request&error=Valideur');
        exit;
    }

    $cp->fk_user = $user_id;
    $cp->description = $description;
    $cp->date_debut = $date_debut;
    $cp->date_fin = $date_fin;
    $cp->fk_validator = $valideur;

    $verif = $cp->create($user_id);

    // Si pas d'erreur SQL on redirige vers la fiche de la demande
    if ($verif > 0)
    {
        header('Location: fiche.php?id='.$verif);
        exit;
    }
    else
    {
        // Sinon on affiche le formulaire de demande avec le message d'erreur SQL
        header('Location: fiche.php?action=request&error=SQL_Create&msg='.$cp->error);
        exit;
    }

}

if ($action == 'update')
{
	$date_debut = dol_mktime(0, 0, 0, $_POST['date_debut_month'], $_POST['date_debut_day'], $_POST['date_debut_year']);
	$date_fin = dol_mktime(0, 0, 0, $_POST['date_fin_month'], $_POST['date_fin_day'], $_POST['date_fin_year']);

    // Si pas le droit de modifier une demande
    if(!$user->rights->holiday->write)
    {
        header('Location: fiche.php?action=request&error=CantUpdate');
        exit;
    }

    $cp = new Holiday($db);
    $cp->fetch($_POST['holiday_id']);

    // Si en attente de validation
    if ($cp->statut == 1)
    {
        // Si c'est le créateur ou qu'il a le droit de tout lire / modifier
        if ($user->id == $cp->fk_user || $user->rights->holiday->lire_tous)
        {
            $valideur = $_POST['valideur'];
            $description = trim($_POST['description']);

            // Si pas de date de début
            if (empty($_POST['date_debut_'])) {
                header('Location: fiche.php?id='.$_POST['holiday_id'].'&action=edit&error=nodatedebut');
                exit;
            }

            // Si pas de date de fin
            if (empty($_POST['date_fin_'])) {
                header('Location: fiche.php?id='.$_POST['holiday_id'].'&action=edit&error=nodatefin');
                exit;
            }

            $testDateDebut = $date_debut;
            $testDateFin = $date_fin;

            // Si date de début après la date de fin
            if ($testDateDebut > $testDateFin) {
                header('Location: fiche.php?id='.$_POST['holiday_id'].'&action=edit&error=datefin');
                exit;
            }

            // Si pas de valideur choisi
            if ($valideur < 1) {
                header('Location: fiche.php?id='.$_POST['holiday_id'].'&action=edit&error=Valideur');
                exit;
            }

            // Si pas de jours ouvrés dans la demande
            $nbopenedday=num_open_day($testDateDebut,$testDateFin,0,1);
            if ($nbopenedday < 1)
            {
                header('Location: fiche.php?id='.$_POST['holiday_id'].'&action=edit&error=DureeHoliday');
                exit;
            }

            $cp->description = $description;
            $cp->date_debut = $date_debut;
            $cp->date_fin = $date_fin;
            $cp->fk_validator = $valideur;

            $verif = $cp->update($user->id);

            // Si pas d'erreur SQL on redirige vers la fiche de la demande
            if ($verif > 0)
            {
                header('Location: fiche.php?id='.$_POST['holiday_id']);
                exit;
            }
            else
           {
                // Sinon on affiche le formulaire de demande avec le message d'erreur SQL
                header('Location: fiche.php?id='.$_POST['holiday_id'].'&action=edit&error=SQL_Create&msg='.$cp->error);
                exit;
            }
        }
    } else {
        header('Location: fiche.php?id='.$_POST['holiday_id']);
        exit;
    }
}

// Si suppression de la demande
if ($action == 'confirm_delete'  && $_GET['confirm'] == 'yes')
{
    if($user->rights->holiday->delete)
    {
        $cp = new Holiday($db);
        $cp->fetch($_GET['id']);

        // Si c'est bien un brouillon
        if($cp->statut == 1) {
            // Si l'utilisateur à le droit de lire cette demande, il peut la supprimer
            if($user->id == $cp->fk_user || $user->rights->holiday->lire_tous) {
                $cp->delete($_GET['id']);
                header('Location: index.php');
                exit;
            }
            else {
                $error = $langs->trans('ErrorCantDeleteCP');
            }
        }
    }
}

// Si envoi de la demande
if ($action == 'confirm_send')
{
    $cp = new Holiday($db);
    $cp->fetch($_GET['id']);

    $userID = $user->id;

    // Si brouillon et créateur
    if($cp->statut == 1 && $userID == $cp->fk_user)
    {
        $cp->statut = 2;

        $verif = $cp->update($user->id);

        // Si pas d'erreur SQL on redirige vers la fiche de la demande
        if($verif > 0) {

            // A
            $destinataire = new User($db);
            $destinataire->fetch($cp->fk_validator);
            $emailTo = $destinataire->email;

            // De
            $expediteur = new User($db);
            $expediteur->fetch($cp->fk_user);
            $emailFrom = $expediteur->email;

            // Sujet
            if($conf->global->MAIN_APPLICATION_TITLE != NULL) {
                $societeName = addslashes($conf->global->MAIN_APPLICATION_TITLE);
            } else {
                $societeName = addslashes($conf->global->MAIN_INFO_SOCIETE_NOM);
            }

            $subject = stripslashes($societeName)." - Demande de congés payés à valider";

            // Contenu
            $message = "Bonjour {$destinataire->prenom},\n\n";
            $message.= "Veuillez trouver ci-dessous une demande de congés payés à valider.\n";

            $delayForRequest = $cp->getConfCP('delayForRequest');
            //$delayForRequest = $delayForRequest * (60*60*24);

            $now=dol_now();
            $nextMonth = dol_time_plus_duree($now, $delayForRequest, 'd');

            // Si l'option pour avertir le valideur en cas de délai trop court
            if($cp->getConfCP('AlertValidatorDelay')) {
                if($cp->date_debut < $nextMonth) {
                    $message.= "\n";
                    $message.= "Cette demande de congés payés à été effectué dans un";
                    $message.= " délai de moins de ".$cp->getConfCP('delayForRequest')." jours avant ceux-ci.\n";
                }
            }

            // Si l'option pour avertir le valideur en cas de solde inférieur à la demande
            if($cp->getConfCP('AlertValidatorSolde')) {
            	$nbopenedday=num_open_day($cp->date_debut,$cp->date_fin,0,1);
                if ($nbopenedday > $cp->getCPforUser($cp->fk_user))
                {
                    $message.= "\n";
                    $message.= "L'utilisateur ayant fait cette demande de congés payés n'a pas le solde requis.\n";
                }
            }

            $message.= "\n";
            $message.= "- Demandeur : {$expediteur->prenom} {$expediteur->nom}\n";
            $message.= "- Période : du ".date('d/m/Y',strtotime($cp->date_debut))." au ".date('d/m/Y',strtotime($cp->date_fin))."\n";
            $message.= "- Lien : {$dolibarr_main_url_root}/holiday/fiche.php?id={$cp->rowid}\n\n";
            $message.= "Bien cordialement,\n".$societeName;

            $mail = new CMailFile($subject,$emailTo,$emailFrom,$message);

            // Envoi du mail
            $result=$mail->sendfile();

            if(!$result) {
                header('Location: fiche.php?id='.$_GET['id'].'&error=mail&error_content='.$mail->error);
                exit;
            }

            header('Location: fiche.php?id='.$_GET['id']);
            exit;
        } else {
            // Sinon on affiche le formulaire de demande avec le message d'erreur SQL
            header('Location: fiche.php?id='.$_GET['id'].'&error=SQL_Create&msg='.$cp->error);
            exit;
        }
    }
}


// Si Validation de la demande
if($action == 'confirm_valid')
{
    $cp = new Holiday($db);
    $cp->fetch($_GET['id']);

    $userID = $user->id;

    // Si statut en attente de validation et valideur = utilisateur
    if($cp->statut == 2 && $userID == $cp->fk_validator)
    {

        $cp->date_valid = dol_now();
        $cp->fk_user_valid = $user->id;
        $cp->statut = 3;

        $verif = $cp->update($user->id);

        // Si pas d'erreur SQL on redirige vers la fiche de la demande
        if($verif > 0) {

            // Retrait du nombre de jours prit
            $nbJour = $nbopenedday=num_open_day($cp->date_debut,$cp->date_fin,0,1);

            $soldeActuel = $cp->getCpforUser($cp->fk_user);
            $newSolde = $soldeActuel - ($nbJour*$cp->getConfCP('nbHolidayDeducted'));

            // On ajoute la modification dans le LOG
            $cp->addLogCP($userID,$cp->fk_user,'Event : Prise de congés payés',$newSolde);

            // Mise à jour du solde
            $cp->updateSoldeCP($cp->fk_user,$newSolde);

            // A
            $destinataire = new User($db);
            $destinataire->fetch($cp->fk_user);
            $emailTo = $destinataire->email;

            // De
            $expediteur = new User($db);
            $expediteur->fetch($cp->fk_validator);
            $emailFrom = $expediteur->email;

            // Sujet
            if($conf->global->MAIN_APPLICATION_TITLE != NULL) {
                $societeName = addslashes($conf->global->MAIN_APPLICATION_TITLE);
            } else {
                $societeName = addslashes($conf->global->MAIN_INFO_SOCIETE_NOM);
            }

            $subject = stripslashes($societeName)." - Demande de congés payés validée";

            // Contenu
            $message = "Bonjour {$destinataire->prenom},\n\n";
            $message.= "Votre demande de congés payés du ".$cp->date_debut." au ".$cp->date_fin." vient d'être validée!\n";
            $message.= "- Valideur : {$expediteur->prenom} {$expediteur->nom}\n";
            $message.= "- Lien : {$dolibarr_main_url_root}/holiday/fiche.php?id={$cp->rowid}\n\n";
            $message.= "Bien cordialement,\n".$societeName;


            $mail = new CMailFile($subject,$emailTo,$emailFrom,$message);

            // Envoi du mail
            $result=$mail->sendfile();

            if(!$result) {
                header('Location: fiche.php?id='.$_GET['id'].'&error=mail&error_content='.$mail->error);
                exit;
            }

            header('Location: fiche.php?id='.$_GET['id']);
            exit;
        } else {
            // Sinon on affiche le formulaire de demande avec le message d'erreur SQL
            header('Location: fiche.php?id='.$_GET['id'].'&error=SQL_Create&msg='.$cp->error);
            exit;
        }

    }

}

if ($action == 'confirm_refuse')
{
    if(!empty($_POST['detail_refuse']))
    {
        $cp = new Holiday($db);
        $cp->fetch($_GET['id']);

        $userID = $user->id;

        // Si statut en attente de validation et valideur = utilisateur
        if($cp->statut == 2 && $userID == $cp->fk_validator) {

            $cp->date_refuse = date('Y-m-d H:i:s', time());
            $cp->fk_user_refuse = $user->id;
            $cp->statut = 5;
            $cp->detail_refuse = $_POST['detail_refuse'];

            $verif = $cp->update($user->id);

            // Si pas d'erreur SQL on redirige vers la fiche de la demande
            if($verif > 0) {

                // A
                $destinataire = new User($db);
                $destinataire->fetch($cp->fk_user);
                $emailTo = $destinataire->email;

                // De
                $expediteur = new User($db);
                $expediteur->fetch($cp->fk_validator);
                $emailFrom = $expediteur->email;

                // Sujet
                if($conf->global->MAIN_APPLICATION_TITLE != NULL) {
                    $societeName = addslashes($conf->global->MAIN_APPLICATION_TITLE);
                } else {
                    $societeName = addslashes($conf->global->MAIN_INFO_SOCIETE_NOM);
                }

                $subject = stripslashes($societeName)." - Demande de congés payés refusée";

                // Contenu
                $message = "Bonjour {$destinataire->prenom},\n\n";
                $message.= "Votre demande de congés payés ".$cp->date_debut." au ".$cp->date_fin." vient d'être refusée pour le motif suivant :\n";
                $message.= $_POST['detail_refuse']."\n\n";
                $message.= "- Valideur : {$expediteur->prenom} {$expediteur->nom}\n";
                $message.= "- Lien : {$dolibarr_main_url_root}/holiday/fiche.php?id={$cp->rowid}\n\n";
                $message.= "Bien cordialement,\n".$societeName;


                $mail = new CMailFile($subject,$emailTo,$emailFrom,$message);

                // Envoi du mail
                $result=$mail->sendfile();

                if(!$result) {
                    header('Location: fiche.php?id='.$_GET['id'].'&error=mail&error_content='.$mail->error);
                    exit;
                }

                header('Location: fiche.php?id='.$_GET['id']);
                exit;
            } else {
                // Sinon on affiche le formulaire de demande avec le message d'erreur SQL
                header('Location: fiche.php?id='.$_GET['id'].'&error=SQL_Create&msg='.$cp->error);
                exit;
            }

        }

    } else {
        header('Location: fiche.php?id='.$_GET['id'].'&error=NoMotifRefuse');
        exit;
    }
}

// Si Validation de la demande
if ($action == 'confirm_cancel' && $_GET['confirm'] == 'yes')
{
    $cp = new Holiday($db);
    $cp->fetch($_GET['id']);

    $userID = $user->id;

    // Si statut en attente de validation et valideur = utilisateur
    if($cp->statut == 2 && $userID == $cp->fk_validator)
    {
        $cp->date_cancel = date('Y-m-d H:i:s', time());
        $cp->fk_user_cancel = $user->id;
        $cp->statut = 4;

        $verif = $cp->update($user->id);

        // Si pas d'erreur SQL on redirige vers la fiche de la demande
        if($verif > 0)
        {
            // A
            $destinataire = new User($db);
            $destinataire->fetch($cp->fk_user);
            $emailTo = $destinataire->email;

            // De
            $expediteur = new User($db);
            $expediteur->fetch($cp->fk_validator);
            $emailFrom = $expediteur->email;

            // Sujet
            if($conf->global->MAIN_APPLICATION_TITLE != NULL) {
                $societeName = addslashes($conf->global->MAIN_APPLICATION_TITLE);
            } else {
                $societeName = addslashes($conf->global->MAIN_INFO_SOCIETE_NOM);
            }

            $subject = stripslashes($societeName)."- Demande de congés payés annulée";

            // Contenu
            $message = "Bonjour {$destinataire->prenom},\n\n";
            $message.= "Votre demande de congés payés ".$cp->date_debut." au ".$cp->date_fin." vient d'être annulée !\n";
            $message.= "- Valideur : {$expediteur->prenom} {$expediteur->nom}\n";
            $message.= "- Lien : {$dolibarr_main_url_root}/holiday/fiche.php?id={$cp->rowid}\n\n";
            $message.= "Bien cordialement,\n".$societeName;


            $mail = new CMailFile($subject,$emailTo,$emailFrom,$message);

            // Envoi du mail
            $result=$mail->sendfile();

            if(!$result)
            {
                header('Location: fiche.php?id='.$_GET['id'].'&error=mail&error_content='.$mail->error);
                exit;
            }

            header('Location: fiche.php?id='.$_GET['id']);
            exit;
        }
        else
        {
            // Sinon on affiche le formulaire de demande avec le message d'erreur SQL
            header('Location: fiche.php?id='.$_GET['id'].'&error=SQL_Create&msg='.$cp->error);
            exit;
        }

    }

}



/***************************************************
 * View
****************************************************/

llxHeader(array(),$langs->trans('CPTitreMenu'));

if (empty($id) || $action == 'add' || $action == 'request')
{
    // Si l'utilisateur n'a pas le droit de faire une demande
    if(!$user->rights->holiday->write)
    {
        $errors[]=$langs->trans('CantCreateCP');
    }
    else
    {
        // Formulaire de demande de congés payés
        print_fiche_titre($langs->trans('MenuAddCP'));

        // Si il y a une erreur
        if (GETPOST('error')) {

            switch(GETPOST('error')) {
                case 'datefin' :
                    $errors[] = $langs->trans('ErrorEndDateCP');
                    break;
                case 'SQL_Create' :
                    $errors[] = $langs->trans('ErrorSQLCreateCP').' <b>'.htmlentities($_GET['msg']).'</b>';
                    break;
                case 'CantCreate' :
                    $errors[] = $langs->trans('CantCreateCP');
                    break;
                case 'Valideur' :
                    $errors[] = $langs->trans('InvalidValidatorCP');
                    break;
                case 'nodatedebut' :
                    $errors[] = $langs->trans('NoDateDebut');
                    break;
                case 'nodatedebut' :
                    $errors[] = $langs->trans('NoDateFin');
                    break;
                case 'DureeHoliday' :
                    $errors[] = $langs->trans('ErrorDureeCP');
                    break;
                case 'alreadyCP' :
                    $errors[] = $langs->trans('alreadyCPexist');
                    break;
            }

            dol_htmloutput_mesg('',$errors,'error');
        }

        $html = new Form($db);
        $cp = new Holiday($db);

        $delayForRequest = $cp->getConfCP('delayForRequest');
        //$delayForRequest = $delayForRequest * (60*60*24);

        $nextMonth = dol_time_plus_duree($now, $delayForRequest, 'd');

        print '<script type="text/javascript">
       //<![CDATA[

       function valider(){
         if(document.demandeCP.date_debut_.value != "") {

            if(document.demandeCP.date_fin_.value != "") {

               if(document.demandeCP.valideur.value != "-1") {
                 return true;
               }
               else {
                 alert("'.dol_escape_js($langs->transnoentities('InvalidValidatorCP')).'");
                 return false;
               }

            }
            else {
              alert("'.dol_escape_js($langs->transnoentities('NoDateFin')).'");
              return false;
            }
         }

         else {
           alert("'.dol_escape_js($langs->transnoentities('NoDateDebut')).'");
           return false;
         }
       }

       //]]>
       </script>'."\n";

        // Formulaire de demande
        print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'" onsubmit="return valider()" name="demandeCP">'."\n";
        print '<input type="hidden" name="action" value="create" />'."\n";
        print '<input type="hidden" name="userID" value="'.$user_id.'" />'."\n";
        print '<div class="tabBar">';
        print '<span>'.$langs->trans('DelayToRequestCP',$cp->getConfCP('delayForRequest')).'</span><br /><br />';

        $nb_holiday = $cp->getCPforUser($user->id) / $cp->getConfCP('nbHolidayDeducted');

        print '<span>'.$langs->trans('SoldeCPUser', round($nb_holiday,0)).'</span><br /><br />';
        print '<table class="border" width="100%">';
        print '<tbody>';
        print '<tr>';
        print '<td class="fieldrequired">'.$langs->trans("DateDebCP").' ('.$langs->trans("FirstDayOfHoliday").')</td>';
        print '<td>';
        // Si la demande ne vient pas de l'agenda
        if(!isset($_GET['datep'])) {
            $html->select_date(-1,'date_debut_');
        } else {
            $tmpdate = dol_mktime(0, 0, 0, GETPOST('datepmonth'), GETPOST('datepday'), GETPOST('datepyear'));
            $html->select_date($tmpdate,'date_debut_');
        }
        print '</td>';
        print '</tr>';
        print '<tr>';
        print '<td class="fieldrequired">'.$langs->trans("DateFinCP").' ('.$langs->trans("LastDayOfHoliday").')</td>';
        print '<td>';
        // Si la demande ne vient pas de l'agenda
        if(!isset($_GET['datep'])) {
            $html->select_date(-1,'date_fin_');
        } else {
            $tmpdate = dol_mktime(0, 0, 0, GETPOST('datefmonth'), GETPOST('datefday'), GETPOST('datefyear'));
            $html->select_date($tmpdate,'date_fin_');
        }
        print '</td>';
        print '</tr>';
        print '<tr>';
        print '<td class="fieldrequired">'.$langs->trans("ValidateByCP").'</td>';
        // Liste des utiliseurs du groupes choisi dans la config
        $idGroupValid = $cp->getConfCP('userGroup');

        $validator = new UserGroup($db,$idGroupValid);
        $valideurarray = $validator->listUsersForGroup();

        print '<td>';
        print $html->select_dolusers($valideur,"valideur",1,"",0,$valideurarray,'');
        print '</td>';
        print '</tr>';
        print '<tr>';
        print '<td>'.$langs->trans("DescCP").'</td>';
        print '<td>';
        print '<textarea name="description" class="flat" rows="'.ROWS_3.'" cols="70"></textarea>';
        print '</td>';
        print '</tr>';
        print '</tbody>';
        print '</table>';
        print '<div style="clear: both;"></div>';
        print '</div>';
        print '</from>'."\n";

        print '<center>';
        print '<input type="submit" value="'.$langs->trans("SendRequestCP").'" name="bouton" class="button">';
        print '&nbsp; &nbsp; ';
        print '<input type="button" value="'.$langs->trans("Cancel").'" class="button" onclick="history.go(-1)">';
        print '</center>';
    }

}
else
{
    if ($error)
    {
        print '<div class="tabBar">';
        print $error;
        print '<br /><br /><input type="button" value="'.$langs->trans("ReturnCP").'" class="button" onclick="history.go(-1)" />';
        print '</div>';
    }
    else
    {
        // Affichage de la fiche d'une demande de congés payés
        if ($id > 0)
        {
            $cp = new Holiday($db);
            $cp->fetch($id);

            $valideur = new User($db);
            $valideur->fetch($cp->fk_validator);

            $userRequest = new User($db);
            $userRequest->fetch($cp->fk_user);

            // Utilisateur connecté
            $userID = $user->id;

            //print_fiche_titre($langs->trans('TitreRequestCP'));

            // Si il y a une erreur
            if (GETPOST('error')) {

                switch(GETPOST('error')) {
                    case 'datefin' :
                        $errors[] = $langs->trans('ErrorEndDateCP');
                        break;
                    case 'SQL_Create' :
                        $errors[] = $langs->trans('ErrorSQLCreateCP').' <b>'.htmlentities($_GET['msg']).'</b>';
                        break;
                    case 'CantCreate' :
                        $errors[] = $langs->trans('CantCreateCP');
                        break;
                    case 'Valideur' :
                        $errors[] = $langs->trans('InvalidValidatorCP');
                        break;
                    case 'nodatedebut' :
                        $errors[] = $langs->trans('NoDateDebut');
                        break;
                    case 'nodatedebut' :
                        $errors[] = $langs->trans('NoDateFin');
                        break;
                    case 'DureeHoliday' :
                        $errors[] = $langs->trans('ErrorDureeCP');
                        break;
                    case 'NoMotifRefuse' :
                        $errors[] = $langs->trans('NoMotifRefuseCP');
                        break;
                    case 'mail' :
                        $errors[] = $langs->trans('ErrorMailNotSend').'<br /><b>'.$_GET['error_content'].'</b>';
                        break;
                }

                dol_htmloutput_mesg('',$errors,'error');
            }

            // On vérifie si l'utilisateur à le droit de lire cette demande
            if($user->id == $cp->fk_user || $user->rights->holiday->lire_tous)
            {

                if ($action == 'delete' && $cp->statut == 1) {
                    if($user->rights->holiday->delete) {
                        $html = new Form($db);

                        $ret=$html->form_confirm("fiche.php?id=".$_GET['id'],$langs->trans("TitleDeleteCP"),$langs->trans("ConfirmDeleteCP"),"confirm_delete", '', 0, 1);
                        if ($ret == 'html') print '<br />';
                    }
                }

                // Si envoi en validation
                if ($action == 'sendToValidate' && $cp->statut == 1 && $userID == $cp->fk_user) {
                    $html = new Form($db);

                    $ret=$html->form_confirm("fiche.php?id=".$_GET['id'],$langs->trans("TitleToValidCP"),$langs->trans("ConfirmToValidCP"),"confirm_send", '', 0, 1);
                    if ($ret == 'html') print '<br />';
                }

                // Si validation de la demande
                if ($action == 'valid' && $cp->statut == 2 && $userID == $cp->fk_validator) {
                    $html = new Form($db);

                    $ret=$html->form_confirm("fiche.php?id=".$_GET['id'],$langs->trans("TitleValidCP"),$langs->trans("ConfirmValidCP"),"confirm_valid", '', 0, 1);
                    if ($ret == 'html') print '<br />';
                }

                // Si refus de la demande
                if ($action == 'refuse' && $cp->statut == 2 && $userID == $cp->fk_validator) {
                    $html = new Form($db);

                    $array_input = array(array('type'=>"text",'label'=>"Entrez ci-dessous un motif de refus :",'name'=>"detail_refuse",'size'=>"50",'value'=>""));
                    $ret=$html->form_confirm("fiche.php?id=".$_GET['id']."&action=confirm_refuse",$langs->trans("TitleRefuseCP"),"","confirm_refuse",$array_input,"",0);
                    if ($ret == 'html') print '<br />';
                }

                // Si annulation de la demande
                if ($action == 'cancel' && $cp->statut == 2 && $userID == $cp->fk_validator) {
                    $html = new Form($db);

                    $ret=$html->form_confirm("fiche.php?id=".$_GET['id'],$langs->trans("TitleCancelCP"),$langs->trans("ConfirmCancelCP"),"confirm_cancel", '', 0, 1);
                    if ($ret == 'html') print '<br />';
                }


                dol_fiche_head(array(),'card',$langs->trans("CPTitreMenu"),0,'holiday');


                if ($action == 'edit' && $user->id == $cp->fk_user && $cp->statut == 1)
                {
                    $edit = true;
                    print '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$_GET['id'].'">'."\n";
                    print '<input type="hidden" name="action" value="update"/>'."\n";
                    print '<input type="hidden" name="holiday_id" value="'.$_GET['id'].'" />'."\n";

                    $html = new Form($db);
                }

                print '<table class="border" width="100%">';
                print '<tbody>';
                /*print '<tr class="liste_titre">';
                print '<td colspan="2">'.$langs->trans("InfosCP").'</td>';
                print '</tr>';*/

                print '<tr>';
                print '<td width="25%">'.$langs->trans("Ref").'</td>';
                print '<td>'.$cp->rowid.'</td>';
                print '</tr>';

                if(!$edit) {
                    print '<tr>';
                    print '<td>'.$langs->trans('DateDebCP').'</td>';
                    print '<td>'.dol_print_date($cp->date_debut,'day').'</td>';
                    print '</tr>';
                } else {
                    print '<tr>';
                    print '<td>'.$langs->trans('DateDebCP').'</td>';
                    print '<td>';
                    $html->select_date($cp->date_debut,'date_debut_');
                    print '</td>';
                    print '</tr>';
                }

                if(!$edit) {
                    print '<tr>';
                    print '<td>'.$langs->trans('DateFinCP').'</td>';
                    print '<td>'.dol_print_date($cp->date_fin,'day').'</td>';
                    print '</tr>';
                } else {
                    print '<tr>';
                    print '<td>'.$langs->trans('DateFinCP').'</td>';
                    print '<td>';
                    $html->select_date($cp->date_fin,'date_fin_');
                    print '</td>';
                    print '</tr>';
                }
                print '<tr>';
                print '<td>'.$langs->trans('NbUseDaysCP').'</td>';
                print '<td>'.num_open_day($cp->date_debut,$cp->date_fin,0,1).'</td>';
                print '</tr>';

                // Status
                print '<tr>';
                print '<td>'.$langs->trans('StatutCP').'</td>';
                print '<td><b>'.$cp->getStatutCP($cp->statut).'</b></td>';
                print '</tr>';
                if($cp->statut == 5) {
                	print '<tr>';
                	print '<td>'.$langs->trans('DetailRefusCP').'</td>';
                	print '<td>'.$cp->detail_refuse.'</td>';
                	print '</tr>';
                }

                // Description
                if(!$edit) {
                    print '<tr>';
                    print '<td>'.$langs->trans('DescCP').'</td>';
                    print '<td>'.nl2br($cp->description).'</td>';
                    print '</tr>';
                } else {
                    print '<tr>';
                    print '<td>'.$langs->trans('DescCP').'</td>';
                    print '<td><textarea name="description" class="flat" rows="'.ROWS_3.'" cols="70">'.$cp->description.'</textarea></td>';
                    print '</tr>';
                }
                print '</tbody>';
                print '</table>'."\n";

                print '<br><br>';


                print '<table class="border" width="50%">'."\n";
                print '<tbody>';
                print '<tr class="liste_titre">';
                print '<td colspan="2">'.$langs->trans("InfosWorkflowCP").'</td>';
                print '</tr>';

                print '<tr>';
                print '<td>'.$langs->trans('RequestByCP').'</td>';
                print '<td>'.$userRequest->getNomUrl(1).'</td>';
                print '</tr>';

                if(!$edit) {
                    print '<tr>';
                    print '<td width="50%">'.$langs->trans('ValidateByCP').'</td>';
                    print '<td>'.$valideur->getNomUrl(1).'</td>';
                    print '</tr>';
                } else {
                    print '<tr>';
                    print '<td width="50%">'.$langs->trans('ValidateByCP').'</td>';
                    // Liste des utiliseurs du groupes choisi dans la config
                    $idGroupValid = $cp->getConfCP('userGroup');

                    $validator = new UserGroup($db,$idGroupValid);
                    $valideur = $validator->listUsersForGroup();

                    print '<td>';
                    $html->select_users($cp->fk_validator,"valideur",1,"",0,$valideur,'');
                    print '</td>';
                    print '</tr>';
                }

                print '<tr>';
                print '<td>'.$langs->trans('DateCreateCP').'</td>';
                print '<td>'.dol_print_date($cp->date_create,'dayhour').'</td>';
                print '</tr>';
                if($cp->statut == 3) {
                    print '<tr>';
                    print '<td>'.$langs->trans('DateValidCP').'</td>';
                    print '<td>'.dol_print_date($cp->date_valid,'dayhour').'</td>';
                    print '</tr>';
                }
                if($cp->statut == 4) {
                    print '<tr>';
                    print '<td>'.$langs->trans('DateCancelCP').'</td>';
                    print '<td>'.dol_print_date($cp->date_cancel,'dayhour').'</td>';
                    print '</tr>';
                }
                if($cp->statut == 5) {
                    print '<tr>';
                    print '<td>'.$langs->trans('DateRefusCP').'</td>';
                    print '<td>'.dol_print_date($cp->date_refuse,'dayhour').'</td>';
                    print '</tr>';
                }
                print '</tbody>';
                print '</table>';

                dol_fiche_end();

                print '<div style="clear: both;"></div>'."\n";

                if ($edit)
                {
                    print '<center>';
                    if($user->rights->holiday->write && $_GET['action'] == 'edit' && $cp->statut == 1)
                    {
                        print '<input type="submit" value="'.$langs->trans("UpdateButtonCP").'" class="button">';
                    }
                    print '</center>';

                    print '</form>';
                }

                if (! $edit)
                {
                    print '<br />';
                    print '<div style="float: right;">'."\n";

                    // Boutons d'actions

                    if($user->rights->holiday->write && $_GET['action'] != 'edit' && $cp->statut == 1) {
                        print '<a href="fiche.php?id='.$_GET['id'].'&action=edit" class="butAction" style="float: left;">'.$langs->trans("EditCP").'</a>';
                    }
                    if($user->rights->holiday->delete && $cp->statut == 1) {
                        print '<a href="fiche.php?id='.$_GET['id'].'&action=delete" class="butAction" style="float: left;">'.$langs->trans("DeleteCP").'</a>';
                    }
                    if($user->id == $cp->fk_user && $cp->statut == 1) {
                        print '<a href="fiche.php?id='.$_GET['id'].'&action=sendToValidate" class="butAction" style="float: left;">'.$langs->trans("SendToValidationCP").'</a>';
                    }

                    // Si le statut est en attente de validation et que le valideur est connecté
                    if($userID == $cp->fk_validator && $cp->statut == 2) {
                        print '<a href="fiche.php?id='.$_GET['id'].'&action=valid" class="butAction" style="float: left;">'.$langs->trans("ActionValidCP").'</a>';
                        print '<a href="fiche.php?id='.$_GET['id'].'&action=refuse" class="butAction" style="float: left;">'.$langs->trans("ActionRefuseCP").'</a>';
                        print '<a href="fiche.php?id='.$_GET['id'].'&action=cancel" class="butAction" style="float: left;">'.$langs->trans("ActionCancelCP").'</a>';
                    }

                    print '</div>';
                }

            } else {
                print '<div class="tabBar">';
                print $langs->trans('ErrorUserViewCP');
                print '<br /><br /><input type="button" value="'.$langs->trans("ReturnCP").'" class="button" onclick="history.go(-1)" />';
                print '</div>';
            }

        } else {
            print '<div class="tabBar">';
            print $langs->trans('ErrorIDFicheCP');
            print '<br /><br /><input type="button" value="'.$langs->trans("ReturnCP").'" class="button" onclick="history.go(-1)" />';
            print '</div>';
        }

    }

}

// End of page
llxFooter();

if (is_object($db)) $db->close();
?>
