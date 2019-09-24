<?php
/* Copyright (C) 2002-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2018 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2005      Lionel Cousteix      <etm_ltd@tiscali.co.uk>
 * Copyright (C) 2011      Herve Prot           <herve.prot@symeos.com>
 * Copyright (C) 2012-2018 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2013      Florian Henry        <florian.henry@open-concept.pro>
 * Copyright (C) 2013-2016 Alexandre Spangaro   <aspangaro@open-dsi.fr>
 * Copyright (C) 2015-2017 Jean-François Ferry  <jfefe@aternatik.fr>
 * Copyright (C) 2015      Ari Elbaz (elarifr)  <github@accedinfo.com>
 * Copyright (C) 2015-2018 Charlene Benke       <charlie@patas-monkey.com>
 * Copyright (C) 2016      Raphaël Doursenaud   <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2018-2019  Frédéric France     <frederic.france@netlogic.fr>
 * Copyright (C) 2018       David Beniamine     <David.Beniamine@Tetras-Libre.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/user/card.php
 *       \brief      Tab of user card
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
if (! empty($conf->ldap->enabled)) require_once DOL_DOCUMENT_ROOT.'/core/class/ldap.class.php';
if (! empty($conf->adherent->enabled)) require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
if (! empty($conf->categorie->enabled)) require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
if (! empty($conf->stock->enabled)) require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';

$id			= GETPOST('id', 'int');
$action		= GETPOST('action', 'aZ09');
$mode		= GETPOST('mode', 'alpha');
$confirm	= GETPOST('confirm', 'alpha');
$group		= GETPOST("group", "int", 3);
$cancel		= GETPOST('cancel', 'alpha');
$contextpage= GETPOST('contextpage', 'aZ')?GETPOST('contextpage', 'aZ'):'useracard';   // To manage different context of search

// Define value to know what current user can do on users
$canadduser=(! empty($user->admin) || $user->rights->user->user->creer);
$canreaduser=(! empty($user->admin) || $user->rights->user->user->lire);
$canedituser=(! empty($user->admin) || $user->rights->user->user->creer);
$candisableuser=(! empty($user->admin) || $user->rights->user->user->supprimer);
$canreadgroup=$canreaduser;
$caneditgroup=$canedituser;
if (! empty($conf->global->MAIN_USE_ADVANCED_PERMS))
{
	$canreadgroup=(! empty($user->admin) || $user->rights->user->group_advance->read);
	$caneditgroup=(! empty($user->admin) || $user->rights->user->group_advance->write);
}

// Define value to know what current user can do on properties of edited user
if ($id)
{
	// $user est le user qui edite, $id est l'id de l'utilisateur edite
	$caneditfield=((($user->id == $id) && $user->rights->user->self->creer)
	|| (($user->id != $id) && $user->rights->user->user->creer));
	$caneditpassword=((($user->id == $id) && $user->rights->user->self->password)
	|| (($user->id != $id) && $user->rights->user->user->password));
}

// Security check
$socid=0;
if ($user->societe_id > 0) $socid = $user->societe_id;
$feature2='user';

$result = restrictedArea($user, 'user', $id, 'user&user', $feature2);

if ($user->id <> $id && ! $canreaduser) accessforbidden();

// Load translation files required by page
$langs->loadLangs(array('users', 'companies', 'ldap', 'admin', 'hrm', 'stocks'));

$object = new User($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels=$extrafields->fetch_name_optionals_label($object->table_element);

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('usercard','globalcard'));



/**
 * Actions
 */

$parameters=array('id' => $id, 'socid' => $socid, 'group' => $group, 'caneditgroup' => $caneditgroup);
$reshook=$hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {

	if ($action == 'confirm_disable' && $confirm == "yes" && $candisableuser) {
		if ($id <> $user->id) {
			$object->fetch($id);
			$object->setstatus(0);
			header("Location: ".$_SERVER['PHP_SELF'].'?id='.$id);
			exit;
		}
	}
	if ($action == 'confirm_enable' && $confirm == "yes" && $candisableuser) {
		$error = 0;

		if ($id <> $user->id) {
			$object->fetch($id);

			if (!empty($conf->file->main_limit_users)) {
				$nb = $object->getNbOfUsers("active");
				if ($nb >= $conf->file->main_limit_users) {
					$error ++;
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

	if ($action == 'confirm_delete' && $confirm == "yes" && $candisableuser)
	{
		if ($id <> $user->id)
		{
			if (! GETPOSTISSET('token'))
			{
				print 'Error, token required for this critical operation';
				exit;
			}

			$object = new User($db);
			$object->fetch($id);

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

		if (!$_POST["lastname"]) {
			$error ++;
			setEventMessages($langs->trans("NameNotDefined"), null, 'errors');
			$action = "create";       // Go back to create page
		}
		if (!$_POST["login"]) {
			$error ++;
			setEventMessages($langs->trans("LoginNotDefined"), null, 'errors');
			$action = "create";       // Go back to create page
		}

		if (!empty($conf->file->main_limit_users)) { // If option to limit users is set
			$nb = $object->getNbOfUsers("active");
			if ($nb >= $conf->file->main_limit_users) {
				$error ++;
				setEventMessages($langs->trans("YourQuotaOfUsersIsReached"), null, 'errors');
				$action = "create";       // Go back to create page
			}
		}

		if (!$error) {
			$object->lastname = GETPOST("lastname", 'alphanohtml');
			$object->firstname = GETPOST("firstname", 'alphanohtml');
			$object->login = GETPOST("login", 'alphanohtml');
			$object->api_key = GETPOST("api_key", 'alphanohtml');
			$object->gender = GETPOST("gender", 'aZ09');
			$birth = dol_mktime(0, 0, 0, GETPOST('birthmonth', 'int'), GETPOST('birthday', 'int'), GETPOST('birthyear', 'int'));
			$object->birth = $birth;
			$object->admin = GETPOST("admin", 'int');
			$object->address = GETPOST('address', 'alphanohtml');
			$object->zip = GETPOST('zipcode', 'alphanohtml');
			$object->town = GETPOST('town', 'alphanohtml');
			$object->country_id = GETPOST('country_id', 'int');
			$object->state_id = GETPOST('state_id', 'int');
			$object->office_phone = GETPOST("office_phone", 'alphanohtml');
			$object->office_fax = GETPOST("office_fax", 'alphanohtml');
			$object->user_mobile = GETPOST("user_mobile", 'alphanohtml');

			$object->skype = GETPOST("skype", 'alphanohtml');
			$object->twitter = GETPOST("twitter", 'alphanohtml');
			$object->facebook = GETPOST("facebook", 'alphanohtml');
			$object->linkedin = GETPOST("linkedin", 'alphanohtml');

			$object->email = preg_replace('/\s+/', '', GETPOST("email", 'alphanohtml'));
			$object->job = GETPOST("job", 'nohtml');
			$object->signature = GETPOST("signature", 'none');
			$object->accountancy_code = GETPOST("accountancy_code", 'alphanohtml');
			$object->note = GETPOST("note", 'none');
			$object->ldap_sid = GETPOST("ldap_sid", 'alphanohtml');
			$object->fk_user = GETPOST("fk_user", 'int') > 0 ? GETPOST("fk_user", 'int') : 0;
			$object->employee = GETPOST('employee', 'alphanohtml');

			$object->thm = GETPOST("thm", 'alphanohtml') != '' ? GETPOST("thm", 'alphanohtml') : '';
			$object->tjm = GETPOST("tjm", 'alphanohtml') != '' ? GETPOST("tjm", 'alphanohtml') : '';
			$object->salary = GETPOST("salary", 'alphanohtml') != '' ? GETPOST("salary", 'alphanohtml') : '';
			$object->salaryextra = GETPOST("salaryextra", 'alphanohtml') != '' ? GETPOST("salaryextra", 'alphanohtml') : '';
			$object->weeklyhours = GETPOST("weeklyhours", 'alphanohtml') != '' ? GETPOST("weeklyhours", 'alphanohtml') : '';

			$object->color = GETPOST("color", 'alphanohtml') != '' ? GETPOST("color", 'alphanohtml') : '';
			$dateemployment = dol_mktime(0, 0, 0, GETPOST('dateemploymentmonth', 'int'), GETPOST('dateemploymentday', 'int'), GETPOST('dateemploymentyear', 'int'));
			$object->dateemployment = $dateemployment;

			$dateemploymentend = dol_mktime(0, 0, 0, GETPOST('dateemploymentendmonth', 'int'), GETPOST('dateemploymentendday', 'int'), GETPOST('dateemploymentendyear', 'int'));
			$object->dateemploymentend = $dateemploymentend;

			$object->fk_warehouse = GETPOST('fk_warehouse', 'int');

			// Fill array 'array_options' with data from add form
			$ret = $extrafields->setOptionalsFromPost($extralabels, $object);
			if ($ret < 0) {
				$error ++;
			}

			// Set entity property
			$entity = GETPOST('entity', 'int');
			if (! empty($conf->multicompany->enabled)) {
				if (GETPOST('superadmin', 'int')) {
					$object->entity = 0;
				} else {
					if (! empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)) {
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
				if (GETPOST('password')) {
					$object->setPassword($user, GETPOST('password'));
				}
				if (! empty($conf->categorie->enabled)) {
					// Categories association
					$usercats = GETPOST('usercats', 'array');
					$object->setCategories($usercats);
				}
				$db->commit();

				header("Location: ".$_SERVER['PHP_SELF'].'?id='.$id);
				exit;
			}
			else
			{
				$langs->load("errors");
				$db->rollback();
				setEventMessages($object->error, $object->errors, 'errors');
				$action = "create";       // Go back to create page
			}
		}
	}

	// Action add usergroup
	if (($action == 'addgroup' || $action == 'removegroup') && $caneditgroup)
	{
		if ($group)
		{
			$editgroup = new UserGroup($db);
			$editgroup->fetch($group);
			$editgroup->oldcopy=clone $editgroup;

			$object->fetch($id);
			if ($action == 'addgroup') {
				$result = $object->SetInGroup($group, $editgroup->entity);
			}
			if ($action == 'removegroup') {
				$result = $object->RemoveFromGroup($group, $editgroup->entity);
			}

			if ($result > 0) {
				header("Location: ".$_SERVER['PHP_SELF'].'?id='.$id);
				exit;
			}
			else
			{
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	}

	if ($action == 'update' && ! $cancel)
	{
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		if ($caneditfield)    // Case we can edit all field
		{
			$error = 0;

			if (! GETPOST("lastname", 'alpha')) {
				setEventMessages($langs->trans("NameNotDefined"), null, 'errors');
				$action = "edit";       // Go back to create page
				$error ++;
			}
			if (! GETPOST("login", 'alpha')) {
				setEventMessages($langs->trans("LoginNotDefined"), null, 'errors');
				$action = "edit";       // Go back to create page
				$error ++;
			}

			if (!$error)
			{
				$object->fetch($id);

				$object->oldcopy = clone $object;

				$db->begin();

				$object->lastname = GETPOST("lastname", 'alphanohtml');
				$object->firstname = GETPOST("firstname", 'alphanohtml');
				$object->login = GETPOST("login", 'alphanohtml');
				$object->gender = GETPOST("gender", 'aZ09');
				$birth = dol_mktime(0, 0, 0, GETPOST('birthmonth', 'int'), GETPOST('birthday', 'int'), GETPOST('birthyear', 'int'));
				$object->birth = $birth;
				$object->pass = GETPOST("password", 'none');
				$object->api_key = (GETPOST("api_key", 'alphanohtml')) ? GETPOST("api_key", 'alphanohtml') : $object->api_key;
				if (! empty($user->admin)) $object->admin = GETPOST("admin", "int"); 	// admin flag can only be set/unset by an admin user. A test is also done later when forging sql request
				$object->address = GETPOST('address', 'alphanohtml');
				$object->zip = GETPOST('zipcode', 'alphanohtml');
				$object->town = GETPOST('town', 'alphanohtml');
				$object->country_id = GETPOST('country_id', 'int');
				$object->state_id = GETPOST('state_id', 'int');
				$object->office_phone = GETPOST("office_phone", 'alphanohtml');
				$object->office_fax = GETPOST("office_fax", 'alphanohtml');
				$object->user_mobile = GETPOST("user_mobile", 'alphanohtml');

				$object->skype = GETPOST("skype", 'alphanohtml');
				$object->twitter = GETPOST("twitter", 'alphanohtml');
				$object->facebook = GETPOST("facebook", 'alphanohtml');
				$object->linkedin = GETPOST("linkedin", 'alphanohtml');

				$object->email = preg_replace('/\s+/', '', GETPOST("email", 'alphanohtml'));
				$object->job = GETPOST("job", 'nohtml');
				$object->signature = GETPOST("signature", 'none');
				$object->accountancy_code = GETPOST("accountancy_code", 'alphanohtml');
				$object->openid = GETPOST("openid", 'alphanohtml');
				$object->fk_user = GETPOST("fk_user", 'int') > 0 ? GETPOST("fk_user", 'int') : 0;
				$object->employee = GETPOST('employee', 'int');

				$object->thm = GETPOST("thm", 'alphanohtml') != '' ? GETPOST("thm", 'alphanohtml') : '';
				$object->tjm = GETPOST("tjm", 'alphanohtml') != '' ? GETPOST("tjm", 'alphanohtml') : '';
				$object->salary = GETPOST("salary", 'alphanohtml') != '' ? GETPOST("salary", 'alphanohtml') : '';
				$object->salaryextra = GETPOST("salaryextra", 'alphanohtml') != '' ? GETPOST("salaryextra", 'alphanohtml') : '';
				$object->weeklyhours = GETPOST("weeklyhours", 'alphanohtml') != '' ? GETPOST("weeklyhours", 'alphanohtml') : '';

				$object->color = GETPOST("color", 'alphanohtml') != '' ? GETPOST("color", 'alphanohtml') : '';
				$dateemployment = dol_mktime(0, 0, 0, GETPOST('dateemploymentmonth', 'int'), GETPOST('dateemploymentday', 'int'), GETPOST('dateemploymentyear', 'int'));
				$object->dateemployment = $dateemployment;
				$dateemploymentend = dol_mktime(0, 0, 0, GETPOST('dateemploymentendmonth', 'int'), GETPOST('dateemploymentendday', 'int'), GETPOST('dateemploymentendyear', 'int'));
				$object->dateemploymentend = $dateemploymentend;

                if (! empty($conf->stock->enabled))
                {
				    $object->fk_warehouse = GETPOST('fk_warehouse', 'int');
                }

				if (! empty($conf->multicompany->enabled))
				{
					if (! empty($_POST["superadmin"]))
					{
						$object->entity = 0;
					}
					elseif (! empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE))
					{
						$object->entity = 1; // all users in master entity
					}
					else
					{
						$object->entity = (! GETPOST('entity', 'int') ? 0 : GETPOST('entity', 'int'));
					}
				}
				else
				{
					$object->entity = (! GETPOST('entity', 'int') ? 0 : GETPOST('entity', 'int'));
				}

				// Fill array 'array_options' with data from add form
				$ret = $extrafields->setOptionalsFromPost($extralabels, $object);
				if ($ret < 0) {
					$error ++;
				}

				if (GETPOST('deletephoto')) {
					$object->photo = '';
				}
				if (!empty($_FILES['photo']['name']))
				{
				    $isimage=image_format_supported($_FILES['photo']['name']);
				    if ($isimage > 0)
				    {
    					$object->photo = dol_sanitizeFileName($_FILES['photo']['name']);
				    }
				    else
				    {
				        $error++;
				        $langs->load("errors");
				        setEventMessages($langs->trans("ErrorBadImageFormat"), null, 'errors');
				        dol_syslog($langs->transnoentities("ErrorBadImageFormat"), LOG_INFO);
				    }
				}

				if (!$error) {
					$ret = $object->update($user);
					if ($ret < 0) {
						$error++;
						if ($db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
							$langs->load("errors");
							setEventMessages($langs->trans("ErrorLoginAlreadyExists", $object->login), null, 'errors');
						}
						else
						{
							setEventMessages($object->error, $object->errors, 'errors');
						}
					}
				}

				if (!$error && GETPOSTISSET('contactid')) {
					$contactid = GETPOST('contactid', 'int');

					if ($contactid > 0) {
						$contact = new Contact($db);
						$contact->fetch($contactid);

						$sql = "UPDATE ".MAIN_DB_PREFIX."user";
						$sql .= " SET fk_socpeople=".$db->escape($contactid);
						if (!empty($contact->socid)) {
							$sql .= ", fk_soc=".$db->escape($contact->socid);
						}
						$sql .= " WHERE rowid=".$object->id;
					} else {
						$sql = "UPDATE ".MAIN_DB_PREFIX."user";
						$sql .= " SET fk_socpeople=NULL, fk_soc=NULL";
						$sql .= " WHERE rowid=".$object->id;
					}
					dol_syslog("usercard::update", LOG_DEBUG);
					$resql = $db->query($sql);
					if (!$resql) {
						$error ++;
						setEventMessages($db->lasterror(), null, 'errors');
					}
				}

				if (!$error && !count($object->errors)) {
					if (GETPOST('deletephoto') && $object->photo) {
						$fileimg = $conf->user->dir_output.'/'.get_exdir(0, 0, 0, 0, $object, 'user').'/'.$object->id.'/logos/'.$object->photo;
						$dirthumbs = $conf->user->dir_output.'/'.get_exdir(0, 0, 0, 0, $object, 'user').'/'.$object->id.'/logos/thumbs';
						dol_delete_file($fileimg);
						dol_delete_dir_recursive($dirthumbs);
					}

					if (isset($_FILES['photo']['tmp_name']) && trim($_FILES['photo']['tmp_name'])) {
						$dir = $conf->user->dir_output.'/'.get_exdir(0, 0, 0, 0, $object, 'user').'/'.$object->id;

						dol_mkdir($dir);

						if (@is_dir($dir)) {
							$newfile = $dir.'/'.dol_sanitizeFileName($_FILES['photo']['name']);
							$result = dol_move_uploaded_file($_FILES['photo']['tmp_name'], $newfile, 1, 0, $_FILES['photo']['error']);

							if (!$result > 0) {
								setEventMessages($langs->trans("ErrorFailedToSaveFile"), null, 'errors');
							} else {
								// Create thumbs
								$object->addThumbs($newfile);
							}
						} else {
							$error ++;
							$langs->load("errors");
							setEventMessages($langs->trans("ErrorFailedToCreateDir", $dir), $mesgs, 'errors');
						}
					}
				}

				if (! $error && ! count($object->errors))
				{
					// Then we add the associated categories
					$categories = GETPOST('usercats', 'array');
					$object->setCategories($categories);
				}

				if (!$error && !count($object->errors)) {
					setEventMessages($langs->trans("UserModified"), null, 'mesgs');
					$db->commit();

					$login = $_SESSION["dol_login"];
					if ($login && $login == $object->oldcopy->login && $object->oldcopy->login != $object->login)    // Current user has changed its login
					{
						$error++;
						$langs->load("errors");
						setEventMessages($langs->transnoentitiesnoconv("WarningYourLoginWasModifiedPleaseLogin"), null, 'warnings');
					}
				}
				else {
					$db->rollback();
				}
			}
		}
		else
		{
			if ($caneditpassword)    // Case we can edit only password
			{
				dol_syslog("Not allowed to change fields, only password");

				$object->fetch($id);

				$object->oldcopy = clone $object;

				$ret = $object->setPassword($user, GETPOST("password"));
				if ($ret < 0)
				{
					setEventMessages($object->error, $object->errors, 'errors');
				}
			}
		}
	}

	// Change password with a new generated one
	if ((($action == 'confirm_password' && $confirm == 'yes')
			|| ($action == 'confirm_passwordsend' && $confirm == 'yes')) && $caneditpassword
	) {
		$object->fetch($id);

		$newpassword = $object->setPassword($user, '');
		if ($newpassword < 0) {
			// Echec
			setEventMessages($langs->trans("ErrorFailedToSetNewPassword"), null, 'errors');
		} else {
			// Succes
			if ($action == 'confirm_passwordsend' && $confirm == 'yes') {
				if ($object->send_password($user, $newpassword) > 0)
				{
					setEventMessages($langs->trans("PasswordChangedAndSentTo", $object->email), null, 'mesgs');
				}
				else
				{
					setEventMessages($object->error, $object->errors, 'errors');
				}
			}
			else
			{
				setEventMessages($langs->trans("PasswordChangedTo", $newpassword), null, 'warnings');
			}
		}
	}

	// Action initialisation donnees depuis record LDAP
	if ($action == 'adduserldap') {
		$selecteduser = $_POST['users'];

		$required_fields = array(
			$conf->global->LDAP_KEY_USERS,
			$conf->global->LDAP_FIELD_NAME,
			$conf->global->LDAP_FIELD_FIRSTNAME,
			$conf->global->LDAP_FIELD_LOGIN,
			$conf->global->LDAP_FIELD_LOGIN_SAMBA,
			$conf->global->LDAP_FIELD_PASSWORD,
			$conf->global->LDAP_FIELD_PASSWORD_CRYPTED,
			$conf->global->LDAP_FIELD_PHONE,
			$conf->global->LDAP_FIELD_FAX,
			$conf->global->LDAP_FIELD_MOBILE,
			$conf->global->LDAP_FIELD_SKYPE,
			$conf->global->LDAP_FIELD_MAIL,
			$conf->global->LDAP_FIELD_TITLE,
			$conf->global->LDAP_FIELD_DESCRIPTION,
			$conf->global->LDAP_FIELD_SID
		);

		$ldap = new Ldap();
		$result = $ldap->connect_bind();
		if ($result >= 0) {
			// Remove from required_fields all entries not configured in LDAP (empty) and duplicated
			$required_fields = array_unique(array_values(array_filter($required_fields, "dol_validElement")));

			$ldapusers = $ldap->getRecords($selecteduser, $conf->global->LDAP_USER_DN, $conf->global->LDAP_KEY_USERS, $required_fields);
			//print_r($ldapusers);

			if (is_array($ldapusers)) {
				foreach ($ldapusers as $key => $attribute) {
					$ldap_lastname = $attribute[$conf->global->LDAP_FIELD_NAME];
					$ldap_firstname = $attribute[$conf->global->LDAP_FIELD_FIRSTNAME];
					$ldap_login = $attribute[$conf->global->LDAP_FIELD_LOGIN];
					$ldap_loginsmb = $attribute[$conf->global->LDAP_FIELD_LOGIN_SAMBA];
					$ldap_pass = $attribute[$conf->global->LDAP_FIELD_PASSWORD];
					$ldap_pass_crypted = $attribute[$conf->global->LDAP_FIELD_PASSWORD_CRYPTED];
					$ldap_phone = $attribute[$conf->global->LDAP_FIELD_PHONE];
					$ldap_fax = $attribute[$conf->global->LDAP_FIELD_FAX];
					$ldap_mobile = $attribute[$conf->global->LDAP_FIELD_MOBILE];
					$ldap_skype = $attribute[$conf->global->LDAP_FIELD_SKYPE];
					$ldap_twitter = $attribute[$conf->global->LDAP_FIELD_TWITTER];
					$ldap_facebook = $attribute[$conf->global->LDAP_FIELD_FACEBOOK];
					$ldap_linkedin = $attribute[$conf->global->LDAP_FIELD_LINKEDIN];
					$ldap_mail = $attribute[$conf->global->LDAP_FIELD_MAIL];
					$ldap_sid = $attribute[$conf->global->LDAP_FIELD_SID];
				}
			}
		}
		else
		{
			setEventMessages($ldap->error, $ldap->errors, 'errors');
		}
	}

	// Actions to send emails
	$trigger_name='USER_SENTBYMAIL';
	$paramname='id';    // Name of param key to open the card
	$mode='emailfromuser';
	$trackid='use'.$id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';

	// Actions to build doc
	$upload_dir = $conf->user->dir_output;
	$permissioncreate=$user->rights->user->user->creer;
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';
}


/*
 * View
 */

$form = new Form($db);
$formother=new FormOther($db);
$formcompany = new FormCompany($db);
$formfile = new FormFile($db);
if (! empty($conf->stock->enabled)) $formproduct = new FormProduct($db);

llxHeader('', $langs->trans("UserCard"));

if ($action == 'create' || $action == 'adduserldap')
{
	/* ************************************************************************** */
	/*                                                                            */
	/* Affichage fiche en mode creation                                           */
	/*                                                                            */
	/* ************************************************************************** */

	print load_fiche_titre($langs->trans("NewUser"));

	print $langs->trans("CreateInternalUserDesc")."<br>\n";
	print "<br>";


	if (! empty($conf->ldap->enabled) && (isset($conf->global->LDAP_SYNCHRO_ACTIVE) && $conf->global->LDAP_SYNCHRO_ACTIVE == 'ldap2dolibarr'))
	{
		/*
         * Affiche formulaire d'ajout d'un compte depuis LDAP
         * si on est en synchro LDAP vers Dolibarr
         */

		$ldap = new Ldap();
		$result = $ldap->connect_bind();
		if ($result >= 0)
		{
			$required_fields=array(
				$conf->global->LDAP_KEY_USERS,
				$conf->global->LDAP_FIELD_FULLNAME,
				$conf->global->LDAP_FIELD_NAME,
				$conf->global->LDAP_FIELD_FIRSTNAME,
				$conf->global->LDAP_FIELD_LOGIN,
				$conf->global->LDAP_FIELD_LOGIN_SAMBA,
				$conf->global->LDAP_FIELD_PASSWORD,
				$conf->global->LDAP_FIELD_PASSWORD_CRYPTED,
				$conf->global->LDAP_FIELD_PHONE,
				$conf->global->LDAP_FIELD_FAX,
				$conf->global->LDAP_FIELD_MOBILE,
				$conf->global->LDAP_FIELD_SKYPE,
				$conf->global->LDAP_FIELD_MAIL,
				$conf->global->LDAP_FIELD_TITLE,
				$conf->global->LDAP_FIELD_DESCRIPTION,
				$conf->global->LDAP_FIELD_SID
			);

			// Remove from required_fields all entries not configured in LDAP (empty) and duplicated
			$required_fields=array_unique(array_values(array_filter($required_fields, "dol_validElement")));

			// Get from LDAP database an array of results
			$ldapusers = $ldap->getRecords('*', $conf->global->LDAP_USER_DN, $conf->global->LDAP_KEY_USERS, $required_fields, 1);

			if (is_array($ldapusers))
			{
				$liste=array();
				foreach ($ldapusers as $key => $ldapuser)
				{
					// Define the label string for this user
					$label='';
					foreach ($required_fields as $value)
					{
						if ($value)
						{
							$label.=$value."=".$ldapuser[$value]." ";
						}
					}
					$liste[$key] = $label;
				}
			}
			else
			{
				setEventMessages($ldap->error, $ldap->errors, 'errors');
			}
		}
		else
		{
			setEventMessages($ldap->error, $ldap->errors, 'errors');
		}

		// If user list is full, we show drop-down list
	   	print "\n\n<!-- Form liste LDAP debut -->\n";

	   	print '<form name="add_user_ldap" action="'.$_SERVER["PHP_SELF"].'" method="post">';
	   	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	   	print '<table class="border centpercent"><tr>';
	   	print '<td width="160">';
	   	print $langs->trans("LDAPUsers");
	   	print '</td>';
	   	print '<td>';
	   	print '<input type="hidden" name="action" value="adduserldap">';
		if (is_array($liste) && count($liste))
		{
			print $form->selectarray('users', $liste, '', 1);
			print ajax_combobox('users');
		}
	   	print '</td><td class="center">';
	   	print '<input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans('Get')).'"'.(count($liste)?'':' disabled').'>';
	   	print '</td></tr></table>';
	   	print '</form>';

	   	print "\n<!-- Form liste LDAP fin -->\n\n";
	   	print '<br>';
	}


	print '<form action="'.$_SERVER['PHP_SELF'].'" method="POST" name="createuser">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add">';
	if (! empty($ldap_sid)) print '<input type="hidden" name="ldap_sid" value="'.dol_escape_htmltag($ldap_sid).'">';
	print '<input type="hidden" name="entity" value="'.$conf->entity.'">';

	dol_fiche_head('', '', '', 0, '');

	print dol_set_focus('#lastname');

	print '<table class="border centpercent">';

	// Lastname
	print '<tr>';
	print '<td class="titlefieldcreate"><span class="fieldrequired">'.$langs->trans("Lastname").'</span></td>';
	print '<td>';
	if (! empty($ldap_lastname))
	{
		print '<input type="hidden" id="lastname" name="lastname" value="'.$ldap_lastname.'">';
		print $ldap_lastname;
	}
	else
	{
		print '<input class="minwidth100" type="text" id="lastname" name="lastname" value="'.GETPOST('lastname').'">';
	}
	print '</td></tr>';

	// Firstname
	print '<tr><td>'.$langs->trans("Firstname").'</td>';
	print '<td>';
	if (! empty($ldap_firstname))
	{
		print '<input type="hidden" name="firstname" value="'.$ldap_firstname.'">';
		print $ldap_firstname;
	}
	else
	{
		print '<input class="minwidth100" type="text" name="firstname" value="'.GETPOST('firstname').'">';
	}
	print '</td></tr>';

	// Login
	print '<tr><td><span class="fieldrequired">'.$langs->trans("Login").'</span></td>';
	print '<td>';
	if (! empty($ldap_login))
	{
		print '<input type="hidden" name="login" value="'.$ldap_login.'">';
		print $ldap_login;
	}
	elseif (! empty($ldap_loginsmb))
	{
		print '<input type="hidden" name="login" value="'.$ldap_loginsmb.'">';
		print $ldap_loginsmb;
	}
	else
	{
		print '<input class="maxwidth200" maxsize="24" type="text" name="login" value="'.dol_escape_htmltag(GETPOST('login', 'alpha')).'">';
	}
	print '</td></tr>';

	$generated_password='';
	if (empty($ldap_sid))    // ldap_sid is for activedirectory
	{
		require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
		$generated_password=getRandomPassword(false);
	}
	$password=$generated_password;

	// Password
	print '<tr><td class="fieldrequired">'.$langs->trans("Password").'</td>';
	print '<td>';
	$valuetoshow='';
	if (preg_match('/ldap/', $dolibarr_main_authentication))
	{
		$valuetoshow.=($valuetoshow?', ':'').$langs->trans("PasswordOfUserInLDAP");
	}
	if (preg_match('/http/', $dolibarr_main_authentication))
	{
		$valuetoshow.=($valuetoshow?', ':'').$langs->trans("HTTPBasicPassword");
	}
	if (preg_match('/dolibarr/', $dolibarr_main_authentication))
	{
		if (! empty($ldap_pass))	// For very old system comaptibilty. Now clear password can't be viewed from LDAP read
		{
			$valuetoshow.= ($valuetoshow?', ':'').'<input type="hidden" name="password" value="'.$ldap_pass.'">';	// Dolibarr password is preffiled with LDAP known password
			$valuetoshow.= preg_replace('/./i', '*', $ldap_pass);
		}
		else
		{
			// We do not use a field password but a field text to show new password to use.
			$valuetoshow.= ($valuetoshow?', ':'').'<input size="30" maxsize="32" type="text" name="password" value="'.$password.'" autocomplete="new-password">';
		}
	}

	// Other form for user password
	$parameters=array('valuetoshow' => $valuetoshow, 'password' => $password);
	$reshook=$hookmanager->executeHooks('printUserPasswordField', $parameters, $object, $action);    // Note that $action and $object may have been modified by hook
	if ($reshook > 0) $valuetoshow=$hookmanager->resPrint;	// to replace
	else $valuetoshow.=$hookmanager->resPrint;				// to add

	print $valuetoshow;
	print '</td></tr>';

	if (! empty($conf->api->enabled))
	{
		// API key
		$generated_api_key = '';
		require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
			$generated_password=getRandomPassword(false);
		print '<tr><td>'.$langs->trans("ApiKey").'</td>';
		print '<td>';
		print '<input size="30" maxsize="32" type="text" id="api_key" name="api_key" value="'.$api_key.'" autocomplete="off">';
		if (! empty($conf->use_javascript_ajax))
			print '&nbsp;'.img_picto($langs->trans('Generate'), 'refresh', 'id="generate_api_key" class="linkobject"');
		print '</td></tr>';
	}
	else
	{
		require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
		// PARTIAL WORKAROUND
		$generated_fake_api_key=getRandomPassword(false);
		print '<input type="hidden" name="api_key" value="'.$generated_fake_api_key.'">';
	}

	// Administrator
	if (! empty($user->admin))
	{
		print '<tr><td>'.$langs->trans("Administrator").'</td>';
		print '<td>';
		print $form->selectyesno('admin', GETPOST('admin'), 1);

		if (! empty($conf->multicompany->enabled) && ! $user->entity)
		{
			if (! empty($conf->use_javascript_ajax))
			{
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
			$checked=(GETPOST('superadmin', 'int')?' checked':'');
			$disabled=(GETPOST('superadmin', 'int')?'':' disabled');
			print '<input type="checkbox" name="superadmin" value="1"'.$checked.$disabled.' /> '.$langs->trans("SuperAdministrator");
		}
		print "</td></tr>\n";
	}

	// Type
	print '<tr><td>'.$langs->trans("Type").'</td>';
	print '<td>';
	print $form->textwithpicto($langs->trans("Internal"), $langs->trans("InternalExternalDesc"), 1, 'help', '', 0, 2);
	print '</td></tr>';

	// Gender
	print '<tr><td>'.$langs->trans("Gender").'</td>';
	print '<td>';
	$arraygender=array('man'=>$langs->trans("Genderman"),'woman'=>$langs->trans("Genderwoman"));
	print $form->selectarray('gender', $arraygender, GETPOST('gender'), 1);
	print '</td></tr>';

	// Employee
	$defaultemployee=1;
	print '<tr>';
	print '<td>'.$langs->trans('Employee').'</td><td>';
	print $form->selectyesno("employee", (GETPOST('employee')!=''?GETPOST('employee'):$defaultemployee), 1);
	print '</td></tr>';

	// Hierarchy
	print '<tr><td class="titlefieldcreate">'.$langs->trans("HierarchicalResponsible").'</td>';
	print '<td>';
	print $form->select_dolusers($object->fk_user, 'fk_user', 1, array($object->id), 0, '', 0, $conf->entity, 0, 0, '', 0, '', 'maxwidth300');
	print '</td>';
	print "</tr>\n";


	print '</table><hr><table class="border centpercent">';


	// Address
	print '<tr><td class="tdtop titlefieldcreate">'.$form->editfieldkey('Address', 'address', '', $object, 0).'</td>';
	print '<td><textarea name="address" id="address" class="quatrevingtpercent" rows="3" wrap="soft">';
	print $object->address;
	print '</textarea></td></tr>';

	// Zip
	print '<tr><td>'.$form->editfieldkey('Zip', 'zipcode', '', $object, 0).'</td><td>';
	print $formcompany->select_ziptown($object->zip, 'zipcode', array('town','selectcountry_id','state_id'), 6);
	print '</td></tr>';

	// Town
	print '<tr><td>'.$form->editfieldkey('Town', 'town', '', $object, 0).'</td><td>';
	print $formcompany->select_ziptown($object->town, 'town', array('zipcode','selectcountry_id','state_id'));
	print '</td></tr>';

	// Country
	print '<tr><td>'.$form->editfieldkey('Country', 'selectcountry_id', '', $object, 0).'</td><td class="maxwidthonsmartphone">';
	print $form->select_country((GETPOST('country_id')!=''?GETPOST('country_id'):$object->country_id));
	if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
	print '</td></tr>';

	// State
	if (empty($conf->global->USER_DISABLE_STATE))
	{
		print '<tr><td>'.$form->editfieldkey('State', 'state_id', '', $object, 0).'</td><td class="maxwidthonsmartphone">';
		print $formcompany->select_state($object->state_id, $object->country_code, 'state_id');
		print '</td></tr>';
	}

	// Tel
	print '<tr><td>'.$langs->trans("PhonePro").'</td>';
	print '<td>';
	if (! empty($ldap_phone))
	{
		print '<input type="hidden" name="office_phone" value="'.$ldap_phone.'">';
		print $ldap_phone;
	}
	else
	{
		print '<input size="20" type="text" name="office_phone" value="'.GETPOST('office_phone').'">';
	}
	print '</td></tr>';

	// Tel portable
	print '<tr><td>'.$langs->trans("PhoneMobile").'</td>';
	print '<td>';
	if (! empty($ldap_mobile))
	{
		print '<input type="hidden" name="user_mobile" value="'.$ldap_mobile.'">';
		print $ldap_mobile;
	}
	else
	{
		print '<input size="20" type="text" name="user_mobile" value="'.GETPOST('user_mobile').'">';
	}
	print '</td></tr>';

	// Fax
	print '<tr><td>'.$langs->trans("Fax").'</td>';
	print '<td>';
	if (! empty($ldap_fax))
	{
		print '<input type="hidden" name="office_fax" value="'.$ldap_fax.'">';
		print $ldap_fax;
	}
	else
	{
		print '<input size="20" type="text" name="office_fax" value="'.GETPOST('office_fax').'">';
	}
	print '</td></tr>';

	// Skype
	if (! empty($conf->socialnetworks->enabled))
	{
		print '<tr><td>'.$langs->trans("Skype").'</td>';
		print '<td>';
		if (! empty($ldap_skype))
		{
			print '<input type="hidden" name="skype" value="'.$ldap_skype.'">';
			print $ldap_skype;
		}
		else
		{
			print '<input class="maxwidth200" type="text" name="skype" value="'.GETPOST('skype', 'alpha').'">';
		}
		print '</td></tr>';
	}

	// Twitter
	if (! empty($conf->socialnetworks->enabled))
	{
		print '<tr><td>'.$langs->trans("Twitter").'</td>';
		print '<td>';
		if (! empty($ldap_twitter))
		{
			print '<input type="hidden" name="twitter" value="'.$ldap_twitter.'">';
			print $ldap_twitter;
		}
		else
		{
			print '<input class="maxwidth200" type="text" name="twitter" value="'.GETPOST('twitter', 'alpha').'">';
		}
		print '</td></tr>';
	}

	// Facebook
	if (! empty($conf->socialnetworks->enabled))
	{
		print '<tr><td>'.$langs->trans("Facebook").'</td>';
		print '<td>';
		if (! empty($ldap_facebook))
		{
			print '<input type="hidden" name="facebook" value="'.$ldap_facebook.'">';
			print $ldap_facebook;
		}
		else
		{
			print '<input class="maxwidth200" type="text" name="facebook" value="'.GETPOST('facebook', 'alpha').'">';
		}
		print '</td></tr>';
	}

    // LinkedIn
    if (! empty($conf->socialnetworks->enabled))
    {
        print '<tr><td>'.$langs->trans("LinkedIn").'</td>';
        print '<td>';
        if (! empty($ldap_linkedin))
        {
            print '<input type="hidden" name="linkedin" value="'.$ldap_linkedin.'">';
            print $ldap_linkedin;
        }
        else
        {
            print '<input class="maxwidth200" type="text" name="linkedin" value="'.GETPOST('linkedin', 'alpha').'">';
        }
        print '</td></tr>';
    }

	// EMail
	print '<tr><td'.(! empty($conf->global->USER_MAIL_REQUIRED)?' class="fieldrequired"':'').'>'.$langs->trans("EMail").'</td>';
	print '<td>';
	if (! empty($ldap_mail))
	{
		print '<input type="hidden" name="email" value="'.$ldap_mail.'">';
		print $ldap_mail;
	}
	else
	{
		print '<input size="40" type="text" name="email" value="'.GETPOST('email').'">';
	}
	print '</td></tr>';

	// Accountancy code
	if ($conf->accounting->enabled)
	{
		print '<tr><td>'.$langs->trans("AccountancyCode").'</td>';
		print '<td>';
		print '<input size="30" type="text" name="accountancy_code" value="'.GETPOST('accountancy_code').'">';
		print '</td></tr>';
	}

	// User color
	if (! empty($conf->agenda->enabled))
	{
		print '<tr><td>'.$langs->trans("ColorUser").'</td>';
		print '<td>';
		print $formother->selectColor(GETPOST('color')?GETPOST('color'):$object->color, 'color', null, 1, '', 'hideifnotset');
		print '</td></tr>';
	}

	// Categories
	if (! empty($conf->categorie->enabled)  && ! empty($user->rights->categorie->lire))
	{
		print '<tr><td>' . $form->editfieldkey('Categories', 'usercats', '', $object, 0) . '</td><td colspan="3">';
		$cate_arbo = $form->select_all_categories('user', null, 'parent', null, null, 1);
        print $form->multiselectarray('usercats', $cate_arbo, GETPOST('usercats', 'array'), null, null, null, null, '90%');
		print "</td></tr>";
	}

	// Multicompany
	if (! empty($conf->multicompany->enabled) && is_object($mc))
	{
		// This is now done with hook formObjectOptions. Keep this code for backward compatibility with old multicompany module
		if (! method_exists($mc, 'formObjectOptions'))
		{
			if (empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE) && $conf->entity == 1 && $user->admin && ! $user->entity)	// condition must be same for create and edit mode
			{
				 print "<tr>".'<td>'.$langs->trans("Entity").'</td>';
				 print "<td>".$mc->select_entities($conf->entity);
				 print "</td></tr>\n";
			}
			else
			{
				 print '<input type="hidden" name="entity" value="'.$conf->entity.'" />';
			}
		}
	}

	// Other attributes
	$parameters=array('objectsrc' => $objectsrc, 'colspan' => ' colspan="3"');
	$reshook=$hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	if (empty($reshook))
	{
		print $object->showOptionals($extrafields, 'edit');
	}

	// Note
	print '<tr><td class="tdtop">';
	print $langs->trans("Note");
	print '</td><td>';
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$doleditor=new DolEditor('note', '', '', 120, 'dolibarr_notes', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_3, '90%');
	$doleditor->Create();
	print "</td></tr>\n";

	// Signature
	print '<tr><td class="tdtop">'.$langs->trans("Signature").'</td>';
	print '<td>';
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$doleditor=new DolEditor('signature', GETPOST('signature'), '', 138, 'dolibarr_notes', 'In', true, true, empty($conf->global->FCKEDITOR_ENABLE_USERSIGN)?0:1, ROWS_4, '90%');
	print $doleditor->Create(1);
	print '</td></tr>';


	print '</table><hr><table class="border centpercent">';


	// TODO Move this into tab RH (HierarchicalResponsible must be on both tab)

	// Position/Job
	print '<tr><td class="titlefieldcreate">'.$langs->trans("PostOrFunction").'</td>';
	print '<td>';
	print '<input class="maxwidth200" type="text" name="job" value="'.GETPOST('job', 'nohtml').'">';
	print '</td></tr>';

	// Default warehouse
    if (! empty($conf->stock->enabled))
    {
		print '<tr><td>'.$langs->trans("DefaultWarehouse").'</td><td>';
		print $formproduct->selectWarehouses($object->fk_warehouse, 'fk_warehouse', 'warehouseopen', 1);
		print '</td></tr>';
	}

	if ((! empty($conf->salaries->enabled) && ! empty($user->rights->salaries->read))
		|| (! empty($conf->hrm->enabled) && ! empty($user->rights->hrm->employee->read)))
	{
		$langs->load("salaries");

		// THM
		print '<tr><td>';
		$text=$langs->trans("THM");
		print $form->textwithpicto($text, $langs->trans("THMDescription"), 1, 'help', 'classthm');
		print '</td>';
		print '<td>';
		print '<input size="8" type="text" name="thm" value="'.GETPOST('thm').'">';
		print '</td>';
		print "</tr>\n";

		// TJM
		print '<tr><td>';
		$text=$langs->trans("TJM");
		print $form->textwithpicto($text, $langs->trans("TJMDescription"), 1, 'help', 'classtjm');
		print '</td>';
		print '<td>';
		print '<input size="8" type="text" name="tjm" value="'.GETPOST('tjm').'">';
		print '</td>';
		print "</tr>\n";

		// Salary
		print '<tr><td>'.$langs->trans("Salary").'</td>';
		print '<td>';
		print '<input size="8" type="text" name="salary" value="'.GETPOST('salary').'">';
		print '</td>';
		print "</tr>\n";
	}

	// Weeklyhours
	print '<tr><td>'.$langs->trans("WeeklyHours").'</td>';
	print '<td>';
	print '<input size="8" type="text" name="weeklyhours" value="'.GETPOST('weeklyhours').'">';
	print '</td>';
	print "</tr>\n";

	// Date employment
	print '<tr><td>'.$langs->trans("DateEmployment").'</td>';
	print '<td>';
	print $form->selectDate(GETPOST('dateemployment'), 'dateemployment', 0, 0, 1, 'formdateemployment', 1, 0);
	print '</td>';
	print "</tr>\n";

	// Date employment END
	print '<tr><td>'.$langs->trans("DateEmploymentEnd").'</td>';
	print '<td>';
	print $form->selectDate(GETPOST('dateemploymentend'), 'dateemploymentend', 0, 0, 1, 'formdateemploymentend', 1, 0);
	print '</td>';
	print "</tr>\n";

	// Date birth
	print '<tr><td>'.$langs->trans("DateToBirth").'</td>';
	print '<td>';
	print $form->selectDate(GETPOST('birth'), 'birth', 0, 0, 1, 'createuser', 1, 0);
	print '</td>';
	print "</tr>\n";

	print "</table>\n";

 	dol_fiche_end();

	print '<div class="center">';
	print '<input class="button" value="'.$langs->trans("CreateUser").'" name="create" type="submit">';
	//print '&nbsp; &nbsp; &nbsp;';
	//print '<input value="'.$langs->trans("Cancel").'" class="button" type="submit" name="cancel">';
	print '</div>';

	print "</form>";
}
else
{
	/* ************************************************************************** */
	/*                                                                            */
	/* View and edition                                                            */
	/*                                                                            */
	/* ************************************************************************** */

	if ($id > 0)
	{
		$object->fetch($id, '', '', 1);
		if ($res < 0) { dol_print_error($db, $object->error); exit; }
		$res=$object->fetch_optionals();

		// Check if user has rights
		if (empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE))
		{
			$object->getrights();
			if (empty($object->nb_rights) && $object->statut != 0 && empty($object->admin)) setEventMessages($langs->trans('UserHasNoPermissions'), null, 'warnings');
		}

		// Connexion ldap
		// pour recuperer passDoNotExpire et userChangePassNextLogon
		if (! empty($conf->ldap->enabled) && ! empty($object->ldap_sid))
		{
			$ldap = new Ldap();
			$result=$ldap->connect_bind();
			if ($result > 0)
			{
				$userSearchFilter = '('.$conf->global->LDAP_FILTER_CONNECTION.'('.$ldap->getUserIdentifier().'='.$object->login.'))';
				$entries = $ldap->fetch($object->login, $userSearchFilter);
				if (! $entries)
				{
					setEventMessages($ldap->error, $ldap->errors, 'errors');
				}

				$passDoNotExpire = 0;
				$userChangePassNextLogon = 0;
				$userDisabled = 0;
				$statutUACF = '';

				// Check options of user account
				if (count($ldap->uacf) > 0)
				{
					foreach ($ldap->uacf as $key => $statut)
					{
						if ($key == 65536)
						{
							$passDoNotExpire = 1;
							$statutUACF = $statut;
						}
					}
				}
				else
				{
					$userDisabled = 1;
					$statutUACF = "ACCOUNTDISABLE";
				}

				if ($ldap->pwdlastset == 0)
				{
					$userChangePassNextLogon = 1;
				}
			}
		}

		// Show tabs
		if ($mode == 'employee') // For HRM module development
		{
			$title = $langs->trans("Employee");
			$linkback = '<a href="'.DOL_URL_ROOT.'/hrm/employee/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
		}
		else
		{
			$title = $langs->trans("User");
			$linkback = '';

			if ($user->rights->user->user->lire || $user->admin) {
				$linkback = '<a href="'.DOL_URL_ROOT.'/user/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
			}
		}

		$head = user_prepare_head($object);

		/*
         * Confirmation reinitialisation mot de passe
         */
		if ($action == 'password')
		{
			print $form->formconfirm($_SERVER['PHP_SELF']."?id=$object->id", $langs->trans("ReinitPassword"), $langs->trans("ConfirmReinitPassword", $object->login), "confirm_password", '', 0, 1);
		}

		/*
         * Confirmation envoi mot de passe
         */
		if ($action == 'passwordsend')
		{
			print $form->formconfirm($_SERVER['PHP_SELF']."?id=$object->id", $langs->trans("SendNewPassword"), $langs->trans("ConfirmSendNewPassword", $object->login), "confirm_passwordsend", '', 0, 1);
		}

		/*
         * Confirm deactivation
         */
		if ($action == 'disable')
		{
			print $form->formconfirm($_SERVER['PHP_SELF']."?id=$object->id", $langs->trans("DisableAUser"), $langs->trans("ConfirmDisableUser", $object->login), "confirm_disable", '', 0, 1);
		}

		/*
         * Confirm activation
         */
		if ($action == 'enable')
		{
			print $form->formconfirm($_SERVER['PHP_SELF']."?id=$object->id", $langs->trans("EnableAUser"), $langs->trans("ConfirmEnableUser", $object->login), "confirm_enable", '', 0, 1);
		}

		/*
         * Confirmation suppression
         */
		if ($action == 'delete')
		{
			print $form->formconfirm($_SERVER['PHP_SELF']."?id=$object->id", $langs->trans("DeleteAUser"), $langs->trans("ConfirmDeleteUser", $object->login), "confirm_delete", '', 0, 1);
		}

		/*
         * Fiche en mode visu
         */
		if ($action != 'edit')
		{
			dol_fiche_head($head, 'user', $title, -1, 'user');

			dol_banner_tab($object, 'id', $linkback, $user->rights->user->user->lire || $user->admin);

			print '<div class="fichecenter">';
			print '<div class="fichehalfleft">';

			print '<div class="underbanner clearboth"></div>';
			print '<table class="border tableforfield" width="100%">';

			// Login
			print '<tr><td class="titlefield">'.$langs->trans("Login").'</td>';
			if (! empty($object->ldap_sid) && $object->statut==0)
			{
				print '<td class="error">'.$langs->trans("LoginAccountDisableInDolibarr").'</td>';
			}
			else
			{
				print '<td>'.$object->login.'</td>';
			}
			print '</tr>'."\n";

			// Password
			print '<tr><td>'.$langs->trans("Password").'</td>';

			print '<td class="wordbreak">';
			$valuetoshow='';
			if (preg_match('/ldap/', $dolibarr_main_authentication))
			{
				if (! empty($object->ldap_sid))
				{
					if ($passDoNotExpire)
					{
						$valuetoshow.=($valuetoshow?(' '.$langs->trans("or").' '):'').$langs->trans("LdapUacf_".$statutUACF);
					}
					elseif($userChangePassNextLogon)
					{
						$valuetoshow.=($valuetoshow?(' '.$langs->trans("or").' '):'').'<span class="warning">'.$langs->trans("UserMustChangePassNextLogon", $ldap->domainFQDN).'</span>';
					}
					elseif($userDisabled)
					{
						$valuetoshow.=($valuetoshow?(' '.$langs->trans("or").' '):'').'<span class="warning">'.$langs->trans("LdapUacf_".$statutUACF, $ldap->domainFQDN).'</span>';
					}
					else
					{
						$valuetoshow.=($valuetoshow?(' '.$langs->trans("or").' '):'').$langs->trans("PasswordOfUserInLDAP");
					}
				}
				else
				{
					$valuetoshow.=($valuetoshow?(' '.$langs->trans("or").' '):'').$langs->trans("PasswordOfUserInLDAP");
				}
			}
			if (preg_match('/http/', $dolibarr_main_authentication))
			{
				$valuetoshow.=($valuetoshow?(' '.$langs->trans("or").' '):'').$langs->trans("HTTPBasicPassword");
			}
			if (preg_match('/dolibarr/', $dolibarr_main_authentication))
			{
				if ($object->pass) $valuetoshow.= ($valuetoshow?(' '.$langs->trans("or").' '):'').preg_replace('/./i', '*', $object->pass);
				else
				{
					if ($user->admin) $valuetoshow.= ($valuetoshow?(' '.$langs->trans("or").' '):'').$langs->trans("Crypted").': '.$object->pass_indatabase_crypted;
					else $valuetoshow.= ($valuetoshow?(' '.$langs->trans("or").' '):'').$langs->trans("Hidden");
				}
			}

			// Other form for user password
			$parameters=array('valuetoshow' => $valuetoshow);
			$reshook=$hookmanager->executeHooks('printUserPasswordField', $parameters, $object, $action);    // Note that $action and $object may have been modified by hook
			if ($reshook > 0) $valuetoshow=$hookmanager->resPrint;	// to replace
			else $valuetoshow.=$hookmanager->resPrint;				// to add

			print $valuetoshow;
			print "</td>";
			print '</tr>'."\n";

			// API key
			if (! empty($conf->api->enabled) && $user->admin) {
				print '<tr><td>'.$langs->trans("ApiKey").'</td>';
				print '<td>';
				if (! empty($object->api_key)) print preg_replace('/./', '*', $object->api_key);
				print '</td></tr>';
			}

			// Administrator
			print '<tr><td>'.$langs->trans("Administrator").'</td><td>';
			if (! empty($conf->multicompany->enabled) && $object->admin && ! $object->entity)
			{
				print $form->textwithpicto(yn($object->admin), $langs->trans("SuperAdministratorDesc"), 1, "superadmin");
			}
			elseif ($object->admin)
			{
				print $form->textwithpicto(yn($object->admin), $langs->trans("AdministratorDesc"), 1, "admin");
			}
			else
			{
				print yn($object->admin);
			}
			print '</td></tr>'."\n";

			// Type
			print '<tr><td>';
			$text=$langs->trans("Type");
			print $form->textwithpicto($text, $langs->trans("InternalExternalDesc"));
			print '</td><td>';
			$type=$langs->trans("Internal");
			if ($object->societe_id > 0) $type=$langs->trans("External");
			print $type;
			if ($object->ldap_sid) print ' ('.$langs->trans("DomainUser").')';
			print '</td></tr>'."\n";

			// Ldap sid
			if ($object->ldap_sid)
			{
				print '<tr><td>'.$langs->trans("Type").'</td><td>';
				print $langs->trans("DomainUser", $ldap->domainFQDN);
				print '</td></tr>'."\n";
			}

			// Gender
			print '<tr><td>'.$langs->trans("Gender").'</td>';
			print '<td>';
			if ($object->gender) print $langs->trans("Gender".$object->gender);
			print '</td></tr>';

			// Employee
			print '<tr><td>'.$langs->trans("Employee").'</td><td colspan="2">';
			print yn($object->employee);
			print '</td></tr>'."\n";

			// TODO Move this into tab RH, visible when salarie or RH is visible (HierarchicalResponsible must be on both tab)

			// Hierarchy
			print '<tr><td>'.$langs->trans("HierarchicalResponsible").'</td>';
			print '<td>';
			if (empty($object->fk_user)) print $langs->trans("None");
			else {
				$huser=new User($db);
				$huser->fetch($object->fk_user);
				print $huser->getNomUrl(1);
			}
			print '</td>';
			print "</tr>\n";

			// Position/Job
			print '<tr><td>'.$langs->trans("PostOrFunction").'</td>';
			print '<td>'.$object->job.'</td>';
			print '</tr>'."\n";

			// Default warehouse
            if (! empty($conf->stock->enabled))
            {
				require_once DOL_DOCUMENT_ROOT .'/product/stock/class/entrepot.class.php';
				$warehousestatic=new Entrepot($db);
				$warehousestatic->fetch($object->fk_warehouse);
				print '<tr><td>'.$langs->trans("DefaultWarehouse").'</td><td>';
				print $warehousestatic->getNomUrl();
				print '</td></tr>';
            }

			//$childids = $user->getAllChildIds(1);

			if ((! empty($conf->salaries->enabled) && ! empty($user->rights->salaries->read))
				|| (! empty($conf->hrm->enabled) && ! empty($user->rights->hrm->employee->read)))
			{
            	// Even a superior can't see this info of its subordinates wihtout $user->rights->salaries->read and $user->rights->hrm->employee->read (setting/viewing is reserverd to HR people).
            	// However, he can see the valuation of timesheet of its subordinates even without these permissions.
				$langs->load("salaries");

				// THM
				print '<tr><td>';
				$text=$langs->trans("THM");
				print $form->textwithpicto($text, $langs->trans("THMDescription"), 1, 'help', 'classthm');
				print '</td>';
				print '<td>';
				print ($object->thm!=''?price($object->thm, '', $langs, 1, -1, -1, $conf->currency):'');
				print '</td>';
				print "</tr>\n";

				// TJM
				print '<tr><td>';
				$text=$langs->trans("TJM");
				print $form->textwithpicto($text, $langs->trans("TJMDescription"), 1, 'help', 'classtjm');
				print '</td>';
				print '<td>';
				print ($object->tjm!=''?price($object->tjm, '', $langs, 1, -1, -1, $conf->currency):'');
				print '</td>';
				print "</tr>\n";

				// Salary
				print '<tr><td>'.$langs->trans("Salary").'</td>';
				print '<td>';
				print ($object->salary!=''?price($object->salary, '', $langs, 1, -1, -1, $conf->currency):'');
				print '</td>';
				print "</tr>\n";
			}

			// Weeklyhours
			print '<tr><td>'.$langs->trans("WeeklyHours").'</td>';
			print '<td>';
			print price2num($object->weeklyhours);
			print '</td>';
			print "</tr>\n";

			// Date employment
			print '<tr><td>'.$langs->trans("DateEmployment").'</td>';
			print '<td>';
			print dol_print_date($object->dateemployment, 'day');
			print '</td>';
			print "</tr>\n";

			// Date employment
			print '<tr><td>'.$langs->trans("DateEmploymentEnd").'</td>';
			print '<td>';
			print dol_print_date($object->dateemploymentend);
			print '</td>';
			print "</tr>\n";

			// Date of birth
			print '<tr><td>'.$langs->trans("DateToBirth").'</td>';
			print '<td>';
			print dol_print_date($object->birth, 'day');
			print '</td>';
			print "</tr>\n";

			// Accountancy code
			if ($conf->accounting->enabled)
			{
				print '<tr><td>'.$langs->trans("AccountancyCode").'</td>';
				print '<td>'.$object->accountancy_code.'</td></tr>';
			}

			print '</table>';

			print '</div>';
			print '<div class="fichehalfright"><div class="ficheaddleft">';

			print '<div class="underbanner clearboth"></div>';
			print '<table class="border tableforfield centpercent">';

			// Color user
			if (! empty($conf->agenda->enabled))
			{
				print '<tr><td>'.$langs->trans("ColorUser").'</td>';
				print '<td>';
				print $formother->showColor($object->color, '');
				print '</td>';
				print "</tr>\n";
			}

			// Categories
			if (! empty($conf->categorie->enabled)  && ! empty($user->rights->categorie->lire))
			{
				print '<tr><td>' . $langs->trans("Categories") . '</td>';
				print '<td colspan="3">';
				print $form->showCategories($object->id, 'user', 1);
				print '</td></tr>';
			}

			if (isset($conf->file->main_authentication) && preg_match('/openid/', $conf->file->main_authentication) && ! empty($conf->global->MAIN_OPENIDURL_PERUSER))
			{
				print '<tr><td>'.$langs->trans("OpenIDURL").'</td>';
				print '<td>'.$object->openid.'</td>';
				print "</tr>\n";
			}

			print '<tr><td class="titlefield">'.$langs->trans("LastConnexion").'</td>';
			print '<td>'.dol_print_date($object->datelastlogin, "dayhour").'</td>';
			print "</tr>\n";

			print '<tr><td>'.$langs->trans("PreviousConnexion").'</td>';
			print '<td>'.dol_print_date($object->datepreviouslogin, "dayhour").'</td>';
			print "</tr>\n";

		    // Multicompany
			if (! empty($conf->multicompany->enabled) && is_object($mc))
			{
				// This is now done with hook formObjectOptions. Keep this code for backward compatibility with old multicompany module
				if (! method_exists($mc, 'formObjectOptions'))
				{
				    if (! empty($conf->multicompany->enabled) && empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE) && $conf->entity == 1 && $user->admin && ! $user->entity)
				    {
				     	print '<tr><td>' . $langs->trans("Entity") . '</td><td>';
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
			include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

			// Company / Contact
			if (! empty($conf->societe->enabled))
			{
				print '<tr><td>'.$langs->trans("LinkToCompanyContact").'</td>';
				print '<td>';
				if (isset($object->socid) && $object->socid > 0)
				{
					$societe = new Societe($db);
					$societe->fetch($object->socid);
					print $societe->getNomUrl(1, '');
				}
				else
				{
					print $langs->trans("ThisUserIsNot");
				}
				if (! empty($object->contactid))
				{
					$contact = new Contact($db);
					$contact->fetch($object->contactid);
					if ($object->socid > 0) print ' / ';
					else print '<br>';
					print '<a href="'.DOL_URL_ROOT.'/contact/card.php?id='.$object->contactid.'">'.img_object($langs->trans("ShowContact"), 'contact').' '.dol_trunc($contact->getFullName($langs), 32).'</a>';
				}
				print '</td>';
				print '</tr>'."\n";
			}

			// Module Adherent
			if (! empty($conf->adherent->enabled))
			{
				$langs->load("members");
				print '<tr><td>'.$langs->trans("LinkedToDolibarrMember").'</td>';
				print '<td>';
				if ($object->fk_member)
				{
					$adh=new Adherent($db);
					$adh->fetch($object->fk_member);
					$adh->ref=$adh->getFullname($langs);	// Force to show login instead of id
					print $adh->getNomUrl(1);
				}
				else
				{
					print $langs->trans("UserNotLinkedToMember");
				}
				print '</td>';
				print '</tr>'."\n";
			}

			// Signature
			print '<tr><td class="tdtop">'.$langs->trans('Signature').'</td><td>';
			print dol_htmlentitiesbr($object->signature);
			print "</td></tr>\n";

			print "</table>\n";
			print '</div>';

			print '</div></div>';
			print '<div style="clear:both"></div>';


			dol_fiche_end();


			/*
             * Buttons actions
             */

			print '<div class="tabsAction">';

			$parameters=array();
			$reshook=$hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action);    // Note that $action and $object may have been modified by hook
			if (empty($reshook))
			{
				if (! empty($object->email))
				{
					$langs->load("mails");
					print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=presend&amp;mode=init#formmailbeforetitle">'.$langs->trans('SendMail').'</a></div>';
				}
				else
				{
					$langs->load("mails");
					print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NoEMail")).'">'.$langs->trans('SendMail').'</a></div>';
				}

				if ($caneditfield && (empty($conf->multicompany->enabled) || ! $user->entity || ($object->entity == $conf->entity) || ($conf->global->MULTICOMPANY_TRANSVERSE_MODE && $conf->entity == 1)))
				{
					if (! empty($conf->global->MAIN_ONLY_LOGIN_ALLOWED))
					{
						print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("DisabledInMonoUserMode")).'">'.$langs->trans("Modify").'</a></div>';
					}
					else
					{
						print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=edit">'.$langs->trans("Modify").'</a></div>';
					}
				}
				elseif ($caneditpassword && ! $object->ldap_sid &&
				(empty($conf->multicompany->enabled) || ! $user->entity || ($object->entity == $conf->entity) || ($conf->global->MULTICOMPANY_TRANSVERSE_MODE && $conf->entity == 1)))
				{
					print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=edit">'.$langs->trans("EditPassword").'</a></div>';
				}

				// Si on a un gestionnaire de generation de mot de passe actif
				if ($conf->global->USER_PASSWORD_GENERATED != 'none')
				{
					if ($object->statut == 0)
					{
						print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("UserDisabled")).'">'.$langs->trans("ReinitPassword").'</a></div>';
					}
					elseif (($user->id != $id && $caneditpassword) && $object->login && !$object->ldap_sid &&
					((empty($conf->multicompany->enabled) && $object->entity == $user->entity) || ! $user->entity || ($object->entity == $conf->entity) || ($conf->global->MULTICOMPANY_TRANSVERSE_MODE && $conf->entity == 1)))
					{
						print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=password">'.$langs->trans("ReinitPassword").'</a></div>';
					}

					if ($object->statut == 0)
					{
						print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("UserDisabled")).'">'.$langs->trans("SendNewPassword").'</a></div>';
					}
					elseif (($user->id != $id && $caneditpassword) && $object->login && !$object->ldap_sid &&
					((empty($conf->multicompany->enabled) && $object->entity == $user->entity) || ! $user->entity || ($object->entity == $conf->entity) || ($conf->global->MULTICOMPANY_TRANSVERSE_MODE && $conf->entity == 1)))
					{
						if ($object->email) print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=passwordsend">'.$langs->trans("SendNewPassword").'</a></div>';
						else print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NoEMail")).'">'.$langs->trans("SendNewPassword").'</a></div>';
					}
				}

				// Enable user
				if ($user->id <> $id && $candisableuser && $object->statut == 0 &&
				((empty($conf->multicompany->enabled) && $object->entity == $user->entity) || ! $user->entity || ($object->entity == $conf->entity) || ($conf->global->MULTICOMPANY_TRANSVERSE_MODE && $conf->entity == 1)))
				{
					print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=enable">'.$langs->trans("Reactivate").'</a></div>';
				}
				// Disable user
				if ($user->id <> $id && $candisableuser && $object->statut == 1 &&
				((empty($conf->multicompany->enabled) && $object->entity == $user->entity) || ! $user->entity || ($object->entity == $conf->entity) || ($conf->global->MULTICOMPANY_TRANSVERSE_MODE && $conf->entity == 1)))
				{
					print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?action=disable&amp;id='.$object->id.'">'.$langs->trans("DisableUser").'</a></div>';
				}
				else
				{
				    if ($user->id == $id)
				    {
				        print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("CantDisableYourself").'">'.$langs->trans("DisableUser").'</a></div>';
				    }
				}
				// Delete
				if ($user->id <> $id && $candisableuser &&
				((empty($conf->multicompany->enabled) && $object->entity == $user->entity) || ! $user->entity || ($object->entity == $conf->entity) || ($conf->global->MULTICOMPANY_TRANSVERSE_MODE && $conf->entity == 1)))
				{
					if ($user->admin || ! $object->admin) // If user edited is admin, delete is possible on for an admin
					{
						print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?action=delete&amp;id='.$object->id.'">'.$langs->trans("DeleteUser").'</a></div>';
					}
					else
					{
						print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("MustBeAdminToDeleteOtherAdmin")).'">'.$langs->trans("DeleteUser").'</a></div>';
					}
				}
			}

			print "</div>\n";



			//Select mail models is same action as presend
			if (GETPOST('modelselected')) $action = 'presend';

			// Presend form
			$modelmail='user';
			$defaulttopic='Information';
			$diroutput = $conf->user->dir_output;
			$trackid = 'use'.$object->id;

			include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';

			if ($action != 'presend' && $action != 'send')
			{
				/*
                 * List of groups of user
                 */

				if ($canreadgroup)
				{
					print load_fiche_titre($langs->trans("ListOfGroupsForUser"), '', '');

					// On selectionne les groupes auquel fait parti le user
					$exclude = array();

					$usergroup=new UserGroup($db);
					$groupslist = $usergroup->listGroupsForUser($object->id);

					if (! empty($groupslist))
					{
						foreach($groupslist as $groupforuser)
						{
							$exclude[]=$groupforuser->id;
						}
					}

					if ($caneditgroup)
					{
						print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$id.'" method="POST">'."\n";
						print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'" />';
						print '<input type="hidden" name="action" value="addgroup" />';
					}

					print '<table class="noborder" width="100%">'."\n";

					// Other form for add user to group
					$parameters=array('caneditgroup' => $caneditgroup, 'groupslist' => $groupslist, 'exclude' => $exclude);
					$reshook=$hookmanager->executeHooks('formAddUserToGroup', $parameters, $object, $action);    // Note that $action and $object may have been modified by hook
					print $hookmanager->resPrint;

					if (empty($reshook))
					{
						print '<tr class="liste_titre"><th class="liste_titre">'.$langs->trans("Groups").'</th>'."\n";
						print '<th class="liste_titre right">';
						if ($caneditgroup)
						{
							print $form->select_dolgroups('', 'group', 1, $exclude, 0, '', '', $object->entity);
							print ' &nbsp; ';
							print '<input type="hidden" name="entity" value="'.$conf->entity.'" />';
							print '<input type="submit" class="button" value="'.$langs->trans("Add").'" />';
						}
						print '</th></tr>'."\n";

						/*
						 * Groups assigned to user
						 */
						if (! empty($groupslist))
						{
							foreach($groupslist as $group)
							{
								print '<tr class="oddeven">';
								print '<td>';
								if ($caneditgroup)
								{
									print '<a href="'.DOL_URL_ROOT.'/user/group/card.php?id='.$group->id.'">'.img_object($langs->trans("ShowGroup"), "group").' '.$group->name.'</a>';
								}
								else
								{
									print img_object($langs->trans("ShowGroup"), "group").' '.$group->name;
								}
								print '</td>';
								print '<td class="right">';
								if ($caneditgroup)
								{
									print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=removegroup&amp;group='.$group->id.'">';
									print img_picto($langs->trans("RemoveFromGroup"), 'unlink');
									print '</a>';
								}
								else
								{
									print "&nbsp;";
								}
								print "</td></tr>\n";
							}
						}
						else
						{
							print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
						}
					}

					print "</table>";

					if ($caneditgroup)
					{
						print '</form>';
					}
					print "<br>";
				}
			}
		}

		/*
         * Fiche en mode edition
         */
		if ($action == 'edit' && ($canedituser || $caneditfield || $caneditpassword || ($user->id == $object->id)))
		{
			print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'" method="POST" name="updateuser" enctype="multipart/form-data">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="update">';
			print '<input type="hidden" name="entity" value="'.$object->entity.'">';

			dol_fiche_head($head, 'user', $title, 0, 'user');

			print '<table class="border centpercent">';

			// Ref/ID
			if (! empty($conf->global->MAIN_SHOW_TECHNICAL_ID))
			{
				print '<tr><td class="titlefield">'.$langs->trans("Ref").'</td>';
				print '<td>';
				print $object->id;
				print '</td>';
				print '</tr>';
			}

			// Lastname
			print "<tr>";
			print '<td class="titlefield fieldrequired">'.$langs->trans("Lastname").'</td>';
			print '<td>';
			if ($caneditfield && !$object->ldap_sid)
			{
				print '<input class="minwidth100" type="text" class="flat" name="lastname" value="'.$object->lastname.'">';
			}
			else
			{
				print '<input type="hidden" name="lastname" value="'.$object->lastname.'">';
				print $object->lastname;
			}
			print '</td>';
			print '</tr>';

			// Firstname
			print "<tr>".'<td>'.$langs->trans("Firstname").'</td>';
			print '<td>';
			if ($caneditfield && !$object->ldap_sid)
			{
				print '<input class="minwidth100" type="text" class="flat" name="firstname" value="'.$object->firstname.'">';
			}
			else
			{
				print '<input type="hidden" name="firstname" value="'.$object->firstname.'">';
				print $object->firstname;
			}
			print '</td></tr>';

			// Login
			print "<tr>".'<td><span class="fieldrequired">'.$langs->trans("Login").'</span></td>';
			print '<td>';
			if ($user->admin  && !$object->ldap_sid)
			{
				print '<input size="12" maxlength="24" type="text" class="flat" name="login" value="'.$object->login.'">';
			}
			else
			{
				print '<input type="hidden" name="login" value="'.$object->login.'">';
				print $object->login;
			}
			print '</td>';
			print '</tr>';

			// Pass
			print '<tr><td>'.$langs->trans("Password").'</td>';
			print '<td>';
			$valuetoshow='';
			if (preg_match('/ldap/', $dolibarr_main_authentication))
			{
				$valuetoshow.=($valuetoshow?(' '.$langs->trans("or").' '):'').$langs->trans("PasswordOfUserInLDAP");
			}
			if (preg_match('/http/', $dolibarr_main_authentication))
			{
				$valuetoshow.=($valuetoshow?(' '.$langs->trans("or").' '):'').$form->textwithpicto($text, $langs->trans("DolibarrInHttpAuthenticationSoPasswordUseless", $dolibarr_main_authentication), 1, 'warning');
			}
			if (preg_match('/dolibarr/', $dolibarr_main_authentication))
			{
				if ($caneditpassword)
				{
					$valuetoshow.=($valuetoshow?(' '.$langs->trans("or").' '):'').'<input size="12" maxlength="32" type="password" class="flat" name="password" value="'.$object->pass.'" autocomplete="new-password">';
				}
				else
				{
					$valuetoshow.=($valuetoshow?(' '.$langs->trans("or").' '):'').preg_replace('/./i', '*', $object->pass);
				}
			}

			// Other form for user password
			$parameters=array('valuetoshow' => $valuetoshow, 'caneditpassword' => $caneditpassword);
			$reshook=$hookmanager->executeHooks('printUserPasswordField', $parameters, $object, $action);    // Note that $action and $object may have been modified by hook
			if ($reshook > 0) $valuetoshow=$hookmanager->resPrint;	// to replace
			else $valuetoshow.=$hookmanager->resPrint;				// to add

			print $valuetoshow;
			print "</td></tr>\n";

			// API key
			if(! empty($conf->api->enabled) && $user->admin)
			{
				print '<tr><td>'.$langs->trans("ApiKey").'</td>';
				print '<td>';
				print '<input class="minwidth300" maxsize="32" type="text" id="api_key" name="api_key" value="'.$object->api_key.'" autocomplete="off">';
				if (! empty($conf->use_javascript_ajax))
					print '&nbsp;'.img_picto($langs->trans('Generate'), 'refresh', 'id="generate_api_key" class="linkobject"');
				print '</td></tr>';
			}

			// Administrator
			print '<tr><td>'.$langs->trans("Administrator").'</td>';
			if ($object->socid > 0)
			{
				$langs->load("admin");
				print '<td>';
				print '<input type="hidden" name="admin" value="'.$object->admin.'">'.yn($object->admin);
				print ' ('.$langs->trans("ExternalUser").')';
				print '</td></tr>';
			}
			else
			{
				print '<td>';
				$nbAdmin = $user->getNbOfUsers('active', '', 1);
				$nbSuperAdmin = $user->getNbOfUsers('active', 'superadmin', 1);
				//var_dump($nbAdmin);
				//var_dump($nbSuperAdmin);
				if ($user->admin								// Need to be admin to allow downgrade of an admin
				&& ($user->id != $object->id)                   // Don't downgrade ourself
				&& (
					(empty($conf->multicompany->enabled) && $nbAdmin >= 1)
					|| (! empty($conf->multicompany->enabled) && (($object->entity > 0 || ($user->entity == 0 && $object->entity == 0)) || $nbSuperAdmin > 1))    // Don't downgrade a superadmin if alone
					)
				)
				{
					print $form->selectyesno('admin', $object->admin, 1);

					if (! empty($conf->multicompany->enabled) && ! $user->entity)
					{
						if ($conf->use_javascript_ajax)
						{
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

						$checked=(($object->admin && ! $object->entity) ? ' checked' : '');
						print '<input type="checkbox" name="superadmin" value="1"'.$checked.' /> '.$langs->trans("SuperAdministrator");
					}
				}
				else
				{
					$yn = yn($object->admin);
					print '<input type="hidden" name="admin" value="'.$object->admin.'">';
					print '<input type="hidden" name="superadmin" value="'.(empty($object->entity) ? 1 : 0).'">';
					if (! empty($conf->multicompany->enabled) && empty($object->entity)) print $form->textwithpicto($yn, $langs->trans("DontDowngradeSuperAdmin"), 1, 'warning');
					else print $yn;
				}
				print '</td></tr>';
			}

		   	// Type
		   	print '<tr><td>'.$langs->trans("Type").'</td>';
		   	print '<td>';
		   	if ($user->id == $object->id || ! $user->admin)
		   	{
			   	$type=$langs->trans("Internal");
			   	if ($object->socid) $type=$langs->trans("External");
			   	print $form->textwithpicto($type, $langs->trans("InternalExternalDesc"));
			   	if ($object->ldap_sid) print ' ('.$langs->trans("DomainUser").')';
		   	}
		   	else
			{
				$type=0;
				if ($object->contactid) $type=$object->contactid;
				print $form->selectcontacts(0, $type, 'contactid', 2, '', '', 1, '', false, 1);
			   	if ($object->ldap_sid) print ' ('.$langs->trans("DomainUser").')';
			}
		   	print '</td></tr>';

		   	// Gender
		   	print '<tr><td>'.$langs->trans("Gender").'</td>';
		   	print '<td>';
		   	$arraygender=array('man'=>$langs->trans("Genderman"),'woman'=>$langs->trans("Genderwoman"));
		   	print $form->selectarray('gender', $arraygender, GETPOST('gender')?GETPOST('gender'):$object->gender, 1);
		   	print '</td></tr>';

            // Employee
            print '<tr>';
            print '<td>'.$form->editfieldkey('Employee', 'employee', '', $object, 0).'</td><td>';
            if ($caneditfield) {
                 print $form->selectyesno("employee", $object->employee, 1);
			} else {
				if ($object->employee){
					print $langs->trans("Yes");
				} else {
					print $langs->trans("No");
				}
			}
		    print '</td></tr>';

		    // Hierarchy
		   	print '<tr><td class="titlefield">'.$langs->trans("HierarchicalResponsible").'</td>';
		   	print '<td>';
		   	if ($caneditfield)
		   	{
		   		print $form->select_dolusers($object->fk_user, 'fk_user', 1, array($object->id), 0, '', 0, $object->entity, 0, 0, '', 0, '', 'maxwidth300');
		   	}
		   	else
		   	{
		   		print '<input type="hidden" name="fk_user" value="'.$object->fk_user.'">';
		   		$huser=new User($db);
		   		$huser->fetch($object->fk_user);
		   		print $huser->getNomUrl(1);
		   	}
		   	print '</td>';
		   	print "</tr>\n";


		   	print '</table><hr><table class="border centpercent">';


			// Address
			print '<tr><td class="tdtop titlefield">'.$form->editfieldkey('Address', 'address', '', $object, 0).'</td>';
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
			print '<tr><td>'.$form->editfieldkey('Country', 'selectcounty_id', '', $object, 0).'</td><td>';
			print $form->select_country((GETPOST('country_id')!=''?GETPOST('country_id'):$object->country_id), 'country_id');
			if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
			print '</td></tr>';

			// State
			if (empty($conf->global->USER_DISABLE_STATE))
			{
				print '<tr><td class="tdoverflow">'.$form->editfieldkey('State', 'state_id', '', $object, 0).'</td><td>';
				print $formcompany->select_state($object->state_id, $object->country_code, 'state_id');
				print '</td></tr>';
			}

			// Tel pro
			print "<tr>".'<td>'.$langs->trans("PhonePro").'</td>';
			print '<td>';
			if ($caneditfield  && empty($object->ldap_sid))
			{
				print '<input size="20" type="text" name="office_phone" class="flat" value="'.$object->office_phone.'">';
			}
			else
			{
				print '<input type="hidden" name="office_phone" value="'.$object->office_phone.'">';
				print $object->office_phone;
			}
			print '</td></tr>';

			// Tel mobile
			print "<tr>".'<td>'.$langs->trans("PhoneMobile").'</td>';
			print '<td>';
			if ($caneditfield && empty($object->ldap_sid))
			{
				print '<input size="20" type="text" name="user_mobile" class="flat" value="'.$object->user_mobile.'">';
			}
			else
			{
				print '<input type="hidden" name="user_mobile" value="'.$object->user_mobile.'">';
				print $object->user_mobile;
			}
			print '</td></tr>';

			// Fax
			print "<tr>".'<td>'.$langs->trans("Fax").'</td>';
			print '<td>';
			if ($caneditfield  && empty($object->ldap_sid))
			{
				print '<input size="20" type="text" name="office_fax" class="flat" value="'.$object->office_fax.'">';
			}
			else
			{
				print '<input type="hidden" name="office_fax" value="'.$object->office_fax.'">';
				print $object->office_fax;
			}
			print '</td></tr>';

			// Skype
			if (! empty($conf->socialnetworks->enabled))
			{
				print '<tr><td>'.$langs->trans("Skype").'</td>';
				print '<td>';
				if ($caneditfield  && empty($object->ldap_sid))
				{
					print '<input size="40" type="text" name="skype" class="flat" value="'.$object->skype.'">';
				}
				else
				{
					print '<input type="hidden" name="skype" value="'.$object->skype.'">';
					print $object->skype;
				}
				print '</td></tr>';
			}

			// Twitter
			if (! empty($conf->socialnetworks->enabled))
			{
				print '<tr><td>'.$langs->trans("Twitter").'</td>';
				print '<td>';
				if ($caneditfield  && empty($object->ldap_sid))
				{
					print '<input size="40" type="text" name="twitter" class="flat" value="'.$object->twitter.'">';
				}
				else
				{
					print '<input type="hidden" name="twitter" value="'.$object->twitter.'">';
					print $object->twitter;
				}
				print '</td></tr>';
			}

			// Facebook
			if (! empty($conf->socialnetworks->enabled))
			{
				print '<tr><td>'.$langs->trans("Facebook").'</td>';
				print '<td>';
				if ($caneditfield  && empty($object->ldap_sid))
				{
					print '<input size="40" type="text" name="facebook" class="flat" value="'.$object->facebook.'">';
				}
				else
				{
					print '<input type="hidden" name="facebook" value="'.$object->facebook.'">';
					print $object->facebook;
				}
				print '</td></tr>';
			}

            // LinkedIn
            if (! empty($conf->socialnetworks->enabled))
            {
                print '<tr><td>'.$langs->trans("LinkedIn").'</td>';
                print '<td>';
                if ($caneditfield  && empty($object->ldap_sid))
                {
                    print '<input size="40" type="text" name="linkedin" class="flat" value="'.$object->linkedin.'">';
                }
                else
                {
                    print '<input type="hidden" name="linkedin" value="'.$object->linkedin.'">';
                    print $object->linkedin;
                }
                print '</td></tr>';
            }

			// EMail
			print "<tr>".'<td'.(! empty($conf->global->USER_MAIL_REQUIRED)?' class="fieldrequired"':'').'>'.$langs->trans("EMail").'</td>';
			print '<td>';
			if ($caneditfield  && empty($object->ldap_sid))
			{
				print '<input class="minwidth100" type="text" name="email" class="flat" value="'.$object->email.'">';
			}
			else
			{
				print '<input type="hidden" name="email" value="'.$object->email.'">';
				print $object->email;
			}
			print '</td></tr>';

			// OpenID url
			if (isset($conf->file->main_authentication) && preg_match('/openid/', $conf->file->main_authentication) && ! empty($conf->global->MAIN_OPENIDURL_PERUSER))
			{
				print "<tr>".'<td>'.$langs->trans("OpenIDURL").'</td>';
				print '<td>';
				if ($caneditfield)
				{
					print '<input class="minwidth100" type="url" name="openid" class="flat" value="'.$object->openid.'">';
				}
				else
				{
					print '<input type="hidden" name="openid" value="'.$object->openid.'">';
					print $object->openid;
				}
				print '</td></tr>';
			}

			print '</table><hr><table class="border centpercent">';

			// Accountancy code
			if ($conf->accounting->enabled)
			{
				print "<tr>";
				print '<td>'.$langs->trans("AccountancyCode").'</td>';
				print '<td>';
				if ($caneditfield)
				{
					print '<input size="30" type="text" class="flat" name="accountancy_code" value="'.$object->accountancy_code.'">';
				}
				else
				{
					print '<input type="hidden" name="accountancy_code" value="'.$object->accountancy_code.'">';
					print $object->accountancy_code;
				}
				print '</td>';
				print "</tr>";
			}

			// User color
			if (! empty($conf->agenda->enabled))
			{
				print '<tr><td>'.$langs->trans("ColorUser").'</td>';
				print '<td>';
				if ($caneditfield)
				{
					print $formother->selectColor(GETPOST('color')?GETPOST('color'):$object->color, 'color', null, 1, '', 'hideifnotset');
				}else{
					print $formother->showColor($object->color, '');
				}
				print '</td></tr>';
			}

			// Photo
			print '<tr>';
			print '<td>'.$langs->trans("Photo").'</td>';
			print '<td>';
			print $form->showphoto('userphoto', $object, 60, 0, $caneditfield, 'photowithmargin', 'small');
			print '</td>';
			print '</tr>';

			// Categories
			if (!empty($conf->categorie->enabled) && !empty($user->rights->categorie->lire))
			{
				print '<tr><td>' . $form->editfieldkey('Categories', 'usercats', '', $object, 0) . '</td>';
				print '<td>';
				$cate_arbo = $form->select_all_categories(Categorie::TYPE_USER, null, null, null, null, 1);
				$c = new Categorie($db);
				$cats = $c->containing($object->id, Categorie::TYPE_USER);
				foreach ($cats as $cat) {
					$arrayselected[] = $cat->id;
				}
				if ($caneditfield)
				{
					print $form->multiselectarray('usercats', $cate_arbo, $arrayselected, '', 0, '', 0, '90%');
				}else{
					print $form->showCategories($object->id, 'user', 1);
				}
				print "</td></tr>";
			}

			// Status
			print '<tr><td>'.$langs->trans("Status").'</td>';
			print '<td>';
			print $object->getLibStatut(4);
			print '</td></tr>';

			// Company / Contact
			if (! empty($conf->societe->enabled))
			{
				print '<tr><td width="25%">'.$langs->trans("LinkToCompanyContact").'</td>';
				print '<td>';
				if ($object->socid > 0)
				{
					$societe = new Societe($db);
					$societe->fetch($object->socid);
					print $societe->getNomUrl(1, '');
					if ($object->contactid)
					{
						$contact = new Contact($db);
						$contact->fetch($object->contactid);
						print ' / <a href="'.DOL_URL_ROOT.'/contact/card.php?id='.$object->contactid.'">'.img_object($langs->trans("ShowContact"), 'contact').' '.dol_trunc($contact->getFullName($langs), 32).'</a>';
					}
				}
				else
				{
					print $langs->trans("ThisUserIsNot");
				}
				print ' ('.$langs->trans("UseTypeFieldToChange").')';
				print '</td>';
				print "</tr>\n";
			}

			// Module Adherent
			if (! empty($conf->adherent->enabled))
			{
				$langs->load("members");
				print '<tr><td width="25%">'.$langs->trans("LinkedToDolibarrMember").'</td>';
				print '<td>';
				if ($object->fk_member)
				{
					$adh=new Adherent($db);
					$adh->fetch($object->fk_member);
					$adh->ref=$adh->login;	// Force to show login instead of id
					print $adh->getNomUrl(1);
				}
				else
				{
					print $langs->trans("UserNotLinkedToMember");
				}
				print '</td>';
				print "</tr>\n";
			}

            // Multicompany
            // TODO check if user not linked with the current entity before change entity (thirdparty, invoice, etc.) !!
            if (! empty($conf->multicompany->enabled) && is_object($mc))
            {
            	// This is now done with hook formObjectOptions. Keep this code for backward compatibility with old multicompany module
            	if (! method_exists($mc, 'formObjectOptions'))
            	{
            		if (empty($conf->multicompany->transverse_mode) && $conf->entity == 1 && $user->admin && ! $user->entity)
	            	{
	            		print "<tr>".'<td>'.$langs->trans("Entity").'</td>';
	            		print "<td>".$mc->select_entities($object->entity, 'entity', '', 0, 1, false, false, 1);		// last parameter 1 means, show also a choice 0=>'all entities'
	            		print "</td></tr>\n";
	            	}
	            	else
	            	{
	            		print '<input type="hidden" name="entity" value="'.$conf->entity.'" />';
	            	}
	            }
			}

			// Other attributes
			$parameters=array('colspan' => ' colspan="2"');
			$reshook=$hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action);    // Note that $action and $object may have been modified by hook
			print $hookmanager->resPrint;
			if (empty($reshook))
			{
				if ($caneditfield)
				{
					print $object->showOptionals($extrafields, 'edit');
				}else{
					print $object->showOptionals($extrafields, 'view');
				}
			}

			// Signature
			print '<tr><td class="tdtop">'.$langs->trans("Signature").'</td>';
			print '<td>';
			if ($caneditfield)
			{
				require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
				$doleditor=new DolEditor('signature', $object->signature, '', 138, 'dolibarr_notes', 'In', false, true, empty($conf->global->FCKEDITOR_ENABLE_USERSIGN)?0:1, ROWS_4, '90%');
				print $doleditor->Create(1);
			}
			else
			{
				print dol_htmlentitiesbr($object->signature);
			}
			print '</td></tr>';


			print '</table><hr><table class="border centpercent">';


			// TODO Move this into tab RH (HierarchicalResponsible must be on both tab)

			// Position/Job
			print '<tr><td class="titlefield">'.$langs->trans("PostOrFunction").'</td>';
			print '<td>';
			if ($caneditfield)
			{
				print '<input size="30" type="text" name="job" value="'.$object->job.'">';
			}
			else
			{
				print '<input type="hidden" name="job" value="'.$object->job.'">';
				print $object->job;
			}
			print '</td></tr>';

			// Default warehouse
            if (! empty($conf->stock->enabled))
            {
                print '<tr><td>'.$langs->trans("DefaultWarehouse").'</td><td>';
                print $formproduct->selectWarehouses($object->fk_warehouse, 'fk_warehouse', 'warehouseopen', 1);
                print ' <a href="'.DOL_URL_ROOT.'/product/stock/card.php?action=create&amp;backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.$object->id.'&action=edit').'">'.$langs->trans("AddWarehouse").'</a>';
                print '</td></tr>';
            }

			if ((! empty($conf->salaries->enabled) && ! empty($user->rights->salaries->read))
				|| (! empty($conf->hrm->enabled) && ! empty($user->rights->hrm->employee->read)))
			{
				$langs->load("salaries");

				// THM
				print '<tr><td>';
				$text=$langs->trans("THM");
				print $form->textwithpicto($text, $langs->trans("THMDescription"), 1, 'help', 'classthm');
				print '</td>';
				print '<td>';
				if($caneditfield){
					print '<input size="8" type="text" name="thm" value="'.price2num(GETPOST('thm')?GETPOST('thm'):$object->thm).'">';
				}else{
					print ($object->thm!=''?price($object->thm, '', $langs, 1, -1, -1, $conf->currency):'');
				}
				print '</td>';
				print "</tr>\n";

				// TJM
				print '<tr><td>';
				$text=$langs->trans("TJM");
				print $form->textwithpicto($text, $langs->trans("TJMDescription"), 1, 'help', 'classthm');
				print '</td>';
				print '<td>';
				if($caneditfield)
				{
					print '<input size="8" type="text" name="tjm" value="'.price2num(GETPOST('tjm')?GETPOST('tjm'):$object->tjm).'">';
				}else{
					print ($object->tjm!=''?price($object->tjm, '', $langs, 1, -1, -1, $conf->currency):'');
				}
				print '</td>';
				print "</tr>\n";

				// Salary
				print '<tr><td>'.$langs->trans("Salary").'</td>';
				print '<td>';
				print '<input size="8" type="text" name="salary" value="'.price2num(GETPOST('salary')?GETPOST('salary'):$object->salary).'">';
				print '</td>';
				print "</tr>\n";
			}

			// Weeklyhours
			print '<tr><td>'.$langs->trans("WeeklyHours").'</td>';
			print '<td>';
			if($caneditfield)
			{
				print '<input size="8" type="text" name="weeklyhours" value="'.price2num(GETPOST('weeklyhours')?GETPOST('weeklyhours'):$object->weeklyhours).'">';
			}else{
				print price2num($object->weeklyhours);
			}
			print '</td>';
			print "</tr>\n";

			// Date employment
			print '<tr><td>'.$langs->trans("DateEmployment").'</td>';
			print '<td>';
			if($caneditfield)
			{
				print $form->selectDate(GETPOST('dateemployment')?GETPOST('dateemployment'):$object->dateemployment, 'dateemployment', 0, 0, 1, 'formdateemployment', 1, 0);
			}else{
				print dol_print_date($object->dateemployment, 'day');
			}
			print '</td>';
			print "</tr>\n";

			// Date employmentEnd
			print '<tr><td>'.$langs->trans("DateEmploymentEnd").'</td>';
			print '<td>';
			if($caneditfield)
			{
				print $form->selectDate(GETPOST('dateemploymentend')?GETPOST('dateemploymentend'):$object->dateemploymentend, 'dateemploymentend', 0, 0, 1, 'formdateemploymentend', 1, 0);
			}else{
				print dol_print_date($object->dateemploymentend, 'day');
			}
			print '</td>';
			print "</tr>\n";


			// Date birth
			print '<tr><td>'.$langs->trans("DateToBirth").'</td>';
			print '<td>';
			if($caneditfield)
			{
				echo $form->selectDate(GETPOST('birth')?GETPOST('birth'):$object->birth, 'birth', 0, 0, 1, 'updateuser', 1, 0);
			}else{
				print dol_print_date($object->birth, 'day');
			}
			print '</td>';
			print "</tr>\n";

			print '</table>';

			dol_fiche_end();

			print '<div class="center">';
			print '<input value="'.$langs->trans("Save").'" class="button" type="submit" name="save">';
			print '&nbsp; &nbsp; &nbsp;';
			print '<input value="'.$langs->trans("Cancel").'" class="button" type="submit" name="cancel">';
			print '</div>';

			print '</form>';
		}

		if ($action != 'edit' && $action != 'presend')
		{
			print '<div class="fichecenter"><div class="fichehalfleft">';
			/*
             * Documents generes
             */
			$filename = dol_sanitizeFileName($object->ref);
			$filedir = $conf->user->dir_output . "/" . dol_sanitizeFileName($object->ref);
			$urlsource = $_SERVER["PHP_SELF"] . "?id=" . $object->id;
			$genallowed = $user->rights->user->user->lire;
			$delallowed = $user->rights->user->user->creer;

			print $formfile->showdocuments('user', $filename, $filedir, $urlsource, $genallowed, $delallowed, $object->modelpdf, 1, 0, 0, 28, 0, '', 0, '', $soc->default_lang);
			$somethingshown = $formfile->numoffiles;

			// Show links to link elements
			$linktoelem = $form->showLinkToObjectBlock($object, null, null);
			$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);

			print '</div><div class="fichehalfright"><div class="ficheaddleft">';

			// List of actions on element
			include_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
			$formactions = new FormActions($db);
			$somethingshown = $formactions->showactions($object, 'user', $socid, 1);


			print '</div></div></div>';
		}

		if (! empty($conf->ldap->enabled) && ! empty($object->ldap_sid)) $ldap->close();
	}
}

if (! empty($conf->api->enabled) && ! empty($conf->use_javascript_ajax))
{
	print "\n".'<script type="text/javascript">';
	print '$(document).ready(function () {
            $("#generate_api_key").click(function() {
                $.get( "'.DOL_URL_ROOT.'/core/ajax/security.php", {
                    action: \'getrandompassword\',
                    generic: true
                },
                function(token) {
                    $("#api_key").val(token);
                });
            });
    });';
	print '</script>';
}

// End of page
llxFooter();
$db->close();
