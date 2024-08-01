<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
* Copyright (C) 2004-2011 Laurent Destailleur     <eldy@users.sourceforge.net>
* Copyright (C) 2005-2011 Regis Houssin           <regis.houssin@inodbox.com>
* Copyright (C) 2004      Sebastien Di Cintio     <sdicintio@ressource-toi.org>
* Copyright (C) 2004      Benoit Mortier          <benoit.mortier@opensides.be>
* Copyright (C) 2010-2013 Juanjo Menent           <jmenent@2byte.es>
* Copyright (C) 2011-2018 Philippe Grand          <philippe.grand@atoo-net.com>
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
 *  \file       htdocs/admin/supplier_order.php
 *  \ingroup    fournisseur
 *  \brief      Page d'administration-configuration du module Fournisseur
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/fourn.lib.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';

// Load translation files required by the page
$langs->loadLangs(array("admin", "other", "orders", "stocks"));

$action = GETPOST('action', 'aZ09');

$type = GETPOST('type', 'alpha');
$value = GETPOST('value', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ09');	// Used by actions_setmoduleoptions.inc.php

$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');

$specimenthirdparty = new Societe($db);
$specimenthirdparty->initAsSpecimen();

$error = 0;

if (!$user->admin) {
	accessforbidden();
}


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

if ($action == 'updateMask') {
	$maskconstorder = GETPOST('maskconstorder', 'aZ09');
	$maskvalue = GETPOST('maskorder', 'alpha');

	if ($maskconstorder && preg_match('/_MASK$/', $maskconstorder)) {
		$res = dolibarr_set_const($db, $maskconstorder, $maskvalue, 'chaine', 0, '', $conf->entity);
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

if ($action == 'specimen') {  // For orders
	$modele = GETPOST('module', 'alpha');

	$commande = new CommandeFournisseur($db);
	$commande->initAsSpecimen();
	$commande->thirdparty = $specimenthirdparty;

	// Search template files
	$file = '';
	$classname = '';
	$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
	foreach ($dirmodels as $reldir) {
		$file = dol_buildpath($reldir."core/modules/supplier_order/doc/pdf_".$modele.".modules.php", 0);
		if (file_exists($file)) {
			$classname = "pdf_".$modele;
			break;
		}
	}

	if ($classname !== '') {
		require_once $file;

		$module = new $classname($db, $commande);
		'@phan-var-force CommonDocGenerator $module';

		if ($module->write_file($commande, $langs) > 0) {
			header("Location: ".DOL_URL_ROOT."/document.php?modulepart=commande_fournisseur&file=SPECIMEN.pdf");
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
		if ($conf->global->COMMANDE_SUPPLIER_ADDON_PDF == "$value") {
			dolibarr_del_const($db, 'COMMANDE_SUPPLIER_ADDON_PDF', $conf->entity);
		}
	}
} elseif ($action == 'setdoc') {
	// Set default model
	if (dolibarr_set_const($db, "COMMANDE_SUPPLIER_ADDON_PDF", $value, 'chaine', 0, '', $conf->entity)) {
		// La constante qui a ete lue en avant du nouveau set
		// on passe donc par une variable pour avoir un affichage coherent
		$conf->global->COMMANDE_SUPPLIER_ADDON_PDF = $value;
	}

	// On active le modele
	$ret = delDocumentModel($value, $type);
	if ($ret > 0) {
		$ret = addDocumentModel($value, $type, $label, $scandir);
	}
} elseif ($action == 'unsetdoc') {
	dolibarr_del_const($db, "COMMANDE_SUPPLIER_ADDON_PDF", $conf->entity);
} elseif ($action == 'setmod') {
	// TODO Verify if the chosen numbering module can be activated
	// by calling method canBeActivated

	dolibarr_set_const($db, "COMMANDE_SUPPLIER_ADDON_NUMBER", $value, 'chaine', 0, '', $conf->entity);
} elseif ($action == 'addcat') {
	$fourn = new Fournisseur($db);
	$fourn->CreateCategory($user, GETPOST('cat', 'alphanohtml'));
} elseif ($action == 'set_SUPPLIER_ORDER_OTHER') {
	$freetext = GETPOST('SUPPLIER_ORDER_FREE_TEXT', 'restricthtml'); // No alpha here, we want exact string
	$doubleapproval = GETPOST('SUPPLIER_ORDER_3_STEPS_TO_BE_APPROVED', 'alpha');
	$doubleapproval = price2num($doubleapproval);

	$res1 = dolibarr_set_const($db, "SUPPLIER_ORDER_FREE_TEXT", $freetext, 'chaine', 0, '', $conf->entity);
	$res2 = dolibarr_set_const($db, "SUPPLIER_ORDER_3_STEPS_TO_BE_APPROVED", $doubleapproval, 'chaine', 0, '', $conf->entity);

	// TODO We add/delete permission here until permission can have a condition on a global var
	include_once DOL_DOCUMENT_ROOT.'/core/modules/modFournisseur.class.php';
	$newmodule = new modFournisseur($db);

	if ($conf->global->SUPPLIER_ORDER_3_STEPS_TO_BE_APPROVED) {
		// clear default rights array
		$newmodule->rights = array();
		// add new right
		$r = 0;
		$newmodule->rights[$r][0] = 1190;
		$newmodule->rights[$r][1] = $langs->trans("Permission1190");
		$newmodule->rights[$r][2] = 'w';
		$newmodule->rights[$r][3] = 0;
		$newmodule->rights[$r][4] = 'commande';
		$newmodule->rights[$r][5] = 'approve2';

		// Insert
		$newmodule->insert_permissions(1);
	} else {
		// Remove all rights with Permission1190
		$newmodule->delete_permissions();

		// Add all right without Permission1190
		$newmodule->insert_permissions(1);
	}
} elseif ($action == 'set_BANK_ASK_PAYMENT_BANK_DURING_SUPPLIER_ORDER') {
	// Activate ask for payment bank
	$res = dolibarr_set_const($db, "BANK_ASK_PAYMENT_BANK_DURING_SUPPLIER_ORDER", $value, 'chaine', 0, '', $conf->entity);

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

$form = new Form($db);

$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

llxHeader('', '', '', '', 0, 0, '', '', '', 'mod-admin page-supplier_order');

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("SuppliersSetup"), $linkback, 'title_setup');

print "<br>";

$head = supplierorder_admin_prepare_head();

print dol_get_fiche_head($head, 'order', $langs->trans("Suppliers"), -1, 'company');


// Supplier order numbering module

print load_fiche_titre($langs->trans("OrdersNumberingModules"), '', '');

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
	$dir = dol_buildpath($reldir."core/modules/supplier_order/");

	if (is_dir($dir)) {
		$handle = opendir($dir);
		if (is_resource($handle)) {
			while (($file = readdir($handle)) !== false) {
				if (substr($file, 0, 25) == 'mod_commande_fournisseur_' && substr($file, dol_strlen($file) - 3, 3) == 'php') {
					$file = substr($file, 0, dol_strlen($file) - 4);

					require_once $dir.$file.'.php';

					$module = new $file();

					if ($module->isEnabled()) {
						// Show modules according to features level
						if ($module->version == 'development' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 2) {
							continue;
						}
						if ($module->version == 'experimental' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 1) {
							continue;
						}


						print '<tr class="oddeven"><td>'.$module->nom."</td><td>\n";
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
						if ($conf->global->COMMANDE_SUPPLIER_ADDON_NUMBER == "$file") {
							print img_picto($langs->trans("Activated"), 'switch_on');
						} else {
							print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setmod&token='.newToken().'&value='.urlencode($file).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
						}
						print '</td>';

						$commande = new CommandeFournisseur($db);
						$commande->initAsSpecimen();

						// Info
						$htmltooltip = '';
						$htmltooltip .= ''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
						$nextval = $module->getNextValue($mysoc, $commande);
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

print '</table></div><br>';


/*
 *  Documents models for supplier orders
 */

print load_fiche_titre($langs->trans("OrdersModelModule"), '', '');

// Defini tableau def de modele
$def = array();

$sql = "SELECT nom";
$sql .= " FROM ".MAIN_DB_PREFIX."document_model";
$sql .= " WHERE type = 'order_supplier'";
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
	$realpath = $reldir."core/modules/supplier_order/doc";
	$dir = dol_buildpath($realpath);

	if (is_dir($dir)) {
		$handle = opendir($dir);
		if (is_resource($handle)) {
			while (($file = readdir($handle)) !== false) {
				if (preg_match('/\.modules\.php$/i', $file) && preg_match('/^(pdf_|doc_)/', $file)) {
					$name = substr($file, 4, dol_strlen($file) - 16);
					$classname = substr($file, 0, dol_strlen($file) - 12);

					require_once $dir.'/'.$file;
					$module = new $classname($db, new CommandeFournisseur($db));


					print "<tr class=\"oddeven\">\n";
					print "<td>";
					print(empty($module->name) ? $name : $module->name);
					print "</td>\n";
					print "<td>\n";
					require_once $dir.'/'.$file;
					$module = new $classname($db, $specimenthirdparty);
					if (method_exists($module, 'info')) {
						print $module->info($langs);
					} else {
						print $module->description;
					}
					print "</td>\n";

					// Active
					if (in_array($name, $def)) {
						print '<td class="center">'."\n";
						if ($conf->global->COMMANDE_SUPPLIER_ADDON_PDF != "$name") {
							print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=del&token='.newToken().'&value='.urlencode($name).'&scan_dir='.urlencode($module->scandir).'&label='.urlencode($module->name).'&type=order_supplier">';
							print img_picto($langs->trans("Enabled"), 'switch_on');
							print '</a>';
						} else {
							print img_picto($langs->trans("Enabled"), 'switch_on');
						}
						print "</td>";
					} else {
						print '<td class="center">'."\n";
						print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=set&token='.newToken().'&value='.urlencode($name).'&scan_dir='.urlencode($module->scandir).'&label='.urlencode($module->name).'&type=order_supplier">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
						print "</td>";
					}

					// Default
					print '<td class="center">';
					if ($conf->global->COMMANDE_SUPPLIER_ADDON_PDF == "$name") {
						print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=unsetdoc&token='.newToken().'&value='.urlencode($name).'&scan_dir='.urlencode($module->scandir).'&label='.urlencode($module->name).'&type=order_supplier" alt="'.$langs->trans("Disable").'">'.img_picto($langs->trans("Enabled"), 'on').'</a>';
					} else {
						print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setdoc&token='.newToken().'&value='.urlencode($name).'&scan_dir='.urlencode($module->scandir).'&label='.urlencode($module->name).'&type=order_supplier" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
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
					print '<td class="center">';
					print $form->textwithpicto('', $htmltooltip, 1, 0);
					print '</td>';
					print '<td class="center">';
					print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.urlencode($name).'">'.img_object($langs->trans("Preview"), 'pdf').'</a>';
					print '</td>';

					print "</tr>\n";
				}
			}

			closedir($handle);
		}
	}
}

print '</table></div><br>';

/*
 * Other options
 */

print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="set_SUPPLIER_ORDER_OTHER">';

print load_fiche_titre($langs->trans("OtherOptions"), '', '');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td align="center" width="60">'.$langs->trans("Value").'</td>';
print '<td width="80">&nbsp;</td>';
print "</tr>\n";

print '<tr class="oddeven"><td>';
print $form->textwithpicto($langs->trans("UseDoubleApproval"), $langs->trans("Use3StepsApproval"), 1, 'help').'<br>';
print $langs->trans("IfSetToYesDontForgetPermission");
print '</td><td>';
print '<input type="text" size="6" name="SUPPLIER_ORDER_3_STEPS_TO_BE_APPROVED" value="'.getDolGlobalString("SUPPLIER_ORDER_3_STEPS_TO_BE_APPROVED").'">';
print '</td><td class="right">';
print '<input type="submit" class="button button-edit" value="'.$langs->trans("Modify").'">';
print "</td></tr>\n";


// Ask for payment bank during supplier order
/* Kept as hidden for the moment
if (isModEnabled('banque')) {

print '<tr class="oddeven"><td>';
print $langs->trans("BANK_ASK_PAYMENT_BANK_DURING_SUPPLIER_ORDER").'</td><td>&nbsp;</td><td align="center">';
if (!empty($conf->use_javascript_ajax))
{
print ajax_constantonoff('BANK_ASK_PAYMENT_BANK_DURING_SUPPLIER_ORDER');
}
else
{
if (empty($conf->global->BANK_ASK_PAYMENT_BANK_DURING_ORDER))
{
print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_BANK_ASK_PAYMENT_BANK_DURING_SUPPLIER_ORDER&token='.newToken().'&value=1">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
}
else
{
print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_BANK_ASK_PAYMENT_BANK_DURING_SUPPLIER_ORDER&token='.newToken().'&value=0">'.img_picto($langs->trans("Enabled"),'switch_on').'</a>';
}
}
print '</td></tr>';
}
else
{

print '<tr class="oddeven"><td>';
print $langs->trans("BANK_ASK_PAYMENT_BANK_DURING_SUPPLIER_ORDER").'</td><td>&nbsp;</td><td align="center">'.$langs->trans('NotAvailable').'</td></tr>';
}
*/

$substitutionarray = pdf_getSubstitutionArray($langs, null, null, 2);
$substitutionarray['__(AnyTranslationKey)__'] = $langs->trans("Translation");
$htmltext = '<i>'.$langs->trans("AvailableVariables").':<br>';
foreach ($substitutionarray as $key => $val) {
	$htmltext .= $key.'<br>';
}
$htmltext .= '</i>';

print '<tr class="oddeven"><td colspan="2">';
print $form->textwithpicto($langs->trans("FreeLegalTextOnOrders"), $langs->trans("AddCRIfTooLong").'<br><br>'.$htmltext, 1, 'help', '', 0, 2, 'freetexttooltip').'<br>';
$variablename = 'SUPPLIER_ORDER_FREE_TEXT';
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

// Option to add a quality/validation step, on products, after reception.
print '<tr class="oddeven">';
print '<td>'.$langs->trans("UseDispatchStatus").'</td>';
print '<td></td>';
print '<td class="center">';
if (isModEnabled('reception')) {
	print '<span class="opacitymedium">'.$langs->trans("FeatureNotAvailableWithReceptionModule").'</span>';
} else {
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('SUPPLIER_ORDER_USE_DISPATCH_STATUS');
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("SUPPLIER_ORDER_USE_DISPATCH_STATUS", $arrval, $conf->global->SUPPLIER_ORDER_USE_DISPATCH_STATUS);
	}
}
print "</td>\n";
print "</tr>\n";


// Disallow to classify billed a supplier order without invoice
print '<tr class="oddeven"><td>'.$langs->trans("SupplierOrderClassifyBilledWithoutInvoice"). '&nbsp;' ;
print $form->textwithpicto('', $langs->trans("SupplierOrderClassifyBilledWithoutInvoiceHelp"), 1, 'help') . '</td>';
print '<td class="left" colspan="2">';
print ajax_constantonoff('SUPPLIER_ORDER_DISABLE_CLASSIFY_BILLED_FROM_SUPPLIER_ORDER');
print '</td></tr>';

print '</table></div><br>';

print '</form>';


/*
* Notifications
*/

print load_fiche_titre($langs->trans("Notifications"), '', '');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td align="center" width="60"></td>';
print '<td width="80">&nbsp;</td>';
print "</tr>\n";

print '<tr class="oddeven"><td colspan="2">';
print $langs->trans("YouMayFindNotificationsFeaturesIntoModuleNotification").'<br>';
print '</td><td class="right">';
print "</td></tr>\n";

print '</table>';
print '</div>';

// End of page
llxFooter();
$db->close();
