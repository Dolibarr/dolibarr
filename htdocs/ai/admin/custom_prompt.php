<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2022 Alice Adminson <aadminson@example.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 * \file    htdocs/ai/admin/custom_prompt.php
 * \ingroup ai
 * \brief   Ai other custom page.
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once '../lib/ai.lib.php';

$langs->loadLangs(array("admin", "website", "other"));

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ09');	// Used by actions_setmoduleoptions.inc.php

if (empty($action)) {
	$action = 'edit';
}

$error = 0;
$setupnotempty = 0;

// Access control
if (!$user->admin) {
	accessforbidden();
}


// Set this to 1 to use the factory to manage constants. Warning, the generated module will be compatible with version v15+ only
$useFormSetup = 1;

if (!class_exists('FormSetup')) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formsetup.class.php';
}

$formSetup = new FormSetup($db);

// Setup conf AI_PROMPT
$item = $formSetup->newItem('AI_CONFIGURATIONS_PROMPT');
$item->defaultFieldValue = '';

$setupnotempty += count($formSetup->items);

$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

// List of AI features
$arrayofaifeatures = array(
	'textgenerationemail' => array('label' => $langs->trans('TextGeneration').' ('.$langs->trans("EmailContent").')', 'picto'=>'', 'status'=>'development'),
	'textgenerationwebpage' => array('label' => $langs->trans('TextGeneration').' ('.$langs->trans("WebsitePage").')', 'picto'=>'', 'status'=>'development'),
	'textgeneration' => array('label' => $langs->trans('TextGeneration').' ('.$langs->trans("Other").')', 'picto'=>'', 'status'=>'notused'),
	'imagegeneration' => array('label' => 'ImageGeneration', 'picto'=>'', 'status'=>'notused'),
	'videogeneration' => array('label' => 'VideoGeneration', 'picto'=>'', 'status'=>'notused'),
	'transcription' => array('label' => 'Transcription', 'picto'=>'', 'status'=>'notused'),
	'translation' => array('label' => 'Translation', 'picto'=>'', 'status'=>'notused'),
	'audiotext' => array('label' => 'AudioText', 'picto'=>'', 'status'=>'notused')
);


/*
 * Actions
 */

$functioncode = GETPOST('functioncode', 'alpha');
$pre_prompt = GETPOST('prePrompt');
$post_prompt = GETPOST('postPrompt');
// get all configs in const AI

$currentConfigurationsJson = getDolGlobalString('AI_CONFIGURATIONS_PROMPT');
$currentConfigurations = json_decode($currentConfigurationsJson, true);

if ($action == 'update' && GETPOST('cancel')) {
	$action = 'edit';
}
if ($action == 'update' && !GETPOST('cancel')) {
	$error = 0;
	if (empty($functioncode)) {
		$error++;
		setEventMessages($langs->trans('ErrorInputRequired'), null, 'errors');
	}
	if (!is_array($currentConfigurations)) {
		$currentConfigurations = [];
	}

	if (empty($functioncode) || (empty($pre_prompt) && empty($post_prompt))) {
		if (isset($currentConfigurations[$functioncode])) {
			unset($currentConfigurations[$functioncode]);
		}
	} else {
		$currentConfigurations[$functioncode] = [
			'prePrompt' => $pre_prompt,
			'postPrompt' => $post_prompt,
		];
	}

	$newConfigurationsJson = json_encode($currentConfigurations, JSON_UNESCAPED_UNICODE);
	$result = dolibarr_set_const($db, 'AI_CONFIGURATIONS_PROMPT', $newConfigurationsJson, 'chaine', 0, '', $conf->entity);
	if (!$error) {
		if ($result) {
			header("Location: ".$_SERVER['PHP_SELF']);
			setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
			exit;
		} else {
			setEventMessages($langs->trans("ErrorUpdating"), null, 'errors');
		}
	}

	$action = 'edit';
}

if ($action == 'updatePrompts') {
	$key = GETPOST('key', 'alpha');

	$currentConfigurations[$key] = [
		'prePrompt' => $pre_prompt,
		'postPrompt' => $post_prompt,
	];

	$newConfigurationsJson = json_encode($currentConfigurations, JSON_UNESCAPED_UNICODE);
	$result = dolibarr_set_const($db, 'AI_CONFIGURATIONS_PROMPT', $newConfigurationsJson, 'chaine', 0, '', $conf->entity);
	if (!$error) {
		$action = '';
		if ($result) {
			header("Location: ".$_SERVER['PHP_SELF']);
			setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
			exit;
		} else {
			setEventMessages($langs->trans("ErrorUpdating"), null, 'errors');
		}
	}
}

if ($action == 'confirm_deleteproperty' && GETPOST('confirm') == 'yes') {
	$key = GETPOST('key', 'alpha');

	if (isset($currentConfigurations[$key])) {
		unset($currentConfigurations[$key]);

		$newConfigurationsJson = json_encode($currentConfigurations, JSON_UNESCAPED_UNICODE);
		$res = dolibarr_set_const($db, 'AI_CONFIGURATIONS_PROMPT', $newConfigurationsJson, 'chaine', 0, '', $conf->entity);
		if ($res) {
			header("Location: ".$_SERVER['PHP_SELF']);
			setEventMessages($langs->trans("RecordDeleted"), null, 'mesgs');
			exit;
		} else {
			setEventMessages($langs->trans("NoRecordDeleted"), null, 'errors');
		}
	}
}


/*
 * View
 */

$form = new Form($db);

$help_url = '';
$title = "AiSetup";

llxHeader('', $langs->trans($title), $help_url);

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($title), $linkback, 'title_setup');

// Configuration header
$head = aiAdminPrepareHead();
print dol_get_fiche_head($head, 'custom', $langs->trans($title), -1, "fa-microchip");

//$newbutton = '<a href="'.$_SERVER["PHP_SELF"].'?action=create">'.$langs->trans("New").'</a>';
$newbutton = '';

print load_fiche_titre($langs->trans("AIPromptForFeatures"), $newbutton, '');

if ($action == 'deleteproperty') {
	$formconfirm = $form->formconfirm(
		$_SERVER["PHP_SELF"].'?key='.urlencode(GETPOST('key', 'alpha')),
		$langs->trans('Delete'),
		$langs->trans('ConfirmDeleteSetup', GETPOST('key', 'alpha')),
		'confirm_deleteproperty',
		'',
		0,
		1
	);
	print $formconfirm;
}

if ($action == 'edit' || $action == 'deleteproperty') {
	$out = '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	$out .= '<input type="hidden" name="token" value="'.newToken().'">';
	$out .= '<input type="hidden" name="action" value="update">';


	$out .= '<table class="noborder centpercent">';
	$out .= '<thead>';
	$out .= '<tr class="liste_titre">';
	$out .= '<td>'.$langs->trans('Add').'</td>';
	$out .= '<td></td>';
	$out .= '</tr>';
	$out .= '</thead>';
	$out .= '<tbody>';
	$out .= '<tr class="oddeven">';
	$out .= '<td class="col-setup-title">';
	$out .= '<span id="module" class="spanforparamtooltip">'.$langs->trans("Feature").'</span>';
	$out .= '</td>';
	$out .= '<td>';
	// Combo list of AI features
	$out .= '<select name="functioncode" id="functioncode" class="flat minwidth500">';
	$out .= '<option>&nbsp;</option>';
	foreach ($arrayofaifeatures as $key => $val) {
		$labelhtml = $langs->trans($arrayofaifeatures[$key]['label']).($arrayofaifeatures[$key]['status'] == 'notused' ? ' <span class="opacitymedium">('.$langs->trans("NotYetAvailable").')</span>' : "");
		$labeltext = $langs->trans($arrayofaifeatures[$key]['label']);
		$out .= '<option value="'.$key.'" data-html="'.dol_escape_htmltag($labelhtml).'">'.dol_escape_htmltag($labeltext).'</option>';
	}
	/*
	$sql = "SELECT name FROM llx_const WHERE name LIKE 'MAIN_MODULE_%' AND value = '1'";
	$resql = $db->query($sql);

	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			$moduleName = str_replace('MAIN_MODULE_', '', $obj->name);
			$out .= '<option value="' . htmlspecialchars($moduleName) . '">' . htmlspecialchars($moduleName) . '</option>';
		}
	} else {
		$out.= '<option disabled>Erreur :'. $db->lasterror().'</option>';
	}
	*/
	$out .= '</select>';
	$out .= ajax_combobox("functioncode");

	$out .= '</td>';
	$out .= '</tr>';
	$out .= '<tr class="oddeven">';
	$out .= '<td class="col-setup-title">';
	$out .= '<span id="prePrompt" class="spanforparamtooltip">'.$langs->trans("Pre-Prompt").'</span>';
	$out .= '</td>';
	$out .= '<td>';
	$out .= '<textarea class="flat minwidth500 quatrevingtpercent" id="prePromptInput" name="prePrompt" rows="3"></textarea>';
	$out .= '</td>';
	$out .= '</tr>';
	$out .= '<tr class="oddeven">';
	$out .= '<td class="col-setup-title">';
	$out .= '<span id="postPrompt" class="spanforparamtooltip">'.$langs->trans("Post-Prompt").'</span>';
	$out .= '</td>';
	$out .= '<td>';
	$out .= '<textarea class="flat minwidth500 quatrevingtpercent" id="postPromptInput" name="postPrompt" rows="3"></textarea>';
	$out .= '</td>';
	$out .= '</tr>';
	$out .= '</tbody>';
	$out .= '</table>';

	$out .= $form->buttonsSaveCancel("Add", "");
	$out .= '</form>';
	$out .= '<br><br><br>';

	print $out;
}


if ($action == 'edit' || $action == 'create' || $action == 'deleteproperty') {
	$out = '';

	if (!empty($currentConfigurations)) {
		$out = '<table class="noborder centpercent">';
		foreach ($currentConfigurations as $key => $config) {
			if (!empty($key) && !preg_match('/^[a-z]+$/i', $key)) {	// Ignore empty saved setup
				continue;
			}

			$out .= '<thead>';
			$out .= '<tr class="liste_titre">';
			$out .= '<td>'.$arrayofaifeatures[$key]['picto'].' '.$langs->trans($arrayofaifeatures[$key]['label']);
			$out .= '<a class="viewfielda reposition marginleftonly marginrighttonly showInputBtn" href="#" data-index="'.$key.'" data-state="edit" data-icon-edit="'.dol_escape_htmltag(img_edit()).'" data-icon-cancel="'.dol_escape_htmltag(img_view()).'">'.img_edit().'</a>';
			$out .= '<a class="deletefielda reposition marginleftonly right" href="'.$_SERVER["PHP_SELF"].'?action=deleteproperty&token='.newToken().'&key='.urlencode($key).'">'.img_delete().'</a>';
			$out .= '</td>';
			$out .= '<td></td>';
			$out .= '</tr>';
			$out .= '</thead>';
			$out .= '<tbody>';

			$out .= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
			$out .= '<input type="hidden" name="token" value="'.newToken().'">';
			$out .= '<input type="hidden" name="key" value="'.$key.'" />';
			$out .= '<input type="hidden" name="action" value="updatePrompts">';
			$out .= '<tr class="oddeven">';
			$out .= '<td class="col-setup-title">';
			$out .= '<span id="prePrompt" class="spanforparamtooltip">'.$langs->trans("Pre-Prompt").'</span>';
			$out .= '</td>';
			$out .= '<td>';
			$out .= '<textarea class="flat minwidth500" id="prePromptInput_'.$key.'" name="prePrompt" rows="2" disabled>'.$config['prePrompt'].'</textarea>';
			$out .= '</td>';
			$out .= '</tr>';
			$out .= '<tr class="oddeven">';
			$out .= '<td class="col-setup-title">';
			$out .= '<span id="postPrompt" class="spanforparamtooltip">'.$langs->trans("Post-Prompt").'</span>';
			$out .= '</td>';
			$out .= '<td>';
			$out .= '<textarea class="flat minwidth500" id="postPromptInput_'.$key.'" name="postPrompt" rows="2" disabled>'.$config['postPrompt'].'</textarea>';
			$out .= '<br><input type="submit" class="button small submitBtn" name="modify" data-index="'.$key.'" style="display: none;" value="'.dol_escape_htmltag($langs->trans("Modify")).'"/>';

			$out .= '</td>';
			$out .= '</tr>';
			$out .= '</form>';
		}
		$out .= '</tbody>';
		$out .= '</table>';
	}


	$out .= "<script>
    var configurations =  ".$currentConfigurationsJson.";
    $(document).ready(function() {
        $('#module_select').change(function() {
            var selectedModule = $(this).val();
            var moduleConfig = configurations[selectedModule];

            if (moduleConfig) {
                $('#prePromptInput').val(moduleConfig.prePrompt || '');
                $('#postPromptInput').val(moduleConfig.postPrompt || '');
            } else {
                $('#prePromptInput').val('');
                $('#postPromptInput').val('');
            }
        });

		$('.showInputBtn').click(function() {
			event.preventDefault();
			var index = $(this).data('index');
			var state = $(this).data('state');

			if(state === 'edit') {
				$('#prePromptInput_'+index).removeAttr('disabled').focus();
				$('#postPromptInput_'+index).removeAttr('disabled');
				$('.submitBtn[data-index=' + index + ']').show();
				$(this).html($(this).data('icon-cancel'));
				$(this).data('state', 'cancel');

			} else {

				$('#prePromptInput_'+index).attr('disabled', 'disabled');
				$('#postPromptInput_'+index).attr('disabled', 'disabled');
				$('.submitBtn[data-index=' + index + ']').hide();
				$(this).html($(this).data('icon-edit'));
				$(this).data('state', 'edit');
			}
		});
	});


    </script>";

	print $out;

	print '<br>';
}

if (empty($setupnotempty)) {
	print '<br>'.$langs->trans("NothingToSetup");
}


// Page end
print dol_get_fiche_end();

llxFooter();
$db->close();
