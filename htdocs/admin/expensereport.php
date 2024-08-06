<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville         <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur          <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio          <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier               <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2014 Regis Houssin                <regis.houssin@inodbox.com>
 * Copyright (C) 2008      Raphael Bertrand (Resultic)  <raphael.bertrand@resultic.fr>
 * Copyright (C) 2011-2013 Juanjo Menent			    <jmenent@2byte.es>
 * Copyright (C) 2011-2022 Philippe Grand			    <philippe.grand@atoo-net.com>
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
 *	\file       htdocs/admin/expensereport.php
 *	\ingroup    expensereport
 *	\brief      Setup page of module ExpenseReport
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/expensereport.lib.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'errors', 'trips', 'other'));

if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$value = GETPOST('value', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ09');	// Used by actions_setmoduleoptions.inc.php

$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');
$type = 'expensereport';


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

if ($action == 'updateMask') {
	$maskconst = GETPOST('maskconst', 'aZ09');
	$maskvalue = GETPOST('maskvalue', 'alpha');
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
} elseif ($action == 'specimen') { // For fiche expensereport
	$modele = GETPOST('module', 'alpha');

	$expensespecimen = new ExpenseReport($db);
	$expensespecimen->initAsSpecimen();
	$expensespecimen->status = 0; // Force statut draft to show watermark

	// Search template files
	$file = '';
	$classname = '';
	$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
	foreach ($dirmodels as $reldir) {
		$file = dol_buildpath($reldir."core/modules/expensereport/doc/pdf_".$modele.".modules.php", 0);
		if (file_exists($file)) {
			$classname = "pdf_".$modele;
			break;
		}
	}

	if ($classname !== '') {
		require_once $file;

		$module = new $classname($db);
		'@phan-var-force ModeleExpenseReport $module';

		if ($module->write_file($expensespecimen, $langs) > 0) {
			header("Location: ".DOL_URL_ROOT."/document.php?modulepart=expensereport&file=SPECIMEN.pdf");
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
	if ($ret > 0 && !getDolGlobalString('EXPENSEREPORT_ADDON_PDF')) {
		dolibarr_set_const($db, 'EXPENSEREPORT_ADDON_PDF', $value, 'chaine', 0, '', $conf->entity);
	}
} elseif ($action == 'del') {
	$ret = delDocumentModel($value, $type);
	if ($ret > 0) {
		if ($conf->global->EXPENSEREPORT_ADDON_PDF == "$value") {
			dolibarr_del_const($db, 'EXPENSEREPORT_ADDON_PDF', $conf->entity);
		}
	}
} elseif ($action == 'setdoc') {
	// Set default model
	if (dolibarr_set_const($db, "EXPENSEREPORT_ADDON_PDF", $value, 'chaine', 0, '', $conf->entity)) {
		// La constante qui a ete lue en avant du nouveau set
		// on passe donc par une variable pour avoir un affichage coherent
		$conf->global->EXPENSEREPORT_ADDON_PDF = $value;
	}

	// On active le modele
	$ret = delDocumentModel($value, $type);
	if ($ret > 0) {
		$ret = addDocumentModel($value, $type, $label, $scandir);
	}
} elseif ($action == 'setmod') {
	// TODO Verifier si module numerotation choisi peut etre active
	// par appel method canBeActivated

	dolibarr_set_const($db, "EXPENSEREPORT_ADDON", $value, 'chaine', 0, '', $conf->entity);
} elseif ($action == 'setoptions') {
	$db->begin();

	$freetext = GETPOST('EXPENSEREPORT_FREE_TEXT', 'restricthtml'); // No alpha here, we want exact string
	$res1 = dolibarr_set_const($db, "EXPENSEREPORT_FREE_TEXT", $freetext, 'chaine', 0, '', $conf->entity);

	$draft = GETPOST('EXPENSEREPORT_DRAFT_WATERMARK', 'alpha');
	$res2 = dolibarr_set_const($db, "EXPENSEREPORT_DRAFT_WATERMARK", trim($draft), 'chaine', 0, '', $conf->entity);

	$res3 = 0;
	if (isModEnabled('project') && GETPOSTISSET('EXPENSEREPORT_PROJECT_IS_REQUIRED')) {  // Option may not be provided
		$res3 = dolibarr_set_const($db, 'EXPENSEREPORT_PROJECT_IS_REQUIRED', GETPOSTINT('EXPENSEREPORT_PROJECT_IS_REQUIRED'), 'chaine', 0, '', $conf->entity);
	}

	$dates = GETPOSTINT('EXPENSEREPORT_PREFILL_DATES_WITH_CURRENT_MONTH');
	$res4 = dolibarr_set_const($db, 'EXPENSEREPORT_PREFILL_DATES_WITH_CURRENT_MONTH', intval($dates), 'chaine', 0, '', $conf->entity);

	$amounts = GETPOSTINT('EXPENSEREPORT_FORCE_LINE_AMOUNTS_INCLUDING_TAXES_ONLY');
	$res5 = dolibarr_set_const($db, 'EXPENSEREPORT_FORCE_LINE_AMOUNTS_INCLUDING_TAXES_ONLY', intval($amounts), 'chaine', 0, '', $conf->entity);

	$linesblocked = GETPOSTINT('EXPENSEREPORT_BLOCK_LINE_CREATION_IF_NOT_BETWEEN_DATES');
	$res6 = dolibarr_set_const($db, 'EXPENSEREPORT_BLOCK_LINE_CREATION_IF_NOT_BETWEEN_DATES', intval($linesblocked), 'chaine', 0, '', $conf->entity);

	if (!($res1 > 0) || !($res2 > 0) || !($res3 >= 0) || !($res4 > 0) || !($res5 > 0) || !($res6 > 0)) {
		$error++;
	}

	if (!$error) {
		$db->commit();
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		$db->rollback();
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}


/*
 * View
 */

$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

llxHeader('', $langs->trans("ExpenseReportsSetup"), '', '', 0, 0, '', '', '', 'mod-admin page-expensereport');

$form = new Form($db);

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("ExpenseReportsSetup"), $linkback, 'title_setup');


$head = expensereport_admin_prepare_head();

print dol_get_fiche_head($head, 'expensereport', $langs->trans("ExpenseReports"), -1, 'trip');

/*
 * Expense report numbering model
 */

print load_fiche_titre($langs->trans("ExpenseReportNumberingModules"), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td class="nowrap">'.$langs->trans("Example").'</td>';
print '<td class="center" width="60">'.$langs->trans("Status").'</td>';
print '<td class="center" width="16">'.$langs->trans("ShortInfo").'</td>';
print '</tr>'."\n";

clearstatcache();

foreach ($dirmodels as $reldir) {
	$dir = dol_buildpath($reldir."core/modules/expensereport/");

	if (is_dir($dir)) {
		$handle = opendir($dir);
		if (is_resource($handle)) {
			while (($file = readdir($handle)) !== false) {
				if (substr($file, 0, 18) == 'mod_expensereport_' && substr($file, dol_strlen($file) - 3, 3) == 'php') {
					$file = substr($file, 0, dol_strlen($file) - 4);

					require_once $dir.$file.'.php';

					$module = new $file($db);

					'@phan-var-force ModeleNumRefExpenseReport $module';

					// Show modules according to features level
					if ($module->version == 'development' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 2) {
						continue;
					}
					if ($module->version == 'experimental' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 1) {
						continue;
					}

					if ($module->isEnabled()) {
						print '<tr class="oddeven"><td>'.$module->nom."</td><td>\n";
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
						if ($conf->global->EXPENSEREPORT_ADDON == $file) {
							print img_picto($langs->trans("Activated"), 'switch_on');
						} else {
							print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setmod&token='.newToken().'&value='.urlencode($file).'">';
							print img_picto($langs->trans("Disabled"), 'switch_off');
							print '</a>';
						}
						print '</td>';

						$exp = new ExpenseReport($db);
						$exp->initAsSpecimen();

						// Info
						$htmltooltip = '';
						$htmltooltip .= ''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
						$nextval = $module->getNextValue($exp);
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

						print "</tr>\n";
					}
				}
			}
			closedir($handle);
		}
	}
}
print "</table><br>\n";

/*
 *  Documents models for ExpenseReport
 */

print load_fiche_titre($langs->trans("TemplatePDFExpenseReports"), '', '');

// Defini tableau def des modeles
$type = 'expensereport';
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

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td class="center" width="60">'.$langs->trans("Status")."</td>\n";
print '<td class="center" width="60">'.$langs->trans("Default")."</td>\n";
print '<td class="center" width="80">'.$langs->trans("ShortInfo").'</td>';
print '<td class="center" width="80">'.$langs->trans("Preview").'</td>';
print "</tr>\n";

clearstatcache();

foreach ($dirmodels as $reldir) {
	$dir = dol_buildpath($reldir."core/modules/expensereport/doc");

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

						'@phan-var-force ModeleExpenseReport $module';

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
								print '<a href="'.$_SERVER["PHP_SELF"].'?action=del&token='.newToken().'&value='.urlencode($name).'&scan_dir='.urlencode($module->scandir).'&label='.urlencode($module->name).'">';
								print img_picto($langs->trans("Enabled"), 'switch_on');
								print '</a>';
								print "</td>";
							} else {
								print '<td class="center">'."\n";
								print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&token='.newToken().'&value='.urlencode($name).'&scan_dir='.urlencode($module->scandir).'&label='.urlencode($module->name).'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
								print "</td>";
							}

							// Default
							print '<td class="center">';
							if ($conf->global->EXPENSEREPORT_ADDON_PDF == "$name") {
								print img_picto($langs->trans("Default"), 'on');
							} else {
								print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&token='.newToken().'&value='.$name.'&scan_dir='.$module->scandir.'&label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
							}
							print '</td>';

							// Info
							$htmltooltip = ''.$langs->trans("Name").': '.$module->name;
							$htmltooltip .= '<br>'.$langs->trans("Type").': '.($module->type ? $module->type : $langs->trans("Unknown"));
							$htmltooltip .= '<br>'.$langs->trans("Width").'/'.$langs->trans("Height").': '.$module->page_largeur.'/'.$module->page_hauteur;
							$htmltooltip .= '<br><br><u>'.$langs->trans("FeaturesSupported").':</u>';
							$htmltooltip .= '<br>'.$langs->trans("Logo").': '.yn($module->option_logo, 1, 1);
							$htmltooltip .= '<br>'.$langs->trans("PaymentMode").': '.yn($module->option_modereg, 1, 1);
							$htmltooltip .= '<br>'.$langs->trans("PaymentConditions").': '.yn($module->option_condreg, 1, 1);
							$htmltooltip .= '<br>'.$langs->trans("MultiLanguage").': '.yn($module->option_multilang, 1, 1);
							$htmltooltip .= '<br>'.$langs->trans("WatermarkOnDraftOrders").': '.yn($module->option_draft_watermark, 1, 1);
							print '<td class="center">';
							print $form->textwithpicto('', $htmltooltip, -1, 0);
							print '</td>';

							// Preview
							print '<td class="center">';
							if ($module->type == 'pdf') {
								print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.$name.'">'.img_object($langs->trans("Preview"), 'pdf').'</a>';
							} else {
								print img_object($langs->trans("PreviewNotAvailable"), 'generic');
							}
							print '</td>';

							print '</tr>';
						}
					}
				}
			}
		}
	}
}

print '</table>';


print '<br>';



/*
 * Other options
 */

print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="setoptions">';

print load_fiche_titre($langs->trans("OtherOptions"), '', '');
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td class="center" width="60"></td>';
print "</tr>\n";

$substitutionarray = pdf_getSubstitutionArray($langs, null, null, 2);
$substitutionarray['__(AnyTranslationKey)__'] = $langs->trans("Translation");
$htmltext = '<i>'.$langs->trans("AvailableVariables").':<br>';
foreach ($substitutionarray as $key => $val) {
	$htmltext .= $key.'<br>';
}
$htmltext .= '</i>';

print '<tr class="oddeven"><td colspan="2">';
print $form->textwithpicto($langs->trans("FreeLegalTextOnExpenseReports"), $langs->trans("AddCRIfTooLong").'<br><br>'.$htmltext, 1, 'help', '', 0, 2, 'freetexttooltip').'<br>';
$variablename = 'EXPENSEREPORT_FREE_TEXT';
if (!getDolGlobalString('PDF_ALLOW_HTML_FOR_FREE_TEXT')) {
	print '<textarea name="'.$variablename.'" class="flat" cols="120">'.getDolGlobalString($variablename).'</textarea>';
} else {
	include_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$doleditor = new DolEditor($variablename, getDolGlobalString($variablename), '', 80, 'dolibarr_notes');
	print $doleditor->Create();
}
print '</td></tr>'."\n";

// Use draft Watermark

print '<tr class="oddeven"><td colspan="2">';
print $form->textwithpicto($langs->trans("WatermarkOnDraftExpenseReports"), $htmltext, 1, 'help', '', 0, 2, 'watermarktooltip').'<br>';
print '<input class="flat minwidth200" type="text" name="EXPENSEREPORT_DRAFT_WATERMARK" value="'.dol_escape_htmltag(getDolGlobalString('EXPENSEREPORT_DRAFT_WATERMARK')).'">';
print '</td></tr>'."\n";

if (isModEnabled('project')) {
	print '<tr class="oddeven"><td>';
	print $langs->trans('ProjectIsRequiredOnExpenseReports');
	print '</td><td class="right">';
	print $form->selectyesno('EXPENSEREPORT_PROJECT_IS_REQUIRED', getDolGlobalString('EXPENSEREPORT_PROJECT_IS_REQUIRED') ? 1 : 0, 1);
	print '</td></tr>';
}

print '<tr class="oddeven"><td>';
print $langs->trans('PrefillExpenseReportDatesWithCurrentMonth');
print '</td><td class="right">';
print $form->selectyesno('EXPENSEREPORT_PREFILL_DATES_WITH_CURRENT_MONTH', getDolGlobalString('EXPENSEREPORT_PREFILL_DATES_WITH_CURRENT_MONTH') ? 1 : 0, 1);
print '</td></tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('ForceExpenseReportsLineAmountsIncludingTaxesOnly');
print '</td><td class="right">';
print $form->selectyesno('EXPENSEREPORT_FORCE_LINE_AMOUNTS_INCLUDING_TAXES_ONLY', getDolGlobalString('EXPENSEREPORT_FORCE_LINE_AMOUNTS_INCLUDING_TAXES_ONLY') ? 1 : 0, 1);
print '</td></tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('BlockExpenseReportLineCreationIfNotBetweenDates');
print '</td><td class="right">';
print $form->selectyesno('EXPENSEREPORT_BLOCK_LINE_CREATION_IF_NOT_BETWEEN_DATES', getDolGlobalString('EXPENSEREPORT_BLOCK_LINE_CREATION_IF_NOT_BETWEEN_DATES') ? 1 : 0, 1);
print '</td></tr>';

print '</table>';

print $form->buttonsSaveCancel("Save", '');

print '</form>';

print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
