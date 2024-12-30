<?php
/* Copyright (C) 2001-2002  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2017  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2013       Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2015-2016  Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2018-2019  Thibault FOUCART        <support@ptibogxiv.net>
 * Copyright (C) 2018-2024  Frédéric France         <frederic.france@free.fr>
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
 *  \file       htdocs/don/card.php
 *  \ingroup    donations
 *  \brief      Page of donation card
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/dons/modules_don.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/donation.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/don/class/don.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmargin.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
if (isModEnabled('project')) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Societe $mysoc
 * @var Translate $langs
 * @var User $user
 */

$langs->loadLangs(array('bills', 'companies', 'donations', 'users'));

$id = GETPOST('rowid') ? GETPOSTINT('rowid') : GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$socid = GETPOSTINT('socid');
$amount = price2num(GETPOST('amount', 'alphanohtml'), 'MT');
$donation_date = dol_mktime(12, 0, 0, GETPOSTINT('remonth'), GETPOSTINT('reday'), GETPOSTINT('reyear'));
$projectid = (GETPOST('projectid') ? GETPOSTINT('projectid') : 0);
$public_donation = GETPOSTINT("public");

$object = new Don($db);
if ($id > 0 || $ref) {
	$object->fetch($id, $ref);
}

if (!empty($socid) && $socid > 0) {
	$soc = new Societe($db);
	if ($socid > 0) {
		$soc->fetch($socid);
	}
}

$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);
$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array($object->element.'card', 'globalcard'));

$upload_dir = $conf->don->dir_output;


// Security check
$result = restrictedArea($user, 'don', $object->id);

$permissiontoread = $user->hasRight('don', 'lire');
$permissiontoadd = $user->hasRight('don', 'creer');
$permissiontodelete = $user->hasRight('don', 'supprimer');


/*
 * Actions
 */

$error = 0;

$parameters = array();

$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	$backurlforlist = DOL_URL_ROOT.'/don/list.php';

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = DOL_URL_ROOT.'/don/card.php?id='.((!empty($id) && $id > 0) ? $id : '__ID__');
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

	// Action reopen object
	if ($action == 'confirm_reopen' && $confirm == 'yes' && $permissiontoadd) {
		$result = $object->reopen($user);
		if ($result >= 0) {
			// Define output language
			if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
				if (method_exists($object, 'generateDocument')) {
					$outputlangs = $langs;
					$newlang = '';
					if (getDolGlobalInt('MAIN_MULTILANGS') && GETPOST('lang_id', 'aZ09')) {
						$newlang = GETPOST('lang_id', 'aZ09');
					}
					if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
						$newlang = $object->thirdparty->default_lang;
					}
					if (!empty($newlang)) {
						$outputlangs = new Translate("", $conf);
						$outputlangs->setDefaultLang($newlang);
					}
					$model = $object->model_pdf;
					$ret = $object->fetch($id); // Reload to get new records
					$hidedetails = 0;
					$hidedesc = 0;
					$hideref = 0;
					$object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
				}
			}

			header("Location: ".$_SERVER["PHP_SELF"].'?id='.$object->id);
			exit;
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
			$action = 'create';
		}
	}


	// Action update object
	if ($action == 'update' && $permissiontoadd) {
		if (!empty($cancel)) {
			header("Location: ".$_SERVER['PHP_SELF']."?id=".urlencode((string) ($id)));
			exit;
		}

		if (empty($donation_date)) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Date")), null, 'errors');
			$action = "create";
			$error++;
		}

		if (empty($amount)) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Amount")), null, 'errors');
			$action = "create";
			$error++;
		}

		if (!$error) {
			$object->fetch($id);

			$object->firstname = GETPOST("firstname", 'alpha');
			$object->lastname = GETPOST("lastname", 'alpha');
			$object->societe = GETPOST("societe", 'alpha');
			$object->address = GETPOST("address", 'alpha');
			$object->amount = GETPOSTFLOAT("amount");
			$object->town = GETPOST("town", 'alpha');
			$object->zip = GETPOST("zipcode", 'alpha');
			$object->country_id = GETPOSTINT('country_id');
			$object->email = GETPOST("email", 'alpha');
			$object->date = $donation_date;
			$object->public = $public_donation;
			$object->fk_project = GETPOSTINT("fk_project");
			$object->modepaymentid = GETPOSTINT('modepayment');

			// Fill array 'array_options' with data from add form
			$ret = $extrafields->setOptionalsFromPost(null, $object, '@GETPOSTISSET');
			if ($ret < 0) {
				$error++;
			}

			if ($object->update($user) > 0) {
				header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
				exit;
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
				$action = "create";
			}
		}
	}


	// Action add/create object
	if ($action == 'add' && $permissiontoadd) {
		if (!empty($cancel)) {
			header("Location: index.php");
			exit;
		}

		if (isModEnabled("societe") && getDolGlobalString('DONATION_USE_THIRDPARTIES') && !(GETPOSTINT("socid") > 0)) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ThirdParty")), null, 'errors');
			$action = "create";
			$error++;
		}
		if (empty($donation_date)) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Date")), null, 'errors');
			$action = "create";
			$error++;
		}

		if (empty($amount)) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Amount")), null, 'errors');
			$action = "create";
			$error++;
		}

		if (!$error) {
			$object->socid = GETPOSTINT("socid");
			$object->firstname = (string) GETPOST("firstname", 'alpha');
			$object->lastname = (string) GETPOST("lastname", 'alpha');
			$object->societe = (string) GETPOST("societe", 'alpha');
			$object->address = (string) GETPOST("address", 'alpha');
			$object->amount = price2num(GETPOST("amount", 'alpha'), '', 2);
			$object->zip = (string) GETPOST("zipcode", 'alpha');
			$object->town = (string) GETPOST("town", 'alpha');
			$object->country_id = GETPOSTINT('country_id');
			$object->email = (string) GETPOST('email', 'alpha');
			$object->date = $donation_date;
			$object->note_private = (string) GETPOST("note_private", 'restricthtml');
			$object->note_public = (string) GETPOST("note_public", 'restricthtml');
			$object->public = $public_donation;
			$object->fk_project = GETPOSTINT("fk_project");
			$object->modepaymentid = GETPOSTINT('modepayment');

			// Fill array 'array_options' with data from add form
			$ret = $extrafields->setOptionalsFromPost(null, $object);
			if ($ret < 0) {
				$error++;
			}

			$res = $object->create($user);
			if ($res > 0) {
				header("Location: ".$_SERVER['PHP_SELF'].'?id='.$res);
				exit;
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
				$action = "create";
			}
		}
	}

	// Action delete object
	if ($action == 'confirm_delete' && GETPOST("confirm") == "yes" && $permissiontodelete) {
		$result = $object->delete($user);
		if ($result > 0) {
			header("Location: index.php");
			exit;
		} else {
			dol_syslog($object->error, LOG_DEBUG);
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// Action validation
	if ($action == 'valid_promesse' && $permissiontoadd) {
		// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
		if ($object->valid_promesse($id, $user->id) >= 0) {
			setEventMessages($langs->trans("DonationValidated", $object->ref), null);
			$action = '';
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// Action cancel
	if ($action == 'set_cancel' && $permissiontoadd) {
		if ($object->set_cancel($id) >= 0) {
			$action = '';
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// Action set paid
	if ($action == 'set_paid' && $permissiontoadd) {
		$modepayment = GETPOSTINT('modepayment');
		if ($object->setPaid($id, $modepayment) >= 0) {
			$action = '';
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} elseif ($action == 'classin' && $user->hasRight('don', 'creer')) {
		$object->setProject($projectid);
	}

	if ($action == 'update_extras' && $permissiontoadd) {
		$object->oldcopy = dol_clone($object, 2);

		// Fill array 'array_options' with data from update form
		$ret = $extrafields->setOptionalsFromPost(null, $object, GETPOST('attribute', 'restricthtml'));

		if ($ret < 0) {
			$error++;
		}

		if (!$error) {
			$result = $object->insertExtraFields('DON_MODIFY');
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
				$error++;
			}
		}

		if ($error) {
			$action = 'edit_extras';
		}
	}

	// Actions to build doc
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';
}


/*
 * View
 */

$bankaccountstatic = new Account($db);

$title = $langs->trans("Donation");

$help_url = 'EN:Module_Donations|FR:Module_Dons|ES:M&oacute;dulo_Donaciones|DE:Modul_Spenden';

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-donation page-card');

$form = new Form($db);
$formfile = new FormFile($db);
$formcompany = new FormCompany($db);
if (isModEnabled('project')) {
	$formproject = new FormProjets($db);
}

if ($action == 'create') {
	print load_fiche_titre($langs->trans("AddDonation"), '', 'object_donation');

	print '<form name="add" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

	print dol_get_fiche_head([]);

	print '<table class="border centpercent">';
	print '<tbody>';

	// Ref
	print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans('Ref').'</td><td>'.$langs->trans('Draft').'</td></tr>';

	// Company
	if (isModEnabled("societe") && getDolGlobalString('DONATION_USE_THIRDPARTIES')) {
		// Thirdparty
		if (!empty($soc) && $soc->id > 0) {
			print '<td class="fieldrequired">'.$langs->trans('ThirdParty').'</td>';
			print '<td>';
			print $soc->getNomUrl(1);
			print '<input type="hidden" name="socid" value="'.$soc->id.'">';
			// Outstanding Bill
			$arrayoutstandingbills = $soc->getOutstandingBills();
			$outstandingBills = $arrayoutstandingbills['opened'];
			print ' ('.$langs->trans('CurrentOutstandingBill').': ';
			print price($outstandingBills, 0, $langs, 0, 0, -1, $conf->currency);
			if ($soc->outstanding_limit != '') {
				if ($outstandingBills > $soc->outstanding_limit) {
					print img_warning($langs->trans("OutstandingBillReached"));
				}
				print ' / '.price($soc->outstanding_limit, 0, $langs, 0, 0, -1, $conf->currency);
			}
			print ')';
			print '</td>';
		} else {
			print '<td class="fieldrequired">'.$langs->trans('ThirdParty').'</td>';
			print '<td>';
			$filter = '((s.client:IN:1,2,3) AND (status:=:1))';
			print $form->select_company('', 'socid', $filter, 'SelectThirdParty', 0, 0, null, 0, 'minwidth300');
			// Option to reload page to retrieve customer information. Note, this clear other input
			if (getDolGlobalString('RELOAD_PAGE_ON_CUSTOMER_CHANGE_DISABLED')) {
				print '<script type="text/javascript">
				$(document).ready(function() {
					$("#socid").change(function() {
						console.log("We have changed the company - Reload page");
						var socid = $(this).val();
				        var fac_rec = $(\'#fac_rec\').val();
						// reload page
						$("input[name=action]").val("create");
						$("form[name=add]").submit();
					});
				});
				</script>';
			}
			print ' <a href="'.DOL_URL_ROOT.'/societe/card.php?action=create&client=3&fournisseur=0&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create').'"><span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("AddThirdParty").'"></span></a>';
			print '</td>';
		}
		print '</tr>'."\n";
	}

	// Date
	print '<tr><td class="fieldrequired titlefieldcreate">'.$langs->trans("Date").'</td><td>';
	print $form->selectDate($donation_date ? $donation_date : -1, '', 0, 0, 0, "add", 1, 1);
	print '</td>';

	// Amount
	print "<tr>".'<td class="fieldrequired">'.$langs->trans("Amount").'</td><td><input type="text" name="amount" value="'.dol_escape_htmltag(GETPOST("amount")).'" size="10"> '.$langs->trans("Currency".$conf->currency).'</td></tr>';

	// Public donation
	print '<tr><td class="fieldrequired">'.$langs->trans("PublicDonation")."</td><td>";
	print $form->selectyesno("public", $public_donation, 1);
	print "</td></tr>\n";

	if (!isModEnabled('societe') || !getDolGlobalString('DONATION_USE_THIRDPARTIES')) {
		print "<tr>".'<td>'.$langs->trans("Company").'</td><td><input type="text" name="societe" value="'.dol_escape_htmltag(GETPOST("societe")).'" class="maxwidth200"></td></tr>';
		print "<tr>".'<td>'.$langs->trans("Lastname").'</td><td><input type="text" name="lastname" value="'.dol_escape_htmltag(GETPOST("lastname")).'" class="maxwidth200"></td></tr>';
		print "<tr>".'<td>'.$langs->trans("Firstname").'</td><td><input type="text" name="firstname" value="'.dol_escape_htmltag(GETPOST("firstname")).'" class="maxwidth200"></td></tr>';
		print "<tr>".'<td>'.$langs->trans("Address").'</td><td>';
		print '<textarea name="address" wrap="soft" class="quatrevingtpercent" rows="3">'.dol_escape_htmltag(GETPOST("address", "alphanohtml"), 0, 1).'</textarea></td></tr>';

		// Zip / Town
		print '<tr><td>'.$langs->trans("Zip").' / '.$langs->trans("Town").'</td><td>';
		print $formcompany->select_ziptown((GETPOSTISSET("zipcode") ? GETPOST("zipcode") : $object->zip), 'zipcode', array('town', 'selectcountry_id', 'state_id'), 6);
		print ' ';
		print $formcompany->select_ziptown((GETPOSTISSET("town") ? GETPOST("town") : $object->town), 'town', array('zipcode', 'selectcountry_id', 'state_id'));
		print '</tr>';

		// Country
		print '<tr><td><label for="selectcountry_id">'.$langs->trans('Country').'</label></td><td class="maxwidthonsmartphone">';
		print img_picto('', 'globe-americas', 'class="paddingrightonly"').$form->select_country(GETPOST('country_id') != '' ? GETPOST('country_id') : $object->country_id);
		if ($user->admin) {
			print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
		}
		print '</td></tr>';

		print "<tr>".'<td>'.$langs->trans("EMail").'</td><td>'.img_picto('', 'object_email', 'class="paddingrightonly"').'<input type="text" name="email" value="'.dol_escape_htmltag(GETPOST("email")).'" class="maxwidth200"></td></tr>';
	}

	// Payment mode
	print "<tr><td>".$langs->trans("PaymentMode")."</td><td>\n";
	$selected = GETPOSTINT('modepayment');
	print img_picto('', 'payment', 'class="pictofixedwidth"');
	print $form->select_types_paiements($selected, 'modepayment', 'CRDT', 0, 1, 0, 0, 1, 'maxwidth200 widthcentpercentminusx', 1);
	print "</td></tr>\n";

	// Public note
	print '<tr>';
	print '<td class="tdtop">'.$langs->trans('NotePublic').'</td>';
	print '<td>';
	if (!isset($note_public)) {
		$note_public = $object->getDefaultCreateValueFor('note_public');
	}
	$doleditor = new DolEditor('note_public', $note_public, '', 80, 'dolibarr_notes', 'In', false, false, !getDolGlobalString('FCKEDITOR_ENABLE_NOTE_PUBLIC') ? 0 : 1, ROWS_3, '90%');
	print $doleditor->Create(1);
	print '</td></tr>';

	// Private note
	if (empty($user->socid)) {
		print '<tr>';
		print '<td class="tdtop">'.$langs->trans('NotePrivate').'</td>';
		print '<td>';
		if (!isset($note_private)) {
			$note_private = $object->getDefaultCreateValueFor('note_private');
		}
		$doleditor = new DolEditor('note_private', $note_private, '', 80, 'dolibarr_notes', 'In', false, false, !getDolGlobalString('FCKEDITOR_ENABLE_NOTE_PRIVATE') ? 0 : 1, ROWS_3, '90%');
		print $doleditor->Create(1);
		print '</td></tr>';
	}

	if (isModEnabled('project')) {
		print "<tr><td>".$langs->trans("Project")."</td><td>";
		print img_picto('', 'project', 'class="pictofixedwidth"');
		print $formproject->select_projects(-1, $projectid, 'fk_project', 0, 0, 1, 1, 0, 0, 0, '', 1, 0, 'maxwidth500');
		print "</td></tr>\n";
	}

	// Other attributes
	$parameters = array();
	$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	if (empty($reshook)) {
		print $object->showOptionals($extrafields, 'create', $parameters);
	}

	print '</tbody>';
	print "</table>\n";

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel();

	print "</form>\n";
}


/* ************************************************************ */
/*                                                              */
/* Donation card in edit mode                                   */
/*                                                              */
/* ************************************************************ */

if (!empty($id) && $action == 'edit') {
	$hselected = 'card';
	$head = donation_prepare_head($object);

	print '<form name="update" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="rowid" value="'.$object->id.'">';
	print '<input type="hidden" name="amount" value="'.$object->amount.'">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

	print dol_get_fiche_head($head, $hselected, $langs->trans("Donation"), 0, 'donation');

	print '<table class="border centpercent">';

	// Ref
	print '<tr><td>'.$langs->trans("Ref").'</td><td colspan="2">';
	print $object->getNomUrl();
	print '</td>';
	print '</tr>';

	// Date
	print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("Date").'</td><td>';
	print $form->selectDate($object->date, '', 0, 0, 0, "update");
	print '</td>';

	// Amount
	if ($object->status == 0) {
		print "<tr>".'<td class="fieldrequired">'.$langs->trans("Amount").'</td><td><input type="text" name="amount" size="10" value="'.price($object->amount).'"> '.$langs->trans("Currency".$conf->currency).'</td></tr>';
	} else {
		print '<tr><td>'.$langs->trans("Amount").'</td><td>';
		print price($object->amount, 0, $langs, 0, 0, -1, $conf->currency);
		print '</td></tr>';
	}

	print '<tr><td class="fieldrequired">'.$langs->trans("PublicDonation")."</td><td>";
	print $form->selectyesno("public", $object->public, 1);
	print "</td>";
	print "</tr>\n";

	if (isModEnabled("societe") && getDolGlobalString('DONATION_USE_THIRDPARTIES')) {
		$company = new Societe($db);

		print '<tr><td>'.$langs->trans("ThirdParty").'</td><td colspan="2">';
		if ($object->socid > 0) {
			$result = $company->fetch($object->socid);
			print $company->getNomUrl(1);
		}
		print '</td></tr>';
	} else {
		$langs->load("companies");
		print '<tr><td>'.$langs->trans("Company").'</td><td><input type="text" name="societe" class="maxwidth200" value="'.dol_escape_htmltag($object->societe).'"></td></tr>';
		print '<tr><td>'.$langs->trans("Lastname").'</td><td><input type="text" name="lastname" class="maxwidth200" value="'.dol_escape_htmltag($object->lastname).'"></td></tr>';
		print '<tr><td>'.$langs->trans("Firstname").'</td><td><input type="text" name="firstname" class="maxwidth200" value="'.dol_escape_htmltag($object->firstname).'"></td></tr>';
		print '<tr><td>'.$langs->trans("Address").'</td><td>';
		print '<textarea name="address" wrap="soft" class="quatrevingtpercent" rows="'.ROWS_3.'">'.dol_escape_htmltag($object->address, 0, 1).'</textarea></td></tr>';

		// Zip / Town
		print '<tr><td>'.$langs->trans("Zip").' / '.$langs->trans("Town").'</td><td>';
		print $formcompany->select_ziptown((GETPOSTISSET("zipcode") ? GETPOSTISSET("zipcode") : $object->zip), 'zipcode', array('town', 'selectcountry_id', 'state_id'), 6);
		print ' ';
		print $formcompany->select_ziptown((GETPOSTISSET("town") ? GETPOST("town") : $object->town), 'town', array('zipcode', 'selectcountry_id', 'state_id'));
		print '</tr>';

		// Country
		print '<tr><td class="titlefieldcreate">'.$langs->trans('Country').'</td><td>';
		print $form->select_country((!empty($object->country_id) ? $object->country_id : $mysoc->country_code), 'country_id');
		if ($user->admin) {
			print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
		}
		print '</td></tr>';

		print "<tr>".'<td>'.$langs->trans("EMail").'</td><td><input type="text" name="email" class="maxwidth200" value="'.dol_escape_htmltag($object->email).'"></td></tr>';
	}
	// Payment mode
	print "<tr><td>".$langs->trans("PaymentMode")."</td><td>\n";
	if ($object->mode_reglement_id) {
		$selected = $object->mode_reglement_id;
	} else {
		$selected = '';
	}
	$form->select_types_paiements($selected, 'modepayment', 'CRDT', 0, 1);
	print "</td></tr>\n";

	// Status
	print "<tr>".'<td>'.$langs->trans("Status").'</td><td>'.$object->getLibStatut(4).'</td></tr>';

	// Project
	if (isModEnabled('project')) {
		$formproject = new FormProjets($db);

		$langs->load('projects');
		print '<tr><td>'.$langs->trans('Project').'</td><td>';
		$formproject->select_projects(-1, $object->fk_project, 'fk_project', 0, 0, 1, 1, 0, 0, 0, '', 0, 0, 'maxwidth500');
		print '</td></tr>';
	}

	// Other attributes
	$parameters = array();
	$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	if (empty($reshook)) {
		print $object->showOptionals($extrafields, 'edit', $parameters);
	}

	print "</table>\n";

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel();

	print "</form>\n";
}



/* ************************************************************ */
/*                                                              */
/* Donation card in view mode                                   */
/*                                                              */
/* ************************************************************ */
if (!empty($id) && $action != 'edit') {
	$totalpaid = 0;
	$formconfirm = "";
	// Confirmation delete
	if ($action == 'delete') {
		$text = $langs->trans("ConfirmDeleteADonation");
		print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id, $langs->trans("DeleteADonation"), $text, "confirm_delete", '', '', 1);
	}

	$result = $object->fetch($id);
	if ($result < 0) {
		dol_print_error($db, $object->error);
		exit;
	}
	$result = $object->fetch_optionals();
	if ($result < 0) {
		dol_print_error($db);
		exit;
	}

	$hselected = 'card';

	$head = donation_prepare_head($object);
	print dol_get_fiche_head($head, $hselected, $langs->trans("Donation"), -1, 'donation');

	// Print form confirm
	print $formconfirm;

	$linkback = '<a href="'.DOL_URL_ROOT.'/don/list.php'.(!empty($socid) ? '?socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	// Project
	if (isModEnabled('project')) {
		$langs->load("projects");
		$morehtmlref .= $langs->trans('Project').' ';
		if ($user->hasRight('don', 'creer')) {
			if ($action != 'classify') {
				$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> : ';
			}
			if ($action == 'classify') {
				//$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
				$morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
				$morehtmlref .= '<input type="hidden" name="action" value="classin">';
				$morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
				$morehtmlref .= '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
				$morehtmlref .= $formproject->select_projects($object->socid, $object->fk_project, 'projectid', 0, 0, 1, 0, 1, 0, 0, '', 1, 0, 'maxwidth500');
				$morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
				$morehtmlref .= '</form>';
			} else {
				$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1, '', 'maxwidth300');
			}
		} else {
			if (!empty($object->fk_project)) {
				$proj = new Project($db);
				$proj->fetch($object->fk_project);
				$morehtmlref .= ' : '.$proj->getNomUrl(1);
				if ($proj->title) {
					$morehtmlref .= ' - '.$proj->title;
				}
			} else {
				$morehtmlref .= '';
			}
		}
	}
	$morehtmlref .= '</div>';


	dol_banner_tab($object, 'rowid', $linkback, 1, 'rowid', 'ref', $morehtmlref);


	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border tableforfield" width="100%">';

	// Date
	print '<tr><td class="titlefield">'.$langs->trans("Date").'</td><td colspan="2">';
	print dol_print_date($object->date, "day");
	print "</td>";

	print '<tr><td>'.$langs->trans("Amount").'</td><td colspan="2">';
	print price($object->amount, 0, $langs, 0, 0, -1, $conf->currency);
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("PublicDonation").'</td><td colspan="2">';
	print yn($object->public);
	print '</td></tr>';

	if (isModEnabled("societe") && getDolGlobalString('DONATION_USE_THIRDPARTIES')) {
		$company = new Societe($db);

		print '<tr><td>'.$langs->trans("ThirdParty").'</td><td colspan="2">';
		if ($object->socid > 0) {
			$result = $company->fetch($object->socid);
			print $company->getNomUrl(1);
		}
		print '</td></tr>';
	} else {
		print '<tr><td>'.$langs->trans("Company").'</td><td colspan="2">'.$object->societe.'</td></tr>';
		print '<tr><td>'.$langs->trans("Lastname").'</td><td colspan="2">'.$object->lastname.'</td></tr>';
		print '<tr><td>'.$langs->trans("Firstname").'</td><td colspan="2">'.$object->firstname.'</td></tr>';
	}

	// Payment mode
	print "<tr><td>".$langs->trans("PaymentMode")."</td><td>";
	$form->form_modes_reglement(null, $object->mode_reglement_id, 'none');
	print "</td></tr>\n";

	// Other attributes
	$cols = 2;
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';

	print '</div>';
	print '<div class="fichehalfright">';

	/*
	 * Payments
	 */
	$sql = "SELECT p.rowid, p.num_payment, p.datep as dp, p.amount,";
	$sql .= " c.code as type_code, c.libelle as paiement_type,";
	$sql .= " b.fk_account";
	$sql .= " FROM ".MAIN_DB_PREFIX."payment_donation as p";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank as b ON p.fk_bank = b.rowid";
	$sql .= ", ".MAIN_DB_PREFIX."c_paiement as c ";
	$sql .= ", ".MAIN_DB_PREFIX."don as d";
	$sql .= " WHERE d.rowid = ".((int) $id);
	$sql .= " AND p.fk_donation = d.rowid";
	$sql .= " AND d.entity IN (".getEntity('donation').")";
	$sql .= " AND p.fk_typepayment = c.id";
	$sql .= " ORDER BY dp";

	//print $sql;
	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;

		print '<table class="noborder paymenttable centpercent">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("RefPayment").'</td>';
		print '<td>'.$langs->trans("Date").'</td>';
		print '<td>'.$langs->trans("Type").'</td>';
		if (isModEnabled("bank")) {
			print '<td>'.$langs->trans("BankAccount").'</td>';
		}
		print '<td class="right">'.$langs->trans("Amount").'</td>';
		print '</tr>';

		while ($i < $num) {
			$objp = $db->fetch_object($resql);

			print '<tr class="oddeven"><td>';
			print '<a href="'.DOL_URL_ROOT.'/don/payment/card.php?id='.$objp->rowid.'">'.img_object($langs->trans("Payment"), "payment").' '.$objp->rowid.'</a></td>';
			print '<td>'.dol_print_date($db->jdate($objp->dp), 'day')."</td>\n";
			$labeltype = ($langs->trans("PaymentType".$objp->type_code) != "PaymentType".$objp->type_code) ? $langs->trans("PaymentType".$objp->type_code) : $objp->paiement_type;
			print "<td>".$labeltype.' '.$objp->num_payment."</td>\n";
			if (isModEnabled("bank")) {
				$bankaccountstatic->fetch($objp->fk_account);
				/*$bankaccountstatic->id = $objp->fk_bank;
				$bankaccountstatic->ref = $objp->baref;
				$bankaccountstatic->label = $objp->baref;
				$bankaccountstatic->number = $objp->banumber;
				$bankaccountstatic->currency_code = $objp->bacurrency_code;

				if (isModEnabled('accounting')) {
					$bankaccountstatic->account_number = $objp->account_number;

					$accountingjournal = new AccountingJournal($db);
					$accountingjournal->fetch($objp->fk_accountancy_journal);
					$bankaccountstatic->accountancy_journal = $accountingjournal->getNomUrl(0, 1, 1, '', 1);
				}
				*/
				print '<td class="nowraponall">';
				if ($bankaccountstatic->id) {
					print $bankaccountstatic->getNomUrl(1, 'transactions');
				}
				print '</td>';
			}
			print '<td class="right">'.price($objp->amount)."</td>\n";
			print "</tr>";
			$totalpaid += $objp->amount;
			$i++;
		}

		if ($object->paid == 0) {
			$colspan = 3;
			if (isModEnabled("bank")) {
				$colspan++;
			}
			print '<tr><td colspan="'.$colspan.'" class="right">'.$langs->trans("AlreadyPaid").' :</td><td class="right">'.price($totalpaid)."</td></tr>\n";
			print '<tr><td colspan="'.$colspan.'" class="right">'.$langs->trans("AmountExpected").' :</td><td class="right">'.price($object->amount)."</td></tr>\n";

			$remaintopay = $object->amount - $totalpaid;
			$resteapayeraffiche = $remaintopay;

			print '<tr><td colspan="'.$colspan.'" class="right">'.$langs->trans("RemainderToPay")." :</td>";
			print '<td class="right'.(!empty($resteapayeraffiche) ? ' amountremaintopay' : '').'">'.price($remaintopay)."</td></tr>\n";
		}
		print "</table>";
		$db->free($resql);
	} else {
		dol_print_error($db);
	}

	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();

	$remaintopay = $object->amount - $totalpaid;

	// Actions buttons

	print '<div class="tabsAction">';
	$parameters = array();
	$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action);
	if (empty($reshook)) {
		// Re-open
		if ($permissiontoadd && $object->status == $object::STATUS_CANCELED) {
			print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=confirm_reopen&confirm=yes&token='.newToken().'">'.$langs->trans("ReOpen").'</a>';
		}

		print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&token='.newToken().'&rowid='.$object->id.'">'.$langs->trans('Modify').'</a></div>';

		if ($object->status == $object::STATUS_DRAFT) {
			print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?rowid='.$object->id.'&action=valid_promesse&token='.newToken().'">'.$langs->trans("ValidPromess").'</a></div>';
		}

		if (($object->status == $object::STATUS_DRAFT || $object->status == $object::STATUS_VALIDATED) && $totalpaid == 0 && $object->paid == 0) {
			print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?rowid='.$object->id.'&action=set_cancel&token='.newToken().'">'.$langs->trans("ClassifyCanceled")."</a></div>";
		}

		// Create payment
		if ($object->status == $object::STATUS_VALIDATED && $object->paid == 0 && $user->hasRight('don', 'creer')) {
			if ($remaintopay == 0) {
				print '<div class="inline-block divButAction"><span class="butActionRefused classfortooltip" title="'.$langs->trans("DisabledBecauseRemainderToPayIsZero").'">'.$langs->trans('DoPayment').'</span></div>';
			} else {
				print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/don/payment/payment.php?rowid='.$object->id.'&action=create&token='.newToken().'">'.$langs->trans('DoPayment').'</a></div>';
			}
		}

		// Classify 'paid'
		if ($object->status == $object::STATUS_VALIDATED && round($remaintopay) == 0 && $object->paid == 0 && $user->hasRight('don', 'creer')) {
			print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?rowid='.$object->id.'&action=set_paid&token='.newToken().'">'.$langs->trans("ClassifyPaid")."</a></div>";
		}

		// Delete
		if ($user->hasRight('don', 'supprimer')) {
			if ($object->status == $object::STATUS_CANCELED || $object->status == $object::STATUS_DRAFT) {
				print '<div class="inline-block divButAction"><a class="butActionDelete" href="card.php?rowid='.$object->id.'&action=delete&token='.newToken().'">'.$langs->trans("Delete")."</a></div>";
			} else {
				print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("CantRemovePaymentWithOneInvoicePaid").'">'.$langs->trans("Delete")."</a></div>";
			}
		} else {
			print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#">'.$langs->trans("Delete")."</a></div>";
		}
	}

	print "</div>";


	print '<div class="fichecenter"><div class="fichehalfleft">';

	/*
	 * Generated documents
	 */
	$filename = dol_sanitizeFileName((string) $object->id);
	$filedir = $conf->don->dir_output."/".dol_sanitizeFileName((string) $object->id);
	$urlsource = $_SERVER['PHP_SELF'].'?rowid='.$object->id;
	$genallowed	= (($object->paid == 0 || $user->admin) && $user->hasRight('don', 'lire'));
	$delallowed	= $user->hasRight('don', 'creer');

	print $formfile->showdocuments('donation', $filename, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf);

	// Show links to link elements
	$tmparray = $form->showLinkToObjectBlock($object, array(), array('don'), 1);
	$linktoelem = $tmparray['linktoelem'];
	$htmltoenteralink = $tmparray['htmltoenteralink'];
	print $htmltoenteralink;

	$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);

	// Show online payment link
	// The list can be complete by the hook 'doValidatePayment' executed inside getValidOnlinePaymentMethods()
	include_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
	$validpaymentmethod = getValidOnlinePaymentMethods('');
	$useonlinepayment = count($validpaymentmethod);

	if ($useonlinepayment) { //$object->statut != Facture::STATUS_DRAFT &&
		print '<br><!-- Link to pay -->'."\n";
		require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
		print showOnlinePaymentUrl('donation', $object->ref).'<br>';
	}

	print '</div><div class="fichehalfright">';

	print '</div></div>';
}

llxFooter();
$db->close();
