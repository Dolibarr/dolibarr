<?php
/* Copyright (C) 2008-2016	Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2011		Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2011-2017  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2015		Jean-François Ferry	    <jfefe@aternatik.fr>
 * Copyright (C) 2016		Charlie Benke		    <charlie@patas-monkey.com>
 * Copyright (C) 2017       Open-DSI                <support@open-dsi.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 *	    \file       htdocs/admin/agenda_other.php
 *      \ingroup    agenda
 *      \brief      Autocreate actions for agenda module setup page
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/defaultvalues.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';

if (!$user->admin) {
	accessforbidden();
}

// Load translation files required by the page
$langs->loadLangs(array('admin', 'other', 'agenda', 'users'));

$action = GETPOST('action', 'aZ09');
$value = GETPOST('value', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ09');	// Used by actions_setmoduleoptions.inc.php

$param = GETPOST('param', 'alpha');
$cancel = GETPOST('cancel', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');
$type = 'action';


/*
 *	Actions
 */

$error = 0;
$errors = array();

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

$reg = array();
if (preg_match('/set_([a-z0-9_\-]+)/i', $action, $reg)) {
	$code = $reg[1];
	$value = (GETPOST($code, 'alpha') ? GETPOST($code, 'alpha') : 1);
	if (dolibarr_set_const($db, $code, $value, 'chaine', 0, '', $conf->entity) > 0) {
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	} else {
		dol_print_error($db);
	}
}

if (preg_match('/del_([a-z0-9_\-]+)/i', $action, $reg)) {
	$code = $reg[1];
	if (dolibarr_del_const($db, $code, $conf->entity) > 0) {
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	} else {
		dol_print_error($db);
	}
}
if ($action == 'set') {
	$getDefaultFilter = GETPOST('AGENDA_DEFAULT_FILTER_TYPE');
	$defaultfilter = (is_array($getDefaultFilter)) ? implode(',', $getDefaultFilter) : $getDefaultFilter;
	dolibarr_set_const($db, 'AGENDA_USE_EVENT_TYPE_DEFAULT', GETPOST('AGENDA_USE_EVENT_TYPE_DEFAULT'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'AGENDA_DEFAULT_FILTER_TYPE', $defaultfilter, 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'AGENDA_DEFAULT_FILTER_STATUS', GETPOST('AGENDA_DEFAULT_FILTER_STATUS'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'AGENDA_DEFAULT_VIEW', GETPOST('AGENDA_DEFAULT_VIEW'), 'chaine', 0, '', $conf->entity);

	$defaultValues = new DefaultValues($db);
	$result = $defaultValues->fetchAll('', '', 0, 0, array('t.page' => 'comm/action/card.php', 't.param' => 'complete', 't.user_id' => '0', 't.type' => 'createform', 't.entity' => $conf->entity));
	if (!is_array($result) && $result < 0) {
		setEventMessages($defaultValues->error, $defaultValues->errors, 'errors');
	} elseif (count($result) > 0) {
		foreach ($result as $defval) {
			$defaultValues->id = $defval->id;
			$resultDel = $defaultValues->delete($user);
			if ($resultDel < 0) {
				setEventMessages($defaultValues->error, $defaultValues->errors, 'errors');
			}
		}
	}
	$defaultValues->type = 'createform';
	$defaultValues->entity = $conf->entity;
	$defaultValues->user_id = 0;
	$defaultValues->page = 'comm/action/card.php';
	$defaultValues->param = 'complete';
	$defaultValues->value = GETPOST('AGENDA_EVENT_DEFAULT_STATUS');
	$resultCreat = $defaultValues->create($user);
	if ($resultCreat < 0) {
		setEventMessages($defaultValues->error, $defaultValues->errors, 'errors');
	} else {
		setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
	}
} elseif ($action == 'specimen') {  // For actioncomm
	$modele = GETPOST('module', 'alpha');

	$action = new ActionComm($db);
	$action->initAsSpecimen();
	$specimenthirdparty = new Societe($db);
	$specimenthirdparty->initAsSpecimen();
	$action->thirdparty = $specimenthirdparty;

	// Search template files
	$file = '';
	$classname = '';
	$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
	foreach ($dirmodels as $reldir) {
		$file = dol_buildpath($reldir."core/modules/action/doc/pdf_".$modele.".modules.php", 0);
		if (file_exists($file)) {
			$classname = "pdf_".$modele;
			break;
		}
	}

	if ($classname !== '') {
		require_once $file;

		$module = new $classname($db, $action);
		'@phan-var-force pdf_standard_actions $module';

		if ($module->write_file($action, $langs) > 0) {
			header("Location: ".DOL_URL_ROOT."/document.php?modulepart=action&file=SPECIMEN.pdf");
			return;
		} else {
			setEventMessages($module->error, $module->errors, 'errors');
			dol_syslog($module->error, LOG_ERR);
		}
	} else {
		setEventMessages($langs->trans("ErrorModuleNotFound"), null, 'errors');
		dol_syslog($langs->trans("ErrorModuleNotFound"), LOG_ERR);
	}
} elseif ($action == 'setmodel') {
	// Activate a model
	//print "sssd".$value;
	$ret = addDocumentModel($value, $type, $label, $scandir);
} elseif ($action == 'del') {
	$ret = delDocumentModel($value, $type);
	if ($ret > 0) {
		if ($conf->global->ACTION_EVENT_ADDON_PDF == "$value") {
			dolibarr_del_const($db, 'ACTION_EVENT_ADDON_PDF', $conf->entity);
		}
	}
} elseif ($action == 'setdoc') {
	// Set default model
	if (dolibarr_set_const($db, "ACTION_EVENT_ADDON_PDF", $value, 'chaine', 0, '', $conf->entity)) {
		// La constante qui a ete lue en avant du nouveau set
		// on passe donc par une variable pour avoir un affichage coherent
		$conf->global->ACTION_EVENT_ADDON_PDF = $value;
	}

	// On active le modele
	$ret = delDocumentModel($value, $type);
	if ($ret > 0) {
		$ret = addDocumentModel($value, $type, $label, $scandir);
	}
}


/**
 * View
 */

$form = new Form($db);
$formactions = new FormActions($db);

$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

$wikihelp = 'EN:Module_Agenda_En|FR:Module_Agenda|ES:Módulo_Agenda|DE:Modul_Terminplanung';
llxHeader('', $langs->trans("AgendaSetup"), $wikihelp, '', 0, 0, '', '', '', 'mod-admin page-agenda_other');

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("AgendaSetup"), $linkback, 'title_setup');



$head = agenda_prepare_head();

print dol_get_fiche_head($head, 'other', $langs->trans("Agenda"), -1, 'action');


/*
 *  Miscellaneous
 */

// Define an array def of models
$def = array();

$sql = "SELECT nom";
$sql .= " FROM ".MAIN_DB_PREFIX."document_model";
$sql .= " WHERE type = 'action'";
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

if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {
	print load_fiche_titre($langs->trans("AgendaModelModule"), '', '');

	print '<table class="noborder centpercent">'."\n";
	print '<tr class="liste_titre">'."\n";
	print '<td width="100">'.$langs->trans("Name").'</td>'."\n";
	print '<td>'.$langs->trans("Description").'</td>'."\n";
	print '<td class="center" width="60">'.$langs->trans("Status").'</td>'."\n";
	print '<td class="center" width="60">'.$langs->trans("Default").'</td>'."\n";
	print '<td class="center" width="40">'.$langs->trans("ShortInfo").'</td>';
	print '<td class="center" width="40">'.$langs->trans("Preview").'</td>';
	print '</tr>'."\n";

	clearstatcache();

	$specimenthirdparty = new Societe($db);
	$specimenthirdparty->initAsSpecimen();

	foreach ($dirmodels as $reldir) {
		$dir = dol_buildpath($reldir."core/modules/action/doc");

		if (is_dir($dir)) {
			$handle = opendir($dir);
			if (is_resource($handle)) {
				while (($file = readdir($handle)) !== false) {
					if (preg_match('/\.modules\.php$/i', $file) && preg_match('/^(pdf_|doc_)/', $file)) {
						$name = substr($file, 4, dol_strlen($file) - 16);
						$classname = substr($file, 0, dol_strlen($file) - 12);
						require_once $dir.'/'.$file;
						$module = new $classname($db, new ActionComm($db));

						'@phan-var-force ModeleAction $module';

						print '<tr class="oddeven">'."\n";
						print "<td>";
						print(empty($module->name) ? $name : $module->name);
						print "</td>\n";
						print "<td>\n";
						require_once $dir.'/'.$file;
						$module = new $classname($db, $specimenthirdparty);
						'@phan-var-force ModeleAction $module';
						if (method_exists($module, 'info')) {
							print $module->info($langs);  // @phan-suppress-current-line PhanUndeclaredMethod
						} else {
							print $module->description;
						}
						print "</td>\n";

						// Active
						if (in_array($name, $def)) {
							print '<td class="center">'."\n";
							if ($conf->global->ACTION_EVENT_ADDON_PDF != "$name") {
								print '<a href="'.$_SERVER["PHP_SELF"].'?action=del&token='.newToken().'&value='.$name.'&scan_dir='.$module->scandir.'&label='.urlencode($module->name).'&type=action">';
								print img_picto($langs->trans("Enabled"), 'switch_on');
								print '</a>';
							} else {
								print img_picto($langs->trans("Enabled"), 'switch_on');
							}
							print "</td>";
						} else {
							print '<td class="center">'."\n";
							print '<a href="'.$_SERVER["PHP_SELF"].'?action=setmodel&token='.newToken().'&value='.$name.'&scan_dir='.$module->scandir.'&label='.urlencode($module->name).'&type=action">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
							print "</td>";
						}

						// Default
						print '<td class="center">';
						if ($conf->global->ACTION_EVENT_ADDON_PDF == "$name") {
							print img_picto($langs->trans("Default"), 'on');
						} else {
							print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&token='.newToken().'&value='.urlencode($name).'&amp;scan_dir='.urlencode($module->scandir).'&label='.urlencode($module->name).'&amp;type=action"" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
						}
						print '</td>';

						// Info
						$htmltooltip = ''.$langs->trans("Name").': '.$module->name;
						$htmltooltip .= '<br>'.$langs->trans("Type").': '.($module->type ? $module->type : $langs->trans("Unknown"));
						$htmltooltip .= '<br>'.$langs->trans("Width").'/'.$langs->trans("Height").': '.$module->page_largeur.'/'.$module->page_hauteur;
						$htmltooltip .= '<br><br><u>'.$langs->trans("FeaturesSupported").':</u>';
						$htmltooltip .= '<br>'.$langs->trans("Logo").': '.yn($module->option_logo, 1, 1);
						print '<td class="center">';
						print $form->textwithpicto('', $htmltooltip, 1, 0);
						print '</td>';
						print '<td class="center">';
						print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&amp;module='.$name.'">'.img_object($langs->trans("Preview"), 'pdf').'</a>';
						print '</td>';

						print "</tr>\n";
					}
				}
				closedir($handle);
			}
		}
	}
	print '</table><br>';

	print load_fiche_titre($langs->trans('MiscellaneousOptions'), '', '');
}


print '<form action="'.$_SERVER["PHP_SELF"].'" name="agenda">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="set">';

print '<table class="noborder allwidth">'."\n";
print '<tr class="liste_titre">'."\n";
print '<td>'.$langs->trans("Parameters").'</td>'."\n";
print '<td class="center">&nbsp;</td>'."\n";
print '<td class="right">'.$langs->trans("Value").'</td>'."\n";
print '</tr>'."\n";

// AGENDA_DEFAULT_VIEW
print '<tr class="oddeven">'."\n";
$htmltext = $langs->trans("ThisValueCanOverwrittenOnUserLevel", $langs->transnoentitiesnoconv("UserGUISetup"));
print '<td>'.$form->textwithpicto($langs->trans("AGENDA_DEFAULT_VIEW"), $htmltext).'</td>'."\n";
print '<td class="center">&nbsp;</td>'."\n";
print '<td class="right">'."\n";
$tmplist = array('' => '&nbsp;', 'show_list' => $langs->trans("ViewList"), 'show_month' => $langs->trans("ViewCal"), 'show_week' => $langs->trans("ViewWeek"), 'show_day' => $langs->trans("ViewDay"), 'show_peruser' => $langs->trans("ViewPerUser"));
print $form->selectarray('AGENDA_DEFAULT_VIEW', $tmplist, getDolGlobalString('AGENDA_DEFAULT_VIEW'));
print '</td></tr>'."\n";

// Manual or automatic

print '<tr class="oddeven">'."\n";
print '<td>'.$langs->trans("AGENDA_USE_EVENT_TYPE").'</td>'."\n";
print '<td class="center">&nbsp;</td>'."\n";
print '<td class="right">'."\n";
//print ajax_constantonoff('AGENDA_USE_EVENT_TYPE');	Do not use ajax here, we need to reload page to change other combo list
if (!getDolGlobalString('AGENDA_USE_EVENT_TYPE')) {
	print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_AGENDA_USE_EVENT_TYPE&token='.newToken().'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
} else {
	print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_AGENDA_USE_EVENT_TYPE&token='.newToken().'">'.img_picto($langs->trans("Enabled"), 'switch_on').'</a>';
}
print '</td></tr>'."\n";

if (getDolGlobalString('AGENDA_USE_EVENT_TYPE')) {
	print '<!-- AGENDA_USE_EVENT_TYPE_DEFAULT -->';
	print '<tr class="oddeven">'."\n";
	print '<td>'.$langs->trans("AGENDA_USE_EVENT_TYPE_DEFAULT").'</td>'."\n";
	print '<td class="center">&nbsp;</td>'."\n";
	print '<td class="right nowrap">'."\n";
	$formactions->select_type_actions(getDolGlobalString('AGENDA_USE_EVENT_TYPE_DEFAULT'), "AGENDA_USE_EVENT_TYPE_DEFAULT", 'systemauto', 0, 1);
	print '</td></tr>'."\n";
}

// AGENDA_EVENT_DEFAULT_STATUS
print '<tr class="oddeven">'."\n";
print '<td>'.$langs->trans("AGENDA_EVENT_DEFAULT_STATUS").'</td>'."\n";
print '<td class="center">&nbsp;</td>'."\n";
print '<td class="right nowrap">'."\n";
$defval = 'na';
$defaultValues = new DefaultValues($db);
$result = $defaultValues->fetchAll('', '', 0, 0, array('t.page' => 'comm/action/card.php', 't.param' => 'complete', 't.user_id' => '0', 't.type' => 'createform', 't.entity' => $conf->entity));
if (!is_array($result) && $result < 0) {
	setEventMessages($defaultValues->error, $defaultValues->errors, 'errors');
} elseif (count($result) > 0) {
	$defval = reset($result)->value;
}
$formactions->form_select_status_action('agenda', $defval, 1, "AGENDA_EVENT_DEFAULT_STATUS", 0, 1, 'maxwidth200 onrightofpage');
print '</td></tr>'."\n";

// AGENDA_DEFAULT_FILTER_TYPE
print '<tr class="oddeven">'."\n";
print '<td>'.$langs->trans("AGENDA_DEFAULT_FILTER_TYPE").'</td>'."\n";
print '<td class="center">&nbsp;</td>'."\n";
print '<td class="right nowrap">'."\n";
$multiselect = 0;
if (getDolGlobalString('MAIN_ENABLE_MULTISELECT_TYPE')) {
	// We use an option here because it adds bugs when used on agenda page "peruser" and "list"
	$multiselect = (getDolGlobalString('AGENDA_USE_EVENT_TYPE'));
}
$formactions->select_type_actions(getDolGlobalString('AGENDA_DEFAULT_FILTER_TYPE'), "AGENDA_DEFAULT_FILTER_TYPE", '', (getDolGlobalString('AGENDA_USE_EVENT_TYPE') ? -1 : 1), 1, $multiselect);
print '</td></tr>'."\n";

// AGENDA_DEFAULT_FILTER_STATUS
// TODO Remove to use the default generic feature
print '<tr class="oddeven">'."\n";
print '<td>'.$langs->trans("AGENDA_DEFAULT_FILTER_STATUS").'</td>'."\n";
print '<td class="center">&nbsp;</td>'."\n";
print '<td class="right">'."\n";
$formactions->form_select_status_action('agenda', getDolGlobalString('AGENDA_DEFAULT_FILTER_STATUS'), 1, 'AGENDA_DEFAULT_FILTER_STATUS', 1, 2, 'minwidth100');
print '</td></tr>'."\n";

print '</table>';

print $form->buttonsSaveCancel("Save", '');

print '</form>';


print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
