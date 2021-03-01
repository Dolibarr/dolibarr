<?php
/* Copyright (C) 2001-2004  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003  Jean-Louis Bergamo      <jlb@j1b.org>
 * Copyright (C) 2004-2012  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2018  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2012       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2012-2020  Philippe Grand          <philippe.grand@atoo-net.com>
 * Copyright (C) 2015-2018  Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2018-2020  Frédéric France         <frederic.france@netlogic.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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
 *  \file       htdocs/adherents/card.php
 *  \ingroup    member
 *  \brief      Page of a member
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/member.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/subscription.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

// Load translation files required by the page
$langs->loadLangs(array("companies", "bills", "members", "users", "other", "paypal"));

$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$rowid = GETPOST('rowid', 'int');
$id = GETPOST('id') ?GETPOST('id', 'int') : $rowid;
$typeid = GETPOST('typeid', 'int');
$userid = GETPOST('userid', 'int');
$socid = GETPOST('socid', 'int');

if (!empty($conf->mailmanspip->enabled)) {
	include_once DOL_DOCUMENT_ROOT.'/mailmanspip/class/mailmanspip.class.php';

	$langs->load('mailmanspip');

	$mailmanspip = new MailmanSpip($db);
}

$object = new Adherent($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$socialnetworks = getArrayOfSocialNetworks();

// Get object canvas (By default, this is not defined, so standard usage of dolibarr)
$object->getCanvas($id);
$canvas = $object->canvas ? $object->canvas : GETPOST("canvas");
$objcanvas = null;
if (!empty($canvas)) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/canvas.class.php';
	$objcanvas = new Canvas($db, $action);
	$objcanvas->getCanvas('adherent', 'membercard', $canvas);
}

// Security check
$result = restrictedArea($user, 'adherent', $id, '', '', 'socid', 'rowid', 0);

if ($id > 0) {
	// Load member
	$result = $object->fetch($id);

	// Define variables to know what current user can do on users
	$canadduser = ($user->admin || $user->rights->user->user->creer);
	// Define variables to know what current user can do on properties of user linked to edited member
	if ($object->user_id) {
		// $User is the user who edits, $object->user_id is the id of the related user in the edited member
		$caneditfielduser = ((($user->id == $object->user_id) && $user->rights->user->self->creer)
				|| (($user->id != $object->user_id) && $user->rights->user->user->creer));
		$caneditpassworduser = ((($user->id == $object->user_id) && $user->rights->user->self->password)
				|| (($user->id != $object->user_id) && $user->rights->user->user->password));
	}
}

// Define variables to determine what the current user can do on the members
$canaddmember = $user->rights->adherent->creer;
// Define variables to determine what the current user can do on the properties of a member
if ($id) {
	$caneditfieldmember = $user->rights->adherent->creer;
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('membercard', 'globalcard'));



/*
 * 	Actions
 */

$parameters = array('id'=>$id, 'rowid'=>$id, 'objcanvas'=>$objcanvas, 'confirm'=>$confirm);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	if ($cancel) {
		if (!empty($backtopage)) {
			header("Location: ".$backtopage);
			exit;
		}
		$action = '';
	}

	if ($action == 'setuserid' && ($user->rights->user->self->creer || $user->rights->user->user->creer)) {
		$error = 0;
		if (empty($user->rights->user->user->creer)) {	// If can edit only itself user, we can link to itself only
			if ($userid != $user->id && $userid != $object->user_id) {
				$error++;
				setEventMessages($langs->trans("ErrorUserPermissionAllowsToLinksToItselfOnly"), null, 'errors');
			}
		}

		if (!$error) {
			if ($userid != $object->user_id) {	// If link differs from currently in database
				$result = $object->setUserId($userid);
				if ($result < 0) dol_print_error($object->db, $object->error);
				$action = '';
			}
		}
	}

	if ($action == 'setsocid') {
		$error = 0;
		if (!$error) {
			if ($socid != $object->socid) {	// If link differs from currently in database
				$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."adherent";
				$sql .= " WHERE socid = ".((int) $socid);
				$sql .= " AND entity = ".$conf->entity;
				$resql = $db->query($sql);
				if ($resql) {
					$obj = $db->fetch_object($resql);
					if ($obj && $obj->rowid > 0) {
						$othermember = new Adherent($db);
						$othermember->fetch($obj->rowid);
						$thirdparty = new Societe($db);
						$thirdparty->fetch($socid);
						$error++;
						setEventMessages($langs->trans("ErrorMemberIsAlreadyLinkedToThisThirdParty", $othermember->getFullName($langs), $othermember->login, $thirdparty->name), null, 'errors');
					}
				}

				if (!$error) {
					$result = $object->setThirdPartyId($socid);
					if ($result < 0) dol_print_error($object->db, $object->error);
					$action = '';
				}
			}
		}
	}

	// Create user from a member
	if ($action == 'confirm_create_user' && $confirm == 'yes' && $user->rights->user->user->creer) {
		if ($result > 0) {
			// Creation user
			$nuser = new User($db);
			$tmpuser = dol_clone($object);
			if (GETPOST('internalorexternal', 'aZ09') == 'internal') {
				$tmpuser->fk_soc = 0;
			}

			$result = $nuser->create_from_member($tmpuser, GETPOST('login', 'alphanohtml'));

			if ($result < 0) {
				$langs->load("errors");
				setEventMessages($langs->trans($nuser->error), null, 'errors');
			} else {
				setEventMessages($langs->trans("NewUserCreated", $nuser->login), null, 'mesgs');
				$action = '';
			}
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// Create third party from a member
	if ($action == 'confirm_create_thirdparty' && $confirm == 'yes' && $user->rights->societe->creer) {
		if ($result > 0) {
			// User creation
			$company = new Societe($db);
			$result = $company->create_from_member($object, GETPOST('companyname', 'alpha'), GETPOST('companyalias', 'alpha'));

			if ($result < 0) {
				$langs->load("errors");
				setEventMessages($langs->trans($company->error), null, 'errors');
				setEventMessages($company->error, $company->errors, 'errors');
			}
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	if ($action == 'update' && !$cancel && $user->rights->adherent->creer) {
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$birthdate = '';
		if (GETPOST("birthday", 'int') && GETPOST("birthmonth", 'int') && GETPOST("birthyear", 'int'))
		{
			$birthdate = dol_mktime(12, 0, 0, GETPOST("birthmonth", 'int'), GETPOST("birthday", 'int'), GETPOST("birthyear", 'int'));
		}
		$lastname = GETPOST("lastname", 'alphanohtml');
		$firstname = GETPOST("firstname", 'alphanohtml');
		$gender = GETPOST("gender", 'alphanohtml');
		$societe = GETPOST("societe", 'alphanohtml');
		$morphy = GETPOST("morphy", 'alphanohtml');
		$login = GETPOST("login", 'alphanohtml');
		if ($morphy != 'mor' && empty($lastname)) {
			$error++;
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Lastname")), null, 'errors');
		}
		if ($morphy != 'mor' && (!isset($firstname) || $firstname == '')) {
			$error++;
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Firstname")), null, 'errors');
		}
		if ($morphy == 'mor' && empty($societe)) {
			$error++;
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Company")), null, 'errors');
		}
		// Check if the login already exists
		if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED)) {
			if (empty($login)) {
				$error++;
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Login")), null, 'errors');
			}
		}
		// Create new object
		if ($result > 0 && !$error) {
			$object->oldcopy = clone $object;

			// Change values
			$object->civility_id = trim(GETPOST("civility_id", 'alphanohtml'));
			$object->firstname   = trim(GETPOST("firstname", 'alphanohtml'));
			$object->lastname    = trim(GETPOST("lastname", 'alphanohtml'));
			$object->gender      = trim(GETPOST("gender", 'alphanohtml'));
			$object->login       = trim(GETPOST("login", 'alphanohtml'));
			$object->pass        = trim(GETPOST("pass", 'alpha'));

			$object->societe     = trim(GETPOST("societe", 'alphanohtml')); // deprecated
			$object->company     = trim(GETPOST("societe", 'alphanohtml'));

			$object->address     = trim(GETPOST("address", 'alphanohtml'));
			$object->zip         = trim(GETPOST("zipcode", 'alphanohtml'));
			$object->town        = trim(GETPOST("town", 'alphanohtml'));
			$object->state_id    = GETPOST("state_id", 'int');
			$object->country_id  = GETPOST("country_id", 'int');

			$object->phone       = trim(GETPOST("phone", 'alpha'));
			$object->phone_perso = trim(GETPOST("phone_perso", 'alpha'));
			$object->phone_mobile = trim(GETPOST("phone_mobile", 'alpha'));
			$object->email       = preg_replace('/\s+/', '', GETPOST("member_email", 'alpha'));
			$object->socialnetworks = array();
			foreach ($socialnetworks as $key => $value) {
				if (GETPOSTISSET($key) && GETPOST($key, 'alphanohtml') != '') {
					$object->socialnetworks[$key] = trim(GETPOST($key, 'alphanohtml'));
				}
			}
			//$object->skype       = trim(GETPOST("skype", 'alpha'));
			//$object->twitter     = trim(GETPOST("twitter", 'alpha'));
			//$object->facebook    = trim(GETPOST("facebook", 'alpha'));
			//$object->linkedin    = trim(GETPOST("linkedin", 'alpha'));
			$object->birth       = $birthdate;

			$object->typeid      = GETPOST("typeid", 'int');
			//$object->note        = trim(GETPOST("comment","alpha"));
			$object->morphy      = GETPOST("morphy", 'alpha');

			if (GETPOST('deletephoto', 'alpha')) $object->photo = '';
			elseif (!empty($_FILES['photo']['name'])) $object->photo = dol_sanitizeFileName($_FILES['photo']['name']);

			// Get status and public property
			$object->statut      = GETPOST("statut", 'alpha');
			$object->public      = GETPOST("public", 'alpha');

			// Fill array 'array_options' with data from add form
			$ret = $extrafields->setOptionalsFromPost(null, $object);
			if ($ret < 0) $error++;

			// Check if we need to also synchronize user information
			$nosyncuser = 0;
			if ($object->user_id) {	// If linked to a user
				if ($user->id != $object->user_id && empty($user->rights->user->user->creer)) $nosyncuser = 1; // Disable synchronizing
			}

			// Check if we need to also synchronize password information
			$nosyncuserpass = 0;
			if ($object->user_id) {	// If linked to a user
				if ($user->id != $object->user_id && empty($user->rights->user->user->password)) $nosyncuserpass = 1; // Disable synchronizing
			}

			$result = $object->update($user, 0, $nosyncuser, $nosyncuserpass);

			if ($result >= 0 && !count($object->errors)) {
				$categories = GETPOST('memcats', 'array');
				$object->setCategories($categories);

				// Logo/Photo save
				$dir = $conf->adherent->dir_output.'/'.get_exdir(0, 0, 0, 1, $object, 'member').'/photos';
				$file_OK = is_uploaded_file($_FILES['photo']['tmp_name']);
				if ($file_OK) {
					if (GETPOST('deletephoto')) {
						require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
						$fileimg = $conf->adherent->dir_output.'/'.get_exdir(0, 0, 0, 1, $object, 'member').'/photos/'.$object->photo;
						$dirthumbs = $conf->adherent->dir_output.'/'.get_exdir(0, 0, 0, 1, $object, 'member').'/photos/thumbs';
						dol_delete_file($fileimg);
						dol_delete_dir_recursive($dirthumbs);
					}

					if (image_format_supported($_FILES['photo']['name']) > 0) {
						dol_mkdir($dir);

						if (@is_dir($dir)) {
							$newfile = $dir.'/'.dol_sanitizeFileName($_FILES['photo']['name']);
							if (!dol_move_uploaded_file($_FILES['photo']['tmp_name'], $newfile, 1, 0, $_FILES['photo']['error']) > 0) {
								setEventMessages($langs->trans("ErrorFailedToSaveFile"), null, 'errors');
							} else {
								// Create thumbs
								$object->addThumbs($newfile);
							}
						}
					} else {
						setEventMessages("ErrorBadImageFormat", null, 'errors');
					}
				} else {
					switch ($_FILES['photo']['error']) {
						case 1: //uploaded file exceeds the upload_max_filesize directive in php.ini
						case 2: //uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the html form
							$errors[] = "ErrorFileSizeTooLarge";
							break;
						case 3: //uploaded file was only partially uploaded
							$errors[] = "ErrorFilePartiallyUploaded";
							break;
					}
				}

				$rowid = $object->id;
				$id = $object->id;
				$action = '';

				if (!empty($backtopage)) {
					header("Location: ".$backtopage);
					exit;
				}
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
				$action = '';
			}
		} else {
			$action = 'edit';
		}
	}

	if ($action == 'add' && $user->rights->adherent->creer) {
		if ($canvas) $object->canvas = $canvas;
		$birthdate = '';
		if (GETPOSTISSET("birthday") && GETPOST("birthday") && GETPOSTISSET("birthmonth") && GETPOST("birthmonth") && GETPOSTISSET("birthyear") && GETPOST("birthyear")) {
			$birthdate = dol_mktime(12, 0, 0, GETPOST("birthmonth", 'int'), GETPOST("birthday", 'int'), GETPOST("birthyear", 'int'));
		}
		$datesubscription = '';
		if (GETPOSTISSET("reday") && GETPOSTISSET("remonth") && GETPOSTISSET("reyear")) {
			$datesubscription = dol_mktime(12, 0, 0, GETPOST("remonth", 'int'), GETPOST("reday", "int"), GETPOST("reyear", "int"));
		}

		$typeid = GETPOST("typeid", 'int');
		$civility_id = GETPOST("civility_id", 'alphanohtml');
		$lastname = GETPOST("lastname", 'alphanohtml');
		$firstname = GETPOST("firstname", 'alphanohtml');
		$gender = GETPOST("gender", 'alphanohtml');
		$societe = GETPOST("societe", 'alphanohtml');
		$address = GETPOST("address", 'alphanohtml');
		$zip = GETPOST("zipcode", 'alphanohtml');
		$town = GETPOST("town", 'alphanohtml');
		$state_id = GETPOST("state_id", 'int');
		$country_id = GETPOST("country_id", 'int');

		$phone = GETPOST("phone", 'alpha');
		$phone_perso = GETPOST("phone_perso", 'alpha');
		$phone_mobile = GETPOST("phone_mobile", 'alpha');
		// $skype=GETPOST("member_skype", 'alpha');
		// $twitter=GETPOST("member_twitter", 'alpha');
		// $facebook=GETPOST("member_facebook", 'alpha');
		// $linkedin=GETPOST("member_linkedin", 'alpha');
		$email = preg_replace('/\s+/', '', GETPOST("member_email", 'alpha'));
		$login = GETPOST("member_login", 'alphanohtml');
		$pass = GETPOST("password", 'alpha');
		$photo = GETPOST("photo", 'alpha');
		$morphy = GETPOST("morphy", 'alphanohtml');
		$public = GETPOST("public", 'alphanohtml');

		$userid = GETPOST("userid", 'int');
		$socid = GETPOST("socid", 'int');

		$object->civility_id = $civility_id;
		$object->firstname   = $firstname;
		$object->lastname    = $lastname;
		$object->gender      = $gender;
		$object->societe     = $societe; // deprecated
		$object->company     = $societe;
		$object->address     = $address;
		$object->zip         = $zip;
		$object->town        = $town;
		$object->state_id    = $state_id;
		$object->country_id  = $country_id;
		$object->phone       = $phone;
		$object->phone_perso = $phone_perso;
		$object->phone_mobile = $phone_mobile;
		$object->socialnetworks = array();
		if (!empty($conf->socialnetworks->enabled)) {
			foreach ($socialnetworks as $key => $value) {
				if (GETPOSTISSET($key) && GETPOST($key, 'alphanohtml') != '') {
					$object->socialnetworks[$key] = GETPOST("member_".$key, 'alphanohtml');
				}
			}
		}

		// $object->skype       = $skype;
		// $object->twitter     = $twitter;
		// $object->facebook    = $facebook;
		// $object->linkedin    = $linkedin;

		$object->email       = $email;
		$object->login       = $login;
		$object->pass        = $pass;
		$object->birth       = $birthdate;
		$object->photo       = $photo;
		$object->typeid      = $typeid;
		//$object->note        = $comment;
		$object->morphy      = $morphy;
		$object->user_id     = $userid;
		$object->socid = $socid;
		$object->public      = $public;

		// Fill array 'array_options' with data from add form
		$ret = $extrafields->setOptionalsFromPost(null, $object);
		if ($ret < 0) $error++;

		// Check parameters
		if (empty($morphy) || $morphy == "-1") {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("MemberNature")), null, 'errors');
		}
		// Tests if the login already exists
		if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED)) {
			if (empty($login)) {
				$error++;
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Login")), null, 'errors');
			} else {
				$sql = "SELECT login FROM ".MAIN_DB_PREFIX."adherent WHERE login='".$db->escape($login)."'";
				$result = $db->query($sql);
				if ($result) {
					$num = $db->num_rows($result);
				}
				if ($num) {
					$error++;
					$langs->load("errors");
					setEventMessages($langs->trans("ErrorLoginAlreadyExists", $login), null, 'errors');
				}
			}
			if (empty($pass)) {
				$error++;
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Password")), null, 'errors');
			}
		}
		if ($morphy == 'mor' && empty($societe)) {
			$error++;
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Company")), null, 'errors');
		}
		if ($morphy != 'mor' && empty($lastname)) {
			$error++;
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Lastname")), null, 'errors');
		}
		if ($morphy != 'mor' && (!isset($firstname) || $firstname == '')) {
			$error++;
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Firstname")), null, 'errors');
		}
		if (!($typeid > 0)) {	// Keep () before !
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Type")), null, 'errors');
		}
		if ($conf->global->ADHERENT_MAIL_REQUIRED && !isValidEMail($email)) {
			$error++;
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorBadEMail", $email), null, 'errors');
		}
		$public = 0;
		if (isset($public)) $public = 1;

		if (!$error) {
			$db->begin();

			// Email about right and login does not exist
			$result = $object->create($user);
			if ($result > 0) {
				// Foundation categories
				$memcats = GETPOST('memcats', 'array');
				$object->setCategories($memcats);

				$db->commit();
				$rowid = $object->id;
				$id = $object->id;
				$action = '';
			} else {
				$db->rollback();

				if ($object->error) {
					setEventMessages($object->error, $object->errors, 'errors');
				} else {
					setEventMessages($object->error, $object->errors, 'errors');
				}

				$action = 'create';
			}
		} else {
			$action = 'create';
		}
	}

	if ($user->rights->adherent->supprimer && $action == 'confirm_delete' && $confirm == 'yes') {
		$result = $object->delete($id, $user);
		if ($result > 0) {
			if (!empty($backtopage)) {
				header("Location: ".$backtopage);
				exit;
			} else {
				header("Location: list.php");
				exit;
			}
		} else {
			$errmesg = $object->error;
		}
	}

	if ($user->rights->adherent->creer && $action == 'confirm_valid' && $confirm == 'yes') {
		$error = 0;

		$db->begin();

		$adht = new AdherentType($db);
		$adht->fetch($object->typeid);

		$result = $object->validate($user);

		if ($result >= 0 && !count($object->errors)) {
			// Send confirmation email (according to parameters of member type. Otherwise generic)
			if ($object->email && GETPOST("send_mail")) {
				$subject = '';
				$msg = '';

				// Send subscription email
				include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
				$formmail = new FormMail($db);
				// Set output language
				$outputlangs = new Translate('', $conf);
				$outputlangs->setDefaultLang(empty($object->thirdparty->default_lang) ? $mysoc->default_lang : $object->thirdparty->default_lang);
				// Load traductions files required by page
				$outputlangs->loadLangs(array("main", "members"));
				// Get email content from template
				$arraydefaultmessage = null;
				$labeltouse = $conf->global->ADHERENT_EMAIL_TEMPLATE_MEMBER_VALIDATION;

				if (!empty($labeltouse)) $arraydefaultmessage = $formmail->getEMailTemplate($db, 'member', $user, $outputlangs, 0, 1, $labeltouse);

				if (!empty($labeltouse) && is_object($arraydefaultmessage) && $arraydefaultmessage->id > 0) {
					$subject = $arraydefaultmessage->topic;
					$msg     = $arraydefaultmessage->content;
				}

				if (empty($labeltouse) || (int) $labeltouse === -1) {
					//fallback on the old configuration.
					setEventMessages('WarningMandatorySetupNotComplete', null, 'errors');
					$error++;
				} else {
					$substitutionarray = getCommonSubstitutionArray($outputlangs, 0, null, $object);
					complete_substitutions_array($substitutionarray, $outputlangs, $object);
					$subjecttosend = make_substitutions($subject, $substitutionarray, $outputlangs);
					$texttosend = make_substitutions(dol_concatdesc($msg, $adht->getMailOnValid()), $substitutionarray, $outputlangs);

					$moreinheader = 'X-Dolibarr-Info: send_an_email by adherents/card.php'."\r\n";

					$result = $object->send_an_email($texttosend, $subjecttosend, array(), array(), array(), "", "", 0, -1, '', $moreinheader);
					if ($result < 0) {
						$error++;
						setEventMessages($object->error, $object->errors, 'errors');
					}
				}
			}
		} else {
			$error++;
			if ($object->error) {
				setEventMessages($object->error, $object->errors, 'errors');
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}

		if (!$error) {
			$db->commit();
		} else {
			$db->rollback();
		}
		$action = '';
	}

	if ($user->rights->adherent->supprimer && $action == 'confirm_resign') {
		$error = 0;

		if ($confirm == 'yes') {
			$adht = new AdherentType($db);
			$adht->fetch($object->typeid);

			$result = $object->resiliate($user);

			if ($result >= 0 && !count($object->errors)) {
				if ($object->email && GETPOST("send_mail")) {
					$subject = '';
					$msg = '';

					// Send subscription email
					include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
					$formmail = new FormMail($db);
					// Set output language
					$outputlangs = new Translate('', $conf);
					$outputlangs->setDefaultLang(empty($object->thirdparty->default_lang) ? $mysoc->default_lang : $object->thirdparty->default_lang);
					// Load traductions files required by page
					$outputlangs->loadLangs(array("main", "members"));
					// Get email content from template
					$arraydefaultmessage = null;
					$labeltouse = $conf->global->ADHERENT_EMAIL_TEMPLATE_CANCELATION;

					if (!empty($labeltouse)) $arraydefaultmessage = $formmail->getEMailTemplate($db, 'member', $user, $outputlangs, 0, 1, $labeltouse);

					if (!empty($labeltouse) && is_object($arraydefaultmessage) && $arraydefaultmessage->id > 0) {
						$subject = $arraydefaultmessage->topic;
						$msg     = $arraydefaultmessage->content;
					}

					if (empty($labeltouse) || (int) $labeltouse === -1) {
						//fallback on the old configuration.
						setEventMessages('WarningMandatorySetupNotComplete', null, 'errors');
						$error++;
					} else {
						$substitutionarray = getCommonSubstitutionArray($outputlangs, 0, null, $object);
						complete_substitutions_array($substitutionarray, $outputlangs, $object);
						$subjecttosend = make_substitutions($subject, $substitutionarray, $outputlangs);
						$texttosend = make_substitutions(dol_concatdesc($msg, $adht->getMailOnResiliate()), $substitutionarray, $outputlangs);

						$moreinheader = 'X-Dolibarr-Info: send_an_email by adherents/card.php'."\r\n";

						$result = $object->send_an_email($texttosend, $subjecttosend, array(), array(), array(), "", "", 0, -1, '', $moreinheader);
						if ($result < 0) {
							$error++;
							setEventMessages($object->error, $object->errors, 'errors');
						}
					}
				}
			} else {
				$error++;

				if ($object->error) {
					setEventMessages($object->error, $object->errors, 'errors');
				} else {
					setEventMessages($object->error, $object->errors, 'errors');
				}
				$action = '';
			}
		}
		if (!empty($backtopage) && !$error) {
			header("Location: ".$backtopage);
			exit;
		}
	}

	// SPIP Management
	if ($user->rights->adherent->supprimer && $action == 'confirm_del_spip' && $confirm == 'yes') {
		if (!count($object->errors)) {
			if (!$mailmanspip->del_to_spip($object)) {
				setEventMessages($langs->trans('DeleteIntoSpipError').': '.$mailmanspip->error, null, 'errors');
			}
		}
	}

	if ($user->rights->adherent->creer && $action == 'confirm_add_spip' && $confirm == 'yes') {
		if (!count($object->errors)) {
			if (!$mailmanspip->add_to_spip($object)) {
				setEventMessages($langs->trans('AddIntoSpipError').': '.$mailmanspip->error, null, 'errors');
			}
		}
	}

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Actions to build doc
	$upload_dir = $conf->adherent->dir_output;
	$permissiontoadd = $user->rights->adherent->creer;
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

	// Actions to send emails
	$triggersendname = 'MEMBER_SENTBYMAIL';
	$paramname = 'id';
	$mode = 'emailfrommember';
	$trackid = 'mem'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';
}


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formcompany = new FormCompany($db);

$title = $langs->trans("Member")." - ".$langs->trans("Card");
$help_url = 'EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros';
llxHeader('', $title, $help_url);

$countrynotdefined = $langs->trans("ErrorSetACountryFirst").' ('.$langs->trans("SeeAbove").')';

if (is_object($objcanvas) && $objcanvas->displayCanvasExists($action)) {
	// -----------------------------------------
	// When used with CANVAS
	// -----------------------------------------
	if (empty($object->error) && $id) {
		$object = new Adherent($db);
		$result = $object->fetch($id);
		if ($result <= 0) dol_print_error('', $object->error);
	}
   	$objcanvas->assign_values($action, $object->id, $object->ref); // Set value for templates
	$objcanvas->display_canvas($action); // Show template
} else {
	// -----------------------------------------
	// When used in standard mode
	// -----------------------------------------

	if ($action == 'create') {
		/* ************************************************************************** */
		/*                                                                            */
		/* Creation mode                                                              */
		/*                                                                            */
		/* ************************************************************************** */
		$object->canvas = $canvas;
		$object->state_id = GETPOST('state_id', 'int');

		// We set country_id, country_code and country for the selected country
		$object->country_id = GETPOST('country_id', 'int') ?GETPOST('country_id', 'int') : $mysoc->country_id;
		if ($object->country_id) {
			$tmparray = getCountry($object->country_id, 'all');
			$object->country_code = $tmparray['code'];
			$object->country = $tmparray['label'];
		}

		if (!empty($socid)) {
			$object = new Societe($db);
			if ($socid > 0) $object->fetch($socid);

			if (!($object->id > 0)) {
				$langs->load("errors");
				print($langs->trans('ErrorRecordNotFound'));
				exit;
			}
		}

		$adht = new AdherentType($db);

		print load_fiche_titre($langs->trans("NewMember"), '', 'members');

		if ($conf->use_javascript_ajax) {
			print "\n".'<script type="text/javascript" language="javascript">';
			print 'jQuery(document).ready(function () {
						jQuery("#selectcountry_id").change(function() {
							document.formsoc.action.value="create";
							document.formsoc.submit();
						});
						function initfieldrequired()
						{
							jQuery("#tdcompany").removeClass("fieldrequired");
							jQuery("#tdlastname").removeClass("fieldrequired");
							jQuery("#tdfirstname").removeClass("fieldrequired");
							if (jQuery("#morphy").val() == \'mor\')
							{
								jQuery("#tdcompany").addClass("fieldrequired");
							}
							if (jQuery("#morphy").val() == \'phy\')
							{
								jQuery("#tdlastname").addClass("fieldrequired");
								jQuery("#tdfirstname").addClass("fieldrequired");
							}
						}
						jQuery("#morphy").change(function() {
							initfieldrequired();
						});
						initfieldrequired();
					})';
			print '</script>'."\n";
		}

		print '<form name="formsoc" action="'.$_SERVER["PHP_SELF"].'" method="post" enctype="multipart/form-data">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="add">';
		print '<input type="hidden" name="socid" value="'.$socid.'">';
		if ($backtopage) print '<input type="hidden" name="backtopage" value="'.($backtopage != '1' ? $backtopage : $_SERVER["HTTP_REFERER"]).'">';

		print dol_get_fiche_head('');

		print '<table class="border centpercent">';
		print '<tbody>';

		// Login
		if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED)) {
			print '<tr><td><span class="fieldrequired">'.$langs->trans("Login").' / '.$langs->trans("Id").'</span></td><td><input type="text" name="member_login" class="minwidth300" maxlength="50" value="'.(GETPOSTISSET("member_login") ? GETPOST("member_login", 'alphanohtml', 2) : $object->login).'" autofocus="autofocus"></td></tr>';
		}

		// Password
		if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED)) {
			require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
			$generated_password = getRandomPassword(false);
			print '<tr><td><span class="fieldrequired">'.$langs->trans("Password").'</span></td><td>';
			print '<input type="text" class="minwidth300" maxlength="50" name="password" value="'.$generated_password.'">';
			print '</td></tr>';
		}

		// Type
		print '<tr><td class="fieldrequired">'.$langs->trans("MemberType").'</td><td>';
		$listetype = $adht->liste_array(1);
		if (count($listetype)) {
			print $form->selectarray("typeid", $listetype, (GETPOST('typeid', 'int') ? GETPOST('typeid', 'int') : $typeid), (count($listetype) > 1 ? 1 : 0), 0, 0, '', 0, 0, 0, '', '', 1);
		} else {
			print '<font class="error">'.$langs->trans("NoTypeDefinedGoToSetup").'</font>';
		}
		print "</td>\n";

		// Morphy
		$morphys["phy"] = $langs->trans("Physical");
		$morphys["mor"] = $langs->trans("Moral");
		print '<tr><td class="fieldrequired">'.$langs->trans("MemberNature")."</td><td>\n";
		print $form->selectarray("morphy", $morphys, (GETPOST('morphy', 'alpha') ?GETPOST('morphy', 'alpha') : $object->morphy), 1, 0, 0, '', 0, 0, 0, '', '', 1);
		print "</td>\n";

		// Company
		print '<tr><td id="tdcompany">'.$langs->trans("Company").'</td><td><input type="text" name="societe" class="minwidth300" maxlength="128" value="'.(GETPOSTISSET('societe') ? GETPOST('societe', 'alphanohtml') : $object->company).'"></td></tr>';

		// Civility
		print '<tr><td>'.$langs->trans("UserTitle").'</td><td>';
		print $formcompany->select_civility(GETPOST('civility_id', 'int') ? GETPOST('civility_id', 'int') : $object->civility_id, 'civility_id', 'maxwidth150', 1).'</td>';
		print '</tr>';

		// Lastname
		print '<tr><td id="tdlastname">'.$langs->trans("Lastname").'</td><td><input type="text" name="lastname" class="minwidth300" maxlength="50" value="'.(GETPOSTISSET('lastname') ? GETPOST('lastname', 'alphanohtml') : $object->lastname).'"></td>';
		print '</tr>';

		// Firstname
		print '<tr><td id="tdfirstname">'.$langs->trans("Firstname").'</td><td><input type="text" name="firstname" class="minwidth300" maxlength="50" value="'.(GETPOSTISSET('firstname') ? GETPOST('firstname', 'alphanohtml') : $object->firstname).'"></td>';
		print '</tr>';

		// Gender
		print '<tr><td>'.$langs->trans("Gender").'</td>';
		print '<td>';
		$arraygender = array('man'=>$langs->trans("Genderman"), 'woman'=>$langs->trans("Genderwoman"), 'other'=>$langs->trans("Genderother"));
		print $form->selectarray('gender', $arraygender, GETPOST('gender', 'alphanohtml'), 1, 0, 0, '', 0, 0, 0, '', '', 1);
		print '</td></tr>';

		// EMail
		print '<tr><td>'.($conf->global->ADHERENT_MAIL_REQUIRED ? '<span class="fieldrequired">' : '').$langs->trans("EMail").($conf->global->ADHERENT_MAIL_REQUIRED ? '</span>' : '').'</td>';
		print '<td>'.img_picto('', 'object_email').' <input type="text" name="member_email" class="minwidth300" maxlength="255" value="'.(GETPOSTISSET('member_email') ? GETPOST('member_email', 'alpha') : $object->email).'"></td></tr>';

		// Address
		print '<tr><td class="tdtop">'.$langs->trans("Address").'</td><td>';
		print '<textarea name="address" wrap="soft" class="quatrevingtpercent" rows="2">'.(GETPOSTISSET('address') ?GETPOST('address', 'alphanohtml') : $object->address).'</textarea>';
		print '</td></tr>';

		// Zip / Town
		print '<tr><td>'.$langs->trans("Zip").' / '.$langs->trans("Town").'</td><td>';
		print $formcompany->select_ziptown((GETPOSTISSET('zipcode') ? GETPOST('zipcode', 'alphanohtml') : $object->zip), 'zipcode', array('town', 'selectcountry_id', 'state_id'), 6);
		print ' ';
		print $formcompany->select_ziptown((GETPOSTISSET('town') ? GETPOST('town', 'alphanohtml') : $object->town), 'town', array('zipcode', 'selectcountry_id', 'state_id'));
		print '</td></tr>';

		// Country
		$object->country_id = $object->country_id ? $object->country_id : $mysoc->country_id;
		print '<tr><td width="25%">'.$langs->trans('Country').'</td><td>';
		print $form->select_country(GETPOSTISSET('country_id') ? GETPOST('country_id', 'alpha') : $object->country_id, 'country_id');
		if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
		print '</td></tr>';

		// State
		if (empty($conf->global->MEMBER_DISABLE_STATE)) {
			print '<tr><td>'.$langs->trans('State').'</td><td>';
			if ($object->country_id) {
				print $formcompany->select_state(GETPOSTISSET('state_id') ? GETPOST('state_id', 'int') : $object->state_id, $object->country_code);
			} else {
				print $countrynotdefined;
			}
			print '</td></tr>';
		}

		// Pro phone
		print '<tr><td>'.$langs->trans("PhonePro").'</td>';
		print '<td>'.img_picto('', 'object_phoning').' <input type="text" name="phone" size="20" value="'.(GETPOSTISSET('phone') ? GETPOST('phone', 'alpha') : $object->phone).'"></td></tr>';

		// Personal phone
		print '<tr><td>'.$langs->trans("PhonePerso").'</td>';
		print '<td>'.img_picto('', 'object_phoning').' <input type="text" name="phone_perso" size="20" value="'.(GETPOSTISSET('phone_perso') ? GETPOST('phone_perso', 'alpha') : $object->phone_perso).'"></td></tr>';

		// Mobile phone
		print '<tr><td>'.$langs->trans("PhoneMobile").'</td>';
		print '<td>'.img_picto('', 'object_phoning_mobile').' <input type="text" name="phone_mobile" size="20" value="'.(GETPOSTISSET('phone_mobile') ? GETPOST('phone_mobile', 'alpha') : $object->phone_mobile).'"></td></tr>';

		if (!empty($conf->socialnetworks->enabled)) {
			foreach ($socialnetworks as $key => $value) {
				if (!$value['active']) break;
				print '<tr><td>'.$langs->trans($value['label']).'</td><td><input type="text" name="member_'.$key.'" size="40" value="'.(GETPOSTISSET('member_'.$key) ? GETPOST('member_'.$key, 'alpha') : $object->socialnetworks[$key]).'"></td></tr>';
			}
		}

		// Birth Date
		print "<tr><td>".$langs->trans("DateOfBirth")."</td><td>\n";
		print $form->selectDate(($object->birth ? $object->birth : -1), 'birth', '', '', 1, 'formsoc');
		print "</td></tr>\n";

		// Public profil
		print "<tr><td>".$langs->trans("Public")."</td><td>\n";
		print $form->selectyesno("public", $object->public, 1);
		print "</td></tr>\n";

		// Categories
		if (!empty($conf->categorie->enabled) && !empty($user->rights->categorie->lire)) {
			print '<tr><td>'.$form->editfieldkey("Categories", 'memcats', '', $object, 0).'</td><td>';
			$cate_arbo = $form->select_all_categories(Categorie::TYPE_MEMBER, null, 'parent', null, null, 1);
			print img_picto('', 'category').$form->multiselectarray('memcats', $cate_arbo, GETPOST('memcats', 'array'), null, null, 'quatrevingtpercent widthcentpercentminusx', 0, 0);
			print "</td></tr>";
		}

		// Other attributes
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

		print '<tbody>';
		print "</table>\n";

		print dol_get_fiche_end();

		print '<div class="center">';
		print '<input type="submit" name="button" class="button" value="'.$langs->trans("AddMember").'">';
		print '&nbsp;&nbsp;';
		if (!empty($backtopage)) {
			print '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
		} else {
			print '<input type="button" class="button button-cancel" value="'.$langs->trans("Cancel").'" onClick="javascript:history.go(-1)">';
		}
		print '</div>';

		print "</form>\n";
	}

	if ($action == 'edit') {
		/********************************************
		*
		* Edition mode
		*
		********************************************/

		$res = $object->fetch($id);
		if ($res < 0) {
			dol_print_error($db, $object->error); exit;
		}
		$res = $object->fetch_optionals();
		if ($res < 0) {
			dol_print_error($db); exit;
		}

		$adht = new AdherentType($db);
		$adht->fetch($object->typeid);

		// We set country_id, and country_code, country of the chosen country
		$country = GETPOST('country', 'int');
		if (!empty($country) || $object->country_id) {
			$sql = "SELECT rowid, code, label from ".MAIN_DB_PREFIX."c_country where rowid = ".(!empty($country) ? $country : $object->country_id);
			$resql = $db->query($sql);
			if ($resql) {
				$obj = $db->fetch_object($resql);
			} else {
				dol_print_error($db);
			}
			$object->country_id = $obj->rowid;
			$object->country_code = $obj->code;
			$object->country = $langs->trans("Country".$obj->code) ? $langs->trans("Country".$obj->code) : $obj->label;
		}

		$head = member_prepare_head($object);


		if ($conf->use_javascript_ajax) {
			print "\n".'<script type="text/javascript" language="javascript">';
			print 'jQuery(document).ready(function () {
				jQuery("#selectcountry_id").change(function() {
					document.formsoc.action.value="edit";
					document.formsoc.submit();
				});
				function initfieldrequired()
				{
					jQuery("#tdcompany").removeClass("fieldrequired");
					jQuery("#tdlastname").removeClass("fieldrequired");
					jQuery("#tdfirstname").removeClass("fieldrequired");
					if (jQuery("#morphy").val() == \'mor\')
					{
						jQuery("#tdcompany").addClass("fieldrequired");
					}
					if (jQuery("#morphy").val() == \'phy\')
					{
						jQuery("#tdlastname").addClass("fieldrequired");
						jQuery("#tdfirstname").addClass("fieldrequired");
					}
				}
				jQuery("#morphy").change(function() {
					initfieldrequired();
				});
				initfieldrequired();
			})';
			print '</script>'."\n";
		}

		print '<form name="formsoc" action="'.$_SERVER["PHP_SELF"].'" method="post" enctype="multipart/form-data">';
		print '<input type="hidden" name="token" value="'.newToken().'" />';
		print '<input type="hidden" name="action" value="update" />';
		print '<input type="hidden" name="rowid" value="'.$id.'" />';
		print '<input type="hidden" name="statut" value="'.$object->statut.'" />';
		if ($backtopage) print '<input type="hidden" name="backtopage" value="'.($backtopage != '1' ? $backtopage : $_SERVER["HTTP_REFERER"]).'">';

		print dol_get_fiche_head($head, 'general', $langs->trans("Member"), 0, 'user');

		print '<table class="border centpercent">';

		// Ref
		print '<tr><td class="titlefieldcreate">'.$langs->trans("Ref").'</td><td class="valeur">'.$object->id.'</td></tr>';

		// Login
		if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED)) {
			print '<tr><td><span class="fieldrequired">'.$langs->trans("Login").' / '.$langs->trans("Id").'</span></td><td><input type="text" name="login" class="minwidth300" maxlength="50" value="'.(GETPOSTISSET("login") ? GETPOST("login", 'alphanohtml', 2) : $object->login).'"></td></tr>';
		}

		// Password
		if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED)) {
			print '<tr><td class="fieldrequired">'.$langs->trans("Password").'</td><td><input type="password" name="pass" class="minwidth300" maxlength="50" value="'.(GETPOSTISSET("pass") ? GETPOST("pass", '', 2) : $object->pass).'"></td></tr>';
		}
		// Morphy
		$morphys["phy"] = $langs->trans("Physical");
		$morphys["mor"] = $langs->trans("Moral");
		print '<tr><td><span class="fieldrequired">'.$langs->trans("MemberNature").'</span></td><td>';
		print $form->selectarray("morphy", $morphys, (GETPOSTISSET("morphy") ? GETPOST("morphy", 'alpha') : $object->morphy), 0, 0, 0, '', 0, 0, 0, '', '', 1);
		print "</td></tr>";

		// Type
		print '<tr><td class="fieldrequired">'.$langs->trans("Type").'</td><td>';
		if ($user->rights->adherent->creer) {
			print $form->selectarray("typeid", $adht->liste_array(), (GETPOSTISSET("typeid") ? GETPOST("typeid", 'int') : $object->typeid), 0, 0, 0, '', 0, 0, 0, '', '', 1);
		} else {
			print $adht->getNomUrl(1);
			print '<input type="hidden" name="typeid" value="'.$object->typeid.'">';
		}
		print "</td></tr>";

		// Company
		print '<tr><td id="tdcompany">'.$langs->trans("Company").'</td><td><input type="text" name="societe" class="minwidth300" maxlength="128" value="'.(GETPOSTISSET("societe") ? GETPOST("societe", 'alphanohtml', 2) : $object->company).'"></td></tr>';

		// Civility
		print '<tr><td>'.$langs->trans("UserTitle").'</td><td>';
		print $formcompany->select_civility(GETPOSTISSET("civility_id") ? GETPOST("civility_id", 'alpha') : $object->civility_id, 'civility_id', 'maxwidth150', 1);
		print '</td>';
		print '</tr>';

		// Lastname
		print '<tr><td id="tdlastname">'.$langs->trans("Lastname").'</td><td><input type="text" name="lastname" class="minwidth300" maxlength="50" value="'.(GETPOSTISSET("lastname") ? GETPOST("lastname", 'alphanohtml', 2) : $object->lastname).'"></td>';
		print '</tr>';

		// Firstname
		print '<tr><td id="tdfirstname">'.$langs->trans("Firstname").'</td><td><input type="text" name="firstname" class="minwidth300" maxlength="50" value="'.(GETPOSTISSET("firstname") ? GETPOST("firstname", 'alphanohtml', 3) : $object->firstname).'"></td>';
		print '</tr>';

		// Gender
		print '<tr><td>'.$langs->trans("Gender").'</td>';
		print '<td>';
		$arraygender = array('man'=>$langs->trans("Genderman"), 'woman'=>$langs->trans("Genderwoman"), 'other'=>$langs->trans("Genderother"));
		print $form->selectarray('gender', $arraygender, GETPOSTISSET('gender') ? GETPOST('gender', 'alphanohtml') : $object->gender, 1, 0, 0, '', 0, 0, 0, '', '', 1);
		print '</td></tr>';

		// Photo
		print '<tr><td>'.$langs->trans("Photo").'</td>';
		print '<td class="hideonsmartphone" valign="middle">';
		print $form->showphoto('memberphoto', $object)."\n";
		if ($caneditfieldmember) {
			if ($object->photo) print "<br>\n";
			print '<table class="nobordernopadding">';
			if ($object->photo) print '<tr><td><input type="checkbox" class="flat photodelete" name="deletephoto" id="photodelete"> '.$langs->trans("Delete").'<br><br></td></tr>';
			print '<tr><td>'.$langs->trans("PhotoFile").'</td></tr>';
			print '<tr><td><input type="file" class="flat" name="photo" id="photoinput"></td></tr>';
			print '</table>';
		}
		print '</td></tr>';

		// EMail
		print '<tr><td>'.($conf->global->ADHERENT_MAIL_REQUIRED ? '<span class="fieldrequired">' : '').$langs->trans("EMail").($conf->global->ADHERENT_MAIL_REQUIRED ? '</span>' : '').'</td>';
		print '<td>'.img_picto('', 'object_email').' <input type="text" name="member_email" class="minwidth300" maxlength="255" value="'.(GETPOSTISSET("member_email") ? GETPOST("member_email", '', 2) : $object->email).'"></td></tr>';

		// Address
		print '<tr><td>'.$langs->trans("Address").'</td><td>';
		print '<textarea name="address" wrap="soft" class="quatrevingtpercent" rows="'.ROWS_2.'">'.(GETPOSTISSET("address") ? GETPOST("address", 'alphanohtml', 2) : $object->address).'</textarea>';
		print '</td></tr>';

		// Zip / Town
		print '<tr><td>'.$langs->trans("Zip").' / '.$langs->trans("Town").'</td><td>';
		print $formcompany->select_ziptown((GETPOSTISSET("zipcode") ? GETPOST("zipcode", 'alphanohtml', 2) : $object->zip), 'zipcode', array('town', 'selectcountry_id', 'state_id'), 6);
		print ' ';
		print $formcompany->select_ziptown((GETPOSTISSET("town") ? GETPOST("town", 'alphanohtml', 2) : $object->town), 'town', array('zipcode', 'selectcountry_id', 'state_id'));
		print '</td></tr>';

		// Country
		//$object->country_id=$object->country_id?$object->country_id:$mysoc->country_id;    // In edit mode we don't force to company country if not defined
		print '<tr><td>'.$langs->trans('Country').'</td><td>';
		print $form->select_country(GETPOSTISSET("country_id") ? GETPOST("country_id", "alpha") : $object->country_id, 'country_id');
		if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
		print '</td></tr>';

		// State
		if (empty($conf->global->MEMBER_DISABLE_STATE)) {
			print '<tr><td>'.$langs->trans('State').'</td><td>';
			print $formcompany->select_state($object->state_id, GETPOSTISSET("country_id") ? GETPOST("country_id", "alpha") : $object->country_id);
			print '</td></tr>';
		}

		// Pro phone
		print '<tr><td>'.$langs->trans("PhonePro").'</td>';
		print '<td>'.img_picto('', 'object_phoning').' <input type="text" name="phone" value="'.(GETPOSTISSET("phone") ? GETPOST("phone") : $object->phone).'"></td></tr>';

		// Personal phone
		print '<tr><td>'.$langs->trans("PhonePerso").'</td>';
		print '<td>'.img_picto('', 'object_phoning').' <input type="text" name="phone_perso" value="'.(GETPOSTISSET("phone_perso") ? GETPOST("phone_perso") : $object->phone_perso).'"></td></tr>';

		// Mobile phone
		print '<tr><td>'.$langs->trans("PhoneMobile").'</td>';
		print '<td>'.img_picto('', 'object_phoning_mobile').' <input type="text" name="phone_mobile" value="'.(GETPOSTISSET("phone_mobile") ? GETPOST("phone_mobile") : $object->phone_mobile).'"></td></tr>';

		if (!empty($conf->socialnetworks->enabled)) {
			foreach ($socialnetworks as $key => $value) {
				if (!$value['active']) break;
				print '<tr><td>'.$langs->trans($value['label']).'</td><td><input type="text" name="'.$key.'" class="minwidth100" value="'.(GETPOSTISSET($key) ? GETPOST($key, 'alphanohtml') : $object->socialnetworks[$key]).'"></td></tr>';
			}
		}

		// Birth Date
		print "<tr><td>".$langs->trans("DateOfBirth")."</td><td>\n";
		print $form->selectDate(($object->birth ? $object->birth : -1), 'birth', '', '', 1, 'formsoc');
		print "</td></tr>\n";

		// Public profil
		print "<tr><td>".$langs->trans("Public")."</td><td>\n";
		print $form->selectyesno("public", (GETPOSTISSET("public") ? GETPOST("public", 'alphanohtml', 2) : $object->public), 1);
		print "</td></tr>\n";

		// Categories
		if (!empty($conf->categorie->enabled) && !empty($user->rights->categorie->lire)) {
			print '<tr><td>'.$form->editfieldkey("Categories", 'memcats', '', $object, 0).'</td>';
			print '<td>';
			$cate_arbo = $form->select_all_categories(Categorie::TYPE_MEMBER, null, null, null, null, 1);
			$c = new Categorie($db);
			$cats = $c->containing($object->id, Categorie::TYPE_MEMBER);
			$arrayselected = array();
			if (is_array($cats)) {
				foreach ($cats as $cat) {
					$arrayselected[] = $cat->id;
				}
			}
			print $form->multiselectarray('memcats', $cate_arbo, $arrayselected, '', 0, '', 0, '100%');
			print "</td></tr>";
		}

		// Third party Dolibarr
		if (!empty($conf->societe->enabled)) {
			print '<tr><td>'.$langs->trans("LinkedToDolibarrThirdParty").'</td><td colspan="2" class="valeur">';
			if ($object->socid) {
				$company = new Societe($db);
				$result = $company->fetch($object->socid);
				print $company->getNomUrl(1);
			} else {
				print $langs->trans("NoThirdPartyAssociatedToMember");
			}
			print '</td></tr>';
		}

		// Login Dolibarr
		print '<tr><td>'.$langs->trans("LinkedToDolibarrUser").'</td><td colspan="2" class="valeur">';
		if ($object->user_id) {
			$form->form_users($_SERVER['PHP_SELF'].'?rowid='.$object->id, $object->user_id, 'none');
		} else print $langs->trans("NoDolibarrAccess");
		print '</td></tr>';

		// Other attributes. Fields from hook formObjectOptions and Extrafields.
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

		print '</table>';
		print dol_get_fiche_end();

		print '<div class="center">';
		print '<input type="submit" class="button button-save" name="save" value="'.$langs->trans("Save").'">';
		print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		print '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
		print '</div>';

		print '</form>';
	}

	if ($id > 0 && $action != 'edit') {
		/* ************************************************************************** */
		/*                                                                            */
		/* View mode                                                                  */
		/*                                                                            */
		/* ************************************************************************** */

		$res = $object->fetch($id);
		if ($res < 0) {
			dol_print_error($db, $object->error); exit;
		}
		$res = $object->fetch_optionals();
		if ($res < 0) {
			dol_print_error($db); exit;
		}

		$adht = new AdherentType($db);
		$res = $adht->fetch($object->typeid);
		if ($res < 0) {
			dol_print_error($db); exit;
		}


		/*
		 * Show tabs
		 */
		$head = member_prepare_head($object);

		print dol_get_fiche_head($head, 'general', $langs->trans("Member"), -1, 'user');

		// Confirm create user
		if ($action == 'create_user') {
			$login = (GETPOSTISSET('login') ? GETPOST('login', 'alphanohtml') : $object->login);
			if (empty($login)) {
				// Full firstname and name separated with a dot : firstname.name
				include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
				$login = dol_buildlogin($object->lastname, $object->firstname);
			}
			if (empty($login)) $login = strtolower(substr($object->firstname, 0, 4)).strtolower(substr($object->lastname, 0, 4));

			// Create a form array
			$formquestion = array(
					array('label' => $langs->trans("LoginToCreate"), 'type' => 'text', 'name' => 'login', 'value' => $login)
			);
			if (!empty($conf->societe->enabled) && $object->socid > 0) {
				$object->fetch_thirdparty();
				$formquestion[] = array('label' => $langs->trans("UserWillBe"), 'type' => 'radio', 'name' => 'internalorexternal', 'default'=>'external', 'values' => array('external'=>$langs->trans("External").' - '.$langs->trans("LinkedToDolibarrThirdParty").' '.$object->thirdparty->getNomUrl(1, '', 0, 1), 'internal'=>$langs->trans("Internal")));
			}
			$text = '';
			if (!empty($conf->societe->enabled) && $object->socid <= 0) {
				$text .= $langs->trans("UserWillBeInternalUser").'<br>';
			}
			$text .= $langs->trans("ConfirmCreateLogin");
			print $form->formconfirm($_SERVER["PHP_SELF"]."?rowid=".$object->id, $langs->trans("CreateDolibarrLogin"), $text, "confirm_create_user", $formquestion, 'yes');
		}

		// Confirm create third party
		if ($action == 'create_thirdparty') {
			$companyalias = '';
			$fullname = $object->getFullName($langs);

			if ($object->morphy == 'mor') {
				$companyname = $object->company;
				if (!empty($fullname)) $companyalias = $fullname;
			} else {
				$companyname = $fullname;
				if (!empty($object->company)) $companyalias = $object->company;
			}

			// Create a form array
			$formquestion = array(
				array('label' => $langs->trans("NameToCreate"), 'type' => 'text', 'name' => 'companyname', 'value' => $companyname, 'morecss' => 'minwidth300', 'moreattr' => 'maxlength="128"'),
				array('label' => $langs->trans("AliasNames"), 'type' => 'text', 'name' => 'companyalias', 'value' => $companyalias, 'morecss' => 'minwidth300', 'moreattr' => 'maxlength="128"')
			);

			print $form->formconfirm($_SERVER["PHP_SELF"]."?rowid=".$object->id, $langs->trans("CreateDolibarrThirdParty"), $langs->trans("ConfirmCreateThirdParty"), "confirm_create_thirdparty", $formquestion, 'yes');
		}

		// Confirm validate member
		if ($action == 'valid') {
			$langs->load("mails");

			$adht = new AdherentType($db);
			$adht->fetch($object->typeid);

			$subject = '';
			$msg = '';

			// Send subscription email
			include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
			$formmail = new FormMail($db);
			// Set output language
			$outputlangs = new Translate('', $conf);
			$outputlangs->setDefaultLang(empty($object->thirdparty->default_lang) ? $mysoc->default_lang : $object->thirdparty->default_lang);
			// Load traductions files required by page
			$outputlangs->loadLangs(array("main", "members"));
			// Get email content from template
			$arraydefaultmessage = null;
			$labeltouse = $conf->global->ADHERENT_EMAIL_TEMPLATE_MEMBER_VALIDATION;

			if (!empty($labeltouse)) $arraydefaultmessage = $formmail->getEMailTemplate($db, 'member', $user, $outputlangs, 0, 1, $labeltouse);

			if (!empty($labeltouse) && is_object($arraydefaultmessage) && $arraydefaultmessage->id > 0) {
				$subject = $arraydefaultmessage->topic;
				$msg     = $arraydefaultmessage->content;
			}

			$substitutionarray = getCommonSubstitutionArray($outputlangs, 0, null, $object);
			complete_substitutions_array($substitutionarray, $outputlangs, $object);
			$subjecttosend = make_substitutions($subject, $substitutionarray, $outputlangs);
			$texttosend = make_substitutions(dol_concatdesc($msg, $adht->getMailOnValid()), $substitutionarray, $outputlangs);

			$tmp = $langs->trans("SendingAnEMailToMember");
			$tmp .= '<br>'.$langs->trans("MailFrom").': <b>'.$conf->global->ADHERENT_MAIL_FROM.'</b>, ';
			$tmp .= '<br>'.$langs->trans("MailRecipient").': <b>'.$object->email.'</b>';
			$helpcontent = '';
			$helpcontent .= '<b>'.$langs->trans("MailFrom").'</b>: '.$conf->global->ADHERENT_MAIL_FROM.'<br>'."\n";
			$helpcontent .= '<b>'.$langs->trans("MailRecipient").'</b>: '.$object->email.'<br>'."\n";
			$helpcontent .= '<b>'.$langs->trans("Subject").'</b>:<br>'."\n";
			$helpcontent .= $subjecttosend."\n";
			$helpcontent .= "<br>";
			$helpcontent .= '<b>'.$langs->trans("Content").'</b>:<br>';
			$helpcontent .= dol_htmlentitiesbr($texttosend)."\n";
			$label = $form->textwithpicto($tmp, $helpcontent, 1, 'help');

			// Create form popup
			$formquestion = array();
			if ($object->email) $formquestion[] = array('type' => 'checkbox', 'name' => 'send_mail', 'label' => $label, 'value' => ($conf->global->ADHERENT_DEFAULT_SENDINFOBYMAIL ?true:false));
			if (!empty($conf->mailman->enabled) && !empty($conf->global->ADHERENT_USE_MAILMAN)) {
				$formquestion[] = array('type'=>'other', 'label'=>$langs->transnoentitiesnoconv("SynchroMailManEnabled"), 'value'=>'');
			}
			if (!empty($conf->mailman->enabled) && !empty($conf->global->ADHERENT_USE_SPIP)) {
				$formquestion[] = array('type'=>'other', 'label'=>$langs->transnoentitiesnoconv("SynchroSpipEnabled"), 'value'=>'');
			}
			print $form->formconfirm("card.php?rowid=".$id, $langs->trans("ValidateMember"), $langs->trans("ConfirmValidateMember"), "confirm_valid", $formquestion, 'yes', 1, 220);
		}

		// Confirm terminate
		if ($action == 'resign') {
			$langs->load("mails");

			$adht = new AdherentType($db);
			$adht->fetch($object->typeid);

			$subject = '';
			$msg = '';

			// Send subscription email
			include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
			$formmail = new FormMail($db);
			// Set output language
			$outputlangs = new Translate('', $conf);
			$outputlangs->setDefaultLang(empty($object->thirdparty->default_lang) ? $mysoc->default_lang : $object->thirdparty->default_lang);
			// Load traductions files required by page
			$outputlangs->loadLangs(array("main", "members"));
			// Get email content from template
			$arraydefaultmessage = null;
			$labeltouse = $conf->global->ADHERENT_EMAIL_TEMPLATE_CANCELATION;

			if (!empty($labeltouse)) $arraydefaultmessage = $formmail->getEMailTemplate($db, 'member', $user, $outputlangs, 0, 1, $labeltouse);

			if (!empty($labeltouse) && is_object($arraydefaultmessage) && $arraydefaultmessage->id > 0) {
				$subject = $arraydefaultmessage->topic;
				$msg     = $arraydefaultmessage->content;
			}

			$substitutionarray = getCommonSubstitutionArray($outputlangs, 0, null, $object);
			complete_substitutions_array($substitutionarray, $outputlangs, $object);
			$subjecttosend = make_substitutions($subject, $substitutionarray, $outputlangs);
			$texttosend = make_substitutions(dol_concatdesc($msg, $adht->getMailOnResiliate()), $substitutionarray, $outputlangs);

			$tmp = $langs->trans("SendingAnEMailToMember");
			$tmp .= '<br>('.$langs->trans("MailFrom").': <b>'.$conf->global->ADHERENT_MAIL_FROM.'</b>, ';
			$tmp .= $langs->trans("MailRecipient").': <b>'.$object->email.'</b>)';
			$helpcontent = '';
			$helpcontent .= '<b>'.$langs->trans("MailFrom").'</b>: '.$conf->global->ADHERENT_MAIL_FROM.'<br>'."\n";
			$helpcontent .= '<b>'.$langs->trans("MailRecipient").'</b>: '.$object->email.'<br>'."\n";
			$helpcontent .= '<b>'.$langs->trans("Subject").'</b>:<br>'."\n";
			$helpcontent .= $subjecttosend."\n";
			$helpcontent .= "<br>";
			$helpcontent .= '<b>'.$langs->trans("Content").'</b>:<br>';
			$helpcontent .= dol_htmlentitiesbr($texttosend)."\n";
			$label = $form->textwithpicto($tmp, $helpcontent, 1, 'help');

			// Create an array
			$formquestion = array();
			if ($object->email) $formquestion[] = array('type' => 'checkbox', 'name' => 'send_mail', 'label' => $label, 'value' => (!empty($conf->global->ADHERENT_DEFAULT_SENDINFOBYMAIL) ? 'true' : 'false'));
			if ($backtopage)    $formquestion[] = array('type' => 'hidden', 'name' => 'backtopage', 'value' => ($backtopage != '1' ? $backtopage : $_SERVER["HTTP_REFERER"]));
			print $form->formconfirm("card.php?rowid=".$id, $langs->trans("ResiliateMember"), $langs->trans("ConfirmResiliateMember"), "confirm_resign", $formquestion, 'no', 1, 240);
		}

		// Confirm remove member
		if ($action == 'delete') {
			$formquestion = array();
			if ($backtopage) $formquestion[] = array('type' => 'hidden', 'name' => 'backtopage', 'value' => ($backtopage != '1' ? $backtopage : $_SERVER["HTTP_REFERER"]));
			print $form->formconfirm("card.php?rowid=".$id, $langs->trans("DeleteMember"), $langs->trans("ConfirmDeleteMember"), "confirm_delete", $formquestion, 'no', 1);
		}

		// Confirm add in spip
		if ($action == 'add_spip') {
			print $form->formconfirm("card.php?rowid=".$id, $langs->trans('AddIntoSpip'), $langs->trans('AddIntoSpipConfirmation'), 'confirm_add_spip');
		}
		// Confirm removed from spip
		if ($action == 'del_spip') {
			print $form->formconfirm("card.php?rowid=$id", $langs->trans('DeleteIntoSpip'), $langs->trans('DeleteIntoSpipConfirmation'), 'confirm_del_spip');
		}

		$rowspan = 17;
		if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED)) $rowspan++;
		if (!empty($conf->societe->enabled)) $rowspan++;

		$linkback = '<a href="'.DOL_URL_ROOT.'/adherents/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

		dol_banner_tab($object, 'rowid', $linkback);

		print '<div class="fichecenter">';
		print '<div class="fichehalfleft">';

		print '<div class="underbanner clearboth"></div>';
		print '<table class="border tableforfield centpercent">';

		// Login
		if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED)) {
			print '<tr><td class="titlefield">'.$langs->trans("Login").' / '.$langs->trans("Id").'</td><td class="valeur">'.dol_escape_htmltag($object->login).'</td></tr>';
		}

		// Type
		print '<tr><td class="titlefield">'.$langs->trans("Type").'</td><td class="valeur">'.$adht->getNomUrl(1)."</td></tr>\n";

		// Morphy
		print '<tr><td>'.$langs->trans("MemberNature").'</td><td class="valeur" >'.$object->getmorphylib().'</td>';
		print '</tr>';

		// Gender
		print '<tr><td>'.$langs->trans("Gender").'</td>';
		print '<td>';
		if ($object->gender) print $langs->trans("Gender".$object->gender);
		print '</td></tr>';

		// Company
		print '<tr><td>'.$langs->trans("Company").'</td><td class="valeur">'.dol_escape_htmltag($object->company).'</td></tr>';

		// Civility
		print '<tr><td>'.$langs->trans("UserTitle").'</td><td class="valeur">'.$object->getCivilityLabel().'</td>';
		print '</tr>';

		// Password
		if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED)) {
			print '<tr><td>'.$langs->trans("Password").'</td><td>'.preg_replace('/./i', '*', $object->pass);
			if ($object->pass) print preg_replace('/./i', '*', $object->pass);
			else {
				if ($user->admin) print $langs->trans("Crypted").': '.$object->pass_indatabase_crypted;
				else print $langs->trans("Hidden");
			}
			if ((!empty($object->pass) || !empty($object->pass_crypted)) && empty($object->user_id)) {
				$langs->load("errors");
				$htmltext = $langs->trans("WarningPasswordSetWithNoAccount");
				print ' '.$form->textwithpicto('', $htmltext, 1, 'warning');
			}
			print '</td></tr>';
		}

		// Date end subscription
		print '<tr><td>'.$langs->trans("SubscriptionEndDate").'</td><td class="valeur">';
		if ($object->datefin) {
			print dol_print_date($object->datefin, 'day');
			if ($object->hasDelay()) {
				print " ".img_warning($langs->trans("Late"));
			}
		} else {
			if ($object->need_subscription == 0) {
				print $langs->trans("SubscriptionNotNeeded");
			} elseif (!$adht->subscription) {
				print $langs->trans("SubscriptionNotRecorded");
				if ($object->statut > 0) print " ".img_warning($langs->trans("Late")); // displays delay Pictogram only if not a draft and not terminated
			} else {
				print $langs->trans("SubscriptionNotReceived");
				if ($object->statut > 0) print " ".img_warning($langs->trans("Late")); // displays delay Pictogram only if not a draft and not terminated
			}
		}
		print '</td></tr>';

		// Third party Dolibarr
		if (!empty($conf->societe->enabled)) {
			print '<tr><td>';
			$editenable = $user->rights->adherent->creer;
			print $form->editfieldkey('LinkedToDolibarrThirdParty', 'thirdparty', '', $object, $editenable);
			print '</td><td colspan="2" class="valeur">';
			if ($action == 'editthirdparty') {
				$htmlname = 'socid';
				print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'" name="form'.$htmlname.'">';
				print '<input type="hidden" name="rowid" value="'.$object->id.'">';
				print '<input type="hidden" name="action" value="set'.$htmlname.'">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
				print '<tr><td>';
				print $form->select_company($object->socid, 'socid', '', 1);
				print '</td>';
				print '<td class="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
				print '</tr></table></form>';
			} else {
				if ($object->socid) {
					$company = new Societe($db);
					$result = $company->fetch($object->socid);
					print $company->getNomUrl(1);
				} else {
					print $langs->trans("NoThirdPartyAssociatedToMember");
				}
			}
			print '</td></tr>';
		}

		// Login Dolibarr
		print '<tr><td>';
		$editenable = $user->rights->adherent->creer && $user->rights->user->user->creer;
		print $form->editfieldkey('LinkedToDolibarrUser', 'login', '', $object, $editenable);
		print '</td><td colspan="2" class="valeur">';
		if ($action == 'editlogin') {
			$form->form_users($_SERVER['PHP_SELF'].'?rowid='.$object->id, $object->user_id, 'userid', '');
		} else {
			if ($object->user_id) {
				$linkeduser = new User($db);
				$linkeduser->fetch($object->user_id);
				print $linkeduser->getNomUrl(-1);
			} else {
				print $langs->trans("NoDolibarrAccess");
			}
		}
		print '</td></tr>';

		print '</table>';

		print '</div>';

		print '<div class="fichehalfright"><div class="ficheaddleft">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border tableforfield tableforfield" width="100%">';

		// Birth Date
		print '<tr><td class="titlefield">'.$langs->trans("DateOfBirth").'</td><td class="valeur">'.dol_print_date($object->birth, 'day').'</td></tr>';

		// Public
		print '<tr><td>'.$langs->trans("Public").'</td><td class="valeur">'.yn($object->public).'</td></tr>';

		// Categories
		if (!empty($conf->categorie->enabled) && !empty($user->rights->categorie->lire)) {
			print '<tr><td>'.$langs->trans("Categories").'</td>';
			print '<td colspan="2">';
			print $form->showCategories($object->id, Categorie::TYPE_MEMBER, 1);
			print '</td></tr>';
		}

		//VCard
		print '<tr><td>';
		print $langs->trans("VCard").'</td><td colspan="3">';
		print '<a href="'.DOL_URL_ROOT.'/adherents/vcard.php?id='.$object->id.'">';
		print img_picto($langs->trans("Download"), 'vcard.png', 'class="paddingrightonly"');
		print $langs->trans("Download");
		print '</a>';
		print '</td></tr>';

		// Other attributes
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

		print "</table>\n";

		print "</div></div></div>\n";
		print '<div style="clear:both"></div>';

		print dol_get_fiche_end();


		/*
		 * Action bar
		 */

		print '<div class="tabsAction">';
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been
		if (empty($reshook)) {
			if ($action != 'editlogin' && $action != 'editthirdparty') {
				// Send
				if (empty($user->socid)) {
					if ($object->statut == 1) {
						print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=presend&mode=init#formmailbeforetitle">'.$langs->trans('SendMail').'</a></div>';
					}
				}

				// Send card by email
				// TODO Remove this to replace with a template
				/*
				if ($user->rights->adherent->creer)
				{
					if ($object->statut >= 1)
					{
						if ($object->email) print '<div class="inline-block divButAction"><a class="butAction" href="card.php?rowid='.$object->id.'&action=sendinfo">'.$langs->trans("SendCardByMail")."</a></div>\n";
						else print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NoEMail")).'">'.$langs->trans("SendCardByMail")."</a></div>\n";
					}
					else
					{
						print '<div class="inline-block divButAction"><font class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("ValidateBefore")).'">'.$langs->trans("SendCardByMail")."</font></div>";
					}
				}
				else
				{
					print '<div class="inline-block divButAction"><font class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("SendCardByMail")."</font></div>";
				}*/

				// Modify
				if ($user->rights->adherent->creer) {
					print '<div class="inline-block divButAction"><a class="butAction" href="card.php?rowid='.$id.'&action=edit">'.$langs->trans("Modify")."</a></div>";
				} else {
					print '<div class="inline-block divButAction"><font class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("Modify").'</font></div>';
				}

				// Validate
				if ($object->statut == -1) {
					if ($user->rights->adherent->creer) {
						print '<div class="inline-block divButAction"><a class="butAction" href="card.php?rowid='.$id.'&action=valid">'.$langs->trans("Validate")."</a></div>\n";
					} else {
						print '<div class="inline-block divButAction"><font class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("Validate").'</font></div>';
					}
				}

				// Reactivate
				if ($object->statut == 0) {
					if ($user->rights->adherent->creer) {
						print '<div class="inline-block divButAction"><a class="butAction" href="card.php?rowid='.$id.'&action=valid">'.$langs->trans("Reenable")."</a></div>\n";
					} else {
						print '<div class="inline-block divButAction"><font class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("Reenable")."</font></div>";
					}
				}

				// Terminate
				if ($object->statut >= 1) {
					if ($user->rights->adherent->supprimer) {
						print '<div class="inline-block divButAction"><a class="butAction" href="card.php?rowid='.$id.'&action=resign">'.$langs->trans("Resiliate")."</a></div>\n";
					} else {
						print '<div class="inline-block divButAction"><font class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("Resiliate")."</font></div>";
					}
				}

				// Create third party
				if (!empty($conf->societe->enabled) && !$object->socid) {
					if ($user->rights->societe->creer) {
						if ($object->statut != -1) print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?rowid='.$object->id.'&amp;action=create_thirdparty">'.$langs->trans("CreateDolibarrThirdParty").'</a></div>';
						else print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("ValidateBefore")).'">'.$langs->trans("CreateDolibarrThirdParty").'</a></div>';
					} else {
						print '<div class="inline-block divButAction"><font class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("CreateDolibarrThirdParty")."</font></div>";
					}
				}

				// Create user
				if (!$user->socid && !$object->user_id) {
					if ($user->rights->user->user->creer) {
						if ($object->statut != -1) print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?rowid='.$object->id.'&amp;action=create_user">'.$langs->trans("CreateDolibarrLogin").'</a></div>';
						else print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("ValidateBefore")).'">'.$langs->trans("CreateDolibarrLogin").'</a></div>';
					} else {
						print '<div class="inline-block divButAction"><font class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("CreateDolibarrLogin")."</font></div>";
					}
				}

				// Action SPIP
				if (!empty($conf->mailmanspip->enabled) && !empty($conf->global->ADHERENT_USE_SPIP)) {
					$isinspip = $mailmanspip->is_in_spip($object);

					if ($isinspip == 1) {
						print '<div class="inline-block divButAction"><a class="butAction" href="card.php?rowid='.$object->id.'&action=del_spip">'.$langs->trans("DeleteIntoSpip")."</a></div>\n";
					}
					if ($isinspip == 0) {
						print '<div class="inline-block divButAction"><a class="butAction" href="card.php?rowid='.$object->id.'&action=add_spip">'.$langs->trans("AddIntoSpip")."</a></div>\n";
					}
				}

				// Delete
				if ($user->rights->adherent->supprimer) {
					print '<div class="inline-block divButAction"><a class="butActionDelete" href="card.php?rowid='.$object->id.'&action=delete&token='.newToken().'">'.$langs->trans("Delete")."</a></div>\n";
				} else {
					print '<div class="inline-block divButAction"><font class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("Delete")."</font></div>";
				}
			}
		}
		print '</div>';

		if ($isinspip == -1) {
			print '<br><br><font class="error">'.$langs->trans('SPIPConnectionFailed').': '.$mailmanspip->error.'</font>';
		}


		// Select mail models is same action as presend
		if (GETPOST('modelselected')) {
			$action = 'presend';
		}

		if ($action != 'presend') {
			print '<div class="fichecenter"><div class="fichehalfleft">';
			print '<a name="builddoc"></a>'; // ancre

			// Documents generes
			$filename = dol_sanitizeFileName($object->ref);
			//$filename =  'tmp_cards.php';
			//$filedir = $conf->adherent->dir_output . '/' . get_exdir($object->id, 2, 0, 0, $object, 'member') . dol_sanitizeFileName($object->ref);
			$filedir = $conf->adherent->dir_output.'/'.get_exdir(0, 0, 0, 0, $object, 'member');
			$urlsource = $_SERVER['PHP_SELF'].'?id='.$object->id;
			$genallowed = $user->rights->adherent->lire;
			$delallowed = $user->rights->adherent->creer;

			print $formfile->showdocuments('member', $filename, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', $object->default_lang, '', $object);
			$somethingshown = $formfile->numoffiles;

			// Show links to link elements
			//$linktoelem = $form->showLinkToObjectBlock($object, null, array('subscription'));
			//$somethingshown = $form->showLinkedObjectBlock($object, '');

			// Show links to link elements
			/*$linktoelem = $form->showLinkToObjectBlock($object,array('order'));
			 if ($linktoelem) print ($somethingshown?'':'<br>').$linktoelem;
			 */

			// Show online payment link
			$useonlinepayment = (!empty($conf->paypal->enabled) || !empty($conf->stripe->enabled) || !empty($conf->paybox->enabled));

			if ($useonlinepayment) {
				print '<br>';

				require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
				print showOnlinePaymentUrl('membersubscription', $object->ref);
			}

			print '</div><div class="fichehalfright"><div class="ficheaddleft">';

			$MAX = 10;

			$morehtmlright = '<a href="'.DOL_URL_ROOT.'/adherents/agenda.php?id='.$object->id.'">';
			$morehtmlright .= $langs->trans("SeeAll");
			$morehtmlright .= '</a>';

			// List of actions on element
			include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
			$formactions = new FormActions($db);
			$somethingshown = $formactions->showactions($object, 'member', $socid, 1, 'listactions', $MAX, '', $morehtmlright);

			print '</div></div></div>';
		}

		// Presend form
		$modelmail = 'member';
		$defaulttopic = 'CardContent';
		$diroutput = $conf->adherent->dir_output;
		$trackid = 'mem'.$object->id;

		include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
	}
}

// End of page
llxFooter();
$db->close();
