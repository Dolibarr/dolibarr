<?php
/* Copyright (C) 2011		Dimitri Mouillard	<dmouillard@teclib.com>
 * Copyright (C) 2012-2016	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2016	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2013		Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2017		Alexandre Spangaro	<aspangaro@open-dsi.fr>
 * Copyright (C) 2014-2017  Ferran Marcet		<fmarcet@2byte.es>
 * Copyright (C) 2018       Frédéric France     <frederic.france@netlogic.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *   	\file       htdocs/holiday/card.php
 *		\ingroup    holiday
 *		\brief      Form and file creation of paid holiday.
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/holiday.lib.php';
require_once DOL_DOCUMENT_ROOT.'/holiday/class/holiday.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

// Get parameters
$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'alpha');
$confirm = GETPOST('confirm', 'alpha');

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$fuserid = (GETPOST('fuserid', 'int') ?GETPOST('fuserid', 'int') : $user->id);

// Load translation files required by the page
$langs->loadLangs(array("other", "holiday", "mails"));

$now = dol_now();

$childids = $user->getAllChildIds(1);

$morefilter = '';
if (!empty($conf->global->HOLIDAY_HIDE_FOR_NON_SALARIES)) $morefilter = 'AND employee = 1';

$error = 0;

$object = new Holiday($db);

$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

if (($id > 0) || $ref)
{
	$object->fetch($id, $ref);

	// Check current user can read this leave request
	$canread = 0;
	if (!empty($user->rights->holiday->readall)) $canread = 1;
	if (!empty($user->rights->holiday->read) && in_array($object->fk_user, $childids)) $canread = 1;
	if (!$canread)
	{
		accessforbidden();
	}
}

$cancreate = 0;

if (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->holiday->writeall_advance)) $cancreate = 1;
if (!empty($user->rights->holiday->write) && in_array($fuserid, $childids)) $cancreate = 1;

$candelete = 0;
if (!empty($user->rights->holiday->delete)) $candelete = 1;
if ($object->statut == Holiday::STATUS_DRAFT && $user->rights->holiday->write && in_array($object->fk_user, $childids)) $candelete = 1;

// Protection if external user
if ($user->socid) $socid = $user->socid;
$result = restrictedArea($user, 'holiday', $object->id, 'holiday');


/*
 * Actions
 */

$parameters = array('socid' => $socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	if ($cancel)
	{
		if (!empty($backtopage))
		{
			header("Location: ".$backtopage);
			exit;
		}
		$action = '';
	}

	// Add leave request
	if ($action == 'add')
	{
		// If no right to create a request
		if (!$cancreate)
		{
			$error++;
			setEventMessages($langs->trans('CantCreateCP'), null, 'errors');
			$action = 'create';
		}

		if (!$error)
		{
			$object = new Holiday($db);

			$db->begin();

			$date_debut = dol_mktime(0, 0, 0, GETPOST('date_debut_month'), GETPOST('date_debut_day'), GETPOST('date_debut_year'));
			$date_fin = dol_mktime(0, 0, 0, GETPOST('date_fin_month'), GETPOST('date_fin_day'), GETPOST('date_fin_year'));
			$date_debut_gmt = dol_mktime(0, 0, 0, GETPOST('date_debut_month'), GETPOST('date_debut_day'), GETPOST('date_debut_year'), 1);
			$date_fin_gmt = dol_mktime(0, 0, 0, GETPOST('date_fin_month'), GETPOST('date_fin_day'), GETPOST('date_fin_year'), 1);
			$starthalfday = GETPOST('starthalfday');
			$endhalfday = GETPOST('endhalfday');
			$type = GETPOST('type');
			$halfday = 0;
			if ($starthalfday == 'afternoon' && $endhalfday == 'morning') $halfday = 2;
			elseif ($starthalfday == 'afternoon') $halfday = -1;
			elseif ($endhalfday == 'morning') $halfday = 1;

			$valideur = GETPOST('valideur', 'int');
			$description = trim(GETPOST('description', 'restricthtml'));

			// Check that leave is for a user inside the hierarchy or advanced permission for all is set
			if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->holiday->write)) || (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->holiday->writeall_advance))) {
				$error++;
				setEventMessages($langs->trans("NotEnoughPermission"), null, 'errors');
			} else {
				if (empty($conf->global->MAIN_USE_ADVANCED_PERMS) || empty($user->rights->holiday->writeall_advance)) {
					if (!in_array($fuserid, $childids)) {
						$error++;
						setEventMessages($langs->trans("UserNotInHierachy"), null, 'errors');
						$action = 'create';
					}
				}
			}

			// If no type
			if ($type <= 0)
			{
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Type")), null, 'errors');
				$error++;
				$action = 'create';
			}

			// If no start date
			if (empty($date_debut))
			{
				setEventMessages($langs->trans("NoDateDebut"), null, 'errors');
				$error++;
				$action = 'create';
			}
			// If no end date
			if (empty($date_fin))
			{
				setEventMessages($langs->trans("NoDateFin"), null, 'errors');
				$error++;
				$action = 'create';
			}
			// If start date after end date
			if ($date_debut > $date_fin)
			{
				setEventMessages($langs->trans("ErrorEndDateCP"), null, 'errors');
				$error++;
				$action = 'create';
			}

			// Check if there is already holiday for this period
			$verifCP = $object->verifDateHolidayCP($fuserid, $date_debut, $date_fin, $halfday);
			if (!$verifCP)
			{
				setEventMessages($langs->trans("alreadyCPexist"), null, 'errors');
				$error++;
				$action = 'create';
			}

			// If there is no Business Days within request
			$nbopenedday = num_open_day($date_debut_gmt, $date_fin_gmt, 0, 1, $halfday);
			if ($nbopenedday < 0.5)
			{
				setEventMessages($langs->trans("ErrorDureeCP"), null, 'errors'); // No working day
				$error++;
				$action = 'create';
			}

			// If no validator designated
			if ($valideur < 1)
			{
				setEventMessages($langs->transnoentitiesnoconv('InvalidValidatorCP'), null, 'errors');
				$error++;
			}

			$result = 0;

			if (!$error)
			{
				$object->fk_user = $fuserid;
				$object->description = $description;
				$object->fk_validator = $valideur;
				$object->fk_type = $type;
				$object->date_debut = $date_debut;
				$object->date_fin = $date_fin;
				$object->halfday = $halfday;

				$result = $object->create($user);
				if ($result <= 0)
				{
					setEventMessages($object->error, $object->errors, 'errors');
					$error++;
				}
			}

			// If no SQL error we redirect to the request card
			if (!$error)
			{
				$db->commit();

				header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
				exit;
			} else {
				$db->rollback();
			}
		}
	}

	if ($action == 'update' && GETPOSTISSET('savevalidator') && !empty($user->rights->holiday->approve))
	{
		$object->fetch($id);

		$object->oldcopy = dol_clone($object);

		$object->fk_validator = GETPOST('valideur', 'int');

		if ($object->fk_validator != $object->oldcopy->fk_validator)
		{
			$verif = $object->update($user);

			if ($verif <= 0)
			{
				setEventMessages($object->error, $object->errors, 'warnings');
				$action = 'editvalidator';
			} else {
				header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
				exit;
			}
		}

		$action = '';
	}

	if ($action == 'update' && !GETPOSTISSET('savevalidator'))
	{
		$date_debut = dol_mktime(0, 0, 0, GETPOST('date_debut_month'), GETPOST('date_debut_day'), GETPOST('date_debut_year'));
		$date_fin = dol_mktime(0, 0, 0, GETPOST('date_fin_month'), GETPOST('date_fin_day'), GETPOST('date_fin_year'));
		$date_debut_gmt = dol_mktime(0, 0, 0, GETPOST('date_debut_month'), GETPOST('date_debut_day'), GETPOST('date_debut_year'), 1);
		$date_fin_gmt = dol_mktime(0, 0, 0, GETPOST('date_fin_month'), GETPOST('date_fin_day'), GETPOST('date_fin_year'), 1);
		$starthalfday = GETPOST('starthalfday');
		$endhalfday = GETPOST('endhalfday');
		$halfday = 0;
		if ($starthalfday == 'afternoon' && $endhalfday == 'morning') $halfday = 2;
		elseif ($starthalfday == 'afternoon') $halfday = -1;
		elseif ($endhalfday == 'morning') $halfday = 1;

		// If no right to modify a request
		if (!$user->rights->holiday->write)
		{
			setEventMessages($langs->trans("CantUpdate"), null, 'errors');
			header('Location: '.$_SERVER["PHP_SELF"].'?action=create');
			exit;
		}

		$object->fetch($id);

		// If under validation
		if ($object->statut == Holiday::STATUS_DRAFT)
		{
			// If this is the requestor or has read/write rights
			if ($cancreate)
			{
				$valideur = GETPOST('valideur', 'int');
				$description = trim(GETPOST('description', 'restricthtml'));

				// If no start date
				if (empty($_POST['date_debut_'])) {
					header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&error=nodatedebut');
					exit;
				}

				// If no end date
				if (empty($_POST['date_fin_'])) {
					header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&error=nodatefin');
					exit;
				}

				// If start date after end date
				if ($date_debut > $date_fin) {
					header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&error=datefin');
					exit;
				}

				// If no validator designated
				if ($valideur < 1) {
					header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&error=Valideur');
					exit;
				}

				// If there is no Business Days within request
				$nbopenedday = num_open_day($date_debut_gmt, $date_fin_gmt, 0, 1, $halfday);
				if ($nbopenedday < 0.5)
				{
					header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&error=DureeHoliday');
					exit;
				}

				$object->description = $description;
				$object->date_debut = $date_debut;
				$object->date_fin = $date_fin;
				$object->fk_validator = $valideur;
				$object->halfday = $halfday;

				// Update
				$verif = $object->update($user);

				if ($verif <= 0)
				{
					setEventMessages($object->error, $object->errors, 'warnings');
					$action = 'edit';
				} else {
					header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
					exit;
				}
			} else {
				setEventMessages($langs->trans("NotEnoughPermissions"), null, 'errors');
				$action = '';
			}
		} else {
			setEventMessages($langs->trans("ErrorBadStatus"), null, 'errors');
			$action = '';
		}
	}

	// If delete of request
	if ($action == 'confirm_delete' && GETPOST('confirm') == 'yes' && $user->rights->holiday->delete)
	{
		$error = 0;

		$db->begin();

		$object->fetch($id);

		// If this is a rough draft, approved, canceled or refused
		if ($object->statut == Holiday::STATUS_DRAFT || $object->statut == Holiday::STATUS_CANCELED || $object->statut == Holiday::STATUS_REFUSED)
		{
			// Si l'utilisateur à le droit de lire cette demande, il peut la supprimer
			if ($candelete)
			{
				$result = $object->delete($user);
			} else {
				$error++;
				setEventMessages($langs->trans('ErrorCantDeleteCP'), null, 'errors');
				$action = '';
			}
		}

		if (!$error)
		{
			$db->commit();
			header('Location: list.php?restore_lastsearch_values=1');
			exit;
		} else {
			$db->rollback();
		}
	}

	// Action validate (+ send email for approval)
	if ($action == 'confirm_send')
	{
		$object->fetch($id);

		// If draft and owner of leave
		if ($object->statut == Holiday::STATUS_DRAFT && $cancreate)
		{
			$object->oldcopy = dol_clone($object);

			$object->statut = Holiday::STATUS_VALIDATED;

			$verif = $object->validate($user);

			// Si pas d'erreur SQL on redirige vers la fiche de la demande
			if ($verif > 0)
			{
				// To
				$destinataire = new User($db);
				$destinataire->fetch($object->fk_validator);
				$emailTo = $destinataire->email;

				if (!$emailTo)
				{
					dol_syslog("Expected validator has no email, so we redirect directly to finished page without sending email");
					header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
					exit;
				}

				// From
				$expediteur = new User($db);
				$expediteur->fetch($object->fk_user);
				//$emailFrom = $expediteur->email;		Email of user can be an email into another company. Sending will fails, we must use the generic email.
				$emailFrom = $conf->global->MAIN_MAIL_EMAIL_FROM;

				// Subject
				$societeName = $conf->global->MAIN_INFO_SOCIETE_NOM;
				if (!empty($conf->global->MAIN_APPLICATION_TITLE)) $societeName = $conf->global->MAIN_APPLICATION_TITLE;

				$subject = $societeName." - ".$langs->transnoentitiesnoconv("HolidaysToValidate");

				// Content
				$message = $langs->transnoentitiesnoconv("Hello")." ".$destinataire->firstname.",\n";
				$message .= "\n";

				$message .= $langs->transnoentities("HolidaysToValidateBody")."\n";

				$delayForRequest = $object->getConfCP('delayForRequest');
				//$delayForRequest = $delayForRequest * (60*60*24);

				$nextMonth = dol_time_plus_duree($now, $delayForRequest, 'd');

				// Si l'option pour avertir le valideur en cas de délai trop court
				if ($object->getConfCP('AlertValidatorDelay'))
				{
					if ($object->date_debut < $nextMonth)
					{
						$message .= "\n";
						$message .= $langs->transnoentities("HolidaysToValidateDelay", $object->getConfCP('delayForRequest'))."\n";
					}
				}

				// Si l'option pour avertir le valideur en cas de solde inférieur à la demande
				if ($object->getConfCP('AlertValidatorSolde'))
				{
					$nbopenedday = num_open_day($object->date_debut_gmt, $object->date_fin_gmt, 0, 1, $object->halfday);
					if ($nbopenedday > $object->getCPforUser($object->fk_user, $object->fk_type))
					{
						$message .= "\n";
						$message .= $langs->transnoentities("HolidaysToValidateAlertSolde")."\n";
					}
				}

				$message .= "\n";
				$message .= "- ".$langs->transnoentitiesnoconv("Name")." : ".dolGetFirstLastname($expediteur->firstname, $expediteur->lastname)."\n";
				$message .= "- ".$langs->transnoentitiesnoconv("Period")." : ".dol_print_date($object->date_debut, 'day')." ".$langs->transnoentitiesnoconv("To")." ".dol_print_date($object->date_fin, 'day')."\n";
				$message .= "- ".$langs->transnoentitiesnoconv("Link")." : ".$dolibarr_main_url_root."/holiday/card.php?id=".$object->id."\n\n";
				$message .= "\n";

				$trackid = 'leav'.$object->id;

				$mail = new CMailFile($subject, $emailTo, $emailFrom, $message, array(), array(), array(), '', '', 0, 0, '', '', $trackid);

				// Envoi du mail
				$result = $mail->sendfile();

				if (!$result)
				{
					setEventMessages($mail->error, $mail->errors, 'warnings');
					$action = '';
				} else {
					header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
					exit;
				}
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
				$action = '';
			}
		}
	}

	if ($action == 'update_extras')
	{
		$object->oldcopy = dol_clone($object);

		// Fill array 'array_options' with data from update form
		$ret = $extrafields->setOptionalsFromPost(null, $object, GETPOST('attribute', 'restricthtml'));
		if ($ret < 0) $error++;

		if (!$error)
		{
			// Actions on extra fields
			$result = $object->insertExtraFields('HOLIDAY_MODIFY');
			if ($result < 0)
			{
				setEventMessages($object->error, $object->errors, 'errors');
				$error++;
			}
		}

		if ($error)
			$action = 'edit_extras';
	}

	// Approve leave request
	if ($action == 'confirm_valid')
	{
		$object->fetch($id);

		// If status is waiting approval and approver is also user
		if ($object->statut == Holiday::STATUS_VALIDATED && $user->id == $object->fk_validator)
		{
			$object->oldcopy = dol_clone($object);

			$object->date_valid = dol_now();
			$object->fk_user_valid = $user->id;
			$object->statut = Holiday::STATUS_APPROVED;

			$db->begin();

			$verif = $object->approve($user);
			if ($verif <= 0)
			{
				setEventMessages($object->error, $object->errors, 'errors');
				$error++;
			}

			// Si pas d'erreur SQL on redirige vers la fiche de la demande
			if (!$error)
			{
				// Calculcate number of days consummed
				$nbopenedday = num_open_day($object->date_debut_gmt, $object->date_fin_gmt, 0, 1, $object->halfday);
				$soldeActuel = $object->getCpforUser($object->fk_user, $object->fk_type);
				$newSolde = ($soldeActuel - $nbopenedday);

				// On ajoute la modification dans le LOG
				$result = $object->addLogCP($user->id, $object->fk_user, $langs->transnoentitiesnoconv("Holidays"), $newSolde, $object->fk_type);
				if ($result < 0)
				{
					$error++;
					setEventMessages(null, $object->errors, 'errors');
				}

				//Update balance
				$result = $object->updateSoldeCP($object->fk_user, $newSolde, $object->fk_type);
				if ($result < 0)
				{
					$error++;
					setEventMessages(null, $object->errors, 'errors');
				}
			}

			if (!$error)
			{
				// To
				$destinataire = new User($db);
				$destinataire->fetch($object->fk_user);
				$emailTo = $destinataire->email;

				if (!$emailTo)
				{
					dol_syslog("User that request leave has no email, so we redirect directly to finished page without sending email");
				} else {
					// From
					$expediteur = new User($db);
					$expediteur->fetch($object->fk_validator);
					//$emailFrom = $expediteur->email;		Email of user can be an email into another company. Sending will fails, we must use the generic email.
					$emailFrom = $conf->global->MAIN_MAIL_EMAIL_FROM;

					// Subject
					$societeName = $conf->global->MAIN_INFO_SOCIETE_NOM;
					if (!empty($conf->global->MAIN_APPLICATION_TITLE)) $societeName = $conf->global->MAIN_APPLICATION_TITLE;

					$subject = $societeName." - ".$langs->transnoentitiesnoconv("HolidaysValidated");

					// Content
					$message = $langs->transnoentitiesnoconv("Hello")." ".$destinataire->firstname.",\n";
					$message .= "\n";

					$message .= $langs->transnoentities("HolidaysValidatedBody", dol_print_date($object->date_debut, 'day'), dol_print_date($object->date_fin, 'day'))."\n";

					$message .= "- ".$langs->transnoentitiesnoconv("ValidatedBy")." : ".dolGetFirstLastname($expediteur->firstname, $expediteur->lastname)."\n";

					$message .= "- ".$langs->transnoentitiesnoconv("Link")." : ".$dolibarr_main_url_root."/holiday/card.php?id=".$object->id."\n\n";
					$message .= "\n";

					$trackid = 'leav'.$object->id;

					$mail = new CMailFile($subject, $emailTo, $emailFrom, $message, array(), array(), array(), '', '', 0, 0, '', '', $trackid);

					// Envoi du mail
					$result = $mail->sendfile();

					if (!$result)
					{
						setEventMessages($mail->error, $mail->errors, 'warnings'); // Show error, but do no make rollback, so $error is not set to 1
						$action = '';
					}
				}
			}

			if (!$error)
			{
				$db->commit();

			   	header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
			   	exit;
			} else {
				$db->rollback();
				$action = '';
			}
		}
	}

	if ($action == 'confirm_refuse' && GETPOST('confirm', 'alpha') == 'yes')
	{
		if (!empty($_POST['detail_refuse']))
		{
			$object->fetch($id);

			// Si statut en attente de validation et valideur = utilisateur
			if ($object->statut == Holiday::STATUS_VALIDATED && $user->id == $object->fk_validator)
			{
				$object->date_refuse = dol_print_date('dayhour', dol_now());
				$object->fk_user_refuse = $user->id;
				$object->statut = Holiday::STATUS_REFUSED;
				$object->detail_refuse = GETPOST('detail_refuse', 'alphanohtml');

				$db->begin();

				$verif = $object->update($user);
				if ($verif <= 0)
				{
					$error++;
					setEventMessages($object->error, $object->errors, 'errors');
				}

				// Si pas d'erreur SQL on redirige vers la fiche de la demande
				if (!$error)
				{
					// To
					$destinataire = new User($db);
					$destinataire->fetch($object->fk_user);
					$emailTo = $destinataire->email;

					if (!$emailTo)
					{
						dol_syslog("User that request leave has no email, so we redirect directly to finished page without sending email");
					} else {
						// From
						$expediteur = new User($db);
						$expediteur->fetch($object->fk_validator);
						//$emailFrom = $expediteur->email;		Email of user can be an email into another company. Sending will fails, we must use the generic email.
						$emailFrom = $conf->global->MAIN_MAIL_EMAIL_FROM;

						// Subject
						$societeName = $conf->global->MAIN_INFO_SOCIETE_NOM;
						if (!empty($conf->global->MAIN_APPLICATION_TITLE)) $societeName = $conf->global->MAIN_APPLICATION_TITLE;

						$subject = $societeName." - ".$langs->transnoentitiesnoconv("HolidaysRefused");

						// Content
						$message = $langs->transnoentitiesnoconv("Hello")." ".$destinataire->firstname.",\n";
						$message .= "\n";

						$message .= $langs->transnoentities("HolidaysRefusedBody", dol_print_date($object->date_debut, 'day'), dol_print_date($object->date_fin, 'day'))."\n";
						$message .= GETPOST('detail_refuse', 'alpha')."\n\n";

						$message .= "- ".$langs->transnoentitiesnoconv("ModifiedBy")." : ".dolGetFirstLastname($expediteur->firstname, $expediteur->lastname)."\n";

						$message .= "- ".$langs->transnoentitiesnoconv("Link")." : ".$dolibarr_main_url_root."/holiday/card.php?id=".$object->id."\n\n";
						$message .= "\n";

						$trackid = 'leav'.$object->id;

						$mail = new CMailFile($subject, $emailTo, $emailFrom, $message, array(), array(), array(), '', '', 0, 0, '', '', $trackid);

						// Envoi du mail
						$result = $mail->sendfile();

						if (!$result)
						{
							setEventMessages($mail->error, $mail->errors, 'warnings'); // Show error, but do no make rollback, so $error is not set to 1
							$action = '';
						}
					}
				} else {
					$action = '';
				}

				if (!$error)
				{
					$db->commit();

					header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
					exit;
				} else {
					$db->rollback();
					$action = '';
				}
			}
		} else {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("DetailRefusCP")), null, 'errors');
			$action = 'refuse';
		}
	}


	// Si Validation de la demande
	if ($action == 'confirm_draft' && GETPOST('confirm') == 'yes')
	{
		$error = 0;

		$object->fetch($id);

		$oldstatus = $object->statut;
		$object->statut = Holiday::STATUS_DRAFT;

		$result = $object->update($user);
		if ($result < 0)
		{
			$error++;
			setEventMessages($langs->trans('ErrorBackToDraft').' '.$object->error, $object->errors, 'errors');
		}

		if (!$error)
		{
			$db->commit();

			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
			exit;
		} else {
			$db->rollback();
		}
	}

	// Si confirmation of cancellation
	if ($action == 'confirm_cancel' && GETPOST('confirm') == 'yes')
	{
		$error = 0;

		$object->fetch($id);

		// Si statut en attente de validation et valideur = valideur ou utilisateur, ou droits de faire pour les autres
		if (($object->statut == Holiday::STATUS_VALIDATED || $object->statut == Holiday::STATUS_APPROVED) && ($user->id == $object->fk_validator || in_array($object->fk_user, $childids)
			|| (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->holiday->writeall_advance))))
		{
			$db->begin();

			$oldstatus = $object->statut;
			$object->date_cancel = dol_now();
			$object->fk_user_cancel = $user->id;
			$object->statut = Holiday::STATUS_CANCELED;

			$result = $object->update($user);

			if ($result >= 0 && $oldstatus == Holiday::STATUS_APPROVED)	// holiday was already validated, status 3, so we must increase back the balance
			{
				// Calculcate number of days consummed
				$nbopenedday = num_open_day($object->date_debut_gmt, $object->date_fin_gmt, 0, 1, $object->halfday);

				$soldeActuel = $object->getCpforUser($object->fk_user, $object->fk_type);
				$newSolde = ($soldeActuel + $nbopenedday);

				// On ajoute la modification dans le LOG
				$result1 = $object->addLogCP($user->id, $object->fk_user, $langs->transnoentitiesnoconv("HolidaysCancelation"), $newSolde, $object->fk_type);

				// Mise à jour du solde
				$result2 = $object->updateSoldeCP($object->fk_user, $newSolde, $object->fk_type);

				if ($result1 < 0 || $result2 < 0)
				{
					$error++;
					setEventMessages($langs->trans('ErrorCantDeleteCP').' '.$object->error, $object->errors, 'errors');
				}
			}

			if (!$error)
			{
				$db->commit();
			} else {
				$db->rollback();
			}

			// Si pas d'erreur SQL on redirige vers la fiche de la demande
			if (!$error && $result > 0)
			{
				// To
				$destinataire = new User($db);
				$destinataire->fetch($object->fk_user);
				$emailTo = $destinataire->email;

				if (!$emailTo)
				{
					header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
					exit;
				}

				// From
				$expediteur = new User($db);
				$expediteur->fetch($object->fk_user_cancel);
				//$emailFrom = $expediteur->email;		Email of user can be an email into another company. Sending will fails, we must use the generic email.
				$emailFrom = $conf->global->MAIN_MAIL_EMAIL_FROM;

				// Subject
				$societeName = $conf->global->MAIN_INFO_SOCIETE_NOM;
				if (!empty($conf->global->MAIN_APPLICATION_TITLE)) $societeName = $conf->global->MAIN_APPLICATION_TITLE;

				$subject = $societeName." - ".$langs->transnoentitiesnoconv("HolidaysCanceled");

				// Content
			   	$message = $langs->transnoentitiesnoconv("Hello")." ".$destinataire->firstname.",\n";
				$message .= "\n";

				$message .= $langs->transnoentities("HolidaysCanceledBody", dol_print_date($object->date_debut, 'day'), dol_print_date($object->date_fin, 'day'))."\n";
				$message .= "- ".$langs->transnoentitiesnoconv("ModifiedBy")." : ".dolGetFirstLastname($expediteur->firstname, $expediteur->lastname)."\n";

				$message .= "- ".$langs->transnoentitiesnoconv("Link")." : ".$dolibarr_main_url_root."/holiday/card.php?id=".$object->id."\n\n";
				$message .= "\n";

				$trackid = 'leav'.$object->id;

				$mail = new CMailFile($subject, $emailTo, $emailFrom, $message, array(), array(), array(), '', '', 0, 0, '', '', $trackid);

				// Envoi du mail
				$result = $mail->sendfile();

				if (!$result)
				{
					setEventMessages($mail->error, $mail->errors, 'warnings');
					$action = '';
				} else {
					header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
					exit;
				}
			}
		}
	}

	/*
	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Actions to send emails
	$triggersendname = 'HOLIDAY_SENTBYMAIL';
	$autocopy='MAIN_MAIL_AUTOCOPY_HOLIDAY_TO';
	$trackid='leav'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';

	// Actions to build doc
	$upload_dir = $conf->holiday->dir_output;
	$permissiontoadd = $user->rights->holiday->creer;
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';
	*/
}



/*
 * View
 */

$form = new Form($db);
$object = new Holiday($db);

$listhalfday = array('morning'=>$langs->trans("Morning"), "afternoon"=>$langs->trans("Afternoon"));

llxHeader('', $langs->trans('CPTitreMenu'));

if ((empty($id) && empty($ref)) || $action == 'create' || $action == 'add')
{
	// If user has no permission to create a leave
	if ((in_array($fuserid, $childids) && empty($user->rights->holiday->write)) || (!in_array($fuserid, $childids) && (empty($conf->global->MAIN_USE_ADVANCED_PERMS) || empty($user->rights->holiday->writeall_advance))))
	{
		$errors[] = $langs->trans('CantCreateCP');
	} else {
		// Form to add a leave request
		print load_fiche_titre($langs->trans('MenuAddCP'), '', 'title_hrm.png');

		// Error management
		if (GETPOST('error')) {
			switch (GETPOST('error')) {
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
				case 'nodatefin' :
					$errors[] = $langs->trans('NoDateFin');
					break;
				case 'DureeHoliday' :
					$errors[] = $langs->trans('ErrorDureeCP');
					break;
				case 'alreadyCP' :
					$errors[] = $langs->trans('alreadyCPexist');
					break;
			}

			setEventMessages($errors, null, 'errors');
		}


		$delayForRequest = $object->getConfCP('delayForRequest');
		//$delayForRequest = $delayForRequest * (60*60*24);

		$nextMonth = dol_time_plus_duree($now, $delayForRequest, 'd');

		print '<script type="text/javascript">
	    function valider()
	    {
    	    if(document.demandeCP.date_debut_.value != "")
    	    {
	           	if(document.demandeCP.date_fin_.value != "")
	           	{
	               if(document.demandeCP.valideur.value != "-1") {
	                 return true;
	               }
	               else {
	                 alert("'.dol_escape_js($langs->transnoentities('InvalidValidatorCP')).'");
	                 return false;
	               }
	            }
	            else
	            {
	              alert("'.dol_escape_js($langs->transnoentities('NoDateFin')).'");
	              return false;
	            }
	        }
	        else
	        {
	           alert("'.dol_escape_js($langs->transnoentities('NoDateDebut')).'");
	           return false;
	        }
       	}
       </script>'."\n";

		// Formulaire de demande
		print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'" onsubmit="return valider()" name="demandeCP">'."\n";
		print '<input type="hidden" name="token" value="'.newToken().'" />'."\n";
		print '<input type="hidden" name="action" value="add" />'."\n";

		if (empty($conf->global->HOLIDAY_HIDE_BALANCE)) {
			print dol_get_fiche_head('', '', '', -1);

			$out = '';
			$typeleaves = $object->getTypes(1, 1);
			foreach ($typeleaves as $key => $val)
			{
				$nb_type = $object->getCPforUser($user->id, $val['rowid']);
				$nb_holiday += $nb_type;

				$out .= ' - '.($langs->trans($val['code']) != $val['code'] ? $langs->trans($val['code']) : $val['label']).': <strong>'.($nb_type ? price2num($nb_type) : 0).'</strong><br>';
				//$out .= ' - '.$val['label'].': <strong>'.($nb_type ?price2num($nb_type) : 0).'</strong><br>';
			}
			print $langs->trans('SoldeCPUser', round($nb_holiday, 5)).'<br>';
			print $out;

			print dol_get_fiche_end();
		} elseif (!is_numeric($conf->global->HOLIDAY_HIDE_BALANCE)) {
			print $langs->trans($conf->global->HOLIDAY_HIDE_BALANCE).'<br>';
		}

		print dol_get_fiche_head();

		//print '<span>'.$langs->trans('DelayToRequestCP',$object->getConfCP('delayForRequest')).'</span><br><br>';

		print '<table class="border centpercent">';
		print '<tbody>';

		// User for leave request
		print '<tr>';
		print '<td class="titlefield fieldrequired">'.$langs->trans("User").'</td>';
		print '<td>';

		if (empty($conf->global->MAIN_USE_ADVANCED_PERMS) || empty($user->rights->holiday->writeall_advance))
		{
			print img_picto('', 'user').$form->select_dolusers(($fuserid ? $fuserid : $user->id), 'fuserid', 0, '', 0, 'hierarchyme', '', '0,'.$conf->entity, 0, 0, $morefilter, 0, '', 'minwidth200 maxwidth500');
			//print '<input type="hidden" name="fuserid" value="'.($fuserid?$fuserid:$user->id).'">';
		} else {
			print img_picto('', 'user').$form->select_dolusers(GETPOST('fuserid', 'int') ? GETPOST('fuserid', 'int') : $user->id, 'fuserid', 0, '', 0, '', '', '0,'.$conf->entity, 0, 0, $morefilter, 0, '', 'minwidth200 maxwidth500');
		}
		print '</td>';
		print '</tr>';

		// Type
		print '<tr>';
		print '<td class="fieldrequired">'.$langs->trans("Type").'</td>';
		print '<td>';
		$typeleaves = $object->getTypes(1, -1);
		$arraytypeleaves = array();
		foreach ($typeleaves as $key => $val)
		{
			$labeltoshow = ($langs->trans($val['code']) != $val['code'] ? $langs->trans($val['code']) : $val['label']);
			$labeltoshow .= ($val['delay'] > 0 ? ' ('.$langs->trans("NoticePeriod").': '.$val['delay'].' '.$langs->trans("days").')' : '');
			$arraytypeleaves[$val['rowid']] = $labeltoshow;
		}
		print $form->selectarray('type', $arraytypeleaves, (GETPOST('type', 'alpha') ?GETPOST('type', 'alpha') : ''), 1, 0, 0, '', 0, 0, 0, '', '', true);
		if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
		print '</td>';
		print '</tr>';

		// Date start
		print '<tr>';
		print '<td class="fieldrequired">';
		print $form->textwithpicto($langs->trans("DateDebCP"), $langs->trans("FirstDayOfHoliday"));
		print '</td>';
		print '<td>';
		// Si la demande ne vient pas de l'agenda
		if (!GETPOST('date_debut_')) {
			print $form->selectDate(-1, 'date_debut_', 0, 0, 0, '', 1, 1);
		} else {
			$tmpdate = dol_mktime(0, 0, 0, GETPOST('date_debut_month', 'int'), GETPOST('date_debut_day', 'int'), GETPOST('date_debut_year', 'int'));
			print $form->selectDate($tmpdate, 'date_debut_', 0, 0, 0, '', 1, 1);
		}
		print ' &nbsp; &nbsp; ';
		print $form->selectarray('starthalfday', $listhalfday, (GETPOST('starthalfday', 'alpha') ?GETPOST('starthalfday', 'alpha') : 'morning'));
		print '</td>';
		print '</tr>';

		// Date end
		print '<tr>';
		print '<td class="fieldrequired">';
		print $form->textwithpicto($langs->trans("DateFinCP"), $langs->trans("LastDayOfHoliday"));
		print '</td>';
		print '<td>';
		if (!GETPOST('date_fin_')) {
			print $form->selectDate(-1, 'date_fin_', 0, 0, 0, '', 1, 1);
		} else {
			$tmpdate = dol_mktime(0, 0, 0, GETPOST('date_fin_month', 'int'), GETPOST('date_fin_day', 'int'), GETPOST('date_fin_year', 'int'));
			print $form->selectDate($tmpdate, 'date_fin_', 0, 0, 0, '', 1, 1);
		}
		print ' &nbsp; &nbsp; ';
		print $form->selectarray('endhalfday', $listhalfday, (GETPOST('endhalfday', 'alpha') ?GETPOST('endhalfday', 'alpha') : 'afternoon'));
		print '</td>';
		print '</tr>';

		// Approver
		print '<tr>';
		print '<td class="fieldrequired">'.$langs->trans("ReviewedByCP").'</td>';
		print '<td>';

		$object = new Holiday($db);
		$include_users = $object->fetch_users_approver_holiday();
		if (empty($include_users)) {
			print img_warning().' '.$langs->trans("NobodyHasPermissionToValidateHolidays");
		} else {
			$defaultselectuser = (empty($user->fk_user_holiday_validator) ? $user->fk_user : $user->fk_user_holiday_validator); // Will work only if supervisor has permission to approve so is inside include_users
			if (!empty($conf->global->HOLIDAY_DEFAULT_VALIDATOR)) $defaultselectuser = $conf->global->HOLIDAY_DEFAULT_VALIDATOR; // Can force default approver
			if (GETPOST('valideur', 'int') > 0) $defaultselectuser = GETPOST('valideur', 'int');
			$s = $form->select_dolusers($defaultselectuser, "valideur", 1, '', 0, $include_users, '', '0,'.$conf->entity, 0, 0, '', 0, '', 'minwidth200 maxwidth500');
			print img_picto('', 'user').$form->textwithpicto($s, $langs->trans("AnyOtherInThisListCanValidate"));
		}

		//print $form->select_dolusers((GETPOST('valideur','int')>0?GETPOST('valideur','int'):$user->fk_user), "valideur", 1, ($user->admin ? '' : array($user->id)), 0, '', 0, 0, 0, 0, '', 0, '', '', 1);	// By default, hierarchical parent
		print '</td>';
		print '</tr>';

		// Description
		print '<tr>';
		print '<td>'.$langs->trans("DescCP").'</td>';
		print '<td class="tdtop">';
		$doleditor = new DolEditor('description', GETPOST('description', 'restricthtml'), '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, '90%');
		print $doleditor->Create(1);
		print '</td></tr>';

		// Other attributes
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

		print '</tbody>';
		print '</table>';

		print dol_get_fiche_end();

		print '<div class="center">';
		print '<input type="submit" value="'.$langs->trans("SendRequestCP").'" name="bouton" class="button">';
		print '&nbsp; &nbsp; ';
		print '<input type="button" value="'.$langs->trans("Cancel").'" class="button button-cancel" onclick="history.go(-1)">';
		print '</div>';

		print '</from>'."\n";
	}
} else {
	if ($error) {
		print '<div class="tabBar">';
		print $error;
		print '<br><br><input type="button" value="'.$langs->trans("ReturnCP").'" class="button" onclick="history.go(-1)" />';
		print '</div>';
	} else {
		// Affichage de la fiche d'une demande de congés payés
		if (($id > 0) || $ref)
		{
			$result = $object->fetch($id, $ref);

			$valideur = new User($db);
			$valideur->fetch($object->fk_validator);

			$userRequest = new User($db);
			$userRequest->fetch($object->fk_user);

			//print load_fiche_titre($langs->trans('TitreRequestCP'));

			// Si il y a une erreur
			if (GETPOST('error'))
			{
				switch (GETPOST('error'))
				{
					case 'datefin' :
						$errors[] = $langs->transnoentitiesnoconv('ErrorEndDateCP');
						break;
					case 'SQL_Create' :
						$errors[] = $langs->transnoentitiesnoconv('ErrorSQLCreateCP').' '.$_GET['msg'];
						break;
					case 'CantCreate' :
						$errors[] = $langs->transnoentitiesnoconv('CantCreateCP');
						break;
					case 'Valideur' :
						$errors[] = $langs->transnoentitiesnoconv('InvalidValidatorCP');
						break;
					case 'nodatedebut' :
						$errors[] = $langs->transnoentitiesnoconv('NoDateDebut');
						break;
					case 'nodatefin' :
						$errors[] = $langs->transnoentitiesnoconv('NoDateFin');
						break;
					case 'DureeHoliday' :
						$errors[] = $langs->transnoentitiesnoconv('ErrorDureeCP');
						break;
					case 'NoMotifRefuse' :
						$errors[] = $langs->transnoentitiesnoconv('NoMotifRefuseCP');
						break;
					case 'mail' :
						$errors[] = $langs->transnoentitiesnoconv('ErrorMailNotSend')."\n".$_GET['error_content'];
						break;
				}

				setEventMessages($errors, null, 'errors');
			}

			// On vérifie si l'utilisateur à le droit de lire cette demande
			if ($cancreate)
			{
				$head = holiday_prepare_head($object);

				if (($action == 'edit' && $object->statut == Holiday::STATUS_DRAFT) || ($action == 'editvalidator'))
				{
					if ($action == 'edit' && $object->statut == Holiday::STATUS_DRAFT) $edit = true;

					print '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">'."\n";
					print '<input type="hidden" name="token" value="'.newToken().'" />'."\n";
					print '<input type="hidden" name="action" value="update"/>'."\n";
					print '<input type="hidden" name="id" value="'.$object->id.'" />'."\n";
				}

				print dol_get_fiche_head($head, 'card', $langs->trans("CPTitreMenu"), -1, 'holiday');

				$linkback = '<a href="'.DOL_URL_ROOT.'/holiday/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

				dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref');


				print '<div class="fichecenter">';
				print '<div class="fichehalfleft">';
				print '<div class="underbanner clearboth"></div>';

				print '<table class="border tableforfield centpercent">';
				print '<tbody>';

				// User
				print '<tr>';
				print '<td class="titlefield">'.$langs->trans("User").'</td>';
				print '<td>';
				print $userRequest->getNomUrl(-1, 'leave');
				print '</td></tr>';

				// Type
				print '<tr>';
				print '<td>'.$langs->trans("Type").'</td>';
				print '<td>';
				$typeleaves = $object->getTypes(1, -1);
				$labeltoshow = (($typeleaves[$object->fk_type]['code'] && $langs->trans($typeleaves[$object->fk_type]['code']) != $typeleaves[$object->fk_type]['code']) ? $langs->trans($typeleaves[$object->fk_type]['code']) : $typeleaves[$object->fk_type]['label']);
				print empty($labeltoshow) ? $langs->trans("TypeWasDisabledOrRemoved", $object->fk_type) : $labeltoshow;
				print '</td>';
				print '</tr>';

				$starthalfday = ($object->halfday == -1 || $object->halfday == 2) ? 'afternoon' : 'morning';
				$endhalfday = ($object->halfday == 1 || $object->halfday == 2) ? 'morning' : 'afternoon';

				if (!$edit)
				{
					print '<tr>';
					print '<td class="nowrap">';
					print $form->textwithpicto($langs->trans('DateDebCP'), $langs->trans("FirstDayOfHoliday"));
					print '</td>';
					print '<td>'.dol_print_date($object->date_debut, 'day');
					print ' &nbsp; &nbsp; ';
					print '<span class="opacitymedium">'.$langs->trans($listhalfday[$starthalfday]).'</span>';
					print '</td>';
					print '</tr>';
				} else {
					print '<tr>';
					print '<td class="nowrap">';
					print $form->textwithpicto($langs->trans('DateDebCP'), $langs->trans("FirstDayOfHoliday"));
					print '</td>';
					print '<td>';
					print $form->selectDate($object->date_debut, 'date_debut_');
					print ' &nbsp; &nbsp; ';
					print $form->selectarray('starthalfday', $listhalfday, (GETPOST('starthalfday') ?GETPOST('starthalfday') : $starthalfday));
					print '</td>';
					print '</tr>';
				}

				if (!$edit)
				{
					print '<tr>';
					print '<td class="nowrap">';
					print $form->textwithpicto($langs->trans('DateFinCP'), $langs->trans("LastDayOfHoliday"));
					print '</td>';
					print '<td>'.dol_print_date($object->date_fin, 'day');
					print ' &nbsp; &nbsp; ';
					print '<span class="opacitymedium">'.$langs->trans($listhalfday[$endhalfday]).'</span>';
					print '</td>';
					print '</tr>';
				} else {
					print '<tr>';
					print '<td class="nowrap">';
					print $form->textwithpicto($langs->trans('DateFinCP'), $langs->trans("LastDayOfHoliday"));
					print '</td>';
					print '<td>';
					print $form->selectDate($object->date_fin, 'date_fin_');
					print ' &nbsp; &nbsp; ';
					print $form->selectarray('endhalfday', $listhalfday, (GETPOST('endhalfday') ?GETPOST('endhalfday') : $endhalfday));
					print '</td>';
					print '</tr>';
				}

				// Nb of days
				print '<tr>';
				print '<td>';
				$htmlhelp = $langs->trans('NbUseDaysCPHelp');
				$includesaturday = (isset($conf->global->MAIN_NON_WORKING_DAYS_INCLUDE_SATURDAY) ? $conf->global->MAIN_NON_WORKING_DAYS_INCLUDE_SATURDAY : 1);
				$includesunday   = (isset($conf->global->MAIN_NON_WORKING_DAYS_INCLUDE_SUNDAY) ? $conf->global->MAIN_NON_WORKING_DAYS_INCLUDE_SUNDAY : 1);
				if ($includesaturday) $htmlhelp .= '<br>'.$langs->trans("DayIsANonWorkingDay", $langs->trans("Saturday"));
				if ($includesunday) $htmlhelp .= '<br>'.$langs->trans("DayIsANonWorkingDay", $langs->trans("Sunday"));
				print $form->textwithpicto($langs->trans('NbUseDaysCP'), $htmlhelp);
				print '</td>';
				print '<td>';
				print num_open_day($object->date_debut_gmt, $object->date_fin_gmt, 0, 1, $object->halfday);
				print '</td>';
				print '</tr>';

				if ($object->statut == Holiday::STATUS_REFUSED)
				{
					print '<tr>';
					print '<td>'.$langs->trans('DetailRefusCP').'</td>';
					print '<td>'.$object->detail_refuse.'</td>';
					print '</tr>';
				}

				// Description
				if (!$edit)
				{
					print '<tr>';
					print '<td>'.$langs->trans('DescCP').'</td>';
					print '<td>'.nl2br($object->description).'</td>';
					print '</tr>';
				} else {
					print '<tr>';
					print '<td>'.$langs->trans('DescCP').'</td>';
					print '<td class="tdtop">';
					$doleditor = new DolEditor('description', $object->description, '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, '90%');
					print $doleditor->Create(1);
					print '</td></tr>';
				}

				// Other attributes
				include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

				print '</tbody>';
				print '</table>'."\n";

				print '</div>';
				print '<div class="fichehalfright">';
				print '<div class="ficheaddleft">';

				print '<div class="underbanner clearboth"></div>';

				// Info workflow
				print '<table class="border tableforfield centpercent">'."\n";
				print '<tbody>';

				if (!empty($object->fk_user_create))
				{
					$userCreate = new User($db);
					$userCreate->fetch($object->fk_user_create);
					print '<tr>';
					print '<td class="titlefield">'.$langs->trans('RequestByCP').'</td>';
					print '<td>'.$userCreate->getNomUrl(-1).'</td>';
					print '</tr>';
				}

				// Approver
				if (!$edit && $action != 'editvalidator') {
					print '<tr>';
					print '<td class="titlefield">';
					if ($object->statut == Holiday::STATUS_APPROVED || $object->statut == Holiday::STATUS_CANCELED) print $langs->trans('ApprovedBy');
					else print $langs->trans('ReviewedByCP');
					print '</td>';
					print '<td>'.$valideur->getNomUrl(-1);
					$include_users = $object->fetch_users_approver_holiday();
					if (is_array($include_users) && in_array($user->id, $include_users) && $object->statut == Holiday::STATUS_VALIDATED)
					{
						print '<a class="editfielda paddingleft" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=editvalidator">'.img_edit($langs->trans("Edit")).'</a>';
					}
					print '</td>';
					print '</tr>';
				} else {
					print '<tr>';
					print '<td class="titlefield">'.$langs->trans('ReviewedByCP').'</td>';
					print '<td>';
					$include_users = $object->fetch_users_approver_holiday();
					if (!in_array($object->fk_validator, $include_users))  // Add the current validator to the list to not lose it when editing.
					{
						$include_users[] = $object->fk_validator;
					}
					if (empty($include_users)) print img_warning().' '.$langs->trans("NobodyHasPermissionToValidateHolidays");
					else {
						$arrayofvalidatorstoexclude = (($user->admin || ($user->id != $userRequest->id)) ? '' : array($user->id)); // Nobody if we are admin or if we are not the user of the leave.
						$s = $form->select_dolusers($object->fk_validator, "valideur", (($action == 'editvalidator') ? 0 : 1), $arrayofvalidatorstoexclude, 0, $include_users);
						print $form->textwithpicto($s, $langs->trans("AnyOtherInThisListCanValidate"));
					}
					if ($action == 'editvalidator')
					{
						print '<input type="submit" class="button button-save" name="savevalidator" value="'.$langs->trans("Save").'">';
						print '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
					}
					print '</td>';
					print '</tr>';
				}

				print '<tr>';
				print '<td>'.$langs->trans('DateCreation').'</td>';
				print '<td>'.dol_print_date($object->date_create, 'dayhour', 'tzuser').'</td>';
				print '</tr>';
				if ($object->statut == Holiday::STATUS_APPROVED || $object->statut == Holiday::STATUS_CANCELED) {
					print '<tr>';
					print '<td>'.$langs->trans('DateValidCP').'</td>';
					print '<td>'.dol_print_date($object->date_valid, 'dayhour', 'tzuser').'</td>'; // warning: date_valid is approval date on holiday module
					print '</tr>';
				}
				if ($object->statut == Holiday::STATUS_CANCELED) {
					print '<tr>';
					print '<td>'.$langs->trans('DateCancelCP').'</td>';
					print '<td>'.dol_print_date($object->date_cancel, 'dayhour', 'tzuser').'</td>';
					print '</tr>';
				}
				if ($object->statut == Holiday::STATUS_REFUSED) {
					print '<tr>';
					print '<td>'.$langs->trans('DateRefusCP').'</td>';
					print '<td>'.dol_print_date($object->date_refuse, 'dayhour', 'tzuser').'</td>';
					print '</tr>';
				}
				print '</tbody>';
				print '</table>';

				print '</div>';
				print '</div>';
				print '</div>';

				print '<div class="clearboth"></div>';

				print dol_get_fiche_end();


				// Confirmation messages
				if ($action == 'delete')
				{
					if ($user->rights->holiday->delete)
					{
						print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id, $langs->trans("TitleDeleteCP"), $langs->trans("ConfirmDeleteCP"), "confirm_delete", '', 0, 1);
					}
				}

				// Si envoi en validation
				if ($action == 'sendToValidate' && $object->statut == Holiday::STATUS_DRAFT)
				{
					print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id, $langs->trans("TitleToValidCP"), $langs->trans("ConfirmToValidCP"), "confirm_send", '', 1, 1);
				}

				// Si validation de la demande
				if ($action == 'valid')
				{
					print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id, $langs->trans("TitleValidCP"), $langs->trans("ConfirmValidCP"), "confirm_valid", '', 1, 1);
				}

				// Si refus de la demande
				if ($action == 'refuse')
				{
					$array_input = array(array('type'=>"text", 'label'=> $langs->trans('DetailRefusCP'), 'name'=>"detail_refuse", 'size'=>"50", 'value'=>""));
					print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id."&action=confirm_refuse", $langs->trans("TitleRefuseCP"), $langs->trans('ConfirmRefuseCP'), "confirm_refuse", $array_input, 1, 0);
				}

				// Si annulation de la demande
				if ($action == 'cancel')
				{
					print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id, $langs->trans("TitleCancelCP"), $langs->trans("ConfirmCancelCP"), "confirm_cancel", '', 1, 1);
				}

				// Si back to draft
				if ($action == 'backtodraft')
				{
					print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id, $langs->trans("TitleSetToDraft"), $langs->trans("ConfirmSetToDraft"), "confirm_draft", '', 1, 1);
				}

				if (($action == 'edit' && $object->statut == Holiday::STATUS_DRAFT) || ($action == 'editvalidator'))
				{
					if ($action == 'edit' && $object->statut == Holiday::STATUS_DRAFT)
					{
						print '<div class="center">';
						if ($cancreate && $object->statut == Holiday::STATUS_DRAFT)
						{
							print '<input type="submit" value="'.$langs->trans("Save").'" class="button button-save">';
						}
						print '</div>';
					}

					print '</form>';
				}

				if (!$edit)
				{
					// Buttons for actions

					print '<div class="tabsAction">';

					if ($cancreate && $object->statut == Holiday::STATUS_DRAFT)
					{
						print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit" class="butAction">'.$langs->trans("EditCP").'</a>';
					}
					if ($cancreate && $object->statut == Holiday::STATUS_DRAFT)		// If draft
					{
						print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=sendToValidate" class="butAction">'.$langs->trans("Validate").'</a>';
					}
					if ($object->statut == Holiday::STATUS_VALIDATED)	// If validated
					{
						if ($user->id == $object->fk_validator)
						{
							print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=valid" class="butAction">'.$langs->trans("Approve").'</a>';
							print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=refuse" class="butAction">'.$langs->trans("ActionRefuseCP").'</a>';
						} else {
							print '<a href="#" class="butActionRefused classfortooltip" title="'.$langs->trans("NotTheAssignedApprover").'">'.$langs->trans("Approve").'</a>';
							print '<a href="#" class="butActionRefused classfortooltip" title="'.$langs->trans("NotTheAssignedApprover").'">'.$langs->trans("ActionRefuseCP").'</a>';
						}
					}
					if (($user->id == $object->fk_validator || in_array($object->fk_user, $childids) || (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->holiday->writeall_advance))) && ($object->statut == 2 || $object->statut == 3))	// Status validated or approved
					{
						if (($object->date_debut > dol_now()) || $user->admin) print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=cancel" class="butAction">'.$langs->trans("ActionCancelCP").'</a>';
						else print '<a href="#" class="butActionRefused classfortooltip" title="'.$langs->trans("HolidayStarted").'">'.$langs->trans("ActionCancelCP").'</a>';
					}
					if ($cancreate && $object->statut == Holiday::STATUS_CANCELED)
					{
						print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=backtodraft" class="butAction">'.$langs->trans("SetToDraft").'</a>';
					}
					if ($candelete && ($object->statut == Holiday::STATUS_DRAFT || $object->statut == Holiday::STATUS_CANCELED || $object->statut == Holiday::STATUS_REFUSED))	// If draft or canceled or refused
					{
						print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&token='.newToken().'" class="butActionDelete">'.$langs->trans("DeleteCP").'</a>';
					}

					print '</div>';
				}
			} else {
				print '<div class="tabBar">';
				print $langs->trans('ErrorUserViewCP');
				print '<br><br><input type="button" value="'.$langs->trans("ReturnCP").'" class="button" onclick="history.go(-1)" />';
				print '</div>';
			}
		} else {
			print '<div class="tabBar">';
			print $langs->trans('ErrorIDFicheCP');
			print '<br><br><input type="button" value="'.$langs->trans("ReturnCP").'" class="button" onclick="history.go(-1)" />';
			print '</div>';
		}


		// Select mail models is same action as presend
		if (GETPOST('modelselected')) {
			$action = 'presend';
		}

		if ($action != 'presend')
		{
			print '<div class="fichecenter"><div class="fichehalfleft">';
			print '<a name="builddoc"></a>'; // ancre

			$includedocgeneration = 0;

			// Documents
			if ($includedocgeneration) {
				$objref = dol_sanitizeFileName($object->ref);
				$relativepath = $objref.'/'.$objref.'.pdf';
				$filedir = $conf->holiday->dir_output.'/'.$object->element.'/'.$objref;
				$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
				$genallowed = ($user->rights->holiday->read && $object->fk_user == $user->id) || !empty($user->rights->holiday->readall); // If you can read, you can build the PDF to read content
				$delallowed = ($user->rights->holiday->write && $object->fk_user == $user->id) || !empty($user->rights->holiday->writeall_advance); // If you can create/edit, you can remove a file on card
				print $formfile->showdocuments('holiday:Holiday', $object->element.'/'.$objref, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', $langs->defaultlang);
			}

			// Show links to link elements
			//$linktoelem = $form->showLinkToObjectBlock($object, null, array('myobject'));
			//$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);


			print '</div><div class="fichehalfright"><div class="ficheaddleft">';

			$MAXEVENT = 10;

			/*$morehtmlright = '<a href="'.dol_buildpath('/holiday/myobject_agenda.php', 1).'?id='.$object->id.'">';
			$morehtmlright .= $langs->trans("SeeAll");
			$morehtmlright .= '</a>';*/

			// List of actions on element
			include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
			$formactions = new FormActions($db);
			$somethingshown = $formactions->showactions($object, $object->element, (is_object($object->thirdparty) ? $object->thirdparty->id : 0), 1, '', $MAXEVENT, '', $morehtmlright);

			print '</div></div></div>';
		}
	}
}

// End of page
llxFooter();

if (is_object($db)) $db->close();
