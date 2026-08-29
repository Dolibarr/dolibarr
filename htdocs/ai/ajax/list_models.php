<?php
/* Copyright (C) 2026	Jose Martinez			<jose.martinez@pichinov.com>
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
 * \file htdocs/ai/ajax/list_models.php
 * \ingroup ai
 * \brief Return the list of model ids available from the configured AI provider.
 *
 * Queries the provider's model listing endpoint (Anthropic GET /v1/models,
 * Google GET /v1beta/models, OpenAI-compatible GET /v1/models) and caches the
 * result for one hour in a JSON constant, so the chat model picker and the
 * admin datalists don't hammer the provider. Response: {"service":..., "models":[ids]}.
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1');
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/ai/lib/ai.lib.php';

top_httphead('application/json');

if (!isModEnabled('ai')) {
	http_response_code(403);
	print json_encode(array('error' => 'Module not enabled'));
	exit;
}
// Same gate as the assistant itself: the dedicated right, or admin.
if (empty($user->admin) && !$user->hasRight('ai', 'assistant', 'use')) {
	http_response_code(403);
	print json_encode(array('error' => 'Permission denied'));
	exit;
}

$serviceKey = getDolGlobalString('AI_API_SERVICE');
if (empty($serviceKey) || $serviceKey == '-1') {
	print json_encode(array('service' => '', 'models' => array()));
	exit;
}

// 1-hour cache in a JSON constant (avoid one provider round-trip per page)
$cacheraw = getDolGlobalString('AI_MODELS_LIST_CACHE');
if ($cacheraw) {
	$cache = json_decode($cacheraw, true);
	if (is_array($cache) && !empty($cache['service']) && $cache['service'] === $serviceKey
		&& !empty($cache['ts']) && (dol_now() - (int) $cache['ts']) < 3600
		&& !empty($cache['models']) && is_array($cache['models'])) {
		print json_encode(array('service' => $serviceKey, 'models' => $cache['models'], 'cached' => true));
		exit;
	}
}

$servicesList = getListOfAIServices();
$adapterType = $servicesList[$serviceKey]['adapter_type'] ?? 'openai';
$defUrl = $servicesList[$serviceKey]['url'] ?? '';
$baseUrl = rtrim(getDolGlobalString('AI_API_'.strtoupper($serviceKey).'_URL') ?: $defUrl, '/');

$apiKey = getDolGlobalString('AI_API_'.strtoupper($serviceKey).'_KEY');
if (preg_match('/^crypt:/', $apiKey)) {
	$apiKey = dolDecrypt($apiKey, $conf->file->instance_unique_id);
}
if (empty($apiKey) || empty($baseUrl)) {
	print json_encode(array('service' => $serviceKey, 'models' => array(), 'error' => 'Provider not configured'));
	exit;
}

$models = array();
if ($adapterType === 'anthropic') {
	$headers = array('x-api-key: '.$apiKey, 'anthropic-version: 2023-06-01');
	$res = getURLContent($baseUrl.'/models?limit=100', 'GET', '', 1, $headers, array('http', 'https'), 2);
	$json = json_decode($res['content'] ?? '', true);
	foreach ((array) ($json['data'] ?? array()) as $m) {
		if (!empty($m['id'])) {
			$models[] = (string) $m['id'];
		}
	}
} elseif ($adapterType === 'google') {
	$res = getURLContent($baseUrl.'/models?pageSize=200&key='.urlencode($apiKey), 'GET', '', 1, array(), array('http', 'https'), 2);
	$json = json_decode($res['content'] ?? '', true);
	foreach ((array) ($json['models'] ?? array()) as $m) {
		if (!empty($m['name'])) {
			$models[] = preg_replace('/^models\//', '', (string) $m['name']);
		}
	}
} else {
	// OpenAI-compatible providers (OpenAI, Mistral, Groq, DeepSeek, custom...)
	$headers = array('Authorization: Bearer '.$apiKey);
	$res = getURLContent($baseUrl.'/models', 'GET', '', 1, $headers, array('http', 'https'), 2);
	$json = json_decode($res['content'] ?? '', true);
	foreach ((array) ($json['data'] ?? array()) as $m) {
		if (!empty($m['id'])) {
			$models[] = (string) $m['id'];
		}
	}
}

$models = array_values(array_unique($models));
sort($models);

if (count($models)) {
	dolibarr_set_const($db, 'AI_MODELS_LIST_CACHE', json_encode(array('service' => $serviceKey, 'ts' => dol_now(), 'models' => $models)), 'chaine', 0, '', $conf->entity);
}

print json_encode(array('service' => $serviceKey, 'models' => $models, 'cached' => false));
