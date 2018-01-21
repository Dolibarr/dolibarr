<?php
/* Copyright (C) 2011		Dimitri Mouillard	<dmouillard@teclib.com>
 * Copyright (C) 2012-2016	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2016	Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2013		Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2017		Alexandre Spangaro	<aspangaro@zendsi.com>
 * Copyright (C) 2014-2017	Ferran Marcet		<fmarcet@2byte.es>
 * Copyright (C) 2018       Frederic France     <frederic.france@netlogic.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, orwrite
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
 *   	\file       htdocs/holiday/card.php
 *		\ingroup    holiday
 *		\brief      Form and file creation of paid holiday.
 */

require('../main.inc.php');
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/holiday.lib.php';
require_once DOL_DOCUMENT_ROOT.'/holiday/common.inc.php';

// Get parameters
$myparam = GETPOST("myparam");
$action=GETPOST('action', 'alpha');
$id=GETPOST('id', 'int');
$fuserid = (GETPOST('fuserid','int')?GETPOST('fuserid','int'):$user->id);

// Protection if external user
if ($user->societe_id > 0) accessforbidden();

$now=dol_now();

$langs->load("holiday");

$object = new Holiday($db);

// Load object
if ($id > 0) {
    $ret  = $object->fetch($id);
    if ($ret < 0) {
        unset($id);
        setEventMessages($object->error, $object->errors, 'errors');
    }
}

/*
 * Actions
 */

// If create a request
if ($action == 'create')
{
    $error = 0;
    // If no right to create a request
    $fuserid = GETPOST('fuserid','int');
    if (($fuserid == $user->id && empty($user->rights->holiday->write)) || ($fuserid != $user->id && empty($user->rights->holiday->write_all)))
    {
    	$error++;
    	setEventMessages($langs->trans('CantCreateCP'), null, 'errors');
    	$action='request';
    }

    if (! $error)
    {
    	$db->begin();

	    $date_debut = dol_mktime(0, 0, 0, GETPOST('date_debut_month'), GETPOST('date_debut_day'), GETPOST('date_debut_year'));
	    $date_fin = dol_mktime(0, 0, 0, GETPOST('date_fin_month'), GETPOST('date_fin_day'), GETPOST('date_fin_year'));
	    $date_debut_gmt = dol_mktime(0, 0, 0, GETPOST('date_debut_month'), GETPOST('date_debut_day'), GETPOST('date_debut_year'), 1);
	    $date_fin_gmt = dol_mktime(0, 0, 0, GETPOST('date_fin_month'), GETPOST('date_fin_day'), GETPOST('date_fin_year'), 1);
	    $starthalfday=GETPOST('starthalfday');
	    $endhalfday=GETPOST('endhalfday');
	    $type=GETPOST('type');
	    $halfday=0;
	    if ($starthalfday == 'afternoon' && $endhalfday == 'morning') $halfday=2;
	    else if ($starthalfday == 'afternoon') $halfday=-1;
	    else if ($endhalfday == 'morning') $halfday=1;

	    $valideur = GETPOST('valideur');
	    $description = trim(GETPOST('description'));

    	// If no type
	    if ($type <= 0)
	    {
	        setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Type")), null, 'errors');
	        $error++;
	        $action='create';
	    }

	    // If no start date
	    if (empty($date_debut))
	    {
	        setEventMessages($langs->trans("NoDateDebut"), null, 'errors');
	        $error++;
	        $action='create';
	    }
	    // If no end date
	    if (empty($date_fin))
	    {
	        setEventMessages($langs->trans("NoDateFin"), null, 'errors');
	        $error++;
	        $action='create';
	    }
	    // If start date after end date
	    if ($date_debut > $date_fin)
	    {
	        setEventMessages($langs->trans("ErrorEndDateCP"), null, 'errors');
	        $error++;
	        $action='create';
	    }

	    // Check if there is already holiday for this period
	    $verifCP = $object->verifDateHolidayCP($fuserid, $date_debut, $date_fin, $halfday);
	    if (! $verifCP)
	    {
	        setEventMessages($langs->trans("alreadyCPexist"), null, 'errors');
	        $error++;
	        $action='create';
	    }

	    // If there is no Business Days within request
	    $nbopenedday=num_open_day($date_debut_gmt, $date_fin_gmt, 0, 1, $halfday);
	    if($nbopenedday < 0.5)
	    {
	        setEventMessages($langs->trans("ErrorDureeCP"), null, 'errors');
	        $error++;
	        $action='create';
	    }

	    // If no validator designated
	    if ($valideur < 1)
	    {
	        setEventMessages($langs->transnoentitiesnoconv('InvalidValidatorCP'), null, 'errors');
	        $error++;
	    }

	    if (! $error)
	    {
    	    $object->fk_user = $fuserid;
    	    $object->description = $description;
    	    $object->fk_validator = $valideur;
    		$object->fk_type = $type;
    		$object->date_debut = $date_debut;
    		$object->date_fin = $date_fin;
    		$object->halfday = $halfday;

    		$id = $object->create($user);
    		if ($id <= 0)
    		{
    			setEventMessages($object->error, $object->errors, 'errors');
    			$error++;
    		}
	    }

	    // If no SQL error we redirect to the request card
	    if (! $error)
	    {
            $db->commit();
            setEventMessages($langs->trans('CPCreated'), null, 'mesgs');
            $action = '';
            $result = $object->fetch($id);
	    }
	    else
		{
            $db->rollback();
            $action = 'create';
	    }
    }
}

if ($action == 'update' && $id > 0)
{
	$date_debut = dol_mktime(0, 0, 0, GETPOST('date_debut_month'), GETPOST('date_debut_day'), GETPOST('date_debut_year'));
	$date_fin = dol_mktime(0, 0, 0, GETPOST('date_fin_month'), GETPOST('date_fin_day'), GETPOST('date_fin_year'));
	$date_debut_gmt = dol_mktime(0, 0, 0, GETPOST('date_debut_month'), GETPOST('date_debut_day'), GETPOST('date_debut_year'), 1);
	$date_fin_gmt = dol_mktime(0, 0, 0, GETPOST('date_fin_month'), GETPOST('date_fin_day'), GETPOST('date_fin_year'), 1);
	$starthalfday=GETPOST('starthalfday');
	$endhalfday=GETPOST('endhalfday');
	$halfday=0;
	if ($starthalfday == 'afternoon' && $endhalfday == 'morning') $halfday=2;
	else if ($starthalfday == 'afternoon') $halfday=-1;
	else if ($endhalfday == 'morning') $halfday=1;

    // If no right to modify a request
    if (! $user->rights->holiday->write)
    {
        setEventMessages($langs->transnoentitiesnoconv('CantUpdateCP') , null, 'errors');
        $action = 'request';
    } else {

	    $canedit=(($user->id == $object->fk_user && $user->rights->holiday->write) || ($user->id != $object->fk_user && $user->rights->holiday->write_all));

	    // If under validation
        if ($object->statut == Holiday::STATUS_DRAFT)
        {
            // If this is the requestor or has read/write rights
            if ($canedit)
            {
                $valideur = $_POST['valideur'];
                $description = trim($_POST['description']);
                $error = 0;

                // If no start date
                if (empty($date_debut)) {
                    setEventMessages($langs->trans("NoDateDebut"), null, 'errors');
                    $error++;
                    $action='edit';
                }

                // If no end date
                if (empty($date_fin)) {
                    setEventMessages($langs->trans("NoDateFin"), null, 'errors');
                    $error++;
                    $action = 'edit';
                }

                // If start date after end date
                if ($date_debut > $date_fin) {
                    setEventMessages($langs->trans("ErrorEndDateCP"), null, 'errors');
                    $error++;
                    $action = 'edit';
                }

                // If no validator designated
                if ($valideur < 1) {
                    setEventMessages($langs->transnoentitiesnoconv('InvalidValidatorCP'), null, 'errors');
                    $error++;
                    $action = 'edit';
                }

                // If there is no Business Days within request
                $nbopenedday=num_open_day($date_debut_gmt, $date_fin_gmt, 0, 1, $halfday);
                if ($nbopenedday < 0.5) {
                    setEventMessages($langs->trans("ErrorDureeCP"), null, 'errors');
                    $error++;
                    $action = 'edit';
                }
                if (!$error) {

                    $object->description = $description;
                    $object->date_debut = $date_debut;
                    $object->date_fin = $date_fin;
                    $object->fk_validator = $valideur;
			        $object->halfday = $halfday;

			        // Update
			        $result = $object->update($user);
                    if ($result > 0)
                    {
                        setEventMessages($langs->trans('CPUpdated'), null, 'mesgs');
                        $action = '';
                    }
                    else
                    {
                        setEventMessages($langs->trans('ErrorSQLCreateCP'), null, 'errors');
                        setEventMessages($object->error, $object->errors, 'errors');
                        $action = 'edit';
                    }
                }
            }
        } else {
            setEventMessages($langs->trans('CPNotDraft'), null, 'warnings');
            $action = '';
        }
    }
}

// If delete of request
if ($action == 'confirm_delete' && GETPOST('confirm') == 'yes' && $user->rights->holiday->delete && $id > 0)
{
	$error=0;

	$db->begin();

	$canedit=(($user->id == $object->fk_user && $user->rights->holiday->write) || ($user->id != $object->fk_user && $user->rights->holiday->write_all));

    // If this is a rough draft
	if ($object->statut == Holiday::STATUS_DRAFT || $object->statut == Holiday::STATUS_APPROVED)
	{
		// Si l'utilisateur à le droit de lire cette demande, il peut la supprimer
		if ($canedit)
		{
			$result=$object->delete($user);
		}
		else
		{
            setEventMessages($langs->trans('ErrorCantDeleteCP'), null, 'errors');
            $error++;
		}
	}

	if (! $error)
	{
		$db->commit();
		header('Location: list.php');
		exit;
	}
	else
	{
		$db->rollback();
	}
}

// Si envoi de la demande
if ($action == 'confirm_send' && $id > 0)
{

    $canedit=(($user->id == $object->fk_user && $user->rights->holiday->write) || ($user->id != $object->fk_user && $user->rights->holiday->write_all));

    // Si brouillon et créateur
    if($object->statut == Holiday::STATUS_DRAFT && $canedit)
    {
        $object->statut = Holiday::STATUS_TO_REVIEW;

        $verif = $object->update($user);

        // Si pas d'erreur SQL on redirige vers la fiche de la demande
        if ($verif > 0)
        {
            // To
            $destinataire = new User($db);
            $destinataire->fetch($object->fk_validator);
            $emailTo = $destinataire->email;

            if ($emailTo)
            {

                // From
                $expediteur = new User($db);
                $expediteur->fetch($object->fk_user);
                $emailFrom = $expediteur->email;

                // Subject
			    $societeName = $conf->global->MAIN_INFO_SOCIETE_NOM;
                if (! empty($conf->global->MAIN_APPLICATION_TITLE)) $societeName = $conf->global->MAIN_APPLICATION_TITLE;

                $subject = $societeName." - ".$langs->transnoentitiesnoconv("HolidaysToValidate");

                // Content
                $message = $langs->transnoentitiesnoconv("Hello")." ".$destinataire->firstname.",\n";
                $message.= "\n";
                $message.= $langs->transnoentities("HolidaysToValidateBody")."\n";

                $delayForRequest = $object->getConfCP('delayForRequest');
                //$delayForRequest = $delayForRequest * (60*60*24);

                $nextMonth = dol_time_plus_duree($now, $delayForRequest, 'd');

                // Si l'option pour avertir le valideur en cas de délai trop court
                if($object->getConfCP('AlertValidatorDelay'))
                {
                    if($object->date_debut < $nextMonth)
                    {
                        $message.= "\n";
                        $message.= $langs->transnoentities("HolidaysToValidateDelay",$object->getConfCP('delayForRequest'))."\n";
                    }
                }

                // Si l'option pour avertir le valideur en cas de solde inférieur à la demande
                if ($object->getConfCP('AlertValidatorSolde'))
                {
            	    $nbopenedday=num_open_day($object->date_debut_gmt,$object->date_fin_gmt,0,1,$object->halfday);
                    if ($nbopenedday > $object->getCPforUser($object->fk_user, $object->fk_type))
                    {
                        $message.= "\n";
                        $message.= $langs->transnoentities("HolidaysToValidateAlertSolde")."\n";
                    }
                }

                $message.= "\n";
                $message.= "- ".$langs->transnoentitiesnoconv("Name")." : ".dolGetFirstLastname($expediteur->firstname, $expediteur->lastname)."\n";
                $message.= "- ".$langs->transnoentitiesnoconv("Period")." : ".dol_print_date($object->date_debut,'day')." ".$langs->transnoentitiesnoconv("To")." ".dol_print_date($object->date_fin,'day')."\n";
                $message.= "- ".$langs->transnoentitiesnoconv("Link")." : ".$dolibarr_main_url_root."/holiday/card.php?id=".$object->id."\n\n";
                $message.= "\n";

                $trackid = 'leav'.$object->id;

                $mail = new CMailFile($subject, $emailTo, $emailFrom, $message, array(), null, null, '', '', 0, 0, '', '', $trackid);

                // Envoi du mail
                $result = $mail->sendfile();

                if (!$result)
                {
                    setEventMessages($langs->transnoentitiesnoconv('ErrorMailNotSend'), null, 'warnings');
                    setEventMessages($mail->error, $mail->errors, 'warnings');
                }
            }
        }
        else
        {
            setEventMessages($langs->trans('ErrorSQLCreateCP'), null, 'errors');
            setEventMessages($object->error, $object->errors, 'errors');
        }
        $action = '';
    }
}


// Si Validation de la demande
if ($action == 'confirm_valid' && $id > 0)
{

    // Si statut en attente de validation et valideur = utilisateur
    if ($object->statut == Holiday::STATUS_TO_REVIEW && $user->id == $object->fk_validator)
    {
        $object->date_valid = dol_now();
        $object->fk_user_valid = $user->id;
        $object->statut = 3;

        $verif = $object->update($user);

        // Si pas d'erreur SQL on redirige vers la fiche de la demande
        if ($verif > 0)
        {
            // Calculcate number of days consummed
            $nbopenedday=num_open_day($object->date_debut_gmt,$object->date_fin_gmt,0,1,$object->halfday);

            $soldeActuel = $object->getCpforUser($object->fk_user, $object->fk_type);
            $newSolde = $soldeActuel - ($nbopenedday * $object->getConfCP('nbHolidayDeducted'));

            // On ajoute la modification dans le LOG
            $object->addLogCP($user->id, $object->fk_user, $langs->transnoentitiesnoconv("Holidays"), $newSolde, $object->fk_type);

            // Mise à jour du solde
            $object->updateSoldeCP($object->fk_user, $newSolde, $object->fk_type);

            // To
            $destinataire = new User($db);
            $destinataire->fetch($object->fk_user);
            $emailTo = $destinataire->email;

            if ($emailTo)
            {

                // From
                $expediteur = new User($db);
                $expediteur->fetch($object->fk_validator);
                $emailFrom = $expediteur->email;

                // Subject
			    $societeName = $conf->global->MAIN_INFO_SOCIETE_NOM;
                if (! empty($conf->global->MAIN_APPLICATION_TITLE)) $societeName = $conf->global->MAIN_APPLICATION_TITLE;

                $subject = $societeName." - ".$langs->transnoentitiesnoconv("HolidaysValidated");

                // Content
                $message = $langs->transnoentitiesnoconv("Hello")." ".$destinataire->firstname.",\n";
                $message.= "\n";
                $message.=  $langs->transnoentities("HolidaysValidatedBody", dol_print_date($object->date_debut,'day'),dol_print_date($object->date_fin,'day'))."\n";

                $message.= "- ".$langs->transnoentitiesnoconv("ValidatedBy")." : ".dolGetFirstLastname($expediteur->firstname, $expediteur->lastname)."\n";

                $message.= "- ".$langs->transnoentitiesnoconv("Link")." : ".$dolibarr_main_url_root."/holiday/card.php?id=".$object->id."\n\n";
                $message.= "\n";

                $trackid='leav'.$object->id;

                $mail = new CMailFile($subject, $emailTo, $emailFrom, $message, array(), null, null, '', '', 0, 0, '', '', $trackid);

                // Envoi du mail
                $result=$mail->sendfile();

                if (!$result) {
                    setEventMessages($langs->transnoentitiesnoconv('ErrorMailNotSend'), null, 'warnings');
                    setEventMessages($mail->error, $mail->errors, 'warnings');
                }
            }

        } else {
            setEventMessages($langs->trans('ErrorSQLCreateCP'), null, 'errors');
            setEventMessages($object->error, $object->errors, 'errors');
        }
        $action = '';
    }

}

if ($action == 'confirm_refuse' && GETPOST('confirm') == 'yes' && $id > 0)
{
    if (! empty($_POST['detail_refuse']))
    {

        // Si statut en attente de validation et valideur = utilisateur
        if ($object->statut == Holiday::STATUS_TO_REVIEW && $user->id == $object->fk_validator)
        {
            $object->date_refuse = date('Y-m-d H:i:s', time());
            $object->fk_user_refuse = $user->id;
            $object->statut = 5;
            $object->detail_refuse = $_POST['detail_refuse'];

            $verif = $object->update($user);

            // Si pas d'erreur SQL on redirige vers la fiche de la demande
            if ($verif > 0)
            {
                // To
                $destinataire = new User($db);
                $destinataire->fetch($object->fk_user);
                $emailTo = $destinataire->email;

                if ($emailTo)
                {

                    // From
                    $expediteur = new User($db);
                    $expediteur->fetch($object->fk_validator);
                    $emailFrom = $expediteur->email;

	                // Subject
				    $societeName = $conf->global->MAIN_INFO_SOCIETE_NOM;
	                if (! empty($conf->global->MAIN_APPLICATION_TITLE)) $societeName = $conf->global->MAIN_APPLICATION_TITLE;

	                $subject = $societeName." - ".$langs->transnoentitiesnoconv("HolidaysRefused");

                    // Content
            	    $message = $langs->transnoentitiesnoconv("Hello")." ".$destinataire->firstname.",\n";
	                $message.= "\n";
                    $message.= $langs->transnoentities("HolidaysRefusedBody", dol_print_date($object->date_debut,'day'), dol_print_date($object->date_fin,'day'))."\n";
                    $message.= GETPOST('detail_refuse','alpha')."\n\n";

	                $message.= "- ".$langs->transnoentitiesnoconv("ModifiedBy")." : ".dolGetFirstLastname($expediteur->firstname, $expediteur->lastname)."\n";

	                $message.= "- ".$langs->transnoentitiesnoconv("Link")." : ".$dolibarr_main_url_root."/holiday/card.php?id=".$object->id."\n\n";
                    $message.= "\n";

	                $trackid ='leav'.$object->id;

	                $mail = new CMailFile($subject, $emailTo, $emailFrom, $message, array(), null, null, '', '', 0, 0, '', '', $trackid);

                    // Envoi du mail
                    $result = $mail->sendfile();

                    if (!$result) {
                        setEventMessages($langs->transnoentitiesnoconv('ErrorMailNotSend'), null, 'warnings');
                        setEventMessages($mail->error, $mail->errors, 'warnings');
                    }
                }
            } else {
                setEventMessages($langs->trans('ErrorSQLCreateCP'), null, 'errors');
                setEventMessages($object->error, $object->errors, 'errors');
            }
            $action = '';

        }

    } else {
        setEventMessages($langs->transnoentitiesnoconv('NoMotifRefuseCP'), null, 'errors');
        $action = 'refuse';
    }
}


// Si Validation de la demande
if ($action == 'confirm_draft' && GETPOST('confirm') == 'yes' && $id > 0)
{

    $oldstatus = $object->statut;
    $object->statut = 1;

    $result = $object->update($user);
    if ($result < 0)
    {
        setEventMessages($langs->trans('ErrorBackToDraft'), null, 'errors');
        setEventMessages($object->error, $object->errors, 'errors');
        $db->rollback();
    } else {
        $db->commit();
        setEventMessages($langs->trans('CPBackToDraft'), null, 'mesgs');
    }
    $id = $object->id;
    $action = '';
}

// Si Validation de la demande
if ($action == 'confirm_cancel' && GETPOST('confirm') == 'yes' && $id > 0)
{

    // Si statut en attente de validation et valideur = valideur ou utilisateur, ou droits de faire pour les autres
    if (($object->statut == Holiday::STATUS_TO_REVIEW || $object->statut == Holiday::STATUS_APPROVED) && ($user->id == $object->fk_validator || $user->id == $object->fk_user || ! empty($user->rights->holiday->write_all)))
    {
    	$db->begin();

    	$oldstatus = $object->statut;
        $object->date_cancel = dol_now();
        $object->fk_user_cancel = $user->id;
        $object->statut = 4;
        $error = 0;

        $result = $object->update($user);

        if ($result >= 0 && $oldstatus == Holiday::STATUS_APPROVED)	// holiday was already validated, status 3, so we must increase back sold
        {
        	// Calculcate number of days consummed
        	$nbopenedday=num_open_day($object->date_debut_gmt,$object->date_fin_gmt,0,1,$object->halfday);

        	$soldeActuel = $object->getCpforUser($object->fk_user, $object->fk_type);
        	$newSolde = $soldeActuel + ($nbopenedday * $object->getConfCP('nbHolidayDeducted'));

        	// On ajoute la modification dans le LOG
        	$result1=$object->addLogCP($user->id, $object->fk_user, $langs->transnoentitiesnoconv("HolidaysCancelation"), $newSolde, $object->fk_type);

        	// Mise à jour du solde
        	$result2=$object->updateSoldeCP($object->fk_user, $newSolde, $object->fk_type);

        	if ($result1 < 0 || $result2 < 0)
        	{
                setEventMessages($langs->trans('ErrorCantDeleteCP'), null, 'errors');
                $error++;
        	}
        }

        if (! $error)
        {
        	$db->commit();
        }
        else
        {
        	$db->rollback();
        }

        // Si pas d'erreur SQL on redirige vers la fiche de la demande
        if (! $error && $result > 0)
        {
            // To
            $destinataire = new User($db);
            $destinataire->fetch($object->fk_user);
            $emailTo = $destinataire->email;

            if ($emailTo)
            {

                // From
                $expediteur = new User($db);
                $expediteur->fetch($object->fk_user_cancel);
                $emailFrom = $expediteur->email;

                // Subject
			    $societeName = $conf->global->MAIN_INFO_SOCIETE_NOM;
                if (! empty($conf->global->MAIN_APPLICATION_TITLE)) $societeName = $conf->global->MAIN_APPLICATION_TITLE;

                $subject = $societeName." - ".$langs->transnoentitiesnoconv("HolidaysCanceled");

                // Content
           	    $message = $langs->transnoentitiesnoconv("Hello")." ".$destinataire->firstname.",\n";
                $message.= "\n";

                $message.= $langs->transnoentities("HolidaysCanceledBody", dol_print_date($object->date_debut,'day'), dol_print_date($object->date_fin,'day'))."\n";
                $message.= "- ".$langs->transnoentitiesnoconv("ModifiedBy")." : ".dolGetFirstLastname($expediteur->firstname, $expediteur->lastname)."\n";

                $message.= "- ".$langs->transnoentitiesnoconv("Link")." : ".$dolibarr_main_url_root."/holiday/card.php?id=".$object->id."\n\n";
                $message.= "\n";

                $trackid='leav'.$object->id;

                $mail = new CMailFile($subject, $emailTo, $emailFrom, $message, array(), null, null, '', '', 0, 0, '', '', $trackid);

                // send mail
                $result = $mail->sendfile();

                if(!$result)
                {
                    setEventMessages($langs->transnoentitiesnoconv('ErrorMailNotSend'), null, 'warnings');
                    setEventMessages($mail->error, $mail->errors, 'warnings');
                }
            }
        }
        else
        {
            setEventMessages($langs->trans('ErrorSQLCreateCP'), null, 'errors');
            setEventMessages($object->error, $object->errors, 'errors');
        }
        $action = '';
    }
}



/*
 * View
 */

$form = new Form($db);

$listhalfday = array(
    'morning' => $langs->trans("Morning"),
    'afternoon' => $langs->trans("Afternoon"),
);

llxHeader('', $langs->trans('CPTitreMenu'));

if (empty($id) || $action == 'add' || $action == 'request' || $action == 'create')
{
    // Si l'utilisateur n'a pas le droit de faire une demande
    if (($fuserid == $user->id && empty($user->rights->holiday->write)) || ($fuserid != $user->id && empty($user->rights->holiday->write_all)))
    {
        setEventMessages($langs->trans('CantCreateCP'), null, 'errors');
    }
    else
    {
        // Formulaire de demande de congés payés
        print load_fiche_titre($langs->trans('MenuAddCP'), '', 'title_hrm.png');

        // // Si il y a une erreur
        // if (GETPOST('error')) {

        //     switch(GETPOST('error')) {
        //         case 'datefin' :
        //             $errors[] = $langs->trans('ErrorEndDateCP');
        //             break;
        //         case 'SQL_Create' :
        //             $errors[] = $langs->trans('ErrorSQLCreateCP').' <b>'.htmlentities($_GET['msg']).'</b>';
        //             break;
        //         case 'CantCreate' :
        //             $errors[] = $langs->trans('CantCreateCP');
        //             break;
        //         case 'Valideur' :
        //             $errors[] = $langs->trans('InvalidValidatorCP');
        //             break;
        //         case 'nodatedebut' :
        //             $errors[] = $langs->trans('NoDateDebut');
        //             break;
        //         case 'nodatefin' :
        //             $errors[] = $langs->trans('NoDateFin');
        //             break;
        //         case 'DureeHoliday' :
        //             $errors[] = $langs->trans('ErrorDureeCP');
        //             break;
        //         case 'alreadyCP' :
        //             $errors[] = $langs->trans('alreadyCPexist');
        //             break;
        //     }

	    //     setEventMessages($errors, null, 'errors');
        // }


        $delayForRequest = $object->getConfCP('delayForRequest');
        //$delayForRequest = $delayForRequest * (60*60*24);

        $nextMonth = dol_time_plus_duree($now, $delayForRequest, 'd');

        // Formulaire de demande
        print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'" onsubmit="return valider()" name="demandeCP">'."\n";
        print '<input type="hidden" name="action" value="create" />'."\n";

        dol_fiche_head('', '', '', -1);

        $out='';
        $typeleaves=$object->getTypes(1, 1);
        print $langs->trans('SoldeCPUser', round($nb_holiday,5)).'<br>';
        print '<ul>';
    	foreach($typeleaves as $key => $val)
		{
			$nb_type = $object->getCPforUser($user->id, $val['rowid']);
			$nb_holiday += $nb_type;
			print '<li>'.$val['label'].': <strong>'.($nb_type?price2num($nb_type):0).'</strong></li>';
		}
        print '</ul>';
        dol_fiche_end();


        dol_fiche_head();

        //print '<span>'.$langs->trans('DelayToRequestCP',$object->getConfCP('delayForRequest')).'</span><br><br>';

        print '<table class="border" width="100%">';
        print '<tbody>';

        // User
        print '<tr>';
        print '<td class="titlefield fieldrequired">'.$langs->trans("User").'</td>';
        print '<td>';
        if (empty($user->rights->holiday->write_all))
        {
        	print $form->select_dolusers($fuserid, 'useridbis', 0, '', 1, '', '', 0, 0, 0, '', 0, '', 'maxwidth300');
        	print '<input type="hidden" name="fuserid" value="'.($fuserid?$fuserid:$user->id).'">';
        }
        else print $form->select_dolusers(GETPOST('fuserid','int')?GETPOST('fuserid','int'):$user->id,'fuserid',0,'',0);
        print '</td>';
        print '</tr>';

        // Type
        print '<tr>';
        print '<td class="fieldrequired">'.$langs->trans("Type").'</td>';
        print '<td>';
        $typeleaves=$object->getTypes(1,-1);
        $arraytypeleaves=array();
        foreach($typeleaves as $key => $val)
        {
        	$labeltoshow = $val['label'];
        	$labeltoshow .= ($val['delay'] > 0 ? ' ('.$langs->trans("NoticePeriod").': '.$val['delay'].' '.$langs->trans("days").')':'');
			$arraytypeleaves[$val['rowid']]=$labeltoshow;
        }
        print $form->selectarray('type', $arraytypeleaves, (GETPOST('type')?GETPOST('type'):''), 1);
        if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
        print '</td>';
        print '</tr>';

        // Date start
        print '<tr>';
        print '<td class="fieldrequired">';
        print $langs->trans("DateDebCP");
        print ' ('.$langs->trans("FirstDayOfHoliday").')';
        print '</td>';
        print '<td>';
        // Si la demande ne vient pas de l'agenda
        if (! GETPOST('date_debut_')) {
            $form->select_date(-1, 'date_debut_', 0, 0, 0, '', 1, 1);
        } else {
            $tmpdate = dol_mktime(0, 0, 0, GETPOST('date_debut_month'), GETPOST('date_debut_day'), GETPOST('date_debut_year'));
            $form->select_date($tmpdate, 'date_debut_', 0, 0, 0, '', 1, 1);
        }
        print ' &nbsp; &nbsp; ';
        print $form->selectarray('starthalfday', $listhalfday, (GETPOST('starthalfday')?GETPOST('starthalfday'):'morning'));
        print '</td>';
        print '</tr>';

        // Date end
        print '<tr>';
        print '<td class="fieldrequired">';
        print $langs->trans("DateFinCP");
        print ' ('.$langs->trans("LastDayOfHoliday").')';
        print '</td>';
        print '<td>';
        // Si la demande ne vient pas de l'agenda
        if (! GETPOST('date_fin_')) {
            $form->select_date(-1,'date_fin_', 0, 0, 0, '', 1, 1);
        } else {
            $tmpdate = dol_mktime(0, 0, 0, GETPOST('date_fin_month'), GETPOST('date_fin_day'), GETPOST('date_fin_year'));
            $form->select_date($tmpdate,'date_fin_', 0, 0, 0, '', 1, 1);
        }
        print ' &nbsp; &nbsp; ';
        print $form->selectarray('endhalfday', $listhalfday, (GETPOST('endhalfday')?GETPOST('endhalfday'):'afternoon'));
        print '</td>';
        print '</tr>';

        // Approved by
        print '<tr>';
        print '<td class="fieldrequired">'.$langs->trans("ReviewedByCP").'</td>';
        print '<td>';
        print $form->select_dolusers((GETPOST('valideur')>0?GETPOST('valideur'):$user->fk_user), "valideur", 1, ($user->admin ? '' : array($user->id)), 0, '', 0, 0, 0, 0, '', 0, '', '', 1);	// By default, hierarchical parent
        print '</td>';
        print '</tr>';

        // Description
        print '<tr>';
        print '<td>'.$langs->trans("DescCP").'</td>';
        print '<td class="tdtop">';
        $doleditor = new DolEditor('description', GETPOST('description'), '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, '90%');
        print $doleditor->Create(1);
        print '</td></tr>';

        print '</tbody>';
        print '</table>';

        dol_fiche_end();

        print '<div class="center">';
        print '<input type="submit" value="'.$langs->trans("SendRequestCP").'" name="bouton" class="button">';
        print '&nbsp;&nbsp;';
        print '<input type="button" value="'.$langs->trans("Cancel").'" class="button" onclick="history.go(-1)">';
        print '</div>';

        print '</from>'."\n";
    }
}
else
{
    // Affichage de la fiche d'une demande de congés payés

	$canedit=(($user->id == $object->fk_user && $user->rights->holiday->write) || ($user->id != $object->fk_user && $user->rights->holiday->write_all));

    $valideur = new User($db);
    $valideur->fetch($object->fk_validator);

    $userRequest = new User($db);
    $userRequest->fetch($object->fk_user);

    //print load_fiche_titre($langs->trans('TitreRequestCP'));

    // On vérifie si l'utilisateur à le droit de lire cette demande
    if ($canedit)
    {
        if ($action == 'delete')
        {
            if ($user->rights->holiday->delete)
            {
                print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id,$langs->trans("TitleDeleteCP"),$langs->trans("ConfirmDeleteCP"),"confirm_delete", '', 0, 1);
            }
        }

        // Si envoi en validation
        if ($action == 'sendToValidate' && $object->statut == Holiday::STATUS_DRAFT)
        {
            print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id,$langs->trans("TitleToValidCP"),$langs->trans("ConfirmToValidCP"),"confirm_send", '', 1, 1);
        }

        // Si validation de la demande
        if ($action == 'valid')
        {
            print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id,$langs->trans("TitleValidCP"),$langs->trans("ConfirmValidCP"),"confirm_valid", '', 1, 1);
        }

        // Si refus de la demande
        if ($action == 'refuse')
        {
            $array_input = array(array('type'=>"text",'label'=> $langs->trans('DetailRefusCP'),'name'=>"detail_refuse",'size'=>"50",'value'=>""));
            print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id."&action=confirm_refuse", $langs->trans("TitleRefuseCP"), $langs->trans('ConfirmRefuseCP'), "confirm_refuse", $array_input, 1, 0);
        }

        // Si annulation de la demande
        if ($action == 'cancel')
        {
            print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id,$langs->trans("TitleCancelCP"),$langs->trans("ConfirmCancelCP"),"confirm_cancel", '', 1, 1);
        }

        // Si back to draft
        if ($action == 'backtodraft')
        {
            print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id,$langs->trans("TitleSetToDraft"),$langs->trans("ConfirmSetToDraft"),"confirm_draft", '', 1, 1);
        }

        $head = holiday_prepare_head($object);


        if ($action == 'edit' && $object->statut == Holiday::STATUS_DRAFT)
        {
            print '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">'."\n";
            print '<input type="hidden" name="action" value="update"/>'."\n";
            print '<input type="hidden" name="id" value="'.$object->id.'" />'."\n";
        }

        dol_fiche_head($head, 'card', $langs->trans("CPTitreMenu"), -1, 'holiday');

        $linkback='<a href="'.DOL_URL_ROOT.'/holiday/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

        dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref');


        print '<div class="fichecenter">';
        print '<div class="fichehalfleft">';
        print '<div class="underbanner clearboth"></div>';

        print '<table class="border centpercent">';
        print '<tbody>';

        print '<tr>';
        print '<td class="titlefield">'.$langs->trans("User").'</td>';
        print '<td>';
        print $userRequest->getNomUrl(-1, 'leave');
        print '</td></tr>';

		// Type
	    print '<tr>';
	    print '<td>'.$langs->trans("Type").'</td>';
        print '<td>';
        $typeleaves=$object->getTypes(1,-1);
        print empty($typeleaves[$object->fk_type]['label']) ? $langs->trans("TypeWasDisabledOrRemoved",$object->fk_type) : $typeleaves[$object->fk_type]['label'];
        print '</td>';
        print '</tr>';

	    $starthalfday=($object->halfday == -1 || $object->halfday == 2)?'afternoon':'morning';
	    $endhalfday=($object->halfday == 1 || $object->halfday == 2)?'morning':'afternoon';

        print '<tr>';
        print '<td>'.$langs->trans('DateDebCP').' ('.$langs->trans("FirstDayOfHoliday").')</td>';
        if($action != 'edit')
        {
            print '<td>'.dol_print_date($object->date_debut,'day');
		    print ' &nbsp; &nbsp; ';
		    print '<span class="opacitymedium">'.$langs->trans($listhalfday[$starthalfday]).'</span>';
        }
        else
        {
            print '<td>';
            $form->select_date($object->date_debut,'date_debut_');
	        print ' &nbsp; &nbsp; ';
        	print $form->selectarray('starthalfday', $listhalfday, (GETPOST('starthalfday')?GETPOST('starthalfday'):$starthalfday));
        }
        print '</td>';
        print '</tr>';

        print '<tr>';
        print '<td>'.$langs->trans('DateFinCP').' ('.$langs->trans("LastDayOfHoliday").')</td>';
        if ($action != 'edit')
        {
            print '<td>'.dol_print_date($object->date_fin,'day');
            print ' &nbsp; &nbsp; ';
            print '<span class="opacitymedium">'.$langs->trans($listhalfday[$endhalfday]).'</span>';
        }
        else
        {
            print '<td>';
            $form->select_date($object->date_fin,'date_fin_');
	        print ' &nbsp; &nbsp; ';
        	print $form->selectarray('endhalfday', $listhalfday, (GETPOST('endhalfday')?GETPOST('endhalfday'):$endhalfday));
        }
        print '</td>';
        print '</tr>';

        print '<tr>';
        print '<td>'.$langs->trans('NbUseDaysCP').'</td>';
        print '<td>'.num_open_day($object->date_debut_gmt, $object->date_fin_gmt, 0, 1, $object->halfday).'</td>';
        print '</tr>';

        if ($object->statut == Holiday::STATUS_REFUSED)
        {
            print '<tr>';
            print '<td>'.$langs->trans('DetailRefusCP').'</td>';
            print '<td>'.$object->detail_refuse.'</td>';
            print '</tr>';
        }

        // Description
        print '<tr>';
        print '<td>'.$langs->trans('DescCP').'</td>';
        if ($action != 'edit')
        {
            print '<td>'.nl2br($object->description).'</td>';
        }
        else
        {
            print '<td class="tdtop">';
            $doleditor = new DolEditor('description', $object->description, '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, '90%');
            print $doleditor->Create(1);
            print '</td>';
        }
        print '</tr>';

        print '</tbody>';
        print '</table>'."\n";

        print '</div>';
        print '<div class="fichehalfright">';
        print '<div class="ficheaddleft">';

        print '<div class="underbanner clearboth"></div>';

		// Info workflow
        print '<table class="border centpercent">'."\n";
        print '<tbody>';

        if (! empty($object->fk_user_create))
        {
            $userCreate=new User($db);
            $userCreate->fetch($object->fk_user_create);
            print '<tr>';
            print '<td class="titlefield">'.$langs->trans('RequestByCP').'</td>';
            print '<td>'.$userCreate->getNomUrl(-1).'</td>';
            print '</tr>';
        }

        print '<tr>';
        print '<td class="titlefield">'.$langs->trans('ReviewedByCP').'</td>';
        if ($action != 'edit') {
            print '<td>'.$valideur->getNomUrl(-1).'</td>';
        } else {
            print '<td>';
        	print $form->select_dolusers($object->fk_user, "valideur", 1, ($user->admin ? '' : array($user->id)));	// By default, hierarchical parent
            print '</td>';
        }
        print '</tr>';

        print '<tr>';
        print '<td>'.$langs->trans('DateCreateCP').'</td>';
        print '<td>'.dol_print_date($object->date_create,'dayhour').'</td>';
        print '</tr>';
        if ($object->statut == Holiday::STATUS_APPROVED) {
            print '<tr>';
            print '<td>'.$langs->trans('DateValidCP').'</td>';
            print '<td>'.dol_print_date($object->date_valid,'dayhour').'</td>';
            print '</tr>';
        }
        if ($object->statut == Holiday::STATUS_CANCELED) {
            print '<tr>';
            print '<td>'.$langs->trans('DateCancelCP').'</td>';
            print '<td>'.dol_print_date($object->date_cancel,'dayhour').'</td>';
            print '</tr>';
        }
        if ($object->statut == Holiday::STATUS_REFUSED) {
            print '<tr>';
            print '<td>'.$langs->trans('DateRefusCP').'</td>';
            print '<td>'.dol_print_date($object->date_refuse,'dayhour').'</td>';
            print '</tr>';
        }
        print '</tbody>';
        print '</table>';

        print '</div>';
        print '</div>';
        print '</div>';

        print '<div class="clearboth"></div>';

        dol_fiche_end();


        if ($action == 'edit' && $object->statut == Holiday::STATUS_DRAFT)
        {
            print '<div align="center">';
            if ($canedit && $object->statut == Holiday::STATUS_DRAFT)
            {
                print '<input type="submit" value="'.$langs->trans("Save").'" class="button">';
            }
            print '</div>';

            print '</form>';
        }


        if ($action != 'edit')
        {
            print '<div class="tabsAction">';

            // Boutons d'actions
            if ($canedit && $object->statut == Holiday::STATUS_DRAFT)
            {
                print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit" class="butAction">'.$langs->trans("EditCP").'</a>';
                print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=sendToValidate" class="butAction">'.$langs->trans("Validate").'</a>';
            }
            if ($user->rights->holiday->delete && $object->statut == Holiday::STATUS_DRAFT)
            {
                print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete" class="butActionDelete">'.$langs->trans("DeleteCP").'</a>';
            }

            if ($object->statut == Holiday::STATUS_DRAFT)
            {
                if ($user->id == $object->fk_validator)
                {
                    print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=valid" class="butAction">'.$langs->trans("Approve").'</a>';
                    print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=refuse" class="butAction">'.$langs->trans("ActionRefuseCP").'</a>';
                }
                else
                {
                    print '<a href="#" class="butActionRefused" title="'.$langs->trans("NotTheAssignedApprover").'">'.$langs->trans("Approve").'</a>';
                    print '<a href="#" class="butActionRefused" title="'.$langs->trans("NotTheAssignedApprover").'">'.$langs->trans("ActionRefuseCP").'</a>';
                }
            }

            if (($user->id == $object->fk_validator || $user->id == $object->fk_user || ! empty($user->rights->holiday->write_all)) && ($object->statut == Holiday::STATUS_TO_REVIEW || $object->statut == 3))	// Status validated or approved
            {
                if (($object->date_debut > dol_now()) || $user->admin) print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=cancel" class="butAction">'.$langs->trans("ActionCancelCP").'</a>';
                else print '<a href="#" class="butActionRefused" title="'.$langs->trans("HolidayStarted").'">'.$langs->trans("ActionCancelCP").'</a>';
            }

            if ($canedit && $object->statut == 4)
            {
                print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=backtodraft" class="butAction">'.$langs->trans("SetToDraft").'</a>';
            }

            print '</div>';
        }

    } else {
        print '<div class="tabBar">';
        print $langs->trans('ErrorUserViewCP');
        print '<br><br><input type="button" value="'.$langs->trans("ReturnCP").'" class="button" onclick="history.go(-1)" />';
        print '</div>';
    }

}

// End of page
llxFooter();

$db->close();
