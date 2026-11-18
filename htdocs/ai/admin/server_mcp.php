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

require '../../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 * @var Form $form
 */
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT . "/core/class/html.form.class.php";
require_once DOL_DOCUMENT_ROOT . "/core/class/doleditor.class.php";
require_once DOL_DOCUMENT_ROOT . "/ai/lib/ai.lib.php";
require_once DOL_DOCUMENT_ROOT . "/core/lib/functions2.lib.php";
require_once DOL_DOCUMENT_ROOT . "/core/lib/security.lib.php";
require_once DOL_DOCUMENT_ROOT . "/core/lib/security2.lib.php";


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
$backtopage = GETPOST('backtopage', 'alpha');


/**
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
		if ($user_id > 0) {
			$res = dolibarr_set_const($db, "AI_MCP_USER_ID", $user_id,  'int', 0, '', $conf->entity);
		} else {
			$res = dolibarr_del_const($db, "AI_MCP_USER_ID", $conf->entity);
		}
		if ($res < 0) {
			$error++;
		}
	}

	if (GETPOSTISSET('AI_MCP_API_KEY')) {
		$mcpapikey = trim(GETPOST('AI_MCP_API_KEY', 'alphanohtml'));
		if ($mcpapikey !== '') {
			$res = dolibarr_set_const($db, "AI_MCP_API_KEY", $mcpapikey, 'chaine', 0, '', $conf->entity);
		} else {
			$res = dolibarr_del_const($db, "AI_MCP_API_KEY", $conf->entity);
		}
		if ($res < 0) {
			$error++;
		}
	}

	// Cache duration (in seconds) returned as ttlMs by server/discover. Empty means default value.
	if (GETPOSTISSET('AI_MCP_DISCOVER_TTL')) {
		if (GETPOST('AI_MCP_DISCOVER_TTL', 'alphanohtml') !== '') {
			$res = dolibarr_set_const($db, "AI_MCP_DISCOVER_TTL", max(0, GETPOSTINT('AI_MCP_DISCOVER_TTL')), 'int', 0, '', $conf->entity);
		} else {
			$res = dolibarr_del_const($db, "AI_MCP_DISCOVER_TTL", $conf->entity);
		}
		if ($res < 0) {
			$error++;
		}
	}

	// Checkbox has its own form, flagged by a hidden field because an unchecked checkbox is not posted
	if (GETPOSTISSET('AI_MCP_BEHIND_BASIC_AUTH_FORM')) {
		$res = dolibarr_set_const($db, "AI_MCP_BEHIND_BASIC_AUTH", GETPOSTINT("AI_MCP_BEHIND_BASIC_AUTH"), 'int', 0, '', $conf->entity);
		if ($res < 0) {
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
if ($action == 'confirm_generate_key' && GETPOST('confirm', 'alpha') == 'yes') {
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

	// Redirect, so a reload of the page does not generate another key
	header("Location: ".$_SERVER["PHP_SELF"]);
	exit;
}


/*
 * VIEW
 */

$help_url = '';
$title = "AiSetup";

llxHeader('', $langs->trans($title), $help_url, '', 0, 0, '', '', '', 'mod-ai page-admin');

$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.img_picto($langs->trans("BackToModuleList"), 'back', 'class="pictofixedwidth"').'<span class="hideonsmartphone">'.$langs->trans("BackToModuleList").'</span></a>';

print load_fiche_titre($langs->trans($title), $linkback, 'title_setup');


$head = aiAdminPrepareHead();
print dol_get_fiche_head($head, 'servermcp', "MCP Server", -1, "ai");

//print '<span class="opacitymedium">' . $langs->trans("ConfigHelp") . '</span><br><br>';

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
print '<div class="neutral">';
//print '<table class="noborder centpercent">';

// Enable/Disable
//print '<tr class="oddeven">';
//print '<td class="titlefield" width="30%">'
print $form->textwithpicto($langs->trans('EnableMCPServer'), $langs->trans('DisableMCPAI'));
//print '</td>';
//print '<td>';
print ' &nbsp; ';
print ajax_constantonoff('AI_MCP_ENABLED', array(), null, 0, 0, 1);
//print ' <span class="opacitymedium">' . $langs->trans('DisableMCPAI') . '</span>';
//print '</td>';
//print '</tr>';

//print '</table>';
print '</div>';

print '<br>';
print '<br>';

if (getDolGlobalString('AI_MCP_ENABLED')) {
	/*
	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';

	// Input Mode
	print '<tr class="oddeven">';
	print '<td>';
	print $form->textwithpicto('Default Interface', $langs->trans("InputMethodHelp"));
	print '</td>';
	print '<td>';
	$input_modes = [
		'text' => $langs->trans('OptionTextOnly'),
		'native' => $langs->trans('OptionCloudFast') . ' - ' . $langs->trans('OptionCloudFasthelp'),
		'whisper' => $langs->trans('OptionWhisperLocal') . ' - ' . $langs->trans('OptionWhisperLocalhelp')
	];
	print $form->selectarray('AI_DEFAULT_INPUT_MODE', $input_modes, getDolGlobalString('AI_DEFAULT_INPUT_MODE'), 0, 0, 0);
	print '</td>';
	print '</tr>';

	// Enhanced Privacy control
	print '<tr class="oddeven">';
	print '<td>' . $langs->trans('ObfuscatePIIData') . '</td>';
	print '<td>';
	print ajax_constantonoff('AI_PRIVACY_REDACTION');
	print ' &nbsp; <span class="opacitymedium">' . $langs->trans("RedactionHelp") . '</span>';
	print '</td>';
	print '</tr>';

	// Confirmation Level
	print '<tr class="oddeven">';
	print '<td>' . $form->textwithpicto($langs->trans("AskConfirmation"), $langs->trans("AskConfirmationHelp")).'</td>';
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
	print '<td>' . $langs->trans('EnableLogging') . '</td>';
	print '<td>';
	print ajax_constantonoff('AI_LOG_REQUESTS');
	print ' &nbsp; <a href="' . DOL_URL_ROOT . '/ai/admin/log_viewer.php" target="_blank" style="padding-top: 4px; padding-bottom: 4px;">'.$langs->trans("ViewLogs").'</a>';
	print '</td>';
	print '</tr>';

	print '<tr class="oddeven">';
	print '<td>' . $langs->trans("LogRetention") . '</td>';
	print '<td><input class="width50" type="number" name="AI_LOG_RETENTION" value="' . getDolGlobalInt('AI_LOG_RETENTION', 30) . '"> (0 = Forever)</td>';
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

	print '<br>';
	print '<br>';

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
		$modelVal = getDolGlobalString($prefix.'_MODEL_TEXT', $services[$currentService]['textgeneration']['default']);

		print '<tr class="oddeven"><td>'.$langs->trans("AI_API_MODEL").'</td><td>'.$modelVal.'</td></tr>';
		print '</table></div>';

		print '<div class="center">';

		if ($currentService && $currentService !== '-1') {
			print ' <a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=test_provider&token='.newToken().'&service_key='.$currentService.'" class="button">Test Connection</a>';
		}

		print '</div>';
	}

	print '<br><br>';
	*/

	// External Access Configuration

	// A new key makes the current one useless for the tools already using it: ask first
	if ($action == 'ask_generate_key') {
		print $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans('RegenerateKey'), $langs->trans('ConfirmRegenerateMcpApiKey'), 'confirm_generate_key', '', 0, 1);
	}

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update_external">';

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';

	print '<tr class="liste_titre"><td class="titlefieldmiddle">'.$langs->trans("Parameters").'</td><td></td><td></td></tr>';

	// Service User
	print '<tr class="oddeven">';
	print '<td class="fieldrequired">Service User '.img_picto($langs->trans("UserPermissionsTooltip"), 'info').'</td>';
	print '<td>';
	print '<div style="display: flex; align-items: center;">';
	// Use -1 when not set, so select_dolusers() does not preselect the current user
	print $form->select_dolusers(getDolGlobalInt('AI_MCP_USER_ID', -1), 'AI_MCP_USER_ID', 1);
	print '</div>';
	print '<span class="opacitymedium small">' . $langs->trans("DedicatedUserRecommendation") . '</span>';
	print '</td>';
	print '<td></td>';
	print '</tr>';

	// API Key
	print '<tr class="oddeven">';
	print '<td class="fieldrequired">'.$form->textwithpicto('MCP API Key', $langs->trans("McpApiKeyHelp")).'</td>';
	print '<td>';
	// The key is hidden in a password field, the eye picto shows it
	print '<input type="password" class="minwidth400 input-icon-password" id="AI_MCP_API_KEY" name="AI_MCP_API_KEY" value="'.dolPrintHTMLForAttribute($apiKey).'" autocomplete="new-password">';
	print showEyeForField('eyeAI_MCP_API_KEY', 'AI_MCP_API_KEY');
	if (!$apiKey) {
		print ' <span class="opacitymedium">' . $langs->trans("NoKeyWarning") . '</span>';
	}
	print '</td>';
	print '<td class="right nowraponall">';
	if ($apiKey) {
		// Replacing an existing key is confirmed first
		print '<a class="button smallpaddingimp reposition" href="'.$_SERVER["PHP_SELF"].'?action=ask_generate_key&token='.newToken().'">'.img_picto('', 'refresh', 'class="pictofixedwidth"').$langs->trans("RegenerateKey").'</a>';
	} else {
		print '<a class="button smallpaddingimp reposition" href="'.$_SERVER["PHP_SELF"].'?action=confirm_generate_key&confirm=yes&token='.newToken().'">'.img_picto('', 'refresh', 'class="pictofixedwidth"').$langs->trans("GenerateKey").'</a>';
	}
	print '</td>';
	print '</tr>';

	$endpoint = dol_buildpath('/ai/server/mcp_server.php', 3);
	// Name of the MCP server in the configuration examples: the database name, restricted to safe chars for a command line
	$mcpservername = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $conf->db->name);
	// Name of the environment variable holding the key, suffixed with the database name so several instances can share the same .env file
	$mcpapikeyenvname = 'DOLIBARR_MCP_API_KEY_'.dol_strtoupper(preg_replace('/[^a-zA-Z0-9_]/', '_', $conf->db->name));

	// Server behind a HTTP Basic authentication: the Authorization header is then taken, so the key goes into the DOLAPIKEY header
	$behindbasicauth = getDolGlobalInt('AI_MCP_BEHIND_BASIC_AUTH');
	$basicauthplaceholder = 'BASE64_OF_LOGIN:PASSWORD';
	// If this page is itself behind the Basic authentication, reuse the credentials sent by the browser to fill the examples
	$basicauthvalue = $basicauthplaceholder;
	$authheader = empty($_SERVER['HTTP_AUTHORIZATION']) ? (empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) ? '' : $_SERVER['REDIRECT_HTTP_AUTHORIZATION']) : $_SERVER['HTTP_AUTHORIZATION'];
	$reg = array();
	if (preg_match('/^Basic\s+([a-zA-Z0-9+\/=]+)$/i', $authheader, $reg)) {
		$basicauthvalue = $reg[1];
	} elseif (!empty($_SERVER['PHP_AUTH_USER'])) {
		$basicauthvalue = base64_encode($_SERVER['PHP_AUTH_USER'].':'.(empty($_SERVER['PHP_AUTH_PW']) ? '' : $_SERVER['PHP_AUTH_PW']));
	}
	// If option was never set, enable it by default when a Basic authentication is detected
	if (getDolGlobalString('AI_MCP_BEHIND_BASIC_AUTH') === '' && $basicauthvalue != $basicauthplaceholder) {
		$behindbasicauth = 1;
	}
	// Custom connector of Mistral Vibe web: the Basic authentication credentials, if any, are put into the URL
	$vibeweburl = $endpoint;
	if ($behindbasicauth) {
		$credentials = 'LOGIN:PASSWORD';
		$decoded = ($basicauthvalue != $basicauthplaceholder) ? base64_decode($basicauthvalue, true) : false;
		if (!empty($_SERVER['PHP_AUTH_USER'])) {
			$credentials = rawurlencode($_SERVER['PHP_AUTH_USER']).':'.rawurlencode(empty($_SERVER['PHP_AUTH_PW']) ? '' : $_SERVER['PHP_AUTH_PW']);
		} elseif ($decoded !== false && strpos($decoded, ':') !== false) {
			list($basiclogin, $basicpass) = explode(':', $decoded, 2);
			$credentials = rawurlencode($basiclogin).':'.rawurlencode($basicpass);
		}
		$vibeweburl = preg_replace('/^(https?:\/\/)/i', '${1}'.$credentials.'@', $endpoint);
	}
	// URL to test in a browser the answer of the MCP server with the list of tools
	$testurl = $vibeweburl.'?DOLAPIKEY='.urlencode($apiKey ? $apiKey : 'YOUR_KEY_HERE').'&debug=1';

	print '<tr class="oddeven">';
	print '<td>'.$form->textwithpicto('MCP Endpoint URL', $langs->trans("MCPEndpointTestHelp", '<br><br><span class="wordbreak">'.$testurl.'</span>'), 1, 'info', '', 1, 3, 'mcpendpointtest').'</td>';
	print '<td>';
	print '<input type="text" id="endpoint" value="'.$endpoint.'" readonly style="width:600px; border:none; background:transparent;">';
	print '</td>';
	print '<td></td>';
	print '</tr>';

	// Cache duration of the server/discover answer
	print '<tr class="oddeven">';
	print '<td>'.$form->textwithpicto($langs->trans("McpDiscoverTtl"), $langs->trans("McpDiscoverTtlHelp")).'</td>';
	print '<td>';
	print '<input type="number" min="0" class="width75" id="AI_MCP_DISCOVER_TTL" name="AI_MCP_DISCOVER_TTL" value="'.getDolGlobalInt('AI_MCP_DISCOVER_TTL', 3600).'"> '.$langs->trans("Seconds");
	print '</td>';
	print '<td></td>';
	print '</tr>';

	print '</table>';
	print '</div>';

	print '<div class="center"><input type="submit" class="button" value="'.$langs->trans("Save").'"> &nbsp; </div>';

	print '</form>';
	print '<br>';

	print '<br>';

	// Configuration Examples
	print '<br>';
	print '<div style="background:#fcfcfc; border:1px solid #eee; padding:15px; border-radius:5px;">';

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update_external">';
	print '<input type="hidden" name="AI_MCP_BEHIND_BASIC_AUTH_FORM" value="1">';
	print '<input type="checkbox" id="AI_MCP_BEHIND_BASIC_AUTH" name="AI_MCP_BEHIND_BASIC_AUTH" value="1"'.($behindbasicauth ? ' checked' : '').' onchange="this.form.submit();">';
	print ' <label for="AI_MCP_BEHIND_BASIC_AUTH">'.$form->textwithpicto($langs->trans("MCPServerBehindBasicAuth"), $langs->trans("MCPServerBehindBasicAuthHelp", $basicauthplaceholder)).'</label>';
	print '</form>';
	print '<br>';

	// Command line to declare the MCP server into Mistral Vibe, the key being read from its .env file
	print '<strong>' . $langs->trans("MistralVibeConfig") . '</strong><br>';
	print '<pre style="background:#333; color:#fff; padding:10px; border-radius:4px; overflow:auto; margin-top:10px; white-space: break-spaces;">';
	if ($behindbasicauth) {
		echo htmlspecialchars('vibe mcp add '.$mcpservername.' --url '.$endpoint.' --transport streamable-http --header \'Authorization=Basic '.$basicauthvalue.'\' --header \'DOLAPIKEY='.($apiKey ? $apiKey : "YOUR_KEY_HERE").'\'');
	} else {
		echo htmlspecialchars('vibe mcp add '.$mcpservername.' --url '.$endpoint.' --transport streamable-http --api-key-env '.$mcpapikeyenvname."\n\n");
		echo htmlspecialchars($langs->transnoentitiesnoconv("MistralVibeConfigEnv", '~/.vibe/.env')."\n");
		echo htmlspecialchars($mcpapikeyenvname.'="'.($apiKey ? $apiKey : "YOUR_KEY_HERE").'"');
	}
	print '</pre>';

	print '<br><strong>' . $langs->trans("MistralVibeWebConnector") . '</strong><br>';
	print '<pre style="background:#333; color:#fff; padding:10px; border-radius:4px; overflow:auto; margin-top:10px; white-space: break-spaces;">';
	echo htmlspecialchars($langs->transnoentitiesnoconv("MistralVibeWebConnectorUrl")."\n");
	echo htmlspecialchars($vibeweburl."\n\n");
	echo htmlspecialchars($langs->transnoentitiesnoconv("MistralVibeWebConnectorAuth", 'DOLAPIKEY')."\n");
	echo htmlspecialchars($apiKey ? $apiKey : "YOUR_KEY_HERE");
	print '</pre>';

	// Command line to declare the MCP server into Claude Code
	print '<br><strong>' . $langs->trans("ClaudeCodeConfig") . '</strong><br>';
	print '<pre style="background:#333; color:#fff; padding:10px; border-radius:4px; overflow:auto; margin-top:10px; white-space: break-spaces;">';
	if ($behindbasicauth) {
		echo htmlspecialchars('claude mcp add --transport http '.$mcpservername.' '.$endpoint.' --header "Authorization: Basic '.$basicauthvalue.'" --header "DOLAPIKEY: '.($apiKey ? $apiKey : "YOUR_KEY_HERE").'"');
	} else {
		echo htmlspecialchars('claude mcp add --transport http '.$mcpservername.' '.$endpoint.' --header "Authorization: Bearer '.($apiKey ? $apiKey : "YOUR_KEY_HERE").'"');
	}
	print '</pre>';

	print '<br><strong>' . $langs->trans("ClaudeDesktopConfig") . '</strong><br>';
	print '<pre style="background:#333; color:#fff; padding:10px; border-radius:4px; overflow:auto; margin-top:10px; white-space: break-spaces;">';
	echo htmlspecialchars('
	{
	  "mcpServers": {
	    "'.$mcpservername.'": {
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
}

print dol_get_fiche_end();

llxFooter();
$db->close();
