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
 * Thin AJAX wrapper around getAiProviderModelList() (ai/lib/ai.lib.php), which
 * queries the provider's model listing endpoint and caches the result for one
 * hour, so the chat model picker and the admin datalists don't hammer the
 * provider. Response: {"service":..., "models":[ids]}.
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
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var User $user
 */

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

$list = getAiProviderModelList($db);

print json_encode(array('service' => $list['service'], 'models' => $list['models']));
