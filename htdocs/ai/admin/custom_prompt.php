<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2022 Alice Adminson <aadminson@example.com>
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
 * \file    ai/admin/custom_prompt.php
 * \ingroup ai
 * \brief   Ai other custom page.
 */

// Load Dolibarr environment
require '../../main.inc.php';

global $langs, $user, $conf;

// Libraries
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once '../lib/ai.lib.php';

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ09');	// Used by actions_setmoduleoptions.inc.php

$value = GETPOST('value', 'alpha');
$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');
$type = 'myobject';

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

$setupnotempty =+ count($formSetup->items);

$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

/*
 * Actions
 */

$modulename = GETPOST('module_name');
$pre_prompt = GETPOST('prePrompt', 'alpha');
$post_prompt = GETPOST('postPrompt', 'alpha');
 // get all configs in const AI

 $currentConfigurationsJson = dolibarr_get_const($db, 'AI_CONFIGURATIONS_PROMPT', $conf->entity);
 $currentConfigurations = json_decode($currentConfigurationsJson, true);

if ($action == 'update' && !GETPOST('cancel')) {
	$error = 0;
	if (empty($modulename)) {
		$error++;
		setEventMessages($langs->trans('ErrorInputRequired'), null, 'errors');
	}
	if (!is_array($currentConfigurations)) {
		$currentConfigurations = [];
	}

	if (empty($pre_prompt) && empty($post_prompt)) {
		if (isset($currentConfigurations[$modulename])) {
			unset($currentConfigurations[$modulename]);
		}
	} else {
		$currentConfigurations[$modulename] = [
			'prePrompt' => $pre_prompt,
			'postPrompt' => $post_prompt,
		];
	}

	$newConfigurationsJson = json_encode($currentConfigurations, JSON_UNESCAPED_UNICODE);
	$result = dolibarr_set_const($db, 'AI_CONFIGURATIONS_PROMPT', $newConfigurationsJson, 'chaine', 0, '', $conf->entity);
	if (!$error) {
		if ($result) {
			header("Location: ".$_SERVER['PHP_SELF']);
			setEventMessages($langs->trans("CongigurationUpdated"), null, 'mesgs');
			exit;
		} else {
			setEventMessages($langs->trans("ErrorUpdating"), null, 'errors');
		}
	}
}

/*
 * View
 */

 $form = new Form($db);

 $help_url = '';
 $page_name = "AiCustomPrompt";

 llxHeader('', $langs->trans($page_name), $help_url);

 // Subheader
 $linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

 print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

 // Configuration header
 $head = aiAdminPrepareHead();
 print dol_get_fiche_head($head, 'custom', $langs->trans($page_name), -1, "fa-microchip");

if ($action == 'edit') {
	$out =  '<div id="dragDropAreaTabBar" class="tabBar">';
	$out .='<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	$out .= '<input type="hidden" name="token" value="'.newToken().'">';
	$out .= '<input type="hidden" name="action" value="update">';
	$out .= '<table class="noborder centpercent">';
	$out .= '<thead>';
	$out .= '<tr class="liste_titre">';
	$out .= '<td>'.$langs->trans('Settings').'</td>';
	$out .= '<td>'.$langs->trans('Value').'</td>';
	$out .= '</tr>';
	$out .= '</thead>';
	$out .= '<tbody>';
	$out .= '<tr class="oddeven">';
	$out .= '<td class="col-setup-title">';
	$out .= '<span id="module" class="spanforparamtooltip">'.$langs->trans("Module").'</span>';
	$out .= '</td>';
	$out .= '<td>';

	$sql = "SELECT name FROM llx_const WHERE name LIKE 'MAIN_MODULE_%' AND value = '1'";
	$resql = $db->query($sql);

	if ($resql) {
		$out .= '<select name="module_name" id="module_select" class="flat minwidth500">';
		$out .= '<option></option>';
		while ($obj = $db->fetch_object($resql)) {
			$moduleName = str_replace('MAIN_MODULE_', '', $obj->name);
			$out .= '<option value="' . htmlspecialchars($moduleName) . '">' . htmlspecialchars($moduleName) . '</option>';
		}
	} else {
		$out.= '<option disabled>Erreur :'. $db->lasterror().'</option>';
	}

	$out .= '</select>';
	$out .= '</td>';
	$out .= '</tr>';
	$out .= '<tr class="oddeven">';
	$out .= '<td class="col-setup-title">';
	$out .= '<span id="prePrompt" class="spanforparamtooltip">pre-Prompt</span>';
	$out .= '</td>';
	$out .= '<td>';
	$out .= '<input name="prePrompt" id="prePromptInput"  class="flat minwidth500">';
	$out .= '</td>';
	$out .= '</tr>';
	$out .= '<tr class="oddeven">';
	$out .= '<td class="col-setup-title">';
	$out .= '<span id="postPrompt" class="spanforparamtooltip">Post-prompt</span>';
	$out .= '</td>';
	$out .= '<td>';
	$out .= '<input name="postPrompt" id="postPromptInput"  class="flat minwidth500">';
	$out .= '</td>';
	$out .= '</tr>';
	$out .= '</tbody>';
	$out .= '</table>';
	$out .= $form->buttonsSaveCancel("Save", 'Cancel');

	$out .= '</form>';
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
    });
    </script>";

	print $out;

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


// Page end
print dol_get_fiche_end();

llxFooter();
$db->close();
