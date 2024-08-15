<?php
/* Copyright (C) 2002-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2022 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2021 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2005      Lionel Cousteix      <etm_ltd@tiscali.co.uk>
 * Copyright (C) 2011      Herve Prot           <herve.prot@symeos.com>
 * Copyright (C) 2012-2018 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2013      Florian Henry        <florian.henry@open-concept.pro>
 * Copyright (C) 2013-2016 Alexandre Spangaro   <aspangaro@open-dsi.fr>
 * Copyright (C) 2015-2017 Jean-François Ferry  <jfefe@aternatik.fr>
 * Copyright (C) 2015      Ari Elbaz (elarifr)  <github@accedinfo.com>
 * Copyright (C) 2015-2018 Charlene Benke       <charlie@patas-monkey.com>
 * Copyright (C) 2016      Raphaël Doursenaud   <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2018-2023 Frédéric France      <frederic.france@netlogic.fr>
 * Copyright (C) 2018      David Beniamine      <David.Beniamine@Tetras-Libre.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *       \file       htdocs/user/card.php
 *       \brief      Tab of user card
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
if (isModEnabled('ldap')) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/ldap.class.php';
}
if (isModEnabled('member')) {
	require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
}
if (isModEnabled('category')) {
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
}
if (isModEnabled('stock')) {
	require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
}

// Load translation files required by page
$langs->loadLangs(array('users', 'companies', 'ldap', 'admin', 'hrm', 'stocks', 'other'));

$id = GETPOSTINT('id');
$action		= GETPOST('action', 'aZ09');
$mode = GETPOST('mode', 'alpha');
$confirm	= GETPOST('confirm', 'alpha');
$group = GETPOSTINT("group", 3);
$cancel		= GETPOST('cancel', 'alpha');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'useracard'; // To manage different context of search

if (empty($id) && $action != 'create') {
	$id = $user->id;
}

$dateemployment = dol_mktime(0, 0, 0, GETPOSTINT('dateemploymentmonth'), GETPOSTINT('dateemploymentday'), GETPOSTINT('dateemploymentyear'));
$dateemploymentend = dol_mktime(0, 0, 0, GETPOSTINT('dateemploymentendmonth'), GETPOSTINT('dateemploymentendday'), GETPOSTINT('dateemploymentendyear'));
$datestartvalidity = dol_mktime(0, 0, 0, GETPOSTINT('datestartvaliditymonth'), GETPOSTINT('datestartvalidityday'), GETPOSTINT('datestartvalidityyear'));
$dateendvalidity = dol_mktime(0, 0, 0, GETPOSTINT('dateendvaliditymonth'), GETPOSTINT('dateendvalidityday'), GETPOSTINT('dateendvalidityyear'));
$dateofbirth = dol_mktime(0, 0, 0, GETPOSTINT('dateofbirthmonth'), GETPOSTINT('dateofbirthday'), GETPOSTINT('dateofbirthyear'));

$childids = $user->getAllChildIds(1);	// For later, test on salary visibility

$object = new User($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$socialnetworks = getArrayOfSocialNetworks();

// Initialize a technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('usercard', 'globalcard'));

$error = 0;

$acceptlocallinktomedia = (acceptLocalLinktoMedia() > 0 ? 1 : 0);

if ($id > 0) {
	$res = $object->fetch($id, '', '', 1);
}

// Security check
$socid = 0;
if ($user->socid > 0) {
	$socid = $user->socid;
}
$feature2 = 'user';
$result = restrictedArea($user, 'user', $id, 'user', $feature2);

// Define value to know what current user can do on users
$canadduser = (!empty($user->admin) || $user->hasRight("user", "user", "write"));
$canreaduser = (!empty($user->admin) || $user->hasRight("user", "user", "read"));
$canedituser = (!empty($user->admin) || $user->hasRight("user", "user", "write"));	// edit other user
$candisableuser = (!empty($user->admin) || $user->hasRight("user", "user", "delete"));
$canreadgroup = $canreaduser;
$caneditgroup = $canedituser;
if (getDolGlobalString('MAIN_USE_ADVANCED_PERMS')) {
	$canreadgroup = (!empty($user->admin) || $user->hasRight("user", "group_advance", "read"));
	$caneditgroup = (!empty($user->admin) || $user->hasRight("user", "group_advance", "write"));
}

if ($user->id != $id && !$canreaduser) {
	accessforbidden();
}

// Define value to know what current user can do on properties of edited user
if ($id > 0) {
	// $user is the current logged user, $id is the user we want to edit
	$canedituser = (($user->id == $id) && $user->hasRight("user", "self", "write")) || (($user->id != $id) && $user->hasRight("user", "user", "write"));
	$caneditfield = ((($user->id == $id) && $user->hasRight("user", "self", "write")) || (($user->id != $id) && $user->hasRight("user", "user", "write")));
	$caneditpasswordandsee = ((($user->id == $id) && $user->hasRight("user", "self", "password")) || (($user->id != $id) && $user->hasRight("user", "user", "password") && $user->admin));
	$caneditpasswordandsend = ((($user->id == $id) && $user->hasRight("user", "self", "password")) || (($user->id != $id) && $user->hasRight("user", "user", "password")));
}


/**
 * Actions
 */

$parameters = array('id' => $id, 'socid' => $socid, 'group' => $group, 'caneditgroup' => $caneditgroup);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	$backurlforlist = DOL_URL_ROOT.'/user/list.php';

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = DOL_URL_ROOT.'/user/card.php?id='.((!empty($id) && $id > 0) ? $id : '__ID__');
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

	if ($action == 'confirm_disable' && $confirm == "yes" && $candisableuser) {
		if ($id != $user->id) {		// A user can't disable itself
			$object->fetch($id);
			if ($object->admin && empty($user->admin)) {
				// If user to delete is an admin user and if logged user is not admin, we deny the operation.
				$error++;
				setEventMessages($langs->trans("OnlyAdminUsersCanDisableAdminUsers"), null, 'errors');
			} else {
				$object->setstatus(0);
				header("Location: ".$_SERVER['PHP_SELF'].'?id='.$id);
				exit;
			}
		}
	}

	if ($action == 'confirm_enable' && $confirm == "yes" && $candisableuser) {
		$error = 0;

		if ($id != $user->id) {
			$object->fetch($id);

			if (!empty($conf->file->main_limit_users)) {
				$nb = $object->getNbOfUsers("active");
				if ($nb >= $conf->file->main_limit_users) {
					$error++;
					setEventMessages($langs->trans("YourQuotaOfUsersIsReached"), null, 'errors');
				}
			}

			if (!$error) {
				$object->setstatus(1);
				header("Location: ".$_SERVER['PHP_SELF'].'?id='.$id);
				exit;
			}
		}
	}

	if ($action == 'confirm_delete' && $confirm == "yes" && $candisableuser) {
		if ($id != $user->id) {
			if (!GETPOSTISSET('token')) {
				print 'Error, token required for this critical operation';
				exit;
			}

			$object = new User($db);
			$object->fetch($id);
			$object->oldcopy = clone $object;

			$result = $object->delete($user);
			if ($result < 0) {
				$langs->load("errors");
				setEventMessages($langs->trans("ErrorUserCannotBeDelete"), null, 'errors');
			} else {
				setEventMessages($langs->trans("RecordDeleted"), null);
				header("Location: ".DOL_URL_ROOT."/user/list.php?restore_lastsearch_values=1");
				exit;
			}
		}
	}

	// Action Add user
	if ($action == 'add' && $canadduser) {
		$error = 0;

		if (!GETPOST("lastname")) {
			$error++;
			setEventMessages($langs->trans("NameNotDefined"), null, 'errors');
			$action = "create"; // Go back to create page
		}
		if (!GETPOST("login")) {
			$error++;
			setEventMessages($langs->trans("LoginNotDefined"), null, 'errors');
			$action = "create"; // Go back to create page
		}

		if (!empty($conf->file->main_limit_users)) { // If option to limit users is set
			$nb = $object->getNbOfUsers("active");
			if ($nb >= $conf->file->main_limit_users) {
				$error++;
				setEventMessages($langs->trans("YourQuotaOfUsersIsReached"), null, 'errors');
				$action = "create"; // Go back to create page
			}
		}

		if (!$error) {
			$object->civility_code = GETPOST("civility_code", 'aZ09');
			$object->lastname = GETPOST("lastname", 'alphanohtml');
			$object->firstname = GETPOST("firstname", 'alphanohtml');
			$object->ref_employee = GETPOST("ref_employee", 'alphanohtml');
			$object->national_registration_number = GETPOST("national_registration_number", 'alphanohtml');
			$object->login = GETPOST("login", 'alphanohtml');
			$object->api_key = GETPOST("api_key", 'alphanohtml');
			$object->gender = GETPOST("gender", 'aZ09');
			$object->admin = GETPOSTINT("admin");
			$object->address = GETPOST('address', 'alphanohtml');
			$object->zip = GETPOST('zipcode', 'alphanohtml');
			$object->town = GETPOST('town', 'alphanohtml');
			$object->country_id = GETPOSTINT('country_id');
			$object->state_id = GETPOSTINT('state_id');
			$object->office_phone = GETPOST("office_phone", 'alphanohtml');
			$object->office_fax = GETPOST("office_fax", 'alphanohtml');
			$object->user_mobile = GETPOST("user_mobile", 'alphanohtml');

			if (isModEnabled('socialnetworks')) {
				$object->socialnetworks = array();
				foreach ($socialnetworks as $key => $value) {
					if (GETPOST($key, 'alphanohtml')) {
						$object->socialnetworks[$key] = GETPOST($key, 'alphanohtml');
					}
				}
			}

			$object->email = preg_replace('/\s+/', '', GETPOST("email", 'alphanohtml'));
			$object->job = GETPOST("job", 'alphanohtml');
			$object->signature = GETPOST("signature", 'restricthtml');
			$object->accountancy_code = GETPOST("accountancy_code", 'alphanohtml');
			$object->note_public = GETPOST("note_public", 'restricthtml');
			$object->note_private = GETPOST("note_private", 'restricthtml');
			$object->ldap_sid = GETPOST("ldap_sid", 'alphanohtml');
			$object->fk_user = GETPOSTINT("fk_user") > 0 ? GETPOSTINT("fk_user") : 0;
			$object->fk_user_expense_validator = GETPOSTINT("fk_user_expense_validator") > 0 ? GETPOSTINT("fk_user_expense_validator") : 0;
			$object->fk_user_holiday_validator = GETPOSTINT("fk_user_holiday_validator") > 0 ? GETPOSTINT("fk_user_holiday_validator") : 0;
			$object->employee = GETPOST('employee', 'alphanohtml');

			$object->thm = GETPOST("thm", 'alphanohtml') != '' ? GETPOST("thm", 'alphanohtml') : '';
			$object->thm = price2num($object->thm);
			$object->tjm = GETPOST("tjm", 'alphanohtml') != '' ? GETPOST("tjm", 'alphanohtml') : '';
			$object->tjm = price2num($object->tjm);
			$object->salary = GETPOST("salary", 'alphanohtml') != '' ? GETPOST("salary", 'alphanohtml') : '';
			$object->salary = price2num($object->salary);
			$object->salaryextra = GETPOST("salaryextra", 'alphanohtml') != '' ? GETPOST("salaryextra", 'alphanohtml') : '';
			$object->weeklyhours = GETPOST("weeklyhours", 'alphanohtml') != '' ? GETPOST("weeklyhours", 'alphanohtml') : '';

			$object->color = GETPOST("color", 'alphanohtml') != '' ? GETPOST("color", 'alphanohtml') : '';

			$object->dateemployment = $dateemployment;
			$object->dateemploymentend = $dateemploymentend;
			$object->datestartvalidity = $datestartvalidity;
			$object->dateendvalidity = $dateendvalidity;
			$object->birth = $dateofbirth;

			$object->fk_warehouse = GETPOSTINT('fk_warehouse');

			$object->lang = GETPOST('default_lang', 'aZ09');

			// Fill array 'array_options' with data from add form
			$ret = $extrafields->setOptionalsFromPost(null, $object);
			if ($ret < 0) {
				$error++;
			}

			// Set entity property
			$entity = GETPOSTINT('entity');
			if (isModEnabled('multicompany')) {
				if (GETPOSTINT('superadmin')) {
					$object->entity = 0;
				} else {
					if (getDolGlobalString('MULTICOMPANY_TRANSVERSE_MODE')) {
						$object->entity = 1; // all users are forced into master entity
					} else {
						$object->entity = ($entity == '' ? 1 : $entity);
					}
				}
			} else {
				$object->entity = ($entity == '' ? 1 : $entity);
				/*if ($user->admin && $user->entity == 0 && GETPOST("admin",'alpha'))
				{
				}*/
			}

			$db->begin();

			$id = $object->create($user);
			if ($id > 0) {
				$resPass = 0;
				if (GETPOST('password', 'password')) {
					$resPass = $object->setPassword($user, GETPOST('password', 'password'));
				}
				if (is_int($resPass) && $resPass < 0) {
					$langs->load("errors");
					$db->rollback();
					setEventMessages($object->error, $object->errors, 'errors');
					$action = "create"; // Go back to create page
				} else {
					if (isModEnabled("category")) {
						// Categories association
						$usercats = GETPOST('usercats', 'array');
						$object->setCategories($usercats);
					}
					$db->commit();

					header("Location: ".$_SERVER['PHP_SELF'].'?id='.$id);
					exit;
				}
			} else {
				$langs->load("errors");
				$db->rollback();
				setEventMessages($object->error, $object->errors, 'errors');
				$action = "create"; // Go back to create page
			}
		}
	}

	// Action add usergroup
	if (($action == 'addgroup' || $action == 'removegroup') && $caneditgroup) {
		if ($group) {
			$editgroup = new UserGroup($db);
			$editgroup->fetch($group);
			$editgroup->oldcopy = clone $editgroup;

			$object->fetch($id);

			if ($action == 'addgroup') {
				$result = $object->SetInGroup($group, $editgroup->entity);
			}
			if ($action == 'removegroup') {
				$result = $object->RemoveFromGroup($group, $editgroup->entity);
			}

			if ($result > 0) {
				$action = '';
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	}

	if ($action == 'update' && ($canedituser || $caneditpasswordandsee)) {
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		if ($caneditfield) {    // Case we can edit all field
			$error = 0;

			if (!GETPOST("lastname", 'alpha')) {
				setEventMessages($langs->trans("NameNotDefined"), null, 'errors');
				$action = "edit"; // Go back to create page
				$error++;
			}
			if (!GETPOST("login", 'alpha')) {
				setEventMessages($langs->trans("LoginNotDefined"), null, 'errors');
				$action = "edit"; // Go back to create page
				$error++;
			}

			if (!$error) {
				$object->fetch($id);

				$object->oldcopy = clone $object;

				$db->begin();

				$object->civility_code = GETPOST("civility_code", 'aZ09');
				$object->lastname = GETPOST("lastname", 'alphanohtml');
				$object->firstname = GETPOST("firstname", 'alphanohtml');
				// Protection against deletion of ref_employee while the field is not present in the user tab
				if (GETPOSTISSET("ref_employee")) {
					$object->ref_employee = GETPOST("ref_employee", 'alphanohtml');
				}
				// Protection against deletion of national_registration_number while the field is not present in the user tab
				if (GETPOSTISSET("national_registration_number")) {
					$object->national_registration_number = GETPOST("national_registration_number", 'alphanohtml');
				}
				$object->gender = GETPOST("gender", 'aZ09');
				if ($caneditpasswordandsee) {
					$object->pass = GETPOST("password", 'password');
				}
				if ($caneditpasswordandsee || $user->hasRight("api", "apikey", "generate")) {
					$object->api_key = (GETPOST("api_key", 'alphanohtml')) ? GETPOST("api_key", 'alphanohtml') : $object->api_key;
				}
				if (!empty($user->admin) && $user->id != $id) {
					// admin flag can only be set/unset by an admin user and not four ourself
					// A test is also done later when forging sql request
					$object->admin = GETPOSTINT("admin");
				}
				if ($user->admin && !$object->ldap_sid) {	// same test than on edit page
					$object->login = GETPOST("login", 'alphanohtml');
				}
				$object->address = GETPOST('address', 'alphanohtml');
				$object->zip = GETPOST('zipcode', 'alphanohtml');
				$object->town = GETPOST('town', 'alphanohtml');
				$object->country_id = GETPOSTINT('country_id');
				$object->state_id = GETPOSTINT('state_id');
				$object->office_phone = GETPOST("office_phone", 'alphanohtml');
				$object->office_fax = GETPOST("office_fax", 'alphanohtml');
				$object->user_mobile = GETPOST("user_mobile", 'alphanohtml');

				if (isModEnabled('socialnetworks')) {
					$object->socialnetworks = array();
					foreach ($socialnetworks as $key => $value) {
						if (GETPOST($key, 'alphanohtml')) {
							$object->socialnetworks[$key] = GETPOST($key, 'alphanohtml');
						}
					}
				}

				$object->email = preg_replace('/\s+/', '', GETPOST("email", 'alphanohtml'));
				$object->job = GETPOST("job", 'alphanohtml');
				$object->signature = GETPOST("signature", 'restricthtml');
				$object->accountancy_code = GETPOST("accountancy_code", 'alphanohtml');
				$object->openid = GETPOST("openid", 'alphanohtml');
				$object->fk_user = GETPOSTINT("fk_user") > 0 ? GETPOSTINT("fk_user") : 0;
				$object->fk_user_expense_validator = GETPOSTINT("fk_user_expense_validator") > 0 ? GETPOSTINT("fk_user_expense_validator") : 0;
				$object->fk_user_holiday_validator = GETPOSTINT("fk_user_holiday_validator") > 0 ? GETPOSTINT("fk_user_holiday_validator") : 0;
				$object->employee = GETPOSTINT('employee');

				$object->thm = GETPOST("thm", 'alphanohtml') != '' ? GETPOST("thm", 'alphanohtml') : '';
				$object->thm = price2num($object->thm);
				$object->tjm = GETPOST("tjm", 'alphanohtml') != '' ? GETPOST("tjm", 'alphanohtml') : '';
				$object->tjm = price2num($object->tjm);
				$object->salary = GETPOST("salary", 'alphanohtml') != '' ? GETPOST("salary", 'alphanohtml') : '';
				$object->salary = price2num($object->salary);
				$object->salaryextra = GETPOST("salaryextra", 'alphanohtml') != '' ? GETPOST("salaryextra", 'alphanohtml') : '';
				$object->salaryextra = price2num($object->salaryextra);
				$object->weeklyhours = GETPOST("weeklyhours", 'alphanohtml') != '' ? GETPOST("weeklyhours", 'alphanohtml') : '';
				$object->weeklyhours = price2num($object->weeklyhours);

				$object->color = GETPOST("color", 'alphanohtml') != '' ? GETPOST("color", 'alphanohtml') : '';
				$object->dateemployment = $dateemployment;
				$object->dateemploymentend = $dateemploymentend;
				$object->datestartvalidity = $datestartvalidity;
				$object->dateendvalidity = $dateendvalidity;
				$object->birth = $dateofbirth;

				if (isModEnabled('stock')) {
					$object->fk_warehouse = GETPOSTINT('fk_warehouse');
				}

				$object->lang = GETPOST('default_lang', 'aZ09');

				// Do we update also ->entity ?
				if (isModEnabled('multicompany') && empty($user->entity) && !empty($user->admin)) {	// If multicompany is not enabled, we never update the entity of a user.
					if (GETPOSTINT('superadmin')) {
						$object->entity = 0;
					} else {
						if (getDolGlobalString('MULTICOMPANY_TRANSVERSE_MODE')) {
							$object->entity = 1; // all users are in master entity
						} else {
							// We try to change the entity of user
							$object->entity = (GETPOSTISSET('entity') ? GETPOSTINT('entity') : $object->entity);
						}
					}
				}

				// Fill array 'array_options' with data from add form
				$ret = $extrafields->setOptionalsFromPost(null, $object, '@GETPOSTISSET');
				if ($ret < 0) {
					$error++;
				}

				if (GETPOST('deletephoto')) {
					$object->photo = '';
				}
				if (!empty($_FILES['photo']['name'])) {
					$isimage = image_format_supported($_FILES['photo']['name']);
					if ($isimage > 0) {
						$object->photo = dol_sanitizeFileName($_FILES['photo']['name']);
					} else {
						$error++;
						$langs->load("errors");
						setEventMessages($langs->trans("ErrorBadImageFormat"), null, 'errors');
						dol_syslog($langs->transnoentities("ErrorBadImageFormat"), LOG_INFO);
					}
				}

				if (!$error) {
					$passwordismodified = 0;
					if (!empty($object->pass)) {
						if ($object->pass != $object->pass_indatabase && !dol_verifyHash($object->pass, $object->pass_indatabase_crypted)) {
							$passwordismodified = 1;
						}
					}

					$ret = $object->update($user);		// This may include call to setPassword if password has changed
					if ($ret < 0) {
						$error++;
						if ($db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
							$langs->load("errors");
							setEventMessages($langs->trans("ErrorUpdateCanceledDueToDuplicatedUniqueValue", $object->login), null, 'errors');
						} else {
							setEventMessages($object->error, $object->errors, 'errors');
							$action = 'edit';
						}
					}
				}

				if (!$error && GETPOSTISSET('contactid')) {
					$contactid = GETPOSTINT('contactid');
					$socid = GETPOSTINT('socid');

					if ($contactid > 0) {	// The 'contactid' is used inpriority over the 'socid'
						$contact = new Contact($db);
						$contact->fetch($contactid);

						$sql = "UPDATE ".MAIN_DB_PREFIX."user";
						$sql .= " SET fk_socpeople=".((int) $contactid);
						if (!empty($contact->socid)) {
							$sql .= ", fk_soc=".((int) $contact->socid);
						} elseif ($socid > 0) {
							$sql .= ", fk_soc = null";
							setEventMessages($langs->trans("WarningUserDifferentContactSocid"), null, 'warnings'); // Add message if post socid != $contact->socid
						}
						$sql .= " WHERE rowid = ".((int) $object->id);
					} elseif ($socid > 0) {
						$sql = "UPDATE ".MAIN_DB_PREFIX."user";
						$sql .= " SET fk_socpeople=NULL, fk_soc=".((int) $socid);
						$sql .= " WHERE rowid = ".((int) $object->id);
					} else {
						$sql = "UPDATE ".MAIN_DB_PREFIX."user";
						$sql .= " SET fk_socpeople=NULL, fk_soc=NULL";
						$sql .= " WHERE rowid = ".((int) $object->id);
					}
					dol_syslog("usercard::update", LOG_DEBUG);
					$resql = $db->query($sql);
					if (!$resql) {
						$error++;
						setEventMessages($db->lasterror(), null, 'errors');
					}
				}

				if (!$error && !count($object->errors)) {
					if (!empty($object->oldcopy->photo) && (GETPOST('deletephoto') || ($object->photo != $object->oldcopy->photo))) {
						$fileimg = $conf->user->dir_output.'/'.get_exdir(0, 0, 0, 0, $object, 'user').'photos/'.$object->oldcopy->photo;
						dol_delete_file($fileimg);

						$dirthumbs = $conf->user->dir_output.'/'.get_exdir(0, 0, 0, 0, $object, 'user').'photos/thumbs';
						dol_delete_dir_recursive($dirthumbs);
					}

					if (isset($_FILES['photo']['tmp_name']) && trim($_FILES['photo']['tmp_name'])) {
						$dir = $conf->user->dir_output.'/'.get_exdir(0, 0, 0, 1, $object, 'user').'/photos';

						dol_mkdir($dir);

						if (@is_dir($dir)) {
							$newfile = $dir.'/'.dol_sanitizeFileName($_FILES['photo']['name']);
							$result = dol_move_uploaded_file($_FILES['photo']['tmp_name'], $newfile, 1, 0, $_FILES['photo']['error']);

							if (!($result > 0)) {
								setEventMessages($langs->trans("ErrorFailedToSaveFile"), null, 'errors');
							} else {
								// Create thumbs
								$object->addThumbs($newfile);
							}
						} else {
							$error++;
							$langs->load("errors");
							setEventMessages($langs->trans("ErrorFailedToCreateDir", $dir), $mesgs, 'errors');
						}
					}
				}

				if (!$error && !count($object->errors)) {
					// Then we add the associated categories
					$categories = GETPOST('usercats', 'array');
					$object->setCategories($categories);
				}

				if (!$error && !count($object->errors)) {
					setEventMessages($langs->trans("UserModified"), null, 'mesgs');
					$db->commit();

					$login = $_SESSION["dol_login"];
					if ($login && $login == $object->oldcopy->login && $object->oldcopy->login != $object->login) {    // Current user has changed its login
						$error++;
						$langs->load("errors");
						setEventMessages($langs->transnoentitiesnoconv("WarningYourLoginWasModifiedPleaseLogin"), null, 'warnings');
					}
					if ($passwordismodified && $object->login == $user->login) {    // Current user has changed its password
						$error++;
						$langs->load("errors");
						setEventMessages($langs->transnoentitiesnoconv("WarningYourPasswordWasModifiedPleaseLogin"), null, 'warnings');
						header("Location: ".DOL_URL_ROOT.'/user/card.php?id='.$object->id);
						exit;
					}
				} else {
					$db->rollback();
				}
			}
		} else {
			if ($caneditpasswordandsee) {    // Case we can edit only password
				dol_syslog("Not allowed to change fields, only password");

				$object->fetch($id);

				if (GETPOST("password", "password")) {	// If pass is empty, we do not change it.
					$object->oldcopy = clone $object;

					$ret = $object->setPassword($user, GETPOST("password", "password"));
					if (is_int($ret) && $ret < 0) {
						setEventMessages($object->error, $object->errors, 'errors');
					}
				}
			}
		}
	}

	// Change password with a new generated one
	if ((($action == 'confirm_password' && $confirm == 'yes' && $caneditpasswordandsee)
			|| ($action == 'confirm_passwordsend' && $confirm == 'yes' && $caneditpasswordandsend))
	) {
		$object->fetch($id);

		$newpassword = $object->setPassword($user, '');	// This will generate a new password
		if (is_int($newpassword) && $newpassword < 0) {
			// Echec
			setEventMessages($langs->trans("ErrorFailedToSetNewPassword"), null, 'errors');
		} else {
			// Success
			if ($action == 'confirm_passwordsend' && $confirm == 'yes') {
				if ($object->send_password($user, $newpassword) > 0) {
					setEventMessages($langs->trans("PasswordChangedAndSentTo", $object->email), null, 'mesgs');
				} else {
					setEventMessages($object->error, $object->errors, 'errors');
				}
			} else {
				setEventMessages($langs->trans("PasswordChangedTo", $newpassword), null, 'warnings');
			}
		}
	}

	// Action to initialize data from a LDAP record
	if ($action == 'adduserldap' && $canadduser) {
		$selecteduser = GETPOST('users');

		$required_fields = array(
			getDolGlobalString('LDAP_KEY_USERS'),
			getDolGlobalString('LDAP_FIELD_NAME'),
			getDolGlobalString('LDAP_FIELD_FIRSTNAME'),
			getDolGlobalString('LDAP_FIELD_LOGIN'),
			getDolGlobalString('LDAP_FIELD_LOGIN_SAMBA'),
			getDolGlobalString('LDAP_FIELD_PASSWORD'),
			getDolGlobalString('LDAP_FIELD_PASSWORD_CRYPTED'),
			getDolGlobalString('LDAP_FIELD_PHONE'),
			getDolGlobalString('LDAP_FIELD_FAX'),
			getDolGlobalString('LDAP_FIELD_MOBILE'),
			getDolGlobalString('LDAP_FIELD_MAIL'),
			getDolGlobalString('LDAP_FIELD_TITLE'),
			getDolGlobalString('LDAP_FIELD_DESCRIPTION'),
			getDolGlobalString('LDAP_FIELD_SID')
		);
		if (isModEnabled('socialnetworks')) {
			$arrayofsocialnetworks = array('skype', 'twitter', 'facebook', 'linkedin');
			foreach ($arrayofsocialnetworks as $socialnetwork) {
				$required_fields[] = getDolGlobalString('LDAP_FIELD_'.strtoupper($socialnetwork));
			}
		}

		$ldap = new Ldap();
		$result = $ldap->connectBind();
		if ($result >= 0) {
			// Remove from required_fields all entries not configured in LDAP (empty) and duplicated
			$required_fields = array_unique(array_values(array_filter($required_fields, "dol_validElement")));

			$ldapusers = $ldap->getRecords($selecteduser, getDolGlobalString('LDAP_USER_DN'), getDolGlobalString('LDAP_KEY_USERS'), $required_fields);
			//print_r($ldapusers);

			if (is_array($ldapusers)) {
				foreach ($ldapusers as $key => $attribute) {
					$ldap_lastname = $attribute[getDolGlobalString('LDAP_FIELD_NAME')];
					$ldap_firstname = $attribute[getDolGlobalString('LDAP_FIELD_FIRSTNAME')];
					$ldap_login = $attribute[getDolGlobalString('LDAP_FIELD_LOGIN')];
					$ldap_loginsmb = $attribute[getDolGlobalString('LDAP_FIELD_LOGIN_SAMBA')];
					$ldap_pass = $attribute[getDolGlobalString('LDAP_FIELD_PASSWORD')];
					$ldap_pass_crypted = $attribute[getDolGlobalString('LDAP_FIELD_PASSWORD_CRYPTED')];
					$ldap_phone = $attribute[getDolGlobalString('LDAP_FIELD_PHONE')];
					$ldap_fax = $attribute[getDolGlobalString('LDAP_FIELD_FAX')];
					$ldap_mobile = $attribute[getDolGlobalString('LDAP_FIELD_MOBILE')];
					$ldap_mail = $attribute[getDolGlobalString('LDAP_FIELD_MAIL')];
					$ldap_sid = $attribute[getDolGlobalString('LDAP_FIELD_SID')];
					$ldap_social = array();

					if (isModEnabled('socialnetworks')) {
						$arrayofsocialnetworks = array('skype', 'twitter', 'facebook', 'linkedin');
						foreach ($arrayofsocialnetworks as $socialnetwork) {
							$ldap_social[$socialnetwork] = $attribute[getDolGlobalString('LDAP_FIELD_'.strtoupper($socialnetwork))];
						}
					}
				}
			}
		} else {
			setEventMessages($ldap->error, $ldap->errors, 'errors');
		}
	}

	// Actions to send emails
	$triggersendname = 'USER_SENTBYMAIL';
	$paramname = 'id'; // Name of param key to open the card
	$mode = 'emailfromuser';
	$trackid = 'use'.$id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';

	// Actions to build doc
	$upload_dir = $conf->user->dir_output;
	$permissiontoadd = $user->hasRight("user", "user", "write");
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';
}


/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);
$formcompany = new FormCompany($db);
$formadmin = new FormAdmin($db);
$formfile = new FormFile($db);
if (isModEnabled('stock')) {
	$formproduct = new FormProduct($db);
}

// Count nb of users
$nbofusers = 1;
$sql = "SELECT COUNT(rowid) as nb FROM ".MAIN_DB_PREFIX.'user WHERE entity IN ('.getEntity('user').')';
$resql = $db->query($sql);
if ($resql) {
	$obj = $db->fetch_object($resql);
	if ($obj) {
		$nbofusers = $obj->nb;
	}
} else {
	dol_print_error($db);
}

if ($object->id > 0) {
	$person_name = !empty($object->firstname) ? $object->lastname.", ".$object->firstname : $object->lastname;
	$title = $person_name." - ".$langs->trans('Card');
} else {
	if (GETPOST('employee', 'alphanohtml')) {
		$title = $langs->trans("NewEmployee");
	} else {
		$title = $langs->trans("NewUser");
	}
}
$help_url = '';

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-user page-card');


if ($action == 'create' || $action == 'adduserldap') {
	print load_fiche_titre($title, '', 'user');

	print '<span class="opacitymedium">'.$langs->trans("CreateInternalUserDesc")."</span><br>\n";
	print "<br>";


	if (isModEnabled('ldap') && (getDolGlobalInt('LDAP_SYNCHRO_ACTIVE') === Ldap::SYNCHRO_LDAP_TO_DOLIBARR)) {
		$liste = array();

		// Show form to add an account from LDAP if sync LDAP -> Dolibarr is set
		$ldap = new Ldap();
		$result = $ldap->connectBind();
		if ($result >= 0) {
			$required_fields = array(
				getDolGlobalString('LDAP_KEY_USERS'),
				getDolGlobalString('LDAP_FIELD_FULLNAME'),
				getDolGlobalString('LDAP_FIELD_NAME'),
				getDolGlobalString('LDAP_FIELD_FIRSTNAME'),
				getDolGlobalString('LDAP_FIELD_LOGIN'),
				getDolGlobalString('LDAP_FIELD_LOGIN_SAMBA'),
				getDolGlobalString('LDAP_FIELD_PASSWORD'),
				getDolGlobalString('LDAP_FIELD_PASSWORD_CRYPTED'),
				getDolGlobalString('LDAP_FIELD_PHONE'),
				getDolGlobalString('LDAP_FIELD_FAX'),
				getDolGlobalString('LDAP_FIELD_MOBILE'),
				getDolGlobalString('LDAP_FIELD_SKYPE'),
				getDolGlobalString('LDAP_FIELD_MAIL'),
				getDolGlobalString('LDAP_FIELD_TITLE'),
				getDolGlobalString('LDAP_FIELD_DESCRIPTION'),
				getDolGlobalString('LDAP_FIELD_SID')
			);

			// Remove from required_fields all entries not configured in LDAP (empty) and duplicated
			$required_fields = array_unique(array_values(array_filter($required_fields, "dol_validElement")));

			// Get from LDAP database an array of results
			$ldapusers = $ldap->getRecords('*', getDolGlobalString('LDAP_USER_DN'), getDolGlobalString('LDAP_KEY_USERS'), $required_fields, 1);

			if (is_array($ldapusers)) {
				foreach ($ldapusers as $key => $ldapuser) {
					// Define the label string for this user
					$label = '';
					foreach ($required_fields as $value) {
						if ($value === getDolGlobalString('LDAP_FIELD_PASSWORD') || $value === getDolGlobalString('LDAP_FIELD_PASSWORD_CRYPTED')) {
							$label .= $value."=******* ";
						} elseif ($value) {
							$label .= $value."=".$ldapuser[$value]." ";
						}
					}
					$liste[$key] = $label;
				}
			} else {
				setEventMessages($ldap->error, $ldap->errors, 'errors');
			}
		} else {
			setEventMessages($ldap->error, $ldap->errors, 'errors');
		}

		// If user list is full, we show drop-down list
		print "\n\n<!-- Form liste LDAP debut -->\n";

		print '<form name="add_user_ldap" action="'.$_SERVER["PHP_SELF"].'" method="post">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<table class="border centpercent"><tr>';
		print '<td width="160">';
		print $langs->trans("LDAPUsers");
		print '</td>';
		print '<td>';
		print '<input type="hidden" name="action" value="adduserldap">';
		if (is_array($liste) && count($liste)) {
			print $form->selectarray('users', $liste, '', 1, 0, 0, '', 0, 0, 0, '', 'maxwidth500');
			print ajax_combobox('users');
		}
		print '</td><td class="center">';
		print '<input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans('Get')).'"'.(count($liste) ? '' : ' disabled').'>';
		print '</td></tr></table>';
		print '</form>';

		print "\n<!-- Form liste LDAP fin -->\n\n";
		print '<br>';
	}


	print '<form action="'.$_SERVER['PHP_SELF'].'" method="POST" name="createuser">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	if (!empty($ldap_sid)) {
		print '<input type="hidden" name="ldap_sid" value="'.dol_escape_htmltag($ldap_sid).'">';
	}
	print '<input type="hidden" name="entity" value="'.$conf->entity.'">';

	print dol_get_fiche_head(array(), '', '', 0, '');

	dol_set_focus('#lastname');

	print '<table class="border centpercent">';

	// Civility
	print '<tr><td><label for="civility_code">'.$langs->trans("UserTitle").'</label></td><td>';
	print $formcompany->select_civility(GETPOSTISSET("civility_code") ? GETPOST("civility_code", 'aZ09') : $object->civility_code, 'civility_code');
	print '</td></tr>';

	// Lastname
	print '<tr>';
	print '<td class="titlefieldcreate"><span class="fieldrequired">'.$langs->trans("Lastname").'</span></td>';
	print '<td>';
	if (!empty($ldap_lastname)) {
		print '<input type="hidden" id="lastname" name="lastname" value="'.dol_escape_htmltag($ldap_lastname).'">';
		print $ldap_lastname;
	} else {
		print '<input class="minwidth100 maxwidth150onsmartphone createloginauto" type="text" id="lastname" name="lastname" value="'.dol_escape_htmltag(GETPOST('lastname', 'alphanohtml')).'">';
	}
	print '</td></tr>';

	// Firstname
	print '<tr><td>'.$langs->trans("Firstname").'</td>';
	print '<td>';
	if (!empty($ldap_firstname)) {
		print '<input type="hidden" name="firstname" value="'.dol_escape_htmltag($ldap_firstname).'">';
		print $ldap_firstname;
	} else {
		print '<input id="firstname" class="minwidth100 maxwidth150onsmartphone createloginauto" type="text" name="firstname" value="'.dol_escape_htmltag(GETPOST('firstname', 'alphanohtml')).'">';
	}
	print '</td></tr>';

	// Login
	print '<tr><td><span class="fieldrequired">'.$langs->trans("Login").'</span></td>';
	print '<td>';
	if (!empty($ldap_login)) {
		print '<input type="hidden" name="login" value="'.dol_escape_htmltag($ldap_login).'">';
		print $ldap_login;
	} elseif (!empty($ldap_loginsmb)) {
		print '<input type="hidden" name="login" value="'.dol_escape_htmltag($ldap_loginsmb).'">';
		print $ldap_loginsmb;
	} else {
		print '<input id="login" class="maxwidth200 maxwidth150onsmartphone" maxsize="24" type="text" name="login" value="'.dol_escape_htmltag(GETPOST('login', 'alphanohtml')).'">';
	}
	print '</td></tr>';

	if (!empty($conf->use_javascript_ajax)) {
		// Add code to generate the login when creating a new user.
		// Best rule to generate would be to use the same rule than dol_buildlogin() but currently it is a PHP function not available in js.
		// TODO Implement a dol_buildlogin in javascript.
		$charforseparator = getDolGlobalString("MAIN_USER_SEPARATOR_CHAR_FOR_GENERATED_LOGIN", '.');
		if ($charforseparator == 'none') {
			$charforseparator = '';
		}
		print '<script>
			jQuery(document).ready(function() {
				$(".createloginauto").on("keyup", function() {
					console.log(".createloginauto change: We generate login when we have a lastname");

					lastname = $("#lastname").val().toLowerCase();
			';
		if (getDolGlobalString('MAIN_BUILD_LOGIN_RULE') == 'f.lastname') {
			print '			firstname = $("#firstname").val().toLowerCase()[0];';
		} else {
			print '			firstname = $("#firstname").val().toLowerCase();';
		}
		print '
					login = "";
					if (lastname) {
						if (firstname) {
							login = firstname + \''. dol_escape_js($charforseparator).'\';
						}
						login += lastname;
					}
					$("#login").val(login);
				})
			});
		</script>';
	}

	$generated_password = '';
	if (empty($ldap_sid)) {    // ldap_sid is for activedirectory
		$generated_password = getRandomPassword(false);
	}
	$password = (GETPOSTISSET('password') ? GETPOST('password') : $generated_password);

	// Administrator
	if (!empty($user->admin)) {
		print '<tr><td>'.$form->textwithpicto($langs->trans("Administrator"), $langs->trans("AdministratorDesc"), 1, 'star').'</td>';
		print '<td>';
		print $form->selectyesno('admin', GETPOST('admin'), 1, false, 0, 1);

		if (isModEnabled('multicompany') && !$user->entity) {
			if (!empty($conf->use_javascript_ajax)) {
				print '<script type="text/javascript">
                            $(function() {
                                $("select[name=admin]").change(function() {
                                     if ( $(this).val() == 0 ) {
                                        $("input[name=superadmin]")
                                            .prop("disabled", true)
                                            .prop("checked", false);
                                        $("select[name=entity]")
                                            .prop("disabled", false);
                                     } else {
                                        $("input[name=superadmin]")
                                            .prop("disabled", false);
                                     }
                                });
                                $("input[name=superadmin]").change(function() {
                                    if ( $(this).is(":checked") ) {
                                        $("select[name=entity]")
                                            .prop("disabled", true);
                                    } else {
                                        $("select[name=entity]")
                                            .prop("disabled", false);
                                    }
                                });
                            });
                    </script>';
			}
			$checked = (GETPOSTINT('superadmin') ? ' checked' : '');
			$disabled = (GETPOSTINT('superadmin') ? '' : ' disabled');
			print '<input type="checkbox" name="superadmin" id="superadmin" value="1"'.$checked.$disabled.' /> <label for="superadmin">'.$langs->trans("SuperAdministrator").'</span>';
		}
		print "</td></tr>\n";
	}

	// Gender
	print '<tr><td>'.$langs->trans("Gender").'</td>';
	print '<td>';
	$arraygender = array('man' => $langs->trans("Genderman"), 'woman' => $langs->trans("Genderwoman"), 'other' => $langs->trans("Genderother"));
	print $form->selectarray('gender', $arraygender, GETPOST('gender'), 1);
	print '</td></tr>';

	// Employee
	$defaultemployee = '1';
	print '<tr>';
	print '<td>'.$langs->trans('Employee').'</td><td>';
	print '<input type="checkbox" name="employee" value="1"'.(GETPOST('employee') == '1' ? ' checked="checked"' : (($defaultemployee && !GETPOSTISSET('login')) ? ' checked="checked"' : '')).'>';
	//print $form->selectyesno("employee", (GETPOST('employee') != '' ?GETPOST('employee') : $defaultemployee), 1);
	print '</td></tr>';

	// Hierarchy
	print '<tr><td class="titlefieldcreate">'.$langs->trans("HierarchicalResponsible").'</td>';
	print '<td>';
	print img_picto('', 'user', 'class="pictofixedwidth"').$form->select_dolusers($object->fk_user, 'fk_user', 1, array($object->id), 0, '', 0, $conf->entity, 0, 0, '', 0, '', 'maxwidth300 widthcentpercentminusx');
	print '</td>';
	print "</tr>\n";

	// Expense report validator
	if (isModEnabled('expensereport')) {
		print '<tr><td class="titlefieldcreate">';
		$text = $langs->trans("ForceUserExpenseValidator");
		print $form->textwithpicto($text, $langs->trans("ValidatorIsSupervisorByDefault"), 1, 'help');
		print '</td>';
		print '<td>';
		print img_picto('', 'user', 'class="pictofixedwidth"').$form->select_dolusers($object->fk_user_expense_validator, 'fk_user_expense_validator', 1, array($object->id), 0, '', 0, $conf->entity, 0, 0, '', 0, '', 'maxwidth300 widthcentpercentminusx');
		print '</td>';
		print "</tr>\n";
	}

	// Holiday request validator
	if (isModEnabled('holiday')) {
		print '<tr><td class="titlefieldcreate">';
		$text = $langs->trans("ForceUserHolidayValidator");
		print $form->textwithpicto($text, $langs->trans("ValidatorIsSupervisorByDefault"), 1, 'help');
		print '</td>';
		print '<td>';
		print img_picto('', 'user', 'class="pictofixedwidth"').$form->select_dolusers($object->fk_user_holiday_validator, 'fk_user_holiday_validator', 1, array($object->id), 0, '', 0, $conf->entity, 0, 0, '', 0, '', 'maxwidth300 widthcentpercentminusx');
		print '</td>';
		print "</tr>\n";
	}

	// External user
	print '<tr><td>'.$langs->trans("ExternalUser").' ?</td>';
	print '<td>';
	print $form->textwithpicto($langs->trans("Internal"), $langs->trans("InternalExternalDesc"), 1, 'help', '', 0, 2);
	print '</td></tr>';


	print '</table><hr><table class="border centpercent">';


	// Date validity
	print '<tr><td class="titlefieldcreate">'.$langs->trans("RangeOfLoginValidity").'</td>';
	print '<td>';
	print $form->selectDate($datestartvalidity, 'datestartvalidity', 0, 0, 1, 'formdatestartvalidity', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("from"));

	print ' &nbsp; ';

	print $form->selectDate($dateendvalidity, 'dateendvalidity', 0, 0, 1, 'formdateendvalidity', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("to"));
	print '</td>';
	print "</tr>\n";

	// Password
	print '<tr><td class="fieldrequired">'.$langs->trans("Password").'</td>';
	print '<td>';
	$valuetoshow = '';
	if (preg_match('/ldap/', $dolibarr_main_authentication)) {
		$valuetoshow .= ($valuetoshow ? ' + ' : '').$langs->trans("PasswordOfUserInLDAP").' (hidden)';
	}
	if (preg_match('/http/', $dolibarr_main_authentication)) {
		$valuetoshow .= ($valuetoshow ? ' + ' : '').$langs->trans("HTTPBasicPassword");
	}
	if (preg_match('/dolibarr/', $dolibarr_main_authentication) || preg_match('/forceuser/', $dolibarr_main_authentication)) {
		if (!empty($ldap_pass)) {	// For very old system comaptibilty. Now clear password can't be viewed from LDAP read
			$valuetoshow .= ($valuetoshow ? ' + ' : '').'<input type="hidden" name="password" value="'.dol_escape_htmltag($ldap_pass).'">'; // Dolibarr password is preffiled with LDAP known password
			$valuetoshow .= preg_replace('/./i', '*', $ldap_pass);
		} else {
			// We do not use a field password but a field text to show new password to use.
			$valuetoshow .= ($valuetoshow ? ' + '.$langs->trans("DolibarrPassword") : '').'<input class="minwidth300 maxwidth400 widthcentpercentminusx" maxlength="128" type="text" id="password" name="password" value="'.dol_escape_htmltag($password).'" autocomplete="new-password">';
			if (!empty($conf->use_javascript_ajax)) {
				$valuetoshow .= img_picto($langs->trans('Generate'), 'refresh', 'id="generate_password" class="linkobject paddingleft"');
			}
		}
	}

	// Other form for user password
	$parameters = array('valuetoshow' => $valuetoshow, 'password' => $password);
	$reshook = $hookmanager->executeHooks('printUserPasswordField', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if ($reshook > 0) {
		$valuetoshow = $hookmanager->resPrint; // to replace
	} else {
		$valuetoshow .= $hookmanager->resPrint; // to add
	}

	print $valuetoshow;
	print '</td></tr>';

	if (isModEnabled('api')) {
		// API key
		//$generated_password = getRandomPassword(false);
		print '<tr><td>'.$langs->trans("ApiKey").'</td>';
		print '<td>';
		print '<input class="minwidth300 maxwidth400 widthcentpercentminusx" minlength="12" maxlength="128" type="text" id="api_key" name="api_key" value="'.GETPOST('api_key', 'alphanohtml').'" autocomplete="off">';
		if (!empty($conf->use_javascript_ajax)) {
			print img_picto($langs->trans('Generate'), 'refresh', 'id="generate_api_key" class="linkobject paddingleft"');
		}
		print '</td></tr>';
	} else {
		// PARTIAL WORKAROUND
		$generated_fake_api_key = getRandomPassword(false);
		print '<input type="hidden" name="api_key" value="'.$generated_fake_api_key.'">';
	}


	print '</table><hr><table class="border centpercent">';


	// Address
	print '<tr><td class="tdtop titlefieldcreate">'.$form->editfieldkey('Address', 'address', '', $object, 0).'</td>';
	print '<td><textarea name="address" id="address" class="quatrevingtpercent" rows="3" wrap="soft">';
	print $object->address;
	print '</textarea></td></tr>';

	// Zip
	print '<tr><td>'.$form->editfieldkey('Zip', 'zipcode', '', $object, 0).'</td><td>';
	print $formcompany->select_ziptown($object->zip, 'zipcode', array('town', 'selectcountry_id', 'state_id'), 6);
	print '</td></tr>';

	// Town
	print '<tr><td>'.$form->editfieldkey('Town', 'town', '', $object, 0).'</td><td>';
	print $formcompany->select_ziptown($object->town, 'town', array('zipcode', 'selectcountry_id', 'state_id'));
	print '</td></tr>';

	// Country
	print '<tr><td>'.$form->editfieldkey('Country', 'selectcountry_id', '', $object, 0).'</td><td class="maxwidthonsmartphone">';
	print img_picto('', 'country', 'class="pictofixedwidth"');
	print $form->select_country((GETPOST('country_id') != '' ? GETPOST('country_id') : $object->country_id), 'country_id');
	if ($user->admin) {
		print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
	}
	print '</td></tr>';

	// State
	if (!getDolGlobalString('USER_DISABLE_STATE')) {
		print '<tr><td>'.$form->editfieldkey('State', 'state_id', '', $object, 0).'</td><td class="maxwidthonsmartphone">';
		print img_picto('', 'state', 'class="pictofixedwidth"');
		print $formcompany->select_state_ajax('country_id', $object->state_id, $object->country_id, 'state_id');
		print '</td></tr>';
	}

	// Tel
	print '<tr><td>'.$langs->trans("PhonePro").'</td>';
	print '<td>';
	print img_picto('', 'object_phoning', 'class="pictofixedwidth"');
	if (!empty($ldap_phone)) {
		print '<input type="hidden" name="office_phone" value="'.dol_escape_htmltag($ldap_phone).'">';
		print $ldap_phone;
	} else {
		print '<input class="maxwidth200 widthcentpercentminusx" type="text" name="office_phone" value="'.dol_escape_htmltag(GETPOST('office_phone', 'alphanohtml')).'">';
	}
	print '</td></tr>';

	// Tel portable
	print '<tr><td>'.$langs->trans("PhoneMobile").'</td>';
	print '<td>';
	print img_picto('', 'object_phoning_mobile', 'class="pictofixedwidth"');
	if (!empty($ldap_mobile)) {
		print '<input type="hidden" name="user_mobile" value="'.dol_escape_htmltag($ldap_mobile).'">';
		print $ldap_mobile;
	} else {
		print '<input class="maxwidth200 widthcentpercentminusx" type="text" name="user_mobile" value="'.dol_escape_htmltag(GETPOST('user_mobile', 'alphanohtml')).'">';
	}
	print '</td></tr>';

	// Fax
	print '<tr><td>'.$langs->trans("Fax").'</td>';
	print '<td>';
	print img_picto('', 'object_phoning_fax', 'class="pictofixedwidth"');
	if (!empty($ldap_fax)) {
		print '<input type="hidden" name="office_fax" value="'.dol_escape_htmltag($ldap_fax).'">';
		print $ldap_fax;
	} else {
		print '<input class="maxwidth200 widthcentpercentminusx" type="text" name="office_fax" value="'.dol_escape_htmltag(GETPOST('office_fax', 'alphanohtml')).'">';
	}
	print '</td></tr>';

	// EMail
	print '<tr><td'.(getDolGlobalString('USER_MAIL_REQUIRED') ? ' class="fieldrequired"' : '').'>'.$langs->trans("EMail").'</td>';
	print '<td>';
	print img_picto('', 'object_email', 'class="pictofixedwidth"');
	if (!empty($ldap_mail)) {
		print '<input type="hidden" name="email" value="'.dol_escape_htmltag($ldap_mail).'">';
		print $ldap_mail;
	} else {
		print '<input type="text" name="email" class="maxwidth500 widthcentpercentminusx" value="'.dol_escape_htmltag(GETPOST('email', 'alphanohtml')).'">';
	}
	print '</td></tr>';

	// Social networks
	if (isModEnabled('socialnetworks')) {
		foreach ($socialnetworks as $key => $value) {
			if ($value['active']) {
				print '<tr><td>'.$langs->trans($value['label']).'</td>';
				print '<td>';
				if (!empty($value['icon'])) {
					print '<span class="fab '.$value['icon'].' pictofixedwidth"></span>';
				}
				if (!empty($ldap_social[$key])) {
					print '<input type="hidden" name="'.$key.'" value="'.$ldap_social[$key].'">';
					print $ldap_social[$key];
				} else {
					print '<input class="maxwidth200 widthcentpercentminusx" type="text" name="'.$key.'" value="'.GETPOST($key, 'alphanohtml').'">';
				}
				print '</td></tr>';
			} else {
				// if social network is not active but value exist we do not want to loose it
				if (!empty($ldap_social[$key])) {
					print '<input type="hidden" name="'.$key.'" value="'.$ldap_social[$key].'">';
				} else {
					print '<input type="hidden" name="'.$key.'" value="'.GETPOST($key, 'alphanohtml').'">';
				}
			}
		}
	}

	// Accountancy code
	if (isModEnabled('accounting')) {
		print '<tr><td>'.$langs->trans("AccountancyCode").'</td>';
		print '<td>';
		print '<input type="text" class="maxwidthonsmartphone" name="accountancy_code" value="'.dol_escape_htmltag(GETPOST('accountancy_code', 'alphanohtml')).'">';
		print '</td></tr>';
	}

	// User color
	if (isModEnabled('agenda')) {
		print '<tr><td>'.$langs->trans("ColorUser").'</td>';
		print '<td>';
		print $formother->selectColor(GETPOSTISSET('color') ? GETPOST('color', 'alphanohtml') : $object->color, 'color', null, 1, '', 'hideifnotset');
		print '</td></tr>';
	}

	// Categories
	if (isModEnabled('category') && $user->hasRight("categorie", "read")) {
		print '<tr><td>'.$form->editfieldkey('Categories', 'usercats', '', $object, 0).'</td><td>';
		$cate_arbo = $form->select_all_categories('user', '', 'parent', 0, 0, 3);
		print img_picto('', 'category', 'class="pictofixedwidth"').$form->multiselectarray('usercats', $cate_arbo, GETPOST('usercats', 'array'), 0, 0, 'maxwdith300 widthcentpercentminusx', 0, '90%');
		print "</td></tr>";
	}

	// Default language
	if (getDolGlobalInt('MAIN_MULTILANGS')) {
		print '<tr><td>'.$form->editfieldkey('DefaultLang', 'default_lang', '', $object, 0, 'string', '', 0, 0, 'id', $langs->trans("WarningNotLangOfInterface", $langs->transnoentitiesnoconv("UserGUISetup"))).'</td>';
		print '<td class="maxwidthonsmartphone">'."\n";
		print img_picto('', 'language', 'class="pictofixedwidth"').$formadmin->select_language(GETPOST('default_lang', 'alpha') ? GETPOST('default_lang', 'alpha') : ($object->lang ? $object->lang : ''), 'default_lang', 0, 0, 1, 0, 0, 'maxwidth300 widthcentpercentminusx');
		print '</td>';
		print '</tr>';
	}

	// Multicompany
	if (isModEnabled('multicompany') && is_object($mc)) {
		// This is now done with hook formObjectOptions. Keep this code for backward compatibility with old multicompany module
		if (!method_exists($mc, 'formObjectOptions')) {
			if (!getDolGlobalString('MULTICOMPANY_TRANSVERSE_MODE') && $conf->entity == 1 && $user->admin && !$user->entity) {	// condition must be same for create and edit mode
				print "<tr>".'<td>'.$langs->trans("Entity").'</td>';
				print "<td>".$mc->select_entities($conf->entity);
				print "</td></tr>\n";
			} else {
				print '<input type="hidden" name="entity" value="'.$conf->entity.'" />';
			}
		}
	}

	// Other attributes
	$parameters = array();
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

	// Signature
	print '<tr><td class="tdtop">'.$langs->trans("Signature").'</td>';
	print '<td class="wordbreak">';
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

	$doleditor = new DolEditor('signature', GETPOST('signature', 'restricthtml'), '', 138, 'dolibarr_notes', 'In', true, $acceptlocallinktomedia, !getDolGlobalString('FCKEDITOR_ENABLE_USERSIGN') ? 0 : 1, ROWS_4, '90%');
	print $doleditor->Create(1);
	print '</td></tr>';

	// Note private
	print '<tr><td class="tdtop">';
	print $langs->trans("NotePublic");
	print '</td><td>';
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$doleditor = new DolEditor('note_public', GETPOSTISSET('note_public') ? GETPOST('note_public', 'restricthtml') : '', '', 100, 'dolibarr_notes', '', false, true, getDolGlobalString('FCKEDITOR_ENABLE_NOTE_PUBLIC'), ROWS_3, '90%');
	$doleditor->Create();
	print "</td></tr>\n";

	// Note private
	print '<tr><td class="tdtop">';
	print $langs->trans("NotePrivate");
	print '</td><td>';
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$doleditor = new DolEditor('note_private', GETPOSTISSET('note_private') ? GETPOST('note_private', 'restricthtml') : '', '', 100, 'dolibarr_notes', '', false, true, getDolGlobalString('FCKEDITOR_ENABLE_NOTE_PRIVATE'), ROWS_3, '90%');
	$doleditor->Create();
	print "</td></tr>\n";

	print '</table><hr><table class="border centpercent">';


	// TODO Move this into tab RH (HierarchicalResponsible must be on both tab)

	// Default warehouse
	if (isModEnabled('stock') && getDolGlobalString('MAIN_DEFAULT_WAREHOUSE_USER')) {
		print '<tr><td>'.$langs->trans("DefaultWarehouse").'</td><td>';
		print $formproduct->selectWarehouses($object->fk_warehouse, 'fk_warehouse', 'warehouseopen', 1);
		print '</td></tr>';
	}

	// Position/Job
	print '<tr><td class="titlefieldcreate">'.$langs->trans("PostOrFunction").'</td>';
	print '<td>';
	print '<input class="maxwidth200 maxwidth150onsmartphone" type="text" name="job" value="'.dol_escape_htmltag(GETPOST('job', 'alphanohtml')).'">';
	print '</td></tr>';

	if ((isModEnabled('salaries') && $user->hasRight("salaries", "read") && in_array($id, $childids))
		|| (isModEnabled('salaries') && $user->hasRight("salaries", "readall"))
		|| (isModEnabled('hrm') && $user->hasRight("hrm", "employee", "read"))) {
		$langs->load("salaries");

		// THM
		print '<tr><td>';
		$text = $langs->trans("THM");
		print $form->textwithpicto($text, $langs->trans("THMDescription"), 1, 'help', 'classthm');
		print '</td>';
		print '<td>';
		print '<input size="8" type="text" name="thm" value="'.dol_escape_htmltag(GETPOST('thm')).'"> '.$langs->getCurrencySymbol($conf->currency);
		print '</td>';
		print "</tr>\n";

		// TJM
		print '<tr><td>';
		$text = $langs->trans("TJM");
		print $form->textwithpicto($text, $langs->trans("TJMDescription"), 1, 'help', 'classtjm');
		print '</td>';
		print '<td>';
		print '<input size="8" type="text" name="tjm" value="'.dol_escape_htmltag(GETPOST('tjm')).'"> '.$langs->getCurrencySymbol($conf->currency);
		print '</td>';
		print "</tr>\n";

		// Salary
		print '<tr><td>'.$langs->trans("Salary").'</td>';
		print '<td>';
		print img_picto('', 'salary', 'class="pictofixedwidth paddingright"').'<input class="width100" type="text" name="salary" value="'.dol_escape_htmltag(GETPOST('salary')).'"> '.$langs->getCurrencySymbol($conf->currency);
		print '</td>';
		print "</tr>\n";
	}

	// Weeklyhours
	print '<tr><td>'.$langs->trans("WeeklyHours").'</td>';
	print '<td>';
	print '<input size="8" type="text" name="weeklyhours" value="'.dol_escape_htmltag(GETPOST('weeklyhours')).'">';
	print '</td>';
	print "</tr>\n";

	// Date employment
	print '<tr><td>'.$langs->trans("DateOfEmployment").'</td>';
	print '<td>';
	print $form->selectDate($dateemployment, 'dateemployment', 0, 0, 1, 'formdateemployment', 1, 1, 0, '', '', '', '', 1, '', $langs->trans("from"));

	print ' - ';

	print $form->selectDate($dateemploymentend, 'dateemploymentend', 0, 0, 1, 'formdateemploymentend', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("to"));
	print '</td>';
	print "</tr>\n";

	// Date birth
	print '<tr><td>'.$langs->trans("DateOfBirth").'</td>';
	print '<td>';
	print $form->selectDate($dateofbirth, 'dateofbirth', 0, 0, 1, 'createuser', 1, 0, 0, '', 0, '', '', 1, '', '', 'tzserver');
	print '</td>';
	print "</tr>\n";

	print "</table>\n";

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel("CreateUser");

	print "</form>";
} else {
	// View and edit mode
	if ($id > 0) {
		$res = $object->fetch($id, '', '', 1);
		if ($res < 0) {
			dol_print_error($db, $object->error);
			exit;
		}
		$res = $object->fetch_optionals();

		// Check if user has rights
		if (!getDolGlobalString('MULTICOMPANY_TRANSVERSE_MODE')) {
			$object->loadRights();
			if (empty($object->nb_rights) && $object->statut != 0 && empty($object->admin)) {
				setEventMessages($langs->trans('UserHasNoPermissions'), null, 'warnings');
			}
		}

		// Connection ldap
		// pour recuperer passDoNotExpire et userChangePassNextLogon
		if (isModEnabled('ldap') && !empty($object->ldap_sid)) {
			$ldap = new Ldap();
			$result = $ldap->connectBind();
			if ($result > 0) {
				$userSearchFilter = '(' . getDolGlobalString('LDAP_FILTER_CONNECTION').'('.$ldap->getUserIdentifier().'='.$object->login.'))';
				$entries = $ldap->fetch($object->login, $userSearchFilter);
				if (!$entries) {
					setEventMessages($ldap->error, $ldap->errors, 'errors');
				}

				$passDoNotExpire = 0;
				$userChangePassNextLogon = 0;
				$userDisabled = 0;
				$statutUACF = '';

				// Check options of user account
				if (count($ldap->uacf) > 0) {
					foreach ($ldap->uacf as $key => $statut) {
						if ($key == 65536) {
							$passDoNotExpire = 1;
							$statutUACF = $statut;
						}
					}
				} else {
					$userDisabled = 1;
					$statutUACF = "ACCOUNTDISABLE";
				}

				if ($ldap->pwdlastset == 0) {
					$userChangePassNextLogon = 1;
				}
			}
		}

		// Show tabs
		if ($mode == 'employee') { // For HRM module development
			$title = $langs->trans("Employee");
			$linkback = '<a href="'.DOL_URL_ROOT.'/hrm/employee/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
		} else {
			$title = $langs->trans("User");
			$linkback = '';

			if ($user->hasRight("user", "user", "read") || $user->admin) {
				$linkback = '<a href="'.DOL_URL_ROOT.'/user/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
			}
		}

		$head = user_prepare_head($object);

		/*
		 * Confirmation reinitialisation password
		 */
		if ($action == 'password') {
			print $form->formconfirm($_SERVER['PHP_SELF']."?id=$object->id", $langs->trans("ReinitPassword"), $langs->trans("ConfirmReinitPassword", $object->login), "confirm_password", '', 0, 1);
		}

		/*
		 * Confirmation envoi password
		 */
		if ($action == 'passwordsend') {
			print $form->formconfirm($_SERVER['PHP_SELF']."?id=$object->id", $langs->trans("SendNewPassword"), $langs->trans("ConfirmSendNewPassword", $object->login), "confirm_passwordsend", '', 0, 1);
		}

		/*
		 * Confirm deactivation
		 */
		if ($action == 'disable') {
			print $form->formconfirm($_SERVER['PHP_SELF']."?id=$object->id", $langs->trans("DisableAUser"), $langs->trans("ConfirmDisableUser", $object->login), "confirm_disable", '', 0, 1);
		}

		/*
		 * Confirm activation
		 */
		if ($action == 'enable') {
			print $form->formconfirm($_SERVER['PHP_SELF']."?id=$object->id", $langs->trans("EnableAUser"), $langs->trans("ConfirmEnableUser", $object->login), "confirm_enable", '', 0, 1);
		}

		/*
		 * Confirmation suppression
		 */
		if ($action == 'delete') {
			print $form->formconfirm($_SERVER['PHP_SELF']."?id=$object->id", $langs->trans("DeleteAUser"), $langs->trans("ConfirmDeleteUser", $object->login), "confirm_delete", '', 0, 1);
		}

		/*
		 * View mode
		 */
		if ($action != 'edit') {
			print dol_get_fiche_head($head, 'user', $title, -1, 'user');

			$morehtmlref = '<a href="'.DOL_URL_ROOT.'/user/vcard.php?id='.$object->id.'&output=file&file='.urlencode(dol_sanitizeFileName($object->getFullName($langs).'.vcf')).'" class="refid" rel="noopener" rel="noopener">';
			$morehtmlref .= img_picto($langs->trans("Download").' '.$langs->trans("VCard").' ('.$langs->trans("AddToContacts").')', 'vcard.png', 'class="valignmiddle marginleftonly paddingrightonly"');
			$morehtmlref .= '</a>';

			$urltovirtualcard = '/user/virtualcard.php?id='.((int) $object->id);
			$morehtmlref .= dolButtonToOpenUrlInDialogPopup('publicvirtualcard', $langs->transnoentitiesnoconv("PublicVirtualCardUrl").' - '.$object->getFullName($langs), img_picto($langs->trans("PublicVirtualCardUrl"), 'card', 'class="valignmiddle marginleftonly paddingrightonly"'), $urltovirtualcard, '', 'nohover');

			dol_banner_tab($object, 'id', $linkback, $user->hasRight("user", "user", "read") || $user->admin, 'rowid', 'ref', $morehtmlref);

			print '<div class="fichecenter">';
			print '<div class="fichehalfleft">';

			print '<div class="underbanner clearboth"></div>';
			print '<table class="border tableforfield centpercent">';

			// Login
			print '<tr><td class="titlefieldmiddle">'.$langs->trans("Login").'</td>';
			if (!empty($object->ldap_sid) && $object->statut == 0) {
				print '<td class="error">';
				print $langs->trans("LoginAccountDisableInDolibarr");
				print '</td>';
			} else {
				print '<td>';
				$addadmin = '';
				if (property_exists($object, 'admin')) {
					if (isModEnabled('multicompany') && !empty($object->admin) && empty($object->entity)) {
						$addadmin .= img_picto($langs->trans("SuperAdministratorDesc"), "redstar", 'class="paddingleft"');
					} elseif (!empty($object->admin)) {
						$addadmin .= img_picto($langs->trans("AdministratorDesc"), "star", 'class="paddingleft"');
					}
				}
				print showValueWithClipboardCPButton($object->login).$addadmin;
				print '</td>';
			}
			print '</tr>'."\n";

			// Type
			print '<tr><td>';
			$text = $langs->trans("Type");
			print $form->textwithpicto($text, $langs->trans("InternalExternalDesc"));
			print '</td><td>';
			$type = $langs->trans("Internal");
			if ($object->socid > 0) {
				$type = $langs->trans("External");
			}
			print '<span class="badgeneutral">';
			print $type;
			if ($object->ldap_sid) {
				print ' ('.$langs->trans("DomainUser").')';
			}
			print '</span>';
			print '</td></tr>'."\n";

			// Ldap sid
			if ($object->ldap_sid) {
				print '<tr><td>'.$langs->trans("Type").'</td><td>';
				print $langs->trans("DomainUser", $ldap->domainFQDN);
				print '</td></tr>'."\n";
			}

			// Employee
			print '<tr><td>'.$langs->trans("Employee").'</td><td>';
			if (getDolGlobalInt('MAIN_OPTIMIZEFORTEXTBROWSER') < 2) {
				print '<input type="checkbox" disabled name="employee" value="1"'.($object->employee ? ' checked="checked"' : '').'>';
			} else {
				print yn($object->employee);
			}
			print '</td></tr>'."\n";

			// TODO This is also available into the tab RH
			if ($nbofusers > 1) {
				// Hierarchy
				print '<tr><td>'.$langs->trans("HierarchicalResponsible").'</td>';
				print '<td>';
				if (empty($object->fk_user)) {
					print '<span class="opacitymedium">'.$langs->trans("None").'</span>';
				} else {
					$huser = new User($db);
					if ($object->fk_user > 0) {
						$huser->fetch($object->fk_user);
						print $huser->getNomUrl(-1);
					} else {
						print '<span class="opacitymedium">'.$langs->trans("None").'</span>';
					}
				}
				print '</td>';
				print "</tr>\n";

				// Expense report validator
				if (isModEnabled('expensereport')) {
					print '<tr><td>';
					$text = $langs->trans("ForceUserExpenseValidator");
					print $form->textwithpicto($text, $langs->trans("ValidatorIsSupervisorByDefault"), 1, 'help');
					print '</td>';
					print '<td>';
					if (!empty($object->fk_user_expense_validator)) {
						$evuser = new User($db);
						$evuser->fetch($object->fk_user_expense_validator);
						print $evuser->getNomUrl(-1);
					}
					print '</td>';
					print "</tr>\n";
				}

				// Holiday request validator
				if (isModEnabled('holiday')) {
					print '<tr><td>';
					$text = $langs->trans("ForceUserHolidayValidator");
					print $form->textwithpicto($text, $langs->trans("ValidatorIsSupervisorByDefault"), 1, 'help');
					print '</td>';
					print '<td>';
					if (!empty($object->fk_user_holiday_validator)) {
						$hvuser = new User($db);
						$hvuser->fetch($object->fk_user_holiday_validator);
						print $hvuser->getNomUrl(-1);
					}
					print '</td>';
					print "</tr>\n";
				}
			}

			// Position/Job
			print '<tr><td>'.$langs->trans("PostOrFunction").'</td>';
			print '<td>'.dol_escape_htmltag($object->job).'</td>';
			print '</tr>'."\n";

			// Weeklyhours
			print '<tr><td>'.$langs->trans("WeeklyHours").'</td>';
			print '<td>';
			print price2num($object->weeklyhours);
			print '</td>';
			print "</tr>\n";

			// Sensitive salary/value information
			if ((empty($user->socid) && in_array($id, $childids))	// A user can always see salary/value information for its subordinates
				|| (isModEnabled('salaries') && $user->hasRight("salaries", "readall"))
				|| (isModEnabled('hrm') && $user->hasRight("hrm", "employee", "read"))) {
				$langs->load("salaries");

				// Salary
				print '<tr><td>'.$langs->trans("Salary").'</td>';
				print '<td>';
				print($object->salary != '' ? img_picto('', 'salary', 'class="pictofixedwidth paddingright"').'<span class="amount">'.price($object->salary, 0, $langs, 1, -1, -1, $conf->currency) : '').'</span>';
				print '</td>';
				print "</tr>\n";

				// THM
				print '<tr><td>';
				$text = $langs->trans("THM");
				print $form->textwithpicto($text, $langs->trans("THMDescription"), 1, 'help', 'classthm');
				print '</td>';
				print '<td>';
				print($object->thm != '' ? price($object->thm, 0, $langs, 1, -1, -1, $conf->currency) : '');
				print '</td>';
				print "</tr>\n";

				// TJM
				print '<tr><td>';
				$text = $langs->trans("TJM");
				print $form->textwithpicto($text, $langs->trans("TJMDescription"), 1, 'help', 'classtjm');
				print '</td>';
				print '<td>';
				print($object->tjm != '' ? price($object->tjm, 0, $langs, 1, -1, -1, $conf->currency) : '');
				print '</td>';
				print "</tr>\n";
			}

			// Date employment
			print '<tr><td>'.$langs->trans("DateOfEmployment").'</td>';
			print '<td>';
			if ($object->dateemployment) {
				print '<span class="opacitymedium">'.$langs->trans("FromDate").'</span> ';
				print dol_print_date($object->dateemployment, 'day');
			}
			if ($object->dateemploymentend) {
				print '<span class="opacitymedium"> - '.$langs->trans("To").'</span> ';
				print dol_print_date($object->dateemploymentend, 'day');
			}
			print '</td>';
			print "</tr>\n";

			// Date of birth
			print '<tr><td>'.$langs->trans("DateOfBirth").'</td>';
			print '<td>';
			print dol_print_date($object->birth, 'day', 'tzserver');
			print '</td>';
			print "</tr>\n";

			// Default warehouse
			if (isModEnabled('stock') && getDolGlobalString('MAIN_DEFAULT_WAREHOUSE_USER')) {
				require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
				print '<tr><td>'.$langs->trans("DefaultWarehouse").'</td><td>';
				if ($object->fk_warehouse > 0) {
					$warehousestatic = new Entrepot($db);
					$warehousestatic->fetch($object->fk_warehouse);
					print $warehousestatic->getNomUrl(1);
				}
				print '</td></tr>';
			}

			print '</table>';

			print '</div>';
			print '<div class="fichehalfright">';

			print '<div class="underbanner clearboth"></div>';

			print '<table class="border tableforfield centpercent">';

			// Color user
			if (isModEnabled('agenda')) {
				print '<tr><td class="titlefield">'.$langs->trans("ColorUser").'</td>';
				print '<td>';
				print $formother->showColor($object->color, '');
				print '</td>';
				print "</tr>\n";
			}

			// Categories
			if (isModEnabled('category') && $user->hasRight("categorie", "read")) {
				print '<tr><td class="titlefield">'.$langs->trans("Categories").'</td>';
				print '<td colspan="3">';
				print $form->showCategories($object->id, Categorie::TYPE_USER, 1);
				print '</td></tr>';
			}

			// Default language
			if (getDolGlobalInt('MAIN_MULTILANGS')) {
				$langs->load("languages");
				require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
				print '<tr><td class="titlefield">';
				print $form->textwithpicto($langs->trans("DefaultLang"), $langs->trans("WarningNotLangOfInterface", $langs->transnoentitiesnoconv("UserGUISetup")));
				print '</td><td>';
				//$s=picto_from_langcode($object->default_lang);
				//print ($s?$s.' ':'');
				$labellang = ($object->lang ? $langs->trans('Language_'.$object->lang) : '');
				print picto_from_langcode($object->lang, 'class="paddingrightonly saturatemedium opacitylow"');
				print $labellang;
				print '</td></tr>';
			}

			if (isset($conf->file->main_authentication) && preg_match('/openid/', $conf->file->main_authentication) && getDolGlobalString('MAIN_OPENIDURL_PERUSER')) {
				print '<tr><td>'.$langs->trans("OpenIDURL").'</td>';
				print '<td>'.$object->openid.'</td>';
				print "</tr>\n";
			}

			// Multicompany
			if (isModEnabled('multicompany') && is_object($mc)) {
				// This is now done with hook formObjectOptions. Keep this code for backward compatibility with old multicompany module
				if (!method_exists($mc, 'formObjectOptions')) {
					if (isModEnabled('multicompany') && !getDolGlobalString('MULTICOMPANY_TRANSVERSE_MODE') && $conf->entity == 1 && $user->admin && !$user->entity) {
						print '<tr><td>'.$langs->trans("Entity").'</td><td>';
						if (empty($object->entity)) {
							print $langs->trans("AllEntities");
						} else {
							$mc->getInfo($object->entity);
							print $mc->label;
						}
						print "</td></tr>\n";
					}
				}
			}

			// Other attributes
			include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

			// Company / Contact
			if (isModEnabled("societe")) {
				print '<tr><td>'.$langs->trans("LinkToCompanyContact").'</td>';
				print '<td>';
				$s = '';
				if (isset($object->socid) && $object->socid > 0) {
					$societe = new Societe($db);
					$societe->fetch($object->socid);
					if ($societe->id > 0) {
						$s .= $societe->getNomUrl(1, '');
					}
				} else {
					$s .= '<span class="opacitymedium hideonsmartphone">'.$langs->trans("ThisUserIsNot").'</span>';
				}
				if (!empty($object->contact_id)) {
					$contact = new Contact($db);
					$contact->fetch($object->contact_id);
					if ($contact->id > 0) {
						if ($object->socid > 0 && $s) {
							$s .= ' / ';
						} else {
							$s .= '<br>';
						}
						$s .= $contact->getNomUrl(1, '');
					}
				}
				print $s;
				print '</td>';
				print '</tr>'."\n";
			}

			// Module Adherent
			if (isModEnabled('member')) {
				$langs->load("members");
				print '<tr><td>'.$langs->trans("LinkedToDolibarrMember").'</td>';
				print '<td>';
				if ($object->fk_member) {
					$adh = new Adherent($db);
					$adh->fetch($object->fk_member);
					$adh->ref = $adh->getFullname($langs); // Force to show login instead of id
					print $adh->getNomUrl(-1);
				} else {
					print '<span class="opacitymedium hideonsmartphone">'.$langs->trans("UserNotLinkedToMember").'</span>';
				}
				print '</td>';
				print '</tr>'."\n";
			}

			// Signature
			print '<tr><td class="tdtop">'.$langs->trans('Signature').'</td><td class="wordbreak">';
			print dol_htmlentitiesbr($object->signature);
			print "</td></tr>\n";

			print "</table>\n";


			// Credentials section

			print '<br>';
			print '<div class="div-table-responsive-no-min">';
			print '<table class="border tableforfield centpercent">';

			print '<tr class="liste_titre"><td class="liste_titre">';
			print img_picto('', 'security', 'class="paddingleft pictofixedwidth"').$langs->trans("Credentials");
			print '</td>';
			print '<td class="liste_titre"></td>';
			print '</tr>';

			// Date login validity
			print '<tr class="nooddeven"><td class="titlefield">'.$langs->trans("RangeOfLoginValidity").'</td>';
			print '<td>';
			if ($object->datestartvalidity) {
				print '<span class="opacitymedium">'.$langs->trans("FromDate").'</span> ';
				print dol_print_date($object->datestartvalidity, 'day');
			}
			if ($object->dateendvalidity) {
				print '<span class="opacitymedium"> - '.$langs->trans("To").'</span> ';
				print dol_print_date($object->dateendvalidity, 'day');
			}
			print '</td>';
			print "</tr>\n";

			// Alternative email for OAUth2 login
			if (!empty($object->email_oauth2) && preg_match('/googleoauth/', $dolibarr_main_authentication)) {
				print '<tr class="nooddeven"><td class="titlefield">'.$langs->trans("AlternativeEmailForOAuth2").'</td>';
				print '<td>';
				print dol_print_email($object->email_oauth2);
				print '</td>';
				print "</tr>\n";
			}

			// Password
			$valuetoshow = '';
			if (preg_match('/ldap/', $dolibarr_main_authentication)) {
				if (!empty($object->ldap_sid)) {
					if ($passDoNotExpire) {
						$valuetoshow .= ($valuetoshow ? (' '.$langs->trans("or").' ') : '').$langs->trans("LdapUacf_".$statutUACF);
					} elseif ($userChangePassNextLogon) {
						$valuetoshow .= ($valuetoshow ? (' '.$langs->trans("or").' ') : '').'<span class="warning">'.$langs->trans("UserMustChangePassNextLogon", $ldap->domainFQDN).'</span>';
					} elseif ($userDisabled) {
						$valuetoshow .= ($valuetoshow ? (' '.$langs->trans("or").' ') : '').'<span class="warning">'.$langs->trans("LdapUacf_".$statutUACF, $ldap->domainFQDN).'</span>';
					} else {
						$valuetoshow .= ($valuetoshow ? (' '.$langs->trans("or").' ') : '').$langs->trans("PasswordOfUserInLDAP");
					}
				} else {
					$valuetoshow .= ($valuetoshow ? (' '.$langs->trans("or").' ') : '').$langs->trans("PasswordOfUserInLDAP");
				}
			}
			if (preg_match('/http/', $dolibarr_main_authentication)) {
				$valuetoshow .= ($valuetoshow ? (' '.$langs->trans("or").' ') : '').$langs->trans("HTTPBasicPassword");
			}
			/*
			if (preg_match('/dolibarr/', $dolibarr_main_authentication)) {
				if ($object->pass) {
					$valuetoshow .= ($valuetoshow ? (' '.$langs->trans("or").' ') : '');
					$valuetoshow .= '<span class="opacitymedium">'.$langs->trans("Hidden").'</span>';
				} else {
					if ($user->admin && $user->id == $object->id) {
						$valuetoshow .= ($valuetoshow ? (' '.$langs->trans("or").' ') : '');
						$valuetoshow .= '<span class="opacitymedium">'.$langs->trans("Hidden").'</span>';
						$valuetoshow .= '<!-- Encrypted into '.$object->pass_indatabase_crypted.' -->';
					} else {
						$valuetoshow .= ($valuetoshow ? (' '.$langs->trans("or").' ') : '');
						$valuetoshow .= '<span class="opacitymedium">'.$langs->trans("Hidden").'</span>';
					}
				}
			}
			*/

			// Other form for user password
			$parameters = array('valuetoshow' => $valuetoshow);
			$reshook = $hookmanager->executeHooks('printUserPasswordField', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
			if ($reshook > 0) {
				$valuetoshow = $hookmanager->resPrint; // to replace
			} else {
				$valuetoshow .= $hookmanager->resPrint; // to add
			}

			if (dol_string_nohtmltag($valuetoshow)) {	// If there is a real visible content to show
				print '<tr class="nooddeven"><td class="titlefield">'.$langs->trans("Password").'</td>';
				print '<td class="wordbreak">';
				print $valuetoshow;
				print "</td>";
				print '</tr>'."\n";
			}

			// API key
			if (isModEnabled('api') && ($user->id == $id || $user->admin || $user->hasRight("api", "apikey", "generate"))) {
				print '<tr class="nooddeven"><td>'.$langs->trans("ApiKey").'</td>';
				print '<td>';
				if (!empty($object->api_key)) {
					print '<span class="opacitymedium">';
					print showValueWithClipboardCPButton($object->api_key, 1, $langs->trans("Hidden"));		// TODO Add an option to also reveal the hash, not only copy paste
					print '</span>';
				}
				print '</td></tr>';
			}
			if ((getDolGlobalInt('MAIN_ENABLE_LOGINS_PRIVACY') == 0) || (getDolGlobalInt('MAIN_ENABLE_LOGINS_PRIVACY') == 1 && $object->id == $user->id)) {
				print '<tr class="nooddeven"><td>'.$langs->trans("LastConnexion").'</td>';
				print '<td>';
				if ($object->datepreviouslogin) {
					print dol_print_date($object->datepreviouslogin, "dayhour", "tzuserrel").' <span class="opacitymedium">('.$langs->trans("Previous").')</span>, ';
				}
				if ($object->datelastlogin) {
					print dol_print_date($object->datelastlogin, "dayhour", "tzuserrel").' <span class="opacitymedium">('.$langs->trans("Currently").')</span>';
				}
				print '</td>';
				print "</tr>\n";
			}
			print '</table>';
			print '</div>';

			print '</div>';

			print '</div>';
			print '<div class="clearboth"></div>';


			print dol_get_fiche_end();


			/*
			 * Buttons actions
			 */
			print '<div class="tabsAction">';

			$parameters = array();
			$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
			if (empty($reshook)) {
				$params = array(
					'attr' => array(
						'title' => '',
						'class' => 'classfortooltip'
					)
				);

				if (empty($user->socid)) {
					$canSendMail = false;
					if (!empty($object->email)) {
						$langs->load("mails");
						$canSendMail = true;
						unset($params['attr']['title']);
					} else {
						$langs->load("mails");
						$params['attr']['title'] = $langs->trans('NoEMail');
					}
					print dolGetButtonAction('', $langs->trans('SendMail'), 'default', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=presend&mode=init#formmailbeforetitle', '', $canSendMail, $params);
				}

				if ($caneditfield && (!isModEnabled('multicompany') || !$user->entity || ($object->entity == $conf->entity) || (getDolGlobalString('MULTICOMPANY_TRANSVERSE_MODE') && $object->entity == 1))) {
					if (getDolGlobalString('MAIN_ONLY_LOGIN_ALLOWED')) {
						$params['attr']['title'] = $langs->trans('DisabledInMonoUserMode');
						print dolGetButtonAction($langs->trans('Modify'), '', 'default', $_SERVER['PHP_SELF'].'#', '', false, $params);
					} else {
						unset($params['attr']['title']);
						print dolGetButtonAction($langs->trans('Modify'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=edit&token='.newToken(), '', true, $params);
					}
				} elseif ($caneditpasswordandsee && !$object->ldap_sid &&
				(!isModEnabled('multicompany') || !$user->entity || ($object->entity == $conf->entity) || (getDolGlobalString('MULTICOMPANY_TRANSVERSE_MODE') && $object->entity == 1))) {
					unset($params['attr']['title']);
					print dolGetButtonAction($langs->trans('Modify'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=edit', '', true, $params);
				}

				// If we have a password generator engine enabled
				$params = array(
					'attr' => array(
						'title' => '',
						'class' => 'classfortooltip'
					)
				);
				if (getDolGlobalString('USER_PASSWORD_GENERATED') != 'none') {
					if ($object->status == $object::STATUS_DISABLED) {
						$params['attr']['title'] = $langs->trans('UserDisabled');
						print dolGetButtonAction($langs->trans('ReinitPassword'), '', 'default', $_SERVER['PHP_SELF'].'#', '', false, $params);
					} elseif (($user->id != $id && $caneditpasswordandsee) && $object->login && !$object->ldap_sid &&
					((!isModEnabled('multicompany') && $object->entity == $user->entity) || !$user->entity || ($object->entity == $conf->entity) || (getDolGlobalString('MULTICOMPANY_TRANSVERSE_MODE') && $object->entity == 1))) {
						unset($params['attr']['title']);
						print dolGetButtonAction($langs->trans('ReinitPassword'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=password&token='.newToken(), '', true, $params);
					}

					if ($object->status == $object::STATUS_DISABLED) {
						$params['attr']['title'] = $langs->trans('UserDisabled');
						print dolGetButtonAction($langs->trans('SendNewPassword'), '', 'default', $_SERVER['PHP_SELF'].'#', '', false, $params);
					} elseif (($user->id != $id && $caneditpasswordandsend) && $object->login && !$object->ldap_sid &&
					((!isModEnabled('multicompany') && $object->entity == $user->entity) || !$user->entity || ($object->entity == $conf->entity) || (getDolGlobalString('MULTICOMPANY_TRANSVERSE_MODE') && $object->entity == 1))) {
						if ($object->email) {
							unset($params['attr']['title']);
							print dolGetButtonAction($langs->trans('SendNewPassword'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=passwordsend&token='.newToken(), '', true, $params);
						} else {
							$params['attr']['title'] = $langs->trans('NoEMail');
							print dolGetButtonAction($langs->trans('SendNewPassword'), '', 'default', $_SERVER['PHP_SELF'].'#', '', false, $params);
						}
					}
				}

				if ($user->id != $id && $candisableuser && $object->statut == 0 &&
				((!isModEnabled('multicompany') && $object->entity == $user->entity) || !$user->entity || ($object->entity == $conf->entity) || (getDolGlobalString('MULTICOMPANY_TRANSVERSE_MODE') && $object->entity == 1))) {
					unset($params['attr']['title']);
					print dolGetButtonAction($langs->trans('Reactivate'), '', 'default', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=enable&token='.newToken(), '', true, $params);
				}
				// Disable user
				if ($user->id != $id && $candisableuser && $object->statut == 1 &&
				((!isModEnabled('multicompany') && $object->entity == $user->entity) || !$user->entity || ($object->entity == $conf->entity) || (getDolGlobalString('MULTICOMPANY_TRANSVERSE_MODE') && $object->entity == 1))) {
					unset($params['attr']['title']);
					print dolGetButtonAction($langs->trans('DisableUser'), '', 'default', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=disable&token='.newToken(), '', true, $params);
				} else {
					if ($user->id == $id) {
						$params['attr']['title'] = $langs->trans('CantDisableYourself');
						print dolGetButtonAction($langs->trans('DisableUser'), '', 'default', $_SERVER['PHP_SELF'].'#', '', false, $params);
					}
				}
				// Delete
				if ($user->id != $id && $candisableuser &&
				((!isModEnabled('multicompany') && $object->entity == $user->entity) || !$user->entity || ($object->entity == $conf->entity) || (getDolGlobalString('MULTICOMPANY_TRANSVERSE_MODE') && $object->entity == 1))) {
					if ($user->admin || !$object->admin) { // If user edited is admin, delete is possible on for an admin
						unset($params['attr']['title']);
						print dolGetButtonAction($langs->trans('DeleteUser'), '', 'default', $_SERVER['PHP_SELF'].'?action=delete&token='.newToken().'&id='.$object->id, '', true, $params);
					} else {
						$params['attr']['title'] = $langs->trans('MustBeAdminToDeleteOtherAdmin');
						print dolGetButtonAction($langs->trans('DeleteUser'), '', 'default', $_SERVER['PHP_SELF'].'?action=delete&token='.newToken().'&id='.$object->id, '', false, $params);
					}
				}
			}

			print "</div>\n";



			// Select mail models is same action as presend
			if (GETPOST('modelselected')) {
				$action = 'presend';
			}

			// Presend form
			$modelmail = 'user';
			$defaulttopic = 'Information';
			$diroutput = $conf->user->dir_output;
			$trackid = 'use'.$object->id;

			include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';

			if ($action != 'presend' && $action != 'send') {
				/*
				 * List of groups of user
				 */

				if ($canreadgroup) {
					print '<!-- Group section -->'."\n";

					print load_fiche_titre($langs->trans("ListOfGroupsForUser"), '', '');

					// We select the groups that the users belongs to
					$exclude = array();

					$usergroup = new UserGroup($db);
					$groupslist = $usergroup->listGroupsForUser($object->id, false);

					if (!empty($groupslist)) {
						foreach ($groupslist as $groupforuser) {
							$exclude[] = $groupforuser->id;
						}
					}

					// Other form for add user to group
					$parameters = array('caneditgroup' => $caneditgroup, 'groupslist' => $groupslist, 'exclude' => $exclude);
					$reshook = $hookmanager->executeHooks('formAddUserToGroup', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
					print $hookmanager->resPrint;

					if (empty($reshook)) {
						if ($caneditgroup) {
							print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$id.'" method="POST">'."\n";
							print '<input type="hidden" name="token" value="'.newToken().'" />';
							print '<input type="hidden" name="action" value="addgroup" />';
							print '<input type="hidden" name="page_y" value="" />';
						}

						print '<!-- List of groups of the user -->'."\n";
						print '<table class="noborder centpercent">'."\n";
						print '<tr class="liste_titre"><th class="liste_titre">'.$langs->trans("Groups").'</th>'."\n";
						print '<th class="liste_titre right">';
						if ($caneditgroup) {
							print $form->select_dolgroups('', 'group', 1, $exclude, 0, '', '', $object->entity, false, 'maxwidth150');
							print ' &nbsp; ';
							print '<input type="hidden" name="entity" value="'.$conf->entity.'" />';
							print '<input type="submit" class="button buttongen button-add reposition" value="'.$langs->trans("Add").'" />';
						}
						print '</th></tr>'."\n";

						// List of groups of user
						if (!empty($groupslist)) {
							foreach ($groupslist as $group) {
								print '<tr class="oddeven">';
								print '<td class="tdoverflowmax150">';
								if ($caneditgroup) {
									print $group->getNomUrl(1);
								} else {
									print img_object($langs->trans("ShowGroup"), "group").' '.$group->name;
								}
								print '</td>';
								print '<td class="right">';
								if ($caneditgroup) {
									print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=removegroup&token='.newToken().'&group='.((int) $group->id).'">';
									print img_picto($langs->trans("RemoveFromGroup"), 'unlink');
									print '</a>';
								} else {
									print "&nbsp;";
								}
								print "</td></tr>\n";
							}
						} else {
							print '<tr class="oddeven"><td colspan="3"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
						}

						print "</table>";

						if ($caneditgroup) {
							print '</form>';
						}
						print "<br>";
					}
				}
			}
		}

		/*
		 * Edit mode
		 */
		if ($action == 'edit' && ($canedituser || $caneditpasswordandsee)) {
			print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'" method="POST" name="updateuser" enctype="multipart/form-data">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="update">';
			print '<input type="hidden" name="entity" value="'.$object->entity.'">';

			print dol_get_fiche_head($head, 'user', $title, 0, 'user');

			print '<table class="border centpercent">';

			// Ref/ID
			if (getDolGlobalString('MAIN_SHOW_TECHNICAL_ID')) {
				print '<tr><td class="titlefieldcreate">'.$langs->trans("Ref").'</td>';
				print '<td>';
				print $object->id;
				print '</td>';
				print '</tr>';
			}

			// Civility
			print '<tr><td class="titlefieldcreate"><label for="civility_code">'.$langs->trans("UserTitle").'</label></td><td>';
			if ($caneditfield && !$object->ldap_sid) {
				print $formcompany->select_civility(GETPOSTISSET("civility_code") ? GETPOST("civility_code", 'aZ09') : $object->civility_code, 'civility_code');
			} elseif ($object->civility_code) {
				print $langs->trans("Civility".$object->civility_code);
			}
			print '</td></tr>';

			// Lastname
			print "<tr>";
			print '<td class="titlefieldcreate fieldrequired">'.$langs->trans("Lastname").'</td>';
			print '<td>';
			if ($caneditfield && !$object->ldap_sid) {
				print '<input class="minwidth100" type="text" class="flat" name="lastname" value="'.$object->lastname.'">';
			} else {
				print '<input type="hidden" name="lastname" value="'.$object->lastname.'">';
				print $object->lastname;
			}
			print '</td>';
			print '</tr>';

			// Firstname
			print '<tr><td>'.$langs->trans("Firstname").'</td>';
			print '<td>';
			if ($caneditfield && !$object->ldap_sid) {
				print '<input class="minwidth100" type="text" class="flat" name="firstname" value="'.$object->firstname.'">';
			} else {
				print '<input type="hidden" name="firstname" value="'.$object->firstname.'">';
				print $object->firstname;
			}
			print '</td></tr>';

			// Login
			print "<tr>".'<td><span class="fieldrequired">'.$langs->trans("Login").'</span></td>';
			print '<td>';
			if ($user->admin && !$object->ldap_sid) {
				print '<input maxlength="50" type="text" class="flat" name="login" value="'.$object->login.'">';
			} else {
				print '<input type="hidden" name="login" value="'.$object->login.'">';
				print $object->login;
			}
			print '</td>';
			print '</tr>';

			// Administrator
			print '<tr><td>'.$form->textwithpicto($langs->trans("Administrator"), $langs->trans("AdministratorDesc")).'</td>';
			if ($object->socid > 0) {
				$langs->load("admin");
				print '<td>';
				print '<input type="hidden" name="admin" value="'.$object->admin.'">'.yn($object->admin);
				print ' <span class="opacitymedium">('.$langs->trans("ExternalUser").')</span>';
				print '</td></tr>';
			} else {
				print '<td>';
				$nbAdmin = $user->getNbOfUsers('active', '', 1);
				$nbSuperAdmin = $user->getNbOfUsers('active', 'superadmin', 1);
				//var_dump($nbAdmin);
				//var_dump($nbSuperAdmin);
				if ($user->admin								// Need to be admin to allow downgrade of an admin
				&& ($user->id != $object->id)                   // Don't downgrade ourself
				&& (
					(!isModEnabled('multicompany') && $nbAdmin >= 1)
					|| (isModEnabled('multicompany') && (($object->entity > 0 || ($user->entity == 0 && $object->entity == 0)) || $nbSuperAdmin > 1))    // Don't downgrade a superadmin if alone
				)
				) {
					print $form->selectyesno('admin', $object->admin, 1, false, 0, 1);

					if (isModEnabled('multicompany') && !$user->entity) {
						if ($conf->use_javascript_ajax) {
							print '<script type="text/javascript">
									$(function() {
										var admin = $("select[name=admin]").val();
										if (admin == 0) {
											$("input[name=superadmin]")
													.prop("disabled", true)
													.prop("checked", false);
										}
										if ($("input[name=superadmin]").is(":checked")) {
											$("select[name=entity]")
													.prop("disabled", true);
										}
										$("select[name=admin]").change(function() {
											 if ( $(this).val() == 0 ) {
											 	$("input[name=superadmin]")
													.prop("disabled", true)
													.prop("checked", false);
											 	$("select[name=entity]")
													.prop("disabled", false);
											 } else {
											 	$("input[name=superadmin]")
													.prop("disabled", false);
											 }
										});
										$("input[name=superadmin]").change(function() {
											if ( $(this).is(":checked")) {
												$("select[name=entity]")
													.prop("disabled", true);
											} else {
												$("select[name=entity]")
													.prop("disabled", false);
											}
										});
									});
								</script>';
						}

						$checked = (($object->admin && !$object->entity) ? ' checked' : '');
						print '<input type="checkbox" name="superadmin" id="superadmin" value="1"'.$checked.' /> <label for="superadmin">'.$langs->trans("SuperAdministrator").'</span>';
					}
				} else {
					$yn = yn($object->admin);
					print '<input type="hidden" name="admin" value="'.$object->admin.'">';
					print '<input type="hidden" name="superadmin" value="'.(empty($object->entity) ? 1 : 0).'">';
					if (isModEnabled('multicompany') && empty($object->entity)) {
						print $form->textwithpicto($yn, $langs->trans("DontDowngradeSuperAdmin"), 1, 'warning');
					} else {
						print $yn;
					}
				}
				print '</td></tr>';
			}

			// Gender
			print '<tr><td>'.$langs->trans("Gender").'</td>';
			print '<td>';
			$arraygender = array('man' => $langs->trans("Genderman"), 'woman' => $langs->trans("Genderwoman"), 'other' => $langs->trans("Genderother"));
			if ($caneditfield) {
				print $form->selectarray('gender', $arraygender, GETPOSTISSET('gender') ? GETPOST('gender') : $object->gender, 1);
			} else {
				print $arraygender[$object->gender];
			}
			print '</td></tr>';

			// Employee
			print '<tr>';
			print '<td>'.$form->editfieldkey('Employee', 'employee', '', $object, 0).'</td><td>';
			if ($caneditfield) {
				print '<input type="checkbox" name="employee" value="1"'.($object->employee ? ' checked="checked"' : '').'>';
				//print $form->selectyesno("employee", $object->employee, 1);
			} else {
				print '<input type="checkbox" name="employee" disabled value="1"'.($object->employee ? ' checked="checked"' : '').'>';
				/*if ($object->employee) {
					print $langs->trans("Yes");
				} else {
					print $langs->trans("No");
				}*/
			}
			print '</td></tr>';

			if ($nbofusers > 1) {
				// Hierarchy
				print '<tr><td class="titlefieldcreate">'.$langs->trans("HierarchicalResponsible").'</td>';
				print '<td>';
				if ($caneditfield) {
					print img_picto('', 'user', 'class="pictofixedwidth"').$form->select_dolusers($object->fk_user, 'fk_user', 1, array($object->id), 0, '', 0, $object->entity, 0, 0, '', 0, '', 'widthcentpercentminusx maxwidth300');
				} else {
					print '<input type="hidden" name="fk_user" value="'.$object->fk_user.'">';
					$huser = new User($db);
					$huser->fetch($object->fk_user);
					print $huser->getNomUrl(-1);
				}
				print '</td>';
				print "</tr>\n";

				// Expense report validator
				if (isModEnabled('expensereport')) {
					print '<tr><td class="titlefieldcreate">';
					$text = $langs->trans("ForceUserExpenseValidator");
					print $form->textwithpicto($text, $langs->trans("ValidatorIsSupervisorByDefault"), 1, 'help');
					print '</td>';
					print '<td>';
					if ($caneditfield) {
						print img_picto('', 'user', 'class="pictofixedwidth"').$form->select_dolusers($object->fk_user_expense_validator, 'fk_user_expense_validator', 1, array($object->id), 0, '', 0, $object->entity, 0, 0, '', 0, '', 'widthcentpercentminusx maxwidth300');
					} else {
						print '<input type="hidden" name="fk_user_expense_validator" value="'.$object->fk_user_expense_validator.'">';
						$evuser = new User($db);
						$evuser->fetch($object->fk_user_expense_validator);
						print $evuser->getNomUrl(-1);
					}
					print '</td>';
					print "</tr>\n";
				}

				// Holiday request validator
				if (isModEnabled('holiday')) {
					print '<tr><td class="titlefieldcreate">';
					$text = $langs->trans("ForceUserHolidayValidator");
					print $form->textwithpicto($text, $langs->trans("ValidatorIsSupervisorByDefault"), 1, 'help');
					print '</td>';
					print '<td>';
					if ($caneditfield) {
						print img_picto('', 'user', 'class="pictofixedwidth"').$form->select_dolusers($object->fk_user_holiday_validator, 'fk_user_holiday_validator', 1, array($object->id), 0, '', 0, $object->entity, 0, 0, '', 0, '', 'widthcentpercentminusx maxwidth300');
					} else {
						print '<input type="hidden" name="fk_user_holiday_validator" value="'.$object->fk_user_holiday_validator.'">';
						$hvuser = new User($db);
						$hvuser->fetch($object->fk_user_holiday_validator);
						print $hvuser->getNomUrl(-1);
					}
					print '</td>';
					print "</tr>\n";
				}
			}

			// External user ?
			print '<tr><td>'.$langs->trans("ExternalUser").' ?</td>';
			print '<td>';
			if ($user->id == $object->id || !$user->admin) {
				// Read mode
				$type = $langs->trans("Internal");
				if ($object->socid) {
					$type = $langs->trans("External");
				}
				// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
				print $form->textwithpicto($type, $langs->trans("InternalExternalDesc"));
				if ($object->ldap_sid) {
					print ' ('.$langs->trans("DomainUser").')';
				}
			} else {
				// Select mode
				$type = 0;
				if ($object->contact_id) {
					$type = $object->contact_id;
				}

				if ($object->socid > 0 && !($object->contact_id > 0)) {	// external user but no link to a contact
					print img_picto('', 'company').$form->select_company($object->socid, 'socid', '', '&nbsp;', 0, 0, null, 0, 'maxwidth300');
					print img_picto('', 'contact');
					//print $form->selectcontacts(0, 0, 'contactid', 1, '', '', 1, 'maxwidth300', false, 1);
					print $form->select_contact(0, 0, 'contactid', 1, '', '', 1, 'minwidth100imp widthcentpercentminusxx maxwidth300', true, 1);
					if ($object->ldap_sid) {
						print ' ('.$langs->trans("DomainUser").')';
					}
				} elseif ($object->socid > 0 && $object->contact_id > 0) {	// external user with a link to a contact
					print img_picto('', 'company').$form->select_company($object->socid, 'socid', '', '&nbsp;', 0, 0, null, 0, 'maxwidth300'); // We keep thirdparty empty, contact is already set
					print img_picto('', 'contact');
					//print $form->selectcontacts(0, $object->contact_id, 'contactid', 1, '', '', 1, 'maxwidth300', false, 1);
					print $form->select_contact(0, $object->contact_id, 'contactid', 1, '', '', 1, 'minwidth100imp widthcentpercentminusxx maxwidth300', true, 1);
					if ($object->ldap_sid) {
						print ' ('.$langs->trans("DomainUser").')';
					}
				} elseif (!($object->socid > 0) && $object->contact_id > 0) {	// internal user with a link to a contact
					print img_picto('', 'company').$form->select_company(0, 'socid', '', '&nbsp;', 0, 0, null, 0, 'maxwidth300'); // We keep thirdparty empty, contact is already set
					print img_picto('', 'contact');
					//print $form->selectcontacts(0, $object->contact_id, 'contactid', 1, '', '', 1, 'maxwidth300', false, 1);
					print $form->select_contact(0, $object->contact_id, 'contactid', 1, '', '', 1, 'minwidth100imp widthcentpercentminusxx maxwidth300', true, 1);
					if ($object->ldap_sid) {
						print ' ('.$langs->trans("DomainUser").')';
					}
				} else {	// $object->socid is not > 0 here
					print img_picto('', 'company').$form->select_company(0, 'socid', '', '&nbsp;', 0, 0, null, 0, 'maxwidth300'); // We keep thirdparty empty, contact is already set
					print img_picto('', 'contact');
					//print $form->selectcontacts(0, 0, 'contactid', 1, '', '', 1, 'maxwidth300', false, 1);
					print $form->select_contact(0, 0, 'contactid', 1, '', '', 1, 'minwidth100imp widthcentpercentminusxx maxwidth300', true, 1);
				}
			}
			print '</td></tr>';

			print '</table>';

			print '<hr>';

			print '<table class="border centpercent">';

			// Date access validity
			print '<tr><td>'.$langs->trans("RangeOfLoginValidity").'</td>';
			print '<td>';
			if ($caneditfield) {
				print $form->selectDate($datestartvalidity ? $datestartvalidity : $object->datestartvalidity, 'datestartvalidity', 0, 0, 1, 'formdatestartvalidity', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("from"));
			} else {
				print dol_print_date($object->datestartvalidity, 'day');
			}
			print ' &nbsp; ';

			if ($caneditfield) {
				print $form->selectDate($dateendvalidity ? $dateendvalidity : $object->dateendvalidity, 'dateendvalidity', 0, 0, 1, 'formdateendvalidity', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("to"));
			} else {
				print dol_print_date($object->dateendvalidity, 'day');
			}
			print '</td>';
			print "</tr>\n";

			// Pass
			print '<tr><td class="titlefieldcreate">'.$langs->trans("Password").'</td>';
			print '<td>';
			$valuetoshow = '';
			if (preg_match('/ldap/', $dolibarr_main_authentication)) {
				$valuetoshow .= ($valuetoshow ? (' '.$langs->trans("or").' ') : '').$langs->trans("PasswordOfUserInLDAP");
			}
			if (preg_match('/http/', $dolibarr_main_authentication)) {
				$valuetoshow .= ($valuetoshow ? (' '.$langs->trans("or").' ') : '').$form->textwithpicto($text, $langs->trans("DolibarrInHttpAuthenticationSoPasswordUseless", $dolibarr_main_authentication), 1, 'warning');
			}
			if (preg_match('/dolibarr/', $dolibarr_main_authentication) || preg_match('/forceuser/', $dolibarr_main_authentication)) {
				if ($caneditpasswordandsee) {
					$valuetoshow .= ($valuetoshow ? (' '.$langs->trans("or").' ') : '').'<input maxlength="128" type="password" class="flat" id="password" name="password" value="'.dol_escape_htmltag($object->pass).'" autocomplete="new-password">';
					if (!empty($conf->use_javascript_ajax)) {
						$valuetoshow .= img_picto((getDolGlobalString('USER_PASSWORD_GENERATED') === 'none' ? $langs->trans('NoPasswordGenerationRuleConfigured') : $langs->trans('Generate')), 'refresh', 'id="generate_password" class="paddingleft'.(getDolGlobalString('USER_PASSWORD_GENERATED') === 'none' ? ' opacitymedium' : ' linkobject').'"');
					}
				} else {
					$valuetoshow .= ($valuetoshow ? (' '.$langs->trans("or").' ') : '').preg_replace('/./i', '*', $object->pass);
				}
			}
			// Other form for user password
			$parameters = array('valuetoshow' => $valuetoshow, 'caneditpasswordandsee' => $caneditpasswordandsee, 'caneditpasswordandsend' => $caneditpasswordandsend);
			$reshook = $hookmanager->executeHooks('printUserPasswordField', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
			if ($reshook > 0) {
				$valuetoshow = $hookmanager->resPrint; // to replace
			} else {
				$valuetoshow .= $hookmanager->resPrint; // to add
			}

			print $valuetoshow;
			print "</td></tr>\n";

			// API key
			if (isModEnabled('api')) {
				print '<tr><td>'.$langs->trans("ApiKey").'</td>';
				print '<td>';
				if ($caneditpasswordandsee || $user->hasRight("api", "apikey", "generate")) {
					print '<input class="minwidth300 maxwidth400 widthcentpercentminusx" minlength="12" maxlength="128" type="text" id="api_key" name="api_key" value="'.$object->api_key.'" autocomplete="off">';
					if (!empty($conf->use_javascript_ajax)) {
						print img_picto($langs->trans('Generate'), 'refresh', 'id="generate_api_key" class="linkobject paddingleft"');
					}
				}
				print '</td></tr>';
			}

			// OpenID url
			if (isset($conf->file->main_authentication) && preg_match('/openid/', $conf->file->main_authentication) && getDolGlobalString('MAIN_OPENIDURL_PERUSER')) {
				print "<tr>".'<td>'.$langs->trans("OpenIDURL").'</td>';
				print '<td>';
				if ($caneditfield) {
					print '<input class="minwidth100" type="url" name="openid" class="flat" value="'.$object->openid.'">';
				} else {
					print '<input type="hidden" name="openid" value="'.$object->openid.'">';
					print $object->openid;
				}
				print '</td></tr>';
			}

			print '</table><hr><table class="border centpercent">';


			// Address
			print '<tr><td class="tdtop titlefieldcreate">'.$form->editfieldkey('Address', 'address', '', $object, 0).'</td>';
			print '<td>';
			if ($caneditfield) {
				print '<textarea name="address" id="address" class="quatrevingtpercent" rows="3" wrap="soft">';
			}
			print dol_escape_htmltag(GETPOSTISSET('address') ? GETPOST('address') : $object->address, 0, 1);
			if ($caneditfield) {
				print '</textarea>';
			}
			print '</td></tr>';

			// Zip
			print '<tr><td>'.$form->editfieldkey('Zip', 'zipcode', '', $object, 0).'</td><td>';
			if ($caneditfield) {
				print $formcompany->select_ziptown((GETPOSTISSET('zipcode') ? GETPOST('zipcode') : $object->zip), 'zipcode', array('town', 'selectcountry_id', 'state_id'), 6);
			} else {
				print $object->zip;
			}
			print '</td></tr>';

			// Town
			print '<tr><td>'.$form->editfieldkey('Town', 'town', '', $object, 0).'</td><td>';
			if ($caneditfield) {
				print $formcompany->select_ziptown((GETPOSTISSET('town') ? GETPOST('town') : $object->town), 'town', array('zipcode', 'selectcountry_id', 'state_id'));
			} else {
				print $object->town;
			}
			print '</td></tr>';

			// Country
			print '<tr><td>'.$form->editfieldkey('Country', 'selectcounty_id', '', $object, 0).'</td><td>';
			print img_picto('', 'country', 'class="pictofixedwidth"');
			if ($caneditfield) {
				print $form->select_country((GETPOST('country_id') != '' ? GETPOST('country_id') : $object->country_id), 'country_id');
				if ($user->admin) {
					print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
				}
			} else {
				$countrylabel = getCountry($object->country_id, '0');
				print $countrylabel;
			}
			print '</td></tr>';

			// State
			if (!getDolGlobalString('USER_DISABLE_STATE')) {
				print '<tr><td class="tdoverflow">'.$form->editfieldkey('State', 'state_id', '', $object, 0).'</td><td>';
				if ($caneditfield) {
					print img_picto('', 'state', 'class="pictofixedwidth"');
					print $formcompany->select_state_ajax('country_id', $object->state_id, $object->country_id, 'state_id');
				} else {
					print $object->state;
				}
				print '</td></tr>';
			}

			// Tel pro
			print "<tr>".'<td>'.$langs->trans("PhonePro").'</td>';
			print '<td>';
			print img_picto('', 'phoning', 'class="pictofixedwidth"');
			if ($caneditfield && empty($object->ldap_sid)) {
				print '<input type="text" name="office_phone" class="flat maxwidth200" value="'.$object->office_phone.'">';
			} else {
				print '<input type="hidden" name="office_phone" value="'.$object->office_phone.'">';
				print $object->office_phone;
			}
			print '</td></tr>';

			// Tel mobile
			print "<tr>".'<td>'.$langs->trans("PhoneMobile").'</td>';
			print '<td>';
			print img_picto('', 'phoning_mobile', 'class="pictofixedwidth"');
			if ($caneditfield && empty($object->ldap_sid)) {
				print '<input type="text" name="user_mobile" class="flat maxwidth200" value="'.$object->user_mobile.'">';
			} else {
				print '<input type="hidden" name="user_mobile" value="'.$object->user_mobile.'">';
				print $object->user_mobile;
			}
			print '</td></tr>';

			// Fax
			print "<tr>".'<td>'.$langs->trans("Fax").'</td>';
			print '<td>';
			print img_picto('', 'phoning_fax', 'class="pictofixedwidth"');
			if ($caneditfield && empty($object->ldap_sid)) {
				print '<input type="text" name="office_fax" class="flat maxwidth200" value="'.$object->office_fax.'">';
			} else {
				print '<input type="hidden" name="office_fax" value="'.$object->office_fax.'">';
				print $object->office_fax;
			}
			print '</td></tr>';

			// EMail
			print "<tr>".'<td'.(getDolGlobalString('USER_MAIL_REQUIRED') ? ' class="fieldrequired"' : '').'>'.$langs->trans("EMail").'</td>';
			print '<td>';
			print img_picto('', 'object_email', 'class="pictofixedwidth"');
			if ($caneditfield && empty($object->ldap_sid)) {
				print '<input class="minwidth100 maxwidth500 widthcentpercentminusx" type="text" name="email" class="flat" value="'.$object->email.'">';
			} else {
				print '<input type="hidden" name="email" value="'.$object->email.'">';
				print $object->email;
			}
			print '</td></tr>';

			if (isModEnabled('socialnetworks')) {
				foreach ($socialnetworks as $key => $value) {
					if ($value['active']) {
						print '<tr><td>'.$langs->trans($value['label']).'</td>';
						print '<td>';
						if (!empty($value['icon'])) {
							print '<span class="fab '.$value['icon'].' pictofixedwidth"></span>';
						}
						if ($caneditfield && empty($object->ldap_sid)) {
							print '<input type="text" name="'.$key.'" class="flat maxwidth200" value="'.(isset($object->socialnetworks[$key]) ? $object->socialnetworks[$key] : '').'">';
						} else {
							print '<input type="hidden" name="'.$key.'" value="'.$object->socialnetworks[$key].'">';
							print $object->socialnetworks[$key];
						}
						print '</td></tr>';
					} else {
						// if social network is not active but value exist we do not want to loose it
						print '<input type="hidden" name="'.$key.'" value="'.(isset($object->socialnetworks[$key]) ? $object->socialnetworks[$key] : '').'">';
					}
				}
			}

			print '</table><hr><table class="border centpercent">';

			// Default warehouse
			if (isModEnabled('stock') && getDolGlobalString('MAIN_DEFAULT_WAREHOUSE_USER')) {
				print '<tr><td class="titlefield">'.$langs->trans("DefaultWarehouse").'</td><td>';
				print $formproduct->selectWarehouses($object->fk_warehouse, 'fk_warehouse', 'warehouseopen', 1);
				print ' <a href="'.DOL_URL_ROOT.'/product/stock/card.php?action=create&token='.newToken().'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.$object->id.'&action=edit&token='.newToken()).'"><span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("AddWarehouse").'"></span></a>';
				print '</td></tr>';
			}

			// Accountancy code
			if (isModEnabled('accounting')) {
				print "<tr>";
				print '<td class="titlefieldcreate">'.$langs->trans("AccountancyCode").'</td>';
				print '<td>';
				if ($caneditfield) {
					print '<input type="text" class="flat maxwidth300" name="accountancy_code" value="'.$object->accountancy_code.'">';
				} else {
					print '<input type="hidden" name="accountancy_code" value="'.$object->accountancy_code.'">';
					print $object->accountancy_code;
				}
				print '</td>';
				print "</tr>";
			}

			// User color
			if (isModEnabled('agenda')) {
				print '<tr><td class="titlefieldcreate">'.$langs->trans("ColorUser").'</td>';
				print '<td>';
				if ($caneditfield) {
					print $formother->selectColor(GETPOSTISSET('color') ? GETPOST('color', 'alphanohtml') : $object->color, 'color', null, 1, '', 'hideifnotset');
				} else {
					print $formother->showColor($object->color, '');
				}
				print '</td></tr>';
			}

			// Photo
			print '<tr>';
			print '<td class="titlefieldcreate">'.$langs->trans("Photo").'</td>';
			print '<td>';
			print $form->showphoto('userphoto', $object, 60, 0, $caneditfield, 'photowithmargin', 'small', 1, 0, 'user', 1);
			print '</td>';
			print '</tr>';

			// Categories
			if (isModEnabled('category') && $user->hasRight("categorie", "read")) {
				print '<tr><td>'.$form->editfieldkey('Categories', 'usercats', '', $object, 0).'</td>';
				print '<td>';
				print img_picto('', 'category', 'class="pictofixedwidth"');
				$cate_arbo = $form->select_all_categories(Categorie::TYPE_USER, null, null, null, null, 1);
				$c = new Categorie($db);
				$cats = $c->containing($object->id, Categorie::TYPE_USER);
				$arrayselected = array();
				foreach ($cats as $cat) {
					$arrayselected[] = $cat->id;
				}
				if ($caneditfield) {
					print $form->multiselectarray('usercats', $cate_arbo, $arrayselected, '', 0, '', 0, '90%');
				} else {
					print $form->showCategories($object->id, Categorie::TYPE_USER, 1);
				}
				print "</td></tr>";
			}

			// Default language
			if (getDolGlobalInt('MAIN_MULTILANGS')) {
				print '<tr><td>'.$form->editfieldkey('DefaultLang', 'default_lang', '', $object, 0, 'string', '', 0, 0, 'id', $langs->trans("WarningNotLangOfInterface", $langs->transnoentitiesnoconv("UserGUISetup"))).'</td><td colspan="3">'."\n";
				print img_picto('', 'language', 'class="pictofixedwidth"').$formadmin->select_language($object->lang, 'default_lang', 0, null, '1', 0, 0, 'widthcentpercentminusx maxwidth300');
				print '</td>';
				print '</tr>';
			}

			// Status
			print '<tr><td>'.$langs->trans("Status").'</td>';
			print '<td>';
			print $object->getLibStatut(4);
			print '</td></tr>';

			// Company / Contact
			/* Disabled, this is already on field "External user ?"
			if (isModEnabled("societe")) {
				print '<tr><td>'.$langs->trans("LinkToCompanyContact").'</td>';
				print '<td>';
				if ($object->socid > 0) {
					$societe = new Societe($db);
					$societe->fetch($object->socid);
					print $societe->getNomUrl(1, '');
					if ($object->contact_id) {
						$contact = new Contact($db);
						$contact->fetch($object->contact_id);
						print ' / <a href="'.DOL_URL_ROOT.'/contact/card.php?id='.$object->contact_id.'">'.img_object($langs->trans("ShowContact"), 'contact').' '.dol_trunc($contact->getFullName($langs), 32).'</a>';
					}
				} else {
					print '<span class="opacitymedium hideonsmartphone">'.$langs->trans("ThisUserIsNot").'</span>';
				}
				print ' <span class="opacitymedium hideonsmartphone">('.$langs->trans("UseTypeFieldToChange").')</span>';
				print '</td>';
				print "</tr>\n";
			}
			*/

			// Module Adherent
			if (isModEnabled('member')) {
				$langs->load("members");
				print '<tr><td>'.$langs->trans("LinkedToDolibarrMember").'</td>';
				print '<td>';
				if ($object->fk_member) {
					$adh = new Adherent($db);
					$adh->fetch($object->fk_member);
					$adh->ref = $adh->login; // Force to show login instead of id
					print $adh->getNomUrl(1);
				} else {
					print '<span class="opacitymedium hideonsmartphone">'.$langs->trans("UserNotLinkedToMember").'</span>';
				}
				print '</td>';
				print "</tr>\n";
			}

			// Multicompany
			// TODO check if user not linked with the current entity before change entity (thirdparty, invoice, etc.) !!
			if (isModEnabled('multicompany') && is_object($mc)) {
				// This is now done with hook formObjectOptions. Keep this code for backward compatibility with old multicompany module
				if (!method_exists($mc, 'formObjectOptions')) {
					if (empty($conf->multicompany->transverse_mode) && $conf->entity == 1 && $user->admin && !$user->entity) {
						print "<tr>".'<td>'.$langs->trans("Entity").'</td>';
						print "<td>".$mc->select_entities($object->entity, 'entity', '', 0, 1, false, false, 1); // last parameter 1 means, show also a choice 0=>'all entities'
						print "</td></tr>\n";
					} else {
						print '<input type="hidden" name="entity" value="'.$conf->entity.'" />';
					}
				}
			}

			// Other attributes
			$parameters = array('colspan' => ' colspan="2"');
			//include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';		// We do not use common tpl here because we need a special test on $caneditfield
			$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
			print $hookmanager->resPrint;
			if (empty($reshook)) {
				if ($caneditfield) {
					print $object->showOptionals($extrafields, 'edit');
				} else {
					print $object->showOptionals($extrafields, 'view');
				}
			}

			// Signature
			print '<tr><td class="tdtop">'.$langs->trans("Signature").'</td>';
			print '<td>';
			if ($caneditfield) {
				require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

				$doleditor = new DolEditor('signature', $object->signature, '', 138, 'dolibarr_notes', 'In', false, $acceptlocallinktomedia, !getDolGlobalString('FCKEDITOR_ENABLE_USERSIGN') ? 0 : 1, ROWS_4, '90%');
				print $doleditor->Create(1);
			} else {
				print dol_htmlentitiesbr($object->signature);
			}
			print '</td></tr>';


			print '</table>';

			print '<hr>';


			print '<table class="border centpercent">';


			// TODO Move this into tab RH (HierarchicalResponsible must be on both tab)

			// Position/Job
			print '<tr><td class="titlefieldcreate">'.$langs->trans("PostOrFunction").'</td>';
			print '<td>';
			if ($caneditfield) {
				print '<input type="text" class="minwidth300 maxwidth500" name="job" value="'.dol_escape_htmltag($object->job).'">';
			} else {
				print '<input type="hidden" name="job" value="'.dol_escape_htmltag($object->job).'">';
				print dol_escape_htmltag($object->job);
			}
			print '</td></tr>';

			// Weeklyhours
			print '<tr><td>'.$langs->trans("WeeklyHours").'</td>';
			print '<td>';
			if ($caneditfield) {
				print '<input size="8" type="text" name="weeklyhours" value="'.price2num(GETPOST('weeklyhours') ? GETPOST('weeklyhours') : $object->weeklyhours).'">';
			} else {
				print price2num($object->weeklyhours);
			}
			print '</td>';
			print "</tr>\n";

			// Sensitive salary/value information
			if ((empty($user->socid) && in_array($id, $childids))	// A user can always see salary/value information for its subordinates
				|| (isModEnabled('salaries') && $user->hasRight("salaries", "readall"))
				|| (isModEnabled('hrm') && $user->hasRight("hrm", "employee", "read"))) {
				$langs->load("salaries");

				// Salary
				print '<tr><td>'.$langs->trans("Salary").'</td>';
				print '<td>';
				print img_picto('', 'salary', 'class="pictofixedwidth paddingright"').'<input size="8" type="text" name="salary" value="'.price2num(GETPOST('salary') ? GETPOST('salary') : $object->salary).'">';
				print '</td>';
				print "</tr>\n";

				// THM
				print '<tr><td>';
				$text = $langs->trans("THM");
				print $form->textwithpicto($text, $langs->trans("THMDescription"), 1, 'help', 'classthm');
				print '</td>';
				print '<td>';
				if ($caneditfield) {
					print '<input size="8" type="text" name="thm" value="'.price2num(GETPOST('thm') ? GETPOST('thm') : $object->thm).'">';
				} else {
					print($object->thm != '' ? price($object->thm, 0, $langs, 1, -1, -1, $conf->currency) : '');
				}
				print '</td>';
				print "</tr>\n";

				// TJM
				print '<tr><td>';
				$text = $langs->trans("TJM");
				print $form->textwithpicto($text, $langs->trans("TJMDescription"), 1, 'help', 'classthm');
				print '</td>';
				print '<td>';
				if ($caneditfield) {
					print '<input size="8" type="text" name="tjm" value="'.price2num(GETPOST('tjm') ? GETPOST('tjm') : $object->tjm).'">';
				} else {
					print($object->tjm != '' ? price($object->tjm, 0, $langs, 1, -1, -1, $conf->currency) : '');
				}
				print '</td>';
				print "</tr>\n";
			}

			// Date employment
			print '<tr><td>'.$langs->trans("DateEmployment").'</td>';
			print '<td>';
			if ($caneditfield) {
				print $form->selectDate($dateemployment ? $dateemployment : $object->dateemployment, 'dateemployment', 0, 0, 1, 'formdateemployment', 1, 1, 0, '', '', '', '', 1, '', $langs->trans("from"));
			} else {
				print dol_print_date($object->dateemployment, 'day');
			}

			if ($dateemployment && $dateemploymentend) {
				print ' - ';
			}

			if ($caneditfield) {
				print $form->selectDate($dateemploymentend ? $dateemploymentend : $object->dateemploymentend, 'dateemploymentend', 0, 0, 1, 'formdateemploymentend', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("to"));
			} else {
				print dol_print_date($object->dateemploymentend, 'day');
			}
			print '</td>';
			print "</tr>\n";

			// Date birth
			print '<tr><td>'.$langs->trans("DateOfBirth").'</td>';
			print '<td>';
			if ($caneditfield) {
				echo $form->selectDate($dateofbirth ? $dateofbirth : $object->birth, 'dateofbirth', 0, 0, 1, 'updateuser', 1, 0, 0, '', '', '', '', 1, '', '', 'tzserver');
			} else {
				print dol_print_date($object->birth, 'day', 'tzserver');
			}
			print '</td>';
			print "</tr>\n";

			print '</table>';

			print dol_get_fiche_end();

			print '<div class="center">';
			print '<input value="'.$langs->trans("Save").'" class="button button-save" type="submit" name="save">';
			print '&nbsp; &nbsp; &nbsp;';
			print '<input value="'.$langs->trans("Cancel").'" class="button button-cancel" type="submit" name="cancel">';
			print '</div>';

			print '</form>';
		}

		if ($action != 'edit' && $action != 'presend') {
			print '<div class="fichecenter"><div class="fichehalfleft">';

			// Generated documents
			$filename = dol_sanitizeFileName($object->ref);
			$filedir = $conf->user->dir_output."/".dol_sanitizeFileName($object->ref);
			$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
			$genallowed = $user->hasRight("user", "user", "read");
			$delallowed = $user->hasRight("user", "user", "write");

			print $formfile->showdocuments('user', $filename, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', 0, '', empty($soc->default_lang) ? '' : $soc->default_lang);
			$somethingshown = $formfile->numoffiles;

			// Show links to link elements
			$linktoelem = $form->showLinkToObjectBlock($object, null, null);
			$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);

			$MAXEVENT = 10;

			$morehtmlcenter = '<div class="nowraponall">';
			$morehtmlcenter .= dolGetButtonTitle($langs->trans('FullConversation'), '', 'fa fa-comments imgforviewmode', DOL_URL_ROOT.'/user/messaging.php?id='.$object->id);
			$morehtmlcenter .= dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', DOL_URL_ROOT.'/user/agenda.php?id='.$object->id);
			$morehtmlcenter .= '</div>';

			print '</div><div class="fichehalfright">';

			// List of actions on element
			include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
			$formactions = new FormActions($db);
			$somethingshown = $formactions->showactions($object, 'user', $socid, 1, 'listactions', $MAXEVENT, '', $morehtmlcenter, $object->id);

			print '</div></div>';
		}

		if (isModEnabled('ldap') && !empty($object->ldap_sid)) {
			$ldap->unbind();
		}
	}
}

// Add button to autosuggest a key
include_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
print dolJSToSetRandomPassword('password', 'generate_password', 0);
if (isModEnabled('api')) {
	print dolJSToSetRandomPassword('api_key', 'generate_api_key', 1);
}

// End of page
llxFooter();
$db->close();
