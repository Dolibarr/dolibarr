<?php
/* Copyright (C) 2005-2010  Laurent Destailleur  	<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2015  Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2013-2017  Philippe Grand			<philippe.grand@atoo-net.com>
 * Copyright (C) 2015-2020  Alexandre Spangaro		<aspangaro@open-dsi.fr>
 * Copyright (C) 2015       Benoit Bruchard			<benoitb21@gmail.com>
 * Copyright (C) 2019       Thibault FOUCART		<support@ptibogxiv.net>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 *  \file       htdocs/don/admin/donation.php
 *  \ingroup    donations
 *  \brief      Page to setup the donation module
 */
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/donation.lib.php';
require_once DOL_DOCUMENT_ROOT.'/don/class/don.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
if (isModEnabled('accounting')) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
}

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array('admin', 'donations', 'accountancy', 'other'));

if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$value = GETPOST('value');
$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');

$type = 'donation';


/*
 * Action
 */
$error = 0;

if ($action == 'specimen') {
	$modele = GETPOST('module', 'alpha');

	$don = new Don($db);
	$don->initAsSpecimen();

	// Search template files
	$dir = DOL_DOCUMENT_ROOT."/core/modules/dons/";
	$file = $modele.".modules.php";
	if ($modele !== '' && file_exists($dir.$file)) {
		require_once $dir.$file;

		$classname = (string) $modele;
		$obj = new $classname($db);
		'@phan-var-force ModeleDon $obj';

		if ($obj->write_file($don, $langs) > 0) {
			header("Location: ".DOL_URL_ROOT."/document.php?modulepart=donation&file=SPECIMEN.html");
			return;
		} else {
			setEventMessages($obj->error, $obj->errors, 'errors');
			dol_syslog($obj->error, LOG_ERR);
		}
	} else {
		setEventMessages($langs->trans("ErrorModuleNotFound"), null, 'errors');
		dol_syslog($langs->trans("ErrorModuleNotFound"), LOG_ERR);
	}
} elseif ($action == 'setdoc') {
	// Set default model
	if (dolibarr_set_const($db, "DON_ADDON_MODEL", $value, 'chaine', 0, '', $conf->entity)) {
		// The constant that was read before the new set
		// So we go through a variable for a coherent display
		$conf->global->DON_ADDON_MODEL = $value;
	}

	// It enables the model
	$ret = delDocumentModel($value, $type);
	if ($ret > 0) {
		$ret = addDocumentModel($value, $type, $label, $scandir);
	}
} elseif ($action == 'set') {
	// Activate a model
	$ret = addDocumentModel($value, $type, $label, $scandir);
} elseif ($action == 'del') {
	$ret = delDocumentModel($value, $type);
	if ($ret > 0) {
		if ($conf->global->DON_ADDON_MODEL == "$value") {
			dolibarr_del_const($db, 'DON_ADDON_MODEL', $conf->entity);
		}
	}
}

// Options
if ($action == 'set_DONATION_ACCOUNTINGACCOUNT') {
	$account = GETPOST('DONATION_ACCOUNTINGACCOUNT', 'alpha');

	$res = dolibarr_set_const($db, "DONATION_ACCOUNTINGACCOUNT", $account, 'chaine', 0, '', $conf->entity);

	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
		$action = '';	// To avoid to execute next actions
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
		$action = '';	// To avoid to execute next actions
	}
}

if ($action == 'set_DONATION_MESSAGE') {
	$freemessage = GETPOST('DONATION_MESSAGE', 'restricthtml'); // No alpha here, we want exact string

	$res = dolibarr_set_const($db, "DONATION_MESSAGE", $freemessage, 'chaine', 0, '', $conf->entity);

	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
		$action = '';	// To avoid to execute next actions
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
		$action = '';	// To avoid to execute next actions
	}
}

// Other cases
$reg = array();
if (preg_match('/set_([a-z0-9_\-]+)/i', $action, $reg)) {
	$code = $reg[1];
	if (dolibarr_set_const($db, $code, 1, 'chaine', 0, '', $conf->entity) > 0) {
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

/*
 * View
 */

$dir = "../../core/modules/dons/";
$form = new Form($db);
if (isModEnabled('accounting')) {
	$formaccounting = new FormAccounting($db);
}

$help_url = '';
llxHeader('', $langs->trans("DonationsSetup"), $help_url, '', 0, 0, '', '', '', 'mod-donation page-admin');

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("DonationsSetup"), $linkback, 'title_setup');

$head = donation_admin_prepare_head();

print dol_get_fiche_head($head, 'general', $langs->trans("Donations"), -1, 'payment');


// Document templates
print load_fiche_titre($langs->trans("DonationsModels"), '', '');

// Defined the template definition table
$type = 'donation';
$def = array();
$sql = "SELECT nom";
$sql .= " FROM ".MAIN_DB_PREFIX."document_model";
$sql .= " WHERE type = '".$db->escape($type)."'";
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
print '<td class="center" width="60">'.$langs->trans("Activated").'</td>';
print '<td class="center" width="60">'.$langs->trans("Default").'</td>';
print '<td class="center" width="80">'.$langs->trans("ShortInfo").'</td>';
print '<td class="center" width="80">'.$langs->trans("Preview").'</td>';
print "</tr>\n";

clearstatcache();

$handle = opendir($dir);

if (is_resource($handle)) {
	while (($file = readdir($handle)) !== false) {
		if (preg_match('/\.modules\.php$/i', $file)) {
			$name = substr($file, 0, dol_strlen($file) - 12);
			$classname = substr($file, 0, dol_strlen($file) - 12);

			require_once $dir.'/'.$file;
			$module = new $classname($db);
			'@phan-var-force ModeleDon $module';

			// Show modules according to features level
			if ($module->version == 'development' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 2) {
				continue;
			}
			if ($module->version == 'experimental' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 1) {
				continue;
			}

			if ($module->isEnabled()) {
				print '<tr class="oddeven"><td width=\"100\">';
				echo $module->name;
				print '</td>';
				print '<td>';
				print $module->description;
				print '</td>';

				// Active
				if (in_array($name, $def)) {
					if ($conf->global->DON_ADDON_MODEL == $name) {
						print "<td class=\"center\">\n";
						print img_picto($langs->trans("Enabled"), 'switch_on');
						print '</td>';
					} else {
						print "<td class=\"center\">\n";
						print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&token='.newToken().'&value='.urlencode($name).'&scan_dir='.urlencode($module->scandir).'&label='.urlencode($module->name).'">'.img_picto($langs->trans("Enabled"), 'switch_on').'</a>';
						print '</td>';
					}
				} else {
					print "<td class=\"center\">\n";
					print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&token='.newToken().'&value='.urlencode($name).'&scan_dir='.urlencode($module->scandir).'&label='.urlencode($module->name).'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
					print "</td>";
				}

				// Default
				if ($conf->global->DON_ADDON_MODEL == "$name") {
					print "<td class=\"center\">";
					print img_picto($langs->trans("Default"), 'on');
					print '</td>';
				} else {
					print "<td class=\"center\">";
					print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&token='.newToken().'&value='.urlencode($name).'&scan_dir='.urlencode($module->scandir).'&label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
					print '</td>';
				}

				// Info
				$htmltooltip = ''.$langs->trans("Name").': '.$module->name;
				$htmltooltip .= '<br>'.$langs->trans("Type").': '.($module->type ? $module->type : $langs->trans("Unknown"));
				if ($module->type == 'pdf') {
					$htmltooltip .= '<br>'.$langs->trans("Width").'/'.$langs->trans("Height").': '.$module->page_largeur.'/'.$module->page_hauteur;
				}
				$htmltooltip .= '<br><br><u>'.$langs->trans("FeaturesSupported").':</u>';
				$htmltooltip .= '<br>'.$langs->trans("Logo").': '.yn($module->option_logo, 1, 1);
				$htmltooltip .= '<br>'.$langs->trans("MultiLanguage").': '.yn($module->option_multilang, 1, 1);
				print '<td class="center">';
				print $form->textwithpicto('', $htmltooltip, -1, 0);
				print '</td>';

				// Preview
				print '<td class="center">';
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.$name.'" target="specimen">'.img_object($langs->trans("Preview"), 'generic').'</a>';
				print '</td>';

				print "</tr>\n";
			}
		}
	}
	closedir($handle);
}

print '</table><br>';

/*
 *  Params
 */
print load_fiche_titre($langs->trans("Options"), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td>';
print '<td width="60" class="center">'.$langs->trans("Value")."</td>\n";
print '<td></td>';
print "</tr>\n";

if (isModEnabled("societe")) {
	print '<tr class="oddeven">';
	print '<td colspan="2">';
	print $langs->trans("DonationUseThirdparties");
	print '</td>';
	print '<td class="center">';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('DONATION_USE_THIRDPARTIES');
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("DONATION_USE_THIRDPARTIES", $arrval, $conf->global->DONATION_USE_THIRDPARTIES);
	}
	print "</td>\n";
	print "</tr>\n";
}

print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.newToken().'" />';
print '<input type="hidden" name="action" value="set_DONATION_ACCOUNTINGACCOUNT" />';

print '<tr class="oddeven">';
print '<td>';
$label = $langs->trans("AccountAccounting");
print '<label for="DONATION_ACCOUNTINGACCOUNT">'.$label.'</label></td>';
print '<td class="center">';
if (isModEnabled('accounting')) {
	/** @var FormAccounting $formaccounting */
	print $formaccounting->select_account($conf->global->DONATION_ACCOUNTINGACCOUNT, 'DONATION_ACCOUNTINGACCOUNT', 1, array(), 1, 1);
} else {
	print '<input type="text" size="10" id="DONATION_ACCOUNTINGACCOUNT" name="DONATION_ACCOUNTINGACCOUNT" value="' . getDolGlobalString('DONATION_ACCOUNTINGACCOUNT').'">';
}
print '</td><td class="center">';
print '<input type="submit" class="button button-edit" value="'.$langs->trans("Modify").'" />';
print "</td></tr>\n";
print '</form>';

print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.newToken().'" />';
print '<input type="hidden" name="action" value="set_DONATION_MESSAGE" />';

print '<tr class="oddeven"><td colspan="2">';
print $langs->trans("FreeTextOnDonations").' '.img_info($langs->trans("AddCRIfTooLong")).'<br>';
print '<textarea name="DONATION_MESSAGE" class="flat" cols="80">' . getDolGlobalString('DONATION_MESSAGE').'</textarea>';
print '</td><td class="center">';
print '<input type="submit" class="button button-edit" value="'.$langs->trans("Modify").'" />';
print "</td></tr>\n";

print "</table>\n";
print '</form>';

/*
 *  French params
 */
if (preg_match('/fr/i', $conf->global->MAIN_INFO_SOCIETE_COUNTRY)) {
	print '<br>';
	print load_fiche_titre($langs->trans("FrenchOptions"), '', '');

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Parameters").'</td>'."\n";
	print '<td class="center">'.$langs->trans("Status").'</td>'."\n";
	print "</tr>\n";

	print '<tr class="oddeven">';
	print '<td width="80%">'.$langs->trans("DONATION_ART200").'</td>';
	print '<td class="center">';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('DONATION_ART200');
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("DONATION_ART200", $arrval, $conf->global->DONATION_ART200);
	}
	print '</td></tr>';

	print '<tr class="oddeven">';
	print '<td width="80%">'.$langs->trans("DONATION_ART238").'</td>';
	print '<td class="center">';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('DONATION_ART238');
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("DONATION_ART238", $arrval, $conf->global->DONATION_ART238);
	}
	print '</td></tr>';

	print '<tr class="oddeven">';
	print '<td width="80%">'.$langs->trans("DONATION_ART978").'</td>';
	print '<td class="center">';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('DONATION_ART978');
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("DONATION_ART978", $arrval, $conf->global->DONATION_ART978);
	}
	print '</td></tr>';
	print "</table>\n";
}

llxFooter();

$db->close();
