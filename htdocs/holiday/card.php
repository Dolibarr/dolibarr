<?php
/* Copyright (C) 2011		Dimitri Mouillard	<dmouillard@teclib.com>
 * Copyright (C) 2012-2016	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2016	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2013		Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2017		Alexandre Spangaro	<aspangaro@open-dsi.fr>
 * Copyright (C) 2014-2017  Ferran Marcet		<fmarcet@2byte.es>
 * Copyright (C) 2018-2023  Frédéric France     <frederic.france@netlogic.fr>
 * Copyright (C) 2020-2021  Udo Tamm            <dev@dolibit.de>
 * Copyright (C) 2022		Anthony Berton      <anthony.berton@bb2a.fr>
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

// Load Dolibarr environment
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
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$fuserid = (GETPOST('fuserid', 'int') ?GETPOST('fuserid', 'int') : $user->id);
$socid = GETPOST('socid', 'int');

// Load translation files required by the page
$langs->loadLangs(array("other", "holiday", "mails", "trips"));

$error = 0;

$now = dol_now();

$childids = $user->getAllChildIds(1);

$morefilter = '';
if (!empty($conf->global->HOLIDAY_HIDE_FOR_NON_SALARIES)) {
	$morefilter = 'AND employee = 1';
}

$object = new Holiday($db);

$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

if (($id > 0) || $ref) {
	$object->fetch($id, $ref);

	// Check current user can read this leave request
	$canread = 0;
	if (!empty($user->rights->holiday->readall)) {
		$canread = 1;
	}
	if (!empty($user->rights->holiday->read) && in_array($object->fk_user, $childids)) {
		$canread = 1;
	}
	if (!$canread) {
		accessforbidden();
	}
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('holidaycard', 'globalcard'));

$cancreate = 0;
$cancreateall = 0;
if (!empty($user->rights->holiday->write) && in_array($fuserid, $childids)) {
	$cancreate = 1;
}
if (!empty($user->rights->holiday->writeall)) {
	$cancreate = 1;
	$cancreateall = 1;
}

$candelete = 0;
if (!empty($user->rights->holiday->delete)) {
	$candelete = 1;
}
if ($object->statut == Holiday::STATUS_DRAFT && $user->rights->holiday->write && in_array($object->fk_user, $childids)) {
	$candelete = 1;
}

// Protection if external user
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'holiday', $object->id, 'holiday', '', '', 'rowid', $object->statut);


/*
 * Actions
 */

$parameters = array('socid' => $socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	$backurlforlist = DOL_URL_ROOT.'/holiday/list.php';

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = DOL_URL_ROOT.'/holiday/card.php?id='.((!empty($id) && $id > 0) ? $id : '__ID__');
			}
		}
	}

	if ($cancel) {
		if (!empty($backtopageforcancel)) {
			header("Location: ".$backtopageforcancel);
			exit;
		} elseif (!empty($backtopage)) {
			header("Location: ".$backtopage);
			exit;
		}
		$action = '';
	}

	// Add leave request
	if ($action == 'add') {
		// If no right to create a request
		if (!$cancreate) {
			$error++;
			setEventMessages($langs->trans('CantCreateCP'), null, 'errors');
			$action = 'create';
		}

		if (!$error) {
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
			if ($starthalfday == 'afternoon' && $endhalfday == 'morning') {
				$halfday = 2;
			} elseif ($starthalfday == 'afternoon') {
				$halfday = -1;
			} elseif ($endhalfday == 'morning') {
				$halfday = 1;
			}

			$approverid = GETPOST('valideur', 'int');
			$description = trim(GETPOST('description', 'restricthtml'));

			// Check that leave is for a user inside the hierarchy or advanced permission for all is set
			if (!$cancreateall) {
				if (empty($conf->global->MAIN_USE_ADVANCED_PERMS)) {
					if (empty($user->rights->holiday->write)) {
						$error++;
						setEventMessages($langs->trans("NotEnoughPermissions"), null, 'errors');
					} elseif (!in_array($fuserid, $childids)) {
						$error++;
						setEventMessages($langs->trans("UserNotInHierachy"), null, 'errors');
						$action = 'create';
					}
				} else {
					if (empty($user->rights->holiday->write) && empty($user->rights->holiday->writeall_advance)) {
						$error++;
						setEventMessages($langs->trans("NotEnoughPermissions"), null, 'errors');
					} elseif (empty($user->rights->holiday->writeall_advance) && !in_array($fuserid, $childids)) {
						$error++;
						setEventMessages($langs->trans("UserNotInHierachy"), null, 'errors');
						$action = 'create';
					}
				}
			}

			// If no type
			if ($type <= 0) {
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Type")), null, 'errors');
				$error++;
				$action = 'create';
			}

			// If no start date
			if (empty($date_debut)) {
				setEventMessages($langs->trans("NoDateDebut"), null, 'errors');
				$error++;
				$action = 'create';
			}
			// If no end date
			if (empty($date_fin)) {
				setEventMessages($langs->trans("NoDateFin"), null, 'errors');
				$error++;
				$action = 'create';
			}
			// If start date after end date
			if ($date_debut > $date_fin) {
				setEventMessages($langs->trans("ErrorEndDateCP"), null, 'errors');
				$error++;
				$action = 'create';
			}

			// Check if there is already holiday for this period
			$verifCP = $object->verifDateHolidayCP($fuserid, $date_debut, $date_fin, $halfday);
			if (!$verifCP) {
				setEventMessages($langs->trans("alreadyCPexist"), null, 'errors');
				$error++;
				$action = 'create';
			}

			// If there is no Business Days within request
			$nbopenedday = num_open_day($date_debut_gmt, $date_fin_gmt, 0, 1, $halfday);
			if ($nbopenedday < 0.5) {
				setEventMessages($langs->trans("ErrorDureeCP"), null, 'errors'); // No working day
				$error++;
				$action = 'create';
			}

			// If no validator designated
			if ($approverid < 1) {
				setEventMessages($langs->transnoentitiesnoconv('InvalidValidatorCP'), null, 'errors');
				$error++;
			}

			$approverslist = $object->fetch_users_approver_holiday();
			if (!in_array($approverid, $approverslist)) {
				setEventMessages($langs->transnoentitiesnoconv('InvalidValidator'), null, 'errors');
				$error++;
			}

			// Fill array 'array_options' with data from add form
			$ret = $extrafields->setOptionalsFromPost(null, $object);
			if ($ret < 0) {
				$error++;
			}

			$result = 0;

			if (!$error) {
				$object->fk_user = $fuserid;
				$object->description = $description;
				$object->fk_validator = $approverid;
				$object->fk_type = $type;
				$object->date_debut = $date_debut;
				$object->date_fin = $date_fin;
				$object->halfday = $halfday;
				$object->entity = $conf->entity;

				$result = $object->create($user);
				if ($result <= 0) {
					setEventMessages($object->error, $object->errors, 'errors');
					$error++;
				}
			}

			// If no SQL error we redirect to the request card
			if (!$error) {
				$db->commit();

				header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
				exit;
			} else {
				$db->rollback();
			}
		}
	}

	// If this is an update and we are an approver, we can update to change the expected approver with another one (including himself)
	if ($action == 'update' && GETPOSTISSET('savevalidator') && !empty($user->rights->holiday->approve)) {
		$object->fetch($id);

		$object->oldcopy = dol_clone($object);

		$object->fk_validator = GETPOST('valideur', 'int');

		if ($object->fk_validator != $object->oldcopy->fk_validator) {
			$verif = $object->update($user);

			if ($verif <= 0) {
				setEventMessages($object->error, $object->errors, 'warnings');
				$action = 'editvalidator';
			} else {
				header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
				exit;
			}
		}

		$action = '';
	}

	if ($action == 'update' && !GETPOSTISSET('savevalidator')) {
		$date_debut = dol_mktime(0, 0, 0, GETPOST('date_debut_month'), GETPOST('date_debut_day'), GETPOST('date_debut_year'));
		$date_fin = dol_mktime(0, 0, 0, GETPOST('date_fin_month'), GETPOST('date_fin_day'), GETPOST('date_fin_year'));
		$date_debut_gmt = dol_mktime(0, 0, 0, GETPOST('date_debut_month'), GETPOST('date_debut_day'), GETPOST('date_debut_year'), 1);
		$date_fin_gmt = dol_mktime(0, 0, 0, GETPOST('date_fin_month'), GETPOST('date_fin_day'), GETPOST('date_fin_year'), 1);
		$starthalfday = GETPOST('starthalfday');
		$endhalfday = GETPOST('endhalfday');
		$halfday = 0;
		if ($starthalfday == 'afternoon' && $endhalfday == 'morning') {
			$halfday = 2;
		} elseif ($starthalfday == 'afternoon') {
			$halfday = -1;
		} elseif ($endhalfday == 'morning') {
			$halfday = 1;
		}

		// If no right to modify a request
		if (!$cancreateall) {
			if ($cancreate) {
				if (!in_array($fuserid, $childids)) {
					setEventMessages($langs->trans("UserNotInHierachy"), null, 'errors');
					header('Location: '.$_SERVER["PHP_SELF"].'?action=create');
					exit;
				}
			} else {
				setEventMessages($langs->trans("NotEnoughPermissions"), null, 'errors');
				header('Location: '.$_SERVER["PHP_SELF"].'?action=create');
				exit;
			}
		}

		$object->fetch($id);

		// If under validation
		if ($object->statut == Holiday::STATUS_DRAFT) {
			// If this is the requestor or has read/write rights
			if ($cancreate) {
				$approverid = GETPOST('valideur', 'int');
				// TODO Check this approver user id has the permission for approval

				$description = trim(GETPOST('description', 'restricthtml'));

				// If no end date
				if (!GETPOST('date_fin_')) {
					setEventMessages($langs->trans('NoDateFin'), null, 'warnings');
					$error++;
					$action = 'edit';
				}

				// If start date after end date
				if ($date_debut > $date_fin) {
					setEventMessages($langs->trans('ErrorEndDateCP'), null, 'warnings');
					$error++;
					$action = 'edit';
				}

				// If no validator designated
				if ($approverid < 1) {
					setEventMessages($langs->trans('InvalidValidatorCP'), null, 'warnings');
					$error++;
					$action = 'edit';
				}

				// If there is no Business Days within request
				$nbopenedday = num_open_day($date_debut_gmt, $date_fin_gmt, 0, 1, $halfday);
				if ($nbopenedday < 0.5) {
					setEventMessages($langs->trans('ErrorDureeCP'), null, 'warnings');
					$error++;
					$action = 'edit';
				}

				$db->begin();

				if (!$error) {
					$object->description = $description;
					$object->date_debut = $date_debut;
					$object->date_fin = $date_fin;
					$object->fk_validator = $approverid;
					$object->halfday = $halfday;

					// Update
					$verif = $object->update($user);

					if ($verif <= 0) {
						setEventMessages($object->error, $object->errors, 'warnings');
						$error++;
						$action = 'edit';
					}
				}

				if (!$error) {
					$db->commit();

					header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
					exit;
				} else {
					$db->rollback();
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
	if ($action == 'confirm_delete' && GETPOST('confirm') == 'yes' && $candelete) {
		$error = 0;

		$db->begin();

		$object->fetch($id);

		// If this is a rough draft, approved, canceled or refused
		if ($object->statut == Holiday::STATUS_DRAFT || $object->statut == Holiday::STATUS_CANCELED || $object->statut == Holiday::STATUS_REFUSED) {
			$result = $object->delete($user);
		} else {
			$error++;
			setEventMessages($langs->trans('BadStatusOfObject'), null, 'errors');
			$action = '';
		}

		if (!$error) {
			$db->commit();
			header('Location: list.php?restore_lastsearch_values=1');
			exit;
		} else {
			$db->rollback();
		}
	}

	// Action validate (+ send email for approval to the expected approver)
	if ($action == 'confirm_send') {
		$object->fetch($id);

		// If draft and owner of leave
		if ($object->statut == Holiday::STATUS_DRAFT && $cancreate) {
			$object->oldcopy = dol_clone($object);

			$object->statut = Holiday::STATUS_VALIDATED;

			$verif = $object->validate($user);

			// If no SQL error, we redirect to the request form
			if ($verif > 0) {
				// To
				$destinataire = new User($db);
				$destinataire->fetch($object->fk_validator);
				$emailTo = $destinataire->email;

				if (!$emailTo) {
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
				if (!empty($conf->global->MAIN_APPLICATION_TITLE)) {
					$societeName = $conf->global->MAIN_APPLICATION_TITLE;
				}

				$subject = $societeName." - ".$langs->transnoentitiesnoconv("HolidaysToValidate");

				// Content
				$message = "<p>".$langs->transnoentitiesnoconv("Hello")." ".$destinataire->firstname.",</p>\n";

				$message .= "<p>".$langs->transnoentities("HolidaysToValidateBody")."</p>\n";


				// option to warn the validator in case of too short delay
				if (empty($conf->global->HOLIDAY_HIDE_APPROVER_ABOUT_TOO_LOW_DELAY)) {
					$delayForRequest = 0;		// TODO Set delay depending of holiday leave type
					if ($delayForRequest) {
						$nowplusdelay = dol_time_plus_duree($now, $delayForRequest, 'd');

						if ($object->date_debut < $nowplusdelay) {
							$message = "<p>".$langs->transnoentities("HolidaysToValidateDelay", $delayForRequest)."</p>\n";
						}
					}
				}

				// option to notify the validator if the balance is less than the request
				if (empty($conf->global->HOLIDAY_HIDE_APPROVER_ABOUT_NEGATIVE_BALANCE)) {
					$nbopenedday = num_open_day($object->date_debut_gmt, $object->date_fin_gmt, 0, 1, $object->halfday);

					if ($nbopenedday > $object->getCPforUser($object->fk_user, $object->fk_type)) {
						$message .= "<p>".$langs->transnoentities("HolidaysToValidateAlertSolde")."</p>\n";
					}
				}

				$link = dol_buildpath("/holiday/card.php", 3) . '?id='.$object->id;

				$message .= "<ul>";
				$message .= "<li>".$langs->transnoentitiesnoconv("Name")." : ".dolGetFirstLastname($expediteur->firstname, $expediteur->lastname)."</li>\n";
				$message .= "<li>".$langs->transnoentitiesnoconv("Period")." : ".dol_print_date($object->date_debut, 'day')." ".$langs->transnoentitiesnoconv("To")." ".dol_print_date($object->date_fin, 'day')."</li>\n";
				$message .= "<li>".$langs->transnoentitiesnoconv("Link").' : <a href="'.$link.'" target="_blank">'.$link."</a></li>\n";
				$message .= "</ul>\n";

				$trackid = 'leav'.$object->id;

				$mail = new CMailFile($subject, $emailTo, $emailFrom, $message, array(), array(), array(), '', '', 0, 1, '', '', $trackid);

				// Sending the email
				$result = $mail->sendfile();

				if (!$result) {
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

	if ($action == 'update_extras') {
		$object->oldcopy = dol_clone($object);

		// Fill array 'array_options' with data from update form
		$ret = $extrafields->setOptionalsFromPost(null, $object, GETPOST('attribute', 'restricthtml'));
		if ($ret < 0) {
			$error++;
		}

		if (!$error) {
			// Actions on extra fields
			$result = $object->insertExtraFields('HOLIDAY_MODIFY');
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
				$error++;
			}
		}

		if ($error) {
			$action = 'edit_extras';
		}
	}

	// Approve leave request
	if ($action == 'confirm_valid') {
		$object->fetch($id);

		// If status is waiting approval and approver is also user
		if ($object->statut == Holiday::STATUS_VALIDATED && $user->id == $object->fk_validator) {
			$object->oldcopy = dol_clone($object);

			$object->date_approval = dol_now();
			$object->fk_user_approve = $user->id;
			$object->statut = Holiday::STATUS_APPROVED;
			$object->status = Holiday::STATUS_APPROVED;

			$db->begin();

			$verif = $object->approve($user);
			if ($verif <= 0) {
				setEventMessages($object->error, $object->errors, 'errors');
				$error++;
			}

			// If no SQL error, we redirect to the request form
			if (!$error) {
				// Calculcate number of days consumed
				$nbopenedday = num_open_day($object->date_debut_gmt, $object->date_fin_gmt, 0, 1, $object->halfday);
				$soldeActuel = $object->getCpforUser($object->fk_user, $object->fk_type);
				$newSolde = ($soldeActuel - $nbopenedday);
				$label = $langs->transnoentitiesnoconv("Holidays").' - '.$object->ref;

				// The modification is added to the LOG
				$result = $object->addLogCP($user->id, $object->fk_user, $label, $newSolde, $object->fk_type);
				if ($result < 0) {
					$error++;
					setEventMessages(null, $object->errors, 'errors');
				}

				// Update balance
				$result = $object->updateSoldeCP($object->fk_user, $newSolde, $object->fk_type);
				if ($result < 0) {
					$error++;
					setEventMessages(null, $object->errors, 'errors');
				}
			}

			if (!$error) {
				// To
				$destinataire = new User($db);
				$destinataire->fetch($object->fk_user);
				$emailTo = $destinataire->email;

				if (!$emailTo) {
					dol_syslog("User that request leave has no email, so we redirect directly to finished page without sending email");
				} else {
					// From
					$expediteur = new User($db);
					$expediteur->fetch($object->fk_validator);
					//$emailFrom = $expediteur->email;		Email of user can be an email into another company. Sending will fails, we must use the generic email.
					$emailFrom = $conf->global->MAIN_MAIL_EMAIL_FROM;

					// Subject
					$societeName = $conf->global->MAIN_INFO_SOCIETE_NOM;
					if (!empty($conf->global->MAIN_APPLICATION_TITLE)) {
						$societeName = $conf->global->MAIN_APPLICATION_TITLE;
					}

					$subject = $societeName." - ".$langs->transnoentitiesnoconv("HolidaysValidated");

					// Content
					$message = "<p>".$langs->transnoentitiesnoconv("Hello")." ".$destinataire->firstname.",</p>\n";

					$message .= "<p>".$langs->transnoentities("HolidaysValidatedBody", dol_print_date($object->date_debut, 'day'), dol_print_date($object->date_fin, 'day'))."</p>\n";

					$link = dol_buildpath('/holiday/card.php', 3).'?id='.$object->id;

					$message .= "<ul>\n";
					$message .= "<li>".$langs->transnoentitiesnoconv("ValidatedBy")." : ".dolGetFirstLastname($expediteur->firstname, $expediteur->lastname)."</li>\n";
					$message .= "<li>".$langs->transnoentitiesnoconv("Link").' : <a href="'.$link.'" target="_blank">'.$link."</a></li>\n";
					$message .= "</ul>\n";

					$trackid = 'leav'.$object->id;

					$mail = new CMailFile($subject, $emailTo, $emailFrom, $message, array(), array(), array(), '', '', 0, 1, '', '', $trackid);

					// Sending email
					$result = $mail->sendfile();

					if (!$result) {
						setEventMessages($mail->error, $mail->errors, 'warnings'); // Show error, but do no make rollback, so $error is not set to 1
						$action = '';
					}
				}
			}

			if (!$error) {
				$db->commit();

				header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
				exit;
			} else {
				$db->rollback();
				$action = '';
			}
		}
	}

	if ($action == 'confirm_refuse' && GETPOST('confirm', 'alpha') == 'yes') {
		if (GETPOST('detail_refuse')) {
			$object->fetch($id);

			// If status pending validation and validator = user
			if ($object->statut == Holiday::STATUS_VALIDATED && $user->id == $object->fk_validator) {
				$object->date_refuse = dol_now();
				$object->fk_user_refuse = $user->id;
				$object->statut = Holiday::STATUS_REFUSED;
				$object->status = Holiday::STATUS_REFUSED;
				$object->detail_refuse = GETPOST('detail_refuse', 'alphanohtml');

				$db->begin();

				$verif = $object->update($user);
				if ($verif <= 0) {
					$error++;
					setEventMessages($object->error, $object->errors, 'errors');
				}

				// If no SQL error, we redirect to the request form
				if (!$error) {
					// To
					$destinataire = new User($db);
					$destinataire->fetch($object->fk_user);
					$emailTo = $destinataire->email;

					if (!$emailTo) {
						dol_syslog("User that request leave has no email, so we redirect directly to finished page without sending email");
					} else {
						// From
						$expediteur = new User($db);
						$expediteur->fetch($object->fk_validator);
						//$emailFrom = $expediteur->email;		Email of user can be an email into another company. Sending will fails, we must use the generic email.
						$emailFrom = $conf->global->MAIN_MAIL_EMAIL_FROM;

						// Subject
						$societeName = $conf->global->MAIN_INFO_SOCIETE_NOM;
						if (!empty($conf->global->MAIN_APPLICATION_TITLE)) {
							$societeName = $conf->global->MAIN_APPLICATION_TITLE;
						}

						$subject = $societeName." - ".$langs->transnoentitiesnoconv("HolidaysRefused");

						// Content
						$message = "<p>".$langs->transnoentitiesnoconv("Hello")." ".$destinataire->firstname.",</p>\n";

						$message .= "<p>".$langs->transnoentities("HolidaysRefusedBody", dol_print_date($object->date_debut, 'day'), dol_print_date($object->date_fin, 'day'))."<p>\n";
						$message .= "<p>".GETPOST('detail_refuse', 'alpha')."</p>";

						$link = dol_buildpath('/holiday/card.php', 3).'?id='.$object->id;

						$message .= "<ul>\n";
						$message .= "<li>".$langs->transnoentitiesnoconv("ModifiedBy")." : ".dolGetFirstLastname($expediteur->firstname, $expediteur->lastname)."</li>\n";
						$message .= "<li>".$langs->transnoentitiesnoconv("Link").' : <a href="'.$link.'" target="_blank">'.$link."</a></li>\n";
						$message .= "</ul>";

						$trackid = 'leav'.$object->id;

						$mail = new CMailFile($subject, $emailTo, $emailFrom, $message, array(), array(), array(), '', '', 0, 1, '', '', $trackid);

						// sending email
						$result = $mail->sendfile();

						if (!$result) {
							setEventMessages($mail->error, $mail->errors, 'warnings'); // Show error, but do no make rollback, so $error is not set to 1
							$action = '';
						}
					}
				} else {
					$action = '';
				}

				if (!$error) {
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


	// If the request is validated
	if ($action == 'confirm_draft' && GETPOST('confirm') == 'yes') {
		$error = 0;

		$object->fetch($id);

		$oldstatus = $object->statut;
		$object->statut = Holiday::STATUS_DRAFT;
		$object->status = Holiday::STATUS_DRAFT;

		$result = $object->update($user);
		if ($result < 0) {
			$error++;
			setEventMessages($langs->trans('ErrorBackToDraft').' '.$object->error, $object->errors, 'errors');
		}

		if (!$error) {
			$db->commit();

			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
			exit;
		} else {
			$db->rollback();
		}
	}

	// If confirmation of cancellation
	if ($action == 'confirm_cancel' && GETPOST('confirm') == 'yes') {
		$error = 0;

		$object->fetch($id);

		// If status pending validation and validator = validator or user, or rights to do for others
		if (($object->statut == Holiday::STATUS_VALIDATED || $object->statut == Holiday::STATUS_APPROVED) &&
			(!empty($user->admin) || $user->id == $object->fk_validator || $cancreate || $cancreateall)) {
				$db->begin();

				$oldstatus = $object->statut;
				$object->date_cancel = dol_now();
				$object->fk_user_cancel = $user->id;
				$object->statut = Holiday::STATUS_CANCELED;
				$object->status = Holiday::STATUS_CANCELED;

				$result = $object->update($user);

			if ($result >= 0 && $oldstatus == Holiday::STATUS_APPROVED) {	// holiday was already validated, status 3, so we must increase back the balance
				// Call trigger
				$result = $object->call_trigger('HOLIDAY_CANCEL', $user);
				if ($result < 0) {
					$error++;
				}

				// Calculcate number of days consumed
				$nbopenedday = num_open_day($object->date_debut_gmt, $object->date_fin_gmt, 0, 1, $object->halfday);

				$soldeActuel = $object->getCpforUser($object->fk_user, $object->fk_type);
				$newSolde = ($soldeActuel + $nbopenedday);

				// The modification is added to the LOG
				$result1 = $object->addLogCP($user->id, $object->fk_user, $langs->transnoentitiesnoconv("HolidaysCancelation"), $newSolde, $object->fk_type);

				// Update of the balance
				$result2 = $object->updateSoldeCP($object->fk_user, $newSolde, $object->fk_type);

				if ($result1 < 0 || $result2 < 0) {
					$error++;
					setEventMessages($langs->trans('ErrorCantDeleteCP').' '.$object->error, $object->errors, 'errors');
				}
			}

			if (!$error) {
				$db->commit();
			} else {
				$db->rollback();
			}

				// If no SQL error, we redirect to the request form
			if (!$error && $result > 0) {
				// To
				$destinataire = new User($db);
				$destinataire->fetch($object->fk_user);
				$emailTo = $destinataire->email;

				if (!$emailTo) {
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
				if (!empty($conf->global->MAIN_APPLICATION_TITLE)) {
					$societeName = $conf->global->MAIN_APPLICATION_TITLE;
				}

				$subject = $societeName." - ".$langs->transnoentitiesnoconv("HolidaysCanceled");

				// Content
				$message = "<p>".$langs->transnoentitiesnoconv("Hello")." ".$destinataire->firstname.",</p>\n";

				$message .= "<p>".$langs->transnoentities("HolidaysCanceledBody", dol_print_date($object->date_debut, 'day'), dol_print_date($object->date_fin, 'day'))."</p>\n";

				$link = dol_buildpath('/holiday/card.php', 3).'?id='.$object->id;

				$message .= "<ul>\n";
				$message .= "<li>".$langs->transnoentitiesnoconv("ModifiedBy")." : ".dolGetFirstLastname($expediteur->firstname, $expediteur->lastname)."</li>\n";
				$message .= "<li>".$langs->transnoentitiesnoconv("Link").' : <a href="'.$link.'" target="_blank">'.$link."</a></li>\n";
				$message .= "</ul>\n";

				$trackid = 'leav'.$object->id;

				$mail = new CMailFile($subject, $emailTo, $emailFrom, $message, array(), array(), array(), '', '', 0, 1, '', '', $trackid);

				// sending email
				$result = $mail->sendfile();

				if (!$result) {
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

$title = $langs->trans('Leave');
$help_url = 'EN:Module_Holiday';

llxHeader('', $title, $help_url);

$edit = false;

if ((empty($id) && empty($ref)) || $action == 'create' || $action == 'add') {
	// If user has no permission to create a leave
	if ((in_array($fuserid, $childids) && empty($user->rights->holiday->write)) || (!in_array($fuserid, $childids) && ((!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->holiday->writeall_advance) || empty($user->rights->holiday->writeall))))) {
		$errors[] = $langs->trans('CantCreateCP');
	} else {
		// Form to add a leave request
		print load_fiche_titre($langs->trans('MenuAddCP'), '', 'title_hrm.png');

		// Error management
		if (GETPOST('error')) {
			switch (GETPOST('error')) {
				case 'datefin':
					$errors[] = $langs->trans('ErrorEndDateCP');
					break;
				case 'SQL_Create':
					$errors[] = $langs->trans('ErrorSQLCreateCP');
					break;
				case 'CantCreate':
					$errors[] = $langs->trans('CantCreateCP');
					break;
				case 'Valideur':
					$errors[] = $langs->trans('InvalidValidatorCP');
					break;
				case 'nodatedebut':
					$errors[] = $langs->trans('NoDateDebut');
					break;
				case 'nodatefin':
					$errors[] = $langs->trans('NoDateFin');
					break;
				case 'DureeHoliday':
					$errors[] = $langs->trans('ErrorDureeCP');
					break;
				case 'alreadyCP':
					$errors[] = $langs->trans('alreadyCPexist');
					break;
			}

			setEventMessages($errors, null, 'errors');
		}


		print '<script type="text/javascript">
		$( document ).ready(function() {
			$("input.button-save").click("submit", function(e) {
				console.log("Call valider()");
	    	    if (document.demandeCP.date_debut_.value != "")
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
	       	});
		});
       </script>'."\n";


		// Formulaire de demande
		print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'" name="demandeCP">'."\n";
		print '<input type="hidden" name="token" value="'.newToken().'" />'."\n";
		print '<input type="hidden" name="action" value="add" />'."\n";

		print dol_get_fiche_head();

		//print '<span>'.$langs->trans('DelayToRequestCP',$object->getConfCP('delayForRequest')).'</span><br><br>';

		print '<table class="border centpercent">';
		print '<tbody>';

		// User for leave request
		print '<tr>';
		print '<td class="titlefield fieldrequired tdtop">'.$langs->trans("User").'</td>';
		print '<td><div class="inline-block">';
		if ($cancreate && !$cancreateall) {
			print img_picto('', 'user', 'class="pictofixedwidth"').$form->select_dolusers(($fuserid ? $fuserid : $user->id), 'fuserid', 0, '', 0, 'hierarchyme', '', '0,'.$conf->entity, 0, 0, $morefilter, 0, '', 'minwidth200 maxwidth500 inline-block');
			//print '<input type="hidden" name="fuserid" value="'.($fuserid?$fuserid:$user->id).'">';
		} else {
			print img_picto('', 'user', 'class="pictofixedwidth"').$form->select_dolusers($fuserid ? $fuserid : $user->id, 'fuserid', 0, '', 0, '', '', '0,'.$conf->entity, 0, 0, $morefilter, 0, '', 'minwidth200 maxwidth500 inline-block');
		}
		print '</div>';

		if (empty($conf->global->HOLIDAY_HIDE_BALANCE)) {
			print '<div class="leaveuserbalance paddingtop inline-block floatright badge badge-status0 badge-status margintoponsmartphone">';

			$out = '';
			$nb_holiday = 0;
			$typeleaves = $object->getTypes(1, 1);
			foreach ($typeleaves as $key => $val) {
				$nb_type = $object->getCPforUser(($fuserid ? $fuserid : $user->id), $val['rowid']);
				$nb_holiday += $nb_type;

				$out .= ' - '.($langs->trans($val['code']) != $val['code'] ? $langs->trans($val['code']) : $val['label']).': <strong>'.($nb_type ? price2num($nb_type) : 0).'</strong><br>';
				//$out .= ' - '.$val['label'].': <strong>'.($nb_type ?price2num($nb_type) : 0).'</strong><br>';
			}
			print ' &nbsp; &nbsp; ';

			$htmltooltip = $langs->trans("Detail").'<br>';
			$htmltooltip .= $out;

			print $form->textwithtooltip($langs->trans('SoldeCPUser', round($nb_holiday, 5)).' '.img_picto('', 'help'), $htmltooltip);

			print '</div>';
			if (!empty($conf->use_javascript_ajax)) {
				print '<script>';
				print '$( document ).ready(function() {
					jQuery("#fuserid").change(function() {
						console.log("We change to user id "+jQuery("#fuserid").val());
						if (jQuery("#fuserid").val() == '.((int) $user->id).') {
							jQuery(".leaveuserbalance").show();
						} else {
							jQuery(".leaveuserbalance").hide();
						}
					});
				});';
				print '</script>';
			}
		} elseif (!is_numeric($conf->global->HOLIDAY_HIDE_BALANCE)) {
			print '<div class="leaveuserbalance paddingtop">';
			print $langs->trans($conf->global->HOLIDAY_HIDE_BALANCE);
			print '</div>';
		}

		print '</td>';
		print '</tr>';

		// Type
		print '<tr>';
		print '<td class="fieldrequired">'.$langs->trans("Type").'</td>';
		print '<td>';
		$typeleaves = $object->getTypes(1, -1);
		$arraytypeleaves = array();
		foreach ($typeleaves as $key => $val) {
			$labeltoshow = ($langs->trans($val['code']) != $val['code'] ? $langs->trans($val['code']) : $val['label']);
			$labeltoshow .= ($val['delay'] > 0 ? ' ('.$langs->trans("NoticePeriod").': '.$val['delay'].' '.$langs->trans("days").')' : '');
			$arraytypeleaves[$val['rowid']] = $labeltoshow;
		}
		print $form->selectarray('type', $arraytypeleaves, (GETPOST('type', 'alpha') ?GETPOST('type', 'alpha') : ''), 1, 0, 0, '', 0, 0, 0, '', '', true);
		if ($user->admin) {
			print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
		}
		print '</td>';
		print '</tr>';

		// Date start
		print '<tr>';
		print '<td class="fieldrequired">';
		print $form->textwithpicto($langs->trans("DateDebCP"), $langs->trans("FirstDayOfHoliday"));
		print '</td>';
		print '<td>'.img_picto('', 'action', 'class="pictofixedwidth"');
		if (!GETPOST('date_debut_')) {	// If visitor does not come from agenda
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
		print '<td>'.img_picto('', 'action', 'class="pictofixedwidth"');
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
			// Defined default approver (the forced approved of user or the supervisor if no forced value defined)
			// Note: This use will be set only if the deinfed approvr has permission to approve so is inside include_users
			$defaultselectuser = (empty($user->fk_user_holiday_validator) ? $user->fk_user : $user->fk_user_holiday_validator);
			if (!empty($conf->global->HOLIDAY_DEFAULT_VALIDATOR)) {
				$defaultselectuser = $conf->global->HOLIDAY_DEFAULT_VALIDATOR; // Can force default approver
			}
			if (GETPOST('valideur', 'int') > 0) {
				$defaultselectuser = GETPOST('valideur', 'int');
			}
			$s = $form->select_dolusers($defaultselectuser, "valideur", 1, '', 0, $include_users, '', '0,'.$conf->entity, 0, 0, '', 0, '', 'minwidth200 maxwidth500');
			print img_picto('', 'user', 'class="pictofixedwidth"').$form->textwithpicto($s, $langs->trans("AnyOtherInThisListCanValidate"));
		}

		//print $form->select_dolusers((GETPOST('valideur','int')>0?GETPOST('valideur','int'):$user->fk_user), "valideur", 1, ($user->admin ? '' : array($user->id)), 0, '', 0, 0, 0, 0, '', 0, '', '', 1);	// By default, hierarchical parent
		print '</td>';
		print '</tr>';

		// Description
		print '<tr>';
		print '<td>'.$langs->trans("DescCP").'</td>';
		print '<td class="tdtop">';
		$doleditor = new DolEditor('description', GETPOST('description', 'restricthtml'), '', 80, 'dolibarr_notes', 'In', 0, false, isModEnabled('fckeditor'), ROWS_3, '90%');
		print $doleditor->Create(1);
		print '</td></tr>';

		// Other attributes
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

		print '</tbody>';
		print '</table>';

		print dol_get_fiche_end();

		print $form->buttonsSaveCancel("SendRequestCP");

		print '</from>'."\n";
	}
} else {
	if ($error && $action != 'edit') {
		print '<div class="tabBar">';
		print $error;
		print '<br><br><input type="button" value="'.$langs->trans("ReturnCP").'" class="button" onclick="history.go(-1)" />';
		print '</div>';
	} else {
		// Show page in view or edit mode
		if (($id > 0) || $ref) {
			$result = $object->fetch($id, $ref);

			$approverexpected = new User($db);
			$approverexpected->fetch($object->fk_validator);	// Use that should be the approver

			$userRequest = new User($db);
			$userRequest->fetch($object->fk_user);

			//print load_fiche_titre($langs->trans('TitreRequestCP'));

			// Si il y a une erreur
			if (GETPOST('error')) {
				switch (GETPOST('error')) {
					case 'datefin':
						$errors[] = $langs->transnoentitiesnoconv('ErrorEndDateCP');
						break;
					case 'SQL_Create':
						$errors[] = $langs->transnoentitiesnoconv('ErrorSQLCreateCP');
						break;
					case 'CantCreate':
						$errors[] = $langs->transnoentitiesnoconv('CantCreateCP');
						break;
					case 'Valideur':
						$errors[] = $langs->transnoentitiesnoconv('InvalidValidatorCP');
						break;
					case 'nodatedebut':
						$errors[] = $langs->transnoentitiesnoconv('NoDateDebut');
						break;
					case 'nodatefin':
						$errors[] = $langs->transnoentitiesnoconv('NoDateFin');
						break;
					case 'DureeHoliday':
						$errors[] = $langs->transnoentitiesnoconv('ErrorDureeCP');
						break;
					case 'NoMotifRefuse':
						$errors[] = $langs->transnoentitiesnoconv('NoMotifRefuseCP');
						break;
					case 'mail':
						$errors[] = $langs->transnoentitiesnoconv('ErrorMailNotSend');
						break;
				}

				setEventMessages($errors, null, 'errors');
			}

			// check if the user has the right to read this request
			if ($canread) {
				$head = holiday_prepare_head($object);

				if (($action == 'edit' && $object->statut == Holiday::STATUS_DRAFT) || ($action == 'editvalidator')) {
					if ($action == 'edit' && $object->statut == Holiday::STATUS_DRAFT) {
						$edit = true;
					}

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

				if (!$edit) {
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
					$tmpdate = dol_mktime(0, 0, 0, GETPOST('date_debut_month', 'int'), GETPOST('date_debut_day', 'int'), GETPOST('date_debut_year', 'int'));
					print $form->selectDate($tmpdate ? $tmpdate : $object->date_debut, 'date_debut_');
					print ' &nbsp; &nbsp; ';
					print $form->selectarray('starthalfday', $listhalfday, (GETPOST('starthalfday') ?GETPOST('starthalfday') : $starthalfday));
					print '</td>';
					print '</tr>';
				}

				if (!$edit) {
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
				if ($includesaturday) {
					$htmlhelp .= '<br>'.$langs->trans("DayIsANonWorkingDay", $langs->trans("Saturday"));
				}
				if ($includesunday) {
					$htmlhelp .= '<br>'.$langs->trans("DayIsANonWorkingDay", $langs->trans("Sunday"));
				}
				print $form->textwithpicto($langs->trans('NbUseDaysCP'), $htmlhelp);
				print '</td>';
				print '<td>';
				print num_open_day($object->date_debut_gmt, $object->date_fin_gmt, 0, 1, $object->halfday);
				print '</td>';
				print '</tr>';

				if ($object->statut == Holiday::STATUS_REFUSED) {
					print '<tr>';
					print '<td>'.$langs->trans('DetailRefusCP').'</td>';
					print '<td>'.$object->detail_refuse.'</td>';
					print '</tr>';
				}

				// Description
				if (!$edit) {
					print '<tr>';
					print '<td>'.$langs->trans('DescCP').'</td>';
					print '<td>'.nl2br($object->description).'</td>';
					print '</tr>';
				} else {
					print '<tr>';
					print '<td>'.$langs->trans('DescCP').'</td>';
					print '<td class="tdtop">';
					$doleditor = new DolEditor('description', $object->description, '', 80, 'dolibarr_notes', 'In', 0, false, isModEnabled('fckeditor'), ROWS_3, '90%');
					print $doleditor->Create(1);
					print '</td></tr>';
				}

				// Other attributes
				include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

				print '</tbody>';
				print '</table>'."\n";

				print '</div>';
				print '<div class="fichehalfright">';

				print '<div class="underbanner clearboth"></div>';

				// Info workflow
				print '<table class="border tableforfield centpercent">'."\n";
				print '<tbody>';

				if (!empty($object->fk_user_create)) {
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
					if ($object->statut == Holiday::STATUS_APPROVED || $object->statut == Holiday::STATUS_CANCELED) {
						print $langs->trans('ApprovedBy');
					} else {
						print $langs->trans('ReviewedByCP');
					}
					print '</td>';
					print '<td>';
					if ($object->statut == Holiday::STATUS_APPROVED || $object->statut == Holiday::STATUS_CANCELED) {
						if ($object->fk_user_approve > 0) {
							$approverdone = new User($db);
							$approverdone->fetch($object->fk_user_approve);
							print $approverdone->getNomUrl(-1);
						}
					} else {
						print $approverexpected->getNomUrl(-1);
					}
					$include_users = $object->fetch_users_approver_holiday();
					if (is_array($include_users) && in_array($user->id, $include_users) && $object->statut == Holiday::STATUS_VALIDATED) {
						print '<a class="editfielda paddingleft" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=editvalidator">'.img_edit($langs->trans("Edit")).'</a>';
					}
					print '</td>';
					print '</tr>';
				} else {
					print '<tr>';
					print '<td class="titlefield">'.$langs->trans('ReviewedByCP').'</td>';	// Will be approved by
					print '<td>';
					$include_users = $object->fetch_users_approver_holiday();
					if (!in_array($object->fk_validator, $include_users)) {  // Add the current validator to the list to not lose it when editing.
						$include_users[] = $object->fk_validator;
					}
					if (empty($include_users)) {
						print img_warning().' '.$langs->trans("NobodyHasPermissionToValidateHolidays");
					} else {
						$arrayofvalidatorstoexclude = (($user->admin || ($user->id != $userRequest->id)) ? '' : array($user->id)); // Nobody if we are admin or if we are not the user of the leave.
						$s = $form->select_dolusers($object->fk_validator, "valideur", (($action == 'editvalidator') ? 0 : 1), $arrayofvalidatorstoexclude, 0, $include_users);
						print $form->textwithpicto($s, $langs->trans("AnyOtherInThisListCanValidate"));
					}
					if ($action == 'editvalidator') {
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
					print '<td>'.dol_print_date($object->date_approval, 'dayhour', 'tzuser').'</td>'; // warning: date_valid is approval date on holiday module
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

				print '<div class="clearboth"></div>';

				print dol_get_fiche_end();


				// Confirmation messages
				if ($action == 'delete') {
					if ($candelete) {
						print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id, $langs->trans("TitleDeleteCP"), $langs->trans("ConfirmDeleteCP"), "confirm_delete", '', 0, 1);
					}
				}

				// Si envoi en validation
				if ($action == 'sendToValidate' && $object->statut == Holiday::STATUS_DRAFT) {
					print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id, $langs->trans("TitleToValidCP"), $langs->trans("ConfirmToValidCP"), "confirm_send", '', 1, 1);
				}

				// Si validation de la demande
				if ($action == 'valid') {
					print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id, $langs->trans("TitleValidCP"), $langs->trans("ConfirmValidCP"), "confirm_valid", '', 1, 1);
				}

				// Si refus de la demande
				if ($action == 'refuse') {
					$array_input = array(array('type'=>"text", 'label'=> $langs->trans('DetailRefusCP'), 'name'=>"detail_refuse", 'size'=>"50", 'value'=>""));
					print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id."&action=confirm_refuse", $langs->trans("TitleRefuseCP"), $langs->trans('ConfirmRefuseCP'), "confirm_refuse", $array_input, 1, 0);
				}

				// Si annulation de la demande
				if ($action == 'cancel') {
					print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id, $langs->trans("TitleCancelCP"), $langs->trans("ConfirmCancelCP"), "confirm_cancel", '', 1, 1);
				}

				// Si back to draft
				if ($action == 'backtodraft') {
					print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id, $langs->trans("TitleSetToDraft"), $langs->trans("ConfirmSetToDraft"), "confirm_draft", '', 1, 1);
				}

				if (($action == 'edit' && $object->statut == Holiday::STATUS_DRAFT) || ($action == 'editvalidator')) {
					if ($action == 'edit' && $object->statut == Holiday::STATUS_DRAFT) {
						if ($cancreate && $object->statut == Holiday::STATUS_DRAFT) {
							print $form->buttonsSaveCancel();
						}
					}

					print '</form>';
				}

				if (!$edit) {
					// Buttons for actions

					print '<div class="tabsAction">';

					if ($cancreate && $object->statut == Holiday::STATUS_DRAFT) {
						print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&token='.newToken().'" class="butAction">'.$langs->trans("EditCP").'</a>';
					}

					if ($cancreate && $object->statut == Holiday::STATUS_DRAFT) {		// If draft
						print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=sendToValidate&token='.newToken().'" class="butAction">'.$langs->trans("Validate").'</a>';
					}

					if ($object->statut == Holiday::STATUS_VALIDATED) {	// If validated
						// Button Approve / Refuse
						if ($user->id == $object->fk_validator) {
							print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=valid&token='.newToken().'" class="butAction">'.$langs->trans("Approve").'</a>';
							print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=refuse&token='.newToken().'" class="butAction">'.$langs->trans("ActionRefuseCP").'</a>';
						} else {
							print '<a href="#" class="butActionRefused classfortooltip" title="'.$langs->trans("NotTheAssignedApprover").'">'.$langs->trans("Approve").'</a>';
							print '<a href="#" class="butActionRefused classfortooltip" title="'.$langs->trans("NotTheAssignedApprover").'">'.$langs->trans("ActionRefuseCP").'</a>';

							// Button Cancel (because we can't approve)
							if ($cancreate || $cancreateall) {
								if (($object->date_debut > dol_now()) || !empty($user->admin)) {
									print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=cancel&token='.newToken().'" class="butAction">'.$langs->trans("ActionCancelCP").'</a>';
								} else {
									print '<a href="#" class="butActionRefused classfortooltip" title="'.$langs->trans("HolidayStarted").'-'.$langs->trans("NotAllowed").'">'.$langs->trans("ActionCancelCP").'</a>';
								}
							}
						}
					}
					if ($object->statut == Holiday::STATUS_APPROVED) { // If validated and approved
						if ($user->id == $object->fk_validator || $user->id == $object->fk_user_approve || $cancreate || $cancreateall) {
							if (($object->date_debut > dol_now()) || !empty($user->admin) || $user->id == $object->fk_user_approve) {
								print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=cancel&token='.newToken().'" class="butAction">'.$langs->trans("ActionCancelCP").'</a>';
							} else {
								print '<a href="#" class="butActionRefused classfortooltip" title="'.$langs->trans("HolidayStarted").'-'.$langs->trans("NotAllowed").'">'.$langs->trans("ActionCancelCP").'</a>';
							}
						} else { // I have no rights on the user of the holiday.
							if (!empty($user->admin)) {	// If current approver can't cancel an approved leave, we allow admin user
								print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=cancel&token='.newToken().'" class="butAction">'.$langs->trans("ActionCancelCP").'</a>';
							} else {
								print '<a href="#" class="butActionRefused classfortooltip" title="'.$langs->trans("NotAllowed").'">'.$langs->trans("ActionCancelCP").'</a>';
							}
						}
					}

					if (($cancreate || $cancreateall) && $object->statut == Holiday::STATUS_CANCELED) {
						print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=backtodraft" class="butAction">'.$langs->trans("SetToDraft").'</a>';
					}
					if ($candelete && ($object->statut == Holiday::STATUS_DRAFT || $object->statut == Holiday::STATUS_CANCELED || $object->statut == Holiday::STATUS_REFUSED)) {	// If draft or canceled or refused
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

		if ($action != 'presend' && $action != 'edit') {
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


			print '</div><div class="fichehalfright">';

			$MAXEVENT = 10;
			$morehtmlright = '';

			// List of actions on element
			include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
			$formactions = new FormActions($db);
			$somethingshown = $formactions->showactions($object, $object->element, (is_object($object->thirdparty) ? $object->thirdparty->id : 0), 1, '', $MAXEVENT, '', $morehtmlright);

			print '</div></div>';
		}
	}
}

// End of page
llxFooter();

if (is_object($db)) {
	$db->close();
}
