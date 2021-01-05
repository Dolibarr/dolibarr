<?php
/* Copyright (C) 2001-2007	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2019	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2017	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2010-2014	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2011-2017	Philippe Grand			<philippe.grand@atoo-net.com>
 * Copyright (C) 2015		Alexandre Spangaro		<aspangaro@open-dsi.fr>
 * Copyright (C) 2017       Rui Strecht			    <rui.strecht@aliartalentos.com>
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
 *	\file       htdocs/admin/company.php
 *	\ingroup    company
 *	\brief      Setup page to configure company/foundation
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

$action = GETPOST('action', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'admincompany'; // To manage different context of search

// Load translation files required by the page
$langs->loadLangs(array('admin', 'companies', 'bills'));

if (!$user->admin) accessforbidden();

$error = 0;

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('admincompany', 'globaladmin'));


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (($action == 'update' && !GETPOST("cancel", 'alpha'))
|| ($action == 'updateedit'))
{
	$tmparray = getCountry(GETPOST('country_id', 'int'), 'all', $db, $langs, 0);
	if (!empty($tmparray['id']))
	{
		$mysoc->country_id   = $tmparray['id'];
		$mysoc->country_code = $tmparray['code'];
		$mysoc->country_label = $tmparray['label'];

		$s = $mysoc->country_id.':'.$mysoc->country_code.':'.$mysoc->country_label;
		dolibarr_set_const($db, "MAIN_INFO_SOCIETE_COUNTRY", $s, 'chaine', 0, '', $conf->entity);

		activateModulesRequiredByCountry($mysoc->country_code);
	}

	$tmparray = getState(GETPOST('state_id', 'int'), 'all', $db, $langs, 0);
	if (!empty($tmparray['id']))
	{
		$mysoc->state_id   = $tmparray['id'];
		$mysoc->state_code = $tmparray['code'];
		$mysoc->state_label = $tmparray['label'];

		$s = $mysoc->state_id.':'.$mysoc->state_code.':'.$mysoc->state_label;
		dolibarr_set_const($db, "MAIN_INFO_SOCIETE_STATE", $s, 'chaine', 0, '', $conf->entity);
	} else {
		dolibarr_del_const($db, "MAIN_INFO_SOCIETE_STATE", $conf->entity);
	}

	$db->begin();

	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_NOM", GETPOST("nom", 'nohtml'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_ADDRESS", GETPOST("MAIN_INFO_SOCIETE_ADDRESS", 'nohtml'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_TOWN", GETPOST("MAIN_INFO_SOCIETE_TOWN", 'nohtml'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_ZIP", GETPOST("MAIN_INFO_SOCIETE_ZIP", 'alphanohtml'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_REGION", GETPOST("region_code", 'alphanohtml'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_MONNAIE", GETPOST("currency", 'aZ09'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_TEL", GETPOST("tel", 'alphanohtml'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_FAX", GETPOST("fax", 'alphanohtml'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_MAIL", GETPOST("mail", 'alphanohtml'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_WEB", GETPOST("web", 'alphanohtml'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_NOTE", GETPOST("note", 'restricthtml'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_GENCOD", GETPOST("barcode", 'alphanohtml'), 'chaine', 0, '', $conf->entity);

	$dirforimage = $conf->mycompany->dir_output.'/logos/';

	$arrayofimages = array('logo', 'logo_squarred');

	foreach ($arrayofimages as $varforimage)
	{
		if ($_FILES[$varforimage]["name"] && !preg_match('/(\.jpeg|\.jpg|\.png)$/i', $_FILES[$varforimage]["name"])) {	// Logo can be used on a lot of different places. Only jpg and png can be supported.
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorBadImageFormat"), null, 'errors');
			break;
		}

		if ($_FILES[$varforimage]["tmp_name"])
		{
			$reg = array();
			if (preg_match('/([^\\/:]+)$/i', $_FILES[$varforimage]["name"], $reg))
			{
				$original_file = $reg[1];

				$isimage = image_format_supported($original_file);
				if ($isimage >= 0)
				{
					dol_syslog("Move file ".$_FILES[$varforimage]["tmp_name"]." to ".$dirforimage.$original_file);
					if (!is_dir($dirforimage))
					{
						dol_mkdir($dirforimage);
					}
					$result = dol_move_uploaded_file($_FILES[$varforimage]["tmp_name"], $dirforimage.$original_file, 1, 0, $_FILES[$varforimage]['error']);
					if ($result > 0)
					{
						$constant = "MAIN_INFO_SOCIETE_LOGO";
						if ($varforimage == 'logo_squarred') $constant = "MAIN_INFO_SOCIETE_LOGO_SQUARRED";

						dolibarr_set_const($db, $constant, $original_file, 'chaine', 0, '', $conf->entity);

						// Create thumbs of logo (Note that PDF use original file and not thumbs)
						if ($isimage > 0)
						{
							// Create thumbs
							//$object->addThumbs($newfile);    // We can't use addThumbs here yet because we need name of generated thumbs to add them into constants. TODO Check if need such constants. We should be able to retrieve value with get...

							// Create small thumb, Used on logon for example
							$imgThumbSmall = vignette($dirforimage.$original_file, $maxwidthsmall, $maxheightsmall, '_small', $quality);
							if (image_format_supported($imgThumbSmall) >= 0 && preg_match('/([^\\/:]+)$/i', $imgThumbSmall, $reg))
							{
								$imgThumbSmall = $reg[1]; // Save only basename
								dolibarr_set_const($db, $constant."_SMALL", $imgThumbSmall, 'chaine', 0, '', $conf->entity);
							} else dol_syslog($imgThumbSmall);

							// Create mini thumb, Used on menu or for setup page for example
							$imgThumbMini = vignette($dirforimage.$original_file, $maxwidthmini, $maxheightmini, '_mini', $quality);
							if (image_format_supported($imgThumbMini) >= 0 && preg_match('/([^\\/:]+)$/i', $imgThumbMini, $reg))
							{
								$imgThumbMini = $reg[1]; // Save only basename
								dolibarr_set_const($db, $constant."_MINI", $imgThumbMini, 'chaine', 0, '', $conf->entity);
							} else dol_syslog($imgThumbMini);
						} else dol_syslog("ErrorImageFormatNotSupported", LOG_WARNING);
					} elseif (preg_match('/^ErrorFileIsInfectedWithAVirus/', $result)) {
						$error++;
						$langs->load("errors");
						$tmparray = explode(':', $result);
						setEventMessages($langs->trans('ErrorFileIsInfectedWithAVirus', $tmparray[1]), null, 'errors');
					} else {
						$error++;
						setEventMessages($langs->trans("ErrorFailedToSaveFile"), null, 'errors');
					}
				} else {
					$error++;
					$langs->load("errors");
					setEventMessages($langs->trans("ErrorBadImageFormat"), null, 'errors');
				}
			}
		}
	}

	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_MANAGERS", GETPOST("MAIN_INFO_SOCIETE_MANAGERS", 'nohtml'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_GDPR", GETPOST("MAIN_INFO_GDPR", 'nohtml'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_CAPITAL", GETPOST("capital", 'nohtml'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_FORME_JURIDIQUE", GETPOST("forme_juridique_code", 'nohtml'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SIREN", GETPOST("siren", 'nohtml'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SIRET", GETPOST("siret", 'nohtml'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_APE", GETPOST("ape", 'nohtml'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_RCS", GETPOST("rcs", 'nohtml'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_PROFID5", GETPOST("MAIN_INFO_PROFID5", 'nohtml'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_PROFID6", GETPOST("MAIN_INFO_PROFID6", 'nohtml'), 'chaine', 0, '', $conf->entity);

	dolibarr_set_const($db, "MAIN_INFO_TVAINTRA", GETPOST("tva", 'nohtml'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_OBJECT", GETPOST("object", 'nohtml'), 'chaine', 0, '', $conf->entity);

	dolibarr_set_const($db, "SOCIETE_FISCAL_MONTH_START", GETPOST("SOCIETE_FISCAL_MONTH_START", 'int'), 'chaine', 0, '', $conf->entity);

	// Sale tax options
	$usevat = GETPOST("optiontva", 'aZ09');
	$uselocaltax1 = GETPOST("optionlocaltax1", 'aZ09');
	$uselocaltax2 = GETPOST("optionlocaltax2", 'aZ09');
	if ($uselocaltax1 == 'localtax1on' && !$usevat)
	{
		setEventMessages($langs->trans("IfYouUseASecondTaxYouMustSetYouUseTheMainTax"), null, 'errors');
		$error++;
	}
	if ($uselocaltax2 == 'localtax2on' && !$usevat)
	{
		setEventMessages($langs->trans("IfYouUseAThirdTaxYouMustSetYouUseTheMainTax"), null, 'errors');
		$error++;
	}

	dolibarr_set_const($db, "FACTURE_TVAOPTION", $usevat, 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "FACTURE_LOCAL_TAX1_OPTION", $uselocaltax1, 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "FACTURE_LOCAL_TAX2_OPTION", $uselocaltax2, 'chaine', 0, '', $conf->entity);

	if ($_POST["optionlocaltax1"] == "localtax1on")
	{
		if (!isset($_REQUEST['lt1']))
		{
			dolibarr_set_const($db, "MAIN_INFO_VALUE_LOCALTAX1", 0, 'chaine', 0, '', $conf->entity);
		} else {
			dolibarr_set_const($db, "MAIN_INFO_VALUE_LOCALTAX1", GETPOST('lt1', 'aZ09'), 'chaine', 0, '', $conf->entity);
		}
		dolibarr_set_const($db, "MAIN_INFO_LOCALTAX_CALC1", GETPOST("clt1", 'aZ09'), 'chaine', 0, '', $conf->entity);
	}
	if ($_POST["optionlocaltax2"] == "localtax2on")
	{
		if (!isset($_REQUEST['lt2']))
		{
			dolibarr_set_const($db, "MAIN_INFO_VALUE_LOCALTAX2", 0, 'chaine', 0, '', $conf->entity);
		} else {
			dolibarr_set_const($db, "MAIN_INFO_VALUE_LOCALTAX2", GETPOST('lt2', 'aZ09'), 'chaine', 0, '', $conf->entity);
		}
		dolibarr_set_const($db, "MAIN_INFO_LOCALTAX_CALC2", GETPOST("clt2", 'aZ09'), 'chaine', 0, '', $conf->entity);
	}

	if (!$error)
	{
		$db->commit();
	} else {
		$db->rollback();
	}

	if ($action != 'updateedit' && !$error)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
}

if ($action == 'addthumb' || $action == 'addthumbsquarred')  // Regenerate thumbs
{
	if (file_exists($conf->mycompany->dir_output.'/logos/'.$_GET["file"]))
	{
		$isimage = image_format_supported($_GET["file"]);

		// Create thumbs of logo
		if ($isimage > 0)
		{
			$constant = "MAIN_INFO_SOCIETE_LOGO";
			if ($action == 'addthumbsquarred') $constant = "MAIN_INFO_SOCIETE_LOGO_SQUARRED";

			$reg = array();

			// Create thumbs
			//$object->addThumbs($newfile);    // We can't use addThumbs here yet because we need name of generated thumbs to add them into constants. TODO Check if need such constants. We should be able to retrieve value with get...

			// Create small thumb. Used on logon for example
			$imgThumbSmall = vignette($conf->mycompany->dir_output.'/logos/'.$_GET["file"], $maxwidthsmall, $maxheightsmall, '_small', $quality);
			if (image_format_supported($imgThumbSmall) >= 0 && preg_match('/([^\\/:]+)$/i', $imgThumbSmall, $reg))
			{
				$imgThumbSmall = $reg[1]; // Save only basename
				dolibarr_set_const($db, $constant."_SMALL", $imgThumbSmall, 'chaine', 0, '', $conf->entity);
			} else dol_syslog($imgThumbSmall);

			// Create mini thumbs. Used on menu or for setup page for example
			$imgThumbMini = vignette($conf->mycompany->dir_output.'/logos/'.$_GET["file"], $maxwidthmini, $maxheightmini, '_mini', $quality);
			if (image_format_supported($imgThumbSmall) >= 0 && preg_match('/([^\\/:]+)$/i', $imgThumbMini, $reg))
			{
				$imgThumbMini = $reg[1]; // Save only basename
				dolibarr_set_const($db, $constant."_MINI", $imgThumbMini, 'chaine', 0, '', $conf->entity);
			} else dol_syslog($imgThumbMini);

			header("Location: ".$_SERVER["PHP_SELF"]);
			exit;
		} else {
			$error++;
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorBadImageFormat"), null, 'errors');
			dol_syslog($langs->transnoentities("ErrorBadImageFormat"), LOG_INFO);
		}
	} else {
		$error++;
		$langs->load("errors");
		setEventMessages($langs->trans("ErrorFileDoesNotExists", $_GET["file"]), null, 'errors');
		dol_syslog($langs->transnoentities("ErrorFileDoesNotExists", $_GET["file"]), LOG_WARNING);
	}
}


if ($action == 'removelogo' || $action == 'removelogosquarred')
{
	$constant = "MAIN_INFO_SOCIETE_LOGO";
	if ($action == 'removelogosquarred') $constant = "MAIN_INFO_SOCIETE_LOGO_SQUARRED";

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	$logofilename = $mysoc->logo;
	$logofilenamebis = $mysoc->logo_squarred;
	if ($action == 'removelogosquarred')
	{
		$logofilename = $mysoc->logo_squarred;
		$logofilenamebis = $mysoc->logo;
	}

	$logofile = $conf->mycompany->dir_output.'/logos/'.$logofilename;
	if ($logofilename != '' && $logofilename != $logofilenamebis) dol_delete_file($logofile);
	dolibarr_del_const($db, $constant, $conf->entity);
	if ($action == 'removelogosquarred') $mysoc->logo_squarred = '';
	else $mysoc->logo = '';

	$logofilename = $mysoc->logo_small;
	$logofilenamebis = $mysoc->logo_squarred_small;
	if ($action == 'removelogosquarred')
	{
		$logofilename = $mysoc->logo_squarred_small;
		$logofilenamebis = $mysoc->logo_small;
	}

	$logosmallfile = $conf->mycompany->dir_output.'/logos/thumbs/'.$logofilename;
	if ($logofilename != '' && $logofilename != $logofilenamebis) dol_delete_file($logosmallfile);
	dolibarr_del_const($db, $constant."_SMALL", $conf->entity);
	if ($action == 'removelogosquarred') $mysoc->logo_squarred_small = '';
	else $mysoc->logo_small = '';

	$logofilename = $mysoc->logo_mini;
	$logofilenamebis = $mysoc->logo_squarred_mini;
	if ($action == 'removelogosquarred')
	{
		$logofilename = $mysoc->logo_squarred_mini;
		$logofilenamebis = $mysoc->logo_mini;
	}

	$logominifile = $conf->mycompany->dir_output.'/logos/thumbs/'.$logofilename;
	if ($logofilename != '' && $logofilename != $logofilenamebis) dol_delete_file($logominifile);
	dolibarr_del_const($db, $constant."_MINI", $conf->entity);
	if ($action == 'removelogosquarred') $mysoc->logo_squarred_mini = '';
	else $mysoc->logo_mini = '';
}


/*
 * View
 */

$wikihelp = 'EN:First_setup|FR:Premiers_paramétrages|ES:Primeras_configuraciones';
llxHeader('', $langs->trans("Setup"), $wikihelp);

$form = new Form($db);
$formother = new FormOther($db);
$formcompany = new FormCompany($db);

$countrynotdefined = '<font class="error">'.$langs->trans("ErrorSetACountryFirst").' ('.$langs->trans("SeeAbove").')</font>';

print load_fiche_titre($langs->trans("CompanyFoundation"), '', 'title_setup');

$head = company_admin_prepare_head();

print dol_get_fiche_head($head, 'company', $langs->trans("Company"), -1, 'company');

print '<span class="opacitymedium">'.$langs->trans("CompanyFundationDesc", $langs->transnoentities("Save"))."</span><br>\n";
print "<br>\n";


/**
 * Edit parameters
 */
print "\n".'<script type="text/javascript" language="javascript">';
print '$(document).ready(function () {
		  $("#selectcountry_id").change(function() {
			document.form_index.action.value="updateedit";
			document.form_index.submit();
		  });
	  });';
print '</script>'."\n";

print '<form enctype="multipart/form-data" method="POST" action="'.$_SERVER["PHP_SELF"].'" name="form_index">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

print '<table class="noborder centpercent editmode">';
print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("CompanyInfo").'</th><th>'.$langs->trans("Value").'</th></tr>'."\n";

// Name
print '<tr class="oddeven"><td class="fieldrequired wordbreak"><label for="name">'.$langs->trans("CompanyName").'</label></td><td>';
print '<input name="nom" id="name" class="minwidth200" value="'.dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_NOM ? $conf->global->MAIN_INFO_SOCIETE_NOM : GETPOST("nom", 'nohtml')).'"'.(empty($conf->global->MAIN_INFO_SOCIETE_NOM) ? ' autofocus="autofocus"' : '').'></td></tr>'."\n";

// Address
print '<tr class="oddeven"><td><label for="MAIN_INFO_SOCIETE_ADDRESS">'.$langs->trans("CompanyAddress").'</label></td><td>';
print '<textarea name="MAIN_INFO_SOCIETE_ADDRESS" id="MAIN_INFO_SOCIETE_ADDRESS" class="quatrevingtpercent" rows="'.ROWS_3.'">'.($conf->global->MAIN_INFO_SOCIETE_ADDRESS ? $conf->global->MAIN_INFO_SOCIETE_ADDRESS : GETPOST("MAIN_INFO_SOCIETE_ADDRESS", 'nohtml')).'</textarea></td></tr>'."\n";

// Zip
print '<tr class="oddeven"><td><label for="MAIN_INFO_SOCIETE_ZIP">'.$langs->trans("CompanyZip").'</label></td><td>';
print '<input class="width100" name="MAIN_INFO_SOCIETE_ZIP" id="MAIN_INFO_SOCIETE_ZIP" value="'.dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_ZIP ? $conf->global->MAIN_INFO_SOCIETE_ZIP : GETPOST("MAIN_INFO_SOCIETE_ZIP", 'alpha')).'"></td></tr>'."\n";

print '<tr class="oddeven"><td><label for="MAIN_INFO_SOCIETE_TOWN">'.$langs->trans("CompanyTown").'</label></td><td>';
print '<input name="MAIN_INFO_SOCIETE_TOWN" class="minwidth200" id="MAIN_INFO_SOCIETE_TOWN" value="'.dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_TOWN ? $conf->global->MAIN_INFO_SOCIETE_TOWN : GETPOST("MAIN_INFO_SOCIETE_TOWN", 'nohtml')).'"></td></tr>'."\n";

// Country
print '<tr class="oddeven"><td class="fieldrequired"><label for="selectcountry_id">'.$langs->trans("Country").'</label></td><td class="maxwidthonsmartphone">';
print img_picto('', 'globe-americas', 'class="paddingrightonly"');
print $form->select_country($mysoc->country_id, 'country_id');
if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
print '</td></tr>'."\n";

print '<tr class="oddeven"><td><label for="state_id">'.$langs->trans("State").'</label></td><td class="maxwidthonsmartphone">';
$state_id = 0;
if (!empty($conf->global->MAIN_INFO_SOCIETE_STATE))
{
	$tmp = explode(':', $conf->global->MAIN_INFO_SOCIETE_STATE);
	$state_id = $tmp[0];
}
$formcompany->select_departement($state_id, $mysoc->country_code, 'state_id');
print '</td></tr>'."\n";

// Currency
print '<tr class="oddeven"><td><label for="currency">'.$langs->trans("CompanyCurrency").'</label></td><td>';
print $form->selectCurrency($conf->currency, "currency");
print '</td></tr>'."\n";

// Phone
print '<tr class="oddeven"><td><label for="phone">'.$langs->trans("Phone").'</label></td><td>';
print img_picto('', 'object_phoning', '', false, 0, 0, '', 'paddingright');
print '<input name="tel" id="phone" value="'.dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_TEL).'"></td></tr>';
print '</td></tr>'."\n";

// Fax
print '<tr class="oddeven"><td><label for="fax">'.$langs->trans("Fax").'</label></td><td>';
print img_picto('', 'object_phoning_fax', '', false, 0, 0, '', 'paddingright');
print '<input name="fax" id="fax" value="'.dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_FAX).'"></td></tr>';
print '</td></tr>'."\n";

// Email
print '<tr class="oddeven"><td><label for="email">'.$langs->trans("EMail").'</label></td><td>';
print img_picto('', 'object_email', '', false, 0, 0, '', 'paddingright');
print '<input name="mail" id="email" class="minwidth200" value="'.dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_MAIL).'"></td></tr>';
print '</td></tr>'."\n";

// Web
print '<tr class="oddeven"><td><label for="web">'.$langs->trans("Web").'</label></td><td>';
print img_picto('', 'globe', '', false, 0, 0, '', 'paddingright');
print '<input name="web" id="web" class="minwidth300" value="'.dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_WEB).'"></td></tr>';
print '</td></tr>'."\n";

// Barcode
if (!empty($conf->barcode->enabled)) {
	print '<tr class="oddeven"><td>';
	print '<label for="barcode">'.$langs->trans("Gencod").'</label></td><td>';
	print '<span class="fa paddingright fa-barcode"></span>';
	print '<input name="barcode" id="barcode" class="minwidth150" value="'.dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_GENCOD).'"></td></tr>';
	print '</td></tr>';
}

// Logo
print '<tr class="oddeven"><td><label for="logo">'.$form->textwithpicto($langs->trans("Logo"), 'png, jpg').'</label></td><td>';
print '<div class="centpertent nobordernopadding valignmiddle "><div class="inline-block marginrightonly">';
print '<input type="file" class="flat minwidth200" name="logo" id="logo" accept="image/*">';
print '</div>';
if (!empty($mysoc->logo_small)) {
	if (file_exists($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_small)) {
		print '<div class="inline-block valignmiddle">';
		print '<img style="max-height: 80px" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&amp;file='.urlencode('logos/thumbs/'.$mysoc->logo_small).'">';
		print '</div>';
	} elseif (!empty($mysoc->logo)) {
	    if (!file_exists($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_mini)) {
	        $imgThumbMini = vignette($conf->mycompany->dir_output.'/logos/'.$mysoc->logo, $maxwidthmini, $maxheightmini, '_mini', $quality);
	    }
	    $imgThumbSmall = vignette($conf->mycompany->dir_output.'/logos/'.$mysoc->logo, $maxwidthmini, $maxheightmini, '_small', $quality);
	    print '<div class="inline-block valignmiddle">';
	    print '<img style="max-height: 80px" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&amp;file='.urlencode('logos/thumbs/'.basename($imgThumbSmall)).'">';
	    print '</div>';
	}
	print '<div class="inline-block valignmiddle marginrightonly"><a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=removelogo">'.img_delete($langs->trans("Delete"), '', 'marginleftonly').'</a></div>';
} elseif (!empty($mysoc->logo)) {
	if (file_exists($conf->mycompany->dir_output.'/logos/'.$mysoc->logo)) {
		print '<div class="inline-block valignmiddle">';
		print '<img style="max-height: 80px" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&amp;file='.urlencode('logos/'.$mysoc->logo).'">';
		print '</div>';
		print '<div class="inline-block valignmiddle marginrightonly"><a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=removelogo">'.img_delete($langs->trans("Delete"), '', 'marginleftonly').'</a></div>';
	} else {
		print '<div class="inline-block valignmiddle">';
		print '<img height="80" src="'.DOL_URL_ROOT.'/public/theme/common/nophoto.png">';
		print '</div>';
	}
}
print '</div>';
print '</td></tr>';

// Logo (squarred)
print '<tr class="oddeven"><td><label for="logo_squarred">'.$form->textwithpicto($langs->trans("LogoSquarred"), 'png, jpg').'</label></td><td>';
print '<div class="centpertent nobordernopadding valignmiddle"><div class="inline-block marginrightonly">';
print '<input type="file" class="flat minwidth200" name="logo_squarred" id="logo_squarred" accept="image/*">';
print '</div>';
if (!empty($mysoc->logo_squarred_small)) {
	if (file_exists($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_squarred_small)) {
		print '<div class="inline-block valignmiddle marginrightonly">';
		print '<img style="max-height: 80px" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&amp;file='.urlencode('logos/thumbs/'.$mysoc->logo_squarred_small).'">';
		print '</div>';
	} elseif (!empty($mysoc->logo_squarred)) {
	    if (!file_exists($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_squarred_mini)) {
	        $imgThumbMini = vignette($conf->mycompany->dir_output.'/logos/'.$mysoc->logo_squarred, $maxwidthmini, $maxheightmini, '_mini', $quality);
	    }
	    $imgThumbSmall = vignette($conf->mycompany->dir_output.'/logos/'.$mysoc->logo_squarred, $maxwidthmini, $maxheightmini, '_small', $quality);
	    print '<div class="inline-block valignmiddle">';
	    print '<img style="max-height: 80px" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&amp;file='.urlencode('logos/thumbs/'.basename($imgThumbSmall)).'">';
	    print '</div>';
	}
	print '<div class="inline-block valignmiddle marginrightonly"><a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=removelogosquarred">'.img_delete($langs->trans("Delete"), '', 'marginleftonly').'</a></div>';
} elseif (!empty($mysoc->logo_squarred)) {
	if (file_exists($conf->mycompany->dir_output.'/logos/'.$mysoc->logo_squarred)) {
		print '<div class="inline-block valignmiddle">';
		print '<img style="max-height: 80px" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&amp;file='.urlencode('logos/'.$mysoc->logo_squarred).'">';
		print '</div>';
		print '<div class="inline-block valignmiddle marginrightonly"><a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=removelogosquarred">'.img_delete($langs->trans("Delete"), '', 'marginleftonly').'</a></div>';
	}
	else {
		print '<div class="inline-block valignmiddle">';
		print '<img height="80" src="'.DOL_URL_ROOT.'/public/theme/common/nophoto.png">';
		print '</div>';
	}
}
print '</div>';
print '</td></tr>';

// Note
print '<tr class="oddeven"><td class="tdtop"><label for="note">'.$langs->trans("Note").'</label></td><td>';
print '<textarea class="flat quatrevingtpercent" name="note" id="note" rows="'.ROWS_5.'">'.(GETPOSTISSET('note') ? GETPOST('note', 'restricthtml') : $conf->global->MAIN_INFO_SOCIETE_NOTE).'</textarea></td></tr>';
print '</td></tr>';

print '</table>';

print '<br>';

// IDs of the company (country-specific)
print '<table class="noborder centpercent editmode">';
print '<tr class="liste_titre"><td class="titlefield">'.$langs->trans("CompanyIds").'</td><td>'.$langs->trans("Value").'</td></tr>';

$langs->load("companies");

// Managing Director(s)
print '<tr class="oddeven"><td><label for="director">'.$langs->trans("ManagingDirectors").'</label></td><td>';
print '<input name="MAIN_INFO_SOCIETE_MANAGERS" id="directors" class="minwidth200" value="'.dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_MANAGERS).'"></td></tr>';

// GDPR contact
print '<tr class="oddeven"><td>';
print $form->textwithpicto($langs->trans("GDPRContact"), $langs->trans("GDPRContactDesc"));
print '</td><td>';
print '<input name="MAIN_INFO_GDPR" id="infodirector" class="minwidth500" value="'.dol_escape_htmltag($conf->global->MAIN_INFO_GDPR).'"></td></tr>';

// Capital
print '<tr class="oddeven"><td><label for="capital">'.$langs->trans("Capital").'</label></td><td>';
print '<input name="capital" id="capital" class="maxwidth100" value="'.dol_escape_htmltag($conf->global->MAIN_INFO_CAPITAL).'"></td></tr>';

// Juridical Status
print '<tr class="oddeven"><td><label for="forme_juridique_code">'.$langs->trans("JuridicalStatus").'</label></td><td>';
if ($mysoc->country_code) {
	print $formcompany->select_juridicalstatus($conf->global->MAIN_INFO_SOCIETE_FORME_JURIDIQUE, $mysoc->country_code, '', 'forme_juridique_code');
} else {
	print $countrynotdefined;
}
print '</td></tr>';

// ProfId1
if ($langs->transcountry("ProfId1", $mysoc->country_code) != '-')
{
	print '<tr class="oddeven"><td><label for="profid1">'.$langs->transcountry("ProfId1", $mysoc->country_code).'</label></td><td>';
	if (!empty($mysoc->country_code))
	{
		print '<input name="siren" id="profid1" class="minwidth200" value="'.dol_escape_htmltag(!empty($conf->global->MAIN_INFO_SIREN) ? $conf->global->MAIN_INFO_SIREN : '').'">';
	} else {
		print $countrynotdefined;
	}
	print '</td></tr>';
}

// ProfId2
if ($langs->transcountry("ProfId2", $mysoc->country_code) != '-')
{
	print '<tr class="oddeven"><td><label for="profid2">'.$langs->transcountry("ProfId2", $mysoc->country_code).'</label></td><td>';
	if (!empty($mysoc->country_code))
	{
		print '<input name="siret" id="profid2" class="minwidth200" value="'.dol_escape_htmltag(!empty($conf->global->MAIN_INFO_SIRET) ? $conf->global->MAIN_INFO_SIRET : '').'">';
	} else {
		print $countrynotdefined;
	}
	print '</td></tr>';
}

// ProfId3
if ($langs->transcountry("ProfId3", $mysoc->country_code) != '-')
{
	print '<tr class="oddeven"><td><label for="profid3">'.$langs->transcountry("ProfId3", $mysoc->country_code).'</label></td><td>';
	if (!empty($mysoc->country_code))
	{
		print '<input name="ape" id="profid3" class="minwidth200" value="'.dol_escape_htmltag(!empty($conf->global->MAIN_INFO_APE) ? $conf->global->MAIN_INFO_APE : '').'">';
	} else {
		print $countrynotdefined;
	}
	print '</td></tr>';
}

// ProfId4
if ($langs->transcountry("ProfId4", $mysoc->country_code) != '-')
{
	print '<tr class="oddeven"><td><label for="profid4">'.$langs->transcountry("ProfId4", $mysoc->country_code).'</label></td><td>';
	if (!empty($mysoc->country_code))
	{
		print '<input name="rcs" id="profid4" class="minwidth200" value="'.dol_escape_htmltag(!empty($conf->global->MAIN_INFO_RCS) ? $conf->global->MAIN_INFO_RCS : '').'">';
	} else {
		print $countrynotdefined;
	}
	print '</td></tr>';
}

// ProfId5
if ($langs->transcountry("ProfId5", $mysoc->country_code) != '-')
{
	print '<tr class="oddeven"><td><label for="profid5">'.$langs->transcountry("ProfId5", $mysoc->country_code).'</label></td><td>';
	if (!empty($mysoc->country_code))
	{
		print '<input name="MAIN_INFO_PROFID5" id="profid5" class="minwidth200" value="'.dol_escape_htmltag(!empty($conf->global->MAIN_INFO_PROFID5) ? $conf->global->MAIN_INFO_PROFID5 : '').'">';
	} else {
		print $countrynotdefined;
	}
	print '</td></tr>';
}

// ProfId6
if ($langs->transcountry("ProfId6", $mysoc->country_code) != '-')
{
	print '<tr class="oddeven"><td><label for="profid6">'.$langs->transcountry("ProfId6", $mysoc->country_code).'</label></td><td>';
	if (!empty($mysoc->country_code))
	{
		print '<input name="MAIN_INFO_PROFID6" id="profid6" class="minwidth200" value="'.dol_escape_htmltag(!empty($conf->global->MAIN_INFO_PROFID6) ? $conf->global->MAIN_INFO_PROFID6 : '').'">';
	} else {
		print $countrynotdefined;
	}
	print '</td></tr>';
}

// Intra-community VAT number
print '<tr class="oddeven"><td><label for="intra_vat">'.$langs->trans("VATIntra").'</label></td><td>';
print '<input name="tva" id="intra_vat" class="minwidth200" value="'.dol_escape_htmltag(!empty($conf->global->MAIN_INFO_TVAINTRA) ? $conf->global->MAIN_INFO_TVAINTRA : '').'">';
print '</td></tr>';

// Object of the company
print '<tr class="oddeven"><td><label for="object">'.$langs->trans("CompanyObject").'</label></td><td>';
print '<textarea class="flat quatrevingtpercent" name="object" id="object" rows="'.ROWS_5.'">'.(!empty($conf->global->MAIN_INFO_SOCIETE_OBJECT) ? $conf->global->MAIN_INFO_SOCIETE_OBJECT : '').'</textarea></td></tr>';
print '</td></tr>';

print '</table>';


// Fiscal year start
print '<br>';
print '<table class="noborder centpercent editmode">';
print '<tr class="liste_titre">';
print '<td class="titlefield">'.$langs->trans("FiscalYearInformation").'</td><td>'.$langs->trans("Value").'</td>';
print "</tr>\n";

print '<tr class="oddeven"><td><label for="SOCIETE_FISCAL_MONTH_START">'.$langs->trans("FiscalMonthStart").'</label></td><td>';
print $formother->select_month($conf->global->SOCIETE_FISCAL_MONTH_START, 'SOCIETE_FISCAL_MONTH_START', 0, 1, 'maxwidth100').'</td></tr>';

print "</table>";
print '<br>';


// Sales taxes (VAT, IRPF, ...)
print load_fiche_titre($langs->trans("TypeOfSaleTaxes"), '', 'object_payment');

print '<table class="noborder centpercent editmode">';
print '<tr class="liste_titre">';
print '<td width="25%">'.$langs->trans("VATManagement").'</td><td>'.$langs->trans("Description").'</td>';
print '<td class="right">&nbsp;</td>';
print "</tr>\n";

// Main tax
print '<tr class="oddeven"><td width="140"><label><input type="radio" name="optiontva" id="use_vat" value="1"'.(empty($conf->global->FACTURE_TVAOPTION) ? "" : " checked")."> ".$langs->trans("VATIsUsed")."</label></td>";
print '<td colspan="2">';
$tooltiphelp = '';
if ($mysoc->country_code == 'FR') $tooltiphelp = '<i>'.$langs->trans("Example").': '.$langs->trans("VATIsUsedExampleFR")."</i>";
print "<label for=\"use_vat\">".$form->textwithpicto($langs->trans("VATIsUsedDesc"), $tooltiphelp)."</label>";
print "</td></tr>\n";


print '<tr class="oddeven"><td width="140"><label><input type="radio" name="optiontva" id="no_vat" value="0"'.(empty($conf->global->FACTURE_TVAOPTION) ? " checked" : "")."> ".$langs->trans("VATIsNotUsed")."</label></td>";
print '<td colspan="2">';
$tooltiphelp = '';
if ($mysoc->country_code == 'FR') $tooltiphelp = "<i>".$langs->trans("Example").': '.$langs->trans("VATIsNotUsedExampleFR")."</i>\n";
print "<label for=\"no_vat\">".$form->textwithpicto($langs->trans("VATIsNotUsedDesc"), $tooltiphelp)."</label>";
print "</td></tr>\n";

print "</table>";

// Second tax
print '<br>';
print '<table class="noborder centpercent editmode">';
print '<tr class="liste_titre">';
print '<td width="25%">'.$form->textwithpicto($langs->transcountry("LocalTax1Management", $mysoc->country_code), $langs->transcountry("LocalTax1IsUsedDesc", $mysoc->country_code)).'</td><td>'.$langs->trans("Description").'</td>';
print '<td class="right">&nbsp;</td>';
print "</tr>\n";

if ($mysoc->useLocalTax(1))
{
	// Note: When option is not set, it must not appears as set on on, because there is no default value for this option
	print '<tr class="oddeven"><td><input type="radio" name="optionlocaltax1" id="lt1" value="localtax1on"'.(($conf->global->FACTURE_LOCAL_TAX1_OPTION == '1' || $conf->global->FACTURE_LOCAL_TAX1_OPTION == "localtax1on") ? " checked" : "")."> ".$langs->transcountry("LocalTax1IsUsed", $mysoc->country_code)."</td>";
	print '<td colspan="2">';
	print '<div class="nobordernopadding">';
	$tooltiphelp = $langs->transcountry("LocalTax1IsUsedExample", $mysoc->country_code);
	$tooltiphelp = ($tooltiphelp != "LocalTax1IsUsedExample" ? "<i>".$langs->trans("Example").': '.$langs->transcountry("LocalTax1IsUsedExample", $mysoc->country_code)."</i>\n" : "");
	print '<label for="lt1">'.$form->textwithpicto($langs->transcountry("LocalTax1IsUsedDesc", $mysoc->country_code), $tooltiphelp)."</label>";
	if (!isOnlyOneLocalTax(1))
	{
		print '<br><label for="lt1">'.$langs->trans("LTRate").'</label>: ';
		$formcompany->select_localtax(1, $conf->global->MAIN_INFO_VALUE_LOCALTAX1, "lt1");
	}

	$opcions = array($langs->trans("CalcLocaltax1").' '.$langs->trans("CalcLocaltax1Desc"), $langs->trans("CalcLocaltax2").' - '.$langs->trans("CalcLocaltax2Desc"), $langs->trans("CalcLocaltax3").' - '.$langs->trans("CalcLocaltax3Desc"));

	print '<br><label for="clt1">'.$langs->trans("CalcLocaltax").'</label>: ';
	print $form->selectarray("clt1", $opcions, $conf->global->MAIN_INFO_LOCALTAX_CALC1);
	print "</div>";
	print "</td></tr>\n";

	print '<tr class="oddeven"><td><input type="radio" name="optionlocaltax1" id="nolt1" value="localtax1off"'.((empty($conf->global->FACTURE_LOCAL_TAX1_OPTION) || $conf->global->FACTURE_LOCAL_TAX1_OPTION == "localtax1off") ? " checked" : "")."> ".$langs->transcountry("LocalTax1IsNotUsed", $mysoc->country_code)."</td>";
	print '<td colspan="2">';
	$tooltiphelp = $langs->transcountry("LocalTax1IsNotUsedExample", $mysoc->country_code);
	$tooltiphelp = ($tooltiphelp != "LocalTax1IsNotUsedExample" ? "<i>".$langs->trans("Example").': '.$langs->transcountry("LocalTax1IsNotUsedExample", $mysoc->country_code)."</i>\n" : "");
	print "<label for=\"nolt1\">".$form->textwithpicto($langs->transcountry("LocalTax1IsNotUsedDesc", $mysoc->country_code), $tooltiphelp)."</label>";
	print "</td></tr>\n";
} else {
	if (empty($mysoc->country_code))
	{
		print '<tr class="oddeven nohover"><td class="">'.$countrynotdefined.'</td><td></td><td></td></tr>';
	} else {
		print '<tr class="oddeven nohover"><td class="" colspan="3">'.$langs->trans("NoLocalTaxXForThisCountry", $langs->transnoentitiesnoconv("Setup"), $langs->transnoentitiesnoconv("Dictionaries"), $langs->transnoentitiesnoconv("DictionaryVAT"), $langs->transnoentitiesnoconv("LocalTax1Management")).'</td></tr>';
	}
}

print "</table>";

// Third tax system
print '<br>';
print '<table class="noborder centpercent editmode">';
print '<tr class="liste_titre">';
print '<td width="25%">'.$form->textwithpicto($langs->transcountry("LocalTax2Management", $mysoc->country_code), $langs->transcountry("LocalTax2IsUsedDesc", $mysoc->country_code)).'</td><td>'.$langs->trans("Description").'</td>';
print '<td class="right">&nbsp;</td>';
print "</tr>\n";

if ($mysoc->useLocalTax(2))
{
	// Note: When option is not set, it must not appears as set on on, because there is no default value for this option
	print '<tr class="oddeven"><td><input type="radio" name="optionlocaltax2" id="lt2" value="localtax2on"'.(($conf->global->FACTURE_LOCAL_TAX2_OPTION == '1' || $conf->global->FACTURE_LOCAL_TAX2_OPTION == "localtax2on") ? " checked" : "")."> ".$langs->transcountry("LocalTax2IsUsed", $mysoc->country_code)."</td>";
	print '<td colspan="2">';
	print '<div class="nobordernopadding">';
	print '<label for="lt2">'.$langs->transcountry("LocalTax2IsUsedDesc", $mysoc->country_code)."</label>";
	$tooltiphelp = $langs->transcountry("LocalTax2IsUsedExample", $mysoc->country_code);
	$tooltiphelp = ($tooltiphelp != "LocalTax2IsUsedExample" ? "<i>".$langs->trans("Example").': '.$langs->transcountry("LocalTax2IsUsedExample", $mysoc->country_code)."</i>\n" : "");
	if (!isOnlyOneLocalTax(2))
	{
		print '<br><label for="lt2">'.$langs->trans("LTRate").'</label>: ';
		$formcompany->select_localtax(2, $conf->global->MAIN_INFO_VALUE_LOCALTAX2, "lt2");
	}
	print '<br><label for="clt2">'.$langs->trans("CalcLocaltax").'</label>: ';
	print $form->selectarray("clt2", $opcions, $conf->global->MAIN_INFO_LOCALTAX_CALC2);
	print "</div>";
	print "</td></tr>\n";

	print '<tr class="oddeven"><td><input type="radio" name="optionlocaltax2" id="nolt2" value="localtax2off"'.((empty($conf->global->FACTURE_LOCAL_TAX2_OPTION) || $conf->global->FACTURE_LOCAL_TAX2_OPTION == "localtax2off") ? " checked" : "")."> ".$langs->transcountry("LocalTax2IsNotUsed", $mysoc->country_code)."</td>";
	print '<td colspan="2">';
	print "<div>";
	$tooltiphelp = $langs->transcountry("LocalTax2IsNotUsedExample", $mysoc->country_code);
	$tooltiphelp = ($tooltiphelp != "LocalTax2IsNotUsedExample" ? "<i>".$langs->trans("Example").': '.$langs->transcountry("LocalTax2IsNotUsedExample", $mysoc->country_code)."</i>\n" : "");
	print "<label for=\"nolt2\">".$form->textwithpicto($langs->transcountry("LocalTax2IsNotUsedDesc", $mysoc->country_code), $tooltiphelp)."</label>";
	print "</div>";
	print "</td></tr>\n";
} else {
	if (empty($mysoc->country_code))
	{
		print '<tr class="oddeven nohover"><td class="">'.$countrynotdefined.'</td><td></td><td></td></tr>';
	} else {
		print '<tr class="oddeven nohover"><td class="" colspan="3">'.$langs->trans("NoLocalTaxXForThisCountry", $langs->transnoentitiesnoconv("Setup"), $langs->transnoentitiesnoconv("Dictionaries"), $langs->transnoentitiesnoconv("DictionaryVAT"), $langs->transnoentitiesnoconv("LocalTax2Management")).'</td></tr>';
	}
}

print "</table>";


// Tax stamp
print '<br>';
print '<table class="noborder centpercent editmode">';
print '<tr class="liste_titre">';
print '<td width="25%">'.$form->textwithpicto($langs->trans("RevenueStamp"), $langs->trans("RevenueStampDesc")).'</td><td>'.$langs->trans("Description").'</td>';
print '<td class="right">&nbsp;</td>';
print "</tr>\n";
if ($mysoc->useRevenueStamp())
{
	// Note: When option is not set, it must not appears as set on on, because there is no default value for this option
	print '<tr class="oddeven"><td>';
	print $langs->trans("UseRevenueStamp");
	print "</td>";
	print '<td colspan="2">';
	print $langs->trans("UseRevenueStampExample", $langs->transnoentitiesnoconv("Setup"), $langs->transnoentitiesnoconv("Dictionaries"), $langs->transnoentitiesnoconv("DictionaryRevenueStamp"));
	print "</td></tr>\n";
} else {
	if (empty($mysoc->country_code))
	{
		print '<tr class="oddeven nohover"><td class="">'.$countrynotdefined.'</td><td></td><td></td></tr>';
	} else {
		print '<tr class="oddeven nohover"><td class="" colspan="3">'.$langs->trans("NoLocalTaxXForThisCountry", $langs->transnoentitiesnoconv("Setup"), $langs->transnoentitiesnoconv("Dictionaries"), $langs->transnoentitiesnoconv("DictionaryRevenueStamp"), $langs->transnoentitiesnoconv("RevenueStamp")).'</td></tr>';
	}
}

print "</table>";


print '<br><div class="center">';
print '<input type="submit" class="button button-save" name="save" value="'.$langs->trans("Save").'">';
print '</div>';

print '</form>';


// End of page
llxFooter();
$db->close();
