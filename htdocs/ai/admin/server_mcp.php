<?php
/* Copyright (C) 2004-2017	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2022		Alice Adminson				<aadminson@example.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
 * Coryright (C) 2024		Alexandre Spangaro			<alexandre@inovea-conseil.com>
 * Copyright (C) 2026		Nick Fragoulis
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY, without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file htdocs/ai/admin/server_mcp.php
 * \ingroup ai
 * \brief MCP Server & Assistant Configuration Page
 */

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 * @var Form $form
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT . "/core/class/html.form.class.php";
require_once DOL_DOCUMENT_ROOT . "/core/class/doleditor.class.php";
require_once DOL_DOCUMENT_ROOT . "/ai/lib/ai.lib.php";
require_once DOL_DOCUMENT_ROOT . "/core/lib/functions2.lib.php";
require_once DOL_DOCUMENT_ROOT . "/core/lib/security.lib.php";


$langs->loadLangs(array("admin", "website", "other"));

// Access control
if (!$user->admin) {
	accessforbidden();
}
if (!isModEnabled('ai')) {
	accessforbidden('Module AI not activated.');
}

// Parameters
$action = GETPOST('action', 'aZ09');

/*
 * ACTIONS
 */

// Main Settings
if ($action == 'update') {
	$error = 0;

	if (GETPOSTISSET('AI_ASK_FOR_CONFIRMATION')) {
		$res = dolibarr_set_const($db, "AI_ASK_FOR_CONFIRMATION", GETPOSTINT("AI_ASK_FOR_CONFIRMATION"), 'int', 0, '', $conf->entity);
		if ($res <= 0) $error++;
	}

	if (GETPOSTISSET('AI_LOG_RETENTION')) {
		$res = dolibarr_set_const($db, "AI_LOG_RETENTION", GETPOST("AI_LOG_RETENTION"), 'int', 0, '', $conf->entity);
		if ($res <= 0) {
			$error++;
		}
	}

	if (GETPOSTISSET('AI_DEFAULT_INPUT_MODE')) {
		$res = dolibarr_set_const($db, "AI_DEFAULT_INPUT_MODE", GETPOST("AI_DEFAULT_INPUT_MODE"), 'chaine', 0, '', $conf->entity);
		if ($res <= 0) {
			$error++;
		}
	}

	if (GETPOSTISSET('AI_INTENT_PROMPT')) {
		$res = dolibarr_set_const($db, "AI_INTENT_PROMPT", GETPOST("AI_INTENT_PROMPT"), 'chaine', 0, '', $conf->entity);
		if ($res <= 0) {
			$error++;
		}
	}

	if ($error) {
		setEventMessages($langs->trans("ErrorSavingSettings"), null, 'errors');
	} else {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	}

	header("Location: ".$_SERVER["PHP_SELF"]."?mainmenu=home");
	exit;
}

// Test connection
if ($action == 'test_provider') {
	$service = GETPOST('service_key', 'aZ09');
	if ($service) {
		$credential = getDolGlobalString('AI_API_' . strtoupper($service) . '_KEY');
		$url = getDolGlobalString('AI_API_' . strtoupper($service) . '_URL');

		// Decrypt if needed
		if (preg_match('/^crypted:/', $credential)) {
			$credential = dol_decode(substr($credential, 8));
		} elseif (preg_match('/^dolcrypt:/', $credential)) {
			$credential = dolDecrypt($credential, '');
		}

		// Only proceed if the key is valid (decrypted or not encrypted)
		if ($credential !== null) {
			$res = testAIConnection($service, $credential, $url);

			if ($res['success']) {
				setEventMessages($langs->trans("ConnectionSuccessful") . $res['message'], null, 'mesgs');
			} else {
				setEventMessages($langs->trans("ConnectionFailed") . $res['message'], null, 'errors');
			}
		}
	}
}

// External Access Settings
if ($action == 'update_external') {
	$error = 0;

	$user_id = GETPOSTINT("AI_MCP_USER_ID");
	if (GETPOSTISSET('AI_MCP_USER_ID')) {
		$res = dolibarr_set_const($db, "AI_MCP_USER_ID", $user_id,  'int', 0, '', $conf->entity);
		if ($res <= 0) {
			$error++;
		}
	}

	if ($error) {
		setEventMessages($langs->trans("ErrorSavingSettings"), null, 'errors');
	} else {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	}

	header("Location: ".$_SERVER["PHP_SELF"]."?mainmenu=home");
	exit;
}

// Generate New API Key
if ($action == 'generate_key') {
	$newKey = dolGetRandomBytes(64);

	if (empty($newKey)) {
		setEventMessages($langs->trans("KeyGenerationFailed"), null, 'errors');
	} else {
		if (dolibarr_set_const($db, 'AI_MCP_API_KEY', $newKey, 'chaine', 0, '', $conf->entity) > 0) {
			setEventMessages($langs->trans("KeyGenerationSuccessfull"), null, 'mesgs');
		} else {
			setEventMessages($langs->trans("KeySaveFailed"), null, 'errors');
		}
	}
}

/*
 * VIEW
 */

$help_url = '';
$title = "AIMCPConfig";
llxHeader('', $langs->trans($title), $help_url, '', 0, 0, '', '', '', 'mod-ai page-admin');


$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($title, $linkback, 'title_setup');


$head = aiAdminPrepareHead();
print dol_get_fiche_head($head, 'servermcp', "MCP Server", -1, "ai");

print '<span class="opacitymedium">' . $langs->trans("ConfigHelp") . '</span><br><br>';

$form = new Form($db);

// Load Current Values
$apiKey = getDolGlobalString('AI_MCP_API_KEY');

// Default Prompt Logic
$defaultPromptText = " ROLE: Dolibarr ERP AI.
GOAL: Map user intent to specific JSON commands.

CONSTRAINTS:
1. OUTPUT: Single valid JSON object ONLY. No Markdown. No text.
   Format: {\"tool\": \"tool_name\", \"arguments\": {\"argument_name\": \"argument_value\", ...}}
2. TOOLS: Use ONLY provided tools. If no tool or thirdparty fits, you must first use 'respond_to_user' to inform user.
3. ARGS: strict adherence to schema. Do not invent parameters.
4. MISSING INFO: If a required argument (like an ID) is missing, use 'ask_for_clarification'.
5. SAFETY: For DELETE/UPDATE actions, you MUST use 'ask_for_confirmation'.";

$currentPrompt = getDolGlobalString('AI_INTENT_PROMPT', $defaultPromptText);

// Settings
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';

// Enable/Disable
print '<tr class="oddeven">';
print '<td class="titlefield" width="30%">' . $langs->trans('EnableMCPServer') . '</td>';
print '<td>';
print ajax_constantonoff('AI_MCP_ENABLED');
print ' <span class="opacitymedium">' . $langs->trans('DisableMCPAI') . '</span>';
print '</td>';
print '</tr>';

print '</table>';
print '</div>';

print load_fiche_titre($langs->trans("PrivateModeTitle"), '', 'fas fa-lock');

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';

// Input Mode
print '<tr class="oddeven">';
print '<td>Default Interface Mode</td>';
print '<td>';
$input_modes = [
	'text' => '💬 ' . $langs->trans('OptionTextOnly'),
	'native' => '☁️ ' . $langs->trans('OptionCloudFast') . ' - ' . $langs->trans('OptionCloudFasthelp'),
	'whisper' => '🔒 ' . $langs->trans('OptionWhisperLocal') . ' - ' . $langs->trans('OptionWhisperLocalhelp')
];
print $form->selectarray('AI_DEFAULT_INPUT_MODE', $input_modes, getDolGlobalString('AI_DEFAULT_INPUT_MODE'), 0, 0, 0);
print '<br><span class="opacitymedium small">' . $langs->trans("InputMethodHelp") . '</span>';
print '</td>';
print '</tr>';

// Enhanced Privacy control
print '<tr class="oddeven">';
print '<td class="titlefield" width="30%">' . $langs->trans('ObfuscatePIIData') . '</td>';
print '<td>';
print ajax_constantonoff('AI_PRIVACY_REDACTION');
print ' <span class="opacitymedium">' . $langs->trans("RedactionHelp") . '</span>';
print '</td>';
print '</tr>';

// Confirmation Level
print '<tr class="oddeven">';
print '<td class="titlefield" width="30%">' . $langs->trans("AskConfirmation");
print ' <span class="fa fa-info-circle" title="' . $langs->trans("AskConfirmationHelp") . '"></span></td>';
print '<td>';
$confirmation_options = [
	'0' => $langs->trans("ConfirmNever"),
	'1' => $langs->trans("ConfirmWriteOnly"),
	'2' => $langs->trans("ConfirmAlways")
];
print $form->selectarray('AI_ASK_FOR_CONFIRMATION', $confirmation_options, getDolGlobalInt('AI_ASK_FOR_CONFIRMATION', 1), 0, 0, 0);
print '</td>';
print '</tr>';

// Logging
print '<tr class="oddeven">';
print '<td class="titlefield" width="30%">' . $langs->trans('EnableLogging') . '</td>';
print '<td>';
print ajax_constantonoff('AI_LOG_REQUESTS');
print ' <a href="' . DOL_URL_ROOT . '/ai/admin/log_viewer.php" target="_blank" class="button" style="padding-top: 4px; padding-bottom: 4px;">View Logs</a>';
print '</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>' . $langs->trans("LogRetention") . '</td>';
print '<td><input type="number" name="AI_LOG_RETENTION" value="' . getDolGlobalInt('AI_LOG_RETENTION', 30) . '" size="5"> (0 = Forever)</td>';
print '</tr>';

// System Prompt
print '<tr class="oddeven">';
print '<td colspan="2">';
print '<strong>' . $langs->trans("SystemPrompt") . '</strong><br>';
print '<span class="opacitymedium small">' . $langs->trans("SystemPromptHelp") . '</span><br>';
$doleditor = new DolEditor('AI_INTENT_PROMPT', $currentPrompt, '', 250, 'dolibarr_notes', 'In', false, false, true, ROWS_8, '90%');
$doleditor->Create();
print '</td>';
print '</tr>';

print '</table>';
print '</div>';

print '<div class="center"><input type="submit" class="button" value="'.$langs->trans("Save").'"></div>';
print '</form>';



// AI Provider Config and Connection testing
$services = getListOfAIServices();
$currentService = getDolGlobalString('AI_API_SERVICE');

print load_fiche_titre($langs->trans("AIProviderConfigTitle"), '', 'fa fa-plug');

if ((string) $currentService == '-1') {
	print '<div class="warning">'.$langs->trans("NoAIProviderSelected").' <a href="'.dol_buildpath('/ai/admin/setup.php', 1).'">'.$langs->trans("ConfigureHere").'</a></div>';
} else {
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';

	print '<tr class="oddeven"><td class="titlefield">'.$langs->trans("AIProvider").'</td><td>'.$services[$currentService]['label'].'</td></tr>';

	$prefix = 'AI_API_'.strtoupper($currentService);
	$modelVal = getDolGlobalString($prefix.'_MODEL', $services[$currentService]['textgeneration']);

	print '<tr class="oddeven"><td>'.$langs->trans("AI_API_MODEL").'</td><td>'.$modelVal.'</td></tr>';
	print '</table></div>';

	print '<div class="center">';

	if ($currentService && $currentService !== '-1') {
		print ' <a href="'.$_SERVER["PHP_SELF"].'?action=test_provider&service_key='.$currentService.'" class="button">Test Connection</a>';
	}

	print '</div>';
}
print '</div>';
print '</form>';

// External Access Configuration
print load_fiche_titre($langs->trans("AiMcpExternalAccess"), '', 'fas fa-lock-open text-danger');

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update_external">';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';

// Service User
print '<tr class="oddeven">';
print '<td>Service User <span class="fa fa-info-circle" title="' . $langs->trans("UserPermissionsTooltip") . '"></span></td>';
print '<td>';
print '<div style="display: flex; align-items: center;">';
print $form->select_dolusers(getDolGlobalInt('AI_MCP_USER_ID'), 'AI_MCP_USER_ID', 1);
print ' <input type="submit" class="button" value="'.$langs->trans("Save").'" style="margin-left: 20px;">';
print '</div>';
print '<span class="opacitymedium small">' . $langs->trans("DedicatedUserRecommendation") . '</span>';
print '</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td width="30%">API Key</td>';
print '<td>';
if ($apiKey) {
	print '<input type="text" id="apikey" value="'.$apiKey.'" readonly style="width:400px; padding:6px; background:#f4f4f4; border:1px solid #ccc; color:#555;">';
	print ' <button type="button" class="button small" onclick="navigator.clipboard.writeText(document.getElementById(\'apikey\').value)">' . $langs->trans("Copy") . '</button>';
	print ' <a class="button" href="'.$_SERVER["PHP_SELF"].'?action=generate_key&token='.newToken().'">Generate New Key</a>';
} else {
	print '<span class="opacitymedium">' . $langs->trans("NoKeyWarning") . '</span>';
	print ' <a class="button" href="' . $_SERVER["PHP_SELF"] . '?action=generate_key&token=' . newToken() . '">' . $langs->trans("GenerateKey") . '</a>';
}
print '</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>Endpoint URL</td>';
print '<td>';
 $endpoint = dol_buildpath('/ai/server/mcp_server.php', 2);
print '<input type="text" value="'.$endpoint.'" readonly style="width:600px; border:none; background:transparent;">';
print '</td>';
print '</tr>';

print '</table>';
print '</div>';

print '</form>';

// Configuration Examples
print '<br>';
print '<div style="background:#fcfcfc; border:1px solid #eee; padding:15px; border-radius:5px;">';
print '<strong>' . $langs->trans("ClaudeDesktopConfig") . '</strong><br>';
print '<pre style="background:#333; color:#fff; padding:10px; border-radius:4px; overflow:auto; margin-top:10px;">';
echo htmlspecialchars('{
  "mcpServers": {
    "dolibarr": {
      "command": "node",
      "args": ["/path/to/mcp-bridge.js"],
      "env": {
        "DOLIBARR_URL": "'.$endpoint.'",
        "DOLIBARR_API_KEY": "'.($apiKey ? $apiKey : "YOUR_KEY_HERE").'"
      }
    }
  }
}');
print '</pre>';
print '</div>';

print dol_get_fiche_end();

llxFooter();
$db->close();
