<?php
/* Copyright (C) 2004       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2018-2021  Frédéric France         <frederic.france@netlogic.fr>
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
 *       \file       htdocs/contact/perso.php
 *       \ingroup    societe
 *       \brief      Onglet informations personnelles d'un contact
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/contact.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('companies', 'other'));

$id = GETPOST('id', 'int');
$action = GETPOST('action', 'aZ09');

// Security check
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'contact', $id, 'socpeople&societe');
$object = new Contact($db);

/*
 * Action
 */

if ($action == 'update' && !GETPOST("cancel") && $user->rights->societe->contact->creer) {
	$ret = $object->fetch($id);

	// Note: Correct date should be completed with location to have exact GM time of birth.
	$object->birthday = dol_mktime(0, 0, 0, GETPOST("birthdaymonth"), GETPOST("birthdayday"), GETPOST("birthdayyear"));
	$object->birthday_alert = GETPOST("birthday_alert");

	if (GETPOST('deletephoto')) {
		$object->photo = '';
	} elseif (!empty($_FILES['photo']['name'])) {
		$object->photo = dol_sanitizeFileName($_FILES['photo']['name']);
	}

	$result = $object->update_perso($id, $user);
	if ($result > 0) {
		$object->oldcopy = clone $object;

		// Logo/Photo save
		$dir = $conf->societe->dir_output.'/contact/'.get_exdir($object->id, 0, 0, 1, $object, 'contact').'/photos';

		$file_OK = is_uploaded_file($_FILES['photo']['tmp_name']);
		if ($file_OK) {
			require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
			require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
			if (GETPOST('deletephoto')) {
				$fileimg = $conf->societe->dir_output.'/contact/'.get_exdir($object->id, 0, 0, 1, $object, 'contact').'/photos/'.$object->photo;
				$dirthumbs = $conf->societe->dir_output.'/contact/'.get_exdir($object->id, 0, 0, 1, $object, 'contact').'/photos/thumbs';
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
	} else {
		$error = $object->error;
	}
}


/*
 *	View
 */

$now = dol_now();

$title = (!empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT) ? $langs->trans("Contacts") : $langs->trans("ContactsAddresses"));
if (!empty($conf->global->MAIN_HTML_TITLE) && preg_match('/contactnameonly/', $conf->global->MAIN_HTML_TITLE) && $object->lastname) {
	$title = $object->lastname;
}
$help_url = 'EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('', $title, $help_url);

$form = new Form($db);

$object->fetch($id, $user);

$head = contact_prepare_head($object);

if ($action == 'edit') {
	/*
	 * Fiche en mode edition
	 */

	print '<form name="perso" method="POST" enctype="multipart/form-data" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';

	print dol_get_fiche_head($head, 'perso', $title, 0, 'contact');

	print '<table class="border centpercent">';

	// Ref
	print '<tr><td class="titlefieldcreate">'.$langs->trans("Ref").'</td><td colspan="3">';
	print $object->id;
	print '</td>';

	// Photo
	print '<td class="center hideonsmartphone valignmiddle" rowspan="6">';
	print $form->showphoto('contact', $object)."\n";
	if ($object->photo) {
		print "<br>\n";
	}

	print '<table class="nobordernopadding">';

	if ($object->photo) {
		print '<tr><td class="center"><input type="checkbox" class="flat photodelete" name="deletephoto" id="photodelete"> '.$langs->trans("Delete").'<br><br></td></tr>';
	}
	print '<tr><td>'.$langs->trans("PhotoFile").'</td></tr>';
	print '<tr><td>';
	$maxfilesizearray = getMaxFileSizeArray();
	$maxmin = $maxfilesizearray['maxmin'];
	if ($maxmin > 0) {
		print '<input type="hidden" name="MAX_FILE_SIZE" value="'.($maxmin * 1024).'">';	// MAX_FILE_SIZE must precede the field type=file
	}
	print '<input type="file" class="flat" name="photo" id="photoinput">';
	print '</td></tr>';
	print '</table>';

	print '</td></tr>';

	// Name
	print '<tr><td>'.$langs->trans("Lastname").' / '.$langs->trans("Label").'</td><td colspan="3">'.$object->lastname.'</td></tr>';
	print '<tr><td>'.$langs->trans("Firstname").'</td><td colspan="3">'.$object->firstname.'</td>';

	// Company
	if (empty($conf->global->SOCIETE_DISABLE_CONTACTS)) {
		if ($object->socid > 0) {
			$objsoc = new Societe($db);
			$objsoc->fetch($object->socid);

			print '<tr><td>'.$langs->trans("ThirdParty").'</td><td colspan="3">'.$objsoc->getNomUrl(1).'</td>';
		} else {
			print '<tr><td>'.$langs->trans("ThirdParty").'</td><td colspan="3">';
			print $langs->trans("ContactNotLinkedToCompany");
			print '</td></tr>';
		}
	}

	// Civility
	print '<tr><td>'.$langs->trans("UserTitle").'</td><td colspan="3">';
	print $object->getCivilityLabel();
	print '</td></tr>';

	// Date To Birth
	print '<tr><td>'.$langs->trans("DateOfBirth").'</td><td>';
	$form = new Form($db);
	print $form->selectDate($object->birthday, 'birthday', 0, 0, 1, "perso", 1, 0);
	print '</td>';

	print '<td colspan="2">'.$langs->trans("Alert").': ';
	if (!empty($object->birthday_alert)) {
		print '<input type="checkbox" name="birthday_alert" checked></td>';
	} else {
		print '<input type="checkbox" name="birthday_alert"></td>';
	}
	print '</tr>';

	print "</table>";

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel();

	print "</form>";
} else {
	// View mode

	print dol_get_fiche_head($head, 'perso', $title, -1, 'contact');

	$linkback = '<a href="'.DOL_URL_ROOT.'/contact/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<a href="'.DOL_URL_ROOT.'/contact/vcard.php?id='.$object->id.'" class="refid">';
	$morehtmlref .= img_picto($langs->trans("Download").' '.$langs->trans("VCard"), 'vcard.png', 'class="valignmiddle marginleftonly paddingrightonly"');
	$morehtmlref .= '</a>';

	$morehtmlref .= '<div class="refidno">';
	if (empty($conf->global->SOCIETE_DISABLE_CONTACTS)) {
		$objsoc = new Societe($db);
		$objsoc->fetch($object->socid);
		// Thirdparty
		$morehtmlref .= $langs->trans('ThirdParty').' : ';
		if ($objsoc->id > 0) {
			$morehtmlref .= $objsoc->getNomUrl(1);
		} else {
			$morehtmlref .= $langs->trans("ContactNotLinkedToCompany");
		}
	}
	$morehtmlref .= '</div>';


	dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref);


	print '<div class="fichecenter">';

	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">';

	// Company
	/*
	if (empty($conf->global->SOCIETE_DISABLE_CONTACTS))
	{
		if ($object->socid > 0)
		{
			$objsoc = new Societe($db);
			$objsoc->fetch($object->socid);

			print '<tr><td>'.$langs->trans("ThirdParty").'</td><td colspan="3">'.$objsoc->getNomUrl(1).'</td></tr>';
		}

		else
		{
			print '<tr><td>'.$langs->trans("ThirdParty").'</td><td colspan="3">';
			print $langs->trans("ContactNotLinkedToCompany");
			print '</td></tr>';
		}
	}*/

	// Civility
	print '<tr><td class="titlefield">'.$langs->trans("UserTitle").'</td><td colspan="3">';
	print $object->getCivilityLabel();
	print '</td></tr>';

	// Date To Birth
	print '<tr>';
	if (!empty($object->birthday)) {
		include_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

		print '<td>'.$langs->trans("DateOfBirth").'</td><td colspan="3">'.dol_print_date($object->birthday, "day");

		print ' &nbsp; ';
		//var_dump($birthdatearray);
		$ageyear = convertSecondToTime($now - $object->birthday, 'year') - 1970;
		$agemonth = convertSecondToTime($now - $object->birthday, 'month') - 1;
		if ($ageyear >= 2) {
			print '('.$ageyear.' '.$langs->trans("DurationYears").')';
		} elseif ($agemonth >= 2) {
			print '('.$agemonth.' '.$langs->trans("DurationMonths").')';
		} else {
			print '('.$agemonth.' '.$langs->trans("DurationMonth").')';
		}


		print ' &nbsp; - &nbsp; ';
		if ($object->birthday_alert) {
			print $langs->trans("BirthdayAlertOn");
		} else {
			print $langs->trans("BirthdayAlertOff");
		}
		print '</td>';
	} else {
		print '<td>'.$langs->trans("DateOfBirth").'</td><td colspan="3"></td>';
	}
	print "</tr>";

	print "</table>";

	print '</div>';

	print dol_get_fiche_end();
}


if ($action != 'edit') {
	/*
	 * Action bar
	 */
	if ($user->socid == 0) {
		print '<div class="tabsAction">';

		if ($user->rights->societe->contact->creer) {
			print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=edit&token='.newToken().'">'.$langs->trans('Modify').'</a>';
		}

		print "</div>";
	}
}


llxFooter();

$db->close();
