<?php
/* Copyright (C) 2008-2011  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2011-2017  Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2021       Nicolas ZABOURI     <info@inovea-conseil.com>
 * Copyright (C) 2022       Alexandre Spangaro  <aspangaro@open-dsi.fr>
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
 *	\file       htdocs/takepos/admin/setup.php
 *	\ingroup    takepos
 *	\brief      Setup page for TakePos module
 */

// Load Dolibarr environment
require '../../main.inc.php'; // Load $user and permissions
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT."/core/lib/takepos.lib.php";
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

// If socid provided by ajax company selector
if (GETPOST('CASHDESK_ID_THIRDPARTY_id', 'alpha')) {
	$_GET['CASHDESK_ID_THIRDPARTY'] = GETPOST('CASHDESK_ID_THIRDPARTY_id', 'alpha');
	$_POST['CASHDESK_ID_THIRDPARTY'] = GETPOST('CASHDESK_ID_THIRDPARTY_id', 'alpha');
	$_REQUEST['CASHDESK_ID_THIRDPARTY'] = GETPOST('CASHDESK_ID_THIRDPARTY_id', 'alpha');
}

// Security check
if (!$user->admin) {
	accessforbidden();
}

$langs->loadLangs(array("admin", "cashdesk"));

global $db, $mysoc;

$sql = "SELECT code, libelle FROM ".MAIN_DB_PREFIX."c_paiement";
$sql .= " WHERE entity IN (".getEntity('c_paiement').")";
$sql .= " AND active = 1";
$sql .= " ORDER BY libelle";
$resql = $db->query($sql);
$paiements = array();
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		array_push($paiements, $obj);
	}
}

$action = GETPOST('action', 'aZ09');


/*
 * Actions
 */

$error = 0;

if ($action == 'set') {
	$db->begin();

	$res = dolibarr_set_const($db, "TAKEPOS_ROOT_CATEGORY_ID", GETPOST('TAKEPOS_ROOT_CATEGORY_ID', 'alpha'), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "TAKEPOS_SUPPLEMENTS_CATEGORY", GETPOST('TAKEPOS_SUPPLEMENTS_CATEGORY', 'alpha'), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "TAKEPOS_NUMPAD", GETPOST('TAKEPOS_NUMPAD', 'alpha'), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "TAKEPOS_SORTPRODUCTFIELD", GETPOST('TAKEPOS_SORTPRODUCTFIELD', 'alpha'), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "TAKEPOS_NUM_TERMINALS", GETPOST('TAKEPOS_NUM_TERMINALS', 'alpha'), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "TAKEPOS_ADDON", GETPOST('TAKEPOS_ADDON', 'alpha'), 'int', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "TAKEPOS_EMAIL_TEMPLATE_INVOICE", GETPOST('TAKEPOS_EMAIL_TEMPLATE_INVOICE', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (getDolGlobalInt('TAKEPOS_ENABLE_SUMUP')) {
		$res = dolibarr_set_const($db, "TAKEPOS_SUMUP_AFFILIATE", GETPOST('TAKEPOS_SUMUP_AFFILIATE', 'alpha'), 'chaine', 0, '', $conf->entity);
		$res = dolibarr_set_const($db, "TAKEPOS_SUMUP_APPID", GETPOST('TAKEPOS_SUMUP_APPID', 'alpha'), 'chaine', 0, '', $conf->entity);
	}
	if (isModEnabled('barcode')) {
		$res = dolibarr_set_const($db, 'TAKEPOS_BARCODE_RULE_TO_INSERT_PRODUCT', GETPOST('TAKEPOS_BARCODE_RULE_TO_INSERT_PRODUCT', 'alpha'), 'chaine', 0, '', $conf->entity);
	}

	dol_syslog("admin/cashdesk: level ".GETPOST('level', 'alpha'));

	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		$db->commit();
	} else {
		$db->rollback();
	}
} elseif ($action == 'updateMask') {
	$maskconst = GETPOST('maskconst', 'aZ09');
	$maskvalue = GETPOST('maskvalue', 'alpha');
	if ($maskconst && preg_match('/_MASK$/', $maskconst)) {
		$res = dolibarr_set_const($db, $maskconst, $maskvalue, 'chaine', 0, '', $conf->entity);
	}
	if (!($res > 0)) {
		$error++;
	}
} elseif ($action == 'setrefmod') {
	$value = GETPOST('value', 'alpha');
	dolibarr_set_const($db, "TAKEPOS_REF_ADDON", $value, 'chaine', 0, '', $conf->entity);
}

if ($action != '') {
	if (!$error) {
		setEventMessage($langs->trans('SetupSaved'));
	} else {
		setEventMessages($langs->trans('Error'), null, 'errors');
	}
}


/*
 * View
 */

$form = new Form($db);
$formproduct = new FormProduct($db);

$help_url = 'EN:Module_Point_of_sale_(TakePOS)';

llxHeader('', $langs->trans("CashDeskSetup"), $help_url, '', 0, 0, '', '', '', 'mod-takepos page-admin_setup');

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("CashDeskSetup").' (TakePOS)', $linkback, 'title_setup');
$head = takepos_admin_prepare_head();
print dol_get_fiche_head($head, 'setup', 'TakePOS', -1, 'cash-register');

// Numbering modules
$now = dol_now();
$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

print load_fiche_titre($langs->trans('CashDeskRefNumberingModules'), '', '');

print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name")."</td>\n";
print '<td>'.$langs->trans("Description")."</td>\n";
print '<td class="nowrap">'.$langs->trans("Example")."</td>\n";
print '<td class="center" width="60">'.$langs->trans("Status").'</td>';
print '<td class="center" width="16">'.$langs->trans("ShortInfo").'</td>';
print '</tr>'."\n";

clearstatcache();

foreach ($dirmodels as $reldir) {
	$dir = dol_buildpath($reldir."core/modules/takepos/");

	if (is_dir($dir)) {
		$handle = opendir($dir);
		if (is_resource($handle)) {
			$var = true;

			while (($file = readdir($handle)) !== false) {
				if (substr($file, 0, 16) == 'mod_takepos_ref_' && substr($file, dol_strlen($file) - 3, 3) == 'php') {
					$file = substr($file, 0, dol_strlen($file) - 4);

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
							print $langs->trans($tmp);
						} else {
							print $tmp;
						}
						print '</td>'."\n";

						print '<td class="center">';
						if (getDolGlobalString('TAKEPOS_REF_ADDON') == "$file") {
							print img_picto($langs->trans("Activated"), 'switch_on');
						} else {
							print '<a href="'.$_SERVER["PHP_SELF"].'?action=setrefmod&token='.newToken().'&value='.urlencode($file).'">';
							print img_picto($langs->trans("Disabled"), 'switch_off');
							print '</a>';
						}
						print '</td>';

						// example for next value
						$invoice = new Facture($db);
						$invoice->date = $now;
						$invoice->module_source = 'takepos';
						$invoice->pos_source = 1;

						// Info
						$htmltooltip = '';
						$htmltooltip .= ''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
						$nextval = $module->getNextValue($mysoc, $invoice);
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

						print '<td align="center">';
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
print "</table>\n";
print '</div>';
print "\n";

print '<br>';


print load_fiche_titre($langs->trans('Options'), '', '');

// Mode
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="set">';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<td class="notitlefield">'.$langs->trans("Parameters").'</td><td></td>';
print "</tr>\n";

// Terminals
print '<tr class="oddeven"><td>';
print $langs->trans("NumberOfTerminals");
print '<td>';
print '<input type="number" name="TAKEPOS_NUM_TERMINALS" min="1" value="' . (!getDolGlobalString('TAKEPOS_NUM_TERMINALS') ? '1' : $conf->global->TAKEPOS_NUM_TERMINALS)  . '">';
print "</td></tr>\n";

// Services
if (isModEnabled("service")) {
	print '<tr class="oddeven"><td>';
	print $langs->trans("CashdeskShowServices");
	print '<td>';
	print ajax_constantonoff("CASHDESK_SERVICES", array(), $conf->entity, 0, 0, 1, 0);
	//print $form->selectyesno("CASHDESK_SERVICES", $conf->global->CASHDESK_SERVICES, 1);
	print "</td></tr>\n";
}

// Root category for products
print '<tr class="oddeven"><td>';
print $form->textwithpicto($langs->trans("RootCategoryForProductsToSell"), $langs->trans("RootCategoryForProductsToSellDesc"));
print '<td>';
print img_object('', 'category', 'class="paddingright"').$form->select_all_categories(Categorie::TYPE_PRODUCT, getDolGlobalInt('TAKEPOS_ROOT_CATEGORY_ID'), 'TAKEPOS_ROOT_CATEGORY_ID', 64, 0, 0, 0, 'maxwidth500 widthcentpercentminusx');
print ajax_combobox('TAKEPOS_ROOT_CATEGORY_ID');
print "</td></tr>\n";

// Sort product
print '<tr class="oddeven"><td>';
print $langs->trans("SortProductField");
print '<td>';
$prod = new Product($db);
$array = array('rowid' => 'ID', 'ref' => 'Ref', 'label' => 'Label', 'datec' => 'DateCreation', 'tms' => 'DateModification');
print $form->selectarray('TAKEPOS_SORTPRODUCTFIELD', $array, getDolGlobalString('TAKEPOS_SORTPRODUCTFIELD', 'rowid'), 0, 0, 0, '', 1);
print "</td></tr>\n";

print '<tr class="oddeven"><td>';
print $langs->trans('TakeposGroupSameProduct');
print '<td>';
print ajax_constantonoff("TAKEPOS_GROUP_SAME_PRODUCT", array(), $conf->entity, 0, 0, 1, 0);
print "</td></tr>\n";

$substitutionarray = pdf_getSubstitutionArray($langs, null, null, 2);
$substitutionarray['__(AnyTranslationKey)__'] = $langs->trans("Translation");
$htmltext = '<i>'.$langs->trans("AvailableVariables").':<br>';
foreach ($substitutionarray as $key => $val) {
	$htmltext .= $key.'<br>';
}
$htmltext .= '</i>';

// Payment numpad
print '<tr class="oddeven"><td>';
print $langs->trans("Paymentnumpad");
print '<td>';
$array = array(0=>$langs->trans("Numberspad"), 1=>$langs->trans("BillsCoinsPad"));
print $form->selectarray('TAKEPOS_NUMPAD', $array, (!getDolGlobalString('TAKEPOS_NUMPAD') ? '0' : $conf->global->TAKEPOS_NUMPAD), 0);
print "</td></tr>\n";

// Numpad use payment icons
/*print '<tr class="oddeven"><td>';
print $langs->trans('TakeposNumpadUsePaymentIcon');
print '<td colspan="2">';
print ajax_constantonoff("TAKEPOS_NUMPAD_USE_PAYMENT_ICON", array(), $conf->entity, 0, 0, 1, 0);
print "</td></tr>\n";
*/

// Direct Payment
print '<tr class="oddeven"><td>';
print $langs->trans('DirectPaymentButton');
print '<td>';
print ajax_constantonoff("TAKEPOS_DIRECT_PAYMENT", array(), $conf->entity, 0, 0, 1, 0);
//print $form->selectyesno("TAKEPOS_DIRECT_PAYMENT", $conf->global->TAKEPOS_DIRECT_PAYMENT, 1);
print "</td></tr>\n";

// Head Bar
/*print '<tr class="oddeven"><td>';
print $langs->trans('HeadBar');
print '<td colspan="2">';
print $form->selectyesno("TAKEPOS_HEAD_BAR", $conf->global->TAKEPOS_HEAD_BAR, 1);
print "</td></tr>\n";
*/

// Email template for send invoice
print '<tr class="oddeven"><td>';
print $langs->trans('EmailTemplate');
print '<td>';
include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
$formmail = new FormMail($db);
$nboftemplates = $formmail->fetchAllEMailTemplate('facture_send', $user, null, -1); // We set lang=null to get in priority record with no lang
//$arraydefaultmessage = $formmail->getEMailTemplate($db, $tmp[1], $user, null, 0, 1, '');
$arrayofmessagename = array();
if (is_array($formmail->lines_model)) {
	foreach ($formmail->lines_model as $modelmail) {
		//var_dump($modelmail);
		$moreonlabel = '';
		if (!empty($arrayofmessagename[$modelmail->label])) {
			$moreonlabel = ' <span class="opacitymedium">('.$langs->trans("SeveralLangugeVariatFound").')</span>';
		}
		$arrayofmessagename[$modelmail->id] = $langs->trans(preg_replace('/\(|\)/', '', $modelmail->topic)).$moreonlabel;
	}
}
//var_dump($arraydefaultmessage);
//var_dump($arrayofmessagename);
print $form->selectarray('TAKEPOS_EMAIL_TEMPLATE_INVOICE', $arrayofmessagename, getDolGlobalString('TAKEPOS_EMAIL_TEMPLATE_INVOICE'), 'None', 1, 0, '', 0, 0, 0, '', 'maxwidth500 widthcentpercentminusx', 1);
print "</td></tr>\n";

// Control cash box at opening pos
print '<tr class="oddeven"><td>';
print $langs->trans('ControlCashOpening');
print '<td>';
print ajax_constantonoff("TAKEPOS_CONTROL_CASH_OPENING", array(), $conf->entity, 0, 0, 1, 0);
print "</td></tr>\n";

// Gift receipt
print '<tr class="oddeven"><td>';
print $langs->trans('GiftReceiptButton');
print '<td>';
print ajax_constantonoff("TAKEPOS_GIFT_RECEIPT", array(), $conf->entity, 0, 0, 1, 0);
print "</td></tr>\n";

// Delayed Pay Button
print '<tr class="oddeven"><td>';
print $langs->trans('AllowDelayedPayment');
print '<td>';
print ajax_constantonoff("TAKEPOS_DELAYED_PAYMENT", array(), $conf->entity, 0, 0, 1, 0);
print "</td></tr>\n";

// Show price without vat
print '<tr class="oddeven"><td>';
print $langs->trans('ShowPriceHT');
print '<td>';
print ajax_constantonoff("TAKEPOS_SHOW_HT", array(), $conf->entity, 0, 0, 1, 0);
print "</td></tr>\n";

// Use price excl. taxes (HT) and not price incl. taxes (TTC)
print '<tr class="oddeven"><td>';
print $langs->trans('UsePriceHT');
print '</td><td>';
print ajax_constantonoff("TAKEPOS_CHANGE_PRICE_HT", array(), $conf->entity, 0, 0, 1, 0);
print "</td></tr>\n";

// Barcode rule to insert product
if (isModEnabled('barcode')) {
	print '<tr class="oddeven"><td>';
	print $form->textwithpicto($langs->trans("TakeposBarcodeRuleToInsertProduct"), $langs->trans("TakeposBarcodeRuleToInsertProductDesc"), 1, 'help', '', 0, 3, 'barcoderuleonsmartphone');
	print '<td>';
	print '<input type="text" name="TAKEPOS_BARCODE_RULE_TO_INSERT_PRODUCT" value="' . (getDolGlobalString('TAKEPOS_BARCODE_RULE_TO_INSERT_PRODUCT')) . '">';
	print "</td></tr>\n";
}

// Numbering module
//print '<tr class="oddeven"><td>';
//print $langs->trans("BillsNumberingModule");
//print '<td colspan="2">';
//$array = array(0=>$langs->trans("Default"), "terminal"=>$langs->trans("ByTerminal"));
//$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
//foreach ($dirmodels as $reldir)
//{
//	$dir = dol_buildpath($reldir."core/modules/facture/");
//    if (is_dir($dir))
//    {
//        $handle = opendir($dir);
//        if (is_resource($handle))
//        {
//            while (($file = readdir($handle)) !== false)
//            {
//                if (!is_dir($dir.$file) || (substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS'))
//                {
//                    $filebis = $file;
//                    $classname = preg_replace('/\.php$/', '', $file);
//                    // For compatibility
//                    if (!is_file($dir.$filebis))
//                    {
//                        $filebis = $file."/".$file.".modules.php";
//                        $classname = "mod_facture_".$file;
//                    }
//                    // Check if there is a filter on country
//                    preg_match('/\-(.*)_(.*)$/', $classname, $reg);
//                    if (!empty($reg[2]) && $reg[2] != strtoupper($mysoc->country_code)) continue;
//
//                    $classname = preg_replace('/\-.*$/', '', $classname);
//                    if (!class_exists($classname) && is_readable($dir.$filebis) && (preg_match('/mod_/', $filebis) || preg_match('/mod_/', $classname)) && substr($filebis, dol_strlen($filebis) - 3, 3) == 'php')
//                    {
//                        // Charging the numbering class
//                        require_once $dir.$filebis;
//
//                        $module = new $classname($db);
//
//                        // Show modules according to features level
//                        if ($module->version == 'development' && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
//                        if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;
//
//                        if ($module->isEnabled())
//                        {
//							$array[preg_replace('/\-.*$/', '', preg_replace('/\.php$/', '', $file))] = preg_replace('/\-.*$/', '', preg_replace('/mod_facture_/', '', preg_replace('/\.php$/', '', $file)));
//                        }
//                    }
//                }
//            }
//            closedir($handle);
//        }
//    }
//}
//
//print $form->selectarray('TAKEPOS_ADDON', $array, (empty($conf->global->TAKEPOS_ADDON) ? '0' : $conf->global->TAKEPOS_ADDON), 0);
//print "</td></tr>\n";

print '</table>';
print '</div>';


// Sumup options
if (getDolGlobalInt('TAKEPOS_ENABLE_SUMUP')) {
	print '<br>';

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';

	print '<tr class="liste_titre">';
	print '<td class="titlefield">'.$langs->trans("Sumup").'</td><td></td>';
	print "</tr>\n";

	print '<tr class="oddeven"><td>';
	print $langs->trans("SumupAffiliate");
	print '<td colspan="2">';
	print '<input type="text" name="TAKEPOS_SUMUP_AFFILIATE" value="' . getDolGlobalString('TAKEPOS_SUMUP_AFFILIATE').'"></input>';
	print "</td></tr>\n";
	print '<tr class="oddeven"><td>';
	print $langs->trans("SumupAppId");
	print '<td colspan="2">';
	print '<input type="text" name="TAKEPOS_SUMUP_APPID" value="' . getDolGlobalString('TAKEPOS_SUMUP_APPID').'"></input>';
	print "</td></tr>\n";

	print '</table>';
	print '</div>';
}

print $form->buttonsSaveCancel("Save", '');

print "</form>\n";

llxFooter();
$db->close();
