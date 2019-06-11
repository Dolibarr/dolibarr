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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
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

$action=GETPOST('action', 'aZ09');
$contextpage=GETPOST('contextpage', 'aZ')?GETPOST('contextpage', 'aZ'):'admincompany';   // To manage different context of search

// Load translation files required by the page
$langs->loadLangs(array('admin', 'companies'));

if (! $user->admin) accessforbidden();

$error=0;

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('admincompany','globaladmin'));


/*
 * Actions
 */

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if ( ($action == 'update' && ! GETPOST("cancel", 'alpha'))
|| ($action == 'updateedit') )
{
	$tmparray=getCountry(GETPOST('country_id', 'int'), 'all', $db, $langs, 0);
	if (! empty($tmparray['id']))
	{
		$mysoc->country_id   =$tmparray['id'];
		$mysoc->country_code =$tmparray['code'];
		$mysoc->country_label=$tmparray['label'];

		$s=$mysoc->country_id.':'.$mysoc->country_code.':'.$mysoc->country_label;
		dolibarr_set_const($db, "MAIN_INFO_SOCIETE_COUNTRY", $s, 'chaine', 0, '', $conf->entity);

		activateModulesRequiredByCountry($mysoc->country_code);
	}

	$db->begin();

	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_NOM", GETPOST("nom", 'nohtml'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_ADDRESS", GETPOST("MAIN_INFO_SOCIETE_ADDRESS", 'nohtml'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_TOWN", GETPOST("MAIN_INFO_SOCIETE_TOWN", 'nohtml'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_ZIP", GETPOST("MAIN_INFO_SOCIETE_ZIP", 'alpha'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_STATE", GETPOST("state_id", 'alpha'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_REGION", GETPOST("region_code", 'alpha'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_MONNAIE", GETPOST("currency", 'aZ09'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_TEL", GETPOST("tel", 'alpha'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_FAX", GETPOST("fax", 'alpha'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_MAIL", GETPOST("mail", 'alpha'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_WEB", GETPOST("web", 'alpha'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_NOTE", GETPOST("note", 'none'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_GENCOD", GETPOST("barcode", 'alpha'), 'chaine', 0, '', $conf->entity);

	$varforimage='logo'; $dirforimage=$conf->mycompany->dir_output.'/logos/';
	if ($_FILES[$varforimage]["tmp_name"])
	{
		if (preg_match('/([^\\/:]+)$/i', $_FILES[$varforimage]["name"], $reg))
		{
			$original_file=$reg[1];

			$isimage=image_format_supported($original_file);
			if ($isimage >= 0)
			{
				dol_syslog("Move file ".$_FILES[$varforimage]["tmp_name"]." to ".$dirforimage.$original_file);
				if (! is_dir($dirforimage))
				{
					dol_mkdir($dirforimage);
				}
				$result=dol_move_uploaded_file($_FILES[$varforimage]["tmp_name"], $dirforimage.$original_file, 1, 0, $_FILES[$varforimage]['error']);
				if ($result > 0)
				{
					dolibarr_set_const($db, "MAIN_INFO_SOCIETE_LOGO", $original_file, 'chaine', 0, '', $conf->entity);

					// Create thumbs of logo (Note that PDF use original file and not thumbs)
					if ($isimage > 0)
					{
					    // Create thumbs
					    //$object->addThumbs($newfile);    // We can't use addThumbs here yet because we need name of generated thumbs to add them into constants. TODO Check if need such constants. We should be able to retreive value with get...

						// Create small thumb, Used on logon for example
						$imgThumbSmall = vignette($dirforimage.$original_file, $maxwidthsmall, $maxheightsmall, '_small', $quality);
						if (image_format_supported($imgThumbSmall) >= 0 && preg_match('/([^\\/:]+)$/i', $imgThumbSmall, $reg))
						{
							$imgThumbSmall = $reg[1];    // Save only basename
							dolibarr_set_const($db, "MAIN_INFO_SOCIETE_LOGO_SMALL", $imgThumbSmall, 'chaine', 0, '', $conf->entity);
						}
						else dol_syslog($imgThumbSmall);

						// Create mini thumb, Used on menu or for setup page for example
						$imgThumbMini = vignette($dirforimage.$original_file, $maxwidthmini, $maxheightmini, '_mini', $quality);
						if (image_format_supported($imgThumbMini) >= 0 && preg_match('/([^\\/:]+)$/i', $imgThumbMini, $reg))
						{
							$imgThumbMini = $reg[1];     // Save only basename
							dolibarr_set_const($db, "MAIN_INFO_SOCIETE_LOGO_MINI", $imgThumbMini, 'chaine', 0, '', $conf->entity);
						}
						else dol_syslog($imgThumbMini);
					}
					else dol_syslog("ErrorImageFormatNotSupported", LOG_WARNING);
				} elseif (preg_match('/^ErrorFileIsInfectedWithAVirus/', $result)) {
					$error++;
					$langs->load("errors");
					$tmparray=explode(':', $result);
					setEventMessages($langs->trans('ErrorFileIsInfectedWithAVirus', $tmparray[1]), null, 'errors');
				}
				else
				{
					$error++;
					setEventMessages($langs->trans("ErrorFailedToSaveFile"), null, 'errors');
				}
			}
			else
			{
				$error++;
				$langs->load("errors");
				setEventMessages($langs->trans("ErrorBadImageFormat"), null, 'errors');
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
	if ($uselocaltax1 == 'localtax1on' && ! $usevat)
	{
		setEventMessages($langs->trans("IfYouUseASecondTaxYouMustSetYouUseTheMainTax"), null, 'errors');
		$error++;
	}
	if ($uselocaltax2 == 'localtax2on' && ! $usevat)
	{
		setEventMessages($langs->trans("IfYouUseAThirdTaxYouMustSetYouUseTheMainTax"), null, 'errors');
		$error++;
	}

	dolibarr_set_const($db, "FACTURE_TVAOPTION", $usevat, 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "FACTURE_LOCAL_TAX1_OPTION", $uselocaltax1, 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "FACTURE_LOCAL_TAX2_OPTION", $uselocaltax2, 'chaine', 0, '', $conf->entity);

	if($_POST["optionlocaltax1"]=="localtax1on")
	{
		if(!isset($_REQUEST['lt1']))
		{
			dolibarr_set_const($db, "MAIN_INFO_VALUE_LOCALTAX1", 0, 'chaine', 0, '', $conf->entity);
		}
		else
		{
			dolibarr_set_const($db, "MAIN_INFO_VALUE_LOCALTAX1", GETPOST('lt1', 'aZ09'), 'chaine', 0, '', $conf->entity);
		}
		dolibarr_set_const($db, "MAIN_INFO_LOCALTAX_CALC1", GETPOST("clt1", 'aZ09'), 'chaine', 0, '', $conf->entity);
	}
	if($_POST["optionlocaltax2"]=="localtax2on")
	{
		if(!isset($_REQUEST['lt2']))
		{
			dolibarr_set_const($db, "MAIN_INFO_VALUE_LOCALTAX2", 0, 'chaine', 0, '', $conf->entity);
		}
		else
		{
			dolibarr_set_const($db, "MAIN_INFO_VALUE_LOCALTAX2", GETPOST('lt2', 'aZ09'), 'chaine', 0, '', $conf->entity);
		}
		dolibarr_set_const($db, "MAIN_INFO_LOCALTAX_CALC2", GETPOST("clt2", 'aZ09'), 'chaine', 0, '', $conf->entity);
	}

	if (! $error)
	{
		$db->commit();
	}
	else
	{
		$db->rollback();
	}

	if ($action != 'updateedit' && ! $error)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
}

if ($action == 'addthumb')  // Regenerate thumbs
{
	if (file_exists($conf->mycompany->dir_output.'/logos/'.$_GET["file"]))
	{
		$isimage=image_format_supported($_GET["file"]);

		// Create thumbs of logo
		if ($isimage > 0)
		{
		    // Create thumbs
		    //$object->addThumbs($newfile);    // We can't use addThumbs here yet because we need name of generated thumbs to add them into constants. TODO Check if need such constants. We should be able to retreive value with get...

			// Create small thumb. Used on logon for example
			$imgThumbSmall = vignette($conf->mycompany->dir_output.'/logos/'.$_GET["file"], $maxwidthsmall, $maxheightsmall, '_small', $quality);
			if (image_format_supported($imgThumbSmall) >= 0 && preg_match('/([^\\/:]+)$/i', $imgThumbSmall, $reg))
			{
				$imgThumbSmall = $reg[1];   // Save only basename
				dolibarr_set_const($db, "MAIN_INFO_SOCIETE_LOGO_SMALL", $imgThumbSmall, 'chaine', 0, '', $conf->entity);
			}
			else dol_syslog($imgThumbSmall);

			// Create mini thumbs. Used on menu or for setup page for example
			$imgThumbMini = vignette($conf->mycompany->dir_output.'/logos/'.$_GET["file"], $maxwidthmini, $maxheightmini, '_mini', $quality);
			if (image_format_supported($imgThumbSmall) >= 0 && preg_match('/([^\\/:]+)$/i', $imgThumbMini, $reg))
			{
				$imgThumbMini = $reg[1];   // Save only basename
				dolibarr_set_const($db, "MAIN_INFO_SOCIETE_LOGO_MINI", $imgThumbMini, 'chaine', 0, '', $conf->entity);
			}
			else dol_syslog($imgThumbMini);

			header("Location: ".$_SERVER["PHP_SELF"]);
			exit;
		}
		else
		{
			$error++;
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorBadImageFormat"), null, 'errors');
			dol_syslog($langs->transnoentities("ErrorBadImageFormat"), LOG_INFO);
		}
	}
	else
	{
		$error++;
		$langs->load("errors");
		setEventMessages($langs->trans("ErrorFileDoesNotExists", $_GET["file"]), null, 'errors');
		dol_syslog($langs->transnoentities("ErrorFileDoesNotExists", $_GET["file"]), LOG_WARNING);
	}
}

if ($action == 'removelogo')
{
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	$logofile=$conf->mycompany->dir_output.'/logos/'.$mysoc->logo;
	if ($mysoc->logo != '') dol_delete_file($logofile);
	dolibarr_del_const($db, "MAIN_INFO_SOCIETE_LOGO", $conf->entity);
	$mysoc->logo='';

	$logosmallfile=$conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_small;
	if ($mysoc->logo_small != '') dol_delete_file($logosmallfile);
	dolibarr_del_const($db, "MAIN_INFO_SOCIETE_LOGO_SMALL", $conf->entity);
	$mysoc->logo_small='';

	$logominifile=$conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_mini;
	if ($mysoc->logo_mini != '') dol_delete_file($logominifile);
	dolibarr_del_const($db, "MAIN_INFO_SOCIETE_LOGO_MINI", $conf->entity);
	$mysoc->logo_mini='';
}


/*
 * View
 */

$wikihelp='EN:First_setup|FR:Premiers_paramétrages|ES:Primeras_configuraciones';
llxHeader('', $langs->trans("Setup"), $wikihelp);

$form=new Form($db);
$formother=new FormOther($db);
$formcompany=new FormCompany($db);

$countrynotdefined='<font class="error">'.$langs->trans("ErrorSetACountryFirst").' ('.$langs->trans("SeeAbove").')</font>';

print load_fiche_titre($langs->trans("CompanyFoundation"), '', 'title_setup');

$head = company_admin_prepare_head();

dol_fiche_head($head, 'company', $langs->trans("Company"), -1, 'company');

print '<span class="opacitymedium">'.$langs->trans("CompanyFundationDesc", $langs->transnoentities("Modify"), $langs->transnoentities("Save"))."</span><br>\n";
print "<br>\n";

if ($action == 'edit' || $action == 'updateedit')
{
	/**
	 * Edition des parametres
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
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="update">';

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("CompanyInfo").'</th><th>'.$langs->trans("Value").'</th></tr>'."\n";

	// Name

	print '<tr class="oddeven"><td class="fieldrequired"><label for="name">'.$langs->trans("CompanyName").'</label></td><td>';
	print '<input name="nom" id="name" class="minwidth200" value="'. dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_NOM?$conf->global->MAIN_INFO_SOCIETE_NOM: GETPOST("nom", 'nohtml')) . '" autofocus="autofocus"></td></tr>'."\n";

	// Addresse

	print '<tr class="oddeven"><td><label for="MAIN_INFO_SOCIETE_ADDRESS">'.$langs->trans("CompanyAddress").'</label></td><td>';
	print '<textarea name="MAIN_INFO_SOCIETE_ADDRESS" id="MAIN_INFO_SOCIETE_ADDRESS" class="quatrevingtpercent" rows="'.ROWS_3.'">'. ($conf->global->MAIN_INFO_SOCIETE_ADDRESS?$conf->global->MAIN_INFO_SOCIETE_ADDRESS:GETPOST("MAIN_INFO_SOCIETE_ADDRESS", 'nohtml')) . '</textarea></td></tr>'."\n";


	print '<tr class="oddeven"><td><label for="MAIN_INFO_SOCIETE_ZIP">'.$langs->trans("CompanyZip").'</label></td><td>';
	print '<input class="minwidth100" name="MAIN_INFO_SOCIETE_ZIP" id="MAIN_INFO_SOCIETE_ZIP" value="'. dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_ZIP?$conf->global->MAIN_INFO_SOCIETE_ZIP:GETPOST("MAIN_INFO_SOCIETE_ZIP", 'alpha')) . '"></td></tr>'."\n";


	print '<tr class="oddeven"><td><label for="MAIN_INFO_SOCIETE_TOWN">'.$langs->trans("CompanyTown").'</label></td><td>';
	print '<input name="MAIN_INFO_SOCIETE_TOWN" class="minwidth100" id="MAIN_INFO_SOCIETE_TOWN" value="'. dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_TOWN?$conf->global->MAIN_INFO_SOCIETE_TOWN:GETPOST("MAIN_INFO_SOCIETE_TOWN", 'nohtml')) . '"></td></tr>'."\n";

	// Country

	print '<tr class="oddeven"><td class="fieldrequired"><label for="selectcountry_id">'.$langs->trans("Country").'</label></td><td class="maxwidthonsmartphone">';
	//if (empty($country_selected)) $country_selected=substr($langs->defaultlang,-2);    // By default, country of localization
	print $form->select_country($mysoc->country_id, 'country_id');
	if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
	print '</td></tr>'."\n";


	print '<tr class="oddeven"><td><label for="state_id">'.$langs->trans("State").'</label></td><td class="maxwidthonsmartphone">';
	$formcompany->select_departement($conf->global->MAIN_INFO_SOCIETE_STATE, $mysoc->country_code, 'state_id');
	print '</td></tr>'."\n";


	print '<tr class="oddeven"><td><label for="currency">'.$langs->trans("CompanyCurrency").'</label></td><td>';
	print $form->selectCurrency($conf->currency, "currency");
	print '</td></tr>'."\n";


	print '<tr class="oddeven"><td><label for="phone">'.$langs->trans("Phone").'</label></td><td>';
	print '<input name="tel" id="phone" value="'. dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_TEL) . '"></td></tr>';
	print '</td></tr>'."\n";


	print '<tr class="oddeven"><td><label for="fax">'.$langs->trans("Fax").'</label></td><td>';
	print '<input name="fax" id="fax" value="'. dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_FAX) . '"></td></tr>';
	print '</td></tr>'."\n";


	print '<tr class="oddeven"><td><label for="email">'.$langs->trans("EMail").'</label></td><td>';
	print '<input name="mail" id="email" class="minwidth200" value="'. dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_MAIL) . '"></td></tr>';
	print '</td></tr>'."\n";

	// Web
	print '<tr class="oddeven"><td><label for="web">'.$langs->trans("Web").'</label></td><td>';
	print '<input name="web" id="web" class="minwidth300" value="'. dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_WEB) . '"></td></tr>';
	print '</td></tr>'."\n";

	// Barcode
	if (! empty($conf->barcode->enabled)) {

		print '<tr class="oddeven"><td><label for="barcode">'.$langs->trans("Gencod").'</label></td><td>';
		print '<input name="barcode" id="barcode" class="minwidth150" value="'. dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_GENCOD) . '"></td></tr>';
		print '</td></tr>';
	}

	// Logo
	print '<tr class="oddeven"><td><label for="logo">'.$langs->trans("Logo").' (png,jpg)</label></td><td>';
	print '<table width="100%" class="nobordernopadding"><tr class="nocellnopadd"><td valign="middle" class="nocellnopadd">';
	print '<input type="file" class="flat class=minwidth200" name="logo" id="logo" accept="image/*">';
	print '</td><td class="nocellnopadd right" valign="middle">';
	if (! empty($mysoc->logo_mini)) {
		print '<a href="'.$_SERVER["PHP_SELF"].'?action=removelogo">'.img_delete($langs->trans("Delete")).'</a>';
		if (file_exists($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_mini)) {
			print ' &nbsp; ';
			print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&amp;file='.urlencode('logos/thumbs/'.$mysoc->logo_mini).'">';
		}
	} else {
		print '<img height="30" src="'.DOL_URL_ROOT.'/public/theme/common/nophoto.png">';
	}
	print '</td></tr></table>';
	print '</td></tr>';

	// Note
	print '<tr class="oddeven"><td class="tdtop"><label for="note">'.$langs->trans("Note").'</label></td><td>';
	print '<textarea class="flat quatrevingtpercent" name="note" id="note" rows="'.ROWS_5.'">'.(GETPOST('note', 'none') ? GETPOST('note', 'none') : $conf->global->MAIN_INFO_SOCIETE_NOTE).'</textarea></td></tr>';
	print '</td></tr>';

	print '</table>';

	print '<br>';

	// IDs of the company (country-specific)
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td class="titlefield">'.$langs->trans("CompanyIds").'</td><td>'.$langs->trans("Value").'</td></tr>';

	$langs->load("companies");

	// Managing Director(s)

	print '<tr class="oddeven"><td><label for="director">'.$langs->trans("ManagingDirectors").'</label></td><td>';
	print '<input name="MAIN_INFO_SOCIETE_MANAGERS" id="director" class="minwidth200" value="' . dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_MANAGERS) . '"></td></tr>';

	// GDPR contact

	print '<tr class="oddeven"><td>';
	print $form->textwithpicto($langs->trans("GDPRContact"), $langs->trans("GDPRContactDesc"));
	print '</td><td>';
	print '<input name="MAIN_INFO_GDPR" id="director" class="minwidth500" value="' . dol_escape_htmltag($conf->global->MAIN_INFO_GDPR) . '"></td></tr>';

	// Capital

	print '<tr class="oddeven"><td><label for="capital">'.$langs->trans("Capital").'</label></td><td>';
	print '<input name="capital" id="capital" class="minwidth100" value="' . dol_escape_htmltag($conf->global->MAIN_INFO_CAPITAL) . '"></td></tr>';

	// Juridical Status

	print '<tr class="oddeven"><td><label for="forme_juridique_code">'.$langs->trans("JuridicalStatus").'</label></td><td>';
	if ($mysoc->country_code) {
		print $formcompany->select_juridicalstatus($conf->global->MAIN_INFO_SOCIETE_FORME_JURIDIQUE, $mysoc->country_code, '', 'forme_juridique_code');
	} else {
		print $countrynotdefined;
	}
	print '</td></tr>';

	// ProfID1
	if ($langs->transcountry("ProfId1", $mysoc->country_code) != '-')
	{

		print '<tr class="oddeven"><td><label for="profid1">'.$langs->transcountry("ProfId1", $mysoc->country_code).'</label></td><td>';
		if (! empty($mysoc->country_code))
		{
			print '<input name="siren" id="profid1" class="minwidth200" value="' . dol_escape_htmltag(! empty($conf->global->MAIN_INFO_SIREN) ? $conf->global->MAIN_INFO_SIREN : '') . '">';
		}
		else
		{
			print $countrynotdefined;
		}
		print '</td></tr>';
	}

	// ProfId2
	if ($langs->transcountry("ProfId2", $mysoc->country_code) != '-')
	{

		print '<tr class="oddeven"><td><label for="profid2">'.$langs->transcountry("ProfId2", $mysoc->country_code).'</label></td><td>';
		if (! empty($mysoc->country_code))
		{
			print '<input name="siret" id="profid2" class="minwidth200" value="' . dol_escape_htmltag(! empty($conf->global->MAIN_INFO_SIRET) ? $conf->global->MAIN_INFO_SIRET : '') . '">';
		}
		else
		{
			print $countrynotdefined;
		}
		print '</td></tr>';
	}

	// ProfId3
	if ($langs->transcountry("ProfId3", $mysoc->country_code) != '-')
	{

		print '<tr class="oddeven"><td><label for="profid3">'.$langs->transcountry("ProfId3", $mysoc->country_code).'</label></td><td>';
		if (! empty($mysoc->country_code))
		{
			print '<input name="ape" id="profid3" class="minwidth200" value="' . dol_escape_htmltag(! empty($conf->global->MAIN_INFO_APE) ? $conf->global->MAIN_INFO_APE : '') . '">';
		}
		else
		{
			print $countrynotdefined;
		}
		print '</td></tr>';
	}

	// ProfId4
	if ($langs->transcountry("ProfId4", $mysoc->country_code) != '-')
	{

		print '<tr class="oddeven"><td><label for="profid4">'.$langs->transcountry("ProfId4", $mysoc->country_code).'</label></td><td>';
		if (! empty($mysoc->country_code))
		{
			print '<input name="rcs" id="profid4" class="minwidth200" value="' . dol_escape_htmltag(! empty($conf->global->MAIN_INFO_RCS) ? $conf->global->MAIN_INFO_RCS : '') . '">';
		}
		else
		{
			print $countrynotdefined;
		}
		print '</td></tr>';
	}

	// ProfId5
	if ($langs->transcountry("ProfId5", $mysoc->country_code) != '-')
	{

		print '<tr class="oddeven"><td><label for="profid5">'.$langs->transcountry("ProfId5", $mysoc->country_code).'</label></td><td>';
		if (! empty($mysoc->country_code))
		{
			print '<input name="MAIN_INFO_PROFID5" id="profid5" class="minwidth200" value="' . dol_escape_htmltag(! empty($conf->global->MAIN_INFO_PROFID5) ? $conf->global->MAIN_INFO_PROFID5 : '') . '">';
		}
		else
		{
			print $countrynotdefined;
		}
		print '</td></tr>';
	}

	// ProfId6
	if ($langs->transcountry("ProfId6", $mysoc->country_code) != '-')
	{

		print '<tr class="oddeven"><td><label for="profid6">'.$langs->transcountry("ProfId6", $mysoc->country_code).'</label></td><td>';
		if (! empty($mysoc->country_code))
		{
			print '<input name="MAIN_INFO_PROFID6" id="profid6" class="minwidth200" value="' . dol_escape_htmltag(! empty($conf->global->MAIN_INFO_PROFID6) ? $conf->global->MAIN_INFO_PROFID6 : '') . '">';
		}
		else
		{
			print $countrynotdefined;
		}
		print '</td></tr>';
	}

	// TVA Intra

	print '<tr class="oddeven"><td><label for="intra_vat">'.$langs->trans("VATIntra").'</label></td><td>';
	print '<input name="tva" id="intra_vat" class="minwidth200" value="' . dol_escape_htmltag(! empty($conf->global->MAIN_INFO_TVAINTRA) ? $conf->global->MAIN_INFO_TVAINTRA : '') . '">';
	print '</td></tr>';

	// Object of the company

	print '<tr class="oddeven"><td><label for="object">'.$langs->trans("CompanyObject").'</label></td><td>';
	print '<textarea class="flat quatrevingtpercent" name="object" id="object" rows="'.ROWS_5.'">'.(! empty($conf->global->MAIN_INFO_SOCIETE_OBJECT) ? $conf->global->MAIN_INFO_SOCIETE_OBJECT : '').'</textarea></td></tr>';
	print '</td></tr>';

	print '</table>';


	// Fiscal year start
	print '<br>';
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td class="titlefield">'.$langs->trans("FiscalYearInformation").'</td><td>'.$langs->trans("Value").'</td>';
	print "</tr>\n";

	print '<tr class="oddeven"><td><label for="SOCIETE_FISCAL_MONTH_START">'.$langs->trans("FiscalMonthStart").'</label></td><td>';
	print $formother->select_month($conf->global->SOCIETE_FISCAL_MONTH_START, 'SOCIETE_FISCAL_MONTH_START', 0, 1, 'maxwidth100') . '</td></tr>';

	print "</table>";


	// Fiscal options
	print '<br>';
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td width="140">'.$langs->trans("VATManagement").'</td><td>'.$langs->trans("Description").'</td>';
	print '<td class="right">&nbsp;</td>';
	print "</tr>\n";


	print "<tr class=\"oddeven\"><td width=\"140\"><label><input type=\"radio\" name=\"optiontva\" id=\"use_vat\" value=\"1\"".(empty($conf->global->FACTURE_TVAOPTION)?"":" checked")."> ".$langs->trans("VATIsUsed")."</label></td>";
	print '<td colspan="2">';
	print "<table>";
	print "<tr><td><label for=\"use_vat\">".$langs->trans("VATIsUsedDesc")."</label></td></tr>";
	if ($mysoc->country_code == 'FR') print "<tr><td><i>".$langs->trans("Example").': '.$langs->trans("VATIsUsedExampleFR")."</i></td></tr>\n";
	print "</table>";
	print "</td></tr>\n";


	print "<tr class=\"oddeven\"><td width=\"140\"><label><input type=\"radio\" name=\"optiontva\" id=\"no_vat\" value=\"0\"".(empty($conf->global->FACTURE_TVAOPTION)?" checked":"")."> ".$langs->trans("VATIsNotUsed")."</label></td>";
	print '<td colspan="2">';
	print "<table>";
	print "<tr><td><label for=\"no_vat\">".$langs->trans("VATIsNotUsedDesc")."</label></td></tr>";
	if ($mysoc->country_code == 'FR') print "<tr><td><i>".$langs->trans("Example").': '.$langs->trans("VATIsNotUsedExampleFR")."</i></td></tr>\n";
	print "</table>";
	print "</td></tr>\n";

	print "</table>";

	/*
	 *  Local Taxes
	 */
	if ($mysoc->useLocalTax(1))
	{
		// Local Tax 1
		print '<br>';
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td width="140">'.$langs->transcountry("LocalTax1Management", $mysoc->country_code).'</td><td>'.$langs->trans("Description").'</td>';
		print '<td class="right">&nbsp;</td>';
		print "</tr>\n";

		// Note: When option is not set, it must not appears as set on on, because there is no default value for this option
		print "<tr class=\"oddeven\"><td width=\"140\"><input type=\"radio\" name=\"optionlocaltax1\" id=\"lt1\" value=\"localtax1on\"".(($conf->global->FACTURE_LOCAL_TAX1_OPTION == '1' || $conf->global->FACTURE_LOCAL_TAX1_OPTION == "localtax1on")?" checked":"")."> ".$langs->transcountry("LocalTax1IsUsed", $mysoc->country_code)."</td>";
		print '<td colspan="2">';
		print '<table class="nobordernopadding">';
		print "<tr><td><label for=\"lt1\">".$langs->transcountry("LocalTax1IsUsedDesc", $mysoc->country_code)."</label></td></tr>";
		$example=$langs->transcountry("LocalTax1IsUsedExample", $mysoc->country_code);
		print ($example!="LocalTax1IsUsedExample"?"<tr><td><i>".$langs->trans("Example").': '.$langs->transcountry("LocalTax1IsUsedExample", $mysoc->country_code)."</i></td></tr>\n":"");
		if(! isOnlyOneLocalTax(1))
		{
			print '<tr><td class="left"><label for="lt1">'.$langs->trans("LTRate").'</label>: ';
			$formcompany->select_localtax(1, $conf->global->MAIN_INFO_VALUE_LOCALTAX1, "lt1");
		    print '</td></tr>';
		}

		$opcions=array($langs->trans("CalcLocaltax1").' '.$langs->trans("CalcLocaltax1Desc"),$langs->trans("CalcLocaltax2").' - '.$langs->trans("CalcLocaltax2Desc"),$langs->trans("CalcLocaltax3").' - '.$langs->trans("CalcLocaltax3Desc"));

		print '<tr><td class="left"></label for="clt1">'.$langs->trans("CalcLocaltax").'</label>: ';
		print $form->selectarray("clt1", $opcions, $conf->global->MAIN_INFO_LOCALTAX_CALC1);
		print '</td></tr>';
		print "</table>";
		print "</td></tr>\n";


		print "<tr class=\"oddeven\"><td width=\"140\"><input type=\"radio\" name=\"optionlocaltax1\" id=\"nolt1\" value=\"localtax1off\"".((empty($conf->global->FACTURE_LOCAL_TAX1_OPTION) || $conf->global->FACTURE_LOCAL_TAX1_OPTION == "localtax1off")?" checked":"")."> ".$langs->transcountry("LocalTax1IsNotUsed", $mysoc->country_code)."</td>";
		print '<td colspan="2">';
		print "<table>";
		print "<tr><td><label for=\"nolt1\">".$langs->transcountry("LocalTax1IsNotUsedDesc", $mysoc->country_code)."</label></td></tr>";
		$example=$langs->transcountry("LocalTax1IsNotUsedExample", $mysoc->country_code);
		print ($example!="LocalTax1IsNotUsedExample"?"<tr><td><i>".$langs->trans("Example").': '.$langs->transcountry("LocalTax1IsNotUsedExample", $mysoc->country_code)."</i></td></tr>\n":"");
		print "</table>";
		print "</td></tr>\n";
		print "</table>";
	}
	if ($mysoc->useLocalTax(2))
	{
		// Local Tax 2
		print '<br>';
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->transcountry("LocalTax2Management", $mysoc->country_code).'</td><td>'.$langs->trans("Description").'</td>';
		print '<td class="right">&nbsp;</td>';
		print "</tr>\n";


		// Note: When option is not set, it must not appears as set on on, because there is no default value for this option
		print "<tr class=\"oddeven\"><td width=\"140\"><input type=\"radio\" name=\"optionlocaltax2\" id=\"lt2\" value=\"localtax2on\"".(($conf->global->FACTURE_LOCAL_TAX2_OPTION == '1' || $conf->global->FACTURE_LOCAL_TAX2_OPTION == "localtax2on")?" checked":"")."> ".$langs->transcountry("LocalTax2IsUsed", $mysoc->country_code)."</td>";
		print '<td colspan="2">';
		print '<table class="nobordernopadding">';
		print "<tr><td><label for=\"lt2\">".$langs->transcountry("LocalTax2IsUsedDesc", $mysoc->country_code)."</label></td></tr>";
		$example=$langs->transcountry("LocalTax2IsUsedExample", $mysoc->country_code);
		print ($example!="LocalTax2IsUsedExample"?"<tr><td><i>".$langs->trans("Example").': '.$langs->transcountry("LocalTax2IsUsedExample", $mysoc->country_code)."</i></td></tr>\n":"");
		if(! isOnlyOneLocalTax(2))
		{
		    print '<tr><td class="left"><label for="lt2">'.$langs->trans("LTRate").'</label>: ';
		    $formcompany->select_localtax(2, $conf->global->MAIN_INFO_VALUE_LOCALTAX2, "lt2");
			print '</td></tr>';
		}
		print '<tr><td class="left"><label for="clt2">'.$langs->trans("CalcLocaltax").'</label>: ';
		print $form->selectarray("clt2", $opcions, $conf->global->MAIN_INFO_LOCALTAX_CALC2);
		print '</td></tr>';
		print "</table>";
		print "</td></tr>\n";


		print "<tr class=\"oddeven\"><td width=\"140\"><input type=\"radio\" name=\"optionlocaltax2\" id=\"nolt2\" value=\"localtax2off\"".((empty($conf->global->FACTURE_LOCAL_TAX2_OPTION) || $conf->global->FACTURE_LOCAL_TAX2_OPTION == "localtax2off")?" checked":"")."> ".$langs->transcountry("LocalTax2IsNotUsed", $mysoc->country_code)."</td>";
		print '<td colspan="2">';
		print "<table>";
		print "<tr><td><label for=\"nolt2\">".$langs->transcountry("LocalTax2IsNotUsedDesc", $mysoc->country_code)."</label></td></tr>";
		$example=$langs->transcountry("LocalTax2IsNotUsedExample", $mysoc->country_code);
		print ($example!="LocalTax2IsNotUsedExample"?"<tr><td><i>".$langs->trans("Example").': '.$langs->transcountry("LocalTax2IsNotUsedExample", $mysoc->country_code)."</i></td></tr>\n":"");
		print "</table>";
		print "</td></tr>\n";
		print "</table>";
	}


	print '<br><div class="center">';
	print '<input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';
	print '<br>';

	print '</form>';
}
else
{
	/*
	 * Show parameters
	 */

	// Actions buttons
	//print '<div class="tabsAction">';
	//print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit">'.$langs->trans("Modify").'</a>';
	//print '</div><br>';

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td>'.$langs->trans("CompanyInfo").'</td><td>'.$langs->trans("Value").'</td></tr>';


	print '<tr class="oddeven"><td class="titlefield wordbreak">'.$langs->trans("CompanyName").'</td><td>';
	if (! empty($conf->global->MAIN_INFO_SOCIETE_NOM)) print $conf->global->MAIN_INFO_SOCIETE_NOM;
	else print img_warning().' <font class="error">'.$langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("CompanyName")).'</font>';
	print '</td></tr>';


	print '<tr class="oddeven"><td>'.$langs->trans("CompanyAddress").'</td><td>' . nl2br(empty($conf->global->MAIN_INFO_SOCIETE_ADDRESS)?'':$conf->global->MAIN_INFO_SOCIETE_ADDRESS) . '</td></tr>';


	print '<tr class="oddeven"><td>'.$langs->trans("CompanyZip").'</td><td>' . (empty($conf->global->MAIN_INFO_SOCIETE_ZIP)?'':$conf->global->MAIN_INFO_SOCIETE_ZIP) . '</td></tr>';


	print '<tr class="oddeven"><td>'.$langs->trans("CompanyTown").'</td><td>' . (empty($conf->global->MAIN_INFO_SOCIETE_TOWN)?'':$conf->global->MAIN_INFO_SOCIETE_TOWN) . '</td></tr>';


	print '<tr class="oddeven"><td>'.$langs->trans("CompanyCountry").'</td><td>';
	if ($mysoc->country_code)
	{
		$img=picto_from_langcode($mysoc->country_code);
		print $img?$img.' ':'';
		print getCountry($mysoc->country_code, 1);
	}
	else print img_warning().' <font class="error">'.$langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("CompanyCountry")).'</font>';
	print '</td></tr>';


	if (! empty($conf->global->MAIN_SHOW_REGION_IN_STATE_SELECT)) print '<tr class="oddeven"><td>'.$langs->trans("Region-State").'</td><td>';
	else print '<tr class="oddeven"><td>'.$langs->trans("State").'</td><td>';
	if (! empty($conf->global->MAIN_INFO_SOCIETE_STATE)) print getState($conf->global->MAIN_INFO_SOCIETE_STATE, $conf->global->MAIN_SHOW_STATE_CODE, 0, $conf->global->MAIN_SHOW_REGION_IN_STATE_SELECT);
	else print '&nbsp;';
	print '</td></tr>';


	print '<tr class="oddeven"><td>'.$langs->trans("CompanyCurrency").'</td><td>';
	print currency_name($conf->currency, 0);
	print ' ('.$conf->currency;
	print ($conf->currency != $langs->getCurrencySymbol($conf->currency) ? ' - '.$langs->getCurrencySymbol($conf->currency) : '');
	print ')';
	print ' - '.$langs->trans("PriceFormatInCurrentLanguage", $langs->defaultlang).' : '.price(price2num('99.333333333', 'MT'), 1, $langs, 1, -1, -1, $conf->currency);
	print '</td></tr>';


	print '<tr class="oddeven"><td>'.$langs->trans("Phone").'</td><td>' . dol_print_phone($conf->global->MAIN_INFO_SOCIETE_TEL, $mysoc->country_code) . '</td></tr>';


	print '<tr class="oddeven"><td>'.$langs->trans("Fax").'</td><td>' . dol_print_phone($conf->global->MAIN_INFO_SOCIETE_FAX, $mysoc->country_code) . '</td></tr>';


	print '<tr class="oddeven"><td>'.$langs->trans("Mail").'</td><td>' . dol_print_email($conf->global->MAIN_INFO_SOCIETE_MAIL, 0, 0, 0, 80) . '</td></tr>';

	// Web

	print '<tr class="oddeven"><td>'.$langs->trans("Web").'</td><td>';
	$arrayofurl = preg_split('/\s/', $conf->global->MAIN_INFO_SOCIETE_WEB);
	foreach($arrayofurl as $urltoshow)
	{
		if ($urltoshow) print dol_print_url($urltoshow, '_blank', 80);
	}
	print '</td></tr>';

	// Barcode

	if (! empty($conf->barcode->enabled))
	{
		print '<tr class="oddeven"><td>'.$langs->trans("Gencod").'</td><td>' . $conf->global->MAIN_INFO_SOCIETE_GENCOD . '</td></tr>';
	}

	// Logo

	print '<tr class="oddeven"><td>'.$langs->trans("Logo").'</td><td>';

	$tagtd='tagtd ';
	if ($conf->browser->layout == 'phone') $tagtd='';
	print '<div class="tagtable centpercent"><div class="tagtr inline-block centpercent valignmiddle"><div class="'.$tagtd.'inline-block valignmiddle left">';
	print $mysoc->logo;
	print '</div><div class="'.$tagtd.'inline-block valignmiddle left">';

	// It offers the generation of the thumbnail if it does not exist
	if (!is_file($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_mini) && preg_match('/(\.jpg|\.jpeg|\.png)$/i', $mysoc->logo))
	{
		print '<a class="img_logo" href="'.$_SERVER["PHP_SELF"].'?action=addthumb&amp;file='.urlencode($mysoc->logo).'">'.img_picto($langs->trans('GenerateThumb'), 'refresh').'</a>&nbsp;&nbsp;';
	}
	elseif ($mysoc->logo_mini && is_file($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_mini))
	{
		print '<img class="img_logo" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&amp;file='.urlencode('logos/thumbs/'.$mysoc->logo_mini).'">';
	}
	else
	{
		print '<img class="img_logo" src="'.DOL_URL_ROOT.'/public/theme/common/nophoto.png">';
	}
	print '</div></div></div>';

	print '</td></tr>';


	print '<tr class="oddeven"><td class="tdtop">'.$langs->trans("Note").'</td><td>' . (! empty($conf->global->MAIN_INFO_SOCIETE_NOTE) ? nl2br($conf->global->MAIN_INFO_SOCIETE_NOTE) : '') . '</td></tr>';

	print '</table>';
	print "</div>";

	print '<br>';


	// IDs of the company (country-specific)
	print '<form name="formsoc" method="post">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td class="titlefield wordbreak">'.$langs->trans("CompanyIds").'</td><td>'.$langs->trans("Value").'</td></tr>';

	// Managing Director(s)

	print '<tr class="oddeven"><td>'.$langs->trans("ManagingDirectors").'</td><td>';
	print $conf->global->MAIN_INFO_SOCIETE_MANAGERS . '</td></tr>';

	// GDPR Contact

	print '<tr class="oddeven"><td>'.$langs->trans("GDPRContact").'</td><td>';
	print $conf->global->MAIN_INFO_GDPR . '</td></tr>';

	// Capital

	print '<tr class="oddeven"><td>'.$langs->trans("Capital").'</td><td>';
	print $conf->global->MAIN_INFO_CAPITAL . '</td></tr>';

	// Juridical Status

	print '<tr class="oddeven"><td>'.$langs->trans("JuridicalStatus").'</td><td>';
	print getFormeJuridiqueLabel($conf->global->MAIN_INFO_SOCIETE_FORME_JURIDIQUE);
	print '</td></tr>';

	// ProfId1
	if ($langs->transcountry("ProfId1", $mysoc->country_code) != '-')
	{

		print '<tr class="oddeven"><td>'.$langs->transcountry("ProfId1", $mysoc->country_code).'</td><td>';
		if (! empty($conf->global->MAIN_INFO_SIREN))
		{
			print $conf->global->MAIN_INFO_SIREN;
			$s = $mysoc->id_prof_url(1, $mysoc);
			if ($s) print ' - '.$s;
		} else {
			print '&nbsp;';
		}
		print '</td></tr>';
	}

	// ProfId2
	if ($langs->transcountry("ProfId2", $mysoc->country_code) != '-')
	{

		print '<tr class="oddeven"><td>'.$langs->transcountry("ProfId2", $mysoc->country_code).'</td><td>';
		if (! empty($conf->global->MAIN_INFO_SIRET))
		{
			print $conf->global->MAIN_INFO_SIRET;
			$s = $mysoc->id_prof_url(2, $mysoc);
			if ($s) print ' - '.$s;
		} else {
			print '&nbsp;';
		}
		print '</td></tr>';
	}

	// ProfId3
	if ($langs->transcountry("ProfId3", $mysoc->country_code) != '-')
	{

		print '<tr class="oddeven"><td>'.$langs->transcountry("ProfId3", $mysoc->country_code).'</td><td>';
		if (! empty($conf->global->MAIN_INFO_APE))
		{
			print $conf->global->MAIN_INFO_APE;
			$s = $mysoc->id_prof_url(3, $mysoc);
			if ($s) print ' - '.$s;
		} else {
			print '&nbsp;';
		}
		print '</td></tr>';
	}

	// ProfId4
	if ($langs->transcountry("ProfId4", $mysoc->country_code) != '-')
	{

		print '<tr class="oddeven"><td>'.$langs->transcountry("ProfId4", $mysoc->country_code).'</td><td>';
		if (! empty($conf->global->MAIN_INFO_RCS))
		{
			print $conf->global->MAIN_INFO_RCS;
			$s = $mysoc->id_prof_url(4, $mysoc);
			if ($s) print ' - '.$s;
		} else {
			print '&nbsp;';
		}
		print '</td></tr>';
	}

	// ProfId5
	if ($langs->transcountry("ProfId5", $mysoc->country_code) != '-')
	{

		print '<tr class="oddeven"><td>'.$langs->transcountry("ProfId5", $mysoc->country_code).'</td><td>';
		if (! empty($conf->global->MAIN_INFO_PROFID5))
		{
			print $conf->global->MAIN_INFO_PROFID5;
			$s = $mysoc->id_prof_url(5, $mysoc);
			if ($s) print ' - '.$s;
		} else {
			print '&nbsp;';
		}
		print '</td></tr>';
	}

	// ProfId6
	if ($langs->transcountry("ProfId6", $mysoc->country_code) != '-')
	{

		print '<tr class="oddeven"><td>'.$langs->transcountry("ProfId6", $mysoc->country_code).'</td><td>';
		if (! empty($conf->global->MAIN_INFO_PROFID6))
		{
			print $conf->global->MAIN_INFO_PROFID6;
			$s = $mysoc->id_prof_url(6, $mysoc);
			if ($s) print ' - '.$s;
		} else {
			print '&nbsp;';
		}
		print '</td></tr>';
	}

	// VAT

	print '<tr class="oddeven"><td>'.$langs->trans("VATIntra").'</td>';
	print '<td>';
	if (! empty($conf->global->MAIN_INFO_TVAINTRA))
	{
		$s='';
		$s.=$conf->global->MAIN_INFO_TVAINTRA;
		$s.='<input type="hidden" name="tva_intra" size="12" maxlength="20" value="'.$conf->global->MAIN_INFO_TVAINTRA.'">';
		if (empty($conf->global->MAIN_DISABLEVATCHECK) && $mysoc->isInEEC())
		{
			$s.=' - ';
			if (! empty($conf->use_javascript_ajax))
			{
				print "\n";
				print '<script language="JavaScript" type="text/javascript">';
				print "function CheckVAT(a) {\n";
				print "newpopup('".DOL_URL_ROOT."/societe/checkvat/checkVatPopup.php?vatNumber='+a,'".dol_escape_js($langs->trans("VATIntraCheckableOnEUSite"))."',500,285);\n";
				print "}\n";
				print '</script>';
				print "\n";
				$s.='<a href="#" onClick="javascript: CheckVAT(document.formsoc.tva_intra.value);">'.$langs->trans("VATIntraCheck").'</a>';
				$s = $form->textwithpicto($s, $langs->trans("VATIntraCheckDesc", $langs->transnoentitiesnoconv("VATIntraCheck")), 1);
			}
			else
			{
				$s.='<a href="'.$langs->transcountry("VATIntraCheckURL", $soc->id_country).'" target="_blank">'.img_picto($langs->trans("VATIntraCheckableOnEUSite"), 'help').'</a>';
			}
		}
		print $s;
	}
	else
	{
		print '&nbsp;';
	}
	print '</td>';
	print '</tr>';


	print '<tr class="oddeven"><td class="tdtop">'.$langs->trans("CompanyObject").'</td><td>' . (! empty($conf->global->MAIN_INFO_SOCIETE_OBJECT) ? nl2br($conf->global->MAIN_INFO_SOCIETE_OBJECT) : '') . '</td></tr>';

	print '</table>';
	print "</div>";

	print '</form>';

	/*
	 *  fiscal year beginning
	 */
	print '<br>';

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td class="titlefield">'.$langs->trans("FiscalYearInformation").'</td><td>'.$langs->trans("Value").'</td>';
	print "</tr>\n";


	print '<tr class="oddeven"><td>'.$langs->trans("FiscalMonthStart").'</td><td>';
	$monthstart=(! empty($conf->global->SOCIETE_FISCAL_MONTH_START)) ? $conf->global->SOCIETE_FISCAL_MONTH_START : 1;
	print dol_print_date(dol_mktime(12, 0, 0, $monthstart, 1, 2000, 1), '%B', 'gm') . '</td></tr>';

	print "</table>";
	print "</div>";

	/*
	 *  tax options
	 */
	print '<br>';
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td class="titlefield">'.$langs->trans("VATManagement").'</td><td>'.$langs->trans("Description").'</td>';
	print '<td class="right">&nbsp;</td>';
	print "</tr>\n";


	print '<tr class="oddeven"><td class="titlefield">';
	print "<input class=\"oddeven\" type=\"radio\" name=\"optiontva\" id=\"use_vat\" disabled value=\"1\"".(empty($conf->global->FACTURE_TVAOPTION)?"":" checked")."> ".$langs->trans("VATIsUsed")."</td>";
	print '<td colspan="2">';
	print "<table>";
	print "<tr><td><label for=\"use_vat\">".$langs->trans("VATIsUsedDesc")."</label></td></tr>";
	if ($mysoc->country_code == 'FR') print "<tr><td><i>".$langs->trans("Example").': '.$langs->trans("VATIsUsedExampleFR")."</i></td></tr>\n";
	print "</table>";
	print "</td></tr>\n";


	print '<tr class="oddeven"><td class="titlefield">';
	print "<input class=\"oddeven\" type=\"radio\" name=\"optiontva\" id=\"no_vat\" disabled value=\"0\"".(empty($conf->global->FACTURE_TVAOPTION)?" checked":"")."> ".$langs->trans("VATIsNotUsed")."</td>";
	print '<td colspan="2">';
	print "<table>";
	print "<tr><td><label=\"no_vat\">".$langs->trans("VATIsNotUsedDesc")."</label></td></tr>";
	if ($mysoc->country_code == 'FR') print "<tr><td><i>".$langs->trans("Example").': '.$langs->trans("VATIsNotUsedExampleFR")."</i></td></tr>\n";
	print "</table>";
	print "</td></tr>\n";

	print "</table>";
	print "</div>";

	/*
	 *  Local Taxes
	 */
	if ($mysoc->useLocalTax(1))    // True if we found at least on vat with a setup adding a localtax 1
	{
		// Local Tax 1
		print '<br>';
		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td class="titlefield">'.$langs->transcountry("LocalTax1Management", $mysoc->country_code).'</td><td>'.$langs->trans("Description").'</td>';
		print '<td class="right">&nbsp;</td>';
		print "</tr>\n";


		print "<tr class=\"oddeven\"><td>";
		print "<input class=\"oddeven\" type=\"radio\" name=\"optionlocaltax1\" id=\"lt1\" disabled value=\"localtax1on\"".(($conf->global->FACTURE_LOCAL_TAX1_OPTION == '1' || $conf->global->FACTURE_LOCAL_TAX1_OPTION == "localtax1on")?" checked":"")."> ".$langs->transcountry("LocalTax1IsUsed", $mysoc->country_code)."</td>";
		print '<td colspan="2">';
		print "<table>";
		print "<tr><td></label for=\"lt1\">".$langs->transcountry("LocalTax1IsUsedDesc", $mysoc->country_code)."</label></td></tr>";
		$example=$langs->transcountry("LocalTax1IsUsedExample", $mysoc->country_code);
		print ($example!="LocalTax1IsUsedExample"?"<tr><td><i>".$langs->trans("Example").': '.$langs->transcountry("LocalTax1IsUsedExample", $mysoc->country_code)."</i></td></tr>\n":"");
		if($conf->global->MAIN_INFO_VALUE_LOCALTAX1!=0)
		{
			print '<tr><td>'.$langs->trans("LTRate").': '. $conf->global->MAIN_INFO_VALUE_LOCALTAX1 .'</td></tr>';
		}
		print '<tr><td class="left">'.$langs->trans("CalcLocaltax").': ';
		if($conf->global->MAIN_INFO_LOCALTAX_CALC1==0)
		{
			print $langs->trans("CalcLocaltax1").' - '.$langs->trans("CalcLocaltax1Desc");
		}
		elseif($conf->global->MAIN_INFO_LOCALTAX_CALC1==1)
		{
			print $langs->trans("CalcLocaltax2").' - '.$langs->trans("CalcLocaltax2Desc");
		}
		elseif($conf->global->MAIN_INFO_LOCALTAX_CALC1==2){
			print $langs->trans("CalcLocaltax3").' - '.$langs->trans("CalcLocaltax3Desc");
		}

		print '</td></tr>';
		print "</table>";
		print "</td></tr>\n";


		print '<tr class="oddeven"><td>';
		print "<input class=\"oddeven\" type=\"radio\" name=\"optionlocaltax1\" id=\"nolt1\" disabled value=\"localtax1off\"".((empty($conf->global->FACTURE_LOCAL_TAX1_OPTION) || $conf->global->FACTURE_LOCAL_TAX1_OPTION == "localtax1off")?" checked":"")."> ".$langs->transcountry("LocalTax1IsNotUsed", $mysoc->country_code)."</td>";
		print '<td colspan="2">';
		print "<table>";
		print "<tr><td><label for=\"no_lt1\">".$langs->transcountry("LocalTax1IsNotUsedDesc", $mysoc->country_code)."</label></td></tr>";
		$example=$langs->transcountry("LocalTax1IsNotUsedExample", $mysoc->country_code);
		print ($example!="LocalTax1IsNotUsedExample"?"<tr><td><i>".$langs->trans("Example").': '.$langs->transcountry("LocalTax1IsNotUsedExample", $mysoc->country_code)."</i></td></tr>\n":"");
		print "</table>";
		print "</td></tr>\n";

		print "</table>";
		print "</div>";
	}
	if ($mysoc->useLocalTax(2))    // True if we found at least on vat with a setup adding a localtax 1
	{
		// Local Tax 2
		print '<br>';
		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td class="titlefield">'.$langs->transcountry("LocalTax2Management", $mysoc->country_code).'</td><td>'.$langs->trans("Description").'</td>';
		print '<td class="right">&nbsp;</td>';
		print "</tr>\n";


		print "<tr class=\"oddeven\"><td>";
		print "<input class=\"oddeven\" type=\"radio\" name=\"optionlocaltax2\" id=\"lt2\" disabled value=\"localtax2on\"".(($conf->global->FACTURE_LOCAL_TAX2_OPTION == '1' || $conf->global->FACTURE_LOCAL_TAX2_OPTION == "localtax2on")?" checked":"")."> ".$langs->transcountry("LocalTax2IsUsed", $mysoc->country_code)."</td>";
		print '<td colspan="2">';
		print "<table>";
		print "<tr><td><label for=\"lt2\">".$langs->transcountry("LocalTax2IsUsedDesc", $mysoc->country_code)."</label></td></tr>";
		$example=$langs->transcountry("LocalTax2IsUsedExample", $mysoc->country_code);
		print ($example!="LocalTax2IsUsedExample"?"<tr><td><i>".$langs->trans("Example").': '.$langs->transcountry("LocalTax2IsUsedExample", $mysoc->country_code)."</i></td></tr>\n":"");
		if($conf->global->MAIN_INFO_VALUE_LOCALTAX2!=0)
		{
			print '<tr><td>'.$langs->trans("LTRate").': '. $conf->global->MAIN_INFO_VALUE_LOCALTAX2 .'</td></tr>';
		}
		print '<tr><td class="left">'.$langs->trans("CalcLocaltax").': ';
		if($conf->global->MAIN_INFO_LOCALTAX_CALC2==0)
		{
			print $langs->trans("CalcLocaltax1").' - '.$langs->trans("CalcLocaltax1Desc");
		}
		elseif($conf->global->MAIN_INFO_LOCALTAX_CALC2==1)
		{
			print $langs->trans("CalcLocaltax2").' - '.$langs->trans("CalcLocaltax2Desc");
		}
		elseif($conf->global->MAIN_INFO_LOCALTAX_CALC2==2)
		{
			print $langs->trans("CalcLocaltax3").' - '.$langs->trans("CalcLocaltax3Desc");
		}

		print '</td></tr>';
		print "</table>";
		print "</td></tr>\n";


		print "<tr class=\"oddeven\"><td width=\"160\"><input class=\"oddeven\" type=\"radio\" name=\"optionlocaltax2\" id=\"nolt2\" disabled value=\"localtax2off\"".((empty($conf->global->FACTURE_LOCAL_TAX2_OPTION) || $conf->global->FACTURE_LOCAL_TAX2_OPTION == "localtax2off")?" checked":"")."> ".$langs->transcountry("LocalTax2IsNotUsed", $mysoc->country_code)."</td>";
		print '<td colspan="2">';
		print "<table>";
		print "<tr><td><label for=\"nolt2\">".$langs->transcountry("LocalTax2IsNotUsedDesc", $mysoc->country_code)."</label></td></tr>";
		$example=$langs->transcountry("LocalTax2IsNotUsedExample", $mysoc->country_code);
		print ($example!="LocalTax2IsNotUsedExample"?"<tr><td><i>".$langs->trans("Example").': '.$langs->transcountry("LocalTax2IsNotUsedExample", $mysoc->country_code)."</i></td></tr>\n":"");
		print "</table>";
		print "</td></tr>\n";

		print "</table>";
		print "</div>";
	}


	// Actions buttons
	print '<div class="tabsAction">';
	print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit">'.$langs->trans("Modify").'</a></div>';
	print '</div>';
}

// End of page
llxFooter();
$db->close();
