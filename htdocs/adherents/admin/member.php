<?php
/* Copyright (C) 2003		Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2003		Jean-Louis Bergamo			<jlb@j1b.org>
 * Copyright (C) 2004-2012	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Sebastien Di Cintio			<sdicintio@ressource-toi.org>
 * Copyright (C) 2004		Benoit Mortier				<benoit.mortier@opensides.be>
 * Copyright (C) 2005-2012	Regis Houssin				<regis.houssin@inodbox.com>
 * Copyright (C) 2011-2012	Juanjo Menent				<jmenent@2byte.es>
 * Copyright (C) 2012		J. Fernando Lagrange		<fernando@demo-tic.org>
 * Copyright (C) 2015		Jean-François Ferry			<jfefe@aternatik.fr>
 * Copyright (C) 2020-2021	Frédéric France      		<frederic.france@netlogic.fr>
 * Copyright (C) 2023		Waël Almoman				<info@almoman.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		Alexandre Spangaro			<alexandre@inovea-conseil.com>
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
 *   	\file       htdocs/adherents/admin/member.php
 *		\ingroup    member
 *		\brief      Page to setup the module Foundation
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/member.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("admin", "members"));

if (!$user->admin) {
	accessforbidden();
}


$choices = array('yesno', 'texte', 'chaine');

$value = GETPOST('value', 'alpha');
$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scandir', 'alpha');
$type = 'member';

$action = GETPOST('action', 'aZ09');
$modulepart = GETPOST('modulepart', 'aZ09');


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

if ($action == 'set_default') {
	$ret = addDocumentModel($value, $type, $label, $scandir);
	$res = true;
} elseif ($action == 'del_default') {
	$ret = delDocumentModel($value, $type);
	if ($ret > 0) {
		if (getDolGlobalString('MEMBER_ADDON_PDF_ODT') == "$value") {
			dolibarr_del_const($db, 'MEMBER_ADDON_PDF_ODT', $conf->entity);
		}
	}
	$res = true;
} elseif ($action == 'setdoc') {
	// Set default model
	if (dolibarr_set_const($db, "MEMBER_ADDON_PDF_ODT", $value, 'chaine', 0, '', $conf->entity)) {
		// The constant that was read ahead of the new set
		// we therefore go through a variable to have a consistent display
		$conf->global->MEMBER_ADDON_PDF_ODT = $value;
	}

	// We activate the model
	$ret = delDocumentModel($value, $type);
	if ($ret > 0) {
		$ret = addDocumentModel($value, $type, $label, $scandir);
	}
	$res = true;
} elseif (preg_match('/set_([a-z0-9_\-]+)/i', $action, $reg)) {
	$code = $reg[1];
	if (dolibarr_set_const($db, $code, 1, 'chaine', 0, '', $conf->entity) > 0) {
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	} else {
		dol_print_error($db);
	}
} elseif (preg_match('/del_([a-z0-9_\-]+)/i', $action, $reg)) {
	$code = $reg[1];
	if (dolibarr_del_const($db, $code, $conf->entity) > 0) {
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	} else {
		dol_print_error($db);
	}
} elseif ($action == 'updatemainoptions') {
	$db->begin();
	$res1 = $res2 = $res3 = $res4 = $res5 = $res6 = $res7 = $res8 = $res9 = 0;
	$res1 = dolibarr_set_const($db, 'ADHERENT_LOGIN_NOT_REQUIRED', GETPOST('ADHERENT_LOGIN_NOT_REQUIRED', 'alpha') ? 0 : 1, 'chaine', 0, '', $conf->entity);
	$res2 = dolibarr_set_const($db, 'ADHERENT_MAIL_REQUIRED', GETPOST('ADHERENT_MAIL_REQUIRED', 'alpha'), 'chaine', 0, '', $conf->entity);
	$res3 = dolibarr_set_const($db, 'ADHERENT_DEFAULT_SENDINFOBYMAIL', GETPOST('ADHERENT_DEFAULT_SENDINFOBYMAIL', 'alpha'), 'chaine', 0, '', $conf->entity);
	$res3 = dolibarr_set_const($db, 'ADHERENT_CREATE_EXTERNAL_USER_LOGIN', GETPOST('ADHERENT_CREATE_EXTERNAL_USER_LOGIN', 'alpha'), 'chaine', 0, '', $conf->entity);
	$res4 = dolibarr_set_const($db, 'ADHERENT_BANK_USE', GETPOST('ADHERENT_BANK_USE', 'alpha'), 'chaine', 0, '', $conf->entity);
	$res7 = dolibarr_set_const($db, 'MEMBER_PUBLIC_ENABLED', GETPOST('MEMBER_PUBLIC_ENABLED', 'alpha'), 'chaine', 0, '', $conf->entity);
	$res8 = dolibarr_set_const($db, 'MEMBER_SUBSCRIPTION_START_FIRST_DAY_OF', GETPOST('MEMBER_SUBSCRIPTION_START_FIRST_DAY_OF', 'alpha'), 'chaine', 0, '', $conf->entity);
	$res9 = dolibarr_set_const($db, 'MEMBER_SUBSCRIPTION_START_AFTER', GETPOST('MEMBER_SUBSCRIPTION_START_AFTER', 'alpha'), 'chaine', 0, '', $conf->entity);
	// Use vat for invoice creation
	if (isModEnabled('invoice')) {
		$res4 = dolibarr_set_const($db, 'ADHERENT_VAT_FOR_SUBSCRIPTIONS', GETPOST('ADHERENT_VAT_FOR_SUBSCRIPTIONS', 'alpha'), 'chaine', 0, '', $conf->entity);
		$res5 = dolibarr_set_const($db, 'ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS', GETPOST('ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS', 'alpha'), 'chaine', 0, '', $conf->entity);
		if (isModEnabled("product") || isModEnabled("service")) {
			$res6 = dolibarr_set_const($db, 'ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS', GETPOST('ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS', 'alpha'), 'chaine', 0, '', $conf->entity);
		}
	}
	if ($res1 < 0 || $res2 < 0 || $res3 < 0 || $res4 < 0 || $res5 < 0 || $res6 < 0 || $res7 < 0) {
		setEventMessages('ErrorFailedToSaveData', null, 'errors');
		$db->rollback();
	} else {
		setEventMessages('RecordModifiedSuccessfully', null, 'mesgs');
		$db->commit();
	}
} elseif ($action == 'updatememberscards') {
	$db->begin();
	$res1 = $res2 = $res3 = $res4 = 0;
	$res1 = dolibarr_set_const($db, 'ADHERENT_CARD_TYPE', GETPOST('ADHERENT_CARD_TYPE'), 'chaine', 0, '', $conf->entity);
	$res2 = dolibarr_set_const($db, 'ADHERENT_CARD_HEADER_TEXT', GETPOST('ADHERENT_CARD_HEADER_TEXT', 'alpha'), 'chaine', 0, '', $conf->entity);
	$res3 = dolibarr_set_const($db, 'ADHERENT_CARD_TEXT', GETPOST('ADHERENT_CARD_TEXT', 'alpha'), 'chaine', 0, '', $conf->entity);
	$res3 = dolibarr_set_const($db, 'ADHERENT_CARD_TEXT_RIGHT', GETPOST('ADHERENT_CARD_TEXT_RIGHT', 'alpha'), 'chaine', 0, '', $conf->entity);
	$res4 = dolibarr_set_const($db, 'ADHERENT_CARD_FOOTER_TEXT', GETPOST('ADHERENT_CARD_FOOTER_TEXT', 'alpha'), 'chaine', 0, '', $conf->entity);

	if ($res1 < 0 || $res2 < 0 || $res3 < 0 || $res4 < 0) {
		setEventMessages('ErrorFailedToSaveDate', null, 'errors');
		$db->rollback();
	} else {
		setEventMessages('RecordModifiedSuccessfully', null, 'mesgs');
		$db->commit();
	}
} elseif ($action == 'updatememberstickets') {
	$db->begin();
	$res1 = $res2 = 0;
	$res1 = dolibarr_set_const($db, 'ADHERENT_ETIQUETTE_TYPE', GETPOST('ADHERENT_ETIQUETTE_TYPE'), 'chaine', 0, '', $conf->entity);
	$res2 = dolibarr_set_const($db, 'ADHERENT_ETIQUETTE_TEXT', GETPOST('ADHERENT_ETIQUETTE_TEXT', 'alpha'), 'chaine', 0, '', $conf->entity);

	if ($res1 < 0 || $res2 < 0) {
		setEventMessages('ErrorFailedToSaveDate', null, 'errors');
		$db->rollback();
	} else {
		setEventMessages('RecordModifiedSuccessfully', null, 'mesgs');
		$db->commit();
	}
} elseif ($action == 'setcodemember') {
	$result = dolibarr_set_const($db, "MEMBER_CODEMEMBER_ADDON", $value, 'chaine', 0, '', $conf->entity);
	if ($result <= 0) {
		dol_print_error($db);
	}
} elseif ($action == 'update' || $action == 'add') {
	// Action to update or add a constant
	$constname = GETPOST('constname', 'alpha');
	$constvalue = (GETPOST('constvalue_'.$constname) ? GETPOST('constvalue_'.$constname) : GETPOST('constvalue'));


	if (($constname == 'ADHERENT_CARD_TYPE' || $constname == 'ADHERENT_ETIQUETTE_TYPE' || $constname == 'ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS') && $constvalue == -1) {
		$constvalue = '';
	}
	if ($constname == 'ADHERENT_LOGIN_NOT_REQUIRED') { // Invert choice
		if ($constvalue) {
			$constvalue = 0;
		} else {
			$constvalue = 1;
		}
	}

	$consttype = GETPOST('consttype', 'alpha');
	$constnote = GETPOST('constnote');
	$res = dolibarr_set_const($db, $constname, $constvalue, $choices[$consttype], 0, $constnote, $conf->entity);

	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

// Action to enable of a submodule of the adherent module
if ($action == 'set') {
	$result = dolibarr_set_const($db, GETPOST('name', 'alpha'), GETPOST('value'), '', 0, '', $conf->entity);
	if ($result < 0) {
		print $db->error();
	}
}

// Action to disable a submodule of the adherent module
if ($action == 'unset') {
	$result = dolibarr_del_const($db, GETPOST('name', 'alpha'), $conf->entity);
	if ($result < 0) {
		print $db->error();
	}
}



/*
 * View
 */

$form = new Form($db);

$title = $langs->trans("MembersSetup");
$help_url = 'EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros|DE:Modul_Mitglieder';

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-member page-admin');


$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("MembersSetup"), $linkback, 'title_setup');


$head = member_admin_prepare_head();

print dol_get_fiche_head($head, 'general', $langs->trans("Members"), -1, 'user');

$dirModMember = array_merge(array('/core/modules/member/'), $conf->modules_parts['member']);
foreach ($conf->modules_parts['models'] as $mo) {
	//Add more models
	$dirModMember[] = $mo.'core/modules/member/';
}

// Module to manage customer/supplier code

print load_fiche_titre($langs->trans("MemberCodeChecker"), '', '');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">'."\n";
print '<tr class="liste_titre">'."\n";
print '  <td>'.$langs->trans("Name").'</td>';
print '  <td>'.$langs->trans("Description").'</td>';
print '  <td>'.$langs->trans("Example").'</td>';
print '  <td class="center" width="80">'.$langs->trans("Status").'</td>';
print '  <td class="center" width="60">'.$langs->trans("ShortInfo").'</td>';
print "</tr>\n";

$arrayofmodules = array();

foreach ($dirModMember as $dirroot) {
	$dir = dol_buildpath($dirroot, 0);

	$handle = @opendir($dir);
	if (is_resource($handle)) {
		// Loop on each module find in opened directory
		while (($file = readdir($handle)) !== false) {
			// module filename has to start with mod_member_
			if (substr($file, 0, 11) == 'mod_member_' && substr($file, -3) == 'php') {
				$file = substr($file, 0, dol_strlen($file) - 4);
				try {
					dol_include_once($dirroot.$file.'.php');
				} catch (Exception $e) {
					dol_syslog($e->getMessage(), LOG_ERR);
					continue;
				}
				$modCodeMember = new $file();
				// Show modules according to features level
				if ($modCodeMember->version == 'development' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 2) {
					continue;
				}
				if ($modCodeMember->version == 'experimental' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 1) {
					continue;
				}

				$arrayofmodules[$file] = $modCodeMember;
			}
		}
		closedir($handle);
	}
}

$arrayofmodules = dol_sort_array($arrayofmodules, 'position');
'@phan-var-force array<string,ModeleNumRefMembers> $arrayofmodules';

foreach ($arrayofmodules as $file => $modCodeMember) {
	print '<tr class="oddeven">'."\n";
	print '<td width="140">'.$modCodeMember->name.'</td>'."\n";
	print '<td>'.$modCodeMember->info($langs).'</td>'."\n";
	print '<td class="nowrap">'.$modCodeMember->getExample().'</td>'."\n";

	if (getDolGlobalString('MEMBER_CODEMEMBER_ADDON') == "$file") {
		print '<td class="center">'."\n";
		print img_picto($langs->trans("Activated"), 'switch_on');
		print "</td>\n";
	} else {
		$disabled = isModEnabled('multicompany') && ((is_object($mc) && !empty($mc->sharings['referent']) && $mc->sharings['referent'] != $conf->entity));
		print '<td class="center">';
		if (!$disabled) {
			print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setcodemember&token='.newToken().'&value='.urlencode($file).'">';
		}
		print img_picto($langs->trans("Disabled"), 'switch_off');
		if (!$disabled) {
			print '</a>';
		}
		print '</td>';
	}

	print '<td class="center">';
	$s = $modCodeMember->getToolTip($langs, null);
	print $form->textwithpicto('', $s, 1);
	print '</td>';

	print '</tr>';
}
print '</table>';
print '</div>';



print "<br>";



// Document templates for documents generated from member record

$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

// Defined model definition table
$def = array();
$sql = "SELECT nom as name";
$sql .= " FROM ".MAIN_DB_PREFIX."document_model";
$sql .= " WHERE type = '".$db->escape($type)."'";
$sql .= " AND entity = ".$conf->entity;
$resql = $db->query($sql);
if ($resql) {
	$i = 0;
	$num_rows = $db->num_rows($resql);
	while ($i < $num_rows) {
		$obj = $db->fetch_object($resql);
		array_push($def, $obj->name);
		$i++;
	}
} else {
	dol_print_error($db);
}


print load_fiche_titre($langs->trans("MembersDocModules"), '', '');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td align="center" width="60">'.$langs->trans("Status")."</td>\n";
print '<td align="center" width="60">'.$langs->trans("Default")."</td>\n";
print '<td align="center" width="80">'.$langs->trans("ShortInfo").'</td>';
print '<td align="center" width="80">'.$langs->trans("Preview").'</td>';
print "</tr>\n";

clearstatcache();

foreach ($dirmodels as $reldir) {
	foreach (array('', '/doc') as $valdir) {
		$dir = dol_buildpath($reldir."core/modules/member".$valdir);
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
					if (preg_match('/\.class\.php$/i', $file) && preg_match('/^(pdf_|doc_)/', $file)) {
						if (file_exists($dir.'/'.$file)) {
							$name = substr($file, 4, dol_strlen($file) - 14);
							$classname = substr($file, 0, dol_strlen($file) - 10);

							require_once $dir.'/'.$file;
							$module = new $classname($db);
							'@phan-var-force doc_generic_member_odt|pdf_standard_member $module';

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
									print $module->info($langs);
								} else {
									print $module->description;
								}
								print '</td>';

								// Active
								if (in_array($name, $def)) {
									print '<td class="center">'."\n";
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=del_default&token='.newToken().'&value='.$name.'">';
									print img_picto($langs->trans("Enabled"), 'switch_on');
									print '</a>';
									print '</td>';
								} else {
									print '<td class="center">'."\n";
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=set_default&token='.newToken().'&value='.$name.'&scandir='.(!empty($module->scandir) ? $module->scandir : '').'&label='.urlencode($module->name).'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
									print "</td>";
								}

								// Default
								print '<td class="center">';
								if (getDolGlobalString('MEMBER_ADDON_PDF_ODT') == $name) {
									print img_picto($langs->trans("Default"), 'on');
								} else {
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&token='.newToken().'&value='.$name.'&scandir='.(!empty($module->scandir) ? $module->scandir : '').'&label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
								}
								print '</td>';

								// Info
								$htmltooltip = ''.$langs->trans("Name").': '.$module->name;
								$htmltooltip .= '<br>'.$langs->trans("Type").': '.($module->type ? $module->type : $langs->trans("Unknown"));
								if ($module->type == 'pdf') {
									$htmltooltip .= '<br>'.$langs->trans("Width").'/'.$langs->trans("Height").': '.$module->page_largeur.'/'.$module->page_hauteur;
								}
								$htmltooltip .= '<br><br><u>'.$langs->trans("FeaturesSupported").':</u>';
								$htmltooltip .= '<br>'.$langs->trans("Logo").': '.yn(!empty($module->option_logo) ? $module->option_logo : 0, 1, 1);
								$htmltooltip .= '<br>'.$langs->trans("MultiLanguage").': '.yn(!empty($module->option_multilang) ? $module->option_multilang : 0, 1, 1);


								print '<td class="center">';
								print $form->textwithpicto('', $htmltooltip, 1, 0);
								print '</td>';

								// Preview
								print '<td class="center">';
								if ($module->type == 'pdf') {
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.$name.'">'.img_object($langs->trans("Preview"), 'contract').'</a>';
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
print '</div>';


print '<br>';


print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="updatemainoptions">';
print '<input type="hidden" name="page_y" value="">';


// Main options

print load_fiche_titre($langs->trans("MemberMainOptions"), '', '');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Description").'</td>';
print '<td class="soixantepercent">'.$langs->trans("Value").'</td>';
print "</tr>\n";

// Delay to start the new membership ([+/-][0-99][Y/m/d], for instance, with "+4m", the subscription will start in 4 month.)
print '<tr class="oddeven drag" id="startfirstdayof"><td>';
print $form->textwithpicto($langs->trans("MemberSubscriptionStartAfter"), $langs->trans("MemberSubscriptionStartAfterDesc").'<br>'.$langs->trans("MemberSubscriptionStartAfterDesc2"));
print '</td><td>';
print '<input type="text" class="right width50" id="MEMBER_SUBSCRIPTION_START_AFTER" name="MEMBER_SUBSCRIPTION_START_AFTER" value="'.getDolGlobalString('MEMBER_SUBSCRIPTION_START_AFTER').'">';
print "</td></tr>\n";

// Start date of new membership
$startpoint = array();
$startpoint[0] = $langs->trans("NoCorrection");
$startpoint["m"] = $langs->trans("Month");
$startpoint["Y"] = $langs->trans("Year");
print '<tr class="oddeven drag" id="startfirstdayof"><td>';
print $langs->trans("MemberSubscriptionStartFirstDayOf");
print '</td><td>';
$startfirstdayof = !getDolGlobalString('MEMBER_SUBSCRIPTION_START_FIRST_DAY_OF') ? 0 : getDolGlobalString('MEMBER_SUBSCRIPTION_START_FIRST_DAY_OF');
print $form->selectarray("MEMBER_SUBSCRIPTION_START_FIRST_DAY_OF", $startpoint, $startfirstdayof, 0);
print "</td></tr>\n";

// Mail required for members
print '<tr class="oddeven"><td>'.$langs->trans("AdherentMailRequired").'</td><td>';
print $form->selectyesno('ADHERENT_MAIL_REQUIRED', getDolGlobalInt('ADHERENT_MAIL_REQUIRED'), 1, false, 0, 1);
print "</td></tr>\n";

// Login/Pass required for members
print '<tr class="oddeven"><td>';
print $form->textwithpicto($langs->trans("AdherentLoginRequired"), $langs->trans("AdherentLoginRequiredDesc"));
print '</td><td>';
print $form->selectyesno('ADHERENT_LOGIN_NOT_REQUIRED', (getDolGlobalString('ADHERENT_LOGIN_NOT_REQUIRED') ? 0 : 1), 1, false, 0, 1);
print "</td></tr>\n";

// Create an external user login after an online payment of a membership subscription
// TODO Move this into the validate() method of the member.
print '<tr class="oddeven"><td>'.$langs->trans("MemberCreateAnExternalUserForSubscriptionValidated").'</td><td>';
print $form->selectyesno('ADHERENT_CREATE_EXTERNAL_USER_LOGIN', getDolGlobalInt('ADHERENT_CREATE_EXTERNAL_USER_LOGIN'), 1, false, 0, 1);
print "</td></tr>\n";

// Send mail information is on by default
print '<tr class="oddeven"><td>'.$langs->trans("MemberSendInformationByMailByDefault").'</td><td>';
print $form->selectyesno('ADHERENT_DEFAULT_SENDINFOBYMAIL', getDolGlobalInt('ADHERENT_DEFAULT_SENDINFOBYMAIL'), 1, false, 0, 1);
print "</td></tr>\n";

// Publish member information on public annuary
$linkofpubliclist = DOL_MAIN_URL_ROOT.'/public/members/public_list.php'.((isModEnabled('multicompany')) ? '?entity='.((int) $conf->entity) : '');
print '<tr class="oddeven"><td>'.$langs->trans("Public", getDolGlobalString('MAIN_INFO_SOCIETE_NOM'), $linkofpubliclist).'</td><td>';
print $form->selectyesno('MEMBER_PUBLIC_ENABLED', getDolGlobalInt('MEMBER_PUBLIC_ENABLED'), 1, false, 0, 1);
print "</td></tr>\n";

// Allow members to change type on renewal forms
/* To test during next beta
print '<tr class="oddeven"><td>'.$langs->trans("MemberAllowchangeOfType").'</td><td>';
print $form->selectyesno('MEMBER_ALLOW_CHANGE_OF_TYPE', (getDolGlobalInt('MEMBER_ALLOW_CHANGE_OF_TYPE') ? 0 : 1), 1);
print "</td></tr>\n";
*/

// Insert subscription into bank account
print '<tr class="oddeven"><td>'.$langs->trans("MoreActionsOnSubscription").'</td>';
$arraychoices = array('0' => $langs->trans("None"));
if (isModEnabled("bank")) {
	$arraychoices['bankdirect'] = $langs->trans("MoreActionBankDirect");
}
if (isModEnabled("bank") && isModEnabled("societe") && isModEnabled('invoice')) {
	$arraychoices['invoiceonly'] = $langs->trans("MoreActionInvoiceOnly");
}
if (isModEnabled("bank") && isModEnabled("societe") && isModEnabled('invoice')) {
	$arraychoices['bankviainvoice'] = $langs->trans("MoreActionBankViaInvoice");
}
print '<td>';
print $form->selectarray('ADHERENT_BANK_USE', $arraychoices, getDolGlobalString('ADHERENT_BANK_USE'), 0);
if (getDolGlobalString('ADHERENT_BANK_USE') == 'bankdirect' || getDolGlobalString('ADHERENT_BANK_USE') == 'bankviainvoice') {
	print '<br><div style="padding-top: 5px;"><span class="opacitymedium">'.$langs->trans("ABankAccountMustBeDefinedOnPaymentModeSetup").'</span></div>';
}
print '</td>';
print "</tr>\n";

// Use vat for invoice creation
if (isModEnabled('invoice')) {
	print '<tr class="oddeven"><td>'.$langs->trans("VATToUseForSubscriptions").'</td>';
	if (isModEnabled("bank")) {
		print '<td>';
		print $form->selectarray('ADHERENT_VAT_FOR_SUBSCRIPTIONS', array('0' => $langs->trans("NoVatOnSubscription"), 'defaultforfoundationcountry' => $langs->trans("Default")), getDolGlobalString('ADHERENT_VAT_FOR_SUBSCRIPTIONS', '0'), 0);
		print '</td>';
	} else {
		print '<td class="right">';
		print $langs->trans("WarningModuleNotActive", $langs->transnoentities("Module85Name"));
		print '</td>';
	}
	print "</tr>\n";

	if (isModEnabled("product") || isModEnabled("service")) {
		print '<tr class="oddeven"><td>'.$langs->trans("ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS").'</td>';
		print '<td>';
		$selected = getDolGlobalString('ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS');
		print img_picto('', 'product', 'class="pictofixedwidth"');
		$form->select_produits($selected, 'ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS', '', 0, 0, 1, 2, '', 0, [], 0, 1, 0, 'minwidth100 maxwidth500 widthcentpercentminusx');
		print '</td>';
	}
	print "</tr>\n";
}

print '</table>';
print '</div>';

print '<div class="center">';
print '<input type="submit" class="button reposition" value="'.$langs->trans("Update").'" name="Button">';
print '</div>';

print '</form>';


print '<br>';



// Generation of cards for members

print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="updatememberscards">';
print '<input type="hidden" name="page_y" value="">';

print load_fiche_titre($langs->trans("MembersCards"), '', '');

$helptext = '*'.$langs->trans("FollowingConstantsWillBeSubstituted").'<br>';
$helptext .= '__DOL_MAIN_URL_ROOT__, __ID__, __FIRSTNAME__, __LASTNAME__, __FULLNAME__, __LOGIN__, __PASSWORD__, ';
$helptext .= '__COMPANY__, __ADDRESS__, __ZIP__, __TOWN__, __COUNTRY__, __EMAIL__, __BIRTH__, __PHOTO__, __TYPE__, ';
$helptext .= '__YEAR__, __MONTH__, __DAY__';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Description").'</td>';
print '<td class="soixantepercent">'.$form->textwithpicto($langs->trans("Value"), $helptext, 1, 'help', '', 0, 2, 'idhelptext').'</td>';
print "</tr>\n";

// Format of cards page
print '<tr class="oddeven"><td>'.$langs->trans("DescADHERENT_CARD_TYPE").'</td><td>';

require_once DOL_DOCUMENT_ROOT.'/core/lib/format_cards.lib.php'; // List of possible labels (defined into $_Avery_Labels variable set into format_cards.lib.php)
$arrayoflabels = array();
foreach (array_keys($_Avery_Labels) as $codecards) {
	$arrayoflabels[$codecards] = $_Avery_Labels[$codecards]['name'];
}
print $form->selectarray('ADHERENT_CARD_TYPE', $arrayoflabels, getDolGlobalString('ADHERENT_CARD_TYPE') ? getDolGlobalString('ADHERENT_CARD_TYPE') : 'CARD', 1, 0, 0);

print "</td></tr>\n";

// Text printed on top of member cards
print '<tr class="oddeven"><td>'.$langs->trans("DescADHERENT_CARD_HEADER_TEXT").'</td><td>';
print '<input type="text" class="flat minwidth300" name="ADHERENT_CARD_HEADER_TEXT" value="'.dol_escape_htmltag(getDolGlobalString('ADHERENT_CARD_HEADER_TEXT')).'">';
print "</td></tr>\n";

// Text printed on member cards (align on left)
print '<tr class="oddeven"><td>'.$langs->trans("DescADHERENT_CARD_TEXT").'</td><td>';
print '<textarea class="flat" name="ADHERENT_CARD_TEXT" cols="50" rows="5" wrap="soft">'."\n";
print getDolGlobalString('ADHERENT_CARD_TEXT');
print '</textarea>';
print "</td></tr>\n";

// Text printed on member cards (align on right)
print '<tr class="oddeven"><td>'.$langs->trans("DescADHERENT_CARD_TEXT_RIGHT").'</td><td>';
print '<textarea class="flat" name="ADHERENT_CARD_TEXT_RIGHT" cols="50" rows="5" wrap="soft">'."\n";
print getDolGlobalString('ADHERENT_CARD_TEXT_RIGHT');
print '</textarea>';
print "</td></tr>\n";

// Text printed on bottom of member cards
print '<tr class="oddeven"><td>'.$langs->trans("DescADHERENT_CARD_FOOTER_TEXT").'</td><td>';
print '<input type="text" class="flat minwidth300" name="ADHERENT_CARD_FOOTER_TEXT" value="'.dol_escape_htmltag(getDolGlobalString('ADHERENT_CARD_FOOTER_TEXT')).'">';
print "</td></tr>\n";

print '</table>';
print '</div>';

print '<div class="center">';
print '<input type="submit" class="button reposition" value="'.$langs->trans("Update").'" name="Button">';
print '</div>';

print '</form>';

print '<br>';


// Membership address sheet

print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="updatememberstickets">';
print '<input type="hidden" name="page_y" value="">';

print load_fiche_titre($langs->trans("MembersTickets"), '', '');

$helptext = '*'.$langs->trans("FollowingConstantsWillBeSubstituted").'<br>';
$helptext .= '__DOL_MAIN_URL_ROOT__, __ID__, __FIRSTNAME__, __LASTNAME__, __FULLNAME__, __LOGIN__, __PASSWORD__, ';
$helptext .= '__COMPANY__, __ADDRESS__, __ZIP__, __TOWN__, __COUNTRY__, __EMAIL__, __BIRTH__, __PHOTO__, __TYPE__, ';
$helptext .= '__YEAR__, __MONTH__, __DAY__';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Description").'</td>';
print '<td class="soixantepercent">'.$form->textwithpicto($langs->trans("Value"), $helptext, 1, 'help', '', 0, 2, 'idhelptext').'</td>';
print "</tr>\n";

// Format of labels page
print '<tr class="oddeven"><td>'.$langs->trans("DescADHERENT_ETIQUETTE_TYPE").'</td><td>';

require_once DOL_DOCUMENT_ROOT.'/core/lib/format_cards.lib.php'; // List of possible labels (defined into $_Avery_Labels variable set into format_cards.lib.php)
$arrayoflabels = array();
foreach (array_keys($_Avery_Labels) as $codecards) {
	$arrayoflabels[$codecards] = $_Avery_Labels[$codecards]['name'];
}
print $form->selectarray('ADHERENT_ETIQUETTE_TYPE', $arrayoflabels, getDolGlobalString('ADHERENT_ETIQUETTE_TYPE') ? getDolGlobalString('ADHERENT_ETIQUETTE_TYPE') : 'CARD', 1, 0, 0);

print "</td></tr>\n";

// Text printed on member address sheets
print '<tr class="oddeven"><td>'.$langs->trans("DescADHERENT_ETIQUETTE_TEXT").'</td><td>';
print '<textarea class="flat" name="ADHERENT_ETIQUETTE_TEXT" cols="50" rows="5" wrap="soft">'."\n";
print getDolGlobalString('ADHERENT_ETIQUETTE_TEXT');
print '</textarea>';
print "</td></tr>\n";

print '</table>';
print '</div>';

print '<div class="center">';
print '<input type="submit" class="button reposition" value="'.$langs->trans("Update").'" name="Button">';
print '</div>';

print '</form>';

print '<br>';

print "<br>";

print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
