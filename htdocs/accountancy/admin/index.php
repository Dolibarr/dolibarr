<?php
/* Copyright (C) 2013-2014	Olivier Geffroy			<jeff@jeffinfo.com>
 * Copyright (C) 2013-2014	Florian Henry			<florian.henry@open-concept.pro>
 * Copyright (C) 2013-2025	Alexandre Spangaro		<alexandre@inovea-conseil.com>
 * Copyright (C) 2014-2015	Ari Elbaz (elarifr)		<github@accedinfo.com>
 * Copyright (C) 2014		Marcos García			<marcosgdf@gmail.com>
 * Copyright (C) 2014		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2015		Jean-François Ferry		<jfefe@aternatik.fr>
 * Copyright (C) 2017		Laurent Destailleur		<eldy@destailleur.fr>
 * Copyright (C) 2021		Ferran Marcet			<fmarcet@2byte.es>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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
 * \file		htdocs/accountancy/admin/index.php
 * \ingroup		Accountancy (Double entries)
 * \brief		Setup page to configure accounting expert module
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountancyexport.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/bookkeeping.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formsetup.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Societe $mysoc
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array("compta", "bills", "admin", "accountancy", "other"));

// Security access
if (!$user->hasRight('accounting', 'chartofaccount')) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');
if (empty($action)) {
	$action = 'edit';
}

$nbletter = GETPOSTINT('ACCOUNTING_LETTERING_NBLETTERS');

// New form setup options
$formSetup = new FormSetup($db);

// Main options
$formSetup->newItem('BANK_DISABLE_DIRECT_INPUT')->setAsYesNo();

$formSetup->newItem('ACCOUNTANCY_COMBO_FOR_AUX')->setAsYesNo();

$item = $formSetup->newItem('ACCOUNTING_MANAGE_ZERO')->setAsYesNo();
$item->helpText = $langs->trans('ACCOUNTING_MANAGE_ZERO2');
$item->fieldParams['helpText'] = 'noclick';


if (!getDolGlobalInt('ACCOUNTING_MANAGE_ZERO')) {
	$item = $formSetup->newItem('ACCOUNTING_LENGTH_GACCOUNT')->setAsString();
	$item->fieldAttr['type'] = 'number';
	$item->fieldAttr['class'] = 'maxwidth50 right';

	$item = $formSetup->newItem('ACCOUNTING_LENGTH_AACCOUNT')->setAsString();
	$item->fieldAttr['type'] = 'number';
	$item->fieldAttr['class'] = 'maxwidth50 right';
}

// Parameters ACCOUNTING_* and others
$list = array(
	'ACCOUNTING_LENGTH_GACCOUNT',
	'ACCOUNTING_LENGTH_AACCOUNT',
);

$list_binding = array(
	'ACCOUNTING_DEFAULT_PERIOD_ON_TRANSFER',
	'ACCOUNTING_DATE_START_BINDING',
	'ACCOUNTING_LABEL_OPERATION_ON_TRANSFER'
);

// Parameters for export options
$main_option = array(
	'ACCOUNTING_EXPORT_PREFIX_SPEC',
);

$accountancyexport = new AccountancyExport($db);
$configuration = $accountancyexport->getTypeConfig();

$listparam = $configuration['param'];
$listformat = $configuration['format'];
$listcr = $configuration['cr'];

$model_option = array(
	'1' => array(
		'label' => 'ACCOUNTING_EXPORT_FORMAT',
		'param' => $listformat,
	),
	'2' => array(
		'label' => 'ACCOUNTING_EXPORT_SEPARATORCSV',
		'param' => '',
	),
	'3' => array(
		'label' => 'ACCOUNTING_EXPORT_ENDLINE',
		'param' => $listcr,
	),
	'4' => array(
		'label' => 'ACCOUNTING_EXPORT_DATE',
		'param' => '',
	),
);

$error = 0;

$accounting_mode = getDolGlobalString('ACCOUNTING_MODE', 'CREANCES-DETTES');


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

if (in_array($action, array('setACCOUNTANCY_ER_DATE_RECORD', 'setACCOUNTING_BANK_CONCILIATED'))) {
	$constname = preg_replace('/^set/', '', $action);
	$constvalue = GETPOSTINT('value');
	$res = dolibarr_set_const($db, $constname, $constvalue, 'yesno', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'mesgs');
	}
}

if ($action == 'updatemode') {
	$error = 0;

	$accounting_modes = array(
		'CREANCES-DETTES',
		'RECETTES-DEPENSES'
	);

	$accounting_mode = GETPOST('accounting_mode', 'alpha');

	if (in_array($accounting_mode, $accounting_modes)) {
		if (dolibarr_set_const($db, 'ACCOUNTING_MODE', $accounting_mode, 'chaine', 0, '', $conf->entity)) {
			setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
		} else {
			$error++;
		}
	} else {
		$error++;
	}
}

if ($action == 'update2') {
	$error = 0;

	if (!$error) {
		foreach ($list as $constname) {
			$constvalue = GETPOST($constname, 'alpha');
			if (!dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, '', $conf->entity)) {
				$error++;
			}
		}
		if ($error) {
			setEventMessages($langs->trans("Error"), null, 'errors');
		}

		// option in section binding
		foreach ($list_binding as $constname) {
			$constvalue = GETPOST($constname, 'alpha');

			if ($constname == 'ACCOUNTING_DATE_START_BINDING') {
				$constvalue = dol_mktime(0, 0, 0, GETPOSTINT($constname.'month'), GETPOSTINT($constname.'day'), GETPOSTINT($constname.'year'));
			}

			if (!dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, '', $conf->entity)) {
				$error++;
			}
		}

		// options in section other
		if (GETPOSTISSET('ACCOUNTING_LETTERING_NBLETTERS')) {
			if (!dolibarr_set_const($db, 'ACCOUNTING_LETTERING_NBLETTERS', GETPOST('ACCOUNTING_LETTERING_NBLETTERS'), 'chaine', 0, '', $conf->entity)) {
				$error++;
			}
		}

		// Export options
		$modelcsv = GETPOSTINT('ACCOUNTING_EXPORT_MODELCSV');

		if (!empty($modelcsv)) {
			if (!dolibarr_set_const($db, 'ACCOUNTING_EXPORT_MODELCSV', $modelcsv, 'chaine', 0, '', $conf->entity)) {
				$error++;
			}
			//if ($modelcsv==AccountancyExport::$EXPORT_TYPE_QUADRATUS || $modelcsv==AccountancyExport::$EXPORT_TYPE_CIEL) {
			//	dolibarr_set_const($db, 'ACCOUNTING_EXPORT_FORMAT', 'txt', 'chaine', 0, '', $conf->entity);
			//}
		} else {
			$error++;
		}

		foreach ($main_option as $constname) {
			$constvalue = GETPOST($constname, 'alpha');

			if (!dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, '', $conf->entity)) {
				$error++;
			}
		}

		foreach ($listparam[$modelcsv] as $key => $value) {
			$constante = $key;

			if (strpos($constante, 'ACCOUNTING') !== false) {
				$constvalue = GETPOST($key, 'alpha');
				if (!dolibarr_set_const($db, $constante, $constvalue, 'chaine', 0, '', $conf->entity)) {
					$error++;
				}
			}
		}

		if (!$error) {
			// reload
			$configuration = $accountancyexport->getTypeConfig();
			$listparam = $configuration['param'];
		}
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

if ($action == 'setenabledraftexport') {
	$setenabledraftexport = GETPOSTINT('value');
	$res = dolibarr_set_const($db, "ACCOUNTING_ENABLE_EXPORT_DRAFT_JOURNAL", $setenabledraftexport, 'yesno', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'mesgs');
	}
}

if ($action == 'setdisablebindingonsales') {
	$setdisablebindingonsales = GETPOSTINT('value');
	$res = dolibarr_set_const($db, "ACCOUNTING_DISABLE_BINDING_ON_SALES", $setdisablebindingonsales, 'yesno', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'mesgs');
	}
}

if ($action == 'setdisablebindingonpurchases') {
	$setdisablebindingonpurchases = GETPOSTINT('value');
	$res = dolibarr_set_const($db, "ACCOUNTING_DISABLE_BINDING_ON_PURCHASES", $setdisablebindingonpurchases, 'yesno', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'mesgs');
	}
}

if ($action == 'setdisablebindingonexpensereports') {
	$setdisablebindingonexpensereports = GETPOSTINT('value');
	$res = dolibarr_set_const($db, "ACCOUNTING_DISABLE_BINDING_ON_EXPENSEREPORTS", $setdisablebindingonexpensereports, 'yesno', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'mesgs');
	}
}

if ($action == 'setenablelettering') {
	$setenablelettering = GETPOSTINT('value');
	$res = dolibarr_set_const($db, "ACCOUNTING_ENABLE_LETTERING", $setenablelettering, 'yesno', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'mesgs');
	}
}

if ($action == 'setenableautolettering') {
	$setenableautolettering = GETPOSTINT('value');
	$res = dolibarr_set_const($db, "ACCOUNTING_ENABLE_AUTOLETTERING", $setenableautolettering, 'yesno', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'mesgs');
	}
}

if ($action == 'setenablevatreversecharge') {
	$setenablevatreversecharge = GETPOSTINT('value');
	$res = dolibarr_set_const($db, "ACCOUNTING_FORCE_ENABLE_VAT_REVERSE_CHARGE", $setenablevatreversecharge, 'yesno', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'mesgs');
	}
}

if ($action == 'setenabletabonthirdparty') {
	$setenabletabonthirdparty = GETPOSTINT('value');
	$res = dolibarr_set_const($db, "ACCOUNTING_ENABLE_TABONTHIRDPARTY", $setenabletabonthirdparty, 'yesno', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'mesgs');
	}
}

if ($action == 'updateMask') {
	$maskconstbookkeeping = GETPOST('maskconstbookkeeping', 'aZ09');
	$maskbookkeeping = GETPOST('maskbookkeeping', 'alpha');

	$res = 0;

	if ($maskconstbookkeeping && preg_match('/_MASK$/', $maskconstbookkeeping)) {
		$res = dolibarr_set_const($db, $maskconstbookkeeping, $maskbookkeeping, 'chaine', 0, '', $conf->entity);
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

if ($action == 'setmod') {
	$value = GETPOST('value', 'alpha');
	dolibarr_set_const($db, "BOOKKEEPING_ADDON", $value, 'chaine', 0, '', $conf->entity);
}

/*
 * View
 */

$form = new Form($db);

$title = $langs->trans('ConfigAccountingExpert');
$help_url = 'EN:Module_Double_Entry_Accounting#Setup|FR:Module_Comptabilit&eacute;_en_Partie_Double#Configuration';
llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-accountancy page-admin_index');


$linkback = '';
//$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1">' . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($title, $linkback, 'accountancy');


// Show message if accountancy hidden options are activated to help to resolve some problems
if (getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS')) {
	print '<div class="info">' . $langs->trans("ConstantIsOn", "FACTURE_DEPOSITS_ARE_JUST_PAYMENTS") . '</div>';
}
if (getDolGlobalString('FACTURE_SUPPLIER_DEPOSITS_ARE_JUST_PAYMENTS')) {
	print '<div class="info">' . $langs->trans("ConstantIsOn", "FACTURE_SUPPLIER_DEPOSITS_ARE_JUST_PAYMENTS") . '</div>';
}
if (getDolGlobalString('ACCOUNTANCY_USE_PRODUCT_ACCOUNT_ON_THIRDPARTY')) {
	print '<div class="info">' . $langs->trans("ConstantIsOn", "ACCOUNTANCY_USE_PRODUCT_ACCOUNT_ON_THIRDPARTY") . '</div>';
}
if (getDolGlobalString('MAIN_COMPANY_PERENTITY_SHARED')) {
	print '<div class="info">' . $langs->trans("ConstantIsOn", "MAIN_COMPANY_PERENTITY_SHARED") . '</div>';
}
if (getDolGlobalString('MAIN_PRODUCT_PERENTITY_SHARED')) {
	print '<div class="info">' . $langs->trans("ConstantIsOn", "MAIN_PRODUCT_PERENTITY_SHARED") . '</div>';
}

print '<br>';


// Case of the parameter ACCOUNTING_MODE

if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 1) {
	print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="updatemode">';

	print '<table class="noborder centpercent">';

	print '<tr class="liste_titre">';
	print '<td colspan="2">'.$langs->trans('OptionMode').'</td>';
	print "</tr>\n";
	print '<tr class="oddeven"><td class="nowraponall"><input type="radio" id="accounting_mode_1" name="accounting_mode" value="CREANCES-DETTES"'.($accounting_mode != 'RECETTES-DEPENSES' ? ' checked' : '').'><label for="accounting_mode_1"> '.$langs->trans('OptionModeVirtual').'</label> ('.$langs->trans('Default').')</td>';
	print '<td><span class="opacitymedium">'.nl2br($langs->trans('ACCOUNTING_USE_NON_TREASURY_Desc')).'</span>';
	print "</td></tr>\n";
	print '<tr class="oddeven"><td class="nowraponall"><input type="radio" id="accounting_mode_2" name="accounting_mode" value="RECETTES-DEPENSES"'.($accounting_mode == 'RECETTES-DEPENSES' ? ' checked' : '').'><label for="accounting_mode_2"> '.$langs->trans('OptionModeTrue').'</label></td>';
	print '<td><span class="opacitymedium">'.nl2br($langs->trans('ACCOUNTING_USE_TREASURY_Desc'))."</span>";
	print "</td></tr>\n";

	print "</table>\n";

	print '<div class="center"><input type="submit" class="button button-edit" name="button" value="'.$langs->trans('Save').'"></div>';
	print '</form>';


	print '<br><br><br>';
}


// Show form main options
print $formSetup->generateOutput(true);

print '<br><br><br>';


print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update2">';
print '<input type="hidden" name="page_y" value="">';

// Binding params
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td colspan="2">'.$langs->trans('BindingOptions').'</td>';
print "</tr>\n";

// Param a user $user->hasRight('accounting', 'chartofaccount') can access
foreach ($list_binding as $key) {
	print '<tr class="oddeven value">';

	// Param
	$label = $langs->trans($key);
	print '<td>'.$label.'</td>';
	// Value
	print '<td class="right minwidth75imp parentonrightofpage">';
	if ($key == 'ACCOUNTING_DATE_START_BINDING') {
		print $form->selectDate((getDolGlobalInt($key) ? (int) getDolGlobalInt($key) : -1), $key, 0, 0, 1);
	} elseif ($key == 'ACCOUNTING_DEFAULT_PERIOD_ON_TRANSFER') {
		$array = array(0=>$langs->trans("PreviousMonth"), 1=>$langs->trans("CurrentMonth"), 2=>$langs->trans("Fiscalyear"));
		print $form->selectarray($key, $array, getDolGlobalInt('ACCOUNTING_DEFAULT_PERIOD_ON_TRANSFER', 0), 0, 0, 0, '', 0, 0, 0, '', 'onrightofpage width200');
	} elseif ($key == 'ACCOUNTING_LABEL_OPERATION_ON_TRANSFER') {
		$array = array(
			0=>$langs->trans("ThirdPartyName") . ' - ' . $langs->trans("NumPiece") . ' - ' . $langs->trans("LabelAccount"),
			1=>$langs->trans("ThirdPartyName") . ' - ' . $langs->trans("NumPiece"),
			2=>$langs->trans("ThirdPartyName")
		);
		print $form->selectarray($key, $array, getDolGlobalInt('ACCOUNTING_LABEL_OPERATION_ON_TRANSFER', 0), 0, 0, 0, '', 0, 0, 0, '', 'onrightofpage width200');
	} else {
		print '<input type="text" class="maxwidth100" id="'.$key.'" name="'.$key.'" value="'.getDolGlobalString($key).'">';
	}

	print '</td>';
	print '</tr>';
}

print '<tr class="oddeven">';
print '<td>'.$langs->trans("ACCOUNTING_DISABLE_BINDING_ON_SALES").'</td>';
if (getDolGlobalString('ACCOUNTING_DISABLE_BINDING_ON_SALES')) {
	print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?token='.newToken().'&action=setdisablebindingonsales&value=0">';
	print img_picto($langs->trans("Activated"), 'switch_on', '', 0, 0, 0, '', 'warning');
	print '</a></td>';
} else {
	print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?token='.newToken().'&action=setdisablebindingonsales&value=1">';
	print img_picto($langs->trans("Disabled"), 'switch_off');
	print '</a></td>';
}
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("ACCOUNTING_DISABLE_BINDING_ON_PURCHASES").'</td>';
if (getDolGlobalString('ACCOUNTING_DISABLE_BINDING_ON_PURCHASES')) {
	print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?token='.newToken().'&action=setdisablebindingonpurchases&value=0">';
	print img_picto($langs->trans("Activated"), 'switch_on', '', 0, 0, 0, '', 'warning');
	print '</a></td>';
} else {
	print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?token='.newToken().'&action=setdisablebindingonpurchases&value=1">';
	print img_picto($langs->trans("Disabled"), 'switch_off');
	print '</a></td>';
}
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("ACCOUNTING_DISABLE_BINDING_ON_EXPENSEREPORTS").'</td>';
if (getDolGlobalString('ACCOUNTING_DISABLE_BINDING_ON_EXPENSEREPORTS')) {
	print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?token='.newToken().'&action=setdisablebindingonexpensereports&value=0">';
	print img_picto($langs->trans("Activated"), 'switch_on', '', 0, 0, 0, '', 'warning');
	print '</a></td>';
} else {
	print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?token='.newToken().'&action=setdisablebindingonexpensereports&value=1">';
	print img_picto($langs->trans("Disabled"), 'switch_off');
	print '</a></td>';
}
print '</tr>';

if (!getDolGlobalString('ACCOUNTING_DISABLE_BINDING_ON_EXPENSEREPORTS')) {
	print '<tr class="oddeven">';
	print '<td>'.$langs->trans("ACCOUNTANCY_ER_DATE_RECORD").'</td>';
	if (getDolGlobalInt('ACCOUNTANCY_ER_DATE_RECORD')) {
		print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?token='.newToken().'&action=setACCOUNTANCY_ER_DATE_RECORD&value=0">';
		print img_picto($langs->trans("Activated"), 'switch_on');
		print '</a></td>';
	} else {
		print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?token='.newToken().'&action=setACCOUNTANCY_ER_DATE_RECORD&value=1">';
		print img_picto($langs->trans("Disabled"), 'switch_off');
		print '</a></td>';
	}
	print '</tr>';
}

print '<tr class="oddeven">';
print '<td>'.$langs->trans("ACCOUNTING_BANK_CONCILIATED").'</td>';
if (getDolGlobalInt('ACCOUNTING_BANK_CONCILIATED') == 2) {
	print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?token='.newToken().'&action=setACCOUNTING_BANK_CONCILIATED&value=1">';
	print img_picto($langs->trans("Activated"), 'switch_on');
	print '</a></td>';
} else {
	print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?token='.newToken().'&action=setACCOUNTING_BANK_CONCILIATED&value=2">';
	print img_picto($langs->trans("Disabled"), 'switch_off');
	print '</a></td>';
}
print '</tr>';

print '</table>';
print '</div>';

print '<div class="center"><input type="submit" class="button reposition" value="'.dol_escape_htmltag($langs->trans('Save')).'" name="button"></div>';

// Show numbering options
print '<br><br>';

// Accountancy Numbering model
$dirmodels = array_merge(array('/'), $conf->modules_parts['models']);

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td colspan="2">'.$langs->trans("BookkeepingNumberingModules").'</td>';
print '<td class="nowrap">'.$langs->trans("Example").'</td>';
print '<td class="center" width="60">'.$langs->trans("Status").'</td>';
print '<td class="center" width="16">'.$langs->trans("ShortInfo").'</td>';
print '</tr>'."\n";

clearstatcache();

$arrayofmodules = array();

foreach ($dirmodels as $reldir) {
	$dir = dol_buildpath($reldir."core/modules/accountancy/");

	if (is_dir($dir)) {
		$handle = opendir($dir);
		if (is_resource($handle)) {
			while (($file = readdir($handle)) !== false) {
				if (strpos($file, 'mod_bookkeeping_') === 0 && substr($file, dol_strlen($file) - 3, 3) == 'php') {
					$file = substr($file, 0, dol_strlen($file) - 4);

					require_once $dir.$file.'.php';

					$module = new $file($db);

					/** @var ModeleNumRefBookkeeping $module */
					'@phan-var-force ModeleNumRefBookkeeping $module';

					$arrayofmodules[] = $module;
				}
			}
			closedir($handle);
		}
	}
}

$arrayofmodules = dol_sort_array($arrayofmodules, 'position');

foreach ($arrayofmodules as $module) {
	$file = 'mod_bookkeeping_'.strtolower($module->getName($langs));

	// Show modules according to features level
	if ($module->version == 'development' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 2) {
		continue;
	}
	if ($module->version == 'experimental' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 1) {
		continue;
	}

	if ($module->isEnabled()) {
		print '<tr class="oddeven"><td class="width100">'.$module->name."</td><td>\n";
		print $module->info($langs);
		print '</td>';

		// Show example of the numbering model
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
		if (getDolGlobalString('BOOKKEEPING_ADDON') == $file) {
			print img_picto($langs->trans("Activated"), 'switch_on');
		} else {
			print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setmod&token='.newToken().'&value='.urlencode($file).'">';
			print img_picto($langs->trans("Disabled"), 'switch_off');
			print '</a>';
		}
		print '</td>';

		$bookkeeping = new BookKeeping($db);
		$bookkeeping->initAsSpecimen();

		// Info
		$htmltooltip = ''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';

		$nextval = $module->getNextValue($bookkeeping);
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
		print $form->textwithpicto('', $htmltooltip, 1, 'info');
		print '</td>';

		print "</tr>\n";
	}
}
print '</table>';
print '</div>';


// Show advanced options
print '<br><br>';

// Advanced params
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td colspan="2">' . $langs->trans('OptionsAdvanced') . '</td>';
print "</tr>\n";

print '<tr class="oddeven">';
print '<td>';
print $form->textwithpicto($langs->trans("ACCOUNTING_ENABLE_LETTERING"), $langs->trans("ACCOUNTING_ENABLE_LETTERING_DESC", $langs->transnoentitiesnoconv("NumMvts")).'<br>'.$langs->trans("EnablingThisFeatureIsNotNecessary")).'</td>';
if (getDolGlobalInt('ACCOUNTING_ENABLE_LETTERING')) {
	print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?token='.newToken().'&action=setenablelettering&value=0">';
	print img_picto($langs->trans("Activated"), 'switch_on');
	print '</a></td>';
} else {
	print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?token='.newToken().'&action=setenablelettering&value=1">';
	print img_picto($langs->trans("Disabled"), 'switch_off');
	print '</a></td>';
}
print '</tr>';

if (getDolGlobalInt('ACCOUNTING_ENABLE_LETTERING')) {
	// Number of letters for lettering (3 by default (AAA), min 2 (AA))
	print '<tr class="oddeven">';
	print '<td>';
	print $form->textwithpicto($langs->trans("ACCOUNTING_LETTERING_NBLETTERS"), $langs->trans("ACCOUNTING_LETTERING_NBLETTERS_DESC")) . '</td>';
	print '<td class="right">';

	if (empty($letter)) {
		if (getDolGlobalInt('ACCOUNTING_LETTERING_NBLETTERS')) {
			$nbletter = getDolGlobalInt('ACCOUNTING_LETTERING_NBLETTERS');
		} else {
			$nbletter = 3;
		}
	}

	print '<input class="flat right" name="ACCOUNTING_LETTERING_NBLETTERS" id="ACCOUNTING_LETTERING_NBLETTERS" value="' . $nbletter . '" type="number" step="1" min="2" max="3" >' . "\n";
	print '</tr>';

	// Auto Lettering when transfer in accountancy is realized
	print '<tr class="oddeven">';
	print '<td>';
	print $form->textwithpicto($langs->trans("ACCOUNTING_ENABLE_AUTOLETTERING"), $langs->trans("ACCOUNTING_ENABLE_AUTOLETTERING_DESC")) . '</td>';
	if (getDolGlobalInt('ACCOUNTING_ENABLE_AUTOLETTERING')) {
		print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setenableautolettering&value=0">';
		print img_picto($langs->trans("Activated"), 'switch_on');
		print '</a></td>';
	} else {
		print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setenableautolettering&value=1">';
		print img_picto($langs->trans("Disabled"), 'switch_off');
		print '</a></td>';
	}
	print '</tr>';
}

print '<tr class="oddeven">';
print '<td>';
print $form->textwithpicto($langs->trans("ACCOUNTING_FORCE_ENABLE_VAT_REVERSE_CHARGE"), $langs->trans("ACCOUNTING_FORCE_ENABLE_VAT_REVERSE_CHARGE_DESC", $langs->transnoentities("MenuDefaultAccounts"))).'</td>';
if (getDolGlobalString('ACCOUNTING_FORCE_ENABLE_VAT_REVERSE_CHARGE')) {
	print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setenablevatreversecharge&value=0">';
	print img_picto($langs->trans("Activated"), 'switch_on');
	print '</a></td>';
} else {
	print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setenablevatreversecharge&value=1">';
	print img_picto($langs->trans("Disabled"), 'switch_off');
	print '</a></td>';
}
print '</tr>';

print '<tr class="oddeven">';
print '<td>';
print $form->textwithpicto($langs->trans("ACCOUNTING_ENABLE_TABONTHIRDPARTY"), $langs->trans("ACCOUNTING_ENABLE_TABONTHIRDPARTY_DESC")).'</td>';
if (getDolGlobalString('ACCOUNTING_ENABLE_TABONTHIRDPARTY')) {
	print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setenabletabonthirdparty&value=0">';
	print img_picto($langs->trans("Activated"), 'switch_on');
	print '</a></td>';
} else {
	print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setenabletabonthirdparty&value=1">';
	print img_picto($langs->trans("Disabled"), 'switch_off');
	print '</a></td>';
}
print '</tr>';

print '</table>';
print '</div>';


print '<div class="center"><input type="submit" class="button button-edit reposition" name="button" value="'.$langs->trans('Save').'"></div>';

print '<br><br>';


// Export options

print "\n".'<script type="text/javascript">'."\n";
print 'jQuery(document).ready(function () {'."\n";
print '    function initfields()'."\n";
print '    {'."\n";
foreach ($listparam as $key => $param) {
	print '        if (jQuery("#ACCOUNTING_EXPORT_MODELCSV").val()=="'.$key.'")'."\n";
	print '        {'."\n";
	print '            //console.log("'.$param['label'].'");'."\n";
	if (empty($param['ACCOUNTING_EXPORT_FORMAT'])) {
		print '            jQuery("#ACCOUNTING_EXPORT_FORMAT").val("'.getDolGlobalString('ACCOUNTING_EXPORT_FORMAT').'");'."\n";
		print '            jQuery("#ACCOUNTING_EXPORT_FORMAT").prop("disabled", true);'."\n";
	} else {
		print '            jQuery("#ACCOUNTING_EXPORT_FORMAT").val("'.$param['ACCOUNTING_EXPORT_FORMAT'].'");'."\n";
		print '            jQuery("#ACCOUNTING_EXPORT_FORMAT").removeAttr("disabled");'."\n";
	}
	if (empty($param['ACCOUNTING_EXPORT_SEPARATORCSV'])) {
		print '            jQuery("#ACCOUNTING_EXPORT_SEPARATORCSV").val("");'."\n";
		print '            jQuery("#ACCOUNTING_EXPORT_SEPARATORCSV").prop("disabled", true);'."\n";
	} else {
		print '            jQuery("#ACCOUNTING_EXPORT_SEPARATORCSV").val("'.getDolGlobalString('ACCOUNTING_EXPORT_SEPARATORCSV').'");'."\n";
		print '            jQuery("#ACCOUNTING_EXPORT_SEPARATORCSV").removeAttr("disabled");'."\n";
	}
	if (empty($param['ACCOUNTING_EXPORT_ENDLINE'])) {
		print '            jQuery("#ACCOUNTING_EXPORT_ENDLINE").prop("disabled", true);'."\n";
	} else {
		print '            jQuery("#ACCOUNTING_EXPORT_ENDLINE").removeAttr("disabled");'."\n";
	}
	if (empty($param['ACCOUNTING_EXPORT_DATE'])) {
		print '            jQuery("#ACCOUNTING_EXPORT_DATE").val("");'."\n";
		print '            jQuery("#ACCOUNTING_EXPORT_DATE").prop("disabled", true);'."\n";
	} else {
		print '            jQuery("#ACCOUNTING_EXPORT_DATE").val("'.getDolGlobalString('ACCOUNTING_EXPORT_DATE').'");'."\n";
		print '            jQuery("#ACCOUNTING_EXPORT_DATE").removeAttr("disabled");'."\n";
	}
	print '        }'."\n";
}
print '    }'."\n";
print '    initfields();'."\n";
print '    jQuery("#ACCOUNTING_EXPORT_MODELCSV").change(function() {'."\n";
print '        initfields();'."\n";
print '    });'."\n";
print '})'."\n";
print '</script>'."\n";

// Main Options

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td colspan="2">'.$langs->trans('ExportOptions').'</td>';
print "</tr>\n";

$num = count($main_option);
if ($num) {
	foreach ($main_option as $key) {
		print '<tr class="oddeven value">';

		// Param
		$label = $langs->trans($key);
		print '<td>'.dol_escape_htmltag($label).'</td>';

		// Value
		print '<td>';
		print '<input type="text" size="20" id="'.$key.'" name="'.$key.'" value="'.getDolGlobalString($key).'">';
		print '</td></tr>';
	}
}

print '<tr class="oddeven">';
print '<td>'.$langs->trans("Selectmodelcsv").'</td>';
if (!$conf->use_javascript_ajax) {
	print '<td class="nowrap">';
	print $langs->trans("NotAvailableWhenAjaxDisabled");
	print "</td>";
} else {
	print '<td>';
	$listofexporttemplates = $accountancyexport->getType(1);
	print $form->selectarray("ACCOUNTING_EXPORT_MODELCSV", $listofexporttemplates, getDolGlobalString('ACCOUNTING_EXPORT_MODELCSV'), 0, 0, 0, '', 0, 0, 0, '', '', 1);
	print '</td>';
}
print "</tr>";


$num2 = count($model_option);
if ($num2) {
	foreach ($model_option as $key) {
		print '<tr class="oddeven value">';

		// Param
		$label = $key['label'];
		print '<td>'.$langs->trans($label).'</td>';

		// Value
		print '<td>';
		if (is_array($key['param'])) {
			print $form->selectarray($label, $key['param'], getDolGlobalString($label), 0);
		} else {
			print '<input type="text" size="20" id="'.$label.'" name="'.$key['label'].'" value="'.getDolGlobalString($label).'">';
		}

		print '</td></tr>';
	}

	print "</table>\n";
}

print '<div class="center"><input type="submit" class="button reposition" value="'.dol_escape_htmltag($langs->trans('Save')).'" name="button"></div>';


print '</form>';

// End of page
llxFooter();
$db->close();
