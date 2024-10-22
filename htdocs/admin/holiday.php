<?php
/* Copyright (C) 2011-2019      Juanjo Menent	    <jmenent@2byte.es>
 * Copyright (C) 2011-2018      Philippe Grand	    <philippe.grand@atoo-net.com>
 * Copyright (C) 2018		    Charlene Benke		<charlie@patas-monkey.com>
 * Copyright (C) 2018-2024  Frédéric France         <frederic.france@free.fr>
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
 *	\file       htdocs/admin/contract.php
 *	\ingroup    contract
 *	\brief      Setup page of module Contracts
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/holiday/class/holiday.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/holiday.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("admin", "errors", "holiday"));

if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$value = GETPOST('value', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ09');	// Used by actions_setmoduleoptions.inc.php

$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');
$type = 'contract';

if (!getDolGlobalString('HOLIDAY_ADDON')) {
	$conf->global->HOLIDAY_ADDON = 'mod_holiday_madonna';
}


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

if ($action == 'updateMask') {
	$maskconst = GETPOST('maskconstholiday', 'aZ09');
	$maskvalue = GETPOST('maskholiday', 'alpha');
	$res = 0;
	if ($maskconst && preg_match('/_MASK$/', $maskconst)) {
		$res = dolibarr_set_const($db, $maskconst, $maskvalue, 'chaine', 0, '', $conf->entity);
	}

	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
} elseif ($action == 'specimen') { // For contract
	$modele = GETPOST('module', 'alpha');

	$holiday = new Holiday($db);
	$holiday->initAsSpecimen();

	// Search template files
	$file = '';
	$classname = '';
	$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
	foreach ($dirmodels as $reldir) {
		$file = dol_buildpath($reldir."core/modules/holiday/doc/pdf_".$modele.".modules.php", 0);
		if (file_exists($file)) {
			$classname = "pdf_".$modele;
			break;
		}
	}

	if ($classname !== '') {
		require_once $file;

		$module = new $classname($db);
		'@phan-var-force ModelePDFHoliday $module';

		if ($module->write_file($holiday, $langs) > 0) {
			header("Location: ".DOL_URL_ROOT."/document.php?modulepart=holiday&file=SPECIMEN.pdf");
			return;
		} else {
			setEventMessages($module->error, $module->errors, 'errors');
			dol_syslog($module->error, LOG_ERR);
		}
	} else {
		setEventMessages($langs->trans("ErrorModuleNotFound"), null, 'errors');
		dol_syslog($langs->trans("ErrorModuleNotFound"), LOG_ERR);
	}
} elseif ($action == 'set') {
	// Activate a model
	$ret = addDocumentModel($value, $type, $label, $scandir);
} elseif ($action == 'del') {
	$ret = delDocumentModel($value, $type);
	if ($ret > 0) {
		if ($conf->global->HOLIDAY_ADDON_PDF == "$value") {
			dolibarr_del_const($db, 'HOLIDAY_ADDON_PDF', $conf->entity);
		}
	}
} elseif ($action == 'setdoc') {
	// Set default model
	if (dolibarr_set_const($db, "HOLIDAY_ADDON_PDF", $value, 'chaine', 0, '', $conf->entity)) {
		// La constante qui a ete lue en avant du nouveau set
		// on passe donc par une variable pour avoir un affichage coherent
		$conf->global->HOLIDAY_ADDON_PDF = $value;
	}

	// On active le modele
	$ret = delDocumentModel($value, $type);
	if ($ret > 0) {
		$ret = addDocumentModel($value, $type, $label, $scandir);
	}
} elseif ($action == 'setmod') {
	// TODO Verifier si module numerotation choisi peut etre active
	// par appel method canBeActivated

	dolibarr_set_const($db, "HOLIDAY_ADDON", $value, 'chaine', 0, '', $conf->entity);
} elseif ($action == 'set_other') {
	$freetext = GETPOST('HOLIDAY_FREE_TEXT', 'restricthtml'); // No alpha here, we want exact string
	$res1 = dolibarr_set_const($db, "HOLIDAY_FREE_TEXT", $freetext, 'chaine', 0, '', $conf->entity);

	$draft = GETPOST('HOLIDAY_DRAFT_WATERMARK', 'alpha');
	$res2 = dolibarr_set_const($db, "HOLIDAY_DRAFT_WATERMARK", trim($draft), 'chaine', 0, '', $conf->entity);

	if (!($res1 > 0) || !($res2 > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}


/*
 * View
 */

$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

llxHeader('', '', '', '', 0, 0, '', '', '', 'mod-admin page-holiday');

$form = new Form($db);

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("HolidaySetup"), $linkback, 'title_setup');

$head = holiday_admin_prepare_head();

print dol_get_fiche_head($head, 'holiday', $langs->trans("Holidays"), -1, 'holiday');

/*
 * Holiday Numbering model
 */

print load_fiche_titre($langs->trans("HolidaysNumberingModules"), '', '');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td width="100">'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td>'.$langs->trans("Example").'</td>';
print '<td align="center" width="60">'.$langs->trans("Status").'</td>';
print '<td align="center" width="16">'.$langs->trans("ShortInfo").'</td>';
print "</tr>\n";

clearstatcache();

foreach ($dirmodels as $reldir) {
	$dir = dol_buildpath($reldir."core/modules/holiday/");

	if (is_dir($dir)) {
		$handle = opendir($dir);
		if (is_resource($handle)) {
			while (($file = readdir($handle)) !== false) {
				if (substr($file, 0, 12) == 'mod_holiday_' && substr($file, dol_strlen($file) - 3, 3) == 'php') {
					$file = substr($file, 0, dol_strlen($file) - 4);

					require_once $dir.$file.'.php';

					$module = new $file($db);

					'@phan-var-force ModelNumRefHolidays $module';

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
						if ($conf->global->HOLIDAY_ADDON == "$file") {
							print img_picto($langs->trans("Activated"), 'switch_on');
						} else {
							print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setmod&token='.newToken().'&value='.urlencode($file).'">';
							print img_picto($langs->trans("Disabled"), 'switch_off');
							print '</a>';
						}
						print '</td>';

						$holiday = new Holiday($db);
						$holiday->initAsSpecimen();

						// Info
						$htmltooltip = '';
						$htmltooltip .= ''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
						$nextval = $module->getNextValue($mysoc, $holiday);
						if ("$nextval" != $langs->trans("NotAvailable")) {  // Keep " on nextval
							$htmltooltip .= ''.$langs->trans("NextValue").': ';
							if ($nextval) {
								if (preg_match('/^Error/', $nextval) || $nextval == 'NotConfigured') {
									$nextval = $langs->trans($nextval);
								}
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
 *  Documents models for Holidays
 */

if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {
	print load_fiche_titre($langs->trans("TemplatePDFHolidays"), '', '');

	// Defined model definition table
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
			$realpath = $reldir."core/modules/holiday".$valdir;
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


								'@phan-var-force ModelePDFHoliday $module';

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

									// Active
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

									// Default
									print '<td class="center">';
									if ($conf->global->HOLIDAY_ADDON_PDF == $name) {
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
									$htmltooltip .= '<br>'.$langs->trans("Path").': '.preg_replace('/^\//', '', $realpath).'/'.$file;

									$htmltooltip .= '<br><br><u>'.$langs->trans("FeaturesSupported").':</u>';
									$htmltooltip .= '<br>'.$langs->trans("Logo").': '.yn($module->option_logo, 1, 1);
									$htmltooltip .= '<br>'.$langs->trans("PaymentMode").': '.yn($module->option_modereg, 1, 1);
									$htmltooltip .= '<br>'.$langs->trans("PaymentConditions").': '.yn($module->option_condreg, 1, 1);
									$htmltooltip .= '<br>'.$langs->trans("MultiLanguage").': '.yn($module->option_multilang, 1, 1);
									$htmltooltip .= '<br>'.$langs->trans("WatermarkOnDraftOrders").': '.yn($module->option_draft_watermark, 1, 1);


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
	print "<br>";
}


/*
 * Other options
 */

print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="set_other">';

print load_fiche_titre($langs->trans("OtherOptions"), '', '');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td align="center" width="60">'.$langs->trans("Value").'</td>';
print "</tr>\n";

//var_dump($conf->global->MAIN_NON_WORKING_DAYS_INCLUDE_MONDAY);
//var_dump($conf->global->MAIN_NON_WORKING_DAYS_INCLUDE_FRIDAY);
//var_dump($conf->global->MAIN_NON_WORKING_DAYS_INCLUDE_SATURDAY);
//var_dump($conf->global->MAIN_NON_WORKING_DAYS_INCLUDE_SUNDAY);

if (!isset($conf->global->MAIN_NON_WORKING_DAYS_INCLUDE_SATURDAY)) {
	$conf->global->MAIN_NON_WORKING_DAYS_INCLUDE_SATURDAY = 1;
}
if (!isset($conf->global->MAIN_NON_WORKING_DAYS_INCLUDE_SUNDAY)) {
	$conf->global->MAIN_NON_WORKING_DAYS_INCLUDE_SUNDAY = 1;
}

//var_dump($conf->global->MAIN_NON_WORKING_DAYS_INCLUDE_MONDAY);
//var_dump($conf->global->MAIN_NON_WORKING_DAYS_INCLUDE_FRIDAY);
//var_dump($conf->global->MAIN_NON_WORKING_DAYS_INCLUDE_SATURDAY);
//var_dump($conf->global->MAIN_NON_WORKING_DAYS_INCLUDE_SUNDAY);


// Set working days
print '<tr class="oddeven">';
print "<td>".$langs->trans("XIsAUsualNonWorkingDay", $langs->transnoentitiesnoconv("Monday"))."</td>";
print '<td class="center">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('MAIN_NON_WORKING_DAYS_INCLUDE_MONDAY', array(), null, 0);
} else {
	if (getDolGlobalString('MAIN_NON_WORKING_DAYS_INCLUDE_MONDAY')) {
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_other&token='.newToken().'&MAIN_NON_WORKING_DAYS_INCLUDE_MONDAY=1">'.img_picto($langs->trans("Enabled"), 'on').'</a>';
	} else {
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_other&token='.newToken().'&MAIN_NON_WORKING_DAYS_INCLUDE_MONDAY=0">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
	}
}
print "</td>";
print "</tr>";

// Set working days
print '<tr class="oddeven">';
print "<td>".$langs->trans("XIsAUsualNonWorkingDay", $langs->transnoentitiesnoconv("Friday"))."</td>";
print '<td class="center">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('MAIN_NON_WORKING_DAYS_INCLUDE_FRIDAY', array(), null, 0);
} else {
	if (getDolGlobalString('MAIN_NON_WORKING_DAYS_INCLUDE_FRIDAY')) {
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_other&token='.newToken().'&MAIN_NON_WORKING_DAYS_INCLUDE_FRIDAY=1">'.img_picto($langs->trans("Enabled"), 'on').'</a>';
	} else {
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_other&token='.newToken().'&MAIN_NON_WORKING_DAYS_INCLUDE_FRIDAY=0">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
	}
}
print "</td>";
print "</tr>";

// Set working days
print '<tr class="oddeven">';
print "<td>".$langs->trans("XIsAUsualNonWorkingDay", $langs->transnoentitiesnoconv("Saturday"))."</td>";
print '<td class="center">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('MAIN_NON_WORKING_DAYS_INCLUDE_SATURDAY', array(), null, 0, 0, 0, 2, 0, 1);
} else {
	if (getDolGlobalString('MAIN_NON_WORKING_DAYS_INCLUDE_SATURDAY')) {
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_other&token='.newToken().'&MAIN_NON_WORKING_DAYS_INCLUDE_SATURDAY=1">'.img_picto($langs->trans("Enabled"), 'on').'</a>';
	} else {
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_other&token='.newToken().'&MAIN_NON_WORKING_DAYS_INCLUDE_SATURDAY=0">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
	}
}
print "</td>";
print "</tr>";

// Set working days
print '<tr class="oddeven">';
print "<td>".$langs->trans("XIsAUsualNonWorkingDay", $langs->transnoentitiesnoconv("Sunday"))."</td>";
print '<td class="center">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('MAIN_NON_WORKING_DAYS_INCLUDE_SUNDAY', array(), null, 0, 0, 0, 2, 0, 1);
} else {
	if (getDolGlobalString('MAIN_NON_WORKING_DAYS_INCLUDE_SUNDAY')) {
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_other&token='.newToken().'&MAIN_NON_WORKING_DAYS_INCLUDE_SUNDAY=1">'.img_picto($langs->trans("Enabled"), 'on').'</a>';
	} else {
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_other&token='.newToken().'&MAIN_NON_WORKING_DAYS_INCLUDE_SUNDAY=0">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
	}
}
print "</td>";
print "</tr>";

// Set holiday decrease at the end of month
print '<tr class="oddeven">';
print "<td>".$langs->trans("ConsumeHolidaysAtTheEndOfTheMonthTheyAreTakenAt")."</td>";
print '<td class="center">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('HOLIDAY_DECREASE_AT_END_OF_MONTH', array(), null, 0, 0, 0, 2, 0, 1);
} else {
	if (getDolGlobalString('HOLIDAY_DECREASE_AT_END_OF_MONTH')) {
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_other&token='.newToken().'&HOLIDAY_DECREASE_AT_END_OF_MONTH=1">'.img_picto($langs->trans("Enabled"), 'on').'</a>';
	} else {
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_other&token='.newToken().'&HOLIDAY_DECREASE_AT_END_OF_MONTH=0">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
	}
}
print "</td>";
print "</tr>";

if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {
	$substitutionarray = pdf_getSubstitutionArray($langs, array('objectamount'), null, 2);
	$substitutionarray['__(AnyTranslationKey)__'] = $langs->trans("Translation");
	$htmltext = '<i>'.$langs->trans("AvailableVariables").':<br>';
	foreach ($substitutionarray as $key => $val) {
		$htmltext .= $key.'<br>';
	}
	$htmltext .= '</i>';

	print '<tr class="oddeven"><td colspan="2">';
	print $form->textwithpicto($langs->trans("FreeLegalTextOnHolidays"), $langs->trans("AddCRIfTooLong").'<br><br>'.$htmltext, 1, 'help', '', 0, 2, 'tooltiphelp');
	print '<br>';
	$variablename = 'HOLIDAY_FREE_TEXT';
	if (!getDolGlobalString('PDF_ALLOW_HTML_FOR_FREE_TEXT')) {
		print '<textarea name="'.$variablename.'" class="flat" cols="120">'.getDolGlobalString($variablename).'</textarea>';
	} else {
		include_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
		$doleditor = new DolEditor($variablename, getDolGlobalString($variablename), '', 80, 'dolibarr_notes');
		print $doleditor->Create();
	}
	print '</td></tr>'."\n";

	//Use draft Watermark

	print '<tr class="oddeven"><td>';
	print $form->textwithpicto($langs->trans("WatermarkOnDraftHolidayCards"), $htmltext, 1, 'help', '', 0, 2, 'watermarktooltip').'<br>';
	print '</td><td>';
	print '<input class="flat minwidth200" type="text" name="HOLIDAY_DRAFT_WATERMARK" value="'.dol_escape_htmltag(getDolGlobalString('HOLIDAY_DRAFT_WATERMARK')).'">';
	print '</td></tr>'."\n";
}

print '</table>';
print '</div>';

print $form->buttonsSaveCancel("Save", '');

print '</form>';



print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
