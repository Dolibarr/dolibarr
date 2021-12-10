<?php
/* Copyright (C) 2004-2005  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2019  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2004       Benoit Mortier          <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2017  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2007       Franky Van Liedekerke   <franky.van.liedekerke@telenet.be>
 * Copyright (C) 2013       Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2013-2016  Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2014       Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2015       Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2018-2021  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2019       Josep Lluís Amador      <joseplluis@lliuretic.cat>
 * Copyright (C) 2020       Open-Dsi     			<support@open-dsi.fr>
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
 *       \file       htdocs/contact/card.php
 *       \ingroup    societe
 *       \brief      Card of a contact
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/contact.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

// Load translation files required by the page
$langs->loadLangs(array('companies', 'users', 'other', 'commercial'));

$mesg = ''; $error = 0; $errors = array();

$action = (GETPOST('action', 'alpha') ? GETPOST('action', 'alpha') : 'view');
$confirm = GETPOST('confirm', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$cancel = GETPOST('cancel', 'alpha');

$id = GETPOST('id', 'int');
$socid = GETPOST('socid', 'int');

$object = new Contact($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$socialnetworks = getArrayOfSocialNetworks();

// Get object canvas (By default, this is not defined, so standard usage of dolibarr)
$object->getCanvas($id);
$objcanvas = null;
$canvas = (!empty($object->canvas) ? $object->canvas : GETPOST("canvas"));
if (!empty($canvas)) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/canvas.class.php';
	$objcanvas = new Canvas($db, $action);
	$objcanvas->getCanvas('contact', 'contactcard', $canvas);
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('contactcard', 'globalcard'));

if ($id > 0) {
	$object->fetch($id);
}

if (!($object->id > 0) && $action == 'view') {
	$langs->load("errors");
	print($langs->trans('ErrorRecordNotFound'));
	exit;
}

$triggermodname = 'CONTACT_MODIFY';
$permissiontoadd = $user->rights->societe->contact->creer;

// Security check
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'contact', $id, 'socpeople&societe', '', '', 'rowid', 0); // If we create a contact with no company (shared contacts), no check on write permission


/*
 *	Actions
 */

$parameters = array('id'=>$id, 'objcanvas'=>$objcanvas);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	// Cancel
	if (GETPOST('cancel', 'alpha') && !empty($backtopage)) {
		header("Location: ".$backtopage);
		exit;
	}

	// Creation utilisateur depuis contact
	if ($action == 'confirm_create_user' && $confirm == 'yes' && $user->rights->user->user->creer) {
		// Recuperation contact actuel
		$result = $object->fetch($id);

		if ($result > 0) {
			$db->begin();

			// Creation user
			$nuser = new User($db);
			$result = $nuser->create_from_contact($object, GETPOST("login")); // Do not use GETPOST(alpha)

			if ($result > 0) {
				$result2 = $nuser->setPassword($user, GETPOST("password"), 0, 0, 1); // Do not use GETPOST(alpha)
				if ($result2) {
					$db->commit();
				} else {
					$error = $nuser->error; $errors = $nuser->errors;
					$db->rollback();
				}
			} else {
				$error = $nuser->error; $errors = $nuser->errors;
				$db->rollback();
			}
		} else {
			$error = $object->error; $errors = $object->errors;
		}
	}


	// Confirmation desactivation
	if ($action == 'disable' && !empty($permissiontoadd)) {
		$object->fetch($id);
		if ($object->setstatus(0) < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		} else {
			header("Location: ".$_SERVER['PHP_SELF'].'?id='.$id);
			exit;
		}
	}

	// Confirmation activation
	if ($action == 'enable' && !empty($permissiontoadd)) {
		$object->fetch($id);
		if ($object->setstatus(1) < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		} else {
			header("Location: ".$_SERVER['PHP_SELF'].'?id='.$id);
			exit;
		}
	}

	// Add contact
	if ($action == 'add' && !empty($permissiontoadd)) {
		$db->begin();

		if ($canvas) {
			$object->canvas = $canvas;
		}

		$object->entity = (GETPOSTISSET('entity') ?GETPOST('entity', 'int') : $conf->entity);
		$object->socid = GETPOST("socid", 'int');
		$object->lastname = (string) GETPOST("lastname", 'alpha');
		$object->firstname = (string) GETPOST("firstname", 'alpha');
		$object->civility_code = (string) GETPOST("civility_code", 'alpha');
		$object->poste = (string) GETPOST("poste", 'alpha');
		$object->address = (string) GETPOST("address", 'alpha');
		$object->zip = (string) GETPOST("zipcode", 'alpha');
		$object->town = (string) GETPOST("town", 'alpha');
		$object->country_id = (int) GETPOST("country_id", 'int');
		$object->state_id = (int) GETPOST("state_id", 'int');
		//$object->jabberid		= GETPOST("jabberid", 'alpha');
		//$object->skype		= GETPOST("skype", 'alpha');
		//$object->twitter		= GETPOST("twitter", 'alpha');
		//$object->facebook		= GETPOST("facebook", 'alpha');
		//$object->linkedin		= GETPOST("linkedin", 'alpha');
		$object->socialnetworks = array();
		if (!empty($conf->socialnetworks->enabled)) {
			foreach ($socialnetworks as $key => $value) {
				if (GETPOSTISSET($key) && GETPOST($key, 'alphanohtml') != '') {
					$object->socialnetworks[$key] = (string) GETPOST($key, 'alphanohtml');
				}
			}
		}
		$object->email = (string) GETPOST('email', 'custom', 0, FILTER_SANITIZE_EMAIL);
		$object->no_email = GETPOST("no_email", "int");
		$object->phone_pro = (string) GETPOST("phone_pro", 'alpha');
		$object->phone_perso = (string) GETPOST("phone_perso", 'alpha');
		$object->phone_mobile = (string) GETPOST("phone_mobile", 'alpha');
		$object->fax = (string) GETPOST("fax", 'alpha');
		$object->priv = GETPOST("priv", 'int');
		$object->note_public = (string) GETPOST("note_public", 'restricthtml');
		$object->note_private = (string) GETPOST("note_private", 'restricthtml');
		$object->roles = GETPOST("roles", 'array');

		$object->statut = 1; //Default status to Actif

		// Note: Correct date should be completed with location to have exact GM time of birth.
		$object->birthday = dol_mktime(0, 0, 0, GETPOST("birthdaymonth", 'int'), GETPOST("birthdayday", 'int'), GETPOST("birthdayyear", 'int'));
		$object->birthday_alert = GETPOST("birthday_alert", 'alpha');

		// Fill array 'array_options' with data from add form
		$ret = $extrafields->setOptionalsFromPost(null, $object);
		if ($ret < 0) {
			$error++;
			$action = 'create';
		}

		if (!empty($conf->mailing->enabled) && $conf->global->MAILING_CONTACT_DEFAULT_BULK_STATUS == 2 && $object->no_email == -1 && !empty($object->email)) {
			$error++;
			$errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentities("No_Email"));
			$action = 'create';
		}

		if (!empty($object->email) && !isValidEMail($object->email)) {
			$langs->load("errors");
			$error++;
			$errors[] = $langs->trans("ErrorBadEMail", GETPOST('email', 'alpha'));
			$action = 'create';
		}

		if (empty($object->lastname)) {
			$error++;
			$errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentities("Lastname").' / '.$langs->transnoentities("Label"));
			$action = 'create';
		}

		if (empty($error)) {
			$id = $object->create($user);
			if ($id <= 0) {
				$error++;
				$errors = array_merge($errors, ($object->error ? array($object->error) : $object->errors));
				$action = 'create';
			}
		}

		if (empty($error)) {
			// Categories association
			$contcats = GETPOST('contcats', 'array');
			if (count($contcats) > 0) {
				$result = $object->setCategories($contcats);
				if ($result <= 0) {
					$error++;
					$errors = array_merge($errors, ($object->error ? array($object->error) : $object->errors));
					$action = 'create';
				}
			}
		}

		if (empty($error) && !empty($conf->mailing->enabled) && !empty($object->email)) {
			// Add mass emailing flag into table mailing_unsubscribe
			$result = $object->setNoEmail($object->no_email);
			if ($result < 0) {
				$error++;
				$errors = array_merge($errors, ($object->error ? array($object->error) : $object->errors));
				$action = 'create';
			}
		}

		if (empty($error) && $id > 0) {
			$db->commit();
			if (!empty($backtopage)) {
				$url = $backtopage;
			} else {
				$url = 'card.php?id='.$id;
			}
			header("Location: ".$url);
			exit;
		} else {
			$db->rollback();
		}
	}

	if ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->societe->contact->supprimer) {
		$result = $object->fetch($id);
		$object->oldcopy = clone $object;

		$object->old_lastname = (string) GETPOST("old_lastname", 'alpha');
		$object->old_firstname = (string) GETPOST("old_firstname", 'alpha');

		$result = $object->delete(); // TODO Add $user as first param
		if ($result > 0) {
			if ($backtopage) {
				header("Location: ".$backtopage);
				exit;
			} else {
				header("Location: ".DOL_URL_ROOT.'/contact/list.php');
				exit;
			}
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	if ($action == 'update' && empty($cancel) && !empty($permissiontoadd)) {
		if (!GETPOST("lastname", 'alpha')) {
			$error++; $errors = array($langs->trans("ErrorFieldRequired", $langs->transnoentities("Name").' / '.$langs->transnoentities("Label")));
			$action = 'edit';
		}

		if (!empty($conf->mailing->enabled) && $conf->global->MAILING_CONTACT_DEFAULT_BULK_STATUS == 2 && GETPOST("no_email", "int") == -1 && !empty(GETPOST('email', 'custom', 0, FILTER_SANITIZE_EMAIL))) {
			$error++;
			$errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentities("No_Email"));
			$action = 'edit';
		}

		if (!empty(GETPOST('email', 'custom', 0, FILTER_SANITIZE_EMAIL)) && !isValidEMail(GETPOST('email', 'custom', 0, FILTER_SANITIZE_EMAIL))) {
			$langs->load("errors");
			$error++;
			$errors[] = $langs->trans("ErrorBadEMail", GETPOST('email', 'alpha'));
			$action = 'edit';
		}

		if (!$error) {
			$contactid = GETPOST("contactid", 'int');
			$object->fetch($contactid);
			$object->fetchRoles($contactid);

			// Photo save
			$dir = $conf->societe->multidir_output[$object->entity]."/contact/".$object->id."/photos";
			$file_OK = is_uploaded_file($_FILES['photo']['tmp_name']);
			if (GETPOST('deletephoto') && $object->photo) {
				$fileimg = $dir.'/'.$object->photo;
				$dirthumbs = $dir.'/thumbs';
				dol_delete_file($fileimg);
				dol_delete_dir_recursive($dirthumbs);
				$object->photo = '';
			}
			if ($file_OK) {
				if (image_format_supported($_FILES['photo']['name']) > 0) {
					dol_mkdir($dir);

					if (@is_dir($dir)) {
						$newfile = $dir.'/'.dol_sanitizeFileName($_FILES['photo']['name']);
						$result = dol_move_uploaded_file($_FILES['photo']['tmp_name'], $newfile, 1);

						if (!$result > 0) {
							$errors[] = "ErrorFailedToSaveFile";
						} else {
							$object->photo = dol_sanitizeFileName($_FILES['photo']['name']);

							// Create thumbs
							$object->addThumbs($newfile);
						}
					}
				} else {
					$errors[] = "ErrorBadImageFormat";
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

			$object->oldcopy = clone $object;

			$object->old_lastname = (string) GETPOST("old_lastname", 'alpha');
			$object->old_firstname = (string) GETPOST("old_firstname", 'alpha');

			$object->socid = GETPOST("socid", 'int');
			$object->lastname = (string) GETPOST("lastname", 'alpha');
			$object->firstname = (string) GETPOST("firstname", 'alpha');
			$object->civility_code = (string) GETPOST("civility_code", 'alpha');
			$object->poste = (string) GETPOST("poste", 'alpha');

			$object->address = (string) GETPOST("address", 'alpha');
			$object->zip = (string) GETPOST("zipcode", 'alpha');
			$object->town = (string) GETPOST("town", 'alpha');
			$object->state_id = GETPOST("state_id", 'int');
			$object->country_id = GETPOST("country_id", 'int');

			$object->email = (string) GETPOST('email', 'custom', 0, FILTER_SANITIZE_EMAIL);
			$object->no_email = GETPOST("no_email", "int");
			//$object->jabberid		= GETPOST("jabberid", 'alpha');
			//$object->skype		= GETPOST("skype", 'alpha');
			//$object->twitter		= GETPOST("twitter", 'alpha');
			//$object->facebook		= GETPOST("facebook", 'alpha');
			//$object->linkedin		= GETPOST("linkedin", 'alpha');
			$object->socialnetworks = array();
			if (!empty($conf->socialnetworks->enabled)) {
				foreach ($socialnetworks as $key => $value) {
					if (GETPOSTISSET($key) && GETPOST($key, 'alphanohtml') != '') {
						$object->socialnetworks[$key] = (string) GETPOST($key, 'alphanohtml');
					}
				}
			}
			$object->phone_pro = (string) GETPOST("phone_pro", 'alpha');
			$object->phone_perso = (string) GETPOST("phone_perso", 'alpha');
			$object->phone_mobile = (string) GETPOST("phone_mobile", 'alpha');
			$object->fax = (string) GETPOST("fax", 'alpha');
			$object->priv = (string) GETPOST("priv", 'int');
			$object->note_public = (string) GETPOST("note_public", 'restricthtml');
			$object->note_private = (string) GETPOST("note_private", 'restricthtml');

			$object->roles = GETPOST("roles", 'array'); // Note GETPOSTISSET("role") is null when combo is empty

			// Fill array 'array_options' with data from add form
			$ret = $extrafields->setOptionalsFromPost(null, $object, '@GETPOSTISSET');
			if ($ret < 0) {
				$error++;
			}

			if (!$error) {
				$result = $object->update($contactid, $user);

				if ($result > 0) {
					// Categories association
					$categories = GETPOST('contcats', 'array');
					$object->setCategories($categories);

					// Update mass emailing flag into table mailing_unsubscribe
					if (GETPOSTISSET('no_email') && $object->email) {
						$no_email = GETPOST('no_email', 'int');
						$result = $object->setNoEmail($no_email);
						if ($result < 0) {
							setEventMessages($object->error, $object->errors, 'errors');
							$action = 'edit';
						}
					}

					$action = 'view';
				} else {
					setEventMessages($object->error, $object->errors, 'errors');
					$action = 'edit';
				}
			}
		}

		if (!$error && empty($errors)) {
			if (!empty($backtopage)) {
				header("Location: ".$backtopage);
				exit;
			}
		}
	}

	if ($action == 'setprospectcontactlevel' && !empty($permissiontoadd)) {
		$object->fetch($id);
		$object->fk_prospectlevel = GETPOST('prospect_contact_level_id', 'alpha');
		$result = $object->update($object->id, $user);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// set communication status
	if ($action == 'setstcomm' && !empty($permissiontoadd)) {
		$object->fetch($id);
		$object->stcomm_id = dol_getIdFromCode($db, GETPOST('stcomm', 'alpha'), 'c_stcommcontact');
		$result = $object->update($object->id, $user);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// Update extrafields
	if ($action == "update_extras" && !empty($permissiontoadd)) {
		$object->fetch(GETPOST('id', 'int'));

		$attributekey = GETPOST('attribute', 'alpha');
		$attributekeylong = 'options_'.$attributekey;

		if (GETPOSTISSET($attributekeylong.'day') && GETPOSTISSET($attributekeylong.'month') && GETPOSTISSET($attributekeylong.'year')) {
			// This is properties of a date
			$object->array_options['options_'.$attributekey] = dol_mktime(GETPOST($attributekeylong.'hour', 'int'), GETPOST($attributekeylong.'min', 'int'), GETPOST($attributekeylong.'sec', 'int'), GETPOST($attributekeylong.'month', 'int'), GETPOST($attributekeylong.'day', 'int'), GETPOST($attributekeylong.'year', 'int'));
			//var_dump(dol_print_date($object->array_options['options_'.$attributekey]));exit;
		} else {
			$object->array_options['options_'.$attributekey] = GETPOST($attributekeylong, 'alpha');
		}

		$result = $object->insertExtraFields(empty($triggermodname) ? '' : $triggermodname, $user);
		if ($result > 0) {
			setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
			$action = 'view';
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
			$action = 'edit_extras';
		}
	}

	// Actions to send emails
	$triggersendname = 'CONTACT_SENTBYMAIL';
	$paramname = 'id';
	$mode = 'emailfromcontact';
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';
}


/*
 *	View
 */


$title = (!empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT) ? $langs->trans("Contacts") : $langs->trans("ContactsAddresses"));
if (!empty($conf->global->MAIN_HTML_TITLE) && preg_match('/contactnameonly/', $conf->global->MAIN_HTML_TITLE) && $object->lastname) {
	$title = $object->lastname;
}
$help_url = 'EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('', $title, $help_url);

$form = new Form($db);
$formcompany = new FormCompany($db);

$countrynotdefined = $langs->trans("ErrorSetACountryFirst").' ('.$langs->trans("SeeAbove").')';

if ($socid > 0) {
	$objsoc = new Societe($db);
	$objsoc->fetch($socid);
}

if (is_object($objcanvas) && $objcanvas->displayCanvasExists($action)) {
	// -----------------------------------------
	// When used with CANVAS
	// -----------------------------------------
	if (empty($object->error) && $id) {
		$object = new Contact($db);
		$result = $object->fetch($id);
		if ($result <= 0) {
			dol_print_error('', $object->error);
		}
	}
	$objcanvas->assign_values($action, $object->id, $object->ref); // Set value for templates
	$objcanvas->display_canvas($action); // Show template
} else {
	// -----------------------------------------
	// When used in standard mode
	// -----------------------------------------

	// Confirm deleting contact
	if ($user->rights->societe->contact->supprimer) {
		if ($action == 'delete') {
			print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$id.($backtopage ? '&backtopage='.$backtopage : ''), $langs->trans("DeleteContact"), $langs->trans("ConfirmDeleteContact"), "confirm_delete", '', 0, 1);
		}
	}

	/*
	 * Onglets
	 */
	$head = array();
	if ($id > 0) {
		// Si edition contact deja existant
		$object = new Contact($db);
		$res = $object->fetch($id, $user);
		if ($res < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}

		$object->fetchRoles();

		// Show tabs
		$head = contact_prepare_head($object);

		$title = (!empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT) ? $langs->trans("Contacts") : $langs->trans("ContactsAddresses"));
	}

	if ($user->rights->societe->contact->creer) {
		if ($action == 'create') {
			/*
			 * Fiche en mode creation
			 */
			$object->canvas = $canvas;

			$object->state_id = GETPOST("state_id");

			// We set country_id, country_code and label for the selected country
			$object->country_id = GETPOST("country_id") ? GETPOST("country_id", "int") : (empty($objsoc->country_id) ? $mysoc->country_id : $objsoc->country_id);
			if ($object->country_id) {
				$tmparray = getCountry($object->country_id, 'all');
				$object->country_code = $tmparray['code'];
				$object->country      = $tmparray['label'];
			}

			$title = (!empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT) ? $langs->trans("AddContact") : $langs->trans("AddContactAddress"));
			$linkback = '';
			print load_fiche_titre($title, $linkback, 'address');

			// Show errors
			dol_htmloutput_errors(is_numeric($error) ? '' : $error, $errors);

			if ($conf->use_javascript_ajax) {
				print "\n".'<script type="text/javascript" language="javascript">'."\n";
				print 'jQuery(document).ready(function () {
							jQuery("#selectcountry_id").change(function() {
								document.formsoc.action.value="create";
								document.formsoc.submit();
							});

							$("#copyaddressfromsoc").click(function() {
								$(\'textarea[name="address"]\').val("'.dol_escape_js($objsoc->address).'");
								$(\'input[name="zipcode"]\').val("'.dol_escape_js($objsoc->zip).'");
								$(\'input[name="town"]\').val("'.dol_escape_js($objsoc->town).'");
								console.log("Set state_id to '.dol_escape_js($objsoc->state_id).'");
								$(\'select[name="state_id"]\').val("'.dol_escape_js($objsoc->state_id).'").trigger("change");
								/* set country at end because it will trigger page refresh */
								console.log("Set country id to '.dol_escape_js($objsoc->country_id).'");
								$(\'select[name="country_id"]\').val("'.dol_escape_js($objsoc->country_id).'").trigger("change");   /* trigger required to update select2 components */
                            });
						})'."\n";
				print '</script>'."\n";
			}

			print '<form method="post" name="formsoc" action="'.$_SERVER["PHP_SELF"].'">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="add">';
			print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
			if (!empty($objsoc)) {
				print '<input type="hidden" name="entity" value="'.$objsoc->entity.'">';
			}

			print dol_get_fiche_head($head, 'card', '', 0, '');

			print '<table class="border centpercent">';

			// Name
			print '<tr><td class="titlefieldcreate fieldrequired"><label for="lastname">'.$langs->trans("Lastname").' / '.$langs->trans("Label").'</label></td>';
			print '<td colspan="3"><input name="lastname" id="lastname" type="text" class="maxwidth100onsmartphone" maxlength="80" value="'.dol_escape_htmltag(GETPOST("lastname", 'alpha') ?GETPOST("lastname", 'alpha') : $object->lastname).'" autofocus="autofocus"></td>';
			print '</tr>';

			print '<tr>';
			print '<td><label for="firstname">';
			print $form->textwithpicto($langs->trans("Firstname"), $langs->trans("KeepEmptyIfGenericAddress")).'</label></td>';
			print '<td colspan="3"><input name="firstname" id="firstname"type="text" class="maxwidth100onsmartphone" maxlength="80" value="'.dol_escape_htmltag(GETPOST("firstname", 'alpha') ?GETPOST("firstname", 'alpha') : $object->firstname).'"></td>';
			print '</tr>';

			// Company
			if (empty($conf->global->SOCIETE_DISABLE_CONTACTS)) {
				if ($socid > 0) {
					print '<tr><td><label for="socid">'.$langs->trans("ThirdParty").'</label></td>';
					print '<td colspan="3" class="maxwidthonsmartphone">';
					print $objsoc->getNomUrl(1, 'contact');
					print '</td>';
					print '<input type="hidden" name="socid" id="socid" value="'.$objsoc->id.'">';
					print '</td></tr>';
				} else {
					print '<tr><td><label for="socid">'.$langs->trans("ThirdParty").'</label></td><td colspan="3" class="maxwidthonsmartphone">';
					print img_picto('', 'company').$form->select_company($socid, 'socid', '', 'SelectThirdParty');
					print '</td></tr>';
				}
			}

			// Civility
			print '<tr><td><label for="civility_code">'.$langs->trans("UserTitle").'</label></td><td colspan="3">';
			print $formcompany->select_civility(GETPOSTISSET("civility_code") ? GETPOST("civility_code", 'alpha') : $object->civility_code, 'civility_code');
			print '</td></tr>';

			// Job position
			print '<tr><td><label for="title">'.$langs->trans("PostOrFunction").'</label></td>';
			print '<td colspan="3"><input name="poste" id="title" type="text" class="minwidth100" maxlength="255" value="'.dol_escape_htmltag(GETPOSTISSET("poste") ?GETPOST("poste", 'alphanohtml') : $object->poste).'"></td>';

			$colspan = 3;
			if ($conf->use_javascript_ajax && $socid > 0) {
				$colspan = 2;
			}

			// Address
			if (($objsoc->typent_code == 'TE_PRIVATE' || !empty($conf->global->CONTACT_USE_COMPANY_ADDRESS)) && dol_strlen(trim($object->address)) == 0) {
				$object->address = $objsoc->address; // Predefined with third party
			}
			print '<tr><td><label for="address">'.$langs->trans("Address").'</label></td>';
			print '<td colspan="'.$colspan.'"><textarea class="flat quatrevingtpercent" name="address" id="address" rows="'.ROWS_2.'">'.(GETPOST("address", 'alpha') ?GETPOST("address", 'alpha') : $object->address).'</textarea></td>';

			if ($conf->use_javascript_ajax && $socid > 0) {
				$rowspan = 3;
				if (empty($conf->global->SOCIETE_DISABLE_STATE)) {
					$rowspan++;
				}

				print '<td class="valignmiddle center" rowspan="'.$rowspan.'">';
				print '<a href="#" id="copyaddressfromsoc">'.$langs->trans('CopyAddressFromSoc').'</a>';
				print '</td>';
			}
			print '</tr>';

			// Zip / Town
			if (($objsoc->typent_code == 'TE_PRIVATE' || !empty($conf->global->CONTACT_USE_COMPANY_ADDRESS)) && dol_strlen(trim($object->zip)) == 0) {
				$object->zip = $objsoc->zip; // Predefined with third party
			}
			if (($objsoc->typent_code == 'TE_PRIVATE' || !empty($conf->global->CONTACT_USE_COMPANY_ADDRESS)) && dol_strlen(trim($object->town)) == 0) {
				$object->town = $objsoc->town; // Predefined with third party
			}
			print '<tr><td><label for="zipcode">'.$langs->trans("Zip").'</label> / <label for="town">'.$langs->trans("Town").'</label></td><td colspan="'.$colspan.'" class="maxwidthonsmartphone">';
			print $formcompany->select_ziptown((GETPOST("zipcode", 'alpha') ? GETPOST("zipcode", 'alpha') : $object->zip), 'zipcode', array('town', 'selectcountry_id', 'state_id'), 6).'&nbsp;';
			print $formcompany->select_ziptown((GETPOST("town", 'alpha') ? GETPOST("town", 'alpha') : $object->town), 'town', array('zipcode', 'selectcountry_id', 'state_id'));
			print '</td></tr>';

			// Country
			print '<tr><td><label for="selectcountry_id">'.$langs->trans("Country").'</label></td><td colspan="'.$colspan.'" class="maxwidthonsmartphone">';
			print img_picto('', 'globe-americas', 'class="paddingrightonly"');
			print $form->select_country((GETPOST("country_id", 'alpha') ? GETPOST("country_id", 'alpha') : $object->country_id), 'country_id');
			if ($user->admin) {
				print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
			}
			print '</td></tr>';

			// State
			if (empty($conf->global->SOCIETE_DISABLE_STATE)) {
				if (!empty($conf->global->MAIN_SHOW_REGION_IN_STATE_SELECT) && ($conf->global->MAIN_SHOW_REGION_IN_STATE_SELECT == 1 || $conf->global->MAIN_SHOW_REGION_IN_STATE_SELECT == 2)) {
					print '<tr><td><label for="state_id">'.$langs->trans('Region-State').'</label></td><td colspan="'.$colspan.'" class="maxwidthonsmartphone">';
				} else {
					print '<tr><td><label for="state_id">'.$langs->trans('State').'</label></td><td colspan="'.$colspan.'" class="maxwidthonsmartphone">';
				}

				if ($object->country_id) {
					print img_picto('', 'state', 'class="pictofixedwidth"');
					print $formcompany->select_state(GETPOST("state_id", 'alpha') ? GETPOST("state_id", 'alpha') : $object->state_id, $object->country_code, 'state_id');
				} else {
					print $countrynotdefined;
				}
				print '</td></tr>';
			}

			if (($objsoc->typent_code == 'TE_PRIVATE' || !empty($conf->global->CONTACT_USE_COMPANY_ADDRESS)) && dol_strlen(trim($object->phone_pro)) == 0) {
				$object->phone_pro = $objsoc->phone; // Predefined with third party
			}
			if (($objsoc->typent_code == 'TE_PRIVATE' || !empty($conf->global->CONTACT_USE_COMPANY_ADDRESS)) && dol_strlen(trim($object->fax)) == 0) {
				$object->fax = $objsoc->fax; // Predefined with third party
			}

			// Phone / Fax
			print '<tr><td>'.$form->editfieldkey('PhonePro', 'phone_pro', '', $object, 0).'</td>';
			print '<td>';
			print img_picto('', 'object_phoning');
			print '<input type="text" name="phone_pro" id="phone_pro" class="maxwidth200" value="'.(GETPOSTISSET('phone_pro') ? GETPOST('phone_pro', 'alpha') : $object->phone_pro).'"></td>';
			if ($conf->browser->layout == 'phone') {
				print '</tr><tr>';
			}
			print '<td>'.$form->editfieldkey('PhonePerso', 'phone_perso', '', $object, 0).'</td>';
			print '<td>';
			print img_picto('', 'object_phoning');
			print '<input type="text" name="phone_perso" id="phone_perso" class="maxwidth200" value="'.(GETPOSTISSET('phone_perso') ? GETPOST('phone_perso', 'alpha') : $object->phone_perso).'"></td></tr>';

			print '<tr><td>'.$form->editfieldkey('PhoneMobile', 'phone_mobile', '', $object, 0).'</td>';
			print '<td>';
			print img_picto('', 'object_phoning_mobile');
			print '<input type="text" name="phone_mobile" id="phone_mobile" class="maxwidth200" value="'.(GETPOSTISSET('phone_mobile') ? GETPOST('phone_mobile', 'alpha') : $object->phone_mobile).'"></td>';
			if ($conf->browser->layout == 'phone') {
				print '</tr><tr>';
			}
			print '<td>'.$form->editfieldkey('Fax', 'fax', '', $object, 0).'</td>';
			print '<td>';
			print img_picto('', 'object_phoning_fax');
			print '<input type="text" name="fax" id="fax" class="maxwidth200" value="'.(GETPOSTISSET('fax') ? GETPOST('fax', 'alpha') : $object->fax).'"></td>';
			print '</tr>';

			if (($objsoc->typent_code == 'TE_PRIVATE' || !empty($conf->global->CONTACT_USE_COMPANY_ADDRESS)) && dol_strlen(trim($object->email)) == 0) {
				$object->email = $objsoc->email; // Predefined with third party
			}

			// Email
			print '<tr><td>'.$form->editfieldkey('EMail', 'email', '', $object, 0, 'string', '').'</td>';
			print '<td>';
			print img_picto('', 'object_email');
			print '<input type="text" name="email" id="email" value="'.(GETPOSTISSET('email') ? GETPOST('email', 'alpha') : $object->email).'"></td>';
			print '</tr>';

			// Unsubscribe
			if (!empty($conf->mailing->enabled)) {
				if ($conf->use_javascript_ajax && $conf->global->MAILING_CONTACT_DEFAULT_BULK_STATUS == 2) {
					print "\n".'<script type="text/javascript" language="javascript">'."\n";
					print '$(document).ready(function () {
							$("#email").keyup(function() {
								if ($(this).val()!="") {
									$(".noemail").addClass("fieldrequired");
								} else {
									$(".noemail").removeClass("fieldrequired");
								}
							});
						})'."\n";
					print '</script>'."\n";
				}
				if (!GETPOSTISSET("no_email") && !empty($object->email)) {
					$result = $object->getNoEmail();
					if ($result < 0) {
						setEventMessages($object->error, $object->errors, 'errors');
					}
				}
				print '<tr>';
				print '<td class="noemail"><label for="no_email">'.$langs->trans("No_Email").'</label></td>';
				print '<td>';
				print $form->selectyesno('no_email', (GETPOSTISSET("no_email") ? GETPOST("no_email", 'int') : $conf->global->MAILING_CONTACT_DEFAULT_BULK_STATUS), 1, false, ($conf->global->MAILING_CONTACT_DEFAULT_BULK_STATUS == 2));
				print '</td>';
				print '</tr>';
			}


			if (!empty($conf->socialnetworks->enabled)) {
				foreach ($socialnetworks as $key => $value) {
					if ($value['active']) {
						print '<tr>';
						print '<td><label for="'.$value['label'].'">'.$form->editfieldkey($value['label'], $key, '', $object, 0).'</label></td>';
						print '<td colspan="3">';
						if (!empty($value['icon'])) {
							print '<span class="fa '.$value['icon'].'"></span>';
						}
						print '<input type="text" name="'.$key.'" id="'.$key.'" class="minwidth100" maxlength="80" value="'.dol_escape_htmltag(GETPOSTISSET($key) ?GETPOST($key, 'alphanohtml') : $object->socialnetworks[$key]).'">';
						print '</td>';
						print '</tr>';
					} elseif (!empty($object->socialnetworks[$key])) {
						print '<input type="hidden" name="'.$key.'" value="'.$object->socialnetworks[$key].'">';
					}
				}
			}

			// Visibility
			print '<tr><td><label for="priv">'.$langs->trans("ContactVisibility").'</label></td><td colspan="3">';
			$selectarray = array('0'=>$langs->trans("ContactPublic"), '1'=>$langs->trans("ContactPrivate"));
			print $form->selectarray('priv', $selectarray, (GETPOST("priv", 'alpha') ?GETPOST("priv", 'alpha') : $object->priv), 0);
			print '</td></tr>';

			// Categories
			if (!empty($conf->categorie->enabled) && !empty($user->rights->categorie->lire)) {
				print '<tr><td>'.$form->editfieldkey('Categories', 'contcats', '', $object, 0).'</td><td colspan="3">';
				$cate_arbo = $form->select_all_categories(Categorie::TYPE_CONTACT, null, 'parent', null, null, 1);
				print img_picto('', 'category').$form->multiselectarray('contcats', $cate_arbo, GETPOST('contcats', 'array'), null, null, null, null, '90%');
				print "</td></tr>";
			}

			// Contact by default
			if (!empty($socid)) {
				print '<tr><td>'.$langs->trans("ContactByDefaultFor").'</td>';
				print '<td colspan="3">';
				$contactType = $object->listeTypeContacts('external', '', 1);
				print $form->multiselectarray('roles', $contactType, array(), 0, 0, 'minwidth500');
				print '</td></tr>';
			}

			// Other attributes
			$parameters = array('socid' => $socid, 'objsoc' => $objsoc, 'colspan' => ' colspan="3"', 'cols' => 3, 'colspanvalue' => 3);
			include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

			print "</table><br>";

			print '<hr style="margin-bottom: 20px">';

			// Add personnal information
			print load_fiche_titre('<div class="comboperso">'.$langs->trans("PersonalInformations").'</div>', '', '');

			print '<table class="border centpercent">';

			// Date To Birth
			print '<tr><td><label for="birthday">'.$langs->trans("DateOfBirth").'</label></td><td>';
			$form = new Form($db);
			if ($object->birthday) {
				print $form->selectDate($object->birthday, 'birthday', 0, 0, 0, "perso", 1, 0);
			} else {
				print $form->selectDate('', 'birthday', 0, 0, 1, "perso", 1, 0);
			}
			print '</td>';

			print '<td><label for="birthday_alert">'.$langs->trans("Alert").'</label>: ';
			if ($object->birthday_alert) {
				print '<input type="checkbox" name="birthday_alert" id="birthday_alert" checked>';
			} else {
				print '<input type="checkbox" name="birthday_alert" id="birthday_alert">';
			}
			print '</td>';
			print '</tr>';

			print "</table>";

			print dol_get_fiche_end();

			print '<div class="center">';
			print '<input type="submit" class="button" name="add" value="'.$langs->trans("Add").'">';
			if (!empty($backtopage)) {
				print ' &nbsp; &nbsp; ';
				print '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
			} else {
				print ' &nbsp; &nbsp; ';
				print '<input type="button" class="button button-cancel" value="'.$langs->trans("Cancel").'" onClick="javascript:history.go(-1)">';
			}
			print '</div>';

			print "</form>";
		} elseif ($action == 'edit' && !empty($id)) {
			/*
			 * Fiche en mode edition
			 */

			// We set country_id, and country_code label of the chosen country
			if (GETPOSTISSET("country_id") || $object->country_id) {
				$tmparray = getCountry($object->country_id, 'all');
				$object->country_code = $tmparray['code'];
				$object->country      = $tmparray['label'];
			}

			$objsoc = new Societe($db);
			$objsoc->fetch($object->socid);

			// Show errors
			dol_htmloutput_errors(is_numeric($error) ? '' : $error, $errors);

			if ($conf->use_javascript_ajax) {
				print "\n".'<script type="text/javascript" language="javascript">'."\n";
				print 'jQuery(document).ready(function () {
							jQuery("#selectcountry_id").change(function() {
								document.formsoc.action.value="edit";
								document.formsoc.submit();
							});

							$("#copyaddressfromsoc").click(function() {
								$(\'textarea[name="address"]\').val("'.dol_escape_js($objsoc->address).'");
								$(\'input[name="zipcode"]\').val("'.dol_escape_js($objsoc->zip).'");
								$(\'input[name="town"]\').val("'.dol_escape_js($objsoc->town).'");
								console.log("Set state_id to '.dol_escape_js($objsoc->state_id).'");
								$(\'select[name="state_id"]\').val("'.dol_escape_js($objsoc->state_id).'").trigger("change");
								/* set country at end because it will trigger page refresh */
								console.log("Set country id to '.dol_escape_js($objsoc->country_id).'");
								$(\'select[name="country_id"]\').val("'.dol_escape_js($objsoc->country_id).'").trigger("change");   /* trigger required to update select2 components */
            				});
						})'."\n";
				print '</script>'."\n";
			}

			print '<form enctype="multipart/form-data" method="post" action="'.$_SERVER["PHP_SELF"].'?id='.$id.'" name="formsoc">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="id" value="'.$id.'">';
			print '<input type="hidden" name="action" value="update">';
			print '<input type="hidden" name="contactid" value="'.$object->id.'">';
			print '<input type="hidden" name="old_lastname" value="'.$object->lastname.'">';
			print '<input type="hidden" name="old_firstname" value="'.$object->firstname.'">';
			if (!empty($backtopage)) {
				print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
			}

			print dol_get_fiche_head($head, 'card', $title, 0, 'contact');

			print '<table class="border centpercent">';

			// Ref/ID
			if (!empty($conf->global->MAIN_SHOW_TECHNICAL_ID)) {
				print '<tr><td>'.$langs->trans("ID").'</td><td colspan="3">';
				print $object->ref;
				print '</td></tr>';
			}

			// Lastname
			print '<tr><td class="titlefieldcreate fieldrequired"><label for="lastname">'.$langs->trans("Lastname").' / '.$langs->trans("Label").'</label></td>';
			print '<td colspan="3"><input name="lastname" id="lastname" type="text" class="minwidth200" maxlength="80" value="'.(GETPOSTISSET("lastname") ? GETPOST("lastname") : $object->lastname).'" autofocus="autofocus"></td>';
			print '</tr>';
			print '<tr>';
			// Firstname
			print '<td><label for="firstname">'.$langs->trans("Firstname").'</label></td>';
			print '<td colspan="3"><input name="firstname" id="firstname" type="text" class="minwidth200" maxlength="80" value="'.(GETPOSTISSET("firstname") ? GETPOST("firstname") : $object->firstname).'"></td>';
			print '</tr>';

			// Company
			if (empty($conf->global->SOCIETE_DISABLE_CONTACTS)) {
				print '<tr><td><label for="socid">'.$langs->trans("ThirdParty").'</label></td>';
				print '<td colspan="3" class="maxwidthonsmartphone">';
				print img_picto('', 'company').$form->select_company(GETPOST('socid', 'int') ?GETPOST('socid', 'int') : ($object->socid ? $object->socid : -1), 'socid', '', $langs->trans("SelectThirdParty"));
				print '</td>';
				print '</tr>';
			}

			// Civility
			print '<tr><td><label for="civility_code">'.$langs->trans("UserTitle").'</label></td><td colspan="3">';
			print $formcompany->select_civility(GETPOSTISSET("civility_code") ? GETPOST("civility_code", "aZ09") : $object->civility_code, 'civility_code');
			print '</td></tr>';

			// Job position
			print '<tr><td><label for="title">'.$langs->trans("PostOrFunction").'</label></td>';
			print '<td colspan="3"><input name="poste" id="title" type="text" class="minwidth100" maxlength="255" value="'.dol_escape_htmltag(GETPOSTISSET("poste") ? GETPOST("poste", 'alphanohtml') : $object->poste).'"></td></tr>';

			// Address
			print '<tr><td><label for="address">'.$langs->trans("Address").'</label></td>';
			print '<td colspan="3">';
			print '<div class="paddingrightonly valignmiddle inline-block quatrevingtpercent">';
			print '<textarea class="flat minwidth200 centpercent" name="address" id="address">'.(GETPOSTISSET("address") ? GETPOST("address", 'nohtml') : $object->address).'</textarea>';
			print '</div><div class="paddingrightonly valignmiddle inline-block">';
			if ($conf->use_javascript_ajax) {
				print '<a href="#" id="copyaddressfromsoc">'.$langs->trans('CopyAddressFromSoc').'</a><br>';
			}
			print '</div>';
			print '</td>';

			// Zip / Town
			print '<tr><td><label for="zipcode">'.$langs->trans("Zip").'</label> / <label for="town">'.$langs->trans("Town").'</label></td><td colspan="3" class="maxwidthonsmartphone">';
			print $formcompany->select_ziptown((GETPOSTISSET("zipcode") ? GETPOST("zipcode") : $object->zip), 'zipcode', array('town', 'selectcountry_id', 'state_id'), 6).'&nbsp;';
			print $formcompany->select_ziptown((GETPOSTISSET("town") ? GETPOST("town") : $object->town), 'town', array('zipcode', 'selectcountry_id', 'state_id'));
			print '</td></tr>';

			// Country
			print '<tr><td><label for="selectcountry_id">'.$langs->trans("Country").'</label></td><td colspan="3" class="maxwidthonsmartphone">';
			print img_picto('', 'globe-americas', 'class="paddingrightonly"');
			print $form->select_country(GETPOSTISSET("country_id") ? GETPOST("country_id") : $object->country_id, 'country_id');
			if ($user->admin) {
				print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
			}
			print '</td></tr>';

			// State
			if (empty($conf->global->SOCIETE_DISABLE_STATE)) {
				if (!empty($conf->global->MAIN_SHOW_REGION_IN_STATE_SELECT) && ($conf->global->MAIN_SHOW_REGION_IN_STATE_SELECT == 1 || $conf->global->MAIN_SHOW_REGION_IN_STATE_SELECT == 2)) {
					print '<tr><td><label for="state_id">'.$langs->trans('Region-State').'</label></td><td colspan="3" class="maxwidthonsmartphone">';
				} else {
					print '<tr><td><label for="state_id">'.$langs->trans('State').'</label></td><td colspan="3" class="maxwidthonsmartphone">';
				}

				print img_picto('', 'state', 'class="pictofixedwidth"');
				print $formcompany->select_state(GETPOSTISSET('state_id') ? GETPOST('state_id', 'alpha') : $object->state_id, $object->country_code, 'state_id');
				print '</td></tr>';
			}

			// Phone
			print '<tr><td>'.$form->editfieldkey('PhonePro', 'phone_pro', GETPOST('phone_pro', 'alpha'), $object, 0).'</td>';
			print '<td>';
			print img_picto('', 'object_phoning');
			print '<input type="text" name="phone_pro" id="phone_pro" class="maxwidth200" maxlength="80" value="'.(GETPOSTISSET('phone_pro') ?GETPOST('phone_pro', 'alpha') : $object->phone_pro).'"></td>';
			print '<td>'.$form->editfieldkey('PhonePerso', 'fax', GETPOST('phone_perso', 'alpha'), $object, 0).'</td>';
			print '<td>';
			print img_picto('', 'object_phoning');
			print '<input type="text" name="phone_perso" id="phone_perso" class="maxwidth200" maxlength="80" value="'.(GETPOSTISSET('phone_perso') ?GETPOST('phone_perso', 'alpha') : $object->phone_perso).'"></td></tr>';

			print '<tr><td>'.$form->editfieldkey('PhoneMobile', 'phone_mobile', GETPOST('phone_mobile', 'alpha'), $object, 0, 'string', '').'</td>';
			print '<td>';
			print img_picto('', 'object_phoning_mobile');
			print '<input type="text" name="phone_mobile" id="phone_mobile" class="maxwidth200" maxlength="80" value="'.(GETPOSTISSET('phone_mobile') ?GETPOST('phone_mobile', 'alpha') : $object->phone_mobile).'"></td>';
			print '<td>'.$form->editfieldkey('Fax', 'fax', GETPOST('fax', 'alpha'), $object, 0).'</td>';
			print '<td>';
			print img_picto('', 'object_phoning_fax');
			print '<input type="text" name="fax" id="fax" class="maxwidth200" maxlength="80" value="'.(GETPOSTISSET('phone_fax') ?GETPOST('phone_fax', 'alpha') : $object->fax).'"></td></tr>';

			// EMail
			print '<tr><td>'.$form->editfieldkey('EMail', 'email', GETPOST('email', 'alpha'), $object, 0, 'string', '', (!empty($conf->global->SOCIETE_EMAIL_MANDATORY))).'</td>';
			print '<td>';
			print img_picto('', 'object_email');
			print '<input type="text" name="email" id="email" class="maxwidth100onsmartphone quatrevingtpercent" value="'.(GETPOSTISSET('email') ?GETPOST('email', 'alpha') : $object->email).'"></td>';
			if (!empty($conf->mailing->enabled)) {
				$langs->load("mails");
				print '<td class="nowrap">'.$langs->trans("NbOfEMailingsSend").'</td>';
				print '<td>'.$object->getNbOfEMailings().'</td>';
			} else {
				print '<td colspan="2"></td>';
			}
			print '</tr>';

			// Unsubscribe
			if (!empty($conf->mailing->enabled)) {
				if ($conf->use_javascript_ajax && isset($conf->global->MAILING_CONTACT_DEFAULT_BULK_STATUS) && $conf->global->MAILING_CONTACT_DEFAULT_BULK_STATUS == 2) {
					print "\n".'<script type="text/javascript" language="javascript">'."\n";

					print '
					jQuery(document).ready(function () {
						function init_check_no_email(input) {
							if (input.val()!="") {
								$(".noemail").addClass("fieldrequired");
							} else {
								$(".noemail").removeClass("fieldrequired");
							}
						}
						$("#email").keyup(function() {
							init_check_no_email($(this));
						});
						init_check_no_email($("#email"));
					})'."\n";
					print '</script>'."\n";
				}
				if (!GETPOSTISSET("no_email") && !empty($object->email)) {
					$result = $object->getNoEmail();
					if ($result < 0) {
						setEventMessages($object->error, $object->errors, 'errors');
					}
				}
				print '<tr>';
				print '<td class="noemail"><label for="no_email">'.$langs->trans("No_Email").'</label></td>';
				print '<td>';
				$useempty = (isset($conf->global->MAILING_CONTACT_DEFAULT_BULK_STATUS) && ($conf->global->MAILING_CONTACT_DEFAULT_BULK_STATUS == 2));
				print $form->selectyesno('no_email', (GETPOSTISSET("no_email") ? GETPOST("no_email", 'int') : $object->no_email), 1, false, $useempty);
				print '</td>';
				print '</tr>';
			}

			if (!empty($conf->socialnetworks->enabled)) {
				foreach ($socialnetworks as $key => $value) {
					if ($value['active']) {
						print '<tr>';
						print '<td><label for="'.$value['label'].'">'.$form->editfieldkey($value['label'], $key, '', $object, 0).'</label></td>';
						print '<td colspan="3">';
						if (!empty($value['icon'])) {
							print '<span class="fa '.$value['icon'].'"></span>';
						}
						print '<input type="text" name="'.$key.'" id="'.$key.'" class="minwidth100" maxlength="80" value="'.dol_escape_htmltag(GETPOSTISSET($key) ?GETPOST($key, 'alphanohtml') : (empty($object->socialnetworks[$key]) ? '' : $object->socialnetworks[$key])).'">';
						print '</td>';
						print '</tr>';
					} elseif (!empty($object->socialnetworks[$key])) {
						print '<input type="hidden" name="'.$key.'" value="'.$object->socialnetworks[$key].'">';
					}
				}
			}

			// Visibility
			print '<tr><td><label for="priv">'.$langs->trans("ContactVisibility").'</label></td><td colspan="3">';
			$selectarray = array('0'=>$langs->trans("ContactPublic"), '1'=>$langs->trans("ContactPrivate"));
			print $form->selectarray('priv', $selectarray, $object->priv, 0);
			print '</td></tr>';

			// Note Public
			print '<tr><td class="tdtop"><label for="note_public">'.$langs->trans("NotePublic").'</label></td><td colspan="3">';
			$doleditor = new DolEditor('note_public', $object->note_public, '', 80, 'dolibarr_notes', 'In', 0, false, empty($conf->global->FCKEDITOR_ENABLE_NOTE_PUBLIC) ? 0 : 1, ROWS_3, '90%');
			print $doleditor->Create(1);
			print '</td></tr>';

			// Note Private
			print '<tr><td class="tdtop"><label for="note_private">'.$langs->trans("NotePrivate").'</label></td><td colspan="3">';
			$doleditor = new DolEditor('note_private', $object->note_private, '', 80, 'dolibarr_notes', 'In', 0, false, empty($conf->global->FCKEDITOR_ENABLE_NOTE_PRIVATE) ? 0 : 1, ROWS_3, '90%');
			print $doleditor->Create(1);
			print '</td></tr>';

			// Status
			print '<tr><td>'.$langs->trans("Status").'</td>';
			print '<td colspan="3">';
			print $object->getLibStatut(4);
			print '</td></tr>';

			// Categories
			if (!empty($conf->categorie->enabled) && !empty($user->rights->categorie->lire)) {
				print '<tr><td>'.$form->editfieldkey('Categories', 'contcats', '', $object, 0).'</td>';
				print '<td colspan="3">';
				$cate_arbo = $form->select_all_categories(Categorie::TYPE_CONTACT, null, null, null, null, 1);
				$c = new Categorie($db);
				$cats = $c->containing($object->id, 'contact');
				foreach ($cats as $cat) {
					$arrayselected[] = $cat->id;
				}
				print img_picto('', 'category').$form->multiselectarray('contcats', $cate_arbo, $arrayselected, '', 0, '', 0, '90%');
				print "</td></tr>";
			}

			// Contact by default
			if (!empty($object->socid)) {
				print '<tr><td>'.$langs->trans("ContactByDefaultFor").'</td>';
				print '<td colspan="3">';
				print $formcompany->showRoles("roles", $object, 'edit', $object->roles);
				print '</td></tr>';
			}

			// Other attributes
			$parameters = array('colspan' => ' colspan="3"', 'cols'=> '3', 'colspanvalue'=> '3');
			include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';

			$object->load_ref_elements();

			if (!empty($conf->commande->enabled)) {
				print '<tr><td>'.$langs->trans("ContactForOrders").'</td><td colspan="3">';
				print $object->ref_commande ? $object->ref_commande : ('<span class="opacitymedium">'.$langs->trans("NoContactForAnyOrder").'</span>');
				print '</td></tr>';
			}

			if (!empty($conf->propal->enabled)) {
				print '<tr><td>'.$langs->trans("ContactForProposals").'</td><td colspan="3">';
				print $object->ref_propal ? $object->ref_propal : ('<span class="opacitymedium">'.$langs->trans("NoContactForAnyProposal").'</span>');
				print '</td></tr>';
			}

			if (!empty($conf->contrat->enabled)) {
				print '<tr><td>'.$langs->trans("ContactForContracts").'</td><td colspan="3">';
				print $object->ref_contrat ? $object->ref_contrat : ('<span class="opacitymedium">'.$langs->trans("NoContactForAnyContract").'</span>');
				print '</td></tr>';
			}

			if (!empty($conf->facture->enabled)) {
				print '<tr><td>'.$langs->trans("ContactForInvoices").'</td><td colspan="3">';
				print $object->ref_facturation ? $object->ref_facturation : ('<span class="opacitymedium">'.$langs->trans("NoContactForAnyInvoice").'</span>');
				print '</td></tr>';
			}

			// Login Dolibarr
			print '<tr><td>'.$langs->trans("DolibarrLogin").'</td><td colspan="3">';
			if ($object->user_id) {
				$dolibarr_user = new User($db);
				$result = $dolibarr_user->fetch($object->user_id);
				print $dolibarr_user->getLoginUrl(1);
			} else {
				print '<span class="opacitymedium">'.$langs->trans("NoDolibarrAccess").'</span>';
			}
			print '</td></tr>';

			// Photo
			print '<tr>';
			print '<td>'.$langs->trans("PhotoFile").'</td>';
			print '<td colspan="3">';
			if ($object->photo) {
				print $form->showphoto('contact', $object);
				print "<br>\n";
			}
			print '<table class="nobordernopadding">';
			if ($object->photo) {
				print '<tr><td><input type="checkbox" class="flat photodelete" name="deletephoto" id="photodelete"> '.$langs->trans("Delete").'<br><br></td></tr>';
			}
			//print '<tr><td>'.$langs->trans("PhotoFile").'</td></tr>';
			print '<tr><td><input type="file" class="flat" name="photo" id="photoinput"></td></tr>';
			print '</table>';

			print '</td>';
			print '</tr>';

			print '</table>';

			print dol_get_fiche_end();

			print '<div class="center">';
			print '<input type="submit" class="button button-save" name="save" value="'.$langs->trans("Save").'">';
			print ' &nbsp; &nbsp; ';
			print '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
			print '</div>';

			print "</form>";
		}
	}

	// Select mail models is same action as presend
	if (GETPOST('modelselected', 'alpha')) {
		$action = 'presend';
	}

	if (!empty($id) && $action != 'edit' && $action != 'create') {
		$objsoc = new Societe($db);

		// View mode

		// Show errors
		dol_htmloutput_errors(is_numeric($error) ? '' : $error, $errors);

		print dol_get_fiche_head($head, 'card', $title, -1, 'contact');

		if ($action == 'create_user') {
			// Full firstname and lastname separated with a dot : firstname.lastname
			include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
			$login = dol_buildlogin($object->lastname, $object->firstname);

			$generated_password = '';
			if (!$ldap_sid) { // TODO ldap_sid ?
				require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
				$generated_password = getRandomPassword(false);
			}
			$password = $generated_password;

			// Create a form array
			$formquestion = array(
				array('label' => $langs->trans("LoginToCreate"), 'type' => 'text', 'name' => 'login', 'value' => $login),
				array('label' => $langs->trans("Password"), 'type' => 'text', 'name' => 'password', 'value' => $password),
				//array('label' => $form->textwithpicto($langs->trans("Type"),$langs->trans("InternalExternalDesc")), 'type' => 'select', 'name' => 'intern', 'default' => 1, 'values' => array(0=>$langs->trans('Internal'),1=>$langs->trans('External')))
			);
			$text = $langs->trans("ConfirmCreateContact").'<br>';
			if (!empty($conf->societe->enabled)) {
				if ($object->socid > 0) {
					$text .= $langs->trans("UserWillBeExternalUser");
				} else {
					$text .= $langs->trans("UserWillBeInternalUser");
				}
			}
			print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id, $langs->trans("CreateDolibarrLogin"), $text, "confirm_create_user", $formquestion, 'yes');
		}

		$linkback = '<a href="'.DOL_URL_ROOT.'/contact/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

		$morehtmlref = '<div class="refidno">';
		if (empty($conf->global->SOCIETE_DISABLE_CONTACTS)) {
			$objsoc->fetch($object->socid);
			// Thirdparty
			$morehtmlref .= $langs->trans('ThirdParty').' : ';
			if ($objsoc->id > 0) {
				$morehtmlref .= $objsoc->getNomUrl(1, 'contact');
			} else {
				$morehtmlref .= '<span class="opacitymedium">'.$langs->trans("ContactNotLinkedToCompany").'</span>';
			}
		}
		$morehtmlref .= '</div>';

		dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref);


		print '<div class="fichecenter">';
		print '<div class="fichehalfleft">';

		print '<div class="underbanner clearboth"></div>';
		print '<table class="border tableforfield" width="100%">';

		// Civility
		print '<tr><td class="titlefield">'.$langs->trans("UserTitle").'</td><td>';
		print $object->getCivilityLabel();
		print '</td></tr>';

		// Job / position
		print '<tr><td>'.$langs->trans("PostOrFunction").'</td><td>'.$object->poste.'</td></tr>';

		// Email
		if (!empty($conf->mailing->enabled)) {
			$langs->load("mails");
			print '<tr><td>'.$langs->trans("NbOfEMailingsSend").'</td>';
			print '<td><a href="'.DOL_URL_ROOT.'/comm/mailing/list.php?filteremail='.urlencode($object->email).'">'.$object->getNbOfEMailings().'</a></td></tr>';
		}

		// Unsubscribe opt-out
		if (!empty($conf->mailing->enabled)) {
			$result = $object->getNoEmail();
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
			}
			print '<tr><td>'.$langs->trans("No_Email").'</td><td>';
			if ($object->email) {
				print yn($object->no_email);
			} else {
				print '<span class="opacitymedium">'.$langs->trans("EMailNotDefined").'</span>';
			}
			print '</td></tr>';
		}

		print '<tr><td>'.$langs->trans("ContactVisibility").'</td><td>';
		print $object->LibPubPriv($object->priv);
		print '</td></tr>';

		print '</table>';
		print '</div>';

		$object->fetch_thirdparty();

		if (!empty($conf->global->THIRDPARTY_ENABLE_PROSPECTION_ON_ALTERNATIVE_ADRESSES)) {
			if ($object->thirdparty->client == 2 || $object->thirdparty->client == 3) {
				print '<br>';

				print '<div class="underbanner clearboth"></div>';
				print '<table class="border" width="100%">';

				// Level of prospect
				print '<tr><td class="titlefield nowrap">';
				print '<table width="100%" class="nobordernopadding"><tr><td class="nowrap">';
				print $langs->trans('ProspectLevel');
				print '<td>';
				if ($action != 'editlevel' && $user->rights->societe->contact->creer) {
					print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editlevel&amp;id='.$object->id.'">'.img_edit($langs->trans('Modify'), 1).'</a></td>';
				}
				print '</tr></table>';
				print '</td><td>';
				if ($action == 'editlevel') {
					$formcompany->formProspectContactLevel($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_prospectlevel, 'prospect_contact_level_id', 1);
				} else {
					print $object->getLibProspLevel();
				}
				print "</td>";
				print '</tr>';

				// Status of prospection
				$object->loadCacheOfProspStatus();
				print '<tr><td>'.$langs->trans("StatusProsp").'</td><td>'.$object->getLibProspCommStatut(4, $object->cacheprospectstatus[$object->stcomm_id]['label']);
				print ' &nbsp; &nbsp; ';
				print '<div class="floatright">';
				foreach ($object->cacheprospectstatus as $key => $val) {
					$titlealt = 'default';
					if (!empty($val['code']) && !in_array($val['code'], array('ST_NO', 'ST_NEVER', 'ST_TODO', 'ST_PEND', 'ST_DONE'))) {
						$titlealt = $val['label'];
					}
					if ($object->stcomm_id != $val['id']) {
						print '<a class="pictosubstatus" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&stcomm='.$val['code'].'&action=setstcomm&token='.newToken().'">'.img_action($titlealt, $val['code'], $val['picto']).'</a>';
					}
				}
				print '</div></td></tr>';

				print "</table>";
				print '</div>';
			}
		}

		print '<div class="fichehalfright"><div class="ficheaddleft">';

		print '<div class="underbanner clearboth"></div>';
		print '<table class="border tableforfield" width="100%">';

		// Categories
		if (!empty($conf->categorie->enabled) && !empty($user->rights->categorie->lire)) {
			print '<tr><td class="titlefield">'.$langs->trans("Categories").'</td>';
			print '<td colspan="3">';
			print $form->showCategories($object->id, Categorie::TYPE_CONTACT, 1);
			print '</td></tr>';
		}

		if (!empty($object->socid)) {
			print '<tr><td class="titlefield">'.$langs->trans("ContactByDefaultFor").'</td>';
			print '<td colspan="3">';
			print $formcompany->showRoles("roles", $object, 'view', $object->roles);
			print '</td></tr>';
		}

		// Other attributes
		$cols = 3;
		$parameters = array('socid'=>$socid);
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

		$object->load_ref_elements();

		if (!empty($conf->propal->enabled)) {
			print '<tr><td class="titlefield">'.$langs->trans("ContactForProposals").'</td><td colspan="3">';
			print $object->ref_propal ? $object->ref_propal : $langs->trans("NoContactForAnyProposal");
			print '</td></tr>';
		}

		if (!empty($conf->commande->enabled) || !empty($conf->expedition->enabled)) {
			print '<tr><td>';
			if (!empty($conf->expedition->enabled)) {
				print $langs->trans("ContactForOrdersOrShipments");
			} else {
				print $langs->trans("ContactForOrders");
			}
			print '</td><td colspan="3">';
			$none = $langs->trans("NoContactForAnyOrder");
			if (!empty($conf->expedition->enabled)) {
				$none = $langs->trans("NoContactForAnyOrderOrShipments");
			}
			print $object->ref_commande ? $object->ref_commande : $none;
			print '</td></tr>';
		}

		if (!empty($conf->contrat->enabled)) {
			print '<tr><td>'.$langs->trans("ContactForContracts").'</td><td colspan="3">';
			print $object->ref_contrat ? $object->ref_contrat : $langs->trans("NoContactForAnyContract");
			print '</td></tr>';
		}

		if (!empty($conf->facture->enabled)) {
			print '<tr><td>'.$langs->trans("ContactForInvoices").'</td><td colspan="3">';
			print $object->ref_facturation ? $object->ref_facturation : $langs->trans("NoContactForAnyInvoice");
			print '</td></tr>';
		}

		print '<tr><td>'.$langs->trans("DolibarrLogin").'</td><td colspan="3">';
		if ($object->user_id) {
			$dolibarr_user = new User($db);
			$result = $dolibarr_user->fetch($object->user_id);
			print $dolibarr_user->getLoginUrl(1);
		} else {
			print $langs->trans("NoDolibarrAccess");
		}
		print '</td></tr>';

		print '<tr><td>';
		print $langs->trans("VCard").'</td><td colspan="3">';
		print '<a href="'.DOL_URL_ROOT.'/contact/vcard.php?id='.$object->id.'">';
		print img_picto($langs->trans("Download"), 'vcard.png', 'class="paddingrightonly"');
		print $langs->trans("Download");
		print '</a>';
		print '</td></tr>';

		print "</table>";

		print '</div></div></div>';
		print '<div style="clear:both"></div>';

		print dol_get_fiche_end();

		/*
		 * Action bar
		 */
		print '<div class="tabsAction">';

		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if (empty($reshook) && $action != 'presend') {
			if (empty($user->socid)) {
				if (!empty($object->email)) {
					$langs->load("mails");
					print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=presend&mode=init#formmailbeforetitle">'.$langs->trans('SendMail').'</a></div>';
				} else {
					$langs->load("mails");
					print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NoEMail")).'">'.$langs->trans('SendMail').'</a></div>';
				}
			}

			if ($user->rights->societe->contact->creer) {
				print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=edit">'.$langs->trans('Modify').'</a>';
			}

			if (!$object->user_id && $user->rights->user->user->creer) {
				print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=create_user">'.$langs->trans("CreateDolibarrLogin").'</a>';
			}

			// Activer
			if ($object->statut == 0 && $user->rights->societe->contact->creer) {
				print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=enable&token='.newToken().'">'.$langs->trans("Reactivate").'</a>';
			}
			// Desactiver
			if ($object->statut == 1 && $user->rights->societe->contact->creer) {
				print '<a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?action=disable&id='.$object->id.'&token='.newToken().'">'.$langs->trans("DisableUser").'</a>';
			}

			// Delete
			if ($user->rights->societe->contact->supprimer) {
				print '<a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=delete&token='.newToken().''.($backtopage ? '&backtopage='.urlencode($backtopage) : '').'">'.$langs->trans('Delete').'</a>';
			}
		}

		print "</div>";

		//Select mail models is same action as presend
		if (GETPOST('modelselected')) {
			$action = 'presend';
		}

		if ($action != 'presend') {
			print '<div class="fichecenter"><div class="fichehalfleft">';

			print '</div><div class="fichehalfright"><div class="ficheaddleft">';

			$MAXEVENT = 10;

			$morehtmlright = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-list-alt imgforviewmode', DOL_URL_ROOT.'/contact/agenda.php?id='.$object->id);

			// List of actions on element
			include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
			$formactions = new FormActions($db);
			$somethingshown = $formactions->showactions($object, 'contact', $object->socid, 1, '', $MAXEVENT, '', $morehtmlright); // Show all action for thirdparty

			print '</div></div></div>';
		}

		// Presend form
		$modelmail = 'contact';
		$defaulttopic = 'Information';
		$diroutput = $conf->societe->dir_output.'/contact/';
		$trackid = 'ctc'.$object->id;

		include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
	}
}


llxFooter();

$db->close();
