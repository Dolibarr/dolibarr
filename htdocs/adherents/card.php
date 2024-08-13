<?php
/* Copyright (C) 2001-2004	Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003	Jean-Louis Bergamo			<jlb@j1b.org>
 * Copyright (C) 2004-2012	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2018	Regis Houssin				<regis.houssin@inodbox.com>
 * Copyright (C) 2012		Marcos García				<marcosgdf@gmail.com>
 * Copyright (C) 2012-2020	Philippe Grand				<philippe.grand@atoo-net.com>
 * Copyright (C) 2015-2024	Alexandre Spangaro			<alexandre@inovea-conseil.com>
 * Copyright (C) 2018-2024	Frédéric France				<frederic.france@free.fr>
 * Copyright (C) 2021		Waël Almoman				<info@almoman.com>
 * Copyright (C) 2024       MDW							<mdeweerd@users.noreply.github.com>
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


// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/member.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/subscription.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';


// Load translation files required by the page
$langs->loadLangs(array("companies", "bills", "members", "users", "other", "paypal"));


// Get parameters
$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$rowid = GETPOSTINT('rowid');
$id = GETPOST('id') ? GETPOSTINT('id') : $rowid;
$typeid = GETPOSTINT('typeid');
$userid = GETPOSTINT('userid');
$socid = GETPOSTINT('socid');
$ref = GETPOST('ref', 'alpha');

if (isModEnabled('mailmanspip')) {
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

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('membercard', 'globalcard'));

// Fetch object
if ($id > 0 || !empty($ref)) {
	// Load member
	$result = $object->fetch($id, $ref);

	// Define variables to know what current user can do on users
	$canadduser = ($user->admin || $user->hasRight('user', 'user', 'creer'));
	// Define variables to know what current user can do on properties of user linked to edited member
	if ($object->user_id) {
		// $User is the user who edits, $object->user_id is the id of the related user in the edited member
		$caneditfielduser = ((($user->id == $object->user_id) && $user->hasRight('user', 'self', 'creer'))
			|| (($user->id != $object->user_id) && $user->hasRight('user', 'user', 'creer')));
		$caneditpassworduser = ((($user->id == $object->user_id) && $user->hasRight('user', 'self', 'password'))
			|| (($user->id != $object->user_id) && $user->hasRight('user', 'user', 'password')));
	}
}

// Define variables to determine what the current user can do on the members
$canaddmember = $user->hasRight('adherent', 'creer');
// Define variables to determine what the current user can do on the properties of a member
if ($id) {
	$caneditfieldmember = $user->hasRight('adherent', 'creer');
}

// Security check
$result = restrictedArea($user, 'adherent', $object->id, '', '', 'socid', 'rowid', 0);

if (!$user->hasRight('adherent', 'creer') && $action == 'edit') {
	accessforbidden('Not enough permission');
}

$linkofpubliclist = DOL_MAIN_URL_ROOT.'/public/members/public_list.php'.((isModEnabled('multicompany')) ? '?entity='.$conf->entity : '');


/*
 * 	Actions
 */

$parameters = array('id' => $id, 'rowid' => $id, 'objcanvas' => $objcanvas, 'confirm' => $confirm);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	$backurlforlist = DOL_URL_ROOT.'/adherents/list.php';

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = DOL_URL_ROOT.'/adherents/card.php?id='.((!empty($id) && $id > 0) ? $id : '__ID__');
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

	if ($action == 'setuserid' && ($user->hasRight('user', 'self', 'creer') || $user->hasRight('user', 'user', 'creer'))) {
		$error = 0;
		if (!$user->hasRight('user', 'user', 'creer')) {	// If can edit only itself user, we can link to itself only
			if ($userid != $user->id && $userid != $object->user_id) {
				$error++;
				setEventMessages($langs->trans("ErrorUserPermissionAllowsToLinksToItselfOnly"), null, 'errors');
			}
		}

		if (!$error) {
			if ($userid != $object->user_id) {	// If link differs from currently in database
				$result = $object->setUserId($userid);
				if ($result < 0) {
					dol_print_error($object->db, $object->error);
				}
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
					if ($result < 0) {
						dol_print_error($object->db, $object->error);
					}
					$action = '';
				}
			}
		}
	}

	// Create user from a member
	if ($action == 'confirm_create_user' && $confirm == 'yes' && $user->hasRight('user', 'user', 'creer')) {
		if ($result > 0) {
			// Creation user
			$nuser = new User($db);
			$tmpuser = dol_clone($object, 2);
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
	if ($action == 'confirm_create_thirdparty' && $confirm == 'yes' && $user->hasRight('societe', 'creer')) {
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

	if ($action == 'update' && !$cancel && $user->hasRight('adherent', 'creer')) {
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$birthdate = '';
		if (GETPOSTINT("birthday") && GETPOSTINT("birthmonth") && GETPOSTINT("birthyear")) {
			$birthdate = dol_mktime(12, 0, 0, GETPOSTINT("birthmonth"), GETPOSTINT("birthday"), GETPOSTINT("birthyear"));
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
		if (!getDolGlobalString('ADHERENT_LOGIN_NOT_REQUIRED')) {
			if (empty($login)) {
				$error++;
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Login")), null, 'errors');
			}
		}
		// Create new object
		if ($result > 0 && !$error) {
			$object->oldcopy = dol_clone($object, 2);

			// Change values
			$object->civility_id = trim(GETPOST("civility_id", 'alphanohtml'));
			$object->firstname   = trim(GETPOST("firstname", 'alphanohtml'));
			$object->lastname    = trim(GETPOST("lastname", 'alphanohtml'));
			$object->gender      = trim(GETPOST("gender", 'alphanohtml'));
			$object->login       = trim(GETPOST("login", 'alphanohtml'));
			if (GETPOSTISSET('pass')) {
				$object->pass        = trim(GETPOST("pass", 'password'));	// For password, we must use 'none'
			}

			$object->societe     = trim(GETPOST("societe", 'alphanohtml')); // deprecated
			$object->company     = trim(GETPOST("societe", 'alphanohtml'));

			$object->address     = trim(GETPOST("address", 'alphanohtml'));
			$object->zip         = trim(GETPOST("zipcode", 'alphanohtml'));
			$object->town        = trim(GETPOST("town", 'alphanohtml'));
			$object->state_id    = GETPOSTINT("state_id");
			$object->country_id  = GETPOSTINT("country_id");

			$object->phone       = trim(GETPOST("phone", 'alpha'));
			$object->phone_perso = trim(GETPOST("phone_perso", 'alpha'));
			$object->phone_mobile = trim(GETPOST("phone_mobile", 'alpha'));
			$object->email = preg_replace('/\s+/', '', GETPOST("member_email", 'alpha'));
			$object->url = trim(GETPOST('member_url', 'custom', 0, FILTER_SANITIZE_URL));
			$object->socialnetworks = array();
			foreach ($socialnetworks as $key => $value) {
				if (GETPOSTISSET($key) && GETPOST($key, 'alphanohtml') != '') {
					$object->socialnetworks[$key] = trim(GETPOST($key, 'alphanohtml'));
				}
			}
			$object->birth = $birthdate;
			$object->default_lang = GETPOST('default_lang', 'alpha');
			$object->typeid = GETPOSTINT("typeid");
			//$object->note = trim(GETPOST("comment", "restricthtml"));
			$object->morphy = GETPOST("morphy", 'alpha');

			if (GETPOST('deletephoto', 'alpha')) {
				$object->photo = '';
			} elseif (!empty($_FILES['photo']['name'])) {
				$object->photo = dol_sanitizeFileName($_FILES['photo']['name']);
			}

			// Get status and public property
			$object->statut = GETPOSTINT("statut");
			$object->status = GETPOSTINT("statut");
			$object->public = GETPOSTINT("public");

			// Fill array 'array_options' with data from add form
			$ret = $extrafields->setOptionalsFromPost(null, $object, '@GETPOSTISSET');
			if ($ret < 0) {
				$error++;
			}

			// Check if we need to also synchronize user information
			$nosyncuser = 0;
			if ($object->user_id) {	// If linked to a user
				if ($user->id != $object->user_id && !$user->hasRight('user', 'user', 'creer')) {
					$nosyncuser = 1; // Disable synchronizing
				}
			}

			// Check if we need to also synchronize password information
			$nosyncuserpass = 1;	// no by default
			if (GETPOSTISSET('pass')) {
				if ($object->user_id) {	// If member is linked to a user
					$nosyncuserpass = 0;	// We may try to sync password
					if ($user->id == $object->user_id) {
						if (!$user->hasRight('user', 'self', 'password')) {
							$nosyncuserpass = 1; // Disable synchronizing
						}
					} else {
						if (!$user->hasRight('user', 'user', 'password')) {
							$nosyncuserpass = 1; // Disable synchronizing
						}
					}
				}
			}

			if (!$error) {
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
		} else {
			$action = 'edit';
		}
	}

	if ($action == 'add' && $user->hasRight('adherent', 'creer')) {
		if ($canvas) {
			$object->canvas = $canvas;
		}
		$birthdate = '';
		if (GETPOSTISSET("birthday") && GETPOST("birthday") && GETPOSTISSET("birthmonth") && GETPOST("birthmonth") && GETPOSTISSET("birthyear") && GETPOST("birthyear")) {
			$birthdate = dol_mktime(12, 0, 0, GETPOSTINT("birthmonth"), GETPOSTINT("birthday"), GETPOSTINT("birthyear"));
		}
		$datesubscription = '';
		if (GETPOSTISSET("reday") && GETPOSTISSET("remonth") && GETPOSTISSET("reyear")) {
			$datesubscription = dol_mktime(12, 0, 0, GETPOSTINT("remonth"), GETPOSTINT("reday"), GETPOSTINT("reyear"));
		}

		$typeid = GETPOSTINT("typeid");
		$civility_id = GETPOST("civility_id", 'alphanohtml');
		$lastname = GETPOST("lastname", 'alphanohtml');
		$firstname = GETPOST("firstname", 'alphanohtml');
		$gender = GETPOST("gender", 'alphanohtml');
		$societe = GETPOST("societe", 'alphanohtml');
		$address = GETPOST("address", 'alphanohtml');
		$zip = GETPOST("zipcode", 'alphanohtml');
		$town = GETPOST("town", 'alphanohtml');
		$state_id = GETPOSTINT("state_id");
		$country_id = GETPOSTINT("country_id");

		$phone = GETPOST("phone", 'alpha');
		$phone_perso = GETPOST("phone_perso", 'alpha');
		$phone_mobile = GETPOST("phone_mobile", 'alpha');
		$email = preg_replace('/\s+/', '', GETPOST("member_email", 'aZ09arobase'));
		$url = trim(GETPOST('url', 'custom', 0, FILTER_SANITIZE_URL));
		$login = GETPOST("member_login", 'alphanohtml');
		$pass = GETPOST("password", 'password');	// For password, we use 'none'
		$photo = GETPOST("photo", 'alphanohtml');
		$morphy = GETPOST("morphy", 'alphanohtml');
		$public = GETPOST("public", 'alphanohtml');

		$userid = GETPOSTINT("userid");
		$socid = GETPOSTINT("socid");
		$default_lang = GETPOST('default_lang', 'alpha');

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
		if (isModEnabled('socialnetworks')) {
			foreach ($socialnetworks as $key => $value) {
				if (GETPOSTISSET($key) && GETPOST($key, 'alphanohtml') != '') {
					$object->socialnetworks[$key] = GETPOST("member_".$key, 'alphanohtml');
				}
			}
		}

		$object->email       = $email;
		$object->url       	 = $url;
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
		$object->default_lang = $default_lang;
		// Fill array 'array_options' with data from add form
		$ret = $extrafields->setOptionalsFromPost(null, $object);
		if ($ret < 0) {
			$error++;
		}

		// Check parameters
		if (empty($morphy) || $morphy == "-1") {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("MemberNature")), null, 'errors');
		}
		// Tests if the login already exists
		if (!getDolGlobalString('ADHERENT_LOGIN_NOT_REQUIRED')) {
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
		if (getDolGlobalString('ADHERENT_MAIL_REQUIRED') && !isValidEmail($email)) {
			$error++;
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorBadEMail", $email), null, 'errors');
		}
		if (!empty($object->url) && !isValidUrl($object->url)) {
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorBadUrl", $object->url), null, 'errors');
		}
		$public = 0;
		if (isset($public)) {
			$public = 1;
		}

		if (!$error) {
			$db->begin();

			// Create the member
			$result = $object->create($user);
			if ($result > 0) {
				// Foundation categories
				$memcats = GETPOST('memcats', 'array');
				$object->setCategories($memcats);

				$db->commit();

				$rowid = $object->id;
				$id = $object->id;

				$backtopage = preg_replace('/__ID__/', (string) $id, $backtopage);
			} else {
				$db->rollback();

				$error++;
				setEventMessages($object->error, $object->errors, 'errors');
			}

			// Auto-create thirdparty on member creation
			if (getDolGlobalString('ADHERENT_DEFAULT_CREATE_THIRDPARTY')) {
				if ($result > 0) {
					// Create third party out of a member
					$company = new Societe($db);
					$result = $company->create_from_member($object);
					if ($result < 0) {
						$langs->load("errors");
						setEventMessages($langs->trans($company->error), null, 'errors');
						setEventMessages($company->error, $company->errors, 'errors');
					}
				} else {
					setEventMessages($object->error, $object->errors, 'errors');
				}
			}
		}
		$action = ($result < 0 || !$error) ? '' : 'create';

		if (!$error && $backtopage) {
			header("Location: ".$backtopage);
			exit;
		}
	}

	if ($user->hasRight('adherent', 'supprimer') && $action == 'confirm_delete' && $confirm == 'yes') {
		$result = $object->delete($user);
		if ($result > 0) {
			setEventMessages($langs->trans("RecordDeleted"), null, 'errors');
			if (!empty($backtopage) && !preg_match('/'.preg_quote($_SERVER["PHP_SELF"], '/').'/', $backtopage)) {
				header("Location: ".$backtopage);
				exit;
			} else {
				header("Location: list.php");
				exit;
			}
		} else {
			setEventMessages($object->error, null, 'errors');
		}
	}

	if ($user->hasRight('adherent', 'creer') && $action == 'confirm_valid' && $confirm == 'yes') {
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
				$outputlangs->loadLangs(array("main", "members", "companies", "install", "other"));
				// Get email content from template
				$arraydefaultmessage = null;
				$labeltouse = getDolGlobalString('ADHERENT_EMAIL_TEMPLATE_MEMBER_VALIDATION');

				if (!empty($labeltouse)) {
					$arraydefaultmessage = $formmail->getEMailTemplate($db, 'member', $user, $outputlangs, 0, 1, $labeltouse);
				}

				if (!empty($labeltouse) && is_object($arraydefaultmessage) && $arraydefaultmessage->id > 0) {
					$subject = $arraydefaultmessage->topic;
					$msg     = $arraydefaultmessage->content;
				}

				if (empty($labeltouse) || (int) $labeltouse === -1) {
					//fallback on the old configuration.
					$langs->load("errors");
					setEventMessages('<a href="'.DOL_URL_ROOT.'/adherents/admin/member_emails.php">'.$langs->trans('WarningMandatorySetupNotComplete').'</a>', null, 'errors');
					$error++;
				} else {
					$substitutionarray = getCommonSubstitutionArray($outputlangs, 0, null, $object);
					complete_substitutions_array($substitutionarray, $outputlangs, $object);
					$subjecttosend = make_substitutions($subject, $substitutionarray, $outputlangs);
					$texttosend = make_substitutions(dol_concatdesc($msg, $adht->getMailOnValid()), $substitutionarray, $outputlangs);

					$moreinheader = 'X-Dolibarr-Info: send_an_email by adherents/card.php'."\r\n";

					$result = $object->sendEmail($texttosend, $subjecttosend, array(), array(), array(), "", "", 0, -1, '', $moreinheader);
					if ($result < 0) {
						$error++;
						setEventMessages($object->error, $object->errors, 'errors');
					}
				}
			}
		} else {
			$error++;
			setEventMessages($object->error, $object->errors, 'errors');
		}

		if (!$error) {
			$db->commit();
		} else {
			$db->rollback();
		}
		$action = '';
	}

	if ($user->hasRight('adherent', 'supprimer') && $action == 'confirm_resiliate') {
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
					$outputlangs->loadLangs(array("main", "members", "companies", "install", "other"));
					// Get email content from template
					$arraydefaultmessage = null;
					$labeltouse = getDolGlobalString('ADHERENT_EMAIL_TEMPLATE_CANCELATION');

					if (!empty($labeltouse)) {
						$arraydefaultmessage = $formmail->getEMailTemplate($db, 'member', $user, $outputlangs, 0, 1, $labeltouse);
					}

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

						$result = $object->sendEmail($texttosend, $subjecttosend, array(), array(), array(), "", "", 0, -1, '', $moreinheader);
						if ($result < 0) {
							$error++;
							setEventMessages($object->error, $object->errors, 'errors');
						}
					}
				}
			} else {
				$error++;

				setEventMessages($object->error, $object->errors, 'errors');
				$action = '';
			}
		}
		if (!empty($backtopage) && !$error) {
			header("Location: ".$backtopage);
			exit;
		}
	}

	if ($user->hasRight('adherent', 'supprimer') && $action == 'confirm_exclude') {
		$error = 0;

		if ($confirm == 'yes') {
			$adht = new AdherentType($db);
			$adht->fetch($object->typeid);

			$result = $object->exclude($user);

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
					$outputlangs->loadLangs(array("main", "members", "companies", "install", "other"));
					// Get email content from template
					$arraydefaultmessage = null;
					$labeltouse = getDolGlobalString('ADHERENT_EMAIL_TEMPLATE_EXCLUSION');

					if (!empty($labeltouse)) {
						$arraydefaultmessage = $formmail->getEMailTemplate($db, 'member', $user, $outputlangs, 0, 1, $labeltouse);
					}

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
						$texttosend = make_substitutions(dol_concatdesc($msg, $adht->getMailOnExclude()), $substitutionarray, $outputlangs);

						$moreinheader = 'X-Dolibarr-Info: send_an_email by adherents/card.php'."\r\n";

						$result = $object->sendEmail($texttosend, $subjecttosend, array(), array(), array(), "", "", 0, -1, '', $moreinheader);
						if ($result < 0) {
							$error++;
							setEventMessages($object->error, $object->errors, 'errors');
						}
					}
				}
			} else {
				$error++;

				setEventMessages($object->error, $object->errors, 'errors');
				$action = '';
			}
		}
		if (!empty($backtopage) && !$error) {
			header("Location: ".$backtopage);
			exit;
		}
	}

	// SPIP Management
	if ($user->hasRight('adherent', 'supprimer') && $action == 'confirm_del_spip' && $confirm == 'yes') {
		if (!count($object->errors)) {
			if (!$mailmanspip->del_to_spip($object)) {
				setEventMessages($langs->trans('DeleteIntoSpipError').': '.$mailmanspip->error, null, 'errors');
			}
		}
	}

	if ($user->hasRight('adherent', 'creer') && $action == 'confirm_add_spip' && $confirm == 'yes') {
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
	$permissiontoadd = $user->hasRight('adherent', 'creer');
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
$formadmin = new FormAdmin($db);
$formcompany = new FormCompany($db);

$title = $langs->trans("Member")." - ".$langs->trans("Card");
$help_url = 'EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros|DE:Modul_Mitglieder';

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-member page-card');

$countrynotdefined = $langs->trans("ErrorSetACountryFirst").' ('.$langs->trans("SeeAbove").')';

if (is_object($objcanvas) && $objcanvas->displayCanvasExists($action)) {
	// -----------------------------------------
	// When used with CANVAS
	// -----------------------------------------
	if (empty($object->error) && $id) {
		$object = new Adherent($db);
		$result = $object->fetch($id);
		if ($result <= 0) {
			dol_print_error(null, $object->error);
		}
	}
	$objcanvas->assign_values($action, $object->id, $object->ref); // Set value for templates
	$objcanvas->display_canvas($action); // Show template
} else {
	// -----------------------------------------
	// When used in standard mode
	// -----------------------------------------

	// Create mode
	if ($action == 'create') {
		$object->canvas = $canvas;
		$object->state_id = GETPOSTINT('state_id');

		// We set country_id, country_code and country for the selected country
		$object->country_id = GETPOSTINT('country_id') ? GETPOSTINT('country_id') : $mysoc->country_id;
		if ($object->country_id) {
			$tmparray = getCountry($object->country_id, 'all');
			$object->country_code = $tmparray['code'];
			$object->country = $tmparray['label'];
		}

		$soc = new Societe($db);
		if (!empty($socid)) {
			if ($socid > 0) {
				$soc->fetch($socid);
			}

			if (!($soc->id > 0)) {
				$langs->load("errors");
				print($langs->trans('ErrorRecordNotFound'));
				exit;
			}
		}

		$adht = new AdherentType($db);

		print load_fiche_titre($langs->trans("NewMember"), '', $object->picto);

		if ($conf->use_javascript_ajax) {
			print "\n".'<script type="text/javascript">'."\n";
			print 'jQuery(document).ready(function () {
						jQuery("#selectcountry_id").change(function() {
							document.formsoc.action.value="create";
							document.formsoc.submit();
						});
						function initfieldrequired() {
							jQuery("#tdcompany").removeClass("fieldrequired");
							jQuery("#tdlastname").removeClass("fieldrequired");
							jQuery("#tdfirstname").removeClass("fieldrequired");
							if (jQuery("#morphy").val() == \'mor\') {
								jQuery("#tdcompany").addClass("fieldrequired");
							}
							if (jQuery("#morphy").val() == \'phy\') {
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
		if ($backtopage) {
			print '<input type="hidden" name="backtopage" value="'.($backtopage != '1' ? $backtopage : $_SERVER["HTTP_REFERER"]).'">';
		}

		print dol_get_fiche_head('');

		print '<table class="border centpercent">';
		print '<tbody>';

		// Login
		if (!getDolGlobalString('ADHERENT_LOGIN_NOT_REQUIRED')) {
			print '<tr><td><span class="fieldrequired">'.$langs->trans("Login").' / '.$langs->trans("Id").'</span></td><td><input type="text" name="member_login" class="minwidth300" maxlength="50" value="'.(GETPOSTISSET("member_login") ? GETPOST("member_login", 'alphanohtml', 2) : $object->login).'" autofocus="autofocus"></td></tr>';
		}

		// Password
		if (!getDolGlobalString('ADHERENT_LOGIN_NOT_REQUIRED')) {
			require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
			$generated_password = getRandomPassword(false);
			print '<tr><td><span class="fieldrequired">'.$langs->trans("Password").'</span></td><td>';
			print '<input type="text" class="minwidth300" maxlength="50" name="password" value="'.dol_escape_htmltag($generated_password).'">';
			print '</td></tr>';
		}

		// Type
		print '<tr><td class="fieldrequired">'.$langs->trans("MemberType").'</td><td>';
		$listetype = $adht->liste_array(1);
		print img_picto('', $adht->picto, 'class="pictofixedwidth"');
		if (count($listetype)) {
			print $form->selectarray("typeid", $listetype, (GETPOSTINT('typeid') ? GETPOSTINT('typeid') : $typeid), (count($listetype) > 1 ? 1 : 0), 0, 0, '', 0, 0, 0, '', 'minwidth200', 1);
		} else {
			print '<span class="error">'.$langs->trans("NoTypeDefinedGoToSetup").'</span>';
		}
		if ($user->hasRight('member', 'configurer')) {
			print ' <a href="'.DOL_URL_ROOT.'/adherents/type.php?action=create&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create&typeid=--IDFORBACKTOPAGE--').'"><span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("NewMemberType").'"></span></a>';
		}
		print "</td>\n";

		// Morphy
		$morphys = array();
		$morphys["phy"] = $langs->trans("Physical");
		$morphys["mor"] = $langs->trans("Moral");
		print '<tr><td class="fieldrequired">'.$langs->trans("MemberNature")."</td><td>\n";
		print $form->selectarray("morphy", $morphys, (GETPOST('morphy', 'alpha') ? GETPOST('morphy', 'alpha') : $object->morphy), 1, 0, 0, '', 0, 0, 0, '', 'minwidth200', 1);
		print "</td>\n";

		// Company
		print '<tr><td id="tdcompany">'.$langs->trans("Company").'</td><td><input type="text" name="societe" class="minwidth300" maxlength="128" value="'.(GETPOSTISSET('societe') ? GETPOST('societe', 'alphanohtml') : $soc->name).'"></td></tr>';

		// Civility
		print '<tr><td>'.$langs->trans("UserTitle").'</td><td>';
		print $formcompany->select_civility(GETPOSTINT('civility_id') ? GETPOSTINT('civility_id') : $object->civility_id, 'civility_id', 'maxwidth150', 1).'</td>';
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
		$arraygender = array('man' => $langs->trans("Genderman"), 'woman' => $langs->trans("Genderwoman"), 'other' => $langs->trans("Genderother"));
		print $form->selectarray('gender', $arraygender, GETPOST('gender', 'alphanohtml'), 1, 0, 0, '', 0, 0, 0, '', 'minwidth100', 1);
		print '</td></tr>';

		// EMail
		print '<tr><td>'.(getDolGlobalString('ADHERENT_MAIL_REQUIRED') ? '<span class="fieldrequired">' : '').$langs->trans("EMail").(getDolGlobalString('ADHERENT_MAIL_REQUIRED') ? '</span>' : '').'</td>';
		print '<td>'.img_picto('', 'object_email').' <input type="text" name="member_email" class="minwidth300" maxlength="255" value="'.(GETPOSTISSET('member_email') ? GETPOST('member_email', 'alpha') : $soc->email).'"></td></tr>';

		// Website
		print '<tr><td>'.$form->editfieldkey('Web', 'member_url', GETPOST('member_url', 'alpha'), $object, 0).'</td>';
		print '<td>'.img_picto('', 'globe').' <input type="text" class="maxwidth500 widthcentpercentminusx" name="member_url" id="member_url" value="'.(GETPOSTISSET('member_url') ? GETPOST('member_url', 'alpha') : $object->url).'"></td></tr>';

		// Address
		print '<tr><td class="tdtop">'.$langs->trans("Address").'</td><td>';
		print '<textarea name="address" wrap="soft" class="quatrevingtpercent" rows="2">'.(GETPOSTISSET('address') ? GETPOST('address', 'alphanohtml') : $soc->address).'</textarea>';
		print '</td></tr>';

		// Zip / Town
		print '<tr><td>'.$langs->trans("Zip").' / '.$langs->trans("Town").'</td><td>';
		print $formcompany->select_ziptown((GETPOSTISSET('zipcode') ? GETPOST('zipcode', 'alphanohtml') : $soc->zip), 'zipcode', array('town', 'selectcountry_id', 'state_id'), 6);
		print ' ';
		print $formcompany->select_ziptown((GETPOSTISSET('town') ? GETPOST('town', 'alphanohtml') : $soc->town), 'town', array('zipcode', 'selectcountry_id', 'state_id'));
		print '</td></tr>';

		// Country
		if (empty($soc->country_id)) {
			$soc->country_id = $mysoc->country_id;
			$soc->country_code = $mysoc->country_code;
			$soc->state_id = $mysoc->state_id;
		}
		print '<tr><td>'.$langs->trans('Country').'</td><td>';
		print img_picto('', 'country', 'class="pictofixedwidth"');
		print $form->select_country(GETPOSTISSET('country_id') ? GETPOST('country_id', 'alpha') : $soc->country_id, 'country_id');
		if ($user->admin) {
			print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
		}
		print '</td></tr>';

		// State
		if (!getDolGlobalString('MEMBER_DISABLE_STATE')) {
			print '<tr><td>'.$langs->trans('State').'</td><td>';
			if ($soc->country_id) {
				print img_picto('', 'state', 'class="pictofixedwidth"');
				print $formcompany->select_state(GETPOSTISSET('state_id') ? GETPOSTINT('state_id') : $soc->state_id, $soc->country_code);
			} else {
				print $countrynotdefined;
			}
			print '</td></tr>';
		}

		// Pro phone
		print '<tr><td>'.$langs->trans("PhonePro").'</td>';
		print '<td>'.img_picto('', 'object_phoning', 'class="pictofixedwidth"').'<input type="text" name="phone" size="20" value="'.(GETPOSTISSET('phone') ? GETPOST('phone', 'alpha') : $soc->phone).'"></td></tr>';

		// Personal phone
		print '<tr><td>'.$langs->trans("PhonePerso").'</td>';
		print '<td>'.img_picto('', 'object_phoning', 'class="pictofixedwidth"').'<input type="text" name="phone_perso" size="20" value="'.(GETPOSTISSET('phone_perso') ? GETPOST('phone_perso', 'alpha') : $object->phone_perso).'"></td></tr>';

		// Mobile phone
		print '<tr><td>'.$langs->trans("PhoneMobile").'</td>';
		print '<td>'.img_picto('', 'object_phoning_mobile', 'class="pictofixedwidth"').'<input type="text" name="phone_mobile" size="20" value="'.(GETPOSTISSET('phone_mobile') ? GETPOST('phone_mobile', 'alpha') : $object->phone_mobile).'"></td></tr>';

		if (isModEnabled('socialnetworks')) {
			foreach ($socialnetworks as $key => $value) {
				if (!$value['active']) {
					break;
				}
				$val = (GETPOSTISSET('member_'.$key) ? GETPOST('member_'.$key, 'alpha') : (empty($object->socialnetworks[$key]) ? '' : $object->socialnetworks[$key]));
				print '<tr><td>'.$langs->trans($value['label']).'</td><td><input type="text" name="member_'.$key.'" size="40" value="'.$val.'"></td></tr>';
			}
		}

		// Birth Date
		print "<tr><td>".$langs->trans("DateOfBirth")."</td><td>\n";
		print img_picto('', 'object_calendar', 'class="pictofixedwidth"').$form->selectDate(($object->birth ? $object->birth : -1), 'birth', 0, 0, 1, 'formsoc');
		print "</td></tr>\n";

		// Public profil
		print "<tr><td>";
		$htmltext = $langs->trans("Public", getDolGlobalString('MAIN_INFO_SOCIETE_NOM'), $linkofpubliclist);
		print $form->textwithpicto($langs->trans("MembershipPublic"), $htmltext, 1, 'help', '', 0, 3, 'membershippublic');
		print "</td><td>\n";
		print $form->selectyesno("public", $object->public, 1, false, 0, 1);
		print "</td></tr>\n";

		// Categories
		if (isModEnabled('category') && $user->hasRight('categorie', 'lire')) {
			print '<tr><td>'.$form->editfieldkey("Categories", 'memcats', '', $object, 0).'</td><td>';
			$cate_arbo = $form->select_all_categories(Categorie::TYPE_MEMBER, '', 'parent', 64, 0, 3);
			print img_picto('', 'category').$form->multiselectarray('memcats', $cate_arbo, GETPOST('memcats', 'array'), 0, 0, 'quatrevingtpercent widthcentpercentminusx', 0, 0);
			print "</td></tr>";
		}

		// Other attributes
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

		print '<tbody>';
		print "</table>\n";

		print dol_get_fiche_end();

		print $form->buttonsSaveCancel("AddMember");

		print "</form>\n";
	}

	// Edit mode
	if ($action == 'edit') {
		$res = $object->fetch($id);
		if ($res < 0) {
			dol_print_error($db, $object->error);
			exit;
		}
		$res = $object->fetch_optionals();
		if ($res < 0) {
			dol_print_error($db);
			exit;
		}

		$adht = new AdherentType($db);
		$adht->fetch($object->typeid);

		// We set country_id, and country_code, country of the chosen country
		$country = GETPOSTINT('country');
		if (!empty($country) || $object->country_id) {
			$sql = "SELECT rowid, code, label from ".MAIN_DB_PREFIX."c_country";
			$sql .= " WHERE rowid = ".(int) (!empty($country) ? $country : $object->country_id);
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
			print "\n".'<script type="text/javascript">';
			print 'jQuery(document).ready(function () {
				jQuery("#selectcountry_id").change(function() {
					document.formsoc.action.value="edit";
					document.formsoc.submit();
				});
				function initfieldrequired() {
					jQuery("#tdcompany").removeClass("fieldrequired");
					jQuery("#tdlastname").removeClass("fieldrequired");
					jQuery("#tdfirstname").removeClass("fieldrequired");
					if (jQuery("#morphy").val() == \'mor\') {
						jQuery("#tdcompany").addClass("fieldrequired");
					}
					if (jQuery("#morphy").val() == \'phy\') {
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
		print '<input type="hidden" name="statut" value="'.$object->status.'" />';
		if ($backtopage) {
			print '<input type="hidden" name="backtopage" value="'.($backtopage != '1' ? $backtopage : $_SERVER["HTTP_REFERER"]).'">';
		}

		print dol_get_fiche_head($head, 'general', $langs->trans("Member"), 0, 'user');

		print '<table class="border centpercent">';

		// Ref
		print '<tr><td class="titlefieldcreate">'.$langs->trans("Ref").'</td><td class="valeur">'.$object->ref.'</td></tr>';

		// Login
		if (!getDolGlobalString('ADHERENT_LOGIN_NOT_REQUIRED')) {
			print '<tr><td><span class="fieldrequired">'.$langs->trans("Login").' / '.$langs->trans("Id").'</span></td><td><input type="text" name="login" class="minwidth300" maxlength="50" value="'.(GETPOSTISSET("login") ? GETPOST("login", 'alphanohtml', 2) : $object->login).'"></td></tr>';
		}

		// Password
		if (!getDolGlobalString('ADHERENT_LOGIN_NOT_REQUIRED')) {
			print '<tr><td class="fieldrequired">'.$langs->trans("Password").'</td><td><input type="password" name="pass" class="minwidth300" maxlength="50" value="'.dol_escape_htmltag(GETPOSTISSET("pass") ? GETPOST("pass", 'password', 2) : '').'"></td></tr>';
		}

		// Type
		print '<tr><td class="fieldrequired">'.$langs->trans("Type").'</td><td>';
		if ($user->hasRight('adherent', 'creer')) {
			print $form->selectarray("typeid", $adht->liste_array(), (GETPOSTISSET("typeid") ? GETPOSTINT("typeid") : $object->typeid), 0, 0, 0, '', 0, 0, 0, '', 'minwidth200', 1);
		} else {
			print $adht->getNomUrl(1);
			print '<input type="hidden" name="typeid" value="'.$object->typeid.'">';
		}
		print "</td></tr>";

		// Morphy
		$morphys["phy"] = $langs->trans("Physical");
		$morphys["mor"] = $langs->trans("Moral");
		print '<tr><td><span class="fieldrequired">'.$langs->trans("MemberNature").'</span></td><td>';
		print $form->selectarray("morphy", $morphys, (GETPOSTISSET("morphy") ? GETPOST("morphy", 'alpha') : $object->morphy), 0, 0, 0, '', 0, 0, 0, '', 'minwidth200', 1);
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
		$arraygender = array('man' => $langs->trans("Genderman"), 'woman' => $langs->trans("Genderwoman"), 'other' => $langs->trans("Genderother"));
		print $form->selectarray('gender', $arraygender, GETPOSTISSET('gender') ? GETPOST('gender', 'alphanohtml') : $object->gender, 1, 0, 0, '', 0, 0, 0, '', 'minwidth100', 1);
		print '</td></tr>';

		// Photo
		print '<tr><td>'.$langs->trans("Photo").'</td>';
		print '<td class="hideonsmartphone" valign="middle">';
		print $form->showphoto('memberphoto', $object)."\n";
		if ($caneditfieldmember) {
			if ($object->photo) {
				print "<br>\n";
			}
			print '<table class="nobordernopadding">';
			if ($object->photo) {
				print '<tr><td><input type="checkbox" class="flat photodelete" name="deletephoto" id="photodelete"><label for="photodelete" class="paddingleft">'.$langs->trans("Delete").'</label><br></td></tr>';
			}
			print '<tr><td>';
			$maxfilesizearray = getMaxFileSizeArray();
			$maxmin = $maxfilesizearray['maxmin'];
			if ($maxmin > 0) {
				print '<input type="hidden" name="MAX_FILE_SIZE" value="'.($maxmin * 1024).'">';	// MAX_FILE_SIZE must precede the field type=file
			}
			print '<input type="file" class="flat" name="photo" id="photoinput">';
			print '</td></tr>';
			print '</table>';
		}
		print '</td></tr>';

		// EMail
		print '<tr><td>'.(getDolGlobalString("ADHERENT_MAIL_REQUIRED") ? '<span class="fieldrequired">' : '').$langs->trans("EMail").(getDolGlobalString("ADHERENT_MAIL_REQUIRED") ? '</span>' : '').'</td>';
		print '<td>'.img_picto('', 'object_email', 'class="pictofixedwidth"').'<input type="text" name="member_email" class="minwidth300" maxlength="255" value="'.(GETPOSTISSET("member_email") ? GETPOST("member_email", '', 2) : $object->email).'"></td></tr>';

		// Website
		print '<tr><td>'.$form->editfieldkey('Web', 'member_url', GETPOST('member_url', 'alpha'), $object, 0).'</td>';
		print '<td>'.img_picto('', 'globe', 'class="pictofixedwidth"').'<input type="text" name="member_url" id="member_url" class="maxwidth200onsmartphone maxwidth500 widthcentpercentminusx " value="'.(GETPOSTISSET('member_url') ? GETPOST('member_url', 'alpha') : $object->url).'"></td></tr>';

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
		print img_picto('', 'country', 'class="pictofixedwidth"');
		print $form->select_country(GETPOSTISSET("country_id") ? GETPOST("country_id", "alpha") : $object->country_id, 'country_id');
		if ($user->admin) {
			print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
		}
		print '</td></tr>';

		// State
		if (!getDolGlobalString('MEMBER_DISABLE_STATE')) {
			print '<tr><td>'.$langs->trans('State').'</td><td>';
			print img_picto('', 'state', 'class="pictofixedwidth"');
			print $formcompany->select_state($object->state_id, GETPOSTISSET("country_id") ? GETPOST("country_id", "alpha") : $object->country_id);
			print '</td></tr>';
		}

		// Pro phone
		print '<tr><td>'.$langs->trans("PhonePro").'</td>';
		print '<td>'.img_picto('', 'object_phoning', 'class="pictofixedwidth"').'<input type="text" name="phone" value="'.(GETPOSTISSET("phone") ? GETPOST("phone") : $object->phone).'"></td></tr>';

		// Personal phone
		print '<tr><td>'.$langs->trans("PhonePerso").'</td>';
		print '<td>'.img_picto('', 'object_phoning', 'class="pictofixedwidth"').'<input type="text" name="phone_perso" value="'.(GETPOSTISSET("phone_perso") ? GETPOST("phone_perso") : $object->phone_perso).'"></td></tr>';

		// Mobile phone
		print '<tr><td>'.$langs->trans("PhoneMobile").'</td>';
		print '<td>'.img_picto('', 'object_phoning_mobile', 'class="pictofixedwidth"').'<input type="text" name="phone_mobile" value="'.(GETPOSTISSET("phone_mobile") ? GETPOST("phone_mobile") : $object->phone_mobile).'"></td></tr>';

		if (isModEnabled('socialnetworks')) {
			foreach ($socialnetworks as $key => $value) {
				if (!$value['active']) {
					break;
				}
				print '<tr><td>'.$langs->trans($value['label']).'</td><td><input type="text" name="'.$key.'" class="minwidth100" value="'.(GETPOSTISSET($key) ? GETPOST($key, 'alphanohtml') : (isset($object->socialnetworks[$key]) ? $object->socialnetworks[$key] : null)).'"></td></tr>';
			}
		}

		// Birth Date
		print "<tr><td>".$langs->trans("DateOfBirth")."</td><td>\n";
		print img_picto('', 'object_calendar', 'class="pictofixedwidth"').$form->selectDate(($object->birth ? $object->birth : -1), 'birth', 0, 0, 1, 'formsoc');
		print "</td></tr>\n";

		// Default language
		if (getDolGlobalInt('MAIN_MULTILANGS')) {
			print '<tr><td>'.$form->editfieldkey('DefaultLang', 'default_lang', '', $object, 0).'</td><td colspan="3">'."\n";
			print img_picto('', 'language', 'class="pictofixedwidth"').$formadmin->select_language($object->default_lang, 'default_lang', 0, 0, 1);
			print '</td>';
			print '</tr>';
		}

		// Public profil
		print "<tr><td>";
		$htmltext = $langs->trans("Public", getDolGlobalString('MAIN_INFO_SOCIETE_NOM'), $linkofpubliclist);
		print $form->textwithpicto($langs->trans("MembershipPublic"), $htmltext, 1, 'help', '', 0, 3, 'membershippublic');
		print "</td><td>\n";
		print $form->selectyesno("public", (GETPOSTISSET("public") ? GETPOST("public", 'alphanohtml', 2) : $object->public), 1, false, 0, 1);
		print "</td></tr>\n";

		// Categories
		if (isModEnabled('category') && $user->hasRight('categorie', 'lire')) {
			print '<tr><td>'.$form->editfieldkey("Categories", 'memcats', '', $object, 0).'</td>';
			print '<td>';
			$cate_arbo = $form->select_all_categories(Categorie::TYPE_MEMBER, '', '', 64, 0, 3);
			$c = new Categorie($db);
			$cats = $c->containing($object->id, Categorie::TYPE_MEMBER);
			$arrayselected = array();
			if (is_array($cats)) {
				foreach ($cats as $cat) {
					$arrayselected[] = $cat->id;
				}
			}
			print img_picto('', 'category', 'class="pictofixedwidth"');
			print $form->multiselectarray('memcats', $cate_arbo, $arrayselected, 0, 0, 'widthcentpercentminusx', 0, '100%');
			print "</td></tr>";
		}

		// Third party Dolibarr
		if (isModEnabled('societe')) {
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
		} else {
			print $langs->trans("NoDolibarrAccess");
		}
		print '</td></tr>';

		// Other attributes. Fields from hook formObjectOptions and Extrafields.
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';

		print '</table>';
		print dol_get_fiche_end();

		print $form->buttonsSaveCancel("Save", 'Cancel');

		print '</form>';
	}

	// View
	if ($id > 0 && $action != 'edit') {
		$res = $object->fetch($id);
		if ($res < 0) {
			dol_print_error($db, $object->error);
			exit;
		}
		$res = $object->fetch_optionals();
		if ($res < 0) {
			dol_print_error($db);
			exit;
		}

		$adht = new AdherentType($db);
		$res = $adht->fetch($object->typeid);
		if ($res < 0) {
			dol_print_error($db);
			exit;
		}


		/*
		 * Show tabs
		 */
		$head = member_prepare_head($object);

		print dol_get_fiche_head($head, 'general', $langs->trans("Member"), -1, 'user', 0, '', '', 0, '', 1);

		// Confirm create user
		if ($action == 'create_user') {
			$login = (GETPOSTISSET('login') ? GETPOST('login', 'alphanohtml') : $object->login);
			if (empty($login)) {
				// Full firstname and name separated with a dot : firstname.name
				include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
				$login = dol_buildlogin($object->lastname, $object->firstname);
			}
			if (empty($login)) {
				$login = strtolower(substr($object->firstname, 0, 4)).strtolower(substr($object->lastname, 0, 4));
			}

			// Create a form array
			$formquestion = array(
					array('label' => $langs->trans("LoginToCreate"), 'type' => 'text', 'name' => 'login', 'value' => $login)
			);
			if (isModEnabled('societe') && $object->socid > 0) {
				$object->fetch_thirdparty();
				$formquestion[] = array('label' => $langs->trans("UserWillBe"), 'type' => 'radio', 'name' => 'internalorexternal', 'default' => 'external', 'values' => array('external' => $langs->trans("External").' - '.$langs->trans("LinkedToDolibarrThirdParty").' '.$object->thirdparty->getNomUrl(1, '', 0, 1), 'internal' => $langs->trans("Internal")));
			}
			$text = '';
			if (isModEnabled('societe') && $object->socid <= 0) {
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
				if (!empty($fullname)) {
					$companyalias = $fullname;
				}
			} else {
				$companyname = $fullname;
				if (!empty($object->company)) {
					$companyalias = $object->company;
				}
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
			$outputlangs->loadLangs(array("main", "members", "companies", "install", "other"));
			// Get email content from template
			$arraydefaultmessage = null;
			$labeltouse = getDolGlobalString("ADHERENT_EMAIL_TEMPLATE_MEMBER_VALIDATION");

			if (!empty($labeltouse)) {
				$arraydefaultmessage = $formmail->getEMailTemplate($db, 'member', $user, $outputlangs, 0, 1, $labeltouse);
			}

			if (!empty($labeltouse) && is_object($arraydefaultmessage) && $arraydefaultmessage->id > 0) {
				$subject = $arraydefaultmessage->topic;
				$msg = $arraydefaultmessage->content;
			}

			$substitutionarray = getCommonSubstitutionArray($outputlangs, 0, null, $object);
			complete_substitutions_array($substitutionarray, $outputlangs, $object);
			$subjecttosend = make_substitutions($subject, $substitutionarray, $outputlangs);
			$texttosend = make_substitutions(dol_concatdesc($msg, $adht->getMailOnValid()), $substitutionarray, $outputlangs);

			$tmp = $langs->trans("SendingAnEMailToMember");
			$tmp .= '<br>'.$langs->trans("MailFrom").': <b>'.getDolGlobalString('ADHERENT_MAIL_FROM').'</b>, ';
			$tmp .= '<br>'.$langs->trans("MailRecipient").': <b>'.$object->email.'</b>';
			$helpcontent = '';
			$helpcontent .= '<b>'.$langs->trans("MailFrom").'</b>: '.getDolGlobalString('ADHERENT_MAIL_FROM').'<br>'."\n";
			$helpcontent .= '<b>'.$langs->trans("MailRecipient").'</b>: '.$object->email.'<br>'."\n";
			$helpcontent .= '<b>'.$langs->trans("Subject").'</b>:<br>'."\n";
			$helpcontent .= $subjecttosend."\n";
			$helpcontent .= "<br>";
			$helpcontent .= '<b>'.$langs->trans("Content").'</b>:<br>';
			$helpcontent .= dol_htmlentitiesbr($texttosend)."\n";
			// @phan-suppress-next-line PhanPluginSuspiciousParamOrder
			$label = $form->textwithpicto($tmp, $helpcontent, 1, 'help');

			// Create form popup
			$formquestion = array();
			if ($object->email) {
				$formquestion[] = array('type' => 'checkbox', 'name' => 'send_mail', 'label' => $label, 'value' => (getDolGlobalString('ADHERENT_DEFAULT_SENDINFOBYMAIL') ? true : false));
			}
			if (isModEnabled('mailman') && getDolGlobalString('ADHERENT_USE_MAILMAN')) {
				$formquestion[] = array('type' => 'other', 'label' => $langs->transnoentitiesnoconv("SynchroMailManEnabled"), 'value' => '');
			}
			if (isModEnabled('mailman') && getDolGlobalString('ADHERENT_USE_SPIP')) {
				$formquestion[] = array('type' => 'other', 'label' => $langs->transnoentitiesnoconv("SynchroSpipEnabled"), 'value' => '');
			}
			print $form->formconfirm("card.php?rowid=".$id, $langs->trans("ValidateMember"), $langs->trans("ConfirmValidateMember"), "confirm_valid", $formquestion, 'yes', 1, 220);
		}

		// Confirm resiliate
		if ($action == 'resiliate') {
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
			$labeltouse = getDolGlobalString('ADHERENT_EMAIL_TEMPLATE_CANCELATION');

			if (!empty($labeltouse)) {
				$arraydefaultmessage = $formmail->getEMailTemplate($db, 'member', $user, $outputlangs, 0, 1, $labeltouse);
			}

			if (!empty($labeltouse) && is_object($arraydefaultmessage) && $arraydefaultmessage->id > 0) {
				$subject = $arraydefaultmessage->topic;
				$msg     = $arraydefaultmessage->content;
			}

			$substitutionarray = getCommonSubstitutionArray($outputlangs, 0, null, $object);
			complete_substitutions_array($substitutionarray, $outputlangs, $object);
			$subjecttosend = make_substitutions($subject, $substitutionarray, $outputlangs);
			$texttosend = make_substitutions(dol_concatdesc($msg, $adht->getMailOnResiliate()), $substitutionarray, $outputlangs);

			$tmp = $langs->trans("SendingAnEMailToMember");
			$tmp .= '<br>('.$langs->trans("MailFrom").': <b>'.getDolGlobalString('ADHERENT_MAIL_FROM').'</b>, ';
			$tmp .= $langs->trans("MailRecipient").': <b>'.$object->email.'</b>)';
			$helpcontent = '';
			$helpcontent .= '<b>'.$langs->trans("MailFrom").'</b>: '.getDolGlobalString('ADHERENT_MAIL_FROM').'<br>'."\n";
			$helpcontent .= '<b>'.$langs->trans("MailRecipient").'</b>: '.$object->email.'<br>'."\n";
			$helpcontent .= '<b>'.$langs->trans("Subject").'</b>:<br>'."\n";
			$helpcontent .= $subjecttosend."\n";
			$helpcontent .= "<br>";
			$helpcontent .= '<b>'.$langs->trans("Content").'</b>:<br>';
			$helpcontent .= dol_htmlentitiesbr($texttosend)."\n";
			// @phan-suppress-next-line PhanPluginSuspiciousParamOrder
			$label = $form->textwithpicto($tmp, $helpcontent, 1, 'help');

			// Create an array
			$formquestion = array();
			if ($object->email) {
				$formquestion[] = array('type' => 'checkbox', 'name' => 'send_mail', 'label' => $label, 'value' => (getDolGlobalString('ADHERENT_DEFAULT_SENDINFOBYMAIL') ? 'true' : 'false'));
			}
			if ($backtopage) {
				$formquestion[] = array('type' => 'hidden', 'name' => 'backtopage', 'value' => ($backtopage != '1' ? $backtopage : $_SERVER["HTTP_REFERER"]));
			}
			print $form->formconfirm("card.php?rowid=".$id, $langs->trans("ResiliateMember"), $langs->trans("ConfirmResiliateMember"), "confirm_resiliate", $formquestion, 'no', 1, 240);
		}

		// Confirm exclude
		if ($action == 'exclude') {
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
			$labeltouse = getDolGlobalString('ADHERENT_EMAIL_TEMPLATE_EXCLUSION');

			if (!empty($labeltouse)) {
				$arraydefaultmessage = $formmail->getEMailTemplate($db, 'member', $user, $outputlangs, 0, 1, $labeltouse);
			}

			if (!empty($labeltouse) && is_object($arraydefaultmessage) && $arraydefaultmessage->id > 0) {
				$subject = $arraydefaultmessage->topic;
				$msg     = $arraydefaultmessage->content;
			}

			$substitutionarray = getCommonSubstitutionArray($outputlangs, 0, null, $object);
			complete_substitutions_array($substitutionarray, $outputlangs, $object);
			$subjecttosend = make_substitutions($subject, $substitutionarray, $outputlangs);
			$texttosend = make_substitutions(dol_concatdesc($msg, $adht->getMailOnExclude()), $substitutionarray, $outputlangs);

			$tmp = $langs->trans("SendingAnEMailToMember");
			$tmp .= '<br>('.$langs->trans("MailFrom").': <b>'.getDolGlobalString('ADHERENT_MAIL_FROM').'</b>, ';
			$tmp .= $langs->trans("MailRecipient").': <b>'.$object->email.'</b>)';
			$helpcontent = '';
			$helpcontent .= '<b>'.$langs->trans("MailFrom").'</b>: '.getDolGlobalString('ADHERENT_MAIL_FROM').'<br>'."\n";
			$helpcontent .= '<b>'.$langs->trans("MailRecipient").'</b>: '.$object->email.'<br>'."\n";
			$helpcontent .= '<b>'.$langs->trans("Subject").'</b>:<br>'."\n";
			$helpcontent .= $subjecttosend."\n";
			$helpcontent .= "<br>";
			$helpcontent .= '<b>'.$langs->trans("Content").'</b>:<br>';
			$helpcontent .= dol_htmlentitiesbr($texttosend)."\n";
			// @phan-suppress-next-line PhanPluginSuspiciousParamOrder
			$label = $form->textwithpicto($tmp, $helpcontent, 1, 'help');

			// Create an array
			$formquestion = array();
			if ($object->email) {
				$formquestion[] = array('type' => 'checkbox', 'name' => 'send_mail', 'label' => $label, 'value' => (getDolGlobalString('ADHERENT_DEFAULT_SENDINFOBYMAIL') ? 'true' : 'false'));
			}
			if ($backtopage) {
				$formquestion[] = array('type' => 'hidden', 'name' => 'backtopage', 'value' => ($backtopage != '1' ? $backtopage : $_SERVER["HTTP_REFERER"]));
			}
			print $form->formconfirm("card.php?rowid=".$id, $langs->trans("ExcludeMember"), $langs->trans("ConfirmExcludeMember"), "confirm_exclude", $formquestion, 'no', 1, 240);
		}

		// Confirm remove member
		if ($action == 'delete') {
			$formquestion = array();
			if ($backtopage) {
				$formquestion[] = array('type' => 'hidden', 'name' => 'backtopage', 'value' => ($backtopage != '1' ? $backtopage : $_SERVER["HTTP_REFERER"]));
			}
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
		if (!getDolGlobalString('ADHERENT_LOGIN_NOT_REQUIRED')) {
			$rowspan++;
		}
		if (isModEnabled('societe')) {
			$rowspan++;
		}

		$linkback = '<a href="'.DOL_URL_ROOT.'/adherents/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

		$morehtmlref = '<a href="'.DOL_URL_ROOT.'/adherents/vcard.php?id='.$object->id.'" class="refid">';
		$morehtmlref .= img_picto($langs->trans("Download").' '.$langs->trans("VCard"), 'vcard.png', 'class="valignmiddle marginleftonly paddingrightonly"');
		$morehtmlref .= '</a>';


		dol_banner_tab($object, 'rowid', $linkback, 1, 'rowid', 'ref', $morehtmlref);

		print '<div class="fichecenter">';
		print '<div class="fichehalfleft">';

		print '<div class="underbanner clearboth"></div>';
		print '<table class="border tableforfield centpercent">';

		// Login
		if (!getDolGlobalString('ADHERENT_LOGIN_NOT_REQUIRED')) {
			print '<tr><td class="titlefield">'.$langs->trans("Login").' / '.$langs->trans("Id").'</td><td class="valeur">'.dol_escape_htmltag($object->login).'</td></tr>';
		}

		// Type
		print '<tr><td class="titlefield">'.$langs->trans("Type").'</td>';
		print '<td class="valeur">'.$adht->getNomUrl(1)."</td></tr>\n";

		// Morphy
		print '<tr><td>'.$langs->trans("MemberNature").'</td>';
		print '<td class="valeur" >'.$object->getmorphylib('', 1).'</td>';
		print '</tr>';

		// Company
		print '<tr><td>'.$langs->trans("Company").'</td><td class="valeur">'.dol_escape_htmltag($object->company).'</td></tr>';

		// Civility
		print '<tr><td>'.$langs->trans("UserTitle").'</td><td class="valeur">'.$object->getCivilityLabel().'</td>';
		print '</tr>';

		// Password
		if (!getDolGlobalString('ADHERENT_LOGIN_NOT_REQUIRED')) {
			print '<tr><td>'.$langs->trans("Password").'</td><td>';
			if ($object->pass) {
				print preg_replace('/./i', '*', $object->pass);
			} else {
				if ($user->admin) {
					print '<!-- '.$langs->trans("Crypted").': '.$object->pass_indatabase_crypted.' -->';
				}
				print '<span class="opacitymedium">'.$langs->trans("Hidden").'</span>';
			}
			if (!empty($object->pass_indatabase) && empty($object->user_id)) {	// Show warning only for old password still in clear (does not happen anymore)
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
				if (Adherent::STATUS_VALIDATED == $object->status) {
					print " ".img_warning($langs->trans("Late")); // displays delay Pictogram only if not a draft, not excluded and not resiliated
				}
			} else {
				print $langs->trans("SubscriptionNotReceived");
				if (Adherent::STATUS_VALIDATED == $object->status) {
					print " ".img_warning($langs->trans("Late")); // displays delay Pictogram only if not a draft, not excluded and not resiliated
				}
			}
		}
		print '</td></tr>';

		print '</table>';

		print '</div>';

		print '<div class="fichehalfright">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border tableforfield centpercent">';

		// Tags / Categories
		if (isModEnabled('category') && $user->hasRight('categorie', 'lire')) {
			print '<tr><td>'.$langs->trans("Categories").'</td>';
			print '<td colspan="2">';
			print $form->showCategories($object->id, Categorie::TYPE_MEMBER, 1);
			print '</td></tr>';
		}

		// Birth Date
		print '<tr><td class="titlefield">'.$langs->trans("DateOfBirth").'</td><td class="valeur">'.dol_print_date($object->birth, 'day').'</td></tr>';

		// Default language
		if (getDolGlobalInt('MAIN_MULTILANGS')) {
			require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
			print '<tr><td>'.$langs->trans("DefaultLang").'</td><td>';
			//$s=picto_from_langcode($object->default_lang);
			//print ($s?$s.' ':'');
			$langs->load("languages");
			$labellang = ($object->default_lang ? $langs->trans('Language_'.$object->default_lang) : '');
			print picto_from_langcode($object->default_lang, 'class="paddingrightonly saturatemedium opacitylow"');
			print $labellang;
			print '</td></tr>';
		}

		// Public
		print '<tr><td>';
		$htmltext = $langs->trans("Public", getDolGlobalString('MAIN_INFO_SOCIETE_NOM'), $linkofpubliclist);
		print $form->textwithpicto($langs->trans("MembershipPublic"), $htmltext, 1, 'help', '', 0, 3, 'membershippublic');
		print '</td><td class="valeur">'.yn($object->public).'</td></tr>';

		// Other attributes
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

		// Third party Dolibarr
		if (isModEnabled('societe')) {
			print '<tr><td>';
			$editenable = $user->hasRight('adherent', 'creer');
			print $form->editfieldkey('LinkedToDolibarrThirdParty', 'thirdparty', '', $object, $editenable);
			print '</td><td colspan="2" class="valeur">';
			if ($action == 'editthirdparty') {
				$htmlname = 'socid';
				print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'" name="form'.$htmlname.'">';
				print '<input type="hidden" name="rowid" value="'.$object->id.'">';
				print '<input type="hidden" name="action" value="set'.$htmlname.'">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<table class="nobordernopadding">';
				print '<tr><td>';
				print $form->select_company($object->socid, 'socid', '', 1);
				print '</td>';
				print '<td class="left"><input type="submit" class="button button-edit smallpaddingimp" value="'.$langs->trans("Modify").'"></td>';
				print '</tr></table></form>';
			} else {
				if ($object->socid) {
					$company = new Societe($db);
					$result = $company->fetch($object->socid);
					print $company->getNomUrl(1);

					// Show link to invoices
					$tmparray = $company->getOutstandingBills('customer');
					if (!empty($tmparray['refs'])) {
						print ' - '.img_picto($langs->trans("Invoices"), 'bill', 'class="paddingright"').'<a href="'.DOL_URL_ROOT.'/compta/facture/list.php?socid='.$object->socid.'">'.$langs->trans("Invoices").' ('.count($tmparray['refs']).')';
						// TODO Add alert if warning on at least one invoice late
						print '</a>';
					}
				} else {
					print '<span class="opacitymedium">'.$langs->trans("NoThirdPartyAssociatedToMember").'</span>';
				}
			}
			print '</td></tr>';
		}

		// Login Dolibarr - Link to user
		print '<tr><td>';
		$editenable = $user->hasRight('adherent', 'creer') && $user->hasRight('user', 'user', 'creer');
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
				print '<span class="opacitymedium">'.$langs->trans("NoDolibarrAccess").'</span>';
			}
		}
		print '</td></tr>';

		print "</table>\n";

		print "</div></div>\n";
		print '<div class="clearboth"></div>';

		print dol_get_fiche_end();


		/*
		 * Action bar
		 */

		print '<div class="tabsAction">';
		$isinspip = 0;
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been
		if (empty($reshook)) {
			if ($action != 'editlogin' && $action != 'editthirdparty') {
				// Send
				if (empty($user->socid)) {
					if (Adherent::STATUS_VALIDATED == $object->status) {
						print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.((int) $object->id).'&action=presend&mode=init#formmailbeforetitle">'.$langs->trans('SendMail').'</a>'."\n";
					}
				}

				// Send card by email
				// TODO Remove this to replace with a template
				/*
				if ($user->hasRight('adherent', 'creer')) {
					if (Adherent::STATUS_VALIDATED == $object->status) {
						if ($object->email) print '<a class="butAction" href="card.php?rowid='.$object->id.'&action=sendinfo">'.$langs->trans("SendCardByMail")."</a>\n";
						else print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NoEMail")).'">'.$langs->trans("SendCardByMail")."</a>\n";
					} else {
						print '<span class="butActionRefused classfortooltip" title="'.dol_escape_htmltag($langs->trans("ValidateBefore")).'">'.$langs->trans("SendCardByMail")."</span>";
					}
				} else {
					print '<span class="butActionRefused classfortooltip" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("SendCardByMail")."</span>";
				}*/

				// Modify
				if ($user->hasRight('adherent', 'creer')) {
					print '<a class="butAction" href="card.php?rowid='.((int) $object->id).'&action=edit&token='.newToken().'">'.$langs->trans("Modify").'</a>'."\n";
				} else {
					print '<span class="butActionRefused classfortooltip" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("Modify").'</span>'."\n";
				}

				// Validate
				if (Adherent::STATUS_DRAFT == $object->status) {
					if ($user->hasRight('adherent', 'creer')) {
						print '<a class="butAction" href="card.php?rowid='.((int) $object->id).'&action=valid&token='.newToken().'">'.$langs->trans("Validate").'</a>'."\n";
					} else {
						print '<span class="butActionRefused classfortooltip" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("Validate").'</span>'."\n";
					}
				}

				// Reactivate
				if (Adherent::STATUS_RESILIATED == $object->status || Adherent::STATUS_EXCLUDED == $object->status) {
					if ($user->hasRight('adherent', 'creer')) {
						print '<a class="butAction" href="card.php?rowid='.((int) $object->id).'&action=valid&token='.newToken().'">'.$langs->trans("Reenable")."</a>\n";
					} else {
						print '<span class="butActionRefused classfortooltip" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("Reenable").'</span>'."\n";
					}
				}

				// Resiliate
				if (Adherent::STATUS_VALIDATED == $object->status) {
					if ($user->hasRight('adherent', 'supprimer')) {
						print '<a class="butAction" href="card.php?rowid='.((int) $object->id).'&action=resiliate&token='.newToken().'">'.$langs->trans("Resiliate")."</a></span>\n";
					} else {
						print '<span class="butActionRefused classfortooltip" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("Resiliate").'</span>'."\n";
					}
				}

				// Exclude
				if (Adherent::STATUS_VALIDATED == $object->status) {
					if ($user->hasRight('adherent', 'supprimer')) {
						print '<a class="butAction" href="card.php?rowid='.((int) $object->id).'&action=exclude&token='.newToken().'">'.$langs->trans("Exclude")."</a></span>\n";
					} else {
						print '<span class="butActionRefused classfortooltip" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("Exclude").'</span>'."\n";
					}
				}

				// Create third party
				if (isModEnabled('societe') && !$object->socid) {
					if ($user->hasRight('societe', 'creer')) {
						if (Adherent::STATUS_DRAFT != $object->status) {
							print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?rowid='.((int) $object->id).'&action=create_thirdparty&token='.newToken().'" title="'.dol_escape_htmltag($langs->trans("CreateDolibarrThirdPartyDesc")).'">'.$langs->trans("CreateDolibarrThirdParty").'</a>'."\n";
						} else {
							print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("ValidateBefore")).'">'.$langs->trans("CreateDolibarrThirdParty").'</a>'."\n";
						}
					} else {
						print '<span class="butActionRefused classfortooltip" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("CreateDolibarrThirdParty").'</span>'."\n";
					}
				}

				// Create user
				if (!$user->socid && !$object->user_id) {
					if ($user->hasRight('user', 'user', 'creer')) {
						if (Adherent::STATUS_DRAFT != $object->status) {
							print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?rowid='.((int) $object->id).'&action=create_user&token='.newToken().'" title="'.dol_escape_htmltag($langs->trans("CreateDolibarrLoginDesc")).'">'.$langs->trans("CreateDolibarrLogin").'</a>'."\n";
						} else {
							print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("ValidateBefore")).'">'.$langs->trans("CreateDolibarrLogin").'</a>'."\n";
						}
					} else {
						print '<span class="butActionRefused classfortooltip" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("CreateDolibarrLogin").'</span>'."\n";
					}
				}

				// Action SPIP
				if (isModEnabled('mailmanspip') && getDolGlobalString('ADHERENT_USE_SPIP')) {
					$isinspip = $mailmanspip->is_in_spip($object);

					if ($isinspip == 1) {
						print '<a class="butAction" href="card.php?rowid='.((int) $object->id).'&action=del_spip&token='.newToken().'">'.$langs->trans("DeleteIntoSpip").'</a>'."\n";
					}
					if ($isinspip == 0) {
						print '<a class="butAction" href="card.php?rowid='.((int) $object->id).'&action=add_spip&token='.newToken().'">'.$langs->trans("AddIntoSpip").'</a>'."\n";
					}
				}

				// Delete
				if ($user->hasRight('adherent', 'supprimer')) {
					print '<a class="butActionDelete" href="card.php?rowid='.((int) $object->id).'&action=delete&token='.newToken().'">'.$langs->trans("Delete").'</a>'."\n";
				} else {
					print '<span class="butActionRefused classfortooltip" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("Delete").'</span>'."\n";
				}
			}
		}
		print '</div>';

		if ($isinspip == -1) {
			print '<br><br><span class="error">'.$langs->trans('SPIPConnectionFailed').': '.$mailmanspip->error.'</span>';
		}


		// Select mail models is same action as presend
		if (GETPOST('modelselected')) {
			$action = 'presend';
		}

		if ($action != 'presend') {
			print '<div class="fichecenter"><div class="fichehalfleft">';
			print '<a name="builddoc"></a>'; // ancre

			// Generated documents
			$filename = dol_sanitizeFileName($object->ref);
			$filedir = $conf->adherent->dir_output.'/'.get_exdir(0, 0, 0, 1, $object, 'member');
			$urlsource = $_SERVER['PHP_SELF'].'?id='.$object->id;
			$genallowed = $user->hasRight('adherent', 'lire');
			$delallowed = $user->hasRight('adherent', 'creer');

			print $formfile->showdocuments('member', $filename, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', (empty($object->default_lang) ? '' : $object->default_lang), '', $object);
			$somethingshown = $formfile->numoffiles;

			// Show links to link elements
			//$linktoelem = $form->showLinkToObjectBlock($object, null, array('subscription'));
			//$somethingshown = $form->showLinkedObjectBlock($object, '');

			// Show links to link elements
			/*$linktoelem = $form->showLinkToObjectBlock($object,array('order'));
			 if ($linktoelem) {
				print ($somethingshown?'':'<br>').$linktoelem;
			}
			 */

			// Show online payment link
			// The list can be complete by the hook 'doValidatePayment' executed inside getValidOnlinePaymentMethods()
			include_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
			$validpaymentmethod = getValidOnlinePaymentMethods('');
			$useonlinepayment = count($validpaymentmethod);

			if ($useonlinepayment) {
				print '<br>';
				if (empty($amount)) {   // Take the maximum amount among what the member is supposed to pay / has paid in the past
					$amount = max($adht->amount, $object->first_subscription_amount, $object->last_subscription_amount);
				}
				if (empty($amount)) {
					$amount = 0;
				}
				require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
				print showOnlinePaymentUrl('membersubscription', $object->ref, $amount);
			}

			print '</div><div class="fichehalfright">';

			$MAXEVENT = 10;

			$morehtmlcenter = '';
			$messagingUrl = DOL_URL_ROOT.'/adherents/messaging.php?rowid='.$object->id;
			$morehtmlcenter .= dolGetButtonTitle($langs->trans('ShowAsConversation'), '', 'fa fa-comments imgforviewmode', $messagingUrl, '', 1);
			$morehtmlcenter .= dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', DOL_URL_ROOT.'/adherents/agenda.php?id='.$object->id);

			// List of actions on element
			include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
			$formactions = new FormActions($db);
			$somethingshown = $formactions->showactions($object, $object->element, $socid, 1, 'listactions', $MAXEVENT, '', $morehtmlcenter);

			print '</div></div>';
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
