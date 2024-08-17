<?php
/* Copyright (C) 2015  Juanjo Menent				<jmenent@2byte.es>
 * Copyright (C) 2016  Laurent Destailleur          <eldy@users.sourceforge.net>
 * Copyright (C) 2020  Maxime DEMAREST              <maxime@indelog.fr>
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
 *      \file       htdocs/admin/supplier_payment.php
 *		\ingroup    supplier
 *		\brief      Page to setup supplier invoices payments
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/fourn.lib.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php';

// Load translation files required by the page
$langs->loadLangs(array("admin", "errors", "other", "bills", "orders"));

if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$value = GETPOST('value', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ09');	// Used by actions_setmoduleoptions.inc.php

$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scandir', 'alpha');
$type = 'supplier_payment';


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

if ($action == 'updateMask') {
	$maskconstsupplierpayment = GETPOST('maskconstsupplierpayment', 'aZ09');
	$masksupplierpayment = GETPOST('masksupplierpayment', 'alpha');
	if ($maskconstsupplierpayment && preg_match('/_MASK$/', $maskconstsupplierpayment)) {
		$res = dolibarr_set_const($db, $maskconstsupplierpayment, $masksupplierpayment, 'chaine', 0, '', $conf->entity);
	}

	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
} elseif ($action == 'setmod') {
	dolibarr_set_const($db, "SUPPLIER_PAYMENT_ADDON", $value, 'chaine', 0, '', $conf->entity);
} elseif ($action == 'set') {
	// Activate a model
	$ret = addDocumentModel($value, $type, $label, $scandir);
} elseif ($action == 'del') {
	$ret = delDocumentModel($value, $type);
	if ($ret > 0) {
		if (getDolGlobalString("FACTURE_ADDON_PDF") == "$value") {
			dolibarr_del_const($db, 'SUPPLIER_PAYMENT_ADDON_PDF', $conf->entity);
		}
	}
} elseif ($action == 'setdoc') {
	// Set default model
	if (dolibarr_set_const($db, "SUPPLIER_PAYMENT_ADDON_PDF", $value, 'chaine', 0, '', $conf->entity)) {
		// La constante qui a ete lue en avant du nouveau set
		// on passe donc par une variable pour avoir un affichage coherent
		$conf->global->FACTURE_ADDON_PDF = $value;
	}

	// On active le modele
	$ret = delDocumentModel($value, $type);
	if ($ret > 0) {
		$ret = addDocumentModel($value, $type, $label, $scandir);
	}
} elseif ($action == 'unsetdoc') {
	dolibarr_del_const($db, "SUPPLIER_PAYMENT_ADDON_PDF", $conf->entity);
} elseif ($action == 'specimen') {
	$modele = GETPOST('module', 'alpha');

	$paiementFourn = new PaiementFourn($db);
	$paiementFourn->initAsSpecimen();

	// Search template files
	$file = '';
	$classname = '';
	$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
	foreach ($dirmodels as $reldir) {
		$file = dol_buildpath($reldir."core/modules/supplier_payment/doc/pdf_".$modele.".modules.php", 0);
		if (file_exists($file)) {
			$classname = "pdf_".$modele;
			break;
		}
	}

	if ($classname !== '') {
		require_once $file;

		$module = new $classname($db);
		'@phan-var-force ModelePDFSuppliersPayments $module';

		if ($module->write_file($paiementFourn, $langs) > 0) {
			header("Location: ".DOL_URL_ROOT."/document.php?modulepart=supplier_payment&file=SPECIMEN.pdf");
			return;
		} else {
			setEventMessages($module->error, $module->errors, 'errors');
			dol_syslog($module->error, LOG_ERR);
		}
	} else {
		setEventMessages($langs->trans("ErrorModuleNotFound"), null, 'errors');
		dol_syslog($langs->trans("ErrorModuleNotFound"), LOG_ERR);
	}
} elseif ($action == 'setparams') {
	$res = dolibarr_set_const($db, "PAYMENTS_FOURN_REPORT_GROUP_BY_MOD", (string) GETPOSTINT('PAYMENTS_FOURN_REPORT_GROUP_BY_MOD'), 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}

	if ($error) {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	}
}

/*
 * View
 */

$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

llxHeader('', $langs->trans("SupplierPaymentSetup"), 'EN:Supplier_Payment_Configuration|FR:Configuration_module_paiement_fournisseur', '', 0, 0, '', '', '', 'mod-admin page-supplier_payment');

$form = new Form($db);


$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("SupplierPaymentSetup"), $linkback, 'title_setup');

print "<br>";

$head = supplierorder_admin_prepare_head();
print dol_get_fiche_head($head, 'supplierpayment', $langs->trans("Suppliers"), -1, 'company');

/*
 *  Numbering module
 */

if (!getDolGlobalString('SUPPLIER_PAYMENT_ADDON')) {
	$conf->global->SUPPLIER_PAYMENT_ADDON = 'mod_supplier_payment_bronan';
}

print load_fiche_titre($langs->trans("PaymentsNumberingModule"), '', '');

// Load array def with activated templates
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
print '<td class="nowrap">'.$langs->trans("Example").'</td>';
print '<td align="center" width="60">'.$langs->trans("Status").'</td>';
print '<td align="center" width="16">'.$langs->trans("ShortInfo").'</td>';
print '</tr>'."\n";

clearstatcache();

foreach ($dirmodels as $reldir) {
	$dir = dol_buildpath($reldir."core/modules/supplier_payment/");
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
						$classname = "mod_supplier_payment_".$file;
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

						// Show modules according to features level
						if ($module->version == 'development' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 2) {
							continue;
						}
						if ($module->version == 'experimental' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 1) {
							continue;
						}

						if ($module->isEnabled()) {
							print '<tr class="oddeven"><td width="100">';
							echo preg_replace('/\-.*$/', '', preg_replace('/mod_supplier_payment_/', '', preg_replace('/\.php$/', '', $file)));
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
							//print "> ".$conf->global->SUPPLIER_PAYMENT_ADDON." - ".$file;
							if ($conf->global->SUPPLIER_PAYMENT_ADDON == $file || getDolGlobalString('SUPPLIER_PAYMENT_ADDON') . '.php' == $file) {
								print img_picto($langs->trans("Activated"), 'switch_on');
							} else {
								print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setmod&token='.newToken().'&value='.preg_replace('/\.php$/', '', $file).(!empty($module->scandir) ? '&scandir='.$module->scandir : '').'&label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
							}
							print '</td>';

							$payment = new PaiementFourn($db);
							$payment->initAsSpecimen();

							// Example
							$htmltooltip = '';
							$htmltooltip .= ''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
							$nextval = $module->getNextValue($mysoc, $payment);
							if ("$nextval" != $langs->trans("NotAvailable")) {  // Keep " on nextval
								$htmltooltip .= $langs->trans("NextValue").': ';
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

							if (getDolGlobalString("PAYMENT_ADDON").'.php' == $file) {  // If module is the one used, we show existing errors
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


/*
 *  Document templates generators
 */
print '<br>';
print load_fiche_titre($langs->trans("PaymentsPDFModules"), '', '');

print '<table class="noborder centpercent">'."\n";
print '<tr class="liste_titre">'."\n";
print '<td width="100">'.$langs->trans("Name").'</td>'."\n";
print '<td>'.$langs->trans("Description").'</td>'."\n";
print '<td align="center" width="60">'.$langs->trans("Status").'</td>'."\n";
print '<td align="center" width="60">'.$langs->trans("Default").'</td>'."\n";
print '<td align="center" width="40">'.$langs->trans("ShortInfo").'</td>';
print '<td align="center" width="40">'.$langs->trans("Preview").'</td>';
print '</tr>'."\n";

clearstatcache();

foreach ($dirmodels as $reldir) {
	$realpath = $reldir."core/modules/supplier_payment/doc";
	$dir = dol_buildpath($realpath);

	if (is_dir($dir)) {
		$handle = opendir($dir);


		if (is_resource($handle)) {
			while (($file = readdir($handle)) !== false) {
				if (preg_match('/\.modules\.php$/i', $file) && preg_match('/^(pdf_|doc_)/', $file)) {
					$name = substr($file, 4, dol_strlen($file) - 16);
					$classname = substr($file, 0, dol_strlen($file) - 12);

					require_once $dir.'/'.$file;
					$module = new $classname($db, new PaiementFourn($db));

					print "<tr class=\"oddeven\">\n";
					print "<td>";
					print(empty($module->name) ? $name : $module->name);
					print "</td>\n";
					print "<td>\n";
					require_once $dir.'/'.$file;
					$module = new $classname($db, new Societe($db));
					if (method_exists($module, 'info')) {
						print $module->info($langs);
					} else {
						print $module->description;
					}

					print "</td>\n";

					// Active
					if (in_array($name, $def)) {
						print '<td class="center">'."\n";
						//if ($conf->global->SUPPLIER_PAYMENT_ADDON_PDF != "$name")
						//{
						// Even if choice is the default value, we allow to disable it: For supplier invoice, we accept to have no doc generation at all
						print '<a href="'.$_SERVER["PHP_SELF"].'?action=del&amp;value='.$name.'&amp;scandir='.$module->scandir.'&amp;label='.urlencode($module->name).'&amp;type=SUPPLIER_PAYMENT">';
						print img_picto($langs->trans("Enabled"), 'switch_on');
						print '</a>';
						/*}
						else
						{
							print img_picto($langs->trans("Enabled"),'switch_on');
						}*/
						print "</td>";
					} else {
						print '<td class="center">'."\n";
						print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&token='.newToken().'&value='.urlencode($name).'&scandir='.urlencode($module->scandir).'&label='.urlencode($module->name).'&type=SUPPLIER_PAYMENT">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
						print "</td>";
					}

					// Default
					print '<td class="center">';
					if (getDolGlobalString("SUPPLIER_PAYMENT_ADDON_PDF") == "$name") {
						//print img_picto($langs->trans("Default"),'on');
						// Even if choice is the default value, we allow to disable it: For supplier invoice, we accept to have no doc generation at all
						print '<a href="'.$_SERVER["PHP_SELF"].'?action=unsetdoc&token='.newToken().'&value='.urlencode($name).'&scandir='.urlencode($module->scandir).'&label='.urlencode($module->name).'&type=SUPPLIER_PAYMENT"" alt="'.$langs->trans("Disable").'">'.img_picto($langs->trans("Enabled"), 'on').'</a>';
					} else {
						print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&token='.newToken().'&value='.urlencode($name).'&scandir='.urlencode($module->scandir).'&label='.urlencode($module->name).'&type=SUPPLIER_PAYMENT"" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
					}
					print '</td>';

					// Info
					$htmltooltip = ''.$langs->trans("Name").': '.$module->name;
					$htmltooltip .= '<br>'.$langs->trans("Type").': '.($module->type ? $module->type : $langs->trans("Unknown"));
					$htmltooltip .= '<br>'.$langs->trans("Width").'/'.$langs->trans("Height").': '.$module->page_largeur.'/'.$module->page_hauteur;
					$htmltooltip .= '<br>'.$langs->trans("Path").': '.preg_replace('/^\//', '', $realpath).'/'.$file;

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

print '</table>';

/*
 *  Other Options
 */

print "<br>";

print load_fiche_titre($langs->trans("OtherOptions"), '', '');

print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.newToken().'" />';
print '<input type="hidden" name="action" value="setparams" />';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td align="center" width="60">'.$langs->trans("Value").'</td>';
print '<td width="80">&nbsp;</td>';
print "</tr>\n";

// Allow to group payments by mod in rapports
print '<tr class="oddeven"><td>';
print $langs->trans("GroupPaymentsByModOnReports");
print '</td><td width="60" align="center">';
print $form->selectyesno("PAYMENTS_FOURN_REPORT_GROUP_BY_MOD", getDolGlobalString("PAYMENTS_FOURN_REPORT_GROUP_BY_MOD"), 1);
print '</td><td class="right">';
print "</td></tr>\n";

print '</table>';

print dol_get_fiche_end();

print '<br>';
print '<div class="center">';
print '<input type="submit" class="button button-edit" value="'.$langs->trans("Modify").'" />';
print '</div>';
print '<br>';

print '</form>';

// End of page
llxFooter();
$db->close();
