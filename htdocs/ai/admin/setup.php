<?php
/* Copyright (C) 2004-2017	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2022		Lamrani Abdel
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024-2025	Frédéric France				<frederic.france@free.fr>
 * Coryright (C) 2024		Alexandre Spangaro			<alexandre@inovea-conseil.com>
 * Copyright (C) 2026		Nick Fragoulis
 * Copyright (C) 2026		Jose Martinez				<jose.martinez@pichinov.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY, without even the implied warranty of
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
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php";
require_once DOL_DOCUMENT_ROOT."/ai/lib/ai.lib.php";

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

// List all available AI
$arrayofai = getListOfAIServices();

// List all available features
$arrayofaifeatures = getListOfAIFeatures();

// Main Service Selection
$item = $formSetup->newItem('AI_API_SERVICE');	// Name of constant must end with _KEY so it is encrypted when saved into database.
$item->setAsSelect($arrayofai);
$item->cssClass = 'minwidth150';

// Loop for Provider Configs
foreach ($arrayofai as $ia => $iarecord) {
	if ($ia == '-1') {
		continue;
	}
	$ialabel = $iarecord['label'];
	// Setup conf AI_PUBLIC_INTERFACE_TOPIC
	/*$item = $formSetup->newItem('AI_API_'.strtoupper($ia).'_ENDPOINT');	// Name of constant must end with _KEY so it is encrypted when saved into database.
	$item->defaultFieldValue = '';
	$item->cssClass = 'minwidth500';*/

	// API Key
	$item = $formSetup->newItem('AI_API_'.strtoupper($ia).'_KEY')->setAsSecureKey();	// Name of constant must end with _KEY so it is encrypted when saved into database.
	$item->nameText = $langs->trans("AI_API_KEY").' ('.$ialabel.')';
	$item->defaultFieldValue = '';
	$item->fieldParams['hideGenerateButton'] = 1;
	$item->fieldParams['trClass'] = 'iaservice '.$ia;
	$item->cssClass = 'minwidth500 text-security input'.$ia;
	$item->helpText = '<span class="helptoshow">HelpToShow</span>';

	// API URL
	$item = $formSetup->newItem('AI_API_'.strtoupper($ia).'_URL');	// Name of constant must end with _KEY so it is encrypted when saved into database.
	$item->nameText = $langs->trans("AI_API_URL").' ('.$ialabel.')';
	$item->defaultFieldValue =  $iarecord['url'];
	$item->fieldParams['trClass'] = 'iaservice iaurl '.$ia;
	$item->cssClass = 'minwidth500 input'.$ia;
	if ($ia == 'custom') {
		$item->fieldAttr['placeholder'] = 'https://domainofapi.com/v1/';
	}

	// Model used for text generation. It was readable on the assistant page but
	// nothing could set it, so a provider whose default model does not exist
	// answered 403 with no way to correct it from the interface.
	$item = $formSetup->newItem('AI_API_'.strtoupper($ia).'_MODEL_TEXT');
	$item->nameText = $langs->trans("AI_API_MODEL").' ('.$ialabel.')';
	$item->defaultFieldValue = $iarecord['textgeneration']['default'] ?? '';
	$item->fieldParams['trClass'] = 'iaservice '.$ia;
	$item->cssClass = 'minwidth500 input'.$ia.' aimodelinput aimodelinput'.$ia;
	$item->fieldAttr['placeholder'] = $iarecord['textgeneration']['default'] ?? $langs->trans("AIModelNamePlaceholder");
	$item->helpText = $langs->trans("AIModelNameHelp");
}

$setupnotempty = + count($formSetup->items);


$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

// Access control
// Setup intentionally stays admin-only: per @sonikf and @eldy feedback,
// the AI module's technical setup (API keys, provider configuration) is
// a task done by the admin user, just like for every other Dolibarr module.
// Per-user/group AI usage is gated separately by 'ai/assistant/use'.
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
$linkback = '<a href="'.($backtopage ? $backtopage : dolBuildUrl(DOL_URL_ROOT.'/admin/modules.php', ['restore_lastsearch_values' => 1])).'">'.img_picto($langs->trans("BackToModuleList"), 'back', 'class="pictofixedwidth"').'<span class="hideonsmartphone">'.$langs->trans("BackToModuleList").'</span></a>';

print load_fiche_titre($langs->trans($title), $linkback, 'title_setup');

// Configuration header
$head = aiAdminPrepareHead();
/**
 * Models the configured provider actually serves, offered as a fill-in for the
 * model field. Typing a name that does not exist there answers 403 with nothing
 * on screen to explain it, so the list is shown where the field is edited.
 *
 * @param  DoliDB   $db    Database handler.
 * @param  Translate $langs Language object.
 * @return string          HTML block, empty when no provider is configured.
 */
function aiModelPickerOutput($db, $langs)
{
	$service = getDolGlobalString('AI_API_SERVICE');
	if (empty($service) || $service === '-1') {
		return '';
	}

	$list = getAiProviderModelList($db, (GETPOST('action', 'aZ09') === 'refreshaimodels'));
	if (empty($list['models'])) {
		return '<div class="opacitymedium">'.$langs->trans("AIModelListUnavailable").' <a href="'.$_SERVER["PHP_SELF"].'?action=refreshaimodels&token='.newToken().'">'.$langs->trans("Refresh").'</a></div>';
	}

	// A configured or defaulted model that the provider does not serve fails at
	// runtime as a 403 with nothing on screen to explain it. Say so here, while
	// the field is in front of the administrator.
	$services = getListOfAIServices();
	$configured = getDolGlobalString('AI_API_'.strtoupper($service).'_MODEL_TEXT', $services[$service]['textgeneration']['default'] ?? '');
	$out = '';
	if ($configured !== '' && !in_array($configured, $list['models'], true)) {
		$warn = $langs->trans("AIModelNotServedByProvider", dol_escape_htmltag($configured));
		$closest = aiSuggestClosestModel($configured, $list['models']);
		if ($closest !== '') {
			$warn .= ' '.$langs->trans("AIModelClosestAvailable", dol_escape_htmltag($closest));
		}
		$out .= info_admin($warn, 0, 0, 'warning');
	}

	$out .= '<div class="aimodellist"><span class="opacitymedium">'.$langs->trans("AIModelsAvailableOnProvider").'</span> ';
	foreach ($list['models'] as $model) {
		$out .= '<a href="#" class="aimodelpick marginrightonly" data-model="'.dol_escape_htmltag($model).'">'.dol_escape_htmltag($model).'</a> ';
	}
	$out .= ' <a href="'.$_SERVER["PHP_SELF"].'?action=refreshaimodels&token='.newToken().'">'.$langs->trans("Refresh").'</a></div>';
	$out .= '<script>
	jQuery(document).ready(function () {
		jQuery(".aimodelpick").click(function (e) {
			e.preventDefault();
			jQuery(".aimodelinput'.strtolower($service).'").val(jQuery(this).data("model"));
		});
	});
	</script>';

	return $out;
}

print dol_get_fiche_head($head, 'settings', $langs->trans($title), -1, "ai");


if ($action == 'edit') {
	print $formSetup->generateOutput(true);
	print aiModelPickerOutput($db, $langs);
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
	print dol_sanitizeKeyCode($key).': \''.dol_escape_js($airecord['url']).'\'';
}
print '};
				const arrayofextlink = {';
$i = 0;
foreach ($arrayofai as $key => $airecord) {
	if ($key == -1) {
		continue;
	}
	if ($i) {
		print ', ';
	}
	$i++;
	print dol_sanitizeKeyCode($key).': \''.dol_escape_js($airecord['setup']).'\'';
}
print '};
				console.log("Check URL for .iaurl."+aiservice+" .input"+aiservice);
				if (jQuery(".iaurl."+aiservice+" .input"+aiservice).val() == \'\') {
					console.log("URL is empty, we fill with default value of IA selected");
					jQuery(".iaurl."+aiservice+" .input"+aiservice).val(arrayofia[aiservice]);
				}
				jQuery(".helptoshow").text(arrayofextlink[aiservice]);
			}
		}

		jQuery("#AI_API_SERVICE").change(function() {
	        var aiservice = $(this).val();

			showHideAIService(aiservice);

			jQuery(".sectiontest").hide();	/* Hide test section, will appear after the save */
		});

		showHideAIService("'.getDolGlobalString("AI_API_SERVICE").'");
	});
</script>';

// Page end
print dol_get_fiche_end();


// The section for test

if (getDolGlobalString("AI_API_SERVICE")) {
	print '<br><div class="sectiontest">';

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

	print ' &nbsp; ';
	if (getDolGlobalString("AI_DEBUG")) {
		print ' <span class="small opacitymedium">'.$langs->trans("DebugOnInFile", 'dolibarr_ai.log').'</span>';
	} else {
		print ' <span class="small opacitymedium">'.$langs->trans("DebugOff", 'dolibarr_ai.log').'</span>';
	}

	print '<br><textarea id="'.$htmlname.'" placeholder="Click on picto to enter a prompt or enter a message and click picto to make text transformation..." class="quatrevingtpercent" rows="4"></textarea>';	// The div

	print '<br><br>';


	$showlinktoai .= 'html';
	$htmlname .= 'html';
	$formmail->withaiprompt = 'html';

	// Fill $out
	$out = $langs->trans("Test").': &nbsp; ';
	include DOL_DOCUMENT_ROOT.'/core/tpl/formlayoutai.tpl.php';
	print $out;

	print ' &nbsp; ';
	if (getDolGlobalString("AI_DEBUG")) {
		print ' <span class="small opacitymedium">'.$langs->trans("DebugOnInFile", 'dolibarr_ai.log').'</span>';
	} else {
		print ' <span class="small opacitymedium">'.$langs->trans("DebugOff", 'dolibarr_ai.log').'</span>';
	}

	print '<br>';
	$doleditor = new DolEditor($htmlname, '', '', 150, 'dolibarr_details');
	print $doleditor->Create(1);

	print '</form>';

	print '</div>';
}

llxFooter();
$db->close();
