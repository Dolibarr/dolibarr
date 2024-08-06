<?php
/* Copyright (C) 2003-2004	Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2005		Eric Seigne					<eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012	Regis Houssin				<regis.houssin@inodbox.com>
 * Copyright (C) 2008		Raphael Bertrand (Resultic)	<raphael.bertrand@resultic.fr>
 * Copyright (C) 2012-2013  Juanjo Menent				<jmenent@2byte.es>
 * Copyright (C) 2014		Teddy Andreotti				<125155@supinfo.com>
 * Copyright (C) 2022		Anthony Berton				<anthony.berton@bb2a.fr>
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
 *      \file       htdocs/admin/invoice.php
 *		\ingroup    invoice
 *		\brief      Page to setup invoice module
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'errors', 'other', 'bills'));

if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$value = GETPOST('value', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ09');	// Used by actions_setmoduleoptions.inc.php

$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');
$type = 'invoice';

$error = 0;


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

if ($action == 'updateMask') {
	$maskconstinvoice = GETPOST('maskconstinvoice', 'aZ09');
	$maskconstreplacement = GETPOST('maskconstreplacement', 'aZ09');
	$maskconstcredit = GETPOST('maskconstcredit', 'aZ09');
	$maskconstdeposit = GETPOST('maskconstdeposit', 'aZ09');
	$maskinvoice = GETPOST('maskinvoice', 'alpha');
	$maskreplacement = GETPOST('maskreplacement', 'alpha');
	$maskcredit = GETPOST('maskcredit', 'alpha');
	$maskdeposit = GETPOST('maskdeposit', 'alpha');
	if ($maskconstinvoice && preg_match('/_MASK_/', $maskconstinvoice)) {
		$res = dolibarr_set_const($db, $maskconstinvoice, $maskinvoice, 'chaine', 0, '', $conf->entity);
	}
	if ($maskconstreplacement && preg_match('/_MASK_/', $maskconstreplacement)) {
		$res = dolibarr_set_const($db, $maskconstreplacement, $maskreplacement, 'chaine', 0, '', $conf->entity);
	}
	if ($maskconstcredit && preg_match('/_MASK_/', $maskconstcredit)) {
		$res = dolibarr_set_const($db, $maskconstcredit, $maskcredit, 'chaine', 0, '', $conf->entity);
	}
	if ($maskconstdeposit && preg_match('/_MASK_/', $maskconstdeposit)) {
		$res = dolibarr_set_const($db, $maskconstdeposit, $maskdeposit, 'chaine', 0, '', $conf->entity);
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

	$facture = new Facture($db);
	$facture->initAsSpecimen();

	// Search template files
	$file = '';
	$classname = '';
	$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
	foreach ($dirmodels as $reldir) {
		$file = dol_buildpath($reldir."core/modules/facture/doc/pdf_".$modele.".modules.php", 0);
		if (file_exists($file)) {
			$classname = "pdf_".$modele;
			break;
		}
	}

	if ($classname !== '') {
		require_once $file;

		$module = new $classname($db);
		'@phan-var-force ModelePDFFactures $module';

		if ($module->write_file($facture, $langs) > 0) {
			header("Location: ".DOL_URL_ROOT."/document.php?modulepart=facture&file=SPECIMEN.pdf");
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
		if ($conf->global->FACTURE_ADDON_PDF == "$value") {
			dolibarr_del_const($db, 'FACTURE_ADDON_PDF', $conf->entity);
		}
	}
} elseif ($action == 'setdoc') {
	// Set default model
	if (dolibarr_set_const($db, "FACTURE_ADDON_PDF", $value, 'chaine', 0, '', $conf->entity)) {
		// La constante qui a ete lue en avant du nouveau set
		// on passe donc par une variable pour avoir un affichage coherent
		$conf->global->FACTURE_ADDON_PDF = $value;
	}

	// On active le modele
	$ret = delDocumentModel($value, $type);
	if ($ret > 0) {
		$ret = addDocumentModel($value, $type, $label, $scandir);
	}
} elseif ($action == 'setmod') {
	// TODO Check if numbering module chosen can ba activated by calling method canBeActivated()

	dolibarr_set_const($db, "FACTURE_ADDON", $value, 'chaine', 0, '', $conf->entity);
} elseif ($action == 'setribchq') {
	$rib = GETPOST('rib', 'alpha');
	$chq = GETPOST('chq', 'alpha');

	$res = dolibarr_set_const($db, "FACTURE_RIB_NUMBER", $rib, 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "FACTURE_CHQ_NUMBER", $chq, 'chaine', 0, '', $conf->entity);

	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
} elseif ($action == 'set_FACTURE_DRAFT_WATERMARK') {
	$draft = GETPOST('FACTURE_DRAFT_WATERMARK', 'alpha');

	$res = dolibarr_set_const($db, "FACTURE_DRAFT_WATERMARK", trim($draft), 'chaine', 0, '', $conf->entity);

	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
} elseif ($action == 'set_INVOICE_FREE_TEXT') {
	$freetext = GETPOST('INVOICE_FREE_TEXT', 'restricthtml'); // No alpha here, we want exact string

	$res = dolibarr_set_const($db, "INVOICE_FREE_TEXT", $freetext, 'chaine', 0, '', $conf->entity);

	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
} elseif ($action == 'setforcedate') {
	$forcedate = GETPOST('forcedate', 'alpha');

	$res = dolibarr_set_const($db, "FAC_FORCE_DATE_VALIDATION", $forcedate, 'chaine', 0, '', $conf->entity);

	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
} elseif ($action == 'setDefaultPDFModulesByType') {
	$invoicetypemodels = GETPOST('invoicetypemodels');

	if (!empty($invoicetypemodels) && is_array($invoicetypemodels)) {
		$error = 0;

		foreach ($invoicetypemodels as $type => $value) {
			$res = dolibarr_set_const($db, 'FACTURE_ADDON_PDF_'.intval($type), $value, 'chaine', 0, '', $conf->entity);
			if (!($res > 0)) {
				$error++;
			}
		}

		if (!$error) {
			setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
		} else {
			setEventMessages($langs->trans("Error"), null, 'errors');
		}
	}
} elseif ($action == 'set_INVOICE_CHECK_POSTERIOR_DATE') {
	$check_posterior_date = GETPOSTINT('INVOICE_CHECK_POSTERIOR_DATE');
	$res = dolibarr_set_const($db, 'INVOICE_CHECK_POSTERIOR_DATE', $check_posterior_date, 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}
} elseif (preg_match('/set_(.*)/', $action, $reg)) {
	$code = $reg[1];
	$value = (GETPOST($code) ? GETPOST($code) : 1);

	$res = dolibarr_set_const($db, $code, $value, 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}

	if ($error) {
		setEventMessages($langs->trans('Error'), null, 'errors');
	} else {
		setEventMessages($langs->trans('SetupSaved'), null, 'mesgs');
		header("Location: " . $_SERVER["PHP_SELF"]);
		exit();
	}
} elseif (preg_match('/del_(.*)/', $action, $reg)) {
	$code = $reg[1];
	$res = dolibarr_del_const($db, $code, $conf->entity);

	if (!($res > 0)) {
		$error++;
	}

	if ($error) {
		setEventMessages($langs->trans('Error'), null, 'errors');
	} else {
		setEventMessages($langs->trans('SetupSaved'), null, 'mesgs');
		header("Location: " . $_SERVER["PHP_SELF"]);
		exit();
	}
}


/*
 * View
 */

$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

llxHeader("", $langs->trans("BillsSetup"), 'EN:Invoice_Configuration|FR:Configuration_module_facture|ES:ConfiguracionFactura', '', 0, 0, '', '', '', 'mod-admin page-invoice');

$form = new Form($db);


$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("BillsSetup"), $linkback, 'title_setup');

$head = invoice_admin_prepare_head();
print dol_get_fiche_head($head, 'general', $langs->trans("Invoices"), -1, 'invoice');

/*
 *  Numbering module
 */

print load_fiche_titre($langs->trans("BillsNumberingModule"), '', '');

print '<div class="div-table-responsive-no-min">';
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
	$dir = dol_buildpath($reldir."core/modules/facture/");
	if (is_dir($dir)) {
		$handle = opendir($dir);
		if (is_resource($handle)) {
			while (($file = readdir($handle)) !== false) {
				if (!is_dir($dir.$file) || (substr($file, 0, 1) != '.' && substr($file, 0, 3) != 'CVS')) {
					$filebis = $file;
					$classname = preg_replace('/\.php$/', '', $file);
					// For compatibility
					if (!is_file($dir.$filebis)) {
						$filebis = $file."/".$file.".modules.php";
						$classname = "mod_facture_".$file;
					}
					// Check if there is a filter on country
					preg_match('/\-(.*)_(.*)$/', $classname, $reg);
					if (!empty($reg[2]) && $reg[2] != strtoupper($mysoc->country_code)) {
						continue;
					}

					$classname = preg_replace('/\-.*$/', '', $classname);
					if (!class_exists($classname) && is_readable($dir.$filebis) && (preg_match('/mod_/', $filebis) || preg_match('/mod_/', $classname)) && substr($filebis, dol_strlen($filebis) - 3, 3) == 'php') {
						// Charging the numbering class
						require_once $dir.$filebis;

						$module = new $classname($db);

						'@phan-var-force ModeleNumRefFactures $module';

						// Show modules according to features level
						if ($module->version == 'development' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 2) {
							continue;
						}
						if ($module->version == 'experimental' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 1) {
							continue;
						}

						if ($module->isEnabled()) {
							print '<tr class="oddeven"><td width="100">';
							echo preg_replace('/\-.*$/', '', preg_replace('/mod_facture_/', '', preg_replace('/\.php$/', '', $file)));
							print "</td><td>\n";

							print $module->info($langs);

							print '</td>';

							// Show example of numbering module
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
							//print "> ".$conf->global->FACTURE_ADDON." - ".$file;
							if ($conf->global->FACTURE_ADDON == $file || getDolGlobalString('FACTURE_ADDON') . '.php' == $file) {
								print img_picto($langs->trans("Activated"), 'switch_on');
							} else {
								print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setmod&token='.newToken().'&value='.preg_replace('/\.php$/', '', $file).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
							}
							print '</td>';

							$facture = new Facture($db);
							$facture->initAsSpecimen();

							// Example for standard invoice
							$htmltooltip = '';
							$htmltooltip .= ''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
							$facture->type = 0;
							$nextval = $module->getNextValue($mysoc, $facture);
							if ("$nextval" != $langs->trans("NotAvailable")) {  // Keep " on nextval
								$htmltooltip .= $langs->trans("NextValueForInvoices").': ';
								if ($nextval) {
									if (preg_match('/^Error/', $nextval) || $nextval == 'NotConfigured') {
										$nextval = $langs->trans($nextval);
									}
									$htmltooltip .= $nextval.'<br>';
								} else {
									$htmltooltip .= $langs->trans($module->error).'<br>';
								}
							}
							// Example for replacement
							$facture->type = 1;
							$nextval = $module->getNextValue($mysoc, $facture);
							if ("$nextval" != $langs->trans("NotAvailable")) {  // Keep " on nextval
								$htmltooltip .= $langs->trans("NextValueForReplacements").': ';
								if ($nextval) {
									if (preg_match('/^Error/', $nextval) || $nextval == 'NotConfigured') {
										$nextval = $langs->trans($nextval);
									}
									$htmltooltip .= $nextval.'<br>';
								} else {
									$htmltooltip .= $langs->trans($module->error).'<br>';
								}
							}

							// Example for credit invoice
							$facture->type = 2;
							$nextval = $module->getNextValue($mysoc, $facture);
							if ("$nextval" != $langs->trans("NotAvailable")) {  // Keep " on nextval
								$htmltooltip .= $langs->trans("NextValueForCreditNotes").': ';
								if ($nextval) {
									if (preg_match('/^Error/', $nextval) || $nextval == 'NotConfigured') {
										$nextval = $langs->trans($nextval);
									}
									$htmltooltip .= $nextval.'<br>';
								} else {
									$htmltooltip .= $langs->trans($module->error).'<br>';
								}
							}
							// Example for deposit invoice
							$facture->type = 3;
							$nextval = $module->getNextValue($mysoc, $facture);
							if ("$nextval" != $langs->trans("NotAvailable")) {  // Keep " on nextval
								$htmltooltip .= $langs->trans("NextValueForDeposit").': ';
								if ($nextval) {
									if (preg_match('/^Error/', $nextval) || $nextval == 'NotConfigured') {
										$nextval = $langs->trans($nextval);
									}
									$htmltooltip .= $nextval;
								} else {
									$htmltooltip .= $langs->trans($module->error);
								}
							}

							print '<td class="center">';
							print $form->textwithpicto('', $htmltooltip, 1, 0);

							if (getDolGlobalString('FACTURE_ADDON') . '.php' == $file) {  // If module is the one used, we show existing errors
								if (!empty($module->error)) {
									dol_htmloutput_mesg($module->error, '', 'error', 1);
								}
							}

							print '</td>';

							print "</tr>\n";
						}
					}
				}
			}
			closedir($handle);
		}
	}
}

print '</table>';
print '</div>';


/*
 *  Document templates generators
 */

print '<br>';
print load_fiche_titre($langs->trans("BillsPDFModules"), '', '');

// Load array def with activated templates
$type = 'invoice';
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
print '<td class="center" width="60">'.$langs->trans("Status").'</td>';
print '<td class="center" width="60">'.$langs->trans("Default").'</td>';
print '<td class="center" width="32">'.$langs->trans("ShortInfo").'</td>';
print '<td class="center" width="32">'.$langs->trans("Preview").'</td>';
print "</tr>\n";

clearstatcache();

$activatedModels = array();

foreach ($dirmodels as $reldir) {
	foreach (array('', '/doc') as $valdir) {
		$realpath = $reldir."core/modules/facture".$valdir;
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

							'@phan-var-force ModelePDFFactures $module';

							$modulequalified = 1;
							if ($module->version == 'development' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 2) {
								$modulequalified = 0;
							}
							if ($module->version == 'experimental' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 1) {
								$modulequalified = 0;
							}
							if ($module->version == 'disabled') {
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
									print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=del&token='.newToken().'&value='.urlencode($name).'">';
									print img_picto($langs->trans("Enabled"), 'switch_on');
									print '</a>';
									print '</td>';
								} else {
									print '<td class="center">'."\n";
									print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=set&token='.newToken().'&value='.urlencode($name).'&scan_dir='.urlencode($module->scandir).'&label='.urlencode($module->name).'">'.img_picto($langs->trans("SetAsDefault"), 'switch_off').'</a>';
									print "</td>";
								}

								// Default
								print '<td class="center">';
								if ($conf->global->FACTURE_ADDON_PDF == "$name") {
									print img_picto($langs->trans("Default"), 'on');
								} else {
									print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setdoc&token='.newToken().'&value='.urlencode($name).'&scan_dir='.urlencode($module->scandir).'&label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("SetAsDefault"), 'off').'</a>';
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
								$htmltooltip .= '<br>'.$langs->trans("Discounts").': '.yn($module->option_escompte, 1, 1);
								$htmltooltip .= '<br>'.$langs->trans("CreditNote").': '.yn($module->option_credit_note, 1, 1);
								$htmltooltip .= '<br>'.$langs->trans("MultiLanguage").': '.yn($module->option_multilang, 1, 1);
								$htmltooltip .= '<br>'.$langs->trans("WatermarkOnDraftInvoices").': '.yn($module->option_draft_watermark, 1, 1);


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

if (getDolGlobalString('INVOICE_USE_DEFAULT_DOCUMENT')) { // Hidden conf
	/*
	 *  Document templates generators
	 */
	print '<br>';
	print load_fiche_titre($langs->trans("BillsPDFModulesAccordindToInvoiceType"), '', '');

	print '<form action="'.$_SERVER["PHP_SELF"].'#default-pdf-modules-by-type-table" method="POST">';
	print '<input type="hidden" name="token" value="'.newToken().'" />';
	print '<input type="hidden" name="action" value="setDefaultPDFModulesByType" >';
	print '<input type="hidden" name="page_y" value="" />';

	print '<div class="div-table-responsive-no-min">';
	print '<table id="default-pdf-modules-by-type-table" class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Type").'</td>';
	print '<td>'.$langs->trans("Name").'</td>';
	print '<td class="right"><input type="submit" class="button button-edit reposition" value="'.$langs->trans("Modify").'"></td>';
	print "</tr>\n";

	$listtype = array(
		Facture::TYPE_STANDARD => $langs->trans("InvoiceStandard"),
		Facture::TYPE_REPLACEMENT => $langs->trans("InvoiceReplacement"),
		Facture::TYPE_CREDIT_NOTE => $langs->trans("InvoiceAvoir"),
		Facture::TYPE_DEPOSIT => $langs->trans("InvoiceDeposit"),
	);
	if (getDolGlobalInt('INVOICE_USE_SITUATION')) {
		$listtype[Facture::TYPE_SITUATION] = $langs->trans("InvoiceSituation");
	}

	foreach ($listtype as $type => $trans) {
		$thisTypeConfName = 'FACTURE_ADDON_PDF_'.$type;
		$current = getDolGlobalString($thisTypeConfName, getDolGlobalString('FACTURE_ADDON_PDF'));
		print '<tr >';
		print '<td>'.$trans.'</td>';
		print '<td colspan="2" >'.$form->selectarray('invoicetypemodels['.$type.']', ModelePDFFactures::liste_modeles($db), $current, 0, 0, 0).'</td>';
		print "</tr>\n";
	}

	print '</table>';
	print '</div>';

	print "</form>";
}

/*
 *  Payment modes
 */
print '<br>';
print load_fiche_titre($langs->trans("SuggestedPaymentModesIfNotDefinedInInvoice"), '', '');

print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.newToken().'" />';
print '<input type="hidden" name="page_y" value="" />';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<td>';
print '<input type="hidden" name="action" value="setribchq">';
print $langs->trans("PaymentMode").'</td>';
print '<td class="right"><input type="submit" class="button button-edit reposition" value="'.$langs->trans("Modify").'"></td>';
print "</tr>\n";

print '<tr class="oddeven">';
print "<td>".$langs->trans("SuggestPaymentByRIBOnAccount")."</td>";
print "<td>";
if (isModEnabled('bank')) {
	$sql = "SELECT rowid, label, clos";
	$sql .= " FROM ".MAIN_DB_PREFIX."bank_account";
	$sql .= " WHERE courant = 1";
	$sql .= " AND entity IN (".getEntity('bank_account').")";
	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		if ($num > 0) {
			print '<select name="rib" class="flat" id="rib">';
			print '<option value="0">'.$langs->trans("DoNotSuggestPaymentMode").'</option>';
			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				print '<option value="'.$obj->rowid.'"';
				print getDolGlobalString('FACTURE_RIB_NUMBER') == $obj->rowid ? ' selected' : '';
				if (!empty($obj->clos)) {
					print ' disabled';
				}
				print '>'.dol_escape_htmltag($obj->label).'</option>';

				$i++;
			}
			print "</select>";
			print ajax_combobox("rib");
		} else {
			print '<span class="opacitymedium">'.$langs->trans("NoActiveBankAccountDefined").'</span>';
		}
	}
} else {
	print $langs->trans("BankModuleNotActive");
}
print "</td></tr>";

$FACTURE_CHQ_NUMBER = getDolGlobalInt('FACTURE_CHQ_NUMBER');

print '<tr class="oddeven">';
print "<td>".$langs->trans("SuggestPaymentByChequeToAddress")."</td>";
print "<td>";
print '<select class="flat" name="chq" id="chq">';
print '<option value="0">'.$langs->trans("DoNotSuggestPaymentMode").'</option>';
print '<option value="-1"'.($FACTURE_CHQ_NUMBER == -1 ? ' selected' : '').'>'.$langs->trans("MenuCompanySetup").' ('.($mysoc->name ? $mysoc->name : $langs->trans("NotDefined")).')</option>';

$sql = "SELECT rowid, label";
$sql .= " FROM ".MAIN_DB_PREFIX."bank_account";
$sql .= " WHERE clos = 0";
$sql .= " AND courant = 1";
$sql .= " AND entity IN (".getEntity('bank_account').")";

$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;
	while ($i < $num) {
		$row = $db->fetch_row($resql);

		print '<option value="'.$row[0].'"';
		print $FACTURE_CHQ_NUMBER == $row[0] ? ' selected' : '';
		print '>'.$langs->trans("OwnerOfBankAccount", $row[1]).'</option>';

		$i++;
	}
}
print "</select>";
print ajax_combobox("chq", array(), 0, 0, 'resolve', -2);

print "</td></tr>";
print "</table>";
print '</div>';

print "</form>";


print "<br>";
print load_fiche_titre($langs->trans("OtherOptions"), '', '');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td>';
print '<td class="center" width="60">'.$langs->trans("Value").'</td>';
print '<td width="80">&nbsp;</td>';
print "</tr>\n";

// Force date validation
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.newToken().'" />';
print '<input type="hidden" name="action" value="setforcedate" />';
print '<input type="hidden" name="page_y" value="" />';
print '<tr class="oddeven"><td>';
print $langs->trans("ForceInvoiceDate");
print '</td><td width="60" class="center">';
print $form->selectyesno("forcedate", getDolGlobalInt('FAC_FORCE_DATE_VALIDATION', 0), 1);
print '</td><td class="right">';
print '<input type="submit" class="button button-edit reposition" value="'.$langs->trans("Modify").'" />';
print "</td></tr>\n";
print '</form>';

$substitutionarray = pdf_getSubstitutionArray($langs, null, null, 2);
$substitutionarray['__(AnyTranslationKey)__'] = $langs->trans("Translation");
$htmltext = '<i>'.$langs->trans("AvailableVariables").':<br>';
foreach ($substitutionarray as $key => $val) {
	$htmltext .= $key.'<br>';
}
$htmltext .= '</i>';

print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.newToken().'" />';
print '<input type="hidden" name="action" value="set_INVOICE_FREE_TEXT" />';
print '<input type="hidden" name="page_y" value="" />';
print '<tr class="oddeven"><td colspan="2">';
print $form->textwithpicto($langs->trans("FreeLegalTextOnInvoices"), $langs->trans("AddCRIfTooLong").'<br><br>'.$htmltext, 1, 'help', '', 0, 2, 'freetexttooltip').'<br>';
$variablename = 'INVOICE_FREE_TEXT';
if (!getDolGlobalString('PDF_ALLOW_HTML_FOR_FREE_TEXT')) {
	print '<textarea name="'.$variablename.'" class="flat" cols="120">'.getDolGlobalString($variablename).'</textarea>';
} else {
	include_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$doleditor = new DolEditor($variablename, getDolGlobalString($variablename), '', 80, 'dolibarr_notes');
	print $doleditor->Create();
}
print '</td><td class="right">';
print '<input type="submit" class="button button-edit reposition" value="'.$langs->trans("Modify").'" />';
print "</td></tr>\n";
print '</form>';


print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.newToken().'" />';
print '<input type="hidden" name="action" value="set_FACTURE_DRAFT_WATERMARK" />';
print '<input type="hidden" name="page_y" value="" />';
print '<tr class="oddeven"><td>';
print $form->textwithpicto($langs->trans("WatermarkOnDraftBill"), $htmltext, 1, 'help', '', 0, 2, 'watermarktooltip').'<br>';
print '</td>';
print '<td><input class="flat minwidth200imp" type="text" name="FACTURE_DRAFT_WATERMARK" value="'.dol_escape_htmltag(getDolGlobalString('FACTURE_DRAFT_WATERMARK')).'">';
print '</td><td class="right">';
print '<input type="submit" class="button button-edit reposition" value="'.$langs->trans("Modify").'" />';
print "</td></tr>\n";
print '</form>';


print '<tr class="oddeven"><td>'.$langs->trans("InvoiceCheckPosteriorDate"). '&nbsp;' ;
print $form->textwithpicto('', $langs->trans("InvoiceCheckPosteriorDateHelp"), 1, 'help') . '</td>';
print '<td class="left" colspan="2">';
print ajax_constantonoff('INVOICE_CHECK_POSTERIOR_DATE');
print '</td></tr>';


// Allow external download
print '<tr class="oddeven">';
print '<td>'.$langs->trans("AllowExternalDownload").'</td>';
print '<td class="left" colspan="2">';
print ajax_constantonoff('INVOICE_ALLOW_EXTERNAL_DOWNLOAD', array(), null, 0, 0, 0, 2, 0, 1);
print '</td></tr>';

print '</table>';
print '</div>';

/*
 *  Repertoire
 */
print '<br>';
print load_fiche_titre($langs->trans("PathToDocuments"), '', '');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">'."\n";
print '<tr class="liste_titre">'."\n";
print '<td>'.$langs->trans("Name").'</td>'."\n";
print '<td>'.$langs->trans("Value").'</td>'."\n";
print "</tr>\n";
print '<tr class="oddeven">'."\n";
print '<td width="140">'.$langs->trans("PathDirectory").'</td>'."\n";
print '<td>'.$conf->facture->dir_output.'</td>'."\n";
print '</tr>'."\n";
print "</table>\n";
print "</div>\n";

/*
 * Notifications
 */
print '<br>';
print load_fiche_titre($langs->trans("Notifications"), '', '');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td class="center" width="60"></td>';
print '<td width="80">&nbsp;</td>';
print "</tr>\n";
print '<tr class="oddeven"><td colspan="2">';
print $langs->trans("YouMayFindNotificationsFeaturesIntoModuleNotification").'<br>';
print '</td><td class="right">';
print "</td></tr>\n";
print '</table>';
print "</div>\n";


print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
