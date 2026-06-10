<?php
/* Copyright (C) 2004-2017  Laurent Destailleur         <eldy@users.sourceforge.net>
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
 * \file    htdocs/ai/admin/configure_tools.php
 * \ingroup ai
 * \brief   Admin page to configure which MCP tools are available per context.
 *
 * Two independent allow-lists are managed here:
 *   AI_ASSISTANT_ALLOWED_TOOLS  — tools visible to the private chat assistant
 *   AI_MCP_SERVER_ALLOWED_TOOLS — tools visible to external MCP clients
 *
 * System tools (isSystem() = true) are always shown as locked-on and are never
 * written into the allow-list constants — McpHandler forces them through at runtime.
 *
 * Constant semantics (same for both constants):
 *   ''     → not yet configured → all non-system tools are allowed (default open)
 *   'NONE' → admin explicitly disabled every non-system tool
 *   else   → comma-separated list of allowed tool names (explicit allow-list)
 */

require '../../main.inc.php';

/**
 * @var Conf        $conf
 * @var DoliDB      $db
 * @var HookManager $hookmanager
 * @var Translate   $langs
 * @var User        $user
 */

require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/ai/lib/ai.lib.php';
require_once DOL_DOCUMENT_ROOT . '/ai/class/mcp.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';

$langs->loadLangs(array('admin', 'other', 'main'));

// Access control
if (!$user->admin) {
	accessforbidden();
}
if (!isModEnabled('ai')) {
	accessforbidden('Module AI not activated.');
}

// Parameters
$action   = GETPOST('action', 'aZ09');
$toolcontext  = GETPOST('toolcontext', 'alpha');
$toolname = GETPOST('toolname', 'alpha');
$mode     = GETPOST('mode', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

// Load unfiltered schema
$mcpHandler = new McpHandler($db, $user, $conf, McpHandler::CTX_ASSISTANT);
$unfilteredSchema = $mcpHandler->getToolsSchemaUnfiltered();

// Build grouped lists from the schema metadata set by getToolsSchemaUnfiltered()
$allDiscoveredTools = array();  // names of all non-system tools
$groupedNormalTools = array();  // non-system tools grouped by class_name
$groupedSystemTools = array();  // system tools grouped by class_name

if (!empty($unfilteredSchema) && is_array($unfilteredSchema)) {
	foreach ($unfilteredSchema as $def) {
		$name         = $def['name'];
		$isSystem     = !empty($def['is_system']);
		$classNameKey = !empty($def['class_name']) ? (string) $def['class_name'] : 'DefaultTools';

		if (!$isSystem) {
			$allDiscoveredTools[]                = $name;
			$groupedNormalTools[$classNameKey][] = $def;
		} else {
			$groupedSystemTools[$classNameKey][] = $def;
		}
	}
}

$rawAst     = getDolGlobalString('AI_ASSISTANT_ALLOWED_TOOLS');
$rawMcp     = getDolGlobalString('AI_MCP_SERVER_ALLOWED_TOOLS');
$astAllowed = McpHandler::resolveAllowList($rawAst, $allDiscoveredTools);
$mcpAllowed = McpHandler::resolveAllowList($rawMcp, $allDiscoveredTools);

/**
 * ACTIONS
 */

if ($action == 'addrights' || $action == 'delrights') {
	if (GETPOST('token', 'alpha') !== $_SESSION['token']) {
		accessforbidden('Bad token');
	}

	if (!empty($toolcontext) && !empty($toolname)) {
		if ($toolcontext === 'ast') {
			if ($action == 'addrights') {
				if (!in_array($toolname, $astAllowed, true)) {
					$astAllowed[] = $toolname;
				}
			} else {
				$astAllowed = array_values(array_diff($astAllowed, array($toolname)));
			}
			$val = empty($astAllowed) ? 'NONE' : implode(',', $astAllowed);
			dolibarr_set_const($db, 'AI_ASSISTANT_ALLOWED_TOOLS', $val, 'chaine', 0, '', $conf->entity);
		}

		if ($toolcontext === 'mcp') {
			if ($action == 'addrights') {
				if (!in_array($toolname, $mcpAllowed, true)) {
					$mcpAllowed[] = $toolname;
				}
			} else {
				$mcpAllowed = array_values(array_diff($mcpAllowed, array($toolname)));
			}
			$val = empty($mcpAllowed) ? 'NONE' : implode(',', $mcpAllowed);
			dolibarr_set_const($db, 'AI_MCP_SERVER_ALLOWED_TOOLS', $val, 'chaine', 0, '', $conf->entity);
		}
	}

	header('Location: ' . $_SERVER['PHP_SELF']);
	exit;
}

if ($action == 'apply_preset' && !empty($toolcontext) && !empty($mode)) {
	if (GETPOST('token', 'alpha') !== $_SESSION['token']) {
		accessforbidden('Bad token');
	}

	$resultSet = array();

	if ($mode === 'all') {
		$resultSet = $allDiscoveredTools;
	} elseif ($mode === 'none') {
		$resultSet = array();
	} elseif ($mode === 'readonly') {
		foreach ($allDiscoveredTools as $tName) {
			// Tools starting with a mutating verb are write tools.
			// This heuristic matches the naming convention used throughout ai/tools/*.
			// External tool authors should follow the same convention.
			if (!preg_match('/^(create|update|delete|add|remove|change|write|edit|validate|pay|send)/i', $tName)) {
				$resultSet[] = $tName;
			}
		}
	}

	$val         = empty($resultSet) ? 'NONE' : implode(',', $resultSet);
	$constTarget = ($toolcontext === 'mcp') ? 'AI_MCP_SERVER_ALLOWED_TOOLS' : 'AI_ASSISTANT_ALLOWED_TOOLS';
	dolibarr_set_const($db, $constTarget, $val, 'chaine', 0, '', $conf->entity);

	header('Location: ' . $_SERVER['PHP_SELF']);
	exit;
}

/*
 * VIEW
 */

$help_url = '';
$title = 'AiSetup';
llxHeader('', $langs->trans($title), '', '', 0, 0, array(dol_buildpath('/ai/js/ai.js', 1)), array(dol_buildpath('/ai/css/ai.css', 1)), '', 'mod-ai page-admin');

$linkback = '<a href="' . ($backtopage ? $backtopage : DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . img_picto($langs->trans("BackToModuleList"), 'back', 'class="pictofixedwidth"') . '<span class="hideonsmartphone">' . $langs->trans("BackToModuleList") . '</span></a>';

print load_fiche_titre($langs->trans($title), $linkback, 'title_setup');

$head = aiAdminPrepareHead();
print dol_get_fiche_head($head, 'tools', 'MCP Server', -1, 'ai');

print '<span class="opacitymedium">' . $langs->trans("ToolAccessControlHelp") . '</span><br><br>';

print '<div class="marginleftonly" style="display:flex; flex-wrap:wrap; gap:40px; margin-top:15px; padding-top:15px; border-top:1px solid #ddd;">';

// Presets For Chat Assistant
print '<div>';
print '<strong>' . $langs->trans('PresetsForChatAssistant') . ':</strong><br>';
print '<a class="button" href="' . dolBuildUrl($_SERVER['PHP_SELF'], array('action' => 'apply_preset', 'toolcontext' => 'ast', 'mode' => 'all'), true) . '" style="margin:4px 2px;">' . $langs->trans('AllTools') . '</a>';
print '<a class="button" href="' . dolBuildUrl($_SERVER['PHP_SELF'], array('action' => 'apply_preset', 'toolcontext' => 'ast', 'mode' => 'readonly'), true) . '" style="margin:4px 2px;">' . $langs->trans('ViewOnly') . '</a>';
print '<a class="button" href="' . dolBuildUrl($_SERVER['PHP_SELF'], array('action' => 'apply_preset', 'toolcontext' => 'ast', 'mode' => 'none'), true) . '" style="margin:4px 2px;">' . $langs->trans('None') . '</a>';
print '</div>';

// Presets For MCP Server
print '<div>';
print '<strong>' . $langs->trans('PresetsForMcpServer') . ':</strong><br>';
print '<a class="button" href="' . dolBuildUrl($_SERVER['PHP_SELF'], array('action' => 'apply_preset', 'toolcontext' => 'mcp', 'mode' => 'all'), true) . '" style="margin:4px 2px;">' . $langs->trans('AllTools') . '</a>';
print '<a class="button" href="' . dolBuildUrl($_SERVER['PHP_SELF'], array('action' => 'apply_preset', 'toolcontext' => 'mcp', 'mode' => 'readonly'), true) . '" style="margin:4px 2px;">' . $langs->trans('ViewOnly') . '</a>';
print '<a class="button" href="' . dolBuildUrl($_SERVER['PHP_SELF'], array('action' => 'apply_preset', 'toolcontext' => 'mcp', 'mode' => 'none'), true) . '" style="margin:4px 2px;">' . $langs->trans('None') . '</a>';
print '</div>';

print '</div>';


// Tools table
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent" id="toolsTable">';
print '<tr class="liste_titre">';
print '<td class="tdoverflowmax200" style="min-width: 130px;">' . $langs->trans('Tool') . '</td>';
print '<td class="center" style="min-width: 60px;">' . $langs->trans('ToolActionType') . '</td>';
print '<td class="hideonsmartphone">' . $langs->trans('ToolDescription') . '</td>';
print '<td class="center nowraponall">' . $langs->trans('ChatAssistant') . '</td>';
print '<td class="center nowraponall">' . $langs->trans('McpServer') . '</td>';
print '</tr>';

if (empty($groupedNormalTools) && empty($groupedSystemTools)) {
	print '<tr class="oddeven"><td colspan="5" class="opacitymedium">' . $langs->trans('NoMcpToolsDiscovered') . '</td></tr>';
} else {
	$groupId = 0;
	$finalGroupsList = array_merge($groupedNormalTools, $groupedSystemTools);

	foreach ($finalGroupsList as $categoryName => $definitions) {
		$groupId++;

		print '<tr class="trgroup" data-group="group-' . $groupId . '">';
		print '<td colspan="5" class="mcp-trigger-collapse" style="cursor:pointer;">';
		print '<span class="toggle-icon">▼</span> ' . dol_escape_htmltag($categoryName);
		print '</td>';
		print '</tr>';

		foreach ($definitions as $def) {
			$name     = $def['name'];
			$desc     = !empty($def['description']) ? $def['description'] : '-';
			$isSystem = !empty($def['is_system']);

			$isAstOn = $isSystem ? true : in_array($name, $astAllowed, true);
			$isMcpOn = $isSystem ? true : in_array($name, $mcpAllowed, true);

			if (preg_match('/^(create|update|delete|add|remove|change|write|edit|validate|pay|send)/i', $name)) {
				$type      = 'write';
				$typeBadge = '<span class="badge badge-status8" style="display: inline-block;">' . $langs->trans('Modify') . '</span>';
			} else {
				$type      = 'read';
				$typeBadge = '<span class="badge badge-status4" style="display: inline-block;">' . $langs->trans('ViewOnly') . '</span>';
			}

			print '<tr class="oddeven group-' . $groupId . '" data-tool-name="' . dol_escape_htmltag($name) . '" data-tool-type="' . $type . '">';

			// Tool
			print '<td style="padding-left:20px; min-width: 130px; word-break: break-all;" class="tdoverflowmax200">';
			print '<strong>' . dol_escape_htmltag($name) . '</strong>';
			if ($isSystem) {
				print '<br><span class="badgeneutral">' . $langs->trans('System') . '</span>';
			}
			print '</td>';

			// Type Badge
			print '<td class="center">' . $typeBadge . '</td>';

			// Tool Description (hidden on mobile)
			print '<td class="small opacitymedium hideonsmartphone">' . dol_escape_htmltag($desc) . '</td>';

			$lockCssClass = $isSystem ? ' opacitymedium disabled' : '';

			// Chat Assistant Switch
			print '<td class="center nowraponall">';
			if ($isSystem) {
				print img_picto($langs->trans('Active'), 'switch_on', '', 0, 0, 0, '', 'opacitymedium');
			} else {
				$actAst   = $isAstOn ? 'delrights' : 'addrights';
				$pictoAst = $isAstOn ? 'switch_on' : 'switch_off';
				$urlAst   = dolBuildUrl($_SERVER["PHP_SELF"], [
					'action'      => $actAst,
					'toolcontext' => 'ast',
					'toolname'    => $name
				], true);

				print '<a class="ctx-ast-link' . $lockCssClass . '" href="' . $urlAst . '">';
				print img_picto($langs->trans($isAstOn ? 'Remove' : 'Add'), $pictoAst);
				print '</a>';
			}
			print '</td>';

			// MCP Server Switch
			print '<td class="center nowraponall">';
			if ($isSystem) {
				print img_picto($langs->trans('Active'), 'switch_on', '', 0, 0, 0, '', 'opacitymedium');
			} else {
				$actMcp   = $isMcpOn ? 'delrights' : 'addrights';
				$pictoMcp = $isMcpOn ? 'switch_on' : 'switch_off';
				$urlMcp   = dolBuildUrl($_SERVER["PHP_SELF"], [
					'action'      => $actMcp,
					'toolcontext' => 'mcp',
					'toolname'    => $name
				], true);

				print '<a class="ctx-mcp-link' . $lockCssClass . '" href="' . $urlMcp . '">';
				print img_picto($langs->trans($isMcpOn ? 'Remove' : 'Add'), $pictoMcp);
				print '</a>';
			}
			print '</td>';

			print '</tr>' . "\n";
		}
	}
}

print '</table>';
print '</div>';

print dol_get_fiche_end();

llxFooter();
$db->close();
