<?php
/* Copyright (C) 2010-2014	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2011-2016	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2011-2015	Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2011-2018	Philippe Grand		<philippe.grand@atoo-net.com>
 * Copyright (C) 2013		Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2015       Marcos García       <marcosgdf@gmail.com>
 * Copyright (C) 2018		Ferran Marcet		<fmarcet@2byte.es>
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
 *  \file       htdocs/projet/admin/project.php
 *  \ingroup    project
 *  \brief      Page to setup project module
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'errors', 'other', 'projects'));

if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$modulepart = GETPOST('modulepart', 'aZ09');

$value = GETPOST('value', 'alpha');
$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');
$type = 'project';


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

if ($action == 'updateMask') {
	$maskconstproject = GETPOST('maskconstproject', 'aZ09');
	$maskproject = GETPOST('maskproject', 'alpha');

	if ($maskconstproject && preg_match('/_MASK$/', $maskconstproject)) {
		$res = dolibarr_set_const($db, $maskconstproject, $maskproject, 'chaine', 0, '', $conf->entity);
	}

	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

if ($action == 'updateMaskTask') {
	$maskconstmasktask = GETPOST('maskconsttask', 'aZ09');
	$masktaskt = GETPOST('masktask', 'alpha');

	if ($maskconstmasktask && preg_match('/_MASK$/', $maskconstmasktask)) {
		$res = dolibarr_set_const($db, $maskconstmasktask, $masktaskt, 'chaine', 0, '', $conf->entity);
	}

	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
} elseif ($action == 'specimen') {
	$modele = GETPOST('module', 'alpha');

	$project = new Project($db);
	$project->initAsSpecimen();

	// Search template files
	$file = '';
	$classname = '';
	$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
	foreach ($dirmodels as $reldir) {
		$file = dol_buildpath($reldir."core/modules/project/doc/pdf_".$modele.".modules.php", 0);
		if (file_exists($file)) {
			$classname = "pdf_".$modele;
			break;
		}
	}

	if ($classname !== '') {
		require_once $file;

		$module = new $classname($db);

		'@phan-var-force ModelePDFProjects $module';

		if ($module->write_file($project, $langs) > 0) {
			header("Location: ".DOL_URL_ROOT."/document.php?modulepart=project&file=SPECIMEN.pdf");
			return;
		} else {
			setEventMessages($obj->error, $obj->errors, 'errors');
			dol_syslog($obj->error, LOG_ERR);
		}
	} else {
		setEventMessages($langs->trans("ErrorModuleNotFound"), null, 'errors');
		dol_syslog($langs->trans("ErrorModuleNotFound"), LOG_ERR);
	}
} elseif ($action == 'specimentask') {
	$modele = GETPOST('module', 'alpha');

	$project = new Project($db);
	$project->initAsSpecimen();

	// Search template files
	$file = '';
	$classname = '';
	$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
	foreach ($dirmodels as $reldir) {
		$file = dol_buildpath($reldir."core/modules/project/task/doc/pdf_".$modele.".modules.php", 0);
		if (file_exists($file)) {
			$classname = "pdf_".$modele;
			break;
		}
	}

	if ($classname !== '') {
		require_once $file;

		$module = new $classname($db);

		'@phan-var-force ModelePDFTask $module';

		if ($module->write_file($project, $langs) > 0) {
			header("Location: ".DOL_URL_ROOT."/document.php?modulepart=project_task&file=SPECIMEN.pdf");
			return;
		} else {
			setEventMessages($obj->error, $obj->errors, 'errors');
			dol_syslog($obj->error, LOG_ERR);
		}
	} else {
		setEventMessages($langs->trans("ErrorModuleNotFound"), null, 'errors');
		dol_syslog($langs->trans("ErrorModuleNotFound"), LOG_ERR);
	}
} elseif ($action == 'set') {
	// Activate a model
	$ret = addDocumentModel($value, $type, $label, $scandir);
} elseif ($action == 'settask') {
	// Activate a model for task
	$ret = addDocumentModel($value, 'project_task', $label, $scandir);
} elseif ($action == 'del') {
	$ret = delDocumentModel($value, $type);
	if ($ret > 0) {
		if ($conf->global->PROJECT_ADDON_PDF == "$value") {
			dolibarr_del_const($db, 'PROJECT_ADDON_PDF', $conf->entity);
		}
	}
} elseif ($action == 'deltask') {
	$ret = delDocumentModel($value, 'project_task');
	if ($ret > 0) {
		if ($conf->global->PROJECT_TASK_ADDON_PDF == "$value") {
			dolibarr_del_const($db, 'PROJECT_TASK_ADDON_PDF', $conf->entity);
		}
	}
} elseif ($action == 'setdoc') {
	// Set default model
	dolibarr_set_const($db, "PROJECT_ADDON_PDF", $value, 'chaine', 0, '', $conf->entity);

	// On active le modele
	$ret = delDocumentModel($value, $type);
	if ($ret > 0) {
		$ret = addDocumentModel($value, $type, $label, $scandir);
	}
} elseif ($action == 'setdoctask') {
	if (dolibarr_set_const($db, "PROJECT_TASK_ADDON_PDF", $value, 'chaine', 0, '', $conf->entity)) {
		// La constante qui a ete lue en avant du nouveau set
		// on passe donc par une variable pour avoir un affichage coherent
		$conf->global->PROJECT_TASK_ADDON_PDF = $value;
	}

	// On active le modele
	$ret = delDocumentModel($value, 'project_task');
	if ($ret > 0) {
		$ret = addDocumentModel($value, 'project_task', $label, $scandir);
	}
} elseif ($action == 'setmod') {
	// TODO Verifier si module numerotation choisi peut etre active
	// par appel methode canBeActivated

	dolibarr_set_const($db, "PROJECT_ADDON", $value, 'chaine', 0, '', $conf->entity);
} elseif ($action == 'setmodtask') {
	// TODO Verifier si module numerotation choisi peut etre active
	// par appel methode canBeActivated

	dolibarr_set_const($db, "PROJECT_TASK_ADDON", $value, 'chaine', 0, '', $conf->entity);
} elseif ($action == 'updateoptions') {
	if (GETPOST('PROJECT_USE_SEARCH_TO_SELECT')) {
		$companysearch = GETPOST('activate_PROJECT_USE_SEARCH_TO_SELECT', 'alpha');
		if (dolibarr_set_const($db, "PROJECT_USE_SEARCH_TO_SELECT", $companysearch, 'chaine', 0, '', $conf->entity)) {
			$conf->global->PROJECT_USE_SEARCH_TO_SELECT = $companysearch;
		}
	}
	if (GETPOST('PROJECT_ALLOW_TO_LINK_FROM_OTHER_COMPANY')) {
		$projectToSelect = GETPOST('projectToSelect', 'alpha');
		dolibarr_set_const($db, 'PROJECT_ALLOW_TO_LINK_FROM_OTHER_COMPANY', $projectToSelect, 'chaine', 0, '', $conf->entity); //Allow to disable this configuration if empty value
	}
	if (GETPOST('PROJECT_TIMESHEET_PREVENT_AFTER_MONTHS')) {
		$timesheetFreezeDuration = GETPOST('timesheetFreezeDuration', 'alpha');
		dolibarr_set_const($db, 'PROJECT_TIMESHEET_PREVENT_AFTER_MONTHS', intval($timesheetFreezeDuration), 'chaine', 0, '', $conf->entity); //Allow to disable this configuration if empty value
	}
} elseif (preg_match('/^(set|del)_?([A-Z_]+)$/', $action, $reg)) {
	// Set boolean (on/off) constants
	if (!dolibarr_set_const($db, $reg[2], ($reg[1] === 'set' ? '1' : '0'), 'chaine', 0, '', $conf->entity) > 0) {
		dol_print_error($db);
	}
}

/*
 * View
 */

$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

llxHeader("", $langs->trans("ProjectsSetup"), '', '', 0, 0, '', '', '', 'mod-project page-admin');

$form = new Form($db);

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("ProjectsSetup"), $linkback, 'title_setup');

$head = project_admin_prepare_head();

print dol_get_fiche_head($head, 'project', $langs->trans("Projects"), -1, 'project');



// Main options
$form = new Form($db);

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="setmainoptions">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print "<td>".$langs->trans("Parameters")."</td>\n";
print '<td class="right" width="60">'.$langs->trans("Value").'</td>'."\n";
print '<td width="80">&nbsp;</td></tr>'."\n";

print '<tr class="oddeven">';
print '<td width="80%">'.$langs->trans("ManageOpportunitiesStatus").'</td>';
print '<td width="60" class="right">';
print ajax_constantonoff("PROJECT_USE_OPPORTUNITIES", null, null, 0, 0, 1);
print '</td><td class="right">';
print "</td>";
print '</tr>';


print '<tr class="oddeven">';
print '<td width="80%">'.$langs->trans("ManageTasks").'</td>';
print '<td width="60" class="right">';
print ajax_constantonoff("PROJECT_HIDE_TASKS", array(), null, 1);
print '</td><td class="right">';
print "</td>";
print '</tr>';

print '</table></form>';

print '<br>';



/*
 * Projects Numbering model
 */

print load_fiche_titre($langs->trans("ProjectsNumberingModules"), '', '');

print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td width="100">'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td>'.$langs->trans("Example").'</td>';
print '<td class="center" width="60">'.$langs->trans("Activated").'</td>';
print '<td class="center" width="80">'.$langs->trans("ShortInfo").'</td>';
print "</tr>\n";

clearstatcache();

foreach ($dirmodels as $reldir) {
	$dir = dol_buildpath($reldir."core/modules/project/");

	if (is_dir($dir)) {
		$handle = opendir($dir);
		if (is_resource($handle)) {
			while (($file = readdir($handle)) !== false) {
				if (preg_match('/^(mod_.*)\.php$/i', $file, $reg)) {
					$file = $reg[1];
					$classname = substr($file, 4);

					require_once $dir.$file.'.php';

					$module = new $file();

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
							print $langs->trans($tmp);
						} else {
							print $tmp;
						}
						print '</td>'."\n";

						print '<td class="center">';
						if ($conf->global->PROJECT_ADDON == 'mod_'.$classname) {
							print img_picto($langs->trans("Activated"), 'switch_on');
						} else {
							print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setmod&token='.newToken().'&value=mod_'.$classname.'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
						}
						print '</td>';

						$project = new Project($db);
						$project->initAsSpecimen();

						// Info
						$htmltooltip = '';
						$htmltooltip .= ''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
						$nextval = $module->getNextValue($mysoc, $project);
						if ("$nextval" != $langs->trans("NotAvailable")) {	// Keep " on nextval
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

if (!getDolGlobalString('PROJECT_HIDE_TASKS')) {
	// Task numbering module
	print load_fiche_titre($langs->trans("TasksNumberingModules"), '', '');

	print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td width="100">'.$langs->trans("Name").'</td>';
	print '<td>'.$langs->trans("Description").'</td>';
	print '<td>'.$langs->trans("Example").'</td>';
	print '<td class="center" width="60">'.$langs->trans("Activated").'</td>';
	print '<td class="center" width="80">'.$langs->trans("ShortInfo").'</td>';
	print "</tr>\n";

	clearstatcache();

	foreach ($dirmodels as $reldir) {
		$dir = dol_buildpath($reldir."core/modules/project/task/");

		if (is_dir($dir)) {
			$handle = opendir($dir);
			if (is_resource($handle)) {
				while (($file = readdir($handle)) !== false) {
					if (preg_match('/^(mod_.*)\.php$/i', $file, $reg)) {
						$file = $reg[1];
						$classname = substr($file, 4);

						require_once $dir.$file.'.php';

						$module = new $file();

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

							// Show example of numbering module
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
							if ($conf->global->PROJECT_TASK_ADDON == 'mod_'.$classname) {
								print img_picto($langs->trans("Activated"), 'switch_on');
							} else {
								print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setmodtask&token='.newToken().'&value=mod_'.$classname.'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
							}
							print '</td>';

							$project = new Project($db);
							$project->initAsSpecimen();

							// Info
							$htmltooltip = '';
							$htmltooltip .= ''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
							$nextval = $module->getNextValue($mysoc, $project);
							if ("$nextval" != $langs->trans("NotAvailable")) {	// Keep " on nextval
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
}


/*
 * Document templates generators
 */

print load_fiche_titre($langs->trans("ProjectsModelModule"), '', '');

// Defini tableau def de modele
$type = 'project';
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

print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
print '<table class="noborder" width="100%">'."\n";
print '<tr class="liste_titre">'."\n";
print '  <td width="100">'.$langs->trans("Name")."</td>\n";
print "  <td>".$langs->trans("Description")."</td>\n";
print '<td class="center" width="60">'.$langs->trans("Activated")."</td>\n";
print '<td class="center" width="60">'.$langs->trans("Default")."</td>\n";
print '<td class="center" width="80">'.$langs->trans("ShortInfo").'</td>';
print '<td class="center" width="80">'.$langs->trans("Preview").'</td>';
print "</tr>\n";

clearstatcache();

$filelist = array();
foreach ($dirmodels as $reldir) {
	foreach (array('', '/doc') as $valdir) {
		$dir = dol_buildpath($reldir."core/modules/project/".$valdir);

		if (is_dir($dir)) {
			$handle = opendir($dir);
			if (is_resource($handle)) {
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
								print "</td>\n";

								// Active
								if (in_array($name, $def)) {
									print "<td class=\"center\">\n";
									print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=del&token='.newToken().'&value='.urlencode($name).'&scan_dir='.urlencode($module->scandir).'&label='.urlencode($module->name).'">';
									print img_picto($langs->trans("Enabled"), 'switch_on');
									print '</a>';
									print "</td>";
								} else {
									print "<td class=\"center\">\n";
									print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=set&token='.newToken().'&value='.urlencode($name).'&scan_dir='.urlencode($module->scandir).'&label='.urlencode($module->name).'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
									print "</td>";
								}

								// Default
								print "<td class=\"center\">";
								if ($conf->global->PROJECT_ADDON_PDF == "$name") {
									print img_picto($langs->trans("Default"), 'on');
								} else {
									print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setdoc&token='.newToken().'&value='.urlencode($name).'&scan_dir='.urlencode($module->scandir).'&label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
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

								print '<td class="center">';
								print $form->textwithpicto('', $htmltooltip, 1, 0);
								print '</td>';

								// Preview
								print '<td class="center">';
								if ($module->type == 'pdf') {
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.$name.'">'.img_object($langs->trans("Preview"), 'bill').'</a>';
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



if (!getDolGlobalString('PROJECT_HIDE_TASKS')) {
	/*
	 * Modeles documents for Task
	 */

	print load_fiche_titre($langs->trans("TaskModelModule"), '', '');

	// Defini tableau def de modele
	$type = 'project_task';
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

	print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
	print '<table class="noborder" width="100%">'."\n";
	print '<tr class="liste_titre">'."\n";
	print '  <td width="100">'.$langs->trans("Name")."</td>\n";
	print "  <td>".$langs->trans("Description")."</td>\n";
	print '<td class="center" width="60">'.$langs->trans("Activated")."</td>\n";
	print '<td class="center" width="60">'.$langs->trans("Default")."</td>\n";
	print '<td class="center" width="80">'.$langs->trans("ShortInfo").'</td>';
	print '<td class="center" width="80">'.$langs->trans("Preview").'</td>';
	print "</tr>\n";

	clearstatcache();

	foreach ($dirmodels as $reldir) {
		foreach (array('', '/doc') as $valdir) {
			$dir = dol_buildpath($reldir."core/modules/project/task/".$valdir);

			if (is_dir($dir)) {
				$handle = opendir($dir);
				if (is_resource($handle)) {
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
									print "</td>\n";

									// Active
									if (in_array($name, $def)) {
										print '<td class="center">'."\n";
										print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=deltask&token='.newToken().'&value='.urlencode($name).'&scan_dir='.urlencode($module->scandir).'&label='.urlencode($module->name).'">';
										print img_picto($langs->trans("Enabled"), 'switch_on');
										print '</a>';
										print "</td>";
									} else {
										print '<td class="center">'."\n";
										print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=settask&token='.newToken().'&value='.urlencode($name).'&scan_dir='.urlencode($module->scandir).'&label='.urlencode($module->name).'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
										print "</td>";
									}

									// Default
									print '<td class="center">';
									if ($conf->global->PROJECT_TASK_ADDON_PDF == "$name") {
										print img_picto($langs->trans("Default"), 'on');
									} else {
										print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setdoctask&token='.newToken().'&value='.urlencode($name).'&scan_dir='.urlencode($module->scandir).'&label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
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

									print '<td class="center">';
									print $form->textwithpicto('', $htmltooltip, 1, 0);
									print '</td>';

									// Preview
									print '<td class="center">';
									if ($module->type == 'pdf') {
										print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimentask&module='.$name.'">'.img_object($langs->trans("Preview"), 'bill').'</a>';
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
}


print load_fiche_titre($langs->trans("Other"), '', '');

// Other options
$form = new Form($db);

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="updateoptions">';
print '<input type="hidden" name="page_y" value="">';

print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print "<td>".$langs->trans("Parameters")."</td>\n";
print '<td class="right" width="60">'.$langs->trans("Value").'</td>'."\n";
print '<td width="80">&nbsp;</td></tr>'."\n";

print '<tr class="oddeven">';
print '<td width="80%">'.$langs->trans("UseSearchToSelectProject").'</td>';
if (!$conf->use_javascript_ajax) {
	print '<td class="nowrap right" colspan="2">';
	print $langs->trans("NotAvailableWhenAjaxDisabled");
	print "</td>";
} else {
	print '<td width="60" class="right">';
	$arrval = array('0' => $langs->trans("No"),
		'1' => $langs->trans("Yes").' ('.$langs->trans("NumberOfKeyToSearch", 1).')',
		'2' => $langs->trans("Yes").' ('.$langs->trans("NumberOfKeyToSearch", 2).')',
		'3' => $langs->trans("Yes").' ('.$langs->trans("NumberOfKeyToSearch", 3).')',
	);
	print $form->selectarray("activate_PROJECT_USE_SEARCH_TO_SELECT", $arrval, getDolGlobalString("PROJECT_USE_SEARCH_TO_SELECT"));
	print '</td><td class="right">';
	print '<input type="submit" class="button small reposition" name="PROJECT_USE_SEARCH_TO_SELECT" value="'.$langs->trans("Modify").'">';
	print "</td>";
}
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("AllowToSelectProjectFromOtherCompany").'</td>';

print '<td class="right" width="60" colspan="2">';
print '<input type="text" id="projectToSelect" name="projectToSelect" value="' . getDolGlobalString('PROJECT_ALLOW_TO_LINK_FROM_OTHER_COMPANY').'"/>&nbsp;';
print $form->textwithpicto('', $langs->trans('AllowToLinkFromOtherCompany'));
print '<input type="submit" class="button small reposition" name="PROJECT_ALLOW_TO_LINK_FROM_OTHER_COMPANY" value="'.$langs->trans("Modify").'">';
print '</td>';
print '</tr>';

$key = 'PROJECT_CLASSIFY_CLOSED_WHEN_ALL_TASKS_DONE';
echo '<tr class="oddeven">',
'<td class="left">',
$form->textwithpicto($langs->transnoentities($key), $langs->transnoentities($key . '_help')),
'</td>',
'<td class="right" colspan="2">',
ajax_constantonoff($key),
'</td>',
'</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("TimesheetPreventAfterFollowingMonths").'</td>';

print '<td class="right" width="60" colspan="2">';
print '<input type="number" class="width50" id="timesheetFreezeDuration" name="timesheetFreezeDuration" min="0" step="1" value="' . getDolGlobalString('PROJECT_TIMESHEET_PREVENT_AFTER_MONTHS').'"/>&nbsp;';
print '<input type="submit" class="button small reposition" name="PROJECT_TIMESHEET_PREVENT_AFTER_MONTHS" value="'.$langs->trans("Modify").'">';
print '</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td class="left">';
print $form->textwithpicto($langs->transnoentities('PROJECT_DISPLAY_LINKED_BY_CONTACT'), $langs->transnoentities('PROJECT_DISPLAY_LINKED_BY_CONTACT_help'));
print '</td>';
print '<td class="right" colspan="2">';
print ajax_constantonoff('PROJECT_DISPLAY_LINKED_BY_CONTACT');
print '</td>';
print '</tr>';

print '</table>';
print '</div>';

print '</form>';

// End of page
llxFooter();
$db->close();
