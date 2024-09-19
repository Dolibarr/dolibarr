<?php
/* Copyright (C) 2013-2018  Jean-François FERRY 	<hello@librethic.io>
 * Copyright (C) 2016       Christophe Battarel 	<christophe@altairis.fr>
 * Copyright (C) 2022-2023  Udo Tamm            	<dev@dolibit.de>
 * Copyright (C) 2023       Alexandre Spangaro  	<aspangaro@easya.solutions>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2024		Benjamin Falière		<benjamin.faliere@altairis.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *     \file        htdocs/admin/ticket.php
 *     \ingroup     ticket
 *     \brief       Page to setup the module ticket
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT."/core/class/html.formcategory.class.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/ticket.lib.php";
require_once DOL_DOCUMENT_ROOT."/ticket/class/ticket.class.php";

// Load translation files required by the page
$langs->loadLangs(array("admin", "ticket"));

// Access control
if (!$user->admin) {
	accessforbidden();
}

// Parameters
$value = GETPOST('value', 'alpha');
$action = GETPOST('action', 'aZ09');
$modulepart = GETPOST('modulepart', 'aZ09');	// Used by actions_setmoduleoptions.inc.php

$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');
$type = 'ticket';

$error = 0;
$reg = array();

// Initiate status list
$statuslist = array(
	Ticket::STATUS_IN_PROGRESS => $langs->trans("InProgress"),
	Ticket::STATUS_NOT_READ => $langs->trans("NotRead"),
	Ticket::STATUS_READ => $langs->trans("Read"),
	Ticket::STATUS_ASSIGNED => $langs->trans("Assigned"),
	Ticket::STATUS_NEED_MORE_INFO => $langs->trans("NeedMoreInformationShort"),
	Ticket::STATUS_WAITING => $langs->trans("Waiting"),
	Ticket::STATUS_CLOSED => $langs->trans("SolvedClosed")
);

/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

if ($action == 'updateMask') {
	$maskconstticket = GETPOST('maskconstticket', 'aZ09');
	$maskticket = GETPOST('maskticket', 'alpha');

	$res = 0;

	if ($maskconstticket && preg_match('/_MASK$/', $maskconstticket)) {
		$res = dolibarr_set_const($db, $maskconstticket, $maskticket, 'chaine', 0, '', $conf->entity);
	}

	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
} elseif ($action == 'set') {
	// Activate a model
	$ret = addDocumentModel($value, $type, $label, $scandir);
} elseif ($action == 'del') {
	$ret = delDocumentModel($value, $type);
	if ($ret > 0) {
		if ($conf->global->TICKET_ADDON_PDF == "$value") {
			dolibarr_del_const($db, 'TICKET_ADDON_PDF', $conf->entity);
		}
	}
} elseif (preg_match('/set_(.*)/', $action, $reg)) {
	$code = $reg[1];
	$value = GETPOSTISSET($code) ? GETPOSTINT($code) : 1;
	if ($code == 'TICKET_NOTIFICATION_ALSO_MAIN_ADDRESS' && getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {
		$param_notification_also_main_addressemail = GETPOST('TICKET_NOTIFICATION_ALSO_MAIN_ADDRESS', 'alpha');
		$res = dolibarr_set_const($db, 'TICKET_NOTIFICATION_ALSO_MAIN_ADDRESS', $param_notification_also_main_addressemail, 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$error++;
		}
	} else {
		$res = dolibarr_set_const($db, $code, $value, 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$error++;
		}
	}
} elseif (preg_match('/del_(.*)/', $action, $reg)) {
	$code = $reg[1];
	$res = dolibarr_del_const($db, $code, $conf->entity);
	if (!($res > 0)) {
		$error++;
	}
} elseif ($action == 'setdoc') {
	// Set default model
	if (dolibarr_set_const($db, "TICKET_ADDON_PDF", $value, 'chaine', 0, '', $conf->entity)) {
		// The constant that was read before the new set
		// We therefore requires a variable to have a coherent view
		$conf->global->TICKET_ADDON_PDF = $value;
	}

	// Activate the model
	$ret = delDocumentModel($value, $type);
	if ($ret > 0) {
		$ret = addDocumentModel($value, $type, $label, $scandir);
	}
} elseif ($action == 'setmod') {
	// TODO check if the chosen numbering module can be activated
	// by calling the canBeActivated method

	dolibarr_set_const($db, "TICKET_ADDON", $value, 'chaine', 0, '', $conf->entity);
} elseif ($action == 'setvarworkflow') {
	// For compatibility when javascript is not enabled
	if (empty($conf->use_javascript_ajax)) {
		$param_auto_read = GETPOST('TICKET_AUTO_READ_WHEN_CREATED_FROM_BACKEND', 'alpha');
		$res = dolibarr_set_const($db, 'TICKET_AUTO_READ_WHEN_CREATED_FROM_BACKEND', $param_auto_read, 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$error++;
		}

		$param_auto_assign = GETPOST('TICKET_AUTO_ASSIGN_USER_CREATE', 'alpha');
		$res = dolibarr_set_const($db, 'TICKET_AUTO_ASSIGN_USER_CREATE', $param_auto_assign, 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$error++;
		}

		$param_auto_notify_close = GETPOST('TICKET_NOTIFY_AT_CLOSING', 'alpha');
		$res = dolibarr_set_const($db, 'TICKET_NOTIFY_AT_CLOSING', $param_auto_notify_close, 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$error++;
		}
	}

	$param_limit_view = GETPOST('TICKET_LIMIT_VIEW_ASSIGNED_ONLY', 'alpha');
	$res = dolibarr_set_const($db, 'TICKET_LIMIT_VIEW_ASSIGNED_ONLY', $param_limit_view, 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}

	if (GETPOSTISSET('product_category_id')) {
		$param_ticket_product_category = GETPOSTINT('product_category_id');
		$res = dolibarr_set_const($db, 'TICKET_PRODUCT_CATEGORY', $param_ticket_product_category, 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$error++;
		}
	}

	$param_status = GETPOST('TICKET_SET_STATUS_ON_ANSWER');
	$res = dolibarr_set_const($db, 'TICKET_SET_STATUS_ON_ANSWER', $param_status, 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}


	$param_delay_first_response = GETPOSTINT('delay_first_response');
	$res = dolibarr_set_const($db, 'TICKET_DELAY_BEFORE_FIRST_RESPONSE', $param_delay_first_response, 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}

	$param_delay_between_responses = GETPOSTINT('delay_between_responses');
	$res = dolibarr_set_const($db, 'TICKET_DELAY_SINCE_LAST_RESPONSE', $param_delay_between_responses, 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}
} elseif ($action == 'setvar') {
	include_once DOL_DOCUMENT_ROOT."/core/lib/files.lib.php";

	$notification_email = GETPOST('TICKET_NOTIFICATION_EMAIL_FROM', 'alpha');
	$notification_email_description = "Sender of ticket replies sent from Dolibarr";
	if (!empty($notification_email)) {
		$res = dolibarr_set_const($db, 'TICKET_NOTIFICATION_EMAIL_FROM', $notification_email, 'chaine', 0, $notification_email_description, $conf->entity);
	} else { // If an empty e-mail address is providen, use the global "FROM" since an empty field will cause other issues
		$res = dolibarr_set_const($db, 'TICKET_NOTIFICATION_EMAIL_FROM', $conf->global->MAIN_MAIL_EMAIL_FROM, 'chaine', 0, $notification_email_description, $conf->entity);
	}
	if (!($res > 0)) {
		$error++;
	}

	// altairis : differentiate notification email FROM and TO
	$notification_email_to = GETPOST('TICKET_NOTIFICATION_EMAIL_TO', 'alpha');
	$notification_email_to_description = "Notified e-mail for ticket replies sent from Dolibarr";
	if (!empty($notification_email_to)) {
		$res = dolibarr_set_const($db, 'TICKET_NOTIFICATION_EMAIL_TO', $notification_email_to, 'chaine', 0, $notification_email_to_description, $conf->entity);
	} else {
		$res = dolibarr_set_const($db, 'TICKET_NOTIFICATION_EMAIL_TO', '', 'chaine', 0, $notification_email_to_description, $conf->entity);
	}
	if (!($res > 0)) {
		$error++;
	}

	$mail_intro = GETPOST('TICKET_MESSAGE_MAIL_INTRO', 'restricthtml');
	$mail_intro_description = "Introduction text of ticket replies sent from Dolibarr";
	if (!empty($mail_intro)) {
		$res = dolibarr_set_const($db, 'TICKET_MESSAGE_MAIL_INTRO', $mail_intro, 'chaine', 0, $mail_intro_description, $conf->entity);
	} else {
		$res = dolibarr_set_const($db, 'TICKET_MESSAGE_MAIL_INTRO', '', 'chaine', 0, $mail_intro_description, $conf->entity);
	}
	if (!($res > 0)) {
		$error++;
	}

	$mail_signature = GETPOST('TICKET_MESSAGE_MAIL_SIGNATURE', 'restricthtml');
	$signature_description = "Signature of ticket replies sent from Dolibarr";
	if (!empty($mail_signature)) {
		$res = dolibarr_set_const($db, 'TICKET_MESSAGE_MAIL_SIGNATURE', $mail_signature, 'chaine', 0, $signature_description, $conf->entity);
	} else {
		$res = dolibarr_set_const($db, 'TICKET_MESSAGE_MAIL_SIGNATURE', '', 'chaine', 0, $signature_description, $conf->entity);
	}
	if (!($res > 0)) {
		$error++;
	}

	// For compatibility when javascript is not enabled
	if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2 && empty($conf->use_javascript_ajax)) {
		$param_notification_also_main_addressemail = GETPOST('TICKET_NOTIFICATION_ALSO_MAIN_ADDRESS', 'alpha');
		$res = dolibarr_set_const($db, 'TICKET_NOTIFICATION_ALSO_MAIN_ADDRESS', $param_notification_also_main_addressemail, 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$error++;
		}
	}
}


/*
 * View
 */

$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

$formcategory = new FormCategory($db);

// Page Header
$help_url = 'EN:Module_Ticket|FR:Module_Ticket_FR';
$page_name = 'TicketSetup';
llxHeader('', $langs->trans($page_name), $help_url, '', 0, 0, '', '', '', 'mod-admin page-ticket');

// Subheader
$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// Configuration header
$head = ticketAdminPrepareHead();

print dol_get_fiche_head($head, 'settings', $langs->trans("Module56000Name"), -1, "ticket");

print '<span class="opacitymedium">'.$langs->trans("TicketSetupDictionaries").'</span> : <a href="'.DOL_URL_ROOT.'/admin/dict.php">'.$langs->trans("ClickHereToGoTo", $langs->transnoentitiesnoconv("DictionarySetup")).'</a><br>';

print dol_get_fiche_end();


/*
 * Tickets numbering model
 */

print load_fiche_titre($langs->trans("TicketNumberingModules"), '', '');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td width="100">'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td>'.$langs->trans("Example").'</td>';
print '<td align="center" width="60">'.$langs->trans("Activated").'</td>';
print '<td align="center" width="40">'.$langs->trans("ShortInfo").'</td>';
print "</tr>\n";

clearstatcache();

foreach ($dirmodels as $reldir) {
	$dir = dol_buildpath($reldir."core/modules/ticket");

	if (is_dir($dir)) {
		$handle = opendir($dir);
		if (is_resource($handle)) {
			while (($file = readdir($handle)) !== false) {
				if (preg_match('/^(mod_.*)\.php$/i', $file, $reg)) {
					$file = $reg[1];
					$classname = substr($file, 4);

					include_once $dir.'/'.$file.'.php';

					$module = new $file();
					'@phan-var-force ModeleNumRefTicket $module';

					// Show modules according to features level
					if ($module->version == 'development' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 2) {
						continue;
					}

					if ($module->version == 'experimental' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 1) {
						continue;
					}

					if ($module->isEnabled()) {
						print '<tr class="oddeven"><td>'.$module->name."</td><td>\n";
						print $module->info($langs);
						print '</td>';

						// Show example of numbering model
						print '<td class="nowrap">';
						$tmp = $module->getExample();
						if (preg_match('/^Error/', $tmp)) {
							$langs->load("errors");
							print '<div class="error">'.$langs->trans($tmp).'</div>';
						} elseif ($tmp == 'NotConfigured') {
							print '<span class="opacitymedium">'.$langs->trans($tmp).'</span>';
						} else {
							print $tmp;
						}

						print '</td>'."\n";

						print '<td class="center">';
						if ($conf->global->TICKET_ADDON == 'mod_'.$classname) {
							print img_picto($langs->trans("Activated"), 'switch_on');
						} else {
							print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setmod&amp;token='.newToken().'&amp;value=mod_'.$classname.'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
						}
						print '</td>';

						$ticket = new Ticket($db);
						$ticket->initAsSpecimen();

						// Info
						$htmltooltip = '';
						$htmltooltip .= ''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
						$nextval = $module->getNextValue($mysoc, $ticket);
						if ("$nextval" != $langs->trans("NotAvailable")) { // Keep " on nextval
							$htmltooltip .= ''.$langs->trans("NextValue").': ';
							if ($nextval) {
								$htmltooltip .= $nextval.'<br>';
							} else {
								$htmltooltip .= $langs->trans($module->error).'<br>';
							}
						}

						print '<td class="center">';
						print $formcategory->textwithpicto('', $htmltooltip, 1, 0);
						print '</td>';

						print '</tr>';
					}
				}
			}
			closedir($handle);
		}
	}
}

print '</table>';
print '</div>';
print '<br>';



/*
 * Document templates generators
 */

print load_fiche_titre($langs->trans("TicketsModelModule"), '', '');

// Load array def with activated templates
$def = array();
$sql = "SELECT nom";
$sql .= " FROM ".MAIN_DB_PREFIX."document_model";
$sql .= " WHERE type = '".$db->escape($type)."'";
$sql .= " AND entity = ".$conf->entity;
$resql = $db->query($sql);
if ($resql) {
	$i = 0;
	$num_rows = $db->num_rows($resql);
	while ($i < $num_rows) {
		$array = $db->fetch_array($resql);
		if (is_array($array)) {
			array_push($def, $array[0]);
		}
		$i++;
	}
} else {
	dol_print_error($db);
}


print '<div class="div-table-responsive-no-min">';
print '<table class="noborder cenpercent">'."\n";
print '<tr class="liste_titre">'."\n";
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td class="center" width="60">'.$langs->trans("Status")."</td>\n";
print '<td class="center" width="60">'.$langs->trans("Default")."</td>\n";
print '<td class="center" width="50">'.$langs->trans("Preview").'</td>';
print '<td class="center" width="40">'.$langs->trans("ShortInfo").'</td>';
print "</tr>\n";

clearstatcache();

foreach ($dirmodels as $reldir) {
	foreach (array('', '/doc') as $valdir) {
		$realpath = $reldir."core/modules/ticket".$valdir;
		$dir = dol_buildpath($realpath);

		if (is_dir($dir)) {
			$handle = opendir($dir);
			if (is_resource($handle)) {
				$filelist = array();
				while (($file = readdir($handle)) !== false) {
					$filelist[] = $file;
				}
				closedir($handle);
				arsort($filelist);

				foreach ($filelist as $file) {
					if (preg_match('/\.modules\.php$/i', $file) && preg_match('/^(pdf_|doc_)/', $file)) {
						if (file_exists($dir.'/'.$file)) {
							$name = substr($file, 4, dol_strlen($file) - 16);
							$classname = substr($file, 0, dol_strlen($file) - 12);

							require_once $dir.'/'.$file;
							$module = new $classname($db);
							'@phan-var-force ModelePDFTicket $module';

							$modulequalified = 1;
							if ($module->version == 'development' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 2) {
								$modulequalified = 0;
							}
							if ($module->version == 'experimental' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 1) {
								$modulequalified = 0;
							}

							if ($modulequalified) {
								print '<tr class="oddeven"><td width="100">';
								print(empty($module->name) ? $name : $module->name);
								print "</td><td>\n";
								if (method_exists($module, 'info')) {
									print $module->info($langs);  // @phan-suppress-current-line PhanUndeclaredMethod
								} else {
									print $module->description;
								}
								print '</td>';

								// Active / Status
								if (in_array($name, $def)) {
									print '<td class="center">'."\n";
									print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=del&token='.newToken().'&value='.urlencode($name).'">';
									print img_picto($langs->trans("Enabled"), 'switch_on');
									print '</a>';
									print '</td>';
								} else {
									print '<td class="center">'."\n";
									print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=set&token='.newToken().'&value='.urlencode($name).'&scan_dir='.urlencode($module->scandir).'&label='.urlencode($module->name).'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
									print "</td>";
								}

								// Default Template
								print '<td class="center">';
								if (getDolGlobalString("TICKET_ADDON_PDF") == $name) {
									print img_picto($langs->trans("Default"), 'on');
								} else {
									print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setdoc&amp;token='.newToken().'&amp;value='.$name.'&amp;scan_dir='.$module->scandir.'&amp;label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
								}
								print '</td>';

								// Preview
								print '<td class="center">';
								if ($module->type == 'pdf') {
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.urlencode($name).'">'.img_object($langs->trans("Preview"), 'pdf').'</a>';
								} else {
									print img_object($langs->trans("PreviewNotAvailable"), 'generic');
								}
								print '</td>';

								// Info
								$htmltooltip = ''.$langs->trans("Name").': '.$module->name;
								$htmltooltip .= '<br>'.$langs->trans("Type").': '.($module->type ? $module->type : $langs->trans("Unknown"));
								if ($module->type == 'pdf') {
									$htmltooltip .= '<br>'.$langs->trans("Width").'/'.$langs->trans("Height").': '.$module->page_largeur.'/'.$module->page_hauteur;
								}
								$htmltooltip .= '<br>'.$langs->trans("Path").': '.preg_replace('/^\//', '', $realpath).'/'.$file;

								$htmltooltip .= '<br><br><u>'.$langs->trans("FeaturesSupported").':</u>';
								$htmltooltip .= '<br>'.$langs->trans("Logo").': '.yn($module->option_logo, 1, 1);
								$htmltooltip .= '<br>'.$langs->trans("MultiLanguage").': '.yn($module->option_multilang, 1, 1);
								//$htmltooltip .= '<br>'.$langs->trans("Discounts").': '.yn($module->option_escompte,1,1);
								//$htmltooltip .= '<br>'.$langs->trans("CreditNote").': '.yn($module->option_credit_note,1,1);
								//$htmltooltip .= '<br>'.$langs->trans("WatermarkOnDraftOrders").': '.yn($module->option_draft_watermark, 1, 1);


								print '<td class="center">';
								print $formcategory->textwithpicto('', $htmltooltip, 1, 0);
								print '</td>';

								print "</tr>\n";
							}
						}
					}
				}
			}
		}
	}
}

print '</table>';
print '</div><br>';


print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'" enctype="multipart/form-data" >';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="setvarworkflow">';
print '<input type="hidden" name="page_y" value="">';

/*
 * Other Parameters
 */

print load_fiche_titre($langs->trans("Other"), '', '');
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td>'.$langs->trans("Status")."</td>\n";
print '<td class="center" width="40">'.$langs->trans("ShortInfo").'</td>';
print "</tr>\n";

// Auto mark ticket as read when created from backoffice
print '<tr class="oddeven"><td>'.$langs->trans("TicketsAutoReadTicket").'</td>';
print '<td class="left">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('TICKET_AUTO_READ_WHEN_CREATED_FROM_BACKEND');
} else {
	$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
	print $formcategory->selectarray("TICKET_AUTO_READ_WHEN_CREATED_FROM_BACKEND", $arrval, getDolGlobalString('TICKET_AUTO_READ_WHEN_CREATED_FROM_BACKEND'));
}
print '</td>';
print '<td class="center">';
print $formcategory->textwithpicto('', $langs->trans("TicketsAutoReadTicketHelp"), 1, 'help');
print '</td>';
print '</tr>';

// Auto assign ticket to user who created it
print '<tr class="oddeven">';
print '<td>'.$langs->trans("TicketsAutoAssignTicket").'</td>';
print '<td class="left">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('TICKET_AUTO_ASSIGN_USER_CREATE');
} else {
	$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
	print $formcategory->selectarray("TICKET_AUTO_ASSIGN_USER_CREATE", $arrval, getDolGlobalString('TICKET_AUTO_ASSIGN_USER_CREATE'));
}
print '</td>';
print '<td class="center">';
print $formcategory->textwithpicto('', $langs->trans("TicketsAutoAssignTicketHelp"), 1, 'help');
print '</td>';
print '</tr>';

// Auto notify contacts when closing the ticket
print '<tr class="oddeven"><td>'.$langs->trans("TicketsAutoNotifyClose").'</td>';
print '<td class="left">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('TICKET_NOTIFY_AT_CLOSING');
} else {
	$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
	print $formcategory->selectarray("TICKET_NOTIFY_AT_CLOSING", $arrval, getDolGlobalString('TICKET_NOTIFY_AT_CLOSING'));
}
print '</td>';
print '<td class="center">';
print $formcategory->textwithpicto('', $langs->trans("TicketsAutoNotifyCloseHelp"), 1, 'help');
print '</td>';
print '</tr>';

// Automatically define status on answering a ticket
print '<tr class="oddeven"><td>'.$langs->trans("TicketAutoChangeStatusOnAnswer").'</td>';
print '<td class="left">';
print $formcategory->selectarray("TICKET_SET_STATUS_ON_ANSWER", $statuslist, getDolGlobalString('TICKET_SET_STATUS_ON_ANSWER'));
print '</td>';
print '<td class="center">';
print $formcategory->textwithpicto('', $langs->trans("TicketAutoChangeStatusOnAnswerHelp"), 1, 'help');
print '</td>';
print '</tr>';

// Check "Notify thirdparty" on ticket creation
print '<tr class="oddeven"><td>'.$langs->trans("TicketAutoCheckNotifyThirdParty").'</td>';
print '<td class="left">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('TICKET_CHECK_NOTIFY_THIRDPARTY_AT_CREATION');
} else {
	$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
	print $formcategory->selectarray("TICKET_CHECK_NOTIFY_THIRDPARTY_AT_CREATION", $arrval, getDolGlobalString('TICKET_CHECK_NOTIFY_THIRDPARTY_AT_CREATION'));
}
print '</td>';
print '<td class="center">';
print $formcategory->textwithpicto('', $langs->trans("TicketAutoCheckNotifyThirdPartyHelp"), 1, 'help');
print '</td>';
print '</tr>';

// Assign contact to a message
print '<tr class="oddeven"><td>'.$langs->trans("TicketAssignContactToMessage").'</td>';
print '<td class="left">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('TICKET_ASSIGN_CONTACT_TO_MESSAGE');
} else {
	$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
	print $formcategory->selectarray("TICKET_ASSIGN_CONTACT_TO_MESSAGE", $arrval, getDolGlobalString('TICKET_ASSIGN_CONTACT_TO_MESSAGE'));
}
print '</td>';
print '<td class="center">';
print $formcategory->textwithpicto('', $langs->trans("TicketAssignContactToMessageHelp"), 1, 'help');
print '</td>';
print '</tr>';

if (isModEnabled('product')) {
	$htmlname = "product_category_id";
	print '<tr class="oddeven"><td>'.$langs->trans("TicketChooseProductCategory").'</td>';
	print '<td class="left">';
	print img_picto('', 'category', 'class="pictofixedwidth"');
	$formcategory->selectProductCategory(getDolGlobalString('TICKET_PRODUCT_CATEGORY'), $htmlname);
	if ($conf->use_javascript_ajax) {
		print ajax_combobox('select_'.$htmlname);
	}
	print '</td>';
	print '<td class="center">';
	print $formcategory->textwithpicto('', $langs->trans("TicketChooseProductCategoryHelp"), 1, 'help');
	print '</td>';
	print '</tr>';
}

print '<tr class="oddeven">';
print '<td>'.$langs->trans("TicketsDelayBeforeFirstAnswer")."</td>";
print '<td class="left">
	<input type="number" value="'.getDolGlobalString('TICKET_DELAY_BEFORE_FIRST_RESPONSE').'" name="delay_first_response" class="width50">
	</td>';
print '<td class="center">';
print $formcategory->textwithpicto('', $langs->trans("TicketsDelayBeforeFirstAnswerHelp"), 1, 'help');
print '</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("TicketsDelayBetweenAnswers")."</td>";
print '<td class="left">
	<input type="number" value="'.getDolGlobalString('TICKET_DELAY_SINCE_LAST_RESPONSE').'" name="delay_between_responses" class="width50">
	</td>';
print '<td class="center">';
print $formcategory->textwithpicto('', $langs->trans("TicketsDelayBetweenAnswersHelp"), 1, 'help');
print '</td>';
print '</tr>';

print '</table><br>';

print $formcategory->buttonsSaveCancel("Save", '', array(), 0, 'reposition');

print '</form>';


/*
 * Notification
 */

// Admin var of module
print load_fiche_titre($langs->trans("Notification"), '', '');

print '<table class="noborder centpercent">';

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'" enctype="multipart/form-data" >';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="setvar">';
print '<input type="hidden" name="page_y" value="">';

print '<tr class="liste_titre">';
print '<td colspan="2">'.$langs->trans("Email").'</td>';
print '<td class="center" width="40">'.$langs->trans("ShortInfo").'</td>';
print "</tr>\n";

if (!getDolGlobalString('FCKEDITOR_ENABLE_MAIL')) {
	print '<tr>';
	print '<td colspan="2"><div class="info">'.$langs->trans("TicketCkEditorEmailNotActivated").'</div></td>';
	print '<td class="center" width="40">'.$langs->trans("ShortInfo").'</td>';
	print "</tr>\n";
}

// TODO Use module notification instead...

// Email to send notifications
print '<tr class="oddeven"><td>'.$langs->trans("TicketEmailNotificationFrom").'</td>';
print '<td class="left">';
print '<input type="text" class="minwidth200" name="TICKET_NOTIFICATION_EMAIL_FROM" value="' . getDolGlobalString('TICKET_NOTIFICATION_EMAIL_FROM').'"></td>';
print '<td class="center">';
print $formcategory->textwithpicto('', $langs->trans("TicketEmailNotificationFromHelp"), 1, 'help');
print '</td>';
print '</tr>';

// Email for notification of TICKET_CREATE
print '<tr class="oddeven"><td>'.$langs->trans("TicketEmailNotificationTo").'</td>';
print '<td class="left">';
print '<input type="text" class="minwidth200" name="TICKET_NOTIFICATION_EMAIL_TO" value="'.(getDolGlobalString('TICKET_NOTIFICATION_EMAIL_TO') ? $conf->global->TICKET_NOTIFICATION_EMAIL_TO : '').'"></td>';
print '<td class="center">';
print $formcategory->textwithpicto('', $langs->trans("TicketEmailNotificationToHelp"), 1, 'help');
print '</td>';
print '</tr>';

// Also send to TICKET_NOTIFICATION_EMAIL_TO for responses (not only creation)
if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {
	print '<tr class="oddeven"><td>'.$langs->trans("TicketsEmailAlsoSendToMainAddress").'</td>';
	print '<td class="left">';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('TICKET_NOTIFICATION_ALSO_MAIN_ADDRESS');
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $formcategory->selectarray("TICKET_NOTIFICATION_ALSO_MAIN_ADDRESS", $arrval, $conf->global->TICKET_NOTIFICATION_ALSO_MAIN_ADDRESS);
	}
	print '</td>';
	print '<td class="center">';
	print $formcategory->textwithpicto('', $langs->trans("TicketsEmailAlsoSendToMainAddressHelp"), 1, 'help');
	print '</td>';
	print '</tr>';
}

// Message header
$mail_intro = getDolGlobalString('TICKET_MESSAGE_MAIL_INTRO', '');
print '<tr class="oddeven"><td>'.$langs->trans("TicketMessageMailIntro");
print '</td><td>';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
$doleditor = new DolEditor('TICKET_MESSAGE_MAIL_INTRO', $mail_intro, '100%', 90, 'dolibarr_mailings', '', false, true, getDolGlobalInt('FCKEDITOR_ENABLE_MAIL'), ROWS_2, '70');
$doleditor->Create();
print '</td>';
print '<td class="center">';
print $formcategory->textwithpicto('', $langs->trans("TicketMessageMailIntroHelpAdmin"), 1, 'help');
print '</td></tr>';

// Message footer
$mail_signature = getDolGlobalString('TICKET_MESSAGE_MAIL_SIGNATURE');
print '<tr class="oddeven"><td>'.$langs->trans("TicketMessageMailFooter").'</label>';
print '</td><td>';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
$doleditor = new DolEditor('TICKET_MESSAGE_MAIL_SIGNATURE', $mail_signature, '100%', 90, 'dolibarr_mailings', '', false, true, getDolGlobalInt('FCKEDITOR_ENABLE_MAIL'), ROWS_2, '70');
$doleditor->Create();
print '</td>';
print '<td class="center">';
print $formcategory->textwithpicto('', $langs->trans("TicketMessageMailFooterHelpAdmin"), 1, 'help');
print '</td></tr>';

print '</table>';

print $formcategory->buttonsSaveCancel("Save", '', array(), 0, 'reposition');

print '</form>';

// End of page
llxFooter();
$db->close();
