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
 *     \file        admin/ticket.php
 *     \ingroup     ticket
 *     \brief       Page to setup module ticket
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

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

if ($action == 'updateMask') {
	$maskconstticket = GETPOST('maskconstticket', 'alpha');
	$maskticket = GETPOST('maskticket', 'alpha');

	if ($maskconstticket) {
		$res = dolibarr_set_const($db, $maskconstticket, $maskticket, 'chaine', 0, '', $conf->entity);
	}

	if (!($res > 0)) {
		$error++;
	}

	if (!$error)
	{
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

// Activate a model
elseif ($action == 'set') {
	$ret = addDocumentModel($value, $type, $label, $scandir);
} elseif ($action == 'del') {
	$ret = delDocumentModel($value, $type);
	if ($ret > 0)
	{
		if ($conf->global->TICKET_ADDON_PDF == "$value") dolibarr_del_const($db, 'TICKET_ADDON_PDF', $conf->entity);
	}
}

// Set default model
elseif ($action == 'setdoc') {
	if (dolibarr_set_const($db, "TICKET_ADDON_PDF", $value, 'chaine', 0, '', $conf->entity))
	{
		// The constant that was read before the new set
		// We therefore requires a variable to have a coherent view
		$conf->global->TICKET_ADDON_PDF = $value;
	}

	// On active le modele
	$ret = delDocumentModel($value, $type);
	if ($ret > 0)
	{
		$ret = addDocumentModel($value, $type, $label, $scandir);
	}
}

elseif ($action == 'setmod') {
	// TODO Verifier si module numerotation choisi peut etre active
	// par appel methode canBeActivated

	dolibarr_set_const($db, "TICKET_ADDON", $value, 'chaine', 0, '', $conf->entity);
} elseif ($action == 'setvar') {
	include_once DOL_DOCUMENT_ROOT."/core/lib/files.lib.php";

	$notification_email = GETPOST('TICKET_NOTIFICATION_EMAIL_FROM', 'alpha');
	if (!empty($notification_email)) {
		$res = dolibarr_set_const($db, 'TICKET_NOTIFICATION_EMAIL_FROM', $notification_email, 'chaine', 0, '', $conf->entity);
	} else {
		$res = dolibarr_set_const($db, 'TICKET_NOTIFICATION_EMAIL_FROM', '', 'chaine', 0, '', $conf->entity);
	}
	if (!($res > 0)) {
		$error++;
	}

	// altairis : differentiate notification email FROM and TO
	$notification_email_to = GETPOST('TICKET_NOTIFICATION_EMAIL_TO', 'alpha');
	if (!empty($notification_email_to)) {
		$res = dolibarr_set_const($db, 'TICKET_NOTIFICATION_EMAIL_TO', $notification_email_to, 'chaine', 0, '', $conf->entity);
	} else {
		$res = dolibarr_set_const($db, 'TICKET_NOTIFICATION_EMAIL_TO', '', 'chaine', 0, '', $conf->entity);
	}
	if (!($res > 0)) {
		$error++;
	}

	$mail_intro = GETPOST('TICKET_MESSAGE_MAIL_INTRO', 'restricthtml');
	if (!empty($mail_intro)) {
		$res = dolibarr_set_const($db, 'TICKET_MESSAGE_MAIL_INTRO', $mail_intro, 'chaine', 0, '', $conf->entity);
	} else {
		$res = dolibarr_set_const($db, 'TICKET_MESSAGE_MAIL_INTRO', $langs->trans('TicketMessageMailIntroText'), 'chaine', 0, '', $conf->entity);
	}
	if (!($res > 0)) {
		$error++;
	}

	$mail_signature = GETPOST('TICKET_MESSAGE_MAIL_SIGNATURE', 'restricthtml');
	if (!empty($mail_signature)) {
		$res = dolibarr_set_const($db, 'TICKET_MESSAGE_MAIL_SIGNATURE', $mail_signature, 'chaine', 0, '', $conf->entity);
	} else {
		$res = dolibarr_set_const($db, 'TICKET_MESSAGE_MAIL_SIGNATURE', $langs->trans('TicketMessageMailSignatureText'), 'chaine', 0, '', $conf->entity);
	}
	if (!($res > 0)) {
		$error++;
	}
}

if ($action == 'setvarother') {
	$param_must_exists = GETPOST('TICKET_EMAIL_MUST_EXISTS', 'alpha');
	$res = dolibarr_set_const($db, 'TICKET_EMAIL_MUST_EXISTS', $param_must_exists, 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}

	$param_disable_email = GETPOST('TICKET_DISABLE_NOTIFICATION_MAILS', 'alpha');
	$res = dolibarr_set_const($db, 'TICKET_DISABLE_NOTIFICATION_MAILS', $param_disable_email, 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}

	if ($conf->global->MAIN_FEATURES_LEVEL >= 2)
	{
		$param_show_module_logo = GETPOST('TICKET_SHOW_MODULE_LOGO', 'alpha');
		$res = dolibarr_set_const($db, 'TICKET_SHOW_MODULE_LOGO', $param_show_module_logo, 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$error++;
		}
	}

	if ($conf->global->MAIN_FEATURES_LEVEL >= 2)
	{
		$param_notification_also_main_addressemail = GETPOST('TICKET_NOTIFICATION_ALSO_MAIN_ADDRESS', 'alpha');
		$res = dolibarr_set_const($db, 'TICKET_NOTIFICATION_ALSO_MAIN_ADDRESS', $param_notification_also_main_addressemail, 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$error++;
		}
	}

	$param_limit_view = GETPOST('TICKET_LIMIT_VIEW_ASSIGNED_ONLY', 'alpha');
	$res = dolibarr_set_const($db, 'TICKET_LIMIT_VIEW_ASSIGNED_ONLY', $param_limit_view, 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}

	$param_auto_assign = GETPOST('TICKET_AUTO_ASSIGN_USER_CREATE', 'alpha');
	$res = dolibarr_set_const($db, 'TICKET_AUTO_ASSIGN_USER_CREATE', $param_auto_assign, 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) {
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
print '<td align="center" width="80">'.$langs->trans("ShortInfo").'</td>';
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

					$module = new $file;

					// Show modules according to features level
					if ($module->version == 'development' && $conf->global->MAIN_FEATURES_LEVEL < 2) {
						continue;
					}

					if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) {
						continue;
					}

					if ($module->isEnabled()) {
						print '<tr class="oddeven"><td>'.$module->name."</td><td>\n";
						print $module->info();
						print '</td>';

						// Show example of numbering model
						print '<td class="nowrap">';
						$tmp = $module->getExample();
						if (preg_match('/^Error/', $tmp)) {
							$langs->load("errors");
							print '<div class="error">'.$langs->trans($tmp).'</div>';
						} elseif ($tmp == 'NotConfigured') {
							print $langs->trans($tmp);
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
if ($resql)
{
	$i = 0;
	$num_rows = $db->num_rows($resql);
	while ($i < $num_rows)
	{
		$array = $db->fetch_array($resql);
		array_push($def, $array[0]);
		$i++;
	}
} else {
	dol_print_error($db);
}


print '<div class="div-table-responsive-no-min">';
print "<table class=\"noborder\" width=\"100%\">\n";
print "<tr class=\"liste_titre\">\n";
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td class="center" width="60">'.$langs->trans("Status")."</td>\n";
print '<td class="center" width="60">'.$langs->trans("Default")."</td>\n";
print '<td class="center" width="38">'.$langs->trans("ShortInfo").'</td>';
print '<td class="center" width="38">'.$langs->trans("Preview").'</td>';
print "</tr>\n";

clearstatcache();

foreach ($dirmodels as $reldir)
{
	foreach (array('', '/doc') as $valdir)
	{
		$realpath = $reldir."core/modules/ticket".$valdir;
		$dir = dol_buildpath($realpath);

		if (is_dir($dir))
		{
			$handle = opendir($dir);
			if (is_resource($handle))
			{
				while (($file = readdir($handle)) !== false)
				{
					$filelist[] = $file;
				}
				closedir($handle);
				arsort($filelist);

				foreach ($filelist as $file)
				{
					if (preg_match('/\.modules\.php$/i', $file) && preg_match('/^(pdf_|doc_)/', $file))
					{
						if (file_exists($dir.'/'.$file))
						{
							$name = substr($file, 4, dol_strlen($file) - 16);
							$classname = substr($file, 0, dol_strlen($file) - 12);

							require_once $dir.'/'.$file;
							$module = new $classname($db);

							$modulequalified = 1;
							if ($module->version == 'development' && $conf->global->MAIN_FEATURES_LEVEL < 2) $modulequalified = 0;
							if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) $modulequalified = 0;

							if ($modulequalified)
							{
								print '<tr class="oddeven"><td width="100">';
								print (empty($module->name) ? $name : $module->name);
								print "</td><td>\n";
								if (method_exists($module, 'info')) print $module->info($langs);
								else print $module->description;
								print '</td>';

								// Active
								if (in_array($name, $def))
								{
									print '<td class="center">'."\n";
									print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=del&value='.$name.'">';
									print img_picto($langs->trans("Enabled"), 'switch_on');
									print '</a>';
									print '</td>';
								} else {
									print '<td class="center">'."\n";
									print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=set&amp;token='.newToken().'&amp;value='.$name.'&amp;scan_dir='.$module->scandir.'&amp;label='.urlencode($module->name).'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
									print "</td>";
								}

								// Default
								print '<td class="center">';
								if ($conf->global->TICKET_ADDON_PDF == $name)
								{
									print img_picto($langs->trans("Default"), 'on');
								} else {
									print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setdoc&amp;token='.newToken().'&amp;value='.$name.'&amp;scan_dir='.$module->scandir.'&amp;label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
								}
								print '</td>';

								// Info
								$htmltooltip = ''.$langs->trans("Name").': '.$module->name;
								$htmltooltip .= '<br>'.$langs->trans("Type").': '.($module->type ? $module->type : $langs->trans("Unknown"));
								if ($module->type == 'pdf')
								{
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
								print $form->textwithpicto('', $htmltooltip, 1, 0);
								print '</td>';

								// Preview
								print '<td class="center">';
								if ($module->type == 'pdf')
								{
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.$name.'">'.img_object($langs->trans("Preview"), 'pdf').'</a>';
								} else {
									print img_object($langs->trans("PreviewNotAvailable"), 'generic');
								}
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


if (!$conf->use_javascript_ajax) {
	print '<form method="post" action="'.$_SERVER['PHP_SELF'].'" enctype="multipart/form-data" >';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="setvarother">';
}

print load_fiche_titre($langs->trans("Other"), '', '');
print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td></td>';
print '<td></td>';
print "</tr>\n";

// Auto assign ticket at user who created it
print '<tr class="oddeven"><td>'.$langs->trans("TicketsAutoAssignTicket").'</td>';
print '<td class="left">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('TICKET_AUTO_ASSIGN_USER_CREATE');
} else {
	$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
	print $form->selectarray("TICKET_AUTO_ASSIGN_USER_CREATE", $arrval, $conf->global->TICKET_AUTO_ASSIGN_USER_CREATE);
}
print '</td>';
print '<td class="center">';
print $form->textwithpicto('', $langs->trans("TicketsAutoAssignTicketHelp"), 1, 'help');
print '</td>';
print '</tr>';

print '</table><br>';

if (!$conf->use_javascript_ajax) {
	print '</form>';
}

// Admin var of module
print load_fiche_titre($langs->trans("Notification"), '', '');

print '<table class="noborder centpercent">';

print '<form method="post" action="'.$_SERVER['PHP_SELF'].'" enctype="multipart/form-data" >';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="setvar">';

print '<tr class="liste_titre">';
print '<td colspan="3">'.$langs->trans("Email").'</td>';
print "</tr>\n";

if (empty($conf->global->FCKEDITOR_ENABLE_MAIL)) {
	print '<tr>';
	print '<td colspan="3"><div class="info">'.$langs->trans("TicketCkEditorEmailNotActivated").'</div></td>';
	print "</tr>\n";
}

// @todo Use module notification instead...

// Email d'envoi des notifications
print '<tr class="oddeven"><td>'.$langs->trans("TicketEmailNotificationFrom").'</td>';
print '<td class="left">';
print '<input type="text" class="minwidth200" name="TICKET_NOTIFICATION_EMAIL_FROM" value="'.$conf->global->TICKET_NOTIFICATION_EMAIL_FROM.'"></td>';
print '<td class="center">';
print $form->textwithpicto('', $langs->trans("TicketEmailNotificationFromHelp"), 1, 'help');
print '</td>';
print '</tr>';

// Email for notification of TICKET_CREATE
print '<tr class="oddeven"><td>'.$langs->trans("TicketEmailNotificationTo").' ('.$langs->trans("Creation").')</td>';
print '<td class="left">';
print '<input type="text" name="TICKET_NOTIFICATION_EMAIL_TO" value="'.(!empty($conf->global->TICKET_NOTIFICATION_EMAIL_TO) ? $conf->global->TICKET_NOTIFICATION_EMAIL_TO : '').'"></td>';
print '<td class="center">';
print $form->textwithpicto('', $langs->trans("TicketEmailNotificationToHelp"), 1, 'help');
print '</td>';
print '</tr>';

// Also send to TICKET_NOTIFICATION_EMAIL_TO for responses (not only creation)
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

// Texte d'introduction
$mail_intro = $conf->global->TICKET_MESSAGE_MAIL_INTRO ? $conf->global->TICKET_MESSAGE_MAIL_INTRO : $langs->trans('TicketMessageMailIntroText');
print '<tr class="oddeven"><td>'.$langs->trans("TicketMessageMailIntroLabelAdmin").' ('.$langs->trans("Responses").')';
print '</td><td>';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
$doleditor = new DolEditor('TICKET_MESSAGE_MAIL_INTRO', $mail_intro, '100%', 120, 'dolibarr_mailings', '', false, true, $conf->global->FCKEDITOR_ENABLE_MAIL, ROWS_2, 70);
$doleditor->Create();
print '</td>';
print '<td class="center">';
print $form->textwithpicto('', $langs->trans("TicketMessageMailIntroHelpAdmin"), 1, 'help');
print '</td></tr>';

// Texte de signature
$mail_signature = $conf->global->TICKET_MESSAGE_MAIL_SIGNATURE ? $conf->global->TICKET_MESSAGE_MAIL_SIGNATURE : $langs->trans('TicketMessageMailSignatureText');
print '<tr class="oddeven"><td>'.$langs->trans("TicketMessageMailSignatureLabelAdmin").'</label>';
print '</td><td>';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
$doleditor = new DolEditor('TICKET_MESSAGE_MAIL_SIGNATURE', $mail_signature, '100%', 120, 'dolibarr_mailings', '', false, true, $conf->global->FCKEDITOR_ENABLE_MAIL, ROWS_2, 70);
$doleditor->Create();
print '</td>';
print '<td class="center">';
print $form->textwithpicto('', $langs->trans("TicketMessageMailSignatureHelpAdmin"), 1, 'help');
print '</td></tr>';

print '</table>';

print '<div class="center">';
print '<input type="submit" class="button button-save" value="'.$langs->trans("Save").'">';
print '</div>';

print '</form>';

// End of page
llxFooter();
$db->close();
