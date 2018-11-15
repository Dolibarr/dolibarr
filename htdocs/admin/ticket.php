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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *     \file        admin/ticket.php
 *     \ingroup     ticket
 *     \brief       This file is a module setup page
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT."/ticket/class/ticket.class.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/ticket.lib.php";

// Load translation files required by the page
$langs->load("ticket");

// Access control
if (!$user->admin) {
    accessforbidden();
}

// Parameters
$value = GETPOST('value', 'alpha');
$action = GETPOST('action', 'alpha');
$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scandir', 'alpha');
$type = 'ticket';

if ($action == 'updateMask') {
    $maskconstticket = GETPOST('maskconstticket', 'alpha');
    $maskticket = GETPOST('maskticket', 'alpha');

    if ($maskconstticket) {
        $res = dolibarr_set_const($db, $maskconstticket, $maskticket, 'chaine', 0, '', $conf->entity);
    }

    if (!$res > 0) {
        $error++;
    }

    if (!$error)
    {
        setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
    }
    else
    {
        setEventMessages($langs->trans("Error"), null, 'errors');
    }
} elseif ($action == 'setmod') {
    // TODO Verifier si module numerotation choisi peut etre active
    // par appel methode canBeActivated

    dolibarr_set_const($db, "TICKETSUP_ADDON", $value, 'chaine', 0, '', $conf->entity);
} elseif ($action == 'setvar') {
    include_once DOL_DOCUMENT_ROOT . "/core/lib/files.lib.php";

    $notification_email = GETPOST('TICKET_NOTIFICATION_EMAIL_FROM', 'alpha');
    if (!empty($notification_email)) {
        $res = dolibarr_set_const($db, 'TICKET_NOTIFICATION_EMAIL_FROM', $notification_email, 'chaine', 0, '', $conf->entity);
    } else {
        $res = dolibarr_set_const($db, 'TICKET_NOTIFICATION_EMAIL_FROM', '', 'chaine', 0, '', $conf->entity);
    }
    if (!$res > 0) {
        $error++;
    }

    // altairis : differentiate notification email FROM and TO
    $notification_email_to = GETPOST('TICKET_NOTIFICATION_EMAIL_TO', 'alpha');
    if (!empty($notification_email_to)) {
        $res = dolibarr_set_const($db, 'TICKET_NOTIFICATION_EMAIL_TO', $notification_email_to, 'chaine', 0, '', $conf->entity);
    } else {
        $res = dolibarr_set_const($db, 'TICKET_NOTIFICATION_EMAIL_TO', '', 'chaine', 0, '', $conf->entity);
    }
    if (!$res > 0) {
        $error++;
    }

    $mail_new_ticket = GETPOST('TICKET_MESSAGE_MAIL_NEW', 'alpha');
    if (!empty($mail_new_ticket)) {
        $res = dolibarr_set_const($db, 'TICKET_MESSAGE_MAIL_NEW', $mail_new_ticket, 'chaine', 0, '', $conf->entity);
    } else {
        $res = dolibarr_set_const($db, 'TICKET_MESSAGE_MAIL_NEW', $langs->trans('TicketMessageMailNewText'), 'chaine', 0, '', $conf->entity);
    }
    if (!$res > 0) {
        $error++;
    }

    $mail_intro = GETPOST('TICKET_MESSAGE_MAIL_INTRO', 'alpha');
    if (!empty($mail_intro)) {
        $res = dolibarr_set_const($db, 'TICKET_MESSAGE_MAIL_INTRO', $mail_intro, 'chaine', 0, '', $conf->entity);
    } else {
        $res = dolibarr_set_const($db, 'TICKET_MESSAGE_MAIL_INTRO', $langs->trans('TicketMessageMailIntroText'), 'chaine', 0, '', $conf->entity);
    }
    if (!$res > 0) {
        $error++;
    }

    $mail_signature = GETPOST('TICKET_MESSAGE_MAIL_SIGNATURE', 'alpha');
    if (!empty($mail_signature)) {
        $res = dolibarr_set_const($db, 'TICKET_MESSAGE_MAIL_SIGNATURE', $mail_signature, 'chaine', 0, '', $conf->entity);
    } else {
        $res = dolibarr_set_const($db, 'TICKET_MESSAGE_MAIL_SIGNATURE', $langs->trans('TicketMessageMailSignatureText'), 'chaine', 0, '', $conf->entity);
    }
    if (!$res > 0) {
        $error++;
    }

    $url_interface = GETPOST('TICKET_URL_PUBLIC_INTERFACE', 'alpha');
    if (!empty($mail_signature)) {
        $res = dolibarr_set_const($db, 'TICKET_URL_PUBLIC_INTERFACE', $url_interface, 'chaine', 0, '', $conf->entity);
    } else {
        $res = dolibarr_set_const($db, 'TICKET_URL_PUBLIC_INTERFACE', '', 'chaine', 0, '', $conf->entity);
    }
    if (!$res > 0) {
        $error++;
    }

    $topic_interface = GETPOST('TICKET_PUBLIC_INTERFACE_TOPIC', 'alpha');
    if (!empty($mail_signature)) {
        $res = dolibarr_set_const($db, 'TICKET_PUBLIC_INTERFACE_TOPIC', $topic_interface, 'chaine', 0, '', $conf->entity);
    } else {
        $res = dolibarr_set_const($db, 'TICKET_PUBLIC_INTERFACE_TOPIC', '', 'chaine', 0, '', $conf->entity);
    }
    if (!$res > 0) {
        $error++;
    }

    $text_home = GETPOST('TICKET_PUBLIC_TEXT_HOME', 'alpha');
    if (!empty($mail_signature)) {
        $res = dolibarr_set_const($db, 'TICKET_PUBLIC_TEXT_HOME', $text_home, 'chaine', 0, '', $conf->entity);
    } else {
        $res = dolibarr_set_const($db, 'TICKET_PUBLIC_TEXT_HOME', $langs->trans('TicketPublicInterfaceTextHome'), 'chaine', 0, '', $conf->entity);
    }
    if (!$res > 0) {
        $error++;
    }

    $text_help = GETPOST('TICKET_PUBLIC_TEXT_HELP_MESSAGE', 'alpha');
    if (!empty($text_help)) {
        $res = dolibarr_set_const($db, 'TICKET_PUBLIC_TEXT_HELP_MESSAGE', $text_help, 'chaine', 0, '', $conf->entity);
    } else {
        $res = dolibarr_set_const($db, 'TICKET_PUBLIC_TEXT_HELP_MESSAGE', $langs->trans('TicketPublicPleaseBeAccuratelyDescribe'), 'chaine', 0, '', $conf->entity);
    }
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

    $param_disable_email = GETPOST('TICKET_DISABLE_ALL_MAILS', 'alpha');
    $res = dolibarr_set_const($db, 'TICKET_DISABLE_ALL_MAILS', $param_disable_email, 'chaine', 0, '', $conf->entity);
    if (!$res > 0) {
        $error++;
    }

    $param_activate_log_by_email = GETPOST('TICKET_ACTIVATE_LOG_BY_EMAIL', 'alpha');
    $res = dolibarr_set_const($db, 'TICKET_ACTIVATE_LOG_BY_EMAIL', $param_activate_log_by_email, 'chaine', 0, '', $conf->entity);
    if (!$res > 0) {
        $error++;
    }

    if ($conf->global->MAIN_FEATURES_LEVEL >= 2)
    {
    	$param_show_module_logo = GETPOST('TICKET_SHOW_MODULE_LOGO', 'alpha');
    	$res = dolibarr_set_const($db, 'TICKET_SHOW_MODULE_LOGO', $param_show_module_logo, 'chaine', 0, '', $conf->entity);
    	if (!$res > 0) {
        	$error++;
    	}
    }

    if ($conf->global->MAIN_FEATURES_LEVEL >= 2)
    {
    	$param_notification_also_main_addressemail = GETPOST('TICKET_NOTIFICATION_ALSO_MAIN_ADDRESS', 'alpha');
	    $res = dolibarr_set_const($db, 'TICKET_NOTIFICATION_ALSO_MAIN_ADDRESS', $param_notification_also_main_addressemail, 'chaine', 0, '', $conf->entity);
	    if (!$res > 0) {
	        $error++;
	    }
    }

    $param_limit_view = GETPOST('TICKET_LIMIT_VIEW_ASSIGNED_ONLY', 'alpha');
    $res = dolibarr_set_const($db, 'TICKET_LIMIT_VIEW_ASSIGNED_ONLY', $param_limit_view, 'chaine', 0, '', $conf->entity);
    if (!$res > 0) {
        $error++;
    }

    $param_auto_assign = GETPOST('TICKET_AUTO_ASSIGN_USER_CREATE', 'alpha');
    $res = dolibarr_set_const($db, 'TICKET_AUTO_ASSIGN_USER_CREATE', $param_auto_assign, 'chaine', 0, '', $conf->entity);
    if (!$res > 0) {
        $error++;
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
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// Configuration header
$head = ticketAdminPrepareHead();

dol_fiche_head($head, 'settings', $langs->trans("Module56000Name"), -1, "ticket");

print $langs->trans("TicketSetupDictionaries") . ' : <a href="' . dol_buildpath('/admin/dict.php', 1) . '" >' . dol_buildpath('/admin/dict.php', 2) . '</a><br>';

print $langs->trans("TicketPublicAccess") . ' : <a href="' . dol_buildpath('/public/ticket/index.php', 1) . '" target="_blank" >' . dol_buildpath('/public/ticket/index.php', 2) . '</a>';

dol_fiche_end();


/*
 * Projects Numbering model
 */

print_titre($langs->trans("TicketNumberingModules"));

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="100">' . $langs->trans("Name") . '</td>';
print '<td>' . $langs->trans("Description") . '</td>';
print '<td>' . $langs->trans("Example") . '</td>';
print '<td align="center" width="60">' . $langs->trans("Activated") . '</td>';
print '<td align="center" width="80">' . $langs->trans("ShortInfo") . '</td>';
print "</tr>\n";

clearstatcache();

foreach ($dirmodels as $reldir) {
    $dir = dol_buildpath($reldir . "core/modules/ticket/");

    if (is_dir($dir)) {
        $handle = opendir($dir);
        if (is_resource($handle)) {

            while (($file = readdir($handle)) !== false) {
                if (preg_match('/^(mod_.*)\.php$/i', $file, $reg)) {
                    $file = $reg[1];
                    $classname = substr($file, 4);

                    include_once $dir . $file . '.php';

                    $module = new $file;

                    // Show modules according to features level
                    if ($module->version == 'development' && $conf->global->MAIN_FEATURES_LEVEL < 2) {
                        continue;
                    }

                    if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) {
                        continue;
                    }

                    if ($module->isEnabled()) {
                        print '<tr class="oddeven"><td>' . $module->name . "</td><td>\n";
                        print $module->info();
                        print '</td>';

                        // Show example of numbering model
                        print '<td class="nowrap">';
                        $tmp = $module->getExample();
                        if (preg_match('/^Error/', $tmp)) {
                            print '<div class="error">' . $langs->trans($tmp) . '</div>';
                        } elseif ($tmp == 'NotConfigured') {
                            print $langs->trans($tmp);
                        } else {
                            print $tmp;
                        }

                        print '</td>' . "\n";

                        print '<td align="center">';
                        if ($conf->global->TICKETSUP_ADDON == 'mod_' . $classname) {
                            print img_picto($langs->trans("Activated"), 'switch_on');
                        } else {
                            print '<a href="' . $_SERVER["PHP_SELF"] . '?action=setmod&amp;value=mod_' . $classname . '" alt="' . $langs->trans("Default") . '">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
                        }
                        print '</td>';

                        $ticket = new Ticket($db);
                        $ticket->initAsSpecimen();

                        // Info
                        $htmltooltip = '';
                        $htmltooltip .= '' . $langs->trans("Version") . ': <b>' . $module->getVersion() . '</b><br>';
                        $nextval = $module->getNextValue($mysoc, $ticket);
                        if ("$nextval" != $langs->trans("NotAvailable")) { // Keep " on nextval
                            $htmltooltip .= '' . $langs->trans("NextValue") . ': ';
                            if ($nextval) {
                                $htmltooltip .= $nextval . '<br>';
                            } else {
                                $htmltooltip .= $langs->trans($module->error) . '<br>';
                            }
                        }

                        print '<td align="center">';
                        print $form->textwithpicto('', $htmltooltip, 1, 0);
                        print '</td>';

                        print '</tr>';
                    }
                }
            }
            closedir($handle);
        }
    }
}

print '</table><br>';

if (!$conf->use_javascript_ajax) {
    print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" enctype="multipart/form-data" >';
    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
    print '<input type="hidden" name="action" value="setvarother">';
}
print_titre($langs->trans("TicketParamPublicInterface"));

print '<table class="noborder" width="100%">';

// Activate public interface
print '<tr class="pair"><td width="70%">' . $langs->trans("TicketsActivatePublicInterface") . '</td>';
print '<td align="left">';
if ($conf->use_javascript_ajax) {
    print ajax_constantonoff('TICKET_ENABLE_PUBLIC_INTERFACE');
} else {
    $arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
    print $form->selectarray("TICKET_ENABLE_PUBLIC_INTERFACE", $arrval, $conf->global->TICKET_ENABLE_PUBLIC_INTERFACE);
}
print '</td>';
print '<td align="center">';
print $form->textwithpicto('', $langs->trans("TicketsActivatePublicInterfaceHelp"), 1, 'help');
print '</td>';
print '</tr>';

// Check if email exists
print '<tr class="pair"><td width="70%">' . $langs->trans("TicketsEmailMustExist") . '</td>';
print '<td align="left">';
if ($conf->use_javascript_ajax) {
    print ajax_constantonoff('TICKET_EMAIL_MUST_EXISTS');
} else {
    $arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
    print $form->selectarray("TICKET_EMAIL_MUST_EXISTS", $arrval, $conf->global->TICKET_EMAIL_MUST_EXISTS);
}
print '</td>';
print '<td align="center">';
print $form->textwithpicto('', $langs->trans("TicketsEmailMustExistHelp"), 1, 'help');
print '</td>';
print '</tr>';

/*if ($conf->global->MAIN_FEATURES_LEVEL >= 2)
{
	// Show logo for module
	print '<tr class="pair"><td width="70%">' . $langs->trans("TicketsShowModuleLogo") . '</td>';
	print '<td align="left">';
	if ($conf->use_javascript_ajax) {
	    print ajax_constantonoff('TICKET_SHOW_MODULE_LOGO');
	} else {
	    $arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
	    print $form->selectarray("TICKET_SHOW_MODULE_LOGO", $arrval, $conf->global->TICKET_SHOW_MODULE_LOGO);
	}
	print '</td>';
	print '<td align="center">';
	print $form->textwithpicto('', $langs->trans("TicketsShowModuleLogoHelp"), 1, 'help');
	print '</td>';
	print '</tr>';
}*/

// Show logo for company
print '<tr class="pair"><td width="70%">' . $langs->trans("TicketsShowCompanyLogo") . '</td>';
print '<td align="left">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('TICKET_SHOW_COMPANY_LOGO');
} else {
	$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
	print $form->selectarray("TICKET_SHOW_COMPANY_LOGO", $arrval, $conf->global->TICKET_SHOW_COMPANY_LOGO);
}
print '</td>';
print '<td align="center">';
print $form->textwithpicto('', $langs->trans("TicketsShowCompanyLogoHelp"), 1, 'help');
print '</td>';
print '</tr>';

print '</table><br>';

print_titre($langs->trans("TicketParams"));
print '<table class="noborder" width="100%">';

// Activate email notifications
print '<tr class="pair"><td width="70%">' . $langs->trans("TicketsDisableEmail") . '</td>';
print '<td align="left">';
if ($conf->use_javascript_ajax) {
    print ajax_constantonoff('TICKET_DISABLE_ALL_MAILS');
} else {
    $arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
    print $form->selectarray("TICKET_DISABLE_ALL_MAILS", $arrval, $conf->global->TICKET_DISABLE_ALL_MAILS);
}
print '</td>';
print '<td align="center">';
print $form->textwithpicto('', $langs->trans("TicketsDisableEmailHelp"), 1, 'help');
print '</td>';
print '</tr>';

// Activate log by email
print '<tr class="pair"><td width="70%">' . $langs->trans("TicketsLogEnableEmail") . '</td>';
print '<td align="left">';
if ($conf->use_javascript_ajax) {
    print ajax_constantonoff('TICKET_ACTIVATE_LOG_BY_EMAIL');
} else {
    $arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
    print $form->selectarray("TICKET_ACTIVATE_LOG_BY_EMAIL", $arrval, $conf->global->TICKET_ACTIVATE_LOG_BY_EMAIL);
}
print '</td>';
print '<td align="center">';
print $form->textwithpicto('', $langs->trans("TicketsLogEnableEmailHelp"), 1, 'help');
print '</td>';
print '</tr>';

// Also send to main email address
if ($conf->global->MAIN_FEATURES_LEVEL >= 2)
{
	print '<tr class="pair"><td width="70%">' . $langs->trans("TicketsEmailAlsoSendToMainAddress") . '</td>';
	print '<td align="left">';
	if ($conf->use_javascript_ajax) {
	    print ajax_constantonoff('TICKET_NOTIFICATION_ALSO_MAIN_ADDRESS');
	} else {
	    $arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
	    print $form->selectarray("TICKET_NOTIFICATION_ALSO_MAIN_ADDRESS", $arrval, $conf->global->TICKET_NOTIFICATION_ALSO_MAIN_ADDRESS);
	}
	print '</td>';
	print '<td align="center">';
	print $form->textwithpicto('', $langs->trans("TicketsEmailAlsoSendToMainAddressHelp"), 1, 'help');
	print '</td>';
	print '</tr>';
}

// Limiter la vue des tickets à ceux assignés à l'utilisateur
print '<tr class="pair"><td width="70%">' . $langs->trans("TicketsLimitViewAssignedOnly") . '</td>';
print '<td align="left">';
if ($conf->use_javascript_ajax) {
    print ajax_constantonoff('TICKET_LIMIT_VIEW_ASSIGNED_ONLY');
} else {
    $arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
    print $form->selectarray("TICKET_LIMIT_VIEW_ASSIGNED_ONLY", $arrval, $conf->global->TICKET_LIMIT_VIEW_ASSIGNED_ONLY);
}
print '</td>';
print '<td align="center">';
print $form->textwithpicto('', $langs->trans("TicketsLimitViewAssignedOnlyHelp"), 1, 'help');
print '</td>';
print '</tr>';

if (!$conf->use_javascript_ajax) {
    print '<tr class="impair"><td colspan="3" align="center"><input type="submit" class="button" value="' . $langs->trans("Save") . '"></td>';
    print '</tr>';
}

// Auto assign ticket at user who created it
print '<tr class="pair"><td width="70%">' . $langs->trans("TicketsAutoAssignTicket") . '</td>';
print '<td align="left">';
if ($conf->use_javascript_ajax) {
    print ajax_constantonoff('TICKET_AUTO_ASSIGN_USER_CREATE');
} else {
    $arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
    print $form->selectarray("TICKET_AUTO_ASSIGN_USER_CREATE", $arrval, $conf->global->TICKET_AUTO_ASSIGN_USER_CREATE);
}
print '</td>';
print '<td align="center">';
print $form->textwithpicto('', $langs->trans("TicketsAutoAssignTicketHelp"), 1, 'help');
print '</td>';
print '</tr>';

print '</table><br>';

if (!$conf->use_javascript_ajax) {
    print '</form>';
}

// Admin var of module
print_titre($langs->trans("TicketParamMail"));

print '<table class="noborder" width="100%">';

print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" enctype="multipart/form-data" >';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="setvar">';

print '<tr class="liste_titre">';
print '<td colspan="3">' . $langs->trans("Email") . '</td>';
print "</tr>\n";

if (empty($conf->global->FCKEDITOR_ENABLE_MAIL)) {
    print '<tr>';
    print '<td colspan="3"><div class="info">' . $langs->trans("TicketCkEditorEmailNotActivated") . '</div></td>';
    print "</tr>\n";
}

// Email d'envoi des notifications
print '<tr class="pair"><td>' . $langs->trans("TicketEmailNotificationFrom") . '</td>';
print '<td align="left">';
print '<input type="text" name="TICKET_NOTIFICATION_EMAIL_FROM" value="' . $conf->global->TICKET_NOTIFICATION_EMAIL_FROM . '" size="20" ></td>';
print '<td align="center">';
print $form->textwithpicto('', $langs->trans("TicketEmailNotificationFromHelp"), 1, 'help');
print '</td>';
print '</tr>';

// Email de réception des notifications
print '<tr class="pair"><td>' . $langs->trans("TicketEmailNotificationTo") . '</td>';
print '<td align="left">';
print '<input type="text"   name="TICKET_NOTIFICATION_EMAIL_TO" value="' . (!empty($conf->global->TICKET_NOTIFICATION_EMAIL_TO) ? $conf->global->TICKET_NOTIFICATION_EMAIL_TO : $conf->global->TICKET_NOTIFICATION_EMAIL_FROM) . '" size="20" ></td>';
print '<td align="center">';
print $form->textwithpicto('', $langs->trans("TicketEmailNotificationToHelp"), 1, 'help');
print '</td>';
print '</tr>';

// Texte de création d'un ticket
$mail_mesg_new = $conf->global->TICKET_MESSAGE_MAIL_NEW ? $conf->global->TICKET_MESSAGE_MAIL_NEW : $langs->trans('TicketNewEmailBody');
print '<tr><td>' . $langs->trans("TicketNewEmailBodyLabel") . '</label>';
print '</td><td>';
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
$doleditor = new DolEditor('TICKET_MESSAGE_MAIL_NEW', $mail_mesg_new, '100%', 120, 'dolibarr_mailings', '', false, true, $conf->global->FCKEDITOR_ENABLE_MAIL, ROWS_2, 70);
$doleditor->Create();
print '</td>';
print '<td align="center">';
print $form->textwithpicto('', $langs->trans("TicketNewEmailBodyHelp"), 1, 'help');
print '</td></tr>';

// Texte d'introduction
$mail_intro = $conf->global->TICKET_MESSAGE_MAIL_INTRO ? $conf->global->TICKET_MESSAGE_MAIL_INTRO : $langs->trans('TicketMessageMailIntroText');
print '<tr><td>' . $langs->trans("TicketMessageMailIntroLabelAdmin") . '</label>';
print '</td><td>';
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
$doleditor = new DolEditor('TICKET_MESSAGE_MAIL_INTRO', $mail_intro, '100%', 120, 'dolibarr_mailings', '', false, true, $conf->global->FCKEDITOR_ENABLE_MAIL, ROWS_2, 70);
$doleditor->Create();
print '</td>';
print '<td align="center">';
print $form->textwithpicto('', $langs->trans("TicketMessageMailIntroHelpAdmin"), 1, 'help');
print '</td></tr>';

// Texte de signature
$mail_signature = $conf->global->TICKET_MESSAGE_MAIL_SIGNATURE ? $conf->global->TICKET_MESSAGE_MAIL_SIGNATURE : $langs->trans('TicketMessageMailSignatureText');
print '<tr><td>' . $langs->trans("TicketMessageMailSignatureLabelAdmin") . '</label>';
print '</td><td>';
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
$doleditor = new DolEditor('TICKET_MESSAGE_MAIL_SIGNATURE', $mail_signature, '100%', 120, 'dolibarr_mailings', '', false, true, $conf->global->FCKEDITOR_ENABLE_MAIL, ROWS_2, 70);
$doleditor->Create();
print '</td>';
print '<td align="center">';
print $form->textwithpicto('', $langs->trans("TicketMessageMailSignatureHelpAdmin"), 1, 'help');
print '</td></tr>';

print '<tr class="liste_titre">';
print '<td colspan="3">' . $langs->trans("PublicInterface") . '</td>';
print "</tr>\n";

// Url public interface
$url_interface = $conf->global->TICKET_URL_PUBLIC_INTERFACE;
print '<tr><td>' . $langs->trans("TicketUrlPublicInterfaceLabelAdmin") . '</label>';
print '</td><td>';
print '<input type="text" name="TICKET_URL_PUBLIC_INTERFACE" value="' . $conf->global->TICKET_URL_PUBLIC_INTERFACE . '" size="40" ></td>';
print '</td>';
print '<td align="center">';
print $form->textwithpicto('', $langs->trans("TicketUrlPublicInterfaceHelpAdmin"), 1, 'help');
print '</td></tr>';

// Interface topic
$url_interface = $conf->global->TICKET_PUBLIC_INTERFACE_TOPIC;
print '<tr><td>' . $langs->trans("TicketPublicInterfaceTopicLabelAdmin") . '</label>';
print '</td><td>';
print '<input type="text"   name="TICKET_PUBLIC_INTERFACE_TOPIC" value="' . $conf->global->TICKET_PUBLIC_INTERFACE_TOPIC . '" size="40" ></td>';
print '</td>';
print '<td align="center">';
print $form->textwithpicto('', $langs->trans("TicketPublicInterfaceTopicHelp"), 1, 'help');
print '</td></tr>';

// Texte d'accueil homepage
$public_text_home = $conf->global->TICKET_PUBLIC_TEXT_HOME ? $conf->global->TICKET_PUBLIC_TEXT_HOME : $langs->trans('TicketPublicInterfaceTextHome');
print '<tr><td>' . $langs->trans("TicketPublicInterfaceTextHomeLabelAdmin") . '</label>';
print '</td><td>';
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
$doleditor = new DolEditor('TICKET_PUBLIC_TEXT_HOME', $public_text_home, '100%', 180, 'dolibarr_notes', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_2, 70);
$doleditor->Create();
print '</td>';
print '<td align="center">';
print $form->textwithpicto('', $langs->trans("TicketPublicInterfaceTextHomeHelpAdmin"), 1, 'help');
print '</td></tr>';

// Texte d'aide à la saisie du message
$public_text_help_message = $conf->global->TICKET_PUBLIC_TEXT_HELP_MESSAGE ? $conf->global->TICKET_PUBLIC_TEXT_HELP_MESSAGE : $langs->trans('TicketPublicPleaseBeAccuratelyDescribe');
print '<tr><td>' . $langs->trans("TicketPublicInterfaceTextHelpMessageLabelAdmin") . '</label>';
print '</td><td>';
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
$doleditor = new DolEditor('TICKET_PUBLIC_TEXT_HELP_MESSAGE', $public_text_help_message, '100%', 180, 'dolibarr_notes', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_2, 70);
$doleditor->Create();
print '</td>';
print '<td align="center">';
print $form->textwithpicto('', $langs->trans("TicketPublicInterfaceTextHelpMessageHelpAdmin"), 1, 'help');
print '</td></tr>';

print '<tr class="impair"><td colspan="3" align="center"><input type="submit" class="button" value="' . $langs->trans("Save") . '"></td>';
print '</tr>';

print '</table><br>';
print '</form>';

// End of page
llxFooter();
$db->close();
