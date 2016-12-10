<?php
/* Copyright (C) 2014 Florian Henry        <florian.henry@open-concept.pro>
 * Copyright (C) 2016 Laurent Destailleur  <eldy@uers.sourceforge.net>
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
 *       \file       htdocs/comm/mailing/advtargetemailing.php
 *       \ingroup    mailing
 *       \brief      Page to define emailing targets
 */

require '../../main.inc.php';

require_once DOL_DOCUMENT_ROOT . '/comm/mailing/class/mailing.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/emailing.lib.php';
require_once DOL_DOCUMENT_ROOT . '/comm/mailing/class/advtargetemailing.class.php';
require_once DOL_DOCUMENT_ROOT . '/comm/mailing/class/html.formadvtargetemailing.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/modules/mailings/advthirdparties.modules.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';

// Translations
$langs->load("mails");
$langs->load("companies");
if (! empty($conf->categorie->enabled)) {
	$langs->load("categories");
}

// Security check
if (! $user->rights->mailing->lire || $user->societe_id > 0)
	accessforbidden();

$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOST("page", 'int');
if ($page == - 1) {
	$page = 0;
}
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder)
	$sortorder = "ASC";
if (! $sortfield)
	$sortfield = "email";

$id = GETPOST('id', 'int');
$rowid = GETPOST('rowid', 'int');
$action = GETPOST("action");
$search_nom = GETPOST("search_nom");
$search_prenom = GETPOST("search_prenom");
$search_email = GETPOST("search_email");
$template_id = GETPOST('template_id', 'int');

// Do we click on purge search criteria ?
if (GETPOST("button_removefilter_x")) {
	$search_nom = '';
	$search_prenom = '';
	$search_email = '';
}

$array_query = array ();

$object = new Mailing($db);
$advTarget = new AdvanceTargetingMailing($db);
if (empty($template_id)) {
	$advTarget->fk_mailing = $id;
	$result = $advTarget->fetch_by_mailing();
} else {
	$result = $advTarget->fetch($template_id);
}

if ($result < 0) {
	setEventMessage($advTarget->error, 'errors');
} else {
	if (! empty($advTarget->id)) {
		$array_query = json_decode($advTarget->filtervalue, true);
	}
}


/*
 * Actions
 */

if ($action == 'loadfilter') {
	if (! empty($template_id)) {
		$result = $advTarget->fetch($template_id);
		if ($result < 0) {
			setEventMessage($advTarget->error, 'errors');
		} else {
			if (! empty($advTarget->id)) {
				$array_query = json_decode($advTarget->filtervalue, true);
			}
		}
	}
}

if ($action == 'add') {

	$user_contact_query = false;

	$array_query = array ();

	// Get extra fields

	foreach ( $_POST as $key => $value ) {
		// print '$key='.$key.' $value='.$value.'<BR>';
		if (preg_match("/^options_.*(?<!_cnct)$/", $key)) {
			// Special case for start date come with 3 inputs day, month, year
			if (preg_match("/st_dt/", $key)) {
				$dtarr = array ();
				$dtarr = explode('_', $key);
				if (! array_key_exists('options_' . $dtarr[1] . '_st_dt', $array_query)) {
					$array_query['options_' . $dtarr[1] . '_st_dt'] = dol_mktime(0, 0, 0, GETPOST('options_' . $dtarr[1] . '_st_dtmonth', 'int'), GETPOST('options_' . $dtarr[1] . '_st_dtday', 'int'), GETPOST('options_' . $dtarr[1] . '_st_dtyear', 'int'));
				}
			} elseif (preg_match("/end_dt/", $key)) {
				// Special case for end date come with 3 inputs day, month, year
				$dtarr = array ();
				$dtarr = explode('_', $key);
				if (! array_key_exists('options_' . $dtarr[1] . '_end_dt', $array_query)) {
					$array_query['options_' . $dtarr[1] . '_end_dt'] = dol_mktime(0, 0, 0, GETPOST('options_' . $dtarr[1] . '_end_dtmonth', 'int'), GETPOST('options_' . $dtarr[1] . '_end_dtday', 'int'), GETPOST('options_' . $dtarr[1] . '_end_dtyear', 'int'));
				}
			} else {
				$array_query[$key] = GETPOST($key);
			}
		}
		if (preg_match("/^options_.*_cnct/", $key)) {
			$user_contact_query = true;
			// Special case for start date come with 3 inputs day, month, year
			if (preg_match("/st_dt/", $key)) {
				$dtarr = array ();
				$dtarr = explode('_', $key);
				if (! array_key_exists('options_' . $dtarr[1] . '_st_dt' . '_cnct', $array_query)) {
					$array_query['options_' . $dtarr[1] . '_st_dt' . '_cnct'] = dol_mktime(0, 0, 0, GETPOST('options_' . $dtarr[1] . '_st_dtmonth' . '_cnct', 'int'), GETPOST('options_' . $dtarr[1] . '_st_dtday' . '_cnct', 'int'), GETPOST('options_' . $dtarr[1] . '_st_dtyear' . '_cnct', 'int'));
				}
			} elseif (preg_match("/end_dt/", $key)) {
				// Special case for end date come with 3 inputs day, month, year
				$dtarr = array ();
				$dtarr = explode('_', $key);
				if (! array_key_exists('options_' . $dtarr[1] . '_end_dt' . '_cnct', $array_query)) {
					$array_query['options_' . $dtarr[1] . '_end_dt' . '_cnct'] = dol_mktime(0, 0, 0, GETPOST('options_' . $dtarr[1] . '_end_dtmonth' . '_cnct', 'int'), GETPOST('options_' . $dtarr[1] . '_end_dtday' . '_cnct', 'int'), GETPOST('options_' . $dtarr[1] . '_end_dtyear' . '_cnct', 'int'));
				}
			} else {
				$array_query[$key] = GETPOST($key);
			}
		}

		if (preg_match("/^cust_/", $key)) {
			$array_query[$key] = GETPOST($key);
		}

		if (preg_match("/^contact_/", $key)) {

			$array_query[$key] = GETPOST($key);

			$specials_date_key = array (
					'contact_update_st_dt',
					'contact_update_end_dt',
					'contact_create_st_dt',
					'contact_create_end_dt'
			);
			foreach ( $specials_date_key as $date_key ) {
				if ($key == $date_key) {
					$dt = GETPOST($date_key);
					if (! empty($dt)) {
						$array_query[$key] = dol_mktime(0, 0, 0, GETPOST($date_key . 'month', 'int'), GETPOST($date_key . 'day', 'int'), GETPOST($date_key . 'year', 'int'));
					} else {
						$array_query[$key] = '';
					}
				}
			}

			if (! empty($array_query[$key])) {
				$user_contact_query = true;
			}
		}

		if ($array_query['type_of_target'] == 2 || $array_query['type_of_target'] == 4) {
			$user_contact_query = true;
		}

		if (preg_match("/^type_of_target/", $key)) {
			$array_query[$key] = GETPOST($key);
		}
	}

	// if ($array_query ['type_of_target'] == 1 || $array_query ['type_of_target'] == 3) {
	$result = $advTarget->query_thirdparty($array_query);
	if ($result < 0) {
		setEventMessage($advTarget->error, 'errors');
	}
	/*} else {
		$advTarget->thirdparty_lines = array ();
	}*/

	if ($user_contact_query && ($array_query['type_of_target'] == 1 || $array_query['type_of_target'] == 2 || $array_query['type_of_target'] == 4)) {
		$result = $advTarget->query_contact($array_query, 1);
		if ($result < 0) {
			setEventMessage($advTarget->error, 'errors');
		}
		// If use contact but no result use artefact to so not use socid into add_to_target
		if (count($advTarget->contact_lines) == 0) {
			$advTarget->contact_lines = array (
					0
			);
		}
	} else {
		$advTarget->contact_lines = array ();
	}

	if ((count($advTarget->thirdparty_lines) > 0) || (count($advTarget->contact_lines) > 0)) {
		// Add targets into database
		$obj = new mailing_advthirdparties($db);
		$result = $obj->add_to_target_spec($id, $advTarget->thirdparty_lines, $array_query['type_of_target'], $advTarget->contact_lines);
	} else {
		$result = 0;
	}

	if ($result > 0) {
		$query_temlate_id = '';
		if (! empty($template_id)) {
			$query_temlate_id = '&template_id=' . $template_id;
		}
		header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id . $query_temlate_id);
		exit();
	}
	if ($result == 0) {
		setEventMessage($langs->trans("WarningNoEMailsAdded"), 'warnings');
	}
	if ($result < 0) {
		setEventMessage($obj->error, 'errors');
	}
}

if ($action == 'clear') {
	// Chargement de la classe
	$classname = "MailingTargets";
	$obj = new $classname($db);
	$obj->clear_target($id);

	header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id);
	exit();
}

if ($action == 'savefilter' || $action == 'createfilter') {

	$template_name = GETPOST('template_name');
	$error = 0;

	if ($action == 'createfilter' && empty($template_name)) {
		setEventMessage($langs->trans('ErrorFieldRequired', $langs->trans('AdvTgtOrCreateNewFilter')), 'errors');
		$error ++;
	}

	if (empty($error)) {

		$array_query = array ();

		// Get extra fields
		foreach ( $_POST as $key => $value ) {
			if (preg_match("/^options_.*(?<!_cnct)$/", $key)) {
				// Special case for start date come with 3 inputs day, month, year
				if (preg_match("/st_dt/", $key)) {
					$dtarr = array ();
					$dtarr = explode('_', $key);
					if (! array_key_exists('options_' . $dtarr[1] . '_st_dt', $array_query)) {
						$array_query['options_' . $dtarr[1] . '_st_dt'] = dol_mktime(0, 0, 0, GETPOST('options_' . $dtarr[1] . '_st_dtmonth', 'int'), GETPOST('options_' . $dtarr[1] . '_st_dtday', 'int'), GETPOST('options_' . $dtarr[1] . '_st_dtyear', 'int'));
					}
				} elseif (preg_match("/end_dt/", $key)) {
					// Special case for end date come with 3 inputs day, month, year
					$dtarr = array ();
					$dtarr = explode('_', $key);
					if (! array_key_exists('options_' . $dtarr[1] . '_end_dt', $array_query)) {
						$array_query['options_' . $dtarr[1] . '_end_dt'] = dol_mktime(0, 0, 0, GETPOST('options_' . $dtarr[1] . '_end_dtmonth', 'int'), GETPOST('options_' . $dtarr[1] . '_end_dtday', 'int'), GETPOST('options_' . $dtarr[1] . '_end_dtyear', 'int'));
						// print $array_query['options_'.$dtarr[1].'_end_dt'];
						// 01/02/1013=1361228400
					}
				} else {
					$array_query[$key] = GETPOST($key);
				}
			}
			if (preg_match("/^options_.*_cnct/", $key)) {
				// Special case for start date come with 3 inputs day, month, year
				if (preg_match("/st_dt/", $key)) {
					$dtarr = array ();
					$dtarr = explode('_', $key);
					if (! array_key_exists('options_' . $dtarr[1] . '_st_dt' . '_cnct', $array_query)) {
						$array_query['options_' . $dtarr[1] . '_st_dt' . '_cnct'] = dol_mktime(0, 0, 0, GETPOST('options_' . $dtarr[1] . '_st_dtmonth' . '_cnct', 'int'), GETPOST('options_' . $dtarr[1] . '_st_dtday' . '_cnct', 'int'), GETPOST('options_' . $dtarr[1] . '_st_dtyear' . '_cnct', 'int'));
					}
				} elseif (preg_match("/end_dt/", $key)) {
					// Special case for end date come with 3 inputs day, month, year
					$dtarr = array ();
					$dtarr = explode('_', $key);
					if (! array_key_exists('options_' . $dtarr[1] . '_end_dt' . '_cnct', $array_query)) {
						$array_query['options_' . $dtarr[1] . '_end_dt' . '_cnct'] = dol_mktime(0, 0, 0, GETPOST('options_' . $dtarr[1] . '_end_dtmonth' . '_cnct', 'int'), GETPOST('options_' . $dtarr[1] . '_end_dtday' . '_cnct', 'int'), GETPOST('options_' . $dtarr[1] . '_end_dtyear' . '_cnct', 'int'));
						// print $array_query['cnct_options_'.$dtarr[1].'_end_dt'];
						// 01/02/1013=1361228400
					}
				} else {
					$array_query[$key] = GETPOST($key);
				}
			}

			if (preg_match("/^cust_/", $key)) {
				$array_query[$key] = GETPOST($key);
			}

			if (preg_match("/^contact_/", $key)) {

				$array_query[$key] = GETPOST($key);

				$specials_date_key = array (
						'contact_update_st_dt',
						'contact_update_end_dt',
						'contact_create_st_dt',
						'contact_create_end_dt'
				);
				foreach ( $specials_date_key as $date_key ) {
					if ($key == $date_key) {
						$dt = GETPOST($date_key);
						if (! empty($dt)) {
							$array_query[$key] = dol_mktime(0, 0, 0, GETPOST($date_key . 'month', 'int'), GETPOST($date_key . 'day', 'int'), GETPOST($date_key . 'year', 'int'));
						} else {
							$array_query[$key] = '';
						}
					}
				}
			}

			if (preg_match("/^type_of_target/", $key)) {
				$array_query[$key] = GETPOST($key);
			}
		}
		$advTarget->filtervalue = json_encode($array_query);

		if ($action == 'createfilter') {
			$advTarget->name = $template_name;
			$result = $advTarget->create($user);
			if ($result < 0) {
				setEventMessage($advTarget->error, 'errors');
			}
		} elseif ($action == 'savefilter') {
			$result = $advTarget->update($user);
			if ($result < 0) {
				setEventMessage($advTarget->error, 'errors');
			}
		}
		$template_id = $advTarget->id;
	}
}

if ($action == 'deletefilter') {
	$result = $advTarget->delete($user);
	if ($result < 0) {
		setEventMessage($advTarget->error, 'errors');
	}
	header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id);
	exit();
}

if ($action == 'delete') {
	// Ici, rowid indique le destinataire et id le mailing
	$sql = "DELETE FROM " . MAIN_DB_PREFIX . "mailing_cibles WHERE rowid=" . $rowid;
	$resql = $db->query($sql);
	if ($resql) {
		if (! empty($id)) {
			$classname = "MailingTargets";
			$obj = new $classname($db);
			$obj->update_nb($id);

			header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id);
			exit();
		} else {
			header("Location: liste.php");
			exit();
		}
	} else {
		dol_print_error($db);
	}
}

if ($_POST["button_removefilter"]) {
	$search_nom = '';
	$search_prenom = '';
	$search_email = '';
}


/*
 * View
 */

$extrajs = array (
		'/includes/jquery/plugins/multiselect/js/ui.multiselect.js'
);
$extracss = array (
		'/includes/jquery/plugins/multiselect/css/ui.multiselect.css',
);

llxHeader('', $langs->trans("MailAdvTargetRecipients"), '', '', '', '', $extrajs, $extracss);

print '<script type="text/javascript" language="javascript">
	$(document).ready(function() {

		// Click Function
		$(":button[name=addcontact]").click(function() {
				$(":hidden[name=action]").val("add");
				$("#find_customer").submit();
		});

		$(":button[name=loadfilter]").click(function() {
				$(":hidden[name=action]").val("loadfilter");
				$("#find_customer").submit();
		});

		$(":button[name=deletefilter]").click(function() {
				$(":hidden[name=action]").val("deletefilter");
				$("#find_customer").submit();
		});

		$(":button[name=savefilter]").click(function() {
				$(":hidden[name=action]").val("savefilter");
				$("#find_customer").submit();
		});

		$(":button[name=createfilter]").click(function() {
				$(":hidden[name=action]").val("createfilter");
				$("#find_customer").submit();
		});
	});
</script>';

$form = new Form($db);
$formadvtargetemaling = new FormAdvTargetEmailing($db);
$formcompany = new FormCompany($db);
$formother = new FormOther($db);

if ($object->fetch($id) >= 0) {

	$head = emailing_prepare_head($object);

	dol_fiche_head($head, 'advtargets', $langs->trans("Mailing"), 0, 'email');

	print '<table class="border" width="100%">';

	$linkback = '<a href="' . DOL_URL_ROOT . '/comm/mailing/liste.php">' . $langs->trans("BackToList") . '</a>';

	print '<tr><td width="25%">' . $langs->trans("Ref") . '</td>';
	print '<td colspan="3">';
	print $form->showrefnav($object, 'id', $linkback);
	print '</td></tr>';

	print '<tr><td width="25%">' . $langs->trans("MailTitle") . '</td><td colspan="3">' . $object->titre . '</td></tr>';

	print '<tr><td width="25%">' . $langs->trans("MailFrom") . '</td><td colspan="3">' . dol_print_email($object->email_from, 0, 0, 0, 0, 1) . '</td></tr>';

	// Errors to
	print '<tr><td width="25%">' . $langs->trans("MailErrorsTo") . '</td><td colspan="3">' . dol_print_email($object->email_errorsto, 0, 0, 0, 0, 1);
	print '</td></tr>';

	// Status
	print '<tr><td width="25%">' . $langs->trans("Status") . '</td><td colspan="3">' . $object->getLibStatut(4) . '</td></tr>';

	// Nb of distinct emails
	print '<tr><td width="25%">';
	print $langs->trans("TotalNbOfDistinctRecipients");
	print '</td><td colspan="3">';
	$nbemail = ($object->nbemail ? $object->nbemail : '0');
	if (! empty($conf->global->MAILING_LIMIT_SENDBYWEB) && $conf->global->MAILING_LIMIT_SENDBYWEB < $nbemail) {
		$text = $langs->trans('LimitSendingEmailing', $conf->global->MAILING_LIMIT_SENDBYWEB);
		print $form->textwithpicto($nbemail, $text, 1, 'warning');
	} else {
		print $nbemail;
	}
	print '</td></tr>';

	print '</table>';

	print "</div>";

	// Show email selectors
	if ($object->statut == 0 && $user->rights->mailing->creer) {
		print_fiche_titre($langs->trans("AdvTgtTitle"));

		print '<div class="tabBar">' . "\n";
		print '<form name="find_customer" id="find_customer" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '"  method="POST">' . "\n";
		print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">' . "\n";
		print '<input type="hidden" name="action" value="">' . "\n";
		print '<table class="border" width="100%">' . "\n";

		print '<tr>' . "\n";
		print '<td colspan="3" align="right">' . "\n";

		print '<input type="button" name="addcontact" id="addcontact" value="' . $langs->trans('AdvTgtAddContact') . '" class="butAction"/>' . "\n";

		print '</td>' . "\n";
		print '</tr>' . "\n";

		print '<tr><td>' . $langs->trans('AdvTgtNameTemplate') . '</td><td>';
		if (! empty($template_id)) {
			$default_template = $template_id;
		} else {
			$default_template = $advTarget->id;
		}
		print $formadvtargetemaling->selectAdvtargetemailingTemplate('template_id', $default_template);
		print '<input type="button" name="loadfilter" id="loadfilter" value="' . $langs->trans('AdvTgtLoadFilter') . '" class="butAction"/>';
		print '<input type="button" name="deletefilter" id="deletefilter" value="' . $langs->trans('AdvTgtDeleteFilter') . '" class="butAction"/>';
		print '<input type="button" name="savefilter" id="savefilter" value="' . $langs->trans('AdvTgtSaveFilter') . '" class="butAction"/>';
		print $langs->trans('AdvTgtOrCreateNewFilter');
		print '<input type="text" name="template_name" id="template_name" value=""/>';
		print '<input type="button" name="createfilter" id="createfilter" value="' . $langs->trans('AdvTgtCreateFilter') . '" class="butAction"/>';
		print '</td><td>' . "\n";
		print '</td></tr>' . "\n";

		print '<tr><td>' . $langs->trans('AdvTgtTypeOfIncude') . '</td><td>';
		print $form->selectarray('type_of_target', $advTarget->select_target_type, $array_query['type_of_target']);
		print '</td><td>' . "\n";
		print $form->textwithpicto('', $langs->trans("AdvTgtTypeOfIncudeHelp"), 1, 'help');
		print '</td></tr>' . "\n";

		// Customer name
		print '<tr><td>' . $langs->trans('ThirdPartyName');
		if (! empty($array_query['cust_name'])) {
			print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
		}
		print '</td><td><input type="text" name="cust_name" value="' . $array_query['cust_name'] . '"/></td><td>' . "\n";
		print $form->textwithpicto('', $langs->trans("AdvTgtSearchTextHelp"), 1, 'help');
		print '</td></tr>' . "\n";

		// Code Client
		print '<tr><td>' . $langs->trans('CustomerCode');
		if (! empty($array_query['cust_code'])) {
			print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
		}
		print '</td><td><input type="text" name="cust_code" value="' . $array_query['cust_code'] . '"/></td><td>' . "\n";
		print $form->textwithpicto('', $langs->trans("AdvTgtSearchTextHelp"), 1, 'help');
		print '</td></tr>' . "\n";

		// Address Client
		print '<tr><td>' . $langs->trans('Address');
		if (! empty($array_query['cust_adress'])) {
			print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
		}
		print '</td><td><input type="text" name="cust_adress" value="' . $array_query['cust_adress'] . '"/></td><td>' . "\n";
		print $form->textwithpicto('', $langs->trans("AdvTgtSearchTextHelp"), 1, 'help');
		print '</td></tr>' . "\n";

		// Zip Client
		print '<tr><td>' . $langs->trans('Zip');
		if (! empty($array_query['cust_zip'])) {
			print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
		}
		print '</td><td><input type="text" name="cust_zip" value="' . $array_query['cust_zip'] . '"/></td><td>' . "\n";
		print $form->textwithpicto('', $langs->trans("AdvTgtSearchTextHelp"), 1, 'help');
		print '</td></tr>' . "\n";

		// City Client
		print '<tr><td>' . $langs->trans('Town');
		if (! empty($array_query['cust_city'])) {
			print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
		}
		print '</td><td><input type="text" name="cust_city" value="' . $array_query['cust_city'] . '"/></td><td>' . "\n";
		print $form->textwithpicto('', $langs->trans("AdvTgtSearchTextHelp"), 1, 'help');
		print '</td></tr>' . "\n";

		// Customer Country
		print '<tr><td>' . $langs->trans("Country");
		if (count($array_query['cust_country']) > 0) {
			print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
		}
		print '</td><td>' . "\n";
		print $formadvtargetemaling->multiselectCountry('cust_country', $array_query['cust_country']);
		print '</td><td>' . "\n";
		print '</td></tr>' . "\n";

		// State Customer
		print '<tr><td>' . $langs->trans('Status') . ' ' . $langs->trans('ThirdParty');
		if (count($array_query['cust_status']) > 0) {
			print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
		}
		print '</td><td>';
		print $formadvtargetemaling->advMultiselectarray('cust_status', array (
				'0' => $langs->trans('ActivityCeased'),
				'1' => $langs->trans('InActivity')
		), $array_query['cust_status']);
		print '</td><td>' . "\n";
		print '</td></tr>' . "\n";

		// Mother Company
		print '<tr><td>' . $langs->trans("Maison mère");
		if (! empty($array_query['cust_mothercompany'])) {
			print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
		}
		print '</td><td>' . "\n";
		print '<input type="text" name="cust_mothercompany" value="' . $array_query['cust_mothercompany'] . '"/>';
		print '</td><td>' . "\n";
		print $form->textwithpicto('', $langs->trans("AdvTgtSearchTextHelp"), 1, 'help');
		print '</td></tr>' . "\n";

		// Prospect/Customer
		$selected = $array_query['cust_typecust'];
		print '<tr><td>' . $langs->trans('ProspectCustomer') . ' ' . $langs->trans('ThirdParty');
		if (count($array_query['cust_typecust']) > 0) {
			print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
		}
		print '</td><td>';
		$options_array = array (
				2 => $langs->trans('Prospect'),
				3 => $langs->trans('ProspectCustomer'),
				1 => $langs->trans('Customer'),
				0 => $langs->trans('NorProspectNorCustomer')
		);
		print $formadvtargetemaling->advMultiselectarray('cust_typecust', $options_array, $array_query['cust_typecust']);
		print '</td><td>' . "\n";
		print '</td></tr>' . "\n";

		// Prospection status
		print '<tr><td>' . $langs->trans('ProspectLevel');
		if (count($array_query['cust_prospect_status']) > 0) {
			print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
		}
		print '</td><td>';
		print $formadvtargetemaling->multiselectProspectionStatus($array_query['cust_prospect_status'], 'cust_prospect_status', 1);
		print '</td><td>' . "\n";
		print '</td></tr>' . "\n";

		// Prospection comm status
		print '<tr><td>' . $langs->trans('StatusProsp');
		if (count($array_query['cust_comm_status']) > 0) {
			print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
		}
		print '</td><td>';
		print $formadvtargetemaling->advMultiselectarray('cust_comm_status', $advTarget->type_statuscommprospect, $array_query['cust_comm_status']);
		print '</td><td>' . "\n";
		print '</td></tr>' . "\n";

		// Customer Type
		print '<tr><td>' . $langs->trans("ThirdPartyType");
		if (count($array_query['cust_typeent']) > 0) {
			print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
		}
		print '</td><td>' . "\n";
		print $formadvtargetemaling->advMultiselectarray('cust_typeent', $formcompany->typent_array(0, " AND id <> 0"), $array_query['cust_typeent']);
		print '</td><td>' . "\n";
		print '</td></tr>' . "\n";

		// Staff number
		print '<td>' . $langs->trans("Staff");
		if (count($array_query['cust_effectif_id']) > 0) {
			print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
		}
		print '</td><td>';
		print $formadvtargetemaling->advMultiselectarray("cust_effectif_id", $formcompany->effectif_array(0, " AND id <> 0"), $array_query['cust_effectif_id']);
		print '</td><td>' . "\n";
		print '</td></tr>' . "\n";

		// Sales manager
		print '<tr><td>' . $langs->trans("SalesRepresentatives");
		if (count($array_query['cust_saleman']) > 0) {
			print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
		}
		print '</td><td>' . "\n";
		print $formadvtargetemaling->multiselectselectSalesRepresentatives('cust_saleman', $array_query['cust_saleman'], $user);
		print '</td><td>' . "\n";
		print '</td></tr>' . "\n";

		// Customer Default Langauge
		if (! empty($conf->global->MAIN_MULTILANGS)) {

			print '<tr><td>' . $langs->trans("DefaultLang");
			if (count($array_query['cust_language']) > 0) {
				print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
			}
			print '</td><td>' . "\n";
			print $formadvtargetemaling->multiselectselectLanguage('cust_language', $array_query['cust_language']);
			print '</td><td>' . "\n";
			print '</td></tr>' . "\n";
		}

		if (! empty($conf->categorie->enabled) && $user->rights->categorie->lire) {
			// Customer Categories
			print '<tr><td>' . $langs->trans("CustomersCategoryShort");
			if (count($array_query['cust_categ']) > 0) {
				print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
			}
			print '</td><td>' . "\n";
			print $formadvtargetemaling->multiselectCustomerCategories('cust_categ', $array_query['cust_categ']);
			print '</td><td>' . "\n";
			print '</td></tr>' . "\n";
		}

		// Standard Extrafield feature
		if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) {
			// fetch optionals attributes and labels
			dol_include_once('/core/class/extrafields.class.php');
			$extrafields = new ExtraFields($db);
			$extralabels = $extrafields->fetch_name_optionals_label('societe');
			foreach ( $extralabels as $key => $val ) {
				if ($key != 'ts_nameextra' && $key != 'ts_payeur') {
					print '<tr><td>' . $extrafields->attribute_label[$key];
					if (! empty($array_query['options_' . $key]) || (is_array($array_query['options_' . $key]) && count($array_query['options_' . $key]) > 0)) {
						print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
					}
					print '</td><td>';
					if (($extrafields->attribute_type[$key] == 'varchar') || ($extrafields->attribute_type[$key] == 'text')) {
						print '<input type="text" name="options_' . $key . '"/></td><td>' . "\n";
						print $form->textwithpicto('', $langs->trans("AdvTgtSearchTextHelp"), 1, 'help');
					} elseif (($extrafields->attribute_type[$key] == 'int') || ($extrafields->attribute_type[$key] == 'double')) {
						print $langs->trans("AdvTgtMinVal") . '<input type="text" name="options' . $key . '_min"/>';
						print $langs->trans("AdvTgtMaxVal") . '<input type="text" name="options' . $key . '_max"/>';
						print '</td><td>' . "\n";
						print $form->textwithpicto('', $langs->trans("AdvTgtSearchIntHelp"), 1, 'help');
					} elseif (($extrafields->attribute_type[$key] == 'date') || ($extrafields->attribute_type[$key] == 'datetime')) {

						print '<table class="nobordernopadding"><tr>';
						print '<td>' . $langs->trans("AdvTgtStartDt") . '</td><td>';
						print $form->select_date('', 'options_' . $key . '_st_dt');
						print '</td><td>' . $langs->trans("AdvTgtEndDt") . '</td><td>';
						print $form->select_date('', 'options_' . $key . '_end_dt');
						print '</td></tr></table>';

						print '</td><td>' . "\n";
						print $form->textwithpicto('', $langs->trans("AdvTgtSearchDtHelp"), 1, 'help');
					} elseif (($extrafields->attribute_type[$key] == 'boolean')) {
						print $form->selectarray('options_' . $key, array (
								'' => '',
								'1' => $langs->trans('Yes'),
								'0' => $langs->trans('No')
						), $array_query['options_' . $key]);
						print '</td><td>' . "\n";
					} elseif (($extrafields->attribute_type[$key] == 'select')) {
						print $formadvtargetemaling->advMultiselectarray('options_' . $key, $extrafields->attribute_param[$key]['options'], $array_query['options_' . $key]);
						print '</td><td>' . "\n";
					} elseif (($extrafields->attribute_type[$key] == 'sellist')) {
						print $formadvtargetemaling->advMultiselectarraySelllist('options_' . $key, $extrafields->attribute_param[$key]['options'], $array_query['options_' . $key]);
						print '</td><td>' . "\n";
					} else {

						print '<table class="nobordernopadding"><tr>';
						print '<td></td><td>';
						if (is_array($array_query['options_' . $key])) {
							print $extrafields->showInputField($key, implode(',', $array_query['options_' . $key]));
						} else {
							print $extrafields->showInputField($key, $array_query['options_' . $key]);
						}
						print '</td></tr></table>';

						print '</td><td>' . "\n";
					}
					print '</td></tr>' . "\n";
				}
			}
		} else {
			$std_soc = new Societe($db);
			$action_search = 'query';
			// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
			include_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
			$hookmanager = new HookManager($db);
			$hookmanager->initHooks(array (
					'thirdpartycard'
			));
			if (! empty($advTarget->id)) {
				$parameters = array (
						'array_query' => $advTarget->filtervalue
				);
			}
			// Module extrafield feature
			$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $std_soc, $action_search);
		}

		// State Contact
		print '<tr><td>' . $langs->trans('Status') . ' ' . $langs->trans('Contact');
		if (count($array_query['contact_status']) > 0) {
			print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
		}
		print '</td><td>';
		print $formadvtargetemaling->advMultiselectarray('contact_status', array (
				'0' => $langs->trans('ActivityCeased'),
				'1' => $langs->trans('InActivity')
		), $array_query['contact_status']);
		print '</td><td>' . "\n";
		print $form->textwithpicto('', $langs->trans("AdvTgtContactHelp"), 1, 'help');
		print '</td></tr>' . "\n";

		// Civility
		print '<tr><td width="15%">' . $langs->trans("UserTitle");
		if (count($array_query['contact_civility']) > 0) {
			print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
		}
		print '</td><td>';
		print $formadvtargetemaling->multiselectCivility('contact_civility', $array_query['contact_civility']);
		print '</td></tr>';

		// contact name
		print '<tr><td>' . $langs->trans('Contact') . ' ' . $langs->trans('Lastname');
		if (! empty($array_query['contact_lastname'])) {
			print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
		}
		print '</td><td><input type="text" name="contact_lastname" value="' . $array_query['contact_lastname'] . '"/></td><td>' . "\n";
		print $form->textwithpicto('', $langs->trans("AdvTgtSearchTextHelp"), 1, 'help');
		print '</td></tr>' . "\n";
		print '<tr><td>' . $langs->trans('Contact') . ' ' . $langs->trans('Firstname');
		if (! empty($array_query['contact_firstname'])) {
			print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
		}
		print '</td><td><input type="text" name="contact_firstname" value="' . $array_query['contact_firstname'] . '"/></td><td>' . "\n";
		print $form->textwithpicto('', $langs->trans("AdvTgtSearchTextHelp"), 1, 'help');
		print '</td></tr>' . "\n";

		// Contact Country
		print '<tr><td>' . $langs->trans('Contact') . ' ' . $langs->trans("Country");
		if (count($array_query['contact_country']) > 0) {
			print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
		}
		print '</td><td>' . "\n";
		print $formadvtargetemaling->multiselectCountry('contact_country', $array_query['contact_country']);
		print '</td><td>' . "\n";
		print '</td></tr>' . "\n";

		// Never send mass mailing
		print '<tr><td>' . $langs->trans('Contact') . ' ' . $langs->trans("No_Email");
		if (! empty($array_query['contact_no_email'])) {
			print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
		}
		print '</td><td>' . "\n";
		print $form->selectarray('contact_no_email', array (
				'' => '',
				'1' => $langs->trans('Yes'),
				'0' => $langs->trans('No')
		), $array_query['contact_no_email']);
		print '</td><td>' . "\n";
		print '</td></tr>' . "\n";

		// Contact Date Create
		print '<tr><td>' . $langs->trans('Contact') . ' ' . $langs->trans("DateCreation");
		if (! empty($array_query['contact_create_st_dt'])) {
			print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
		}
		print '</td><td>' . "\n";
		print '<table class="nobordernopadding"><tr>';
		print '<td>' . $langs->trans("AdvTgtStartDt") . '</td><td>';
		print $form->select_date($array_query['contact_create_st_dt'], 'contact_create_st_dt', 0, 0, 1, 'find_customer', 1, 1);
		print '</td><td>' . $langs->trans("AdvTgtEndDt") . '</td><td>';
		print $form->select_date($array_query['contact_create_end_dt'], 'contact_create_end_dt', 0, 0, 1, 'find_customer', 1, 1);
		print '</td></tr></table>';
		print '</td><td>' . "\n";
		print '</td></tr>' . "\n";

		// Contact update Create
		print '<tr><td>' . $langs->trans('Contact') . ' ' . $langs->trans("DateLastModification");
		if (! empty($array_query['contact_update_st_dt'])) {
			print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
		}
		print '</td><td>' . "\n";
		print '<table class="nobordernopadding"><tr>';
		print '<td>' . $langs->trans("AdvTgtStartDt") . '</td><td>';
		print $form->select_date($array_query['contact_update_st_dt'], 'contact_update_st_dt', 0, 0, 1, 'find_customer', 1, 1);
		print '</td><td>' . $langs->trans("AdvTgtEndDt") . '</td><td>';
		print $form->select_date($array_query['contact_update_end_dt'], 'contact_update_end_dt', 0, 0, 1, 'find_customer', 1, 1);
		print '</td></tr></table>';
		print '</td><td>' . "\n";
		print '</td></tr>' . "\n";

		if (! empty($conf->categorie->enabled) && $user->rights->categorie->lire) {
			// Customer Categories
			print '<tr><td>' . $langs->trans("ContactCategoriesShort");
			if (count($array_query['contact_categ']) > 0) {
				print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
			}
			print '</td><td>' . "\n";
			print $formadvtargetemaling->multiselectContactCategories('contact_categ', $array_query['contact_categ']);
			print '</td><td>' . "\n";
			print '</td></tr>' . "\n";
		}

		// Standard Extrafield feature
		if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) {
			// fetch optionals attributes and labels
			dol_include_once('/core/class/extrafields.class.php');
			$extrafields = new ExtraFields($db);
			$extralabels = $extrafields->fetch_name_optionals_label('socpeople');
            foreach($extrafields->attribute_type as $key=>&$value) {
                if($value == 'radio')$value = 'select';
            }


			foreach ( $extralabels as $key => $val ) {

				print '<tr><td>' . $extrafields->attribute_label[$key];
				if ($array_query['options_' . $key . '_cnct'] != '' || (is_array($array_query['options_' . $key . '_cnct']) && count($array_query['options_' . $key . '_cnct']) > 0)) {
					print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
				}
				print '</td><td>';
				if (($extrafields->attribute_type[$key] == 'varchar') || ($extrafields->attribute_type[$key] == 'text')) {
					print '<input type="text" name="options_' . $key . '_cnct"/></td><td>' . "\n";
					print $form->textwithpicto('', $langs->trans("AdvTgtSearchTextHelp"), 1, 'help');
				} elseif (($extrafields->attribute_type[$key] == 'int') || ($extrafields->attribute_type[$key] == 'double')) {
					print $langs->trans("AdvTgtMinVal") . '<input type="text" name="options_' . $key . '_min_cnct"/>';
					print $langs->trans("AdvTgtMaxVal") . '<input type="text" name="options_' . $key . '_max_cnct"/>';
					print '</td><td>' . "\n";
					print $form->textwithpicto('', $langs->trans("AdvTgtSearchIntHelp"), 1, 'help');
				} elseif (($extrafields->attribute_type[$key] == 'date') || ($extrafields->attribute_type[$key] == 'datetime')) {

					print '<table class="nobordernopadding"><tr>';
					print '<td>' . $langs->trans("AdvTgtStartDt") . '</td><td>';
					print $form->select_date('', 'options_' . $key . '_st_dt' . '_cnct');
					print '</td><td>' . $langs->trans("AdvTgtEndDt") . '</td><td>';
					print $form->select_date('', 'options_' . $key . '_end_dt' . '_cnct');
					print '</td></tr></table>';

					print '</td><td>' . "\n";
					print $form->textwithpicto('', $langs->trans("AdvTgtSearchDtHelp"), 1, 'help');
				} elseif (($extrafields->attribute_type[$key] == 'boolean')) {
					print $form->selectarray('options_' . $key . '_cnct', array (
							'' => '',
							'1' => $langs->trans('Yes'),
							'0' => $langs->trans('No')
					), $array_query['options_' . $key . '_cnct']);
					print '</td><td>' . "\n";
				} elseif (($extrafields->attribute_type[$key] == 'select')) {
					print $formadvtargetemaling->advMultiselectarray('options_' . $key . '_cnct', $extrafields->attribute_param[$key]['options'], $array_query['options_' . $key . '_cnct']);
					print '</td><td>' . "\n";
				} elseif (($extrafields->attribute_type[$key] == 'sellist')) {
					print $formadvtargetemaling->advMultiselectarraySelllist('options_' . $key . '_cnct', $extrafields->attribute_param[$key]['options'], $array_query['options_' . $key . '_cnct']);
					print '</td><td>' . "\n";
				} else {

					print '<table class="nobordernopadding"><tr>';
					print '<td></td><td>';
					if (is_array($array_query['options_' . $key . '_cnct'])) {
						print $extrafields->showInputField($key, implode(',', $array_query['options_' . $key . '_cnct']), '', '_cnct');
					} else {
						print $extrafields->showInputField($key, $array_query['options_' . $key . '_cnct'], '', '_cnct');
					}
					print '</td></tr></table>';

					print '</td><td>' . "\n";
				}
				print '</td></tr>' . "\n";
			}
		}

		print '<tr>' . "\n";
		print '<td colspan="3" align="right">' . "\n";

		print '<input type="button" name="addcontact" id="addcontact" value="' . $langs->trans('AdvTgtAddContact') . '" class="butAction"/>' . "\n";

		print '</td>' . "\n";
		print '</tr>' . "\n";
		print '</table>' . "\n";
		print '</form>' . "\n";
		print '</div>' . "\n";

		print '<form action="' . $_SERVER['PHP_SELF'] . '?action=clear&id=' . $object->id . '" method="POST">';
		print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
		print_titre($langs->trans("ToClearAllRecipientsClickHere"));
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td class="liste_titre" align="right"><input type="submit" class="button" value="' . $langs->trans("TargetsReset") . '"></td>';
		print '</tr>';
		print '</table>';
		print '</form>';
		print '<br>';
	}
}

llxFooter();
$db->close();