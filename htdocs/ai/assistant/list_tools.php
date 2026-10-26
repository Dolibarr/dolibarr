<?php
/* Copyright (C) 2026	Nick Fragoulis
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
 * \file    htdocs/ai/assistant/list_tools.php
 * \ingroup ai
 * \brief   Tools the current user may run, with their input schema, for the
 *          chat's "/" palette. Same filter as the model gets: a tool absent
 *          here is one the model would not be offered either. No AI provider
 *          is involved.
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1);
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', 1);
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', 1);
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', 1);
}
// The token is checked explicitly below by aiCheckCsrfToken(), like the other endpoints of the chat.
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', 1);
}

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/ai/class/mcp.class.php';
require_once DOL_DOCUMENT_ROOT.'/ai/tools/api_bridge.class.php';
require_once DOL_DOCUMENT_ROOT.'/ai/lib/ai.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Translate $langs
 * @var User $user
 */

// Same gates as execute_tool.php, which runs what the palette offers.
if (!isModEnabled('ai') || !getDolGlobalString('AI_ASSISTANT_ENABLED')) {
	accessforbidden('Module or feature not allowed');
}
if (!$user->hasRight('ai', 'assistant', 'use')) {
	accessforbidden();
}
aiCheckCsrfToken('ai/assistant/list_tools.php');

top_httphead('application/json');

$mcp = new McpHandler($db, $user, $conf, McpHandler::CTX_ASSISTANT);
$mcp->loadTools();

$out = array();
foreach ($mcp->getToolsSchemaForLLM() as $def) {
	$name = (string) ($def['name'] ?? '');
	if ($name === '') {
		continue;
	}
	$schema = (isset($def['inputSchema']) && is_array($def['inputSchema'])) ? $def['inputSchema'] : array();
	$props = array();
	foreach (($schema['properties'] ?? array()) as $pname => $pdef) {
		// Only what a form needs: type, help text, choices, and for a list of
		// records (invoice lines) the fields of one record.
		$props[$pname] = array(
			'type' => (string) ($pdef['type'] ?? 'string'),
			'description' => dol_trunc((string) ($pdef['description'] ?? ''), 200),
			'enum' => array_values((array) ($pdef['enum'] ?? array())),
			'items' => (isset($pdef['items']['properties']) && is_array($pdef['items']['properties'])) ? array_keys($pdef['items']['properties']) : array()
		);
	}
	// A plain name for the person choosing, the API name shown beside it.
	$tool = $mcp->toolsByName[$name] ?? null;
	$route = ($tool instanceof ToolApiBridge) ? $tool->toolRoute($name) : null;
	$out[] = array(
		'name' => $name,
		'title' => aiPaletteToolTitle($langs, $name, $route),
		'description' => dol_trunc(aiPaletteToolDescription((string) ($def['description'] ?? '')), 200),
		'properties' => $props,
		'required' => array_values((array) ($schema['required'] ?? array()))
	);
}

usort($out,
	/**
	 * @param array{name:string,title:string} $a Tool
	 * @param array{name:string,title:string} $b Tool
	 * @return int
	 */
	function ($a, $b) {
		return strcmp($a['title'], $b['title']) ?: strcmp($a['name'], $b['name']);
	}
);

print json_encode(array('tools' => $out));

$db->close();
