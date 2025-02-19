<?php
/* Copyright (C) 2013-2018	Jean-François FERRY	<hello@librethic.io>
 * Copyright (C) 2016		Christophe Battarel	<christophe@altairis.fr>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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
 *     \file        admin/ticket_public.php
 *     \ingroup     ticket
 *     \brief       Page to public interface of module Ticket
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT."/ticket/class/ticket.class.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/ticket.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/class/html.formcategory.class.php";

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 *
 * @var string $dolibarr_main_url_root
 */

// Load translation files required by the page
$langs->loadLangs(array("admin", "ticket"));

// Access control
if (!$user->admin) {
	accessforbidden();
}

// Parameters
$value = GETPOST('value', 'alpha');
$action = GETPOST('action', 'aZ09');
$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scandir', 'alpha');
$type = 'ticket';


/*
 * Actions
 */
$error = 0;
$errors = array();

if ($action == 'setTICKET_ENABLE_PUBLIC_INTERFACE') {
	if (GETPOST('value')) {
		$res = dolibarr_set_const($db, 'TICKET_ENABLE_PUBLIC_INTERFACE', 1, 'chaine', 0, '', $conf->entity);
	} else {
		$res = dolibarr_set_const($db, 'TICKET_ENABLE_PUBLIC_INTERFACE', 0, 'chaine', 0, '', $conf->entity);
	}
	if (!($res > 0)) {
		$error++;
		$errors[] = $db->lasterror();
	}
} elseif ($action == 'setvar') {
	include_once DOL_DOCUMENT_ROOT."/core/lib/files.lib.php";

	if (GETPOSTISSET('TICKET_ENABLE_PUBLIC_INTERFACE')) {	// only for no js case
		$param_enable_public_interface = GETPOST('TICKET_ENABLE_PUBLIC_INTERFACE', 'alpha');
		$res = dolibarr_set_const($db, 'TICKET_ENABLE_PUBLIC_INTERFACE', $param_enable_public_interface, 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$error++;
			$errors[] = $db->lasterror();
		}
	}

	if (GETPOSTISSET('TICKET_SHOW_COMPANY_LOGO')) {	// only for no js case
		$param_show_module_logo = GETPOST('TICKET_SHOW_COMPANY_LOGO', 'alpha');
		$res = dolibarr_set_const($db, 'TICKET_SHOW_COMPANY_LOGO', $param_show_module_logo, 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$error++;
			$errors[] = $db->lasterror();
		}
	}

	$topic_interface = GETPOST('TICKET_PUBLIC_INTERFACE_TOPIC', 'alphanohtml');
	if (!empty($topic_interface)) {
		$res = dolibarr_set_const($db, 'TICKET_PUBLIC_INTERFACE_TOPIC', $topic_interface, 'chaine', 0, '', $conf->entity);
	} else {
		$res = dolibarr_set_const($db, 'TICKET_PUBLIC_INTERFACE_TOPIC', '', 'chaine', 0, '', $conf->entity);
	}
	if (!($res > 0)) {
		$error++;
		$errors[] = $db->lasterror();
	}

	$text_home = GETPOST('TICKET_PUBLIC_TEXT_HOME', 'restricthtml');
	if (GETPOSTISSET('TICKET_PUBLIC_TEXT_HOME')) {
		$res = dolibarr_set_const($db, 'TICKET_PUBLIC_TEXT_HOME', $text_home, 'chaine', 0, '', $conf->entity);
	} else {
		$res = dolibarr_set_const($db, 'TICKET_PUBLIC_TEXT_HOME', $langs->trans('TicketPublicInterfaceTextHome'), 'chaine', 0, '', $conf->entity);
	}
	if (!($res > 0)) {
		$error++;
		$errors[] = $db->lasterror();
	}

	$text_help = GETPOST('TICKET_PUBLIC_TEXT_HELP_MESSAGE', 'restricthtml');
	if (!empty($text_help)) {
		$res = dolibarr_set_const($db, 'TICKET_PUBLIC_TEXT_HELP_MESSAGE', $text_help, 'chaine', 0, '', $conf->entity);
	} else {
		$res = dolibarr_set_const($db, 'TICKET_PUBLIC_TEXT_HELP_MESSAGE', $langs->trans('TicketPublicPleaseBeAccuratelyDescribe'), 'chaine', 0, '', $conf->entity);
	}
	if (!($res > 0)) {
		$error++;
		$errors[] = $db->lasterror();
	}

	$mail_new_ticket = GETPOST('TICKET_MESSAGE_MAIL_NEW', 'restricthtml');
	if (!empty($mail_new_ticket)) {
		$res = dolibarr_set_const($db, 'TICKET_MESSAGE_MAIL_NEW', $mail_new_ticket, 'chaine', 0, '', $conf->entity);
	} else {
		$res = dolibarr_set_const($db, 'TICKET_MESSAGE_MAIL_NEW', $langs->trans('TicketMessageMailNewText'), 'chaine', 0, '', $conf->entity);
	}
	if (!($res > 0)) {
		$error++;
		$errors[] = $db->lasterror();
	}

	$url_interface = GETPOST('TICKET_URL_PUBLIC_INTERFACE', 'alpha');
	if (!empty($url_interface)) {
		$res = dolibarr_set_const($db, 'TICKET_URL_PUBLIC_INTERFACE', $url_interface, 'chaine', 0, '', $conf->entity);
	} else {
		$res = dolibarr_set_const($db, 'TICKET_URL_PUBLIC_INTERFACE', '', 'chaine', 0, '', $conf->entity);
	}
	if (!($res > 0)) {
		$error++;
		$errors[] = $db->lasterror();
	}

	$param_public_notification_new_message_default_email = GETPOST('TICKET_PUBLIC_NOTIFICATION_NEW_MESSAGE_DEFAULT_EMAIL', 'alpha');
	$res = dolibarr_set_const($db, 'TICKET_PUBLIC_NOTIFICATION_NEW_MESSAGE_DEFAULT_EMAIL', $param_public_notification_new_message_default_email, 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
		$errors[] = $db->lasterror();
	}

	// For compatibility when javascript is not enabled
	if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2 && empty($conf->use_javascript_ajax)) {
		$param_notification_also_main_addressemail = GETPOST('TICKET_NOTIFICATION_ALSO_MAIN_ADDRESS', 'alpha');
		$res = dolibarr_set_const($db, 'TICKET_NOTIFICATION_ALSO_MAIN_ADDRESS', $param_notification_also_main_addressemail, 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$error++;
			$errors[] = $db->lasterror();
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
		if (!$error) {
			if ($code == 'TICKET_EMAIL_MUST_EXISTS') {
				$res = dolibarr_del_const($db, 'TICKET_CREATE_THIRD_PARTY_WITH_CONTACT_IF_NOT_EXIST', $conf->entity);
				if (!($res > 0)) {
					$error++;
					$errors[] = $db->lasterror();
				}
			} elseif ($code == 'TICKET_CREATE_THIRD_PARTY_WITH_CONTACT_IF_NOT_EXIST') {
				$res = dolibarr_del_const($db, 'TICKET_EMAIL_MUST_EXISTS', $conf->entity);
				if (!($res > 0)) {
					$error++;
					$errors[] = $db->lasterror();
				}
			}
		}
	}
} elseif (preg_match('/del_(.*)/', $action, $reg)) {
	$code = $reg[1];
	$res = dolibarr_del_const($db, $code, $conf->entity);
	if (!($res > 0)) {
		$error++;
		$errors[] = $db->lasterror();
	}
}

if ($action != '') {
	if (!$error) {
		$db->commit();
		setEventMessage($langs->trans('SetupSaved'));
		header("Location: " . $_SERVER['PHP_SELF']);
		exit;
	} else {
		$db->rollback();
		setEventMessages('', $errors, 'errors');
	}
}


/*
 * View
 */

$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

$form = new Form($db);
$formcategory = new FormCategory($db);

$help_url = "FR:Module_Ticket";
$page_name = "TicketSetup";
llxHeader('', $langs->trans($page_name), $help_url, '', 0, 0, '', '', '', 'mod-admin page-ticket_public');

// Subheader
$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// Configuration header
$head = ticketAdminPrepareHead();

print dol_get_fiche_head($head, 'public', $langs->trans("Module56000Name"), -1, "ticket");

$param = '';

print '<br>';

$enabledisablehtml = $langs->trans("TicketsActivatePublicInterface").' ';
if (!getDolGlobalInt('TICKET_ENABLE_PUBLIC_INTERFACE')) {
	// Button off, click to enable
	$enabledisablehtml .= '<a class="reposition valignmiddle" href="'.$_SERVER["PHP_SELF"].'?action=setTICKET_ENABLE_PUBLIC_INTERFACE&token='.newToken().'&value=1'.$param.'">';
	$enabledisablehtml .= img_picto($langs->trans("Disabled"), 'switch_off');
	$enabledisablehtml .= '</a>';
} else {
	// Button on, click to disable
	$enabledisablehtml .= '<a class="reposition valignmiddle" href="'.$_SERVER["PHP_SELF"].'?action=setTICKET_ENABLE_PUBLIC_INTERFACE&token='.newToken().'&value=0'.$param.'">';
	$enabledisablehtml .= img_picto($langs->trans("Activated"), 'switch_on');
	$enabledisablehtml .= '</a>';
}
print $enabledisablehtml;
print '<input type="hidden" id="TICKET_ENABLE_PUBLIC_INTERFACE" name="TICKET_ENABLE_PUBLIC_INTERFACE" value="'.(!getDolGlobalInt('TICKET_ENABLE_PUBLIC_INTERFACE') ? 0 : 1).'">';

print dol_get_fiche_end();



if (getDolGlobalInt('TICKET_ENABLE_PUBLIC_INTERFACE')) {
	print '<br>';


	// Define $urlwithroot
	$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
	$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
	//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

	print '<span class="opacitymedium">'.$langs->trans("TicketPublicAccess").'</span> :<br>';
	print '<div class="urllink">';
	print '<input type="text" id="publicurlmember" class="quatrevingtpercentminusx" value="'.$urlwithroot.'/public/ticket/index.php?entity='.$conf->entity.'">';
	print '<a target="_blank" rel="noopener noreferrer" href="'.$urlwithroot.'/public/ticket/index.php?entity='.$conf->entity.'">'.img_picto('', 'globe', 'class="paddingleft"').'</a>';
	print '</div>';
	print ajax_autoselect('publicurlmember');


	print '<br><br>';


	print '<form method="post" action="'.$_SERVER['PHP_SELF'].'" enctype="multipart/form-data" >';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="setvar">';

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><td>'.$langs->trans("Parameters").'</td>';
	print '<td class="left">';
	print '</td>';
	print '<td class="center width75">';
	print '</td>';
	print '</tr>';

	// Enable Captcha code
	print '<tr class="oddeven">';
	print '<td>'.$langs->trans("TicketUseCaptchaCode").'</td>';
	print '<td class="left">';
	if (function_exists("imagecreatefrompng")) {
		if (!empty($conf->use_javascript_ajax)) {
			print ajax_constantonoff('MAIN_SECURITY_ENABLECAPTCHA_TICKET');
		} else {
			if (!getDolGlobalInt('MAIN_SECURITY_ENABLECAPTCHA_TICKET')) {
				print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_MAIN_SECURITY_ENABLECAPTCHA_TICKET&token='.newToken().'">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
			} else {
				print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_MAIN_SECURITY_ENABLECAPTCHA_TICKET&token='.newToken().'">'.img_picto($langs->trans("Enabled"), 'on').'</a>';
			}
		}
	} else {
		$desc = $form->textwithpicto('', $langs->transnoentities("EnableGDLibraryDesc"), 1, 'warning');
		print $desc;
	}
	print '</td>';
	print '<td class="center width75">';
	print $form->textwithpicto('', $langs->trans("TicketUseCaptchaCodeHelp"), 1, 'help');
	print '</td>';
	print '</tr>';

	// Check if email exists
	print '<tr class="oddeven"><td>'.$langs->trans("TicketsEmailMustExist").'</td>';
	print '<td class="left">';
	if (!getDolGlobalInt('TICKET_EMAIL_MUST_EXISTS')) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_TICKET_EMAIL_MUST_EXISTS&token='.newToken().'">' . img_picto($langs->trans('Disabled'), 'switch_off') . '</a>';
	} else {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_TICKET_EMAIL_MUST_EXISTS&token='.newToken().'">' . img_picto($langs->trans('Enabled'), 'switch_on') . '</a>';
	}
	print '</td>';
	print '<td class="center width75">';
	print $form->textwithpicto('', $langs->trans("TicketsEmailMustExistHelp"), 1, 'help');
	print '</td>';
	print '</tr>';

	// Auto fill the contact found from email
	// This option is a serious security hole. it allows to any non logged perso, to get the database of contacts or to check if an email is a customer or not. We must keep it as hidden option only.
	/*
	print '<tr class="oddeven"><td>'.$langs->trans("TicketCreateThirdPartyWithContactIfNotExist").'</td>';
	print '<td class="left">';
	if (!getDolGlobalInt('TICKET_CREATE_THIRD_PARTY_WITH_CONTACT_IF_NOT_EXIST')) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_TICKET_CREATE_THIRD_PARTY_WITH_CONTACT_IF_NOT_EXIST&token='.newToken().'">' . img_picto($langs->trans('Disabled'), 'switch_off') . '</a>';
	} else {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_TICKET_CREATE_THIRD_PARTY_WITH_CONTACT_IF_NOT_EXIST&token='.newToken().'">' . img_picto($langs->trans('Enabled'), 'switch_on') . '</a>';
	}
	print '</td>';
	print '<td class="center width75">';
	print $form->textwithpicto('', $langs->trans("TicketCreateThirdPartyWithContactIfNotExistHelp"), 1, 'help');
	print '</td>';
	print '</tr>';
	*/


	// Show logo for company
	print '<tr class="oddeven"><td>'.$langs->trans("TicketsShowCompanyLogo").'</td>';
	print '<td class="left">';
	if (!empty($conf->use_javascript_ajax)) {
		print ajax_constantonoff('TICKET_SHOW_COMPANY_LOGO');
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("TICKET_SHOW_COMPANY_LOGO", $arrval, getDolGlobalInt('TICKET_SHOW_COMPANY_LOGO'));
	}
	print '</td>';
	print '<td class="center width75">';
	print $form->textwithpicto('', $langs->trans("TicketsShowCompanyLogoHelp"), 1, 'help');
	print '</td>';
	print '</tr>';

	// show footer for company
	print '<tr class="oddeven"><td>'.$langs->trans("TicketsShowCompanyFooter").'</td>';
	print '<td class="left">';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('TICKET_SHOW_COMPANY_FOOTER');
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("TICKET_SHOW_COMPANY_FOOTER", $arrval, $conf->global->TICKET_SHOW_COMPANY_FOOTER);
	}
	print '</td>';
	print '<td class="center width75">';
	print $form->textwithpicto('', $langs->trans("TicketsShowCompanyFooterHelp"), 1, 'help');
	print '</td>';
	print '</tr>';

	// Show progression
	print '<tr class="oddeven"><td>'.$langs->trans("TicketsShowProgression").'</td>';
	print '<td class="left">';
	if (!empty($conf->use_javascript_ajax)) {
		print ajax_constantonoff('TICKET_SHOW_PROGRESSION');
	} else {
		if (!getDolGlobalInt('TICKET_SHOW_PROGRESSION')) {
			print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_TICKET_SHOW_PROGRESSION&token='.newToken().'">' . img_picto($langs->trans('Disabled'), 'switch_off') . '</a>';
		} else {
			print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_TICKET_SHOW_PROGRESSION&token='.newToken().'">' . img_picto($langs->trans('Enabled'), 'switch_on') . '</a>';
		}
	}
	print '</td>';
	print '<td class="center width75">';
	print $form->textwithpicto('', $langs->trans("TicketsShowProgressionHelp"), 1, 'help');
	print '</td>';
	print '</tr>';

	if (empty($conf->use_javascript_ajax)) {
		print '<tr><td colspan="3" align="center"><input type="submit" class="button button-save" value="'.$langs->trans("Save").'"></td>';
		print '</tr>';
	}

	if (!getDolGlobalInt('FCKEDITOR_ENABLE_MAIL')) {
		print '<tr>';
		print '<td colspan="3"><div class="info">'.$langs->trans("TicketCkEditorEmailNotActivated").'</div></td>';
		print "</tr>\n";
	}

	// Interface topic
	$url_interface = getDolGlobalString("TICKET_PUBLIC_INTERFACE_TOPIC");
	print '<tr><td>'.$langs->trans("TicketPublicInterfaceTopicLabelAdmin").'</label>';
	print '</td><td>';
	print '<input type="text"   name="TICKET_PUBLIC_INTERFACE_TOPIC" value="'.$url_interface.'" size="40" ></td>';
	print '</td>';
	print '<td class="center width75">';
	print $form->textwithpicto('', $langs->trans("TicketPublicInterfaceTopicHelp"), 1, 'help');
	print '</td></tr>';

	// Text on home page
	$public_text_home = getDolGlobalString('TICKET_PUBLIC_TEXT_HOME', '<span class="opacitymedium">'.$langs->trans("TicketPublicDesc").'</span>');
	print '<tr><td>'.$langs->trans("TicketPublicInterfaceTextHomeLabelAdmin").'</label>';
	print '</td><td>';
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$doleditor = new DolEditor('TICKET_PUBLIC_TEXT_HOME', $public_text_home, '100%', 180, 'dolibarr_notes', '', false, true, getDolGlobalInt('FCKEDITOR_ENABLE_TICKET'), ROWS_2, '70');
	$doleditor->Create();
	print '</td>';
	print '<td class="center">';
	print $form->textwithpicto('', $langs->trans("TicketPublicInterfaceTextHomeHelpAdmin"), 1, 'help');
	print '</td></tr>';

	// Text to help to enter a ticket
	$public_text_help_message = getDolGlobalString("TICKET_PUBLIC_TEXT_HELP_MESSAGE", $langs->trans('TicketPublicPleaseBeAccuratelyDescribe'));
	print '<tr><td>'.$langs->trans("TicketPublicInterfaceTextHelpMessageLabelAdmin").'</label>';
	print '</td><td>';
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$doleditor = new DolEditor('TICKET_PUBLIC_TEXT_HELP_MESSAGE', $public_text_help_message, '100%', 180, 'dolibarr_notes', '', false, true, getDolGlobalInt('FCKEDITOR_ENABLE_TICKET'), ROWS_2, '70');
	$doleditor->Create();
	print '</td>';
	print '<td class="center">';
	print $form->textwithpicto('', $langs->trans("TicketPublicInterfaceTextHelpMessageHelpAdmin"), 1, 'help');
	print '</td></tr>';

	// Add first contact id found in database from submitter email entered into public interface
	// Feature disabled: This has a security trouble. The public interface is a no login interface, so being able to show the contact info from an
	// email decided by the submiter allows anybody to get information on any contact (customer or supplier) in Dolibarr database.
	// He can even check if contact exists by trying any email if this feature is enabled.
	/*
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
	*/

	// Url public interface
	$url_interface = getDolGlobalString("TICKET_URL_PUBLIC_INTERFACE");
	print '<tr class="oddeven"><td>'.$langs->trans("UrlPublicInterfaceLabelAdmin").'</label>';
	print '</td><td>';
	print '<input type="text" class="minwidth500" name="TICKET_URL_PUBLIC_INTERFACE" value="'.$url_interface.'" placeholder="https://..."></td>';
	print '</td>';
	print '<td class="center">';
	print $form->textwithpicto('', $langs->trans("UrlPublicInterfaceHelpAdmin"), 1, 'help');
	print '</td></tr>';

	print '</table>';


	print '<br><br>';


	//print load_fiche_titre($langs->trans("Emails"));

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';

	print '<tr class="liste_titre"><td>'.$langs->trans("Emails").'</td>';
	print '<td class="left">';
	print '</td>';
	print '</tr>';

	// Text of email after creatio of a ticket
	$mail_mesg_new = getDolGlobalString("TICKET_MESSAGE_MAIL_NEW", $langs->trans('TicketNewEmailBody'));
	print '<tr class="oddeven"><td>';
	print $form->textwithpicto($langs->trans("TicketNewEmailBodyLabel"), $langs->trans("TicketNewEmailBodyHelp"), 1, 'help');
	print '</label>';
	print '</td><td>';
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$doleditor = new DolEditor('TICKET_MESSAGE_MAIL_NEW', $mail_mesg_new, '100%', 120, 'dolibarr_mailings', '', false, true, getDolGlobalInt('FCKEDITOR_ENABLE_MAIL'), ROWS_2, '70');
	$doleditor->Create();
	print '</td>';
	print '</tr>';

	// Activate email notification when a new message is added
	print '<tr class="oddeven"><td>';
	print $form->textwithpicto($langs->trans("TicketsPublicNotificationNewMessage"), $langs->trans("TicketsPublicNotificationNewMessageHelp"), 1, 'help');
	print '</td>';
	print '<td class="left">';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('TICKET_PUBLIC_NOTIFICATION_NEW_MESSAGE_ENABLED');
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("TICKET_PUBLIC_NOTIFICATION_NEW_MESSAGE_ENABLED", $arrval, getDolGlobalString("TICKET_PUBLIC_NOTIFICATION_NEW_MESSAGE_ENABLED"));
	}
	print '</td>';
	print '</tr>';

	// Send notification when a new message is added to a email if a user is not assigned to the ticket
	print '<tr class="oddeven"><td>';
	print $form->textwithpicto($langs->trans("TicketPublicNotificationNewMessageDefaultEmail"), $langs->trans("TicketPublicNotificationNewMessageDefaultEmailHelp"), 1, 'help');
	print '</td><td>';
	print '<input type="text" name="TICKET_PUBLIC_NOTIFICATION_NEW_MESSAGE_DEFAULT_EMAIL" value="'.getDolGlobalString("TICKET_PUBLIC_NOTIFICATION_NEW_MESSAGE_DEFAULT_EMAIL").'" size="40" ></td>';
	print '</td>';
	print '</tr>';

	// Also send to main email address
	if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {
		print '<tr class="oddeven"><td>'.$langs->trans("TicketsEmailAlsoSendToMainAddress");
		print $form->textwithpicto('', $langs->trans("TicketsEmailAlsoSendToMainAddressHelp", $langs->transnoentitiesnoconv("TicketEmailNotificationTo").' ('.$langs->transnoentitiesnoconv("Creation").')', $langs->trans("Settings")), 1, 'help');
		print '</td>';
		print '<td class="left">';
		if (!empty($conf->use_javascript_ajax)) {
			print ajax_constantonoff('TICKET_NOTIFICATION_ALSO_MAIN_ADDRESS');
		} else {
			$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
			print $form->selectarray("TICKET_NOTIFICATION_ALSO_MAIN_ADDRESS", $arrval, getDolGlobalInt('TICKET_NOTIFICATION_ALSO_MAIN_ADDRESS'));
		}
		print '</td>';
		print '</tr>';
	}

	print '</table>';
	print '</div>';

	print $form->buttonsSaveCancel("Save", '');

	print '</form>';
}

// End of page
llxFooter();
$db->close();
