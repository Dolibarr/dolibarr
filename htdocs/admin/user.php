<?php
/* Copyright (C) 2003		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2003		Jean-Louis Bergamo		<jlb@j1b.org>
 * Copyright (C) 2004-2009	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Sebastien Di Cintio		<sdicintio@ressource-toi.org>
 * Copyright (C) 2004		Benoit Mortier			<benoit.mortier@opensides.be>
 * Copyright (C) 2005-2011	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2015		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2020-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *   	\file       htdocs/admin/user.php
 *		\ingroup    core
 *		\brief      Page to setup user module
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'members', 'users'));

$extrafields = new ExtraFields($db);

$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ09');	// Used by actions_setmoduleoptions.inc.php

$value = GETPOST('value', 'alpha');
$label = GETPOST('label', 'alpha');

$scandir = GETPOST('scandir', 'alpha');
$type = 'user';

if (empty($user->admin)) {
	accessforbidden();
}


/*
 * Action
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

$reg = array();

if ($action == 'set_default') {
	$ret = addDocumentModel($value, $type, $label, $scandir);
	$res = true;
} elseif ($action == 'del_default') {
	$ret = delDocumentModel($value, $type);
	if ($ret > 0) {
		if ($conf->global->USER_ADDON_PDF_ODT == "$value") {
			dolibarr_del_const($db, 'USER_ADDON_PDF_ODT', $conf->entity);
		}
	}
	$res = true;
} elseif ($action == 'setdoc') {
	// Set default model
	if (dolibarr_set_const($db, "USER_ADDON_PDF_ODT", $value, 'chaine', 0, '', $conf->entity)) {
		// La constante qui a ete lue en avant du nouveau set
		// on passe donc par une variable pour avoir un affichage coherent
		$conf->global->USER_ADDON_PDF_ODT = $value;
	}

	// On active le modele
	$ret = delDocumentModel($value, $type);
	if ($ret > 0) {
		$ret = addDocumentModel($value, $type, $label, $scandir);
	}
	$res = true;
} elseif ($action == 'unsetdoc') {
	// We disable the template
	dolibarr_del_const($db, "USER_ADDON_PDF_ODT", $conf->entity);
} elseif (preg_match('/set_([a-z0-9_\-]+)/i', $action, $reg)) {
	$code = $reg[1];
	if (dolibarr_set_const($db, $code, '1', 'chaine', 0, '', $conf->entity) > 0) {
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
} elseif ($action == 'sethideinactiveuser') {
	//Set hide closed customer into combox or select
	$status = GETPOST('status', 'alpha');

	if (dolibarr_set_const($db, "USER_HIDE_INACTIVE_IN_COMBOBOX", $status, 'chaine', 0, '', $conf->entity) > 0) {
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	} else {
		dol_print_error($db);
	}
}


/*
 * View
 */

$form = new Form($db);

dol_mkdir(DOL_DATA_ROOT.'/doctemplates/users');
dol_mkdir(DOL_DATA_ROOT.'/doctemplates/usergroups');

$help_url = 'EN:Module_Users|FR:Module_Utilisateurs|ES:M&oacute;dulo_Usuarios';
llxHeader('', $langs->trans("UsersSetup"), $help_url, '', 0, 0, '', '', '', 'mod-admin page-user');

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("UsersSetup"), $linkback, 'title_setup');


$head = user_admin_prepare_head();

print dol_get_fiche_head($head, 'card', $langs->trans("MenuUsersAndGroups"), -1, 'user');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";
print '</tr>';


// Mail required for users

print '<tr class="oddeven">';
print '<td>'.$langs->trans("UserMailRequired").'</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('USER_MAIL_REQUIRED');
} else {
	if (!getDolGlobalString('USER_MAIL_REQUIRED')) {
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_USER_MAIL_REQUIRED&token='.newToken().'">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
	} else {
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_USER_MAIL_REQUIRED&token='.newToken().'">'.img_picto($langs->trans("Enabled"), 'on').'</a>';
	}
}
print '</td></tr>';

// user hide inactive

print '<tr class="oddeven">';
print '<td>'.$langs->trans("UserHideInactive").'</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('USER_HIDE_INACTIVE_IN_COMBOBOX');
} else {
	if (!getDolGlobalString('USER_HIDE_INACTIVE_IN_COMBOBOX')) {
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_USER_HIDE_INACTIVE_IN_COMBOBOX&token='.newToken().'">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
	} else {
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_USER_HIDE_INACTIVE_IN_COMBOBOX&token='.newToken().'">'.img_picto($langs->trans("Enabled"), 'on').'</a>';
	}
}
print '</td></tr>';

print '</table>';
print '</div>';

print '<br>';

$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

// Defini tableau def des modeles
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

print load_fiche_titre($langs->trans("UsersDocModules"), '', '');

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
		$dir = dol_buildpath($reldir."core/modules/user".$valdir);
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
							'@phan-var-force CommonDocGenerator $module';

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
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=del_default&token='.newToken().'&value='.urlencode($name).'">';
									print img_picto($langs->trans("Enabled"), 'switch_on');
									print '</a>';
									print '</td>';
								} else {
									print '<td class="center">'."\n";
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=set_default&token='.newToken().'&value='.urlencode($name).'&scandir='.urlencode($module->scandir).'&label='.urlencode($module->name).'">';
									print img_picto($langs->trans("Disabled"), 'switch_off');
									print '</a>';
									print "</td>";
								}

								// Default
								print '<td class="center">';
								if (getDolGlobalString('USER_ADDON_PDF_ODT') == $name) {
									//print img_picto($langs->trans("Default"), 'on');
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=unsetdoc&token='.newToken().'&value='.urlencode($name).'&scandir='.urlencode($module->scandir).'&label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Default"), 'on').'</a>';
								} else {
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&token='.newToken().'&value='.urlencode($name).'&scandir='.urlencode($module->scandir).'&label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
								}
								print '</td>';

								// Info
								$htmltooltip = ''.$langs->trans("Name").': '.$module->name;
								$htmltooltip .= '<br>'.$langs->trans("Type").': '.($module->type ? $module->type : $langs->trans("Unknown"));
								if ($module->type == 'pdf') {
									$htmltooltip .= '<br>'.$langs->trans("Width").'/'.$langs->trans("Height").': '.$module->page_largeur.'/'.$module->page_hauteur;
								}
								$htmltooltip .= '<br><br><u>'.$langs->trans("FeaturesSupported").':</u>';
								$htmltooltip .= '<br>'.$langs->trans("Logo").': '.yn($module->option_logo, 1, 1);
								$htmltooltip .= '<br>'.$langs->trans("MultiLanguage").': '.yn($module->option_multilang, 1, 1);


								print '<td class="center">';
								print $form->textwithpicto('', $htmltooltip, 1, 0);
								print '</td>';

								// Preview
								print '<td class="center">';
								if ($module->type == 'pdf') {
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
print '</div>';

print '<br>';

print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
