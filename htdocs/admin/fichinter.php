<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville         <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur          <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio          <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier               <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2014 Regis Houssin                <regis.houssin@inodbox.com>
 * Copyright (C) 2008      Raphael Bertrand (Resultic)  <raphael.bertrand@resultic.fr>
 * Copyright (C) 2011-2013 Juanjo Menent			    <jmenent@2byte.es>
 * Copyright (C) 2011-2018 Philippe Grand			    <philippe.grand@atoo-net.com>
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
 *	\file       htdocs/admin/fichinter.php
 *	\ingroup    fichinter
 *	\brief      Setup page of module Interventions
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/fichinter.lib.php';
require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'errors', 'interventions', 'other'));

if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$value = GETPOST('value', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ09');	// Used by actions_setmoduleoptions.inc.php

$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');
$type = 'ficheinter';


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

if ($action == 'updateMask') {
	$maskconst = GETPOST('maskconst', 'aZ09');
	$maskvalue = GETPOST('maskvalue', 'alpha');

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
} elseif ($action == 'specimen') { // For Intervention card
	$modele = GETPOST('module', 'alpha');

	$inter = new Fichinter($db);
	$inter->initAsSpecimen();

	// Search template files
	$file = '';
	$classname = '';
	$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
	foreach ($dirmodels as $reldir) {
		$file = dol_buildpath($reldir."core/modules/fichinter/doc/pdf_".$modele.".modules.php", 0);
		if (file_exists($file)) {
			$classname = "pdf_".$modele;
			break;
		}
	}

	if ($classname !== '') {
		require_once $file;

		$module = new $classname($db);
		'@phan-var-force ModelePDFFicheinter $module';

		if ($module->write_file($inter, $langs) > 0) {
			header("Location: ".DOL_URL_ROOT."/document.php?modulepart=ficheinter&file=SPECIMEN.pdf");
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
		if ($conf->global->FICHEINTER_ADDON_PDF == "$value") {
			dolibarr_del_const($db, 'FICHEINTER_ADDON_PDF', $conf->entity);
		}
	}
} elseif ($action == 'setdoc') {
	// Set default model
	if (dolibarr_set_const($db, "FICHEINTER_ADDON_PDF", $value, 'chaine', 0, '', $conf->entity)) {
		// La constante qui a ete lue en avant du nouveau set
		// on passe donc par une variable pour avoir un affichage coherent
		$conf->global->FICHEINTER_ADDON_PDF = $value;
	}

	// On active le modele
	$ret = delDocumentModel($value, $type);
	if ($ret > 0) {
		$ret = addDocumentModel($value, $type, $label, $scandir);
	}
} elseif ($action == 'setmod') {
	// TODO Verify if the chosen numbering module can be activated
	// by calling method canBeActivated

	dolibarr_set_const($db, "FICHEINTER_ADDON", $value, 'chaine', 0, '', $conf->entity);
} elseif ($action == 'set_FICHINTER_FREE_TEXT') {
	$freetext = GETPOST('FICHINTER_FREE_TEXT', 'restricthtml'); // No alpha here, we want exact string
	$res = dolibarr_set_const($db, "FICHINTER_FREE_TEXT", $freetext, 'chaine', 0, '', $conf->entity);

	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
} elseif ($action == 'set_FICHINTER_DRAFT_WATERMARK') {
	$draft = GETPOST('FICHINTER_DRAFT_WATERMARK', 'alpha');
	$res = dolibarr_set_const($db, "FICHINTER_DRAFT_WATERMARK", trim($draft), 'chaine', 0, '', $conf->entity);

	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
} elseif ($action == 'set_FICHINTER_PRINT_PRODUCTS') {
	$val = GETPOST('FICHINTER_PRINT_PRODUCTS', 'alpha');
	$res = dolibarr_set_const($db, "FICHINTER_PRINT_PRODUCTS", ($val == 'on' ? 1 : 0), 'bool', 0, '', $conf->entity);

	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
} elseif ($action == 'set_FICHINTER_USE_SERVICE_DURATION') {
	$val = GETPOST('FICHINTER_USE_SERVICE_DURATION', 'alpha');
	$res = dolibarr_set_const($db, "FICHINTER_USE_SERVICE_DURATION", ($val == 'on' ? 1 : 0), 'bool', 0, '', $conf->entity);

	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
} elseif ($action == 'set_FICHINTER_WITHOUT_DURATION') {
	$val = GETPOST('FICHINTER_WITHOUT_DURATION', 'alpha');
	$res = dolibarr_set_const($db, "FICHINTER_WITHOUT_DURATION", ($val == 'on' ? 1 : 0), 'bool', 0, '', $conf->entity);

	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
} elseif ($action == 'set_FICHINTER_DATE_WITHOUT_HOUR') {
	$val = GETPOST('FICHINTER_DATE_WITHOUT_HOUR', 'alpha');
	$res = dolibarr_set_const($db, "FICHINTER_DATE_WITHOUT_HOUR", ($val == 'on' ? 1 : 0), 'bool', 0, '', $conf->entity);

	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
} elseif ($action == "set_FICHINTER_ALLOW_ONLINE_SIGN") {
	$val = GETPOST('FICHINTER_ALLOW_ONLINE_SIGN', 'alpha');
	$res = dolibarr_set_const($db, "FICHINTER_ALLOW_ONLINE_SIGN", ($val == 'on' ? 1 : 0), 'bool', 0, '', $conf->entity);

	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
} elseif ($action == "set_FICHINTER_ALLOW_EXTERNAL_DOWNLOAD") {
	$val = GETPOST('FICHINTER_ALLOW_EXTERNAL_DOWNLOAD', 'alpha');
	$res = dolibarr_set_const($db, "FICHINTER_ALLOW_EXTERNAL_DOWNLOAD", ($val == 'on' ? 1 : 0), 'bool', 0, '', $conf->entity);

	if (!($res > 0)) {
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

llxHeader('', '', '', '', 0, 0, '', '', '', 'mod-admin page-fichinter');

$form = new Form($db);

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("InterventionsSetup"), $linkback, 'title_setup');


$head = fichinter_admin_prepare_head();

print dol_get_fiche_head($head, 'ficheinter', $langs->trans("Interventions"), -1, 'intervention');

// Interventions numbering model

print load_fiche_titre($langs->trans("FicheinterNumberingModules"), '', '');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td width="100">'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td>'.$langs->trans("Example").'</td>';
print '<td align="center" width="60">'.$langs->trans("Status").'</td>';
print '<td align="center" width="80">'.$langs->trans("ShortInfo").'</td>';
print "</tr>\n";

clearstatcache();

foreach ($dirmodels as $reldir) {
	$dir = dol_buildpath($reldir."core/modules/fichinter/");

	if (is_dir($dir)) {
		$handle = opendir($dir);
		if (is_resource($handle)) {
			while (($file = readdir($handle)) !== false) {
				if (preg_match('/^(mod_.*)\.php$/i', $file, $reg)) {
					$file = $reg[1];
					$classname = substr($file, 4);

					require_once $dir.$file.'.php';

					$module = new $file();

					'@phan-var-force ModeleNumRefFicheinter $module';

					if ($module->isEnabled()) {
						// Show modules according to features level
						if ($module->version == 'development' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 2) {
							continue;
						}
						if ($module->version == 'experimental' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 1) {
							continue;
						}


						print '<tr class="oddeven"><td>'.$module->getName($langs)."</td><td>\n";
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
						if ($conf->global->FICHEINTER_ADDON == $classname) {
							print img_picto($langs->trans("Activated"), 'switch_on');
						} else {
							print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setmod&token='.newToken().'&value='.urlencode($classname).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
						}
						print '</td>';

						$ficheinter = new Fichinter($db);
						$ficheinter->initAsSpecimen();

						// Info
						$htmltooltip = '';
						$htmltooltip .= ''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
						$nextval = $module->getNextValue($mysoc, $ficheinter);
						if ("$nextval" != $langs->trans("NotAvailable")) {   // Keep " on nextval
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
 *  Documents models for Interventions
 */

print load_fiche_titre($langs->trans("TemplatePDFInterventions"), '', '');

// Defini tableau def des modeles
$type = 'ficheinter';
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
	$realpath = $reldir."core/modules/fichinter/doc";
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

						'@phan-var-force ModelePDFFicheinter $module';

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
								print "<td align=\"center\">\n";
								print '<a href="'.$_SERVER["PHP_SELF"].'?action=del&token='.newToken().'&value='.urlencode($name).'&scan_dir='.urlencode($module->scandir).'&label='.urlencode($module->name).'">';
								print img_picto($langs->trans("Enabled"), 'switch_on');
								print '</a>';
								print "</td>";
							} else {
								print "<td align=\"center\">\n";
								print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&token='.newToken().'&value='.urlencode($name).'&scan_dir='.urlencode($module->scandir).'&label='.urlencode($module->name).'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
								print "</td>";
							}

							// Default
							print "<td align=\"center\">";
							if ($conf->global->FICHEINTER_ADDON_PDF == "$name") {
								print img_picto($langs->trans("Default"), 'on');
							} else {
								print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&token='.newToken().'&value='.urlencode($name).'&scan_dir='.urlencode($module->scandir).'&label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
							}
							print '</td>';

							// Info
							$htmltooltip = ''.$langs->trans("Name").': '.$module->name;
							$htmltooltip .= '<br>'.$langs->trans("Type").': '.($module->type ? $module->type : $langs->trans("Unknown"));
							$htmltooltip .= '<br>'.$langs->trans("Width").'/'.$langs->trans("Height").': '.$module->page_largeur.'/'.$module->page_hauteur;
							$htmltooltip .= '<br>'.$langs->trans("Path").': '.preg_replace('/^\//', '', $realpath).'/'.$file;

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
print '</div>';
print "<br>";

/*
 * Other options
 */

print load_fiche_titre($langs->trans("OtherOptions"), '', '');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td align="center" width="60">'.$langs->trans("Value").'</td>';
print "<td>&nbsp;</td>\n";
print "</tr>\n";

$substitutionarray = pdf_getSubstitutionArray($langs, null, null, 2);
$substitutionarray['__(AnyTranslationKey)__'] = $langs->trans("Translation");
$htmltext = '<i>'.$langs->trans("AvailableVariables").':<br>';
foreach ($substitutionarray as $key => $val) {
	$htmltext .= $key.'<br>';
}
$htmltext .= '</i>';

print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="set_FICHINTER_FREE_TEXT">';
print '<tr class="oddeven"><td colspan="2">';
print $form->textwithpicto($langs->trans("FreeLegalTextOnInterventions"), $langs->trans("AddCRIfTooLong").'<br><br>'.$htmltext, 1, 'help', '', 0, 2, 'freetexttooltip').'<br>';
$variablename = 'FICHINTER_FREE_TEXT';
if (!getDolGlobalString('PDF_ALLOW_HTML_FOR_FREE_TEXT')) {
	print '<textarea name="'.$variablename.'" class="flat" cols="120">'.getDolGlobalString($variablename).'</textarea>';
} else {
	include_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$doleditor = new DolEditor($variablename, getDolGlobalString($variablename), '', 80, 'dolibarr_notes');
	print $doleditor->Create();
}
print '</td><td class="right">';
print '<input type="submit" class="button button-edit" value="'.$langs->trans("Modify").'">';
print "</td></tr>\n";
print '</form>';

//Use draft Watermark
print "<form method=\"post\" action=\"".$_SERVER["PHP_SELF"]."\">";
print '<input type="hidden" name="token" value="'.newToken().'">';
print "<input type=\"hidden\" name=\"action\" value=\"set_FICHINTER_DRAFT_WATERMARK\">";
print '<tr class="oddeven"><td>';
print $form->textwithpicto($langs->trans("WatermarkOnDraftInterventionCards"), $htmltext, 1, 'help', '', 0, 2, 'watermarktooltip').'<br>';
print '</td><td>';
print '<input class="flat minwidth200" type="text" name="FICHINTER_DRAFT_WATERMARK" value="'.dol_escape_htmltag(getDolGlobalString('FICHINTER_DRAFT_WATERMARK')).'">';
print '</td><td class="right">';
print '<input type="submit" class="button button-edit" value="'.$langs->trans("Modify").'">';
print "</td></tr>\n";
print '</form>';
// print products on fichinter
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="set_FICHINTER_PRINT_PRODUCTS">';
print '<tr class="oddeven"><td>';
print $langs->trans("PrintProductsOnFichinter").' ('.$langs->trans("PrintProductsOnFichinterDetails").')</td>';
print '<td align="center"><input type="checkbox" name="FICHINTER_PRINT_PRODUCTS" ';
if (getDolGlobalString("FICHINTER_PRINT_PRODUCTS")) {
	print 'checked ';
}
print '/>';
print '</td><td class="right">';
print '<input type="submit" class="button button-edit" value="'.$langs->trans("Modify").'">';
print "</td></tr>\n";
print '</form>';
// Use services duration
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="set_FICHINTER_USE_SERVICE_DURATION">';
print '<tr class="oddeven">';
print '<td>';
print $langs->trans("UseServicesDurationOnFichinter");
print '</td>';
print '<td class="center">';
print '<input type="checkbox" name="FICHINTER_USE_SERVICE_DURATION"'.(getDolGlobalString("FICHINTER_USE_SERVICE_DURATION") ? ' checked' : '').'>';
print '</td>';
print '<td class="right">';
print '<input type="submit" class="button button-edit" value="'.$langs->trans("Modify").'">';
print '</td>';
print '</tr>';
print '</form>';
// Use duration
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="set_FICHINTER_WITHOUT_DURATION">';
print '<tr class="oddeven">';
print '<td>';
print $langs->trans("UseDurationOnFichinter");
print '</td>';
print '<td class="center">';
print '<input type="checkbox" name="FICHINTER_WITHOUT_DURATION"'.(getDolGlobalString("FICHINTER_WITHOUT_DURATION") ? ' checked' : '').'>';
print '</td>';
print '<td class="right">';
print '<input type="submit" class="button button-edit" value="'.$langs->trans("Modify").'">';
print '</td>';
print '</tr>';
print '</form>';
// use date without hour
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="set_FICHINTER_DATE_WITHOUT_HOUR">';
print '<tr class="oddeven">';
print '<td>';
print $langs->trans("UseDateWithoutHourOnFichinter");
print '</td>';
print '<td class="center">';
print '<input type="checkbox" name="FICHINTER_DATE_WITHOUT_HOUR"'.(getDolGlobalString("FICHINTER_DATE_WITHOUT_HOUR") ? ' checked' : '').'>';
print '</td>';
print '<td class="right">';
print '<input type="submit" class="button button-edit" value="'.$langs->trans("Modify").'">';
print '</td>';
print '</tr>';
print '</form>';
// Allow online signing
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="set_FICHINTER_ALLOW_ONLINE_SIGN">';
print '<tr class="oddeven">';
print '<td>';
print $langs->trans("AllowOnlineSign");
print '</td>';
print '<td class="center">';
print '<input type="checkbox" name="FICHINTER_ALLOW_ONLINE_SIGN"'.(getDolGlobalString("FICHINTER_ALLOW_ONLINE_SIGN") ? ' checked' : '').'>';
print '</td>';
print '<td class="right">';
print '<input type="submit" class="button button-edit" value="'.$langs->trans("Modify").'">';
print '</td>';
print '</tr>';
print '</form>';
// Allow external download
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="set_FICHINTER_ALLOW_EXTERNAL_DOWNLOAD">';
print '<tr class="oddeven">';
print '<td>';
print $langs->trans("AllowExternalDownload");
print '</td>';
print '<td class="center">';
print '<input type="checkbox" name="FICHINTER_ALLOW_EXTERNAL_DOWNLOAD"'.(getDolGlobalString("FICHINTER_ALLOW_EXTERNAL_DOWNLOAD") ? ' checked' : '').'>';
print '</td>';
print '<td class="right">';
print '<input type="submit" class="button button-edit" value="'.$langs->trans("Modify").'">';
print '</td>';
print '</tr>';
print '</form>';


print '</table>';
print '</div>';

print '<br>';

// End of page
llxFooter();
$db->close();
