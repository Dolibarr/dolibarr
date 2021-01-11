<?php
/* Copyright (C) 2013-2018	Jean-François FERRY	<hello@librethic.io>
 * Copyright (C) 2016		Christophe Battarel	<christophe@altairis.fr>
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

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT."/ticket/class/ticket.class.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/ticket.lib.php";

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

$error = 0;

/*
 * Actions
 */

if ($action == 'setTICKET_ENABLE_PUBLIC_INTERFACE')
{
	if (GETPOST('value')) dolibarr_set_const($db, 'TICKET_ENABLE_PUBLIC_INTERFACE', 1, 'chaine', 0, '', $conf->entity);
	else dolibarr_set_const($db, 'TICKET_ENABLE_PUBLIC_INTERFACE', 0, 'chaine', 0, '', $conf->entity);
}

if ($action == 'setvar') {
	include_once DOL_DOCUMENT_ROOT."/core/lib/files.lib.php";

	$topic_interface = GETPOST('TICKET_PUBLIC_INTERFACE_TOPIC', 'nohtml');
	if (!empty($topic_interface)) {
		$res = dolibarr_set_const($db, 'TICKET_PUBLIC_INTERFACE_TOPIC', $topic_interface, 'chaine', 0, '', $conf->entity);
	} else {
		$res = dolibarr_set_const($db, 'TICKET_PUBLIC_INTERFACE_TOPIC', '', 'chaine', 0, '', $conf->entity);
	}
	if (!$res > 0) {
		$error++;
	}

	$text_home = GETPOST('TICKET_PUBLIC_TEXT_HOME', 'restricthtml');
	if (!empty($text_home)) {
		$res = dolibarr_set_const($db, 'TICKET_PUBLIC_TEXT_HOME', $text_home, 'chaine', 0, '', $conf->entity);
	} else {
		$res = dolibarr_set_const($db, 'TICKET_PUBLIC_TEXT_HOME', $langs->trans('TicketPublicInterfaceTextHome'), 'chaine', 0, '', $conf->entity);
	}
	if (!$res > 0) {
		$error++;
	}

	$text_help = GETPOST('TICKET_PUBLIC_TEXT_HELP_MESSAGE', 'restricthtml');
	if (!empty($text_help)) {
		$res = dolibarr_set_const($db, 'TICKET_PUBLIC_TEXT_HELP_MESSAGE', $text_help, 'chaine', 0, '', $conf->entity);
	} else {
		$res = dolibarr_set_const($db, 'TICKET_PUBLIC_TEXT_HELP_MESSAGE', $langs->trans('TicketPublicPleaseBeAccuratelyDescribe'), 'chaine', 0, '', $conf->entity);
	}
	if (!$res > 0) {
		$error++;
	}

	$mail_new_ticket = GETPOST('TICKET_MESSAGE_MAIL_NEW', 'restricthtml');
	if (!empty($mail_new_ticket)) {
		$res = dolibarr_set_const($db, 'TICKET_MESSAGE_MAIL_NEW', $mail_new_ticket, 'chaine', 0, '', $conf->entity);
	} else {
		$res = dolibarr_set_const($db, 'TICKET_MESSAGE_MAIL_NEW', $langs->trans('TicketMessageMailNewText'), 'chaine', 0, '', $conf->entity);
	}
	if (!$res > 0) {
		$error++;
	}

	$url_interface = GETPOST('TICKET_URL_PUBLIC_INTERFACE', 'alpha');
	if (!empty($url_interface)) {
		$res = dolibarr_set_const($db, 'TICKET_URL_PUBLIC_INTERFACE', $url_interface, 'chaine', 0, '', $conf->entity);
	} else {
		$res = dolibarr_set_const($db, 'TICKET_URL_PUBLIC_INTERFACE', '', 'chaine', 0, '', $conf->entity);
	}
	if (!$res > 0) {
		$error++;
	}

	$param_public_notification_new_message_default_email = GETPOST('TICKET_PUBLIC_NOTIFICATION_NEW_MESSAGE_DEFAULT_EMAIL', 'alpha');
	$res = dolibarr_set_const($db, 'TICKET_PUBLIC_NOTIFICATION_NEW_MESSAGE_DEFAULT_EMAIL', $param_public_notification_new_message_default_email, 'chaine', 0, '', $conf->entity);
	if (!$res > 0) {
		$error++;
	}
}

if ($action == 'setvarother') {
	$param_enable_public_interface = GETPOST('TICKET_ENABLE_PUBLIC_INTERFACE', 'alpha');
	$res = dolibarr_set_const($db, 'TICKET_ENABLE_PUBLIC_INTERFACE', $param_enable_public_interface, 'chaine', 0, '', $conf->entity);
	if (!$res > 0) {
		$error++;
	}

	$param_must_exists = GETPOST('TICKET_EMAIL_MUST_EXISTS', 'alpha');
	$res = dolibarr_set_const($db, 'TICKET_EMAIL_MUST_EXISTS', $param_must_exists, 'chaine', 0, '', $conf->entity);
	if (!$res > 0) {
		$error++;
	}

	$param_disable_email = GETPOST('TICKET_DISABLE_CUSTOMER_MAILS', 'alpha');
	$res = dolibarr_set_const($db, 'TICKET_DISABLE_CUSTOMER_MAILS', $param_disable_email, 'chaine', 0, '', $conf->entity);
	if (!$res > 0) {
		$error++;
	}

   	$param_show_module_logo = GETPOST('TICKET_SHOW_COMPANY_LOGO', 'alpha');
   	$res = dolibarr_set_const($db, 'TICKET_SHOW_COMPANY_LOGO', $param_show_module_logo, 'chaine', 0, '', $conf->entity);
   	if (!$res > 0) {
	   	$error++;
   	}

	if ($conf->global->MAIN_FEATURES_LEVEL >= 2)
	{
		$param_notification_also_main_addressemail = GETPOST('TICKET_NOTIFICATION_ALSO_MAIN_ADDRESS', 'alpha');
		$res = dolibarr_set_const($db, 'TICKET_NOTIFICATION_ALSO_MAIN_ADDRESS', $param_notification_also_main_addressemail, 'chaine', 0, '', $conf->entity);
		if (!$res > 0) {
			$error++;
		}
	}
}



/*
 * View
 */

$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

$form = new Form($db);

$help_url = "FR:Module_Ticket";
$page_name = "TicketSetup";
llxHeader('', $langs->trans($page_name), $help_url);

// Subheader
$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// Configuration header
$head = ticketAdminPrepareHead();

print dol_get_fiche_head($head, 'public', $langs->trans("Module56000Name"), -1, "ticket");

print '<span class="opacitymedium">'.$langs->trans("TicketPublicAccess").'</span> : <a class="wordbreak" href="'.dol_buildpath('/public/ticket/index.php', 1).'" target="_blank" >'.dol_buildpath('/public/ticket/index.php', 2).'</a>';

print dol_get_fiche_end();


$enabledisablehtml = $langs->trans("TicketsActivatePublicInterface").' ';
if (empty($conf->global->TICKET_ENABLE_PUBLIC_INTERFACE))
{
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
print '<input type="hidden" id="TICKET_ENABLE_PUBLIC_INTERFACE" name="TICKET_ENABLE_PUBLIC_INTERFACE" value="'.(empty($conf->global->TICKET_ENABLE_PUBLIC_INTERFACE) ? 0 : 1).'">';

print '<br><br>';

if (!empty($conf->global->TICKET_ENABLE_PUBLIC_INTERFACE))
{
	if (empty($conf->use_javascript_ajax)) {
		print '<form method="post" action="'.$_SERVER['PHP_SELF'].'" enctype="multipart/form-data" >';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="setvarother">';
	}

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><td>'.$langs->trans("Parameters").'</td>';
	print '<td class="left">';
	print '</td>';
	print '<td class="center">';
	print '</td>';
	print '</tr>';

	// Check if email exists
	print '<tr class="oddeven"><td>'.$langs->trans("TicketsEmailMustExist").'</td>';
	print '<td class="left">';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('TICKET_EMAIL_MUST_EXISTS');
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("TICKET_EMAIL_MUST_EXISTS", $arrval, $conf->global->TICKET_EMAIL_MUST_EXISTS);
	}
	print '</td>';
	print '<td class="center">';
	print $form->textwithpicto('', $langs->trans("TicketsEmailMustExistHelp"), 1, 'help');
	print '</td>';
	print '</tr>';

	/*if ($conf->global->MAIN_FEATURES_LEVEL >= 2)
    {
    	// Show logo for module
    	print '<tr class="oddeven"><td>' . $langs->trans("TicketsShowModuleLogo") . '</td>';
    	print '<td class="left">';
    	if ($conf->use_javascript_ajax) {
    	    print ajax_constantonoff('TICKET_SHOW_MODULE_LOGO');
    	} else {
    	    $arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
    	    print $form->selectarray("TICKET_SHOW_MODULE_LOGO", $arrval, $conf->global->TICKET_SHOW_MODULE_LOGO);
    	}
    	print '</td>';
    	print '<td class="center">';
    	print $form->textwithpicto('', $langs->trans("TicketsShowModuleLogoHelp"), 1, 'help');
    	print '</td>';
    	print '</tr>';
    }*/

	// Show logo for company
	print '<tr class="oddeven"><td>'.$langs->trans("TicketsShowCompanyLogo").'</td>';
	print '<td class="left">';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('TICKET_SHOW_COMPANY_LOGO');
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("TICKET_SHOW_COMPANY_LOGO", $arrval, $conf->global->TICKET_SHOW_COMPANY_LOGO);
	}
	print '</td>';
	print '<td class="center">';
	print $form->textwithpicto('', $langs->trans("TicketsShowCompanyLogoHelp"), 1, 'help');
	print '</td>';
	print '</tr>';

	// Also send to main email address
	if ($conf->global->MAIN_FEATURES_LEVEL >= 2)
	{
		print '<tr class="oddeven"><td>'.$langs->trans("TicketsEmailAlsoSendToMainAddress").'</td>';
		print '<td class="left">';
		if ($conf->use_javascript_ajax) {
			print ajax_constantonoff('TICKET_NOTIFICATION_ALSO_MAIN_ADDRESS');
		} else {
			$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
			print $form->selectarray("TICKET_NOTIFICATION_ALSO_MAIN_ADDRESS", $arrval, $conf->global->TICKET_NOTIFICATION_ALSO_MAIN_ADDRESS);
		}
		print '</td>';
		print '<td class="center">';
		print $form->textwithpicto('', $langs->trans("TicketsEmailAlsoSendToMainAddressHelp"), 1, 'help');
		print '</td>';
		print '</tr>';
	}

	if (!$conf->use_javascript_ajax) {
		print '<tr class="impair"><td colspan="3" align="center"><input type="submit" class="button button-save" value="'.$langs->trans("Save").'"></td>';
		print '</tr>';
	}

	print '</table>';
	print '</div>';
	print '<br>';

	if (!$conf->use_javascript_ajax) {
		print '</form>';
	}

	// Admin var of module
	print load_fiche_titre($langs->trans("TicketParamMail"));

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';

	print '<form method="post" action="'.$_SERVER['PHP_SELF'].'" enctype="multipart/form-data" >';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="setvar">';

	print '<tr class="liste_titre">';
	print '<td colspan="3">'.$langs->trans("Parameter").'</td>';
	print "</tr>\n";

	if (empty($conf->global->FCKEDITOR_ENABLE_MAIL)) {
		print '<tr>';
		print '<td colspan="3"><div class="info">'.$langs->trans("TicketCkEditorEmailNotActivated").'</div></td>';
		print "</tr>\n";
	}

	// Interface topic
	$url_interface = $conf->global->TICKET_PUBLIC_INTERFACE_TOPIC;
	print '<tr><td>'.$langs->trans("TicketPublicInterfaceTopicLabelAdmin").'</label>';
	print '</td><td>';
	print '<input type="text"   name="TICKET_PUBLIC_INTERFACE_TOPIC" value="'.$conf->global->TICKET_PUBLIC_INTERFACE_TOPIC.'" size="40" ></td>';
	print '</td>';
	print '<td class="center">';
	print $form->textwithpicto('', $langs->trans("TicketPublicInterfaceTopicHelp"), 1, 'help');
	print '</td></tr>';

	// Texte d'accueil homepage
	$public_text_home = $conf->global->TICKET_PUBLIC_TEXT_HOME ? $conf->global->TICKET_PUBLIC_TEXT_HOME : $langs->trans('TicketPublicInterfaceTextHome');
	print '<tr><td>'.$langs->trans("TicketPublicInterfaceTextHomeLabelAdmin").'</label>';
	print '</td><td>';
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$doleditor = new DolEditor('TICKET_PUBLIC_TEXT_HOME', $public_text_home, '100%', 180, 'dolibarr_notes', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_2, 70);
	$doleditor->Create();
	print '</td>';
	print '<td class="center">';
	print $form->textwithpicto('', $langs->trans("TicketPublicInterfaceTextHomeHelpAdmin"), 1, 'help');
	print '</td></tr>';

	// Texte d'aide à la saisie du message
	$public_text_help_message = $conf->global->TICKET_PUBLIC_TEXT_HELP_MESSAGE ? $conf->global->TICKET_PUBLIC_TEXT_HELP_MESSAGE : $langs->trans('TicketPublicPleaseBeAccuratelyDescribe');
	print '<tr><td>'.$langs->trans("TicketPublicInterfaceTextHelpMessageLabelAdmin").'</label>';
	print '</td><td>';
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$doleditor = new DolEditor('TICKET_PUBLIC_TEXT_HELP_MESSAGE', $public_text_help_message, '100%', 180, 'dolibarr_notes', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_2, 70);
	$doleditor->Create();
	print '</td>';
	print '<td class="center">';
	print $form->textwithpicto('', $langs->trans("TicketPublicInterfaceTextHelpMessageHelpAdmin"), 1, 'help');
	print '</td></tr>';

	// Activate email creation to user
	print '<tr class="pair"><td>'.$langs->trans("TicketsDisableCustomerEmail").'</td>';
	print '<td class="left">';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('TICKET_DISABLE_CUSTOMER_MAILS');
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("TICKET_DISABLE_CUSTOMER_MAILS", $arrval, $conf->global->TICKET_DISABLE_CUSTOMER_MAILS);
	}
	print '</td>';
	print '<td class="center">';
	print $form->textwithpicto('', $langs->trans("TicketsDisableEmailHelp"), 1, 'help');
	print '</td>';
	print '</tr>';

	// Texte de création d'un ticket
	$mail_mesg_new = $conf->global->TICKET_MESSAGE_MAIL_NEW ? $conf->global->TICKET_MESSAGE_MAIL_NEW : $langs->trans('TicketNewEmailBody');
	print '<tr><td>'.$langs->trans("TicketNewEmailBodyLabel").'</label>';
	print '</td><td>';
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$doleditor = new DolEditor('TICKET_MESSAGE_MAIL_NEW', $mail_mesg_new, '100%', 120, 'dolibarr_mailings', '', false, true, $conf->global->FCKEDITOR_ENABLE_MAIL, ROWS_2, 70);
	$doleditor->Create();
	print '</td>';
	print '<td class="center">';
	print $form->textwithpicto('', $langs->trans("TicketNewEmailBodyHelp"), 1, 'help');
	print '</td></tr>';

	// Url public interface
	$url_interface = $conf->global->TICKET_URL_PUBLIC_INTERFACE;
	print '<tr><td>'.$langs->trans("TicketUrlPublicInterfaceLabelAdmin").'</label>';
	print '</td><td>';
	print '<input type="text" class="minwidth500" name="TICKET_URL_PUBLIC_INTERFACE" value="'.$conf->global->TICKET_URL_PUBLIC_INTERFACE.'"></td>';
	print '</td>';
	print '<td class="center">';
	print $form->textwithpicto('', $langs->trans("TicketUrlPublicInterfaceHelpAdmin"), 1, 'help');
	print '</td></tr>';

	// Activate email notification when a new message is added
	print '<tr class="pair"><td>'.$langs->trans("TicketsPublicNotificationNewMessage").'</td>';
	print '<td class="left">';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('TICKET_PUBLIC_NOTIFICATION_NEW_MESSAGE_ENABLED');
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("TICKET_PUBLIC_NOTIFICATION_NEW_MESSAGE_ENABLED", $arrval, $conf->global->TICKET_PUBLIC_NOTIFICATION_NEW_MESSAGE_ENABLED);
	}
	print '</td>';
	print '<td align="center">';
	print $form->textwithpicto('', $langs->trans("TicketsPublicNotificationNewMessageHelp"), 1, 'help');
	print '</td>';
	print '</tr>';

	// Send notification when a new message is added to a email if a user is not assigned to the ticket
	print '<tr><td>'.$langs->trans("TicketPublicNotificationNewMessageDefaultEmail").'</label>';
	print '</td><td>';
	print '<input type="text" name="TICKET_PUBLIC_NOTIFICATION_NEW_MESSAGE_DEFAULT_EMAIL" value="'.$conf->global->TICKET_PUBLIC_NOTIFICATION_NEW_MESSAGE_DEFAULT_EMAIL.'" size="40" ></td>';
	print '</td>';
	print '<td align="center">';
	print $form->textwithpicto('', $langs->trans("TicketPublicNotificationNewMessageDefaultEmailHelp"), 1, 'help');
	print '</td></tr>';

	print '</table>';
	print '</div>';

	print '<div class="center"><input type="submit" class="button button-save" value="'.$langs->trans("Save").'"></div>';

	print '</form>';
}

// End of page
llxFooter();
$db->close();
