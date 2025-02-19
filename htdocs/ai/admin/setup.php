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

$arrayofaifeatures = getLitOfAIFeatures();

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
$arrayofia = array(
	'-1' => $langs->trans('SelectAService'),
	'chatgpt' => 'ChatGPT',
	'groq' => 'Groq',
	'custom' => 'Custom'
	//'gemini' => 'Gemini'
);

$item = $formSetup->newItem('AI_API_SERVICE');	// Name of constant must end with _KEY so it is encrypted when saved into database.
$item->setAsSelect($arrayofia);
$item->cssClass = 'minwidth150';

foreach ($arrayofia as $ia => $ialabel) {
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
	$item->fieldParams['trClass'] = 'iaservice '.$ia;
	$item->cssClass = 'minwidth500 input'.$ia;
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

$form = new Form($db);

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
	print '<br>';
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
			console.log("We select the AI service "+aiservice);
			jQuery(".iaservice").hide();

			if (aiservice != "-1") {
				jQuery(".iaservice."+aiservice).show();
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


if (getDolGlobalString("AI_API_SERVICE")) {
	// Section to test
	print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

	$functioncode = GETPOST('functioncode');
	$out = '';

	if ($functioncode) {
		$labeloffeature = empty($arrayofaifeatures[GETPOST('functioncode')]['label']) ? 'Undefined' : $arrayofaifeatures[GETPOST('functioncode')]['label'];

		//$out .= $langs->trans("Test").' '.$labeloffeature.'...<br><br>';

		if (GETPOST('functioncode') == 'textgenerationemail') {
			$key = 'textgenerationemail';	// The HTML ID of field to fill

			include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
			$showlinktoai = $key;		// 'textgeneration', 'imagegeneration', ...
			$showlinktoailabel = $langs->trans("Test").' '.$labeloffeature;
			$showlinktolayout = 0;
			$formmail = new FormMail($db);
			$htmlname = $key;

			// Fill $out
			include DOL_DOCUMENT_ROOT.'/core/tpl/formlayoutai.tpl.php';

			$out .= '<div id="'.$key.'"></div>';
		} else {
			$out .= $langs->trans("FeatureNotYetAvailable").'<br><br>';
			$functioncode = '';
		}
	}

	if (!$functioncode) {
		// Combo list of AI features
		$out .= '<select name="functioncode" id="functioncode" class="flat minwidth300" placeholder="Test feature">';
		$out .= '<option value="-1">'.$langs->trans("SelectFeatureToTest").'</option>';
		foreach ($arrayofaifeatures as $key => $val) {
			$labelhtml = $langs->trans($arrayofaifeatures[$key]['label']).($arrayofaifeatures[$key]['status'] == 'notused' ? ' <span class="opacitymedium">('.$langs->trans("NotYetAvailable").')</span>' : "");
			$labeltext = $langs->trans($arrayofaifeatures[$key]['label']);
			$out .= '<option value="'.$key.'" data-html="'.dol_escape_htmltag($labelhtml).'"';
			$out .= (GETPOST('functioncode') == $key ? ' selected="selected"' : '');
			$out .= '>'.dol_escape_htmltag($labeltext).'</option>';
		}
		$out .= '</select>';
		$out .= ajax_combobox("functioncode");

		$out .= '<input class="button small" type="submit" name="testmode" value="'.$langs->trans("Test").'">';
	}
	print $out;

	print '</form>';
}

llxFooter();
$db->close();
