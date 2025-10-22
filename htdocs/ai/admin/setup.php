<?php
/* Copyright (C) 2004-2017	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2022		Alice Adminson				<aadminson@example.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
 * Coryright (C) 2024		Alexandre Spangaro			<alexandre@inovea-conseil.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    htdocs/ai/admin/setup.php
 * \ingroup ai
 * \brief   Ai setup page.
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php";
require_once DOL_DOCUMENT_ROOT."/ai/lib/ai.lib.php";

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

$langs->loadLangs(array("admin", "website", "other"));


// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ09');	// Used by actions_setmoduleoptions.inc.php

if (empty($action)) {
	$action = 'edit';
}

$content = GETPOST('content');

$error = 0;
$setupnotempty = 0;


// Set this to 1 to use the factory to manage constants. Warning, the generated module will be compatible with version v15+ only
$useFormSetup = 1;

if (!class_exists('FormSetup')) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formsetup.class.php';
}

$formSetup = new FormSetup($db);

// List all available IA
$arrayofai = getListOfAIServices();

// List all available features
$arrayofaifeatures = getListOfAIFeatures();

$item = $formSetup->newItem('AI_API_SERVICE');	// Name of constant must end with _KEY so it is encrypted when saved into database.
$item->setAsSelect($arrayofai);
$item->cssClass = 'minwidth150';

foreach ($arrayofai as $ia => $iarecord) {
	$ialabel = $iarecord['label'];
	// Setup conf AI_PUBLIC_INTERFACE_TOPIC
	/*$item = $formSetup->newItem('AI_API_'.strtoupper($ia).'_ENDPOINT');	// Name of constant must end with _KEY so it is encrypted when saved into database.
	$item->defaultFieldValue = '';
	$item->cssClass = 'minwidth500';*/

	$item = $formSetup->newItem('AI_API_'.strtoupper($ia).'_KEY')->setAsSecureKey();	// Name of constant must end with _KEY so it is encrypted when saved into database.
	$item->nameText = $langs->trans("AI_API_KEY").' ('.$ialabel.')';
	$item->defaultFieldValue = '';
	$item->fieldParams['hideGenerateButton'] = 1;
	$item->fieldParams['trClass'] = 'iaservice '.$ia;
	$item->cssClass = 'minwidth500 text-security input'.$ia;

	$item = $formSetup->newItem('AI_API_'.strtoupper($ia).'_URL');	// Name of constant must end with _KEY so it is encrypted when saved into database.
	$item->nameText = $langs->trans("AI_API_URL").' ('.$ialabel.')';
	$item->defaultFieldValue = '';
	$item->fieldParams['trClass'] = 'iaservice iaurl '.$ia;
	$item->cssClass = 'minwidth500 input'.$ia;
	if ($ia == 'custom') {
		$item->fieldAttr['placeholder'] = 'https://domainofapi.com/v1/';
	}
}

$setupnotempty = + count($formSetup->items);


$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

// Access control
if (!$user->admin) {
	accessforbidden();
}
if (!isModEnabled('ai')) {
	accessforbidden('Module AI not activated.');
}


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

$action = 'edit';


/*
 * View
 */

$help_url = '';
$title = "AiSetup";

llxHeader('', $langs->trans($title), $help_url, '', 0, 0, '', '', '', 'mod-ai page-admin');

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($title), $linkback, 'title_setup');

// Configuration header
$head = aiAdminPrepareHead();
print dol_get_fiche_head($head, 'settings', $langs->trans($title), -1, "ai");


if ($action == 'edit') {
	print $formSetup->generateOutput(true);
} elseif (!empty($formSetup->items)) {
	print $formSetup->generateOutput();
	print '<div class="tabsAction">';
	print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&token='.newToken().'">'.$langs->trans("Modify").'</a>';
	print '</div>';
} else {
	print '<br>'.$langs->trans("NothingToSetup");
}


if (empty($setupnotempty)) {
	print '<br>'.$langs->trans("NothingToSetup");
}

print '<script type="text/javascript">
    jQuery(document).ready(function() {
		function showHideAIService(aiservice) {
			console.log("showHideAIService: We select the AI service "+aiservice);
			jQuery(".iaservice").hide();

			if (aiservice != "-1") {
				jQuery(".iaservice."+aiservice).show();
				const arrayofia = {';
$i = 0;
foreach ($arrayofai as $key => $airecord) {
	if ($key == -1) {
		continue;
	}
	if ($i) {
		print ', ';
	}
	$i++;
	print dol_escape_js($key).': \''.dol_escape_js($airecord['url']).'\'';
}
print '};
				console.log("Check URL for .iaurl."+aiservice+" .input"+aiservice);
				if (jQuery(".iaurl."+aiservice+" .input"+aiservice).val() == \'\') {
					console.log("URL is empty, we fill with default value of IA selected");
					jQuery(".iaurl."+aiservice+" .input"+aiservice).val(arrayofia[aiservice]);
				}
			}
		}

		jQuery("#AI_API_SERVICE").change(function() {
	        var aiservice = $(this).val();

			showHideAIService(aiservice);
		});

		showHideAIService("'.getDolGlobalString("AI_API_SERVICE").'");
	});
</script>';

// Page end
print dol_get_fiche_end();


// The section for test

if (getDolGlobalString("AI_API_SERVICE")) {
	print '<br>';

	// Section to test
	print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';


	$key = 'textgenerationother';	// The HTML ID of field to fill

	//if (GETPOST('functioncode') == 'textgenerationemail') {

	print '<br>';
	//print '<hr>';

	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
	include_once DOL_DOCUMENT_ROOT."/core/class/html.formai.class.php";
	$formai = new FormAI($db);
	$formmail = new FormMail($db);

	$showlinktoai = $key;		// 'textgeneration', 'imagegeneration', ...
	$showlinktoailabel = $langs->trans("AITestText");
	$showlinktolayout = 0;
	$htmlname = $key;
	$formmail->withaiprompt = '';

	// Fill $out

	$out = $langs->trans("Test").': &nbsp; ';
	include DOL_DOCUMENT_ROOT.'/core/tpl/formlayoutai.tpl.php';
	print $out;

	print '<br><textarea id="'.$htmlname.'" placeholder="Lore ipsum..." class="quatrevingtpercent" rows="4"></textarea>';	// The div

	print '<br><br>';


	$showlinktoai .= 'html';
	$htmlname .= 'html';
	$formmail->withaiprompt = 'html';

	// Fill $out
	$out = $langs->trans("Test").': &nbsp; ';
	include DOL_DOCUMENT_ROOT.'/core/tpl/formlayoutai.tpl.php';
	print $out;

	print '<br>';
	$doleditor = new DolEditor($htmlname, '', '', 100, 'dolibarr_details');
	print $doleditor->Create(1);


	print '</form>';
}

llxFooter();
$db->close();
